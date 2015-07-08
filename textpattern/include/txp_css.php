<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2015 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Styles panel.
 *
 * @package Admin\CSS
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'css') {
    require_privs('css');

    bouncer($step, array(
        'pour'            => false,
        'css_save'        => true,
        'css_delete'      => true,
        'css_edit'        => false,
        'css_skin_change' => true,
    ));

    switch (strtolower($step)) {
        case '':
            css_edit();
            break;
        case 'pour':
            css_edit();
            break;
        case 'css_save':
            css_save();
            break;
        case 'css_delete':
            css_delete();
            break;
        case 'css_edit':
            css_edit();
            break;
        case "css_skin_change":
            css_skin_change();
            css_edit();
            break;
    }
}

/**
 * Renders a list of stylesheets.
 *
 * @param  string $current The active stylesheet
 * @param  string $skin    The active skin name
 * @return string HTML
 */

function css_list($current, $skin)
{
    $out = array();
    $protected = safe_column('DISTINCT css', 'txp_section', '1=1');

    $criteria = "skin = '" . doSlash($skin) . "'";
    $criteria .= callback_event('admin_criteria', 'css_list', 0, $criteria);

    $rs = safe_rows_start('name', 'txp_css', $criteria);

    if ($rs) {
        while ($a = nextRow($rs)) {
            extract($a);
            $active = ($current === $name);

            if ($active) {
                $edit = txpspecialchars($name);
            } else {
                $edit = eLink('css', '', 'name', $name, $name);
            }

            if (!array_key_exists($name, $protected)) {
                $edit .= dLink('css', 'css_delete', 'name', $name);
            }

            $out[] = tag(n.$edit.n, 'li', array(
                'class' => $active ? 'active' : '',
            ));
        }

        $out = tag(join(n, $out), 'ul', array(
            'class' => 'switcher-list',
        ));

        return wrapGroup('all_styles', $out, 'all_stylesheets');
    }
}

/**
 * The main stylesheet editor panel.
 *
 * @param string|array $message The activity message
 */

function css_edit($message = '')
{
    global $event, $step;

    pagetop(gTxt('edit_css'), $message);

    $default_name = safe_field('css', 'txp_section', "name = 'default'");

    extract(array_map('assert_string', gpsa(array(
        'copy',
        'save_error',
        'savenew',
    ))));

    $name = sanitizeForPage(assert_string(gps('name')));
    $newname = sanitizeForPage(assert_string(gps('newname')));

    // Use master skin as first fallback.
    $skin = get_pref('skin_editing', get_pref('skin_master', 'default'), true);

    if ($step == 'css_delete' || empty($name) && $step != 'pour' && !$savenew) {
        $name = $default_name;
    } elseif (((($copy || $savenew) && $newname) || ($newname && ($newname != $name))) && !$save_error) {
        $name = $newname;
    }

    $buttons = n.tag(gTxt('css_name'), 'label', array('for' => 'new_style')).
        br.fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_style', false, true);

    if ($name) {
        $buttons .= n.span(
            href(gTxt('duplicate'), '#', array(
                'id'    => 'txp_clone',
                'class' => 'clone',
                'title' => gTxt('css_clone'),
            )), array('class' => 'txp-actions'));
    } else {
        $buttons .= hInput('savenew', 'savenew');
    }

    $thecss = gps('css');

    if (!$save_error) {
        $thecss = safe_field('css', 'txp_css', "name='".doSlash($name)."' AND skin='" . doSlash($skin) . "'");
    }

    $skin_list = get_skin_list();

    echo hed(gTxt('tab_style'), 1, array('class' => 'txp-heading'));
    echo n.tag(
        n.tag(
            form(
                graf($buttons).
                graf(
                    tag(gTxt('css_code'), 'label', array('for' => 'css')).
                    br.'<textarea class="code" id="css" name="css" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($thecss).'</textarea>'
                ).
                graf(
                    fInput('submit', '', gTxt('save'), 'publish').
                    eInput('css').sInput('css_save').
                    hInput('name', $name).
                    hInput('skin', $skin)
                ), '', '', 'post', 'edit-form', '', 'style_form').n, 'div', array(
            'id'    => 'main_content',
            'class' => 'txp-layout-cell txp-layout-3-4',
        )).

        n.tag(
            graf(sLink('css', 'pour', gTxt('create_new_css')), array('class' => 'action-create')).
            ((count($skin_list) > 0)
            ? form(
                inputLabel('skin', selectInput('skin', $skin_list, $skin, false, 1, 'skin'), 'skin').
                eInput('css').
                sInput('css_skin_change')
                )
            : ''
            ).
            css_list($name, $skin).n, 'div', array(
            'id'    => 'content_switcher',
            'class' => 'txp-layout-cell txp-layout-1-4',
        )).n, 'div', array(
        'id'    => $event.'_container',
        'class' => 'txp-layout-grid',
    ));
}

/**
 * Saves or clones a stylesheet.
 */

function css_save()
{
    extract(doSlash(array_map('assert_string', psa(array(
        'savenew',
        'copy',
        'css',
        'skin',
    )))));

    $name = sanitizeForPage(assert_string(ps('name')));
    $newname = sanitizeForPage(assert_string(ps('newname')));

    css_set_skin($skin);

    $save_error = false;
    $message = '';

    if (!$newname) {
        $message = array(gTxt('css_name_required'), E_ERROR);
        $save_error = true;
    } else {
        if ($copy && ($name === $newname)) {
            $newname .= '_copy';
            $_POST['newname'] = $newname;
        }

        $safe_skin = doSlash($skin);
        $safe_name = doSlash($name);
        $safe_newname = doSlash($newname);

        $exists = safe_field('name', 'txp_css', "name = '$safe_newname' AND skin = '$safe_skin'");

        if (($newname !== $name) && $exists) {
            $message = array(gTxt('css_already_exists', array('{name}' => $newname)), E_ERROR);
            if ($savenew) {
                $_POST['newname'] = '';
            }

            $save_error = true;
        } else {
            if ($savenew or $copy) {
                if ($newname) {
                    if (safe_insert('txp_css', "name = '$safe_newname', css = '$css', skin = '$safe_skin'")) {
                        update_lastmod();
                        $message = gTxt('css_created', array('{name}' => $newname));
                    } else {
                        $message = array(gTxt('css_save_failed'), E_ERROR);
                        $save_error = true;
                    }
                } else {
                    $message = array(gTxt('css_name_required'), E_ERROR);
                    $save_error = true;
                }
            } else {
                if (safe_update('txp_css',
                        "css = '$css', name = '$safe_newname', skin = '$safe_skin'",
                        "name = '$safe_name' AND skin = '$safe_skin'")) {
                    safe_update('txp_section', "css = '$safe_newname'", "css='$safe_name'");
                    update_lastmod();
                    $message = gTxt('css_updated', array('{name}' => $name));
                } else {
                    $message = array(gTxt('css_save_failed'), E_ERROR);
                    $save_error = true;
                }
            }
        }
    }

    if ($save_error === true) {
        $_POST['save_error'] = '1';
    } else {
        callback_event('css_saved', '', 0, $name, $newname);
    }

    css_edit($message);
}

/**
 * Deletes a stylesheet.
 */

function css_delete()
{
    $name  = ps('name');
    $skin = get_pref('skin_editing', get_pref('skin_master', 'default'));
    $count = safe_count('txp_section', "css = '".doSlash($name)."'");
    $message = '';

    if ($count) {
        $message = array(gTxt('css_used_by_section', array('{name}' => $name, '{count}' => $count)), E_ERROR);
    } else {
        if (safe_delete('txp_css', "name = '".doSlash($name)."' AND skin='".doSlash($skin)."'")) {
            callback_event('css_deleted', '', 0, compact('name', 'skin'));
            $message = gTxt('css_deleted', array('{name}' => $name));
        }
    }
    css_edit($message);
}

/**
 * Changes the skin in which styles are being edited.
 *
 * Keeps track of which skin is being edited from panel to panel.
 *
 * @param  string $skin Optional skin name. Read from GET/POST otherwise
 */

function css_skin_change($skin = null)
{
    if ($skin === null) {
        $skin = gps('skin');
    }

    css_set_skin($skin);

    return true;
}

/**
 * Set the current skin so it persists across panels.
 *
 * @param  string $skin The skin name to store
 * @todo   Generalise this elsewhere?
 * @return string HTML
 */

function css_set_skin($skin)
{
    set_pref('skin_editing', $skin, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
}

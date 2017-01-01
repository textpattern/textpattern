<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2017 The Textpattern Development Team
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
        'pour'       => false,
        'css_save'   => true,
        'css_delete' => true,
        'css_edit'   => false,
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
    }
}

/**
 * Renders a list of stylesheets.
 *
 * @param  array $current Current record set of the edited sheet
 * @return string HTML
 */

function css_list($current)
{
    $out = array();
    $protected = safe_column("DISTINCT css", 'txp_section', "1 = 1");

    $criteria = 1;
    $criteria .= callback_event('admin_criteria', 'css_list', 0, $criteria);

    $rs = safe_rows_start("name", 'txp_css', $criteria . ' ORDER BY name');

    if ($rs) {
        while ($a = nextRow($rs)) {
            extract($a);
            $active = ($current['name'] === $name);

            $edit = eLink('css', '', 'name', $name, $name);

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
 * @param string|array $message          The activity message
 * @param bool         $refresh_partials Whether to refresh partial contents
 */

function css_edit($message = '', $refresh_partials = false)
{
    global $event, $step;

    /*
    $partials is an array of:
    $key => array (
        'mode' => {PARTIAL_STATIC | PARTIAL_VOLATILE | PARTIAL_VOLATILE_VALUE},
        'selector' => $DOM_selector or array($selector, $fragment) of $DOM_selectors,
         'cb' => $callback_function,
         'html' => $return_value_of_callback_function (need not be intialised here)
    )
    */
    $partials = array(
        // Stylesheet list.
        'list' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#all_styles',
            'cb'       => 'css_list',
        ),
        // Name field.
        'name' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'div.name',
            'cb'       => 'css_partial_name',
        ),
        // Name value.
        'name_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#new_style,input[name=name]',
            'cb'       => 'css_partial_name_value',
        ),
        // Textarea.
        'css' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.css',
            'cb'       => 'css_partial_css',
        ),
    );

    extract(array_map('assert_string', gpsa(array(
        'copy',
        'save_error',
        'savenew',
    ))));

    $default_name = safe_field("css", 'txp_section', "name = 'default'");

    $name = sanitizeForPage(assert_string(gps('name')));
    $newname = sanitizeForPage(assert_string(gps('newname')));
    $class = 'async';

    if ($step == 'css_delete' || empty($name) && $step != 'pour' && !$savenew) {
        $name = $default_name;
    } elseif ((($copy || $savenew) && $newname) && !$save_error) {
        $name = $newname;
    } elseif ((($newname && ($newname != $name)) || $step === 'pour') && !$save_error) {
        $name = $newname;
        $class = '';
    } elseif ($savenew && $save_error) {
        $class = '';
    }

    $thecss = gps('css');

    if (!$save_error) {
        $thecss = fetch('css', 'txp_css', 'name', $name);
    }

    $actionsExtras = '';

    if ($name) {
        $actionsExtras .= href('<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'), '#', array(
            'class'     => 'txp-clone',
            'data-form' => 'style_form',
        ));
    }

    $actions = graf(
        sLink('css', 'pour', '<span class="ui-icon ui-extra-icon-new-document"></span> '.gTxt('create_new_css'), 'txp-new').
        $actionsExtras,
        array('class' => 'txp-actions txp-actions-inline')
    );

    $buttons = graf(
        tag_void('input', array(
            'class'  => 'publish',
            'type'   => 'submit',
            'method' => 'post',
            'value'  =>  gTxt('save'),
        )), ' class="txp-save"'
    );

    $rs = array(
        'name'    => $name,
        'newname' => $newname,
        'default' => $default_name,
        'css'     => $thecss,
        );

    // Get content for volatile partials.
    $partials = updatePartials($partials, $rs, array(PARTIAL_VOLATILE, PARTIAL_VOLATILE_VALUE));

    if ($refresh_partials) {
        $response[] = announce($message);
        $response = array_merge($response, updateVolatilePartials($partials));
        send_script_response(join(";\n", $response));

        // Bail out.
        return;
    }

    // Get content for static partials.
    $partials = updatePartials($partials, $rs, PARTIAL_STATIC);

    pagetop(gTxt('edit_css'), $message);

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_style'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        );

    // Styles create/switcher column.
    echo n.tag(
        $partials['list']['html'].n,
        'div', array(
            'class' => 'txp-layout-4col-alt',
            'id'    => 'content_switcher',
            'role'  => 'region',
        )
    );

    // Styles code columm.
    echo n.tag(
        form(
            $actions.
            $partials['name']['html'].
            $partials['css']['html'].
            $buttons, '', '', 'post', $class, '', 'style_form'),
        'div', array(
            'class' => 'txp-layout-4col-3span',
            'id'    => 'main_content',
            'role'  => 'region',
        )
    );

    echo n.'</div>'; // End of .txp-layout.
}

/**
 * Saves or clones a stylesheet.
 */

function css_save()
{
    global $app_mode;

    extract(doSlash(array_map('assert_string', psa(array(
        'savenew',
        'copy',
        'css',
    )))));

    $name = sanitizeForPage(assert_string(ps('name')));
    $newname = sanitizeForPage(assert_string(ps('newname')));

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

        $exists = safe_field("name", 'txp_css', "name = '".doSlash($newname)."'");

        if (($newname !== $name) && $exists) {
            $message = array(gTxt('css_already_exists', array('{name}' => $newname)), E_ERROR);

            if ($savenew) {
                $_POST['newname'] = '';
            }

            $save_error = true;
        } else {
            if ($savenew or $copy) {
                if ($newname) {
                    if (safe_insert('txp_css', "name = '".doSlash($newname)."', css = '$css'")) {
                        update_lastmod('css_created', compact('newname', 'name', 'css'));
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
                if (safe_update('txp_css', "css = '$css', name = '".doSlash($newname)."'", "name = '".doSlash($name)."'")) {
                    safe_update('txp_section', "css = '".doSlash($newname)."'", "css='".doSlash($name)."'");
                    update_lastmod('css_saved', compact('newname', 'name', 'css'));
                    $message = gTxt('css_updated', array('{name}' => $newname));
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

    css_edit($message, ($app_mode === 'async') ? true : false);
}

/**
 * Deletes a stylesheet.
 */

function css_delete()
{
    $name = ps('name');
    $count = safe_count('txp_section', "css = '".doSlash($name)."'");
    $message = '';

    if ($count) {
        $message = array(gTxt('css_used_by_section', array('{name}' => $name, '{count}' => $count)), E_ERROR);
    } else {
        if (safe_delete('txp_css', "name = '".doSlash($name)."'")) {
            callback_event('css_deleted', '', 0, $name);
            $message = gTxt('css_deleted', array('{name}' => $name));
        }
    }
    css_edit($message);
}

/**
 * Renders css name field.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function css_partial_name($rs)
{
    $name = $rs['name'];
    $newname = $rs['newname'];

    $titleblock = inputLabel(
        'new_style',
        fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_style', false, true),
        'css_name',
        array('', 'instructions_style_name'),
        array('class' => 'txp-form-field name')
    );

    if ($name === '') {
        $titleblock .= hInput('savenew', 'savenew');
    } else {
        $titleblock .= hInput('name', $name);
    }

    $titleblock .= eInput('css').sInput('css_save');

    return $titleblock;
}

/**
 * Renders css name field.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function css_partial_name_value($rs)
{
    return $rs['name'];
}

/**
 * Renders css textarea field.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function css_partial_css($rs)
{
    $out = inputLabel(
        'css',
        '<textarea class="code" id="css" name="css" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($rs['css']).'</textarea>',
        'css_code',
        array('', 'instructions_style_code'),
        array('class' => 'txp-form-field css')
    );

    return $out;
}

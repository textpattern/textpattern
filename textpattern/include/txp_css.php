<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Styles panel.
 *
 * @package Admin\CSS
 */

use Textpattern\Skin\Skin;
use Textpattern\Skin\Css;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'css') {
    require_privs('css');

    $instance = Txp::get('Textpattern\Skin\Css');

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
            Txp::get('Textpattern\Skin\Css')->selectEdit();
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
    $safe_skin = doSlash($current['skin']);
    $protected = safe_column("DISTINCT css", 'txp_section', "skin = '$safe_skin' OR dev_skin = '$safe_skin'");

    $criteria = "skin = '$safe_skin'";
    $criteria .= callback_event('admin_criteria', 'css_list', 0, $criteria);

    $rs = safe_rows_start("name", 'txp_css', $criteria . ' ORDER BY name');

    while ($a = nextRow($rs)) {
        extract($a);

        $active = ($current['name'] === $name);
        $edit = eLink('css', '', 'name', $name, $name);

        if (!array_key_exists($name, $protected)) {
            $edit .= dLink('css', 'css_delete', 'name', $name);
        }

        $out[] = tag(n.$edit.n, 'li', array('class' => $active ? 'active' : ''));
    }

    $list = wrapGroup('all_styles_css', tag(join(n, $out), 'ul', array('class' => 'switcher-list')), gTxt('all_stylesheets'));

    return n.tag($list, 'div', array(
            'id'    => 'all_styles',
            'role'  => 'region',
        )
    );
}

/**
 * The main stylesheet editor panel.
 *
 * @param string|array $message          The activity message
 * @param bool         $refresh_partials Whether to refresh partial contents
 */

function css_edit($message = '', $refresh_partials = false)
{
    global $instance, $event, $step;

    /*
    $partials is an array of:
    $key => array (
        'mode' => {PARTIAL_STATIC | PARTIAL_VOLATILE | PARTIAL_VOLATILE_VALUE},
        'selector' => $DOM_selector or array($selector, $fragment) of $DOM_selectors,
         'cb' => $callback_function,
         'html' => $return_value_of_callback_function (need not be initialised here)
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
            'selector' => '#new_style,#main_content input[name=name]',
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
        'skin',
    ))));

    $default_name = safe_field("css", 'txp_section', "name = 'default'");

    $name = assert_string(gps('name'));
    $newname = Css::sanitize(assert_string(gps('newname')));
    $skin = ($skin !== '') ? $skin : null;
    $class = 'async';

    $thisSkin = Txp::get('Textpattern\Skin\Skin');
    $skin = $thisSkin->setName($skin)->setEditing();

    if ($step == 'css_delete' || empty($name) && $step != 'pour' && !$savenew) {
        $name = get_pref('last_css_saved', $default_name);
    } elseif ((($copy || $savenew) && $newname) && !$save_error) {
        $name = $newname;
    } elseif ((($newname && ($newname != $name)) || $step === 'pour') && !$save_error) {
        $name = $newname;
        $class = '';
    } elseif ($savenew && $save_error) {
        $class = '';
    }

    if (!$save_error) {
        $thecss = safe_field('css', 'txp_css', "name='".doSlash($name)."' AND skin='" . doSlash($skin) . "'");
    } else {
        $thecss = gps('css');
    }

    $actionsExtras = '';

    if ($name) {
        $actionsExtras .= sLink('css', 'pour', '<span class="ui-icon ui-extra-icon-new-document"></span> '.gTxt('create_css'), 'txp-new')
        .href('<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'), '#', array(
            'class'     => 'txp-clone',
            'data-form' => 'style_form',
        ));
    }

    $actions = graf(
        $actionsExtras,
        array('class' => 'txp-actions txp-actions-inline')
    );

    $skinBlock = n.$instance->setSkin($thisSkin)->getSelectEdit();

    $buttons = graf(
        (!is_writable($instance->getDirPath()) ? '' :
            span(
                checkbox2('export', gps('export'), 0, 'export').
                n.tag(gTxt('export_to_disk'), 'label', array('for' => 'export'))
            , array('class' => 'txp-save-export'))
        ).n.
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
        'skin'    => $skin,
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

    pagetop(gTxt('tab_style'), $message);

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_style'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        );

    // Styles create/switcher column.
    echo n.tag(
        $skinBlock.$partials['list']['html'].n,
        'div', array(
            'class' => 'txp-layout-4col-alt',
            'id'    => 'content_switcher',
            'role'  => 'region',
        )
    );

    // Styles code column.
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
    global $instance, $app_mode;

    extract(doSlash(array_map('assert_string', psa(array(
        'savenew',
        'copy',
        'css',
        'skin',
    )))));

    $passedName = assert_string(ps('name'));
    $name = Css::sanitize($passedName);
    $newname = Css::sanitize(assert_string(ps('newname')));

    $skin = Txp::get('Textpattern\Skin\Skin')->setName($skin)->setEditing();

    $save_error = false;
    $message = '';

    if (!$newname) {
        $message = array(gTxt('css_name_required'), E_ERROR);
        $save_error = true;
    } else {
        if ($copy && ($name === $newname)) {
            $newname .= '_copy';
            $passedName = $name;
            $_POST['newname'] = $newname;
        }

        $safe_skin = doSlash($skin);
        $safe_name = doSlash($passedName);
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
                        set_pref('last_css_saved', $newname, 'css', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
                        update_lastmod('css_created', compact('newname', 'name', 'css'));

                        $message = gTxt('css_created', array('{list}' => $newname));

                        // If css name has been auto-sanitized, throw a warning.
                        if ($passedName !== $name) {
                            $message = array($message, E_WARNING);
                        }

                        callback_event($copy ? 'css_duplicated' : 'css_created', '', 0, $name, $newname);
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
                    safe_update('txp_section', "css = '$safe_newname'", "css='$safe_name' AND skin='$safe_skin'");
                    safe_update('txp_section', "dev_css = '$safe_newname'", "dev_css='$safe_name' AND dev_skin='$safe_skin'");
                    set_pref('last_css_saved', $newname, 'css', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
                    update_lastmod('css_saved', compact('newname', 'name', 'css'));

                    $message = gTxt('css_updated', array('{list}' => $newname));

                    // If css name has been auto-sanitized, throw a warning.
                    if ($passedName !== $name) {
                        $message = array($message, E_WARNING);
                    }

                    callback_event('css_updated', '', 0, $name, $newname);
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
        if (gps('export')) {
            $instance->setNames(array($newname))->export()->getMessage();
        }

        callback_event('css_saved', '', 0, $name, $newname);
    }

    css_edit($message, ($app_mode === 'async') ? true : false);
}

/**
 * Deletes a stylesheet.
 */

function css_delete()
{
    global $prefs;

    $name = ps('name');
    $safe_name = doSlash($name);
    $skin = get_pref('skin_editing', 'default');
    $safe_skin = doSlash($skin);

    $count = safe_count('txp_section', "css = '$safe_name' AND (skin='$safe_skin' OR dev_skin='$safe_skin')");
    $message = '';

    if ($count) {
        $message = array(gTxt('css_used_by_section', array('{name}' => $name, '{count}' => $count)), E_ERROR);
    } else {
        if (safe_delete('txp_css', "name = '$safe_name' AND skin='$safe_skin'")) {
            callback_event('css_deleted', '', 0, compact('name', 'skin'));
            $message = gTxt('css_deleted', array('{list}' => $name));
            if ($name === get_pref('last_css_saved')) {
                unset($prefs['last_css_saved']);
                remove_pref('last_css_saved', 'css');
            }
        }
    }

    css_edit($message);
}

/**
 * Changes the skin in which styles are being edited.
 *
 * Keeps track of which skin is being edited from panel to panel.
 *
 * @param      string $skin Optional skin name. Read from GET/POST otherwise
 * @deprecated in 4.7.0
 */

function css_skin_change($skin = null)
{
    Txp::get('Textpattern\Skin\Css')->selectEdit($skin);

    return true;
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
    $skin = $rs['skin'];
    $nameRegex = '^(?=[^.\s])[^\x00-\x1f\x22\x26\x27\x2a\x2f\x3a\x3c\x3e\x3f\x5c\x7c\x7f]+';

    $titleblock = inputLabel(
        'new_style',
        fInput('text', array('name' => 'newname', 'pattern' => $nameRegex), $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_style', false, true),
        'css_name',
        array('', 'instructions_style_name'),
        array('class' => 'txp-form-field name')
    );

    if ($name === '') {
        $titleblock .= hInput('savenew', 'savenew');
    } else {
        $titleblock .= hInput('name', $name);
    }

    $titleblock .= hInput('skin', $skin).
        eInput('css').sInput('css_save');

    return $titleblock;
}

/**
 * Renders css name value.
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

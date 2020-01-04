<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2020 The Textpattern Development Team
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
 * Forms panel.
 *
 * @package Admin\Form
 */

use Textpattern\Skin\Skin;
use Textpattern\Skin\Form;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'form') {
    require_privs('form');

    $instance = Txp::get('Textpattern\Skin\Form');

    /**
     * List of essential forms.
     *
     * @global array $essential_forms
     */

    $essential_forms = $instance->getEssential('name');

    /**
     * List of form types.
     *
     * @global array $form_types
     */

    $form_types = array();

    foreach ($instance->getTypes() as $type) {
        $form_types[$type] = gTxt($type);
    }

    bouncer($step, array(
        'form_edit'        => false,
        'form_create'      => false,
        'form_delete'      => true,
        'form_multi_edit'  => true,
        'form_save'        => true,
        'form_skin_change' => true,
        'tagbuild'         => false,
    ));

    switch (strtolower($step)) {
        case '':
            form_edit();
            break;
        case 'form_edit':
            form_edit();
            break;
        case 'form_create':
            form_create();
            break;
        case 'form_delete':
            form_delete();
            break;
        case 'form_multi_edit':
            form_multi_edit();
            break;
        case 'form_save':
            form_save();
            break;
        case "form_skin_change":
            $instance->selectEdit();
            form_edit();
            break;
        case 'tagbuild':
            echo form_tagbuild();
            break;
    }
}

/**
 * Renders a list of form templates.
 *
 * This function returns a list of form templates, wrapped in a multi-edit
 * form widget.
 *
 * @param  array  $current The selected form info
 * @return string HTML
 */

function form_list($current)
{
    global $essential_forms, $form_types;

    $criteria = "skin = '" . doSlash($current['skin']) . "'";
    $criteria .= callback_event('admin_criteria', 'form_list', 0, $criteria);

    $rs = safe_rows_start(
        "name, type",
        'txp_form',
        "$criteria ORDER BY FIELD(type, ".join(',', quote_list(array_keys($form_types))).") ASC, name ASC"
    );

    if ($rs) {
        $prev_type = null;

        // Add a hidden field, in case only one skin is in use and multi-edit is the
        // first action performed. This way, the value is propagated and saved, even
        // if the skin select list is not rendered or a Form is not saved first.
        $out[] = hInput('skin', $current['skin']);

        while ($a = nextRow($rs)) {
            extract($a);
            $active = ($current['name'] === $name);

            if ($prev_type !== $type) {
                if ($prev_type !== null) {
                    $group_out = tag(n.join(n, $group_out).n, 'ul', array('class' => 'switcher-list'));

                    $label = isset($form_types[$prev_type]) ? $form_types[$prev_type] : $prev_type;
                    $out[] = wrapRegion($prev_type.'_forms_group', $group_out, 'form_'.$prev_type, $label, 'form_'.$prev_type);
                }

                $prev_type = $type;
                $group_out = array();
            }

            $editlink = eLink('form', 'form_edit', 'name', $name, $name);

            if (!in_array($name, $essential_forms)) {
                $modbox = span(
                    checkbox('selected_forms[]', txpspecialchars($name), false), array('class' => 'switcher-action'));
            } else {
                $modbox = '';
            }

            $group_out[] = tag(n.$modbox.$editlink.n, 'li', array('class' => $active ? 'active' : ''));
        }

        if ($prev_type !== null) {
            $group_out = tag(n.join(n, $group_out).n, 'ul', array('class' => 'switcher-list'));

            $out[] = wrapRegion($prev_type.'_forms_group', $group_out, 'form_'.$prev_type, $form_types[$prev_type], 'form_'.$prev_type);
        }

        $out = tag(implode('', $out), 'div', array(
            'id'   => 'allforms_form_sections',
            'role' => 'region',
        ));

        $methods = array(
            'changetype' => array(
                'label' => gTxt('changetype'),
                'html' => formTypes('', false, 'changetype'),
            ),
            'delete'     => gTxt('delete'),
        );

        $out .= multi_edit($methods, 'form', 'form_multi_edit');

        return form($out, '', '', 'post', '', '', 'allforms_form');
    }
}

/**
 * Processes multi-edit actions.
 */

function form_multi_edit()
{
    $method = ps('edit_method');
    $forms = ps('selected_forms');
    $skin = ps('skin');
    $affected = array();
    $message = '';

    $skin = Txp::get('Textpattern\Skin\Skin')->setName($skin)->setEditing();

    if ($forms && is_array($forms)) {
        if ($method == 'delete') {
            foreach ($forms as $name) {
                if (form_delete($name, $skin)) {
                    $affected[] = $name;
                }
            }

            callback_event('forms_deleted', '', 0, compact('affected', 'skin'));
            update_lastmod('form_deleted', $affected);

            $message = gTxt('form_deleted', array('{list}' => join(', ', $affected)));
        }

        if ($method == 'changetype') {
            $new_type = ps('type');

            foreach ($forms as $name) {
                if (form_set_type($name, $new_type)) {
                    $affected[] = $name;
                }
            }

            $message = gTxt('form_updated', array('{list}' => join(', ', $affected)));
        }
    }

    form_edit($message);
}

/**
 * Creates a new form.
 *
 * Directs requests back to the main editor panel, armed with a
 * 'form_create' step.
 */

function form_create()
{
    form_edit();
}

/**
 * Renders the main Form editor panel.
 *
 * @param string|array $message          The activity message
 * @param bool         $refresh_partials Whether to refresh partial contents
 */

function form_edit($message = '', $refresh_partials = false)
{
    global $instance, $event, $step;

    /*
    $partials is an array of:
    $key => array (
        'mode' => {PARTIAL_STATIC | PARTIAL_VOLATILE | PARTIAL_VOLATILE_VALUE},
        'selector' => $DOM_selector or array($selector, $fragment, $script) of $DOM_selectors,
         'cb' => $callback_function,
         'html' => $return_value_of_callback_function (need not be initialised here)
    )
    */
    $partials = array(
        // Form list.
        'list' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#allforms_form_sections',
            'cb'       => 'form_list',
        ),
        // Name field.
        'name' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'div.name',
            'cb'       => 'form_partial_name',
        ),
        // Name value.
        'name_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#new_form,input[name=name]',
            'cb'       => 'form_partial_name_value',
        ),
        // Type field.
        'type' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '.type',
            'cb'       => 'form_partial_type',
        ),
        // Type value.
        'type_value' => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '[name=type]',
            'cb'       => 'form_partial_type_value',
        ),
        // Textarea.
        'template' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.template',
            'cb'       => 'form_partial_template',
        ),
    );

    extract(array_map('assert_string', gpsa(array(
        'copy',
        'save_error',
        'savenew',
        'skin',
    ))));

    $name = assert_string(gps('name'));
    $type = assert_string(gps('type'));
    $newname = Form::sanitize(assert_string(gps('newname')));
    $skin = ($skin !== '') ? $skin : null;
    $class = 'async';

    $thisSkin = Txp::get('Textpattern\Skin\Skin');
    $skin = $thisSkin->setName($skin)->setEditing();

    if ($step == 'form_delete' || empty($name) && $step != 'form_create' && !$savenew) {
        $name = get_pref('last_form_saved', 'default');
    } elseif ((($copy || $savenew) && $newname) && !$save_error) {
        $name = $newname;
    } elseif ((($newname && ($newname != $name)) || $step === 'form_create') && !$save_error) {
        $name = $newname;
        $class = '';
    } elseif ($savenew && $save_error) {
        $class = '';
    }

    $Form = gps('Form');

    if (!$save_error) {
        if (!extract(safe_row('*', 'txp_form', "name = '".doSlash($name)."' AND skin = '" . doSlash($skin) . "'"))) {
            $name = '';
        }
    }

    $actionsExtras = '';

    if ($name) {
        $actionsExtras .= sLink('form', 'form_create', '<span class="ui-icon ui-extra-icon-new-document"></span> '.gTxt('create_form'), 'txp-new')
        .href('<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'), '#', array(
            'class'     => 'txp-clone',
            'data-form' => 'form_form',
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
        ).
        n.tag_void('input', array(
            'class'  => 'publish',
            'type'   => 'submit',
            'method' => 'post',
            'value'  =>  gTxt('save'),
        )), ' class="txp-save"'
    );

    $listActions = graf(
        href('<span class="ui-icon ui-icon-arrowthickstop-1-s"></span> '.gTxt('expand_all'), '#', array(
            'class'         => 'txp-expand-all',
            'aria-controls' => 'allforms_form',
        )).
        href('<span class="ui-icon ui-icon-arrowthickstop-1-n"></span> '.gTxt('collapse_all'), '#', array(
            'class'         => 'txp-collapse-all',
            'aria-controls' => 'allforms_form',
        )), array('class' => 'txp-actions')
    );

    $rs = array(
        'name'    => $name,
        'newname' => $newname,
        'type'    => $type,
        'skin'    => $skin,
        'form'    => $Form,
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

    pagetop(gTxt('tab_forms'), $message);

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_forms').popHelp('forms_overview'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        );

    // Forms create/switcher column.
    echo n.tag(
        $skinBlock.$listActions.n.
        $partials['list']['html'].n,
        'div', array(
            'class' => 'txp-layout-4col-alt',
            'id'    => 'content_switcher',
            'role'  => 'region',
        )
    );

    // Forms code column.
    echo n.tag(
        form(
            $actions.
            $partials['name']['html'].
            $partials['type']['html'].
            $partials['template']['html'].
            $buttons, '', '', 'post', $class, '', 'form_form'),
        'div', array(
            'class' => 'txp-layout-4col-3span',
            'id'    => 'main_content',
            'role'  => 'region',
        )
    );

    // Tag builder dialog placeholder.
    echo n.tag(
        '&nbsp;',
        'div', array(
            'class'      => 'txp-tagbuilder-content',
            'id'         => 'tagbuild_links',
            'aria-label' => gTxt('tagbuilder'),
            'title'      => gTxt('tagbuilder'),
        ));

    echo n.'</div>'; // End of .txp-layout.
}

/**
 * Saves a form template.
 */

function form_save()
{
    global $essential_forms, $form_types, $app_mode, $instance;

    extract(doSlash(array_map('assert_string', psa(array(
        'savenew',
        'Form',
        'type',
        'copy',
        'skin',
    )))));

    $passedName = assert_string(ps('name'));
    $name = Form::sanitize($passedName);
    $newname = Form::sanitize(assert_string(ps('newname')));

    $skin = Txp::get('Textpattern\Skin\Skin')->setName($skin)->setEditing();

    $save_error = false;
    $message = '';

    if (in_array($name, $essential_forms)) {
        $newname = $passedName = $name;
        $type = safe_field('type', 'txp_form', "name = '".doSlash($newname)."' AND skin = '".doSlash($skin)."'");
        $_POST['newname'] = $newname;
    }

    if (!$newname) {
        $message = array(gTxt('form_name_invalid'), E_ERROR);
        $save_error = true;
    } else {
        if (!isset($form_types[$type])) {
            $message = array(gTxt('form_type_missing'), E_ERROR);
            $save_error = true;
        } else {
            if ($copy && $name === $newname) {
                $newname .= '_copy';
                $passedName = $name;
                $_POST['newname'] = $newname;
            }

            $exists = safe_field("name", 'txp_form', "name = '".doSlash($newname)."' AND skin = '".doSlash($skin)."'");

            if ($newname !== $name && $exists !== false) {
                $message = array(gTxt('form_already_exists', array('{name}' => $newname)), E_ERROR);

                if ($savenew) {
                    $_POST['newname'] = '';
                }

                $save_error = true;
            } else {
                $safe_skin = doSlash($skin);

                if ($savenew or $copy) {
                    if ($newname) {
                        if (safe_insert(
                            'txp_form',
                            "Form = '$Form',
                            type = '$type',
                            skin = '$safe_skin',
                            name = '".doSlash($newname)."'"
                        )) {
                            update_lastmod('form_created', compact('newname', 'name', 'type', 'Form'));

                            $message = gTxt('form_created', array('{list}' => $newname));

                            // If form name has been auto-sanitized, throw a warning.
                            if ($passedName !== $name) {
                                $message = array($message, E_WARNING);
                            }

                            callback_event($copy ? 'form_duplicated' : 'form_created', '', 0, $name, $newname);
                        } else {
                            $message = array(gTxt('form_save_failed'), E_ERROR);
                            $save_error = true;
                        }
                    } else {
                        $message = array(gTxt('form_name_invalid'), E_ERROR);
                        $save_error = true;
                    }
                } else {
                    if (safe_update(
                        'txp_form',
                        "Form = '$Form',
                        type = '$type',
                        skin = '$safe_skin',
                        name = '".doSlash($newname)."'",
                        "name = '".doSlash($passedName)."' AND skin = '$safe_skin'"
                    )) {
                        update_lastmod('form_saved', compact('newname', 'name', 'type', 'Form'));

                        $message = gTxt('form_updated', array('{list}' => $newname));

                        // If form name has been auto-sanitized, throw a warning.
                        if ($passedName !== $name) {
                            $message = array($message, E_WARNING);
                        }

                        callback_event('form_updated', '', 0, $name, $newname);
                    } else {
                        $message = array(gTxt('form_save_failed'), E_ERROR);
                        $save_error = true;
                    }
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

        set_pref('last_form_saved', $newname, 'form', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
        callback_event('form_saved', '', 0, $name, $newname);
    }

    form_edit($message, ($app_mode === 'async') ? true : false);
}

/**
 * Deletes a form template with the given name.
 *
 * @param  string $name The form template
 * @param  string $skin The form skin in use
 * @return bool FALSE on error
 */

function form_delete($name, $skin)
{
    global $prefs, $essential_forms;

    if (in_array($name, $essential_forms)) {
        return false;
    } elseif ($name === get_pref('last_form_saved')) {
        unset($prefs['last_form_saved']);
        remove_pref('last_form_saved', 'form');
    }

    $name = doSlash($name);
    $skin = doSlash($skin);

    return safe_delete("txp_form", "name = '$name' AND skin = '$skin'");
}

/**
 * Changes the skin in which styles are being edited.
 *
 * Keeps track of which skin is being edited from panel to panel.
 *
 * @param      string $skin Optional skin name. Read from GET/POST otherwise
 * @deprecated in 4.7.0
 */

function form_skin_change($skin = null)
{
    Txp::get('Textpattern\Skin\Form')->selectEdit($skin);

    return true;
}

/**
 * Changes a form template's type.
 *
 * @param  string $name The form template
 * @param  string $type The new type
 * @return bool FALSE on error
 */

function form_set_type($name, $type)
{
    global $essential_forms, $form_types;

    if (in_array($name, $essential_forms) || !isset($form_types[$type])) {
        return false;
    }

    $name = doSlash($name);
    $type = doSlash($type);
    $skin = doSlash(get_pref('skin_editing', 'default'));

    return safe_update('txp_form', "type = '$type'", "name = '$name' AND skin = '$skin'");
}

/**
 * Renders a &lt;select&gt; input listing all form types.
 *
 * @param  string $type        The selected option
 * @param  bool   $blank_first If TRUE, the list defaults to an empty selection
 * @param  string $id          HTML id attribute value
 * @param  bool   $disabled    If TRUE renders the select disabled
 * @return string HTML
 * @access private
 */

function formTypes($type, $blank_first = true, $id = 'type', $disabled = false)
{
    global $form_types;

    return selectInput('type', $form_types, $type, $blank_first, '', $id, false, $disabled);
}

/**
 * Renders form name field.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function form_partial_name($rs)
{
    global $essential_forms, $form_types;

    $name = $rs['name'];
    $skin = $rs['skin'];
    $type = $rs['type'];
    $nameRegex = '^(?=[^.\s])[^\x00-\x1f\x22\x26\x27\x2a\x2f\x3a\x3c\x3e\x3f\x5c\x7c\x7f]+';

    if (in_array($name, $essential_forms) || $type && !isset($form_types[$type])) {
        $nameInput = fInput('text', array('name' => 'newname', 'pattern' => $nameRegex), $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_form', true);
    } else {
        $nameInput = fInput('text', array('name' => 'newname', 'pattern' => $nameRegex), $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_form', false, true);
    }

    $name_widgets = inputLabel(
        'new_form',
        $nameInput,
        'form_name',
        array('', 'instructions_form_name'),
        array('class' => 'txp-form-field name')
    );

    if ($name === '') {
        $name_widgets .= hInput('savenew', 'savenew');
    } else {
        $name_widgets .= hInput('name', $name);
    }

    $name_widgets .= hInput('skin', $skin).
        eInput('form').sInput('form_save');

    return $name_widgets;
}

/**
 * Renders form type field.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function form_partial_type($rs)
{
    global $essential_forms, $form_types;

    $name = $rs['name'];
    $type = $rs['type'];
    $type_widgets = '';

    if ($type && !isset($form_types[$type])) {
        $typeInput = tag_void('input', array(
            'id'       => 'types',
            'name'     => 'type',
            'type'     => 'text',
            'value'    => $type,
            'disabled' => true
        ));
        $type_widgets .= hInput('type', $type);
    } elseif (in_array($name, $essential_forms)) {
        $typeInput = formTypes($type, false, 'type', true);
        $type_widgets .= hInput('type', $type);
    } else {
        $typeInput = formTypes($type, false);
    }

    $type_widgets .= inputLabel(
        'type',
        $typeInput,
        'form_type',
        array('', 'instructions_form_type'),
        array('class' => 'txp-form-field type')
    );

    return $type_widgets;
}

/**
 * Renders form name value.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function form_partial_name_value($rs)
{
    return $rs['name'];
}

/**
 * Renders form type value.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function form_partial_type_value($rs)
{
    return $rs['type'];
}

/**
 * Renders form textarea field.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function form_partial_template($rs)
{
    global $event;

    $out = inputLabel(
        'form',
        '<textarea class="code" id="form" name="Form" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($rs['form']).'</textarea>',
        array(
            'form_code',
            n.span(
                (has_privs('tag')
                    ? href(
                        span(null, array('class' => 'ui-icon ui-extra-icon-code')).' '.gTxt('tagbuilder'),
                        array('event' => 'tag', 'panel' => $event),
                        array('class' => 'txp-tagbuilder-dialog')
                    )
                    : ''
                ),
                array('class' => 'txp-textarea-options')
            )
        ),
        array('', 'instructions_form_code'),
        array('class' => 'txp-form-field template'),
        array('div', 'div')
    );

    return $out;
}

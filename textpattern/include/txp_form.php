<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2016 The Textpattern Development Team
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
 * Forms panel.
 *
 * @package Admin\Form
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

/**
 * List of essential forms.
 *
 * @global array $essential_forms
 */

$essential_forms = array(
    'comments',
    'comments_display',
    'comment_form',
    'default',
    'plainlinks',
    'files',
);

/**
 * List of form types.
 *
 * @global array $form_types
 */

$form_types = array(
    'article'  => gTxt('article'),
    'misc'     => gTxt('misc'),
    'comment'  => gTxt('comment'),
    'category' => gTxt('category'),
    'file'     => gTxt('file'),
    'link'     => gTxt('link'),
    'section'  => gTxt('section'),
);

if ($event == 'form') {
    require_privs('form');

    bouncer($step, array(
        'form_edit'       => false,
        'form_create'     => false,
        'form_delete'     => true,
        'form_multi_edit' => true,
        'form_save'       => true,
        'tagbuild'        => false,
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
 * @param  string $curname The selected form
 * @return string HTML
 */

function form_list($curname)
{
    global $essential_forms, $form_types;

    $criteria = 1;
    $criteria .= callback_event('admin_criteria', 'form_list', 0, $criteria);

    $rs = safe_rows_start(
        "name, type",
        'txp_form',
        "$criteria ORDER BY FIELD(type, ".join(',', quote_list(array_keys($form_types))).") ASC, name ASC"
    );

    if ($rs) {
        $prev_type = null;
        $group_out = array();

        while ($a = nextRow($rs)) {
            extract($a);
            $active = ($curname === $name);

            if ($prev_type !== $type) {
                if ($prev_type !== null) {
                    $group_out = tag(n.join(n, $group_out).n, 'ul', array(
                        'class' => 'switcher-list',
                    ));

                    $out[] = wrapRegion($prev_type.'_forms_group', $group_out, 'form_'.$prev_type, $form_types[$prev_type], 'form_'.$prev_type);
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

            $group_out[] = tag(n.$modbox.$editlink.n, 'li', array(
                'class' => $active ? 'active' : '',
            ));
        }

        if ($prev_type !== null) {
            $group_out = tag(n.join(n, $group_out).n, 'ul', array(
                'class' => 'switcher-list',
            ));

            $out[] = wrapRegion($prev_type.'_forms_group', $group_out, 'form_'.$prev_type, $form_types[$prev_type], 'form_'.$prev_type);
        }

        $methods = array(
            'changetype' => array('label' => gTxt('changetype'), 'html' => formTypes('', false, 'changetype')),
            'delete'     => gTxt('delete'),
        );

        $out[] = multi_edit($methods, 'form', 'form_multi_edit');

        return form(join('', $out), '', '', 'post', '', '', 'allforms_form');
    }
}

/**
 * Processes multi-edit actions.
 */

function form_multi_edit()
{
    $method = ps('edit_method');
    $forms = ps('selected_forms');
    $affected = array();

    if ($forms && is_array($forms)) {
        if ($method == 'delete') {
            foreach ($forms as $name) {
                if (form_delete($name)) {
                    $affected[] = $name;
                }
            }

            callback_event('forms_deleted', '', 0, $affected);

            $message = gTxt('forms_deleted', array('{list}' => join(', ', $affected)));

            form_edit($message);
        }

        if ($method == 'changetype') {
            $new_type = ps('type');

            foreach ($forms as $name) {
                if (form_set_type($name, $new_type)) {
                    $affected[] = $name;
                }
            }

            $message = gTxt('forms_updated', array('{list}' => join(', ', $affected)));

            form_edit($message);
        }
    } else {
        form_edit();
    }
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
 * @param string|array $message The activity message
 */

function form_edit($message = '')
{
    global $event, $step, $essential_forms;

    pagetop(gTxt('edit_forms'), $message);

    extract(array_map('assert_string', gpsa(array(
        'copy',
        'save_error',
        'savenew',
    ))));

    $name = sanitizeForPage(assert_string(gps('name')));
    $type = assert_string(gps('type'));
    $newname = sanitizeForPage(assert_string(gps('newname')));

    if ($step == 'form_delete' || empty($name) && $step != 'form_create' && !$savenew) {
        $name = 'default';
    } elseif (((($copy || $savenew) && $newname) || ($newname && $newname !== $name)) && !$save_error) {
        $name = $newname;
    }

    $Form = gps('Form');

    if (!$save_error) {
        $rs = safe_row("*", 'txp_form', "name = '".doSlash($name)."'");
        extract($rs);
    }

    if (in_array($name, $essential_forms)) {
        $name_widgets = inputLabel(
            'new_form',
            fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_form', true),
            'form_name',
            array('', 'instructions_form_name'),
            array('class' => 'txp-form-field name')
        );

        $type_widgets = inputLabel(
            'type',
            formTypes($type, false, 'type', true),
            'form_type',
            array('', 'instructions_form_type'),
            array('class' => 'txp-form-field type')
        );
    } else {
        $name_widgets = inputLabel(
            'new_form',
            fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_form', false, true),
            'form_name',
            array('', 'instructions_form_name'),
            array('class' => 'txp-form-field name')
        );

        $type_widgets = inputLabel(
            'type',
            formTypes($type, false),
            'form_type',
            array('', 'instructions_form_type'),
            array('class' => 'txp-form-field type')
        );
    }

    if ($name === '') {
        $name_widgets .= hInput('savenew', 'savenew');
    } else {
        $name_widgets .= hInput('name', $name);
    }

    $name_widgets .= eInput('form').sInput('form_save');

    $actionsExtras = '';

    if ($name) {
        $actionsExtras .= href('<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'), '#', array(
            'class'     => 'txp-clone',
            'data-form' => 'form_form',
        ));
    }

    $actions = graf(
        sLink('form', 'form_create', '<span class="ui-icon ui-extra-icon-new-document"></span> '.gTxt('create_new_form'), 'txp-new').
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

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_forms').popHelp('forms_overview'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        );

    // Forms create/switcher column.
    echo n.tag(
        $listActions.n.
        form_list($name).n,
        'div', array(
            'class' => 'txp-layout-4col-alt',
            'id'    => 'content_switcher',
            'role'  => 'region',
        )
    );

    // Forms code columm.
    echo n.tag(
        form(
            $actions.
            $name_widgets.
            $type_widgets.
            inputLabel(
                'form',
                '<textarea class="code" id="form" name="Form" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($Form).'</textarea>',
                array(
                    'form_code',
                    n.href('<span class="ui-icon ui-extra-icon-code"></span> '.gTxt('tagbuilder'), '#', array('class' => 'txp-tagbuilder-dialog')),
                ),
                array('', 'instructions_form_code'),
                array('class' => 'txp-form-field'),
                array('div', 'div')
            ).
            $buttons
            , '', '', 'post', '', '', 'form_form'),
        'div', array(
            'class' => 'txp-layout-4col-3span',
            'id'    => 'main_content',
            'role'  => 'region',
        )
    );

    // Tag builder dialog.
    echo n.tag(
        form_tagbuild(),
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
    global $essential_forms, $form_types;

    extract(doSlash(array_map('assert_string', psa(array(
        'savenew',
        'Form',
        'type',
        'copy',
    )))));

    $name = sanitizeForPage(assert_string(ps('name')));
    $newname = sanitizeForPage(assert_string(ps('newname')));

    $save_error = false;
    $message = '';

    if (in_array($name, $essential_forms)) {
        $newname = $name;
        $type = fetch('type', 'txp_form', 'name', $newname);
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
                $_POST['newname'] = $newname;
            }

            $exists = safe_field("name", 'txp_form', "name = '".doSlash($newname)."'");

            if ($newname !== $name && $exists !== false) {
                $message = array(gTxt('form_already_exists', array('{name}' => $newname)), E_ERROR);
                if ($savenew) {
                    $_POST['newname'] = '';
                }

                $save_error = true;
            } else {
                if ($savenew or $copy) {
                    if ($newname) {
                        if (safe_insert(
                                'txp_form',
                                "Form = '$Form',
                                type = '$type',
                                name = '".doSlash($newname)."'"
                        )) {
                            update_lastmod('form_created', compact('newname', 'name', 'type', 'Form'));
                            $message = gTxt('form_created', array('{name}' => $newname));
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
                            name = '".doSlash($newname)."'",
                            "name = '".doSlash($name)."'"
                    )) {
                        update_lastmod('form_saved', compact('newname', 'name', 'type', 'Form'));
                        $message = gTxt('form_updated', array('{name}' => $name));
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
        callback_event('form_saved', '', 0, $name, $newname);
    }

    form_edit($message);
}

/**
 * Deletes a form template with the given name.
 *
 * @param  string $name The form template
 * @return bool FALSE on error
 */

function form_delete($name)
{
    global $essential_forms;

    if (in_array($name, $essential_forms)) {
        return false;
    }

    $name = doSlash($name);

    return safe_delete('txp_form', "name = '$name'");
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

    return safe_update('txp_form', "type = '$type'", "name = '$name'");
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
 * Return a list of tag builder tags.
 *
 * @return HTML
 */

function form_tagbuild()
{
    $listActions = graf(
        href('<span class="ui-icon ui-icon-arrowthickstop-1-s"></span> '.gTxt('expand_all'), '#', array(
            'class'         => 'txp-expand-all',
            'aria-controls' => 'tagbuild_links',
        )).
        href('<span class="ui-icon ui-icon-arrowthickstop-1-n"></span> '.gTxt('collapse_all'), '#', array(
            'class'         => 'txp-collapse-all',
            'aria-controls' => 'tagbuild_links',
        )), array('class' => 'txp-actions')
    );

    // Generate the tagbuilder links.
    // Format of each entry is popTagLink -> array ( gTxt string, class/ID ).
    $tagbuild_items = array(
        'article'         => array('articles', 'article-tags'),
        'link'            => array('links', 'link-tags'),
        'comment'         => array('comments', 'comment-tags'),
        'comment_details' => array('comment_details', 'comment-detail-tags'),
        'comment_form'    => array('comment_form', 'comment-form-tags'),
        'search_result'   => array('search_results_form', 'search-result-tags'),
        'file_download'   => array('file_download_tags', 'file-tags'),
        'category'        => array('category_tags', 'category-tags'),
        'section'         => array('section_tags', 'section-tags'),
    );

    $tagbuild_links = '';

    foreach ($tagbuild_items as $tb => $item) {
        $tagbuild_links .= wrapRegion($item[1].'_group', popTagLinks($tb), $item[1], $item[0], $item[1]);
    }

    return $listActions.$tagbuild_links;
}

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
    ));

    switch (strtolower($step)) {
        case "":
            form_edit();
            break;
        case "form_edit":
            form_edit();
            break;
        case "form_create":
            form_create();
            break;
        case "form_delete":
            form_delete();
            break;
        case "form_multi_edit":
            form_multi_edit();
            break;
        case "form_save":
            form_save();
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
        'name, type',
        'txp_form',
        "$criteria order by field(type, ".join(',', quote_list(array_keys($form_types))).") asc, name asc"
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

            if ($active) {
                $editlink = txpspecialchars($name);
            } else {
                $editlink = eLink('form', 'form_edit', 'name', $name, $name);
            }

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
        $rs = safe_row('*', 'txp_form', "name='".doSlash($name)."'");
        extract($rs);
    }

    if (in_array($name, $essential_forms)) {
        $name_widgets = span(gTxt('form_name'), array('class' => 'txp-label-fixed')).br.
            span($name, array('class' => 'txp-value-fixed'));

        $type_widgets = span(gTxt('form_type'), array('class' => 'txp-label-fixed')).br.
            span($type, array('class' => 'txp-value-fixed'));
    } else {
        $name_widgets = tag(gTxt('form_name'), 'label', 'for="new_form"').br.
            fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_form', false, true);

        $type_widgets = tag(gTxt('form_type'), 'label', 'for="type"').br.
            formTypes($type, false);
    }

    $buttons = href(gTxt('duplicate'), '#', array(
        'id'    => 'txp_clone',
        'class' => 'clone',
        'title' => gTxt('form_clone'),
    ));

    if ($name) {
        $name_widgets .= n.span($buttons, array('class' => 'txp-actions'));
    } else {
        $name_widgets .= hInput('savenew', 'savenew');
    }

    // Generate the tagbuilder links.
    // Format of each entry is popTagLink -> array ( gTxt string, class/ID ).
    $tagbuild_items = array(
        'article' => array(
            'articles',
            'article-tags',
        ),
        'link' => array(
            'links',
            'link-tags',
        ),
        'comment' => array(
            'comments',
            'comment-tags',
        ),
        'comment_details' => array(
            'comment_details',
            'comment-detail-tags',
        ),
        'comment_form' => array(
            'comment_form',
            'comment-form-tags',
        ),
        'search_result' => array(
            'search_results_form',
            'search-result-tags',
        ),
        'file_download' => array(
            'file_download_tags',
            'file-tags',
        ),
        'category' => array(
            'category_tags',
            'category-tags',
        ),
        'section' => array(
            'section_tags',
            'section-tags',
        ),
    );

    $tagbuild_links = '';

    foreach ($tagbuild_items as $tb => $item) {
        $tagbuild_links .= wrapRegion($item[1].'_group', popTagLinks($tb), $item[1], $item[0], $item[1]);
    }

    echo hed(gTxt('tab_forms').popHelp('forms_overview'), 1, array('class' => 'txp-heading'));
    echo n.tag(

        n.tag(
            hed(gTxt('tagbuilder'), 2).
            $tagbuild_links.n, 'div', array(
            'id'    => 'tagbuild_links',
            'class' => 'txp-layout-cell txp-layout-1-4',
        )).

        n.tag(
            form(
                graf($name_widgets).
                graf(
                    tag(gTxt('form_code'), 'label', array('for' => 'form')).
                    br.'<textarea class="code" id="form" name="Form" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($Form).'</textarea>'
                ).
                graf($type_widgets).
                graf(
                    fInput('submit', 'save', gTxt('save'), 'publish').
                    eInput('form').sInput('form_save').
                    hInput('name', $name)
                ), '', '', 'post', 'edit-form', '', 'form_form').n, 'div', array(
            'id'    => 'main_content',
            'class' => 'txp-layout-cell txp-layout-2-4',
        )).

        n.tag(
            graf(sLink('form', 'form_create', gTxt('create_new_form')), ' class="action-create"').
            form_list($name).n, 'div', array(
            'id'    => 'content_switcher',
            'class' => 'txp-layout-cell txp-layout-1-4',
        )).n, 'div', array(
        'id'    => $event.'_container',
        'class' => 'txp-layout-grid',
    ));
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

            $exists = safe_field('name', 'txp_form', "name = '".doSlash($newname)."'");

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

    return safe_delete("txp_form", "name='$name'");
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

    return safe_update('txp_form', "type='$type'", "name='$name'");
}

/**
 * Renders a &lt;select&gt; input listing all form types.
 *
 * @param  string $type        The selected option
 * @param  bool   $blank_first If TRUE, the list defaults to an empty selection
 * @param  string $id          HTML id attribute value
 * @return string HTML
 * @access private
 */

function formTypes($type, $blank_first = true, $id = 'type')
{
    global $form_types;

    return selectInput('type', $form_types, $type, $blank_first, '', $id);
}

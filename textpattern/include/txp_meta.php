<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
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

use Textpattern\Meta\Field;
use Textpattern\Meta\FieldSet;
use Textpattern\Meta\DataType;
use Textpattern\Meta\ContentType;
use Textpattern\Validator\Validator;
use Textpattern\Search\Filter;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'meta') {
    require_privs('meta');

    global $vars;

    $vars = array(
        'id',
        'name',
        'labelStr',
        'content_type',
        'render',
        'default',
        'family',
        'textfilter',
        'delimiter',
        'ordinal',
        'created',
        'modified',
        'expires',
    );

    global $all_content_types, $all_render_types;
    $all_content_types = Txp::get('\Textpattern\Meta\ContentType')->getLabel();
    $dataMap = Txp::get('\Textpattern\Meta\DataType')->get();
    $all_render_types = array_combine(array_keys($dataMap), array_keys($dataMap));

    $available_steps = array(
        'meta_list'          => false,
        'meta_edit'          => false,
        'meta_save_ui'       => true,
        'meta_change_pageby' => true,
        'meta_multi_edit'    => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        meta_list();
    }
}

/**
 * The main panel listing all meta data (custom fields).
 *
 * @param string|array $message The activity message
 */

function meta_list($message = '')
{
    global $event, $step, $meta_list_pageby, $txp_user;

    pagetop(gTxt('tab_meta'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('meta_sort_column', 'name');
    } else {
        if (!in_array($sort, array('id', 'name', 'content_type', 'render', 'family', 'ordinal', 'created', 'modified', 'expires'))) {
            $sort = 'name';
        }

        set_pref('meta_sort_column', $sort, 'meta', 2, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('meta_sort_dir', 'asc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('meta_sort_dir', $dir, 'meta', 2, '', 0, PREF_PRIVATE);
    }

    switch ($sort) {
        case 'id' :
            $sort_sql = "id $dir";
            break;
        case 'content_type' :
            $sort_sql = "content_type $dir, id asc";
            break;
        case 'render' :
            $sort_sql = "render $dir, id asc";
            break;
        case 'family' :
            $sort_sql = "family $dir, id asc";
            break;
        case 'ordinal' :
            $sort_sql = "ordinal $dir, id asc";
            break;
        case 'created' :
            $sort_sql = "created $dir";
            break;
        case 'modified' :
            $sort_sql = "modified $dir, created desc";
            break;
        case 'expires' :
            $sort_sql = "expires $dir";
            break;
        default :
            $sort = 'name';
            $sort_sql = "name $dir, id asc";
            break;
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'id' => array(
                'column' => 'txp_meta.id',
                'label'  => gTxt('id'),
                'type'   => 'integer',
            ),
            'name' => array(
                'column' => 'txp_meta.name',
                'label'  => gTxt('name'),
            ),
            'content_type' => array(
                'column' => 'txp_meta.content_type',
                'label'  => gTxt('content_type'),
                'type'   => 'find_in_set',
            ),
            'data_type' => array(
                'column' => 'txp_meta.data_type',
                'label'  => gTxt('data_type'),
                'type'   => 'find_in_set',
            ),
            'render' => array(
                'column' => 'txp_meta.render',
                'label'  => gTxt('render'),
            ),
            'family' => array(
                'column' => 'txp_meta.family',
                'label'  => gTxt('family'),
            ),
            'created' => array(
                'column'  => array('txp_meta.created'),
                'label'   => gTxt('created'),
            ),
            'modified' => array(
                'column'  => array('txp_meta.modified'),
                'label'   => gTxt('modified'),
            ),
            'expires' => array(
                'column'  => array('txp_meta.expires'),
                'label'   => gTxt('expires'),
            ),
        )
    );

    list($criteria, $crit, $search_method) = $search->getFilter(array(
            'id' => array('can_list' => true),
        ));

    $search_render_options = array('placeholder' => 'search_meta');

    if ($criteria === 1) {
        $total = safe_count('txp_meta', $criteria);
    } else {
        $total = getThing("SELECT COUNT(*) FROM txp_meta WHERE $criteria");
    }

    $searchBlock =
        n.tag(
            $search->renderForm('meta_list', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $createBlock[] =
        n.tag(
            sLink('meta', 'meta_edit', gTxt('create_meta'), 'txp-button'),
            'div', array('class' => 'txp-control-panel')
        );

    $createBlock = implode(n, $createBlock);
    $contentBlock = '';

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $paginator->getLimit();

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    if ($total < 1) {
        $contentBlock .= graf(
            span(null, array('class' => 'ui-icon ui-icon-info')).' '.
            gTxt($criteria == 1 ? 'no_meta_recorded' : 'no_results_found'),
            array('class' => 'alert-block information')
        );
    } else {
        $rs = safe_rows_start('*', 'txp_meta', "$criteria order by $sort_sql limit $offset, $limit");

        if ($rs && numRows($rs)) {
            $contentBlock .= n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'meta_form',
                    'name'   => 'longform',
                    'method' => 'post',
                    'action' => 'index.php',
                )).
                n.tag_start('div', array('class' => 'txp-listtables')).
                n.tag_start('table', array('class' => 'txp-list')).
                n.tag_start('thead').
                tr(
                    hCell(
                        fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                            '', ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                    ).
                    column_head(
                        'id', 'id', 'meta', true, $switch_dir, $crit, $search_method,
                            (('id' == $sort) ? "$dir " : '').'txp-list-col-id'
                    ).
                    column_head(
                        'name', 'name', 'meta', true, $switch_dir, $crit, $search_method,
                            (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                    ).
                    column_head(
                        'content_type', 'content_type', 'meta', true, $switch_dir, $crit, $search_method,
                            (('content_type' == $sort) ? "$dir " : '').'txp-list-col-content-type'
                    ).
                    column_head(
                        'render', 'render', 'meta', true, $switch_dir, $crit, $search_method,
                            (('render' == $sort) ? "$dir " : '').'txp-list-col-render'
                    ).
                    column_head(
                        'family', 'family', 'meta', true, $switch_dir, $crit, $search_method,
                            (('family' == $sort) ? "$dir " : '').'txp-list-col-family'
                    ).
                    column_head(
                        'ordinal', 'ordinal', 'meta', true, $switch_dir, $crit, $search_method,
                            (('ordinal' == $sort) ? "$dir " : '').'txp-list-col-order'
                    ).
                    column_head(
                        'created', 'created', 'meta', true, $switch_dir, $crit, $search_method,
                            (('created' == $sort) ? "$dir " : '').'txp-list-col-created date'
                    ).
                    column_head(
                        'modified', 'modified', 'meta', true, $switch_dir, $crit, $search_method,
                            (('modified' == $sort) ? "$dir " : '').'txp-list-col-modified date'
                    ).
                    column_head(
                        'expires', 'expires', 'meta', true, $switch_dir, $crit, $search_method,
                            (('expires' == $sort) ? "$dir " : '').'txp-list-col-expires date'
                    )
                ).
                n.tag_end('thead').
                n.tag_start('tbody');

            $validator = new Validator();

            while ($a = nextRow($rs)) {
                extract($a, EXTR_PREFIX_ALL, 'meta');

                $edit_url = array(
                    'event'         => 'meta',
                    'step'          => 'meta_edit',
                    'id'            => $meta_id,
                    'sort'          => $sort,
                    'dir'           => $dir,
                    'page'          => $page,
                    'search_method' => $search_method,
                    'crit'          => $crit,
                );

/*
TODO: constraints
                $validator->setConstraints(array(new CategoryConstraint($meta_category, array('type' => 'meta'))));
                $vc = $validator->validate() ? '' : ' error';
*/
                $contentBlock .= tr(
                    td(
                        fInput('checkbox', 'selected[]', $meta_id), '', 'txp-list-col-multi-edit'
                    ).
                    hCell(
                        href($meta_id, $edit_url, ' title="'.gTxt('edit').'"'), '', ' class="txp-list-col-id" scope="row"'
                    ).
                    td(
                        href(txpspecialchars($meta_name), $edit_url, ' title="'.gTxt('edit').'"'), '', 'txp-list-col-name'
                    ).
                    td(
                        txpspecialchars($meta_content_type), '', 'txp-list-col-content-type'
                    ).
                    td(
                        txpspecialchars($meta_render), '', 'txp-list-col-render'
                    ).
                    td(
                        txpspecialchars($meta_family), '', 'txp-list-col-family'
                    ).
                    td(
                        txpspecialchars($meta_ordinal), '', 'txp-list-col-ordinal'
                    ).
                    td(
                        txpspecialchars($meta_created), '', 'txp-list-col-created date'
                    ).
                    td(
                        txpspecialchars($meta_modified), '', 'txp-list-col-modified date'
                    ).
                    td(
                        txpspecialchars($meta_expires), '', 'txp-list-col-expires date'
                    )
                );

            }

            $contentBlock .= n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div').
                meta_multiedit_form($page, $sort, $dir, $crit, $search_method).
                tInput().
                n.tag_end('form');
        }
    }

    $pageBlock = $paginator->render().
        nav_form('meta', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table($event);
    echo $table->render(compact('total', 'criteria'), $searchBlock, $createBlock, $contentBlock, $pageBlock);
}

/**
 * Renders and outputs the meta editor panel.
 *
 * @param string|array $message The activity message
 */

function meta_edit($message = '')
{
    global $vars, $event, $step, $txp_user, $all_content_types, $all_render_types;

    pagetop(gTxt('tab_meta'), $message);

    extract(array_map('assert_string', gpsa($vars)));

    $data_types = Txp::get('\Textpattern\Meta\DataType')->get();

    $textfilter_types = array();
    $option_types = array();
    $delimited_types = array();

    foreach ($data_types as $key => $data_type) {
        if ($data_type['textfilter']) {
            $textfilter_types[] = $key;
        }

        if ($data_type['options']) {
            $option_types[] = $key;
        }

        if ($data_type['delimited']) {
            $delimited_types[] = $key;
        }
    }

    $is_edit = ($id && $step == 'meta_edit');
    $default = '';
    $label_ref = '';
    $help_ref = '';
    $inline_help_ref = '';
    $options = array();

    $rs = array();

    if ($is_edit) {
        $id = assert_int($id);
        $cf = new Field($id);
        $rs = $cf->get();

        if ($rs) {
            if (!has_privs('meta')) {
                meta_list(gTxt('restricted_area'));

                return;
            }

            extract($rs);

            $label_ref = $cf->getLabelReference($name);
            $help_ref = $cf->getHelpReference($name);
            $inline_help_ref = $cf->getHelpReference($name, 'inline');
            $has_textfilter = ($textfilter !== null && isset($data_types[$render]) && $data_types[$render]['textfilter'] !== '');
            $table_name = 'txp_meta_value_' . txpspecialchars($data_type);
            $ts = safe_strtotime((string)$created);

            $default = $cf->get('default');
            $options = $cf->get('options');
        }
    } else {
        $render = 'textInput';
    }

    $optionList = array();

    foreach ($options as $opt) {
        $optionList[] = $opt['name'] . ' => ' . gTxt($opt['label']);
    }

    $textfilter_map = implode("','", $textfilter_types);
    $delimiter_map = implode("','", $delimited_types);
    $option_map = implode("','", $option_types);

    if (has_privs('meta')) {
        $caption = gTxt(($is_edit) ? 'edit_meta' : 'create_meta');
        $helpTxt = (gTxt($help_ref) === $help_ref) ? '' : gTxt($help_ref);
        $inlineHelpTxt = (gTxt($inline_help_ref) === $inline_help_ref) ? '' : gTxt($inline_help_ref);

        echo script_js(<<<EOJS
var textfilter_map = ['{$textfilter_map}'];
var delimiter_map = ['{$delimiter_map}'];
var option_map = ['{$option_map}'];
EOJS
        );
        echo form(
            hed($caption, 2).
            inputLabel(
                'labelStr',
                fInput('text', 'labelStr', gTxt($label_ref), '', '', '', INPUT_REGULAR, '', 'labelStr'),
                'label', '', array('class' => 'txp-form-field edit-meta-label')
            ).
            inputLabel(
                'name',
                fInput('text', 'name', txpspecialchars($name), '', '', '', INPUT_REGULAR, '', 'name'),
                'name', '', array('class' => 'txp-form-field edit-meta-name')
            ).
            inputLabel(
                'content_type',
                selectInput('content_type', $all_content_types, $content_type),
                'content_type', '', array('class' => 'txp-form-field edit-meta-content-type')
            ).
            inputLabel(
                'render',
                selectInput('render', $all_render_types, $render, false, '', 'render'),
                'render', '', array('class' => 'txp-form-field edit-meta-render')
            ).
            inputLabel(
                'default',
                fInput('text', 'default', txpspecialchars($default), '', '', '', INPUT_REGULAR, '', 'default'),
                'default', '', array('class' => 'txp-form-field edit-meta-default')
            ).
            inputLabel(
                'family',
                fInput('text', 'family', txpspecialchars($family), '', '', '', INPUT_REGULAR, '', 'family'),
                'family', '', array('class' => 'txp-form-field edit-meta-family')
            ).
            inputLabel(
                'textfilter',
                pref_text('textfilter', $textfilter, 'textfilter'),
                'textfilter', '', array('class' => 'txp-form-field edit-meta-textfilter')
            ).
            inputLabel(
                'delimiter',
                fInput('text', 'delimiter', txpspecialchars($delimiter), '', '', '', INPUT_SMALL, '', 'delimiter'),
                'delimiter', '', array('class' => 'txp-form-field edit-meta-delimiter')
            ).
            inputLabel(
                'ordinal',
                fInput('text', 'ordinal', txpspecialchars($ordinal), '', '', '', INPUT_REGULAR, '', 'ordinal'),
                'ordinal', '', array('class' => 'txp-form-field edit-meta-ordinal')
            ).
            inputLabel(
                'created',
                fInput('text', 'created', txpspecialchars($created), '', '', '', INPUT_REGULAR, '', 'created'),
                'created', '', array('class' => 'txp-form-field edit-meta-created')
            ).
            inputLabel(
                'expires',
                fInput('text', 'expires', txpspecialchars($expires), '', '', '', INPUT_REGULAR, '', 'expires'),
                'expires', '', array('class' => 'txp-form-field edit-meta-expires')
            ).
            inputLabel(
                'options',
                text_area('options', 0, 0, implode(n, $optionList)),
                'options', '', array('class' => 'txp-form-field edit-meta-options')
            ).
            inputLabel(
                'help',
                text_area('help', 0, 0, $helpTxt, 'help'),
                'help', '', array('class' => 'txp-form-field edit-meta-help')
            ).
            inputLabel(
                'inline_help',
                text_area('inline_help', 0, 0, $inlineHelpTxt, 'inline_help'),
                'inline_help', '', array('class' => 'txp-form-field edit-meta-inline-help')
            ).
            pluggable_ui('meta_ui', 'extend_detail_form', '', $rs).
            graf(
                sLink('meta', '', gTxt('cancel'), 'txp-button').
                fInput('submit', '', gTxt('save'), 'publish'),
                array('class' => 'txp-edit-actions')
            ).
            eInput('meta').
            sInput('meta_save_ui').
            hInput('id', $id).
            hInput('search_method', gps('search_method')).
            hInput('crit', gps('crit')).
            hInput('render_orig', $render).
            hInput('name_orig', $name)
        , '', '', 'post', 'txp-edit', '', 'meta_details');
    }
}

/**
 * Perform the save operation from the Textpattern interface.
 *
 * Handles redrawing the screen after save, whereas meta_save()
 * itself just handles the DB interaction, for a cleaner separation
 * of responsibilities and making it easier for plugins to hook into.
 */

function meta_save_ui()
{
    $message = meta_save();
    meta_list($message);
}

/**
 * Saves the custom field configuration to the DB.
 *
 * Reads in content to save from POST.
 *
 * @return string Success/Fail message
 */
function meta_save()
{
    global $vars, $txp_user;

    $message = '';

    if (!has_privs('meta')) {
        $message = gTxt('restricted_area');
    } else {
        $varray = array_map('assert_string', gpsa($vars));

        if ($varray['name'] === '') {
            $message = array(gTxt('meta_empty'), E_ERROR);
        } else {
            $varray['name_orig'] = ps('name_orig');
            $varray['render_orig'] = ps('render_orig');
            $varray['options'] = ps('options');

            if ($varray['id']) {
                $varray['id'] = assert_int($varray['id']);
            }

            $cf = new Field($varray['id']);
            $id = $cf->save($varray);

            if ($id) {
                $_POST['id'] = $id;
                $message = gTxt('meta_saved');
            }
        }
    }

    return $message;
}

// -------------------------------------------------------------

function meta_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    meta_list();
}

// -------------------------------------------------------------

function meta_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $all_content_types, $all_render_types;

    $content_types = $all_content_types ? selectInput('content_type', $all_content_types, '') : '';
    $render_types = $all_render_types ? selectInput('render', $all_render_types, '') : '';

    $methods = array(
        'changecontenttype' => array('label' => gTxt('changecontenttype'), 'html' => $content_types),
        'changerendertype'  => array('label' => gTxt('changerendertype'), 'html' => $render_types),
        'delete'            => gTxt('delete'),
    );

    if (!$content_types) {
        unset($methods['changecontenttype']);
    }

    if (!$render_types) {
        unset($methods['changerendertype']);
    }

    return multi_edit($methods, 'meta', 'meta_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

// -------------------------------------------------------------

function meta_multi_edit()
{
    global $txp_user, $all_content_types, $all_render_types;

    $selected = ps('selected');

    if (!$selected or !is_array($selected)) {
        meta_list();

        return;
    }

    $selected = array_map('assert_int', $selected);
    $method   = ps('edit_method');
    $changed  = array();
    $key = '';

    switch ($method) {
        case 'delete':
            if (has_privs('meta')) {
                foreach ($selected as $id) {
                    // @Todo Atomic with rollback?
                    $dType = safe_field('data_type', 'txp_meta', 'id = '.$id);
                    safe_delete('txp_meta_value_'.$dType, 'meta_id = '.$id);
                    safe_delete('txp_meta_options', 'meta_id = '.$id);

                    if (safe_delete('txp_meta', 'id = '.$id)) {
                        $changed[] = $id;
                    }
                }
            }

            if ($changed) {
                callback_event('meta_deleted', '', 0, $changed);
            }

            $key = '';
            break;
        case 'changecontenttype':
            $val = ps('content_type');
            if (in_array($val, $all_content_types)) {
                $key = 'content_type';
            }
            break;
        case 'changerendertype':
            $val = ps('render');
            if (in_array($val, $all_render_types)) {
                $key = 'render';
            }
            break;
        default:
            $key = '';
            $val = '';
            break;
    }
/*
// Todo when save is refactored to put the atomic insert/update + create-if-not-exists
// into its own function.
    if ($selected && $key) {
        foreach ($selected as $id) {
            if (safe_update('txp_meta', "$key = '".doSlash($val)."'", "id = $id")) {
                $changed[] = $id;
            }
        }
    }

    if ($changed) {
        update_lastmod(); // Needed?

        meta_list(gTxt(
            ($method == 'delete' ? 'meta_deleted' : 'meta_updated'),
            array(($method == 'delete' ? '{list}' : '{name}') => join(', ', $changed))));

        return;
    }
*/
    meta_list();
}

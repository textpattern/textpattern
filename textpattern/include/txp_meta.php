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
        'ordinal',
        'created',
        'modified',
        'expires',
    );

    global $all_content_types, $all_render_types;
    $all_content_types = Txp::get('Textpattern_Meta_ContentTypes')->get();
    $dataMap = Txp::get('Textpattern_Meta_DataTypes')->get();
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

// -------------------------------------------------------------

function meta_list($message = '')
{
    global $event, $step, $meta_list_pageby, $txp_user;

    pagetop(gTxt('tab_meta'), $message);

    extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

    if ($sort === '') {
        $sort = get_pref('meta_sort_column', 'name');
    }

    if ($dir === '') {
        $dir = get_pref('meta_sort_dir', 'asc');
    }
    $dir = ($dir == 'desc') ? 'desc' : 'asc';

    switch ($sort) {
        case 'id' :
            $sort_sql = 'id '.$dir;
            break;
        case 'content_type' :
            $sort_sql = 'content_type '.$dir.', id asc';
            break;
        case 'render' :
            $sort_sql = 'render '.$dir.', id asc';
            break;
        case 'family' :
            $sort_sql = 'family '.$dir.', id asc';
            break;
        case 'ordinal' :
            $sort_sql = 'ordinal '.$dir.', id asc';
            break;
        case 'created' :
            $sort_sql = 'created '.$dir;
            break;
        case 'modified' :
            $sort_sql = 'modified '.$dir.', created desc';
            break;
        case 'expires' :
            $sort_sql = 'expires '.$dir;
            break;
        default :
            $sort = 'name';
            $sort_sql = 'name '.$dir.', id asc';
            break;
    }

    set_pref('meta_sort_column', $sort, 'meta', 2, '', 0, PREF_PRIVATE);
    set_pref('meta_sort_dir', $dir, 'meta', 2, '', 0, PREF_PRIVATE);

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $criteria = 1;

    if ($search_method and $crit != '') {
        $verbatim = preg_match('/^"(.*)"$/', $crit, $m);
        $crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
        $critsql = $verbatim ?
            array(
                'id'           => "id in ('" .join("','", do_list($crit_escaped)). "')",
                'name'         => "name = '$crit_escaped'",
                'content_type' => "content_type = '$crit_escaped'",
                'render'       => "render = '$crit_escaped'",
                'family'       => "family = '$crit_escaped'",
                'ordinal'      => "ordinal = '$crit_escaped'",
                'created'      => "created = '$crit_escaped'",
                'modified'     => "modified = '$crit_escaped'",
                'expires'      => "expires = '$crit_escaped'"
            ) : array(
                'id'           => "id in ('" .join("','", do_list($crit_escaped)). "')",
                'name'         => "name like '%$crit_escaped%'",
                'content_type' => "content_type like '%$crit_escaped%'",
                'render'       => "render like '%$crit_escaped%'",
                'family'       => "family like '%$crit_escaped%'",
                'ordinal'      => "ordinal like '%$crit_escaped%'",
                'created'      => "created like '%$crit_escaped%'",
                'modified'     => "modified like '%$crit_escaped%'",
                'expires'      => "expires like '%$crit_escaped%'"
            );

        if (array_key_exists($search_method, $critsql)) {
            $criteria = $critsql[$search_method];
        } else {
            $search_method = '';
            $crit = '';
        }
    } else {
        $search_method = '';
        $crit = '';
    }

    $criteria .= callback_event('admin_criteria', 'meta_list', 0, $criteria);

    $total = getCount('txp_meta', $criteria);

    echo hed(gTxt('tab_meta'), 1, array('class' => 'txp-heading'));
    echo n.'<div id="'.$event.'_control" class="txp-control-panel">';

    echo graf(
        sLink('meta', 'meta_edit', gTxt('add_new_meta'))
        , ' class="txp-buttons"');

    if ($total < 1) {
        if ($criteria != 1) {
            echo meta_search_form($crit, $search_method).
                graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
        } else {
            echo graf(gTxt('no_metas_recorded'), ' class="indicator"').'</div>';
        }

        return;
    }

    $limit = max($meta_list_pageby, 15);

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    echo meta_search_form($crit, $search_method).'</div>';

    $rs = safe_rows_start('*', 'txp_meta', "$criteria order by $sort_sql limit $offset, $limit");

    if ($rs) {
        echo
            n.tag_start('div', array(
                'id'    => $event.'_container',
                'class' => 'txp-container',
            )).
            n.tag_start('form', array(
                'action' => 'index.php',
                'id'     => 'meta_form',
                'class'  => 'multi_edit_form',
                'method' => 'post',
                'name'   => 'longform',
            )).
            n.tag_start('div', array('class' => 'txp-listtables')).
            n.tag_start('table', array('class' => 'txp-list')).
            n.tag_start('thead').
            tr(
                hCell(
                    fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                        '', ' scope="col" title="'.gTxt('toggle_all_selected').'" class="txp-list-col-multi-edit"'
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
                        (('content_type' == $sort) ? "$dir " : '').'txp-list-col-content-type meta_detail'
                ).
                column_head(
                    'render', 'render', 'meta', true, $switch_dir, $crit, $search_method,
                        (('render' == $sort) ? "$dir " : '').'txp-list-col-render'
                ).
                column_head(
                    'family', 'family', 'meta', true, $switch_dir, $crit, $search_method,
                        (('family' == $sort) ? "$dir " : '').'txp-list-col-family meta_detail'
                ).
                column_head(
                    'ordinal', 'ordinal', 'meta', true, $switch_dir, $crit, $search_method,
                        (('ordinal' == $sort) ? "$dir " : '').'txp-list-col-order'
                ).
                column_head(
                    'created', 'created', 'meta', true, $switch_dir, $crit, $search_method,
                        (('created' == $sort) ? "$dir " : '').'txp-list-col-created date meta_detail'
                ).
                column_head(
                    'modified', 'modified', 'meta', true, $switch_dir, $crit, $search_method,
                        (('modified' == $sort) ? "$dir " : '').'txp-list-col-modified date meta_detail'
                ).
                column_head(
                    'expires', 'expires', 'meta', true, $switch_dir, $crit, $search_method,
                        (('expires' == $sort) ? "$dir " : '').'txp-list-col-expires date meta_detail'
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

            echo tr(
                td(
                    fInput('checkbox', 'selected[]', $meta_id), '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    href($meta_id, $edit_url, ' title="'.gTxt('edit').'"'), '', ' scope="row" class="txp-list-col-id"'
                ).
                td(
                    href(txpspecialchars($meta_name), $edit_url, ' title="'.gTxt('edit').'"'), '', 'txp-list-col-name'
                ).
                td(
                    txpspecialchars($meta_content_type), '', 'txp-list-col-content-type meta_detail'
                ).
                td(
                    txpspecialchars($meta_render), '', 'txp-list-col-render'
                ).
                td(
                    txpspecialchars($meta_family), '', 'txp-list-col-family meta_detail'
                ).
                td(
                    txpspecialchars($meta_ordinal), '', 'txp-list-col-ordinal'
                ).
                td(
                    txpspecialchars($meta_created), '', 'txp-list-col-created date meta_detail'
                ).
                td(
                    txpspecialchars($meta_modified), '', 'txp-list-col-modified date meta_detail'
                ).
                td(
                    txpspecialchars($meta_expires), '', 'txp-list-col-expires date meta_detail'
                )
            );
        }

        echo
            n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div').
            meta_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            graf(toggle_box('meta_detail'), array('class' => 'detail-toggle')).

            n.tag_start('div', array(
                'id'    => $event.'_navigation',
                'class' => 'txp-navigation',
            )).
            pageby_form('meta', $meta_list_pageby).
            nav_form('meta', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div').
            n.tag_end('div');
    }
}

// -------------------------------------------------------------

function meta_search_form($crit, $method)
{
    $methods = array(
        'id'           => gTxt('ID'),
        'name'         => gTxt('meta_name'),
        'content_type' => gTxt('content_type'),
        'render'       => gTxt('render'),
        'family'       => gTxt('family'),
        'ordinal'      => gTxt('ordinal'),
        'created'      => gTxt('created'),
        'modified'     => gTxt('modified'),
        'expires'      => gTxt('expires'),
    );

    return search_form('meta', 'meta_list', $crit, $methods, $method, 'name');
}

// -------------------------------------------------------------

function meta_edit($message = '')
{
    global $vars, $event, $step, $txp_user, $all_content_types, $all_render_types;

    pagetop(gTxt('tab_meta'), $message);

    echo '<div id="'.$event.'_container" class="txp-container">';

    extract(array_map('assert_string', gpsa($vars)));

    $data_types = Txp::get('Textpattern_Meta_DataTypes')->get();

    $textfilter_types = array();
    $option_types = array();

    foreach ($data_types as $key => $data_type) {
        if ($data_type['textfilter']) {
            $textfilter_types[] = $key;
        }
        if ($data_type['options']) {
            $option_types[] = $key;
        }
    }

    $is_edit = ($id && $step == 'meta_edit');
    $default = ''; 
    $label_ref = '';
    $help_ref = '';
    $options = array();

    $rs = array();

    if ($is_edit) {
        $id = assert_int($id);
        $cf = new Textpattern_Meta_Field($id);
        $rs = $cf->get();

        if ($rs) {
            if (!has_privs('meta')) {
                meta_list(gTxt('restricted_area'));

                return;
            }

            extract($rs);

            $label_ref = $cf->getLabelReference($name);
            $help_ref = $cf->getHelpReference($name);
            $has_textfilter = ($textfilter !== null && $data_types[$render]['textfilter'] !== '');
            $table_name = 'txp_meta_value_' . $data_type;
            $ts = safe_strtotime($created);

            $default = $cf->get('default');
            $options = $cf->get('options');
        }
    } else {
        $render = 'text_input';
    }

    $optionList = array();

    foreach ($options as $opt) {
        $optionList[] = $opt['value'] . ' => ' . gTxt($opt['label']);
    }

    $textfilter_map = implode("','", $textfilter_types);
    $option_map = implode("','", $option_types);

    if (has_privs('meta')) {
        $caption = gTxt(($is_edit) ? 'edit_meta' : 'add_new_meta');
        $helpTxt = (gTxt($help_ref) === $help_ref) ? '' : gTxt($help_ref);

        echo script_js(<<<EOJS
var textfilter_map = ['{$textfilter_map}'];
var option_map = ['{$option_map}'];
EOJS
        );
        echo form(
            n.'<section class="txp-edit">'.
            hed($caption, 2).
            inputLabel('labelStr', fInput('text', 'labelStr', gTxt($label_ref), '', '', '', INPUT_REGULAR, '', 'labelStr'), 'label').
            inputLabel('name', fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'name'), 'name').
            inputLabel('content_type', selectInput('content_type', $all_content_types, $content_type)).
            inputLabel('render', selectInput('render', $all_render_types, $render, false, '', 'render')).
            inputLabel('default', fInput('text', 'default', $default, '', '', '', INPUT_REGULAR, '', 'default')).
            inputLabel('family', fInput('text', 'family', $family, '', '', '', INPUT_REGULAR, '', 'family'), 'family').
            inputLabel('textfilter', pref_text('textfilter', $textfilter, 'textfilter'), 'textfilter').
            inputLabel('ordinal', fInput('text', 'ordinal', $ordinal, '', '', '', INPUT_REGULAR, '', 'ordinal'), 'ordinal').
            inputLabel('created', fInput('text', 'created', $created, '', '', '', INPUT_REGULAR, '', 'created'), 'created').
            inputLabel('expires', fInput('text', 'expires', $expires, '', '', '', INPUT_REGULAR, '', 'expires'), 'expires').
            inputLabel('options', text_area('options', 0, 0, implode(n, $optionList)), 'options').
            inputLabel('help', text_area('help', 0, 0, $helpTxt, 'help'), 'help').
            pluggable_ui('meta_ui', 'extend_detail_form', '', $rs).
            graf(fInput('submit', '', gTxt('save'), 'publish')).
            eInput('meta').
            sInput('meta_save_ui').
            hInput('id', $id).
            hInput('search_method', gps('search_method')).
            hInput('crit', gps('crit')).
            hInput('render_orig', $render).
            hInput('name_orig', $name).
            n.'</section>'
        , '', '', 'post', 'edit-form', '', 'meta_details');
    }

    echo '</div>';
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

            $cf = new Textpattern_Meta_Field($varray['id']);
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
    event_change_pageby('meta');
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
        case 'delete' :
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
        case 'changecontenttype' :
            $val = ps('content_type');
            if (in_array($val, $all_content_types)) {
                $key = 'content_type';
            }
            break;
        case 'changerendertype' :
            $val = ps('render');
            if (in_array($val, $all_render_types)) {
                $key = 'render';
            }
            break;
        default :
            $key = '';
            $val = '';
            break;
    }
/*
// ToDo when save is refactored to put the atomic insert/update + create-if-not-exists
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

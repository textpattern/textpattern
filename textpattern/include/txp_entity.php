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

if ($event == 'entity') {
    require_privs('meta');

    global $all_content_types, $all_render_types, $all_tables;
    $all_tables = Txp::get('\Textpattern\Meta\ContentType')->getTableColumnMap();

    $available_steps = array(
        'entity_list'          => false,
        'entity_edit'          => false,
        'entity_save_ui'       => true,
        'entity_save'     => true,
        'entity_change_pageby' => true,
        'entity_multi_edit'    => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        entity_list(/*test_integrity()*/);
    }
}

function test_integrity() {
    $message = array();
    $all_types = safe_column('id', 'txp_meta_entity');
    $in_types = $all_types ? implode(',', $all_types) : '-1';
    $all_metas = safe_column('id', 'txp_meta');
    $in_metas = $all_metas ? implode(',', $all_metas) : '-1';

    if ($c = safe_count('txp_meta_registry', "type_id NOT IN ($in_types)")) {
        $message[] = $c.' invalid_registry';
    }

    if ($c = safe_count('txp_meta_fieldsets', "type_id NOT IN ($in_types) OR meta_id NOT IN ($in_metas)")) {
        $message[] = $c.' invalid_fieldsets';
    }

    if ($c = safe_count('txp_meta_delta', "type_id NOT IN ($in_types) OR ABS(meta_id) NOT IN ($in_metas)")) {
        $message[] = $c.' invalid_deltas';
    }

    if ($c = safe_count('txp_meta_delta', "meta_id IN (SELECT meta_id FROM ".PFX."txp_meta_fieldsets WHERE type_id = txp_meta_delta.type_id)")) {
        $message[] = $c.' overlapping_deltas';
    }

    if ($c = safe_count('txp_meta_delta', "meta_id < 0 AND -meta_id NOT IN (SELECT meta_id FROM ".PFX."txp_meta_fieldsets WHERE type_id = txp_meta_delta.type_id)")) {
        $message[] = $c.' underlapping_deltas';
    }

    if ($c = getThing("SELECT COUNT(*) FROM ".PFX."txp_meta_value_varchar v WHERE v.content_id > 0 AND v.meta_id NOT IN
                (SELECT f.meta_id FROM ".PFX."txp_meta_fieldsets f JOIN ".PFX."txp_meta_entity e ON f.type_id=e.id JOIN ".PFX."txp_meta_registry r ON f.type_id=r.type_id WHERE e.table_id=v.table_id)")
    ) {
        $message[] = $c.' orphaned_varchar';
    }

    return $message ? array(implode(br, $message), 2) : '';
}


/**
 * The main panel listing all meta data (custom fields).
 *
 * @param string|array $message The activity message
 */

function entity_list($message = '')
{
    global $app_mode, $event, $step, $all_tables, $txp_user;

    pagetop(gTxt('tab_entity'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('entity_sort_column', 'name');
    } else {
        if (!in_array($sort, array('id', 'name', 'table'))) {
            $sort = 'name';
        }

        set_pref('entity_sort_column', $sort, 'meta', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('entity_sort_dir', 'asc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('entity_sort_dir', $dir, 'meta', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    switch ($sort) {
        case 'id' :
            $sort_sql = "id $dir";
            break;
        case 'table' :
            $sort_sql = "table_id $dir, id asc";
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
                'column' => 'id',
                'label'  => gTxt('id'),
                'type'   => 'integer',
            ),
            'name' => array(
                'column' => 'name',
                'label'  => gTxt('name'),
            ),
            'table' => array(
                'column'  => 'table_id',
                'label'   => gTxt('type'),
            ),
        )
    );

    list($criteria, $crit, $search_method) = $search->getFilter(array(
            'id' => array('can_list' => true),
        ));

    $search_render_options = array('placeholder' => 'search_meta');

    $searchBlock =
        n.tag(
            $search->renderForm('entity_list', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $createBlock[] =
        n.tag(
            sLink('entity', 'entity_edit', gTxt('create_entity'), 'txp-button'),
            'div', array('class' => 'txp-control-panel')
        );

    $createBlock = implode(n, $createBlock);
    $contentBlock = '';

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $paginator->getLimit();
    $total = safe_count('txp_meta_entity', $criteria);

//    $sql_group = "(SELECT GROUP_CONCAT(type_id) FROM ".safe_pfx('txp_meta_fieldsets')." WHERE meta_id = txp_meta.id) AS content_type";

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    if ($total < 1) {
        if ($app_mode == 'json') {
            send_json_response(array());
            exit;
        }

        $contentBlock .= graf(
            span(null, array('class' => 'ui-icon ui-icon-info')).' '.
            gTxt($criteria == 1 ? 'no_meta_recorded' : 'no_results_found'),
            array('class' => 'alert-block information')
        );
    } else {
        $rs = safe_rows_start("*", 'txp_meta_entity', "$criteria order by $sort_sql limit $offset, $limit");

        if ($app_mode == 'json') {
            send_json_response($rs);
            exit;
        }

        $contentBlock .= pluggable_ui('entity_ui', 'extend_controls', '', $rs);

        if ($rs && numRows($rs)) {
            $contentBlock .= n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'entity_form',
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
                        'id', 'id', 'entity', true, $switch_dir, $crit, $search_method,
                            (('id' == $sort) ? "$dir " : '').'txp-list-col-id'
                    ).
                    column_head(
                        'name', 'name', 'entity', true, $switch_dir, $crit, $search_method,
                            (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                    ).
                    column_head(
                        'type', 'table', 'entity', true, $switch_dir, $crit, $search_method,
                            (('table' == $sort) ? "$dir " : '').'txp-list-col-table'
                    ).
                    column_head(
                        'custom', 'custom', 'entity', false, $switch_dir, $crit, $search_method,
                            (('custom' == $sort) ? "$dir " : '').'txp-list-col-custom'
                    )
                ).
                n.tag_end('thead').
                n.tag_start('tbody');

            $validator = new Validator();

            $edit_url = array(
                'event'         => 'entity',
                'step'          => 'entity_edit',
                'id'            => 0,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
            );

            while ($a = nextRow($rs)) {
                extract($a);

                $edit_url['id'] = $id;
                
                if ($meta = safe_column(array('id', 'name'), 'txp_meta', 'id IN (SELECT meta_id FROM '.PFX."txp_meta_fieldsets WHERE type_id = $id) ORDER BY name")) {
                    array_walk($meta, function(&$v, $k) {
                        $v = eLink('meta', 'meta_edit', 'id', $k, $v);
                    });
                }

                $meta = $meta ? implode(br, $meta) : '';
/*
TODO: constraints
                $validator->setConstraints(array(new CategoryConstraint($meta_category, array('type' => 'meta'))));
                $vc = $validator->validate() ? '' : ' error';
*/
                $contentBlock .= tr(
                    td(
                        fInput('checkbox', 'selected[]', $id), '', 'txp-list-col-multi-edit'
                    ).
                    hCell(
                        href($id, $edit_url, ' title="'.gTxt('edit').'"'), '', ' class="txp-list-col-id" scope="row"'
                    ).
                    td(
                        href(txpspecialchars($name), $edit_url, ' title="'.gTxt('edit').'"'), '', 'txp-list-col-name'
                    ).
                    td(
                        txpspecialchars($all_tables[$table_id]['label']), '', 'txp-list-col-table'
                    ).
                    td(
                        $meta, '', 'txp-list-col-meta'
                    )
                );

            }

            $contentBlock .= n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div').
                entity_multiedit_form($page, $sort, $dir, $crit, $search_method).
                tInput().
                n.tag_end('form');
        }
    }

    $pageBlock = $paginator->render().
        nav_form($event, $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table($event);
    echo $table->render(compact('total', 'crit'), $searchBlock, $createBlock, $contentBlock, $pageBlock);
}

/**
 * Perform the save operation from the Textpattern interface.
 *
 * Handles redrawing the screen after save, whereas meta_save()
 * itself just handles the DB interaction, for a cleaner separation
 * of responsibilities and making it easier for plugins to hook into.
 */

function entity_save_ui()
{
    $message = entity_save();
    entity_list($message);
}

// -------------------------------------------------------------

function entity_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    entity_list();
}

// -------------------------------------------------------------

function entity_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $all_content_types, $all_render_types;

//    $content_types = $all_content_types ? selectInput('content_type', $all_content_types, '') : '';
    $render_types = $all_render_types ? selectInput('render', $all_render_types, '') : '';

    $methods = array(
//        'changecontenttype' => array('label' => gTxt('changecontenttype'), 'html' => $content_types),
        'changerendertype'  => array('label' => gTxt('changerendertype'), 'html' => $render_types),
        'delete'            => gTxt('delete'),
    );
/*
    if (!$content_types) {
        unset($methods['changecontenttype']);
    }
*/
    if (!$render_types) {
        unset($methods['changerendertype']);
    }

    return multi_edit($methods, 'entity', 'entity_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

// -------------------------------------------------------------

function entity_multi_edit()
{
    global $txp_user, $all_content_types, $all_render_types;

    $selected = ps('selected');

    if (!$selected or !is_array($selected)) {
        entity_list();

        return;
    }

    $selected = array_map('intval', $selected);
    $method   = ps('edit_method');
    $changed  = array();
    $key = '';

    switch ($method) {
        case 'delete':
            if (has_privs('meta')) {
                Txp::get('\Textpattern\Meta\ContentType')->delete($selected);
                callback_event('entity_deleted', '', 0, $selected);
            }

            $key = '';
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
   entity_list(gTxt(
        ($method == 'delete' ? 'meta_deleted' : 'meta_updated'),
        array(($method == 'delete' ? '{list}' : '{name}') => join(', ', $changed))));
}

/**
 * Renders and outputs the meta editor panel.
 *
 * @param string|array $message The activity message
 */

function entity_edit($message = '')
{
    global $event, $step, $DB;

    pagetop(gTxt('tab_entity'), $message);

//    $txp_tables = getThings('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = "'.$DB->db.'" AND TABLE_TYPE LIKE "BASE_TABLE" AND TABLE_NAME NOT LIKE "'.PFX.'txp\_meta%"');
    $txp_tables = array_column(Txp::get('\Textpattern\Meta\ContentType')->getTableColumnMap(), 'label', 'id');

    $id = intval(gps('id'));

    if ($id && $type = safe_row('*', 'txp_meta_entity', "id = $id")) {
        extract($type);
    } else {
        list($id, $name, $label, $table_id) = array(0, '', '', 1);
    }

    $caption = gTxt($id ? 'edit_entity' : 'create_entity');
//    $families = do_list_unique(safe_field('GROUP_CONCAT(family)', 'txp_meta', "family > '' ORDER BY family"));
    $all_metas = safe_column(array('id', 'name'), 'txp_meta', "1 ORDER BY name");
    $metas = $id ? safe_column('meta_id', 'txp_meta_fieldsets', "type_id = $id") : array();

    echo form(
        hed($caption, 2).
        inputLabel(
            'label',
            fInput('text', 'label', $label, '', '', '', INPUT_REGULAR, '', 'label'),
            'label', '', array('class' => 'txp-form-field edit-meta-label')
        ).
        inputLabel(
            'name',
            fInput('text', 'name', txpspecialchars($name), '', '', '', INPUT_REGULAR, '', 'name'),
            'name', '', array('class' => 'txp-form-field edit-meta-name')
        ).
        inputLabel(
            'table',
            selectInput(array('name' =>'table_id', 'disabled' => !empty($id)), $txp_tables, $table_id, false, '', 'table'),
            'type', '', array('class' => 'txp-form-field edit-meta-content-type')
        ).($all_metas ?
        inputLabel(
            'meta',
            selectInput(array('name' => 'meta'), $all_metas, $metas, false, '', 'meta'),
            'meta', '', array('class' => 'txp-form-field edit-meta-field')/*
        ) : '').($families ?
        inputLabel(
            'family',
            selectInput(array('name' =>false), $families, array(), false, '', 'family'),
            'add_from', '', array('class' => 'txp-form-field edit-meta-family')*/
        ) : '').
//            pluggable_ui('meta_ui', 'extend_detail_form', '', $rs).
        graf(
            sLink('entity', '', gTxt('cancel'), 'txp-button').
            fInput('submit', '', gTxt('save'), 'publish'),
            array('class' => 'txp-edit-actions')
        ).
        eInput('entity').
        sInput('entity_save').
        hInput('id', $id).
        hInput('search_method', gps('search_method')).
        hInput('crit', gps('crit'))
    , '', '', 'post', 'txp-edit', '', 'meta_entity');
}

function entity_save()
{
    $ok = \Txp::get('\Textpattern\Meta\ContentType')->save(gpsa(array(
        'id',
        'name',
        'label',
        'table_id',
        'meta',
        ))
    );
    entity_list(gTxt($ok ? 'meta_saved' : 'meta_save_failed'));
}
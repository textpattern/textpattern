<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2014 The Textpattern Development Team
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

    if ($event == 'link') {
        require_privs('link');

        global $vars;
        $vars = array('category', 'url', 'linkname', 'linksort', 'description', 'id');

        global $all_link_cats, $all_link_authors;
        $all_link_cats = getTree('root', 'link');
        $all_link_authors = the_privileged('link.edit.own');

        $available_steps = array(
            'link_list'          => false,
            'link_edit'          => false,
            'link_save'          => true,
            'link_change_pageby' => true,
            'link_multi_edit'    => true,
        );

        if ($step && bouncer($step, $available_steps)) {
            $step();
        } else {
            link_list();
        }
    }

// -------------------------------------------------------------

    function link_list($message = '')
    {
        global $event, $step, $link_list_pageby, $txp_user;

        pagetop(gTxt('tab_link'), $message);

        extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

        if ($sort === '') {
            $sort = get_pref('link_sort_column', 'name');
        }

        if ($dir === '') {
            $dir = get_pref('link_sort_dir', 'asc');
        }
        $dir = ($dir == 'desc') ? 'desc' : 'asc';

        switch ($sort) {
            case 'id' :
                $sort_sql = 'id '.$dir;
                break;
            case 'description' :
                $sort_sql = 'description '.$dir.', id asc';
                break;
            case 'url' :
                $sort_sql = 'url '.$dir.', id asc';
                break;
            case 'category' :
                $sort_sql = 'category '.$dir.', id asc';
                break;
            case 'date' :
                $sort_sql = 'date '.$dir.', id asc';
                break;
            case 'author' :
                $sort_sql = 'author '.$dir.', id asc';
                break;
            default :
                $sort = 'name';
                $sort_sql = 'linksort '.$dir.', id asc';
                break;
        }

        set_pref('link_sort_column', $sort, 'link', 2, '', 0, PREF_PRIVATE);
        set_pref('link_sort_dir', $dir, 'link', 2, '', 0, PREF_PRIVATE);

        $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

        $criteria = 1;

        if ($search_method and $crit != '') {
            $verbatim = preg_match('/^"(.*)"$/', $crit, $m);
            $crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
            $critsql = $verbatim ?
                array(
                    'id'          => "ID in ('" .join("','", do_list($crit_escaped)). "')",
                    'name'        => "linkname = '$crit_escaped'",
                    'description' => "description = '$crit_escaped'",
                    'url'         => "url = '$crit_escaped'",
                    'category'    => "category = '$crit_escaped'",
                    'author'      => "author = '$crit_escaped'"
                ) : array(
                    'id'          => "ID in ('" .join("','", do_list($crit_escaped)). "')",
                    'name'        => "linkname like '%$crit_escaped%'",
                    'description' => "description like '%$crit_escaped%'",
                    'url'         => "url like '%$crit_escaped%'",
                    'category'    => "category like '%$crit_escaped%'",
                    'author'      => "author like '%$crit_escaped%'"
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

        $criteria .= callback_event('admin_criteria', 'link_list', 0, $criteria);

        $total = getCount('txp_link', $criteria);

        echo hed(gTxt('tab_link'), 1, array('class' => 'txp-heading'));
        echo n.'<div id="'.$event.'_control" class="txp-control-panel">';

        if (has_privs('link.edit')) {
            echo graf(
                sLink('link', 'link_edit', gTxt('add_new_link'))
                , ' class="txp-buttons"');
        }

        if ($total < 1) {
            if ($criteria != 1) {
                echo link_search_form($crit, $search_method).
                    graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
            } else {
                echo graf(gTxt('no_links_recorded'), ' class="indicator"').'</div>';
            }

            return;
        }

        $limit = max($link_list_pageby, 15);

        list($page, $offset, $numPages) = pager($total, $limit, $page);

        echo link_search_form($crit, $search_method).'</div>';

        $rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_link', "$criteria order by $sort_sql limit $offset, $limit");

        if ($rs) {
            $show_authors = !has_single_author('txp_link');

            echo n.'<div id="'.$event.'_container" class="txp-container">';
            echo n.'<form action="index.php" id="links_form" class="multi_edit_form" method="post" name="longform">',

                n.'<div class="txp-listtables">'.
                startTable('', '', 'txp-list').
                n.'<thead>'.
                tr(
                    hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' scope="col" title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
                    column_head('ID', 'id', 'link', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id').
                    column_head('link_name', 'name', 'link', true, $switch_dir, $crit, $search_method, (('name' == $sort) ? "$dir " : '').'name').
                    column_head('description', 'description', 'link', true, $switch_dir, $crit, $search_method, (('description' == $sort) ? "$dir " : '').'links_detail description').
                    column_head('link_category', 'category', 'link', true, $switch_dir, $crit, $search_method, (('category' == $sort) ? "$dir " : '').'category').
                    column_head('url', 'url', 'link', true, $switch_dir, $crit, $search_method, (('url' == $sort) ? "$dir " : '').'url').
                    column_head('date', 'date', 'link', true, $switch_dir, $crit, $search_method, (('date' == $sort) ? "$dir " : '').'links_detail date created').
                    ($show_authors ? column_head('author', 'author', 'link', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '')
                ).
                n.'</thead>';

            echo n.'<tbody>';

            $validator = new Validator();

            while ($a = nextRow($rs)) {
                extract($a, EXTR_PREFIX_ALL, 'link');

                $edit_url = array(
                    'event'         => 'link',
                    'step'          => 'link_edit',
                    'id'            => $link_id,
                    'sort'          => $sort,
                    'dir'           => $dir,
                    'page'          => $page,
                    'search_method' => $search_method,
                    'crit'          => $crit,
                );

                $validator->setConstraints(array(new CategoryConstraint($link_category, array('type' => 'link'))));
                $vc = $validator->validate() ? '' : ' error';

                $can_edit = has_privs('link.edit') || ($link_author === $txp_user && has_privs('link.edit.own'));
                $view_url = txpspecialchars($link_url);

                echo tr(
                    td(
                        fInput('checkbox', 'selected[]', $link_id)
                    , '', 'multi-edit').

                    hCell(
                        ($can_edit ? href($link_id, $edit_url, ' title="'.gTxt('edit').'"') : $link_id)
                    , '', ' scope="row" class="id"').

                    td(
                        ($can_edit ? href(txpspecialchars($link_linkname), $edit_url, ' title="'.gTxt('edit').'"') : txpspecialchars($link_linkname))
                    , '', 'name').

                    td(
                        txpspecialchars($link_description)
                    , '', 'links_detail description').

                    td(
                        span($link_category, array('title' => fetch_category_title($link_category, 'link')))
                    , '', 'category'.$vc).

                    td(
                        href($view_url, $view_url, ' rel="external" target="_blank"')
                    , '', 'url').

                    td(
                        gTime($link_uDate)
                    , '', 'links_detail date created').

                    ($show_authors ? td(
                        span(txpspecialchars($link_author), array('title' => get_author_name($link_author)))
                    , '', 'author') : '')
                );
            }

            echo n.'</tbody>'.
                endTable().
                '</div>'.
                link_multiedit_form($page, $sort, $dir, $crit, $search_method).
                tInput().
                n.'</form>'.
                graf(
                    toggle_box('links_detail'),
                    ' class="detail-toggle"'
                ).
                n.'<div id="'.$event.'_navigation" class="txp-navigation">'.
                pageby_form('link', $link_list_pageby).
                nav_form('link', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
                n.'</div>'.
                n.'</div>';
        }
    }

// -------------------------------------------------------------

    function link_search_form($crit, $method)
    {
        $methods = array(
            'id'          => gTxt('ID'),
            'name'        => gTxt('link_name'),
            'description' => gTxt('description'),
            'url'         => gTxt('url'),
            'category'    => gTxt('link_category'),
            'author'      => gTxt('author'),
        );

        return search_form('link', 'link_list', $crit, $methods, $method, 'name');
    }

// -------------------------------------------------------------

    function link_edit($message = '')
    {
        global $vars, $event, $step, $txp_user;

        pagetop(gTxt('tab_link'), $message);

        echo '<div id="'.$event.'_container" class="txp-container">';

        extract(array_map('assert_string', gpsa($vars)));

        $is_edit = ($id && $step == 'link_edit');

        $rs = array();
        if ($is_edit) {
            $id = assert_int($id);
            $rs = safe_row('*', 'txp_link', "id = $id");
            if ($rs) {
                extract($rs);
                if (!has_privs('link.edit') && !($author === $txp_user && has_privs('link.edit.own'))) {
                    link_list(gTxt('restricted_area'));
                    return;
                }
            }
        }

        if (has_privs('link.edit') || has_privs('link.edit.own')) {
            $caption = gTxt(($is_edit) ? 'edit_link' : 'add_new_link');

            echo form(
                n.'<section class="txp-edit">'.
                hed($caption, 2).
                inputLabel('linkname', fInput('text', 'linkname', $linkname, '', '', '', INPUT_REGULAR, '', 'linkname'), 'title').
                inputLabel('linksort', fInput('text', 'linksort', $linksort, '', '', '', INPUT_REGULAR, '', 'linksort'), 'sort_value', 'link_sort').
                inputLabel('url', fInput('text', 'url', $url, '', '', '', INPUT_REGULAR, '', 'url'), 'url', 'link_url', 'edit-link-url'). /* TODO: maybe use type = 'url' once browsers are less strict */

                inputLabel(
                    'link_category',
                    linkcategory_popup($category).
                    sp.span('[', array('aria-hidden' => 'true')).
                    eLink('category', 'list', '', '', gTxt('edit')).
                    span(']', array('aria-hidden' => 'true'))
                , 'link_category', 'link_category').

                inputLabel('link_description', '<textarea id="link_description" name="description" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_MEDIUM.'">'.txpspecialchars($description).'</textarea>', 'description', 'link_description', '', '').
                pluggable_ui('link_ui', 'extend_detail_form', '', $rs).
                graf(fInput('submit', '', gTxt('save'), 'publish')).
                eInput('link').
                sInput('link_save').
                hInput('id', $id).
                hInput('search_method', gps('search_method')).
                hInput('crit', gps('crit')).
                n.'</section>'
            , '', '', 'post', 'edit-form', '', 'link_details');
        }

        echo '</div>';
    }

//--------------------------------------------------------------

    function linkcategory_popup($cat = '')
    {
        return event_category_popup('link', $cat, 'link_category');
    }

// -------------------------------------------------------------
    function link_save()
    {
        global $vars, $txp_user;

        $varray = array_map('assert_string', gpsa($vars));
        extract(doSlash($varray));

        if ($id) {
            $id = $varray['id'] = assert_int($id);
        }

        if ($linkname === '' && $url === '' && $description === '') {
            link_list(array(gTxt('link_empty'), E_ERROR));
            return;
        }

        $author = fetch('author', 'txp_link', 'id', $id);
        if (!has_privs('link.edit') && !($author === $txp_user && has_privs('link.edit.own'))) {
            link_list(gTxt('restricted_area'));
            return;
        }

        if (!$linksort) {
            $linksort = $linkname;
        }

        $constraints = array(
            'category' => new CategoryConstraint($varray['category'], array('type' => 'link'))
        );

        callback_event_ref('link_ui', 'validate_save', 0, $varray, $constraints);
        $validator = new Validator($constraints);

        if ($validator->validate()) {
            if ($id) {
                $ok = safe_update('txp_link',
                    "category   = '$category',
                    url         = '".trim($url)."',
                    linkname    = '$linkname',
                    linksort    = '$linksort',
                    description = '$description',
                    author      = '".doSlash($txp_user)."'",
                    "id = $id"
                );
            } else {
                $ok = safe_insert('txp_link',
                    "category   = '$category',
                    date        = now(),
                    url         = '".trim($url)."',
                    linkname    = '$linkname',
                    linksort    = '$linksort',
                    description = '$description',
                    author      = '".doSlash($txp_user)."'"
                );
                if ($ok) {
                    $GLOBALS['ID'] = $_POST['id'] = $ok;
                }
            }

            if ($ok) {
                // update lastmod due to link feeds
                update_lastmod();
                $message = gTxt(($id ? 'link_updated' : 'link_created'), array('{name}' => doStrip($linkname)));
            } else {
                $message = array(gTxt('link_save_failed'), E_ERROR);
            }
        } else {
            $message = array(gTxt('link_save_failed'), E_ERROR);
        }

        link_list($message);
    }

// -------------------------------------------------------------
    function link_change_pageby()
    {
        event_change_pageby('link');
        link_list();
    }

// -------------------------------------------------------------

    function link_multiedit_form($page, $sort, $dir, $crit, $search_method)
    {
        global $all_link_cats, $all_link_authors;

        $categories = $all_link_cats ? treeSelectInput('category', $all_link_cats, '') : '';
        $authors = $all_link_authors ? selectInput('author', $all_link_authors, '', true) : '';

        $methods = array(
            'changecategory' => array('label' => gTxt('changecategory'), 'html' => $categories),
            'changeauthor'   => array('label' => gTxt('changeauthor'), 'html' => $authors),
            'delete'         => gTxt('delete'),
        );

        if (!$categories) {
            unset($methods['changecategory']);
        }

        if (has_single_author('txp_link')) {
            unset($methods['changeauthor']);
        }

        if (!has_privs('link.delete.own') && !has_privs('link.delete')) {
            unset($methods['delete']);
        }

        return multi_edit($methods, 'link', 'link_multi_edit', $page, $sort, $dir, $crit, $search_method);
    }

// -------------------------------------------------------------

    function link_multi_edit()
    {
        global $txp_user, $all_link_cats, $all_link_authors;

        // Empty entry to permit clearing the category
        $categories = array('');

        foreach ($all_link_cats as $row) {
            $categories[] = $row['name'];
        }

        $selected = ps('selected');

        if (!$selected or !is_array($selected)) {
            link_list();
            return;
        }

        $selected = array_map('assert_int', $selected);
        $method   = ps('edit_method');
        $changed  = array();
        $key = '';

        switch ($method) {
            case 'delete' :
                if (!has_privs('link.delete')) {
                    if (has_privs('link.delete.own')) {
                        $selected = safe_column('id', 'txp_link', 'id IN ('.join(',', $selected).') AND author=\''.doSlash($txp_user).'\'' );
                    } else {
                        $selected = array();
                    }
                }
                foreach ($selected as $id) {
                    if (safe_delete('txp_link', 'id = '.$id)) {
                        $changed[] = $id;
                    }
                }

                if ($changed) {
                    callback_event('links_deleted', '', 0, $changed);
                }

                $key = '';
                break;
            case 'changecategory' :
                $val = ps('category');
                if (in_array($val, $categories)) {
                    $key = 'category';
                }
                break;
            case 'changeauthor' :
                $val = ps('author');
                if (in_array($val, $all_link_authors)) {
                    $key = 'author';
                }
                break;
            default :
                $key = '';
                $val = '';
                break;
        }

        if ($selected and $key) {
            foreach ($selected as $id) {
                if (safe_update('txp_link', "$key = '".doSlash($val)."'", "id = $id")) {
                    $changed[] = $id;
                }
            }
        }

        if ($changed) {
            update_lastmod();

            link_list(gTxt(
                ($method == 'delete' ? 'links_deleted' : 'link_updated'),
                array(($method == 'delete' ? '{list}' : '{name}') => join(', ', $changed))));
            return;
        }

        link_list();
    }

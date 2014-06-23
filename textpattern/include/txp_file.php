<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2004 Dean Allen
 * Copyright (C) 2014 The Textpattern Development Team
 *
 * "Mod File Upload" by Michael Manfre
 * http://manfre.net
 *
 * Copyright (C) 2004 Michael Manfre
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

$levels = array(
    1 => gTxt('private'),
    0 => gTxt('public'),
);

global $file_statuses;
$file_statuses = status_list(true, array(STATUS_DRAFT, STATUS_STICKY));

if ($event == 'file') {
    require_privs('file');

    global $all_file_cats, $all_file_authors;
    $all_file_cats = getTree('root', 'file');
    $all_file_authors = the_privileged('file.edit.own');

    $available_steps = array(
        'file_change_pageby' => true,
        'file_multi_edit'    => true,
        'file_edit'          => false,
        'file_insert'        => true,
        'file_list'          => false,
        'file_replace'       => true,
        'file_save'          => true,
        'file_create'        => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        file_list();
    }
}

// -------------------------------------------------------------

function file_list($message = '')
{
    global $file_base_path, $file_statuses, $file_list_pageby, $txp_user, $event;

    pagetop(gTxt('tab_file'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('file_sort_column', 'filename');
    }

    if ($dir === '') {
        $dir = get_pref('file_sort_dir', 'asc');
    }

    if ($dir === 'desc') {
        $dir = 'desc';
    } else {
        $dir = 'asc';
    }

    echo
        hed(gTxt('tab_file'), 1, array('class' => 'txp-heading')).
        n.tag_start('div', array('id' => $event.'_control', 'class' => 'txp-control-panel'));

    if (!is_dir($file_base_path) || !is_writeable($file_base_path)) {
        echo graf(
            span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
            gTxt('file_dir_not_writeable', array('{filedir}' => $file_base_path)),
            array('class' => 'alert-block warning')
        );
    } elseif (has_privs('file.edit.own')) {
        $existing_files = get_filenames();

        if ($existing_files) {
            echo form(
                eInput('file').
                sInput('file_create').
                graf(
                    tag(gTxt('existing_file'), 'label', array('for' => 'file-existing')).
                    sp.selectInput('filename', $existing_files, '', 1, '', 'file-existing').
                    sp.fInput('submit', '', gTxt('Create')),
                    array('class' => 'existing-file')
                )
            , '', '', 'post', '', '', 'assign_file');
        }

        echo file_upload_form(gTxt('upload_file'), 'upload', 'file_insert');
    }

    switch ($sort) {
        case 'id' :
            $sort_sql = 'txp_file.id '.$dir;
            break;
        case 'description' :
            $sort_sql = 'txp_file.description '.$dir.', txp_file.filename desc';
            break;
        case 'category' :
            $sort_sql = 'txp_category.title '.$dir.', txp_file.filename desc';
            break;
        case 'title' :
            $sort_sql = 'txp_file.title '.$dir.', txp_file.filename desc';
            break;
        case 'downloads' :
            $sort_sql = 'txp_file.downloads '.$dir.', txp_file.filename desc';
            break;
        case 'author' :
            $sort_sql = 'txp_users.RealName '.$dir.', txp_file.id asc';
            break;
        default :
            $sort = 'filename';
            $sort_sql = 'txp_file.filename '.$dir;
            break;
    }

    set_pref('file_sort_column', $sort, 'file', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    set_pref('file_sort_dir', $dir, 'file', PREF_HIDDEN, '', 0, PREF_PRIVATE);

    if ($dir == 'desc') {
        $switch_dir = 'asc';
    } else {
        $switch_dir = 'desc';
    }

    $criteria = 1;

    if ($search_method && $crit !== '') {
        $verbatim = preg_match('/^"(.*)"$/', $crit, $m);
        $crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
        $critsql = $verbatim ?
            array(
                'id'          => "txp_file.id in ('" .join("','", do_list($crit_escaped)). "')",
                'filename'    => "txp_file.filename = '$crit_escaped'",
                'title'       => "txp_file.title = '$crit_escaped'",
                'description' => "txp_file.description = '$crit_escaped'",
                'category'    => "txp_file.category = '$crit_escaped' or txp_category.title = '$crit_escaped'",
                'author'      => "txp_file.author = '$crit_escaped' or txp_users.RealName = '$crit_escaped'"
            ) :    array(
                'id'          => "txp_file.id in ('" .join("','", do_list($crit_escaped)). "')",
                'filename'    => "txp_file.filename like '%$crit_escaped%'",
                'title'       => "txp_file.title like '%$crit_escaped%'",
                'description' => "txp_file.description like '%$crit_escaped%'",
                'category'    => "txp_file.category like '%$crit_escaped%' or txp_category.title like '%$crit_escaped%'",
                'author'      => "txp_file.author like '%$crit_escaped%' or txp_users.RealName like '%$crit_escaped%'"
            );

        if (array_key_exists($search_method, $critsql)) {
            $criteria = $critsql[$search_method];
            $limit = 500;
        } else {
            $search_method = '';
            $crit = '';
        }
    } else {
        $search_method = '';
        $crit = '';
    }

    $criteria .= callback_event('admin_criteria', 'file_list', 0, $criteria);

    $sql_from =
        safe_pfx_j('txp_file')."
        left join ".safe_pfx_j('txp_category')." on txp_category.name = txp_file.category and txp_category.type = 'file'
        left join ".safe_pfx_j('txp_users')." on txp_users.name = txp_file.author";

    if ($criteria === 1) {
        $total = safe_count('txp_file', $criteria);
    } else {
        $total = getThing('select count(*) from '.$sql_from.' where '.$criteria);
    }

    if ($total < 1) {
        if ($criteria != 1) {
            echo file_search_form($crit, $search_method).
                graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
        } else {
            echo graf(gTxt('no_files_recorded'), ' class="indicator"').'</div>';
        }

        return;
    }

    $limit = max($file_list_pageby, 15);

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    echo file_search_form($crit, $search_method).'</div>';

    $rs = safe_query(
        "select
            txp_file.id,
            txp_file.filename,
            txp_file.title,
            txp_file.category,
            txp_file.description,
            txp_file.downloads,
            txp_file.status,
            txp_file.author,
            txp_users.RealName as realname,
            txp_category.Title as category_title
        from $sql_from where $criteria order by $sort_sql limit $offset, $limit"
    );

    if ($rs && numRows($rs)) {
        $show_authors = !has_single_author('txp_file');

        echo
            n.tag_start('div', array(
                'id'    => $event.'_container',
                'class' => 'txp-container',
            )).
            n.tag_start('form', array(
                'action' => 'index.php',
                'id'     => 'files_form',
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
                    'ID', 'id', 'file', true, $switch_dir, $crit, $search_method,
                        (('id' == $sort) ? "$dir " : '').'txp-list-col-id'
                ).
                column_head(
                    'file_name', 'filename', 'file', true, $switch_dir, $crit, $search_method,
                        (('filename' == $sort) ? "$dir " : '').'txp-list-col-filename'
                ).
                column_head(
                    'title', 'title', 'file', true, $switch_dir, $crit, $search_method,
                        (('title' == $sort) ? "$dir " : '').'txp-list-col-title'
                ).
                column_head(
                    'description', 'description', 'file', true, $switch_dir, $crit, $search_method,
                        (('description' == $sort) ? "$dir " : '').'txp-list-col-description files_detail'
                ).
                column_head(
                    'file_category', 'category', 'file', true, $switch_dir, $crit, $search_method,
                        (('category' == $sort) ? "$dir " : '').'txp-list-col-category category'
                ).
                hCell(gTxt(
                    'tags'), '', ' scope="col" class="txp-list-col-tag-build files_detail"'
                ).
                hCell(gTxt(
                    'status'), '', ' scope="col" class="txp-list-col-status"'
                ).
                hCell(gTxt(
                    'condition'), '', ' scope="col" class="txp-list-col-condition"'
                ).
                column_head(
                    'downloads', 'downloads', 'file', true, $switch_dir, $crit, $search_method,
                        (('downloads' == $sort) ? "$dir " : '').'txp-list-col-downloads'
                ).
                (
                    $show_authors
                    ? column_head('author', 'author', 'file', true, $switch_dir, $crit, $search_method,
                        (('author' == $sort) ? "$dir " : '').'txp-list-col-author name')
                    : ''
                )
            ).
            n.tag_end('thead').
            n.tag_start('tbody');

        $validator = new Validator();

        while ($a = nextRow($rs)) {
            extract($a);
            $filename = sanitizeForFile($filename);

            $edit_url = array(
                'event'         => 'file',
                'step'          => 'file_edit',
                'id'            => $id,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
            );

            $tag_url = array(
                'event'       => 'tag',
                'tag_name'    => 'file_download_link',
                'id'          => $id,
                'description' => $description,
                'filename'    => $filename,
            );

            $file_exists = file_exists(build_file_path($file_base_path, $filename));
            $can_edit = has_privs('file.edit') || ($author === $txp_user && has_privs('file.edit.own'));
            $validator->setConstraints(array(new CategoryConstraint($category, array('type' => 'file'))));

            if ($validator->validate()) {
                $vc = '';
            } else {
                $vc = ' error';
            }

            if ($file_exists) {
                $downloads = make_download_link($id, $downloads, $filename);
                $condition = span(gTxt('file_status_ok'), array('class' => 'success'));
            } else {
                $condition = span(gTxt('file_status_missing'), array('class' => 'error'));
            }

            if ($category) {
                $category = span(txpspecialchars($category_title), array('title' => $category));
            }

            if ($can_edit) {
                $name = href(txpspecialchars($filename), $edit_url, array('title' => gTxt('edit')));
            } else {
                $name = txpspecialchars($filename);
            }

            if ($can_edit) {
                $id_column = href($id, $edit_url, array('title' => gTxt('edit')));
                $multi_edit = fInput('checkbox', 'selected[]', $id);
            } else {
                $id_column = $id;
                $multi_edit = '';
            }

            if ($file_exists) {
                $id_column .=
                    sp.span('[', array('aria-hidden' => 'true')).
                    make_download_link($id, gTxt('download'), $filename).
                    span(']', array('aria-hidden' => 'true'));
            }

            if (isset($file_statuses[$status])) {
                $status = $file_statuses[$status];
            } else {
                $status = span(gTxt('none'), array('class' => 'error'));
            }

            echo tr(
                td(
                    $multi_edit, '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    $id_column, '', array('scope' => 'row', 'class' => 'txp-list-col-id')
                ).
                td(
                    $name, '', 'txp-list-col-filename'
                ).
                td(
                    txpspecialchars($title), '', 'txp-list-col-title'
                ).
                td(
                    txpspecialchars($description), '', 'txp-list-col-description files_detail'
                ).
                td(
                    $category, '', 'txp-list-col-category category'.$vc
                ).
                td(
                    href('Textile', $tag_url + array('type' => 'textile'), ' target="_blank" onclick="popWin(this.href); return false;"').
                    sp.span('&#124;', array('role' => 'separator')).
                    sp.href('Textpattern', $tag_url + array('type' => 'textpattern'), ' target="_blank" onclick="popWin(this.href); return false;"').
                    sp.span('&#124;', array('role' => 'separator')).
                    sp.href('HTML', $tag_url + array('type' => 'html'), ' target="_blank" onclick="popWin(this.href); return false;"')
                , '', 'txp-list-col-tag-build files_detail').

                td(
                    $status, '', 'txp-list-col-status'
                ).
                td(
                    $condition, '', 'txp-list-col-condition'
                ).
                td(
                    $downloads, '', 'txp-list-col-downloads'
                ).
                (
                    $show_authors
                    ? td(span(txpspecialchars($realname), array('title' => $author)), '', 'txp-list-col-author name')
                    : ''
                )
            );
        }

        echo
            n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div').
            file_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            graf(toggle_box('files_detail'), array('class' => 'detail-toggle')).
            n.tag_start('div', array(
                'id'    => $event.'_navigation',
                'class' => 'txp-navigation',
            )).
            pageby_form('file', $file_list_pageby).
            nav_form('file', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div').
            n.tag_end('div');
    }
}

// -------------------------------------------------------------

function file_search_form($crit, $method)
{
    $methods = array(
        'id'          => gTxt('ID'),
        'filename'    => gTxt('file_name'),
        'title'       => gTxt('title'),
        'description' => gTxt('description'),
        'category'    => gTxt('file_category'),
        'author'      => gTxt('author'),
    );

    return search_form('file', 'file_list', $crit, $methods, $method, 'filename');
}

// -------------------------------------------------------------

function file_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $file_statuses, $all_file_cats, $all_file_authors;

    $categories = $all_file_cats ? treeSelectInput('category', $all_file_cats, '') : '';
    $authors = $all_file_authors ? selectInput('author', $all_file_authors, '', true) : '';
    $status = selectInput('status', $file_statuses, '', true);

    $methods = array(
        'changecategory' => array('label' => gTxt('changecategory'), 'html' => $categories),
        'changeauthor'   => array('label' => gTxt('changeauthor'), 'html' => $authors),
        'changestatus'   => array('label' => gTxt('changestatus'), 'html' => $status),
        'changecount'    => array('label' => gTxt('reset_download_count')),
        'delete'         => gTxt('delete'),
    );

    if (!$categories) {
        unset($methods['changecategory']);
    }

    if (has_single_author('txp_file')) {
        unset($methods['changeauthor']);
    }

    if (!has_privs('file.delete.own') && !has_privs('file.delete')) {
        unset($methods['delete']);
    }

    return multi_edit($methods, 'file', 'file_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

// -------------------------------------------------------------

function file_multi_edit()
{
    global $txp_user, $all_file_cats, $all_file_authors;

    // Empty entry to permit clearing the category
    $categories = array('');

    foreach ($all_file_cats as $row) {
        $categories[] = $row['name'];
    }

    $selected = ps('selected');

    if (!$selected or !is_array($selected)) {
        return file_list();
    }

    $selected = array_map('assert_int', $selected);
    $method   = ps('edit_method');
    $changed  = array();
    $key = '';

    switch ($method) {
        case 'delete' :
            return file_delete($selected);
            break;
        case 'changecategory' :
            $val = ps('category');
            if (in_array($val, $categories)) {
                $key = 'category';
            }
            break;
        case 'changeauthor' :
            $val = ps('author');
            if (in_array($val, $all_file_authors)) {
                $key = 'author';
            }
            break;
        case 'changecount' :
            $key = 'downloads';
            $val = 0;
            break;
        case 'changestatus' :
            $key = 'status';
            $val = ps('status');

            // do not allow to be set to an empty value
            if (!$val) {
                $selected = array();
            }
            break;
        default :
            $key = '';
            $val = '';
            break;
    }

    if (!has_privs('file.edit')) {
        if (has_privs('file.edit.own')) {
            $selected = safe_column('id', 'txp_file', 'id IN ('.join(',', $selected).') AND author=\''.doSlash($txp_user).'\'');
        } else {
            $selected = array();
        }
    }

    if ($selected and $key) {
        foreach ($selected as $id) {
            if (safe_update('txp_file', "$key = '".doSlash($val)."'", "id = $id")) {
                $changed[] = $id;
            }
        }
    }

    if ($changed) {
        update_lastmod();

        return file_list(gTxt('file_updated', array('{name}' => join(', ', $changed))));
    }

    return file_list();
}

// -------------------------------------------------------------

function file_edit($message = '', $id = '')
{
    global $file_base_path, $levels, $file_statuses, $txp_user, $event, $all_file_cats;

    extract(gpsa(array(
        'name',
        'title',
        'category',
        'permissions',
        'description',
        'sort',
        'dir',
        'page',
        'crit',
        'search_method',
        'publish_now',
    )));

    if (!$id) {
        $id = gps('id');
    }
    $id = assert_int($id);

    $rs = safe_row('*, unix_timestamp(created) as created, unix_timestamp(modified) as modified', 'txp_file', "id = $id");

    if ($rs) {
        extract($rs);
        $filename = sanitizeForFile($filename);

        if (!has_privs('file.edit') && !($author === $txp_user && has_privs('file.edit.own'))) {
            require_privs();
        }

        pagetop(gTxt('edit_file'), $message);

        if ($permissions=='') {
            $permissions='-1';
        }

        if (!has_privs('file.publish') && $status >= STATUS_LIVE) {
            $status = STATUS_PENDING;
        }

        $file_exists = file_exists(build_file_path($file_base_path, $filename));
        $existing_files = get_filenames();

        $replace = ($file_exists)
            ? wrapGroup('file_upload_group', file_upload_form('', '', 'file_replace', $id, 'file_replace'), 'replace_file', 'replace-file', 'file_replace')
            : wrapGroup('file_upload_group', file_upload_form('', '', 'file_replace', $id, 'file_reassign'), 'file_relink', 'upload-file', 'file_reassign');

        $condition = span((($file_exists)
                ? gTxt('file_status_ok')
                : gTxt('file_status_missing')
            ), array('class' => (($file_exists) ? 'success' : 'error')));

        $downloadlink = ($file_exists) ? make_download_link($id, txpspecialchars($filename), $filename) : txpspecialchars($filename);

        $created =
                graf(
                    checkbox('publish_now', '1', $publish_now, '', 'publish_now').
                    n.'<label for="publish_now">'.gTxt('set_to_now').'</label>'
                , ' class="edit-file-publish-now"'
                ).

                graf(gTxt('or_publish_at').popHelp('timestamp'), ' class="edit-file-publish-at"').

                graf(
                    span(gTxt('date'), array('class' => 'txp-label-fixed')).br.
                    tsi('year', '%Y', $rs['created'], '', gTxt('yyyy')).' / '.
                    tsi('month', '%m', $rs['created'], '', gTxt('mm')).' / '.
                    tsi('day', '%d', $rs['created'], '', gTxt('dd'))
                , ' class="edit-file-published"'
                ).

                graf(
                    span(gTxt('time'), array('class' => 'txp-label-fixed')).br.
                    tsi('hour', '%H', $rs['created'], '', gTxt('hh')).' : '.
                    tsi('minute', '%M', $rs['created'], '', gTxt('mm')).' : '.
                    tsi('second', '%S', $rs['created'], '', gTxt('ss'))
                , ' class="edit-file-created"'
                );

        echo n.'<div id="'.$event.'_container" class="txp-container">';
        echo n.'<section class="txp-edit">'.
            hed(gTxt('edit_file'), 2).
            inputLabel('condition', $condition).
            inputLabel('name', $downloadlink).
            inputLabel('download_count', $downloads).
            $replace.
            n.'<div class="file-detail '.($file_exists ? '' : 'not-').'exists">'.
            form(
                (($file_exists)
                ? inputLabel('file_status', selectInput('status', $file_statuses, $status, false, '', 'file_status'), 'file_status').
                    inputLabel('file_title', fInput('text', 'title', $title, '', '', '', INPUT_REGULAR, '', 'file_title'), 'title').
                    inputLabel('file_category', treeSelectInput('category', $all_file_cats, $category, 'file_category'), 'file_category').
//                    inputLabel('perms', selectInput('perms', $levels, $permissions), 'permissions').
                    inputLabel('file_description', '<textarea id="file_description" name="description" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.$description.'</textarea>', 'description', '', '', '').
                    wrapRegion('file_created', $created, '', gTxt('timestamp'), '', 'file-created').
                    pluggable_ui('file_ui', 'extend_detail_form', '', $rs).
                    graf(fInput('submit', '', gTxt('Save'), 'publish')).
                    hInput('filename', $filename)
                : (empty($existing_files)
                        ? ''
                        : gTxt('existing_file').selectInput('filename', $existing_files, '', 1)
                    ).
                    pluggable_ui('file_ui', 'extend_detail_form', '', $rs).
                    graf(fInput('submit', '', gTxt('Save'), 'publish')).
                    hInput('category', $category).
                    hInput('perms', ($permissions=='-1') ? '' : $permissions).
                    hInput('title', $title).
                    hInput('description', $description).
                    hInput('status', $status)
                ).
                eInput('file').
                sInput('file_save').
                hInput('id', $id).
                hInput('sort', $sort).
                hInput('dir', $dir).
                hInput('page', $page).
                hInput('crit', $crit).
                hInput('search_method', $search_method)
            , '', '', 'post', 'edit-form', '', (($file_exists) ? 'file_details' : 'assign_file')).
            n.'</div>'.n.'</section>'.n.'</div>';
    }
}

// -------------------------------------------------------------

function file_db_add($filename, $category, $permissions, $description, $size, $title='')
{
    global $txp_user;
    $rs = safe_insert("txp_file",
        "filename = '$filename',
         title = '$title',
         category = '$category',
         permissions = '$permissions',
         description = '$description',
         size = '$size',
         created = now(),
         modified = now(),
         author = '".doSlash($txp_user)."'
    ");

    if ($rs) {
        $GLOBALS['ID'] = $rs;

        return $GLOBALS['ID'];
    }

    return false;
}

// -------------------------------------------------------------

function file_create()
{
    global $txp_user, $file_base_path;

    require_privs('file.edit.own');

    extract(doSlash(array_map('assert_string', gpsa(array(
        'filename',
        'title',
        'category',
        'permissions',
        'description',
    )))));

    $safe_filename = sanitizeForFile($filename);
    if ($safe_filename != $filename) {
        file_list(array(gTxt('invalid_filename'), E_ERROR));

        return;
    }

    $size = filesize(build_file_path($file_base_path, $safe_filename));
    $id = file_db_add($safe_filename, $category, $permissions, $description, $size, $title);

    if ($id === false) {
        file_list(array(gTxt('file_upload_failed').' (db_add)', E_ERROR));
    } else {
        $newpath = build_file_path($file_base_path, $safe_filename);

        if (is_file($newpath)) {
            file_set_perm($newpath);
            update_lastmod();
            file_list(gTxt('linked_to_file').' '.$safe_filename);
        } else {
            file_list(gTxt('file_not_found').' '.$safe_filename);
        }
    }
}

// -------------------------------------------------------------

function file_insert()
{
    global $txp_user, $file_base_path, $file_max_upload_size;

    require_privs('file.edit.own');

    extract(doSlash(array_map('assert_string', gpsa(array(
        'category',
        'title',
        'permissions',
        'description',
    )))));

    $name = file_get_uploaded_name();
    $file = file_get_uploaded();

    if ($file === false) {
        // Could not get uploaded file.
        file_list(array(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg($_FILES['thefile']['error']), E_ERROR));

        return;
    }

    $size = filesize($file);
    if ($file_max_upload_size < $size) {
        unlink($file);
        file_list(array(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg(UPLOAD_ERR_FORM_SIZE), E_ERROR));

        return;
    }

    $newname = sanitizeForFile($name);
    $newpath = build_file_path($file_base_path, $newname);

    if (!is_file($newpath) && !safe_count('txp_file', "filename = '".doSlash($newname)."'")) {
        $id = file_db_add(doSlash($newname), $category, $permissions, $description, $size, $title);

        if (!$id) {
            file_list(array(gTxt('file_upload_failed').' (db_add)', E_ERROR));
        } else {
            $id = assert_int($id);

            if (!shift_uploaded_file($file, $newpath)) {
                safe_delete("txp_file", "id = $id");
                safe_alter("txp_file", "auto_increment=$id");

                if (isset( $GLOBALS['ID'])) {
                    unset( $GLOBALS['ID']);
                }

                file_list(array($newpath.' '.gTxt('upload_dir_perms'), E_ERROR));
                // Clean up file.
            } else {
                file_set_perm($newpath);
                update_lastmod();
                file_edit(gTxt('file_uploaded', array('{name}' => $newname)), $id);
            }
        }
    } else {
        file_list(array(gTxt('file_already_exists', array('{name}' => $newname)), E_ERROR));
    }
}

// -------------------------------------------------------------

function file_replace()
{
    global $txp_user, $file_base_path;

    $id = assert_int(gps('id'));

    $rs = safe_row('filename, author', 'txp_file', "id = $id");

    if (!$rs) {
        file_list(array(messenger(gTxt('invalid_id'), $id), E_ERROR));

        return;
    }

    extract($rs);
    $filename = sanitizeForFile($filename);

    if (!has_privs('file.edit') && !($author === $txp_user && has_privs('file.edit.own'))) {
        require_privs();
    }

    $file = file_get_uploaded();
    $name = file_get_uploaded_name();

    if ($file === false) {
        // Could not get uploaded file.
        file_list(array(gTxt('file_upload_failed') ." $name ".upload_get_errormsg($_FILES['thefile']['error']), E_ERROR));

        return;
    }

    if (!$filename) {
        file_list(array(gTxt('invalid_filename'), E_ERROR));
    } else {
        $newpath = build_file_path($file_base_path, $filename);

        if (is_file($newpath)) {
            rename($newpath, $newpath.'.tmp');
        }

        if (!shift_uploaded_file($file, $newpath)) {
            safe_delete("txp_file", "id = $id");

            file_list(array($newpath.sp.gTxt('upload_dir_perms'), E_ERROR));

            // Rename tmp back.
            rename($newpath.'.tmp', $newpath);

            // Remove tmp upload.
            unlink($file);
        } else {
            file_set_perm($newpath);
            update_lastmod();
            if ($size = filesize($newpath)) {
                safe_update('txp_file', 'size = '.$size.', modified = now()', 'id = '.$id);
            }

            file_edit(gTxt('file_uploaded', array('{name}' => $name)), $id);

            // Clean up old.
            if (is_file($newpath.'.tmp')) {
                unlink($newpath.'.tmp');
            }
        }
    }
}

// -------------------------------------------------------------

function file_save()
{
    global $file_base_path, $file_statuses, $txp_user;

    $varray = array_map('assert_string', gpsa(array(
        'id',
        'category',
        'title',
        'description',
        'status',
        'publish_now',
        'year',
        'month',
        'day',
        'hour',
        'minute',
        'second',
    )));

    extract(doSlash($varray));
    $filename = $varray['filename'] = sanitizeForFile(gps('filename'));

    if ($filename == '') {
        file_list(array(gTxt('file_not_updated', array('{name}' => $filename)), E_ERROR));

        return;
    }

    $id = $varray['id'] = assert_int($id);

    $permissions = gps('perms');
    if (is_array($permissions)) {
        asort($permissions);
        $permissions = implode(",", $permissions);
    }
    $varray['permissions'] = $permissions;
    $perms = doSlash($permissions);

    $rs = safe_row('filename, author', 'txp_file', "id=$id");
    if (!has_privs('file.edit') && !($rs['author'] === $txp_user && has_privs('file.edit.own'))) {
        require_privs();
    }

    $old_filename = $varray['old_filename'] = sanitizeForFile($rs['filename']);
    if ($old_filename != false && strcmp($old_filename, $filename) != 0) {
        $old_path = build_file_path($file_base_path, $old_filename);
        $new_path = build_file_path($file_base_path, $filename);

        if (file_exists($old_path) && shift_uploaded_file($old_path, $new_path) === false) {
            file_list(array(gTxt('file_cannot_rename', array('{name}' => $filename)), E_ERROR));

            return;
        } else {
            file_set_perm($new_path);
        }
    }

    $created_ts = @safe_strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
    if ($publish_now) {
        $created = 'now()';
    } elseif ($created_ts > 0) {
        $created = "from_unixtime('".$created_ts."')";
    } else {
        $created = '';
    }

    $size = filesize(build_file_path($file_base_path, $filename));

    $constraints = array(
        'category' => new CategoryConstraint(gps('category'), array('type' => 'file')),
        'status'   => new ChoiceConstraint(gps('status'), array('choices' => array_keys($file_statuses), 'message' => 'invalid_status'))
    );
    callback_event_ref('file_ui', 'validate_save', 0, $varray, $constraints);
    $validator = new Validator($constraints);

    $rs = $validator->validate() && safe_update('txp_file', "
        filename = '".doSlash($filename)."',
        title = '$title',
        category = '$category',
        permissions = '$perms',
        description = '$description',
        status = '$status',
        size = '$size',
        modified = now()"
        .($created ? ", created = $created" : '')
    , "id = $id");

    if (!$rs) {
        // update failed, rollback name
        if (isset($old_path) && shift_uploaded_file($new_path, $old_path) === false) {
            file_list(array(gTxt('file_unsynchronized', array('{name}' => $filename)), E_ERROR));

            return;
        } else {
            file_list(array(gTxt('file_not_updated', array('{name}' => $filename)), E_ERROR));

            return;
        }
    }

    update_lastmod();
    file_list(gTxt('file_updated', array('{name}' => $filename)));
}

// -------------------------------------------------------------

function file_delete($ids = array())
{
    global $file_base_path, $txp_user;

    $ids  = $ids ? array_map('assert_int', $ids) : array(assert_int(ps('id')));

    if (!has_privs('file.delete')) {
        if (has_privs('file.delete.own')) {
            $ids = safe_column('id', 'txp_file', 'id IN ('.join(',', $ids).') AND author=\''.doSlash($txp_user).'\'' );
        } else {
            $ids = array();
        }
    }

    if (!empty($ids)) {
        $fail = array();

        $rs = safe_rows_start('id, filename', 'txp_file', 'id IN ('.join(',', $ids).')');

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);

                $filepath = build_file_path($file_base_path, $filename);

                // Notify plugins of pending deletion, pass file's id and path
                callback_event('file_deleted', '', false, $id, $filepath);

                $rsd = safe_delete('txp_file', "id = $id");
                $ul  = false;

                if ($rsd && is_file($filepath)) {
                    $ul = unlink($filepath);
                }

                if (!$rsd or !$ul) {
                    $fail[] = $id;
                }
            }
            if ($fail) {
                file_list(array(messenger(gTxt('file_delete_failed'), join(', ', $fail)), E_ERROR));

                return;
            } else {
                update_lastmod();
                file_list(gTxt('file_deleted', array('{name}' => join(', ', $ids))));

                return;
            }
        } else {
            file_list(array(messenger(gTxt('file_not_found'), join(', ', $ids), ''), E_ERROR));

            return;
        }
    }
    file_list();
}

// -------------------------------------------------------------

function file_get_uploaded_name()
{
    return $_FILES['thefile']['name'];
}

// -------------------------------------------------------------

function file_get_uploaded()
{
    return get_uploaded_file($_FILES['thefile']['tmp_name']);
}

// -------------------------------------------------------------

function file_set_perm($file)
{
    return @chmod($file, 0644);
}

// -------------------------------------------------------------

function file_upload_form($label, $pophelp, $step, $id='', $label_id='')
{
    global $file_max_upload_size;

    if (!$file_max_upload_size || intval($file_max_upload_size) == 0) $file_max_upload_size = 2 * (1024 * 1024);

    $max_file_size = (intval($file_max_upload_size) == 0) ? '': intval($file_max_upload_size);

    return upload_form($label, $pophelp, $step, 'file', $id, $max_file_size, $label_id);
}

// -------------------------------------------------------------

function file_change_pageby()
{
    event_change_pageby('file');
    file_list();
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
 *
 * "Mod File Upload" by Michael Manfre
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Files panel.
 *
 * @package Admin\File
 */

use Textpattern\Validator\CategoryConstraint;
use Textpattern\Validator\ChoiceConstraint;
use Textpattern\Validator\Validator;
use Textpattern\Search\Filter;

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
    $all_file_authors = the_privileged('file.edit.own', true);

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

/**
 * The main panel listing all files.
 *
 * @param string|array $message The activity message
 */

function file_list($message = '', $ids = array())
{
    global $file_base_path, $file_statuses, $txp_user, $event;

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
    } else {
        if (!in_array($sort, array('id', 'category', 'title', 'date', 'downloads', 'author', 'size'))) {
            $sort = 'filename';
        }

        set_pref('file_sort_column', $sort, 'file', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('file_sort_dir', 'asc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('file_sort_dir', $dir, 'file', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    switch ($sort) {
        case 'id':
            $sort_sql = "txp_file.id $dir";
            break;
        case 'date':
            $sort_sql = "txp_file.created $dir, txp_file.id ASC";
            break;
        case 'category':
            $sort_sql = "txp_category.title $dir, txp_file.filename DESC";
            break;
        case 'title':
            $sort_sql = "txp_file.title $dir, txp_file.filename DESC";
            break;
        case 'size':
            $sort_sql = "txp_file.size $dir, txp_file.id ASC";
            break;
        case 'downloads':
            $sort_sql = "txp_file.downloads $dir, txp_file.filename DESC";
            break;
        case 'author':
            $sort_sql = "txp_users.RealName $dir, txp_file.id ASC";
            break;
        default:
            $sort = 'filename';
            $sort_sql = "txp_file.filename $dir";
            break;
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'id' => array(
                'column' => 'txp_file.id',
                'label'  => gTxt('id'),
                'type'   => 'integer',
            ),
            'filename' => array(
                'column' => 'txp_file.filename',
                'label'  => gTxt('name'),
            ),
            'title' => array(
                'column' => 'txp_file.title',
                'label'  => gTxt('title'),
            ),
            'description' => array(
                'column' => 'txp_file.description',
                'label'  => gTxt('description'),
            ),
            'category' => array(
                'column' => array('txp_file.category', 'txp_category.title'),
                'label'  => gTxt('category'),
            ),
            'status' => array(
                'column' => array('txp_file.status'),
                'label'  => gTxt('status'),
                'type'   => 'boolean',
            ),
            'author' => array(
                'column' => array('txp_file.author', 'txp_users.RealName'),
                'label'  => gTxt('author'),
            ),
        )
    );

    $search->setAliases('status', $file_statuses);

    list($criteria, $crit, $search_method) = $search->getFilter(array('id' => array('can_list' => true)));

    $search_render_options = array('placeholder' => 'search_files');

    $sql_from =
        safe_pfx_j('txp_file')."
        LEFT JOIN ".safe_pfx_j('txp_category')." ON txp_category.name = txp_file.category AND txp_category.type = 'file'
        LEFT JOIN ".safe_pfx_j('txp_users')." ON txp_users.name = txp_file.author";

    if ($crit === '') {
        $total = safe_count('txp_file', $criteria);
    } else {
        $total = getThing("SELECT COUNT(*) FROM $sql_from WHERE $criteria");
    }

    $searchBlock =
        n.tag(
            $search->renderForm('file_list', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
                'style' => $total || $crit === '' ? false : 'display:none',
            )
        );

    $createBlock = array();

    if (!is_dir($file_base_path) || !is_writeable($file_base_path)) {
        $createBlock[] =
            graf(
                span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                gTxt('file_dir_not_writeable', array('{filedir}' => $file_base_path)),
                array('class' => 'alert-block warning')
            );
    } elseif (has_privs('file.edit.own')) {
        $categories = event_category_popup('file', '', 'file_category');
        $createBlock[] =
            n.tag_start('div', array('class' => 'txp-control-panel')).
            n.file_upload_form('upload_file', 'upload', 'file_insert[]', '', '', 'async', '',
                array('postinput' => ($categories
                    ? n.tag(
                        n.tag(gTxt('category'), 'label', array('for' => 'file_category')).$categories.n,
                        'span',
                        array('class' => 'inline-file-uploader-actions'))
                    : ''
                ))
            );

        if ($existing_files = get_filenames()) {
            $selected = count($existing_files) >= 5 ? array() : null;
            $createBlock[] =
                form(
                    eInput('file').
                    sInput('file_create').
                    tag(gTxt('existing_file'), 'label', array('for' => 'file-existing')).
                    selectInput('filename', $existing_files, $selected, false, '', 'file-existing').
                    fInput('submit', '', gTxt('import')),
                '', '', 'post', 'assign-existing-form', '', 'assign_file');
        }

        $createBlock[] = tag_end('div');
    }

    $createBlock = implode(n, $createBlock);
    $contentBlock = '';

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $paginator->getLimit();

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    if ($total < 1) {
        $contentBlock .= graf(
            span(null, array('class' => 'ui-icon ui-icon-info')).' '.
            gTxt($crit === '' ? 'no_files_recorded' : 'no_results_found'),
            array('class' => 'alert-block information')
        );
    } else {
        $rs = safe_query(
            "SELECT
                txp_file.id,
                txp_file.filename,
                txp_file.title,
                txp_file.category,
                txp_file.description,
                UNIX_TIMESTAMP(txp_file.created) AS uDate,
                txp_file.downloads,
                txp_file.status,
                txp_file.author,
                txp_file.size,
                txp_users.RealName AS realname,
                txp_category.Title AS category_title
            FROM $sql_from WHERE $criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        if ($rs && numRows($rs)) {
            $show_authors = !has_single_author('txp_file');

            $contentBlock .= n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'files_form',
                    'name'   => 'longform',
                    'method' => 'post',
                    'action' => 'index.php',
                )).
                n.tag_start('div', array(
                    'class'      => 'txp-listtables',
                    'tabindex'   => 0,
                    'aria-label' => gTxt('list'),
                )).
                n.tag_start('table', array('class' => 'txp-list')).
                n.tag_start('thead').
                tr(
                    hCell(
                        fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                            '', ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                    ).
                    column_head(
                        'ID', 'id', 'file', true, $switch_dir, $crit, $search_method,
                            (('id' == $sort) ? "$dir " : '').'txp-list-col-id'
                    ).
                    column_head(
                        'name', 'filename', 'file', true, $switch_dir, $crit, $search_method,
                            (('filename' == $sort) ? "$dir " : '').'txp-list-col-filename'
                    ).
                    column_head(
                        'title', 'title', 'file', true, $switch_dir, $crit, $search_method,
                            (('title' == $sort) ? "$dir " : '').'txp-list-col-title'
                    ).
                    column_head(
                        'date', 'date', 'file', true, $switch_dir, $crit, $search_method,
                            (('date' == $sort) ? "$dir " : '').'txp-list-col-created date'
                    ).
                    column_head(
                        'category', 'category', 'file', true, $switch_dir, $crit, $search_method,
                            (('category' == $sort) ? "$dir " : '').'txp-list-col-category category'
                    ).
                    (has_privs('tag')
                        ? hCell(gTxt(
                            'tags'), '', ' class="txp-list-col-tag-build" scope="col"'
                        )
                        : ''
                    ).
                    hCell(gTxt(
                        'status'), '', ' class="txp-list-col-status" scope="col"'
                    ).
                    hCell(gTxt(
                        'condition'), '', ' class="txp-list-col-condition" scope="col"'
                    ).
                    column_head(
                        'file_size', 'size', 'file', true, $switch_dir, $crit, $search_method,
                            (('size' == $sort) ? "$dir " : '').'txp-list-col-filesize'
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

                $tagName = 'file_download_link';
                $tag_url = array(
                    'id'          => $id,
                    'description' => $description,
                    'filename'    => $filename,
                    'step'        => 'build',
                );

                $file_exists = is_file(build_file_path($file_base_path, $filename));
                $can_edit = has_privs('file.edit') || ($author === $txp_user && has_privs('file.edit.own'));
                $validator->setConstraints(array(new CategoryConstraint($category, array('type' => 'file'))));

                if ($validator->validate()) {
                    $vc = '';
                } else {
                    $vc = ' error';
                }

                if ($file_exists) {
                    $downloads = make_download_link($id, $downloads, $filename);
                    $condition = tag(gTxt('status_ok'), 'small', array('class' => 'alert-block alert-pill success'));
                } else {
                    $condition = tag(gTxt('status_missing'), 'small', array('class' => 'alert-block alert-pill error'));
                }

                if ($category) {
                    $category = span(txpspecialchars($category_title), array(
                        'title'      => $category,
                        'aria-label' => $category,
                    ));
                }

                if ($can_edit) {
                    $name = href(txpspecialchars($filename), $edit_url, array(
                        'title'      => gTxt('edit'),
                        'aria-label' => gTxt('edit'),
                    ));
                } else {
                    $name = txpspecialchars($filename);
                }

                if ($can_edit) {
                    $id_column = href($id, $edit_url, array(
                        'title'      => gTxt('edit'),
                        'aria-label' => gTxt('edit'),
                    ));
                    $multi_edit = checkbox('selected[]', $id, in_array($id, $ids));
                } else {
                    $id_column = $id;
                    $multi_edit = '';
                }

                if ($file_exists) {
                    $id_column .= span(
                        sp.span('&#124;', array('role' => 'separator')).
                        sp.make_download_link($id, gTxt('download'), $filename),
                        array('class' => 'txp-option-link')
                    );
                }

                if (isset($file_statuses[$status])) {
                    $status = $file_statuses[$status];
                } else {
                    $status = span(gTxt('none'), array('class' => 'error'));
                }

                $contentBlock .= tr(
                    td(
                        $multi_edit, '', 'txp-list-col-multi-edit'
                    ).
                    hCell(
                        $id_column, '', array(
                            'class' => 'txp-list-col-id',
                            'scope' => 'row',
                        )
                    ).
                    td(
                        $name, '', 'txp-list-col-filename txp-contain'
                    ).
                    td(
                        txpspecialchars($title), '', 'txp-list-col-title'
                    ).
                    td(
                        gTime($uDate), '', 'txp-list-col-created date'
                    ).
                    td(
                        $category, '', 'txp-list-col-category category'.$vc
                    ).
                    (has_privs('tag')
                        ? td(
                            popTag($tagName, 'Textile', array('type' => 'textile') + $tag_url).
                            sp.span('&#124;', array('role' => 'separator')).
                            sp.popTag($tagName, 'Textpattern', array('type' => 'textpattern') + $tag_url).
                            sp.span('&#124;', array('role' => 'separator')).
                            sp.popTag($tagName, 'HTML', array('type' => 'html') + $tag_url), '', 'txp-list-col-tag-build'
                        )
                        : ''
                    ).
                    td(
                        $status, '', 'txp-list-col-status'
                    ).
                    td(
                        $condition, '', 'txp-list-col-condition'
                    ).
                    td(
                        format_filesize($size), '', 'txp-list-col-filesize'
                    ).
                    td(
                        $downloads, '', 'txp-list-col-downloads'
                    ).
                    (
                        $show_authors
                        ? td(span(txpspecialchars($realname), array(
                            'title'      => $author,
                            'aria-label' => $author,
                        )), '', 'txp-list-col-author name')
                        : ''
                    )
                );
            }

            $contentBlock .=
                n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div'). // End of .txp-listtables.
                file_multiedit_form($page, $sort, $dir, $crit, $search_method).
                tInput().
                n.tag_end('form');
        }
    }

    $pageBlock = $paginator->render().
        nav_form($event, $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table($event);
    echo $table->render(compact('total', 'crit'), $searchBlock, $createBlock, $contentBlock, $pageBlock).
        n.tag(
        null,
        'div', array(
            'class'      => 'txp-tagbuilder-content',
            'id'         => 'tagbuild_links',
            'title'      => gTxt('tagbuilder'),
            'aria-label' => gTxt('tagbuilder'),
        ));
}

// -------------------------------------------------------------

function file_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $file_statuses, $all_file_cats, $all_file_authors;

    $categories = $all_file_cats ? treeSelectInput('category', $all_file_cats, '') : '';
    $authors = $all_file_authors ? selectInput('author', $all_file_authors, '', true) : '';
    $status = selectInput('status', $file_statuses, '', true);

    $methods = array(
        'changestatus'   => array(
            'label' => gTxt('changestatus'),
            'html'  => $status,
        ),
        'changecategory' => array(
            'label' => gTxt('changecategory'),
            'html'  => $categories,
        ),
        'changeauthor'   => array(
            'label' => gTxt('changeauthor'),
            'html'  => $authors,
        ),
        'changecount'    => array('label' => gTxt('reset_download_count')),
        'delete'         => gTxt('delete'),
    );

    if (!$categories) {
        unset($methods['changecategory']);
    }

    if (has_single_author('txp_file') || !has_privs('file.edit')) {
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

    // Empty entry to permit clearing the category.
    $categories = array('');

    foreach ($all_file_cats as $row) {
        $categories[] = $row['name'];
    }

    $selected = ps('selected');

    if (!$selected || !is_array($selected)) {
        return file_list();
    }

    $selected = array_map('assert_int', $selected);
    $method   = ps('edit_method');
    $changed  = array();
    $key = '';

    switch ($method) {
        case 'delete':
            return file_delete($selected);
            break;
        case 'changecategory':
            $val = ps('category');
            if (in_array($val, $categories)) {
                $key = 'category';
            }
            break;
        case 'changeauthor':
            $val = ps('author');
            if (has_privs('file.edit') && isset($all_file_authors[$val])) {
                $key = 'author';
            }
            break;
        case 'changecount':
            $key = 'downloads';
            $val = 0;
            break;
        case 'changestatus':
            $key = 'status';
            $val = ps('status');

            // Do not allow to be set to an empty value.
            if (!$val) {
                $selected = array();
            }
            break;
        default:
            $key = '';
            $val = '';
            break;
    }

    // Remove bogus (false) entries to prevent SQL syntax errors being thrown.
    $selected = array_filter($selected);

    if (!has_privs('file.edit')) {
        if ($selected && has_privs('file.edit.own')) {
            $selected = safe_column("id", 'txp_file', "id IN (".join(',', $selected).") AND author = '".doSlash($txp_user)."'");
        } else {
            $selected = array();
        }
    }

    if ($selected && $key) {
        foreach ($selected as $id) {
            if (safe_update('txp_file', "$key = '".doSlash($val)."'", "id = '$id'")) {
                $changed[] = $id;
            }
        }
    }

    if ($changed) {
        update_lastmod('file_updated', $changed);

        return file_list(gTxt('file_updated', array('{name}' => join(', ', $changed))));
    }

    return file_list();
}

/**
 * Renders and outputs the file editor panel.
 *
 * @param string|array $message The activity message
 * @param int          $id      The file ID
 */

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
    $rs = safe_row("*, UNIX_TIMESTAMP(created) AS created, UNIX_TIMESTAMP(modified) AS modified", 'txp_file', "id = '$id'");

    if ($rs) {
        extract($rs);
        $filename = sanitizeForFile($filename);

        if (!has_privs('file.edit') && !($author === $txp_user && has_privs('file.edit.own'))) {
            require_privs();
        }

        pagetop(gTxt('edit_file'), $message);

        if ($permissions == '') {
            $permissions = '-1';
        }

        if (!has_privs('file.publish') && $status >= STATUS_LIVE) {
            $status = STATUS_PENDING;
        }

        $file_exists = is_file(build_file_path($file_base_path, $filename));
        $existing_files = get_filenames();

        if (!is_dir($file_base_path) || !is_writeable($file_base_path)) {
            $replace = '';
        } else {
            $replace = ($file_exists)
                ? file_upload_form('replace_file', 'file_replace', 'file_replace', $id, 'file_replace', ' replace-file')
                : file_upload_form('file_relink', 'file_reassign', 'file_replace', $id, 'file_reassign', ' upload-file');
        }

        $condition = span((($file_exists)
                ? gTxt('status_ok')
                : gTxt('status_missing')
            ), array('class' => 'alert-block alert-pill '.(($file_exists) ? 'success' : 'error')));

        $downloadlink = ($file_exists) ? make_download_link($id, txpspecialchars($filename), $filename) : txpspecialchars($filename);

        $created =
            inputLabel(
                'year',
                tsi('year', '%Y', $rs['created'], '', 'year').
                ' <span role="separator">/</span> '.
                tsi('month', '%m', $rs['created'], '', 'month').
                ' <span role="separator">/</span> '.
                tsi('day', '%d', $rs['created'], '', 'day'),
                'publish_date',
                array('timestamp_file', 'instructions_file_date'),
                array('class' => 'txp-form-field date posted')
            ).
            inputLabel(
                'hour',
                tsi('hour', '%H', $rs['created'], '', 'hour').
                ' <span role="separator">:</span> '.
                tsi('minute', '%M', $rs['created'], '', 'minute').
                ' <span role="separator">:</span> '.
                tsi('second', '%S', $rs['created'], '', 'second'),
                'publish_time',
                array('', 'instructions_file_time'),
                array('class' => 'txp-form-field time posted')
            ).
            n.tag(
                checkbox('publish_now', '1', $publish_now, '', 'publish_now').
                n.tag(gTxt('set_to_now'), 'label', array('for' => 'publish_now')),
                'div', array('class' => 'txp-form-field-shim posted-now')
            );

        echo n.tag_start('div', array('class' => 'txp-edit')).
            hed(gTxt('edit_file'), 2).
            $replace.
            inputLabel(
                'condition',
                $condition,
                '', '', array('class' => 'txp-form-field edit-file-condition')
            ).
            inputLabel(
                'id',
                $id,
                'id', '', array('class' => 'txp-form-field edit-file-id')
            ).
            inputLabel(
                'name',
                $downloadlink,
                '', '', array('class' => 'txp-form-field edit-file-name')
            ).
            inputLabel(
                'download_count',
                $downloads,
                '', '', array('class' => 'txp-form-field edit-file-download-count')
            ).
            form(
                (($file_exists)
                ? inputLabel(
                        'file_status',
                        selectInput('status', $file_statuses, $status, false, '', 'file_status'),
                        'file_status', '', array('class' => 'txp-form-field edit-file-status')
                    ).
                    $created.
                    inputLabel(
                        'file_title',
                        fInput('text', 'title', $title, '', '', '', INPUT_REGULAR, '', 'file_title'),
                        'title', '', array('class' => 'txp-form-field edit-file-title')
                    ).
                    inputLabel(
                        'file_category',
                        event_category_popup('file', $category, 'file_category').
                        n.eLink('category', 'list', '', '', gTxt('edit'), '', '', '', 'txp-option-link'),
                        'category', '', array('class' => 'txp-form-field edit-file-category')
                    ).
//                    inputLabel(
//                        'perms',
//                        selectInput('perms', $levels, $permissions),
//                        'permissions'
//                    ).
                    inputLabel(
                        'file_description',
                        '<textarea id="file_description" name="description" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.htmlspecialchars($description, ENT_NOQUOTES).'</textarea>',
                        'description', '', array('class' => 'txp-form-field txp-form-field-textarea edit-file-description')
                    ).
                    pluggable_ui('file_ui', 'extend_detail_form', '', $rs).
                    graf(
                        sLink('file', '', gTxt('cancel'), 'txp-button').
                        fInput('submit', '', gTxt('save'), 'publish'),
                        array('class' => 'txp-edit-actions')
                    ).
                    hInput('filename', $filename)
                : (empty($existing_files)
                        ? ''
                        : gTxt('existing_file').selectInput('filename', $existing_files, '', 1)
                    ).
                    pluggable_ui('file_ui', 'extend_detail_form', '', $rs).
                    graf(
                        sLink('file', '', gTxt('cancel'), 'txp-button').
                        fInput('submit', '', gTxt('save'), 'publish'),
                        array('class' => 'txp-edit-actions')
                    ).
                    hInput('perms', ($permissions == '-1') ? '' : $permissions).
                    hInput(compact('category', 'title', 'description', 'status'))
                ).
                eInput('file').
                sInput('file_save').
                hInput(compact('id', 'sort', 'dir', 'page', 'search_method', 'crit')),
            '', '', 'post', 'file-detail '.(($file_exists) ? '' : 'not-').'exists', '', (($file_exists) ? 'file_details' : 'assign_file')).
            n.tag_end('div');
    }
}

// -------------------------------------------------------------

function file_db_add($filename, $category, $permissions, $description, $size, $title = '')
{
    global $txp_user;

    if (trim($filename) === '') {
        return false;
    }

    $qs = quote_list(
        array('author' => $txp_user) + compact('filename', 'title', 'category', 'permissions', 'description', 'size')
    ) + array(
        'created'  => 'NOW()',
        'modified' => 'NOW()',
    );

    $rs = safe_insert('txp_file', join_qs($qs, ','));

    if ($rs) {
        return $GLOBALS['ID'] = $rs;
    }

    return false;
}

// -------------------------------------------------------------

function file_create()
{
    global $txp_user, $file_base_path;

    require_privs('file.edit.own');

    extract(array_map('assert_string', gpsa(array(
        'title',
        'category',
        'permissions',
        'description',
    ))));

    $filename = array_filter((array) gps('filename'));

    $success = $failed = $notFound = $invalid = $ids = array();

    foreach ($filename as $file) {
        $safe_filename = sanitizeForFile($file);
        if ($safe_filename != $file) {
            $invalid[] = $file;
            continue;
        }

        $size = filesize(build_file_path($file_base_path, $safe_filename));
        $id = file_db_add($safe_filename, $category, $permissions, $description, $size, $title);

        if ($id === false) {
            $failed[] = $safe_filename;
        } else {
            $newpath = build_file_path($file_base_path, $safe_filename);

            if (is_file($newpath)) {
                file_set_perm($newpath);
                $ids[] = $id;
                $success[] = $safe_filename;
            } else {
                $notFound[] = $safe_filename;
            }
        }
    }

    $messages = array();

    if ($success) {
        $messages[] = array(gTxt('linked_to_file', array('{list}' => join(', ', $success))), 0);
    }

    if ($failed) {
        $messages[] = array(gTxt('file_upload_failed', array('{list}' => join(', ', $failed))), E_ERROR);
    }

    if ($notFound) {
        $messages[] = array(gTxt('file_not_found', array('{list}' => join(', ', $notFound))), E_ERROR);
    }

    if ($invalid) {
        $messages[] = array(gTxt('invalid_filename', array('{list}' => join(', ', $invalid))), E_ERROR);
    }

    if ($ids) {
        now('created', true);
        update_lastmod('file_created', compact('ids', 'filename', 'title', 'category', 'description'));
    }

    $response = '';

    foreach ($messages as $message) {
        $response .= 'textpattern.Console.addMessage('.json_encode($message, TEXTPATTERN_JSON).');'.n;
    }

    script_js($response, false);
    file_list();
}

// -------------------------------------------------------------

function file_insert()
{
    global $txp_user, $file_base_path, $file_max_upload_size, $app_mode;

    require_privs('file.edit.own');
    $messages = $ids = array();
    $fileshandler = Txp::get('\Textpattern\Server\Files');
    $files = $fileshandler->refactor($_FILES['thefile']);
    $titles = gps('title');

    extract(array_map('assert_string', gpsa(array(
        'category',
        'permissions',
        'description',
    ))));

    foreach ($files as $i => $file) {
        $chunked = $fileshandler->dechunk($file);
        extract($file);
        $newname = sanitizeForFile($name);
        $newpath = build_file_path($file_base_path, $newname);

        if ($error || !$size && $chunked) {
            $messages[] = array(gTxt('file_upload_failed', array('{list}' => $newname))." - ".upload_get_errormsg($error ? $error : UPLOAD_ERR_PARTIAL), E_ERROR);
        } elseif ($file_max_upload_size < $size) {
            $messages[] = array(gTxt('file_upload_failed', array('{list}' => $newname))." - ".upload_get_errormsg(UPLOAD_ERR_FORM_SIZE), E_ERROR);
        } elseif (!is_file($newpath) && !safe_count('txp_file', "filename = '".doSlash($newname)."'")) {
            $hash = isset($titles[$i]) ? $i : md5($name);
            $title = isset($titles[$hash]) ? $titles[$hash] : '';
            $id = file_db_add($newname, $category, $permissions, $description, $size, $title);

            if (!$id) {
                $messages[] = array(gTxt('file_upload_failed', array('{list}' => $newname)).' (db_add)', E_ERROR);
            } else {
                $id = assert_int($id);

                if (!shift_uploaded_file($tmp_name, $newpath)) {
                    safe_delete('txp_file', "id = '$id'");
                    safe_alter('txp_file', "auto_increment = '$id'");
                    $messages[] = array(gTxt('directory_permissions', array('{path}' => $newpath)), E_ERROR);
                } else {
                    file_set_perm($newpath);
                    $ids[] = $GLOBALS['ID'] = $id;
                    $messages[] = array(gTxt('file_uploaded', array('{name}' => href(txpspecialchars($newname), '?event=file&step=file_edit&id='.$id, array(
                        'title'      => gTxt('edit'),
                        'aria-label' => gTxt('edit'),
                    ))), false), 0);
                }
            }
        } else {
            $messages[] = array(gTxt('file_already_exists', array('{name}' => $newname)), E_WARNING);
        }

        // Clean up file.
        
        if (is_file($tmp_name)) {
            unlink(realpath($tmp_name));
        }
    }

    if ($ids) {
        update_lastmod('file_uploaded', compact('ids', 'title', 'category', 'description'));
        now('created', true);
    }

    if ($app_mode == 'async') {
        $response = $ids ? 'textpattern.Relay.data.fileid = ["'.implode('","', $ids).'"].concat(textpattern.Relay.data.fileid || []);'.n : '';

        foreach ($messages as $message) {
            $response .= 'textpattern.Console.addMessage('.json_encode($message, TEXTPATTERN_JSON).', "uploadEnd");'.n;
        }

        send_script_response($response);

        // Bail out.
        return;
    }

    $status = $ids ? (count($ids) < count($messages) ? E_WARNING : 0) : E_ERROR;
    $message = array();

    foreach ($messages as $row) {
        $message[] = $row[0];
    }

    $messages = implode(br, $message);

    if ($ids && count($files) == 1) {
        file_edit(array($messages, $status), $ids[0]);
    } else {
        unset($GLOBALS['ID']);
        file_list($files ? array($messages, $status) : '', $ids);
    }
}

// -------------------------------------------------------------

function file_replace()
{
    global $txp_user, $file_base_path;

    $id = assert_int(gps('id'));
    $rs = safe_row("filename, author", 'txp_file', "id = '$id'");

    if (!$rs) {
        file_list(array(gTxt('invalid_id', array('{id}' => $id)), E_ERROR));

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
        file_list(array(gTxt('file_upload_failed')." $name ".upload_get_errormsg($_FILES['thefile']['error']), E_ERROR));

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
            file_list(array(gTxt('directory_permissions', array('{path}' => $newpath)), E_ERROR));

            // Rename tmp back.
            rename($newpath.'.tmp', $newpath);

            // Remove tmp upload.
            unlink(realpath($file));
        } else {
            file_set_perm($newpath);
            update_lastmod('file_replaced', compact('id', 'filename'));
            now('created', true);

            if ($size = filesize($newpath)) {
                safe_update('txp_file', "size = $size, modified = NOW()", "id = '$id'");
            }

            file_edit(gTxt('file_uploaded', array('{name}' => $name)), $id);

            // Clean up old.
            if (is_file($newpath.'.tmp')) {
                unlink(realpath($newpath.'.tmp'));
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
    $rs = safe_row("filename, author", 'txp_file', "id = '$id'");

    if (!has_privs('file.edit') && !($rs['author'] === $txp_user && has_privs('file.edit.own'))) {
        require_privs();
    }

    $old_filename = $varray['old_filename'] = sanitizeForFile($rs['filename']);

    if ($old_filename != false && strcmp($old_filename, $filename) != 0) {
        $old_path = build_file_path($file_base_path, $old_filename);
        $new_path = build_file_path($file_base_path, $filename);

        if (is_file($old_path) && shift_uploaded_file($old_path, $new_path) === false) {
            file_list(array(gTxt('file_cannot_rename', array('{name}' => $filename)), E_ERROR));

            return;
        } else {
            file_set_perm($new_path);
        }
    }

    $created_ts = @safe_strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);

    if ($publish_now) {
        $created = "NOW()";
    } elseif ($created_ts > 0) {
        $created = "FROM_UNIXTIME('".$created_ts."')";
    } else {
        $created = '';
    }

    $size = filesize(build_file_path($file_base_path, $filename));

    $constraints = array(
        'category' => new CategoryConstraint(gps('category'), array('type' => 'file')),
        'status'   => new ChoiceConstraint(gps('status'), array(
            'choices' => array_keys($file_statuses),
            'message' => 'invalid_status',
        )),
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
        modified = NOW()"
        .($created ? ", created = $created" : ''), "id = '$id'");

    if (!$rs) {
        // Update failed, rollback name.
        if (isset($old_path) && shift_uploaded_file($new_path, $old_path) === false) {
            file_list(array(gTxt('file_unsynchronized', array('{name}' => $filename)), E_ERROR));

            return;
        } else {
            file_list(array(gTxt('file_not_updated', array('{name}' => $filename)), E_ERROR));

            return;
        }
    }

    update_lastmod('file_saved', compact('id', 'filename', 'title', 'category', 'description', 'status', 'size'));
    now('created', true);
    file_list(gTxt('file_updated', array('{name}' => $filename)));
}

// -------------------------------------------------------------

function file_delete($ids = array())
{
    global $file_base_path, $txp_user;

    // Fetch ids and remove bogus (false) entries to prevent SQL syntax errors being thrown.
    $ids = $ids ? array_map('assert_int', $ids) : array(assert_int(ps('id')));
    $ids = array_filter($ids);

    if (!has_privs('file.delete')) {
        if ($ids && has_privs('file.delete.own')) {
            $ids = safe_column("id", 'txp_file', "id IN (".join(',', $ids).") AND author = '".doSlash($txp_user)."'");
        } else {
            $ids = array();
        }
    }

    if (!empty($ids)) {
        $fail = array();

        $rs = safe_rows_start("id, filename", 'txp_file', "id IN (".join(',', $ids).")");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);
                $id = assert_int($id);
                $filepath = build_file_path($file_base_path, $filename);

                // Notify plugins of pending deletion, pass file's id and path.
                callback_event('file_deleted', '', false, $id, $filepath);

                $rsd = safe_delete('txp_file', "id = '$id'");
                $ul = false;

                if ($rsd && is_file($filepath)) {
                    $ul = unlink(realpath($filepath));
                }

                if (!$rsd or !$ul) {
                    $fail[] = $id;
                }
            }
            if ($fail) {
                file_list(array(gTxt('file_delete_failed', array('{list}' => join(', ', $fail))), E_ERROR));

                return;
            } else {
                update_lastmod('file_deleted', $ids);
                now('created', true);
                file_list(gTxt('file_deleted', array('{name}' => join(', ', $ids))));

                return;
            }
        } else {
            file_list(array(gTxt('file_not_found', array('{list}' => join(', ', $ids))), E_ERROR));

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

/**
 * Renders a specific file upload form.
 *
 * @param  string       $label       File name label. May be empty
 * @param  string       $pophelp     Help item
 * @param  string       $step        Step
 * @param  string       $id          File id
 * @param  string       $label_id    HTML id attribute for the filename input element
 * @param  string       $class       HTML class attribute for the form element
 * @param  string|array $wraptag_val Tag to wrap the value / label in, or empty to omit
 * @param  array        $extra       array('postinput' => $categories ...)
 * @param  string|array $accept      Comma separated list of allowed file types, or empty to omit
 * @return string HTML
 */

function file_upload_form($label, $pophelp, $step, $id = '', $label_id = '', $class = '', $wraptag_val = array('div', 'div'), $extra = null, $accept = '')
{
    global $file_max_upload_size;

    if (!$file_max_upload_size || intval($file_max_upload_size) == 0) {
        $file_max_upload_size = 2 * (1024 * 1024);
    }

    $max_file_size = (intval($file_max_upload_size) == 0) ? '' : intval($file_max_upload_size);

    return upload_form($label, $pophelp, $step, 'file', $id, $max_file_size, $label_id, $class, $wraptag_val, $extra, $accept);
}

// -------------------------------------------------------------

function file_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    file_list();
}

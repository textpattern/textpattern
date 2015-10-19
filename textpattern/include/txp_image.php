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
 * Images panel.
 *
 * @package Admin\Image
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

global $extensions;
$extensions = get_safe_image_types();

include txpath.'/lib/class.thumb.php';

if ($event == 'image') {
    require_privs('image');

    global $all_image_cats, $all_image_authors;
    $all_image_cats = getTree('root', 'image');
    $all_image_authors = the_privileged('image.edit.own');

    $available_steps = array(
        'image_list'          => false,
        'image_edit'          => false,
        'image_insert'        => true,
        'image_replace'       => true,
        'image_save'          => true,
        'thumbnail_insert'    => true,
        'image_change_pageby' => true,
        'thumbnail_create'    => true,
        'thumbnail_delete'    => true,
        'image_multi_edit'    => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        image_list();
    }
}

/**
 * The main panel listing all images.
 *
 * @param string|array $message The activity message
 */

function image_list($message = '')
{
    global $txpcfg, $extensions, $img_dir, $file_max_upload_size, $image_list_pageby, $txp_user, $event;

    pagetop(gTxt('tab_image'), $message);

    extract($txpcfg);
    extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

    if ($sort === '') {
        $sort = get_pref('image_sort_column', 'id');
    } else {
        if (!in_array($sort, array('name', 'thumbnail', 'category', 'date', 'author'))) {
            $sort = 'id';
        }

        set_pref('image_sort_column', $sort, 'image', 2, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('image_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? 'asc' : 'desc';
        set_pref('image_sort_dir', $dir, 'image', 2, '', 0, PREF_PRIVATE);
    }

    echo hed(gTxt('tab_image'), 1, array('class' => 'txp-heading'));
    echo n.'<div id="'.$event.'_control" class="txp-control-panel">';

    if (!is_dir(IMPATH) or !is_writeable(IMPATH)) {
        echo graf(
            span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
            gTxt('img_dir_not_writeable', array('{imgdir}' => IMPATH)),
            array('class' => 'alert-block warning')
        );
    } elseif (has_privs('image.edit.own')) {
        echo upload_form(gTxt('upload_image'), 'upload_image', 'image_insert', 'image', '', $file_max_upload_size);
    }

    switch ($sort) {
        case 'name':
            $sort_sql = 'txp_image.name '.$dir;
            break;
        case 'thumbnail':
            $sort_sql = 'txp_image.thumbnail '.$dir.', txp_image.id asc';
            break;
        case 'category':
            $sort_sql = 'txp_category.title '.$dir.', txp_image.id asc';
            break;
        case 'date':
            $sort_sql = 'txp_image.date '.$dir.', txp_image.id asc';
            break;
        case 'author':
            $sort_sql = 'txp_users.RealName '.$dir.', txp_image.id asc';
            break;
        default:
            $sort = 'id';
            $sort_sql = 'txp_image.id '.$dir;
            break;
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $criteria = 1;

    if ($search_method and $crit != '') {
        $verbatim = preg_match('/^"(.*)"$/', $crit, $m);
        $crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
        $critsql = $verbatim ?
            array(
                'id'       => "txp_image.ID in ('".join("','", do_list($crit_escaped))."')",
                'name'     => "txp_image.name = '$crit_escaped'",
                'category' => "txp_image.category = '$crit_escaped' or txp_category.title = '$crit_escaped'",
                'author'   => "txp_image.author = '$crit_escaped' or txp_users.RealName = '$crit_escaped'",
                'alt'      => "txp_image.alt = '$crit_escaped'",
                'caption'  => "txp_image.caption = '$crit_escaped'",
            ) : array(
                'id'       => "txp_image.ID in ('".join("','", do_list($crit_escaped))."')",
                'name'     => "txp_image.name like '%$crit_escaped%'",
                'category' => "txp_image.category like '%$crit_escaped%'",
                'author'   => "txp_image.author like '%$crit_escaped%' or txp_category.title like '%$crit_escaped%'",
                'alt'      => "txp_image.alt like '%$crit_escaped%' or txp_users.RealName like '%$crit_escaped%'",
                'caption'  => "txp_image.caption like '%$crit_escaped%'",
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

    $criteria .= callback_event('admin_criteria', 'image_list', 0, $criteria);

    $sql_from =
        safe_pfx_j('txp_image')."
        left join ".safe_pfx_j('txp_category')." on txp_category.name = txp_image.category and txp_category.type = 'image'
        left join ".safe_pfx_j('txp_users')." on txp_users.name = txp_image.author";

    if ($criteria === 1) {
        $total = getCount('txp_image', $criteria);
    } else {
        $total = getThing('select count(*) from '.$sql_from.' where '.$criteria);
    }

    if ($total < 1) {
        if ($criteria != 1) {
            echo n.image_search_form($crit, $search_method).
                graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
        } else {
            echo graf(gTxt('no_images_recorded'), ' class="indicator"').'</div>';
        }

        return;
    }

    $limit = max($image_list_pageby, 15);

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    echo image_search_form($crit, $search_method);

    $rs = safe_query(
        "select
            txp_image.id,
            txp_image.name,
            txp_image.category,
            txp_image.ext,
            txp_image.w,
            txp_image.h,
            txp_image.alt,
            txp_image.caption,
            unix_timestamp(txp_image.date) as uDate,
            txp_image.author,
            txp_image.thumbnail,
            txp_image.thumb_w,
            txp_image.thumb_h,
            txp_users.RealName as realname,
            txp_category.Title as category_title
        from $sql_from where $criteria order by $sort_sql limit $offset, $limit"
    );

    echo pluggable_ui('image_ui', 'extend_controls', '', $rs);
    echo '</div>'; // End txp-control-panel.

    if ($rs) {
        $show_authors = !has_single_author('txp_image');

        echo
            n.tag_start('div', array(
                'id'    => $event.'_container',
                'class' => 'txp-container',
            )).
            n.tag_start('form', array(
                'action' => 'index.php',
                'id'     => 'images_form',
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
                    'ID', 'id', 'image', true, $switch_dir, $crit, $search_method,
                        (('id' == $sort) ? "$dir " : '').'txp-list-col-id'
                ).
                column_head(
                    'name', 'name', 'image', true, $switch_dir, $crit, $search_method,
                        (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                ).
                column_head(
                    'date', 'date', 'image', true, $switch_dir, $crit, $search_method,
                        (('date' == $sort) ? "$dir " : '').'txp-list-col-created date images_detail'
                ).
                column_head(
                    'thumbnail', 'thumbnail', 'image', true, $switch_dir, $crit, $search_method,
                        (('thumbnail' == $sort) ? "$dir " : '').'txp-list-col-thumbnail'
                ).
                hCell(
                    gTxt('tags'), '', ' scope="col" class="txp-list-col-tag-build images_detail"'
                ).
                column_head(
                    'image_category', 'category', 'image', true, $switch_dir, $crit, $search_method,
                        (('category' == $sort) ? "$dir " : '').'txp-list-col-category category'
                ).
                (
                    $show_authors
                    ? column_head('author', 'author', 'image', true, $switch_dir, $crit, $search_method,
                        (('author' == $sort) ? "$dir " : '').'txp-list-col-author name')
                    : ''
                )
            ).
            n.tag_end('thead').
            n.tag_start('tbody');

        $validator = new Validator();

        while ($a = nextRow($rs)) {
            extract($a);

            $edit_url = array(
                'event'         => 'image',
                'step'          => 'image_edit',
                'id'            => $id,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
            );

            $name = empty($name) ? gTxt('unnamed') : txpspecialchars($name);

            if ($thumbnail) {
                if ($ext != '.swf') {
                    $thumbnail = '<img class="content-image" src="'.imagesrcurl($id, $ext, true)."?$uDate".'" alt="" '.
                                        "title='$id$ext ($w &#215; $h)'".
                                        ($thumb_w ? " width='$thumb_w' height='$thumb_h'" : '').' />';
                } else {
                    $thumbnail = '';
                }
            } else {
                $thumbnail = gTxt('no');
            }

            if ($ext != '.swf') {
                $tag_url = '?event=tag'.a.'tag_name=image'.a.'id='.$id.a.'ext='.$ext.a.'w='.$w.a.'h='.$h.a.'alt='.urlencode($alt).a.'caption='.urlencode($caption);
                $tagbuilder = href('Textile', $tag_url.a.'type=textile', ' target="_blank" onclick="popWin(this.href); return false;"').
                    sp.span('&#124;', array('role' => 'separator')).
                    sp.href('Textpattern', $tag_url.a.'type=textpattern', ' target="_blank" onclick="popWin(this.href); return false;"').
                    sp.span('&#124;', array('role' => 'separator')).
                    sp.href('HTML', $tag_url.a.'type=html', ' target="_blank" onclick="popWin(this.href); return false;"');
            } else {
                $tagbuilder = sp;
            }

            $validator->setConstraints(array(new CategoryConstraint($category, array('type' => 'image'))));
            $vc = $validator->validate() ? '' : ' error';

            if ($category) {
                $category = span(txpspecialchars($category_title), array('title' => $category));
            }

            $can_edit = has_privs('image.edit') || ($author === $txp_user && has_privs('image.edit.own'));

            echo tr(
                td(
                    $can_edit ? fInput('checkbox', 'selected[]', $id) : '&#160;', '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    ($can_edit ? href($id, $edit_url, array('title' => gTxt('edit'))) : $id).
                    sp.span(
                        span('[', array('aria-hidden' => 'true')).
                        href(gTxt('view'), imagesrcurl($id, $ext)).
                        span(']', array('aria-hidden' => 'true')), array('class' => 'images_detail')), '', ' scope="row" class="txp-list-col-id"').

                td(
                    ($can_edit ? href($name, $edit_url, ' title="'.gTxt('edit').'"') : $name), '', 'txp-list-col-name'
                ).
                td(
                    gTime($uDate), '', 'txp-list-col-created date images_detail'
                ).
                td(
                    pluggable_ui('image_ui', 'thumbnail', ($can_edit ? href($thumbnail, $edit_url) : $thumbnail), $a), '', 'txp-list-col-thumbnail'
                ).
                td(
                    $tagbuilder, '', 'txp-list-col-tag-build images_detail'
                ).
                td(
                    $category, '', 'txp-list-col-category category'.$vc
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
            image_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            graf(toggle_box('images_detail'), array('class' => 'detail-toggle')).
            n.tag_start('div', array(
                'id'    => $event.'_navigation',
                'class' => 'txp-navigation',
            )).
            pageby_form('image', $image_list_pageby).
            nav_form('image', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div').
            n.tag_end('div');
    }
}

// -------------------------------------------------------------

function image_search_form($crit, $method)
{
    $methods = array(
        'id'       => gTxt('ID'),
        'name'     => gTxt('name'),
        'category' => gTxt('image_category'),
        'author'   => gTxt('author'),
        'alt'      => gTxt('alt_text'),
        'caption'  => gTxt('caption'),
    );

    return search_form('image', 'image_list', $crit, $methods, $method, 'name');
}

// -------------------------------------------------------------

function image_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $all_image_cats, $all_image_authors;

    $categories = $all_image_cats ? treeSelectInput('category', $all_image_cats, '') : '';
    $authors = $all_image_authors ? selectInput('author', $all_image_authors, '', true) : '';

    $methods = array(
        'changecategory' => array('label' => gTxt('changecategory'), 'html' => $categories),
        'changeauthor'   => array('label' => gTxt('changeauthor'), 'html' => $authors),
        'delete'         => gTxt('delete'),
    );

    if (!$categories) {
        unset($methods['changecategory']);
    }

    if (has_single_author('txp_image')) {
        unset($methods['changeauthor']);
    }

    if (!has_privs('image.delete.own') && !has_privs('image.delete')) {
        unset($methods['delete']);
    }

    return multi_edit($methods, 'image', 'image_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

// -------------------------------------------------------------

function image_multi_edit()
{
    global $txp_user, $all_image_cats, $all_image_authors;

    // Empty entry to permit clearing the category.
    $categories = array('');

    foreach ($all_image_cats as $row) {
        $categories[] = $row['name'];
    }

    $selected = ps('selected');

    if (!$selected or !is_array($selected)) {
        return image_list();
    }

    $selected = array_map('assert_int', $selected);
    $method   = ps('edit_method');
    $changed  = array();
    $key = '';

    switch ($method) {
        case 'delete':
            return image_delete($selected);
            break;
        case 'changecategory':
            $val = ps('category');
            if (in_array($val, $categories)) {
                $key = 'category';
            }
            break;
        case 'changeauthor':
            $val = ps('author');
            if (in_array($val, $all_image_authors)) {
                $key = 'author';
            }
            break;
        default:
            $key = '';
            $val = '';
            break;
    }

    if (!has_privs('image.edit')) {
        if (has_privs('image.edit.own')) {
            $selected = safe_column('id', 'txp_image', 'id IN ('.join(',', $selected).') AND author=\''.doSlash($txp_user).'\'');
        } else {
            $selected = array();
        }
    }

    if ($selected and $key) {
        foreach ($selected as $id) {
            if (safe_update('txp_image', "$key = '".doSlash($val)."'", "id = $id")) {
                $changed[] = $id;
            }
        }
    }

    if ($changed) {
        update_lastmod('image_updated', $changed);

        return image_list(gTxt('image_updated', array('{name}' => join(', ', $changed))));
    }

    return image_list();
}

// -------------------------------------------------------------

function image_edit($message = '', $id = '')
{
    global $prefs, $file_max_upload_size, $txp_user, $event, $all_image_cats;

    if (!$id) {
        $id = gps('id');
    }

    $id = assert_int($id);
    $rs = safe_row("*, unix_timestamp(date) as uDate", "txp_image", "id = $id");

    if ($rs) {
        extract($rs);

        if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
            image_list(gTxt('restricted_area'));

            return;
        }

        pagetop(gTxt('edit_image'), $message);

        extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

        if ($ext != '.swf') {
            $aspect = ($h == $w) ? ' square' : (($h > $w) ? ' portrait' : ' landscape');
            $img_info = $id.$ext.' ('.$w.' &#215; '.$h.')';
            $img = '<div class="fullsize-image"><img class="content-image" src="'.imagesrcurl($id, $ext)."?$uDate".'" alt="'.$img_info.'" title="'.$img_info.'" /></div>';
        } else {
            $img = $aspect = '';
        }

        if ($thumbnail and ($ext != '.swf')) {
            $thumb_info = $id.'t'.$ext.' ('.$thumb_w.' &#215; '.$thumb_h.')';
            $thumb = '<img class="content-image" src="'.imagesrcurl($id, $ext, true)."?$uDate".'" alt="'.$thumb_info.'" '.
                        ($thumb_w ? 'width="'.$thumb_w.'" height="'.$thumb_h.'" title="'.$thumb_info.'"' : '').' />';
        } else {
            $thumb = '';

            if ($thumb_w == 0) {
                $thumb_w = get_pref('thumb_w', 0);
            }

            if ($thumb_h == 0) {
                $thumb_h = get_pref('thumb_h', 0);
            }
        }

        echo n.'<div id="'.$event.'_container" class="txp-container">';
        echo
            pluggable_ui(
                'image_ui',
                'fullsize_image',
                $img,
                $rs
            ),

            '<section class="txp-edit">',
            hed(gTxt('edit_image'), 2),

            pluggable_ui(
                'image_ui',
                'image_edit',
                wrapGroup('image_edit_group', upload_form('', '', 'image_replace', 'image', $id, $file_max_upload_size, 'image_replace', 'image-replace'), 'replace_image', 'replace-image', 'replace_image_form'),
                $rs
            ),

            pluggable_ui(
                'image_ui',
                'thumbnail_image',
                '<div class="thumbnail-edit">'.
                (($thumbnail)
                    ? $thumb.n.dLink('image', 'thumbnail_delete', 'id', $id, '', '', '', '', array($page, $sort, $dir, $crit, $search_method))
                    :     '').
                '</div>',
                $rs
            ),

            pluggable_ui(
                'image_ui',
                'thumbnail_edit',
                wrapGroup('thumbnail_edit_group', upload_form('', '', 'thumbnail_insert', 'image', $id, $file_max_upload_size, 'upload_thumbnail', 'thumbnail-upload'), 'upload_thumbnail', 'thumbnail-upload', 'upload_thumbnail'),
                $rs
            ),

            (check_gd($ext))
            ? pluggable_ui(
                'image_ui',
                'thumbnail_create',
                wrapGroup(
                    'thumbnail_create_group',
                    form(
                        graf(
                            n.'<label for="width">'.gTxt('thumb_width').'</label>'.
                            fInput('text', 'width', @$thumb_w, 'input-xsmall', '', '', INPUT_XSMALL, '', 'width').
                            n.'<label for="height">'.gTxt('thumb_height').'</label>'.
                            fInput('text', 'height', @$thumb_h, 'input-xsmall', '', '', INPUT_XSMALL, '', 'height').
                            n.'<label for="crop">'.gTxt('keep_square_pixels').'</label>'.
                            checkbox('crop', 1, @$prefs['thumb_crop'], '', 'crop').
                            fInput('submit', '', gTxt('Create')), ' class="edit-alter-thumbnail"').
                        hInput('id', $id).
                        eInput('image').
                        sInput('thumbnail_create').
                        hInput('sort', $sort).
                        hInput('dir', $dir).
                        hInput('page', $page).
                        hInput('search_method', $search_method).
                        hInput('crit', $crit), '', '', 'post', 'edit-form', '', 'thumbnail_alter_form'),
                    'create_thumbnail',
                    'thumbnail-alter',
                    'create_thumbnail'
                    ),
                $rs
            )
            : '',

            '<div class="image-detail">',
                form(
                    inputLabel('image_name', fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'image_name'), 'image_name').
                    inputLabel('image_category', treeSelectInput('category', $all_image_cats, $category, 'image_category'), 'image_category').
                    inputLabel('image_alt_text', fInput('text', 'alt', $alt, '', '', '', INPUT_REGULAR, '', 'image_alt_text'), 'alt_text').
                    inputLabel('image_caption', text_area('caption', 0, 0, $caption, 'image_caption', TEXTAREA_HEIGHT_SMALL, INPUT_LARGE), 'caption', '', '', '').
                    pluggable_ui('image_ui', 'extend_detail_form', '', $rs).
                    graf(fInput('submit', '', gTxt('save'), 'publish')).
                    hInput('id', $id).
                    eInput('image').
                    sInput('image_save').
                    hInput('sort', $sort).
                    hInput('dir', $dir).
                    hInput('page', $page).
                    hInput('search_method', $search_method).
                    hInput('crit', $crit), '', '', 'post', 'edit-form', '', 'image_details_form'),
            '</div>',
        '</section>'.n.'</div>';
    }
}

// -------------------------------------------------------------

function image_insert()
{
    global $txpcfg, $extensions, $txp_user;

    if (!has_privs('image.edit.own')) {
        image_list(gTxt('restricted_area'));

        return;
    }

    extract($txpcfg);

    $meta = gpsa(array('caption', 'alt', 'category'));

    $img_result = image_data($_FILES['thefile'], $meta);

    if (is_array($img_result)) {
        list($message, $id) = $img_result;

        return image_edit($message, $id);
    } else {
        return image_list(array($img_result, E_ERROR));
    }
}

// -------------------------------------------------------------

function image_replace()
{
    global $txpcfg, $extensions, $txp_user;
    extract($txpcfg);

    $id = assert_int(gps('id'));
    $rs = safe_row("*", "txp_image", "id = $id");

    if (!has_privs('image.edit') && !($rs['author'] === $txp_user && has_privs('image.edit.own'))) {
        image_list(gTxt('restricted_area'));

        return;
    }

    if ($rs) {
        $meta = array('category' => $rs['category'], 'caption' => $rs['caption'], 'alt' => $rs['alt']);
    } else {
        $meta = '';
    }

    $img_result = image_data($_FILES['thefile'], $meta, $id);

    if (is_array($img_result)) {
        list($message, $id) = $img_result;

        return image_edit($message, $id);
    } else {
        return image_edit(array($img_result, E_ERROR), $id);
    }
}

// -------------------------------------------------------------

function thumbnail_insert()
{
    global $txpcfg, $extensions, $txp_user, $img_dir, $path_to_site;
    extract($txpcfg);
    $id = assert_int(gps('id'));
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        image_list(gTxt('restricted_area'));

        return;
    }

    $file = $_FILES['thefile']['tmp_name'];
    $name = $_FILES['thefile']['name'];
    $file = get_uploaded_file($file);

    if (empty($file)) {
        image_edit(array(upload_get_errormsg(UPLOAD_ERR_NO_FILE), E_ERROR), $id);

        return;
    }

    list($w, $h, $extension) = getimagesize($file);

    if (($file !== false) && @$extensions[$extension]) {
        $ext = $extensions[$extension];
        $newpath = IMPATH.$id.'t'.$ext;

        if (shift_uploaded_file($file, $newpath) == false) {
            image_list(array($newpath.sp.gTxt('upload_dir_perms'), E_ERROR));
        } else {
            chmod($newpath, 0644);
            safe_update("txp_image", "thumbnail = 1, thumb_w = $w, thumb_h = $h, date = now()", "id = $id");

            $message = gTxt('image_uploaded', array('{name}' => $name));
            update_lastmod('thumbnail_created', compact('id', 'w', 'h'));

            image_edit($message, $id);
        }
    } else {
        if ($file === false) {
            image_list(array(upload_get_errormsg($_FILES['thefile']['error']), E_ERROR));
        } else {
            image_list(array(gTxt('only_graphic_files_allowed'), E_ERROR));
        }
    }
}

// -------------------------------------------------------------

function image_save()
{
    global $txp_user;

    $varray = array_map('assert_string', gpsa(array('id', 'name', 'category', 'caption', 'alt')));
    extract(doSlash($varray));
    $id = $varray['id'] = assert_int($id);
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        image_list(gTxt('restricted_area'));

        return;
    }

    $constraints = array(
        'category' => new CategoryConstraint(gps('category'), array('type' => 'image')),
    );
    callback_event_ref('image_ui', 'validate_save', 0, $varray, $constraints);
    $validator = new Validator($constraints);

    if ($validator->validate() && safe_update(
        "txp_image",
        "name    = '$name',
        category = '$category',
        alt      = '$alt',
        caption  = '$caption'",
        "id = $id"
    )) {
        $message = gTxt('image_updated', array('{name}' => doStrip($name)));
        update_lastmod('image_saved', compact('id', 'name', 'category', 'alt', 'caption'));
    } else {
        $message = array(gTxt('image_save_failed'), E_ERROR);
    }

    image_list($message);
}

// -------------------------------------------------------------

function image_delete($ids = array())
{
    global $txp_user, $event;

    $ids = $ids ? array_map('assert_int', $ids) : array(assert_int(ps('id')));
    $message = '';

    if (!has_privs('image.delete')) {
        if (has_privs('image.delete.own')) {
            $ids = safe_column('id', 'txp_image', 'id IN ('.join(',', $ids).') AND author=\''.doSlash($txp_user).'\'');
        } else {
            $ids = array();
        }
    }

    if (!empty($ids)) {
        $fail = array();
        $rs   = safe_rows_start('id, ext', 'txp_image', 'id IN ('.join(',', $ids).')');

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);

                // Notify plugins of pending deletion, pass image's $id.
                callback_event('image_deleted', $event, false, $id);

                $rsd = safe_delete('txp_image', "id = $id");

                $ul  = false;

                if (is_file(IMPATH.$id.$ext)) {
                    $ul = unlink(IMPATH.$id.$ext);
                }

                if (is_file(IMPATH.$id.'t'.$ext)) {
                    $ult = unlink(IMPATH.$id.'t'.$ext);
                }

                if (!$rsd or !$ul) {
                    $fail[] = $id;
                }
            }

            if ($fail) {
                $message = array(gTxt('image_delete_failed', array('{name}' => join(', ', $fail))), E_ERROR);
            } else {
                update_lastmod('image_deleted', compact('id'));
                $message = gTxt('image_deleted', array('{name}' => join(', ', $ids)));
            }
        }
    }

    image_list($message);
}

// -------------------------------------------------------------

function image_change_pageby()
{
    event_change_pageby('image');
    image_list();
}

// -------------------------------------------------------------

function thumbnail_create()
{
    global $prefs, $txp_user;

    extract(doSlash(gpsa(array('id', 'width', 'height'))));
    $id = assert_int($id);
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        image_list(gTxt('restricted_area'));

        return;
    }

    $width = (int) $width;
    $height = (int) $height;

    if ($width == 0) {
        $width = '';
    }

    if ($height == 0) {
        $height = '';
    }

    $crop = gps('crop');

    $prefs['thumb_w'] = $width;
    $prefs['thumb_h'] = $height;
    $prefs['thumb_crop'] = $crop;

    // Hidden prefs.
    set_pref('thumb_w', $width, 'image', 2);
    set_pref('thumb_h', $height, 'image', 2);
    set_pref('thumb_crop', $crop, 'image', 2);

    if ($width === '' && $height === '') {
        image_edit(array(gTxt('invalid_width_or_height'), E_ERROR), $id);

        return;
    }

    $t = new txp_thumb($id);
    $t->crop = ($crop == '1');
    $t->hint = '0';
    $t->width = $width;
    $t->height = $height;

    if ($t->write()) {
        $message = gTxt('thumbnail_saved', array('{id}' => $id));
        update_lastmod('thumbnail_created', compact('id', 'width', 'height', 'crop'));

        image_edit($message, $id);
    } else {
        $message = array(gTxt('thumbnail_not_saved', array('{id}' => $id)), E_ERROR);

        image_edit($message, $id);
    }
}

// -------------------------------------------------------------

function thumbnail_delete()
{
    global $txp_user;

    $id = assert_int(gps('id'));
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        image_list(gTxt('restricted_area'));

        return;
    }

    $t = new txp_thumb($id);

    if ($t->delete()) {
        callback_event('thumbnail_deleted', '', false, $id);
        update_lastmod('thumbnail_deleted', compact('id'));
        image_edit(gTxt('thumbnail_deleted'), $id);
    } else {
        image_edit(array(gTxt('thumbnail_delete_failed'), E_ERROR), $id);
    }
}

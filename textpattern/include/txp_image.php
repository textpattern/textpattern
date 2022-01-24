<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * Images panel.
 *
 * @package Admin\Image
 */

use Textpattern\Validator\CategoryConstraint;
use Textpattern\Validator\Validator;
use Textpattern\Search\Filter;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

global $extensions;
$extensions = get_safe_image_types();

include_once txpath.'/lib/class.thumb.php';

if ($event == 'image') {
    require_privs('image');

    global $all_image_cats, $all_image_authors;
    $all_image_cats = getTree('root', 'image');
    $all_image_authors = the_privileged('image.edit.own', true);

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
    global $file_max_upload_size, $txp_user, $event;

    pagetop(gTxt('tab_image'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('image_sort_column', 'id');
    } else {
        if (!in_array($sort, array('name', 'thumbnail', 'category', 'date', 'author'))) {
            $sort = 'id';
        }

        set_pref('image_sort_column', $sort, 'image', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('image_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('image_sort_dir', $dir, 'image', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    switch ($sort) {
        case 'name':
            $sort_sql = "txp_image.name $dir";
            break;
        case 'thumbnail':
            $sort_sql = "txp_image.thumbnail $dir, txp_image.id ASC";
            break;
        case 'category':
            $sort_sql = "txp_category.title $dir, txp_image.id ASC";
            break;
        case 'date':
            $sort_sql = "txp_image.date $dir, txp_image.id ASC";
            break;
        case 'author':
            $sort_sql = "txp_users.RealName $dir, txp_image.id ASC";
            break;
        default:
            $sort = 'id';
            $sort_sql = "txp_image.id $dir";
            break;
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'id' => array(
                'column' => 'txp_image.id',
                'label'  => gTxt('id'),
                'type'   => 'integer',
            ),
            'name' => array(
                'column' => 'txp_image.name',
                'label'  => gTxt('name'),
            ),
            'alt' => array(
                'column' => 'txp_image.alt',
                'label'  => gTxt('alt_text'),
            ),
            'caption' => array(
                'column' => 'txp_image.caption',
                'label'  => gTxt('caption'),
            ),
            'category' => array(
                'column' => array('txp_image.category', 'txp_category.title'),
                'label'  => gTxt('category'),
            ),
            'ext' => array(
                'column' => 'txp_image.ext',
                'label'  => gTxt('extension'),
            ),
            'author' => array(
                'column' => array('txp_image.author', 'txp_users.RealName'),
                'label'  => gTxt('author'),
            ),
            'thumbnail' => array(
                'column' => array('txp_image.thumbnail'),
                'label'  => gTxt('thumbnail'),
                'type'   => 'boolean',
            ),
        )
    );

    $alias_yes = '1, Yes';
    $alias_no = '0, No';
    $search->setAliases('thumbnail', array($alias_no, $alias_yes));

    list($criteria, $crit, $search_method) = $search->getFilter(array('id' => array('can_list' => true)));

    $search_render_options = array('placeholder' => 'search_images');

    $sql_from =
        safe_pfx_j('txp_image')."
        LEFT JOIN ".safe_pfx_j('txp_category')." ON txp_category.name = txp_image.category AND txp_category.type = 'image'
        LEFT JOIN ".safe_pfx_j('txp_users')." ON txp_users.name = txp_image.author";

    if ($crit === '') {
        $total = getCount('txp_image', $criteria);
    } else {
        $total = getThing("SELECT COUNT(*) FROM $sql_from WHERE $criteria");
    }

    $searchBlock =
        n.tag(
            $search->renderForm('image_list', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $createBlock = array();

    if (!is_dir(IMPATH) or !is_writeable(IMPATH)) {
        $createBlock[] =
            graf(
                span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                gTxt('img_dir_not_writeable', array('{imgdir}' => IMPATH)),
                array('class' => 'alert-block warning')
            );
    } elseif (has_privs('image.edit.own')) {
        $imagetypes = get_safe_image_types();
        $categories = event_category_popup('image', '', 'image_category');
        $createBlock[] =
            n.tag(
                n.upload_form('upload_image', 'upload_image', 'image_insert[]', 'image', '', $file_max_upload_size, '', 'async', '',
                    array('postinput' => ($categories
                        ? n.tag(
                            n.tag(gTxt('category'), 'label', array('for' => 'image_category')).$categories.n,
                            'span',
                            array('class' => 'inline-file-uploader-actions'))
                        : ''
                    )),
                    implode(',', $imagetypes)
                ),
                'div', array('class' => 'txp-control-panel')
            );
    }

    $createBlock = implode(n, $createBlock);
    $contentBlock = '';

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $paginator->getLimit();

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    if ($total < 1) {
        $contentBlock .= graf(
            span(null, array('class' => 'ui-icon ui-icon-info')).' '.
            gTxt($crit === '' ? 'no_images_recorded' : 'no_results_found'),
            array('class' => 'alert-block information')
        );
    } else {
        $rs = safe_query(
            "SELECT
                txp_image.id,
                txp_image.name,
                txp_image.category,
                txp_image.ext,
                txp_image.w,
                txp_image.h,
                txp_image.alt,
                txp_image.caption,
                UNIX_TIMESTAMP(txp_image.date) AS uDate,
                txp_image.author,
                txp_image.thumbnail,
                txp_image.thumb_w,
                txp_image.thumb_h,
                txp_users.RealName AS realname,
                txp_category.Title AS category_title
            FROM $sql_from WHERE $criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        $contentBlock .= pluggable_ui('image_ui', 'extend_controls', '', $rs);

        if ($rs && numRows($rs)) {
            $show_authors = !has_single_author('txp_image');

            $contentBlock .= n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'images_form',
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
                        'ID', 'id', 'image', true, $switch_dir, $crit, $search_method,
                            (('id' == $sort) ? "$dir " : '').'txp-list-col-id'
                    ).
                    column_head(
                        'name', 'name', 'image', true, $switch_dir, $crit, $search_method,
                            (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                    ).
                    column_head(
                        'date', 'date', 'image', true, $switch_dir, $crit, $search_method,
                            (('date' == $sort) ? "$dir " : '').'txp-list-col-created date'
                    ).
                    column_head(
                        'thumbnail', 'thumbnail', 'image', true, $switch_dir, $crit, $search_method,
                            (('thumbnail' == $sort) ? "$dir " : '').'txp-list-col-thumbnail'
                    ).
                    (has_privs('tag')
                        ? hCell(
                            gTxt('tags'), '', ' class="txp-list-col-tag-build" scope="col"'
                        )
                        : ''
                    ).
                    column_head(
                        'category', 'category', 'image', true, $switch_dir, $crit, $search_method,
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

                $name = empty($name) ? 'unnamed' : txpspecialchars($name);

                if ($thumbnail) {
                    if ($ext != '.swf') {
                        $thumbnail = '<img class="content-image" loading="lazy" src="'.imagesrcurl($id, $ext, true)."?$uDate".'" alt="'.$id.$ext.'" title="'.$id.$ext.'" />';
                        $thumbexists = 1;
                    } else {
                        $thumbnail = '';
                        $thumbexists = '';
                    }
                } else {
                    $thumbnail = gTxt('no');
                    $thumbexists = '';
                }

                if ($ext != '.swf') {
                    $tagName = 'image';
                    $tag_url = array(
                        'id'      => $id,
                        'ext'     => $ext,
                        'w'       => $w,
                        'h'       => $h,
                        'alt'     => urlencode($alt),
                        'caption' => urlencode($caption),
                        'step'    => 'build',
                    );

                    $tagbuilder = popTag($tagName, 'Textile', array('type' => 'textile') + $tag_url).
                        sp.span('&#124;', array('role' => 'separator')).
                        sp.popTag($tagName, 'Textpattern', array('type' => 'textpattern') + $tag_url).
                        sp.span('&#124;', array('role' => 'separator')).
                        sp.popTag($tagName, 'HTML', array('type' => 'html') + $tag_url);
                } else {
                    $tagbuilder = sp;
                }

                $validator->setConstraints(array(new CategoryConstraint($category, array('type' => 'image'))));
                $vc = $validator->validate() ? '' : ' error';

                if ($category) {
                    $category = span(txpspecialchars($category_title), array(
                        'title'      => $category,
                        'aria-label' => $category,
                    ));
                }

                $can_view = has_privs('image.edit.own');
                $can_edit = has_privs('image.edit') || ($author === $txp_user && $can_view);

                $contentBlock .= tr(
                    td(
                        $can_edit ? fInput('checkbox', 'selected[]', $id) : '&#160;', '', 'txp-list-col-multi-edit'
                    ).
                    hCell(
                        ($can_view ? href($id, $edit_url, array(
                            'title'      => gTxt('edit'),
                            'aria-label' => gTxt('edit'),
                        )) : $id)
                        , '', array(
                            'class' => 'txp-list-col-id',
                            'scope' => 'row',
                        )
                    ).
                    td(
                        ($can_view ? href($name, $edit_url, ' title="'.gTxt('edit').'"') : $name), '', 'txp-list-col-name txp-contain'
                    ).
                    td(
                        gTime($uDate), '', 'txp-list-col-created date'
                    ).
                    td(
                        pluggable_ui('image_ui', 'thumbnail', ($can_edit ? href($thumbnail, $edit_url, array(
                            'title'      => gTxt('edit'),
                            'aria-label' => gTxt('edit'),
                        )) : $thumbnail), $a), '', 'txp-list-col-thumbnail'.($thumbexists ? ' has-thumbnail' : '')
                    ).
                    (has_privs('tag')
                        ? td(
                            $tagbuilder, '', 'txp-list-col-tag-build'
                        )
                        : ''
                    ).
                    td(
                        $category, '', 'txp-list-col-category category'.$vc
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

            $contentBlock .= n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div'). // End of .txp-listtables.
                image_multiedit_form($page, $sort, $dir, $crit, $search_method).
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

/**
 * Renders a multi-edit form widget for images.
 *
 * @param  int    $page          The page number
 * @param  string $sort          The current sort value
 * @param  string $dir           The current sort direction
 * @param  string $crit          The current search criteria
 * @param  string $search_method The current search method
 * @return string HTML
 */

function image_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $all_image_cats, $all_image_authors;

    $categories = $all_image_cats ? treeSelectInput('category', $all_image_cats, '') : '';
    $authors = $all_image_authors ? selectInput('author', $all_image_authors, '', true) : '';

    $methods = array(
        'changecategory' => array(
            'label' => gTxt('changecategory'),
            'html'  => $categories,
        ),
        'changeauthor'   => array(
            'label' => gTxt('changeauthor'),
            'html'  => $authors,
        ),
        'delete'         => gTxt('delete'),
    );

    if (!$categories) {
        unset($methods['changecategory']);
    }

    if (has_single_author('txp_image') || !has_privs('image.edit')) {
        unset($methods['changeauthor']);
    }

    if (!has_privs('image.delete.own') && !has_privs('image.delete')) {
        unset($methods['delete']);
    }

    return multi_edit($methods, 'image', 'image_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

/**
 * Processes multi-edit actions.
 */

function image_multi_edit()
{
    global $txp_user, $all_image_cats, $all_image_authors;

    // Empty entry to permit clearing the category.
    $categories = array('');

    foreach ($all_image_cats as $row) {
        $categories[] = $row['name'];
    }

    $selected = ps('selected');

    if (!$selected || !is_array($selected)) {
        return image_list();
    }

    // Fetch and remove bogus (false) entries to prevent SQL syntax errors being thrown.
    $selected = array_map('assert_int', $selected);
    $selected = array_filter($selected);
    $method = ps('edit_method');
    $changed = array();
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
            if (has_privs('image.edit') && isset($all_image_authors[$val])) {
                $key = 'author';
            }
            break;
        default:
            $key = '';
            $val = '';
            break;
    }

    if (!has_privs('image.edit')) {
        if ($selected && has_privs('image.edit.own')) {
            $selected = safe_column("id", 'txp_image', "id IN (".join(',', $selected).") AND author = '".doSlash($txp_user)."'");
        } else {
            $selected = array();
        }
    }

    if ($selected && $key) {
        foreach ($selected as $id) {
            if (safe_update('txp_image', "$key = '".doSlash($val)."'", "id = '$id'")) {
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

/**
 * Renders and outputs the image editor panel.
 *
 * @param string|array $message The activity message
 * @param int          $id      The image ID
 */

function image_edit($message = '', $id = '')
{
    global $file_max_upload_size, $txp_user, $event, $all_image_cats;

    if (!$id) {
        $id = gps('id');
    }

    $id = assert_int($id);
    $rs = safe_row("*, UNIX_TIMESTAMP(date) AS uDate", 'txp_image', "id = '$id'");

    if ($rs) {
        extract($rs);

        if (!has_privs('image.edit') && !has_privs('image.edit.own')) {
            require_privs('image.edit');

            return;
        }

        pagetop(gTxt('edit_image'), $message);

        extract(gpsa(array(
            'page',
            'sort',
            'dir',
            'crit',
            'search_method',
        )));

        if ($ext != '.swf') {
            $aspect = ($h == $w) ? ' square' : (($h > $w) ? ' portrait' : ' landscape');
            $img_info = $id.$ext.' ('.$w.' &#215; '.$h.')';
            $img = '<div class="fullsize-image"><img class="content-image" src="'.imagesrcurl($id, $ext)."?$uDate".'" alt="'.$img_info.'" title="'.$img_info.'" /></div>';
        } else {
            $img = $aspect = '';
        }

        if ($thumbnail and ($ext != '.swf')) {
            $thumb_info = $id.'t'.$ext.' ('.$thumb_w.' &#215; '.$thumb_h.')';
            $thumb = '<img class="content-image" src="'.imagesrcurl($id, $ext, true)."?$uDate".'" alt="'.$thumb_info.'" title="'.$thumb_info.'" />';
        } else {
            $thumb = '';

            if ($thumb_w == 0) {
                $thumb_w = get_pref('thumb_w', 0);
            }

            if ($thumb_h == 0) {
                $thumb_h = get_pref('thumb_h', 0);
            }
        }

        $imageBlock = array();
        $thumbBlock = array();
        $can_edit = has_privs('image.edit') || ($author === $txp_user && has_privs('image.edit.own'));
        $can_upload = $can_edit && is_dir(IMPATH) && is_writeable(IMPATH);
        $imagetypes = get_safe_image_types();

        $imageBlock[] = ($can_upload
            ? pluggable_ui(
                'image_ui',
                'image_edit',
                upload_form('replace_image', 'replace_image_form', 'image_replace', 'image', $id, $file_max_upload_size, 'image-upload', ' image-replace', array('div', 'div'), '' , implode(',', $imagetypes)),
                $rs)
            : ''
        );

        $imageBlock[] = pluggable_ui(
                'image_ui',
                'fullsize_image',
                $img,
                $rs
            );

        $thumbBlock[] = ($can_upload
            ? hed(gTxt('create_thumbnail').popHelp('create_thumbnail'), 3)
            : hed(gTxt('thumbnail'), 3)
        );

        $thumbBlock[] = ($can_upload
            ? pluggable_ui(
            'image_ui',
            'thumbnail_edit',
            upload_form('upload_thumbnail', 'upload_thumbnail', 'thumbnail_insert', 'image', $id, $file_max_upload_size, 'thumbnail-upload', ' thumbnail-upload', array('div', 'div'), '' , $ext == '.jpg' ? '.jpg,.jpeg' : $ext),
            $rs)
            : ''
        );

        $thumbBlock[] = (check_gd($ext))
            ? ($can_upload
                ? pluggable_ui(
                    'image_ui',
                    'thumbnail_create',
                    form(
                        graf(
                            n.'<label for="width">'.gTxt('width').'</label>'.
                            fInput('text', 'width', @$thumb_w, 'input-xsmall', '', '', INPUT_XSMALL, '', 'width').
                            n.'<a class="thumbnail-swap-size">'.gTxt('swap_values').'</a>'.
                            n.'<label for="height">'.gTxt('height').'</label>'.
                            fInput('text', 'height', @$thumb_h, 'input-xsmall', '', '', INPUT_XSMALL, '', 'height').
                            n.'<label for="crop">'.gTxt('keep_square_pixels').'</label>'.
                            checkbox('crop', 1, get_pref('thumb_crop'), '', 'crop').
                            fInput('submit', '', gTxt('create')), ' class="edit-alter-thumbnail"'
                        ).
                        hInput('id', $id).
                        eInput('image').
                        sInput('thumbnail_create').
                        hInput('sort', $sort).
                        hInput('dir', $dir).
                        hInput('page', $page).
                        hInput('search_method', $search_method).
                        hInput('crit', $crit), '', '', 'post', '', '', 'thumbnail_alter_form'),
                    $rs)
                : ''
            )
            : '';

        $thumbBlock[] = pluggable_ui(
            'image_ui',
            'thumbnail_image',
            '<div class="thumbnail-image">'.
            (($thumbnail)
                ? $thumb.n.($can_upload
                    ? dLink('image', 'thumbnail_delete', 'id', $id, '', '', '', '', array($page, $sort, $dir, $crit, $search_method))
                    :'')
                : gTxt('none')).
            '</div>',
            $rs
        );

        echo n.'<div class="txp-layout">'.
            n.tag(
                hed(gTxt('edit_image'), 1, array('class' => 'txp-heading')),
                'div', array('class' => 'txp-layout-1col')
            ).
            n.tag(
                form(
                    wrapGroup(
                        'image-details',
                        inputLabel(
                            'id',
                            $id,
                            'id', '', array('class' => 'txp-form-field edit-image-id')
                        ).
                        inputLabel(
                            'image_name',
                            tag_void('input', array(
                                'id'       => 'image_name',
                                'name'     => 'name',
                                'size'     => INPUT_REGULAR,
                                'value'    => $name,
                                'type'     => 'text',
                                'readonly' => !$can_edit,
                            )),
                            'image_name', '', array('class' => 'txp-form-field edit-image-name')
                        ).
                        inputLabel(
                            'image_category',
                            ($can_edit
                                ? event_category_popup('image', $category, 'image_category').
                                    n.eLink('category', 'list', '', '', gTxt('edit'), '', '', '', 'txp-option-link')
                                : (($category !== '') ? fetch_category_title($category, 'image') : gTxt('none'))
                            ),
                            'category', '', array('class' => 'txp-form-field edit-image-category')
                        ).
                        inputLabel(
                            'image_alt_text',
                            tag_void('input', array(
                                'id'       => 'image_alt_text',
                                'name'     => 'alt',
                                'size'     => INPUT_REGULAR,
                                'value'    => $alt,
                                'type'     => 'text',
                                'readonly' => !$can_edit,
                            )),
                            'alt_text', '', array('class' => 'txp-form-field edit-image-alt-text')
                        ).
                        inputLabel(
                            'image_caption',
                            '<textarea id="image_caption" name="caption" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'"'.($can_edit ? '' : ' readonly="readonly"').'>'.htmlspecialchars($caption, ENT_NOQUOTES).'</textarea>',
                            'caption', '', array('class' => 'txp-form-field txp-form-field-textarea edit-image-caption')
                        ).
                        pluggable_ui('image_ui', 'extend_detail_form', '', $rs).
                        graf(
                            href(gTxt('go_back'), array(
                                'event'         => 'image',
                                'sort'          => $sort,
                                'dir'           => $dir,
                                'page'          => $page,
                                'search_method' => $search_method,
                                'crit'          => $crit,
                            ), array('class' => 'txp-button')).
                            ($can_edit
                                ? fInput('submit', '', gTxt('save'), 'publish')
                                : ''
                            ),
                            array('class' => 'txp-edit-actions')
                        ).
                        hInput('id', $id).
                        eInput('image').
                        sInput('image_save').
                        hInput('sort', $sort).
                        hInput('dir', $dir).
                        hInput('page', $page).
                        hInput('search_method', $search_method).
                        hInput('crit', $crit),
                        'image_details'
                    ),
                    '', '', 'post', '', '', 'image_details_form'),
                'div', array('class' => 'txp-layout-4col-alt')
            ).
            n.tag(
                n.implode(n, $imageBlock).
                n.'<hr />'.
                n.tag(implode(n, $thumbBlock), 'section', array('class' => 'thumbnail-alter')),
                'div', array('class' => 'txp-layout-4col-3span')
            ).
            n.'</div>'; // End of .txp-layout.
    } else {
        image_list(array(gTxt('unknown_image'), E_ERROR));
    }
}

/**
 * Creates a new image from an upload.
 */

function image_insert()
{
    if (!has_privs('image.edit.own')) {
        require_privs('image.edit.own');

        return;
    }

    global $app_mode, $event;
    $messages = $ids = array();
    $fileshandler = Txp::get('\Textpattern\Server\Files');
    $files = $fileshandler->refactor($_FILES['thefile']);
    $meta = gpsa(array('caption', 'alt', 'category'));

    foreach ($files as $i => $file) {
        $chunked = $fileshandler->dechunk($file);
        $img_result = image_data($file, $meta, 0, !$chunked);

        if (is_file($file['tmp_name'])) {
            unlink(realpath($file['tmp_name']));
        }

        if (is_array($img_result)) {
            list($message, $id) = $img_result;
            $ids[] = $id;
            $messages[] = array($message, 0);
        } else {
            $messages[] = array($img_result, E_ERROR);
        }
    }

    if ($app_mode == 'async') {
        $response = !empty($ids) ? 'textpattern.Relay.data.fileid = ["'.implode('","', $ids).'"].concat(textpattern.Relay.data.fileid || []);'.n : '';

        foreach ($messages as $message) {
            $response .= 'textpattern.Console.addMessage('.json_encode((array) $message, TEXTPATTERN_JSON).', "uploadEnd");'.n;
        }

        send_script_response($response);

        // Bail out.
        return;
    }

    if (is_array($img_result)) {
        list($message, $id) = $img_result;

        return image_edit($message, $id);
    } else {
        return image_list(array($img_result, E_ERROR));
    }
}

/**
 * Replaces an image with one from an upload.
 */

function image_replace()
{
    global $txp_user;

    $id = assert_int(gps('id'));
    $rs = safe_row("*", 'txp_image', "id = '$id'");

    if (!has_privs('image.edit') && !($rs['author'] === $txp_user && has_privs('image.edit.own'))) {
        require_privs('image.edit');

        return;
    }

    if ($rs) {
        $meta = array(
            'category' => $rs['category'],
            'caption'  => $rs['caption'],
            'alt'      => $rs['alt'],
        );
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

/**
 * Creates a new thumbnail from an upload.
 */

function thumbnail_insert()
{
    global $extensions, $txp_user;

    $id = assert_int(gps('id'));
    $author = fetch('author', 'txp_image', 'id', '$id');

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        require_privs('image.edit');

        return;
    }

    $file = $_FILES['thefile']['tmp_name'];
    $name = $_FILES['thefile']['name'];
    $file = get_uploaded_file($file);
    $extension = 0;

    if (empty($file)) {
        image_edit(array(upload_get_errormsg(UPLOAD_ERR_NO_FILE), E_ERROR), $id);

        return;
    }

    if ($imagesize = txpimagesize($file)) {
        list($w, $h, $extension) = $imagesize;
    }

    if (($file !== false) && !empty($extensions[$extension])) {
        $ext = $extensions[$extension];
        $newpath = IMPATH.$id.'t'.$ext;

        if (shift_uploaded_file($file, $newpath) == false) {
            image_edit(array(gTxt('directory_permissions', array('{path}' => $newpath)), E_ERROR), $id);
        } else {
            chmod($newpath, 0644);
            safe_update('txp_image', "thumbnail = 1, thumb_w = $w, thumb_h = $h, date = NOW()", "id = '$id'");

            $message = gTxt('image_uploaded', array('{name}' => $name));
            update_lastmod('thumbnail_created', compact('id', 'w', 'h'));

            image_edit($message, $id);
        }
    } else {
        if ($file === false) {
            image_edit(array(upload_get_errormsg($_FILES['thefile']['error']), E_ERROR), $id);
        } else {
            image_edit(array(gTxt('only_graphic_files_allowed', array('{formats}' => join(', ', $extensions))), E_ERROR), $id);
        }
    }
}

/**
 * Saves image meta data.
 */

function image_save()
{
    global $txp_user;

    $varray = array_map('assert_string', gpsa(array('id', 'name', 'category', 'caption', 'alt')));
    extract(doSlash($varray));
    $id = $varray['id'] = assert_int($id);
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        require_privs('image.edit');

        return;
    }

    $constraints = array('category' => new CategoryConstraint(gps('category'), array('type' => 'image')));
    callback_event_ref('image_ui', 'validate_save', 0, $varray, $constraints);
    $validator = new Validator($constraints);

    if ($validator->validate() && safe_update(
        'txp_image',
        "name    = '$name',
        category = '$category',
        alt      = '$alt',
        caption  = '$caption'",
        "id = '$id'"
    )) {
        $message = gTxt('image_updated', array('{name}' => doStrip($name)));
        update_lastmod('image_saved', compact('id', 'name', 'category', 'alt', 'caption'));
    } else {
        $message = array(gTxt('image_save_failed'), E_ERROR);
    }

    image_list($message);
}

/**
 * Deletes the given image(s) from the database and filesystem.
 *
 * @param array $ids List of image IDs to delete
 */

function image_delete($ids = array())
{
    global $txp_user, $event;

    $message = '';

    // Fetch ids and remove bogus (false) entries to prevent SQL syntax errors being thrown.
    $ids = $ids ? array_map('assert_int', $ids) : array(assert_int(ps('id')));
    $ids = array_filter($ids);

    if (!has_privs('image.delete')) {
        if ($ids && has_privs('image.delete.own')) {
            $ids = safe_column("id", 'txp_image', "id IN (".join(',', $ids).") AND author = '".doSlash($txp_user)."'");
        } else {
            $ids = array();
        }
    }

    if (!empty($ids)) {
        $fail = array();
        $rs   = safe_rows_start("id, ext", 'txp_image', "id IN (".join(',', $ids).")");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);

                // Notify plugins of pending deletion, pass image's $id.
                callback_event('image_deleted', $event, false, $id);

                $rsd = safe_delete('txp_image', "id = '$id'");
                $ul = false;

                if (is_file(IMPATH.$id.$ext)) {
                    $ul = unlink(realpath(IMPATH.$id.$ext));
                }

                if (is_file(IMPATH.$id.'t'.$ext)) {
                    $ult = unlink(realpath(IMPATH.$id.'t'.$ext));
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

/**
 * Saves pageby value for the image list.
 */

function image_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    image_list();
}

/**
 * Creates a new thumbnail from an existing image.
 */

function thumbnail_create()
{
    global $prefs, $txp_user;

    extract(doSlash(gpsa(array('id', 'width', 'height'))));
    $id = assert_int($id);
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        require_privs('image.edit');

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
    set_pref('thumb_w', $width, 'image', PREF_HIDDEN);
    set_pref('thumb_h', $height, 'image', PREF_HIDDEN);
    set_pref('thumb_crop', $crop, 'image', PREF_HIDDEN);

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

/**
 * Delete a thumbnail.
 */

function thumbnail_delete()
{
    global $txp_user;

    $id = assert_int(gps('id'));
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        require_privs('image.edit');

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

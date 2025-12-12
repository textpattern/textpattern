<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2025 The Textpattern Development Team
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
use lencioni\SLIR\SLIR;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

global $extensions;
$extensions = get_safe_image_types();

include_once txpath . '/lib/class.thumb.php';

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

    $plugin_steps = array();
    callback_event_ref('image', 'steps', 0, $plugin_steps);

    // Available steps overwrite custom ones to prevent plugins trampling
    // core routines.
    if ($step && bouncer($step, array_merge($plugin_steps, $available_steps))) {
        if (array_key_exists($step, $available_steps)) {
            $step();
        } else {
            callback_event($event, $step, 0);
        }
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
    global $app_mode, $file_max_upload_size, $txp_user, $event, $theme;

    // Garbage collect old image verification tokens.
    Txp::get('\Textpattern\Security\Token')->remove('image_verify', null, THUMB_VALIDITY_SECONDS . ' SECOND');

    $show_authors = !has_single_author('txp_image');

    $fields = array(
        'id' => array(
            'column' => 'txp_image.id',
            'label' => 'id',
        ),
        'name' => array(
            'column' => 'txp_image.name',
            'label' => 'name',
            'class' => 'name',
        ),
        'uDate' => array(
            'column' => 'TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(0), txp_image.date)',
            'label' => 'date',
            'class'  => 'date',
        ),
        'thumbnail' => array(
            'column' => 'txp_image.thumbnail',
            'label' => 'thumbnail',
            'class' => 'thumbnail',
        ),
        'tags' => array(
            'column' => '',
            'label' => 'tags',
            'class' => 'tag-build',
            'visible' => has_privs('tag'),
        ),
        'category' => array(
            'column' => 'txp_image.category',
            'label' => 'category',
            'class' => 'category',
        ),
        'author' => array(
            'column' => 'txp_image.author',
            'label' => 'author',
            'class' => 'author name',
            'visible' => $show_authors,
        ),
        'ext' => array(
            'column' => 'txp_image.ext',
            'visible' => false,
        ),
        'w' => array(
            'column' => 'txp_image.w',
            'visible' => false,
        ),
        'h' => array(
            'column' => 'txp_image.h',
            'visible' => false,
        ),
        'alt' => array(
            'column' => 'txp_image.alt',
            'visible' => false,
        ),
        'caption' => array(
            'column' => 'txp_image.caption',
            'visible' => false,
        ),
        'thumb_w' => array(
            'column' => 'txp_image.thumb_w',
            'visible' => false,
        ),
        'thumb_h' => array(
            'column' => 'txp_image.thumb_h',
            'visible' => false,
        ),
        'realname' => array(
            'column' => 'txp_users.RealName',
            'visible' => false,
        ),
        'category_title' => array(
            'column' => 'txp_category.Title',
            'visible' => false,
        ),
    );

    $sql_from =
        safe_pfx_j('txp_image') . "
        LEFT JOIN " . safe_pfx_j('txp_category') . " ON txp_category.name = txp_image.category AND txp_category.type = 'image'
        LEFT JOIN " . safe_pfx_j('txp_users') . " ON txp_users.name = txp_image.author";

    callback_event_ref($event, 'fields', 'list', $fields);
    callback_event_ref($event, 'from', 'list', $sql_from);

    $fieldlist = array();

    // Build field list, excluding empty columns.
    foreach ($fields as $fld => $def) {
        if (!isset($def['column'])) {
            $fieldlist[] = $fld;
        } elseif (!empty($def['column'])) {
            $fieldlist[] = $def['column'] . ' AS ' . $fld;
        }
    }

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
    }

    if (!in_array($sort, array_keys(array_filter($fields, function($value) {
            return !isset($value['sortable']) || !empty($value['sortable']);
        })))
    ) {
        $sort = 'id';
    }

    set_pref('image_sort_column', $sort, 'image', PREF_HIDDEN, '', 0, PREF_PRIVATE);

    if ($dir === '') {
        $dir = get_pref('image_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('image_sort_dir', $dir, 'image', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    $sort_sql = $sort . ' ' . $dir . ($sort == 'id' ? '' : ", txp_image.id $dir");

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter(
        $event,
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
            'date' => array(
                'column'  => array('txp_image.date'),
                'label'   => gTxt('date'),
                'options' => array('case_sensitive' => true),
            ),
            'thumbnail' => array(
                'column' => array('txp_image.thumbnail'),
                'label'  => gTxt('thumbnail'),
                'type'   => 'integer',
            ),
        )
    );

    $alias_no = THUMB_NONE . ', No';
    $alias_yes = THUMB_CUSTOM . ', Yes, Custom';
    $alias_auto = THUMB_AUTO . ', Auto';
    $search->setAliases('thumbnail', array($alias_no, $alias_yes, $alias_auto));

    list($criteria, $crit, $search_method) = $search->getFilter(array('id' => array('can_list' => true)));

    $search_render_options = array('placeholder' => 'search_images');

    $total = (int)getThing("SELECT COUNT(*) FROM $sql_from WHERE $criteria");

    $searchBlock =
        n . tag(
            $search->renderForm('image_list', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event . '_control',
            )
        );

    $buttons = array();

    if (has_privs('article.edit.own')) {
        $buttons[] = sLink('article', '', gTxt('create_article'), 'txp-button');
    }

    callback_event_ref($event, 'controls', 'panel', $buttons);

    $createBlock = array();

    if (!is_dir(IMPATH) or !is_writeable(IMPATH)) {
        $createBlock[] =
            graf(
                span(null, array('class' => 'ui-icon ui-icon-alert')) . ' ' .
                gTxt('img_dir_not_writeable', array('{imgdir}' => IMPATH)),
                array('class' => 'alert-block warning')
            );
    } elseif (has_privs('image.edit.own')) {
        $imagetypes = get_safe_image_types();
        $categories = event_category_popup('image', '', 'image_category');
        $createBlock[] =
            n . tag(
                n . upload_form(
                    'upload_image', 'upload_image', 'image_insert[]', 'image', '', $file_max_upload_size, '', 'async', '',
                    array('postinput' => ($categories
                        ? n . tag(
                            n . tag(gTxt('category'), 'label', array('for' => 'image_category')) . $categories . n,
                            'span',
                            array('class' => 'inline-file-uploader-actions')
                        )
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
        if ($app_mode == 'json') {
            send_json_response(array());
            exit;
        }

        $contentBlock .= graf(
            span(null, array('class' => 'ui-icon ui-icon-info')) . ' ' .
            gTxt($crit === '' ? 'no_images_recorded' : 'no_results_found'),
            array('class' => 'alert-block information')
        );
    } else {
        $rs = safe_query(
            "SELECT " . implode(', ', $fieldlist) .
            " FROM $sql_from " .
            " WHERE $criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        if ($app_mode == 'json') {
            send_json_response($rs);
            exit;
        }

        $contentBlock .= pluggable_ui('image_ui', 'extend_controls', '', $rs);

        if ($rs && numRows($rs)) {
            $contentBlock .= n . tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'images_form',
                    'name'   => 'longform',
                    'method' => 'post',
                    'action' => 'index.php',
                )) .
                n . tag_start('div', array(
                    'class'      => 'txp-listtables',
                    'tabindex'   => 0,
                    'aria-label' => gTxt('list'),
                )) .
                n . tag_start('table', array('class' => 'txp-list')) .
                n . tag_start('thead');

                $headings = array();
                $headings[] = hCell(
                        fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                        '', ' class="txp-list-col-multi-edit" scope="col" title="' . gTxt('toggle_all_selected') . '"'
                );

                foreach ($fields as $col => $opts) {
                    if (isset($opts['visible']) && empty($opts['visible'])) {
                        continue;
                    }

                    $lbl = empty($opts['label']) ? $col : $opts['label'];
                    $cls = empty($opts['class']) ? $col : $opts['class'];
                    $clsSuffix = strtolower(str_replace('_', '-', $lbl));

                    if (empty($opts['column'])) {
                        $headings[] = hCell(gTxt($lbl), '', ' class="txp-list-col-' . $clsSuffix . ' ' . $cls . '" scope="col"');
                    } else {
                        $headings[] = column_head(
                            $lbl,
                            $col,
                            'image',
                            true,
                            $switch_dir,
                            '',
                            '',
                            (($col == $sort) ? "$dir " : '') .
                                'txp-list-col-' . $clsSuffix . ' ' . $cls
                        );
                    }
                }

                $contentBlock .= tr(
                    implode(n, $headings)
                ) .
                n . tag_end('thead') .
                n . tag_start('tbody');

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
                $payload = array(
                    'id' => $id,
                    'ext' => $ext,
                    'w' => $theme->get_pref('thumb_width', TEXTPATTERN_THUMB_WIDTH),
                    'h' => $theme->get_pref('thumb_height', TEXTPATTERN_THUMB_HEIGHT),
                    'c' => $theme->get_pref('thumb_cropping', TEXTPATTERN_THUMB_CROPPING),
                );

                $thumb_w = $thumbnail == THUMB_AUTO ? $payload['w'] : $thumb_w;
                $thumb_h = $thumbnail == THUMB_AUTO ? $payload['h'] : $thumb_h;

                if ($ext != '.swf') {
                    $altinfo = !empty($alt) ? txpspecialchars($alt) : $id . $ext;
                    $thumbnail = '<img class="content-image" loading="lazy" src="' . imageBuildURL($payload, $thumbnail) . ($thumbnail === THUMB_CUSTOM ? "?$uDate" : '') . '" alt="' . $altinfo . '" height="' . $thumb_h . '" width="' . $thumb_w . '"/>';
                    $thumbexists = 1;
                } else {
                    $thumbnail = '';
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

                    $tagbuilder = popTag($tagName, 'Textile', array('type' => 'textile') + $tag_url) .
                        sp . span('&#124;', array('role' => 'separator')) .
                        sp . popTag($tagName, 'Textpattern', array('type' => 'textpattern') + $tag_url) .
                        sp . span('&#124;', array('role' => 'separator')) .
                        sp . popTag($tagName, 'HTML', array('type' => 'html') + $tag_url);
                } else {
                    $tagbuilder = sp;
                }

                $validator->setConstraints(array(new CategoryConstraint($category, array('type' => 'image'))));
                $vc = $validator->validate() ? '' : ' error';

                if ($category) {
                    $category = span(txpspecialchars($category_title), array('title' => $category));
                }

                $can_view = has_privs('image.edit.own');
                $can_edit = has_privs('image.edit') || ($author === $txp_user && $can_view);

                $contentBlock .= tr(
                    td(
                        $can_edit ? fInput('checkbox', 'selected[]', $id) : '&#160;', '', 'txp-list-col-multi-edit'
                    ) .
                    hCell(
                        ($can_view ? href($id, $edit_url, array('title' => gTxt('edit'))) : $id),
                        '', array(
                            'class' => 'txp-list-col-id',
                            'scope' => 'row',
                        )
                    ) .
                    td(
                        ($can_view ? href($name, $edit_url, ' title="' . gTxt('edit') . '"') : $name), '', 'txp-list-col-name txp-contain'
                    ) .
                    td(
                        gTime($uDate), '', 'txp-list-col-created date'
                    ) .
                    td(
                        pluggable_ui('image_ui', 'thumbnail', ($can_edit
                            ? href($thumbnail, $edit_url, array('title' => gTxt('edit')))
                            : $thumbnail), $a), '', 'txp-list-col-thumbnail' . ($thumbexists ? ' has-thumbnail' : '')
                    ) .
                    (has_privs('tag')
                        ? td($tagbuilder, '', 'txp-list-col-tag-build')
                        : ''
                    ) .
                    td(
                        $category, '', 'txp-list-col-category category' . $vc
                    ) .
                    (
                        $show_authors
                        ? td(span(txpspecialchars($realname), array('title' => $author)), '', 'txp-list-col-author name')
                        : ''
                    ) .
                    pluggable_ui('image_ui', 'list.row', '', $a)
                );
            }

            $contentBlock .= n . tag_end('tbody') .
                n . tag_end('table') .
                n . tag_end('div') . // End of .txp-listtables.
                image_multiedit_form($page, $sort, $dir, $crit, $search_method) .
                tInput() .
                n . tag_end('form');
        }
    }

    $pageBlock = $paginator->render() .
        nav_form($event, $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table($event);
    echo $table->render(compact('total', 'crit'), $searchBlock, $createBlock, $contentBlock, $pageBlock) .
        n . tag(
            null,
            'div', array(
            'class' => 'txp-tagbuilder-content',
            'id'    => 'tagbuild_links',
            'title' => gTxt('tagbuilder'),
            )
        );
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
    $thumbTypes = thumb_type_select('thumbtype', get_pref(''));

    $methods = array(
        'changecategory' => array(
            'label' => gTxt('changecategory'),
            'html'  => $categories,
        ),
        'changeauthor' => array(
            'label' => gTxt('changeauthor'),
            'html'  => $authors,
        ),
        'changethumb' => array(
            'label' => gTxt('changethumb'),
            'html'  => $thumbTypes,
        ),
        'delete' => gTxt('delete'),
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
        case 'changethumb':
            $val = ps('thumbtype');
            if (has_privs('image.edit')) {
                $key = 'thumbnail';
            }
            break;
        default:
            $key = '';
            $val = '';
            break;
    }

    if (!has_privs('image.edit')) {
        if ($selected && has_privs('image.edit.own')) {
            $selected = safe_column("id", 'txp_image', "id IN (" . join(',', $selected) . ") AND author = '" . doSlash($txp_user) . "'");
        } else {
            $selected = array();
        }
    }

    if ($selected && $key) {
        foreach ($selected as $id) {
            if (safe_update('txp_image', "$key = '" . doSlash($val) . "'", "id = '$id'")) {
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
    global $file_max_upload_size, $txp_user, $event, $all_image_cats, $theme;

    if (!$id) {
        $id = gps('id');
    }

    $id = assert_int($id);
    $rs = safe_row("*, TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(0), date) AS uDate", 'txp_image', "id = '$id'");

    if ($rs) {
        extract($rs);

        if (!has_privs('image.edit') && !has_privs('image.edit.own')) {
            require_privs('image.edit');

            return;
        }

        pagetop(gTxt('edit_image'), $message);

        $fieldSizes = Txp::get('\Textpattern\DB\Core')->columnSizes('txp_image', 'name, alt');

        extract(gpsa(array(
            'page',
            'sort',
            'dir',
            'crit',
            'search_method',
            'publish_now',
        )));

        $payload = array(
            'id' => $id,
            'ext' => $ext,
        );

        if ($ext != '.swf') {
            $aspect = ($h == $w) ? ' square' : (($h > $w) ? ' portrait' : ' landscape');
            $img = '<div id="fullsize-image" class="fullsize-image"><img class="content-image" src="' . imageBuildURL($payload) . "?$uDate" . '" alt="' . $id . $ext . '" /></div>';
        } else {
            $img = $aspect = '';
        }

        $payload['w'] = $theme->get_pref('thumb_width', TEXTPATTERN_THUMB_WIDTH);
        $payload['h'] = $theme->get_pref('thumb_height', TEXTPATTERN_THUMB_HEIGHT);
        $payload['c'] = $theme->get_pref('thumb_cropping', TEXTPATTERN_THUMB_CROPPING);

        $canThumb = !in_array($ext, array('.swf', '.svg'));

        if ($thumbnail && $canThumb) {
            $thumb_info = $id . ($thumbnail == THUMB_CUSTOM ? 't' : '') . $ext;
            $thumb = '<img class="content-image" src="' . imageBuildURL($payload, $thumbnail) . ($thumbnail == THUMB_CUSTOM ? "?$uDate" : '') . '" alt="' . $thumb_info . '" />';
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
        $can_delete = has_privs('image.delete') || ($author == $txp_user && has_privs('image.delete.own'));
        $can_upload = $can_edit && is_dir(IMPATH) && is_writeable(IMPATH);
        $imagetypes = get_safe_image_types();
        $delete = ($can_delete)
            ? form(
                eInput('image') .
                sInput('image_multi_edit') .
                hInput('edit_method', 'delete') .
                hInput('selected[]', $id),
                '', '', 'post', '', '', 'delete-image'
            )
            : '';

        $imageBlock[] = ($can_upload
            ? pluggable_ui(
                'image_ui',
                'image_edit',
                upload_form('replace_image', 'replace_image_form', 'image_replace', 'image', $id, $file_max_upload_size, 'image-upload', 'async image-replace', array('div', 'div'), '', implode(',', $imagetypes)),
                $rs
            )
            : ''
        ) . $delete;

        $imageBlock[] = pluggable_ui(
            'image_ui',
            'fullsize_image',
            $img,
            $rs
        );

        if ($canThumb) {
            $thumbBlock[] = ($can_upload
                ? hed(gTxt('create_thumbnail') . popHelp('create_thumbnail'), 3)
                : hed(gTxt('thumbnail'), 3)
            );
            $thumbBlock[] = tag(Txp::get('\Textpattern\UI\Label', gTxt('thumbnail_type'), 'thumbnail_type').sp.thumb_type_select('thumbnail_type', $thumbnail), 'p', array('class' => 'txp-thumb-type'));
            $thumbBlock[] = '<div class="thumbtype_1'.($thumbnail != THUMB_CUSTOM ? " hidden" : "").'">';

            $thumbBlock[] = ($can_upload
                ? pluggable_ui(
                    'image_ui',
                    'thumbnail_edit',
                    upload_form('upload_thumbnail', 'upload_thumbnail', 'thumbnail_insert', 'image', $id, $file_max_upload_size, 'thumbnail-upload', ' thumbnail-upload', array('div', 'div'), '', $ext == '.jpg' ? '.jpg,.jpeg' : $ext),
                    $rs
                )
                : ''
            );

            $thumbBlock[] = (check_gd($ext))
                ? ($can_upload
                    ? pluggable_ui(
                        'image_ui',
                        'thumbnail_create',
                        form(
                            graf(
                                n . '<label for="width">' . gTxt('width') . '</label>' .
                                fInput('text', 'width', $thumb_w, 'input-xsmall', '', '', INPUT_XSMALL, '', 'width') .
                                n . '<a class="thumbnail-swap-size">' . gTxt('swap_values') . '</a>' .
                                n . '<label for="height">' . gTxt('height') . '</label>' .
                                fInput('text', 'height', $thumb_h, 'input-xsmall', '', '', INPUT_XSMALL, '', 'height') .
                                n . '<label for="crop">' . gTxt('keep_square_pixels') . '</label>' .
                                checkbox('crop', 1, get_pref('thumb_crop'), '', 'crop') .
                                fInput('submit', '', gTxt('create')), ' class="edit-alter-thumbnail"'
                            ) .
                            hInput('id', $id) .
                            eInput('image') .
                            sInput('thumbnail_create') .
                            hInput('sort', $sort) .
                            hInput('dir', $dir) .
                            hInput('page', $page) .
                            hInput('search_method', $search_method) .
                            hInput('crit', $crit), '', '', 'post', '', '', 'thumbnail_alter_form'
                        ),
                        $rs
                    )
                    : ''
                )
                : '';
            $thumbBlock[] = '</div>';
            $thumbBlock[] = '<div class="thumbtype_1 thumbtype_2">';
            $thumbBlock[] = pluggable_ui(
                'image_ui',
                'thumbnail_image',
                '<div id="thumbnail-image" class="thumbnail-image">' .
                (($thumbnail)
                    ? $thumb . n . ($can_upload
                        ? dLink('image', 'thumbnail_delete', 'id', $id, '', '', '', '', array($page, $sort, $dir, $crit, $search_method))
                        : '')
                    : gTxt('none')) .
                '</div>',
                $rs
            );
            $thumbBlock[] = '</div>';
        }

        $created =
            inputLabel(
                'year',
                tsi('year', '%Y', $uDate, '', 'year') .
                ' <span role="separator">/</span> ' .
                tsi('month', '%m', $uDate, '', 'month') .
                ' <span role="separator">/</span> ' .
                tsi('day', '%d', $uDate, '', 'day'),
                'publish_date',
                array('timestamp_image', 'instructions_image_date'),
                array('class' => 'txp-form-field date posted')
            ) .
            inputLabel(
                'hour',
                tsi('hour', '%H', $uDate, '', 'hour') .
                ' <span role="separator">:</span> ' .
                tsi('minute', '%M', $uDate, '', 'minute') .
                ' <span role="separator">:</span> ' .
                tsi('second', '%S', $uDate, '', 'second'),
                'publish_time',
                array('', 'instructions_image_time'),
                array('class' => 'txp-form-field time posted')
            ) .
            n . tag(
                checkbox('publish_now', '1', $publish_now, '', 'publish_now') .
                n . tag(gTxt('set_to_now'), 'label', array('for' => 'publish_now')),
                'div', array('class' => 'txp-form-field posted-now')
            );

        echo n . '<div class="txp-layout">' .
            n . tag(
                hed(gTxt('edit_image'), 1, array('class' => 'txp-heading')),
                'div', array('class' => 'txp-layout-1col')
            ) .
            n . tag(
                form(
                    wrapGroup(
                        'image-details',
                        inputLabel(
                            'id',
                            $id,
                            'id', '', array('class' => 'txp-form-field edit-image-id')
                        ) .
                        inputLabel(
                            'dimensions',
                            $w . 'px &#215; ' . $h . 'px',
                            'dimensions', '', array('class' => 'txp-form-field edit-image-dimensions')
                        ) .
                        inputLabel(
                            'image_name',
                            Txp::get('\Textpattern\UI\Input', 'name', 'text', $name)->setAtts(array(
                                'id'        => 'image_name',
                                'size'      => INPUT_REGULAR,
                                'maxlength' => $fieldSizes['name'],
                                'readonly'  => !$can_edit,
                            )),
                            'image_name', '', array('class' => 'txp-form-field edit-image-name')
                        ) .
                        inputLabel(
                            'image_category',
                            ($can_edit
                                ? event_category_popup('image', $category, 'image_category') .
                                    n . eLink('category', 'list', '', '', gTxt('edit'), '', '', '', 'txp-option-link')
                                : (($category !== '') ? fetch_category_title($category, 'image') : gTxt('none'))
                            ),
                            'category', '', array('class' => 'txp-form-field edit-image-category')
                        ) .
                        inputLabel(
                            'image_alt_text',
                            Txp::get('\Textpattern\UI\Input', 'alt', 'text', $alt)->setAtts(array(
                                'id'        => 'image_alt_text',
                                'size'      => INPUT_REGULAR,
                                'maxlength' => $fieldSizes['alt'],
                                'readonly'  => !$can_edit,
                            )),
                            'alt_text', '', array('class' => 'txp-form-field edit-image-alt-text')
                        ) .
                        inputLabel(
                            'image_caption',
                            '<textarea id="image_caption" name="caption" cols="' . INPUT_LARGE . '" rows="' . TEXTAREA_HEIGHT_SMALL . '"' . ($can_edit ? '' : ' readonly="readonly"') . '>' . htmlspecialchars($caption, ENT_NOQUOTES) . '</textarea>',
                            'caption', '', array('class' => 'txp-form-field txp-form-field-textarea edit-image-caption')
                        ) .
                        hInput('thumbnail', ($canThumb ? $thumbnail : THUMB_AUTO)) .
                        pluggable_ui('image_ui', 'extend_detail_form', '', $rs) .
                        $created.
                        graf(
                            ($can_delete
                                ? tag_void('input', array(
                                    'class'   => 'caution',
                                    'name'    => 'image_delete',
                                    'type'    => 'submit',
                                    'form'    => 'delete-image',
                                    'value'   =>  gTxt('delete'),
                                ))
                                : ''
                            ) .
                            href(gTxt('go_back'), array(
                                'event'         => 'image',
                                'sort'          => $sort,
                                'dir'           => $dir,
                                'page'          => $page,
                                'search_method' => $search_method,
                                'crit'          => $crit,
                            ), array('class' => 'txp-button')) .
                            ($can_edit
                                ? fInput('submit', '', gTxt('save'), 'publish')
                                : ''
                            ),
                            array('class' => 'txp-edit-actions')
                        ) .
                        hInput('id', $id) .
                        eInput('image') .
                        sInput('image_save') .
                        hInput('sort', $sort) .
                        hInput('dir', $dir) .
                        hInput('page', $page) .
                        hInput('search_method', $search_method) .
                        hInput('crit', $crit),
                        'image_details'
                    ),
                    '', '', 'post', '', '', 'image_details_form'
                ),
                'div', array('class' => 'txp-layout-4col-alt')
            ) .
            n . tag(
                n . implode(n, $imageBlock) .
                ($thumbBlock ? n . '<hr />' . n . tag(implode(n, $thumbBlock), 'section', array('class' => 'thumbnail-alter')) : ''),
                'div', array('class' => 'txp-layout-4col-3span')
            ) .
            n . '</div>'; // End of .txp-layout.
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
        $response = !empty($ids) ? 'textpattern.Relay.data.fileid = ["' . implode('","', $ids) . '"].concat(textpattern.Relay.data.fileid || []);' . n : '';

        foreach ($messages as $message) {
            $response .= 'textpattern.Console.addMessage(' . json_encode((array) $message, TEXTPATTERN_JSON) . ', "uploadEnd");' . n;
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
    global $app_mode, $txp_user;

    $id = assert_int(gps('id'));

    if (!isset($_FILES['thefile'])) {
        return image_edit('', $id);
    }

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

    $fileshandler = Txp::get('\Textpattern\Server\Files');
    $files = $fileshandler->refactor($_FILES['thefile']);

    foreach ($files as $i => $file) {
        $chunked = $fileshandler->dechunk($file);
        $img_result = image_data($file, $meta, $id, !$chunked);

        if (is_file($file['tmp_name'])) {
            unlink(realpath($file['tmp_name']));
        }
    }

//    $img_result = image_data($_FILES['thefile'], $meta, $id);

    if (is_array($img_result)) {
        list($message, $id) = $img_result;

        if ($app_mode == 'async') {
            $response = 'textpattern.Console.addMessage(' . json_encode((array) $message, TEXTPATTERN_JSON) . ', "uploadEnd");' . n;

            send_script_response($response);

            // Bail out.
            return;
        }

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
        $newpath = IMPATH . $id . 't' . $ext;

        if (shift_uploaded_file($file, $newpath) == false) {
            image_edit(array(gTxt('directory_permissions', array('{path}' => $newpath)), E_ERROR), $id);
        } else {
            chmod($newpath, 0644);
            safe_update('txp_image', "thumbnail = " . THUMB_CUSTOM . ", thumb_w = $w, thumb_h = $h, date = NOW()", "id = '$id'");

            $message = gTxt('image_uploaded', array('{name}' => $name));
            update_lastmod('thumbnail_created', compact('id', 'w', 'h'));
            set_thumb_type(THUMB_CUSTOM); // Uploads can only be of type 'custom thumbnail'

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
 * Set the thumbnail flavour in the database
 *
 * @param int $type The type
 */

function set_thumb_type($type)
{
    set_pref('thumbnail_type', $type, 'image', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
}

/**
 * Saves image meta data.
 */

function image_save()
{
    global $txp_user;

    $varray = array_map('assert_string', gpsa(array(
        'id',
        'name',
        'category',
        'caption',
        'alt',
        'publish_now',
        'year',
        'month',
        'day',
        'hour',
        'minute',
        'second',
        'thumbnail',
    )));

    extract(doSlash($varray));
    $id = $varray['id'] = assert_int($id);
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        require_privs('image.edit');

        return;
    }

    if ($publish_now) {
        $created = "NOW()";
    } else {
        $created_ts = safe_strtotime($year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $second);
        $created = $created_ts === false ? false : "FROM_UNIXTIME(0) + INTERVAL $created_ts SECOND";
    }

    $constraints = array('category' => new CategoryConstraint(gps('category'), array('type' => 'image')));
    callback_event_ref('image_ui', 'validate_save', 0, $varray, $constraints);
    $validator = new Validator($constraints);

    if ($validator->validate() && safe_update(
            'txp_image',
            "name     = '$name',
            category  = '$category',
            thumbnail = '" . (int) $thumbnail . "',
            alt       = '$alt',
            caption   = '$caption'" .
            ($created ? ", date = $created" : ''),
            "id = '$id'"
        )
    ) {
        $message = gTxt('image_updated', array('{name}' => doStrip($name)));
        update_lastmod('image_saved', compact('id', 'name', 'category', 'alt', 'caption', 'thumbnail'));
        set_thumb_type($thumbnail);
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
    global $txp_user, $event, $img_dir;

    $message = '';

    // Fetch ids and remove bogus (false) entries to prevent SQL syntax errors being thrown.
    $ids = $ids ? array_map('assert_int', $ids) : array(assert_int(ps('id')));
    $ids = array_filter($ids);

    if (!has_privs('image.delete')) {
        if ($ids && has_privs('image.delete.own')) {
            $ids = safe_column("id", 'txp_image', "id IN (" . join(',', $ids) . ") AND author = '" . doSlash($txp_user) . "'");
        } else {
            $ids = array();
        }
    }

    if (!empty($ids)) {
        $fail = array();
        $rs = safe_rows_start("id, ext", 'txp_image', "id IN (" . join(',', $ids) . ")");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);

                // Notify plugins of pending deletion, pass image's $id.
                callback_event('image_deleted', $event, false, $id);

                $rsd = safe_delete('txp_image', "id = '$id'");
                $ul = false;

                $slir = new SLIR(rhu.$img_dir.'/'.$id.$ext);
                $slir->uncache();

                if (is_file(IMPATH . $id . $ext)) {
                    $ul = unlink(realpath(IMPATH . $id . $ext));
                }

                if (is_file(IMPATH . $id . 't' . $ext)) {
                    $ult = unlink(realpath(IMPATH . $id . 't' . $ext));
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
    global $txp_user, $img_dir;

    $id = assert_int(gps('id'));
    $author = fetch('author', 'txp_image', 'id', $id);

    if (!has_privs('image.edit') && !($author === $txp_user && has_privs('image.edit.own'))) {
        require_privs('image.edit');

        return;
    }

    $rs = safe_row("id, ext", 'txp_image', "id = $id");
    $slir = new SLIR(rhu.$img_dir.'/'.$rs['id'].$rs['ext']);
    $slir->uncache();

    $t = new txp_thumb($id);

    $t->delete();
    safe_update('txp_image', 'thumbnail = 0', "id = $id");
    update_lastmod('thumbnail_deleted', compact('id'));
    callback_event('thumbnail_deleted', '', false, $id);
    image_edit(gTxt('thumbnail_deleted'), $id);
}

/**
 * Convenience function to return the thumbnail type options.
 *
 * @return array
 */
function thumb_types()
{
    return array(THUMB_NONE => gTxt('none'), THUMB_CUSTOM => gTxt('thumb_custom'), THUMB_AUTO => gTxt('thumb_auto'));
}

/**
 * Select list of thumb types
 *
 * @param string $name    Selector name
 * @param string $default Initially selected value
 * @return HTML
 */
function thumb_type_select($name, $default = '')
{
    return Txp::get('\Textpattern\UI\Select', $name, thumb_types(), $default);
}


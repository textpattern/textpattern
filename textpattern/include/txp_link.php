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
 * Links panel.
 *
 * @package Admin\Link
 */

use Textpattern\Validator\CategoryConstraint;
use Textpattern\Validator\Validator;
use Textpattern\Search\Filter;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'link') {
    require_privs('link');

    global $vars;
    $vars = array(
        'category',
        'url',
        'linkname',
        'linksort',
        'description',
        'id',
        'publish_now',
        'date',
        'year',
        'month',
        'day',
        'hour',
        'minute',
        'second',
    );

    global $all_link_cats, $all_link_authors;
    $all_link_cats = getTree('root', 'link');
    $all_link_authors = the_privileged('link.edit.own', true);

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

/**
 * The main panel listing all links.
 *
 * @param string|array $message The activity message
 */

function link_list($message = '')
{
    global $event, $step, $txp_user;

    pagetop(gTxt('tab_link'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('link_sort_column', 'name');
    } else {
        if (!in_array($sort, array('id', 'description', 'url', 'category', 'date', 'author'))) {
            $sort = 'name';
        }

        set_pref('link_sort_column', $sort, 'link', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('link_sort_dir', 'asc');
    } else {
        $dir = ($dir == 'desc') ? "desc" : "asc";
        set_pref('link_sort_dir', $dir, 'link', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    switch ($sort) {
        case 'id':
            $sort_sql = "txp_link.id $dir";
            break;
        case 'description':
            $sort_sql = "txp_link.description $dir, txp_link.id ASC";
            break;
        case 'url':
            $sort_sql = "txp_link.url $dir, txp_link.id ASC";
            break;
        case 'category':
            $sort_sql = "txp_category.title $dir, txp_link.id ASC";
            break;
        case 'date':
            $sort_sql = "txp_link.date $dir, txp_link.id ASC";
            break;
        case 'author':
            $sort_sql = "txp_users.RealName $dir, txp_link.id ASC";
            break;
        default:
            $sort = 'name';
            $sort_sql = "txp_link.linksort $dir, txp_link.id ASC";
            break;
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'id' => array(
                'column' => 'txp_link.id',
                'label'  => gTxt('id'),
                'type'   => 'integer',
            ),
            'name' => array(
                'column' => 'txp_link.linkname',
                'label'  => gTxt('title'),
            ),
            'url' => array(
                'column' => 'txp_link.url',
                'label'  => gTxt('url'),
            ),
            'description' => array(
                'column' => 'txp_link.description',
                'label'  => gTxt('description'),
            ),
            'category' => array(
                'column' => array('txp_link.category', 'txp_category.title'),
                'label'  => gTxt('category'),
            ),
            'author' => array(
                'column' => array('txp_link.author', 'txp_users.RealName'),
                'label'  => gTxt('author'),
            ),
            'linksort' => array(
                'column' => 'txp_link.linksort',
                'label'  => gTxt('sort_value'),
            ),
        )
    );

    list($criteria, $crit, $search_method) = $search->getFilter(array('id' => array('can_list' => true)));

    $search_render_options = array('placeholder' => 'search_links');

    $sql_from =
        safe_pfx_j('txp_link')."
        LEFT JOIN ".safe_pfx_j('txp_category')." ON txp_category.name = txp_link.category AND txp_category.type = 'link'
        LEFT JOIN ".safe_pfx_j('txp_users')." ON txp_users.name = txp_link.author";

    if ($crit === '') {
        $total = safe_count('txp_link', $criteria);
    } else {
        $total = getThing("SELECT COUNT(*) FROM $sql_from WHERE $criteria");
    }

    $searchBlock =
        n.tag(
            $search->renderForm('link_list', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $createBlock = array();

    if (has_privs('link.edit')) {
        $createBlock[] =
            n.tag(
                sLink('link', 'link_edit', gTxt('create_link'), 'txp-button'),
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
            gTxt($crit === '' ? 'no_links_recorded' : 'no_results_found'),
            array('class' => 'alert-block information')
        );
    } else {
        $rs = safe_query(
            "SELECT
                txp_link.id,
                UNIX_TIMESTAMP(txp_link.date) AS uDate,
                txp_link.category,
                txp_link.url,
                txp_link.linkname,
                txp_link.description,
                txp_link.author,
                txp_users.RealName AS realname,
                txp_category.Title AS category_title
            FROM $sql_from WHERE $criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        if ($rs && numRows($rs)) {
            $show_authors = !has_single_author('txp_link');

            $contentBlock .= n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'links_form',
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
                        'ID', 'id', 'link', true, $switch_dir, $crit, $search_method,
                            (('id' == $sort) ? "$dir " : '').'txp-list-col-id'
                    ).
                    column_head(
                        'title', 'name', 'link', true, $switch_dir, $crit, $search_method,
                            (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                    ).
                    column_head(
                        'description', 'description', 'link', true, $switch_dir, $crit, $search_method,
                            (('description' == $sort) ? "$dir " : '').'txp-list-col-description'
                    ).
                    column_head(
                        'date', 'date', 'link', true, $switch_dir, $crit, $search_method,
                            (('date' == $sort) ? "$dir " : '').'txp-list-col-created date'
                    ).
                    column_head(
                        'category', 'category', 'link', true, $switch_dir, $crit, $search_method,
                            (('category' == $sort) ? "$dir " : '').'txp-list-col-category category'
                    ).
                    column_head(
                        'url', 'url', 'link', true, $switch_dir, $crit, $search_method,
                            (('url' == $sort) ? "$dir " : '').'txp-list-col-url'
                    ).
                    (
                        $show_authors
                        ? column_head('author', 'author', 'link', true, $switch_dir, $crit, $search_method,
                            (('author' == $sort) ? "$dir " : '').'txp-list-col-author name')
                        : ''
                    )
                ).
                n.tag_end('thead').
                n.tag_start('tbody');

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

                if ($link_category) {
                    $link_category = span(txpspecialchars($link_category_title), array(
                        'title'      => $link_category,
                        'aria-label' => $link_category,
                    ));
                }

                $can_edit = has_privs('link.edit') || ($link_author === $txp_user && has_privs('link.edit.own'));
                $view_url = txpspecialchars($link_url);

                $contentBlock .= tr(
                    td(
                        fInput('checkbox', 'selected[]', $link_id), '', 'txp-list-col-multi-edit'
                    ).
                    hCell(
                        ($can_edit ? href($link_id, $edit_url, ' title="'.gTxt('edit').'"') : $link_id), '', ' class="txp-list-col-id" scope="row"'
                    ).
                    td(
                        ($can_edit ? href(txpspecialchars($link_linkname), $edit_url, ' title="'.gTxt('edit').'"') : txpspecialchars($link_linkname)), '', 'txp-list-col-name'
                    ).
                    td(
                        txpspecialchars($link_description), '', 'txp-list-col-description'
                    ).
                    td(
                        gTime($link_uDate), '', 'txp-list-col-created date'
                    ).
                    td(
                        $link_category, '', 'txp-list-col-category category'.$vc
                    ).
                    td(
                        href($view_url.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), $view_url, array(
                            'rel'    => 'external noopener',
                            'target' => '_blank',
                        )), '', 'txp-list-col-url txp-contain'
                    ).
                    (
                        $show_authors
                        ? td(span(txpspecialchars($link_realname), array(
                            'title'      => $link_author,
                            'aria-label' => $link_author,
                        )), '', 'txp-list-col-author name')
                        : ''
                    )
                );
            }

            $contentBlock .= n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div').
                link_multiedit_form($page, $sort, $dir, $crit, $search_method).
                tInput().
                n.tag_end('form');
        }
    }

    $pageBlock = $paginator->render().
        nav_form('link', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table($event);
    echo $table->render(compact('total', 'crit'), $searchBlock, $createBlock, $contentBlock, $pageBlock);
}

/**
 * Renders and outputs the link editor panel.
 *
 * @param string|array $message The activity message
 */

function link_edit($message = '')
{
    global $vars, $event, $step, $txp_user;

    pagetop(gTxt('tab_link'), $message);

    extract(array_map('assert_string', gpsa($vars)));

    $is_edit = ($id && $step == 'link_edit');

    $rs = array();

    if ($is_edit) {
        $id = assert_int($id);
        $rs = safe_row("*, UNIX_TIMESTAMP(date) AS date", 'txp_link', "id = '$id'");

        if ($rs) {
            extract($rs);

            if (!has_privs('link.edit') && !($author === $txp_user && has_privs('link.edit.own'))) {
                require_privs('link.edit');

                return;
            }
        } else {
            link_list(array(gTxt('unknown_link'), E_ERROR));
        }
    }

    if (has_privs('link.edit') || has_privs('link.edit.own')) {
        $caption = gTxt($is_edit ? 'edit_link' : 'create_link');
        $created =
            inputLabel(
                'year',
                tsi('year', '%Y', $date, '', 'year').
                ' <span role="separator">/</span> '.
                tsi('month', '%m', $date, '', 'month').
                ' <span role="separator">/</span> '.
                tsi('day', '%d', $date, '', 'day'),
                'publish_date',
                array('timestamp_link', 'instructions_link_date'),
                array('class' => 'txp-form-field date posted')
            ).
            inputLabel(
                'hour',
                tsi('hour', '%H', $date, '', 'hour').
                ' <span role="separator">:</span> '.
                tsi('minute', '%M', $date, '', 'minute').
                ' <span role="separator">:</span> '.
                tsi('second', '%S', $date, '', 'second'),
                'publish_time',
                array('', 'instructions_link_time'),
                array('class' => 'txp-form-field time posted')
            ).
            n.tag(
                checkbox('publish_now', '1', $publish_now, '', 'publish_now').
                n.tag(gTxt('set_to_now'), 'label', array('for' => 'publish_now')),
                'div', array('class' => 'txp-form-field-shim posted-now')
            );
        echo form(
            hed($caption, 2).
            inputLabel(
                'link_name',
                fInput('text', 'linkname', $linkname, '', '', '', INPUT_REGULAR, '', 'link_name', false, true),
                'title', '', array('class' => 'txp-form-field edit-link-name')
            ).
            inputLabel(
                'link_sort',
                fInput('text', 'linksort', $linksort, 'input-medium', '', '', INPUT_MEDIUM, '', 'link_sort'),
                'sort_value', 'link_sort', array('class' => 'txp-form-field edit-link-sort')
            ).
            // TODO: maybe use type="url" once browsers are less strict.
            inputLabel(
                'link_url',
                fInput('text', 'url', $url, '', '', '', INPUT_REGULAR, '', 'link_url'),
                'url', 'link_url', array('class' => 'txp-form-field edit-link-url')
            ).
            inputLabel(
                'link_category',
                event_category_popup('link', $category, 'link_category').
                n.eLink('category', 'list', '', '', gTxt('edit'), '', '', '', 'txp-option-link'),
                'category', 'link_category', array('class' => 'txp-form-field edit-link-category')
            ).
            $created.
            inputLabel(
                'link_description',
                '<textarea id="link_description" name="description" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.txpspecialchars($description).'</textarea>',
                'description', 'link_description', array('class' => 'txp-form-field txp-form-field-textarea edit-link-description')
            ).
            pluggable_ui('link_ui', 'extend_detail_form', '', $rs).
            graf(
                sLink('link', '', gTxt('cancel'), 'txp-button').
                fInput('submit', '', gTxt('save'), 'publish'),
                array('class' => 'txp-edit-actions')
            ).
            eInput('link').
            sInput('link_save').
            hInput('id', $id).
            hInput('sort', gps('sort')).
            hInput('dir', gps('dir')).
            hInput('page', gps('page')).
            hInput('search_method', gps('search_method')).
            hInput('crit', gps('crit')),
        '', '', 'post', 'txp-edit', '', 'link_details');
    }
}

/**
 * Legacy link category HTML select field.
 *
 * @param      string $cat
 * @return     string
 * @deprecated in 4.6.0
 */

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
        require_privs('link.edit');

        return;
    }

    if (!$linksort) {
        $linksort = $linkname;
    }

    $created_ts = @safe_strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
    $created = "NOW()";

    if (!$publish_now && $created_ts > 0) {
        $created = "FROM_UNIXTIME('".$created_ts."')";
    }

    $constraints = array(
        'category' => new CategoryConstraint($varray['category'], array('type' => 'link')),
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
                date        = $created,
                description = '$description',
                author      = '".doSlash($txp_user)."'",
                "id = $id"
            );
        } else {
            $ok = safe_insert('txp_link',
                "category   = '$category',
                url         = '".trim($url)."',
                linkname    = '$linkname',
                linksort    = '$linksort',
                date        = $created,
                description = '$description',
                author      = '".doSlash($txp_user)."'"
            );
            if ($ok) {
                $GLOBALS['ID'] = $_POST['id'] = $ok;
            }
        }

        if ($ok) {
            $message = gTxt(($id ? 'link_updated' : 'link_created'), array('{name}' => doStrip($linkname)));

            // Update lastmod due to link feeds.
            $id = empty($id) ? $ok : $id;
            update_lastmod('link_saved', compact('id', 'linkname', 'linksort', 'url', 'category', 'description'));
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
    Txp::get('\Textpattern\Admin\Paginator')->change();
    link_list();
}

// -------------------------------------------------------------

function link_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $all_link_cats, $all_link_authors;

    $categories = $all_link_cats ? treeSelectInput('category', $all_link_cats, '') : '';
    $authors = $all_link_authors ? selectInput('author', $all_link_authors, '', true) : '';

    $methods = array(
        'changecategory' => array(
            'label' => gTxt('changecategory'),
            'html' => $categories
        ),
        'changeauthor'   => array(
            'label' => gTxt('changeauthor'),
            'html' => $authors
        ),
        'delete'         => gTxt('delete'),
    );

    if (!$categories) {
        unset($methods['changecategory']);
    }

    if (has_single_author('txp_link') || !has_privs('link.edit')) {
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

    // Empty entry to permit clearing the category.
    $categories = array('');

    foreach ($all_link_cats as $row) {
        $categories[] = $row['name'];
    }

    $selected = ps('selected');

    if (!$selected || !is_array($selected)) {
        link_list();

        return;
    }

    // Fetch and remove bogus (false) entries to prevent SQL syntax errors being thrown.
    $selected = array_map('assert_int', $selected);
    $selected = array_filter($selected);
    $method = ps('edit_method');
    $changed = array();
    $key = '';

    switch ($method) {
        case 'delete':
            if (!has_privs('link.delete')) {
                if ($selected && has_privs('link.delete.own')) {
                    $selected = safe_column("id", 'txp_link', "id IN (".join(',', $selected).") AND author = '".doSlash($txp_user)."'");
                } else {
                    $selected = array();
                }
            }

            foreach ($selected as $id) {
                if (safe_delete('txp_link', "id = '$id'")) {
                    $changed[] = $id;
                }
            }

            if ($changed) {
                callback_event('links_deleted', '', 0, $changed);
            }

            $key = '';
            break;
        case 'changecategory':
            $val = ps('category');
            if (in_array($val, $categories)) {
                $key = 'category';
            }
            break;
        case 'changeauthor':
            $val = ps('author');
            if (has_privs('link.edit') && isset($all_link_authors[$val])) {
                $key = 'author';
            }
            break;
        default:
            $key = '';
            $val = '';
            break;
    }

    if (!has_privs('link.edit')) {
        if ($selected && has_privs('link.edit.own')) {
            $selected = safe_column("id", 'txp_link', "id IN (".join(',', $selected).") AND author = '".doSlash($txp_user)."'");
        } else {
            $selected = array();
        }
    }

    if ($selected && $key) {
        foreach ($selected as $id) {
            if (safe_update('txp_link', "$key = '".doSlash($val)."'", "id = '$id'")) {
                $changed[] = $id;
            }
        }
    }

    if ($changed) {
        update_lastmod('link_updated', $changed);

        link_list(gTxt(
            ($method == 'delete' ? 'links_deleted' : 'link_updated'),
            array(($method == 'delete' ? '{list}' : '{name}') => join(', ', $changed))
        ));

        return;
    }

    link_list();
}

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

/**
 * Articles panel.
 *
 * @package Admin\List
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'list') {
    global $statuses, $all_cats, $all_authors, $all_sections;

    require_privs('article');

    $statuses = array(
        STATUS_DRAFT   => gTxt('draft'),
        STATUS_HIDDEN  => gTxt('hidden'),
        STATUS_PENDING => gTxt('pending'),
        STATUS_LIVE    => gTxt('live'),
        STATUS_STICKY  => gTxt('sticky'),
    );

    $all_cats = getTree('root', 'article');
    $all_authors = the_privileged('article.edit.own');
    $all_sections = safe_column('name', 'txp_section', "name != 'default'");

    $available_steps = array(
        'list_list'          => false,
        'list_change_pageby' => true,
        'list_multi_edit'    => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        list_list();
    }
}

/**
 * Outputs the main panel listing all articles.
 *
 * @param  string|array $message The activity message
 * @param  string       $post    Not used
 */

function list_list($message = '', $post = '')
{
    global $statuses, $use_comments, $comments_disabled_after, $step, $txp_user, $article_list_pageby, $event;

    pagetop(gTxt('tab_list'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method'
    )));

    if ($sort === '') {
        $sort = get_pref('article_sort_column', 'posted');
    }

    if ($dir === '') {
        $dir = get_pref('article_sort_dir', 'desc');
    }

    $dir = ($dir == 'asc') ? 'asc' : 'desc';

    $sesutats = array_flip($statuses);

    switch ($sort) {
        case 'id' :
            $sort_sql = 'textpattern.ID '.$dir;
            break;
        case 'title' :
            $sort_sql = 'textpattern.Title '.$dir.', textpattern.Posted desc';
            break;
        case 'expires' :
            $sort_sql = 'textpattern.Expires '.$dir;
            break;
        case 'section' :
            $sort_sql = 'section.title '.$dir.', textpattern.Posted desc';
            break;
        case 'category1' :
            $sort_sql = 'category1.title '.$dir.', textpattern.Posted desc';
            break;
        case 'category2' :
            $sort_sql = 'category2.title '.$dir.', textpattern.Posted desc';
            break;
        case 'status' :
            $sort_sql = 'textpattern.Status '.$dir.', textpattern.Posted desc';
            break;
        case 'author' :
            $sort_sql = 'user.RealName '.$dir.', textpattern.Posted desc';
            break;
        case 'comments' :
            $sort_sql = 'textpattern.comments_count '.$dir.', textpattern.Posted desc';
            break;
        case 'lastmod' :
            $sort_sql = 'textpattern.LastMod '.$dir.', textpattern.Posted desc';
            break;
        default :
            $sort = 'posted';
            $sort_sql = 'textpattern.Posted '.$dir;
            break;
    }

    set_pref('article_sort_column', $sort, 'list', 2, '', 0, PREF_PRIVATE);
    set_pref('article_sort_dir', $dir, 'list', 2, '', 0, PREF_PRIVATE);

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $criteria = 1;

    if ($search_method and $crit != '') {
        $verbatim = preg_match('/^"(.*)"$/', $crit, $m);
        $crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
        $critsql = $verbatim ?
            array(
                'id'                 => "textpattern.ID in ('" .join("','", do_list($crit_escaped)). "')",
                'title_body_excerpt' => "textpattern.Title = '$crit_escaped' or textpattern.Body = '$crit_escaped' or textpattern.Excerpt = '$crit_escaped'",
                'section'            => "textpattern.Section = '$crit_escaped' or section.title = '$crit_escaped'",
                'keywords'           => "FIND_IN_SET('".$crit_escaped."',textpattern.Keywords)",
                'categories'         => "textpattern.Category1 = '$crit_escaped' or textpattern.Category2 = '$crit_escaped' or category1.title = '$crit_escaped' or category2.title = '$crit_escaped'",
                'status'             => "textpattern.Status = '".(@$sesutats[gTxt($crit_escaped)])."'",
                'author'             => "textpattern.AuthorID = '$crit_escaped' or user.RealName = '$crit_escaped'",
                'article_image'      => "textpattern.Image in ('" .join("','", do_list($crit_escaped)). "')",
                'posted'             => "textpattern.Posted = '$crit_escaped'",
                'lastmod'            => "textpattern.LastMod = '$crit_escaped'"
            ) : array(
                'id'                 => "textpattern.ID in ('" .join("','", do_list($crit_escaped)). "')",
                'title_body_excerpt' => "textpattern.Title like '%$crit_escaped%' or textpattern.Body like '%$crit_escaped%' or textpattern.Excerpt like '%$crit_escaped%'",
                'section'            => "textpattern.Section like '%$crit_escaped%' or section.title like '%$crit_escaped%'",
                'keywords'           => "FIND_IN_SET('".$crit_escaped."',textpattern.Keywords)",
                'categories'         => "textpattern.Category1 like '%$crit_escaped%' or textpattern.Category2 like '%$crit_escaped%' or category1.title like '%$crit_escaped%' or category2.title like '%$crit_escaped%'",
                'status'             => "textpattern.Status = '".(@$sesutats[gTxt($crit_escaped)])."'",
                'author'             => "textpattern.AuthorID like '%$crit_escaped%' or user.RealName like '%$crit_escaped%'",
                'article_image'      => "textpattern.Image in ('" .join("','", do_list($crit_escaped)). "')",
                'posted'             => "textpattern.Posted like '$crit_escaped%'",
                'lastmod'            => "textpattern.LastMod like '$crit_escaped%'"
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

    $criteria .= callback_event('admin_criteria', 'list_list', 0, $criteria);

    $sql_from =
        safe_pfx('textpattern')." textpattern
        left join ".safe_pfx('txp_category')." category1 on category1.name = textpattern.Category1 and category1.type = 'article'
        left join ".safe_pfx('txp_category')." category2 on category2.name = textpattern.Category2 and category2.type = 'article'
        left join ".safe_pfx('txp_section')." section on section.name = textpattern.Section
        left join ".safe_pfx('txp_users')." user on user.name = textpattern.AuthorID";

    if ($criteria === 1) {
        $total = safe_count('textpattern', $criteria);
    } else {
        $total = getThing('select count(*) from '.$sql_from.' where '.$criteria);
    }

    echo hed(gTxt('tab_list'), 1, array('class' => 'txp-heading'));
    echo n.'<div id="'.$event.'_control" class="txp-control-panel">';

    if ($total < 1) {
        if ($criteria != 1) {
            echo list_search_form($crit, $search_method).
                graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
        } else {
            echo graf(gTxt('no_articles_recorded'), ' class="indicator"').'</div>';
        }

        return;
    }

    $limit = max($article_list_pageby, 15);

    list ($page, $offset, $numPages) = pager($total, $limit, $page);

    echo list_search_form($crit, $search_method).'</div>';

    $rs = safe_query(
        "select
            textpattern.ID, textpattern.Title, textpattern.url_title, textpattern.Section,
            textpattern.Category1, textpattern.Category2,
            textpattern.Status, textpattern.Annotate, textpattern.AuthorID,
            unix_timestamp(textpattern.Posted) as posted,
            unix_timestamp(textpattern.LastMod) as lastmod,
            unix_timestamp(textpattern.Expires) as expires,
            category1.title as category1_title,
            category2.title as category2_title,
            section.title as section_title,
            user.RealName as RealName,
            (select count(*) from ".safe_pfx('txp_discuss')." where parentid = textpattern.ID) as total_comments
        from $sql_from where $criteria order by $sort_sql limit $offset, $limit"
    );

    if ($rs) {
        $show_authors = !has_single_author('textpattern', 'AuthorID');

        echo n.'<div id="'.$event.'_container" class="txp-container">';
        echo n.'<form name="longform" id="articles_form" class="multi_edit_form" method="post" action="index.php">'.

            n.'<div class="txp-listtables">'.
            startTable('', '', 'txp-list').
            n.'<thead>'.
            tr(
                hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' scope="col" title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
                column_head('ID', 'id', 'list', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id actions').
                column_head('title', 'title', 'list', true, $switch_dir, $crit, $search_method, (('title' == $sort) ? "$dir " : '').'title').
                column_head('posted', 'posted', 'list', true, $switch_dir, $crit, $search_method, (('posted' == $sort) ? "$dir " : '').'date posted created').
                column_head('article_modified', 'lastmod', 'list', true, $switch_dir, $crit, $search_method, (('lastmod' == $sort) ? "$dir " : '').'articles_detail date modified').
                column_head('expires', 'expires', 'list', true, $switch_dir, $crit, $search_method, (('expires' == $sort) ? "$dir " : '').'articles_detail date expires').
                column_head('section', 'section', 'list', true, $switch_dir, $crit, $search_method, (('section' == $sort) ? "$dir " : '').'section').
                column_head('category1', 'category1', 'list', true, $switch_dir, $crit, $search_method, (('category1' == $sort) ? "$dir " : '').'articles_detail category category1').
                column_head('category2', 'category2', 'list', true, $switch_dir, $crit, $search_method, (('category2' == $sort) ? "$dir " : '').'articles_detail category category2').
                column_head('status', 'status', 'list', true, $switch_dir, $crit, $search_method, (('status' == $sort) ? "$dir " : '').'status').
                ($show_authors ? column_head('author', 'author', 'list', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '').
                ($use_comments == 1 ? column_head('comments', 'comments', 'list', true, $switch_dir, $crit, $search_method, (('comments' == $sort) ? "$dir " : '').'articles_detail comments') : '')
            ).
            n.'</thead>';

        include_once txpath.'/publish/taghandlers.php';

        echo n.'<tbody>';

        $validator = new Validator();

        while ($a = nextRow($rs)) {
            extract($a);

            if ($Title === '') {
                $Title = '<em>'.eLink('article', 'edit', 'ID', $ID, gTxt('untitled')).'</em>';
            } else {
                $Title = eLink('article', 'edit', 'ID', $ID, $Title);
            }

            // Valid section and categories?
            $validator->setConstraints(array(new SectionConstraint($Section)));
            $vs = $validator->validate() ? '' : ' error';

            $validator->setConstraints(array(new CategoryConstraint($Category1, array('type' => 'article'))));
            $vc[1] = $validator->validate() ? '' : ' error';

            $validator->setConstraints(array(new CategoryConstraint($Category2, array('type' => 'article'))));
            $vc[2] = $validator->validate() ? '' : ' error';

            $Category1 = ($Category1) ? span(txpspecialchars($category1_title), array('title' => $Category1)) : '';
            $Category2 = ($Category2) ? span(txpspecialchars($category2_title), array('title' => $Category2)) : '';

            if ($Status != STATUS_LIVE and $Status != STATUS_STICKY) {
                $view_url = '?txpreview='.intval($ID).'.'.time();
            } else {
                $view_url = permlinkurl($a);
            }

            if (isset($statuses[$Status])) {
                $Status = $statuses[$Status];
            }

            $comments = '('.$total_comments.')';

            if ($total_comments) {
                $comments = href($comments, array(
                    'event'         => 'discuss',
                    'step'          => 'list',
                    'search_method' => 'parent',
                    'crit'          => $ID,
                ), array('title' => gTxt('manage')));
            }

            $comment_status = ($Annotate) ? gTxt('on') : gTxt('off');

            if ($comments_disabled_after) {
                $lifespan = $comments_disabled_after * 86400;
                $time_since = time() - $posted;

                if ($time_since > $lifespan) {
                    $comment_status = gTxt('expired');
                }
            }

            $comments =
                tag($comment_status, 'span', array('class' => 'comments-status')).' '.
                tag($comments, 'span', array('class' => 'comments-manage'));

            echo tr(

                td((
                    (  ($a['Status'] >= STATUS_LIVE and has_privs('article.edit.published'))
                    or ($a['Status'] >= STATUS_LIVE and $AuthorID === $txp_user and has_privs('article.edit.own.published'))
                    or ($a['Status'] < STATUS_LIVE and has_privs('article.edit'))
                    or ($a['Status'] < STATUS_LIVE and $AuthorID === $txp_user and has_privs('article.edit.own'))
                    )
                    ? fInput('checkbox', 'selected[]', $ID, 'checkbox')
                    : ''
                ), '', 'multi-edit').

                hCell(
                    eLink('article', 'edit', 'ID', $ID, $ID).
                    tag(
                        sp.tag('[', 'span', array('aria-hidden' => 'true')).
                        href(gTxt('view'), $view_url).
                        tag(']', 'span', array('aria-hidden' => 'true'))
                    , 'span', array('class' => 'articles_detail'))
                , '', ' scope="row" class="id"').

                td($Title, '', 'title').

                td(
                    gTime($posted), '', ($posted < time() ? '' : 'unpublished ').'date posted created'
                ).

                td(
                    gTime($lastmod), '', "articles_detail date modified"
                ).

                td(
                    ($expires ? gTime($expires) : ''), '', 'articles_detail date expires'
                ).

                td(span(txpspecialchars($section_title), array('title' => $Section)), '', 'section'.$vs).

                td($Category1, '', "articles_detail category category1".$vc[1]).
                td($Category2, '', "articles_detail category category2".$vc[2]).
                td(href($Status, $view_url, join_atts(array('title' => gTxt('view')))), '', 'status').

                ($show_authors ? td(span(txpspecialchars($RealName), array('title' => $AuthorID)), '', 'author') : '').

                ($use_comments ? td($comments, '', "articles_detail comments") : '')
            );
        }

        echo n.'</tbody>'.
            endTable().
            n.'</div>'.
            list_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.'</form>'.

            graf(
                toggle_box('articles_detail'),
                array('class' => 'detail-toggle')
            ).

            n.tag(
                pageby_form('list', $article_list_pageby).
                nav_form('list', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).n,
                'div', array(
                'class' => 'txp-navigation',
                'id'    => $event.'_navigation'
            )).

            n.'</div>';
    }
}

/**
 * Saves pageby value for the article list.
 */

function list_change_pageby()
{
    event_change_pageby('article');
    list_list();
}

/**
 * Renders a search form for articles.
 *
 * @param  string $crit   The current search criteria
 * @param  string $method The selected search method
 * @return string HTML
 */

function list_search_form($crit, $method)
{
    $methods = array(
        'id'                 => gTxt('ID'),
        'title_body_excerpt' => gTxt('title_body_excerpt'),
        'section'            => gTxt('section'),
        'categories'         => gTxt('categories'),
        'keywords'           => gTxt('keywords'),
        'status'             => gTxt('status'),
        'author'             => gTxt('author'),
        'article_image'      => gTxt('article_image'),
        'posted'             => gTxt('posted'),
        'lastmod'            => gTxt('article_modified'),
    );

    return search_form('list', 'list', $crit, $methods, $method, 'title_body_excerpt');
}

/**
 * Renders a multi-edit form widget for articles.
 *
 * @param  int    $page          The page number
 * @param  string $sort          The current sort value
 * @param  string $dir           The current sort direction
 * @param  string $crit          The current search criteria
 * @param  string $search_method The current search method
 * @return string HTML
 */

function list_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $statuses, $all_cats, $all_authors, $all_sections;

    if ($all_cats) {
        $category1 = treeSelectInput('Category1', $all_cats, '');
        $category2 = treeSelectInput('Category2', $all_cats, '');
    } else {
        $category1 = $category2 = '';
    }

    $sections = $all_sections ? selectInput('Section', $all_sections, '', true) : '';
    $comments = onoffRadio('Annotate', get_pref('comments_on_default'));
    $status = selectInput('Status', $statuses, '', true);
    $authors = $all_authors ? selectInput('AuthorID', $all_authors, '', true) : '';

    $methods = array(
        'changesection'   => array('label' => gTxt('changesection'),   'html' => $sections),
        'changecategory1' => array('label' => gTxt('changecategory1'), 'html' => $category1),
        'changecategory2' => array('label' => gTxt('changecategory2'), 'html' => $category2),
        'changestatus'    => array('label' => gTxt('changestatus'),    'html' => $status),
        'changecomments'  => array('label' => gTxt('changecomments'),  'html' => $comments),
        'changeauthor'    => array('label' => gTxt('changeauthor'),    'html' => $authors),
        'duplicate'       => gTxt('duplicate'),
        'delete'          => gTxt('delete'),
    );

    if (!$all_cats) {
        unset($methods['changecategory1'], $methods['changecategory2']);
    }

    if (has_single_author('textpattern', 'AuthorID')) {
        unset($methods['changeauthor']);
    }

    if (!has_privs('article.delete.own') && !has_privs('article.delete')) {
        unset($methods['delete']);
    }

    return multi_edit($methods, 'list', 'list_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

/**
 * Processes multi-edit actions.
 */

function list_multi_edit()
{
    global $txp_user, $statuses, $all_cats, $all_authors, $all_sections;

    extract(psa(array(
        'selected',
        'edit_method',
    )));

    if (!$selected || !is_array($selected)) {
        return list_list();
    }

    $selected = array_map('assert_int', $selected);

    // Empty entry to permit clearing the categories.
    $categories = array('');

    foreach ($all_cats as $row) {
        $categories[] = $row['name'];
    }

    $allowed = array();
    $field = $value = '';

    switch ($edit_method) {
        // Delete.
        case 'delete' :
            if (!has_privs('article.delete')) {
                if (has_privs('article.delete.own')) {
                    $allowed = safe_column_num(
                        'ID',
                        'textpattern',
                        "ID in(".join(',', $selected).") and AuthorID = '".doSlash($txp_user)."'"
                    );
                }

                $selected = $allowed;
            }

            if ($selected && safe_delete('textpattern', 'ID in ('.join(',', $selected).')')) {
                safe_update('txp_discuss', "visible = ".MODERATE, "parentid in(".join(',', $selected).")");
                callback_event('articles_deleted', '', 0, $selected);
                callback_event('multi_edited.articles', 'delete', 0, compact('selected', 'field', 'value'));
                update_lastmod();

                return list_list(messenger('article', join(', ', $selected), 'deleted'));
            }

            return list_list();
            break;
        // Change author.
        case 'changeauthor' :
            $value = ps('AuthorID');
            if (has_privs('article.edit') && in_array($value, $all_authors, true)) {
                $field = 'AuthorID';
            }
            break;

        // Change category1.
        case 'changecategory1' :
            $value = ps('Category1');
            if (in_array($value, $categories, true)) {
                $field = 'Category1';
            }
            break;
        // Change category2.
        case 'changecategory2' :
            $value = ps('Category2');
            if (in_array($value, $categories, true)) {
                $field = 'Category2';
            }
            break;
        // Change comment status.
        case 'changecomments' :
            $field = 'Annotate';
            $value = (int) ps('Annotate');
            break;
        // Change section.
        case 'changesection' :
            $value = ps('Section');
            if (in_array($value, $all_sections, true)) {
                $field = 'Section';
            }
            break;
        // Change status.
        case 'changestatus' :
            $value = (int) ps('Status');
            if (array_key_exists($value, $statuses)) {
                $field = 'Status';
            }

            if (!has_privs('article.publish') && $value >= STATUS_LIVE) {
                $value = STATUS_PENDING;
            }
            break;
    }

    $selected = safe_rows(
        'ID, AuthorID, Status',
        'textpattern',
        'ID in ('.join(',', $selected).')'
    );

    foreach ($selected as $item) {
        if (
            ($item['Status'] >= STATUS_LIVE && has_privs('article.edit.published')) ||
            ($item['Status'] >= STATUS_LIVE && $item['AuthorID'] === $txp_user && has_privs('article.edit.own.published')) ||
            ($item['Status'] < STATUS_LIVE && has_privs('article.edit')) ||
            ($item['Status'] < STATUS_LIVE && $item['AuthorID'] === $txp_user && has_privs('article.edit.own'))
        )
        {
            $allowed[] = $item['ID'];
        }
    }

    $selected = $allowed;

    if ($selected) {
        $message = messenger('article', join(', ', $selected), 'modified');

        if ($edit_method === 'duplicate') {
            $rs = safe_rows_start('*', 'textpattern', "ID in (".join(',', $selected).")");

            if ($rs) {
                while ($a = nextRow($rs)) {
                    unset($a['ID'], $a['LastMod'], $a['LastModID'], $a['Expires']);
                    $a['uid'] = md5(uniqid(rand(), true));
                    $a['AuthorID'] = $txp_user;

                    foreach ($a as $name => &$value) {
                        $value = "`{$name}` = '".doSlash($value)."'";
                    }

                    if ($id = (int) safe_insert('textpattern', join(',', $a))) {
                        safe_update(
                            'textpattern',
                            "Title = concat(Title, ' (', {$id}, ')'),
                            url_title = concat(url_title, '-', {$id}),
                            Posted = now(),
                            feed_time = now()",
                            "ID = {$id}"
                        );
                    }
                }
            }

            $message = gTxt('duplicated_articles', array('{id}' => join(', ', $selected)));
        } elseif (!$field || safe_update('textpattern', "$field = '".doSlash($value)."'", "ID in (".join(',', $selected).")") === false) {
            return list_list();
        }

        update_lastmod();
        callback_event('multi_edited.articles', $edit_method, 0, compact('selected', 'field', 'value'));

        return list_list($message);
    }

    return list_list();
}

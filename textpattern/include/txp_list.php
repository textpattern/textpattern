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
 * Articles panel.
 *
 * @package Admin\List
 */

use Textpattern\Validator\CategoryConstraint;
use Textpattern\Validator\SectionConstraint;
use Textpattern\Validator\Validator;
use Textpattern\Search\Filter;
use Textpattern\Admin\Customiser;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'list') {
    global $statuses, $all_cats, $all_authors, $all_sections;

    require_privs('article');

    $statuses = status_list();

    $all_cats = getTree('root', 'article');
    $all_authors = the_privileged('article.edit.own', true);
    $all_sections = array();

    foreach (safe_rows("name, title", 'txp_section', "name != 'default' ORDER BY title") as $section) {
        extract($section);
        $all_sections[$name] = $title;
    }

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
 * The main panel listing all articles.
 *
 * @param  string|array $message The activity message
 * @param  string       $post    Not used
 */

function list_list($message = '', $post = '')
{
    global $statuses, $use_comments, $comments_disabled_after, $step, $txp_user, $event;

    pagetop(gTxt('tab_list'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('article_sort_column', 'posted');
    } else {
        if (!in_array($sort, array('id', 'title', 'expires', 'section', 'category1', 'category2', 'status', 'author', 'comments', 'lastmod'))) {
            $sort = 'posted';
        }

        set_pref('article_sort_column', $sort, 'list', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('article_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('article_sort_dir', $dir, 'list', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    $sesutats = array_flip($statuses);

    switch ($sort) {
        case 'id':
            $sort_sql = "textpattern.ID $dir";
            break;
        case 'title':
            $sort_sql = "textpattern.Title $dir, textpattern.ID DESC";
            break;
        case 'expires':
            $sort_sql = "textpattern.Expires $dir, textpattern.ID DESC";
            break;
        case 'section':
            $sort_sql = "section.title $dir, textpattern.ID DESC";
            break;
        case 'category1':
            $sort_sql = "category1.title $dir, textpattern.ID DESC";
            break;
        case 'category2':
            $sort_sql = "category2.title $dir, textpattern.ID DESC";
            break;
        case 'status':
            $sort_sql = "textpattern.Status $dir, textpattern.ID DESC";
            break;
        case 'author':
            $sort_sql = "user.RealName $dir, textpattern.ID DESC";
            break;
        case 'comments':
            $sort_sql = "total_comments $dir, textpattern.ID DESC";
            break;
        case 'lastmod':
            $sort_sql = "textpattern.LastMod $dir, textpattern.ID DESC";
            break;
        default:
            $sort = 'posted';
            $sort_sql = "textpattern.Posted $dir, textpattern.ID DESC";
            break;
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'id' => array(
                'column' => 'textpattern.ID',
                'label'  => gTxt('id'),
                'type'   => 'integer',
            ),
            'title_body_excerpt' => array(
                'column' => array('textpattern.Title', 'textpattern.Body', 'textpattern.Excerpt'),
                'label'  => gTxt('title_body_excerpt'),
            ),
            'section' => array(
                'column' => array('textpattern.Section', 'section.title'),
                'label'  => gTxt('section'),
            ),
            'keywords' => array(
                'column' => 'textpattern.Keywords',
                'label'  => gTxt('keywords'),
                'type'   => 'find_in_set',
            ),
            'categories' => array(
                'column' => array('textpattern.Category1', 'textpattern.Category2', 'category1.title', 'category2.title'),
                'label'  => gTxt('categories'),
            ),
            'status' => array(
                'column' => array('textpattern.Status'),
                'label'  => gTxt('status'),
                'type'   => 'boolean',
            ),
            'author' => array(
                'column' => array('textpattern.AuthorID', 'user.RealName'),
                'label'  => gTxt('author'),
            ),
            'article_image' => array(
                'column' => array('textpattern.Image'),
                'label'  => gTxt('article_image'),
                'type'   => 'find_in_set',
            ),
            'posted' => array(
                'column'  => array('textpattern.Posted'),
                'label'   => gTxt('posted'),
                'options' => array('case_sensitive' => true),
            ),
            'lastmod' => array(
                'column'  => array('textpattern.LastMod'),
                'label'   => gTxt('modified'),
                'options' => array('case_sensitive' => true),
            ),
        )
    );

    $search->setAliases('status', $statuses);

    list($criteria, $crit, $search_method) = $search->getFilter(array(
            'id'                 => array('can_list' => true),
            'title_body_excerpt' => array('always_like' => true),
        ));

    $search_render_options = array('placeholder' => 'search_articles');

    $sql_from =
        safe_pfx('textpattern')." textpattern
        LEFT JOIN ".safe_pfx('txp_category')." category1 ON category1.name = textpattern.Category1 AND category1.type = 'article'
        LEFT JOIN ".safe_pfx('txp_category')." category2 ON category2.name = textpattern.Category2 AND category2.type = 'article'
        LEFT JOIN ".safe_pfx('txp_section')." section ON section.name = textpattern.Section
        LEFT JOIN ".safe_pfx('txp_users')." user ON user.name = textpattern.AuthorID";

    if ($crit === '') {
        $total = safe_count('textpattern', $criteria);
    } else {
        $total = getThing("SELECT COUNT(*) FROM $sql_from WHERE $criteria");
    }

    $searchBlock =
        n.tag(
            $search->renderForm('list', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $createBlock = array();

    if (has_privs('article.edit.own')) {
        $createBlock[] =
            n.tag(
                sLink('article', '', gTxt('create_article'), 'txp-button'),
                'div', array('class' => 'txp-control-panel')
            );
    }

    $createBlock = implode(n, $createBlock);
    $contentBlock = '';

    $paginator = new \Textpattern\Admin\Paginator($event, 'article');
    $limit = $paginator->getLimit();
    list($page, $offset, $numPages) = pager($total, $limit, $page);

    if ($total < 1) {
        $contentBlock .= graf(
            span(null, array('class' => 'ui-icon ui-icon-info')).' '.
            gTxt($crit === '' ? 'no_articles_recorded' : 'no_results_found'),
            array('class' => 'alert-block information')
        );
    } else {
        $show_authors = !has_single_author('textpattern', 'AuthorID');

        $headers = array(
            'title'     => 'title',
            'posted'    => 'posted',
            'lastmod'   => 'modified',
            'expires'   => 'expires',
            'section'   => 'section',
            'category1' => 'category1',
            'category2' => 'category2',
            'status'    => 'status',
        );

        if ($show_authors) {
            $headers['author'] = 'author';
        }

        if ($use_comments) {
            $headers['comments'] = 'comments';
        }

        $rs = safe_query(
            "SELECT
                textpattern.ID, textpattern.Title, textpattern.url_title, textpattern.Section,
                textpattern.Category1, textpattern.Category2,
                textpattern.Status, textpattern.Annotate, textpattern.AuthorID,
                UNIX_TIMESTAMP(textpattern.Posted) AS posted,
                UNIX_TIMESTAMP(textpattern.LastMod) AS lastmod,
                UNIX_TIMESTAMP(textpattern.Expires) AS expires,
                category1.title AS category1_title,
                category2.title AS category2_title,
                section.title AS section_title,
                user.RealName AS RealName,
                (SELECT COUNT(*) FROM ".safe_pfx('txp_discuss')." WHERE parentid = textpattern.ID) AS total_comments
            FROM $sql_from WHERE $criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        if ($rs) {
            $common_atts = array(
                'event' => 'list',
                'step'  => 'list',
                'is_link' => true,
                'dir'   => $switch_dir,
                'crit'  => $crit,
                'method'=> $search_method,
            );

            $dates = array('posted', 'lastmod', 'expires');

            $head_row = hCell(
                fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                    '', 'class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
            ).column_head(array(
                'options' => array('class' => trim('txp-list-col-id'.('id' == $sort ? " $dir" : ''))),
                'value' => 'ID',
                'sort'  => 'id'
            ) + $common_atts);

            foreach ($headers as $header => $column_head) {
                $head_row .= column_head(array(
                        'options' => array(
                            'class' => trim('txp-list-col-'.$header.($header == $sort ? " $dir" : '').(in_array($header, $dates) ? ' date' : ''))),
                        'value' => $column_head,
                        'sort'  => $header
                    ) + $common_atts);
            }

            $contentBlock .= n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'articles_form',
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
                tr($head_row).
                n.tag_end('thead');

            include_once txpath.'/publish/taghandlers.php';
            $can_preview = has_privs('article.preview');

            $contentBlock .= n.tag_start('tbody');

            $validator = new Validator();

            while ($a = nextRow($rs)) {
                extract($a);

                if ($Title === '') {
                    $Title = '<em>'.eLink('article', 'edit', 'ID', $ID, gTxt('untitled'), '' , '', gTxt('edit')).'</em>';
                } else {
                    $Title = eLink('article', 'edit', 'ID', $ID, $Title, '', '', gTxt('edit'));
                }

                // Valid section and categories?
                $validator->setConstraints(array(new SectionConstraint($Section)));
                $vs = $validator->validate() ? '' : ' error';

                $validator->setConstraints(array(new CategoryConstraint($Category1, array('type' => 'article'))));
                $vc[1] = $validator->validate() ? '' : ' error';

                $validator->setConstraints(array(new CategoryConstraint($Category2, array('type' => 'article'))));
                $vc[2] = $validator->validate() ? '' : ' error';

                $Category1 = ($Category1) ? span(txpspecialchars($category1_title), array(
                    'title'      => $Category1,
                    'aria-label' => $Category1,
                )) : '';
                $Category2 = ($Category2) ? span(txpspecialchars($category2_title), array(
                    'title'      => $Category2,
                    'aria-label' => $Category2,
                )) : '';

                if ($Status != STATUS_LIVE and $Status != STATUS_STICKY) {
                    $view_url = $can_preview ? '?txpreview='.intval($ID).'.'.time() : '';
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
                        'step'          => 'discuss_list',
                        'search_method' => 'parent',
                        'crit'          => $ID,
                    ), array(
                        'title'      => gTxt('manage'),
                        'aria-label' => gTxt('manage'),
                    ));
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

                $contentBlock .= tr(
                    td(
                        (
                            (
                                ($a['Status'] >= STATUS_LIVE and has_privs('article.edit.published'))
                                or ($a['Status'] >= STATUS_LIVE and $AuthorID === $txp_user and has_privs('article.edit.own.published'))
                                or ($a['Status'] < STATUS_LIVE and has_privs('article.edit'))
                                or ($a['Status'] < STATUS_LIVE and $AuthorID === $txp_user and has_privs('article.edit.own'))
                            )
                        ? fInput('checkbox', 'selected[]', $ID, 'checkbox')
                        : ''
                        ), '', 'txp-list-col-multi-edit'
                    ).
                    hCell(
                        eLink('article', 'edit', 'ID', $ID, $ID, '', '', gTxt('edit')),
                        '',
                        array(
                            'class' => '',
                            'scope' => 'row',
                        )
                    ).
                    td(
                        $Title, '', 'txp-list-col-title'
                    ).
                    td(
                        gTime($posted), '', 'txp-list-col-posted date'.($posted < time() ? '' : ' unpublished')
                    ).
                    td(
                        gTime($lastmod), '', 'txp-list-col-lastmod date'.($posted === $lastmod ? ' not-modified' : '')
                    ).
                    td(
                        ($expires ? gTime($expires) : ''), '', 'txp-list-col-expires date'
                    ).
                    td(
                        span(txpspecialchars($section_title), array(
                            'title'      => $Section,
                            'aria-label' => $Section,
                        )), '', 'txp-list-col-section'.$vs
                    ).
                    td(
                        $Category1, '', 'txp-list-col-category1 category'.$vc[1]
                    ).
                    td(
                        $Category2, '', 'txp-list-col-category2 category'.$vc[2]
                    ).
                    td($view_url ?
                        href($Status, $view_url, join_atts(array(
                            'rel'        => 'noopener',
                            'target'     => '_blank',
                            'title'      => gTxt('view'),
                            'aria-label' => gTxt('view'),
                        ), TEXTPATTERN_STRIP_EMPTY)) : $Status, '', 'txp-list-col-status'
                    ).
                    (
                        $show_authors
                        ? td(span(txpspecialchars($RealName), array(
                            'title'      => $AuthorID,
                            'aria-label' => $AuthorID,
                        )), '', 'txp-list-col-author name')
                        : ''
                    ).
                    (
                        $use_comments
                        ? td($comments, '', 'txp-list-col-comments')
                        : ''
                    )
                );
            }

            $contentBlock .= n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div'). // End of .txp-listtables.
                list_multiedit_form($page, $sort, $dir, $crit, $search_method).
                tInput().
                n.tag_end('form');
        }
    }

    $pageBlock = $paginator->render().
        nav_form('list', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table($event);
    echo $table->render(compact('total', 'crit'), $searchBlock, $createBlock, $contentBlock, $pageBlock);
}

/**
 * Saves pageby value for the article list.
 */

function list_change_pageby()
{
    global $event;

    Txp::get('\Textpattern\Admin\Paginator', $event, 'article')->change();
    list_list();
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
    $statusa = has_privs('article.publish') ? $statuses : array_diff_key($statuses, array(STATUS_LIVE => 'live', STATUS_STICKY => 'sticky'));
    $status = selectInput('Status', $statusa, '', true);
    $authors = $all_authors ? selectInput('AuthorID', $all_authors, '', true) : '';

    $methods = array(
        'changestatus'    => array(
            'label' => gTxt('changestatus'),
            'html' => $status,
        ),
        'changesection'   => array(
            'label' => gTxt('changesection'),
            'html' => $sections,
        ),
        'changecategory1' => array(
            'label' => gTxt('changecategory1'),
            'html' => $category1,
        ),
        'changecategory2' => array(
            'label' => gTxt('changecategory2'),
            'html' => $category2,
        ),
        'changecomments'  => array(
            'label' => gTxt('changecomments'),
            'html' => $comments,
        ),
        'changeauthor'    => array(
            'label' => gTxt('changeauthor'),
            'html' => $authors,
        ),
        'duplicate'       => gTxt('duplicate'),
        'delete'          => gTxt('delete'),
    );

    if (!$all_cats) {
        unset($methods['changecategory1'], $methods['changecategory2']);
    }

    if (has_single_author('textpattern', 'AuthorID') || !has_privs('article.edit')) {
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

    // Fetch ids and remove bogus (false) entries to prevent SQL syntax errors being thrown.
    $selected = array_map('assert_int', $selected);
    $selected = array_filter($selected);

    // Empty entry to permit clearing the categories.
    $categories = array('');

    foreach ($all_cats as $row) {
        $categories[] = $row['name'];
    }

    $allowed = array();
    $field = $value = '';

    switch ($edit_method) {
        // Delete.
        case 'delete':
            if (!has_privs('article.delete')) {
                if ($selected && has_privs('article.delete.own')) {
                    $allowed = safe_column_num(
                        "ID",
                        'textpattern',
                        "ID IN (".join(',', $selected).") AND AuthorID = '".doSlash($txp_user)."'"
                    );
                }

                $selected = $allowed;
            }

            // @todo Post-delete callback should match event/step here when hooks are standardised.
            callback_event('articles', 'multi_edit.'.$edit_method, 1, compact('selected', 'field', 'value'));

            if ($selected && safe_delete('textpattern', "ID IN (".join(',', $selected).")")) {
                callback_event('articles_deleted', '', 0, $selected);
                callback_event('multi_edited.articles', 'delete', 0, compact('selected', 'field', 'value'));
                safe_delete('txp_discuss', "parentid IN (".join(',', $selected).")");
                update_lastmod('articles_deleted', $selected);
                now('posted', true);
                now('expires', true);

                return list_list(gTxt('articles_deleted', array('{list}' => join(', ', $selected))));
            }

            return list_list();
            break;
        // Change author.
        case 'changeauthor':
            $value = ps('AuthorID');
            if (has_privs('article.edit') && isset($all_authors[$value])) {
                $field = 'AuthorID';
            }
            break;

        // Change category1.
        case 'changecategory1':
            $value = ps('Category1');
            if (in_array($value, $categories, true)) {
                $field = 'Category1';
            }
            break;
        // Change category2.
        case 'changecategory2':
            $value = ps('Category2');
            if (in_array($value, $categories, true)) {
                $field = 'Category2';
            }
            break;
        // Change comment status.
        case 'changecomments':
            $field = 'Annotate';
            $value = (int) ps('Annotate');
            break;
        // Change section.
        case 'changesection':
            $value = ps('Section');
            if (isset($all_sections[$value])) {
                $field = 'Section';
            }
            break;
        // Change status.
        case 'changestatus':
            $value = (int) ps('Status');
            if (array_key_exists($value, $statuses)) {
                $field = 'Status';
            }

            if (!has_privs('article.publish') && $value >= STATUS_LIVE) {
                $value = STATUS_PENDING;
            }
            break;
    }

    if ($selected) {
        $selected = safe_rows(
            "ID, AuthorID, Status",
            'textpattern',
            "ID IN (".join(',', $selected).")"
        );
    }

    foreach ($selected as $item) {
        if (
            ($item['Status'] >= STATUS_LIVE && has_privs('article.edit.published')) ||
            ($item['Status'] >= STATUS_LIVE && $item['AuthorID'] === $txp_user && has_privs('article.edit.own.published')) ||
            ($item['Status'] < STATUS_LIVE && has_privs('article.edit')) ||
            ($item['Status'] < STATUS_LIVE && $item['AuthorID'] === $txp_user && has_privs('article.edit.own'))
        ) {
            $allowed[] = $item['ID'];
        }
    }

    $selected = $allowed;

    // @todo Post-edit callback should match event/step here when hooks are standardised.
    callback_event('articles', 'multi_edit.'.$edit_method, 1, compact('selected', 'field', 'value'));

    if ($selected) {
        $message = gTxt('articles_modified', array('{list}' => join(', ', $selected)));

        if ($edit_method === 'duplicate') {
            $rs = safe_rows_start("*", 'textpattern', "ID IN (".join(',', $selected).")");
            $created = $selected = array();

            if ($rs) {
                while ($a = nextRow($rs)) {
                    $pid = $a['ID'];
                    $title = $a['Title'];
                    unset($a['ID'], $a['comments_count']);
                    $a['uid'] = md5(uniqid(rand(), true));
                    $a['AuthorID'] = $txp_user;
                    $a['LastModID'] = $txp_user;
                    $a['Status'] = ($a['Status'] >= STATUS_LIVE) ? STATUS_DRAFT : $a['Status'];

                    foreach ($a as $name => &$value) {
                        if ($name == 'Expires' && !$value) {
                            $value = "Expires = NULL";
                        } else {
                            $value = "`$name` = '".doSlash($value)."'";
                        }
                    }

                    if ($id = (int) safe_insert('textpattern', join(',', $a))) {
                        $url_title = stripSpace($title." ($id)", 1);
                        $created[$id] = $url_title;
                        $selected[$id] = $pid;
                    }
                }

                if ($created) {
                    $ids = implode(',', array_keys($created));
                    $titles = quote_list($created, ',');
                    safe_update(
                        'textpattern',
                        "Title     = CONCAT(Title, ' (', ID, ')'),
                         url_title = ELT(FIELD(ID, $ids), $titles),
                         LastMod   = NOW(),
                         feed_time = NOW()",
                        "ID IN ($ids)"
                    );
                }
            }

            $message = gTxt('articles_duplicated', array('{list}' => join(', ', $selected)));
        } elseif (!$field || safe_update('textpattern', "$field = '".doSlash($value)."'", "ID IN (".join(',', $selected).")") === false) {
            return list_list();
        }

        update_lastmod('articles_updated', compact('selected', 'field', 'value'));
        now('posted', true);
        now('expires', true);
        callback_event('multi_edited.articles', $edit_method, 0, compact('selected', 'field', 'value'));

        return list_list($message);
    }

    return list_list();
}

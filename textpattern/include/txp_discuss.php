<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2016 The Textpattern Development Team
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
 * Comments panel.
 *
 * @package Admin\Discuss
 */

use Textpattern\Validator\ChoiceConstraint;
use Textpattern\Validator\Validator;
use Textpattern\Search\Filter;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'discuss') {
    require_privs('discuss');

    if (!get_pref('use_comments', 1)) {
        require_privs();
    }

    $available_steps = array(
        'discuss_save'          => true,
        'discuss_list'          => false,
        'discuss_edit'          => false,
        'discuss_multi_edit'    => true,
        'discuss_change_pageby' => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        discuss_list();
    }
}

//-------------------------------------------------------------

function discuss_save()
{
    $varray = array_map('assert_string', gpsa(array('email', 'name', 'web', 'message', 'ip')));
    $varray = $varray + array_map('assert_int', gpsa(array('discussid', 'visible', 'parentid')));
    extract(doSlash($varray));

    $message = $varray['message'] = preg_replace('#<(/?txp:.+?)>#', '&lt;$1&gt;', $message);

    $constraints = array(
        'status' => new ChoiceConstraint($visible, array(
            'choices' => array(SPAM, MODERATE, VISIBLE),
            'message' => 'invalid_status',
        )),
    );

    callback_event_ref('discuss_ui', 'validate_save', 0, $varray, $constraints);
    $validator = new Validator($constraints);

    if ($validator->validate() && safe_update('txp_discuss',
        "email   = '$email',
         name    = '$name',
         web     = '$web',
         message = '$message',
         visible = $visible",
        "discussid = $discussid"
    )) {
        update_comments_count($parentid);
        update_lastmod('discuss_saved', compact('discussid', 'email', 'name', 'web', 'message', 'ip', 'visible', 'parentid'));
        $message = gTxt('comment_updated', array('{id}' => $discussid));
    } else {
        $message = array(gTxt('comment_save_failed'), E_ERROR);
    }

    discuss_list($message);
}

//-------------------------------------------------------------

function short_preview($message)
{
    $message = strip_tags($message);
    $offset = min(120, strlen($message));

    if (strpos($message, ' ', $offset) !== false) {
        $maxpos = strpos($message, ' ', $offset);
        $message = substr($message, 0, $maxpos).'&#8230;';
    }

    return $message;
}

/**
 * Outputs the main panel listing all comments.
 *
 * @param  string|array $message The activity message
 */

function discuss_list($message = '')
{
    global $event, $comment_list_pageby;

    pagetop(gTxt('list_discussions'), $message);

    extract(gpsa(array(
        'sort',
        'dir',
        'page',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('discuss_sort_column', 'date');
    } else {
        if (!in_array($sort, array('id', 'ip', 'name', 'email', 'website', 'message', 'status', 'parent'))) {
            $sort = 'date';
        }

        set_pref('discuss_sort_column', $sort, 'discuss', 2, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('discuss_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('discuss_sort_dir', $dir, 'discuss', 2, '', 0, PREF_PRIVATE);
    }

    switch ($sort) {
        case 'id':
            $sort_sql = "txp_discuss.discussid $dir";
            break;
        case 'ip':
            $sort_sql = "txp_discuss.ip $dir";
            break;
        case 'name':
            $sort_sql = "txp_discuss.name $dir";
            break;
        case 'email':
            $sort_sql = "txp_discuss.email $dir";
            break;
        case 'website':
            $sort_sql = "txp_discuss.web $dir";
            break;
        case 'message':
            $sort_sql = "txp_discuss.message $dir";
            break;
        case 'status':
            $sort_sql = "txp_discuss.visible $dir";
            break;
        case 'parent':
            $sort_sql = "txp_discuss.parentid $dir";
            break;
        default:
            $sort = 'date';
            $sort_sql = "txp_discuss.posted $dir";
            break;
    }

    if ($sort != 'date') {
        $sort_sql .= ", txp_discuss.posted ASC";
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'id' => array(
                'column' => 'txp_discuss.discussid',
                'label'  => gTxt('ID'),
                'type'   => 'integer',
            ),
            'parent' => array(
                'column' => array('txp_discuss.parentid', 'textpattern.Title'),
                'label'  => gTxt('parent'),
            ),
            'name' => array(
                'column' => 'txp_discuss.name',
                'label'  => gTxt('name'),
            ),
            'message' => array(
                'column' => 'txp_discuss.message',
                'label'  => gTxt('message'),
            ),
            'email' => array(
                'column' => 'txp_discuss.email',
                'label'  => gTxt('email'),
            ),
            'website' => array(
                'column' => 'txp_discuss.web',
                'label'  => gTxt('website'),
            ),
            'ip' => array(
                'column' => 'txp_discuss.ip',
                'label'  => gTxt('IP'),
            ),
            'visible' => array(
                'column' => 'txp_discuss.visible',
                'label'  => gTxt('visible'),
                'type'   => 'numeric',
            ),
        )
    );

    $alias_yes = VISIBLE.', Yes';
    $alias_no = MODERATE.', No, Unmoderated, Pending';
    $alias_spam = SPAM.', Spam';

    $search->setAliases('visible', array(
        VISIBLE => $alias_yes,
        MODERATE => $alias_no,
        SPAM => $alias_spam,
        ));

    list($criteria, $crit, $search_method) = $search->getFilter(array(
            'id' => array('can_list' => true),
        ));

    $search_render_options = array(
        'placeholder' => 'search_comments',
    );

    $sql_from =
        safe_pfx_j('txp_discuss')."
        left join ".safe_pfx_j('textpattern')." on txp_discuss.parentid = textpattern.ID";

    $counts = getRows(
        "SELECT txp_discuss.visible, COUNT(*) AS c
        FROM ".safe_pfx_j('txp_discuss')."
            LEFT JOIN ".safe_pfx_j('textpattern')."
            ON txp_discuss.parentid = textpattern.ID
        WHERE $criteria GROUP BY txp_discuss.visible"
    );

    $count[SPAM] = $count[MODERATE] = $count[VISIBLE] = 0;

    if ($counts) {
        foreach ($counts as $c) {
            $count[$c['visible']] = $c['c'];
        }
    }

    // Grand total comment count.
    $total = $count[SPAM] + $count[MODERATE] + $count[VISIBLE];

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('list_discussions'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-4col-alt')
        );

    $searchBlock =
        n.tag(
            $search->renderForm('discuss_list', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $contentBlockStart = n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => $event.'_container',
        ));

    if ($total < 1) {
        if ($criteria != 1) {
            echo $searchBlock.
                $contentBlockStart.
                graf(
                    span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                    gTxt('no_results_found'),
                    array('class' => 'alert-block information')
                );
        } else {
            echo $contentBlockStart.
                graf(
                    span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                    gTxt('no_comments_recorded'),
                    array('class' => 'alert-block information')
                );
        }

        echo n.tag_end('div'). // End of .txp-layout-1col.
            n.'</div>'; // End of .txp-layout.

        return;
    }

    if (!cs('toggle_show_spam')) {
        $total = $count[MODERATE] + $count[VISIBLE];
        $criteria = 'visible != '.intval(SPAM).' and '.$criteria;
    }

    $limit = max($comment_list_pageby, 15);

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    echo $searchBlock.$contentBlockStart;

    $rs = safe_query(
        "SELECT
            txp_discuss.discussid,
            txp_discuss.parentid,
            txp_discuss.name,
            txp_discuss.email,
            txp_discuss.web,
            txp_discuss.ip,
            txp_discuss.message,
            txp_discuss.visible,
            UNIX_TIMESTAMP(txp_discuss.posted) AS uPosted,
            textpattern.ID AS thisid,
            textpattern.Section AS section,
            textpattern.url_title,
            textpattern.Title AS title,
            textpattern.Status,
            UNIX_TIMESTAMP(textpattern.Posted) AS posted
        FROM ".safe_pfx_j('txp_discuss')."
            LEFT JOIN ".safe_pfx_j('textpattern')." ON txp_discuss.parentid = textpattern.ID
        WHERE $criteria ORDER BY $sort_sql LIMIT $offset, $limit"
    );

    if ($rs) {
        echo n.tag(
                cookie_box('show_spam').
                toggle_box('discuss_detail'),
                'div', array('class' => 'txp-list-options')).
            n.tag_start('form', array(
                'class'  => 'multi_edit_form',
                'id'     => 'discuss_form',
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
                    'ID', 'id', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('id' == $sort) ? "$dir " : '').'txp-list-col-id'
                ).
                column_head(
                    'date', 'date', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('date' == $sort) ? "$dir " : '').'txp-list-col-created date'
                ).
                column_head(
                    'name', 'name', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                ).
                column_head(
                    'message', 'message', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('message' == $sort) ? "$dir " : 'txp-list-col-message')
                ).
                column_head(
                    'email', 'email', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('email' == $sort) ? "$dir " : '').'txp-list-col-email discuss_detail'
                ).
                column_head(
                    'website', 'website', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('website' == $sort) ? "$dir " : '').'txp-list-col-website discuss_detail'
                ).
                column_head(
                    'IP', 'ip', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('ip' == $sort) ? "$dir " : '').'txp-list-col-ip discuss_detail'
                ).
                column_head(
                    'status', 'status', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('status' == $sort) ? "$dir " : '').'txp-list-col-status'
                ).
                column_head(
                    'parent', 'parent', 'discuss', true, $switch_dir, $crit, $search_method,
                        (('parent' == $sort) ? "$dir " : '').'txp-list-col-parent'
                )
            ).
            n.tag_end('thead');

        include_once txpath.'/publish/taghandlers.php';

        echo n.tag_start('tbody');

        while ($a = nextRow($rs)) {
            extract($a);
            $parentid = assert_int($parentid);

            $edit_url = array(
                'event'         => 'discuss',
                'step'          => 'discuss_edit',
                'discussid'     => $discussid,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
            );

            $dmessage = ($visible == SPAM) ? short_preview($message) : $message;

            switch ($visible) {
                case VISIBLE:
                    $comment_status = gTxt('visible');
                    $row_class = 'visible';
                    break;
                case SPAM:
                    $comment_status = gTxt('spam');
                    $row_class = 'spam';
                    break;
                case MODERATE:
                    $comment_status = gTxt('unmoderated');
                    $row_class = 'moderate';
                    break;
                default:
                    break;
            }

            if (empty($thisid)) {
                $parent = gTxt('article_deleted').' ('.$parentid.')';
                $view = '';
            } else {
                $parent_title = empty($title) ? '<em>'.gTxt('untitled').'</em>' : escape_title($title);

                $parent = href($parent_title, '?event=article'.a.'step=edit'.a.'ID='.$parentid);

                $view = $comment_status;

                if ($visible == VISIBLE and in_array($Status, array(4, 5))) {
                    $view = href($comment_status, permlinkurl($a).'#c'.$discussid, ' title="'.gTxt('view').'"');
                }
            }

            echo tr(
                td(
                    fInput('checkbox', 'selected[]', $discussid), '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    href($discussid, $edit_url, ' title="'.gTxt('edit').'"'), '', ' class="txp-list-col-id" scope="row"'
                ).
                td(
                    gTime($uPosted), '', 'txp-list-col-created date'
                ).
                td(
                    txpspecialchars(soft_wrap($name, 15)), '', 'txp-list-col-name'
                ).
                td(
                    short_preview($dmessage), '', 'txp-list-col-message'
                ).
                td(
                    txpspecialchars(soft_wrap($email, 15)), '', 'txp-list-col-email discuss_detail'
                ).
                td(
                    txpspecialchars(soft_wrap($web, 15)), '', 'txp-list-col-website discuss_detail'
                ).
                td(
                    href(txpspecialchars($ip), 'https://whois.domaintools.com/'.rawurlencode($ip), array(
                        'rel'    => 'external',
                        'target' => '_blank',
                    )), '', 'txp-list-col-ip discuss_detail'
                ).
                td(
                    $view, '', 'txp-list-col-status'
                ).
                td(
                    $parent, '', 'txp-list-col-parent'
                ), ' class="'.$row_class.'"'
            );
        }

        if (empty($message)) {
            echo n.tr(tda(gTxt('just_spam_results_found'), ' colspan="10"'));
        }

        echo n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div'). // End of .txp-listtables.
            discuss_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            n.tag_start('div', array(
                'class' => 'txp-navigation',
                'id'    => $event.'_navigation',
            )).
            pageby_form('discuss', $comment_list_pageby).
            nav_form('discuss', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div');
    }

    echo n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.
}

/**
 * Renders and outputs the comment editor panel.
 */

function discuss_edit()
{
    global $event;

    pagetop(gTxt('edit_comment'));

    extract(gpsa(array(
        'discussid',
        'sort',
        'dir',
        'page',
        'crit',
        'search_method',
    )));

    $discussid = assert_int($discussid);

    $rs = safe_row("*, UNIX_TIMESTAMP(posted) AS uPosted", 'txp_discuss', "discussid = $discussid");

    if ($rs) {
        extract($rs);

        $message = txpspecialchars($message);

        $status_list = selectInput(
            'visible',
            array(
                VISIBLE  => gTxt('visible'),
                SPAM     => gTxt('spam'),
                MODERATE => gTxt('unmoderated'),
            ),
            $visible,
            false,
            '',
            'status');

        echo form(
                hed(gTxt('edit_comment'), 2).
                inputLabel(
                    'status',
                    $status_list,
                    'status', '', array('class' => 'txp-form-field edit-comment-status')
                ).
                inputLabel(
                    'name',
                    fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'name'),
                    'name', '', array('class' => 'txp-form-field edit-comment-name')
                ).
                inputLabel(
                    'IP',
                    href(txpspecialchars($ip), 'https://whois.domaintools.com/'.rawurlencode($ip), array(
                        'rel'    => 'external',
                        'target' => '_blank',
                    )),
                    '', '', array('class' => 'txp-form-field edit-comment-ip')
                ).
                inputLabel(
                    'email',
                    fInput('email', 'email', $email, '', '', '', INPUT_REGULAR, '', 'email'),
                    'email', '', array('class' => 'txp-form-field edit-comment-email')
                ).
                inputLabel(
                    'website',
                    fInput('text', 'web', $web, '', '', '', INPUT_REGULAR, '', 'website'),
                    'website', '', array('class' => 'txp-form-field edit-comment-website')
                ).
                inputLabel(
                    'date',
                    safe_strftime('%d %b %Y %X',
                    $uPosted),
                    '', '', array('class' => 'txp-form-field edit-comment-date')
                ).
                inputLabel(
                    'commentmessage',
                    '<textarea id="commentmessage" name="message" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_MEDIUM.'">'.$message.'</textarea>',
                    'message', '', array('class' => 'txp-form-field txp-form-field-textarea edit-comment-message')
                ).
                graf(
                    sLink('discuss', '', gTxt('cancel'), 'txp-button').
                    fInput('submit', 'step', gTxt('save'), 'publish'),
                    array('class' => 'txp-edit-actions')
                ).
                hInput('sort', $sort).
                hInput('dir', $dir).
                hInput('page', $page).
                hInput('crit', $crit).
                hInput('search_method', $search_method).
                hInput('discussid', $discussid).
                hInput('parentid', $parentid).
                hInput('ip', $ip).
                eInput('discuss').
                sInput('discuss_save'),
            '', '', 'post', 'txp-edit', '', 'discuss_edit_form');
    } else {
        echo graf(
            span(null, array('class' => 'ui-icon ui-icon-info')).' '.
            gTxt('comment_not_found'),
            array('class' => 'alert-block information')
        );
    }
}

// -------------------------------------------------------------

function discuss_change_pageby()
{
    event_change_pageby('comment');
    discuss_list();
}

// -------------------------------------------------------------

function discuss_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    $methods = array(
        'visible'     => gTxt('show'),
        'unmoderated' => gTxt('hide_unmoderated'),
        'spam'        => gTxt('hide_spam'),
        'delete'      => gTxt('delete'),
    );

    return multi_edit($methods, 'discuss', 'discuss_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

// -------------------------------------------------------------

function discuss_multi_edit()
{
    // FIXME: this method needs some refactoring.
    $selected = ps('selected');
    $method = ps('edit_method');
    $done = array();

    if ($selected and is_array($selected)) {
        // Get all articles for which we have to update the count.
        foreach ($selected as $id) {
            $ids[] = assert_int($id);
        }
        $parentids = safe_column("DISTINCT parentid", 'txp_discuss', "discussid IN (".implode(',', $ids).")");

        $rs = safe_rows_start("*", 'txp_discuss', "discussid IN (".implode(',', $ids).")");

        while ($row = nextRow($rs)) {
            extract($row);
            $id = assert_int($discussid);
            $parentids[] = $parentid;

            if ($method == 'delete') {
                // Delete and, if successful, update comment count.
                if (safe_delete('txp_discuss', "discussid = $id")) {
                    $done[] = $id;
                }

                callback_event('discuss_deleted', '', 0, $done);
            } elseif ($method == 'spam') {
                if (safe_update('txp_discuss',
                    "visible = ".SPAM,
                    "discussid = $id"
                )) {
                    $done[] = $id;
                }
            } elseif ($method == 'unmoderated') {
                if (safe_update('txp_discuss',
                    "visible = ".MODERATE,
                    "discussid = $id"
                )) {
                    $done[] = $id;
                }
            } elseif ($method == 'visible') {
                if (safe_update('txp_discuss',
                    "visible = ".VISIBLE,
                    "discussid = $id"
                )) {
                    $done[] = $id;
                }
            }
        }

        $doneStr = join(', ', $done);

        if ($doneStr) {
            // Might as well clean up all comment counts while we're here.
            clean_comment_counts($parentids);

            $messages = array(
                'delete'      => gTxt('comments_deleted', array('{list}' => $doneStr)),
                'spam'        => gTxt('comments_marked_spam', array('{list}' => $doneStr)),
                'unmoderated' => gTxt('comments_marked_unmoderated', array('{list}' => $doneStr)),
                'visible'     => gTxt('comments_marked_visible', array('{list}' => $doneStr)),
            );

            update_lastmod('discuss_updated', $done);

            return discuss_list($messages[$method]);
        }
    }

    return discuss_list();
}

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

if ($event == 'discuss') {
    require_privs('discuss');

    if (!get_pref('use_comments', 1)) {
        require_privs();
    }

    $available_steps = array(
        'discuss_save'          => true,
        'discuss_list'          => false,
        'discuss_edit'          => false,
        'ipban_add'             => true,
        'discuss_multi_edit'    => true,
        'ipban_list'            => false,
        'ipban_unban'           => true,
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
            'message' => 'invalid_status'
        ))
    );

    callback_event_ref('discuss_ui', 'validate_save', 0, $varray, $constraints);
    $validator = new Validator($constraints);

    if ($validator->validate() && safe_update("txp_discuss",
        "email   = '$email',
         name    = '$name',
         web     = '$web',
         message = '$message',
         visible = $visible",
        "discussid = $discussid"
    )) {
        update_comments_count($parentid);
        update_lastmod();
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

//-------------------------------------------------------------

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
    }

    if ($dir === '') {
        $dir = get_pref('discuss_sort_dir', 'desc');
    }

    $dir = ($dir == 'asc') ? 'asc' : 'desc';

    switch ($sort) {
        case 'id' :
            $sort_sql = 'txp_discuss.discussid '.$dir;
            break;
        case 'ip' :
            $sort_sql = 'txp_discuss.ip '.$dir;
            break;
        case 'name' :
            $sort_sql = 'txp_discuss.name '.$dir;
            break;
        case 'email' :
            $sort_sql = 'txp_discuss.email '.$dir;
            break;
        case 'website' :
            $sort_sql = 'txp_discuss.web '.$dir;
            break;
        case 'message' :
            $sort_sql = 'txp_discuss.message '.$dir;
            break;
        case 'status' :
            $sort_sql = 'txp_discuss.visible '.$dir;
            break;
        case 'parent' :
            $sort_sql = 'txp_discuss.parentid '.$dir;
            break;
        default :
            $sort = 'date';
            $sort_sql = 'txp_discuss.posted '.$dir;
            break;
    }

    if ($sort != 'date') {
        $sort_sql .= ', txp_discuss.posted asc';
    }

    set_pref('discuss_sort_column', $sort, 'discuss', 2, '', 0, PREF_PRIVATE);
    set_pref('discuss_sort_dir', $dir, 'discuss', 2, '', 0, PREF_PRIVATE);

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $criteria = 1;

    if ($search_method and $crit != '') {
        $verbatim = preg_match('/^"(.*)"$/', $crit, $m);
        $crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
        $critsql = $verbatim ?
            array(
                'id'      => "txp_discuss.discussid in ('" .join("','", do_list($crit_escaped)). "')",
                'parent'  => "txp_discuss.parentid = '$crit_escaped'".((string) intval($crit_escaped) === $crit_escaped ? '' : " or textpattern.Title = '$crit_escaped'"),
                'name'    => "txp_discuss.name = '$crit_escaped'",
                'message' => "txp_discuss.message = '$crit_escaped'",
                'email'   => "txp_discuss.email = '$crit_escaped'",
                'website' => "txp_discuss.web = '$crit_escaped'",
                'ip'      => "txp_discuss.ip = '$crit_escaped'",
            ) : array(
                'id'      => "txp_discuss.discussid in ('" .join("','", do_list($crit_escaped)). "')",
                'parent'  => "txp_discuss.parentid = '$crit_escaped'".((string) intval($crit_escaped) === $crit_escaped ? '' : " or textpattern.Title like '%$crit_escaped%'"),
                'name'    => "txp_discuss.name like '%$crit_escaped%'",
                'message' => "txp_discuss.message like '%$crit_escaped%'",
                'email'   => "txp_discuss.email like '%$crit_escaped%'",
                'website' => "txp_discuss.web like '%$crit_escaped%'",
                'ip'      => "txp_discuss.ip like '%$crit_escaped%'",
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

    $criteria .= callback_event('admin_criteria', 'discuss_list', 0, $criteria);

    $counts = getRows(
        "select txp_discuss.visible, COUNT(*) AS c
        from ".safe_pfx_j('txp_discuss')."
        left join ".safe_pfx_j('textpattern')." ON txp_discuss.parentid = textpattern.ID
        where {$criteria} group by txp_discuss.visible"
    );

    $count[SPAM] = $count[MODERATE] = $count[VISIBLE] = 0;

    if ($counts) foreach ($counts as $c) {
        $count[$c['visible']] = $c['c'];
    }

    // grand total comment count
    $total = $count[SPAM] + $count[MODERATE] + $count[VISIBLE];

    echo hed(gTxt('list_discussions'), 1, array('class' => 'txp-heading'));
    echo n.'<div id="'.$event.'_control" class="txp-control-panel">';
    echo graf(
        sLink('discuss', 'ipban_list', gTxt('list_banned_ips'))
        , ' class="txp-buttons"');

    if ($total < 1) {
        if ($criteria != 1) {
            echo discuss_search_form($crit, $search_method).
                graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
        } else {
            echo graf(gTxt('no_comments_recorded'), ' class="indicator"').'</div>';
        }

        return;
    }

    echo discuss_search_form($crit, $search_method).'</div>';

    if (!cs('toggle_show_spam')) {
        $total = $count[MODERATE] + $count[VISIBLE];
        $criteria = 'visible != '.intval(SPAM).' and '.$criteria;
    }

    $limit = max($comment_list_pageby, 15);
    list($page, $offset, $numPages) = pager($total, $limit, $page);

    $rs = safe_query(
        "select
        txp_discuss.discussid,
        txp_discuss.parentid,
        txp_discuss.name,
        txp_discuss.email,
        txp_discuss.web,
        txp_discuss.ip,
        txp_discuss.message,
        txp_discuss.visible,
        unix_timestamp(txp_discuss.posted) as uPosted,
        textpattern.ID as thisid,
        textpattern.Section as section,
        textpattern.url_title,
        textpattern.Title as title,
        textpattern.Status,
        unix_timestamp(textpattern.Posted) as posted
        from ".safe_pfx_j('txp_discuss')."
        left join ".safe_pfx_j('textpattern')." on txp_discuss.parentid = textpattern.ID
        where {$criteria} order by {$sort_sql} limit {$offset}, {$limit}"
    );

    if ($rs) {
        echo
            n.tag_start('div', array(
                'id'    => $event.'_container',
                'class' => 'txp-container',
            )).
            n.tag_start('form', array(
                'action' => 'index.php',
                'id'     => 'discuss_form',
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
                case VISIBLE :
                    $comment_status = gTxt('visible');
                    $row_class = 'visible';
                    break;
                case SPAM :
                    $comment_status = gTxt('spam');
                    $row_class = 'spam';
                    break;
                case MODERATE :
                    $comment_status = gTxt('unmoderated');
                    $row_class = 'moderate';
                    break;
                default :
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
                    href($discussid, $edit_url, ' title="'.gTxt('edit').'"'), '', ' scope="row" class="txp-list-col-id"'
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
                    $ip, '', 'txp-list-col-ip discuss_detail'
                ).
                td(
                    $view, '', 'txp-list-col-status'
                ).
                td(
                    $parent, '', 'txp-list-col-parent'
                )
                , ' class="'.$row_class.'"'
            );
        }

        if (empty($message)) {
            echo n.tr(tda(gTxt('just_spam_results_found'), ' colspan="10"'));
        }

        echo
            n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div').
            discuss_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            graf(toggle_box('discuss_detail'), array('class' => 'detail-toggle')).
            cookie_box('show_spam').
            n.tag_start('div', array(
                'id'    => $event.'_navigation',
                'class' => 'txp-navigation',
            )).
            pageby_form('discuss', $comment_list_pageby).
            nav_form('discuss', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div').
            n.tag_end('div');
    }
}

//-------------------------------------------------------------

function discuss_search_form($crit, $method)
{
    $methods = array(
        'id'      => gTxt('ID'),
        'parent'  => gTxt('parent'),
        'name'    => gTxt('name'),
        'message' => gTxt('message'),
        'email'   => gTxt('email'),
        'website' => gTxt('website'),
        'ip'      => gTxt('IP'),
    );

    return search_form('discuss', 'list', $crit, $methods, $method, 'message');
}

//-------------------------------------------------------------

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

    $rs = safe_row('*, unix_timestamp(posted) as uPosted', 'txp_discuss', "discussid = $discussid");

    if ($rs) {
        extract($rs);

        $message = txpspecialchars($message);

        if (fetch('ip', 'txp_discuss_ipban', 'ip', $ip)) {
            $ban_step = 'ipban_unban';
            $ban_text = gTxt('unban');
        } else {
            $ban_step = 'ipban_add';
            $ban_text = gTxt('ban');
        }

        $ban_link = sp.span('[', array('aria-hidden' => 'true')).
            href(
                $ban_text,
                array(
                    'event'      => 'discuss',
                    'step'       => $ban_step,
                    'ip'         => $ip,
                    'name'       => $name,
                    'discussid'  => $discussid,
                    '_txp_token' => form_token(),
                ),
                array('class' => 'action-ban')
            ).
            span(']', array('aria-hidden' => 'true'));

        $status_list = selectInput(
            'visible',
            array(
                VISIBLE  => gTxt('visible'),
                SPAM     => gTxt('spam'),
                MODERATE => gTxt('unmoderated')
            ),
            $visible,
            false,
            '',
            'status');

        echo '<div id="'.$event.'_container" class="txp-container">'.
            form(
                n.'<section class="txp-edit">'.
                hed(gTxt('edit_comment'), 2).
                inputLabel('status', $status_list, 'status').
                inputLabel('name', fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'name'), 'name').
                inputLabel('IP', $ip.$ban_link, '').
                inputLabel('email', fInput('email', 'email', $email, '', '', '', INPUT_REGULAR, '', 'email'), 'email').
                inputLabel('website', fInput('text', 'web', $web, '', '', '', INPUT_REGULAR, '', 'website'), 'website').
                inputLabel('date', safe_strftime('%d %b %Y %X', $uPosted), '').
                inputLabel('commentmessage', '<textarea id="commentmessage" name="message" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_REGULAR.'">'.$message.'</textarea>', 'message', '', '', '').
                graf(fInput('submit', 'step', gTxt('save'), 'publish')).

                hInput('sort', $sort).
                hInput('dir', $dir).
                hInput('page', $page).
                hInput('crit', $crit).
                hInput('search_method', $search_method).

                hInput('discussid', $discussid).
                hInput('parentid', $parentid).
                hInput('ip', $ip).

                eInput('discuss').
                sInput('discuss_save').
                n.'</section>'
            , '', '', 'post', 'edit-form', '', 'discuss_edit_form'), '</div>';
    } else {
        echo graf(gTxt('comment_not_found'), ' class="indicator"');
    }
}

// -------------------------------------------------------------

function ipban_add()
{
    extract(gpsa(array('ip', 'name', 'discussid')));
    $discussid = assert_int($discussid);

    if (!$ip) {
        return ipban_list(gTxt('cant_ban_blank_ip'));
    }

    $ban_exists = fetch('ip', 'txp_discuss_ipban', 'ip', $ip);

    if ($ban_exists) {
        $message = gTxt('ip_already_banned', array('{ip}' => $ip));

        return ipban_list($message);
    }

    $rs = safe_insert('txp_discuss_ipban', "
        ip = '".doSlash($ip)."',
        name_used = '".doSlash($name)."',
        banned_on_message = $discussid,
        date_banned = now()
    ");

    // hide all messages from that IP also
    if ($rs) {
        safe_update('txp_discuss', "visible = ".SPAM, "ip = '".doSlash($ip)."'");

        $message = gTxt('ip_banned', array('{ip}' => $ip));

        return ipban_list($message);
    }

    ipban_list();
}

// -------------------------------------------------------------

function ipban_unban()
{
    $ip = doSlash(gps('ip'));

    $rs = safe_delete('txp_discuss_ipban', "ip = '$ip'");

    if ($rs) {
        $message = gTxt('ip_ban_removed', array('{ip}' => $ip));

        ipban_list($message);
    }
}

// -------------------------------------------------------------

function ipban_list($message = '')
{
    global $event;

    pageTop(gTxt('list_banned_ips'), $message);

    echo hed(gTxt('banned_ips'), 1, array('class' => 'txp-heading'));
    echo n.'<div id="'.$event.'_banned_control" class="txp-control-panel">'.
        graf(
            sLink('discuss', 'discuss_list', gTxt('list_discussions'))
        , ' class="txp-buttons"').
        n.'</div>';

    $rs = safe_rows_start('*, unix_timestamp(date_banned) as uBanned', 'txp_discuss_ipban',
        "1 = 1 order by date_banned desc");

    if ($rs and numRows($rs) > 0) {
        echo
            n.tag_start('div', array(
                'id'    => $event.'_ban_container',
                'class' => 'txp-container',
            )).
            n.tag_start('div', array('class' => 'txp-listtables')).
            n.tag_start('table', array('class' => 'txp-list')).
            n.tag_start('thead').
            tr(
                hCell(
                    gTxt('date_banned'), '', ' scope="col" class="txp-list-col-banned date"'
                ).
                hCell(
                    gTxt('IP'), '', ' scope="col" class="txp-list-col-ip"'
                ).
                hCell(
                    gTxt('name_used'), '', ' scope="col" class="txp-list-col-name"'
                ).
                hCell(
                    gTxt('banned_for'), '', ' scope="col" class="txp-list-col-id"'
                )
            ).
            n.tag_end('thead').
            n.tag_start('tbody');

        while ($a = nextRow($rs)) {
            extract($a);

            echo tr(
                hCell(
                    gTime($uBanned), '', ' scope="row" class="txp-list-col-banned date"'
                ).
                td(
                    txpspecialchars($ip).
                    sp.span('[', array('aria-hidden' => 'true')).
                    href(
                        gTxt('unban'),
                        array(
                            'event'      => 'discuss',
                            'step'       => 'ipban_unban',
                            'ip'         => $ip,
                            '_txp_token' => form_token(),
                        ),
                        array('class' => 'action-ban')
                    ).
                    span(']', array('aria-hidden' => 'true')), '', 'txp-list-col-ip'
                ).
                td(
                    txpspecialchars($name_used), '', 'txp-list-col-name'
                ).
                td(
                    href($banned_on_message, '?event=discuss'.a.'step=discuss_edit'.a.'discussid='.$banned_on_message), '', 'txp-list-col-id'
                )
            );
        }

        echo
            n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div').
            n.tag_end('div');
    } else {
        echo graf(gTxt('no_ips_banned'), ' class="indicator"');
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
        'ban'         => gTxt('ban_author'),
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
        foreach($selected as $id)
            $ids[] = assert_int($id);
        $parentids = safe_column("DISTINCT parentid", "txp_discuss", "discussid IN (".implode(',', $ids).")");

        $rs = safe_rows_start('*', 'txp_discuss', "discussid IN (".implode(',', $ids).")");

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
            } elseif ($method == 'ban') {
                // Ban the IP and hide all messages by that IP.
                if (!safe_field('ip', 'txp_discuss_ipban', "ip='".doSlash($ip)."'")) {
                    safe_insert("txp_discuss_ipban",
                        "ip = '".doSlash($ip)."',
                        name_used = '".doSlash($name)."',
                        banned_on_message = $id,
                        date_banned = now()
                    ");
                    safe_update('txp_discuss',
                        "visible = ".SPAM,
                        "ip='".doSlash($ip)."'"
                    );
                }
                $done[] = $id;
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

        $done = join(', ', $done);

        if ($done) {
            // Might as well clean up all comment counts while we're here.
            clean_comment_counts($parentids);

            $messages = array(
                'delete'      => gTxt('comments_deleted', array('{list}' => $done)),
                'ban'         => gTxt('ips_banned', array('{list}' => $done)),
                'spam'        => gTxt('comments_marked_spam', array('{list}' => $done)),
                'unmoderated' => gTxt('comments_marked_unmoderated', array('{list}' => $done)),
                'visible'     => gTxt('comments_marked_visible', array('{list}' => $done))
            );

            update_lastmod();

            return discuss_list($messages[$method]);
        }
    }

    return discuss_list();
}

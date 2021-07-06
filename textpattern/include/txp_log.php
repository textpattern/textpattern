<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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
 * Visitor logs panel.
 *
 * @package Admin\Log
 */

use Textpattern\Search\Filter;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'log') {
    if (get_pref('logging') === 'none' || !intval(get_pref('expire_logs_after'))) {
        require_privs();
    }

    require_privs('log');

    $available_steps = array(
        'log_list'          => false,
        'log_change_pageby' => true,
        'log_multi_edit'    => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        log_list();
    }
}

/**
 * The main panel listing all log hits.
 *
 * @param string|array $message The activity message
 */

function log_list($message = '')
{
    global $event, $expire_logs_after;

    pagetop(gTxt('tab_logs'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('log_sort_column', 'time');
    } else {
        if (!in_array($sort, array('page', 'refer', 'method', 'status'))) {
            $sort = 'time';
        }

        set_pref('log_sort_column', $sort, 'log', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('log_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('log_sort_dir', $dir, 'log', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    $expire_logs_after = assert_int($expire_logs_after);

    safe_delete('txp_log', "time < DATE_SUB(NOW(), INTERVAL $expire_logs_after DAY)");

    switch ($sort) {
        case 'page':
            $sort_sql = "page $dir";
            break;
        case 'refer':
            $sort_sql = "refer $dir";
            break;
        case 'method':
            $sort_sql = "method $dir";
            break;
        case 'status':
            $sort_sql = "status $dir";
            break;
        default:
            $sort = 'time';
            $sort_sql = "time $dir";
            break;
    }

    $sort_sql .= $sort == 'time' ? '' : ", time DESC";
    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter(
        $event,
        array(
            'time' => array(
                'column' => 'txp_log.time',
                'label'  => gTxt('time'),
            ),
            'page' => array(
                'column' => 'txp_log.page',
                'label'  => gTxt('page'),
            ),
            'refer' => array(
                'column' => 'txp_log.refer',
                'label'  => gTxt('referrer'),
            ),
            'method' => array(
                'column' => 'txp_log.method',
                'label'  => gTxt('method'),
            ),
            'status' => array(
                'column' => 'txp_log.status',
                'label'  => gTxt('status'),
                'type'   => 'integer',
            ),
        )
    );

    list($criteria, $crit, $search_method) = $search->getFilter(array('status' => array('can_list' => true)));

    $search_render_options = array('placeholder' => 'search_logs');

    $total = safe_count('txp_log', "$criteria");

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $paginator->getLimit();
    list($page, $offset, $numPages) = pager($total, $limit, $page);

    $searchBlock =
        n.tag(
            $search->renderForm('log_list', $search_render_options),
            'div',
            array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $contentBlock ='';

    if ($total < 1) {
        $contentBlock .=
            graf(
                span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                gTxt($crit === '' ? 'no_refers_recorded' : 'no_results_found'),
                array('class' => 'alert-block information')
            );
    } else {
        $rs = safe_rows_start(
            "*, UNIX_TIMESTAMP(time) AS uTime",
            'txp_log',
            "$criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        if ($rs) {
            $contentBlock .= n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'log_form',
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
                        '',
                        ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                    ).
                    column_head(
                        'time',
                        'time',
                        'log',
                        true,
                        $switch_dir,
                        $crit,
                        $search_method,
                        (('time' == $sort) ? "$dir " : '').'txp-list-col-time'
                    ).
                    column_head(
                        'page',
                        'page',
                        'log',
                        true,
                        $switch_dir,
                        $crit,
                        $search_method,
                        (('page' == $sort) ? "$dir " : '').'txp-list-col-page'
                    ).
                    column_head(
                        'referrer',
                        'refer',
                        'log',
                        true,
                        $switch_dir,
                        $crit,
                        $search_method,
                        (('refer' == $sort) ? "$dir " : '').'txp-list-col-refer'
                    ).
                    column_head(
                        'method',
                        'method',
                        'log',
                        true,
                        $switch_dir,
                        $crit,
                        $search_method,
                        (('method' == $sort) ? "$dir " : '').'txp-list-col-method'
                    ).
                    column_head(
                        'status',
                        'status',
                        'log',
                        true,
                        $switch_dir,
                        $crit,
                        $search_method,
                        (('status' == $sort) ? "$dir " : '').'txp-list-col-status'
                    )
                ).
                n.tag_end('thead').
                n.tag_start('tbody');

            while ($a = nextRow($rs)) {
                extract($a, EXTR_PREFIX_ALL, 'log');

                if ($log_refer) {
                    $log_refer = href(txpspecialchars(soft_wrap(preg_replace('#^http://#', '', $log_refer), 30)).sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), txpspecialchars($log_refer), array(
                        'rel'    => 'external noopener',
                        'target' => '_blank',
                    ));
                }

                if ($log_page) {
                    $log_anchor = preg_replace('/\/$/', '', $log_page);
                    $log_anchor = soft_wrap(substr($log_anchor, 1), 30);
                    $log_page = href('/'.txpspecialchars($log_anchor).sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), rtrim(hu, '/').txpspecialchars($log_page), array(
                        'rel'    => 'external noopener',
                        'target' => '_blank',
                    ));

                    if ($log_method == 'POST') {
                        $log_page = strong($log_page);
                    }
                }

                $contentBlock .= tr(
                    td(
                        fInput('checkbox', 'selected[]', $log_id),
                        '',
                        'txp-list-col-multi-edit'
                    ).
                    hCell(
                        gTime($log_uTime),
                        '',
                        ' class="txp-list-col-time" scope="row"'
                    ).
                    td(
                        $log_page,
                        '',
                        'txp-list-col-page'
                    ).
                    td(
                        $log_refer,
                        '',
                        'txp-list-col-refer'
                    ).
                    td(
                        txpspecialchars($log_method),
                        '',
                        'txp-list-col-method'
                    ).
                    td(
                        $log_status,
                        '',
                        'txp-list-col-status'
                    )
                );
            }

            $contentBlock .=
                n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div'). // End of .txp-listtables.
                log_multiedit_form($page, $sort, $dir, $crit, $search_method).
                tInput().
                n.tag_end('form');
        }
    }

    $createBlock = '';
    $pageBlock = $paginator->render().
        nav_form('log', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table($event);
    echo $table->render(compact('total', 'crit') + array('heading' => 'tab_logs'), $searchBlock, $createBlock, $contentBlock, $pageBlock);
}

/**
 * Saves a new pageby value to the server.
 */

function log_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    log_list();
}

/**
 * Renders a multi-edit widget.
 *
 * @param  int    $page          The page number
 * @param  string $sort          The current sorting value
 * @param  string $dir           The current sorting direction
 * @param  string $crit          The current search criteria
 * @param  string $search_method The current search method
 * @return string HTML
 */

function log_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    $methods = array('delete' => gTxt('delete'));

    return multi_edit($methods, 'log', 'log_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

/**
 * Processes multi-edit actions.
 */

function log_multi_edit()
{
    $deleted = event_multi_edit('txp_log', 'id');

    if ($deleted) {
        $message = gTxt('logs_deleted', array('{list}' => $deleted));

        return log_list($message);
    }

    return log_list();
}

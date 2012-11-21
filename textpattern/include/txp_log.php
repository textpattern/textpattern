<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of
	the Textpattern license agreement
*/

/**
 * Visitor logs panel.
 *
 * @package Admin\Log
 */

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	if ($event == 'log')
	{
		require_privs('log');

		$available_steps = array(
			'log_list'          => false,
			'log_change_pageby' => true,
			'log_multi_edit'    => true,
		);

		if ($step && bouncer($step, $available_steps))
		{
			$step();
		}
		else
		{
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
		global $event, $log_list_pageby, $expire_logs_after;

		pagetop(gTxt('tab_logs'), $message);

		extract(gpsa(array(
			'page',
			'sort',
			'dir',
			'crit',
			'search_method'
		)));

		if ($sort === '')
		{
			$sort = get_pref('log_sort_column', 'time');
		}

		if ($dir === '')
		{
			$dir = get_pref('log_sort_dir', 'desc');
		}

		$dir = ($dir == 'asc') ? 'asc' : 'desc';

		$expire_logs_after = assert_int($expire_logs_after);

		safe_delete('txp_log', "time < date_sub(now(), interval $expire_logs_after day)");

		switch ($sort)
		{
			case 'ip' :
				$sort_sql = 'ip '.$dir;
				break;
			case 'host' :
				$sort_sql = 'host '.$dir;
				break;
			case 'page' :
				$sort_sql = 'page '.$dir;
				break;
			case 'refer' :
				$sort_sql = 'refer '.$dir;
				break;
			case 'method' :
				$sort_sql = 'method '.$dir;
				break;
			case 'status' :
				$sort_sql = 'status '.$dir;
				break;
			default :
				$sort = 'time';
				$sort_sql = 'time '.$dir;
				break;
		}

		set_pref('log_sort_column', $sort, 'log', 2, '', 0, PREF_PRIVATE);
		set_pref('log_sort_dir', $dir, 'log', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit != '')
		{
			$verbatim = preg_match('/^"(.*)"$/', $crit, $m);
			$crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
			$critsql = $verbatim ?
				array(
					'ip'     => "ip = '$crit_escaped'",
					'host'   => "host = '$crit_escaped'",
					'page'   => "page = '$crit_escaped'",
					'refer'  => "refer = '$crit_escaped'",
					'method' => "method = '$crit_escaped'",
					'status' => "status = '$crit_escaped'",
				) : array(
					'ip'     => "ip like '%$crit_escaped%'",
					'host'   => "host like '%$crit_escaped%'",
					'page'   => "page like '%$crit_escaped%'",
					'refer'  => "refer like '%$crit_escaped%'",
					'method' => "method like '%$crit_escaped%'",
					'status' => "status like '%$crit_escaped%'",
				);

			if (array_key_exists($search_method, $critsql))
			{
				$criteria = $critsql[$search_method];
				$limit = 500;
			}
			else
			{
				$search_method = '';
				$crit = '';
			}
		}
		else
		{
			$search_method = '';
			$crit = '';
		}

		$criteria .= callback_event('admin_criteria', 'log_list', 0, $criteria);

		$total = safe_count('txp_log', "$criteria");

		echo n.'<h1 class="txp-heading">'.gTxt('tab_logs').'</h1>';
		echo n.'<div id="'.$event.'_control" class="txp-control-panel">';

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo log_search_form($crit, $search_method).
					graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
			}
			else
			{
				echo graf(gTxt('no_refers_recorded'), ' class="indicator"').'</div>';
			}

			return;
		}

		$limit = max($log_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo log_search_form($crit, $search_method).'</div>';

		$rs = safe_rows_start(
			'*, unix_timestamp(time) as uTime',
			'txp_log',
			"$criteria order by $sort_sql limit $offset, $limit"
		);

		if ($rs)
		{
			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo n.'<form action="index.php" id="log_form" class="multi_edit_form" method="post" name="longform">'.

				n.'<div class="txp-listtables">'.
				startTable('', '', 'txp-list').
				n.'<thead>'.
				tr(
					hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' scope="col" title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
					column_head('time', 'time', 'log', true, $switch_dir, $crit, $search_method, (('time' == $sort) ? "$dir " : '').'date time').
					column_head('IP', 'ip', 'log', true, $switch_dir, $crit, $search_method, (('ip' == $sort) ? "$dir " : '').'log_detail ip').
					column_head('host', 'host', 'log', true, $switch_dir, $crit, $search_method, (('host' == $sort) ? "$dir " : '').'host').
					column_head('page', 'page', 'log', true, $switch_dir, $crit, $search_method, (('page' == $sort) ? "$dir " : '').'page').
					column_head('referrer', 'refer', 'log', true, $switch_dir, $crit, $search_method, (('refer' == $sort) ? "$dir " : '').'refer').
					column_head('method', 'method', 'log', true, $switch_dir, $crit, $search_method, (('method' == $sort) ? "$dir " : '').'log_detail method').
					column_head('status', 'status', 'log', true, $switch_dir, $crit, $search_method, (('status' == $sort) ? "$dir " : '').'log_detail status')
			).
			n.'</thead>';

			echo n.'<tbody>';

			while ($a = nextRow($rs))
			{
				extract($a, EXTR_PREFIX_ALL, 'log');

				if ($log_refer)
				{
					$log_refer = '<a href="'.txpspecialchars($log_refer).'" target="_blank">'.txpspecialchars(soft_wrap($log_refer, 30)).'</a>';
				}

				if ($log_page)
				{
					$log_anchor = preg_replace('/\/$/', '', $log_page);
					$log_anchor = soft_wrap(substr($log_anchor,1), 30);

					$log_page = '<a href="'.txpspecialchars($log_page).'" target="_blank">'.txpspecialchars($log_anchor).'</a>';

					if ($log_method == 'POST')
					{
						$log_page = '<strong>'.$log_page.'</strong>';
					}
				}

				echo tr(
					td(
						fInput('checkbox', 'selected[]', $log_id)
					, '', 'multi-edit').

					hCell(
						gTime($log_uTime)
					, '', ' scope="row" class="date time"').

					td(txpspecialchars($log_ip), '', 'log_detail ip').

					td(txpspecialchars($log_host), '', 'host').

					td($log_page, '', 'page').
					td($log_refer, '', 'refer').
					td(txpspecialchars($log_method), '', 'log_detail method').
					td($log_status, '', 'log_detail status')
				);
			}

			echo n.'</tbody>'.
				endTable().
				n.'</div>'.
				log_multiedit_form($page, $sort, $dir, $crit, $search_method).
				tInput().
				n.'</form>'.
				graf(
					toggle_box('log_detail'),
					' class="detail-toggle"'
				).
				n.'<div id="'.$event.'_navigation" class="txp-navigation">'.
				nav_form('log', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
				pageby_form('log', $log_list_pageby).
				n.'</div>'.
				n.'</div>';
		}
	}

/**
 * Renders a search form for logs.
 *
 * @param  string $crit   The current search criteria
 * @param  string $method The selected search method
 * @retrun string HTML
 */
 
	function log_search_form($crit, $method)
	{
		$methods = array(
			'ip'     => gTxt('IP'),
			'host'   => gTxt('host'),
			'page'   => gTxt('page'),
			'refer'  => gTxt('referrer'),
			'method' => gTxt('method'),
			'status' => gTxt('status')
		);

		return search_form('log', 'log_list', $crit, $methods, $method, 'page');
	}

/**
 * Saves a new pageby value to the server.
 */

	function log_change_pageby()
	{
		event_change_pageby('log');
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
		$methods = array(
			'delete' => gTxt('delete')
		);

		return multi_edit($methods, 'log', 'log_multi_edit', $page, $sort, $dir, $crit, $search_method);
	}

/**
 * Processes multi-edit actions.
 */

	function log_multi_edit()
	{
		$deleted = event_multi_edit('txp_log', 'id');

		if ($deleted)
		{
			$message = gTxt('logs_deleted', array('{list}' => $deleted));

			return log_list($message);
		}

		return log_list();
	}

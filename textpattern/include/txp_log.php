<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of
	the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/
	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	if ($event == 'log')
	{
		require_privs('log');

		$available_steps = array(
			'log_change_pageby',
			'log_multi_edit'
		);

		if (!$step or !in_array($step, $available_steps))
		{
			log_list();
		}

		else
		{
			$step();
		}
	}


//-------------------------------------------------------------

	function log_list($message = '')
	{
		pagetop(gTxt('visitor_logs'), $message);

		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		$expire_logs_after = assert_int($expire_logs_after);

		safe_delete('txp_log', "time < date_sub(now(), interval $expire_logs_after day)");

		$dir = ($dir == 'asc') ? 'asc' : 'desc';

		switch ($sort)
		{
			case 'ip':
				$sort_sql = 'ip '.$dir;
			break;

			case 'host':
				$sort_sql = 'host '.$dir;
			break;

			case 'page':
				$sort_sql = 'page '.$dir;
			break;

			case 'refer':
				$sort_sql = 'refer '.$dir;
			break;

			case 'method':
				$sort_sql = 'method '.$dir;
			break;

			case 'status':
				$sort_sql = 'status '.$dir;
			break;

			default:
				$sort = 'time';
				$sort_sql = 'time '.$dir;
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'ip'     => "ip like '%$crit_escaped%'",
				'host'   => "host like '%$crit_escaped%'",
				'page'   => "page like '%$crit_escaped%'",
				'refer'  => "refer like '%$crit_escaped%'",
				'method' => "method like '%$crit_escaped%'",
				'status' => "status like '%$crit_escaped%'"
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

		$total = safe_count('txp_log', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.log_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' style="text-align: center;"');
			}

			else
			{
				echo graf(gTxt('no_refers_recorded'), ' style="text-align: center;"');
			}

			return;
		}

		$limit = max(@$log_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo n.log_search_form($crit, $search_method);

		$rs = safe_rows_start('*, unix_timestamp(time) as uTime', 'txp_log',
			"$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			echo n.n.'<form action="index.php" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

				startTable('list','','','','90%').

				n.tr(
					n.column_head('time', 'time', 'log', true, $switch_dir, $crit, $search_method, ('time' == $sort) ? $dir : '').
					column_head('IP', 'ip', 'log', true, $switch_dir, $crit, $search_method, (('ip' == $sort) ? "$dir " : '').'log_detail').
					column_head('host', 'host', 'log', true, $switch_dir, $crit, $search_method, ('host' == $sort) ? $dir : '').
					column_head('page', 'page', 'log', true, $switch_dir, $crit, $search_method, ('page' == $sort) ? $dir : '').
					column_head('referrer', 'refer', 'log', true, $switch_dir, $crit, $search_method, ('refer' == $sort) ? $dir : '').
					column_head('method', 'method', 'log', true, $switch_dir, $crit, $search_method, (('method' == $sort) ? "$dir " : '').'log_detail').
					column_head('status', 'status', 'log', true, $switch_dir, $crit, $search_method, (('status' == $sort) ? "$dir " : '').'log_detail').
					hCell()
				);

			while ($a = nextRow($rs))
			{
				extract($a, EXTR_PREFIX_ALL, 'log');

				if ($log_refer)
				{
					$log_refer = 'http://'.$log_refer;

					$log_refer = '<a href="'.htmlspecialchars($log_refer).'" target="_blank">'.htmlspecialchars(soft_wrap($log_refer, 30)).'</a>';
				}

				if ($log_page)
				{
					$log_anchor = preg_replace('/\/$/','',$log_page);
					$log_anchor = soft_wrap(substr($log_anchor,1), 30);

					$log_page = '<a href="'.htmlspecialchars($log_page).'" target="_blank">'.htmlspecialchars($log_anchor).'</a>';

					if ($log_method == 'POST')
					{
						$log_page = '<strong>'.$log_page.'</strong>';
					}
				}

				echo tr(

					n.td(
						gTime($log_uTime)
					, 85).

					td($log_ip, 20, 'log_detail').

					td(soft_wrap($log_host, 30)).

					td($log_page).
					td($log_refer).
					td(htmlspecialchars($log_method), 60, 'log_detail').
					td($log_status, 60, 'log_detail').

					td(
						fInput('checkbox', 'selected[]', $log_id)
					)

				);
			}

			echo n.n.tr(
				tda(
					toggle_box('log_detail'),
					' colspan="2" style="text-align: left; border: none;"'
				).
				tda(
					select_buttons().
					log_multiedit_form($page, $sort, $dir, $crit, $search_method)
				, ' colspan="6" style="text-align: right; border: none;"')
			).

			n.endTable().
			'</form>'.

			n.nav_form('log', $page, $numPages, $sort, $dir, $crit, $search_method).

			n.pageby_form('log', $log_list_pageby);
		}
	}

//-------------------------------------------------------------

	function log_search_form($crit, $method)
	{
		$methods =	array(
			'ip'     => gTxt('IP'),
			'host'	 => gTxt('host'),
			'page'   => gTxt('page'),
			'refer'	 => gTxt('referrer'),
			'method' => gTxt('method'),
			'status' => gTxt('status')
		);

		return search_form('log', 'log_list', $crit, $methods, $method, 'page');
	}

//-------------------------------------------------------------

	function log_change_pageby()
	{
		event_change_pageby('log');
		log_list();
	}

// -------------------------------------------------------------

	function log_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'delete' => gTxt('delete')
		);

		return event_multiedit_form('log', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

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

?>

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

		if (!$step or !in_array($step, array('log_change_pageby')))
		{
			log_list();
		}

		else
		{
			$step();
		}
	}

//-------------------------------------------------------------

	function chunk($str, $len, $break = '&#8230;<br />') 
	{
		return join($break, preg_split('/(.{1,'.$len.'})/', $str, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY));
	}

//-------------------------------------------------------------

	function log_list() 
	{
		pagetop(gTxt('visitor_logs'));

		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'method')));

		safe_delete('txp_log', "time < date_sub(now(), interval ".$expire_logs_after." day)");

		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		switch ($sort)
		{
			case 'time':
				$sort_sql = '`time` '.$dir;
			break;

			case 'ip':
				$sort_sql = '`ip` '.$dir;
			break;

			case 'page':
				$sort_sql = '`page` '.$dir;
			break;

			case 'refer':
				$sort_sql = '`refer` '.$dir;
			break;

			case 'method':
				$sort_sql = '`method` '.$dir;
			break;

			case 'status':
				$sort_sql = '`status` '.$dir;
			break;

			default:
				$dir = 'desc';
				$sort_sql = '`time` '.$dir;
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($crit or $method)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'ip'     => "`ip` like '%$crit_escaped%'",
				'page'   => "`page` like '%$crit_escaped%'",
				'refer'  => "`refer` like '%$crit_escaped%'",
				'method' => "`method` like '%$crit_escaped%'",
				'status' => "`status` like '%$crit_escaped%'"
			);

			if (array_key_exists($method, $critsql))
			{
				$criteria = $critsql[$method];
				$limit = 500;
			}

			else
			{
				$method = '';
			}
		}

		$total = safe_count('txp_log', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.log_search_form($crit, $method).
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

		echo n.log_search_form($crit, $method);

		$rs = safe_rows_start('*, unix_timestamp(`time`) as uTime', 'txp_log', 
			"$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			echo startTable('list').

			column_head('time', 'time', 'log', true, $switch_dir, $crit, $method).
			column_head('IP', 'ip', 'log', true, $switch_dir, $crit, $method).
			column_head('page', 'page', 'log', true, $switch_dir, $crit, $method).
			column_head('referrer', 'refer', 'log', true, $switch_dir, $crit, $method).
			column_head('method', 'method', 'log', true, $switch_dir, $crit, $method).
			column_head('status', 'status', 'log', true, $switch_dir, $crit, $method);

			while ($a = nextRow($rs))
			{
				extract($a, EXTR_PREFIX_ALL, 'log');

				if ($log_refer)
				{
					$log_refer = htmlspecialchars('http://'.$log_refer);

					$log_refer = '<a href="'.$log_refer.'" target="_blank">'.chunk($log_refer, 50).'</a>';
				}

				if ($log_page)
				{
					$log_page = htmlspecialchars('http://'.$log_host.$log_page);

					$log_page = '<a href="'.$log_page.'" target="_blank">'.chunk($log_page, 50).'</a>';

					if ($log_method == 'POST')
					{
						$log_page = '<strong>'.$log_page.'</strong>';
					}
				}

				echo tr(

					n.td(
						safe_strftime('%d %b %Y %I:%M:%S %p', $log_uTime)
					, 85).

					td($log_ip, 75).
					td($log_page, 200).
					td($log_refer, 200).
					td($log_method, 50).
					td($log_status, 50)
				);
			}

			echo n.endTable().

			n.log_nav_form($page, $numPages, $sort, $dir, $crit, $method).

			n.pageby_form('log', $log_list_pageby);
		} 
	}

//-------------------------------------------------------------

	function log_search_form($crit, $method)
	{
		$default_method = 'page';

		$methods =	array(
			'ip'     => gTxt('IP'),
			'page'   => gTxt('page'),
			'refer'	 => gTxt('referrer'),
			'method' => gTxt('method'),
			'status' => gTxt('status')
		);

		$method = ($method) ? $method : $default_method;

		return n.n.form(
			graf(

				gTxt('Search').sp.selectInput('method', $methods, $method).
				fInput('text', 'crit', $crit, 'edit', '', '', '15').
				eInput('log').
				sInput('log_list').
				fInput('submit', 'search', gTxt('go'), 'smallerbox')

			,' style="text-align: center;"')
		);
	}

//-------------------------------------------------------------

	function log_nav_form($page, $numPages, $sort, $dir, $crit, $method)
	{
		$nav = array();

		if ($page > 1)
		{
			$nav[] = PrevNextLink('log', $page - 1, gTxt('prev'), 'prev', $sort, $dir, $crit, $method).sp;
		}

		$nav[] = small($page.'/'.$numPages);

		if ($page != $numPages)
		{
			$nav[] = sp.PrevNextLink('log', $page + 1, gTxt('next'), 'next', $sort, $dir, $crit, $method);
		}

		return graf(join('', $nav),' style="text-align: center;"');
	}

//-------------------------------------------------------------

	function log_change_pageby() 
	{
		event_change_pageby('log');
		log_list();
	}

?>
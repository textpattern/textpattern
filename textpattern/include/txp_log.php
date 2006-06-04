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
	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'log') {
		require_privs('log');

		log_list();
	}
				
//-------------------------------------------------------------
	function chunk($str, $len, $break='&#133;<br />') 
	{
		return join($break, preg_split('/(.{1,'.$len.'})/', $str, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY));
	}

//-------------------------------------------------------------
	function log_list() 
	{
		pagetop(gTxt('visitor_logs'));
		extract(get_prefs());

		safe_delete("txp_log", "time < date_sub(now(),interval ".
						$expire_logs_after." day)");

		$page = gps('page');

		$total = getCount('txp_log',"1");  
		$limit = 50;
		$numPages = ceil($total/$limit);  
		$page = (!$page) ? 1 : $page;
		$offset = ($page - 1) * $limit;

		$nav[] = ($page > 1)
		?	PrevNextLink("log",$page-1,gTxt('prev'),'prev') : '';

		$nav[] = sp.small($page. '/'.$numPages).sp;

		$nav[] = ($page != $numPages) 
		?	PrevNextLink("log",$page+1,gTxt('next'),'next') : '';

		$rs = safe_rows_start(
			"*, unix_timestamp(time) as stamp", 
			"txp_log", 
			"1 order by time desc limit $offset, $limit"
		);

		if ($rs) {
			echo startTable('list'),
			assHead('time','host','page','referrer');
			$stamp ='';

			while ($a = nextRow($rs)) {
				extract($a);
				if ($refer) {
					$referprint = preg_replace("/^www\./","",
						chunk(htmlspecialchars($refer),50));
					$referprint = '<a href="http://'.htmlspecialchars($refer).'">'.$referprint.'</a>';
				} else {
					$referprint = '&#160;';
				}
				$pageprint = preg_replace('/\/$/','', htmlspecialchars(substr($page,1)));
				$pageprint = ($pageprint=='') 
				?	'' 
				:	'<a href="'.htmlspecialchars($page).'" target="_blank">'.chunk($pageprint,60).'</a>';
				if ($method == 'POST')
					$pageprint = '<b>'.$pageprint.'</b>';
				$fstamp = safe_strftime('%b %e %I:%M %p', $stamp);

				$hostprint = chunk($host, 50);
				
				echo tr(
					td($fstamp).
					td($hostprint).
					td($pageprint).
					td($referprint));
				unset($refer,$referprint,$page,$pageprint);
			}

			echo '<tr><td colspan="4" align="right" style="padding:10px">',
					join('',$nav),
				 "</td></tr>",
				 endTable();
		} else echo graf(gTxt('no_refers_recorded'), ' align="center"');
	}
?>

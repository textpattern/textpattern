<?php

/*
	This is Textpattern

	Copyright 2004 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of
	the Textpattern license agreement 
*/

	log_list();
				
//-------------------------------------------------------------
	function log_list() 
	{
		pagetop(gTxt('visitor_logs'));
		extract(get_prefs());
		global $txpac;

		safe_delete("txp_log", "`time` < date_sub(now(),interval ".
						$txpac['expire_logs_after']." day)");

		safe_optimize("txp_log");
		safe_repair("txp_log");

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

		$rs = safe_rows(
			"*, unix_timestamp(time) as stamp", 
			"txp_log", 
			"1 order by time desc limit $offset,$limit"
		);

		if ($rs) {
			echo startTable('list'),
			assHead('time','host','page','referrer');
			$stamp ='';

			foreach ($rs as $a) {
				extract($a);
				if ($refer) {
					$referprint = str_replace("www.","",
						substr(htmlspecialchars($refer),0,50));
					$referprint = '<a href="http://'.$refer.'">'.$referprint.'</a>';
				} else {
					$referprint = '&#160;';
				}
				$pageprint = preg_replace('/\/$/','', htmlspecialchars(substr($page,1)));
				$pageprint = ($pageprint=='') 
				?	'' 
				:	'<a href="'.$page.'" target="_blank">'.$pageprint.'</a>';
				$fstamp = date("n/j g:i a",($stamp + $timeoffset));
				
				echo tr(
					td($fstamp).
					td($host).
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

<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'discuss') {
		require_privs('discuss');

		if(!$step or !in_array($step, array('discuss_delete','discuss_save','discuss_list','discuss_edit','ipban_add','discuss_multi_edit','ipban_list','ipban_unban','discuss_change_pageby'))){
			discuss_list();
		} else $step();
	}

//-------------------------------------------------------------
	// Removed (broken) function discuss_delete() since it was not used.
//-------------------------------------------------------------
	function discuss_save()
	{
		extract(doSlash(gpsa(array('email','name','web','message','discussid','ip','visible','parentid'))));
		safe_update("txp_discuss",
			"email   = '$email',
			 name    = '$name',
			 web     = '$web',
			 message = '$message',
			 visible = '$visible'",
			"discussid = $discussid");
		update_comments_count($parentid);
		discuss_list(messenger('message',$discussid,'updated'));
	}

//-------------------------------------------------------------
	function short_preview($message)
	{
		$message = strip_tags($message);
		$offset = min(175,strlen($message));
		if ( strpos($message,' ',$offset) !== false)
		{
			$maxpos = strpos($message,' ',$offset);
			$message = substr($message,0,$maxpos).'...';
		}
		return $message;
	}
//-------------------------------------------------------------
	function discuss_list($message='') 
	{
		pagetop(gTxt('list_discussions'),$message);

		extract(doSlash(gpsa(array('page','crit'))));

		extract(get_prefs());

		$total = safe_count('txp_discuss',"1=1");  
		$limit = max(@$comment_list_pageby, 25);
		$numPages = ceil($total/$limit);  
		$page = (!$page) ? 1 : $page;
		$offset = ($page - 1) * $limit;

		$nav[] = ($page > 1)
		?	PrevNextLink("discuss",$page-1,gTxt('prev'),'prev') : '';

		$nav[] = sp.small($page. '/'.$numPages).sp;

		$nav[] = ($page != $numPages) 
		?	PrevNextLink("discuss",$page+1,gTxt('next'),'next') : '';

		$criteria = ($crit) ? "message like '%$crit%'" : '1=1'; 

		$rs = safe_rows_start(
			"*, unix_timestamp(posted) as uPosted", 
			"txp_discuss",
			"$criteria order by posted desc limit $offset, $limit"
		);

		echo pageby_form('discuss',$comment_list_pageby);

		if($rs) {	

			echo '<form action="index.php" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">',

			startTable('list'),
			assHead('date','name','message','parent','');
	
			while ($a = nextRow($rs)) {
				extract($a);
				$dmessage = ($visible == SPAM) ? short_preview($message) : $message;
				$date = "".safe_strftime('%b %e %I:%M %p',$uPosted)."";
				$editlink = eLink('discuss','discuss_edit','discussid',$discussid,$date);
				$cbox = fInput('checkbox','selected[]',$discussid);
	
				$tq = fetch('Title','textpattern','ID',$parentid);
				$parent = (!$tq) ? gTxt('article_deleted') : $tq;

				echo assRow(array(
					$editlink   => 100,
					$name       => 100,
					$dmessage   => 250,
					$parent     => 100,
					$cbox       => 20
				), ' class="'.(($visible == VISIBLE) ? 'visible' : (($visible == SPAM) ? 'spam' : 'moderate')).'"');
			}
			
			echo tr(tda(select_buttons().discuss_multiedit_form(),' colspan="5" style="text-align:right;border:0px"'));
			
			echo endTable().'</form>';

			echo startTable('edit'),
				tr(
					td(
						form(
							fInput('text','crit','','edit').
							fInput('submit','search',gTxt('search'),'smallbox').
							eInput("discuss").
							sInput("list")
						)
					).
					td(graf(join('',$nav)))).
				tr(
					tda(
						graf('<a href="index.php?event=discuss'.a.'step=ipban_list">'.
							gTxt('list_banned_ips').'</a>'),' colspan="2" align="center" valign="middle"'
					)
				)
				,endTable();

		} else echo graf(gTxt('no_comments_recorded'), ' align="center"');
	}

//-------------------------------------------------------------
	function discuss_edit()
	{
		$discussid=gps('discussid');
		extract(safe_row("*", "txp_discuss", "discussid='$discussid'"));
		$ta = '<textarea name="message" cols="60" rows="15">'.
			preg_replace(array('/</', '/>/'), array('&lt;', '&gt;'), $message).'</textarea>';

		if (fetch('ip','txp_discuss_ipban','ip',$ip)) {
			$banstep = 'ipban_unban'; $bantext = gTxt('unban');
		} else {
			$banstep = 'ipban_add'; $bantext = gTxt('ban');
		}
		
		$banlink = '[<a href="?event=discuss'.a.'step='.$banstep.a.'ip='.$ip.a.
			'name='.urlencode($name).a.'discussid='.$discussid.'">'.$bantext.'</a>]';
	
		pagetop(gTxt('edit_comment'));
		echo 
			form(
			startTable('edit').
				stackRows(
					fLabelCell('name') . fInputCell('name',$name),
					fLabelCell('email') . fInputCell('email',$email),
					fLabelCell('website') . fInputCell('web',$web),
					td() . td($ta),
					fLabelCell('visible') . td(selectInput('visible', array(VISIBLE => gTxt('visible'), SPAM => gTxt('spam'),MODERATE => gTxt('unmoderated')),$visible,false)),
					fLabelCell('IP') . td($ip.sp.$banlink),
					td() . td(fInput('submit','step',gTxt('save'),'publish')),
				hInput("discussid", $discussid).hInput('ip',$ip).hInput('parentid',$parentid).
				eInput('discuss').sInput('discuss_save')
			).
			endTable()
		);
	}

// -------------------------------------------------------------
	function ipban_add() 
	{
		extract(doSlash(gpsa(array('ip','name','discussid'))));
		
		if (!$ip) exit(ipban_list(gTxt("cant_ban_blank_ip")));
		
		$chk = fetch('ip','txp_discuss_ipban','ip',$ip);
		
		if (!$chk) {
			$rs = safe_insert("txp_discuss_ipban",
				"ip = '$ip',
				 name_used = '$name',
				 banned_on_message = '$discussid',
				 date_banned = now()
			");
			// hide all messages from that IP also
			if ($rs)
				safe_update('txp_discuss',
					"visible=".SPAM,
					"ip='".doSlash($ip)."'"
				);
			if ($rs) ipban_list(messenger('ip',$ip,'banned'));
		} else ipban_list(messenger('ip',$ip,'already_banned'));
		
	}

// -------------------------------------------------------------
	function ipban_unban() 
	{
		$ip = gps('ip');
		
		$rs = safe_delete("txp_discuss_ipban","ip='$ip'");
		
		if($rs) ipban_list(messenger('ip',$ip,'unbanned'));
	}

// -------------------------------------------------------------
	function ipban_list($message='')
	{
		pageTop(gTxt('list_banned_ips'),$message);
		$rs = safe_rows_start(
				"*", 
				"txp_discuss_ipban", 
				"1=1 order by date_banned desc"
			);
		if ($rs) {
			echo startTable('list'),
			tr(
				hCell('Date banned') .
				hCell('ip')          .
				hCell('Name used')   .
				hCell('Banned for')  .
				td()
			);

			while ($a = nextRow($rs)) {
				extract($a);
				
				$unbanlink = '<a href="?event=discuss'.a.'step=ipban_unban'.a.
					'ip='.$ip.'">unban</a>';
				$datebanned = date("Y-m-d",strtotime($date_banned));
				$messagelink = '<a href="?event=discuss'.a.'step=discuss_edit'.a.'discussid='.$banned_on_message.'">'.$banned_on_message.'</a>';
				echo
				tr(
					td($datebanned)  .
					td($ip)          .
					td($name_used)   .
					td($messagelink) .
					td($unbanlink)
				);
				
			}
			echo endTable();
		} else echo graf(gTxt('no_ips_banned'),' align="center"');
	}

// -------------------------------------------------------------
	function discuss_change_pageby() 
	{
		event_change_pageby('comment');
		discuss_list();
	}

// -------------------------------------------------------------
	function discuss_multiedit_form() 
	{
		$methods = array(
			'ban'=>gTxt('ban'),
			'delete'=>gTxt('delete'),
			'spam'=>gTxt('spam'),
			'unmoderated'=>gTxt('unmoderated'),
			'visible'=>gTxt('visible'),
		);
		return event_multiedit_form('discuss', $methods);
	}

// -------------------------------------------------------------
	function discuss_multi_edit() 
	{
		//FIXME, this method needs some refactoring
		
		$selected = ps('selected');
		$method = ps('method');
		$done = array();
		if ($selected) {
			// Get all articles for which we have to update the count
			foreach($selected as $id)
				$ids[] = "'".intval($id)."'";
			$parentids = safe_column("DISTINCT parentid","txp_discuss","discussid IN (".implode(',',$ids).")");

			$rs = safe_rows_start('*', 'txp_discuss', "discussid IN (".implode(',',$ids).")");
			while ($row = nextRow($rs)) {
				extract($row);
				$id = intval($discussid);
				$parentids[] = $parentid;

				if ($method == 'delete') {
					// Delete and if succesful update commnet count 
					if (safe_delete('txp_discuss', "discussid='$id'"))
						$done[] = $id;
				}
				elseif ($method == 'ban') {
					// Ban the IP and hide all messages by that IP
					if (!safe_field('ip', 'txp_discuss_ipban', "ip='".doSlash($ip)."'")) {
						safe_insert("txp_discuss_ipban",
							"ip = '".doSlash($ip)."',
							name_used = '".doSlash($name)."',
							banned_on_message = '".doSlash($discussid)."',
							date_banned = now()
						");
						safe_update('txp_discuss',
							"visible = ".SPAM,
							"ip='".doSlash($ip)."'"
						);
					}
					$done[] = $id;
				}
				elseif ($method == 'spam') {
						if (safe_update('txp_discuss',
							"visible = ".SPAM,
							"discussid = $id"
						))
							$done[] = $id;
				}
				elseif ($method == 'unmoderated') {
						if (safe_update('txp_discuss',
							"visible = ".MODERATE,
							"discussid = $id"
						))
							$done[] = $id;
				}
				elseif ($method == 'visible') {
						if (safe_update('txp_discuss',
							"visible = ".VISIBLE,
							"discussid = $id"
						))
							$done[] = $id;
				}
				
			}

			$done = join(', ', $done);

			if(!empty($done)) {
				// might as well clean up all comment counts while we're here.
				clean_comment_counts($parentids);
				$messages = array(
					'delete'	=> messenger('comment',$done,'deleted'),
					'ban'		=> messenger('comment',$done,'banned'),
					'spam'		=>  gTxt('comment').' '.strong($done).' '. gTxt('marked_as').' '.gTxt('spam'),
					'unmoderated'=> gTxt('comment').' '.strong($done).' '. gTxt('marked_as').' '.gTxt('unmoderated'),
					'visible'	=>  gTxt('comment').' '.strong($done).' '. gTxt('marked_as').' '.gTxt('visible'),
				);
				return discuss_list($messages[$method]);
			}
		}
		return discuss_list();
	}


?>

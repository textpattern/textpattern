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

		$available_steps = array(
			'discuss_delete' 	=> true,
			'discuss_save' 	=> true,
			'discuss_list' 	=> false,
			'discuss_edit' 	=> false,
			'ipban_add' 	=> true,
			'discuss_multi_edit' 	=> true,
			'ipban_list' 	=> false,
			'ipban_unban' 	=> true,
			'discuss_change_pageby' 	=> true
		);

		if(!$step or !bouncer($step, $available_steps)){
			discuss_list();
		} else $step();
	}

//-------------------------------------------------------------
	function discuss_save()
	{
		extract(doSlash(gpsa(array('email','name','web','message','ip'))));
		extract(array_map('assert_int',gpsa(array('discussid','visible','parentid'))));
		safe_update("txp_discuss",
			"email   = '$email',
			 name    = '$name',
			 web     = '$web',
			 message = '$message',
			 visible = $visible",
			"discussid = $discussid");
		update_comments_count($parentid);
		update_lastmod();

		$message = gTxt('comment_updated', array('{id}' => $discussid));

		discuss_list($message);
	}

//-------------------------------------------------------------

	function short_preview($message)
	{
		$message = strip_tags($message);
		$offset = min(150, strlen($message));

		if (strpos($message, ' ', $offset) !== false)
		{
			$maxpos = strpos($message,' ',$offset);
			$message = substr($message, 0, $maxpos).'&#8230;';
		}

		return $message;
	}

//-------------------------------------------------------------

	function discuss_list($message = '')
	{
		global $event, $comment_list_pageby;

		pagetop(gTxt('list_discussions'), $message);

		echo graf(
			'<a href="index.php?event=discuss'.a.'step=ipban_list">'.gTxt('list_banned_ips').'</a>'
		, ' id="list_banned_ips" style="text-align: center;"');

		extract(gpsa(array('sort', 'dir', 'page', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('discuss_sort_column', 'date');
		if ($dir === '') $dir = get_pref('discuss_sort_dir', 'desc');
		$dir = ($dir == 'asc') ? 'asc' : 'desc';

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'discussid '.$dir;
			break;

			case 'ip':
				$sort_sql = 'ip '.$dir;
			break;

			case 'name':
				$sort_sql = 'name '.$dir;
			break;

			case 'email':
				$sort_sql = 'email '.$dir;
			break;

			case 'website':
				$sort_sql = 'web '.$dir;
			break;

			case 'message':
				$sort_sql = 'message '.$dir;
			break;

			case 'status':
				$sort_sql = 'visible '.$dir;
			break;

			case 'parent':
				$sort_sql = 'parentid '.$dir;
			break;

			default:
				$sort = 'date';
				$sort_sql = 'txp_discuss.posted '.$dir;
			break;
		}

		if ($sort != 'date') $sort_sql .= ', txp_discuss.posted asc';

		set_pref('discuss_sort_column', $sort, 'discuss', 2, '', 0, PREF_PRIVATE);
		set_pref('discuss_sort_dir', $dir, 'discuss', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit != '')
		{
			$crit_escaped = doSlash(str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));

			$critsql = array(
				'id'      => "discussid = '$crit_escaped'",
				'parent'  => "parentid = '$crit_escaped'".(intval($crit_escaped) ? '' : " OR title like '%$crit_escaped%'"),
				'name'    => "name like '%$crit_escaped%'",
				'message' => "message like '%$crit_escaped%'",
				'email'   => "email like '%$crit_escaped%'",
				'website' => "web like '%$crit_escaped%'",
				'ip'      => "ip like '%$crit_escaped%'",
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

		$counts = getRows(
			'SELECT visible, COUNT(*) AS c'.
			' FROM '.safe_pfx_j('txp_discuss').' LEFT JOIN '.safe_pfx_j('textpattern').' ON txp_discuss.parentid = textpattern.ID'.
			' WHERE '. $criteria.' GROUP BY visible'
		);

		$count[SPAM] = $count[MODERATE] = $count[VISIBLE] = 0;

		if ($counts) foreach($counts as $c)
		{
			$count[$c['visible']] = $c['c'];
		}

		// grand total comment count
		$total = $count[SPAM] + $count[MODERATE] + $count[VISIBLE];

		echo '<div id="'.$event.'_control" class="txp-control-panel">';

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.discuss_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
			}

			else
			{
				echo graf(gTxt('no_comments_recorded'), ' class="indicator"').'</div>';
			}

			return;
		}

		// paging through displayed comments
		$total = ((cs('toggle_show_spam')) ? $count[SPAM] : 0) + $count[MODERATE] + $count[VISIBLE];
		$limit = max($comment_list_pageby, 15);
		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo discuss_search_form($crit, $search_method).'</div>';

		$spamq = cs('toggle_show_spam') ? '1=1' : 'visible != '.intval(SPAM);

		$rs = safe_query(
			'SELECT txp_discuss.*, unix_timestamp(txp_discuss.posted) as uPosted, ID as thisid, Section as section, url_title, Title as title, Status, unix_timestamp(textpattern.Posted) as posted'.
			' FROM '.safe_pfx_j('txp_discuss').' LEFT JOIN '.safe_pfx_j('textpattern').' ON txp_discuss.parentid = textpattern.ID'.
			' WHERE '.$spamq.' AND '.$criteria.
			' ORDER BY '.$sort_sql.
			' LIMIT '.$offset.', '.$limit
		);

		if ($rs)
		{
			echo n.'<div id="'.$event.'_container" class="txp-container txp-list">';
			echo n.n.'<form name="longform" id="discuss_form" method="post" action="index.php" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

				n.startTable('list','','list','','90%').
				n.'<thead>'.
				n.n.tr(
					column_head('ID', 'id', 'discuss', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id').
					column_head('date', 'date', 'discuss', true, $switch_dir, $crit, $search_method, (('date' == $sort) ? "$dir " : '').'date posted created').
					column_head('name', 'name', 'discuss', true, $switch_dir, $crit, $search_method, (('name' == $sort) ? "$dir " : '').'name').
					column_head('message', 'message', 'discuss', true, $switch_dir, $crit, $search_method, (('message' == $sort) ? "$dir " : 'message')).
					column_head('email', 'email', 'discuss', true, $switch_dir, $crit, $search_method, (('email' == $sort) ? "$dir " : '').'discuss_detail email').
					column_head('website', 'website', 'discuss', true, $switch_dir, $crit, $search_method, (('website' == $sort) ? "$dir " : '').'discuss_detail website').
					column_head('IP', 'ip', 'discuss', true, $switch_dir, $crit, $search_method, (('ip' == $sort) ? "$dir " : '').'discuss_detail ip').
					column_head('status', 'status', 'discuss', true, $switch_dir, $crit, $search_method, (('status' == $sort) ? "$dir " : '').'discuss_detail status').
					column_head('parent', 'parent', 'discuss', true, $switch_dir, $crit, $search_method, (('parent' == $sort) ? "$dir " : '').'parent').
					hCell('', '', ' class="multi-edit"')
				).
				n.'</thead>';

			include_once txpath.'/publish/taghandlers.php';

			$tfoot = n.'<tfoot>'.tr(
				tda(
					toggle_box('discuss_detail'),
					' class="detail-toggle" colspan="2" style="text-align: left; border: none;"'
				).
				tda(
					select_buttons().
					discuss_multiedit_form($page, $sort, $dir, $crit, $search_method)
				, ' class="multi-edit" colspan="9" style="text-align: right; border: none;"')
			).n.'</tfoot>';

			echo $tfoot;
			echo '<tbody>';

			$ctr = 1;

			while ($a = nextRow($rs))
			{
				extract($a);
				$parentid = assert_int($parentid);

				$edit_url = '?event=discuss'.a.'step=discuss_edit'.a.'discussid='.$discussid.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

				$dmessage = ($visible == SPAM) ? short_preview($message) : $message;

				switch ($visible)
				{
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

				if (empty($thisid))
				{
					$parent = gTxt('article_deleted').' ('.$parentid.')';
					$view = '';
				}

				else
				{
					$parent_title = empty($title) ? '<em>'.gTxt('untitled').'</em>' : escape_title($title);

					$parent = href($parent_title, '?event=article'.a.'step=edit'.a.'ID='.$parentid);

					$view = '';

					if ($visible == VISIBLE and in_array($Status, array(4,5)))
					{
						$view = n.t.'<li class="action-view"><a href="'.permlinkurl($a).'#c'.$discussid.'">'.gTxt('view').'</a></li>';
					}
				}

				echo n.n.tr(

					n.td('<a class="action-edit" href="'.$edit_url.'">'.$discussid.'</a>'.
						n.'<ul class="discuss_detail actions">'.
						n.t.'<li class="action-edit"><a href="'.$edit_url.'">'.gTxt('edit').'</a></li>'.
						$view.
						n.'</ul>'
					, 50, 'id').

					td(gTime($uPosted), '', 'date posted created').
					td(htmlspecialchars(soft_wrap($name, 15)), '', 'name').
					td(short_preview($dmessage), '', 'message').
					td(htmlspecialchars(soft_wrap($email, 15)), '', 'discuss_detail email').
					td(htmlspecialchars(soft_wrap($web, 15)), '', 'discuss_detail website').
					td($ip, '', 'discuss_detail ip').
					td($comment_status, '', 'discuss_detail status').
					td($parent, '', 'parent').
					td(fInput('checkbox', 'selected[]', $discussid), '', 'multi-edit')
				, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').' '.$row_class.'"');

				$ctr++;
			}

			if (empty($message))
				echo tr(tda(gTxt('just_spam_results_found'),' colspan="9" style="text-align: left; border: none;"'));

			echo '</tbody>'.
			n.endTable().
			n.tInput().
			n.'</form>'.

			n.cookie_box('show_spam').

			n.'<div id="'.$event.'_navigation" class="txp-navigation">'.
			nav_form('discuss', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).

			pageby_form('discuss', $comment_list_pageby).
			n.'</div>'.n.'</div>';
		}
	}

//-------------------------------------------------------------

	function discuss_search_form($crit, $method)
	{
		$methods =	array(
			'id'      => gTxt('ID'),
			'parent'  => gTxt('parent'),
			'name'    => gTxt('name'),
			'message' => gTxt('message'),
			'email'   => gTxt('email'),
			'website' => gTxt('website'),
			'ip'      => gTxt('IP')
		);

		return search_form('discuss', 'list', $crit, $methods, $method, 'message');
	}

//-------------------------------------------------------------

	function discuss_edit()
	{
		global $event;

		pagetop(gTxt('edit_comment'));

		extract(gpsa(array('discussid', 'sort', 'dir', 'page', 'crit', 'search_method')));

		$discussid = assert_int($discussid);

		$rs = safe_row('*, unix_timestamp(posted) as uPosted', 'txp_discuss', "discussid = $discussid");

		if ($rs)
		{
			extract($rs);

			$message = htmlspecialchars($message);

			if (fetch('ip', 'txp_discuss_ipban', 'ip', $ip))
			{
				$ban_step = 'ipban_unban';
				$ban_text = gTxt('unban');
			}

			else
			{
				$ban_step = 'ipban_add';
				$ban_text = gTxt('ban');
			}

			$ban_link = '[<a class="action-ban" href="?event=discuss'.a.'step='.$ban_step.a.'ip='.$ip.
				a.'name='.urlencode($name).a.'discussid='.$discussid.a.'_txp_token='.form_token().'">'.$ban_text.'</a>]';

			echo '<div id="'.$event.'_container" class="txp-container txp-edit">'.
				form(
				startTable('edit', '', 'edit-pane').
				stackRows(

					fLabelCell('name').
					fInputCell('name', $name, '', '', '', 'name'),

					fLabelCell('IP').
					td("$ip $ban_link", '', 'ip'),

					fLabelCell('email').
					fInputCell('email', $email, '', '', '', 'email'),

					fLabelCell('website').
					fInputCell('web', $web, '', '', '', 'website'),

					fLabelCell('date').
					td(
						safe_strftime('%d %b %Y %X', $uPosted)
					, '', 'date posted created'),

					tda(gTxt('message')).
					td(
						'<textarea name="message" cols="60" rows="15">'.$message.'</textarea>'
					, '', 'comment message text'),

					fLabelCell('status').
					td(
						selectInput('visible', array(
							VISIBLE	 => gTxt('visible'),
							SPAM		 => gTxt('spam'),
							MODERATE => gTxt('unmoderated')
						), $visible, false)
					, '', 'status'),

					td().td(fInput('submit', 'step', gTxt('save'), 'publish'))

				).

				endTable().
				hInput('sort', $sort).
				hInput('dir', $dir).
				hInput('page', $page).
				hInput('crit', $crit).
				hInput('search_method', $search_method).

				hInput('discussid', $discussid).
				hInput('parentid', $parentid).
				hInput('ip', $ip).

				eInput('discuss').
				sInput('discuss_save')
				, '', '', 'post', 'edit-form', '', 'discuss_edit_form'),'</div>';
		}

		else
		{
			echo graf(gTxt('comment_not_found'),' class="indicator"');
		}
	}

// -------------------------------------------------------------

	function ipban_add()
	{
		extract(gpsa(array('ip', 'name', 'discussid')));
		$discussid = assert_int($discussid);

		if (!$ip)
		{
			return ipban_list(gTxt('cant_ban_blank_ip'));
		}

		$ban_exists = fetch('ip', 'txp_discuss_ipban', 'ip', $ip);

		if ($ban_exists)
		{
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
		if ($rs)
		{
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

		if ($rs)
		{
			$message = gTxt('ip_ban_removed', array('{ip}' => $ip));

			ipban_list($message);
		}
	}

// -------------------------------------------------------------

	function ipban_list($message = '')
	{
		global $event;

		pageTop(gTxt('list_banned_ips'), $message);

		$rs = safe_rows_start('*, unix_timestamp(date_banned) as uBanned', 'txp_discuss_ipban',
			"1 = 1 order by date_banned desc");

		if ($rs and numRows($rs) > 0)
		{
			echo '<div id="'.$event.'_ban_container" class="txp-container txp-list">'.
				startTable('list', '', 'list').
				n.'<thead>'.
				tr(
					hCell(gTxt('date_banned'), '', ' class="date banned"').
					hCell(gTxt('IP'), '', ' class="ip"').
					hCell(gTxt('name_used'), '', ' class="name"').
					hCell(gTxt('banned_for'), '', ' class="id"').
					hCell('', '', ' class="actions"')
				).
				n.'</thead>';

			echo '<tbody>';
			$ctr = 1;

			while ($a = nextRow($rs))
			{
				extract($a);

				echo tr(
					td(
						safe_strftime('%d %b %Y %I:%M %p', $uBanned)
					, 100, 'date banned').

					td(
						$ip
					, 100, 'ip').

					td(
						$name_used
					, 100, 'name').

					td(
						'<a href="?event=discuss'.a.'step=discuss_edit'.a.'discussid='.$banned_on_message.'">'.
							$banned_on_message.'</a>'
					, 100, 'id').

					td(
						'<a class="action-ban" href="?event=discuss'.a.'step=ipban_unban'.a.'ip='.$ip.a.'_txp_token='.form_token().'">'.gTxt('unban').'</a>'
					, '', 'actions')
				, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').'"'
				);

				$ctr++;
			}

			echo '</tbody>'.
			endTable().
			'</div>';
		}

		else
		{
			echo graf(gTxt('no_ips_banned'),' class="indicator"');
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

		return event_multiedit_form('discuss', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------
	function discuss_multi_edit()
	{
		//FIXME, this method needs some refactoring

		$selected = ps('selected');
		$method = ps('edit_method');
		$done = array();

		if ($selected and is_array($selected))
		{
			// Get all articles for which we have to update the count
			foreach($selected as $id)
				$ids[] = assert_int($id);
			$parentids = safe_column("DISTINCT parentid","txp_discuss","discussid IN (".implode(',',$ids).")");

			$rs = safe_rows_start('*', 'txp_discuss', "discussid IN (".implode(',',$ids).")");
			while ($row = nextRow($rs)) {
				extract($row);
				$id = assert_int($discussid);
				$parentids[] = $parentid;

				if ($method == 'delete') {
					// Delete and if succesful update commnet count
					if (safe_delete('txp_discuss', "discussid = $id"))
						$done[] = $id;
				}
				elseif ($method == 'ban') {
					// Ban the IP and hide all messages by that IP
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

			if ($done)
			{
				// might as well clean up all comment counts while we're here.
				clean_comment_counts($parentids);

				$messages = array(
					'delete'			=> gTxt('comments_deleted', array('{list}' => $done)),
					'ban'					=> gTxt('ips_banned', array('{list}' => $done)),
					'spam'				=> gTxt('comments_marked_spam', array('{list}' => $done)),
					'unmoderated' => gTxt('comments_marked_unmoderated', array('{list}' => $done)),
					'visible'			=> gTxt('comments_marked_visible', array('{list}' => $done))
				);

				update_lastmod();

				return discuss_list($messages[$method]);
			}
		}

		return discuss_list();
	}

?>

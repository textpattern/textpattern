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

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	global $vars;

	if ($event == 'link')
	{
		require_privs('link');

		$vars = array('category', 'url', 'linkname', 'linksort', 'description', 'id');

		$available_steps = array(
			'link_list',
			'link_edit',
			'link_post',
			'link_save',
			'link_delete',
			'link_change_pageby',
			'link_multi_edit'
		);

		if (!$step or !function_exists($step) or !in_array($step, $available_steps))
		{
			link_edit();
		}

		else
		{
			$step();
		}
	}

// -------------------------------------------------------------

	function link_list($message = '')
	{
		global $step, $link_list_pageby;

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'id '.$dir;
			break;

			case 'description':
				$sort_sql = 'description '.$dir.', id asc';
			break;

			case 'category':
				$sort_sql = 'category '.$dir.', id asc';
			break;

			case 'date':
				$sort_sql = 'date '.$dir.', id asc';
			break;

			case 'author':
				$sort_sql = 'author '.$dir.', id asc';
			break;

			default:
				$sort = 'name';
				$sort_sql = 'linksort '.$dir.', id asc';
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'id'         	=> "ID in ('" .join("','", do_list($crit_escaped)). "')",
				'name'			=> "linkname like '%$crit_escaped%'",
				'description'	=> "description like '%$crit_escaped%'",
				'category'		=> "category like '%$crit_escaped%'",
				'author'		=> "author like '%$crit_escaped%'"
			);

			if (array_key_exists($search_method, $critsql))
			{
				$criteria = $critsql[$search_method];
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

		$total = getCount('txp_link', $criteria);

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.link_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' class="indicator"');
			}

			else
			{
				echo n.graf(gTxt('no_links_recorded'), ' class="indicator"');
			}

			return;
		}

		$limit = max($link_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo link_search_form($crit, $search_method);

		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_link', "$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			echo n.n.'<form action="index.php" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">',

				startTable('list').

				n.tr(
					column_head('ID', 'id', 'link', true, $switch_dir, $crit, $search_method, ('id' == $sort) ? $dir : '').
					hCell().
					column_head('link_name', 'name', 'link', true, $switch_dir, $crit, $search_method, ('name' == $sort) ? $dir : '').
					column_head('description', 'description', 'link', true, $switch_dir, $crit, $search_method, ('description' == $sort) ? $dir : '').
					column_head('link_category', 'category', 'link', true, $switch_dir, $crit, $search_method, ('category' == $sort) ? $dir : '').
					column_head('date', 'date', 'link', true, $switch_dir, $crit, $search_method, ('date' == $sort) ? $dir : '').
					column_head('author', 'author', 'link', true, $switch_dir, $crit, $search_method, ('date' == $sort) ? $dir : '').
					hCell()
				);

				while ($a = nextRow($rs))
				{
					extract($a);

					$edit_url = '?event=link'.a.'step=link_edit'.a.'id='.$id.a.'sort='.$sort.
						a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

					echo tr(

						n.td($id, 20).

						td(
							n.'<ul>'.
							n.t.'<li>'.href(gTxt('edit'), $edit_url).'</li>'.
							n.t.'<li>'.href(gTxt('view'), $url).'</li>'.
							n.'</ul>'
						, 35).

						td(
							href(htmlspecialchars($linkname), $edit_url)
						, 125).

						td(
							htmlspecialchars($description)
						, 150).

						td(
							'<span title="'.htmlspecialchars(fetch_category_title($category, 'link')).'">'.$category.'</span>'
						, 125).

						td(
							gTime($uDate)
						, 75).

						td(
							'<span title="'.htmlspecialchars(get_author_name($author)).'">'.htmlspecialchars($author).'</span>'
						).

						td(
							fInput('checkbox', 'selected[]', $id)
						)
					);
				}

			echo n.n.tr(
				tda(
					select_buttons().
					link_multiedit_form($page, $sort, $dir, $crit, $search_method)
				, ' colspan="7" style="text-align: right; border: none;"')
			).

			endTable().
			'</form>'.

			n.nav_form('link', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).

			pageby_form('link', $link_list_pageby);
		}
	}

// -------------------------------------------------------------

	function link_search_form($crit, $method)
	{
		$methods =	array(
			'id'			=> gTxt('ID'),
			'name'			=> gTxt('link_name'),
			'description' 	=> gTxt('description'),
			'category'		=> gTxt('link_category'),
			'author'		=> gTxt('author')
		);

		return search_form('link', 'link_edit', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------

	function link_edit($message = '')
	{
		global $vars, $step;

		pagetop(gTxt('edit_links'), $message);

		extract(gpsa($vars));

		$rs = array();
		if ($id && $step == 'link_edit')
		{
			$id = assert_int($id);
			$rs = safe_row('*', 'txp_link', "id = $id");
			extract($rs);
		}

		if ($step == 'link_save' or $step == 'link_post')
		{
			foreach ($vars as $var)
			{
				$$var = '';
			}
		}

		echo form(

			startTable('edit', '', 'edit-pane') .

			tr(
				fLabelCell('title', '', 'link-title').
				fInputCell('linkname', $linkname, 1, 30, '', 'link-title')
			).

			tr(
				fLabelCell('sort_value', '', 'link-sort').
				fInputCell('linksort', $linksort, 2, 15, '', 'link-sort')
			).

			tr(
				fLabelCell('url', 'link_url', 'link-url').
				fInputCell('url', $url, 3, 30, '', 'link-url')
			).

			tr(
				fLabelCell('link_category', 'link_category', 'link-category').

				td(
					linkcategory_popup($category).' ['.eLink('category', 'list', '', '', gTxt('edit')).']'
				)
			) .

			tr(
				tda(
					'<label for="link-description">'.gTxt('description').'</label>'.sp.popHelp('link_description')
				,' style="text-align: right; vertical-align: top;"').

				td(
					'<textarea id="link-description" name="description" cols="40" rows="7" tabindex="4">'.htmlspecialchars($description).'</textarea>'
				)
			).

			pluggable_ui('link_ui', 'extend_detail_form', '', $rs).

			tr(
				td().
				td(
					fInput('submit', '', gTxt('save'), 'publish')
				)
			).

			endTable().

			eInput('link').

			($id ? sInput('link_save').hInput('id', $id) : sInput('link_post')).

			hInput('search_method', gps('search_method')).
			hInput('crit', gps('crit'))
		, 'margin-bottom: 25px;');

		echo link_list();
	}

//--------------------------------------------------------------

	function linkcategory_popup($cat = '')
	{
		return event_category_popup('link', $cat, 'link-category');
	}

// -------------------------------------------------------------
	function link_post()
	{
		global $txpcfg, $vars, $txp_user;
		$varray = gpsa($vars);

		extract(doSlash($varray));

		if (!$linksort) $linksort = $linkname;

		$q = safe_insert("txp_link",
		   "category    = '$category',
			date        = now(),
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description',
			author		= '$txp_user'"
		);

		$GLOBALS['ID'] = mysql_insert_id( );

		if ($q)
		{
			//update lastmod due to link feeds
			update_lastmod();

			$message = gTxt('link_created', array('{name}' => $linkname));

			link_edit($message);
		}
	}

// -------------------------------------------------------------
	function link_save()
	{
		global $txpcfg, $vars, $txp_user;
		$varray = gpsa($vars);

		extract(doSlash($varray));

		if (!$linksort) $linksort = $linkname;
		$id = assert_int($id);

		$rs = safe_update("txp_link",
		   "category    = '$category',
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description',
			author 		= '$txp_user'",
		   "id = $id"
		);

		if ($rs)
		{
			update_lastmod();

			$message = gTxt('link_updated', array('{name}' => doStrip($linkname)));

			link_edit($message);
		}
	}

// -------------------------------------------------------------
	function link_change_pageby()
	{
		event_change_pageby('link');
		link_edit();
	}

// -------------------------------------------------------------

	function link_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changecategory' => gTxt('changecategory'),
			'delete' => gTxt('delete')
		);

		return event_multiedit_form('link', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function link_multi_edit()
	{
		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return link_edit();
		}

		$selected = array_map('assert_int', $selected);
		$method   = ps('edit_method');
		$changed  = array();

		if ($method == 'delete')
		{
			foreach ($selected as $id)
			{
				if (safe_delete('txp_link', 'id = '.$id))
				{
					$changed[] = $id;
				}
			}
		}
		elseif ($method == 'changecategory')
		{
			foreach ($selected as $id)
			{
				if (safe_update('txp_link', "category = '".doSlash(ps('category'))."'", "id = $id"))
				{
					$changed[] = $id;
				}
			}
		}

		if ($changed)
		{
			return link_edit(gTxt(
				($method == 'delete' ? 'links_deleted' : 'link_updated'),
				array(($method == 'delete' ? '{list}' : '{name}') => join(', ', $changed))));
		}

		return link_edit();
	}

?>

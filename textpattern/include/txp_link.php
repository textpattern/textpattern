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
			'link_list' 	=> false,
			'link_edit' 	=> false,
			'link_post' 	=> true,
			'link_save' 	=> true,
			'link_delete' 	=> true,
			'link_change_pageby' => true,
			'link_multi_edit' => true
		);

		if (!$step or !bouncer($step, $available_steps)){
			$step = 'link_edit';
		}
		$step();
	}

// -------------------------------------------------------------

	function link_list($message = '')
	{
		global $event,$step, $link_list_pageby, $txp_user;

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('link_sort_column', 'name');
		if ($dir === '') $dir = get_pref('link_sort_dir', 'asc');
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

		set_pref('link_sort_column', $sort, 'link', 2, '', 0, PREF_PRIVATE);
		set_pref('link_sort_dir', $dir, 'link', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit != '')
		{
			$crit_escaped = doSlash(str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));

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

		echo '<div id="'.$event.'_control" class="txp-control-panel">';

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.link_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
			}

			else
			{
				echo n.graf(gTxt('no_links_recorded'), ' class="indicator"').'</div>';
			}

			return;
		}

		$limit = max($link_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo link_search_form($crit, $search_method).'</div>';

		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_link', "$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			$show_authors = !has_single_author('txp_link');

			echo n.'<div class="txp-list">';
			echo n.n.'<form action="index.php" id="links_form" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">',

				startTable('list', '', 'list').
				n.'<thead>'.
				n.tr(
					column_head('ID', 'id', 'link', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id').
					hCell('', '', ' class="actions"').
					column_head('link_name', 'name', 'link', true, $switch_dir, $crit, $search_method, (('name' == $sort) ? "$dir " : '').'name').
					column_head('description', 'description', 'link', true, $switch_dir, $crit, $search_method, (('description' == $sort) ? "$dir " : '').'description').
					column_head('link_category', 'category', 'link', true, $switch_dir, $crit, $search_method, (('category' == $sort) ? "$dir " : '').'category').
					column_head('date', 'date', 'link', true, $switch_dir, $crit, $search_method, (('date' == $sort) ? "$dir " : '').'date created').
					($show_authors ? column_head('author', 'author', 'link', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '').
					hCell('', '', ' class="multi-edit"')
				).
				n.'</thead>';

				$tfoot = n.'<tfoot>'.tr(
					tda(
						select_buttons().
						link_multiedit_form($page, $sort, $dir, $crit, $search_method)
					, ' class="multi-edit" colspan="'.($show_authors ? '8' : '7').'" style="text-align: right; border: none;"')
				).n.'</tfoot>';

				echo $tfoot;
				echo '<tbody>';

				$ctr = 1;

				while ($a = nextRow($rs))
				{
					extract($a);

					$edit_url = '?event=link'.a.'step=link_edit'.a.'id='.$id.a.'sort='.$sort.
						a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

					$can_edit = has_privs('link.edit') || ($author == $txp_user && has_privs('link.edit.own'));

					echo tr(

						n.td($id, 20, 'id').

						td(
							n.'<ul>'.
							($can_edit ? n.t.'<li class="action-edit">'.href(gTxt('edit'), $edit_url).'</li>' : '').
							n.t.'<li class="action-view">'.href(gTxt('view'), $url).'</li>'.
							n.'</ul>'
						, 35, 'actions').

						td(
							($can_edit ? href($linkname, $edit_url) : $linkname)
						, 125, 'name').

						td(
							htmlspecialchars($description)
						, 150, 'description').

						td(
							'<span title="'.htmlspecialchars(fetch_category_title($category, 'link')).'">'.$category.'</span>'
						, 125, 'category').

						td(
							gTime($uDate)
						, 75, 'date created').

						($show_authors ? td(
							'<span title="'.htmlspecialchars(get_author_name($author)).'">'.htmlspecialchars($author).'</span>'
						, '', 'author') : '').

						td(
							fInput('checkbox', 'selected[]', $id)
						, '', 'multi-edit')
					, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').'"'
					);

					$ctr++;
				}

			echo '</tbody>'.
			n.endTable().
			n.tInput().
			n.'</form>'.

			n.'<div id="'.$event.'_navigation" class="txp-navigation">'.
			n.nav_form('link', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).

			pageby_form('link', $link_list_pageby).
			n.'</div>'.n.'</div>';
		}
	}

// -------------------------------------------------------------

	function link_search_form($crit, $method)
	{
		$methods =	array(
			'id'          => gTxt('ID'),
			'name'        => gTxt('link_name'),
			'description' => gTxt('description'),
			'category'    => gTxt('link_category'),
			'author'      => gTxt('author')
		);

		return search_form('link', 'link_edit', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------

	function link_edit($message = '')
	{
		global $vars, $event, $step, $txp_user;

		pagetop(gTxt('edit_links'), $message);

		echo '<div id="'.$event.'_container" class="txp-container">';

		extract(gpsa($vars));

		$rs = array();
		if ($id && $step == 'link_edit')
		{
			$id = assert_int($id);
			$rs = safe_row('*', 'txp_link', "id = $id");
			if ($rs)
			{
				extract($rs);
				if (!has_privs('link.edit') && !($author == $txp_user && has_privs('link.edit.own')))
				{
					link_list(gTxt('restricted_area'));
					return;
				}
			}
		}

		if ($step == 'link_save' or $step == 'link_post')
		{
			foreach ($vars as $var)
			{
				$$var = '';
			}
		}

		if (has_privs('link.edit') || has_privs('link.edit.own'))
		{
			echo '<div class="txp-edit">'.
				form(

				startTable('edit', '', 'edit-pane') .

				tr(
					fLabelCell('title', '', 'link-title').
					fInputCell('linkname', $linkname, 1, 30, '', 'link-title')
				, ' class="name"').

				tr(
					fLabelCell('sort_value', '', 'link-sort').
					fInputCell('linksort', $linksort, 2, 15, '', 'link-sort')
				, ' class="sort"').

				tr(
					fLabelCell('url', 'link_url', 'link-url').
					fInputCell('url', $url, 3, 30, '', 'link-url')
				, ' class="url"').

				tr(
					fLabelCell('link_category', 'link_category', 'link-category').

					td(
						linkcategory_popup($category).' ['.eLink('category', 'list', '', '', gTxt('edit')).']'
					)
				, ' class="category"') .

				tr(
					tda(
						'<label for="link-description">'.gTxt('description').'</label>'.sp.popHelp('link_description')
					,' style="text-align: right; vertical-align: top;"').

					td(
						'<textarea id="link-description" name="description" cols="40" rows="7" tabindex="4">'.htmlspecialchars($description).'</textarea>'
					)
				, ' class="description text"').

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
			, 'margin-bottom: 25px;', '', 'post', 'edit-form', '', 'link_details').'</div>';

		}
		link_list();

		echo '</div>';
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

		if ($linkname === '' && $url === '' && $description === '')
		{
			link_edit();
			return;
		}

		if (!has_privs('link.edit.own'))
		{
			link_edit(gTxt('restricted_area'));
			return;
		}

		if (!$linksort) $linksort = $linkname;

		$q = safe_insert("txp_link",
		   "category    = '$category',
			date        = now(),
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description',
			author		= '".doSlash($txp_user)."'"
		);

		$GLOBALS['ID'] = mysql_insert_id( );

		if ($q)
		{
			//update lastmod due to link feeds
			update_lastmod();

			$message = gTxt('link_created', array('{name}' => doStrip($linkname)));

			link_edit($message);
		}
	}

// -------------------------------------------------------------
	function link_save()
	{
		global $txpcfg, $vars, $txp_user;

		$varray = gpsa($vars);

		extract(doSlash($varray));

		$id = assert_int($id);

		if ($linkname === '' && $url === '' && $description === '')
		{
			link_edit();
			return;
		}

		$author = fetch('author', 'txp_link', 'id', $id);
		if (!has_privs('link.edit') && !($author == $txp_user && has_privs('link.edit.own')))
		{
			link_edit(gTxt('restricted_area'));
			return;
		}

		if (!$linksort) $linksort = $linkname;

		$rs = safe_update("txp_link",
		   "category    = '$category',
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description',
			author 		= '".doSlash($txp_user)."'",
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
			'changeauthor' => gTxt('changeauthor'),
			'delete' => gTxt('delete')
		);

		if (has_single_author('txp_link'))
		{
			unset($methods['changeauthor']);
		}

		if (!has_privs('link.delete.own') && !has_privs('link.delete'))
		{
			unset($methods['delete']);
		}

		return event_multiedit_form('link', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function link_multi_edit()
	{
		global $txp_user;

		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			link_edit();
			return;
		}

		$selected = array_map('assert_int', $selected);
		$method   = ps('edit_method');
		$changed  = array();

		switch ($method)
		{
			case 'delete';
				if (!has_privs('link.delete'))
				{
					if (has_privs('link.delete.own'))
					{
						$selected = safe_column('id', 'txp_link', 'id IN ('.join(',', $selected).') AND author=\''.doSlash($txp_user).'\'' );
					}
					else
					{
						$selected = array();
					}
				}
				foreach ($selected as $id)
				{
					if (safe_delete('txp_link', 'id = '.$id))
					{
						$changed[] = $id;
					}
				}
				$key = '';
				break;

			case 'changecategory':
				$key = 'category';
				$val = ps('category');
				break;

			case 'changeauthor';
				$key = 'author';
				$val = ps('author');
				break;

			default:
				$key = '';
				$val = '';
				break;
		}

		if ($selected and $key)
		{
			foreach ($selected as $id)
			{
				if (safe_update('txp_link', "$key = '".doSlash($val)."'", "id = $id"))
				{
					$changed[] = $id;
				}
			}
		}

		if ($changed)
		{
			update_lastmod();

			link_edit(gTxt(
				($method == 'delete' ? 'links_deleted' : 'link_updated'),
				array(($method == 'delete' ? '{list}' : '{name}') => join(', ', $changed))));
			return;
		}

		link_edit();
	}

?>
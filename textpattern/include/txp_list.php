<?php
/*
	This is Textpattern
	Copyright 2005 by Dean Allen
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	global $statuses;
	$statuses = array(
		1 => gTxt('draft'),
		2 => gTxt('hidden'),
		3 => gTxt('pending'),
		4 => gTxt('live'),
		5 => gTxt('sticky'),
	);

	if ($event=='list') {
		require_privs('article');

		if(!$step or !bouncer($step, array('list_list' => false, 'list_change_pageby' => true, 'list_multi_edit' => true))){
			$step = 'list_list';
		}
		$step();
	}

//--------------------------------------------------------------

	function list_list($message = '', $post = '')
	{
		global $statuses, $comments_disabled_after, $step, $txp_user, $article_list_pageby, $event;

		pagetop(gTxt('tab_list'), $message);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('article_sort_column', 'posted');
		if ($dir === '') $dir = get_pref('article_sort_dir', 'desc');
		$dir = ($dir == 'asc') ? 'asc' : 'desc';

		$sesutats = array_flip($statuses);

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'ID '.$dir;
			break;

			case 'expires':
				$sort_sql = 'Expires '.$dir;
			break;

			case 'title':
				$sort_sql = 'Title '.$dir.', Posted desc';
			break;

			case 'section':
				$sort_sql = 'Section '.$dir.', Posted desc';
			break;

			case 'category1':
				$sort_sql = 'Category1 '.$dir.', Posted desc';
			break;

			case 'category2':
				$sort_sql = 'Category2 '.$dir.', Posted desc';
			break;

			case 'status':
				$sort_sql = 'Status '.$dir.', Posted desc';
			break;

			case 'author':
				$sort_sql = 'AuthorID '.$dir.', Posted desc';
			break;

			case 'comments':
				$sort_sql = 'comments_count '.$dir.', Posted desc';
			break;

			case 'lastmod':
				$sort_sql = 'LastMod '.$dir.', Posted desc';
			break;

			default:
				$sort = 'posted';
				$sort_sql = 'Posted '.$dir;
			break;
		}

		set_pref('article_sort_column', $sort, 'list', 2, '', 0, PREF_PRIVATE);
		set_pref('article_sort_dir', $dir, 'list', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit != '')
		{
			$crit_escaped = doSlash(str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));
			$critsql = array(
				'id'         => "ID in ('" .join("','", do_list($crit_escaped)). "')",
				'title_body_excerpt' => "Title like '%$crit_escaped%' or Body like '%$crit_escaped%' or Excerpt like '%$crit_escaped%'",
				'section'    => "Section like '%$crit_escaped%'",
				'keywords'   => "FIND_IN_SET('".$crit_escaped."',Keywords)",
				'categories' => "Category1 like '%$crit_escaped%' or Category2 like '%$crit_escaped%'",
				'status'     => "Status = '".(@$sesutats[gTxt($crit_escaped)])."'",
				'author'     => "AuthorID like '%$crit_escaped%'",
				'article_image' => "Image in ('" .join("','", do_list($crit_escaped)). "')",
				'posted'     => "Posted like '$crit_escaped%'",
				'lastmod'    => "LastMod like '$crit_escaped%'"
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

		$total = safe_count('textpattern', "$criteria");

		echo '<div id="'.$event.'_control" class="txp-control-panel">';

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.list_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
			}

			else
			{
				echo graf(gTxt('no_articles_recorded'), ' class="indicator"').'</div>';
			}

			return;
		}

		$limit = max($article_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo n.list_search_form($crit, $search_method).'</div>';

		$rs = safe_rows_start('*, unix_timestamp(Posted) as posted, unix_timestamp(LastMod) as lastmod, unix_timestamp(Expires) as expires', 'textpattern',
			"$criteria order by $sort_sql limit $offset, $limit"
		);

		if ($rs)
		{
			$show_authors = !has_single_author('textpattern', 'AuthorID');

			$total_comments = array();

			// fetch true comment count, not the public comment count
			// maybe we should have another row in the db?
			$rs2 = safe_rows_start('parentid, count(*) as num', 'txp_discuss', "1 group by parentid order by parentid");

			if ($rs2)
			{
				while ($a = nextRow($rs2))
				{
					$pid = $a['parentid'];
					$num = $a['num'];

					$total_comments[$pid] = $num;
				}
			}

			echo n.'<div id="'.$event.'_container" class="txp-container txp-list">';
			echo n.n.'<form name="longform" id="articles_form" method="post" action="index.php" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

				n.startTable('list','','list','','90%').
				n.'<thead>'.
				n.tr(
					n.column_head('ID', 'id', 'list', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id actions').
					column_head('posted', 'posted', 'list', true, $switch_dir, $crit, $search_method, (('posted' == $sort) ? "$dir " : '').'date posted created').
					column_head('article_modified', 'lastmod', 'list', true, $switch_dir, $crit, $search_method, (('lastmod' == $sort) ? "$dir " : '').'articles_detail date modified').
					column_head('expires', 'expires', 'list', true, $switch_dir, $crit, $search_method, (('expires' == $sort) ? "$dir " : '').'articles_detail date expires').
					column_head('title', 'title', 'list', true, $switch_dir, $crit, $search_method, (('title' == $sort) ? "$dir " : '').'title').
					column_head('section', 'section', 'list', true, $switch_dir, $crit, $search_method, (('section' == $sort) ? "$dir " : '').'section').
					column_head('category1', 'category1', 'list', true, $switch_dir, $crit, $search_method, (('category1' == $sort) ? "$dir " : '').'articles_detail category category1').
					column_head('category2', 'category2', 'list', true, $switch_dir, $crit, $search_method, (('category2' == $sort) ? "$dir " : '').'articles_detail category category2').
					column_head('status', 'status', 'list', true, $switch_dir, $crit, $search_method, (('status' == $sort) ? "$dir " : '').'status').
					($show_authors ? column_head('author', 'author', 'list', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '').
					column_head('comments', 'comments', 'list', true, $switch_dir, $crit, $search_method, (('comments' == $sort) ? "$dir " : '').'articles_detail comments').
					hCell('', '', ' class="multi-edit"')
				).
				n.'</thead>';

			include_once txpath.'/publish/taghandlers.php';

			$tfoot = n.'<tfoot>'.tr(
				tda(
					toggle_box('articles_detail'),
					' class="detail-toggle" colspan="2"'
				).

				tda(
					select_buttons().
					list_multiedit_form($page, $sort, $dir, $crit, $search_method)
				,' class="multi-edit" colspan="'.($show_authors ? '10' : '9').'"')
			).n.'</tfoot>';

			echo $tfoot;
			echo '<tbody>';

			$ctr = 1;

			while ($a = nextRow($rs))
			{
				extract($a);

				if (empty($Title))
				{
					$Title = '<em>'.eLink('article', 'edit', 'ID', $ID, gTxt('untitled')).'</em>';
				}

				else
				{
					$Title = eLink('article', 'edit', 'ID', $ID, $Title);
				}

				$Category1 = ($Category1) ? '<span title="'.htmlspecialchars(fetch_category_title($Category1)).'">'.$Category1.'</span>' : '';
				$Category2 = ($Category2) ? '<span title="'.htmlspecialchars(fetch_category_title($Category2)).'">'.$Category2.'</span>' : '';

				if ($Status != 4 and $Status != 5)
				{
					$view_url = '?txpreview='.intval($ID).'.'.time();
				}
				else
				{
					$view_url = permlinkurl($a);
				}

				$manage = n.'<ul class="articles_detail actions">'.
						n.t.'<li class="action-edit">'.eLink('article', 'edit', 'ID', $ID, gTxt('edit')).'</li>'.
						n.t.'<li class="action-view"><a href="'.$view_url.'" class="article-view">'.gTxt('view').'</a></li>'.
						n.'</ul>';

				$Status = !empty($Status) ? $statuses[$Status] : '';

				$comments = gTxt('none');

				if (isset($total_comments[$ID]) and $total_comments[$ID] > 0)
				{
					$comments = href(gTxt('manage'), 'index.php?event=discuss'.a.'step=list'.a.'search_method=parent'.a.'crit='.$ID).
						' ('.$total_comments[$ID].')';
				}

				$comment_status = ($Annotate) ? gTxt('on') : gTxt('off');

				if ($comments_disabled_after)
				{
					$lifespan = $comments_disabled_after * 86400;
					$time_since = time() - $posted;

					if ($time_since > $lifespan)
					{
						$comment_status = gTxt('expired');
					}
				}

				$comments = n.'<ul>'.
					n.t.'<li class="comments-status">'.$comment_status.'</li>'.
					n.t.'<li class="comments-manage">'.$comments.'</li>'.
					n.'</ul>';

				echo n.n.tr(

					n.td(eLink('article', 'edit', 'ID', $ID, $ID).$manage, '', 'id').

					td(
						gTime($posted), '', ($posted < time() ? '' : 'unpublished ').'date posted created'
					).

					td(
						gTime($lastmod), '', "articles_detail date modified"
					).

					td(
						($expires ? gTime($expires) : ''), '' ,'articles_detail date expires'
					).

					td($Title, '', 'title').

					td(
						'<span title="'.htmlspecialchars(fetch_section_title($Section)).'">'.$Section.'</span>'
					, 75, 'section').

					td($Category1, 100, "articles_detail category category1").
					td($Category2, 100, "articles_detail category category2").
					td(($a['Status'] < 4 ? $Status : '<a href="'.permlinkurl($a).'">'.$Status.'</a>'), 50, 'status').

					($show_authors ? td(
						'<span title="'.htmlspecialchars(get_author_name($AuthorID)).'">'.htmlspecialchars($AuthorID).'</span>'
						, '', 'author'
					) : '').

					td($comments, 50, "articles_detail comments").

					td((
						(  ($a['Status'] >= 4 and has_privs('article.edit.published'))
						or ($a['Status'] >= 4 and $AuthorID == $txp_user
											     and has_privs('article.edit.own.published'))
						or ($a['Status'] < 4 and has_privs('article.edit'))
						or ($a['Status'] < 4 and $AuthorID == $txp_user and has_privs('article.edit.own'))
						)
						? fInput('checkbox', 'selected[]', $ID, 'checkbox')
						: '&nbsp;'
					), '', 'multi-edit')
				, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').'"'
				);

				$ctr++;
			}

			echo '</tbody>'.
			n.endTable().
			n.tInput().
			n.'</form>'.

			n.'<div id="'.$event.'_navigation" class="txp-navigation">'.
			n.nav_form('list', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).

			n.pageby_form('list', $article_list_pageby).
			n.'</div>'.n.'</div>';
		}
	}

// -------------------------------------------------------------
	function list_change_pageby()
	{
		event_change_pageby('article');
		list_list();
	}

// -------------------------------------------------------------

	function list_search_form($crit, $method)
	{
		$methods =	array(
			'id'                 => gTxt('ID'),
			'title_body_excerpt' => gTxt('title_body_excerpt'),
			'section'            => gTxt('section'),
			'categories'         => gTxt('categories'),
			'keywords'           => gTxt('keywords'),
			'status'             => gTxt('status'),
			'author'             => gTxt('author'),
			'article_image'      => gTxt('article_image'),
			'posted'             => gTxt('posted'),
			'lastmod'            => gTxt('article_modified')
		);

		return search_form('list', 'list', $crit, $methods, $method, 'title_body_excerpt');
	}

// -------------------------------------------------------------

	function list_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changesection'   => gTxt('changesection'),
			'changecategory1' => gTxt('changecategory1'),
			'changecategory2' => gTxt('changecategory2'),
			'changestatus'    => gTxt('changestatus'),
			'changecomments'  => gTxt('changecomments'),
			'changeauthor'    => gTxt('changeauthor'),
			'delete'          => gTxt('delete'),
		);

		if (has_single_author('textpattern', 'AuthorID'))
		{
			unset($methods['changeauthor']);
		}

		if(!has_privs('article.delete.own') && !has_privs('article.delete'))
		{
			unset($methods['delete']);
		}

		return event_multiedit_form('list', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function list_multi_edit()
	{
		global $txp_user;

		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return list_list();
		}

		$selected = array_map('assert_int', $selected);
		$method   = ps('edit_method');
		$changed  = false;
		$ids      = array();

		if ($method == 'delete')
		{
			if (!has_privs('article.delete'))
			{
				$allowed = array();

				if (has_privs('article.delete.own'))
				{
					foreach ($selected as $id)
					{
						$author = safe_field('AuthorID', 'textpattern', "ID = $id");

						if ($author == $txp_user)
						{
							$allowed[] = $id;
						}
					}
				}

				$selected = $allowed;
			}

			foreach ($selected as $id)
			{
				if (safe_delete('textpattern', "ID = $id"))
				{
					$ids[] = $id;
				}
			}

			$changed = join(', ', $ids);

			if ($changed)
			{
				safe_update('txp_discuss', "visible = ".MODERATE, "parentid in($changed)");
			}
		}

		else
		{
			$selected = array_map('assert_int', $selected);
			$selected = safe_rows('ID, AuthorID, Status', 'textpattern',
									  'ID in ('. implode(',',$selected) .')');

			$allowed = array();
			foreach ($selected as $item)
			{
				if ( ($item['Status'] >= 4 and has_privs('article.edit.published'))
				  or ($item['Status'] >= 4 and $item['AuthorID'] == $txp_user and has_privs('article.edit.own.published'))
				  or ($item['Status'] < 4 and has_privs('article.edit'))
				  or ($item['Status'] < 4 and $item['AuthorID'] == $txp_user and has_privs('article.edit.own')))
				{
					$allowed[] = $item['ID'];
				}
			}

			$selected = $allowed;
			unset($allowed);

			switch ($method)
			{
				// change author
				case 'changeauthor':

					$key = 'AuthorID';
					$val = has_privs('article.edit') ? ps('AuthorID') : '';

					// do not allow to be set to an empty value
					if (!$val)
					{
						$selected = array();
					}

				break;

				// change category1
				case 'changecategory1':
					$key = 'Category1';
					$val = ps('Category1');
				break;

				// change category2
				case 'changecategory2':
					$key = 'Category2';
					$val = ps('Category2');
				break;

				// change comments
				case 'changecomments':
					$key = 'Annotate';
					$val = (int) ps('Annotate');
				break;

				// change section
				case 'changesection':

					$key = 'Section';
					$val = ps('Section');

					// do not allow to be set to an empty value
					if (!$val)
					{
						$selected = array();
					}

				break;

				// change status
				case 'changestatus':

					$key = 'Status';
					$val = ps('Status');
					if (!has_privs('article.publish') && $val>=4) $val = 3;

					// do not allow to be set to an empty value
					if (!$val)
					{
						$selected = array();
					}

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
					if (safe_update('textpattern', "$key = '".doSlash($val)."'", "ID = $id"))
					{
						$ids[] = $id;
					}
				}

				$changed = join(', ', $ids);
			}
		}

		if ($changed)
		{
			update_lastmod();

			return list_list(
				messenger('article', $changed, (($method == 'delete') ? 'deleted' : 'modified' ))
			);
		}

		return list_list();
	}

?>
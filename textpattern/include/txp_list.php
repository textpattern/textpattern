<?php
/*
	This is Textpattern
	Copyright 2005 by Dean Allen
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'list') {
		global $statuses, $all_cats, $all_authors, $all_sections;

		require_privs('article');

		$statuses = array(
			STATUS_DRAFT   => gTxt('draft'),
			STATUS_HIDDEN  => gTxt('hidden'),
			STATUS_PENDING => gTxt('pending'),
			STATUS_LIVE    => gTxt('live'),
			STATUS_STICKY  => gTxt('sticky'),
		);

		$all_cats = getTree('root', 'article');
		$all_authors = the_privileged('article.edit.own');
		$all_sections = safe_column('name', 'txp_section', "name != 'default'");

		$available_steps = array(
			'list_list'          => false,
			'list_change_pageby' => true,
			'list_multi_edit'    => true,
		);

		if ($step && bouncer($step, $available_steps)) {
			$step();
		} else {
			list_list();
		}
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

			case 'title':
				$sort_sql = 'Title '.$dir.', Posted desc';
			break;

			case 'expires':
				$sort_sql = 'Expires '.$dir;
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
			$verbatim = preg_match('/^"(.*)"$/', $crit, $m);
			$crit_escaped = doSlash($verbatim ? $m[1] : str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));
			$critsql = $verbatim ?
				array(
					'id'         => "ID in ('" .join("','", do_list($crit_escaped)). "')",
					'title_body_excerpt' => "Title = '$crit_escaped' or Body = '$crit_escaped' or Excerpt = '$crit_escaped'",
					'section'    => "Section = '$crit_escaped'",
					'keywords'   => "FIND_IN_SET('".$crit_escaped."',Keywords)",
					'categories' => "Category1 = '$crit_escaped' or Category2 = '$crit_escaped'",
					'status'     => "Status = '".(@$sesutats[gTxt($crit_escaped)])."'",
					'author'     => "AuthorID = '$crit_escaped'",
					'article_image' => "Image in ('" .join("','", do_list($crit_escaped)). "')",
					'posted'     => "Posted = '$crit_escaped'",
					'lastmod'    => "LastMod = '$crit_escaped'"
				) : array(
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

		$criteria .= callback_event('admin_criteria', 'list_list', 0, $criteria);

		$total = safe_count('textpattern', "$criteria");

		echo '<h1 class="txp-heading">'.gTxt('tab_list').'</h1>';
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

			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo n.n.'<form name="longform" id="articles_form" class="multi_edit_form" method="post" action="index.php">'.

				n.'<div class="txp-listtables">'.
				n.startTable('', '', 'txp-list').
				n.'<thead>'.
				n.tr(
					n.hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' scope="col" title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
					n.column_head('ID', 'id', 'list', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id actions').
					column_head('title', 'title', 'list', true, $switch_dir, $crit, $search_method, (('title' == $sort) ? "$dir " : '').'title').
					column_head('posted', 'posted', 'list', true, $switch_dir, $crit, $search_method, (('posted' == $sort) ? "$dir " : '').'date posted created').
					column_head('article_modified', 'lastmod', 'list', true, $switch_dir, $crit, $search_method, (('lastmod' == $sort) ? "$dir " : '').'articles_detail date modified').
					column_head('expires', 'expires', 'list', true, $switch_dir, $crit, $search_method, (('expires' == $sort) ? "$dir " : '').'articles_detail date expires').
					column_head('section', 'section', 'list', true, $switch_dir, $crit, $search_method, (('section' == $sort) ? "$dir " : '').'section').
					column_head('category1', 'category1', 'list', true, $switch_dir, $crit, $search_method, (('category1' == $sort) ? "$dir " : '').'articles_detail category category1').
					column_head('category2', 'category2', 'list', true, $switch_dir, $crit, $search_method, (('category2' == $sort) ? "$dir " : '').'articles_detail category category2').
					column_head('status', 'status', 'list', true, $switch_dir, $crit, $search_method, (('status' == $sort) ? "$dir " : '').'status').
					($show_authors ? column_head('author', 'author', 'list', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '').
					column_head('comments', 'comments', 'list', true, $switch_dir, $crit, $search_method, (('comments' == $sort) ? "$dir " : '').'articles_detail comments')
				).
				n.'</thead>';

			include_once txpath.'/publish/taghandlers.php';

			echo '<tbody>';

			$validator = new Validator();

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

				// Valid section and categories?
				$validator->setConstraints(array(new SectionConstraint($Section)));
				$vs = $validator->validate() ? '' : ' error';

				$validator->setConstraints(array(new CategoryConstraint($Category1, array('type' => 'article'))));
				$vc[1] = $validator->validate() ? '' : ' error';

				$validator->setConstraints(array(new CategoryConstraint($Category2, array('type' => 'article'))));
				$vc[2] = $validator->validate() ? '' : ' error';

				$Category1 = ($Category1) ? '<span title="'.txpspecialchars(fetch_category_title($Category1)).'">'.$Category1.'</span>' : '';
				$Category2 = ($Category2) ? '<span title="'.txpspecialchars(fetch_category_title($Category2)).'">'.$Category2.'</span>' : '';

				if ($Status != STATUS_LIVE and $Status != STATUS_STICKY)
				{
					$view_url = '?txpreview='.intval($ID).'.'.time();
				}
				else
				{
					$view_url = permlinkurl($a);
				}

				$Status = !empty($Status) ? $statuses[$Status] : '';

				$comments = '(0)';

				if (isset($total_comments[$ID]) and $total_comments[$ID] > 0)
				{
					$comments = href('('.$total_comments[$ID].')', 'index.php?event=discuss'.a.'step=list'.a.'search_method=parent'.a.'crit='.$ID, ' title="'.gTxt('manage').'"');
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

				$comments = n.'<span class="comments-status">'.$comment_status.'</span> <span class="comments-manage">'.$comments.'</span>';

				echo n.n.tr(

					n.td((
						(  ($a['Status'] >= STATUS_LIVE and has_privs('article.edit.published'))
						or ($a['Status'] >= STATUS_LIVE and $AuthorID == $txp_user
											     and has_privs('article.edit.own.published'))
						or ($a['Status'] < STATUS_LIVE and has_privs('article.edit'))
						or ($a['Status'] < STATUS_LIVE and $AuthorID == $txp_user and has_privs('article.edit.own'))
						)
						? fInput('checkbox', 'selected[]', $ID, 'checkbox')
						: '&#160;'
					), '', 'multi-edit').

					n.hCell(eLink('article', 'edit', 'ID', $ID, $ID) .sp. '<span class="articles_detail">[<a href="'.$view_url.'">'.gTxt('view').'</a>]</span>', '', ' scope="row" class="id"').

					td($Title, '', 'title').

					td(
						gTime($posted), '', ($posted < time() ? '' : 'unpublished ').'date posted created'
					).

					td(
						gTime($lastmod), '', "articles_detail date modified"
					).

					td(
						($expires ? gTime($expires) : ''), '' ,'articles_detail date expires'
					).

					td(
						'<span title="'.txpspecialchars(fetch_section_title($Section)).'">'.$Section.'</span>'
					, '', 'section'.$vs).

					td($Category1, '', "articles_detail category category1".$vc[1]).
					td($Category2, '', "articles_detail category category2".$vc[2]).
					td('<a href="'.$view_url.'" title="'.gTxt('view').'">'.$Status.'</a>', '', 'status').

					($show_authors ? td(
						'<span title="'.txpspecialchars(get_author_name($AuthorID)).'">'.txpspecialchars($AuthorID).'</span>'
						, '', 'author'
					) : '').

					td($comments, '', "articles_detail comments")
				);
			}

			echo '</tbody>',
				n, endTable(),
				n, '</div>',
				n, list_multiedit_form($page, $sort, $dir, $crit, $search_method),
				n, tInput(),
				n, '</form>',
				n, graf(
					toggle_box('articles_detail'),
					' class="detail-toggle"'
				),
				n, '<div id="'.$event.'_navigation" class="txp-navigation">',
				n, nav_form('list', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit),
				n, pageby_form('list', $article_list_pageby),
				n, '</div>',
				n, '</div>';
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
		global $statuses, $all_cats, $all_authors, $all_sections;

		if ($all_cats) {
			$category1 = treeSelectInput('Category1', $all_cats, '');
			$category2 = treeSelectInput('Category2', $all_cats, '');
		}
		else
		{
			$category1 = $category2 = '';
		}

		$sections = $all_sections ? selectInput('Section', $all_sections, '', true) : '';
		$comments = onoffRadio('Annotate', get_pref('comments_on_default'));
		$status = selectInput('Status', $statuses, '', true);
		$authors = $all_authors ? selectInput('AuthorID', $all_authors, '', true) : '';

		$methods = array(
			'changesection'   => array('label' => gTxt('changesection'), 'html' => $sections),
			'changecategory1' => array('label' => gTxt('changecategory1'), 'html' => $category1),
			'changecategory2' => array('label' => gTxt('changecategory2'), 'html' => $category2),
			'changestatus'    => array('label' => gTxt('changestatus'), 'html' => $status),
			'changecomments'  => array('label' => gTxt('changecomments'), 'html' => $comments),
			'changeauthor'    => array('label' => gTxt('changeauthor'), 'html' => $authors),
			'delete'          => gTxt('delete'),
		);

		if (!$all_cats)
		{
			unset($methods['changecategory1'], $methods['changecategory2']);
		}

		if (has_single_author('textpattern', 'AuthorID'))
		{
			unset($methods['changeauthor']);
		}

		if(!has_privs('article.delete.own') && !has_privs('article.delete'))
		{
			unset($methods['delete']);
		}

		return multi_edit($methods, 'list', 'list_multi_edit', $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function list_multi_edit()
	{
		global $txp_user, $statuses, $all_cats, $all_authors, $all_sections;

		// Empty entry to permit clearing the categories
		$categories = array('');

		foreach ($all_cats as $row) {
			$categories[] = $row['name'];
		}

		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return list_list();
		}

		$selected = array_map('assert_int', $selected);
		$method   = ps('edit_method');
		$changed  = false;
		$ids      = array();
		$key      = '';

		if ($method == 'delete')
		{
			if (!has_privs('article.delete'))
			{
				$allowed = array();

				if (has_privs('article.delete.own'))
				{
					$allowed = safe_column_num('ID', 'textpattern', 'ID in('.join(',',$selected).') and AuthorID=\''.doSlash($txp_user).'\'');
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
				callback_event('articles_deleted', '', 0, $ids);
			}
		}

		else
		{
			$selected = safe_rows('ID, AuthorID, Status', 'textpattern',
									  'ID in ('. implode(',',$selected) .')');

			$allowed = array();
			foreach ($selected as $item)
			{
				if ( ($item['Status'] >= STATUS_LIVE and has_privs('article.edit.published'))
				  or ($item['Status'] >= STATUS_LIVE and $item['AuthorID'] == $txp_user and has_privs('article.edit.own.published'))
				  or ($item['Status'] < STATUS_LIVE and has_privs('article.edit'))
				  or ($item['Status'] < STATUS_LIVE and $item['AuthorID'] == $txp_user and has_privs('article.edit.own')))
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
					$val = has_privs('article.edit') ? ps('AuthorID') : '';
					if (in_array($val, $all_authors))
					{
						$key = 'AuthorID';
					}
				break;

				// change category1
				case 'changecategory1':
					$val = ps('Category1');
					if (in_array($val, $categories))
					{
						$key = 'Category1';
					}
				break;

				// change category2
				case 'changecategory2':
					$val = ps('Category2');
					if (in_array($val, $categories))
					{
						$key = 'Category2';
					}
				break;

				// change comments
				case 'changecomments':
					$key = 'Annotate';
					$val = (int) ps('Annotate');
				break;

				// change section
				case 'changesection':
					$val = ps('Section');
					if (in_array($val, $all_sections))
					{
						$key = 'Section';
					}
				break;

				// change status
				case 'changestatus':
					$val = (int) ps('Status');
					if (array_key_exists($val, $statuses))
					{
						$key = 'Status';
					}

					if (!has_privs('article.publish') && $val >= STATUS_LIVE)
					{
						$val = STATUS_PENDING;
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

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

	if ($event == 'section') {
		require_privs('section');

		global $all_pages, $all_styles;
		$all_pages = safe_column('name', 'txp_page', "1=1");
		$all_styles = safe_column('name', 'txp_css', "1=1");

		$available_steps = array(
			'section_change_pageby' => true,
			'sec_section_list'      => false,
			'section_delete'        => true,
			'section_save'          => true,
			'section_edit'          => false,
			'section_multi_edit'    => true,
			'section_set_default'   => true,
			'section_toggle_option' => true,
		);

		if ($step && bouncer($step, $available_steps)) {
			$step();
		} else {
			sec_section_list();
		}
	}

// -------------------------------------------------------------
// So-named to avoid clashing with the <txp:section_list /> tag
	function sec_section_list($message = '')
	{
		global $event, $section_list_pageby;

		pagetop(gTxt('tab_sections'), $message);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('section_sort_column', 'time');
		if ($dir === '') $dir = get_pref('section_sort_dir', 'desc');
		$dir = ($dir == 'asc') ? 'asc' : 'desc';

		switch ($sort)
		{
			case 'title':
				$sort_sql = 'title '.$dir;
			break;

			case 'page':
				$sort_sql = 'page '.$dir;
			break;

			case 'css':
				$sort_sql = 'css '.$dir;
			break;

			case 'in_rss':
				$sort_sql = 'in_rss '.$dir;
			break;

			case 'on_frontpage':
				$sort_sql = 'on_frontpage '.$dir;
			break;

			case 'searchable':
				$sort_sql = 'searchable '.$dir;
			break;

			case 'article_count':
				$sort_sql = 'article_count '.$dir;
			break;

			default:
				$sort_sql = 'name '.$dir;
			break;
		}

		set_pref('section_sort_column', $sort, 'section', 2, '', 0, PREF_PRIVATE);
		set_pref('section_sort_dir', $dir, 'section', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit != '')
		{
			$verbatim = preg_match('/^"(.*)"$/', $crit, $m);
			$crit_escaped = doSlash($verbatim ? $m[1] : str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));
			$critsql = $verbatim ?
				array(
					'name'         => "name = '$crit_escaped'",
					'title'        => "title = '$crit_escaped'",
					'page'         => "page = '$crit_escaped'",
					'css'          => "css = '$crit_escaped'",
					'in_rss'       => "in_rss = '$crit_escaped'",
					'on_frontpage' => "on_frontpage = '$crit_escaped'",
					'searchable'   => "searchable = '$crit_escaped'"
				) : array(
					'name'         => "name like '%$crit_escaped%'",
					'title'        => "title like '%$crit_escaped%'",
					'page'         => "page like '%$crit_escaped%'",
					'css'          => "css like '%$crit_escaped%'",
					'in_rss'       => "in_rss = '$crit_escaped'",
					'on_frontpage' => "on_frontpage = '$crit_escaped'",
					'searchable'   => "searchable = '$crit_escaped'"
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

		$criteria .= callback_event('admin_criteria', 'section_list', 0, $criteria);

		$total = safe_count('txp_section', "$criteria");

		echo '<h1 class="txp-heading">'.gTxt('tab_sections').sp.popHelp('section_category').'</h1>';
		echo '<div id="'.$event.'_control" class="txp-control-panel">';
		echo graf(
			sLink('section', 'section_edit', gTxt('create_section'))
			, ' class="txp-buttons"');

		echo n.'<form id="default_section_form" name="default_section_form" method="post" action="index.php" class="async">';
		echo graf(
				'<label>'.gTxt('default_write_section').'</label>'.sp.popHelp('section_default').n.section_select_list()
			).
			eInput('section').
			sInput('section_set_default');
		echo '</form>';

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.section_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
			}

			return;
		}

		$limit = max($section_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo n.section_search_form($crit, $search_method).'</div>';

		$rs = safe_rows_start('*, (SELECT count(*) FROM '.safe_pfx('textpattern').' articles WHERE articles.Section = txp_section.name) AS article_count', 'txp_section',
			"$criteria order by $sort_sql limit $offset, $limit" );

		if ($rs)
		{
			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo n.n.'<form action="index.php" id="section_form" class="multi_edit_form" method="post" name="longform">'.

				n.'<div class="txp-listtables">'.n.
				n.startTable('', '', 'txp-list').
				n.'<thead>'.
				n.tr(
					n.hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
					n.column_head('name', 'name', 'section', true, $switch_dir, $crit, $search_method, (('name' == $sort) ? "$dir " : '').'name').
					n.column_head('title', 'title', 'section', true, $switch_dir, $crit, $search_method, (('title' == $sort) ? "$dir " : '').'title').
					n.column_head('page', 'page', 'section', true, $switch_dir, $crit, $search_method, (('page' == $sort) ? "$dir " : '').'page').
					n.column_head('css', 'css', 'section', true, $switch_dir, $crit, $search_method, (('css' == $sort) ? "$dir " : '').'style').
					n.column_head('on_front_page', 'on_frontpage', 'section', true, $switch_dir, $crit, $search_method, (('on_frontpage' == $sort) ? "$dir " : '').'section_detail frontpage').
					n.column_head('syndicate', 'in_rss', 'section', true, $switch_dir, $crit, $search_method, (('in_rss' == $sort) ? "$dir " : '').'section_detail syndicate').
					n.column_head('include_in_search', 'searchable', 'section', true, $switch_dir, $crit, $search_method, (('searchable' == $sort) ? "$dir " : '').'section_detail searchable').
					n.column_head('articles', 'article_count', 'section', true, $switch_dir, $crit, $search_method, (('article_count' == $sort) ? "$dir " : '').'section_detail article_count')
			).
			n.'</thead>';

			echo '<tbody>';

			while ($a = nextRow($rs))
			{
				extract($a, EXTR_PREFIX_ALL, 'sec');

				$is_default_section = ($sec_name == 'default');

				$edit_url = '?event=section'.a.'step=section_edit'.a.'name='.$sec_name.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;
				$page_url = '?event=page'.a.'name='.$sec_page;
				$style_url = '?event=css'.a.'name='.$sec_css;
				$articles = ($sec_article_count > 0
					? href($sec_article_count, '?event=list'.a.'search_method=section'.a.'crit=&quot;'.txpspecialchars($sec_name).'&quot;',
						' title="'.gTxt('article_count', array('{num}' => $sec_article_count)).'"')
					: ($is_default_section ? '' : '0'));
//				$can_delete = ($sec_name != 'default' && $sec_article_count == 0);

				$parms = array(
					'step' => 'section_toggle_option',
					'thing' => $sec_name
				);
				echo tr(

					td(
						fInput('checkbox', 'selected[]', $sec_name)
					, '', 'multi-edit').

					td('<a href="'.$edit_url.'" title="'.gTxt('edit').'">'.$sec_name.'</a>' .n. '<span class="section_detail">[<a href="'.hu.$sec_name.'">'.gTxt('view').'</a>]</span>', '', 'name').
					td(txpspecialchars($sec_title), '', 'title').
					td('<a href="'.$page_url.'" title="'.gTxt('edit').'">'.$sec_page.'</a>', '', 'page').
					td('<a href="'.$style_url.'" title="'.gTxt('edit').'">'.$sec_css.'</a>', '', 'style').
					td($is_default_section ? '-' : asyncHref($sec_on_frontpage ? gTxt('yes') : gTxt('no'), $parms + array('property' => 'on_frontpage')), '', 'section_detail frontpage').
					td($is_default_section ? '-' : asyncHref($sec_in_rss ? gTxt('yes') : gTxt('no'), $parms + array('property' => 'in_rss')), '', 'section_detail syndicate').
					td($is_default_section ? '-' : asyncHref($sec_searchable ? gTxt('yes') : gTxt('no'), $parms + array('property' => 'searchable')), '', 'section_detail searchable').
					td($is_default_section ? '' : $articles, '', 'section_detail article_count')
				, ' id="txp_section_'.$sec_name.'"'
				);
			}

			echo '</tbody>',
				n, endTable(),
				n, '</div>',
				n, section_multiedit_form($page, $sort, $dir, $crit, $search_method),
				n, tInput(),
				n, '</form>',
				n, graf(
					toggle_box('section_detail'),
					' class="detail-toggle"'
				),
				n, '<div id="'.$event.'_navigation" class="txp-navigation">',
				n, nav_form('section', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit),
				n, pageby_form('section', $section_list_pageby),
				n, '</div>',
				n, '</div>';

			echo script_js( <<<EOS
			$('#default_section').change(function() {
				$('#default_section_form').submit();
			});
EOS
			);
		}
	}

//-------------------------------------------------------------
	function section_edit()
	{
		global $event, $step, $txp_user, $all_pages, $all_styles;

		$name = gps('name');

		$is_edit = ($name && $step == 'section_edit');

		if ($is_edit)
		{
			$name = assert_string($name);
			$rs = safe_row('*', 'txp_section', "name = '".doSlash($name)."'");
		} else {
			$rs = array_flip(getThings('describe `'.PFX.'txp_section`'));
		}

		if ($rs)
		{
			if (!has_privs('section.edit'))
			{
				sec_section_list(gTxt('restricted_area'));
				return;
			}

			pagetop(gTxt('tab_sections'));

			extract($rs, EXTR_PREFIX_ALL, 'sec');
			extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

			$is_default_section = ($is_edit && $sec_name == 'default');
			$caption = gTxt(($is_default_section) ? 'edit_default_section' : ($is_edit ? 'edit_section' : 'create_section'));

			if (!$is_edit)
			{
				// Pulling out the radio items from the default entry might seem pointless since they can't be directly
				// edited, but they will take on either:
				//  a) the default (SQL) values as defined at table creation time, or
				//  b) the values set when a multi-edit was performed that included the default section (because the values are silently updated then)
				$default = doSlash(safe_row('page, css, on_frontpage, in_rss, searchable', 'txp_section', "name = 'default'"));
				$sec_name = $sec_title = '';
				$sec_page = $default['page'];
				$sec_css = $default['css'];
				$sec_on_frontpage = $default['on_frontpage'];
				$sec_in_rss = $default['in_rss'];
				$sec_searchable = $default['searchable'];
			}

			echo '<div id="'.$event.'_container" class="txp-container">';
			echo form(

				'<div class="txp-edit">'.
				hed($caption, 2).

				(($is_default_section)
				? hInput('name', 'default')
				: inputLabel('section_name', fInput('text', 'name', $sec_name, '', '', '', INPUT_REGULAR, '', 'section_name'), 'section_name')
				).

				(($is_default_section)
				? ''
				: inputLabel('section_title', fInput('text', 'title', $sec_title, '', '', '', INPUT_REGULAR, '', 'section_title'), 'section_longtitle')
				).

				inputLabel('section_page', selectInput('section_page', $all_pages, $sec_page, '', '', 'section_page'), 'uses_page', 'section_uses_page').
				inputLabel('section_css', selectInput('css', $all_styles, $sec_css, '', '', 'section_css'), 'uses_style', 'section_uses_css').

				(($is_default_section)
				? ''
				: inputLabel('on_front_page', yesnoradio('on_frontpage', $sec_on_frontpage, '', $sec_name), '', 'section_on_frontpage')
				).

				(($is_default_section)
				? ''
				: inputLabel('syndicate', yesnoradio('in_rss', $sec_in_rss, '', $sec_name), '', 'section_syndicate')
				).

				(($is_default_section)
				? ''
				: inputLabel('include_in_search', yesnoradio('searchable', $sec_searchable, '', $sec_name), '', 'section_searchable')
				).

				pluggable_ui('section_ui', 'extend_detail_form', '', $rs).

				graf(
					fInput('submit', '', gTxt('save'), 'publish')
				).

				eInput('section').
				sInput('section_save').
				hInput('old_name', $sec_name).
				hInput('search_method', $search_method).
				hInput('crit', $crit).
				hInput('page', $page).
				hInput('sort', $sort).
				hInput('dir', $dir).
				'</div>'
			, '', '', 'post', 'edit-form', '', 'section_details');
			echo '</div>';
		}
	}

//-------------------------------------------------------------
	function section_save()
	{
		global $app_mode;

		$in = array_map('assert_string', psa(array('name', 'title', 'old_name', 'section_page', 'css')));
		if (empty($in['title']))
		{
			$in['title'] = $in['name'];
		}

		// Prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';

		$textile = new Textile();
		$in['title'] = $textile->TextileThis($in['title'],1);
		$in['name']  = strtolower(sanitizeForUrl($in['name']));

		extract($in);

		$in = doSlash($in);
		extract($in, EXTR_PREFIX_ALL, 'safe');

		if ($name != strtolower($old_name))
		{
			if (safe_field('name', 'txp_section', "name='$safe_name'"))
			{
				// Invalid input. Halt all further processing (e.g. plugin event handlers).
				$message = array(gTxt('section_name_already_exists', array('{name}' => $name)), E_ERROR);
//				modal_halt($message);
				sec_section_list($message);
				return;
			}
		}

		$ok = false;
		if ($name == 'default')
		{
			$ok = safe_update('txp_section', "page = '$safe_section_page', css = '$safe_css'", "name = 'default'");
		}
		else if ($name)
		{
			extract(array_map('assert_int', psa(array('on_frontpage','in_rss','searchable'))));

			if ($safe_old_name)
			{
				$ok = safe_update('txp_section', "
					name         = '$safe_name',
					title        = '$safe_title',
					page         = '$safe_section_page',
					css          = '$safe_css',
					on_frontpage = $on_frontpage,
					in_rss       = $in_rss,
					searchable   = $searchable
					", "name = '$safe_old_name'");

				// Manually maintain referential integrity
				if ($ok)
				{
					$ok = safe_update('textpattern', "Section = '$safe_name'", "Section = '$safe_old_name'");
				}
			}
			else
			{
				$ok = safe_insert('txp_section', "
					name         = '$safe_name',
					title        = '$safe_title',
					page         = '$safe_section_page',
					css          = '$safe_css',
					on_frontpage = $on_frontpage,
					in_rss       = $in_rss,
					searchable   = $searchable");
			}
		}

		if ($ok)
		{
			update_lastmod();
		}

		if ($ok)
		{
			sec_section_list(gTxt(($safe_old_name ? 'section_updated': 'section_created'), array('{name}' => $name)));
		}
		else
		{
			sec_section_list(array(gTxt('section_save_failed'), E_ERROR));
		}
	}

// -------------------------------------------------------------
	function section_change_pageby()
	{
		event_change_pageby('section');
		sec_section_list();
	}

/**
 * Toggle section yes/no parameters.
 *
 * Requires:
 * ($_POST) $column	string	Database column name to alter: on_frontpage | in_rss | searchable
 * ($_POST) $value	string	The current Yes/No value of the control
 * ($_POST) $name	string	Section name to be altered
 * @return  string a text/plain response comprising the new displayable value for the toggled parameter
 */
	function section_toggle_option()
	{
		$column = gps('property');
		$value = gps('value');
		$name = gps('thing');
		$newval = ($value == gTxt('yes')) ? '0' : '1';
		$ret = false;
		if (in_array($column, array('on_frontpage', 'in_rss', 'searchable')))
		{
			$ret = safe_update('txp_section', "$column='$newval'", "name='".doSlash($name)."'");
		}

		if ($ret)
		{
			// TODO: Remove non-AJAX alternative code path in future version
			if (!AJAXALLY_CHALLENGED) {
				echo gTxt($newval ? 'yes' : 'no');
			} else {
				sec_section_list(gTxt('section_updated', array('{name}' => $name)));
			}
		}
		else
		{
			trigger_error(gTxt('section_save_failed'), E_USER_ERROR);
		}
	}

// -------------------------------------------------------------
	function section_set_default()
	{
		set_pref('default_section', ps('default_section'), 'section', PREF_HIDDEN, '', 0);
		send_script_response();
	}

//-------------------------------------------------------------
	function section_select_list()
	{
		$val = get_pref('default_section');
		$sections = safe_rows('name, title', 'txp_section', "name != 'default' ORDER BY name");
		$vals = array();
		foreach($sections as $row)
		{
			$vals[$row['name']] = $row['title'];
		}

		return selectInput('default_section', $vals, $val, '', '', 'default_section');
	}

// -------------------------------------------------------------
	function section_delete()
	{
		$selected  = ps('selected');
		$with_articles = safe_rows('Section, Count(*) AS count', 'textpattern', "Section in ('".join("','", doSlash($selected))."') GROUP BY Section");
		$protected[] = 'default';
		$del['success'] = $del['error'] = array();

		foreach ($with_articles as $row)
		{
			$protected[] = $row['Section'];
		}
		$protected = array_unique($protected);

		foreach ($selected as $item) {
			if (in_array($item, $protected))
			{
				$del['error'][] = $item;
			}

			else
			{
				$ret = safe_delete('txp_section', "name = '".doSlash($item)."'");
				if ($ret)
				{
					$del['success'][] = $item;
				}

				else
				{
					$del['error'][] = $item;
				}
			}
		}

		if ($del['success'])
		{
			callback_event('sections_deleted', '', 0, $del['success']);
		}

		$message = ($del['success']) ? gTxt('section_deleted', array('{name}' => join(', ', $del['success']))) : '';

		sec_section_list($message);
	}

// -------------------------------------------------------------

	function section_search_form($crit, $method)
	{
		$methods =	array(
			'name'         => gTxt('name'),
			'title'        => gTxt('title'),
			'page'         => gTxt('page'),
			'css'          => gTxt('css'),
			'on_frontpage' => gTxt('on_front_page'),
			'in_rss'       => gTxt('syndicate'),
			'searchable'   => gTxt('include_in_search')
		);

		return search_form('section', 'sec_section_list', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------

	function section_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		global $all_pages, $all_styles;

		$methods = array(
			'changepage'        => array('label' => gTxt('uses_page'), 'html' => selectInput('uses_page', $all_pages, '', false)),
			'changecss'         => array('label' => gTxt('uses_style'), 'html' => selectInput('css', $all_styles, '', false)),
			'changeonfrontpage' => array('label' => gTxt('on_front_page'), 'html' => yesnoRadio('on_frontpage', 1)),
			'changesyndicate'   => array('label' => gTxt('syndicate'), 'html' => yesnoRadio('in_rss', 1)),
			'changesearchable'  => array('label' => gTxt('include_in_search'), 'html' => yesnoRadio('searchable', 1)),
			'delete'            => gTxt('delete'),
		);

		return multi_edit($methods, 'section', 'section_multi_edit', $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function section_multi_edit()
	{
		global $txp_user, $all_pages, $all_styles;
		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return sec_section_list();
		}

		$method   = ps('edit_method');
		$changed  = array();
		$key = $msg = '';

		switch ($method)
		{
			case 'delete':
				return section_delete($selected);
				break;

			case 'changepage':
				$val = ps('uses_page');
				if (in_array($val, $all_pages))
				{
					$key = 'page';
				}
				break;

			case 'changecss':
				$val = ps('css');
				if (in_array($val, $all_styles))
				{
					$key = 'css';
				}
				break;

			case 'changeonfrontpage':
				$key = 'on_frontpage';
				$val = (int) ps('on_frontpage');
				break;

			case 'changesyndicate':
				$key = 'in_rss';
				$val = (int) ps('in_rss');
				break;

			case 'changesearchable':
				$key = 'searchable';
				$val = (int) ps('searchable');
				break;

			default:
				$key = '';
				$val = '';
				break;
		}

		$selected = safe_column('name', 'txp_section', "name IN ('".join("','", doSlash($selected))."')");

		if ($selected and $key)
		{
			foreach ($selected as $id)
			{
				if (safe_update('txp_section', "$key = '".doSlash($val)."'", "name = '".doSlash($id)."'"))
				{
					$changed[] = $id;
				}
			}
			$msg = gTxt('section_updated', array('{name}' => join(', ', $changed)));
		}

		return sec_section_list($msg);
	}

?>

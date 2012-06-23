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

		$available_steps = array(
			'section_change_pageby' => true,
			'sec_section_list'      => false,
			'section_create'        => true,
			'section_delete'        => true,
			'section_save'          => true,
			'section_edit'          => true,
			'section_multi_edit'    => true,
			'section_set_default'   => true,
			'section_toggle_option' => true,
		);

		if (!$step or !bouncer($step, $available_steps)){
			$step ='sec_section_list';
		}
		$step();
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

		gTxtScript(array('yes', 'no'));

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
			$crit_escaped = doSlash(str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));

			$critsql = array(
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

		$total = safe_count('txp_section', "$criteria");

		echo '<h1>' . gTxt('tab_sections') . '</h1>';
		echo '<div id="'.$event.'_control" class="txp-control-panel">';
		echo n.form(
			graf(
				'<label>'.gTxt('create_section').'</label>'.sp.popHelp('section_category').n.
				fInput('text', 'name', '', '', '', '', 32).n.
				fInput('submit', '', gTxt('create')).
				eInput('section').
				sInput('section_create')
			)
			, '', '', 'post', 'edit-form', '', 'section_create');
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

		$rs = safe_rows_start('*', 'txp_section',
			"$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo n.n.'<form action="index.php" id="section_form" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

				n.'<div class="txp-listtables">'.n.
				n.startTable('', '', 'txp-list').
				n.'<thead>'.
				n.tr(
					n.hCell(fInput('checkbox', 'selected_toggle', 0, '', '', '', '', '', 'selected_toggle'), '', ' title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
					n.column_head('name', 'name', 'section', true, $switch_dir, $crit, $search_method, (('name' == $sort) ? "$dir " : '').'name').
					n.column_head('title', 'title', 'section', true, $switch_dir, $crit, $search_method, (('title' == $sort) ? "$dir " : '').'name').
					n.column_head('page', 'page', 'section', true, $switch_dir, $crit, $search_method, (('page' == $sort) ? "$dir " : '').'page').
					n.column_head('css', 'css', 'section', true, $switch_dir, $crit, $search_method, (('css' == $sort) ? "$dir " : '').'style').
					n.column_head('on_front_page', 'on_frontpage', 'section', true, $switch_dir, $crit, $search_method, (('on_frontpage' == $sort) ? "$dir " : '').'section_detail frontpage').
					n.column_head('syndicate', 'in_rss', 'section', true, $switch_dir, $crit, $search_method, (('in_rss' == $sort) ? "$dir " : '').'section_detail syndicate').
					n.column_head('include_in_search', 'searchable', 'section', true, $switch_dir, $crit, $search_method, (('searchable' == $sort) ? "$dir " : '').'section_detail searchable')
			).
			n.'</thead>';

			echo '<tbody>';

			while ($a = nextRow($rs))
			{
				extract($a, EXTR_PREFIX_ALL, 'sec');

				$edit_url = '?event=section'.a.'step=section_edit'.a.'name='.$sec_name.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;
				$page_url = '?event=page'.a.'name='.$sec_page;
				$style_url = '?event=css'.a.'name='.$sec_css;
				$article_count = safe_count('textpattern', "Section = '".doSlash($sec_name)."'");
//				$can_delete = ($sec_name == 'default' || $article_count > 0) ? false : true;
				$is_default_section = ($sec_name == 'default');

				echo tr(

					td(
						fInput('checkbox', 'selected[]', $sec_name)
					, '', 'multi-edit').

					td('<a href="'.$edit_url.'" title="'.gTxt('edit').'">'.$sec_name.'</a>' .n. '<span class="section_detail">[<a href="'.hu.$sec_name.'">'.gTxt('view').'</a>]</span>', '', 'name').
					td(txpspecialchars($sec_title), '', 'name').
					td(
						'<a href="'.$page_url.'" title="'.gTxt('edit').'">'.$sec_page.'</a>'.n.
						( ($article_count > 0) ? '<a title="'.gTxt('article_count', array('{num}' => $article_count)).'" href="?event=list'.a.'step=list'.a.'search=Go'.a.'search_method=section'.a.'crit='.htmlspecialchars($sec_name).'">('.$article_count.')</a>' : ($is_default_section ? '' : '(0)') )
					, '', 'page').

					td('<a href="'.$style_url.'" title="'.gTxt('edit').'">'.$sec_css.'</a>', '', 'style').
					td($is_default_section ? '-' : '<a href="#" id="txp_column_'.$sec_name.'_on_frontpage" class="section_toggle_option">'.($sec_on_frontpage ? gTxt('yes') : gTxt('no')).'</a>', '', 'section_detail frontpage').
					td($is_default_section ? '-' : '<a href="#" id="txp_column_'.$sec_name.'_in_rss" class="section_toggle_option">'.($sec_in_rss ? gTxt('yes') : gTxt('no')).'</a>', '', 'section_detail syndicate').
					td($is_default_section ? '-' : '<a href="#" id="txp_column_'.$sec_name.'_searchable" class="section_toggle_option">'.($sec_searchable ? gTxt('yes') : gTxt('no')).'</a>', '', 'section_detail searchable')
				, ' id="txp_section_'.$sec_name.'"'
				);
			}

			echo '</tbody>'.
			n.endTable().

			n.graf(
				select_buttons().n.
				section_multiedit_form($page, $sort, $dir, $crit, $search_method)
			, ' class="multi-edit"').

			n.'</div>'.
			n.tInput().
			n.'</form>'.

			n.graf(
				toggle_box('section_detail'),
				' class="detail-toggle"'
			).

			n.'<div id="'.$event.'_navigation" class="txp-navigation">'.
			n.nav_form('section', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).

			n.pageby_form('section', $section_list_pageby).
			n.'</div>'.n.'</div>';

			// Come the HTML 5 revolution, we can use data-txp-column="on_frontpage" in the anchors and use var col = obj.data('txp-column') instead of abusing IDs
			echo script_js( <<<EOS
			$('a.section_toggle_option').click(function(ev) {
				ev.preventDefault();
				var obj = $(this);
				var secname = obj.parent().parent().attr('id').replace('txp_section_', '');
				var col = obj.attr('id').replace('txp_column_'+secname+'_', '');
				var val = obj.text();
				sendAsyncEvent(
				{
					event: textpattern.event,
					step: 'section_toggle_option',
					name: secname,
					column: col,
					value: val
				}, function(data) {
					var newval = $(data).find('section_toggle_val').attr('value');
					obj.gTxt(newval);
				});
			});

			$('#default_section').change(function() {
				var form = $('#default_section_form').submit();
			});
EOS
			);
		}
	}

//-------------------------------------------------------------
	function section_edit()
	{
		global $event, $step, $txp_user;

		$name = gps('name');
		$name = assert_string($name);

		$is_default_section = ($name == 'default');

		$rs = safe_row('*', 'txp_section', "name = '".doSlash($name)."'");

		if ($rs)
		{
			extract($rs, EXTR_PREFIX_ALL, 'sec');

			if (!has_privs('section.edit'))
			{
				sec_section_list(gTxt('restricted_area'));
				return;
			}

			pagetop(gTxt('tab_sections'));

			extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

			echo '<div id="'.$event.'_container" class="txp-container">';

			$caption = ($is_default_section) ? gTxt('edit_default_section') : gTxt('edit_section');
			$pages = safe_column('name', 'txp_page', "1=1");
			$styles = safe_column('name', 'txp_css', "1=1");

			echo form(

				'<div class="txp-edit">'.
				hed($caption, 2).

				(($is_default_section)
				? hInput('section_name', 'default')
				: inputLabel('section_name', fInput('text', 'section_name', $sec_name, '', '', '', INPUT_REGULAR, '', 'section_name'), 'section_name')
				).

				(($is_default_section)
				? ''
				: inputLabel('section_title', fInput('text', 'section_title', $sec_title, '', '', '', INPUT_REGULAR, '', 'section_title'), 'section_longtitle')
				).

				inputLabel('section_page', selectInput('section_page', $pages, $sec_page, '', '', 'section_page'), 'uses_page', 'section_uses_page').
				inputLabel('section_css', selectInput('section_css', $styles, $sec_css, '', '', 'section_css'), 'uses_style', 'section_uses_css').

				(($is_default_section)
				? ''
				: inputLabel('on_front_page', yesnoradio('on_front_page', $sec_on_frontpage, '', $sec_name), '', 'section_on_frontpage')
				).

				(($is_default_section)
				? ''
				: inputLabel('syndicate', yesnoradio('syndicate', $sec_in_rss, '', $sec_name), '', 'section_syndicate')
				).

				(($is_default_section)
				? ''
				: inputLabel('include_in_search', yesnoradio('include_in_search', $sec_searchable, '', $sec_name), '', 'section_searchable')
				).

				pluggable_ui('section_ui', 'extend_detail_form', '', $rs).

				graf(
					fInput('submit', '', gTxt('save'), 'publish')
				).

				'</div>'.

				eInput('section').
				sInput('section_save').
				hInput('old_name', $sec_name).
				hInput('search_method', $search_method).
				hInput('crit', $crit).
				hInput('page', $page).
				hInput('sort', $sort).
				hInput('dir', $dir)
			, '', '', 'post', 'edit-form', '', 'section_details');
			echo '</div>';
		}
	}

//-------------------------------------------------------------
	function section_create()
	{
		$name = assert_string(ps('name'));

		//Prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile();
		$title = $textile->TextileThis($name,1);
		$name = strtolower(sanitizeForUrl($name));

		$chk = fetch('name','txp_section','name',$name);

		if (!$chk)
		{
			if ($name)
			{
				$default = doSlash(safe_row('page, css', 'txp_section', "name = 'default'"));

				$rs = safe_insert(
				   "txp_section",
				   "name         = '".doSlash($name) ."',
					title        = '".doSlash($title)."',
					page         = '".$default['page']."',
					css          = '".$default['css']."',
					in_rss       = 1,
					on_frontpage = 1"
				);

				if ($rs)
				{
					update_lastmod();

					$message = gTxt('section_created', array('{name}' => $name));

					sec_section_list($message);
				}
				else
				{
					sec_section_list(array(gTxt('section_save_failed'), E_ERROR));
				}
			}

			else
			{
				sec_section_list();
			}
		}

		else
		{
			$message = array(gTxt('section_name_already_exists', array('{name}' => $name)), E_ERROR);

			sec_section_list($message);
		}
	}

//-------------------------------------------------------------
	function section_save()
	{
		global $app_mode;

		$in = array_map('assert_string', psa(array('section_name', 'section_title', 'old_name', 'section_page', 'section_css')));
		if (empty($in['section_title']))
		{
			$in['section_title'] = $in['section_name'];
		}

		// Prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';

		$textile = new Textile();
		$in['section_title'] = $textile->TextileThis($in['section_title'],1);
		$in['section_name']  = sanitizeForUrl($in['section_name']);

		extract($in);

		$in = doSlash($in);
		extract($in, EXTR_PREFIX_ALL, 'safe');

		if (strtolower($section_name) != strtolower($old_name))
		{
			if (safe_field('name', 'txp_section', "name='$safe_section_name'"))
			{
				// Invalid input. Halt all further processing (e.g. plugin event handlers).
				$message = array(gTxt('section_name_already_exists', array('{name}' => $section_name)), E_ERROR);
//				modal_halt($message);
				sec_section_list($message);
				return;
			}
		}

		$ok = true;
		if ($section_name == 'default')
		{
			$ok = safe_update('txp_section', "page = '$safe_section_page', css = '$safe_section_css'", "name = 'default'");
		}
		else
		{
			extract(array_map('assert_int', psa(array('on_front_page','syndicate','include_in_search'))));

			if ($ok )
			{
				$ok = safe_update('txp_section', "
					name         = '$safe_section_name',
					title        = '$safe_section_title',
					page         = '$safe_section_page',
					css          = '$safe_section_css',
					on_frontpage = $on_front_page,
					in_rss       = $syndicate,
					searchable   = $include_in_search
					", "name = '$safe_old_name'");
			}

			// Manually maintain referential integrity
			if ($ok)
			{
				$ok = safe_update('textpattern', "Section = '$safe_section_name'", "Section = '$safe_old_name'");
			}
		}

		if ($ok)
		{
			update_lastmod();
		}

		if ($ok) {
			sec_section_list(gTxt('section_updated', array('{name}' => $section_name)));
		} else {
			sec_section_list(array(gTxt('section_save_failed'), E_ERROR));
		}

/*
		if (!AJAXALLY_CHALLENGED) {
			if ($ok) {
				global $theme;
				// Keep old name around to mangle existing HTML
				$on = $old_name;
				// Old became new as we have saved this section
				$old_name = $name;

				$s = compact('name', 'old_name', 'title', 'page', 'css', 'on_frontpage', 'in_rss', 'searchable');
				$form = section_detail_partial($s);

				$s = doSpecial($s);
				extract($s);

				// Update form with current data
				$response[] = '$("#section-form-'.$on.'").html("'.escape_js($form).'")';
				// Reflect new section name on id and row label
				$label = ($name == 'default' ? gTxt('default') : $name);
				$response[] = '$("tr#section-'.$on.'").attr("id", "section-'.$name.'").find(".label").html("'.$label.'")';
				$response[] = $theme->announce_async(gTxt('section_updated', array('{name}' => $name)));
			} else {
				$response[] =  $theme->announce_async(array(gTxt('section_save_failed'), E_ERROR));
			}
			send_script_response(join(";\n", $response));
		} else {
			// TODO: Deprecate non-AJAX alternative code path in future version
			if ($ok) {
				sec_section_list(gTxt('section_updated', array('{name}' => $name)));
			} else {
				sec_section_list(array(gTxt('section_save_failed'), E_ERROR));
			}
		}
*/

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
 * Returns an XML response comprising the new displayable value for the toggled parameter
 */
	function section_toggle_option()
	{
		$column = ps('column');
		$value = strtolower(ps('value'));
		$name = ps('name');
		$newval = ($value == strtolower(gTxt('yes'))) ? '0' : '1';
		$ret = false;
		switch($column)
		{
			case 'on_frontpage':
				$ret = safe_update('txp_section', "on_frontpage='".$newval."'", "name='".doSlash($name)."'");
			break;
			case 'in_rss':
				$ret = safe_update('txp_section', "in_rss='".$newval."'", "name='".doSlash($name)."'");
			break;
			case 'searchable':
				$ret = safe_update('txp_section', "searchable='".$newval."'", "name='".doSlash($name)."'");
			break;
		}

		if ($ret)
		{
			// Send gTxt strings which are translated client side
			send_xml_response(array('section_toggle_val' => ($newval == '1' ? 'yes' : 'no')));
		}
		exit;
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
		$with_articles = safe_rows('Section, Count(*) AS count', 'textpattern', "Section in ('".join("','", $selected)."') GROUP BY Section");
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
		$methods = array(
			'changepage'        => gTxt('uses_page'),
			'changecss'         => gTxt('uses_style'),
			'changeonfrontpage' => gTxt('on_front_page'),
			'changesyndicate'   => gTxt('syndicate'),
			'changesearchable'  => gTxt('include_in_search'),
			'delete'            => gTxt('delete'),
		);

		return event_multiedit_form('section', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function section_multi_edit()
	{
		global $txp_user;
		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return sec_section_list();
		}

		$method   = ps('edit_method');
		$changed  = array();

		switch ($method)
		{
			case 'delete';
				return section_delete($selected);
				break;

			case 'changepage':
				$key = 'page';
				$val = ps('page');
				break;

			case 'changecss';
				$key = 'css';
				$val = ps('css');
				break;

			case 'changeonfrontpage';
				$key = 'on_frontpage';
				$val = ps('on_front_page');
				break;

			case 'changesyndicate';
				$key = 'in_rss';
				$val = ps('in_rss');
				break;

			case 'changesearchable';
				$key = 'searchable';
				$val = ps('searchable');
				break;

			default:
				$key = '';
				$val = '';
				break;
		}

		$selected = safe_column('name', 'txp_section', "name IN ('".join("','", $selected)."')");

		if ($selected and $key)
		{
			foreach ($selected as $id)
			{
				if (safe_update('txp_section', "$key = '".doSlash($val)."'", "name = '$id'"))
				{
					$changed[] = $id;
				}
			}
		}

		return sec_section_list(gTxt('section_updated', array('{name}' => join(', ', $changed))));
	}

// -------------------------------------------------------------
// TODO: Is this needed any more?
	function section_detail_partial($thesection)
	{
		static $pages, $styles;
		if (empty($pages)) {
			$pages = safe_column('name', 'txp_page', "1 = 1");
			$styles = safe_column('name', 'txp_css', "1 = 1");
		}

		extract($thesection);

		$default_section = ($name == 'default');

		return '<table>'.

			($default_section ? '' : n.n.tr(
				fLabelCell(gTxt('section_name').':').
				fInputCell('name', $name, '', 32)
			, ' class="name"')).

			($default_section ? '' : n.n.tr(
				fLabelCell(gTxt('section_longtitle').':').
				fInputCell('title', $title, '', 32)
			, ' class="title"')).

			n.n.tr(
				fLabelCell(gTxt('uses_page').':').
				td(
					selectInput('page', $pages, $page).sp.popHelp('section_uses_page')
				)
			, ' class="uses-page"').

			n.n.tr(
				fLabelCell(gTxt('uses_style').':').
				td(
					selectInput('css', $styles, $css).sp.popHelp('section_uses_css')
				)
			, ' class="uses-style"').

			($default_section ? '' : n.n.tr(
				fLabelCell(gTxt('on_front_page')).
				td(
					yesnoradio('on_frontpage', $on_frontpage, '', $name).sp.popHelp('section_on_frontpage')
				)
			, ' class="option on-frontpage"')).

			($default_section ? '' : n.n.tr(
				fLabelCell(gTxt('syndicate')) .
				td(
					yesnoradio('in_rss', $in_rss, '', $name).sp.popHelp('section_syndicate')
				)
			, ' class="option in-rss"')).

			($default_section ? '' : n.n.tr(
				fLabelCell(gTxt('include_in_search')).
				td(
					yesnoradio('searchable', $searchable, '', $name).sp.popHelp('section_searchable')
				)
			, ' class="option is-searchable"')).

			pluggable_ui('section_ui', 'extend_detail_form', '', $thesection).

			n.n.tr(
				tda(
					fInput('submit', '', gTxt('save'), 'publish').
					eInput('section').
					sInput('section_save').
					($default_section ? hInput('name', $name) : '').
					hInput('old_name', $old_name)
				, ' colspan="2"')
			).

			endTable();
	}
?>

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
			'sec_section_list' 	=> false,
			'section_create' 	=> true,
			'section_delete' 	=> true,
			'section_save' 		=> true
		);

		if (!$step or !bouncer($step, $available_steps)){
			$step ='sec_section_list';
		}
		$step();
	}

// -------------------------------------------------------------

	function sec_section_list($message = '')
	{
		// TODO: what is $wlink? Remove it?
		global $wlink, $event;

		pagetop(gTxt('tab_sections'), $message);

		$default = safe_row('page, css, name', 'txp_section', "name = 'default'");
		$default['old_name'] = 'default';

		echo '<h1 class="txp-heading">'.gTxt('tab_sections').'</h1>';
		echo n.'<div id="'.$event.'_container" class="txp-container">';
		echo n.n.startTable('', '', 'txp-columntable').

			n.n.tr(
				tda(
					n.'<div id="'.$event.'_control" class="txp-control-panel">'.

					n.n.form(
						fInput('text', 'name', '', '', '', '', 10).n.
						fInput('submit', '', gTxt('create')).sp.popHelp('section_category').
						eInput('section').
						sInput('section_create')
					, '', '', 'post', 'edit-form', '', 'section_create').
					n.'</div>'
				, ' colspan="3"')
			).

			n.n.tr(
				td(gTxt('default'), '', 'label').n.
				td(form(section_detail_partial($default), '', '', 'post', 'async', 'section-default', 'section-form-default')).n.
				td()
			, ' id="section-default" class="section default"');

		$rs = safe_rows_start('*', 'txp_section', "name != 'default' order by name");

		if ($rs)
		{
			$ctr = 1;

			while ($a = nextRow($rs))
			{
				extract($a);
				$a['old_name'] = $name;

				echo n.n.tr(
					n.td($name, '', 'label').
					n.td(form(section_detail_partial($a), '', '', 'post', 'async', 'section-'.$name, 'section-form-'.$name), '', 'main').
					td(
						dLink('section', 'section_delete', 'name', $name, '', 'type', 'section')
					, '', 'actions')
				,' id="section-'.$name.'" class="section '.(($ctr%2 == 0) ? 'even' : 'odd').'"');

				$ctr++;
			}
		}

		echo n.n.endTable().'</div>';
	}

//-------------------------------------------------------------
	function section_create()
	{
		global $txpcfg;
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
					is_default   = 0,
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
		global $txpcfg, $app_mode;

		$in = array_map('assert_string', psa(array('name', 'title', 'old_name', 'page', 'css')));
		if (empty($in['title']))
		{
			$in['title'] = $in['name'];
		}

		// Prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';

		$textile = new Textile();
		$in['title'] = $textile->TextileThis($in['title'],1);
		$in['name']  = sanitizeForUrl($in['name']);

		extract($in);

		$in = doSlash($in);
		extract($in, EXTR_PREFIX_ALL, 'safe');

		if (strtolower($name) != strtolower($old_name))
		{
			if (safe_field('name', 'txp_section', "name='$safe_name'"))
			{
				// Invalid input. Halt all further processing (e.g. plugin event handlers).
				$message = array(gTxt('section_name_already_exists', array('{name}' => $name)), E_ERROR);
				modal_halt($message);
				// TODO: Deprecate non-AJAX alternative code path in next version
				sec_section_list($message);
				return;
			}
		}

		$ok = true;
		if ($name == 'default')
		{
			$ok = safe_update('txp_section', "page = '$safe_page', css = '$safe_css'", "name = 'default'");
		}
		else
		{
			extract(array_map('assert_int', psa(array('is_default','on_frontpage','in_rss','searchable'))));
			// note this means 'selected by default' not 'default page'
			if ($is_default)
			{
				$ok = safe_update("txp_section", "is_default = 0", "name != '$safe_old_name'");
				// switch off $is_default for all sections in async app_mode
				if (!AJAXALLY_CHALLENGED) {
					$response[] =  '$("input[name=\"is_default\"][value=\"1\"]").attr("checked", false);'.
								'$("input[name=\"is_default\"][value=\"0\"]").attr("checked", true);';
				}
			}

			if ($ok )
			{
				$ok = safe_update('txp_section', "
					name         = '$safe_name',
					title        = '$safe_title',
					page         = '$safe_page',
					css          = '$safe_css',
					is_default   = $is_default,
					on_frontpage = $on_frontpage,
					in_rss       = $in_rss,
					searchable   = $searchable
					", "name = '$safe_old_name'");
			}

			if ($ok)
			{
				$ok = safe_update('textpattern', "Section = '$safe_name'", "Section = '$safe_old_name'");
			}
		}

		if ($ok)
		{
			update_lastmod();
		}

		if (!AJAXALLY_CHALLENGED) {
			if ($ok) {
				global $theme;
				// Keep old name around to mangle existing HTML
				$on = $old_name;
				// Old became new as we have saved this section
				$old_name = $name;

				$s = compact('name', 'old_name', 'title', 'page', 'css', 'is_default', 'on_frontpage', 'in_rss', 'searchable');
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
	}

// -------------------------------------------------------------

	function section_delete()
	{
		$name  = ps('name');
		$count = safe_count('textpattern', "section = '".doSlash($name)."'");

		if ($count)
		{
			$message = array(gTxt('section_used_by_article', array('{name}' => $name, '{count}' => $count)), E_ERROR);
		}

		else
		{
			safe_delete('txp_section', "name = '".doSlash($name)."'");

			$message = gTxt('section_deleted', array('{name}' => $name));
		}

		sec_section_list($message);
	}

// -------------------------------------------------------------

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
				fInputCell('name', $name, '', 20)
			, ' class="name"')).

			($default_section ? '' : n.n.tr(
				fLabelCell(gTxt('section_longtitle').':').
				fInputCell('title', $title, '', 20)
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
				fLabelCell(gTxt('selected_by_default')).
				td(
					yesnoradio('is_default', $is_default, '', $name).sp.popHelp('section_is_default')
				)
			, ' class="option is-default"')).

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

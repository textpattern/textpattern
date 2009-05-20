<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance ofthe Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'section') {
		require_privs('section');

		if(!$step or !in_array($step, array('sec_section_list','section_create','section_delete','section_save'))){
			$step ='sec_section_list';
		}
		$step();
	}

// -------------------------------------------------------------

	function sec_section_list($message = '')
	{
		global $wlink;

		pagetop(gTxt('sections'), $message);

		$default = safe_row('page, css', 'txp_section', "name = 'default'");

		$pages = safe_column('name', 'txp_page', "1 = 1");
		$styles = safe_column('name', 'txp_css', "1 = 1");

		echo n.n.startTable('list').

			n.n.tr(
				tda(
					n.n.hed(gTxt('section_head').sp.popHelp('section_category'), 1).

					n.n.form(
						fInput('text', 'name', '', 'edit', '', '', 10).
						fInput('submit', '', gTxt('create'), 'smallerbox').
						eInput('section').
						sInput('section_create')
					)
				, ' colspan="3"')
			).

			n.n.tr(
				td(gTxt('default')).

				td(
					form(
						'<table>'.

						tr(
							fLabelCell(gTxt('uses_page').':').
							td(
								selectInput('page', $pages, $default['page']).sp.popHelp('section_uses_page')
							, '', 'noline')
						).

						tr(
							fLabelCell(gTxt('uses_style').':') .
							td(
								selectInput('css', $styles, $default['css']).sp.popHelp('section_uses_css')
							, '', 'noline')
						).

						pluggable_ui('section_ui', 'extend_detail_form', '', $default).

						tr(
							tda(
								fInput('submit', '', gTxt('save_button'), 'smallerbox').
								eInput('section').
								sInput('section_save').
								hInput('name','default')
							, ' colspan="2" class="noline"')
						).

						endTable()
					)
				).

				td()
			);

		$rs = safe_rows_start('*', 'txp_section', "name != 'default' order by name");

		if ($rs)
		{
			while ($a = nextRow($rs))
			{
				extract($a);

				echo n.n.tr(
					n.td($name).

					n.td(
						form(
							'<table>'.

							n.n.tr(
								fLabelCell(gTxt('section_name').':').
								fInputCell('name', $name, 1, 20)
							).

							n.n.tr(
								fLabelCell(gTxt('section_longtitle').':').
								fInputCell('title', $title, 1, 20)
							).

							n.n.tr(
								fLabelCell(gTxt('uses_page').':').
								td(
									selectInput('page', $pages, $page).sp.popHelp('section_uses_page')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('uses_style').':').
								td(
									selectInput('css', $styles, $css).sp.popHelp('section_uses_css')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('selected_by_default')).
								td(
									yesnoradio('is_default', $is_default, '', $name).sp.popHelp('section_is_default')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('on_front_page')).
								td(
									yesnoradio('on_frontpage', $on_frontpage, '', $name).sp.popHelp('section_on_frontpage')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('syndicate')) .
								td(
									yesnoradio('in_rss', $in_rss, '', $name).sp.popHelp('section_syndicate')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('include_in_search')).
								td(
									yesnoradio('searchable', $searchable, '', $name).sp.popHelp('section_searchable')
								, '', 'noline')
							).

							pluggable_ui('section_ui', 'extend_detail_form', '', $a).

							n.n.tr(
								tda(
									fInput('submit', '', gTxt('save_button'), 'smallerbox').
									eInput('section').
									sInput('section_save').
									hInput('old_name', $name)
								, ' colspan="2" class="noline"')
							).

							endTable(),
							'', '', 'post', '', 'section-'.$name
						)
					).

					td(
						dLink('section', 'section_delete', 'name', $name, '', 'type', 'section')
					),
					" id=\"section-$name\""
				);
			}
		}

		echo n.n.endTable();
	}

//-------------------------------------------------------------
	function section_create()
	{
		global $txpcfg;
		$name = ps('name');

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
				$rs = safe_insert(
				   "txp_section",
				   "name         = '".doSlash($name) ."',
					title        = '".doSlash($title)."',
					page         = 'default',
					css          = 'default',
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
		global $txpcfg;

		extract(doSlash(psa(array('page','css','old_name'))));
		extract(psa(array('name', 'title')));

		if (empty($title))
		{
			$title = $name;
		}

		// Prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';

		$textile = new Textile();
		$title = doSlash($textile->TextileThis($title,1));
		$name  = doSlash(sanitizeForUrl($name));

		if ($old_name && (strtolower($name) != strtolower($old_name)))
		{
			if (safe_field('name', 'txp_section', "name='$name'"))
			{
				$message = array(gTxt('section_name_already_exists', array('{name}' => $name)), E_ERROR);

				sec_section_list($message);
				return;
			}
		}

		if ($name == 'default')
		{
			safe_update('txp_section', "page = '$page', css = '$css'", "name = 'default'");

			update_lastmod();
		}

		else
		{
			extract(array_map('assert_int',psa(array('is_default','on_frontpage','in_rss','searchable'))));
			// note this means 'selected by default' not 'default page'
			if ($is_default)
			{
				safe_update("txp_section", "is_default = 0", "name != '$old_name'");
			}

			safe_update('txp_section', "
				name         = '$name',
				title        = '$title',
				page         = '$page',
				css          = '$css',
				is_default   = $is_default,
				on_frontpage = $on_frontpage,
				in_rss       = $in_rss,
				searchable   = $searchable
			", "name = '$old_name'");

			safe_update('textpattern', "Section = '$name'", "Section = '$old_name'");

			update_lastmod();
		}

		$message = gTxt('section_updated', array('{name}' => $name));

		sec_section_list($message);
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

?>

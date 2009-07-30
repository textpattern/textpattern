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

	if ($event == 'page') {
		require_privs('page');

		if(!$step or !in_array($step, array('page_edit','page_save','page_delete','page_list'))){
			$step = 'page_edit';
		}
		$step();
	}

//-------------------------------------------------------------

	function page_edit($message = '') {
		global $step;

		pagetop(gTxt('edit_pages'), $message);

		extract(gpsa(array('name', 'newname', 'copy')));

		if (!$name or $step == 'page_delete')
		{
			$name = safe_field('page', 'txp_section', "name = 'default'");
		}

		$name = ( $copy && trim(preg_replace('/[<>&"\']/', '', $newname)) ) ? $newname : $name;

		echo
			startTable('edit').
			tr(
				tda(

					n.hed(
						gTxt('tagbuilder')
					, 2).

					n.n.hed(
						'<a href="#article-tags">'.gTxt('page_article_hed').'</a>'
					, 3, ' class="plain lever expanded"').
						n.'<div id="article-tags" class="toggle on" style="display:block">'.taglinks('page_article').'</div>'.

					n.n.hed('<a href="#article-nav-tags">'.gTxt('page_article_nav_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="article-nav-tags" class="toggle" style="display:none">'.taglinks('page_article_nav').'</div>'.

					n.n.hed('<a href="#nav-tags">'.gTxt('page_nav_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="nav-tags" class="toggle" style="display:none">'.taglinks('page_nav').'</div>'.

					n.n.hed('<a href="#xml-tags">'.gTxt('page_xml_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="xml-tags" class="toggle" style="display:none">'.taglinks('page_xml').'</div>'.

					n.n.hed('<a href="#misc-tags">'.gTxt('page_misc_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="misc-tags" class="toggle" style="display:none">'.taglinks('page_misc').'</div>'.

					n.n.hed('<a href="#file-tags">'.gTxt('page_file_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="file-tags" class="toggle" style="display:none">'.taglinks('page_file').'</div>'

				,' class="column"').

				tda(
					page_edit_form($name)
				, ' class="column"').

				tda(
					hed(gTxt('all_pages'), 2).
					page_list($name)
				, ' class="column"')
			).

			endTable();
	}

//-------------------------------------------------------------
	function page_edit_form($name)
	{
		global $step;
		$html = safe_field('user_html','txp_page',"name='".doSlash($name)."'");

		$out[] = '<p>'.gTxt('you_are_editing_page').sp.strong($name).br.
					'<textarea id="html" class="code" name="html" cols="84" rows="36">'.htmlspecialchars($html).'</textarea>'.br.
					n.fInput('submit','save',gTxt('save'),'publish').
					n.eInput('page').
					n.sInput('page_save').
					n.hInput('name',$name);

		$out[] =
				n.'<label for="copy-page">'.gTxt('copy_page_as').'</label>'.sp.
				n.fInput('text', 'newname', '', 'edit', '', '', '', '', 'copy-page').
				n.fInput('submit','copy',gTxt('copy'),'smallerbox').'</p>';
		return form(join('',$out));
	}

//-------------------------------------------------------------

	function page_list($current)
	{
		$protected = safe_column('DISTINCT page', 'txp_section', '1=1') + array('error_default');

		$rs = safe_rows_start('name', 'txp_page', "1 order by name asc");

		while ($a = nextRow($rs))
		{
			extract($a);

			$link  = eLink('page', '', 'name', $name, $name);
			$dlink = !in_array($name, $protected) ? dLink('page', 'page_delete', 'name', $name) : '';

			$out[] = ($current == $name) ?
				tr(td($name).td($dlink)) :
				tr(td($link).td($dlink));
		}

		return startTable('list').join(n, $out).endTable();
	}

//-------------------------------------------------------------

	function page_delete()
	{
		$name  = ps('name');
		$count = safe_count('txp_section', "page = '".doSlash($name)."'");

		if ($name == 'error_default')
		{
			return page_edit();
		}

		if ($count)
		{
			$message = array(gTxt('page_used_by_section', array('{name}' => $name, '{count}' => $count)), E_WARNING);
		}

		else
		{
			safe_delete('txp_page', "name = '".doSlash($name)."'");

			$message = gTxt('page_deleted', array('{name}' => $name));
		}

		page_edit($message);
	}

// -------------------------------------------------------------

	function page_save()
	{
		extract(doSlash(gpsa(array('name', 'html', 'copy'))));

		if ($copy)
		{
			$newname = doSlash(trim(preg_replace('/[<>&"\']/', '', gps('newname'))));

			if ($newname and safe_field('name', 'txp_page', "name = '$newname'"))
			{
				$message = array(gTxt('page_already_exists', array('{name}' => $newname)), E_ERROR);
			}
			elseif ($newname)
			{
				safe_insert('txp_page', "name = '$newname', user_html = '$html'");
				update_lastmod();

				$message = gTxt('page_created', array('{name}' => $newname));
			}
			else
			{
				$message = array(gTxt('page_name_invalid'), E_ERROR);
			}

 			page_edit($message);
		}

		else
		{
			safe_update('txp_page', "user_html = '$html'", "name = '$name'");

			update_lastmod();

			$message = gTxt('page_updated', array('{name}' => $name));

			page_edit($message);
		}
	}

//-------------------------------------------------------------
	function taglinks($type)
	{
		return popTagLinks($type);
	}
?>

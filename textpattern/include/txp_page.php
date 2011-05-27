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

		bouncer($step,
			array(
				'page_edit' => false,
				'page_save' => true,
				'page_delete' => true,
				'save_pane_state' => true
			)
		);

		switch(strtolower($step)) {
			case "":                page_edit();             break;
			case "page_edit":       page_edit();             break;
			case "page_save":       page_save();             break;
			case "page_delete":     page_delete();           break;
			case "page_new":        page_new();              break;
			case "save_pane_state": page_save_pane_state();  break;
		}
	}

//-------------------------------------------------------------

	function page_edit($message = '') {
		global $event,$step;

		pagetop(gTxt('edit_pages'), $message);

		extract(gpsa(array('copy', 'savenew')));
		$name = sanitizeForPage(gps('name'));
		$newname = sanitizeForPage(gps('newname'));

		if ($step == 'page_delete' || empty($name) && $step != 'page_new' && !$savenew)
		{
			$name = safe_field('page', 'txp_section', "name = 'default'");
		}
		elseif( ( $copy || $savenew ) && $newname )
		{
			$name = $newname;
		}

		// Format of each entry is popTagLink -> array ( gTxt() string, class/ID)
		$tagbuild_items = array(
			'page_article' => array('page_article_hed', 'article-tags'),
			'page_article_nav' => array('page_article_nav_hed', 'article-nav-tags'),
			'page_nav' => array('page_nav_hed', 'nav-tags'),
			'page_xml' => array('page_xml_hed', 'xml-tags'),
			'page_misc' => array('page_misc_hed', 'misc-tags'),
			'page_file' => array('page_file_hed', 'file-tags'),
		);

		$tagbuild_options = '';
		foreach ($tagbuild_items as $tb => $item) {
			$tagbuild_options .= n.n.'<div class="'.$item[1].'">'.hed('<a href="#'.$item[1].'">'.gTxt($item[0]).'</a>'
					, 3, ' class="plain lever'.(get_pref('pane_page_'.$item[1].'_visible') ? ' expanded' : '').'"').
						n.'<div id="'.$item[1].'" class="toggle" style="display:'.(get_pref('pane_page_'.$item[1].'_visible') ? 'block' : 'none').'">'.taglinks($tb).'</div></div>';
		}

		echo
			'<div id="'.$event.'_container" class="txp-container txp-edit">'.
			startTable('edit', '', 'edit-pane').
			tr(
				tda(

					'<div id="tagbuild_links">'.n.hed(
						gTxt('tagbuilder')
					, 2).
						$tagbuild_options.
						n.'</div>'
				,' class="column"').

				tda(
					page_edit_form($name)
				, ' class="column"').

				tda(
					'<div id="content_switcher">'.
					hed(gTxt('all_pages'), 2).
					graf(sLink('page', 'page_new', gTxt('create_new_page')), ' class="action-create smallerbox"').
					page_list($name).
					'</div>'
				, ' class="column"')
			).
			endTable().'</div>';
	}

//-------------------------------------------------------------
	function page_edit_form($name)
	{
		global $step;
		if ($name) {
			$html = safe_field('user_html','txp_page',"name='".doSlash($name)."'");
		} else {
			$html = gps('html');
		}

		if (empty($name))
		{
			$buttons = '<div class="edit-title">'.
			gTxt('name_for_this_page').': '
			.fInput('text','newname','','edit','','',20).
			hInput('savenew','savenew').
			'</div>';
		} else {
			$buttons = '<div class="edit-title">'.gTxt('you_are_editing_page').sp.strong(htmlspecialchars($name)).'</div>';
		}

		$out[] = '<div id="main_content">'.$buttons.
					'<textarea id="html" class="code" name="html" cols="84" rows="36">'.htmlspecialchars($html).'</textarea>'.br.
					n.fInput('submit','save',gTxt('save'),'publish').
					n.eInput('page').
					n.sInput('page_save').
					n.hInput('name',$name);

		if (!empty($name)) {
			$out[] =
				n.'<span class="copy-as"><label for="copy-page">'.gTxt('copy_page_as').'</label>'.sp.
				n.fInput('text', 'newname', '', 'edit', '', '', '', '', 'copy-page').
				n.fInput('submit','copy',gTxt('copy'),'smallerbox').'</span>';
		}
		$out[] = '</div>';

		return form(join('',$out), '', '', 'post', '', '', 'page_form');
	}

//-------------------------------------------------------------

	function page_list($current)
	{
		$protected = safe_column('DISTINCT page', 'txp_section', '1=1') + array('error_default');

		$rs = safe_rows_start('name', 'txp_page', "1 order by name asc");

		$ctr = 1;

		while ($a = nextRow($rs))
		{
			extract($a);

			$link  = eLink('page', '', 'name', $name, $name);
			$dlink = !in_array($name, $protected) ? dLink('page', 'page_delete', 'name', $name) : '';
			$trcls = ' class="'.((($ctr==1) ? 'first ' : '').(($ctr%2 == 0) ? 'even' : 'odd')).'"';
			$out[] = ($current == $name) ?
				tr(td($name).td($dlink), $trcls) :
				tr(td($link).td($dlink), $trcls);
			$ctr++;
		}

		return startTable('list', '', 'list').join(n, $out).endTable();
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
		extract(doSlash(gpsa(array('savenew', 'html', 'copy'))));
		$name = sanitizeForPage(gps('name'));

		if ($savenew or $copy)
		{
			$newname = doSlash(sanitizeForPage(gps('newname')));

			if ($newname and safe_field('name', 'txp_page', "name = '$newname'"))
			{
				$message = array(gTxt('page_already_exists', array('{name}' => $newname)), E_ERROR);
				if ($savenew)
				{
					$_POST['newname'] = '';
				}
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

// -------------------------------------------------------------

	function page_new()
	{
		page_edit();
	}
//-------------------------------------------------------------
	function taglinks($type)
	{
		return popTagLinks($type);
	}

// -------------------------------------------------------------
	function page_save_pane_state()
	{
		global $event;
		$panes = array('article-tags', 'article-nav-tags', 'nav-tags', 'xml-tags', 'misc-tags', 'file-tags');
		$pane = gps('pane');
		if (in_array($pane, $panes))
		{
			set_pref("pane_page_{$pane}_visible", (gps('visible') == 'true' ? '1' : '0'), $event, PREF_HIDDEN, 'yesnoradio', 0, PREF_PRIVATE);
			send_xml_response();
		} else {
			send_xml_response(array('http-status' => '400 Bad Request'));
		}
	}

?>

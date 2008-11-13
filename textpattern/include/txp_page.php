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

		if(!$step or !in_array($step, array('page_edit','page_save','page_delete','div_edit','div_save','page_list'))){
			page_edit();
		} else $step();
	}

//-------------------------------------------------------------

	function page_edit($message = '') {
		global $step;

		pagetop(gTxt('edit_pages'), $message);

		extract(gpsa(array('name', 'div', 'newname', 'copy')));

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
						'<a href="#article-tags" onclick="toggleDisplay(\'article-tags\'); return false;">'.gTxt('page_article_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="article-tags" class="toggle on">'.taglinks('page_article').'</div>'.

					n.n.hed('<a href="#article-nav-tags" onclick="toggleDisplay(\'article-nav-tags\'); return false;">'.gTxt('page_article_nav_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="article-nav-tags" class="toggle">'.taglinks('page_article_nav').'</div>'.

					n.n.hed('<a href="#nav-tags" onclick="toggleDisplay(\'nav-tags\'); return false;">'.gTxt('page_nav_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="nav-tags" class="toggle">'.taglinks('page_nav').'</div>'.

					n.n.hed('<a href="#xml-tags" onclick="toggleDisplay(\'xml-tags\'); return false;">'.gTxt('page_xml_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="xml-tags" class="toggle">'.taglinks('page_xml').'</div>'.

					n.n.hed('<a href="#misc-tags" onclick="toggleDisplay(\'misc-tags\'); return false;">'.gTxt('page_misc_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="misc-tags" class="toggle">'.taglinks('page_misc').'</div>'.

					n.n.hed('<a href="#file-tags" onclick="toggleDisplay(\'file-tags\'); return false;">'.gTxt('page_file_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="file-tags" class="toggle">'.taglinks('page_file').'</div>'

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

// -------------------------------------------------------------
	#deprecated
	function div_edit()
	{
		return page_edit();
	}

//-------------------------------------------------------------
	function page_edit_form($name)
	{
		global $step;
		if ($step=='div_edit') {
			list($html_array,$html,$start_pos,$stop_pos) = extract_div();
			$html_array = serialize($html_array);
			$outstep = 'div_save';
		} else {
			$html = safe_field('user_html','txp_page',"name='".doSlash($name)."'");
			$outstep = 'page_save';
		}

		$divline = ($step == 'div_edit') ? graf(gTxt('you_are_editing_div').sp.strong($div)) : '';

		$out[] = '<p>'.gTxt('you_are_editing_page').sp.strong($name).$divline.br.
					'<textarea id="html" class="code" name="html" cols="84" rows="36">'.htmlspecialchars($html).'</textarea>'.br.
					n.fInput('submit','save',gTxt('save'),'publish').
					n.eInput('page').
					n.sInput($outstep).
					n.hInput('name',$name);

			if($step=='div_edit') {
				$out[] =
					n.hInput('html_array',$html_array).
				  	n.hInput('start_pos',$start_pos).
				  	n.hInput('stop_pos',$stop_pos).
				  	n.hInput('name',$name);
			} else {
				$out[] =
						n.'<label for="copy-page">'.gTxt('copy_page_as').'</label>'.sp.
						n.fInput('text', 'newname', '', 'edit', '', '', '', '', 'copy-page').
						n.fInput('submit','copy',gTxt('copy'),'smallerbox').'</p>';
			}
		return form(join('',$out));
	}

//-------------------------------------------------------------

	function page_list($current) {
		$protected = array(
			safe_field('page', 'txp_section', "name = 'default'"),
			'error_default'
		);

		$rs = safe_rows_start('name', 'txp_page', "1 order by name asc");

		while ($a = nextRow($rs)) {
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

		if ($name == 'default')
		{
			return page_edit();
		}

		if ($count)
		{
			$message = gTxt('page_used_by_section', array('{name}' => $name, '{count}' => $count));
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
				$message = gTxt('page_already_exists', array('{name}' => $newname));
			}
			elseif ($newname)
			{
				safe_insert('txp_page', "name = '$newname', user_html = '$html'");
				update_lastmod();

				$message = gTxt('page_created', array('{name}' => $newname));
			}
			else
			{
				$message = gTxt('page_name_invalid');
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

// -------------------------------------------------------------
	#deprecated
	function extract_div()
	{
		extract(doSlash(gpsa(array('name','div'))));
		$name = (!$name) ? 'default' : $name;
		$html = safe_field('user_html','txp_page',"name='$name'");

		$goodlevel = (preg_match("/<div id=\"container\"/i",$html)) ? 2 : 1;

		if ($div) {

			$html_array = preg_split("/(<.*>)/U",$html,-1,PREG_SPLIT_DELIM_CAPTURE);

			$level = 0;
			$count = -1;
			$indiv = false;
			foreach($html_array as $a) {
				$count++;
				if(preg_match("/^<div/i",$a)) $level++;
				if(preg_match("/^<div id=\"$div\"/i",$a)) {
					$indiv = true;
					$start_pos = $count;
				}

				if ($indiv) $thediv[] = $a;

#				print n.$count.': '.$level.': '.$a;

				if($level==$goodlevel && preg_match("/^<\/div/i",$a) && $indiv) {
					$indiv = false;
					$stop_pos = $count;
				}
				if(preg_match("/^<\/div/i",$a)) $level--;
			}
			return array($html_array,join('',$thediv),$start_pos,$stop_pos);
		}
	}

// -------------------------------------------------------------
	#deprecated
	function div_save()
	{
		extract(gpsa(array('html_array', 'html', 'start_pos', 'stop_pos', 'name')));

		$html_array = unserialize($html_array);

		$repl_array = preg_split("/(<.*>)/U", $html, -1, PREG_SPLIT_DELIM_CAPTURE);

		array_splice($html_array, $start_pos, ($stop_pos - $start_pos) + 1, $repl_array);

		$html = doSlash(join('', $html_array));

		safe_update('txp_page', "user_html = '$html'", "name = '".doSlash($name)."'");

		$message = gTxt('page_updated', array('{name}' => $name));

		page_edit($message);

		// print_r($html_array);
	}

?>

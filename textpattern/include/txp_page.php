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
	function page_edit($message='')
	{
		global $step;
		pagetop(gTxt('edit_pages'),$message);
		extract(gpsa(array('name','div')));
		$name = (!$name or $step=='page_delete') ? 'default' : $name;

		echo 
			startTable('edit').
			tr(
				tda(

					n.hed(
						gTxt('tagbuilder')
					, 2).

					n.n.hed(
						'<a href="#" onclick="toggleDisplay(\'article-tags\'); return false;">'.gTxt('page_article_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="article-tags">'.taglinks('page_article').'</div>'.

					n.n.hed('<a href="#" onclick="toggleDisplay(\'article-nav-tags\'); return false;">'.gTxt('page_article_nav_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="article-nav-tags" style="display: none;">'.taglinks('page_article_nav').'</div>'.

					n.n.hed('<a href="#" onclick="toggleDisplay(\'nav-tags\'); return false;">'.gTxt('page_nav_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="nav-tags" style="display: none;">'.taglinks('page_nav').'</div>'.

					n.n.hed('<a href="#" onclick="toggleDisplay(\'xml-tags\'); return false;">'.gTxt('page_xml_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="xml-tags" style="display: none;">'.taglinks('page_xml').'</div>'.

					n.n.hed('<a href="#" onclick="toggleDisplay(\'misc-tags\'); return false;">'.gTxt('page_misc_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="misc-tags" style="display: none;">'.taglinks('page_misc').'</div>'.

					n.n.hed('<a href="#" onclick="toggleDisplay(\'file-tags\'); return false;">'.gTxt('page_file_hed').'</a>'
					, 3, ' class="plain"').
						n.'<div id="file-tags" style="display: none;">'.taglinks('page_file').'</div>'

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
			$html = safe_field('user_html','txp_page',"name='$name'");
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
	function page_list($current)
	{
		$rs = safe_rows_start("name", "txp_page", "name != '' order by name");			
		while ($a = nextRow($rs)) {
			extract($a);
			$dlink = ($name!='default') ? dLink('page','page_delete','name',$name) :'';
			$link =  '<a href="?event=page'.a.'name='.$name.'">'.$name.'</a>';
			$out[] = ($current == $name) 
			?	tr(td($name).td($dlink))
			:	tr(td($link).td($dlink));
		}
		return startTable('list').join(n,$out).endTable();
	}
	
//-------------------------------------------------------------
	function page_delete()
	{
		if (ps('name')=='default') return page_edit();
		$name = doSlash(ps('name'));
		safe_delete("txp_page","name='$name'");
		page_edit(messenger('page',$name,'deleted'));
	}

// -------------------------------------------------------------
	function page_save() {
		extract(doSlash(gpsa(array('name','html','newname','copy'))));
		if($newname && $copy) {
			safe_insert("txp_page", "name='$newname', user_html='$html'");
			page_edit(messenger('page',$newname,'created'));
		} else {
			safe_update("txp_page","user_html='$html'", "name='$name'");
			page_edit(messenger('page',$name,'updated'));
		}
	}
	
//-------------------------------------------------------------
	function taglinks($type) 
	{
		return popTagLinks($type);
	}

// -------------------------------------------------------------
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
	function div_save() 
	{
		extract(gpsa(array('html_array','html','start_pos','stop_pos','name')));
		
		$html_array = unserialize($html_array);
		
		$repl_array = preg_split("/(<.*>)/U",$html,-1,PREG_SPLIT_DELIM_CAPTURE);
		
		array_splice($html_array,$start_pos,($stop_pos - $start_pos)+1,$repl_array);
		
		$html = doSlash(join('',$html_array));
		
		safe_update("txp_page","user_html='$html'", "name='$name'");

		page_edit(messenger('page',$name,'updated'));

#		print_r($html_array);
	}

?>
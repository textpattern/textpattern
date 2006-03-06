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

		$divline = ($step=="div_edit")
		?	graf(gTxt('you_are_editing_div') . sp . strong($div))
		:	'';
		echo 
			startTable('edit').
			tr(
				td().
				td(
					graf(gTxt('you_are_editing_page') . sp . strong($name)).$divline
				).
				td()
			).
			tr(
				tda(
					hed(gTxt('useful_tags'),2).
					graf(gTxt('page_article_hed').br.small(taglinks('page_article')),' class="column"').
					graf(gTxt('page_article_nav_hed').br.small(taglinks('page_article_nav')),' class="column"').
					graf(gTxt('page_nav_hed').br.
						small(taglinks('page_nav')),' class="column"').
					graf(gTxt('page_xml_hed').br.
						small(taglinks('page_xml')),' class="column"').
					graf(gTxt('page_misc_hed').br.
						small(taglinks('page_misc')),' class="column"').
					graf(gTxt('page_file_hed').br.
						small(taglinks('page_file')),' class="column"')
				).
				tda(
					page_edit_form($name),' class="column"'
				).
				tda(
					hed(gTxt('all_pages'),2).
					page_list($name),' class="column"'
				)
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
		
		$out[] = textarea('500','600',$html,'html').
				graf(
					fInput('submit','save',gTxt('save'),'publish').
					eInput('page').
					sInput($outstep).
					hInput('name',$name)
				);
				
			if($step=='div_edit') {
				$out[] = 
					hInput('html_array',$html_array).
				  	hInput('start_pos',$start_pos).
				  	hInput('stop_pos',$stop_pos).
				  	hInput('name',$name);
			} else {
				$out[] = 
					graf(
						gTxt('copy_page_as').
						fInput('text','newname','','edit').
						fInput('submit','copy',gTxt('copy'),'smallerbox')
					); 
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
	function textarea($h,$w,$content,$name)
	{
		return '<textarea name="'.$name.'" style="height:'.$h.'px;width:'.$w.'px;font-family:monaco,courier,courier new;font-size:10px;margin-top:6px" rows="1" cols="1">'.htmlspecialchars($content).'</textarea>';
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

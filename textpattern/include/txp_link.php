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

	global $vars;
	$vars = array('category', 'url', 'linkname', 'linksort', 'description', 'id');

	if ($event == 'link') {	
		require_privs('link');		
		
		if(!$step or !function_exists($step) or !in_array($step, array('link_list','link_edit','link_post','link_save','link_delete','link_change_pageby','link_multi_edit'))){
			link_edit();
		} else $step();
	}

// -------------------------------------------------------------
	function link_list($message="") 
	{
		global $step,$link_list_pageby;
		
		extract(get_prefs());
		
		$page = gps('page');
		$total = getCount('txp_link',"1");  
		$limit = max(@$link_list_pageby, 25);
		$numPages = ceil($total/$limit);  
		$page = (!$page) ? 1 : $page;
		$offset = ($page - 1) * $limit;

		$sort = gps('sort');
		$dir = gps('dir');

		$sort = ($sort) ? $sort : 'linksort';
		$dir = ($dir) ? $dir : 'asc';
		if ($dir == "desc") { $dir = "asc"; } else { $dir = "desc"; }

		$nav[] = ($page > 1)
		?	PrevNextLink("link",$page-1,gTxt('prev'),'prev') : '';

		$nav[] = sp.small($page. '/'.$numPages).sp;

		$nav[] = ($page != $numPages) 
		?	PrevNextLink("link",$page+1,gTxt('next'),'next') : '';

		$rs = safe_rows_start(
			"*", 
			"txp_link", 
			"1 order by $sort $dir limit $offset, $limit"
		);


		if ($rs) {
			echo '<form action="index.php" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">',
			startTable('list'),

			tr(
				column_head('link_name','linksort','link',1,$dir).
				column_head('description','description','link',1,$dir).
				column_head('link_category','category','link',1,$dir).
				td()
				);
			while ($a = nextRow($rs)) {
				extract($a);				
				$elink = eLink('link','link_edit','id',$id,$linkname);
				$cbox = fInput('checkbox','selected[]',$id);
				$category = fetch_category_title($category, 'link');

				echo tr(
						td($elink).
						td($description).
						td($category).
						td($cbox)
					);
			}
			echo 
			tr(
				tda(select_buttons().link_multiedit_form(), ' colspan="4" style="border:0px;text-align:right"')
			);
			echo endTable(),'</form>';
			echo pageby_form('link',$link_list_pageby);
			echo graf(join('',$nav),' align="center"');
		}
	}

// -------------------------------------------------------------
	function link_edit($message="")
	{
		global $vars,$step;
		extract(gpsa($vars));

		pagetop(gTxt('edit_links',$message));


		$id = gps('id');
		if($id && $step=='link_edit') {
			extract(safe_row("*", "txp_link", "id = $id"));
		}
		
		if ($step=='link_save' or $step=='link_post'){
			foreach($vars as $var) {
				$$var = '';
			}
		}

		$textarea = '<textarea name="description" cols="40" rows="7" tabindex="4">'.
			$description.'</textarea>';
		$selects = linkcategory_popup($category);
		$editlink = ' ['.eLink('category','list','','',gTxt('edit')).']';

		$out = 
			startTable( 'edit' ) .
			
				tr( fLabelCell( 'title' ) . fInputCell( 'linkname', $linkname, 1, 30 )) .
					
				tr( fLabelCell( 'sort_value') .fInputCell( 'linksort', $linksort, 2, 15 )) .
					
				tr( fLabelCell( 'url','link_url') . fInputCell( 'url', $url, 3, 30) ) .
					
				tr( fLabelCell( 'link_category', 'link_category' ) . td( $selects . $editlink ) ) .
					
				tr( fLabelCell( 'description', 'link_description' ) . tda( $textarea, ' valign="top"' ) ) .
					
				tr( td() . td( fInput( "submit", '', gTxt( 'save' ), "publish" ) ) ) .
				
			endTable() .
			
			eInput( 'link' ) . sInput( ( $step=='link_edit' ) ? 'link_save' : 'link_post' ) .
			hInput( 'id', $id );

		echo form( $out );
		echo link_list();
	}

//--------------------------------------------------------------
	function linkcategory_popup($cat="") 
	{
		return event_category_popup("link", $cat);		
	}

// -------------------------------------------------------------
	function link_post()
	{
		global $txpcfg,$prefs,$vars;
		$varray = gpsa($vars);

		if($prefs['textile_links']) {

			include_once txpath.'/lib/classTextile.php';
			$textile = new Textile();
		
			$varray['linkname'] = $textile->TextileThis($varray['linkname'],'',1);
			$varray['description'] = $textile->TextileThis($varray['description'],1);
	
		}
	
		extract(doSlash($varray));

		if (!$linksort) $linksort = $linkname;

		$q = safe_insert("txp_link",
		   "category    = '$category',
			date        = now(),
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description'"
		);

		if ($q) {
			//update lastmod due to link feeds
			safe_update("txp_prefs", "val = now()", "name = 'lastmod'");
			
			link_edit(messenger('link',$linkname,'created'));			
		}
	}

// -------------------------------------------------------------
	function link_save() 
	{
		global $txpcfg,$prefs,$vars;
		$varray = gpsa($vars);

		if($prefs['textile_links']) {

			include_once txpath.'/lib/classTextile.php';
			$textile = new Textile();
			
			$varray['linkname'] = $textile->TextileThis($varray['linkname'],'',1);
			$varray['description'] = $textile->TextileThis($varray['description'],1);
		
		}
		
		extract(doSlash($varray));
		
		if (!$linksort) $linksort = $linkname;

		$rs = safe_update("txp_link",
		   "category    = '$category',
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description'",
		   "id = '$id'"
		);
		if ($rs) link_edit( messenger( 'link', doStrip($linkname), 'saved' ) );
	}

// -------------------------------------------------------------
	function link_delete() 
	{
		$id = ps('id');
		$rs = safe_delete("txp_link", "id=$id");
		if ($rs) link_edit(messenger('link', '', 'deleted'));
	}

// -------------------------------------------------------------
	function link_change_pageby() 
	{
		event_change_pageby('link');
		link_edit();
	}

// -------------------------------------------------------------
	function link_multiedit_form() 
	{
		return event_multiedit_form('link');
	}

// -------------------------------------------------------------
	function link_multi_edit() 
	{
		$deleted = event_multi_edit('txp_link','id');
		if(!empty($deleted)) return link_edit(messenger('link',$deleted,'deleted'));
		return link_edit();
	}

?>

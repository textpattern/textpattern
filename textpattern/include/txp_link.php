<?php

/*
	This is Textpattern

	Copyright 2004 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/

	check_privs(1,2,3);

	$vars = array('category', 'url', 'linkname', 'linksort', 'description', 'id');
		
	if(!$step or !function_exists($step)){
		link_edit();
	} else $step();

// -------------------------------------------------------------
	function link_list($message="") 
	{
		global $step,$link_list_pageby;
		
		extract(get_prefs());
		
		$page = gps('page');
		$total = getCount('txp_link',"1");  
		$limit = $link_list_pageby;
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

		$rs = safe_rows(
			"*", 
			"txp_link", 
			"1 order by $sort $dir limit $offset,$limit"
		);


		if ($rs) {
			echo '<form action="index.php" method="post" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">',
			startTable('list'),

			tr(
				column_head('link_name','linksort','link',1,$dir).
				column_head('description','description','link',1,$dir).
				column_head('link_category','category','link',1,$dir).
				td()
				);
			foreach ($rs as $a) {
				extract($a);				
				$elink = eLink('link','link_edit','id',$id,$linkname);
				$cbox = fInput('checkbox','selected[]',$id);
					
				echo tr(
						td($elink).
						td($description).
						td($category).
						td($cbox)
					);
			}
			echo 
			tr(
				tda(link_multiedit_form(), ' colspan="4" style="border:0px;text-align:right"')
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
		$arr = array('');
		$rs = getTree("root","link");
		if ($rs) {
			return treeSelectInput("category", $rs, $cat);
		}
		return false;
	}

// -------------------------------------------------------------
	function link_post($vars)
	{
		global $txpcfg,$txpac;
		$varray = gpsa($vars);

		if($txpac['textile_links']) {

			include_once $txpcfg['txpath'].'/lib/classTextile.php';
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

		if ($q) link_edit(messenger('link',$linkname,'created'));
	}

// -------------------------------------------------------------
	function link_save($vars) 
	{
		global $txpcfg,$txpac;
		$varray = gpsa($vars);

		if($txpac['textile_links']) {

			include_once $txpcfg['txpath'].'/lib/classTextile.php';
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
		$qty = gps('qty');
		safe_update('txp_prefs',"val=$qty","name='link_list_pageby'");
		link_edit();
	}

// -------------------------------------------------------------
	function link_multiedit_form() 
	{
		$method = ps('method');
		$methods = array('delete'=>'delete');
		return
			gTxt('with_selected').sp.selectInput('method',$methods,$method,1).
			eInput('link').sInput('link_multi_edit').fInput('submit','',gTxt('go'),'smallerbox');
	}

// -------------------------------------------------------------
	function link_multi_edit() 
	{
		$method = ps('method');
		$things = ps('selected');
		if ($things) {
			if ($method == 'delete') {
				foreach($things as $id) {
					if (safe_delete('txp_link',"id='$id'")) {
						$ids[] = $id;
					}
				}
				link_edit(messenger('link',join(', ',$ids),'deleted'));
			} else link_edit();
		} else link_edit();
	}



?>

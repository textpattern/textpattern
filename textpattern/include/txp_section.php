<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance ofthe Textpattern license agreement 
*/

	require_privs('section');

	if(!$step or !function_exists($step)){
		section_list();
	} else $step();

// -------------------------------------------------------------
	function section_list($message='') 
	{
		pagetop(gTxt('sections'),$message);

		global $txpac,$wlink;
		$out[] = 
			tr(
				tdcs(strong(gTxt('section_head')).popHelp('section_category'),3)
			);
		$out[] = 
			tr(tdcs(form(
				fInput('text','name','','edit','','',10).
				fInput('submit','',gTxt('Create'),'smallerbox').
				eInput('section').
				sInput('section_create')
			),3));
	
		$pageslist = safe_column("name", "txp_page", "1");
		$styleslist = safe_column("name", "txp_css", "1");

		$rs = safe_rows("*", "txp_section", "name!='' order by name");

		if($rs) {
			foreach ($rs as $a) {
				extract($a);
				if($name=='default') continue;

				$deletelink = dLink('section','section_delete','name',
					$name,'','type','section');

				$form = startTable('edit') .
				stackRows(
					fLabelCell(gTxt('section_name').':') . 
						fInputCell('name',$name,1,20),
		
					fLabelCell(gTxt('uses_page').':') . 
						td(selectInput('page',$pageslist,$page).
							popHelp('section_uses_page'),'','noline'),
		
					fLabelCell(gTxt('uses_style').':') . 
						td(selectInput('css',$styleslist,$css).
							popHelp('section_uses_css'),'','noline'),
		
					fLabelCell(gTxt('selected_by_default').'?') . 
						td(yesnoradio('is_default',$is_default).
							popHelp('section_is_default'),'','noline'),
		
					fLabelCell(gTxt('on_front_page').'?') . 
						td(yesnoradio('on_frontpage',$on_frontpage).
							popHelp('section_on_frontpage'),'','noline'),
		
					fLabelCell(gTxt('syndicate').'?') . 
						td(yesnoradio('in_rss',$in_rss).
							popHelp('section_syndicate'),'','noline'),
		
					fLabelCell(gTxt('include_in_search').'?') . 
						td(yesnoradio('searchable',$searchable).
							popHelp('section_searchable'),'','noline'),
						
					tda(fInput('submit', '', gTxt('save_button'),'smallerbox'),' colspan="2" style="border:0"')
				).
				endTable().
				eInput('section').sInput('section_save').hInput('old_name',$name);
				
				$form = form($form);

				$out[] = tr( td( $name ) . td( $form ) . td( $deletelink ) );
			}
		}
		echo startTable('list').join('',$out).endTable();
	}

//-------------------------------------------------------------
	function section_create() 
	{
		global $txpcfg;
		$name = ps('name');
		
		//Prevent non url chars on section names
		include_once $txpcfg['txpath'].'/lib/classTextile.php';
		$textile = new Textile();
				
		$name = dumbDown($textile->TextileThis(trim(doSlash($name)),1));
		$name = preg_replace("/[^[:alnum:]\-]/", "", str_replace(" ","-",$name));
		
		$chk = fetch('name','txp_section','name',$name);
		if (!$chk) {
			if ($name) {
				$rs = safe_insert(
				   "txp_section",
				   "name         = '$name',
					page         = 'default',
					css          = 'default',
					is_default   = 0,
					in_rss       = 1,
					on_frontpage = 1"
				);
				if ($rs) section_list(messenger('section',$name,'created'));
			} else section_list();
		} else section_list(gTxt('section_name_already_exists'));
	}

//-------------------------------------------------------------
	function section_save()
	{
		global $txpcfg;
		$in = psa(array(
			'name','page','css','is_default','on_frontpage','in_rss','searchable','old_name')
		);
		extract(doSlash($in));
		
		//Prevent non url chars on section names
		include_once $txpcfg['txpath'].'/lib/classTextile.php';
		$textile = new Textile();		
		$name = dumbDown($textile->TextileThis($name, 1));
		$name = preg_replace("/[^[:alnum:]\-]/", "", str_replace(" ","-",$name));
		
		if ($is_default) {
			safe_update("txp_section", "is_default=0", "name!='$old_name'");
		}

		safe_update("txp_section",
		   "name         = '$name',
			page         = '$page',
			css          = '$css',
			is_default   = '$is_default',
			on_frontpage = '$on_frontpage',
			in_rss       = '$in_rss',
			searchable   = '$searchable'",
		   "name = '$old_name'"
		);
		safe_update("textpattern","Section='$name'", "Section='$old_name'");
		section_list(messenger('section',$name,'updated'));
	}

// -------------------------------------------------------------
	function section_delete() 
	{
		$name = ps('name');
		safe_delete("txp_section","name='$name'");
		section_list(messenger('section',$name,'deleted'));
	}

?>

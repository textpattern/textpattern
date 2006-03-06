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

		if(!$step or !function_exists($step) or !in_array($step, array('sec_section_list','section_create','section_delete','section_save'))){
			sec_section_list();
		} else $step();
	}

// -------------------------------------------------------------
	function sec_section_list($message='') 
	{
		pagetop(gTxt('sections'),$message);

		global $wlink;

		$pageslist = safe_column("name", "txp_page", "1=1");
		$styleslist = safe_column("name", "txp_css", "1=1");

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

		$defrow = safe_row("page, css","txp_section","name like 'default'");
		
		$out[] =
			form(
				tr(td(gTxt('default')) . td(
					startTable('edit','left','').

						tr(fLabelCell(gTxt('uses_page').':') . 
							td(selectInput('page',$pageslist,$defrow['page']).
								popHelp('section_uses_page'),'','noline')).
		
						tr(fLabelCell(gTxt('uses_style').':') . 
							td(selectInput('css',$styleslist,$defrow['css']).
								popHelp('section_uses_css'),'','noline')).
						tr(tda(fInput('submit', '', gTxt('save_button'),'smallerbox'),' colspan="2" style="border:0"')).

					endTable()
				). td()).
				eInput('section').sInput('section_save').hInput('name','default')
			);

		$rs = safe_rows_start("*", "txp_section", "name!='' order by name");

		if($rs) {
			while ($a = nextRow($rs)) {
				extract($a);
				if($name=='default') continue;

				$deletelink = dLink('section','section_delete','name',
					$name,'','type','section');

				$form = startTable('edit') .
				stackRows(
					fLabelCell(gTxt('section_name').':') . 
						fInputCell('name',$name,1,20),
		
					fLabelCell(gTxt('section_longtitle').':') . 
						fInputCell('title',$title,1,20),
		
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
		echo startTable('list') . join('',$out) . endTable();
	}

//-------------------------------------------------------------
	function section_create() 
	{
		global $txpcfg;
		$name = doSlash(ps('name'));
		
		//Prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile();
		$title = $textile->TextileThis($name,1);
		$name = dumbDown($textile->TextileThis(trim(doSlash($name)),1));
		$name = preg_replace("/[^[:alnum:]\-_]/", "", str_replace(" ","-",$name));
		
		$chk = fetch('name','txp_section','name',$name);
		if (!$chk) {
			if ($name) {
				$rs = safe_insert(
				   "txp_section",
				   "name         = '$name',
					title        = '$title', 
					page         = 'default',
					css          = 'default',
					is_default   = 0,
					in_rss       = 1,
					on_frontpage = 1"
				);
				if ($rs) sec_section_list(messenger('section',$name,'created'));
			} else sec_section_list();
		} else sec_section_list(gTxt('section_name_already_exists'));
	}

//-------------------------------------------------------------
	function section_save()
	{
		global $txpcfg;
		$in = psa(array(
			'name', 'title','page','css','is_default','on_frontpage','in_rss','searchable','old_name')
		);
		extract(doSlash($in));
		
		if (empty($title))
			$title = $name;

		//Prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile();
		$title = $textile->TextileThis($title,1);
		$name = dumbDown($textile->TextileThis($name, 1));
		$name = preg_replace("/[^[:alnum:]\-_]/", "", str_replace(" ","-",$name));

		if ($name == 'default') {
				safe_update("txp_section", "page='$page',css='$css'", "name='default'");
		} else {
			if ($is_default) { // note this means 'selected by default' not 'default page'
				safe_update("txp_section", "is_default=0", "name!='$old_name'");
			}
	
			safe_update("txp_section",
			   "name         = '$name',
				title        = '$title',
				page         = '$page',
				css          = '$css',
				is_default   = '$is_default',
				on_frontpage = '$on_frontpage',
				in_rss       = '$in_rss',
				searchable   = '$searchable'",
			   "name = '$old_name'"
			);
			safe_update("textpattern","Section='$name'", "Section='$old_name'");
		}
		sec_section_list(messenger('section',$name,'updated'));
	}

// -------------------------------------------------------------
	function section_delete() 
	{
		$name = ps('name');
		safe_delete("txp_section","name='$name'");
		sec_section_list(messenger('section',$name,'deleted'));
	}

?>

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

if ($event == 'category') {
	require_privs('category');	

	if(!$step or !in_array($step, array(
		'cat_category_list','cat_article_create','cat_image_create','cat_file_create','cat_link_create',
		'cat_category_multiedit','cat_article_save','cat_image_save','cat_file_save','cat_link_save',
		'cat_article_edit','cat_image_edit','cat_file_edit','cat_link_edit',		
	))){
		cat_category_list();
	} else $step();
}

//-------------------------------------------------------------
	function cat_category_list($message="")
	{
		pagetop(gTxt('categories'),$message);
		$out = array('<table cellspacing="20" align="center">',
		'<tr>',
			tdtl(cat_article_list(),' class="categories"'),
			tdtl(cat_link_list(),' class="categories"'),
			tdtl(cat_image_list(),' class="categories"'),
			tdtl(cat_file_list(),' class="categories"'),
		'</tr>',
		endTable());
		echo join(n,$out);
	}

 
//-------------------------------------------------------------
	function cat_article_list() 
	{
		return cat_event_category_list('article');
	}

//-------------------------------------------------------------
	function cat_article_create()
	{
		return cat_event_category_create('article');
	}

//-------------------------------------------------------------
	function cat_article_edit()
	{
		return cat_event_category_edit('article');
	}

//-------------------------------------------------------------
	function cat_article_save()
	{
		return cat_event_category_save('article', 'textpattern');
	}

//--------------------------------------------------------------
	function cat_parent_pop($name,$type)
	{
		$rs = getTree("root",$type);
		if ($rs) {
			return ' '.treeSelectInput('parent', $rs, $name);
		}
		return 'no categories created';
	}




// -------------------------------------------------------------
	function cat_link_list() 
	{
		return cat_event_category_list('link');
	}

//-------------------------------------------------------------
	function cat_link_create()
	{
		return cat_event_category_create('link');
	}

//-------------------------------------------------------------
	function cat_link_edit()
	{
		return cat_event_category_edit('link');
	}

//-------------------------------------------------------------
	function cat_link_save()
	{
		return cat_event_category_save('link', 'txp_link');
	}

// -------------------------------------------------------------
	function cat_image_list() 
	{
		return cat_event_category_list('image');
	}

//-------------------------------------------------------------
	function cat_image_create()
	{
		return cat_event_category_create('image');
	}

//-------------------------------------------------------------
	function cat_image_edit()
	{
		return cat_event_category_edit('image');
	}

//-------------------------------------------------------------
	function cat_image_save()
	{
		return cat_event_category_save('image', 'txp_image');
	}


// -------------------------------------------------------------
	function cat_article_multiedit_form($area, $array) 
	{
		$methods = array('delete'=>gTxt('delete'));
		if ($array) {
		return 
		form(
			join('',$array).
			eInput('category').sInput('cat_category_multiedit').hInput('type',$area).
			small(gTxt('with_selected')).sp.selectInput('method',$methods,'',1).sp.
			fInput('submit','',gTxt('go'),'smallerbox')
			,'margin-top:1em',"verify('".gTxt('are_you_sure')."')"
		);
		} return;
	}

// -------------------------------------------------------------
	function cat_category_multiedit() 
	{
		$type = ps('type');
		$method = ps('method');
		$things = ps('selected');
		if ($things) {
			foreach($things as $catid) {
				if ($method == 'delete') {
					if (safe_delete('txp_category',"id=$catid")) {
						$categories[] = $catid;
					}
				}
			}
			rebuild_tree('root', 1, $type);
			cat_category_list(messenger($type.'_category',join(', ',$categories),'deleted'));
		}
	}

//Refactoring: Functions are more or less the same for all event types
// so, merge them. Addition of new event categories is easiest now.

//-------------------------------------------------------------
	function cat_event_category_list($evname) 
	{
		global $prefs;
		if($evname=='article') $headspan = ($prefs['show_article_category_count']) ? 3 : 2;		

		$o = hed(gTxt($evname.'_head').popHelp($evname.'_category'),3);

		$o .= 
			form(
				fInput('text','name','','edit','','',10).
				fInput('submit','',gTxt('Create'),'smallerbox').
				eInput('category').
				sInput('cat_'.$evname.'_create')
			);

		$rs = getTree('root',$evname);
			
		if($rs) {
			foreach ($rs as $a) {
				extract($a);
				if ($name=='root') continue;
				//Stuff for articles only
				if ($evname=='article' && $prefs['show_article_category_count']) {
					$sname = doSlash($name);
					$count = sp . small(safe_count("textpattern",
						"((Category1='$sname') or (Category2='$sname'))"));
				} else $count = '';

				$cbox = checkbox('selected[]',$id,0);
				$editlink = eLink('category','cat_'.$evname.'_edit','id',
					$id,$title);

				$items[] = graf( $cbox . sp . str_repeat(sp,max(0,$level-1)*2) . $editlink . $count);
			}

			if (!empty($items)) $o .= cat_article_multiedit_form($evname,$items);

		}
			return $o;
	}

//-------------------------------------------------------------
	function cat_event_category_create($evname)
	{
		global $txpcfg;
		
		//Prevent non url chars on category names
		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile();
		
		$name = ps('name');		
		$title = doSlash($name);				
		$name = dumbDown($textile->TextileThis(trim(doSlash($name)),1));
		$name = preg_replace("/[^[:alnum:]\-_]/", "", str_replace(" ","-",$name));

		$check = safe_field("name", "txp_category", "name='$name' and type='$evname'");

		if (!$check) {
			if($name) {				
				$q = 
				safe_insert("txp_category", "name='$name', title='$title', type='$evname', parent='root'");
				
				rebuild_tree('root', 1, $evname);
				
				if ($q) cat_category_list(messenger($evname.'_category',$name,'created'));
			} else {
				cat_category_list();
			}
		} else {
			cat_category_list(messenger($evname.'_category',$name,'already_exists'));		
		}
	}

//-------------------------------------------------------------
	function cat_event_category_edit($evname)
	{
		pagetop(gTxt('categories'));

		extract(doSlash(gpsa(array('id','parent'))));
		$row = safe_row("*", "txp_category", "id=$id");
		if($row){
			extract($row);
			$out = stackRows(
				fLabelCell($evname.'_category_name') . fInputCell('name', $name, 1, 20),
				fLabelCell('parent') . td(cat_parent_pop($parent,$evname)),
				fLabelCell($evname.'_category_title') . fInputCell('title', $title, 1, 30),
				hInput('id',$id),
				tdcs(fInput('submit', '', gTxt('save_button'),'smallerbox'), 2)
			);
		}
		$out.= eInput( 'category' ) . sInput( 'cat_'.$evname.'_save' ) . hInput( 'old_name',$name );
		echo form( startTable( 'edit' ) . $out . endTable() );
	}

//-------------------------------------------------------------
	function cat_event_category_save($evname,$table_name)
	{
		
		global $txpcfg;
		
		//Prevent non url chars on category names
		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile();
				
		$in = psa(array('id','name','old_name','parent','title'));
		extract(doSlash($in));
		
		$title = $textile->TextileThis($title,1);		
		$name = dumbDown($textile->TextileThis($name,1));
		$name = preg_replace("/[^[:alnum:]\-_]/", "", str_replace(" ","-",$name));
		
		$parent = ($parent) ? $parent : 'root';
		if (safe_update("txp_category", 
					"name='$name',parent='$parent',title='$title'", 
					"id=$id"))
			safe_update('txp_category', "parent='$name'", "parent='$old_name'");
		
					
		rebuild_tree('root', 1, $evname);
		if ($evname=='article'){
			safe_update("textpattern","Category1='$name'", "Category1 = '$old_name'"); 
			safe_update("textpattern","Category2='$name'", "Category2 = '$old_name'"); 
		}else {
			safe_update($table_name, "category='$name'", "category='$old_name'");
		}
		cat_category_list(messenger($evname.'_category',stripslashes($name),'saved'));
	}

	
// --------------------------------------------------------------
// Non image file upload. Have I mentioned how much I love this file refactoring?
// -------------------------------------------------------------
	function cat_file_list() 
	{
		return cat_event_category_list('file');
	}

//-------------------------------------------------------------
	function cat_file_create()
	{
		return cat_event_category_create('file');
	}

//-------------------------------------------------------------
	function cat_file_edit()
	{
		return cat_event_category_edit('file');
	}

//-------------------------------------------------------------
	function cat_file_save()
	{
		return cat_event_category_save('file','txp_file');
	}
	
	
?>

<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance ofthe Textpattern license agreement 
*/

	require_privs('category');

	if(!$step or !function_exists($step)){
		category_list();
	} else $step();

//-------------------------------------------------------------
	function category_list($message="")
	{
		pagetop(gTxt('categories'),$message);
		$out = array('<table cellspacing="20" align="center">',
		'<tr>',
			tdtl(article_list(),' class="categories"'),
			tdtl(link_list(),' class="categories"'),
			tdtl(image_list(),' class="categories"'),
		'</tr>',
		endTable());
		echo join(n,$out);
	}

 
//-------------------------------------------------------------
	function article_list() 
	{
		return event_category_list('article');
	}

//-------------------------------------------------------------
	function article_create()
	{
		return event_category_create('article');
	}

//-------------------------------------------------------------
	function article_edit()
	{
		return event_category_edit('article');
	}

//-------------------------------------------------------------
	function article_save()
	{
		return event_category_save('article', 'textpattern');
	}

//--------------------------------------------------------------
	function parent_pop($name,$type)
	{
		$rs = getTree("root",$type);
		if ($rs) {
			return ' '.treeSelectInput('parent', $rs, $name);
		}
		return 'no categories created';
	}




// -------------------------------------------------------------
	function link_list() 
	{
		return event_category_list('link');
	}

//-------------------------------------------------------------
	function link_create()
	{
		return event_category_create('link');
	}

//-------------------------------------------------------------
	function link_edit()
	{
		return event_category_edit('link');
	}

//-------------------------------------------------------------
	function link_save()
	{
		return event_category_save('link', 'txp_link');
	}

// -------------------------------------------------------------
	function image_list() 
	{
		return event_category_list('image');
	}

//-------------------------------------------------------------
	function image_create()
	{
		return event_category_create('image');
	}

//-------------------------------------------------------------
	function image_edit()
	{
		return event_category_edit('image');
	}

//-------------------------------------------------------------
	function image_save()
	{
		return event_category_save('image', 'txp_image');
	}


// -------------------------------------------------------------
	function article_multiedit_form($area, $array) 
	{
		$methods = array('delete'=>gTxt('delete'));
		if ($array) {
		return 
		form(
			join('',$array).
			eInput('category').sInput('category_multiedit').hInput('type',$area).
			small(gTxt('with_selected')).sp.selectInput('method',$methods,'',1).sp.
			fInput('submit','',gTxt('go'),'smallerbox')
			,'margin-top:1em',"verify('".gTxt('are_you_sure')."')"
		);
		} return;
	}

// -------------------------------------------------------------
	function category_multiedit() 
	{
		$type = ps('type');
		$method = ps('method');
		$things = ps('selected');
		if ($things) {
			foreach($things as $name) {
				$name = doSlash($name);
				if ($method == 'delete') {
					if (safe_delete('txp_category',"name='$name' and type='$type'")) {
						$categories[] = $name;
					}
				}
			}
			category_list(messenger($type.'_category',join(', ',$categories),'deleted'));
		}
	}

//Refactoring: Functions are more or less the same for all event types
// so, merge them. Addition of new event categories is easiest now.

//-------------------------------------------------------------
	function event_category_list($evname) 
	{
		global $txpac;
		if($evname=='article') $headspan = ($txpac['show_article_category_count']) ? 3 : 2;		

		$o = hed(gTxt($evname.'_head').popHelp($evname.'_category'),3);

		$o .= 
			form(
				fInput('text','name','','edit','','',10).
				fInput('submit','',gTxt('Create'),'smallerbox').
				eInput('category').
				sInput($evname.'_create')
			);

		$rs = getTree('root',$evname);
			
		if($rs) {
			foreach ($rs as $a) {
				extract($a);
				if ($name=='root') continue;
				//Stuff for articles only
				if ($evname=='article' && $txpac['show_article_category_count']) {
					$sname = doSlash($name);
					$count = sp . small(safe_count("textpattern",
						"((Category1='$sname') or (Category2='$sname'))"));
				} else $count = '';

				$cbox = checkbox('selected[]',$name,0);
				$editlink = eLink('category',$evname.'_edit','name',
					$name,htmlspecialchars($name));

				$items[] = graf( $cbox . sp . str_repeat(sp,$level-1) . $editlink . $count);

			}

			if (!empty($items)) $o .= article_multiedit_form($evname,$items);

		}
			return $o;
	}

//-------------------------------------------------------------
	function event_category_create($evname)
	{
		$name = gps('name');
		$name = trim(doSlash($name));

		$check = safe_field("name", "txp_category", "name='$name' and type='$evname'");

		if (!$check) {
			if($name) {
				
				$q = 
				safe_insert("txp_category", "name='$name', type='$evname', parent='root'");
				
				rebuild_tree('root', 1, $evname);
				
				if ($q) category_list(messenger($evname.'_category',$name,'created'));
			} else {
				category_list();
			}
		} else {
			category_list(messenger($evname.'_category',$name,'already_exists'));		
		}
	}

//-------------------------------------------------------------
	function event_category_edit($evname)
	{
		pagetop(gTxt('categories'));

		extract(gpsa(array('name','parent')));
		$row = safe_row("*", "txp_category", "name='$name' and type='$evname'");
		if($row){
			extract($row);
			$out = stackRows(
				fLabelCell($evname.'_category_name') . fInputCell('name', $name, 1, 20),
				fLabelCell('parent') . td(parent_pop($parent,$evname)),
				tdcs(fInput('submit', '', gTxt('save_button'),'smallerbox'), 2)
			);
		}
		$out.= eInput( 'category' ) . sInput( $evname.'_save' ) . hInput( 'old_name',$name );
		echo form( startTable( 'edit' ) . $out . endTable() );
	}

//-------------------------------------------------------------
	function event_category_save($evname,$table_name)
	{
		$in = gpsa(array('name','old_name','parent'));
		extract(doSlash($in));
		$parent = ($parent) ? $parent : 'root';
		safe_update("txp_category", 
					"name='$name',parent='$parent'", 
					"name='$old_name' and type='$evname'"); 
		rebuild_tree('root', 1, $evname);
		if ($evname=='article'){
			safe_update("textpattern","Category1='$name'", "Category1 = '$old_name'"); 
			safe_update("textpattern","Category2='$name'", "Category2 = '$old_name'"); 
		}else {
			safe_update($table_name, "category='$name'", "category='$old_name'");
		}
		category_list(messenger($evname.'_category',stripslashes($name),'saved'));
	}
	
?>

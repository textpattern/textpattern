<?php

/*
	This is Textpattern

	Copyright 2004 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance ofthe Textpattern license agreement 
*/

	check_privs(1,2,3);

	if(!$step or !function_exists($step)){
		category_list();
	} else $step();

//-------------------------------------------------------------
	function category_list($message="")
	{
		global $use_sections,$use_categories;
		pagetop(gTxt('categories'),$message);
		$out = array('<table cellspacing="20" align="center">',
		'<tr>',
			($use_categories) ? tdtl(article_list(),' class="categories"') : '',
			tdtl(link_list(),' class="categories"'),
			tdtl(image_list(),' class="categories"'),
		'</tr>',
		endTable());
		echo join(n,$out);
	}

 
//-------------------------------------------------------------
	function article_list() 
	{
		global $url_mode,$txpac;
		$headspan = ($txpac['show_article_category_count']) ? 3 : 2;

		$o = hed(gTxt('article_head').popHelp('article_category'),3);

		$o .= 
			form(
				fInput('text','name','','edit','','',10).
				fInput('submit','',gTxt('Create'),'smallerbox').
				eInput('category').
				sInput('article_create')
			);

		$rs = getTree('root','article');
			
		if($rs) {
			foreach ($rs as $a) {
				extract($a);
				if ($name=='root') continue;
				if ($txpac['show_article_category_count']) {
					$sname = doSlash($name);
					$count = sp . small(safe_count("textpattern",
						"((Category1='$sname') or (Category2='$sname'))"));
				} else $count = '';

				$cbox = checkbox('selected[]',$name,0);
				$editlink = eLink('category','article_edit','name',
					$name,htmlspecialchars($name));

				$items[] = graf( $cbox . sp . str_repeat(sp,$level-1) . $editlink . $count);

			}

			if (!empty($items)) $o .= article_multiedit_form('article',$items);

		}
			return $o;
	}

//-------------------------------------------------------------
	function article_create()
	{
		$name = gps('name');
		$name = trim(doSlash($name));

		$check = safe_field("name", "txp_category", "name='$name' and type='article'");

		if (!$check) {
			if($name) {
				
				$q = 
				safe_insert("txp_category", "name='$name', type='article', parent='root'");
				
				rebuild_tree('root', 1, 'article');
				
				if ($q) category_list(messenger('article_category',$name,'created'));
			} else {
				category_list();
			}
		} else {
			category_list(messenger('article_category',$name,'already_exists'));		
		}
	}

//-------------------------------------------------------------
	function article_edit()
	{
		pagetop(gTxt('categories'));

		extract(doSlash(gpsa(array('name','parent'))));
		$row = safe_row("*", "txp_category", "name='$name' and type='article'");
		if($row){
			extract($row);
			$out = stackRows(
				fLabelCell('article_category_name') . fInputCell('name', $name, 1, 20),
				fLabelCell('parent') . td(parent_pop($parent,'article')),
				tdcs(fInput('submit', '', gTxt('save_button'),'smallerbox'), 2)
			);
		}
		$out.= eInput( 'category' ) . sInput( 'article_save' ) . hInput( 'old_name',$name );
		echo form( startTable( 'edit' ) . $out . endTable() );
	}

//-------------------------------------------------------------
	function article_save()
	{
		$in = gpsa(array('name','old_name','parent'));
		extract(doSlash($in));
		$parent = ($parent) ? $parent : 'root';
		safe_update("txp_category", 
					"name='$name',parent='$parent'", 
					"name='$old_name' and type='article'"); 
		rebuild_tree('root', 1, 'article');
		safe_update("textpattern","Category1='$name'", "Category1 = '$old_name'"); 
		safe_update("textpattern","Category2='$name'", "Category2 = '$old_name'"); 
		category_list(messenger('article_category',stripslashes($name),'saved'));
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
		global $url_mode;
		$o = hed(gTxt('link_head').popHelp('link_category'),3);
		$o .= 
			form(
				fInput('text','name','','edit','','',10).
				fInput('submit','',gTxt('Create'),'smallerbox').
				eInput('category').
				sInput('link_create')
			);
	
		$rs = getTree('root','link');

		if($rs) {
			foreach ($rs as $a) {
				extract($a);
				if($name=='root') continue;
				$cbox = checkbox('selected[]',$name,0);
				$editlink = eLink('category','link_edit','name',
					$name,htmlspecialchars($name));

				$items[] = graf( $cbox . sp . str_repeat(sp,$level-1).$editlink );
			}

			if (!empty($items)) $o .= article_multiedit_form('link',$items);

		}
		return $o;
	}

//-------------------------------------------------------------
	function link_create()
	{
		$name = ps('name');
		$name = trim(doSlash($name));

		$check = safe_field("name", "txp_category", "name='$name' and type='link'");
		if (!$check) {
			if ($name) {
				safe_insert("txp_category", "name='$name', type='link', parent='root'"); 
				rebuild_tree('root', 1, 'link');
				category_list(messenger('link_category',$name,'created'));
			} else category_list();
		} else category_list(messenger('link_category',$name,'already_exists'));
	}

//-------------------------------------------------------------
	function link_edit()
	{
		pagetop(gTxt('categories'));
		$name = doSlash(gps('name'));
		extract(safe_row("*", "txp_category", "name='$name' and type='link'"));
		$out = 
		tr( fLabelCell(gTxt('link_category_name').':').fInputCell('name',$name,1,20)).
		tr( fLabelCell('parent') . td(parent_pop($parent,'link'))).
		tr( tdcs(fInput('submit','', gTxt('save_button'),'smallerbox'), 2));
		$out.= eInput('category').sInput('link_save').hInput('old_name',$name);
		echo form(startTable('edit').$out.endTable());
	}

//-------------------------------------------------------------
	function link_save()
	{
		$in = gpsa(array('name','old_name','parent'));
		extract(doSlash($in));
		$parent = ($parent) ? $parent : 'root';
		safe_update("txp_category", 
					"name='$name',parent='$parent'",
					"name='$old_name' and type='link'"); 
		rebuild_tree('root', 1, 'link');
		safe_update("txp_link", "category='$name'", "category='$old_name'"); 
		category_list(messenger('link_category',$name,'saved'));
	}




// -------------------------------------------------------------
	function image_list() 
	{
		global $url_mode;
		$o = 
			hed(gTxt('image_head').popHelp('image_category'),3);
		$o .= 
			form(
				fInput('text','name','','edit','','',10).
				fInput('submit','',gTxt('Create'),'smallerbox').
				eInput('category').
				sInput('image_create')
			);
	
		$rs = getTree('root','image');

		if($rs) {
			foreach ($rs as $a) {
				extract($a);
				if ($name == 'root') continue;

				$cbox = checkbox('selected[]',$name,0);

				$editimage = eLink('category','image_edit','name',
					$name,htmlspecialchars($name));

				$items[] = graf( $cbox . sp . str_repeat(sp,$level-1).$editimage);
			}
		
			if (!empty($items)) $o .= article_multiedit_form('image',$items);
		}
		return $o;
	}

//-------------------------------------------------------------
	function image_create()
	{
		$name = trim(doSlash(ps('name')));

		$checkdb = safe_field("name","txp_category", "name='$name' and type='image'");

		if (!$checkdb) {
			if ($name) {
				$q = safe_insert("txp_category", "name='$name',type='image',parent='root'");
				rebuild_tree('root', 1, 'image');
				if ($q) category_list(messenger('image_category',$name,'created'));
			} else category_list();
		} else category_list(messenger('image_category',$name,'already_exists'));
	}

//-------------------------------------------------------------
	function image_edit()
	{
		pagetop(gTxt('categories'));
		$name = doSlash(gps('name'));
		extract(safe_row("*","txp_category", "name='$name' and type='image'"));
		$out = 
		tr(fLabelCell(gTxt('image_category_name').':').fInputCell('name',$name,1,20)).
		tr( fLabelCell('parent') . td(parent_pop($parent,'image'))).
		tr(tdcs(fInput('submit','', gTxt('save_button'),'smallerbox'), 2));
		$out.= eInput('category').sInput('image_save').hInput('old_name',$name);
		echo form(startTable('edit').$out.endTable());
	}

//-------------------------------------------------------------
	function image_save()
	{
		extract(doSlash(gpsa(array('name','old_name','parent'))));

		$parent = ($parent) ? $parent : 'root';
		safe_update(
			"txp_category", 
			"name='$name', parent='$parent'",
			"name='$old_name' and type='image'"); 
		rebuild_tree('root', 1, 'image');
		safe_update("txp_image", "category='$name'", "category='$old_name'"); 
		category_list(messenger('image_category',$name,'saved'));
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

?>

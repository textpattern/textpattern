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

	function cat_parent_pop($name, $type, $id)
	{
		if ($id)
		{
			$id = assert_int($id);
			list($lft, $rgt) = array_values(safe_row('lft, rgt', 'txp_category', 'id = '.$id));

			$rs = getTree('root', $type, "lft not between $lft and $rgt");
		}

		else
		{
			$rs = getTree('root', $type);
		}

		if ($rs)
		{
			return treeSelectInput('parent', $rs, $name);
		}

		return gTxt('no_other_categories_exist');
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
			small(gTxt('with_selected')).sp.selectInput('edit_method',$methods,'',1).sp.
			fInput('submit','',gTxt('go'),'smallerbox')
			,'margin-top:1em',"verify('".gTxt('are_you_sure')."')"
		);
		} return;
	}

// -------------------------------------------------------------
	function cat_category_multiedit()
	{
		$type = ps('type');
		$method = ps('edit_method');
		$things = ps('selected');
		if ($things) {
			foreach($things as $catid) {
				$catid = assert_int($catid);
				if ($method == 'delete') {
					$catname = safe_field('name', 'txp_category', "id=$catid");
					if (safe_delete('txp_category',"id=$catid")) {
						if ($catname)
							safe_update('txp_category', "parent='root'", "type='".doSlash($type)."' and parent='".doSlash($catname)."'");
						$categories[] = $catid;
					}
				}
			}
			rebuild_tree_full($type);

			$message = gTxt($type.'_categories_deleted', array('{list}' => join(', ',$categories)));

			cat_category_list($message);
		}
	}

//Refactoring: Functions are more or less the same for all event types
// so, merge them. Addition of new event categories is easiest now.

//-------------------------------------------------------------

	function cat_event_category_list($event) 
	{
		$out = n.n.hed(gTxt($event.'_head').sp.popHelp($event.'_category'), 3).

			form(
				fInput('text', 'title', '', 'edit', '', '', 20).
				fInput('submit', '', gTxt('Create'), 'smallerbox').
				eInput('category').
				sInput('cat_'.$event.'_create')
			);

		$rs = getTree('root', $event);

		if ($rs)
		{
			$total_count = array();

			if ($event == 'article')
			{
				$rs2 = safe_rows_start('Category1, count(*) as num', 'textpattern', "1 = 1 group by Category1");

				while ($a = nextRow($rs2))
				{
					$name = $a['Category1'];
					$num = $a['num'];

					$total_count[$name] = $num;
				}

				$rs2 = safe_rows_start('Category2, count(*) as num', 'textpattern', "1 = 1 group by Category2");

				while ($a = nextRow($rs2))
				{
					$name = $a['Category2'];
					$num = $a['num'];

					if (isset($total_count[$name]))
					{
						$total_count[$name] += $num;
					}

					else
					{
						$total_count[$name] = $num;
					}
				}
			}

			else
			{
				switch ($event)
				{
					case 'link':
						$rs2 = safe_rows_start('category, count(*) as num', 'txp_link', "1 group by category");
					break;

					case 'image':
						$rs2 = safe_rows_start('category, count(*) as num', 'txp_image', "1 group by category");
					break;

					case 'file':
						$rs2 = safe_rows_start('category, count(*) as num', 'txp_file', "1 group by category");
					break;
				}

				while ($a = nextRow($rs2))
				{
					$name = $a['category'];
					$num = $a['num'];

					$total_count[$name] = $num;
				}
			}

			$items = array();

			foreach ($rs as $a)
			{
				extract($a);

				// format count
				switch ($event)
				{
					case 'article':
						$url = 'index.php?event=list'.a.'search_method=categories'.a.'crit='.$name;
					break;
	
					case 'link':
						$url = 'index.php?event=link'.a.'search_method=category'.a.'crit='.$name;
					break;
	
					case 'image':
						$url = 'index.php?event=image'.a.'search_method=category'.a.'crit='.$name;
					break;
	
					case 'file':
						$url = 'index.php?event=file'.a.'search_method=category'.a.'crit='.$name;
					break;
				}

				$count = isset($total_count[$name]) ? '('.href($total_count[$name], $url).')' : '(0)';

				$items[] = graf(
					checkbox('selected[]', $id, 0).sp.str_repeat(sp.sp, $level * 2).
					eLink('category', 'cat_'.$event.'_edit', 'id', $id, $title).sp.small($count)
				);
			}

			if ($items)
			{
				$out .= cat_article_multiedit_form($event, $items);
			}
		}

		else
		{
			$out .= graf(gTxt('no_categories_exist'));
		}

		return $out;
	}

//-------------------------------------------------------------

	function cat_event_category_create($event)
	{
		global $txpcfg;

		$title = ps('title');

		$name = sanitizeForUrl($title);

		if (!$name)
		{
			$message = gTxt($event.'_category_invalid', array('{name}' => $name));

			return cat_category_list($message);
		}

		$exists = safe_field('name', 'txp_category', "name = '".doSlash($name)."' and type = '".doSlash($event)."'");

		if ($exists)
		{
			$message = gTxt($event.'_category_already_exists', array('{name}' => $name));

			return cat_category_list($message);
		}

		$q = safe_insert('txp_category', "name = '".doSlash($name)."', title = '".doSlash($title)."', type = '".doSlash($event)."', parent = 'root'");

		if ($q)
		{
			rebuild_tree_full($event);

			$message = gTxt($event.'_category_created', array('{name}' => $name));

			cat_category_list($message);
		}
	}

//-------------------------------------------------------------
	function cat_event_category_edit($evname)
	{
		pagetop(gTxt('categories'));

		$id     = assert_int(gps('id'));
		$parent = doSlash(gps('parent'));

		$row = safe_row("*", "txp_category", "id=$id");
		if($row){
			extract($row);
			$out = stackRows(
				fLabelCell($evname.'_category_name') . fInputCell('name', $name, 1, 20),
				fLabelCell('parent') . td(cat_parent_pop($parent,$evname,$id)),
				fLabelCell($evname.'_category_title') . fInputCell('title', $title, 1, 30),
				hInput('id',$id),
				tdcs(fInput('submit', '', gTxt('save_button'),'smallerbox'), 2)
			);
		}
		$out.= eInput( 'category' ) . sInput( 'cat_'.$evname.'_save' ) . hInput( 'old_name',$name );
		echo form( startTable( 'edit' ) . $out . endTable() );
	}

//-------------------------------------------------------------

	function cat_event_category_save($event, $table_name)
	{
		global $txpcfg;

		extract(doSlash(psa(array('id', 'name', 'old_name', 'parent', 'title'))));
		$id = assert_int($id);

		$name = sanitizeForUrl($name);

		// make sure the name is valid
		if (!$name)
		{
			$message = gTxt($event.'_category_invalid', array('{name}' => $name));

			return cat_category_list($message);
		}

		// don't allow rename to clobber an existing category
		$existing_id = safe_field('id', 'txp_category', "name = '$name' and type = '$event'");

		if ($existing_id and $existing_id != $id)
		{
			$message = gTxt($event.'_category_already_exists', array('{name}' => $name));

			return cat_category_list($message);
		}

		$parent = ($parent) ? $parent : 'root';

		if (safe_update('txp_category', "name = '$name', parent = '$parent', title = '$title'", "id = $id"))
		{
			safe_update('txp_category', "parent = '$name'", "parent = '$old_name'");
		}

		rebuild_tree_full($event);

		if ($event == 'article')
		{
			safe_update('textpattern', "Category1 = '$name'", "Category1 = '$old_name'");
			safe_update('textpattern', "Category2 = '$name'", "Category2 = '$old_name'");
		}

		else
		{
			safe_update($table_name, "category = '$name'", "category = '$old_name'");
		}

		$message = gTxt($event.'_category_updated', array('{name}' => doStrip($name)));

		cat_category_list($message);
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

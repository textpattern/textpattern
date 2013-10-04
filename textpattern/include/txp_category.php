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

if ($event == 'category') {
	require_privs('category');

	$available_steps = array(
		'cat_category_list'      => false,
		'cat_category_multiedit' => true,
		'cat_article_create'     => true,
		'cat_image_create'       => true,
		'cat_file_create'        => true,
		'cat_link_create'        => true,
		'cat_article_save'       => true,
		'cat_image_save'         => true,
		'cat_file_save'          => true,
		'cat_link_save'          => true,
		'cat_article_edit'       => false,
		'cat_image_edit'         => false,
		'cat_file_edit'          => false,
		'cat_link_edit'          => false,
	);

	if ($step && bouncer($step, $available_steps)){
		$step();
	} else {
		cat_category_list();
	}
}

//-------------------------------------------------------------
	function cat_category_list($message="")
	{
		pagetop(gTxt('categories'),$message);
		$out = array('<h1 class="txp-heading">'.gTxt('tab_organise').'</h1>',
		'<div id="category_container" class="txp-container">',
		'<table class="category-list">',
		'<tr>',
			tdtl('<div id="categories_article">'.cat_article_list().'</div>',' class="categories article"'),
			tdtl('<div id="categories_link">'.cat_link_list().'</div>',' class="categories link"'),
			tdtl('<div id="categories_image">'.cat_image_list().'</div>',' class="categories image"'),
			tdtl('<div id="categories_file">'.cat_file_list().'</div>',' class="categories file"'),
		'</tr>',
		endTable(),
		'</div>',
		script_js( <<<EOS
			$(document).ready(function() {
				$('.category-tree').txpMultiEditForm({
					'row' : 'p',
					'highlighted' : 'p'
				});
			});
EOS
		));
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
			return array(treeSelectInput('parent', $rs, $name, 'category_parent'), true);
		}

		return array(gTxt('no_other_categories_exist'), false);
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
		$rs = getTree('root', $area);
		$categories = $rs ? treeSelectInput('new_parent', $rs, '') : '';

		$methods = array(
			'changeparent' => array('label' => gTxt('changeparent'), 'html' => $categories),
			'deleteforce'  => gTxt('deleteforce'),
			'delete'       => gTxt('delete'),
		);

		if ($array) {
			return
				form(
					join('',$array).
					hInput('type',$area).
					n.multi_edit($methods, 'category', 'cat_category_multiedit', '', '', '', '', '', $area)
					,'', '', 'post', 'category-tree', '', 'category_'.$area.'_form'
				);
		}
		return;
	}

// -------------------------------------------------------------
	function cat_category_multiedit()
	{
		$type = ps('type');
		$method = ps('edit_method');
		$things = ps('selected');

		if (is_array($things) and $things and in_array($type, array('article','image','link','file')))
		{
			$things = array_map('assert_int', $things);

			if ($method == 'delete' || $method == 'deleteforce')
			{
				if ($type === 'article')
				{
					$used = 'name NOT IN(SELECT category1 FROM '.safe_pfx('textpattern').')'.
						' AND name NOT IN(SELECT category2 FROM '.safe_pfx('textpattern').')';
				}
				else
				{
					$used = 'name NOT IN(SELECT category FROM '.safe_pfx('txp_'.$type).')';
				}

				$rs = safe_rows('id, name', 'txp_category', "id IN (".join(',', $things).") AND type='".$type."'" . (($method == 'deleteforce') ? '' : " AND rgt - lft = 1 AND ".$used));

				if ($rs)
				{
					foreach($rs as $cat)
					{
						$catid[] = $cat['id'];
						$names[] = doSlash($cat['name']);
					}

					if (safe_delete('txp_category','id IN ('.join(',', $catid).')'))
					{
						if ($method == 'deleteforce')
						{
							// Clear the deleted category names from assets
							$affected = join("','", $names);
							if($type === 'article')
							{
								safe_update('textpattern', "category1 = ''", "category1 IN ('$affected')");
								safe_update('textpattern', "category2 = ''", "category2 IN ('$affected')");
							}
							else
							{
								safe_update('txp_'.$type, "category = ''", "category IN ('$affected')");
							}

							// Promote subcats of deleted cats to root
							safe_update('txp_category', "parent='root'", "parent IN ('$affected')");
						}

						rebuild_tree_full($type);
						callback_event('categories_deleted', $type, 0, $catid);

						$message = gTxt($type.'_categories_deleted', array('{list}' => join(', ',$catid)));

						return cat_category_list($message);
					}
				}
			}

			else if ($method == 'changeparent')
			{
				$new_parent = ps('new_parent');

				$rs = safe_rows('id, name', 'txp_category', "id IN (".join(',', $things).") AND type='".$type."'");

				if ($rs)
				{
					$exists = safe_field('name', 'txp_category', "name = '".doSlash($new_parent)."' AND type='$type'");
					$parent = ($exists == '') ? 'root' : $exists;
					$to_change = $affected = array();

					foreach($rs as $cat)
					{
						// Cannot assign parent to itself
						if ($cat['name'] != $new_parent)
						{
							$to_change[] = doSlash($cat['name']);
							$affected[] = $cat['name'];
						}
					}

					$ret = safe_update('txp_category', "parent='".doSlash($parent)."'", "name IN ('".join("','", $to_change)."') AND type='".$type."'");

					if ($ret)
					{
						rebuild_tree_full($type);

						$message = gTxt('categories_set_parent', array('{type}' => gTxt($type), '{parent}' => $parent, '{list}' => join(', ',$affected)));

						return cat_category_list($message);
					}
				}
			}
		}

		return cat_category_list();
	}

//Refactoring: Functions are more or less the same for all event types
// so, merge them. Addition of new event categories is easiest now.

//-------------------------------------------------------------

	function cat_event_category_list($event)
	{
		$rs = getTree('root', $event);

		$parent = ps('parent_cat');

		$heading = 'tab_' . ($event == 'article' ? 'list' : $event);
		$for = $rs ? ' for="'.$event.'_category_parent"' : '';

		$out = n.n.hed(gTxt($heading).sp.popHelp($event.'_category'), 2).
			form(
				fInput('text', 'title', '', '', '', '', INPUT_REGULAR).
				(($rs) ? '<div class="parent"><label'.$for.'>' . gTxt('parent') . '</label>' . treeSelectInput('parent_cat', $rs, $parent, $event.'_category_parent') . '</div>' : '').
				n.fInput('submit', '', gTxt('Create')).
				n.eInput('category').
				n.sInput('cat_'.$event.'_create')
			,'', '', 'post', 'action-create '.$event);

		if ($rs)
		{
			$total_count = array();

			if ($event == 'article')
			{
				// Count distinct articles for both categories, avoid duplicates
				$rs2 = getRows(
				    'select category, count(*) as num from ('.
						'select ID, Category1 as category from '.safe_pfx('textpattern').
						' union '.
						'select ID, Category2 as category from '.safe_pfx('textpattern').
					') as t where category != "" group by category');

				if ($rs2 !== false)
				{
					foreach ($rs2 as $a)
					{
						$total_count[$a['category']] = $a['num'];
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

				$count = isset($total_count[$name]) ? href('('.$total_count[$name].')', $url) : '(0)';

				if (empty($title)) {
					$edit_link = '<em>'.eLink('category', 'cat_'.$event.'_edit', 'id', $id, gTxt('untitled')).'</em>';
				} else {
					$edit_link = eLink('category', 'cat_'.$event.'_edit', 'id', $id, $title);
				}

				$items[] = graf(
					checkbox('selected[]', $id, 0).sp.str_repeat(sp.sp, $level * 2).$edit_link.sp.$count
				, ' class="level-'.$level.'"');
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
		$title = ps('title');

		$name = strtolower(sanitizeForUrl($title));

		if (!$name)
		{
			$message = array(gTxt($event.'_category_invalid', array('{name}' => $name)), E_ERROR);

			return cat_category_list($message);
		}

		$exists = safe_field('name', 'txp_category', "name = '".doSlash($name)."' and type = '".doSlash($event)."'");

		if ($exists)
		{
			$message = array(gTxt($event.'_category_already_exists', array('{name}' => $name)), E_ERROR);

			return cat_category_list($message);
		}

		$parent = strtolower(sanitizeForUrl(ps('parent_cat')));
		$parent_exists = safe_field('name', 'txp_category', "name = '".doSlash($parent)."' and type = '".doSlash($event)."'");
		$parent = ($parent_exists) ? $parent_exists : 'root';

		$q = safe_insert('txp_category', "name = '".doSlash($name)."', title = '".doSlash($title)."', type = '".doSlash($event)."', parent = '".$parent."'");

		if ($q)
		{
			rebuild_tree_full($event);

			$message = gTxt($event.'_category_created', array('{name}' => $name));

			cat_category_list($message);
		}
		else
		{
			cat_category_list(array(gTxt('category_save_failed'), E_ERROR));
		}
	}

//-------------------------------------------------------------

	function cat_event_category_edit($evname)
	{
		$id     = assert_int(gps('id'));
		$parent = doSlash(gps('parent'));

		$row = safe_row('*', 'txp_category', "id=$id");
		if ($row) {
			pagetop(gTxt('edit_category'));
			extract($row);
			list($parent_widget, $has_parent) = cat_parent_pop($parent,$evname,$id);
			$out = '<div class="txp-edit">'.n.
				hed(gTxt('edit_category'), 2).n.
				inputLabel('category_name', fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'category_name'), $evname.'_category_name').n.
				($has_parent ? inputLabel('category_parent', $parent_widget, 'parent') : graf('<span class="edit-label">'.gTxt('parent').'</span><span class="edit-value">'.$parent_widget.'</span>')).n.
				inputLabel('category_title', fInput('text', 'title', $title, '', '', '', INPUT_REGULAR, '', 'category_title'), $evname.'_category_title').n.
				pluggable_ui('category_ui', 'extend_detail_form', '', $row).n.
				hInput('id',$id).
				graf(fInput('submit', '', gTxt('save'), 'publish')).
				eInput('category').
				sInput('cat_'.$evname.'_save').
				hInput('old_name',$name).
				'</div>';
			echo '<div id="category_container" class="txp-container">'.
				form(  $out, '', '', 'post', 'edit-form' ).
				'</div>';
		} else {
			cat_category_list(array(gTxt('category_not_found'), E_ERROR));
		}
	}

//-------------------------------------------------------------

	function cat_event_category_save($event, $table_name)
	{
		extract(doSlash(array_map('assert_string', psa(array('id', 'name', 'old_name', 'parent', 'title')))));
		$id = assert_int($id);

		$name = sanitizeForUrl($name);

		// make sure the name is valid
		if (!$name)
		{
			$message = array(gTxt($event.'_category_invalid', array('{name}' => $name)), E_ERROR);

			return cat_category_list($message);
		}

		// don't allow rename to clobber an existing category
		$existing_id = safe_field('id', 'txp_category', "name = '$name' and type = '$event'");

		if ($existing_id and $existing_id != $id)
		{
			$message = array(gTxt($event.'_category_already_exists', array('{name}' => $name)), E_ERROR);

			return cat_category_list($message);
		}

		//TODO: validate parent?
		$parent = ($parent) ? $parent : 'root';

		$message = array(gTxt('category_save_failed'), E_ERROR);
		if (safe_update('txp_category', "name = '$name', parent = '$parent', title = '$title'", "id = $id") &&
			safe_update('txp_category', "parent = '$name'", "parent = '$old_name' and type='$event'"))
		{
			rebuild_tree_full($event);

			if ($event == 'article')
			{
				if (safe_update('textpattern', "Category1 = '$name'", "Category1 = '$old_name'") &&
					safe_update('textpattern', "Category2 = '$name'", "Category2 = '$old_name'"))
				{
					$message = gTxt($event.'_category_updated', array('{name}' => doStrip($name)));
				}
			}
			else
			{
				if (safe_update($table_name, "category = '$name'", "category = '$old_name'"))
				{
					$message = gTxt($event.'_category_updated', array('{name}' => doStrip($name)));
				}
			}
		}
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

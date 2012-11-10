<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	global $extensions;
	$extensions = get_safe_image_types();

	include txpath.'/lib/class.thumb.php';

	if ($event == 'image')
	{
		require_privs('image');

		global $all_image_cats, $all_image_authors;
		$all_image_cats = getTree('root', 'image');
		$all_image_authors = the_privileged('image.edit.own');

		$available_steps = array(
			'image_list'          => false,
			'image_edit'          => false,
			'image_insert'        => true,
			'image_replace'       => true,
			'image_save'          => true,
			'thumbnail_insert'    => true,
			'image_change_pageby' => true,
			'thumbnail_create'    => true,
			'thumbnail_delete'    => true,
			'image_multi_edit'    => true,
		);

		if ($step && bouncer($step, $available_steps)) {
			$step();
		} else {
			image_list();
		}
	}

// -------------------------------------------------------------

	function image_list($message = '')
	{
		global $txpcfg, $extensions, $img_dir, $file_max_upload_size, $image_list_pageby, $txp_user, $event;

		pagetop(gTxt('tab_image'), $message);

		extract($txpcfg);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('image_sort_column', 'id');
		if ($dir === '') $dir = get_pref('image_sort_dir', 'desc');
		$dir = ($dir == 'asc') ? 'asc' : 'desc';

		echo n.'<h1 class="txp-heading">'.gTxt('tab_image').'</h1>';
		echo n.'<div id="'.$event.'_control" class="txp-control-panel">';

		if (!is_dir(IMPATH) or !is_writeable(IMPATH))
		{
			echo graf(
				gTxt('img_dir_not_writeable', array('{imgdir}' => IMPATH))
			,' class="alert-block warning"');
		}

		elseif (has_privs('image.edit.own'))
		{
			echo upload_form(gTxt('upload_image'), 'upload_image', 'image_insert', 'image', '', $file_max_upload_size);
		}

		switch ($sort)
		{
			case 'name':
				$sort_sql = 'name '.$dir;
			break;

			case 'thumbnail':
				$sort_sql = 'thumbnail '.$dir.', id asc';
			break;

			case 'category':
				$sort_sql = 'category '.$dir.', id asc';
			break;

			case 'date':
				$sort_sql = 'date '.$dir.', id asc';
			break;

			case 'author':
				$sort_sql = 'author '.$dir.', id asc';
			break;

			default:
				$sort = 'id';
				$sort_sql = 'id '.$dir;
			break;
		}

		set_pref('image_sort_column', $sort, 'image', 2, '', 0, PREF_PRIVATE);
		set_pref('image_sort_dir', $dir, 'image', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit != '')
		{
			$verbatim = preg_match('/^"(.*)"$/', $crit, $m);
			$crit_escaped = doSlash($verbatim ? $m[1] : str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));
			$critsql = $verbatim ?
				array(
					'id'       => "ID in ('" .join("','", do_list($crit_escaped)). "')",
					'name'     => "name = '$crit_escaped'",
					'category' => "category = '$crit_escaped'",
					'author'   => "author = '$crit_escaped'",
					'alt'      => "alt = '$crit_escaped'",
					'caption'  => "caption = '$crit_escaped'"
				) : array(
					'id'       => "ID in ('" .join("','", do_list($crit_escaped)). "')",
					'name'     => "name like '%$crit_escaped%'",
					'category' => "category like '%$crit_escaped%'",
					'author'   => "author like '%$crit_escaped%'",
					'alt'      => "alt like '%$crit_escaped%'",
					'caption'  => "caption like '%$crit_escaped%'"
				);

			if (array_key_exists($search_method, $critsql))
			{
				$criteria = $critsql[$search_method];
				$limit = 500;
			}

			else
			{
				$search_method = '';
				$crit = '';
			}
		}

		else
		{
			$search_method = '';
			$crit = '';
		}

		$criteria .= callback_event('admin_criteria', 'image_list', 0, $criteria);

		$total = safe_count('txp_image', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.image_search_form($crit, $search_method).
					graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
			}

			else
			{
				echo graf(gTxt('no_images_recorded'), ' class="indicator"').'</div>';
			}

			return;
		}

		$limit = max($image_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo image_search_form($crit, $search_method);

		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_image',
			"$criteria order by $sort_sql limit $offset, $limit
		");

		echo pluggable_ui('image_ui', 'extend_controls', '', $rs);
		echo '</div>'; // end txp-control-panel

		if ($rs)
		{
			$show_authors = !has_single_author('txp_image');

			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo n.'<form name="longform" id="images_form" class="multi_edit_form" method="post" action="index.php">'.

				n.'<div class="txp-listtables">'.
				startTable('', '', 'txp-list').
				n.'<thead>'.
				tr(
					hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' scope="col" title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
					column_head('ID', 'id', 'image', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id').
					column_head('name', 'name', 'image', true, $switch_dir, $crit, $search_method, (('name' == $sort) ? "$dir " : '').'name').
					column_head('date', 'date', 'image', true, $switch_dir, $crit, $search_method, (('date' == $sort) ? "$dir " : '').'images_detail date created').
					column_head('thumbnail', 'thumbnail', 'image', true, $switch_dir, $crit, $search_method, (('thumbnail' == $sort) ? "$dir " : '').'thumbnail').
					hCell(gTxt('tags'), '', ' scope="col" class="images_detail tag-build"').
					column_head('image_category', 'category', 'image', true, $switch_dir, $crit, $search_method, (('category' == $sort) ? "$dir " : '').'category').
					($show_authors ? column_head('author', 'author', 'image', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '')
				).
				n.'</thead>';

			echo n.'<tbody>';

			$validator = new Validator();

			while ($a = nextRow($rs))
			{
				extract($a);

				$edit_url = '?event=image'.a.'step=image_edit'.a.'id='.$id.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

				$name = empty($name) ? gTxt('unnamed') : txpspecialchars($name);

				if ($thumbnail) {
					if ($ext != '.swf') {
						$thumbnail = '<img class="content-image" src="'.imagesrcurl($id, $ext, true)."?$uDate".'" alt="" '.
											"title='$id$ext ($w &#215; $h)'".
											($thumb_w ? " width='$thumb_w' height='$thumb_h'" : ''). ' />';
					} else {
						$thumbnail = '';
					}
				} else {
					$thumbnail = gTxt('no');
				}

				if ($ext != '.swf') {
					$tag_url = '?event=tag'.a.'tag_name=image'.a.'id='.$id.a.'ext='.$ext.a.'w='.$w.a.'h='.$h.a.'alt='.urlencode($alt).a.'caption='.urlencode($caption);
					$tagbuilder = '<a target="_blank" href="'.$tag_url.a.'type=textile" onclick="popWin(this.href); return false;">Textile</a>'.sp.
							'&#124;'.sp.'<a target="_blank" href="'.$tag_url.a.'type=textpattern" onclick="popWin(this.href); return false;">Textpattern</a>'.sp.
							'&#124;'.sp.'<a target="_blank" href="'.$tag_url.a.'type=html" onclick="popWin(this.href); return false;">HTML</a>';
				} else {
					$tagbuilder = sp;
				}

				$validator->setConstraints(array(new CategoryConstraint($category, array('type' => 'image'))));
				$vc = $validator->validate() ? '' : ' error';
				$category = ($category) ? '<span title="'.txpspecialchars(fetch_category_title($category, 'image')).'">'.$category.'</span>' : '';

				$can_edit = has_privs('image.edit') || ($author == $txp_user && has_privs('image.edit.own'));

				echo tr(
					td($can_edit ? fInput('checkbox', 'selected[]', $id) : '&#160;'
					, '', 'multi-edit').

					hCell(
						($can_edit ? href($id, $edit_url, ' title="'.gTxt('edit').'"') : $id).sp.
						'<span class="images_detail"><span role="presentation">[</span><a href="'.imagesrcurl($id, $ext).'">'.gTxt('view').'</a><span role="presentation">]</span></span>'
					, '', ' scope="row" class="id"').

					td(
						($can_edit ? href($name, $edit_url, ' title="'.gTxt('edit').'"') : $name)
					, '', 'name').

					td(
						gTime($uDate)
					, '', 'images_detail date created').

					td(
						pluggable_ui('image_ui', 'thumbnail',
						($can_edit ? href($thumbnail, $edit_url) : $thumbnail)
						, $a)
					, '', 'thumbnail').

					td($tagbuilder, '', 'images_detail tag-build').
					td($category, '', 'category'.$vc).

					($show_authors ? td(
						'<span title="'.txpspecialchars(get_author_name($author)).'">'.txpspecialchars($author).'</span>'
					, '', 'author') : '')
				);
			}

			echo n.'</tbody>'.
				endTable().
				n.'</div>'.
				image_multiedit_form($page, $sort, $dir, $crit, $search_method).
				tInput().
				n.'</form>'.
				graf(
					toggle_box('images_detail'),
					' class="detail-toggle"'
				).
				n.'<div id="'.$event.'_navigation" class="txp-navigation">'.
				nav_form('image', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
				pageby_form('image', $image_list_pageby).
				n.'</div>'.
				n.'</div>';
		}
	}

// -------------------------------------------------------------

	function image_search_form($crit, $method)
	{
		$methods =	array(
			'id'       => gTxt('ID'),
			'name'     => gTxt('name'),
			'category' => gTxt('image_category'),
			'author'   => gTxt('author'),
			'alt'      => gTxt('alt_text'),
			'caption'  => gTxt('caption')
		);

		return search_form('image', 'image_list', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------

	function image_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		global $all_image_cats, $all_image_authors;

		$categories = $all_image_cats ? treeSelectInput('category', $all_image_cats, '') : '';
		$authors = $all_image_authors ? selectInput('author', $all_image_authors, '', true) : '';

		$methods = array(
			'changecategory' => array('label' => gTxt('changecategory'), 'html' => $categories),
			'changeauthor'   => array('label' => gTxt('changeauthor'), 'html' => $authors),
			'delete'         => gTxt('delete'),
		);

		if (!$categories)
		{
			unset($methods['changecategory']);
		}

		if (has_single_author('txp_image'))
		{
			unset($methods['changeauthor']);
		}

		if (!has_privs('image.delete.own') && !has_privs('image.delete'))
		{
			unset($methods['delete']);
		}

		return multi_edit($methods, 'image', 'image_multi_edit', $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function image_multi_edit()
	{
		global $txp_user, $all_image_cats, $all_image_authors;

		// Empty entry to permit clearing the category
		$categories = array('');

		foreach ($all_image_cats as $row) {
			$categories[] = $row['name'];
		}

		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return image_list();
		}

		$selected = array_map('assert_int', $selected);
		$method   = ps('edit_method');
		$changed  = array();
		$key = '';

		switch ($method)
		{
			case 'delete':
				return image_delete($selected);
				break;

			case 'changecategory':
				$val = ps('category');
				if (in_array($val, $categories))
				{
					$key = 'category';
				}
				break;

			case 'changeauthor':
				$val = ps('author');
				if (in_array($val, $all_image_authors))
				{
					$key = 'author';
				}
				break;

			default:
				$key = '';
				$val = '';
				break;
		}

		if (!has_privs('image.edit'))
		{
			if (has_privs('image.edit.own'))
			{
				$selected = safe_column('id', 'txp_image', 'id IN ('.join(',', $selected).') AND author=\''.doSlash($txp_user).'\'');
			}
			else
			{
				$selected = array();
			}
		}

		if ($selected and $key)
		{
			foreach ($selected as $id)
			{
				if (safe_update('txp_image', "$key = '".doSlash($val)."'", "id = $id"))
				{
					$changed[] = $id;
				}
			}
		}

		if ($changed)
		{
			update_lastmod();

			return image_list(gTxt('image_updated', array('{name}' => join(', ', $changed))));
		}

		return image_list();
	}

// -------------------------------------------------------------
	function image_edit($message='',$id='')
	{
		global $prefs, $file_max_upload_size, $txp_user, $event, $all_image_cats;

		if (!$id) $id = gps('id');
		$id = assert_int($id);

		$rs = safe_row("*, unix_timestamp(date) as uDate", "txp_image", "id = $id");

		if ($rs) {
			extract($rs);

			if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own')))
			{
				image_list(gTxt('restricted_area'));
				return;
			}

			pagetop(gTxt('edit_image'),$message);

			extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

			if ($ext != '.swf') {
				$aspect = ($h == $w) ? ' square' : (($h > $w) ? ' portrait' : ' landscape');
				$img_info = $id.$ext.' ('.$w.' &#215; '.$h.')';
				$img = '<div class="fullsize-image"><img class="content-image" src="'.imagesrcurl($id, $ext)."?$uDate".'" alt="'.$img_info.'" title="'.$img_info.'" /></div>';
			} else {
				$img = $aspect = '';
			}

			if ($thumbnail and ($ext != '.swf')) {
				$thumb_info = $id.'t'.$ext.' ('.$thumb_w.' &#215; '.$thumb_h.')';
				$thumb = '<img class="content-image" src="'.imagesrcurl($id, $ext, true)."?$uDate".'" alt="'.$thumb_info.'" '.
							($thumb_w ? 'width="'.$thumb_w.'" height="'.$thumb_h.'" title="'.$thumb_info.'"' : ''). ' />';
			} else {
				$thumb = '';
				if ($thumb_w == 0) {
					$thumb_w = get_pref('thumb_w', 0);
				}
				if ($thumb_h == 0) {
					$thumb_h = get_pref('thumb_h', 0);
				}
			}

			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo
				pluggable_ui(
					'image_ui',
					'fullsize_image',
					$img,
					$rs
				),

				'<section class="txp-edit">',
				hed(gTxt('edit_image'), 2),

				pluggable_ui(
					'image_ui',
					'image_edit',
					wrapGroup('image_edit_group', upload_form('', '', 'image_replace', 'image', $id, $file_max_upload_size, 'image_replace', 'image-replace'), 'replace_image', 'replace-image', 'replace_image_form'),
					$rs
				),

				pluggable_ui(
					'image_ui',
					'thumbnail_image',
					'<div class="thumbnail-edit">'.
					(($thumbnail)
						? $thumb.n.dLink('image','thumbnail_delete','id',$id, '', '', '', '', array($page, $sort, $dir, $crit, $search_method))
						: 	'').
					'</div>',
					$rs
				),

				pluggable_ui(
					'image_ui',
					'thumbnail_edit',
					wrapGroup('thumbnail_edit_group', upload_form('', '', 'thumbnail_insert','image', $id, $file_max_upload_size, 'upload_thumbnail', 'thumbnail-upload'), 'upload_thumbnail', 'thumbnail-upload', 'upload_thumbnail'),
					$rs
				),

				(check_gd($ext))
				? pluggable_ui(
					'image_ui',
					'thumbnail_create',
					wrapGroup(
						'thumbnail_create_group',
						form(
							graf(
								n.'<label for="width">'.gTxt('thumb_width').'</label>'.
								fInput('text', 'width', @$thumb_w, 'input-xsmall', '', '', INPUT_XSMALL, '', 'width').
								n.'<label for="height">'.gTxt('thumb_height').'</label>'.
								fInput('text', 'height', @$thumb_h, 'input-xsmall', '', '', INPUT_XSMALL, '', 'height').
								n.'<label for="crop">'.gTxt('keep_square_pixels').'</label>'.
								checkbox('crop', 1, @$prefs['thumb_crop'], '', 'crop').
								fInput('submit', '', gTxt('Create'))
							, ' class="edit-alter-thumbnail"').
							hInput('id', $id).
							eInput('image').
							sInput('thumbnail_create').
							hInput('sort', $sort).
							hInput('dir', $dir).
							hInput('page', $page).
							hInput('search_method', $search_method).
							hInput('crit', $crit)
						, '', '', 'post', 'edit-form', '', 'thumbnail_alter_form'),
						'create_thumbnail',
						'thumbnail-alter',
						'create_thumbnail'
						),
					$rs
				)
				: '',

				'<div class="image-detail">',
					form(
						inputLabel('image_name', fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'image_name'), 'image_name').
						inputLabel('image_category', treeSelectInput('category', $all_image_cats, $category, 'image_category'), 'image_category').
						inputLabel('image_alt_text', fInput('text', 'alt', $alt, '', '', '', INPUT_REGULAR, '', 'image_alt_text'), 'alt_text').
						inputLabel('image_caption', '<textarea id="image_caption" name="caption" rows="'.INPUT_XSMALL.'" cols="'.INPUT_LARGE.'">'.$caption.'</textarea>', 'caption', '', '', '').
						pluggable_ui('image_ui', 'extend_detail_form', '', $rs).
						graf(fInput('submit', '', gTxt('save'), 'publish')).
						hInput('id', $id).
						eInput('image').
						sInput('image_save').
						hInput('sort', $sort).
						hInput('dir', $dir).
						hInput('page', $page).
						hInput('search_method', $search_method).
						hInput('crit', $crit)
					, '', '', 'post', 'edit-form', '', 'image_details_form'),
				'</div>',
			'</section>'.n.'</div>';
		}
	}

// -------------------------------------------------------------
	function image_insert()
	{
		global $txpcfg, $extensions, $txp_user;

		if (!has_privs('image.edit.own'))
		{
			image_list(gTxt('restricted_area'));
			return;
		}

		extract($txpcfg);

		$meta = gpsa(array('caption', 'alt', 'category'));

		$img_result = image_data($_FILES['thefile'], $meta);

		if (is_array($img_result))
		{
			list($message, $id) = $img_result;

			return image_edit($message, $id);
		}

		else
		{
			return image_list(array($img_result, E_ERROR));
		}
	}

// -------------------------------------------------------------
	function image_replace()
	{
		global $txpcfg,$extensions,$txp_user;
		extract($txpcfg);

		$id = assert_int(gps('id'));
		$rs = safe_row("*", "txp_image", "id = $id");

		if (!has_privs('image.edit') && !($rs['author'] == $txp_user && has_privs('image.edit.own')))
		{
			image_list(gTxt('restricted_area'));
			return;
		}

		if ($rs) {
			$meta = array('category' => $rs['category'], 'caption' => $rs['caption'], 'alt' => $rs['alt']);
		} else {
			$meta = '';
		}

		$img_result = image_data($_FILES['thefile'], $meta, $id);

		if(is_array($img_result))
		{
			list($message, $id) = $img_result;
			return image_edit($message, $id);
		}else{
			return image_edit(array($img_result, E_ERROR), $id);
		}
	}

// -------------------------------------------------------------
	function thumbnail_insert()
	{
		global $txpcfg,$extensions,$txp_user,$img_dir,$path_to_site;
		extract($txpcfg);
		$id = assert_int(gps('id'));

		$author = fetch('author', 'txp_image', 'id', $id);
		if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own')))
		{
			image_list(gTxt('restricted_area'));
			return;
		}

		$file = $_FILES['thefile']['tmp_name'];
		$name = $_FILES['thefile']['name'];

		$file = get_uploaded_file($file);

		if (empty($file))
		{
			image_edit(array(upload_get_errormsg(UPLOAD_ERR_NO_FILE), E_ERROR), $id);
			return;
		}

		list($w, $h, $extension) = getimagesize($file);

		if (($file !== false) && @$extensions[$extension]) {
			$ext = $extensions[$extension];
			$newpath = IMPATH.$id.'t'.$ext;

			if (shift_uploaded_file($file, $newpath) == false) {
				image_list(array($newpath.sp.gTxt('upload_dir_perms'), E_ERROR));
			} else {
				chmod($newpath, 0644);
				safe_update("txp_image", "thumbnail = 1, thumb_w = $w, thumb_h = $h, date = now()", "id = $id");

				$message = gTxt('image_uploaded', array('{name}' => $name));
				update_lastmod();

				image_edit($message, $id);
			}
		} else {
			if ($file === false)
				image_list(array(upload_get_errormsg($_FILES['thefile']['error']), E_ERROR));
			else
				image_list(array(gTxt('only_graphic_files_allowed'), E_ERROR));
		}
	}


// -------------------------------------------------------------
	function image_save()
	{
		global $txp_user;

		$varray = array_map('assert_string', gpsa(array('id', 'name', 'category', 'caption', 'alt')));
		extract(doSlash($varray));
		$id = $varray['id'] = assert_int($id);

		$author = fetch('author', 'txp_image', 'id', $id);
		if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own')))
		{
			image_list(gTxt('restricted_area'));
			return;
		}

		$constraints = array(
			'category' => new CategoryConstraint(gps('category'), array('type' => 'image')),
		);
		callback_event_ref('image_ui', 'validate_save', 0, $varray, $constraints);
		$validator = new Validator($constraints);

		if ($validator->validate() && safe_update(
			"txp_image",
			"name    = '$name',
			category = '$category',
			alt      = '$alt',
			caption  = '$caption'",
			"id = $id"
		))
		{
			$message = gTxt('image_updated', array('{name}' => doStrip($name)));
			update_lastmod();
		}
		else
		{
			$message = array(gTxt('image_save_failed'), E_ERROR);
		}

		image_list($message);
	}

// -------------------------------------------------------------

	function image_delete($ids = array())
	{
		global $txp_user, $event;

		$ids = $ids ? array_map('assert_int', $ids) : array(assert_int(ps('id')));
		$message = '';

		if (!has_privs('image.delete'))
		{
			if (has_privs('image.delete.own'))
			{
				$ids = safe_column('id', 'txp_image', 'id IN ('.join(',', $ids).') AND author=\''.doSlash($txp_user).'\'' );
			}
			else
			{
				$ids = array();
			}
		}

		if (!empty($ids))
		{
			$fail = array();

			$rs   = safe_rows_start('id, ext', 'txp_image', 'id IN ('.join(',', $ids).')');

			if ($rs)
			{
				while ($a = nextRow($rs))
				{
					extract($a);

					// notify plugins of pending deletion, pass image's $id
					callback_event('image_deleted', $event, false, $id);

					$rsd = safe_delete('txp_image', "id = $id");

					$ul  = false;

					if (is_file(IMPATH.$id.$ext))
					{
						$ul = unlink(IMPATH.$id.$ext);
					}

					if (is_file(IMPATH.$id.'t'.$ext))
					{
						$ult = unlink(IMPATH.$id.'t'.$ext);
					}

					if (!$rsd or !$ul)
					{
						$fail[] = $id;
					}
				}

				if ($fail)
				{
					$message = array(gTxt('image_delete_failed', array('{name}' => join(', ', $fail))), E_ERROR);
				}
				else
				{
					update_lastmod();
					$message = gTxt('image_deleted', array('{name}' => join(', ', $ids)));
				}
			}
		}
		image_list($message);
	}

// -------------------------------------------------------------
	function image_change_pageby()
	{
		event_change_pageby('image');
		image_list();
	}

// -------------------------------------------------------------

	function thumbnail_create()
	{
		global $prefs, $txp_user;

		extract(doSlash(gpsa(array('id', 'width', 'height'))));
		$id = assert_int($id);

		$author = fetch('author', 'txp_image', 'id', $id);
		if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own')))
		{
			image_list(gTxt('restricted_area'));
			return;
		}

		$width = (int) $width;
		$height = (int) $height;

		if ($width == 0) $width = '';
		if ($height == 0) $height = '';

		$crop = gps('crop');

		$prefs['thumb_w'] = $width;
		$prefs['thumb_h'] = $height;
		$prefs['thumb_crop'] = $crop;

		// hidden prefs
		set_pref('thumb_w', $width, 'image', 2);
		set_pref('thumb_h', $height, 'image', 2);
		set_pref('thumb_crop', $crop, 'image', 2);

		if ($width === '' && $height === '')
		{
			image_edit(array(gTxt('invalid_width_or_height'), E_ERROR), $id);
			return;
		}

		$t = new txp_thumb( $id );
		$t->crop = ($crop == '1');
		$t->hint = '0';

		$t->width = $width;
		$t->height = $height;

		if ($t->write())
		{
			$message = gTxt('thumbnail_saved', array('{id}' => $id));
			update_lastmod();

			image_edit($message, $id);
		}

		else
		{
			$message = array(gTxt('thumbnail_not_saved', array('{id}' => $id)), E_ERROR);

			image_edit($message, $id);
		}
	}

// -------------------------------------------------------------
	function thumbnail_delete()
	{
		global $txp_user;

		$id = assert_int(gps('id'));

		$author = fetch('author', 'txp_image', 'id', $id);
		if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own')))
		{
			image_list(gTxt('restricted_area'));
			return;
		}

		$t = new txp_thumb($id);
		if ($t->delete()) {
			callback_event('thumbnail_deleted', '', false, $id);
			update_lastmod();
			image_edit(gTxt('thumbnail_deleted'),$id);
		} else {
			image_edit(array(gTxt('thumbnail_delete_failed'), E_ERROR),$id);
		}
	}

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

	global $extensions;
	$extensions = (has_privs('image.create.trusted')) ?
			array(0,'.gif','.jpg','.png','.swf',0,0,0,0,0,0,0,0,'.swf') :
			array(0,'.gif','.jpg','.png');

	define("IMPATH",$path_to_site.DS.$img_dir.DS);
	include txpath.'/lib/class.thumb.php';

	if ($event == 'image')
	{
		require_privs('image');

		$available_steps = array(
			'image_list'	=> false,
			'image_edit'	=> false,
			'image_insert'	=> true,
			'image_replace'	=> true,
			'image_save'	=> true,
			'thumbnail_insert'	=> true,
			'image_change_pageby'	=> true,
			'thumbnail_create'	=> true,
			'thumbnail_delete'	=> true,
			'image_multi_edit'	=> true
		);

		if(!$step or !bouncer($step, $available_steps)) {
			image_list();
		} else $step();
	}

// -------------------------------------------------------------

	function image_list($message = '')
	{
		global $txpcfg, $extensions, $img_dir, $file_max_upload_size, $image_list_pageby, $txp_user, $event;

		pagetop(gTxt('images'), $message);

		extract($txpcfg);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('image_sort_column', 'id');
		if ($dir === '') $dir = get_pref('image_sort_dir', 'desc');
		$dir = ($dir == 'asc') ? 'asc' : 'desc';

		echo '<div id="'.$event.'_control" class="txp-control-panel">';

		if (!is_dir(IMPATH) or !is_writeable(IMPATH))
		{
			echo graf(
				gTxt('img_dir_not_writeable', array('{imgdir}' => IMPATH))
			,' id="warning"');
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
			$crit_escaped = doSlash(str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));

			$critsql = array(
				'id'		=> "ID in ('" .join("','", do_list($crit_escaped)). "')",
				'name'		=> "name like '%$crit_escaped%'",
				'category' 	=> "category like '%$crit_escaped%'",
				'author'	=> "author like '%$crit_escaped%'",
				'alt'		=> "alt like '%$crit_escaped%'",
				'caption'	=> "caption like '%$crit_escaped%'"
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
		$total = safe_count('txp_image', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.image_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
			}

			else
			{
				echo n.graf(gTxt('no_images_recorded'), ' class="indicator"').'</div>';
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

			echo n.'<div id="'.$event.'_container" class="txp-container txp-list">';
			echo n.n.'<form name="longform" id="images_form" method="post" action="index.php" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.
			n.n.startTable('list', '', 'list').
				n.'<thead>'.
				n.tr(
					column_head('ID', 'id', 'image', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id').
					hCell('', '', ' class="actions"').
					column_head('date', 'date', 'image', true, $switch_dir, $crit, $search_method, (('date' == $sort) ? "$dir " : '').'date created').
					column_head('name', 'name', 'image', true, $switch_dir, $crit, $search_method, (('name' == $sort) ? "$dir " : '').'name').
					column_head('thumbnail', 'thumbnail', 'image', true, $switch_dir, $crit, $search_method, (('thumbnail' == $sort) ? "$dir " : '').'thumbnail').
					hCell(gTxt('tags'), '', ' class="tag-build"').
					column_head('image_category', 'category', 'image', true, $switch_dir, $crit, $search_method, (('category' == $sort) ? "$dir " : '').'category').
					($show_authors ? column_head('author', 'author', 'image', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '').
					hCell('', '', ' class="multi-edit"')
				).
				n.'</thead>';

			$tfoot = n.'<tfoot>'.tr(
				tda(
					select_buttons().
					image_multiedit_form($page, $sort, $dir, $crit, $search_method)
				,' class="multi-edit" colspan="'.($show_authors ? '9' : '8').'"')
			).n.'</tfoot>';

			echo $tfoot;
			echo '<tbody>';

			$ctr = 1;

			while ($a = nextRow($rs))
			{
				extract($a);

				$edit_url = '?event=image'.a.'step=image_edit'.a.'id='.$id.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

				$name = empty($name) ? gTxt('unnamed') : htmlspecialchars($name);

				if ($thumbnail) {
					if ($ext != '.swf') {
						$thumbnail = '<img src="'.imagesrcurl($id, $ext, true)."?$uDate".'" alt="" '.
											"title='$id$ext ($w &#215; $h)'".
											($thumb_w ? "width='$thumb_w' height='$thumb_h'" : ''). ' />';
					} else {
						$thumbnail = '';
					}
				} else {
					$thumbnail = gTxt('no');
				}

				if ($ext != '.swf') {
					$tag_url = '?event=tag'.a.'tag_name=image'.a.'id='.$id.a.'ext='.$ext.a.'w='.$w.a.'h='.$h.a.'alt='.urlencode($alt).a.'caption='.urlencode($caption);
					$tagbuilder = '<ul>'.
							'<li><a target="_blank" href="'.$tag_url.a.'type=textile" onclick="popWin(this.href); return false;">Textile</a></li>'.
							'<li><a target="_blank" href="'.$tag_url.a.'type=textpattern" onclick="popWin(this.href); return false;">Textpattern</a></li>'.
							'<li><a target="_blank" href="'.$tag_url.a.'type=xhtml" onclick="popWin(this.href); return false;">XHTML</a></li>'.
							'</ul>';
				} else {
					$tagbuilder = sp;
				}

				$category = ($category) ? '<span title="'.htmlspecialchars(fetch_category_title($category, 'image')).'">'.$category.'</span>' : '';
				$can_edit = has_privs('image.edit') || ($author == $txp_user && has_privs('image.edit.own'));

				echo n.n.tr(

					n.td($id, 20, 'id').

					td(
						n.'<ul>'.
						($can_edit ? n.t.'<li class="action-edit">'.href(gTxt('edit'), $edit_url).'</li>' : '').
						n.t.'<li class="action-view"><a href="'.imagesrcurl($id, $ext).'">'.gTxt('view').'</a></li>'.
						n.'</ul>'
					, 35, 'actions').

					td(
						gTime($uDate)
					, 75, 'date created').

					td(
						($can_edit ? href($name, $edit_url) : $name)
					, 75, 'name').

					td(
						pluggable_ui('image_ui', 'thumbnail',
						($can_edit ? href($thumbnail, $edit_url) : $thumbnail)
						, $a)
					, 80, 'image thumbnail').

					td($tagbuilder, 85, 'tag-build').
					td($category, 75, 'category').

					($show_authors ? td(
						'<span title="'.htmlspecialchars(get_author_name($author)).'">'.htmlspecialchars($author).'</span>'
					, 75, 'author') : '').

					td($can_edit ? fInput('checkbox', 'selected[]', $id) : '&nbsp;'
					, 10, 'multi-edit')
				, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').'"'
				);

				$ctr++;
			}

			echo '</tbody>'.
			n.endTable().
			n.tInput().
			n.'</form>'.

			n.'<div id="'.$event.'_navigation" class="txp-navigation">'.
			nav_form('image', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).

			pageby_form('image', $image_list_pageby).
			n.'</div>'.n.'</div>';
		}
	}

// -------------------------------------------------------------

	function image_search_form($crit, $method)
	{
		$methods =	array(
			'id'       	=> gTxt('ID'),
			'name'     	=> gTxt('name'),
			'category' 	=> gTxt('image_category'),
			'author'    => gTxt('author'),
			'alt'       => gTxt('alt_text'),
			'caption'   => gTxt('caption')
		);

		return search_form('image', 'image_list', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------

	function image_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changecategory'  => gTxt('changecategory'),
			'changeauthor'    => gTxt('changeauthor'),
			'delete'          => gTxt('delete'),
		);

		if (has_single_author('txp_image'))
		{
			unset($methods['changeauthor']);
		}

		if (!has_privs('image.delete.own') && !has_privs('image.delete'))
		{
			unset($methods['delete']);
		}

		return event_multiedit_form('image', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function image_multi_edit()
	{
		global $txp_user;

		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return image_list();
		}

		$selected = array_map('assert_int', $selected);
		$method   = ps('edit_method');
		$changed  = array();

		switch ($method)
		{
			case 'delete';
				return image_delete($selected);
				break;

			case 'changecategory':
				$key = 'category';
				$val = ps('category');
				break;

			case 'changeauthor';
				$key = 'author';
				$val = ps('author');
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
		global $txpcfg,$img_dir,$file_max_upload_size,$txp_user,$event;

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

			$categories = getTree("root", "image");

			if ($ext != '.swf') {
				$aspect = ($h == $w) ? ' square' : (($h > $w) ? ' portrait' : ' landscape');
				$img = '<img class="image fullsize" src="'.imagesrcurl($id, $ext)."?$uDate".'" height="'.$h.'" width="'.$w.'" alt="" title="'.$id.$ext.' ('.$w.' &#215; '.$h.')" id="image-fullsize" />';
			} else {
				$img = $aspect = '';
			}

			if ($thumbnail and ($ext != '.swf')) {
				$thumb = '<img class="image thumbnail" src="'.imagesrcurl($id, $ext, true)."?$uDate".'" alt="" '.
							($thumb_w ? "width='$thumb_w' height='$thumb_h'" : ''). ' />';
			} else {
				$thumb = '';
			}

			echo n.'<div id="'.$event.'_container" class="txp-container txp-edit">';
			echo startTable('list', '', 'edit-pane'),
			tr(
				td(
					pluggable_ui('image_ui', 'image_edit',
						$img.br.
						upload_form(gTxt('replace_image'), 'replace_image_form', 'image_replace', 'image', $id, $file_max_upload_size, 'image-replace', 'image-replace')
					, $rs)
				)
			, ' class="image-edit'.$aspect.'"'),
			tr(
				td(
					pluggable_ui('image_ui', 'thumbnail_edit', join('',
						array(
							($thumbnail)
							? 	startTable('image-thumbnail', '', 'image-thumbnail').
									tr(
										td($thumb, '', 'thumbwrapper').
										td(dLink('image','thumbnail_delete','id',$id, '', '', '', '', array($page, $sort, $dir, $crit, $search_method)))
									).
								endTable().br
							: 	'',
							upload_form(gTxt('upload_thumbnail'),'upload_thumbnail',
								'thumbnail_insert','image',$id,$file_max_upload_size, 'upload-thumbnail', 'thumbnail-upload')
						)
					), $rs)
				)
			, ' class="thumbnail-edit"'),

			(check_gd($ext))
			?	thumb_ui( $id, $rs )
			:	'',

			tr(
				td(
					form(
						graf('<label for="image-name">'.gTxt('image_name').'</label>'.br.
							fInput('text', 'name', $name, 'edit', '', '', '', '', 'image-name'), ' class="name"').

						graf('<label for="image-category">'.gTxt('image_category').'</label>'.br.
							treeSelectInput('category', $categories, $category, 'image-category'), ' class="category"').

						graf('<label for="alt-text">'.gTxt('alt_text').'</label>'.br.
							fInput('text', 'alt', $alt, 'edit', '', '', 50, '', 'alt-text'), ' class="alt text"').

						graf('<label for="caption">'.gTxt('caption').'</label>'.br.
							'<textarea id="caption" name="caption">'.$caption.'</textarea>'
							, ' class="caption description text"').

						pluggable_ui('image_ui', 'extend_detail_form', '', $rs).

						n.graf(fInput('submit', '', gTxt('save'), 'publish')).
						n.hInput('id', $id).
						n.eInput('image').
						n.sInput('image_save').

						n.hInput('sort', $sort).
						n.hInput('dir', $dir).
						n.hInput('page', $page).
						n.hInput('search_method', $search_method).
						n.hInput('crit', $crit)
					, '', '', 'post', 'edit-form', '', 'image_details_form')
				)
			, ' class="image-detail"'),
			endTable().
			n.'</div>';
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
				safe_update("txp_image", "thumbnail = 1, thumb_w = $w, thumb_h = $h", "id = $id");

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

		extract(doSlash(gpsa(array('id','category','caption','alt'))));
		$name = gps('name');
		$safename = doSlash($name);
		$id = assert_int($id);

		$author = fetch('author', 'txp_image', 'id', $id);
		if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own')))
		{
			image_list(gTxt('restricted_area'));
			return;
		}

		safe_update(
			"txp_image",
			"name     = '$safename',
			category = '$category',
			alt      = '$alt',
			caption  = '$caption'",
			"id = $id"
		);

		$message = gTxt('image_updated', array('{name}' => $name));
		update_lastmod();

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
	function thumb_ui($id, $rs)
	{
		global $prefs, $sort, $dir, $page, $search_method, $crit;
		extract($prefs);
		return
		tr(
			td(
				pluggable_ui('image_ui', 'thumbnail_create', form(
					graf(gTxt('create_thumbnail'), ' class="label"') .
					startTable('thumbnail_alter','left','',1) .
						tr(
							fLabelCell(gTxt('thumb_width'), '', 'width') .
							fInputCell('width', @$thumb_w, 1, 4, '', 'width').

							fLabelCell(gTxt('thumb_height'), '', 'height') .
							fInputCell('height', @$thumb_h, 1, 4, '', 'height').

							fLabelCell(gTxt('keep_square_pixels'), '', 'crop') .
							tda(checkbox('crop', 1, @$thumb_crop, '', 'crop'), ' class="noline"').

							tda(
								fInput('submit', '', gTxt('Create'), 'smallerbox')
							, ' class="noline"')
						, ' class="thumbnail-alter-controls"').

						n.hInput('id', $id).
						n.eInput('image').
						n.sInput('thumbnail_create').

						n.hInput('sort', $sort).
						n.hInput('dir', $dir).
						n.hInput('page', $page).
						n.hInput('search_method', $search_method).
						n.hInput('crit', $crit).

					endTable()
				, '', '', 'post', 'edit-form', '', 'thumbnail_alter_form'), $rs)
			)
		, ' class="thumbnail-alter"');
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
			image_edit(gTxt('thumbnail_deleted'),$id);
		} else {
			image_edit(array(gTxt('thumbnail_delete_failed'), E_ERROR),$id);
		}
	}

// -------------------------------------------------------------
// Refactoring attempt, allowing other - plugin - functions to
// upload images without the need for writing duplicated code.

	function image_data($file , $meta = '', $id = '', $uploaded = true)
	{
		global $txpcfg, $extensions, $txp_user, $prefs, $file_max_upload_size, $event;

		extract($txpcfg);

		$name = $file['name'];
		$error = $file['error'];
		$file = $file['tmp_name'];

		if ($uploaded)
		{
			$file = get_uploaded_file($file);

			if ($file_max_upload_size < filesize($file))
			{
				unlink($file);

				return upload_get_errormsg(UPLOAD_ERR_FORM_SIZE);
			}
		}

		if (empty($file))
		{
			return upload_get_errormsg(UPLOAD_ERR_NO_FILE);
		}

		list($w, $h, $extension) = getimagesize($file);

		if (($file !== false) && @$extensions[$extension])
		{
			$ext = $extensions[$extension];

			$name = substr($name, 0, strrpos($name, '.')).$ext;
			$safename = doSlash($name);

			if ($meta == false)
			{
				$meta = array('category' => '', 'caption' => '', 'alt' => '');
			}

			extract(doSlash($meta));

			$q ="
				name = '$safename',
				ext = '$ext',
				w = $w,
				h = $h,
				alt = '$alt',
				caption = '$caption',
				category = '$category',
				date = now(),
				author = '".doSlash($txp_user)."'
			";

			if (empty($id))
			{
				$rs = safe_insert('txp_image', $q);

				$id = $GLOBALS['ID'] = mysql_insert_id();
			}

			else
			{
				$id = assert_int($id);

				$rs = safe_update('txp_image', $q, "id = $id");
			}

			if (!$rs)
			{
				return gTxt('image_save_error');
			}

			else
			{
				$newpath = IMPATH.$id.$ext;

				if (shift_uploaded_file($file, $newpath) == false)
				{
					$id = assert_int($id);

					safe_delete('txp_image', "id = $id");

					safe_alter('txp_image', "auto_increment = $id");

					if (isset($GLOBALS['ID']))
					{
						unset( $GLOBALS['ID']);
					}

					return $newpath.sp.gTxt('upload_dir_perms');
				}

				else
				{
					@chmod($newpath, 0644);

					// GD is supported
					if (check_gd($ext))
					{
						// Auto-generate a thumbnail using the last settings
						if (isset($prefs['thumb_w'], $prefs['thumb_h'], $prefs['thumb_crop']))
						{
							$width  = intval($prefs['thumb_w']);
							$height = intval($prefs['thumb_h']);

							if ($width > 0 or $height > 0)
							{
								$t = new txp_thumb( $id );

								$t->crop = ($prefs['thumb_crop'] == '1');
								$t->hint = '0';
								$t->width = $width;
								$t->height = $height;

								$t->write();
							}
						}
					}

					$message = gTxt('image_uploaded', array('{name}' => $name));
					update_lastmod();

					// call post-upload plugins with new image's $id
					callback_event('image_uploaded', $event, false, $id);

					return array($message, $id);
				}
			}
		}

		else
		{
			if ($file === false)
			{
				return upload_get_errormsg($error);
			}

			else
			{
				return gTxt('only_graphic_files_allowed');
			}
		}
	}

// -------------------------------------------------------------
// check GD info

	function check_gd($image_type) {
		// GD is installed
		if (function_exists('gd_info')) {
			$gd_info = gd_info();

			switch ($image_type) {
				// check gif support
				case '.gif':
					return ($gd_info['GIF Create Support'] == 1) ? true : false;
				break;

				// check png support
				case '.png':
					return ($gd_info['PNG Support'] == 1) ? true : false;
				break;

				// check jpg support
				case '.jpg':
					return (!empty($gd_info['JPEG Support']) || !empty($gd_info['JPG Support'])) ? true : false;
				break;

				// unsupported format
				default:
					return false;
				break;
			}
		} else { // GD isn't installed
			return false;
		}
	}

?>

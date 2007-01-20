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
	$extensions = array(0,'.gif','.jpg','.png','.swf');
	define("IMPATH",$path_to_site.'/'.$img_dir.'/');
	include txpath.'/lib/class.thumb.php';

	if ($event == 'image') {	
		require_privs('image');		

		if(!$step or !in_array($step, array('image_list','image_edit','image_insert','image_delete','image_replace','image_save','thumbnail_insert','image_change_pageby','thumbnail_create'
		))){
			image_list();
		} else $step();
	}

// -------------------------------------------------------------

	function image_list($message = '')
	{
		global $txpcfg, $extensions, $img_dir, $file_max_upload_size;

		pagetop(gTxt('images'), $message);

		extract($txpcfg);
		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		if (!is_dir(IMPATH) or !is_writeable(IMPATH))
		{
			echo graf(
				gTxt('img_dir_not_writeable', array('{imgdir}' => IMPATH))
			,' id="warning"');
		}

		else
		{
			echo upload_form(gTxt('upload_image'), 'upload', 'image_insert', 'image', '', $file_max_upload_size);
		}

		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'id '.$dir;
			break;

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
				$dir = 'desc';
				$sort_sql = 'id '.$dir;
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'id'			 => "id = '$crit_escaped'",
				'name'		 => "name like '%$crit_escaped%'",
				'category' => "category like '%$crit_escaped%'",
				'author'	 => "author like '%$crit_escaped%'"
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
					n.graf(gTxt('no_results_found'), ' style="text-align: center;"');
			}

			else
			{
				echo n.graf(gTxt('no_images_recorded'), ' style="text-align: center;"');
			}

			return;
		}

		$limit = max(@$image_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo image_search_form($crit, $search_method);

		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_image',
			"$criteria order by $sort_sql limit $offset, $limit
		");

		if ($rs)
		{
			echo n.n.startTable('list').
				n.tr(
					column_head('ID', 'id', 'image', true, $switch_dir, $crit, $search_method).
					hCell().
					column_head('date', 'date', 'image', true, $switch_dir, $crit, $search_method).
					column_head('name', 'name', 'image', true, $switch_dir, $crit, $search_method).
					column_head('thumbnail', 'thumbnail', 'image', true, $switch_dir, $crit, $search_method).
					hCell(gTxt('tags')).
					column_head('image_category', 'category', 'image', true, $switch_dir, $crit, $search_method).
					column_head('author', 'author', 'image', true, $switch_dir, $crit, $search_method).
					hCell()
				);

			while ($a = nextRow($rs))
			{
				extract($a);

				$edit_url = '?event=image'.a.'step=image_edit'.a.'id='.$id.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

				$name = empty($name) ? gTxt('unnamed') : $name;

				$thumbnail = ($thumbnail) ?
					'<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" />' :
					gTxt('no');

				$tag_url = '?event=tag'.a.'tag_name=image'.a.'id='.$id.a.'ext='.$ext.
					a.'w='.$w.a.'h='.$h.a.'alt='.urlencode($alt).a.'caption='.urlencode($caption);

				$category = ($category) ? '<span title="'.fetch_category_title($category, 'image').'">'.$category.'</span>' : '';

				echo n.n.tr(

					n.td($id, 20).

					td(
						n.'<ul>'.
						n.t.'<li>'.href(gTxt('edit'), $edit_url).'</li>'.
						n.t.'<li><a href="'.hu.$img_dir.'/'.$id.$ext.'">'.gTxt('view').'</a></li>'.
						n.'</ul>'
					, 35).

					td(
						safe_strftime('%d %b %Y %X', $uDate)
					, 75).

					td(
						href($name, $edit_url)
					, 75).

					td($thumbnail, 75).

					td(
						'<ul>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=textile" onclick="popWin(this.href); return false;">Textile</a></li>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=textpattern" onclick="popWin(this.href); return false;">Textpattern</a></li>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=xhtml" onclick="popWin(this.href); return false;">XHTML</a></li>'.
						'</ul>'
					, 85).

					td($category, 75).

					td(
						'<span title="'.get_author_name($author).'">'.$author.'</span>'
					, 75).

					td(
						dLink('image', 'image_delete', 'id', $id)
					, 10)
				);
			}

			echo endTable().

			nav_form('image', $page, $numPages, $sort, $dir, $crit, $search_method).

			pageby_form('image', $image_list_pageby);
		}
	}

// -------------------------------------------------------------

	function image_search_form($crit, $method)
	{
		$methods =	array(
			'id'       => gTxt('ID'),
			'name'     => gTxt('name'),
			'category' => gTxt('image_category'),
			'author'	 => gTxt('author')
		);

		return search_form('image', 'image_list', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------
	function image_edit($message='',$id='') 
	{
		if (!$id) $id = gps('id');
		$id = assert_int($id);
		global $txpcfg,$img_dir,$file_max_upload_size;

		pagetop(gTxt('edit_image'),$message);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		$categories = getTree("root", "image");
		
		$rs = safe_row("*", "txp_image", "id = $id");
		
		if ($rs) {
			extract($rs);
			echo startTable('list'),
			tr(
				td(
					'<img src="'.hu.$img_dir.
						'/'.$id.$ext.'" height="'.$h.'" width="'.$w.'" alt="" />'.
						br.upload_form(gTxt('replace_image'),'replace_image_form',
							'image_replace','image',$id,$file_max_upload_size, 'image-replace', '')
				)
			),
			tr(
				td(
					join('',
						array(
							($thumbnail)
							?	'<img src="'.hu.$img_dir.
								'/'.$id.'t'.$ext.'" alt="" />'.br
							:	'',
							upload_form(gTxt('upload_thumbnail'),'upload_thumbnail',
								'thumbnail_insert','image',$id,$file_max_upload_size, 'upload-thumbnail', '')
						)
					)
				)
			),

			(check_gd($ext))
			?	thumb_ui( $id )
			:	'',

			tr(
				td(
					form(
						graf('<label for="image-name">'.gTxt('image_name').'</label>'.br.
							fInput('text', 'name', $name, 'edit', '', '', '', '', 'image-name')).

						graf('<label for="image-category">'.gTxt('image_category').'</label>'.br.
							treeSelectInput('category', $categories, $category, 'image-category')).

						graf('<label for="alt-text">'.gTxt('alt_text').'</label>'.br.
							fInput('text', 'alt', $alt, 'edit', '', '', 50, '', 'alt-text')).

						graf('<label for="caption">'.gTxt('caption').'</label>'.br.
							text_area('caption', '100', '400', $caption, 'caption')).

						n.graf(fInput('submit', '', gTxt('save'), 'publish')).
						n.hInput('id', $id).
						n.eInput('image').
						n.sInput('image_save').

						n.hInput('sort', $sort).
						n.hInput('dir', $dir).
						n.hInput('page', $page).
						n.hInput('search_method', $search_method).
						n.hInput('crit', $crit)
					)
				)
			),
			endTable();
		}
	}

// -------------------------------------------------------------

	function image_insert()
	{
		global $txpcfg, $extensions, $txp_user;

		extract($txpcfg);

		$meta = doSlash(gpsa(array('caption', 'alt', 'category')));

		$img_result = image_data($_FILES['thefile'], $meta);

		if (is_array($img_result))
		{
			list($message, $id) = $img_result;

			return image_edit($message, $id);
		}

		else
		{
			return image_list($img_result);
		}
	}

// -------------------------------------------------------------
	function image_replace() 
	{	
		global $txpcfg,$extensions,$txp_user;
		extract($txpcfg);
		$id = gps('id');
		
		$img_result = image_data($_FILES['thefile'], '', $id);
		

		if(is_array($img_result))
		{
			list($message, $id) = $img_result;
			return image_edit($message, $id);
		}else{
			return image_list($img_result);
		}
	}

// -------------------------------------------------------------
	function thumbnail_insert() 
	{
		global $txpcfg,$extensions,$txp_user,$img_dir,$path_to_site;
		extract($txpcfg);
		$id = assert_int(gps('id'));
		
		$file = $_FILES['thefile']['tmp_name'];
		$name = $_FILES['thefile']['name'];

		$file = get_uploaded_file($file);
		
		list(,,$extension) = getimagesize($file);
	
		if (($file !== false) && $extensions[$extension]) {
			$ext = $extensions[$extension];

				$newpath = IMPATH.$id.'t'.$ext;
			
			if(shift_uploaded_file($file, $newpath) == false) {
				image_list($newpath.sp.gTxt('upload_dir_perms'));
			} else {
				chmod($newpath,0644);
				safe_update("txp_image", "thumbnail = 1", "id = $id");

				$message = gTxt('image_uploaded', array('{name}' => $name));
				update_lastmod();

				image_edit($message, $id);
			}
		} else {
			if ($file === false)
				image_list(upload_get_errormsg($_FILES['thefile']['error']));
			else
				image_list(gTxt('only_graphic_files_allowed'));
		}
	}


// -------------------------------------------------------------
	function image_save() 
	{
		extract(doSlash(gpsa(array('id','name','category','caption','alt'))));
		$id = assert_int($id);
		
		safe_update(
			"txp_image",
			"name     = '$name',
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
	function image_delete() 
	{
		global $txpcfg;
		extract($txpcfg);
		$id = assert_int(ps('id'));
		
		$rs = safe_row("*", "txp_image", "id = $id");
		if ($rs) {
			extract($rs);
			$rsd = safe_delete("txp_image","id = $id");
			$ul = unlink(IMPATH.$id.$ext) or exit(image_list());
			if(is_file(IMPATH.$id.'t'.$ext)){
				$ult = unlink(IMPATH.$id.'t'.$ext);
			}

			if ($rsd && $ul)
			{
				$message = gTxt('image_deleted', array('{name}' => $name));
				update_lastmod();

				image_list($message);
			}
		} else image_list();
	}


	
// -------------------------------------------------------------
	function image_change_pageby()
	{
		event_change_pageby('image');
		image_list();
	}

// -------------------------------------------------------------
	function thumb_ui($id)
	{		
		global $prefs, $sort, $dir, $page, $search_method, $crit;
		extract($prefs);
		return
		tr(
			td(
				form(
					graf(gTxt('create_thumbnail')) .
					startTable('','left','',1) .
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
						).

						n.hInput('id', $id).
						n.eInput('image').
						n.sInput('thumbnail_create').

						n.hInput('sort', $sort).
						n.hInput('dir', $dir).
						n.hInput('page', $page).
						n.hInput('search_method', $search_method).
						n.hInput('crit', $crit).

					endTable()
				)
			)
		);
	}

// -------------------------------------------------------------

	function thumbnail_create()
	{
		extract(doSlash(gpsa(array('id', 'width', 'height'))));

		// better checking of thumbnail dimensions
		// don't try and use zeros

		$width = (int) $width;
		$height = (int) $height;

		if ($width == 0 && $height == 0) {
			image_edit(messenger('invalid_width_or_height', "($width)/($height)", ''), $id);
			return;
		} else {
			if ($width == 0) {
				$width = '';
			}

			if ($height == 0) {
				$height = '';
			}
		}

		$crop = gps('crop');

		$t = new txp_thumb( $id );
		$t->crop = ($crop == '1');
		$t->hint = '0';

		$t->width = $width;
		$t->height = $height;

		if ($t->write())
		{
			global $prefs;

			$prefs['thumb_w'] = $width;
			$prefs['thumb_h'] = $height;
			$prefs['thumb_crop'] = $crop;

			// hidden prefs
			set_pref('thumb_w', $width, 'image',	2);
			set_pref('thumb_h', $height, 'image',	 2);
			set_pref('thumb_crop', $crop, 'image',	2);

			$message = gTxt('thumbnail_saved', array('{id}' => $id));
			update_lastmod();

			image_edit($message, $id);
		}

		else
		{
			$message = gTxt('thumbnail_not_saved', array('{id}' => $id));

			image_edit($message, $id);
		}
	}

// -------------------------------------------------------------
// Refactoring attempt, allowing other - plugin - functions to
// upload images without the need for writting duplicated code.

	function image_data($file , $meta = '', $id = '', $uploaded = true)
	{
		global $txpcfg, $extensions, $txp_user, $prefs, $file_max_upload_size;

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

		list($w, $h, $extension) = getimagesize($file);

		if (($file !== false) && @$extensions[$extension])
		{
			$ext = $extensions[$extension];

			$name = doSlash(substr($name, 0, strrpos($name, '.')).$ext);

			if ($meta == false)
			{
				$meta = array('category' => '', 'caption' => '', 'alt' => '');
			}

			extract($meta);

			$q ="
				name = '$name',
				ext = '$ext',
				w = $w,
				h = $h,
				alt = '$alt',
				caption = '$caption',
				category = '$category',
				date = now(),
				author = '$txp_user'
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

					// Auto-generate a thumbnail using the last settings
					if (isset($prefs['thumb_w'], $prefs['thumb_h'], $prefs['thumb_crop']))
					{
						if (intval($prefs['thumb_w']) > 0 and intval($prefs['thumb_h']) > 0) {
							$t = new txp_thumb( $id );

							$t->crop = ($prefs['thumb_crop'] == '1');
							$t->hint = '0';
							$t->width = intval($prefs['thumb_w']);
							$t->height = intval($prefs['thumb_h']);

							$t->write();
						}
					}

					$message = gTxt('image_uploaded', array('{name}' => $name));
					update_lastmod();

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
					return ($gd_info['JPG Support'] == 1) ? true : false;
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
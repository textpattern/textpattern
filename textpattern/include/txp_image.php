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

		pagetop(gTxt('image'), $message);

		extract($txpcfg);
		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		if (!is_dir(IMPATH) or !is_writeable(IMPATH))
		{
			echo graf(
				str_replace('{imgdir}', IMPATH, gTxt('img_dir_not_writeable'))
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
				$sort_sql = '`id` '.$dir;
			break;

			case 'name':
				$sort_sql = '`name` '.$dir;
			break;

			case 'thumbnail':
				$sort_sql = '`thumbnail` '.$dir.', `id` asc';
			break;

			case 'category':
				$sort_sql = '`category` '.$dir.', `id` asc';
			break;

			case 'date':
				$sort_sql = '`date` '.$dir.', `id` asc';
			break;

			case 'author':
				$sort_sql = '`author` '.$dir.', `id` asc';
			break;

			default:
				$dir = 'desc';
				$sort_sql = '`id` '.$dir;
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($crit or $search_method)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'id'			 => "id = '$crit_escaped'",
				'name'		 => "`name` like '%$crit_escaped%'",
				'category' => "`category` like '%$crit_escaped%'",
				'author'	 => "`author` like '%$crit_escaped%'"
			);

			if (array_key_exists($search_method, $critsql))
			{
				$criteria = $critsql[$search_method];
				$limit = 500;
			}

			else
			{
				$method = '';
			}
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

		$rs = safe_rows_start('*, unix_timestamp(`date`) as uDate', 'txp_image',
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

				$tag_url = '?event=tag'.a.'name=image'.a.'id='.$id.a.'ext='.$ext.a.
					'alt='.$alt.a.'h='.$h.a.'w='.$w;

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
						safe_strftime('%d %b %Y %I:%M %p', $uDate)
					, 75).

					td(
						href($name, $edit_url)
					, 75).

					td($thumbnail, 75).

					td(
						'<ul>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=textile" onclick="popWin(this.href, 400, 250); return false;">Textile</a></li>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=textpattern" onclick="popWin(this.href, 400, 250); return false;">Textpattern</a></li>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=xhtml" onclick="popWin(this.href, 400, 250); return false;">XHTML</a></li>'.
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
		$default_method = 'name';

		$methods =	array(
			'id'       => gTxt('ID'),
			'name'     => gTxt('name'),
			'category' => gTxt('image_category'),
			'author'	 => gTxt('author')
		);

		$method = ($method) ? $method : $default_method;

		return n.n.form(
			graf(

				gTxt('Search').sp.selectInput('method', $methods, $method).
				fInput('text', 'crit', $crit, 'edit', '', '', '15').
				eInput('image').
				sInput('image_list').
				fInput('submit', 'search', gTxt('go'), 'smallerbox')

			,' style="text-align: center;"')
		);
	}

// -------------------------------------------------------------
	function image_edit($message='',$id='') 
	{
		if (!$id) $id = gps('id');
		global $txpcfg,$img_dir,$file_max_upload_size;

		pagetop('image',$message);

		$categories = getTree("root", "image");
		
		$rs = safe_row("*", "txp_image", "id='$id'");
		
		if ($rs) {
			extract($rs);
			echo startTable('list'),
			tr(
				td(
					'<img src="'.hu.$img_dir.
						'/'.$id.$ext.'" height="'.$h.'" width="'.$w.'" alt="" />'.
						br.upload_form(gTxt('replace_image'),'replace_image_form',
							'image_replace','image',$id,$file_max_upload_size)
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
								'thumbnail_insert','image',$id,$file_max_upload_size)
						)
					)
				)
			),

			(function_exists("imagecreatefromjpeg"))
			?	thumb_ui( $id )
			:	'',

			tr(
				td(
					form(
						graf(gTxt('image_name').br.fInput('text','name',$name,'edit')) .
						 graf(gTxt('image_category').br.treeSelectInput('category',
						 		$categories,$category)) .
						graf(gTxt('alt_text').br.fInput('text','alt',$alt,'edit','','',50)) .
						graf(gTxt('caption').br.text_area('caption','100','400',$caption)) .
						graf(fInput('submit','',gTxt('save'),'publish')) .
						hInput('id',$id) .
						eInput('image') .
						sInput('image_save')
					)
				)
			),
			endTable();
		}
	}

// -------------------------------------------------------------
	function image_insert() 
	{	
		global $txpcfg,$extensions,$txp_user;
		extract($txpcfg);
		$category = doSlash(gps('category'));
		
		$img_result = image_data($_FILES['thefile'], $category);
		
		if(is_array($img_result))
		{
			list($message, $id) = $img_result;
			return image_edit($message, $id);
		}else{
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
		$id = gps('id');
		
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
				chmod($newpath,0755);
				safe_update("txp_image", "thumbnail='1'", "id='$id'");
				image_edit(messenger('image',$name,'uploaded'),$id);
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
		
		safe_update(
			"txp_image",
			"name     = '$name',
			category = '$category',
			alt      = '$alt',
			caption  = '$caption'",
			"id = '$id'"
		);
		image_list(messenger("image",$name,"updated"));
	}

// -------------------------------------------------------------
	function image_delete() 
	{
		global $txpcfg;
		extract($txpcfg);
		$id = ps('id');
		
		$rs = safe_row("*", "txp_image", "id='$id'");
		if ($rs) {
			extract($rs);
			$rsd = safe_delete("txp_image","id='$id'");
			$ul = unlink(IMPATH.$id.$ext) or exit(image_list());
			if(is_file(IMPATH.$id.'t'.$ext)){
				$ult = unlink(IMPATH.$id.'t'.$ext);
			}

			if ($rsd && $ul) image_list(messenger("image",$name,"deleted"));
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
		global $prefs;
		extract($prefs);
		return
		tr(
			td(
				form(
					graf(gTxt('create_thumbnail')) .
					startTable('','left','',1) .
						tr(
							fLabelCell(gTxt('thumb_width').':') . 
							fInputCell('width',@$thumb_w,1,4).
							
							fLabelCell(gTxt('thumb_height').':') . 
							fInputCell('height',@$thumb_h,1,4).
							
							fLabelCell(gTxt('keep_square_pixels').':') . 
							tda(checkbox2('crop', @$thumb_crop),' class="noline"').
														
							tda(fInput('submit','',gTxt('Create'),'smallerbox'),
								' class="noline"')
						) .
						hInput('id',$id) .
						eInput('image') .
						sInput('thumbnail_create') .
					endTable()
				)
			)
		);
	}

// -------------------------------------------------------------
	function thumbnail_create() 
	{
	
		$id = gps('id');
		$width = gps('width');
		$height = gps('height');
		if (!is_numeric ($width) && !is_numeric($height)) {
			image_edit(messenger('invalid_width_or_height',"($width)/($height)", ''),$id);
			return;
		}
		
		$crop = gps('crop');
		
		$t = new txp_thumb( $id );
		$t->crop = ($crop == '1');
		$t->hint = '0';
		if ( is_numeric ($width)) $t->width = $width;
		if ( is_numeric ($height)) $t->height = $height;
		
		if ($t->write()) {
			global $prefs;
			$prefs['thumb_w'] = $width;
			$prefs['thumb_h'] = $height;
			$prefs['thumb_crop'] = $crop;
			set_pref('thumb_w', $width, 'image',  1);
			set_pref('thumb_h', $height, 'image',  1);
			set_pref('thumb_crop', $crop, 'image',  1);
			image_edit(messenger('thumbnail',$id,'saved'),$id);
		 }
		else {
			image_edit(messenger('thumbnail',$id,'not_saved'),$id);
		}
	}

// -------------------------------------------------------------	
// Refactoring attempt, allowing other - plugin - functions to
// upload images without the need for writting duplicated code.
// -------------------------------------------------------------	
	function image_data($file , $category = '', $id = '', $uploaded = true)
	{
		global $txpcfg, $extensions, $txp_user, $prefs, $file_max_upload_size;
		extract($txpcfg); 
		
		$name = $file['name'];
		$error = $file['error'];
		$file = $file['tmp_name'];
		
		if($uploaded) {
			$file = get_uploaded_file($file);
			if ($file_max_upload_size < filesize($file)) {
				unlink($file);
				return upload_get_errormsg(UPLOAD_ERR_FORM_SIZE);
			}
		}
		
		list($w,$h,$extension) = getimagesize($file);

		if (($file !== false) && @$extensions[$extension]) {
			$ext = $extensions[$extension];
			$name = substr($name,0,strrpos($name,'.'));
			$name .= $ext;
			$name2db = doSlash($name);
			
			$q ="w        = '$w',
				 h        = '$h',
				 ext      = '$ext',
				 name   = '$name2db',
				 date   = now(),
				 caption  = '',
				 author   = '$txp_user'";
			if (empty($id)) {
				$q.= ", category = '$category'";
				$rs = safe_insert("txp_image",$q);
				$id = mysql_insert_id();
			}else{
				$id = doSlash($id);
				$rs = safe_update('txp_image',$q, "id = $id");
			}
			
			if(!$rs){
				
				return gTxt('image_save_error');

			} else {

				$newpath = IMPATH.$id.$ext;

				if(shift_uploaded_file($file, $newpath) == false) {
					safe_delete("txp_image","id='$id'");
					safe_alter("txp_image", "auto_increment=$id");
					return $newpath.sp.gTxt('upload_dir_perms');
				} else {
					chmod($newpath,0755);
					// Auto-generate a thumbnail using the last settings
					if (isset($prefs['thumb_w'], $prefs['thumb_h'], $prefs['thumb_crop'])) {
						$t = new txp_thumb( $id );
						$t->crop = ($prefs['thumb_crop'] == '1');
						$t->hint = '0';
						if ( is_numeric ($prefs['thumb_w'])) $t->width = $prefs['thumb_w'];
						if ( is_numeric ($prefs['thumb_h'])) $t->height = $prefs['thumb_h'];
						$t->write();
					}
					return array(messenger('image',$name,'uploaded'),$id);
				}
			}
		} else {
			if ($file === false)
				return upload_get_errormsg($error);
			else
				return gTxt('only_graphic_files_allowed');
		}
	}

?>

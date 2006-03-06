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
	function image_list($message='') 
	{
		global $txpcfg,$extensions,$img_dir;
		extract($txpcfg);
		extract(get_prefs());

		pagetop(gTxt('image'),$message);

		echo pageby_form('image',$image_list_pageby);

		echo startTable('list'),
		tr(
			tda(
				upload_form(gTxt('upload_file'),gTxt('upload'),'image_insert','image'),
					' colspan="4" style="border:0"'
			)
		),
		tr(
			hCell(gTxt('name')) . 
			hCell(gTxt('image_category')) . 
			hCell(gTxt('tags')) . 
			hCell(gTxt('author')) . 
			hCell(gTxt('thumbnail')) . 
			hCell()
		);

		$page = gps('page');

		$total = getCount('txp_image',"1=1");  
		$limit = max(@$image_list_pageby, 25);
		$numPages = ceil($total/$limit);  
		$page = (!$page) ? 1 : $page;
		$offset = ($page - 1) * $limit;

		$nav[] = ($page > 1)
		?	PrevNextLink("image",$page-1,gTxt('prev'),'prev') : '';

		$nav[] = sp.small($page. '/'.$numPages).sp;

		$nav[] = ($page != $numPages) 
		?	PrevNextLink("image",$page+1,gTxt('next'),'next') : '';
		
		$rs = safe_rows_start("*", "txp_image", "1=1 order by category,name limit $offset, $limit");
	
		if($rs) {
			while ($a = nextRow($rs)) {
			
				extract($a);
				
				$thumbnail = ($thumbnail) 
				?	'<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" />' 
				:	gTxt('no');
				
				$elink = eLink('image','image_edit','id',$id,$name);
	
				$txtilelink = '<a target="_blank" href="?event=tag'.a.'name=image'.a.'id='.$id.a.'ext='.$ext.a.'alt='.$alt.a.'h='.$h.a.'w='.$w.a.'type=textile" onclick="window.open(this.href, \'popupwindow\', \'width=400,height=400,scrollbars,resizable\'); return false;">Textile</a>';
				$txplink = '<a target="_blank" href="?event=tag'.a.'name=image'.a.'id='.$id.a.'type=textpattern" onclick="window.open(this.href, \'popupwindow\', \'width=400,height=400,scrollbars,resizable\'); return false;">Textpattern</a>';
				$xhtmlink = '<a target="_blank" href="?event=tag'.a.'name=image'.a.'id='.$id.a.'ext='.$ext.a.'alt='.$alt.a.'h='.$h.a.'w='.$w.a.'type=xhtml" onclick="window.open(this.href, \'popupwindow\', \'width=400,height=400,scrollbars,resizable\'); return false;">XHTML</a>';
				
				$dlink = dLink('image','image_delete','id',$id);
	
				echo
				tr(
					td($elink).td($category).td($txtilelink.' / '.$txplink.' / '.$xhtmlink). 
					td($author).
					td($thumbnail).
					td($dlink,10)
				);
			}

			echo 
				tr(
					tdcs(
						graf(join('',$nav))
					,6)
				);
		}
		echo endTable();

		if (!is_dir(IMPATH) or !is_writeable(IMPATH)) {
		
			echo graf(str_replace("{imgdir}",IMPATH,gTxt('img_dir_not_writeable')),' style="text-align:center;color:red"');

		}
	}

// -------------------------------------------------------------
	function image_edit($message='',$id='') 
	{
		if (!$id) $id = gps('id');
		global $txpcfg,$img_dir;

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
							'image_replace','image',$id)
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
								'thumbnail_insert','image',$id)
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
		global $txpcfg, $extensions, $txp_user, $prefs;
		extract($txpcfg); 
		
		$name = $file['name'];
		$error = $file['error'];
		$file = $file['tmp_name'];
		
		if($uploaded){
			$file = get_uploaded_file($file);
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

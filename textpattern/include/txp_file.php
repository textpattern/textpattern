<?php
/*
            _______________________________________
   ________|            Textpattern                |________
   \       |          Mod File Upload              |       /
    \      |   Michael Manfre (http://manfre.net)  |      /
    /      |_______________________________________|      \
   /___________)                               (___________\

	Textpattern Copyright 2004 by Dean Allen. All rights reserved.
	Use of this software denotes acceptance of the Textpattern license agreement 
	
	"Mod File Upload" Copyright 2004 by Michael Manfre. All rights reserved.
	Use of this mod denotes acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	$levels = array(
		1 => gTxt('private'),
		0 => gTxt('public')
	);

	if ($event == 'file') {
		require_privs('file');		

		if(!$step or !in_array($step, array('file_change_max_size','file_change_pageby','file_db_add','file_delete','file_edit','file_insert','file_list','file_replace','file_save','file_reset_count','file_create'))){
			file_list();
		} else $step();
	}

// -------------------------------------------------------------
	function file_list($message='') 
	{
		global $txpcfg,$extensions,$file_base_path;

		extract($txpcfg);
		extract(get_prefs());

		pagetop(gTxt('file'),$message);

		$page = gps('page');

		$total = getCount('txp_file',"1=1");  
		$limit = max(@$file_list_pageby, 25);
		$numPages = ceil($total/$limit);  
		$page = (!$page) ? 1 : $page;
		$offset = ($page - 1) * $limit;

		$sort = gps('sort');
		$dir = gps('dir');

		$sort = ($sort) ? $sort : 'filename';
		$dir = ($dir) ? $dir : 'desc';
		if ($dir == "desc") { $dir = "asc"; } else { $dir = "desc"; }

		$existing_files = get_filenames();

		echo startTable('list'),
		tr(
			tda(
				file_upload_form(gTxt('upload_file'),'upload','file_insert'),
					' colspan="4" style="border:0"'
			)
		),
		(count($existing_files)>0?
			tr(
				tda(
					form(
						graf(gTxt('existing_file').sp.
						selectInput('filename',$existing_files,"",1).sp.
						fInput('submit','',gTxt('Create'),'smallerbox').sp.
						eInput('file').
						sInput('file_create'))
					),
					' colspan="4" style="border:0"'
				)
			):''),
		tr(
			column_head('Id','id','file',1,$dir).
			column_head('file_name','filename','file',1,$dir).
			td(gTxt('status')).
			td(gTxt('tags')).
			column_head('file_category','category','file',1,$dir).
//			column_head('permissions','permissions','file',1,$dir).
			column_head('description','description','file',1,$dir).
			column_head('downloads','downloads','file',1,$dir).
			td()
		);


		$nav[] = ($page > 1)
		?	PrevNextLink("file",$page-1,gTxt('prev'),'prev') : '';

		$nav[] = sp.small($page. '/'.$numPages).sp;

		$nav[] = ($page != $numPages) 
		?	PrevNextLink("file",$page+1,gTxt('next'),'next') : '';
		
		$rs = safe_rows_start("*", "txp_file", "1=1 order by $sort $dir limit $offset, $limit");
		
		if($rs) {
			while ($a = nextRow($rs)) {
			
				extract($a);
				
				// does the downloads column exist?
				if (!isset($downloads)) {
					// nope, add it
					safe_alter("txp_file", "ADD downloads INT DEFAULT '0' NOT NULL");
					$downloads = 0;
				} else {
					if (empty($downloads))
						$downloads = '0';
				}
				
				$elink = eLink('file','file_edit','id',$id,$filename);
				$dlink = dLink('file','file_delete','id',$id);
				//Add tags helper
				$txtilelink = '<a target="_blank" href="?event=tag'.a.'name=file'.a.'id='.$id.a.'description='.urlencode($description).a.'filename='.urlencode($filename).a.'type=textile" onclick="window.open(this.href, \'popupwindow\', \'width=400,height=400,scrollbars,resizable\'); return false;">Textile</a>';
				$txplink = '<a target="_blank" href="?event=tag'.a.'name=file'.a.'id='.$id.a.'description='.urlencode($description).a.'filename='.urlencode($filename).a.'type=textpattern" onclick="window.open(this.href, \'popupwindow\', \'width=400,height=400,scrollbars,resizable\'); return false;">Textpattern</a>';
				$xhtmlink = '<a target="_blank" href="?event=tag'.a.'name=file'.a.'id='.$id.a.'description='.urlencode($description).a.'filename='.urlencode($filename).a.'type=xhtml" onclick="window.open(this.href, \'popupwindow\', \'width=400,height=400,scrollbars,resizable\'); return false;">XHTML</a>';
				
				$file_exists = file_exists(build_file_path($file_base_path,$filename));
				$missing = '<span style="color:';
				$missing .= ($file_exists) ? 'green' : 'red';
				$missing .= '">';
				$missing .= ($file_exists)?gTxt('file_status_ok'):gTxt('file_status_missing');
				$missing .= '</span>';

				$downloadlink = ($file_exists)?make_download_link($id,$filename,$id):$id;

				echo
				tr(
					td($downloadlink).
					td($elink).
					td($missing).
					td($txtilelink.' / '.$txplink.' / '.$xhtmlink).
					td($category,90).
//					td(($permissions=='1')?gTxt('private'):gTxt('public'),80).
					td($description,150).
					td(($downloads=='0')?" 0":$downloads,20).
					td($dlink,10)
					
				);
			}

			echo 
				tr(
					tdcs(
						graf(join('',$nav))
					,8)
				);
		}
		echo endTable();

		echo pageby_form('file',$file_list_pageby);

		if (!is_dir($file_base_path) or !is_writeable($file_base_path)) {
		
			echo graf(str_replace("{filedir}",$file_base_path,gTxt('file_dir_not_writeable')),' style="text-align:center;color:red"');

		}
	}

// -------------------------------------------------------------
	function file_edit($message='',$id='') 
	{
		global $txpcfg,$file_base_path,$levels,$path_from_root;

		extract(doSlash(gpsa(array('name','category','permissions','description'))));
		
		if (!$id) $id = gps('id');

		pagetop('file',$message);

		$categories = getTree("root", "file");
		
		$rs = safe_row("*", "txp_file", "id='$id'");
		
		if ($rs) {
			extract($rs);
			
			if ($permissions=='') $permissions='-1';

			$file_exists = file_exists(build_file_path($file_base_path,$filename));
			
			$existing_files = get_filenames();


			$status = '<span style="color:';
			$status .= ($file_exists) ? 'green' : 'red';
			$status .= '">';
			$status .= ($file_exists)?gTxt('file_status_ok'):gTxt('file_status_missing');
			$status .= '</span>';

			$downloadlink = ($file_exists)?make_download_link($id,$filename,$filename):$filename;
			
			$form = '';
			
			if ($file_exists) {
				$form =	tr(
							td(
								form(
									graf(gTxt('file_category').br.treeSelectInput('category',
									 		$categories,$category)) .
//									graf(gTxt('permissions').br.selectInput('perms',$levels,$permissions)).
									graf(gTxt('description').br.text_area('description','100','400',$description)) .
									graf(fInput('submit','',gTxt('save'))) .
									hInput('filename',$filename).
									hInput('id',$id) .
									eInput('file') .
									sInput('file_save')
								)
							)
						);
			} else {
			
				$form =	tr(
							tda(
								hed(gTxt('file_relink'),3).
								file_upload_form(gTxt('upload_file'),'upload','file_replace',$id).
								form(
									graf(gTxt('existing_file').' '.
									selectInput('filename',$existing_files,"",1).
									fInput('submit','',gTxt('Save'),'smallerbox').
									eInput('file').
									hInput('id',$id).
									hInput('category',$category).
									hInput('perms',($permissions=='-1')?'':$permissions).
									hInput('description',$description).
									sInput('file_save')
									)
								),
								' colspan="4" style="border:0"'
							)
						);
			}
			echo startTable('list'),
			tr(
				td(
					graf(gTxt('file_status').br.$status) .
					graf(gTxt('file_name').br.$downloadlink) .
					graf(gTxt('file_download_count').br.(isset($downloads)?$downloads:0))					
				)
			),
			$form,
			endTable();
		}
	}

// -------------------------------------------------------------
	function file_db_add($filename,$category,$permissions,$description)
	{
		$rs = safe_insert("txp_file",
			"filename = '$filename',
			 category = '$category',
			 permissions = '$permissions',
			 description = '$description'
		");
		
		if ($rs)
			return mysql_insert_id();
			
		return false;
	}	
	
// -------------------------------------------------------------
	function file_create() 
	{	
		global $txpcfg,$extensions,$txp_user,$file_base_path;
		extract($txpcfg);
		extract(doSlash(gpsa(array('filename','category','permissions','description'))));

		$id = file_db_add($filename,$category,$permissions,$description);
		
		if($id === false){
			file_list(gTxt('file_upload_failed').' (db_add)');
		} else {
			$newpath = build_file_path($file_base_path,trim($filename));

			if (is_file($newpath)) {
				file_set_perm($newpath);
				file_list(gTxt('linked_to_file').' '.$filename);
			} else {
				file_list(gTxt('file_not_found').' '.$filename);
			}
		}
	}

// -------------------------------------------------------------
	function file_insert() 
	{	
		global $txpcfg,$extensions,$txp_user,$file_base_path;
		extract($txpcfg);
		extract(doSlash(gpsa(array('category','permissions','description'))));
       		
		$name = file_get_uploaded_name();
		$file = file_get_uploaded();

		if ($file === false) {
			// could not get uploaded file
			file_list(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg($_FILES['file']['error']));
			return;
		}
		
		if (!is_file(build_file_path($file_base_path,$name))) {

			$id = file_db_add($name,$category,$permissions,$description);
			
			if(!$id){
				file_list(gTxt('file_upload_failed').' (db_add)');
			} else {

				$newpath = build_file_path($file_base_path,trim($name));
				
				if(!shift_uploaded_file($file, $newpath)) {
					safe_delete("txp_file","id='$id'");
					safe_alter("txp_file", "auto_increment=$id");
					file_list($newpath.' '.gTxt('upload_dir_perms'));
					// clean up file
				} else {
					file_set_perm($newpath);
					file_edit(messenger('file',$name,'uploaded'),$id);
				}
			}
		} else {
			file_list(messenger(gTxt('file'),$name,gTxt('already_exists')));
		}
	}

// -------------------------------------------------------------
	function file_replace() 
	{	
		global $txpcfg,$extensions,$txp_user,$file_base_path;
		extract($txpcfg);
		$id = gps('id');

		$rs = safe_row('filename','txp_file',"id='$id'");
		
		if (!$rs) {
			file_list(messenger(gTxt('invalid_id'),$id,''));
			return;
		}
		
		extract($rs);
		
		$file = file_get_uploaded();
		$name = file_get_uploaded_name();

		if ($file === false) {
			// could not get uploaded file
			file_list(gTxt('file_upload_failed') ." $name ".upload_get_errormsg($_FILES['file']['error']));
			return;
		}

		if (!$filename) {
			file_list(gTxt('invalid_filename'));
		} else {
			$newpath = build_file_path($file_base_path,$filename);

			if (is_file($newpath)) {
				rename($newpath,$newpath.'.tmp');
			}
	
			if(!shift_uploaded_file($file, $newpath)) {
				safe_delete("txp_file","id='$id'");

				file_list($newpath.sp.gTxt('upload_dir_perms'));
				// rename tmp back
				rename($newpath.'.tmp',$newpath);
				
				// remove tmp upload
				unlink($file);				
			} else {
				file_set_perm($newpath);
				file_edit(messenger('file',$name,'uploaded'),$id);
				// clean up old
				if (is_file($newpath.'.tmp'))
					unlink($newpath.'.tmp');
			}
		}
	}


// -------------------------------------------------------------
	function file_reset_count() 
	{
		extract(doSlash(gpsa(array('id','filename','category','description'))));
		
		
		if ($id) {
			if (safe_update('txp_file','downloads=0',"id='${id}'")) {
				file_edit(gTxt('reset_file_count_success'),$id);
			}
		} else {
			file_list(gTxt('reset_file_count_failure'));
		}		
	}

// -------------------------------------------------------------
	function file_save() 
	{
		global $file_base_path;
		extract(doSlash(gpsa(array('id','filename','category','description'))));
		
		$permissions = "";
		if (isset($_GET['perms'])) {
			$permissions =  urldecode($_GET['perms']);
		} elseif (isset($_POST['perms'])) {
			$permissions = $_POST['perms'];
		}
		if (is_array($permissions)) {
			asort($permissions);
			$permissions = implode(",",$permissions);
		}

		$perms = doSlash($permissions);
		
		$old_filename = fetch('filename','txp_file','id','$id');
		
		if ($old_filename != false && strcmp($old_filename,$filename)!=0) {
			$old_path = build_file_path($file_base_path,$old_filename);
			$new_path = build_file_path($file_base_path,$filename);
			
			if (file_exists($old_path) && shift_uploaded_file($old_path,$new_path) === false) {
				file_list(messenger("file",$filename,"could not be renamed"));
				return;
			} else {
				file_set_perm($new_path);
			}
		}
		
		$rs = safe_update(
			"txp_file",
			"filename = '$filename',
			category = '$category',
			permissions = '$perms',
			description = '$description'",
			"id = '$id'"
		);
		
		if (!$rs) {
			// update failed, rollback name
			if (shift_uploaded_file($new_path,$old_path) === false) {
				file_list(messenger("file",$filename,"has become unsyned with database. Manually fix file name."));
				return;
			} else {
				file_list(messenger(gTxt('file'),$filename,"was not updated"));
				return;
			}
		}
		
		file_list(messenger(gTxt('file'),$filename,"updated"));
	}

// -------------------------------------------------------------
	function file_delete() 
	{
		global $txpcfg,$file_base_path;
		extract($txpcfg);
		$id = ps('id');
		
		$rs = safe_row("*", "txp_file", "id='$id'");
		if ($rs) {
			extract($rs);
			
			$filepath = build_file_path($file_base_path,$filename);
			
			$rsd = safe_delete("txp_file","id='$id'");
			$ul = false;
			if ($rsd && is_file($filepath))
				$ul = unlink($filepath);
			if ($rsd && $ul) {
				file_list(messenger(gTxt('file'),$filename,gTxt('deleted')));
				return;
			} else {
				file_list(messenger(gTxt('file_delete_failed'),$filename,''));
			}
		} else 
			file_list(messenger(gTxt('file_not_found'),$filename,''));
	}

// -------------------------------------------------------------
	function file_get_uploaded_name()
	{
		return $_FILES['thefile']['name'];
	}

// -------------------------------------------------------------
	function file_get_uploaded()
	{
		return get_uploaded_file($_FILES['thefile']['tmp_name']);		
	}
	
// -------------------------------------------------------------
	function file_set_perm($file)
	{
		return @chmod($file,0755);
	}	

// -------------------------------------------------------------
	function file_upload_form($label,$pophelp,$step,$id='')
	{
		global $file_max_upload_size;
		
		if (!$file_max_upload_size || intval($file_max_upload_size)==0) $file_max_upload_size = 2*(1024*1024);
		
		$max_file_size = (intval($file_max_upload_size)==0)? '': hInput('max_file_size',$file_max_upload_size);
			
		return upload_form($label, $pophelp, $step, 'file', $id, $max_file_size);
	}
	
// -------------------------------------------------------------
	function file_change_pageby() 
	{
		event_change_pageby('file');
		file_list();
	}
	
// -------------------------------------------------------------
	function file_change_max_size() 
	{
		$qty = gps('qty');
		safe_update('txp_prefs',"val=$qty","name='file_max_upload_size'");
		file_list();
	}

// -------------------------------------------------------------
	function make_download_link($id, $filename, $text)
	{
		global $permlink_mode;
		
		if ($permlink_mode == 'messy') {
			return '<a href="'.hu.'index.php?s=file_download&id='.$id.'" title="download file '.$filename.'">'.$text.'</a>';
		} else {
			return '<a href="'.hu.''.gTxt('file_download').'/'.$id.'" title="download file '.$filename.'">'.$text.'</a>';
		}
	}
	
// -------------------------------------------------------------
	function get_filenames()
	{
		global $file_base_path;
		
		$dirlist = array();

		if (!is_dir($file_base_path))
			return $dirlist;		
		
		if (chdir($file_base_path)) {
			if (function_exists('glob'))
				$g_array = glob("*.*");
			else {
				$dh = opendir($file_base_path);
				$g_array = array();
				while (false !== ($filename = readdir($dh))) {
					$g_array[] = $filename;
				}
				closedir($dh);
				
			}
			
			if ($g_array) {
				foreach ($g_array as $filename) {
					if (is_file($filename)) {
						$dirlist[$filename] = $filename;
					}
				}
			}
		}

		$files = array();
		$rs = safe_rows("filename", "txp_file", "1=1");

		if ($rs) {
			foreach ($rs as $a) {
				$files[$a['filename']] = $a['filename'];
			}
		}
		
		return array_diff($dirlist,$files);
	}
	
?>

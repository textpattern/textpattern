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

	function file_list($message = '') 
	{
		global $txpcfg, $extensions, $file_base_path;

		pagetop(gTxt('file'), $message);

		extract($txpcfg);
		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		if (!is_dir($file_base_path) or !is_writeable($file_base_path))
		{
			echo graf(
				str_replace('{filedir}', $file_base_path, gTxt('file_dir_not_writeable'))
			, ' id="warning"');
		}

		else
		{
			$existing_files = get_filenames();

			if (count($existing_files) > 0)
			{
				echo form(
					eInput('file').
					sInput('file_create').

					graf(gTxt('existing_file').sp.selectInput('filename', $existing_files, '', 1).sp.
						fInput('submit', '', gTxt('Create'), 'smallerbox'))

				, 'text-align: center;');
			}

			echo file_upload_form(gTxt('upload_file'), 'upload', 'file_insert');
		}

		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'id '.$dir;
			break;

			case 'filename':
				$sort_sql = 'filename '.$dir;
			break;

			case 'description':
				$sort_sql = 'description '.$dir.', filename desc';
			break;

			case 'category':
				$sort_sql = 'category '.$dir.', filename desc';
			break;

			case 'downloads':
				$sort_sql = 'downloads '.$dir.', filename desc';
			break;

			default:
				$dir = 'desc';
				$sort_sql = 'filename '.$dir;
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'id'			    => "id = '$crit_escaped'",
				'filename'    => "filename like '%$crit_escaped%'",
				'description' => "description like '%$crit_escaped%'",
				'category'    => "category like '%$crit_escaped%'"
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

		$total = safe_count('txp_file', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.file_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' style="text-align: center;"');
			}

			else
			{
				echo n.graf(gTxt('no_files_recorded'), ' style="text-align: center;"');
			}

			return;
		}

		$limit = max(@$file_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo file_search_form($crit, $search_method);

		$rs = safe_rows_start('*', 'txp_file', "$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			echo startTable('list').

				tr(
					column_head('ID', 'id', 'file', true, $switch_dir, $crit, $search_method).
					td().
					column_head('file_name', 'filename', 'file', true, $switch_dir, $crit, $search_method).
					column_head('description', 'description', 'file', true, $switch_dir, $crit, $search_method).
					column_head('file_category', 'category', 'file', true, $switch_dir, $crit, $search_method).
					// column_head('permissions', 'permissions', 'file', true, $switch_dir, $crit, $search_method).
					hCell(gTxt('tags')).
					hCell(gTxt('status')).
					column_head('downloads', 'downloads', 'file', true, $switch_dir, $crit, $search_method).
					hCell()
				);

			while ($a = nextRow($rs))
			{
				extract($a);

				$edit_url = '?event=file'.a.'step=file_edit'.a.'id='.$id.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

				$file_exists = file_exists(build_file_path($file_base_path, $filename));

				$download_link = ($file_exists) ? '<li>'.make_download_link($id).'</li>' : '';

				$category = ($category) ? '<span title="'.fetch_category_title($category, 'file').'">'.$category.'</span>' : '';

				$tag_url = '?event=tag'.a.'tag_name=file_download_link'.a.'id='.$id.a.'description='.urlencode($description).
					a.'filename='.urlencode($filename);

				$status = '<span class="';
				$status .= ($file_exists) ? 'ok' : 'not-ok';
				$status .= '">';
				$status .= ($file_exists) ? gTxt('file_status_ok') : gTxt('file_status_missing');
				$status .= '</span>';

				// does the downloads column exist?
				if (!isset($downloads))
				{
					// nope, add it
					safe_alter('txp_file', "ADD downloads INT DEFAULT '0' NOT NULL");
					$downloads = 0;
				}

				elseif (empty($downloads))
				{
					$downloads = '0';
				}

				echo tr(

					n.td($id).

					td(
						'<ul>'.
						'<li>'.href(gTxt('edit'), $edit_url).'</li>'.
						$download_link.
						'</ul>'
					, 65).

					td(
						href($filename, $edit_url)
					, 125).

					td($description, 150).
					td($category, 90).

					/*
					td(
						($permissions == '1') ? gTxt('private') : gTxt('public')
					,80).
					*/

					td(
						n.'<ul>'.
						n.t.'<li><a target="_blank" href="'.$tag_url.a.'type=textile" onclick="popWin(this.href, 400, 250); return false;">Textile</a></li>'.
						n.t.'<li><a target="_blank" href="'.$tag_url.a.'type=textpattern" onclick="popWin(this.href, 400, 250); return false;">Textpattern</a></li>'.
						n.t.'<li><a target="_blank" href="'.$tag_url.a.'type=xhtml" onclick="popWin(this.href, 400, 250); return false;">XHTML</a></li>'.
						n.'</ul>'
					, 75).

					td($status, 45).

					td(
						($downloads == '0' ? gTxt('none') : $downloads)
					, 25).

					td(
						dLink('file', 'file_delete', 'id', $id)
					, 10)
				);
			}

			echo endTable().

			nav_form('file', $page, $numPages, $sort, $dir, $crit, $search_method).

			pageby_form('file', $file_list_pageby);
		}
	}
	
// -------------------------------------------------------------

	function file_search_form($crit, $method)
	{
		$methods =	array(
			'id'					=> gTxt('ID'),
			'filename'		=> gTxt('file_name'),
			'description' => gTxt('description'),
			'category'		=> gTxt('file_category')
		);

		return search_form('file', 'file_list', $crit, $methods, $method, 'filename');
	}

// -------------------------------------------------------------

	function file_edit($message = '', $id = '') 
	{
		global $txpcfg, $file_base_path, $levels, $path_from_root;

		pagetop('file', $message);

		extract(gpsa(array('name', 'category', 'permissions', 'description', 'sort', 'dir', 'page', 'crit', 'search_method')));

		if (!$id)
		{
			$id = gps('id');
		}

		$categories = getTree('root', 'file');

		$rs = safe_row('*', 'txp_file', "id = '$id'");

		if ($rs)
		{
			extract($rs);

			if ($permissions=='') $permissions='-1';

			$file_exists = file_exists(build_file_path($file_base_path,$filename));
			
			$existing_files = get_filenames();

			$status = '<span class="';
			$status .= ($file_exists) ? 'ok' : 'not-ok';
			$status .= '">';
			$status .= ($file_exists)?gTxt('file_status_ok'):gTxt('file_status_missing');
			$status .= '</span>';

			$downloadlink = ($file_exists)?make_download_link($id, $filename):$filename;
			
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

									eInput('file') .
									sInput('file_save').

									hInput('filename', $filename).
									hInput('id', $id) .

									hInput('sort', $sort).
									hInput('dir', $dir).
									hInput('page', $page).
									hInput('crit', $crit).
									hInput('search_method', $search_method)
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
									sInput('file_save').

									hInput('id',$id).
									hInput('category',$category).
									hInput('perms',($permissions=='-1')?'':$permissions).
									hInput('description',$description).

									hInput('sort', $sort).
									hInput('dir', $dir).
									hInput('page', $page).
									hInput('crit', $crit).
									hInput('search_method', $search_method)

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
		global $txpcfg,$extensions,$txp_user,$file_base_path,$file_max_upload_size;
		extract($txpcfg);
		extract(doSlash(gpsa(array('category','permissions','description'))));

		$name = file_get_uploaded_name();
		$file = file_get_uploaded();

		if ($file === false) {
			// could not get uploaded file
			file_list(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg(@$_FILES['file']['error']));
			return;
		}

		if ($file_max_upload_size < filesize($file)) {
			unlink($file);
			file_list(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg(UPLOAD_ERR_FORM_SIZE));
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
		
		$max_file_size = (intval($file_max_upload_size) == 0) ? '': intval($file_max_upload_size);
			
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
		// DEPRECATED function; removed old code
		file_list();
	}

// -------------------------------------------------------------

	function make_download_link($id, $label = '')
	{
		global $permlink_mode;

		$label = ($label) ? $label : gTxt('download');

		$url = ($permlink_mode == 'messy') ? 
			hu.'index.php?s=file_download'.a.'id='.$id : 
			hu.''.gTxt('file_download').'/'.$id;

		return '<a href="'.$url.'">'.$label.'</a>';
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

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

	global $file_statuses;
	$file_statuses = array(
			2 => gTxt('hidden'),
			3 => gTxt('pending'),
			4 => gTxt('live'),
	);

	if ($event == 'file') {
		require_privs('file');

		$available_steps = array(
			'file_change_pageby'=> true,
			'file_multi_edit'	=> true,
			'file_edit'			=> false,
			'file_insert'		=> true,
			'file_list'			=> false,
			'file_replace'		=> true,
			'file_save'			=> true,
			'file_reset_count'	=> true,
			'file_create'		=> true
		);

		if(!$step or !bouncer($step, $available_steps)){
			file_list();
		} else $step();
	}

// -------------------------------------------------------------

	function file_list($message = '')
	{
		global $file_base_path, $file_statuses, $file_list_pageby, $txp_user, $event;

		pagetop(gTxt('file'), $message);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('file_sort_column', 'filename');
		if ($dir === '') $dir = get_pref('file_sort_dir', 'asc');
		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		echo '<div id="'.$event.'_control" class="txp-control-panel">';

		if (!is_dir($file_base_path) or !is_writeable($file_base_path))
		{
			echo graf(
				gTxt('file_dir_not_writeable', array('{filedir}' => $file_base_path))
			, ' id="warning"');
		}

		elseif (has_privs('file.edit.own'))
		{
			$existing_files = get_filenames();

			if (count($existing_files) > 0)
			{
				echo form(
					eInput('file').
					sInput('file_create').

					graf('<label for="file-existing">'.gTxt('existing_file').'</label>'.sp.selectInput('filename', $existing_files, '', 1, '', 'file-existing').sp.
						fInput('submit', '', gTxt('Create'), 'smallerbox'), ' class="existing-file"')

				, 'text-align: center;', '', 'post', '', '', 'assign_file');
			}

			echo file_upload_form(gTxt('upload_file'), 'upload', 'file_insert');
		}

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'id '.$dir;
			break;

			case 'description':
				$sort_sql = 'description '.$dir.', filename desc';
			break;

			case 'category':
				$sort_sql = 'category '.$dir.', filename desc';
			break;

			case 'title':
				$sort_sql = 'title '.$dir.', filename desc';
			break;

			case 'downloads':
				$sort_sql = 'downloads '.$dir.', filename desc';
			break;

			case 'author':
				$sort_sql = 'author '.$dir.', id asc';
			break;

			default:
				$sort = 'filename';
				$sort_sql = 'filename '.$dir;
			break;
		}

		set_pref('file_sort_column', $sort, 'file', 2, '', 0, PREF_PRIVATE);
		set_pref('file_sort_dir', $dir, 'file', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit != '')
		{
			$crit_escaped = doSlash(str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));

			$critsql = array(
				'id'          => "ID in ('" .join("','", do_list($crit_escaped)). "')",
				'filename'    => "filename like '%$crit_escaped%'",
				'title'       => "title like '%$crit_escaped%'",
				'description' => "description like '%$crit_escaped%'",
				'category'    => "category like '%$crit_escaped%'",
				'author'      => "author like '%$crit_escaped%'"
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
					n.graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
			}

			else
			{
				echo n.graf(gTxt('no_files_recorded'), ' class="indicator"').'</div>';
			}

			return;
		}

		$limit = max($file_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo file_search_form($crit, $search_method).'</div>';

		$rs = safe_rows_start('*', 'txp_file', "$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			$show_authors = !has_single_author('txp_file');

			echo n.'<div id="'.$event.'_container" class="txp-container txp-list">';
			echo '<form name="longform" id="files_form" method="post" action="index.php" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

			startTable('list', '', 'list').
				n.'<thead>'.
				tr(
					column_head('ID', 'id', 'file', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id').
					hCell('', '', ' class="actions"').
					column_head('file_name', 'filename', 'file', true, $switch_dir, $crit, $search_method, (('filename' == $sort) ? "$dir " : '').'name').
					column_head('title', 'title', 'file', true, $switch_dir, $crit, $search_method, (('title' == $sort) ? "$dir " : '').'title').
					column_head('description', 'description', 'file', true, $switch_dir, $crit, $search_method, (('description' == $sort) ? "$dir " : '').'description').
					column_head('file_category', 'category', 'file', true, $switch_dir, $crit, $search_method, (('category' == $sort) ? "$dir " : '').'category').
					// column_head('permissions', 'permissions', 'file', true, $switch_dir, $crit, $search_method).
					hCell(gTxt('tags'), '', ' class="tag-build"').
					hCell(gTxt('status'), '', ' class="status"').
					hCell(gTxt('condition'), '', ' class="condition"').
					column_head('downloads', 'downloads', 'file', true, $switch_dir, $crit, $search_method, (('downloads' == $sort) ? "$dir " : '').'downloads').
					($show_authors ? column_head('author', 'author', 'file', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '').
					hCell('', '', ' class="multi-edit"')
				).
				n.'</thead>';

			$tfoot = n.'<tfoot>'.tr(
				tda(
					select_buttons().
					file_multiedit_form($page, $sort, $dir, $crit, $search_method)
				,' class="multi-edit" colspan="'.($show_authors ? '12' : '11').'" style="text-align: right; border: none;"')
			).n.'</tfoot>';

			echo $tfoot;
			echo '<tbody>';

			$ctr = 1;

			while ($a = nextRow($rs))
			{
				extract($a);
				$filename = sanitizeForFile($filename);

				$edit_url = '?event=file'.a.'step=file_edit'.a.'id='.$id.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

				$file_exists = file_exists(build_file_path($file_base_path, $filename));

				$download_link = ($file_exists) ? '<li class="action-view">'.make_download_link($id, '', $filename).'</li>' : '';

				$category = ($category) ? '<span title="'.htmlspecialchars(fetch_category_title($category, 'file')).'">'.$category.'</span>' : '';

				$tag_url = '?event=tag'.a.'tag_name=file_download_link'.a.'id='.$id.a.'description='.urlencode($description).
					a.'filename='.urlencode($filename);

				$condition = '<span class="';
				$condition .= ($file_exists) ? 'ok' : 'not-ok';
				$condition .= '">';
				$condition .= ($file_exists) ? gTxt('file_status_ok') : gTxt('file_status_missing');
				$condition .= '</span>';

				$can_edit = has_privs('file.edit') || ($author == $txp_user && has_privs('file.edit.own'));

				echo tr(

					n.td($id, '', 'id').

					td(
						'<ul>'.
						($can_edit ? '<li class="action-edit">'.href(gTxt('edit'), $edit_url).'</li>' : '').
						$download_link.
						'</ul>'
					, 65, 'actions').

					td(
						($can_edit ? href(htmlspecialchars($filename), $edit_url) : htmlspecialchars($filename))
					, 125, 'name').

					td(htmlspecialchars($title), 90, 'title').
					td(htmlspecialchars($description), 150, 'description').
					td($category, 90, 'category').

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
					, 75, 'tag-build').

					td(in_array($status, array_keys($file_statuses)) ? $file_statuses[$status] : '<span class="not-ok">'.gTxt('none').'</span>', 45, 'status').

					td($condition, 45, 'condition').

					td(
						($downloads == '0' ? gTxt('none') : $downloads)
					, 25, 'downloads').

					($show_authors ? td(
						'<span title="'.htmlspecialchars(get_author_name($author)).'">'.htmlspecialchars($author).'</span>'
					, '', 'author') : '').

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
			nav_form('file', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).

			pageby_form('file', $file_list_pageby).
			n.'</div>'.n.'</div>';
		}
	}

// -------------------------------------------------------------

	function file_search_form($crit, $method)
	{
		$methods =	array(
			'id'          => gTxt('ID'),
			'filename'    => gTxt('file_name'),
			'title'       => gTxt('title'),
			'description' => gTxt('description'),
			'category'    => gTxt('file_category'),
			'author'      => gTxt('author')
		);

		return search_form('file', 'file_list', $crit, $methods, $method, 'filename');
	}

// -------------------------------------------------------------

	function file_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changecategory' => gTxt('changecategory'),
			'changeauthor'   => gTxt('changeauthor'),
			'delete'         => gTxt('delete'),
		);

		if (has_single_author('txp_file'))
		{
			unset($methods['changeauthor']);
		}

		if (!has_privs('file.delete.own') && !has_privs('file.delete'))
		{
			unset($methods['delete']);
		}

		return event_multiedit_form('file', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function file_multi_edit()
	{
		global $txp_user;
		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return file_list();
		}

		$selected = array_map('assert_int', $selected);
		$method   = ps('edit_method');
		$changed  = array();

		switch ($method)
		{
			case 'delete';
				return file_delete($selected);
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

		if (!has_privs('file.edit'))
		{
			if (has_privs('file.edit.own'))
			{
				$selected = safe_column('id', 'txp_file', 'id IN ('.join(',', $selected).') AND author=\''.doSlash($txp_user).'\'');
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
				if (safe_update('txp_file', "$key = '".doSlash($val)."'", "id = $id"))
				{
					$changed[] = $id;
				}
			}
		}

		if ($changed)
		{
			update_lastmod();

			return file_list(gTxt('file_updated', array('{name}' => join(', ', $changed))));
		}

		return file_list();
	}

// -------------------------------------------------------------

	function file_edit($message = '', $id = '')
	{
		global $file_base_path, $levels, $file_statuses, $txp_user, $event;

		extract(gpsa(array('name', 'title', 'category', 'permissions', 'description', 'sort', 'dir', 'page', 'crit', 'search_method', 'publish_now')));

		if (!$id)
		{
			$id = gps('id');
		}
		$id = assert_int($id);

		$categories = getTree('root', 'file');

		$rs = safe_row('*, unix_timestamp(created) as created, unix_timestamp(modified) as modified', 'txp_file', "id = $id");

		if ($rs)
		{
			extract($rs);
			$filename = sanitizeForFile($filename);

			if (!has_privs('file.edit') && !($author == $txp_user && has_privs('file.edit.own')))
			{
				file_list(gTxt('restricted_area'));
				return;
			}

			pagetop(gTxt('file'), $message);

			if ($permissions=='') $permissions='-1';
			if (!has_privs('file.publish') && $status >= 4) $status = 3;

			$file_exists = file_exists(build_file_path($file_base_path,$filename));
			$replace = ($file_exists) ? tr(td(file_upload_form(gTxt('replace_file'),'file_replace','file_replace',$id,'file-replace')), ' class="replace-file"') : '';

			$existing_files = get_filenames();

			$condition = '<span class="';
			$condition .= ($file_exists) ? 'ok' : 'not-ok';
			$condition .= '">';
			$condition .= ($file_exists)?gTxt('file_status_ok'):gTxt('file_status_missing');
			$condition .= '</span>';

			$downloadlink = ($file_exists)?make_download_link($id, htmlspecialchars($filename),$filename):htmlspecialchars($filename);

			$created =
					n.graf(checkbox('publish_now', '1', $publish_now, '', 'publish_now').'<label for="publish_now">'.gTxt('set_to_now').'</label>', ' class="publish-now"').

					n.graf(gTxt('or_publish_at').sp.popHelp('timestamp'), ' class="publish-at"').

					n.graf('<span class="label">'.gtxt('date').'</span>'.sp.
						tsi('year', '%Y', $rs['created']).' / '.
						tsi('month', '%m', $rs['created']).' / '.
						tsi('day', '%d', $rs['created'])
					, ' class="date posted created"'
					).

					n.graf('<span class="label">'.gTxt('time').'</span>'.sp.
						tsi('hour', '%H', $rs['created']).' : '.
						tsi('minute', '%M', $rs['created']).' : '.
						tsi('second', '%S', $rs['created'])
					, ' class="time posted created"'
					);

			$form = '';

			if ($file_exists) {
				$form =	tr(
							td(
								form(
									graf('<label for="file_title">'.gTxt('title').'</label>: '.fInput('text','title',$title,'','','',40,'','file_title'), ' class="title"') .
									graf('<label for="category">'.gTxt('file_category').'</label>'.br.treeSelectInput('category',
									 		$categories,$category), ' class="category"') .
//									graf(gTxt('permissions').br.selectInput('perms',$levels,$permissions)).
									graf('<label for="description">'.gTxt('description').'</label>'.br.text_area('description','100','400',$description, 'description'), ' class="description text"') .
									fieldset(radio_list('status', $file_statuses, $status, 4), gTxt('status'), 'file-status').
									fieldset($created, gTxt('timestamp'), 'file-created').
									pluggable_ui('file_ui', 'extend_detail_form', '', $rs).
									graf(fInput('submit','',gTxt('save'),'publish')).

									eInput('file') .
									sInput('file_save').

									hInput('filename', $filename).
									hInput('id', $id) .

									hInput('sort', $sort).
									hInput('dir', $dir).
									hInput('page', $page).
									hInput('crit', $crit).
									hInput('search_method', $search_method)
								, '', '', 'post', 'edit-form', '', 'file_details')
							)
						, ' class="file-detail exists"');
			} else {

				$ef_select = empty($existing_files) ? '' : gTxt('existing_file').' '.selectInput('filename',$existing_files,"",1);
				$form =	tr(
							tda(
								hed(gTxt('file_relink'),3).
								file_upload_form(gTxt('upload_file'),'file_reassign','file_replace',$id,'file-reassign').
								form(
									graf(
										$ef_select.
										pluggable_ui('file_ui', 'extend_detail_form', '', $rs).
										fInput('submit','',gTxt('Save'),'smallerbox').

										eInput('file').
										sInput('file_save').

										hInput('id',$id).
										hInput('category',$category).
										hInput('perms',($permissions=='-1') ? '' : $permissions).
										hInput('title',$title).
										hInput('description',$description).
										hInput('status',$status).

										hInput('sort', $sort).
										hInput('dir', $dir).
										hInput('page', $page).
										hInput('crit', $crit).
										hInput('search_method', $search_method)
									)
								, '', '', 'post', 'edit-form', '', 'assign_file'),
								' colspan="4" style="border:0"'
							)
						, ' class="file-detail not-exists"');
			}

			echo n.'<div id="'.$event.'_container" class="txp-container txp-edit">';
			echo startTable('list', '', 'edit-pane'),
			tr(
				td(
					graf(gTxt('file_status').': '.$condition, ' class="condition"') .
					graf(gTxt('file_name').': '.$downloadlink, ' class="name"') .
					graf(gTxt('file_download_count').': '.$downloads, ' class="downloads"')
				)
			, ' class="file-info"'),
			$form,
			$replace,
			endTable().
			n.'</div>';
		}
	}

// -------------------------------------------------------------
	function file_db_add($filename, $category, $permissions, $description, $size, $title='')
	{
		global $txp_user;
		$rs = safe_insert("txp_file",
			"filename = '$filename',
			 title = '$title',
			 category = '$category',
			 permissions = '$permissions',
			 description = '$description',
			 size = '$size',
			 created = now(),
			 modified = now(),
			 author = '".doSlash($txp_user)."'
		");

		if ($rs) {
			$GLOBALS['ID'] = mysql_insert_id( );
			return $GLOBALS['ID'];
		}

		return false;
	}

// -------------------------------------------------------------
	function file_create()
	{
		global $txp_user,$file_base_path;

		if (!has_privs('file.edit.own'))
		{
			file_list(gTxt('restricted_area'));
			return;
		}

		extract(doSlash(gpsa(array('filename','title','category','permissions','description'))));
		$filename = sanitizeForFile($filename);

		$size = filesize(build_file_path($file_base_path,$filename));
		$id = file_db_add($filename,$category,$permissions,$description,$size,$title);

		if($id === false){
			file_list(array(gTxt('file_upload_failed').' (db_add)', E_ERROR));
		} else {
			$newpath = build_file_path($file_base_path, $filename);

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
		global $txp_user,$file_base_path,$file_max_upload_size;

		if (!has_privs('file.edit.own'))
		{
			file_list(gTxt('restricted_area'));
			return;
		}

		extract(doSlash(gpsa(array('category','title','permissions','description'))));

		$name = file_get_uploaded_name();
		$file = file_get_uploaded();

		if ($file === false) {
			// could not get uploaded file
			file_list(array(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg($_FILES['thefile']['error']), E_ERROR));
			return;
		}

		$size = filesize($file);
		if ($file_max_upload_size < $size) {
			unlink($file);
			file_list(array(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg(UPLOAD_ERR_FORM_SIZE), E_ERROR));
			return;
		}

		$newname = sanitizeForFile($name);
		$newpath = build_file_path($file_base_path, $newname);

		if (!is_file($newname)) {

			$id = file_db_add(doSlash($newname),$category,$permissions,$description,$size,$title);

			if(!$id){
				file_list(array(gTxt('file_upload_failed').' (db_add)', E_ERROR));
			} else {

				$id = assert_int($id);

				if(!shift_uploaded_file($file, $newpath)) {
					safe_delete("txp_file","id = $id");
					safe_alter("txp_file", "auto_increment=$id");
					if ( isset( $GLOBALS['ID'])) unset( $GLOBALS['ID']);
					file_list(array($newpath.' '.gTxt('upload_dir_perms'), E_ERROR));
					// clean up file
				} else {
					file_set_perm($newpath);

					$message = gTxt('file_uploaded', array('{name}' => htmlspecialchars($newname)));

					file_edit($message, $id);
				}
			}
		}

		else
		{
			$message = gTxt('file_already_exists', array('{name}' => $newname));

			file_list($message);
		}
	}

// -------------------------------------------------------------
	function file_replace()
	{
		global $txp_user,$file_base_path;

		$id = assert_int(gps('id'));

		$rs = safe_row('filename, author','txp_file',"id = $id");

		if (!$rs) {
			file_list(messenger(array(gTxt('invalid_id'), E_ERROR),$id,''));
			return;
		}

		extract($rs);
		$filename = sanitizeForFile($filename);

		if (!has_privs('file.edit') && !($author == $txp_user && has_privs('file.edit.own')))
		{
			file_edit(gTxt('restricted_area'));
			return;
		}

		$file = file_get_uploaded();
		$name = file_get_uploaded_name();

		if ($file === false) {
			// could not get uploaded file
			file_list(gTxt('file_upload_failed') ." $name ".upload_get_errormsg($_FILES['thefile']['error']));
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
				safe_delete("txp_file","id = $id");

				file_list($newpath.sp.gTxt('upload_dir_perms'));
				// rename tmp back
				rename($newpath.'.tmp',$newpath);

				// remove tmp upload
				unlink($file);
			} else {
				file_set_perm($newpath);
				if ($size = filesize($newpath))
					safe_update('txp_file', 'size = '.$size.', modified = now()', 'id = '.$id);

				$message = gTxt('file_uploaded', array('{name}' => htmlspecialchars($name)));

				file_edit($message, $id);
				// clean up old
				if (is_file($newpath.'.tmp'))
					unlink($newpath.'.tmp');
			}
		}
	}


// -------------------------------------------------------------
	function file_reset_count()
	{
		// TODO: accompanying user interface

		extract(doSlash(gpsa(array('id','filename','category','description'))));

		if ($id) {
			$id = assert_int($id);
			if (safe_update('txp_file','downloads = 0',"id = $id")) {
				file_edit(gTxt('reset_file_count_success'),$id);
			}
		} else {
			file_list(gTxt('reset_file_count_failure'));
		}
	}

// -------------------------------------------------------------

	function file_save()
	{
		global $file_base_path, $txp_user;

		extract(doSlash(gpsa(array('id', 'category', 'title', 'description', 'status', 'publish_now', 'year', 'month', 'day', 'hour', 'minute', 'second'))));
		$filename = sanitizeForFile(gps('filename'));

		if ($filename == '') {
			$message = gTxt('file_not_updated', array('{name}' => $filename));
			return file_list($message);
		}

		$id = assert_int($id);

		$permissions = gps('perms');
		if (is_array($permissions)) {
			asort($permissions);
			$permissions = implode(",",$permissions);
		}

		$perms = doSlash($permissions);

		$rs = safe_row('filename, author', 'txp_file', "id=$id");
		if (!has_privs('file.edit') && !($rs['author'] == $txp_user && has_privs('file.edit.own')))
		{
			file_edit(gTxt('restricted_area'));
			return;
		}

		$old_filename = sanitizeForFile($rs['filename']);
		if ($old_filename != false && strcmp($old_filename, $filename) != 0)
		{
			$old_path = build_file_path($file_base_path,$old_filename);
			$new_path = build_file_path($file_base_path,$filename);

			if (file_exists($old_path) && shift_uploaded_file($old_path, $new_path) === false)
			{
				$message = gTxt('file_cannot_rename', array('{name}' => $filename));

				return file_list($message);
			}

			else
			{
				file_set_perm($new_path);
			}
		}

		$created_ts = @safe_strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
		if ($publish_now)
			$created = 'now()';
		elseif ($created_ts > 0)
			$created = "from_unixtime('".$created_ts."')";
		else
			$created = '';

		$size = filesize(build_file_path($file_base_path,$filename));
		$rs = safe_update('txp_file', "
			filename = '".doSlash($filename)."',
			title = '$title',
			category = '$category',
			permissions = '$perms',
			description = '$description',
			status = '$status',
			size = '$size',
			modified = now(),
			author = '".doSlash($txp_user)."'"
			.($created ? ", created = $created" : '')
		, "id = $id");

		if (!$rs)
		{
			// update failed, rollback name
			if (shift_uploaded_file($new_path, $old_path) === false)
			{
				$message = gTxt('file_unsynchronized', array('{name}' => $filename));

				return file_list($message);
			}

			else
			{
				$message = gTxt('file_not_updated', array('{name}' => $filename));

				return file_list($message);
			}
		}

		$message = gTxt('file_updated', array('{name}' => $filename));

		file_list($message);
	}

// -------------------------------------------------------------

	function file_delete($ids = array())
	{
		global $file_base_path, $txp_user;

		$ids  = $ids ? array_map('assert_int', $ids) : array(assert_int(ps('id')));
		$message = '';

		if (!has_privs('file.delete'))
		{
			if (has_privs('file.delete.own'))
			{
				$ids = safe_column('id', 'txp_file', 'id IN ('.join(',', $ids).') AND author=\''.doSlash($txp_user).'\'' );
			}
			else
			{
				$ids = array();
			}
		}

		if (!empty($ids))
		{
			$fail = array();

			$rs = safe_rows_start('id, filename', 'txp_file', 'id IN ('.join(',', $ids).')');

			if ($rs)
			{
				while ($a = nextRow($rs))
				{
					extract($a);

					$filepath = build_file_path($file_base_path, $filename);

					$rsd = safe_delete('txp_file', "id = $id");
					$ul  = false;

					if ($rsd && is_file($filepath))
					{
						$ul = unlink($filepath);
					}

					if (!$rsd or !$ul)
					{
						$fail[] = $id;
					}
				}
				if ($fail)
				{
					$message = messenger(gTxt('file_delete_failed'), join(', ', $fail), '');
				}
				else
				{
					update_lastmod();
					$message = gTxt('file_deleted', array('{name}' => join(', ', $ids)));
				}
			}
			else
			{
				$message = messenger(gTxt('file_not_found'), join(', ', $ids), '');
			}
		}
		file_list($message);
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
		return @chmod($file,0644);
	}

// -------------------------------------------------------------
	function file_upload_form($label,$pophelp,$step,$id='',$label_id='')
	{
		global $file_max_upload_size;

		if (!$file_max_upload_size || intval($file_max_upload_size)==0) $file_max_upload_size = 2*(1024*1024);

		$max_file_size = (intval($file_max_upload_size) == 0) ? '': intval($file_max_upload_size);

		return upload_form($label, $pophelp, $step, 'file', $id, $max_file_size, $label_id);
	}

// -------------------------------------------------------------
	function file_change_pageby()
	{
		event_change_pageby('file');
		file_list();
	}

// -------------------------------------------------------------

	function make_download_link($id, $label = '', $filename = '')
	{
		$label = ($label) ? $label : gTxt('download');
		$url = filedownloadurl($id, $filename);
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
			$g_array = glob("*.*");
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

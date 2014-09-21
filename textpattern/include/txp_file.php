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
			STATUS_HIDDEN  => gTxt('hidden'),
			STATUS_PENDING => gTxt('pending'),
			STATUS_LIVE    => gTxt('live'),
	);

	if ($event == 'file') {
		require_privs('file');

		global $all_file_cats, $all_file_authors;
		$all_file_cats = getTree('root', 'file');
		$all_file_authors = the_privileged('file.edit.own');

		$available_steps = array(
			'file_change_pageby' => true,
			'file_multi_edit'    => true,
			'file_edit'          => false,
			'file_insert'        => true,
			'file_list'          => false,
			'file_replace'       => true,
			'file_save'          => true,
			'file_create'        => true,
		);

		if ($step && bouncer($step, $available_steps)) {
			$step();
		} else {
			file_list();
		}
	}

// -------------------------------------------------------------

	function file_list($message = '')
	{
		global $file_base_path, $file_statuses, $file_list_pageby, $txp_user, $event;

		pagetop(gTxt('tab_file'), $message);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('file_sort_column', 'filename');
		if ($dir === '') $dir = get_pref('file_sort_dir', 'asc');
		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		echo '<h1 class="txp-heading">'.gTxt('tab_file').'</h1>';
		echo '<div id="'.$event.'_control" class="txp-control-panel">';

		if (!is_dir($file_base_path) or !is_writeable($file_base_path))
		{
			echo graf(
				gTxt('file_dir_not_writeable', array('{filedir}' => $file_base_path))
			, ' class="alert-block warning"');
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
						fInput('submit', '', gTxt('Create')), ' class="existing-file"')

				, '', '', 'post', '', '', 'assign_file');
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

		set_pref('file_sort_column', $sort, 'file', PREF_HIDDEN, '', 0, PREF_PRIVATE);
		set_pref('file_sort_dir', $dir, 'file', PREF_HIDDEN, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit != '')
		{
			$verbatim = preg_match('/^"(.*)"$/', $crit, $m);
			$crit_escaped = doSlash($verbatim ? $m[1] : str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));
			$critsql = $verbatim ?
				array(
					'id'          => "ID in ('" .join("','", do_list($crit_escaped)). "')",
					'filename'    => "filename = '$crit_escaped'",
					'title'       => "title = '$crit_escaped'",
					'description' => "description = '$crit_escaped'",
					'category'    => "category = '$crit_escaped'",
					'author'      => "author = '$crit_escaped'"
				) :	array(
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

		$criteria .= callback_event('admin_criteria', 'file_list', 0, $criteria);

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

			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo '<form name="longform" id="files_form" class="multi_edit_form" method="post" action="index.php">'.

				n.'<div class="txp-listtables">'.
				n.startTable('', '', 'txp-list').
				n.'<thead>'.
				tr(
					n.hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
					n.column_head('ID', 'id', 'file', true, $switch_dir, $crit, $search_method, (('id' == $sort) ? "$dir " : '').'id').
					n.column_head('file_name', 'filename', 'file', true, $switch_dir, $crit, $search_method, (('filename' == $sort) ? "$dir " : '').'name').
					n.column_head('title', 'title', 'file', true, $switch_dir, $crit, $search_method, (('title' == $sort) ? "$dir " : '').'title').
					n.column_head('description', 'description', 'file', true, $switch_dir, $crit, $search_method, (('description' == $sort) ? "$dir " : '').'files_detail description').
					n.column_head('file_category', 'category', 'file', true, $switch_dir, $crit, $search_method, (('category' == $sort) ? "$dir " : '').'category').
					// column_head('permissions', 'permissions', 'file', true, $switch_dir, $crit, $search_method).
					n.hCell(gTxt('tags'), '', ' class="files_detail tag-build"').
					n.hCell(gTxt('status'), '', ' class="status"').
					n.hCell(gTxt('condition'), '', ' class="condition"').
					n.column_head('downloads', 'downloads', 'file', true, $switch_dir, $crit, $search_method, (('downloads' == $sort) ? "$dir " : '').'downloads').
					($show_authors ? n.column_head('author', 'author', 'file', true, $switch_dir, $crit, $search_method, (('author' == $sort) ? "$dir " : '').'author') : '')
				).
				n.'</thead>';

			echo '<tbody>';

			$validator = new Validator();

			while ($a = nextRow($rs))
			{
				extract($a);
				$filename = sanitizeForFile($filename);

				$edit_url = '?event=file'.a.'step=file_edit'.a.'id='.$id.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

				$file_exists = file_exists(build_file_path($file_base_path, $filename));

				$download_link = ($file_exists) ? make_download_link($id, $downloads, $filename) : $downloads;

				$validator->setConstraints(array(new CategoryConstraint($category, array('type' => 'file'))));
				$vc = $validator->validate() ? '' : ' error';
				$category = ($category) ? '<span title="'.txpspecialchars(fetch_category_title($category, 'file')).'">'.$category.'</span>' : '';

				$tag_url = '?event=tag'.a.'tag_name=file_download_link'.a.'id='.$id.a.'description='.urlencode($description).
					a.'filename='.urlencode($filename);

				$condition = '<span class="';
				$condition .= ($file_exists) ? 'success' : 'error';
				$condition .= '">';
				$condition .= ($file_exists) ? gTxt('file_status_ok') : gTxt('file_status_missing');
				$condition .= '</span>';

				$can_edit = has_privs('file.edit') || ($author == $txp_user && has_privs('file.edit.own'));

				echo tr(
					n.td($can_edit ? fInput('checkbox', 'selected[]', $id) : '&#160;'
					, '', 'multi-edit').

					n.td(
						($can_edit ? href($id, $edit_url, ' title="'.gTxt('edit').'"') : $id).
						(($file_exists) ? sp.'<span class="files_detail">['.make_download_link($id, gTxt('download'), $filename).']</span>' : '')
					, '', 'id').

					td(
						($can_edit ? href(txpspecialchars($filename), $edit_url, ' title="'.gTxt('edit').'"') : txpspecialchars($filename))
					, '', 'name').

					td(txpspecialchars($title), '', 'title').
					td(txpspecialchars($description), '', 'files_detail description').
					td($category, '', 'category'.$vc).

					/*
					td(
						($permissions == '1') ? gTxt('private') : gTxt('public')
					).
					*/

					td(
						n.'<a target="_blank" href="'.$tag_url.a.'type=textile" onclick="popWin(this.href, 400, 250); return false;">Textile</a>'.sp.
						'&#124;'.sp.'<a target="_blank" href="'.$tag_url.a.'type=textpattern" onclick="popWin(this.href, 400, 250); return false;">Textpattern</a>'.sp.
						'&#124;'.sp.'<a target="_blank" href="'.$tag_url.a.'type=html" onclick="popWin(this.href, 400, 250); return false;">HTML</a>'
					, '', 'files_detail tag-build').

					td(in_array($status, array_keys($file_statuses)) ? $file_statuses[$status] : '<span class="error">'.gTxt('none').'</span>', '', 'status').

					td($condition, '', 'condition').

					td($download_link, '', 'downloads').

					($show_authors ? td(
						'<span title="'.txpspecialchars(get_author_name($author)).'">'.txpspecialchars($author).'</span>'
					, '', 'author') : '')
				);
			}

			echo '</tbody>',
				n, endTable(),
				n, '</div>',
				n, file_multiedit_form($page, $sort, $dir, $crit, $search_method),
				n, tInput(),
				n, '</form>',
				n, graf(
					toggle_box('files_detail'),
					' class="detail-toggle"'
				),
				n, '<div id="'.$event.'_navigation" class="txp-navigation">',
				n, nav_form('file', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit),
				n, pageby_form('file', $file_list_pageby),
				n, '</div>',
				n, '</div>';
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
		global $file_statuses, $all_file_cats, $all_file_authors;

		$categories = $all_file_cats ? treeSelectInput('category', $all_file_cats, '') : '';
		$authors = $all_file_authors ? selectInput('author', $all_file_authors, '', true) : '';
		$status = selectInput('status', $file_statuses, '', true);

		$methods = array(
			'changecategory' => array('label' => gTxt('changecategory'), 'html' => $categories),
			'changeauthor'   => array('label' => gTxt('changeauthor'), 'html' => $authors),
			'changestatus'   => array('label' => gTxt('changestatus'), 'html' => $status),
			'changecount'    => array('label' => gTxt('reset_download_count')),
			'delete'         => gTxt('delete'),
		);

		if (!$categories)
		{
			unset($methods['changecategory']);
		}

		if (has_single_author('txp_file'))
		{
			unset($methods['changeauthor']);
		}

		if (!has_privs('file.delete.own') && !has_privs('file.delete'))
		{
			unset($methods['delete']);
		}

		return multi_edit($methods, 'file', 'file_multi_edit', $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function file_multi_edit()
	{
		global $txp_user, $all_file_cats, $all_file_authors;

		// Empty entry to permit clearing the category
		$categories = array('');

		foreach ($all_file_cats as $row) {
			$categories[] = $row['name'];
		}

		$selected = ps('selected');

		if (!$selected or !is_array($selected))
		{
			return file_list();
		}

		$selected = array_map('assert_int', $selected);
		$method   = ps('edit_method');
		$changed  = array();
		$key = '';

		switch ($method)
		{
			case 'delete':
				return file_delete($selected);
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
				if (in_array($val, $all_file_authors))
				{
					$key = 'author';
				}
				break;

			case 'changecount':
				$key = 'downloads';
				$val = 0;
				break;

			case 'changestatus':
				$key = 'status';
				$val = ps('status');

				// do not allow to be set to an empty value
				if (!$val)
				{
					$selected = array();
				}
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
		global $file_base_path, $levels, $file_statuses, $txp_user, $event, $all_file_cats;

		extract(gpsa(array('name', 'title', 'category', 'permissions', 'description', 'sort', 'dir', 'page', 'crit', 'search_method', 'publish_now')));

		if (!$id)
		{
			$id = gps('id');
		}
		$id = assert_int($id);

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

			pagetop(gTxt('edit_file'), $message);

			if ($permissions=='') $permissions='-1';
			if (!has_privs('file.publish') && $status >= STATUS_LIVE) $status = STATUS_PENDING;

			$file_exists = file_exists(build_file_path($file_base_path,$filename));
			$existing_files = get_filenames();

			$replace = ($file_exists)
				? '<div class="summary-details replace-file">'.n.
						'<h3>'.gTxt('replace_file').sp.popHelp('file_replace').'</h3>'.n.
						'<div>'.n.
							file_upload_form('', '', 'file_replace', $id, 'file_replace').n.
						'</div>'.n.
					'</div>'.n
				: '<div class="summary-details upload-file">'.n.
						'<h3>'.gTxt('file_relink').sp.popHelp('file_reassign').'</h3>'.n.
						'<div>'.n.
							file_upload_form('', '', 'file_replace', $id, 'file_reassign').n.
						'</div>'.n.
					'</div>'.n;

			$condition = '<span class="'.(($file_exists) ? 'success' : 'error').'">'.
				(($file_exists) ? gTxt('file_status_ok') : gTxt('file_status_missing')).
				'</span>';

			$downloadlink = ($file_exists) ? make_download_link($id, txpspecialchars($filename),$filename) : txpspecialchars($filename);

			$created =
					graf(checkbox('publish_now', '1', $publish_now, '', 'publish_now') . '<label for="publish_now">'.gTxt('set_to_now').'</label>', ' class="edit-file-publish-now"').n.
					graf(gTxt('or_publish_at').sp.popHelp('timestamp'), ' class="edit-file-publish-at"').n.
					graf('<span class="label">'.gtxt('date').'</span>'.n.
						tsi('year', '%Y', $rs['created']).' / '.n.
						tsi('month', '%m', $rs['created']).' / '.n.
						tsi('day', '%d', $rs['created'])
					, ' class="edit-file-published"'
					).n.
					graf('<span class="label">'.gTxt('time').'</span>'.n.
						tsi('hour', '%H', $rs['created']).' : '.n.
						tsi('minute', '%M', $rs['created']).' : '.n.
						tsi('second', '%S', $rs['created'])
					, ' class="edit-file-created"'
					);

			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo '<div class="txp-edit">',
				hed(gTxt('edit_file'), 2),
				inputLabel('condition', $condition).n,
				inputLabel('name', $downloadlink).n,
				inputLabel('download_count', $downloads).n,
				$replace.n,
				'<div class="file-detail '.($file_exists ? '' : 'not-').'exists">'.n,
				form(
					(($file_exists)
					? inputLabel('file_status', radioSet($file_statuses, 'status', $status)).n.
						inputLabel('file_title', fInput('text', 'title', $title, '', '', '', INPUT_REGULAR, '', 'file_title'), 'title').n.
						inputLabel('file_category', treeSelectInput('category', $all_file_cats, $category, 'file_category'), 'file_category').n.
//						inputLabel('perms', selectInput('perms', $levels, $permissions), 'permissions').n.
						inputLabel('file_description', '<textarea id="file_description" name="description" rows="'.INPUT_XSMALL.'" cols="'.INPUT_LARGE.'">'.$description.'</textarea>', 'description', '', '', '').n.
						'<fieldset class="file-created">'.n.
							'<legend>'.n.
								gTxt('timestamp').n.
							'</legend>'.n.
							$created.n.
						'</fieldset>'.n.
						pluggable_ui('file_ui', 'extend_detail_form', '', $rs).
						graf(fInput('submit', '', gTxt('Save'), 'publish')).n.
						hInput('filename', $filename)
					: (empty($existing_files)
							? ''
							: gTxt('existing_file').n.selectInput('filename', $existing_files, '', 1)
						).n.
						pluggable_ui('file_ui', 'extend_detail_form', '', $rs).n.
						graf(fInput('submit', '', gTxt('Save'), 'publish')).n.
						hInput('category', $category).n.
						hInput('perms', ($permissions=='-1') ? '' : $permissions).n.
						hInput('title', $title).n.
						hInput('description', $description).n.
						hInput('status', $status)
					).
					eInput('file').n.
					sInput('file_save').n.
					hInput('id',$id).n.
					hInput('sort', $sort).n.
					hInput('dir', $dir).n.
					hInput('page', $page).n.
					hInput('crit', $crit).n.
					hInput('search_method', $search_method)
				, '', '', 'post', 'edit-form', '', (($file_exists) ? 'file_details' : 'assign_file')),
				'</div>'.n,
				'</div>'.n.'</div>';
		}
	}

// -------------------------------------------------------------
	function file_db_add($filename, $category, $permissions, $description, $size, $title='')
	{
		global $txp_user;

		if (trim($filename) === '') {
			return false;
		}

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
			$GLOBALS['ID'] = $rs;
			return $GLOBALS['ID'];
		}

		return false;
	}

// -------------------------------------------------------------
	function file_create()
	{
		global $txp_user, $file_base_path;

		if (!has_privs('file.edit.own'))
		{
			file_list(gTxt('restricted_area'));
			return;
		}

		extract(doSlash(array_map('assert_string', gpsa(array('filename','title','category','permissions','description')))));
		$safe_filename = sanitizeForFile($filename);
		if ($safe_filename != $filename) {
			file_list(array(gTxt('invalid_filename'), E_ERROR));
			return;
		}

		$size = filesize(build_file_path($file_base_path,$safe_filename));
		$id = file_db_add($safe_filename,$category,$permissions,$description,$size,$title);

		if($id === false){
			file_list(array(gTxt('file_upload_failed').' (db_add)', E_ERROR));
		} else {
			$newpath = build_file_path($file_base_path, $safe_filename);

			if (is_file($newpath)) {
				file_set_perm($newpath);
				update_lastmod();
				file_list(gTxt('linked_to_file').' '.$safe_filename);
			} else {
				file_list(gTxt('file_not_found').' '.$safe_filename);
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

		extract(doSlash(array_map('assert_string', gpsa(array('category','title','permissions','description')))));

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

		if (!is_file($newpath)) {

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
					update_lastmod();

					$message = gTxt('file_uploaded', array('{name}' => $newname));

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
				update_lastmod();
				if ($size = filesize($newpath))
					safe_update('txp_file', 'size = '.$size.', modified = now()', 'id = '.$id);

				$message = gTxt('file_uploaded', array('{name}' => txpspecialchars($name)));

				file_edit($message, $id);
				// clean up old
				if (is_file($newpath.'.tmp'))
					unlink($newpath.'.tmp');
			}
		}
	}

// -------------------------------------------------------------

	function file_save()
	{
		global $file_base_path, $txp_user;

		$varray = array_map('assert_string',
			gpsa(array('id', 'category', 'title', 'description', 'status', 'publish_now', 'year', 'month', 'day', 'hour', 'minute', 'second')));
		extract(doSlash($varray));
		$filename = $varray['filename'] = sanitizeForFile(gps('filename'));

		if ($filename == '') {
			$message = gTxt('file_not_updated', array('{name}' => $filename));
			return file_list($message);
		}

		$id = $varray['id'] = assert_int($id);

		$permissions = gps('perms');
		if (is_array($permissions)) {
			asort($permissions);
			$permissions = implode(",",$permissions);
		}
		$varray['permissions'] = $permissions;
		$perms = doSlash($permissions);

		$rs = safe_row('filename, author', 'txp_file', "id=$id");
		if (!has_privs('file.edit') && !($rs['author'] == $txp_user && has_privs('file.edit.own')))
		{
			file_edit(gTxt('restricted_area'));
			return;
		}

		$old_filename = $varray['old_filename'] = sanitizeForFile($rs['filename']);
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

		$constraints = array(
			'category' => new CategoryConstraint(gps('category'), array('type' => 'file')),
			'status'   => new ChoiceConstraint(gps('status'), array('choices' => array(STATUS_HIDDEN, STATUS_PENDING, STATUS_LIVE), 'message' => 'invalid_status'))
		);
		callback_event_ref('file_ui', 'validate_save', 0, $varray, $constraints);
		$validator = new Validator($constraints);

		$rs = $validator->validate() && safe_update('txp_file', "
			filename = '".doSlash($filename)."',
			title = '$title',
			category = '$category',
			permissions = '$perms',
			description = '$description',
			status = '$status',
			size = '$size',
			modified = now()"
			.($created ? ", created = $created" : '')
		, "id = $id");

		if (!$rs)
		{
			// update failed, rollback name
			if (isset($old_path) && shift_uploaded_file($new_path, $old_path) === false)
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

		update_lastmod();
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

					// Notify plugins of pending deletion, pass file's id and path
					callback_event('file_deleted', '', false, $id, $filepath);

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
		$label = ($label != '') ? $label : gTxt('download');
		$url = filedownloadurl($id, $filename);
		return '<a title="'.gTxt('download').'" href="'.$url.'">'.$label.'</a>';
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

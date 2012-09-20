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

	global $vars;
	$vars = array('Form','type','name','savenew','oldname');
	$essential_forms = array('comments','comments_display','comment_form','default','plainlinks','files');
	$form_types = array(
		'article'  => gTxt('article'),
		'misc'     => gTxt('misc'),
		'comment'  => gTxt('comment'),
		'category' => gTxt('category'),
		'file'     => gTxt('file'),
		'link'     => gTxt('link'),
		'section'  => gTxt('section'),
	);

	if ($event == 'form') {
		require_privs('form');

		bouncer($step,
			array(
				'form_edit'       => false,
				'form_create'     => false,
				'form_delete'     => true,
				'form_multi_edit' => true,
				'form_save'       => true,
				'save_pane_state' => true
			)
		);

		switch(strtolower($step)) {
			case "":                form_edit();             break;
			case "form_edit":       form_edit();             break;
			case "form_create":     form_create();           break;
			case "form_delete":     form_delete();           break;
			case "form_multi_edit": form_multi_edit();       break;
			case "form_save":       form_save();             break;
			case "save_pane_state": form_save_pane_state();  break;
		}

	}

// -------------------------------------------------------------
	function form_list($curname)
	{
		global $step,$essential_forms,$form_types;

		$types = formTypes('', false);

		$methods = array(
			'changetype' => array('label' => gTxt('changetype'), 'html' => $types),
			'delete'     => gTxt('delete'),
		);

		$out[] = '<p class="action-create">'.sLink('form','form_create',gTxt('create_new_form')).'</p>';

		$criteria = 1;
		$criteria .= callback_event('admin_criteria', 'form_list', 0, $criteria);

		$rs = safe_rows_start("*", "txp_form", "$criteria order by field(type,'" . join("','", array_keys($form_types)) . "') asc, name asc");

		if ($rs) {
			$ctr = 1;
			$prev_type = '';
			while ($a = nextRow($rs)){
				extract($a);
				$editlink = ($curname!=$name)
					?	eLink('form','form_edit','name',$name,$name)
					:	txpspecialchars($name);
				$modbox = (!in_array($name, $essential_forms))
					?	'<input type="checkbox" name="selected_forms[]" value="'.$name.'" />'
					:	'';

				if ($prev_type != $type) {
					$visipref = 'pane_form_'.$type.'_visible';
					$group_start = '<div class="form-list-group '.$type.'"><h3 class="lever'.(get_pref($visipref) ? ' expanded' : '').'"><a href="#'.$type.'">'.$form_types[$type].'</a></h3>'.n.
						'<div id="'.$type.'" class="toggle form-list" style="display:'.(get_pref($visipref) ? 'block' : 'none').'">'.n.
						'<ul class="plain-list">'.n;
					$group_end = ($ctr > 1) ? '</ul></div></div>'.n : '';
				} else {
					$group_start = $group_end = '';
				}

				$out[] = $group_end.$group_start;
				$out[] = '<li>'.n.'<span class="form-list-action">'.$modbox.'</span><span class="form-list-name">'.$editlink.'</span></li>';
				$prev_type = $type;
				$ctr++;
			}

			$out[] = '</ul></div></div>';
			$out[] = multi_edit($methods, 'form', 'form_multi_edit');

			return form( join('',$out),'','', 'post', '', '', 'allforms_form' ).
				script_js( <<<EOS
				$(document).ready(function() {
					$('#allforms_form').txpMultiEditForm({
						'checkbox' : 'input[name="selected_forms[]"][type=checkbox]',
						'row' : '.plain-list li, .form-list-name',
						'highlighted' : '.plain-list li'
					});
				});
EOS
				);
		}
	}

// -------------------------------------------------------------

	function form_multi_edit()
	{
		global $essential_forms;

		$method = ps('edit_method');
		$forms = ps('selected_forms');
		$affected = array();

		if ($forms and is_array($forms))
		{
			if ($method == 'delete')
			{
				foreach ($forms as $name)
				{
					if (form_delete($name))
					{
						$affected[] = $name;
					}
				}

				callback_event('forms_deleted', '', 0, $affected);

				$message = gTxt('forms_deleted', array('{list}' => join(', ', $affected)));

				form_edit($message);
			}

			if ($method == 'changetype')
			{
				$new_type = ps('type');

				foreach ($forms as $name)
				{
					if (form_set_type($name, $new_type))
					{
						$affected[] = $name;
					}
				}

				$message = gTxt('forms_updated', array('{list}' => join(', ', $affected)));

				form_edit($message);
			}

		}

		else
		{
			form_edit();
		}
	}

// -------------------------------------------------------------
	function form_create()
	{
		form_edit();
	}

// -------------------------------------------------------------
	function form_edit($message='')
	{
		global $event,$step,$essential_forms;
		pagetop(gTxt('edit_forms'),$message);

		extract(gpsa(array('Form','name','type')));
		$name = trim(preg_replace('/[<>&"\']/', '', $name));

		if ($step=='form_create') {
			$inputs = fInput('submit','savenew',gTxt('save_new'),'publish').
				eInput("form").sInput('form_save');
		} else {
			$name = (!$name or $step=='form_delete') ? 'default' : $name;
			$rs = safe_row("*", "txp_form", "name='".doSlash($name)."'");
//			if ($rs)
 {
				extract($rs);
				$inputs = fInput('submit','save',gTxt('save'),'publish').
					eInput("form").sInput('form_save').hInput('oldname',$name);
			}
		}

		if (!in_array($name, $essential_forms))
			$changename = graf(gTxt('form_name').br.fInput('text','name',$name,'edit','','',INPUT_REGULAR));
		else
			$changename = graf(gTxt('form_name').br.tag($name, 'em').hInput('name',$name));

		// Generate the tagbuilder links
		// Format of each entry is popTagLink -> array ( gTxt string, class/ID )
		$tagbuild_items = array(
			'article'         => array('articles', 'article-tags'),
			'link'            => array('links', 'link-tags'),
			'comment'         => array('comments', 'comment-tags'),
			'comment_details' => array('comment_details', 'comment-detail-tags'),
			'comment_form'    => array('comment_form', 'comment-form-tags'),
			'search_result'   => array('search_results_form', 'search-result-tags'),
			'file_download'   => array('file_download_tags', 'file-tags'),
			'category'        => array('category_tags', 'category-tags'),
			'section'         => array('section_tags', 'section-tags'),
		);

		$tagbuild_links = '';
		foreach ($tagbuild_items as $tb => $item) {
			$tagbuild_links .= '<div class="'.$item[1].'">'.hed('<a href="#'.$item[1].'">'.gTxt($item[0]).'</a>'
					, 3, ' class="lever'.(get_pref('pane_form_'.$item[1].'_visible') ? ' expanded' : '').'"').
					'<div id="'.$item[1].'" class="toggle on" style="display:'.(get_pref('pane_form_'.$item[1].'_visible') ? 'block' : 'none').'">'.popTagLinks($tb).'</div></div>';
		}

		$out =
			'<h1 class="txp-heading">'.gTxt('tab_forms').sp.popHelp('forms_overview').'</h1>'.
			'<div id="'.$event.'_container" class="txp-container">'.
			startTable('', '', 'txp-columntable').
			tr(
				tdtl(
					'<div id="tagbuild_links">'.hed(gTxt('tagbuilder'), 2).
					$tagbuild_links.
					'</div>'
				, ' class="column"').
				tdtl(
					'<form action="index.php" method="post" id="form_form">'.
						'<div id="main_content">'.
						'<div class="edit-title">'.gTxt('you_are_editing_form').sp.strong(($name) ? $name : gTxt('untitled')).'</div>'.
						'<textarea id="form" class="code" name="Form" cols="'.INPUT_LARGE.'" rows="'.INPUT_REGULAR.'">'.txpspecialchars($Form).'</textarea>'.

					$changename.

					graf(gTxt('form_type').br.
						formtypes($type)).
					(empty($type) ? graf(gTxt('only_articles_can_be_previewed')) : '').
					(empty($type) || $type == 'article' ? fInput('submit','form_preview',gTxt('preview')) : '' ).
					graf($inputs).
					'</div>'.
					n.tInput().
					n.'</form>'

				, ' class="column"').
				tdtl(
					'<div id="content_switcher">'.hed(gTxt('all_forms'), 2).
					form_list($name).
					'</div>'
				, ' class="column"')
			).endTable().'</div>';

		echo $out;
	}

// -------------------------------------------------------------

	function form_save()
	{
		global $vars, $step, $essential_forms, $form_types;

		extract(doSlash(array_map('assert_string', gpsa($vars))));
		$name = doSlash(trim(preg_replace('/[<>&"\']/', '', gps('name'))));

		if (!$name)
		{
			$step = 'form_create';
			$message = gTxt('form_name_invalid');

			return form_edit(array($message, E_ERROR));
		}

		if (!isset($form_types[$type]))
		{
			$step = 'form_create';
			$message = gTxt('form_type_missing');

			return form_edit(array($message, E_ERROR));
		}

		if ($savenew)
		{
			$exists = safe_field('name', 'txp_form', "name = '$name'");

			if ($exists)
			{
				$step = 'form_create';
				$message = gTxt('form_already_exists', array('{name}' => $name));

				return form_edit(array($message, E_ERROR));
			}

			if (safe_insert('txp_form', "Form = '$Form', type = '$type', name = '$name'"))
			{
				update_lastmod();
				$message = gTxt('form_created', array('{name}' => $name));
			}
			else
			{
				$message = array(gTxt('form_save_failed'), E_ERROR);
			};

			return form_edit($message);
		}

		if (safe_update('txp_form', "Form = '$Form', type = '$type', name = '$name'", "name = '$oldname'"))
		{
			update_lastmod();
			$message = gTxt('form_updated', array('{name}' => $name));
		}
		else
		{
			$message = array(gTxt('form_save_failed'), E_ERROR);
		}
		form_edit($message);
	}

// -------------------------------------------------------------
	function form_delete($name)
	{
		global $essential_forms;
		if (in_array($name, $essential_forms)) return false;
		$name = doSlash($name);
		return safe_delete("txp_form","name='$name'");
	}

// -------------------------------------------------------------
	function form_set_type($name, $type)
	{
		global $essential_forms, $form_types;
		if (in_array($name, $essential_forms) || !isset($form_types[$type])) return false;
		$name = doSlash($name);
		$type = doSlash($type);
		return safe_update('txp_form', "type='$type'", "name='$name'");
	}

// -------------------------------------------------------------
	function formTypes($type, $blank_first = true)
	{
	 	global $form_types;
	 	return selectInput('type', $form_types, $type, $blank_first);
	}

// -------------------------------------------------------------
	function form_save_pane_state()
	{
		global $event;
		$panes = array('article', 'category', 'comment', 'file', 'link', 'misc', 'section', 'article-tags', 'link-tags', 'comment-tags', 'comment-detail-tags', 'comment-form-tags', 'search-result-tags', 'file-tags', 'category-tags', 'section-tags');
		$pane = gps('pane');
		if (in_array($pane, $panes))
		{
			set_pref("pane_form_{$pane}_visible", (gps('visible') == 'true' ? '1' : '0'), $event, PREF_HIDDEN, 'yesnoradio', 0, PREF_PRIVATE);
			send_xml_response();
		} else {
			trigger_error('invalid_pane', E_USER_WARNING);
		}
	}

?>

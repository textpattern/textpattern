<?php
/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

*/

/**
 * Forms panel.
 *
 * @package Admin\Form
 */

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

/**
 * List of essential forms.
 *
 * @global array $essential_forms
 */

	$essential_forms = array(
		'comments',
		'comments_display',
		'comment_form',
		'default',
		'plainlinks',
		'files',
	);

/**
 * List of form types.
 *
 * @global array $form_types
 */

	$form_types = array(
		'article'  => gTxt('article'),
		'misc'     => gTxt('misc'),
		'comment'  => gTxt('comment'),
		'category' => gTxt('category'),
		'file'     => gTxt('file'),
		'link'     => gTxt('link'),
		'section'  => gTxt('section'),
	);

	if ($event == 'form')
	{
		require_privs('form');

		bouncer($step, array(
			'form_edit'       => false,
			'form_create'     => false,
			'form_delete'     => true,
			'form_multi_edit' => true,
			'form_save'       => true,
			'save_pane_state' => true,
		));

		switch (strtolower($step))
		{
			case "" :
				form_edit();
				break;
			case "form_edit" :
				form_edit();
				break;
			case "form_create" :
				form_create();
				break;
			case "form_delete" :
				form_delete();
				break;
			case "form_multi_edit" :
				form_multi_edit();
				break;
			case "form_save" :
				form_save();
				break;
			case "save_pane_state" :
				form_save_pane_state();
				break;
		}
	}

/**
 * Renders a list of form templates.
 *
 * This function returns a list of form templates,
 * wrapped in a multi-edit form widget.
 *
 * @param  string $curname The selected form
 * @return string HTML
 */

	function form_list($curname)
	{
		global $essential_forms, $form_types;

		$criteria = 1;
		$criteria .= callback_event('admin_criteria', 'form_list', 0, $criteria);

		$rs = safe_rows_start(
			'*',
			'txp_form',
			"$criteria order by field(type, ".join(',', quote_list(array_keys($form_types))).") asc, name asc"
		);

		if ($rs)
		{
			$prev_type = null;
			$group_out = array();

			while ($a = nextRow($rs))
			{
				extract($a);
				$active = ($curname === $name);

				if ($prev_type !== $type)
				{
					if ($prev_type !== null)
					{
						$group_out = tag(n.join(n, $group_out).n, 'ul', array(
							'class' => 'switcher-list',
						));

						$out[] = wrapRegion($prev_type.'_forms_group', $group_out, 'form_'.$prev_type, $form_types[$prev_type], 'form_'.$prev_type);
					}

					$prev_type = $type;
					$group_out = array();
				}

				if ($active)
				{
					$editlink = txpspecialchars($name);
				}
				else
				{
					$editlink = eLink('form', 'form_edit', 'name', $name, $name);
				}

				if (!in_array($name, $essential_forms))
				{
					$modbox = tag(
						checkbox('selected_forms[]', txpspecialchars($name), false)
					, 'span', array('class' => 'switcher-action'));
				}
				else
				{
					$modbox = '';
				}

				$group_out[] = tag(n.$modbox.$editlink.n, 'li', array(
					'class' => $active ? 'active' : ''
				));
			}

			if ($prev_type !== null)
			{
				$group_out = tag(n.join(n, $group_out).n, 'ul', array(
					'class' => 'switcher-list',
				));

				$out[] = wrapRegion($prev_type.'_forms_group', $group_out, 'form_'.$prev_type, $form_types[$prev_type], 'form_'.$prev_type);
			}

			$methods = array(
				'changetype' => array('label' => gTxt('changetype'), 'html' => formTypes('', false, 'changetype')),
				'delete'     => gTxt('delete'),
			);

			$out[] = multi_edit($methods, 'form', 'form_multi_edit');

			return form(join('', $out), '', '', 'post', '', '', 'allforms_form');
		}
	}

/**
 * Processes multi-edit actions.
 */

	function form_multi_edit()
	{
		$method = ps('edit_method');
		$forms = ps('selected_forms');
		$affected = array();

		if ($forms && is_array($forms))
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

/**
 * Creates a new form.
 *
 * Directs requests back to the main editor panel,
 * armed with a 'form_create' step.
 */

	function form_create()
	{
		form_edit();
	}

/**
 * Renders the main Form editor panel.
 *
 * @param string|array $message The activity message
 */

	function form_edit($message = '')
	{
		global $event, $step, $essential_forms;

		pagetop(gTxt('edit_forms'), $message);

		extract(array_map('assert_string', gpsa(array(
			'copy',
			'save_error',
			'savenew',
		))));

		$name = sanitizeForPage(assert_string(gps('name')));
		$type = assert_string(gps('type'));
		$newname = sanitizeForPage(assert_string(gps('newname')));

		if ($step == 'form_delete' || empty($name) && $step != 'form_create' && !$savenew)
		{
			$name = 'default';
		}
		elseif (((($copy || $savenew) && $newname) || ($newname && $newname !== $name)) && !$save_error)
		{
			$name = $newname;
		}

		$Form = gps('Form');

		if (!$save_error)
		{
			$rs = safe_row('*', 'txp_form', "name='".doSlash($name)."'");
			extract($rs);
		}

		if (in_array($name, $essential_forms))
		{
			$name_widgets = gTxt('form_name').br.tag($name, 'span', 'class="txp-value-fixed"');
			$type_widgets = gTxt('form_type').br.tag($type, 'span', 'class="txp-value-fixed"');
		}
		else
		{
			$name_widgets = '<label for="new_form">'.gTxt('form_name').'</label>'.br.fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_form', false, true);
			$type_widgets = '<label for="type">'.gTxt('form_type').'</label>'.br.formTypes($type, false);
		}

		$buttons = href(gTxt('duplicate'), '#', array('id' => 'txp_clone', 'class' => 'clone', 'title' => gTxt('form_clone')));
		$buttons .= (empty($type) || $type == 'article') ? href(gTxt('preview'), '#',  array('id' => 'form_preview', 'class' => 'form-preview')) : '';

		$name_widgets .= (empty($name)) ? hInput('savenew', 'savenew') : n.'<span class="txp-actions">'.$buttons.'</span>'.n;

		// Generate the tagbuilder links.
		// Format of each entry is popTagLink -> array ( gTxt string, class/ID ).
		$tagbuild_items = array(
			'article'         => array('articles',            'article-tags'),
			'link'            => array('links',               'link-tags'),
			'comment'         => array('comments',            'comment-tags'),
			'comment_details' => array('comment_details',     'comment-detail-tags'),
			'comment_form'    => array('comment_form',        'comment-form-tags'),
			'search_result'   => array('search_results_form', 'search-result-tags'),
			'file_download'   => array('file_download_tags',  'file-tags'),
			'category'        => array('category_tags',       'category-tags'),
			'section'         => array('section_tags',        'section-tags'),
		);

		$tagbuild_links = '';
		foreach ($tagbuild_items as $tb => $item)
		{
			$tagbuild_links .= wrapRegion($item[1].'_group', popTagLinks($tb), $item[1], $item[0], $item[1]);
		}

 		echo
 		hed(gTxt('tab_forms').popHelp('forms_overview'), 1, 'class="txp-heading"').
 		n.'<div id="'.$event.'_container" class="txp-layout-grid">'.
 			n.'<div id="tagbuild_links" class="txp-layout-cell txp-layout-1-4">'.
 				hed(gTxt('tagbuilder'), 2).
 				$tagbuild_links.
 			n.'</div>'.

 			n.'<div id="main_content" class="txp-layout-cell txp-layout-2-4">'.
 			form(
 				graf($name_widgets).
 				graf(
 					'<label for="form">'.gTxt('form_code').'</label>'.
 					br.'<textarea id="form" class="code" name="Form" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'">'.txpspecialchars($Form).'</textarea>'
 				).
 				graf($type_widgets).
 				(empty($type) ? graf(gTxt('only_articles_can_be_previewed')) : '').
 				graf(
 					fInput('submit', 'save', gTxt('save'), 'publish').
 					eInput('form').sInput('form_save').
 					hInput('name', $name)
 				)
 			, '', '', 'post', 'edit-form', '', 'form_form').
 			n.'</div>'.

 			n.'<div id="content_switcher" class="txp-layout-cell txp-layout-1-4">'.
 				graf(sLink('form', 'form_create', gTxt('create_new_form')), ' class="action-create"').
 				form_list($name).
 			n.'</div>'
 		.'</div>';
	}

/**
 * Saves a form template.
 */

	function form_save()
	{
		global $essential_forms, $form_types;

		extract(doSlash(array_map('assert_string', psa(array(
			'savenew',
			'Form',
			'type',
			'copy',
		)))));

		$name = sanitizeForPage(assert_string(ps('name')));
		$newname = sanitizeForPage(assert_string(ps('newname')));

		$save_error = false;
		$message = '';

		if (in_array($name, $essential_forms))
		{
			$newname = $name;
			$type = fetch('type', 'txp_form', 'name', $newname);
			$_POST['newname'] = $newname;
		}

		if (!$newname)
		{
			$message = array(gTxt('form_name_invalid'), E_ERROR);
			$save_error = true;
		}
		else
		{
			if (!isset($form_types[$type]))
			{
				$message = array(gTxt('form_type_missing'), E_ERROR);
				$save_error = true;
			}
			else
			{
				if ($copy && $name === $newname)
				{
					$newname .= '_copy';
					$_POST['newname'] = $newname;
				}

				$exists = safe_field('name', 'txp_form', "name = '".doSlash($newname)."'");

				if ($newname !== $name && $exists)
				{
					$message = array(gTxt('form_already_exists', array('{name}' => $newname)), E_ERROR);
					if ($savenew)
					{
						$_POST['newname'] = '';
					}

					$save_error = true;
				}
				else
				{
					if ($savenew or $copy)
					{
						if ($newname)
						{
							if (safe_insert(
									'txp_form',
									"Form = '$Form',
									type = '$type',
									name = '".doSlash($newname)."'"
							)) {
								update_lastmod();
								$message = gTxt('form_created', array('{name}' => $newname));
							}
							else
							{
								$message = array(gTxt('form_save_failed'), E_ERROR);
								$save_error = true;
							}
						}
						else
						{
							$message = array(gTxt('form_name_invalid'), E_ERROR);
							$save_error = true;
						}
					}
					else
					{
						if (safe_update(
								'txp_form',
								"Form = '$Form',
								type = '$type',
								name = '".doSlash($newname)."'",
								"name = '".doSlash($name)."'"
						)) {
							update_lastmod();
							$message = gTxt('form_updated', array('{name}' => $name));
						}
						else
						{
							$message = array(gTxt('form_save_failed'), E_ERROR);
							$save_error = true;
						}
					}
				}
			}
		}

		if ($save_error === true)
		{
			$_POST['save_error'] = '1';
		}
		else
		{
			callback_event('form_saved', '', 0, $name, $newname);
		}

		form_edit($message);
	}

/**
 * Deletes a form template with the given name.
 *
 * @param  string $name The form template
 * @return bool   FALSE on error
 */

	function form_delete($name)
	{
		global $essential_forms;

		if (in_array($name, $essential_forms))
		{
			return false;
		}

		$name = doSlash($name);

		return safe_delete("txp_form", "name='$name'");
	}

/**
 * Changes a form template's type.
 *
 * @param  string $name The form template
 * @param  string $type The new type
 * @return bool   FALSE on error
 */

	function form_set_type($name, $type)
	{
		global $essential_forms, $form_types;

		if (in_array($name, $essential_forms) || !isset($form_types[$type]))
		{
			return false;
		}

		$name = doSlash($name);
		$type = doSlash($type);

		return safe_update('txp_form', "type='$type'", "name='$name'");
	}

/**
 * Renders a &lt;select&gt; input listing all form types.
 *
 * @param  string $type        The selected option
 * @param  bool   $blank_first If TRUE, the list defaults to an empty selection
 * @param  string $id          HTML id attribute value
 * @return string HTML
 * @access private
 */

	function formTypes($type, $blank_first = true, $id = 'type')
	{
	 	global $form_types;
	 	return selectInput('type', $form_types, $type, $blank_first, '', $id);
	}

/**
 * Saves a pane visibility state on the server.
 */

	function form_save_pane_state()
	{
		global $event;

		$panes = array(
			'form_article',
			'form_category',
			'form_comment',
			'form_file',
			'form_link',
			'form_misc',
			'form_section',
			'article-tags',
			'link-tags',
			'comment-tags',
			'comment-detail-tags',
			'comment-form-tags',
			'search-result-tags',
			'file-tags',
			'category-tags',
			'section-tags',
		);

		$pane = gps('pane');

		if (in_array($pane, $panes))
		{
			set_pref("pane_{$pane}_visible", (gps('visible') == 'true' ? '1' : '0'), $event, PREF_HIDDEN, 'yesnoradio', 0, PREF_PRIVATE);
			send_xml_response();
		}
		else
		{
			trigger_error('invalid_pane', E_USER_WARNING);
		}
	}

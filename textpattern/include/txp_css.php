<?php
/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

*/

/**
 * Styles panel.
 *
 * @package Admin\CSS
 */

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	if ($event == 'css')
	{
		require_privs('css');

		bouncer($step, array(
			'pour'       => false,
			'css_save'   => true,
			'css_delete' => true,
			'css_edit'   => false,
		));

		switch(strtolower($step))
		{
			case '' :
				css_edit();
				break;
			case 'pour' :
				css_edit();
				break;
			case 'css_save' :
				css_save();
				break;
			case 'css_delete' :
				css_delete();
				break;
			case 'css_edit' :
				css_edit();
				break;
		}
	}

/**
 * Renders a list of stylesheets.
 *
 * @param  string $current The active stylesheet
 * @param  string $default Not used
 * @return string HTML
 */

	function css_list($current, $default)
	{
		$out = array();
		$protected = safe_column('DISTINCT css', 'txp_section', '1=1');

		$criteria = 1;
		$criteria .= callback_event('admin_criteria', 'css_list', 0, $criteria);

		$rs = safe_rows_start('name', 'txp_css', $criteria);

		if ($rs)
		{
			$out[] = '<ul class="switcher-list">';

			while ($a = nextRow($rs))
			{
				extract($a);
				$active = ($current === $name);
				$edit = ($active) ? txpspecialchars($name) : eLink('css', '', 'name', $name, $name);
				$delete = (!array_key_exists($name, $protected)) ? dLink('css', 'css_delete', 'name', $name) : '';
				$out[] = '<li'.($active ? ' class="active"' : '').'>'.n.$edit.$delete.n.'</li>';
			}

			$out[] = '</ul>';

			return wrapGroup('all_styles', join(n, $out), 'all_stylesheets');
		}
	}

/**
 * The main stylesheet editor panel.
 *
 * @param string|array $message The activity message
 */

	function css_edit($message = '')
	{
		global $event, $step;

		pagetop(gTxt('edit_css'), $message);

		$default_name = safe_field('css', 'txp_section', "name = 'default'");

		extract(array_map('assert_string', gpsa(array(
			'copy',
			'save_error',
			'savenew',
		))));

		$name = sanitizeForPage(assert_string(gps('name')));
		$newname = sanitizeForPage(assert_string(gps('newname')));

		if ($step == 'css_delete' || empty($name) && $step != 'pour' && !$savenew)
		{
			$name = $default_name;
		}
		elseif ( ((($copy || $savenew) && $newname) || ($newname && ($newname != $name))) && !$save_error)
		{
			$name = $newname;
		}

		$buttons = n.'<label for="new_style">'.gTxt('css_name').'</label>'.br.fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_style', false, true);
		$buttons .= (empty($name))
			? hInput('savenew', 'savenew')
			: n.span(
				href(gTxt('duplicate'), '#', array(
					'id'    => 'txp_clone',
					'class' => 'clone',
					'title' => gTxt('css_clone')
				)), array('class' => 'txp-actions'));
		$thecss = gps('css');

		if (!$save_error)
		{
			$thecss = fetch('css', 'txp_css', 'name', $name);
		}

		$right =
			n.'<div id="content_switcher" class="txp-layout-cell txp-layout-1-4">'.
				graf(sLink('css', 'pour', gTxt('create_new_css')), ' class="action-create"').
				css_list($name, $default_name).
			n.'</div>';

		echo hed(gTxt('tab_style'), 1, array('class' => 'txp-heading'));
		echo n.'<div id="'.$event.'_container" class="txp-layout-grid">'.
			n.'<div id="main_content" class="txp-layout-cell txp-layout-3-4">'.
			form(
				graf($buttons).
				graf(
					'<label for="css">'.gTxt('css_code').'</label>'.
					br.'<textarea id="css" class="code" name="css" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'">'.txpspecialchars($thecss).'</textarea>'
				).
				graf(
					fInput('submit', '', gTxt('save'), 'publish').
					eInput('css').sInput('css_save').
					hInput('name', $name)
				)
			, '', '', 'post', 'edit-form', '', 'style_form').
			n.'</div>'.
			$right.
		n.'</div>';
	}

/**
 * Saves or clones a stylesheet.
 */

	function css_save()
	{
		extract(doSlash(array_map('assert_string', psa(array(
			'savenew',
			'copy',
			'css',
		)))));

		$name = sanitizeForPage(assert_string(ps('name')));
		$newname = sanitizeForPage(assert_string(ps('newname')));

		$save_error = false;
		$message = '';

		if (!$newname)
		{
			$message = array(gTxt('css_name_required'), E_ERROR);
			$save_error = true;
		}
		else
		{
			if ($copy && ($name === $newname))
			{
				$newname .= '_copy';
				$_POST['newname'] = $newname;
			}

			$exists = safe_field('name', 'txp_css', "name = '".doSlash($newname)."'");

			if (($newname !== $name) && $exists)
			{
				$message = array(gTxt('css_already_exists', array('{name}' => $newname)), E_ERROR);
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
						if (safe_insert('txp_css', "name = '".doSlash($newname)."', css = '$css'"))
						{
							update_lastmod();
							$message = gTxt('css_created', array('{name}' => $newname));
						}
						else
						{
							$message = array(gTxt('css_save_failed'), E_ERROR);
							$save_error = true;
						}
					}
					else
					{
						$message = array(gTxt('css_name_required'), E_ERROR);
						$save_error = true;
					}
				}
				else
				{
					if (safe_update('txp_css', "css = '$css', name = '".doSlash($newname)."'", "name = '".doSlash($name)."'"))
					{
						safe_update('txp_section', "css = '".doSlash($newname)."'", "css='".doSlash($name)."'");
						update_lastmod();
						$message = gTxt('css_updated', array('{name}' => $name));
					}
					else
					{
						$message = array(gTxt('css_save_failed'), E_ERROR);
						$save_error = true;
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
			callback_event('css_saved', '', 0, $name, $newname);
		}

		css_edit($message);
	}

/**
 * Deletes a stylesheet.
 */

	function css_delete()
	{
		$name  = ps('name');
		$count = safe_count('txp_section', "css = '".doSlash($name)."'");
		$message = '';

		if ($count)
		{
			$message = array(gTxt('css_used_by_section', array('{name}' => $name, '{count}' => $count)), E_ERROR);
		}
		else
		{
			if (safe_delete('txp_css', "name = '".doSlash($name)."'"))
			{
				callback_event('css_deleted', '', 0, $name);
				$message = gTxt('css_deleted', array('{name}' => $name));
			}
		}
		css_edit($message);
	}

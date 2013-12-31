<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2014 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
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
			while ($a = nextRow($rs))
			{
				extract($a);
				$active = ($current === $name);

				if ($active)
				{
					$edit = txpspecialchars($name);
				}
				else
				{
					$edit = eLink('css', '', 'name', $name, $name);
				}

				if (!array_key_exists($name, $protected))
				{
					$edit .= dLink('css', 'css_delete', 'name', $name);
				}

				$out[] = tag(n.$edit.n, 'li', array(
					'class' => $active ? 'active' : '',
				));
			}

			$out = tag(join(n, $out), 'ul', array(
				'class' => 'switcher-list'
			));

			return wrapGroup('all_styles', $out, 'all_stylesheets');
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
		elseif (((($copy || $savenew) && $newname) || ($newname && ($newname != $name))) && !$save_error)
		{
			$name = $newname;
		}

		$buttons = n.tag(gTxt('css_name'), 'label', array('for' => 'new_style')).
			br.fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_style', false, true);

		if ($name)
		{
			$buttons .= n.span(
				href(gTxt('duplicate'), '#', array(
					'id'    => 'txp_clone',
					'class' => 'clone',
					'title' => gTxt('css_clone')
				)), array('class' => 'txp-actions'));
		}
		else
		{
			$buttons .= hInput('savenew', 'savenew');
		}

		$thecss = gps('css');

		if (!$save_error)
		{
			$thecss = fetch('css', 'txp_css', 'name', $name);
		}

		echo hed(gTxt('tab_style'), 1, array('class' => 'txp-heading'));
		echo n.tag(
			n.tag(
				form(
					graf($buttons).
					graf(
						tag(gTxt('css_code'), 'label', array('for' => 'css')).
						br.'<textarea id="css" class="code" name="css" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'">'.txpspecialchars($thecss).'</textarea>'
					).
					graf(
						fInput('submit', '', gTxt('save'), 'publish').
						eInput('css').sInput('css_save').
						hInput('name', $name)
					)
				, '', '', 'post', 'edit-form', '', 'style_form').n
			, 'div', array(
				'id'    => 'main_content',
				'class' => 'txp-layout-cell txp-layout-3-4',
			)).

			n.tag(
				graf(sLink('css', 'pour', gTxt('create_new_css')), array('class' => 'action-create')).
				css_list($name, $default_name).n
			, 'div', array(
				'id'    => 'content_switcher',
				'class' => 'txp-layout-cell txp-layout-1-4',
			)).n

		, 'div', array(
			'id'    => $event.'_container',
			'class' => 'txp-layout-grid',
		));
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

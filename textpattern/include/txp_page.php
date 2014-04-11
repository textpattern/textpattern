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
 * Pages panel.
 *
 * @package Admin\Page
 */

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	if ($event == 'page')
	{
		require_privs('page');

		bouncer($step, array(
			'page_edit'   => false,
			'page_save'   => true,
			'page_delete' => true,
		));

		switch(strtolower($step))
		{
			case "" :
				page_edit();
				break;
			case "page_edit" :
				page_edit();
				break;
			case "page_save" :
				page_save();
				break;
			case "page_delete" :
				page_delete();
				break;
			case "page_new" :
				page_new();
				break;
		}
	}

/**
 * The main Page editor panel.
 *
 * @param string|array $message The activity message
 */

	function page_edit($message = '')
	{
		global $event, $step;

		pagetop(gTxt('edit_pages'), $message);

		extract(array_map('assert_string', gpsa(array(
			'copy',
			'save_error',
			'savenew',
		))));

		$name = sanitizeForPage(assert_string(gps('name')));
		$newname = sanitizeForPage(assert_string(gps('newname')));

		if ($step == 'page_delete' || empty($name) && $step != 'page_new' && !$savenew)
		{
			$name = safe_field('page', 'txp_section', "name = 'default'");
		}
		elseif (((($copy || $savenew) && $newname) || ($newname && ($newname != $name))) && !$save_error)
		{
			$name = $newname;
		}

		$buttons = n.tag(gTxt('page_name'), 'label', array('for' => 'new_page')).
			br.fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_page', false, true);

		if ($name)
		{
			$buttons .= span(href(gTxt('duplicate'), '#', array(
				'id'    => 'txp_clone',
				'class' => 'clone',
				'title' => gTxt('page_clone')
			)), array('class' => 'txp-actions'));
		}
		else
		{
			$buttons .= hInput('savenew', 'savenew');
		}

		$html = (!$save_error) ? fetch('user_html', 'txp_page', 'name', $name) : gps('html');

		// Format of each entry is popTagLink -> array ( gTxt() string, class/ID).
		$tagbuild_items = array(
			'page_article'     => array('page_article_hed',     'article-tags'),
			'page_article_nav' => array('page_article_nav_hed', 'article-nav-tags'),
			'page_nav'         => array('page_nav_hed',         'nav-tags'),
			'page_xml'         => array('page_xml_hed',         'xml-tags'),
			'page_misc'        => array('page_misc_hed',        'misc-tags'),
			'page_file'        => array('page_file_hed',        'file-tags'),
		);

		$tagbuild_links = '';
		foreach ($tagbuild_items as $tb => $item)
		{
			$tagbuild_links .= wrapRegion($item[1].'_group', taglinks($tb), $item[1], $item[0], 'page_'.$item[1]);
		}

		echo hed(gTxt('tab_pages'), 1, array('class' => 'txp-heading'));
		echo n.tag(

			n.tag(
				hed(gTxt('tagbuilder'), 2).
				$tagbuild_links
			, 'div', array(
				'id'    => 'tagbuild_links',
				'class' => 'txp-layout-cell txp-layout-1-4',
			)).

			n.tag(
				form(
					graf($buttons).
					graf(
						tag(gTxt('page_code'), 'label', array('for' => 'html')).
						br.'<textarea class="code" id="html" name="html" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($html).'</textarea>'
					).
					graf(
						fInput('submit', '', gTxt('save'), 'publish').
						eInput('page').sInput('page_save').
						hInput('name', $name)
					)
				, '', '', 'post', 'edit-form', '', 'page_form')
			, 'div', array(
				'id'    => 'main_content',
				'class' => 'txp-layout-cell txp-layout-2-4',
			)).

			n.tag(
				graf(sLink('page', 'page_new', gTxt('create_new_page')), ' class="action-create"').
				page_list($name).
			n, 'div', array(
				'id'    => 'content_switcher',
				'class' => 'txp-layout-cell txp-layout-1-4',
			)).n

		, 'div', array(
			'id'    => $event.'_container',
			'class' => 'txp-layout-grid'
		));
	}

/**
 * Renders a list of page templates.
 *
 * @param  string $current The selected template
 * @return string HTML
 */

	function page_list($current)
	{
		$out = array();
		$protected = safe_column('DISTINCT page', 'txp_section', '1=1') + array('error_default');

		$criteria = 1;
		$criteria .= callback_event('admin_criteria', 'page_list', 0, $criteria);

		$rs = safe_rows_start('name', 'txp_page', "$criteria order by name asc");

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
					$edit = eLink('page', '', 'name', $name, $name);
				}

				if (!in_array($name, $protected))
				{
					$edit .= dLink('page', 'page_delete', 'name', $name);
				}

				$out[] = tag($edit, 'li', array(
					'class' => $active ? 'active' : ''
				));
			}

			$out = tag(join(n, $out), 'ul', array(
				'class' => 'switcher-list'
			));

			return wrapGroup('all_pages', $out, 'all_pages');
		}
	}

/**
 * Deletes a page template.
 */

	function page_delete()
	{
		$name  = ps('name');
		$count = safe_count('txp_section', "page = '".doSlash($name)."'");
		$message = '';

		if ($name == 'error_default')
		{
			return page_edit();
		}

		if ($count)
		{
			$message = array(gTxt('page_used_by_section', array('{name}' => $name, '{count}' => $count)), E_WARNING);
		}
		else
		{
			if (safe_delete('txp_page', "name = '".doSlash($name)."'"))
			{
				callback_event('page_deleted', '', 0, $name);
				$message = gTxt('page_deleted', array('{name}' => $name));
			}
		}

		page_edit($message);
	}

/**
 * Saves or clones a page template.
 */

	function page_save()
	{
		extract(doSlash(array_map('assert_string', psa(array(
			'savenew',
			'html',
			'copy',
		)))));

		$name = sanitizeForPage(assert_string(ps('name')));
		$newname = sanitizeForPage(assert_string(ps('newname')));

		$save_error = false;
		$message = '';

		if (!$newname)
		{
			$message = array(gTxt('page_name_invalid'), E_ERROR);
			$save_error = true;
		}
		else
		{
			if ($copy && ($name === $newname))
			{
				$newname .= '_copy';
				$_POST['newname'] = $newname;
			}

			$exists = safe_field('name', 'txp_page', "name = '".doSlash($newname)."'");

			if ($newname !== $name && $exists !== false)
			{
				$message = array(gTxt('page_already_exists', array('{name}' => $newname)), E_ERROR);
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
						if (safe_insert('txp_page', "name = '".doSlash($newname)."', user_html = '$html'"))
						{
							update_lastmod();
							$message = gTxt('page_created', array('{name}' => $newname));
						}
						else
						{
							$message = array(gTxt('page_save_failed'), E_ERROR);
							$save_error = true;
						}
					}
					else
					{
						$message = array(gTxt('page_name_invalid'), E_ERROR);
						$save_error = true;
					}
				}
				else
				{
					if (safe_update('txp_page', "user_html = '$html', name = '".doSlash($newname)."'", "name = '".doSlash($name)."'"))
					{
						safe_update('txp_section', "page = '".doSlash($newname)."'", "page='".doSlash($name)."'");
						update_lastmod();
						$message = gTxt('page_updated', array('{name}' => $name));
					}
					else
					{
						$message = array(gTxt('page_save_failed'), E_ERROR);
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
			callback_event('page_saved', '', 0, $name, $newname);
		}

		page_edit($message);
	}

/**
 * Directs requests to page_edit() armed with a 'page_new' step.
 *
 * @see page_edit()
 */

	function page_new()
	{
		page_edit();
	}

/**
 * Renders a list of tag builder options.
 *
 * @param  string $type
 * @return HTML
 * @access private
 * @see    popTagLinks()
 */

	function taglinks($type)
	{
		return popTagLinks($type);
	}
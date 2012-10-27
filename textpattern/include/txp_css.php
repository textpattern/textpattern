<?php

/**
 * Styles panel.
 *
 * @package Admin\CSS
 */

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'css') {
		require_privs('css');

		bouncer($step,
			array(
				'css_edit_raw' => false,
				'pour'         => false,
				'css_save'     => true,
				'css_copy'     => true,
				'css_delete'   => true,
				'css_edit'     => false,
			)
		);

		switch ($step) {
			case '': css_edit(); break;
			case 'css_edit_raw': css_edit();    break;
			case 'pour': css_edit();            break;
			case 'css_save': css_save();        break;
			case 'css_copy': css_copy();        break;
			case 'css_delete': css_delete();    break;
			case 'css_edit': css_edit();        break;
		}
	}

/**
 * Renders a list of styles.
 *
 * @param  string $current The active style
 * @param  string $default Not used
 * @return string A HTML &lt;table&gt;
 */

	function css_list($current, $default) {
		$out[] = startTable('', '', 'txp-list');

		$protected = safe_column('DISTINCT css', 'txp_section', '1=1');

		$criteria = 1;
		$criteria .= callback_event('admin_criteria', 'css_list', 0, $criteria);

		$rs = safe_rows_start('name', 'txp_css', $criteria);

		if ($rs) {
			while ($a = nextRow($rs)) {
				extract($a);
				$edit = ($current != $name) ?	eLink('css', '', 'name', $name, $name) : txpspecialchars($name);
				$delete = (!array_key_exists($name, $protected)) ? dLink('css', 'css_delete', 'name', $name) : '';
				$out[] = tr(td($edit).td($delete));
			}

			$out[] =  endTable();

			return join('', $out);
		}
	}

/**
 * The main editor panel as a complete HTML document.
 *
 * @param string|array $message The activity message
 * @see   css_edit_raw()
 */

	function css_edit($message='')
	{
		pagetop(gTxt("edit_css"),$message);
		global $step,$prefs;
		css_edit_raw();
	}

/**
 * The main editor without a header.
 *
 * @see css_edit()
 */

	function css_edit_raw() {
		global $event, $step;

		$default_name = safe_field('css', 'txp_section', "name = 'default'");
		extract(gpsa(array('name', 'newname', 'copy', 'savenew')));

		if ($step == 'css_delete' || empty($name) && $step != 'pour' && !$savenew)
		{
			$name = $default_name;
		}
		elseif (($copy || $savenew) && trim(preg_replace('/[<>&"\']/', '', $newname)) )
		{
			$name = $newname;
		}

		if (empty($name))
		{
			$buttons = '<div class="edit-title">'.
			gTxt('name_for_this_style').': '
			.fInput('text','newname','','','','',INPUT_REGULAR).
			hInput('savenew','savenew').
			'</div>';
			$thecss = gps('css');

		} else {
			$buttons = '<div class="edit-title">'.gTxt('you_are_editing_css').sp.strong(txpspecialchars($name)).'</div>';
			$thecss = fetch("css",'txp_css','name',$name);
		}

		if (!empty($name)) {
			$copy =
				n.'<p class="copy-as"><label for="copy-css">'.gTxt('copy_css_as').'</label>'.
				n.fInput('text','newname','','input-medium','','',INPUT_MEDIUM,'','copy-css').
				n.fInput('submit', 'copy', gTxt('copy')).'</p>';
		} else {
			$copy = '';
		}

		$right =
		'<div id="content_switcher">'.
		hed(gTxt('all_stylesheets'),2).
		graf(sLink('css', 'pour', gTxt('create_new_css')), ' class="action-create"').
		css_list($name, $default_name).
		'</div>';

		echo
		'<h1 class="txp-heading">'.gTxt('tab_style').'</h1>'.
		'<div id="'.$event.'_container" class="txp-container">'.
		startTable('', '', 'txp-columntable').
		tr(
			td(
				form(
					'<div id="main_content">'.
					$buttons.
					'<textarea id="css" class="code" name="css" cols="'.INPUT_LARGE.'" rows="'.INPUT_REGULAR.'">'.txpspecialchars($thecss).'</textarea>'.
					'<p>'.fInput('submit','',gTxt('save'),'publish').
					eInput('css').sInput('css_save').
					hInput('name',$name).'</p>'
					.$copy.
					'</div>'
				, '', '', 'post', 'edit-form', '', 'style_form')
			, '', 'column').
			tdtl(
				$right
			, ' class="column"')
		).
		endTable().
		'</div>';
	}

/**
 * Copies an existing style.
 */

	function css_copy()
	{
		extract(psa(array('oldname', 'newname')));

		$css = doSlash(fetch('css', 'txp_css', 'name', $oldname));

		$rs = safe_insert('txp_css', "css = '$css', name = '".doSlash($newname)."'");

		css_edit(
			gTxt('css_created', array('{name}' => $newname))
		);
	}

/**
 * Saves a style.
 */

	function css_save()
	{
		extract(array_map('assert_string', psa(array('name','css','savenew','newname','copy'))));
		$css = doSlash($css);

		if ($savenew or $copy)
		{
			$newname = doSlash(trim(preg_replace('/[<>&"\']/', '', ps('newname'))));

			if ($newname and safe_field('name', 'txp_css', "name = '$newname'"))
			{
				$message = gTxt('css_already_exists', array('{name}' => $newname), E_ERROR);
				if ($savenew)
				{
					$_POST['newname'] = '';
				}
			}

			elseif ($newname)
			{
				if (safe_insert('txp_css', "name = '".$newname."', css = '$css'"))
				{
					update_lastmod();
					$message = gTxt('css_created', array('{name}' => $newname));
				}
				else
				{
					$message = array(gTxt('css_save_failed'), E_ERROR);
				}
			}
			else
			{
				$message = array(gTxt('css_name_required'), E_ERROR);
			}

			css_edit($message);
		}
		else
		{
			if (safe_update('txp_css', "css = '$css'", "name = '".doSlash($name)."'"))
			{
				update_lastmod();
				$message = gTxt('css_updated', array('{name}' => $name));
			}
			else
			{
				$message = array(gTxt('css_save_failed'), E_ERROR);
			}
			css_edit($message);
		}
	}

/**
 * Removes a style.
 */

	function css_delete()
	{
		$name  = ps('name');
		$count = safe_count('txp_section', "css = '".doSlash($name)."'");

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

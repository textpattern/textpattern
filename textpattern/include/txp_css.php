<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'css') {
		require_privs('css');

		bouncer($step,
			array(
				'css_edit_raw' 	=> false,
				'pour' 			=> false,
				'css_save' 		=> true,
				'css_copy' 		=> true,
				'css_delete' 	=> true,
				'css_edit' 		=> false,
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

//-------------------------------------------------------------
	function css_list($current, $default) {
		$out[] = startTable('list', 'left', 'list');

		$rs = safe_rows_start('name', 'txp_css', "1=1");

		$ctr = 1;

		if ($rs) {
			while ($a = nextRow($rs)) {
				extract($a);
				$edit = ($current != $name) ?	eLink('css', '', 'name', $name, $name) : htmlspecialchars($name);
				$delete = ($name != $default) ? dLink('css', 'css_delete', 'name', $name) : '';
				$trcls = ' class="'.((($ctr==1) ? 'first ' : '').(($ctr%2 == 0) ? 'even' : 'odd')).'"';
				$out[] = tr(td($edit).td($delete), $trcls);
				$ctr++;
			}

			$out[] =  endTable();

			return join('', $out);
		}
	}

//-------------------------------------------------------------
	function css_edit($message='')
	{
		pagetop(gTxt("edit_css"),$message);
		global $step,$prefs;
		css_edit_raw();
	}

//-------------------------------------------------------------
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
			.fInput('text','newname','','edit','','',20).
			hInput('savenew','savenew').
			'</div>';
			$thecss = gps('css');

		} else {
			$buttons = '<div class="edit-title">'.gTxt('you_are_editing_css').sp.strong(htmlspecialchars($name)).'</div>';
			$thecss = fetch("css",'txp_css','name',$name);
		}

		if (!empty($name)) {
			$copy = '<span class="copy-as"><label for="copy-css">'.gTxt('copy_css_as').'</label>'.sp.fInput('text', 'newname', '', 'edit', '', '', '', '', 'copy-css').sp.
				fInput('submit', 'copy', gTxt('copy'), 'smallerbox').'</span>';
		} else {
			$copy = '';
		}

		$right =
		'<div id="content_switcher">'.
		hed(gTxt('all_stylesheets'),2).
		graf(sLink('css', 'pour', gTxt('create_new_css')), ' class="action-create smallerbox"').
		css_list($name, $default_name).
		'</div>';

		echo
		'<div id="'.$event.'_container" class="txp-container txp-edit">'.
		startTable('edit').
		tr(
			td(
				form(
					'<div id="main_content">'.
					$buttons.
					'<textarea id="css" class="code" name="css" cols="78" rows="32">'.htmlspecialchars($thecss).'</textarea>'.br.
					fInput('submit','',gTxt('save'),'publish').
					eInput('css').sInput('css_save').
					hInput('name',$name)
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

// -------------------------------------------------------------
	function css_copy()
	{
		extract(gpsa(array('oldname', 'newname')));

		$css = doSlash(fetch('css', 'txp_css', 'name', $oldname));

		$rs = safe_insert('txp_css', "css = '$css', name = '".doSlash($newname)."'");

		css_edit(
			gTxt('css_created', array('{name}' => $newname))
		);
	}

//-------------------------------------------------------------
	function css_save()
	{
		extract(gpsa(array('name','css','savenew','newname','copy')));
		$css = doSlash($css);

		if ($savenew or $copy)
		{
			$newname = doSlash(trim(preg_replace('/[<>&"\']/', '', gps('newname'))));

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
				safe_insert('txp_css', "name = '".$newname."', css = '$css'");

				// update site last mod time
				update_lastmod();

				$message = gTxt('css_created', array('{name}' => $newname));
			}

			else
			{
				$message = array(gTxt('css_name_required'), E_ERROR);
			}

			css_edit($message);
		}

		else
		{
			safe_update('txp_css', "css = '$css'", "name = '".doSlash($name)."'");

			// update site last mod time
			update_lastmod();

			$message = gTxt('css_updated', array('{name}' => $name));

			css_edit($message);
		}
	}

//-------------------------------------------------------------
	function css_delete()
	{
		$name  = ps('name');
		$count = safe_count('txp_section', "css = '".doSlash($name)."'");

		if ($count)
		{
			$message = gTxt('css_used_by_section', array('{name}' => $name, '{count}' => $count));
		}

		else
		{
			safe_delete('txp_css', "name = '".doSlash($name)."'");

			$message = gTxt('css_deleted', array('{name}' => $name));
		}

		css_edit($message);
	}

?>

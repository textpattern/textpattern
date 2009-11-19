<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'css') {
		require_privs('css');

		switch ($step) {
			case '': css_edit(); break;
			case 'css_edit_raw': css_edit();           break;
			case 'pour': css_edit();	               break;
			case 'css_save': css_save();               break;
			case 'css_copy': css_copy();               break;
			case 'css_delete': css_delete();           break;
			case 'css_edit': css_edit();               break;
		}
	}

//-------------------------------------------------------------
	function css_list($current, $default) {
		$out[] = startTable('list', 'left');

		$rs = safe_rows_start('name', 'txp_css', "1=1");

		if ($rs) {
			while ($a = nextRow($rs)) {
				extract($a);
				$edit = ($current != $name) ?	eLink('css', '', 'name', $name, $name) : htmlspecialchars($name);
				$delete = ($name != $default) ? dLink('css', 'css_delete', 'name', $name) : '';

				$out[] = tr(td($edit).td($delete));
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
		global $step;

		$name = gps('name');

		$default_name = safe_field('css', 'txp_section', "name = 'default'");

		$name = (!$name or $step == 'css_delete') ? $default_name : $name;

		if (gps('copy') && trim(preg_replace('/[<>&"\']/', '', gps('newname'))) )
			$name = gps('newname');

		if ($step=='pour')
		{
			$buttons =
			gTxt('name_for_this_style').': '
			.fInput('text','newname','','edit','','',20).
			hInput('savenew','savenew');
			$thecss = '';

		} else {
			$buttons = '';
			$thecss = base64_decode(fetch("css",'txp_css','name',$name));
		}

		if ($step!='pour') {

			$left = graf(gTxt('you_are_editing_css').br.strong(htmlspecialchars($name))).
				graf(sLink('css', 'pour', gTxt('bulkload_existing_css')));

			$copy = gTxt('copy_css_as').sp.fInput('text', 'newname', '', 'edit').sp.
				fInput('submit', 'copy', gTxt('copy'), 'smallerbox');
		} else {
			$left = '&nbsp;';
			$copy = '';
		}

		$right =
		hed(gTxt('all_stylesheets'),2).
		css_list($name, $default_name);

		echo
		startTable('edit').
		tr(
			tdtl(
				$left
			).
			td(
				form(
					graf($buttons).
					'<textarea id="css" class="code" name="css" cols="78" rows="32">'.htmlspecialchars($thecss).'</textarea>'.br.
					fInput('submit','',gTxt('save'),'publish').
					eInput('css').sInput('css_save').
					hInput('name',$name)
					.$copy
				)
			).
			tdtl(
				$right
			)
		).
		endTable();
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
		$css = doSlash(base64_encode($css));

		if ($savenew or $copy)
		{
			$newname = doSlash(trim(preg_replace('/[<>&"\']/', '', gps('newname'))));

			if ($newname and safe_field('name', 'txp_css', "name = '$newname'"))
			{
				$message = gTxt('css_already_exists', array('{name}' => $newname));
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


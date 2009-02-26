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
			case 'css_edit_form': css_edit();          break;
			case 'pour': css_edit();	               break;
			case 'css_save': css_save();               break;
			case 'css_copy': css_copy();               break;
			case 'css_save_as': css_save_as();         break;
			case 'css_save_posted': css_save_posted(); break;
			case 'css_delete': css_delete();           break;
			case 'css_edit': css_edit();               break;
			case 'del_dec': css_edit();                break;
			case 'add_dec': css_edit();                break;
			case 'add_sel': css_edit();
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
		if (!$step or $step == 'css_save'){

			if ($prefs['edit_raw_css_by_default']) {
				css_edit_raw();
			} else {
				css_edit_form();
			}

		} else {

			if ($step=='css_edit_raw' or $step=='pour' or ($step=='css_delete' && $prefs['edit_raw_css_by_default'])) {
				css_edit_raw();
			} else {
				css_edit_form();
			}
		}
	}

// -------------------------------------------------------------

	function css_edit_form() {
		global $step;

		$name = gps('name');

		$default_name = safe_field('css', 'txp_section', "name = 'default'");

		$name = (!$name or $step == 'css_delete') ? $default_name : $name;

		if (gps('copy') && trim(preg_replace('/[<>&"\']/', '', gps('newname'))) )
			$name = gps('newname');
		$css = base64_decode(fetch("css",'txp_css','name',$name));
		$css = parseCSS($css);

		$css = ($step == 'add_dec') ? add_declaration($css) : $css;
		$css = ($step == 'del_dec') ? delete_declaration($css) : $css;
		$css = ($step == 'add_sel') ? addSel($css) : $css;

		$right =
		hed(gTxt('all_stylesheets'),2).
		css_list($name, $default_name);

		$left = graf(gTxt('you_are_editing_css').br.strong(htmlspecialchars($name))).
			graf(eLink('css', 'css_edit_raw', 'name', $name, gTxt('edit_raw_css'))).
			graf(sLink('css', 'pour', gTxt('bulkload_existing_css')));

		$out[] = startTable('css-edit', '', '', 3);

		$out[] =
		tr(
			td(strong(gTxt('css_selector'))).
			td(strong(gTxt('css_property_value')))
		);

		$i = -1;
		foreach($css as $selector=>$propvals) {
			$out[] = n.'<tr>'.n.
				td(fInput('text',++$i,$selector,'css')
				.' '.aLink('css','add_dec','selector',$selector,'name',$name)
				,'', 'selector','s'.$i);
			if(is_array($propvals))
			{
				$out[] = n.t.'<td class="selector2">';
				$ii = -1;
				foreach($propvals as $property=>$value)
				{
					$out[] = fInput('text',$i.'-'.++$ii.'p',$property,'css'). ' '
					.fInput('text',$i.'-'.$ii.'v',ltrim($value),'css').' '
					.dLink('css','del_dec','declaration',$i.'-'.$ii,'','name',$name,1).br;
				}
			$out[] = '</td>'.n.'</tr>';
		 }
		}

		$out[] = tr(tdcs(fInput('submit','',gTxt('save'),'publish'),2)).
		endTable().eInput('css').sInput('css_save_posted').hInput('name',$name);

		echo
		startTable('edit').
		tr(
			tdtl(
				$left
			).
			td(
				form(
					graf(
						gTxt('add_new_selector').': '.sp.
						fInput('text','selector','','css').sp.
						fInput('submit','add_sel',gTxt('submit'),'smallerbox').
						eInput('css') . sInput('add_sel') . hInput('name',$name)
					)
				).
				form(join('',$out)).

				form(
					graf(
						gTxt('copy_css_as').sp.fInput('text', 'newname', '', 'edit').sp.
						fInput('submit', 'copy', gTxt('copy'), 'smallerbox').
						eInput('css').
						sInput('css_copy').
						hInput('oldname', $name).
						hInput('name', $name)
					)
				)
			).
			tdtl(
				$right
			)
		).
		endTable();

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
				graf(eLink('css', 'css_edit_form', 'name', $name, gTxt('edit_css_in_form'))).
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
	function parseCSS($css) // parse raw css into a multidimensional array
	{
		$css = preg_replace("/\/\*.+\*\//Usi","",$css); // remove comments
		$selectors = preg_replace('/\s+/',' ',explode("}",strip_rn($css)));
		foreach($selectors as $selector) {
			if(trim($selector)) {
			list($keystr,$codestr) = explode("{",$selector);
				if (trim($keystr)) {
					$codes = explode(";",trim($codestr));
					foreach ($codes as $code) {
						if (trim($code)) {
							list($property,$value) = explode(":",$code,2);
							$out[trim($keystr)][trim($property)] = trim($value);
						}
					}
				}
			}
		}
		return (isset($out)) ? $out : array();
	}

// -------------------------------------------------------------
	function parsePostedCSS() //turn css info delivered by editor form into an array
	{
		$post = (MAGIC_QUOTES_GPC) ? doStrip($_POST) : $_POST;
		foreach($post as $a=>$b){
			if (preg_match("/^\d+$/",$a)) {
				$selector = $b;
			}
			if (preg_match("/^\d+-\d+(?:p|v)$/",$a)) {
				if(strstr($a,'p')) {
					$property = $b;
				} else {
					if(trim($property) && trim($selector)) {
						$out[$selector][$property] = $b;
					}
				}
			}
		}
		return (isset($out)) ? $out : array();
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

// -------------------------------------------------------------

	function css_save_posted()
	{
		$name = gps('name');
		$css  = parsePostedCSS();
		$css  = doSlash(base64_encode(css_format($css)));

		safe_update('txp_css', "css = '$css'", "name = '".doSlash($name)."'");

		// update site last mod time
		update_lastmod();

		$message = gTxt('css_updated', array('{name}' => $name));

		css_edit($message);
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

// -------------------------------------------------------------
	function css_format($css,$out='')
	{
		foreach ($css as $selector => $propvals) {
			$out .= n.$selector.n.'{'.n;
			foreach($propvals as $prop=>$val) {
				$out .= t.$prop.': '.$val.';'.n;
			}
			$out .= '}'.n;
		}
		return trim($out);
	}

// -------------------------------------------------------------
	function addSel($css)
	{
		$selector = gps('selector');
		$css[$selector][' '] = '';
		return $css;
	}

// -------------------------------------------------------------
	function add_declaration($css)
	{
		$selector = gps('selector');
		$css[$selector][' '] = '';
		return $css;
	}

// -------------------------------------------------------------
	function delete_declaration($css)
	{
		$thedec = gps('declaration');
		$name = gps('name');
		$i = 0;
		foreach($css as $a=>$b) {
			$cursel = $i++;
			$ii = 0;
			foreach($b as $c=>$d) {
				$curdec = $ii++;
				if(($cursel.'-'.$curdec)!=$thedec) {
					$out[$a][$c]=$d;
				}
			}
 		}
		$css = base64_encode(css_format($out));
		safe_update("txp_css", "css='".doSlash($css)."'", "name='".doSlash($name)."'");

		// update site last mod time
		update_lastmod();

		return parseCSS(base64_decode(fetch('css','txp_css','name',$name)));
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

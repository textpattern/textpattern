<?php

/*
$HeadURL$
$LastChangedRevision$
*/

//-------------------------------------------------------------

	function radioSet($vals, $field, $var, $tabindex = '', $id = '')
	{
		$id = ($id) ? $id.'-'.$field : $field;

		foreach ($vals as $a => $b)
		{
			$out[] = '<input type="radio" id="'.$id.'-'.$a.'" name="'.$field.'" value="'.$a.'" class="radio'.($a == $var ? ' active' : '').'"';
			$out[] = ($a == $var) ? ' checked="checked"' : '';
			$out[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
			$out[] = ' /><label for="'.$id.'-'.$a.'">'.$b.'</label> ';
		}

		return join('', $out);
	}

//-------------------------------------------------------------

	function yesnoRadio($field, $var, $tabindex = '', $id = '')
	{
		$vals = array(
			'0' => gTxt('no'),
			'1' => gTxt('yes')
		);
		return radioSet ($vals, $field, $var, $tabindex, $id);
	}

//-------------------------------------------------------------

	function onoffRadio($field, $var, $tabindex = '', $id = '')
	{
		$vals = array(
			'0' => gTxt('off'),
			'1' => gTxt('on')
		);

		return radioSet ($vals, $field, $var, $tabindex, $id);
	}

//-------------------------------------------------------------

	function selectInput($name = '', $array = '', $value = '', $blank_first = '', $onchange = '', $select_id = '', $check_type = false)
	{
		$out = array();

		$selected = false;

		foreach ($array as $avalue => $alabel)
		{
			if ($check_type) {
				if ($avalue === $value || $alabel === $value) {
					$sel = ' selected="selected"';
					$selected = true;
				} else {
					$sel = '';
				}
			}

			else {
				if ($avalue == $value || $alabel == $value) {
					$sel = ' selected="selected"';
					$selected = true;
				} else {
					$sel = '';
				}
			}

			$out[] = n.t.'<option value="'.txpspecialchars($avalue).'"'.$sel.'>'.txpspecialchars($alabel).'</option>';
		}

		return '<select'.( $select_id ? ' id="'.$select_id.'"' : '' ).' name="'.$name.'"'.
			($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange).
			'>'.
			($blank_first ? n.t.'<option value=""'.($selected == false ? ' selected="selected"' : '').'></option>' : '').
			( $out ? join('', $out) : '').
			n.'</select>';
	}

//-------------------------------------------------------------

	function treeSelectInput($select_name = '', $array = '', $value = '', $select_id = '', $truncate = 0)
	{
		$out = array();

		$selected = false;

		foreach ($array as $a)
		{
			if ($a['name'] == 'root')
			{
				continue;
			}

			extract($a);

			if ($name == $value)
			{
				$sel = ' selected="selected"';
				$selected = true;
			}

			else
			{
				$sel = '';
			}

			$sp = str_repeat(sp.sp, $level);

			if (($truncate > 3) && (strlen(utf8_decode($title)) > $truncate)) {
				$htmltitle = ' title="'.txpspecialchars($title).'"';
				$title = preg_replace('/^(.{0,'.($truncate - 3).'}).*$/su','$1',$title);
				$hellip = '&#8230;';
			} else {
				$htmltitle = $hellip = '';
			}

			$out[] = n.t.'<option value="'.txpspecialchars($name).'"'.$htmltitle.$sel.'>'.$sp.txpspecialchars($title).$hellip.'</option>';
		}

		return n.'<select'.( $select_id ? ' id="'.$select_id.'" ' : '' ).' name="'.$select_name.'">'.
			n.t.'<option value=""'.($selected == false ? ' selected="selected"' : '').'>&#160;</option>'.
			( $out ? join('', $out) : '').
			n.'</select>';
	}

//-------------------------------------------------------------
	function fInput($type, 		          // generic form input
					$name,
					$value,
					$class='',
					$title='',
					$onClick='',
					$size='',
					$tab='',
					$id='',
					$disabled = false,
					$required = false)
	{
		$o  = '<input type="'.$type.'"';
		$o .= ($type == 'file' || $type == 'image') ? '' : ' value="'.txpspecialchars($value).'"';
		$o .= strlen($name)? ' name="'.$name.'"' : '';
		$o .= ($size)     ? ' size="'.$size.'"' : '';
		$o .= ($class)    ? ' class="'.$class.'"' : '';
		$o .= ($title)    ? ' title="'.$title.'"' : '';
		$o .= ($onClick)  ? ' onclick="'.$onClick.'"' : '';
		$o .= ($tab)      ? ' tabindex="'.$tab.'"' : '';
		$o .= ($id)       ? ' id="'.$id.'"' : '';
		$o .= ($disabled) ? ' disabled="disabled"' : '';
		$o .= ($required) ? ' required' : '';
		$o .= " />";
		return $o;
	}

// -------------------------------------------------------------
	// deprecated in 4.2.0
	function cleanfInput($text)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'escape_title')), E_USER_NOTICE);
		return escape_title($text);
	}

//-------------------------------------------------------------
	function hInput($name,$value)		// hidden form input
	{
		return fInput('hidden',$name,$value);
	}

//-------------------------------------------------------------
	function sInput($step)				// hidden step input
	{
		return hInput('step',$step);
	}

//-------------------------------------------------------------
	function eInput($event)				// hidden event input
	{
		return hInput('event',$event);
	}

//-------------------------------------------------------------
	function tInput()				// hidden form token input
	{
		return hInput('_txp_token', form_token());
	}

//-------------------------------------------------------------

	function checkbox($name, $value, $checked = '1', $tabindex = '', $id = '')
	{
		$o[] = '<input type="checkbox" name="'.$name.'" value="'.$value.'"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($checked == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="checkbox'.($checked == '1' ? ' active' : '').'" />';

		return join('', $o);
	}

//-------------------------------------------------------------

	function checkbox2($name, $value, $tabindex = '', $id = '')
	{
		$o[] = '<input type="checkbox" name="'.$name.'" value="1"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($value == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="checkbox'.($value == '1' ? ' active' : '').'" />';

		return join('', $o);
	}

//-------------------------------------------------------------

	function radio($name, $value, $checked = '1', $id = '', $tabindex = '')
	{
		$o[] = '<input type="radio" name="'.$name.'" value="'.$value.'"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($checked == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="radio'.($checked == '1' ? ' active' : '').'" />';

		return join('', $o);
	}

//-------------------------------------------------------------

	function form($contents, $style = '', $onsubmit = '', $method = 'post', $class = '', $fragment = '', $id = '')
	{
		return n.'<form method="'.$method.'" action="index.php'.($fragment ? '#'.$fragment.'"' : '"').
			($id ? ' id="'.$id.'"' : '').
			($class ? ' class="'.$class.'"' : '').
			($style ? ' style="'.$style.'"' : '').
			($onsubmit ? ' onsubmit="return '.$onsubmit.'"' : '').
			'>'.$contents.n.
			tInput().n.
			'</form>'.n;
	}

// -------------------------------------------------------------
	function fetch_editable($name,$event,$identifier,$id)
	{
		$q = fetch($name,'txp_'.$event,$identifier,$id);
		return txpspecialchars($q);
	}

//-------------------------------------------------------------

	function text_area($name, $h='', $w='', $thing = '', $id = '', $rows='5', $cols='40')
	{
		$id = ($id) ? ' id="'.$id.'"' : '';
		$rows = ' rows="' . ( ($rows && is_numeric($rows)) ? $rows : '5') . '"';
		$cols = ' cols="' . ( ($cols && is_numeric($cols)) ? $cols : '40') . '"';
		$width = ($w) ? 'width:'.$w.'px;' : '';
		$height = ($h) ? 'height:'.$h.'px;' : '';
		$style = ($width || $height) ? ' style="'.$width.$height.'"' : '';
		return '<textarea'.$id.' name="'.$name.'"'.$rows.$cols.$style.'>'.txpspecialchars($thing).'</textarea>';
	}

//-------------------------------------------------------------
	function type_select($options)
	{
		return '<select name="type">'.n.type_options($options).'</select>'.n;
	}

//-------------------------------------------------------------
	function type_options($array)
	{
		foreach($array as $a=>$b) {
			$out[] = t.'<option value="'.$a.'">'.gTxt($b).'</option>'.n;
		}
		return join('',$out);
	}


//-------------------------------------------------------------
	function radio_list($name, $values, $current_val='', $hilight_val='')
	{
		// $values is an array of value => label pairs
		foreach ($values as $k => $v)
		{
			$id = $name.'-'.$k;
			$out[] = n.t.'<li class="status-'.$k.' '.$v.($hilight_val == $k ? ' active' : '').'">'.radio($name, $k, ($current_val == $k) ? 1 : 0, $id).
				'<label for="'.$id.'">'.($hilight_val == $k ? strong($v) : $v).'</label></li>';
		}

		return '<ul class="status plain-list">'.join('', $out).n.'</ul>';
	}

//--------------------------------------------------------------
	function tsi($name,$datevar,$time,$tab='')
	{
		$size = ($name=='year' or $name=='exp_year') ? INPUT_XSMALL : INPUT_TINY;
		$s = ($time == 0)? '' : safe_strftime($datevar, $time);
		return n.'<input type="text" name="'.$name.'" value="'.
			$s
		.'" size="'.$size.'" maxlength="'.$size.'" class="'.$name.'"'.(empty($tab) ? '' : ' tabindex="'.$tab.'"').' title="'.gTxt('article_'.$name).'" />';
	}
?>

<?php

/*
$HeadURL$
$LastChangedRevision$
*/

//-------------------------------------------------------------

	function yesnoRadio($field, $var, $tabindex = '', $id = '')
	{
		$id = ($id) ? $id.'-'.$field : $field;

		$vals = array(
			'0' => gTxt('no'),
			'1' => gTxt('yes')
		);

		foreach ($vals as $a => $b)
		{
			$out[] = '<input type="radio" id="'.$id.'-'.$a.'" name="'.$field.'" value="'.$a.'" class="radio"';
			$out[] = ($a == $var) ? ' checked="checked"' : '';
			$out[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
			$out[] = ' /><label for="'.$id.'-'.$a.'">'.$b.'</label> ';
		}

		return join('', $out);
	}

//-------------------------------------------------------------

	function onoffRadio($field, $var, $tabindex = '', $id = '')
	{
		$id = ($id) ? $id.'-'.$field : $field;

		$vals = array(
			'0' => gTxt('off'),
			'1' => gTxt('on')
		);

		foreach ($vals as $a => $b)
		{
			$out[] = '<input type="radio" id="'.$id.'-'.$a.'" name="'.$field.'" value="'.$a.'" class="radio"';
			$out[] = ($a == $var) ? ' checked="checked"' : '';
			$out[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
			$out[] = ' /><label for="'.$id.'-'.$a.'">'.$b.'</label> ';
		}

		return join('', $out);
	}

//-------------------------------------------------------------

	function selectInput($name = '', $array = '', $value = '', $blank_first = '', $onchange = '', $select_id = '')
	{
		$out = array();

		$selected = false;

		foreach ($array as $avalue => $alabel)
		{
			if ($avalue == $value || $alabel == $value)
			{
				$sel = ' selected="selected"';
				$selected = true;
			}

			else
			{
				$sel = '';
			}

			$out[] = n.t.'<option value="'.htmlspecialchars($avalue).'"'.$sel.'>'.htmlspecialchars($alabel).'</option>';
		}

		return '<select'.( $select_id ? ' id="'.$select_id.'"' : '' ).' name="'.$name.'" class="list"'.
			($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange).
			'>'.
			($blank_first ? n.t.'<option value=""'.($selected == false ? ' selected="selected"' : '').'></option>' : '').
			( $out ? join('', $out) : '').
			n.'</select>';
	}

//-------------------------------------------------------------

	function treeSelectInput($select_name = '', $array = '', $value = '', $select_id = '')
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

			$out[] = n.t.'<option value="'.htmlspecialchars($name).'"'.$sel.'>'.$sp.htmlspecialchars($title).'</option>';
		}

		return n.'<select'.( $select_id ? ' id="'.$select_id.'" ' : '' ).' name="'.$select_name.'" class="list">'.
			n.t.'<option value=""'.($selected == false ? ' selected="selected"' : '').'>&nbsp;</option>'.
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
					$id='') 
	{
		$o  = '<input type="'.$type.'" name="'.$name.'"'; 
		$o .= ' value="'.cleanfInput($value).'"';
		$o .= ($size)    ? ' size="'.$size.'"' : '';
		$o .= ($class)   ? ' class="'.$class.'"' : '';
		$o .= ($title)   ? ' title="'.$title.'"' : '';
		$o .= ($onClick) ? ' onclick="'.$onClick.'"' : '';
		$o .= ($tab)     ? ' tabindex="'.$tab.'"' : '';
		$o .= ($id)      ? ' id="'.$id.'"' : '';
		$o .= " />";
		return $o;
	}

// -------------------------------------------------------------
	function cleanfInput($text) 
	{
		return str_replace(
			array('"',"'","<",">"),
			array("&#34;","&#39;","&#60;","&#62;"),
			$text
		);
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

	function checkbox($name, $value, $checked = '1', $tabindex = '', $id = '')
	{
		$o[] = '<input type="checkbox" name="'.$name.'" value="'.$value.'"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($checked == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="checkbox" />';

		return join('', $o);
	}

//-------------------------------------------------------------

	function checkbox2($name, $value, $tabindex = '', $id = '')
	{
		$o[] = '<input type="checkbox" name="'.$name.'" value="1"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($value == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="checkbox" />';

		return join('', $o);
	}

//-------------------------------------------------------------

	function radio($name, $value, $checked = '1', $id = '', $tabindex = '')
	{
		$o[] = '<input type="radio" name="'.$name.'" value="'.$value.'"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($checked == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="radio" />';

		return join('', $o);
	}

//-------------------------------------------------------------

	function form($contents, $style = '', $onsubmit = '', $method = 'post', $class = '')
	{	
		return n.'<form method="'.$method.'" action="index.php"'.
			($class ? ' class="'.$class.'"' : '').
			($style ? ' style="'.$style.'"' : '').
			($onsubmit ? ' onsubmit="return '.$onsubmit.'"' : '').
			'>'.$contents.'</form>'.n;
	}

// -------------------------------------------------------------
	function fetch_editable($name,$event,$identifier,$id)
	{	
		$q = fetch($name,'txp_'.$event,$identifier,$id);
		return htmlspecialchars($q);
	}

//-------------------------------------------------------------

	function text_area($name, $h, $w, $thing = '', $id = '')
	{
		$id = ($id) ? ' id="'.$id.'"' : '';
		return '<textarea'.$id.' name="'.$name.'" cols="40" rows="5" style="width:'.$w.'px; height:'.$h.'px;">'.$thing.'</textarea>';
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

?>

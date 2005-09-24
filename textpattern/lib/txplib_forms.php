<?php

/*
$HeadURL$
$LastChangedRevision$
*/

//-------------------------------------------------------------	
	function yesnoRadio($field,$var)
	{
		$vals = array("0"=>gTxt('no'),"1"=>gTxt('yes'));
		foreach($vals as $a => $b) {
			$out[] = '<label><input type="radio" name="'.$field.'" value="'.$a.'" class="radio"';
			$out[] = ($a == $var) ? ' checked="checked"' : '';
			$out[] = " />$b</label> ";
		}
		return join('',$out);
	}

//-------------------------------------------------------------	
	function onoffRadio($field,$var)
	{
		$vals = array("0"=>gTxt('off'),"1"=>gTxt('on'));
		foreach($vals as $a => $b) {
			$out[] = '<label><input type="radio" name="'.$field.'" value="'.$a.'" class="radio"';
			$out[] = ($a == $var) ? ' checked="checked"' : '';
			$out[] = " />$b</label>  ";
		}
		return join('',$out);
	}

//-------------------------------------------------------------
	function selectInput($name="", $array="", $value="", $blankfirst='',$onchange='')
	{
		$out = '<select name="'.$name.'" class="list"';
		$out .= ($onchange == 1) ? ' onchange="submit(this.form)"' : $onchange;
		$out .= '>'.n;
		$out .= ($blankfirst) ? '<option value=""></option>' : '';
		foreach ($array as $avalue => $alabel) {
			$selected = ($avalue == $value || $alabel == $value)
			?	' selected="selected"'
			:	'';
			$alabel = htmlspecialchars($alabel);
			$out .= t.'<option value="'.htmlspecialchars($avalue).'"'.$selected.'>'.
					$alabel.'</option>'.n;
		}
		$out .= '</select>'.n;
		return $out;
	}

//-------------------------------------------------------------
	function treeSelectInput($selectname="", $array="", $value="")
	{
		$out[] = '<select name="'.$selectname.'" class="list">'.n;
		$out[] = '<option value=""></option>'.n;
		foreach ($array as $a) {
			extract($a);
			if ($name=='root') continue;
			$selected = ($name == $value) ? ' selected="selected"' : '';
			$name = htmlspecialchars($name);
			$sp = ($level > 0) ? str_repeat(sp.sp,$level-1) : '';
			$out[] = t.'<option value="'.$name.'"'.$selected.'>'.$sp.$title.'</option>'.n;
		}
		$out[] = '</select>'.n;
		return join('',$out);
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
	function checkbox($name,$value,$checked='1')
	{
		$o[] = '<input type="checkbox" name="'.$name.'" value="'.$value.'" id="'.$name.'"';
		$o[] = ($checked=='1') ? ' checked="checked"' : '';
		$o[] = ' />';
		return join('',$o);
	}

//-------------------------------------------------------------
	function checkbox2($name,$value)
	{
		$o[] = '<input type="checkbox" name="'.$name.'" value="1"';
		$o[] = ($value=='1')?' checked="checked"':'';
		$o[] = ' />';
		return join('',$o);
	}


//-------------------------------------------------------------
	function radio($name,$value,$checked='1')
	{
		$o[] = '<input type="radio" name="'.$name.'" value="'.$value.'"';
		$o[] = ($checked=='1')?' checked="checked"':'';
		$o[] = ' />';
		return join('',$o);
	}

//-------------------------------------------------------------
	function form($contents,$style='',$onsubmit='')
	{	
		$style = ($style) ? ' style="'.$style.'"' : '';
		$onsubmit = ($onsubmit) ? ' onsubmit="return '.$onsubmit.'"' : '';
		return "\n".'<form action="index.php" method="post"'.$style.$onsubmit.'>'.$contents.'</form>'."\n";
	}

// -------------------------------------------------------------
	function fetch_editable($name,$event,$identifier,$id)
	{	
		$q = fetch($name,'txp_'.$event,$identifier,$id);
		return htmlspecialchars($q);
	}

//-------------------------------------------------------------
	function text_area($name,$h,$w,$thing='')
	{
		return
		'<textarea name="'.$name.'" cols="1" rows="1" style="height:'.$h.'px;width:'.$w.'px;margin-bottom:1em;">'.$thing.'</textarea>';
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

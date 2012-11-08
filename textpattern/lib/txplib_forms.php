<?php

/**
 * Collection of HTML form widgets.
 *
 * @package Form
 */

/**
 * Generates a radio button toggle.
 *
 * @param  array  $vals     The values as an array
 * @param  string $field    The field name
 * @param  string $var      The selected option, takes a value from $vals
 * @param  int    $tabindex The HTML tabindex
 * @param  string $id       The HTML id
 * @return string A HTML radio button set
 */

	function radioSet($vals, $field, $var, $tabindex = 0, $id = '')
	{
		$id = ($id) ? $id.'-'.$field : $field;

		foreach ($vals as $a => $b)
		{
			$out[] = n.'<input type="radio" id="'.$id.'-'.$a.'" name="'.$field.'" value="'.$a.'" class="radio'.($a == $var ? ' active' : '').'"';
			$out[] = ($a == $var) ? ' checked="checked"' : '';
			$out[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
			$out[] = ' />'.n.'<label for="'.$id.'-'.$a.'">'.$b.'</label>';
		}

		return join('', $out);
	}

/**
 * Generates a Yes/No radio button toggle.
 *
 * These buttons are booleans. 'Yes' will have a value of 1 and 
 * 'No' is 0.
 *
 * @param  string $field    The field name
 * @param  string $var      The selected button. Either '1', '0'
 * @param  int    $tabindex The HTML tabindex
 * @param  string $id       The HTML id
 * @return string HTML
 * @see    radioSet()
 * @example
 * echo form(
 * 	'Is this an example?'.
 * 	yesnoRadio('is_example', 1)
 * );
 */

	function yesnoRadio($field, $var, $tabindex = 0, $id = '')
	{
		$vals = array(
			'0' => gTxt('no'),
			'1' => gTxt('yes')
		);
		return radioSet($vals, $field, $var, $tabindex, $id);
	}

/**
 * Generates an On/Off radio button toggle.
 *
 * @param  string $field    The field name
 * @param  string $var      The selected button. Either '1', '0'
 * @param  int    $tabindex The HTML tabindex
 * @param  string $id       The HTML id
 * @return string HTML
 * @see    radioSet()
 */

	function onoffRadio($field, $var, $tabindex = 0, $id = '')
	{
		$vals = array(
			'0' => gTxt('off'),
			'1' => gTxt('on')
		);

		return radioSet($vals, $field, $var, $tabindex, $id);
	}

/**
 * Generates a select field.
 *
 * @param  string $name        The field
 * @param  array  $array       The values as an array array( 'value' => 'label' )
 * @param  string $value       The selected option. Takes a value from $value
 * @param  bool   $blank_first If TRUE, prepends an empty option to the list
 * @param  mixed  $onchange    If TRUE submits the form when an option is changed. If a string, inserts it to the select tag
 * @param  string $select_id   The HTML id
 * @param  bool   $check_type  Type-agnostic comparison
 * @return string HTML
 * @example
 * echo selectInput('myInput', array(
 * 	'value1' => 'Label1',
 * 	'value2' => 'Label2',
 * ));
 */

	function selectInput($name = '', $array = '', $value = '', $blank_first = '', $onchange = '', $select_id = '', $check_type = false)
	{
		$out = array();

		$selected = false;
		$value = (string) $value;

		foreach ($array as $avalue => $alabel)
		{
			if ($value === (string) $avalue || $value === (string) $alabel)
			{
				$sel = ' selected="selected"';
				$selected = true;
			}
			else
			{
				$sel = '';
			}

			$out[] = n.'<option value="'.txpspecialchars($avalue).'"'.$sel.'>'.txpspecialchars($alabel).'</option>';
		}

		return n.'<select'.( $select_id ? ' id="'.$select_id.'"' : '' ).' name="'.$name.'"'.
			($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange).
			'>'.
			($blank_first ? n.'<option value=""'.($selected == false ? ' selected="selected"' : '').'></option>' : '').
			( $out ? join('', $out) : '').
			n.'</select>';
	}

/**
 * Generates a tree structured select field.
 *
 * This field takes a NSTREE structure as an associative
 * array. This is mainly used for categories.
 *
 * @param  string $select_name The field
 * @param  array  $array       The values as an array
 * @param  string $value       The selected option. Takes a value from $value
 * @param  string $select_id   The HTML id
 * @param  int    $truncate    Truncate labels to certain length. Disabled if set <4.
 * @return string HTML
 * @see    getTree()
 */

	function treeSelectInput($select_name = '', $array = array(), $value = '', $select_id = '', $truncate = 0)
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

			if (($truncate > 3) && (strlen(utf8_decode($title)) > $truncate))
			{
				$htmltitle = ' title="'.txpspecialchars($title).'"';
				$title = preg_replace('/^(.{0,'.($truncate - 3).'}).*$/su','$1',$title);
				$hellip = '&#8230;';
			}
			else
			{
				$htmltitle = $hellip = '';
			}

			$out[] = n.'<option value="'.txpspecialchars($name).'"'.$htmltitle.$sel.'>'.$sp.txpspecialchars($title).$hellip.'</option>';
		}

		return n.'<select'.( $select_id ? ' id="'.$select_id.'" ' : '' ).' name="'.$select_name.'">'.
			n.'<option value=""'.($selected == false ? ' selected="selected"' : '').'>&#160;</option>'.
			( $out ? join('', $out) : '').
			n.'</select>';
	}

/**
 * Generic form input.
 *
 * @param  string $type        The input type
 * @param  string $name        The input name
 * @param  string $value       The value
 * @param  string $class       The HTML class
 * @param  string $title       The tooltip
 * @param  string $onClick     Inline JavaScript attached to the click event
 * @param  int    $size        The input size
 * @param  int    $tab         The HTML tabindex
 * @param  string $id          The HTML id
 * @param  bool   $disabled    If TRUE renders the input disabled
 * @param  bool   $required    If TRUE the field is marked as required
 * @param  string $placeholder The placeholder value displayed when the field is empty
 * @return string HTML input
 * @example
 * echo fInput('text', 'myInput', 'My example value');
 */

	function fInput($type, $name, $value, $class = '', $title = '', $onClick = '', $size = 0, $tab = 0, $id = '', $disabled = false, $required = false, $placeholder = '')
	{
		$o  = n.'<input type="'.$type.'"';
		$o .= ($type == 'file' || $type == 'image') ? '' : ' value="'.txpspecialchars($value).'"';
		$o .= strlen($name) ? ' name="'.$name.'"' : '';
		$o .= ($size)       ? ' size="'.$size.'"' : '';
		$o .= ($class)      ? ' class="'.$class.'"' : '';
		$o .= ($title)      ? ' title="'.$title.'"' : '';
		$o .= ($onClick)    ? ' onclick="'.$onClick.'"' : '';
		$o .= ($tab)        ? ' tabindex="'.$tab.'"' : '';
		$o .= ($id)         ? ' id="'.$id.'"' : '';
		$o .= ($disabled)   ? ' disabled="disabled"' : '';
		$o .= ($required)   ? ' required="required"' : '';
		$o .= ($placeholder)? ' placeholder="'.txpspecialchars($placeholder).'"' : '';
		$o .= " />";
		return $o;
	}

/**
 * Sanitises a page title.
 *
 * @param      string $text The input string
 * @return     string
 * @deprecated in 4.2.0
 * @see        escape_title()
 */

	function cleanfInput($text)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'escape_title')), E_USER_NOTICE);
		return escape_title($text);
	}

/**
 * Hidden form input.
 *
 * @param  string $name  The name
 * @param  string $value The value
 * @return string HTML input
 * @example
 * echo hInput('myInput', 'hidden value');
 */

	function hInput($name,$value)
	{
		return fInput('hidden', $name, $value);
	}

/**
 * Hidden step input.
 *
 * @param  string $step The step
 * @return string HTML input
 * @see    form()
 * @see    eInput()
 * @example
 * echo form(
 * 	eInput('event').
 * 	sInput('step')
 * );
 */

	function sInput($step)
	{
		return hInput('step', $step);
	}

/**
 * Hidden event input.
 *
 * @param  string $event The event
 * @return string HTML input
 * @see    form()
 * @see    sInput()
 * @example
 * echo form(
 * 	eInput('event').
 * 	sInput('step')
 * );
 */

	function eInput($event)
	{
		return hInput('event', $event);
	}

/**
 * Hidden form token input.
 *
 * @return string A hidden HTML input containing a CSRF token
 * @see    bouncer()
 * @see    form_token()
 */

	function tInput()
	{
		return hInput('_txp_token', form_token());
	}

/**
 * A checkbox.
 *
 * @param  string $name     The field
 * @param  string $value    The value
 * @param  bool   $checked  If TRUE the box is checked
 * @param  int    $tabindex 
 * @param  string $id
 * @return string HTML input
 */

	function checkbox($name, $value, $checked = true, $tabindex = 0, $id = '')
	{
		$o[] = n.'<input type="checkbox" name="'.$name.'" value="'.$value.'"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($checked == 1) ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="checkbox'.($checked == 1 ? ' active' : '').'" />';

		return join('', $o);
	}

/**
 * A checkbox without an option to set the value.
 *
 * @param  string $name     The field
 * @param  bool   $value    If TRUE the box is checked
 * @param  int    $tabindex The HTML tabindex
 * @param  string $id       The HTML id
 * @return string HTML input
 * @access private
 * @see    checkbox()
 */

	function checkbox2($name, $value, $tabindex = 0, $id = '')
	{
		return checkbox($name, 1, $value, $tabindex, $id);
	}

/**
 * A single radio button.
 *
 * @param  string $name     The field
 * @param  string $value    The value
 * @param  bool   $checked  If TRUE, the button is selected
 * @param  string $id       The HTML id
 * @param  int    $tabindex The HTML tabindex
 * @return string HTML input
 */

	function radio($name, $value, $checked = true, $id = '', $tabindex = 0)
	{
		$o[] = n.'<input type="radio" name="'.$name.'" value="'.$value.'"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($checked == 1) ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="radio'.($checked == 1 ? ' active' : '').'" />';

		return join('', $o);
	}

/**
 * Generates a form element.
 *
 * This form will contain a CSRF token if called on an authenticated
 * page.
 *
 * @param  string $contents The form contents
 * @param  string $style    Inline styles added to the form
 * @param  string $onsubmit JavaScript run when the form is sent
 * @param  string $method   The form method, e.g. "post", "get"
 * @param  string $class    The HTML class
 * @param  string $fragment A URL fragment added to the form target
 * @param  string $id       The HTML id
 * @return string HTML form element
 */

	function form($contents, $style = '', $onsubmit = '', $method = 'post', $class = '', $fragment = '', $id = '')
	{
		return n.'<form method="'.$method.'" action="index.php'.($fragment ? '#'.$fragment.'"' : '"').
			($id ? ' id="'.$id.'"' : '').
			($class ? ' class="'.$class.'"' : '').
			($style ? ' style="'.$style.'"' : '').
			($onsubmit ? ' onsubmit="return '.$onsubmit.'"' : '').
			'>'.$contents.
			tInput().
			n.'</form>'.n;
	}

/**
 * Gets and sanitises a field from a prefixed core database table.
 *
 * @param  string $name       The field
 * @param  string $event      The table
 * @param  string $identifier The field used for selecting
 * @param  string $id         The value used for selecting
 * @return string HTML
 * @access private
 * @see    fetch()
 * @see    txpspecialchars()
 */

	function fetch_editable($name, $event, $identifier, $id)
	{
		$q = fetch($name, 'txp_'.$event, $identifier, $id);
		return txpspecialchars($q);
	}

/**
 * A textarea.
 *
 * @param  string $name        The field
 * @param  int    $h           The field height in pixels
 * @param  int    $w           The field width in pixels
 * @param  string $thing       The value
 * @param  string $id          The HTML id
 * @param  int    $rows        Rows
 * @param  int    $cols        Columns
 * @param  string $placeholder The placeholder value displayed when the field is empty
 * @return string HTML
 */

	function text_area($name, $h = 0, $w = 0, $thing = '', $id = '', $rows = 5, $cols = 40, $placeholder='')
	{
		$id = ($id) ? ' id="'.$id.'"' : '';
		$rows = ' rows="' . ( ($rows && is_numeric($rows)) ? $rows : '5') . '"';
		$cols = ' cols="' . ( ($cols && is_numeric($cols)) ? $cols : '40') . '"';
		$width = ($w) ? 'width:'.$w.'px;' : '';
		$height = ($h) ? 'height:'.$h.'px;' : '';
		$style = ($width || $height) ? ' style="'.$width.$height.'"' : '';
		return '<textarea'.$id.' name="'.$name.'"'.$rows.$cols.$style.($placeholder == '' ? '' : ' placeholder="'.txpspecialchars($placeholder).'"').'>'.txpspecialchars($thing).'</textarea>';
	}

/**
 * Generates a select field with a name "type".
 *
 * @param  array $options 
 * @return string
 * @access private
 * @see    selectInput()
 */

	function type_select($options)
	{
		return n.'<select name="type">'.type_options($options).'</select>';
	}

/**
 * Generates a list of options for use in a select field.
 *
 * @param  array $array
 * @return string
 * @access private
 * @see    selectInput()
 */

	function type_options($array)
	{
		foreach ($array as $a => $b)
		{
			$out[] = n.'<option value="'.$a.'">'.gTxt($b).'</option>';
		}
		return join('', $out);
	}

/**
 * Generates a list of radio buttons wrapped in a unordered list.
 *
 * @param  string       $name        The field
 * @param  array        $values      The values as an array array( $value => $label )
 * @param  string       $current_val The selected option. Takes a value from $value
 * @param  string       $hilight_val The highlighted list item
 * @param  string|array $atts        HTML attributes
 * @return string       HTML
 */

	function radio_list($name, $values, $current_val = '', $hilight_val = '', $atts = array('class' => 'status plain-list'))
	{
		foreach ($values as $value => $label)
		{
			$id = $name.'-'.$value;
			$class = 'status-'.$value;

			if ((string) $value === (string) $hilight_val)
			{
				$label = strong($label);
				$class .= ' active';
			}

			$out[] = tag(
				radio($name, $value, ((string) $current_val === (string) $value), $id).
				n.tag($label, 'label', array('for' => $id)),
				'li', array('class' => $class)
			);
		}

		return tag(n.join(n, $out).n, 'ul', $atts);
	}

/**
 * Generates a field used to store and set a date.
 *
 * @param  string $name        The field
 * @param  string $datevar     The strftime format the date is displayed 
 * @param  int    $time        The displayed date as a UNIX timestamp
 * @param  int    $tab         The HTML tabindex
 * @return string HTML
 * @access private
 */

	function tsi($name, $datevar, $time, $tab = 0)
	{
		static $placeholders = array(
			'%Y' => 'yyyy',
			'%m' => 'mm',
			'%d' => 'dd',
			'%H' => 'hh',
			'%M' => 'mn',
			'%S' => 'ss',
		);

		$size = ($name=='year' or $name=='exp_year') ? INPUT_XSMALL : INPUT_TINY;
		$s = ($time == 0) ? '' : safe_strftime($datevar, $time);
		return n.'<input type="text" name="'.$name.'" value="'.
			$s
			.'" size="'.$size.'" maxlength="'.$size.'" class="'.$name.'"'.(empty($tab) ? '' : ' tabindex="'.$tab.'"').' title="'.gTxt('article_'.$name)
			.'"'.(isset($placeholders[$datevar]) ? ' placeholder="'.txpspecialchars(gTxt($placeholders[$datevar])).'"' : '').' />';
	}

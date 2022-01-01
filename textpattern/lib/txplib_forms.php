<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Collection of HTML form widgets.
 *
 * @package Form
 */

/**
 * Generates a radio button toggle.
 *
 * @param  array  $values   The values as an array
 * @param  string $field    The field name
 * @param  string $checked  The checked button, takes a value from $vals
 * @param  int    $tabindex The HTML tabindex
 * @param  string $id       The HTML id
 * @return string A HTML radio button set
 * @example
 * echo radioSet(array(
 *     'value1' => 'Label1',
 *     'value2' => 'Label2',
 * ), 'myInput', 'value1');
 */

function radioSet($values, $field, $checked = '', $tabindex = 0, $id = '')
{
    if ($id) {
        $id = $id.'-'.$field;
    } else {
        $id = $field;
    }

    $out = array();

    foreach ((array) $values as $value => $label) {
        $out[] = radio($field, $value, (string) $value === (string) $checked, $id.'-'.$value, $tabindex);
        $out[] = n.tag($label, 'label', array('for' => $id.'-'.$value));
    }

    return join('', $out);
}

/**
 * Generates a Yes/No radio button toggle.
 *
 * These buttons are booleans. 'Yes' will have a value of 1 and 'No' is 0.
 *
 * @param  string $field    The field name
 * @param  string $checked  The checked button, either '1', '0'
 * @param  int    $tabindex The HTML tabindex
 * @param  string $id       The HTML id
 * @return string HTML
 * @see    radioSet()
 * @example
 * echo form(
 *     'Is this an example?'.
 *     yesnoRadio('is_example', 1)
 * );
 */

function yesnoRadio($field, $checked = '', $tabindex = 0, $id = '')
{
    $vals = array(
        '0' => gTxt('no'),
        '1' => gTxt('yes'),
    );

    return radioSet($vals, $field, $checked, $tabindex, $id);
}

/**
 * Generates an On/Off radio button toggle.
 *
 * @param  string $field    The field name
 * @param  string $checked  The checked button, either '1', '0'
 * @param  int    $tabindex The HTML tabindex
 * @param  string $id       The HTML id
 * @return string HTML
 * @see    radioSet()
 */

function onoffRadio($field, $checked = '', $tabindex = 0, $id = '')
{
    $vals = array(
        '0' => gTxt('off'),
        '1' => gTxt('on'),
    );

    return radioSet($vals, $field, $checked, $tabindex, $id);
}

/**
 * Generates a select field.
 *
 * @param  string $name        The field
 * @param  array  $array       The values as an array array( 'value' => 'label' )
 * @param  mixed  $value       The selected option(s). If an array, renders the select multiple
 * @param  bool   $blank_first If TRUE, prepends an empty option to the list
 * @param  mixed  $onchange    If TRUE submits the form when an option is changed. If a string, inserts it to the select tag
 * @param  string $select_id   The HTML id
 * @param  bool   $check_type  Type-agnostic comparison
 * @param  bool   $disabled    If TRUE renders the select disabled
 * @return string HTML
 * @example
 * echo selectInput('myInput', array(
 *     'value1' => 'Label1',
 *     'value2' => 'Label2',
 * ));
 */

function selectInput($name = '', $array = array(), $value = '', $blank_first = false, $onchange = '', $select_id = '', $check_type = false, $disabled = false)
{
    $out = array();

    $selected = false;
    $multiple = is_array($value);
    $multiple or $value = (string) $value;

    if (is_array($disabled)) {
        $disable = $disabled;
        $disabled = false;
    } else {
        $disable = array();
    }

    if (is_array($blank_first)) {
        $array = $blank_first + $array;
        $blank_first = false;
    }

    foreach ($array as $avalue => $alabel) {
        $atts = array('value' => $avalue, 'dir' => 'auto', 'disabled' => in_array($avalue, $disable));

        if (!$multiple && $value === (string) $avalue || $multiple && in_array($avalue, $value)) {
            $atts['selected'] = $selected = true;
        }

        if (is_array($alabel)) {
            $atts = $alabel + $atts;
            $alabel = isset($atts['title']) ? $atts['title'] : $avalue;
            unset($atts['title']);
        }

        $atts = join_atts($atts, TEXTPATTERN_STRIP_NONE);
        $out[] = '<option'.$atts.'>'.txpspecialchars($alabel).'</option>';
    }

    if ($blank_first) {
        array_unshift($out, '<option value=""'.($selected === false ? ' selected="selected"' : '').'>&#160;</option>');
    }

    $name_m = (is_array($name) ? $name['name'] : $name).($multiple ? '[]' : '');

    $atts = join_atts((is_array($name) ? $name : array(
        'name' => $name.($multiple ? '[]' : '')
    )) + array(
        'id'       => $select_id,
        'disabled' => (bool) $disabled,
        'multiple' => $multiple
    ), TEXTPATTERN_STRIP_EMPTY);

    if ((string) $onchange === '1') {
        $atts .= ' data-submit-on="change"';
    } elseif ($onchange) {
        $atts .= ' '.trim($onchange);
    }

    return n.'<select'.$atts.'>'.n.join(n, $out).n.'</select>'.n
        .($multiple ? hInput($name_m, '').n : ''); // TODO: use jQuery UI selectmenu?
}

/**
 * Generates a tree structured select field.
 *
 * This field takes a NSTREE structure as an associative array. This is mainly
 * used for categories.
 *
 * @param  string $select_name The field
 * @param  array  $array       The values as an array
 * @param  string $value       The selected option. Takes a value from $value
 * @param  string $select_id   The HTML id
 * @param  int    $truncate    Truncate labels to certain length. Disabled if set <4.
 * @return string HTML
 * @see    getTree()
 */

function treeSelectInput($select_name = '', $array = array(), $value = '', $select_id = '', $truncate = 0, $atts = array())
{
    $out = array();

    $doctype = get_pref('doctype');
    $selected = false;

    foreach ($array as $a) {
        if ($a['name'] == 'root') {
            continue;
        }

        if ((string) $a['name'] === (string) $value) {
            $sel = ' selected="selected"';
            $selected = true;
        } else {
            $sel = '';
        }

        $sp = str_repeat(sp.sp, $a['level']);

        if (($truncate > 3) && (strlen(utf8_decode($a['title'])) > $truncate)) {
            $htmltitle = ' title="'.txpspecialchars($a['title']).'"';
            $a['title'] = preg_replace('/^(.{0,'.($truncate - 3).'}).*$/su', '$1', $a['title']);
            $hellip = '&#8230;';
        } else {
            $htmltitle = $hellip = '';
        }

        $data_level = '';
        if ($doctype !== 'xhtml') {
            $data_level = ' data-level="'.$a['level'].'"';
        }

        $out[] = '<option value="'.txpspecialchars($a['name']).'"'.$htmltitle.$sel.$data_level.' dir="auto">'.$sp.txpspecialchars($a['title']).$hellip.'</option>';
    }

    array_unshift($out, '<option value=""'.($selected === false ? ' selected="selected"' : '').'>&#160;</option>');

    return n.tag(n.join(n, $out).n, 'select', array(
        'id'   => $select_id,
        'name' => $select_name,
    ) + $atts);
}

/**
 * Render HTML &lt;select&gt; element for choosing a timezone.
 *
 * @param  string      $name        Element name
 * @param  string      $value       Selected timezone
 * @param  bool        $blank_first Add empty first option
 * @param  bool|string $onchange
 * @param  string      $select_id   HTML id attribute
 * @return string HTML markup
 * @since  4.7.0
 * @todo   Might be a better way of doing this, perhaps introducing optgroup to selectInput()
 */

function timezoneSelectInput($name = '', $value = '', $blank_first = '', $onchange = '', $select_id = '')
{
    if ($details = Txp::get('\Textpattern\Date\Timezone')->getTimeZones()) {
        $thiscontinent = false;
        $selected = false;

        foreach ($details as $timezone_id => $tz) {
            extract($tz);

            if ($value == $timezone_id) {
                $selected = true;
            }

            if ($continent !== $thiscontinent) {
                if ($thiscontinent !== false) {
                    $out[] = n.'</optgroup>';
                }

                $out[] = n.'<optgroup label="'.gTxt($continent ? $continent : 'Universal').'">';
                $thiscontinent = $continent;
            }

            $where = gTxt(str_replace('_', ' ', $city))
                .(!empty($subcity) ? '/'.gTxt(str_replace('_', ' ', $subcity)) : '')
                /*."($abbr)"*/
                or $where = $timezone_id;

            $out[] = n.'<option value="'.txpspecialchars($timezone_id).'"'.($value == $timezone_id ? ' selected="selected"' : '').' dir="auto">'.$where.t.'</option>';
        }

        $out[] = n.'</optgroup>';

        return n.'<select'.($select_id ? ' id="'.$select_id.'"' : '').' name="'.$name.'"'.
            ($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange).
            '>'.
            ($blank_first ? n.'<option value=""'.($selected == false ? ' selected="selected"' : '').'>&#160;</option>' : '').
            join('', $out).
            n.'</select>';
    }

    return '';
}

/**
 * Generic form input.
 *
 * @param  string       $type         The input type
 * @param  string       $name         The input name
 * @param  string       $value        The value
 * @param  string       $class        The HTML class
 * @param  string       $title        The tooltip
 * @param  string       $onClick      Inline JavaScript attached to the click event
 * @param  int          $size         The input size
 * @param  int          $tab          The HTML tabindex
 * @param  string       $id           The HTML id
 * @param  bool         $disabled     If TRUE renders the input disabled
 * @param  bool         $required     If TRUE the field is marked as required
 * @param  string       $placeholder  The placeholder value displayed when the field is empty
 * @return string HTML input
 * @example
 * echo fInput('text', 'myInput', 'My example value');
 */

function fInput($type, $name, $value, $class = '', $title = '', $onClick = '', $size = 0, $tab = 0, $id = '', $disabled = false, $required = false, $placeholder = null)
{
    $atts = (is_array($name) ? $name : array('name' => $name)) + array(
        'class'        => $class,
        'id'           => $id,
        'type'         => $type,
        'size'         => (int) $size,
        'title'        => $title,
        'aria-label'   => $title,
        'onclick'      => $onClick,
        'tabindex'     => (int) $tab,
        'disabled'     => (bool) $disabled,
        'required'     => (bool) $required,
        'placeholder'  => $placeholder,
    );

    if ($atts['required'] && !isset($atts['placeholder'])
        && in_array($atts['type'], array('email', 'password', 'search', 'tel', 'text', 'url'))
    ) {
        $atts['placeholder'] = gTxt('required');
    }

    $atts = join_atts($atts, TEXTPATTERN_STRIP_EMPTY);

    if ($type != 'file' && $type != 'image') {
        $atts .= join_atts(array('value' => (string) $value), TEXTPATTERN_STRIP_NONE);
    }

    return n.tag_void('input', $atts);
}

/**
 * Hidden form input.
 *
 * @param  string/array $name  The name
 * @param  string       $value The value
 * @return string       HTML input
 * @example
 * echo hInput('myInput', 'hidden value');
 */

function hInput($name, $value = null, $glue = ',')
{
    if (!is_array($name)) {
        return fInput('hidden', $name, $value);
    }

    return array_walk($name, function (&$v, $n, $glue) {
        $v = fInput('hidden', $n, is_array($v) ? implode($glue, $v) : $v);
    }, $glue) ? implode($name) : false;
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
 *     eInput('event').
 *     sInput('step')
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
 *     eInput('event').
 *     sInput('step')
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
 * @param  int    $tabindex The HTML tabindex
 * @param  string $id       The HTML id
 * @return string HTML input
 * @example
 * echo checkbox('name', 'value', true);
 */

function checkbox($name, $value, $checked = true, $tabindex = 0, $id = '')
{
    $class = 'checkbox';

    if ($checked) {
        $class .= ' active';
    }

    $atts = join_atts(array(
        'class'    => $class,
        'id'       => $id,
        'name'     => $name,
        'type'     => 'checkbox',
        'checked'  => (bool) $checked,
        'tabindex' => (int) $tabindex,
    ), TEXTPATTERN_STRIP_EMPTY);

    $atts .= join_atts(array('value' => (string) $value), TEXTPATTERN_STRIP_NONE);

    return n.tag_void('input', $atts);
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
    $class = 'radio';

    if ($checked) {
        $class .= ' active';
    }

    $atts = join_atts(array(
        'class'    => $class,
        'id'       => $id,
        'name'     => $name,
        'type'     => 'radio',
        'checked'  => (bool) $checked,
        'tabindex' => (int) $tabindex,
    ), TEXTPATTERN_STRIP_EMPTY);

    $atts .= join_atts(array('value' => (string) $value), TEXTPATTERN_STRIP_NONE);

    return n.tag_void('input', $atts);
}

/**
 * Generates a form element.
 *
 * This form will contain a CSRF token if called on an authenticated page.
 *
 * @param  string $contents           The form contents
 * @param  string $style              Inline styles added to the form
 * @param  string $onsubmit           JavaScript run when the form is sent
 * @param  string $method             The form method, e.g. "post", "get"
 * @param  string $class              The HTML class
 * @param  string $fragment           A URL fragment added to the form target
 * @param  string $id                 The HTML id
 * @param  string $role               ARIA role name
 * @param  bool   $allow_autocomplete If FALSE, the form is set to autocomplete="off"
 * @return string HTML form element
 */

function form($contents, $style = '', $onsubmit = '', $method = 'post', $class = '', $fragment = '', $id = '', $role = '', $allow_autocomplete = true)
{
    $action = 'index.php';
    $autocomplete = '';

    if ($onsubmit) {
        $onsubmit = 'return '.$onsubmit;
    }

    if ($fragment) {
        $action .= '#'.$fragment;
    }

    if ($allow_autocomplete === false) {
        $autocomplete = 'off';
    }

    return n.tag($contents.tInput().n, 'form', array(
        'class'        => $class,
        'id'           => $id,
        'method'       => $method,
        'action'       => $action,
        'onsubmit'     => $onsubmit,
        'role'         => $role,
        'autocomplete' => $autocomplete,
        'style'        => $style,
    ));
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
 * @param  bool   $required    If TRUE the field is marked as required
 * @return string HTML
 */

function text_area($name, $h = 0, $w = 0, $thing = '', $id = '', $rows = 5, $cols = 40, $placeholder = null, $required = false)
{
    $style = '';

    if ($w) {
        $style .= 'width:'.intval($w).'px;';
    }

    if ($h) {
        $style .= 'height:'.intval($h).'px;';
    }

    if ((string) $thing === '') {
        $thing = null;
    } else {
        $thing = txpspecialchars($thing);
    }

    if (!intval($rows)) {
        $rows = 5;
    }

    if (!intval($cols)) {
        $cols = 40;
    }

    $atts = (is_array($name) ? $name : array('name' => $name)) + array(
        'id'          => $id,
        'rows'        => (int) $rows,
        'cols'        => (int) $cols,
        'style'       => $style,
        'required'    => (bool) $required,
        'placeholder' => $placeholder,
    );

    if (!isset($atts['placeholder'])) {
        $atts['placeholder'] = $atts['required'] ? gTxt('required') : false;
    }

    return n.tag($thing, 'textarea', $atts);
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
    foreach ($array as $a => $b) {
        $out[] = n.'<option value="'.$a.'">'.gTxt($b).'</option>';
    }

    return join('', $out);
}

/**
 * Generates a list of radio buttons wrapped in an unordered list.
 *
 * @param  string       $name        The field
 * @param  array        $values      The values as an array array( $value => $label )
 * @param  string       $current_val The selected option. Takes a value from $value
 * @param  string       $hilight_val The highlighted list item
 * @param  string|array $atts        HTML attributes
 * @return string       HTML
 */

function radio_list($name, $values, $current_val = '', $hilight_val = '', $atts = array('class' => 'plain-list'))
{
    foreach ($values as $value => $label) {
        $id = $name.'-'.$value;
        $class = 'status-'.$value;

        if ((string) $value === (string) $hilight_val) {
            $label = strong($label);
            $class .= ' active';
        }

        $out[] = tag(
            radio($name, $value, ((string) $current_val === (string) $value), $id).
            n.tag($label, 'label', array('for' => $id)),
            'li',
            array('class' => $class)
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
 * @param  string $id          The HTML id
 * @return string HTML
 * @access private
 * @example
 * echo tsi('year', '%Y', 1200000000);
 */

function tsi($name, $datevar, $time, $tab = 0, $id = '')
{
    static $placeholders = array(
        '%Y' => 'yyyy',
        '%m' => 'mm',
        '%d' => 'dd',
        '%H' => 'hh',
        '%M' => 'mn',
        '%S' => 'ss',
    );

    $value = $placeholder = '';
    $size = INPUT_TINY;
    $pattern = '([0-5][0-9])';

    if ((int) $time) {
        $value = safe_strftime($datevar, (int) $time);
    }

    if (isset($placeholders[$datevar])) {
        $placeholder = gTxt($placeholders[$datevar]);
    }

    if ($datevar == '%Y' || $name == 'year' || $name == 'exp_year') {
        $class = 'input-year';
        $pattern = '[0-9]{4}';
        $size = INPUT_XSMALL;
        $title = 'input_year';
    }

    if ($datevar == '%m' || $name == 'month' || $name == 'exp_month') {
        $class = 'input-month';
        $pattern = '(0[1-9]|1[012])';
        $title = 'input_month';
    }

    if ($datevar == '%d' || $name == 'day' || $name == 'exp_day') {
        $class = 'input-day';
        $pattern = '(0[1-9]|[12][0-9]|3[01])';
        $title = 'input_day';
    }

    if ($datevar == '%H' || $name == 'hour' || $name == 'exp_hour') {
        $class = 'input-hour';
        $pattern = '([0-1][0-9]|2[0-3])';
        $title = 'input_hour';
    }

    if ($datevar == '%M' || $name == 'minute' || $name == 'exp_minute') {
        $class = 'input-minute';
        $pattern = '([0-5][0-9])';
        $title = 'input_minute';
    }

    if ($datevar == '%S' || $name == 'second' || $name == 'exp_second') {
        $class = 'input-second';
        $pattern = '([0-5][0-9])';
        $title = 'input_second';
    }

    return n.tag_void('input', array(
        'class'       => $class,
        'id'          => $id,
        'name'        => $name,
        'type'        => 'text',
        'inputmode'   => 'numeric',
        'pattern'     => $pattern,
        'size'        => (int) $size,
        'maxlength'   => $size,
        'title'       => gTxt($title),
        'aria-label'  => gTxt($title),
        'placeholder' => $placeholder,
        'tabindex'    => (int) $tab,
        'value'       => $value,
    ));
}

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
 * Preferences panel user interface and interaction.
 *
 * @package Admin\Prefs
 */

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	if ($event == 'prefs')
	{
		require_privs('prefs');

		bouncer($step, array(
			'prefs_save' => true,
			'prefs_list' => false,
		));

		switch (strtolower($step))
		{
			case "" :
			case "prefs_list" :
				prefs_list();
				break;
			case "prefs_save" :
				prefs_save();
				break;
		}
	}

/**
 * Commits prefs to the database.
 */

	function prefs_save()
	{
		global $prefs, $gmtoffset, $is_dst, $auto_dst, $timezone_key, $txp_user;

		// Update custom fields count from database schema and cache it as a hidden pref.
		// TODO: move this when custom fields are refactored.
		$max_custom_fields = count(preg_grep('/^custom_\d+/', getThings('describe '.safe_pfx('textpattern'))));
		set_pref('max_custom_fields', $max_custom_fields, 'publish', 2);

		$sql = array();
		$sql[] = 'prefs_id = 1 and event != "" and type in('.PREF_CORE.', '.PREF_PLUGIN.', '.PREF_HIDDEN.')';
		$sql[] = "(user_name = '' or (user_name='".doSlash($txp_user)."' and name not in(
				select name from ".safe_pfx('txp_prefs')." where user_name = ''
			)))";

		if (!get_pref('use_comments', 1, 1))
		{
			$sql[] = "event != 'comments'";
		}

		$prefnames = safe_rows_start(
			"name, event, user_name, val",
			'txp_prefs',
			join(' and ', $sql)
		);

		$post = stripPost();

		if (isset($post['tempdir']) && empty($post['tempdir']))
		{
			$post['tempdir'] = find_temp_dir();
		}

		if (!empty($post['file_max_upload_size']))
		{
			$post['file_max_upload_size'] = real_max_upload_size($post['file_max_upload_size']);
		}

		if (isset($post['auto_dst']))
		{
			$prefs['auto_dst'] = $auto_dst = $post['auto_dst'];

			if (isset($post['is_dst']) && !$post['auto_dst'])
			{
				$is_dst = $post['is_dst'];
			}
		}

		// Forge $gmtoffset and $is_dst from $timezone_key if present.
		if (isset($post['timezone_key']))
		{
			$key = $post['timezone_key'];
			$tzd = Txp::get('Textpattern_Date_Timezone')->getTimeZones();

			if (isset($tzd[$key]))
			{
				$prefs['timezone_key'] = $timezone_key = $key;
				$post['gmtoffset'] = $prefs['gmtoffset'] = $gmtoffset = $tzd[$key]['offset'];
				$post['is_dst'] = $prefs['is_dst'] = $is_dst = Txp::get('Textpattern_Date_Timezone')->isDst(null, $key);
			}
		}

		if (isset($post['siteurl']))
		{
			$post['siteurl'] = preg_replace('#^https?://#', '', rtrim($post['siteurl'], '/ '));
		}

		while ($a = nextRow($prefnames))
		{
			extract($a);

			if (!isset($post[$name]) || !has_privs('prefs.'.$event))
			{
				continue;
			}

			if ($name === 'logging' && $post[$name] === 'none' && $post[$name] !== $val)
			{
				safe_truncate('txp_log');
			}

			if ($name === 'expire_logs_after' && (int) $post[$name] !== (int) $val)
			{
				safe_delete('txp_log', 'time < date_sub(now(), interval '.intval($post[$name]).' day)');
			}

			update_pref($name, (string) $post[$name], null, null, null, null, (string) $user_name);
		}

		update_lastmod();

		prefs_list(gTxt('preferences_saved'));
	}

/**
 * Renders the list of preferences.
 *
 * Plugins may add their own prefs, for example by using plugin lifecycle events or
 * raising a (pre) callback on event=admin / step=prefs_list so they are installed
 * or updated when accessing the Preferences panel. Access to the prefs can be
 * controlled by using add_privs() on 'prefs.your-prefs-event-name'.
 *
 * @param  string $message The feedback / error string to display
 */

	function prefs_list($message = '')
	{
		global $prefs, $txp_user;

		extract($prefs);

		pagetop(gTxt('tab_preferences'), $message);

		$locale = setlocale(LC_ALL, $locale);

		echo hed(gTxt('tab_preferences'), 1, array('class' => 'txp-heading'));
		echo n.'<div id="prefs_container" class="txp-container">'.
			n.'<form method="post" class="prefs-form" action="index.php">'.
			n.'<div class="txp-layout-textbox">';

		// TODO: remove 'custom' when custom fields are refactored.
		$core_events = array('site', 'admin', 'publish', 'feeds', 'comments', 'custom');
		$joined_core = join(',', quote_list($core_events));

		$sql = array();
		$sql[] = 'prefs_id = 1 and event != "" and type in('.PREF_CORE.', '.PREF_PLUGIN.')';
		$sql[] = "(user_name = '' or (user_name='".doSlash($txp_user)."' and name not in(
				select name from ".safe_pfx('txp_prefs')." where user_name = ''
			)))";

		if (!get_pref('use_comments', 1, 1))
		{
			$sql[] = "event != 'comments'";
		}

		$rs = safe_rows_start(
			"*, FIELD(event,{$joined_core}) as sort_value",
			'txp_prefs',
			join(' and ', $sql)." ORDER BY sort_value = 0, sort_value, event, position"
		);

		$last_event = null;
		$out = array();

		if (numRows($rs))
		{
			while ($a = nextRow($rs))
			{
				if (!has_privs('prefs.'.$a['event']))
				{
					continue;
				}

				if ($a['event'] !== $last_event)
				{
					if ($last_event !== null)
					{
						echo wrapRegion('prefs_group_'.$last_event, join(n, $out), 'prefs_'.$last_event, $last_event, 'prefs_'.$last_event);
					}

					$last_event = $a['event'];
					$out = array();
				}

				$label = '';

				if (!in_array($a['html'], array('yesnoradio', 'is_dst')))
				{
					$label = $a['name'];
				}

				// TODO: remove exception when custom fields move to meta store.
				$help = '';
				if (strpos($a['name'], 'custom_') === false)
				{
					$help = $a['name'];
				}

				if ($a['html'] == 'text_input')
				{
					$size = INPUT_REGULAR;
				}
				else
				{
					$size = '';
				}

				$out[] = inputLabel(
					$a['name'],
					pref_func($a['html'], $a['name'], $a['val'], $size),
					$label,
					$help,
					array('id' => 'prefs-'.$a['name'])
				);
			}
		}

		if ($last_event === null)
		{
			echo graf(gTxt('no_preferences'));
		}
		else
		{
			echo wrapRegion('prefs_group_'.$last_event, join(n, $out), 'prefs_'.$last_event, $last_event, 'prefs_'.$last_event);
		}

		echo n.'</div>'.
			sInput('prefs_save').
			eInput('prefs').
			hInput('prefs_id', '1').
			tInput();

		if ($last_event !== null)
		{
			echo graf(fInput('submit', 'Submit', gTxt('save'), 'publish'));
		}

		echo n.'</form>'.n.'</div>';
	}

/**
 * Calls a core or custom function to render a preference input widget.
 *
 * @param  string $func Callable in a string presentation
 * @param  string $name HTML name/id of the input control
 * @param  string $val  Initial (or current) value of the input control
 * @param  int    $size Size of the input control (width or depth, dependent on control)
 * @return string HTML
 */

	function pref_func($func, $name, $val, $size = '')
	{
		if ($func != 'func' && is_callable('pref_'.$func))
		{
			$func = 'pref_'.$func;
		}
		else
		{
			$string = new Textpattern_Type_String($func);
			$func = $string->toCallback();

			if (!is_callable($func))
			{
				$func = 'text_input';
			}
		}

		return call_user_func($func, $name, $val, $size);
	}

/**
 * Renders a HTML &lt;input&gt; element.
 *
 * @param  string $name HTML name and id of the text box
 * @param  string $val  Initial (or current) content of the text box
 * @param  int    $size Width of the textbox. Options are INPUT_MEDIUM | INPUT_SMALL | INPUT_XSMALL
 * @return string HTML
 */

	function text_input($name, $val, $size = 0)
	{
		$class = '';
		switch ($size)
		{
			case INPUT_MEDIUM :
				$class = 'input-medium';
				break;
			case INPUT_SMALL : 
				$class = 'input-small';
				break;
			case INPUT_XSMALL :
				$class = 'input-xsmall';
				break;
		}

		return fInput('text', $name, $val, $class, '', '', $size, '', $name);
	}

/**
 * Renders a HTML &lt;textarea&gt; element.
 *
 * @param  string $name HTML name of the textarea
 * @param  string $val  Initial (or current) content of the textarea
 * @param  int    $size Number of rows the textarea has
 * @return string HTML
 */

	function pref_longtext_input($name, $val, $size = '')
	{
		return text_area($name, '', '', $val, '', $size);
	}

/**
 * Renders a HTML &lt;select&gt; list of cities for timezone selection.
 *
 * Can be altered by plugins via the 'prefs_ui > gmtoffset'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name of the list
 * @param  string $val  Initial (or current) selected option
 * @return string HTML
 */

	function gmtoffset_select($name, $val)
	{
		// Fetch *hidden* pref
		$key = get_pref('timezone_key', '', true);

		if ($key === '')
		{
			$key = (string) Txp::get('Textpattern_Date_Timezone')->getTimezone();
		}

		$tz = new timezone;
		$ui = $tz->selectInput('timezone_key', $key, false, '', 'gmtoffset');

		return pluggable_ui('prefs_ui', 'gmtoffset', $ui, $name, $val);
	}

/**
 * Renders a HTML choice for whether Daylight Savings Time is in effect.
 *
 * Can be altered by plugins via the 'prefs_ui > is_dst'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function is_dst($name, $val)
	{
		$ui = yesnoRadio ($name, $val).
		script_js (<<<EOS
			$(document).ready(function ()
			{
				var radio = $("#prefs-is_dst input");
				if (radio)
				{
					if ($("#auto_dst-1").prop("checked"))
					{
						radio.prop("disabled", "disabled");
					}

					$("#auto_dst-0").click(function ()
					{
						radio.removeProp("disabled");
					});

					$("#auto_dst-1").click(function ()
					{
						radio.prop("disabled", "disabled");
					});
				}
			});
EOS
		);

		return pluggable_ui('prefs_ui', 'is_dst', $ui, $name, $val);
	}

/**
 * Renders a HTML &lt;select&gt; list of hit logging options.
 *
 * @param  string $name HTML name and id of the list
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function logging($name, $val)
	{
		$vals = array(
			'all'   => gTxt('all_hits'),
			'refer' => gTxt('referrers_only'),
			'none'  => gTxt('none')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

/**
 * Renders a HTML &lt;select&gt; list of supported permanent link URL formats.
 *
 * @param  string $name HTML name and id of the list
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function permlinkmodes($name, $val)
	{
		$vals = array(
			'messy'                => gTxt('messy'),
			'id_title'             => gTxt('id_title'),
			'section_id_title'     => gTxt('section_id_title'),
			'year_month_day_title' => gTxt('year_month_day_title'),
			'section_title'        => gTxt('section_title'),
			'title_only'           => gTxt('title_only'),
			// 'category_subcategory' => gTxt('category_subcategory')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

/**
 * Renders a HTML choice of comment popup modes.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function commentmode($name, $val)
	{
		$vals = array(
			'0' => gTxt('nopopup'),
			'1' => gTxt('popup')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

/**
 * Renders a HTML &lt;select&gt; list of comment popup modes.
 *
 * Can be altered by plugins via the 'prefs_ui > weeks'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function weeks($name, $val)
	{
		$weeks = gTxt('weeks');

		$vals = array(
			'0' => gTxt('never'),
			7   => '1 '.gTxt('week'),
			14  => '2 '.$weeks,
			21  => '3 '.$weeks,
			28  => '4 '.$weeks,
			35  => '5 '.$weeks,
			42  => '6 '.$weeks
		);

		return pluggable_ui('prefs_ui', 'weeks', selectInput($name, $vals, $val, '', '', $name), $name, $val);
	}

/**
 * Renders a HTML &lt;select&gt; list of available ways to display the date.
 *
 * Can be altered by plugins via the 'prefs_ui > dateformats'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function dateformats($name, $val)
	{
		$dayname = '%A';
		$dayshort = '%a';
		$daynum = is_numeric(@strftime('%e')) ? '%e' : '%d';
		$daynumlead = '%d';
		$daynumord = is_numeric(substr(trim(@strftime('%Oe')), 0, 1)) ? '%Oe' : $daynum;
		$monthname = '%B';
		$monthshort = '%b';
		$monthnum = '%m';
		$year = '%Y';
		$yearshort = '%y';
		$time24 = '%H:%M';
		$time12 = @strftime('%p') ? '%I:%M %p' : $time24;
		$date = @strftime('%x') ? '%x' : '%Y-%m-%d';

		$formats = array(
			"$monthshort $daynumord, $time12",
			"$daynum.$monthnum.$yearshort",
			"$daynumord $monthname, $time12",
			"$yearshort.$monthnum.$daynumlead, $time12",
			"$dayshort $monthshort $daynumord, $time12",
			"$dayname $monthname $daynumord, $year",
			"$monthshort $daynumord",
			"$daynumord $monthname $yearshort",
			"$daynumord $monthnum $year - $time24",
			"$daynumord $monthname $year",
			"$daynumord $monthname $year, $time24",
			"$daynumord. $monthname $year",
			"$daynumord. $monthname $year, $time24",
			"$year-$monthnum-$daynumlead",
			"$year-$daynumlead-$monthnum",
			"$date $time12",
			"$date",
			"$time24",
			"$time12",
			"$year-$monthnum-$daynumlead $time24",
		);

		$ts = time();

		$vals = array();

		foreach ($formats as $f)
		{
			if ($d = safe_strftime($f, $ts))
			{
				$vals[$f] = $d;
			}
		}

		$vals['since'] = gTxt('hours_days_ago');

		return pluggable_ui('prefs_ui', 'dateformats', selectInput($name, array_unique($vals), $val, '', '', $name), compact('vals', 'name', 'val', 'ts'));
	}

/**
 * Renders a HTML &lt;select&gt; list of site production status.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function prod_levels($name, $val)
	{
		$vals = array(
			'debug'   => gTxt('production_debug'),
			'testing' => gTxt('production_test'),
			'live'    => gTxt('production_live'),
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

/**
 * Renders a HTML &lt;select&gt; list of available panels to show immediately after login.
 *
 * @param  string $name HTML name of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function default_event($name, $val)
	{
		$vals = areas();

		$out = array();

		foreach ($vals as $a => $b)
		{
			if (count($b) > 0)
			{
				$out[] = n.'<optgroup label="'.gTxt('tab_'.$a).'">';

				foreach ($b as $c => $d)
				{
					$out[] = n.'<option value="'.$d.'"'.( $val == $d ? ' selected="selected"' : '' ).'>'.$c.'</option>';
				}

				$out[] = n.'</optgroup>';
			}
		}

		return n.'<select id="default_event" name="'.$name.'" class="default-events">'.
			join('', $out).
			n.'</select>';
	}

/**
 * Renders a HTML &lt;select&gt; list of sendmail options.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function commentsendmail($name, $val)
	{
		$vals = array(
			'1' => gTxt('all'),
			'0' => gTxt('none'),
			'2' => gTxt('ham')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

/**
 * Renders a HTML custom field.
 *
 * Can be altered by plugins via the 'prefs_ui > custom_set'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name of the widget
 * @param  string $val  Initial (or current) content
 * @return string HTML
 * @todo   deprecate or move this when CFs are migrated to the meta store
 */

	function custom_set($name, $val)
	{
		return pluggable_ui('prefs_ui', 'custom_set', text_input($name, $val, INPUT_REGULAR), $name, $val);
	}

/**
 * Renders a HTML &lt;select&gt; list of installed admin-side themes.
 *
 * Can be altered by plugins via the 'prefs_ui > theme_name'
 * pluggable UI callback event.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function themename($name, $val)
	{
		$themes = theme::names();
		foreach ($themes as $t)
		{
			$theme = theme::factory($t);
			if ($theme)
			{
				$m = $theme->manifest();
				$title = empty($m['title']) ? ucwords($theme->name) : $m['title'];
				$vals[$t] = $title;
				unset($theme);
			}
		}
		asort($vals, SORT_STRING);

		return pluggable_ui('prefs_ui', 'theme_name',
			selectInput($name, $vals, $val, '', '', $name));
	}

/**
 * Renders a HTML &lt;select&gt; list of available public site markup schemes to adhere to.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

	function doctypes($name, $val)
	{
		$vals = array(
			'xhtml' => gTxt('XHTML'),
			'html5' => gTxt('HTML5')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

/**
 * Gets the maximum allowed file upload size.
 *
 * Computes the maximum acceptable file size to the application if the user-selected
 * value is larger than the maximum allowed by the current PHP configuration.
 *
 * @param  int $user_max Desired upload size supplied by the administrator
 * @return int Actual value; the lower of user-supplied value or system-defined value
 */

	function real_max_upload_size($user_max)
	{
		// The minimum of the candidates, is the real max. possible size
		$candidates = array($user_max,
							ini_get('post_max_size'),
							ini_get('upload_max_filesize'));
		$real_max = null;
		foreach ($candidates as $item)
		{
			$val = trim($item);
			$modifier = strtolower( substr($val, -1) );
			switch ($modifier)
			{
				// The 'G' modifier is available since PHP 5.1.0
				case 'g' :
					$val *= 1024;
				case 'm' :
					$val *= 1024;
				case 'k' :
					$val *= 1024;
			}
			if ($val > 1)
			{
				if (is_null($real_max))
				{
					$real_max = $val;
				}
				elseif ($val < $real_max)
				{
					$real_max = $val;
				}
			}
		}

		return $real_max;
	}

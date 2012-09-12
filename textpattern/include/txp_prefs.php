<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');

//-------------------------------------------------------------

	if ($event == 'prefs') {
		require_privs('prefs');

		$available_steps = array(
			'advanced_prefs'      => false,
			'prefs_save'          => true,
			'advanced_prefs_save' => true,
			'prefs_list'          => false
		);

		if ($step && bouncer($step, $available_steps)) {
			$step();
		} else {
			prefs_list();
		}
	}

// -------------------------------------------------------------
	function prefs_save()
	{
		global $prefs, $gmtoffset, $is_dst, $auto_dst, $timezone_key;

		$prefnames = safe_column("name", "txp_prefs", "prefs_id = 1");

		$post = doSlash(stripPost());

		// Forge $auto_dst for (in-)capable servers
		if (!timezone::is_supported())
		{
			$post['auto_dst'] = false;
		}
		$prefs['auto_dst'] = $auto_dst = $post['auto_dst'];

		if (!$post['auto_dst'])
		{
			$is_dst = $post['is_dst'];
		}

		// Forge $gmtoffset and $is_dst from $timezone_key if present
		if (isset($post['timezone_key']))
		{
			$key = $post['timezone_key'];
			$tz = new timezone;
			$tzd = $tz->details();
			if (isset($tzd[$key]))
			{
				$prefs['timezone_key'] = $timezone_key = $key;
				$post['gmtoffset'] = $prefs['gmtoffset'] = $gmtoffset = $tzd[$key]['offset'];
				$post['is_dst'] = $prefs['is_dst'] = $is_dst = timezone::is_dst(time(), $key);
			}

		}

		foreach($prefnames as $prefname) {
			if (isset($post[$prefname])) {
				if ($prefname == 'siteurl')
				{
					$post[$prefname] = str_replace("http://",'',$post[$prefname]);
					$post[$prefname] = rtrim($post[$prefname],"/ ");
				}

				safe_update(
					"txp_prefs",
					"val = '".$post[$prefname]."'",
					"name = '".doSlash($prefname)."' and prefs_id = 1"
				);
			}
		}

		update_lastmod();

		prefs_list(gTxt('preferences_saved'));
	}

// -------------------------------------------------------------

	function prefs_list($message = '')
	{
		global $prefs;
		extract($prefs);

		// Read real DB value instead of potentially 'stale' $prefs array when value has just changed
		$use_comments = safe_field('val', 'txp_prefs', "name='use_comments'");

		echo pagetop(gTxt('tab_preferences'), $message);

		$locale = setlocale(LC_ALL, $locale);

		echo '<h1 class="txp-heading">'.gTxt('tab_preferences').'</h1>';
		echo n.'<div id="prefs_container" class="txp-container">'.
			n.n.'<form method="post" class="prefs-form basic" action="index.php">'.

			n.'<p class="nav-tertiary">'.
				sLink('prefs', 'prefs_list', gTxt('site_prefs'), 'navlink-active').
				sLink('prefs', 'advanced_prefs', gTxt('advanced_preferences'), 'navlink').
			n.'</p>'.

			n.n.startTable('', '', 'txp-list')
			.'<tbody>';

		$evt_list = safe_column('event', 'txp_prefs', "type = 0 and prefs_id = 1 group by event order by event desc");

		foreach ($evt_list as $event)
		{
			$rs = safe_rows_start('*', 'txp_prefs', "type = 0 and prefs_id = 1 and event = '".doSlash($event)."' order by position");

			$cur_evt = '';

			while ($a = nextRow($rs))
			{
				if ($a['event'] != $cur_evt)
				{
					$cur_evt = $a['event'];

					if ($cur_evt == 'comments' && !$use_comments)
					{
						continue;
					}

					echo n.n.tr(
						tdcs(
							hed(gTxt($a['event']), 3, ' class="'.$a['event'].'-prefs"')
						, 2)
					, ' class="pref-heading"');
				}

				if ($cur_evt == 'comments' && !$use_comments)
				{
					continue;
				}

				$label = (!in_array($a['html'], array('yesnoradio', 'is_dst'))) ?
					'<label for="'.$a['name'].'">'.gTxt($a['name']).'</label>' :
					gTxt($a['name']);

				$out = tda($label.n.popHelp($a['name']), ' class="pref-label"');
				$out.= td(pref_func($a['html'], $a['name'], $a['val'], ($a['html'] == 'text_input' ? INPUT_REGULAR : '')), '', 'pref-value');

				echo tr($out, " id='prefs-{$a['name']}' class='{$a['event']}-prefs'");
			}
		}

		echo n.'</tbody>'.n.endTable().
			graf(
				fInput('submit', 'Submit', gTxt('save'), 'publish').
				n.sInput('prefs_save').
				n.eInput('prefs').
				n.hInput('prefs_id', '1').
				n.tInput()
			).
			n.n.'</form>'.
			n.'</div>';
	}

//-------------------------------------------------------------

	function pref_func($func, $name, $val, $size = '')
	{
		$func = (is_callable('pref_'.$func) ? 'pref_'.$func : (is_callable($func) ? $func : 'text_input'));
		return call_user_func($func, $name, $val, $size);
	}

//-------------------------------------------------------------

	function text_input($name, $val, $size = '')
	{
		$class = '';
		switch ($size) {
			case INPUT_MEDIUM: $class = 'input-medium'; break;
			case INPUT_SMALL: $class = 'input-small'; break;
			case INPUT_XSMALL: $class = 'input-xsmall'; break;
		}
		return fInput('text', $name, $val, $class, '', '', $size, '', $name);
	}

//-------------------------------------------------------------

	function pref_longtext_input($name, $val, $size = '')
	{
		return text_area($name, '', '', $val, '', $size);
	}

//-------------------------------------------------------------

	function gmtoffset_select($name, $val)
	{
		// Fetch *hidden* pref
		$key = safe_field('val', 'txp_prefs', "name='timezone_key'");
		$tz = new timezone;
		$ui = $tz->selectInput('timezone_key', $key, true, '', 'gmtoffset');
		return pluggable_ui('prefs_ui', 'gmtoffset', $ui, $name, $val);
	}


//-------------------------------------------------------------

	function is_dst($name, $val)
	{
		$ui = yesnoRadio ($name, $val).n.
		script_js ("textpattern.timezone_is_supported = ".(int)timezone::is_supported().";").
		script_js (<<<EOS
			$(document).ready(function(){
				var radio = $("#prefs-is_dst input");
				if (radio) {
					if ($("#auto_dst-1").prop("checked") && textpattern.timezone_is_supported) {
						radio.prop("disabled","disabled");
					}
					$("#auto_dst-0").click(
						function(){
							radio.removeProp("disabled");
						});
				   	$("#auto_dst-1").click(
						function(){
							radio.prop("disabled","disabled");
					  	});
			   	}
				if (!textpattern.timezone_is_supported) {
					$("#prefs-auto_dst input").prop("disabled","disabled");
				}
	});
EOS
		);
		return pluggable_ui('prefs_ui', 'is_dst', $ui, $name, $val);
	}

//-------------------------------------------------------------

	function logging($name, $val)
	{
		$vals = array(
			'all'   => gTxt('all_hits'),
			'refer' => gTxt('referrers_only'),
			'none'  => gTxt('none')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------

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

//-------------------------------------------------------------
// Deprecated; permlinkmodes is used instead now
	function urlmodes($name, $val)
	{
		$vals = array(
			'0' => gTxt('messy'),
			'1' => gTxt('clean')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------

	function commentmode($name, $val)
	{
		$vals = array(
			'0' => gTxt('nopopup'),
			'1' => gTxt('popup')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------

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

// -------------------------------------------------------------

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

		return selectInput($name, array_unique($vals), $val, '', '', $name);
	}
//-------------------------------------------------------------

	function prod_levels($name, $val)
	{
		$vals = array(
			'debug'   => gTxt('production_debug'),
			'testing' => gTxt('production_test'),
			'live'    => gTxt('production_live'),
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------
	function default_event($name, $val)
	{
		$vals = areas();

		$out = array();

		foreach ($vals as $a => $b)
		{
			if (count($b) > 0)
			{
				$out[] = n.t.'<optgroup label="'.gTxt('tab_'.$a).'">';

				foreach ($b as $c => $d)
				{
					$out[] = n.t.t.'<option value="'.$d.'"'.( $val == $d ? ' selected="selected"' : '' ).'>'.$c.'</option>';
				}

				$out[] = n.t.'</optgroup>';
			}
		}

		return n.'<select id="default_event" name="'.$name.'" class="default-events">'.
			join('', $out).
			n.'</select>';
	}

//-------------------------------------------------------------
	function commentsendmail($name, $val)
	{
		$vals = array(
			'1' => gTxt('all'),
			'0' => gTxt('none'),
			'2' => gTxt('ham')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------
	function custom_set($name, $val)
	{
		return pluggable_ui('prefs_ui', 'custom_set', text_input($name, $val, INPUT_REGULAR), $name, $val);
	}

//-------------------------------------------------------------
	function themename($name, $val)
	{
		$themes = theme::names();
		foreach ($themes as $t)
		{
			$theme = theme::factory($t);
			if ($theme) {
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

//-------------------------------------------------------------
	function doctypes($name, $val)
	{
		$vals = array(
			'xhtml' => gTxt('XHTML'),
			'html5' => gTxt('HTML5')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------
	function advanced_prefs($message = '')
	{
		echo pagetop(gTxt('advanced_preferences'), $message).

			n.'<h1 class="txp-heading">'.gTxt('tab_preferences').'</h1>'.
			n.'<div id="prefs_container" class="txp-container">'.
			n.n.'<form method="post" class="prefs-form advanced" action="index.php">'.

			n.'<p class="nav-tertiary">'.
				sLink('prefs', 'prefs_list', gTxt('site_prefs'), 'navlink').
				sLink('prefs', 'advanced_prefs', gTxt('advanced_preferences'), 'navlink-active').
			n.'</p>'.

			n.n.startTable('', '', 'txp-list')
			.'<tbody>';

		$rs = safe_rows_start('*', 'txp_prefs', "type = 1 and prefs_id = 1 order by event, position");

		$cur_evt = '';

		while ($a = nextRow($rs))
		{
			$headingPopHelp = (strpos($a['name'], 'custom_') !== false);

			if ($a['event']!= $cur_evt)
			{
				$cur_evt = $a['event'];

				echo n.n.tr(
					tdcs(
						hed(gTxt($a['event']) . ($headingPopHelp ? n.popHelp($a['name']) : ''), 3, ' class="'.$a['event'].'-prefs"')
					, 2)
				, ' class="pref-heading"');
			}

			$label = (!in_array($a['html'], array('yesnoradio', 'is_dst')))
				? '<label for="'.$a['name'].'">'.gTxt($a['name']).'</label>'
				: gTxt($a['name']);

			$out = tda($label. (($headingPopHelp) ? '' : n.popHelp($a['name'])), ' class="pref-label"');

			if ($a['html'] == 'text_input')
			{
				$look_for = array('expire_logs_after', 'max_url_len', 'time_offset', 'rss_how_many', 'logs_expire');

				$size = in_array($a['name'], $look_for) ? INPUT_XSMALL : INPUT_REGULAR;

				$out.= td(
					pref_func('text_input', $a['name'], $a['val'], $size)
				, '', 'pref-value');
			}

			else
			{
				$out.= td(
					pref_func($a['html'], $a['name'], $a['val'])
				, '', 'pref-value');
			}

			echo n.n.tr($out, " id='prefs-{$a['name']}' class='{$a['event']}-prefs'");
		}

		echo n.'</tbody>'.n.endTable().
			graf(
				fInput('submit', 'Submit', gTxt('save'), 'publish').
				n.sInput('advanced_prefs_save').
				n.eInput('prefs').
				n.hInput('prefs_id', '1').
				n.tInput()
			).
			n.n.'</form>'.
			n.'</div>';
	}

//-------------------------------------------------------------
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
			switch($modifier) {
				// The 'G' modifier is available since PHP 5.1.0
				case 'g': $val *= 1024;
				case 'm': $val *= 1024;
				case 'k': $val *= 1024;
			}
			if ($val > 1) {
				if (is_null($real_max))
					$real_max = $val;
				elseif ($val < $real_max)
					$real_max = $val;
			}
		}
		return $real_max;
	}

//-------------------------------------------------------------
	function advanced_prefs_save()
	{
		// update custom fields count from database schema and cache it as a hidden pref
		$max_custom_fields = count(preg_grep('/^custom_\d+/', getThings('describe `'.PFX.'textpattern`')));
		set_pref('max_custom_fields', $max_custom_fields, 'publish', 2);

		// safe all regular advanced prefs
		$prefnames = safe_column("name", "txp_prefs", "prefs_id = 1 AND type = 1");

		$post = doSlash(stripPost());

		if (empty($post['tempdir']))
			$post['tempdir'] = doSlash(find_temp_dir());

		if (!empty($post['file_max_upload_size']))
			$post['file_max_upload_size'] = real_max_upload_size($post['file_max_upload_size']);

		foreach($prefnames as $prefname) {
			if (isset($post[$prefname])) {
					safe_update(
						"txp_prefs",
						"val = '".$post[$prefname]."'",
						"name = '".doSlash($prefname)."' and prefs_id = 1"
					);
			}
		}

		update_lastmod();

		advanced_prefs(gTxt('preferences_saved'));
	}
?>

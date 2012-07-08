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

	include_once txpath.'/lib/txplib_update.php';

	if ($event == 'prefs') {
		require_privs('prefs');

		$available_steps = array(
			'advanced_prefs'      => false,
			'prefs_save'          => true,
			'advanced_prefs_save' => true,
			'get_language'        => true,
			'get_textpack'        => true,
			'remove_language'     => true,
			'list_languages'      => false,
			'prefs_list'          => false
		);

		if (!$step or !bouncer($step, $available_steps)){
			$step = 'prefs_list';
		}
		$step();
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
				sLink('prefs', 'list_languages', gTxt('manage_languages'), 'navlink').
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
							hed(gTxt($a['event']), 3, ' class="pref-heading '.$a['event'].'-prefs"')
						, 2)
					);
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

//-------------------------------------------------------------

	function languages($name, $val)
	{
		$installed_langs = safe_column('lang', 'txp_lang', "1 = 1 group by lang");

		$vals = array();

		foreach ($installed_langs as $lang)
		{
			$vals[$lang] = safe_field('data', 'txp_lang', "name = '".doSlash($lang)."' AND lang = '".doSlash($lang)."'");

			if (trim($vals[$lang]) == '')
			{
				$vals[$lang] = $lang;
			}
		}

		asort($vals);
		reset($vals);

		$out = n.'<select id="'.$name.'" name="'.$name.'" class="languages">';

		foreach ($vals as $avalue => $alabel)
		{
			$selected = ($avalue == $val || $alabel == $val) ?
				' selected="selected"' :
				'';

			$out .= n.t.'<option value="'.txpspecialchars($avalue).'"'.$selected.'>'.txpspecialchars($alabel).'</option>'.n;
		}

		$out .= n.'</select>';

		return $out;
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
				sLink('prefs', 'list_languages', gTxt('manage_languages'), 'navlink').
			n.'</p>'.

			n.n.startTable('', '', 'txp-list')
			.'<tbody>';

		$rs = safe_rows_start('*', 'txp_prefs', "type = 1 and prefs_id = 1 order by event, position");

		$cur_evt = '';

		while ($a = nextRow($rs))
		{
			if ($a['event']!= $cur_evt)
			{
				$cur_evt = $a['event'];

				echo n.n.tr(
					tdcs(
						hed(gTxt($a['event']), 3, ' class="pref-heading '.$a['event'].'-prefs"')
					, 2)
				);
			}

				$label = (!in_array($a['html'], array('yesnoradio', 'is_dst'))) ?
					'<label for="'.$a['name'].'">'.gTxt($a['name']).'</label>' :
					gTxt($a['name']);

			$out = tda($label.n.popHelp($a['name']), ' class="pref-label"');

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

//-------------------------------------------------------------
// install/update/remove languages
	function list_languages($message='')
	{
		global $prefs, $locale, $textarray;
		require_once txpath.'/lib/IXRClass.php';

		// Select and save active language
		if (!$message && ps('step')=='list_languages' && ps('language'))
		{
				$locale = doSlash(getlocale(ps('language')));
				safe_update("txp_prefs","val='".doSlash(ps('language'))."'", "name='language'");
				safe_update("txp_prefs","val='". $locale ."'", "name='locale'");
				$textarray = load_lang(doSlash(ps('language')));
				$locale = setlocale(LC_ALL, $locale);
				$message = gTxt('preferences_saved');
		}
		$active_lang = safe_field('val','txp_prefs',"name='language'");
		$lang_form = '<div id="language_control" class="txp-control-panel">'.
								form(
									graf(
										gTxt('active_language').
										languages('language',$active_lang).n.
										fInput('submit','Submit',gTxt('save'),'publish').
										eInput('prefs').sInput('list_languages')
									)
								).'</div>';

		$client = new IXR_Client(RPC_SERVER);
		//$client->debug = true;

		$available_lang = array();
		$rpc_connect = false;
		$show_files = false;

		// Get items from RPC
		@set_time_limit(90);
		if ($client->query('tups.listLanguages',$prefs['blog_uid']))
		{
			$rpc_connect = true;
			$response = $client->getResponse();
			foreach ($response as $language)
				$available_lang[$language['language']]['rpc_lastmod'] = gmmktime($language['lastmodified']->hour,$language['lastmodified']->minute,$language['lastmodified']->second,$language['lastmodified']->month,$language['lastmodified']->day,$language['lastmodified']->year);
		}
		elseif (gps('force') != 'file')
		{
			$msg = gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->";
		}

		// Get items from Filesystem
		$files = get_lang_files();

		if ( is_array($files) && !empty($files) )
		{
			foreach ($files as $file)
			{
				if ($fp = @fopen(txpath.DS.'lang'.DS.$file,'r'))
				{
					$name = str_replace('.txt','',$file);
					$firstline = fgets($fp, 4069);
					fclose($fp);
					if (strpos($firstline,'#@version') !== false)
						@list($fversion,$ftime) = explode(';',trim(substr($firstline,strpos($firstline,' ',1))));
					else
						$fversion = $ftime = NULL;

					$available_lang[$name]['file_note'] = (isset($fversion)) ? $fversion : 0;
					$available_lang[$name]['file_lastmod'] = (isset($ftime)) ? $ftime : 0;
				}
			}
		}

		// Get installed items from the database
		// We need a value here for the language itself, not for each one of the rows
		$rows = safe_rows('lang, UNIX_TIMESTAMP(MAX(lastmod)) as lastmod','txp_lang',"1 GROUP BY lang ORDER BY lastmod DESC");
		$installed_lang = array();
		foreach ($rows as $language)
		{
			$available_lang[$language['lang']]['db_lastmod'] = $language['lastmod'];
			if ($language['lang'] != $active_lang) {
				$installed_lang[] = $language['lang'];
			}
		}

		$list = '';

		// Show the language table
		foreach ($available_lang as $langname => $langdat)
		{
			$file_updated = ( isset($langdat['db_lastmod']) && @$langdat['file_lastmod'] > $langdat['db_lastmod']);
			$rpc_updated = ( @$langdat['rpc_lastmod'] > @$langdat['db_lastmod']);

			$rpc_install = tda(
								($rpc_updated)
								? strong(
										eLink(
											'prefs',
											'get_language',
											'lang_code',
											$langname,
											(isset($langdat['db_lastmod'])
												? gTxt('update')
												: gTxt('install')
											),
											'updating',
											isset($langdat['db_lastmod']),
											''
										)
									).
									n.'<span class="date modified">'.safe_strftime('%d %b %Y %X',@$langdat['rpc_lastmod']).'</span>'
								: (
										(isset($langdat['rpc_lastmod'])
											? gTxt('updated')
											: '-'
										).
										(isset($langdat['db_lastmod'])
											? n.'<span class="date modified">'.safe_strftime('%d %b %Y %X',$langdat['db_lastmod']).'</span>'
											: ''
										)
									)
								,(isset($langdat['db_lastmod']) && $rpc_updated)
									? ' class="highlight pref-value"'
									: ' class="pref-value"'
								);

			$lang_file = tda(
								(isset($langdat['file_lastmod']))
								? strong(
									eLink(
										'prefs',
										'get_language',
										'lang_code',
										$langname,
										(
											($file_updated)
											? gTxt('update')
											: gTxt('install')
										),
										'force',
										'file',
										''
									)
								).
								n.'<span class="date '.($file_updated ? 'created' : 'modified').'">'.safe_strftime($prefs['archive_dateformat'],$langdat['file_lastmod']).'</span>'

								: '-'
							, ' class="langfile pref-value languages_detail'.((isset($langdat['db_lastmod']) && $rpc_updated) ? ' highlight' : '').'"'
							);
			$list .= tr (
				// Lang-Name & Date
				tda(gTxt($langname)
					, (isset($langdat['db_lastmod']) && $rpc_updated)
							? ' class="highlight pref-label"'
							: ' class="pref-label"' ).n.
				$rpc_install.n.
				$lang_file.n.
				tda( (in_array($langname, $installed_lang) ? dLink('prefs', 'remove_language', 'lang_code', $langname, 1) : '-'), ' class="languages_detail'.((isset($langdat['db_lastmod']) && $rpc_updated) ? ' highlight' : '').'"')
			).n.n;
		}


		// Output Table + Content
		pagetop(gTxt('update_languages'),$message);

		//TODO: tab_languages when this panel is moved to its own tab
		echo '<h1 class="txp-heading">'.gTxt('update_languages').'</h1>';
		echo n.'<div id="language_container" class="txp-container">';

		if (isset($msg) && $msg)
			echo tag ($msg,'p',' class="error lang-msg"' );

		echo n.'<p class="nav-tertiary">'.
				sLink('prefs', 'prefs_list', gTxt('site_prefs'), 'navlink').
				sLink('prefs', 'advanced_prefs', gTxt('advanced_preferences'), 'navlink').
				sLink('prefs', 'list_languages', gTxt('manage_languages'), 'navlink-active').
			n.'</p>';

		echo $lang_form;

		echo n.'<div class="txp-listtables">',
			startTable('', '', 'txp-list'),
			'<thead>',
			tr(
				hCell(gTxt('language')).
				hCell(gTxt('from_server').n.popHelp('install_lang_from_server')).
				hCell(gTxt('from_file').n.popHelp('install_lang_from_file'), '', ' class="languages_detail"').
				hCell(gTxt('remove_lang').n.popHelp('remove_lang'), '', ' class="languages_detail"')
			),
			'</thead>';

		echo n.'<tfoot>'.tr(
				tda(
					toggle_box('languages_detail'),
					' class="detail-toggle" colspan="4"'
				)
			).n.'</tfoot>';

		echo '<tbody>'.$list.'</tbody>',
			endTable(),
			n.'</div>';

		echo
			hed(gTxt('install_from_textpack'), 3).n
				.form(
					graf(
						'<label for="textpack-install">'.gTxt('install_textpack').'</label>'.n.
						popHelp('get_textpack').n.
						'<textarea id="textpack-install" class="code" name="textpack" cols="'.INPUT_LARGE.'" rows="'.INPUT_XSMALL.'"></textarea>'.n.
						fInput('submit', 'install_new', gTxt('upload')).
						eInput('prefs').
						sInput('get_textpack')
					)
				, '', '', 'post', 'edit-form', '', 'text_uploader');

		echo '</div>'; // end language_container
	}

//-------------------------------------------------------------
	function get_language()
	{
		global $prefs, $textarray;
		require_once txpath.'/lib/IXRClass.php';
		$lang_code = gps('lang_code');

		$client = new IXR_Client(RPC_SERVER);
//		$client->debug = true;

		@set_time_limit(90);
		if (gps('force')=='file' || !$client->query('tups.getLanguage',$prefs['blog_uid'],$lang_code))
		{
			if ( (gps('force')=='file' || gps('updating')!=='1') && install_language_from_file($lang_code) )
			{
				if (defined('LANG'))
					$textarray = load_lang(LANG);

				return list_languages(gTxt($lang_code).sp.gTxt('updated'));
			}
			else
			{
				pagetop(gTxt('installing_language'));
				echo tag( gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->"
						,'p',' class="error lang-msg"' );
			}
		}
		else
		{
			$response = $client->getResponse();
			$lang_struct = unserialize($response);
			if ($lang_struct === false) {
				$errors = $size = 1;
			} else {
				function install_lang_key(&$value, $key)
				{
					extract(gpsa(array('lang_code','updating')));
					$exists = safe_field('name','txp_lang',"name='".doSlash($value['name'])."' AND lang='".doSlash($lang_code)."'");
					$q = "name='".doSlash($value['name'])."', event='".doSlash($value['event'])."', data='".doSlash($value['data'])."', lastmod='".doSlash(strftime('%Y%m%d%H%M%S',$value['uLastmod']))."'";

					if ($exists)
					{
						$value['ok'] = safe_update('txp_lang',$q,"lang='".doSlash($lang_code)."' AND name='".doSlash($value['name'])."'");
					}
					else
					{
						$value['ok'] = safe_insert('txp_lang',$q.", lang='".doSlash($lang_code)."'");
					}
				}

				array_walk($lang_struct,'install_lang_key');
				$size = count($lang_struct);
				$errors = 0;
				for($i=0; $i < $size ; $i++)
				{
					$errors += ( !$lang_struct[$i]['ok'] );
				}

				if (defined('LANG')) {
					$textarray = load_lang(LANG);
				}
			}

			$msg = gTxt($lang_code).sp.gTxt('updated');

			if ($errors > 0) {
				$msg = array($msg.sprintf(" (%s errors, %s ok)",$errors, ($size-$errors)), E_ERROR);
			}

			return list_languages($msg);
		}
	}

//-------------------------------------------------------------
	function get_textpack()
	{
		$textpack = ps('textpack');
		$n = install_textpack($textpack, true);
		return list_languages(gTxt('textpack_strings_installed', array('{count}' => $n)));
	}

//-------------------------------------------------------------
	function remove_language()
	{
		$lang_code = ps('lang_code');
		$ret = safe_delete('txp_lang', "lang='".doSlash($lang_code)."'");
		if ($ret) {
			$msg = gTxt($lang_code).sp.gTxt('deleted');
		}
		else
		{
			$msg = gTxt('cannot_delete', array('{thing}' => $lang_code));
		}

		return list_languages($msg);
	}

// ----------------------------------------------------------------------

	function get_lang_files()
	{
		$lang_dir = txpath.DS.'lang'.DS;

		if (!is_dir($lang_dir))
		{
			trigger_error('Lang directory is not a directory: '.$lang_dir, E_USER_WARNING);
			return array();
		}

		if (chdir($lang_dir))
		{
			$files = glob('*.txt');
		}
		return (is_array($files)) ? $files : array();
	}
?>

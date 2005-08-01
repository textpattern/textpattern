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

//-------------------------------------------------------------

define('RPC_SERVER', 'http://rpc.textpattern.com');

	if ($event == 'prefs') {
		require_privs('prefs');

		if(!$step or !in_array($step, array('advanced_prefs','prefs_save','advanced_prefs_save','get_language','list_languages','prefs_list'))){
			prefs_list();
		} else $step();
	}

// -------------------------------------------------------------
	function prefs_save() 
	{
		$prefnames = safe_column("name", "txp_prefs", "prefs_id='1'");

		$post = doSlash(stripPost());

		if (empty($post['tempdir']))
			$post['tempdir'] = doSlash(find_temp_dir());

		if (!empty($post['language']))
			$post['locale'] = doSlash(getlocale($post['language']));

		foreach($prefnames as $prefname) {
			if (isset($post[$prefname])) {
 				if ($prefname == 'lastmod') {
					safe_update("txp_prefs","val=now()", "name='lastmod'");
				} else {
					if($prefname == 'siteurl') {
						$post[$prefname] = str_replace("http://",'',$post[$prefname]);
						$post[$prefname] = rtrim($post[$prefname],"/");
					}
					safe_update(
						"txp_prefs", 
						"val = '".$post[$prefname]."'",
						"name = '$prefname' and prefs_id ='1'"
					);
				}
			}			
		}
		
		prefs_list(gTxt('preferences_saved'));		
	}

// -------------------------------------------------------------
	function prefs_list($message='') 
	{
		global $textarray;

		extract(get_prefs());
		$locale = setlocale(LC_ALL, $locale);
		$textarray = load_lang($language);

		echo 
		pagetop(gTxt('edit_preferences'),$message),
		'<form action="index.php" method="post">',
		startTable('list'),		
		tr(tdcs(hed(gTxt('site_prefs'),1),3)),
		
		tr(tdcs(sLink('prefs','advanced_prefs',gTxt('advanced_preferences')).sp.sLink('prefs','list_languages',gTxt('install_language')),'3'));
		
		$evt_list = safe_column('event','txp_prefs',"type='0' AND prefs_id='1' GROUP BY 'event' ORDER BY 'event' DESC");
		
		foreach ($evt_list as $event)
		{			
			$rs = safe_rows_start('*','txp_prefs',"type='0' AND prefs_id='1' AND event='$event' ORDER BY 'position'");
			$cur_evt = '';
			while ($a = nextRow($rs))
			{			
				if ($a['event']!= $cur_evt)
				{
					$cur_evt = $a['event'];
					if ($cur_evt == 'comments' && !$use_comments) continue;
					echo tr(tdcs(hed(ucfirst(gTxt($a['event'])),1),3));
				}
				if ($cur_evt == 'comments' && !$use_comments) continue;
	
				# Skip old settings that don't have an input type
				if (!is_callable($a['html']))
					continue;
	
				$out = tda(gTxt($a['name']), ' style="text-align:right;vertical-align:middle"');
				if ($a['html'] == 'text_input')
				{
					$size = 20;
					$out.= td(call_user_func('text_input', $a['name'], $a['val'], $size));
				}else{
					$out.= td(call_user_func($a['html'], $a['name'], $a['val']));
				}
				$out.= tda(popHelp($a['name']), ' style="vertical-align:middle"');
				echo tr($out);
			}			
		}		
			
		echo
		tr(tda(fInput('submit','Submit',gTxt('save_button'),'publish'),
			' colspan="3" class="noline"')),
		endTable(),
		sInput('prefs_save'),
		eInput('prefs'),
		hInput('prefs_id',"1"),
		hInput('lastmod',"now()"),
		'</form>';
		
		$check_updates = gps('check_updates');
		if (!empty($check_updates)){
			include_once txpath.'/lib/txplib_update.php';
			$updates = checkUpdates();
			if (is_array($updates))
			{
				$out = join(br,$updates);
			}else{
				$out = $updates;
			}
			echo startTable('edit'),tr(tda($out)),endTable();
		}else{
			echo startTable('edit'),form(
				tr(
					tda(tag(gTxt('check_for_txp_updates'),'strong'),' style="text-align:right;vertical-align:middle"').
				tda('<input type="submit" value="'.gTxt('check_for_txp_updates').'" name="check_updates" class="publish" style="width:175px;" />')).
				eInput('prefs').
				sInput('prefs_list')
			),endTable();
		}
	}

//-------------------------------------------------------------
	function text_input($item,$var,$size="") 
	{
		return fInput("text",$item,$var,'edit','','',$size);
	}
			
//-------------------------------------------------------------
	function gmtoffset_select($item,$var) {		
		// Standard time zones as compiled by H.M. Nautical Almanac Office, June 2004
		// http://aa.usno.navy.mil/faq/docs/world_tzones.html
		$tz = array(
			-12, -11, -10, -9.5, -9, -8.5, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 
			0,
			+1, +2, +3, +3.5, +4, +4.5, +5, +5.5, +6, +6.5, +7, +8, +9, +9.5, +10, +10.5, +11, +11.5, +12, +13, +14,
		);

		foreach ($tz as $z) {
			$sign = ($z >= 0 ? '+' : '');
			$name = sprintf("GMT %s%02d:%02d", $sign, $z, abs($z - (int)$z) * 60);
			$timevals[sprintf("%s%d", $sign, $z * 3600)] = $name;
		}

		return selectInput($item, $timevals, $var);
	}

//-------------------------------------------------------------
	function getlocale($lang) {
		global $locale;

		if (empty($locale))
			$locale = @setlocale(LC_TIME, '0');

		// Locale identifiers vary from system to system.  The
		// following code will attempt to discover which identifiers
		// are available.  We'll need to expand these lists to 
		// improve support.
		// ISO identifiers: http://www.w3.org/WAI/ER/IG/ert/iso639.htm
		// Windows: http://msdn.microsoft.com/library/default.asp?url=/library/en-us/vclib/html/_crt_language_strings.asp
		$guesses = array(
			'cs-cz' => array('cs_CZ.UTF-8', 'cs_CZ', 'ces', 'cze', 'cs', 'csy', 'czech', 'cs_CZ.cs_CZ.ISO_8859-2'),
			'de-de' => array('de_DE.UTF-8', 'de_DE', 'de', 'deu', 'german', 'de_DE.ISO_8859-1'),
			'en-gb' => array('en_GB.UTF-8', 'en_GB', 'en_UK', 'eng', 'en', 'english-uk', 'english', 'en_GB.ISO_8859-1'),
			'en-us' => array('en_US.UTF-8', 'en_US', 'english-us', 'en_US.ISO_8859-1'),
			'es-es' => array('es_ES.UTF-8', 'es_ES', 'esp', 'spanish', 'es_ES.ISO_8859-1'),
			'el-gr' => array('el_GR.UTF-8', 'el_GR', 'el', 'gre', 'greek', 'el_GR.ISO_8859-7'),
			'fr-fr' => array('fr_FR.UTF-8', 'fr_FR', 'fra', 'fre', 'fr', 'french', 'fr_FR.ISO_8859-1'),
			'it-it' => array('it_IT.UTF-8', 'it_IT', 'it', 'ita', 'italian', 'it_IT.ISO_8859-1'),
			'ja-jp' => array('ja_JP.UTF-8', 'ja_JP', 'ja', 'jpn', 'japanese', 'ja_JP.ISO_8859-1'),
			'no-no' => array('no_NO.UTF-8', 'no_NO', 'no', 'nor', 'norwegian', 'no_NO.ISO_8859-1'),
			'nl-nl' => array('nl_NL.UTF-8', 'nl_NL', 'dut', 'nla', 'nl', 'nld', 'dutch', 'nl_NL.ISO_8859-1'),
			'pt-pt' => array('pt_PT.UTF-8', 'pt_PT', 'por', 'portuguese', 'pt_PT.ISO_8859-1'),
			'ru-ru' => array('ru_RU.UTF-8', 'ru_RU', 'ru', 'rus', 'russian', 'ru_RU.ISO8859-5'),
			'sk-sk' => array('sk_SK.UTF-8', 'sk_SK', 'sk', 'slo', 'slk', 'sky', 'slovak', 'sk_SK.ISO_8859-1'),
			'sv-se' => array('sv_SE.UTF-8', 'sv_SE', 'sv', 'swe', 'sve', 'swedish', 'sv_SE.ISO_8859-1'),
			'th-th' => array('th_TH.UTF-8', 'th_TH', 'th', 'tha', 'thai', 'th_TH.ISO_8859-11')
		);

		if (!empty($guesses[$lang])) {
			$l = @setlocale(LC_TIME, $guesses[$lang]);
			if ($l !== false)
				$locale = $l;
		}
		@setlocale(LC_TIME, $locale);

		return $locale;
	}

//-------------------------------------------------------------
	function text($item,$var)
	{
		$things = array(
			"2" => gTxt('use_textile'),
			"1" => gTxt('convert_linebreaks'),
			"0" => gTxt('leave_text_untouched'));
		return selectInput($item, $things, $var);
	}

//-------------------------------------------------------------
	function logging($item,$var) 
	{	
		$things = array(
			"all"   => gTxt('all_hits'),
			"refer" => gTxt('referrers_only'),
			"none"  => gTxt('none'));
		return selectInput($item, $things, $var);
	}

//-------------------------------------------------------------
	function permlinkmodes($item,$var) 
	{
		$things = array(
			"messy" => gTxt('messy'),
			"id_title" => gTxt('id_title'),
			"section_id_title" => gTxt("section_id_title"),
			"year_month_day_title" => gTxt("year_month_day_title"),
			"section_title"=>gTxt('section_title'),
			"title_only" => gTxt("title_only"),
#			"category_subcategory" => gTxt('category_subcategory')
		);
		return selectInput($item, $things, $var);
	}

//-------------------------------------------------------------
	function urlmodes($item,$var) 
	{
		$things = array("0"=>gTxt("messy"),"1"=>gTxt("clean"));
		return selectInput($item, $things, $var);
	}

//-------------------------------------------------------------
	function commentmode($item,$var) 
	{
		$things = array("0"=>gTxt("nopopup"),"1"=>gTxt("popup"));
		return selectInput($item, $things, $var);
	}

//-------------------------------------------------------------
	function weeks($item,$var)
	{
		$weeks = gTxt('weeks');
		$things = array(
			'0' => gTxt('never'),
			7   => '1 '.gTxt('week'),
			14  => '2 '.$weeks,
			21  => '3 '.$weeks,
			28  => '4 '.$weeks,
			35  => '5 '.$weeks,
			42  => '6 '.$weeks);
		return selectInput($item, $things, $var);
	}

//-------------------------------------------------------------
	function languages($item,$var) 
	{
		$installed_langs = safe_column('lang','txp_lang',"1 GROUP BY 'lang'");
		
		$things = array();
		
		foreach ($installed_langs as $lang)
		{
			$things[$lang] = safe_field('data','txp_lang',"name='$lang' AND lang='$lang'");			
		}
					
		asort($things);
		reset($things);

		$out = '<select name="'.$item.'" class="list">'.n;
		foreach ($things as $avalue => $alabel) {
			$selected = ($avalue == $var || $alabel == $var)
			?	' selected="selected"'
			:	'';
			$out .= t.'<option value="'.htmlspecialchars($avalue).'"'.$selected.'>'.
					$alabel.'</option>'.n;
		}
		$out .= '</select>'.n;
		return $out;			
	}

// -------------------------------------------------------------
	function dateformats($item,$var) {

		$dayname = '%A';
		$dayshort = '%a';
		$daynum = (is_numeric(strftime('%e')) ? '%e' : '%d');
		$daynumlead = '%d';
		$daynumord = (is_numeric(substr(trim(strftime('%Oe')), 0, 1)) ? '%Oe' : $daynum);
		$monthname = '%B';
		$monthshort = '%b';
		$monthnum = '%m';
		$year = '%Y';
		$yearshort = '%y';
		$time24 = '%H:%M';
		$time12 = (strftime('%p') ? '%I:%M %p' : $time24);
		$date = (strftime('%x') ? '%x' : '%Y-%m-%d');
	
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
		foreach ($formats as $f)
			if ($d = safe_strftime($f, $ts))
				$dateformats[$f] = $d;

		$dateformats['since'] = 'hrs/days ago';

		return selectInput($item, array_unique($dateformats), $var);
	}
//-------------------------------------------------------------
	function prod_levels($item, $var) {
		$levels = array(
			'debug'   => gTxt('production_debug'),
			'testing' => gTxt('production_test'),
			'live'    => gTxt('production_live'),
		);

		return selectInput($item, $levels, $var);
	}
	
//-------------------------------------------------------------
	function advanced_prefs($message='')
	{
		global $textarray;
		#this means new language strings and new help entries		
		echo 
		pagetop(gTxt('advanced_preferences'),$message),
		'<form action="index.php" method="post">',
		startTable('list'),
		tr(tdcs(hed(gTxt('advanced_preferences'),1),3)),
		tr(tdcs(sLink('prefs','prefs_list',gTxt('site_prefs')).sp.sLink('prefs','list_languages',gTxt('install_language')),'3'));		
				
		$rs = safe_rows_start('*','txp_prefs',
			"type='1' AND prefs_id='1' ORDER BY event,position");
		$cur_evt = '';
		while ($a = nextRow($rs))
		{			
			if ($a['event']!= $cur_evt)
			{
				$cur_evt = $a['event'];
				echo tr(tdcs(hed(ucfirst(gTxt($a['event'])),1),3));
			}
			$out = tda(gTxt($a['name']), ' style="text-align:right;vertical-align:middle"');
			if ($a['html'] == 'text_input')
			{
				$size = ($a['name'] == 'expire_logs_after' || $a['name'] == 'max_url_len' || $a['name'] == 'time_offset' || $a['name'] == 'rss_how_many' || $a['name'] == 'logs_expire')? 3 : 20;
				$out.= td(call_user_func('text_input', $a['name'], $a['val'], $size));
			}else{
				$out.= td(call_user_func($a['html'], $a['name'], $a['val']));
			}
			$out.= tda(popHelp($a['name']), ' style="vertical-align:middle"');
			echo tr($out);
		}
		
		echo tr(tda(fInput('submit','Submit',gTxt('save_button'),'publish'),
			' colspan="3" class="noline"')),
		endTable(),
		sInput('advanced_prefs_save'),
		eInput('prefs'),
		hInput('prefs_id',"1"),
		hInput('lastmod',"now()"),
		'</form>';	
	}

//-------------------------------------------------------------
	function real_max_upload_size($user_max) 
	{
		// The minimum of the candidates, is the real max. possible size
		$candidates = array($user_max,
							ini_get('post_max_size'), 
							ini_get('upload_max_filesize'),
							ini_get('memory_limit'));
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
		$prefnames = safe_column("name", "txp_prefs", "prefs_id='1' AND type='1'");
		
		$post = doSlash(stripPost());

		if (!empty($post['file_max_upload_size']))
			$post['file_max_upload_size'] = real_max_upload_size($post['file_max_upload_size']);

		foreach($prefnames as $prefname) {
			if (isset($post[$prefname])) {
					safe_update(
						"txp_prefs", 
						"val = '".$post[$prefname]."'",
						"name = '$prefname' and prefs_id ='1'"
					);
			}			
		}
		
		advanced_prefs(gTxt('preferences_saved'));	
	}
	
//-------------------------------------------------------------
	# RPC install/update languages
	function list_languages($message='')
	{
		global $prefs;
		require_once txpath.'/lib/IXRClass.php';
		pagetop(gTxt('update_languages'),$message);

		$client = new IXR_Client(RPC_SERVER);
		#$client->debug = true;

		$available_lang = array();
		$rpc_connect = false;$show_files = false;

		# Get items from RPC
		if (gps('force')!='file' && $client->query('tups.listLanguages',$prefs['blog_uid']))
		{
			$rpc_connect = true;
			$response = $client->getResponse();
			foreach ($response as $language)
				$available_lang[$language['language']]['rpc_lastmod'] = gmmktime($language['lastmodified']->hour,$language['lastmodified']->minute,$language['lastmodified']->second,$language['lastmodified']->month,$language['lastmodified']->day,$language['lastmodified']->year);
		} elseif (gps('force')!='file') $msg = gTxt('rpc_connect_error');

		# Get items from Filesystem
		$files = get_lang_files();
		if ( (gps('force')=='file' || !$rpc_connect) && is_array($files) && !empty($files))
		{
			$show_files = true;
			foreach ($files as $file)
			{
				if ($fp = @fopen(txpath.'/lang/'.$file,'r'))
				{
					$name = str_replace('.txt','',$file);
					$firstline = fgets($fp, 4069);
					fclose($fp);
					if (strpos($firstline,'#@version') !== false) 
						@list($fversion,$ftime) = explode(';',trim(substr($firstline,strpos($firstline,' ',1))));
					$available_lang[$name]['file_note'] = ($fversion) ? $fversion : 0;
					$available_lang[$name]['file_lastmod'] = ($ftime) ? $ftime : 0;
				}
			}
		}
		# Get installed items from the database
		# I'm affraid we need a value here for the language itself, not for each one of the rows
		$rows = safe_rows('lang, UNIX_TIMESTAMP(MAX(lastmod)) as lastmod','txp_lang',"1 GROUP BY lang ORDER BY lastmod DESC");
		foreach ($rows as $language)
		{
			$available_lang[$language['lang']]['db_lastmod'] = $language['lastmod'];
		}

		$list = '';
		# Show the language table 
		foreach ($available_lang as $langname => $langdat)
		{
			$file_updated = ( isset($langdat['db_lastmod']) && @$langdat['file_lastmod'] > $langdat['db_lastmod']);
			$rpc_updated = ( @$langdat['rpc_lastmod'] > @$langdat['db_lastmod']);
			$rpc_install = tda( eLink('prefs','get_language','lang_code',$langname,(isset($langdat['db_lastmod'])) 
										? strong(gTxt('update')) : strong(gTxt('install')),'updating',isset($langdat['db_lastmod']) ).
								br.safe_strftime($prefs['archive_dateformat'],@$langdat['rpc_lastmod'])
							,(isset($langdat['db_lastmod'])) 
								? ' style="color:red;text-align:center;background-color:#FFFFCC;"'
								: ' style="color:#667;vertical-align:middle;text-align:center"');
			$list.= tr (
				# Lang-Name & Date
				tda(gTxt($langname).
					 tag( ( isset($langdat['db_lastmod']) ) 
							? br.'&nbsp;'.safe_strftime($prefs['archive_dateformat'],$langdat['db_lastmod'])
							: ''
						, 'span',' style="color:#aaa;font-style:italic"')
					, (isset($langdat['db_lastmod']) && $rpc_updated) #tda attribute
							? ' nowrap="nowrap" style="color:red;background-color:#FFFFCC;"' 
							: ' nowrap="nowrap" style="vertical-align:middle"' ).n.
				# RPC - Info
				(  ($rpc_updated) 
					? $rpc_install 
					: tda( (isset($langdat['rpc_lastmod'])) ? gTxt('updated') : '-'
						,' style="vertical-align:middle;text-align:center"')
				).n.
				# File - Info
				( ($show_files)
					?	tda( tag( ( isset($langdat['file_lastmod']) ) 
									? eLink('prefs','get_language','lang_code',$langname,($file_updated) ? gTxt('update') : gTxt('install'),'force','file').
											br.'&nbsp;'.safe_strftime($prefs['archive_dateformat'],$langdat['file_lastmod'])
									: ' &nbsp; '  # No File available
								, 'span', ($file_updated) ? ' style="color:#667;"' : ' style="color:#aaa;font-style:italic"' )
							, ' class="langfile" style="text-align:center;vertical-align:middle"').n
					:   '')
			).n.n;
		}

		// Output Table + Content

		if (isset($msg) && $msg)
			echo tag ($msg,'p',' style="text-align:center;color:red;width:50%;margin: 2em auto"' );

		echo startTable('list'),				
		tr(tdcs(hed(gTxt('update_languages'),1),3)),
		tr(tdcs(sLink('prefs','prefs_list',gTxt('site_prefs')).sp.sLink('prefs','advanced_prefs',gTxt('advanced_preferences')),'3')),
		tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')),
		tr(tda(gTxt('language')).tda(gTxt('from_server')).( ($show_files) ? tda(gTxt('from_file')) : '' ), ' style="font-weight:bold"');
		echo $list;
		if (!$show_files)
		{
			$linktext = strong( gTxt('from_file').' ('.gTxt('experts_only').')' );
			echo tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')).
				 tr(tda(eLink('prefs','list_languages','force','file',$linktext),' colspan="3" style="text-align:center"') );
		} elseif (gps('force')=='file')	{
			echo tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')).
				 tr(tda(sLink('prefs','list_languages',strong(gTxt('from_server'))),' colspan="3" style="text-align:center"') );
		}
		echo endTable();

		$install_langfile = str_replace( '{url}', strong('<a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a>'), gTxt('install_langfile'));
		if ( $install_langfile == 'install_langfile')
			$install_langfile = 'To install new languages from file you can download them from <b><a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a></b> and place them inside your ./textpattern/lang/ directory.';
		echo tag( $install_langfile ,'p',' style="text-align:center;width:50%;margin: 2em auto"' );

	}
	
//-------------------------------------------------------------
	function get_language()
	{
		global $prefs;
		require_once txpath.'/lib/IXRClass.php';
		$lang_code = gps('lang_code');		

		$client = new IXR_Client(RPC_SERVER);
//		$client->debug = true;
		
		if (gps('force')=='file' || !$client->query('tups.getLanguage',$prefs['blog_uid'],$lang_code))
		{
			include_once txpath.'/lib/txplib_update.php';
			if ( (gps('force')=='file' || gps('updating')!=='1') && install_language_from_file($lang_code) )
			{
				return list_languages(gTxt($lang_code).sp.gTxt('updated'));
			}else{
				$install_langfile = str_replace( '{url}', strong('<a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a>'), gTxt('install_langfile'));
				if ( $install_langfile == 'install_langfile')
					$install_langfile = 'To install new languages from file you can download them from <b><a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a></b> and place them inside your ./textpattern/lang/ directory.';
				pagetop(gTxt('installing_language'));
				echo tag( gTxt('rpc_connect_error') ,'p',' style="text-align:center;color:red;width:50%;margin: 2em auto"' );
				echo tag( $install_langfile ,'p',' style="text-align:center;width:50%;margin: 2em auto"' );
			}			
		}else {
			$response = $client->getResponse();
			$lang_struct = unserialize($response);
			function install_lang_key($value, $key)
			{
				extract(gpsa(array('lang_code','updating')));
				$exists = safe_field('name','txp_lang',"name='$value[name]' AND lang='$lang_code'");				
				$q = "name='".doSlash($value['name'])."', event='".doSlash($value['event'])."', data='".doSlash($value['data'])."', lastmod='".strftime('%Y%m%d%H%M%S',$value['uLastmod'])."'";

				if ($exists)
				{
					safe_update('txp_lang',$q,"lang='$lang_code' AND name='$value[name]'");
				}else{
					safe_insert('txp_lang',$q.", lang='$lang_code'");
				}
			}			
			array_walk($lang_struct,'install_lang_key');
			
			return list_languages(gTxt($lang_code).sp.gTxt('updated'));
		}		
	}
	
// ----------------------------------------------------------------------

function get_lang_files()
{
	global $txpcfg;
	
	$dirlist = array();
	
	$lang_dir = $txpcfg['txpath'].'/lang/';

	if (!is_dir($lang_dir))
		return $dirlist;		
	
	if (chdir($lang_dir)) {
		if (function_exists('glob')){
			$g_array = glob("*.txt");
		}else {
			# filter .txt only files here?
			$dh = opendir($lang_dir);
			$g_array = array();
			while (false !== ($filename = readdir($dh))) {
				$g_array[] = $filename;
			}
			closedir($dh);
			
		}
		# build an array of lang-codes => filemtimes
		if ($g_array) {
			foreach ($g_array as $lang_name) {
				if (is_file($lang_name)) {
					$dirlist[substr($lang_name,0,5)] = @filemtime($lang_name);
				}
			}
		}
	}
	return $g_array;
}
	
?>

<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/

//-------------------------------------------------------------

	$step = gps('step');

	if(!$step or !function_exists($step)){
		prefs_list();
	} else $step();

	require_privs('prefs');


// -------------------------------------------------------------
	function prefs_save() 
	{
		$prefnames = safe_column("name", "txp_prefs", "prefs_id='1'");

		$post = doSlash(stripPost());

		if (empty($post['tempdir']))
			$post['tempdir'] = find_temp_dir();

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
		extract(get_prefs());
		$locale = setlocale(LC_ALL, $locale);

		echo 
		pagetop(gTxt('edit_preferences'),$message),
		'<form action="index.php" method="post">',
		startTable('list'),
		tr(tdcs(hed(gTxt('site_prefs'),1),3)),

		pCell('sitename',$sitename,'input',20),
		pCell('siteurl',$siteurl,'input',20),
		pCell('site_slogan',$site_slogan,'input',20),
		pCell('language',$language,'languages'),
		pCell('locale',$locale,'locales'),
		pCell('gmtoffset',$gmtoffset,'gmtoffset'),
		pCell('is_dst',$is_dst,'radio'),
		pCell('dateformat',$dateformat,'dateformats'),
		pCell('archive_dateformat',$archive_dateformat,'dateformats'),
		pCell('permlink_mode',$permlink_mode,'permlinkmodes'),
		pCell('send_lastmod',$send_lastmod,'radio'),
		pCell('ping_weblogsdotcom',$ping_weblogsdotcom,'radio'),
		pCell('logging',$logging,'logging'),
		pCell('use_textile',$use_textile,'text'),
		pCell('tempdir',$tempdir,'input',20),
		@pCell('file_base_path',$file_base_path,'input',20),
		@pCell('file_max_upload_size',$file_max_upload_size,'input',10);


			echo tr(tdcs(hed(gTxt('comments'),1),3)),
			pCell('use_comments',$use_comments,'radio');

		if ($use_comments) {

			echo
			pCell('comments_moderate',$comments_moderate,'radio'),
			pCell('comments_on_default',$comments_on_default,'radio'),
			pCell('comments_default_invite',$comments_default_invite,'input',15),
			pCell('comments_dateformat',$comments_dateformat,'dateformats'),
			pCell('comments_mode',$comments_mode,'commentmode'),
			pCell('comments_are_ol',$comments_are_ol,'radio'),
			pCell('comments_sendmail',$comments_sendmail,'radio'),
			pCell('comments_disallow_images',$comments_disallow_images,'radio'),
			pCell('comments_disabled_after',$comments_disabled_after,'weeks');

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
	}


//-------------------------------------------------------------
	function pCell($item,$var,$format,$size="",$nohelp="") {
		
		$out = tda(gTxt($item), ' style="text-align:right;vertical-align:middle"');
		switch($format) {
			case "radio":         $in = yesnoradio($item,$var);        break;
			case "input":         $in = text_input($item,$var,$size);  break;
			case "gmtoffset":     $in = gmtoffset_select($item,$var);  break;
			case 'commentmode':   $in = commentmode($item,$var);       break;
			case 'cases':         $in = cases($item,$var);             break;
			case 'locales':       $in = locale_select($item,$var);     break;
			case 'dateformats':   $in = dateformats($item,$var);       break;
			case 'weeks':         $in = weeks($item,$var);             break;
			case 'logging':       $in = logging($item,$var);           break;
			case 'languages':     $in = languages($item,$var);         break;
			case 'text':          $in = text($item,$var);              break;
			case 'permlinkmodes': $in = permlinkmodes($item,$var);     break;
			case 'urlmodes':      $in = urlmodes($item,$var);
		}
		$out.= td($in);
		$out.= ($nohelp!=1) ? tda(popHelp($item), ' style="vertical-align:middle"') : td();
		return tr($out);
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
	function locale_select($item, $var) {
		global $locale;

		if (empty($locale))
			$locale = @setlocale(LC_TIME, '0');

		$locales = array(''=>gTxt('default'));

		// Locale identifiers vary from system to system.  The
		// following code will attempt to discover which identifiers
		// are available.  We'll need to expand these lists to 
		// improve support.
		$guesses = array(
			'en-gb' => array('en_GB', 'en_UK', 'english-uk', 'en_GB.UTF-8', 'en_GB.ISO_8859-1'),
			'en-us' => array('en_US', 'english-us', 'en_US.UTF-8', 'en_US.ISO_8859-1'),
			'fr-fr' => array('fr_FR', 'fr', 'french', 'fr_FR.UTF-8', 'fr_FR.ISO_8859-1'),
			'de-de' => array('de_DE', 'de', 'deu', 'german', 'de_DE.UTF-8', 'de_DE.ISO_8859-1'),
			'es-es' => array('es_ES', 'es', 'esp', 'spanish', 'es_ES.UTF-8', 'es_ES.ISO_8859-1'),
			'it-it' => array('it_IT', 'it', 'ita', 'italian', 'it_IT.UTF-8', 'it_IT.ISO_8859-1'),
		);

		foreach ($guesses as $name => $guess) {
			$l = @setlocale(LC_TIME, $guess);
			if ($l !== false)
				$locales[$l] = gTxt($name);
		}
		@setlocale(LC_TIME, $locale);

		return selectInput($item, $locales, $var);
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
		$things = array(
			'en-gb' => gTxt('english_gb'),
			'en-us' => gTxt('english_us'),
			'fr-fr' => gTxt('french'),
			'es-es' => gTxt('spanish'),
			'sv-se' => gTxt('swedish'),
			'it-it' => gTxt('italian'),
			'cs-cz' => gTxt('czech'),
			'de-de' => gTxt('german'),
			'no-no' => gTxt('norwegian'),
			'ru-ru' => gTxt('russian'),
			'th-th' => gTxt('thai'),
//			'pt-pt' => gTxt('portuguese'),
//			'fi-fi' => gTxt('finnish'),
//			'du-du' => gTxt('dutch'),
//			'da-da' => gTxt('danish'),
//			'po-po' => gTxt('polish'),
//			'tl-tl' => gTxt('tagalog'),
//			'gl-gl' => gTxt('scots')
		);
			asort($things);
			reset($things);

		return selectInput($item, $things, $var);
	}

// -------------------------------------------------------------
	function dateformats($item,$var) {
		$dateformats = array(
			'%b %e, %I:%M%p'    => safe_strftime('%b %e, %H:%M%p'),
			'%e.%m.%y'          => safe_strftime('%e.%m.%y'),
			'%e %B, %I:%M%p'    => safe_strftime('%e %B, %H:%M%p'),
			'%y.%m.%d, %I:%M%p' => safe_strftime('%y.%m.%d, %H:%M%p'),
			'%H:%M%p'           => safe_strftime('%H:%M%p'),
			'%a %b %e, %I:%M%p' => safe_strftime('%a %b %e, %H:%M%p'),
			'%A, %B %e, %Y'     => safe_strftime('%A, %B %e, %Y'),
			'%b %e'             => safe_strftime('%b %e'),
			'%e %B %y'          => safe_strftime('%e %B %y'),
			'%e %m %Y - %H:%M'  => safe_strftime('%e %m %Y - %H:%M'),
			'%Y-%m-%d'          => safe_strftime('%Y-%m-%d'),
			'%Y-%d-%m'          => safe_strftime('%Y-%d-%m'),
			'%x %X'             => safe_strftime('%x %X'),
			'%x'                => safe_strftime('%x'),
			'%X'                => safe_strftime('%X'),
			'%x %r'             => safe_strftime('%x %r'),
			'%r'                => safe_strftime('%r'),
			'since'             => "hrs/days ago");
		return selectInput($item, $dateformats, $var);
	}

?>

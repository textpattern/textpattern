<?php

/*
	This is Textpattern

	Copyright 2004 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/

//-------------------------------------------------------------

	check_privs(1,2);

	$step = ps('step');

	if ($step=='save') {
	
		$prefnames = safe_column("name", "txp_prefs", "prefs_id='1'");

		$post = (get_magic_quotes_gpc()) ? doStrip($_POST) : $_POST;
		$post = doSlash($post);

		foreach($prefnames as $prefname) {
			if (isset($post[$prefname])) {
 				if ($prefname == 'lastmod') {
					safe_update("txp_prefs","val=now()", "name='lastmod'");
				} else {
					if($prefname == 'siteurl') {
						$post[$prefname] = str_replace("http://",'',$post[$prefname]);
					}
					safe_update(
						"txp_prefs", 
						"val = '".$post[$prefname]."'",
						"name = '$prefname' and prefs_id ='1'"
					);
				}
			}			
		}
		
		$message = gTxt('preferences_saved');
	}
	
		extract(get_prefs());
		
		$message = (isset($message)) ? $message : "";

		echo 
		pagetop(gTxt('edit_preferences'),$message),
		'<form action="index.php" method="post">',
		startTable('list'),
		tr(tdcs(hed(gTxt('site_prefs'),1),3)),

		pCell('sitename',$sitename,'input',20),
		pCell('siteurl',$siteurl,'input',20),
		pCell('path_from_root',$path_from_root,'input',20),
		pCell('site_slogan',$site_slogan,'input',20),
		pCell('language',$language,'languages'),
		pCell('timeoffset',$timeoffset,'timeoffset'),
		pCell('dateformat',$dateformat,'dateformats'),
		pCell('archive_dateformat',$archive_dateformat,'dateformats'),
		pCell('url_mode',$url_mode,'urlmodes'),
		pCell('send_lastmod',$send_lastmod,'radio'),
		pCell('ping_weblogsdotcom',$ping_weblogsdotcom,'radio'),
		pCell('logging',$logging,'logging'),
		pCell('record_mentions',$record_mentions,'radio'),
		pCell('use_textile',$use_textile,'text'),
		pCell('use_categories',$use_categories,'radio'),
		pCell('use_sections',$use_sections,'radio');


			echo tr(tdcs(hed(gTxt('comments'),1),3)),
			pCell('use_comments',$use_comments,'radio');

		if ($use_comments) {

			echo
			pCell('comments_moderate',$comments_moderate,'radio'),
			pCell('comments_on_default',$comments_on_default,'radio'),
			pCell('comments_default_invite',$comments_default_invite,'input',15),
			pCell('comments_dateformat',$comments_dateformat,'dateformats'),
			pCell('comments_mode',$comments_mode,'commentmode'),
			pCell('comments_sendmail',$comments_sendmail,'radio'),
			pCell('comments_disallow_images',$comments_disallow_images,'radio'),
			pCell('comments_disabled_after',$comments_disabled_after,'weeks');

		} 
	
		echo
		tr(tda(fInput('submit','Submit',gTxt('save_button'),'publish'),
			' colspan="3" class="noline"')),
		endTable(),
		sInput('save'),
		eInput('prefs'),
		hInput('prefs_id',"1"),
		hInput('lastmod',"now()"),
		'</form>';


//-------------------------------------------------------------
	function pCell($item,$var,$format,$size="",$nohelp="") {
		
		$var = stripslashes($var);
		
		$out = tda(gTxt($item), ' style="text-align:right;vertical-align:middle"');
		switch($format) {
			case "radio":       $in = yesnoradio($item,$var);        break;
			case "input":       $in = text_input($item,$var,$size);  break;
			case "timeoffset":  $in = timeoffset_select($item,$var); break;
			case 'commentmode': $in = commentmode($item,$var);       break;
			case 'cases':       $in = cases($item,$var);             break;
			case 'dateformats': $in = dateformats($item,$var);       break;
			case 'weeks':       $in = weeks($item,$var);             break;
			case 'logging':     $in = logging($item,$var);           break;
			case 'languages':   $in = languages($item,$var);         break;
			case 'text':        $in = text($item,$var);              break;
			case 'urlmodes':    $in = urlmodes($item,$var);
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
	function timeoffset_select($item,$var) {		
		$timevals = array(
			"-82800" => "-23",
			"-79200" => "-22",
			"-75600" => "-21",
			"-72000" => "-20",
			"-68400" => "-19",
			"-64800" => "-18",
			"-61200" => "-17",
			"-57600" => "-16",
			"-54000" => "-15",
			"-50400" => "-14",
			"-46800" => "-13",
			"-43200" => "-12",
			"-39600" => "-11",
			"-36000" => "-10",
			"-32400" => "-9",
			"-28800" => "-8",
			"-25200" => "-7",
			"-21600" => "-6",
			"-18000" => "-5",
			"-14400" => "-4",
			"-10800" => "-3",
			"-7200"  => "-2",
			"-3600"  => "-1",
			"0"      => "0",
			"+3600"  => "+1",
			"+7200"  => "+2",
			"+10800" => "+3",
			"+14400" => "+4",
			"+18000" => "+5",
			"+21600" => "+6",
			"+25200" => "+7",
			"+28800" => "+8",
			"+32400" => "+9",
			"+36000" => "+10",
			"+39600" => "+11",
			"+43200" => "+12",
			"+46800" => "+13",
			"+50400" => "+14",
			"+54000" => "+15",
			"+57600" => "+16",
			"+61200" => "+17",
			"+64800" => "+18",
			"+68400" => "+19",
			"+72000" => "+20",
			"+75600" => "+21",
			"+79200" => "+22",
			"+82800" => "+23"
		);
		return selectInput($item, $timevals, $var);
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
			14  =>'2 '.$weeks,
			21  =>'3 '.$weeks,
			28  =>'4 '.$weeks,
			35  =>'5 '.$weeks,
			42  =>'6 '.$weeks);
		return selectInput($item, $things, $var);
	}

//-------------------------------------------------------------
	function languages($item,$var) 
	{
		$things = array(
			'english'    => gTxt('english'),
			'french'     => gTxt('french'),
			'german'     => gTxt('german'),
			'italian'    => gTxt('italian'),
			'portuguese' => gTxt('portuguese'),
			'spanish'    => gTxt('spanish'),
			'finnish'    => gTxt('finnish'),
			'swedish'    => gTxt('swedish'),
			'russian'    => gTxt('russian'),
			'dutch'      => gTxt('dutch'),
			'danish'     => gTxt('danish'),
			'polish'     => gTxt('polish'),
			'tagalog'    => gTxt('tagalog'),
			'czech'      => gTxt('czech'),
			'scots'      => gTxt('scots')
		);
			asort($things);
			reset($things);

		return selectInput($item, $things, $var);
	}

// -------------------------------------------------------------
	function dateformats($item,$var) {
		global $timeoffset;
		$time = time()+$timeoffset;
		$dateformats = array(
			'M j, g:ia'    => date("M j, g:ia",$time),
			'j.m.y'        => date("j.m.y",$time),
			'j F, g:ia'    => date("j F, g:ia",$time),
			'y.m.d, g:ia'  => date("y.m.d, g:ia",$time),
			'g:ia'         => date("g:ia",$time),
			'D M jS, g:ia' => date("D M jS, g:ia",$time),
			'l, F j, Y'    => date("l, F j, Y",$time),
			'M jS'         => date("M jS",$time),
			'j F y'        => date("j F y",$time),
			'j m Y - H:i'  => date('j m Y - H:i',$time),
			'Y-m-d'        => date('Y-m-d',$time),
			'Y-d-m'        => date('Y-d-m',$time),
			'since'        => "hrs/days ago");
		return selectInput($item, $dateformats, $var);
	}

?>

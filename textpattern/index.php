<?php

/*
	This is Textpattern

	Copyright 2004 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/
	header("Content-type: text/html; charset=utf-8");

	$thisversion = '1.0rc1';

	require './config.php';
	$txpath = $txpcfg['txpath'];

	if (isset($_POST['preview'])) {
		include $txpath.'/publish.php';
		textpattern();
		exit;
	}

//	error_reporting(E_ALL);
//  ini_set("display_errors","1");

	include $txpath.'/lib/txplib_db.php';
	include $txpath.'/lib/txplib_forms.php';
	include $txpath.'/lib/txplib_html.php';
	include $txpath.'/lib/txplib_misc.php';
	include $txpath.'/lib/admin_config.php';

	$microstart = getmicrotime();

	$dbversion = safe_field('val','txp_prefs',"name = 'version'");
	
	extract(get_prefs());

#	define("LANG",$language);
	define("LANG","en-gb");
	define('txp_version', $thisversion);

	$textarray = load_lang(LANG);

	if (!$dbversion or $dbversion != $thisversion) {
		include_once '_update.php';
	}

	include $txpath.'/include/txp_auth.php';
	include $txpath.'/lib/txplib_head.php';

	$event = gps('event');
	$step = gps('step');

	include (!$event) 
	?	$txpath.'/include/txp_article.php'
	:	$txpath.'/include/txp_'.$event.'.php';

	$microdiff = (getmicrotime() - $microstart);
	echo "\n<!-- Runtime: ",substr($microdiff,0,6),"-->";

	end_page();
?>

<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/
	define("txpath", dirname(__FILE__));
	define("txpinterface", "admin");

	$thisversion = '1.0rc3';
	$txp_rc = 1; // should be 0 for a stable version

	if (!@include './config.php') { 
		include './setup.php';
		exit();
	}

	if (isset($_POST['preview'])) {
		include txpath.'/publish.php';
		textpattern();
		exit;
	}

// just a comment
//	error_reporting(E_ALL);
//  ini_set("display_errors","1");

	include txpath.'/lib/txplib_db.php';
	include txpath.'/lib/txplib_forms.php';
	include txpath.'/lib/txplib_html.php';
	include txpath.'/lib/txplib_misc.php';
	include txpath.'/lib/admin_config.php';

	$microstart = getmicrotime();

	 if ($connected && safe_query("describe ".PFX."textpattern")) {

		$dbversion = safe_field('val','txp_prefs',"name = 'version'");

		$prefs = get_prefs();
		extract($prefs);
	
		define("LANG",$language);
		//i18n: define("LANG","en-gb");
		define('txp_version', $thisversion);
		define("hu",'http://'.$siteurl);
	
		if (!empty($locale)) setlocale(LC_ALL, $locale);
		$textarray = load_lang(LANG);
	
		include txpath.'/include/txp_auth.php';
	
		$event = (gps('event') ? gps('event') : 'article');
		$step = gps('step');
		
		if (!$dbversion or $dbversion != $thisversion or 
				($txp_rc and @filemtime(txpath.'/_update.php') > $dbupdatetime)) {
			include './_update.php';
			$event = 'prefs';
			$step = 'prefs';
		}

		if ($txpac['admin_side_plugins'] and gps('event') != 'plugin')
			load_plugins(1);
		include txpath.'/lib/txplib_head.php';

		callback_event($event, $step, 1);

		$inc = txpath . '/include/txp_'.$event.'.php';
		if (is_readable($inc))
			include($inc);
	
		callback_event($event, $step, 0);

		$microdiff = (getmicrotime() - $microstart);
		echo n.comment(gTxt('runtime').': '.substr($microdiff,0,6));

		end_page();

	} else {
	 	include './setup.php';
	 	exit();
	}
?>

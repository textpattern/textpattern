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
	if (@ini_get('register_gobals'))
		foreach ( $_REQUEST as $name => $value )
			unset($$name);

	define("txpath", dirname(__FILE__));
	define("txpinterface", "admin");

	$thisversion = '4.0';
	$txp_rc = 1; // should be 0 for a stable version

	if (!@include './config.php') { 
		include txpath.'/setup/index.php';
		exit();
	}

	header("Content-type: text/html; charset=utf-8");
	if (isset($_POST['preview'])) {
		include txpath.'/publish.php';
		textpattern();
		exit;
	}
	
	error_reporting(E_ALL);
	@ini_set("display_errors","1");

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

		if (empty($siteurl))
			$siteurl = $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
		if (empty($path_to_site))
			updateSitePath(dirname(dirname(__FILE__)));
	
		define("LANG",$language);
		//i18n: define("LANG","en-gb");
		define('txp_version', $thisversion);
		define("hu",'http://'.$siteurl.'/');
		// v1.0 experimental relative url global
		define("rhu",preg_replace("/http:\/\/.+(\/.*)\/?$/U","$1",hu));

		if (!empty($locale)) setlocale(LC_ALL, $locale);
		$textarray = load_lang(LANG);
	
		include txpath.'/include/txp_auth.php';
		doAuth();

		$event = (gps('event') ? gps('event') : 'article');
		$step = gps('step');
		
		if (!$dbversion or $dbversion != $thisversion or 
				($txp_rc and @filemtime(txpath.'/update/_update.php') > $dbupdatetime)) {
			define('TXP_UPDATE', 1);
			include txpath.'/update/_update.php';
			$event = 'prefs';
			$step = 'list_languages';
			// We updated, so let's get the fresh prefs
			$prefs = get_prefs();
			extract($prefs);
		}

		if (!empty($admin_side_plugins) and gps('event') != 'plugin')
			load_plugins(1);

		include txpath.'/lib/txplib_head.php';

		// ugly hack, for the people that don't update their admin_config.php
		// Get rid of this when we completely remove admin_config and move privs to db
		if ($event == 'list') 		
			require_privs('article'); 
		else 
			require_privs($event);

		callback_event($event, $step, 1);

		$inc = txpath . '/include/txp_'.$event.'.php';
		if (is_readable($inc))
			include($inc);
	
		callback_event($event, $step, 0);

		$microdiff = (getmicrotime() - $microstart);
		echo n.comment(gTxt('runtime').': '.substr($microdiff,0,6));

		end_page();

	} else {
		include txpath.'/setup/index.php';
		exit();
	}
?>

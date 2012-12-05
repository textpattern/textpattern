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

	if (@ini_get('register_globals')) {
		if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
			die('GLOBALS overwrite attempt detected. Please consider turning register_globals off.');
		}

		// Collect and unset all registered variables from globals
		$_txpg = array_merge(
			isset($_SESSION) ? (array) $_SESSION : array(),
			(array) $_ENV,
			(array) $_GET,
			(array) $_POST,
			(array) $_COOKIE,
			(array) $_FILES,
			(array) $_SERVER);

		// As the deliberately awkward-named local variable $_txpfoo MUST NOT be unset to avoid notices further down
		// we must remove any potentially identical-named global from the list of global names here.
		unset($_txpg['_txpfoo']);
		foreach ($_txpg as $_txpfoo => $value) {
			if (!in_array($_txpfoo, array(
				'GLOBALS',
				'_SERVER',
				'_GET',
				'_POST',
				'_FILES',
				'_COOKIE',
				'_SESSION',
				'_REQUEST',
				'_ENV',
			))) {
				unset($GLOBALS[$_txpfoo], $$_txpfoo);
			}
		}
	}

	if (!defined('txpath'))
	{
		define("txpath", dirname(__FILE__));
	}

	define("txpinterface", "admin");

	$thisversion = '4.5.4';
	$txp_using_svn = false; // set false for releases

	ob_start(NULL, 2048);
	if (!isset($txpcfg['table_prefix']) && !@include './config.php') {
		ob_end_clean();
		header('HTTP/1.1 503 Service Unavailable');
		exit('config.php is missing or corrupt.  To install Textpattern, visit <a href="./setup/">setup</a>.');
	} else ob_end_clean();

	header("Content-type: text/html; charset=utf-8");

	// We need to violate/disable E_STRICT for PHP 4.x compatibility
	// E_STRICT bitmask calculation stems from the variations for E_ALL in PHP 4.x, 5.3, and 5.4
	error_reporting(E_ALL & ~(defined('E_STRICT') ? E_STRICT : 0));
	@ini_set("display_errors","1");

	include_once txpath.'/lib/constants.php';
	include txpath.'/lib/txplib_misc.php';
	include txpath.'/lib/txplib_db.php';
	include txpath.'/lib/txplib_forms.php';
	include txpath.'/lib/txplib_html.php';
	include txpath.'/lib/txplib_theme.php';
	include txpath.'/lib/txplib_validator.php';
	include txpath.'/lib/admin_config.php';

	set_error_handler('adminErrorHandler', error_reporting());
	$microstart = getmicrotime();

	 if ($connected && safe_query("describe `".PFX."textpattern`")) {

		$dbversion = safe_field('val','txp_prefs',"name = 'version'");

		// global site prefs
		$prefs = get_prefs();
		extract($prefs);

		if (empty($siteurl)) {
			$httphost = preg_replace('/[^-_a-zA-Z0-9.:]/', '', $_SERVER['HTTP_HOST']);
			$prefs['siteurl'] = $siteurl = $httphost . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
		}
		if (empty($path_to_site))
			updateSitePath(dirname(dirname(__FILE__)));

		define("LANG",$language);
		//i18n: define("LANG","en-gb");
		define('txp_version', $thisversion);

		if (!defined('PROTOCOL')) {
			switch (serverSet('HTTPS')) {
				case '':
				case 'off': // ISAPI with IIS
					define('PROTOCOL', 'http://');
				break;

				default:
					define('PROTOCOL', 'https://');
				break;
			}
		}

		define('hu', PROTOCOL.$siteurl.'/');
		// relative url global
		define('rhu', preg_replace('|^https?://[^/]+|', '', hu));
		// http address of the site serving images
		if (!defined('ihu')) define('ihu', hu);

		if (!empty($locale)) setlocale(LC_ALL, $locale);
		$textarray = load_lang(LANG);

		// init global theme
		$theme = theme::init();

		include txpath.'/include/txp_auth.php';
		doAuth();

		// once more for global plus private prefs
		$prefs = get_prefs();
		extract($prefs);

		$event = (gps('event') ? trim(gps('event')) : (!empty($default_event) && has_privs($default_event) ? $default_event : 'article'));
		$step = trim(gps('step'));
		$app_mode = trim(gps('app_mode'));

		if (!$dbversion or ($dbversion != $thisversion) or $txp_using_svn)
		{
			define('TXP_UPDATE', 1);
			include txpath.'/update/_update.php';
		}

		janitor();

		// article or form preview
		if (isset($_POST['form_preview']) || isset($_GET['txpreview'])) {
			include txpath.'/publish.php';
			textpattern();
			exit;
		}

		if (!empty($admin_side_plugins) and gps('event') != 'plugin')
			load_plugins(1);

		// plugins may have altered privilege settings
		if (!defined('TXP_UPDATE_DONE') && !gps('event') && !empty($default_event) && has_privs($default_event))
		{
			 $event = $default_event;
		}

		// init private theme
		$theme = theme::init();

		include txpath.'/lib/txplib_head.php';

		require_privs($event);
		callback_event($event, $step, 1);
		$inc = txpath . '/include/txp_'.$event.'.php';
		if (is_readable($inc))
			include($inc);
		callback_event($event, $step, 0);

		end_page();

		$microdiff = substr(getmicrotime() - $microstart,0,6);
		$memory_peak = is_callable('memory_get_peak_usage') ? ceil(memory_get_peak_usage(true)/1024) : '-';

		if ($app_mode != 'async') {
			echo n.comment(gTxt('runtime').': '.$microdiff);
			echo n.comment(sprintf('Memory: %sKb', $memory_peak));
		} else {
			header("X-Textpattern-Runtime: $microdiff");
			header("X-Textpattern-Memory: $memory_peak");
		}
	} else {
		txp_die('DB-Connect was successful, but the textpattern-table was not found.',
				'503 Service Unavailable');
	}
?>

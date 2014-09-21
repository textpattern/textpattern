<?php
/*
$HeadURL: https://textpattern.googlecode.com/svn/development/4.x/sites/site1/public/index.php $
$LastChangedRevision: 3238 $
*/

	// Make sure we display all errors that occur during initialization
	error_reporting(E_ALL);
	@ini_set("display_errors","1");

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

	define("txpinterface", "public");

	// save server path to site root
	if (!isset($here))
	{
		$here = dirname(__FILE__);
	}

	// pull in config unless configuration data has already been provided (multi-headed use).
	if (!isset($txpcfg['table_prefix']))
	{
		// Use buffering to ensure bogus whitespace in config.php is ignored
		ob_start(NULL, 2048);
		include '../private/config.php';
		ob_end_clean();
	}

	if (!defined('txpath'))
	{
		define("txpath", realpath(dirname(__FILE__).'/../../../textpattern'));
	}

	include txpath.'/lib/constants.php';
	include txpath.'/lib/txplib_misc.php';
	if (!isset($txpcfg['table_prefix']))
	{
		txp_status_header('503 Service Unavailable');
		exit('config.php is missing or corrupt.  To install Textpattern, visit <a href="./setup/">textpattern/setup/</a>');
	}

	// custom caches et cetera?
	if (isset($txpcfg['pre_publish_script']))
	{
		require $txpcfg['pre_publish_script'];
	}

	include txpath.'/publish.php';
	textpattern();

?>

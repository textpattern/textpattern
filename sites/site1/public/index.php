<?php
/*
$HeadURL: https://textpattern.googlecode.com/svn/development/4.x/sites/site1/public/index.php $
$LastChangedRevision: 3238 $
*/

	// Make sure we display all errors that occur during initialization
	error_reporting(E_ALL);
	@ini_set("display_errors","1");

	if (@ini_get('register_globals'))
		foreach ( $_REQUEST as $name => $value )
			unset($$name);
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

	include txpath.'/publish.php';
	textpattern();

?>

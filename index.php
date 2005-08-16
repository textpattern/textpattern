<?php
/*
$HeadURL$
$LastChangedRevision$
*/

	// Make sure we display all errors that occur during initialization
	error_reporting(E_ALL);
	@ini_set("display_errors","1");

	if (@ini_get('register_globals'))
		foreach ( $_REQUEST as $name => $value )
			unset($$name);
	define("txpinterface", "public");

	// Use buffering to ensure bogus whitespace in config.php is ignored
	ob_start(NULL, 2048);
	$here = dirname(__FILE__);
	include './textpattern/config.php';
	ob_end_clean();

	if (!isset($txpcfg['txpath']) )	{ 
		header('Status: 503 Service Unavailable'); header('HTTP/1.0 503 Service Unavailable');
		exit('config.php is not ok or not found. If you would like to install, go to [/subdir]/textpattern/setup/'); 
	}

	include $txpcfg['txpath'].'/publish.php';
	textpattern();
?>

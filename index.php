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
	if (!defined('txpath'))
		define("txpath", dirname(__FILE__).'/textpattern');

	// Use buffering to ensure bogus whitespace in config.php is ignored
	ob_start(NULL, 2048);
	$here = dirname(__FILE__);
	include txpath.'/config.php';
	ob_end_clean();

	include txpath.'/lib/constants.php';
	if (!isset($txpcfg['txpath']) )	{
		$status = '503 Service Unavailable';
		if (IS_FASTCGI)
			header("Status: $status");
		elseif ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0')
			header("HTTP/1.0 $status");
		else
			header("HTTP/1.1 $status");

		$msg = 'config.php is missing or corrupt.  To install Textpattern, visit <a href="./textpattern/setup/">textpattern/setup/</a>';
		exit ($msg);
	}

	include txpath.'/publish.php';
	textpattern();

?>
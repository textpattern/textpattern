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
		$status = '503 Service Unavailable';
		if (substr(php_sapi_name(), 0, 3) == 'cgi' and empty($_SERVER['FCGI_ROLE']) and empty($_ENV['FCGI_ROLE']))
			header("Status: $status");
		else
			header("HTTP/1.1 $status");

		$msg = 'config.php is missing or corrupt.  To install Textpattern, visit <a href="./textpattern/setup/">textpattern/setup/</a>';
		exit ($msg);
	}

	include $txpcfg['txpath'].'/publish.php';
	textpattern();
?>

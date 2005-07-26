<?php
/*
$HeadURL$
$LastChangedRevision$
*/

	// Make sure we display all errors that occur during initialization
	error_reporting(E_ALL);
	ini_set("display_errors","1");

	// Use buffering to ensure bogus whitespace in config.php is ignored
	ob_start();
	$here = dirname(__FILE__);
	include './textpattern/config.php';
	ob_end_clean();

	include $txpcfg['txpath'].'/publish.php';
	textpattern();
?>

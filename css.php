<?php
/*
$HeadURL$
$LastChangedRevision$
*/

if (@ini_get('register_globals')) {
	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
		die('GLOBALS overwrite attempt detected. Please consider turning register_globals off.');
	}

	foreach (
		array_merge(
			isset($_SESSION) ? (array) $_SESSION : array(),
			(array) $_ENV,
			(array) $_GET,
			(array) $_POST,
			(array) $_COOKIE,
			(array) $_FILES,
			(array) $_SERVER
		) as $name => $value
	) {
		if (!in_array($name, array(
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
			unset($GLOBALS[$name], $$name);
		}
	}
}

header('Content-type: text/css');

if (!defined("txpath"))
{
	define("txpath", dirname(__FILE__).'/textpattern');
}

if (!isset($txpcfg['table_prefix']))
{
	ob_start(NULL, 2048);
	include txpath.'/config.php';
	ob_end_clean();
}

$nolog = 1;
define("txpinterface", "css");
include txpath.'/publish.php';
$s = gps('s');
$n = gps('n');
output_css($s,$n);
?>


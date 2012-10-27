<?php

/**
 * Outputs CSS files.
 *
 * @since 4.2.0
 */

if (@ini_get('register_globals'))
{
	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
	{
		die('GLOBALS overwrite attempt detected. Please consider turning register_globals off.');
	}

	// Collect and unset all registered variables from globals.
	$_txpg = array_merge(
		isset($_SESSION) ? (array) $_SESSION : array(),
		(array) $_ENV,
		(array) $_GET,
		(array) $_POST,
		(array) $_COOKIE,
		(array) $_FILES,
		(array) $_SERVER);

	// As the deliberate awkwardly-named local variable $_txpfoo MUST NOT be unset to avoid notices further
	// down, we must remove any potential identically-named global from the list of global names here.
	unset($_txpg['_txpfoo']);
	foreach ($_txpg as $_txpfoo => $value)
	{
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
		)))
		{
			unset($GLOBALS[$_txpfoo], $$_txpfoo);
		}
	}
}

header('Content-type: text/css');

if (!defined("txpath"))
{
	/**
	 * @ignore
	 */

	define("txpath", dirname(__FILE__).'/textpattern');
}

if (!isset($txpcfg['table_prefix']))
{
	ob_start(NULL, 2048);
	include txpath.'/config.php';
	ob_end_clean();
}

$nolog = 1;

/**
 * @ignore
 */

define("txpinterface", "css");
include txpath.'/publish.php';
$s = gps('s');
$n = gps('n');
output_css($s, $n);

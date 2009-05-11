<?php
/*
$HeadURL$
$LastChangedRevision$
*/

if (@ini_get('register_globals'))
	foreach ( $_REQUEST as $name => $value )
		unset($$name);

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


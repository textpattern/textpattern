<?php
/*
$HeadURL$
$LastChangedRevision$
*/

if (@ini_get('register_globals'))
	foreach ( $_REQUEST as $name => $value )
		unset($$name);

header('Content-type: text/css');

ob_start(NULL, 2048);
include './config.php';
ob_end_clean();

$nolog = 1;
define("txpinterface", "css");
include $txpcfg['txpath'].'/publish.php';
$s = gps('s');
$n = gps('n');
output_css($s,$n);
?>

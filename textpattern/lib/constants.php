<?php
/*
$HeadURL: $
$LastChangedRevision: $
*/

$old_level = error_reporting(E_ALL ^ (E_NOTICE));

define('TXP_DEBUG', 0);

define('SPAM', -1);
define('MODERATE', 0);
define('VISIBLE', 1);
define('RELOAD', -99);

define('RPC_SERVER', 'http://rpc.textpattern.com');

define('LEAVE_TEXT_UNTOUCHED', 0);
define('USE_TEXTILE', 1);
define('CONVERT_LINEBREAKS', 2);

if (defined('DIRECTORY_SEPARATOR'))
	define('DS', DIRECTORY_SEPARATOR);
else
	define ('DS', (is_windows() ? '\\' : '/'));


error_reporting($old_level);unset($old_level);
?>
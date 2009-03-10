<?php
/*
$HeadURL$
$LastChangedRevision$
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

define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

define('REGEXP_UTF8', @preg_match('@\pL@u', 'q'));
define('NULLDATETIME', '\'0000-00-00 00:00:00\'');

define('PERMLINKURL', 0);
define('PAGELINKURL', 1);

define('EXTRA_MEMORY', '32M');

define('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi' );
define('IS_FASTCGI', IS_CGI and empty($_SERVER['FCGI_ROLE']) and empty($_ENV['FCGI_ROLE']) );
define('IS_APACHE', !IS_CGI and substr(PHP_SAPI, 0, 6) == 'apache' );

define('PREF_PRIVATE', true);
define('PREF_GLOBAL', false);
define('PREF_BASIC', 0);
define('PREF_ADVANCED', 1);
define('PREF_HIDDEN', 2);

define('PLUGIN_HAS_PREFS', 0x0001);
define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);
define('PLUGIN_RESERVED_FLAGS', 0x0fff); // reserved bits for use by Textpattern core

error_reporting($old_level);
unset($old_level);

?>

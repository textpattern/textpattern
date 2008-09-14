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

define('EXTRA_MEMORY', '32M');

define('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi' );
define('IS_FASTCGI', IS_CGI and empty($_SERVER['FCGI_ROLE']) and empty($_ENV['FCGI_ROLE']) );
define('IS_APACHE', !IS_CGI and substr(PHP_SAPI, 0, 6) == 'apache' );

error_reporting($old_level);
unset($old_level);

?>
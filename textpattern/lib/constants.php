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
defined('HELP_URL') || define('HELP_URL', 'http://rpc.textpattern.com/help/');

define('LEAVE_TEXT_UNTOUCHED', 0);
define('USE_TEXTILE', 1);
define('CONVERT_LINEBREAKS', 2);
define('IS_WIN', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

if (defined('DIRECTORY_SEPARATOR'))
	define('DS', DIRECTORY_SEPARATOR);
else
	define ('DS', (IS_WIN ? '\\' : '/'));

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

define('PASSWORD_LENGTH', 10); // password default length, in characters
define('PASSWORD_COMPLEXITY', 8); // log(2) of stretching iteration count
define('PASSWORD_PORTABILITY', TRUE);
defined('LOGIN_COOKIE_HTTP_ONLY') || define('LOGIN_COOKIE_HTTP_ONLY', true);
defined('X_FRAME_OPTIONS') || define('X_FRAME_OPTIONS', 'SAMEORIGIN');

defined('AJAX_TIMEOUT') || define('AJAX_TIMEOUT', max(30000, 1000 * @ini_get('max_execution_time')));
defined('AJAXALLY_CHALLENGED') || define('AJAXALLY_CHALLENGED', false); // TODO: @since 4.5.0; Wait for plugins to catch up, then lose this at a future release

define('PARTIAL_STATIC', 0);		// render on initial synchronous page load
define('PARTIAL_VOLATILE', 1);		// render as HTML partial on every page load
define('PARTIAL_VOLATILE_VALUE', 2);// render as an element's jQuery.val() on every page load

define('STATUS_DRAFT', 1);
define('STATUS_HIDDEN', 2);
define('STATUS_PENDING', 3);
define('STATUS_LIVE', 4);
define('STATUS_STICKY', 5);

define('INPUT_XLARGE', 96);
define('INPUT_LARGE', 64);
define('INPUT_REGULAR', 32);
define('INPUT_MEDIUM', 16);
define('INPUT_SMALL', 8);
define('INPUT_XSMALL', 4);
define('INPUT_TINY', 2);

define('REQUIRED_PHP_VERSION', '5.2');

error_reporting($old_level);
unset($old_level);
?>

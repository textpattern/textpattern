<?php
/*
XML-RPC Server for Textpattern 4.0.x
http://txp.kusor.com/rpc-api
(C)2005-2006 The Textpattern Development Team - http://textpattern.com
@author Pedro Palazón - http://kusor.com
$HeadURL$
$LastChangedRevision$
*/

#TODO: change error reporting to E_ALL, including E_NOTICE to detect subtle bugs?
error_reporting(E_ALL & ~E_NOTICE);
#TODO: if display_errors is set to 0... who will ever see errors?
ini_set("display_errors","0");

if (@ini_get('register_globals'))
	foreach ( $_REQUEST as $name => $value )
		unset($$name);

define('txpath', dirname(dirname(__FILE__)).'/textpattern');
define('txpinterface','xmlrpc');

require_once txpath.'/config.php';
require_once txpath.'/lib/txplib_db.php';
require_once txpath.'/lib/txplib_misc.php';
require_once txpath.'/lib/admin_config.php';
require_once txpath.'/lib/IXRClass.php';

if ($connected && safe_query("describe `".PFX."textpattern`"))
{
#TODO: where is dbversion used?
	$dbversion = safe_field('val','txp_prefs',"name = 'version'");

	// Hold it globally, instead of do several calls to the function
	$prefs = get_prefs();
	extract($prefs);

	if (!defined('LANG')) define("LANG", $language);
	if (!defined('hu')) define("hu", 'http://'.$siteurl.'/');
	if (!defined('txrpcpath')) define('txrpcpath', hu.'rpc/');

	if (!empty($locale)) setlocale(LC_ALL, $locale);
	$textarray = load_lang(LANG);

#TODO: include txplib_html instead of duplicating?
	// from txplib_html.php
	if (!defined('t'))  define("t", "\t");
	if (!defined('n'))  define("n", "\n");
	if (!defined('br')) define("br", "<br />");
	if (!defined('sp')) define("sp", "&#160;");
	if (!defined('a'))  define("a", "&#38;");
}

require_once txpath.'/lib/txplib_wrapper.php';
require_once 'TXP_RPCServer.php';

// run the XML-RPC Server
$server = new TXP_RPCServer();
$server->serve();

#TODO: remove before official release?
// save some debug logs:
function write_log()
{
	global $HTTP_RAW_POST_DATA;

	if (!defined('txpdmpfile')) define('txpdmpfile', 'txpxmlrpc.txt');

	$fp = @fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'xmlrpclog','a');

	if ($fp)
	{
		$lnsep = "\n================================\n";
		fwrite($fp, "\n$lnsep".strftime("%Y-%m-%d %H:%M:%S"));
		fwrite($fp, '[USER_AGENT] '.$_SERVER['HTTP_USER_AGENT']);
		fwrite($fp, $lnsep);
		fwrite($fp, '[ACCEPT_ENCODING] '.$_SERVER['HTTP_ACCEPT_ENCODING']);

		if (strpos(strtolower($_SERVER['SERVER_SOFTWARE']),'apache')!==false && is_callable('getallheaders'))
		{
			fwrite($fp, $lnsep);
			fwrite($fp, "Apache Request Headers:\n");
			fwrite($fp, $lnsep);
			$headers = getallheaders();

			foreach ($headers as $header => $value)
			{
				fwrite($fp, "$header: $value \n");
			}
		}

		fwrite($fp, $lnsep);
		fwrite($fp,"Incoming data, usually utf-8 encoded:\n");
		fwrite($fp, $lnsep);
		fwrite($fp, $HTTP_RAW_POST_DATA);
		fclose($fp);
	}
}

?>

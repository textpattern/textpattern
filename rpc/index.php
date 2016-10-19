<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * XML-RPC Server for Textpattern 4.0.x
 * http://web.archive.org/web/20150119065246/http://txp.kusor.com/rpc-api
 *
 * Copyright (C) 2005-2006, 2016 The Textpattern Development Team
 * Author: Pedro Palazón
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

// TODO: change error reporting to E_ALL, including E_NOTICE to detect subtle bugs?
error_reporting(E_ALL & ~E_NOTICE);

// TODO: if display_errors is set to 0... who will ever see errors?
ini_set("display_errors", "0");

if (@ini_get('register_globals')) {
    if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
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

    // As the deliberate awkwardly-named local variable $_txpfoo MUST NOT be
    // unset to avoid notices further down, we must remove any potential
    // identically-named global from the list of global names here.
    unset($_txpg['_txpfoo']);

    foreach ($_txpg as $_txpfoo => $value) {
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
        ))) {
            unset($GLOBALS[$_txpfoo], $$_txpfoo);
        }
    }
}

define('txpath', dirname(dirname(__FILE__)).'/textpattern');
define('txpinterface', 'xmlrpc');

require_once txpath.'/config.php';
require_once txpath.'/lib/txplib_db.php';
require_once txpath.'/lib/txplib_misc.php';
require_once txpath.'/lib/admin_config.php';
require_once txpath.'/lib/IXRClass.php';
require_once txpath.'/lib/class.trace.php';

$trace = new Trace();

require_once txpath.'/vendors/Textpattern/Loader.php';

$loader = new \Textpattern\Loader(txpath.'/vendors');
$loader->register();

$loader = new \Textpattern\Loader(txpath.'/lib');
$loader->register();


if ($connected && numRows(safe_query("show tables like '".PFX."textpattern'"))) {
    // TODO: where is dbversion used?
    $dbversion = safe_field('val', 'txp_prefs', "name = 'version'");

    // Hold it globally, instead of do several calls to the function.
    $prefs = get_prefs();
    extract($prefs);

    if (!defined('LANG')) {
        define("LANG", $language);
    }

    if (!defined('hu')) {
        define("hu", 'http://'.$siteurl.'/');
    }

    if (!defined('txrpcpath')) {
        define('txrpcpath', hu.'rpc/');
    }

    if (!empty($locale)) {
        setlocale(LC_ALL, $locale);
    }

    $textarray = load_lang(LANG);

// TODO: include txplib_html instead of duplicating?
    // From txplib_html.php.
    if (!defined('t')) {
        define("t", "\t");
    }

    if (!defined('n')) {
        define("n", "\n");
    }

    if (!defined('br')) {
        define("br", "<br />");
    }

    if (!defined('sp')) {
        define("sp", "&#160;");
    }

    if (!defined('a')) {
        define("a", "&#38;");
    }
}

require_once txpath.'/lib/txplib_wrapper.php';
require_once 'TXP_RPCServer.php';

// Run the XML-RPC server.
$server = new TXP_RPCServer();
$server->serve();

// TODO: remove before official release?
// Save some debug logs.
function write_log()
{
    global $HTTP_RAW_POST_DATA;

    if (!defined('txpdmpfile')) {
        define('txpdmpfile', 'txpxmlrpc.txt');
    }

    $fp = @fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'xmlrpclog', 'a');

    if ($fp) {
        $lnsep = "\n================================\n";
        fwrite($fp, "\n$lnsep".strftime("%Y-%m-%d %H:%M:%S"));
        fwrite($fp, '[USER_AGENT] '.$_SERVER['HTTP_USER_AGENT']);
        fwrite($fp, $lnsep);
        fwrite($fp, '[ACCEPT_ENCODING] '.$_SERVER['HTTP_ACCEPT_ENCODING']);

        if (strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'apache') !== false && is_callable('getallheaders')) {
            fwrite($fp, $lnsep);
            fwrite($fp, "Apache Request Headers:\n");
            fwrite($fp, $lnsep);
            $headers = getallheaders();

            foreach ($headers as $header => $value) {
                fwrite($fp, "$header: $value \n");
            }
        }

        fwrite($fp, $lnsep);
        fwrite($fp, "Incoming data, usually utf-8 encoded:\n");
        fwrite($fp, $lnsep);
        fwrite($fp, $HTTP_RAW_POST_DATA);
        fclose($fp);
    }
}

<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2016 The Textpattern Development Team
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

    // As the deliberately awkward-named local variable $_txpfoo MUST NOT be unset to avoid notices further
    // down, we must remove any potentially identical-named global from the list of global names here.
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

if (!defined('txpath')) {
    define("txpath", dirname(__FILE__));
}

define("txpinterface", "admin");

$thisversion = '4.6.2';
$txp_using_svn = false; // Set false for releases.

ob_start(null, 2048);

if (!isset($txpcfg['table_prefix']) && !@include './config.php') {
    ob_end_clean();
    header('HTTP/1.1 503 Service Unavailable');
    exit('config.php is missing or corrupt. To install Textpattern, visit <a href="./setup/">setup</a>.');
} else {
    ob_end_clean();
}

header("Content-type: text/html; charset=utf-8");

error_reporting(E_ALL | E_STRICT);
@ini_set("display_errors", "1");
include txpath.'/lib/class.trace.php';
$trace = new Trace();
$trace->start('[PHP includes]');
include_once txpath.'/lib/constants.php';
include txpath.'/lib/txplib_misc.php';

include txpath.'/vendors/Textpattern/Loader.php';

$loader = new \Textpattern\Loader(txpath.'/vendors');
$loader->register();

$loader = new \Textpattern\Loader(txpath.'/lib');
$loader->register();

include txpath.'/lib/txplib_db.php';
include txpath.'/lib/txplib_forms.php';
include txpath.'/lib/txplib_html.php';
include txpath.'/lib/admin_config.php';
$trace->stop();

set_error_handler('adminErrorHandler', error_reporting());

if ($connected && numRows(safe_query("SHOW TABLES LIKE '".PFX."textpattern'"))) {
    // Global site preferences.
    $prefs = get_prefs();
    extract($prefs);

    $dbversion = $version;

    if (empty($siteurl)) {
        $httphost = preg_replace('/[^-_a-zA-Z0-9.:]/', '', $_SERVER['HTTP_HOST']);
        $prefs['siteurl'] = $siteurl = $httphost.rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), DS);
    }

    if (empty($path_to_site)) {
        updateSitePath(dirname(dirname(__FILE__)));
    }

    define("LANG", $language);
    define('txp_version', $thisversion);

    if (!defined('PROTOCOL')) {
        switch (serverSet('HTTPS')) {
            case '':
            case 'off': // ISAPI with IIS.
                define('PROTOCOL', 'http://');
                break;
            default:
                define('PROTOCOL', 'https://');
                break;
        }
    }

    define('hu', PROTOCOL.$siteurl.'/');

    // Relative URL global.
    define('rhu', preg_replace('|^https?://[^/]+|', '', hu));

    // HTTP address of the site serving images.
    if (!defined('ihu')) {
        define('ihu', hu);
    }

    if (!empty($locale)) {
        setlocale(LC_ALL, $locale);
    }

    $textarray = load_lang(LANG);

    // Initialise global theme.
    $theme = \Textpattern\Admin\Theme::init();

    include txpath.'/include/txp_auth.php';
    doAuth();

    // Add private preferences.
    $prefs = array_merge(get_prefs($txp_user), $prefs);
    extract($prefs);

    /**
     * @ignore
     */

    define('SITE_HOST', (string) @parse_url(hu, PHP_URL_HOST));

    /**
     * @ignore
     */

    define('IMPATH', $path_to_site.DS.$img_dir.DS);

    $event = (gps('event') ? trim(gps('event')) : (!empty($default_event) && has_privs($default_event) ? $default_event : 'article'));
    $step = trim(gps('step'));
    $app_mode = trim(gps('app_mode'));

    if (!$dbversion or ($dbversion != $thisversion) or $txp_using_svn) {
        define('TXP_UPDATE', 1);
        include txpath.'/update/_update.php';
    }

    janitor();

    // Article or form preview.
    if (isset($_GET['txpreview'])) {
        include txpath.'/publish.php';
        textpattern();
        exit;
    }

    if (!empty($admin_side_plugins) and gps('event') != 'plugin') {
        load_plugins(1);
    }

    // Plugins may have altered privilege settings.
    if (!defined('TXP_UPDATE_DONE') && !gps('event') && !empty($default_event) && has_privs($default_event)) {
        $event = $default_event;
    }

    // Initialise private theme.
    $theme = \Textpattern\Admin\Theme::init();

    include txpath.'/lib/txplib_head.php';

    require_privs($event);
    callback_event($event, $step, 1);
    $inc = txpath.'/include/txp_'.$event.'.php';

    if (is_readable($inc)) {
        include($inc);
    }

    callback_event($event, $step, 0);

    end_page();

    if ($app_mode != 'async') {
        echo $trace->summary();
        echo $trace->result();
    } else {
        foreach ($trace->summary(true) as $key => $value) {
            header('X-Textpattern-'.preg_replace('/[^\w]+/', '', $key).': '.$value);
        }
    }
} else {
    txp_die('Database connection was successful, but the <code>textpattern</code> table was not found.',
        '503 Service Unavailable');
}

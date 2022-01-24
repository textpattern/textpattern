<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('txpath')) {
    define("txpath", dirname(__FILE__));
}

define("txpinterface", "admin");

$thisversion = '4.8.8';
// $txp_using_svn deprecated in 4.7.0.
$txp_using_svn = $txp_is_dev = false; // Set false for releases.

ob_start(null, 2048);

if (!isset($txpcfg['table_prefix']) && !@include './config.php') {
    ob_end_clean();
    header('HTTP/1.1 503 Service Unavailable');
    exit('<p>config.php is missing or corrupt. To install Textpattern, visit <a href="./setup/">setup</a>.</p>');
} else {
    ob_end_clean();
}

header("Content-Type: text/html; charset=utf-8");

error_reporting(E_ALL | E_STRICT);
@ini_set("display_errors", "1");
include txpath.'/lib/class.trace.php';
$trace = new Trace();
$trace->start('[PHP includes]');
include_once txpath.'/lib/constants.php';
include txpath.'/lib/txplib_misc.php';
include txpath.'/lib/txplib_admin.php';

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

    if (empty($siteurl)) {
        $httphost = preg_replace('/[^-_a-zA-Z0-9.:]/', '', $_SERVER['HTTP_HOST']);
        $prefs['siteurl'] = $siteurl = $httphost.rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), DS);
    }

    if (empty($path_to_site)) {
        updateSitePath(dirname(dirname(__FILE__)));
    }

    define('TXP_PATTERN', get_pref('enable_short_tags', false) ? 'txp|[a-z]+:' : 'txp:?');
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

    // HTTP address of Textpattern admin URL.
    if (!defined('ahu')) {
        if (empty($txpcfg['admin_url'])) {
            $adminurl = hu.'textpattern/';
        } else {
            $adminurl = PROTOCOL.rtrim(preg_replace('|^https?://|', '', $txpcfg['admin_url']), '/').'/';
        }

        define('ahu', $adminurl);
    }

    // Shared admin and public cookie_domain when using multisite admin URL (use main domain if not set).
    if (!defined('cookie_domain')) {
        if (!isset($txpcfg['cookie_domain'])) {
            if (empty($txpcfg['admin_url'])) {
                $txpcfg['cookie_domain'] = '';
            } else {
                $txpcfg['cookie_domain'] = rtrim(substr($txpcfg['admin_url'], strpos($txpcfg['admin_url'], '.') + 1), '/');
            }
        }

        define('cookie_domain', $txpcfg['cookie_domain']);
    }

    if (!empty($locale)) {
        setlocale(LC_ALL, $locale);
    }

    // For backwards-compatibility (sort of) with plugins that expect the
    // $textarray global to be present.
    // Will remove in future.
    $textarray = array();

    //load_lang(LANG, 'admin');

    // Initialise global theme.
    $theme = \Textpattern\Admin\Theme::init();

    include txpath.'/include/txp_auth.php';
    doAuth();

    // Add private preferences.
    $prefs += get_prefs($txp_user);
    plug_privs();
    extract($prefs);

    $dbversion = $version;

    $event = (gps('event') ? trim(gps('event')) : (!empty($default_event) && has_privs($default_event) ? $default_event : 'article'));
    $step = trim(gps('step'));
    $app_mode = trim(gps('app_mode'));

    /**
     * @ignore
     */

    define('SITE_HOST', (string) @parse_url(hu, PHP_URL_HOST));

    /**
     * @ignore
     */

    define('IMPATH', $path_to_site.DS.$img_dir.DS);

    if (!$dbversion || ($dbversion != $thisversion) || $txp_is_dev) {
        define('TXP_UPDATE', 1);
        include txpath.'/update/_update.php';
    }

    janitor();

    // Article or form preview.
    if (isset($_GET['txpreview'])) {
        load_lang(LANG, 'public');
        include txpath.'/publish.php';
        textpattern();
        echo $trace->summary();

        if ($production_status === 'debug') {
            echo $trace->result();
        }

        exit;
    }

    $txp_sections = safe_column(array('name'), 'txp_section', '1 ORDER BY title, name');
    $timezone_key = get_pref('timezone_key', date_default_timezone_get()) or $timezone_key = 'UTC';
    date_default_timezone_set($timezone_key);

    // Reload string pack using per-user language.
    $lang_ui = (empty($language_ui)) ? $language : $language_ui;
    load_lang($lang_ui, $event);

    if ($lang_ui != $language) {
        Txp::get('\Textpattern\L10n\Locale')->setLocale(LC_ALL, $lang_ui);
    }

    // Register modules
    register_callback('\Textpattern\Module\Help\HelpAdmin::init', 'help');

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

    if ($production_status !== 'live') {
        if ($app_mode != 'async') {
            echo $trace->summary();

            if ($production_status === 'debug') {
                echo $trace->result();
            }
        } else {
            foreach ($trace->summary(true) as $key => $value) {
                header('X-Textpattern-'.preg_replace('/[^\w]+/', '', $key).': '.$value);
            }
        }
    }
} else {
    txp_die(
        'Database connection was successful, but the <code>textpattern</code> table was not found.',
        '503 Service Unavailable'
    );
}

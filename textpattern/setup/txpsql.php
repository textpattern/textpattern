<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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

if (!defined('TXP_INSTALL')) {
    exit;
}

@ignore_user_abort(1);
@set_time_limit(0);

global $DB, $prefs, $txp_user, $txp_groups;
global $permlink_mode, $siteurl, $blog_uid, $theme_name;
include txpath.'/lib/txplib_db.php';
include txpath.'/lib/admin_config.php';

$siteurl = rtrim($_SESSION['siteurl'], '/');
if (! preg_match('%^https?://%', $siteurl)) {
    $siteurl = 'http://'.$siteurl;
}

// Determining the mode of permanent links
ini_set('default_socket_timeout', 10);
$s = md5(uniqid(rand(), true));
$pretext_data = @file("{$siteurl}/{$s}/?txpcleantest=1");
if (trim(@$pretext_data[0]) == md5("/{$s}/?txpcleantest=1")) {
    $permlink_mode = 'section_title';
} else {
    $permlink_mode = 'messy';
}

// Variable set
$blog_uid = md5(uniqid(rand(), true));
$siteurl = preg_replace('%^https?://%', '', $siteurl);
$siteurl = str_replace(' ', '%20', $siteurl);
$theme_name = $_SESSION['theme'] ? $_SESSION['theme'] : 'hive';
$themedir = txpath.DS.'setup';

if (numRows(safe_query("SHOW TABLES LIKE '".PFX."textpattern'"))) {
    die("Textpattern database table already exists. Can't run setup.");
}

$setup = new \Textpattern\DB\Core();

// Create tables
$setup->createAllTables();

// Initial mandatory data
$setup->initData();


setup_txp_lang();

// Create core prefs
$setup->initPrefs();

$prefs = get_prefs();
$txp_user = $_SESSION['name'];

create_user($txp_user, $_SESSION['email'], $_SESSION['pass'], $_SESSION['realname'], 1);



// --- Theme setup
// Load theme /data, /styles, /forms, /pages

foreach (get_files_content($themedir.'/styles', 'css') as $key=>$data) {
    safe_query("INSERT INTO `".PFX."txp_css`(name, css) VALUES('".doSlash($key)."', '".doSlash($data)."')");
}

foreach (get_files_content($themedir.'/forms', 'txp') as $key=>$data) {
    list($type, $name) = explode('.', $key);
    safe_query("INSERT INTO `".PFX."txp_form`(type, name, Form) VALUES('".doSlash($type)."', '".doSlash($name)."', '".doSlash($data)."')");
}

foreach (get_files_content($themedir.'/pages', 'txp') as $key=>$data) {
    safe_query("INSERT INTO `".PFX."txp_page`(name, user_html) VALUES('".doSlash($key)."', '".doSlash($data)."')");
}


/*  Load theme prefs:
        /data/core.prefs    - Allow override some core prefs. Used only in setup theme.
        /data/theme.prefs   - Theme global and private prefs.
                                global  - Used in setup and for AutoCreate missing prefs.
                                private - Will be created after user login
*/
foreach (get_files_content($themedir.'/data', 'prefs') as $key=>$data) {
    if ($out = @json_decode($data, true)) {
        foreach ($out as $name => $p) {
            if (empty($p['private'])) {
                @set_pref($name, $p['val'], $p['event'], $p['type'], $p['html'], $p['position']);
            }
        }
    }
}


$import = new \Textpattern\Import\TxpXML();

foreach (get_files_content($themedir.'/data', 'xml') as $key=>$data) {
    $import->importXml($data);
}

foreach (get_files_content($themedir.'/articles', 'xml') as $key=>$data) {
    $import->importXml($data);
}

// --- Theme setup end




// FIXME: Need some check
$GLOBALS['txp_install_successful'] = true;
$GLOBALS['txp_err_count'] = 0;
$GLOBALS['txp_err_html'] = '';


// Final rebuild category trees
rebuild_tree_full('article');
rebuild_tree_full('link');
rebuild_tree_full('image');
rebuild_tree_full('file');




function setup_txp_lang()
{
    global $blog_uid, $language;
    require_once txpath.'/lib/IXRClass.php';
    $client = new IXR_Client('http://rpc.textpattern.com');

    if (true && $client->query('tups.getLanguage', $blog_uid, $language)) {
        $response = $client->getResponse();
        $lang_struct = unserialize($response);

        foreach ($lang_struct as $item) {
            $item = doSlash($item);

            safe_insert('txp_lang', "
                lang    = '{$language}',
                name    = '{$item['name']}',
                event   = '{$item['event']}',
                data    = '{$item['data']}',
                lastmod = '".strftime('%Y%m%d%H%M%S', $item['uLastmod'])."'");
        }

        return;
    }

    if (!Txp::get('\Textpattern\L10n\Lang')->install_file($language)) {
        // If cannot install from lang file, setup the Default lang. `language` pref changed too.
        $language = TEXTPATTERN_DEFAULT_LANG;
        Txp::get('\Textpattern\L10n\Lang')->install_file($language);
    }
}

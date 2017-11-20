<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
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

function setup_db($cfg = '')
{
    global $language;

    // FIXME: Need check $cfg
    if (empty($cfg)) {
        exit('No setup config');
    }

    @define('LANG', $cfg['site']['lang']);
    $language = @$cfg['site']['lang'];


    global $txpcfg, $DB, $prefs, $txp_user, $txp_groups;
    global $permlink_mode, $siteurl, $theme_name, $public_themes;
    include_once txpath.'/lib/txplib_db.php';
    include_once txpath.'/lib/admin_config.php';

    $siteurl = rtrim(@$cfg['site']['siteurl'], '/');
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
    $siteurl = preg_replace('%^https?://%', '', $siteurl);
    $siteurl = str_replace(' ', '%20', $siteurl);
    $theme_name = empty($cfg['site']['theme']) ? 'hive' : $cfg['site']['theme'];

    get_public_themes_list();
    $public_theme = empty($public_themes[$cfg['site']['public_theme']]['themedir']) ? current(array_keys($public_themes)) : $cfg['site']['public_theme'];

    $themedir = $public_themes[$public_theme]['themedir'];

    if (empty($cfg['site']['datadir'])) {
        /*  Option 'txp-data' in manifest.json:
            <default>           - Import /articles and /data from setup dir
            txp-data == 'theme' - Import /articles and /data from theme dir
            txp-data == 'none'  - Nothing to import.
        */

        if (@$public_themes[$public_theme]['txp-data'] == 'theme') {
            $datadir = $themedir;
        } elseif (@$public_themes[$public_theme]['txp-data'] == 'none') {
            $datadir = '';
        } else {
            $datadir = txpath.DS.'setup';
        }
    } else {
        $datadir = $cfg['site']['datadir'];
    }

    //FIXME: We are doing nothing, waiting for the further development of branch `themes`.
    if (class_exists('\Textpattern\Skin\Main')) {
        $datadir = '';
    }

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
    $txp_user = $cfg['user']['name'];

    create_user($txp_user, $cfg['user']['email'], $cfg['user']['pass'], $cfg['user']['realname'], 1);

    if ($datadir) {
        /*  Load theme prefs:
                /data/core.prefs    - Allow override some core prefs. Used only in setup theme.
                /data/theme.prefs   - Theme global and private prefs.
                                        global  - Used in setup and for AutoCreate missing prefs.
                                        private - Will be created after user login
        */
        foreach (get_files_content($datadir.'/data', 'prefs') as $key=>$data) {
            if ($out = @json_decode($data, true)) {
                foreach ($out as $name => $p) {
                    if (empty($p['private'])) {
                        @set_pref($name, $p['val'], $p['event'], $p['type'], $p['html'], $p['position']);
                    }
                }
            }
        }


        $import = new \Textpattern\Import\TxpXML();

        foreach (get_files_content($datadir.'/data', 'xml') as $key=>$data) {
            $import->importXml($data);
        }

        foreach (get_files_content($datadir.'/articles', 'xml') as $key=>$data) {
            $import->importXml($data);
        }
    }

    // --- Theme setup.
    // Load theme /styles, /forms, /pages

    $public_theme = preg_replace('/\-.*/', '', $public_theme);

    //FIXME: We are doing nothing, waiting for the further development of branch `themes`.
    //FIXME: Need add support /setup/themes dir for Skin->import() function
    if (class_exists('\Textpattern\Skin\Main') /*&& !preg_match('%/setup/themes/%', $themedir) */) {
        //    Txp::get('\Textpattern\Skin\Main', array($public_theme => array()))->import();
        //    safe_update('txp_section', 'skin = "'.doSlash($public_theme).'"', '1=1');
    } else {
        foreach (get_files_content($themedir.'/styles', 'css') as $key=>$data) {
            safe_query("INSERT INTO `".PFX."txp_css`(name, css) VALUES('".doSlash($key)."', '".doSlash($data)."')");
        }

        if ($files = glob("{$themedir}/forms/*/*\.txp")) {
            foreach ($files as $file) {
                if (preg_match('%/forms/(\w+)/(\w+)\.txp$%', $file, $mm)) {
                    $data = @file_get_contents($file);
                    safe_query("INSERT INTO `".PFX."txp_form`(type, name, Form) VALUES('".doSlash($mm[1])."', '".doSlash($mm[2])."', '".doSlash($data)."')");
                }
            }
        }

        foreach (get_files_content($themedir.'/pages', 'txp') as $key=>$data) {
            safe_query("INSERT INTO `".PFX."txp_page`(name, user_html) VALUES('".doSlash($key)."', '".doSlash($data)."')");
        }
    }

    // --- Theme setup end




    // FIXME: Need some check
    //$GLOBALS['txp_install_fail'] = 1;



    // Final rebuild category trees
    rebuild_tree_full('article');
    rebuild_tree_full('link');
    rebuild_tree_full('image');
    rebuild_tree_full('file');
}



function setup_txp_lang()
{
    global $language;

    if (!Txp::get('\Textpattern\L10n\Lang')->install_file($language)) {
        // If cannot install from lang file, setup the Default lang. `language` pref changed too.
        $language = TEXTPATTERN_DEFAULT_LANG;
        Txp::get('\Textpattern\L10n\Lang')->install_file($language);
    }
}

/**
 * Fetch the list of available public themes.
 *
 * @return array
 */

function get_public_themes_list()
{
    global $public_themes;

    $public_themes = $out = array();

    if ($files = glob(txpath."/{setup/themes,../themes}/*/manifest\.json", GLOB_BRACE)) {
        foreach ($files as $file) {
            $file = realpath($file);

            if (preg_match('%^(.*/(\w+))/manifest\.json$%', $file, $mm) && $manifest = @json_decode(file_get_contents($file), true)) {
                if (@$manifest['txp-type'] == 'textpattern-theme') {
                    $key = $mm[2].'-'.md5($file);
                    $public_themes[$key] = $manifest;
                    $public_themes[$key]['themedir'] = $mm[1];
                    $out[$key] = empty($manifest['title']) ? $mm[2] : $manifest['title']." (".$manifest['version'] .') '.str_replace(txpath, '', $mm[1]);
                }
            }
        }
    }

    return $out;
}

/**
 * Generate a config.php file from the known info.
 */

function setup_makeConfig($cfg, $doSpecial = false)
{
    define("nl", "';\n");
    define("o", '$txpcfg[\'');
    define("m", "'] = '");

    // Escape single quotes and backslashes in literal PHP strings.
    foreach ($cfg['mysql'] as $k => $v) {
        $cfg['mysql'][$k] = addcslashes($cfg['mysql'][$k], "'\\");
    }

    if ($doSpecial) {
        $cfg['mysql'] = doSpecial($cfg['mysql']);
    }

    return
    "<"."?php\n"
    .o.'db'.m.$cfg['mysql']['db'].nl
    .o.'user'.m.$cfg['mysql']['user'].nl
    .o.'pass'.m.$cfg['mysql']['pass'].nl
    .o.'host'.m.$cfg['mysql']['host'].nl
    .(empty($cfg['mysql']['dclient_flags']) ? '' : o.'client_flags'."'] = ".$cfg['mysql']['dclient_flags'].";\n")
    .o.'table_prefix'.m.$cfg['mysql']['table_prefix'].nl
    .o.'txpath'.m.txpath.nl
    .o.'dbcharset'.m.$cfg['mysql']['dbcharset'].nl
    ."?".">";
}

/**
 * Try to connect to MySQL, check the database and the prefix of the tables.
 */

function setup_try_mysql($back = false)
{
    global $cfg;

    if (strpos($cfg['mysql']['host'], ':') === false) {
        $dhost = $cfg['mysql']['host'];
        $dport = ini_get("mysqli.default_port");
    } else {
        list($dhost, $dport) = explode(':', $cfg['mysql']['host'], 2);
        $dport = intval($dport);
    }

    $dsocket = ini_get("mysqli.default_socket");

    $mylink = mysqli_init();

    if (@mysqli_real_connect($mylink, $dhost, $cfg['mysql']['user'], $cfg['mysql']['pass'], '', $dport, $dsocket)) {
        $cfg['mysql']['dclient_flags'] = 0;
    } elseif (@mysqli_real_connect($mylink, $dhost, $cfg['mysql']['user'], $cfg['mysql']['pass'], '', $dport, $dsocket, MYSQLI_CLIENT_SSL)) {
        $cfg['mysql']['dclient_flags'] = 'MYSQLI_CLIENT_SSL';
    } else {
        msg(gTxt('db_cant_connect'), MSG_ERROR, $back);
    }

    echo msg(gTxt('db_connected'), MSG_OK);

    if (!($cfg['mysql']['table_prefix'] == '' || preg_match('#^[a-zA-Z_][a-zA-Z0-9_]*$#', $cfg['mysql']['table_prefix']))) {
        msg(gTxt('prefix_bad_characters',
            array('{dbprefix}' => strong(txpspecialchars($cfg['mysql']['table_prefix']))), 'raw'),
            MSG_ERROR, $back
        );
    }

    if (!$mydb = mysqli_select_db($mylink, $cfg['mysql']['db'])) {
        msg(gTxt('db_doesnt_exist',
            array('{dbname}' => strong(txpspecialchars($cfg['mysql']['db']))), 'raw'),
            MSG_ERROR, $back
        );
    }

    $tables_exist = mysqli_query($mylink, "DESCRIBE `".$cfg['mysql']['table_prefix']."textpattern`");
    if ($tables_exist) {
        msg(gTxt('tables_exist',
            array('{dbname}' => strong(txpspecialchars($cfg['mysql']['db']))), 'raw'),
            MSG_ERROR, $back
        );
    }

    // On MySQL 5.5.3+ use real UTF-8 tables, if the client supports it.
    $cfg['mysql']['dbcharset'] = "utf8mb4";
    // Lower versions only support UTF-8 limited to 3 bytes per character
    if (mysqli_get_server_version($mylink) < 50503) {
        $cfg['mysql']['dbcharset'] = "utf8";
    } else {
        if (false !== strpos(mysqli_get_client_info($mylink), 'mysqlnd')) {
            // mysqlnd 5.0.9+ required
            if (mysqli_get_client_version($mylink) < 50009) {
                $cfg['mysql']['dbcharset'] = "utf8";
            }
        } else {
            // libmysqlclient 5.5.3+ required
            if (mysqli_get_client_version($mylink) < 50503) {
                $cfg['mysql']['dbcharset'] = "utf8";
            }
        }
    }
    @mysqli_close($mylink);
    echo msg(gTxt('using_db', array(
        '{dbname}' => strong(txpspecialchars($cfg['mysql']['db'])), ), 'raw').' ('.$cfg['mysql']['dbcharset'].')');

    return true;
}

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

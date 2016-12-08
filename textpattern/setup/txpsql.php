<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
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

if (!defined('TXP_INSTALL')) {
    exit;
}

@ignore_user_abort(1);
@set_time_limit(0);

global $DB, $txp_groups, $blog_uid;
include txpath.'/lib/txplib_db.php';
include txpath.'/lib/admin_config.php';

// Variable set
$siteurl = str_replace("http://", '', $_SESSION['siteurl']);
$siteurl = str_replace(' ', '%20', rtrim($siteurl, "/"));
$urlpath = preg_replace('#^[^/]+#', '', $siteurl);
$theme = $_SESSION['theme'] ? $_SESSION['theme'] : 'hive';
$themedir = txpath.DS.'setup';
$structuredir = txpath.'/update/structure';

$setup_comment_invite = (gTxt('setup_comment_invite') == 'setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite');

// Default to messy URLs if we know clean ones won't work.
$permlink_mode = 'section_id_title';

if (is_callable('apache_get_modules')) {
    $modules = @apache_get_modules();

    if (!is_array($modules) || !in_array('mod_rewrite', $modules)) {
        $permlink_mode = 'messy';
    }
} elseif (!stristr(serverSet('SERVER_SOFTWARE'), 'Apache')) {
    $permlink_mode = 'messy';
}


if (numRows(safe_query("SHOW TABLES LIKE '".PFX."textpattern'"))) {
    die("Textpattern database table already exists. Can't run setup.");
}

// Create tables
foreach (get_files_content($structuredir, 'table') as $key=>$data) {
    safe_create($key, $data);
}

// Initial mandatory data
foreach (get_files_content($structuredir, 'data') as $key=>$data) {
    safe_query("INSERT INTO `".PFX."{$key}` VALUES ".$data);
}

// Create core prefs
include txpath.'/lib/prefs.php';

foreach ($default_prefs as $name => $p) {
    create_pref($name, $p[4], $p[0], $p[1], $p[3], $p[2]);
}

create_user($_SESSION['name'], $_SESSION['email'], $_SESSION['pass'], $_SESSION['realname'], 1);

setup_txp_lang(LANG);

// Theme setup

// Load theme /data, /styles, /forms, /pages
foreach (get_files_content($themedir.'/data', 'data') as $key=>$data) {
    safe_query("INSERT INTO `".PFX."{$key}` VALUES ".$data);
}

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



// FIXME: Make some wrapper for article and move data to $themedir/articles folder
$textile = new \Netcarver\Textile\Parser();

$article['body']    = file_get_contents(txpath.DS.'setup'.DS.'article.body.textile');
$article['excerpt'] = file_get_contents(txpath.DS.'setup'.DS.'article.excerpt.textile');
$article = str_replace('siteurl', $urlpath, $article);
$article['body_html']    = $textile->textileThis($article['body']);
$article['excerpt_html'] = $textile->textileThis($article['excerpt']);
$article = doSlash($article);

safe_query("INSERT INTO `".PFX."textpattern` VALUES (1, NOW(), NULL, '".doSlash($_SESSION['name'])."', NOW(), '', 'Welcome to your site', '', '".$article['body']."', '".$article['body_html']."', '".$article['excerpt']."', '".$article['excerpt_html']."', '', 'hope-for-the-future', 'meaningful-labor', 1, '".$setup_comment_invite."', 1, 4, '1', '1', 'articles', '', '', '', 'welcome-to-your-site', '', '', '', '', '', '', '', '', '', '', '".md5(uniqid(rand(), true))."', NOW())");
// Article hardcode end


// FIXME: Need some check
$GLOBALS['txp_install_successful'] = true;
$GLOBALS['txp_err_count'] = 0;
$GLOBALS['txp_err_html'] = '';


// Funal reduild category trees
rebuild_tree_full('article');
rebuild_tree_full('link');
rebuild_tree_full('image');
rebuild_tree_full('file');



function setup_txp_lang($lang)
{
    global $blog_uid;
    require_once txpath.'/lib/IXRClass.php';
    $client = new IXR_Client('http://rpc.textpattern.com');

    if (!$client->query('tups.getLanguage', $blog_uid, $lang)) {
        // If cannot install from lang file, setup the English lang.
        if (!install_language_from_file($lang)) {
            $lang = 'en-gb';
            include_once txpath.'/setup/en-gb.php';

            if (!@$lastmod) {
                $lastmod = '1970-01-01 00:00:00';
            }

            foreach ($en_gb_lang as $evt_name => $evt_strings) {
                foreach ($evt_strings as $lang_key => $lang_val) {
                    $lang_val = doSlash($lang_val);

                    if (@$lang_val) {
                        safe_insert('txp_lang', "
                            lang    = 'en-gb',
                            name    = '".$lang_key."',
                            event   = '".$evt_name."',
                            data    = '".$lang_val."',
                            lastmod = '".$lastmod."'");
                    }
                }
            }
        }
    } else {
        $response = $client->getResponse();
        $lang_struct = unserialize($response);

        foreach ($lang_struct as $item) {
            $item = doSlash($item);

            safe_insert('txp_lang', "
                lang    = '{$lang}',
                name    = '{$item['name']}',
                event   = '{$item['event']}',
                data    = '{$item['data']}',
                lastmod = '".strftime('%Y%m%d%H%M%S', $item['uLastmod'])."'");
        }
    }
}

<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('TXP_INSTALL')) {
    exit;
}

@ignore_user_abort(1);
@set_time_limit(0);

global $DB, $prefs, $txp_user;
global $permlink_mode, $siteurl, $blog_uid, $theme_name;
include txpath.'/lib/txplib_db.php';
include txpath.'/lib/admin_config.php';

// Variable set
$blog_uid = md5(uniqid(rand(), true));
$siteurl = str_replace("http://", '', $_SESSION['siteurl']);
$siteurl = str_replace(' ', '%20', rtrim($siteurl, "/"));
$theme_name = $_SESSION['theme'] ? $_SESSION['theme'] : 'hive';
$themedir = txpath.DS.'setup';
$structuredir = txpath.'/update/structure';

// Default to messy URLs if we know clean ones won't work.
$permlink_mode = 'section_title';

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
foreach (get_files_content($structuredir, 'json') as $key=>$data) {
    $json = json_decode($data, true);
    if (is_array($json)) {
        foreach ($json as $j) {
            safe_insert($key, make_sql_set($j));
        }
    }
}

setup_txp_lang();

// Create core prefs
foreach (get_prefs_default() as $name => $p) {
    if (empty($p['private'])) {
        @create_pref($name, $p['val'], $p['event'], $p['type'], $p['html'], $p['position']);
    }
}
$prefs = get_prefs();
$txp_user = $_SESSION['name'];

create_user($txp_user, $_SESSION['email'], $_SESSION['pass'], $_SESSION['realname'], 1);

// Theme setup

// Load theme /data, /styles, /forms, /pages
foreach (get_files_content($themedir.'/data', 'json') as $key=>$data) {
    $json = json_decode($data, true);
    if (is_array($json)) {
        foreach ($json as $j) {
            safe_insert($key, make_sql_set($j));
        }
    }
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


// Load articles
article_import($themedir.'/articles');


// FIXME: Need some check
$GLOBALS['txp_install_successful'] = true;
$GLOBALS['txp_err_count'] = 0;
$GLOBALS['txp_err_html'] = '';


// Final rebuild category trees
rebuild_tree_full('article');
rebuild_tree_full('link');
rebuild_tree_full('image');
rebuild_tree_full('file');


/**
 * Import articles with comment from xml files
 *
 */

function article_import($dir)
{
    global $prefs, $siteurl, $txp_user;
    $urlpath = preg_replace('#^[^/]+#', '', $siteurl);

    $textile = new \Netcarver\Textile\Parser();
    $optional_fields = array('section', 'status', 'keywords', 'description', 'annotate', 'annotateinvite',
    'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5', 'custom_6', 'custom_7', 'custom_8', 'custom_9', 'custom_10');

    foreach (get_files_content($dir, 'xml') as $key=>$data) {
        list($section, $notused) = explode('.', $key);
        $data = str_replace('siteurl', $urlpath, $data);

        $xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
        foreach ($xml->article as $a) {
            $article = array();
            $article['section']   = $section;
            $article['status'] = STATUS_LIVE;
            $article['annotate'] = 1;
            $article['annotateinvite'] = $prefs['comments_default_invite'];

            foreach ($optional_fields as $field) {
                if (!empty($a->$field)) {
                    $article[$field] = $a->$field;
                }
            }

            $article['Title']     = trim($a->title);
            $article['url_title'] = stripSpace($article['Title'], 1);
            $article['Category1'] = @$a->category[0];
            $article['Category2'] = @$a->category[1];

            $article['Body'] = @trim($a->body);
            $format = $a->body->attributes()->format;
            if ($format == 'textile') {
                $article['Body_html']       = $textile->textileThis($article['Body']);
                $article['textile_body']    = 1;
            } else {
                $article['Body_html']       = $article['Body'];
                $article['textile_body']    = 0;
            }

            $article['Excerpt'] = @trim($a->excerpt);
            $format = $a->excerpt->attributes()->format;
            if ($format == 'textile') {
                $article['Excerpt_html']    = $textile->textileThis($article['Excerpt']);
                $article['textile_excerpt'] = 1;
            } else {
                $article['Excerpt_html']    = $article['Excerpt'];
                $article['textile_excerpt'] = 0;
            }

            $article['AuthorID'] = $txp_user;
            $article['Posted'] = $article['LastMod'] = $article['feed_time'] = 'NOW()';
            $article['uid'] = md5(uniqid(rand(), true));

            $id = safe_insert('textpattern', make_sql_set($article));

            if ($id && !empty($a->comment)) {
                foreach ($a->comment as $c) {
                    $name = empty($c->name) ? 'txp-user' : $c->name;
                    $email = empty($c->email) ? stripSpace($name, 1).'@example.com' : $c->email;
                    safe_insert('txp_discuss', "
                        parentid        = '$id',
                        name            = '".doSlash($name)."',
                        email           = '".doSlash($email)."',
                        web             = '".doSlash($c->web)."',
                        message         = '".doSlash($c->message)."',
                        posted          = NOW(),
                        ip              = '127.0.0.1',
                        visible         = 1"
                    );
                }
                update_comments_count($id);
            }
        }
    }
}

function setup_txp_lang()
{
    global $blog_uid, $language;
    require_once txpath.'/lib/IXRClass.php';
    $client = new IXR_Client('http://rpc.textpattern.com');

    if (!$client->query('tups.getLanguage', $blog_uid, $language)) {
        if (!install_language_from_file($language)) {
            // If cannot install from lang file, setup the Default lang. `language` pref changed too.
            $language = TEXTPATTERN_DEFAULT_LANG;
            install_language_from_file($language);
        }
    } else {
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
    }
}

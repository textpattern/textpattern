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

if (!defined("txpinterface")) {
    die('If you just updated and expect to see your site here, please also update the files in your main installation directory.'.
        ' (Otherwise note that publish.php cannot be called directly.)');
}

global $trace;

$trace->start('[PHP includes, stage 2]');
include_once txpath.'/vendors/Textpattern/Loader.php';

$loader = new \Textpattern\Loader(txpath.'/vendors');
$loader->register();

$loader = new \Textpattern\Loader(txpath.'/lib');
$loader->register();

include_once txpath.'/lib/txplib_db.php';
include_once txpath.'/lib/admin_config.php';
include_once txpath.'/publish/log.php';

$trace->stop();

set_error_handler('publicErrorHandler', error_reporting());

ob_start();

// Get logged user.
$userInfo = is_logged_in();

// Initialise the current user.
$txp_user = empty($userInfo) ? null : $userInfo['name'];

// Get all prefs as an array.
$prefs = get_prefs(empty($userInfo['name']) ? '' : array('', $userInfo['name']));
empty($userInfo) or plug_privs(null, $userInfo);

// Add prefs to globals.
extract($prefs);

// Check the size of the URL request.
bombShelter();

$txp_sections = array();
$txp_current_tag = '';
$txp_parsed = $txp_else = $txp_item = $txp_context = $txp_yield = $yield = array();
$txp_atts = null;
$timezone_key = get_pref('timezone_key', date_default_timezone_get()) or $timezone_key = 'UTC';
date_default_timezone_set($timezone_key);

isset($pretext) or $pretext = array();

// Set a higher error level during initialisation.
set_error_level(@$production_status == 'live' ? 'testing' : @$production_status);

// disable tracing in live environment.
if ($production_status == 'live') {
    Trace::setQuiet(true);
}

// Use the current URL path if $siteurl is unknown.
if (empty($siteurl)) {
    $httphost = preg_replace('/[^-_a-zA-Z0-9.:]/', '', $_SERVER['HTTP_HOST']);
    $prefs['siteurl'] = $siteurl = $httphost.rtrim(dirname($_SERVER['SCRIPT_NAME']), DS);
}

if (empty($path_to_site)) {
    updateSitePath(dirname(dirname(__FILE__)));
}

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

// Definitive HTTP address of the site.
if (!defined('hu')) {
    define('hu', PROTOCOL.$siteurl.'/');
}

// Relative URL global.
if (!defined('rhu')) {
    define('rhu', preg_replace('|^https?://[^/]+|', '', hu));
}

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

// Shared admin and public cookie_domain when using multisite admin URL.
if (!defined('cookie_domain')) {
    if (!isset($txpcfg['cookie_domain'])) {
        $txpcfg['cookie_domain'] = '';
    }

    define('cookie_domain', $txpcfg['cookie_domain']);
}

if (!defined('SITE_HOST')) {
    /**
     * Site hostname.
     *
     * @package Network
     * @since   4.6.0
     */

    define('SITE_HOST', (string) @parse_url(hu, PHP_URL_HOST));
}

if (!defined('IMPATH')) {
    /**
     * Path to image directory.
     *
     * @package Image
     */

    define('IMPATH', $path_to_site.DS.$img_dir.DS);
}

// 1.0: a new $here variable in the top-level index.php should let us know the
// server path to the live site let's save it to prefs.
if (isset($here) and $path_to_site != $here) {
    updateSitePath($here);
}

if (!defined('LANG')) {
    /**
     * Currently active language.
     *
     * @package L10n
     */

    define('LANG', $language);
}

if (!defined('TXP_PATTERN')) {
    define('TXP_PATTERN', get_pref('enable_short_tags', false) ? 'txp|[a-z]+:' : 'txp:?');
}

if (!empty($locale)) {
    setlocale(LC_ALL, $locale);
}

// For backwards-compatibility (sort of) with plugins that expect the
// $textarray global to be present.
// Will remove in future.
$textarray = array();

// Here come the early plugins.
if ($use_plugins) {
    load_plugins(false, 5);
}

// Request URI rewrite, anyone?
callback_event('pretext', '', 1);
$pretext = preText($pretext, null) + array('secondpass' => 0, '_txp_atts' => false);

// Send 304 Not Modified if appropriate.

if (empty($pretext['feed'])) {
    handle_lastmod();
}

if (txpinterface === 'css') {
    output_css(gps('s'), gps('n'), gps('t'));

    exit;
}

callback_event('pretext_end', '', 1);

$txp_sections = safe_column(array('name'), 'txp_section');

$trace->start('[PHP includes, stage 3]');

include_once txpath.'/lib/txplib_publish.php';
include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/publish/comment.php';
include_once txpath.'/publish/taghandlers.php';

$trace->stop();

// i18n.
//load_lang(LANG);

// Tidy up the site.
janitor();

// Here come the regular plugins.
if ($use_plugins) {
    load_plugins();
}

callback_event('pretext');
$pretext = preText($pretext, $prefs);
extract($pretext);
callback_event('pretext_end');
// Right, twice.
extract($pretext);

// Now that everything is initialised, we can crank down error reporting.
set_error_level($production_status);

if (!empty($feed) && in_array($feed, array('atom', 'rss'), true)) {
    include txpath."/publish/{$feed}.php";
    echo $feed();

    if ($production_status !== 'live') {
        echo $trace->summary();

        if ($production_status === 'debug') {
            echo $trace->result();
        }
    }

    exit;
}

if (gps('parentid')) {
    if (ps('submit')) {
        saveComment();
    } elseif (ps('preview')) {
        checkCommentRequired(getComment());
    } elseif ($comments_mode == 1) {
        // Popup comments?
        header("Content-Type: text/html; charset=utf-8");
        exit(parse_form('popup_comments'));
    }
}

// We are dealing with a download.
if ($s == 'file_download') {
    empty($filename) or output_file_download($filename);
    exit(0);
}

// Log the page view.
log_hit($status);

// -------------------------------------------------------------

function preText($store, $prefs = null)
{
    global $thisarticle, $txp_sections;
    static $url = array(), $out = null;

    if (empty($url)) {
        // Some useful vars for taghandlers, plugins.
        $out['request_uri'] = preg_replace("|^https?://[^/]+|i", "", serverSet('REQUEST_URI'));
        $out['qs'] = serverSet('QUERY_STRING');

        // IIS fix.
        if (!$out['request_uri'] and serverSet('SCRIPT_NAME')) {
            $out['request_uri'] = serverSet('SCRIPT_NAME').((serverSet('QUERY_STRING')) ? '?'.serverSet('QUERY_STRING') : '');
        }

        // Another IIS fix.
        if (!$out['request_uri'] and serverSet('argv')) {
            $argv = serverSet('argv');
            $out['request_uri'] = @substr($argv[0], strpos($argv[0], ';') + 1);
        }

        // Define the usable url, minus any subdirectories.
        // This is pretty ugly, if anyone wants to have a go at it.
        $out['subpath'] = $subpath = preg_quote(preg_replace("/https?:\/\/.*(\/.*)/Ui", "$1", hu), "/");
        $out['req'] = $req = preg_replace("/^$subpath/i", "/", $out['request_uri']);

        $url = chopUrl($req, 5);

        for ($out[0] = 0; isset($url['u'.($out[0]+1)]); $out[++$out[0]] = $url['u'.$out[0]]);

        if ($url['u1'] == 'rss' || gps('rss')) {
            $out['feed'] = 'rss';
        } elseif ($url['u1'] == 'atom' || gps('atom')) {
            $out['feed'] = 'atom';
        }
    }

    // Return clean URL test results for diagnostics.
    if (gps('txpcleantest')) {
        exit(show_clean_test($out));
    }

    if (is_array($store)) {
        $out = $store + $out;
    }

    if (!isset($prefs)) {
        return $out;
    }

    extract($prefs);

    // Set messy variables.
    $out += makeOut('id', 's', 'c', 'context', 'q', 'm', 'pg', 'p', 'month', 'author', 'f');
    $out['skin'] = $out['page'] = $out['css'] = '';

    $is_404 = ($out['status'] == '404');
    $title = null;
    $status = gps('txpreview') ? '' : " AND Status IN (".STATUS_LIVE.",".STATUS_STICKY.")";

    // Handle article preview.
    if (!$status) {
        header('Cache-Control: no-cache, no-store, max-age=0');
        doAuth();

        if (!has_privs('article.preview')) {
            txp_status_header('401 Unauthorized');
            exit(hed('401 Unauthorized', 1).graf(gTxt('restricted_area')));
        } else {
            global $nolog;
    
            $nolog = true;
            $out['id'] = intval(gps('txpreview'));
        }
    }

    // These are deprecated as of Textpattern v1.0 - leaving them here for
    // plugin compatibility.
    $out['path_from_root'] = rhu;
    $out['pfr']            = rhu;

    $out['path_to_site']   = $path_to_site;
    $out['permlink_mode']  = $permlink_mode;
    $out['sitename']       = $sitename;

    // First we sniff out some of the preset URL schemes.
    extract($url);

    // If messy vars exist, bypass URL parsing.
    if (!$is_404 && !$out['id'] && !$out['s'] && txpinterface != 'css' && txpinterface != 'admin' && strlen($u1)) {
        if ($trailing_slash > 0 && $out[$out[0]] !== '' || $trailing_slash < 0 && $out[$out[0]] === '') {
            $is_404 = true;
        } else {
            $n = $trailing_slash > 0 ? $out[0] - 1 : $out[0];
            $un = $out[$n];

            switch ($u1) {
                case 'atom':
                    $out['feed'] = 'atom';
                    break;

                case 'rss':
                    $out['feed'] = 'rss';
                    break;

                // urldecode(strtolower(urlencode())) looks ugly but is the
                // only way to make it multibyte-safe without breaking
                // backwards-compatibility.
                case 'section':
                case urldecode(strtolower(urlencode(gTxt('section')))):
                    $out['s'] = $u2;
                    break;

                case 'category':
                case urldecode(strtolower(urlencode(gTxt('category')))):
                    $out['context'] = $u3 ? validContext($u2) : 'article';
                    if ($permlink_mode == 'breadcrumb_title') {
                        $n < 2 or $out['c'] = $un ? $un : $out[$n-1];
                    } else {
                        $out['c'] = $u3 ? $u3 : $u2;
                    }
                    break;

                case 'author':
                case urldecode(strtolower(urlencode(gTxt('author')))):
                    if ($u3) {
                        $out['context'] = validContext($u2);
                        $out['author'] = $u3;
                    } else {
                        $out['context'] = 'article';
                        $out['author'] = $u2;
                    }

                    $out['author'] = (!empty($out['author'])) ? $out['author'] : '';
                    break;
                    // AuthorID gets resolved from Name further down.

                case 'file_download':
                case urldecode(strtolower(urlencode(gTxt('file_download')))):
                    $out['s'] = 'file_download';
                    $out['id'] = (!empty($u2)) ? $u2 : '';
                    $out['filename'] = (!empty($u3)) ? $u3 : '';
                    break;

                default:
                    $permlink_modes = array('default' => $permlink_mode) + array_column($txp_sections, 'permlink_mode', 'name');
                    $custom_modes = array_filter($permlink_modes, function ($v) use ($permlink_mode) {
                        return $v && $v !== $permlink_mode;
                    });

                    if (empty($custom_modes)) {
                        $permlink_guess = $permlink_mode;
                    } elseif (!empty($un) && ($n > 1 || in_array('title_only', $permlink_modes) || in_array('id_title', $permlink_modes))) {// ID or url_title
                        $safe_un = doSlash($un);
                        $slash = $trailing_slash <= 0 ? '' : '/';

                        $guessarticles = safe_rows(
                            '*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod',
                            'textpattern',
                            "(url_title='$safe_un'".($n < 3 && is_numeric($un) ? " OR ID='$safe_un')" : ')').$status
                        );

                        foreach ($guessarticles as $a) {
                            populateArticleData($a);

                            if ('/'.$a['Section'].'/'.$a['url_title'].$slash === $u0) {
                                $permlink_guess = 'section_title';
                                break;
                            }

                            $thisurl = permlinkurl($thisarticle, '/');

                            if ($thisurl === $u0) {
                                $permlink_guess = $permlink_modes[$a['Section']];
                                break;
                            }
                        }

                        if (!isset($permlink_guess)) {
                            unset($thisarticle);
                            $trailing_slash != 0 or $is_404 = true;
                        } else {
                            $out['id'] = $thisarticle['thisid'];
                            $out['s'] = $thisarticle['section'];
                            $title = $thisarticle['url_title'];
                            $month = explode('-', date('Y-m-d', $thisarticle['posted']));
                        }
                    }

                    if (!$is_404 && empty($out['id'])) {
                        if (!isset($permlink_guess)) {
                            if (isset($permlink_modes[$u1])) {
                                $permlink_guess = $permlink_modes[$u1];
                            } elseif (is_numeric($u1) && strlen($u1) === 4 && in_array('year_month_day_title', $custom_modes)) {
                                // Could be a year.
                                $permlink_guess = 'year_month_day_title';
                            } else {
                                $permlink_guess = $permlink_mode;
                            }
                        }

                        // Then see if the prefs-defined permlink scheme is usable.
                        switch ($permlink_guess) {
                            case 'section_id_title':
                                $out['s'] = $u1;

                                if (is_numeric($u2)) {
                                    $out['id'] = $u2;
                                } else {
                                    $title = empty($u2) ? null : $u2;
                                }

                                break;

                            case 'section_category_title':
                            case 'breadcrumb_title':
                                $out['s'] = $u1;
                                $title = $n < 2 || empty($un) ? null : $un;
                                isset($title) || $n <= 2 or $out['c'] = $out[$n-1];

                                break;

                            case 'year_month_day_title':
                                if ($month = is_date(trim($u1.'-'.$u2.'-'.$u3, '-'))) {
                                    $title = empty($u4) ? null : $u4;
                                } elseif (!empty($u2) && $month = is_date(trim($u2.'-'.$u3.'-'.$u4, '-'))) {
                                    $title = empty($u5) ? null : $u5;
                                    $out['s'] = $u1;
                                } elseif (empty($u3)) {
                                    $out['s'] = $u1;
                                    $title = empty($u2) ? null : $u2;
                                } else {
                                    $is_404 = true;
                                }

                                break;

                            case 'section_title':
                                $out['s'] = $u1;
                                $title = empty($u2) ? null : $u2;

                                break;

                            case 'id_title':
                                if (is_numeric($u1)) {
                                    $out['id'] = $u1;
                                } else {
                                    // We don't want to miss the /section/ pages.
                                    $out['s'] = $u1;
                                    $title = empty($u2) ? null : $u2;
                                }

                                break;

                            default:
                                if (isset($u2) || $trailing_slash < 0) {
                                    $out['s'] = $u1;
                                    $title = empty($u2) ? null : $u2;
                                } else {
                                    $title = $u1;
                                }
                        }
                    }
            }
        }
    }

    $out['context'] = validContext($out['context']);

    // Validate dates
    if ($out['month'] && $out['month'] = is_date($out['month'])) {
        if (empty($month) || strpos($out['month'], $month) === 0) {
            $month = $out['month'];
        } elseif (strpos($month, $out['month']) === 0) {
            $out['month'] = $month;
        } else {
            $month = '';
            $is_404 = true;
        }
    } elseif (!empty($month)) {
        !empty($title) or $out['month'] = $month;
    }

    // Resolve AuthorID from Authorname.
    if (!$is_404 && $out['author'] && $name = safe_field('name', 'txp_users', "RealName LIKE '".doSlash($out['author'])."'")) {
        $out['realname'] = $out['author'];
        $out['author'] = $name;
    } else {
        $is_404 = $is_404 || !empty($out['author']);
        $out['author'] = $out['realname'] = '';
    }

    // Existing category in messy or clean URL?
    if (!$is_404 && !empty($out['c'])) {
        global $thiscategory;

        if (!($thiscategory = ckCat($out['context'], $out['c']))) {
            $is_404 = true;
            $out['c'] = '';
            $thiscategory = null;
        } else {
            $thiscategory += array('is_first' => true, 'is_last' => true, 'section' => $out['s']);
        }
    }

    // Prevent to get the id for file_downloads.
    if ($out['s'] == 'file_download') {
        if (!$is_404 && is_numeric($out['id'])) {
            global $thisfile;

            // Undo the double-encoding workaround for .gz files;
            // @see filedownloadurl().
            if (!empty($out['filename'])) {
                $out['filename'] = preg_replace('/gz&$/i', 'gz', $out['filename']);
            }

            $fn = empty($out['filename']) ? '' : " AND filename = '".doSlash($out['filename'])."'";
            $rs = safe_row('*', 'txp_file', "id = ".intval($out['id'])." AND status = ".STATUS_LIVE." AND created <= ".now('created').$fn);

            $thisfile = $rs ? file_download_format_info($rs) : null;
        }

        $is_404 = $is_404 || empty($rs);
        $out = array_merge($out, $is_404 ? array('id' => '', 'file_error' => 404, 'status' => 404) : $rs);
    } elseif (!$is_404 && $out['context'] == 'article') {
        if ($out['s'] && !isset($txp_sections[$out['s']])) {
            $is_404 = true;
        } elseif (empty($thisarticle) && (!empty($out['id']) || !empty($title))) {
            if ($out['s'] === '') {
                $rs = !empty($out['id']) ?
                    lookupByID($out['id']) :
                    lookupByDateTitle(isset($month) ? $month : '', $title);
            } else {
                $rs = !empty($out['id']) ?
                    lookupByIDSection($out['id'], $out['s']) :
                    lookupByTitleSection($title, $out['s']);
            }

            $out['id'] = (!empty($rs['ID'])) ? $rs['ID'] : '';
            $out['s'] = (!empty($rs['Section'])) ? $rs['Section'] : '';
            $is_404 = $is_404 || empty($out['id']) || $out['s'] == '';
            $is_404 or populateArticleData($rs);
        }

        if (!empty($thisarticle)) {
            unset($thiscategory);
            $uExpires = $thisarticle['expires'];
            $out['id_keywords'] = $thisarticle['keywords'];
            $out['id_author']   = $thisarticle['authorid'];

            if ($status && !$publish_expired_articles && $uExpires && time() > $uExpires) {
                $is_404 = '410';
            }
        }
    }

    // Stats: found or not.
    $out['status'] = is_numeric($is_404) ? $is_404 : ($is_404 ? '404' : '200');
    $out['pg'] = is_numeric($out['pg']) ? intval($out['pg']) : '';
    $out['id'] = is_numeric($out['id']) ? intval($out['id']) : '';
    $id = $out['id'];

    // Hackish.
    global $is_article_list;

    if (empty($id)) {
        $is_article_list = true;
    }

    // By this point we should know the section, so grab its page and CSS.
    // Logged-in users with enough privs use the skin they're currently editing.
    if (txpinterface != 'css') {
        $userInfo = is_logged_in();

        if ($userInfo && has_privs('skin.preview', $userInfo)) {
            foreach ($txp_sections as &$rs) {
                empty($rs['dev_skin']) or $rs['skin'] = $rs['dev_skin'];
                empty($rs['dev_page']) or $rs['page'] = $rs['dev_page'];
                empty($rs['dev_css']) or $rs['css'] = $rs['dev_css'];
            }

            unset($rs);
        }

        $s = $out['s'] = $is_404 || !isset($txp_sections[$out['s']]) ? 'default' : $out['s'];
        $rs = $txp_sections[$s];

        $out['skin'] = isset($rs['skin']) ? $rs['skin'] : '';
        $out['page'] = isset($rs['page']) ? $rs['page'] : '';
        $out['css'] = isset($rs['css']) ? $rs['css'] : '';
    }

    return $out;
}

//    textpattern() is the function that assembles a page, based on
//    the variables passed to it by pretext();

// -------------------------------------------------------------

function textpattern()
{
    global $pretext, $production_status, $has_article_tag;

    $has_article_tag = false;

    callback_event('textpattern');

    if ($pretext['status'] == '404') {
        txp_die(gTxt('404_not_found'), '404');
    }

    if ($pretext['status'] == '410') {
        txp_die(gTxt('410_gone'), '410');
    }

    // Useful for clean URLs with error-handlers.
    txp_status_header('200 OK');

    set_error_handler('tagErrorHandler');
    $html = parse_page($pretext['page'], $pretext['skin']);

    if ($html === false) {
        txp_die(gTxt('unknown_section'), '404');
    }

    // Make sure the page has an article tag if necessary.
    if (!$has_article_tag && $production_status != 'live' && $pretext['context'] == 'article' && (!empty($pretext['id']) || !empty($pretext['c']) || !empty($pretext['q']) || !empty($pretext['pg']))) {
        trigger_error(gTxt('missing_article_tag', array('{page}' => $pretext['page'])));
    }

    restore_error_handler();
    set_headers();
    echo ltrim($html);

    callback_event('textpattern_end');
}

// -------------------------------------------------------------
function output_component($n = '')
{
    global $pretext;
    static $mimetypes = null, $typequery = null;

    if (!isset($mimetypes)) {
        $null = null;
        $mimetypes = get_mediatypes($null);
        $typequery = " AND type IN ('".implode("','", doSlash(array_keys($mimetypes)))."')";
    }

    if (!$n || !is_scalar($n) || empty($mimetypes)) {
        return;
    }

    $t = $pretext['skin'];
    $skinquery = $t ? " AND skin='".doSlash($t)."'" : '';

    $n = do_list_unique(doSlash($n));
    $name = join("','", $n);
    $order = count($n) > 1 ? " ORDER BY FIELD(name, '$name')" : '';
    $mimetype = null;
    $assets = array();

    if (!empty($name) && $rs = safe_rows('Form, type', 'txp_form', "name IN ('$name')".$typequery.$skinquery.$order)) {
        foreach ($rs as $row) {
            if (!isset($mimetype) || $mimetypes[$row['type']] == $mimetype) {
                $assets[] = $row['Form'];
                $mimetype = $mimetypes[$row['type']];
            }
        }

        set_error_handler('tagErrorHandler');
        @header('Content-Type: '.$mimetype.'; charset=utf-8');
        echo ltrim(parse_page(null, null, implode(n, $assets)));
        restore_error_handler();
    }
}

// -------------------------------------------------------------
function output_css($s = '', $n = '', $t = '')
{
    $order = '';

    if ($n) {
        if (!is_array($n)) {
            $n = do_list_unique($n);
        }

        $cssname = join("','", doSlash($n));

        if (count($n) > 1) {
            $order = " ORDER BY FIELD(name, '$cssname')";
        }
    } elseif ($s && $res = safe_row('css, skin', 'txp_section', "name='".doSlash($s)."'")) {
        $cssname = $res['css'];
        $t or $t = $res['skin'];
    }

    if (!empty($cssname)) {
        $skinquery = $t ? " AND skin='".doSlash($t)."'" : '';
        $css = join(n, safe_column_num('css', 'txp_css', "name IN ('$cssname')".$skinquery.$order));
        set_error_handler('tagErrorHandler');
        @header('Content-Type: text/css; charset=utf-8');
        echo $css;
        restore_error_handler();
    }
}

// -------------------------------------------------------------
function output_file_download($filename)
{
    global $file_error, $file_base_path, $pretext;

    set_headers(array(
        'last-modified' => false,
        'etag' => false
    ), true);

    callback_event('file_download');

    if (!isset($file_error)) {
        parse_form('file_download_header');
        $filename = sanitizeForFile($filename);
        $fullpath = build_file_path($file_base_path, $filename);

        if (is_file($fullpath)) {
            // Discard any error PHP messages.
            ob_clean();
            $filesize = filesize($fullpath);
            $sent = 0;

            set_headers(array(
                'content-type' => 'application/octet-stream',
                'content-disposition' => 'attachment; filename="'.$filename.'"',
                'content-length' => $filesize,
                // Fix for IE6 PDF bug on servers configured to send cache headers.
                'cache-control' => 'private'
            ));

            @ini_set("zlib.output_compression", "Off");
            @set_time_limit(0);
            @ignore_user_abort(true);

            if ($file = fopen($fullpath, 'rb')) {
                while (!feof($file) and (connection_status() == 0)) {
                    echo fread($file, 1024 * 64);
                    $sent += (1024 * 64);
                    ob_flush();
                    flush();
                }

                fclose($file);

                // Record download.
                if ((connection_status() == 0) and !connection_aborted()) {
                    safe_update('txp_file', "downloads = downloads + 1", "id = ".intval($pretext['id']));
                } else {
                    $pretext['request_uri'] .= ($sent >= $filesize)
                        ? '#aborted'
                        : "#aborted-at-".floor($sent * 100 / $filesize)."%";
                }

                log_hit('200');
            }
        } else {
            $file_error = 404;
        }
    }

    // Deal with error.
    if (isset($file_error)) {
        switch ($file_error) {
            case 403:
                txp_die(gTxt('403_forbidden'), '403');
                break;
            case 404:
                txp_die(gTxt('404_not_found'), '404');
                break;
            default:
                txp_die(gTxt('500_internal_server_error'), '500');
                break;
        }
    }
}

// article() is called when parse() finds a <txp:article /> tag.
// If an $id has been established, we output a single article,
// otherwise, output a list.

// -------------------------------------------------------------
function article($atts, $thing = null)
{
    global $is_article_body, $has_article_tag;

    if ($is_article_body) {
        trigger_error(gTxt('article_tag_illegal_body'));

        return '';
    }

    $has_article_tag = true;

    return parseArticles($atts, '0', $thing);
}

// -------------------------------------------------------------

function doArticles($atts, $iscustom, $thing = null)
{
    global $pretext, $thisarticle, $thispage, $trace;

    extract($pretext);

    // Getting attributes.
    if (isset($thing) && !isset($atts['form'])) {
        $atts['form'] = '';
    }

    $theAtts = filterAtts($atts, $iscustom);
    extract($theAtts);
    $issticky = $theAtts['status'] == STATUS_STICKY;

    $pg or $pg = 1;
    $custom_pg = $pgonly && $pgonly !== true && !is_numeric($pgonly);
    $pgby = intval(empty($pageby) || $pageby === true ? ($custom_pg || !$limit ? 1 : $limit) : $pageby);

    if ($offset === true || !$iscustom && !$issticky) {
        $offset = $offset === true ? 0 : intval($offset);
        $pgoffset = ($pg - 1) * $pgby + $offset;
    } else {
        $pgoffset = $offset = intval($offset);
    }

    if ($custom_pg) {
        $groupby = trim($pgonly);
    } elseif (isset($theAtts['%'])) {
        $groupby = $theAtts['%'];
    }

    $columns = $theAtts['*'];
    $where = $theAtts['$'];
    $tables = $theAtts['#'];

    // Do not paginate if we are on a custom list.
    if ($pageby === true || !$iscustom && !$issticky) {
        if ($pageby === true || empty($thispage) && (!isset($pageby) || $pageby)) {
            $what = !empty($groupby) ? "DISTINCT $groupby" : '*';
            $grand_total = getThing("SELECT COUNT($what) FROM $tables WHERE $where");
            $total = $grand_total - $offset;
            $numPages = $pgby ? ceil($total / $pgby) : 1;
            $trace->log("[Found: $total articles, $numPages pages]");

            // Send paging info to txp:newer and txp:older.
            $thispage = array(
                'pg'          => $pg,
                'numPages'    => $numPages,
                's'           => $s,
                'c'           => $c,
                'context'     => 'article',
                'grand_total' => $grand_total,
                'total'       => $total
            );
        }

        if ($pgonly) {
            return;
        }
    } elseif ($pgonly) {
        $total = getCount(array($tables, !empty($groupby) ? "DISTINCT $groupby" : '*'), $where);
        $total -= $offset;

        return $pgby ? ceil($total / $pgby) : $total;
    }

    $where = $theAtts['?'];

    // Preserve order of custom article ids unless 'sort' attribute is set.
    if (!empty($id) && empty($atts['sort'])) {
        $sort = "FIELD(ID, ".$id."), ".$sort;
    }

    $rs = safe_query("SELECT $columns FROM $tables WHERE $where ORDER BY $sort LIMIT ".
        intval($pgoffset).", ".($limit ? intval($limit) : PHP_INT_MAX)
    );

    $articles = parseList($rs, $thisarticle, 'populateArticleData', compact('allowoverride', 'thing', 'form'));
//    unset($GLOBALS['thisarticle']);

    return !empty($articles) ?
        doLabel($label, $labeltag).doWrap($articles, $wraptag, compact('break', 'class')) :
        ($thing ? parse($thing, false) : '');
}

// -------------------------------------------------------------

function doArticle($atts, $thing = null)
{
    global $pretext, $thisarticle;

    if (isset($thing) && !isset($atts['form'])) {
        $atts['form'] = '';
    }

    $oldAtts = filterAtts();
    $atts = filterAtts($atts);

    // No output required, only setting atts.
    if ($atts['pgonly']) {
        return '';
    }

    if (empty($thisarticle) || $thisarticle['thisid'] != $pretext['id']) {
        $id = assert_int($pretext['id']);
        $thisarticle = null;
        $where = gps('txpreview') ? '1' : $atts['?'];
        $tables = 'textpattern';
        $columns = $atts['*'];

        $rs = safe_rows_start($columns, $tables, "WHERE ID = $id AND $where");

        if ($rs && $a = nextRow($rs)) {
            populateArticleData($a);
            $thisarticle['is_first'] = $thisarticle['is_last'] = 1;
        }
    }

    $article = false;

    if (!empty($thisarticle) && (in_list($thisarticle['status'], $atts['status']) || gps('txpreview'))) {
        extract($thisarticle);
        $thisarticle['is_first'] = $thisarticle['is_last'] = 1;

        if ($atts['allowoverride'] && $override_form) {
            $article = parse_form($override_form);
        } elseif ($atts['form']) {
            $article = parse_form($atts['form']);
        }

        if (isset($thing) && $article === false) {
            $article = parse($thing);
        }

        if ($article !== false && get_pref('use_comments') && get_pref('comments_auto_append')) {
            $article .= parse_form('comments_display');
        }

        unset($GLOBALS['thisarticle']);
    } else {
        // Restore atts to the previous article filter criteria.
        filterAtts($oldAtts ? $oldAtts : false);
    }

    return $article !== false ? $article : ($thing ? parse($thing, false) : '');
}

// -------------------------------------------------------------

function article_custom($atts, $thing = null)
{
    return parseArticles($atts, '1', $thing);
}

// -------------------------------------------------------------

function parseArticles($atts, $iscustom = 0, $thing = null)
{
    global $pretext, $is_article_list;
    $old_ial = $is_article_list;
    $is_article_list = empty($pretext['id']) || $iscustom;
    article_push();
    $r = ($is_article_list) ? doArticles($atts, $iscustom, $thing) : doArticle($atts, $thing);
    article_pop();
    $is_article_list = $old_ial;

    return $r;
}

// -------------------------------------------------------------

function makeOut()
{
    $array['status'] = '200';

    foreach (func_get_args() as $a) {
        $in = gps($a);

        if (is_scalar($in)) {
            $array[$a] = strval($in);
        } else {
            $array[$a] = '';
            $array['status'] = '404';
        }
    }

    return $array;
}

// -------------------------------------------------------------

function validContext($context)
{
    static $valid = null;

    if (empty($valid)) {
        foreach (array('article', 'image', 'file', 'link') as $type) {
            $valid[gTxt($type.'_context')] = $type;
            $valid[$type] = $type;
        }
    }

    return isset($valid[$context]) ? $valid[$context] : 'article';
}

/**
 * Chops a request string into URL-decoded path parts.
 *
 * @param   string $req Request string
 * @return  array
 * @package URL
 */

function chopUrl($req, $min = 4)
{
    $req = strtok($req, '?');
    $req = preg_replace('/index\.php$/i', '', $req);
    $r = array_map('urldecode', explode('/', strtolower($req)));
    $n = isset($min) ? max($min, count($r)) : count($r);
    $o = array('u0' => $req);

    for ($i = 1; $i < $n; $i++) {
        $o['u'.$i] = (isset($r[$i])) ? $r[$i] : null;
    }

    return $o;
}

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

include_once txpath.'/lib/txplib_publish.php';
include_once txpath.'/lib/txplib_db.php';
include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/lib/admin_config.php';

include_once txpath.'/publish/taghandlers.php';
include_once txpath.'/publish/log.php';
include_once txpath.'/publish/comment.php';
$trace->stop();

set_error_handler('publicErrorHandler', error_reporting());

ob_start();

$txp_current_tag = '';
$txp_parsed      = array();

// Get all prefs as an array.
$prefs = get_prefs();

// Add prefs to globals.
extract($prefs);

// Check the size of the URL request.
bombShelter();

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

if (!empty($locale)) {
    setlocale(LC_ALL, $locale);
}

// Initialise the current user.
$txp_user = null;

// i18n.
$textarray = (txpinterface == 'css') ? array() : load_lang(LANG);

// Tidy up the site.
janitor();

// Here come the plugins.
if ($use_plugins) {
    load_plugins();
}

// This step deprecated as of 1.0 - really only useful with old-style section
// placeholders, which passed $s='section_name'.
$s = (empty($s)) ? '' : $s;

$pretext = !isset($pretext) ? array() : $pretext;
$pretext = array_merge($pretext, pretext($s, $prefs));
callback_event('pretext_end');
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
        header("Content-type: text/html; charset=utf-8");
        exit(parse_form('popup_comments'));
    }
}

// We are dealing with a download.
if (@$s == 'file_download' && !empty($filename)) {
    output_file_download($filename);
    exit(0);
}

// Send 304 Not Modified if appropriate.
handle_lastmod();

// Log the page view.
log_hit($status);

// -------------------------------------------------------------

function preText($s, $prefs)
{
    extract($prefs);

    callback_event('pretext');

    // Set messy variables.
    $out = makeOut('id', 's', 'c', 'context', 'q', 'm', 'pg', 'p', 'month', 'author');

    if (gps('rss')) {
        $out['feed'] = 'rss';
    }

    if (gps('atom')) {
        $out['feed'] = 'atom';
    }

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

    // Define the useable url, minus any subdirectories.
    // This is pretty ugly, if anyone wants to have a go at it.
    $out['subpath'] = $subpath = preg_quote(preg_replace("/https?:\/\/.*(\/.*)/Ui", "$1", hu), "/");
    $out['req'] = $req = preg_replace("/^$subpath/i", "/", $out['request_uri']);

    $is_404 = ($out['status'] == '404');

    // If messy vars exist, bypass URL parsing.
    if (!$out['id'] && !$out['s'] && !(txpinterface == 'css') && ! (txpinterface == 'admin')) {
        // Return clean URL test results for diagnostics.
        if (gps('txpcleantest')) {
            exit(show_clean_test($out));
        }

        extract(chopUrl($req));

        // First we sniff out some of the preset URL schemes.
        if (strlen($u1)) {
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
                case urldecode(strtolower(urlencode(gTxt('section')))):
                    $out['s'] = (ckEx('section', $u2)) ? $u2 : ''; $is_404 = empty($out['s']);
                    break;

                case urldecode(strtolower(urlencode(gTxt('category')))):
                    if ($u3) {
                        $out['context'] = validContext($u2);
                        $out['c'] = $u3;
                    } else {
                        $out['context'] = 'article';
                        $out['c'] = $u2;
                    }
                    $out['c'] = (ckCat($out['context'], $out['c'])) ? $out['c'] : '';
                    $is_404 = empty($out['c']);
                    break;

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

                case urldecode(strtolower(urlencode(gTxt('file_download')))):
                    $out['s'] = 'file_download';
                    $out['id'] = (!empty($u2)) ? $u2 : '';
                    $out['filename'] = (!empty($u3)) ? $u3 : '';
                    break;

                default:
                    // Then see if the prefs-defined permlink scheme is usable.
                    switch ($permlink_mode) {

                        case 'section_id_title':
                            if (empty($u2)) {
                                $out['s'] = (ckEx('section', $u1)) ? $u1 : '';
                                $is_404 = empty($out['s']);
                            } else {
                                $rs = lookupByIDSection($u2, $u1);
                                $out['s'] = @$rs['Section'];
                                $out['id'] = @$rs['ID'];
                                $is_404 = (empty($out['s']) or empty($out['id']));
                            }

                            break;

                        case 'year_month_day_title':
                            if (empty($u2)) {
                                $out['s'] = (ckEx('section', $u1)) ? $u1 : '';
                                $is_404 = empty($out['s']);
                            } elseif (empty($u4)) {
                                $month = "$u1-$u2";

                                if (!empty($u3)) {
                                    $month .= "-$u3";
                                }

                                if (preg_match('/\d+-\d+(?:-\d+)?/', $month)) {
                                    $out['month'] = $month;
                                    $out['s'] = 'default';
                                } else {
                                    $is_404 = 1;
                                }
                            } else {
                                $when = "$u1-$u2-$u3";
                                $rs = lookupByDateTitle($when, $u4);
                                $out['id'] = (!empty($rs['ID'])) ? $rs['ID'] : '';
                                $out['s'] = (!empty($rs['Section'])) ? $rs['Section'] : '';
                                $is_404 = (empty($out['s']) or empty($out['id']));
                            }

                            break;

                        case 'section_title':
                            if (empty($u2)) {
                                $out['s'] = (ckEx('section', $u1)) ? $u1 : '';
                                $is_404 = empty($out['s']);
                            } else {
                                $rs = lookupByTitleSection($u2, $u1);
                                $out['id'] = isset($rs['ID']) ? $rs['ID'] : '';
                                $out['s'] = isset($rs['Section']) ? $rs['Section'] : '';
                                $is_404 = (empty($out['s']) or empty($out['id']));
                            }

                            break;

                        case 'title_only':
                            $rs = lookupByTitle($u1);
                            $out['id'] = @$rs['ID'];
                            $out['s'] = (empty($rs['Section']) ? ckEx('section', $u1) :
                                    $rs['Section']);
                            $is_404 = empty($out['s']);

                            break;

                        case 'id_title':
                            if (is_numeric($u1) && ckExID($u1)) {
                                $rs = lookupByID($u1);
                                $out['id'] = (!empty($rs['ID'])) ? $rs['ID'] : '';
                                $out['s'] = (!empty($rs['Section'])) ? $rs['Section'] : '';
                                $is_404 = (empty($out['s']) or empty($out['id']));
                            } else {
                                // We don't want to miss the /section/ pages.
                                $out['s'] = ckEx('section', $u1) ? $u1 : '';
                                $is_404 = empty($out['s']);
                            }

                            break;
                    }

                    if (!$is_404) {
                        $out['context'] = validContext($out['context']);
                    }

                    break; // Prefs-defined permlink scheme case.
            }
        } else {
            $out['s'] = 'default';
            $out['context'] = validContext($out['context']);
        }
    } else {
        // Messy mode, but prevent to get the id for file_downloads.
        $out['context'] = validContext($out['context']);

        if ($out['context'] == 'article' && $out['id'] && $out['s'] != 'file_download') {
            $rs = lookupByID($out['id']);
            $out['id'] = (!empty($rs['ID'])) ? $rs['ID'] : '';
            $out['s'] = (!empty($rs['Section'])) ? $rs['Section'] : '';
            $is_404 = (empty($out['s']) or empty($out['id']));
        }
    }

    // Existing category in messy or clean URL?
    if (!empty($out['c'])) {
        if (!ckCat($out['context'], $out['c'])) {
            $is_404 = true;
            $out['c'] = '';
        }
    }

    // Resolve AuthorID from Authorname.
    if ($out['author']) {
        $name = urldecode(strtolower(urlencode($out['author'])));

        $name = safe_field('name', 'txp_users', "RealName LIKE '".doSlash($out['author'])."'");

        if ($name) {
            $out['author'] = $name;
        } else {
            $out['author'] = '';
            $is_404 = true;
        }
    }

    // Allow article preview.
    if (gps('txpreview')) {
        doAuth();

        if (!has_privs('article.preview')) {
            txp_status_header('401 Unauthorized');
            exit(hed('401 Unauthorized', 1).graf(gTxt('restricted_area')));
        }

        global $nolog;

        $nolog = true;
        $rs = safe_row("ID AS id, Section AS s", 'textpattern', "ID = ".intval(gps('txpreview'))." LIMIT 1");

        if ($rs) {
            $is_404 = false;
            $out = array_merge($out, $rs);
        }
    }

    // Stats: found or not.
    $out['status'] = ($is_404 ? '404' : '200');

    $out['pg'] = is_numeric($out['pg']) ? intval($out['pg']) : '';
    $out['id'] = is_numeric($out['id']) ? intval($out['id']) : '';

    if ($out['s'] == 'file_download') {
        if (is_numeric($out['id'])) {
            // Undo the double-encoding workaround for .gz files;
            // @see filedownloadurl().
            if (!empty($out['filename'])) {
                $out['filename'] = preg_replace('/gz&$/i', 'gz', $out['filename']);
            }

            $fn = empty($out['filename']) ? '' : " AND filename = '".doSlash($out['filename'])."'";
            $rs = safe_row('*', 'txp_file', "id = ".intval($out['id'])." AND status = ".STATUS_LIVE." AND created <= ".now('created').$fn);
        }

        return (!empty($rs)) ? array_merge($out, $rs) : array('s' => 'file_download', 'file_error' => 404);
    }

    if (!$is_404) {
        $out['s'] = (empty($out['s'])) ? 'default' : $out['s'];
    }
    $s = $out['s'];
    $id = $out['id'];

    // Hackish.
    global $is_article_list;
    if (empty($id)) {
        $is_article_list = true;
    }

    // By this point we should know the section, so grab its page and CSS.
    if (txpinterface != 'css') {
        $rs = safe_row("page, css", "txp_section", "name = '".doSlash($s)."' LIMIT 1");
        $out['page'] = isset($rs['page']) ? $rs['page'] : '';
        $out['css'] = isset($rs['css']) ? $rs['css'] : '';
    }

    if (is_numeric($id) and !$is_404) {
        $a = safe_row("*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
            'textpattern',
            "ID = ".intval($id).(gps('txpreview') ? '' : " AND Status IN (".STATUS_LIVE.",".STATUS_STICKY.")"));

        if ($a) {
            $out['id_keywords'] = $a['Keywords'];
            $out['id_author']   = $a['AuthorID'];
            populateArticleData($a);

            $uExpires = $a['uExpires'];

            if ($uExpires and time() > $uExpires and !$publish_expired_articles) {
                $out['status'] = '410';
            }
        }
    }

    // These are deprecated as of Textpattern v1.0 - leaving them here for
    // plugin compatibility.
    $out['path_from_root'] = rhu;
    $out['pfr']            = rhu;

    $out['path_to_site']   = $path_to_site;
    $out['permlink_mode']  = $permlink_mode;
    $out['sitename']       = $sitename;

    return $out;
}

//    textpattern() is the function that assembles a page, based on
//    the variables passed to it by pretext();

// -------------------------------------------------------------

function textpattern()
{
    global $pretext, $prefs, $production_status, $siteurl, $has_article_tag;

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
    $html = parse_page($pretext['page']);

    if ($html === false) {
        txp_die(gTxt('unknown_section'), '404');
    }

    // Make sure the page has an article tag if necessary.
    if (!$has_article_tag and $production_status != 'live' and $pretext['context'] == 'article' and (!empty($pretext['id']) or !empty($pretext['c']) or !empty($pretext['q']) or !empty($pretext['pg']))) {
        trigger_error(gTxt('missing_article_tag', array('{page}' => $pretext['page'])));
    }

    restore_error_handler();

    header("Content-type: text/html; charset=utf-8");
    echo $html;

    callback_event('textpattern_end');
}

// -------------------------------------------------------------
function output_css($s = '', $n = '')
{
    $order = '';

    if ($n) {
        if (!is_scalar($n)) {
            txp_die('Not Found', 404);
        }

        $n = do_list_unique($n);
        $cssname = join("','", doSlash($n));

        if (count($n) > 1) {
            $order = " ORDER BY FIELD(name, '$cssname')";
        }
    } elseif ($s) {
        if (!is_scalar($s)) {
            txp_die('Not Found', 404);
        }

        $cssname = safe_field('css', 'txp_section', "name = '".doSlash($s)."'");
    }

    if (!empty($cssname)) {
        $css = join(n, safe_column_num('css', 'txp_css', "name IN ('$cssname')".$order));
        echo $css;
    }
}

// -------------------------------------------------------------
function output_file_download($filename)
{
    global $file_error, $file_base_path, $pretext;

    callback_event('file_download');

    if (!isset($file_error)) {
        $filename = sanitizeForFile($filename);
        $fullpath = build_file_path($file_base_path, $filename);

        if (is_file($fullpath)) {
            // Discard any error PHP messages.
            ob_clean();
            $filesize = filesize($fullpath);
            $sent = 0;
            header('Content-Description: File Download');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$filename.'"; size = "'.$filesize.'"');

            // Fix for IE6 PDF bug on servers configured to send cache headers.
            header('Cache-Control: private');
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
    global $pretext, $prefs;
    extract($pretext);
    extract($prefs);
    $customFields = getCustomFields();
    $customlAtts = array_null(array_flip($customFields));

    if ($iscustom) {

        // Custom articles must not render search results.
        $q = '';

        $extralAtts = array(
            'category'  => '',
            'section'   => '',
            'excerpted' => '',
            'author'    => '',
            'month'     => '',
            'expired'   => $publish_expired_articles,
            'id'        => '',
            'exclude'   => '',
        );
    } else {
        $extralAtts = array(
            'listform'     => '',
            'searchform'   => '',
            'searchall'    => 1,
            'searchsticky' => 0,
            'pageby'       => '',
            'pgonly'       => 0,
        );
    }

    // Getting attributes.
    $theAtts = lAtts(array(
        'form'          => 'default',
        'limit'         => 10,
        'sort'          => '',
        'sortby'        => '', // Deprecated in 4.0.4.
        'sortdir'       => '', // Deprecated in 4.0.4.
        'keywords'      => '',
        'time'          => 'past',
        'status'        => STATUS_LIVE,
        'allowoverride' => !$iscustom,
        'frontpage'     => !$iscustom,
        'match'         => 'Category1,Category2',
        'offset'        => 0,
        'wraptag'       => '',
        'break'         => '',
        'label'         => '',
        'labeltag'      => '',
        'class'         => '',
    ) + $customlAtts + $extralAtts, $atts);

    // For the txp:article tag, some attributes are taken from globals;
    // override them, then stash all filter attributes.
    if (!$iscustom) {
        $theAtts['category'] = ($c) ? $c : '';
        $theAtts['section'] = ($s && $s != 'default') ? $s : '';
        $theAtts['author'] = (!empty($author) ? $author : '');
        $theAtts['month'] = (!empty($month) ? $month : '');
        $theAtts['frontpage'] = ($theAtts['frontpage'] && $s && $s == 'default');
        $theAtts['excerpted'] = 0;
        $theAtts['exclude'] = 0;
        $theAtts['expired'] = $publish_expired_articles;

        filterAtts($theAtts);
    }

    extract($theAtts);

    // If a listform is specified, $thing is for doArticle() - hence ignore here.
    if (!empty($listform)) {
        $thing = '';
    }

    $pageby = (empty($pageby) ? $limit : $pageby);

    // Treat sticky articles differently wrt search filtering, etc.
    $status = in_array(strtolower($status), array('sticky', STATUS_STICKY)) ? STATUS_STICKY : STATUS_LIVE;
    $issticky = ($status == STATUS_STICKY);

    // Give control to search, if necessary.
    if ($q && !$issticky) {
        include_once txpath.'/publish/search.php';

        $s_filter = ($searchall ? filterSearch() : '');
        $q = trim($q);
        $quoted = ($q[0] === '"') && ($q[strlen($q) - 1] === '"');
        $q = doSlash($quoted ? trim(trim($q, '"')) : $q);

        // Searchable article fields are limited to the columns of the
        // textpattern table and a matching fulltext index must exist.
        $cols = do_list_unique($searchable_article_fields);

        if (empty($cols) or $cols[0] == '') {
            $cols = array('Title', 'Body');
        }

        $score = ", MATCH (`".join("`, `", $cols)."`) AGAINST ('$q') AS score";
        $search_terms = preg_replace('/\s+/', ' ', str_replace(array('\\', '%', '_', '\''), array('\\\\', '\\%', '\\_', '\\\''), $q));

        if ($quoted || empty($m) || $m === 'exact') {
            for ($i = 0; $i < count($cols); $i++) {
                $cols[$i] = "`$cols[$i]` LIKE '%$search_terms%'";
            }
        } else {
            $colJoin = ($m === 'any') ? "OR" : "AND";
            $search_terms = explode(' ', $search_terms);
            for ($i = 0; $i < count($cols); $i++) {
                $like = array();
                foreach ($search_terms as $search_term) {
                    $like[] = "`$cols[$i]` LIKE '%$search_term%'";
                }
                $cols[$i] = "(".join(" $colJoin ", $like).")";
            }
        }

        $cols = join(" OR ", $cols);
        $search = " AND ($cols) $s_filter";

        // searchall=0 can be used to show search results for the current
        // section only.
        if ($searchall) {
            $section = '';
        }

        if (!$sort) {
            $sort = "score DESC";
        }
    } else {
        $score = $search = '';

        if (!$sort) {
            $sort = "Posted DESC";
        }
    }

    // For backwards compatibility. sortby and sortdir are deprecated.
    if ($sortby) {
        trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortby')), E_USER_NOTICE);

        if (!$sortdir) {
            $sortdir = "DESC";
        } else {
            trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortdir')), E_USER_NOTICE);
        }

        $sort = "$sortby $sortdir";
    } elseif ($sortdir) {
        trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortdir')), E_USER_NOTICE);
        $sort = "Posted $sortdir";
    }

    // Building query parts.
    $frontpage = ($frontpage and (!$q or $issticky)) ? filterFrontPage() : '';
    $category  = join("','", doSlash(do_list_unique($category)));
    $categories = array();
    $match = do_list_unique($match);

    if (in_array('Category1', $match)) {
        $categories[] = "Category1 IN ('$category')";
    }

    if (in_array('Category2', $match)) {
        $categories[] = "Category2 IN ('$category')";
    }

    $categories = join(" OR ", $categories);
    $category  = (!$category or !$categories)  ? '' : " AND ($categories)";
    $section   = (!$section)   ? '' : " AND Section IN ('".join("','", doSlash(do_list_unique($section)))."')";
    $excerpted = (!$excerpted) ? '' : " AND Excerpt !=''";
    $author    = (!$author)    ? '' : " AND AuthorID IN ('".join("','", doSlash(do_list_unique($author)))."')";
    $month     = (!$month)     ? '' : " AND Posted LIKE '".doSlash($month)."%'";
    $ids = $id ? array_map('intval', do_list_unique($id)) : array();
    $exclude = $exclude ? array_map('intval', do_list_unique($exclude)) : array();
    $id        = ((!$id)        ? '' : " AND ID IN (".join(',', $ids).")")
        .((!$exclude)   ? '' : " AND ID NOT IN (".join(',', $exclude).")");

    switch ($time) {
        case 'any':
            $time = "";
            break;
        case 'future':
            $time = " AND Posted > ".now('posted');
            break;
        default:
            $time = " AND Posted <= ".now('posted');
    }

    if (!$expired) {
        $time .= " AND (".now('expires')." <= Expires OR Expires IS NULL)";
    }

    $custom = '';

    if ($customFields) {
        foreach ($customFields as $cField) {
            if (isset($atts[$cField])) {
                $customPairs[$cField] = $atts[$cField];
            }
        }

        if (!empty($customPairs)) {
            $custom = buildCustomSql($customFields, $customPairs);
        }
    }

    // Allow keywords for no-custom articles. That tagging mode, you know.
    if ($keywords) {
        $keys = doSlash(do_list_unique($keywords));

        foreach ($keys as $key) {
            $keyparts[] = "FIND_IN_SET('".$key."', Keywords)";
        }

        $keywords = " AND (".join(' or ', $keyparts).")";
    }

    if ($q && $searchsticky) {
        $statusq = " AND Status >= ".STATUS_LIVE;
    } elseif ($id) {
        $statusq = " AND Status >= ".STATUS_LIVE;
    } else {
        $statusq = " AND Status = ".intval($status);
    }

    $where = "1 = 1".$statusq.$time.
        $search.$id.$category.$section.$excerpted.$month.$author.$keywords.$custom.$frontpage;

    // Do not paginate if we are on a custom list.
    if (!$iscustom and !$issticky) {
        $grand_total = safe_count('textpattern', $where);
        $total = $grand_total - $offset;
        $numPages = ceil($total / $pageby);
        $pg = (!$pg) ? 1 : $pg;
        $pgoffset = $offset + (($pg - 1) * $pageby);

        // Send paging info to txp:newer and txp:older.
        $pageout['pg']          = $pg;
        $pageout['numPages']    = $numPages;
        $pageout['s']           = $s;
        $pageout['c']           = $c;
        $pageout['context']     = 'article';
        $pageout['grand_total'] = $grand_total;
        $pageout['total']       = $total;

        global $thispage;

        if (empty($thispage)) {
            $thispage = $pageout;
        }

        if ($pgonly) {
            return;
        }
    } else {
        $pgoffset = $offset;
    }

    // Preserve order of custom article ids unless 'sort' attribute is set.
    if (!empty($atts['id']) && empty($atts['sort'])) {
        $safe_sort = "FIELD(id, ".join(',', $ids).")";
    } else {
        $safe_sort = doSlash($sort);
    }

    $rs = safe_rows_start("*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod".$score,
        'textpattern',
        "$where ORDER BY $safe_sort LIMIT ".intval($pgoffset).", ".intval($limit));

    // Get the form name.
    if ($q && !$issticky) {
        $fname = ($searchform ? $searchform : 'search_results');
    } else {
        $fname = (!empty($listform) ? $listform : $form);
    }

    if ($rs) {
        $count = 0;
        $last = numRows($rs);

        $articles = array();

        while ($a = nextRow($rs)) {
            ++$count;
            populateArticleData($a);
            global $thisarticle, $uPosted, $limit;
            $thisarticle['is_first'] = ($count == 1);
            $thisarticle['is_last'] = ($count == $last);

            // Article form preview.
            if (txpinterface === 'admin' && ps('Form')) {
                doAuth();

                if (!has_privs('form')) {
                    txp_status_header('401 Unauthorized');
                    exit(hed('401 Unauthorized', 1).graf(gTxt('restricted_area')));
                }

                $articles[] = parse(gps('Form'));
            } elseif ($allowoverride and $a['override_form']) {
                $articles[] = parse_form($a['override_form']);
            } else {
                $articles[] = ($thing) ? parse($thing) : parse_form($fname);
            }

            // Sending these to paging_link(); Required?
            $uPosted = $a['uPosted'];

            unset($GLOBALS['thisarticle']);
        }

        return doLabel($label, $labeltag).doWrap($articles, $wraptag, $break, $class);
    }
}

// -------------------------------------------------------------

function doArticle($atts, $thing = null)
{
    global $pretext, $prefs, $thisarticle;
    extract($prefs);
    extract($pretext);

    extract(gpsa(array(
        'parentid',
        'preview',
    )));

    $theAtts = lAtts(array(
        'allowoverride' => '1',
        'form'          => 'default',
        'status'        => STATUS_LIVE,
        'pgonly'        => 0,
    ), $atts, 0);
    extract($theAtts);

    // Save *all* atts to get hold of the current article filter criteria.
    filterAtts($atts);

    // No output required.
    if ($pgonly) {
        return '';
    }

    // If a form is specified, $thing is for doArticles() - hence ignore
    // $thing here.
    if (!empty($atts['form'])) {
        $thing = '';
    }

    if ($status) {
        $status = in_array(strtolower($status), array('sticky', STATUS_STICKY)) ? STATUS_STICKY : STATUS_LIVE;
    }

    if (empty($thisarticle) or $thisarticle['thisid'] != $id) {
        $id = assert_int($id);
        $thisarticle = null;

        $q_status = ($status ? "AND Status = ".intval($status) : "AND Status IN (".STATUS_LIVE.",".STATUS_STICKY.")");

        $rs = safe_row("*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
            'textpattern',
            "ID = $id $q_status LIMIT 1");

        if ($rs) {
            extract($rs);
            populateArticleData($rs);
        }
    }

    if (!empty($thisarticle) and ($thisarticle['status'] == $status or gps('txpreview'))) {
        extract($thisarticle);
        $thisarticle['is_first'] = 1;
        $thisarticle['is_last'] = 1;

        if ($allowoverride and $override_form) {
            $article = parse_form($override_form);
        } else {
            $article = ($thing) ? parse($thing) : parse_form($form);
        }

        if ($use_comments and $comments_auto_append) {
            $article .= parse_form('comments_display');
        }

        unset($GLOBALS['thisarticle']);

        return $article;
    }
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
    foreach (array('article', 'image', 'file', 'link') as $type) {
        $valid[gTxt($type.'_context')] = $type;
    }

    return isset($valid[$context]) ? $valid[$context] : 'article';
}

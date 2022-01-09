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

/**
 * Handles Atom feeds.
 *
 * @package XML
 */

/**
 * @ignore
 */

define("t_texthtml", ' type="text/html"');

/**
 * @ignore
 */

define("t_text", ' type="text"');

/**
 * @ignore
 */

define("t_html", ' type="html"');

/**
 * @ignore
 */

define("t_xhtml", ' type="xhtml"');

/**
 * @ignore
 */

define('t_appxhtml', ' type="xhtml"');

/**
 * @ignore
 */

define("r_relalt", ' rel="alternate"');

/**
 * @ignore
*/

define("r_relself", ' rel="self"');

/**
 * Generates and outputs an Atom feed.
 *
 * This function can only be called once on a page. It outputs an Atom feed
 * based on the requested URL parameters. Accepts HTTP GET parameters 'limit',
 * 'area', 'section' and 'category'.
 */

function atom()
{
    global $prefs, $txp_sections;
    set_error_handler('feedErrorHandler');
    ob_clean();
    extract($prefs);

    extract(doSlash(gpsa(array('limit', 'area'))));

    // Build filter criteria from a comma-separated list of sections
    // and categories.
    $feed_filter_limit = get_pref('feed_filter_limit', 10);
    $section = gps('section');
    $category = gps('category');

    if (!is_scalar($section) || !is_scalar($category)) {
        txp_die('Not Found', 404);
    }

    $section = ($section ? array_slice(do_list_unique($section), 0, $feed_filter_limit) : array());
    $category = ($category ? array_slice(do_list_unique($category), 0, $feed_filter_limit) : array());
    $st = array();

    foreach ($section as $s) {
        $st[] = fetch_section_title($s);
    }

    $ct = array();

    foreach ($category as $c) {
        $ct[] = fetch_category_title($c);
    }

    $sitename .= ($section) ? ' - '.join(' - ', $st) : '';
    $sitename .= ($category) ? ' - '.join(' - ', $ct) : '';

    $pub = safe_row("RealName, email", 'txp_users', "privs = 1");

    // Feed header.
    $out[] = tag(htmlspecialchars($sitename), 'title', t_text);
    $out[] = tag(htmlspecialchars($site_slogan), 'subtitle', t_text);
    $out[] = '<link'.r_relself.' href="'.pagelinkurl(array(
        'atom'     => 1,
        'area'     => $area,
        'section'  => $section,
        'category' => $category,
        'limit'    => $limit,
    )).'" />';
    $out[] = '<link'.r_relalt.t_texthtml.' href="'.hu.'" />';

    // Atom feeds with mail or domain name.
    $dn = explode('/', $siteurl);
    $mail_or_domain = ($use_mail_on_feeds_id) ? eE($blog_mail_uid) : $dn[0];
    $out[] = tag('tag:'.$mail_or_domain.','.$blog_time_uid.':'.$blog_uid.(($section) ? '/'.join(',', $section) : '').(($category) ? '/'.join(',', $category) : ''), 'id');

    $out[] = tag('Textpattern', 'generator', ' uri="https://textpattern.com/" version="'.$version.'"');
    $out[] = tag(safe_strftime("w3cdtf", strtotime($lastmod)), 'updated');

    $auth[] = tag($pub['RealName'], 'name');
    $auth[] = ($include_email_atom) ? tag(eE($pub['email']), 'email') : '';
    $auth[] = tag(hu, 'uri');

    $out[] = tag(n.t.t.join(n.t.t, $auth).n.t, 'author');
    $out[] = callback_event('atom_head');

    // Feed items.
    $articles = array();
    $dates = array();
    $section = doSlash($section);
    $category = doSlash($category);
    $limit = ($limit) ? $limit : $rss_how_many;
    $limit = intval(min($limit, max(100, $rss_how_many)));

    if (!$area or $area == 'article') {
        $sfilter = (!empty($section)) ? "AND Section IN ('".join("','", $section)."')" : '';
        $cfilter = (!empty($category)) ? "AND (Category1 IN ('".join("','", $category)."') OR Category2 IN ('".join("','", $category)."'))" : '';

        $query = array($sfilter, $cfilter);

        $rs = array_filter(array_column($txp_sections, 'in_rss', 'name'));

        if ($rs) {
            $query[] = 'AND Section IN('.join(',', quote_list(array_keys($rs))).')';
        }

        if ($atts = callback_event('feed_filter')) {
            is_array($atts) or $atts = splat(trim($atts));
        } else {
            $atts = array();
        }

        $atts = filterAtts($atts, true);
        $where = $atts['*'].' '.join(' ', $query);

        $rs = safe_rows_start(
            "*,
            ID AS thisid,
            UNIX_TIMESTAMP(Posted) AS uPosted,
            UNIX_TIMESTAMP(Expires) AS uExpires,
            UNIX_TIMESTAMP(LastMod) AS uLastMod",
            'textpattern',
            $where." ORDER BY Posted DESC LIMIT $limit"
        );

        if ($rs) {
            $fields = array_fill_keys(array('thisid', 'authorid', 'title', 'body', 'excerpt', 'category1', 'category2', 'posted', 'modified', 'comments_count'), null);

            while ($a = nextRow($rs)) {
                // In case $GLOBALS['thisarticle'] is unset
                global $thisarticle;
                populateArticleData($a);
                $cb = callback_event('atom_entry');
                extract(array_intersect_key($thisarticle, $fields));
                $e = array();

                if ($show_comment_count_in_feed) {
                    $count = ($comments_count > 0) ? ' ['.$comments_count.']' : '';
                } else {
                    $count = '';
                }

                $thisauthor = get_author_name($authorid);

                $e['thisauthor'] = tag(n.t.t.t.tag(htmlspecialchars($thisauthor), 'name').n.t.t, 'author');

                $e['issued'] = tag(safe_strftime('w3cdtf', $posted), 'published');
                $e['modified'] = tag(safe_strftime('w3cdtf', $modified), 'updated');

                $escaped_title = htmlspecialchars($title);
                $e['title'] = tag($escaped_title.$count, 'title', t_html);

                $permlink = permlinkurl($thisarticle);
                $e['link'] = '<link'.r_relalt.t_texthtml.' href="'.$permlink.'" />';

                $e['id'] = tag('tag:'.$mail_or_domain.','.$a['feed_time'].':'.$blog_uid.'/'.$a['uid'], 'id');

                $e['category1'] = (trim($category1) ? '<category term="'.htmlspecialchars($category1).'" />' : '');
                $e['category2'] = (trim($category2) ? '<category term="'.htmlspecialchars($category2).'" />' : '');

                $summary = trim(replace_relative_urls(parse($excerpt), $permlink));
                $content = '';

                if ($syndicate_body_or_excerpt) {
                    // Short feed: use body as summary if there's no excerpt.
                    if ($summary === '') {
                        $summary = trim(replace_relative_urls(parse($body), $permlink));
                    }
                } else {
                    $content = trim(replace_relative_urls(parse($body), $permlink));
                }

                if ($content !== '') {
                    $e['content'] = tag(escape_cdata($content), 'content', t_html);
                }

                if ($summary !== '') {
                    $e['summary'] = tag(escape_cdata($summary), 'summary', t_html);
                }

                $articles[$thisid] = tag(n.t.t.join(n.t.t, $e).n.t.$cb, 'entry');

                $dates[$thisid] = $modified;
            }
        }
    } elseif ($area == 'link') {
        $cfilter = ($category) ? "category IN ('".join("','", $category)."')" : '1';

        $rs = safe_rows_start("*, UNIX_TIMESTAMP(date) AS uDate", 'txp_link', "$cfilter ORDER BY date DESC, id DESC LIMIT $limit");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);
                $e = array();

                $e['title'] = tag(htmlspecialchars($linkname), 'title', t_html);
                $e['content'] = tag(escape_cdata($description), 'content', t_html);

                $url = (preg_replace("/^\/(.*)/", "https?://$siteurl/$1", $url));
                $url = preg_replace("/&((?U).*)=/", "&amp;\\1=", $url);
                $e['link'] = '<link'.r_relalt.t_texthtml.' href="'.$url.'" />';

                $e['issued'] = tag(safe_strftime('w3cdtf', $uDate), 'published');
                $e['modified'] = tag(safe_strftime('w3cdtf', $uDate), 'updated');
                $e['id'] = tag('tag:'.$mail_or_domain.','.safe_strftime('%Y-%m-%d', $uDate).':'.$blog_uid.'/'.$id, 'id');

                $articles[$id] = tag(n.t.t.join(n.t.t, $e).n.t, 'entry');

                $dates[$id] = $uDate;
            }
        }
    }

    if (!$articles) {
        if ($section) {
            if (safe_field("name", 'txp_section', "name IN ('".join("','", $section)."')") == false) {
                txp_die(gTxt('404_not_found'), '404');
            }
        } elseif ($category) {
            switch ($area) {
                case 'link':
                    if (safe_field("id", 'txp_category', "name = '$category' AND type = 'link'") == false) {
                        txp_die(gTxt('404_not_found'), '404');
                    }

                    break;
                case 'article':
                default:
                    if (safe_field("id", 'txp_category', "name IN ('".join("','", $category)."') AND type = 'article'") == false) {
                        txp_die(gTxt('404_not_found'), '404');
                    }

                    break;
            }
        }
    } else {
        header('Vary: A-IM, If-None-Match, If-Modified-Since');

        handle_lastmod(max($dates));

        // Get timestamp from request caching headers
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $hims = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            $imsd = ($hims) ? strtotime($hims) : 0;
        } elseif (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $hinm = trim(trim($_SERVER['HTTP_IF_NONE_MATCH']), '"');
            $hinm_apache_gzip_workaround = explode('-gzip', $hinm);
            $hinm_apache_gzip_workaround = $hinm_apache_gzip_workaround[0];
            $inmd = ($hinm) ? base_convert($hinm_apache_gzip_workaround, 32, 10) : 0;
        }

        if (isset($imsd) || isset($inmd)) {
            $clfd = max(intval($imsd), intval($inmd));
        }

        $cutarticles = false;

        if (isset($_SERVER["HTTP_A_IM"]) &&
            strpos($_SERVER["HTTP_A_IM"], "feed") &&
            isset($clfd) && $clfd > 0) {

            // Remove articles with timestamps older than the request timestamp
            foreach ($articles as $id => $entry) {
                if ($dates[$id] <= $clfd) {
                    unset($articles[$id]);
                    $cutarticles = true;
                }
            }
        }

        // Indicate that instance manipulation was applied
        if ($cutarticles) {
            header("HTTP/1.1 226 IM Used");
            header("Cache-Control: IM", false);
            header("IM: feed", false);
        }
    }

    $out = array_merge($out, $articles);
    $xmlns = '';

    $feeds_namespaces = parse_ini_string(get_pref('feeds_namespaces'));
    is_array($feeds_namespaces) or $feeds_namespaces = array();

    foreach ($feeds_namespaces as $ns => $url) {
        $xmlns .= ' xmlns:'.$ns.'="'.$url.'"';
    }

    header('Content-Type: application/atom+xml; charset=utf-8');

    return
        '<?xml version="1.0" encoding="UTF-8"?>'.n.
        '<feed xml:lang="'.txpspecialchars($language).'" xmlns="http://www.w3.org/2005/Atom"'.$xmlns.'>'.n.t.
        join(n.t, $out).n.
        '</feed>';
}

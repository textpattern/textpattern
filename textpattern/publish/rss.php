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
 * Handles RSS feeds.
 *
 * @package XML
 */

/**
 * Generates and returns an RSS feed.
 *
 * This function can only be called once on a page. It send HTTP
 * headers and returns an RSS feed based on the requested URL parameters.
 * Accepts HTTP GET parameters 'limit', 'area', 'section' and 'category'.
 *
 * @return string XML
 */

function rss()
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
    $dn = explode('/', $siteurl);
    $mail_or_domain = ($use_mail_on_feeds_id) ? eE($blog_mail_uid) : $dn[0];

    // Feed header.
    $out[] = tag('https://textpattern.com/?v='.$version, 'generator');
    $out[] = tag(doSpecial($sitename), 'title');
    $out[] = tag(hu, 'link');
    $out[] = '<atom:link href="'.pagelinkurl(array(
        'rss'      => 1,
        'area'     => $area,
        'section'  => $section,
        'category' => $category,
        'limit'    => $limit,
    )).'" rel="self" type="application/rss+xml" />';
    $out[] = tag(doSpecial($site_slogan), 'description');
    $out[] = tag(safe_strftime('rss', strtotime($lastmod)), 'pubDate');
    $out[] = callback_event('rss_head');

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
            $fields = array_fill_keys(array('thisid', 'authorid', 'title', 'body', 'excerpt', 'posted', 'modified', 'comments_count'), null);

            while ($a = nextRow($rs)) {
                // In case $GLOBALS['thisarticle'] is unset
                global $thisarticle;
                populateArticleData($a);

                $cb = callback_event('rss_entry');
                extract(array_intersect_key($thisarticle, $fields));

                if ($show_comment_count_in_feed) {
                    $count = ($comments_count > 0) ? ' ['.$comments_count.']' : '';
                } else {
                    $count = '';
                }

                $permlink = permlinkurl($thisarticle);
                $thisauthor = get_author_name($authorid);
                $Title = escape_title(preg_replace("/&(?![#a-z0-9]+;)/i", "&amp;", html_entity_decode(strip_tags($title), ENT_QUOTES, 'UTF-8'))).$count;
                $summary = trim(parse($excerpt));
                $content = '';

                if ($syndicate_body_or_excerpt) {
                    // Short feed: use body as summary if there's no excerpt.
                    if ($summary === '') {
                        $summary = trim(parse($body));
                    }
                } else {
                    $content = trim(parse($body));
                }

                $item =
                    n.t.t.tag($Title, 'title').
                    ($summary !== '' ? n.t.t.tag(escape_cdata($summary), 'description') : '').
                    ($content !== '' ? n.t.t.tag(escape_cdata($content).n, 'content:encoded') : '').
                    n.t.t.tag($permlink, 'link').
                    n.t.t.tag(safe_strftime('rss', $posted), 'pubDate').
                    n.t.t.tag(htmlspecialchars($thisauthor), 'dc:creator').
                    n.t.t.tag('tag:'.$mail_or_domain.','.$a['feed_time'].':'.$blog_uid.'/'.$a['uid'], 'guid', ' isPermaLink="false"').n.
                    $cb;

                $articles[$thisid] = tag(replace_relative_urls($item, $permlink).t, 'item');

                $dates[$thisid] = $modified;
            }
        }
    } elseif ($area == 'link') {
        $cfilter = ($category) ? "category IN ('".join("','", $category)."')" : '1';

        $rs = safe_rows_start("*, UNIX_TIMESTAMP(date) AS uDate", 'txp_link', "$cfilter ORDER BY date DESC, id DESC LIMIT $limit");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);
                $item =
                    n.t.t.tag(doSpecial($linkname), 'title').
                    (trim($description) ? n.t.t.tag(doSpecial($description), 'description') : '').
                    n.t.t.tag(doSpecial($url), 'link').
                    n.t.t.tag(safe_strftime('rss', $uDate), 'pubDate').n;
                $articles[$id] = tag($item.t, 'item');

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
    $feeds_namespaces += array(
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'content' => 'http://purl.org/rss/1.0/modules/content/',
        'atom' => 'http://www.w3.org/2005/Atom'
    );

    foreach ($feeds_namespaces as $ns => $url) {
        $xmlns .= ' xmlns:'.$ns.'="'.$url.'"';
    }

    header('Content-Type: application/rss+xml; charset=utf-8');

    return
        '<?xml version="1.0" encoding="UTF-8"?>'.n.
        '<rss version="2.0"'.$xmlns.'>'.n.
        tag(n.t.join(n.t, $out).n, 'channel').n.
        '</rss>';
}

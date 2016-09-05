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
    global $prefs;
    set_error_handler('feedErrorHandler');
    ob_clean();
    extract($prefs);

    extract(doSlash(gpsa(array(
        'limit',
        'area',
    ))));

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
    $out[] = tag('http://textpattern.com/?v='.$version, 'generator');
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
    $last = fetch("UNIX_TIMESTAMP(val)", 'txp_prefs', 'name', 'lastmod');
    $out[] = tag(safe_strftime('rfc822', $last), 'pubDate');
    $out[] = callback_event('rss_head');

    // Feed items.
    $articles = array();
    $section = doSlash($section);
    $category = doSlash($category);

    if (!$area or $area == 'article') {
        $sfilter = (!empty($section)) ? "AND Section IN ('".join("','", $section)."')" : '';
        $cfilter = (!empty($category)) ? "AND (Category1 IN ('".join("','", $category)."') OR Category2 IN ('".join("','", $category)."'))" : '';
        $limit = ($limit) ? $limit : $rss_how_many;
        $limit = intval(min($limit, max(100, $rss_how_many)));

        $frs = safe_column("name", 'txp_section', "in_rss != '1'");

        if ($frs) {
            foreach ($frs as $f) {
                $query[] = "AND Section != '".doSlash($f)."'";
            }
        }

        $query[] = $sfilter;
        $query[] = $cfilter;

        $expired = ($publish_expired_articles) ? " " : " AND (".now('expires')." <= Expires OR Expires IS NULL) ";
        $rs = safe_rows_start(
            "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(LastMod) AS uLastMod, UNIX_TIMESTAMP(Expires) AS uExpires, ID AS thisid",
            'textpattern',
            "Status = 4 ".join(' ', $query).
            "AND Posted < ".now('posted').$expired." ORDER BY Posted DESC LIMIT $limit"
        );

        if ($rs) {
            while ($a = nextRow($rs)) {
                // In case $GLOBALS['thisarticle'] is unset
                global $thisarticle;
                extract($a);
                populateArticleData($a);

                $cb = callback_event('rss_entry');

                $a['posted'] = $uPosted;
                $a['expires'] = $uExpires;

                $permlink = permlinkurl($a);
                $summary = trim(replace_relative_urls(parse($thisarticle['excerpt']), $permlink));
                $content = trim(replace_relative_urls(parse($thisarticle['body']), $permlink));

                if ($syndicate_body_or_excerpt) {
                    // Short feed: use body as summary if there's no excerpt.
                    if (!trim($summary)) {
                        $summary = $content;
                    }

                    $content = '';
                }

                if ($show_comment_count_in_feed) {
                    $count = ($comments_count > 0) ? ' ['.$comments_count.']' : '';
                } else {
                    $count = '';
                }

                $Title = escape_title(preg_replace("/&(?![#a-z0-9]+;)/i", "&amp;", html_entity_decode(strip_tags($Title), ENT_QUOTES, 'UTF-8'))).$count;

                $thisauthor = get_author_name($AuthorID);

                $item = tag($Title, 'title').n.
                    (trim($summary) ? tag(n.escape_cdata($summary).n, 'description').n : '').
                    (trim($content) ? tag(n.escape_cdata($content).n, 'content:encoded').n : '').
                    tag($permlink, 'link').n.
                    tag(safe_strftime('rfc822', $a['posted']), 'pubDate').n.
                    tag(htmlspecialchars($thisauthor), 'dc:creator').n.
                    tag('tag:'.$mail_or_domain.','.$feed_time.':'.$blog_uid.'/'.$uid, 'guid', ' isPermaLink="false"').n.
                    $cb;

                $articles[$ID] = tag($item, 'item');

                $etags[$ID] = strtoupper(dechex(crc32($articles[$ID])));
                $dates[$ID] = $uPosted;
            }
        }
    } elseif ($area == 'link') {
        $cfilter = ($category) ? "category IN ('".join("','", $category)."')"  : '1';
        $limit = ($limit) ? $limit : $rss_how_many;
        $limit = intval(min($limit, max(100, $rss_how_many)));

        $rs = safe_rows_start("*, UNIX_TIMESTAMP(date) AS uDate", 'txp_link', "$cfilter ORDER BY date DESC LIMIT $limit");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);
                $item =
                    tag(doSpecial($linkname), 'title').n.
                    tag(doSpecial($description), 'description').n.
                    tag(doSpecial($url), 'link').n.
                    tag(safe_strftime('rfc822', $uDate), 'pubDate');
                $articles[$id] = tag($item, 'item');

                $etags[$id] = strtoupper(dechex(crc32($articles[$id])));
                $dates[$id] = $date;
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
        handle_lastmod();
        $hims = serverset('HTTP_IF_MODIFIED_SINCE');
        $imsd = ($hims) ? strtotime($hims) : 0;

        if (is_callable('apache_request_headers')) {
            $headers = apache_request_headers();

            if (isset($headers["A-IM"])) {
                $canaim = strpos($headers["A-IM"], "feed");
            } else {
                $canaim = false;
            }
        } else {
            $canaim = false;
        }

        $hinm = stripslashes(serverset('HTTP_IF_NONE_MATCH'));

        $cutarticles = false;

        if ($canaim !== false) {
            foreach ($articles as $id => $thing) {
                if (strpos($hinm, $etags[$id]) !== false) {
                    unset($articles[$id]);
                    $cutarticles = true;
                    $cut_etag = true;
                }

                if ($dates[$id] < $imsd) {
                    unset($articles[$id]);
                    $cutarticles = true;
                    $cut_time = true;
                }
            }
        }

        if (isset($cut_etag) && isset($cut_time)) {
            header("Vary: If-None-Match, If-Modified-Since");
        } elseif (isset($cut_etag)) {
            header("Vary: If-None-Match");
        } elseif (isset($cut_time)) {
            header("Vary: If-Modified-Since");
        }

        $etag = @join("-", $etags);

        if (strstr($hinm, $etag)) {
            txp_status_header('304 Not Modified');
            exit(0);
        }

        if ($cutarticles) {
            // header("HTTP/1.1 226 IM Used");
            // This should be used as opposed to 200, but Apache doesn't like it.
            header("Cache-Control: no-store, im");
            header("IM: feed");
        }
    }

    $out = array_merge($out, $articles);

    header("Content-Type: application/rss+xml; charset=utf-8");

    if (isset($etag)) {
        header('ETag: "'.$etag.'"');
    }

    return
        '<?xml version="1.0" encoding="utf-8"?>'.n.
        '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">'.n.
        tag(join(n, $out), 'channel').n.
        '</rss>';
}

/**
 * Converts HTML entities to UTF-8 characters.
 *
 * @param      string $toUnicode
 * @return     string
 * @deprecated in 4.0.4
 */

function rss_safe_hed($toUnicode)
{
    if (version_compare(phpversion(), "5.0.0", ">=")) {
        $str =  html_entity_decode($toUnicode, ENT_QUOTES, "UTF-8");
    } else {
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        foreach ($trans_tbl as $k => $v) {
            $ttr[$v] = utf8_encode($k);
        }
        $str = strtr($toUnicode, $ttr);
    }

    return $str;
}

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
    global $prefs;
    set_error_handler('feedErrorHandler');
    ob_clean();
    extract($prefs);

    $last = fetch("UNIX_TIMESTAMP(val)", 'txp_prefs', 'name', 'lastmod');

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

    $out[] = tag('Textpattern', 'generator', ' uri="http://textpattern.com/" version="'.$version.'"');
    $out[] = tag(safe_strftime("w3cdtf", $last), 'updated');

    $auth[] = tag($pub['RealName'], 'name');
    $auth[] = ($include_email_atom) ? tag(eE($pub['email']), 'email') : '';
    $auth[] = tag(hu, 'uri');

    $out[] = tag(n.t.t.join(n.t.t, $auth).n, 'author');
    $out[] = callback_event('atom_head');

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

        $query = array();

        foreach ($frs as $f) {
            $query[] = "AND Section != '".doSlash($f)."'";
        }

        $query[] = $sfilter;
        $query[] = $cfilter;

        $expired = ($publish_expired_articles) ? " " : " AND (".now('expires')." <= Expires OR Expires IS NULL) ";
        $rs = safe_rows_start(
            "*,
            ID AS thisid,
            UNIX_TIMESTAMP(Posted) AS uPosted,
            UNIX_TIMESTAMP(Expires) AS uExpires,
            UNIX_TIMESTAMP(LastMod) AS uLastMod",
            'textpattern',
            "Status = 4 AND Posted <= ".now('posted').$expired.join(' ', $query).
            "ORDER BY Posted DESC LIMIT $limit"
        );

        if ($rs) {
            while ($a = nextRow($rs)) {
                // In case $GLOBALS['thisarticle'] is unset
                global $thisarticle;
                extract($a);
                populateArticleData($a);
                $cb = callback_event('atom_entry');
                $e = array();

                $a['posted'] = $uPosted;
                $a['expires'] = $uExpires;

                if ($show_comment_count_in_feed) {
                    $count = ($comments_count > 0) ? ' ['.$comments_count.']' : '';
                } else {
                    $count = '';
                }

                $thisauthor = get_author_name($AuthorID);

                $e['thisauthor'] = tag(n.t.t.t.tag(htmlspecialchars($thisauthor), 'name').n.t.t, 'author');

                $e['issued'] = tag(safe_strftime('w3cdtf', $uPosted), 'published');
                $e['modified'] = tag(safe_strftime('w3cdtf', $uLastMod), 'updated');

                $escaped_title = htmlspecialchars($Title);
                $e['title'] = tag($escaped_title.$count, 'title', t_html);

                $permlink = permlinkurl($a);
                $e['link'] = '<link'.r_relalt.t_texthtml.' href="'.$permlink.'" />';

                $e['id'] = tag('tag:'.$mail_or_domain.','.$feed_time.':'.$blog_uid.'/'.$uid, 'id');

                $e['category1'] = (trim($Category1) ? '<category term="'.htmlspecialchars($Category1).'" />' : '');
                $e['category2'] = (trim($Category2) ? '<category term="'.htmlspecialchars($Category2).'" />' : '');

                $summary = trim(replace_relative_urls(parse($thisarticle['excerpt']), $permlink));
                $content = trim(replace_relative_urls(parse($thisarticle['body']), $permlink));

                if ($syndicate_body_or_excerpt) {
                    // Short feed: use body as summary if there's no excerpt.
                    if (!trim($summary)) {
                        $summary = $content;
                    }
                    $content = '';
                }

                if (trim($content)) {
                    $e['content'] = tag(n.escape_cdata($content).n, 'content', t_html);
                }

                if (trim($summary)) {
                    $e['summary'] = tag(n.escape_cdata($summary).n, 'summary', t_html);
                }

                $articles[$ID] = tag(n.t.t.join(n.t.t, $e).n.$cb, 'entry');

                $etags[$ID] = strtoupper(dechex(crc32($articles[$ID])));
                $dates[$ID] = $uLastMod;
            }
        }
    } elseif ($area == 'link') {
        $cfilter = ($category) ? "category in ('".join("','", $category)."')" : '1';
        $limit = ($limit) ? $limit : $rss_how_many;
        $limit = intval(min($limit, max(100, $rss_how_many)));

        $rs = safe_rows_start("*", 'txp_link', "$cfilter ORDER BY date DESC, id DESC LIMIT $limit");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);

                $e['title'] = tag(htmlspecialchars($linkname), 'title', t_html);
                $e['content'] = tag(n.htmlspecialchars($description).n, 'content', t_html);

                $url = (preg_replace("/^\/(.*)/", "https?://$siteurl/$1", $url));
                $url = preg_replace("/&((?U).*)=/", "&amp;\\1=", $url);
                $e['link'] = '<link'.r_relalt.t_texthtml.' href="'.$url.'" />';

                $e['issued'] = tag(safe_strftime('w3cdtf', strtotime($date)), 'published');
                $e['modified'] = tag(gmdate('Y-m-d\TH:i:s\Z', strtotime($date)), 'updated');
                $e['id'] = tag('tag:'.$mail_or_domain.','.safe_strftime('%Y-%m-%d', strtotime($date)).':'.$blog_uid.'/'.$id, 'id');

                $articles[$id] = tag(n.t.t.join(n.t.t, $e).n, 'entry');

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
                if (strpos($hinm, $etags[$id])) {
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

        if ($etag) {
            header('ETag: "'.$etag.'"');
        }

        if ($cutarticles) {
            // header("HTTP/1.1 226 IM Used");
            // This should be used as opposed to 200, but Apache doesn't like it.
            header("Cache-Control: no-store, im");
            header("IM: feed");
        }
    }

    $out = array_merge($out, $articles);

    header('Content-type: application/atom+xml; charset=utf-8');

    return chr(60).'?xml version="1.0" encoding="UTF-8"?'.chr(62).n.
        '<feed xml:lang="'.txpspecialchars($language).'" xmlns="http://www.w3.org/2005/Atom">'.join(n, $out).'</feed>';
}

/**
 * Converts HTML entieties to UTF-8 characters.
 *
 * This is included only for backwards compatibility with older plugins.
 *
 * @param      string $toUnicode
 * @return     string
 * @deprecated in 4.0.4
 */

function safe_hed($toUnicode)
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

/**
 * Sanitises a string for use in a feed.
 *
 * Tries to resolve relative URLs and encode unescaped characters.
 *
 * This is included only for backwards compatibility with older plugins.
 *
 * @param      string $toFeed
 * @param      string $permalink
 * @return     string
 * @deprecated in 4.0.4
 */

function fixup_for_feed($toFeed, $permalink)
{
    // Fix relative urls.
    $txt = str_replace('href="/', 'href="'.hu.'/', $toFeed);
    $txt = preg_replace("/href=\\\"#(.*)\"/", "href=\"".$permalink."#\\1\"", $txt);
    // This was removed as entities shouldn't be stripped in Atom feeds when the
    // content type is HTML. Leaving it commented out as a reminder.
    //$txt = safe_hed($txt);

    // Encode and entify.
    $txt = preg_replace(array('/</', '/>/', "/'/", '/"/'), array('&#60;', '&#62;', '&#039;', '&#34;'), $txt);
    $txt = preg_replace("/&(?![#0-9]+;)/i", '&amp;', $txt);

    return $txt;
}

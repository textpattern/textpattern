<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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
 * Link tags.
 *
 * @since  4.9.0
 */

namespace Textpattern\Tag\Syntax;

class Link
{
    public static function linklist($atts, $thing = null)
    {
        global $s, $c, $context, $thislink, $thispage, $pretext;
    
        extract(lAtts(array(
            'break'       => '',
            'category'    => '',
            'author'      => '',
            'realname'    => '',
            'auto_detect' => 'category, author',
            'class'       => 'linklist',
            'form'        => isset($thing) ? '' : 'plainlinks',
            'id'          => '',
            'pageby'      => '',
            'limit'       => 0,
            'offset'      => 0,
            'month'       => '',
            'time'        => null,
            'sort'        => 'linksort asc',
            'wraptag'     => '',
        ), $atts));
    
        $where = array();
        $filters = isset($atts['category']) || isset($atts['author']) || isset($atts['realname']);
        $context_list = (empty($auto_detect) || $filters) ? array() : do_list_unique($auto_detect);
        $pageby = ($pageby == 'limit') ? $limit : $pageby;
    
        if ($category) {
            $where[] = "category IN ('".join("','", doSlash(do_list_unique($category)))."')";
        }
    
        if ($id) {
            $where[] = "id IN ('".join("','", doSlash(do_list_unique($id, array(',', '-'))))."')";
        }
    
        if ($author) {
            $where[] = "author IN ('".join("','", doSlash(do_list_unique($author)))."')";
        }
    
        if ($realname) {
            $authorlist = safe_column("name", 'txp_users', "RealName IN ('".join("','", doArray(doSlash(do_list_unique($realname)), 'urldecode'))."')");
            if ($authorlist) {
                $where[] = "author IN ('".join("','", doSlash($authorlist))."')";
            }
        }
    
        // If no links are selected, try...
        if (!$where && !$filters) {
            foreach ($context_list as $ctxt) {
                switch ($ctxt) {
                    case 'category':
                        // ...the global category in the URL.
                        if ($context == 'link' && !empty($c)) {
                            $where[] = "category = '".doSlash($c)."'";
                        }
                        break;
                    case 'author':
                        // ...the global author in the URL.
                        if ($context == 'link' && !empty($pretext['author'])) {
                            $where[] = "author = '".doSlash($pretext['author'])."'";
                        }
                        break;
                }
    
                // Only one context can be processed.
                if ($where) {
                    break;
                }
            }
        }
    
        if (!$where && $filters) {
            // If nothing matches, output nothing.
            return '';
        }
    
        if ($time === null || $time || $month) {
            $where[] = buildTimeSql($month, $time === null ? 'past' : $time, 'date');
        }
    
        if (!$where) {
            // If nothing matches, start with all links.
            $where[] = "1 = 1";
        }
    
        $where = join(" AND ", $where);
    
        // Set up paging if required.
        if ($limit && $pageby) {
            $pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
            $pgoffset = $offset + (($pg - 1) * $pageby);
    
            if (empty($thispage)) {
                $grand_total = safe_count('txp_link', $where);
                $total = $grand_total - $offset;
                $numPages = ($pageby > 0) ? ceil($total/$pageby) : 1;
    
                // Send paging info to txp:newer and txp:older.
                $pageout['pg']          = $pg;
                $pageout['numPages']    = $numPages;
                $pageout['s']           = $s;
                $pageout['c']           = $c;
                $pageout['context']     = 'link';
                $pageout['grand_total'] = $grand_total;
                $pageout['total']       = $total;
                $thispage = $pageout;
            }
        } else {
            $pgoffset = $offset;
        }
    
        $qparts = array(
            $where,
            'ORDER BY '.sanitizeForSort($sort),
            ($limit) ? 'LIMIT '.intval($pgoffset).', '.intval($limit) : '',
        );
    
        $rs = safe_rows_start("*, UNIX_TIMESTAMP(date) AS uDate", 'txp_link', join(' ', $qparts));
        $out = parseList($rs, $thislink, function($a) {
            global $thislink;
            $thislink = $a;
            $thislink['date'] = $thislink['uDate'];
            unset($thislink['uDate']);
        }, compact('form', 'thing'));
    
        return $out ? doWrap($out, $wraptag, $break, $class) : '';
    }
    
    // -------------------------------------------------------------

    public static function link($atts)
    {
        global $thislink;
    
        extract(lAtts(array(
            'rel'    => '',
            'id'     => '',
            'name'   => '',
            'escape' => true,
        ), $atts));
    
        $rs = $thislink;
        $sql = array();
    
        if ($id) {
            $sql[] = "id = ".intval($id);
        } elseif ($name) {
            $sql[] = "linkname = '".doSlash($name)."'";
        }
    
        if ($sql) {
            $rs = safe_row("linkname, url", 'txp_link', implode(" AND ", $sql)." LIMIT 1");
        }
    
        if (!$rs) {
            trigger_error(gTxt('unknown_link'));
    
            return '';
        }
    
        return tag(
            $escape ? txp_escape($escape, $rs['linkname']) : $rs['linkname'], 'a',
            ($rel ? ' rel="'.txpspecialchars($rel).'"' : '').
            ' href="'.txpspecialchars($rs['url']).'"'
        );
    }
    
    // -------------------------------------------------------------
    
    public static function linkdesctitle($atts)
    {
        global $thislink;
    
        extract(lAtts(array('rel' => '', 'escape' => true), $atts));
    
        assert_link();
    
        $description = ($thislink['description'])
            ? ' title="'.txpspecialchars($thislink['description']).'"'
            : '';
    
        return tag(
            $escape ? txp_escape($escape, $thislink['linkname']) : $thislink['linkname'], 'a',
            ($rel ? ' rel="'.txpspecialchars($rel).'"' : '').
            ' href="'.doSpecial($thislink['url']).'"'.$description
        );
    }
    
    // -------------------------------------------------------------
    
    public static function link_name($atts)
    {
        global $thislink;
    
        extract(lAtts(array('escape' => null), $atts));
    
        assert_link();
    
        return ($escape === null)
            ? txpspecialchars($thislink['linkname'])
            : $thislink['linkname'];
    }
    
    // -------------------------------------------------------------
    
    public static function link_url()
    {
        global $thislink;
    
        assert_link();
    
        return doSpecial($thislink['url']);
    }
    
    // -------------------------------------------------------------
    
    public static function link_author($atts)
    {
        global $thislink, $s;
    
        extract(lAtts(array(
            'link'         => 0,
            'title'        => 1,
            'section'      => '',
            'this_section' => '',
        ), $atts));
    
        assert_link();
    
        if ($thislink['author']) {
            $author_name = get_author_name($thislink['author']);
            $display_name = txpspecialchars(($title) ? $author_name : $thislink['author']);
    
            $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;
    
            $author = ($link)
                ? href($display_name, pagelinkurl(array(
                    's'       => $section,
                    'author'  => $author_name,
                    'context' => 'link',
                )))
                : $display_name;
    
            return $author;
        }
    }
    
    // -------------------------------------------------------------
    
    public static function link_description($atts)
    {
        global $thislink;
    
        extract(lAtts(array('escape' => null), $atts));
    
        assert_link();
    
        if ($thislink['description']) {
            return ($escape === null) ?
                txpspecialchars($thislink['description']) :
                $thislink['description'];
        }
    }
    
    // -------------------------------------------------------------
    
    public static function link_category($atts)
    {
        global $thislink;
    
        extract(lAtts(array('title' => 0), $atts));
    
        assert_link();
    
        if ($thislink['category']) {
            $category = ($title)
                ? fetch_category_title($thislink['category'], 'link')
                : $thislink['category'];
    
            return $category;
        }
    }
    
    // -------------------------------------------------------------
    
    public static function link_id()
    {
        global $thislink;
    
        assert_link();
    
        return $thislink['id'];
    }
}

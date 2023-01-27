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
 * File tags.
 *
 * @since  4.9.0
 */

namespace Textpattern\Tag\Syntax;

class File
{
    public static function file_download_list($atts, $thing = null)
    {
        global $s, $c, $context, $thisfile, $thispage, $pretext;
    
        extract(lAtts(array(
            'break'       => br,
            'category'    => '',
            'author'      => '',
            'realname'    => '',
            'auto_detect' => 'category, author',
            'class'       => 'file_download_list',
            'form'        => isset($thing) ? '' : 'files',
            'id'          => '',
            'pageby'      => '',
            'limit'       => 10,
            'offset'      => 0,
            'month'       => '',
            'time'        => null,
            'sort'        => 'filename asc',
            'wraptag'     => '',
            'status'      => STATUS_LIVE,
        ), $atts));
    
        if (!is_numeric($status)) {
            $status = getStatusNum($status);
        }
    
        // Note: status treated slightly differently.
        $where = array();
        $filters = isset($atts['id']) || isset($atts['category']) || isset($atts['author']) || isset($atts['realname']) || isset($atts['status']);
        $context_list = (empty($auto_detect) || $filters) ? array() : do_list_unique($auto_detect);
        $pageby = ($pageby == 'limit') ? $limit : $pageby;
    
        if ($category) {
            $where[] = "category IN ('".join("','", doSlash(do_list_unique($category)))."')";
        }
    
        $ids = $id ? array_map('intval', do_list_unique($id, array(',', '-'))) : array();
    
        if ($ids) {
            $where[] = "id IN ('".join("','", $ids)."')";
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
    
        // If no files are selected, try...
        if (!$where && !$filters) {
            foreach ($context_list as $ctxt) {
                switch ($ctxt) {
                    case 'category':
                        // ...the global category in the URL.
                        if ($context == 'file' && !empty($c)) {
                            $where[] = "category = '".doSlash($c)."'";
                        }
                        break;
                    case 'author':
                        // ...the global author in the URL.
                        if ($context == 'file' && !empty($pretext['author'])) {
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
    
        if ($status) {
            $where[] = "status = '".doSlash($status)."'";
        } elseif (!$where && $filters) {
            // If nothing matches, output nothing.
            return '';
        }
    
        if ($time === null || $time || $month) {
            $where[] = buildTimeSql($month, $time === null ? 'past' : $time, 'created');
        }
    
        $where = join(" AND ", $where);
    
        // Set up paging if required.
        if ($limit && $pageby) {
            $pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
            $pgoffset = $offset + (($pg - 1) * $pageby);
    
            if (empty($thispage)) {
                $grand_total = safe_count('txp_file', $where);
                $total = $grand_total - $offset;
                $numPages = ($pageby > 0) ? ceil($total/$pageby) : 1;
    
                // Send paging info to txp:newer and txp:older.
                $pageout['pg']          = $pg;
                $pageout['numPages']    = $numPages;
                $pageout['s']           = $s;
                $pageout['c']           = $c;
                $pageout['context']     = 'file';
                $pageout['grand_total'] = $grand_total;
                $pageout['total']       = $total;
                $thispage = $pageout;
            }
        } else {
            $pgoffset = $offset;
        }
    
        // Preserve order of custom file ids unless 'sort' attribute is set.
        if (!empty($ids) && empty($atts['sort'])) {
            $safe_sort = "FIELD(id, ".join(',', $ids).")";
        } else {
            $safe_sort = sanitizeForSort($sort);
        }
    
        $qparts = array(
            "ORDER BY ".$safe_sort,
            ($limit) ? "LIMIT ".intval($pgoffset).", ".intval($limit) : '',
        );
    
        $rs = safe_rows_start("*", 'txp_file', $where.' '.join(' ', $qparts));
        $out = parseList($rs, $thisfile, 'file_download_format_info', compact('form', 'thing'));
    
        return $out ? doWrap($out, $wraptag, compact('break', 'class')) : '';
    }
    
    // -------------------------------------------------------------
    
    public static function file_download($atts, $thing = null)
    {
        global $thisfile;
    
        extract(lAtts(array(
            'filename' => '',
            'form'     => 'files',
            'id'       => '',
        ), $atts));
    
        $from_form = false;
    
        if ($id) {
            $thisfile = fileDownloadFetchInfo('id = '.intval($id).' and created <= '.now('created'));
        } elseif ($filename) {
            $thisfile = fileDownloadFetchInfo("filename = '".doSlash($filename)."' and created <= ".now('created'));
        } else {
            assert_file();
    
            $from_form = true;
        }
    
        if ($thisfile) {
            $out = ($thing) ? parse($thing) : parse_form($form);
    
            // Cleanup: this wasn't called from a form, so we don't want this
            // value remaining.
            if (!$from_form) {
                $thisfile = '';
            }
    
            return $out;
        }
    }
    
    // -------------------------------------------------------------
    
    public static function file_download_link($atts, $thing = null)
    {
        global $thisfile;
    
        extract(lAtts(array(
            'filename' => '',
            'id'       => '',
        ), $atts));
    
        $from_form = false;
    
        if ($id) {
            $thisfile = fileDownloadFetchInfo('id = '.intval($id).' and created <= '.now('created'));
        } elseif ($filename) {
            $thisfile = fileDownloadFetchInfo("filename = '".doSlash($filename)."' and created <= ".now('created'));
        } else {
            assert_file();
    
            $from_form = true;
        }
    
        if ($thisfile) {
            $url = filedownloadurl($thisfile['id'], $thisfile['filename']);
    
            $out = ($thing) ? href(parse($thing), $url) : $url;
    
            // Cleanup: this wasn't called from a form, so we don't want this
            // value remaining
            if (!$from_form) {
                $thisfile = '';
            }
    
            return $out;
        }
    }
    
    // -------------------------------------------------------------
    
    public static function file_download_size($atts)
    {
        global $thisfile;
    
        extract(lAtts(array(
            'decimals' => 2,
            'format'   => '',
        ), $atts));
    
        assert_file();
    
        if (is_numeric($decimals) && $decimals >= 0) {
            $decimals = intval($decimals);
        } else {
            $decimals = 2;
        }
    
        if (isset($thisfile['size'])) {
            $format_unit = strtolower(substr($format, 0, 1));
    
            return format_filesize($thisfile['size'], $decimals, $format_unit);
        } else {
            return '';
        }
    }
    
    // -------------------------------------------------------------
    
    public static function file_download_id()
    {
        global $thisfile;
    
        assert_file();
    
        return $thisfile['id'];
    }
    
    // -------------------------------------------------------------
    
    public static function file_download_name($atts)
    {
        global $thisfile;
    
        extract(lAtts(array('title' => 0), $atts));
    
        assert_file();
    
        return ($title) ? $thisfile['title'] : $thisfile['filename'];
    }
    
    // -------------------------------------------------------------
    
    public static function file_download_category($atts)
    {
        global $thisfile;
    
        extract(lAtts(array('title' => 0), $atts));
    
        assert_file();
    
        if ($thisfile['category']) {
            $category = ($title)
                ? fetch_category_title($thisfile['category'], 'file')
                : $thisfile['category'];
    
            return $category;
        }
    }
    
    // -------------------------------------------------------------
    
    public static function file_download_author($atts)
    {
        global $thisfile, $s;
    
        extract(lAtts(array(
            'link'         => 0,
            'title'        => 1,
            'section'      => '',
            'this_section' => '',
        ), $atts));
    
        assert_file();
    
        if ($thisfile['author']) {
            $author_name = get_author_name($thisfile['author']);
            $display_name = txpspecialchars(($title) ? $author_name : $thisfile['author']);
    
            $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;
    
            $author = ($link)
                ? href($display_name, pagelinkurl(array(
                    's'       => $section,
                    'author'  => $author_name,
                    'context' => 'file',
                )))
                : $display_name;
    
            return $author;
        }
    }
    
    // -------------------------------------------------------------
    
    public static function file_download_downloads()
    {
        global $thisfile;
    
        assert_file();
    
        return $thisfile['downloads'];
    }
    
    // -------------------------------------------------------------
    
    public static function file_download_description($atts)
    {
        global $thisfile;
    
        extract(lAtts(array('escape' => null), $atts));
    
        assert_file();
    
        if ($thisfile['description']) {
            return ($escape === null)
                ? txpspecialchars($thisfile['description'])
                : $thisfile['description'];
        }
    }
}

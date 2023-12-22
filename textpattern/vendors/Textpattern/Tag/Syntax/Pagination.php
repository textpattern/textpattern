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
 * Generates pagination links.
 *
 * @since  4.9.0
 */

namespace Textpattern\Tag\Syntax;

class Pagination
{

    // -------------------------------------------------------------
    
    public static function pager($atts, $thing = null, $newer = null)
    {
        global $thispage, $is_article_list, $txp_context, $txp_item;
        static $pg = true, $numPages = null, $linkall = false, $top = 1, $shown = array();
        static $items = array('page' => null, 'total' => null, 'url' => null);
    
        $get = isset($atts['total']) && $atts['total'] === true;
        $set = $newer === null && (isset($atts['pg']) || isset($atts['total']) && !$get);
        $put = $get || !$set || isset($atts['break']);
        $pairs = array();
    
        if ($get) {
            $pairs += array(
                'total'      => true,
            );
        }
    
        if ($put) {
            $pairs += array(
                'shift'      => false,
                'showalways' => false,
                'link'       => $linkall,
                'title'      => '',
                'escape'     => 'html',
                'rel'        => '',
                'limit'      => 0,
                'wraptag'    => '',
                'break'      => '',
                'class'      => '',
                'html_id'    => ''
            );
        }
    
        if ($set){
            $store = compact('pg', 'numPages', 'linkall', 'top', 'shown');
            $pairs += array(
                'pg'         => $pg,
                'total'      => $numPages,
                'shift'      => 1,
                'showalways' => 2,
                'link'       => false,
            );
        }
    
        extract(lAtts($pairs, $atts));
    
        if ($set) {
            if (isset($total) && $total !== true) {
                list($total, $pageby) = explode('/', $total.'/0');
                $pageby = (int)$pageby;
                $total = (int)$total;
                $numPages = $pageby ? ceil($total/$pageby) : $total;
            } elseif ($pg === true) {
                $numPages = isset($thispage['numPages']) ? (int)$thispage['numPages'] : null;
            }
        }
    
        if (!isset($numPages)) {
            if (isset($thispage['numPages'])) {
                $numPages = (int)$thispage['numPages'];
            } else {
                return $is_article_list ? postpone_process(2) : '';
            }
        }
    
        if ($set) {
            $shown = array();
            $linkall = $link;
    
            if (!$put) {
                $top = $shift === true ? 0 : ((int)$shift < 0 ? $numPages + $shift + 1 : $shift);
    
                if ($thing !== null) {
                    $thing = parse($thing, $numPages >= ($showalways ? (int)$showalways : 2));
                    extract($store);
                }
    
                return $thing;
            } else {
                $shift !== false or $shift = true;
            }
        }
    
        $pgc = $pg === true ? 'pg' : $pg;
        $thispg = $pg === true && isset($thispage['pg']) ? $thispage['pg'] : intval(gps($pgc, $top));
        $thepg = max(1, min($thispg, $numPages));
    
        if ($get) {
            if ($thing === null && $shift === false) {
                return $newer === null ? $numPages : ($newer ? $thepg - 1 : $numPages - $thepg);
            } elseif ($shift === true || $shift === false) {
                if ($newer !== null) {
                    $range = $newer ? $thepg - 1 : $numPages - $thepg;
                }
            } else {
                $range = (int)$shift;
            }
        }
    
        if (isset($range)) {
            if (!$range) {
                $pages = array();
            } elseif ($range > 0) {
                $pages = $newer === null ? range(-max($range, 2*$range + $thepg - $numPages), max($range, 2*$range - $thepg + 1)) :
                    range($newer ? max($range, 2*$range + $thepg - $numPages) : 1, $newer ? 1 : max($range, 2*$range - $thepg + 1));
            } else {
                $pages = $newer !== null ? ($newer ? range(-1, -max(-$range, -2*$range + $thepg - $numPages)) : range(-max(-$range, -2*$range - $thepg + 1), -1)) :
                    range(min(max(1 - $range - $thepg, 1 - 2*$range - $numPages), 0), max(0, min($numPages + $range - $thepg, $numPages + 2*$range - 1)));
            }
        } elseif (is_bool($shift)) {
            $pages = $newer === null ? ($shift ? range(1 - $thepg, $numPages - $thepg) : array(0)) : array($shift ? true : 1);
            $range = !$shift;
        } else {
            $pages = array_map('intval', do_list($shift, array(',', '-')));
            $range = false;
        }
    
        foreach ($items as $item => $val) {
            $items[$item] = isset($txp_item[$item]) ? $txp_item[$item] : null;
        }
    
        $txp_item['total'] = $numPages;
        $limit = $limit ? (int)$limit : -1;
        $old_context = $txp_context;
        $txp_context += get_context();
        $out = array();
        $rel_att = $rel === '' ? '' : ' rel="'.txpspecialchars($rel).'"';
        $class_att = $wraptag === '' && $class !== '' ? ' class="'.txpspecialchars($class).'"' : '';
        $id_att = $wraptag === '' && $html_id !== '' ? ' id="'.txpspecialchars($html_id).'"' : '';
    
        if ($title !== '') {
            $title_att = ' title="'.($escape == 'html' ? escape_title($title) :
                ($escape ? txp_escape($escape, $title) : $title)
            ).'"';
        } else {
            $title_att = '';
        }
    
        foreach ($pages as $page) {
            if ($newer === null) {
                $nextpg = $thepg + $page;
            } elseif ($newer) {
                $nextpg = $page === true ? 1 : ((int)$page < 0 ? -$page : $thepg - $page);
            } else {
                $nextpg = $page === true ? $numPages : ((int)$page < 0 ? $numPages + $page + 1 : $thepg + $page);
            }
    
            if (
                $nextpg >= ($newer === false && $range !== false ? $thepg + 1 : 1) &&
                $nextpg <= ($newer === true && $range !== false ? $thepg - 1 : $numPages)
            ) {
                if (empty($shown[$nextpg]) || $showalways) {
                    $txp_context[$pgc] = $nextpg == $top ? null : $nextpg;
                    $url = pagelinkurl($txp_context);
                    $txp_item['page'] = $nextpg;
                    $txp_item['url'] = $url;
    
                    if ($shift !== false || $newer === null || !is_bool($range)) {
                        $shown[$nextpg] = true;
                        $limit--;
                    }
    
                    $item = isset($thing) ? parse($thing) : $nextpg;
                    $url = $link || $link === false && $nextpg != $thispg ? href(
                        $item,
                        $url,
                        $id_att.$title_att.$rel_att.$class_att
                    ) : $item;
                } else {
                    $url = false;
                }
            } else {
                $url = isset($thing) ? parse($thing, false) : false;
            }
    
            empty($url) or $out[] = $url;
    
            if (!$limit) {
                break;
            }
        }
    
        foreach ($items as $item => $val) {
            $txp_item[$item] = $val;
        }
    
        $txp_context = $old_context;
    
        return $wraptag !== '' ? doWrap($out, $wraptag, compact('break', 'class', 'html_id')) : doWrap($out, '', $break);
    }
}

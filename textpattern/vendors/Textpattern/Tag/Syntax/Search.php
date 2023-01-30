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
 * Generates comment tags.
 *
 * @since  4.9.0
 */

namespace Textpattern\Tag\Syntax;

class Search
{
    public static function search_result_title($atts)
    {
        return permlink($atts, '<txp:title />');
    }
    
    // -------------------------------------------------------------
    
    public static function search_result_excerpt($atts)
    {
        global $thisarticle, $pretext;
    
        extract(lAtts(array(
            'hilight'   => 'strong',
            'limit'     => 5,
            'separator' => ' &#8230;',
        ), $atts));
    
        assert_article();
    
        $m = $pretext['m'];
        $q = $pretext['q'];
    
        $quoted = ($q[0] === '"') && ($q[strlen($q) - 1] === '"');
        $q = $quoted ? trim(trim($q, '"')) : trim($q);
    
        $result = preg_replace('/\s+/', ' ', strip_tags(str_replace('><', '> <', $thisarticle['body'])));
    
        if ($quoted || empty($m) || $m === 'exact') {
            $regex_search = '/(?:\G|\s).{0,50}'.preg_quote($q, '/').'.{0,50}(?:\s|$)/iu';
            $regex_hilite = '/('.preg_quote($q, '/').')/i';
        } else {
            $regex_search = '/(?:\G|\s).{0,50}('.preg_replace('/\s+/', '|', preg_quote($q, '/')).').{0,50}(?:\s|$)/iu';
            $regex_hilite = '/('.preg_replace('/\s+/', '|', preg_quote($q, '/')).')/i';
        }
    
        preg_match_all($regex_search, $result, $concat);
        $concat = $concat[0];
    
        for ($i = 0, $r = array(); $i < min($limit, count($concat)); $i++) {
            $r[] = trim($concat[$i]);
        }
    
        $concat = join($separator.n, $r);
        $concat = preg_replace('/^[^>]+>/U', '', $concat);
        $concat = preg_replace($regex_hilite, "<$hilight>$1</$hilight>", $concat);
    
        return ($concat) ? trim($separator.$concat.$separator) : '';
    }
    
    // -------------------------------------------------------------
    
    public static function search_result_url($atts)
    {
        global $thisarticle;
    
        assert_article();
    
        $l = permlinkurl($thisarticle);
    
        return permlink($atts, $l);
    }
    
    // -------------------------------------------------------------
    
    public static function search_result_date($atts)
    {
        trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);
    
        assert_article();
    
        return posted($atts);
    }

    // -------------------------------------------------------------
    
    public static function search_result_count($atts)
    {
        trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);
    
        return items_count($atts);
    }    
}

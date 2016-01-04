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
 * Tools for searching site contents.
 *
 * @package Search
 */

/**
 * Performs searching and returns results.
 *
 * This is now performed by doArticles().
 *
 * @param      string $q
 * @deprecated in 4.0.4
 * @see        doArticles()
 */

function search($q)
{
    global $prefs;
    $url = $prefs['siteurl'];
    extract($prefs);

    $s_filter = filterSearch();

    $form = fetch('form', 'txp_form', 'name', 'search_results');

    // Lose this eventually - only used if search_results form is missing.
    $form = (!$form) ? legacy_form() : $form;

    $rs = safe_rows(
        "*, ID AS thisid, UNIX_TIMESTAMP(Posted) AS posted, Title AS title,
        MATCH (Title,Body) AGAINST ('$q') AS score",
        'textpattern',
        "(Title RLIKE '$q' OR Body RLIKE '$q') $s_filter
        AND Status = 4 AND Posted <= ".now('posted')." ORDER BY score DESC LIMIT 40");

    if ($rs) {
        $result_rows = count($rs);
        $text = ($result_rows == 1) ? gTxt('article_found') : gTxt('articles_found');
    } else {
        $result_rows = 0;
        $text = gTxt('articles_found');
    }

    $results[] = graf($result_rows.' '.$text);

    if ($result_rows > 0) {
        foreach ($rs as $a) {
            extract($a);

            $result_date = safe_strftime($archive_dateformat, $posted);
            $uTitle = ($url_title) ? $url_title : stripSpace($Title);
            $hurl = permlinkurl($a);
            $result_url = '<a href="'.$hurl.'">'.$hurl.'</a>';
            $result_title = '<a href="'.$hurl.'">'.$Title.'</a>';

            $result = preg_replace("/>\s*</", "> <", $Body_html);
            preg_match_all("/\s.{1,50}".preg_quote($q).".{1,50}\s/i", $result, $concat);

            $concat = join(" ... ", $concat[0]);

            $concat = strip_tags($concat);
            $concat = preg_replace('/^[^>]+>/U', "", $concat);
            $concat = preg_replace("/($q)/i", "<strong>$1</strong>", $concat);
            $result_excerpt = ($concat) ? "... ".$concat." ..." : '';

            $glob['search_result_title']   = $result_title;
            $glob['search_result_excerpt'] = $result_excerpt;
            $glob['search_result_url']     = $result_url;
            $glob['search_result_date']    = $result_date;

            $GLOBALS['this_result'] = $glob;

            $thisresult = $form;

            $results[] = parse($thisresult);
        }
    }

    return (is_array($results)) ? join('', $results) : '';
}

/**
 * Limits search to searchable sections.
 *
 * This function gets a list of searchable sections as an SQL where clause.
 * The returned results can be then be used in or as an SQL query.
 *
 * @return string|bool SQL statement, or FALSE when all sections are included the search
 * @example
 * if ($r = safe_count('textpattern', "Title LIKE '%a%' " . filterSearch()))
 * {
 *     echo 'Found {$r} articles with "a" in the title.';
 * }
 */

function filterSearch()
{
    $rs = safe_column("name", 'txp_section', "searchable != '1'");
    if ($rs) {
        foreach ($rs as $name) {
            $filters[] = "AND Section != '".doSlash($name)."'";
        }

        return join(' ', $filters);
    }

    return false;
}

/**
 * Legacy search results form.
 *
 * This is no longer used.
 *
 * @deprecated in 4.0.4
 */

function legacy_form()
{
    return '<h2><txp:search_result_title /></h2>
<p><txp:search_result_excerpt /><br/>
<small><txp:search_result_url /> &middot; <txp:search_result_date /></small></p>';
}

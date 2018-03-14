<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2018 The Textpattern Development Team
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
 * Tools for page routing and handling article data.
 *
 * @since   4.5.0
 * @package Routing
 */

/**
 * Build a query qualifier to remove non-frontpage articles from the result set.
 *
 * @return string An SQL qualifier for a query's 'WHERE' part
 */

function filterFrontPage()
{
    static $filterFrontPage;

    if (isset($filterFrontPage)) {
        return $filterFrontPage;
    }

    $filterFrontPage = false;

    $rs = safe_column("name", 'txp_section', "on_frontpage != '1'");

    if ($rs) {
        $filters = array();

        foreach ($rs as $name) {
            $filters[] = " and Section != '".doSlash($name)."'";
        }

        $filterFrontPage = join('', $filters);
    }

    return $filterFrontPage;
}

/**
 * Populates the current article data.
 *
 * Fills members of $thisarticle global from a database row.
 *
 * Keeps all article tag-related values in one place, in order to do easy
 * bugfixing and ease the addition of new article tags.
 *
 * @param array $rs An article as an assocative array
 * @example
 * if ($rs = safe_rows_start("*,
 *     UNIX_TIMESTAMP(Posted) AS uPosted,
 *     UNIX_TIMESTAMP(Expires) AS uExpires,
 *     UNIX_TIMESTAMP(LastMod) AS uLastMod",
 *     'textpattern',
 *     "1 = 1"
 * ))
 * {
 *     global $thisarticle;
 *     while ($row = nextRow($rs))
 *     {
 *         populateArticleData($row);
 *         echo $thisarticle['title'];
 *     }
 * }
 */

function populateArticleData($rs)
{
    global $production_status, $thisarticle, $trace;

    if ($production_status === 'debug') {
        $trace->log("[Article: '{$rs['ID']}']");
    }

    foreach (article_column_map() as $key => $column) {
        $thisarticle[$key] = isset($rs[$column]) ? $rs[$column] : null;
    }
}

/**
 * Formats article info and populates the current article data.
 *
 * Fills members of $thisarticle global from a database row.
 *
 * Basically just converts an article's date values to UNIX timestamps.
 * Convenience for those who prefer doing conversion in application end instead
 * of in the SQL statement.
 *
 * @param array $rs An article as an assocative array
 * @example
 * article_format_info(
 *     safe_row('*', 'textpattern', 'Status = 4 LIMIT 1')
 * )
 */

function article_format_info($rs)
{
    $rs['uPosted']  = (($unix_ts = @strtotime($rs['Posted']))  !== false) ? $unix_ts : null;
    $rs['uLastMod'] = (($unix_ts = @strtotime($rs['LastMod'])) !== false) ? $unix_ts : null;
    $rs['uExpires'] = (($unix_ts = @strtotime($rs['Expires'])) !== false) ? $unix_ts : null;
    populateArticleData($rs);
}

/**
 * Maps 'textpattern' table's columns to article data values.
 *
 * This function returns an array of 'data-value' => 'column' pairs.
 *
 * @return array
 */

function article_column_map()
{
    $custom = getCustomFields();
    $custom_map = array();

    if ($custom) {
        foreach ($custom as $i => $name) {
            $custom_map[$name] = 'custom_'.$i;
        }
    }

    return array(
        'thisid'          => 'ID',
        'posted'          => 'uPosted', // Calculated value!
        'expires'         => 'uExpires', // Calculated value!
        'modified'        => 'uLastMod', // Calculated value!
        'annotate'        => 'Annotate',
        'comments_invite' => 'AnnotateInvite',
        'authorid'        => 'AuthorID',
        'title'           => 'Title',
        'url_title'       => 'url_title',
        'description'     => 'description',
        'category1'       => 'Category1',
        'category2'       => 'Category2',
        'section'         => 'Section',
        'keywords'        => 'Keywords',
        'article_image'   => 'Image',
        'comments_count'  => 'comments_count',
        'body'            => 'Body_html',
        'excerpt'         => 'Excerpt_html',
        'override_form'   => 'override_form',
        'status'          => 'Status',
    ) + $custom_map;
}

/**
 * Find an adjacent article relative to a provided threshold level.
 *
 * @param  scalar $threshold      The value to compare against
 * @param  string $s              Optional section restriction
 * @param  string $type           Lesser or greater neighbour? Either '<' (previous) or '>' (next)
 * @param  array  $atts           Attribute of article at threshold
 * @param  string $threshold_type 'cooked': Use $threshold as SQL clause; 'raw': Use $threshold as an escapable scalar
 * @return array|bool An array populated with article data, or 'false' in case of no matches
 */

function getNeighbour($threshold, $s, $type, $atts = array(), $threshold_type = 'raw')
{
    global $prefs;
    static $cache = array();

    $key = md5($threshold.$s.$type.join(n, $atts));

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $atts += array(
        'expired' => true,
        'sortdir' => 'desc',
    );
    $id = $time = $keywords = $custom = '';

    extract($atts);
    $expired = ($expired && ($prefs['publish_expired_articles']));
    $status = isset($status) && intval($status) == STATUS_STICKY ? STATUS_STICKY : STATUS_LIVE;
    $customFields = getCustomFields();
    $thisid = isset($thisid) ? intval($thisid) : 0;

    // Building query parts; lifted from publish.php.
    $id = (!$id) ? '' : " AND ID IN (".join(',', array_map('intval', do_list($id))).")";
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

    if ($keywords) {
        $keys = doSlash(do_list($keywords));

        foreach ($keys as $key) {
            $keyparts[] = "FIND_IN_SET('".$key."', Keywords)";
        }

        $keywords = " AND (".join(" OR ", $keyparts).")";
    }

    $sortdir = strtolower($sortdir);

    // Invert $type for ascending sortdir.
    $types = array(
        '>' => array(
            'desc' => '>',
            'asc'  => '<',
        ),
        '<' => array(
            'desc' => '<',
            'asc'  => '>',
        ),
    );

    $type = ($type == '>') ? $types['>'][$sortdir] : $types['<'][$sortdir];

    // Escape threshold and treat it as a string unless explicitly told otherwise.
    if ($threshold_type != 'cooked') {
        $threshold = "'".doSlash($threshold)."'";
    }

    $safe_name = safe_pfx('textpattern');
    $q = array(
        "SELECT *, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod FROM $safe_name
            WHERE ($sortby $type $threshold OR ".($thisid ? "$sortby = $threshold AND ID $type $thisid" : "0").")",
        ($s != '' && $s != 'default') ? "AND Section = '".doSlash($s)."'" : filterFrontPage(),
        $id,
        $time,
        $custom,
        $keywords,
        "AND Status = $status",
        "ORDER BY $sortby",
        ($type == '<') ? "DESC" : "ASC",
        ', ID '.($type == '<' ? 'DESC' : 'ASC'),
        "LIMIT 1",
    );

    $cache[$key] = getRow(join(n.' ', $q));

    return (is_array($cache[$key])) ? $cache[$key] : false;
}

/**
 * Find next and previous articles relative to a provided threshold level.
 *
 * @param  int    $id        The "pivot" article's id; use zero (0) to indicate $thisarticle
 * @param  scalar $threshold The value to compare against if $id != 0
 * @param  string $s         Optional section restriction if $id != 0
 * @return array An array populated with article data
 */

function getNextPrev($id = 0, $threshold = null, $s = '')
{
    $threshold_type = 'raw';

    if ($id !== 0) {
        // Pivot is specific article by ID: In lack of further information,
        // revert to default sort order 'Posted desc'.
        $atts = filterAtts() + array(
            'sortby'  => 'Posted',
            'sortdir' => 'DESC',
            'thisid'  => $id,
        );
    } else {
        // Pivot is $thisarticle: Use article attributes to find its neighbours.
        assert_article();
        global $thisarticle;
        if (!is_array($thisarticle)) {
            return array();
        }

        $s = $thisarticle['section'];
        $atts = filterAtts() + array(
            'thisid' => $thisarticle['thisid'],
            'sort'   => 'Posted DESC',
        );
        $m = preg_split('/\s+/', $atts['sort']);

        // If in doubt, fall back to chronologically descending order.
        if (empty($m[0])            // No explicit sort attribute
            || count($m) > 2        // Complex clause, e.g. 'foo asc, bar desc'
            || !preg_match('/^(?:[0-9a-zA-Z$_\x{0080}-\x{FFFF}]+|`[\x{0001}-\x{FFFF}]+`)$/u', $m[0])  // The clause's first verb is not a MySQL column identifier.
        ) {
            $atts['sortby'] = "Posted";
            $atts['sortdir'] = "DESC";
        } else {
            // Sort is like 'foo asc'.
            $atts['sortby'] = $m[0];
            $atts['sortdir'] = (isset($m[1]) && strtolower($m[1]) == 'desc' ? "DESC" : "ASC");
        }

        // Attributes with special treatment.
        switch ($atts['sortby']) {
            case 'Posted':
                $threshold = "FROM_UNIXTIME(".doSlash($thisarticle['posted']).")";
                $threshold_type = 'cooked';
                break;
            case 'Expires':
                $threshold = "FROM_UNIXTIME(".doSlash($thisarticle['expires']).")";
                $threshold_type = 'cooked';
                break;
            case 'LastMod':
                $threshold = "FROM_UNIXTIME(".doSlash($thisarticle['modified']).")";
                $threshold_type = 'cooked';
                break;
            default:
                // Retrieve current threshold value per sort column from $thisarticle.
                $acm = array_flip(article_column_map());
                $key = $acm[$atts['sortby']];
                $threshold = $thisarticle[$key];
                break;
        }
    }

    $out['next'] = getNeighbour($threshold, $s, '>', $atts, $threshold_type);
    $out['prev'] = getNeighbour($threshold, $s, '<', $atts, $threshold_type);

    return $out;
}

/**
 * Gets the site last modification date.
 *
 * @return  string
 * @package Pref
 */

function lastMod()
{
    $last = safe_field("UNIX_TIMESTAMP(val)", 'txp_prefs', "name = 'lastmod'");

    return gmdate("D, d M Y H:i:s \G\M\T", $last);
}

/**
 * Parse a string and replace any Textpattern tags with their actual value.
 *
 * @param   string    $thing     The raw string
 * @param   null|bool $condition Process true/false part
 * @return  string               The parsed string
 * @package TagParser
 */

function parse($thing, $condition = true)
{
    global $production_status, $trace, $txp_parsed, $txp_else, $txp_atts, $txp_tag;
    static $pattern, $short_tags = null;

    if (!empty($txp_atts['not'])) {
        $condition = empty($condition);
        unset($txp_atts['not']);
    }

    if ($production_status === 'debug') {
        $trace->log('['.($condition ? 'true' : 'false').']');
    }

    if (!$condition) {
        $txp_atts = null;
    }

    if (!isset($short_tags)) {
        $short_tags = get_pref('enable_short_tags', false);
        $pattern = $short_tags ? 'txp|[a-z]+:' : 'txp:?';
    }

    if ($thing === null) {
        return $condition ? '1' : '';
    } elseif (!$short_tags) {
        if (false === strpos($thing, '<txp:')) {
            return $condition ? $thing : ($thing ? '' : '1');
        }
    } elseif (!preg_match("@<(?:{$pattern}):@", $thing)) {
        return $condition ? $thing : ($thing ? '' : '1');
    }

    $hash = sha1($thing);

    if (!isset($txp_parsed[$hash])) {
        $tag     = array();
        $outside = array();
        $else    = array(-1);
        $count   = array(-1);
        $level   = 0;

        $f = '@(</?(?:'.$pattern.'):\w+(?:\s+[\w\-]+(?:\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/>]+))?)*\s*/?\>)@s';
        $t = '@^</?('.$pattern.'):(\w+)(.*?)/?\>$@s';

        $parsed = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE);
        $last = count($parsed);
        $inside  = array($parsed[0]);
        $tags    = array($inside);

        for ($i = 1; $i < $last || $level > 0; $i++) {
            $chunk = $i < $last ? $parsed[$i] : '</txp:'.$tag[$level-1][2].'>';
            preg_match($t, $chunk, $tag[$level]);
            $count[$level] += 2;

            if ($tag[$level][2] === 'else') {
                $else[$level] = $count[$level];
            } elseif ($tag[$level][1] === 'txp:') {
                // Handle <txp::shortcode />.
                $tag[$level][3] = "yield form='".$tag[$level][2]."'".$tag[$level][3];
                $tag[$level][2] = 'output_form';
            } elseif ($short_tags && $tag[$level][1] !== 'txp') {
                // Handle <short::tags />.
                $tag[$level][2] = rtrim($tag[$level][1], ':').'_'.$tag[$level][2];
            }

            if ($chunk[strlen($chunk) - 2] === '/') {
                // Self closed tag.
                if ($chunk[1] === '/') {
                    trigger_error(gTxt('ambiguous_tag_format', array('{chunk}' => $chunk)), E_USER_WARNING);
                }

                $tags[$level][] = array($chunk, $tag[$level][2], trim($tag[$level][3]), null, null);
                $inside[$level] .= $chunk;
            } elseif ($chunk[1] !== '/') {
                // Opening tag.
                $inside[$level] .= $chunk;
                $level++;
                $outside[$level] = $chunk;
                $inside[$level] = '';
                $else[$level] = $count[$level] = -1;
                $tags[$level] = array();
            } else {
                // Closing tag.
                if ($level < 1) {
                    trigger_error(gTxt('missing_open_tag', array('{chunk}' => $chunk)), E_USER_WARNING);
                    $tags[$level][] = array($chunk, null, '', null, null);
                    $inside[$level] .= $chunk;
                } else {
                    if ($i >= $last) {
                        trigger_error(gTxt('missing_close_tag', array('{chunk}' => $outside[$level])), E_USER_WARNING);
                    } elseif ($tag[$level-1][2] != $tag[$level][2]) {
                        trigger_error(gTxt('mismatch_open_close_tag', array(
                            '{from}' => $outside[$level],
                            '{to}'   => $chunk,
                        )), E_USER_WARNING);
                    }

                    $sha = sha1($inside[$level]);
                    $txp_parsed[$sha] = $count[$level] > 2 ? $tags[$level] : false;
                    $txp_else[$sha] = array($else[$level] > 0 ? $else[$level] : $count[$level], $count[$level] - 2);
                    $level--;
                    $tags[$level][] = array($outside[$level+1], $tag[$level][2], trim($tag[$level][3]), $inside[$level+1], $chunk);
                    $inside[$level] .= $inside[$level+1].$chunk;
                }
            }

            $chunk = ++$i < $last ? $parsed[$i] : '';
            $tags[$level][] = $chunk;
            $inside[$level] .= $chunk;
        }

        $txp_parsed[$hash] = $tags[0];
        $txp_else[$hash] = array($else[0] > 0 ? $else[0] : $count[0] + 2, $count[0]);
    }

    $tag = $txp_parsed[$hash];

    if (empty($tag)) {
        return $condition ? $thing : ($thing ? '' : '1');
    }

    list($first, $last) = $txp_else[$hash];

    if ($condition) {
        $last = $first - 2;
        $first   = 1;
    } elseif ($first <= $last) {
        $first  += 2;
    } else {
        return ($thing ? '' : '1');
    }

    for ($out = $tag[$first - 1]; $first <= $last; $first++) {
        $txp_tag = $tag[$first];
        $out .= processTags($txp_tag[1], $txp_tag[2], $txp_tag[3]).$tag[++$first];
    }

    $txp_tag = null;

    return $out;
}

/**
 * Guesstimate whether a given function name may be a valid tag handler.
 *
 * @param   string $tag function name
 * @return  bool FALSE if the function name is not a valid tag handler
 * @package TagParser
 */

function maybe_tag($tag)
{
    static $tags = null;

    if ($tags === null) {
        $tags = get_defined_functions();
        $tags = array_flip($tags['user']);
    }

    return isset($tags[$tag]);
}

/**
 * Parse a tag for attributes and hand over to the tag handler function.
 *
 * @param  string      $tag   The tag name
 * @param  string      $atts  The attribute string
 * @param  string|null $thing The tag's content in case of container tags
 * @return string Parsed tag result
 * @package TagParser
 */

function processTags($tag, $atts = '', $thing = null)
{
    global $pretext, $production_status, $txp_current_tag, $txp_atts, $txp_tag, $trace;
    static $registry = null, $max_pass, $globals;

    if (empty($tag)) {
        return;
    }

    $old_tag = $txp_current_tag;
    $txp_current_tag = $txp_tag[0].$txp_tag[3].$txp_tag[4];

    if ($production_status !== 'live') {
        $tag_stop = $txp_tag[4];
        $trace->start($txp_tag[0]);
    }

    if ($registry === null) {
        $max_pass = get_pref('secondpass', 1);
        $registry = Txp::get('\Textpattern\Tag\Registry');
        $globals = array_filter(
            $registry->getRegistered(true),
             function ($v) {
                 return !is_bool($v);
             }
         );
    }

    $old_atts = $txp_atts;

    if ($atts) {
        $split = splat($atts);
    } else {
        $txp_atts = null;
        $split = array();
    }

    if (!isset($txp_atts['txp-process'])) {
        $out = $registry->process($tag, $split, $thing);
    } else {
        $process = empty($txp_atts['txp-process']) || is_numeric($txp_atts['txp-process']) ? (int) $txp_atts['txp-process'] : 1;

        if ($process <= $pretext['secondpass'] + 1) {
            unset($txp_atts['txp-process']);
            $out = $process > 0 ? $registry->process($tag, $split, $thing) : '';
        } else {
            $txp_atts['txp-process'] = $process;
            $out = '';
        }
    }

    if ($out === false) {
        if (maybe_tag($tag)) { // Deprecated in 4.6.0.
            trigger_error(gTxt('unregistered_tag'), E_USER_NOTICE);
            $out = $registry->register($tag)->process($tag, $split, $thing);
        } else {
            trigger_error(gTxt('unknown_tag'), E_USER_WARNING);
            $out = '';
        }
    }

    if (isset($txp_atts['txp-process']) && (int) $txp_atts['txp-process'] > $pretext['secondpass'] + 1) {
        $out = $pretext['secondpass'] < $max_pass ? $txp_current_tag : '';
    } else {
        if ($thing === null && !empty($txp_atts['not'])) {
            $out = $out ? '' : '1';
        }

        unset($txp_atts['txp-process'], $txp_atts['not']);

        if ($txp_atts) {
            $pretext['_txp_atts'] = true;

            foreach ($txp_atts as $attr => &$val) {
                if (isset($val) && isset($globals[$attr])) {
                    $out = $registry->processAttr($attr, $split, $out);
                }
            }

            $pretext['_txp_atts'] = false;
        }
    }

    $txp_atts = $old_atts;
    $txp_current_tag = $old_tag;

    if ($production_status !== 'live') {
        $trace->stop($tag_stop);
    }

    return $out;
}

/**
 * Protection from those who'd bomb the site by GET.
 *
 * Origin of the infamous 'Nice try' message and an even more useful '503'
 * HTTP status.
 */

function bombShelter()
{
    global $prefs;
    $in = serverset('REQUEST_URI');

    if (!empty($prefs['max_url_len']) and strlen($in) > $prefs['max_url_len']) {
        txp_status_header('503 Service Unavailable');
        exit('Nice try.');
    }
}

/**
 * Checks a named item's existence in a database table.
 *
 * The given database table is prefixed with 'txp_'. As such this function can
 * only be used with core database tables.
 *
 * @param   string $table The database table name
 * @param   string $val   The name to look for
 * @param   bool   $debug Dump the query
 * @return  bool|string The item's name, or FALSE when it doesn't exist
 * @package Filter
 * @example
 * if ($r = ckEx('section', 'about'))
 * {
 *     echo "Section '{$r}' exists.";
 * }
 */

function ckEx($table, $val, $debug = false)
{
    return safe_field("name", 'txp_'.$table, "name = '".doSlash($val)."' LIMIT 1", $debug);
}

/**
 * Checks if the given category exists.
 *
 * @param   string $type  The category type, either 'article', 'file', 'link', 'image'
 * @param   string $val   The category name to look for
 * @param   bool   $debug Dump the query
 * @return  bool|string The category's name, or FALSE when it doesn't exist
 * @package Filter
 * @see     ckEx()
 * @example
 * if ($r = ckCat('article', 'development'))
 * {
 *     echo "Category '{$r}' exists.";
 * }
 */

function ckCat($type, $val, $debug = false)
{
    return safe_field("name", 'txp_category', "name = '".doSlash($val)."' AND type = '".doSlash($type)."' LIMIT 1", $debug);
}

/**
 * Lookup an article by ID.
 *
 * This function takes an article's ID, and checks if it's been published. If it
 * has, returns the section and the ID as an array. FALSE otherwise.
 *
 * @param   int  $val   The article ID
 * @param   bool $debug Dump the query
 * @return  array|bool Array of ID and section on success, FALSE otherwise
 * @package Filter
 * @example
 * if ($r = ckExID(36))
 * {
 *     echo "Article #{$r['id']} is published, and belongs to the section {$r['section']}.";
 * }
 */

function ckExID($val, $debug = false)
{
    return safe_row("ID, Section", 'textpattern', "ID = ".intval($val)." AND Status >= 4 LIMIT 1", $debug);
}

/**
 * Lookup an article by URL title.
 *
 * This function takes an article's URL title, and checks if the article has
 * been published. If it has, returns the section and the ID as an array.
 * FALSE otherwise.
 *
 * @param   string $val   The URL title
 * @param   bool   $debug Dump the query
 * @return  array|bool Array of ID and section on success, FALSE otherwise
 * @package Filter
 * @example
 * if ($r = ckExID('my-article-title'))
 * {
 *     echo "Article #{$r['id']} is published, and belongs to the section {$r['section']}.";
 * }
 */

function lookupByTitle($val, $debug = false)
{
    return safe_row("ID, Section", 'textpattern', "url_title = '".doSlash($val)."' AND Status >= 4 LIMIT 1", $debug);
}

/**
 * Lookup a published article by URL title and section.
 *
 * This function takes an article's URL title, and checks if the article has
 * been published. If it has, returns the section and the ID as an array.
 * FALSE otherwise.
 *
 * @param   string $val     The URL title
 * @param   string $section The section name
 * @param   bool   $debug   Dump the query
 * @return  array|bool Array of ID and section on success, FALSE otherwise
 * @package Filter
 * @example
 * if ($r = ckExID('my-article-title', 'my-section'))
 * {
 *     echo "Article #{$r['id']} is published, and belongs to the section {$r['section']}.";
 * }
 */

function lookupByTitleSection($val, $section, $debug = false)
{
    return safe_row("ID, Section", 'textpattern', "url_title = '".doSlash($val)."' AND Section = '".doSlash($section)."' AND Status >= 4 LIMIT 1", $debug);
}

/**
 * Lookup live article by ID and section.
 *
 * @param   int    $id      Article ID
 * @param   string $section Section name
 * @param   bool   $debug
 * @return  array|bool
 * @package Filter
 */

function lookupByIDSection($id, $section, $debug = false)
{
    return safe_row("ID, Section", 'textpattern', "ID = ".intval($id)." AND Section = '".doSlash($section)."' AND Status >= 4 LIMIT 1", $debug);
}

/**
 * Lookup live article by ID.
 *
 * @param   int  $id    Article ID
 * @param   bool $debug
 * @return  array|bool
 * @package Filter
 */

function lookupByID($id, $debug = false)
{
    return safe_row("ID, Section", 'textpattern', "ID = ".intval($id)." AND Status >= 4 LIMIT 1", $debug);
}

/**
 * Lookup live article by date and URL title.
 *
 * @param   string $when  date wildcard
 * @param   string $title URL title
 * @param   bool   $debug
 * @return  array|bool
 * @package Filter
 */

function lookupByDateTitle($when, $title, $debug = false)
{
    return safe_row("ID, Section", 'textpattern', "posted LIKE '".doSlash($when)."%' AND url_title LIKE '".doSlash($title)."' AND Status >= 4 LIMIT 1");
}

/**
 * Chops a request string into URL-decoded path parts.
 *
 * @param   string $req Request string
 * @return  array
 * @package URL
 */

function chopUrl($req)
{
    $req = strtolower(strtok($req, '?'));
    $req = preg_replace('/index\.php$/', '', $req);
    $r = array_map('urldecode', explode('/', $req));
    $n = max(4, count($r));

    for ($i = 0; $i < $n; $i++) {
        $o['u'.$i] = (isset($r[$i])) ? $r[$i] : '';
    }

    return $o;
}

/**
 * Save and retrieve the individual article's attributes plus article list
 * attributes for next/prev tags.
 *
 * @param   array $atts
 * @return  array
 * @since   4.5.0
 * @package TagParser
 */

function filterAtts($atts = null)
{
    global $prefs, $trace;
    static $out = array();

    if (is_array($atts)) {
        if (empty($out)) {
            $out = lAtts(array(
                'sort'     => 'Posted desc',
                'keywords' => '',
                'expired'  => $prefs['publish_expired_articles'],
                'id'       => '',
                'time'     => 'past',
            ), $atts, 0);
            $trace->log('[filterAtts accepted]');
        } else {
            // TODO: deal w/ nested txp:article[_custom] tags. See https://github.com/textpattern/textpattern/issues/1009
            $trace->log('[filterAtts ignored]');
        }
    }

    if (empty($out)) {
        $trace->log('[filterAtts not set]');
    }

    return $out;
}

/**
 * Set a flag to postpone tag processing.
 *
 * @param   int $pass
 * @return  null
 * @since   4.7.0
 * @package TagParser
 */

function postpone_process($pass = null)
{
    global $pretext, $txp_atts;

    $txp_atts['txp-process'] = intval($pass === null ? $pretext['secondpass'] + 2 : $pass);
}

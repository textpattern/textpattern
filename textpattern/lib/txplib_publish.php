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

function filterFrontPage($field = 'Section', $column = 'on_frontpage')
{
    static $filterFrontPage = array();
    global $txp_sections;

    is_array($column) or $column = do_list_unique($column);
    $key = $field.'.'.implode('.', $column);

    if (isset($filterFrontPage[$key])) {
        return $filterFrontPage[$key];
    }

    $filterFrontPage[$key] = ' AND 0';
    $field = doSlash($field);
    $rs = array();

    foreach ($column as $col) {
        $rs += array_filter(array_column($txp_sections, $col, 'name'));
    }

    if ($rs) {
        $filterFrontPage[$key] = count($rs) == count($txp_sections) ? '' : " AND $field IN(".quote_list(array_keys($rs), ',').")";
    }

    return $filterFrontPage[$key];
}

/**
 * Populates the current article data.
 *
 * Fills members of $thisarticle global from a database row.
 *
 * Keeps all article tag-related values in one place, in order to do easy
 * bugfixing and ease the addition of new article tags.
 *
 * @param array $rs An article as an associative array
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

    foreach (article_column_map() as $key => $column) {
        $thisarticle[$key] = isset($rs[$column]) ? $rs[$column] : null;
    }

    if ($production_status === 'debug') {
        $trace->log("[Article: '{$thisarticle['thisid']}']");
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
 * @param array $rs An article as an associative array
 * @example
 * article_format_info(
 *     safe_row('*', 'textpattern', 'Status = 4 LIMIT 1')
 * )
 */

function article_format_info($rs)
{
    $rs['uPosted']  = isset($rs['Posted']) && ($unix_ts = strtotime($rs['Posted'])) !== false ? $unix_ts : null;
    $rs['uLastMod'] = isset($rs['LastMod']) && ($unix_ts = strtotime($rs['LastMod'])) !== false ? $unix_ts : null;
    $rs['uExpires'] = isset($rs['Expires']) && ($unix_ts = strtotime($rs['Expires'])) !== false ? $unix_ts : null;
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
    static $column_map = array();

    if (empty($column_map)) {
        $column_map = array(
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
        );

        foreach (getCustomFields() as $i => $name) {
            isset($column_map[$name]) or $column_map[$name] = 'custom_'.$i;
        }
    }

    return $column_map;
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
    static $cache = array();
    static $types = array(
        '>' => array(
            'desc' => '>',
            'asc'  => '<',
        ),
        '<' => array(
            'desc' => '<',
            'asc'  => '>',
        ),
    );

    $key = md5($threshold.$s.$type.join(n, $atts));

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $thisid = isset($atts['thisid']) ? intval($atts['thisid']) : 0;
    $sortdir = isset($atts['sortdir']) ? strtolower($atts['sortdir']) : 'asc';
    $sortby = isset($atts['sortby']) ? $atts['sortby'] : 'Posted';

    // Invert $type for ascending sortdir.
    $type = ($type == '>') ? $types['>'][$sortdir] : $types['<'][$sortdir];

    // Escape threshold and treat it as a string unless explicitly told otherwise.
    if ($threshold_type != 'cooked') {
        $threshold = "'".doSlash($threshold)."'";
    }

    $where = isset($atts['*']) ? $atts['*'] : '1';
    $q = array(
        "SELECT *, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod FROM ".safe_pfx('textpattern')."
            WHERE ($sortby $type $threshold OR ".($thisid ? "$sortby = $threshold AND ID $type $thisid" : "0").")",
        "AND $where",
        "ORDER BY $sortby",
        ($type == '<') ? 'DESC' : 'ASC',
        ', ID '.($type == '<' ? 'DESC' : 'ASC'),
        "LIMIT 1",
    );

    $cache[$key] = getRow(join(' ', $q));

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
    $threshold_type = 'cooked';
    $atts = filterAtts() or $atts = filterAtts(array());

    if ($id !== 0) {
        // Pivot is specific article by ID: In lack of further information,
        // revert to default sort order 'Posted desc'.
        $atts += array(
            'sortby'  => 'Posted',
            'sortdir' => 'DESC',
            'thisid'  => $id,
        );
        $threshold_type = 'raw';
    } else {
        // Pivot is $thisarticle: Use article attributes to find its neighbours.
        assert_article();
        global $thisarticle;
        if (!is_array($thisarticle)) {
            return array();
        }

        $s = $thisarticle['section'];
        $atts += array(
            'thisid' => $thisarticle['thisid'],
            'sort'   => 'Posted DESC',
        );
        $atts['sort'] = trim($atts['sort']);

        if (empty($atts['sort'])) {
            $atts['sortby'] = !empty($atts['id']) ? "FIELD(ID, ".$atts['id'].")" : 'Posted';
            $atts['sortdir'] = !empty($atts['id']) ? 'ASC' : 'DESC';
        } elseif (preg_match('/^([$\w\x{0080}-\x{FFFF}]+|`[\x{0001}-\x{FFFF}]+`)(?i)(\s+asc|\s+desc)?$/u', $atts['sort'], $m)) {
            // The clause's first verb is a MySQL column identifier.
            $atts['sortby'] = trim($m[1], ' `');
            $atts['sortdir'] = (isset($m[2]) ? trim($m[2]) : 'ASC');
        } elseif (preg_match('/^((?>[^(),]|(\((?:[^()]|(?2))*\)))+)(\basc|\bdesc)?$/Ui', $atts['sort'], $m)) {
            // More complex unique clause.
            $atts['sortby'] = trim($m[1]);
            $atts['sortdir'] = (isset($m[3]) ? $m[3] : 'ASC');
        } else {
            $atts['sortby'] = 'Posted';
            $atts['sortdir'] = 'DESC';
        }

        // Attributes with special treatment.
        switch ($atts['sortby']) {
            case 'Posted':
                $threshold = "FROM_UNIXTIME(".doSlash($thisarticle['posted']).")";
                break;
            case 'Expires':
                $threshold = "FROM_UNIXTIME(".doSlash($thisarticle['expires']).")";
                break;
            case 'LastMod':
                $threshold = "FROM_UNIXTIME(".doSlash($thisarticle['modified']).")";
                break;
            default:
                // Retrieve current threshold value per sort column from $thisarticle.
                $threshold_type = 'raw';
                $acm = array_flip(article_column_map());

                if (isset($acm[$atts['sortby']])) {
                    $key = $acm[$atts['sortby']];
                    $threshold = $thisarticle[$key];
                } else {
                    $threshold = safe_field($atts['sortby'], 'textpattern', 'ID='.$atts['thisid']);
                }
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

function parse($thing, $condition = true, $in_tag = true)
{
    global $pretext, $production_status, $trace, $txp_parsed, $txp_else, $txp_atts, $txp_tag, $txp_current_tag;
    static $short_tags = null;

    if (!isset($short_tags)) {
        $short_tags = get_pref('enable_short_tags', false);
    }

    if (!isset($thing) || !$short_tags && false === strpos($thing, '<txp:') ||
        $short_tags && !preg_match('@<(?:'.TXP_PATTERN.'):@', $thing)) {
        $hash = null;
    } else {
        $hash = txp_hash($thing);
    }

    if (!empty($txp_atts['not']) && $in_tag) {
        $condition = empty($condition);
        $not = true;
    }

    $old_tag = $txp_tag;
    $txp_tag = !empty($condition);
    $log = $production_status === 'debug';

    if ($log) {
        $trace->log('['.($condition ? 'true' : 'false').']');
    }

    if (!isset($hash) || !isset($txp_parsed[$hash]) && !txp_tokenize($thing, $hash)) {
        $thing = $condition ? ($thing === null ? '1' : $thing) : '';

        if (isset($txp_atts['$query']) && $in_tag) {
            $thing = txp_eval(array('query' => $txp_atts['$query'], 'test' => $thing));
        }

        return $thing;
    }

    $tag = $txp_parsed[$hash];
    list($first, $last) = $txp_else[$hash];

    if ($condition) {
        $last = $first - 2;
        $first   = 1;
    } elseif ($first <= $last) {
        $first  += 2;
    } else {
        return '';
    }

    $this_tag = $txp_current_tag;
    $isempty = false;
    $dotest = !empty($txp_atts['evaluate']) && $in_tag;
    $evaluate = !$dotest ? null :
        ($txp_atts['evaluate'] === true ? true : do_list($txp_atts['evaluate']));

    if (isset($txp_else[$hash]['test']) && (!$evaluate || $evaluate === true)) {
        $evaluate = $txp_else[$hash]['test'];
    }

    if ($evaluate) {
        $test = is_array($evaluate) ? array_fill_keys($evaluate, array()) : false;
        $isempty = $last >= $first;
    }

    if (empty($test)) {
        for ($out = $tag[$first - 1]; $first <= $last; $first++) {
            $txp_tag = $tag[$first];
            $txp_current_tag = $txp_tag[0].$txp_tag[3].$txp_tag[4];
            $nextag = processTags($txp_tag[1], $txp_tag[2], $txp_tag[3], $log);
            $out .= $nextag.$tag[++$first];
            $isempty = $isempty && trim($nextag) === '';
        }
    } else {
        if ($pre = !isset($test[0])) {
            $test[0] = array();
        }

        $out = array($first-1 => $tag[$first-1]);

        for ($n = $first; $n <= $last; $n++) {
            $txp_tag = $tag[$n];
            $out[$n] = null;

            if (isset($test[($n+1)/2])) {
                $test[($n+1)/2][] = $n;
                $isempty = true;
            } elseif (isset($test[$txp_tag[1]])) {
                $test[$txp_tag[1]][] = $n;
                $isempty = true;
            } else {
                $test[0][] = $n;
            }

            $out[$n] = $tag[++$n];
        }

        foreach ($test as $k => $t) {
            if (!$k && $pre && $dotest && $isempty == empty($not)) {
                $out = false;    
                break;
            }

            foreach ($t as $n) {
                $txp_tag = $tag[$n];
                $txp_current_tag = $txp_tag[0].$txp_tag[3].$txp_tag[4];
                $nextag = processTags($txp_tag[1], $txp_tag[2], $txp_tag[3], $log);
                $out[$n] = $nextag;
                $k and ($isempty = $isempty && trim($nextag) === '');
            }
        }

        if (is_array($out)) {
            $out = implode('', $out);
        }
    }

    if ($dotest && $isempty == empty($not)) {
        $out = false;
    } elseif (isset($txp_atts['$query']) && $in_tag) {
        $out = txp_eval(array('query' => $txp_atts['$query'], 'test' => $out));
    }

    $out !== false or $condition = false;
    $txp_tag = $old_tag || !empty($condition);
    $txp_current_tag = $this_tag;

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
        global $plugins;

        if (empty($plugins)) {
            $tags = false;
        } else {
            $match = array();

            foreach ($plugins as $p) {
                $pfx = strpos($p, '_') === false ? $p : strtok($p, '_').'_';
                $match[$pfx] = preg_quote($pfx, '/');
            }

            $match = '/^('.implode('|', $match).')/i';
            $tags = get_defined_functions();
            $tags = array_filter($tags['user'], function ($f) use ($match) {
                return preg_match($match, $f);
            });
            $tags = array_flip($tags);
        }
    }

    return isset($tags[$tag]);
}

/**
 * Parse a tag for attributes and hand over to the tag handler function.
 *
 * @param  string      $tag   The tag name
 * @param  string      $atts  The attribute string
 * @param  string|null $thing The tag's content in case of container tags
 * @param  bool        $log   Trace log
 * @return string Parsed tag result
 * @package TagParser
 */

function processTags($tag, $atts = '', $thing = null, $log = false)
{
    global $pretext, $txp_atts, $txp_tag, $trace;
    static $registry = null, $globals;

    if (empty($tag)) {
        return;
    }

    if ($registry === null) {
        $registry = Txp::get('\Textpattern\Tag\Registry');
        $globals = array_filter(
            $registry->getRegistered(true),
            function ($v) {
                return !is_bool($v);
            }
        );
    }

    $old_atts = $txp_atts;

    if ($log) {
        $tag_stop = $txp_tag[4];
        $trace->start($txp_tag[0]);
    }

    if ($atts) {
        $split = splat($atts);

        if (isset($txp_atts['evaluate'])) {
            if (strpos($txp_atts['evaluate'], '<+>') !== false) {
                $txp_atts['$query'] = $txp_atts['evaluate'];
                unset($txp_atts['evaluate']);
            }
        }
    } else {
        $txp_atts = null;
        $split = array();
    }

    $txp_tag = null;
    $out = $registry->process($tag, $split, $thing);

    if ($out === false) {
        if (maybe_tag($tag)) { // Deprecated in 4.6.0.
            trigger_error($tag.' '.gTxt('unregistered_tag'), E_USER_NOTICE);
            $out = $registry->register($tag)->process($tag, $split, $thing);
        } else {
            trigger_error($tag.' '.gTxt('unknown_tag'), E_USER_WARNING);
            $out = '';
        }
    }

    if ($txp_tag === null && !empty($txp_atts['not'])) {
        $out = $out ? '' : '1';
    } elseif (isset($txp_atts['$query']) && $txp_tag !== false) {
        $out = txp_eval(array('query' => $txp_atts['$query'], 'test' => $out));
    }

    unset($txp_atts['not'], $txp_atts['evaluate'], $txp_atts['$query']);

    if ($txp_atts && $txp_tag !== false) {
        $pretext['_txp_atts'] = true;

        foreach ($txp_atts as $attr => &$val) {
            if (isset($val) && isset($globals[$attr])) {
                $out = $registry->processAttr($attr, $split, $out);
            }
        }

        $pretext['_txp_atts'] = false;
    }

    $txp_atts = $old_atts;

    if ($log) {
        $trace->stop($tag_stop);
    }

    return $out;
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
    $table === 'textpattern' or $table = 'txp_'.$table;

    if (is_array($val)) {
        $fields = implode(',', array_keys($val));
        $where = join_qs(quote_list(array_filter($val)), ' AND ');

        return safe_row($fields, $table, $where." LIMIT 1", $debug);
    } else {
        $fields = 'name';
        $where = "name = '".doSlash($val)."'";

        return safe_field($fields, $table, $where." LIMIT 1", $debug);
    }
}

/**
 * Checks if the given category exists.
 *
 * @param   string $type  The category type, either 'article', 'file', 'link', 'image'
 * @param   string $val   The category name to look for
 * @param   bool   $debug Dump the query
 * @return  bool|array The category's data, or FALSE when it doesn't exist
 * @package Filter
 * @see     ckEx()
 * @example
 * if ($r = ckCat('article', 'development'))
 * {
 *     echo "Category {$r['name']} exists.";
 * }
 */

function ckCat($type, $val, $debug = false)
{
    return safe_row("name, title, description, type", 'txp_category', "name = '".doSlash($val)."' AND type = '".doSlash($type)."' LIMIT 1", $debug);
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
    return safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "ID = ".intval($val)." AND Status >= 4 LIMIT 1", $debug
    );
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
    return safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "url_title = '".doSlash($val)."' AND Status >= 4 LIMIT 1", $debug
    );
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
    return safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "url_title = '".doSlash($val)."' AND Section = '".doSlash($section)."' AND Status >= 4 LIMIT 1", $debug
    );
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
    return safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "ID = ".intval($id)." AND Section = '".doSlash($section)."' AND Status >= 4 LIMIT 1", $debug
    );
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
    return safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "ID = ".intval($id)." AND Status >= 4 LIMIT 1", $debug
    );
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
    if ($when) {
        $offset = date('P', strtotime($when));
        $dateClause = ($offset ? "CONVERT_TZ(posted, @@session.time_zone, '$offset')" : 'posted')." LIKE '".doSlash($when)."%'";
    } else {
        $dateClause = '1';
    }

    return safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "url_title LIKE '".doSlash($title)."' AND Status >= 4 AND $dateClause LIMIT 1"
    );
}

/**
 * Save and retrieve the individual article's attributes plus article list
 * attributes for next/prev tags.
 *
 * @param   array $atts
 * @param   bool $iscustom
 * @return  array/string
 * @since   4.5.0
 * @package TagParser
 */

function filterAtts($atts = null, $iscustom = null)
{
    global $pretext, $trace, $thisarticle;
    static $out = array();

    if ($atts === false) {
        return $out = array();
    } elseif (!is_array($atts)) {
        // TODO: deal w/ nested txp:article[_custom] tags. See https://github.com/textpattern/textpattern/issues/1009
        $trace->log('[filterAtts ignored]');

        return $out;
    } elseif (isset($atts['*'])) {
        return $out = $atts;
    }

    $exclude = isset($atts['exclude']) ? $atts['exclude'] : '';
    unset($atts['exclude']);

    if ($exclude && $exclude !== true) {
        $exclude = array_map('strtolower', do_list_unique($exclude));
        $excluded = array_filter($exclude, 'is_numeric');
        empty($excluded) or $exclude = array_diff($exclude, $excluded);
    } else {
        $exclude or $exclude = array();
        $excluded = array();
    }

    $exclude === true or $exclude = array_fill_keys($exclude, true);

    $customFields = getCustomFields() + array('url_title' => 'url_title');
    $customlAtts = array_null(array_flip($customFields));

    $extralAtts = array(
        'form'          => 'default',
        'allowoverride' => !$iscustom,
        'limit'         => 10,
        'offset'        => 0,
        'pageby'        => null,
        'pgonly'        => 0,
        'wraptag'       => '',
        'break'         => '',
        'label'         => '',
        'labeltag'      => '',
        'class'         => '',
        'searchall'     => !$iscustom && !empty($pretext['q']),
    );

    if ($iscustom) {
        $customlAtts = array(
            'category'  => '',
            'section'   => '',
            'author'    => '',
            'month'     => '',
            'expired'   => get_pref('publish_expired_articles'),
        ) + $customlAtts;
    } else {
        $extralAtts += array(
            'listform'     => '',
            'searchform'   => '',
            'searchsticky' => 0,
        );
    }

    if ($exclude && is_array($exclude)) {
        foreach ($exclude as $cField => $val) {
            if (array_key_exists($cField, $customlAtts) && !isset($atts[$cField])) {
                $atts[$cField] = $val;
            }
        }
    }

    // Getting attributes.
    $theAtts = lAtts(array(
        'fields'        => null,
        'sort'          => '',
        'keywords'      => '',
        'time'          => null,
        'status'        => empty($atts['id']) ? STATUS_LIVE : true,
        'frontpage'     => !$iscustom,
        'match'         => 'Category',
        'depth'         => 0,
        'id'            => '',
        'excerpted'     => ''
    ) + $extralAtts + $customlAtts, $atts);

    // For the txp:article tag, some attributes are taken from globals;
    // override them, then stash all filter attributes.
    extract($pretext);

    if (!$iscustom) {
        $theAtts['category'] = ($c) ? $c : '';
        $theAtts['section'] = ($s && $s != 'default') ? $s : '';
        $theAtts['author'] = (!empty($author) ? $author : '');
        $theAtts['month'] = (!empty($month) ? $month : '');
        $theAtts['expired'] = get_pref('publish_expired_articles');
        $theAtts['frontpage'] = ($theAtts['frontpage'] && !$theAtts['section']);
    } else {
        $q = '';
    }

    extract($theAtts);

    // Treat sticky articles differently wrt search filtering, etc.
    $issticky = in_array(strtolower($status), array('sticky', STATUS_STICKY));

    if ($status === true) {
        $status = array(STATUS_LIVE, STATUS_STICKY);
    } else {
        $status = array($issticky ? STATUS_STICKY : STATUS_LIVE);
    }

    // Categories
    $operator = 'AND';
    $match = parse_qs($match);

    if (isset($match['category'])) {
        isset($match['category1']) or $match['category1'] = $match['category'];
        isset($match['category2']) or $match['category2'] = $match['category'];
        $operator = 'OR';
    }

    $categories = $category === true ? false : do_list_unique($category);
    $catquery = array();

    if ($categories && (!$depth || $categories = getTree($categories, 'article', '1', 'txp_category', $depth))) {
        $categories  = join("','", doSlash($categories));
    }

    for ($i = 1; $i <= 2; $i++) {
        $not = isset($exclude["category{$i}"]) ? '!' : '';

        if (isset($match['category'.$i])) {
            if ($match['category'.$i] === false) {
                if ($categories) {
                    $catquery[] = "$not(Category{$i} IN ('$categories'))";
                } elseif ($category === true || $not) {
                    $catquery[] = "$not(Category{$i} != '')";
                }
            } elseif (($val = gps($match['category'.$i], false)) !== false) {
                $catquery[] = "$not(Category{$i} IN (".quote_list(do_list($val), ',')."))";
            }
        } elseif ($not) {
            $catquery[] = "(Category{$i} = '')";
        }
    }

    $not = $iscustom && ($exclude === true || isset($exclude['category'])) ? '!' : '';
    $catquery = join(" $operator ", $catquery);
    $category  = !$catquery  ? '' : " AND $not($catquery)";

    // ID
    $not = $exclude === true || isset($exclude['id']) ? 'NOT' : '';
    $ids = $id ? ($id === true ? array(article_id()) : array_map('intval', do_list_unique($id, array(',', '-')))) : array();
    $id        = ((!$ids)        ? '' : " AND ID $not IN (".join(',', $ids).")")
        .(!$excluded   ? '' : " AND ID NOT IN (".join(',', $excluded).")");
    $getid = $ids && !$not;

    // Section
    // searchall=0 can be used to show search results for the current
    // section only.
    if ($q && $searchall && !$issticky) {
        $section = '';
    }

    $not = $iscustom && ($exclude === true || isset($exclude['section'])) ? 'NOT' : '';
    $section !== true or $section = processTags('section');
    $getid = $getid || $section && !$not;
    $section   = (!$section   ? '' : " AND Section $not IN ('".join("','", doSlash(do_list_unique($section)))."')").
        ($getid || $searchall? '' : filterFrontPage('Section', 'page'));


    // Author
    $not = $iscustom && ($exclude === true || isset($exclude['author'])) ? 'NOT' : '';
    $author !== true or $author = processTags('author', 'escape="" title=""');
    $author    = (!$author)    ? '' : " AND AuthorID $not IN ('".join("','", doSlash(do_list_unique($author)))."')";

    $frontpage = ($frontpage && (!$q || $issticky)) ? filterFrontPage() : '';
    $excerpted = (!$excerpted) ? '' : " AND Excerpt !=''";

    if ($time === null || $month || !$expired || $expired == '1') {
        $not = $iscustom && ($month || $time !== null) && ($exclude === true || isset($exclude['month']));
        $timeq = buildTimeSql($month, $time === null ? 'past' : $time);
        $timeq = ' AND '.($not ? "!($timeq)" : $timeq);
    } else {
        $timeq = '';
    }

    if ($expired && $expired != '1') {
        $timeq .= ' AND '.buildTimeSql($expired, $time === null && !strtotime($expired) ? 'any' : $time, 'Expires');
    } elseif (!$expired) {
        $timeq .= ' AND (Expires IS NULL OR '.now('expires').' <= Expires)';
    }

    if ($q && $searchsticky) {
        $statusq = " AND Status >= ".STATUS_LIVE;
    } else {
        $statusq = " AND Status IN (".implode(',', $status).")";
    }

    $custom = '';

    if ($customFields) {
        foreach ($customFields as $cField) {
            if (isset($atts[$cField])) {
                $customPairs[$cField] = $atts[$cField];
            }

            if (isset($match[$cField])) {
                if ($match[$cField] === false && isset($thisarticle[$cField])) {
                    $customPairs[$cField] = $thisarticle[$cField];
                } elseif (($val = gps($match[$cField] === false ? $cField : $match[$cField], false)) !== false) {
                    $customPairs[$cField] = $val;
                }
            }
        }

        if (!empty($customPairs)) {
            $custom = buildCustomSql($customFields, $customPairs, $exclude);
        }
    }

    // Allow keywords for no-custom articles. That tagging mode, you know.
    $not = $exclude === true || isset($exclude['keywords']) ? '!' : '';
    $keyparts = array();

    if ($keywords === true) {
        $keyparts[] = "Keywords != ''";
    } else {
        if (!$keywords && isset($match['keywords'])) {
            $keywords = $match['keywords'] === false && isset($thisarticle['keywords']) ?
                $thisarticle['keywords'] :
                gps($match['keywords'] ? $match['keywords'] : 'keywords');
        }

        if ($keywords) {
            foreach (doSlash(do_list_unique($keywords)) as $key) {
                $keyparts[] = "FIND_IN_SET('".$key."', Keywords)";
            }
        }
    }

    $keywords = $keyparts ? " AND $not(".join(' or ', $keyparts).")" : '';

    $theAtts['status'] = implode(',', $status);
    $theAtts['id'] = implode(',', $ids);
    $theAtts['sort'] = sanitizeForSort($sort);
    $theAtts['*'] = '1'.$timeq.$id.$category.$section.$excerpted.$author.$statusq.$frontpage.$keywords.$custom;

    if (!$iscustom) {
        $out = array_diff_key($theAtts, $extralAtts);
        $trace->log('[filterAtts accepted]');
    }

    return $theAtts;
}

/**
 * Postpone tag processing.
 *
 * @param   int $pass
 * @return  null|string
 * @since   4.7.0
 * @package TagParser
 */

function postpone_process($pass = null)
{
    global $pretext, $txp_atts, $txp_current_tag;

    $txp_atts = null;
    $pass = max($pretext['secondpass'] + 2, (int)$pass) - 1;

    if ($pass <= (int)get_pref('secondpass', 1)) {
        return $txp_current_tag;
    } else {
        trigger_error(gTxt('secondpass').' < '.$pass, E_USER_WARNING);
    }
}

// -------------------------------------------------------------

function parseList($rs, &$object, $populate, $atts = array())
{
    global $txp_atts, $txp_item, $txp_sections;

    $articles = array();

    if ($rs && $last = numRows($rs)) {
        extract($atts + array(
                'form' => '',
                'thing' => null,
                'breakby' => isset($txp_atts['breakby']) ? $txp_atts['breakby'] : '',
                'breakform' => isset($txp_atts['breakform']) ? $txp_atts['breakform'] : '',
                'allowoverride' => false
            )
        );

        $store = $object;
        $count = 0;
        $chunk = false;
        $old_item = $txp_item;
        $txp_item['total'] = $last;
        unset($txp_item['breakby']);
        $groupby = !$breakby || is_numeric(strtr($breakby, ' ,', '00')) ?
            false :
            (preg_match('@<(?:'.TXP_PATTERN.'):@', $breakby) ? (int)php(null, null, 'form') : 2);

        while ($count++ <= $last) {
            if ($a = nextRow($rs)) {
                $res = call_user_func($populate, $a);

                if (is_array($res)) {
                    $object = $res;
                }
 
                $object['is_first'] = ($count == 1);
                $object['is_last'] = ($count == $last);
                $txp_item['count'] = isset($a['count']) ? $a['count'] : $count;

                $newbreak = !$groupby ? $count : ($groupby === 1 ?
                    parse($breakby, true, false) :
                    parse_form($breakby)
                );
            } else {
                $newbreak = null;
            }

            if (isset($txp_item['breakby']) && $newbreak !== $txp_item['breakby']) {
                if ($groupby && $breakform) {
                    $tmpobject = $object;
                    $object = $oldobject;
                    $newform = parse_form($breakform);
                    $chunk = str_replace('<+>', $chunk, $newform);
                    $object = $tmpobject;
                }

                $chunk === false or $articles[] = $chunk;
                $chunk = false;
            }

            if ($count <= $last) {
                $item = false;

                if ($allowoverride && !empty($a['override_form'])) {
                    $item = parse_form($a['override_form'], $txp_sections[$a['Section']]['skin']);
                } elseif ($form) {
                    $item = parse_form($form);
                }

                if ($item === false && isset($thing)) {
                    $item = parse($thing);
                }

                $item === false or $chunk .= $item;
            }

            $oldobject = $object;
            $txp_item['breakby'] = $newbreak;
        }

        if ($groupby) {
            unset($txp_atts['breakby'], $txp_atts['breakform']);
        }

        $txp_item = $old_item;
        $object = $store;
    }

    return $articles;
}

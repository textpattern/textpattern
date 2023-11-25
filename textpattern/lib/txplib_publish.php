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

function filterFrontPage($field = 'Section', $column = array('on_frontpage'), $not = false)
{
    static $filterFrontPage = array();
    global $txp_sections;

    is_array($column) or $column = do_list_unique($column);
    $column = array_intersect($column, array_keys($txp_sections['default']));
    sort($column);
    $key = $field.'.'.implode('.', $column);
    $not = $not ? 'NOT ' : '';

    if (!isset($filterFrontPage[$key])) {
        $filterFrontPage[$key] = '0';
        $rs = array();

        if ($field) {
            $num_sections = count($txp_sections);
            $field = doSlash($field);

            foreach ($column as $col) {
                $rs += array_filter(array_column($txp_sections, $col, 'name'));
            }

            if ($count = count($rs)) {
                $filterFrontPage[$key] = $count == $num_sections ? '1' : (2*$count < $num_sections ?
                    "$field IN(".quote_list(array_keys($rs), ',').")" :
                    "NOT $field IN(".quote_list(array_keys(array_diff_key($txp_sections, $rs)), ',').")"
                );
            }
        } elseif($column) {
            foreach ($column as $col) {
                $rs[] = is_numeric($txp_sections['default'][$col]) ? $col : "$col > ''";
            }

            $filterFrontPage[$key] = implode(' AND ', $rs);
        }
    }

    return ' AND '.preg_replace('/^NOT NOT /', '',  $not.$filterFrontPage[$key]);
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

    $where = isset($atts['?']) ? $atts['?'] : '1';
    $tables = isset($atts['#']) ? $atts['#'] : safe_pfx('textpattern');
    $columns = isset($atts['*']) ? $atts['*'] : '*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod';

    $q = array(
        "SELECT $columns FROM $tables",
        "WHERE ($sortby $type $threshold OR ".($thisid ? "$sortby = $threshold AND ID $type $thisid" : "0").") AND $where",
        "ORDER BY $sortby ".($type == '<' ? 'DESC' : 'ASC').', ID '.($type == '<' ? 'DESC' : 'ASC').' LIMIT 1'
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

    ksort($atts);
    $out['>'] = getNeighbour($threshold, $s, '>', $atts, $threshold_type);
    $out['<'] = getNeighbour($threshold, $s, '<', $atts, $threshold_type);

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
    static $registry = null, $globatts;

    if (empty($tag)) {
        return;
    }

    if ($registry === null) {
        $registry = Txp::get('\Textpattern\Tag\Registry');
        $globatts = array_filter(
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

        if (isset($txp_atts['evaluate']) && strpos($txp_atts['evaluate'], '<+>') !== false) {
            $txp_atts['$query'] = $txp_atts['evaluate'];
            unset($txp_atts['evaluate']);
        }
    } else {
        $txp_atts = null;
        $split = array();
    }

    $txp_tag = null;
    $out = $registry->process($tag, $split, $thing);

    if ($out === false) {
        trigger_error($tag.' '.gTxt('unknown_tag'), E_USER_WARNING);
        $out = '';
    } else {
        if ($txp_tag === null && !empty($txp_atts['not'])) {
            $out = $out ? '' : '1';
        } elseif (isset($txp_atts['$query']) && $txp_tag !== false) {
            $out = txp_eval(array('query' => $txp_atts['$query'], 'test' => $out));
        }

        unset($txp_atts['not'], $txp_atts['evaluate'], $txp_atts['$query']);

        if ($txp_atts && $txp_tag !== false) {
            $pretext['_txp_atts'] = true;

            foreach ($txp_atts as $attr => $val) {
                if (isset($txp_atts[$attr]) && isset($globatts[$attr])) {
                    $out = $registry->processAttr($attr, $txp_atts, $out);
                }
            }

            $pretext['_txp_atts'] = false;
        }
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
    $res = safe_row("name, title, description, type", 'txp_category', "name = '".doSlash($val)."' AND type = '".doSlash($type)."' LIMIT 1", $debug);

    return ($res && $res['name'] == $val) ? $res : false;
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
 * if ($r = lookupByTitle('my-article-title'))
 * {
 *     echo "Article #{$r['id']} is published, and belongs to the section {$r['section']}.";
 * }
 */

function lookupByTitle($val, $debug = false)
{
    $res = safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "url_title = '".doSlash($val)."' LIMIT 1", $debug
    );

    return ($res && $res['url_title'] == $val) ? $res : false;
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
 * if ($r = lookupByTitleSection('my-article-title', 'my-section'))
 * {
 *     echo "Article #{$r['id']} is published, and belongs to the section {$r['section']}.";
 * }
 */

function lookupByTitleSection($val, $section, $debug = false)
{
    $res = safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "url_title = '".doSlash($val)."' AND Section = '".doSlash($section)."' LIMIT 1", $debug
    );

    return ($res && $res['url_title'] == $val && $res['Section'] == $section) ? $res : false;
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
    $res = safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "ID = ".intval($id)." AND Section = '".doSlash($section)."' LIMIT 1", $debug
    );

    return ($res && $res['Section'] == $section) ? $res : false;
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
        "ID = ".intval($id)." LIMIT 1", $debug
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

    $res = safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(Expires) AS uExpires, UNIX_TIMESTAMP(LastMod) AS uLastMod",
        'textpattern',
        "url_title = '".doSlash($title)."' AND $dateClause LIMIT 1"
    );

    return ($res && $res['url_title'] == $title) ? $res : false;
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
    global $is_article_list, $pretext, $trace, $thisarticle;
    static $date_fields = array('posted' => 'Posted', 'modified' => 'LastMod', 'expires' => 'Expires');
    static $aggregate = array(
        'avg' => 'AVG(?)',
        'max' => 'MAX(?)',
        'min' => 'MIN(?)',
        'sum' => 'SUM(?)',
        'list' => "GROUP_CONCAT(? SEPARATOR ',')",
        'concat' => "GROUP_CONCAT(? SEPARATOR ',')"
    ), $windowed = array(
        'count' => 'COUNT(*)',
        'dense' => 'DENSE_RANK()',
        'rank' => 'RANK()',
        'row' => 'ROW_NUMBER()'
    );
    static $out = array();

    if ($atts === false) {
        return $out = array();
    } elseif (!is_array($atts)) {
        // TODO: deal w/ nested txp:article[_custom] tags. See https://github.com/textpattern/textpattern/issues/1009
        $trace->log('[filterAtts ignored]');

        return $out;
    } elseif (isset($atts['?'])) {
        return $out = $atts;
    }

    $excluded = isset($atts['exclude']) ? $atts['exclude'] : '';

    if ($excluded && $excluded !== true) {
        $excluded = array_map('strtolower', do_list_unique($excluded));
        $excludid = array_filter($excluded, 'is_numeric');
        empty($excludid) or $excluded = array_diff($excluded, $excludid);
    } else {
        $excluded or $excluded = array();
        $excludid = array();
    }

    $excluded === true or $excluded = array_fill_keys($excluded, true);

    $customFields = getCustomFields() + array('url_title' => 'url_title');
    $postWhere = $customPairs = $customlAtts = array();

    foreach ($customFields as $num => $field) {
        $customlAtts[$field] = null;

        if (isset($atts['custom_'.$num])) {
            $customPairs[$field] = $atts['custom_'.$num];
            $customlAtts['custom_'.$num] = null;
        } elseif (isset($excluded[$field])) {
            $customPairs[$field] = true;
        }
    }

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

    $sortAtts = array(
        'fields'        => null,
        'sort'          => '',
        'keywords'      => '',
        'time'          => null,
        'status'        => empty($atts['id']) ? STATUS_LIVE : true,
        'frontpage'     => !$iscustom,
        'match'         => 'Category',
        'depth'         => 0,
        'id'            => '',
        'excerpted'     => '',
        'exclude'       => ''
    );

    if ($iscustom) {
        $sortAtts += array(
            'category'  => isset($excluded['category']) ? true : '',
            'section'   => isset($excluded['section']) ? true : '',
            'author'    => isset($excluded['author']) ? true : '',
            'month'     => isset($excluded['month']) ? true : '',
            'expired'   => isset($excluded['expired']) ? true : get_pref('publish_expired_articles'),
        );
    } else {
        $extralAtts += array(
            'listform'     => '',
            'searchform'   => '',
            'searchsticky' => 0,
        );
    }

    $coreColumns = array(
        'posted'   => 'UNIX_TIMESTAMP(Posted) AS uPosted',
        'expires'  => 'UNIX_TIMESTAMP(Expires) AS uExpires',
        'modified' => 'UNIX_TIMESTAMP(LastMod) AS uLastMod',
        ) + article_column_map();

    foreach ($windowed + $coreColumns as $field => $val) {
        if (isset($atts['$'.$field])) {
            $postWhere['$'.$field] = $atts['$'.$field];
            unset($atts['$'.$field]);
        }
    }

    // Getting attributes.
    $theAtts = lAtts($sortAtts + $extralAtts + $customlAtts, $atts);

    // For the txp:article tag, some attributes are taken from globals;
    // override them, then stash all filter attributes.
    if (!$iscustom) {
        $theAtts['category'] = !empty($pretext['c']) ? $pretext['c'] : '';
        $theAtts['section'] = (!empty($pretext['s']) && $pretext['s'] != 'default') ? $pretext['s'] : '';
        $theAtts['author'] = (!empty($pretext['author']) ? $pretext['author'] : '');
        $theAtts['month'] = (!empty($pretext['month']) ? $pretext['month'] : '');
        $theAtts['expired'] = get_pref('publish_expired_articles');
        $theAtts['frontpage'] = ($theAtts['frontpage'] && !$theAtts['section']);
        $q = trim($pretext['q']);
    } else {
        $q = '';
    }

    extract($theAtts, EXTR_SKIP);

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

    if ($categories) {
        !$depth or $categories = getTree($categories, 'article', '1', 'txp_category', $depth) or $categories = array('/');
        $categories = quote_list($categories, ',');
    }

    for ($i = 1; $i <= 2; $i++) {
        $not = isset($excluded["category{$i}"]) ? '!' : '';

        if (isset($match['category'.$i])) {
            if ($match['category'.$i] === false) {
                if ($categories) {
                    $catquery[] = "$not(Category{$i} IN ($categories))";
                } elseif ($category === true || $not) {
                    $catquery[] = "$not(Category{$i} != '')";
                }
            } elseif (($val = gps($match['category'.$i], false)) !== false) {
                $categories = $depth ? getTree(do_list($val), 'article', '1', 'txp_category', $depth) : do_list($val);
                $catquery[] = $categories ? "$not(Category{$i} IN (".quote_list($categories, ',')."))" : "$not 0";
            }
        } elseif ($not) {
            $catquery[] = "(Category{$i} = '')";
        }
    }

    $not = $iscustom && ($excluded === true || isset($excluded['category'])) ? '!' : '';
    $catquery = join(" $operator ", $catquery);
    $category  = !$catquery  ? '' : " AND $not($catquery)";

    // ID
    $not = $excluded === true || isset($excluded['id']) ? 'NOT' : '';
    $ids = $id ? ($id === true ? article_id() : join(',', array_map('intval', do_list_unique($id, array(',', '-'))))) : false;
    $id = ($ids? " AND ID $not IN ($ids)" : '').(!$excludid ? '' : " AND ID NOT IN (".join(',', $excludid).")");
    $getid = $ids && !$not;

    // Section
    // searchall=0 can be used to show search results for the current
    // section only.
    if ($q && $searchall && !$issticky) {
        $section = '';
    }

    $not = $iscustom && ($excluded === true || isset($excluded['section'])) ? 'NOT' : '';
    $section !== true or $section = processTags('section');
    $section   = (!$section   ? '' : " AND Section $not IN ('".join("','", doSlash(do_list_unique($section)))."')").
        ($getid || $section && !$not || $searchall? '' : filterFrontPage('Section', 'page'));


    // Author
    $not = $iscustom && ($excluded === true || isset($excluded['author'])) ? 'NOT' : '';
    $author !== true or $author = processTags('author', 'escape="" title=""');
    $author    = (!$author)    ? '' : " AND AuthorID $not IN ('".join("','", doSlash(do_list_unique($author)))."')";

    $frontpage = ($frontpage && (!$q || $issticky)) ? filterFrontPage('Section', 'on_frontpage', (int)$frontpage < 0) : '';
    $excerpted = (!$excerpted) ? '' : " AND Excerpt !=''";

    if ($time === null || $month || !$expired || $expired == '1') {
        $not = $iscustom && ($month || $time !== null) && ($excluded === true || isset($excluded['month']));
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

    // Allow keywords for no-custom articles. That tagging mode, you know.
    $not = $excluded === true || isset($excluded['keywords']) ? '!' : '';
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

    // Give control to search, if necessary.
    $search = $score = $smatch = '';

    if ($q && !$issticky) {
        $s_filter = $searchall ? filterFrontPage('Section', 'searchable') : (empty($s) || $s == 'default' ? filterFrontPage() : '');
        $quoted = ($q[0] === '"') && ($q[strlen($q) - 1] === '"');
        $q = doSlash($quoted ? trim(trim($q, '"')) : $q);
        $m = $pretext['m'];

        // Searchable article fields are limited to the columns of the
        // textpattern table and a matching fulltext index must exist.
        $cols = do_list_unique(get_pref('searchable_article_fields')) or $cols = array('Title', 'Body');

        if ($m == 'natural') {
            $smatch = "MATCH (`".join("`, `", $cols)."`) AGAINST ('$q' IN NATURAL LANGUAGE MODE)";
        }

        if (!$sort || strpos($sort, 'score') !== false) {
            !empty($smatch) or $smatch = "MATCH (`".join("`, `", $cols)."`) AGAINST ('$q')";
            $score = ', '.(empty($groupby) ? $smatch : "MAX($smatch)").' AS score';
            $sort or $sort = 'score DESC';
        }

        $search_terms = preg_replace('/\s+/', ' ', str_replace(array('\\', '%', '_', '\''), array('\\\\', '\\%', '\\_', '\\\''), $q));

        if ($quoted || empty($m) || $m === 'exact') {
            for ($i = 0; $i < count($cols); $i++) {
                $cols[$i] = "`$cols[$i]` LIKE '%$search_terms%'";
            }
        } else {
            $colJoin = ($m === 'all') ? "AND" : "OR";
            $search_terms = explode(' ', $search_terms);

            for ($i = 0; $i < count($cols); $i++) {
                $like = array();
                foreach ($search_terms as $search_term) {
                    $like[] = "`$cols[$i]` LIKE '%$search_term%'";
                }
                $cols[$i] = "(".join(" $colJoin ", $like).")";
            }
        }

        $cols = join(" OR ", $cols);
        $search = " AND ($cols) $s_filter".($m == 'natural' ? " AND $smatch" : '');
        $fname = $searchform ? $searchform : (isset($thing) ? '' : 'search_results');
    } else {
        $fname = $is_article_list && !empty($listform) ? $listform : $form;
    }

    // Title
    if ($url_title && $url_title !== true) {
        $atts['url_title'] = do_list_unique($url_title);
    }

    // Custom fields
    foreach ($customFields as $cField) {
        if (isset($atts[$cField]) && !isset($extralAtts[$cField]) && !isset($sortAtts[$cField])) {
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

    // Fields
    $sort = $sort ? sanitizeForSort($sort) : '';
    $partition = array();

    if (!empty($fields) && $fields !== true) {
        $what = $alias = $groupby = $sortby = array();
        $column_map = $date_fields + article_column_map();
        $reg_fields = implode('|', array_keys($column_map)).'|\*';
        $agg_reg = implode('|', array_keys($aggregate));
        $regexp = $agg_reg.'|'.implode('|', array_keys($windowed)).'|date|day|month|year|week|quarter';

        preg_match_all("/(?<=,|^)\s*(?:($regexp)(?:\[([^\]]*)\])?\((?:\s*($agg_reg)\(\s*)?)?($reg_fields)(\s+asc|\s+desc)?\s*\){0,2}\s*(?:,|$)/", strtolower($fields), $matches, PREG_SET_ORDER);
        $aggFields = array_column($matches, 1, 4);
        $groupped = true;
        $psort = $sort;

        foreach ($matches as $match) {
            $format = doSlash($match[2]);
            $field = $match[4];
            $dir = isset($match[5]) ? $match[5] : '';
            $column = isset($column_map[$field]) ? $column_map[$field] : 'ID';

            if (isset($windowed[$match[1]])) {
                if ($format === '*') {
                    $parby = implode(', ', $groupby);
                    $groupped = false;
                } else {
                    $parby = $format ? $format : '%';
                }

                if ($match[1] == 'count') {
                    $pattern = "(? OVER (PARTITION BY $parby))";
                } else {
                    $orderby = $field === '*' ? $psort : "`$column`$dir";
                    $pattern = "(? OVER (PARTITION BY $parby ORDER BY $orderby))";
                    $field !== '*' or $sort = '';
                }

                $column = $field === '*' ? '$'.$match[1] : '$'.$field;
                $what[$column] = $windowed[$match[1]];
                $alias[$column] = " AS `$column`";
                $sortby[$column] = '';
                $partition[$column] = $pattern;
                unset($customPairs[$field]);
            } elseif (!$match[1] && $field === '*') {
                $addFields = true;
                $groupped = false;
            } else {
                $custom = "`$column`";
                $alias[$field] = $match[1] ? " AS `$column`" : '';
                $sortby[$column] = $dir;

                if (!$match[1]) {
                    $field != 'thisid' or $groupped = false;
                    $what[$field] = $custom;
                    $groupby[$column] = $custom;
                } elseif (isset($aggregate[$match[1]])) {
                    $what[$field] = strtr($aggregate[$match[1]], array('?' => $custom, ',' => $format ? $format : ','));
                    $parby = implode(', ', $groupby);
                    !$format or $match[1] == 'list' or $partition[$field] = $format == '*' ?  "(? OVER (PARTITION BY $parby))" : "(? OVER (PARTITION BY $format))";
                } else {
                    $what[$field] = "MIN($custom)";
                    $groupby[$format ? "DATE_FORMAT($custom, '$format')" : strtoupper($match[1]).'('.$custom.')'] = $custom;
                }

                if (isset($date_fields[$field])) {
                    $what['u'.$field] = 'UNIX_TIMESTAMP('.$what[$field].')';
                    $alias['u'.$field] = " AS `u{$column}`";
                }
            }
        }

        $parby = implode(', ', $groupby);

        foreach ($what as $field => $custom) {
            if (isset($partition[$field])) {
                $what[$field] = strtr($partition[$field], array('?' => $what[$field], '%' => $parby));
            }

            $what[$field] .= $alias[$field];
        }

        if (!empty($addFields)) {
            foreach (array_diff_key($column_map, $what) as $field => $column) {
                $what[$field] = $coreColumns[$field];
            }
        }

        $fields = implode(', ', $what);
        $groupped or $groupby = false;
        $postWhere = array_intersect_key($postWhere, $what);

        if (!$sort) {
            foreach ($sortby as $key => $val) {
                $sort .= ($sort ? ', ' : '').$key.$val;
            }
        }
    }

    $custom = buildCustomSql($customFields, $customPairs, $excluded);
    $postWhere = empty($what) ? false : array_intersect_key($postWhere, $what);

    if ($fields) {
        $fields = ($groupby ? 'COUNT(*) AS count, ' : '').$fields.$score;
    } else {
        $fields = implode(', ', $coreColumns).$score;
    }

    $theAtts['status'] = implode(',', $status);
    $theAtts['id'] = $ids;
    $theAtts['form'] = $fname;
    $theAtts['sort'] = $sort ? $sort : ($getid ? "FIELD(ID, $ids)" : 'Posted DESC');
    $theAtts['%'] = empty($groupby) ? null : implode(', ', $groupby);
    $theAtts['$'] = '1'.$timeq.$id.$category.$section.$frontpage.$excerpted.$author.$statusq.$keywords.$url_title.$search.$custom;
    $theAtts['?'] = $theAtts['$'].(empty($groupby) ? '' : " GROUP BY ".implode(', ', array_keys($groupby)));
    $theAtts['#'] = safe_pfx('textpattern');
    $theAtts['*'] = $fields;

    if (!empty($postWhere)) {
        $theAtts['%'] = null;
        $theAtts['#'] = '(SELECT '.$theAtts['*'].' FROM '.$theAtts['#'].' WHERE '.$theAtts['?'].') AS textpattern';
        $theAtts['*'] = '*';
        $theAtts['$'] = $theAtts['?'] = '1'.buildCustomSql(null, $postWhere, $excluded);
    }

    if (!$iscustom) {
        $out = array_diff_key($theAtts, $extralAtts);
        $trace->log('[filterAtts accepted]');
    }

    return $theAtts;
}

/**
 * Postpone tag processing.
 *
 * @param   null|int $maxpass
 * @return  null|string
 * @since   4.7.0
 * @package TagParser
 */

function postpone_process($maxpass = null)
{
    global $pretext, $txp_atts, $txp_current_tag;

    $txp_atts = null;
    $pass = max($pretext['secondpass'] + 2, (int)$maxpass) - 1;

    if ($pass <= (int)get_pref('secondpass', 1)) {
        return $txp_current_tag;
    } elseif (!isset($maxpass)) {
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

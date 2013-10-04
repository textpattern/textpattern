<?php

/*
This is Textpattern

Copyright 2012 The Textpattern Development Team
textpattern.com
All rights reserved.

Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

@since 4.5.0
*/

/**
 * Build a query qualifier to remove non-frontpage articles from the result set
 *
 * @return string A SQL qualifier for a querys 'WHERE' part
 */
function filterFrontPage()
{
	static $filterFrontPage;

	if (isset($filterFrontPage)) {
		return $filterFrontPage;
	}

	$filterFrontPage = false;

	$rs = safe_column('name', 'txp_section', "on_frontpage != '1'");

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
 * Fill members of $thisarticle from a DB row assoc.
 *
 * Keep all the article tag-related values in one place,
 * in order to do easy bugfix and ease the addition of
 * new article tags.
 *
 * @param array $rs An assoc w/ one article's data from the DB
 */
function populateArticleData($rs)
{
	global $thisarticle;

	trace_add("[".gTxt('Article')." {$rs['ID']}]");
	foreach (article_column_map() as $key => $column) {
		$thisarticle[$key] = $rs[$column];
	}
}

/**
 * Convenience for those who prefer "SELECT * FROM textpattern"
 *
 * @param array $rs An assoc w/ one article's data from the DB
 */
function article_format_info($rs)
{
	$rs['uPosted'] = (($unix_ts = @strtotime($rs['Posted'])) > 0) ? $unix_ts : NULLDATETIME;
	$rs['uLastMod'] = (($unix_ts = @strtotime($rs['LastMod'])) > 0) ? $unix_ts : NULLDATETIME;
	$rs['uExpires'] = (($unix_ts = @strtotime($rs['Expires'])) > 0) ? $unix_ts : NULLDATETIME;

	populateArticleData($rs);
}

/**
 * @return array
 */
function article_column_map()
{
	$custom = getCustomFields();
	$custom_map = array();
	if ($custom) {
		foreach ($custom as $i => $name)
			$custom_map[$name] ='custom_' . $i;
	}

	return array(
		'thisid' => 'ID',
		'posted' => 'uPosted',		// calculated value!
		'expires' => 'uExpires',	// calculated value!
		'modified' => 'uLastMod',	// calculated value!
		'annotate' => 'Annotate',
		'comments_invite' => 'AnnotateInvite',
		'authorid' => 'AuthorID',
		'title' => 'Title',
		'url_title' => 'url_title',
		'category1' => 'Category1',
		'category2' => 'Category2',
		'section' => 'Section',
		'keywords' => 'Keywords',
		'article_image' => 'Image',
		'comments_count' => 'comments_count',
		'body' => 'Body_html',
		'excerpt' => 'Excerpt_html',
		'override_form' => 'override_form',
		'status' => 'Status',
	) + $custom_map;
}

/**
 * Find an adjacent article relative to a provided threshold level
 *
 * @param scalar $threshold The value to compare against
 * @param string $s string Optional section restriction
 * @param string $type string Find lesser or greater neighbour? Possible values: '<' (previous, default) or '>' (next)
 * @param array $atts Attribute of article at threshold
 * @param string $threshold_type 'cooked': Use $threshold as SQL clause; 'raw': Use $threshold as an escapable scalar
 * @return array|string An array populated with article data, or the empty string in case of no matches
 */
function getNeighbour($threshold, $s, $type, $atts = array(), $threshold_type = 'raw')
{
	global $prefs;
	static $cache = array();

	$key = md5($threshold.$s.$type.join(n, $atts));
	if (isset($cache[$key])) {
		return $cache[$key];
	}

	extract($atts);
	$expired = ($expired && ($prefs['publish_expired_articles']));
	$customFields = getCustomFields();

	//Building query parts
	// lifted from publish.php. This is somewhat embarrassing, isn't it?
	$ids = array_map('intval', do_list($id));
	$id = (!$id) ? '' : " and ID IN (".join(',', $ids).")";
	switch ($time) {
		case 'any':
			$time = ""; break;
		case 'future':
			$time = " and Posted > now()"; break;
		default:
			$time = " and Posted <= now()";
	}
	if (!$expired) {
		$time .= " and (now() <= Expires or Expires = ".NULLDATETIME.")";
	}

	$custom = '';

	if ($customFields) {
		foreach($customFields as $cField) {
			if (isset($atts[$cField]))
				$customPairs[$cField] = $atts[$cField];
		}
		if(!empty($customPairs)) {
			$custom = buildCustomSql($customFields,$customPairs);
		}
	}

	if ($keywords) {
		$keys = doSlash(do_list($keywords));
		foreach ($keys as $key) {
			$keyparts[] = "FIND_IN_SET('".$key."',Keywords)";
		}
		$keywords = " and (" . join(' or ',$keyparts) . ")";
	}

	// invert $type for ascending sortdir
	$types = array(
		'>' => array('desc' => '>', 'asc' => '<'),
		'<' => array('desc' => '<', 'asc' => '>'),
	);
	$type = ($type == '>') ? $types['>'][$sortdir] : $types['<'][$sortdir];

	// escape threshold and treat it as a string unless explicitly told otherwise
	if ($threshold_type != 'cooked') {
		$threshold = "'".doSlash($threshold)."'";
	}

	$safe_name = safe_pfx('textpattern');
	$q = array(
		"select ID, Title, url_title, unix_timestamp(Posted) as uposted
			from ".$safe_name." where $sortby $type ".$threshold,
		($s!='' && $s!='default') ? "and Section = '".doSlash($s)."'" : filterFrontPage(),
		$id,
		$time,
		$custom,
		$keywords,
		'and Status=4',
		'order by '.$sortby,
		($type=='<') ? 'desc' : 'asc',
		'limit 1'
	);

	$cache[$key] = getRow(join(n.' ',$q));
	return (is_array($cache[$key])) ? $cache[$key] : '';
}

/**
 * Find next and previous articles relative to a provided threshold level
 *
 * @param int $id The "pivot" article's id; use zero (0) to indicate $thisarticle
 * @param scalar $threshold The value to compare against if $id != 0
 * @param string $s string Optional section restriction if $id != 0
 * @return array An array populated with article data from the next and previous article
 */
function getNextPrev($id = 0, $threshold = null, $s = '')
{
	if ($id !== 0) {
		// Pivot is specific article by ID: In lack of further information, revert to default sort order 'Posted desc'
		$atts = filterAtts(array('sortby' => 'Posted', 'sortdir' => 'desc'));
	} else {
		// Pivot is $thisarticle: Use article attributes to find its neighbours
		assert_article();
		global $thisarticle;
		if (!is_array($thisarticle)) {
			return array();
		}

		$atts = filterAtts();

		$m = preg_split('/\s+/', $atts['sort']);

		// If in doubt, fall back to chronologically descending order.
		if (empty($m[0])            // No explicit sort attribute
			|| count($m) > 2        // Complex clause, e.g. 'foo asc, bar desc'
			|| !preg_match('/^(?:[0-9a-zA-Z$_\x{0080}-\x{FFFF}]+|`[\x{0001}-\x{FFFF}]+`)$/u', $m[0])  // The clause's first verb is not a MySQL column identifier.
		)
		{
			$atts['sortby'] = 'Posted';
			$atts['sortdir']= 'desc';
		}
		else
		{
			// Sort is like 'foo asc'.
			$atts['sortby'] = $m[0];
			$atts['sortdir'] = (isset($m[1]) && strtolower($m[1]) == 'desc' ? 'desc' : 'asc');
		}

		// atts w/ special treatment
		switch($atts['sortby']) {
			case 'Posted':
				$threshold = 'from_unixtime('.doSlash($thisarticle['posted']).')';
				$threshold_type = 'cooked';
				break;
			case 'Expires':
				$threshold = 'from_unixtime('.doSlash($thisarticle['expires']).')';
				$threshold_type = 'cooked';
				break;
			case 'LastMod':
				$threshold = 'from_unixtime('.doSlash($thisarticle['modified']).')';
				$threshold_type = 'cooked';
				break;
			default:
				// retrieve current threshold value per sort column from $thisarticle
				$acm = array_flip(article_column_map());
				$key = $acm[$atts['sortby']];
				$threshold = $thisarticle[$key];
				$threshold_type = 'raw';
				break;
		}
		$s = $thisarticle['section'];
	}

	$thenext 			= getNeighbour($threshold, $s, '>', $atts, $threshold_type);
	$out['next_id']     = ($thenext) ? $thenext['ID'] : '';
	$out['next_title']  = ($thenext) ? $thenext['Title'] : '';
	$out['next_utitle'] = ($thenext) ? $thenext['url_title'] : '';
	$out['next_posted'] = ($thenext) ? $thenext['uposted'] : '';

	$theprev            = getNeighbour($threshold, $s, '<', $atts, $threshold_type);
	$out['prev_id']     = ($theprev) ? $theprev['ID'] : '';
	$out['prev_title']  = ($theprev) ? $theprev['Title'] : '';
	$out['prev_utitle'] = ($theprev) ? $theprev['url_title'] : '';
	$out['prev_posted'] = ($theprev) ? $theprev['uposted'] : '';
	return $out;
}

/**
 * Date of the site's last modification
 *
 * @return string
 */
function lastMod()
{
	$last = safe_field("unix_timestamp(val)", "txp_prefs", "`name`='lastmod' and prefs_id=1");
	return gmdate("D, d M Y H:i:s \G\M\T",$last);
}

/**
 * Parse a string and replace any Textpattern tags with their actual value
 *
 * @param string $thing The raw string
 * @return string The parsed string
 */
function parse($thing)
{
	$f = '@(</?txp:\w+(?:\s+\w+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/>]+))*\s*/?'.chr(62).')@s';
	$t = '@:(\w+)(.*?)/?.$@s';

	$parsed = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE);

	$level  = 0;
	$out    = '';
	$inside = '';
	$istag  = FALSE;

	foreach ($parsed as $chunk)
	{
		if ($istag)
		{
			if ($level === 0)
			{
				preg_match($t, $chunk, $tag);

				if (substr($chunk, -2, 1) === '/')
				{ # self closing
					$out .= processTags($tag[1], $tag[2]);
				}
				else
				{ # opening
					$level++;
				}
			}
			else
			{
				if (substr($chunk, 1, 1) === '/')
				{ # closing
					if (--$level === 0)
					{
						$out  .= processTags($tag[1], $tag[2], $inside);
						$inside = '';
					}
					else
					{
						$inside .= $chunk;
					}
				}
				elseif (substr($chunk, -2, 1) !== '/')
				{ # opening inside open
					++$level;
					$inside .= $chunk;
				}
				else
				{
					$inside .= $chunk;
				}
			}
		}
		else
		{
			if ($level)
			{
				$inside .= $chunk;
			}
			else
			{
				$out .= $chunk;
			}
		}

		$istag = !$istag;
	}

	return $out;
}

/**
 * Guesstimate whether a given function name may be a valid tag handler.
 *
 * @param string $tag function name
 * @return bool False if the function name is not a valid tag handler
 */
function maybe_tag($tag)
{
	static $tags = NULL;
	if ($tags == NULL) {
		$tags = get_defined_functions();
		$tags = array_flip($tags['user']);
	}
	return isset($tags[$tag]);
}

/**
 * Parse a tag for attributes and hand over to the tag handler function.
 *
 * @param string $tag The tag name without '<txp:'
 * @param string $atts The attribute string
 * @param string|null $thing The tag's content in case of container tags (optional)
 * @return string Parsed tag result
 */
function processTags($tag, $atts, $thing = NULL)
{
	global $production_status, $txptrace, $txptracelevel, $txp_current_tag;

	if ($production_status !== 'live')
	{
		$old_tag = $txp_current_tag;

		$txp_current_tag = '<txp:'.$tag.$atts.(isset($thing) ? '>' : '/>');

		trace_add($txp_current_tag);
		++$txptracelevel;

		if ($production_status === 'debug')
		{
			maxMemUsage($txp_current_tag);
		}
	}

	if ($tag === 'link' || $tag === 'yield')
	{
		$tag = 'tpt_'.$tag;
	}

	if (maybe_tag($tag))
	{
		$out = $tag(splat($atts), $thing);
	}

	// deprecated, remove in crockery
	elseif (isset($GLOBALS['pretext'][$tag]))
	{
		$out = txpspecialchars($pretext[$tag]);

		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);
	}

	else
	{
		$out = '';
		trigger_error(gTxt('unknown_tag'), E_USER_WARNING);
	}

	if ($production_status !== 'live')
	{
		--$txptracelevel;

		if (isset($thing))
		{
			trace_add('</txp:'.$tag.'>');
		}

		$txp_current_tag = $old_tag;
	}

	return $out;
}

/**
 * Protection from those who'd bomb the site by GET.
 *
 * Origin of the infamous 'Nice try' message and an even more useful '503' http status.
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
 * Check a named item's existence in DB table
 *
 * @param string $table	DB table name
 * @param string $val The name to look for
 * @param string $debug
 * @return bool|string
 */
function ckEx($table,$val,$debug='')
{
	return safe_field("name",'txp_'.$table,"`name` = '".doSlash($val)."' limit 1",$debug);
}

/**
 * Check category existence
 *
 * @param string $type Category type {'article', 'file', 'link', 'image'}
 * @param string $val The category name to look for
 * @param string $debug
 * @return bool|string
 */
function ckCat($type,$val,$debug='')
{
	return safe_field("name",'txp_category',"`name` = '".doSlash($val)."' AND type = '".doSlash($type)."' limit 1",$debug);
}

/**
 * Lookup article by ID
 *
 * @param int $val Article ID
 * @param string $debug
 * @return array|bool
 */
function ckExID($val,$debug='')
{
	return safe_row("ID,Section",'textpattern','ID = '.intval($val).' and Status >= 4 limit 1',$debug);
}

/**
 * Lookup article by URL title
 *
 * @param string $val	URL title
 * @param string $debug
 * @return array|bool
 */
function lookupByTitle($val,$debug='')
{
	return safe_row("ID,Section",'textpattern',"url_title = '".doSlash($val)."' and Status >= 4 limit 1",$debug);
}

/**
 * Lookup live article by URL title and section
 *
 * @param string $val		URL title
 * @param string $section	Section name
 * @param string $debug
 * @return array|bool
 */
function lookupByTitleSection($val,$section,$debug='')
{
	return safe_row("ID,Section",'textpattern',"url_title = '".doSlash($val)."' AND Section='".doSlash($section)."' and Status >= 4 limit 1",$debug);
}

/**
 * Lookup live article by ID and section
 *
 * @param int $id 			Article ID
 * @param string $section	Section name
 * @param string $debug
 * @return array|bool
 */
function lookupByIDSection($id, $section, $debug = '')
{
	return safe_row('ID, Section', 'textpattern',
		'ID = '.intval($id)." and Section = '".doSlash($section)."' and Status >= 4 limit 1", $debug);
}

/**
 * Lookup live article by ID
 *
 * @param int $id 			Article ID
 * @param string $debug
 * @return array|bool
 */
function lookupByID($id,$debug='')
{
	return safe_row("ID,Section",'textpattern','ID = '.intval($id).' and Status >= 4 limit 1',$debug);
}

/**
 * Lookup live article by date and URL title
 *
 * @param string $when	date wildcard
 * @param string $title	URL title
 * @param string $debug
 * @return array|bool
 */
function lookupByDateTitle($when,$title,$debug='')
{
	return safe_row("ID,Section","textpattern",
		"posted like '".doSlash($when)."%' and url_title like '".doSlash($title)."' and Status >= 4 limit 1");
}

/**
 * Chop a request string into URL-decoded path parts
 *
 * @param string $req Request string
 * @return array
 */
function chopUrl($req)
{
	$req = strtolower($req);
	//strip off query_string, if present
	$qs = strpos($req,'?');
	if ($qs) $req = substr($req, 0, $qs);
	$req = preg_replace('/index\.php$/', '', $req);
	$r = array_map('urldecode', explode('/',$req));
	$o['u0'] = (isset($r[0])) ? $r[0] : '';
	$o['u1'] = (isset($r[1])) ? $r[1] : '';
	$o['u2'] = (isset($r[2])) ? $r[2] : '';
	$o['u3'] = (isset($r[3])) ? $r[3] : '';
	$o['u4'] = (isset($r[4])) ? $r[4] : '';

	return $o;
}

/**
 * Save and retrieve the individual article's attributes plus article list attributes for next/prev tags
 *
 * @param array $atts
 * @return array
 * @since 4.5.0
 */
function filterAtts($atts = null)
{
	global $prefs;
	static $out = array();

	$valid = array(
		'sort'          => 'Posted desc',
		'sortby'		=> '',
		'sortdir'		=> '',
		'keywords'      => '',
		'expired'       => $prefs['publish_expired_articles'],
		'id'            => '',
		'time'          => 'past',
	);

	if (is_array($atts))
	{
		if (empty($out))
		{
			$out = $atts;
			trace_add('[filterAtts accepted]');
		}
		else
		{
			// TODO: deal w/ nested txp:article[_custom] tags.
			trace_add('[filterAtts ignored]');
		}
	}

	if (empty($out))
	{
		trace_add('[filterAtts not set]');
	}
	return lAtts($valid, $out, 0);
}
?>

<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

// -------------------------------------------------------------

	function page_title($atts)
	{
		global $parentid, $thisarticle, $id, $q, $c, $context, $s, $pg, $sitename;

		extract(lAtts(array(
			'separator' => ': ',
		), $atts));

		$out = txpspecialchars($sitename.$separator);
		$parent_id = (int) $parentid;

		if ($parent_id) {
			$out .= gTxt('comments_on').' '.escape_title(safe_field('Title', 'textpattern', "ID = $parent_id"));
		} elseif ($thisarticle['title']) {
			$out .= escape_title($thisarticle['title']);
		} elseif ($q) {
			$out .= gTxt('search_results').txpspecialchars($separator.$q);
		} elseif ($c) {
			$out .= txpspecialchars(fetch_category_title($c, $context));
		} elseif ($s and $s != 'default') {
			$out .= txpspecialchars(fetch_section_title($s));
		} elseif ($pg) {
			$out .= gTxt('page').' '.$pg;
		} else {
			$out = txpspecialchars($sitename);
		}

		return $out;
	}

// -------------------------------------------------------------

	function css($atts)
	{
		global $css, $doctype;

		extract(lAtts(array(
			'format' => 'url',
			'media'  => 'screen',
			'n'      => $css, // deprecated in 4.3.0
			'name'   => $css,
			'rel'    => 'stylesheet',
			'title'  => '',
		), $atts));

		if (isset($atts['n'])) {
			$name = $n;
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'n')), E_USER_NOTICE);
		}

		if (empty($name)) $name = 'default';
		$url = hu.'css.php?n='.txpspecialchars($name);

		if ($format == 'link') {
			return '<link rel="'.txpspecialchars($rel).
				($doctype != 'html5' ? '" type="text/css"': '"').
				($media ? ' media="'.txpspecialchars($media).'"' : '').
				($title ? ' title="'.txpspecialchars($title).'"' : '').
				' href="'.$url.'" />';
		}

		return $url;
	}

// -------------------------------------------------------------

	function image($atts)
	{
		global $thisimage;

		static $cache = array();

		extract(lAtts(array(
			'class'   => '',
			'escape'  => 'html',
			'html_id' => '',
			'id'      => '',
			'name'    => '',
			'width'   => '',
			'height'  => '',
			'style'   => '',
			'wraptag' => '',
		), $atts));

		if ($name)
		{
			if (isset($cache['n'][$name]))
			{
				$rs = $cache['n'][$name];
			}

			else
			{
				$name = doSlash($name);

				$rs = safe_row('*', 'txp_image', "name = '$name' limit 1");

				$cache['n'][$name] = $rs;
			}
		}

		elseif ($id)
		{
			if (isset($cache['i'][$id]))
			{
				$rs = $cache['i'][$id];
			}

			else
			{
				$id = (int) $id;

				$rs = safe_row('*', 'txp_image', "id = $id limit 1");

				$cache['i'][$id] = $rs;
			}
		}

		elseif ($thisimage)
		{
			$id = (int) $thisimage['id'];
			$rs = $thisimage;
			$cache['i'][$id] = $rs;
		}

		else
		{
			trigger_error(gTxt('unknown_image'));
			return;
		}

		if ($rs)
		{
			extract($rs);

			if ($escape == 'html')
			{
				$alt = txpspecialchars($alt);
				$caption = txpspecialchars($caption);
			}

			if ($width=='' && $w) $width = $w;
			if ($height=='' && $h) $height = $h;

			$out = '<img src="'.imagesrcurl($id, $ext).'" alt="'.$alt.'"'.
				($caption ? ' title="'.$caption.'"' : '').
				( ($html_id and !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '' ).
				( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
				($style ? ' style="'.txpspecialchars($style).'"' : '').
				($width ? ' width="'.(int)$width.'"' : '').
				($height ? ' height="'.(int)$height.'"' : '').
				' />';

			return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
		}

		trigger_error(gTxt('unknown_image'));
	}

// -------------------------------------------------------------

	function thumbnail($atts)
	{
		global $thisimage;

		extract(lAtts(array(
			'class'     => '',
			'escape'    => 'html',
			'html_id'   => '',
			'height'   	=> '',
			'id'        => '',
			'link'      => 0,
			'link_rel'  => '',
			'name'      => '',
			'poplink'   => 0, // is this used?
			'style'     => '',
			'wraptag'   => '',
			'width'   	=> ''
		), $atts));

		if ($name)
		{
			$name = doSlash($name);

			$rs = safe_row('*', 'txp_image', "name = '$name' limit 1");
		}

		elseif ($id)
		{
			$id = (int) $id;

			$rs = safe_row('*', 'txp_image', "id = $id limit 1");
		}

		elseif ($thisimage)
		{
			$id = (int) $thisimage['id'];
			$rs = $thisimage;
		}

		else
		{
			trigger_error(gTxt('unknown_image'));
			return;
		}

		if ($rs)
		{
			extract($rs);

			if ($thumbnail)
			{
				if ($escape == 'html')
				{
					$alt = txpspecialchars($alt);
					$caption = txpspecialchars($caption);
				}

				if ($width=='' && $thumb_w) $width = $thumb_w;
				if ($height=='' && $thumb_h) $height = $thumb_h;

				$out = '<img src="'.imagesrcurl($id, $ext, true).'" alt="'.$alt.'"'.
					($caption ? ' title="'.$caption.'"' : '').
					( ($html_id and !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '' ).
					( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
					($style ? ' style="'.txpspecialchars($style).'"' : '').
					($width ? ' width="'.(int)$width.'"' : '').
					($height ? ' height="'.(int)$height.'"' : '').
					' />';

				if ($link)
				{
					$out = href($out, imagesrcurl($id, $ext), (!empty($link_rel) ? " rel='".txpspecialchars($link_rel)."'" : '')." title='$caption'");
				}

				elseif ($poplink)
				{
					$out = '<a href="'.imagesrcurl($id, $ext).'"'.
						' onclick="window.open(this.href, \'popupwindow\', '.
						'\'width='.$w.', height='.$h.', scrollbars, resizable\'); return false;">'.$out.'</a>';
				}

				return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
			}

		}

		trigger_error(gTxt('unknown_image'));
	}

// -------------------------------------------------------------
	function imageFetchInfo($where)
	{
		$rs = safe_row('*', 'txp_image', $where);

		if ($rs)
		{
			return image_format_info($rs);
		}

		return false;
	}

//--------------------------------------------------------------------------
	function image_format_info($image)
	{
		if (($unix_ts = @strtotime($image['date'])) > 0)
			$image['date'] = $unix_ts;

		return $image;
	}

// -------------------------------------------------------------

	function output_form($atts, $thing = NULL)
	{
		global $yield;

		extract(lAtts(array(
			'form' => '',
		), $atts));

		if (!$form)
		{
			trigger_error(gTxt('form_not_specified'));
		}
		else
		{
			$yield[] = $thing !== NULL ? parse($thing) : NULL;
			$out = parse_form($form);
			array_pop($yield);
			return $out;
		}
	}

// -------------------------------------------------------------

	function tpt_yield()
	{
		global $yield;

		$inner = end($yield);

		return isset($inner) ? $inner : '';
	}

// -------------------------------------------------------------

	function feed_link($atts, $thing = NULL)
	{
		global $s, $c;

		extract(lAtts(array(
			'category' => $c,
			'flavor'   => 'rss',
			'format'   => 'a',
			'label'    => '',
			'limit'    => '',
			'section'  => ( $s == 'default' ? '' : $s),
			'title'    => gTxt('rss_feed_title'),
			'wraptag'  => '',
			'class'    => '',
		), $atts));

		$url = pagelinkurl(array(
			$flavor    => '1',
			'section'  => $section,
			'category' => $category,
			'limit'    => $limit
		));

		if ($flavor == 'atom')
		{
			$title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
		}

		$title = txpspecialchars($title);

		if ($format == 'link')
		{
			$type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

			return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
		}

		$txt = ($thing === NULL ? $label : parse($thing));
		$out = '<a href="'.$url.'" title="'.$title.'">'.$txt.'</a>';

		return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
	}

// -------------------------------------------------------------

	function link_feed_link($atts)
	{
		global $c;

		extract(lAtts(array(
			'category' => $c,
			'flavor'   => 'rss',
			'format'   => 'a',
			'label'    => '',
			'title'    => gTxt('rss_feed_title'),
			'wraptag'  => '',
			'class'    => __FUNCTION__,
		), $atts));

		$url = pagelinkurl(array(
			$flavor => '1',
			'area'  =>'link',
			'category' => $category
		));

		if ($flavor == 'atom')
		{
			$title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
		}

		$title = txpspecialchars($title);

		if ($format == 'link')
		{
			$type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

			return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
		}

		$out = '<a href="'.$url.'" title="'.$title.'">'.$label.'</a>';

		return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
	}

// -------------------------------------------------------------

	function linklist($atts, $thing = NULL)
	{
		global $s, $c, $context, $thislink, $thispage, $pretext;

		extract(lAtts(array(
			'break'       => '',
			'category'    => '',
			'author'      => '',
			'realname'    => '',
			'auto_detect' => 'category, author',
			'class'       => __FUNCTION__,
			'form'        => 'plainlinks',
			'id'          => '',
			'label'       => '',
			'labeltag'    => '',
			'pageby'      => '',
			'limit'       => 0,
			'offset'      => 0,
			'sort'        => 'linksort asc',
			'wraptag'     => '',
		), $atts));

		$where = array();
		$filters = isset($atts['category']) || isset($atts['author']) || isset($atts['realname']);
		$context_list = (empty($auto_detect) || $filters) ? array() : do_list($auto_detect);
		$pageby = ($pageby=='limit') ? $limit : $pageby;

		if ($category) $where[] = "category IN ('".join("','", doSlash(do_list($category)))."')";
		if ($id) $where[] = "id IN ('".join("','", doSlash(do_list($id)))."')";
		if ($author) $where[] = "author IN ('".join("','", doSlash(do_list($author)))."')";
		if ($realname) {
			$authorlist = safe_column('name', 'txp_users', "RealName IN ('". join("','", doArray(doSlash(do_list($realname)), 'urldecode')) ."')" );
			$where[] = "author IN ('".join("','", doSlash($authorlist))."')";
		}

		// If no links are selected, try...
		if (!$where && !$filters)
		{
			foreach ($context_list as $ctxt)
			{
				switch ($ctxt)
				{
					case 'category':
						// ... the global category in the URL
						if ($context == 'link' && !empty($c))
						{
							$where[] = "category = '".doSlash($c)."'";
						}
						break;
					case 'author':
						// ... the global author in the URL
						if ($context == 'link' && !empty($pretext['author']))
						{
							$where[] = "author = '".doSlash($pretext['author'])."'";
						}
						break;
				}
				// Only one context can be processed
				if ($where) break;
			}
		}

		if (!$where && $filters)
		{
			return ''; // If nothing matches, output nothing
		}

		if (!$where)
		{
			$where[] = "1=1"; // If nothing matches, start with all links
		}

		$where = join(' AND ', $where);

		// Set up paging if required
		if ($limit && $pageby) {
			$grand_total = safe_count('txp_link', $where);
			$total = $grand_total - $offset;
			$numPages = ($pageby > 0) ? ceil($total/$pageby) : 1;
			$pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
			$pgoffset = $offset + (($pg - 1) * $pageby);
			// send paging info to txp:newer and txp:older
			$pageout['pg']          = $pg;
			$pageout['numPages']    = $numPages;
			$pageout['s']           = $s;
			$pageout['c']           = $c;
			$pageout['context']     = 'link';
			$pageout['grand_total'] = $grand_total;
			$pageout['total']       = $total;

			if (empty($thispage))
				$thispage = $pageout;
		} else {
			$pgoffset = $offset;
		}

		$qparts = array(
			$where,
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($pgoffset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_link', join(' ', $qparts));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				$thislink = $a;
				$thislink['date'] = $thislink['uDate'];
				unset($thislink['uDate']);

				$out[] = ($thing) ? parse($thing) : parse_form($form);

				$thislink = '';
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return false;
	}

// -------------------------------------------------------------
// NOTE: tpt_ prefix used because link() is a PHP function. See publish.php
	function tpt_link($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'rel' => '',
		), $atts));

		return tag(
			txpspecialchars($thislink['linkname']), 'a',
			($rel ? ' rel="'.txpspecialchars($rel).'"' : '').
			' href="'.doSpecial($thislink['url']).'"'
		);
	}

// -------------------------------------------------------------

	function linkdesctitle($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'rel' => '',
		), $atts));

		$description = ($thislink['description']) ?
			' title="'.txpspecialchars($thislink['description']).'"' :
			'';

		return tag(
			txpspecialchars($thislink['linkname']), 'a',
			($rel ? ' rel="'.txpspecialchars($rel).'"' : '').
			' href="'.doSpecial($thislink['url']).'"'.$description
		);
	}

// -------------------------------------------------------------

	function link_name($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'escape' => 'html',
		), $atts));

		return ($escape == 'html') ?
			txpspecialchars($thislink['linkname']) :
			$thislink['linkname'];
	}

// -------------------------------------------------------------

	function link_url()
	{
		global $thislink;
		assert_link();

		return doSpecial($thislink['url']);
	}

//--------------------------------------------------------------------------

	function link_author($atts)
	{
		global $thislink, $s;
		assert_link();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'title'        => 1,
			'section'      => '',
			'this_section' => '',
			'wraptag'      => '',
		), $atts));

		if ($thislink['author'])
		{
			$author_name = get_author_name($thislink['author']);
			$display_name = txpspecialchars( ($title) ? $author_name : $thislink['author'] );

			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

			$author = ($link) ?
				href($display_name, pagelinkurl(array('s' => $section, 'author' => $author_name, 'context' => 'link'))) :
				$display_name;

			return ($wraptag) ? doTag($author, $wraptag, $class) : $author;
		}
	}

// -------------------------------------------------------------

	function link_description($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'class'    => '',
			'escape'   => 'html',
			'label'    => '',
			'labeltag' => '',
			'wraptag'  => '',
		), $atts));

		if ($thislink['description'])
		{
			$description = ($escape == 'html') ?
				txpspecialchars($thislink['description']) :
				$thislink['description'];

			return doLabel($label, $labeltag).doTag($description, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function link_date($atts)
	{
		global $thislink, $dateformat;
		assert_link();

		extract(lAtts(array(
			'format' => $dateformat,
			'gmt'    => '',
			'lang'   => '',
		), $atts));

		return safe_strftime($format, $thislink['date'], $gmt, $lang);
	}

// -------------------------------------------------------------

	function link_category($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'class'    => '',
			'label'    => '',
			'labeltag' => '',
			'title'    => 0,
			'wraptag'  => '',
		), $atts));

		if ($thislink['category'])
		{
			$category = ($title) ?
				fetch_category_title($thislink['category'], 'link') :
				$thislink['category'];

			return doLabel($label, $labeltag).doTag($category, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function link_id()
	{
		global $thislink;
		assert_link();
		return $thislink['id'];
	}

// -------------------------------------------------------------
	function link_format_info($link)
	{
		if (($unix_ts = @strtotime($link['date'])) > 0)
			$link['date'] = $unix_ts;

		return $link;
	}

// -------------------------------------------------------------
	function eE($txt) // convert email address into unicode entities
	{
		for ($i=0;$i<strlen($txt);$i++) {
			$ent[] = "&#".ord(substr($txt,$i,1)).";";
		}
		if (!empty($ent)) return join('',$ent);
	}

// -------------------------------------------------------------
	function email($atts, $thing = NULL)
	{
		extract(lAtts(array(
			'email'    => '',
			'linktext' => gTxt('contact'),
			'title'    => '',
		),$atts));

		if ($email) {
			if ($thing !== NULL) $linktext = parse($thing);
			// obfuscate link text?
			if (is_valid_email($linktext)) $linktext = eE($linktext);

			return '<a href="'.eE('mailto:'.txpspecialchars($email)).'"'.
				($title ? ' title="'.txpspecialchars($title).'"' : '').">$linktext</a>";
		}
		return '';
	}

// -------------------------------------------------------------
	function password_protect($atts)
	{
		ob_start();

		extract(lAtts(array(
			'login' => '',
			'pass'  => '',
		),$atts));

		$au = serverSet('PHP_AUTH_USER');
		$ap = serverSet('PHP_AUTH_PW');
		//For php as (f)cgi, two rules in htaccess often allow this workaround
		$ru = serverSet('REDIRECT_REMOTE_USER');
		if ($ru && !$au && !$ap && substr( $ru,0,5) == 'Basic' ) {
			list ( $au, $ap ) = explode( ':', base64_decode( substr( $ru,6)));
		}
		if ($login && $pass) {
			if (!$au || !$ap || $au!= $login || $ap!= $pass) {
				header('WWW-Authenticate: Basic realm="Private"');
				txp_die(gTxt('auth_required'), '401');
			}
		}
	}

// -------------------------------------------------------------

	function recent_articles($atts)
	{
		global $prefs;
		extract(lAtts(array(
			'break'    => br,
			'category' => '',
			'class'    => __FUNCTION__,
			'label'    => gTxt('recent_articles'),
			'labeltag' => '',
			'limit'    => 10,
			'section'  => '',
			'sort'     => 'Posted desc',
			'sortby'   => '', // deprecated
			'sortdir'  => '', // deprecated
			'wraptag'  => '',
			'no_widow' => @$prefs['title_no_widow'],
		), $atts));

		// for backwards compatibility
		// sortby and sortdir are deprecated
		if ($sortby)
		{
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortby')), E_USER_NOTICE);

			if (!$sortdir)
			{
				$sortdir = 'desc';
			}
			else
			{
				trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortdir')), E_USER_NOTICE);
			}

			$sort = "$sortby $sortdir";
		}

		elseif ($sortdir)
		{
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortdir')), E_USER_NOTICE);
			$sort = "Posted $sortdir";
		}

		$category   = join("','", doSlash(do_list($category)));
		$categories = ($category) ? "and (Category1 IN ('".$category."') or Category2 IN ('".$category."'))" : '';
		$section = ($section) ? " and Section IN ('".join("','", doSlash(do_list($section)))."')" : '';
		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= Expires or Expires = '.NULLDATETIME.') ';

		$rs = safe_rows_start('*, id as thisid, unix_timestamp(Posted) as posted', 'textpattern',
			"Status = 4 $section $categories and Posted <= now()$expired order by ".doSlash($sort).' limit 0,'.intval($limit));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				$a['Title'] = ($no_widow) ? noWidow(escape_title($a['Title'])) : escape_title($a['Title']);
				$out[] = href($a['Title'], permlinkurl($a));
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function recent_comments($atts, $thing = NULL)
	{

		global $prefs;
		global $thisarticle, $thiscomment;
		extract(lAtts(array(
			'break'    => br,
			'class'    => __FUNCTION__,
			'form'     => '',
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'offset'   => 0,
			'sort'     => 'posted desc',
			'wraptag'  => '',
		), $atts));

		$sort = preg_replace('/\bposted\b/', 'd.posted', $sort);
		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= t.Expires or t.Expires = '.NULLDATETIME.') ';

		$rs = startRows('select d.name, d.email, d.web, d.message, d.discussid, unix_timestamp(d.Posted) as time, '.
				't.ID as thisid, unix_timestamp(t.Posted) as posted, t.Title as title, t.Section as section, t.url_title '.
				'from '. safe_pfx('txp_discuss') .' as d inner join '. safe_pfx('textpattern') .' as t on d.parentid = t.ID '.
				'where t.Status >= 4'.$expired.' and d.visible = '.VISIBLE.' order by '.doSlash($sort).' limit '.intval($offset).','.intval($limit));
		if ($rs)
		{
			$out = array();
			$old_article = $thisarticle;
			while ($c = nextRow($rs))
			{
				if (empty($form) && empty($thing))
				{
					$out[] = href(
						txpspecialchars($c['name']).' ('.escape_title($c['title']).')',
						permlinkurl($c).'#c'.$c['discussid']
					);
				}
				else
				{
					$thiscomment['name'] = $c['name'];
					$thiscomment['email'] = $c['email'];
					$thiscomment['web'] = $c['web'];
					$thiscomment['message'] = $c['message'];
					$thiscomment['discussid'] = $c['discussid'];
					$thiscomment['time'] = $c['time'];

					// allow permlink guesstimation in permlinkurl(), elsewhere
					$thisarticle['thisid'] = $c['thisid'];
					$thisarticle['posted'] = $c['posted'];
					$thisarticle['title'] = $c['title'];
					$thisarticle['section'] = $c['section'];
					$thisarticle['url_title'] = $c['url_title'];

					$out[] = ($thing) ? parse($thing) : parse_form($form);
				}
			}

			if ($out)
			{
				unset($GLOBALS['thiscomment']);
				$thisarticle = $old_article;
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function related_articles($atts, $thing = NULL)
	{
		global $thisarticle, $prefs;

		assert_article();

		extract(lAtts(array(
			'break'    => br,
			'class'    => __FUNCTION__,
			'form'	   => '',
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'match'    => 'Category1,Category2',
			'no_widow' => @$prefs['title_no_widow'],
			'section'  => '',
			'sort'     => 'Posted desc',
			'wraptag'  => '',
		), $atts));

		if (empty($thisarticle['category1']) and empty($thisarticle['category2']))
		{
			return;
		}

		$match = do_list($match);

		if (!in_array('Category1', $match) and !in_array('Category2', $match))
		{
			return;
		}

		$id = $thisarticle['thisid'];

		$cats = array();

		if ($thisarticle['category1'])
		{
			$cats[] = doSlash($thisarticle['category1']);
		}

		if ($thisarticle['category2'])
		{
			$cats[] = doSlash($thisarticle['category2']);
		}

		$cats = join("','", $cats);

		$categories = array();

		if (in_array('Category1', $match))
		{
			$categories[] = "Category1 in('$cats')";
		}

		if (in_array('Category2', $match))
		{
			$categories[] = "Category2 in('$cats')";
		}

		$categories = 'and ('.join(' or ', $categories).')';

		$section = ($section) ? " and Section IN ('".join("','", doSlash(do_list($section)))."')" : '';

		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= Expires or Expires = '.NULLDATETIME.') ';
		$rs = safe_rows_start('*, unix_timestamp(Posted) as posted, unix_timestamp(LastMod) as uLastMod, unix_timestamp(Expires) as uExpires', 'textpattern',
			'ID != '.intval($id)." and Status = 4 $expired  and Posted <= now() $categories $section order by ".doSlash($sort).' limit 0,'.intval($limit));

		if ($rs)
		{
			$out = array();
			$old_article = $thisarticle;

			while ($a = nextRow($rs))
			{
				$a['Title'] = ($no_widow) ? noWidow(escape_title($a['Title'])) : escape_title($a['Title']);
				$a['uPosted'] = $a['posted']; // populateArticleData() and permlinkurl() assume quite a bunch of posting dates...

				if (empty($form) && empty($thing))
				{
					$out[] = href($a['Title'], permlinkurl($a));
				}
				else
				{
					populateArticleData($a);
					$out[] = ($thing) ?  parse($thing) : parse_form($form);
				}
			}
			$thisarticle = $old_article;

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function popup($atts)
	{
		global $s, $c, $permlink_mode;

		extract(lAtts(array(
			'label'        => gTxt('browse'),
			'wraptag'      => '',
			'class'        => '',
			'section'      => '',
			'this_section' => 0,
			'type'         => 'c',
		), $atts));

		if ($type == 's')
		{
			$rs = safe_rows_start('name, title', 'txp_section', "name != 'default' order by name");
		}

		else
		{
			$rs = safe_rows_start('name, title', 'txp_category', "type = 'article' and name != 'root' order by name");
		}

		if ($rs)
		{
			$out = array();

			$current = ($type == 's') ? $s : $c;

			$sel = '';
			$selected = false;

			while ($a = nextRow($rs))
			{
				extract($a);

				if ($name == $current)
				{
					$sel = ' selected="selected"';
					$selected = true;
				}

				$out[] = '<option value="'.$name.'"'.$sel.'>'.txpspecialchars($title).'</option>';

				$sel = '';
			}

			if ($out)
			{
				$section = ($this_section) ? ( $s == 'default' ? '' : $s) : $section;

				$out = n.'<select name="'.txpspecialchars($type).'" onchange="submit(this.form);">'.
					n.t.'<option value=""'.($selected ? '' : ' selected="selected"').'>&#160;</option>'.
					n.t.join(n.t, $out).
					n.'</select>';

				if ($label)
				{
					$out = $label.br.$out;
				}

				if ($wraptag)
				{
					$out = doTag($out, $wraptag, $class);
				}

				if (($type == 's' || $permlink_mode == 'messy')) {
					$action = hu;
					$his = ($section !== '') ? n.hInput('s', $section) : '';
				} else {
					// Clean urls for category popup
					$action = pagelinkurl(array('s' => $section));
					$his = '';
				}

				return '<form method="get" action="'.$action.'">'.
					'<div>'.
					$his.
					n.$out.
					n.'<noscript><div><input type="submit" value="'.gTxt('go').'" /></div></noscript>'.
					n.'</div>'.
					n.'</form>';
			}
		}
	}

// -------------------------------------------------------------
// output href list of site categories

	function category_list($atts, $thing = NULL)
	{
		global $s, $c, $thiscategory;

		extract(lAtts(array(
			'active_class' => '',
			'break'        => br,
			'categories'   => '',
			'class'        => __FUNCTION__,
			'exclude'      => '',
			'form'         => '',
			'label'        => '',
			'labeltag'     => '',
			'parent'       => '',
			'section'      => '',
			'children'     => '1',
			'sort'         => '',
			'this_section' => 0,
			'type'         => 'article',
			'wraptag'      => '',
		), $atts));

		$sort = doSlash($sort);

		if ($categories)
		{
			$categories = do_list($categories);
			$categories = join("','", doSlash($categories));

			$rs = safe_rows_start('name, title', 'txp_category',
				"type = '".doSlash($type)."' and name in ('$categories') order by ".($sort ? $sort : "field(name, '$categories')"));
		}

		else
		{
			if ($children)
			{
				$shallow = '';
			} else {
				// descend only one level from either 'parent' or 'root', plus parent category
				$shallow = ($parent) ? "and (parent = '".doSlash($parent)."' or name = '".doSlash($parent)."')" : "and parent = 'root'" ;
			}

			if ($exclude)
			{
				$exclude = do_list($exclude);

				$exclude = join("','", doSlash($exclude));

				$exclude = "and name not in('$exclude')";
			}

			if ($parent)
			{
				$qs = safe_row('lft, rgt', 'txp_category', "type = '".doSlash($type)."' and name = '".doSlash($parent)."'");

				if ($qs)
				{
					extract($qs);

					$rs = safe_rows_start('name, title', 'txp_category',
						"(lft between $lft and $rgt) and type = '".doSlash($type)."' and name != 'default' $exclude $shallow order by ".($sort ? $sort : 'lft ASC'));
				} else {
					$rs = array();
				}
			}

			else
			{
				$rs = safe_rows_start('name, title', 'txp_category',
					"type = '".doSlash($type)."' and name not in('default','root') $exclude $shallow order by ".($sort ? $sort : 'name ASC'));
			}
		}

		if ($rs)
		{
			$out = array();
			$count = 0;
			$last = numRows($rs);

			if (isset($thiscategory)) $old_category = $thiscategory;
			while ($a = nextRow($rs))
			{
				++$count;
				extract($a);

				if ($name)
				{
					$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

					if (empty($form) && empty($thing))
					{
						$out[] = tag(txpspecialchars($title), 'a',
							( ($active_class and (0 == strcasecmp($c, $name))) ? ' class="'.txpspecialchars($active_class).'"' : '' ).
							' href="'.pagelinkurl(array('s' => $section, 'c' => $name, 'context' => $type)).'"'
						);
					}
					else
					{
						$thiscategory = array('name' => $name, 'title' => $title, 'type' => $type);
						$thiscategory['is_first'] = ($count == 1);
						$thiscategory['is_last'] = ($count == $last);
						if (isset($atts['section'])) $thiscategory['section'] = $section;
						$out[] = ($thing) ? parse($thing) : parse_form($form);
					}
				}
			}
			$thiscategory = (isset($old_category) ? $old_category : NULL);

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------
// output href list of site sections

	function section_list($atts, $thing = NULL)
	{
		global $sitename, $s, $thissection;

		extract(lAtts(array(
			'active_class'    => '',
			'break'           => br,
			'class'           => __FUNCTION__,
			'default_title'   => $sitename,
			'exclude'         => '',
			'form'            => '',
			'include_default' => '',
			'label'           => '',
			'labeltag'        => '',
			'sections'        => '',
			'sort'            => '',
			'wraptag'         => '',
		), $atts));

		$sort = doSlash($sort);

		$rs = array();
		if ($sections)
		{
			$sections = do_list($sections);

			$sections = join("','", doSlash($sections));

			$rs = safe_rows('name, title', 'txp_section', "name in ('$sections') order by ".($sort ? $sort : "field(name, '$sections')"));
		}

		else
		{
			if ($exclude)
			{
				$exclude = do_list($exclude);

				$exclude = join("','", doSlash($exclude));

				$exclude = "and name not in('$exclude')";
			}

			$rs = safe_rows('name, title', 'txp_section', "name != 'default' $exclude order by ".($sort ? $sort : 'name ASC'));
		}

		if ($include_default)
		{
			array_unshift($rs, array('name' => 'default', 'title' => $default_title));
		}

		if ($rs)
		{
			$out = array();
			$count = 0;
			$last = count($rs);

			if (isset($thissection)) $old_section = $thissection;
			foreach ($rs as $a)
			{
				++$count;
				extract($a);

				if (empty($form) && empty($thing))
				{
					$url = pagelinkurl(array('s' => $name));

					$out[] = tag(txpspecialchars($title), 'a',
						( ($active_class and (0 == strcasecmp($s, $name))) ? ' class="'.txpspecialchars($active_class).'"' : '' ).
						' href="'.$url.'"'
					);
				}
				else
				{
					$thissection = array('name' => $name, 'title' => ($name == 'default') ? $default_title : $title);
					$thissection['is_first'] = ($count == 1);
					$thissection['is_last'] = ($count == $last);
					$out[] = ($thing) ? parse($thing) : parse_form($form);
				}
			}
			$thissection = (isset($old_section) ? $old_section : NULL);

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------
	function search_input($atts) // input form for search queries
	{
		global $q, $permlink_mode, $doctype;
		extract(lAtts(array(
			'form'    => 'search_input',
			'wraptag' => 'p',
			'class'   => __FUNCTION__,
			'size'    => '15',
			'html_id' => '',
			'label'   => gTxt('search'),
			'button'  => '',
			'section' => '',
			'match'   => 'exact',
		),$atts));

		if ($form) {
			$rs = fetch('form','txp_form','name',$form);
			if ($rs) {
				return parse($rs);
			}
		}

		$h5 = ($doctype == 'html5');
		$sub = (!empty($button)) ? '<input type="submit" value="'.txpspecialchars($button).'" />' : '';
		$id =  (!empty($html_id)) ? ' id="'.txpspecialchars($html_id).'"' : '';
		$out = fInput( $h5 ? 'search' : 'text','q',$q,'','','',$size,'','',false, $h5);
		$out = (!empty($label)) ? txpspecialchars($label).br.$out.$sub : $out.$sub;
		$out = ($match === 'exact') ? $out : fInput('hidden','m',txpspecialchars($match)) . $out;
		$out = ($wraptag) ? doTag($out,$wraptag, $class) : $out;

		if (!$section) {
			return '<form method="get" action="'.hu.'"'.$id.'>'.
				n.$out.
				n.'</form>';
		}

		if ($permlink_mode != 'messy') {
			return '<form method="get" action="'.pagelinkurl(array('s' => $section)).'"'.$id.'>'.
				n.$out.
				n.'</form>';
		}

		return '<form method="get" action="'.hu.'"'.$id.'>'.
			n.hInput('s', $section).
			n.$out.
			n.'</form>';
	}

// -------------------------------------------------------------
	function search_term($atts)
	{
		global $q;
		if(empty($q)) return '';

		extract(lAtts(array(
			'escape' => 'html'
		),$atts));

		if (isset($atts['escape'])) {
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'escape')), E_USER_NOTICE);
		}
		// TODO: Remove deprecated attribute 'escape'
        return ($escape == 'html' ? txpspecialchars($q) : $q);
	}

// -------------------------------------------------------------
// link to next article, if it exists

	function link_to_next($atts, $thing = NULL)
	{
		global /** @noinspection PhpUnusedLocalVariableInspection */
		$thisarticle, $next_id, $next_title, $prev_id, $prev_title;

		assert_article();

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		if (is_array($thisarticle))
		{
			if (!isset($thisarticle['next_id']))
			{
				$np = getNextPrev();
				$thisarticle = $thisarticle + $np;
				extract($np);
			}

			if ($next_id)
			{
				$url = permlinkurl_id($next_id);

				if ($thing)
				{
					$thing = parse($thing);
					$next_title = escape_title($next_title);

					return '<a rel="next" href="'.$url.'"'.
						($next_title != $thing ? ' title="'.$next_title.'"' : '').
						'>'.$thing.'</a>';
				}

				return $url;
			}
		}
		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------
// link to previous article, if it exists

	function link_to_prev($atts, $thing = NULL)
	{
		global /** @noinspection PhpUnusedLocalVariableInspection */
		$thisarticle, $next_id, $next_title, $prev_id, $prev_title;

		assert_article();

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		if (is_array($thisarticle))
		{
			if (!isset($thisarticle['prev_id']))
			{
				$np = getNextPrev();
				$thisarticle = $thisarticle + $np;
				extract($np);
			}

			if ($prev_id)
			{
				$url = permlinkurl_id($prev_id);

				if ($thing)
				{
					$thing = parse($thing);
					$prev_title = escape_title($prev_title);

					return '<a rel="prev" href="'.$url.'"'.
						($prev_title != $thing ? ' title="'.$prev_title.'"' : '').
						'>'.$thing.'</a>';
				}

				return $url;
			}
		}
		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------

	function next_title()
	{
		global /** @noinspection PhpUnusedLocalVariableInspection */
		$thisarticle, $next_id, $next_title, $prev_id, $prev_title;

		assert_article();
		if (!is_array($thisarticle))
		{
			return '';
		}

		if (!isset($thisarticle['next_title']))
		{
			$np = getNextPrev();
			$thisarticle = $thisarticle + $np;
			extract($np);
		}
		return escape_title($next_title);
	}

// -------------------------------------------------------------

	function prev_title()
	{
		global /** @noinspection PhpUnusedLocalVariableInspection */
		$thisarticle, $next_id, $next_title, $prev_id, $prev_title;

		assert_article();
		if (!is_array($thisarticle))
		{
			return '';
		}

		if (!isset($thisarticle['prev_title']))
		{
			$np = getNextPrev();
			$thisarticle = $thisarticle + $np;
			extract($np);
		}
		return escape_title($prev_title);
	}

// -------------------------------------------------------------

	function site_name()
	{
		return txpspecialchars($GLOBALS['sitename']);
	}

// -------------------------------------------------------------

	function site_slogan()
	{
		return txpspecialchars($GLOBALS['site_slogan']);
	}

// -------------------------------------------------------------

	function link_to_home($atts, $thing = NULL)
	{
		extract(lAtts(array(
			'class' => false,
		), $atts));

		if ($thing)
		{
			$class = ($class) ? ' class="'.txpspecialchars($class).'"' : '';
			return '<a rel="home" href="'.hu.'"'.$class.'>'.parse($thing).'</a>';
		}

		return hu;
	}

// -------------------------------------------------------------

	function newer($atts, $thing = NULL)
	{
		global $thispage, $pretext, $m;

		extract(lAtts(array(
			'showalways' => 0,
			'title'      => '',
			'escape'     => 'html'
		), $atts));

		$numPages = $thispage['numPages'];
		$pg = $thispage['pg'];

		if ($numPages > 1 and $pg > 1 and $pg <= $numPages)
		{
			$nextpg = ($pg - 1 == 1) ? 0 : ($pg - 1);

			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$url = pagelinkurl(array(
				'month'   => @$pretext['month'],
				'pg'      => $nextpg,
				's'       => @$pretext['s'],
				'c'       => @$pretext['c'],
				'context' => @$pretext['context'],
				'q'       => @$pretext['q'],
				'm'       => @$m,
				'author'  => $author
			));

			if ($thing)
			{
				if ($escape == 'html')
				{
					$title = escape_title($title);
				}

				return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------

	function older($atts, $thing = NULL)
	{
		global $thispage, $pretext, $m;

		extract(lAtts(array(
			'showalways' => 0,
			'title'      => '',
			'escape'     => 'html'
		), $atts));

		$numPages = $thispage['numPages'];
		$pg = $thispage['pg'];

		if ($numPages > 1 and $pg > 0 and $pg < $numPages)
		{
			$nextpg = $pg + 1;

			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$url = pagelinkurl(array(
				'month'   => @$pretext['month'],
				'pg'      => $nextpg,
				's'       => @$pretext['s'],
				'c'       => @$pretext['c'],
				'context' => @$pretext['context'],
				'q'       => @$pretext['q'],
				'm'       => @$m,
				'author'  => $author
			));

			if ($thing)
			{
				if ($escape == 'html')
				{
					$title = escape_title($title);
				}

				return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------
	function text($atts)
	{
		extract(lAtts(array(
			'item' => '',
		),$atts));
		return ($item) ? gTxt($item) : '';
	}

// -------------------------------------------------------------

	function article_id()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['thisid'];
	}

// -------------------------------------------------------------

	function article_url_title()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['url_title'];
	}

// -------------------------------------------------------------

	function if_article_id($atts, $thing)
	{
		global $thisarticle, $pretext;

		assert_article();

		extract(lAtts(array(
			'id' => $pretext['id'],
		), $atts));

		if ($id)
		{
			return parse(EvalElse($thing, in_list($thisarticle['thisid'], $id)));
		}
	}

// -------------------------------------------------------------

	function posted($atts)
	{
		global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

		assert_article();

		extract(lAtts(array(
			'class'   => '',
			'format'  => '',
			'gmt'     => '',
			'lang'    => '',
			'wraptag' => ''
		), $atts));

		if ($format)
		{
			$out = safe_strftime($format, $thisarticle['posted'], $gmt, $lang);
		}

		else
		{
			if ($id or $c or $pg)
			{
				$out = safe_strftime($archive_dateformat, $thisarticle['posted'], $gmt, $lang);
			}

			else
			{
				$out = safe_strftime($dateformat, $thisarticle['posted'], $gmt, $lang);
			}
		}

		return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
	}

// -------------------------------------------------------------

	function expires($atts)
	{
		global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

		assert_article();

		if($thisarticle['expires'] == 0)
		{
			return;
		}

		extract(lAtts(array(
			'class'   => '',
			'format'  => '',
			'gmt'     => '',
			'lang'    => '',
			'wraptag' => '',
		), $atts));

		if ($format)
		{
			$out = safe_strftime($format, $thisarticle['expires'], $gmt, $lang);
		}

		else
		{
			if ($id or $c or $pg)
			{
				$out = safe_strftime($archive_dateformat, $thisarticle['expires'], $gmt, $lang);
			}

			else
			{
				$out = safe_strftime($dateformat, $thisarticle['expires'], $gmt, $lang);
			}
		}

		return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
	}

// -------------------------------------------------------------

	function if_expires($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, $thisarticle['expires']));
	}

// -------------------------------------------------------------

	function if_expired($atts, $thing)
	{
		global $thisarticle, $publish_expired_articles, $production_status;
		assert_article();

		if (!$publish_expired_articles && $production_status != 'live')
			trigger_error(gTxt('publish_expired_articles_prefs_off'), E_USER_NOTICE);

		return parse(EvalElse($thing,
			$thisarticle['expires'] && ($thisarticle['expires'] <= time() )));
	}

// -------------------------------------------------------------

	function modified($atts)
	{
		global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

		assert_article();

		extract(lAtts(array(
			'class'   => '',
			'format'  => '',
			'gmt'     => '',
			'lang'    => '',
			'wraptag' => ''
		), $atts));

		if ($format)
		{
			$out = safe_strftime($format, $thisarticle['modified'], $gmt, $lang);
		}

		else
		{
			if ($id or $c or $pg)
			{
				$out = safe_strftime($archive_dateformat, $thisarticle['modified'], $gmt, $lang);
			}

			else
			{
				$out = safe_strftime($dateformat, $thisarticle['modified'], $gmt, $lang);
			}
		}

		return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
	}

// -------------------------------------------------------------

	function comments_count()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['comments_count'];
	}

// -------------------------------------------------------------
	function comments_invite($atts)
	{
		global $thisarticle,$is_article_list;

		assert_article();

		extract($thisarticle);
		global $comments_mode;

		if (!$comments_invite)
			$comments_invite = @$GLOBALS['prefs']['comments_default_invite'];

		extract(lAtts(array(
			'class'      => __FUNCTION__,
			'showcount'  => true,
			'textonly'   => false,
			'showalways' => false,  //FIXME in crockery. This is only for BC.
			'wraptag'    => '',
		), $atts));

		$invite_return = '';
		if (($annotate or $comments_count) && ($showalways or $is_article_list) ) {

			$comments_invite = txpspecialchars($comments_invite);
			$ccount = ($comments_count && $showcount) ?  ' ['.$comments_count.']' : '';
			if ($textonly)
				$invite_return = $comments_invite.$ccount;
			else
			{
				if (!$comments_mode) {
					$invite_return = doTag($comments_invite, 'a', $class, ' href="'.permlinkurl($thisarticle).'#'.gTxt('comment').'" '). $ccount;
				} else {
					$invite_return = "<a href=\"".hu."?parentid=$thisid\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\"".(($class) ? ' class="'.txpspecialchars($class).'"' : '').'>'.$comments_invite.'</a> '.$ccount;
				}
			}
			if ($wraptag) $invite_return = doTag($invite_return, $wraptag, $class);
		}

		return $invite_return;
	}
// -------------------------------------------------------------

	function comments_form($atts)
	{
		global $thisarticle, $has_comments_preview;

		extract(lAtts(array(
			'class'         => __FUNCTION__,
			'form'          => 'comment_form',
			'isize'         => '25',
			'msgcols'       => '25',
			'msgrows'       => '5',
			'msgstyle'      => '',
			'show_preview'  => empty($has_comments_preview),
			'wraptag'       => '',
			'previewlabel'  => gTxt('preview'),
			'submitlabel'   => gTxt('submit'),
			'rememberlabel' => gTxt('remember'),
			'forgetlabel'   => gTxt('forget')
		), $atts));

		assert_article();

		extract($thisarticle);

		$out = '';
		$ip = serverset('REMOTE_ADDR');
		$blacklisted = is_blacklisted($ip);

		if (!checkCommentsAllowed($thisid)) {
			$out = graf(gTxt("comments_closed"), ' id="comments_closed"');
		} elseif (!checkBan($ip)) {
			$out = graf(gTxt('you_have_been_banned'), ' id="comments_banned"');
		} elseif ($blacklisted) {
			$out = graf(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), ' id="comments_blacklisted"');
		} elseif (gps('commented')!=='') {
			$out = gTxt("comment_posted");
			if (gps('commented')==='0')
				$out .= " ". gTxt("comment_moderated");
			$out = graf($out, ' id="txpCommentInputForm"');
		} else {
			# display a comment preview if required
			if (ps('preview') and $show_preview)
				$out = comments_preview(array());
			$out .= commentForm($thisid,$atts);
		}

		return (!$wraptag ? $out : doTag($out,$wraptag,$class) );
	}

// -------------------------------------------------------------

	function comments_error($atts)
	{
		extract(lAtts(array(
			'break'   => 'br',
			'class'   => __FUNCTION__,
			'wraptag' => 'div',
		), $atts));

		$evaluator =& get_comment_evaluator();

		$errors = $evaluator->get_result_message();

		if ($errors)
		{
			return doWrap($errors, $wraptag, $break, $class);
		}
	}

// -------------------------------------------------------------
	function if_comments_error($atts, $thing)
	{
		$evaluator =& get_comment_evaluator();
		return parse(EvalElse($thing,(count($evaluator -> get_result_message()) > 0)));
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	# this functionality will be merged into comments_invite
	# no point in having two tags for one functionality
	function comments_annotateinvite($atts, $thing)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		global $thisarticle, $pretext;

		extract(lAtts(array(
			'class'   => __FUNCTION__,
			'wraptag' => 'h3',
		),$atts));

		assert_article();

		extract($thisarticle);

		extract(
			safe_row(
				"Annotate,AnnotateInvite,unix_timestamp(Posted) as uPosted",
					"textpattern", 'ID = '.intval($thisid)
			)
		);

		if (!$thing)
			$thing = $AnnotateInvite;

		return (!$Annotate) ? '' : doTag($thing,$wraptag,$class,' id="'.gTxt('comment').'"');
	}

// -------------------------------------------------------------
	function comments($atts)
	{
		global $thisarticle, $prefs;
		extract($prefs);

		extract(lAtts(array(
			'form'       => 'comments',
			'wraptag'    => ($comments_are_ol ? 'ol' : ''),
			'break'      => ($comments_are_ol ? 'li' : 'div'),
			'class'      => __FUNCTION__,
			'breakclass' => '',
			'limit'      => 0,
			'offset'     => 0,
			'sort'       => 'posted ASC',
		),$atts));

		assert_article();

		extract($thisarticle);

		if (!$comments_count) return '';

		$qparts = array(
			'parentid='.intval($thisid).' and visible='.VISIBLE,
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*, unix_timestamp(posted) as time', 'txp_discuss', join(' ', $qparts));

		$out = '';

		if ($rs) {
			$comments = array();

			while($vars = nextRow($rs)) {
				$GLOBALS['thiscomment'] = $vars;
				$comments[] = parse_form($form).n;
				unset($GLOBALS['thiscomment']);
			}

			$out .= doWrap($comments,$wraptag,$break,$class,$breakclass);
		}

		return $out;
	}

// -------------------------------------------------------------
	function comments_preview($atts)
	{
		global $has_comments_preview;

		if (!ps('preview'))
			return;

		extract(lAtts(array(
			'form'    => 'comments',
			'wraptag' => '',
			'class'   => __FUNCTION__,
		),$atts));

		assert_article();

		$preview = psa(array('name','email','web','message','parentid','remember'));
		$preview['time'] = time();
		$preview['discussid'] = 0;
		$preview['name'] = strip_tags($preview['name']);
		$preview['email'] = clean_url($preview['email']);
		if ($preview['message'] == '')
		{
			$in = getComment();
			$preview['message'] = $in['message'];

		}
		$preview['message'] = markup_comment(substr(trim($preview['message']), 0, 65535)); // it is called 'message', not 'novel'
		$preview['web'] = clean_url($preview['web']);

		$GLOBALS['thiscomment'] = $preview;
		$comments = parse_form($form).n;
		unset($GLOBALS['thiscomment']);
		$out = doTag($comments,$wraptag,$class);

		# set a flag, to tell the comments_form tag that it doesn't have to show a preview
		$has_comments_preview = true;

		return $out;
	}

// -------------------------------------------------------------
	function if_comments_preview($atts, $thing)
	{
		return parse(EvalElse($thing, ps('preview') && checkCommentsAllowed(gps('parentid')) ));
	}

// -------------------------------------------------------------
	function comment_permlink($atts, $thing)
	{
		global $thisarticle, $thiscomment;

		assert_article();
		assert_comment();

		extract($thiscomment);
		extract(lAtts(array(
			'anchor' => empty($thiscomment['has_anchor_tag']),
		),$atts));

		$dlink = permlinkurl($thisarticle).'#c'.$discussid;

		$thing = parse($thing);

		$name = ($anchor ? ' id="c'.$discussid.'"' : '');

		return tag($thing,'a',' href="'.$dlink.'"'.$name);
	}

// -------------------------------------------------------------
	function comment_id()
	{
		global $thiscomment;

		assert_comment();

		return $thiscomment['discussid'];
	}

// -------------------------------------------------------------

	function comment_name($atts)
	{
		global $thiscomment, $prefs;

		assert_comment();

		extract($prefs);
		extract($thiscomment);

		extract(lAtts(array(
			'link' => 1,
		), $atts));

		$name = txpspecialchars($name);

		if ($link)
		{
			$web = comment_web();
			$nofollow = (@$comment_nofollow ? ' rel="nofollow"' : '');

			if (!empty($web))
			{
				return '<a href="'.$web.'"'.$nofollow.'>'.$name.'</a>';
			}

			if ($email && !$never_display_email)
			{
				return '<a href="'.eE('mailto:'.$email).'"'.$nofollow.'>'.$name.'</a>';
			}
		}

		return $name;
	}

// -------------------------------------------------------------
	function comment_email()
	{
		global $thiscomment;

		assert_comment();

		return txpspecialchars($thiscomment['email']);
	}

// -------------------------------------------------------------
	function comment_web()
	{
		global $thiscomment;

		assert_comment();

		if (preg_match('/^\S/', $thiscomment['web']))
		{
			// Prepend default protocol 'http' for all non-local URLs
			if (!preg_match('!^https?://|^#|^/[^/]!', $thiscomment['web']))
			{
				$thiscomment['web'] = 'http://'.$thiscomment['web'];
			}
			return txpspecialchars($thiscomment['web']);
		}
		return '';
	}

// -------------------------------------------------------------

	function comment_time($atts)
	{
		global $thiscomment, $comments_dateformat;

		assert_comment();

		extract(lAtts(array(
			'format' => $comments_dateformat,
			'gmt'    => '',
			'lang'   => '',
		), $atts));

		return safe_strftime($format, $thiscomment['time'], $gmt, $lang);
	}

// -------------------------------------------------------------
	function comment_message()
	{
		global $thiscomment;

		assert_comment();

		return $thiscomment['message'];
	}

// -------------------------------------------------------------
	function comment_anchor()
	{
		global $thiscomment;

		assert_comment();

		$thiscomment['has_anchor_tag'] = 1;
		return '<a id="c'.$thiscomment['discussid'].'"></a>';
	}

// -------------------------------------------------------------

	function author($atts)
	{
		global $thisarticle, $s, $author;

		extract(lAtts(array(
			'link'         => '',
			'title'        => 1,
			'section'      => '',
			'this_section' => 0,
		), $atts));

		if (!empty($author))
		{
			$theAuthor = $author;
		}

		else
		{
			assert_article();
			$theAuthor = $thisarticle['authorid'];
		}

		$author_name = get_author_name($theAuthor);
		$display_name = txpspecialchars( ($title) ? $author_name : $theAuthor );

		$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

		return ($link) ?
			href($display_name, pagelinkurl(array('s' => $section, 'author' => $author_name)), ' rel="author"') :
			$display_name;
	}

// -------------------------------------------------------------
	function author_email($atts)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'escape' => 'html',
			'link' 	=> ''
		), $atts));

		$author_email = get_author_email($thisarticle['authorid']);
		$display_email = ($escape == 'html' ? txpspecialchars($author_email) : $author_email);
		return ($link) ? email(array('email' => $author_email, 'linktext' => $display_email)) : $display_email;
	}

// -------------------------------------------------------------

	function if_author($atts, $thing)
	{
		global $author, $context;

		extract(lAtts(array(
			'type' => 'article',
			'name' => '',
		),$atts));

		$theType = ($type) ? $type == $context : true;

		if ($name)
		{
			return parse(EvalElse($thing, ($theType && in_list($author, $name))));
		}

		return parse(EvalElse($thing, ($theType && !empty($author))));
	}

// -------------------------------------------------------------

	function if_article_author($atts, $thing)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'name' => '',
		), $atts));

		$author = $thisarticle['authorid'];

		if ($name)
		{
			return parse(EvalElse($thing, in_list($author, $name)));
		}

		return parse(EvalElse($thing, !empty($author)));
	}

// -------------------------------------------------------------

	function body()
	{
		global $thisarticle, $is_article_body;
		assert_article();

		$was_article_body = $is_article_body;
		$is_article_body = 1;
		$out = parse($thisarticle['body']);
		$is_article_body = $was_article_body;
		return $out;
	}

// -------------------------------------------------------------
	function title($atts)
	{
		global $thisarticle, $prefs;
		assert_article();
		extract(lAtts(array(
			'no_widow' => @$prefs['title_no_widow'],
		), $atts));

		$t = escape_title($thisarticle['title']);
		if ($no_widow)
			$t = noWidow($t);
		return $t;
	}

// -------------------------------------------------------------
	function excerpt()
	{
		global $thisarticle, $is_article_body;
		assert_article();

		$was_article_body = $is_article_body;
		$is_article_body = 1;
		$out = parse($thisarticle['excerpt']);
		$is_article_body = $was_article_body;
		return $out;
	}

// -------------------------------------------------------------

	function category1($atts, $thing = NULL)
	{
		global $thisarticle, $s, $permlink_mode;

		assert_article();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'title'        => 0,
			'section'      => '',
			'this_section' => 0,
			'wraptag'      => '',
		), $atts));

		if ($thisarticle['category1'])
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $thisarticle['category1'];

			$label = txpspecialchars(($title) ? fetch_category_title($category) : $category);

			if ($thing)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'"'.
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'">'.$label.'</a>';
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function category2($atts, $thing = NULL)
	{
		global $thisarticle, $s, $permlink_mode;

		assert_article();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'title'        => 0,
			'section'      => '',
			'this_section' => 0,
			'wraptag'      => '',
		), $atts));

		if ($thisarticle['category2'])
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $thisarticle['category2'];

			$label = txpspecialchars(($title) ? fetch_category_title($category) : $category);

			if ($thing)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'"'.
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'">'.$label.'</a>';
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function category($atts, $thing = NULL)
	{
		global $s, $c, $thiscategory, $context;

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'name'         => '',
			'section'      => $s,
			'this_section' => 0,
			'title'        => 0,
			'type'         => 'article',
			'url'          => 0,
			'wraptag'      => '',
		), $atts));

		if ($name)
		{
			$category = $name;
		}
		elseif (!empty($thiscategory['name']))
		{
			$category = $thiscategory['name'];
			$type = $thiscategory['type'];
		}
		else
		{
			$category = $c;
			if (!isset($atts['type']))
			{
				$type = $context;
			}
		}

		if ($category)
		{
			if ($this_section)
			{
				$section = ($s == 'default' ? '' : $s);
			}
			elseif (isset($thiscategory['section']))
			{
				$section = $thiscategory['section'];
			}

			$label = txpspecialchars( ($title) ? fetch_category_title($category, $type) : $category );

			$href = pagelinkurl(array('s' => $section, 'c' => $category, 'context' => $type));

			if ($thing)
			{
				$out = '<a href="'.$href.'"'.
					( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = href($label, $href, ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '');
			}

			elseif ($url)
			{
				$out = $href;
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function section($atts, $thing = NULL)
	{
		global $thisarticle, $s, $thissection;

		extract(lAtts(array(
			'class'   => '',
			'link'    => 0,
			'name'    => '',
			'title'   => 0,
			'url'     => 0,
			'wraptag' => '',
		), $atts));

		if ($name)
		{
			$sec = $name;
		}

		elseif (!empty($thissection['name']))
		{
			$sec = $thissection['name'];
		}

		elseif (!empty($thisarticle['section']))
		{
			$sec = $thisarticle['section'];
		}

		else
		{
			$sec = $s;
		}

		if ($sec)
		{
			$label = txpspecialchars( ($title) ? fetch_section_title($sec) : $sec );

			$href = pagelinkurl(array('s' => $sec));

			if ($thing)
			{
				$out = '<a href="'.$href.'"'.
					( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = href($label, $href, ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '');
			}

			elseif ($url)
			{
				$out = $href;
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------
	function keywords()
	{
		global $thisarticle;
		assert_article();

		return txpspecialchars($thisarticle['keywords']);
	}

// -------------------------------------------------------------
	function if_keywords($atts, $thing = NULL)
	{
		global $thisarticle;
		assert_article();
		extract(lAtts(array(
			'keywords' => ''
		), $atts));

		$condition = empty($keywords) ?
			$thisarticle['keywords'] :
			array_intersect(do_list($keywords), do_list($thisarticle['keywords']));

		return parse(EvalElse($thing, !empty($condition)));
	}

// -------------------------------------------------------------

	function if_article_image($atts, $thing='')
	{
	    global $thisarticle;
	    assert_article();

	    return parse(EvalElse($thing, $thisarticle['article_image']));
	}

// -------------------------------------------------------------

	function article_image($atts)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'class'     => '',
			'escape'    => 'html',
			'html_id'   => '',
			'style'     => '',
			'width'     => '',
			'height'    => '',
			'thumbnail' => 0,
			'wraptag'   => '',
		), $atts));

		if ($thisarticle['article_image'])
		{
			$image = $thisarticle['article_image'];
		}

		else
		{
			return;
		}

		if (intval($image))
		{
			$rs = safe_row('*', 'txp_image', 'id = '.intval($image));

			if ($rs)
			{
				$width = ($width=='') ? (($thumbnail) ? $rs['thumb_w'] : $rs['w']) : $width;
				$height = ($height=='') ? (($thumbnail) ? $rs['thumb_h'] : $rs['h']) : $height;

				if ($thumbnail)
				{
					if ($rs['thumbnail'])
					{
						extract($rs);

						if ($escape == 'html')
						{
							$alt = txpspecialchars($alt);
							$caption = txpspecialchars($caption);
						}

						$out = '<img src="'.imagesrcurl($id, $ext, true).'" alt="'.$alt.'"'.
							($caption ? ' title="'.$caption.'"' : '').
							( ($html_id and !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '' ).
							( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
							($style ? ' style="'.txpspecialchars($style).'"' : '').
							($width ? ' width="'.(int)$width.'"' : '').
							($height ? ' height="'.(int)$height.'"' : '').
							' />';
					}

					else
					{
						return '';
					}
				}

				else
				{
					extract($rs);

					if ($escape == 'html')
					{
						$alt = txpspecialchars($alt);
						$caption = txpspecialchars($caption);
					}

					$out = '<img src="'.imagesrcurl($id, $ext).'" alt="'.$alt.'"'.
						($caption ? ' title="'.$caption.'"' : '').
						( ($html_id and !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '' ).
						( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
						($style ? ' style="'.txpspecialchars($style).'"' : '').
						($width ? ' width="'.(int)$width.'"' : '').
						($height ? ' height="'.(int)$height.'"' : '').
						' />';
				}
			}

			else
			{
				trigger_error(gTxt('unknown_image'));
				return;
			}
		}

		else
		{
			$out = '<img src="'.txpspecialchars($image).'" alt=""'.
				( ($html_id and !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '' ).
				( ($class and !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '' ).
				($style ? ' style="'.txpspecialchars($style).'"' : '').
				($width ? ' width="'.(int)$width.'"' : '').
				($height ? ' height="'.(int)$height.'"' : '').
				' />';
		}

		return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
	}

// -------------------------------------------------------------
	function search_result_title($atts)
	{
		return permlink($atts, '<txp:title />');
	}

// -------------------------------------------------------------
	function search_result_excerpt($atts)
	{
		global $thisarticle, $pretext;

		assert_article();

		extract(lAtts(array(
			'break'   => ' &#8230;',
			'hilight' => 'strong',
			'limit'   => 5,
		), $atts));

		$m = $pretext['m'];
		$q = $pretext['q'];

		$quoted = ($q[0] === '"') && ($q[strlen($q)-1] === '"');
		$q = $quoted ? trim(trim($q, '"')) : $q;

		$result = preg_replace('/\s+/', ' ', strip_tags(str_replace('><', '> <', $thisarticle['body'])));

		if ($quoted || empty($m) || $m === 'exact')
		{
			$regex_search = '/(?:\G|\s).{0,50}'.preg_quote($q, '/').'.{0,50}(?:\s|$)/iu';
			$regex_hilite = '/('.preg_quote($q, '/').')/i';
		}
		else
		{
			$regex_search = '/(?:\G|\s).{0,50}('.preg_replace('/\s+/', '|', preg_quote($q, '/')).').{0,50}(?:\s|$)/iu';
			$regex_hilite = '/('.preg_replace('/\s+/', '|', preg_quote($q, '/')).')/i';
		}

		preg_match_all($regex_search, $result, $concat);
		$concat = $concat[0];

		for ($i = 0, $r = array(); $i < min($limit, count($concat)); $i++)
		{
			$r[] = trim($concat[$i]);
		}

		$concat = join($break.n, $r);
		$concat = preg_replace('/^[^>]+>/U', '', $concat);
#TODO

		$concat = preg_replace($regex_hilite, "<$hilight>$1</$hilight>", $concat);

		return ($concat) ? trim($break.$concat.$break) : '';
	}

// -------------------------------------------------------------
	function search_result_url($atts)
	{
		global $thisarticle;
		assert_article();

		$l = permlinkurl($thisarticle);
		return permlink($atts, $l);
	}

// -------------------------------------------------------------
	function search_result_date($atts)
	{
		assert_article();
		return posted($atts);
	}

// -------------------------------------------------------------
	function search_result_count($atts)
	{
		global $thispage;
		$t = @$thispage['grand_total'];
		extract(lAtts(array(
			'text'     => ($t == 1 ? gTxt('article_found') : gTxt('articles_found')),
		),$atts));

		return $t . ($text ? ' ' . $text : '');
	}

// -------------------------------------------------------------
	function image_index($atts)
	{
		global $s,$c,$p,$path_to_site;
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'class'    => __FUNCTION__,
			'labeltag' => '',
			'c'        => $c, // Keep the option to override categories due to backward compatibility
			'category' => $c,
			'limit'    => 0,
			'offset'   => 0,
			'sort'     => 'name ASC',
		),$atts));

		if (isset($atts['c'])) {
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'c')), E_USER_NOTICE);
		}

		if (isset($atts['category'])) {
			$c = $category; // Override the global
		}

		$qparts = array(
			"category = '".doSlash($c)."' and thumbnail = 1",
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*', 'txp_image',  join(' ', $qparts));

		if ($rs) {
			$out = array();
			while ($a = nextRow($rs)) {
				extract($a);
				$dims = ($thumb_h ? " height=\"$thumb_h\"" : '') . ($thumb_w ? " width=\"$thumb_w\"" : '');
				$url = pagelinkurl(array('c'=>$c, 'context'=>'image', 's'=>$s, 'p'=>$id));
				$out[] = '<a href="'.$url.'">'.
					'<img src="'.imagesrcurl($id, $ext, true).'"'.$dims.' alt="'.txpspecialchars($alt).'" />'.'</a>';
			}
			if (count($out)) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function image_display($atts)
	{
		if (is_array($atts)) extract($atts);
		global $s,$c,$p;
		if($p) {
			$rs = safe_row("*", "txp_image", 'id='.intval($p).' limit 1');
			if ($rs) {
				extract($rs);
				return '<img src="'.imagesrcurl($id, $ext).
					'" style="height:'.$h.'px;width:'.$w.'px" alt="'.txpspecialchars($alt).'" />';
			}
		}
	}

// -------------------------------------------------------------
	function images($atts, $thing = NULL)
	{
		global $s, $c, $context, $p, $path_to_site, $thisimage, $thisarticle, $thispage, $pretext;

		extract(lAtts(array(
			'name'        => '',
			'id'          => '',
			'category'    => '',
			'author'      => '',
			'realname'    => '',
			'extension'   => '',
			'thumbnail'   => '',
			'auto_detect' => 'article, category, author',
			'label'       => '',
			'break'       => br,
			'wraptag'     => '',
			'class'       => __FUNCTION__,
			'html_id'     => '',
			'labeltag'    => '',
			'form'        => '',
			'pageby'      => '',
			'limit'       => 0,
			'offset'      => 0,
			'sort'        => 'name ASC',
		),$atts));

		$safe_sort = doSlash($sort);
		$where = array();
		$has_content = $thing || $form;
		$filters = isset($atts['id']) || isset($atts['name']) || isset($atts['category']) || isset($atts['author']) || isset($atts['realname']) || isset($atts['extension']) || $thumbnail === '1' || $thumbnail === '0';
		$context_list = (empty($auto_detect) || $filters) ? array() : do_list($auto_detect);
		$pageby = ($pageby=='limit') ? $limit : $pageby;

		if ($name) $where[] = "name IN ('".join("','", doSlash(do_list($name)))."')";

		if ($category) $where[] = "category IN ('".join("','", doSlash(do_list($category)))."')";

		if ($id) $where[] = "id IN ('".join("','", doSlash(do_list($id)))."')";

		if ($author) $where[] = "author IN ('".join("','", doSlash(do_list($author)))."')";

		if ($realname) {
			$authorlist = safe_column('name', 'txp_users', "RealName IN ('". join("','", doArray(doSlash(do_list($realname)), 'urldecode')) ."')" );
			$where[] = "author IN ('".join("','", doSlash($authorlist))."')";
		}

		if ($extension) $where[] = "ext IN ('".join("','", doSlash(do_list($extension)))."')";
		if ($thumbnail === '0' || $thumbnail === '1') $where[] = "thumbnail = $thumbnail";

		// If no images are selected, try...
		if (!$where && !$filters)
		{
			foreach ($context_list as $ctxt)
			{
				switch($ctxt)
				{
					case 'article':
						// ...the article image field
						if ($thisarticle && !empty($thisarticle['article_image']))
						{
							$items = do_list($thisarticle['article_image']);
							$i = 0; // TODO: Indexed array access required for PHP 4 compat. Replace with &$item in TXP5? @see [r3435].
							foreach ($items as $item)
							{
								if (is_numeric($item))
								{
									$items[$i] = intval($item);
								}
								else
								{
									return article_image(compact('class', 'html_id', 'wraptag'));
								}
								$i++;
							}
							$items = join(",", $items);
							// NB: This clause will squash duplicate ids
							$where[] = "id IN ($items)";
							// order of ids in article image field overrides default 'sort' attribute
							if (empty($atts['sort']))
							{
								$safe_sort = "field(id, $items)";
							}
						}
						break;
					case 'category':
						// ... the global category in the URL
						if ($context == 'image' && !empty($c))
						{
							$where[] = "category = '".doSlash($c)."'";
						}
						break;
					case 'author':
						// ... the global author in the URL
						if ($context == 'image' && !empty($pretext['author']))
						{
							$where[] = "author = '".doSlash($pretext['author'])."'";
						}
						break;
				}
				// Only one context can be processed
				if ($where) break;
			}
		}

		// order of ids in 'id' attribute overrides default 'sort' attribute
		if (empty($atts['sort']) && $id !== '')
		{
			$safe_sort = 'field(id, '.join(',', doSlash(do_list($id))).')';
		}


		if (!$where && $filters)
		{
			return ''; // If nothing matches, output nothing
		}

		if (!$where)
		{
			$where[] = "1=1"; // If nothing matches, start with all images
		}

		$where = join(' AND ', $where);

		// Set up paging if required
		if ($limit && $pageby) {
			$grand_total = safe_count('txp_image', $where);
			$total = $grand_total - $offset;
			$numPages = ($pageby > 0) ? ceil($total/$pageby) : 1;
			$pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
			$pgoffset = $offset + (($pg - 1) * $pageby);
			// send paging info to txp:newer and txp:older
			$pageout['pg']          = $pg;
			$pageout['numPages']    = $numPages;
			$pageout['s']           = $s;
			$pageout['c']           = $c;
			$pageout['context']     = 'image';
			$pageout['grand_total'] = $grand_total;
			$pageout['total']       = $total;

			if (empty($thispage))
				$thispage = $pageout;
		} else {
			$pgoffset = $offset;
		}

		$qparts = array(
			$where,
			'order by '.$safe_sort,
			($limit) ? 'limit '.intval($pgoffset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*', 'txp_image',  join(' ', $qparts));

		if ($rs)
		{
			$out = array();

			if (isset($thisimage)) $old_image = $thisimage;

			while ($a = nextRow($rs))
			{
				$thisimage = image_format_info($a);
				if (!$has_content)
				{
					$url = pagelinkurl(array('c'=>$thisimage['category'], 'context'=>'image', 's'=>$s, 'p'=>$thisimage['id']));
					$src = image_url(array('thumbnail' => '1'));
					$thing = '<a href="'.$url.'">'.
						'<img src="'. $src .'" alt="'.txpspecialchars($thisimage['alt']).'" />'.'</a>'.n;
				}
				$out[] = ($thing) ? parse($thing) : parse_form($form);
			}

			$thisimage = (isset($old_image) ? $old_image : NULL);

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class, '', '', '', $html_id);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function image_info($atts) {
		global $thisimage;

		extract(lAtts(array(
			'name'       => '',
			'id'         => '',
			'type'       => 'caption',
			'escape'     => 'html',
			'wraptag'    => '',
			'class'      => '',
			'break'      => '',
			'breakclass' => '',
		), $atts));

		$validItems = array('id','name','category','category_title','alt','caption','ext','author','w','h','thumb_w','thumb_h','date');
		$type = do_list($type);

		$from_form = false;

		if ($id)
		{
			$thisimage = imageFetchInfo('id = '.intval($id));
		}

		elseif ($name)
		{
			$thisimage = imageFetchInfo("name = '".doSlash($name)."'");
		}

		else
		{
			assert_image();
			$from_form = true;
		}

		$out = array();
		if ($thisimage)
		{
			$thisimage['category_title'] = fetch_category_title($thisimage['category'], 'image');

			foreach ($type as $item)
			{
				if (in_array($item, $validItems))
				{
					if (isset($thisimage[$item]))
					{
						$out[] = ($escape == 'html') ?
							txpspecialchars($thisimage[$item]) : $thisimage[$item];
					}
				}
			}

			if (!$from_form)
			{
				$thisimage = '';
			}
		}
		return doWrap($out, $wraptag, $break, $class, $breakclass);
	}

// -------------------------------------------------------------
	function image_url($atts, $thing = NULL)
	{
		global $thisimage;

		extract(lAtts(array(
			'name'      => '',
			'id'        => '',
			'thumbnail' => 0,
			'link'      => 'auto',
		), $atts));

		$from_form = false;

		if ($id)
		{
			$thisimage = imageFetchInfo('id = '.intval($id));
		}

		elseif ($name)
		{
			$thisimage = imageFetchInfo("name = '".doSlash($name)."'");
		}

		else
		{
			assert_image();
			$from_form = true;
		}

		if ($thisimage)
		{
			$url = imagesrcurl($thisimage['id'], $thisimage['ext'], $thumbnail);
			$link = ($link == 'auto') ? (($thing) ? 1 : 0) : $link;
			$out = ($thing) ? parse($thing) : $url;
			$out = ($link) ? href($out, $url) : $out;

			if (!$from_form)
			{
				$thisimage = '';
			}

			return $out;
		}
		return '';
	}

//--------------------------------------------------------------------------

	function image_author($atts)
	{
		global $thisimage, $s;
		assert_image();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'title'        => 1,
			'section'      => '',
			'this_section' => '',
			'wraptag'      => '',
		), $atts));

		if ($thisimage['author'])
		{
			$author_name = get_author_name($thisimage['author']);
			$display_name = txpspecialchars( ($title) ? $author_name : $thisimage['author'] );

			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

			$author = ($link) ?
				href($display_name, pagelinkurl(array('s' => $section, 'author' => $author_name, 'context' => 'image'))) :
				$display_name;

			return ($wraptag) ? doTag($author, $wraptag, $class) : $author;
		}
	}

//--------------------------------------------------------------------------
	function image_date($atts)
	{
		global $thisimage;

		extract(lAtts(array(
			'name'   => '',
			'id'     => '',
			'format' => '',
		), $atts));

		$from_form = false;

		if ($id)
		{
			$thisimage = imageFetchInfo('id = '.intval($id));
		}

		elseif ($name)
		{
			$thisimage = imageFetchInfo("name = '".doSlash($name)."'");
		}

		else
		{
			assert_image();
			$from_form = true;
		}

		if (isset($thisimage['date'])) {
			// Not a typo: use fileDownloadFormatTime() since it is fit for purpose
			$out = fileDownloadFormatTime(array(
				'ftime'  => $thisimage['date'],
				'format' => $format
			));

			if (!$from_form)
			{
				$thisimage = '';
			}

			return $out;
		}
	}

//--------------------------------------------------------------------------
	function if_thumbnail($atts, $thing)
	{
		global $thisimage;
		assert_image();

		return parse(EvalElse($thing, ($thisimage['thumbnail'] == 1)));
	}

// -------------------------------------------------------------
	function if_comments($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, ($thisarticle['comments_count'] > 0)));
	}

// -------------------------------------------------------------
	function if_comments_allowed($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, checkCommentsAllowed($thisarticle['thisid'])));
	}

// -------------------------------------------------------------
	function if_comments_disallowed($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, !checkCommentsAllowed($thisarticle['thisid'])));
	}

// -------------------------------------------------------------
	function if_individual_article($atts, $thing)
	{
		global $is_article_list;
		return parse(EvalElse($thing, ($is_article_list == false)));
	}

// -------------------------------------------------------------
	function if_article_list($atts, $thing)
	{
		global $is_article_list;
		return parse(EvalElse($thing, ($is_article_list == true)));
	}

// -------------------------------------------------------------
	function meta_keywords()
	{
		global $id_keywords;
		return ($id_keywords)
		?	'<meta name="keywords" content="'.txpspecialchars($id_keywords).'" />'
		:	'';
	}

// -------------------------------------------------------------
	function meta_author($atts)
	{
		global $id_author;

		extract(lAtts(array(
			'title'  => 0,
		), $atts));

		if ($id_author)
		{
			$display_name = ($title) ? get_author_name($id_author) : $id_author;
			return '<meta name="author" content="'.txpspecialchars($display_name).'" />';
		}
		return '';
	}

// -------------------------------------------------------------

	function doWrap($list, $wraptag, $break, $class = '', $breakclass = '', $atts = '', $breakatts = '', $id = '')
	{
		if (!$list)
		{
			return '';
		}

		if ($id)
		{
			$atts .= ' id="'.txpspecialchars($id).'"';
		}

		if ($class)
		{
			$atts .= ' class="'.txpspecialchars($class).'"';
		}

		if ($breakclass)
		{
			$breakatts.= ' class="'.txpspecialchars($breakclass).'"';
		}

		// non-enclosing breaks
		if (!preg_match('/^\w+$/', $break) or $break == 'br' or $break == 'hr')
		{
			if ($break == 'br' or $break == 'hr')
			{
				$break = "<$break $breakatts/>".n;
			}

			return ($wraptag) ?	tag(join($break, $list), $wraptag, $atts) :	join($break, $list);
		}

		return ($wraptag) ?
			tag(n.t.tag(join("</$break>".n.t."<{$break}{$breakatts}>", $list), $break, $breakatts).n, $wraptag, $atts) :
			tag(n.join("</$break>".n."<{$break}{$breakatts}>".n, $list).n, $break, $breakatts);
	}

// -------------------------------------------------------------

	function doTag($content, $tag, $class = '', $atts = '', $id = '')
	{
		if ($id)
		{
			$atts .= ' id="'.txpspecialchars($id).'"';
		}

		if ($class)
		{
			$atts .= ' class="'.txpspecialchars($class).'"';
		}

		if (!$tag)
		{
			return $content;
		}

		return ($content) ? tag($content, $tag, $atts) : "<$tag $atts />";
	}

// -------------------------------------------------------------
	function doLabel($label='', $labeltag='')
	{
		if ($label) {
			return (empty($labeltag)? $label.'<br />' : tag($label, $labeltag));
		}
		return '';
	}

// -------------------------------------------------------------

	function permlink($atts, $thing = NULL)
	{
		global $thisarticle;

		extract(lAtts(array(
			'class' => '',
			'id'    => '',
			'style' => '',
			'title' => '',
		), $atts));

		if (!$id)
		{
			assert_article();
		}

		$url = ($id) ? permlinkurl_id($id) : permlinkurl($thisarticle);

		if ($url)
		{
			if ($thing === NULL)
			{
				return $url;
			}

			return tag(parse($thing), 'a', ' rel="bookmark" href="'.$url.'"'.
				($title ? ' title="'.txpspecialchars($title).'"' : '').
				($style ? ' style="'.txpspecialchars($style).'"' : '').
				($class ? ' class="'.txpspecialchars($class).'"' : '')
			);
		}
	}

// -------------------------------------------------------------

	function permlinkurl_id($id)
	{
		global $permlinks;
		if (isset($permlinks[$id])) return $permlinks[$id];

		$id = (int) $id;

		$rs = safe_row(
			"ID as thisid, Section as section, Title as title, url_title, unix_timestamp(Posted) as posted",
			'textpattern',
			"ID = $id"
		);

		return permlinkurl($rs);
	}

// -------------------------------------------------------------
	function permlinkurl($article_array)
	{
		global $permlink_mode, $prefs, $permlinks;
		// TODO: A bit hackish. lAtts() might serve us better.
		unset($article_array['permlink_mode'], $article_array['prefs'], $article_array['permlinks']);

		if (isset($prefs['custom_url_func'])
		    and is_callable($prefs['custom_url_func'])
		    and ($url = call_user_func($prefs['custom_url_func'], $article_array, PERMLINKURL)) !== FALSE)
		{
			return $url;
		}

		if (empty($article_array)) return;

		extract($article_array);

		if (empty($thisid)) $thisid = $ID;

		if (isset($permlinks[$thisid])) return $permlinks[$thisid];

		if (!isset($title)) $title = $Title;
		if (empty($url_title)) $url_title = stripSpace($title);
		if (empty($section)) $section = $Section; // lame, huh?
		if (!isset($posted)) $posted = $Posted;

		$section = urlencode($section);
		$url_title = urlencode($url_title);

		switch($permlink_mode) {
			case 'section_id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					$out = hu."$section/$thisid/$url_title";
				}else{
					$out = hu."$section/$thisid/";
				}
				break;
			case 'year_month_day_title':
				list($y,$m,$d) = explode("-",date("Y-m-d",$posted));
				$out =  hu."$y/$m/$d/$url_title";
				break;
			case 'id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					$out = hu."$thisid/$url_title";
				}else{
					$out = hu."$thisid/";
				}
				break;
			case 'section_title':
				$out = hu."$section/$url_title";
				break;
			case 'title_only':
				$out = hu."$url_title";
				break;
			case 'messy':
				$out = hu."index.php?id=$thisid";
				break;
		}
		return $permlinks[$thisid] = $out;
	}

// -------------------------------------------------------------
	function lang()
	{
		return LANG;
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function formatPermLink($ID,$Section)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		return permlinkurl_id($ID);
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function formatCommentsInvite($AnnotateInvite,$Section,$ID)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		global $comments_mode;

		$dc = safe_count('txp_discuss','parentid='.intval($ID).' and visible='.VISIBLE);

		$ccount = ($dc) ?  '['.$dc.']' : '';
		if (!$comments_mode) {
			return '<a href="'.permlinkurl_id($ID).'/#'.gTxt('comment').
				'">'.$AnnotateInvite.'</a>'. $ccount;
		} else {
			return "<a href=\"".hu."?parentid=$ID\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\">".$AnnotateInvite.'</a> '.$ccount;
		}

	}
// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function doPermlink($text, $plink, $Title, $url_title)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		global $url_mode;
		$Title = ($url_title) ? $url_title : stripSpace($Title);
		$Title = ($url_mode) ? $Title : '';
		return preg_replace("/<(txp:permlink)>(.*)<\/\\1>/sU",
			"<a href=\"".$plink.$Title."\" title=\"".gTxt('permanent_link')."\">$2</a>",$text);
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function doArticleHref($ID,$Title,$url_title,$Section)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		$conTitle = ($url_title) ? $url_title : stripSpace($Title);
		return ($GLOBALS['url_mode'])
		?	tag($Title,'a',' href="'.hu.$Section.'/'.$ID.'/'.$conTitle.'"')
		:	tag($Title,'a',' href="'.hu.'index.php?id='.$ID.'"');
	}

// -------------------------------------------------------------

	function breadcrumb($atts)
	{
		global $pretext,$sitename;

		extract(lAtts(array(
			'wraptag'   => 'p',
			'sep'       => '&#160;&#187;&#160;', // deprecated in 4.3.0
			'separator' => '&#160;&#187;&#160;',
			'link'      => 1,
			'label'     => $sitename,
			'title'     => '',
			'class'     => '',
			'linkclass' => '',
		),$atts));

		if (isset($atts['sep'])) {
			$separator = $sep;
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sep')), E_USER_NOTICE);
		}

		// bc, get rid of in crockery
		if ($link == 'y') {
			$linked = true;
		} elseif ($link == 'n') {
			$linked = false;
		} else {
			$linked = $link;
		}

		$label = txpspecialchars($label);
		if ($linked) $label = doTag($label,'a',$linkclass,' href="'.hu.'"');

		$content = array();
		extract($pretext);
		if(!empty($s) && $s!= 'default')
		{
			$section_title = ($title) ? fetch_section_title($s) : $s;
			$section_title_html = escape_title($section_title);
			$content[] = ($linked)? (
					doTag($section_title_html,'a',$linkclass,' href="'.pagelinkurl(array('s'=>$s)).'"')
				):$section_title_html;
		}

		$category = empty($c)? '': $c;

		foreach (getTreePath($category, 'article') as $cat) {
			if ($cat['name'] != 'root') {
				$category_title_html = $title ? escape_title($cat['title']) : $cat['name'];
				$content[] = ($linked)?
					doTag($category_title_html,'a',$linkclass,' href="'.pagelinkurl(array('c'=>$cat['name'])).'"')
						:$category_title_html;
			}
		}

		// add the label at the end, to prevent breadcrumb for home page
		if ($content)
		{
			$content = array_merge(array($label), $content);

			return doTag(join($separator, $content), $wraptag, $class);
		}
	}


//------------------------------------------------------------------------

	function if_excerpt($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		# eval condition here. example for article excerpt
		$excerpt = trim($thisarticle['excerpt']);
		$condition = (!empty($excerpt))? true : false;
		return parse(EvalElse($thing, $condition));
	}

//--------------------------------------------------------------------------
// Searches use default page. This tag allows you to use different templates if searching
//--------------------------------------------------------------------------

	function if_search($atts, $thing)
	{
		global $pretext;
		return parse(EvalElse($thing, !empty($pretext['q'])));
	}

//--------------------------------------------------------------------------

	function if_search_results($atts, $thing)
	{
		global $thispage, $pretext;

		if(empty($pretext['q'])) return '';

		extract(lAtts(array(
			'min' => 1,
			'max' => 0,
		),$atts));

		$results = (int)$thispage['grand_total'];
		return parse(EvalElse($thing, $results >= $min && (!$max || $results <= $max)));
	}

//--------------------------------------------------------------------------
	function if_category($atts, $thing)
	{
		global $c, $context;

		extract(lAtts(array(
			'type' => 'article',
			'name' => FALSE,
		),$atts));

		$theType = ($type) ? $type == $context : true;
		if ($name === FALSE)
		{
			return parse(EvalElse($thing, ($theType && !empty($c))));
		}
		else
		{
			return parse(EvalElse($thing, ($theType && in_list($c, $name))));
		}
	}

//--------------------------------------------------------------------------

	function if_article_category($atts, $thing)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'name'   => '',
			'number' => '',
		), $atts));

		$cats = array();

		if ($number) {
			if (!empty($thisarticle['category'.$number])) {
				$cats = array($thisarticle['category'.$number]);
			}
		} else {
			if (!empty($thisarticle['category1'])) {
				$cats[] = $thisarticle['category1'];
			}

			if (!empty($thisarticle['category2'])) {
				$cats[] = $thisarticle['category2'];
			}

			$cats = array_unique($cats);
		}

		if ($name) {
			return parse(EvalElse($thing, array_intersect(do_list($name), $cats)));
		} else {
			return parse(EvalElse($thing, ($cats)));
		}
	}

// -------------------------------------------------------------
	function if_first_category($atts, $thing)
	{
		global $thiscategory;
		assert_category();
		return parse(EvalElse($thing, !empty($thiscategory['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_category($atts, $thing)
	{
		global $thiscategory;
		assert_category();
		return parse(EvalElse($thing, !empty($thiscategory['is_last'])));
	}

//--------------------------------------------------------------------------
	function if_section($atts, $thing)
	{
		global $pretext;
		extract($pretext);

		extract(lAtts(array(
			'name' => FALSE,
		),$atts));

		$section = ($s == 'default' ? '' : $s);

		if ($section)
			return parse(EvalElse($thing, $name === FALSE or in_list($section, $name)));
		else
			return parse(EvalElse($thing, $name !== FALSE and (in_list('', $name) or in_list('default', $name))));

	}

//--------------------------------------------------------------------------
	function if_article_section($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'name' => '',
		),$atts));

		$section = $thisarticle['section'];

		return parse(EvalElse($thing, in_list($section, $name)));
	}

// -------------------------------------------------------------
	function if_first_section($atts, $thing)
	{
		global $thissection;
		assert_section();
		return parse(EvalElse($thing, !empty($thissection['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_section($atts, $thing)
	{
		global $thissection;
		assert_section();
		return parse(EvalElse($thing, !empty($thissection['is_last'])));
	}

//--------------------------------------------------------------------------
	function php($atts, $thing)
	{
		global $is_article_body, $thisarticle, $prefs;

		if (assert_array($prefs) === FALSE) return '';

		ob_start();
		if (empty($is_article_body)) {
			if (!empty($prefs['allow_page_php_scripting']))
				eval($thing);
			else
				trigger_error(gTxt('php_code_disabled_page'));
		}
		else {
			if (!empty($prefs['allow_article_php_scripting'])) {
				if (has_privs('article.php', $thisarticle['authorid']))
					eval($thing);
				else
					trigger_error(gTxt('php_code_forbidden_user'));
			}
			else
				trigger_error(gTxt('php_code_disabled_article'));
		}
		return ob_get_clean();
	}

//--------------------------------------------------------------------------
	function custom_field($atts)
	{
		global $is_article_body, $thisarticle, $prefs;
		assert_article();

		extract(lAtts(array(
			'name'    => @$prefs['custom_1_set'],
			'escape'  => 'html',
			'default' => '',
		),$atts));

		$name = strtolower($name);
		if (!empty($thisarticle[$name]))
			$out = $thisarticle[$name];
		else
			$out = $default;

		$was_article_body = $is_article_body;
		$is_article_body = 1;
		$out = ($escape == 'html' ? txpspecialchars($out) : parse($out));
		$is_article_body = $was_article_body;
		return $out;
	}

//--------------------------------------------------------------------------
	function if_custom_field($atts, $thing)
	{
		global $thisarticle, $prefs;
		assert_article();

		extract(lAtts(array(
			'name'      => @$prefs['custom_1_set'],
			'value'     => NULL,
			'val'       => NULL, // deprecated in 4.3.0
			'match'     => 'exact',
			'separator' => '',
		),$atts));

		if (isset($atts['val'])) {
			$value = $val;
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'val')), E_USER_NOTICE);
		}

		$name = strtolower($name);
		if ($value !== NULL)
			switch ($match) {
				case '':
				case 'exact':
					$cond = (@$thisarticle[$name] == $value);
					break;
				case 'any':
					$values = do_list($value);
					$cond = false;
					$cf_contents = ($separator) ? do_list(@$thisarticle[$name], $separator) : @$thisarticle[$name];
					foreach($values as $term) {
						if ($term == '') continue;
						$cond = is_array($cf_contents) ? in_array($term, $cf_contents) : ((strpos($cf_contents, $term) !== false) ? true : false);

						// Short circuit if a match is found
						if ($cond) break;
					}
					break;
				case 'all':
					$values = do_list($value);
					$num_values = count($values);
					$term_count = 0;
					$cf_contents = ($separator) ? do_list(@$thisarticle[$name], $separator) : @$thisarticle[$name];
					foreach ($values as $term) {
						if ($term == '') continue;
						$term_count += is_array($cf_contents) ? in_array($term, $cf_contents) : ((strpos($cf_contents, $term) !== false) ? true : false);
					}
					$cond = ($term_count == $num_values) ? true : false;
					break;
				case 'pattern':
					// Cannot guarantee that a fixed delimiter won't break preg_match (and preg_quote doesn't help) so
					// dynamically assign the delimiter based on the first entry in $dlmPool that is NOT in the value attribute.
					// This minimises (does not eliminate) the possibility of a TXP-initiated preg_match error, while still
					// preserving errors outside TXP's control (e.g. mangled user-submitted PCRE pattern)
					$dlmPool = array('/', '@', '#', '~', '`', '|', '!', '%');
					$dlm = array_merge(array_diff($dlmPool, preg_split('//', $value, -1)));
					$dlm = (count($dlm) > 0) ? $dlm[0].$value.$dlm[0] : $value;
					$cond = preg_match($dlm, @$thisarticle[$name]);
					break;
				default:
					trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'value')), E_USER_NOTICE);
					$cond = false;
			}
		else
			$cond = !empty($thisarticle[$name]);

		return parse(EvalElse($thing, $cond));
	}

// -------------------------------------------------------------
	function site_url()
	{
		return hu;
	}

// -------------------------------------------------------------
	function error_message()
	{
		return @$GLOBALS['txp_error_message'];
	}

// -------------------------------------------------------------
	function error_status()
	{
		return @$GLOBALS['txp_error_status'];
	}

// -------------------------------------------------------------
	function if_status($atts, $thing)
	{
		global $pretext;

		extract(lAtts(array(
			'status' => '200',
		), $atts));

		$page_status = !empty($GLOBALS['txp_error_code'])
			? $GLOBALS['txp_error_code']
			: $pretext['status'];

		return parse(EvalElse($thing, $status == $page_status));
	}

// -------------------------------------------------------------
	function page_url($atts)
	{
		global $pretext;

		extract(lAtts(array(
			'type' => 'request_uri',
		), $atts));

		return @txpspecialchars($pretext[$type]);
	}

// -------------------------------------------------------------
	function if_different($atts, $thing)
	{
		static $last;

		$key = md5($thing);

		$cond = EvalElse($thing, 1);

		$out = parse($cond);
		if (empty($last[$key]) or $out != $last[$key]) {
			return $last[$key] = $out;
		}
		else
			return parse(EvalElse($thing, 0));
	}

// -------------------------------------------------------------
	function if_first_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, !empty($thisarticle['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, !empty($thisarticle['is_last'])));
	}

// -------------------------------------------------------------
	function if_plugin($atts, $thing)
	{
		global $plugins, $plugins_ver;
		extract(lAtts(array(
			'name'    => '',
			'ver'     => '', // deprecated in 4.3.0
			'version' => '',
		),$atts));

		if (isset($atts['ver'])) {
			$version = $ver;
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'ver')), E_USER_NOTICE);
		}

		return parse(EvalElse($thing, @in_array($name, $plugins) and (!$version or version_compare($plugins_ver[$name], $version) >= 0)));
	}

//--------------------------------------------------------------------------

	function file_download_list($atts, $thing = NULL)
	{
		global $s, $c, $context, $thisfile, $thispage, $pretext;

		extract(lAtts(array(
			'break'       => br,
			'category'    => '',
			'author'      => '',
			'realname'    => '',
			'auto_detect' => 'category, author',
			'class'       => __FUNCTION__,
			'form'        => 'files',
			'id'          => '',
			'label'       => '',
			'labeltag'    => '',
			'pageby'      => '',
			'limit'       => 10,
			'offset'      => 0,
			'sort'        => 'filename asc',
			'wraptag'     => '',
			'status'      => '4',
		), $atts));

		if (!is_numeric($status))
			$status = getStatusNum($status);

		// N.B. status treated slightly differently
		$where = $statwhere = array();
		$filters = isset($atts['id']) || isset($atts['category']) || isset($atts['author']) || isset($atts['realname']) || isset($atts['status']);
		$context_list = (empty($auto_detect) || $filters) ? array() : do_list($auto_detect);
		$pageby = ($pageby=='limit') ? $limit : $pageby;

		if ($category) $where[] = "category IN ('".join("','", doSlash(do_list($category)))."')";
		$ids = array_map('intval', do_list($id));
		if ($id) $where[] = "id IN ('".join("','", $ids)."')";
		if ($status) $statwhere[] = "status = '".doSlash($status)."'";
		if ($author) $where[] = "author IN ('".join("','", doSlash(do_list($author)))."')";
		if ($realname) {
			$authorlist = safe_column('name', 'txp_users', "RealName IN ('". join("','", doArray(doSlash(do_list($realname)), 'urldecode')) ."')" );
			$where[] = "author IN ('".join("','", doSlash($authorlist))."')";
		}

		// If no files are selected, try...
		if (!$where && !$filters)
		{
			foreach ($context_list as $ctxt)
			{
				switch ($ctxt)
				{
					case 'category':
						// ... the global category in the URL
						if ($context == 'file' && !empty($c))
						{
							$where[] = "category = '".doSlash($c)."'";
						}
						break;
					case 'author':
						// ... the global author in the URL
						if ($context == 'file' && !empty($pretext['author']))
						{
							$where[] = "author = '".doSlash($pretext['author'])."'";
						}
						break;
				}
				// Only one context can be processed
				if ($where) break;
			}
		}

		if (!$where && !$statwhere && $filters)
		{
			return ''; // If nothing matches, output nothing
		}

		if (!$where)
		{
			$where[] = "1=1"; // If nothing matches, start with all files
		}

		$where = join(' AND ', array_merge($where, $statwhere));

		// Set up paging if required
		if ($limit && $pageby) {
			$grand_total = safe_count('txp_file', $where);
			$total = $grand_total - $offset;
			$numPages = ($pageby > 0) ? ceil($total/$pageby) : 1;
			$pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
			$pgoffset = $offset + (($pg - 1) * $pageby);
			// send paging info to txp:newer and txp:older
			$pageout['pg']          = $pg;
			$pageout['numPages']    = $numPages;
			$pageout['s']           = $s;
			$pageout['c']           = $c;
			$pageout['context']     = 'file';
			$pageout['grand_total'] = $grand_total;
			$pageout['total']       = $total;

			if (empty($thispage))
				$thispage = $pageout;
		} else {
			$pgoffset = $offset;
		}

		// preserve order of custom file ids unless 'sort' attribute is set
		if (!empty($atts['id']) && empty($atts['sort']))
		{
			$safe_sort = 'field(id, '.join(',', $ids).')';
		}
		else
		{
			$safe_sort = doSlash($sort);
		}

		$qparts = array(
			'order by '.$safe_sort,
			($limit) ? 'limit '.intval($pgoffset).', '.intval($limit) : '',
		);

		$rs = safe_rows_start('*', 'txp_file', $where.' '.join(' ', $qparts));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				$thisfile = file_download_format_info($a);

				$out[] = ($thing) ? parse($thing) : parse_form($form);

				$thisfile = '';
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}
		return '';
	}

//--------------------------------------------------------------------------

	function file_download($atts, $thing = NULL)
	{
		global $thisfile;

		extract(lAtts(array(
			'filename' => '',
			'form'     => 'files',
			'id'       => '',
		), $atts));

		$from_form = false;

		if ($id)
		{
			$thisfile = fileDownloadFetchInfo('id = '.intval($id));
		}

		elseif ($filename)
		{
			$thisfile = fileDownloadFetchInfo("filename = '".doSlash($filename)."'");
		}

		else
		{
			assert_file();

			$from_form = true;
		}

		if ($thisfile)
		{
			$out = ($thing) ? parse($thing) : parse_form($form);

			// cleanup: this wasn't called from a form,
			// so we don't want this value remaining
			if (!$from_form)
			{
				$thisfile = '';
			}

			return $out;
		}
	}

//--------------------------------------------------------------------------

	function file_download_link($atts, $thing = NULL)
	{
		global $thisfile;

		extract(lAtts(array(
			'filename' => '',
			'id'       => '',
		), $atts));

		$from_form = false;

		if ($id)
		{
			$thisfile = fileDownloadFetchInfo('id = '.intval($id));
		}

		elseif ($filename)
		{
			$thisfile = fileDownloadFetchInfo("filename = '".doSlash($filename)."'");
		}

		else
		{
			assert_file();

			$from_form = true;
		}

		if ($thisfile)
		{
			$url = filedownloadurl($thisfile['id'], $thisfile['filename']);

			$out = ($thing) ? href(parse($thing), $url) : $url;

			// cleanup: this wasn't called from a form,
			// so we don't want this value remaining
			if (!$from_form)
			{
				$thisfile = '';
			}

			return $out;
		}
	}

//--------------------------------------------------------------------------

	function fileDownloadFetchInfo($where)
	{
		$rs = safe_row('*', 'txp_file', $where);

		if ($rs)
		{
			return file_download_format_info($rs);
		}

		return false;
	}

//--------------------------------------------------------------------------

	function file_download_format_info($file)
	{
		if (($unix_ts = @strtotime($file['created'])) > 0)
			$file['created'] = $unix_ts;
		if (($unix_ts = @strtotime($file['modified'])) > 0)
			$file['modified'] = $unix_ts;

		return $file;
	}

//--------------------------------------------------------------------------

	function file_download_size($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'decimals' => 2,
			'format'   => '',
		), $atts));

		if (is_numeric($decimals) and $decimals >= 0)
		{
			$decimals = intval($decimals);
		}
		else
		{
			$decimals = 2;
		}

		if (isset($thisfile['size']))
		{
			$format_unit = strtolower(substr($format, 0, 1));
			return format_filesize($thisfile['size'], $decimals, $format_unit);
		}
		else
		{
			return '';
		}
	}

//--------------------------------------------------------------------------

	function file_download_created($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'format' => '',
		), $atts));

		if ($thisfile['created']) {
			return fileDownloadFormatTime(array(
				'ftime'  => $thisfile['created'],
				'format' => $format
			));
		}
	}

//--------------------------------------------------------------------------

	function file_download_modified($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'format' => '',
		), $atts));

		if ($thisfile['modified']) {
			return fileDownloadFormatTime(array(
				'ftime'  => $thisfile['modified'],
				'format' => $format
			));
		}
	}

//-------------------------------------------------------------------------
// All the time related file_download tags in one
// One Rule to rule them all... now using safe formats

	function fileDownloadFormatTime($params)
	{
		global $prefs;

		extract(lAtts(array(
			'ftime'  => '',
			'format' => ''
		), $params));

		if (!empty($ftime))
		{
			return !empty($format) ?
				safe_strftime($format, $ftime) : safe_strftime($prefs['archive_dateformat'], $ftime);
		}
		return '';
	}

//--------------------------------------------------------------------------

	function file_download_id()
	{
		global $thisfile;
		assert_file();
		return $thisfile['id'];
	}

//--------------------------------------------------------------------------

	function file_download_name($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'title' => 0,
		), $atts));

		return ($title) ? $thisfile['title'] : $thisfile['filename'];
	}

//--------------------------------------------------------------------------

	function file_download_category($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'class'   => '',
			'title'   => 0,
			'wraptag' => '',
		), $atts));

		if ($thisfile['category'])
		{
			$category = ($title) ?
				fetch_category_title($thisfile['category'], 'file') :
				$thisfile['category'];

			return ($wraptag) ? doTag($category, $wraptag, $class) : $category;
		}
	}

//--------------------------------------------------------------------------

	function file_download_author($atts)
	{
		global $thisfile, $s;
		assert_file();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'title'        => 1,
			'section'      => '',
			'this_section' => '',
			'wraptag'      => '',
		), $atts));

		if ($thisfile['author'])
		{
			$author_name = get_author_name($thisfile['author']);
			$display_name = txpspecialchars( ($title) ? $author_name : $thisfile['author'] );

			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

			$author = ($link) ?
				href($display_name, pagelinkurl(array('s' => $section, 'author' => $author_name, 'context' => 'file'))) :
				$display_name;

			return ($wraptag) ? doTag($author, $wraptag, $class) : $author;
		}
	}

//--------------------------------------------------------------------------

	function file_download_downloads()
	{
		global $thisfile;
		assert_file();
		return $thisfile['downloads'];
	}

//--------------------------------------------------------------------------

	function file_download_description($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'class'   => '',
			'escape'  => 'html',
			'wraptag' => '',
		), $atts));

		if ($thisfile['description'])
		{
			$description = ($escape == 'html') ?
				txpspecialchars($thisfile['description']) : $thisfile['description'];

			return ($wraptag) ? doTag($description, $wraptag, $class) : $description;
		}
	}

// -------------------------------------------------------------

	function hide()
	{
		return '';
	}

// -------------------------------------------------------------

	function rsd()
	{
		global $prefs;
		return ($prefs['enable_xmlrpc_server']) ? '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.hu.'rpc/" />' : '';
	}

// -------------------------------------------------------------

	function variable($atts, $thing = NULL)
	{
		global $variable;

		extract(lAtts(array(
			'name'  => '',
			'value' => parse($thing)
		), $atts));

		if (empty($name))
		{
			trigger_error(gTxt('variable_name_empty'));
			return;
		}

		if (!isset($atts['value']) && is_null($thing))
		{
			if (isset($variable[$name])) {
				return $variable[$name];
			} else {
				trace_add("[<txp:variable>: Unknown variable '$name']");
				return '';
			}
		}
		else
		{
			$variable[$name] = $value;
		}
	}

// -------------------------------------------------------------

	function if_variable($atts, $thing = NULL)
	{
		global $variable;

		extract(lAtts(array(
			'name'  => '',
			'value' => ''
		), $atts));

		if (empty($name))
		{
			trigger_error(gTxt('variable_name_empty'));
			return;
		}

		if (isset($variable[$name]))
		{
			if (!isset($atts['value']))
			{
				$x = true;
			}
			else
			{
				$x = $variable[$name] == $value;
			}
		}
		else
		{
			$x = false;
		}

		return parse(EvalElse($thing, $x));
	}

?>

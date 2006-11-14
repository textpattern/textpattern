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
		global $sitename, $s, $c, $q, $pg, $id, $parentid;

		extract(lAtts(array(
			'separator' => ': ',
		), $atts));

		$out = $sitename;

		if ($pg)
		{
			$out = $sitename.$separator.gTxt('page').' '.$pg;
		}

		if ($s and $s != 'default')
		{
			$out = $sitename.$separator.fetch_section_title($s);
		}

		if ($c)
		{
			$out = $sitename.$separator.fetch_category_title($c);
		}

		if ($q)
		{
			$out = $sitename.$separator.gTxt('search_results').
				$separator.' '.$q;
		}

		if ($id)
		{
			$id = (int) $id;

			$out = $sitename.$separator.
				safe_field('Title', 'textpattern', "ID = $id");
		}

		if ($parentid)
		{
			$parent_id = (int) $parent_id;

			$out = $sitename.$separator.gTxt('comments_on').' '.
				safe_field('Title', 'textpattern', "ID = $parentid");
		}

		return escape_title($out);
	}

// -------------------------------------------------------------

	function css($atts)
	{
		global $txp_error_code, $s;

		extract(lAtts(array(
			'format' => 'url',
			'media'  => 'screen',
			'n'      => '',
			'rel'    => 'stylesheet',
			'title'  => '',
		), $atts));

		if ($txp_error_code == '404')
		{
			$url = hu.'textpattern/css.php?n=default';
		}
	
		elseif ($n)
		{
			$url = hu.'textpattern/css.php?n='.$n;
		}

		elseif ($s)
		{
			$sn = safe_field('css','txp_section',"name='".doSlash($s)."'");
			$url = hu.'textpattern/css.php?n='.$sn;
		}

		else
		{
			$url = hu.'textpattern/css.php?n=default';
		}

		if ($format == 'link')
		{
			return '<link rel="'.$rel.'" type="text/css"'.
				($media ? ' media="'.$media.'"' : '').
				($title ? ' title="'.$title.'"' : '').
				' href="'.$url.'" />';
		}

		return $url;
	}

// -------------------------------------------------------------

	function image($atts)
	{
		global $img_dir;

		static $cache = array();

		extract(lAtts(array(
			'align'		=> '', // remove in crockery
			'class'		=> '',
			'escape'	=> '',
			'html_id' => '',
			'id'			=> '',
			'name'		=> '',
			'style'		=> '', // remove in crockery?
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
				$alt = escape_output($alt);
				$caption = escape_output($caption);
			}

			$out = '<img src="'.hu.$img_dir.'/'.$id.$ext.'" width="'.$w.'" height="'.$h.'" alt="'.$alt.'"'.
				($caption ? ' title="'.$caption.'"' : '').
				( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
				( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
				($style ? ' style="'.$style.'"' : '').
				($align ? ' align="'.$align.'"' : '').
				' />';

			return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
		}

		trigger_error(gTxt('unknown_image'));
	}

// -------------------------------------------------------------

	function thumbnail($atts)
	{
		global $img_dir;

		extract(lAtts(array(
			'align'			=> '', // remove in crockery
			'class'			=> '',
			'escape'		=> '',
			'html_id'		=> '',
			'id'				=> '',
			'name'			=> '',
			'poplink'		=> '',
			'style'			=> '', // remove in crockery?
			'wraptag'		=> ''
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
					$alt = escape_output($alt);
					$caption = escape_output($caption);
				}

				$out = '<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="'.$alt.'"'.
					($caption ? ' title="'.$caption.'"' : '').
					( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					($style ? ' style="'.$style.'"' : '').
					($align ? ' align="'.$align.'"' : '').
					' />';

				if ($poplink)
				{
					$out = '<a href="'.hu.$img_dir.'/'.$id.$ext.'"'.
						' onclick="window.open(this.href, \'popupwindow\', '.
						'\'width='.$w.', height='.$h.', scrollbars, resizable\'); return false;">'.$out.'</a>';
				}

				return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
			}

		}

		trigger_error(gTxt('unknown_image'));
	}

// -------------------------------------------------------------
	function output_form($atts) 
	{
		extract(lAtts(array(
			'form' => '',
		), $atts));

		if (!$form)
			trigger_error(gTxt('form_not_specified'));
		else
			return parse_form($form);

	}

// -------------------------------------------------------------

	function feed_link($atts, $thing=NULL)
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

		$title = escape_output($title);

		if ($format == 'link')
		{
			$type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

			return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
		}

		$txt = ($thing === NULL ? $label : parse($thing));
		$out = '<a href="'.$url.'" title="'.$title.'">'.$txt.'</a>';

		return ($wraptag) ? tag($out, $wraptag) : $out;
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
		), $atts));

		$url = pagelinkurl(array(
			$flavor => '1',
			'area'  =>'link',
			'c'     => $category
		));

		if ($flavor == 'atom')
		{
			$title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
		}

		$title = escape_output($title);

		if ($format == 'link')
		{
			$type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

			return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
		}

		$out = '<a href="'.$url.'" title="'.$title.'">'.$label.'</a>';

		return ($wraptag) ? tag($out, $wraptag) : $out;
	}

// -------------------------------------------------------------

	function linklist($atts)
	{
		global $thislink;

		extract(lAtts(array(
			'break'		 => '',
			'category' => '',
			'class'		 => __FUNCTION__,
			'form'		 => 'plainlinks',
			'label'		 => '',
			'labeltag' => '',
			'limit'		 => '',
			'sort'		 => 'linksort asc',
			'wraptag'	 => '',
		), $atts));

		$form = fetch_form($form);

		$qparts = array(
			($category) ? "category = '".doSlash($category)."'" : '1',
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($limit) : ''
		);

		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_link', join(' ', $qparts));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				extract($a);

				$thislink = array(
					'linkname'    => $linkname,
					'url'         => $url,
					'description' => $description,
					'date'        => $uDate,
					'category'    => $category,
				);

				$out[] = parse($form);
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return false;
	}

// -------------------------------------------------------------

	function tpt_link($atts)
	{
		global $thislink;

		extract(lAtts(array(
			'rel' => '',
		), $atts));

		return tag(
			escape_output($thislink['linkname']), 'a',
			($rel ? ' rel="'.$rel.'"' : '').
			' href="'.doSpecial($thislink['url']).'"'
		);
	}

// -------------------------------------------------------------

	function linkdesctitle($atts)
	{
		global $thislink;

		extract(lAtts(array(
			'rel' => '',
		), $atts));

		$description = ($thislink['description']) ? 
			' title="'.escape_output($thislink['description']).'"' : 
			'';

		return tag(
			escape_output($thislink['linkname']), 'a',
			($rel ? ' rel="'.$rel.'"' : '').
			' href="'.doSpecial($thislink['url']).'"'.$description
		);
	}

// -------------------------------------------------------------

	function link_name($atts)
	{
		global $thislink;

		extract(lAtts(array(
			'escape'	 => '',
		), $atts));

		return ($escape == 'html') ? 
			escape_output($thislink['linkname']) : 
			$thislink['linkname'];
	}

// -------------------------------------------------------------

	function link_url($atts)
	{
		global $thislink;

		return $thislink['url'];
	}

// -------------------------------------------------------------

	function link_description($atts)
	{
		global $thislink;

		extract(lAtts(array(
			'class'		 => '',
			'escape'	 => '',
			'label'		 => '',
			'labeltag' => '',
			'wraptag'	 => '',
		), $atts));

		if ($thislink['description'])
		{
			$description = ($escape == 'html') ?
				escape_output($thislink['description']) :
				$thislink['description'];

			return doLabel($label, $labeltag).doTag($description, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function link_date($atts)
	{
		global $thislink, $dateformat;

		extract(lAtts(array(
			'format' => $dateformat,
			'gmt'		 => '',
			'lang'	 => '',
		), $atts));

		return safe_strftime($format, $thislink['date'], $gmt, $lang);
	}

// -------------------------------------------------------------

	function link_category($atts)
	{
		global $thislink;

		extract(lAtts(array(
			'class'		 => '',
			'label'		 => '',
			'labeltag' => '',
			'title'		 => 0,
			'wraptag'	 => '',
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
	function eE($txt) // convert email address into unicode entities
	{
		 for ($i=0;$i<strlen($txt);$i++) { 
			  $ent[] = "&#".ord(substr($txt,$i,1)).";"; 
		 } 
		 if (!empty($ent)) return join('',$ent); 
	}

// -------------------------------------------------------------
	function email($atts) // simple contact link
	{
		extract(lAtts(array(
			'email'    => '',
			'linktext' => gTxt('contact'),
			'title'    => '',
		),$atts));

		if($email) {
			$out  = array(
				'<a href="'.eE('mailto:'.$email).'"',
				($title) ? ' title="'.$title.'"' : '',
				'>',
				$linktext,
				'</a>'
			);
			return join('',$out);
		}
		return '<txp:notice message="malformed email tag />"';
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
			'sortby'   => '',
			'sortdir'  => '',
			'wraptag'  => '',
			'no_widow' => @$prefs['title_no_widow'],
		), $atts));

		// for backwards compatibility
		// sortby and sortdir are deprecated
		if ($sortby)
		{
			if (!$sortdir)
			{
				$sortdir = 'desc';
			}

			$sort = "$sortby $sortdir";
		}

		elseif ($sortdir)
		{
			$sort = "Posted $sortdir";
		}

		$categories = ($category) ? "and (Category1 = '".doSlash($category)."' or Category2 = '".doSlash($category)."')" : '';
		$section = ($section) ? " and Section = '".doSlash($section)."'" : '';

		$rs = safe_rows_start('*, id as thisid, unix_timestamp(Posted) as posted', 'textpattern', 
			"Status = 4 $section $categories and Posted <= now() order by ".doSlash($sort).' limit 0,'.intval($limit));

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

	function recent_comments($atts)
	{
		extract(lAtts(array(
			'break'		 => br,
			'class'		 => __FUNCTION__,
			'label'		 => '',
			'labeltag' => '',
			'limit'		 => 10,
			'sort'     => 'posted desc',
			'wraptag'	 => '',
		), $atts));

		$rs = safe_rows_start('parentid, name, discussid', 'txp_discuss', 
			'visible = '.VISIBLE.' order by '.doSlash($sort).' limit 0,'.intval($limit));

		if ($rs)
		{
			$out = array();

			while ($c = nextRow($rs))
			{
				$a = safe_row('*, ID as thisid, unix_timestamp(Posted) as posted', 
					'textpattern', 'ID = '.intval($c['parentid']));

				If ($a['Status'] >= 4)
				{
					$out[] = href(
						$c['name'].' ('.escape_title($a['Title']).')', 
						permlinkurl($a).'#c'.$c['discussid']
					);
				}
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function related_articles($atts)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'break'    => br,
			'class'    => __FUNCTION__,
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'match'    => 'Category1,Category2',
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

		$section = ($section) ? " and Section = '".doSlash($section)."'" : '';

		$rs = safe_rows_start('*, unix_timestamp(Posted) as posted', 'textpattern', 
			'ID != '.intval($id)." and Status = 4 and Posted <= now() $categories $section order by ".doSlash($sort).' limit 0,'.intval($limit));
	
		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				$out[] = href(escape_title($a['Title']), permlinkurl($a));
			}

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
		global $s, $c;

		extract(lAtts(array(
			'label'        => gTxt('browse'),
			'wraptag'      => '',
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

				$out[] = '<option value="'.$name.'"'.$sel.'>'.htmlspecialchars($title).'</option>';

				$sel = '';
			}

			if ($out)
			{
				$section = ($this_section) ? ( $s == 'default' ? '' : $s) : $section;

				$out = n.'<select name="'.$type.'" onchange="submit(this.form);">'.
					n.t.'<option value=""'.($selected ? '' : ' selected="selected"').'>&nbsp;</option>'.
					n.t.join(n.t, $out).
					n.'</select>';

				if ($label)
				{
					$out = $label.br.$out;
				}

				if ($wraptag)
				{
					$out = tag($out, $wraptag);
				}

				return '<form method="get" action="'.hu.'">'.
					'<div>'.
					( ($type != 's' and $section and $s) ? n.hInput('s', $section) : '').
					n.$out.
					n.'<noscript><div><input type="submit" value="'.gTxt('go').'" /></div></noscript>'.
					n.'</div>'.
					n.'</form>';
			}
		}
	}

// -------------------------------------------------------------
// output href list of site categories

	function category_list($atts)
	{
		global $s, $c;

		extract(lAtts(array(
			'active_class' => '',
			'break'        => br,
			'categories'   => '',
			'class'        => __FUNCTION__,
			'exclude'      => '',
			'label'        => '',
			'labeltag'     => '',
			'parent'       => '',
			'section'      => '',
			'this_section' => 0,
			'type'         => 'article',
			'wraptag'      => '',
		), $atts));

		if ($categories)
		{
			$categories = do_list($categories);
			$categories = join("','", doSlash($categories));

			$rs = safe_rows_start('name, title', 'txp_category', 
				"type = '".doSlash($type)."' and name in ('$categories') order by field(name, '$categories')");
		}

		else
		{
			if ($exclude)
			{
				$exclude = do_list($exclude);

				$exclude = join("','", doSlash($exclude));

				$exclude = "and name not in('$exclude')";
			}

			if ($parent)
			{
				$qs = safe_row('lft, rgt', 'txp_category', "name = '".doSlash($parent)."'");

				if ($qs)
				{
					extract($qs);

					$rs = safe_rows_start('name, title', 'txp_category', 
						"(lft between $lft and $rgt) and type = '".doSlash($type)."' and name != 'default' $exclude order by lft asc");
				}
			}

			else
			{
				$rs = safe_rows_start('name, title', 'txp_category', 
					"type = '$type' and name not in('default','root') $exclude order by name");
			}
		}

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				extract($a);

				if ($name)
				{
					$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

					$out[] = tag(str_replace('& ', '&#38; ', $title), 'a', 
						( ($active_class and ($c == $name)) ? ' class="'.$active_class.'"' : '' ).
						' href="'.pagelinkurl(array('s' => $section, 'c' => $name)).'"'
					);
				}
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}			
		}

		return '';
	}

// -------------------------------------------------------------
// output href list of site sections

	function section_list($atts) 
	{
		global $sitename, $s;

		extract(lAtts(array(
			'active_class'    => '',
			'break'           => br,
			'class'           => __FUNCTION__,
			'default_title'   => $sitename,
			'exclude'         => '',
			'include_default' => '',
			'label'           => '',
			'labeltag'        => '',
			'sections'        => '',
			'wraptag'         => '',
		), $atts));

		if ($sections)
		{
			$sections = do_list($sections);

			$sections = join("','", doSlash($sections));

			$rs = safe_rows_start('name, title', 'txp_section', "name in ('$sections') order by field(name, '$sections')");
		}

		else
		{
			if ($exclude)
			{
				$exclude = do_list($exclude);

				$exclude = join("','", doSlash($exclude));
				
				$exclude = "and name not in('$exclude')";
			}

			$rs = safe_rows_start('name, title', 'txp_section', "name != 'default' $exclude order by name");
		}

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				extract($a);

				$url = pagelinkurl(array('s' => $name));

				$out[] = tag($title, 'a', 
					( ($active_class and ($s == $name)) ? ' class="'.$active_class.'"' : '' ).
					' href="'.$url.'"'
				);
			}

			if ($out)
			{
				if ($include_default)
				{
					$out = array_merge(array(
						tag($default_title,'a', 
							( ($active_class and ($s == 'default')) ? ' class="'.$active_class.'"' : '' ).
							' href="'.hu.'"'
						)
					), $out);
				}

				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------
	function search_input($atts) // input form for search queries
	{
		global $q, $permlink_mode;
		extract(lAtts(array(
			'form'    => 'search_input',
			'wraptag' => 'p',
			'size'    => '15',
			'label'   => gTxt('search'),
			'button'  => '',
			'section' => '',
		),$atts));	

		if ($form) {
			$rs = fetch('form','txp_form','name',$form);
			if ($rs) {
				return $rs;
			}
		}

		$sub = (!empty($button)) ? '<input type="submit" value="'.$button.'" />' : '';
		$out = fInput('text','q',$q,'','','',$size);
		$out = (!empty($label)) ? $label.br.$out.$sub : $out.$sub;
		$out = ($wraptag) ? tag($out,$wraptag) : $out;
	
		if (!$section)
			return '<form action="'.hu.'" method="get">'.$out.'</form>';

		$url = pagelinkurl(array('s'=>$section));	
		return '<form action="'.$url.'" method="get">'.$out.'</form>';
	}

// -------------------------------------------------------------
// link to next article, if it exists

	function link_to_next($atts, $thing)
	{
		global $id, $next_id, $next_title;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		if (intval($id) == 0)
		{
			global $thisarticle, $s;

			extract(getNextPrev(
				@$thisarticle['thisid'],
				@strftime('%Y-%m-%d %H:%M:%S', $thisarticle['posted']),
				@$s
			));
		}

		if ($next_id)
		{
			$url = permlinkurl_id($next_id);

			if ($thing)
			{
				$thing = parse($thing);

				return '<a rel="next" href="'.$url.'"'.
					($next_title != $thing ? ' title="'.$next_title.'"' : '').
					'>'.$thing.'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------
// link to next article, if it exists

	function link_to_prev($atts, $thing) 
	{
		global $id, $prev_id, $prev_title;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		if (intval($id) == 0)
		{
			global $thisarticle, $s;

			extract(getNextPrev(
				$thisarticle['thisid'],
				@strftime('%Y-%m-%d %H:%M:%S', $thisarticle['posted']), 
				@$s
			));
		}

		if ($prev_id)
		{
			$url = permlinkurl_id($prev_id);

			if ($thing)
			{
				$thing = parse($thing);

				return '<a rel="prev" href="'.$url.'"'.
					($prev_title != $thing ? ' title="'.$prev_title.'"' : '').
					'>'.$thing.'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------

	function next_title()
	{
		return $GLOBALS['next_title'];
	}

// -------------------------------------------------------------

	function prev_title()
	{
		return $GLOBALS['prev_title'];
	}

// -------------------------------------------------------------

	function site_slogan()
	{
		return $GLOBALS['site_slogan'];
	}

// -------------------------------------------------------------
	
	function link_to_home($atts, $thing = false) 
	{
		extract(lAtts(array(
			'class' => false,
		), $atts));

		if ($thing)
		{
			$class = ($class) ? ' class="'.$class.'"' : '';
			return '<a rel="home" href="'.hu.'"'.$class.'>'.parse($thing).'</a>';
		}

		return hu;
	}

// -------------------------------------------------------------

	function newer($atts, $thing = false, $match = '')
	{
		global $thispage, $pretext, $permlink_mode;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		$numPages = $thispage['numPages'];
		$pg				= $thispage['pg'];

		if ($numPages > 1 and $pg > 1)
		{
			$nextpg = ($pg - 1 == 1) ? 0 : ($pg - 1);

			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$url = pagelinkurl(array(
				'pg'		 => $nextpg,
				's'			 => @$pretext['s'],
				'c'			 => @$pretext['c'],
				'q'			 => @$pretext['q'],
				'author' => $author
			));

			if ($thing)
			{
				return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------

	function older($atts, $thing = false, $match = '')
	{
		global $thispage, $pretext, $permlink_mode;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		$numPages = $thispage['numPages'];
		$pg				= $thispage['pg'];

		if ($numPages > 1 and $pg != $numPages)
		{
			$nextpg = $pg + 1;

			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$url = pagelinkurl(array(
				'pg'		 => $nextpg,
				's'			 => @$pretext['s'],
				'c'			 => @$pretext['c'],
				'q'			 => @$pretext['q'],
				'author' => $author
			));

			if ($thing)
			{
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

	function if_article_id($atts, $thing)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'id' => '',
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
			'wraptag' => '',
		), $atts));

		if ($format)
		{
			$out = safe_strftime($format, $thisarticle['posted'], $gmt, $lang);
		}

		else
		{
			if ($id or $c or $pg)
			{
				$out = safe_strftime($archive_dateformat, $thisarticle['posted']);
			}

			else
			{
				$out = safe_strftime($dateformat, $thisarticle['posted']);
			}
		}

		return ($wraptag) ? doWrap($out, $wraptag, '', $class) : $out;
	}

// -------------------------------------------------------------

	function comments_count($atts) 
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
			'class'		=> __FUNCTION__,
			'showcount'	=> true,
			'textonly'	=> false,
			'showalways'=> false,  //FIXME in crockery. This is only for BC.
			'wraptag'   => '',
		), $atts));

		$invite_return = '';
		if (($annotate or $comments_count) && ($showalways or $is_article_list) ) {

			$ccount = ($comments_count && $showcount) ?  ' ['.$comments_count.']' : '';
			if ($textonly)
				$invite_return = $comments_invite.$ccount;
			else
			{
				if (!$comments_mode) {
					$invite_return = doTag($comments_invite, 'a', $class, ' href="'.permlinkurl($thisarticle).'#'.gTxt('comment').'" '). $ccount;
				} else {
					$invite_return = "<a href=\"".hu."?parentid=$thisid\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\"".(($class) ? ' class="'.$class.'"' : '').'>'.$comments_invite.'</a> '.$ccount;
				}
			}
			if ($wraptag) $invite_return = doTag($invite_return, $wraptag, $class);
		}

		return $invite_return;
	}
// -------------------------------------------------------------

	function comments_form($atts)
	{
		global $thisarticle, $has_comments_preview, $pretext;

		extract(lAtts(array(
			'class'        => __FUNCTION__,
			'form'         => 'comment_form',
			'id'           => @$pretext['id'],
			'isize'        => '25',
			'msgcols'      => '25',
			'msgrows'      => '5',
			'msgstyle'     => '',
			'show_preview' => empty($has_comments_preview),
			'wraptag'      => '',
		), $atts));

		assert_article();

		if (is_array($thisarticle)) extract($thisarticle);

		if (@$thisid) $id = $thisid;

		$out = '';
		if ($id) {
			$ip = serverset('REMOTE_ADDR');

			$blacklisted = is_blacklisted($ip);

			if (!checkCommentsAllowed($id)) {
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
				$out .= commentForm($id,$atts);
			}

			return (!$wraptag ? $out : doTag($out,$wraptag,$class) );
		}
	}

// -------------------------------------------------------------

	function comments_error($atts)
	{
		extract(lAtts(array(
			'break'		=> 'br',
			'class'		=> __FUNCTION__,
			'wraptag'	=> 'div',
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
	function comments_annotateinvite($atts,$thing=NULL)
	{
		global $thisarticle, $pretext;

		extract(lAtts(array(
			'id'		   => @$pretext['id'],
			'class'		=> __FUNCTION__,
			'wraptag'	=> 'h3',
		),$atts));

		assert_article();
		
		if (is_array($thisarticle)) extract($thisarticle);

		if (@$thisid) $id = $thisid;

		if ($id) {
			extract(
				safe_row(
					"Annotate,AnnotateInvite,unix_timestamp(Posted) as uPosted",
						"textpattern", 'ID = '.intval($id)
				)
			);

			if (!$thing)
				$thing = $AnnotateInvite;

			return (!$Annotate) ? '' : doTag($thing,$wraptag,$class,' id="'.gTxt('comment').'"');
		}
	}

// -------------------------------------------------------------
	function comments($atts)
	{
		global $thisarticle, $prefs, $pretext;
		extract($prefs);

		extract(lAtts(array(
			'id'		   => @$pretext['id'],
			'form'		=> 'comments',
			'wraptag'	=> ($comments_are_ol ? 'ol' : ''),
			'break'		=> ($comments_are_ol ? 'li' : 'div'),
			'class'		=> __FUNCTION__,
			'breakclass'=> '',
		),$atts));	

		assert_article();
		
		if (is_array($thisarticle)) extract($thisarticle);

		if (@$thisid) $id = $thisid;

		$Form = fetch_form($form);

		$rs = safe_rows_start("*, unix_timestamp(posted) as time", "txp_discuss",
			'parentid='.intval($id).' and visible='.VISIBLE.' order by posted asc');

		$out = '';

		if ($rs) {
			$comments = array();

			while($vars = nextRow($rs)) {
				$GLOBALS['thiscomment'] = $vars;
				$comments[] = parse($Form).n;
				unset($GLOBALS['thiscomment']);
			}

			$out .= doWrap($comments,$wraptag,$break,$class,$breakclass);
		}

		return $out;
	}
	
// -------------------------------------------------------------
	function comments_preview($atts, $thing='', $me='')
	{
		global $thisarticle, $has_comments_preview;
		if (!ps('preview'))
			return;


		extract(lAtts(array(
			'id'		   => @$pretext['id'],
			'form'		=> 'comments',
			'wraptag'	=> '',
			'class'		=> __FUNCTION__,
		),$atts));


		assert_article();
		
		if (is_array($thisarticle)) extract($thisarticle);

		if (@$thisid) $id = $thisid;

		$Form = fetch_form($form);

		$preview = psas(array('name','email','web','message','parentid','remember'));
		$preview['time'] = time();
		$preview['discussid'] = 0;
		if ($preview['message'] == '')
		{
			$in = getComment();
			$preview['message'] = $in['message'];

		}
		$preview['message'] = markup_comment($preview['message']);

		$GLOBALS['thiscomment'] = $preview;
		$comments = parse($Form).n;
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
	function comment_permlink($atts,$thing) 
	{
		global $thisarticle, $thiscomment;

		assert_article();
		
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
	function comment_id($atts) 
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

		if ($link)
		{
			$web = str_replace('http://', '', $web);
			$nofollow = (@$comment_nofollow ? ' rel="nofollow"' : '');

			if ($web)
			{
				return '<a href="http://'.$web.'"'.$nofollow.'>'.$name.'</a>';
			}

			if ($email && !$never_display_email)
			{
				return '<a href="'.eE('mailto:'.$email).'"'.$nofollow.'>'.$name.'</a>';
			}
		}

		return $name;
	}

// -------------------------------------------------------------
	function comment_email($atts) 
	{
		global $thiscomment;

		assert_comment();
		
		return $thiscomment['email'];
	}

// -------------------------------------------------------------
	function comment_web($atts) 
	{
		global $thiscomment;
		assert_comment();
		
		return $thiscomment['web'];
	}

// -------------------------------------------------------------

	function comment_time($atts)
	{
		global $thiscomment, $comments_dateformat;

		assert_comment();

		extract(lAtts(array(
			'format' => $comments_dateformat,
			'gmt'		 => '',
			'lang'	 => '',
		), $atts));

		return safe_strftime($format, $thiscomment['time'], $gmt, $lang);
	}

// -------------------------------------------------------------
	function comment_message($atts) 
	{
		global $thiscomment;
		assert_comment();
		
		return $thiscomment['message'];
	}

// -------------------------------------------------------------
	function comment_anchor($atts) 
	{
		global $thiscomment;

		assert_comment();
		
		$thiscomment['has_anchor_tag'] = 1;
		return '<a id="c'.$thiscomment['discussid'].'"></a>';
	}

// -------------------------------------------------------------
// DEPRECATED: the old comment message body tag
	function message($atts) 
	{
		return comment_message($atts);
	}

// -------------------------------------------------------------

	function author($atts)
	{
		global $thisarticle, $s;

		assert_article();

		extract(lAtts(array(
			'link'				 => '',
			'section'			 => '',
			'this_section' => 0,
		), $atts));

		$author_name = get_author_name($thisarticle['authorid']);

		$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

		return ($link) ?
			href($author_name, pagelinkurl(array('s' => $section, 'author' => $author_name))) :
			$author_name;
	}

// -------------------------------------------------------------

	function if_author($atts, $thing)
	{
		global $author;		

		extract(lAtts(array(
			'name' => '',
		), $atts));

		if ($name)
		{
			return parse(EvalElse($thing, in_list($author, $name)));
		}

		return parse(EvalElse($thing, !empty($author)));
	}

// -------------------------------------------------------------

	function if_article_author($atts, $thing)
	{
		global $thisarticle;

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

function body($atts) 
	{
		global $thisarticle, $is_article_body;
		assert_article();
		
		$is_article_body = 1;		
		$out = parse($thisarticle['body']);
		$is_article_body = 0;
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
	function excerpt($atts) 
	{
		global $thisarticle, $is_article_body;
		assert_article();
		
		$is_article_body = 1;		
		$out = parse($thisarticle['excerpt']);
		$is_article_body = 0;
		return $out;
	}

// -------------------------------------------------------------

	function category1($atts, $thing = '')
	{
		global $thisarticle, $s, $permlink_mode;

		assert_article();

		extract(lAtts(array(
			'class'				 => '',
			'link'				 => 0,
			'title'				 => 0,
			'section'			 => '',
			'this_section' => 0,
			'wraptag'			 => '',
		), $atts));

		if ($thisarticle['category1'])
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $thisarticle['category1'];

			$label = ($title) ? fetch_category_title($category) : $category;

			if ($thing)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
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

	function category2($atts, $thing = '')
	{
		global $thisarticle, $s, $permlink_mode;

		assert_article();

		extract(lAtts(array(
			'class'				 => '',
			'link'				 => 0,
			'title'				 => 0,
			'section'			 => '',
			'this_section' => 0,
			'wraptag'			 => '',
		), $atts));

		if ($thisarticle['category2'])
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $thisarticle['category2'];

			$label = ($title) ? fetch_category_title($category) : $category;

			if ($thing)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
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

	function category($atts, $thing = '')
	{
		global $s, $c;

		extract(lAtts(array(
			'class'				 => '',
			'link'				 => 0,
			'name'				 => '',
			'section'			 => $s, // fixme in crockery
			'this_section' => 0,
			'title'				 => 0,
			'type'				 => 'article',
			'wraptag'			 => '',
		), $atts));

		$category = ($name) ? $name : $c;

		if ($category)
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$label = ($title) ? fetch_category_title($category, $type) : $category;

			if ($thing)
			{
				$out = '<a href="'.pagelinkurl(array('s' => $section, 'c' => $category,)).'"'.
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = href($label,
					pagelinkurl(array('s' => $section, 'c' => $category))
				);
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function section($atts, $thing = '')
	{
		global $thisarticle, $s;

		extract(lAtts(array(
			'class'   => '',
			'link'		=> 0,
			'name'		=> '',
			'title'		=> 0,
			'wraptag' => '',
		), $atts));

		if ($name)
		{
			$sec = $name;
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
			$label = ($title) ? fetch_section_title($sec) : $sec;

			if ($thing)
			{
				$out = '<a href="'.pagelinkurl(array('s' => $sec)).'"'.
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = href($label,
					pagelinkurl(array('s' => $sec))
				);
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------
	function keywords($atts) 
	{
		global $thisarticle;
		assert_article();

		return ($thisarticle['keywords']) ? $thisarticle['keywords'] : '';
	}

// -------------------------------------------------------------

	function article_image($atts)
	{
		global $thisarticle, $img_dir;

		assert_article();

		extract(lAtts(array(
			'align' 	  => '', // remove in crockery
			'class'     => '',
			'escape'    => '',
			'html_id'   => '',
			'style' 	  => '', // remove in crockery?
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

		if (is_numeric($image))
		{
			$rs = safe_row('*', 'txp_image', 'id = '.intval($image));

			if ($rs)
			{
				if ($thumbnail)
				{
					if ($rs['thumbnail'])
					{
						extract($rs);

						if ($escape == 'html')
						{
							$alt = escape_output($alt);
							$caption = escape_output($caption);
						}

						$out = '<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="'.$alt.'"'.
							($caption ? ' title="'.$caption.'"' : '').
							( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
							( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
							($style ? ' style="'.$style.'"' : '').
							($align ? ' align="'.$align.'"' : '').
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
						$alt = escape_output($alt);
						$caption = escape_output($caption);
					}

					$out = '<img src="'.hu.$img_dir.'/'.$id.$ext.'" width="'.$w.'" height="'.$h.'" alt="'.$alt.'"'.
						($caption ? ' title="'.$caption.'"' : '').
						( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
						( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
						($style ? ' style="'.$style.'"' : '').
						($align ? ' align="'.$align.'"' : '').
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
			$out = '<img src="'.$image.'" alt=""'.
				( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
				( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
				($style ? ' style="'.$style.'"' : '').
				($align ? ' align="'.$align.'"' : '').
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
		extract(lAtts(array(
			'hilight'     => 'strong',
			'limit'       => 5,
		),$atts));
	
		assert_article();	
		extract($pretext);
		extract($thisarticle);
		
		$result = preg_replace("/>\s*</","> <",$body);
		preg_match_all("/(?:\s|^).{1,50}".preg_quote($q).".{1,50}(?:\s|$)/iu",$result,$concat);

		$r = array();
		for ($i=0; $i < min($limit, count($concat[0])); $i++)
			$r[] = trim($concat[0][$i]);
		$concat = join(" ...\n", $r);

		$concat = strip_tags($concat);
		$concat = preg_replace('/^[^>]+>/U',"",$concat);
		$concat = preg_replace("/(".preg_quote($q).")/i","<$hilight>$1</$hilight>",$concat);
		return ($concat) ? "... ".$concat." ..." : '';
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
		global $s,$c,$p,$img_dir,$path_to_site;
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'class'    => __FUNCTION__,
			'labeltag' => '',
			'c' => $c, // Keep the option to override categories due to backward compatiblity
		),$atts));
		$c = doSlash($c);
		
		$rs = safe_rows_start("*", "txp_image","category='$c' and thumbnail=1 order by name");

		if ($rs) {
			$out = array();
			while ($a = nextRow($rs)) {
				extract($a);
				$impath = $img_dir.'/'.$id.'t'.$ext;
				$imginfo = getimagesize($path_to_site.'/'.$impath);
				$dims = (!empty($imginfo[3])) ? ' '.$imginfo[3] : '';
				$url = pagelinkurl(array('c'=>$c, 's'=>$s, 'p'=>$id));
				$out[] = '<a href="'.$url.'">'.
               '<img src="'.hu.$impath.'"'.$dims.' alt="'.$alt.'" />'.'</a>';

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
		global $s,$c,$p,$img_dir;
		if($p) {
			$rs = safe_row("*", "txp_image", 'id='.intval($p).' limit 1');
			if ($rs) {
				extract($rs);
				$impath = hu.$img_dir.'/'.$id.$ext;
				return '<img src="'.$impath.
					'" style="height:'.$h.'px;width:'.$w.'px" alt="'.$alt.'" />';
			}
		}
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
		global $thisarticle, $pretext;

		$id = gAtt($atts,'id',gps('id'));
		if ($thisarticle['thisid']) $id = $thisarticle['thisid'];
		if (!$id && @$pretext['id']) $id = $pretext['id'];
		return parse(EvalElse($thing, checkCommentsAllowed($id)));
	}

// -------------------------------------------------------------
	function if_comments_disallowed($atts, $thing)
	{
		global $thisarticle, $pretext;

		$id = gAtt($atts,'id',gps('id'));
		if ($thisarticle['thisid']) $id = $thisarticle['thisid'];
		if (!$id && @$pretext['id']) $id = $pretext['id'];
		return parse(EvalElse($thing, !checkCommentsAllowed($id)));
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
		?	'<meta name="keywords" content="'.$id_keywords.'" />'
		:	'';
	}

// -------------------------------------------------------------
	function meta_author() 
	{
		global $id_author;
		return ($id_author)
		?	'<meta name="author" content="'.$id_author.'" />'
		:	'';
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
			$atts .= ' id="'.$id.'"';
		}

		if ($class)
		{
			$atts .= ' class="'.$class.'"';
		}
		
		if ($breakclass) 
		{
			$breakatts.= ' class="'.$breakclass.'"';
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

		// enclosing breaks should be specified by name only, no '<' or '>'
		if (($wraptag == 'ul' or $wraptag == 'ol') and empty($break))
		{
			$break = 'li';
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
			$atts .= ' id="'.$id.'"';
		}

		if ($class)
		{
			$atts .= ' class="'.$class.'"';
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
			'id'		=> '',
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

			if ($title == false and ($id == false or $id == $thisarticle['thisid']))
			{
				$title = gTxt('permanent_link');
			}

			return tag(parse($thing), 'a', ' rel="bookmark" href="'.$url.'"'.
				($title ? ' title="'.$title.'"' : '').
				($style ? ' style="'.$style.'"' : '').
				($class ? ' class="'.$class.'"' : '')
			);
		}
	}

// -------------------------------------------------------------
	function permlinkurl_id($ID)
	{
		$article = safe_row(
			"*,ID as thisid, unix_timestamp(Posted) as posted",
			"textpattern",
			'ID='.intval($ID));
		
		return permlinkurl($article);
	}

// -------------------------------------------------------------
	function permlinkurl($article_array) 
	{
		global $permlink_mode, $prefs;

		if (isset($prefs['custom_url_func']) and is_callable($prefs['custom_url_func']))
			return call_user_func($prefs['custom_url_func'], $article_array);

		if (empty($article_array)) return;
		
		extract($article_array);
		
		if (!isset($title)) $title = $Title;
		if (empty($url_title)) $url_title = stripSpace($title);
		if (empty($section)) $section = $Section; // lame, huh?
		if (empty($posted)) $posted = $Posted;
		if (empty($thisid)) $thisid = $ID;

		$section = urlencode($section);
		$url_title = urlencode($url_title);
		
		switch($permlink_mode) {
			case 'section_id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					return hu."$section/$thisid/$url_title";
				}else{
					return hu."$section/$thisid/";
				}
			case 'year_month_day_title':
				list($y,$m,$d) = explode("-",date("Y-m-d",$posted));
				return hu."$y/$m/$d/$url_title";
			case 'id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					return hu."$thisid/$url_title";
				}else{
					return hu."$thisid/";
				}
			case 'section_title':
				return hu."$section/$url_title";
			case 'title_only':
				return hu."$url_title";	
			case 'messy':
				return hu."index.php?id=$thisid";	
		}
	}
	
// -------------------------------------------------------------	
	function lang($atts)
	{
		return LANG;
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function formatPermLink($ID,$Section)
	{
		return permlinkurl_id($ID);
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function formatCommentsInvite($AnnotateInvite,$Section,$ID)
	{
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
		$conTitle = ($url_title) ? $url_title : stripSpace($Title);	
		return ($GLOBALS['url_mode'])
		?	tag($Title,'a',' href="'.hu.$Section.'/'.$ID.'/'.$conTitle.'"')
		:	tag($Title,'a',' href="'.hu.'index.php?id='.$ID.'"');
	}

// -------------------------------------------------------------
// Testing breadcrumbs
	function breadcrumb($atts)
	{
		global $pretext,$thisarticle,$sitename;
		
		extract(lAtts(array(
			'wraptag' => 'p',
			'sep' => '&#160;&#187;&#160;',
			'link' => 'y',
			'label' => $sitename,
			'title' => '',
			'class' => '',
			'linkclass' => 'noline',
		),$atts));
		$linked = ($link == 'y')? true: false; 		
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

			return doTag(join($sep, $content), $wraptag, $class);
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
		$searching = gps('q');
		$condition = (!empty($searching))? true : false;
		return parse(EvalElse($thing, $condition));
	}

//--------------------------------------------------------------------------
	function if_category($atts, $thing)
	{
		global $c;

		extract(lAtts(array(
			'name' => '',
		),$atts));

		if (trim($name)) {
			return parse(EvalElse($thing, in_list($c, $name)));
		}

		return parse(EvalElse($thing, !empty($c)));
	}

//--------------------------------------------------------------------------
	function if_article_category($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'name' => '',
			'number' => '',
		),$atts));

		if ($number)
			$cats = array($thisarticle['category' . $number]);
		else
			$cats = array_unique(array($thisarticle['category1'], $thisarticle['category2']));

		sort($cats);
		if ($name)
			return parse(EvalElse($thing, (in_array($name, $cats))));

		return parse(EvalElse($thing, (array_shift($cats) != '')));
	}

//--------------------------------------------------------------------------
	function if_section($atts, $thing)
	{
		global $pretext;
		extract($pretext);

		extract(lAtts(array(
			'name' => '',
		),$atts));

		$section = ($s == 'default' ? '' : $s);

		if ($section)
			return parse(EvalElse($thing, in_list($section, $name)));
		else
			return parse(EvalElse($thing, in_list('', $name) or in_list('default', $name)));

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

//--------------------------------------------------------------------------
	function php($atts, $thing)
	{
		global $is_article_body, $thisarticle, $prefs;

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
		global $thisarticle, $prefs;
		assert_article();
		
		extract(lAtts(array(
			'name' => @$prefs['custom_1_set'],
			'escape' => '',
			'default' => '',
		),$atts));

		$name = strtolower($name);
		if (!empty($thisarticle[$name]))
			$out = $thisarticle[$name];
		else
			$out = $default;

		return ($escape == 'html' ? escape_output($out) : $out);
	}

//--------------------------------------------------------------------------
	function if_custom_field($atts, $thing)
	{
		global $thisarticle, $prefs;
		assert_article();

		extract(lAtts(array(
			'name' => @$prefs['custom_1_set'],
			'val' => NULL,
		),$atts));

		$name = strtolower($name);
		if ($val !== NULL)
			$cond = (@$thisarticle[$name] == $val);
		else
			$cond = !empty($thisarticle[$name]);

		return parse(EvalElse($thing, $cond));
	}

// -------------------------------------------------------------
	function site_url($atts)
	{
		return hu;
	}

// -------------------------------------------------------------
	function img($atts)
	{
		extract(lAtts(array(
			'src' => '',
		), $atts));

		$img = rtrim(hu, '/').'/'.ltrim($src, '/');

		$out = '<img src="'.$img.'" />';

		return $out;
	}

// -------------------------------------------------------------
	function error_message($atts) 
	{
		return @$GLOBALS['txp_error_message'];
	}

// -------------------------------------------------------------
	function error_status($atts) 
	{
		return @$GLOBALS['txp_error_status'];
	}

// -------------------------------------------------------------
	function if_status($atts, $thing='') 
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

		return @htmlspecialchars($pretext[$type]);	
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
			'ver'     => '',
		),$atts));	

		return parse(EvalElse($thing, @in_array($name, $plugins) and (!$ver or version_compare($plugins_ver[$name], $ver) >= 0)));
	}

//--------------------------------------------------------------------------

	function file_download_list($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'break'		 => br,
			'category' => '',
			'class'		 => __FUNCTION__,
			'form'		 => 'files',
			'label'		 => '',
			'labeltag' => '',
			'limit'		 => '10',
			'offset'	 => '0',
			'sort'		 => 'filename asc',
			'wraptag'	 => '',
		), $atts));

		$qparts = array(
			($category) ? "category = '".doSlash($category)."'" : '1',
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('id, filename, category, description, downloads', 'txp_file', join(' ', $qparts));

		if ($rs)
		{
			$form = fetch_form($form);

			$out = array();

			while ($a = nextRow($rs))
			{
				$GLOBALS['thisfile'] = file_download_format_info($a);

				$out[] = parse($form);

				$GLOBALS['thisfile'] = '';
			}

			if ($out)
			{
				if ($wraptag == 'ul' or $wraptag == 'ol')
				{
					return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
				}

				return ($wraptag) ? tag(join($break, $out), $wraptag) : join(n, $out);
			}
		}
		return '';
	}

//--------------------------------------------------------------------------

	function file_download($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'filename' => '',
			'form'		 => 'files',
			'id'			 => '',
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

		elseif ($thisfile)
		{
			$from_form = true;
		}

		if ($thisfile)
		{
			$form = fetch_form($form);

			$out = parse($form);

			// cleanup: this wasn't called from a form,
			// so we don't want this value remaining
			if (!$from_form)
			{
				$GLOBALS['thisfile'] = '';
			}

			return $out;
		}
	}

//--------------------------------------------------------------------------

	function file_download_link($atts, $thing)
	{
		global $thisfile, $permlink_mode;

		extract(lAtts(array(
			'filename' => '',
			'id'			 => '',
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
		
		elseif ($thisfile)
		{
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
				$GLOBALS['thisfile'] = '';
			}

			return $out;
		}
	}

//--------------------------------------------------------------------------

	function fileDownloadFetchInfo($where)
	{
		$rs = safe_row('id, filename, category, description, downloads', 'txp_file', $where);

		if ($rs)
		{
			return file_download_format_info($rs);
		}

		return false;
	}

//--------------------------------------------------------------------------

	function file_download_format_info($file)
	{
		global $file_base_path;

		// get filesystem info
		$filepath = build_file_path($file_base_path, $file['filename']);

		if (file_exists($filepath))
		{
			$filesize = filesize($filepath);

			if ($filesize !== false)
			{
				$file['size'] = $filesize;
			}

			$created = filectime($filepath);

			if ($created !== false)
			{
				$file['created'] = $created;
			}

			$modified = filemtime($filepath);

			if ($modified !== false)
			{
				$file['modified'] = $modified;
			}
		} else {
			$file['size'] = false;
			$file['created'] = false;
			$file['modified'] = false;
		}

		return $file;
	}

//--------------------------------------------------------------------------

	function file_download_size($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'decimals' => 2,
			'format'	 => '',
		), $atts));

		if (is_numeric($decimals) and $decimals >= 0)
		{
			$decimals = intval($decimals);
		}

		else
		{
			$decimals = 2;
		}

		if ($thisfile['size'])
		{
			$size = $thisfile['size'];

			if (!in_array($format, array('B','KB','MB','GB','PB')))
			{
				$divs = 0;

				while ($size > 1024)
				{
					$size /= 1024;
					$divs++;
				}

				switch ($divs)
				{
					case 1:
						$format = 'KB';
					break;

					case 2:
						$format = 'MB';
					break;

					case 3:
						$format = 'GB';
					break;

					case 4:
						$format = 'PB';
					break;

					case 0:
					default:
						$format = 'B';
					break;
				}
			}

			$size = $thisfile['size'];

			switch ($format)
			{
				case 'KB':
					$size /= 1024;
				break;

				case 'MB':
					$size /= (1024*1024);
				break;

				case 'GB':
					$size /= (1024*1024*1024);
				break;

				case 'PB':
					$size /= (1024*1024*1024*1024);
				break;

				case 'B':
				default:
					// do nothing
				break;
			}

			return number_format($size, $decimals).$format;
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

		extract(lAtts(array(
			'format' => '',
		), $atts));

		if ($thisfile['created']) {
			return fileDownloadFormatTime(array(
				'ftime'	 => $thisfile['created'],
				'format' => $format
			));
		}
	}

//--------------------------------------------------------------------------

	function file_download_modified($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'format' => '',
		), $atts));

		if ($thisfile['modified']) {
			return fileDownloadFormatTime(array(
				'ftime'	 => $thisfile['modified'],
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

		extract($params);

		if (!empty($ftime))
		{
			return !empty($format) ?
				safe_strftime($format, $ftime) : safe_strftime($prefs['archive_dateformat'], $ftime);
		}
		return '';
	}

//--------------------------------------------------------------------------

	function file_download_id($atts)
	{
		global $thisfile;
		return $thisfile['id'];
	}

//--------------------------------------------------------------------------

	function file_download_name($atts)
	{
		global $thisfile;
		return $thisfile['filename'];
	}

//--------------------------------------------------------------------------

	function file_download_category($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'class'   => '',
			'escape'  => '',
			'wraptag' => '',
		), $atts));

		if ($thisfile['category'])
		{
			$category = ($escape == 'html') ? 
				escape_output($thisfile['category']) : $thisfile['category'];

			return ($wraptag) ? doTag($category, $wraptag, $class) : $category;
		}
	}

//--------------------------------------------------------------------------

	function file_download_downloads($atts)
	{
		global $thisfile;
		return $thisfile['downloads'];
	}

//--------------------------------------------------------------------------

	function file_download_description($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'class'   => '',
			'escape'  => '',
			'wraptag' => '',
		), $atts));

		if ($thisfile['description'])
		{
			$description = ($escape == 'html') ?
				escape_output($thisfile['description']) : $thisfile['description'];

			return ($wraptag) ? doTag($description, $wraptag, $class) : $description;
		}
	}

?>

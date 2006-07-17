<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

if (!defined('txpinterface'))
{
	die('txpinterface is undefined.');
}

// -------------------------------------------------------------

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Txp &#8250; <?php echo gTxt('build'); ?></title>
	<link rel="stylesheet" type="text/css" href="textpattern.css" />
</head>
<body id="tag-event">
<?php

	$tag_name = gps('tag_name');

	$functname = 'tag_'.$tag_name;

	if (function_exists($functname))
	{
		$endform = n.tr(
			td().
			td(
				fInput('submit', '', gTxt('build'), 'smallerbox')
			)
		).
		n.endTable().
		n.eInput('tag').
		n.sInput('build').
		n.hInput('tag_name', $tag_name);

		echo $functname($tag_name);
	}

?>

</body>
</html>
<?php

/*

begin generic functions

*/

// -------------------------------------------------------------

	function tagRow($label, $thing)
	{
		return n.n.tr(
			n.fLabelCell($label).
			n.td($thing)
		);
	}

// -------------------------------------------------------------

	function tb($tag, $atts_list = array(), $thing = '')
	{
		$atts = array();

		foreach ($atts_list as $att => $val)
		{
			if ($val or $val === '0')
			{
				$atts[] = ' '.$att.'="'.$val.'"';
			}
		}

		$atts = ($atts) ? join('', $atts) : '';

		return !empty($thing) ?
			'<txp:'.$tag.$atts.'>'.$thing.'</txp:'.$tag.'>' :
			'<txp:'.$tag.$atts.' />';
	}

// -------------------------------------------------------------

	function tbd($tag, $thing)
	{
		return '<txp:'.$tag.'>'.$thing.'</txp:'.$tag.'>';
	}

// -------------------------------------------------------------

	function tdb($thing)
	{
		return n.graf(text_area('tag', '100', '300', $thing), ' id="tagbuilder-output"');
	}

//--------------------------------------------------------------

	function key_input($name, $var)
	{
		return '<textarea name="'.$name.'" style="width: 120px; height: 50px;">'.$var.'</textarea>';
	}

//--------------------------------------------------------------

	function input_id($id)
	{
		return fInput('text', 'id', $id, 'edit', '', '', 6);
	}

//--------------------------------------------------------------

	function input_time($time)
	{
		return fInput('text', 'time', $time, 'edit', '', '', 6);
	}

//--------------------------------------------------------------

	function input_limit($limit)
	{
		return fInput('text', 'limit', $limit, 'edit', '', '', 2);
	}

//--------------------------------------------------------------

	function input_offset($offset)
	{
		return fInput('text', 'offset', $offset, 'edit', '', '', 2);
	}

//--------------------------------------------------------------

	function input_tag($name, $val)
	{
		return fInput('text', $name, $val, 'edit', '', '', 6);
	}

//--------------------------------------------------------------

	function yesno_pop($select_name, $val)
	{
		$vals = array(
			'y' => gTxt('yes'),
			'n' => gTxt('no')
		);

		return ' '.selectInput($select_name, $vals, $val, true);
	}

//--------------------------------------------------------------

	function yesno2_pop($select_name, $val)
	{
		$vals = array(
			1 => gTxt('yes'),
			0 => gTxt('no'),
		);

		return ' '.selectInput($select_name, $vals, $val, true);
	}

//--------------------------------------------------------------

	function status_pop($val)
	{
		$vals = array(
			4 => gTxt('live'),
			5 => gTxt('sticky'),
			3 => gTxt('pending'),
			1 => gTxt('draft'),
			2 => gTxt('hidden'),
		);

		return ' '.selectInput('status', $vals, $val, true);
	}

//--------------------------------------------------------------

	function section_pop($select_name, $val)
	{
		$vals = array();

		$rs = safe_rows_start('name, title', 'txp_section', "name != 'default' order by name");

		if ($rs and numRows($rs) > 0)
		{
			while ($a = nextRow($rs))
			{
				extract($a);

				$vals[$name] = $title;
			}

			return ' '.selectInput($select_name, $vals, $val, true);
		}

		return gTxt('no_sections_available');
	}

//--------------------------------------------------------------

	function type_pop($val)
	{
		$vals = array(
			'article' => gTxt('article'),
			'link'		=> gTxt('link'),
			'image'		=> gTxt('image'),
			'file'		=> gTxt('file'),
		);

		return ' '.selectInput('type', $vals, $val, true);
	}

//--------------------------------------------------------------

	function feed_flavor_pop($val)
	{
		$vals = array(
			'atom' => 'Atom 1.0',
			'rss'	 => 'RSS 0.92'
		);

		return ' '.selectInput('flavor', $vals, $val, true);
	}

//--------------------------------------------------------------

	function feed_format_pop($val)
	{
		$vals = array(
			'a'		 => '<a href...',
			'link' => '<link rel...',
		);

		return ' '.selectInput('format', $vals, $val, true);
	}

//--------------------------------------------------------------

	function article_category_pop($val)
	{
		$vals = getTree('root','article');

		if ($vals)
		{
			return ' '.treeSelectInput('category', $vals, $val);
		}

		return gTxt('no_categories_available');
	}

//--------------------------------------------------------------

	function link_category_pop($val)
	{
		$vals = getTree('root','link');

		if ($vals)
		{
			return ' '.treeSelectInput('parent', $vals, $val);
		}

		return gTxt('no_categories_available');
	}

//--------------------------------------------------------------

	function file_category_pop($val)
	{
		$vals = getTree('root','file');

		if ($vals)
		{
			return ' '.treeSelectInput('category', $vals, $val);
		}

		return gTxt('no_categories_available');
	}

//--------------------------------------------------------------

	function match_pop($val)
	{
		$vals = array(
			'Category1,Category2'	=> gTxt('category1').' '.gTxt('and').' '.gTxt('category2'),
			'Category1'						=> gTxt('category1'),
			'Category2'						=> gTxt('category2')
		);

		return ' '.selectInput('match', $vals, $val, true);
	}

//--------------------------------------------------------------

	function author_pop($val)
	{
		$vals = array();

		$rs = safe_rows_start('name', 'txp_users', '1 = 1 order by name');

		if ($rs)
		{
			while ($a = nextRow($rs))
			{
				extract($a);

				$vals[$name] = $name;
			}

			return ' '.selectInput('author', $vals, $val, true);
		}
	}

//--------------------------------------------------------------

	function sort_pop($val)
	{
		$asc = ' ('.gTxt('ascending').')';
		$desc = ' ('.gTxt('descending').')';

		$vals = array(
			'Title asc'			 => gTxt('tag_title').$asc,
			'Title desc'		 => gTxt('tag_title').$desc,
			'Posted asc'		 => gTxt('tag_posted').$asc,
			'Posted desc'		 => gTxt('tag_posted').$desc,
			'LastMod asc'		 => gTxt('last_modification').$asc,
			'LastMod desc'	 => gTxt('last_modification').$desc,
			'Section asc'		 => gTxt('section').$asc,
			'Section desc'	 => gTxt('section').$desc,
			'Category1 asc'	 => gTxt('category1').$asc,
			'Category1 desc' => gTxt('category1').$desc,
			'Category2 asc'	 => gTxt('category2').$asc,
			'Category2 desc' => gTxt('category2').$desc,
			'rand()'				 => gTxt('random')
		);

		return ' '.selectInput('sort', $vals, $val, true);
	}

//--------------------------------------------------------------

	function discuss_sort_pop($val)
	{
		$asc = ' ('.gTxt('ascending').')';
		$desc = ' ('.gTxt('descending').')';

		$vals = array(
			'posted asc'	=> gTxt('posted').$asc,
			'posted desc'	=> gTxt('posted').$desc,
		);

		return ' '.selectInput('sort', $vals, $val, true);
	}

//--------------------------------------------------------------

	function pgonly_pop($val)
	{
		$vals = array(
			'1' => gTxt('yes'),
			'0' => gTxt('no')
		);

		return ' '.selectInput('pgonly', $vals, $val, true);
	}

//--------------------------------------------------------------

	function form_pop($select_name, $type = '', $val)
	{
		$vals = array();

		$type = ($type) ? "type = '$type'" : '1 = 1';

		$rs = safe_rows_start('name', 'txp_form', "$type order by name");

		if ($rs and numRows($rs) > 0)
		{
			while ($a = nextRow($rs))
			{
				extract($a);

				$vals[$name] = $name;
			}

			return ' '.selectInput($select_name, $vals, $val, true);
		}

		return gTxt('no_forms_available');
	}

//--------------------------------------------------------------

	function css_pop($val)
	{
		$vals = array();

		$rs = safe_rows_start('name', 'txp_css', "1 = 1 order by name");

		if ($rs)
		{
			while ($a = nextRow($rs))
			{
				extract($a);

				$vals[$name] = $name;
			}

			return ' '.selectInput('n', $vals, $val, true);
		}

		return false;
	}

//--------------------------------------------------------------

	function css_format_pop($val)
	{
		$vals = array(
			'link' => '<link rel...',
			'url'	 => 'css.php?...'
		);

		return ' '.selectInput('format', $vals, $val, true);
	}

//--------------------------------------------------------------

	function escape_pop($val)
	{
		$vals = array(
			'html' => 'html',
		);

		return ' '.selectInput('escape', $vals, $val, true);
	}

//--------------------------------------------------------------

/*

begin tag builder functions

*/

// -------------------------------------------------------------

	function tag_article()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'allowoverride',
			'form',
			'limit',
			'listform',
			'offset',
			'pageby',
			'pgonly',
			'searchall',
			'searchsticky',
			'sort',
			'status',
			'time'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('status',
				status_pop($status)).

			tagRow('time',
				input_time($time)).

			tagRow('searchall',
				yesno2_pop('searchall', $searchall)).

			tagRow('searchsticky',
				yesno2_pop('searchsticky', $searchsticky)).

			tagRow('limit',
				input_limit($limit)).

			tagRow('offset',
				input_offset($offset)).

			tagRow('pageby',
				fInput('text', 'pageby', $pageby, 'edit', '', '', 2)).

			tagRow('sort',
				sort_pop($sort)).

			tagRow('pgonly',
				pgonly_pop($pgonly)).

			tagRow('allowoverride',
				yesno2_pop('allowoverride', $allowoverride)).

			tagRow('form',
				form_pop('form', 'article', $form)).

			tagRow('listform',
				form_pop('listform', 'article', $listform)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_article_custom()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'allowoverride',
			'author',
			'category',
			'excerpted',
			'form',
			'id',
			'keywords',
			'limit',
			'listform',
			'month',
			'offset',
			'pgonly',
			'section',
			'sort',
			'status',
			'time'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('id',
				input_id($id)).

			tagRow('status',
				status_pop($status)).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('category',
				article_category_pop($category)).

			tagRow('time',
				input_time($time)).

			tagRow('month',
				fInput('text', 'month', $month, 'edit', '', '', 7). ' ('.gTxt('yyyy-mm').')') .

			tagRow('keywords',
				key_input('keywords', $keywords)).

			tagRow('has_excerpt',
				yesno_pop('excerpted', $excerpted)).

			tagRow('author',
				author_pop($author)).

			tagRow('sort',
				sort_pop($sort)).

			tagRow('limit',
				input_limit($limit)).

			tagRow('offset',
				input_offset($offset)).

			tagRow('pgonly',
				pgonly_pop($pgonly)).

			tagRow('allowoverride',
				yesno2_pop('allowoverride', $allowoverride)).

			tagRow('form',
				form_pop('form', 'article', $form)).
			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_email()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'email',
			'linktext',
			'title'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('email_address',
				fInput('text', 'email', $email, 'edit', '', '', 20)).

			tagRow('tooltip',
				fInput('text', 'title', $title, 'edit', '', '', 20)).

			tagRow('link_text',
				fInput('text', 'linktext', $linktext, 'edit', '', '', 20)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_page_title()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array('separator'));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('title_separator',
				fInput('text', 'separator', $separator, 'edit', '', '', 4)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_linklist()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'break',
			'category',
			'form',
			'label',
			'labeltag',
			'limit',
			'sort',
			'wraptag',
		));

		$asc = ' ('.gTxt('ascending').')';
		$desc = ' ('.gTxt('descending').')';

		$sorts = array(
			'linksort asc'	=> gTxt('name').$asc,
			'linksort desc' => gTxt('name').$desc,
			'category asc'	=> gTxt('category').$asc,
			'category desc' => gTxt('category').$desc,
			'date asc'			=> gTxt('date').$asc,
			'date desc'			=> gTxt('date').$desc,
			'rand()'				=> gTxt('random')
		);

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('category',
				link_category_pop($category)).

			tagRow('limit',
				input_limit($limit)).

			tagRow('sort',
				' '.selectInput('sort', $sorts, $sort)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 20)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('form',
				form_pop('form', 'link', $form)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('break',
				input_tag('break', $break)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		echo $out;
	}

// -------------------------------------------------------------

	function tag_section_list()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'active_class',
			'break',
			'class',
			'default_title',
			'exclude',
			'include_default',
			'label',
			'labeltag',
			'sections',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('include_default',
				yesno2_pop('include_default', $include_default)).

			tagRow('default_title',
				fInput('text', 'default_title', $default_title, 'edit', '', '', 20)).

			tagRow('sections',
				fInput('text', 'sections', $sections, 'edit', '', '', 20)).

			tagRow('exclude',
				fInput('text', 'exclude', $exclude, 'edit', '', '', 20)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 20)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 14)).

			tagRow('active_class',
				fInput('text', 'active_class', $active_class, 'edit', '', '', 14)).

			tagRow('break',
				input_tag('break', $break)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		echo $out;

	}

// -------------------------------------------------------------

	function tag_category_list()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'active_class',
			'break',
			'categories',
			'class',
			'exclude',
			'label',
			'labeltag',
			'parent',
			'section',
			'this_section',
			'type',
			'wraptag',
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('type',
				type_pop($type)).

			tagRow('parent',
				fInput('text', 'parent', $parent, 'edit', '', '', 20)).

			tagRow('categories',
				fInput('text', 'categories', $categories, 'edit', '', '', 20)).

			tagRow('exclude',
				fInput('text', 'exclude', $exclude, 'edit', '', '', 20)).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('this_section',
				yesno2_pop('this_section', $this_section)).

			tagRow('label',
				fInput('text', 'label', ($label ? $label : gTxt('categories')), 'edit', '', '', 20)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 14)).

			tagRow('active_class',
				fInput('text', 'active_class', $active_class, 'edit', '', '', 14)).

			tagRow('break',
				input_tag('break', $break)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		echo $out;
	}

// -------------------------------------------------------------

	function tag_recent_articles()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'break',
			'category',
			'label',
			'labeltag',
			'limit',
			'section',
			'sort',
			'wraptag',
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('category',
				article_category_pop($category)).

			tagRow('sort',
				sort_pop($sort)).

			tagRow('limit',
				fInput('text', 'limit', $limit, 'edit', '', '', 2)).

			tagRow('label',
				fInput('text', 'label', ($label ? $label : gTxt('recently')), 'edit', '', '', 20)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('break',
				input_tag('break', $break)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_related_articles()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'break',
			'class',
			'label',
			'labeltag',
			'limit',
			'match',
			'section',
			'sort',
			'wraptag',
		));

		extract($atts);

		$label = (!$label) ? 'Related Articles' : $label;

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('match',
				match_pop($match)).

			tagRow('sort',
				sort_pop($sort)).

			tagRow('limit',
				fInput('text', 'limit', $limit, 'edit', '', '', 2)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 20)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 20)).

			tagRow('break',
				input_tag('break', $break)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_recent_comments()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'break',
			'class',
			'label',
			'labeltag',
			'limit',
			'sort',
			'wraptag',
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('sort',
				discuss_sort_pop($sort)).

			tagRow('limit',
				fInput('text', 'limit', $limit, 'edit', '', '', 2)).

			tagRow('label',
				fInput('text', 'label', ($label ? $label : gTxt('recent_comments')), 'edit', '', '', 20)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 5)).

			tagRow('break',
				input_tag('break', $break)).

		$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_output_form()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'form'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('form',
				form_pop('form', 'misc', $form)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_popup()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'label',
			'section',
			'this_section',
			'type',
			'wraptag'
		));

		extract($atts);

		$types = array(
			'c' => gTxt('Category'),
			's' => gTxt('Section')
		);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('type',
				' '.selectInput('type', $types, $type, true)).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('this_section',
				yesno2_pop('this_section', $this_section)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 25)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_password_protect()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'login',
			'pass'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('login',
				fInput('text', 'login', $login, 'edit', '', '', 25)).

			tagRow('password',
				fInput('text', 'pass', $pass, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_search_input()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'button',
			'class',
			'form',
			'label',
			'section',
			'size',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('button_text',
				fInput('text', 'button', $button, 'edit', '', '', 25)).

			tagRow('input_size',
				fInput('text', 'size', $size, 'edit', '', '', 2)).

			tagRow('label',
				fInput('text', 'label', ($label ? $label : gTxt('search')), 'edit', '', '', 25)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			tagRow('form',
				form_pop('form', 'misc', $form)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_category1()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'link',
			'title',
			'section',
			'this_section',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').
			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('title',
				yesno2_pop('title', $title)).

			tagRow('link_to_this_category',
				yesno_pop('link', $link)).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('this_section',
				yesno2_pop('this_section', $this_section)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_category2()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'link',
			'title',
			'section',
			'this_section',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').
			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('title',
				yesno2_pop('title', $title)).

			tagRow('link_to_this_category',
				yesno_pop('link', $link)).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('this_section',
				yesno2_pop('this_section', $this_section)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}


// -------------------------------------------------------------

	function tag_section()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'link',
			'name',
			'title',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('name',
				section_pop('name', $tag_name)).

			tagRow('link_to_this_section',
				yesno_pop('link', $link)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_author()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'link',
			'section',
			'this_section'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('link_to_this_author',
				yesno_pop('link', $link)).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('this_section',
				yesno2_pop('this_section', $this_section)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_link_to_home()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
		));

		extract($atts);

		$thing = gps('thing');

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('link_text',
				fInput('text', 'thing', ($thing ? $thing : gTxt('tag_home')), 'edit', '', '', 25)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_link_to_prev()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'showalways',
		));

		extract($atts);

		$thing = gps('thing');

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('link_text',
				fInput('text', 'thing', ($thing ? $thing : '<txp:prev_title />'), 'edit', '', '', 25)).

			tagRow('showalways',
				yesno_pop('showalways', $showalways)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_link_to_next()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'showalways',
		));

		extract($atts);

		$thing = gps('thing');

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('link_text',
				fInput('text', 'thing', ($thing ? $thing : '<txp:next_title />'), 'edit', '', '', 25)).

			tagRow('showalways',
				yesno_pop('showalways', $showalways)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_feed_link()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'category',
			'flavor',
			'format',
			'label',
			'limit',
			'section',
			'title',
			'wraptag',
		));

		extract($atts);

		$label = $label ? $label : 'XML';

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('flavor',
				feed_flavor_pop($flavor)).

			tagRow('format',
				feed_format_pop($format)).

			tagRow('section',
				section_pop('section', $section)).

			tagRow('category',
				article_category_pop($section)).

			tagRow('limit',
				input_limit($limit)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 25)).

			tagRow('title',
				fInput('text', 'title', $title, 'edit', '', '', 25)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_link_feed_link()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'category',
			'flavor',
			'format',
			'label',
			'limit',
			'title',
			'wraptag'
		));

		extract($atts);

		$label = (!$label) ? 'XML' : $label;

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('flavor',
				feed_flavor_pop($flavor)).

			tagRow('format',
				feed_format_pop($format)).

			tagRow('category',
				link_category_pop($category)).

			tagRow('limit',
				fInput('text', 'limit', $limit, 'edit', '', '', 2)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 25)).

			tagRow('title',
				fInput('text', 'title', $title, 'edit', '', '', 25)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_permlink()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'id',
			'style',
			'title'
		));

		extract($atts);

		$thing = gps('thing');

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('id',
				input_id($id)).

			tagRow('link_text',
				fInput('text', 'thing', ($thing ? $thing : '<txp:title />'), 'edit', '', '', 25)).

			tagRow('title',
				fInput('text', 'title', $title, 'edit', '', '', 25)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			tagRow('style',
				fInput('text', 'style', $style, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_newer()
	{
		global $step, $endform, $tag_name;

		$thing = gps('thing');

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('link_text',
				fInput('text', 'thing', ($thing ? $thing : '<txp:text item="newer" />'), 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, array(), $thing));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_older()
	{
		global $step, $endform, $tag_name;

		$thing = gps('thing');

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('link_text',
				fInput('text', 'thing', ($thing ? $thing : '<txp:text item="older" />'), 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, array(), $thing));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_next_title()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_sitename()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_site_slogan()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_prev_title()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_article_image()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'align',
			'style',
			'thumbnail'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('thumbnail',
				yesno2_pop('thumbnail', $thumbnail)).

			tagRow('style',
				fInput('text', 'style', $style, 'edit', '', '', 25)).

			tagRow('align',
				fInput('text', 'style', $style, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_css()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'format',
			'media',
			'n',
			'rel',
			'title'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('n',
				css_pop($n)).

			tagRow('format',
				css_format_pop($format)).

			tagRow('media',
				fInput('text', 'media', $media, 'edit', '', '', 25)).

			tagRow('rel',
				fInput('text', 'rel', $rel, 'edit', '', '', 25)).

			tagRow('title',
				fInput('text', 'title', $title, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_body()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_excerpt()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_title()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_link()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'rel'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('rel',
				fInput('text', 'rel', $rel, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_linkdesctitle()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'rel'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('rel',
				fInput('text', 'rel', $rel, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_link_description()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'escape',
			'label',
			'labeltag',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('escape',
				escape_pop($escape)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 25)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_link_name()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'escape',
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('escape',
				escape_pop($escape)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_link_category()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'label',
			'labeltag',
			'title',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('title',
				yesno2_pop('title', $title)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 25)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_link_date()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'format',
			'gmt',
			'lang'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('time_format',
				fInput('text', 'format', $format, 'edit', '', '', 25)).

			tagRow('gmt',
				yesno2_pop('gmt', $gmt)).

			tagRow('lang',
				fInput('text', 'lang', $lang, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_posted()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'format',
			'gmt',
			'lang'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('time_format',
				fInput('text', 'format', $format, 'edit', '', '', 25)).

			tagRow('gmt',
				yesno2_pop('gmt', $gmt)).

			tagRow('lang',
				fInput('text', 'lang', $lang, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_comments_invite()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'showcount',
			'textonly',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('textonly',
				yesno2_pop('textonly', $textonly)).

			tagRow('showcount',
				yesno2_pop('showcount', $showcount)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_comment_permlink()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_time()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'format',
			'gmt',
			'lang'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('time_format',
				fInput('text', 'format', $format, 'edit', '', '', 25)).

			tagRow('gmt',
				yesno2_pop('gmt', $gmt)).

			tagRow('lang',
				fInput('text', 'lang', $lang, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_comment_name()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'link'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('link',
				yesno2_pop('link', $link)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_comment_email()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_web()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_message()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_email_input()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_message_input()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_name_input()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_preview()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_remember()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_submit()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comment_web_input()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_comments()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'break',
			'breakclass',
			'class',
			'id',
			'form',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('id',
				input_id($id)).

			tagRow('form',
				form_pop('form', 'comment', $form)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			tagRow('break',
				input_tag('break', $break)).

			tagRow('breakclass',
				fInput('text', 'breakclass', $breakclass, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_comments_form()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'id',
			'isize',
			'form',
			'msgcols',
			'msgrows',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('id',
				input_id($id)).

			tagRow('isize',
				fInput('text', 'isize', $isize, 'edit', '', '', 2)).

			tagRow('msgcols',
				fInput('text', 'msgcols', $msgcols, 'edit', '', '', 2)).

			tagRow('msgrows',
				fInput('text', 'msgrows', $msgrows, 'edit', '', '', 2)).

			tagRow('form',
				form_pop('form', 'comment', $form)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_comments_preview()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'id',
			'form',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('id',
				input_id($id)).

			tagRow('form',
				form_pop('form', 'comment', $form)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_search_result_title()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_search_result_excerpt()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'hilight',
			'limit'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('hilight',
				input_tag('hilight', $hilight)).

			tagRow('limit',
				input_limit($limit)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_search_result_url()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_search_result_date()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'format',
			'gmt',
			'lang'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('time_format',
				fInput('text', 'format', $format, 'edit', '', '', 25)).

			tagRow('gmt',
				yesno2_pop('gmt', $gmt)).

			tagRow('lang',
				fInput('text', 'lang', $lang, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_lang()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_breadcrumb()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'label',
			'link',
			'linkclass',
			'sep',
			'title',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('breadcrumb_separator',
				fInput('text', 'sep', $sep, 'edit', '', '', 4)).

			tagRow('breadcrumb_linked',
				yesno_pop('link', $link)).

			tagRow('linkclass',
				fInput('text', 'linkclass', $linkclass, 'edit', '', '', 25)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 25)).

			tagRow('title',
				fInput('text', 'title', $title, 'edit', '', '', 25)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_image()
	{
		global $step, $endform, $tag_name, $img_dir;

		$atts = gpsa(array(
			'class',
			'html_id',
			'style',

			'alt',
			'h',
			'id',
			'w',
		));

		extract($atts);

		$ext = gps('ext');
		$type = gps('type');

		$types = array(
			'textile'			=> 'Textile',
			'textpattern' => 'Textpattern',
			'xhtml'				=> 'XHTML'
		);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('type',
				''.selectInput('type', $types, ($type ? $type : 'textpattern'), true)).

			tagRow('html_id',
				fInput('text', 'html_id', $html_id, 'edit', '', '', 25)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			tagRow('style',
				fInput('text', 'style', $style, 'edit', '', '', 25)).

			hInput('w', $w).
			hInput('h', $h).
			hInput('ext', $ext).
			hInput('id', $id).
			hInput('alt', $alt).

			$endform
		);

		if ($step == 'build')
		{
			$url = hu.$img_dir.'/'.$id.$ext;

			switch ($type)
			{
				case 'textile':
					$alt = ($alt) ? ' ('.$alt.')' : '';

					$out .= tdb('!'.$url.$alt.'!');
				break;

				case 'xhtml':
					$alt = ($alt) ? ' alt="'.$alt.'"' : '';
					$class = ($class) ? ' class="'.$class.'"' : '';
					$html_id = ($html_id) ? ' id="'.$html_id.'"' : '';
					$style = ($style) ? ' style="'.$style.'"' : '';

					$out .= tdb('<img src="'.$url.'" width="'.$w.'" height="'.$h.'"'.$alt.$html_id.$class.$style.' />');
				break;

				case 'textpattern':
				default:
					$atts = array('class' => $class, 'html_id' => $html_id, 'id' => $id, 'style' => $style);

					$out .= tdb(tb($tag_name, $atts));
				break;
			}
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_file_download()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'form',
			'id'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('id',
				input_id($id)).

			tagRow('form',
				form_pop('form', 'file', $form)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_file_download_list()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'break',
			'category',
			'form',
			'label',
			'labeltag',
			'limit',
			'sort',
			'wraptag',
		));

		$asc = ' ('.gTxt('ascending').')';
		$desc = ' ('.gTxt('descending').')';

		$sorts = array(
			'filename asc'	 => gTxt('name').$asc,
			'filename desc'	 => gTxt('name').$desc,
			'downloads asc'	 => gTxt('download_count').$asc,
			'downloads desc' => gTxt('download_count').$desc,
			'rand()'				 => 'Random'
		);

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(tdcs(hed(gTxt('tag_'.$tag_name),3),2) ).

			tagRow('category',
				file_category_pop($category)).

			tagRow('sort',
				' '.selectInput('sort', $sorts, $sort, true)).

			tagRow('limit',
				input_limit($limit)).

			tagRow('label',
				fInput('text', 'label', $label, 'edit', '', '', 25)).

			tagRow('labeltag',
				input_tag('labeltag', $labeltag)).

			tagRow('wraptag',
				input_tag('wraptag',$wraptag)).

			tagRow('break',
				input_tag('break',$break)).

			tagRow('form',
				form_pop('form','file',$form)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		echo $out;
	}

// -------------------------------------------------------------

	function tag_file_download_created()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'format'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('format',
				fInput('text', 'format', $format, 'edit', '', '', 15)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_file_download_modified()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'format'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('format',
				fInput('text', 'format', $format, 'edit', '', '', 15)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_file_download_size()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'decimals',
			'format'
		));

		$formats = array(
			'b'	 => 'Bytes',
			'kb' => 'Kilobytes',
			'mb' => 'Megabytes',
			'gb' => 'Gigabytes',
			'tb' => 'Terabytes',
			'pb' => 'Petabytes'
		);

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(hed(gTxt('tag_'.$tag_name), 3)
			, 2)
			).

			tagRow('format',
				' '.selectInput('format', $formats, $format, true)).

			tagRow('decimals',
				fInput('text', 'decimals', $decimals, 'edit', '', '', 4)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_file_download_link()
	{
		global $step, $endform, $tag_name, $permlink_mode;

		$atts = gpsa(array(
			'filename',
			'id'
		));

		extract($atts);

		$thing = gps('thing');

		$type = gps('type');
		$description = gps('description');

		$types = array(
			'textile'			=> 'Textile',
			'textpattern' => 'Textpattern',
			'xhtml'				=> 'XHTML'
		);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('type',
				''.selectInput('type', $types, ($type ? $type : 'textpattern'), true)).

			tagRow('link_text',
				fInput('text', 'thing', ($thing ? $thing : $filename), 'edit', '', '', 25)).

			tagRow('id',
				input_id($id)).

			tagRow('filename',
				fInput('text', 'filename', $filename, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$url = ($permlink_mode == 'messy') ?
				hu.'index.php?s=file_download'.a.'id='.$id:
				hu.'file_download/'.$id;

			switch ($type)
			{
				case 'textile':
					$thing = ($thing) ? $thing : $filename;
					$description = ($description) ? ' ('.$description.')' : '';

					$out .= tdb('"'.$thing.$description.'":'.$url);
				break;

				case 'xhtml':
					$thing = ($thing) ? $thing : $filename;

					$out .= tdb('<a href="'.$url.'">'.$thing.'</a>');
				break;

				case 'textpattern':
				default:
					$atts = array('id' => $id);
					$thing = ($thing) ? $thing : '<txp:file_download_name />';

					$out .= tdb(tb($tag_name, $atts, $thing));
				break;
			}
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_file_download_name()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_file_download_downloads()
	{
		global $step, $endform, $tag_name;

		return form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			n.endTable()
		).

		tdb(tb($tag_name));
	}

// -------------------------------------------------------------

	function tag_file_download_category()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'escape',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('escape',
				escape_pop($escape)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

// -------------------------------------------------------------

	function tag_file_download_description()
	{
		global $step, $endform, $tag_name;

		$atts = gpsa(array(
			'class',
			'escape',
			'wraptag'
		));

		extract($atts);

		$out = form(
			startTable('tagbuilder').

			tr(
				tdcs(
					hed(gTxt('tag_'.$tag_name), 3)
				, 2)
			).

			tagRow('escape',
				escape_pop($escape)).

			tagRow('wraptag',
				input_tag('wraptag', $wraptag)).

			tagRow('class',
				fInput('text', 'class', $class, 'edit', '', '', 25)).

			$endform
		);

		if ($step == 'build')
		{
			$out .= tdb(tb($tag_name, $atts));
		}

		return $out;
	}

?>
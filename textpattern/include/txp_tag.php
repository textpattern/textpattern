<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Textpattern: build a tag</title>
<link rel="stylesheet" href="/textpattern/textpattern.css" type="text/css" />
</head>
<body style="padding:10px;background-color:#fff;border-top:solid #FFCC33 15px;">
<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/
	$name = gps('name');
	$endform = tr(tdcs(fInput('submit','',gTxt('build'),'smallerbox'),2)).endTable().
		eInput('tag').sInput('build').hInput('name',$name);

	$functname = 'tag_'.$name;

	if(function_exists($functname)) {
		echo $functname($name);
	}


// -------------------------------------------------------------
	function tagRow($label, $thing) 
	{
		return tr(fLabelCell($label) . td($thing));
	}

// -------------------------------------------------------------
	function tag_article() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('form','limit','listform'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Article Output',3),2) ).
			tagRow('form', form_pop($form,'article','form')) .
			tagRow('listform', form_pop($listform,'article','listform')) .
			tagRow('limit', inputLimit($limit)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}


// -------------------------------------------------------------
	function tag_article_custom()
	{
		global $step,$endform,$name;
		$invars = gpsa(array(
			'form','limit','category','section','sortby','sortdir',
			'excerpted','author','month','keywords','listform'
		));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Custom Article Output',3),2) ) .
			tagRow('form'          , form_pop($form,'article','form')) .
			tagRow('listform'      , form_pop($listform,'article','listform')) .
			tagRow('limit'         , inputLimit($limit)) .
			tagRow('category'      , category_pop($category)) .
			tagRow('section'       , section_pop($section)) .	
			tagRow('keywords'      , key_input('keywords',$keywords)) .	
			tagRow('author'        , author_pop($author)) .	
			tagRow('sort_by'       , sort_pop($sortby)) . 
			tagRow('sort_direction', sortdir_pop($sortdir)) .
			tagRow('month'         , inputMonth($month). ' ('.gTxt('yyyy-mm').')') .
			tagRow('has_excerpt'   , yesno_pop('excerpted',$excerpted)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_email() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('email','linktext','title'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Spam-proof Contact Link',3),2) ) .
			tagRow('email_address', fInput('text','email',$email,'edit','','',20)).
			tagRow('tooltip', fInput('text','title',$title,'edit','','',20)).
			tagRow('link_text', fInput('text','linktext',$linktext,'edit','','',20)).
			$endform
		);
		
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_page_title() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('separator'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Page Title',3),2) ).
			tagRow('title_separator',fInput('text','separator',$separator,'edit','','',4)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_linklist() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('form','category','limit','sort','wraptag','break'));
		$sorts = array(''=>'','linksort'=>'Name',
				'date desc'=>'Date descending','date asc'=>'Date ascending', 'rand()'=>'Random');
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Link List',3),2) ).
			tagRow('form', form_pop($form,'link','form')).
			tagRow('category', link_category_pop($category)).
			tagRow('limit', fInput('text','limit',$limit,'edit','','',2)).
			tagRow('sort_by', selectInput("sort",$sorts,$sort)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			tagRow('break', fInput('text','break',$break,'edit','','',5)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		echo $out;	
	}

// -------------------------------------------------------------
	function tag_category_list() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('form','category','wraptag','break'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Category List',3),2) ).
			tagRow('category', category_pop($category)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			tagRow('break', fInput('text','break',$break,'edit','','',5)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		echo $out;	
	}


// -------------------------------------------------------------
	function tag_recent_articles() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('label','limit','break','wraptag','category','sortby','sortdir'));
		extract($invars);
		$label = (!$label) ? 'Recently' : $label;
		$limit = (!$limit) ? '10' : $limit;
		$break = (!$break) ? '<br />' : $break;
		$category = (!$category) ? '' : $category;
		$sortby = (!$sortby) ? '' : $sortby;
		$out = form(startTable('list').
			tr(tdcs(hed('List of Recent Articles',3),2) ) .
			tagRow('label', fInput('text','label',$label,'edit','','',20)) .
			tagRow('limit', fInput('text','limit',$limit,'edit','','',2)) .
			tagRow('break', fInput('text','break',$break,'edit','','',5)) .
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)) .
			tagRow('category', category_pop($category)) .
			tagRow('sort_by', sort_pop($sortby)) .
			tagRow('sort_direction', sortdir_pop($sortdir)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;	
	}

// -------------------------------------------------------------
	function tag_related_articles() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('label','limit','break','wraptag'));
		extract($invars);
		$label = (!$label) ? 'Related Articles' : $label;
		$limit = (!$limit) ? '10' : $limit;
		$break = (!$break) ? '<br />' : $break;
		
		$out = form(startTable('list').
			tr(tdcs(hed('List of Related Articles',3),2) ).
			tagRow('label', fInput('text','label',$label,'edit','','',20)).
			tagRow('limit', fInput('text','limit',$limit,'edit','','',2)).
			tagRow('break', fInput('text','break',$break,'edit','','',5)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;	
	}

// -------------------------------------------------------------
	function tag_recent_comments() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('label','limit','break','wraptag'));
		extract($invars);
		$label = (!$label) ? 'Recent Comments' : $label;
		$limit = (!$limit) ? '10' : $limit;
		$break = (!$break) ? '<br />' : $break;
		$out = form(startTable('list').
			tr(tdcs(hed('List of Recent Comments',3),2) ).
			tagRow('label', fInput('text','label',$label,'edit','','',20)).
			tagRow('limit', fInput('text','limit',$limit,'edit','','',2)).
			tagRow('break', fInput('text','break',$break,'edit','','',5)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;	
	}

// -------------------------------------------------------------
	function tag_output_form() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('form'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Output a form',3),2) ).
			tagRow('form', form_pop($form,'','form')).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;	
	}

// -------------------------------------------------------------
	function tag_popup() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('label','type','wraptag'));
		extract($invars);
		$typearr = array('c'=>'Category','s'=>'Section');
		$out = form(startTable('list').
			tr(tdcs(hed('Popup Category or Section List',3),2) ).
			tagRow('label', fInput('text','label',$label,'edit','','',25)).
			tagRow('type', selectInput('type',$typearr,$type)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;	
	}

// -------------------------------------------------------------
	function tag_password_protect() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('login','pass'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Password Protection',3),2) ).
			tagRow('login', fInput('text','login',$login,'edit','','',25)).
			tagRow('password', fInput('password','pass',$pass,'','','',25)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;	
	}

// -------------------------------------------------------------
	function tag_search_input() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('label','button','size','wraptag'));
		extract($invars);
		$button = (!$button) ? 'Search' : $button;
		$size = (!$size) ? '15' : $size;
		$label = (!$label) ? 'Search' : $label;
		$out = form(startTable('list').
			tr(tdcs(hed('Search Input Form',3),2) ).
			tagRow('label', fInput('text','label',$label,'edit','','',25)).
			tagRow('button_text', fInput('text','button',$button,'edit','','',25)).
			tagRow('input_size', fInput('text','size',$size,'edit','','',2)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_category1() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('link'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Category 1',3),2) ).
			tagRow('link_to_this_category', yesno_pop('link',$link)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_category2() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('link'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Category 2',3),2) ).
			tagRow('link_to_this_category', yesno_pop('link',$link)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_section() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('link'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Section',3),2) ).
			tagRow('link_to_this_section', yesno_pop('link',$link)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function author() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('author'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Author',3),2) ).
			tagRow('link_to_this_author', yesno_pop('link',$link)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_link_to_home() 
	{
		global $step,$endform,$name;
		$label = gps('label');
		$label = (!$label) ? 'Home' : $label;
		$out = form(startTable('list').
			tr(tdcs(hed('Link to Home Page',3),2) ).
			tagRow('link_text', fInput('text','label',$label,'edit','','',25)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tbd($name, $label)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_link_to_prev() 
	{
		global $step,$endform,$name;
		$label = gps('label');
		$label = (!$label) ? '<txp:prev_title />' : $label;
		$out = form(startTable('list').
			tr(tdcs(hed('Link to Previous Article',3),2) ).
			tagRow('link_text', fInput('text','label',$label,'edit','','',25)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tbd($name, $label)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_link_to_next() 
	{
		global $step,$endform,$name;
		$label = gps('label');
		$label = (!$label) ? '<txp:next_title />' : $label;
		$out = form(startTable('list').
			tr(tdcs(hed('Link to Next Article',3),2) ).
			tagRow('link_text', fInput('text','label',$label,'edit','','',25) ).
			$endform
		);
		$out .= ($step=='build') ? tdb(tbd($name, $label)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_feed_link() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('label','category','section','flavor','wraptag','limit'));
		extract($invars);

		$label = (!$label) ? 'XML' : $label;
		$flavarr = array('rss'=>'rss','atom'=>'atom');
		$out = form(startTable('list').
			tr(tdcs(hed('Link to XML Feed',3),2) ) .
			tagRow('label', fInput('text','label',$label,'edit','','',25)) .
			tagRow('limit', inputLimit($limit)) .
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			tagRow('flavour', selectInput('flavor',$flavarr,$flavor)) .
			tagRow('section', section_pop($section)) .
			tagRow('category', category_pop($section)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_link_feed_link() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('label','category','limit','wraptag'));
		extract($invars);
		$label = (!$label) ? 'XML' : $label;
		$out = form(startTable('list').
			tr(tdcs(hed('Link to XML Feed of Links',3),2) ) .
			tagRow('label',fInput('text','label',$label,'edit','','',25)) .
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			tagRow('category', link_category_pop($category)) .
			tagRow('limit', fInput('text','limit',$limit,'edit','','',2)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}


	// double tags eg: <txp:permlink> permanent link </txp:permlink>

	function tag_permlink()  { return tdb(tbd('permlink','* text or tag here *')); }

	function tag_paging_link() { return tdb(tbd('paging_link','* text or tag here *')); }

	function tag_newer()       { return tdb(tbd('newer','* text or tag here *')); }

	function tag_older()       { return tdb(tbd('older','* text or tag here *')); }

	// single tags eg: <txp:body /> 
	
	function tag_next_title()          { return tdb(tb('next_title')); }

	function tag_sitename()            { return tdb(tb('sitename')); }

	function tag_site_slogan()         { return tdb(tb('site_slogan')); }

	function tag_prev_title()          { return tdb(tb('prev_title')); }

	function tag_article_image()       { return tdb(tb('article_image')); }

	function tag_css()                 { return tdb(tb('css')); }

	function tag_body()                { return tdb(tb('body')); }

	function tag_excerpt()             { return tdb(tb('excerpt')); }

	function tag_title()               { return tdb(tb('title')); }

	function tag_link()                { return tdb(tb('link')); }

	function tag_linkdesctitle()       { return tdb(tb('linkdesctitle')); }

	function tag_link_description()    { return tdb(tb('link_description')); }

	function tag_link_text()           { return tdb(tb('link_text')); }

	function tag_posted()              { return tdb(tb('posted'));	}

	function tag_comments_invite()     { return tdb(tb('comments_invite')); }

	function tag_comment_permlink()    { return tdb(tbd('comment_permlink','#')); }

	function tag_comment_time()        { return tdb(tb('comment_time')); }

	function tag_message()             { return tdb(tb('message')); }

	function tag_comment_name()        { return tdb(tb('comment_name')); }

	function tag_comment_email_input() { return tdb(tb('comment_email_input')); }

	function tag_comment_message_input() { return tdb(tb('comment_message_input')); }

	function tag_comment_name_input()  { return tdb(tb('comment_name_input')); }

	function tag_comment_preview()     { return tdb(tb('comment_preview')); }

	function tag_comment_remember()    { return tdb(tb('comment_remember')); }

	function tag_comment_submit()      { return tdb(tb('comment_submit')); }

	function tag_comment_web_input()   { return tdb(tb('comment_web_input')); }

	function tag_search_result_title() { return tdb(tb('search_result_title')); }

	function tag_search_result_excerpt() { return tdb(tb('search_result_excerpt')); }

	function tag_search_result_url()   { return tdb(tb('search_result_url')); }

	function tag_search_result_date()  { return tdb(tb('search_result_date')); }
	
	function tag_lang()             { return tdb(tb('lang')); }
	function tag_breadcrumb()
	{
		global $step,$endform,$name;
		$invars = gpsa(array(
			'wraptag','label','sep','link'
		));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed('Breadcrumb',3),2) ) .
			tagRow('breadcrumb_separator',fInput('text','sep',$sep,'edit','','',4)).
			tagRow('label',fInput('text','label',$label,'edit','','',25)) .
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)). 
			tagRow('breadcrumb_linked'   , yesno_pop('link',$link)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
		
		" &#187; ";
	}

// -------------------------------------------------------------
	function tb($name,$atts=array(),$double = '') 
	{
		$attsout = '';
		foreach($atts as $a=>$b) if ($b) $attsout[] = ' '.$a.'="'.$b.'"';
		$atts_built = (is_array($attsout)) ? join('', $attsout) : '';		
		return '<txp:'.$name.$atts_built.' />';
	}

// -------------------------------------------------------------
	function tbd($name,$contents) 
	{
		return '<txp:'.$name.'>'.$contents.'</txp:'.$name.'>';
	}

//--------------------------------------------------------------
	function link_category_pop($name)
	{
		$arr = array('');
		$rs = getTree("root",'link');
		if ($rs) {
			return ' '.treeSelectInput("category",$rs,$name);
		}
		return 'no link categories created';
	}

//--------------------------------------------------------------
	function category_pop($name)
	{
		$arr = array('');
		$rs = getTree('root','article');
		if ($rs) {
			return ' '.treeSelectInput("category",$rs,$name);
		}
		return 'no categories created';
	}

//--------------------------------------------------------------
	function sort_pop($sortby)
	{
		$arr = array(
			'Posted' => 'Posted',
			'AuthorID' => 'Author',
			'LastMod' => 'Last Modification',
			'Title' => 'Title',
			'Section' => 'Section',
		);
		return ' '.selectInput("sortby",$arr,"$sortby");
	}
	
//--------------------------------------------------------------
	function sortdir_pop($sortdir)
	{
		$arr = array(
			'desc' => 'Descending',
			'asc' => 'Ascending'
		);
		return ' '.selectInput("sortdir",$arr,"$sortdir");
 	}

//--------------------------------------------------------------
	function yesno_pop($name,$val)
	{
		$arr = array(
			'' => '',
			'y' => gTxt('yes'),
			'n' => gTxt('no')
		);
		return ' '.selectInput($name,$arr,$val);
 	}

//--------------------------------------------------------------
	function css_pop($n)
	{
		$arr = array('');
		$rs = safe_rows("name", "txp_css", "name!='default' order by name");
		if ($rs) {
			foreach ($rs as $a) $arr[$a[0]] = $a[0];
			return ' '.selectInput("n",$arr,$n);
		}
		return false;
	}

//--------------------------------------------------------------
	function section_pop($name) 
	{
		$arr = array('');
		$rs = safe_rows("name", "txp_section", "name!='default' order by name");
		if ($rs) {
			foreach ($rs as $a) $arr[$a[0]] = $a[0];
			return ' '.selectInput("section", $arr,$name);
		}
		return 'no sections created';
	}

//--------------------------------------------------------------
	function author_pop($name) 
	{
		$arr = array('');
		$rs = safe_rows("name", "txp_users", "1 order by name");
		if ($rs) {
			foreach ($rs as $a) $arr[$a[0]] = $a[0];
			return ' '.selectInput("author", $arr,$name);
		}
		return 'no authors created';
	}

//--------------------------------------------------------------
	function form_pop($name,$type='',$formname) 
	{
		$arr = array('');
		
		$typeq = ($type) ? "type = '$type'" : '1';
		
		$rs = safe_rows("name", "txp_form", "$typeq order by name");
		if ($rs) {
			foreach ($rs as $a) $arr[$a[0]] = $a[0];
			return ' '.selectInput($formname, $arr,$name);
		}
		return 'no forms available';
	}

// -------------------------------------------------------------
	function key_input($name,$var) 
	{
		return '<textarea name="'.$name.
			'" style="width:120px;height:50px">'.$var.'</textarea>';
	}

// -------------------------------------------------------------
	function inputLimit($limit) 
	{
		return fInput('text','limit',$limit,'edit','','',2);
	}	

// -------------------------------------------------------------
	function inputMonth($month) 
	{
		return fInput('text','month',$month,'edit','','',7);
	}

// -------------------------------------------------------------
	function tdb($thing)
	{
		return hed('Tag:',3).text_area('tag','100','300',$thing);
	}

?>
</body>
</html>

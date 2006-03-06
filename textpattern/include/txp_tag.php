<?php
	if (!defined('txpinterface')) die('txpinterface is undefined.');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Textpattern: <?php echo gTxt('build'); ?></title>
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

$HeadURL$
$LastChangedRevision$

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
			tr(tdcs(hed(gTxt('page_article_hed'),3),2) ).
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
			tr(tdcs(hed(gTxt('tag_article_custom'),3),2) ) .
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
			tr(tdcs(hed(gTxt('tag_email'),3),2) ) .
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
			tr(tdcs(hed(gTxt('tag_page_title'),3),2) ).
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
		$invars = gpsa(array('form','category','limit','sort','wraptag','break','label','labeltag'));
		$sorts = array(''=>'','linksort'=>'Name',
				'date desc'=>'Date descending','date asc'=>'Date ascending', 'rand()'=>'Random');
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_linklist'),3),2) ).
			tagRow('form', form_pop($form,'link','form')).
			tagRow('category', link_category_pop($category)).
			tagRow('limit', fInput('text','limit',$limit,'edit','','',2)).
			tagRow('sort_by', selectInput("sort",$sorts,$sort)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			tagRow('break', fInput('text','break',$break,'edit','','',5)).
			tagRow('label', fInput('text','label',$label,'edit','','',20)).
			tagRow('labeltag', fInput('text','labeltag',$labeltag,'edit','','',5)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		echo $out;	
	}

// -------------------------------------------------------------
	function tag_category_list() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('form','category','wraptag','break','label','labeltag'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_category_list'),3),2) ).
			tagRow('category', category_pop($category)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			tagRow('break', fInput('text','break',$break,'edit','','',5)).
			tagRow('label', fInput('text','label',$label,'edit','','',20)).
			tagRow('labeltag', fInput('text','labeltag',$labeltag,'edit','','',5)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		echo $out;	
	}


// -------------------------------------------------------------
	function tag_recent_articles() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('label','limit','break','wraptag','category','sortby','sortdir','labeltag'));
		extract($invars);
		$label = (!$label) ? gTxt('recently') : $label;
		$limit = (!$limit) ? '10' : $limit;
		$break = (!$break) ? '<br />' : $break;
		$category = (!$category) ? '' : $category;
		$sortby = (!$sortby) ? '' : $sortby;
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_recent_articles'),3),2) ) .
			tagRow('label', fInput('text','label',$label,'edit','','',20)).
			tagRow('labeltag', fInput('text','labeltag',$labeltag,'edit','','',5)).
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
		$invars = gpsa(array('label','limit','break','wraptag','labeltag'));
		extract($invars);
		$label = (!$label) ? 'Related Articles' : $label;
		$limit = (!$limit) ? '10' : $limit;
		$break = (!$break) ? '<br />' : $break;
		
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_related_articles'),3),2) ).
			tagRow('label', fInput('text','label',$label,'edit','','',20)).
			tagRow('labeltag', fInput('text','labeltag',$labeltag,'edit','','',5)).
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
		$invars = gpsa(array('label','limit','break','wraptag','labeltag'));
		extract($invars);
		$label = (!$label) ? 'Recent Comments' : $label;
		$limit = (!$limit) ? '10' : $limit;
		$break = (!$break) ? '<br />' : $break;
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_recent_comments'),3),2) ).
			tagRow('label', fInput('text','label',$label,'edit','','',20)).
			tagRow('labeltag', fInput('text','labeltag',$labeltag,'edit','','',5)).
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
			tr(tdcs(hed(gTxt('tag_output_form'),3),2) ).
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
		$typearr = array('c'=>gTxt('Category'),'s'=>gTxt('Section'));
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_popup'),3),2) ).
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
			tr(tdcs(hed(gTxt('tag_password_protect'),3),2) ).
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
			tr(tdcs(hed(gTxt('tag_search_input'),3),2) ).
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
			tr(tdcs(hed(gTxt('tag_category1'),3),2) ).
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
			tr(tdcs(hed(gTxt('tag_category2'),3),2) ).
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
			tr(tdcs(hed(gTxt('Section'),3),2) ).
			tagRow('link_to_this_section', yesno_pop('link',$link)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_author() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('author','link'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_author'),3),2) ).
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
		$label = (!$label) ? gTxt('tag_home') : $label;
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_link_to_home'),3),2) ).
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
			tr(tdcs(hed(gTxt('tag_link_to_prev'),3),2) ).
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
			tr(tdcs(hed(gTxt('tag_link_to_next'),3),2) ).
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
			tr(tdcs(hed(gTxt('tag_feed_link'),3),2) ) .
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
			tr(tdcs(hed(gTxt('tag_link_feed_link'),3),2) ) .
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

	function tag_permlink()  { return tdb(tbd('permlink',gTxt('text_or_tag'))); }

	function tag_paging_link() { return tdb(tbd('paging_link',gTxt('text_or_tag'))); }

	function tag_newer()       { return tdb(tbd('newer',gTxt('text_or_tag'))); }

	function tag_older()       { return tdb(tbd('older',gTxt('text_or_tag'))); }

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
			tr(tdcs(hed(gTxt('tag_breadcrumb'),3),2) ) .
			tagRow('breadcrumb_separator',fInput('text','sep',$sep,'edit','','',4)).
			tagRow('label',fInput('text','label',$label,'edit','','',25)) .
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)). 
			tagRow('breadcrumb_linked'   , yesno_pop('link',$link)) .
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;		
	}

// -------------------------------------------------------------
	function tb($name,$atts=array(),$double = '') 
	{
		$attsout = '';
		foreach($atts as $a=>$b) if ($b) $attsout[] = ' '.$a.'="'.$b.'"';
		$atts_built = (is_array($attsout)) ? join('', $attsout) : '';

		return (!empty($double))?'<txp:'.$name.$atts_built.'>'.$double.'</txp:'.$name.'>':'<txp:'.$name.$atts_built.' />';
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
	function file_category_pop($name)
	{
		$arr = array('');
		$rs = getTree("root",'file');
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
			'Posted' => gTxt('tag_posted'),
			'AuthorID' => gTxt('tag_author'),
			'LastMod' => gTxt('last_modification'),
			'Title' => gTxt('tag_title'),
			'Section' => gTxt('Section'),
		);
		return ' '.selectInput("sortby",$arr,"$sortby");
	}
	
//--------------------------------------------------------------
	function sortdir_pop($sortdir)
	{
		$arr = array(
			'desc' => gTxt('descending'),
			'asc' => gTxt('ascending')
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
		$rs = safe_rows_start("name", "txp_css", "name!='default' order by name");
		if ($rs) {
			while ($a = nextRow($rs)){
				$v = array_shift($a);
				$arr[$v] = $v;
			}
			return ' '.selectInput("n",$arr,$n);
		}
		return false;
	}

//--------------------------------------------------------------
	function section_pop($name) 
	{
		$arr = array('');
		$rs = safe_rows_start("name", "txp_section", "name!='default' order by name");
		if ($rs) {
			while ($a = nextRow($rs)){
				$v = array_shift($a);
				$arr[$v] = $v;
			}
			return ' '.selectInput("section", $arr,$name);
		}
		return 'no sections created';
	}

//--------------------------------------------------------------
	function author_pop($name) 
	{
		$arr = array('');
		$rs = safe_rows_start("name", "txp_users", "1=1 order by name");
		if ($rs) {
			while ($a = nextRow($rs)){
				$v = array_shift($a);
				$arr[$v] = $v;
			}
			return ' '.selectInput("author", $arr,$name);
		}
		return 'no authors created';
	}

//--------------------------------------------------------------
	function form_pop($name,$type='',$formname) 
	{
		$arr = array('');
		
		$typeq = ($type) ? "type = '$type'" : '1=1';
		
		$rs = safe_rows_start("name", "txp_form", "$typeq order by name");
		if ($rs) {
			while ($a = nextRow($rs)){
				$v = array_shift($a);
				$arr[$v] = $v;
			}
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
		return hed(gTxt('tag').':',3).text_area('tag','100','300',$thing);
	}

// -------------------------------------------------------------
	function tag_image() 
	{
		global $img_dir;

		$invars = gpsa(array('id','type','h','w','ext','alt'));
		$i_pfx = (rhu == '/') ? '' : '/';
		$i_dir = (!$img_dir) ? $i_pfx.'images' : $i_pfx.$img_dir;
		extract($invars);
		switch ($type) {
			case 'textile': 
					$alt = ($alt) ? ' ('.$alt.')' : '';
					$thing='!'.rhu.$i_dir.'/'.$id.$ext.$alt.'!'; 
					break;
			case 'textpattern': 
					$thing = '<txp:image id="'.$id.$ext.'" />'; 
					break;
			case 'xhtml': 
					$alt = ($alt) ? ' alt="'.$alt.'"' : '';
					$thing = '<img src="'.rhu.$i_dir.'/'.
							$id.$ext.'"'.$alt.' style="height:'.$h.'px;width:'.$w.'px" />';
					break;
		}
		return tdb($thing);
	}
	
// 	

// -------------------------------------------------------------
// Needed by file downloads
// -------------------------------------------------------------

// -------------------------------------------------------------
	function tag_file() 
	{
		global $permlink_mode;
		$invars = gpsa(array('id','description','filename','type'));
		extract($invars);
		$url = ($permlink_mode == 'messy')?
			hu.'index.php?s=file_download&amp;id='.$id:
			hu.'file_download/'.$id;
		switch ($type) {
			case 'textile': 
				$description = ($description) ? ' ('.$description.')' : '';
				$thing='"'.$filename.'":'.$url; 
			break;

			case 'textpattern': $thing = '<txp:file_download_link id="'.$id.'"><txp:file_download_name /></txp:file_download_link>'; break;

			case 'xhtml': $thing = '<a href="'.$url.'" title="'.$filename.'">'.$filename.'</a>';
		}
		return tdb($thing);
	}

// -------------------------------------------------------------

	function tag_file_download() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('form','id'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_file_download'),3),2) ).
			tagRow('form', form_pop($form,'file','form')) .
			tagRow('id', fInput('text','id',$id,'edit','','',2)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}	

// -------------------------------------------------------------
	function tag_file_download_list() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('form','category','limit','sort','wraptag','break','label','labeltag'));
		$sorts = array(''=>'','filename'=>'Name',
				'downloads desc'=>'Download Count descending','downloads asc'=>'Download Count ascending', 'rand()'=>'Random');
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_file_download_list'),3),2) ).
			tagRow('form', form_pop($form,'file','form')).
			tagRow('category', file_category_pop($category)).
			tagRow('limit', fInput('text','limit',$limit,'edit','','',2)).
			tagRow('sort_by', selectInput("sort",$sorts,$sort)).
			tagRow('wraptag', fInput('text','wraptag',$wraptag,'edit','','',2)).
			tagRow('break', fInput('text','break',$break,'edit','','',5)).
			tagRow('label', fInput('text','label',$label,'edit','','',20)).
			tagRow('labeltag', fInput('text','labeltag',$labeltag,'edit','','',5)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		echo $out;	
	}

// -------------------------------------------------------------
	function tag_file_download_created() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('format'));
		extract($invars);
		$format = (!$format) ? '' : $format;
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_file_download_created').'<br />('.gTxt('archive_dateformat').' used if empty)' ,3),2) ).
			tagRow('format', fInput('text','format',$format,'edit','','',15)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_file_download_modified() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('format'));
		extract($invars);
		$format = (!$format) ? '' : $format;
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_file_download_modified').'<br />('.gTxt('archive_dateformat').' used if empty)',3),2) ).
			tagRow('format', fInput('text','format',$format,'edit','','',15)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

// -------------------------------------------------------------
	function tag_file_download_size() 
	{
		global $step,$endform,$name;
		$invars = gpsa(array('format','decimals'));
		$formats = array('b'=>'bytes','kb'=>'kilobytes','mb'=>'megabytes','gb'=>'gigabytes','tb'=>'terabytes','pb'=>'petabytes');
		extract($invars);
		$decimals = (!$decimals) ? '2' : $decimals;
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_file_download_size'),3),2) ).
			tagRow('format', selectInput('format',$formats,$format,1)).
			tagRow('decimals', fInput('text','decimals',$decimals,'edit','','',4)).
			$endform
		);
		$out .= ($step=='build') ? tdb(tb($name, $invars)) : '';
		return $out;
	}

	function tag_file_download_link()       
	{ 
		global $step,$endform,$name;
		$invars = gpsa(array('filename','id'));
		extract($invars);
		$out = form(startTable('list').
			tr(tdcs(hed(gTxt('tag_file_download_link'),3),2) ).
			tagRow('id', fInput('text','id',$id,'edit','','',4)).
			tagRow('filename', fInput('text','filename',$filename,'edit','','',15)).
			$endform
		);
		$out .= tdb(tb('file_download_link',$invars,gTxt('text_or_tag')));
		return $out;
	}

	function tag_file_download_id()  { return tdb(tb('file_download_id')); }

	function tag_file_download_name()  { return tdb(tb('file_download_name')); }

	function tag_file_download_downloads()  { return tdb(tb('file_download_downloads')); }
	
	function tag_file_download_category()  { return tdb(tb('file_download_category')); }
		
	function tag_file_download_description()  { return tdb(tb('file_download_description')); }
		

?>
</body>
</html>

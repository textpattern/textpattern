<?php
/*
            _______________________________________
   ________|                                       |________
   \       |              Textpattern              |       /
    \      |                                       |      /
    /      |_______________________________________|      \
   /___________)                               (___________\

	Copyright 2004 by Dean Allen 
	All rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 
*/

	define("txpath",$txpcfg['txpath']);

//	ERROR_REPORTING(E_ALL);
//	ini_set("display_errors","1");	

	include txpath.'/lib/txplib_db.php';
	include txpath.'/lib/txplib_html.php';
	include txpath.'/lib/txplib_forms.php';
	include txpath.'/lib/txplib_misc.php';
	include txpath.'/lib/admin_config.php';

	include txpath.'/publish/taghandlers.php';
	include txpath.'/publish/log.php';
	include txpath.'/publish/comment.php';

	ob_start();

    	// start the clock for runtime
	$microstart = getmicrotime();

		// check the size of the url request
	bombShelter();

		// get all prefs as an array
 	$prefs = get_prefs();
 	$prefs['path_from_root'] = (!$prefs['path_from_root']) ? '/' : $prefs['path_from_root'];

 		// add prefs to globals
	extract($prefs);

	if ($txpac['use_plugins']) {
		// get plugins, write to a temp file, include, then destroy
		plugins();
	}

	define("LANG",$language);

	$textarray = load_lang('en-gb');

	$s = (empty($s)) ? '' : $s;

	$pretext = pretext($s,$prefs);
	extract($pretext);

	if (gps('parentid') && gps('submit')) {
		saveComment();
	} elseif (gps('parentid') and $comments_mode==1) { // popup comments?
		exit(popComments(gps('parentid')));
	}

		if(!isset($nolog)) {
			if($logging=='all'): logit();
			elseif ($logging=='refer'): logit('refer');
			endif;
		}
/*
	if($send_lastmod) {
		$last = fetch("unix_timestamp(var)",'txp_prefs','name','lastmod');
		$last = gmdate("D, d M Y H:i:s \G\M\T",$last);
		ob_start();
		header("Last-Modified: $last");

		$hims = serverset('HTTP_IF_MODIFIED_SINCE');
		if ($hims == $last) {
			ob_start();
			header("HTTP/1.1 304 Not Modified");
			exit; 
		}
	}
*/
// -------------------------------------------------------------
	function preText($s,$prefs) 
	{
		extract($prefs);

		if(gps('rss')) {
			include txpath.'/publish/rss.php';
			exit(rss());
		}

		if(gps('atom')) {
			include txpath.'/publish/atom.php';
			exit(atom());
		}

		if (!$s) $s = gps('s'); 

		$id = gps('id');
		$id = (!$id && $url_mode) ? frompath() : $id;

		// hackish
		if(empty($id)) $GLOBALS['is_article_list'] = true;

		$out['id'] = $id;


		// what section are we in?	
		if ($s): $out['s'] = $s;
		elseif ($id): $out['s'] = fetch('Section','textpattern','ID',$id);
		else: $out['s'] = "default";
		endif;

		$s = $out['s'];

		$rs = safe_row("*", "txp_section", "name = '$s' limit 1");

		if ($rs) { 	// useful stuff from the database
			extract($rs);	
			$out['page']       = $page;		
			$out['css']        = $css;		
		}

		$out['c']              = gps('c');     // category?
		$out['q']              = gps('q');     // search query?
		$out['count']          = gps('count'); // pageby count? *deprecated*
		$out['pg']             = gps('pg');    // paging?
		$out['p']              = gps('p');     // image?

		if($id) { 		// check for next or previous article in the same section
			$Posted = fetch('Posted','textpattern','ID',$id);
			$thenext           = getNeighbour($Posted,$s,'>');
			$out['next_id']    = ($thenext) ? $thenext['ID'] : '';
			$out['next_title'] = ($thenext) ? $thenext['Title'] : '';
			$out['next_utitle']= ($thenext) ? $thenext['url_title'] : '';
			$theprev           = getNeighbour($Posted,$s,'<');
			$out['prev_id']    = ($theprev) ? $theprev['ID'] : '';
			$out['prev_title'] = ($theprev) ? $theprev['Title'] : '';
			$out['prev_utitle']= ($theprev) ? $theprev['url_title'] : '';
		}

		$out['path_from_root'] = $path_from_root;
		$out['pfr']            = $path_from_root;
		$out['url_mode']       = $url_mode;
		$out['sitename']       = $sitename;
		return $out; 
	}

//	textpattern() is the function that assembles a page, based on
//	the variables passed to it by pretext();

// -------------------------------------------------------------
	function textpattern() 
	{
		global $pretext,$microstart,$txpac;
		$segment = gps('segment');
		extract($pretext);

		$html = safe_field('user_html','txp_page',"name='$page'");
		if (!$html) exit('no page template specified for section '.$s);
		$html = parse($html);
		$html = parse($html);
		$html = (!$segment) ? $html : segmentPage($html);
		$html = ($txpac['allow_page_php_scripting']) ? evalString($html) : $html;

		header("Content-type: text/html; charset=utf-8");
		echo $html;

		$microdiff = (getmicrotime() - $microstart);
		echo n,comment('Runtime: '.substr($microdiff,0,6));
	}

// -------------------------------------------------------------
	function output_css($s='',$n='')
	{
		if ($n) {
			$cssname = $n;
		} elseif ($s) {
			$cssname = safe_field('css','txp_section',"name='$s'");
		}

		$css = safe_field('css','txp_css',"name='$cssname'");
		if ($css) echo base64_decode($css);
	}

//	article() is called when parse() finds a <txp:article /> tag.
//	If an $id has been established, we output a single article,
//	otherwise, output a list.

// -------------------------------------------------------------
	function article($atts)
	{
		global $pretext;
		return ($pretext['id']) ? doArticle($atts) : doArticles($atts);
	}

// -------------------------------------------------------------
	function doArticles($atts)
	{	
		global $pretext, $prefs,$txpcfg;
		extract($pretext);
		if (is_array($atts)) extract($atts);

		$form = (empty($form)) ? 'default' : $form;
		$form = (empty($listform)) ? $form : $listform;

		$limit = (empty($limit)) ? 10 : $limit;
		$count = (!$count) ? 0 : $count;  // deprecated in g1.17

		if($q) {
			include_once txpath.'/publish/search.php';
			return hed(gTxt('search_results'),2).search($q);
		}

			// might be a form preview, otherwise grab it from the db
		$Form = (isset($_POST['Form']))
		?	gps('Form')
		:	safe_field('Form','txp_form',"name='$form'");

		$q1a = "select *, unix_timestamp(Posted) as uPosted ";
		$q1b = "select count(*) ";

		$query = array(
			"from ".PFX."textpattern where status = 4",

			($s == 'default') 			// are we on the front page?
			?	filterFrontPage() : '',

			($s && $s!='default')		// section browse?
			?	"and section = '".doSlash($s)."'" : '',

			($c) 						// category browse?
			?	"and ((Category1='".doSlash($c)."') or (Category2='".doSlash($c)."'))" : ''
		);

		$q2b = " and Posted < now()";


		$total = getThing($q1b . join(' ',$query) . $q2b);
		$numPages = ceil($total/$limit);  
		$pg = (!$pg) ? 1 : $pg;
		$offset = ($pg - 1) * $limit;

		$q2a = " and Posted < now() order by Posted desc limit $offset,$limit";

			// send paging info to txp:newer and txp:older
		$pageout['pg']        = $pg;
		$pageout['numPages']  = $numPages;
		$pageout['s']         = $s;
		$pageout['c']         = $c;


		$GLOBALS['thispage'] = $pageout;

		$GLOBALS['is_article_list'] = true;

		$rs = getRows($q1a . join(' ',$query) . $q2a);

		if ($rs) {

			foreach($rs as $a) {
				extract($a);

				$com_count = safe_count('txp_discuss',"parentid=$ID and visible=1");

				$author = fetch('RealName','txp_users','name',$AuthorID);
				$author = (!$author) ? $AuthorID : $author; 

				$out['thisid']         = $ID;
				$out['posted']         = $uPosted;
				$out['if_comments']    = ($Annotate or $com_count) ? true : false;
				$out['comments_invite']= ($Annotate or $com_count)
										  ? formatCommentsInvite(
												$AnnotateInvite,$Section,$ID)
										  : '';
				$out['comments_count'] = $com_count;										  
				$out['mentions_link']  = formatMentionsLink($Section, $ID);
				$out['author']         = $author;
				$out['permlink']       = formatPermLink($ID,$Section);
				$out['body']           = parse($Body_html);
				$out['excerpt']        = $Excerpt;
				$out['title']          = $Title;
				$out['url_title']      = $url_title;
				$out['category1']      = $Category1;
				$out['category2']      = $Category2;
				$out['section']        = $Section;
				$out['keywords']       = $Keywords;
				$out['article_image']  = $Image;

				$GLOBALS['thisarticle'] = $out;

					// define the article form
				$article = ($override_form) 
				?	fetch('Form','txp_form','name',$override_form)
				:	$Form;

					// quick check for things not pulled from the db
				$article = doPermlink($article, $out['permlink'], $Title, $url_title);

				$articles[] = parse($article);

					// sending these to paging_link(); *deprecated in g1.17*
				$GLOBALS['uPosted'] = $uPosted;
				$GLOBALS['limit'] = $limit;

				unset($GLOBALS['thisarticle']);
			}

			return join('',$articles);
		}
	} 

// -------------------------------------------------------------
	function filterFrontPage() 
	{
		$rs = safe_column("name","txp_section", "on_frontpage != '1'");
		if ($rs) {
			foreach($rs as $name) $filters[] = "and Section != '$name'";	
			return join(' ',$filters);
		}
		return false;
	}


// -------------------------------------------------------------
	function doArticle($atts) 
	{
		global $pretext,$prefs;
		extract($prefs);
		extract($pretext);
		if (is_array($atts)) extract($atts);

		$preview = ps('preview');
		$parentid = ps('parentid');

		if (empty($form)) $form = 'default';

		$Form = fetch('Form','txp_form','name',$form);

		$rs = safe_row("*, unix_timestamp(Posted) as uPosted", 
				"textpattern", "ID='$id' and Status='4' limit 1");

		$GLOBALS['is_article_list'] = false;

		if ($rs) {
			extract($rs);

			$com_count = safe_count('txp_discuss',"parentid=$ID and visible=1");
			$author = fetch('RealName','txp_users','name',$AuthorID);
			$author = (!$author) ? $AuthorID : $author;

			$out['thisid']          = $id;
			$out['posted']          = $uPosted;
			$out['comments_invite'] = '';
			$out['mentions_link']   = '';
			$out['if_comments']     = ($Annotate or $com_count) ? true : false;
			$out['comments_count']  = $com_count;										  
			$out['author']          = $author;
			$out['permlink']        = formatPermLink($ID,$Section);
			$out['body']            = parse($Body_html);
			$out['excerpt']         = $Excerpt;
			$out['title']           = $Title;
			$out['url_title']       = $url_title;
			$out['category1']       = $Category1;
			$out['category2']       = $Category2;
			$out['section']         = $Section;
			$out['keywords']        = $Keywords;
			$out['article_image']   = $Image;

			$GLOBALS['thisarticle'] = $out;

				// define the article form
			$article = ($override_form) 
			?	fetch('Form','txp_form','name',$override_form)
			:	$Form;

				// quick check for things not pulled from the db
			$article = doPermlink($article, $out['permlink'], $Title, $url_title);

#			include txpath.'/publish/mention.php';
#			$article .= show_mentions();

			if ($preview && $parentid) {
				$article = discuss($parentid).$article;
			}

			if (($Annotate or $com_count) && !$preview) {
				if($use_comments) {
					$article .= discuss($ID);
				}
			}

			return parse($article);
		}
		return '';
	}

// -------------------------------------------------------------
	function article_custom($atts) 
	{
		global $pretext,$prefs;
		extract($prefs);
		extract($pretext);
		if (is_array($atts)) extract($atts);

		$GLOBALS['is_article_list'] = true;

		$form      = (empty($form))      ? 'default' : $form;
		$form      = (empty($listform))  ? $form     : $listform;
		$limit     = (empty($limit))     ? '10'      : $limit;
		$category  = (empty($category))  ? ''        : doSlash($category);
		$section   = (empty($section))   ? ''        : doSlash($section);
		$excerpted = (empty($excerpted)) ? ''        : $excerpted;
		$author    = (empty($author))    ? ''        : doSlash($author);
		$sortby    = (empty($sortby))    ? 'Posted'  : $sortby;
		$sortdir   = (empty($sortdir))   ? 'desc'    : $sortdir;
		$month     = (empty($month))     ? ''        : $month;
		$keywords  = (empty($keywords))  ? ''        : doSlash($keywords);
		$frontpage = (empty($frontpage)) ? ''        : filterFrontPage();

		$category  = (!$category)  ? '' : " and ((Category1='".$category.
											"') or (Category2='".$category."')) ";
		$section   = (!$section)   ? '' : " and Section = '$section'";
		$excerpted = ($excerpted=='y')  ? " and Excerpt !=''" : '';
		$author    = (!$author)    ? '' : " and AuthorID = '$author'";	
		$month     = (!$month)     ? '' : " and Posted like '{$month}%'";

		if ($keywords) {
			$keys = split(',',$keywords);
			foreach ($keys as $key) {
				$keyparts[] = " Keywords like '%".trim($key)."%'";
			}
			$keywords = " and (" . join(' or ',$keyparts) . ")"; 
		}


		$Form = fetch('Form','txp_form','name',$form);

		$rs = safe_rows(
			"*, unix_timestamp(Posted) as uPosted",
			"textpattern",
			"1 and Status=4 and Posted < now() ".
			$category . $section . $excerpted . $month . $author . $keywords . $frontpage .
			' order by ' . $sortby . ' ' . $sortdir . ' limit ' . $limit
		);

		if ($rs) {
			foreach($rs as $a) {
				extract($a);

				$com_count = safe_field('count(*)','txp_discuss',"parentid='$ID'");

				$author = fetch('RealName','txp_users',"name",$AuthorID);
				$author = (!$author) ? $AuthorID : $author; 

				$out['thisid']          = $ID;
				$out['posted']          = $uPosted;
				$out['if_comments']     = ($Annotate or $com_count) ? true : false;
				$out['comments_invite'] = ($Annotate or $com_count)
										  ? formatCommentsInvite(
												$AnnotateInvite,$Section,$ID)
										  : '';
				$out['comments_count']  = $com_count;										  
				$out['author']          = $author;
				$out['permlink']        = formatPermLink($ID,$Section);
				$out['body']            = parse($Body_html);
				$out['excerpt']         = $Excerpt;
				$out['title']           = $Title;
				$out['url_title']       = $url_title;
				$out['category1']       = $Category1;
				$out['category2']       = $Category2;
				$out['section']         = $Section;
				$out['keywords']        = $Keywords;
				$out['article_image']   = $Image;

				$GLOBALS['thisarticle'] = $out;

				$article = $Form;

					// quick check for things not pulled from the db
				$article = doPermlink($article, $out['permlink'], $Title, $url_title);

				$articles[] = parse($article);

					// sending these to paging_link();
				$GLOBALS['uPosted'] = $uPosted;
				$GLOBALS['limit'] = $limit;

				unset($GLOBALS['thisarticle']);			
			}
			return join('',$articles);
		}
	}

// -------------------------------------------------------------
	function getNeighbour($Posted, $s, $type) 
	{
		$q = array(
			"select ID, Title,url_title 
			from ".PFX."textpattern where Posted $type '$Posted'",
			($s!='' && $s!='default') ? "and Section = '$s'" : '',
			'and Status=4 and Posted < now() order by Posted',
			($type=='<') ? 'desc' : 'asc',
			'limit 1'
		);

		$out = getRow(join(' ',$q));		
		return (is_array($out)) ? $out : '';
	}

// -------------------------------------------------------------
	function doPermlink($text, $plink, $Title, $url_title) 
	{
		global $url_mode;
		$Title = ($url_title) ? $url_title : stripSpace($Title);
		$Title = ($url_mode) ? $Title : '';
		return preg_replace("/<(txp:permlink)>(.*)<\/\\1>/sU",
			"<a href=\"".$plink.$Title."\" title=\"".gTxt('permanent_link')."\">$2</a>",$text);
	}

// -------------------------------------------------------------
	function formatDate($uPosted,$pg='')
	{
		global $dateformat,$archive_dateformat,$timeoffset,$c,$id;

		if ($pg or $id or $c) { $dateformat = $archive_dateformat; }

			if($dateformat == "since") { $date = since($uPosted); } 
			else { $date = date("$dateformat",($uPosted + $timeoffset)); }
		return $date;
	}

// -------------------------------------------------------------
	function since($stamp) 
	{
		$diff = (time() - $stamp);
		if ($diff <= 3600) {
			$mins = round($diff / 60);
			$since = ($mins<=1) ? ($mins==1) ? "1 minute":"a few seconds":"$mins minutes";
		} else if (($diff <= 86400) && ($diff > 3600)) {
			$hours = round($diff / 3600);
			$since = ($hours <= 1) ? "1 hour" : "$hours hours";
		} else if ($diff >= 86400) {
			$days = round($diff / 86400);
			$since = ($days <= 1) ? "1 day" : "$days days";
		}
		return $since." ago";
	}

// -------------------------------------------------------------
	function formatCommentsInvite($AnnotateInvite,$Section,$ID) 
	{
		global $comments_mode, $url_mode, $pfr;
		$dc = safe_count('txp_discuss',"parentid='$ID' and visible=1");

		$ccount = ($dc) ?  '['.$dc.']' : '';

		if (!$comments_mode) {
			if ($url_mode) {
				$invite = '<a href="'.$pfr.$Section.'/'.$ID.'/#comment">'.
				$AnnotateInvite.'</a> '.$ccount;
			} else {
				$invite = '<a href="'.$pfr.'index.php?id='.$ID.'#comment">'.
				$AnnotateInvite.'</a> '.$ccount;
			}
		} else {
			$invite = "<a href=\"".$pfr."?parentid=$ID\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\">".$AnnotateInvite.'</a> '.$ccount;
		}
		return $invite;
	}

// -------------------------------------------------------------
	function formatMentionsLink($Section, $ID) 
	{
		global $comments_mode, $url_mode, $pfr;
		$mc = safe_count('txp_log_mention',"article_id='$ID'");
		if ($mc) {
			if ($url_mode) {
				return '<a href="'.$pfr.$Section.'/'.$ID.'/#mentions">'.
				gTxt('mentions').'</a> ['.$mc.']';
			} else {
				return '<a href="'.$pfr.'index.php?id='.$ID.'#mentions">'.
				gTxt('mentions').'</a> ['.$mc.']';
			}
		}
		return false;
	}

// -------------------------------------------------------------
	function formatPermLink($ID,$Section)
	{
		global $pfr,$url_mode;
		return ($url_mode==1) ? $pfr.$Section.'/'.$ID.'/' : $pfr.'index.php?id='.$ID;
	}

// -------------------------------------------------------------
	function rssPrep($text) 
	{
		return str_replace(array("&lt;","&gt;","\n"), array("<",">",""), $text);
	}

// -------------------------------------------------------------
	function lastMod() 
	{
		$last = safe_field("unix_timestamp(lastmod)", "txp_prefs", "1");
		return gmdate("D, d M Y H:i:s \G\M\T",$last);	
	}

// -------------------------------------------------------------
	function formatHref($pfr,$Section,$ID,$Linktext,$Title,$class="")
	{
		global $url_mode;
		$class = ($class) ? ' class="'.$class.'"' :'';
		return ($url_mode==1)
		?	'<a href="'.$pfr.$Section.'/'.$ID.'/'.stripSpace($Title).'"'.
				$class.'>'.$Linktext.'</a>'
		:	'<a href="'.$pfr.'index.php?id='.$ID.'"'.$class.'>'.$Linktext.'</a>';
	}


// -------------------------------------------------------------
	function input($type,$name,$val,$size='',$class='',$tab='',$chkd='') 
	{
		$o = array(
			'<input type="'.$type.'" name="'.$name.'" value="'.$val.'"',
			($size)  ? ' size="'.$size.'"'     : '',
			($class) ? ' class="'.$class.'"'	: '',
			($tab)	 ? ' tabindex="'.$tab.'"'	: '',
			($chkd)  ? ' checked="checked"'	: '',
			' />'.n
		);
		return join('',$o);
	}

// -------------------------------------------------------------
	function get_atts($text)
	{
		$pairs = explode('" ', $text);
		foreach	($pairs as $pair) {
			$pair =	explode("=",trim(str_replace('"', "", $pair)));
			if (count($pair)==1)
				$pair[1] = 1;
				$attributes[strtolower($pair[0])] = $pair[1];
		}
		return $attributes;
	}

// -------------------------------------------------------------
	function parse($text)
	{
		$f = '/<txp:(\S+)\b(.*)(?:(?<!br )(\/))?'.chr(62).'(?(3)|(.+)<\/txp:\1>)/sU';
		return preg_replace_callback($f, 'processTags', $text);

	}

// -------------------------------------------------------------
	function processTags($matches)
	{
		global $pretext;
		$tag = $matches[1];

		$atts = (isset($matches[2])) ? splat($matches[2]) : '';
		$thing = (isset($matches[4])) ? $matches[4] : '';

		if ($thing) {

			if (function_exists($tag)) return $tag($atts,$thing,$matches[0]);
			if (isset($pretext[$tag])) return $pretext[$tag];

		} else {

			if (function_exists($tag)) return $tag($atts);
			if (isset($pretext[$tag])) return $pretext[$tag];
		}

	}

// -------------------------------------------------------------
	function splat($attr)  // returns attributes as an array
	{
		$arr = array(); $atnm = ''; $mode = 0;

		while (strlen($attr) != 0) {
				$ok = 0;
				switch ($mode) {
					case 0: // name
						if (preg_match('/^([a-z]+)/i', $attr, $match)) {
							$atnm = $match[1]; $ok = $mode = 1;
							$attr = preg_replace('/^[a-z]+/i', '', $attr);
						}
					break;

					case 1: // =
						if (preg_match('/^\s*=\s*/', $attr)) {
							$ok = 1; $mode = 2;
							$attr = preg_replace('/^\s*=\s*/', '', $attr);
							break;
						}
						if (preg_match('/^\s+/', $attr)) {
							$ok = 1; $mode = 0;
							$arr[$atnm] = $atnm;
							$attr = preg_replace('/^\s+/', '', $attr);
						}
					break;

					case 2: // value
						if (preg_match('/^("[^"]*")(\s+|$)/', $attr, $match)) {
							$arr[$atnm] = str_replace('"','',$match[1]);
							$ok = 1; $mode = 0;
							$attr = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
							break;
						}
						if (preg_match("/^('[^']*')(\s+|$)/", $attr, $match)) {
							$arr[$atnm] = str_replace("'",'',$match[1]);
							$ok = 1; $mode = 0;
							$attr = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
							break;
						}
						if (preg_match("/^(\w+)(\s+|$)/", $attr, $match)) {
							$arr[$atnm] = $match[1];
							$ok = 1; $mode = 0;
							$attr = preg_replace("/^\w+(\s+|$)/", '', $attr);
						}
						break;
				}
				if ($ok == 0) {
					$attr = preg_replace('/^\S*\s*/', '', $attr);
					$mode = 0;
				}
		}
		if ($mode == 1) $arr[$atnm] = $atnm;
		return $arr;
    }

// -------------------------------------------------------------
	function frompath() // Divine what the current article id is, based on the URL 
	{
		$pinfo = serverSet('PATH_INFO');

		if ($pinfo) {
			$frompath = explode('/',$pinfo);

			return (!empty($frompath[1])) ? $frompath[1] : '';
		}
		return '';
	}

// -------------------------------------------------------------
//	Txp plugins are stored in the database. The idea is to minimize reliance
//	on ftp to install and work with plugins, and to rise above the tower of 
//	Babel that is file permissions.

// -------------------------------------------------------------
	function plugins() 
	{
		$rs = safe_column("code", "txp_plugin", "status=1");
		if ($rs) {
			foreach($rs as $a) { 
				$plugins[] = $a; 
			}

			$out = join(n.n,$plugins);
			eval($out);
		}
	}

// -------------------------------------------------------------
	function bombShelter() // protection from those who'd bomb the site by GET
	{
		$in = serverset('REQUEST_URI');
		if (strlen($in) > 200) exit('Nice try.');
	}

// -------------------------------------------------------------
	function segmentPage($text)
	{
		global $pfr,$page;

		$astyle = 'style="font-size:11px;color:white;background:red;font-family:verdana"';
		$dstyle = 'style="border:1px solid red;"';

		return preg_replace("/(<div id=\")(?!container)(\w+)(\".*)(>)/U",
			"$1$2$3 ".$dstyle."$4\n<p><a href=\"".$pfr.
			"textpattern/?event=page&#38;step=div_edit&#38;name=".
			$page."&#38;div=$2\" ".$astyle.">&nbsp;edit&nbsp;</a></p>",$text);
	}

// -------------------------------------------------------------
	function evalString($html) 
	{
		if (strpos($html, chr(60).'?php') !== false) {
			$html = eval(' ?'.chr(62).$html.chr(60).'?php ');
		}
		return $html;	
	}
?>

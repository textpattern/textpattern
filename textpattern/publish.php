<?php
/*
            _______________________________________
   ________|                                       |_________
  \        |                                       |        /
   \       |              Textpattern              |       /
    \      |                                       |      /
    /      |_______________________________________|      \
   /___________)                               (___________\

	Copyright 2005 by Dean Allen 
	All rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpath'))
		define("txpath", dirname(__FILE__));
	if (!defined("txpinterface"))	
		die('If you just updated and expect to see your site here, please also update the files in your main installation directory.'.
			' (Otherwise note that publish.php cannot be called directly.)');


	include_once txpath.'/lib/constants.php';
	include_once txpath.'/lib/txplib_db.php';
	include_once txpath.'/lib/txplib_html.php';
	include_once txpath.'/lib/txplib_forms.php';
	include_once txpath.'/lib/txplib_misc.php';
	include_once txpath.'/lib/admin_config.php';

	include_once txpath.'/publish/taghandlers.php';
	include_once txpath.'/publish/log.php';
	include_once txpath.'/publish/comment.php';

//	set_error_handler('myErrorHandler');

	ob_start();

		// useful for clean urls with error-handlers
	txp_status_header('200 OK');

    	// start the clock for runtime
	$microstart = getmicrotime();

		// check the size of the url request
	bombShelter();

		// get all prefs as an array
 	$prefs = get_prefs();

 		// add prefs to globals
	extract($prefs);

		// set a higher error level during initialization
	set_error_level(@$production_status == 'live' ? 'testing' : @$production_status);

		// use the current URL path if $siteurl is unknown
	if (empty($siteurl))
		$prefs['siteurl'] = $siteurl = $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
	
	if (empty($path_to_site))
		updateSitePath(dirname(dirname(__FILE__)));

		// v1.0: this should be the definitive http address of the site	
	if (!defined('hu'))
		define("hu",'http://'.$siteurl.'/');
	
		// v1.0 experimental relative url global
	if (!defined('rhu'))
		define("rhu",preg_replace("/http:\/\/.+(\/.*)\/?$/U","$1",hu));

		// 1.0: a new $here variable in the top-level index.php 
		// should let us know the server path to the live site
		// let's save it to prefs
	if (isset($here) and $path_to_site != $here) updateSitePath($here);

		// 1.0 removed $doc_root variable from config, but we'll
		// leave it here for a bit until plugins catch up
	$txpcfg['doc_root'] = @$_SERVER['DOCUMENT_ROOT'];
	// work around the IIS lobotomy
	if (empty($txpcfg['doc_root']))
		$txpcfg['doc_root'] = @$_SERVER['PATH_TRANSLATED'];

	if (!defined('LANG'))
		define("LANG",$language);
	if (!empty($locale)) setlocale(LC_ALL, $locale);

		//Initialize the current user
	$txp_user = NULL;

		//i18n: $textarray = load_lang('en-gb');
	$textarray = load_lang(LANG);

		// here come the plugins
	if ($use_plugins) load_plugins();

		// this step deprecated as of 1.0 : really only useful with old-style
		// section placeholders, which passed $s='section_name'
	$s = (empty($s)) ? '' : $s;

	$pretext = !isset($pretext) ? array() : $pretext;
	$pretext = array_merge($pretext, pretext($s,$prefs));
	extract($pretext);
	
	// Now that everything is initialized, we can crank down error reporting
	set_error_level($production_status);
	
	if (gps('parentid') && gps('submit')) {
		saveComment();
	} elseif (gps('parentid') and $comments_mode==1) { // popup comments?
		header("Content-type: text/html; charset=utf-8");
		exit(popComments(gps('parentid')));
	}

	// we are dealing with a download
	if (@$s == 'file_download') {
		if (!isset($file_error)) {

				$fullpath = build_file_path($file_base_path,$filename);

				if (is_file($fullpath)) {

					// discard any error php messages
					ob_clean();
					$filesize = filesize($fullpath); $sent = 0;
					header('Content-Description: File Download');
					header('Content-Type: application/octet-stream');
					header('Content-Length: ' . $filesize);
					header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
					@ini_set("zlib.output_compression", "Off");
					@set_time_limit(0);
					@ignore_user_abort(true);
					if ($file = fopen($fullpath, 'rb')) {
						while(!feof($file) and (connection_status()==0)) {
							echo fread($file, 1024*64); $sent+=(1024*64);
							ob_flush();
							flush();
						}
						fclose($file);
						// record download
						if ((connection_status()==0) and !connection_aborted() ) {
							safe_update("txp_file", "downloads=downloads+1", "id='".intval($id)."'");
						} else {
							$pretext['request_uri'] .= "#aborted-at-".floor($sent*100/$filesize)."%";
							logit();
						}
					}      				
				} else {
					$file_error = 404;
				}
		}

		// deal with error
		if (isset($file_error)) {
			switch($file_error) {
			case 403:
				header('HTTP/1.0 403 Forbidden');
				break;
			case 404:
				header('HTTP/1.0 404 File Not Found');
				break;
			default:
				header('HTTP/1.0 500 Internal Server Error');
				break;
			}
		}
		
		// download done
		exit(0);
	}
	

	if(!isset($nolog) && $status != '404') {
		if($logging == 'refer') { 
			logit('refer'); 
		} elseif ($logging == 'all') {
			logit();
		}
	}

/* FIXME: have to convert mysql-timestamps to unixtimestamps first. Are timezones ok?
	if($send_lastmod) {
		$last = gmdate("D, d M Y H:i:s \G\M\T",$lastmod);
		header("Last-Modified: $last");

		$hims = serverset('HTTP_IF_MODIFIED_SINCE');
		if ($hims == $last) {
			header("HTTP/1.1 304 Not Modified");
			exit; 
		}
	}
*/
// -------------------------------------------------------------
	function preText($s,$prefs) 
	{
		extract($prefs);

		callback_event('pretext');

		if(gps('rss')) {
			include txpath.'/publish/rss.php';
			exit(rss());
		}

		if(gps('atom')) {
			include txpath.'/publish/atom.php';
			exit(atom());
		}
			// set messy variables
		$out =  makeOut('id','s','c','q','pg','p','month','author');

			// some useful vars for taghandlers, plugins
		$out['request_uri'] = serverSet('REQUEST_URI');
		$out['qs'] = serverSet('QUERY_STRING');
			// IIS fix 
		if (!$out['request_uri'] and serverSet('SCRIPT_NAME'))
			$out['request_uri'] = serverSet('SCRIPT_NAME').( (serverSet('QUERY_STRING')) ? '?'.serverSet('QUERY_STRING') : '');
			// another IIS fix
		if (!$out['request_uri'] and serverSet('argv'))
		{
			$argv = serverSet('argv');
			$out['request_uri'] = @substr($argv[0], strpos($argv[0], ';' + 1));
		}

			// define the useable url, minus any subdirectories.
			// this is pretty fugly, if anyone wants to have a go at it - dean
		$out['subpath'] = $subpath = preg_quote(preg_replace("/http:\/\/.*(\/.*)/Ui","$1",hu),"/");
		$out['req'] = $req = preg_replace("/^$subpath/i","/",$out['request_uri']);

		$is_404 = 0;

			// if messy vars exist, bypass url parsing
		if (!$out['id'] && !$out['s'] && !(txpinterface=='css')) {

			extract(chopUrl($req));
	
				//first we sniff out some of the preset url schemes
			if (!empty($u1)) {
	
				switch($u1) {
	
					case 'atom':
						include txpath.'/publish/atom.php'; exit(atom());

					case 'rss':
						include txpath.'/publish/rss.php'; exit(rss());

					// urldecode(strtolower(urlencode())) looks ugly but is the only way to
					// make it multibyte-safe without breaking backwards-compatibility
					case urldecode(strtolower(urlencode(gTxt('section')))):
						$out['s'] = (ckEx('section',$u2)) ? $u2 : ''; $is_404 = empty($out['s']); break;
	
					case urldecode(strtolower(urlencode(gTxt('category')))):
						$out['c'] = (ckEx('category',$u2)) ? $u2 : ''; $is_404 = empty($out['c']); break;
	
					case urldecode(strtolower(urlencode(gTxt('author')))):
						$out['author'] = (!empty($u2)) ? $u2 : ''; break;
						// AuthorID gets resolved from Name further down
	
					case urldecode(strtolower(urlencode(gTxt('file_download')))):
						$out['s'] = 'file_download';
						$out['id'] = (!empty($u2)) ? $u2 : ''; break;
					
					case 'p':
						$out['p'] = (is_numeric($u2)) ? $u2 : ''; break;
					
					default:
						// then see if the prefs-defined permlink scheme is usable
						switch ($permlink_mode) {
			
							case 'section_id_title': 
								if (empty($u2)) {
									$out['s'] = (ckEx('section',$u1)) ? $u1 : '';
									$is_404 = empty($out['s']);
								}
								else {
									$rs = ckExID($u2);
									$out['s'] = @$rs['Section'];
									$out['id'] = @$rs['ID'];
									$is_404 = (empty($out['s']) or empty($out['id']));
								}
							break;
			
							case 'year_month_day_title':
								if (empty($u2)) {
									$out['s'] = (ckEx('section',$u1)) ? $u1 : '';
									$is_404 = empty($out['s']);
								}
								elseif (empty($u4)){
									$month = "$u1-$u2";
									if (!empty($u3)) $month.= "-$u3";
									if (preg_match('/\d+-\d+(?:-\d+)?/', $month)) {
										$out['month'] = $month;
										$out['s'] = 'default';
									}
									else {
										$is_404 = 1;
									}
								}else{
									$when = "$u1-$u2-$u3";
									$rs = lookupByDateTitle($when,$u4);
									$out['id'] = (!empty($rs['ID'])) ? $rs['ID'] : '';
									$out['s'] = (!empty($rs['Section'])) ? $rs['Section'] : '';
									$is_404 = (empty($out['s']) or empty($out['id']));
								}
							break;

							case 'section_title': 
								if (empty($u2)) {
									$out['s'] = (ckEx('section',$u1)) ? $u1 : '';
									$is_404 = empty($out['s']);
								}
								else {
									$rs = lookupByTitleSection($u2,$u1);
									$out['id'] = @$rs['ID'];
									$out['s'] = @$rs['Section'];
									$is_404 = (empty($out['s']) or empty($out['id']));
								}
							break;
							
							case 'title_only': 
								$rs = lookupByTitle($u1);
								$out['id'] = @$rs['ID'];
								$out['s'] = (empty($rs['Section']) ? ckEx('section', $u1) : 
										$rs['Section']);
								$is_404 = empty($out['s']);
							break;

							case 'id_title': 		
								if (is_numeric($u1) && ckExID($u1))
								{
									$rs = lookupByID($u1);
									$out['id'] = (!empty($rs['ID'])) ? $rs['ID'] : '';
									$out['s'] = (!empty($rs['Section'])) ? $rs['Section'] : '';
									$is_404 = (empty($out['s']) or empty($out['id']));
								}else{
									# We don't want to miss the /section/ pages
									$out['s']= ckEx('section',$u1)? $u1 : '';
									$is_404 = empty($out['s']);
								}
							break;
			
						}
				}
			} else {
				$out['s'] = 'default';
			}
		}
		else {
			// Messy mode, but prevent to get the id for file_downloads
			if ($out['id'] && !$out['s'])
				$out['s'] = safe_field('section', 'textpattern', "ID='".doSlash($out['id'])."'");
		}

		// Resolve AuthorID from Authorname
		if ($out['author'])
			$out['author'] = safe_field('name','txp_users',"RealName like '".doSlash($out['author'])."'");

		// Stats: found or not
		$out['status'] = ($is_404 ? '404' : '200');
		
		if ($out['s'] == 'file_download') {
			// get id of potential filename
			if (!is_numeric($out['id'])) {
				$rs = safe_row("*", "txp_file", "filename='".doSlash($out['id'])."'");
			} else {
				$rs = safe_row("*", "txp_file", "id='".intval($out['id'])."'");
			}

			$out = ($rs)? array_merge($out, $rs) : array('s'=>'file_download','file_error'=> 404);
			return $out;
		}
		
		if (!$is_404)
			$out['s'] = (empty($out['s'])) ? 'default' : $out['s'];
		$s = $out['s'];
		$id = $out['id'];

		// hackish
		global $is_article_list;
		if(empty($id)) $is_article_list = true;

			// by this point we should know the section, so grab its page and css
		$rs = safe_row("*", "txp_section", "name = '".doSlash($s)."' limit 1");
		$out['page'] = @$rs['page'];		
// Remove the below line if nothing breaks
// 		$out['css']  = @$rs['css'];

		if(is_numeric($id)) {
			$idrs = safe_row("Posted, AuthorID, Keywords","textpattern","ID=".doSlash($id));
			extract($idrs);

			if ($np = getNextPrev($id, $Posted, $s))
				$out = array_merge($out, $np);

			$out['id_keywords'] = $Keywords; 
			$out['id_author']   = get_author_name($AuthorID); 
		}

		$out['path_from_root'] = $path_from_root; // these are deprecated as of 1.0
		$out['pfr']            = $path_from_root; // leaving them here for plugin compat

		$out['path_to_site']   = $path_to_site;
		$out['permlink_mode']  = $permlink_mode;
		$out['sitename']       = htmlspecialchars($sitename);

		return $out; 

	}

//	textpattern() is the function that assembles a page, based on
//	the variables passed to it by pretext();

// -------------------------------------------------------------
	function textpattern() 
	{
		global $pretext,$microstart,$prefs,$qcount,$qtime,$production_status,$txptrace,$siteurl;

		callback_event('textpattern');

		if ($pretext['status'] == '404')
			txp_die(gTxt('404_not_found'), '404');

		$html = safe_field('user_html','txp_page',"name='".doSlash($pretext['page'])."'");
		if (!$html) 
			txp_die(gTxt('unknown_section'), '404');

		trace_add('['.gTxt('page').': '.$pretext['page'].']');
		set_error_handler("tagErrorHandler");
		$pretext['secondpass'] = false;
		$html = parse($html);
		$pretext['secondpass'] = true;
		trace_add('[ --- '.gTxt('secondpass').' --- ]');
		$html = parse($html); // the function so nice, he ran it twice
		restore_error_handler();
		$html = ($prefs['allow_page_php_scripting']) ? evalString($html) : $html;

		header("Content-type: text/html; charset=utf-8");
		echo $html;

		if (in_array($production_status, array('debug', 'testing'))) {
			$microdiff = (getmicrotime() - $microstart);
			echo n,comment('Runtime:    '.substr($microdiff,0,6));
			echo n,comment('Query time: '.$qtime);
			echo n,comment('Queries: '.$qcount);
			echo maxMemUsage('end of textpattern()',1);
			if (!empty($txptrace) and is_array($txptrace))
				echo n, comment('txp tag trace: '.n.join(n, $txptrace).n);
		}
	}

// -------------------------------------------------------------
	function output_css($s='',$n='')
	{
		if ($n) {
			$cssname = $n;
		} elseif ($s) {
			$cssname = safe_field('css','txp_section',"name='".doSlash($s)."'");
		}

		$css = safe_field('css','txp_css',"name='".doSlash($cssname)."'");
		if ($css) echo base64_decode($css);
	}

//	article() is called when parse() finds a <txp:article /> tag.
//	If an $id has been established, we output a single article,
//	otherwise, output a list.

// -------------------------------------------------------------
	function article($atts)
	{		
		return parseArticles($atts);
	}

// -------------------------------------------------------------
	function doArticles($atts, $iscustom)
	{	
		global $pretext, $prefs, $txpcfg;
		extract($pretext);
		extract($prefs);
		//getting attributes
		$theAtts = lAtts(array(
			'form'      => 'default',
			'limit'     => 10,
			'pageby'    => '',
			'category'  => '',
			'section'   => '',
			'excerpted' => '',
			'author'    => '',
			'sortby'    => '',
			'sortdir'   => 'desc',
			'month'     => '',
			'keywords'  => '',
			'frontpage' => '',
			'id'        => '',
			'time'      => 'past',
			'status'    => '4',
			'pgonly'    => 0,
			'searchall' => 1,
			'searchsticky' => 0,
			'allowoverride' => (!$q and !$iscustom),
			'offset'    => 0,
		),$atts);		
		
		// if an article ID is specified, treat it as a custom list
		$iscustom = (!empty($theAtts['id'])) ? true : $iscustom;

		//for the txp:article tag, some attributes are taken from globals;
		//override them before extract
		if (!$iscustom)
		{
			$theAtts['category'] = ($c)? $c : '';
			$theAtts['section'] = ($s && $s!='default')? $s : '';
			$theAtts['author'] = (!empty($author)? $author: '');
			$theAtts['month'] = (!empty($month)? $month: '');
			$theAtts['frontpage'] = ($s && $s=='default')? true: false;
			$theAtts['excerpted'] = '';			
		}
		extract($theAtts);
		
		$pageby = (empty($pageby) ? $limit : $pageby);

		// treat sticky articles differently wrt search filtering, etc
		if (!is_numeric($status))
			$status = getStatusNum($status);
		$issticky = ($status == 5);
			
		//give control to search, if necesary
		if($q && !$iscustom && !$issticky) {
			include_once txpath.'/publish/search.php';
			$s_filter = ($searchall ? filterSearch() : '');
			$q = doSlash($q);
			$match = ", match (Title,Body) against ('$q') as score";
			$search = " and (Title rlike '$q' or Body rlike '$q') $s_filter";

			// searchall=0 can be used to show search results for the current section only
			if ($searchall) $section = '';
			if (!$sortby) $sortby='score';
		}
		else {
			$match = $search = '';
			if (!$sortby) $sortby='Posted';
		}

		//Building query parts
		$frontpage = ($frontpage and !$q) ? filterFrontPage() : '';		
		$category  = (!$category)  ? '' : " and ((Category1='".doslash($category)."') or (Category2='".doSlash($category)."')) ";
		$section   = (!$section)   ? '' : " and Section = '".doslash($section)."'";
		$excerpted = ($excerpted=='y')  ? " and Excerpt !=''" : '';
		$author    = (!$author)    ? '' : " and AuthorID = '".doslash($author)."'";
		$month     = (!$month)     ? '' : " and Posted like '".doSlash($month)."%'";
		$id        = (!$id)        ? '' : " and ID = '".intval($id)."'";
		switch ($time) {
			case 'any':
				$time = ""; break;
			case 'future':
				$time = " and Posted > now()"; break;
			default:
				$time = " and Posted < now()";
		}
		if (!is_numeric($status))
			$status = getStatusNum($status);
			
		$custom = '';

		// trying custom fields here
		$customFields = getCustomFields();
		
		if ($customFields) {
			foreach($customFields as $cField) {
				if (isset($atts[$cField]))
					$customPairs[$cField] = $atts[$cField];
			}
			if(!empty($customPairs)) {
				$custom =  buildCustomSql($customFields,$customPairs);
			} else $custom = '';
		}

		//Allow keywords for no-custom articles. That tagging mode, you know
		if ($keywords) {
			$keys = split(',',$keywords);
			foreach ($keys as $key) {
				$keyparts[] = " Keywords like '%".doSlash(trim($key))."%'";
			}
			$keywords = " and (" . join(' or ',$keyparts) . ")"; 
		}

		if ($q and $searchsticky)
			$statusq = " and Status >= '4'";
		elseif ($id)
			$statusq = " and Status >= '4'";
		else
			$statusq = " and Status='".doSlash($status)."'";

		$where = "1" . $statusq. $time.
			$search . $id . $category . $section . $excerpted . $month . $author . $keywords . $custom . $frontpage;

		//do not paginate if we are on a custom list
		if (!$iscustom and !$issticky)
		{
			$grand_total = safe_count('textpattern',$where);
			$total = $grand_total - $offset;
			$numPages = ceil($total/$pageby);  
			$pg = (!$pg) ? 1 : $pg;
			$pgoffset = $offset + (($pg - 1) * $pageby).', ';	
			// send paging info to txp:newer and txp:older
			$pageout['pg']       = $pg;
			$pageout['numPages'] = $numPages;
			$pageout['s']        = $s;
			$pageout['c']        = $c;
			$pageout['grand_total'] = $grand_total;
			$pageout['total']    = $total;

			global $thispage;	
			$thispage = $pageout;
			if ($pgonly)
				return;
		}else{
			$pgoffset = $offset . ', ';
		}

		$rs = safe_rows_start("*, unix_timestamp(Posted) as uPosted".$match, 'textpattern', 
		$where. ' order by ' . doslash($sortby) . ' ' . doSlash($sortdir) . ' limit ' . doSlash($pgoffset.$limit));
		// alternative form override for search or list
		if ($q and !$iscustom and !$issticky)
			$form = gAtt($atts, 'searchform', 'search_results');
		else
			$form = gAtt($atts, 'listform', $form);         
		// might be a form preview, otherwise grab it from the db
		$form = (isset($_POST['Form']))
		?	gps('Form')
		:	fetch_form($form);

		if ($rs) {
			$count = 0;
			
			$articles = array();
			while($a = nextRow($rs)) {
				++$count;
				populateArticleData($a);
				global $thisarticle, $uPosted, $limit;
				$thisarticle['is_first'] = ($count == 1);
				$thisarticle['is_last'] = ($count == numRows($rs));
				// define the article form
				$article = ($allowoverride and $a['override_form']) 
				?	fetch_form($a['override_form'])
				:	$form;

				$articles[] = parse($article);
				
				// sending these to paging_link(); Required?
				$uPosted = $a['uPosted'];
				$limit = $limit;

				unset($GLOBALS['thisarticle']);
				unset($GLOBALS['theseatts']);//Required?				
			}
			
			return join('',$articles);
		}
	}

// -------------------------------------------------------------
	function filterFrontPage() 
	{
        static $filterFrontPage;

        if (isset($filterFrontPage)) return $filterFrontPage;

		$rs = safe_column("name","txp_section", "on_frontpage != '1'");
		if ($rs) {
			foreach($rs as $name) $filters[] = "and Section != '".doSlash($name)."'";	
			$filterFrontPage = join(' ',$filters);
            return $filterFrontPage;
		}
        $filterFrontPage = false;
		return $filterFrontPage;
	}


// -------------------------------------------------------------
	function doArticle($atts) 
	{
		global $pretext,$prefs;
		extract($prefs);
		extract($pretext);

		$preview = ps('preview');
		$parentid = ps('parentid');

		extract(lAtts(array(
			'form' => 'default',
			'status' => '',
		),$atts));		

		if ($status and !is_numeric($status))
			$status = getStatusNum($status);

		$q_status = ($status ? "and Status='".doSlash($status)."'" : 'and Status in (4,5)');

		$rs = safe_row("*, unix_timestamp(Posted) as uPosted", 
				"textpattern", "ID='".intval($id)."' $q_status limit 1");

		if ($rs) {
			extract($rs);
			populateArticleData($rs);
			global $thisarticle;
			$thisarticle['is_first'] = 1;
			$thisarticle['is_last'] = 1;

			// define the article form
			$article = fetch_form(($override_form) ? $override_form : $form);

			if ($preview && $parentid) {
				$article = '<txp:comments_preview bc="1" id="'.$parentid.'" />'.$article;
			}

			$article = parse($article);

			if ($use_comments and $comments_auto_append) {
				$f = fetch_form('comments_display');
				$article .= parse($f);
			}

			
			unset($GLOBALS['thisarticle']);	

			return $article;
		}
}	

// -------------------------------------------------------------
	function article_custom($atts)
	{
		return parseArticles($atts, '1');
	}

// -------------------------------------------------------------
	function parseArticles($atts, $iscustom = '')
	{
		global $pretext, $is_article_list;
		$old_ial = $is_article_list;
		$is_article_list = ($pretext['id'] && !$iscustom)? false : true;
		$r = ($is_article_list)? doArticles($atts, $iscustom) : doArticle($atts);
		$is_article_list = $old_ial;

		return $r;
	}

// -------------------------------------------------------------
// Keep all the article tag-related values in one place,
// in order to do easy bugfix and easily the addition of
// new article tags.
	function populateArticleData($rs)
	{
		extract($rs);

		trace_add("[".gTxt('Article')." $ID]");
		$out['thisid']          = $ID;
		$out['posted']          = $uPosted;
		$out['annotate']        = $Annotate;
		$out['comments_invite'] = $AnnotateInvite;
		$out['authorid']        = $AuthorID;
		$out['title']           = $Title;
		$out['url_title']       = $url_title;
		$out['category1']       = $Category1;
		$out['category2']       = $Category2;
		$out['section']         = $Section;
		$out['keywords']        = $Keywords;
		$out['article_image']   = $Image;
		$out['comments_count']  = $comments_count;


		$custom = getCustomFields();
		if ($custom) {
			foreach ($custom as $i => $name)
				$out[$name] = $rs['custom_' . $i];
		}
			
		global $thisarticle, $is_article_body;
		$thisarticle = $out;
		$is_article_body = 1;		
		$thisarticle['body'] = parse($Body_html);
		$thisarticle['excerpt'] = parse($Excerpt_html);
		$is_article_body = 0;

	}

// -------------------------------------------------------------
	function getNeighbour($Posted, $s, $type) 
	{
		$type = ($type == '>') ? '>' : '<';
		$q = array(
			"select ID, Title, url_title, unix_timestamp(Posted) as uposted
			from ".PFX."textpattern where Posted $type '".doSlash($Posted)."'",
			($s!='' && $s!='default') ? "and Section = '".doSlash($s)."'" : filterFrontPage(),
			'and Status=4 and Posted < now() order by Posted',
			($type=='<') ? 'desc' : 'asc',
			'limit 1'
		);

		$out = getRow(join(' ',$q));
		return (is_array($out)) ? $out : '';
	}

// -------------------------------------------------------------
	function getNextPrev($id, $Posted, $s)
	{
		static $next, $cache;

		// If next/prev tags are placed before an article tag on a list page, we
		// have to guess what the current article is
		if (!$id) {
			$current = safe_row('ID, Posted', 'textpattern', 
				'1=1 '.
				(($s!='' && $s!='default') ? "and Section = '".doSlash($s)."'" : filterFrontPage()).
				'and Status=4 and Posted < now() order by Posted desc limit 1');
			if ($current) {
				$id = $current['ID'];
				$Posted = $current['Posted'];
			}
		}

		if (@isset($cache[$next[$id]]))
			$thenext = $cache[$next[$id]];
		else
			$thenext            = getNeighbour($Posted,$s,'>');

		$out['next_id']     = ($thenext) ? $thenext['ID'] : '';
		$out['next_title']  = ($thenext) ? $thenext['Title'] : '';
		$out['next_utitle'] = ($thenext) ? $thenext['url_title'] : '';
		$out['next_posted'] = ($thenext) ? $thenext['uposted'] : '';

		$theprev            = getNeighbour($Posted,$s,'<');
		$out['prev_id']     = ($theprev) ? $theprev['ID'] : '';
		$out['prev_title']  = ($theprev) ? $theprev['Title'] : '';
		$out['prev_utitle'] = ($theprev) ? $theprev['url_title'] : '';
		$out['prev_posted'] = ($theprev) ? $theprev['uposted'] : '';

		if ($theprev) {
			$cache[$theprev['ID']] = $theprev;
			$next[$theprev['ID']] = $id;
		}

		return $out;
	}

// -------------------------------------------------------------
	function lastMod() 
	{
		$last = safe_field("unix_timestamp(val)", "txp_prefs", "`name`='lastmod' and prefs_id=1");
		return gmdate("D, d M Y H:i:s \G\M\T",$last);	
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
		global $pretext, $production_status, $txptrace, $txptracelevel, $txp_current_tag;

		$tag = $matches[1];

		$atts = (isset($matches[2])) ? splat($matches[2]) : '';
		$thing = (isset($matches[4])) ? $matches[4] : '';

		$old_tag = @$txp_current_tag;
		$txp_current_tag = '<txp:'.$tag.
			($atts ? $matches[2] : '').
			($thing ? '>' : '/>');

		trace_add($txp_current_tag);
		@++$txptracelevel;
		if ($production_status == 'debug')
			maxMemUsage(trim($matches[0]));

		$out = '';

		if ($thing) {
			if (function_exists($tag)) $out = $tag($atts,$thing,$matches[0]);
			elseif (isset($pretext[$tag])) $out = $pretext[$tag];
			else trigger_error(gTxt('unknown_tag'), E_USER_WARNING);
		} else {
			if (function_exists($tag)) $out = $tag($atts,null,$matches[0]);
			elseif (isset($pretext[$tag])) $out = $pretext[$tag];
			else trigger_error(gTxt('unknown_tag'), E_USER_WARNING);
		}

		@--$txptracelevel;
		if (isset($matches[4]))
			trace_add('</txp:'.$tag.'>');

		$txp_current_tag = $old_tag;
		return $out;
	}

// -------------------------------------------------------------
	function bombShelter() // protection from those who'd bomb the site by GET
	{
		global $prefs;
		$in = serverset('REQUEST_URI');
		if (!empty($prefs['max_url_len']) and strlen($in) > $prefs['max_url_len']) exit('Nice try.');
	}

// -------------------------------------------------------------
	function evalString($html) 
	{
		if (strpos($html, chr(60).'?php') !== false) {
			$html = eval(' ?'.chr(62).$html.chr(60).'?php ');
		}
		return $html;	
	}

// -------------------------------------------------------------
	function getCustomFields()
	{
		global $prefs;
		$i = 0;
		while ($i < 10) {
			$i++;
			if (!empty($prefs['custom_'.$i.'_set'])) {
				$out[$i] = $prefs['custom_'.$i.'_set'];
			}
		}
		return (!empty($out)) ? $out : false;
	}
	
// -------------------------------------------------------------
	function buildCustomSql($custom,$pairs)
	{
		if ($pairs) {
			$pairs = doSlash($pairs);
			foreach($pairs as $k => $v) {
				if(in_array($k,$custom)) {
					$no = array_keys($custom,$k);
					# nb - use 'like' here to allow substring matches
					$out[] = "and custom_".$no[0]." like '$v'";
				}
			}
		}
		return (!empty($out)) ? ' '.join(' ',$out).' ' : false; 
	}

// -------------------------------------------------------------
	function getStatusNum($name) 
	{
		$labels = array('draft' => 1, 'hidden' => 2, 'pending' => 3, 'live' => 4, 'sticky' => 5);
		$status = strtolower($name);
		$num = empty($labels[$status]) ? 4 : $labels[$status];
		return $num;
	}
	
// -------------------------------------------------------------
	function ckEx($table,$val,$debug='') 
	{
		return safe_field("name",'txp_'.$table,"`name` like '".doSlash($val)."' limit 1",$debug);
	}

// -------------------------------------------------------------
	function ckExID($val,$debug='') 
	{
		return safe_row("ID,Section",'textpattern',"ID = ".doSlash($val)." limit 1",$debug);
	}

// -------------------------------------------------------------
	function lookupByTitle($val,$debug='') 
	{
		return safe_row("ID,Section",'textpattern',"url_title like '".doSlash($val)."' limit 1",$debug);
	}
// -------------------------------------------------------------
	function lookupByTitleSection($val,$section,$debug='') 
	{
		return safe_row("ID,Section",'textpattern',"url_title like '".doSlash($val)."' AND Section='$section' limit 1",$debug);
	}	

// -------------------------------------------------------------
	function lookupByID($id,$debug='') 
	{
		return safe_row("ID,Section",'textpattern',"ID = '".doSlash($id)."' limit 1",$debug);
	}

// -------------------------------------------------------------
	function lookupByDateTitle($when,$title,$debug='') 
	{
		return safe_row("ID,Section","textpattern",
		"posted like '".doSlash($when)."%' and url_title like '".doSlash($title)."' limit 1");
	}

// -------------------------------------------------------------
	function makeOut() 
	{
		foreach(func_get_args() as $a) {
			$array[$a] = htmlspecialchars(gps($a));
		}
		return $array;
	}

// -------------------------------------------------------------
	function chopUrl($req) 
	{
		$req = urldecode(strtolower($req));
		//strip off query_string, if present
		$qs = strpos($req,'?');
		if ($qs) $req = substr($req, 0, $qs);
		$req = preg_replace('/index\.php$/', '', $req);
		$r = explode('/',$req);
		$o['u0'] = (!empty($r[0])) ? $r[0] : '';
		$o['u1'] = (!empty($r[1])) ? $r[1] : '';
		$o['u2'] = (!empty($r[2])) ? $r[2] : '';
		$o['u3'] = (!empty($r[3])) ? $r[3] : '';
		$o['u4'] = (!empty($r[4])) ? $r[4] : '';

		return $o;
	}
	
?>

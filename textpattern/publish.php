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
	include_once txpath.'/lib/txplib_misc.php';
	include_once txpath.'/lib/txplib_db.php';
	include_once txpath.'/lib/txplib_html.php';
	include_once txpath.'/lib/txplib_forms.php';
	include_once txpath.'/lib/admin_config.php';

	include_once txpath.'/publish/taghandlers.php';
	include_once txpath.'/publish/log.php';
	include_once txpath.'/publish/comment.php';

//	set_error_handler('myErrorHandler');

	ob_start();

		// start the clock for runtime
	$microstart = getmicrotime();

		// initialize parse trace globals
	$txptrace        = array();
	$txptracelevel   = '';
	$txp_current_tag = '';

		// get all prefs as an array
	$prefs = get_prefs();

		// add prefs to globals
	extract($prefs);

	// check the size of the url request
	bombShelter();

		// set a higher error level during initialization
	set_error_level(@$production_status == 'live' ? 'testing' : @$production_status);

		// use the current URL path if $siteurl is unknown
	if (empty($siteurl))
		$prefs['siteurl'] = $siteurl = $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

	if (empty($path_to_site))
		updateSitePath(dirname(dirname(__FILE__)));

	if (!defined('PROTOCOL')) {
		switch (serverSet('HTTPS')) {
			case '':
			case 'off': // ISAPI with IIS
				define('PROTOCOL', 'http://');
			break;

			default:
				define('PROTOCOL', 'https://');
			break;
		}
	}

		// v1.0: this should be the definitive http address of the site
	if (!defined('hu'))
		define("hu",PROTOCOL.$siteurl.'/');

		// v1.0 experimental relative url global
	if (!defined('rhu'))
		define("rhu",preg_replace("|^https?://[^/]+|","",hu));

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
	callback_event('pretext_end');
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
		callback_event('file_download');
		if (!isset($file_error)) {

				$fullpath = build_file_path($file_base_path,$filename);

				if (is_file($fullpath)) {

					// discard any error php messages
					ob_clean();
					$filesize = filesize($fullpath); $sent = 0;
					header('Content-Description: File Download');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="' . basename($filename) . '"; size = "'.$filesize.'"');
					// Fix for lame IE 6 pdf bug on servers configured to send cache headers
					header('Cache-Control: private');
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
							safe_update("txp_file", "downloads=downloads+1", 'id='.intval($id));
							log_hit('200');
						} else {
							$pretext['request_uri'] .= ($sent >= $filesize)
								? '#aborted'
								: "#aborted-at-".floor($sent*100/$filesize)."%";
							log_hit('200');
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
				txp_die(gTxt('403_forbidden'), '403');
				break;
			case 404:
				txp_die(gTxt('404_not_found'), '404');
				break;
			default:
				txp_die(gTxt('500_internal_server_error'), '500');
				break;
			}
		}

		// download done
		exit(0);
	}


	// send 304 Not Modified if appropriate
	handle_lastmod();

	// log the page view
	log_hit($status);


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
		$out['request_uri'] = preg_replace("|^https?://[^/]+|i","",serverSet('REQUEST_URI'));
		$out['qs'] = serverSet('QUERY_STRING');
			// IIS fix
		if (!$out['request_uri'] and serverSet('SCRIPT_NAME'))
			$out['request_uri'] = serverSet('SCRIPT_NAME').( (serverSet('QUERY_STRING')) ? '?'.serverSet('QUERY_STRING') : '');
			// another IIS fix
		if (!$out['request_uri'] and serverSet('argv'))
		{
			$argv = serverSet('argv');
			$out['request_uri'] = @substr($argv[0], strpos($argv[0], ';') + 1);
		}

			// define the useable url, minus any subdirectories.
			// this is pretty fugly, if anyone wants to have a go at it - dean
		$out['subpath'] = $subpath = preg_quote(preg_replace("/https?:\/\/.*(\/.*)/Ui","$1",hu),"/");
		$out['req'] = $req = preg_replace("/^$subpath/i","/",$out['request_uri']);

		$is_404 = 0;

			// if messy vars exist, bypass url parsing
		if (!$out['id'] && !$out['s'] && !(txpinterface=='css') &&! ( txpinterface=='admin') ) {

			// return clean URL test results for diagnostics
			if (gps('txpcleantest')) {
				exit(show_clean_test($out));
			}

			extract(chopUrl($req));

				//first we sniff out some of the preset url schemes
			if (strlen($u1)) {

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

					default:
						// then see if the prefs-defined permlink scheme is usable
						switch ($permlink_mode) {

							case 'section_id_title':
								if (empty($u2)) {
									$out['s'] = (ckEx('section',$u1)) ? $u1 : '';
									$is_404 = empty($out['s']);
								}
								else {
									$rs = lookupByIDSection($u2, $u1);
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
									$out['id'] = isset($rs['ID']) ? $rs['ID'] : '';
									$out['s'] = isset($rs['Section']) ? $rs['Section'] : '';
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
			if ($out['id'] && !$out['s']) {
				$rs = lookupByID($out['id']);
				$out['id'] = (!empty($rs['ID'])) ? $rs['ID'] : '';
				$out['s'] = (!empty($rs['Section'])) ? $rs['Section'] : '';
				$is_404 = (empty($out['s']) or empty($out['id']));
			}
		}

		// Resolve AuthorID from Authorname
		if ($out['author'])
		{
			$name = urldecode(strtolower(urlencode($out['author'])));

			$name = safe_field('name', 'txp_users', "RealName like '".doSlash($out['author'])."'");

			if ($name)
			{
				$out['author'] = $name;
			}

			else
			{
				$out['author'] = '';
				$is_404 = true;
			}
		}

		// allow article preview
		if (gps('txpreview') and is_logged_in())
		{
			global $nolog;

			$nolog = true;
			$rs = safe_row("ID as id,Section as s",'textpattern','ID = '.intval(gps('txpreview')).' limit 1');

			if ($rs and $is_404)
			{
				$is_404 = false;
				$out = array_merge($out, $rs);
			}
		}

		// Stats: found or not
		$out['status'] = ($is_404 ? '404' : '200');

		$out['pg'] = is_numeric($out['pg']) ? intval($out['pg']) : '';
		$out['id'] = is_numeric($out['id']) ? intval($out['id']) : '';

		if ($out['s'] == 'file_download') {
			// get id of potential filename
			if (!is_numeric($out['id'])) {
				$rs = safe_row("*", "txp_file", "filename='".doSlash($out['id'])."' and status = 4");
			} else {
				$rs = safe_row("*", "txp_file", 'id='.intval($out['id']).' and status = 4');
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
		if (empty($id)) $is_article_list = true;

		// by this point we should know the section, so grab its page and css
		$rs = safe_row("page, css", "txp_section", "name = '".doSlash($s)."' limit 1");
		$out['page'] = isset($rs['page']) ? $rs['page'] : '';
		$out['css'] = isset($rs['css']) ? $rs['css'] : '';

		if (is_numeric($id) and !$is_404) {
			$a = safe_row('*, unix_timestamp(Posted) as uPosted, unix_timestamp(Expires) as uExpires, unix_timestamp(LastMod) as uLastMod', 'textpattern', 'ID='.intval($id).(gps('txpreview') ? '' : ' and Status in (4,5)'));
			if ($a) {
				$Posted             = $a['Posted'];
				$out['id_keywords'] = $a['Keywords'];
				$out['id_author']   = $a['AuthorID'];
				populateArticleData($a);

				$uExpires = $a['uExpires'];
				if ($uExpires and time() > $uExpires and !$publish_expired_articles) {
					$out['status'] = '410';
				}

				if ($np = getNextPrev($id, $Posted, $s))
					$out = array_merge($out, $np);
			}
		}

		$out['path_from_root'] = rhu; // these are deprecated as of 1.0
		$out['pfr']            = rhu; // leaving them here for plugin compat

		$out['path_to_site']   = $path_to_site;
		$out['permlink_mode']  = $permlink_mode;
		$out['sitename']       = $sitename;

		return $out;

	}

//	textpattern() is the function that assembles a page, based on
//	the variables passed to it by pretext();

// -------------------------------------------------------------
	function textpattern()
	{
		global $pretext,$microstart,$prefs,$qcount,$qtime,$production_status,$txptrace,$siteurl,$has_article_tag;

		$has_article_tag = false;

		callback_event('textpattern');

		if ($pretext['status'] == '404')
			txp_die(gTxt('404_not_found'), '404');

		if ($pretext['status'] == '410')
			txp_die(gTxt('410_gone'), '410');

		$html = safe_field('user_html','txp_page',"name='".doSlash($pretext['page'])."'");
		if (!$html)
			txp_die(gTxt('unknown_section'), '404');

		// useful for clean urls with error-handlers
		txp_status_header('200 OK');

		trace_add('['.gTxt('page').': '.$pretext['page'].']');
		set_error_handler("tagErrorHandler");
		$pretext['secondpass'] = false;
		$html = parse($html);
		$pretext['secondpass'] = true;
		trace_add('[ ~~~ '.gTxt('secondpass').' ~~~ ]');
		$html = parse($html); // the function so nice, he ran it twice
		if ($prefs['allow_page_php_scripting']) $html = evalString($html);

		// make sure the page has an article tag if necessary
		if (!$has_article_tag and $production_status != 'live' and (!empty($pretext['id']) or !empty($pretext['c']) or !empty($pretext['q']) or !empty($pretext['pg'])))
			trigger_error(gTxt('missing_article_tag', array('{page}' => $pretext['page'])));
		restore_error_handler();

		header("Content-type: text/html; charset=utf-8");
		echo $html;

		if (in_array($production_status, array('debug', 'testing'))) {
			$microdiff = (getmicrotime() - $microstart);
			echo n,comment('Runtime:    '.substr($microdiff,0,6));
			echo n,comment('Query time: '.sprintf('%02.6f', $qtime));
			echo n,comment('Queries: '.$qcount);
			echo maxMemUsage('end of textpattern()',1);
			if (!empty($txptrace) and is_array($txptrace))
				echo n, comment('txp tag trace: '.n.str_replace('--','&shy;&shy;',join(n, $txptrace)).n);
				// '&shy;&shy;' is *no* tribute to Kajagoogoo, but an attempt to avoid prematurely terminating HTML comments
		}

		callback_event('textpattern_end');
	}

// -------------------------------------------------------------
	function output_css($s='',$n='')
	{
		if ($n) {
			$cssname = $n;
		} elseif ($s) {
			$cssname = safe_field('css','txp_section',"name='".doSlash($s)."'");
		}

		if (isset($cssname)) $css = safe_field('css','txp_css',"name='".doSlash($cssname)."'");
		if (isset($css)) echo base64_decode($css);
	}

//	article() is called when parse() finds a <txp:article /> tag.
//	If an $id has been established, we output a single article,
//	otherwise, output a list.

// -------------------------------------------------------------
	function article($atts, $thing = NULL)
	{
		global $is_article_body, $has_article_tag;
		if ($is_article_body) {
			trigger_error(gTxt('article_tag_illegal_body'));
			return '';
		}
		$has_article_tag = true;
		return parseArticles($atts, '0', $thing);
	}

// -------------------------------------------------------------
	function doArticles($atts, $iscustom, $thing = NULL)
	{
		global $pretext, $prefs;
		extract($pretext);
		extract($prefs);
		$customFields = getCustomFields();
		$customlAtts = array_null(array_flip($customFields));

		//getting attributes
		$theAtts = lAtts(array(
			'form'      => 'default',
			'listform'  => '',
			'searchform'=> '',
			'limit'     => 10,
			'pageby'    => '',
			'category'  => '',
			'section'   => '',
			'excerpted' => '',
			'author'    => '',
			'sort'      => '',
			'sortby'    => '',
			'sortdir'   => '',
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
			'wraptag'	=> '',
			'break'		=> '',
			'label'		=> '',
			'labeltag'	=> '',
			'class'		=> ''
		)+$customlAtts,$atts);

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

		// if a listform is specified, $thing is for doArticle() - hence ignore here.
		if (!empty($listform)) $thing = '';

		$pageby = (empty($pageby) ? $limit : $pageby);

		// treat sticky articles differently wrt search filtering, etc
		$status = in_array(strtolower($status), array('sticky', '5')) ? 5 : 4;
		$issticky = ($status == 5);

		// give control to search, if necessary
		if ($q && !$iscustom && !$issticky)
		{
			include_once txpath.'/publish/search.php';

			$s_filter = ($searchall ? filterSearch() : '');
			$q = doSlash($q);

            		// searchable article fields are limited to the columns of
            		// the textpattern table and a matching fulltext index must exist.
			$cols = do_list($searchable_article_fields);
			if (empty($cols) or $cols[0] == '') $cols = array('Title', 'Body');

			$match = ', match (`'.join('`, `', $cols)."`) against ('$q') as score";
			for ($i = 0; $i < count($cols); $i++)
			{
				$cols[$i] = "`$cols[$i]` rlike '$q'";
			}
			$cols = join(" or ", $cols);
			$search = " and ($cols) $s_filter";

			// searchall=0 can be used to show search results for the current section only
			if ($searchall) $section = '';
			if (!$sort) $sort = 'score desc';
		}
		else {
			$match = $search = '';
			if (!$sort) $sort = 'Posted desc';
		}

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

		//Building query parts
		$frontpage = ($frontpage and (!$q or $issticky)) ? filterFrontPage() : '';
		$category  = join("','", doSlash(do_list($category)));
		$category  = (!$category)  ? '' : " and (Category1 IN ('".$category."') or Category2 IN ('".$category."'))";
		$section   = (!$section)   ? '' : " and Section IN ('".join("','", doSlash(do_list($section)))."')";
		$excerpted = ($excerpted=='y')  ? " and Excerpt !=''" : '';
		$author    = (!$author)    ? '' : " and AuthorID IN ('".join("','", doSlash(do_list($author)))."')";
		$month     = (!$month)     ? '' : " and Posted like '".doSlash($month)."%'";
		$id        = (!$id)        ? '' : " and ID IN (".join(',', array_map('intval', do_list($id))).")";
		switch ($time) {
			case 'any':
				$time = ""; break;
			case 'future':
				$time = " and Posted > now()"; break;
			default:
				$time = " and Posted <= now()";
		}
		if (!$publish_expired_articles) {
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

		//Allow keywords for no-custom articles. That tagging mode, you know
		if ($keywords) {
			$keys = doSlash(do_list($keywords));
			foreach ($keys as $key) {
				$keyparts[] = "FIND_IN_SET('".$key."',Keywords)";
			}
			$keywords = " and (" . join(' or ',$keyparts) . ")";
		}

		if ($q and $searchsticky)
			$statusq = ' and Status >= 4';
		elseif ($id)
			$statusq = ' and Status >= 4';
		else
			$statusq = ' and Status = '.intval($status);

		$where = "1=1" . $statusq. $time.
			$search . $id . $category . $section . $excerpted . $month . $author . $keywords . $custom . $frontpage;

		//do not paginate if we are on a custom list
		if (!$iscustom and !$issticky)
		{
			$grand_total = safe_count('textpattern',$where);
			$total = $grand_total - $offset;
			$numPages = ceil($total/$pageby);
			$pg = (!$pg) ? 1 : $pg;
			$pgoffset = $offset + (($pg - 1) * $pageby);
			// send paging info to txp:newer and txp:older
			$pageout['pg']       = $pg;
			$pageout['numPages'] = $numPages;
			$pageout['s']        = $s;
			$pageout['c']        = $c;
			$pageout['grand_total'] = $grand_total;
			$pageout['total']    = $total;

			global $thispage;
			if (empty($thispage))
				$thispage = $pageout;
			if ($pgonly)
				return;
		}else{
			$pgoffset = $offset;
		}

		$rs = safe_rows_start("*, unix_timestamp(Posted) as uPosted, unix_timestamp(Expires) as uExpires, unix_timestamp(LastMod) as uLastMod".$match, 'textpattern',
		$where.' order by '.doSlash($sort).' limit '.intval($pgoffset).', '.intval($limit));
		// get the form name
		if ($q and !$iscustom and !$issticky)
			$fname = ($searchform ? $searchform : 'search_results');
		else
			$fname = ($listform ? $listform : $form);

		if ($rs) {
			$count = 0;
			$last = numRows($rs);

			$articles = array();
			while($a = nextRow($rs)) {
				++$count;
				populateArticleData($a);
				global $thisarticle, $uPosted, $limit;
				$thisarticle['is_first'] = ($count == 1);
				$thisarticle['is_last'] = ($count == $last);

				if (@constant('txpinterface') === 'admin' and gps('Form')) {
					$articles[] = parse(gps('Form'));
				}
				elseif ($allowoverride and $a['override_form']) {
					$articles[] = parse_form($a['override_form']);
				}
				else {
					$articles[] = ($thing) ? parse($thing) : parse_form($fname);
				}

				// sending these to paging_link(); Required?
				$uPosted = $a['uPosted'];

				unset($GLOBALS['thisarticle']);
			}

			return doLabel($label, $labeltag).doWrap($articles, $wraptag, $break, $class);
		}
	}

// -------------------------------------------------------------

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

// -------------------------------------------------------------
	function doArticle($atts, $thing = NULL)
	{
		global $pretext,$prefs, $thisarticle;
		extract($prefs);
		extract($pretext);

		extract(gpsa(array('parentid', 'preview')));

		extract(lAtts(array(
			'allowoverride' => '1',
			'form'          => 'default',
			'status'        => '4',
		),$atts, 0));

		// if a form is specified, $thing is for doArticles() - hence ignore $thing here.
		if (!empty($atts['form'])) $thing = '';

		if ($status)
		{
			$status = in_array(strtolower($status), array('sticky', '5')) ? 5 : 4;
		}

		if (empty($thisarticle) or $thisarticle['thisid'] != $id)
		{
			$thisarticle = NULL;

			$q_status = ($status ? 'and Status = '.intval($status) : 'and Status in (4,5)');

			$rs = safe_row("*, unix_timestamp(Posted) as uPosted, unix_timestamp(Expires) as uExpires, unix_timestamp(LastMod) as uLastMod",
					"textpattern", 'ID = '.intval($id)." $q_status limit 1");

			if ($rs) {
				extract($rs);
				populateArticleData($rs);
			}
		}

		if (!empty($thisarticle) and ($thisarticle['status'] == $status or gps('txpreview')))
		{
			extract($thisarticle);
			$thisarticle['is_first'] = 1;
			$thisarticle['is_last'] = 1;

			if ($allowoverride and $override_form)
			{
				$article = parse_form($override_form);
			}
			else
			{
				$article = ($thing) ? parse($thing) : parse_form($form);
			}

			if ($use_comments and $comments_auto_append)
			{
				$article .= parse_form('comments_display');
			}

			unset($GLOBALS['thisarticle']);

			return $article;
		}
	}

// -------------------------------------------------------------
	function article_custom($atts, $thing = NULL)
	{
		return parseArticles($atts, '1', $thing);
	}

// -------------------------------------------------------------
	function parseArticles($atts, $iscustom = 0, $thing = NULL)
	{
		global $pretext, $is_article_list;
		$old_ial = $is_article_list;
		$is_article_list = ($pretext['id'] && !$iscustom)? false : true;
		article_push();
		$r = ($is_article_list)? doArticles($atts, $iscustom, $thing) : doArticle($atts, $thing);
		article_pop();
		$is_article_list = $old_ial;

		return $r;
	}

// -------------------------------------------------------------
// Keep all the article tag-related values in one place,
// in order to do easy bugfix and easily the addition of
// new article tags.
	function populateArticleData($rs)
	{
		global $thisarticle;
		extract($rs);

		trace_add("[".gTxt('Article')." $ID]");
		$thisarticle['thisid']          = $ID;
		$thisarticle['posted']          = $uPosted;
		$thisarticle['expires']         = $uExpires;
		$thisarticle['modified']		= $uLastMod;
		$thisarticle['annotate']        = $Annotate;
		$thisarticle['comments_invite'] = $AnnotateInvite;
		$thisarticle['authorid']        = $AuthorID;
		$thisarticle['title']           = $Title;
		$thisarticle['url_title']       = $url_title;
		$thisarticle['category1']       = $Category1;
		$thisarticle['category2']       = $Category2;
		$thisarticle['section']         = $Section;
		$thisarticle['keywords']        = $Keywords;
		$thisarticle['article_image']   = $Image;
		$thisarticle['comments_count']  = $comments_count;
		$thisarticle['body']            = $Body_html;
		$thisarticle['excerpt']         = $Excerpt_html;
		$thisarticle['override_form']   = $override_form;
		$thisarticle['status']          = $Status;

		$custom = getCustomFields();
		if ($custom) {
			foreach ($custom as $i => $name)
				$thisarticle[$name] = $rs['custom_' . $i];
		}

	}

// -------------------------------------------------------------
	function getNeighbour($Posted, $s, $type)
	{
		global $prefs;
		extract($prefs);
		$expired = ($publish_expired_articles) ? '' : ' and (now() <= Expires or Expires = '.NULLDATETIME.')';
		$type = ($type == '>') ? '>' : '<';
		$safe_name = safe_pfx('textpattern');
		$q = array(
			"select ID, Title, url_title, unix_timestamp(Posted) as uposted
			from ".$safe_name." where Posted $type '".doSlash($Posted)."'",
			($s!='' && $s!='default') ? "and Section = '".doSlash($s)."'" : filterFrontPage(),
			'and Status=4 and Posted < now()'.$expired.' order by Posted',
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

// -------------------------------------------------------------

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

		if ($tag === 'link')
		{
			$tag = 'tpt_'.$tag;
		}

		if (function_exists($tag))
		{
			$out = $tag(splat($atts), $thing);
		}

		// deprecated, remove in crockery
		elseif (isset($GLOBALS['pretext'][$tag]))
		{
			$out = htmlspecialchars($pretext[$tag]);

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
		global $prefs;
		if (strpos($html, chr(60).'?php') !== false) {
			trigger_error(gTxt('raw_php_deprecated'), E_USER_WARNING);
			if (!empty($prefs['allow_raw_php_scripting']))
				$html = eval(' ?'.chr(62).$html.chr(60).'?php ');
			else
				trigger_error(gTxt('raw_php_disabled'), E_USER_WARNING);
		}
		return $html;
	}

// -------------------------------------------------------------
	function getCustomFields()
	{
		global $prefs;
		$max = get_pref('max_custom_fields', 10);
		$out = array();
		for ($i = 1; $i <= $max; $i++) {
			if (!empty($prefs['custom_'.$i.'_set'])) {
				$out[$i] = strtolower($prefs['custom_'.$i.'_set']);
			}
		}
		return $out;
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
		return safe_row("ID,Section",'textpattern','ID = '.intval($val).' and Status >= 4 limit 1',$debug);
	}

// -------------------------------------------------------------
	function lookupByTitle($val,$debug='')
	{
		return safe_row("ID,Section",'textpattern',"url_title like '".doSlash($val)."' and Status >= 4 limit 1",$debug);
	}
// -------------------------------------------------------------
	function lookupByTitleSection($val,$section,$debug='')
	{
		return safe_row("ID,Section",'textpattern',"url_title like '".doSlash($val)."' AND Section='".doSlash($section)."' and Status >= 4 limit 1",$debug);
	}

// -------------------------------------------------------------

	function lookupByIDSection($id, $section, $debug = '')
	{
		return safe_row('ID, Section', 'textpattern',
			'ID = '.intval($id)." and Section = '".doSlash($section)."' and Status >= 4 limit 1", $debug);
	}

// -------------------------------------------------------------
	function lookupByID($id,$debug='')
	{
		return safe_row("ID,Section",'textpattern','ID = '.intval($id).' and Status >= 4 limit 1',$debug);
	}

// -------------------------------------------------------------
	function lookupByDateTitle($when,$title,$debug='')
	{
		return safe_row("ID,Section","textpattern",
		"posted like '".doSlash($when)."%' and url_title like '".doSlash($title)."' and Status >= 4 limit 1");
	}

// -------------------------------------------------------------
	function makeOut()
	{
		foreach(func_get_args() as $a) {
			$array[$a] = strval(gps($a));
		}
		return $array;
	}

// -------------------------------------------------------------
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

?>

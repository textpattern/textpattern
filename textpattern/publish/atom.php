<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/


// -------------------------------------------------------------
	function atom() 
	{
		global $thisarticle;
		extract($GLOBALS['prefs']);
		define("t_texthtml",' type="text/html"');
		define("t_text",' type="text"');
		define("t_html",' type="html"');
		define("t_xhtml",' type="xhtml"');
		define('t_appxhtml',' type="xhtml"');
		define("r_relalt",' rel="alternate"');
		define("r_relself",' rel="self"');
		
		$area = doSlash(gps('area'));
		extract(doSlash(gpsa(array('category','section','limit'))));
		
		$last = fetch('unix_timestamp(val)','txp_prefs','name','lastmod');
				
		$sitename .= ($section) ? ' - '.$section : '';
		$sitename .= ($category) ? ' - '.$category : '';

		$pub = safe_row("RealName, email", "txp_users", "privs=1");

		$out[] = tag($sitename,'title',t_text);
		$out[] = tag($site_slogan,'subtitle',t_text);
		$out[] = '<link'.r_relself.' href="'.pagelinkurl(array('atom'=>1,'area'=>$area,'section'=>$section,'category'=>$category,'limit'=>$limit)).'" />';
		$out[] = '<link'.r_relalt.t_texthtml.' href="'.hu.'" />';
		$articles = array();

		//Atom feeds with mail or domain name
		$dn = explode('/',$siteurl);
		$mail_or_domain = ($use_mail_on_feeds_id)? eE($blog_mail_uid):$dn[0];
		$out[] = tag('tag:'.$mail_or_domain.','.$blog_time_uid.':'.$blog_uid.(($section)? '/'.$section:'').(($category)? '/'.$category:''),'id');

		$out[] = tag('Textpattern','generator',
			' uri="http://textpattern.com/" version="'.$version.'"');
		$out[] = tag(safe_strftime("w3cdtf",$last),'updated');


		$auth[] = tag($pub['RealName'],'name');
		$auth[] = ($include_email_atom) ? tag(eE($pub['email']),'email') : '';
		$auth[] = tag(hu,'uri');
		
		$out[] = tag(n.t.t.join(n.t.t,$auth).n,'author');

		if (!$area or $area=='article') {
			
			$sfilter = ($section) ? "and Section = '".$section."'" : '';
			$cfilter = ($category) 
				? "and (Category1='".$category."' or Category2='".$category."')":'';
			$limit = ($limit) ? $limit : $rss_how_many;
			$limit = min($limit,max(100,$rss_how_many));

			$frs = safe_column("name", "txp_section", "in_rss != '1'");

			$query = array();
			foreach($frs as $f) $query[] = "and Section != '".doSlash($f)."'";
			$query[] = $sfilter;
			$query[] = $cfilter;
				
			$rs = safe_rows_start(
				"*, 
				ID as thisid, 
				unix_timestamp(Posted) as uPosted,
				unix_timestamp(LastMod) as uLastMod",
				"textpattern", 
				"Status=4 and Posted <= now() ".
					join(' ',$query).
					"order by Posted desc limit $limit" 
			);
			if ($rs) {	
				while ($a = nextRow($rs)) {

					extract($a);
					populateArticleData($a);
					$e = array();
					
					$a['posted'] = $uPosted;
	
					if ($show_comment_count_in_feed)
						$count = ($comments_count > 0) ? ' ['.$comments_count.']' : '';
					else $count = '';

					$thisauthor = get_author_name($AuthorID);
					$e['thisauthor'] = tag(n.t.t.t.tag(htmlspecialchars($thisauthor),'name').n.t.t,'author');
		
					$e['issued'] = tag(safe_strftime('w3cdtf',$uPosted),'published');
					$e['modified'] = tag(safe_strftime('w3cdtf',$uLastMod),'updated');

					$escaped_title = escape_output($Title);
					$e['title'] = tag($escaped_title.$count,'title',t_html);


					$uTitle = ($url_title) ? $url_title : stripSpace($Title);
					$uTitle = htmlspecialchars($uTitle,ENT_NOQUOTES);

					$permlink = permlinkurl($a);
					$e['link'] = '<link'.r_relalt.t_texthtml.' href="'.$permlink.'" />';

					$e['id'] = tag('tag:'.$mail_or_domain.','.$feed_time.':'.$blog_uid.'/'.$uid,'id');

					$e['category1'] = (trim($Category1) ? '<category term="'.htmlspecialchars($Category1).'" />' : '');
					$e['category2'] = (trim($Category2) ? '<category term="'.htmlspecialchars($Category2).'" />' : '');

					$Excerpt = fixup_for_feed($thisarticle['excerpt'], permlinkurl($a));
					if ($syndicate_body_or_excerpt == 0)
						$Body = fixup_for_feed($thisarticle['body'], permlinkurl($a));
					else {
						$Body = '';
						// If there's no excerpt, use body as content instead of body as summary
						if (!trim($Excerpt))
							$Body = fixup_for_feed($thisarticle['body'], permlinkurl($a));
					}

					if (trim($Body))
						$e['content'] = tag(n.$Body.n,'content',t_html);

					if (trim($Excerpt))
						$e['summary'] = tag(n.$Excerpt.n,'summary',t_html);
		
					$articles[$ID] = tag(n.t.t.join(n.t.t,$e).n,'entry');

					$etags[$ID] = strtoupper(dechex(crc32($articles[$ID])));
					$dates[$ID] = $uLastMod;
				}
			}
		} elseif ($area=='link') {
		
			$cfilter = ($category) ? "category='".$category."'" : '1';
			$limit = ($limit) ? $limit : $rss_how_many;
			$limit = min($limit,max(100,$rss_how_many));
		
			$rs = safe_rows_start("*", "txp_link", "$cfilter order by date desc limit $limit");

			if ($rs) {
				while ($a = nextRow($rs)) {
					extract($a);
 
					$e['title'] = tag(doSpecial($linkname),'title');
					$content = utf8_encode(htmlspecialchars($description));
					$e['content'] = tag(n.$description.n,'content',t_texthtml);
					
					$url = (preg_replace("/^\/(.*)/","http://$siteurl/$1",$url));
					$url = preg_replace("/&((?U).*)=/","&amp;\\1=",$url);
					$e['link'] = '<link'.r_relalt.t_texthtml.' href="'.$url.'" />';

					$e['issued'] = tag(gmdate('Y-m-d\TH:i:s\Z',strtotime($date)),'published');
					$e['modified'] = tag(gmdate('Y-m-d\TH:i:s\Z',strtotime($date)),'updated');
					$e['id'] = tag('tag:'.$mail_or_domain.','.$feed_time.':'.$id,'id');

					$articles[$id] = tag(n.t.t.join(n.t.t,$e).n,'entry');

					$etags[$id] = strtoupper(dechex(crc32($articles[$id])));
					$dates[$id] = $date;

				}
			}
		
		}
		if (!empty($articles)) {

			//turn on compression if we aren't using it already
			if (extension_loaded('zlib') && ini_get("zlib.output_compression") == 0 && ini_get('output_handler') != 'ob_gzhandler' && !headers_sent()) {
				@ob_start("ob_gzhandler");
			}
		  
			$expires = gmdate('D, d M Y H:i:s \G\M\T', time()+(3600*1));
			header("Expires: $expires");
			$hims = serverset('HTTP_IF_MODIFIED_SINCE');
			$imsd = ($hims) ? strtotime($hims) : 0;
		  
			if ($imsd >= $last) {
				txp_status_header("304 Not Modified"); exit;
			}
			header("Last-Modified: ".gmdate('D, d M Y H:i:s \G\M\T',$last));

			if (is_callable('apache_request_headers')) {
				$headers = apache_request_headers();
				if (isset($headers["A-IM"])) {
					$canaim = strpos($headers["A-IM"], "feed");
				} else {
					$canaim = false;
				}
			} else {
				$canaim = false;
			}
		  
			$hinm = stripslashes(serverset('HTTP_IF_NONE_MATCH'));

			$cutarticles = false;
		
			if ($canaim !== false) {
				foreach($articles as $id=>$thing) {
					if (strpos($hinm, $etags[$id])) {
						unset($articles[$id]);
						$cutarticles = true;
						$cut_etag = true;
					}

					if ($dates[$id] < $imsd) {
						unset($articles[$id]);
						$cutarticles = true;
						$cut_time = true;
					}
				}
			}

			if (isset($cut_etag) && isset($cut_time)) {
				header("Vary: If-None-Match, If-Modified-Since");
			} else if (isset($cut_etag)) {
				header("Vary: If-None-Match");
			} else if (isset($cut_time)) {
				header("Vary: If-Modified-Since");
			}

			$etag = @join("-",$etags);

			if (strstr($hinm, $etag)) {
				header("HTTP/1.1 304 Not Modified"); exit;
			}

			if ($etag) header('ETag: "'.$etag.'"');

			if ($cutarticles) {
				//header("HTTP/1.1 226 IM Used"); 
				//This should be used as opposed to 200, but Apache doesn't like it.
				//http://intertwingly.net/blog/2004/09/11/Vary-ETag/ says that the status code should be 200.
				header("Cache-Control: no-store, im");
				header("IM: feed");
			}
		
			$out = array_merge($out, $articles);

			header('Content-type: application/atom+xml; charset=utf-8');
			return chr(60).'?xml version="1.0" encoding="UTF-8"?'.chr(62).n.
			'<feed xml:lang="'.$language.'" xmlns="http://www.w3.org/2005/Atom">'.join(n,$out).'</feed>';
		}
	}


	function fixup_for_feed($txt, $permalink) {

		$txt = replace_relative_urls($txt, $permalink);
		$txt = escape_output($txt);
		return $txt;
	}

?>

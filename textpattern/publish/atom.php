<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 
*/


// -------------------------------------------------------------
	function atom() 
	{
		global $txpac, $thisarticle;
		extract($GLOBALS['prefs']);
		define("textplain",' type="text/plain"');
		define("texthtml",' type="text/html"');
		define("relalt",' rel="alternate"');
		define('appxhtml',' type="application/xhtml+xml"');
		define("divxhtml",'<div xmlns="http://www.w3.org/1999/xhtml">');
		
		$area = doSlash(gps('area'));
		extract(doSlash(gpsa(array('category','section','limit'))));
		
		$last = fetch('unix_timestamp(val)','txp_prefs','name','lastmod');
				
		$sitename .= ($section) ? ' - '.$section : '';
		$sitename .= ($category) ? ' - '.$category : '';

		$pub = safe_row("RealName, email", "txp_users", "privs=1");

		$out[] = tag($sitename,'title',textplain);
		$out[] = tag($site_slogan,'tagline',textplain);
		$out[] = '<link'.relalt.texthtml.' href="'.hu.'" />';
		$articles = array();

		//Atom feeds with mail or domain name
		$dn = explode('/',$siteurl);
		$mail_or_domain = ($txpac['use_mail_on_feeds_id'])? eE($blog_mail_uid):$dn[0];
		$out[] = tag('tag:'.$mail_or_domain.','.$blog_time_uid.':'.$blog_uid.(($section)? '/'.$section:'').(($category)? '/'.$category:''),'id');

		$out[] = tag('Textpattern','generator',
			' url="http://textpattern.com" version="'.$version.'"');
		$out[] = tag(date("Y-m-d\TH:i:s\Z",$last),'modified');


			$auth[] = tag($pub['RealName'],'name');
			$auth[] = ($txpac['include_email_atom']) ? tag(eE($pub['email']),'email') : '';
			$auth[] = tag(hu,'url');
		
		$out[] = tag(n.t.t.join(n.t.t,$auth).n,'author');

		if (!$area or $area=='article') {
			
			$sfilter = ($section) ? "and Section = '".$section."'" : '';
			$cfilter = ($category) 
				? "and (Category1='".$category."' or Category2='".$category."')":'';
			$limit = ($limit) ? $limit : '5';
			
			$frs = safe_column("name", "txp_section", "in_rss != '1'");
			
			foreach($frs as $f) $query[] = "and Section != '".$f."'";
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
					
					$a['posted'] = $uPosted;
	
					if ($txpac['show_comment_count_in_feed']) {
						$dc = getCount('txp_discuss', "parentid=$ID and visible=1");
						$count = ($dc > 0) ? ' ['.$dc.']' : '';
					} else $count = '';

					$thisauthor = safe_field("RealName","txp_users","name='$AuthorID'");
					$e['thisauthor'] = tag(n.t.t.t.tag(htmlspecialchars($thisauthor),'name').n.t.t,'author');
		
					$e['issued'] = tag(gmdate("Y-m-d\TH:i:s\Z",$uPosted),'issued');
					$e['modified'] = tag(gmdate("Y-m-d\TH:i:s\Z",$uLastMod),'modified');

					$escaped_title = safe_hed($Title);
					$escaped_title = preg_replace("/&(?![#a-z0-9]+;)/i",'&amp;', $escaped_title);
					$escaped_title = str_replace('<','&lt;',$escaped_title);
					$escaped_title = str_replace('>','&gt;',$escaped_title);
					$e['title'] = tag($escaped_title.$count,'title');


					$uTitle = ($url_title) ? $url_title : stripSpace($Title);
					$uTitle = htmlspecialchars($uTitle,ENT_NOQUOTES);

					$permlink = permlinkurl($a);

					$e['link'] = '<link'.relalt.texthtml.' href="'.$permlink.'" />';

					$e['id'] = tag('tag:'.$mail_or_domain.','.$feed_time.':'.$blog_uid.'/'.$uid,'id');

					$e['subject'] = tag(htmlspecialchars($Category1),'dc:subject');
					
						// pull Body or Excerpt?
					$Body = (!$txpac['syndicate_body_or_excerpt']) ? $thisarticle['body'] : $thisarticle['excerpt'];
	
						// if Excerpt is empty, switch back to Body_html
					$Body = (!trim($Body)) ? $thisarticle['body'] : $Body; 

						// fix relative urls
					$Body = str_replace('href="/','href="'.hu,$Body);
					$Body = preg_replace("/href=\\\"#(.*)\"/","href=\"".permlinkurl($a)."#\\1\"",$Body);
					$Body = safe_hed($Body);
						// encode and entify
					$Body = preg_replace(array('/</','/>/',"/'/",'/"/'), array('&#60;','&#62;','&#039;','&#34;'), $Body);
						// encode bare ampersands
					$Body = preg_replace("/&(?![#0-9]+;|\w+;)/i",'&amp;', $Body);


					$e['content'] = tag(n.$Body.n,'content',
						' type="text/html" mode="escaped"');
		
					$articles[$ID] = tag(n.t.t.join(n.t.t,$e).n,'entry');

					$etags[$ID] = strtoupper(dechex(crc32($articles[$ID])));
					$dates[$ID] = $uLastMod;
				}
			}
		} elseif ($area=='link') {
		
			$cfilter = ($category) ? "category='$category'" : '1';
			$limit = ($limit) ? $limit : 15;
		
			$rs = safe_rows_start("*", "txp_link", "$cfilter order by date desc limit $limit");

			if ($rs) {
				while ($a = nextRow($rs)) {
					extract($a);
 
					$e['title'] = tag(doSpecial($linkname),'title');
					$content = utf8_encode(htmlspecialchars($description));
					$e['content'] = tag(n.$description.n,'content',' type="text/html" mode="escaped"');
					
					$url = (preg_replace("/^\/(.*)/","http://$siteurl/$1",$url));
					$url = preg_replace("/&((?U).*)=/","&amp;\\1=",$url);
					$e['link'] = '<link'.relalt.texthtml.' href="'.$url.'" />';

					$e['issued'] = tag(gmdate("Y-m-d\TH:i:s\Z",$date),'issued');
					$e['modified'] = tag(gmdate("Y-m-d\TH:i:s\Z",$date),'modified');
					$e['id'] = tag('tag:'.$mail_or_domain.','.$feed_time.':'.$id,'id');

					$articles[$id] = tag(n.t.t.join(n.t.t,$e).n,'entry');

					$etags[$id] = strtoupper(dechex(crc32($articles[$id])));
					$dates[$id] = $date;

				}
			}
		
		}
		if (!empty($articles)) {

			//turn on compression if we aren't using it already
			if (extension_loaded('zlib') && ini_get("zlib.output_compression") == 0 && ini_get('output_handler') != 'ob_gzhandler') {
				ob_start("ob_gzhandler");
			}
		  
			$last = fetch('unix_timestamp(val)','txp_prefs','name','lastmod');
			$last = gmdate("D, d M Y H:i:s \G\M\T",$last);
			header("Last-Modified: $last");
			$expires = gmdate('D, d M Y H:i:s \G\M\T', time()+(3600*1));
			header("Expires: $expires");
		  	$hims = serverset('HTTP_IF_MODIFIED_SINCE');
		  
			if ($hims == $last) {
		  		header("HTTP/1.1 304 Not Modified"); exit;
		  	}

			$imsd = @strtotime($hims);

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

			ob_start();
			header('Content-type: application/atom+xml; charset=utf-8');
			return chr(60).'?xml version="1.0" encoding="UTF-8"?'.chr(62).n.
			'<feed version="0.3" xml:lang="'.$language.'" xmlns="http://purl.org/atom/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">'.join(n,$out).'</feed>';
		}
	}


	function safe_hed($toUnicode) {
		
		if (version_compare(phpversion(), "5.0.0", ">=")) {
			$str =  html_entity_decode($toUnicode, ENT_QUOTES, "UTF-8");
		} else {
			$trans_tbl = get_html_translation_table(HTML_ENTITIES);
			foreach($trans_tbl as $k => $v) {
				$ttr[$v] = utf8_encode($k);
			}
			$str = strtr($toUnicode, $ttr);
		}
		return $str;
	}


?>

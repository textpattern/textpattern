<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/


// -------------------------------------------------------------
	function rss()
	{
		global $prefs,$thisarticle;
		extract($prefs);

		extract(doSlash(gpsa(array('category','section','limit','area'))));

		$area = gps('area');

		$sitename .= ($section) ? ' - '.$section : '';
		$sitename .= ($category) ? ' - '.$category : '';

		$out[] = tag(doSpecial($sitename),'title');
		$out[] = tag(hu,'link');
		$out[] = tag(doSpecial($site_slogan),'description');
		$articles = array();

		if (!$area or $area=='article') {
					
			$sfilter = ($section) ? "and Section = '".$section."'" : '';
			$cfilter = ($category) 
				? "and (Category1='".$category."' or Category2='".$category."')":'';
			$limit = ($limit) ? $limit : $rss_how_many;
			$limit = min($limit,max(100,$rss_how_many));

			$frs = safe_column("name", "txp_section", "in_rss != '1'");
			if ($frs) foreach($frs as $f) $query[] = "and Section != '".doSlash($f)."'";
			$query[] = $sfilter;
			$query[] = $cfilter;
			
			$rs = safe_rows_start(
				"*, unix_timestamp(Posted) as uPosted, ID as thisid",
				"textpattern", 
				"Status = 4 ".join(' ',$query).
				"and Posted < now() order by Posted desc limit $limit"
			);
				
			if($rs) {
				while ($a = nextRow($rs)) {
					extract($a);
					populateArticleData($a);

					$a['posted'] = $uPosted;

					$Body = (!$syndicate_body_or_excerpt) ? $thisarticle['body'] : $thisarticle['excerpt'];
					$Body = (!trim($Body)) ? $thisarticle['body'] : $Body;
					$Body = str_replace('href="/','href="'.hu,$Body);
					$Body = preg_replace("/href=\\\"#(.*)\"/","href=\"".permlinkurl($a)."#\\1\"",$Body);
					$Body = rss_safe_hed($Body);

					$Body = preg_replace(array('/</','/>/',"/'/",'/"/'), array('&lt;','&gt;','&#039;','&quot;'),$Body);
						// encode bare ampersands
					$Body = preg_replace("/&(?![#0-9]+;|\w+;)/i",'&amp;', $Body);

					$uTitle = ($url_title) ? $url_title : stripSpace($Title);
					$uTitle = htmlspecialchars($uTitle,ENT_NOQUOTES);


					if ($show_comment_count_in_feed) {
						$count = ($comments_count > 0) ? ' ['.$comments_count.']' : '';
					} else $count = '';

					$Title = doSpecial($Title).$count;

					$permlink = permlinkurl($a);

					$item = tag(strip_tags($Title),'title').n.
						tag($Body,'description').n.
						tag($permlink,'link');
	
					$articles[$ID] = tag($item,'item');

					$etags[$ID] = strtoupper(dechex(crc32($articles[$ID])));
					$dates[$ID] = $uPosted;

				}

			}
		} elseif ($area=='link') {
				
			$cfilter = ($category) ? "category='$category'" : '1';
			$limit = ($limit) ? $limit : $rss_how_many;
			$limit = min($limit,max(100,$rss_how_many));

			$rs = safe_rows_start("*", "txp_link", "$cfilter order by date desc limit $limit");
		
			if ($rs) {
				while ($a = nextRow($rs)) {
					extract($a);
					$item = 
						tag(doSpecial($linkname),'title').n.
						tag(doSpecial($description),'description').n.
						tag(doSpecial($url),'link');
					$articles[$id] = tag($item,'item');

					$etags[$id] = strtoupper(dechex(crc32($articles[$id])));
					$dates[$id] = $date;
				}

			}
		}
		
		  //turn on compression if we aren't using it already
		if (extension_loaded('zlib') && ini_get("zlib.output_compression") == 0 && ini_get('output_handler') != 'ob_gzhandler' && !headers_sent()) {
		  @ob_start("ob_gzhandler");
		}

		$last = fetch('unix_timestamp(val)','txp_prefs','name','lastmod');
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
				if (strpos($hinm, $etags[$id]) !== false) {
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


		if ($cutarticles) {
			//header("HTTP/1.1 226 IM Used"); 
			//This should be used as opposed to 200, but Apache doesn't like it.
			//http://intertwingly.net/blog/2004/09/11/Vary-ETag/ says that the status code should be 200.
			header("Cache-Control: no-store, im");
			header("IM: feed");
		}
		
		$out = array_merge($out, $articles);


		header("Content-Type: application/rss+xml; charset=utf-8");
		if ($etag) header('ETag: "'.$etag.'"');
		return '<rss version="0.92">'.tag(join(n,$out),'channel').'</rss>';
	}


	function rss_safe_hed($toUnicode) {

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

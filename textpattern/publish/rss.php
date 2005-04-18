<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

*/


// -------------------------------------------------------------
	function rss()
	{
		global $prefs,$txpac,$thisarticle;
		extract($prefs);
		ob_start();

		extract(doSlash(gpsa(array('category','section','limit','area'))));

		$area = gps('area');

		$sitename .= ($section) ? ' - '.$section : '';
		$sitename .= ($category) ? ' - '.$category : '';

		$out[] = tag(doSpecial($sitename),'title');
		$out[] = tag('http://'.$siteurl.$path_from_root,'link');
		$out[] = tag(doSpecial($site_slogan),'description');

		if (!$area or $area=='article') {
					
			$sfilter = ($section) ? "and Section = '".$section."'" : '';
			$cfilter = ($category) 
				? "and (Category1='".$category."' or Category2='".$category."')":'';
			$limit = ($limit) ? $limit : '5';
		
				$frs = safe_column("name", "txp_section", "in_rss != '1'");
				if ($frs) foreach($frs as $f) $query[] = "and Section != '".$f."'";
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

					$Body = (!$txpac['syndicate_body_or_excerpt']) ? $thisarticle['body'] : $thisarticle['excerpt'];
					$Body = (!trim($Body)) ? $thisarticle['body'] : $Body;
					$Body = str_replace('href="/','href="http://'.$siteurl.'/',$Body);
					$Body = preg_replace("/href=\\\"#(.*)\"/","href=\"".permlinkurl($a)."#\\1\"",$Body);
					$Body = html_entity_decode($Body,ENT_QUOTES);
					$Body = preg_replace("/&((?U).*)=/","&amp;\\1=",$Body);

					$Body = preg_replace(array('/</','/>/',"/'/",'/"/'), array('&lt;','&gt;','&#039;','&quot;'),$Body);


					$uTitle = ($url_title) ? $url_title : stripSpace($Title);
					$uTitle = htmlspecialchars($uTitle,ENT_NOQUOTES);


					if ($txpac['show_comment_count_in_feed']) {
						$dc = getCount('txp_discuss', "parentid=$ID and visible=1");
						$count = ($dc > 0) ? ' ['.$dc.']' : '';
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
			$limit = ($limit) ? $limit : 15;

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
                if (ini_get("zlib.output_compression") == 0) {
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

		if (strpos($_SERVER['SERVER_SOFTWARE'], "Apache") !== false) {
			$headers = apache_request_headers();
			$canaim = strpos(@$headers["A-IM"], "feed");
		}

		$hinm = stripslashes(serverset('HTTP_IF_NONE_MATCH'));

		if ($canaim !== false) {
			foreach($articles as $id=>$thing) {
				if (strpos($hinm, $etags[$id]) !== false) {
					unset($articles[$id]);
					$cutarticles = true;
					header("Vary: If-None-Match");
				}

				if ($dates[$id] < $imsd) {
					unset($articles[$id]);
					$cutarticles = true;
					header("Vary: If-Modified-Since");
				}
			}
		}


		$etag = join("-",$etags);

		if (strstr($hinm, $etag)) {
			header("HTTP/1.1 304 Not Modified"); exit;
		}


		if (!empty($cutarticles)) {
			//header("HTTP/1.1 226 IM Used"); 
			//This should be used as opposed to 200, but Apache doesn't like it.
			//http://intertwingly.net/blog/2004/09/11/Vary-ETag/ says that the status code should be 200.
			header("Cache-Control: no-store, im");
			header("IM: feed");
		}
                
		$out = array_merge($out, $articles);


                header("Content-Type: application/rss+xml; charset=utf-8");
                header('ETag: "'.$etag.'"');
                return '<rss version="0.92">'.tag(join(n,$out),'channel').'</rss>';
	}
?>

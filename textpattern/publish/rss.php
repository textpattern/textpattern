<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

*/


// -------------------------------------------------------------
	function rss()
	{
		global $prefs,$txpac;
		extract($prefs);
		ob_start();

		extract(doSlash(gpsa(array('category','section','limit','area'))));

		// send a 304 if nothing has changed since the last visit
	
		$last = fetch('unix_timestamp(val)','txp_prefs','name','lastmod');
		$last = gmdate("D, d M Y H:i:s \G\M\T",$last);
		header("Last-Modified: $last");
		$hims = serverset('HTTP_IF_MODIFIED_SINCE');
		if ($hims == $last) {
			header("HTTP/1.1 304 Not Modified"); exit; 
		}
		
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
			
			$rs = safe_rows(
				"*, unix_timestamp(Posted) as uPosted, ID as thisid",
				"textpattern", 
				"Status = 4 ".join(' ',$query).
				"and Posted < now() order by Posted desc limit $limit"
			);
				
			if($rs) {
				foreach ($rs as $a) {
					extract($a);

					$a['posted'] = $uPosted;

					$Body = (!$txpac['syndicate_body_or_excerpt']) ? $Body_html : $Excerpt;
					$Body = (!trim($Body)) ? $Body_html : $Body;
					$Body = str_replace('href="/','href="http://'.$siteurl.'/',$Body);
					$Body = preg_replace(array('/</','/>/',"/'/",'/"/'), array('&lt;','&gt;','&#039','&quot;'),$Body);
					
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
	
					$out[] = tag($item,'item');
				}
	
			header("Content-Type: text/xml"); 
			return '<rss version="0.92">'.tag(join(n,$out),'channel').'</rss>';
			}
		} elseif ($area=='link') {
				
			$cfilter = ($category) ? "category='$category'" : '1';
			$limit = ($limit) ? $limit : 15;

			$rs = safe_rows("*", "txp_link", "$cfilter order by date desc limit $limit");
		
			if ($rs) {
				foreach($rs as $a) {
					extract($a);
					$item = 
						tag(doSpecial($linkname),'title').n.
						tag(doSpecial($description),'description').n.
						tag($url,'link');
					$out[] = tag($item,'item');
				}
				header("Content-Type: text/xml"); 
				return '<rss version="0.92">'.tag(join(n,$out),'channel').'</rss>';
			}
		}
		return 'no articles recorded yet';
	}
?>

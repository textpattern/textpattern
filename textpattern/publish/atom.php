<?php

/*
	This is Textpattern
	Copyright 2004 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 
*/


// -------------------------------------------------------------
	function atom() 
	{
		global $txpac;
		extract($GLOBALS['prefs']);
		define("textplain",' type="text/plain"');
		define("texthtml",' type="text/html"');
		define("relalt",' rel="alternate"');
		define('appxhtml',' type="application/xhtml+xml"');
		define("divxhtml",'<div xmlns="http://www.w3.org/1999/xhtml">');
		
		$area = gps('area');
		extract(doSlash(gpsa(array('category','section','limit'))));
		
		$last = fetch('unix_timestamp(val)','txp_prefs','name','lastmod');
		
		$sitename .= ($section) ? ' - '.$section : '';
		$sitename .= ($category) ? ' - '.$category : '';

		$out[] = tag($sitename,'title',textplain);
		$out[] = tag($site_slogan,'tagline',textplain);
		$out[] = '<link'.relalt.texthtml.' href="http://'.$siteurl.$path_from_root.'" />';
		$out[] = tag('tag:'.$siteurl.','.date("Y").':/','id');
		$out[] = tag('Textpattern','generator',
			' url="http://textpattern.com" version="g1.17"');
		$out[] = tag(date("Y-m-d\TH:i:s\Z",$last),'modified');

		$pub = safe_row("RealName, email", "txp_users", "privs=1");

			$auth[] = tag($pub['RealName'],'name');
			$auth[] = ($txpac['include_email_atom']) ? tag(eE($pub['email']),'email') : '';
			$auth[] = tag('http://'.$siteurl.$path_from_root,'url');
		
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
				
			$rs = safe_rows(
				"*, unix_timestamp(Posted) as uPosted,unix_timestamp(LastMod) as uLastMod",
				"textpattern", 
				"Status=4 and Posted <= now() ".
					join(' ',$query).
					"order by Posted desc limit $limit" 
			);
			if ($rs) {	
				foreach ($rs as $a) {
					extract($a);
	
					if ($txpac['show_comment_count_in_feed']) {
						$dc = getCount('txp_discuss', "parentid=$ID and visible=1");
						$count = ($dc > 0) ? ' ['.$dc.']' : '';
					} else $count = '';
		
					$e['issued'] = tag(date("Y-m-d\TH:i:s\Z",$uPosted),'issued');
					$e['modified'] = tag(date("Y-m-d\TH:i:s\Z",$uLastMod),'modified');
					$e['title'] = tag($Title.$count,'title');
						$elink = ($url_mode == 0)
						?	'http://'.$siteurl.$path_from_root.'index.php?id='.$ID
						:	'http://'.$siteurl.$path_from_root.$Section.'/'.$ID.'/';
					$e['link'] = '<link'.relalt.texthtml.' href="'.$elink.'" />';
					$e['id'] = tag('tag:'.$siteurl.','.date("Y-m-d",$uPosted).':'.$ID,'id');
					$e['subject'] = tag(htmlspecialchars($Category1),'dc:subject');
					
						// pull Body or Excerpt?
					$Body = (!$txpac['syndicate_body_or_excerpt']) ? $Body_html : $Excerpt;
	
						// if Excerpt is empty, switch back to Body_html
					$Body = (!trim($Body)) ? $Body_html : $Body; 
					
						// fix relative urls
					$Body = str_replace('href="/','href="http://'.$siteurl.'/',$Body);
	
						// encode and entify
					$Body = utf8_encode(htmlspecialchars($Body));
	
					$e['content'] = tag(n.$Body.n,'content',
						' type="text/html" mode="escaped" xml:lang="en"');
		
					$out[] = tag(n.t.t.join(n.t.t,$e).n,'entry');
				}
			}
		} elseif ($area=='link') {
		
			$cfilter = ($category) ? "category='$category'" : '1';
			$limit = ($limit) ? $limit : 15;
		
			$rs = safe_rows("*", "txp_link", "$cfilter order by date desc limit $limit");

			if ($rs) {
				foreach($rs as $a) {
					extract($a);
 
					$e['title'] = tag(doSpecial($linkname),'title');
					$content = utf8_encode(htmlspecialchars($description));
					$e['content'] = tag(n.$description.n,'content',' type="text/html" mode="escaped" xml:lang="en"');
					$url = (preg_replace("/^\/(.*)/","http://$siteurl/$1",$url));
					$e['link'] = '<link'.relalt.texthtml.' href="'.$url.'" />';

					$out[] = tag(n.t.t.join(n.t.t,$e).n,'entry');
				}
			}
		
		}
		if (!empty($out)) {
			ob_start();
			header('Content-type: text/xml');
			return chr(60).'?xml version="1.0" encoding="UTF-8"?'.chr(62).n.
			'<feed version="0.3" xmlns="http://purl.org/atom/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">'.join(n,$out).'</feed>';
		}
	}

?>

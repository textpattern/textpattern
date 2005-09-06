<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$
	
*/


// -------------------------------------------------------------
	function search($q)
	{
		global $prefs;
		$url = $prefs['siteurl'];
		extract($prefs);
		
		$s_filter = filterSearch();

		$form = fetch('form','txp_form','name','search_results');

			// lose this eventually - only used if search_results form is missing
		$form = (!$form) ? legacy_form() : $form;

		$rs = safe_rows(
			"*, ID as thisid, unix_timestamp(Posted) as posted, Title as title,
			match (Title,Body) against ('$q') as score",
			"textpattern",
			"(Title rlike '$q' or Body rlike '$q') $s_filter
			and Status = 4 and Posted <=now() order by score desc limit 40");
		
		if($rs) {
			$result_rows = count($rs);
			$text = ($result_rows == 1) ? gTxt('article_found') : gTxt('articles_found');
		} else {
			$result_rows = 0;
			$text = gTxt('articles_found');
		}

		$results[] = graf($result_rows.' '.$text);
		if($result_rows > 0) {
			foreach($rs as $a) {
				extract($a);
								
				$result_date = safe_strftime($archive_dateformat,$posted);
				$uTitle = ($url_title) ? $url_title : stripSpace($Title);
				$hurl = permlinkurl($a);
				$result_url = '<a href="'.$hurl.'">'.$hurl.'</a>';
				$result_title = '<a href="'.$hurl.'">'.$Title.'</a>';
	
				$result = preg_replace("/>\s*</","> <",$Body_html);
				preg_match_all("/\s.{1,50}".preg_quote($q).".{1,50}\s/i",$result,$concat);
						
					$concat = join(" ... ",$concat[0]);
					
					$concat = strip_tags($concat);
					$concat = preg_replace('/^[^>]+>/U',"",$concat);
					$concat = preg_replace("/($q)/i","<strong>$1</strong>",$concat);
					$result_excerpt = ($concat) ? "... ".$concat." ..." : '';

					$glob['search_result_title']   = $result_title;
					$glob['search_result_excerpt'] = $result_excerpt;
					$glob['search_result_url']     = $result_url;
					$glob['search_result_date']    = $result_date;

					$GLOBALS['this_result'] = $glob;
					
					$thisresult = $form;

					$results[] = parse($thisresult);
			}
		}
		return (is_array($results)) ? join('',$results) : '';
	}

// -------------------------------------------------------------
	function filterSearch() 
	{
		$rs = safe_column("name", "txp_section", "searchable != '1'");
		if ($rs) {
			foreach($rs as $name) $filters[] = "and Section != '".doSlash($name)."'";	
			return join(' ',$filters);
		}
		return false;
	}

// -------------------------------------------------------------
	function legacy_form() 
	{	// lose this eventually
		return '<h2><txp:search_result_title /></h2>
<p><txp:search_result_excerpt /><br/>
<small><txp:search_result_url /> &middot; <txp:search_result_date /></small></p>';
	}

?>

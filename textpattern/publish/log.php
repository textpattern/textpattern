<?php

/*
	This is Textpattern
	Copyright 2004 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 
*/


// -------------------------------------------------------------
	function logit($r='')
	{
		global $siteurl,$id,$record_mentions;
		$mydomain = str_replace('www.','',$siteurl);
		$out['uri'] = $_SERVER['REQUEST_URI'];
		$out['ref'] = str_replace("http://","",serverset('HTTP_REFERER'));
		$out['ip'] = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
		if (preg_match("/^[^\.]*\.?$mydomain/i", $out['ref'])) $out['ref'] = "";
		
		if ($r=='refer') {
			if (trim($out['ref']) != "") { insert_logit($out); }
		} else insert_logit($out);

		if ($id && $record_mentions && $out['ref']) {
			$thepage = getReferringPage('http://'.$out['ref']);
			$refpage = preg_replace("/^(www\.)?(.*)\/?$/Ui","$2",$out['ref']);
			$reftitle = tweezePageTitle($thepage);
			$excerpt = tweezeExcerpt($thepage,$out['uri']);
			if ($refpage) mentionInsert(array('id'=>$id,
				'refpage'=>$refpage,'reftitle'=>$reftitle,'excerpt'=>$excerpt));
		}
	}

// -------------------------------------------------------------
	function insert_logit($in) 
	{	
		global $DB;
		$in = doSlash($in);
		extract($in);
		safe_insert("txp_log", "`time`=now(),page='$uri',host='$ip',refer='$ref'");
	}

// -------------------------------------------------------------
	function getReferringPage($url) 
	{
		if(function_exists('curl_init')) {
	      	$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			$tmp = curl_exec ($ch);
			curl_close ($ch);
			return $tmp;
		} else {
			return @implode('', @file ($url));
		}
		return false;
	}

// -------------------------------------------------------------
	function tweezePageTitle($text)
	{
		preg_match_all("/<title>(.+)<\/title>/sU",$text,$matches,PREG_SET_ORDER);
		return (!empty($matches[0][1])) ? $matches[0][1] : false;
	}

// -------------------------------------------------------------
	function tweezeExcerpt($text,$url)
	{
		$url = preg_quote($url,"/");
		$pattern = "/(^.*<a.*$url.*>.+<\/a>.*$)/miU";
		preg_match_all($pattern,$text,$matches,PREG_SET_ORDER);
		return (!empty($matches[0][1])) ? strip_tags($matches[0][1]) : false;
	}


// -------------------------------------------------------------
	function mentionInsert($array) 
	{
		extract(doSlash($array));

		$chk = fetch('article_id','txp_log_mention','refpage',$refpage);

		if (!$chk) {
			safe_insert(
			   "txp_log_mention",
			   "article_id = '$id', 
				refpage    = '$refpage', 
				reftitle   = '$reftitle', 
				excerpt    = '$excerpt', 
				count      = 1"
			);
		} else {
			safe_update("textpattern", "count=count+1", "refpage='$refpage'");
		}		
	}

?>

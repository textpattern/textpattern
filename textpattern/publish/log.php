<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 
*/


// -------------------------------------------------------------
	function logit($r='')
	{
		global $siteurl, $txpac;
		$mydomain = str_replace('www.','',preg_quote($siteurl,"/"));
		$out['uri'] = $_SERVER['REQUEST_URI'];
		$out['ref'] = clean_url(str_replace("http://","",serverset('HTTP_REFERER')));
		$host = $ip = $_SERVER['REMOTE_ADDR'];
		if (!empty($txpac['use_dns'])) {
			// Double-check the rDNS
			$host = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
			if ($host != $ip and @gethostbyname($host) != $ip)
				$host = $ip;
			// Confirm that the referrer domain exists
			if (trim($out['ref'])) {
				$p = parse_url(serverset('HTTP_REFERER'));
				if (isset($p['host']) and $p['host'] != $mydomain and @gethostbyname($p['host']) == $p['host'])
					$out['ref'] = '';
			}
		}
		$out['ip'] = $host;
		if (preg_match("/^[^\.]*\.?$mydomain/i", $out['ref'])) $out['ref'] = "";
		
		if ($r=='refer') {
			if (trim($out['ref']) != "") { insert_logit($out); }
		} else insert_logit($out);
	}

// -------------------------------------------------------------
	function insert_logit($in) 
	{	
		global $DB;
		$in = doSlash($in);
		extract($in);
		safe_insert("txp_log", "`time`=now(),page='$uri',host='$ip',refer='$ref'");
	}

?>

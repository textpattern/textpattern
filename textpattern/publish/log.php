<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/


// -------------------------------------------------------------
	function logit($r='')
	{
		global $siteurl, $prefs;
		$mydomain = str_replace('www.','',preg_quote($siteurl,"/"));
		$out['uri'] = $_SERVER['REQUEST_URI'];
		$out['ref'] = clean_url(str_replace("http://","",serverset('HTTP_REFERER')));
		$host = $ip = $_SERVER['REMOTE_ADDR'];

		if (!empty($prefs['use_dns'])) {
			// A crude rDNS cache
			if ($h = safe_field('host', 'txp_log', "ip='".doSlash($ip)."' limit 1")) {
				$host = $h;
			}
			else {
				// Double-check the rDNS
				$host = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
				if ($host != $ip and @gethostbyname($host) != $ip)
					$host = $ip;
			}
		}
		$out['ip'] = $ip;
		$out['host'] = $host;
		$out['status'] = 200; // FIXME
		$out['method'] = $_SERVER['REQUEST_METHOD'];
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
		safe_insert("txp_log", "`time`=now(),page='$uri',ip='$ip',host='$host',refer='$ref',status='$status',method='$method'");
	}

?>

<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/


// -------------------------------------------------------------
	function log_hit($status)
	{
		global $nolog, $logging;
		callback_event('log_hit');
		if(!isset($nolog) && $status != '404') {
			if($logging == 'refer') {
				logit('refer', $status);
			} elseif ($logging == 'all') {
				logit('', $status);
			}
		}
	}

// -------------------------------------------------------------
	function logit($r='', $status='200')
	{
		global $siteurl, $prefs, $pretext;
		$mydomain = str_replace('www.','',preg_quote($siteurl,"/"));
		$out['uri'] = @$pretext['request_uri'];
		$out['ref'] = clean_url(str_replace("http://","",serverSet('HTTP_REFERER')));
		$ip = serverSet('REMOTE_ADDR');
		if (($ip == '127.0.0.1' or $ip == serverSet('SERVER_ADDR')) and serverSet('HTTP_X_FORWARDED_FOR')) {
			$ips = explode( ', ', serverSet('HTTP_X_FORWARDED_FOR') );
			$ip = $ips[0];
		}
		$host = $ip;

		if (!empty($prefs['use_dns'])) {
			// A crude rDNS cache
			if ($h = safe_field('host', 'txp_log', "ip='".doSlash($ip)."' limit 1")) {
				$host = $h;
			}
			else {
				// Double-check the rDNS
				$host = @gethostbyaddr($ip);
				if ($host != $ip and @gethostbyname($host) != $ip)
					$host = $ip;
			}
		}

		$out['ip'] = $ip;
		$out['host'] = $host;
		$out['status'] = $status;
		$out['method'] = serverSet('REQUEST_METHOD');
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

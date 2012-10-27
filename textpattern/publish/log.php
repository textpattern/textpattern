<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

*/

/**
 * Log visitors.
 *
 * @package Log
 */

/**
 * Adds a row to the visitor logs.
 *
 * This function follows the site's logging preferences.
 * If $logging preference is set to 'refer', only referrer
 * hits are logged. If $logging is set to 'none' or '$nolog'
 * global to TRUE, the function will ignore all hits.
 *
 * If the $status parameter is set to 404, the hit isn't logged.
 *
 * @param int $status HTTP status code
 * @example
 * log_hit(200);
 */

	function log_hit($status)
	{
		global $nolog, $logging;
		callback_event('log_hit');
		if (!isset($nolog) && $status != 404)
		{
			if ($logging == 'refer')
			{
				logit('refer', $status);
			}
			elseif ($logging == 'all')
			{
				logit('', $status);
			}
		}
	}

/**
 * Writes a record to the visitor log using the current visitor's information.
 *
 * This function is used by log_hit(). See it before trying to use this one.
 *
 * The hit is ignore if $r is set to 'refer' and the HTTP REFERER header is empty.
 *
 * @param  string   $r      Type of record to write, e.g. refer
 * @param  int      $status HTTP status code
 * @access private
 * @see    log_hit()
 */

	function logit($r = '', $status = 200)
	{
		global $siteurl, $prefs, $pretext;
		$mydomain = str_replace('www.', '', preg_quote($siteurl,"/"));
		$out['uri'] = @$pretext['request_uri'];
		$out['ref'] = clean_url(str_replace("http://", "", serverSet('HTTP_REFERER')));
		$ip = remote_addr();
		$host = $ip;

		if (!empty($prefs['use_dns']))
		{
			// A crude rDNS cache.
			if ($h = safe_field('host', 'txp_log', "ip='".doSlash($ip)."' limit 1"))
			{
				$host = $h;
			}
			else
			{
				// Double-check the rDNS.
				$host = @gethostbyaddr($ip);
				if ($host != $ip and @gethostbyname($host) != $ip)
				{
					$host = $ip;
				}
			}
		}

		$out['ip'] = $ip;
		$out['host'] = $host;
		$out['status'] = $status;
		$out['method'] = serverSet('REQUEST_METHOD');

		if (preg_match("/^[^\.]*\.?$mydomain/i", $out['ref']))
		{
			$out['ref'] = "";
		}

		if ($r == 'refer')
		{
			if (trim($out['ref']))
			{
				insert_logit($out);
			}
		}
		else
		{
			insert_logit($out);
		}
	}

/**
 * Inserts a log record into the database.
 *
 * @param array $in Input array consisting 'uri', 'ip', 'host', 'ref', 'status', 'method'
 * @see   log_hit()
 */

	function insert_logit($in)
	{
		$in = doSlash($in);
		extract($in);
		safe_insert(
			"txp_log",
			"`time` = now(), page = '$uri', ip='$ip', host='$host', refer='$ref', status='$status', method='$method'"
		);
	}

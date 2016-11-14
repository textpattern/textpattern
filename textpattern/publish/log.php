<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2016 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Log visitors.
 *
 * @package Log
 */

/**
 * Adds a row to the visitor logs.
 *
 * This function follows the site's logging preferences. If $logging preference
 * is set to 'refer', only referrer hits are logged. If $logging is set to
 * 'none' or '$nolog' global to TRUE, the function will ignore all hits.
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

    if (!isset($nolog) && $status != 404) {
        if ($logging == 'refer') {
            logit('refer', $status);
        } elseif ($logging == 'all') {
            logit('', $status);
        }
    }
}

/**
 * Writes a record to the visitor log using the current visitor's information.
 *
 * This function is used by log_hit(). See it before trying to use this one.
 *
 * The hit is ignore if $r is set to 'refer' and the HTTP REFERER header
 * is empty.
 *
 * @param  string $r      Type of record to write, e.g. refer
 * @param  int    $status HTTP status code
 * @access private
 * @see    log_hit()
 */

function logit($r = '', $status = 200)
{
    global $prefs, $pretext;

    if (!isset($pretext['request_uri'])) {
        return;
    }

    $host = $ip = (string) remote_addr();
    $protocol = false;
    $referer = serverSet('HTTP_REFERER');

    if ($referer) {
        foreach (do_list(LOG_REFERER_PROTOCOLS) as $option) {
            if (strpos($referer, $option.'://') === 0) {
                $protocol = $option;
                $referer = substr($referer, strlen($protocol) + 3);
                break;
            }
        }

        if (!$protocol || ($protocol === 'https' && PROTOCOL !== 'https://')) {
            $referer = '';
        } elseif (preg_match('/^[^\.]*\.?'.preg_quote(preg_replace('/^www\./', '', SITE_HOST), '/').'/i', $referer)) {
            $referer = '';
        } else {
            $referer = $protocol.'://'.clean_url($referer);
        }
    }

    if ($r == 'refer' && !$referer) {
        return;
    }

    if (!empty($prefs['use_dns'])) {
        // A crude rDNS cache.
        if (($h = safe_field("host", 'txp_log', "ip = '".doSlash($ip)."' LIMIT 1")) !== false) {
            $host = $h;
        } else {
            // Double-check the rDNS.
            $host = @gethostbyaddr($ip);

            if ($host !== $ip && @gethostbyname($host) !== $ip) {
                $host = $ip;
            }
        }
    }

    insert_logit(array(
        'uri'    => $pretext['request_uri'],
        'ip'     => $ip,
        'host'   => $host,
        'status' => $status,
        'method' => serverSet('REQUEST_METHOD'),
        'ref'    => $referer,
    ));
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
        'txp_log',
        "time = NOW(), page = '$uri', ip = '$ip', host = '$host', refer = '$ref', status = '$status', method = '$method'"
    );
}

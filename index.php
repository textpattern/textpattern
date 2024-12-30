<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2025 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

// Make sure we display all errors that occur during initialisation.
error_reporting(E_ALL);
ini_set("display_errors", "1");

if (!defined('txpinterface')) {
    define('txpinterface', 'public');
}

if (!defined('txpath')) {
    define("txpath", dirname(__FILE__) . '/textpattern');
}

// Save server path to site root.
if (!isset($here)) {
    $here = dirname(__FILE__);
}

// Pull in config unless configuration data has already been provided
// (multi-headed use).
if (!isset($txpcfg['table_prefix']) && is_readable(txpath . '/config.php')) {
    // Use buffering to ensure bogus whitespace in config.php is ignored.
    ob_start(null, 2048);
    include txpath . '/config.php';
    ob_end_clean();
}

// Permit a unified "now" time to be referenced irrespective of how
// long it takes to load the page. This harmonises time-based
// content, such as custom fields, and also allows a dedicated time
// value to be passed to SQL queries, alleviating NOW().
$txpnow = time();

include txpath . '/lib/class.trace.php';
$trace = new Trace();
$trace->start('[PHP includes, stage 1]');
include txpath . '/lib/constants.php';
include txpath . '/lib/txplib_misc.php';
$trace->stop();

if (!isset($txpcfg['table_prefix'])) {
    $txpdir = basename(txpath);

    if (is_readable(txpath . DS . 'setup' . DS . 'index.php')) {
        header('Location: ./' . $txpdir . '/setup');
        exit;
    } else {
        txp_status_header('503 Service Unavailable');
        exit('<p>config.php is missing or corrupt. To install Textpattern, ensure <a href="./' . $txpdir . '/setup/">' . $txpdir . '/setup/</a> exists.</p>');
    }
}

// Custom caches, etc?
if (!empty($txpcfg['pre_publish_script'])) {
    $trace->start("[Pre Publish Script: '{$txpcfg['pre_publish_script']}']");
    require $txpcfg['pre_publish_script'];
    $trace->stop();
}

include txpath . '/publish.php';

if (!empty($f)) {
    output_component($f);
} else {
    textpattern();

    if ($production_status !== 'live') {
        echo $trace->summary();
    }

    if ($production_status === 'debug') {
        echo $trace->result();
    }
}

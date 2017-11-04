<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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
error_reporting(E_ALL | E_STRICT);
@ini_set("display_errors", "1");

if (@ini_get('register_globals')) {
    if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
        die('GLOBALS overwrite attempt detected. Please consider turning register_globals off.');
    }

    // Collect and unset all registered variables from globals.
    $_txpg = array_merge(
        isset($_SESSION) ? (array) $_SESSION : array(),
        (array) $_ENV,
        (array) $_GET,
        (array) $_POST,
        (array) $_COOKIE,
        (array) $_FILES,
        (array) $_SERVER
    );

    // As the deliberate awkwardly-named local variable $_txpfoo MUST NOT be
    // unset to avoid notices further down, we must remove any potential
    // identically-named global from the list of global names here.
    unset($_txpg['_txpfoo']);

    foreach ($_txpg as $_txpfoo => $value) {
        if (!in_array($_txpfoo, array(
            'GLOBALS',
            '_SERVER',
            '_GET',
            '_POST',
            '_FILES',
            '_COOKIE',
            '_SESSION',
            '_REQUEST',
            '_ENV',
        ))) {
            unset($GLOBALS[$_txpfoo], $$_txpfoo);
        }
    }
}

if (!defined('txpinterface')) {
    define('txpinterface', 'public');
}

if (!defined('txpath')) {
    define("txpath", dirname(__FILE__).'/textpattern');
}

// Save server path to site root.
if (!isset($here)) {
    $here = dirname(__FILE__);
}

// Pull in config unless configuration data has already been provided
// (multi-headed use).
if (!isset($txpcfg['table_prefix'])) {
    // Use buffering to ensure bogus whitespace in config.php is ignored.
    ob_start(null, 2048);
    include txpath.'/config.php';
    ob_end_clean();
}

include txpath.'/lib/class.trace.php';
$trace = new Trace();
$trace->start('[PHP includes, stage 1]');
include txpath.'/lib/constants.php';
include txpath.'/lib/txplib_misc.php';
$trace->stop();

if (!isset($txpcfg['table_prefix'])) {
    txp_status_header('503 Service Unavailable');
    exit('config.php is missing or corrupt.  To install Textpattern, visit <a href="./textpattern/setup/">textpattern/setup/</a>');
}

// Custom caches, etc?
if (!empty($txpcfg['pre_publish_script'])) {
    $trace->start("[Pre Publish Script: '{$txpcfg['pre_publish_script']}']");
    require $txpcfg['pre_publish_script'];
    $trace->stop();
}

include txpath.'/publish.php';

switch (txpinterface) {
    case 'css':
        $n = gps('n');
        $t = gps('t');
        output_css($s, $n, $t);
        break;
    default:
        textpattern();

        if ($production_status !== 'live') {
            echo $trace->summary();
        }
}

if ($production_status === 'debug') {
    echo txpinterface === 'css' ? n.'/*'.$trace->result().n.'*/'.n : $trace->result();
}

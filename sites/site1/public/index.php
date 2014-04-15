<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2014 The Textpattern Development Team
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

    // Make sure we display all errors that occur during initialisation.
    error_reporting(E_ALL | E_STRICT);
    @ini_set("display_errors", "1");

    if (@ini_get('register_globals'))
    {
        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
        {
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
            (array) $_SERVER);

        // As the deliberately awkward-named local variable $_txpfoo MUST NOT be unset to avoid notices further
        // down, we must remove any potentially identical-named global from the list of global names here.
        unset($_txpg['_txpfoo']);
        foreach ($_txpg as $_txpfoo => $value)
        {
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

    define("txpinterface", "public");

    // Save server path to site root.
    if (!isset($here))
    {
        $here = dirname(__FILE__);
    }

    // Pull in config unless configuration data has already been provided (multi-headed use).
    if (!isset($txpcfg['table_prefix']))
    {
        // Use buffering to ensure bogus whitespace in config.php is ignored.
        ob_start(NULL, 2048);
        include '../private/config.php';
        ob_end_clean();
    }

    if (!defined('txpath'))
    {
        define("txpath", realpath(dirname(__FILE__).'/../../../textpattern'));
    }

    include txpath.'/lib/constants.php';
    include txpath.'/lib/txplib_misc.php';

    if (!isset($txpcfg['table_prefix']))
    {
        txp_status_header('503 Service Unavailable');
        exit('config.php is missing or corrupt.  To install Textpattern, visit <a href="./setup/">textpattern/setup/</a>');
    }

    include txpath.'/publish.php';
    textpattern();

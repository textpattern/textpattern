<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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

// Use buffering to ensure bogus whitespace is ignored.
ob_start(null, 2048);
@include '../private/config.php';
ob_end_clean();

if (!isset($txpcfg['table_prefix'])) {
    $this_protocol = (empty($_SERVER['HTTPS']) || @$_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://';
    $this_domain   = (@$_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    $admin_subdomain = 'admin'; // assumed admin subdomain
    $config_missing_setup_url = $this_protocol.$admin_subdomain.'.'.substr($this_domain, strpos($this_domain, '.') + 1).'/setup/';

    header("HTTP/1.0 503 Service Unavailable");
    exit('<p>config.php is missing or corrupt. To install Textpattern, visit <a href="'.$config_missing_setup_url.'">'.$config_missing_setup_url.'</a> (if necessary, replace \''.$admin_subdomain.'\' with your own admin subdomain).</p>');
}

if (!defined('txpath')) {
    define("txpath", dirname(realpath(dirname(__FILE__).'/../admin/vendors')));
}

// Save server path to site root.
if (!isset($here)) {
    $here = dirname(__FILE__);
}

include txpath.'/../index.php';

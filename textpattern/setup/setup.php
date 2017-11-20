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

if (@php_sapi_name() != 'cli') {
    exit;
}

$params = getopt('', array('config:', 'debug::', 'force::'));
if (! $file = @$params['config']) {
    exit(<<<EOF
Usage: php setup.php --config="my-setup-config.json"
Ohter options:
    --force - ignore existing 'config.php' file and overwrite it.
    --debug - debug output to STDOUT.

EOF
    );
}

$cfg = @json_decode(file_get_contents($file), true);
if (empty($cfg)) {
    msg("Error json config file", MSG_ERROR);
}


define("txpinterface", "admin");
if (!defined('txpath')) {
    define("txpath", dirname(dirname(__FILE__)));
}
define('MSG_OK', '[OK]');
define('MSG_ALERT', '[WARNING]');
define('MSG_ERROR', '[ERROR]');


error_reporting(E_ALL | E_STRICT);
@ini_set("display_errors", "1");

include_once txpath.'/lib/class.trace.php';
$trace = new Trace();
include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_misc.php';
include_once txpath.'/vendors/Textpattern/Loader.php';

$loader = new \Textpattern\Loader(txpath.'/vendors');
$loader->register();

$loader = new \Textpattern\Loader(txpath.'/lib');
$loader->register();

include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/include/txp_auth.php';
include_once txpath.'/setup/setup_lib.php';

assert_system_requirements();

if (! isset($params['force']) && file_exists(txpath.'/config.php')) {
    msg("config.php already exist", MSG_ERROR);
}

setup_try_mysql();
$cfg_php = setup_makeConfig($cfg);
if (@file_put_contents(txpath.'/config.php', $cfg_php) === FALSE) {
    msg("Can not write config.php file", MSG_ERROR);
}

@include txpath.'/config.php';
setup_db($cfg);

if (isset($params['debug'])) {
    echo $trace->summary();
    echo $trace->result();
}

function msg($msg, $class = MSG_OK, $back='')
{
    echo "$class\t".strip_tags($msg)."\n";
    if ($class == MSG_ERROR) {
        exit(128);
    }
}

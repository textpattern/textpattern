<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
Other options:
    --force - overwrite existing 'config.php' file.
    --debug - debug output to STDOUT.

EOF
    );
}

$cfg = json_decode(file_get_contents($file), true);

if (empty($cfg)) {
    msg("Error in JSON config file", MSG_ERROR);
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
include_once txpath.'/lib/txplib_admin.php';
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
setup_load_lang(@$cfg['site']['language_code']);

if (!isset($params['force']) && file_exists(txpath.'/config.php')) {
    msg(gTxt('already_installed', array('{configpath}' => txpath)), MSG_ERROR);
}

setup_connect();
$cfg_php = setup_makeConfig($cfg);

if (@file_put_contents(txpath.'/config.php', $cfg_php) === false) {
    msg(gTxt('config_php_write_error'), MSG_ERROR);
}

@include txpath.'/config.php';

if (empty($cfg['user']['login_name'])) {
    msg(gTxt('name_required'), MSG_ERROR);
}

if (empty($cfg['user']['password'])) {
    msg(gTxt('pass_required'), MSG_ERROR);
}

if (!is_valid_email($cfg['user']['email'])) {
    msg(gTxt('email_required'), MSG_ERROR);
}

setup_db($cfg);
msg(gTxt('that_went_well'));

setup_die(0);

function msg($msg, $class = MSG_OK, $back = false)
{
    echo "$class\t".strip_tags($msg)."\n";
    if ($class == MSG_ERROR) {
        setup_die(128);
    }
}

function setup_die($code = 0)
{
    global $trace, $params;

    if (isset($params['debug'])) {
        echo $trace->summary();
        echo $trace->result();
    }

    exit((int)$code);
}

<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
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

if (!defined('TXP_UPDATE')) {
    exit("Nothing here. You can't access this file directly.");
}

global $thisversion, $dbversion, $txp_using_svn, $dbupdatetime;

$dbupdates = array(
    '1.0.0',
    '4.0.2', '4.0.3', '4.0.4', '4.0.5', '4.0.6', '4.0.7', '4.0.8',
    '4.2.0',
    '4.3.0',
    '4.5.0', '4.5.7',
    '4.6.0'
);

function newest_file()
{
    $newest = 0;
    $dp = opendir(txpath.'/update/');

    while (false !== ($file = readdir($dp))) {
        if (strpos($file, "_") === 0) {
            $newest = max($newest, filemtime(txpath."/update/$file"));
        }
    }

    closedir($dp);

    return $newest;
}

if (($dbversion == '') ||
    (strpos($dbversion, 'g1') === 0) ||
    (strpos($dbversion, '1.0rc') === 0)) {
    $dbversion = '0.9.9';
}

$dbversion_target = $thisversion;

if ($dbversion == $dbversion_target ||
    ($txp_using_svn && (newest_file() <= $dbupdatetime))) {
    return;
}

assert_system_requirements();

@ignore_user_abort(1);
@set_time_limit(0);

// Wipe out the last update check setting so the next visit to Diagnostics
// forces an update check, which resets the message. Without this, people who
// upgrade in future may still see a "new version available" message for some
// time after upgrading.
safe_delete('txp_prefs', "name = 'last_update_check'");

set_error_handler("updateErrorHandler");

$updates = array_fill_keys($dbupdates, true);

if (!isset($updates[$dbversion_target])) {
    $updates[$dbversion_target] = false;
}

try {
    foreach ($updates as $dbupdate => $update) {
        if (version_compare($dbversion, $dbupdate, '<')) {
            if ($update && (include txpath.DS.'update'.DS.'_to_'.$dbupdate.'.php') === false) {
                trigger_error('Something bad happened. Not sure what exactly', E_USER_ERROR);
            }

            if (!($txp_using_svn && $dbversion_target == $dbupdate)) {
                $dbversion = $dbupdate;
            }
        }
    }

    // Keep track of updates for SVN users.
    safe_delete('txp_prefs', "name = 'dbupdatetime'");
    safe_insert('txp_prefs', "prefs_id = 1, name = 'dbupdatetime', val = '".max(newest_file(), time())."', type = '2'");
} catch (Exception $e) {
    // Nothing to do here, the goal was just to abort the update scripts
    // Error message already communicated via updateErrorHandler
}

restore_error_handler();

// Update version.
safe_delete('txp_prefs', "name = 'version'");
safe_insert('txp_prefs', "prefs_id = 1, name = 'version', val = '$dbversion', type = '2'");

// Invite optional third parties to the update experience
// Convention: Put custom code into file(s) at textpattern/update/custom/post-update-abc-foo.php
// where 'abc' is the third party's reserved prefix (@see http://docs.textpattern.io/development/plugin-developer-prefixes)
// and 'foo' is whatever. The execution order among all files is undefined.
$files = glob(txpath.'/update/custom/post-update*.php');

if (is_array($files)) {
    foreach ($files as $f) {
        include $f;
    }
}

// Updated, baby. So let's get the fresh prefs and send them to languages.
define('TXP_UPDATE_DONE', 1);
$event = 'lang';
$step = 'list_languages';

$prefs = get_prefs();

extract($prefs);

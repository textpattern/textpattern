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

if (!defined('TXP_UPDATE')) {
    exit("Nothing here. You can't access this file directly.");
}

global $thisversion, $dbversion, $txp_is_dev, $dbupdatetime, $app_mode, $event;

$dbupdates = array(
    '4.0.3', '4.0.4', '4.0.5', '4.0.6', '4.0.7', '4.0.8',
    '4.2.0',
    '4.3.0',
    '4.5.0', '4.5.7',
    '4.6.0',
    '4.7.0', '4.7.2',
    '4.8.0', '4.8.4',
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

if ($dbversion == $thisversion ||
    ($txp_is_dev && (newest_file() <= $dbupdatetime))) {
    return;
}

assert_system_requirements();

@ignore_user_abort(1);
@set_time_limit(0);

// Wipe out the last update check setting so the next visit to Diagnostics
// forces an update check, which resets the message. Without this, people who
// upgrade in future may still see a "new version available" message for a
// short time after upgrading.
safe_delete('txp_prefs', "name = 'last_update_check'");

set_error_handler("updateErrorHandler");

$updates = array_fill_keys($dbupdates, true);

if (!isset($updates[$thisversion])) {
    $updates[$thisversion] = false;
}

try {
    $versionparts = explode('-', $thisversion);
    $baseversion = $versionparts[0];

    // Disable no zero dates mode
    if (version_compare($dbversion, '4.6', '<') && $sql_mode = getThing('SELECT @@SESSION.sql_mode')) {
        $tmp_mode = implode(',', array_diff(
            do_list_unique($sql_mode),
            array('NO_ZERO_IN_DATE', 'NO_ZERO_DATE', 'TRADITIONAL')
        ));
        safe_query("SET SESSION sql_mode = '".doSlash($tmp_mode)."'");
    }

    foreach ($updates as $dbupdate => $update) {
        if (version_compare($dbversion, $dbupdate, '<') && version_compare($dbupdate, $baseversion, '<=')) {
            if ($update && (include txpath.DS.'update'.DS.'_to_'.$dbupdate.'.php') === false) {
                trigger_error('Something bad happened. Not sure what exactly', E_USER_ERROR);
            }

            if (!($txp_is_dev && $thisversion == $dbupdate)) {
                $dbversion = $dbupdate;
            }
        }
    }

    // Keep track of updates for SVN users.
    safe_delete('txp_prefs', "name = 'dbupdatetime'");
    safe_insert('txp_prefs', "name = 'dbupdatetime', val = '".max(newest_file(), time())."', type = '2'");

    // Restore sql_mode
    if (!empty($sql_mode)) {
        safe_query("SET SESSION sql_mode = '".doSlash($sql_mode)."'");
    }
} catch (Exception $e) {
    // Nothing to do here, the goal was just to abort the update scripts
    // Error message already communicated via updateErrorHandler
}

// Update any out-of-date installed languages.
// Have to refresh the cache first by reloading everything.
$txpLang = Txp::get('\Textpattern\L10n\Lang');
$installed_langs = $txpLang->available(
    TEXTPATTERN_LANG_INSTALLED | TEXTPATTERN_LANG_ACTIVE,
    TEXTPATTERN_LANG_INSTALLED | TEXTPATTERN_LANG_ACTIVE | TEXTPATTERN_LANG_AVAILABLE
);

foreach ($installed_langs as $lang_code => $info) {
    // Reinstall all languages and update the DB stamps in the cache,
    // just in case we're on the Languages panel so it doesn't report
    // the languages as being stale.
    $txpLang->installFile($lang_code);
    $txpLang->available(TEXTPATTERN_LANG_AVAILABLE, TEXTPATTERN_LANG_INSTALLED | TEXTPATTERN_LANG_AVAILABLE);

    if (get_pref('language_ui') === $lang_code) {
        load_lang($lang_code, $event);
    }
}

restore_error_handler();

// Update version if not dev.
if (!$txp_is_dev) {
    remove_pref('version', 'publish');
    create_pref('version', $dbversion, 'publish', PREF_HIDDEN);

    if (isset($txpcfg['multisite_root_path'])) {
        Txp::get('\Textpattern\Admin\Tools')->removeFiles($txpcfg['multisite_root_path'].DS.'admin', 'setup');
    } else {
        Txp::get('\Textpattern\Admin\Tools')->removeFiles(txpath, 'setup');
    }
}

// Invite optional third parties to the update experience
// Convention: Put custom code into file(s) at textpattern/update/custom/post-update-abc-foo.php
// where 'abc' is the third party's reserved prefix (@see https://docs.textpattern.com/development/plugin-developer-prefixes)
// and 'foo' is whatever. The execution order among all files is undefined.
$files = glob(txpath.'/update/custom/post-update*.php');

if (is_array($files)) {
    foreach ($files as $f) {
        include $f;
    }
}

$js = <<<EOS
    textpattern.Console.addMessage(["A new Textpattern version ($thisversion) has been installed.", 0])
EOS;

if ($app_mode == 'async') {
    send_script_response($js);
} else {
    script_js($js, false);
}

// Updated, baby. So let's get the fresh prefs and send them to Diagnostics.
define('TXP_UPDATE_DONE', 1);

$prefs = get_prefs();
extract($prefs);
/*
$event = 'diag';
$step = 'update';
*/

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

if (!defined('TXP_UPDATE')) {
    exit("Nothing here. You can't access this file directly.");
}

// Remove a few licence files. De-clutters the root directory a tad.
if (is_writable(txpath.DS.'..')) {
    foreach (array('LICENSE-BSD-3', 'LICENSE-LESSER') as $v) {
        $file = txpath.DS.'..'.DS.$v.'.txt';

        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Drop the prefs_id column in txp_prefs
$cols = getThings("DESCRIBE `".PFX."txp_prefs`");
if (in_array('prefs_id', $cols)) {
    safe_drop_index('txp_prefs', 'prefs_idx');
    safe_alter('txp_prefs', "ADD UNIQUE prefs_idx (name(185), user_name)");
    safe_alter('txp_prefs', "DROP prefs_id");
}

// Correct the language designators to become less opinionated.
$available_lang = Txp::get('\Textpattern\L10n\Lang')->available();
$available_keys = array_keys($available_lang);
$installed_lang = Txp::get('\Textpattern\L10n\Lang')->available(TEXTPATTERN_LANG_ACTIVE | TEXTPATTERN_LANG_INSTALLED);
$installed_keys = array_keys($installed_lang);

foreach ($installed_keys as $key) {
    if (!in_array($key, $available_keys)) {
        $newKey = Txp::get('\Textpattern\L10n\Locale')->validLocale($key);
        safe_update('txp_lang', "lang='".doSlash($newKey)."'", "lang='".doSlash($key)."'");
    }
}

// Plugin Textpack
$cols = getThings("DESCRIBE `".PFX."txp_plugin`");
if (! in_array('textpack', $cols)) {
    safe_alter('txp_plugin', "ADD textpack MEDIUMTEXT NOT NULL AFTER code_md5");
    safe_alter('txp_plugin', "ADD data MEDIUMTEXT NOT NULL AFTER textpack");
    safe_alter('txp_plugin', "MODIFY help MEDIUMTEXT NOT NULL");
}

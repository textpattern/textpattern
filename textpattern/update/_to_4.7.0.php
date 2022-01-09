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

// Remove a few licence files. De-clutters the root directory a tad.
Txp::get('\Textpattern\Admin\Tools')->removeFiles(txpath.DS.'..', array('LICENSE-BSD-3.txt', 'LICENSE-LESSER.txt'));
Txp::get('\Textpattern\Admin\Tools')->removeFiles(txpath.DS.'vendors', 'dropbox');
Txp::get('\Textpattern\Admin\Tools')->removeFiles(txpath.DS.'lang', 'en-gb.txt');

// Drop the ip column in txp_discuss
$cols = getThings("DESCRIBE `".PFX."txp_discuss`");

if (in_array('ip', $cols)) {
    safe_alter('txp_discuss', "DROP ip");
}

// Drop the ip and host column in txp_log
$cols = getThings("DESCRIBE `".PFX."txp_log`");

if (in_array('ip', $cols)) {
    safe_drop_index('txp_log', 'ip');
    safe_alter('txp_log', "DROP ip");
}

if (in_array('host', $cols)) {
    safe_alter('txp_log', "DROP host");
}

safe_delete('txp_prefs', "name='use_dns'");

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

        if (get_pref('language') === $key) {
            update_pref('language', $newKey);
        }
    }
}

// New fields in the plugin table.
$colInfo = getRows("DESCRIBE `".PFX."txp_plugin`");
$cols = array_map(function ($el) {
    return $el['Field'];
}, $colInfo);

if (!in_array('data', $cols)) {
    safe_alter('txp_plugin', "ADD data MEDIUMTEXT NOT NULL AFTER code_md5");
}

if (!in_array('textpack', $cols)) {
    safe_alter('txp_plugin', "ADD textpack MEDIUMTEXT NOT NULL AFTER code_md5");
}

// Bigger plugin help text.
$helpCol = array_search('help', $cols);

if (strtolower($colInfo[$helpCol]['Type']) !== 'mediumtext') {
    safe_alter('txp_plugin', "MODIFY help MEDIUMTEXT NOT NULL");
}

// Add theme (skin) support. Note that even though outwardly they're
// referred to as Themes, internally they're known as skins because
// "theme" has already been hijacked by admin-side themes. This
// convention avoids potential name clashes.
safe_create('txp_skin', "
    name        VARCHAR(63)    NOT NULL DEFAULT 'default',
    title       VARCHAR(255)   NOT NULL DEFAULT 'Default',
    version     VARCHAR(255)       NULL DEFAULT '1.0',
    description VARCHAR(10240)     NULL DEFAULT '',
    author      VARCHAR(255)       NULL DEFAULT '',
    author_uri  VARCHAR(255)       NULL DEFAULT '',
    lastmod     DATETIME           NULL DEFAULT NULL,

    PRIMARY KEY (`name`(63))
");

// Add theme support to Pages...
$cols = getThings('describe `'.PFX.'txp_page`');

if (!in_array('lastmod', $cols)) {
    safe_alter('txp_page',
        "ADD lastmod DATETIME DEFAULT NULL AFTER user_html");
}

if (!in_array('skin', $cols)) {
    safe_alter('txp_page',
        "ADD skin VARCHAR(63) NOT NULL DEFAULT 'default' AFTER user_html");
}

safe_drop_index('txp_page', 'primary');
safe_create_index('txp_page', 'name(63), skin(63)', 'name_skin', 'unique');

// ... Forms...
$cols = getThings('describe `'.PFX.'txp_form`');

if (!in_array('lastmod', $cols)) {
    safe_alter('txp_form',
        "ADD lastmod DATETIME DEFAULT NULL AFTER Form");
}

if (!in_array('skin', $cols)) {
    safe_alter('txp_form',
        "ADD skin VARCHAR(63) NOT NULL DEFAULT 'default' AFTER Form");
}

safe_drop_index('txp_form', 'primary');
safe_create_index('txp_form', 'name(63), skin(63)', 'name_skin', 'unique');

// ... Stylesheets...
$cols = getThings('describe `'.PFX.'txp_css`');

if (!in_array('lastmod', $cols)) {
    safe_alter('txp_css',
        "ADD lastmod DATETIME DEFAULT NULL AFTER css");
}

if (!in_array('skin', $cols)) {
    safe_alter('txp_css',
        "ADD skin VARCHAR(63) NOT NULL DEFAULT 'default' AFTER css");
}

safe_drop_index('txp_css', 'name');
safe_create_index('txp_css', 'name(63), skin(63)', 'name_skin', 'unique');

// ... and Sections...
$cols = getThings('describe `'.PFX.'txp_section`');

if (!in_array('skin', $cols)) {
    safe_alter('txp_section',
        "ADD skin VARCHAR(63) NOT NULL DEFAULT 'default' AFTER name");
}

safe_drop_index('txp_section', 'primary');
safe_create_index('txp_section', 'page(50), skin(63)', 'page_skin');
safe_create_index('txp_section', 'css(50), skin(63)', 'css_skin');

$exists = safe_row('name', 'txp_skin', "1=1");

if (!$exists) {
    safe_insert('txp_skin',
        "name = 'default',
        title = 'Default',
        version = '".txp_version."',
        author = 'Team Textpattern',
        author_uri = 'https://textpattern.com/'"
    );
}

// Add theme path pref.
if (!get_pref('skin_dir')) {
    set_pref('skin_dir', 'themes', 'admin', PREF_CORE, 'text_input', 70);
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
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

// Add theme (skin) support. Note that even though outwardly they're
// referred to as Themes, internally they're known as skins because
// "theme" has already been hijacked by admin-side themes. This
// convention avoids potential name clashes.
safe_create('txp_skin',
    "`name` varchar(255) default 'default',
    `title` varchar(255) default 'Default',
    `version` varchar(255) default '1.0',
    `author` varchar(255) default '',
    `website` varchar(255) default '',
    `lastmod` timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`name`)"
);

// Add theme support to Pages...
$cols = getThings('describe `'.PFX.'txp_page`');

if (!in_array('lastmod', $cols)) {
    safe_alter('txp_page',
        "ADD lastmod TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER user_html");
}

if (!in_array('skin', $cols)) {
    safe_alter('txp_page',
        "ADD skin VARCHAR(255) NOT NULL DEFAULT 'default' AFTER user_html");
}

safe_drop_index('txp_page', 'primary');
safe_create_index('txp_page', 'name(50), skin(50)', 'name_skin', 'unique');

// ... Forms...
$cols = getThings('describe `'.PFX.'txp_form`');

if (!in_array('lastmod', $cols)) {
    safe_alter('txp_form',
        "ADD lastmod TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER Form");
}

if (!in_array('skin', $cols)) {
    safe_alter('txp_form',
        "ADD skin VARCHAR(255) NOT NULL DEFAULT 'default' AFTER Form");
}

safe_drop_index('txp_form', 'primary');
safe_create_index('txp_form', 'name(50), skin(50)', 'name_skin', 'unique');

// ... Stylesheets...
$cols = getThings('describe `'.PFX.'txp_css`');

if (!in_array('lastmod', $cols)) {
    safe_alter('txp_css',
        "ADD lastmod TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER css");
}

if (!in_array('skin', $cols)) {
    safe_alter('txp_css',
        "ADD skin VARCHAR(255) NOT NULL DEFAULT 'default' AFTER css");
}

safe_drop_index('txp_css', 'name');
safe_create_index('txp_css', 'name(50), skin(50)', 'name_skin', 'unique');

// ... and Sections...
$cols = getThings('describe `'.PFX.'txp_section`');

if (!in_array('skin', $cols)) {
    safe_alter('txp_section',
        "ADD skin VARCHAR(255) NOT NULL DEFAULT 'default' AFTER name");
}

safe_drop_index('txp_section', 'primary');
safe_create_index('txp_section', 'page(50), skin(50)', 'page_skin');
safe_create_index('txp_section', 'css(50), skin(50)', 'css_skin');

$exists = safe_row('name', 'txp_skin', "1=1");

if (!$exists) {
    safe_insert('txp_skin',
        "name='default',
        title='default',
        version='".txp_version."',
        author='Team Textpattern',
        website='http://textpattern.com/'"
    );
}

// Add theme path pref.
if (!get_pref('skin_base_path')) {
    set_pref('skin_base_path', dirname(txpath).DS.'themes', 'admin', PREF_CORE, 'text_input', 70);
}

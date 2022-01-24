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

// Support for per-user private prefs.
$cols = getThings("DESCRIBE `".PFX."txp_prefs`");
if (!in_array('user_name', $cols)) {
    safe_alter('txp_prefs', "ADD user_name VARCHAR(64) NOT NULL DEFAULT ''");
    safe_create_index('txp_prefs', 'user_name', 'user_name');
}

// Add columns for thumbnail dimensions.
$cols = getThings("DESCRIBE `".PFX."txp_image`");

if (!in_array('thumb_w', $cols)) {
    safe_alter('txp_image', "ADD thumb_w int(8) NOT NULL DEFAULT 0");
}

if (!in_array('thumb_h', $cols)) {
    safe_alter('txp_image', "ADD thumb_h int(8) NOT NULL DEFAULT 0");
}

// Plugin flags.
$cols = getThings('DESCRIBE `'.PFX.'txp_plugin`');

if (!in_array('flags', $cols)) {
    safe_alter('txp_plugin', "ADD flags SMALLINT UNSIGNED NOT NULL DEFAULT 0");
}

safe_alter('txp_plugin', "MODIFY code         MEDIUMTEXT NOT NULL");
safe_alter('txp_plugin', "MODIFY code_restore MEDIUMTEXT NOT NULL");

safe_alter('txp_prefs', "MODIFY val TEXT NOT NULL");

// Add author column to files and links, boldly assuming that the publisher in
// charge of updating this site is the author of any existing content items.
foreach (array('txp_file', 'txp_link') as $table) {
    $cols = getThings("DESCRIBE `".PFX.$table."`");

    if (!in_array('author', $cols)) {
        safe_alter($table, "ADD author varchar(64) NOT NULL DEFAULT ''");
        safe_create_index($table, 'author', 'author_idx');
        safe_update($table, "author = '".doSlash($txp_user)."'", "1 = 1");
    }
}

// Add indices on author columns.
safe_create_index('textpattern', 'AuthorID', 'author_idx');
safe_create_index('txp_image', 'author', 'author_idx');

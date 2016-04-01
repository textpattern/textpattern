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

// Doctype prefs.
if (!safe_field("name", 'txp_prefs', "name = 'doctype'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'doctype', val = 'xhtml', type = '0', event = 'publish', html = 'doctypes', position = '190'");
}

// Publisher's email address.
if (!safe_field("name", 'txp_prefs', "name = 'publisher_email'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'publisher_email', val = '', type = 1, event = 'admin', position = 115");
}
// Goodbye raw ?php support.
if (safe_field("name", 'txp_prefs', "name = 'allow_raw_php_scripting'")) {
    safe_delete('txp_prefs', "name = 'allow_raw_php_scripting'");
}

safe_alter('txp_users', "MODIFY RealName VARCHAR(255) NOT NULL DEFAULT ''");
safe_alter('txp_users', "MODIFY email    VARCHAR(254) NOT NULL DEFAULT ''");

// Remove any setup strings from lang table.
safe_delete('txp_lang', "event = 'setup'");

safe_create_index('textpattern', 'url_title', 'url_title_idx');

// Remove is_default from txp_section table and make it a preference.
$cols = getThings("DESCRIBE `".PFX."txp_section`");
if (!safe_field("name", 'txp_prefs', "name = 'default_section'")) {
    if (in_array('is_default', $cols)) {
        $current_default_section = safe_field("name", 'txp_section', "is_default = 1");
    } else {
        // Nothing we can do. Pick first one.
        $current_default_section = safe_field("name", 'txp_section', "1 LIMIT 1");
    }

    safe_insert('txp_prefs', "prefs_id = 1, name = 'default_section', val = '".doSlash($current_default_section)."', type = '2', event = 'section', html = 'text_input', position = '0'");
}

if (in_array('is_default', $cols)) {
    safe_alter('txp_section', "DROP is_default");
}

safe_alter('txp_css', "MODIFY css MEDIUMTEXT NOT NULL");

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2018 The Textpattern Development Team
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

// Add dev support to Sections.
$cols = getThings('describe `'.PFX.'txp_section`');

foreach (array('skin' => 63, 'page' => 255, 'css' => 255) as $field => $size) {
    if (!in_array('dev_'.$field, $cols)) {
        safe_alter('txp_section',
            "ADD dev_{$field} VARCHAR($size) NOT NULL");
    }
}

// Custom form types.
if (false === ($custom_types = get_pref('custom_form_types', false, true))) {
    set_pref('custom_form_types', 
        ';[js]
;mimetype="application/javascript"
;*="Javascript"',
        'advanced_options', PREF_CORE, 'longtext_input', 100, PREF_GLOBAL);
} else {
    safe_update('txp_prefs', "event = 'advanced_options'", "name='custom_form_types'");
    $custom_types = parse_ini_string($custom_types);
}

if ($mimetypes = parse_ini_string(get_pref('assets_mimetypes', '', true))) {
    foreach ($mimetypes as $ext => $type) {
        if (!isset($custom_types[$ext])) {
            $prefs['custom_form_types'] .= n."[$ext]".n.'mimetype="'.$type.'"';
        }
    }

    safe_update('txp_prefs', "val = '".doSlash($prefs['custom_form_types'])."'", "name='custom_form_types'");
}

safe_delete('txp_prefs', "name='assets_mimetypes'");

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

// Mimetypes.
safe_update('txp_prefs', "event = 'advanced_options'", "name='assets_mimetypes'");
safe_update('txp_prefs', "event = 'advanced_options'", "name='custom_form_types'");

if (!get_pref('assets_mimetypes', false, true)) {
    set_pref('assets_mimetypes', $prefs['assets_mimetypes'] =
';css="text/css"
;htm="text/html"
;txt="text/plain"
;js="application/javascript"
;json="application/json"
;svg="image/svg+xml"
;xml="application/xml"',
        'advanced_options', PREF_CORE, 'longtext_input', 200, PREF_GLOBAL);
}

// Custom form types.
if (!get_pref('custom_form_types', false, true)) {
    set_pref('custom_form_types', $prefs['custom_form_types'] =
';[custom]
;*="Custom"',
        'advanced_options', PREF_CORE, 'longtext_input', 100, PREF_GLOBAL);
}

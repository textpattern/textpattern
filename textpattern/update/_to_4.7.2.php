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

// Add dev support to Sections...
$cols = getThings('describe `'.PFX.'txp_section`');

foreach (array('skin' => 63, 'page' => 255, 'css' => 255) as $field => $size) {
    if (!in_array('dev_'.$field, $cols)) {
        safe_alter('txp_section',
            "ADD dev_{$field} VARCHAR($size) NOT NULL");
    }
}

// Mimetypes
if (!isset( $prefs['assets_mimetypes'])) {
    set_pref('assets_mimetypes', $prefs['assets_mimetypes'] =
';css="text/css"
;js="application/javascript"
;json="application/json"
;svg="image/svg+xml"
;xml="application/xml"
;txt="text/plain"
;csv="text/csv"
;htm="text/html"
;html="text/html"',
        'publish', PREF_CORE, 'longtext_input', 360, PREF_GLOBAL);
}
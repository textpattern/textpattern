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

if (!safe_field("name", 'txp_prefs', "name = 'allow_raw_php_scripting'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'allow_raw_php_scripting', val = '1', type = '1', html = 'yesnoradio'");
} else {
    safe_update('txp_prefs', "html = 'yesnoradio'", "name = 'allow_raw_php_scripting'");
}

if (!safe_field("name", 'txp_prefs', "name = 'log_list_pageby'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'log_list_pageby', val = '25', type = 2, event = 'publish'");
}

// Turn on lastmod handling, and reset the lastmod date.
safe_update('txp_prefs', "val = '1'", "name = 'send_lastmod' AND prefs_id = '1'");
update_lastmod();

// Speed up article queries.
safe_create_index('textpattern', 'Section, Status', 'section_status_idx');

if (!safe_field("name", 'txp_prefs', "name = 'title_no_widow'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'title_no_widow', val = '0', type = '1', html = 'yesnoradio'");
}

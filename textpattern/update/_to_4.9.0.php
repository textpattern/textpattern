<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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

safe_update('txp_prefs', "name = 'spam_blocklists'", "name = 'spam_blacklists'");

$cols = getThings('describe `'.PFX.'txp_prefs`');

if (!in_array('collection', $cols)) {
    safe_alter('txp_prefs',
        "ADD collection VARCHAR(255) NOT NULL DEFAULT '' AFTER event");
}

// Populate new Mail subsection in Prefs, migrating some prefs there.
safe_update('txp_prefs', "event = 'mail'", "name IN('smtp_from', 'publisher_email', 'override_emailcharset') AND event='admin'");

if (get_pref('html_email', null, true) === null) {
    set_pref('html_email', '0', 'mail', PREF_CORE, 'html_email', 125, PREF_GLOBAL);
}
if (get_pref('enhanced_email', null, true) === null) {
    set_pref('enhanced_email', '0', 'mail', PREF_CORE, 'enhanced_email', 150, PREF_GLOBAL);
}
if (get_pref('smtp_host', null, true) === null) {
    set_pref('smtp_host', '', array('mail', 'mail_enhanced'), PREF_CORE, 'smtp_handler', 160, PREF_GLOBAL);
}
if (get_pref('smtp_port', null, true) === null) {
    set_pref('smtp_port', '587', array('mail', 'mail_enhanced'), PREF_CORE, 'smtp_handler', 170, PREF_GLOBAL);
}
if (get_pref('smtp_user', null, true) === null) {
    set_pref('smtp_user', '', array('mail', 'mail_enhanced'), PREF_CORE, 'smtp_handler', 180, PREF_GLOBAL);
}
if (get_pref('smtp_pass', null, true) === null) {
    set_pref('smtp_pass', '', array('mail', 'mail_enhanced'), PREF_CORE, 'smtp_handler', 190, PREF_GLOBAL);
}
if (get_pref('smtp_sectype', null, true) === null) {
    set_pref('smtp_sectype', 'ssl', array('mail', 'mail_enhanced'), PREF_CORE, 'smtp_handler', 200, PREF_GLOBAL);
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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

$smtp_prefs = array(
    'enhanced_email' => array(
        'val'        => '0',
        'event'      => 'mail',
        'html'       => 'enhanced_email',
        'position'   => 150,
    ),
    'smtp_host'   => array(
        'val'      => '',
        'event'    => array('mail', 'mail_enhanced'),
        'html'     => 'smtp_handler',
        'position' => 160,
    ),
    'smtp_port'   => array(
        'val'      => '587',
        'event'    => array('mail', 'mail_enhanced'),
        'html'     => 'smtp_handler',
        'position' => 170,
    ),
    'smtp_user'   => array(
        'val'      => '',
        'event'    => array('mail', 'mail_enhanced'),
        'html'     => 'smtp_handler',
        'position' => 180,
    ),
    'smtp_pass'   => array(
        'val'      => '',
        'event'    => array('mail', 'mail_enhanced'),
        'html'     => 'smtp_handler',
        'position' => 190,
    ),
    'smtp_sectype'   => array(
        'val'        => 'ssl',
        'event'      => array('mail', 'mail_enhanced'),
        'html'       => 'smtp_handler',
        'position'   => 200,
    ),
);

$new_prefs = array(
    'trailing_slash' => array(
        'val'        => '0',
        'event'      => 'site',
        'html'       => 'trailing_slash',
        'position'   => 185,
    ),
    'file_download_header'=> array(
        'val'      => '',
        'event'    => 'advanced_options',
        'html'     => 'longtext_input',
        'position' => 250
    ),
);

foreach ($smtp_prefs + $new_prefs as $prefname => $block) {
    if (get_pref($prefname, null) === null) {
        create_pref($prefname, $block['val'], $block['event'], PREF_CORE, $block['html'], $block['position'], PREF_GLOBAL);
    } else {
        update_pref($prefname, null, $block['event'], PREF_CORE, $block['html'], $block['position'], PREF_GLOBAL);
    }
}

// Ensure all tables have primary keys.
$primaries = array('css', 'form', 'page');

foreach ($primaries as $table) {
    safe_drop_index('txp_'.$table, 'name_skin');
    safe_create_index('txp_'.$table, 'name(63), skin(63)', 'primary');
}

$primaries = array('plugin', 'section');

foreach ($primaries as $table) {
    safe_drop_index('txp_'.$table, 'name');
    safe_create_index('txp_'.$table, 'name(63)', 'primary');
}

safe_drop_index('txp_prefs', 'prefs_idx');
safe_create_index('txp_prefs', 'name(185), user_name', 'primary');

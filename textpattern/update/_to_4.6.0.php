<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2014 The Textpattern Development Team
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

safe_alter('textpattern', "CHANGE COLUMN `textile_body` `textile_body` VARCHAR(32) NOT NULL DEFAULT '1', CHANGE COLUMN `textile_excerpt` `textile_excerpt` VARCHAR(32) NOT NULL DEFAULT '1';");
safe_update('txp_prefs', "name = 'pane_article_textfilter_help_visible'", "name = 'pane_article_textile_help_visible'");

// Rejig preferences panel.
$core_ev = doQuote(join("','", array('site', 'admin', 'publish', 'feeds', 'custom', 'comments')));

// 1) Increase event column size.
safe_alter('txp_prefs', "MODIFY event VARCHAR(255) NOT NULL default 'publish', MODIFY html VARCHAR(255) NOT NULL default 'text_input'");

// 2) Remove basic/advanced distinction.
safe_update('txp_prefs', "type = '".PREF_CORE."'", "type = '".PREF_PLUGIN."' AND event IN (".$core_ev.")");

// 3) Consolidate existing prefs into better groups.
safe_update('txp_prefs', "event = 'site'", "name in ('sitename', 'siteurl', 'site_slogan', 'production_status', 'gmtoffset', 'auto_dst', 'is_dst', 'dateformat', 'archive_dateformat', 'permlink_mode', 'doctype', 'logging', 'use_comments', 'expire_logs_after')");

// 4) Reorder existing prefs into a more logical progression.
safe_update('txp_prefs', "position = '230'", "name = 'expire_logs_after'");
safe_update('txp_prefs', "position = '340'", "name = 'max_url_len'");
safe_update('txp_prefs', "position = '160'", "name = 'comments_sendmail'");
safe_update('txp_prefs', "position = '180'", "name = 'comments_are_ol'");
safe_update('txp_prefs', "position = '200'", "name = 'comment_means_site_updated'");
safe_update('txp_prefs', "position = '220'", "name = 'comments_require_name'");
safe_update('txp_prefs', "position = '240'", "name = 'comments_require_email'");
safe_update('txp_prefs', "position = '260'", "name = 'never_display_email'");
safe_update('txp_prefs', "position = '280'", "name = 'comment_nofollow'");
safe_update('txp_prefs', "position = '300'", "name = 'comments_disallow_images'");
safe_update('txp_prefs', "position = '320'", "name = 'comments_use_fat_textile'");
safe_update('txp_prefs', "position = '340'", "name = 'spam_blacklists'");

// Support for l10n string owners.
$cols = getThings('describe `'.PFX.'txp_lang`');

if (!in_array('owner', $cols)) {
    safe_alter('txp_lang',
        "ADD owner VARCHAR(64) NOT NULL DEFAULT '' AFTER event, ADD INDEX owner (owner)");
}

// Keep all comment-related forms together. The loss of 'preview' ability on the
// comments_display Form is of little consequence compared with the benefit of
// tucking them away neatly when not required.
safe_update('txp_form', "type = 'comment'", "name = 'comments_display'");

// Adds protocol to logged HTTP referers.
safe_update(
    'txp_log',
    "refer = CONCAT('http://', refer)",
    "refer != '' and refer NOT LIKE 'http://%' and refer NOT LIKE 'https://%'"
);

// Usernames can be 64 characters long at most.
safe_alter('txp_file', "MODIFY author VARCHAR(64) NOT NULL default ''");
safe_alter('txp_image', "MODIFY author VARCHAR(64) NOT NULL default ''");

// Consistent name length limitations for presentation items.
safe_alter('txp_form', "MODIFY name VARCHAR(255) NOT NULL");
safe_alter('txp_page', "MODIFY name VARCHAR(255) NOT NULL");
safe_alter('txp_section', "MODIFY page VARCHAR(255) NOT NULL default '', MODIFY css VARCHAR(255) NOT NULL default ''");

// Save sections correctly in articles.
safe_alter('textpattern', "MODIFY Section VARCHAR(255) NOT NULL default ''");
safe_alter('txp_section', "MODIFY name VARCHAR(255) NOT NULL");

// Plugins can have longer version numbers.
safe_alter('txp_plugin', "MODIFY version VARCHAR(255) NOT NULL DEFAULT '1.0'");

// Translation strings should allow more than 255 characters.
safe_alter('txp_lang', "MODIFY data TEXT");

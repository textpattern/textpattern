<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2015 The Textpattern Development Team
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

// Add meta description to articles...
$cols = getThings('describe `'.PFX.'textpattern`');

if (!in_array('description', $cols)) {
    safe_alter('textpattern',
        "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER Keywords");
}

// ... categories...
$cols = getThings('describe `'.PFX.'txp_category`');

if (!in_array('description', $cols)) {
    safe_alter('txp_category',
        "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER title");
}

// ... and sections.
$cols = getThings('describe `'.PFX.'txp_section`');

if (!in_array('description', $cols)) {
    safe_alter('txp_section',
        "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER css");
}

// Remove textpattern.com ping pref.
if (safe_field('name', 'txp_prefs', "name = 'ping_textpattern_com'")) {
    safe_delete('txp_prefs', "name = 'ping_textpattern_com'");
}

// Add default publishing status pref.
if (!get_pref('default_publish_status')) {
    set_pref('default_publish_status', STATUS_LIVE, 'publish', PREF_CORE, 'defaultPublishStatus', 15, PREF_PRIVATE);
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
safe_create_index('txp_page', 'name(15), skin(15)', 'name_skin', 'unique');

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
safe_create_index('txp_form', 'name(15), skin(15)', 'name_skin', 'unique');

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
safe_create_index('txp_css', 'name(15), skin(15)', 'name_skin', 'unique');

// ... and Sections...
$cols = getThings('describe `'.PFX.'txp_section`');

if (!in_array('skin', $cols)) {
    safe_alter('txp_section',
        "ADD skin VARCHAR(255) NOT NULL DEFAULT 'default' AFTER name");
}

safe_drop_index('txp_section', 'primary');
safe_create_index('txp_section', 'page(15), skin(15)', 'page_skin');
safe_create_index('txp_section', 'css(15), skin(15)', 'css_skin');

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

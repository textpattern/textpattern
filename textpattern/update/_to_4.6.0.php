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

safe_alter('textpattern', "
    CHANGE COLUMN textile_body    textile_body    VARCHAR(32) NOT NULL DEFAULT '1',
    CHANGE COLUMN textile_excerpt textile_excerpt VARCHAR(32) NOT NULL DEFAULT '1'");
safe_update('txp_prefs', "name = 'pane_article_textfilter_help_visible'", "name = 'pane_article_textile_help_visible'");

// Rejig preferences panel.
$core_ev = doQuote(join("','", array('site', 'admin', 'publish', 'feeds', 'custom', 'comments')));

// 1) Increase event column size.
safe_alter('txp_prefs', "
    MODIFY event VARCHAR(255) NOT NULL DEFAULT 'publish',
    MODIFY html  VARCHAR(255) NOT NULL DEFAULT 'text_input'");

// 2) Remove basic/advanced distinction.
safe_update('txp_prefs', "type = '".PREF_CORE."'", "type = '".PREF_PLUGIN."' AND event IN (".$core_ev.")");

// 3) Consolidate existing prefs into better groups.
safe_update('txp_prefs', "event = 'site'", "name IN ('sitename', 'siteurl', 'site_slogan', 'production_status', 'gmtoffset', 'auto_dst', 'is_dst', 'dateformat', 'archive_dateformat', 'permlink_mode', 'doctype', 'logging', 'use_comments', 'expire_logs_after')");

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
safe_update('txp_prefs', "html = 'permlink_format'", "name = 'permalink_title_format'");

// Support for l10n string owners.
$cols = getThings("DESCRIBE `".PFX."txp_lang`");

if (!in_array('owner', $cols)) {
    safe_alter('txp_lang', "
        ADD owner VARCHAR(64) NOT NULL DEFAULT '' AFTER event,
        ADD INDEX owner (owner)");
}

// Keep all comment-related forms together. The loss of 'preview' ability on the
// comments_display Form is of little consequence compared with the benefit of
// tucking them away neatly when not required.
safe_update('txp_form', "type = 'comment'", "name = 'comments_display'");

// Adds protocol to logged HTTP referers.
safe_update(
    'txp_log',
    "refer = CONCAT('http://', refer)",
    "refer != '' AND refer NOT LIKE 'http://%' AND refer NOT LIKE 'https://%'"
);

// Usernames can be 64 characters long at most.
safe_alter('txp_file',  "MODIFY author VARCHAR(64) NOT NULL DEFAULT ''");
safe_alter('txp_link',  "MODIFY author VARCHAR(64) NOT NULL DEFAULT ''");
safe_alter('txp_image', "MODIFY author VARCHAR(64) NOT NULL DEFAULT ''");

// Consistent name length limitations for presentation items.
safe_alter('txp_form', "MODIFY name VARCHAR(255) NOT NULL DEFAULT ''");
safe_alter('txp_page', "MODIFY name VARCHAR(255) NOT NULL DEFAULT ''");
safe_alter('txp_section', "
    MODIFY page VARCHAR(255) NOT NULL DEFAULT '',
    MODIFY css  VARCHAR(255) NOT NULL DEFAULT ''");

// Save sections correctly in articles.
safe_alter('textpattern', "MODIFY Section VARCHAR(255) NOT NULL DEFAULT ''");
safe_alter('txp_section', "MODIFY name    VARCHAR(255) NOT NULL DEFAULT ''");

// Plugins can have longer version numbers.
safe_alter('txp_plugin', "MODIFY version VARCHAR(255) NOT NULL DEFAULT '1.0'");

// Translation strings should allow more than 255 characters.
safe_alter('txp_lang', "MODIFY data TEXT");

// Add meta description to articles...
$cols = getThings("DESCRIBE `".PFX."textpattern`");

if (!in_array('description', $cols)) {
    safe_alter('textpattern', "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER Keywords");
}

// ... categories...
$cols = getThings("DESCRIBE `".PFX."txp_category`");

if (!in_array('description', $cols)) {
    safe_alter('txp_category', "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER title");
}

// ... and sections.
$cols = getThings("DESCRIBE `".PFX."txp_section`");

if (!in_array('description', $cols)) {
    safe_alter('txp_section', "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER css");
}

// Remove textpattern.com ping pref.
if (safe_field('name', 'txp_prefs', "name = 'ping_textpattern_com'")) {
    safe_delete('txp_prefs', "name = 'ping_textpattern_com'");
}

// Add default publishing status pref.
if (!get_pref('default_publish_status')) {
    set_pref('default_publish_status', STATUS_LIVE, 'publish', PREF_CORE, 'defaultPublishStatus', 15, PREF_PRIVATE);
}

// Remove broken import functionality
if (file_exists(txpath.DS.'include'.DS.'txp_import.php')) {
    $import_files = array(
        'BloggerImportTemplate.txt',
        'import_blogger.php',
        'import_mt.php',
        'import_b2.php',
        'import_mtdb.php',
        'import_wp.php'
    );

    foreach($import_files as $file) {
        unlink(txpath.DS.'include'.DS.'import'.DS.$file);
    }

    rmdir(txpath.DS.'include'.DS.'import');
    unlink(txpath.DS.'include'.DS.'txp_import.php');
}

// Remove unused ipban table or recreate its index (for future utf8mb4 conversion)
if (getThing("SHOW TABLES LIKE '".PFX."txp_discuss_ipban'")) {
    if (!safe_count('txp_discuss_ipban', '1 = 1')) {
        safe_drop('txp_discuss_ipban');
    } else {
        safe_alter('txp_discuss_ipban', "DROP PRIMARY KEY, ADD PRIMARY KEY (ip(250))");
    }
}

// Recreate indexes with smaller key sizes to allow future conversion to charset utf8mb4
safe_alter('txp_css',     "DROP INDEX name,               ADD UNIQUE name (name(250))");
safe_alter('txp_file',    "DROP INDEX filename,           ADD UNIQUE filename (filename(250))");
safe_alter('txp_form',    "DROP PRIMARY KEY,              ADD PRIMARY KEY (name(250))");
safe_alter('txp_page',    "DROP PRIMARY KEY,              ADD PRIMARY KEY (name(250))");
safe_alter('txp_section', "DROP PRIMARY KEY,              ADD PRIMARY KEY (name(250))");
safe_alter('txp_prefs',   "DROP INDEX prefs_idx,          ADD UNIQUE prefs_idx (prefs_id, name(185), user_name)");
safe_alter('txp_prefs',   "DROP INDEX name,               ADD INDEX name (name(250))");
safe_alter('textpattern', "DROP INDEX section_status_idx, ADD INDEX section_status_idx (Section(249), Status)");
safe_alter('textpattern', "DROP INDEX url_title_idx,      ADD INDEX url_title_idx (url_title(250))");
// txp_discuss_nonce didn't have a primary key in 4.0.3, so we recreate its index in two steps
safe_drop_index('txp_discuss_nonce', "PRIMARY");
safe_alter('txp_discuss_nonce', "ADD PRIMARY KEY (nonce(250))");

// Fix typo: textinput should be text_input
safe_update('txp_prefs', "html = 'text_input'", "name = 'timezone_key'");

// Fix typo: position 40 should be 0 (because it's a hidden pref)
safe_update('txp_prefs', "position = 0", "name = 'language'");

// Fix typo: position should be 60 instead of 30 (so it appears just below the site name)
safe_update('txp_prefs', "position = 60", "name = 'site_slogan'");

// Enforce some table changes that happened after 4.0.3 but weren't part of update scripts until now
safe_alter('txp_css',  "MODIFY name  VARCHAR(255) NOT NULL");
safe_alter('txp_lang', "MODIFY lang  VARCHAR(16)  NOT NULL");
safe_alter('txp_lang', "MODIFY name  VARCHAR(64)  NOT NULL");
safe_alter('txp_lang', "MODIFY event VARCHAR(64)  NOT NULL");
safe_drop_index('txp_form', "name");
safe_drop_index('txp_page', "name");
safe_drop_index('txp_plugin', "name_2");
safe_drop_index('txp_section', "name");

// The txp_priv table was created for version 1.0, but never used nor created in later versions.
safe_drop('txp_priv');

// Add generic token table.
safe_create('txp_token',"
id           INT          NOT NULL AUTO_INCREMENT,
reference_id INT          DEFAULT 0,
type         VARCHAR(255) DEFAULT '',
selector     CHAR(12)     DEFAULT '',
token        VARCHAR(255) DEFAULT '',
expires      DATETIME     DEFAULT '0000-00-00 00:00:00',
PRIMARY KEY (id)
"
);
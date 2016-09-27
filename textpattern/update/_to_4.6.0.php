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

safe_alter('textpattern', "MODIFY textile_body    VARCHAR(32) NOT NULL DEFAULT '1'");
safe_alter('textpattern', "MODIFY textile_excerpt VARCHAR(32) NOT NULL DEFAULT '1'");
safe_update('txp_prefs', "name = 'pane_article_textfilter_help_visible'", "name = 'pane_article_textile_help_visible'");

// Rejig preferences panel.
$core_ev = join(',', quote_list(array('site', 'admin', 'publish', 'feeds', 'custom', 'comments')));

// 1) Increase event column size.
safe_alter('txp_prefs', "MODIFY event VARCHAR(255) NOT NULL DEFAULT 'publish'");
safe_alter('txp_prefs', "MODIFY html  VARCHAR(255) NOT NULL DEFAULT 'text_input'");

// 2) Remove basic/advanced distinction.
safe_update('txp_prefs', "type = '".PREF_CORE."'", "type = '".PREF_PLUGIN."' AND event IN ($core_ev)");

// 3) Consolidate existing prefs into better groups.
safe_update('txp_prefs', "event = 'site'", "name IN ('sitename', 'siteurl', 'site_slogan', 'production_status', 'gmtoffset', 'auto_dst', 'is_dst', 'dateformat', 'archive_dateformat', 'permlink_mode', 'doctype', 'logging', 'use_comments', 'expire_logs_after')");

// 4) Reorder existing prefs into a more logical progression.
safe_update('txp_prefs', "position = '110'", "name = 'gmtoffset'");
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
safe_update('txp_prefs', "name = 'permlink_format', html = 'permlink_format'", "name = 'permalink_title_format'");

// Support for l10n string owners.
$cols = getThings("DESCRIBE `".PFX."txp_lang`");

if (!in_array('owner', $cols)) {
    safe_alter('txp_lang', "ADD owner VARCHAR(64) NOT NULL DEFAULT '' AFTER event");
    safe_create_index('txp_lang', 'owner', 'owner');
}

// Keep all comment-related forms together. The loss of 'preview' ability on the
// comments_display Form is of little consequence compared with the benefit of
// tucking them away neatly when not required.
safe_update('txp_form', "type = 'comment'", "name = 'comments_display'");

// Add protocol to logged HTTP referrers.
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
safe_alter('txp_form',    "MODIFY name VARCHAR(255) NOT NULL DEFAULT ''");
safe_alter('txp_page',    "MODIFY name VARCHAR(255) NOT NULL DEFAULT ''");
safe_alter('txp_section', "MODIFY page VARCHAR(255) NOT NULL DEFAULT ''");
safe_alter('txp_section', "MODIFY css  VARCHAR(255) NOT NULL DEFAULT ''");

// Save sections correctly in articles.
safe_alter('textpattern', "MODIFY Section VARCHAR(255) NOT NULL DEFAULT ''");
safe_alter('txp_section', "MODIFY name    VARCHAR(255) NOT NULL DEFAULT ''");

// Plugins can have longer version numbers.
safe_alter('txp_plugin', "MODIFY version VARCHAR(255) NOT NULL DEFAULT '1.0'");

// Translation strings should allow more than 255 characters.
safe_alter('txp_lang', "MODIFY data TEXT");

// Add meta description to articles.
$cols = getThings("DESCRIBE `".PFX."textpattern`");

if (!in_array('description', $cols)) {
    safe_alter('textpattern', "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER Keywords");
}

// Add meta description to categories.
$cols = getThings("DESCRIBE `".PFX."txp_category`");

if (!in_array('description', $cols)) {
    safe_alter('txp_category', "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER title");
}

// Add meta description to sections.
$cols = getThings("DESCRIBE `".PFX."txp_section`");

if (!in_array('description', $cols)) {
    safe_alter('txp_section', "ADD description VARCHAR(255) NOT NULL DEFAULT '' AFTER css");
}

// Remove textpattern.com ping and lastmod_keepalive prefs.
safe_delete('txp_prefs', "name = 'ping_textpattern_com'");
safe_delete('txp_prefs', "name = 'lastmod_keepalive'");

// Add default publishing status pref.
if (!get_pref('default_publish_status')) {
    set_pref('default_publish_status', STATUS_LIVE, 'publish', PREF_CORE, 'defaultPublishStatus', 15, PREF_PRIVATE);
}

// Add prefs to allow query caching when now() is used.
if (!get_pref('sql_now_posted')) {
    set_pref('sql_now_posted', time(), 'publish', PREF_HIDDEN);
    set_pref('sql_now_expires', time(), 'publish', PREF_HIDDEN);
    set_pref('sql_now_created', time(), 'publish', PREF_HIDDEN);
}

// Remove broken import functionality.
if (is_writable(txpath.DS.'include') && file_exists(txpath.DS.'include'.DS.'txp_import.php')) {
    $import_files = array(
        'BloggerImportTemplate.txt',
        'import_blogger.php',
        'import_mt.php',
        'import_b2.php',
        'import_mtdb.php',
        'import_wp.php'
    );

    if (is_writable(txpath.DS.'include'.DS.'import')) {
        foreach ($import_files as $file) {
            if (file_exists(txpath.DS.'include'.DS.'import'.DS.$file)) {
                unlink(txpath.DS.'include'.DS.'import'.DS.$file);
            }
        }
        rmdir(txpath.DS.'include'.DS.'import');
    }

    unlink(txpath.DS.'include'.DS.'txp_import.php');
}

// Remove unused ipban table or recreate its index (for future utf8mb4 conversion).
if (getThing("SHOW TABLES LIKE '".PFX."txp_discuss_ipban'")) {
    if (!safe_count('txp_discuss_ipban', "1 = 1")) {
        safe_drop('txp_discuss_ipban');
    } else {
        safe_drop_index('txp_discuss_ipban', "PRIMARY");
        safe_alter('txp_discuss_ipban', "ADD PRIMARY KEY (ip(250))");
    }
}

// Recreate indexes with smaller key sizes to allow future conversion to charset utf8mb4.
safe_drop_index('txp_css',     "name");
safe_drop_index('txp_file',    "filename");
safe_drop_index('txp_form',    "PRIMARY");
safe_drop_index('txp_page',    "PRIMARY");
safe_drop_index('txp_section', "PRIMARY");
safe_drop_index('txp_prefs',   "prefs_idx");
safe_drop_index('txp_prefs',   "name");
safe_drop_index('textpattern', "section_status_idx");
safe_drop_index('textpattern', "url_title_idx");
// Not using safe_create_index here, because we just dropped the index.
safe_alter('txp_css',     "ADD UNIQUE name (name(250))");
safe_alter('txp_file',    "ADD UNIQUE filename (filename(250))");
safe_alter('txp_form',    "ADD PRIMARY KEY (name(250))");
safe_alter('txp_page',    "ADD PRIMARY KEY (name(250))");
safe_alter('txp_section', "ADD PRIMARY KEY (name(250))");
safe_alter('txp_prefs',   "ADD UNIQUE prefs_idx (prefs_id, name(185), user_name)");
safe_alter('txp_prefs',   "ADD INDEX name (name(250))");
safe_alter('textpattern', "ADD INDEX section_status_idx (Section(249), Status)");
safe_alter('textpattern', "ADD INDEX url_title_idx (url_title(250))");
// Specifically, txp_discuss_nonce didn't have a primary key in 4.0.3
// so it has to be done in two separate steps.
safe_drop_index('txp_discuss_nonce', "PRIMARY");
safe_alter('txp_discuss_nonce', "ADD PRIMARY KEY (nonce(250))");

// Fix typo: textinput should be text_input.
safe_update('txp_prefs', "html = 'text_input'", "name = 'timezone_key'");

// Fix typo: position 40 should be 0 (because it's a hidden pref).
safe_update('txp_prefs', "position = 0", "name = 'language'");

// Fix typo: position should be 60 instead of 30 (so it appears just below the site name).
safe_update('txp_prefs', "position = 60", "name = 'site_slogan'");

// Enforce some table changes that happened after 4.0.3 but weren't part of
// update scripts until now.
safe_alter('txp_css',  "MODIFY name  VARCHAR(255) NOT NULL");
safe_alter('txp_lang', "MODIFY lang  VARCHAR(16)  NOT NULL");
safe_alter('txp_lang', "MODIFY name  VARCHAR(64)  NOT NULL");
safe_alter('txp_lang', "MODIFY event VARCHAR(64)  NOT NULL");
safe_drop_index('txp_form', "name");
safe_drop_index('txp_page', "name");
safe_drop_index('txp_plugin', "name_2");
safe_drop_index('txp_section', "name");

// The txp_priv table was created for version 1.0, but never used nor created in
// later versions.
safe_drop('txp_priv');

// Remove empty update files.
if (is_writable(txpath.DS.'update')) {
    foreach (array('4.4.0', '4.4.1') as $v) {
        $file = txpath.DS.'update'.DS.'_to_'.$v.'.php';

        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Remove unnecessary licence files that have been moved to root.
if (is_writable(txpath)) {
    foreach (array('license', 'lgpl-2.1') as $v) {
        $file = txpath.DS.$v.'.txt';

        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Add generic token table (dropping first, because of changes to the table setup).
safe_drop('txp_token');
safe_create('txp_token', "
    id           INT          NOT NULL AUTO_INCREMENT,
    reference_id INT          NOT NULL,
    type         VARCHAR(255) NOT NULL,
    selector     VARCHAR(12)  NOT NULL DEFAULT '',
    token        VARCHAR(255) NOT NULL,
    expires      DATETIME         NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE INDEX ref_type (reference_id, type(50))
");

// Remove default zero dates to make MySQL 5.7 happy.
safe_alter('textpattern',       "MODIFY Posted      DATETIME NOT NULL");
safe_alter('textpattern',       "MODIFY Expires     DATETIME     NULL DEFAULT NULL");
safe_alter('textpattern',       "MODIFY LastMod     DATETIME NOT NULL");
safe_alter('textpattern',       "MODIFY feed_time   DATE     NOT NULL"); //0000-00-00
safe_alter('txp_discuss',       "MODIFY posted      DATETIME NOT NULL");
safe_alter('txp_discuss_nonce', "MODIFY issue_time  DATETIME NOT NULL");
safe_alter('txp_file',          "MODIFY created     DATETIME NOT NULL");
safe_alter('txp_file',          "MODIFY modified    DATETIME NOT NULL");
safe_alter('txp_image',         "MODIFY date        DATETIME NOT NULL");
safe_alter('txp_link',          "MODIFY date        DATETIME NOT NULL");
safe_alter('txp_log',           "MODIFY time        DATETIME NOT NULL");
safe_alter('txp_users',         "MODIFY last_access DATETIME     NULL DEFAULT NULL");
// Remove logs and nonces with zero dates.
safe_delete('txp_discuss_nonce', "DATE(issue_time) = '0000-00-00'");
safe_delete('txp_log',           "DATE(time)       = '0000-00-00'");
// Replace zero dates (which shouldn't exist, really) with somewhat sensible values.
safe_update('textpattern', "Posted      = NOW()",   "DATE(Posted)      = '0000-00-00'");
safe_update('textpattern', "Expires     = NULL",    "DATE(Expires)     = '0000-00-00'");
safe_update('textpattern', "LastMod     = Posted",  "DATE(LastMod)     = '0000-00-00'");
safe_update('txp_discuss', "posted      = NOW()",   "DATE(posted)      = '0000-00-00'");
safe_update('txp_file',    "created     = NOW()",   "DATE(created)     = '0000-00-00'");
safe_update('txp_file',    "modified    = created", "DATE(modified)    = '0000-00-00'");
safe_update('txp_image',   "date        = NOW()",   "DATE(date)        = '0000-00-00'");
safe_update('txp_link',    "date        = NOW()",   "DATE(date)        = '0000-00-00'");
safe_update('txp_users',   "last_access = NULL",    "DATE(last_access) = '0000-00-00'");
safe_update('textpattern', "feed_time   = DATE(Posted)", "feed_time    = '0000-00-00'");

// Category names are max 64 characters when created/edited, so don't pretend
// they can be longer.
safe_alter('textpattern', "MODIFY Category1 VARCHAR(64) NOT NULL DEFAULT ''");
safe_alter('textpattern', "MODIFY Category2 VARCHAR(64) NOT NULL DEFAULT ''");
safe_alter('txp_file',    "MODIFY category  VARCHAR(64) NOT NULL DEFAULT ''");
safe_alter('txp_image',   "MODIFY category  VARCHAR(64) NOT NULL DEFAULT ''");

// Farewell Classic and Remora themes.
$availableThemes = \Textpattern\Admin\Theme::names();

if (!in_array(get_pref('theme_name'), $availableThemes)) {
    set_pref('theme_name', 'hive');
}

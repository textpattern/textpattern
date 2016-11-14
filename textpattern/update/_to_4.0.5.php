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

safe_alter('txp_lang', 'DELAY_KEY_WRITE = 0');

// New status field for file downloads.
$txpfile = getThings("DESCRIBE `".PFX."txp_file`");

if (!in_array('status', $txpfile)) {
    safe_alter('txp_file', "ADD status SMALLINT NOT NULL DEFAULT '4'");
}

if (!in_array('modified', $txpfile)) {
    safe_alter('txp_file', "ADD modified DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'");
}

safe_alter('txp_file', "MODIFY modified DATETIME NOT NULL");

if (!in_array('created', $txpfile)) {
    safe_alter('txp_file', "ADD created DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'");
}

safe_alter('txp_file', "MODIFY created DATETIME NOT NULL");

if (!in_array('size', $txpfile)) {
    safe_alter('txp_file', "ADD size BIGINT");
}

if (!in_array('downloads', $txpfile)) {
    safe_alter('txp_file', "ADD downloads INT DEFAULT '0' NOT NULL");
}

$txpfile = getThings("DESCRIBE `".PFX."txp_file`");

// Copy existing file timestamps into the new database columns.
if (array_intersect(array('modified', 'created', 'size', ), $txpfile)) {
    $rs  = safe_rows("*", 'txp_file', "1 = 1");
    $dir = get_pref('file_base_path', dirname(txpath).DS.'files');

    foreach ($rs as $row) {
        if (empty($row['filename'])) {
            continue;
        }

        $path = build_file_path($dir, $row['filename']);

        if ($path and ($stat = @stat($path))) {
            safe_update('txp_file', "created = '".strftime('%Y-%m-%d %H:%M:%S', $stat['ctime'])."', modified = '".strftime('%Y-%m-%d %H:%M:%S', $stat['mtime'])."', size = '".doSlash(sprintf('%u', $stat['size']))."'", "id = '".doSlash($row['id'])."'");
        }
    }
}

safe_update('textpattern', "Keywords = TRIM(BOTH ',' FROM
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(Keywords, '\n', ','),
                                            '\r', ','),
                                        '\t', ','),
                                    '    ', ' '),
                                '  ', ' '),
                            '  ', ' '),
                        ' ,', ','),
                    ', ', ','),
                ',,,,', ','),
            ',,', ','),
        ',,', ',')
    )",
    "Keywords != ''"
);

// Shift preferences to more intuitive spots.
// Give positions, leave enough room for later additions.

safe_update('txp_prefs', "position = 20", "name IN(
    'sitename',
    'comments_on_default',
    'img_dir',
    'comments_require_name',
    'syndicate_body_or_excerpt',
    'title_no_widow'
)");

safe_update('txp_prefs', "position = 40", "name IN(
    'siteurl',
    'comments_default_invite',
    'file_base_path',
    'comments_require_email',
    'rss_how_many',
    'articles_use_excerpts'
)");

safe_update('txp_prefs', "position = 60", "name IN(
    'site_slogan',
    'comments_moderate',
    'never_display_email',
    'file_max_upload_size',
    'show_comment_count_in_feed',
    'allow_form_override'
)");

safe_update('txp_prefs', "position = 80", "name IN(
    'production_status',
    'comments_disabled_after',
    'tempdir',
    'comment_nofollow',
    'include_email_atom',
    'attach_titles_to_permalinks'
)");

safe_update('txp_prefs', "position = 100", "name IN(
    'gmtoffset',
    'comments_auto_append',
    'plugin_cache_dir',
    'permlink_format',
    'use_mail_on_feeds_id'
)");

safe_update('txp_prefs', "position = 120", "name IN(
    'is_dst',
    'comments_mode',
    'override_emailcharset'
)");

safe_update('txp_prefs', "position = 120, event = 'publish'", "name = 'send_lastmod'");

safe_update('txp_prefs', "position = 140", "name IN(
    'dateformat',
    'comments_dateformat',
    'spam_blacklists'
)");

safe_update('txp_prefs', "position = 160", "name IN(
    'archive_dateformat',
    'comments_are_ol',
    'comment_means_site_updated',
    'ping_weblogsdotcom'
)");

safe_update('txp_prefs', "position = 180", "name IN(
    'permlink_mode',
    'comments_sendmail',
    'ping_textpattern_com'
)");

safe_update('txp_prefs', "position = 200", "name IN(
    'use_textile',
    'expire_logs_after'
)");

safe_update('txp_prefs', "position = 220", "name IN(
    'logging',
    'use_dns'
)");

safe_update('txp_prefs', "position = 240", "name IN(
    'use_comments',
    'max_url_len'
)");

safe_update('txp_prefs', "position = 260", "name = 'use_plugins'");
safe_update('txp_prefs', "position = 280", "name = 'admin_side_plugins'");
safe_update('txp_prefs', "position = 300", "name = 'allow_page_php_scripting'");
safe_update('txp_prefs', "position = 320", "name = 'allow_article_php_scripting'");
safe_update('txp_prefs', "position = 340", "name = 'allow_raw_php_scripting'");
safe_update('txp_prefs', "position = 120, type = 1", "name = 'comments_disallow_images'");

safe_update('txp_prefs', "event = 'comments'", "name IN(
    'never_display_email',
    'comment_nofollow',
    'spam_blacklists',
    'comment_means_site_updated'
)");

safe_update('txp_prefs', "event = 'feeds'", "name IN(
    'syndicate_body_or_excerpt',
    'rss_how_many',
    'show_comment_count_in_feed',
    'include_email_atom',
    'use_mail_on_feeds_id'
)");

// 'Textile links' feature removed due to unclear specs.
safe_delete('txp_prefs', "event = 'link' AND name = 'textile_links'");

// Use TextileRestricted lite/fat in comments?
if (!safe_field("name", 'txp_prefs', "name = 'comments_use_fat_textile'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'comments_use_fat_textile', val = '0', type = '1', event = 'comments', html = 'yesnoradio', position = '130'");
}

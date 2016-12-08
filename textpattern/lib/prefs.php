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


$permlink_mode = empty($permlink_mode) ? 'messy' : $permlink_mode;
$blog_uid  = empty($blog_uid) ? md5(uniqid(rand(), true)) : $blog_uid;
$setup_comment_invite = (gTxt('setup_comment_invite') == 'setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite');
$theme = empty($theme) ? 'hive' : $theme;
$siteurl = empty($siteurl) ? $GLOBALS['siteurl'] : $siteurl;

// maybe drop its pref? It used only for atom/rss feeds
$blog_mail_uid = empty($_SESSION['email']) ? md5(rand()).'blog@gmail.com' : $_SESSION['email'];

$language = LANG;
$language = empty($language) ? 'en-gb' : $language;

$gmtoffset = sprintf("%+d", gmmktime(0, 0, 0) - mktime(0, 0, 0));

$default_prefs = array(

'img_dir'                     => array('admin',    PREF_CORE,    20, 'text_input'      , 'images'),
'file_base_path'              => array('admin',    PREF_CORE,    40, 'text_input'      , dirname(txpath).DS.'files'),
'file_max_upload_size'        => array('admin',    PREF_CORE,    60, 'text_input'      , '2000000'),
'tempdir'                     => array('admin',    PREF_CORE,    80, 'text_input'      , find_temp_dir()),
'plugin_cache_dir'            => array('admin',    PREF_CORE,   100, 'text_input'      , ''),
'smtp_from'                   => array('admin',    PREF_CORE,   110, 'text_input'      , ''),
'publisher_email'             => array('admin',    PREF_CORE,   115, 'text_input'      , ''),
'override_emailcharset'       => array('admin',    PREF_CORE,   120, 'yesnoradio'      , '0'),
'enable_xmlrpc_server'        => array('admin',    PREF_CORE,   130, 'yesnoradio'      , '0'),
'default_event'               => array('admin',    PREF_CORE,   150, 'default_event'   , 'article'),
'theme_name'                  => array('admin',    PREF_CORE,   160, 'themename'       , $theme),

'show_article_category_count' => array('category', PREF_HIDDEN,   0, 'yesnoradio'      , '1'),

'comments_on_default'         => array('comments', PREF_CORE,    20, 'yesnoradio'      , '0'),
'comments_default_invite'     => array('comments', PREF_CORE,    40, 'text_input'      , $setup_comment_invite),
'comments_moderate'           => array('comments', PREF_CORE,    60, 'yesnoradio'      , '1'),
'comments_disabled_after'     => array('comments', PREF_CORE,    80, 'weeks'           , '42'),
'comments_auto_append'        => array('comments', PREF_CORE,   100, 'yesnoradio'      , '0'),
'comments_mode'               => array('comments', PREF_CORE,   120, 'commentmode'     , '0'),
'comments_dateformat'         => array('comments', PREF_CORE,   140, 'dateformats'     , '%b %d, %I:%M %p'),
'comments_sendmail'           => array('comments', PREF_CORE,   160, 'commentsendmail' , '0'),
'comments_are_ol'             => array('comments', PREF_CORE,   180, 'yesnoradio'      , '1'),
'comment_means_site_updated'  => array('comments', PREF_CORE,   200, 'yesnoradio'      , '1'),
'comments_require_name'       => array('comments', PREF_CORE,   220, 'yesnoradio'      , '1'),
'comments_require_email'      => array('comments', PREF_CORE,   240, 'yesnoradio'      , '1'),
'never_display_email'         => array('comments', PREF_CORE,   260, 'yesnoradio'      , '1'),
'comment_nofollow'            => array('comments', PREF_CORE,   280, 'yesnoradio'      , '1'),
'comments_disallow_images'    => array('comments', PREF_CORE,   300, 'yesnoradio'      , '0'),
'comments_use_fat_textile'    => array('comments', PREF_CORE,   320, 'yesnoradio'      , '0'),
'spam_blacklists'             => array('comments', PREF_CORE,   340, 'text_input'      , ''),

'custom_1_set'                => array('custom',   PREF_CORE,     1, 'custom_set'      , ''),
'custom_2_set'                => array('custom',   PREF_CORE,     2, 'custom_set'      , ''),
'custom_3_set'                => array('custom',   PREF_CORE,     3, 'custom_set'      , ''),
'custom_4_set'                => array('custom',   PREF_CORE,     4, 'custom_set'      , ''),
'custom_5_set'                => array('custom',   PREF_CORE,     5, 'custom_set'      , ''),
'custom_6_set'                => array('custom',   PREF_CORE,     6, 'custom_set'      , ''),
'custom_7_set'                => array('custom',   PREF_CORE,     7, 'custom_set'      , ''),
'custom_8_set'                => array('custom',   PREF_CORE,     8, 'custom_set'      , ''),
'custom_9_set'                => array('custom',   PREF_CORE,     9, 'custom_set'      , ''),
'custom_10_set'               => array('custom',   PREF_CORE,    10, 'custom_set'      , ''),

'syndicate_body_or_excerpt'   => array('feeds',    PREF_CORE,    20, 'yesnoradio'      , '1'),
'rss_how_many'                => array('feeds',    PREF_CORE,    40, 'text_input'      , '5'),
'show_comment_count_in_feed'  => array('feeds',    PREF_CORE,    60, 'yesnoradio'      , '1'),
'include_email_atom'          => array('feeds',    PREF_CORE,    80, 'yesnoradio'      , '0'),
'use_mail_on_feeds_id'        => array('feeds',    PREF_CORE,   100, 'yesnoradio'      , '0'),

'title_no_widow'              => array('publish',  PREF_CORE,    20, 'yesnoradio'      , '0'),
'articles_use_excerpts'       => array('publish',  PREF_CORE,    40, 'yesnoradio'      , '1'),
'allow_form_override'         => array('publish',  PREF_CORE,    60, 'yesnoradio'      , '1'),
'attach_titles_to_permalinks' => array('publish',  PREF_CORE,    80, 'yesnoradio'      , '1'),
'permlink_format'             => array('publish',  PREF_CORE,   100, 'permlink_format' , '1'),
'send_lastmod'                => array('publish',  PREF_CORE,   120, 'yesnoradio'      , '1'),
'publish_expired_articles'    => array('publish',  PREF_CORE,   130, 'yesnoradio'      , '0'),
'ping_weblogsdotcom'          => array('publish',  PREF_CORE,   160, 'yesnoradio'      , '0'),
'use_textile'                 => array('publish',  PREF_CORE,   200, 'pref_text'       , '1'),
'use_dns'                     => array('publish',  PREF_CORE,   220, 'yesnoradio'      , '0'),
'use_plugins'                 => array('publish',  PREF_CORE,   260, 'yesnoradio'      , '1'),
'admin_side_plugins'          => array('publish',  PREF_CORE,   280, 'yesnoradio'      , '1'),
'allow_page_php_scripting'    => array('publish',  PREF_CORE,   300, 'yesnoradio'      , '1'),
'allow_article_php_scripting' => array('publish',  PREF_CORE,   320, 'yesnoradio'      , '1'),
'max_url_len'                 => array('publish',  PREF_CORE,   340, 'text_input'      , '1000'),
'blog_mail_uid'               => array('publish',  PREF_HIDDEN,   0, 'text_input'      , $blog_mail_uid),
'blog_time_uid'               => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '2005'),
'blog_uid'                    => array('publish',  PREF_HIDDEN,   0, 'text_input'      , $blog_uid),
'dbupdatetime'                => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '0'),
'language'                    => array('publish',  PREF_HIDDEN,   0, 'languages'       , $language),
'lastmod'                     => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '2005-07-23 16:24:10'),
'locale'                      => array('publish',  PREF_HIDDEN,   0, 'text_input'      , getlocale($language)),
'path_from_root'              => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '/'),
'path_to_site'                => array('publish',  PREF_HIDDEN,   0, 'text_input'      , dirname(txpath)),
'prefs_id'                    => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '1'),
'searchable_article_fields'   => array('publish',  PREF_HIDDEN,   0, 'text_input'      , 'Title, Body'),
'textile_updated'             => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '1'),
'timeoffset'                  => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '0'),
'timezone_key'                => array('publish',  PREF_HIDDEN,   0, 'text_input'      , ''),
'url_mode'                    => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '1'),
'use_categories'              => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '1'),
'use_sections'                => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '1'),
'sql_now_posted'              => array('publish',  PREF_HIDDEN,   0, 'text_input'      , time()),
'sql_now_expires'             => array('publish',  PREF_HIDDEN,   0, 'text_input'      , time()),
'sql_now_created'             => array('publish',  PREF_HIDDEN,   0, 'text_input'      , time()),
'version'                     => array('publish',  PREF_HIDDEN,   0, 'text_input'      , '4.6.2'),

'default_section'             => array('section',  PREF_HIDDEN,   0, 'text_input'      , 'articles'),

'sitename'                    => array('site',     PREF_CORE,    20, 'text_input'      , gTxt('my_site')),
'siteurl'                     => array('site',     PREF_CORE,    40, 'text_input'      , $siteurl),
'site_slogan'                 => array('site',     PREF_CORE,    60, 'text_input'      , gTxt('my_slogan')),
'production_status'           => array('site',     PREF_CORE,    80, 'prod_levels'     , 'testing'),
'gmtoffset'                   => array('site',     PREF_CORE,   110, 'gmtoffset_select', $gmtoffset),
'auto_dst'                    => array('site',     PREF_CORE,   115, 'yesnoradio'      , '0'),
'is_dst'                      => array('site',     PREF_CORE,   120, 'is_dst'          , '0'),
'dateformat'                  => array('site',     PREF_CORE,   140, 'dateformats'     , 'since'),
'archive_dateformat'          => array('site',     PREF_CORE,   160, 'dateformats'     , '%b %d, %I:%M %p'),
'permlink_mode'               => array('site',     PREF_CORE,   180, 'permlinkmodes'   , $permlink_mode),
'doctype'                     => array('site',     PREF_CORE,   190, 'doctypes'        , 'html5'),
'logging'                     => array('site',     PREF_CORE,   220, 'logging'         , 'none'),
'expire_logs_after'           => array('site',     PREF_CORE,   230, 'text_input'      , '7'),
'use_comments'                => array('site',     PREF_CORE,   240, 'yesnoradio'      , '1'),

);

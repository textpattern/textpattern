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

$default_prefs = array(
    'admin' => array(
        array(PREF_CORE,    20, 'text_input'      , 'img_dir'                    , 'images'),
        array(PREF_CORE,    40, 'text_input'      , 'file_base_path'             , dirname(txpath).DS.'files'),
        array(PREF_CORE,    60, 'text_input'      , 'file_max_upload_size'       , '2000000'),
        array(PREF_CORE,    80, 'text_input'      , 'tempdir'                    , find_temp_dir()),
        array(PREF_CORE,   100, 'text_input'      , 'plugin_cache_dir'           , ''),
        array(PREF_CORE,   110, 'text_input'      , 'smtp_from'                  , ''),
        array(PREF_CORE,   115, 'text_input'      , 'publisher_email'            , ''),
        array(PREF_CORE,   120, 'yesnoradio'      , 'override_emailcharset'      , '0'),
        array(PREF_CORE,   130, 'yesnoradio'      , 'enable_xmlrpc_server'       , '0'),
        array(PREF_CORE,   150, 'default_event'   , 'default_event'              , 'article'),
        array(PREF_CORE,   160, 'themename'       , 'theme_name'                 , $theme),
    ),
    'category' => array(
        array(PREF_HIDDEN,   0, 'yesnoradio'      , 'show_article_category_count', '1'),
    ),
    'comments' => array(
        array(PREF_CORE,    20, 'yesnoradio'      , 'comments_on_default'        , '0'),
        array(PREF_CORE,    40, 'text_input'      , 'comments_default_invite'    , $setup_comment_invite),
        array(PREF_CORE,    60, 'yesnoradio'      , 'comments_moderate'          , '1'),
        array(PREF_CORE,    80, 'weeks'           , 'comments_disabled_after'    , '42'),
        array(PREF_CORE,   100, 'yesnoradio'      , 'comments_auto_append'       , '0'),
        array(PREF_CORE,   120, 'commentmode'     , 'comments_mode'              , '0'),
        array(PREF_CORE,   140, 'dateformats'     , 'comments_dateformat'        , '%b %d, %I:%M %p'),
        array(PREF_CORE,   160, 'commentsendmail' , 'comments_sendmail'          , '0'),
        array(PREF_CORE,   180, 'yesnoradio'      , 'comments_are_ol'            , '1'),
        array(PREF_CORE,   200, 'yesnoradio'      , 'comment_means_site_updated' , '1'),
        array(PREF_CORE,   220, 'yesnoradio'      , 'comments_require_name'      , '1'),
        array(PREF_CORE,   240, 'yesnoradio'      , 'comments_require_email'     , '1'),
        array(PREF_CORE,   260, 'yesnoradio'      , 'never_display_email'        , '1'),
        array(PREF_CORE,   280, 'yesnoradio'      , 'comment_nofollow'           , '1'),
        array(PREF_CORE,   300, 'yesnoradio'      , 'comments_disallow_images'   , '0'),
        array(PREF_CORE,   320, 'yesnoradio'      , 'comments_use_fat_textile'   , '0'),
        array(PREF_CORE,   340, 'text_input'      , 'spam_blacklists'            , ''),
    ),
    'custom' => array(
        array(PREF_CORE,     1, 'custom_set'      , 'custom_1_set'               , 'custom1'),
        array(PREF_CORE,     2, 'custom_set'      , 'custom_2_set'               , 'custom2'),
        array(PREF_CORE,     3, 'custom_set'      , 'custom_3_set'               , ''),
        array(PREF_CORE,     4, 'custom_set'      , 'custom_4_set'               , ''),
        array(PREF_CORE,     5, 'custom_set'      , 'custom_5_set'               , ''),
        array(PREF_CORE,     6, 'custom_set'      , 'custom_6_set'               , ''),
        array(PREF_CORE,     7, 'custom_set'      , 'custom_7_set'               , ''),
        array(PREF_CORE,     8, 'custom_set'      , 'custom_8_set'               , ''),
        array(PREF_CORE,     9, 'custom_set'      , 'custom_9_set'               , ''),
        array(PREF_CORE,    10, 'custom_set'      , 'custom_10_set'              , ''),
    ),
    'feeds' => array(
        array(PREF_CORE,    20, 'yesnoradio'      , 'syndicate_body_or_excerpt'  , '1'),
        array(PREF_CORE,    40, 'text_input'      , 'rss_how_many'               , '5'),
        array(PREF_CORE,    60, 'yesnoradio'      , 'show_comment_count_in_feed' , '1'),
        array(PREF_CORE,    80, 'yesnoradio'      , 'include_email_atom'         , '0'),
        array(PREF_CORE,   100, 'yesnoradio'      , 'use_mail_on_feeds_id'       , '0'),
    ),
    'publish' => array(
        array(PREF_CORE,    20, 'yesnoradio'      , 'title_no_widow'             , '0'),
        array(PREF_CORE,    40, 'yesnoradio'      , 'articles_use_excerpts'      , '1'),
        array(PREF_CORE,    60, 'yesnoradio'      , 'allow_form_override'        , '1'),
        array(PREF_CORE,    80, 'yesnoradio'      , 'attach_titles_to_permalinks', '1'),
        array(PREF_CORE,   100, 'permlink_format' , 'permlink_format'            , '1'),
        array(PREF_CORE,   120, 'yesnoradio'      , 'send_lastmod'               , '1'),
        array(PREF_CORE,   130, 'yesnoradio'      , 'publish_expired_articles'   , '0'),
        array(PREF_CORE,   160, 'yesnoradio'      , 'ping_weblogsdotcom'         , '0'),
        array(PREF_CORE,   200, 'pref_text'       , 'use_textile'                , '1'),
        array(PREF_CORE,   220, 'yesnoradio'      , 'use_dns'                    , '0'),
        array(PREF_CORE,   260, 'yesnoradio'      , 'use_plugins'                , '1'),
        array(PREF_CORE,   280, 'yesnoradio'      , 'admin_side_plugins'         , '1'),
        array(PREF_CORE,   300, 'yesnoradio'      , 'allow_page_php_scripting'   , '1'),
        array(PREF_CORE,   320, 'yesnoradio'      , 'allow_article_php_scripting', '1'),
        array(PREF_CORE,   340, 'text_input'      , 'max_url_len'                , '1000'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'blog_mail_uid'              , $_SESSION['email']),
        array(PREF_HIDDEN,   0, 'text_input'      , 'blog_time_uid'              , '2005'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'blog_uid'                   , $blog_uid),
        array(PREF_HIDDEN,   0, 'text_input'      , 'dbupdatetime'               , '0'),
        array(PREF_HIDDEN,   0, 'languages'       , 'language'                   , LANG),
        array(PREF_HIDDEN,   0, 'text_input'      , 'lastmod'                    , '2005-07-23 16:24:10'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'locale'                     , getlocale(LANG)),
        array(PREF_HIDDEN,   0, 'text_input'      , 'path_from_root'             , '/'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'path_to_site'               , dirname(txpath)),
        array(PREF_HIDDEN,   0, 'text_input'      , 'prefs_id'                   , '1'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'searchable_article_fields'  , 'Title, Body'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'textile_updated'            , '1'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'timeoffset'                 , '0'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'timezone_key'               , ''),
        array(PREF_HIDDEN,   0, 'text_input'      , 'url_mode'                   , '1'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'use_categories'             , '1'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'use_sections'               , '1'),
        array(PREF_HIDDEN,   0, 'text_input'      , 'sql_now_posted'             , time()),
        array(PREF_HIDDEN,   0, 'text_input'      , 'sql_now_expires'            , time()),
        array(PREF_HIDDEN,   0, 'text_input'      , 'sql_now_created'            , time()),
        array(PREF_HIDDEN,   0, 'text_input'      , 'version'                    , '4.6.2'),
    ),
    'section' => array(
        array(PREF_HIDDEN,   0, 'text_input'      , 'default_section'            , 'articles'),
    ),
    'site' => array(
        array(PREF_CORE,    20, 'text_input'      , 'sitename'                   , gTxt('my_site')),
        array(PREF_CORE,    40, 'text_input'      , 'siteurl'                    , $siteurl),
        array(PREF_CORE,    60, 'text_input'      , 'site_slogan'                , gTxt('my_slogan')),
        array(PREF_CORE,    80, 'prod_levels'     , 'production_status'          , 'testing'),
        array(PREF_CORE,   110, 'gmtoffset_select', 'gmtoffset'                  , $gmtoffset),
        array(PREF_CORE,   115, 'yesnoradio'      , 'auto_dst'                   , '0'),
        array(PREF_CORE,   120, 'is_dst'          , 'is_dst'                     , '0'),
        array(PREF_CORE,   140, 'dateformats'     , 'dateformat'                 , 'since'),
        array(PREF_CORE,   160, 'dateformats'     , 'archive_dateformat'         , '%b %d, %I:%M %p'),
        array(PREF_CORE,   180, 'permlinkmodes'   , 'permlink_mode'              , $permlink_mode),
        array(PREF_CORE,   190, 'doctypes'        , 'doctype'                    , 'html5'),
        array(PREF_CORE,   220, 'logging'         , 'logging'                    , 'none'),
        array(PREF_CORE,   230, 'text_input'      , 'expire_logs_after'          , '7'),
        array(PREF_CORE,   240, 'yesnoradio'      , 'use_comments'               , '1'),
    )
);

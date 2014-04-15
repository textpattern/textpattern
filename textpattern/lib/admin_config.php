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

/**
 * Collection of user configuration options.
 *
 * @package User
 */

/**
 * Textpattern admin options.
 *
 * These have been moved to the database.
 *
 * @global     array $txpac
 * @deprecated in 1.0.0
 */

$txpac = array(
// -------------------------------------------------------------
// bypass the Txp CSS editor entirely

    'edit_raw_css_by_default'     => 1,


// -------------------------------------------------------------
// php scripts on page templates will be parsed

    'allow_page_php_scripting'    => 1,

// -------------------------------------------------------------
// php scripts in article bodies will be parsed

    'allow_article_php_scripting' => 1,

// -------------------------------------------------------------
// use Textile on link titles and descriptions

    'textile_links'               => 0,


// -------------------------------------------------------------
// in the article categories listing in the organise tab

    'show_article_category_count' => 1,


// -------------------------------------------------------------
// xml feeds display comment count as part of article title

    'show_comment_count_in_feed'  => 1,


// -------------------------------------------------------------
// include articles or full excerpts in feeds
// 0 = full article body
// 1 = excerpt

    'syndicate_body_or_excerpt'   => 1,


// -------------------------------------------------------------
// include (encoded) author email in atom feeds

    'include_email_atom'          => 1,


// -------------------------------------------------------------
// each comment received updates the site Last-Modified header

    'comment_means_site_updated'  => 1,


// -------------------------------------------------------------
// comment email addresses are encoded to hide from spambots
// but if you never want to see them at all, set this to 1

    'never_display_email'         => 0,


// -------------------------------------------------------------
// comments must enter name and/or email address

    'comments_require_name'       => 1,
    'comments_require_email'      => 1,


// -------------------------------------------------------------
// show 'excerpt' pane in write tab

    'articles_use_excerpts'       => 1,


// -------------------------------------------------------------
// show form overrides on article-by-article basis

    'allow_form_override'         => 1,


// -------------------------------------------------------------
// whether or not to attach article titles to permalinks
// e.g., /article/313/IAteACheeseSandwich

    'attach_titles_to_permalinks' => 1,


// -------------------------------------------------------------
// if attaching titles to permalinks, which format?
// 0 = SuperStudlyCaps
// 1 = hyphenated-lower-case

    'permalink_title_format'      => 1,


// -------------------------------------------------------------
// number of days after which logfiles are purged

    'expire_logs_after'           => 7,


// -------------------------------------------------------------
// plugins on or off

    'use_plugins'                 => 1,

// -------------------------------------------------------------
// use custom fields for articles - must be in 'quotes', and
// must contain no spaces

    'custom_1_set'                => 'custom1',
    'custom_2_set'                => 'custom2',
    'custom_3_set'                => '',
    'custom_4_set'                => '',
    'custom_5_set'                => '',
    'custom_6_set'                => '',
    'custom_7_set'                => '',
    'custom_8_set'                => '',
    'custom_9_set'                => '',
    'custom_10_set'               => '',

// -------------------------------------------------------------
// ping textpattern.com when an article is published

    'ping_textpattern_com'        => 1,

// -------------------------------------------------------------
// use DNS lookups in referrer log

    'use_dns'        => 1,

// -------------------------------------------------------------
// load plugins in the admin interface

    'admin_side_plugins'        => 1,

// -------------------------------------------------------------
// use rel="nofollow" on comment links

    'comment_nofollow'        => 1,

// -------------------------------------------------------------
// use encoded email on atom feeds id, instead of domain name
// (if you plan to move this install to another domain, you should use this)

    'use_mail_on_feeds_id'       =>0,

// -------------------------------------------------------------
// maximum url length before it should be considered malicious

    'max_url_len'               => 200,

// -------------------------------------------------------------
// Spam DNS RBLs

    'spam_blacklists'          => 'sbl.spamhaus.org',

);

/**
 * Sets permissions.
 *
 * @global array $txp_permissions
 */

$txp_permissions = array(
    'admin'                       => '1,2,3,4,5,6',
    'admin.edit'                  => '1',
    'admin.list'                  => '1,2,3',
    'article.delete.own'          => '1,2,3,4',
    'article.delete'              => '1,2',
    'article.edit'                => '1,2,3',
    'article.edit.published'      => '1,2,3',
    'article.edit.own'            => '1,2,3,4,5,6',
    'article.edit.own.published'  => '1,2,3,4',
    'article.preview'             => '1,2,3,4',
    'article.publish'             => '1,2,3,4',
    'article.php'                 => '1,2',
    'article'                     => '1,2,3,4,5,6',
    'list'                        => '1,2,3,4,5,6', //likely the same as for article.
    'category'                    => '1,2,3',
    'css'                         => '1,2,      6',
    'debug.verbose'               => '1,2',
    'debug.backtrace'             => '1',
    'diag'                        => '1,2',
    'discuss'                     => '1,2,3',
    'file'                        => '1,2,3,4,  6',
    'file.edit'                   => '1,2,      6',
    'file.edit.own'               => '1,2,3,4,  6',
    'file.delete'                 => '1,2',
    'file.delete.own'             => '1,2,3,4,  6',
    'file.publish'                => '1,2,3,4,  6',
    'form'                        => '1,2,3,    6',
    'image'                       => '1,2,3,4,  6',
    'image.create.trusted'        => '1,2,3,    6',
    'image.edit'                  => '1,2,3,    6',
    'image.edit.own'              => '1,2,3,4,  6',
    'image.delete'                => '1,2',
    'image.delete.own'            => '1,2,3,4,  6',
    'import'                      => '1,2',
    'lang'                        => '1,2', // more?
    'link'                        => '1,2,3',
    'link.edit'                   => '1,2,3',
    'link.edit.own'               => '1,2,3',
    'link.delete'                 => '1,2',
    'link.delete.own'             => '1,2,3',
    'log'                         => '1,2,3', // more?
    'page'                        => '1,2,3,    6',
    'pane'                        => '1,2,3,4,5,6',
    'plugin'                      => '1,2',
    'prefs'                       => '1,2,3,4,5,6',
    'prefs.edit'                  => '1,2',
    'prefs.site'                  => '1,2',
    'prefs.admin'                 => '1,2',
    'prefs.publish'               => '1,2',
    'prefs.feeds'                 => '1,2',
    'prefs.custom'                => '1,2',
    'prefs.comments'              => '1,2',
    'section'                     => '1,2,3,    6',
    'section.edit'                => '1,2,3,    6',
    'tab.admin'                   => '1,2,3,4,5,6',
    'tab.content'                 => '1,2,3,4,5,6',
    'tab.extensions'              => '1,2',
    'tab.presentation'            => '1,2,3,    6',
    'tag'                         => '1,2,3,4,5,6',
);

/**
 * List of user groups.
 *
 * @global array $txp_groups
 */

$txp_groups = array(
    1 => 'publisher',
    2 => 'managing_editor',
    3 => 'copy_editor',
    4 => 'staff_writer',
    5 => 'freelancer',
    6 => 'designer',
    0 => 'privs_none'
);

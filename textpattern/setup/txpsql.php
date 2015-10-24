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

if (!defined('TXP_INSTALL')) {
    exit;
}

@ignore_user_abort(1);
@set_time_limit(0);

if (strpos($dhost, ':') === false) {
    $host = $dhost;
    $port = ini_get("mysqli.default_port");
} else {
    list($host, $port) = explode(':', $dhost, 2);
    $port = intval($port);
}

if (isset($txpcfg['socket'])) {
    $socket = $txpcfg['socket'];
} else {
    $socket = ini_get("mysqli.default_socket");
}

$link = mysqli_init();
mysqli_real_connect($link, $host, $duser, $dpass, $ddb, $port, $socket, $dclient_flags);

$result = mysqli_query($link, "DESCRIBE `".PFX."textpattern`");

if ($result) {
    die("Textpattern database table already exists. Can't run setup.");
}

$version = mysqli_get_server_info($link);

// Use "ENGINE" if version of MySQL > 4.1.2.
$tabletype = (version_compare($version, '4.1.2') >= 0) ? ' ENGINE=MyISAM ' : ' TYPE=MyISAM ';

// On 4.1 or greater use UTF-8 tables.
if (isset($dbcharset)) {
    $tabletype .= " CHARACTER SET = $dbcharset ";

    if ($dbcharset == 'utf8mb4') {
        $tabletype .= " COLLATE utf8mb4_unicode_ci ";
    } elseif ($dbcharset == 'utf8') {
        $tabletype .= " COLLATE utf8_general_ci ";
    }

    mysqli_query($link, "SET NAMES ".$dbcharset);
}

// Default to messy URLs if we know clean ones won't work.
$permlink_mode = 'section_id_title';

if (is_callable('apache_get_modules')) {
    $modules = @apache_get_modules();

    if (!is_array($modules) || !in_array('mod_rewrite', $modules)) {
        $permlink_mode = 'messy';
    }
} else {
    $server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST'])
        ? ((@$_SERVER['SERVER_SOFTWARE']) ? @$_SERVER['SERVER_SOFTWARE'] : $_SERVER['HTTP_HOST'])
        : '';

    if (!stristr($server_software, 'Apache')) {
        $permlink_mode = 'messy';
    }
}

$username = (!empty($_SESSION['name'])) ? $_SESSION['name'] : 'anon';
$useremail = (!empty($_SESSION['email'])) ? $_SESSION['email'] : '';

$create_sql = array();

$create_sql[] = "CREATE TABLE `".PFX."textpattern` (
    ID              INT(11)      NOT NULL AUTO_INCREMENT,
    Posted          DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    AuthorID        VARCHAR(64)  NOT NULL DEFAULT '',
    LastMod         DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    LastModID       VARCHAR(64)  NOT NULL DEFAULT '',
    Title           VARCHAR(255) NOT NULL DEFAULT '',
    Title_html      VARCHAR(255) NOT NULL DEFAULT '',
    Body            MEDIUMTEXT   NOT NULL,
    Body_html       MEDIUMTEXT   NOT NULL,
    Excerpt         TEXT         NOT NULL,
    Excerpt_html    MEDIUMTEXT   NOT NULL,
    Image           VARCHAR(255) NOT NULL DEFAULT '',
    Category1       VARCHAR(128) NOT NULL DEFAULT '',
    Category2       VARCHAR(128) NOT NULL DEFAULT '',
    Annotate        INT(2)       NOT NULL DEFAULT '0',
    AnnotateInvite  VARCHAR(255) NOT NULL DEFAULT '',
    comments_count  INT(8)       NOT NULL DEFAULT '0',
    Status          INT(2)       NOT NULL DEFAULT '4',
    textile_body    INT(2)       NOT NULL DEFAULT '1',
    textile_excerpt INT(2)       NOT NULL DEFAULT '1',
    Section         VARCHAR(255) NOT NULL DEFAULT '',
    override_form   VARCHAR(255) NOT NULL DEFAULT '',
    Keywords        VARCHAR(255) NOT NULL DEFAULT '',
    url_title       VARCHAR(255) NOT NULL DEFAULT '',
    custom_1        VARCHAR(255) NOT NULL DEFAULT '',
    custom_2        VARCHAR(255) NOT NULL DEFAULT '',
    custom_3        VARCHAR(255) NOT NULL DEFAULT '',
    custom_4        VARCHAR(255) NOT NULL DEFAULT '',
    custom_5        VARCHAR(255) NOT NULL DEFAULT '',
    custom_6        VARCHAR(255) NOT NULL DEFAULT '',
    custom_7        VARCHAR(255) NOT NULL DEFAULT '',
    custom_8        VARCHAR(255) NOT NULL DEFAULT '',
    custom_9        VARCHAR(255) NOT NULL DEFAULT '',
    custom_10       VARCHAR(255) NOT NULL DEFAULT '',
    uid             VARCHAR(32)  NOT NULL DEFAULT '',
    feed_time       DATE         NOT NULL DEFAULT '0000-00-00',
    PRIMARY KEY                 (ID),
    KEY          categories_idx (Category1(10),Category2(10)),
    KEY          Posted         (Posted),
    FULLTEXT KEY searching      (Title,Body)
) $tabletype ";

$setup_comment_invite = doSlash((gTxt('setup_comment_invite') == 'setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite'));

$create_sql[] = "INSERT INTO `".PFX."textpattern` VALUES (1, now(), '".doSlash($username)."', now(), '', 'Welcome to your site', '', ".file2sql('textpattern.body').", ".file2sql('textpattern.body_html').", ".file2sql('textpattern.excerpt').", ".file2sql('textpattern.excerpt_html').", '', 'hope-for-the-future', 'meaningful-labor', 1, '".$setup_comment_invite."', 1, 4, 1, 1, 'articles', '', '', 'welcome-to-your-site', '', '', '', '', '', '', '', '', '', '', '".md5(uniqid(rand(), true))."', now())";

$create_sql[] = "CREATE TABLE `".PFX."txp_category` (
    id     INT(6)       NOT NULL AUTO_INCREMENT,
    name   VARCHAR(64)  NOT NULL DEFAULT '',
    type   VARCHAR(64)  NOT NULL DEFAULT '',
    parent VARCHAR(64)  NOT NULL DEFAULT '',
    lft    INT(6)       NOT NULL DEFAULT '0',
    rgt    INT(6)       NOT NULL DEFAULT '0',
    title  VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (id)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (1, 'root', 'article', '', 1, 8, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (2, 'root', 'link', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (3, 'root', 'image', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (4, 'root', 'file', '', 1, 2, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (5, 'hope-for-the-future', 'article', 'root', 2, 3, 'Hope for the future')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (6, 'meaningful-labor', 'article', 'root', 4, 5, 'Meaningful labor')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (7, 'reciprocal-affection', 'article', 'root', 6, 7, 'Reciprocal affection')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (8, 'textpattern', 'link', 'root', 2, 3, 'Textpattern')";

$create_sql[] = "CREATE TABLE `".PFX."txp_css` (
    name VARCHAR(255) NOT NULL,
    css  TEXT         NOT NULL,
    UNIQUE KEY name (name(250))
) $tabletype ";

// sql:txp_css
$create_sql[] = "INSERT INTO `".PFX."txp_css`(name,css) VALUES('default', ".file2sql('css.default').")";
$create_sql[] = "INSERT INTO `".PFX."txp_css`(name,css) VALUES('ie8', ".file2sql('css.ie8').")";
// /sql:txp_css

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss` (
    discussid INT(6) ZEROFILL NOT NULL AUTO_INCREMENT,
    parentid  INT(8)          NOT NULL DEFAULT '0',
    name      VARCHAR(255)    NOT NULL DEFAULT '',
    email     VARCHAR(50)     NOT NULL DEFAULT '',
    web       VARCHAR(255)    NOT NULL DEFAULT '',
    ip        VARCHAR(100)    NOT NULL DEFAULT '',
    posted    DATETIME        NOT NULL DEFAULT '0000-00-00 00:00:00',
    message   TEXT            NOT NULL,
    visible   TINYINT(4)      NOT NULL DEFAULT '1',
    PRIMARY KEY          (discussid),
    KEY         parentid (parentid)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_discuss` VALUES (000001, 1, 'Donald Swain', 'donald.swain@example.com', 'example.com', '127.0.0.1', now(), '<p>I enjoy your site very much.</p>', 1)";

// This table is only created here to avoid an error when trying to remove it in TXP 4.6.0
$create_sql[] = "CREATE TABLE `".PFX."txp_discuss_ipban` (
    ip                VARCHAR(255) NOT NULL DEFAULT '',
    name_used         VARCHAR(255) NOT NULL DEFAULT '',
    date_banned       DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    banned_on_message INT(8)       NOT NULL DEFAULT '0',
    PRIMARY KEY (ip(250))
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss_nonce` (
    issue_time DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    nonce      VARCHAR(255) NOT NULL DEFAULT '',
    used       TINYINT(4)   NOT NULL DEFAULT '0',
    secret     VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (nonce(250))
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_file` (
    id          INT(11)         NOT NULL AUTO_INCREMENT,
    filename    VARCHAR(255)    NOT NULL DEFAULT '',
    category    VARCHAR(255)    NOT NULL DEFAULT '',
    permissions VARCHAR(32)     NOT NULL DEFAULT '0',
    description TEXT            NOT NULL,
    downloads   INT(4) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY          (id),
    UNIQUE KEY  filename (filename(250))
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_form` (
    name VARCHAR(255) NOT NULL DEFAULT '',
    type VARCHAR(28)  NOT NULL DEFAULT '',
    Form TEXT         NOT NULL,
    PRIMARY KEY (name(250))
) $tabletype ";

// sql:txp_form
$forms = array(
    'article' => array('article_listing', 'default', 'search_results'),
    'comment' => array('comments', 'comments_display', 'comment_form', 'popup_comments'),
    'file'    => array('files'),
    'link'    => array('plainlinks'),
    'misc'    => array('images', 'search_input'),
);

foreach ($forms as $form_type => $forms) {
    foreach ($forms as $form_name) {
        $create_sql[] = "INSERT INTO `".PFX."txp_form`(name,type,Form) VALUES('".$form_name."', '".$form_type."', ".file2sql('form.'.$form_name).")";
    }
}
// /sql:txp_form

$create_sql[] = "CREATE TABLE `".PFX."txp_image` (
    id        INT(11)      NOT NULL AUTO_INCREMENT,
    name      VARCHAR(255) NOT NULL DEFAULT '',
    category  VARCHAR(255) NOT NULL DEFAULT '',
    ext       VARCHAR(20)  NOT NULL DEFAULT '',
    w         INT(8)       NOT NULL DEFAULT '0',
    h         INT(8)       NOT NULL DEFAULT '0',
    alt       VARCHAR(255) NOT NULL DEFAULT '',
    caption   TEXT         NOT NULL,
    date      DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    author    VARCHAR(255) NOT NULL DEFAULT '',
    thumbnail INT(2)       NOT NULL DEFAULT '0',
    PRIMARY KEY (id)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_lang` (
    id      INT(9)      NOT NULL AUTO_INCREMENT,
    lang    VARCHAR(16) NOT NULL,
    name    VARCHAR(64) NOT NULL,
    event   VARCHAR(64) NOT NULL,
    data    TEXT,
    lastmod TIMESTAMP,
    PRIMARY KEY        (id),
    UNIQUE KEY  lang   (lang,name),
    KEY         lang_2 (lang,event)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_link` (
    id          INT(6)       NOT NULL AUTO_INCREMENT,
    date        DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    category    VARCHAR(64)  NOT NULL DEFAULT '',
    url         TEXT         NOT NULL,
    linkname    VARCHAR(255) NOT NULL DEFAULT '',
    linksort    VARCHAR(128) NOT NULL DEFAULT '',
    description TEXT         NOT NULL,
    PRIMARY KEY (id)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (1, now(), 'textpattern', 'http://textpattern.com/', 'Textpattern Website', '10', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (2, now(), 'textpattern', 'http://textpattern.net/', 'Textpattern User Documentation', '20', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (3, now(), 'textpattern', 'http://textpattern.org/', 'Textpattern Resources', '30', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (4, now(), 'textpattern', 'http://textpattern.com/@textpattern', '@textpattern', '40', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (5, now(), 'textpattern', 'http://textpattern.com/+', '+Textpattern CMS', '50', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (6, now(), 'textpattern', 'http://textpattern.com/facebook', 'Textpattern Facebook Group', '60', '')";

$create_sql[] = "CREATE TABLE `".PFX."txp_log` (
    id     INT(12)      NOT NULL AUTO_INCREMENT,
    time   DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    host   VARCHAR(255) NOT NULL DEFAULT '',
    page   VARCHAR(255) NOT NULL DEFAULT '',
    refer  MEDIUMTEXT   NOT NULL,
    status INT(11)      NOT NULL DEFAULT '200',
    method VARCHAR(16)  NOT NULL DEFAULT 'GET',
    ip     VARCHAR(16)  NOT NULL DEFAULT '',
    PRIMARY KEY      (id),
    KEY         time (time)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_page` (
    name      VARCHAR(255) NOT NULL DEFAULT '',
    user_html TEXT         NOT NULL,
    PRIMARY KEY (name(250))
) $tabletype ";

// sql:txp_page
foreach (array('archive', 'default', 'error_default') as $page_name) {
    $create_sql[] = "INSERT INTO `".PFX."txp_page`(name,user_html) VALUES('".$page_name."', ".file2sql('page.'.$page_name).")";
}
// /sql:txp_page

$create_sql[] = "CREATE TABLE `".PFX."txp_plugin` (
    name         VARCHAR(64)  NOT NULL DEFAULT '',
    status       INT(2)       NOT NULL DEFAULT '1',
    author       VARCHAR(128) NOT NULL DEFAULT '',
    author_uri   VARCHAR(128) NOT NULL DEFAULT '',
    version      VARCHAR(10)  NOT NULL DEFAULT '1.0',
    description  TEXT         NOT NULL,
    help         TEXT         NOT NULL,
    code         TEXT         NOT NULL,
    code_restore TEXT         NOT NULL,
    code_md5     VARCHAR(32)  NOT NULL DEFAULT '',
    type         INT(2)       NOT NULL DEFAULT '0',
    UNIQUE KEY name (name)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_prefs` (
    prefs_id  INT(11)              NOT NULL DEFAULT '1',
    name      VARCHAR(255)         NOT NULL DEFAULT '',
    val       VARCHAR(255)         NOT NULL DEFAULT '',
    type      SMALLINT(5) UNSIGNED NOT NULL DEFAULT '2',
    event     VARCHAR(12)          NOT NULL DEFAULT 'publish',
    html      VARCHAR(64)          NOT NULL DEFAULT 'text_input',
    position  SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
    user_name VARCHAR(64)          NOT NULL DEFAULT '',
    UNIQUE KEY prefs_idx (prefs_id,name(185), user_name),
    KEY        name      (name),
    KEY        user_name (user_name)
) $tabletype ";

$prefs['blog_uid'] = md5(uniqid(rand(), true));

$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'prefs_id', '1', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'sitename', '".doSlash(gTxt('my_site'))."', 0, 'publish', 'text_input', 10, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'siteurl', 'comment.local', 0, 'publish', 'text_input', 20, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'site_slogan', '".doSlash(gTxt('my_slogan'))."', 0, 'publish', 'text_input', 30, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'language', 'en-gb', 2, 'publish', 'languages', 40, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'url_mode', '1', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'timeoffset', '0', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_on_default', '0', 0, 'comments', 'yesnoradio', 140, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_default_invite', '".$setup_comment_invite."', 0, 'comments', 'text_input', 180, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_mode', '0', 0, 'comments', 'commentmode', 200, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_disabled_after', '42', 0, 'comments', 'weeks', 210, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_textile', '2', 0, 'publish', 'pref_text', 110, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'ping_weblogsdotcom', '0', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'rss_how_many', '5', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'logging', 'none', 0, 'publish', 'logging', 100, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_comments', '1', 0, 'publish', 'yesnoradio', 120, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_categories', '1', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_sections', '1', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'send_lastmod', '0', 1, 'admin', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'path_from_root', '/', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'lastmod', '2005-07-23 16:24:10', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_dateformat', '%b %d, %I:%M %p', 0, 'comments', 'dateformats', 190, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'dateformat', 'since', 0, 'publish', 'dateformats', 70, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'archive_dateformat', '%b %d, %I:%M %p', 0, 'publish', 'dateformats', 80, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_moderate', '1', 0, 'comments', 'yesnoradio', 130, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'img_dir', 'images', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_disallow_images', '0', 0, 'comments', 'yesnoradio', 170, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_sendmail', '0', 0, 'comments', 'yesnoradio', 160, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_max_upload_size', '2000000', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'path_to_site', '', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'article_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'link_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'image_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'log_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'permlink_mode', '".doSlash($permlink_mode)."', 0, 'publish', 'permlinkmodes', 90, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_are_ol', '1', 0, 'comments', 'yesnoradio', 150, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'is_dst', '0', 0, 'publish', 'yesnoradio', 60, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'locale', 'en_GB.UTF-8', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'tempdir', '".doSlash(find_temp_dir())."', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_base_path', '".doSlash(dirname(txpath).DS.'files')."', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_uid', '".$prefs['blog_uid']."', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_mail_uid', '".doSlash('useremail')."', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_time_uid', '2005', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'edit_raw_css_by_default', '1', 1, 'css', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_page_php_scripting', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_article_php_scripting', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_raw_php_scripting', '0', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'textile_links', '0', 1, 'link', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'show_article_category_count', '1', 2, 'category', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'show_comment_count_in_feed', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'syndicate_body_or_excerpt', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'include_email_atom', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_means_site_updated', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'never_display_email', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_require_name', '1', 1, 'comments', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_require_email', '1', 1, 'comments', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'articles_use_excerpts', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_form_override', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'attach_titles_to_permalinks', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'permalink_title_format', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'expire_logs_after', '7', 1, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_plugins', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_1_set', 'custom1', 1, 'custom', 'text_input', 1, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_2_set', 'custom2', 1, 'custom', 'text_input', 2, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_3_set', '', 1, 'custom', 'text_input', 3, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_4_set', '', 1, 'custom', 'text_input', 4, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_5_set', '', 1, 'custom', 'text_input', 5, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_6_set', '', 1, 'custom', 'text_input', 6, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_7_set', '', 1, 'custom', 'text_input', 7, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_8_set', '', 1, 'custom', 'text_input', 8, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_9_set', '', 1, 'custom', 'text_input', 9, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_10_set', '', 1, 'custom', 'text_input', 10, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_dns', '0', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'admin_side_plugins', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_nofollow', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_mail_on_feeds_id', '0', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'max_url_len', '1000', 1, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'spam_blacklists', '', 1, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'override_emailcharset', '0', 1, 'admin', 'yesnoradio', 21, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'production_status', 'testing', 0, 'publish', 'prod_levels', 210, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_auto_append', '0', 0, 'comments', 'yesnoradio', 211, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'dbupdatetime', '1122194504', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'version', '1.0rc4', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'doctype', 'html5', 0, 'publish', 'doctypes', 190, '')";

$create_sql[] = "CREATE TABLE `".PFX."txp_section` (
    name         VARCHAR(255) NOT NULL DEFAULT '',
    page         VARCHAR(128) NOT NULL DEFAULT '',
    css          VARCHAR(128) NOT NULL DEFAULT '',
    is_default   INT(2)       NOT NULL DEFAULT '0',
    in_rss       INT(2)       NOT NULL DEFAULT '1',
    on_frontpage INT(2)       NOT NULL DEFAULT '1',
    searchable   INT(2)       NOT NULL DEFAULT '1',
    title        VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (name(250))
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('articles', 'archive', 'default', 1, 1, 1, 1, 'Articles')";
$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('default', 'default', 'default', 0, 1, 1, 1, 'Default')";

$create_sql[] = "CREATE TABLE `".PFX."txp_users` (
    user_id     INT(4)       NOT NULL AUTO_INCREMENT,
    name        VARCHAR(64)  NOT NULL DEFAULT '',
    pass        VARCHAR(128) NOT NULL DEFAULT '',
    RealName    VARCHAR(64)  NOT NULL DEFAULT '',
    email       VARCHAR(100) NOT NULL DEFAULT '',
    privs       TINYINT(2)   NOT NULL DEFAULT '1',
    last_access DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    nonce       VARCHAR(64)  NOT NULL DEFAULT '',
    PRIMARY KEY      (user_id),
    UNIQUE KEY  name (name)
) $tabletype ";

$GLOBALS['txp_install_successful'] = true;
$GLOBALS['txp_err_count'] = 0;

foreach ($create_sql as $query) {
    $result = mysqli_query($link, $query);

    if (!$result) {
        $GLOBALS['txp_err_count']++;
        echo "<b>".$GLOBALS['txp_err_count'].".</b> ".mysqli_error($link)."<br />\n";
        echo "<!--\n $query \n-->\n";
        $GLOBALS['txp_install_successful'] = false;
    }
}

// Skip the RPC language fetch when testing.
if (defined('TXP_TEST')) {
    return;
}

require_once txpath.'/lib/IXRClass.php';
$client = new IXR_Client('http://rpc.textpattern.com');

if (!$client->query('tups.getLanguage', $prefs['blog_uid'], LANG)) {
    // If cannot install from lang file, setup the English lang.
    if (!install_language_from_file(LANG)) {
        $lang = 'en-gb';
        include_once txpath.'/setup/en-gb.php';

        if (!@$lastmod) {
            $lastmod = '0000-00-00 00:00:00';
        }

        foreach ($en_gb_lang as $evt_name => $evt_strings) {
            foreach ($evt_strings as $lang_key => $lang_val) {
                $lang_val = doSlash($lang_val);

                if (@$lang_val) {
                    mysqli_query($link, "INSERT DELAYED INTO `".PFX."txp_lang` SET lang='en-gb', name='".$lang_key."', event='".$evt_name."', data='".$lang_val."', lastmod='".$lastmod."'");
                }
            }
        }
    }
} else {
    $response = $client->getResponse();
    $lang_struct = unserialize($response);

    foreach ($lang_struct as $item) {
        foreach ($item as $name => $value) {
            $item[$name] = doSlash($value);
        }

        mysqli_query($link, "INSERT DELAYED INTO `".PFX."txp_lang` SET lang='".LANG."', name='".$item['name']."', event='".$item['event']."', data='".$item['data']."', lastmod='".strftime('%Y%m%d%H%M%S', $item['uLastmod'])."'");
    }
}

mysqli_query($link, "FLUSH TABLE `".PFX."txp_lang`");

/**
 * Stub replacement for txplib_db.php/safe_escape()
 *
 * @ignore
 */

function safe_escape($in = '')
{
    global $link;
    return mysqli_real_escape_string($link, $in);
}

function file2sql($filename) {
    return "'".doSlash(file_get_contents(txpath.'/setup/'.$filename))."'";
}

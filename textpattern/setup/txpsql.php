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

if (!defined('TXP_INSTALL')) {
    exit;
}

@ignore_user_abort(1);
@set_time_limit(0);

global $DB;
include txpath.'/lib/txplib_db.php';

if (numRows(safe_query("SHOW TABLES LIKE '".PFX."textpattern'"))) {
    die("Textpattern database table already exists. Can't run setup.");
}

$tabletype = '';
foreach ($DB->table_options as $name => $value) {
    $tabletype .= ' '.strtoupper($name).' = '.$value;
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

$siteurl = str_replace("http://", '', $_SESSION['siteurl']);
$siteurl = str_replace(' ', '%20', rtrim($siteurl, "/"));
$urlpath = preg_replace('#^[^/]+#', '', $siteurl);
$theme = $_SESSION['theme'] ? $_SESSION['theme'] : 'hive';
$themedir = txpath.DS.'setup';

$create_sql = array();

$create_sql[] = "CREATE TABLE `".PFX."textpattern` (
    ID              INT          NOT NULL AUTO_INCREMENT,
    Posted          DATETIME     NOT NULL,
    Expires         DATETIME         NULL DEFAULT NULL,
    AuthorID        VARCHAR(64)  NOT NULL DEFAULT '',
    LastMod         DATETIME     NOT NULL,
    LastModID       VARCHAR(64)  NOT NULL DEFAULT '',
    Title           VARCHAR(255) NOT NULL DEFAULT '',
    Title_html      VARCHAR(255) NOT NULL DEFAULT '',
    Body            MEDIUMTEXT   NOT NULL,
    Body_html       MEDIUMTEXT   NOT NULL,
    Excerpt         TEXT         NOT NULL,
    Excerpt_html    MEDIUMTEXT   NOT NULL,
    Image           VARCHAR(255) NOT NULL DEFAULT '',
    Category1       VARCHAR(64)  NOT NULL DEFAULT '',
    Category2       VARCHAR(64)  NOT NULL DEFAULT '',
    Annotate        INT          NOT NULL DEFAULT '0',
    AnnotateInvite  VARCHAR(255) NOT NULL DEFAULT '',
    comments_count  INT          NOT NULL DEFAULT '0',
    Status          INT          NOT NULL DEFAULT '4',
    textile_body    VARCHAR(32)  NOT NULL DEFAULT '1',
    textile_excerpt VARCHAR(32)  NOT NULL DEFAULT '1',
    Section         VARCHAR(255) NOT NULL DEFAULT '',
    override_form   VARCHAR(255) NOT NULL DEFAULT '',
    Keywords        VARCHAR(255) NOT NULL DEFAULT '',
    description     VARCHAR(255) NOT NULL DEFAULT '',
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
    feed_time       DATE         NOT NULL,

    PRIMARY KEY                 (ID),
    INDEX    categories_idx     (Category1(10), Category2(10)),
    INDEX    Posted             (Posted),
    INDEX    Expires_idx        (Expires),
    INDEX    author_idx         (AuthorID),
    INDEX    section_status_idx (Section(249), Status),
    INDEX    url_title_idx      (url_title(250)),
    FULLTEXT searching          (Title, Body)
) $tabletype ";

$setup_comment_invite = (gTxt('setup_comment_invite') == 'setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite');

$textile = new \Netcarver\Textile\Parser();

$article['body']    = file_get_contents(txpath.DS.'setup'.DS.'article.body.textile');
$article['excerpt'] = file_get_contents(txpath.DS.'setup'.DS.'article.excerpt.textile');
$article = str_replace('siteurl', $urlpath, $article);
$article['body_html']    = $textile->textileThis($article['body']);
$article['excerpt_html'] = $textile->textileThis($article['excerpt']);
$article = doSlash($article);

$create_sql[] = "INSERT INTO `".PFX."textpattern` VALUES (1, NOW(), NULL, '".doSlash($_SESSION['name'])."', NOW(), '', 'Welcome to your site', '', '".$article['body']."', '".$article['body_html']."', '".$article['excerpt']."', '".$article['excerpt_html']."', '', 'hope-for-the-future', 'meaningful-labor', 1, '".$setup_comment_invite."', 1, 4, '1', '1', 'articles', '', '', '', 'welcome-to-your-site', '', '', '', '', '', '', '', '', '', '', '".md5(uniqid(rand(), true))."', NOW())";

$create_sql[] = "CREATE TABLE `".PFX."txp_category` (
    id          INT          NOT NULL AUTO_INCREMENT,
    name        VARCHAR(64)  NOT NULL DEFAULT '',
    type        VARCHAR(64)  NOT NULL DEFAULT '',
    parent      VARCHAR(64)  NOT NULL DEFAULT '',
    lft         INT          NOT NULL DEFAULT '0',
    rgt         INT          NOT NULL DEFAULT '0',
    title       VARCHAR(255) NOT NULL DEFAULT '',
    description VARCHAR(255) NOT NULL DEFAULT '',

    PRIMARY KEY (id)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (1, 'root', 'article', '', 1, 8, 'root', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (2, 'root', 'link', '', 1, 4, 'root', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (3, 'root', 'image', '', 1, 4, 'root', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (4, 'root', 'file', '', 1, 2, 'root', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (5, 'hope-for-the-future', 'article', 'root', 2, 3, 'Hope for the future', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (6, 'meaningful-labor', 'article', 'root', 4, 5, 'Meaningful labor', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (7, 'reciprocal-affection', 'article', 'root', 6, 7, 'Reciprocal affection', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (8, 'textpattern', 'link', 'root', 2, 3, 'Textpattern', '')";

$create_sql[] = "CREATE TABLE `".PFX."txp_css` (
    name VARCHAR(255) NOT NULL,
    css  MEDIUMTEXT   NOT NULL,

    UNIQUE name (name(250))
) $tabletype ";

foreach (scandir($themedir.DS.'styles') as $cssfile) {
    if (preg_match('/^(\w+)\.css$/', $cssfile, $match)) {
        $css = doSlash(file_get_contents($themedir.DS.'styles'.DS.$cssfile));
        $create_sql[] = "INSERT INTO `".PFX."txp_css`(name, css) VALUES('".$match[1]."', '".$css."')";
    }
}

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss` (
    discussid INT(6) ZEROFILL NOT NULL AUTO_INCREMENT,
    parentid  INT             NOT NULL DEFAULT '0',
    name      VARCHAR(255)    NOT NULL DEFAULT '',
    email     VARCHAR(254)    NOT NULL DEFAULT '',
    web       VARCHAR(255)    NOT NULL DEFAULT '',
    ip        VARCHAR(100)    NOT NULL DEFAULT '',
    posted    DATETIME        NOT NULL,
    message   TEXT            NOT NULL,
    visible   TINYINT         NOT NULL DEFAULT '1',

    PRIMARY KEY    (discussid),
    INDEX parentid (parentid)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_discuss` VALUES (000001, 1, 'Donald Swain', 'donald.swain@example.com', 'example.com', '127.0.0.1', NOW(), '<p>I enjoy your site very much.</p>', 1)";

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss_nonce` (
    issue_time DATETIME     NOT NULL,
    nonce      VARCHAR(255) NOT NULL DEFAULT '',
    used       TINYINT      NOT NULL DEFAULT '0',
    secret     VARCHAR(255) NOT NULL DEFAULT '',

    PRIMARY KEY (nonce(250))
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_file` (
    id          INT          NOT NULL AUTO_INCREMENT,
    filename    VARCHAR(255) NOT NULL DEFAULT '',
    title       VARCHAR(255) DEFAULT NULL,
    category    VARCHAR(64)  NOT NULL DEFAULT '',
    permissions VARCHAR(32)  NOT NULL DEFAULT '0',
    description TEXT         NOT NULL,
    downloads   INT UNSIGNED NOT NULL DEFAULT '0',
    status	SMALLINT     NOT NULL DEFAULT '4',
    modified    DATETIME     NOT NULL,
    created     DATETIME     NOT NULL,
    size        BIGINT       DEFAULT NULL,
    author      VARCHAR(64)  NOT NULL DEFAULT '',

    PRIMARY KEY       (id),
    UNIQUE filename   (filename(250)),
    INDEX  author_idx (author)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_form` (
    name VARCHAR(255) NOT NULL DEFAULT '',
    type VARCHAR(28)  NOT NULL DEFAULT '',
    Form TEXT         NOT NULL,

    PRIMARY KEY (name(250))
) $tabletype ";

foreach (scandir($themedir.DS.'forms') as $formfile) {
    if (preg_match('/^(\w+).(\w+)\.txp$/', $formfile, $match)) {
        $form = doSlash(file_get_contents($themedir.DS.'forms'.DS.$formfile));
        $create_sql[] = "INSERT INTO `".PFX."txp_form`(type, name, Form)
            VALUES('".$match[1]."', '".$match[2]."', '".$form."')";
    }
}

$create_sql[] = "CREATE TABLE `".PFX."txp_image` (
    id        INT          NOT NULL AUTO_INCREMENT,
    name      VARCHAR(255) NOT NULL DEFAULT '',
    category  VARCHAR(64)  NOT NULL DEFAULT '',
    ext       VARCHAR(20)  NOT NULL DEFAULT '',
    w         INT          NOT NULL DEFAULT '0',
    h         INT          NOT NULL DEFAULT '0',
    alt       VARCHAR(255) NOT NULL DEFAULT '',
    caption   TEXT         NOT NULL,
    date      DATETIME     NOT NULL,
    author    VARCHAR(64)  NOT NULL DEFAULT '',
    thumbnail INT          NOT NULL DEFAULT '0',
    thumb_w   INT          NOT NULL DEFAULT '0',
    thumb_h   INT          NOT NULL DEFAULT '0',

    PRIMARY KEY      (id),
    INDEX author_idx (author)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_lang` (
    id      INT         NOT NULL AUTO_INCREMENT,
    lang    VARCHAR(16) NOT NULL,
    name    VARCHAR(64) NOT NULL,
    event   VARCHAR(64) NOT NULL,
    owner   VARCHAR(64) NOT NULL DEFAULT '',
    data    TEXT,
    lastmod TIMESTAMP,

    PRIMARY KEY   (id),
    UNIQUE lang   (lang, name),
    INDEX  lang_2 (lang, event),
    INDEX  owner  (owner)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_link` (
    id          INT          NOT NULL AUTO_INCREMENT,
    date        DATETIME     NOT NULL,
    category    VARCHAR(64)  NOT NULL DEFAULT '',
    url         TEXT         NOT NULL,
    linkname    VARCHAR(255) NOT NULL DEFAULT '',
    linksort    VARCHAR(128) NOT NULL DEFAULT '',
    description TEXT         NOT NULL,
    author      VARCHAR(64)  NOT NULL DEFAULT '',

    PRIMARY KEY      (id),
    INDEX author_idx (author)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (1, NOW(), 'textpattern', 'http://textpattern.com/',             'Textpattern Website',            '10', '', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (2, NOW(), 'textpattern', 'http://docs.textpattern.io/',         'Textpattern User Documentation', '20', '', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (3, NOW(), 'textpattern', 'http://textpattern.org/',             'Textpattern Resources',          '30', '', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (4, NOW(), 'textpattern', 'http://textpattern.com/@textpattern', '@textpattern',                   '40', '', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (5, NOW(), 'textpattern', 'http://textpattern.com/+',            '+Textpattern CMS',               '50', '', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (6, NOW(), 'textpattern', 'http://textpattern.com/facebook',     'Textpattern Facebook Group',     '60', '', '')";

$create_sql[] = "CREATE TABLE `".PFX."txp_log` (
    id     INT          NOT NULL AUTO_INCREMENT,
    time   DATETIME     NOT NULL,
    host   VARCHAR(255) NOT NULL DEFAULT '',
    page   VARCHAR(255) NOT NULL DEFAULT '',
    refer  MEDIUMTEXT   NOT NULL,
    status INT          NOT NULL DEFAULT '200',
    method VARCHAR(16)  NOT NULL DEFAULT 'GET',
    ip     VARCHAR(45)  NOT NULL DEFAULT '',

    PRIMARY KEY (id),
    INDEX time  (time),
    INDEX ip    (ip)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_page` (
    name      VARCHAR(255) NOT NULL DEFAULT '',
    user_html TEXT         NOT NULL,

    PRIMARY KEY (name(250))
) $tabletype ";

foreach (scandir($themedir.DS.'pages') as $pagefile) {
    if (preg_match('/^(\w+)\.txp$/', $pagefile, $match)) {
        $page = doSlash(file_get_contents($themedir.DS.'pages'.DS.$pagefile));
        $create_sql[] = "INSERT INTO `".PFX."txp_page`(name, user_html) VALUES('".$match[1]."', '".$page."')";
    }
}

$create_sql[] = "CREATE TABLE `".PFX."txp_plugin` (
    name         VARCHAR(64)       NOT NULL DEFAULT '',
    status       INT               NOT NULL DEFAULT '1',
    author       VARCHAR(128)      NOT NULL DEFAULT '',
    author_uri   VARCHAR(128)      NOT NULL DEFAULT '',
    version      VARCHAR(255)      NOT NULL DEFAULT '1.0',
    description  TEXT              NOT NULL,
    help         TEXT              NOT NULL,
    code         MEDIUMTEXT        NOT NULL,
    code_restore MEDIUMTEXT        NOT NULL,
    code_md5     VARCHAR(32)       NOT NULL DEFAULT '',
    type         INT               NOT NULL DEFAULT '0',
    load_order   TINYINT  UNSIGNED NOT NULL DEFAULT '5',
    flags        SMALLINT UNSIGNED NOT NULL DEFAULT '0',

    UNIQUE name            (name),
    INDEX  status_type_idx (status, type)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_prefs` (
    prefs_id  INT               NOT NULL DEFAULT '1',
    name      VARCHAR(255)      NOT NULL DEFAULT '',
    val       TEXT              NOT NULL,
    type      SMALLINT UNSIGNED NOT NULL DEFAULT '2',
    event     VARCHAR(255)      NOT NULL DEFAULT 'publish',
    html      VARCHAR(255)      NOT NULL DEFAULT 'text_input',
    position  SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    user_name VARCHAR(64)       NOT NULL DEFAULT '',

    UNIQUE prefs_idx (prefs_id, name(185), user_name),
    INDEX  name      (name(250)),
    INDEX  user_name (user_name)
) $tabletype ";


include txpath.'/lib/prefs.php';

$prefs = array_merge_recursive($default_prefs, new_user_prefs($_SESSION['name']));

foreach ($prefs as $event => $event_prefs) {
    foreach ($event_prefs as $p) {
        $username = empty($p[5]) ? '' : doSlash($p[5]);
        $create_sql[] = "INSERT INTO `".PFX."txp_prefs` (event, type, position, html, name, val, user_name) ".
            "VALUES ('".$event."', ".$p[0].", ".$p[1].", '".$p[2]."', '".$p[3]."', '".doSlash($p[4])."', '".$username."')";
    }
}

$create_sql[] = "CREATE TABLE `".PFX."txp_section` (
    name         VARCHAR(255) NOT NULL DEFAULT '',
    page         VARCHAR(255) NOT NULL DEFAULT '',
    css          VARCHAR(255) NOT NULL DEFAULT '',
    description  VARCHAR(255) NOT NULL DEFAULT '',
    in_rss       INT          NOT NULL DEFAULT '1',
    on_frontpage INT          NOT NULL DEFAULT '1',
    searchable   INT          NOT NULL DEFAULT '1',
    title        VARCHAR(255) NOT NULL DEFAULT '',

    PRIMARY KEY (name(250))
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('articles', 'archive', 'default', '', 1, 1, 1, 'Articles')";
$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('default', 'default', 'default', '', 1, 1, 1, 'Default')";

$create_sql[] = "CREATE TABLE `".PFX."txp_users` (
    user_id     INT          NOT NULL AUTO_INCREMENT,
    name        VARCHAR(64)  NOT NULL DEFAULT '',
    pass        VARCHAR(128) NOT NULL,
    RealName    VARCHAR(255) NOT NULL DEFAULT '',
    email       VARCHAR(254) NOT NULL DEFAULT '',
    privs       TINYINT      NOT NULL DEFAULT '1',
    last_access DATETIME         NULL DEFAULT NULL,
    nonce       VARCHAR(64)  NOT NULL DEFAULT '',

    PRIMARY KEY (user_id),
    UNIQUE name (name)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_users` VALUES (
    1,
    '".doSlash($_SESSION['name'])."',
    '".doSlash(txp_hash_password($_SESSION['pass']))."',
    '".doSlash($_SESSION['realname'])."',
    '".doSlash($_SESSION['email'])."',
    1,
    NOW(),
    '".md5(uniqid(rand(), true))."')";

$create_sql[] = "CREATE TABLE `".PFX."txp_token` (
    id           INT          NOT NULL AUTO_INCREMENT,
    reference_id INT          NOT NULL,
    type         VARCHAR(255) NOT NULL,
    selector     VARCHAR(12)  NOT NULL DEFAULT '',
    token        VARCHAR(255) NOT NULL,
    expires      DATETIME         NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE ref_type (reference_id, type(50))
) $tabletype ";

$GLOBALS['txp_install_successful'] = true;
$GLOBALS['txp_err_count'] = 0;
$GLOBALS['txp_err_html'] = '';

foreach ($create_sql as $query) {
    $result = safe_query($query);

    if (!$result) {
        $GLOBALS['txp_err_count']++;
        $GLOBALS['txp_err_html'] .= '<li>'.n.
            '<b>'.htmlspecialchars(mysqli_error($DB->link)).'</b><br />'.n.
            '<pre>'.htmlspecialchars($query).'</pre>'.n.'</li>'.n;
        $GLOBALS['txp_install_successful'] = false;
    }
}

require_once txpath.'/lib/IXRClass.php';
$client = new IXR_Client('http://rpc.textpattern.com');

if (!$client->query('tups.getLanguage', $blog_uid, LANG)) {
    // If cannot install from lang file, setup the English lang.
    if (!install_language_from_file(LANG)) {
        $lang = 'en-gb';
        include_once txpath.'/setup/en-gb.php';

        if (!@$lastmod) {
            $lastmod = '1970-01-01 00:00:00';
        }

        foreach ($en_gb_lang as $evt_name => $evt_strings) {
            foreach ($evt_strings as $lang_key => $lang_val) {
                $lang_val = doSlash($lang_val);

                if (@$lang_val) {
                    safe_query("INSERT DELAYED INTO `".PFX."txp_lang` SET
                        lang    = 'en-gb',
                        name    = '".$lang_key."',
                        event   = '".$evt_name."',
                        data    = '".$lang_val."',
                        lastmod = '".$lastmod."'");
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

        safe_query("INSERT DELAYED INTO `".PFX."txp_lang` SET
            lang    = '".LANG."',
            name    = '".$item['name']."',
            event   = '".$item['event']."',
            data    = '".$item['data']."',
            lastmod = '".strftime('%Y%m%d%H%M%S', $item['uLastmod'])."'");
    }
}

safe_query("FLUSH TABLE `".PFX."txp_lang`");

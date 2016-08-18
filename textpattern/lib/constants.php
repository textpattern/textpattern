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

/**
 * Constants.
 */

if (!defined('TXP_DEBUG')) {
    /**
     * If set to "1", dumps debug log to the temp directory.
     *
     * This constant can be overridden from the config.php.
     *
     * @package Debug
     * @example
     * define('TXP_DEBUG', 1);
     */

    define('TXP_DEBUG', 0);
}

/**
 * Comment spam status.
 *
 * @package Comment
 */

define('SPAM', -1);

/**
 * Comment moderate status.
 *
 * @package Comment
 */

define('MODERATE', 0);

/**
 * Comment spam status.
 *
 * @package Comment
 */

define('VISIBLE', 1);

/**
 * Comment reload status.
 *
 * @package Comment
 */

define('RELOAD', -99);

if (!defined('RPC_SERVER')) {
    /**
     * RPC server location.
     *
     * This constant can be overridden from the config.php.
     *
     * @example
     * define('RPC_SERVER', 'http://rpc.example.com');
     */

    define('RPC_SERVER', 'http://rpc.textpattern.com');
}

if (!defined('HELP_URL')) {
    /**
     * The location where help documentation is fetched.
     *
     * This constant can be overridden from the config.php.
     *
     * @example
     * define('HELP_URL', 'http://rpc.example.com/help/');
     */

    define('HELP_URL', 'http://rpc.textpattern.com/help/');
}

/**
 * Do not format text.
 *
 * @var     string
 * @package Textfilter
 */

define('LEAVE_TEXT_UNTOUCHED', '0');

/**
 * Format text with Textile.
 *
 * @var     string
 * @package Textfilter
 */

define('USE_TEXTILE', '1');

/**
 * Replace line breaks with HTML &lt;br /&gt; tag.
 *
 * @var     string
 * @package Textfilter
 */

define('CONVERT_LINEBREAKS', '2');

/**
 * System is Windows if TRUE.
 *
 * @package System
 */

define('IS_WIN', strpos(strtoupper(PHP_OS), 'WIN') === 0);

/**
 * Directory separator character.
 *
 * @package File
 */

define('DS', defined('DIRECTORY_SEPARATOR') ? DIRECTORY_SEPARATOR : (IS_WIN ? '\\' : '/'));

/**
 * Magic quotes GPC, TRUE if on.
 *
 * @package Network
 */

define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

if (!defined('REGEXP_UTF8')) {
    /**
     * TRUE if the system supports UTF-8 regex patterns.
     *
     * This constant can be overridden from the config.php in case UTF-8 regex
     * patterns cause issues.
     *
     * @package System
     * @example
     * define('REGEXP_UTF8', false);
     */

    define('REGEXP_UTF8', @preg_match('@\pL@u', 'q'));
}

/**
 * Permlink URL mode.
 *
 * @package    URL
 * @deprecated ?
 */

define('PERMLINKURL', 0);

/**
 * Pagelink URL mode.
 *
 * @package    URL
 * @deprecated ?
 */

define('PAGELINKURL', 1);

if (!defined('EXTRA_MEMORY')) {
    /**
     * Allocated extra memory.
     *
     * Used when creating thumbnails for instance.
     *
     * This constant can be overridden from the config.php.
     *
     * @package System
     * @example
     * define('EXTRA_MEMORY', '64M');
     */

    define('EXTRA_MEMORY', '32M');
}

/**
 * PHP is run as CGI.
 *
 * @package System
 */

define('IS_CGI', strpos(PHP_SAPI, 'cgi') === 0);

/**
 * PHP is run as FCGI.
 *
 * @package System
 */

define('IS_FASTCGI', IS_CGI and empty($_SERVER['FCGI_ROLE']) and empty($_ENV['FCGI_ROLE']));

/**
 * PHP is run as Apache module.
 *
 * @package System
 */

define('IS_APACHE', !IS_CGI and strpos(PHP_SAPI, 'apache') === 0);

/**
 * Preference is user-private.
 *
 * @package Pref
 * @see     set_pref()
 */

define('PREF_PRIVATE', true);

/**
 * Preference is global.
 *
 * @package Pref
 * @see     set_pref()
 */

define('PREF_GLOBAL', false);

/**
 * Preference type is basic.
 *
 * @package    Pref
 * @deprecated in 4.6.0
 * @see        PREF_CORE
 * @see        set_pref()
 */

define('PREF_BASIC', 0);

/**
 * Preference type is a core setting.
 *
 * @package Pref
 * @see     set_pref()
 */

define('PREF_CORE', 0);

/**
 * Preference type is advanced.
 *
 * @package    Pref
 * @deprecated in 4.6.0
 * @see        PREF_CORE
 * @see        PREF_PLUGIN
 * @see        set_pref()
 */

define('PREF_ADVANCED', 1);

/**
 * Preference type is a plugin or third party setting.
 *
 * @package Pref
 * @see     set_pref()
 */

define('PREF_PLUGIN', 1);

/**
 * Preference type is hidden.
 *
 * @package Pref
 * @see     set_pref()
 */

define('PREF_HIDDEN', 2);

/**
 * Plugin flag: has an options page.
 */

define('PLUGIN_HAS_PREFS', 0x0001);

/**
 * Plugin flag: offers lifecycle callbacks.
 */

define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);

/**
 * Reserved bits for use by Textpattern core.
 */

define('PLUGIN_RESERVED_FLAGS', 0x0fff);

if (!defined('LOG_REFERER_PROTOCOLS')) {
    /**
     * Sets accepted protocols for HTTP referrer header.
     *
     * This constant can be overridden from the config.php.
     *
     * @package Log
     * @since   4.6.0
     * @example
     * define('LOG_REFERER_PROTOCOLS', 'http');
     */

    define('LOG_REFERER_PROTOCOLS', 'http, https');
}

if (!defined('PASSWORD_LENGTH')) {
    /**
     * Password default length, in characters.
     *
     * This constant can be overridden from the config.php.
     *
     * @package User
     * @example
     * define('PASSWORD_LENGTH', 14);
     */

    define('PASSWORD_LENGTH', 16);
}

if (!defined('PASSWORD_COMPLEXITY')) {
    /**
     * Password iteration strength count.
     *
     * This constant can be overridden from the config.php.
     *
     * @package User
     * @example
     * define('PASSWORD_COMPLEXITY', 2);
     */

    define('PASSWORD_COMPLEXITY', 8);
}

if (!defined('PASSWORD_PORTABILITY')) {
    /**
     * Passwords are created portable if TRUE.
     *
     * This constant can be overridden from the config.php.
     *
     * @package User
     * @example
     * define('PASSWORD_PORTABILITY', false);
     */

    define('PASSWORD_PORTABILITY', true);
}

if (!defined('PASSWORD_SYMBOLS')) {
    /**
     * Symbols used in auto-generated passwords.
     *
     * This constant can be overridden from the config.php.
     *
     * @package User
     * @since   4.6.0
     * @see     generate_password()
     * @example
     * define('PASSWORD_SYMBOLS', '23456789ABCDEFGHJKLMNPQRSTUYXZabcdefghijkmnopqrstuvwxyz_?!-@$%^*;:');
     */

    define('PASSWORD_SYMBOLS', '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz_-!?.');
}

if (!defined('HASHING_ALGORITHM')) {
    /**
     * Algorithm to use for hashing passwords/reset requests.
     *
     * This constant can be overridden from the config.php.
     *
     * @package User
     * @since   4.6.0
     * @see     PHP's hash_algos() function
     * @example
     * define('HASHING_ALGORITHM', 'whirlpool');
     */

    define('HASHING_ALGORITHM', 'ripemd256');
}

if (!defined('SALT_LENGTH')) {
    /**
     * Length of salt/selector hashes.
     *
     * This constant can be overridden from the config.php.
     *
     * @package User
     * @since   4.6.0
     * @example
     * define('SALT_LENGTH', '80');
     */

    define('SALT_LENGTH', '64');
}

if (!defined('RESET_EXPIRY_MINUTES')) {
    /**
     * Length of time (in minutes) that a password reset request remains valid.
     *
     * This constant can be overridden from the config.php.
     * Values under 60 may fall foul of DST changeover times, but meh.
     *
     * @package User
     * @since   4.6.0
     * @example
     * define('RESET_EXPIRY_MINUTES', '120');
     */

    define('RESET_EXPIRY_MINUTES', '90');
}

if (!defined('RESET_RATE_LIMIT_MINUTES')) {
    /**
     * Minutes during which multiple user-submitted password reset requests are ignored.
     *
     * This constant can be overridden from the config.php.
     *
     * @package User
     * @since   4.6.0
     * @example
     * define('RESET_RATE_LIMIT_MINUTES', '15');
     */

    define('RESET_RATE_LIMIT_MINUTES', '5');
}

if (!defined('ACTIVATION_EXPIRY_HOURS')) {
    /**
     * Length of time (in hours) that a password activation (new account) link remains valid.
     *
     * This constant can be overridden from the config.php.
     *
     * @package User
     * @since   4.6.0
     * @example
     * define('ACTIVATION_EXPIRY_HOURS', '48');
     */

    define('ACTIVATION_EXPIRY_HOURS', '168');
}

if (!defined('LOGIN_COOKIE_HTTP_ONLY')) {
    /**
     * If TRUE, login cookie is set just for HTTP.
     *
     * This constant can be overridden from the config.php.
     *
     * @package CSRF
     * @example
     * define('LOGIN_COOKIE_HTTP_ONLY', false);
     */

    define('LOGIN_COOKIE_HTTP_ONLY', true);
}

if (!defined('X_FRAME_OPTIONS')) {
    /**
     * Sets X-Frame-Options HTTP header's value.
     *
     * This is used to prevent framing of authenticated pages.
     *
     * This constant can be overridden from the config.php.
     *
     * @package CSRF
     * @example
     * define('X_FRAME_OPTIONS', 'DENY');
     */

    define('X_FRAME_OPTIONS', 'SAMEORIGIN');
}

if (!defined('X_UA_COMPATIBLE')) {
    /**
     * Sets X-UA-Compatible HTTP header's value.
     *
     * This constant can be overridden from the config.php.
     *
     * @since   4.6.0
     * @package HTML
     * @example
     * define('X_UA_COMPATIBLE', 'ie=ie9');
     */

    define('X_UA_COMPATIBLE', 'ie=edge');
}

if (!defined('AJAX_TIMEOUT')) {
    /**
     * AJAX timeout in seconds.
     *
     * This constant can be overridden from the config.php.
     *
     * @package Ajax
     * @example
     * define('AJAX_TIMEOUT', 10);
     */

    define('AJAX_TIMEOUT', max(30000, 1000 * @ini_get('max_execution_time')));
}

/**
 * Render on initial synchronous page load.
 *
 * @since   4.5.0
 * @package Ajax
 */

define('PARTIAL_STATIC', 0);

/**
 * Render as HTML partial on every page load.
 *
 * @since   4.5.0
 * @package Ajax
 */

define('PARTIAL_VOLATILE', 1);

/**
 * Render as an element's jQuery.val() on every page load.
 *
 * @since   4.5.0
 * @package Ajax
 */

define('PARTIAL_VOLATILE_VALUE', 2);

/**
 * Draft article status ID.
 *
 * @package Article
 */

define('STATUS_DRAFT', 1);

/**
 * Hidden article status ID.
 *
 * @package Article
 */

define('STATUS_HIDDEN', 2);

/**
 * Pending article status ID.
 *
 * @package Article
 */

define('STATUS_PENDING', 3);

/**
 * Live article status ID.
 *
 * @package Article
 */

define('STATUS_LIVE', 4);

/**
 * Sticky article status ID.
 *
 * @package Article
 */

define('STATUS_STICKY', 5);

if (!defined('WRITE_RECENT_ARTICLES_COUNT')) {
    /**
     * Number of recent articles displayed on the Write panel.
     *
     * This constant can be overridden from the config.php.
     *
     * @package Admin\Article
     * @since   4.6.0
     * @example
     * define('WRITE_RECENT_ARTICLES_COUNT', 5);
     */

    define('WRITE_RECENT_ARTICLES_COUNT', 10);
}

/**
 * Input size extra large.
 *
 * @since   4.5.0
 * @package Form
 */

define('INPUT_XLARGE', 96);

/**
 * Input size large.
 *
 * @since   4.5.0
 * @package Form
 */

define('INPUT_LARGE', 64);

/**
 * Input size regular.
 *
 * @since   4.5.0
 * @package Form
 */

define('INPUT_REGULAR', 32);

/**
 * Input size medium.
 *
 * @since   4.5.0
 * @package Form
 */

define('INPUT_MEDIUM', 16);

/**
 * Input size small.
 *
 * @since   4.5.0
 * @package Form
 */

define('INPUT_SMALL', 8);

/**
 * Input size extra small.
 *
 * @since   4.5.0
 * @package Form
 */

define('INPUT_XSMALL', 4);

/**
 * Input size tiny.
 *
 * @since   4.5.0
 * @package Form
 */

define('INPUT_TINY', 2);

/**
 * Textarea height large.
 *
 * @since   4.6.0
 * @package Form
 */

define('TEXTAREA_HEIGHT_LARGE', 32);

/**
 * Textarea height regular.
 *
 * @since   4.6.0
 * @package Form
 */

define('TEXTAREA_HEIGHT_REGULAR', 16);

/**
 * Textarea height medium.
 *
 * @since   4.6.0
 * @package Form
 */

define('TEXTAREA_HEIGHT_MEDIUM', 8);

/**
 * Textarea height small.
 *
 * @since   4.6.0
 * @package Form
 */

define('TEXTAREA_HEIGHT_SMALL', 4);

/**
 * Required PHP version.
 *
 * @since   4.5.0
 * @package System
 */

define('REQUIRED_PHP_VERSION', '5.3.3');

/**
 * File integrity status good.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_GOOD', 1);

/**
 * File integrity status modified.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_MODIFIED', 2);

/**
 * File integrity not readable.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_NOT_READABLE', 3);

/**
 * File integrity file missing.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_MISSING', 4);

/**
 * File integrity not a file.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_NOT_FILE', 5);

/**
 * Return integrity status.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_STATUS', 0x1);

/**
 * Return integrity MD5 hashes.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_MD5', 0x2);

/**
 * Return full paths.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_REALPATH', 0x4);

/**
 * Return a digest.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_DIGEST', 0x8);

/**
 * Return a parsed checksum file's contents.
 *
 * @since   4.6.0
 * @package Debug
 * @see     check_file_integrity()
 */

define('INTEGRITY_TABLE', 0x10);

/**
 * Link to an external script.
 *
 * @since   4.6.0
 * @package HTML
 * @see     script_js()
 */

define('TEXTPATTERN_SCRIPT_URL', 0x1);

/**
 * Attach version number to script URL if stable.
 *
 * The install is considered as a 'stable' if the version number doesn't contain
 * a '-dev' tag.
 *
 * @since   4.6.0
 * @package HTML
 * @see     script_js()
 */

define('TEXTPATTERN_SCRIPT_ATTACH_VERSION', 0x2);

/**
 * The localised string is owned by the core system.
 *
 * The string will be updated from the remote language server.
 *
 * @since   4.6.0
 * @package L10n
 */

define('TEXTPATTERN_LANG_OWNER_SYSTEM', '');

/**
 * The localised string is owned by the individual site.
 *
 * The string will not be updated from the remote language server.
 *
 * @since   4.6.0
 * @package L10n
 */

define('TEXTPATTERN_LANG_OWNER_SITE', 'site');

/**
 * Strip empty values.
 *
 * @since   4.6.0
 * @package HTML
 * @see     join_atts(), do_list_unique()
 */

define('TEXTPATTERN_STRIP_NONE',         0);
define('TEXTPATTERN_STRIP_EMPTY',        0x1);
define('TEXTPATTERN_STRIP_EMPTY_STRING', 0x2);

/**
 * Sends an adaptive announcement.
 *
 * The rendered message type is based on the context mode.
 *
 * @since   4.6.0
 * @package Announce
 * @see     announce()
 */

define('TEXTPATTERN_ANNOUNCE_ADAPTIVE', 0x1);

/**
 * Sends a modal announcement.
 *
 * The announcement is instructed to be rendered as soon as possible, as a modal
 * alert window.
 *
 * @since   4.6.0
 * @package Announce
 * @see     announce()
 */

define('TEXTPATTERN_ANNOUNCE_MODAL', 0x2);

/**
 * Sends an asynchronous announcement.
 *
 * @since   4.6.0
 * @package Announce
 * @see     announce()
 */

define('TEXTPATTERN_ANNOUNCE_ASYNC', 0x4);

/**
 * Sends a synchronous announcement.
 *
 * @since   4.6.0
 * @package Announce
 * @see     announce()
 */

define('TEXTPATTERN_ANNOUNCE_REGULAR', 0x8);

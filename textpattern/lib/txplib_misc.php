<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Collection of miscellaneous tools.
 *
 * @package Misc
 */

/**
 * Strips NULL bytes.
 *
 * @param  string|array $in The input value
 * @return mixed
 */

function deNull($in)
{
    return is_array($in) ? doArray($in, 'deNull') : strtr($in, array("\0" => ''));
}

/**
 * Strips carriage returns and linefeeds.
 *
 * @param  string|array $in The input value
 * @return mixed
 */

function deCRLF($in)
{
    return is_array($in) ? doArray($in, 'deCRLF') : strtr($in, array(
        "\n" => '',
        "\r" => '',
    ));
}

/**
 * Applies a callback to a given string or an array.
 *
 * @param  string|array $in       An array or a string to run through the callback function
 * @param  callback     $function The callback function
 * @return mixed
 * @example
 * echo doArray(array('value1', 'value2'), 'intval');
 */

function doArray($in, $function)
{
    if (is_array($in)) {
        return array_map($function, $in);
    }

    if (is_array($function)) {
        return call_user_func($function, $in);
    }

    return $function($in);
}

/**
 * Un-quotes a quoted string or an array of values.
 *
 * @param  string|array $in The input value
 * @return mixed
 */

function doStrip($in)
{
    return is_array($in) ? doArray($in, 'doStrip') : doArray($in, 'stripslashes');
}

/**
 * Strips HTML and PHP tags from a string or an array.
 *
 * @param  string|array $in The input value
 * @return mixed
 * @example
 * echo doStripTags('<p>Hello world!</p>');
 */

function doStripTags($in)
{
    return is_array($in) ? doArray($in, 'doStripTags') : doArray($in, 'strip_tags');
}

/**
 * Converts entity escaped brackets back to characters.
 *
 * @param  string|array $in The input value
 * @return mixed
 */

function doDeEnt($in)
{
    return doArray($in, 'deEntBrackets');
}

/**
 * Converts entity escaped brackets back to characters.
 *
 * @param  string $in The input value
 * @return string
 */

function deEntBrackets($in)
{
    $array = array(
        '&#60;'  => '<',
        '&lt;'   => '<',
        '&#x3C;' => '<',
        '&#62;'  => '>',
        '&gt;'   => '>',
        '&#x3E;' => '>',
    );

    foreach ($array as $k => $v) {
        $in = preg_replace("/".preg_quote($k)."/i", $v, $in);
    }

    return $in;
}

/**
 * Escapes special characters for use in an SQL statement.
 *
 * Always use this function when dealing with user-defined values in SQL
 * statements. If this function is not used to escape user-defined data in a
 * statement, the query is vulnerable to SQL injection attacks.
 *
 * @param   string|array $in The input value
 * @return  mixed An array of escaped values or a string depending on $in
 * @package DB
 * @example
 * echo safe_field('column', 'table', "color = '" . doSlash(gps('color')) . "'");
 */

function doSlash($in)
{
    return doArray($in, 'safe_escape');
}

/**
 * Escape SQL LIKE pattern's wildcards for use in an SQL statement.
 *
 * @param   string|array $in The input value
 * @return  mixed An array of escaped values or a string depending on $in
 * @since   4.6.0
 * @package DB
 * @example
 * echo safe_field('column', 'table', "color LIKE '" . doLike(gps('color')) . "'");
 */

function doLike($in)
{
    return doArray($in, 'safe_escape_like');
}

/**
 * A shell for htmlspecialchars() with $flags defaulting to ENT_QUOTES.
 *
 * @param   string $string The string being converted
 * @param   int    $flags A bitmask of one or more flags. The default is ENT_QUOTES
 * @param   string $encoding Defines encoding used in conversion. The default is UTF-8
 * @param   bool   $double_encode When double_encode is turned off PHP will not encode existing HTML entities, the default is to convert everything
 * @return  string
 * @see     https://www.php.net/manual/en/function.htmlspecialchars.php
 * @since   4.5.0
 * @package Filter
 */

function txpspecialchars($string, $flags = ENT_QUOTES, $encoding = 'UTF-8', $double_encode = true)
{
    //    Ignore ENT_HTML5 and ENT_XHTML for now.
    //    ENT_HTML5 and ENT_XHTML are defined in PHP 5.4+ but we consistently encode single quotes as &#039; in any doctype.
    //    global $prefs;
    //    static $h5 = null;
    //
    //    if (defined(ENT_HTML5)) {
    //        if ($h5 === null) {
    //            $h5 = ($prefs['doctype'] == 'html5' && txpinterface == 'public');
    //        }
    //
    //        if ($h5) {
    //            $flags = ($flags | ENT_HTML5) & ~ENT_HTML401;
    //        }
    //    }
    //
    return htmlspecialchars((string)$string, $flags, $encoding, $double_encode);
}

/**
 * Converts special characters to HTML entities.
 *
 * @param   array|string $in The input value
 * @return  mixed The array or string with HTML syntax characters escaped
 * @package Filter
 */

function doSpecial($in)
{
    return doArray($in, 'txpspecialchars');
}

/**
 * Converts the given value to NULL.
 *
 * @param   mixed $a The input value
 * @return  null
 * @package Filter
 * @access  private
 */

function _null($a)
{
    return null;
}

/**
 * Converts an array of values to NULL.
 *
 * @param   array $in The array
 * @return  array
 * @package Filter
 */

function array_null($in)
{
    return array_map('_null', $in);
}

/**
 * Escapes a page title. Converts &lt;, &gt;, ', " characters to HTML entities.
 *
 * @param   string $title The input string
 * @return  string The string escaped
 * @package Filter
 */

function escape_title($title)
{
    return strtr($title, array(
        '<' => '&#60;',
        '>' => '&#62;',
        "'" => '&#39;',
        '"' => '&#34;',
    ));
}

/**
 * Sanitises a string for use in a JavaScript string.
 *
 * Escapes \, \n, \r, " and ' characters. It removes 'PARAGRAPH SEPARATOR'
 * (U+2029) and 'LINE SEPARATOR' (U+2028). When you need to pass a string
 * from PHP to JavaScript, use this function to sanitise the value to avoid
 * XSS attempts.
 *
 * @param   string $js JavaScript input
 * @return  string Escaped JavaScript
 * @since   4.4.0
 * @package Filter
 */

function escape_js($js)
{
    $js = preg_replace('/[\x{2028}\x{2029}]/u', '', $js);

    return addcslashes($js, "\\\'\"\n\r");
}

/**
 * Escapes CDATA section for an XML document.
 *
 * @param   string $str The string
 * @return  string XML representation wrapped in CDATA tags
 * @package XML
 */

function escape_cdata($str)
{
    return '<![CDATA['.str_replace(']]>', ']]]><![CDATA[]>', $str).']]>';
}

/**
 * Returns a localisation string.
 *
 * @param   string $var    String name
 * @param   array  $atts   Replacement pairs
 * @param   string $escape Convert special characters to HTML entities. Either "html" or ""
 * @return  string A localisation string
 * @package L10n
 */

function gTxt($var, $atts = array(), $escape = 'html')
{
    global $event, $plugin, $txp_current_plugin;
    static $txpLang = null;

    if ($txpLang === null) {
        $txpLang = Txp::get('\Textpattern\L10n\Lang');
        $lang = txpinterface == 'admin' ? get_pref('language_ui', gps('lang', LANG)) : LANG;
        $loaded = $txpLang->load($lang, true);
        $evt = isset($event) ? trim($event) : '';

        if (empty($loaded) || !in_array($evt, $loaded)) {
            load_lang($lang, $evt);
        }
    }

    // Hackish
    if (isset($txp_current_plugin) && isset($plugin['textpack'])) {
        $txpLang->loadTextpack($plugin['textpack']);
        unset($plugin['textpack']);
    }

    return $txpLang->txt($var, $atts, $escape);
}

/**
 * Returns given timestamp in a format of 01 Jan 2001 15:19:16.
 *
 * @param   int $timestamp The UNIX timestamp
 * @return  string A formatted date
 * @access  private
 * @see     safe_stftime()
 * @package DateTime
 * @example
 * echo gTime();
 */

function gTime($timestamp = 0)
{
    return safe_strftime('%d&#160;%b&#160;%Y %X', $timestamp);
}

/**
 * Creates a dumpfile from a backtrace and outputs given parameters.
 *
 * @package Debug
 */

function dmp()
{
    static $f = false;

    if (defined('txpdmpfile')) {
        global $prefs;

        if (!$f) {
            $f = fopen($prefs['tempdir'].'/'.txpdmpfile, 'a');
        }

        $stack = get_caller();
        fwrite($f, "\n[".$stack[0].t.safe_strftime('iso8601')."]\n");
    }

    $a = func_get_args();

    if (!$f) {
        echo "<pre dir=\"auto\">".n;
    }

    foreach ($a as $thing) {
        $out = is_scalar($thing) ? strval($thing) : var_export($thing, true);

        if ($f) {
            fwrite($f, $out.n);
        } else {
            echo txpspecialchars($out).n;
        }
    }

    if (!$f) {
        echo "</pre>".n;
    }
}

/**
 * Gets the given language's strings from the database.
 *
 * Fetches the given language from the database and returns the strings
 * as an array.
 *
 * If no $events is specified, only appropriate strings for the current context
 * are returned. If 'txpinterface' constant equals 'admin' all strings are
 * returned. Otherwise, only strings from events 'common' and 'public'.
 *
 * If $events is FALSE, returns all strings.
 *
 * @param   string            $lang   The language code
 * @param   array|string|bool $events An array of loaded events
 * @return  array
 * @package L10n
 * @example
 * print_r(
 *     load_lang('en-gb', false)
 * );
 */

function load_lang($lang, $events = null)
{
    global $production_status, $event, $textarray;

    isset($textarray) or $textarray = array();
    $textarray = array_merge($textarray, Txp::get('\Textpattern\L10n\Lang')->load($lang, $events));

    if (($production_status !== 'live' || $event === 'diag')
        && @$debug = parse_ini_file(txpath.DS.'mode.ini')
    ) {
        $textarray += (array)$debug;
        Txp::get('\Textpattern\L10n\Lang')->setPack($textarray);
    }

    return $textarray;
}

/**
 * Gets a list of user groups.
 *
 * @return  array
 * @package User
 * @example
 * print_r(
 *     get_groups()
 * );
 */

function get_groups()
{
    global $txp_groups;

    return doArray($txp_groups, 'gTxt');
}

/**
 * Checks if a user has privileges to the given resource.
 *
 * @param   string $res  The resource
 * @param   mixed  $user The user. If no user name is supplied, assume the current logged in user
 * @return  bool
 * @package User
 * @example
 * add_privs('my_privilege_resource', '1,2,3');
 * if (has_privs('my_privilege_resource', 'username'))
 * {
 *     echo "'username' has privileges to 'my_privilege_resource'.";
 * }
 */

function has_privs($res = null, $user = '')
{
    global $txp_user, $txp_permissions;
    static $privs;

    if (is_array($user)) {
        $level = isset($user['privs']) ? $user['privs'] : null;
        $user = isset($user['name']) ? $user['name'] : '';
    }

    $user = (string) $user;

    if ($user === '') {
        $user = (string) $txp_user;
    }

    if ($user !== '') {
        if (!isset($privs[$user])) {
            $privs[$user] = isset($level) ?
                $level :
                safe_field("privs", 'txp_users', "name = '".doSlash($user)."'");
        }

        if (!isset($res)) {
            return $privs[$user];
        } elseif (isset($txp_permissions[$res]) && $privs[$user] && $txp_permissions[$res]) {
            return in_list($privs[$user], $txp_permissions[$res]);
        }
    }

    return false;
}

/**
 * Adds dynamic privileges.
 *
 * @param   array $pluggable The array, see global $txp_options
 * @since   4.7.2
 * @package User
 */

function plug_privs($pluggable = null, $user = null)
{
    global $txp_options;

    isset($pluggable) or $pluggable = $txp_options;
    $level = isset($user['privs']) ? $user['privs'] : has_privs();

    foreach ((array)$pluggable as $pref => $pane) {
        if (is_array($pane)) {
            if (isset($pane[0])) {
                if (!in_list($level, $pane[0])) {
                    break;
                }

                unset($pane[0]);
            }
        } else {
            $pane = array('prefs.'.$pref => $pane);
        }

        if (get_pref($pref)) {
            array_walk($pane, function (&$item) use ($level) {
                if ($item === true) {
                    $item = $level;
                }
            });
            add_privs($pane);
        } else {
            add_privs(array_fill_keys(array_keys($pane), null));
        }
    }
}

/**
 * Grants privileges to user-groups.
 *
 * Will not let you override existing privs.
 *
 * @param   mixed  $res  The resource
 * @param   string $perm List of user-groups, e.g. '1,2,3'
 * @package User
 * @example
 * add_privs('my_admin_side_panel_event', '1,2,3,4,5');
 */

function add_privs($res, $perm = '1')
{
    global $txp_permissions;

    if (!is_array($res)) {
        $res = array($res => $perm);
    }

    foreach ($res as $priv => $group) {
        if ($group === null) {
            $txp_permissions[$priv] = null;
        } else {
            $group .= (empty($txp_permissions[$priv]) ? '' : ','.$txp_permissions[$priv]);
            $group = join(',', do_list_unique($group));
            $txp_permissions[$priv] = $group;
        }
    }
}

/**
 * Require privileges from a user to the given resource.
 *
 * Terminates the script if user doesn't have required privileges.
 *
 * @param   string|null $res  The resource, or NULL
 * @param   string      $user The user. If no user name is supplied, assume the current logged in user
 * @package User
 * @example
 * require_privs('article.edit');
 */

function require_privs($res = null, $user = '')
{
    if ($res === null || !has_privs($res, $user)) {
        pagetop(gTxt('restricted_area'));
        echo graf(gTxt('restricted_area'), array('class' => 'restricted-area'));
        end_page();
        exit;
    }
}

/**
 * Gets a list of users having access to a resource.
 *
 * @param   string $res The resource, e.g. 'article.edit.published'
 * @return  array  A list of usernames
 * @since   4.5.0
 * @package User
 */

function the_privileged($res, $real = false)
{
    global $txp_permissions;

    $out = array();

    if (isset($txp_permissions[$res])) {
        foreach (safe_rows("name, RealName", 'txp_users', "FIND_IN_SET(privs, '".$txp_permissions[$res]."') ORDER BY ".($real ? "RealName" : "name")." ASC") as $user) {
            extract($user);
            $out[$name] = $real ? $RealName : $name;
        }
    }

    return $out;
}

/**
 * Lists image types that can be safely uploaded.
 *
 * Returns different results based on the logged in user's privileges.
 *
 * @param   int         $type If set, validates the given value
 * @return  mixed
 * @package Image
 * @since   4.6.0
 * @example
 * list($width, $height, $extension) = getimagesize('image');
 * if ($type = get_safe_image_types($extension))
 * {
 *     echo "Valid image of {$type}.";
 * }
 */

function get_safe_image_types($type = null)
{
    $extensions = array(IMAGETYPE_GIF => '.gif', 0 => '.jpeg', IMAGETYPE_JPEG => '.jpg', IMAGETYPE_PNG => '.png') +
        (defined('IMAGETYPE_WEBP') ? array(IMAGETYPE_WEBP => '.webp') : array());

    if (has_privs('image.create.trusted')) {
        $extensions += array(IMAGETYPE_SWF => '.swf', IMAGETYPE_SWC => '.swf');
    }

    callback_event_ref('txp.image', 'types', 0, $extensions);

    if (isset($type)) {
        return !empty($extensions[$type]) ? $extensions[$type] : false;
    }

    return $extensions;
}


/**
 * Gets the dimensions of an image for a HTML &lt;img&gt; tag.
 *
 * @param   string      $name The filename
 * @return  string|bool height="100" width="40", or FALSE on failure
 * @package Image
 * @example
 * if ($size = sizeImage('/path/to/image.png'))
 * {
 *     echo "&lt;img src='image.png' {$size} /&gt;";
 * }
 */

function sizeImage($name)
{
    $size = @getimagesize($name);

    return is_array($size) ? $size[3] : false;
}

/**
 * Gets an image as an array.
 *
 * @param   int $id image ID
 * @param   string $name image name
 * @return  array|bool An image data array, or FALSE on failure
 * @package Image
 * @example
 * if ($image = imageFetchInfo($id))
 * {
 *     print_r($image);
 * }
 */

function imageFetchInfo($id = "", $name = "")
{
    global $thisimage, $p;
    static $cache = array();

    if ($id) {
        if (isset($cache['i'][$id])) {
            return $cache['i'][$id];
        } else {
            $where = 'id = '.intval($id).' LIMIT 1';
        }
    } elseif ($name) {
        if (isset($cache['n'][$name])) {
            return $cache['n'][$name];
        } else {
            $where = "name = '".doSlash($name)."' LIMIT 1";
        }
    } elseif ($thisimage) {
        $id = (int) $thisimage['id'];
        return $cache['i'][$id] = $thisimage;
    } elseif ($p) {
        if (isset($cache['i'][$p])) {
            return $cache['i'][$p];
        } else {
            $where = 'id = '.intval($p).' LIMIT 1';
        }
    } else {
        assert_image();
        return false;
    }

    $rs = safe_row("*", 'txp_image', $where);

    if ($rs) {
        $id = (int) $rs['id'];
        return $cache['i'][$id] = image_format_info($rs);
    } else {
        trigger_error(gTxt('unknown_image'));
    }

    return false;
}

/**
 * Formats image info.
 *
 * Takes an image data array generated by imageFetchInfo() and formats the contents.
 *
 * @param   array $image The image
 * @return  array
 * @see     imageFetchInfo()
 * @access  private
 * @package Image
 */

function image_format_info($image)
{
    static $mimetypes;

    if (($unix_ts = @strtotime($image['date'])) > 0) {
        $image['date'] = $unix_ts;
    }

    if (!isset($mimetypes)) {
        $mimetypes = get_safe_image_types();
    }

    $image['mime'] = ($mime = array_search($image['ext'], $mimetypes)) !== false ? image_type_to_mime_type($mime) : '';

    return $image;
}

/**
 * Formats link info.
 *
 * @param   array $link The link to format
 * @return  array Formatted link data
 * @access  private
 * @package Link
 */

function link_format_info($link)
{
    if (($unix_ts = @strtotime($link['date'])) > 0) {
        $link['date'] = $unix_ts;
    }

    return $link;
}

/**
 * Gets a HTTP GET or POST parameter.
 *
 * Internally strips CRLF from GET parameters and removes NULL bytes.
 *
 * @param   string $thing The parameter to get
 * @return  string|array The value of $thing, or an empty string
 * @package Network
 * @example
 * if (gps('sky') == 'blue' && gps('roses') == 'red')
 * {
 *     echo 'Roses are red, sky is blue.';
 * }
 */

function gps($thing, $default = '')
{
    global $pretext;

    if (isset($_GET[$thing])) {
        $out = $_GET[$thing];
        $out = doArray($out, 'deCRLF');
    } elseif (isset($_POST[$thing])) {
        $out = $_POST[$thing];
    } elseif (is_numeric($thing) && isset($pretext[abs($thing)])) {
        $thing >= 0 or $thing += $pretext[0] + 1;
        $out = $pretext[$thing];
    } else {
        return $default;
    }

    $out = doArray($out, 'deNull');

    return $out;
}

/**
 * Gets an array of HTTP GET or POST parameters.
 *
 * @param   array $array The parameters to extract
 * @return  array
 * @package Network
 * @example
 * extract(gpsa(array('sky', 'roses'));
 * if ($sky == 'blue' && $roses == 'red')
 * {
 *     echo 'Roses are red, sky is blue.';
 * }
 */

function gpsa($array)
{
    if (is_array($array)) {
        $out = array();

        foreach ($array as $a) {
            $out[$a] = gps($a);
        }

        return $out;
    }

    return false;
}

/**
 * Gets a HTTP POST parameter.
 *
 * Internally removes NULL bytes.
 *
 * @param   string $thing The parameter to get
 * @return  string|array The value of $thing, or an empty string
 * @package Network
 * @example
 * if (ps('sky') == 'blue' && ps('roses') == 'red')
 * {
 *     echo 'Roses are red, sky is blue.';
 * }
 */

function ps($thing)
{
    $out = '';

    if (isset($_POST[$thing])) {
        $out = $_POST[$thing];
    }

    $out = doArray($out, 'deNull');

    return $out;
}

/**
 * Gets an array of HTTP POST parameters.
 *
 * @param   array $array The parameters to extract
 * @return  array
 * @package Network
 * @example
 * extract(psa(array('sky', 'roses'));
 * if ($sky == 'blue' && $roses == 'red')
 * {
 *     echo 'Roses are red, sky is blue.';
 * }
 */

function psa($array)
{
    foreach ($array as $a) {
        $out[$a] = ps($a);
    }

    return $out;
}

/**
 * Gets an array of HTTP POST parameters and strips HTML and PHP tags
 * from values.
 *
 * @param   array $array The parameters to extract
 * @return  array
 * @package Network
 */

function psas($array)
{
    foreach ($array as $a) {
        $out[$a] = doStripTags(ps($a));
    }

    return $out;
}

/**
 * Gets all received HTTP POST parameters.
 *
 * @return  array
 * @package Network
 */

function stripPost()
{
    if (isset($_POST)) {
        return $_POST;
    }

    return '';
}

/**
 * Gets a variable from $_SERVER global array.
 *
 * @param   mixed $thing The variable
 * @return  mixed The variable, or an empty string on error
 * @package System
 * @example
 * echo serverSet('HTTP_USER_AGENT');
 */

function serverSet($thing)
{
    return (isset($_SERVER[$thing])) ? $_SERVER[$thing] : '';
}

/**
 * Gets the client's IP address.
 *
 * Supports proxies and uses 'X_FORWARDED_FOR' HTTP header if deemed necessary.
 *
 * @return  string
 * @package Network
 * @example
 * if ($ip = remote_addr())
 * {
 *     echo "Your IP address is: {$ip}.";
 * }
 */

function remote_addr()
{
    $ip = serverSet('REMOTE_ADDR');

    if (($ip == '127.0.0.1' || $ip == '::1' || $ip == '::ffff:127.0.0.1' || $ip == serverSet('SERVER_ADDR')) && serverSet('HTTP_X_FORWARDED_FOR')) {
        $ips = explode(', ', serverSet('HTTP_X_FORWARDED_FOR'));
        $ip = $ips[0];
    }

    return $ip;
}

/**
 * Gets a variable from HTTP POST or a prefixed cookie.
 *
 * Fetches either a HTTP cookie of the given name prefixed with
 * 'txp_', or a HTTP POST parameter without a prefix.
 *
 * @param   string $thing The variable
 * @return  array|string The variable or an empty string
 * @package Network
 * @example
 * if ($cs = psc('myVariable'))
 * {
 *     echo "'txp_myVariable' cookie or 'myVariable' POST parameter contained: '{$cs}'.";
 * }
 */

function pcs($thing)
{
    if (isset($_COOKIE["txp_".$thing])) {
        return $_COOKIE["txp_".$thing];
    } elseif (isset($_POST[$thing])) {
        return $_POST[$thing];
    }

    return '';
}

/**
 * Gets a HTTP cookie.
 *
 * @param   string $thing The cookie
 * @return  string The cookie or an empty string
 * @package Network
 * @example
 * if ($cs = cs('myVariable'))
 * {
 *     echo "'myVariable' cookie contained: '{$cs}'.";
 * }
 */

function cs($thing)
{
    if (isset($_COOKIE[$thing])) {
        return $_COOKIE[$thing];
    }

    return '';
}

/**
 * Sets a HTTP cookie (polyfill).
 *
 * @param   string $name The cookie name
 * @param   string $value The cookie value
 * @param   array  $options The cookie options
 * @package Network
 */

function set_cookie($name, $value = '', $options = array())
{
    $options += array (
        'expires' => time() - 3600,
        'path' => '',
        'domain' => '',
        'secure' => strtolower(PROTOCOL) == 'https://',
        'httponly' => false,
        'samesite' => 'Lax' // None || Lax  || Strict
    );

    if (version_compare(phpversion(), '7.3.0') >= 0) {
        return setcookie($name, $value, $options);
    }

    extract($options);

    return setcookie($name, $value, $expires, $path.'; samesite='.$samesite, $domain, $secure, $httponly);
}

/**
 * Converts a boolean to a localised "Yes" or "No" string.
 *
 * @param   bool $status The boolean. Ignores type and as such can also take a string or an integer
 * @return  string No if FALSE, Yes otherwise
 * @package L10n
 * @example
 * echo yes_no(3 * 3 === 2);
 */

function yes_no($status)
{
    return ($status) ? gTxt('yes') : gTxt('no');
}

/**
 * Gets UNIX timestamp with microseconds.
 *
 * @return  float
 * @package DateTime
 * @example
 * echo getmicrotime();
 */

function getmicrotime()
{
    list($usec, $sec) = explode(" ", microtime());

    return ((float) $usec + (float) $sec);
}

/**
 * Loads the given plugin or checks if it was loaded.
 *
 * @param  string $name  The plugin
 * @param  bool   $force If TRUE loads the plugin even if it's disabled
 * @return bool TRUE if the plugin is loaded
 * @example
 * if (load_plugin('abc_plugin'))
 * {
 *     echo "'abc_plugin' is active.";
 * }
 */

function load_plugin($name, $force = false)
{
    global $plugin, $plugins, $plugins_ver, $prefs, $txp_current_plugin, $textarray;

    if (is_array($plugins) && in_array($name, $plugins)) {
        return true;
    }

    if (!empty($prefs['plugin_cache_dir'])) {
        $dir = rtrim($prefs['plugin_cache_dir'], '/').'/';

        // In case it's a relative path.
        if (!is_dir($dir)) {
            $dir = rtrim(realpath(txpath.'/'.$dir), '/').'/';
        }

        if (is_file($dir.$name.'.php')) {
            $plugins[] = $name;
            $old_plugin = isset($plugin) ? $plugin : null;
            set_error_handler("pluginErrorHandler");

            if (isset($txp_current_plugin)) {
                $txp_parent_plugin = $txp_current_plugin;
            }

            $txp_current_plugin = $name;
            include $dir.$name.'.php';
            $txp_current_plugin = isset($txp_parent_plugin) ? $txp_parent_plugin : null;
            $plugins_ver[$name] = isset($plugin['version']) ? $plugin['version'] : 0;

            if (isset($plugin['textpack'])) {
                Txp::get('\Textpattern\L10n\Lang')->loadTextpack($plugin['textpack']);
            }

            restore_error_handler();
            $plugin = $old_plugin;

            return true;
        }
    }

    $version = safe_field("version", 'txp_plugin', ($force ? '' : "status = 1 AND ")."name = '".doSlash($name)."'");

    if ($version !== false) {
        $plugins[] = $name;
        $plugins_ver[$name] = $version;
        set_error_handler("pluginErrorHandler");

        if (isset($txp_current_plugin)) {
            $txp_parent_plugin = $txp_current_plugin;
        }

        $txp_current_plugin = $name;
        $dir = sanitizeForFile($name);
        $filename = PLUGINPATH.DS.$dir.DS.$dir.'.php';

        if (!is_file($filename)) {
            $code = safe_field("code", 'txp_plugin', "name = '".doSlash($name)."'");
            \Txp::get('\Textpattern\Plugin\Plugin')->updateFile($txp_current_plugin, $code);
        }

        $ok = is_readable($filename) ? include_once($filename) : false;
        $txp_current_plugin = isset($txp_parent_plugin) ? $txp_parent_plugin : null;
        restore_error_handler();

        return $ok;
    }

    return false;
}

/**
 * Loads a plugin.
 *
 * Identical to load_plugin() except upon failure it issues an E_USER_ERROR.
 *
 * @param  string $name The plugin
 * @return bool
 * @see    load_plugin()
 */

function require_plugin($name)
{
    if (!load_plugin($name)) {
        trigger_error(gTxt('plugin_include_error', array('{name}' => $name)), E_USER_ERROR);

        return false;
    }

    return true;
}

/**
 * Loads a plugin.
 *
 * Identical to load_plugin() except upon failure it issues an E_USER_WARNING.
 *
 * @param  string $name The plugin
 * @return bool
 * @see    load_plugin()
 */

function include_plugin($name)
{
    if (!load_plugin($name)) {
        trigger_error(gTxt('plugin_include_error', array('{name}' => $name)), E_USER_WARNING);

        return false;
    }

    return true;
}

/**
 * Error handler for plugins.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

function pluginErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $production_status, $txp_current_plugin, $plugins_ver;

    $error = array();

    if ($production_status == 'testing') {
        $error = array(
            E_WARNING           => 'Warning',
            E_RECOVERABLE_ERROR => 'Catchable fatal error',
            E_USER_ERROR        => 'User_Error',
            E_USER_WARNING      => 'User_Warning',
        );
    } elseif ($production_status == 'debug') {
        $error = array(
            E_WARNING           => 'Warning',
            E_NOTICE            => 'Notice',
            E_RECOVERABLE_ERROR => 'Catchable fatal error',
            E_USER_ERROR        => 'User_Error',
            E_USER_WARNING      => 'User_Warning',
            E_USER_NOTICE       => 'User_Notice',
        );

        if (!isset($error[$errno])) {
            $error[$errno] = $errno;
        }
    }

    if (!isset($error[$errno]) || !error_reporting()) {
        return;
    }

    $version = empty($plugins_ver[$txp_current_plugin]) ? '' : ' ('.$plugins_ver[$txp_current_plugin].')';

    printf(
        '<pre dir="auto">'.gTxt('plugin_load_error').' <b>%s%s</b> -> <b>%s: %s on line %s</b></pre>',
        $txp_current_plugin,
        $version,
        $error[$errno],
        $errstr,
        $errline
    );

    if ($production_status == 'debug') {
        print "\n<pre class=\"backtrace\" dir=\"ltr\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";
    }
}

/**
 * Error handler for page templates.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

function tagErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $production_status, $txp_current_tag, $txp_current_form, $pretext, $trace;

    $error = array();

    if ($production_status == 'testing') {
        $error = array(
            E_WARNING           => 'Warning',
            E_RECOVERABLE_ERROR => 'Textpattern Catchable fatal error',
            E_USER_ERROR        => 'Textpattern Error',
            E_USER_WARNING      => 'Textpattern Warning',
        );
    } elseif ($production_status == 'debug') {
        $error = array(
            E_WARNING           => 'Warning',
            E_NOTICE            => 'Notice',
            E_RECOVERABLE_ERROR => 'Textpattern Catchable fatal error',
            E_USER_ERROR        => 'Textpattern Error',
            E_USER_WARNING      => 'Textpattern Warning',
            E_USER_NOTICE       => 'Textpattern Notice',
        );

        if (!isset($error[$errno])) {
            $error[$errno] = $errno;
        }
    }

    if (!isset($error[$errno]) || !error_reporting()) {
        return;
    }

    if (empty($pretext['page'])) {
        $page = gTxt('none');
    } else {
        $page = $pretext['page'];
    }

    if (!isset($txp_current_form)) {
        $txp_current_form = gTxt('none');
    }

    $locus = gTxt('while_parsing_page_form', array(
        '{page}' => $page,
        '{form}' => $txp_current_form,
    ));

    printf(
        "<pre dir=\"auto\">".gTxt('tag_error').' <b>%s</b> -> <b> %s: %s %s</b></pre>',
        txpspecialchars($txp_current_tag),
        $error[$errno],
        $errstr,
        $locus
    );

    if ($production_status == 'debug') {
        print "\n<pre class=\"backtrace\" dir=\"ltr\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";

        $trace->log(gTxt('tag_error').' '.$txp_current_tag.' -> '.$error[$errno].': '.$errstr.' '.$locus);
    }
}

/**
 * Error handler for XML feeds.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

function feedErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $production_status;

    if ($production_status != 'debug') {
        return;
    }

    return tagErrorHandler($errno, $errstr, $errfile, $errline);
}

/**
 * Error handler for public-side.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

function publicErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $production_status;

    $error = array();

    if ($production_status == 'testing') {
        $error = array(
            E_WARNING      => 'Warning',
            E_USER_ERROR   => 'Textpattern Error',
            E_USER_WARNING => 'Textpattern Warning',
        );
    } elseif ($production_status == 'debug') {
        $error = array(
            E_WARNING      => 'Warning',
            E_NOTICE       => 'Notice',
            E_USER_ERROR   => 'Textpattern Error',
            E_USER_WARNING => 'Textpattern Warning',
            E_USER_NOTICE  => 'Textpattern Notice',
        );

        if (!isset($error[$errno])) {
            $error[$errno] = $errno;
        }
    }

    if (!isset($error[$errno]) || !error_reporting()) {
        return;
    }

    printf(
        "<pre dir=\"auto\">".gTxt('general_error').' <b>%s: %s on line %s</b></pre>',
        $error[$errno],
        $errstr,
        $errline
    );

    if ($production_status == 'debug') {
        print "\n<pre class=\"backtrace\" dir=\"ltr\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";
    }
}

/**
 * Loads plugins.
 *
 * @param bool $type If TRUE loads admin-side plugins, otherwise public
 */

function load_plugins($type = false, $pre = null)
{
    global $prefs, $plugins, $plugins_ver, $app_mode, $trace;
    static $rs = null;

    $trace->start('[Loading plugins]');
    is_array($plugins) or $plugins = array();

    if (!isset($rs)) {
        if (!empty($prefs['plugin_cache_dir'])) {
            $dir = rtrim($prefs['plugin_cache_dir'], DS).DS;

            // In case it's a relative path.
            if (!is_dir($dir)) {
                $dir = rtrim(realpath(txpath.DS.$dir), DS).DS;
            }

            $files = glob($dir.'*.php');

            if ($files) {
                natsort($files);

                foreach ($files as $f) {
                    $trace->start("[Loading plugin from cache dir: '$f']");
                    load_plugin(basename($f, '.php'));
                    $trace->stop();
                }
            }
        }

        $admin = ($app_mode == 'async' ? '4,5' : '1,3,4,5');
        $where = 'status = 1 AND type IN ('.($type ? $admin : '0,1,5').')'.
            ($plugins ? ' AND name NOT IN ('.join(',', quote_list($plugins)).')' : '');

        $rs = safe_rows("name, version, load_order", 'txp_plugin', $where." ORDER BY load_order ASC, name ASC");
    }

    if ($rs) {
        $old_error_handler = set_error_handler("pluginErrorHandler");
        $pre = intval($pre);

        $writable = is_dir(PLUGINPATH) && is_writable(PLUGINPATH);

        foreach ($rs as $a) {
            if (!isset($plugins_ver[$a['name']]) && (!$pre || $a['load_order'] < $pre)) {
                $plugins[] = $a['name'];
                $plugins_ver[$a['name']] = $a['version'];
                $GLOBALS['txp_current_plugin'] = $a['name'];
                $trace->start("[Loading plugin: '{$a['name']}' version '{$a['version']}']");

                $dir = $a['name'];
                $filename = PLUGINPATH.DS.$dir.DS.$dir.'.php';

                if ($writable && !is_file($filename)) {
                    $code = safe_field('code', 'txp_plugin', "name='".doSlash($a['name'])."'");
                    \Txp::get('\Textpattern\Plugin\Plugin')->updateFile($a['name'], $code);
                }

                $eval_ok = is_readable($filename) ? include($filename) : false;
                $trace->stop();

                if ($eval_ok === false) {
                    trigger_error(gTxt('plugin_include_error', array('{name}' => $a['name'])), E_USER_WARNING);
                }

                unset($GLOBALS['txp_current_plugin']);
            }
        }

        restore_error_handler();
    }

    $trace->stop();
}

/**
 * Attaches a handler to a callback event.
 *
 * @param   callback $func  The callback function
 * @param   string   $event The callback event
 * @param   string   $step  The callback step
 * @param   bool     $pre   Before or after. Works only with selected callback events
 * @package Callback
 * @example
 * register_callback('my_callback_function', 'article.updated');
 * function my_callback_function($event)
 * {
 *     return "'$event' fired.";
 * }
 */

function register_callback($func, $event, $step = '', $pre = 0)
{
    global $plugin_callback;

    $pre or $pre = 0;

    isset($plugin_callback[$event]) or $plugin_callback[$event] = array();
    isset($plugin_callback[$event][$pre]) or $plugin_callback[$event][$pre] = array();
    isset($plugin_callback[$event][$pre][$step]) or $plugin_callback[$event][$pre][$step] =
        isset($plugin_callback[$event][$pre]['']) ? $plugin_callback[$event][$pre][''] : array();

    if ($step === '') {
        foreach($plugin_callback[$event][$pre] as $key => $val) {
            $plugin_callback[$event][$pre][$key][] = $func;
        }
    } else {
        $plugin_callback[$event][$pre][$step][] = $func;
    }
}

/**
 * Call an event's callback.
 *
 * Executes all callback handlers attached to the matched event and step.
 *
 * When called, any event handlers attached with register_callback() to the
 * matching event, step and pre will be called. The handlers, callback
 * functions, will be executed in the same order they were registered.
 *
 * Any extra arguments will be passed to the callback handlers in the same
 * argument position. This allows passing any type of data to the attached
 * handlers. Callback handlers will also receive the event and the step.
 *
 * Returns a combined value of all values returned by the callback handlers.
 *
 * @param   string         $event The callback event
 * @param   string         $step  Additional callback step
 * @param   bool|int|array $pre   Allows two callbacks, a prepending and an appending, with same event and step. Array allows return values chaining
 * @return  mixed  The value returned by the attached callback functions, or an empty string
 * @package Callback
 * @see     register_callback()
 * @example
 * register_callback('my_callback_function', 'my_custom_event');
 * function my_callback_function($event, $step, $extra)
 * {
 *     return "Passed '$extra' on '$event'.";
 * }
 * echo callback_event('my_custom_event', '', 0, 'myExtraValue');
 */

function callback_event($event, $step = '', $pre = 0)
{
    global $production_status, $trace;

    list($pre, $renew) = (array)$pre + array(0, null);
    $callbacks = callback_handlers($event, $step, $pre, false);

    if (empty($callbacks)) {
        return '';
    }

    $trace->start("[Callback_event: '$event', step='$step', pre='$pre']");

    // Any payload parameters?
    $argv = func_get_args();
    $argv = (count($argv) > 3) ? array_slice($argv, 3) : array();

    foreach ($callbacks as $c) {
        if (is_callable($c)) {
            if ($production_status !== 'live') {
                $trace->start("\t[Call function: '".Txp::get('\Textpattern\Type\TypeCallable', $c)->toString()."'".
                    (empty($argv) ? '' : ", argv='".serialize($argv)."'")."]");
            }

            $return_value = call_user_func_array($c, array_merge(array(
                $event,
                $step
            ), $argv));

            if (isset($renew)) {
                $argv[$renew] = $return_value;
            }

            if (isset($out) && !isset($renew)) {
                if (is_array($return_value) && is_array($out)) {
                    $out = array_merge($out, $return_value);
                } elseif (is_bool($return_value) && is_bool($out)) {
                    $out = $return_value && $out;
                } else {
                    $out .= $return_value;
                }
            } else {
                $out = $return_value;
            }

            if ($production_status !== 'live') {
                $trace->stop();
            }
        } elseif ($production_status === 'debug') {
            trigger_error(gTxt('unknown_callback_function', array('{function}' => Txp::get('\Textpattern\Type\TypeCallable', $c)->toString())), E_USER_WARNING);
        }
    }

    $trace->stop();

    if (isset($out)) {
        return $out;
    }

    return '';
}

/**
 * Call an event's callback with two optional byref parameters.
 *
 * @param   string $event   The callback event
 * @param   string $step    Optional callback step
 * @param   bool   $pre     Allows two callbacks, a prepending and an appending, with same event and step
 * @param   mixed  $data    Optional arguments for event handlers
 * @param   mixed  $options Optional arguments for event handlers
 * @return  array Collection of return values from event handlers
 * @since   4.5.0
 * @package Callback
 */

function callback_event_ref($event, $step = '', $pre = 0, &$data = null, &$options = null)
{
    global $production_status;

    $callbacks = callback_handlers($event, $step, $pre, false);

    if (empty($callbacks)) {
        return array();
    }

    $return_value = array();

    foreach ($callbacks as $c) {
        if (is_callable($c)) {
            // Cannot call event handler via call_user_func() as this would
            // dereference all arguments. Side effect: callback handler
            // *must* be ordinary function, *must not* be class method in
            // PHP <5.4. See https://bugs.php.net/bug.php?id=47160.
            $return_value[] = $c($event, $step, $data, $options);
        } elseif ($production_status == 'debug') {
            trigger_error(gTxt('unknown_callback_function', array('{function}' => Txp::get('\Textpattern\Type\TypeCallable', $c)->toString())), E_USER_WARNING);
        }
    }

    return $return_value;
}

/**
 * Checks if a callback event has active handlers.
 *
 * @param   string $event The callback event
 * @param   string $step  The callback step
 * @param   bool   $pre   The position
 * @return  bool TRUE if the event is active, FALSE otherwise
 * @since   4.6.0
 * @package Callback
 * @example
 * if (has_handler('article_saved'))
 * {
 *     echo "There are active handlers for 'article_saved' event.";
 * }
 */

function has_handler($event, $step = '', $pre = 0)
{
    return (bool) callback_handlers($event, $step, $pre, false);
}

/**
 * Lists handlers attached to an event.
 *
 * @param   string $event The callback event
 * @param   string $step  The callback step
 * @param   bool   $pre   The position
 * @param   bool   $as_string Return callables in string representation
 * @return  array|bool An array of handlers, or FALSE
 * @since   4.6.0
 * @package Callback
 * @example
 * if ($handlers = callback_handlers('article_saved'))
 * {
 *     print_r($handlers);
 * }
 */

function callback_handlers($event, $step = '', $pre = 0, $as_string = true)
{
    global $plugin_callback;

    $pre or $pre = 0;
    $step or $step = 0;

    $callbacks = isset($plugin_callback[$event][$pre][$step]) ? $plugin_callback[$event][$pre][$step] :
        (isset($plugin_callback[$event][$pre]['']) ? $plugin_callback[$event][$pre][''] : array());

    if (!$as_string) {
        return $callbacks;
    }

    $out = array();

    foreach ($callbacks as $c) {
        $out[] = Txp::get('\Textpattern\Type\TypeCallable', $c)->toString();
    }

    return $out;
}

/**
 * Merge the second array into the first array.
 *
 * @param   array $pairs The first array
 * @param   array $atts  The second array
 * @param   bool  $warn  If TRUE triggers errors if second array contains values that are not in the first
 * @return  array The two arrays merged
 * @package TagParser
 */

function lAtts($pairs, $atts, $warn = true)
{
    global $pretext, $production_status, $txp_atts;
    static $globals = null, $global_atts, $partial;

    if ($globals === null) {
        $global_atts = Txp::get('\Textpattern\Tag\Registry')->getRegistered(true);
        $globals = array_filter($global_atts);
    }

    if (isset($atts['yield']) && !isset($pairs['yield'])) {
        isset($partial) or $partial = Txp::get('\Textpattern\Tag\Registry')->getTag('yield');

        foreach (parse_qs($atts['yield']) as $name => $alias) {
            $value = call_user_func($partial, array('name' => $alias === false ? $name : $alias));

            if (isset($value)) {
                $atts[$name] = $value;
            }
        }

        unset($atts['yield']);
    }

    if (empty($pretext['_txp_atts'])) {
        foreach ($atts as $name => $value) {
            if (array_key_exists($name, $pairs)) {
                if ($pairs[$name] !== null) {
                    unset($txp_atts[$name]);
                }

                $pairs[$name] = $value;
            } elseif ($warn && $production_status !== 'live' && !array_key_exists($name, $global_atts)) {
                trigger_error(gTxt('unknown_attribute', array('{att}' => $name)));
            }
        }
    } else { // don't import unset globals
        foreach ($atts as $name => $value) {
            if (array_key_exists($name, $pairs) && (!isset($globals[$name]) || isset($txp_atts[$name]))) {
                $pairs[$name] = $value;
                unset($txp_atts[$name]);
            }
        }
    }

    return $pairs ? $pairs : false;
}

/**
 * Sanitises a string for use in an article's URL title.
 *
 * @param   string $text  The title or an URL
 * @param   bool   $force Force sanitisation
 * @return  string|null
 * @package URL
 */

function stripSpace($text, $force = false)
{
    if ($force || get_pref('attach_titles_to_permalinks')) {
        $text = trim(sanitizeForUrl($text, '/[^\p{L}\p{N}\-_\s\/\\\\\x{1F300}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{27BF}]/u'), '-');

        if (get_pref('permlink_format')) {
            return (function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text));
        } else {
            return str_replace('-', '', $text);
        }
    }
}

/**
 * Sanitises a string for use in a URL.
 *
 * Be aware that you still have to urlencode the string when appropriate.
 * This function just makes the string look prettier and excludes some
 * unwanted characters, but leaves UTF-8 letters and digits intact.
 *
 * @param  string $text  The string
 * @param  string $strip The regex of the characters to strip
 * @return string
 * @package URL
 */

function sanitizeForUrl($text, $strip = '/[^\p{L}\p{N}\-_\s\/\\\\]/u')
{
    $out = callback_event('sanitize_for_url', '', 0, $text);

    if ($out !== '') {
        return $out;
    }

    // Remove named entities and tags.
    $text = preg_replace("/(^|&\S+;)|(<[^>]*>)/U", "", dumbDown($text));
    // Remove all characters except letter, number, dash, space and backslash
    $text = preg_replace($strip, '', $text);
    // Collapse spaces, minuses, (back-)slashes.
    $text = trim(preg_replace('/[\s\-\/\\\\]+/', '-', $text), '-');

    return $text;
}

/**
 * Sanitises a string for use in a filename.
 *
 * @param   string $text The string
 * @return  string
 * @package File
 */

function sanitizeForFile($text)
{
    $out = callback_event('sanitize_for_file', '', 0, $text);

    if ($out !== '') {
        return $out;
    }

    // Remove control characters and " * \ : < > ? / |
    $text = preg_replace('/[\x00-\x1f\x22\x2a\x2f\x3a\x3c\x3e\x3f\x5c\x7c\x7f]+/', '', $text);
    // Remove duplicate dots and any leading or trailing dots/spaces.
    $text = preg_replace('/[.]{2,}/', '.', trim($text, '. '));

    return $text;
}

/**
 * Sanitises a string for use in a page template's name.
 *
 * @param   string $text The string
 * @return  string
 * @package Filter
 * @access  private
 */

function sanitizeForPage($text)
{
    $out = callback_event('sanitize_for_page', '', 0, $text);

    if ($out !== '') {
        return $out;
    }

    return trim(preg_replace('/[<>&"\']/', '', $text));
}

/**
 * Sanitizes a string for use in a ORDER BY clause.
 *
 * @param   string $text The string
 * @return  string
 * @package Filter
 * @access  private
 */

function sanitizeForSort($text)
{
    return trim(strtr($text, array('#' => ' ', '--' => ' ')));
}

/**
 * Transliterates a string to ASCII.
 *
 * Used to generate RFC 3986 compliant and pretty ASCII-only URLs.
 *
 * @param   string $str  The string to convert
 * @param   string $lang The language which translation table is used
 * @see     sanitizeForUrl()
 * @package L10n
 */

function dumbDown($str, $lang = null)
{
    static $array;

    if ($lang === null) {
        $lang = get_pref('language_ui', LANG);
    }

    if (empty($array[$lang])) {
        $array[$lang] = array( // Nasty, huh?
            '&#192;' => 'A', '&Agrave;' => 'A', '&#193;' => 'A', '&Aacute;' => 'A', '&#194;' => 'A', '&Acirc;' => 'A',
            '&#195;' => 'A', '&Atilde;' => 'A', '&#196;' => 'Ae', '&Auml;' => 'A', '&#197;' => 'A', '&Aring;' => 'A',
            '&#198;' => 'Ae', '&AElig;' => 'AE',
            '&#256;' => 'A', '&#260;' => 'A', '&#258;' => 'A',
            '&#199;' => 'C', '&Ccedil;' => 'C', '&#262;' => 'C', '&#268;' => 'C', '&#264;' => 'C', '&#266;' => 'C',
            '&#270;' => 'D', '&#272;' => 'D', '&#208;' => 'D', '&ETH;' => 'D',
            '&#200;' => 'E', '&Egrave;' => 'E', '&#201;' => 'E', '&Eacute;' => 'E', '&#202;' => 'E', '&Ecirc;' => 'E', '&#203;' => 'E', '&Euml;' => 'E',
            '&#274;' => 'E', '&#280;' => 'E', '&#282;' => 'E', '&#276;' => 'E', '&#278;' => 'E',
            '&#284;' => 'G', '&#286;' => 'G', '&#288;' => 'G', '&#290;' => 'G',
            '&#292;' => 'H', '&#294;' => 'H',
            '&#204;' => 'I', '&Igrave;' => 'I', '&#205;' => 'I', '&Iacute;' => 'I', '&#206;' => 'I', '&Icirc;' => 'I', '&#207;' => 'I', '&Iuml;' => 'I',
            '&#298;' => 'I', '&#296;' => 'I', '&#300;' => 'I', '&#302;' => 'I', '&#304;' => 'I',
            '&#306;' => 'IJ',
            '&#308;' => 'J',
            '&#310;' => 'K',
            '&#321;' => 'K', '&#317;' => 'K', '&#313;' => 'K', '&#315;' => 'K', '&#319;' => 'K',
            '&#209;' => 'N', '&Ntilde;' => 'N', '&#323;' => 'N', '&#327;' => 'N', '&#325;' => 'N', '&#330;' => 'N',
            '&#210;' => 'O', '&Ograve;' => 'O', '&#211;' => 'O', '&Oacute;' => 'O', '&#212;' => 'O', '&Ocirc;' => 'O', '&#213;' => 'O', '&Otilde;' => 'O',
            '&#214;' => 'Oe', '&Ouml;' => 'Oe',
            '&#216;' => 'O', '&Oslash;' => 'O', '&#332;' => 'O', '&#336;' => 'O', '&#334;' => 'O',
            '&#338;' => 'OE',
            '&#340;' => 'R', '&#344;' => 'R', '&#342;' => 'R',
            '&#346;' => 'S', '&#352;' => 'S', '&#350;' => 'S', '&#348;' => 'S', '&#536;' => 'S',
            '&#356;' => 'T', '&#354;' => 'T', '&#358;' => 'T', '&#538;' => 'T',
            '&#217;' => 'U', '&Ugrave;' => 'U', '&#218;' => 'U', '&Uacute;' => 'U', '&#219;' => 'U', '&Ucirc;' => 'U',
            '&#220;' => 'Ue', '&#362;' => 'U', '&Uuml;' => 'Ue',
            '&#366;' => 'U', '&#368;' => 'U', '&#364;' => 'U', '&#360;' => 'U', '&#370;' => 'U',
            '&#372;' => 'W',
            '&#221;' => 'Y', '&Yacute;' => 'Y', '&#374;' => 'Y', '&#376;' => 'Y',
            '&#377;' => 'Z', '&#381;' => 'Z', '&#379;' => 'Z',
            '&#222;' => 'T', '&THORN;' => 'T',
            '&#224;' => 'a', '&#225;' => 'a', '&#226;' => 'a', '&#227;' => 'a', '&#228;' => 'ae',
            '&auml;' => 'ae',
            '&#229;' => 'a', '&#257;' => 'a', '&#261;' => 'a', '&#259;' => 'a', '&aring;' => 'a',
            '&#230;' => 'ae',
            '&#231;' => 'c', '&#263;' => 'c', '&#269;' => 'c', '&#265;' => 'c', '&#267;' => 'c',
            '&#271;' => 'd', '&#273;' => 'd', '&#240;' => 'd',
            '&#232;' => 'e', '&#233;' => 'e', '&#234;' => 'e', '&#235;' => 'e', '&#275;' => 'e',
            '&#281;' => 'e', '&#283;' => 'e', '&#277;' => 'e', '&#279;' => 'e',
            '&#402;' => 'f',
            '&#285;' => 'g', '&#287;' => 'g', '&#289;' => 'g', '&#291;' => 'g',
            '&#293;' => 'h', '&#295;' => 'h',
            '&#236;' => 'i', '&#237;' => 'i', '&#238;' => 'i', '&#239;' => 'i', '&#299;' => 'i',
            '&#297;' => 'i', '&#301;' => 'i', '&#303;' => 'i', '&#305;' => 'i',
            '&#307;' => 'ij',
            '&#309;' => 'j',
            '&#311;' => 'k', '&#312;' => 'k',
            '&#322;' => 'l', '&#318;' => 'l', '&#314;' => 'l', '&#316;' => 'l', '&#320;' => 'l',
            '&#241;' => 'n', '&#324;' => 'n', '&#328;' => 'n', '&#326;' => 'n', '&#329;' => 'n',
            '&#331;' => 'n',
            '&#242;' => 'o', '&#243;' => 'o', '&#244;' => 'o', '&#245;' => 'o', '&#246;' => 'oe',
            '&ouml;' => 'oe',
            '&#248;' => 'o', '&#333;' => 'o', '&#337;' => 'o', '&#335;' => 'o',
            '&#339;' => 'oe',
            '&#341;' => 'r', '&#345;' => 'r', '&#343;' => 'r',
            '&#353;' => 's',
            '&#249;' => 'u', '&#250;' => 'u', '&#251;' => 'u', '&#252;' => 'ue', '&#363;' => 'u',
            '&uuml;' => 'ue',
            '&#367;' => 'u', '&#369;' => 'u', '&#365;' => 'u', '&#361;' => 'u', '&#371;' => 'u',
            '&#373;' => 'w',
            '&#253;' => 'y', '&#255;' => 'y', '&#375;' => 'y',
            '&#382;' => 'z', '&#380;' => 'z', '&#378;' => 'z',
            '&#254;' => 't',
            '&#223;' => 'ss',
            '&#383;' => 'ss',
            '&agrave;' => 'a', '&aacute;' => 'a', '&acirc;' => 'a', '&atilde;' => 'a', '&auml;' => 'ae',
            '&aring;' => 'a', '&aelig;' => 'ae', '&ccedil;' => 'c', '&eth;' => 'd',
            '&egrave;' => 'e', '&eacute;' => 'e', '&ecirc;' => 'e', '&euml;' => 'e',
            '&igrave;' => 'i', '&iacute;' => 'i', '&icirc;' => 'i', '&iuml;' => 'i',
            '&ntilde;' => 'n',
            '&ograve;' => 'o', '&oacute;' => 'o', '&ocirc;' => 'o', '&otilde;' => 'o', '&ouml;' => 'oe',
            '&oslash;' => 'o',
            '&ugrave;' => 'u', '&uacute;' => 'u', '&ucirc;' => 'u', '&uuml;' => 'ue',
            '&yacute;' => 'y', '&yuml;' => 'y',
            '&thorn;' => 't',
            '&szlig;' => 'ss',
        );

        if (is_file(txpath.'/lib/i18n-ascii.txt')) {
            $i18n = parse_ini_file(txpath.'/lib/i18n-ascii.txt', true);

            // Load the global map.
            if (isset($i18n['default']) && is_array($i18n['default'])) {
                $array[$lang] = array_merge($array[$lang], $i18n['default']);

                // Base language overrides: 'de-AT' applies the 'de' section.
                if (preg_match('/([a-zA-Z]+)-.+/', $lang, $m)) {
                    if (isset($i18n[$m[1]]) && is_array($i18n[$m[1]])) {
                        $array[$lang] = array_merge($array[$lang], $i18n[$m[1]]);
                    }
                }

                // Regional language overrides: 'de-AT' applies the 'de-AT' section.
                if (isset($i18n[$lang]) && is_array($i18n[$lang])) {
                    $array[$lang] = array_merge($array[$lang], $i18n[$lang]);
                }
            }
            // Load an old file (no sections) just in case.
            else {
                $array[$lang] = array_merge($array[$lang], $i18n);
            }
        }
    }

    return strtr($str, $array[$lang]);
}

/**
 * Cleans a URL.
 *
 * @param   string $url The URL
 * @return  string
 * @access  private
 * @package URL
 */

function clean_url($url)
{
    return preg_replace("/\"|'|(?:\s.*$)/", '', $url);
}

/**
 * Replace the last space with a &#160; non-breaking space.
 *
 * @param   string $str The string
 * @return  string
 */

function noWidow($str)
{
    if (REGEXP_UTF8 == 1) {
        return preg_replace('@[ ]+([[:punct:]]?[\p{L}\p{N}\p{Pc}]+[[:punct:]]?)$@u', '&#160;$1', rtrim($str));
    }

    return preg_replace('@[ ]+([[:punct:]]?\w+[[:punct:]]?)$@', '&#160;$1', rtrim($str));
}

/**
 * Checks if an IP is on a spam blocklist.
 *
 * @param   string       $ip     The IP address
 * @param   string|array $checks The checked lists. Defaults to 'spam_blacklists' preferences string
 * @return  string|bool The lists the IP is on or FALSE
 * @package Comment
 * @example
 * if (is_blacklisted('192.0.2.1'))
 * {
 *     echo "'192.0.2.1' is on the blocklist.";
 * }
 */

function is_blacklisted($ip, $checks = '')
{
    if (!$checks) {
        $checks = do_list_unique(get_pref('spam_blacklists'));
    }

    $rip = join('.', array_reverse(explode('.', $ip)));

    foreach ((array) $checks as $a) {
        $parts = explode(':', $a, 2);
        $rbl   = $parts[0];

        if (isset($parts[1])) {
            foreach (explode(':', $parts[1]) as $code) {
                $codes[] = strpos($code, '.') ? $code : '127.0.0.'.$code;
            }
        }

        $hosts = $rbl ? @gethostbynamel($rip.'.'.trim($rbl, '. ').'.') : false;

        if ($hosts and (!isset($codes) or array_intersect($hosts, $codes))) {
            $listed[] = $rbl;
        }
    }

    return (!empty($listed)) ? join(', ', $listed) : false;
}

/**
 * Checks if the user is authenticated on the public-side.
 *
 * @param   string $user The checked username. If not provided, any user is accepted
 * @return  array|bool An array containing details about the user; name, RealName, email, privs. FALSE when the user hasn't authenticated.
 * @package User
 * @example
 * if ($user = is_logged_in())
 * {
 *     echo "Logged in as {$user['RealName']}";
 * }
 */

function is_logged_in($user = '')
{
    static $users = array();

    $name = substr(cs('txp_login_public'), 10);

    if (!strlen($name) || strlen($user) && $user !== $name) {
        return false;
    }

    if (!isset($users[$name])) {
        $users[$name] = safe_row("nonce, name, RealName, email, privs", 'txp_users', "name = '".doSlash($name)."'");
    }

    $rs = $users[$name];

    if ($rs && substr(md5($rs['nonce']), -10) === substr(cs('txp_login_public'), 0, 10)) {
        unset($rs['nonce']);

        return $rs;
    } else {
        return false;
    }
}

/**
 * Updates the path to the site.
 *
 * @param   string $here The path
 * @access  private
 * @package Pref
 */

function updateSitePath($here)
{
    set_pref('path_to_site', $here, 'publish', PREF_HIDDEN);
}

/**
 * Converts Textpattern tag's attribute list to an array.
 *
 * @param   array|string $text The attribute list, e.g. foobar="1" barfoo="0"
 * @return  array Array of attributes
 * @access  private
 * @package TagParser
 */

function splat($text)
{
    static $stack = array(), $parse = array(), $global_atts = array(), $globals = null;
    global $production_status, $trace, $txp_atts;

    if ($globals === null) {
        $globals = array_filter(Txp::get('\Textpattern\Tag\Registry')->getRegistered(true));
    }

    if (is_array($text)) {
        $txp_atts = array_intersect_key($text, $globals);
        return $text;
    }

    $sha = txp_hash($text);

    if (!isset($stack[$sha])) {
        $stack[$sha] = $parse[$sha] = array();

        if (preg_match_all('@([\w\-]+)(?:\s*=\s*(?:"((?:[^"]|"")*)"|\'((?:[^\']|\'\')*)\'|([^\s\'"/>]+)))?@s', $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                $name = strtolower($m[1]);

                switch (count($m)) {
                    case 2:
                        $val = true;
                        break;
                    case 3:
                        $val = str_replace('""', '"', $m[2]);
                        break;
                    case 4:
                        $val = str_replace("''", "'", $m[3]);

                        if (strpos($m[3], ':') !== false) {
                            $parse[$sha][] = $name;
                        }

                        break;
                    case 5:
                        $val = $m[4];
                        trigger_error(gTxt('attribute_values_must_be_quoted'), E_USER_WARNING);
                        break;
                }

                $stack[$sha][$name] = $val;
            }
        }

        $global_atts[$sha] = array_intersect_key($stack[$sha], $globals) or $global_atts[$sha] = null;
    }

    $txp_atts = $global_atts[$sha];

    if (empty($parse[$sha])) {
        return $stack[$sha];
    }

    $atts = $stack[$sha];

    if ($production_status !== 'live') {
        foreach ($parse[$sha] as $p) {
            $trace->start("[attribute '".$p."']");
            $atts[$p] = parse($atts[$p], true, false);
            isset($txp_atts[$p]) and $txp_atts[$p] = $atts[$p];
            $trace->stop('[/attribute]');
        }
    } else {
        foreach ($parse[$sha] as $p) {
            $atts[$p] = parse($atts[$p], true, false);
            isset($txp_atts[$p]) and $txp_atts[$p] = $atts[$p];
        }
    }

    return $atts;
}

/**
 * Replaces CR and LF with spaces, and drops NULL bytes.
 *
 * Used for sanitising email headers.
 *
 * @param      string $str The string
 * @return     string
 * @package    Mail
 * @deprecated in 4.6.0
 * @see        \Textpattern\Mail\Encode::escapeHeader()
 */

function strip_rn($str)
{
    return Txp::get('\Textpattern\Mail\Encode')->escapeHeader($str);
}

/**
 * Validates a string as an email address.
 *
 * <code>
 * if (is_valid_email('john.doe@example.com'))
 * {
 *     echo "'john.doe@example.com' validates.";
 * }
 * </code>
 *
 * @param      string $address The email address
 * @return     bool
 * @package    Mail
 * @deprecated in 4.6.0
 * @see        filter_var()
 */

function is_valid_email($address)
{
    return (bool) filter_var($address, FILTER_VALIDATE_EMAIL);
}

/**
 * Sends an email message as the currently logged in user.
 *
 * <code>
 * if (txpMail('john.doe@example.com', 'Subject', 'Some message'))
 * {
 *     echo "Email sent to 'john.doe@example.com'.";
 * }
 * </code>
 *
 * @param   string $to_address The receiver
 * @param   string $subject    The subject
 * @param   string $body       The message
 * @param   string $reply_to The reply to address
 * @return  bool   Returns FALSE when sending failed
 * @see     \Textpattern\Mail\Compose
 * @package Mail
 */

function txpMail($to_address, $subject, $body, $reply_to = null)
{
    global $txp_user;

    // Send the email as the currently logged in user.
    if ($txp_user) {
        $sender = safe_row(
            "RealName, email",
            'txp_users',
            "name = '".doSlash($txp_user)."'"
        );

        if ($sender && is_valid_email(get_pref('publisher_email'))) {
            $sender['email'] = get_pref('publisher_email');
        }
    }
    // If not logged in, the receiver is the sender.
    else {
        $sender = safe_row(
            "RealName, email",
            'txp_users',
            "email = '".doSlash($to_address)."'"
        );
    }

    if ($sender) {
        extract($sender);

        try {
            $message = Txp::get('\Textpattern\Mail\Compose')
                ->from($email, $RealName)
                ->to($to_address)
                ->subject($subject)
                ->body($body);

            if ($reply_to) {
                $message->replyTo($reply_to);
            }

            $message->send();
        } catch (\Textpattern\Mail\Exception $e) {
            return false;
        }

        return true;
    }

    return false;
}

/**
 * Encodes a string for use in an email header.
 *
 * @param      string $string The string
 * @param      string $type   The type of header, either "text" or "phrase"
 * @return     string
 * @package    Mail
 * @deprecated in 4.6.0
 * @see        \Textpattern\Mail\Encode::header()
 */

function encode_mailheader($string, $type)
{
    try {
        return Txp::get('\Textpattern\Mail\Encode')->header($string, $type);
    } catch (\Textpattern\Mail\Exception $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
    }
}

/**
 * Converts an email address into unicode entities.
 *
 * @param      string $txt The email address
 * @return     string Encoded email address
 * @package    Mail
 * @deprecated in 4.6.0
 * @see        \Textpattern\Mail\Encode::entityObfuscateAddress()
 */

function eE($txt)
{
    return Txp::get('\Textpattern\Mail\Encode')->entityObfuscateAddress($txt);
}

/**
 * Strips PHP tags from a string.
 *
 * @param  string $in The input
 * @return string
 */

function stripPHP($in)
{
    return preg_replace("/".chr(60)."\?(?:php)?|\?".chr(62)."/i", '', $in);
}

/**
 * Creates a form template.
 *
 * On a successful run, will trigger a 'form.create > done' callback event.
 *
 * @param      string $name The name
 * @param      string $type The type
 * @param      string $Form The template
 * @return     bool FALSE on error
 * @since      4.6.0
 * @deprecated 4.8.6 (not skin-aware)
 * @see        Textpattern\Skin\Skin
 * @package    Template
 */

function create_form($name, $type, $Form)
{
    $types = get_form_types();

    if (form_exists($name) || !is_valid_form($name) || !in_array($type, array_keys($types))) {
        return false;
    }

    if (
        safe_insert(
            'txp_form',
            "name = '".doSlash($name)."',
            type = '".doSlash($type)."',
            Form = '".doSlash($Form)."'"
        ) === false
    ) {
        return false;
    }

    callback_event('form.create', 'done', 0, compact('name', 'type', 'Form'));

    return true;
}

/**
 * Checks if a form template exists.
 *
 * @param      string $name The form
 * @return     bool TRUE if the form exists
 * @since      4.6.0
 * @deprecated 4.8.6 (not skin-aware)
 * @see        Textpattern\Skin\CommonBase
 * @package    Template
 */

function form_exists($name)
{
    return (bool) safe_row("name", 'txp_form', "name = '".doSlash($name)."'");
}

/**
 * Validates a string as a form template name.
 *
 * @param      string $name The form name
 * @return     bool TRUE if the string validates
 * @since      4.6.0
 * @deprecated 4.8.6
 * @see        Textpattern\Skin\CommonBase
 * @package    Template
 */

function is_valid_form($name)
{
    if (function_exists('mb_strlen')) {
        $length = mb_strlen($name, '8bit');
    } else {
        $length = strlen($name);
    }

    return $name && !preg_match('/^\s|[<>&"\']|\s$/u', $name) && $length <= 64;
}

/**
 * Validates a string as a date% query.
 *
 * @param   string $date The partial date
 * @return  bool|string FALSE if the string does not validate
 * @since   4.8.5
 * @package Template
 */

function is_date($month)
{
    if (!preg_match('/^\d{1,4}(?:\-\d{1,2}){0,2}$/', $month)) {
        return false;
    }

    $month = explode('-', $month, 3);
    $result = true;

    switch (count($month)) {
        case 3:
            $result = checkdate($month[1], $month[2], $month[0]) and
            $month[2] = str_pad($month[2], 2, '0', STR_PAD_LEFT);
        case 2:
            $result = $result && $month[1] > 0 && $month[1] < 13;
            !$result or $month[1] = str_pad($month[1], 2, '0', STR_PAD_LEFT);
        case 1:
            $result = $result && $month[0] > 0;
            !$result or $month[0] = str_pad($month[0], 4, '0', STR_PAD_LEFT);
    }

    return $result ? implode('-', $month) : false;
}

/**
 * Gets a "since days ago" date format from a given UNIX timestamp.
 *
 * @param   int $stamp UNIX timestamp
 * @return  string "n days ago"
 * @package DateTime
 */

function since($stamp)
{
    $diff = (time() - $stamp);

    if ($diff <= 3600) {
        $qty = round($diff / 60);

        if ($qty < 1) {
            $qty = '';
            $period = gTxt('a_few_seconds');
        } elseif ($qty == 1) {
            $period = gTxt('minute');
        } else {
            $period = gTxt('minutes');
        }
    } elseif (($diff <= 86400) && ($diff > 3600)) {
        $qty = round($diff / 3600);

        if ($qty <= 1) {
            $qty = 1;
            $period = gTxt('hour');
        } else {
            $period = gTxt('hours');
        }
    } elseif ($diff >= 86400) {
        $qty = round($diff / 86400);

        if ($qty <= 1) {
            $qty = 1;
            $period = gTxt('day');
        } else {
            $period = gTxt('days');
        }
    }

    return gTxt('ago', array('{qty}' => $qty, '{period}' => $period));
}

/**
 * Calculates a timezone offset.
 *
 * Calculates the offset between the server local time and the
 * user's selected timezone at a given point in time.
 *
 * @param   int $timestamp The timestamp. Defaults to time()
 * @return  int The offset in seconds
 * @package DateTime
 */

function tz_offset($timestamp = null)
{
    global $gmtoffset, $timezone_key;
    static $dtz = array(), $timezone_server = null;

    if ($timezone_server === null) {
        $timezone_server = date_default_timezone_get();
    }

    if ($timezone_server === $timezone_key) {
        return 0;
    }

    if ($timestamp === null) {
        $timestamp = time();
    }

    try {
        if (!isset($dtz[$timezone_server])) {
            $dtz[$timezone_server] = new \DateTimeZone($timezone_server);
        }

        $transition = $dtz[$timezone_server]->getTransitions($timestamp, $timestamp);
        $serveroffset = $transition[0]['offset'];
    } catch (\Exception $e) {
        extract(getdate($timestamp));
        $serveroffset = gmmktime($hours, $minutes, 0, $mon, $mday, $year) - mktime($hours, $minutes, 0, $mon, $mday, $year);
    }

    try {
        if (!isset($dtz[$timezone_key])) {
            $dtz[$timezone_key] = new \DateTimeZone($timezone_key);
        }

        $transition = $dtz[$timezone_key]->getTransitions($timestamp, $timestamp);
        $siteoffset = $transition[0]['offset'];
    } catch (\Exception $e) {
        $siteoffset = $gmtoffset;
    }

    return $siteoffset - $serveroffset;
}

/**
 * Formats a time.
 *
 * Respects the locale and local timezone, and makes sure the
 * output string is encoded in UTF-8.
 *
 * @param   string $format          The date format
 * @param   int    $time            UNIX timestamp. Defaults to time()
 * @param   bool   $gmt             Return GMT time
 * @param   string $override_locale Override the locale
 * @return  string Formatted date
 * @package DateTime
 * @example
 * echo intl_strftime('w3cdtf');
 */

function intl_strftime($format, $time = null, $gmt = false, $override_locale = '')
{
    global $lang_ui;
    static $DateTime = null, $IntlDateFormatter = array(), $default = array(), $formats = array(
        '%a' => 'eee',
        '%A' => 'eeee',
        '%d' => 'dd',
        '%e' => 'd',
        '%Oe' => 'd',
        '%j' => 'D',
        '%u' => 'c',
        '%w' => 'e',
        '%U' => 'w',
        '%V' => 'ww',
        '%W' => 'ww',
        '%b' => 'MMM',
        '%B' => 'MMMM',
        '%h' => 'MMM',
        '%m' => 'MM',
        '%g' => 'yy',
        '%G' => 'Y',
        '%Y' => 'y',
        '%y' => 'yy',
        '%H' => 'HH',
        '%k' => 'H',
        '%I' => 'hh',
        '%l' => 'h',
        '%M' => 'mm',
        '%S' => 'ss',
        '%p' => 'a',
        '%P' => 'a',
        '%r' => 'h:mm:ss a',
        '%R' => 'HH:mm',
        '%T' => 'HH:mm:ss',
        '%z' => 'Z',
        '%Z' => 'z',
        '%D' => 'MM/dd/yy',
        '%F' => 'yy-MM-dd',
        '%n' => n,
        '%t' => t,
        '%%' => '%',
    );

    if ($DateTime === null) {
        $DateTime = new DateTime();
    }

    $override_locale or $override_locale = txpinterface == 'admin' ? $lang_ui : LANG;

    if (!isset($IntlDateFormatter[$override_locale])) {
        $IntlDateFormatter[$override_locale] = new IntlDateFormatter(
            $override_locale,
            IntlDateFormatter::LONG,
            IntlDateFormatter::SHORT,
            null,
            /*strpos($override_locale, 'calendar') === false ? null :*/ IntlDateFormatter::TRADITIONAL
        );
        $pattern = $IntlDateFormatter[$override_locale]->getPattern();
        $xt = datefmt_create($override_locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT,
        null, IntlDateFormatter::TRADITIONAL)->getPattern();//trim(preg_replace('/[^aHhmps:\s]/', '', $pattern));
        $xd = datefmt_create($override_locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE,
        null, IntlDateFormatter::TRADITIONAL)->getPattern();//trim(str_replace($xt, '', $pattern), ' ,');
        $default[$override_locale] = array('%c' => $pattern, '%x' => $xd, '%X' => $xt);
    }

    $DateTime->setTimestamp($time);

    $formats['%s'] = $time;
    $format = strtr($format, $formats + $default[$override_locale]);
    !$gmt or $IntlDateFormatter[$override_locale]->setTimeZone('GMT+0');
    $IntlDateFormatter[$override_locale]->setPattern($format);
    $str = $IntlDateFormatter[$override_locale]->format($DateTime);
    !$gmt or $IntlDateFormatter[$override_locale]->setTimeZone(null);

    return $str;
}

/**
 * Formats a time.
 *
 * Respects the locale and local timezone, and makes sure the
 * output string is encoded in UTF-8.
 *
 * @param   string $format          The date format
 * @param   int    $time            UNIX timestamp. Defaults to time()
 * @param   bool   $gmt             Return GMT time
 * @param   string $override_locale Override the locale
 * @return  string Formatted date
 * @package DateTime
 * @example
 * echo safe_strftime('w3cdtf');
 */

function safe_strftime($format, $time = null, $gmt = false, $override_locale = '')
{
    static $charsets = array(), $txpLocale = null, $intl = null, $formats = array( //'rfc850', 'rfc1036', 'rfc1123', 'rfc2822' ?
        'atom' => DATE_ATOM, 'w3cdtf' => DATE_ATOM, 'rss' => DATE_RSS, 'cookie' => DATE_COOKIE, 'w3c' => DATE_W3C, 'iso8601' => DATE_ISO8601, 'rfc822' => DATE_RFC822,
    ), $translate = array(
        '%a' => 'D',
        '%A' => 'l',
        '%d' => 'd',
        '%e' => 'j',
        '%Oe' => 'jS',
        '%j' => 'z',
        '%u' => 'N',
        '%w' => 'w',
        '%U' => 'W',
        '%V' => 'W',
        '%W' => 'W',
        '%b' => 'M',
        '%B' => 'F',
        '%h' => 'M',
        '%m' => 'm',
        '%g' => 'y',
        '%G' => 'o',
        '%Y' => 'Y',
        '%y' => 'y',
        '%H' => 'H',
        '%k' => 'G',
        '%I' => 'h',
        '%l' => 'g',
        '%M' => 'i',
        '%S' => 's',
        '%p' => 'A',
        '%P' => 'a',
        '%r' => 'g:i:s A',
        '%R' => 'H:i',
        '%T' => 'H:i:s',
        '%z' => 'O',
        '%Z' => 'T',
        '%D' => 'm/d/y',
        '%F' => 'Y-m-d',
        '%s' => 'U',
        '%n' => n,
        '%t' => t,
        '%%' => '%',
    );

    $time = isset($time) ? (int)$time : time();

    if ($intl === null) {
        $intl = class_exists('IntlDateFormatter');
    }

    if ($format == 'since') {
        return since($time);
    } elseif (isset($formats[$format])) {
        // We could add some other formats here.
        return gmdate($formats[$format], $time);
    } elseif (strpos($format, '%') === false) {
        return $intl ? intl_strftime($format, $time, $gmt, $override_locale) : ($gmt ? gmdate($format, $time) : date($format, $time));
    } elseif (!preg_match('/\%[aAbBchOxX]/', $format) && strpos($override_locale, 'calendar') === false) {
        return $gmt ? gmdate(strtr($format, $translate), $time) : date(strtr($format, $translate), $time);
    } elseif ($intl) {
        return intl_strftime($format, $time, $gmt, $override_locale);
    }

    if ($txpLocale === null) {
        $txpLocale = Txp::get('\Textpattern\L10n\Locale');
    }

    if ($override_locale) {
        $oldLocale = $txpLocale->getLocale(LC_TIME);

        if ($oldLocale != $override_locale) {
            $txpLocale->setLocale(LC_TIME, $override_locale);
        } else {
            $oldLocale = null;
        }
    }

    if ($gmt) {
        $str = gmstrftime($format, $time);
    } else {
        $tztime = $time + tz_offset($time);
        $format = str_replace('%s', $tztime, $format);
        $str = strftime($format, $tztime);
    }

    if (!isset($charsets[$override_locale])) {
        $charsets[$override_locale] = strtoupper($txpLocale->getCharset(LC_TIME, IS_WIN ? 'Windows-1252' : 'ISO-8859-1'));
    }

    $charset = $charsets[$override_locale];

    if ($charset != 'UTF-8' && $charset != 'UTF8') {
        if (is_callable('iconv') && $new = iconv($charset, 'UTF-8', $str)) {
            $str = $new;
        } elseif (is_callable('utf8_encode')) {
            $str = utf8_encode($str);
        }
    }

    // Revert to the old locale.
    if (isset($oldLocale)) {
        $txpLocale->setLocale(LC_TIME, $oldLocale);
    }

    return $str;
}

/**
 * Converts a time string from the Textpattern timezone to GMT.
 *
 * @param   string $time_str The time string
 * @return  int UNIX timestamp
 * @package DateTime
 */

function safe_strtotime($time_str)
{
    $ts = strtotime($time_str);

    // tz_offset calculations are expensive
    $tz_offset = tz_offset($ts);

    return strtotime($time_str, time() + $tz_offset) - $tz_offset;
}

/**
 * Generic error handler.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!error_reporting()) {
        return;
    }

    echo '<pre dir="auto">'.n.n."$errno: $errstr in $errfile at line $errline\n";

    if (is_callable('debug_backtrace')) {
        echo "Backtrace:\n";
        $trace = debug_backtrace();

        foreach ($trace as $ent) {
            if (isset($ent['file'])) {
                echo $ent['file'].':';
            }

            if (isset($ent['function'])) {
                echo $ent['function'].'(';

                if (isset($ent['args'])) {
                    $args = '';

                    foreach ($ent['args'] as $arg) {
                        $args .= $arg.',';
                    }

                    echo rtrim($args, ',');
                }

                echo ') ';
            }

            if (isset($ent['line'])) {
                echo 'at line '.$ent['line'].' ';
            }

            if (isset($ent['file'])) {
                echo 'in '.$ent['file'];
            }

            echo "\n";
        }
    }

    echo "</pre>";
}

/**
 * Renders a download link.
 *
 * @param   int    $id       The file ID
 * @param   string $label    The label
 * @param   string $filename The filename
 * @return  string HTML
 * @package File
 */

function make_download_link($id, $label = '', $filename = '')
{
    if ((string) $label === '') {
        $label = gTxt('download');
    }

    $url = filedownloadurl($id, $filename);

    // Do not use the array() form of passing $atts to href().
    // Doing so breaks download links on the admin side due to
    // double-encoding of the ampersands.
    return href($label, $url, ' title = "'.gTxt('download').'"');
}

/**
 * Sets error reporting level.
 *
 * @param   string $level The level. Either "debug", "live" or "testing"
 * @package Debug
 */

function set_error_level($level)
{
    if ($level == 'debug') {
        error_reporting(E_ALL | E_STRICT);
    } elseif ($level == 'live') {
        // Don't show errors on screen.
        $suppress = E_NOTICE | E_USER_NOTICE | E_WARNING | E_STRICT | E_DEPRECATED;
        error_reporting(E_ALL ^ $suppress);
        @ini_set("display_errors", "1");
    } else {
        // Default is 'testing': display everything except notices.
        error_reporting((E_ALL | E_STRICT) ^ (E_NOTICE | E_USER_NOTICE));
    }
}

/**
 * Translates upload error code to a localised error message.
 *
 * @param   int $err_code The error code
 * @return  string The $err_code as a message
 * @package File
 */

function upload_get_errormsg($err_code)
{
    $msg = '';

    switch ($err_code) {
        // Value: 0; There is no error, the file uploaded with success.
        case UPLOAD_ERR_OK:
            $msg = '';
            break;
        // Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
        case UPLOAD_ERR_INI_SIZE:
            $msg = gTxt('upload_err_ini_size');
            break;
        // Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
        case UPLOAD_ERR_FORM_SIZE:
            $msg = gTxt('upload_err_form_size');
            break;
        // Value: 3; The uploaded file was only partially uploaded.
        case UPLOAD_ERR_PARTIAL:
            $msg = gTxt('upload_err_partial');
            break;
        // Value: 4; No file was uploaded.
        case UPLOAD_ERR_NO_FILE:
            $msg = gTxt('upload_err_no_file');
            break;
        // Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
        case UPLOAD_ERR_NO_TMP_DIR:
            $msg = gTxt('upload_err_tmp_dir');
            break;
        // Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.
        case UPLOAD_ERR_CANT_WRITE:
            $msg = gTxt('upload_err_cant_write');
            break;
        // Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0.
        case UPLOAD_ERR_EXTENSION:
            $msg = gTxt('upload_err_extension');
            break;
    }

    return $msg;
}

/**
 * Formats a file size.
 *
 * @param   int    $bytes    Size in bytes
 * @param   int    $decimals Number of decimals
 * @param   string $format   The format the size is represented
 * @return  string Formatted file size
 * @package File
 * @example
 * echo format_filesize(168642);
 */

function format_filesize($bytes, $decimals = 2, $format = '')
{
    $units = array('b', 'k', 'm', 'g', 't', 'p', 'e', 'z', 'y');

    if (in_array($format, $units)) {
        $pow = array_search($format, $units);
    } else {
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
    }

    $bytes /= pow(1024, $pow);

    $separators = localeconv();
    $sep_dec = isset($separators['decimal_point']) ? $separators['decimal_point'] : '.';
    $sep_thous = isset($separators['thousands_sep']) ? $separators['thousands_sep'] : ',';

    return number_format($bytes, $decimals, $sep_dec, $sep_thous).sp.gTxt('units_'.$units[$pow]);
}

/**
 * Gets a file download as an array.
 *
 * @param   string $where SQL where clause
 * @return  array|bool An array of files, or FALSE on failure
 * @package File
 * @example
 * if ($file = fileDownloadFetchInfo('id = 1'))
 * {
 *     print_r($file);
 * }
 */

function fileDownloadFetchInfo($where)
{
    $rs = safe_row("*", 'txp_file', $where);

    if ($rs) {
        return file_download_format_info($rs);
    }

    return false;
}

/**
 * Formats file download info.
 *
 * Takes a data array generated by fileDownloadFetchInfo()
 * and formats the contents.
 *
 * @param   array $file The file info to format
 * @return  array Formatted file info
 * @access  private
 * @package File
 */

function file_download_format_info($file)
{
    if (($unix_ts = @strtotime($file['created'])) > 0) {
        $file['created'] = $unix_ts;
    }

    if (($unix_ts = @strtotime($file['modified'])) > 0) {
        $file['modified'] = $unix_ts;
    }

    return $file;
}

/**
 * Formats file download's modification and creation timestamps.
 *
 * Used by file_download tags.
 *
 * @param   array $params
 * @return  string
 * @access  private
 * @package File
 */

function fileDownloadFormatTime($params)
{
    extract(lAtts(array(
        'ftime'  => '',
        'format' => '',
    ), $params));

    if (!empty($ftime)) {
        if ($format) {
            return safe_strftime($format, $ftime);
        }

        return safe_strftime(get_pref('archive_dateformat'), $ftime);
    }

    return '';
}

/**
 * file_get_contents wrapper.
 *
 */

function txp_get_contents($file)
{
    return is_readable($file) ? file_get_contents($file) : '';
}

/**
 * Returns the contents of the found files as an array.
 *
 */

function get_files_content($dir, $ext)
{
    $result = array();

    if (is_readable($dir)) {
        foreach ((array)scandir($dir) as $file) {
            if (preg_match('/^(.+)\.'.$ext.'$/', $file, $match)) {
                $result[$match[1]] = file_get_contents("$dir/$file");
            }
        }
    }

    return $result;
}

/**
 * Checks if a function is disabled.
 *
 * @param   string $function The function name
 * @return  bool TRUE if the function is disabled
 * @package System
 * @example
 * if (is_disabled('mail'))
 * {
 *     echo "'mail' function is disabled.";
 * }
 */

function is_disabled($function)
{
    static $disabled;

    if (!isset($disabled)) {
        $disabled = do_list(ini_get('disable_functions'));
    }

    return in_array($function, $disabled);
}

/**
 * Joins two strings to form a single filesystem path.
 *
 * @param   string $base The base directory
 * @param   string $path The second path, a relative filename
 * @return  string A path to a file
 * @package File
 */

function build_file_path($base, $path)
{
    $base = rtrim($base, '/\\');
    $path = ltrim($path, '/\\');

    return $base.DS.$path;
}

/**
 * Gets a user's real name.
 *
 * @param   string $name The username
 * @return  string A real name, or username if empty
 * @package User
 */

function get_author_name($name)
{
    static $authors = array();

    if (isset($authors[$name])) {
        return $authors[$name];
    }

    $realname = fetch('RealName', 'txp_users', 'name', $name);
    $authors[$name] = $realname;

    return ($realname) ? $realname : $name;
}

/**
 * Gets a user's email address.
 *
 * @param   string $name The username
 * @return  string
 * @package User
 */

function get_author_email($name)
{
    static $authors = array();

    if (isset($authors[$name])) {
        return $authors[$name];
    }

    $email = fetch('email', 'txp_users', 'name', $name);
    $authors[$name] = $email;

    return $email;
}

/**
 * Checks if a database table contains items just from one user.
 *
 * @param   string $table The database table
 * @param   string $col   The column
 * @return  bool
 * @package User
 * @example
 * if (has_single_author('textpattern', 'AuthorID'))
 * {
 *     echo "'textpattern' table has only content from one author.";
 * }
 */

function has_single_author($table, $col = 'author')
{
    static $cache = array();

    if (!isset($cache[$table][$col])) {
        $cache[$table][$col] = (safe_field("COUNT(name)", 'txp_users', "1 = 1") <= 1) &&
            (safe_field("COUNT(DISTINCT(".doSlash($col)."))", doSlash($table), "1 = 1") <= 1);
    }

    return $cache[$table][$col];
}

/**
 * Parse a string and store the result.
 *
 * @param   string        $thing        The raw string
 * @param   null|string   $hash         The string SHA1 hash
 * @param   bool|callable $transform    The function applied to txp tags
 * @package TagParser
 */

function txp_tokenize($thing, $hash = null, $transform = null)
{
    global $txp_parsed, $txp_else;
    static $short_tags = null;

    isset($short_tags) or $short_tags = get_pref('enable_short_tags', false);

    $f = '@(</?(?:'.TXP_PATTERN.'):\w+(?:\[-?\d+\])?(?:\s+[\w\-]+(?:\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/>]+))?)*\s*/?\>)@s';
    $t = '@^</?('.TXP_PATTERN.'):(\w+)(?:\[(-?\d+)\])?(.*)\>$@s';

    $parsed = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE);
    $last = count($parsed);

    if (isset($transform) && (is_bool($transform) || is_callable($transform))) {
        $transform !== true or $transform = 'txpspecialchars';

        for ($i = 1; $i < $last; $i+=2) {
            $parsed[$i] = $transform === false ? null : call_user_func($transform, $parsed[$i]);
        }
    }

    if ($hash === false) {
        return $parsed;
    } elseif ($last === 1) {
        return false;
    } elseif (!is_string($hash)) {
        $hash = txp_hash($thing);
    }

    $inside  = array($parsed[0]);
    $tags    = array($inside);
    $tag     = array();
    $outside = array();
    $order = array(array());
    $else    = array(-1);
    $count   = array(-1);
    $level   = 0;

    for ($i = 1; $i < $last || $level > 0; $i++) {
        $chunk = $i < $last ? $parsed[$i] : '</txp:'.$tag[$level-1][2].'>';
        preg_match($t, $chunk, $tag[$level]);
        $count[$level] += 2;

        if ($tag[$level][2] === 'else') {
            $else[$level] = $count[$level];
        } elseif ($tag[$level][1] === 'txp:') {
            // Handle <txp::shortcode />.
            $tag[$level][4] .= ' form="'.$tag[$level][2].'"';
            $tag[$level][2] = 'output_form';
        } elseif ($short_tags && $tag[$level][1] !== 'txp') {
            // Handle <short::tags />.
            $tag[$level][2] = rtrim($tag[$level][1], ':').'_'.$tag[$level][2];
        }

        if ($chunk[strlen($chunk) - 2] === '/') {
            // Self closed tag.
            if ($chunk[1] === '/') {
                trigger_error(gTxt('ambiguous_tag_format', array('{chunk}' => $chunk)), E_USER_WARNING);
            }

            $tags[$level][] = array($chunk, $tag[$level][2], trim(rtrim($tag[$level][4], '/')), null, null);
            $inside[$level] .= $chunk;
            empty($tag[$level][3]) or $order[$level][count($tags[$level])/2] = $tag[$level][3];
        } elseif ($chunk[1] !== '/') {
            // Opening tag.
            $inside[$level] .= $chunk;
            empty($tag[$level][3]) or $order[$level][(count($tags[$level])+1)/2] = $tag[$level][3];
            $level++;
            $outside[$level] = $chunk;
            $inside[$level] = '';
            $else[$level] = $count[$level] = -1;
            $tags[$level] = array();
            $order[$level] = array();
        } else {
            // Closing tag.
            if ($level < 1) {
                trigger_error(gTxt('missing_open_tag', array('{chunk}' => $chunk)), E_USER_WARNING);
                $tags[$level][] = array($chunk, null, '', null, null);
                $inside[$level] .= $chunk;
            } else {
                if ($i >= $last) {
                    trigger_error(gTxt('missing_close_tag', array('{chunk}' => $outside[$level])), E_USER_WARNING);
                } elseif ($tag[$level-1][2] != $tag[$level][2]) {
                    trigger_error(gTxt('mismatch_open_close_tag', array(
                        '{from}' => $outside[$level],
                        '{to}'   => $chunk,
                    )), E_USER_WARNING);
                }

                if ($count[$level] > 2) {
                    $sha = txp_hash($inside[$level]);
                    txp_fill_parsed($sha, $tags[$level], $order[$level], $count[$level], $else[$level]);
                }
    
                $level--;
                $tags[$level][] = array($outside[$level+1], $tag[$level][2], trim($tag[$level][4]), $inside[$level+1], $chunk);
                $inside[$level] .= $inside[$level+1].$chunk;
            }
        }

        $chunk = ++$i < $last ? $parsed[$i] : '';
        $tags[$level][] = $chunk;
        $inside[$level] .= $chunk;
    }

    txp_fill_parsed($hash, $tags[0], $order[0], $count[0] + 2, $else[0]);

    return true;
}

/** Auxiliary **/

function txp_fill_parsed($sha, $tags, $order, $count, $else) {
    global $txp_parsed, $txp_else;

    $txp_parsed[$sha] = $tags;
    $txp_else[$sha] = array($else > 0 ? $else : $count, $count - 2);

    if (!empty($order)) {
        $pre = array_filter($order, function ($v) {return $v > 0;});
        $post = array_filter($order, function ($v) {return $v < 0;});

        if  ($pre) {
            asort($pre);
        }

        if  ($post) {
            asort($post);
        }

        $txp_else[$sha]['test'] = $post ? array_merge(array_keys($pre), array(0), array_keys($post)) : ($pre ? array_keys($pre) : null);
    }
}


/**
 * Extracts a statement from a if/else condition.
 *
 * @param   string  $thing     Statement in Textpattern tag markup presentation
 * @param   bool    $condition TRUE to return if statement, FALSE to else
 * @return  string             Either if or else statement
 * @since   4.8.2
 * @see     parse
 * @package TagParser
 * @example
 * echo getIfElse('true &lt;txp:else /&gt; false', 1 === 1);
 */

function getIfElse($thing, $condition = true)
{
    global $txp_parsed, $txp_else;

    if (!$thing || strpos($thing, ':else') === false) {
        return $condition ? $thing : null;
    }

    $hash = txp_hash($thing);

    if (!isset($txp_parsed[$hash]) && !txp_tokenize($thing, $hash)) {
        return $condition ? $thing : null;
    }

    $tag = $txp_parsed[$hash];
    list($first, $last) = $txp_else[$hash];

    if ($condition) {
        $last = $first - 2;
        $first   = 1;
    } elseif ($first <= $last) {
        $first  += 2;
    } else {
        return null;
    }

    for ($out = $tag[$first - 1]; $first <= $last; $first++) {
        $out .= $tag[$first][0].$tag[$first][3].$tag[$first][4].$tag[++$first];
    }

    return $out;
}

/**
 * Extracts a statement from a if/else condition to parse.
 *
 * @param   string  $thing     Statement in Textpattern tag markup presentation
 * @param   bool    $condition TRUE to return if statement, FALSE to else
 * @return  string             Either if or else statement
 * @deprecated in 4.6.0
 * @see     parse
 * @package TagParser
 * @example
 * echo parse(EvalElse('true &lt;txp:else /&gt; false', 1 === 1));
 */

function EvalElse($thing, $condition)
{
    global $txp_atts;

    if (!empty($txp_atts['not'])) {
        $condition = empty($condition);
        unset($txp_atts['not']);
    }

    if (empty($condition)) {
        $txp_atts = null;
    }

    return (string)getIfElse($thing, $condition);
}

/**
 * Gets a form template's contents.
 *
 * The form template's reading method can be modified by registering a handler
 * to a 'form.fetch' callback event. Any value returned by the callback function
 * will be used as the form template markup.
 *
 * @param   array|string $name The form
 * @return  string
 * @package TagParser
 */

function fetch_form($name, $theme = null)
{
    global $skin;
    static $forms = array();

    isset($theme) or $theme = $skin;
    isset($forms[$theme]) or $forms[$theme] = array();
    $fetch = is_array($name);

    if ($fetch || !isset($forms[$theme][$name])) {
        $names = $fetch ? array_diff($name, array_keys($forms[$theme])) : array($name);

        if (has_handler('form.fetch')) {
            foreach ($names as $name) {
                $forms[$theme][$name] = callback_event('form.fetch', '', false, compact('name', 'skin', 'theme'));
            }
        } elseif ($fetch) {
            $forms[$theme] += array_fill_keys($names, false);
            $nameset = implode(',', quote_list($names));

            if ($nameset and $rs = safe_rows_start('name, Form', 'txp_form', "name IN (".$nameset.") AND skin = '".doSlash($theme)."'")) {
                while ($row = nextRow($rs)) {
                    $forms[$theme][$row['name']] = $row['Form'];
                }
            }
        } else {
            $forms[$theme][$name] = safe_field('Form', 'txp_form', "name ='".doSlash($name)."' AND skin = '".doSlash($theme)."'");
        }

        foreach ($names as $form) {
            if ($forms[$theme][$form] === false) {
                trigger_error(gTxt('form_not_found', array('{list}' => $theme.'.'.$form)));
            }
        }
    }

    if (!$fetch) {
        return $forms[$theme][$name];
    }
}

/**
 * Parses a form template.
 *
 * @param   string $name The form
 * @return  string The parsed contents
 * @package TagParser
 */

function parse_form($name, $theme = null)
{
    global $production_status, $skin, $txp_current_form, $trace;
    static $stack = array(), $depth = null;

    if ($depth === null) {
        $depth = get_pref('form_circular_depth', 15);
    }

    isset($theme) or $theme = $skin;
    $name = (string) $name;
    $f = fetch_form($name, $theme);

    if ($f === false) {
        return false;
    }

    if (!isset($stack[$name])) {
        $stack[$name] = 1;
    } elseif ($stack[$name] >= $depth) {
        trigger_error(gTxt('form_circular_reference', array('{name}' => $name)));

        return '';
    } else {
        $stack[$name]++;
    }

    $old_form = $txp_current_form;
    $txp_current_form = $name;

    if ($production_status === 'debug') {
        $trace->log("[Form: '$theme.$name']");
        $trace->log("[Nesting forms: '".join("' / '", array_keys(array_filter($stack)))."'".($stack[$name] > 1 ? '('.$stack[$name].')' : '')."]");
    }

    $out = parse($f);

    $txp_current_form = $old_form;
    $stack[$name]--;

    return $out;
}

/**
 * Gets a page template's contents.
 *
 * The page template's reading method can be modified by registering a handler
 * to a 'page.fetch' callback event. Any value returned by the callback function
 * will be used as the template markup.
 *
 * @param   string      $name The template
 * @param   string      $theme The public theme
 * @return  string|bool The page template, or FALSE on error
 * @package TagParser
 * @since   4.6.0
 * @example
 * echo fetch_page('default');
 */

function fetch_page($name, $theme)
{
    global $pretext, $trace;

    if (empty($theme)) {
        if (empty($pretext['skin'])) {
            $pretext = safe_row("skin, page, css", "txp_section", "name='default'") + $pretext;
        }

        $theme = $pretext['skin'];
    }

    if (has_handler('page.fetch')) {
        $page = callback_event('page.fetch', '', false, compact('name', 'theme'));
    } else {
        $page = safe_field('user_html', 'txp_page', "name = '".doSlash($name)."' AND skin = '".doSlash($theme)."'");
    }

    if ($page === false) {
        return false;
    }

    $trace->log("[Page: '$theme.$name']");

    return $page;
}

/**
 * Parses a page template.
 *
 * @param   string      $name  The template to parse
 * @param   string      $theme The public theme
 * @param   string      $page  Default content to parse
 * @return  string|bool The parsed page template, or FALSE on error
 * @since   4.6.0
 * @package TagParser
 * @example
 * echo parse_page('default');
 */

function parse_page($name, $theme, $page = '')
{
    global $pretext, $trace, $is_form;

    if (!$page) {
        $page = fetch_page($name, $theme);
    }

    if ($page !== false) {
        while ($pretext['secondpass'] <= (int)get_pref('secondpass', 1) && preg_match('@<(?:'.TXP_PATTERN.'):@', $page)) {
            $is_form = 1;
            $page = parse($page);
            // the function so nice, he ran it twice
            $pretext['secondpass']++;
            $trace->log('[ ~~~ end of pass '.$pretext['secondpass'].' ~~~ ]');
        }
    }

    return $page;
}

/**
 * Gets a HTML select field containing all categories, or sub-categories.
 *
 * @param   string $name Return specified parent category's sub-categories
 * @param   string $cat  The selected category option
 * @param   string $id   The HTML ID
 * @return  string|bool HTML select field or FALSE on error
 * @package Form
 */

function event_category_popup($name, $cat = '', $id = '', $atts = array())
{
    $rs = getTree('root', $name);

    if ($rs) {
        return treeSelectInput('category', $rs, $cat, $id, 0, $atts);
    }

    return false;
}

/**
 * Gets a category's title.
 *
 * @param  string $name The category
 * @param  string $type Category's type. Either "article", "file", "image" or "link"
 * @return string|bool The title or FALSE on error
 */

function fetch_category_title($name, $type = 'article')
{
    static $cattitles = array();
    global $thiscategory;

    if (isset($cattitles[$type][$name])) {
        return $cattitles[$type][$name];
    }

    if (!empty($thiscategory['title']) && $thiscategory['name'] == $name && $thiscategory['type'] == $type) {
        $cattitles[$type][$name] = $thiscategory['title'];

        return $thiscategory['title'];
    }

    $f = safe_field("title", 'txp_category', "name = '".doSlash($name)."' AND type = '".doSlash($type)."'");
    $cattitles[$type][$name] = $f;

    return $f;
}

/**
 * Gets a section's title.
 *
 * @param  string $name The section
 * @return string|bool The title or FALSE on error
 */

function fetch_section_title($name)
{
    static $sectitles = array();
    global $thissection, $txp_sections;

    // Try cache.
    if (isset($sectitles[$name])) {
        return $sectitles[$name];
    }

    if (!empty($thissection) && $thissection['name'] == $name) {
        return $thissection['title'];
    } elseif ($name == 'default' or empty($name)) {
        return '';
    } elseif (isset($txp_sections[$name])) {
        return $sectitles[$name] = $txp_sections[$name]['title'];
    }

    $f = safe_field("title", 'txp_section', "name = '".doSlash($name)."'");

    return $sectitles[$name] = $f;
}

/**
 * Updates an article's comment count.
 *
 * @param   int $id The article
 * @return  bool
 * @package Comment
 */

function update_comments_count($id)
{
    $id = assert_int($id);
    $thecount = safe_field("COUNT(*)", 'txp_discuss', "parentid = '".$id."' AND visible = ".VISIBLE);
    $thecount = assert_int($thecount);
    $updated = safe_update('textpattern', "comments_count = ".$thecount, "ID = '".$id."'");

    return ($updated) ? true : false;
}

/**
 * Recalculates and updates comment counts.
 *
 * @param   array $parentids List of articles to update
 * @package Comment
 */

function clean_comment_counts($parentids)
{
    $parentids = array_map('assert_int', $parentids);
    $parentids = array_filter($parentids);

    if ($parentids) {
        $rs = safe_rows_start("parentid, COUNT(*) AS thecount", 'txp_discuss', "parentid IN (".implode(',', $parentids).") AND visible = ".VISIBLE." GROUP BY parentid");

        if (!$rs) {
            return;
        }

        $updated = array();

        while ($a = nextRow($rs)) {
            safe_update('textpattern', "comments_count = ".$a['thecount'], "ID = ".$a['parentid']);
            $updated[] = $a['parentid'];
        }

        // We still need to update all those, that have zero comments left.
        $leftover = array_diff($parentids, $updated);

        if ($leftover) {
            safe_update('textpattern', "comments_count = 0", "ID IN (".implode(',', $leftover).")");
        }
    }
}

/**
 * Parses and formats comment message using Textile.
 *
 * @param   string $msg The comment message
 * @return  string HTML markup
 * @package Comment
 */

function markup_comment($msg)
{
    $textile = new \Textpattern\Textile\RestrictedParser();

    return $textile->parse($msg);
}

/**
 * Updates site's last modification date.
 *
 * When this action is performed, it will trigger a
 * 'site.update > {event}' callback event and pass
 * any record set that triggered the update, along
 * with the exact time the update was triggered.
 *
 * @param   $trigger Textpattern event or step that triggered the update
 * @param   $rs      Record set data at the time of update
 * @package Pref
 * @example
 * update_lastmod();
 */

function update_lastmod($trigger = '', $rs = array())
{
    $whenStamp = time();
    $whenDate = date('Y-m-d H:i:s', $whenStamp);

    safe_upsert('txp_prefs', "val = '$whenDate'", "name = 'lastmod'");
    callback_event('site.update', $trigger, 0, $rs, compact('whenStamp', 'whenDate'));
}

/**
 * Gets the site's last modification date.
 *
 * @param   int $unix_ts UNIX timestamp
 * @return  int UNIX timestamp
 * @package Pref
 */

function get_lastmod($unix_ts = null)
{
    if ($unix_ts === null) {
        $unix_ts = @strtotime(get_pref('lastmod'));
    }

    // Check for future articles that are now visible.
    if (txpinterface === 'public' && $max_article = safe_field("UNIX_TIMESTAMP(Posted)", 'textpattern', "Posted <= ".now('posted')." AND Status >= 4 ORDER BY Posted DESC LIMIT 1")) {
        $unix_ts = max($unix_ts, $max_article);
    }

    return $unix_ts;
}

/**
 * Sets headers.
 *
 * @param   array $headers    'name' => 'value'
 * @param   bool  $rewrite    If TRUE, rewrites existing headers
 */

function set_headers($headers = array('Content-Type' => 'text/html; charset=utf-8'), $rewrite = false)
{
    if (headers_sent()) {
        return;
    }

    $rewrite = (int)$rewrite;
    $out = $headers_low = array();

    if (($rewrite != 1 || in_array(true, $headers, true)) && $headers_list = headers_list()) {
        foreach ($headers_list as $header) {
            list($name, $value) = explode(':', $header, 2) + array(null, null);
            $headers_low[strtolower(trim($name))] = $value;
        }
    }

    foreach ($headers as $name => $header) {
        $name_low = strtolower(trim($name));

        if ((string)$header === '') {
            !$rewrite or header_remove($name && $name != 1 ? $name : null);
        } elseif ($header === true) {
            if ($name == '' || $name == 1) {
                $out = array_merge($out, $headers_low);
            } elseif (isset($headers_low[$name_low])) {
                $out[$name_low] = $headers_low[$name_low];
            }
        } elseif ($name == 1) {
            txp_status_header($header);
        } elseif ($rewrite == 1 || !isset($headers_low[$name_low])) {
            header($name ? $name.': '.$header : $header);
        } elseif ($rewrite) {
            $header = implode(', ', do_list_unique($headers_low[$name_low].','.$header));
            header($name ? $name.': '.$header : $header);
        }
    }

    return $out ? $out : null;
}

/**
 * Sends and handles a lastmod header.
 *
 * @param   int|null $unix_ts The last modification date as a UNIX timestamp
 * @param   bool     $exit    If TRUE, terminates the script
 * @return  array|null Array of sent HTTP status and the lastmod header, or NULL
 * @package Pref
 */

function handle_lastmod($unix_ts = null, $exit = true)
{
    // Disable caching when not in production
    if (get_pref('production_status') != 'live') {
        header('Cache-Control: no-cache, no-store, max-age=0');
    } elseif (get_pref('send_lastmod')) {
        $unix_ts = get_lastmod($unix_ts);

        // Make sure lastmod isn't in the future.
        $unix_ts = min($unix_ts, time());

        $last = safe_strftime('rfc822', $unix_ts, 1);
        header("Last-Modified: $last");

        $etag = base_convert($unix_ts, 10, 32);
        header('ETag: "' . $etag . '"');

        // Get timestamp from request caching headers
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $hims = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            $imsd = ($hims) ? strtotime($hims) : 0;
        } elseif (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $hinm = trim(trim($_SERVER['HTTP_IF_NONE_MATCH']), '"');
            $hinm_apache_gzip_workaround = explode('-gzip', $hinm);
            $hinm_apache_gzip_workaround = $hinm_apache_gzip_workaround[0];
            $inmd = ($hinm) ? base_convert($hinm_apache_gzip_workaround, 32, 10) : 0;
        }

        // Check request timestamps against the current timestamp
        if ((isset($imsd) && $imsd >= $unix_ts) ||
            (isset($inmd) && $inmd >= $unix_ts)) {
            log_hit('304');

            header('Content-Length: 0');

            txp_status_header('304 Not Modified');

            if ($exit) {
                exit();
            }

            return array('304', $last);
        }

        return array('200', $last);
    }
}

/**
 * Gets preferences as an array.
 *
 * Returns preference values from the database as an array. Shouldn't be used to
 * retrieve selected preferences, see get_pref() instead.
 *
 * By default only the global preferences are returned.
 * If the optional user name parameter is supplied, the private preferences
 * for that user are returned.
 *
 * @param   string $user User name.
 * @return  array
 * @package Pref
 * @access  private
 * @see     get_pref()
 */

function get_prefs($user = '')
{
    $out = array();
    $user = implode(',', (array) quote_list($user));

    $r = safe_rows_start("name, val", 'txp_prefs', "user_name IN (".$user.") ORDER BY FIELD(user_name, ".$user.")");

    if ($r) {
        while ($a = nextRow($r)) {
            $out[$a['name']] = $a['val'];
        }
    }

    return $out;
}

/**
 * Creates or updates a preference.
 *
 * @param   string $name       The name
 * @param   string $val        The value
 * @param   string $event      The section the preference appears in
 * @param   int    $type       Either PREF_CORE, PREF_PLUGIN, PREF_HIDDEN
 * @param   string $html       The HTML control type the field uses. Can take a custom function name
 * @param   int    $position   Used to sort the field on the Preferences panel
 * @param   bool   $is_private If PREF_PRIVATE, is created as a user pref
 * @return  bool FALSE on error
 * @package Pref
 * @example
 * if (set_pref('myPref', 'value'))
 * {
 *     echo "'myPref' created or updated.";
 * }
 */

function set_pref($name, $val, $event = 'publish', $type = PREF_CORE, $html = 'text_input', $position = 0, $is_private = PREF_GLOBAL)
{
    global $prefs;

    $prefs[$name] = $val;
    $user_name = null;

    if ($is_private == PREF_PRIVATE) {
        $user_name = PREF_PRIVATE;
    }

    if (pref_exists($name, $user_name)) {
        return update_pref($name, $val, null, null, null, null, $user_name);
    }

    return create_pref($name, $val, $event, $type, $html, $position, $user_name);
}

/**
 * Gets a preference string.
 *
 * Prefers global system-wide preferences over a user's private preferences.
 *
 * @param   string $thing   The named variable
 * @param   mixed  $default Used as a replacement if named pref isn't found
 * @param   bool   $from_db If TRUE checks database opposed $prefs variable in memory
 * @return  string Preference value or $default
 * @package Pref
 * @example
 * if (get_pref('enable_xmlrpc_server'))
 * {
 *     echo "XML-RPC server is enabled.";
 * }
 */

function get_pref($thing, $default = '', $from_db = false)
{
    global $prefs, $txp_user;

    if ($from_db) {
        $name = doSlash($thing);
        $user_name = doSlash($txp_user);

        $field = safe_field(
            "val",
            'txp_prefs',
            "name = '$name' AND (user_name = '' OR user_name = '$user_name') ORDER BY user_name LIMIT 1"
        );

        if ($field !== false) {
            $prefs[$thing] = $field;
        }
    }

    if (isset($prefs[$thing])) {
        return $prefs[$thing];
    }

    return $default;
}

/**
 * Removes a preference string.
 *
 * Removes preference strings based on the given arguments. Use NULL to omit an argument.
 *
 * @param   string|null      $name      The preference string name
 * @param   string|null      $event     The preference event
 * @param   string|null|bool $user_name The owner. If PREF_PRIVATE, the current user
 * @return  bool TRUE on success
 * @since   4.6.0
 * @package Pref
 * @example
 * if (remove_pref(null, 'myEvent'))
 * {
 *     echo "Removed all preferences from 'myEvent'.";
 * }
 */

function remove_pref($name = null, $event = null, $user_name = null)
{
    global $txp_user;

    $sql = array();

    if ($user_name === PREF_PRIVATE) {
        if (!$txp_user) {
            return false;
        }

        $user_name = $txp_user;
    }

    if ($user_name !== null) {
        $sql[] = "user_name = '".doSlash((string) $user_name)."'";
    }

    if ($event !== null) {
        $sql[] = "event = '".doSlash($event)."'";
    }

    if ($name !== null) {
        $sql[] = "name = '".doSlash($name)."'";
    }

    if ($sql) {
        return safe_delete('txp_prefs', join(" AND ", $sql));
    }

    return false;
}

/**
 * Checks if a preference string exists.
 *
 * Searches for matching preference strings based on the given arguments.
 *
 * The $user_name argument can be used to limit the search to a specific user,
 * or to global and private strings. If NULL, matches are searched from both
 * private and global strings.
 *
 * @param   string           $name      The preference string name
 * @param   string|null|bool $user_name Either the username, NULL, PREF_PRIVATE or PREF_GLOBAL
 * @return  bool TRUE if the string exists, or FALSE on error
 * @since   4.6.0
 * @package Pref
 * @example
 * if (pref_exists('myPref'))
 * {
 *     echo "'myPref' exists.";
 * }
 */

function pref_exists($name, $user_name = null)
{
    global $txp_user;

    $sql = array();
    $sql[] = "name = '".doSlash($name)."'";

    if ($user_name === PREF_PRIVATE) {
        if (!$txp_user) {
            return false;
        }

        $user_name = $txp_user;
    }

    if ($user_name !== null) {
        $sql[] = "user_name = '".doSlash((string) $user_name)."'";
    }

    if (safe_row("name", 'txp_prefs', join(" AND ", $sql))) {
        return true;
    }

    return false;
}

/**
 * Creates a preference string.
 *
 * When a string is created, will trigger a 'preference.create > done' callback event.
 *
 * @param   string      $name       The name
 * @param   string      $val        The value
 * @param   string      $event      The section the preference appears in
 * @param   int         $type       Either PREF_CORE, PREF_PLUGIN, PREF_HIDDEN
 * @param   string      $html       The HTML control type the field uses. Can take a custom function name
 * @param   int         $position   Used to sort the field on the Preferences panel
 * @param   string|bool $user_name  The user name, PREF_GLOBAL or PREF_PRIVATE
 * @return  bool TRUE if the string exists, FALSE on error
 * @since   4.6.0
 * @package Pref
 * @example
 * if (create_pref('myPref', 'value', 'site', PREF_PLUGIN, 'text_input', 25))
 * {
 *     echo "'myPref' created.";
 * }
 */

function create_pref($name, $val, $event = 'publish', $type = PREF_CORE, $html = 'text_input', $position = 0, $user_name = PREF_GLOBAL)
{
    global $txp_user;

    if ($user_name === PREF_PRIVATE) {
        if (!$txp_user) {
            return false;
        }

        $user_name = $txp_user;
    }

    if (pref_exists($name, $user_name)) {
        return true;
    }

    $val = is_scalar($val) ? (string)$val : json_encode($val, TEXTPATTERN_JSON);

    if (
        safe_insert(
            'txp_prefs',
            "name = '".doSlash($name)."',
            val = '".doSlash($val)."',
            event = '".doSlash($event)."',
            html = '".doSlash($html)."',
            type = ".intval($type).",
            position = ".intval($position).",
            user_name = '".doSlash((string) $user_name)."'"
        ) === false
    ) {
        return false;
    }

    callback_event('preference.create', 'done', 0, compact('name', 'val', 'event', 'type', 'html', 'position', 'user_name'));

    return true;
}

/**
 * Updates a preference string.
 *
 * Updates a preference string's properties. The $name and $user_name
 * arguments are used for selecting the updated string, and rest of the
 * arguments take the new values. Use NULL to omit an argument.
 *
 * When a string is updated, will trigger a 'preference.update > done' callback event.
 *
 * @param   string           $name       The update preference string's name
 * @param   string|null      $val        The value
 * @param   string|null      $event      The section the preference appears in
 * @param   int|null         $type       Either PREF_CORE, PREF_PLUGIN, PREF_HIDDEN
 * @param   string|null      $html       The HTML control type the field uses. Can take a custom function name
 * @param   int|null         $position   Used to sort the field on the Preferences panel
 * @param   string|bool|null $user_name  The updated string's owner, PREF_GLOBAL or PREF_PRIVATE
 * @return  bool             FALSE on error
 * @since   4.6.0
 * @package Pref
 * @example
 * if (update_pref('myPref', 'New value.'))
 * {
 *     echo "Updated 'myPref' value.";
 * }
 */

function update_pref($name, $val = null, $event = null, $type = null, $html = null, $position = null, $user_name = PREF_GLOBAL)
{
    global $txp_user;

    $where = $set = array();
    $where[] = "name = '".doSlash($name)."'";

    if ($user_name === PREF_PRIVATE) {
        if (!$txp_user) {
            return false;
        }

        $user_name = $txp_user;
    }

    if ($user_name !== null) {
        $where[] = "user_name = '".doSlash((string) $user_name)."'";
    }

    if (isset($val)) {
        $val = is_scalar($val) ? (string)$val : json_encode($val, TEXTPATTERN_JSON);
    }

    foreach (array('val', 'event', 'type', 'html', 'position') as $field) {
        if ($$field !== null) {
            $set[] = $field." = '".doSlash($$field)."'";
        }
    }

    if ($set && safe_update('txp_prefs', join(', ', $set), join(" AND ", $where))) {
        callback_event('preference.update', 'done', 0, compact('name', 'val', 'event', 'type', 'html', 'position', 'user_name'));

        return true;
    }

    return false;
}

/**
 * Renames a preference string.
 *
 * When a string is renamed, will trigger a 'preference.rename > done' callback event.
 *
 * @param   string $newname   The new name
 * @param   string $name      The current name
 * @param   string $user_name Either the username, PREF_GLOBAL or PREF_PRIVATE
 * @return  bool FALSE on error
 * @since   4.6.0
 * @package Pref
 * @example
 * if (rename_pref('mynewPref', 'myPref'))
 * {
 *     echo "Renamed 'myPref' to 'mynewPref'.";
 * }
 */

function rename_pref($newname, $name, $user_name = null)
{
    global $txp_user;

    $where = array();
    $where[] = "name = '".doSlash($name)."'";

    if ($user_name === PREF_PRIVATE) {
        if (!$txp_user) {
            return false;
        }

        $user_name = $txp_user;
    }

    if ($user_name !== null) {
        $where[] = "user_name = '".doSlash((string) $user_name)."'";
    }

    if (safe_update('txp_prefs', "name = '".doSlash($newname)."'", join(" AND ", $where))) {
        callback_event('preference.rename', 'done', 0, compact('newname', 'name', 'user_name'));

        return true;
    }

    return false;
}

/**
 * Gets a list of custom fields.
 *
 * @return  array
 * @package CustomField
 */

function getCustomFields()
{
    global $prefs;
    static $out = null;

    // Have cache?
    if (!is_array($out)) {
        $cfs = preg_grep('/^custom_\d+_set/', array_keys($prefs));
        $out = array();

        foreach ($cfs as $name) {
            preg_match('/(\d+)/', $name, $match);

            if ($prefs[$name] !== '') {
                $out[$match[1]] = strtolower($prefs[$name]);
            }
        }

        ksort($out, SORT_NUMERIC);
    }

    return $out;
}

/**
 * Build a query qualifier to filter non-matching custom fields from the
 * result set.
 *
 * @param   array $custom An array of 'custom_field_name' => field_number tuples
 * @param   array $pairs  Filter criteria: An array of 'name' => value tuples
 * @return  bool|string An SQL qualifier for a query's 'WHERE' part
 * @package CustomField
 */

function buildCustomSql($custom, $pairs, $exclude = array())
{
    if ($pairs) {
        foreach ($pairs as $k => $val) {
            $no = array_search($k, $custom);

            if ($no !== false) {
                $not = ($exclude === true || isset($exclude[$k])) ? 'NOT ' : '';
                $field = is_numeric($no) ? "custom_{$no}" : $no;

                if ($val === true) {
                    $out[] = "({$not}{$field} != '')";
                } else {
                    $val = doSlash($val);
                    $parts = array();

                    foreach ((array)$val as $v) {
                        list($from, $to) = explode('%%', $v, 2) + array(null, null);

                        if (!isset($to)) {
                            $parts[] = "{$not}{$field} LIKE '$from'";
                        } elseif ($from !== '') {
                            $parts[] = $to === '' ? "{$not}{$field} >= '$from'" :  "{$not}{$field} BETWEEN '$from' AND '$to'";
                        } elseif ($to !== '') {
                            $parts[] = "{$not}{$field} <= '$to'";
                        }
                    }

                    if ($parts) {
                        $out[] = '('.join($not ? ' AND ' : ' OR ', $parts).')';
                    }
                }
            }
        }
    }

    return !empty($out) ? ' AND '.join(' AND ', $out).' ' : false;
}

/**
 * Build a query qualifier to filter time fields from the
 * result set.
 *
 * @param   string $month A starting time point
 * @param   string $time  A time offset
 * @param   string $field The field to filter
 * @return  string An SQL qualifier for a query's 'WHERE' part
 */

function buildTimeSql($month, $time, $field = 'Posted')
{
    $safe_field = '`'.doSlash($field).'`';
    $timeq = '1';

    if ($month === 'past' || $month === 'any' || $month === 'future') {
        if ($month === 'past') {
            $timeq = "$safe_field <= ".now($field);
        } elseif ($month === 'future') {
            $timeq = "$safe_field > ".now($field);
        }
    } elseif ($time === 'past' || $time === 'any' || $time === 'future') {
        if ($time === 'past') {
            $timeq = "$safe_field <= ".now($field);
        } elseif ($time === 'future') {
            $timeq = "$safe_field > ".now($field);
        }

        if ($month) {
            $offset = date('P', strtotime($month));
            $dateClause = ($offset ? "CONVERT_TZ($safe_field, @@session.time_zone, '$offset')" : $safe_field)." LIKE '".doSlash($month)."%'";
            $timeq .= " AND $dateClause";
        }
    } elseif (strpos($time, '%') !== false) {
        $start = $month ? strtotime($month) : time() or $start = time();
        $offset = date('P', $start);
        $timeq = ($offset ? "CONVERT_TZ($safe_field, @@session.time_zone, '$offset')" : $safe_field)." LIKE '".doSlash(safe_strftime($time, $start))."%'";
    } else {
        $start = $month ? strtotime($month) : false;

        if ($start === false) {
            $from = $month ? "'".doSlash($month)."'" : now($field);
            $start = time();
        } else {
            $from = "FROM_UNIXTIME($start)";
        }

        if ($time === 'since') {
            $timeq = "$safe_field > $from";
        } elseif ($time === 'until') {
            $timeq = "$safe_field <= $from";
        } else {
            $stop = strtotime($time, $start) or $stop = time();

            if ($start > $stop) {
                list($start, $stop) = array($stop, $start);
            }

            $timeq = ($start == $stop ?
                "$safe_field = FROM_UNIXTIME($start)" :
                "$safe_field BETWEEN FROM_UNIXTIME($start) AND FROM_UNIXTIME($stop)"
            );
        }
    }

    return $timeq;
}

/**
 * Sends a HTTP status header.
 *
 * @param   string $status The HTTP status code
 * @package Network
 * @example
 * txp_status_header('403 Forbidden');
 */

function txp_status_header($status = '200 OK')
{
    if (IS_FASTCGI) {
        header("Status: $status");
    } elseif (serverSet('SERVER_PROTOCOL') == 'HTTP/1.0') {
        header("HTTP/1.0 $status");
    } else {
        header("HTTP/1.1 $status");
    }
}

/**
 * Terminates normal page rendition and outputs an error page.
 *
 * @param   string|array $msg    The error message
 * @param   string       $status HTTP status code
 * @param   string       $url    Redirects to the specified URL. Can be used with $status of 301, 302 and 307
 * @package Tag
 */

function txp_die($msg, $status = '503', $url = '')
{
    global $connected, $txp_error_message, $txp_error_status, $txp_error_code, $pretext, $production_status, $trace;

    // Make it possible to call this function as a tag, e.g. in an article
    // <txp:txp_die status="410" />.
    if (is_array($msg)) {
        extract(lAtts(array(
            'msg'    => '',
            'status' => '503',
            'url'    => '',
        ), $msg));
    }

    // Intentionally incomplete - just the ones we're likely to use.
    $codes = array(
        '200' => 'OK',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '307' => 'Temporary Redirect',
        '308' => 'Permanent Redirect',
        '401' => 'Unauthorized',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '410' => 'Gone',
        '414' => 'Request-URI Too Long',
        '451' => 'Unavailable For Legal Reasons',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '503' => 'Service Unavailable'
    );

    if ($status) {
        if (isset($codes[strval($status)])) {
            $status = strval($status).' '.$codes[$status];
        }

        txp_status_header($status);
    }

    $code = (int) $status;

    callback_event('txp_die', $code, 0, $url);

    // Redirect with status.
    if ($url && in_array($code, array(301, 302, 303, 307, 308))) {
        ob_end_clean();
        header("Location: $url", true, $code);
        die('<html><head><meta http-equiv="refresh" content="0;URL='.txpspecialchars($url).'"></head><body><p>Document has <a href="'.txpspecialchars($url).'">moved here</a>.</p></body></html>');
    }

    $out = false;
    $skin = empty($pretext['skin']) ? null : $pretext['skin'];

    if ($connected && @txpinterface == 'public') {
        $out = fetch_page("error_{$code}", $skin) or $out = fetch_page('error_default', $skin);
    }

    if ($out === false) {
        $out = <<<eod
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="utf-8">
   <meta name="robots" content="noindex">
   <title>Textpattern Error: <txp:error_status /></title>
</head>
<body>
    <p><txp:error_message /></p>
</body>
</html>
eod;
    }

    header("Content-Type: text/html; charset=utf-8");
    $debug = $production_status === 'live' ?
        '' :
        $trace->summary().($production_status === 'debug' ? $trace->result() : '');

    if (is_callable('parse')) {
        $txp_error_message = $msg;
        $txp_error_status = $status;
        $txp_error_code = $code;
        set_error_handler("tagErrorHandler");
        die(parse($out).$debug);
    } else {
        $out = preg_replace(
            array('@<txp:error_status[^>]*/>@', '@<txp:error_message[^>]*/>@'),
            array($status, $msg),
            $out
        );

        die($out.$debug);
    }
}

/**
 * Get field => alias array.
 *
 * @param   string $match
 * @return  array()
 * @since   4.8.0
 * @package TagParser
 */

function parse_qs($match, $sep='=')
{
    $pairs = array();

    foreach (do_list_unique($match) as $chunk) {
        $name = strtok($chunk, $sep);
        $alias = strtok($sep);
        $pairs[strtolower($name)] = $alias;
    };

    return $pairs;
}

/**
 * Gets a URL-encoded and HTML entity-escaped query string for a URL.
 *
 * Builds a HTTP query string from an associative array.
 *
 * @param   array $q The parameters for the query
 * @return  string The query, including starting "?".
 * @package URL
 * @example
 * echo join_qs(array('param1' => 'value1', 'param2' => 'value2'));
 */

function join_qs($q, $sep = '&amp;')
{
    $qs = array();
    $sql = $sep !== '&amp;';

    foreach ($q as $k => $v) {
        if (is_array($v)) {
            $v = join(',', $v);
        }

        if ($k && (string) $v !== '') {
            $qs[$k] = $sql ? "$k = $v" : urlencode($k).'='.urlencode($v);
        }
    }

    if (!isset($sep)) {
        return $qs;
    }

    $str = join($sep, $qs);

    return  $str ? ($sql ? '' : '?').$str : '';
}

/**
 * Builds a HTML attribute list from an array.
 *
 * Takes an array of raw HTML attributes, and returns a properly
 * sanitised HTML attribute string for use in a HTML tag.
 *
 * Internally handles HTML boolean attributes, array lists and query strings.
 * If an attributes value is set as a boolean, the attribute is considered
 * as one too. If a value is NULL, it's omitted and the attribute is added
 * without a value. An array value is converted to a space-separated list,
 * or for 'href' and 'src' to a URL encoded query string.
 *
 * @param   array|string  $atts  HTML attributes
 * @param   int           $flags TEXTPATTERN_STRIP_EMPTY_STRING
 * @return  string HTML attribute list
 * @since   4.6.0
 * @package HTML
 * @example
 * echo join_atts(array('class' => 'myClass', 'disabled' => true));
 */

function join_atts($atts, $flags = TEXTPATTERN_STRIP_EMPTY_STRING, $glue = ' ')
{
    if (!is_array($atts)) {
        return $atts ? ' '.trim($atts) : '';
    }

    $list = '';
    $txp = $flags & TEXTPATTERN_STRIP_TXP;

    foreach ($atts as $name => $value) {
        if (($flags & TEXTPATTERN_STRIP_EMPTY && !$value) || ($value === false) || ($txp && $value === null)) {
            continue;
        } elseif ($value === null || $txp && $value === true) {
            $list .= ' '.$name;
            continue;
        } elseif (is_array($value)) {
            if ($name == 'href' || $name == 'src') {
                $value = join_qs($value);
            } else {
                $value = txpspecialchars(join($glue, $value));
            }
        } elseif ($name != 'href' && $name != 'src') {
            $value = txpspecialchars($value === true ? $name : $value);
        } else {
            $value = txpspecialchars(str_replace('&amp;', '&', $value));
        }

        if (!($flags & TEXTPATTERN_STRIP_EMPTY_STRING && $value === '')) {
            $list .= ' '.$name.'="'.$value.'"';
        }
    }

    return $list;
}

/**
 * Builds a page URL from an array of parameters.
 *
 * The $inherit can be used to add parameters to an existing url, e.g:
 * pagelinkurl(array('pg' => 2), $pretext).
 *
 * Cannot be used to link to an article. See permlinkurl() and permlinkurl_id() instead.
 *
 * @param   array $parts   The parts used to construct the URL
 * @param   array $inherit Can be used to add parameters to an existing url
 * @return  string
 * @see     permlinkurl()
 * @see     permlinkurl_id()
 * @package URL
 */

function pagelinkurl($parts, $inherit = array(), $url_mode = null)
{
    global $permlink_mode, $prefs, $txp_context, $txp_sections;

    // Link to an article.
    if (!empty($parts['id'])) {
        return permlinkurl_id($parts['id']);
    }

    $hu = isset($prefs['url_base']) ? $prefs['url_base'] : hu;
    $keys = $parts;
    !is_array($inherit) or $keys += $inherit;
    empty($txp_context) or $keys += $txp_context;
    unset($keys['id']);

    if (isset($prefs['custom_url_func'])
        && is_callable($prefs['custom_url_func'])
        && ($url = call_user_func($prefs['custom_url_func'], $keys, PAGELINKURL)) !== false) {
        return $url;
    }

    if (isset($keys['s'])) {
        if (!isset($url_mode) && isset($txp_sections[$keys['s']])) {
            $url_mode = $txp_sections[$keys['s']]['permlink_mode'];
        }

        if ($keys['s'] == 'default') {
            unset($keys['s']);
        }
    }

    if (empty($url_mode)) {
        $url_mode = $permlink_mode;
    }

    // 'article' context is implicit, no need to add it to the page URL.
    if (isset($keys['context']) && $keys['context'] == 'article') {
        unset($keys['context']);
    }

    $numkeys = array();

    foreach ($keys as $key => $v) {
        if (is_numeric($key)) {
            $numkeys[$key] = urlencode($v).'/';
            unset($keys[$key]);
        }
    }

    if ($url_mode == 'messy') {
        $url = 'index.php';
    } else {
        // All clean URL modes use the same schemes for list pages.
        $url = '';

        if (!empty($keys['rss'])) {
            $url = 'rss/';
            unset($keys['rss']);
        } elseif (!empty($keys['atom'])) {
            $url = 'atom/';
            unset($keys['atom']);
        } elseif (!empty($keys['s'])) {
            $url = urlencode($keys['s']).'/';
            unset($keys['s']);
            if (!empty($keys['c']) && ($url_mode == 'section_category_title' || $url_mode == 'breadcrumb_title')) {
                $catpath = $url_mode == 'breadcrumb_title' ?
                    array_column(getRootPath($keys['c'], empty($keys['context']) ? 'article' : $keys['context']), 'name') :
                    array($keys['c']);
                $url .= implode('/', array_map('urlencode', array_reverse($catpath))).'/';
                unset($keys['c']);
            } elseif (!empty($keys['month']) && $url_mode == 'year_month_day_title' && is_date($keys['month'])) {
                $url .= implode('/', explode('-', urlencode($keys['month']))).'/';
                unset($keys['month']);
            }
        } elseif (!empty($keys['author']) && $url_mode != 'year_month_day_title') {
            $ct = empty($keys['context']) ? '' : strtolower(urlencode(gTxt($keys['context'].'_context'))).'/';
            $url = strtolower(urlencode(gTxt('author'))).'/'.$ct.urlencode($keys['author']).'/';
            unset($keys['author'], $keys['context']);
        } elseif (!empty($keys['c']) && $url_mode != 'year_month_day_title') {
            $ct = empty($keys['context']) ? '' : strtolower(urlencode(gTxt($keys['context'].'_context'))).'/';
            $url = strtolower(urlencode(gTxt('category'))).'/'.$ct;
            $catpath = $url_mode == 'breadcrumb_title' ?
                array_column(getRootPath($keys['c'], empty($keys['context']) ? 'article' : $keys['context']), 'name') :
                array($keys['c']);
            $url .= implode('/', array_map('urlencode', array_reverse($catpath))).'/';
            unset($keys['c'], $keys['context']);
        } elseif (!empty($keys['month']) && is_date($keys['month'])) {
            $url = implode('/', explode('-', urlencode($keys['month']))).'/';
            unset($keys['month']);
        }
    }

    if (!empty($keys['context'])) {
        $keys['context'] = gTxt($keys['context'].'_context');
    }

    return $hu.(empty($prefs['no_trailing_slash']) ? $url : rtrim($url, '/')).join_qs($keys);
}

/**
 * Gets a URL for the given article.
 *
 * If you need to generate a list of article URLs from already fetched table
 * rows, consider using permlinkurl() over this due to performance benefits.
 *
 * @param   int $id The article ID
 * @return  string The URL
 * @see     permlinkurl()
 * @package URL
 * @example
 * echo permlinkurl_id(12);
 */

function permlinkurl_id($id)
{
    global $permlinks, $thisarticle;

    $id = (int) $id;

    if (isset($permlinks[$id])) {
        return permlinkurl(array('id' => $id));
    }

    if (isset($thisarticle['thisid']) && $thisarticle['thisid'] == $id) {
        return permlinkurl($thisarticle);
    }

    $rs = empty($id) ? array() : safe_row(
        "ID AS thisid, Section, Title, url_title, Category1, Category2, UNIX_TIMESTAMP(Posted) AS posted, UNIX_TIMESTAMP(Expires) AS expires",
        'textpattern',
        "ID = $id"
    );

    return permlinkurl($rs);
}

/**
 * Generates an article URL from the given data array.
 *
 * @param   array $article_array An array consisting of keys 'thisid', 'section', 'title', 'url_title', 'posted', 'expires'
 * @return  string The URL
 * @package URL
 * @see     permlinkurl_id()
 * @example
 * echo permlinkurl_id(array(
 *     'thisid'    => 12,
 *     'section'   => 'blog',
 *     'url_title' => 'my-title',
 *     'posted'    => 1345414041,
 *     'expires'   => 1345444077
 * ));
 */

function permlinkurl($article_array, $hu = null)
{
    global $permlink_mode, $prefs, $permlinks, $txp_sections;
    static $internals = array('id', 's', 'context', 'pg', 'p'), $now = null,
        $fields = array(
            'thisid'    => null,
            'id'        => null,
            'title'     => null,
            'url_title' => null,
            'section'   => null,
            'category1' => null,
            'category2' => null,
            'posted'    => null,
            'uposted'   => null,
            'expires'   => null,
            'uexpires'  => null,
        );

    if (isset($prefs['custom_url_func'])
        and is_callable($prefs['custom_url_func'])
        and ($url = call_user_func($prefs['custom_url_func'], $article_array, PERMLINKURL)) !== false) {
        return $url;
    }

    if (empty($article_array)) {
        return false;
    }

    extract(array_intersect_key(array_change_key_case($article_array, CASE_LOWER), $fields) + $fields);
    isset($hu) or $hu = isset($prefs['url_base']) ? $prefs['url_base'] : hu;

    if (empty($thisid)) {
        $thisid = $id;
    }

    $thisid = (int) $thisid;
    $keys = get_context(null);

    foreach ($internals as $key) {
        unset($keys[$key]);
    }

    if (isset($permlinks[$thisid])) {
        return $hu.($permlinks[$thisid] === true ?
            'index.php'.join_qs(array('id' => $thisid) + $keys) :
            $permlinks[$thisid].join_qs($keys)
        );
    }

    if (!isset($now)) {
        $now = date('Y-m-d H:i:s');
    }

    if (empty($prefs['publish_expired_articles']) &&
        !empty($expires) &&
        $prefs['production_status'] != 'live' &&
        txpinterface == 'public' &&
        (is_numeric($expires) ? $expires < time()
            : (isset($uexpires) ? $uexpires < time()
            : $expires < $now)
        )
    ) {
        trigger_error(gTxt('permlink_to_expired_article', array('{id}' => $thisid)), E_USER_NOTICE);
    }

    if (empty($section)) {
        $url_mode = 'messy';
    } elseif (isset($txp_sections[$section])) {
        $url_mode = empty($txp_sections[$section]['permlink_mode']) ? $permlink_mode : $txp_sections[$section]['permlink_mode'];
    } else {
        $url_mode = $permlink_mode;
    }

    if (empty($url_title) && !in_array($url_mode, array('section_id_title', 'id_title'))) {
        $url_mode = 'messy';
    }

    $section = urlencode($section);
    $url_title = urlencode($url_title);

    switch ($url_mode) {
        case 'section_id_title':
            if ($url_title && $prefs['attach_titles_to_permalinks']) {
                $out = "$section/$thisid/$url_title";
            } else {
                $out = "$section/$thisid";
            }
            break;
        case 'year_month_day_title':
            list($y, $m, $d) = explode("-", date("Y-m-d", isset($uposted) ? $uposted : $posted));
            $out =  "$y/$m/$d/$url_title";
            break;
        case 'id_title':
            if ($url_title && $prefs['attach_titles_to_permalinks']) {
                $out = "$thisid/$url_title";
            } else {
                $out = "$thisid";
            }
            break;
        case 'section_title':
            $out = "$section/$url_title";
            break;
        case 'section_category_title':
            $out = $section.'/'.
                (empty($category1) ? '' : urlencode($category1).'/').
                (empty($category2) ? '' : urlencode($category2).'/').$url_title;
            break;
        case 'breadcrumb_title':
            $out = $section.'/';
            if (empty($category1)) {
                if (!empty($category2)) {
                    $path = array_reverse(array_column(getRootPath($category2), 'name'));
                    $out .= implode('/', array_map('urlencode', $path)).'/';
                }
            } elseif (empty($category2)) {
                $path = array_reverse(array_column(getRootPath($category1), 'name'));
                $out .= implode('/', array_map('urlencode', $path)).'/';
            } else {
                $c2_path = array_reverse(array_column(getRootPath($category2), 'name'));
                if (in_array($category1, $c2_path)) {
                    $out .= implode('/', array_map('urlencode', $c2_path)).'/';
                } else {
                    $c1_path = array_reverse(array_column(getRootPath($category1), 'name'));
                    if (in_array($category2, $c1_path)) {
                        $out .= implode('/', array_map('urlencode', $c1_path)).'/';
                    } else {
                        $c0_path = array_intersect($c1_path, $c2_path);
                        $out .= ($c0_path ? implode('/', array_map('urlencode', $c0_path)).'/' : '').
                            urlencode($category1).'/'.urlencode($category2).'/';
                    }
                }
            }
            $out .= $url_title;
            break;
        case 'title_only':
            $out = $url_title;
            break;
        case 'messy':
            $out = "index.php";
            $keys['id'] = $thisid;
            break;
    }

    $permlinks[$thisid] = $url_mode == 'messy' ? true : $out;

    return $hu.$out.join_qs($keys);
}

/**
 * Gets a file download URL.
 *
 * @param   int    $id       The ID
 * @param   string $filename The filename
 * @return  string
 * @package File
 */

function filedownloadurl($id, $filename = '')
{
    global $permlink_mode;

    if ($permlink_mode == 'messy') {
        return hu.'index.php'.join_qs(array(
            's'  => 'file_download',
            'id' => (int) $id,
        ));
    }

    if ($filename) {
        $filename = '/'.urlencode($filename);

        // FIXME: work around yet another mod_deflate problem (double compression)
        // https://blogs.msdn.microsoft.com/wndp/2006/08/21/content-encoding-content-type/
        if (preg_match('/gz$/i', $filename)) {
            $filename .= a;
        }
    }

    return hu.'file_download/'.intval($id).$filename;
}

/**
 * Gets an image's absolute URL.
 *
 * @param   int    $id        The image
 * @param   string $ext       The file extension
 * @param   bool   $thumbnail If TRUE returns a URL to the thumbnail
 * @return  string
 * @package Image
 */

function imagesrcurl($id, $ext, $thumbnail = false)
{
    global $img_dir;
    $thumbnail = $thumbnail ? 't' : '';

    return ihu.$img_dir.'/'.$id.$thumbnail.$ext;
}

/**
 * Checks if a value exists in a list.
 *
 * @param  string $val   The searched value
 * @param  string $list  The value list
 * @param  string $delim The list boundary
 * @return bool Returns TRUE if $val is found, FALSE otherwise
 * @example
 * if (in_list('red', 'blue, green, red, yellow'))
 * {
 *     echo "'red' found from the list.";
 * }
 */

function in_list($val, $list, $delim = ',')
{
    return in_array((string) $val, do_list($list, $delim), true);
}

/**
 * Split a string by string.
 *
 * Trims the created values of whitespace.
 *
 * @param  array|string $list  The string
 * @param  string       $delim The boundary
 * @return array
 * @example
 * print_r(
 *     do_list('value1, value2, value3')
 * );
 */

function do_list($list, $delim = ',')
{
    if (!isset($list)) {
        return array();
    } elseif (is_array($list)) {
        return array_map('trim', $list);
    }

    if (is_array($delim)) {
        list($delim, $range) = $delim + array(null, null);
    }

    $array = explode($delim, $list);

    if (isset($range) && strpos($list, $range) !== false) {
        $pattern = '/^\s*(\w|[-+]?\d+)\s*'.preg_quote($range, '/').'\s*(\w|[-+]?\d+)\s*$/';
        $out = array();

        foreach ($array as $item) {
            if (!preg_match($pattern, $item, $match)) {
                $out[] = trim($item);
            } else {
                list($m, $start, $end) = $match;
                foreach(range($start, $end) as $v) {
                    $out[] = $v;
                }
            }
        }
    }

    return isset($out) ? $out : array_map('trim', $array);
}

/**
 * Split a string by string, returning only unique results.
 *
 * Trims unique values of whitespace. Flags permit exclusion of empty strings.
 *
 * @param  string $list  The string
 * @param  string $delim The boundary
 * @param  int    $flags TEXTPATTERN_STRIP_NONE | TEXTPATTERN_STRIP_EMPTY | TEXTPATTERN_STRIP_EMPTY_STRING
 * @return array
 * @example
 * print_r(
 *     do_list_unique('value1, value2, value3')
 * );
 */

function do_list_unique($list, $delim = ',', $flags = TEXTPATTERN_STRIP_EMPTY_STRING)
{
    $out = array_unique(do_list($list, $delim));

    if ($flags & TEXTPATTERN_STRIP_EMPTY) {
        $out = array_filter($out);
    } elseif ($flags & TEXTPATTERN_STRIP_EMPTY_STRING) {
        $out = array_filter($out, function ($v) {return $v !== '';});
    }

    return $out;
}

/**
 * Wraps a string in single quotes.
 *
 * @param  string $val The input string
 * @return string
 */

function doQuote($val)
{
    return "'$val'";
}

/**
 * Escapes special characters for use in an SQL statement and wraps the value
 * in quote.
 *
 * Useful for creating an array/string of values for use in an SQL statement.
 *
 * @param   string|array $in The input value
 * @param   string|null  $separator The separator
 * @return  mixed
 * @package DB
 * @example
 * if ($r = safe_row('name', 'myTable', 'type in(' . quote_list(array('value1', 'value2'), ',') . ')')
 * {
 *     echo "Found '{$r['name']}'.";
 * }
 */

function quote_list($in, $separator = null)
{
    $out = doArray(doSlash($in), 'doQuote');

    return isset($separator) ? implode($separator, $out) : $out;
}

/**
 * Adds a line to the tag trace.
 *
 * @param   string $msg             The message
 * @param   int    $tracelevel_diff Change trace level
 * @deprecated in 4.6.0
 * @package Debug
 */

function trace_add($msg, $level = 0, $dummy = null)
{
    global $trace;

    if ((int) $level > 0) {
        $trace->start($msg);
    } elseif ((int) $level < 0) {
        $trace->stop();
    } else {
        $trace->log($msg);
    }

    // TODO: Uncomment this to trigger deprecated warning in a version (or two).
    // Due to the radical changes under the hood, plugin authors will probably
    // support dual 4.5/4.6 plugins for the short term. Deprecating this
    // immediately causes unnecessary pain for developers.
//    trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'class Trace')), E_USER_NOTICE);
}

/**
 * Push current article on the end of data stack.
 *
 * Populates $stack_article global with the current $thisarticle.
 */

function article_push()
{
    global $thisarticle, $stack_article;
    $stack_article[] = $thisarticle;
}

/**
 * Advance to the next article in the current data stack.
 *
 * Populates $thisarticle global with the last article from the
 * stack stored in $stack_article.
 */

function article_pop()
{
    global $thisarticle, $stack_article;
    $thisarticle = array_pop($stack_article);
}

/**
 * Gets a path relative to the site's root directory.
 *
 * @param   string $path The filename to parse
 * @param   string $pfx  The root directory
 * @return  string The absolute $path converted to relative
 * @package File
 */

function relative_path($path, $pfx = null)
{
    if ($pfx === null) {
        $pfx = dirname(txpath);
    }

    return preg_replace('@^/'.preg_quote(ltrim($pfx, '/'), '@').'/?@', '', $path);
}

/**
 * Gets a backtrace.
 *
 * @param   int $num   The limit
 * @param   int $start The offset
 * @return  array A backtrace
 * @package Debug
 */

function get_caller($num = 1, $start = 2)
{
    $out = array();

    if (!is_callable('debug_backtrace')) {
        return $out;
    }

    $bt = debug_backtrace();

    for ($i = $start; $i < $num+$start; $i++) {
        if (!empty($bt[$i])) {
            $t = '';

            if (!empty($bt[$i]['file'])) {
                $t .= relative_path($bt[$i]['file']);
            }

            if (!empty($bt[$i]['line'])) {
                $t .= ':'.$bt[$i]['line'];
            }

            if ($t) {
                $t .= ' ';
            }

            if (!empty($bt[$i]['class'])) {
                $t .= $bt[$i]['class'];
            }

            if (!empty($bt[$i]['type'])) {
                $t .= $bt[$i]['type'];
            }

            if (!empty($bt[$i]['function'])) {
                $t .= $bt[$i]['function'];
                $t .= '()';
            }

            $out[] = $t;
        }
    }

    return $out;
}

/**
 * Sets a locale.
 *
 * The function name is misleading but remains for legacy reasons.
 *
 * @param      string $lang
 * @return     string Current locale
 * @package    L10n
 * @deprecated in 4.6.0
 * @see        \Textpattern\L10n\Locale::setLocale()
 */

function getlocale($lang)
{
    global $locale;

    Txp::get('\Textpattern\L10n\Locale')->setLocale(LC_TIME, array($lang, $locale));

    return Txp::get('\Textpattern\L10n\Locale')->getLocale(LC_TIME);
}

/**
 * Fetch meta description from the given (or automatic) context.
 *
 * Category context may be refined by specifying the content type as well
 * after a dot. e.g. category.image to check image context category.
 *
 * @param string $type Flavour of meta content to fetch (section, category, article)
 */

function getMetaDescription($type = null)
{
    global $thisarticle, $thiscategory, $thissection, $c, $s, $context, $txp_sections;

    $content = '';

    if ($type === null) {
        if ($thiscategory) {
            $content = $thiscategory['description'];
        } elseif ($thissection) {
            $content = $thissection['description'];
        } elseif ($thisarticle) {
            $content = $thisarticle['description'];
        } elseif ($c) {
            $content = safe_field("description", 'txp_category', "name = '".doSlash($c)."' AND type = '".doSlash($context)."'");
        } elseif ($s) {
            $content = isset($txp_sections[$s]) ? $txp_sections[$s]['description'] : '';
        }
    } else {
        if (strpos($type, 'category') === 0) {
            // Category context.
            if ($thiscategory) {
                $content = $thiscategory['description'];
            } else {
                $thisContext = $context;
                $catParts = do_list($type, '.');

                if (isset($catParts[1])) {
                    $thisContext = $catParts[1];
                }

                $clause = " AND type = '".$thisContext."'";
                $content = safe_field("description", 'txp_category', "name = '".doSlash($c)."'".$clause);
            }
        } elseif ($type === 'section') {
            $theSection = ($thissection) ? $thissection['name'] : $s;
            $content = isset($txp_sections[$theSection]) ? $txp_sections[$theSection]['description'] : '';
        } elseif ($type === 'article') {
            assert_article();
            $content = ($thisarticle? $thisarticle['description'] : '');
        }
    }

    return $content;
}

/**
 * Get some URL data.
 * @param mixed $context The data to retrieve
 * @param array $internals Data restrictions
 * @return array The retrieved data
 */

function get_context($context = true, $internals = array('id', 's', 'c', 'context', 'q', 'm', 'pg', 'p', 'month', 'author', 'f'))
{
    global $pretext, $txp_context;

    if (!isset($context)) {
        return empty($txp_context) ? array() : $txp_context;
    } elseif (empty($context)) {
        return array();
    } elseif (!is_array($context)) {
        $context = array_fill_keys($context === true ? $internals : do_list_unique($context), null);
    }

    $out = array();

    foreach ($context as $q => $v) {
        if (isset($v)) {
            $out[$q] = $v;
        } elseif (isset($pretext[$q]) && in_array($q, $internals)) {
            $out[$q] = $q === 'author' ? $pretext['realname'] : $pretext[$q];
        } else {
            $out[$q] = gps($q, $v);
        }
    }

    return $out;
}

/**
 * Assert context error.
 */

function assert_context($type = 'article', $throw = true)
{
    global ${'this'.$type};

    if (empty(${'this'.$type})) {
        if ($throw) {
            throw new \Exception(gTxt("error_{$type}_context"));
        } else {
            return false;
        }
    }

    return true;
}

/**
 * Assert article context error.
 */

function assert_article($throw = true)
{
    return assert_context('article', $throw);
}

/**
 * Assert comment context error.
 */

function assert_comment($throw = true)
{
    return assert_context('comment', $throw);
}

/**
 * Assert file context error.
 */

function assert_file($throw = true)
{
    return assert_context('file', $throw);
}

/**
 * Assert image context error.
 */

function assert_image($throw = true)
{
    return assert_context('image', $throw);
}

/**
 * Assert link context error.
 */

function assert_link($throw = true)
{
    return assert_context('link', $throw);
}

/**
 * Assert section context error.
 */

function assert_section($throw = true)
{
    return assert_context('section', $throw);
}

/**
 * Assert category context error.
 */

function assert_category($throw = true)
{
    return assert_context('category', $throw);
}

/**
 * Validate a variable as an integer.
 *
 * @param  mixed $myvar The variable
 * @return int|bool The variable or FALSE on error
 */

function assert_int($myvar)
{
    if (is_numeric($myvar) && $myvar == intval($myvar)) {
        return (int) $myvar;
    }

    trigger_error(gTxt('assert_int_value', array('{name}' => (string) $myvar)), E_USER_ERROR);

    return false;
}

/**
 * Validate a variable as a string.
 *
 * @param  mixed $myvar The variable
 * @return string|bool The variable or FALSE on error
 */

function assert_string($myvar)
{
    if (is_string($myvar)) {
        return $myvar;
    }

    trigger_error(gTxt('assert_string_value', array('{name}' => (string) $myvar)), E_USER_ERROR);

    return false;
}

/**
 * Validate a variable as an array.
 *
 * @param  mixed $myvar The variable
 * @return array|bool The variable or FALSE on error
 */

function assert_array($myvar)
{
    if (is_array($myvar)) {
        return $myvar;
    }

    trigger_error(gTxt('assert_array_value', array('{name}' => (string) $myvar)), E_USER_ERROR);

    return false;
}

/**
 * Converts relative links in HTML markup to absolute.
 *
 * @param   string $html      The HTML to check
 * @param   string $permalink Optional URL part appended to the links
 * @return  string HTML
 * @package URL
 */

function replace_relative_urls($html, $permalink = '')
{
    global $siteurl;

    // URLs like "/foo/bar" - relative to the domain.
    if (serverSet('HTTP_HOST')) {
        $html = preg_replace('@(<a[^>]+href=")/(?!/)@', '$1'.PROTOCOL.serverSet('HTTP_HOST').'/', $html);
        $html = preg_replace('@(<img[^>]+src=")/(?!/)@', '$1'.PROTOCOL.serverSet('HTTP_HOST').'/', $html);
    }

    // "foo/bar" - relative to the textpattern root,
    // leave "http:", "mailto:" et al. as absolute URLs.
    $html = preg_replace('@(<a[^>]+href=")(?!\w+:|//)@', '$1'.PROTOCOL.$siteurl.'/$2', $html);
    $html = preg_replace('@(<img[^>]+src=")(?!\w+:|//)@', '$1'.PROTOCOL.$siteurl.'/$2', $html);

    if ($permalink) {
        $html = preg_replace("/href=\\\"#(.*)\"/", "href=\"".$permalink."#\\1\"", $html);
    }

    return ($html);
}

/**
 * Used for clean URL test.
 *
 * @param  array $pretext
 * @access private
 */

function show_clean_test($pretext)
{
    ob_clean();
    if (is_array($pretext) && isset($pretext['req'])) {
        echo md5($pretext['req']).n;
    }

    if (serverSet('SERVER_ADDR') === serverSet('REMOTE_ADDR')) {
        var_export($pretext);
    }
}

/**
 * Calculates paging.
 *
 * Takes a total number of items, a per page limit and the current page number,
 * and in return returns the page number, an offset and a number of pages.
 *
 * @param  int $total The number of items in total
 * @param  int $limit The number of items per page
 * @param  int $page  The page number
 * @return array Array of page, offset and number of pages.
 * @example
 * list($page, $offset, $num_pages) = pager(150, 10, 1);
 * echo "Page {$page} of {$num_pages}. Offset is {$offset}.";
 */

function pager($total, $limit, $page)
{
    $total = (int) $total;
    $limit = (int) $limit;
    $page = (int) $page;

    $num_pages = ceil($total / $limit);

    $page = min(max($page, 1), $num_pages);

    $offset = max(($page - 1) * $limit, 0);

    return array($page, $offset, $num_pages);
}

/**
 * Word-wrap a string using a zero width space.
 *
 * @param  string $text  The input string
 * @param  int    $width Target line length
 * @param  string $break Is not used
 * @return string
 */

function soft_wrap($text, $width, $break = '&#8203;')
{
    $wbr = chr(226).chr(128).chr(139);
    $words = explode(' ', $text);

    foreach ($words as $wordnr => $word) {
        $word = preg_replace('|([,./\\>?!:;@-]+)(?=.)|', '$1 ', $word);
        $parts = explode(' ', $word);

        foreach ($parts as $partnr => $part) {
            $len = strlen(utf8_decode($part));

            if (!$len) {
                continue;
            }

            $parts[$partnr] = preg_replace('/(.{'.ceil($len/ceil($len/$width)).'})(?=.)/u', '$1'.$wbr, $part);
        }

        $words[$wordnr] = join($wbr, $parts);
    }

    return join(' ', $words);
}

/**
 * Removes prefix from a string.
 *
 * @param  string $str The string
 * @param  string $pfx The prefix
 * @return string
 */

function strip_prefix($str, $pfx)
{
    return preg_replace('/^'.preg_quote($pfx, '/').'/', '', $str);
}

/**
 * Sends an XML envelope.
 *
 * Wraps an array of name => value tuples into an XML envelope, supports one
 * level of nested arrays at most.
 *
 * @param   array $response
 * @return  string XML envelope
 * @package XML
 */

function send_xml_response($response = array())
{
    static $headers_sent = false;

    if (!$headers_sent) {
        ob_clean();
        header('Content-Type: text/xml; charset=utf-8');
        $out[] = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
        $headers_sent = true;
    }

    $default_response = array('http-status' => '200 OK');

    // Backfill default response properties.
    $response = $response + $default_response;

    txp_status_header($response['http-status']);
    $out[] = '<textpattern>';

    foreach ($response as $element => $value) {
        if (is_array($value)) {
            $out[] = t."<$element>".n;

            foreach ($value as $e => $v) {
                // Character escaping in values;
                // @see https://www.w3.org/TR/REC-xml/#sec-references.
                $v = str_replace(array("\t", "\n", "\r"), array("&#x9;", "&#xA;", "&#xD;"), htmlentities($v, ENT_QUOTES, 'UTF-8'));
                $out[] = t.t."<$e value='$v' />".n;
            }

            $out[] = t."</$element>".n;
        } else {
            $value = str_replace(array("\t", "\n", "\r"), array("&#x9;", "&#xA;", "&#xD;"), htmlentities($value, ENT_QUOTES, 'UTF-8'));
            $out[] = t."<$element value='$value' />".n;
        }
    }

    $out[] = '</textpattern>';
    echo join(n, $out);
}

/**
 * Sends a text/javascript response.
 *
 * @param   string $out The JavaScript
 * @since   4.4.0
 * @package Ajax
 */

function send_script_response($out = '')
{
    static $headers_sent = false;

    if (!$headers_sent) {
        ob_clean();
        header('Content-Type: text/javascript; charset=utf-8');
        txp_status_header('200 OK');
        $headers_sent = true;
    }

    echo ";\n".$out.";\n";
}

/**
 * Sends an application/json response.
 *
 * If the provided $out is not a string, its encoded as JSON. Any string is
 * treated as it were valid JSON.
 *
 * @param   mixed $out The JSON
 * @since   4.6.0
 * @package Ajax
 */

function send_json_response($out = '')
{
    static $headers_sent = false;

    if (!$headers_sent) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        txp_status_header('200 OK');
        $headers_sent = true;
    }

    if (!is_string($out)) {
        $out = json_encode($out, TEXTPATTERN_JSON);
    }

    echo $out;
}

/**
 * Performs regular housekeeping.
 *
 * @access private
 */

function janitor()
{
    global $prefs, $auto_dst, $timezone_key, $is_dst;

    // Update DST setting.
    if ($auto_dst && $timezone_key) {
        $is_dst = Txp::get('\Textpattern\Date\Timezone')->isDst(null, $timezone_key);

        if ($is_dst != $prefs['is_dst']) {
            $prefs['is_dst'] = $is_dst;
            set_pref('is_dst', $is_dst, 'publish', PREF_HIDDEN);
        }
    }
}

/**
 * Protection from those who'd bomb the site by GET.
 *
 * Origin of the infamous 'Nice try' message and an even more useful '503'
 * HTTP status.
 */

function bombShelter()
{
    global $prefs;
    $in = serverSet('REQUEST_URI');

    if (!empty($prefs['max_url_len']) and strlen($in) > $prefs['max_url_len'] + (!empty($_GET['txpcleantest']) ? 48 : 0)) {
        txp_status_header('503 Service Unavailable');
        exit('Nice try.');
    }
}

/**
 * Test whether the client accepts a certain response format.
 *
 * Discards formats with a quality factor below 0.1
 *
 * @param   string  $format One of 'html', 'txt', 'js', 'css', 'json', 'xml', 'rdf', 'atom', 'rss'
 * @return  boolean $format TRUE if accepted
 * @since   4.5.0
 * @package Network
 */

function http_accept_format($format)
{
    static $formats = array(
        'html' => array('text/html', 'application/xhtml+xml', '*/*'),
        'txt'  => array('text/plain', '*/*'),
        'js'   => array('application/javascript', 'application/x-javascript', 'text/javascript', 'application/ecmascript', 'application/x-ecmascript', '*/*'),
        'css'  => array('text/css', '*/*'),
        'json' => array('application/json', 'application/x-json', '*/*'),
        'xml'  => array('text/xml', 'application/xml', 'application/x-xml', '*/*'),
        'rdf'  => array('application/rdf+xml', '*/*'),
        'atom' => array('application/atom+xml', '*/*'),
        'rss'  => array('application/rss+xml', '*/*'),
    );
    static $accepts = array();
    static $q = array();

    if (empty($accepts)) {
        // Build cache of accepted formats.
        $accepts = preg_split('/\s*,\s*/', serverSet('HTTP_ACCEPT'), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($accepts as $i => &$a) {
            // Sniff out quality factors if present.
            if (preg_match('/(.*)\s*;\s*q=([.0-9]*)/', $a, $m)) {
                $a = $m[1];
                $q[$a] = floatval($m[2]);
            } else {
                $q[$a] = 1.0;
            }

            // Discard formats with quality factors below an arbitrary threshold
            // as jQuery adds a wildcard '*/*; q=0.01' to the 'Accepts' header
            // for XHR requests.
            if ($q[$a] < 0.1) {
                unset($q[$a]);
                unset($accepts[$i]);
            }
        }
    }

    return isset($formats[$format]) && count(array_intersect($formats[$format], $accepts)) > 0;
}

/**
 * Return a list of status codes and their associated names.
 *
 * The list can be extended with a 'status.types > types' callback event.
 * Callback functions get passed three arguments: '$event', '$step' and
 * '$status_list'. The third parameter contains a reference to an array of
 * 'status_code => label' pairs.
 *
 * @param   bool  Return the list with L10n labels (for UI purposes) or raw values (for comparisons)
 * @param   array List of status keys (numbers) to exclude
 * @return  array A status array
 * @since   4.6.0
 */

function status_list($labels = true, $exclude = array())
{
    $status_list = array(
        STATUS_DRAFT   => 'draft',
        STATUS_HIDDEN  => 'hidden',
        STATUS_PENDING => 'pending',
        STATUS_LIVE    => 'live',
        STATUS_STICKY  => 'sticky',
    );

    if (!is_array($exclude)) {
        $exclude = array();
    }

    foreach ($exclude as $remove) {
        unset($status_list[(int) $remove]);
    }

    callback_event_ref('status.types', 'types', 0, $status_list);

    if ($labels) {
        $status_list = array_map('gTxt', $status_list);
    }

    return $status_list;
}

/**
 * Translates article status names into numerical status codes.
 *
 * @param  string $name    Status name
 * @param  int    $default Status code to return if $name is not a defined status name
 * @return int Matching numerical status
 */

function getStatusNum($name, $default = STATUS_LIVE)
{
    $statuses = status_list(false);
    $status = strtolower($name);
    $num = array_search($status, $statuses);

    if ($num === false) {
        $num = $default;
    }

    return (int) $num;
}

/**
 * Gets the maximum allowed file upload size.
 *
 * Computes the maximum acceptable file size to the application if the
 * user-selected value is larger than the maximum allowed by the current PHP
 * configuration.
 *
 * @param  int $user_max Desired upload size supplied by the administrator
 * @return int Actual value; the lower of user-supplied value or system-defined value
 */

function real_max_upload_size($user_max, $php = true)
{
    // The minimum of the candidates, is the real max. possible size
    $candidates = $php ? array($user_max,
        ini_get('post_max_size'),
        ini_get('upload_max_filesize')
    ) : array($user_max);
    $real_max = null;

    foreach ($candidates as $item) {
        $val = floatval($item);
        $modifier = strtolower(substr(trim($item), -1));

        switch ($modifier) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
                // no break
            case 'm':
                $val *= 1024;
                // no break
            case 'k':
                $val *= 1024;
        }

        if ($val >= 1) {
            if (is_null($real_max) || $val < $real_max) {
                $real_max = floor($val);
            }
        }
    }

    // 2^53 - 1 is max safe JavaScript integer, let 8192Tb
    return number_format(min($real_max, pow(2, 53) - 1), 0, '.', '');
}

// -------------------------------------------------------------

function txp_match($atts, $what)
{
    static $dlmPool = array('/', '@', '#', '~', '`', '|', '!', '%');

    extract($atts + array(
        'value'     => null,
        'match'     => 'exact',
        'separator' => '',
    ));


    if ($value !== null) {
        switch ($match) {
            case '<':
            case 'less':
                $cond = (is_array($what) ? $what < do_list($value, $separator ? $separator : ',') : $what < $value);
                break;
            case '<=':
                $cond = (is_array($what) ? $what <= do_list($value, $separator ? $separator : ',') : $what <= $value);
                break;
            case '>':
            case 'greater':
                $cond = (is_array($what) ? $what > do_list($value, $separator ? $separator : ',') : $what > $value);
                break;
            case '>=':
                $cond = (is_array($what) ? $what >= do_list($value, $separator ? $separator : ',') : $what >= $value);
                break;
            case '':
            case 'exact':
                $cond = (is_array($what) ? $what == do_list($value, $separator ? $separator : ',') : $what == $value);
                break;
            case 'any':
                $values = do_list_unique($value);
                $cond = false;
                $cf_contents = $separator && !is_array($what) ? do_list_unique($what, $separator) : $what;

                foreach ($values as $term) {
                    if (is_array($cf_contents) ? in_array($term, $cf_contents) : strpos($cf_contents, $term) !== false) {
                        $cond = true;
                        break;
                    }
                }
                break;
            case 'all':
                $values = do_list_unique($value);
                $cond = true;
                $cf_contents = $separator && !is_array($what) ? do_list_unique($what, $separator) : $what;

                foreach ($values as $term) {
                    if (is_array($cf_contents) ? !in_array($term, $cf_contents) : strpos($cf_contents, $term) === false) {
                        $cond = false;
                        break;
                    }
                }
                break;
            case 'pattern':
                // Cannot guarantee that a fixed delimiter won't break preg_match
                // (and preg_quote doesn't help) so dynamically assign the delimiter
                // based on the first entry in $dlmPool that is NOT in the value
                // attribute. This minimises (does not eliminate) the possibility
                // of a TXP-initiated preg_match error, while still preserving
                // errors outside TXP's control (e.g. mangled user-submitted
                // PCRE pattern).
                if ($separator === true) {
                    $dlm = $value;
                } elseif ($separator && in_array($separator, $dlmPool)) {
                    $dlm = strpos($value, $separator) === 0 ? $value : $separator.$value.$separator;
                } else {
                    $dlm = array_diff($dlmPool, preg_split('//', $value));
                    $dlm = reset($dlm);
                    $dlm = $dlm.$value.$dlm;
                }

                $cond = preg_match($dlm, is_array($what) ? implode('', $what) : $what);
                break;
            default:
                trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'match')), E_USER_NOTICE);
                $cond = false;
        }
    } else {
        $cond = ($what !== null);
    }

    return !empty($cond);
}

// -------------------------------------------------------------

function txp_break($wraptag)
{
    switch (strtolower($wraptag)) {
        case 'ul':
        case 'ol':
            return 'li';
        case 'p':
            return 'br';
        case 'table':
        case 'tbody':
        case 'thead':
        case 'tfoot':
            return 'tr';
        case 'tr':
            return 'td';
        default:
            return ',';
    }
}

// -------------------------------------------------------------

function txp_hash($thing)
{
    return strlen($thing) < TEXTPATTERN_HASH_LENGTH ? $thing : hash(TEXTPATTERN_HASH_ALGO, $thing);
}

/*** Polyfills ***/

if (!function_exists('array_column')) {
    include txpath.'/lib/array_column.php';
}

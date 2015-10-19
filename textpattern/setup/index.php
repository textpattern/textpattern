<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
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

if (!defined('txpath')) {
    define("txpath", dirname(dirname(__FILE__)));
}

define("txpinterface", "admin");
error_reporting(E_ALL | E_STRICT);
@ini_set("display_errors", "1");

include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_misc.php';
include txpath.'/vendors/Textpattern/Loader.php';

$loader = new \Textpattern\Loader(txpath.'/vendors');
$loader->register();

$loader = new \Textpattern\Loader(txpath.'/lib');
$loader->register();

if (!isset($_SESSION)) {
    if (headers_sent()) {
        $_SESSION = array();
    } else {
        session_start();
    }
}

include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/lib/txplib_theme.php';
include_once txpath.'/include/txp_auth.php';

assert_system_requirements();

header("Content-type: text/html; charset=utf-8");
header('X-UA-Compatible: '.X_UA_COMPATIBLE);

// Drop trailing cruft.
$_SERVER['PHP_SELF'] = preg_replace('#^(.*index.php).*$#i', '$1', $_SERVER['PHP_SELF']);

// Sniff out the 'textpattern' directory's name '/path/to/site/textpattern/setup/index.php'.
$txpdir = explode('/', $_SERVER['PHP_SELF']);

if (count($txpdir) > 3) {
    // We live in the regular directory structure.
    $txpdir = '/'.$txpdir[count($txpdir) - 3];
} else {
    // We probably came here from a clever assortment of symlinks and DocumentRoot.
    $txpdir = '/';
}

$step = ps('step');
$rel_siteurl = preg_replace("#^(.*?)($txpdir)?/setup.*$#i", '$1', $_SERVER['PHP_SELF']);
$rel_txpurl = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
$bodyclass = ($step == '') ? ' class="welcome"' : '';

print <<<eod
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow">
<title>Setup &#124; Textpattern CMS</title>
<script src="../vendors/jquery/jquery/jquery.js"></script>
<script src="../vendors/jquery/ui/js/jquery-ui.js"></script>
<script>var textpattern = { do_spellcheck: "", textarray: {} };</script>
<script src="../textpattern.js"></script>
<link rel="stylesheet" href="../vendors/jquery/ui/css/textpattern/jquery-ui.min.css">
<link rel="stylesheet" href="../theme/hive/css/textpattern.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
<script src="../vendors/modernizr/modernizr/modernizr.js"></script>
<!--[if lt IE 9]>
<link rel="stylesheet" href="../vendors/jquery/ui/css/textpattern/jquery-ui-ie8.min.css">
<link rel="stylesheet" href="../theme/hive/css/ie8.min.css">
<script src="../vendors/keithclark/selectivizr/selectivizr.min.js"></script>
<![endif]-->
</head>
<body id="page-setup"{$bodyclass}>
<main class="txp-body">
eod;

switch ($step) {
    case '':
        chooseLang();
        break;
    case 'getDbInfo':
        getDbInfo();
        break;
    case 'getTxpLogin':
        getTxpLogin();
        break;
    case 'printConfig':
        printConfig();
        break;
    case 'createTxp':
        createTxp();
}
?>
</main>
</body>
</html>
<?php

// -------------------------------------------------------------

function chooseLang()
{
    $_SESSION = array();

    echo n.'<div class="txp-container" id="setup_container">',
        n.'<div class="txp-setup">',
        hed('Welcome to Textpattern CMS', 1),
        n.'<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">',
        langs(),
        graf(fInput('submit', 'Submit', 'Submit', 'publish')),
        sInput('getDbInfo'),
        n.'</form>',
        n.'</div>',
        n.'</div>';
}

// -------------------------------------------------------------

function txp_setup_progress_meter($stage = 1)
{
    $stages = array(
        1 => setup_gTxt('set_db_details'),
        2 => setup_gTxt('add_config_file'),
        3 => setup_gTxt('populate_db'),
        4 => setup_gTxt('get_started'),
    );

    $out = array();

    $out[] = n.'<div class="progress-meter">'.
        graf(setup_gTxt('progress_steps'), ' class="txp-accessibility"').
        n.'<ol>';

    foreach ($stages as $idx => $phase) {
        $active = ($idx == $stage);
        $sel = $active ? ' class="active"' : '';
        $out[] = n.'<li'.$sel.'>'.($active ? strong($phase) : $phase).'</li>';
    }

    $out[] = n.'</ol>'.
        n.'</div>';

    return join('', $out);
}

// -------------------------------------------------------------

function getDbInfo()
{
    $lang = ps('lang');

    if ($lang) {
        $_SESSION['lang'] = $lang;
    }

    $GLOBALS['textarray'] = setup_load_lang($_SESSION['lang']);

    global $txpcfg;

    echo n.'<div class="txp-container" id="setup_container">',
        txp_setup_progress_meter(1),
        n.'<div class="txp-setup">';

    if (!isset($txpcfg['db'])) {
        @include txpath.'/config.php';
    }

    if (!empty($txpcfg['db'])) {
        echo graf(
                span(setup_gTxt('already_installed', array('{txpath}' => txpath)), ' class="warning"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';

        exit;
    }

    if (isset($_SESSION['siteurl'])) {
        $guess_siteurl = $_SESSION['siteurl'];
    } elseif (@$_SERVER['SCRIPT_NAME'] && (@$_SERVER['SERVER_NAME'] || @$_SERVER['HTTP_HOST'])) {
        $guess_siteurl = (@$_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $guess_siteurl .= $GLOBALS['rel_siteurl'];
    } else {
        $guess_siteurl = 'mysite.com';
    }

    echo '<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        hed(setup_gTxt('need_details'), 1).
        hed('MySQL', 2).
        graf(setup_gTxt('db_must_exist')).

        graf(
            span('<label for="setup_mysql_login">'.setup_gTxt('mysql_login').'</label>', ' class="txp-label"').
            n.span(fInput('text', 'duser', (isset($_SESSION['duser']) ? txpspecialchars($_SESSION['duser']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_mysql_login'), ' class="txp-value"')
        ).

        graf(
            span('<label for="setup_mysql_pass">'.setup_gTxt('mysql_password').'</label>', ' class="txp-label"').
            n.span(fInput('text', 'dpass', (isset($_SESSION['dpass']) ? txpspecialchars($_SESSION['dpass']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_mysql_pass'), ' class="txp-value"')
        ).

        graf(
            span('<label for="setup_mysql_server">'.setup_gTxt('mysql_server').'</label>', ' class="txp-label"').
            n.span(fInput('text', 'dhost', (isset($_SESSION['dhost']) ? txpspecialchars($_SESSION['dhost']) : 'localhost'), '', '', '', INPUT_REGULAR, '', 'setup_mysql_server', '', true), ' class="txp-value"')
        ).

        graf(
            span('<label for="setup_mysql_db">'.setup_gTxt('mysql_database').'</label>', ' class="txp-label"').
            n.span(fInput('text', 'ddb', (isset($_SESSION['ddb']) ? txpspecialchars($_SESSION['ddb']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_mysql_db', '', true), ' class="txp-value"')
        ).

        graf(
            span('<label for="setup_table_prefix">'.setup_gTxt('table_prefix').'</label>'.popHelp('table_prefix'), ' class="txp-label"').
            n.span(fInput('text', 'dprefix', (isset($_SESSION['dprefix']) ? txpspecialchars($_SESSION['dprefix']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_table_prefix'), ' class="txp-value"')
        ).

        hed(setup_gTxt('site_url'), 2).
        graf(setup_gTxt('please_enter_url')).
        graf(
            span('<label for="setup_site_url">http(s)://</label>'.popHelp('siteurl'), ' class="txp-label"').
            n.span(fInput('text', 'siteurl', txpspecialchars($guess_siteurl), '', '', '', INPUT_REGULAR, '', 'setup_site_url', '', true), ' class="txp-value"')
        );

    if (is_disabled('mail')) {
        echo graf(
            span(setup_gTxt('warn_mail_unavailable'), ' class="warning"')
        );
    }

    echo graf(
        fInput('submit', 'Submit', setup_gTxt('next_step', '', 'raw'), 'publish')
    );

    echo sInput('printConfig').
        n.'</form>'.
        n.'</div>'.
        n.'</div>';
}

// -------------------------------------------------------------

function printConfig()
{
    $_SESSION['ddb'] = ps('ddb');
    $_SESSION['duser'] = ps('duser');
    $_SESSION['dpass'] = ps('dpass');
    $_SESSION['dhost'] = ps('dhost');
    $_SESSION['dprefix'] = ps('dprefix');
    $_SESSION['siteurl'] = ps('siteurl');

    $GLOBALS['textarray'] = setup_load_lang($_SESSION['lang']);

    global $txpcfg;

    echo n.'<div class="txp-container" id="setup_container">'.
        txp_setup_progress_meter(2).
        n.'<div class="txp-setup">';

    if (!isset($txpcfg['db'])) {
        @include txpath.'/config.php';
    }

    if (!empty($txpcfg['db'])) {
        echo graf(
                span(setup_gTxt('already_installed', array('{txpath}' => txpath)), ' class="warning"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';

        exit;
    }

// TODO: @see http://forum.textpattern.com/viewtopic.php?pid=263205#p263205
//    if ('' === $_SESSION['dhost'] || '' === $_SESSION['duser'] || '' === $_SESSION['ddb']) {
//        echo graf(
//            '<span class="war">'.setup_gTxt('missing_db_details').'</span>'
//        ).
//        n.setup_back_button().
//        n.'</div>'.
//        n.'</div>';
//        exit;
//    }

    echo hed(setup_gTxt("checking_database"), 2);

    if (strpos($_SESSION['dhost'], ':') === false) {
        $dhost = $_SESSION['dhost'];
        $dport = ini_get("mysqli.default_port");
    } else {
        list($dhost, $dport) = explode(':', $_SESSION['dhost'], 2);
        $dport = intval($dport);
    }

    $dsocket = ini_get("mysqli.default_socket");

    $mylink = mysqli_init();

    if (@mysqli_real_connect($mylink, $dhost, $_SESSION['duser'], $_SESSION['dpass'], '', $dport, $dsocket)) {
        $_SESSION['dclient_flags'] = 0;
    } elseif (@mysqli_real_connect($mylink, $dhost, $_SESSION['duser'], $_SESSION['dpass'], '', $dport, $dsocket, MYSQLI_CLIENT_SSL)) {
        $_SESSION['dclient_flags'] = 'MYSQLI_CLIENT_SSL';
    } else {
        echo graf(
                span(setup_gTxt('db_cant_connect'), ' class="error"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';

        exit;
    }

    echo graf(
        span(setup_gTxt('db_connected'), ' class="success"')
    );

    if (!($_SESSION['dprefix'] == '' || preg_match('#^[a-zA-Z_][a-zA-Z0-9_]*$#', $_SESSION['dprefix']))) {
        echo graf(
            span(setup_gTxt('prefix_bad_characters', array(
                '{dbprefix}' => strong(txpspecialchars($_SESSION['dprefix'])),
            ), 'raw'), ' class="error"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';

        exit;
    }

    if (!$mydb = mysqli_select_db($mylink, $_SESSION['ddb'])) {
        echo graf(
            span(setup_gTxt('db_doesnt_exist', array(
                '{dbname}' => strong(txpspecialchars($_SESSION['ddb'])),
            ), 'raw'), ' class="error"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';

        exit;
    }

    $tables_exist = mysqli_query($mylink, "describe `".$_SESSION['dprefix']."textpattern`");
    if ($tables_exist) {
        echo graf(
            span(setup_gTxt('tables_exist', array(
                '{dbname}' => strong(txpspecialchars($_SESSION['ddb'])),
            ), 'raw'), ' class="error"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';

        exit;
    }

    // On MySQL 5.5.3+ use real UTF-8 tables, if the client supports it.
    $_SESSION['dbcharset'] = "utf8mb4";
    // Lower versions only support UTF-8 limited to 3 bytes per character
    if (mysqli_get_server_version($mylink) < 50503) {
        $_SESSION['dbcharset'] = "utf8";
    } else {
        if (false !== strpos(mysqli_get_client_info($mylink), 'mysqlnd')) {
            // mysqlnd 5.0.9+ required
            if (mysqli_get_client_version($mylink) < 50009) {
                $_SESSION['dbcharset'] = "utf8";
            }
        } else {
            // libmysqlclient 5.5.3+ required
            if (mysqli_get_client_version($mylink) < 50503) {
                $_SESSION['dbcharset'] = "utf8";
            }
        }
    }

    echo graf(
        span(setup_gTxt('using_db', array(
            '{dbname}' => strong(txpspecialchars($_SESSION['ddb'])), ), 'raw').' ('.$_SESSION['dbcharset'].')', ' class="success"')
    );

    echo setup_config_contents().
        n.'</div>'.
        n.'</div>';
}

// -------------------------------------------------------------

function getTxpLogin()
{
    $GLOBALS['textarray'] = setup_load_lang($_SESSION['lang']);

    global $txpcfg;

    echo n.'<div class="txp-container" id="setup_container">';

    $problems = array();

    if (!isset($txpcfg['db'])) {
        if (!is_readable(txpath.'/config.php')) {
            $problems[] = graf(
                span(setup_gTxt('config_php_not_found', array('{file}' => txpspecialchars(txpath.'/config.php')), 'raw'), ' class="error"')
            );
        } else {
            @include txpath.'/config.php';
        }
    }

    if (!isset($txpcfg) || ($txpcfg['db'] != $_SESSION['ddb']) || ($txpcfg['table_prefix'] != $_SESSION['dprefix'])) {
        $problems[] = graf(
            span(setup_gTxt('config_php_does_not_match_input', '', 'raw'), ' class="error"')
        );

        echo txp_setup_progress_meter(2).
            n.'<div class="txp-setup">'.
            n.join(n, $problems).
            setup_config_contents().
            n.'</div>'.
            n.'</div>';

        exit;
    }

    // Default theme selector.
    $core_themes = array('classic', 'remora', 'hive');

    $themes = theme::names();

    foreach ($themes as $t) {
        $theme = theme::factory($t);

        if ($theme) {
            $m = $theme->manifest();
            $title = empty($m['title']) ? ucwords($theme->name) : $m['title'];
            $vals[$t] = (in_array($t, $core_themes) ? setup_gTxt('core_theme', array('{theme}' => $title)) : $title);
            unset($theme);
        }
    }

    asort($vals, SORT_STRING);

    $theme_chooser = selectInput('theme', $vals, (isset($_SESSION['theme']) ? txpspecialchars($_SESSION['theme']) : 'hive'), '', '', 'setup_admin_theme');

    echo txp_setup_progress_meter(3).
        n.'<div class="txp-setup">';

    echo '<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        hed(setup_gTxt('creating_db_tables'), 2).
        graf(setup_gTxt('about_to_create')).

        graf(
            span('<label for="setup_user_realname">'.setup_gTxt('your_full_name').'</label>', ' class="txp-label"').
            n.span(fInput('text', 'RealName', (isset($_SESSION['realname']) ? txpspecialchars($_SESSION['realname']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_user_realname', '', true), ' class="txp-value"')
        ).

        graf(
            span('<label for="setup_user_login">'.setup_gTxt('setup_login').'</label>'.popHelp('setup_user_login'), ' class="txp-label"').
            n.span(fInput('text', 'name', (isset($_SESSION['name']) ? txpspecialchars($_SESSION['name']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_user_login', '', true), ' class="txp-value"')
        ).

        graf(
            span('<label for="setup_user_pass">'.setup_gTxt('choose_password').'</label>'.popHelp('setup_user_pass'), ' class="txp-label"').
            n.span(fInput('text', 'pass', (isset($_SESSION['pass']) ? txpspecialchars($_SESSION['pass']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_user_pass', '', true), ' class="txp-value"')
        ).

        graf(
            span('<label for="setup_user_email">'.setup_gTxt('your_email'), ' class="txp-label"').
            n.span(fInput('email', 'email', (isset($_SESSION['email']) ? txpspecialchars($_SESSION['email']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_user_email', '', true), ' class="txp-value"')
        ).

        hed(setup_gTxt('site_config'), 2).

        graf(
            span('<label for="setup_admin_theme">'.setup_gTxt('admin_theme').'</label>'.popHelp('theme_name'), ' class="txp-label"').
            n.span($theme_chooser, ' class="txp-value"')
        ).

        graf(
            fInput('submit', 'Submit', setup_gTxt('next_step'), 'publish')
        ).

        sInput('createTxp').
        n.'</form>'.
        n.'</div>'.
        n.'</div>';
}

// -------------------------------------------------------------

function createTxp()
{
    global $link;
    $GLOBALS['textarray'] = setup_load_lang($_SESSION['lang']);
    $_SESSION['name'] = ps('name');
    $_SESSION['realname'] = ps('RealName');
    $_SESSION['pass'] = ps('pass');
    $_SESSION['email'] = ps('email');
    $_SESSION['theme'] = ps('theme');

    if ($_SESSION['name'] == '') {
        echo n.'<div class="txp-container" id="setup_container">'.
            txp_setup_progress_meter(3).
            n.'<div class="txp-setup">'.
            graf(
                span(setup_gTxt('name_required'), ' class="error"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';
        exit;
    }

    if (!$_SESSION['pass']) {
        echo n.'<div class="txp-container" id="setup_container">'.
            txp_setup_progress_meter(3).
            n.'<div class="txp-setup">'.
            graf(
                span(setup_gTxt('pass_required'), ' class="error"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';
        exit;
    }

    if (!is_valid_email($_SESSION['email'])) {
        echo n.'<div class="txp-container" id="setup_container">'.
            txp_setup_progress_meter(3).
            n.'<div class="txp-setup">'.
            graf(
                span(setup_gTxt('email_required'), ' class="error"')
            ).
            setup_back_button(__FUNCTION__).
            n.'</div>'.
            n.'</div>';
        exit;
    }

    global $txpcfg;

    if (!isset($txpcfg['db'])) {
        if (!is_readable(txpath.'/config.php')) {
            $problems[] = graf('<span class="error">'.setup_gTxt('config_php_not_found', array('{file}' => txpspecialchars(txpath.'/config.php')), 'raw').'</span>');
        } else {
            @include txpath.'/config.php';
        }
    }

    if (!isset($txpcfg) || ($txpcfg['db'] != $_SESSION['ddb']) || ($txpcfg['table_prefix'] != $_SESSION['dprefix'])) {
        $problems[] = graf('<span class="error">'.setup_gTxt('config_php_does_not_match_input', 'raw').'</span>');
        echo txp_setup_progress_meter(3).
            n.'<div class="txp-setup">'.
            n.join(n, $problems).
            n.setup_config_contents().
            n.'</div>'.
            n.'</div>';
        exit;
    }

    $ddb = $txpcfg['db'];
    $duser = $txpcfg['user'];
    $dpass = $txpcfg['pass'];
    $dhost = $txpcfg['host'];
    $dclient_flags = isset($txpcfg['client_flags']) ? $txpcfg['client_flags'] : 0;
    $dprefix = $txpcfg['table_prefix'];
    $dbcharset = $txpcfg['dbcharset'];

    $siteurl = str_replace("http://", '', $_SESSION['siteurl']);
    $siteurl = str_replace(' ', '%20', rtrim($siteurl, "/"));
    $urlpath = preg_replace('#^[^/]+#', '', $siteurl);

    define("PFX", trim($dprefix));
    define('TXP_INSTALL', 1);

    include_once txpath.'/lib/txplib_update.php';
    include txpath.'/setup/txpsql.php';

    $nonce = md5(uniqid(rand(), true));
    $hash  = doSlash(txp_hash_password($_SESSION['pass']));

    mysqli_query($link, "INSERT INTO `".PFX."txp_users` VALUES
        (1,
        '".doSlash($_SESSION['name'])."',
        '$hash',
        '".doSlash($_SESSION['realname'])."',
        '".doSlash($_SESSION['email'])."',
        1,
        now(),
        '$nonce')"
    );

    mysqli_query($link, "update `".PFX."txp_prefs` set val = '".doSlash($siteurl)."' where `name`='siteurl'");
    mysqli_query($link, "update `".PFX."txp_prefs` set val = '".LANG."' where `name`='language'");
    mysqli_query($link, "update `".PFX."txp_prefs` set val = '".getlocale(LANG)."' where `name`='locale'");
    mysqli_query($link, "update `".PFX."textpattern` set Body = replace(Body, 'siteurl', '".
        doSlash($urlpath)."'), Body_html = replace(Body_html, 'siteurl', '".
        doSlash($urlpath)."') WHERE ID = 1");

    // cf. update/_to_4.2.0.php.
    // TODO: Position might need altering when prefs panel layout is altered.
    $theme = $_SESSION['theme'] ? $_SESSION['theme'] : 'hive';
    mysqli_query($link, "insert `".PFX."txp_prefs` set prefs_id = 1, name = 'theme_name', val = '".
        doSlash($theme)."', type = '1', event = 'admin', html = 'themename', position = '160'");

    echo fbCreate();
}

// -------------------------------------------------------------

function makeConfig()
{
    define("nl", "';\n");
    define("o", '$txpcfg[\'');
    define("m", "'] = '");
    $open = chr(60).'?php';
    $close = '?'.chr(62);

    // Escape single quotes and backslashes in literal PHP strings.
    foreach ($_SESSION as $k => $v) {
        $_SESSION[$k] = addcslashes($_SESSION[$k], "'\\");
    }

    $_SESSION = doSpecial($_SESSION);

    return
    $open."\n"
    .o.'db'.m.$_SESSION['ddb'].nl
    .o.'user'.m.$_SESSION['duser'].nl
    .o.'pass'.m.$_SESSION['dpass'].nl
    .o.'host'.m.$_SESSION['dhost'].nl
    .($_SESSION['dclient_flags'] ? o.'client_flags'."'] = ".$_SESSION['dclient_flags'].";\n" : '')
    .o.'table_prefix'.m.$_SESSION['dprefix'].nl
    .o.'txpath'.m.txpath.nl
    .o.'dbcharset'.m.$_SESSION['dbcharset'].nl
    .$close;
}

// -------------------------------------------------------------

function fbCreate()
{
    echo n.'<div class="txp-container" id="setup_container">'.
        txp_setup_progress_meter(4).
        n.'<div class="txp-setup">';

    if ($GLOBALS['txp_install_successful'] === false) {
        return graf(
                span(setup_gTxt('errors_during_install', array(
                    '{num}' => $GLOBALS['txp_err_count'],
                )), ' class="error"')
            ).
            n.'</div>'.
            n.'</div>';
    } else {
        // Clear the session so no data is leaked.
        $_SESSION = array();

        $warnings = @find_temp_dir() ? '' : graf(
            span(setup_gTxt('set_temp_dir_prefs'), ' class="warning"')
        );

        $login_url = $GLOBALS['rel_txpurl'].'/index.php';

        return hed(setup_gTxt('that_went_well'), 1).

            $warnings.

            graf(
                setup_gTxt('you_can_access', array(
                    'index.php' => $login_url,
                ))
            ).

            graf(
                setup_gTxt('installation_postamble')
            ).

            hed(setup_gTxt('thanks_for_interest'), 3).

            graf(
                href(setup_gTxt('go_to_login'), $login_url, ' class="navlink publish"')
            ).

            n.'</div>'.
            n.'</div>';
    }
}

// -------------------------------------------------------------

function setup_config_contents()
{
    return hed(setup_gTxt('creating_config'), 2).
        graf(
            strong(setup_gTxt('before_you_proceed')).' '.
            setup_gTxt('create_config', array('{txpath}' => txpspecialchars(txpath)))
        ).
        n.'<textarea class="code" name="config" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_REGULAR.'" dir="ltr" readonly>'.
            makeConfig().
        n.'</textarea>'.
        n.'<form method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
            graf(fInput('submit', 'submit', setup_gTxt('did_it'), 'publish')).
            sInput('getTxpLogin').
        n.'</form>';
}

// -------------------------------------------------------------

function setup_back_button($current = null)
{
    $prevSteps = array(
        'getDbInfo'   => '',
        'getTxpLogin' => 'getDbInfo',
        'printConfig' => 'getDbInfo',
        'createTxp'   => 'getTxpLogin',
        'fbCreate'    => 'createTxp',
    );

    $prev = isset($prevSteps[$current]) ? $prevSteps[$current] : '';

    return graf(
        setup_gTxt('please_go_back')
    ).
    graf(
        '<form method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        sInput($prev).
        fInput('submit', 'submit', setup_gTxt('back'), 'navlink').
        '</form>'
    );
}

// -------------------------------------------------------------

function langs()
{
    $langs = array(
        'ar-dz' => 'جزائري عربي',
        'bg-bg' => 'Български',
        'bs-ba' => 'Bosanski (Bosna i Hercegovina)',
        'ca-es' => 'Català',
        'cs-cz' => 'Čeština',
        'da-dk' => 'Dansk',
        'de-de' => 'Deutsch',
        'el-gr' => 'Ελληνικά',
        'en-gb' => 'English (Great Britain)',
        'en-us' => 'English (United States)',
        'es-es' => 'Español',
        'et-ee' => 'Eesti',
        'fa-ir' => 'Persian (پارسی)',
        'fi-fi' => 'Suomi',
        'fr-fr' => 'Français',
        'gl-gz' => 'Galego',
        'he-il' => 'עברית',
        'hr-hr' => 'Hrvatski',
        'hu-hu' => 'Magyar',
        'id-id' => 'Bahasa Indonesia',
        'is-is' => 'Íslenska',
        'it-it' => 'Italiano',
        'ja-jp' => '日本語',
        'ko-kr' => '한국말 (대한민국)',
        'lt-lt' => 'Lietuvių',
        'lv-lv' => 'Latviešu',
        'nl-nl' => 'Nederlands',
        'no-no' => 'Norsk',
        'pl-pl' => 'Polski',
        'pt-br' => 'Português (Brasil)',
        'pt-pt' => 'Português (Portugal)',
        'ro-ro' => 'Română',
        'ru-ru' => 'Русский',
        'sk-sk' => 'Slovenčina',
        'sp-rs' => 'Srpski',
        'sr-rs' => 'Српски',
        'sv-se' => 'Svenska',
        'th-th' => 'ไทย',
        'tr-tr' => 'Türkçe',
        'uk-ua' => 'Українська',
        'ur-in' => 'اردو (بھارت',
        'vi-vn' => 'Tiếng Việt (Việt Nam)',
        'zh-cn' => '中文(简体)',
        'zh-tw' => '中文(繁體)',
    );

    $default = (!empty($_SESSION['lang']) ? $_SESSION['lang'] : 'en-gb');

    $out = n.'<p><label for="setup_language">Please choose a language</label>'.
        br.'<select name="lang" id="setup_language">';

    foreach ($langs as $a => $b) {
        $out .= n.'<option value="'.txpspecialchars($a).'"'.
            (($a == $default) ? ' selected="selected"' : '').
            '>'.txpspecialchars($b).'</option>';
    }

    $out .= n.'</select></p>';

    return $out;
}

// -------------------------------------------------------------

function setup_load_lang($lang)
{
    global $en_gb_strings;

    require_once txpath.'/setup/setup-langs.php';
    $en_gb_strings = $langs['en-gb'];
    $lang = (isset($langs[$lang]) && !empty($langs[$lang])) ? $lang : 'en-gb';
    define('LANG', $lang);

    return $langs[LANG];
}

// -------------------------------------------------------------

function setup_gTxt($var, $atts = array(), $escape = 'html')
{
    global $en_gb_strings;

    // Try to translate the string in chosen native language.
    $xlate = gTxt($var, $atts, $escape);

    if (!is_array($atts)) {
        $atts = array();
    }

    if ($escape == 'html') {
        foreach ($atts as $key => $value) {
            $atts[$key] = txpspecialchars($value);
        }
    }

    $v = strtolower($var);

    // Find out if the translated string is the same as the $var input.
    if ($atts) {
        $compare = ($xlate == $v.': '.join(', ', $atts));
    } else {
        $compare = ($xlate == $v);
    }

    // No translation string available, so grab an English string we know exists
    // as fallback.
    if ($compare) {
        $xlate = strtr($en_gb_strings[$v], $atts);
    }

    return $xlate;
}

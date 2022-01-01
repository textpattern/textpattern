<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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

if (!defined('txpath')) {
    define("txpath", dirname(dirname(__FILE__)));
}

define("txpinterface", "admin");
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", "1");

define('MSG_OK', 'alert-block success');
define('MSG_ALERT', 'alert-block warning');
define('MSG_ERROR', 'alert-block error');

include_once txpath.'/lib/class.trace.php';
$trace = new Trace();
include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_misc.php';
include_once txpath.'/lib/txplib_admin.php';
include_once txpath.'/vendors/Textpattern/Loader.php';

$loader = new \Textpattern\Loader(txpath.DS.'vendors');
$loader->register();

$loader = new \Textpattern\Loader(txpath.DS.'lib');
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
include_once txpath.'/include/txp_auth.php';
include_once txpath.'/setup/setup_lib.php';

assert_system_requirements();

header("Content-Type: text/html; charset=utf-8");

// Drop trailing cruft.
$_SERVER['PHP_SELF'] = preg_replace('#^(.*index.php).*$#i', '$1', $_SERVER['PHP_SELF']);

// Sniff out the 'textpattern' directory's name '/path/to/site/textpattern/setup/index.php'.
$txpdir = explode('/', $_SERVER['PHP_SELF']);

if (count($txpdir) > 3) {
    // We live in the regular directory structure.
    $txpdir = DS.$txpdir[count($txpdir) - 3];
} else {
    // We probably came here from a clever assortment of symlinks and DocumentRoot.
    $txpdir = DS;
}

$prefs = array();
$prefs['module_pophelp'] = 1;
$step = ps('step');

// Be kind to Windows: replace directory separator with '/' since
// PHP returns $_SERVER variables in that form.
$pattern = "#^(.*?)(".str_replace(DS, '/', $txpdir).")?/setup.*$#i";
$rel_siteurl = preg_replace($pattern, '$1', $_SERVER['PHP_SELF']);
$rel_txpurl = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), DS);

if (empty($_SESSION['cfg'])) {
    $cfg = json_decode(txp_get_contents(dirname(__FILE__).DS.'.default.json'), true);
} else {
    $cfg = $_SESSION['cfg'];
}

if (ps('lang')) {
    $cfg['site']['language_code'] = ps('lang');
}

if (empty($cfg['site']['language_code'])) {
    $cfg['site']['language_code'] = TEXTPATTERN_DEFAULT_LANG;
}

setup_load_lang($cfg['site']['language_code']);

if (defined('is_multisite')) {
    $config_path = multisite_root_path.DS.'private';
} else {
    $config_path = txpath;
}


$protocol = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://';

if (defined('is_multisite')) {
    if (empty($cfg['site']['admin_url'])) {
        $cfg['site']['admin_url'] = $protocol.
        (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    }

    if (empty($cfg['site']['cookie_domain'])) {
        $cfg['site']['cookie_domain'] = substr($cfg['site']['admin_url'], strpos($cfg['site']['admin_url'], '.') + 1);
    }

    if (empty($cfg['site']['public_url'])) {
        $cfg['site']['public_url'] = $protocol.'www.'.$cfg['site']['cookie_domain'];
    }
}

if (empty($cfg['site']['public_url'])) {
    if (!empty($_SERVER['SCRIPT_NAME']) && (!empty($_SERVER['SERVER_NAME']) || !empty($_SERVER['HTTP_HOST']))) {
        $cfg['site']['public_url'] = $protocol.
        (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']).$rel_siteurl;
    } else {
        $cfg['site']['public_url'] = $protocol.'mysite.com';
    }
}


switch ($step) {
    case '':
        step_chooseLang();
        break;
    case 'step_getDbInfo':
        step_getDbInfo();
        break;
    case 'step_getTxpLogin':
        step_getTxpLogin();
        break;
    case 'step_printConfig':
        step_printConfig();
        break;
    case 'step_createTxp':
        step_createTxp();
}
$_SESSION['cfg'] = $cfg;
exit("</main>\n</body>\n</html>");


/**
 * Return the top of page furniture.
 *
 * @return HTML
 */

function preamble()
{
    global $textarray_script, $cfg, $step;

    $out = array();
    $bodyclass = ($step == '') ? ' welcome' : '';
    gTxtScript(array('help'));

    if (isset($cfg['site']['language_code']) && !isset($_SESSION['direction'])) {
        $file = Txp::get('\Textpattern\L10n\Lang', txpath.DS.'setup'.DS.'lang'.DS)->findFilename($cfg['site']['language_code']);
        $meta = Txp::get('\Textpattern\L10n\Lang', txpath.DS.'setup'.DS.'lang'.DS)->fetchMeta($file);
        $_SESSION['direction'] = isset($meta['direction']) ? $meta['direction'] : 'ltr';
    }

    $textDirection = (isset($_SESSION['direction'])) ? ' dir="'.$_SESSION['direction'].'"' : 'ltr';

    $out[] = <<<eod
    <!DOCTYPE html>
    <html lang="en"{$textDirection}>
    <head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Setup &#124; Textpattern CMS</title>
eod;

    $out[] = script_js('../vendors/jquery/jquery/jquery.js', TEXTPATTERN_SCRIPT_URL).
        script_js('../vendors/jquery/jquery-ui/jquery-ui.js', TEXTPATTERN_SCRIPT_URL).
        script_js(
            'var textpattern = '.json_encode(array(
                'prefs'         => (object) null,
                'event'         => 'setup',
                'step'          => $step,
                'textarray'     => (object) $textarray_script,
                ), TEXTPATTERN_JSON).';').
        script_js('../textpattern.js', TEXTPATTERN_SCRIPT_URL);

    $out[] = <<<eod
    <link rel="stylesheet" href="../admin-themes/hive/assets/css/textpattern.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    </head>
    <body class="setup{$bodyclass}" id="page-setup">
    <script src="../admin-themes/hive/assets/js/darkmode.js"></script>
    <main class="txp-body">
eod;

    return join(n, $out);
}

/**
 * Renders stage 0: welcome/choose language panel.
 */

function step_chooseLang()
{
    $_SESSION['direction'] = 'ltr';
    echo preamble();
    unset($_SESSION['direction']);
    echo n.'<div class="txp-setup">',
        hed('Welcome to Textpattern CMS', 1),
        n.'<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">',
        langs(),
        graf(fInput('submit', 'Submit', 'Submit', 'publish')),
        sInput('step_getDbInfo'),
        n.'</form>',
        n.'</div>';
}

/**
 * Renders progress meter displayed on stages 1 to 4 of installation process.
 *
 * @param int $stage The stage
 */

function txp_setup_progress_meter($stage = 1)
{
    $stages = array(
        1 => gTxt('set_db_details'),
        2 => gTxt('add_config_file'),
        3 => gTxt('populate_db'),
        4 => gTxt('get_started'),
    );

    $out = array();

    $out[] = n.'<aside class="progress-meter">'.
        graf(gTxt('progress_steps'), ' class="txp-accessibility"').
        n.'<ol>';

    foreach ($stages as $idx => $phase) {
        $active = ($idx == $stage);
        $sel = $active ? ' class="active" aria-current="step"' : '';
        $out[] = n.'<li'.$sel.'>'.($active ? strong($phase) : $phase).'</li>';
    }

    $out[] = n.'</ol>'.
        n.'</aside>';

    return join('', $out);
}

/**
 * Renders stage 1: database details panel.
 */

function step_getDbInfo()
{
    global $cfg;

    echo preamble();
    echo txp_setup_progress_meter(1),
        n.'<div class="txp-setup">';

    check_config_exists();

    $dbuser = !empty($cfg['database']['user']) ? $cfg['database']['user'] : '';
    $dbpass = !empty($cfg['database']['password']) ? $cfg['database']['password'] : '';
    $dbname = !empty($cfg['database']['db_name']) ? $cfg['database']['db_name'] : '';
    $dbmake = !empty($cfg['database']['create']) ? $cfg['database']['create'] : '';
    $dbtpfx = !empty($cfg['database']['table_prefix']) ? $cfg['database']['table_prefix'] : '';
    $dbhost = !empty($cfg['database']['host']) ? $cfg['database']['host'] : 'localhost';

    echo '<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        hed(gTxt('need_details'), 1).
        hed(tag('MySQL', 'bdi', array('dir' => 'ltr')), 2).
        inputLabel(
            'setup_mysql_login',
            fInput('text', 'duser', $dbuser, '', '', '', INPUT_REGULAR, '', 'setup_mysql_login'),
            'mysql_login', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_mysql_pass',
            fInput('password', 'dpass', $dbpass, 'txp-maskable', '', '', INPUT_REGULAR, '', 'setup_mysql_pass').
            n.tag(
                checkbox('unmask', 1, false, 0, 'show_password').
                n.tag(gTxt('setup_show_password'), 'label', array('for' => 'show_password')),
                'div', array('class' => 'show-password')),
            'mysql_password', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_mysql_server',
            fInput('text', 'dhost', $dbhost, '', '', '', INPUT_REGULAR, '', 'setup_mysql_server'),
            'mysql_server', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_mysql_db',
            fInput('text', 'ddb', $dbname, '', '', '', INPUT_REGULAR, '', 'setup_mysql_db', '', true),
            'mysql_database', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_mysql_db_make',
            selectInput('dmake', array(gTxt('setup_db_none'), gTxt('setup_db_create')), $dbmake),
            'mysql_create', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_table_prefix',
            fInput('text', 'dprefix', $dbtpfx, 'input-medium', '', '', INPUT_MEDIUM, '', 'setup_table_prefix'),
            'table_prefix', 'table_prefix', array('class' => 'txp-form-field')
        );

    if (defined('is_multisite')) {
        $saurl = !empty($cfg['site']['admin_url']) ? $cfg['site']['admin_url'] : '';
        $scdom = !empty($cfg['site']['cookie_domain']) ? $cfg['site']['cookie_domain'] : '';

        echo hed(
                gTxt('multisite_config'), 2
            ).
            graf(gTxt('multisite_please_enter_details')).
            inputLabel(
                'setup_admin_url',
                fInput('text', 'adminurl', $saurl, '', '', '', INPUT_REGULAR, '', 'setup_admin_url', '', true),
                'multisite_admin_domain', 'setup_admin_url', array('class' => 'txp-form-field')
            ).
            inputLabel(
                'setup_cookie_domain',
                fInput('text', 'cookiedomain', $scdom, '', '', '', INPUT_REGULAR, '', 'setup_cookie_domain', '', true),
                'multisite_cookie_domain', 'setup_cookie_domain', array('class' => 'txp-form-field')
            );
    }

    if (is_disabled('mail')) {
        echo msg(gTxt('warn_mail_unavailable'), MSG_ALERT);
    }

    echo graf(
        fInput('submit', 'Submit', gTxt('next_step', '', 'raw'), 'publish')
    );

    echo sInput('step_printConfig').
        n.'</form>'.
        n.'</div>';
}

/**
 * Renders stage 2: either config details panel (on success) or database details
 * error message (on fail).
 */

function step_printConfig()
{
    global $cfg;

    $cfg['database']['user'] = ps('duser');
    $cfg['database']['password'] = ps('dpass');
    $cfg['database']['host'] = ps('dhost');
    $cfg['database']['db_name'] = ps('ddb');
    $cfg['database']['table_prefix'] = ps('dprefix');
    $cfg['database']['create'] = ps('dmake');

    if (defined('is_multisite')) {
        $cfg['site']['admin_url'] = ps('adminurl');
        $cfg['site']['cookie_domain'] = ps('cookiedomain');
    }

    echo preamble();
    echo txp_setup_progress_meter(2).
        n.'<div class="txp-setup">';

    check_config_exists();

    echo hed(gTxt('checking_database'), 2);
    setup_connect();

    echo setup_config_contents().
        n.'</div>';
}

/**
 * Renders either stage 3: admin user details panel (on success), or stage 2:
 * config details error message (on fail).
 */

function step_getTxpLogin()
{
    global $cfg;

    $problems = array();

    echo preamble();
    check_config_txp(2);

    // Default admin-theme selector.
    $core_themes = array('classic', 'hive', 'hiveneutral');

    $vals = \Textpattern\Admin\Theme::names(1);

    foreach ($vals as $key => $title) {
        $vals[$key] = (in_array($key, $core_themes) ? gTxt('core_theme', array('{theme}' => $title)) : $title);
    }

    asort($vals, SORT_STRING);

    $theme_chooser = selectInput('theme', $vals,
        (empty($cfg['site']['admin_theme']) ? 'hive' : $cfg['site']['admin_theme']),
        '', '', 'setup_admin_theme');

    $public_themes_class = Txp::get('Textpattern\Skin\Skin');

    $public_themes_class->setDirPath(txpath.DS.'..'.DS.'themes');
    $vals = $public_themes_class->getUploaded(false);

    $public_themes_class->setDirPath(txpath.DS.'setup'.DS.'themes');
    $vals = array_merge($public_themes_class->getUploaded(false), $vals);

    $public_theme_name = (empty($cfg['site']['public_theme']) ? 'four-point-eight' : $cfg['site']['public_theme']);

    $public_theme_chooser = selectInput('public_theme', $vals, $public_theme_name, '', '', 'setup_public_theme');

    $usname = !empty($cfg['user']['full_name']) ? $cfg['user']['full_name'] : '';
    $uspass = !empty($cfg['user']['password']) ? $cfg['user']['password'] : '';
    $usmail = !empty($cfg['user']['email']) ? $cfg['user']['email'] : '';
    $uslogin = !empty($cfg['user']['login_name']) ? $cfg['user']['login_name'] : '';

    echo txp_setup_progress_meter(3).
        n.'<div class="txp-setup">'.
        n.'<form class="prefs-form" method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        hed(
            gTxt('creating_db_tables'), 2
        ).
        graf(
            gTxt('about_to_create')
        ).
        inputLabel(
            'setup_user_realname',
            fInput('text', 'RealName', $usname, '', '', '', INPUT_REGULAR, '', 'setup_user_realname', '', true),
            'your_full_name', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_user_email',
            fInput('email', 'email', $usmail, '', '', '', INPUT_REGULAR, '', 'setup_user_email', '', true),
            'your_email', '', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_user_login',
            fInput('text', 'name', $uslogin, '', '', '', INPUT_REGULAR, '', 'setup_user_login', '', true),
            'setup_login', 'setup_user_login', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_user_pass',
            fInput('password', 'pass', $uspass, 'txp-maskable', '', '', INPUT_REGULAR, '', 'setup_user_pass', '', true).
            n.tag(
                checkbox('unmask', 1, false, 0, 'show_password').
                n.tag(gTxt('setup_show_password'), 'label', array('for' => 'show_password')),
                'div', array('class' => 'show-password')),
            'choose_password', 'setup_user_pass', array('class' => 'txp-form-field')
        ).
        hed(
            gTxt('site_config'), 2
        ).
        inputLabel(
            'setup_site_url',
            fInput('text', 'siteurl', $cfg['site']['public_url'], '', '', '', INPUT_REGULAR, '', 'setup_site_url', '', true),
            'please_enter_url', 'siteurl', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_admin_theme',
            $theme_chooser,
            'admin_theme', 'theme_name', array('class' => 'txp-form-field')
        ).
        inputLabel(
            'setup_public_theme',
            $public_theme_chooser,
            'public_theme', 'public_theme_name', array('class' => 'txp-form-field')
        ).
        graf(
            fInput('submit', 'Submit', gTxt('next_step'), 'publish')
        ).
        sInput('step_createTxp').
        n.'</form>'.
        n.'</div>';
}

/**
 * Re-renders stage 3: admin user details panel, due to user input errors.
 */

function step_createTxp()
{
    global $cfg;

    $cfg['user']['full_name'] = ps('RealName');
    $cfg['user']['email'] = ps('email');
    $cfg['user']['login_name'] = ps('name');
    $cfg['user']['password'] = ps('pass');

    $cfg['site']['public_url'] = ps('siteurl');
    $cfg['site']['admin_theme'] = ps('theme');
    $cfg['site']['public_theme'] = ps('public_theme');
    $cfg['site']['content_directory'] = '';

    echo preamble();

    if (empty($cfg['user']['login_name'])) {
        echo txp_setup_progress_meter(3).n.'<div class="txp-setup">';
        msg(gTxt('name_required'), MSG_ERROR, true);
    }

    if (empty($cfg['user']['password'])) {
        echo txp_setup_progress_meter(3).n.'<div class="txp-setup">';
        msg(gTxt('pass_required'), MSG_ERROR, true);
    }

    if (!is_valid_email($cfg['user']['email'])) {
        echo txp_setup_progress_meter(3).n.'<div class="txp-setup">';
        msg(gTxt('email_required'), MSG_ERROR, true);
    }

    check_config_txp(3);
    setup_db($cfg);
    step_fbCreate();
}

/**
 * Renders stage 4: either installation completed panel (success) or
 * installation error message (fail).
 */

function step_fbCreate()
{
    global $cfg;

    unset($cfg['database']['client_flags']);
    unset($cfg['database']['charset']);

    $setup_autoinstall_body = gTxt('setup_autoinstall_body')."<pre>".
        json_encode($cfg, TEXTPATTERN_JSON | JSON_PRETTY_PRINT).
        "</pre>";

    if (defined('is_multisite')) {
        $multisite_admin_login_url = $GLOBALS['protocol'].$cfg['site']['admin_url'];
    }

    $warnings = find_temp_dir() ? '' : msg(gTxt('set_temp_dir_prefs'), MSG_ALERT);

    if (defined('is_multisite')) {
        $login_url  = $multisite_admin_login_url.DS.'index.php?lang='.$cfg['site']['language_code'];
        $setup_path = multisite_root_path.DS.'admin'.DS;
    } else {
        $login_url  = $GLOBALS['rel_txpurl'].DS.'index.php?lang='.$cfg['site']['language_code'];
        $setup_path = DS.basename(txpath).DS;
    }

    // Clear the session so no data is leaked.
    $_SESSION = $cfg = array();

    echo txp_setup_progress_meter(4).
        n.'<div class="txp-setup">'.
        hed(gTxt('that_went_well'), 1).
        $warnings.
        graf(
            gTxt('you_can_access', array('index.php' => $login_url))
        ).
        // graf(
            // gTxt('setup_autoinstall_text').popHelp('#', 0, 0, 'pophelp', $setup_autoinstall_body)
        // ).
        graf(
            gTxt('installation_postamble', array(
                '{setuppath}' => $setup_path,
            ))
        ).
        hed(gTxt('thanks_for_interest'), 3).
        graf(
            href(gTxt('go_to_login'), $login_url, ' class="navlink publish"')
        ).
        n.'</div>';
}


/**
 * Populate a textarea with config.php file code.
 *
 * @return HTML
 */

function setup_config_contents()
{
    global $cfg, $config_path;

    return hed(gTxt('creating_config'), 2).
        graf(
            strong(gTxt('before_you_proceed')).' '.
            gTxt('create_config', array('{configpath}' => $config_path.DS))
        ).
        graf('<a class="txp-button txp-config-download">'.gTxt('download').'</a>').
        n.'<textarea class="code" name="config" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_REGULAR.'" dir="ltr" readonly>'.
            setup_makeConfig($cfg, true).
        n.'</textarea>'.
        n.'<form method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
            graf(fInput('submit', 'submit', gTxt('did_it'), 'publish')).
            sInput('step_getTxpLogin').
        n.'</form>';
}


/**
 * Render a 'back' button that goes to the correct step.
 *
 * @return HTML
 */

function setup_back_button()
{
    global $step;

    $prevSteps = array(
        'step_getDbInfo'   => '',
        'step_getTxpLogin' => 'step_getDbInfo',
        'step_printConfig' => 'step_getDbInfo',
        'step_createTxp'   => 'step_getTxpLogin',
        'step_fbCreate'    => 'step_createTxp',
    );

    $prev = isset($prevSteps[$step]) ? $prevSteps[$step] : '';

    return graf(gTxt('please_go_back')).
        n.'<form method="post" action="'.txpspecialchars($_SERVER['PHP_SELF']).'">'.
        sInput($prev).
        fInput('submit', 'submit', gTxt('back'), 'navlink publish').
        n.'</form>';
}

/**
 * Fetch a dropdown of available languages.
 *
 * The list is fetched from the file system of translations.
 *
 * @return array
 */

function langs()
{
    global $cfg;

    $files = Txp::get('\Textpattern\L10n\Lang', txpath.DS.'setup'.DS.'lang'.DS)->files();
    $langs = array();

    $out = n.'<div class="txp-form-field">'.
        n.'<div class="txp-form-field-label">'.
        n.'<label for="setup_language">Please choose a language</label>'.
        n.'</div>'.
        n.'<div class="txp-form-field-value">'.
        n.'<select id="setup_language" name="lang" autocomplete="language">';

    if (is_array($files) && !empty($files)) {
        foreach ($files as $file) {
            $meta = Txp::get('\Textpattern\L10n\Lang', txpath.DS.'setup'.DS.'lang'.DS)->fetchMeta($file);

            if (! empty($meta['code'])) {
                $out .= n.'<option value="'.txpspecialchars($meta['code']).'"'.
                    (($meta['code'] == $cfg['site']['language_code']) ? ' selected="selected"' : '').
                    '>'.txpspecialchars($meta['name']).'</option>';
            }
        }
    }

    $out .= n.'</select>'.
        n.'</div>'.
        n.'</div>';

    return $out;
}


function check_config_txp($meter)
{
    global $txpcfg, $cfg, $config_path;

    $cfg_file = $config_path.DS.'config.php';

    if (!isset($txpcfg['db'])) {
        if (!is_readable($cfg_file)) {
            $problems[] = msg(gTxt('config_php_not_found', array(
                    '{file}' => txpspecialchars($cfg_file)
                ), 'raw'), MSG_ERROR);
        } else {
            include $cfg_file;
        }
    }

    if (!isset($txpcfg)
        || ($txpcfg['db'] != $cfg['database']['db_name'])
        || ($txpcfg['table_prefix'] != $cfg['database']['table_prefix'])
    ) {
        $problems[] = msg(gTxt('config_php_does_not_match_input', '', 'raw'), MSG_ERROR);

        echo txp_setup_progress_meter($meter).
            n.'<div class="txp-setup">'.
            n.join(n, $problems).
            setup_config_contents().
            n.'</div>';

        exit;
    }
}

function check_config_exists()
{
    global $txpcfg, $config_path;

    $cfg_file = $config_path.DS.'config.php';

    if (!isset($txpcfg['db']) && is_readable($cfg_file)) {
        include $cfg_file;
    }

    if (!empty($txpcfg['db'])) {
        echo msg(gTxt('already_installed', array('{configpath}' => $config_path.DS)), MSG_ALERT, true);
    }
}

/**
 * Message box
 *
 */

function msg($msg, $class = MSG_OK, $back = false)
{
    global $cfg;

    $icon = ($class == MSG_OK) ? 'ui-icon ui-icon-check' : 'ui-icon ui-icon-alert';
    $out = graf(
        span(null, array('class' => $icon)).' '.
        $msg,
        array('class' => $class)
    );

    if (!$back) {
        return $out;
    }

    echo $out . setup_back_button().n.'</div>';
    $_SESSION['cfg'] = $cfg;
    exit;
}

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

/**
 * Diagnostics panel.
 *
 * @package Admin\Diag
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

/**
 * @ignore
 */

define("cs", ': ');

/**
 * @ignore
 */

define("ln", str_repeat('-', 24).n);

/**
 * @ignore
 */

define("priv", '=== ');

global $files;

$files = check_file_integrity();

if (!$files) {
    $files = array();
} else {
    $files = array_keys($files);
}

if ($event == 'diag') {
    require_privs('diag');

    $step = ($step) ? $step : gps('step');

    $diag_levels = array(
        'low'  => gTxt('low'),
        'high' => gTxt('high'),
    );

    $available_steps = array(
        'low'     => true,
        'high'    => true,
        'phpinfo' => true,
    );

    $plugin_steps = array();
    callback_event_ref('diag', 'steps', 0, $plugin_steps);

    // Available steps overwrite custom ones to prevent plugins trampling
    // core routines.
    if ($step && bouncer($step, array_merge($plugin_steps, $available_steps))) {
        if ($step === 'phpinfo') {
            phpinfo();
            exit;
        } elseif (array_key_exists($step, $available_steps)) {
            doDiagnostics();
        } else {
            callback_event($event, $step, 0);
        }
    } else {
        doDiagnostics();
    }
}

/**
 * Checks if the given Apache module is installed and active.
 *
 * @param  string $m The module
 * @return bool|null TRUE on success, NULL or FALSE on error
 */

function apache_module($m)
{
    $modules = @apache_get_modules();

    if (is_array($modules)) {
        return in_array($m, $modules);
    }
}

/**
 * Lists all database tables used by the Textpattern core.
 *
 * Returned tables include prefixes.
 *
 * @return array
 */

function list_txp_tables()
{
    $table_names = array(PFX.'textpattern');
    $rows = getRows("SHOW TABLES LIKE '".PFX."txp\_%'");

    foreach ($rows as $row) {
        $table_names[] = array_shift($row);
    }

    return $table_names;
}

/**
 * Checks the status of the given database tables.
 *
 * @param  array  $tables   The tables to check
 * @param  string $type     Check type, either FOR UPGRADE, QUICK, FAST, MEDIUM, EXTENDED, CHANGED
 * @param  bool   $warnings If TRUE, displays warnings
 * @return array An array of table statuses
 * @example
 * print_r(
 *     check_tables(list_txp_tables())
 * );
 */

function check_tables($tables, $type = 'FAST', $warnings = 0)
{
    $msgs = array();

    foreach ($tables as $table) {
        $rs = getRows("CHECK TABLE `$table` $type");
        if ($rs) {
            foreach ($rs as $r) {
                if ($r['Msg_type'] != 'status' and ($warnings or $r['Msg_type'] != 'warning')) {
                    $msgs[] = $table.cs.$r['Msg_type'].cs.$r['Msg_text'];
                }
            }
        }
    }

    return $msgs;
}

/**
 * Renders a diagnostics message block.
 *
 * @param  string $msg  The message
 * @param  string $type The message type
 * @return string HTML
 * @access private
 */

function diag_msg_wrap($msg, $type = 'e')
{
    $classMap = array(
        'e' => 'error',
        'i' => 'information',
        'w' => 'warning',
    );

    $type = isset($classMap[$type]) ? $type : 'e';

    return span($msg, array('class' => $classMap[$type]));
}

/**
 * Outputs a diagnostics report.
 *
 * This is the main panel.
 */

function doDiagnostics()
{
    global $prefs, $files, $txpcfg, $event, $step, $theme, $DB, $txp_is_dev, $diag_levels, $txp_sections;
    extract(get_prefs());

    $urlparts = parse_url(hu);
    $mydomain = $urlparts['host'];
    $path_to_index = $path_to_site."/index.php";
    $is_apache = stristr(serverSet('SERVER_SOFTWARE'), 'Apache') || is_callable('apache_get_version');
    $real_doc_root = (isset($_SERVER['DOCUMENT_ROOT'])) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';

    $fail = $notReadable = array();
    $now = time();
    $heading = gTxt('tab_diagnostics');
    $isUpdate = defined('TXP_UPDATE_DONE');

    if (!$txp_is_dev) {
        // Check for Textpattern updates, at most once every hour.
        $lastCheck = json_decode(get_pref('last_update_check', ''), true);

        if (empty($lastCheck) || $now > ($lastCheck['when'] + (60 * 60))) {
            $lastCheck = checkUpdates();
        }

        if (isset($lastCheck['response']) && $lastCheck['response'] === false) {
            // Problem connecting to update server.
            $fail['i'][] = array('update_server_inaccessible', $lastCheck['msg']);
        } else {
            if (!empty($lastCheck['msg'])) {
                $txpver = empty($lastCheck['msgval']) ? array('{version}' => '') : $lastCheck['msgval'];
                $fail['i'][] = array('textpattern_version_update', $lastCheck['msg'], $txpver);
            }

            if (!empty($lastCheck['msg2'])) {
                $txpver = empty($lastCheck['msgval2']) ? array('{version}' => '') : $lastCheck['msgval2'];
                $fail['i'][] = array('textpattern_version_update_beta', $lastCheck['msg2'], $txpver);
            }
        }
    }

    if (!is_callable('version_compare') || version_compare(PHP_VERSION, REQUIRED_PHP_VERSION, '<')) {
        $fail['e'][] = array('php_version_required', null, array('{version}' => REQUIRED_PHP_VERSION));
    }

    if (@gethostbyname($mydomain) === $mydomain) {
        $fail['w'][] = array('dns_lookup_fails', null, array('{domain}' => $mydomain));
    }

    if (!@is_dir($path_to_site)) {
        $fail['e'][] = array('path_to_site_inaccessible', 'path_inaccessible', array('{path}' => $path_to_site));
    }

    if (rtrim($siteurl, '/') != $siteurl) {
        $fail['w'][] = array('site_trailing_slash', null, array('{path}' => $path_to_site));
    }

    if (!@is_file($path_to_index) || !@is_readable($path_to_index)) {
        $fail['e'][] = array('index_inaccessible', 'path_inaccessible', array('{path}' => $path_to_index));
    }

    if (!@is_writable($path_to_site.DS.$img_dir)) {
        $notReadable[] = array('{dirtype}' => 'img_dir', '{path}' => $path_to_site.DS.$img_dir);
    }

    if (!@is_writable($file_base_path)) {
        $notReadable[] = array('{dirtype}' => 'file_base_path', '{path}' => $file_base_path);
    }

    if (!@is_writable($path_to_site.DS.$skin_dir)) {
        $notReadable[] = array('{dirtype}' => 'skin_dir', '{path}' => $path_to_site.DS.$skin_dir);
    }

    if (!@is_writable($tempdir)) {
        $notReadable[] = array('{dirtype}' => 'tempdir', '{path}' => $tempdir);
    }

    if (!@is_writable(PLUGINPATH)) {
        $notReadable[] = array('{dirtype}' => 'plugin_dir', '{path}' => PLUGINPATH);
    }

    if ($permlink_mode != 'messy' && $is_apache && !@is_readable($path_to_site.'/.htaccess')) {
        $fail['e'][] = array('htaccess_missing');
    }

    if ($permlink_mode != 'messy' && is_callable('apache_get_modules') && !apache_module('mod_rewrite')) {
        $fail['e'][] = array('mod_rewrite_missing');
    }

    if (!ini_get('file_uploads')) {
        $fail['i'][] = array('file_uploads_disabled');
    }

    if (isset($txpcfg['multisite_root_path'])) {
        $basePath = $txpcfg['multisite_root_path'].DS.'admin';

        if (@is_dir($basePath.DS.'setup') && ($txp_is_dev || !Txp::get('\Textpattern\Admin\Tools')->removeFiles($basePath, 'setup'))) {
            $fail['w'][] = array('setup_still_exists', 'still_exists', array('{path}' => $basePath.DS.'setup'.DS));
        }
    } else {
        if (@is_dir(txpath.DS.'setup') && ($txp_is_dev || !Txp::get('\Textpattern\Admin\Tools')->removeFiles(txpath, 'setup'))) {
            $fail['w'][] = array('setup_still_exists', 'still_exists', array('{path}' => txpath.DS.'setup'.DS));
        }
    }

    if (empty($tempdir)) {
        $fail['w'][] = array('no_temp_dir');
    }

    if (is_disabled('mail')) {
        $fail['e'][] = array('warn_mail_unavailable');
    }

    if ($permlink_mode != 'messy') {
        foreach (array_keys($txp_sections) as $name) {
            if ($name != 'default' && file_exists($path_to_site.DS.$name)) {
                $fail['e'][] = array('old_placeholder_exists', 'old_placeholder', array('{path}' => $path_to_site.DS.$name));
            }
        }
    }

    $cs = check_file_integrity(INTEGRITY_REALPATH);

    if (!$cs) {
        $cs = array();
    }

    // Files that don't match their checksums.
    if (!$txp_is_dev && $modified_files = array_keys($cs, INTEGRITY_MODIFIED)) {
        $fail['w'][] = array('modified_files', null, array('{list}' => n.t.implode(', '.n.t, $modified_files)));
    }

    // Running development code in live mode is not recommended.
    if (preg_match('/-dev$/', txp_version) && $production_status == 'live') {
        $fail['w'][] = array('dev_version_live');
    }

    // Missing files.
    if ($missing = array_merge(
        array_keys($cs, INTEGRITY_MISSING),
        array_keys($cs, INTEGRITY_NOT_FILE),
        array_keys($cs, INTEGRITY_NOT_READABLE)
    )) {
        $fail['e'][] = array('missing_files', null, array('{list}' => n.t.implode(', '.n.t, $missing)));
    }

    // Anything might break if arbitrary functions are disabled.
    if (ini_get('disable_functions')) {
        $disabled_funcs = do_list_unique(ini_get('disable_functions'));
        // Commonly disabled functions that we don't need.
        $disabled_funcs = array_filter(array_diff($disabled_funcs, array(
            'imagefilltoborder',
            'escapeshellarg',
            'escapeshellcmd',
            'exec',
            'passthru',
            'proc_close',
            'proc_get_status',
            'proc_nice',
            'proc_open',
            'proc_terminate',
            'shell_exec',
            'system',
            'popen',
            'dl',
            'chown',
        )), function($func) {return strpos($func, 'pcntl_') !== 0;});

        if ($disabled_funcs) {
            $fail['w'][] = array('some_php_functions_disabled', null, array('{list}' => implode(', ', array_filter($disabled_funcs))));
        }
    }

    // Not sure about this one.
//    if (strncmp(php_sapi_name(), 'cgi', 3) == 0 and ini_get('cgi.rfc2616_headers'))
//    $fail['cgi_header_config'] = gTxt('cgi_header_config');

    $guess_site_url = $_SERVER['HTTP_HOST'].preg_replace('#[/\\\\]$#', '', dirname(dirname($_SERVER['SCRIPT_NAME'])));

    if ($siteurl && strip_prefix($siteurl, 'www.') != strip_prefix($guess_site_url, 'www.')) {
        // Skip warning if multi-site setup, as $guess_site_url and $siteurl will mismatch.
        if (!isset($txpcfg['multisite_root_path'])) {
            $fail['w'][] = array('site_url_mismatch', null, array('{url}' => $guess_site_url));
        }
    }

    // Test clean URL server vars.
    if (hu) {
        if (ini_get('allow_url_fopen') && ($permlink_mode != 'messy')) {
            $s = md5(uniqid(rand(), true));
            ini_set('default_socket_timeout', 10);

            $pretext_data = @file(hu.$s.'/?txpcleantest=1');

            if ($pretext_data) {
                $pretext_req = trim(@$pretext_data[0]);

                if ($pretext_req != md5('/'.$s.'/?txpcleantest=1')) {
                    $fail['w'][] = array('clean_url_data_failed', null, array('{data}' => txpspecialchars($pretext_req)));
                }
            } else {
                $fail['w'][] = array('clean_url_test_failed');
            }
        }
    }

    if ($tables = list_txp_tables()) {
        $table_errors = check_tables($tables);

        if ($table_errors) {
            $fail['e'][] = array('mysql_table_errors', null, array('{list}' => n.t.implode(', '.n.t, $table_errors)));
        }
    }

    $active_plugins = array();

    if (!$use_plugins && !$admin_side_plugins) {
        $showTypes = '2';
    } elseif (!$admin_side_plugins) {
        $showTypes = '0, 2, 5';
    } elseif (!$use_plugins) {
        $showTypes = '1, 2, 3, 4, 5';
    } else {
        $showTypes = '0, 1, 2, 3, 4, 5';
    }

    if ($rows = safe_rows("name, version, code_md5, MD5(code) AS md5", 'txp_plugin', "status > 0 AND type IN (".$showTypes.") ORDER BY name")) {
        foreach ($rows as $row) {
            $n = $row['name'].'-'.$row['version'];

            if (strtolower($row['md5']) != strtolower($row['code_md5'])) {
                $n .= ' ('.gTxt('diag_modified').')';
            }

            $active_plugins[] = $n;
        }
    }

    $theme_manifest = $theme->manifest();

    // Check GD info.
    if (function_exists('gd_info')) {
        $gd_info = gd_info();

        $gd_support = array();

        if ($gd_info['GIF Create Support']) {
            $gd_support[] = 'GIF';
        }

        if ($gd_info['JPEG Support']) {
            $gd_support[] = 'JPEG';
        }

        if ($gd_info['PNG Support']) {
            $gd_support[] = 'PNG';
        }

        if (!empty($gd_info['WebP Support'])) {
            $gd_support[] = 'WebP';
        }

        if (!empty($gd_info['AVIF Support'])) {
            $gd_support[] = 'AVIF';
        }

        if ($gd_support) {
            $gd_support = implode(', ', $gd_support);
        } else {
            $gd_support = gTxt('diag_none');
        }

        $gd = gTxt('diag_gd_info', array(
            '{version}'   => $gd_info['GD Version'],
            '{supported}' => $gd_support,
        ));
    } else {
        $gd = gTxt('diag_unavailable');
    }

    $intl = extension_loaded('intl') ? phpversion('intl') : gTxt('diag_unavailable');
    $mbstring = extension_loaded('mbstring') ? phpversion('mbstring') : gTxt('diag_unavailable');

    if (realpath($prefs['tempdir']) === realpath($prefs['plugin_cache_dir'])) {
        $fail['e'][] = array('tmp_plugin_paths_match');
    }

    // Database server time.
    extract(doSpecial(getRow("SELECT @@global.time_zone AS db_global_timezone, @@session.time_zone AS db_session_timezone, NOW() AS db_server_time, UNIX_TIMESTAMP(NOW()) AS db_server_timestamp")));
    $db_server_timeoffset = $db_server_timestamp - $now;

    echo pagetop(gTxt('tab_diagnostics'), '');

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed($heading, 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        ).
        n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => $event.'_container',
        )).
        n.tag_start('div', array('id' => 'pre_flight_check')).
        hed(gTxt('preflight_check'), 2);

    $thisLang = get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);
    $siteLang = get_pref('language', TEXTPATTERN_DEFAULT_LANG);
    $langs = array_unique(array('en', $thisLang));
    $pfcStrings = array();
    $langCounter = 0;
    $txpLang = Txp::get('\Textpattern\L10n\Lang');

    foreach ($langs as $lang) {
        // Overwrite the lang strings to English, then revert on second pass.
        // This allows the pre-flight check to be displayed in the local
        // language above the fold, and in English in the textarea.
        $diagPack = $txpLang->getPack($lang, array($event, 'admin-side'));
        $diagStrings = array();
        $showPophelp = count($langs) === 1 || $langCounter > 0;

        foreach ($diagPack as $key => $packBlock) {
            $diagStrings[$key] = $packBlock['data'];
        }

        $txpLang->setPack($diagStrings, true);
        $not_readable = array();

        foreach ($notReadable as $strings) {
            $strings['{dirtype}'] = gTxt($strings['{dirtype}']);
            $not_readable[] = diag_msg_wrap(gTxt('dir_not_writable', $strings));
        }

        if ($fail || $not_readable) {
            foreach ($fail as $type => $content) {
                foreach ($content as $stringInfo) {
                    $help = $stringInfo[0];
                    $message = !empty($stringInfo[1]) ? $stringInfo[1] : $help;
                    $args = isset($stringInfo[2]) ? $stringInfo[2] : array();
                    $pfcStrings[$lang][] = array(
                        'msg'  => nl2br(diag_msg_wrap(gTxt($message, $args), $type)),
                        'help' => ($showPophelp ? popHelp($help) : ''),
                        'type' => array(),
                    );
                }
            }

            if ($not_readable) {
                foreach ($not_readable as $nr) {
                    $pfcStrings[$lang][] = array(
                        'msg'  => nl2br($nr),
                        'help' => ($showPophelp ? popHelp('dir_not_writable') : ''),
                        'type' => array(),
                    );
                }
            }
        } else {
            $pfcStrings[$lang][] = array(
                'msg' => '<span class="ui-icon ui-icon-check"></span>'.sp.gTxt('all_checks_passed'),
                'help' => '',
                'type' => array('class' => 'success')
            );
        }

        $langCounter++;
    }

    // The lang will now be back to the local lingo so we can use $lang
    // to display the correct pre-flight check.
    foreach ($pfcStrings[$lang] as $preflight) {
        echo n.graf($preflight['msg'].$preflight['help'], $preflight['type']);
    }

    // End of #pre_flight_check.
    echo n.tag_end('div');

    $out = array();

    echo n.tag_start('div', array('id' => 'diagnostics')).
        hed(gTxt('diagnostic_info'), 2);

    $fmt_date = 'Y-m-d H:i:s';
    $updateTime = ($dbupdatetime) ? gmdate($fmt_date, $dbupdatetime).'/' : '';

    $out = array(
        form(
            eInput('diag').
            href(gTxt('php_diagnostics').sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), array(
                'event'      => 'diag',
                'step'       => 'phpinfo',
                '_txp_token' => form_token(),
            ), array(
                'rel'    => 'external noopener',
                'target' => '_blank',
            )).
            pluggable_ui(
                'diag_ui',
                'level',
                inputLabel(
                    'diag_detail_level',
                    selectInput('step', $diag_levels, $step, 0, 1, 'diag_detail_level'),
                    'detail',
                    '',
                    array('class' => 'txp-form-field diagnostic-details-level'),
                    ''
                ),
                $diag_levels
            ).
            inputLabel(
                'diag_clear_private',
                checkbox('diag_clear_private', 1, false, 0, 'diag_clear_private'),
                'diag_clear_private', 'diag_clear_private', array('class' => 'txp-form-field'),
                ''
            )
        ),

        '<textarea class="code" id="diagnostics-detail" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr" readonly>',
        '</textarea>',

        (isset($txpcfg['multisite_root_path']))
        ? '<textarea class="code ui-helper-hidden" id="diagnostics-data" cols="'.INPUT_LARGE.'" data-txproot="'.dirname(dirname($txpcfg['multisite_root_path'])).'" dir="ltr" readonly>'
        : '<textarea class="code ui-helper-hidden" id="diagnostics-data" cols="'.INPUT_LARGE.'" data-txproot="'.dirname(txpath).'" dir="ltr" readonly>',

        gTxt('diag_txp_version').cs.txp_version.' ('.check_file_integrity(INTEGRITY_DIGEST).')'.n,

        gTxt('diag_last_update').cs.$updateTime.gmdate($fmt_date, @filemtime(txpath.'/update/_update.php')).n,

        priv.gTxt('diag_web_domain').cs.$siteurl.n,

        (defined('ahu')) ? priv.gTxt('diag_admin_url').cs.rtrim(preg_replace('|^https?://|', '', ahu), '/').n : '',

        (!empty($txpcfg['cookie_domain'])) ? priv.gTxt('diag_cookie_domain').cs.cookie_domain.n : '',

        priv.gTxt('diag_document_root').cs.@$_SERVER['DOCUMENT_ROOT'].(($real_doc_root != @$_SERVER['DOCUMENT_ROOT']) ? ' ('.$real_doc_root.')' : '').n,

        (isset($txpcfg['multisite_root_path'])) ? gTxt('diag_multisite_root_path').cs.$txpcfg['multisite_root_path'].n : '',

        priv.'$path_to_site'.cs.$path_to_site.n,

        gTxt('diag_txp_path').cs.txpath.n,

        gTxt('diag_permlink_mode').cs.$permlink_mode.n,

        gTxt('diag_production_status').cs.$production_status.n,

        (ini_get('open_basedir')) ? 'open_basedir'.cs.ini_get('open_basedir').n : '',

        (ini_get('upload_tmp_dir')) ? 'upload_tmp_dir'.cs.ini_get('upload_tmp_dir').n : '',

        gTxt('diag_tempdir').cs.$tempdir.n,

        gTxt('diag_php_version').cs.phpversion().n,

        gTxt('diag_gd_library').cs.$gd.n,

        gTxt('diag_intl_extension').cs.$intl.n,

        gTxt('diag_mbstring_extension').cs.$mbstring.n,

        gTxt('diag_server_timezone').cs.Txp::get('\Textpattern\Date\Timezone')->getTimeZone().n,

        gTxt('diag_server_time').cs.date('Y-m-d H:i:s').n,

        strip_tags(gTxt('diag_is_dst')).cs.$is_dst.n,

        strip_tags(gTxt('diag_auto_dst')).cs.$auto_dst.n,

        strip_tags(gTxt('diag_gmtoffset')).cs.$timezone_key.sp."($gmtoffset)".n,

        'MySQL'.cs.$DB->version.' ('.getThing('SELECT @@GLOBAL.version_comment').') '.n,

        gTxt('diag_db_server_time').cs.$db_server_time.n,

        gTxt('diag_db_server_timeoffset').cs.$db_server_timeoffset.' s'.n,

        gTxt('diag_db_global_timezone').cs.$db_global_timezone.n,

        gTxt('diag_db_session_timezone').cs.$db_session_timezone.n,

        gTxt('diag_locale').cs.$locale.n,

        gTxt('diag_languages', array('{site_lang}' => $siteLang, '{admin_lang}' => $thisLang)).n,

        (isset($_SERVER['SERVER_SOFTWARE'])) ? gTxt('diag_web_server').cs.$_SERVER['SERVER_SOFTWARE'].n : '',

        (is_callable('apache_get_version')) ? gTxt('diag_apache_version').cs.@apache_get_version().n : '',

        gTxt('diag_php_sapi_mode').cs.PHP_SAPI.n,

        gTxt('diag_ssl_version').cs.OPENSSL_VERSION_TEXT.n,

        gTxt('diag_rfc2616_headers').cs.ini_get('cgi.rfc2616_headers').n,

        gTxt('diag_server_os_version').cs.php_uname('s').' '.php_uname('r').($step === 'high' ? ' '.php_uname('v').' '.php_uname('m') : '').n,

        gTxt('diag_theme_name').cs.$theme_name.sp.@$theme_manifest['version'].n,

        ($active_plugins ? gTxt('diag_active_plugins').cs.n.t.implode(n.t, $active_plugins).n : ''),

        ($fail || $not_readable)
        ? n.gTxt('diag_preflight_check').cs.n.ln.implode(n, doStripTags(array_column($pfcStrings['en'], 'msg'))).n.ln
        : '',

        ($is_apache && is_readable($path_to_site.'/.htaccess'))
        ? n.gTxt('diag_htaccess_contents').cs.n.ln.txpspecialchars(implode('', file($path_to_site.'/.htaccess'))).n.ln
        : '',
    );

    if ($step == 'high') {
        $lastCheck = json_decode(get_pref('last_update_check', ''), true);

        if (!empty($lastCheck['msg']) || !empty($lastCheck['msg2'])) {
            $relmain = empty($lastCheck['msgval']) ? array('{version}' => '') : $lastCheck['msgval'];
            $relbeta = empty($lastCheck['msgval2']) ? array('{version}' => '') : $lastCheck['msgval2'];
            $msgmain = empty($lastCheck['msg']) ? '' : strip_tags(gTxt($lastCheck['msg'], $relmain));
            $msgbeta = empty($lastCheck['msg2']) ? '' : strip_tags(gTxt($lastCheck['msg2'], $relbeta));
            $out[] = n.gTxt('diag_last_update_check').cs.date('Y-m-d H:i:s', $lastCheck['when']).', '.$msgmain.' '.$msgbeta.n;
        }

        $out[] = n.gTxt('diag_db_charset').cs.$DB->default_charset.'/'.$DB->charset.n;

        $result = safe_query("SHOW variables WHERE Variable_name LIKE 'character_se%' OR Variable_name LIKE 'collation%'");

        while ($row = mysqli_fetch_row($result)) {
            $out[] = $row[0].cs.$row[1].n;

            if ($row[0] == 'character_set_connection') {
                $conn_char = $row[1];
            }
        }

        $table_names = array(PFX.'textpattern');
        $result = safe_query("SHOW TABLES LIKE '".PFX."txp\_%'");

        while ($row = mysqli_fetch_row($result)) {
            $table_names[] = $row[0];
        }

        $table_msg = array();

        foreach ($table_names as $table) {
            $ctr = safe_query("SHOW CREATE TABLE $table");

            if (!$ctr) {
                unset($table_names[$table]);
                continue;
            }

            $row = mysqli_fetch_assoc($ctr);
            $ctcharset = preg_replace('#^CREATE TABLE.*SET=([^ ]+)[^)]*$#is', '\\1', $row['Create Table']);

            if (isset($conn_char) && !stristr($ctcharset, 'CREATE') && ($conn_char != $ctcharset)) {
                $table_msg[] = "$table is $ctcharset";
            }

            $ctr = safe_query("CHECK TABLE $table");
            $row = mysqli_fetch_assoc($ctr);

            if (in_array($row['Msg_type'], array('error', 'warning'))) {
                $table_msg[] = $table.cs.$row['Msg_Text'];
            }
        }

        if ($table_msg == array()) {
            $table_msg = (count($table_names) < 17) ?  array('-') : array('OK');
        }

        $out[] = count($table_names).sp.gTxt('diag_db_tables').cs.implode(', ', $table_msg).n;

        $cf = preg_grep('/^custom_\d+/', getThings("DESCRIBE `".PFX."textpattern`"));
        $out[] = n.get_pref('max_custom_fields', 10).sp.gTxt('diag_custom').cs.
                    implode(', ', $cf).sp.'('.count($cf).')'.n;

        $extns = get_loaded_extensions();
        $extv = array();

        foreach ($extns as $e) {
            $extv[] = $e.(phpversion($e) ? '/'.phpversion($e) : '');
        }

        if (is_callable('apache_get_modules')) {
            $out[] = n.gTxt('diag_apache_modules').cs.implode(', ', apache_get_modules()).n;
        }

        if (isset($pretext_data) && is_array($pretext_data) and count($pretext_data) > 1) {
            $out[] = n.gTxt('diag_pretext_data').cs.txpspecialchars(implode('', array_slice($pretext_data, 1, 20))).n;
        }

        $out[] = n;

        if ($md5s = check_file_integrity(INTEGRITY_MD5)) {
            foreach ($md5s as $f => $checksum) {
                $out[] = $f.cs.n.t.(!$checksum ? gTxt('diag_unknown') : $checksum).n;
            }
        }

        $out[] = n.ln;
    }

    $out[] = callback_event('diag_results', $step).n;
    $out[] = '</textarea>';

    echo implode('', $out),
        n.tag_end('div'). // End of #diagnostics.
        n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.;
}

/**
 * Checks for Textpattern updates.
 *
 * @return  array|null When updates are found returns an array consisting keys 'version', 'msg'
 * @example
 * if ($updates = checkUpdates())
 * {
 *     echo "New version: {$updates['version']}";
 * }
 */

function checkUpdates()
{
    $endpoint = 'https://textpattern.com/version.json';
    $release = $prerelease = null;
    $lastCheck = array(
        'when'     => time(),
        'msg'      => '',
        'msg2'     => '',
        'msgval'   => array(),
        'msgval2'  => array(),
        'response' => true,
    );

    if (OPENSSL_VERSION_NUMBER < REQUIRED_OPENSSL_VERSION) {
        $lastCheck['msg'] = 'problem_connecting_update_server';
        $lastCheck['response'] = false;
    } else {
        if (function_exists('curl_version')) {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $contents = curl_exec($ch);
        } else {
            $contents = file_get_contents($endpoint);
        }

        $response = @json_decode($contents, true);

        if (isset($response['textpattern-version'])) {
            $release = $response['textpattern-version']['release'];
            $prerelease = $response['textpattern-version']['prerelease'];
        }

        $version = get_pref('version');

        if (!empty($release)) {
            if (version_compare($version, $release) < 0) {
                $lastCheck['msg'] = 'textpattern_update_available';
                $lastCheck['msgval'] = array('{version}' => $release);
            }

            if (version_compare($version, $prerelease) < 0) {
                $lastCheck['msg2'] = 'textpattern_update_available_beta';
                $lastCheck['msgval2'] = array('{version}' => $prerelease);
            }
        } else {
            $lastCheck['msg'] = 'problem_connecting_update_server';
            $lastCheck['response'] = false;
        }
    }

    set_pref('last_update_check', json_encode($lastCheck, TEXTPATTERN_JSON), 'publish', PREF_HIDDEN, 'text_input');

    return $lastCheck;
}

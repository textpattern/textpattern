<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
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

global $files;

$files = check_file_integrity();

if (!$files) {
    $files = array();
} else {
    $files = array_keys($files);
}

if ($event == 'diag') {
    require_privs('diag');

    $step = gps('step');
    doDiagnostics();
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
 * Verifies temporary directory status.
 *
 * This function verifies that the given temporary directory is writeable.
 *
 * @param  string $dir The directory to check
 * @return bool|null NULL on error, TRUE on success
 */

function test_tempdir($dir)
{
    $f = realpath(tempnam($dir, 'txp_'));

    if (is_file($f)) {
        @unlink($f);

        return true;
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

function diag_msg_wrap($msg, $type = 'error')
{
    return span($msg, array('class' => $type));
}

/**
 * Outputs a diagnostics report.
 *
 * This is the main panel.
 */

function doDiagnostics()
{
    global $prefs, $files, $txpcfg, $event, $step, $theme, $DB, $txp_using_svn;
    extract(get_prefs());

    $urlparts = parse_url(hu);
    $mydomain = $urlparts['host'];

    $is_apache = stristr(serverSet('SERVER_SOFTWARE'), 'Apache') || is_callable('apache_get_version');
    $real_doc_root = (isset($_SERVER['DOCUMENT_ROOT'])) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';

    // ini_get() returns string values passed via php_value as a string,
    // not boolean.
    $is_register_globals = ((strcasecmp(ini_get('register_globals'), 'on') === 0) or (ini_get('register_globals') === '1'));

    $fail = array();
    $now = time();

    if (!$txp_using_svn) {
        // Check for Textpattern updates, at most once every 24 hours.
        $updateInfo = unserialize(get_pref('last_update_check', ''));

        if (!$updateInfo || ($now > ($updateInfo['when'] + (60 * 60 * 24)))) {
            $updates = checkUpdates();
            $updateInfo['msg'] = ($updates) ? gTxt($updates['msg'], array('{version}' => $updates['version'])) : '';
            $updateInfo['when'] = $now;
            set_pref('last_update_check', serialize($updateInfo), 'publish', PREF_HIDDEN, 'text_input');
        }

        if (!empty($updateInfo['msg'])) {
            $fail['textpattern_version_update'] = diag_msg_wrap($updateInfo['msg'], 'information');
        }
    }

    if (!is_callable('version_compare') || version_compare(PHP_VERSION, REQUIRED_PHP_VERSION, '<')) {
        $fail['php_version_required'] = diag_msg_wrap(gTxt('php_version_required', array('{version}' => REQUIRED_PHP_VERSION)));
    }

    if (@gethostbyname($mydomain) === $mydomain) {
        $fail['dns_lookup_fails'] = diag_msg_wrap(gTxt('dns_lookup_fails').cs.$mydomain, 'warning');
    }

    if (!@is_dir($path_to_site)) {
        $fail['path_to_site_inacc'] = diag_msg_wrap(gTxt('path_to_site_inacc').cs.$path_to_site);
    }

    if (rtrim($siteurl, '/') != $siteurl) {
        $fail['site_trailing_slash'] = diag_msg_wrap(gTxt('site_trailing_slash').cs.$path_to_site, 'warning');
    }

    if (!@is_file($path_to_site."/index.php") || !@is_readable($path_to_site."/index.php")) {
        $fail['index_inaccessible'] = diag_msg_wrap("{$path_to_site}/index.php ".gTxt('is_inaccessible'));
    }

    $not_readable = array();

    if (!@is_writable($path_to_site.'/'.$img_dir)) {
        $not_readable[] = diag_msg_wrap(str_replace('{dirtype}', gTxt('img_dir'), gTxt('dir_not_writable')).": {$path_to_site}/{$img_dir}", 'warning');
    }

    if (!@is_writable($file_base_path)) {
        $not_readable[] = diag_msg_wrap(str_replace('{dirtype}', gTxt('file_base_path'), gTxt('dir_not_writable')).": {$file_base_path}", 'warning');
    }

    if (!@is_writable($tempdir)) {
        $not_readable[] = diag_msg_wrap(str_replace('{dirtype}', gTxt('tempdir'), gTxt('dir_not_writable')).": {$tempdir}", 'warning');
    }

    if ($not_readable) {
        $fail['dir_not_writable'] = join(n, $not_readable);
    }

    if ($permlink_mode != 'messy' && !$is_apache) {
        $fail['cleanurl_only_apache'] = diag_msg_wrap(gTxt('cleanurl_only_apache'), 'information');
    }

    if ($permlink_mode != 'messy' and !@is_readable($path_to_site.'/.htaccess')) {
        $fail['htaccess_missing'] = diag_msg_wrap(gTxt('htaccess_missing'));
    }

    if ($permlink_mode != 'messy' and is_callable('apache_get_modules') and !apache_module('mod_rewrite')) {
        $fail['mod_rewrite_missing'] = diag_msg_wrap(gTxt('mod_rewrite_missing'));
    }

    if (!ini_get('file_uploads')) {
        $fail['file_uploads_disabled'] = diag_msg_wrap(gTxt('file_uploads_disabled'), 'information');
    }

    if (@is_dir(txpath.DS.'setup')) {
        $fail['setup_still_exists'] = diag_msg_wrap(txpath.DS."setup".DS.' '.gTxt('still_exists'), 'warning');
    }

    if (empty($tempdir)) {
        $fail['no_temp_dir'] = diag_msg_wrap(gTxt('no_temp_dir'), 'warning');
    }

    if (is_disabled('mail')) {
        $fail['warn_mail_unavailable'] = diag_msg_wrap(gTxt('warn_mail_unavailable'), 'warning');
    }

    if ($is_register_globals) {
        $fail['warn_register_globals_or_update'] = diag_msg_wrap(gTxt('warn_register_globals_or_update'), 'warning');
    }

    if ($permlink_mode != 'messy') {
        $rs = safe_column("name", 'txp_section', "1 = 1");

        foreach ($rs as $name) {
            if ($name and @file_exists($path_to_site.'/'.$name)) {
                $fail['old_placeholder_exists'] = diag_msg_wrap(gTxt('old_placeholder').": {$path_to_site}/{$name}");
            }
        }
    }

    $cs = check_file_integrity(INTEGRITY_REALPATH);

    if (!$cs) {
        $cs = array();
    }

    // Files that don't match their checksums.
    if (!$txp_using_svn and $modified_files = array_keys($cs, INTEGRITY_MODIFIED)) {
        $fail['modified_files'] = diag_msg_wrap(gTxt('modified_files').cs.n.t.join(', '.n.t, $modified_files), 'warning');
    }

    // Running development code in live mode is not recommended.
    if (preg_match('/-dev$/', txp_version) and $production_status == 'live') {
        $fail['dev_version_live'] = diag_msg_wrap(gTxt('dev_version_live'), 'warning');
    }

    // Missing files.
    if ($missing = array_merge(
        array_keys($cs, INTEGRITY_MISSING),
        array_keys($cs, INTEGRITY_NOT_FILE),
        array_keys($cs, INTEGRITY_NOT_READABLE)
    )) {
        $fail['missing_files'] = diag_msg_wrap(gTxt('missing_files').cs.n.t.join(', '.n.t, $missing));
    }

    // Anything might break if arbitrary functions are disabled.
    if (ini_get('disable_functions')) {
        $disabled_funcs = array_map('trim', explode(',', ini_get('disable_functions')));
        // Commonly disabled functions that we don't need.
        $disabled_funcs = array_diff($disabled_funcs, array(
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
        ));

        if ($disabled_funcs) {
            $fail['some_php_functions_disabled'] = diag_msg_wrap(gTxt('some_php_functions_disabled').cs.join(', ', $disabled_funcs), 'warning');
        }
    }

    // Not sure about this one.
//    if (strncmp(php_sapi_name(), 'cgi', 3) == 0 and ini_get('cgi.rfc2616_headers'))
//    $fail['cgi_header_config'] = gTxt('cgi_header_config');

    $guess_site_url = $_SERVER['HTTP_HOST'].preg_replace('#[/\\\\]$#', '', dirname(dirname($_SERVER['SCRIPT_NAME'])));

    if ($siteurl and strip_prefix($siteurl, 'www.') != strip_prefix($guess_site_url, 'www.')) {
        $fail['site_url_mismatch'] = diag_msg_wrap(gTxt('site_url_mismatch').cs.$guess_site_url, 'warning');
    }

    // Test clean URL server vars.
    if (hu) {
        if (ini_get('allow_url_fopen') and ($permlink_mode != 'messy')) {
            $s = md5(uniqid(rand(), true));
            ini_set('default_socket_timeout', 10);

            $pretext_data = @file(hu.$s.'/?txpcleantest=1');

            if ($pretext_data) {
                $pretext_req = trim(@$pretext_data[0]);

                if ($pretext_req != md5('/'.$s.'/?txpcleantest=1')) {
                    $fail['clean_url_data_failed'] = diag_msg_wrap(gTxt('clean_url_data_failed').cs.txpspecialchars($pretext_req), 'warning');
                }
            } else {
                $fail['clean_url_test_failed'] = diag_msg_wrap(gTxt('clean_url_test_failed'), 'warning');
            }
        }
    }

    if ($tables = list_txp_tables()) {
        $table_errors = check_tables($tables);
        if ($table_errors) {
            $fail['mysql_table_errors'] = diag_msg_wrap(gTxt('mysql_table_errors').cs.n.t.join(', '.n.t, $table_errors));
        }
    }

    $active_plugins = array();
    if ($rows = safe_rows("name, version, code_md5, MD5(code) AS md5", 'txp_plugin', "status > 0")) {
        foreach ($rows as $row) {
            $n = $row['name'].'-'.$row['version'];

            if (strtolower($row['md5']) != strtolower($row['code_md5'])) {
                $n .= 'm';
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

         // Aside: In PHP 5.3, they chose to add a previously unemployed capital
         // "E" to the array key.
        if (!empty($gd_info['JPEG Support']) || !empty($gd_info['JPG Support'])) {
            $gd_support[] = 'JPG';
        }

        if ($gd_info['PNG Support']) {
            $gd_support[] = 'PNG';
        }

        if ($gd_support) {
            $gd_support = join(', ', $gd_support);
        } else {
            $gd_support = gTxt('none');
        }

        $gd = gTxt('gd_info', array(
            '{version}'   => $gd_info['GD Version'],
            '{supported}' => $gd_support,
        ));
    } else {
        $gd = gTxt('gd_unavailable');
    }

    if (realpath($prefs['tempdir']) === realpath($prefs['plugin_cache_dir'])) {
        $fail['tmp_plugin_paths_match'] = diag_msg_wrap(gTxt('tmp_plugin_paths_match'));
    }

    // Database server time.
    extract(doSpecial(getRow("SELECT @@global.time_zone AS db_global_timezone, @@session.time_zone AS db_session_timezone, NOW() AS db_server_time, UNIX_TIMESTAMP(NOW()) AS db_server_timestamp")));
    $db_server_timeoffset = $db_server_timestamp - $now;

    echo pagetop(gTxt('tab_diagnostics'), '');

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_diagnostics'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        ).
        n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => $event.'_container',
        )).
        n.tag_start('div', array('id' => 'pre_flight_check')).
        hed(gTxt('preflight_check'), 2);

    if ($fail) {
        foreach ($fail as $help => $message) {
            echo graf(nl2br($message).popHelp($help));
        }
    } else {
        echo graf(diag_msg_wrap(gTxt('all_checks_passed'), 'success'));
    }

    echo n.tag_end('div'). // End of #pre_flight_check.
        n.tag_start('div', array('id' => 'diagnostics')).
        hed(gTxt('diagnostic_info'), 2);

    $fmt_date = '%Y-%m-%d %H:%M:%S';

    $dets = array(
        'low'  => gTxt('low'),
        'high' => gTxt('high'),
    );

    $out = array(
        form(
            eInput('diag').
            inputLabel(
                'diag_detail_level',
                selectInput('step', $dets, $step, 0, 1, 'diag_detail_level'),
                'detail',
                '',
                array('class' => 'txp-form-field diagnostic-details-level'),
                ''
            )
        ),

        '<textarea class="code" id="diagnostics-detail" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr" readonly>',

        gTxt('txp_version').cs.txp_version.' ('.check_file_integrity(INTEGRITY_DIGEST).')'.n,

        gTxt('last_update').cs.gmstrftime($fmt_date, $dbupdatetime).'/'.gmstrftime($fmt_date, @filemtime(txpath.'/update/_update.php')).n,

        gTxt('document_root').cs.@$_SERVER['DOCUMENT_ROOT'].(($real_doc_root != @$_SERVER['DOCUMENT_ROOT']) ? ' ('.$real_doc_root.')' : '').n,

        '$path_to_site'.cs.$path_to_site.n,

        gTxt('txp_path').cs.txpath.n,

        gTxt('permlink_mode').cs.$permlink_mode.n,

        (ini_get('open_basedir')) ? 'open_basedir: '.ini_get('open_basedir').n : '',

        (ini_get('upload_tmp_dir')) ? 'upload_tmp_dir: '.ini_get('upload_tmp_dir').n : '',

        gTxt('tempdir').cs.$tempdir.n,

        gTxt('web_domain').cs.$siteurl.n,

        gTxt('php_version').cs.phpversion().n,

        ($is_register_globals) ? gTxt('register_globals').cs.$is_register_globals.n : '',

        gTxt('gd_library').cs.$gd.n,

        gTxt('server').' TZ: '.Txp::get('\Textpattern\Date\Timezone')->getTimeZone().n,
        gTxt('server_time').cs.strftime('%Y-%m-%d %H:%M:%S').n,
        strip_tags(gTxt('is_dst')).cs.$is_dst.n,
        strip_tags(gTxt('auto_dst')).cs.$auto_dst.n,
        strip_tags(gTxt('gmtoffset')).cs.$timezone_key.sp."($gmtoffset)".n,

        'MySQL'.cs.mysqli_get_server_info($DB->link).n,
        gTxt('db_server_time').cs.$db_server_time.n,
        gTxt('db_server_timeoffset').cs.$db_server_timeoffset.' s'.n,
        gTxt('db_global_timezone').cs.$db_global_timezone.n,
        gTxt('db_session_timezone').cs.$db_session_timezone.n,

        gTxt('locale').cs.$locale.n,

        (isset($_SERVER['SERVER_SOFTWARE'])) ? gTxt('server').cs.$_SERVER['SERVER_SOFTWARE'].n : '',

        (is_callable('apache_get_version')) ? gTxt('apache_version').cs.@apache_get_version().n : '',

        gTxt('php_sapi_mode').cs.PHP_SAPI.n,

        gTxt('rfc2616_headers').cs.ini_get('cgi.rfc2616_headers').n,

        gTxt('os_version').cs.php_uname('s').' '.php_uname('r').n,

        ($active_plugins ? gTxt('active_plugins').cs.join(', ', $active_plugins).n : ''),

        gTxt('theme_name').cs.$theme_name.sp.$theme_manifest['version'].n,

        $fail
        ? n.gTxt('preflight_check').cs.n.ln.join("\n", doStripTags($fail)).n.ln
        : '',

        (is_readable($path_to_site.'/.htaccess'))
        ?    n.gTxt('htaccess_contents').cs.n.ln.txpspecialchars(join('', file($path_to_site.'/.htaccess'))).n.ln
        :    '',
    );

    if ($step == 'high') {
        $out[] = n.'Charset (default/config)'.cs.$DB->default_charset.'/'.$DB->charset.n;

        $result = safe_query("SHOW variables LIKE 'character_se%'");

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

        $out[] = count($table_names).' Tables'.cs.implode(', ', $table_msg).n;

        $cf = preg_grep('/^custom_\d+/', getThings("DESCRIBE `".PFX."textpattern`"));
        $out[] = n.get_pref('max_custom_fields', 10).sp.gTxt('custom').cs.
                    implode(', ', $cf).sp.'('.count($cf).')'.n;

        $extns = get_loaded_extensions();
        $extv = array();

        foreach ($extns as $e) {
            $extv[] = $e.(phpversion($e) ? '/'.phpversion($e) : '');
        }

        $out[] = n.gTxt('php_extensions').cs.join(', ', $extv).n;

        if (is_callable('apache_get_modules')) {
            $out[] = n.gTxt('apache_modules').cs.join(', ', apache_get_modules()).n;
        }

        if (@is_array($pretext_data) and count($pretext_data) > 1) {
            $out[] = n.gTxt('pretext_data').cs.txpspecialchars(join('', array_slice($pretext_data, 1, 20))).n;
        }

        $out[] = n;

        if ($md5s = check_file_integrity(INTEGRITY_MD5)) {
            foreach ($md5s as $f => $checksum) {
                $out[] = $f.cs.n.t.(!$checksum ? gTxt('unknown') : $checksum).n;
            }
        }

        $out[] = n.ln;
    }

    $out[] = callback_event('diag_results', $step).n;
    $out[] = '</textarea>';

    echo join('', $out),
        n.tag_end('div'). // End of #diagnostics.
        n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.;
}

/**
 * Checks for Textpattern updates.
 *
 * This function uses XML-RPC to do an active remote connection to
 * rpc.textpattern.com. Created connections are not cached, scheduled or
 * delayed, and each subsequent call to the function creates a new connection.
 *
 * These connections do not transmit any identifiable information. Just an
 * anonymous UID assigned for the installation on the first run.
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
    require_once txpath.'/lib/IXRClass.php';
    $client = new IXR_Client('http://rpc.textpattern.com');

    if (!$client->query('tups.getTXPVersion', get_pref('blog_uid'))) {
        return array('version' => 0, 'msg' => 'problem_connecting_rpc_server');
    } else {
        $out = array();
        $response = $client->getResponse();

        if (is_array($response)) {
            ksort($response);
            $version = get_pref('version');

            // Go through each available branch (x.y), but only return the
            // _highest_ version.
            foreach ($response as $key => $val) {
                if (version_compare($version, $val) < 0) {
                    $out = array('version' => $val, 'msg' => 'textpattern_update_available');
                }
            }

            return $out;
        }
    }
}

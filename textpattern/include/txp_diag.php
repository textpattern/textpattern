<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

//-------------------------------------------------------------

	define("cs",': ');
	define("ln",str_repeat('-', 24).n);

	global $files;

	$files = array(
		'/../index.php',
		'/css.php',
		'/include/txp_admin.php',
		'/include/txp_article.php',
		'/include/txp_auth.php',
		'/include/txp_category.php',
		'/include/txp_css.php',
		'/include/txp_diag.php',
		'/include/txp_discuss.php',
		'/include/txp_file.php',
		'/include/txp_form.php',
		'/include/txp_image.php',
		'/include/txp_import.php',
		'/include/txp_link.php',
		'/include/txp_list.php',
		'/include/txp_log.php',
		'/include/txp_page.php',
		'/include/txp_plugin.php',
		'/include/txp_prefs.php',
		'/include/txp_preview.php',
		'/include/txp_section.php',
		'/include/txp_tag.php',
		'/index.php',
		'/jquery.js',
		'/lib/IXRClass.php',
		'/lib/admin_config.php',
		'/lib/class.thumb.php',
		'/lib/classTextile.php',
		'/lib/constants.php',
		'/lib/taglib.php',
		'/lib/txplib_admin.php',
		'/lib/txplib_db.php',
		'/lib/txplib_forms.php',
		'/lib/txplib_head.php',
		'/lib/txplib_html.php',
		'/lib/txplib_misc.php',
		'/lib/txplib_theme.php',
		'/lib/txplib_update.php',
		'/lib/txplib_wrapper.php',
		'/publish.php',
		'/publish/atom.php',
		'/publish/comment.php',
		'/publish/log.php',
		'/publish/rss.php',
		'/publish/search.php',
		'/publish/taghandlers.php',
		'/../rpc/index.php',
		'/../rpc/TXP_RPCServer.php',
		'/theme/classic/classic.php',
		'/update/_to_1.0.0.php',
		'/update/_to_4.0.2.php',
		'/update/_to_4.0.3.php',
		'/update/_to_4.0.4.php',
		'/update/_to_4.0.5.php',
		'/update/_to_4.0.6.php',
		'/update/_to_4.0.7.php',
		'/update/_to_4.0.8.php',
		'/update/_update.php'
	);

	if ($event == 'diag') {
		require_privs('diag');

		$step = gps('step');
		doDiagnostics();
	}


	function apache_module($m) {
		$modules = apache_get_modules();
		return in_array($m, $modules);
	}

	function test_tempdir($dir) {
		$f = realpath(tempnam($dir, 'txp_'));
		if (is_file($f)) {
			@unlink($f);
			return true;
		}
	}

	function list_txp_tables() {
		$table_names = array(PFX.'textpattern');
		$rows = getRows("SHOW TABLES LIKE '".PFX."txp\_%'");
		foreach ($rows as $row)
			$table_names[] = array_shift($row);
		return $table_names;
	}

	function check_tables($tables, $type='FAST', $warnings=0) {
		$msgs = array();
		foreach ($tables as $table) {
			$rs = getRows("CHECK TABLE `$table` $type");
			if ($rs) {
				foreach ($rs as $r)
					if ($r['Msg_type'] != 'status' and ($warnings or $r['Msg_type'] != 'warning'))
						$msgs[] = $table.cs.$r['Msg_type'].cs.$r['Msg_text'];
			}
		}
		return $msgs;
	}

	function doDiagnostics()
	{
		global $prefs, $files, $txpcfg, $step, $theme;
		extract(get_prefs());

	$urlparts = parse_url(hu);
	$mydomain = $urlparts['host'];
	$server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST'])
						? ( (@$_SERVER['SERVER_SOFTWARE']) ?  @$_SERVER['SERVER_SOFTWARE'] :  $_SERVER['HTTP_HOST'] )
						: '';
	$is_apache = ($server_software and stristr($server_software, 'Apache'))
				   or (is_callable('apache_get_version'));
	$real_doc_root = (isset($_SERVER['DOCUMENT_ROOT'])) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';

	// ini_get() returns string values passed via php_value as a string, not boolean
	$is_register_globals = ( (strcasecmp(ini_get('register_globals'),'on')===0) or (ini_get('register_globals')==='1'));

	$fail = array(

		'php_version_4_3_0_required' =>
		(!is_callable('version_compare') or version_compare(PHP_VERSION, '4.3.0', '<'))
		? gTxt('php_version_4_3_0_required')
		: '',

		'path_to_site_missing' =>
		(!isset($path_to_site))
		? gTxt('path_to_site_missing')
		: '',

		'dns_lookup_fails' =>
		(@gethostbyname($mydomain) == $mydomain)
		?	gTxt('dns_lookup_fails').cs. $mydomain
		:	'',

		'path_to_site_inacc' =>
		(!@is_dir($path_to_site))
		?	gTxt('path_to_site_inacc').cs.$path_to_site
		: 	'',

		'site_trailing_slash' =>
		(rtrim($siteurl, '/') != $siteurl)
		?	gTxt('site_trailing_slash').cs.$path_to_site
		:	'',

		'index_inaccessible' =>
		(!@is_file($path_to_site."/index.php") or !@is_readable($path_to_site."/index.php"))
		?	"{$path_to_site}/index.php ".gTxt('is_inaccessible')
		:	'',

		'dir_not_writable' =>
		trim(
			((!@is_writable($path_to_site.'/'.$img_dir))
			?	str_replace('{dirtype}', gTxt('img_dir'), gTxt('dir_not_writable')).": {$path_to_site}/{$img_dir}".n
			:	'').
			((!@is_writable($file_base_path))
			?	str_replace('{dirtype}', gTxt('file_base_path'), gTxt('dir_not_writable')).": {$file_base_path}".n
			:	'').
			((!@is_writable($tempdir))
			?	str_replace('{dirtype}', gTxt('tempdir'), gTxt('dir_not_writable')).": {$tempdir}".n
			:	'')),

		'cleanurl_only_apache' =>
		($permlink_mode != 'messy' and !$is_apache )
		? gTxt('cleanurl_only_apache')
		: '',

		'htaccess_missing' =>
		($permlink_mode != 'messy' and !@is_readable($path_to_site.'/.htaccess'))
		?	gTxt('htaccess_missing')
		:	'',

		'mod_rewrite_missing' =>
		($permlink_mode != 'messy' and is_callable('apache_get_modules') and !apache_module('mod_rewrite'))
		? gTxt('mod_rewrite_missing')
		: '',

		'file_uploads_disabled' =>
		(!ini_get('file_uploads'))
		?	gTxt('file_uploads_disabled')
		:	'',

		'setup_still_exists' =>
		(@is_dir(txpath . DS. 'setup'))
		?	txpath.DS."setup".DS.' '.gTxt('still_exists')
		:	'',

		'no_temp_dir' =>
		(empty($tempdir))
		? gTxt('no_temp_dir')
		: '',

		'warn_mail_unavailable' =>
		(is_disabled('mail'))
		? gTxt('warn_mail_unavailable')
		: '',

		'warn_register_globals_or_update' =>
		( $is_register_globals &&
		  (    version_compare(phpversion(),'4.4.0','<=')
			or ( version_compare(phpversion(),'5.0.0','>=') and version_compare(phpversion(),'5.0.5','<=') )
		))
		? gTxt('warn_register_globals_or_update')
		: '',

	);

	if ($permlink_mode != 'messy') {
		$rs = safe_column("name","txp_section", "1");
		foreach ($rs as $name) {
			if ($name and @file_exists($path_to_site.'/'.$name))
				$fail['old_placeholder_exists'] = gTxt('old_placeholder').": {$path_to_site}/{$name}";
		}
	}

	$missing = array();

	foreach ($files as $f)
	{
		$realpath = realpath(txpath . $f);

		if (is_readable($realpath))
		{
			$found[] = $realpath;
		}
		else
		{
			$missing[] = txpath . $f;
		}
	}

	$files = $found;
	unset($found);

	if ($missing)
		$fail['missing_files'] = gTxt('missing_files').cs.n.t.join(', '.n.t, $missing);

	foreach ($fail as $k=>$v)
		if (empty($v)) unset($fail[$k]);

	# Find the highest revision number
	$file_revs = $file_md5 = array();
	$rev = 0;

	foreach ($files as $f)
	{
		$content = @file_get_contents($f);

		if ($content !== FALSE)
		{
			if (preg_match('/^\$'.'LastChangedRevision: (\d+) \$/m', $content, $match))
			{
				$file_revs[$f] = $match[1];

				if ($match[1] > $rev)
				{
					$rev = $match[1];
				}
			}

			$file_md5[$f]  = md5(str_replace('$'.'HeadURL: http:', '$'.'HeadURL: https:', str_replace("\r\n", "\n", $content)));
		}
	}

	# Check revs & md5 against stable release, if possible
	$dev_files = $old_files = $modified_files = array();

	if ($cs = @file(txpath . '/checksums.txt'))
	{
		foreach ($cs as $c)
		{
			if (preg_match('@^(\S+): r?(\S+) \((.*)\)$@', trim($c), $m))
			{
				list(,$file,$r,$md5) = $m;
				$file = realpath(txpath . $file);

				if (!empty($file_revs[$file]) and $r and $file_revs[$file] < $r)
				{
					$old_files[] = $file;
				}
				elseif (!empty($file_revs[$file]) and $r and $file_revs[$file] > $r)
				{
					$dev_files[] = $file;
				}
				elseif (!empty($file_md5[$file]) and $file_md5[$file] != $md5)
				{
					$modified_files[] = $file;
				}
			}
		}
	}

	# files that haven't been updated
	if ($old_files)
		$fail['old_files'] = gTxt('old_files').cs.n.t.join(', '.n.t, $old_files);

	# files that don't match their checksums
	if ($modified_files)
		$fail['modified_files'] = gTxt('modified_files').cs.n.t.join(', '.n.t, $modified_files);

	# running development code in live mode is not recommended
	if ($dev_files and $production_status == 'live')
		$fail['dev_version_live'] = gTxt('dev_version_live').cs.n.t.join(', '.n.t, $dev_files);

	# anything might break if arbitrary functions are disabled
	if (ini_get('disable_functions')) {
		$disabled_funcs = array_map('trim', explode(',', ini_get('disable_functions')));
		# commonly disabled functions that we don't need
		$disabled_funcs = array_diff($disabled_funcs, array(
			'imagefilltoborder',
			'exec',
			'system',
			'dl',
			'passthru',
			'chown',
			'shell_exec',
			'popen',
			'proc_open',
		));
		if ($disabled_funcs)
			$fail['some_php_functions_disabled'] = gTxt('some_php_functions_disabled').cs.join(', ',$disabled_funcs);
	}

	# not sure about this one
	#if (strncmp(php_sapi_name(), 'cgi', 3) == 0 and ini_get('cgi.rfc2616_headers'))
	#	$fail['cgi_header_config'] = gTxt('cgi_header_config');

	$guess_site_url = $_SERVER['HTTP_HOST'] . preg_replace('#[/\\\\]$#','',dirname(dirname($_SERVER['SCRIPT_NAME'])));
	if ($siteurl and strip_prefix($siteurl, 'www.') != strip_prefix($guess_site_url, 'www.'))
		$fail['site_url_mismatch'] = gTxt('site_url_mismatch').cs.$guess_site_url;

	# test clean URL server vars
	if (hu) {
		if (ini_get('allow_url_fopen') and ($permlink_mode != 'messy')) {
			$s = md5(uniqid(rand(), true));
			ini_set('default_socket_timeout', 10);
			$pretext_data = @file(hu.$s.'/?txpcleantest=1');
			if ($pretext_data) {
				$pretext_req = trim(@$pretext_data[0]);
				if ($pretext_req != md5('/'.$s.'/?txpcleantest=1'))
					$fail['clean_url_data_failed'] = gTxt('clean_url_data_failed').cs.htmlspecialchars($pretext_req);
			}
			else
				$fail['clean_url_test_failed'] = gTxt('clean_url_test_failed');
		}
	}

	if ($tables = list_txp_tables()) {
		$table_errors = check_tables($tables);
		if ($table_errors)
			$fail['mysql_table_errors'] = gTxt('mysql_table_errors').cs.n.t.join(', '.n.t, $table_errors);
	}

	$active_plugins = array();
	if ($rows = safe_rows('name, version, code_md5, md5(code) as md5', 'txp_plugin', 'status > 0')) {
		foreach ($rows as $row) {
			$n = $row['name'].'-'.$row['version'];
			if (strtolower($row['md5']) != strtolower($row['code_md5']))
				$n .= 'm';
			$active_plugins[] = $n;
		}
	}

	$theme_manifest = $theme->manifest();

	// check GD info
	if (function_exists('gd_info')) {
		$gd_info = gd_info();

		$gd_support = array();

		if ($gd_info['GIF Create Support']) {
			$gd_support[] = 'GIF';
		}

		if ($gd_info['JPG Support']) {
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
			'{supported}' => $gd_support
		));
	} else {
		$gd = gTxt('gd_unavailable');
	}

	if ( realpath($prefs['tempdir']) == realpath($prefs['plugin_cache_dir']) )
	{
		$fail['tmp_plugin_paths_match'] = gTxt('tmp_plugin_paths_match');
	}

	echo
	pagetop(gTxt('tab_diagnostics'),''),
	startTable('list'),
	tr(td(hed(gTxt('preflight_check'),1)));


	if ($fail) {
		foreach ($fail as $help => $message)
			echo tr(tda(nl2br($message).sp.popHelp($help), ' class="not-ok"'));
	}
	else {
		echo tr(tda(gTxt('all_checks_passed'), ' class="ok"'));
	}

	echo tr(td(hed(gTxt('diagnostic_info'),1)));


	$fmt_date = '%Y-%m-%d %H:%M:%S';

	$out = array(
		'<textarea cols="78" rows="18" readonly="readonly" style="width: 500px; height: 300px;">',

		gTxt('txp_version').cs.txp_version.' ('.($rev ? 'r'.$rev : 'unknown revision').')'.n,

		gTxt('last_update').cs.gmstrftime($fmt_date, $dbupdatetime).'/'.gmstrftime($fmt_date, @filemtime(txpath.'/update/_update.php')).n,

		gTxt('document_root').cs.@$_SERVER['DOCUMENT_ROOT']. (($real_doc_root != @$_SERVER['DOCUMENT_ROOT']) ? ' ('.$real_doc_root.')' : '') .n,

		'$path_to_site'.cs.$path_to_site.n,

		gTxt('txp_path').cs.txpath.n,

		gTxt('permlink_mode').cs.$permlink_mode.n,

		(ini_get('open_basedir')) ? 'open_basedir: '.ini_get('open_basedir').n : '',

		(ini_get('upload_tmp_dir')) ? 'upload_tmp_dir: '.ini_get('upload_tmp_dir').n : '',

		gTxt('tempdir').cs.$tempdir.n,

		gTxt('web_domain').cs.$siteurl.n,

		(getenv('TZ')) ? 'TZ: '.getenv('TZ').n : '',

		gTxt('php_version').cs.phpversion().n,

		($is_register_globals) ? gTxt('register_globals').cs.$is_register_globals.n : '',

		gTxt('gd_library').cs.$gd.n,

		gTxt('server_time').cs.strftime('%Y-%m-%d %H:%M:%S').n,

		'MySQL'.cs.mysql_get_server_info().n,

		gTxt('locale').cs.$locale.n,

		(isset($_SERVER['SERVER_SOFTWARE'])) ? gTxt('server').cs.$_SERVER['SERVER_SOFTWARE'].n : '',

		(is_callable('apache_get_version')) ? gTxt('apache_version').cs.apache_get_version().n : '',

		gTxt('php_sapi_mode').cs.PHP_SAPI.n,

		gTxt('rfc2616_headers').cs.ini_get('cgi.rfc2616_headers').n,

		gTxt('os_version').cs.php_uname('s').' '.php_uname('r').n,

		($active_plugins ? gTxt('active_plugins').cs.join(', ', $active_plugins).n : ''),

		gTxt('theme_name').cs.$theme_name.sp.$theme_manifest['version'].n,

		$fail
		? n.gTxt('preflight_check').cs.n.ln.join("\n", $fail).n.ln
		: '',

		(is_readable($path_to_site.'/.htaccess'))
		?	n.gTxt('htaccess_contents').cs.n.ln.htmlspecialchars(join('',file($path_to_site.'/.htaccess'))).n.ln
		:	''
	);

	if ($step == 'high') {
		$mysql_client_encoding = (is_callable('mysql_client_encoding')) ? mysql_client_encoding() : '-';
		$out[] = n.'Charset (default/config)'.cs.$mysql_client_encoding.'/'.@$txpcfg['dbcharset'].n;

		$result = safe_query("SHOW variables like 'character_se%'");
		while ($row = mysql_fetch_row($result))
		{
			$out[] = $row[0].cs.$row[1].n;
			if ($row[0] == 'character_set_connection') $conn_char = $row[1];
		}

		$table_names = array(PFX.'textpattern');
		$result = safe_query("SHOW TABLES LIKE '".PFX."txp\_%'");
		while ($row = mysql_fetch_row($result))
		{
			$table_names[] = $row[0];
		}
		$table_msg = array();
		foreach ($table_names as $table)
		{
			$ctr = safe_query("SHOW CREATE TABLE ". $table."");
			if (!$ctr)
			{
				unset($table_names[$table]);
				continue;
			}
			$ctcharset = preg_replace('#^CREATE TABLE.*SET=([^ ]+)[^)]*$#is','\\1',mysql_result($ctr,0,'Create Table'));
			if (isset($conn_char) && !stristr($ctcharset,'CREATE') && ($conn_char != $ctcharset))
				$table_msg[] = "$table is $ctcharset";
			$ctr = safe_query("CHECK TABLE ". $table);
			if (in_array(mysql_result($ctr,0,'Msg_type'), array('error','warning')) )
				$table_msg[] = $table .cs. mysql_result($ctr,0,'Msg_Text');
		}
		if ($table_msg == array())
			$table_msg = (count($table_names) < 17) ?  array('-') : array('OK');
		$out[] = count($table_names).' Tables'.cs. implode(', ',$table_msg).n;

		$extns = get_loaded_extensions();
		$extv = array();
		foreach ($extns as $e) {
			$extv[] = $e . (phpversion($e) ? '/' . phpversion($e) : '');
		}
		$out[] = n.gTxt('php_extensions').cs.join(', ', $extv).n;

		if (is_callable('apache_get_modules'))
			$out[] = n.gTxt('apache_modules').cs.join(', ', apache_get_modules()).n;

		if (@is_array($pretext_data) and count($pretext_data) > 1) {
			$out[] = n.gTxt('pretext_data').cs.htmlspecialchars(join('', array_slice($pretext_data, 1, 20))).n;
		}

		$out[] = n;

		foreach ($files as $f)
		{
			$checksum = isset($file_md5[$f]) ? $file_md5[$f] : gTxt('unknown');
			$revision = isset($file_revs[$f]) ? 'r'.$file_revs[$f] : gTxt('unknown');

			$out[] = "$f" .cs.n.t. $revision .' ('.$checksum.')'.n;
		}
		$out[] = n.ln;

	}

	$out[] = callback_event('diag_results', $step).n;
	$out[] = '</textarea>'.br;

	$dets = array('low'=>gTxt('low'),'high'=>gTxt('high'));

	$out[] =
		form(
			eInput('diag').n.
			gTxt('detail').cs.
			selectInput('step', $dets, $step, 0, 1)
		);

	echo tr(td(join('',$out))),

	endTable();
	}

?>

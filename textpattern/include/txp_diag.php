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
		'/../css.php',
		'/css.php',
		'/include/import/import_b2.php',
		'/include/import/import_blogger.php',
		'/include/import/import_mt.php',
		'/include/import/import_mtdb.php',
		'/include/import/import_wp.php',
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
		'/lib/txplib_publish.php',
		'/lib/txplib_theme.php',
		'/lib/txplib_update.php',
		'/lib/txplib_validator.php',
		'/lib/txplib_wrapper.php',
		'/publish.php',
		'/publish/atom.php',
		'/publish/comment.php',
		'/publish/log.php',
		'/publish/rss.php',
		'/publish/search.php',
		'/publish/taghandlers.php',
		'/textpattern.js',
		'/theme/classic/classic.php',
		'/update/_to_1.0.0.php',
		'/update/_to_4.0.2.php',
		'/update/_to_4.0.3.php',
		'/update/_to_4.0.4.php',
		'/update/_to_4.0.5.php',
		'/update/_to_4.0.6.php',
		'/update/_to_4.0.7.php',
		'/update/_to_4.0.8.php',
		'/update/_to_4.2.0.php',
		'/update/_to_4.3.0.php',
		'/update/_to_4.4.0.php',
		'/update/_to_4.4.1.php',
		'/update/_to_4.5.0.php',
		'/update/_update.php'
	);

	$files_rpc = array(
		'/../rpc/index.php',
		'/../rpc/TXP_RPCServer.php',
	);

	if ($prefs['enable_xmlrpc_server']) $files = array_merge($files, $files_rpc);

	if ($event == 'diag') {
		require_privs('diag');

		$step = gps('step');
		doDiagnostics();
	}

	function apache_module($m) {
		$modules = @apache_get_modules();
		if (is_array($modules)) {
			return in_array($m, $modules);
		}
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

	function diag_msg_wrap($msg, $type='error')
	{
		return '<span class="'.$type.'">'.$msg.'</span>';
	}

	function doDiagnostics()
	{
		global $prefs, $files, $txpcfg, $event, $step, $theme;
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

		// Check for Textpattern updates, at most once every 24 hours
		$now = time();
		$updateInfo = unserialize(get_pref('last_update_check', ''));

		if (!$updateInfo || ( $now > ($updateInfo['when'] + (60*60*24)) ))
		{
			$updates = checkUpdates();
			$updateInfo['msg'] = ($updates) ? gTxt($updates['msg'], array('{version}' => $updates['version'])) : '';
			$updateInfo['when'] = $now;
			set_pref('last_update_check', serialize($updateInfo), 'publish', PREF_HIDDEN, 'text_input');
		}

		$fail = array(

			'textpattern_version_update' => ($updateInfo['msg'] ? diag_msg_wrap($updateInfo['msg'], 'information') : ''),

			'php_version_required' =>
			(!is_callable('version_compare') or version_compare(PHP_VERSION, REQUIRED_PHP_VERSION, '<'))
			? diag_msg_wrap(gTxt('php_version_required', array('{version}' => REQUIRED_PHP_VERSION)))
			: '',

			'path_to_site_missing' =>
			(!isset($path_to_site))
			? diag_msg_wrap(gTxt('path_to_site_missing'), 'warning')
			: '',

			'dns_lookup_fails' =>
			(@gethostbyname($mydomain) == $mydomain)
			?	diag_msg_wrap(gTxt('dns_lookup_fails').cs.$mydomain, 'warning')
			:	'',

			'path_to_site_inacc' =>
			(!@is_dir($path_to_site))
			?	diag_msg_wrap(gTxt('path_to_site_inacc').cs.$path_to_site)
			: 	'',

			'site_trailing_slash' =>
			(rtrim($siteurl, '/') != $siteurl)
			?	diag_msg_wrap(gTxt('site_trailing_slash').cs.$path_to_site, 'warning')
			:	'',

			'index_inaccessible' =>
			(!@is_file($path_to_site."/index.php") or !@is_readable($path_to_site."/index.php"))
			?	diag_msg_wrap("{$path_to_site}/index.php ".gTxt('is_inaccessible'))
			:	'',

			'dir_not_writable' =>
			trim(
				((!@is_writable($path_to_site.'/'.$img_dir))
				?	diag_msg_wrap(str_replace('{dirtype}', gTxt('img_dir'), gTxt('dir_not_writable')).": {$path_to_site}/{$img_dir}", 'warning').n
				:	'').
				((!@is_writable($file_base_path))
				?	diag_msg_wrap(str_replace('{dirtype}', gTxt('file_base_path'), gTxt('dir_not_writable')).": {$file_base_path}", 'warning').n
				:	'').
				((!@is_writable($tempdir))
				?	diag_msg_wrap(str_replace('{dirtype}', gTxt('tempdir'), gTxt('dir_not_writable')).": {$tempdir}", 'warning').n
				:	'')),

			'cleanurl_only_apache' =>
			($permlink_mode != 'messy' and !$is_apache )
			? diag_msg_wrap(gTxt('cleanurl_only_apache'), 'information')
			: '',

			'htaccess_missing' =>
			($permlink_mode != 'messy' and !@is_readable($path_to_site.'/.htaccess'))
			?	diag_msg_wrap(gTxt('htaccess_missing'))
			:	'',

			'mod_rewrite_missing' =>
			($permlink_mode != 'messy' and is_callable('apache_get_modules') and !apache_module('mod_rewrite'))
			? diag_msg_wrap(gTxt('mod_rewrite_missing'))
			: '',

			'file_uploads_disabled' =>
			(!ini_get('file_uploads'))
			?	diag_msg_wrap(gTxt('file_uploads_disabled'), 'information')
			:	'',

			'setup_still_exists' =>
			(@is_dir(txpath . DS. 'setup'))
			?	diag_msg_wrap(txpath.DS."setup".DS.' '.gTxt('still_exists'), 'warning')
			:	'',

			'no_temp_dir' =>
			(empty($tempdir))
			? diag_msg_wrap(gTxt('no_temp_dir'), 'warning')
			: '',

			'warn_mail_unavailable' =>
			(is_disabled('mail'))
			? diag_msg_wrap(gTxt('warn_mail_unavailable'), 'warning')
			: '',

			'warn_register_globals_or_update' =>
			( $is_register_globals &&
			  (    version_compare(phpversion(),'4.4.0','<=')
				or ( version_compare(phpversion(),'5.0.0','>=') and version_compare(phpversion(),'5.0.5','<=') )
			))
			? diag_msg_wrap(gTxt('warn_register_globals_or_update'), 'warning')
			: '',

		);

		if ($permlink_mode != 'messy') {
			$rs = safe_column("name","txp_section", "1");
			foreach ($rs as $name) {
				if ($name and @file_exists($path_to_site.'/'.$name))
					$fail['old_placeholder_exists'] = diag_msg_wrap(gTxt('old_placeholder').": {$path_to_site}/{$name}");
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
			$fail['missing_files'] = diag_msg_wrap(gTxt('missing_files').cs.n.t.join(', '.n.t, $missing));

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
			$fail['old_files'] = diag_msg_wrap(gTxt('old_files').cs.n.t.join(', '.n.t, $old_files));

		# files that don't match their checksums
		if ($modified_files)
			$fail['modified_files'] = diag_msg_wrap(gTxt('modified_files').cs.n.t.join(', '.n.t, $modified_files), 'warning');

		# running development code in live mode is not recommended
		if ($dev_files and $production_status == 'live')
			$fail['dev_version_live'] = diag_msg_wrap(gTxt('dev_version_live').cs.n.t.join(', '.n.t, $dev_files), 'warning');

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
				$fail['some_php_functions_disabled'] = diag_msg_wrap(gTxt('some_php_functions_disabled').cs.join(', ',$disabled_funcs), 'warning');
		}

		# not sure about this one
		#if (strncmp(php_sapi_name(), 'cgi', 3) == 0 and ini_get('cgi.rfc2616_headers'))
		#	$fail['cgi_header_config'] = gTxt('cgi_header_config');

		$guess_site_url = $_SERVER['HTTP_HOST'] . preg_replace('#[/\\\\]$#','',dirname(dirname($_SERVER['SCRIPT_NAME'])));
		if ($siteurl and strip_prefix($siteurl, 'www.') != strip_prefix($guess_site_url, 'www.'))
			$fail['site_url_mismatch'] = diag_msg_wrap(gTxt('site_url_mismatch').cs.$guess_site_url, 'warning');

		# test clean URL server vars
		if (hu) {
			if (ini_get('allow_url_fopen') and ($permlink_mode != 'messy')) {
				$s = md5(uniqid(rand(), true));
				ini_set('default_socket_timeout', 10);
				$pretext_data = @file(hu.$s.'/?txpcleantest=1');
				if ($pretext_data) {
					$pretext_req = trim(@$pretext_data[0]);
					if ($pretext_req != md5('/'.$s.'/?txpcleantest=1'))
						$fail['clean_url_data_failed'] = diag_msg_wrap(gTxt('clean_url_data_failed').cs.txpspecialchars($pretext_req), 'warning');
				}
				else
					$fail['clean_url_test_failed'] = diag_msg_wrap(gTxt('clean_url_test_failed'), 'warning');
			}
		}

		if ($tables = list_txp_tables()) {
			$table_errors = check_tables($tables);
			if ($table_errors)
				$fail['mysql_table_errors'] = diag_msg_wrap(gTxt('mysql_table_errors').cs.n.t.join(', '.n.t, $table_errors));
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

			 // Aside: In PHP 5.3, they chose to add a previously unemployed capital "E" to the array key.
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
				'{supported}' => $gd_support
			));
		} else {
			$gd = gTxt('gd_unavailable');
		}

		if ( realpath($prefs['tempdir']) == realpath($prefs['plugin_cache_dir']) )
		{
			$fail['tmp_plugin_paths_match'] = diag_msg_wrap(gTxt('tmp_plugin_paths_match'));
		}

		echo
		pagetop(gTxt('tab_diagnostics'),''),
		'<h1 class="txp-heading">'.gTxt('tab_diagnostics').'</h1>',
		'<div id="'.$event.'_container" class="txp-container">',
		'<div id="pre_flight_check">',
		hed(gTxt('preflight_check'),2);

		if ($fail) {
			foreach ($fail as $help => $message)
				echo graf(nl2br($message).sp.popHelp($help));
		}
		else {
			echo graf(diag_msg_wrap(gTxt('all_checks_passed'), 'success'));
		}
		echo '</div>';

		echo '<div id="diagnostics">',
			hed(gTxt('diagnostic_info'),2);

		$fmt_date = '%Y-%m-%d %H:%M:%S';

		$out = array(
			'<p><textarea id="diagnostics-detail" cols="'.INPUT_LARGE.'" rows="'.INPUT_MEDIUM.'" readonly="readonly">',

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

			gTxt('php_version').cs.phpversion().n,

			($is_register_globals) ? gTxt('register_globals').cs.$is_register_globals.n : '',

			gTxt('gd_library').cs.$gd.n,

			gTxt('server').' TZ: '.(timezone::is_supported() ? @date_default_timezone_get() : ((getenv('TZ')) ? getenv('TZ') : '-')).n,
			gTxt('server_time').cs.strftime('%Y-%m-%d %H:%M:%S').n,
			strip_tags(gTxt('is_dst')).cs.$is_dst.n,
			strip_tags(gTxt('auto_dst')).cs.$auto_dst.n,
			strip_tags(gTxt('gmtoffset')).cs.$timezone_key.sp."($gmtoffset)".n,

			'MySQL'.cs.mysql_get_server_info().n,

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
			?	n.gTxt('htaccess_contents').cs.n.ln.txpspecialchars(join('',file($path_to_site.'/.htaccess'))).n.ln
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

			$cf = preg_grep('/^custom_\d+/', getThings('describe `'.PFX.'textpattern`'));
			$out[] = n.get_pref('max_custom_fields', 10).sp.gTxt('custom').cs.
						implode(', ', $cf).sp.'('.count($cf).')'.n;

			$extns = get_loaded_extensions();
			$extv = array();
			foreach ($extns as $e) {
				$extv[] = $e . (phpversion($e) ? '/' . phpversion($e) : '');
			}
			$out[] = n.gTxt('php_extensions').cs.join(', ', $extv).n;

			if (is_callable('apache_get_modules'))
				$out[] = n.gTxt('apache_modules').cs.join(', ', apache_get_modules()).n;

			if (@is_array($pretext_data) and count($pretext_data) > 1) {
				$out[] = n.gTxt('pretext_data').cs.txpspecialchars(join('', array_slice($pretext_data, 1, 20))).n;
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
		$out[] = '</textarea></p>';

		$dets = array('low'=>gTxt('low'),'high'=>gTxt('high'));

		$out[] =
			form(
				graf(
					eInput('diag').n.
					'<label>'.gTxt('detail').'</label>'.n.
					selectInput('step', $dets, $step, 0, 1)
				)
			);

		echo join('',$out),
			'</div>',
			'</div>';
	}

	//-------------------------------------------------------------
	// check for updates through xml-rpc
	function checkUpdates()
	{
		require_once txpath.'/lib/IXRClass.php';
		$client = new IXR_Client('http://rpc.textpattern.com');
		$uid = safe_field('val','txp_prefs',"name='blog_uid'");
		if (!$client->query('tups.getTXPVersion',$uid))
		{
			return array('version' => 0, 'msg' => 'problem_connecting_rpc_server');
		}

		else
		{
			$out = array();
			$response = $client->getResponse();
			if (is_array($response))
			{
				ksort($response);
				$version = get_pref('version');

				// Go through each available branch (x.y), but only return the _highest_ version
				foreach ($response as $key => $val)
				{
					if (version_compare($version, $val) < 0)
					{
						$out = array('version' => $val, 'msg' => 'textpattern_update_available');
					}
				}

				return $out;
			}
		}
	}

?>

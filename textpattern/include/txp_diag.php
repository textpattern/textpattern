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

//-------------------------------------------------------------

	define("cs",': ');
	define("ln",str_repeat('-', 24).n);

	global $files;
	
	$files = array(
		'/include/txp_category.php',
		'/include/txp_plugin.php',
		'/include/txp_auth.php',
		'/include/txp_form.php',
		'/include/txp_section.php',
		'/include/txp_tag.php',
		'/include/txp_list.php',
		'/include/txp_page.php',
		'/include/txp_discuss.php',
		'/include/txp_prefs.php',
		'/include/txp_log.php',
		'/include/txp_preview.php',
		'/include/txp_image.php',
		'/include/txp_article.php',
		'/include/txp_css.php',
		'/include/txp_admin.php',
		'/include/txp_link.php',
		'/include/txp_diag.php',
		'/lib/admin_config.php',
		'/lib/txplib_misc.php',
		'/lib/taglib.php',
		'/lib/txplib_head.php',
		'/lib/classTextile.php',
		'/lib/txplib_html.php',
		'/lib/txplib_db.php',
		'/lib/IXRClass.php',
		'/lib/txplib_forms.php',
		'/publish/taghandlers.php',
		'/publish/atom.php',
		'/publish/log.php',
		'/publish/comment.php',
		'/publish/search.php',
		'/publish/rss.php',
		'/publish.php',
		'/index.php',
		'/css.php',
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

	function doDiagnostics()
	{
		global $files, $txpcfg, $step;
		extract(get_prefs());
		
	$urlparts = parse_url(hu);
	$mydomain = $urlparts['host'];
	$server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST']) 
						? ( (@$_SERVER['SERVER_SOFTWARE']) ?  @$_SERVER['SERVER_SOFTWARE'] :  $_SERVER['HTTP_HOST'] )
						: '';
	$is_apache = ($server_software and stristr($server_software, 'Apache')) 
				   or (is_callable('apache_get_version'));
	$real_doc_root = (isset($_SERVER['DOCUMENT_ROOT'])) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';
	
	$fail = array(

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
			?	str_replace('{dirtype}', gTxt('img_dir'), gTxt('dir_not_writable')).": {$path_to_site}/{$img_dir}\r\n"
			:	'').
			((!@is_writable($file_base_path))
			?	str_replace('{dirtype}', gTxt('file_base_path'), gTxt('dir_not_writable')).": {$file_base_path}\r\n"
			:	'').
			((!@is_writable($tempdir))
			?	str_replace('{dirtype}', gTxt('tempdir'), gTxt('dir_not_writable')).": {$tempdir}\r\n"
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
		(@is_dir($txpcfg['txpath'] . DS. 'setup'))
		?	$txpcfg['txpath'].DS."setup".DS.' '.gTxt('still_exists')
		:	'',

		'no_temp_dir' =>
		(empty($tempdir))
		? gTxt('no_temp_dir')
		: '',

		'warn_mail_unavailable' =>
		(!is_callable('mail'))
		? gTxt('warn_mail_unavailable')
		: '',

	);

	if ($permlink_mode != 'messy') {
		$rs = safe_column("name","txp_section", "1");
		foreach ($rs as $name) {
			if (@file_exists($path_to_site.'/'.$name))
				$fail['old_placeholder_exists'] = gTxt('old_placeholder').": {$path_to_site}/{$name}";
		}
	}

	$missing = array();
	foreach ($files as $f) {
		if (!is_readable($txpcfg['txpath'] . $f))
			$missing[] = $txpcfg['txpath'] . $f;
	}

	if ($missing)
		$fail['missing_files'] = gTxt('missing_files').cs.join(', ', $missing);


	foreach ($fail as $k=>$v)
		if (empty($v)) unset($fail[$k]);

	# Find the highest revision number
	$file_revs = array();
	$rev = 0;
	foreach ($files as $f) {
		$lines = @file($txpcfg['txpath'] . $f);
		if ($lines) {
			foreach ($lines as $line) {
				if (preg_match('/^\$LastChangedRevision: (\w+) \$/', $line, $match)) {
					$file_revs[$f] = $match[1];
					if ($match[1] > $rev)
						$rev = $match[1];
				}
			}
		}
	}


	echo 
	pagetop(gTxt('tab_diagnostics'),''),
	startTable('list'),
	tr(td(hed(gTxt('preflight_check'),1)));


	if ($fail) {
		foreach ($fail as $help => $message)
			echo tr(tda(nl2br($message) . popHelp($help), ' style="color:red;"'));
	}
	else {
		echo tr(td(gTxt('all_checks_passed')));
	}

	echo tr(td(hed(gTxt('diagnostic_info'),1)));


	$fmt_date = '%Y-%m-%d %H:%M:%S';
	
	$out = array(
		'<textarea style="width:500px;height:300px;" readonly="readonly">',

		gTxt('txp_version').cs.txp_version.' ('.($rev ? 'r'.$rev : 'unknown revision').')'.n,

		gTxt('last_update').cs.gmstrftime($fmt_date, $dbupdatetime).'/'.gmstrftime($fmt_date, @filemtime(txpath.'/update/_update.php')).n,

		gTxt('document_root').cs.@$_SERVER['DOCUMENT_ROOT']. (($real_doc_root != @$_SERVER['DOCUMENT_ROOT']) ? ' ('.$real_doc_root.')' : '') .n,

		'$path_to_site'.cs.$path_to_site.n,

		gTxt('txp_path').cs.$txpcfg['txpath'].n,

		gTxt('permlink_mode').cs.$permlink_mode.n,

		(ini_get('open_basedir')) ? 'open_basedir: '.ini_get('open_basedir').n : '',

		(ini_get('upload_tmp_dir')) ? 'upload_tmp_dir: '.ini_get('upload_tmp_dir').n : '',

		gTxt('tempdir').cs.$tempdir.n,

		gTxt('web_domain').cs.$siteurl.n,

		(getenv('TZ')) ? 'TZ: '.getenv('TZ').n : '',

		gTxt('php_version').cs.phpversion().n,

		(ini_get('register_globals')) ? gTxt('register_globals').cs.ini_get('register_globals').n : '',

		gTxt('server_time').cs.strftime('%Y-%m-%d %H:%M:%S').n,

		'MySQL'.cs.mysql_get_server_info().n,

		gTxt('locale').cs.$locale.n,

		(isset($_SERVER['SERVER_SOFTWARE'])) ? gTxt('server').cs.$_SERVER['SERVER_SOFTWARE'].n : '',

		(is_callable('apache_get_version')) ? gTxt('apache_version').cs.apache_get_version().n : '',

		$fail
		? n.gTxt('preflight_check').cs.n.ln.join("\n", $fail).n.ln
		: '',

		(is_readable($path_to_site.'/.htaccess')) 
		?	n.gTxt('htaccess_contents').cs.n.ln.join('',file($path_to_site.'/.htaccess')).n.ln 
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
			$table_msg = (count($table_names) < 18) ?  array('-') : array('OK');
		$out[] = count($table_names).' Tables'.cs. implode(', ',$table_msg).n;

		$extns = get_loaded_extensions();
		$extv = array();
		foreach ($extns as $e) {
			$extv[] = $e . (phpversion($e) ? '/' . phpversion($e) : '');
		}
		$out[] = n.gTxt('php_extensions').cs.join(', ', $extv).n;

		if (is_callable('apache_get_modules'))
			$out[] = n.gTxt('apache_modules').cs.join(', ', apache_get_modules()).n.n;

		foreach ($files as $f) {
			$rev = '';
			$checksum = '';

			if (is_callable('md5_file')) {
				$checksum = md5_file($txpcfg['txpath'] . $f);
			}

			if (isset($file_revs[$f]))
				$rev = $file_revs[$f];

			$out[] = "$f" .cs. ($rev ? "r".$rev : gTxt('unknown')).' ('.($checksum ? $checksum : gTxt('unknown')).')'.n;
		}
	}

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

<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/

//-------------------------------------------------------------

	check_privs(1,2);

	$step = gps('step');

	define("cs",': ');
	define("ln",str_repeat('-', 24).n);

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

	function apache_module($m) {
		$modules = apache_get_modules();
		return in_array($m, $modules);
	}

	$fail = array();

	$urlparts = parse_url(hu);
	$mydomain = $urlparts['host'];
	
	$fail = array(

		(!isset($path_to_site))
		? gTxt('path_to_site_missing').pophelp('path_to_site_missing')
		: '',
	
		(@gethostbyname($mydomain) == $mydomain)
		?	gTxt('dns_lookup_fails').cs. $mydomain.pophelp('dns_lookup_fails')
		:	'',

		(0 !== strncmp(realpath($_SERVER['DOCUMENT_ROOT']), realpath($path_to_site), strlen($_SERVER['DOCUMENT_ROOT'])))
		?	gTxt('path_not_doc_root').' [ '.$_SERVER['DOCUMENT_ROOT'].' ] '.pophelp('path_not_doc_root')
		:	'',

		(!@is_dir($path_to_site))
		?	gTxt('path_to_site_inacc').cs.$path_to_site.pophelp('path_to_site_inacc')
		: 	'',

		(rtrim($siteurl, '/') != $siteurl)
		?	gTxt('site_trailing_slash').cs.$path_to_site.pophelp('site_trailing_slash')
		:	'',

		(!@is_file($path_to_site."/index.php") or !@is_readable($path_to_site."/index.php"))
		?	"{$path_to_site}/index.php ".gTxt('is_inaccessible').pophelp('index_inaccessible')
		:	'',

		(!@is_writable($path_to_site.'/'.$img_dir))
		?	gTxt('img_dir_read_only').": {$path_to_site}/{$img_dir}".pophelp('img_dir_read_only')
		:	'',
	
		($url_mode and !@is_readable($path_to_site.'/.htaccess'))
		?	gTxt('htaccess_missing').pophelp('htaccess_missing')
		:	'',

		($url_mode and is_callable('apache_get_modules') and !apache_module('mod_rewrite'))
		? gTxt('mod_rewrite_missing').pophelp('mod_rewrite_missing')
		: '',

		(!ini_get('file_uploads'))
		?	gTxt('file_uploads_disabled').pophelp('file_uploads_disabled')
		:	'',

		(@file_exists($txpcfg['txpath'] . '/_update.php'))
		?	$txpcfg['txpath']."/_update.php ".gTxt('still_exists').pophelp('update_still_exists')
		:	'',

		(@file_exists($txpcfg['txpath'] . '/setup.php'))
		?	$txpcfg['txpath']."/setup.php ".gTxt('still_exists').pophelp('setup_still_exists')
		:	'',

		(empty($tempdir))
		? gTxt('no_temp_dir').pophelp('no_temp_dir')
		: '',

		(!@tempnam(@$tempdir, 'txp_'))
		? gTxt('temp_dir_read_only').pophelp('temp_dir_read_only')
		: '',

	);

	if ($permlink_mode != 'messy') {
		$rs = safe_column("name","txp_section", "1");
		foreach ($rs as $name) {
			if (@file_exists($path_to_site.'/'.$name))
				$fail[] = gTxt('old_placeholder').": {$path_to_site}/{$name}".pophelp('old_placeholder_exists');
		}
	}


	echo 
	pagetop(gTxt('tab_diagnostics'),$message),
	startTable('list'),
	tr(td(hed(gTxt('preflight_check'),1)));


	if ($fail) {
		foreach ($fail as $message)
			echo tr(tda($message, ' style="color:red;"'));
	}
	else {
		echo tr(td(gTxt('all_checks_passed')));
	}

	echo tr(td(hed(gTxt('diagnostic_info'),1)));

	
	$out = array(
		'<textarea style="width:500px;height:300px;" readonly="readonly">',

		gTxt('txp_version').cs.txp_version.n,

		gTxt('document_root').cs.$_SERVER['DOCUMENT_ROOT'].n,

		'$path_to_site'.cs.$path_to_site.n,

		gTxt('txp_path').cs.$txpcfg['txpath'].n,

		(ini_get('open_basedir')) ? 'open_basedir: '.ini_get('open_basedir').n : '',

		(ini_get('upload_tmp_dir')) ? 'upload_tmp_dir: '.ini_get('upload_tmp_dir').n : '',

		gTxt('tmp_dir').cs.$tempdir.n,

		gTxt('web_domain').cs.$siteurl.n,

		(getenv('TZ')) ? 'TZ: '.getenv('TZ').n : '',

		gTxt('php_version').cs.phpversion().n,

		(ini_get('register_globals')) ? gTxt('register_globals').cs.ini_get('register_globals').n : '',

		gTxt('magic_quotes').cs.get_magic_quotes_gpc().'/'.get_magic_quotes_runtime().n,

		gTxt('locale').cs.$locale.n,

		(isset($_SERVER['SERVER_SOFTWARE'])) ? gTxt('server').cs.$_SERVER['SERVER_SOFTWARE'].n : '',

		(is_callable('apache_get_version')) ? gTxt('apache_version').cs.apache_get_version().n : '',

		(is_readable($path_to_site.'/.htaccess')) 
		?	n.gTxt('htaccess_contents').cs.n.ln.join('',file($path_to_site.'/.htaccess')).ln 
		:	''
	);

	if ($step == 'high') {
		$extns = get_loaded_extensions();
		$extv = array();
		foreach ($extns as $e) {
			$extv[] = $e . (phpversion($e) ? '/' . phpversion($e) : '');
		}
		$out[] = n.gTxt('php_extensions').cs.join(', ', $extv).n;

		if (is_callable('apache_get_modules'))
			$out[] = n.gTxt('apache_modules').cs.join(', ', apache_get_modules()).n.n;


		if (is_callable('md5_file')) {
			foreach ($files as $f) {
				$out[] = "MD5 $f: ".md5_file($txpcfg['txpath'] . $f) . n;
			}
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

?>

<?php
/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement.

$HeadURL$
$LastChangedRevision$

*/

if (!defined('txpath'))
{
	define("txpath", dirname(dirname(__FILE__)));
}

define("txpinterface", "admin");
error_reporting(E_ALL);
@ini_set("display_errors","1");

include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/lib/txplib_misc.php';
include_once txpath.'/include/txp_auth.php';

header("Content-type: text/html; charset=utf-8");

// drop trailing cruft
$_SERVER['PHP_SELF'] = preg_replace('#^(.*index.php).*$#i', '$1', $_SERVER['PHP_SELF']);
// sniff out the 'textpattern' directory's name
// '/path/to/site/textpattern/setup/index.php'
//                -3          -2    -1
$txpdir = explode('/', $_SERVER['PHP_SELF']);
if (count($txpdir) > 3)
{
	// we live in the regular directory structure
	$txpdir = '/'.$txpdir[count($txpdir) - 3];
}
else
{
	// we probably came here from a clever assortment of symlinks and DocumentRoot
	$txpdir = '/';
}

$rel_siteurl = preg_replace("#^(.*?)($txpdir)?/setup.*$#i",'$1',$_SERVER['PHP_SELF']);
$rel_txpurl = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
print <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Textpattern &#8250; setup</title>
	<link rel="stylesheet" href="$rel_txpurl/theme/classic/textpattern.css" type="text/css" />
	</head>
	<body id="page-setup">
	<div align="center">
eod;

	$step = ps('step');
	switch ($step) {
		case "": chooseLang(); break;
		case "getDbInfo": getDbInfo(); break;
		case "getTxpLogin": getTxpLogin(); break;
		case "printConfig": printConfig(); break;
		case "createTxp": createTxp();
	}
?>
</div>
</body>
</html>
<?php

// -------------------------------------------------------------
	function chooseLang()
	{
	  echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">',
	  	'<table id="setup" cellpadding="0" cellspacing="0" border="0">',
		tr(
			tda(
				hed('Welcome to Textpattern',1).
				graf('Please choose a language:').
				langs().
				graf(fInput('submit','Submit','Submit','publish')).
				sInput('getDbInfo')
			,' width="400" height="50" colspan="4" align="left"')
		),
		'</table></form>';
	}

// -------------------------------------------------------------
	function getDbInfo()
	{
		$GLOBALS['textarray'] = setup_load_lang(ps('lang'));

		global $txpcfg;

		if (!isset($txpcfg['db']))
		{
			@include txpath.'/config.php';
		}

		if (!empty($txpcfg['db']))
		{
			exit(graf(
				gTxt('already_installed', array('{txpath}' => txpath))
			));
		}

		if (@$_SERVER['SCRIPT_NAME'] && (@$_SERVER['SERVER_NAME'] || @$_SERVER['HTTP_HOST']))
		{
			$guess_siteurl = (@$_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			$guess_siteurl .= $GLOBALS['rel_siteurl'];
		}
		else
		{
			$guess_siteurl = 'mysite.com';
		}

		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">',
			'<table id="setup" cellpadding="0" cellspacing="0" border="0">',
		tr(
			tda(
			  hed(gTxt('welcome_to_textpattern'),1).
			  graf(gTxt('need_details'),' style="margin-bottom:3em"').
			  hed('MySQL',2).
			  graf(gTxt('db_must_exist'))
			,' width="400" height="50" colspan="4" align="left"')
		),
		tr(
			fLabelCell(gTxt('mysql_login')).fInputCell('duser','',1).
			fLabelCell(gTxt('mysql_password')).fInputCell('dpass','',2)
		),
		tr(
			fLabelCell(gTxt('mysql_server')).fInputCell('dhost','localhost',3).
			fLabelCell(gTxt('mysql_database')).fInputCell('ddb','',4)
		),
		tr(
			fLabelCell(gTxt('table_prefix')).fInputCell('dprefix','',5).
			tdcs(small(gTxt('prefix_warning')),2)
		),
		tr(tdcs('&nbsp;',4)),
		tr(
			tdcs(
				hed(gTxt('site_url'),2).
				graf(gTxt('please_enter_url')),4)
		),
		tr(
			fLabelCell('http://').
				tdcs(fInput('text','siteurl',$guess_siteurl,'edit','','',40).
				popHelp('siteurl'),3)
		);

		if (is_disabled('mail'))
		{
			echo tr(
				tdcs(gTxt('warn_mail_unavailable'),3,null,'" style="color:red;text-align:center')
			);
		}

		echo tr(
			td().td(fInput('submit','Submit',gTxt('next'),'publish')).td().td()
		);
		echo endTable(),
		hInput('lang', LANG),
		sInput('printConfig'),
		'</form>';
	}

// -------------------------------------------------------------
	function printConfig()
	{
		$carry = psa(array('ddb','duser','dpass','dhost','dprefix','siteurl','lang'));
		extract($carry);

		$GLOBALS['textarray'] = setup_load_lang($lang);

		global $txpcfg;

		if (!isset($txpcfg['db']))
		{
			@include txpath.'/config.php';
		}

		if (!empty($txpcfg['db']))
		{
			exit(graf(
				gTxt('already_installed', array(
					'{txpath}' => txpath
				))
			));
		}

		// FIXME, remove when all languages are updated with this string
		if (!isset($GLOBALS['textarray']['prefix_bad_characters']))
			$GLOBALS['textarray']['prefix_bad_characters'] =
				'The Table prefix {dbprefix} contains characters that are not allowed.<br />'.
				'The first character must match one of <b>a-zA-Z_</b> and all following
				 characters must match one of <b>a-zA-Z0-9_</b>';

		echo graf(gTxt("checking_database"));

		if (($mylink = mysql_connect($dhost, $duser, $dpass)))
 			$carry['dclient_flags'] = 0;
		elseif (($mylink = mysql_connect($dhost, $duser, $dpass, false, MYSQL_CLIENT_SSL)))
 			$carry['dclient_flags'] = 'MYSQL_CLIENT_SSL';
		else
			exit(graf(gTxt('db_cant_connect')));

		echo graf(gTxt('db_connected'));

		if (! ($dprefix == '' || preg_match('#^[a-zA-Z_][a-zA-Z0-9_]*$#', $dprefix)) )
		{
			exit(graf(
				gTxt('prefix_bad_characters', array(
					'{dbprefix}' => strong(htmlspecialchars($dprefix))
				), 'raw')
			));
		}

		if (!$mydb = mysql_select_db($ddb))
		{
			exit(graf(
				gTxt('db_doesnt_exist', array(
					'{dbname}' => strong(htmlspecialchars($ddb))
				), 'raw')
			));
		}

		// On 4.1 or greater use utf8-tables
		$version = mysql_get_server_info();

		if ( intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version))
		{
			if (mysql_query("SET NAMES utf8"))
			{
				$carry['dbcharset'] = "utf8";
			}
			else
			{
				$carry['dbcharset'] = "latin1";
			}
		}
		else
		{
			$carry['dbcharset'] = "latin1";
		}

		echo graf(
			gTxt('using_db', array('{dbname}' => strong(htmlspecialchars($ddb))), 'raw')
			.' ('. $carry['dbcharset'] .')'
		),
		graf(
			strong(gTxt('before_you_proceed')).', '.gTxt('create_config', array('{txpath}' => htmlspecialchars(txpath)))
		),

		'<textarea name="config" cols="40" rows="5" style="width: 400px; height: 200px">',
		makeConfig($carry),
		'</textarea>',
		'<form action="'.$_SERVER['PHP_SELF'].'" method="post">',
		fInput('submit','submit',gTxt('did_it'),'smallbox'),
		sInput('getTxpLogin'),hInput('carry',postEncode($carry)),
		'</form>';
	}

// -------------------------------------------------------------
	function getTxpLogin()
	{
		$carry = postDecode(ps('carry'));
		extract($carry);

		$GLOBALS['textarray'] = setup_load_lang($lang);

		global $txpcfg;

		if (!isset($txpcfg['db']))
		{
			@include txpath.'/config.php';
		}

		if (!isset($txpcfg) || ($txpcfg['db'] != $ddb) || ($txpcfg['table_prefix'] != $dprefix))
		{
			echo graf(
				strong(gTxt('before_you_proceed')).', '.
				gTxt('create_config', array(
					'{txpath}' => htmlspecialchars(txpath)
				))
			),

			'<textarea style="width:400px;height:200px" name="config" rows="1" cols="1">',
			makeConfig($carry),
			'</textarea>',
			'<form action="'.$_SERVER['PHP_SELF'].'" method="post">',
			fInput('submit','submit',gTxt('did_it'),'smallbox'),
			sInput('getTxpLogin'),hInput('carry',postEncode($carry)),
			'</form>';
			return;
		}

		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">',
	  	startTable('edit'),
		tr(
			tda(
				graf(gTxt('thanks')).
				graf(gTxt('about_to_create'))
			,' width="400" colspan="2" align="center"')
		),
		tr(
			fLabelCell(gTxt('your_full_name')).fInputCell('RealName')
		),
		tr(
			fLabelCell(gTxt('setup_login')).fInputCell('name')
		),
		tr(
			fLabelCell(gTxt('choose_password')).fInputCell('pass')
		),
		tr(
			fLabelCell(gTxt('your_email')).fInputCell('email')
		),
		tr(
			td().td(fInput('submit','Submit',gTxt('next'),'publish'))
		),
		endTable(),
		sInput('createTxp'),
		hInput('lang', htmlspecialchars($lang)),
		hInput('siteurl', htmlspecialchars($siteurl)),
		'</form>';
	}

// -------------------------------------------------------------
	function createTxp()
	{
		$GLOBALS['textarray'] = setup_load_lang(ps('lang'));

		if (!is_valid_email(ps('email')))
		{
			exit(graf(gTxt('email_required')));
		}

		global $txpcfg;

		if (!isset($txpcfg['db']))
		{
			require txpath.'/config.php';
		}

		$ddb = $txpcfg['db'];
		$duser = $txpcfg['user'];
		$dpass = $txpcfg['pass'];
		$dhost = $txpcfg['host'];
		$dclient_flags = isset($txpcfg['client_flags']) ? $txpcfg['client_flags'] : 0;
		$dprefix = $txpcfg['table_prefix'];
		$dbcharset = $txpcfg['dbcharset'];

		$siteurl = str_replace("http://",'', ps('siteurl'));
		$siteurl = rtrim($siteurl,"/");
		$urlpath = preg_replace('#^[^/]+#', '', $siteurl);

		define("PFX",trim($dprefix));
		define('TXP_INSTALL', 1);

		include_once txpath.'/lib/txplib_update.php';
 		include txpath.'/setup/txpsql.php';

		// This has to come after txpsql.php, because otherwise we can't call mysql_real_escape_string
		extract(doSlash(psa(array('name','pass','RealName','email'))));

 		$nonce 	= md5( uniqid( rand(), true ) );
		$hash 	= doSlash(txp_hash_password($pass));

		mysql_query("INSERT INTO `".PFX."txp_users` VALUES
			(1,'$name','$hash','$RealName','$email',1,now(),'$nonce')");

		mysql_query("update `".PFX."txp_prefs` set val = '".doSlash($siteurl)."' where `name`='siteurl'");
		mysql_query("update `".PFX."txp_prefs` set val = '".LANG."' where `name`='language'");
		mysql_query("update `".PFX."txp_prefs` set val = '".getlocale(LANG)."' where `name`='locale'");
		mysql_query("update `".PFX."textpattern` set Body = replace(Body, 'siteurl', '".doSlash($urlpath)."'), Body_html = replace(Body_html, 'siteurl', '".doSlash($urlpath)."') WHERE ID = 1");

 		echo fbCreate();
	}

// -------------------------------------------------------------
	function makeConfig($ar)
	{
		define("nl","';\n");
		define("o",'$txpcfg[\'');
		define("m","'] = '");
		$open = chr(60).'?php';
		$close = '?'.chr(62);
		$ar = doSpecial($ar);
		extract($ar);
		return
		$open."\n"
		.o.'db'           .m.$ddb.nl
		.o.'user'         .m.$duser.nl
		.o.'pass'         .m.$dpass.nl
		.o.'host'         .m.$dhost.nl
		.($dclient_flags ? o.'client_flags'."'] = ".$dclient_flags.";\n" : '')
		.o.'table_prefix' .m.$dprefix.nl
		.o.'txpath'       .m.txpath.nl   // remove in crockery
		.o.'dbcharset'    .m.$dbcharset.nl
		.$close;
	}

// -------------------------------------------------------------
	function fbCreate()
	{
		if ($GLOBALS['txp_install_successful'] === false)
		{
			return '<div width="450" valign="top" style="margin-right: auto; margin-left: auto;">'.
				graf(
					gTxt('errors_during_install', array(
						'{num}' => $GLOBALS['txp_err_count']
					))
				,' style="margin-top: 3em;"').
				'</div>';
		}

		else
		{
			$warnings = @find_temp_dir() ? '' : graf(gTxt('set_temp_dir_prefs'));

			return '<div width="450" valign="top" style="margin-right: auto; margin-left: auto;">'.

			graf(
				gTxt('that_went_well')
			,' style="margin-top:3em"').

			$warnings.

			graf(
				gTxt('you_can_access', array(
					'index.php' => $GLOBALS['rel_txpurl'].'/index.php',
				))
			).

			graf(gTxt('thanks_for_interest')).

			'</div>';
		}
	}

// -------------------------------------------------------------
	function postEncode($thing)
	{
		return base64_encode(serialize($thing));
	}

// -------------------------------------------------------------
	function postDecode($thing)
	{
		return unserialize(base64_decode($thing));
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

		$default = 'en-gb';

		$out = n.'<select name="lang">';

		foreach ($langs as $a => $b)
		{
			$out .= n.t.'<option value="'.htmlspecialchars($a).'"'.
				( ($a == $default) ? ' selected="selected"' : '').
				'>'.htmlspecialchars($b).'</option>';
		}

		$out .= n.'</select>';

		return $out;
	}

// -------------------------------------------------------------
	function setup_load_lang($lang)
	{
		require_once txpath.'/setup/setup-langs.php';
		$lang = (isset($langs[$lang]) && !empty($langs[$lang]))? $lang : 'en-gb';
		define('LANG', $lang);
		return $langs[LANG];
	}

?>

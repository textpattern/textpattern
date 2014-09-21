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
if (defined('E_DEPRECATED')) {
	// Work-around for mysql_* being deprecated in PHP 5.5.
	error_reporting(E_ALL ^ E_DEPRECATED);
} else {
	error_reporting(E_ALL);
}

if (!isset($_SESSION)) {
	if (headers_sent()) {
		$_SESSION = array();
	} else {
		session_start();
	}
}

@ini_set("display_errors","1");

include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/lib/txplib_misc.php';
include_once txpath.'/lib/txplib_theme.php';
include_once txpath.'/include/txp_auth.php';

assert_system_requirements();

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

$step = ps('step');
$rel_siteurl = preg_replace("#^(.*?)($txpdir)?/setup.*$#i",'$1',$_SERVER['PHP_SELF']);
$rel_txpurl = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
$safe_siteurl = txpspecialchars($rel_siteurl);
$safe_txpurl = txpspecialchars($rel_txpurl);

$bodyclass = ($step=='') ? ' class="welcome"' : '';

print <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
	<title>Setup &#124; Textpattern CMS</title>
	<script type="text/javascript" src="$safe_txpurl/jquery.js"></script>
	<script type="text/javascript">var textpattern = { do_spellcheck: "", textarray: {} };</script>
	<script type="text/javascript" src="$safe_txpurl/textpattern.js"></script>
	<link rel="stylesheet" href="$safe_txpurl/theme/hive/css/textpattern.css" type="text/css" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
	<script type="text/javascript" src="$safe_txpurl/theme/hive/js/modernizr.js"></script>
	<script type="text/javascript" src="$safe_txpurl/theme/hive/js/jquery.formalize.min.js"></script>
	<!--[if lt IE 9]><script type="text/javascript" src="$safe_txpurl/theme/hive/js/selectivizr.min.js"></script><![endif]-->
</head>
<body id="page-setup"{$bodyclass}>
	<div class="txp-body">
eod;

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
		echo n.'<div id="setup_container" class="txp-container">',
			n.'<div class="txp-setup">',
			n.hed('Welcome to Textpattern',1),
			n.'<form action="'.txpspecialchars($_SERVER['PHP_SELF']).'" method="post">',
			n.langs(),
			n.graf(fInput('submit','Submit','Submit','publish')),
			n.sInput('getDbInfo'),
			n.'</form>',
			n.'</div>',
			n.'</div>';
	}

// -------------------------------------------------------------
	function txp_setup_progress_meter($stage=1) {

		$stages = array(
			1 => setup_gTxt('set_db_details'),
			2 => setup_gTxt('add_config_file'),
			3 => setup_gTxt('populate_db'),
			4 => setup_gTxt('get_started'),
		);

		$out = array();

		$out[] = n.'<div class="progress-meter">'.
			n.'<ol>';

		foreach ($stages as $idx => $phase)
		{
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

		echo n.'<div id="setup_container" class="txp-container">',
			txp_setup_progress_meter(1),
			n.'<div class="txp-setup">';

		if (!isset($txpcfg['db']))
		{
			@include txpath.'/config.php';
		}

		if (!empty($txpcfg['db']))
		{
			echo graf(
					'<span class="warning">'.setup_gTxt('already_installed', array('{txpath}' => txpath)).'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

		if (isset($_SESSION['siteurl']))
		{
			$guess_siteurl = $_SESSION['siteurl'];
		}
		elseif (@$_SERVER['SCRIPT_NAME'] && (@$_SERVER['SERVER_NAME'] || @$_SERVER['HTTP_HOST']))
		{
			$guess_siteurl = (@$_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			$guess_siteurl .= $GLOBALS['rel_siteurl'];
		}
		else
		{
			$guess_siteurl = 'mysite.com';
		}

		echo '<form action="'.txpspecialchars($_SERVER['PHP_SELF']).'" method="post">'.
			n.hed(setup_gTxt('need_details'),1).
			n.hed('MySQL',2).
			n.graf(setup_gTxt('db_must_exist')).

			n.graf(
				'<span class="edit-label"><label for="setup_mysql_login">'.setup_gTxt('mysql_login').'</label></span>'.
				n.'<span class="edit-value">'.fInput('text', 'duser', (isset($_SESSION['duser']) ? txpspecialchars($_SESSION['duser']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_mysql_login').'</span>'
			).

			n.graf(
				'<span class="edit-label"><label for="setup_mysql_pass">'.setup_gTxt('mysql_password').'</label></span>'.
				n.'<span class="edit-value">'.fInput('text', 'dpass', (isset($_SESSION['dpass']) ? txpspecialchars($_SESSION['dpass']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_mysql_pass').'</span>'
			).

			n.graf(
				'<span class="edit-label"><label for="setup_mysql_server">'.setup_gTxt('mysql_server').'</label></span>'.
				n.'<span class="edit-value">'.fInput('text', 'dhost', (isset($_SESSION['dhost']) ? txpspecialchars($_SESSION['dhost']) : 'localhost'), '', '', '', INPUT_REGULAR, '', 'setup_mysql_server').'</span>'
			).

			n.graf(
				'<span class="edit-label"><label for="setup_mysql_db">'.setup_gTxt('mysql_database').'</label></span>'.
				n.'<span class="edit-value">'.fInput('text', 'ddb', (isset($_SESSION['ddb']) ? txpspecialchars($_SESSION['ddb']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_mysql_db').'</span>'
			).

			n.graf(
				'<span class="edit-label"><label for="setup_table_prefix">'.setup_gTxt('table_prefix').'</label>'.sp.popHelp('table_prefix').'</span>'.
				n.'<span class="edit-value">'.fInput('text', 'dprefix', (isset($_SESSION['dprefix']) ? txpspecialchars($_SESSION['dprefix']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_table_prefix').'</span>'
			).

			n.hed(setup_gTxt('site_url'),2).
			n.graf(setup_gTxt('please_enter_url')).
			n.graf(
				'<span class="edit-label"><label for="setup_site_url">http://</label>'.sp.popHelp('siteurl').'</span>'.
				n.'<span class="edit-value">'.fInput('text', 'siteurl', txpspecialchars($guess_siteurl), '', '', '', INPUT_REGULAR, '', 'setup_site_url').'</span>'
			);

		if (is_disabled('mail'))
		{
			echo n.graf(
				'<span class="warning">'.setup_gTxt('warn_mail_unavailable').'</span>'
			);
		}

		echo n.graf(
			fInput('submit','Submit',setup_gTxt('next_step', '', 'raw'),'publish')
		);

		echo n.sInput('printConfig').
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

		echo n.'<div id="setup_container" class="txp-container">'.
			txp_setup_progress_meter(2).
			n.'<div class="txp-setup">';

		if (!isset($txpcfg['db']))
		{
			@include txpath.'/config.php';
		}

		if (!empty($txpcfg['db']))
		{
			echo graf(
					'<span class="warning">'.setup_gTxt('already_installed', array('{txpath}' => txpath)).'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

// TODO, @see http://forum.textpattern.com/viewtopic.php?pid=263205#p263205
//		if ('' === $_SESSION['dhost'] || '' === $_SESSION['duser'] || '' === $_SESSION['ddb'])
//		{
//			echo graf(
//				'<span class="war">'.setup_gTxt('missing_db_details').'</span>'
//			).
//				n.setup_back_button().
//				n.'</div>'.
//				n.'</div>';
//			exit;
//		}

		echo hed(setup_gTxt("checking_database"), 2);

		if (($mylink = mysql_connect($_SESSION['dhost'], $_SESSION['duser'], $_SESSION['dpass'])))
		{
			$_SESSION['dclient_flags'] = 0;
		}
		elseif (($mylink = mysql_connect($_SESSION['dhost'], $_SESSION['duser'], $_SESSION['dpass'], false, MYSQL_CLIENT_SSL)))
		{
			$_SESSION['dclient_flags'] = 'MYSQL_CLIENT_SSL';
		}
		else
		{
			echo graf(
					'<span class="error">'.setup_gTxt('db_cant_connect').'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

		echo graf(
			'<span class="success">'.setup_gTxt('db_connected').'</span>'
			);

		if (! ($_SESSION['dprefix'] == '' || preg_match('#^[a-zA-Z_][a-zA-Z0-9_]*$#', $_SESSION['dprefix'])) )
		{
			echo graf(
					'<span class="error">'.setup_gTxt('prefix_bad_characters', array(
						'{dbprefix}' => strong(txpspecialchars($_SESSION['dprefix']))
					), 'raw').'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

		if (!$mydb = mysql_select_db($_SESSION['ddb']))
		{
			echo graf(
					'<span class="error">'.setup_gTxt('db_doesnt_exist', array(
						'{dbname}' => strong(txpspecialchars($_SESSION['ddb']))
					), 'raw').'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

		$tables_exist = mysql_query("describe `".$_SESSION['dprefix']."textpattern`");
		if ($tables_exist)
		{
			echo graf(
					'<span class="error">'.setup_gTxt('tables_exist', array(
						'{dbname}' => strong(txpspecialchars($_SESSION['ddb']))
					), 'raw').'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

		// On 4.1 or greater use utf8-tables
		$version = mysql_get_server_info();

		if ( intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version))
		{
			if (mysql_query("SET NAMES utf8"))
			{
				$_SESSION['dbcharset'] = "utf8";
			}
			else
			{
				$_SESSION['dbcharset'] = "latin1";
			}
		}
		else
		{
			$_SESSION['dbcharset'] = "latin1";
		}

		echo graf(
			'<span class="success">'.setup_gTxt('using_db', array('{dbname}' => strong(txpspecialchars($_SESSION['ddb']))), 'raw')
			.' ('. txpspecialchars($_SESSION['dbcharset']) .')</span>'
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

		echo n.'<div id="setup_container" class="txp-container">';

		$problems = array();

		if (!isset($txpcfg['db']))
		{
			if (!is_readable(txpath.'/config.php'))
			{
				$problems[] = graf('<span class="error">'.setup_gTxt('config_php_not_found', array('{file}' => txpspecialchars(txpath.'/config.php')), 'raw').'</span>');
			}
			else
			{
				@include txpath.'/config.php';
			}
		}

		if (!isset($txpcfg) || ($txpcfg['db'] != $_SESSION['ddb']) || ($txpcfg['table_prefix'] != $_SESSION['dprefix']))
		{
			$problems[] = graf('<span class="error">'.setup_gTxt('config_php_does_not_match_input', 'raw').'</span>');
			echo txp_setup_progress_meter(2).
				n.'<div class="txp-setup">'.
				n.join(n, $problems).
				n.setup_config_contents().
				n.'</div>'.
				n.'</div>';
			exit;
		}

		// Default theme selector
		$core_themes = array('classic', 'remora', 'hive');

		$themes = theme::names();
		foreach ($themes as $t)
		{
			$theme = theme::factory($t);
			if ($theme) {
				$m = $theme->manifest();
				$title = empty($m['title']) ? ucwords($theme->name) : $m['title'];
				$vals[$t] = (in_array($t, $core_themes) ? setup_gTxt('core_theme', array('{theme}' => $title)) : $title);
				unset($theme);
			}
		}
		asort($vals, SORT_STRING);

		$theme_chooser = selectInput('theme', $vals, (isset($_SESSION['theme']) ? txpspecialchars($_SESSION['theme']) : 'classic'), '', '', '', 'setup_admin_theme');

		echo txp_setup_progress_meter(3).
			n.'<div class="txp-setup">';

		echo '<form action="'.txpspecialchars($_SERVER['PHP_SELF']).'" method="post">'.
			n.hed(setup_gTxt('creating_db_tables'),2).
			n.graf(setup_gTxt('about_to_create')).

			n.graf(
				'<span class="edit-label"><label for="setup_user_realname">'.setup_gTxt('your_full_name').'</label></span>'.
				n.'<span class="edit-value">'.fInput('text', 'RealName', (isset($_SESSION['realname']) ? txpspecialchars($_SESSION['realname']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_user_realname').'</span>'
			).

			n.graf(
				'<span class="edit-label"><label for="setup_user_login">'.setup_gTxt('setup_login').'</label>'.sp.popHelp('setup_user_login').'</span>'.
				n.'<span class="edit-value">'.fInput('text', 'name', (isset($_SESSION['name']) ? txpspecialchars($_SESSION['name']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_user_login').'</span>'
			).

			n.graf(
				'<span class="edit-label"><label for="setup_user_pass">'.setup_gTxt('choose_password').'</label>'.sp.popHelp('setup_user_pass').'</span>'.
				n.'<span class="edit-value">'.fInput('text', 'pass', (isset($_SESSION['pass']) ? txpspecialchars($_SESSION['pass']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_user_pass').'</span>'
			).

			n.graf(
				'<span class="edit-label"><label for="setup_user_email">'.setup_gTxt('your_email').'</label></span>'.
				n.'<span class="edit-value">'.fInput('text', 'email', (isset($_SESSION['email']) ? txpspecialchars($_SESSION['email']) : ''), '', '', '', INPUT_REGULAR, '', 'setup_user_email').'</span>'
			).

			n.hed(setup_gTxt('site_config'),2).

			n.graf(
				'<span class="edit-label"><label for="setup_admin_theme">'.setup_gTxt('admin_theme').'</label>'.sp.popHelp('theme_name').'</span>'.
				n.'<span class="edit-value">'.$theme_chooser.'</span>'
			).

			n.graf(
				fInput('submit','Submit',setup_gTxt('next_step'),'publish')
			).

			n.sInput('createTxp').
			n.'</form>'.
			n.'</div>'.
			n.'</div>';
	}

// -------------------------------------------------------------
	function createTxp()
	{
		$GLOBALS['textarray'] = setup_load_lang($_SESSION['lang']);

		$_SESSION['name'] = ps('name');
		$_SESSION['realname'] = ps('RealName');
		$_SESSION['pass'] = ps('pass');
		$_SESSION['email'] = ps('email');
		$_SESSION['theme'] = ps('theme');

		if ($_SESSION['name'] == '')
		{
			echo n.'<div id="setup_container" class="txp-container">'.
				txp_setup_progress_meter(3).
				n.'<div class="txp-setup">'.
				n.graf(
					'<span class="error">'.setup_gTxt('name_required').'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

		if (!$_SESSION['pass'])
		{
			echo n.'<div id="setup_container" class="txp-container">'.
				txp_setup_progress_meter(3).
				n.'<div class="txp-setup">'.
				n.graf(
					'<span class="error">'.setup_gTxt('pass_required').'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

		if (!is_valid_email($_SESSION['email']))
		{
			echo n.'<div id="setup_container" class="txp-container">'.
				txp_setup_progress_meter(3).
				n.'<div class="txp-setup">'.
				n.graf(
					'<span class="error">'.setup_gTxt('email_required').'</span>'
				).
				n.setup_back_button(__FUNCTION__).
				n.'</div>'.
				n.'</div>';
			exit;
		}

		global $txpcfg;

		if (!isset($txpcfg['db']))
		{
			if (!is_readable(txpath.'/config.php'))
			{
				$problems[] = graf('<span class="error">'.setup_gTxt('config_php_not_found', array('{file}' => txpspecialchars(txpath.'/config.php')), 'raw').'</span>');
			}
			else
			{
				@include txpath.'/config.php';
			}
		}

		if (!isset($txpcfg) || ($txpcfg['db'] != $_SESSION['ddb']) || ($txpcfg['table_prefix'] != $_SESSION['dprefix']))
		{
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

		$siteurl = str_replace("http://",'', $_SESSION['siteurl']);
		$siteurl = str_replace(' ', '%20', rtrim($siteurl,"/"));
		$urlpath = preg_replace('#^[^/]+#', '', $siteurl);

		define("PFX",trim($dprefix));
		define('TXP_INSTALL', 1);

		include_once txpath.'/lib/txplib_update.php';
		include txpath.'/setup/txpsql.php';

		$nonce = md5( uniqid( rand(), true ) );
		$hash  = doSlash(txp_hash_password($_SESSION['pass']));

		mysql_query("INSERT INTO `".PFX."txp_users` VALUES
			(1,
			'".doSlash($_SESSION['name'])."',
			'$hash',
			'".doSlash($_SESSION['realname'])."',
			'".doSlash($_SESSION['email'])."',
			1,
			now(),
			'$nonce')"
		);

		mysql_query("update `".PFX."txp_prefs` set val = '".doSlash($siteurl)."' where `name`='siteurl'");
		mysql_query("update `".PFX."txp_prefs` set val = '".LANG."' where `name`='language'");
		mysql_query("update `".PFX."txp_prefs` set val = '".getlocale(LANG)."' where `name`='locale'");
		mysql_query("update `".PFX."textpattern` set Body = replace(Body, 'siteurl', '".doSlash($urlpath)."'), Body_html = replace(Body_html, 'siteurl', '".doSlash($urlpath)."') WHERE ID = 1");

		// cf. update/_to_4.2.0.php.
		// TODO: Position might need altering when prefs panel layout is altered
		$theme = $_SESSION['theme'] ? $_SESSION['theme'] : 'classic';
		mysql_query("insert `".PFX."txp_prefs` set prefs_id = 1, name = 'theme_name', val = '".doSlash($theme)."', type = '1', event = 'admin', html = 'themename', position = '160'");

		echo fbCreate();
	}

// -------------------------------------------------------------
	function makeConfig()
	{
		define("nl","';\n");
		define("o",'$txpcfg[\'');
		define("m","'] = '");
		$open = chr(60).'?php';
		$close = '?'.chr(62);

		// Escape single quotes and backslashes in literal PHP strings
		foreach ($_SESSION as $k => $v) {
			$_SESSION[$k] = addcslashes($_SESSION[$k], "'\\");
		}
		$_SESSION = doSpecial($_SESSION);

		return
		$open."\n"
		.o.'db'           .m.$_SESSION['ddb'].nl
		.o.'user'         .m.$_SESSION['duser'].nl
		.o.'pass'         .m.$_SESSION['dpass'].nl
		.o.'host'         .m.$_SESSION['dhost'].nl
		.($_SESSION['dclient_flags'] ? o.'client_flags'."'] = ".$_SESSION['dclient_flags'].";\n" : '')
		.o.'table_prefix' .m.$_SESSION['dprefix'].nl
		.o.'txpath'       .m.txpath.nl
		.o.'dbcharset'    .m.$_SESSION['dbcharset'].nl
		.$close;
	}

// -------------------------------------------------------------
	function fbCreate()
	{
		echo n.'<div id="setup_container" class="txp-container">'.
			txp_setup_progress_meter(4).
			n.'<div class="txp-setup">';

		if ($GLOBALS['txp_install_successful'] === false)
		{
			return n.graf(
					'<span class="error">'.setup_gTxt('errors_during_install', array(
						'{num}' => $GLOBALS['txp_err_count']
					)).'</span>'
				).
				n.'</div>'.
				n.'</div>';
		}
		else
		{
			// Clear the session so no data is leaked.
			$_SESSION = array();

			$warnings = @find_temp_dir() ? '' : n.graf('<span class="warning">'.setup_gTxt('set_temp_dir_prefs').'</span>');
			$login_url = $GLOBALS['rel_txpurl'].'/index.php';

			return n.hed(setup_gTxt('that_went_well'),1).

				$warnings.

				n.graf(
					setup_gTxt('you_can_access', array(
						'index.php' => $login_url,
					))
				).

				n.graf(
					setup_gTxt('installation_postamble')
				).

				n.hed(setup_gTxt('thanks_for_interest'), 3).

				n.graf('<a href="'.$login_url.'" class="navlink publish">'.setup_gTxt('go_to_login').'</a>');

				n.'</div>'.
				n.'</div>';
		}
	}

// -------------------------------------------------------------
	function setup_config_contents()
	{
		return hed(setup_gTxt('creating_config'), 2).
			graf(
				strong(setup_gTxt('before_you_proceed')).' '.setup_gTxt('create_config', array('{txpath}' => txpspecialchars(txpath)))
			).

		'<textarea class="code" readonly="readonly" name="config" cols="'.INPUT_LARGE.'" rows="'.INPUT_MEDIUM.'">'.
		makeConfig().
		'</textarea>'.
		'<form action="'.txpspecialchars($_SERVER['PHP_SELF']).'" method="post">'.
		graf(fInput('submit','submit',setup_gTxt('did_it'),'publish')).
		sInput('getTxpLogin').
		'</form>';
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
		n.graf(
			'<form action="'.txpspecialchars($_SERVER['PHP_SELF']).'" method="post">'.
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

		foreach ($langs as $a => $b)
		{
			$out .= n.t.'<option value="'.txpspecialchars($a).'"'.
				( ($a == $default) ? ' selected="selected"' : '').
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
		$lang = (isset($langs[$lang]) && !empty($langs[$lang]))? $lang : 'en-gb';
		define('LANG', $lang);
		return $langs[LANG];
	}

// -------------------------------------------------------------
	function setup_gTxt($var, $atts=array(), $escape='html')
	{
		global $en_gb_strings;

		// Try to translate the string in chosen native language
		$xlate = gTxt($var, $atts, $escape);

		if (!is_array($atts)) {
			$atts = array();
		}

		if ($escape == 'html')
		{
			foreach ($atts as $key => $value)
			{
				$atts[$key] = txpspecialchars($value);
			}
		}

		$v = strtolower($var);

		// Find out if the translated string is the same as the $var input
		if ($atts)
		{
			$compare = ($xlate == $v.': '.join(', ', $atts));
		}
		else
		{
			$compare = ($xlate == $v);
		}
		if ($compare) {
			// No translation string available, so grab an english string we know exists as fallback
			$xlate = strtr($en_gb_strings[$v], $atts);
		}

		return $xlate;
	}

?>

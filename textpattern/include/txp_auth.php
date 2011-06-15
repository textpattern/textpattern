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

include_once txpath.'/lib/PasswordHash.php';

function doAuth()
{
	global $txp_user;

	$txp_user = NULL;

	$message = doTxpValidate();

	if(!$txp_user)
	{
		doLoginForm($message);
	}

	ob_start();
}

// -------------------------------------------------------------
	function txp_validate($user,$password,$log=TRUE)
	{
		$safe_user = doSlash($user);
		$name = FALSE;

		$hash = safe_field('pass', 'txp_users', "name = '$safe_user'");
		$phpass = new PasswordHash(PASSWORD_COMPLEXITY, PASSWORD_PORTABILITY);

		// check post-4.3-style passwords
		if ($phpass->CheckPassword($password, $hash)) {
			if ($log) {
				$name = safe_field("name", "txp_users",	"name = '$safe_user' and privs > 0");
			} else {
				$name = $user;
			}
		} else {
			// no good password: check 4.3-style passwords
			$passwords = array();

			$passwords[] = "password(lower('".doSlash($password)."'))";
			$passwords[] = "password('".doSlash($password)."')";

			if (version_compare(mysql_get_server_info(), '4.1.0', '>='))
			{
				$passwords[] = "old_password(lower('".doSlash($password)."'))";
				$passwords[] = "old_password('".doSlash($password)."')";
			}

			$name = safe_field("name", "txp_users",
				"name = '$safe_user' and (pass = ".join(' or pass = ', $passwords).") and privs > 0");

			// old password is good: migrate password to phpass
			if ($name !== FALSE) {
				safe_update("txp_users", "pass = '".doSlash($phpass->HashPassword($password))."'", "name = '$safe_user'");
			}
		}

		if ($name !== FALSE && $log)
		{
			// update the last access time
			safe_update("txp_users", "last_access = now()", "name = '$safe_user'");
		}
		return $name;
	}

// -------------------------------------------------------------
	function txp_hash_password($password)
	{
		static $phpass = NULL;
		if (!$phpass) {
			$phpass = new PasswordHash(PASSWORD_COMPLEXITY, PASSWORD_PORTABILITY);
		}
		return $phpass->HashPassword($password);
	}

// -------------------------------------------------------------

	function doLoginForm($message)
	{
		global $txpcfg;

		include txpath.'/lib/txplib_head.php';

		pagetop(gTxt('login'));

		$stay  = (cs('txp_login') and !gps('logout') ? 1 : 0);
		$reset = gps('reset');

		list($name) = explode(',', cs('txp_login'));

		echo n.'<div id="login_container" class="txp-container txp-edit">';
		echo form(
			startTable('edit', '', 'login-pane').
				n.n.tr(
					n.td().
					td(graf($message))
				).

				n.n.tr(
					n.fLabelCell('name', '', 'name').
					n.fInputCell('p_userid', $name, 1, '', '', 'name')
				).

				($reset ? '' :
					n.n.tr(
						n.fLabelCell('password', '', 'password').
						n.td(
						  	fInput('password', 'p_password', '', 'edit', '', '', '', 2, 'password')
						)
					)
				).

				($reset ? '' :
					n.n.tr(
						n.td().
						td(
							graf(checkbox('stay', 1, $stay, 3, 'stay').'<label for="stay">'.gTxt('stay_logged_in').'</label>'.
							sp.popHelp('remember_login'))
						)
					)
				).

				n.n.tr(
					n.td().
					td(
						($reset ? hInput('p_reset', 1) : '').
						fInput('submit', '', gTxt($reset ? 'password_reset_button' : 'log_in_button'), 'publish', '', '', '', 4).
						($reset ? '' : graf('<a href="?reset=1">'.gTxt('password_forgotten').'</a>'))
					)
				).

			endTable().

			(gps('event') ? eInput(gps('event')) : '')
		, '', '', 'post', '', '', 'login_form').'</div>'.


		n.'</body>'.n.'</html>';

		exit(0);
	}

// -------------------------------------------------------------
	function doTxpValidate()
	{
		global $logout,$txpcfg, $txp_user;
		$p_userid   = ps('p_userid');
		$p_password = ps('p_password');
		$p_reset    = ps('p_reset');
		$stay       = ps('stay');
		$logout     = gps('logout');
		$message    = gTxt('login_to_textpattern');
		$pub_path   = preg_replace('|//$|','/', rhu.'/');

		if (cs('txp_login') and strpos(cs('txp_login'), ','))
		{
			list($c_userid, $c_hash) = explode(',', cs('txp_login'));
		}
		else
		{
			$c_hash   = '';
			$c_userid = '';
		}

		if ($logout)
		{
			setcookie('txp_login', '', time()-3600);
			setcookie('txp_login_public', '', time()-3600, $pub_path);
		}

		if ($c_userid and strlen($c_hash) == 32) // cookie exists
		{
			$nonce = safe_field('nonce', 'txp_users', "name='".doSlash($c_userid)."' AND last_access > DATE_SUB(NOW(), INTERVAL 30 DAY)");

			if ($nonce and $nonce === md5($c_userid.pack('H*', $c_hash)))
			{
				// cookie is good

				if ($logout)
				{
					// destroy nonce
					safe_update(
						'txp_users',
						"nonce = '".doSlash(md5(uniqid(mt_rand(), TRUE)))."'",
						"name = '".doSlash($c_userid)."'"
					);
				}
				else
				{
					// create $txp_user
					$txp_user = $c_userid;
				}
				return '';
			}
			else
			{
				setcookie('txp_login', $c_userid, time()+3600*24*365);
				setcookie('txp_login_public', '', time()-3600, $pub_path);
				$message = gTxt('bad_cookie');
			}

		}
		elseif ($p_userid and $p_password) // incoming login vars
		{
			$name = txp_validate($p_userid,$p_password);

			if ($name !== FALSE)
			{
				$c_hash = md5(uniqid(mt_rand(), TRUE));
				$nonce  = md5($name.pack('H*',$c_hash));

				safe_update(
					'txp_users',
					"nonce = '".doSlash($nonce)."'",
					"name = '".doSlash($name)."'"
				);

				setcookie(
					'txp_login',
					$name.','.$c_hash,
					($stay ? time()+3600*24*365 : 0)
				);

				setcookie(
					'txp_login_public',
					substr(md5($nonce), -10).$name,
					($stay ? time()+3600*24*30 : 0),
					$pub_path
				);

				// login is good, create $txp_user
				$txp_user = $name;
				return '';
			}
			else
			{
				sleep(3);
				$message = gTxt('could_not_log_in');
			}
		}
		elseif ($p_reset) // reset request
		{
			sleep(3);

			include_once txpath.'/lib/txplib_admin.php';

			$message = send_reset_confirmation_request($p_userid);
		}
		elseif (gps('reset'))
		{
			$message = gTxt('password_reset');
		}
		elseif (gps('confirm'))
		{
			sleep(3);

			$confirm = pack('H*', gps('confirm'));
			$name    = substr($confirm, 5);
			$nonce   = safe_field('nonce', 'txp_users', "name = '".doSlash($name)."'");

			if ($nonce and $confirm === pack('H*', substr(md5($nonce), 0, 10)).$name)
			{
				include_once txpath.'/lib/txplib_admin.php';

				$message = reset_author_pass($name);
			}
		}

		$txp_user = '';
		return $message;
	}
?>

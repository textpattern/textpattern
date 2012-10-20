<?php

/*
This is Textpattern

Copyright 2005 by Dean Allen
www.textpattern.com
All rights reserved

Use of this software indicates acceptance of the Textpattern license agreement

*/

/**
 * Login panel.
 *
 * @package Admin\Auth
 */

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	include_once txpath.'/lib/PasswordHash.php';

/**
 * Renders a login panel if necessary.
 *
 * If the current visitor isn't authenticated,
 * terminates the script and instead renders
 * a login page.
 *
 * @access private
 */

	function doAuth()
	{
		global $txp_user;

		$txp_user = null;

		$message = doTxpValidate();

		if(!$txp_user)
		{
			doLoginForm($message);
		}

		ob_start();
	}

/**
 * Validates the given user credentials.
 *
 * This function validates a given login and a password combination.
 * If the combination is correct, the user's login name is returned,
 * FALSE otherwise.
 *
 * @param   string      $user     The login
 * @param   string      $password The password
 * @param   bool        $log      If TRUE, updates the user's last access time
 * @return  string|bool The user's login name or FALSE on error
 * @package User
 */

	function txp_validate($user, $password, $log = true)
	{
		$safe_user = doSlash($user);
		$name = false;

		$hash = safe_field('pass', 'txp_users', "name = '$safe_user'");
		$phpass = new PasswordHash(PASSWORD_COMPLEXITY, PASSWORD_PORTABILITY);

		// Check post-4.3-style passwords.
		if ($phpass->CheckPassword($password, $hash))
		{
			if ($log)
			{
				$name = safe_field("name", "txp_users",	"name = '$safe_user' and privs > 0");
			}
			else
			{
				$name = $user;
			}
		}
		else
		{
			// No good password: check 4.3-style passwords.
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

			// Old password is good: migrate password to phpass.
			if ($name !== false)
			{
				safe_update("txp_users", "pass = '".doSlash($phpass->HashPassword($password))."'", "name = '$safe_user'");
			}
		}

		if ($name !== false && $log)
		{
			// Update the last access time.
			safe_update("txp_users", "last_access = now()", "name = '$safe_user'");
		}

		return $name;
	}

/**
 * Calculates a password hash.
 *
 * @param   string $password The password
 * @return  string A hash
 * @see     PASSWORD_COMPLEXITY
 * @see     PASSWORD_PORTABILITY
 * @package User
 */

	function txp_hash_password($password)
	{
		static $phpass = null;
		if (!$phpass)
		{
			$phpass = new PasswordHash(PASSWORD_COMPLEXITY, PASSWORD_PORTABILITY);
		}
		return $phpass->HashPassword($password);
	}

/**
 * Renders and outputs a login form.
 *
 * This function outputs a full HTML document,
 * including &lt;head&gt; and footer.
 *
 * @param string|array $message The activity message
 */

	function doLoginForm($message)
	{
		global $textarray_script, $event, $step;

		include txpath.'/lib/txplib_head.php';
		$event = 'login';

		if (gps('logout'))
		{
			$step = 'logout';
		}
		else if(gps('reset'))
		{
			$step = 'reset';
		}

		pagetop(gTxt('login'), $message);

		$stay  = (cs('txp_login') and !gps('logout') ? 1 : 0);
		$reset = gps('reset');

		$name = join(',', array_slice(explode(',', cs('txp_login')), 0, -1));

		echo n.'<div id="login_container" class="txp-container">';
		echo form(
			'<div class="txp-login">'.
			n.hed(gTxt($reset ? 'password_reset' : 'login_to_textpattern'), 2).

			n.graf(
				'<span class="login-label"><label for="login_name">'.gTxt('name').'</label></span>'.
				n.'<span class="login-value">'.fInput('text', 'p_userid', $name, '', '', '', INPUT_REGULAR, '', 'login_name').'</span>'
			, ' class="login-name"').

			($reset
				? ''
				: n.graf(
					'<span class="login-label"><label for="login_password">'.gTxt('password').'</label></span>'.
					n.'<span class="login-value">'.fInput('password', 'p_password', '', '', '', '', INPUT_REGULAR, '', 'login_password').'</span>'
				, ' class="login-password"')
			).

			($reset
				? ''
				: graf(
					checkbox('stay', 1, $stay, '', 'login_stay').n.'<label for="login_stay">'.gTxt('stay_logged_in').'</label>'.sp.popHelp('remember_login')
					, ' class="login-stay"')
			).

			($reset ? n.hInput('p_reset', 1) : '').

			n.graf(
				fInput('submit', '', gTxt($reset ? 'password_reset_button' : 'log_in_button'), 'publish')
			).
			n.(
				($reset
					? graf('<a href="index.php">'.gTxt('back_to_login').'</a>', ' class="login-return"')
					: graf('<a href="?reset=1">'.gTxt('password_forgotten').'</a>', ' class="login-forgot"')
				)
			).
			(gps('event') ? eInput(gps('event')) : '').
			'</div>'
		, '', '', 'post', '', '', 'login_form').'</div>'.


		n.script_js(<<<EOSCR
// Focus on either username or password when empty
$(document).ready(
	function() {
		var has_name = $("#login_name").val().length;
		var password_box = $("#login_password").val();
		var has_password = (password_box) ? password_box.length : 0;
		if (!has_name) {
			$("#login_name").focus();
		} else if (!has_password) {
		 	$("#login_password").focus();
		}
	}
);
EOSCR
		).
		n.script_js('textpattern.textarray = '.json_encode($textarray_script)).
		n.'</div><!-- /txp-body -->'.n.'</body>'.n.'</html>';

		exit(0);
	}

/**
 * Validates the sent login form and creates a session.
 *
 * @return string A localised feedback message
 * @see    doLoginForm()
 */

	function doTxpValidate()
	{
		global $logout, $txp_user;
		$p_userid   = ps('p_userid');
		$p_password = ps('p_password');
		$p_reset    = ps('p_reset');
		$stay       = ps('stay');
		$logout     = gps('logout');
		$message    = '';
		$pub_path   = preg_replace('|//$|','/', rhu.'/');

		if (cs('txp_login') and strpos(cs('txp_login'), ','))
		{
			$txp_login = explode(',', cs('txp_login'));
			$c_hash = end($txp_login);
			$c_userid = join(',', array_slice($txp_login, 0, -1));
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

		if ($c_userid and strlen($c_hash) == 32) // Cookie exists.
		{
			$nonce = safe_field('nonce', 'txp_users', "name='".doSlash($c_userid)."' AND last_access > DATE_SUB(NOW(), INTERVAL 30 DAY)");

			if ($nonce and $nonce === md5($c_userid.pack('H*', $c_hash)))
			{
				// Cookie is good.

				if ($logout)
				{
					// Destroy nonce.
					safe_update(
						'txp_users',
						"nonce = '".doSlash(md5(uniqid(mt_rand(), TRUE)))."'",
						"name = '".doSlash($c_userid)."'"
					);
				}
				else
				{
					// Create $txp_user.
					$txp_user = $c_userid;
				}
				return $message;
			}
			else
			{
				setcookie('txp_login', $c_userid, time()+3600*24*365);
				setcookie('txp_login_public', '', time()-3600, $pub_path);
				$message = array(gTxt('bad_cookie'), E_ERROR);
			}

		}
		elseif ($p_userid and $p_password) // Incoming login vars.
		{
			$name = txp_validate($p_userid,$p_password);

			if ($name !== false)
			{
				$c_hash = md5(uniqid(mt_rand(), true));
				$nonce  = md5($name.pack('H*',$c_hash));

				safe_update(
					'txp_users',
					"nonce = '".doSlash($nonce)."'",
					"name = '".doSlash($name)."'"
				);

				setcookie(
					'txp_login',
					$name.','.$c_hash,
					($stay ? time()+3600*24*365 : 0),
					null,
					null,
					null,
					LOGIN_COOKIE_HTTP_ONLY
				);

				setcookie(
					'txp_login_public',
					substr(md5($nonce), -10).$name,
					($stay ? time()+3600*24*30 : 0),
					$pub_path
				);

				// Login is good, create $txp_user.
				$txp_user = $name;
				return '';
			}
			else
			{
				sleep(3);
				$message = array(gTxt('could_not_log_in'), E_ERROR);
			}
		}
		elseif ($p_reset) // Reset request.
		{
			sleep(3);

			include_once txpath.'/lib/txplib_admin.php';

			$message = ($p_userid) ? send_reset_confirmation_request($p_userid) : '';
		}
		elseif (gps('reset'))
		{
			$message = '';
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

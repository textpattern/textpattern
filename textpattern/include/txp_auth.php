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
	function txp_validate($user,$password)
	{
		$safe_user = doSlash($user);
		$passwords = array();

		$passwords[] = "password(lower('".doSlash($password)."'))";
		$passwords[] = "password('".doSlash($password)."')";

		if (version_compare(mysql_get_server_info(), '4.1.0', '>='))
		{
			$passwords[] = "old_password(lower('".doSlash($password)."'))";
			$passwords[] = "old_password('".doSlash($password)."')";
		}

		$r = safe_field("name", "txp_users",
			"name = '$safe_user' and (pass = ".join(' or pass = ', $passwords).") and privs > 0");

		if ($r)
		{
			// update the last access time
			safe_update("txp_users", "last_access = now()", "name = '$safe_user'");
			return true;

		} 
		
		return false;
	}
	
// -------------------------------------------------------------

	function doLoginForm($message)
	{
		global $txpcfg;

		include txpath.'/lib/txplib_head.php';

		pagetop(gTxt('login'));

		$stay = !(cs('txp_nostay') == 1);

		echo form(
			startTable('edit').
				n.n.tr(
					n.td().
					td(graf($message))
				).

				n.n.tr(
					n.fLabelCell('name', '', 'name').
					n.fInputCell('p_userid', '', 1, '', '', 'name')
				).

				n.n.tr(
					n.fLabelCell('password', '', 'password').
					n.td(
						fInput('password', 'p_password', '', 'edit', '', '', '', 2, 'password')
					)
				).

				n.n.tr(
					n.td().
					td(
						graf(checkbox('stay', 1, $stay, 3, 'stay').'<label for="stay">'.gTxt('stay_logged_in').'</label>'.
							sp.popHelp('remember_login'))
					)
				).

				n.n.tr(
					n.td().
					td(
						fInput('submit', '', gTxt('log_in_button'), 'publish', '', '', '', 4)
					)
				).

			endTable().

			(gps('event') ? eInput(gps('event')) : '')
			).

		n.'</div>'.n.n.'</body>'.n.'</html>';

		exit(0);
	}

// -------------------------------------------------------------
	function doTxpValidate() 
	{
		global $logout,$txpcfg, $txp_user;
		$p_userid   = ps('p_userid');
		$p_password = ps('p_password');
		$logout     = gps('logout');
		$stay       = ps('stay');
		
		if ($logout)
		{
			setcookie('txp_login','',time()-3600);
		}

		if (!empty($_COOKIE['txp_login']) and !$logout)	// cookie exists
		{
			@list($c_userid,$cookie_hash) = split(',',cs('txp_login'));

			$nonce = safe_field('nonce','txp_users',"name='".doSlash($c_userid)."'");

			if ($nonce && $nonce === md5($c_userid.pack('H*', $cookie_hash))) // check nonce
			{
				// cookie is good, create $txp_user
				$txp_user = $c_userid;
				return '';
			}
			else
			{
				// something's gone wrong
				$txp_user = '';
				setcookie('txp_login','',time()-3600);
				return gTxt('bad_cookie');
			}
			
		}
		elseif ($p_userid and $p_password) // no cookie, but incoming login vars
		{
			sleep(3); // should grind dictionary attacks to a halt
	
			if (txp_validate($p_userid,$p_password))
			{
				$cookie_hash = md5(uniqid(rand()));

				safe_update('txp_users',
					"nonce = '".doSlash(md5($p_userid.pack('H*',$cookie_hash)))."'",
					"name = '".doSlash($p_userid)."'");

				if ($stay) // persistent cookie required
				{
					setcookie('txp_login',
						$p_userid.','.$cookie_hash,
						time()+3600*24*365); // expires in 1 year

					if (cs('txp_nostay'))
					{
						setcookie('txp_nostay','',time()-3600);
					}
				} 
				else // session-only cookie required
				{
					setcookie('txp_login',$p_userid.','.$cookie_hash);    			
					setcookie('txp_nostay','1',
						time()+3600*24*365); // remember nostay for 1 year
				}
				
				$txp_user = $p_userid; // login is good, create $txp_user
				return '';
			}
			else
			{
				$txp_user = '';
				return gTxt('could_not_log_in');
			}
		}
		else
		{
			$txp_user = '';
			return gTxt('login_to_textpattern');
		}	
	}
?>

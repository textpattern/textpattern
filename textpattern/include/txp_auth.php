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

function doAuth() {
	global $txp_user;
			
	$txp_user = NULL;
	
	$message = doTxpValidate();
	
	if(!$txp_user) {
		doLoginForm($message);
	}

	ob_start();
}

// -------------------------------------------------------------
	function txp_validate($user,$password) {
    	$safe_user = addslashes($user);
    	$r = safe_field("name", 
    		"txp_users", "name = '$safe_user'
			and (pass = password(lower('".doSlash($password)."')) or pass = password('".doSlash($password)."')) and privs > 0");

    	if ($r) {

			// update the last access time
			safe_update("txp_users", "last_access = now()", "name = '$safe_user'");
			return true;

    	} else { // try old_password mysql hash

	       	$r_old = safe_field("name", 
	    		"txp_users", "name = '$safe_user'
				and (pass = old_password(lower('".doSlash($password)."')) or pass = old_password('".doSlash($password)."')) and privs > 0");
			if ($r_old) {
				safe_update("txp_users", "last_access = now()", "name = '$safe_user'");
				return true;
			}
    	}
		return false;
	}
	
// -------------------------------------------------------------
	function doLoginForm($message) 
	{
		global $txpcfg;
		include txpath.'/lib/txplib_head.php';
		pagetop('log in');
		$stay = !(cs('txp_nostay') == 1);
		echo
		form(
			startTable('edit').
				tr(
					td().td(graf($message))
				).
				tr(
					fLabelCell('name').fInputCell('p_userid')
				).
				tr(
					fLabelCell('password').
					td(fInput('password','p_password','','edit'))
				).
				tr(
					td().td(graf(checkbox('stay',1,$stay).gTxt('stay_logged_in').
					popHelp('remember_login')))
				).
				tr(
					fLabelCell('').td(fInput('submit','',gTxt('log_in_button'),'publish'))
				).
			endTable().
			(gps('event') ? eInput(gps('event')) : '')
		);
		exit("</div></body></html>");
	} 
	
	
// -------------------------------------------------------------
	function doTxpValidate() 
	{
		global $logout,$txpcfg, $txp_user;
		$p_userid = ps('p_userid');
		$p_password = ps('p_password');
		$logout = gps('logout');
		$stay = ps('stay');
		
		if ($logout) {
			setcookie('txp_login','',time()-3600);
		}
		if (!empty($_COOKIE['txp_login']) and !$logout) {	// cookie exists
	
			@list($c_userid,$cookie_hash) = split(',',cs('txp_login'));

			$nonce = safe_field('nonce','txp_users',"name='".doslash($c_userid)."'");

			if ((md5($c_userid.$nonce) === $cookie_hash) && $nonce) {  // check nonce
	
				$txp_user = $c_userid;	// cookie is good, create $txp_user
				return '';
	
			} else {
					// something's gone wrong
				$txp_user = '';
				setcookie('txp_login','',time()-3600);
				return gTxt('bad_cookie');
			}
			
		} elseif ($p_userid and $p_password) {	// no cookie, but incoming login vars
		
				sleep(3); // should grind dictionary attacks to a halt
	
				if (txp_validate($p_userid,$p_password)) {

					$nonce = safe_field('nonce','txp_users',"name='".doSlash($p_userid)."'");

					if (!$nonce) {
							define('TXP_UPDATE', 1);
							include_once txpath.'/update/_update.php';
							exit(graf('Please reload'));
					}

					if ($stay) {	// persistent cookie required

						setcookie('txp_login',
							$p_userid.','.md5($p_userid.$nonce),
							time()+3600*24*365);	// expires in 1 year
						if (cs('txp_nostay')) setcookie('txp_nostay','',time()-3600);

	
					} else {    // session-only cookie required
	
						setcookie('txp_login',$p_userid.','.md5($p_userid.$nonce));    			
						setcookie('txp_nostay','1',
							time()+3600*24*365);	// remember nostay for 1 year
					}
				
					$txp_user = $p_userid;	// login is good, create $txp_user
					return '';

				} else {
					$txp_user = '';
					return gTxt('could_not_log_in');
				}
	
		} else {
			$txp_user = '';
			return gTxt('login_to_textpattern');
		}	
	}
?>

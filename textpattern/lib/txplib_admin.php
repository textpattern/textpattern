<?php

/*
$HeadURL$
$LastChangedRevision$
*/

//-------------------------------------------------------------

	function send_password($RealName, $name, $email, $password)
	{
		global $sitename;

		require_privs('admin.edit');

		$message = gTxt('greeting').' '.$RealName.','.

			n.n.gTxt('you_have_been_registered').' '.$sitename.

			n.n.gTxt('your_login_is').': '.$name.
			n.gTxt('your_password_is').': '.$password.

			n.n.gTxt('log_in_at').': '.hu.'textpattern/index.php';

		return txpMail($email, "[$sitename] ".gTxt('your_login_info'), $message);
	}

// -------------------------------------------------------------

	function send_new_password($password, $email, $name)
	{
		global $txp_user, $sitename;

		if ( empty( $name)) $name = $txp_user;

		$message = gTxt('greeting').' '.$name.','.

			n.n.gTxt('your_password_is').': '.$password.

			n.n.gTxt('log_in_at').': '.hu.'textpattern/index.php';

		return txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message);
	}

// -------------------------------------------------------------

	function send_reset_confirmation_request($name)
	{
		global $sitename;

		$rs = safe_row('email, nonce', 'txp_users', "name = '".doSlash($name)."'");

		if ($rs)
		{
			extract($rs);

			$confirm = bin2hex(pack('H*', substr(md5($nonce), 0, 10)).$name);

			$message = gTxt('greeting').' '.$name.','.

				n.n.gTxt('password_reset_confirmation').': '.
				n.hu.'textpattern/index.php?confirm='.$confirm;

			if (txpMail($email, "[$sitename] ".gTxt('password_reset_confirmation_request'), $message))
			{
				return gTxt('password_reset_confirmation_request_sent');
			}
			else
			{
				return gTxt('could_not_mail');
			}
		}

		else
		{
			return gTxt('unknown_author', array('{name}' => htmlspecialchars($name)));
		}
	}

// -------------------------------------------------------------

	function generate_password($length = 10)
	{
		$pass = '';
		$chars = '23456789abcdefghijkmnopqrstuvwxyz';
		$i = 0;

		while ($i < $length)
		{
			$char = substr($chars, mt_rand(0, strlen($chars)-1), 1);

			if (!strstr($pass, $char))
			{
				$pass .= $char;
				$i++;
			}
		}

		return $pass;
	}

// -------------------------------------------------------------

	function reset_author_pass($name)
	{
		$email = safe_field('email', 'txp_users', "name = '".doSlash($name)."'");
		$new_pass = doSlash(generate_password(6));

		$rs = safe_update('txp_users', "pass = password(lower('$new_pass'))", "name = '".doSlash($name)."'");

		if ($rs)
		{
			if (send_new_password($new_pass, $email, $name))
			{
				return(gTxt('password_sent_to').' '.$email);
			}

			else
			{
				return(gTxt('could_not_mail').' '.$email);
			}
		}

		else
		{
			return(gTxt('could_not_update_author').' '.htmlspecialchars($name));
		}
	}
?>

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

	if (!defined('txpinterface'))
	{
		die('txpinterface is undefined.');
	}

	$levels = array(
		1 => gTxt('publisher'),
		2 => gTxt('managing_editor'),
		3 => gTxt('copy_editor'),
		4 => gTxt('staff_writer'),
		5 => gTxt('freelancer'),
		6 => gTxt('designer'),
		0 => gTxt('none')
	);

	if ($event == 'admin')
	{
		require_privs('admin');

		$available_steps = array(
			'admin',
			'author_change_pass',
			'author_delete',
			'author_list',
			'author_save',
			'author_save_new',
			'change_email',
			'change_pass'
		);

		if (!$step or !in_array($step, $available_steps))
		{
			admin();
		}

		else
		{
			$step();
		}
	}

// -------------------------------------------------------------

	function admin($message = '')
	{
		global $txp_user;

		pagetop(gTxt('site_administration'), $message);

		if (!is_callable('mail'))
		{
			echo tag(gTxt('warn_mail_unavailable'), 'p',' id="warning" ');
		}

		$email = fetch('email', 'txp_users', 'name', $txp_user);

		echo new_pass_form().
			change_email_form($email);

		if (has_privs('admin.list'))
		{
			echo author_list();
		}

		if (has_privs('admin.edit'))
		{
			echo new_author_form().
				reset_author_pass_form();
		}
	}

// -------------------------------------------------------------

	function change_email()
	{
		global $txp_user;

		$new_email = gps('new_email');

		if (!is_valid_email($new_email))
		{
			admin(gTxt('email_required'));
			return;
		}

		$rs = safe_update('txp_users', "email = '".doSlash($new_email)."'", "name = '".doSlash($txp_user)."'");

		if ($rs)
		{
			admin(
				gTxt('email_changed', array('{email}' => $new_email))
			);
		}
	}

// -------------------------------------------------------------

	function author_save()
	{
		require_privs('admin.edit');

		extract(doSlash(psa(array('privs', 'user_id', 'RealName', 'email'))));
		$privs   = assert_int($privs);
		$user_id = assert_int($user_id);

		if (!is_valid_email($email))
		{
			admin(gTxt('email_required'));
			return;
		}

		$rs = safe_update('txp_users', "
			privs		 = $privs,
			RealName = '$RealName',
			email		 = '$email'",
			"user_id = $user_id"
		);

		if ($rs)
		{
			admin(
				gTxt('author_updated', array('{name}' => $RealName))
			);
		}
	}

// -------------------------------------------------------------

	function change_pass()
	{
		global $txp_user;

		extract(doSlash(psa(array('new_pass', 'mail_password'))));

		if (empty($new_pass))
		{
			admin(gTxt('password_required'));
			return;
		}

		$rs = safe_update('txp_users', "pass = password(lower('$new_pass'))", "name = '".doSlash($txp_user)."'");

		if ($rs)
		{
			$message = gTxt('password_changed');

			if ($mail_password)
			{
				$email = fetch('email', 'txp_users', 'name', $txp_user);

				send_new_password($new_pass, $email, $txp_user);

				$message .= sp.gTxt('and_mailed_to').sp.$email;
			}

			else
			{
				echo comment(mysql_error());
			}

			$message .= '.';

			admin($message);
		}
	}

// -------------------------------------------------------------

	function author_save_new()
	{
		require_privs('admin.edit');

		extract(doSlash(psa(array('privs', 'name', 'email', 'RealName'))));
		$privs = assert_int($privs);

		if ($name && is_valid_email($email))
		{
			$password = doSlash(generate_password(6));

			$rs = safe_insert('txp_users', "
				privs    = $privs,
				name     = '$name',
				email    = '$email',
				RealName = '$RealName',
				pass     = password(lower('$password'))
			");

			if ($rs)
			{
				send_password($RealName, $name, $email, $password);

				admin(
					gTxt('password_sent_to').sp.$email
				);

				return;
			}
		}

		admin(gTxt('error_adding_new_author'));
	}

// -------------------------------------------------------------

	function privs($priv = '')
	{
		global $levels;
		return selectInput('privs', $levels, $priv);
	}

// -------------------------------------------------------------

	function get_priv_level($priv)
	{
		global $levels;
		return $levels[$priv];
	}

// -------------------------------------------------------------

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

	function generate_password($length = 10)
	{
		$pass = '';
		$chars = '023456789bcdfghjkmnpqrstvwxyz';
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

	function new_pass_form()
	{
		return '<div style="margin: 3em auto auto auto; text-align: center;">'.
		form(
			tag(gTxt('change_password'), 'h3').

			graf('<label for="new_pass">'.gTxt('new_password').'</label> '.
				fInput('password', 'new_pass', '', 'edit', '', '', '20', '1', 'new_pass').
				checkbox('mail_password', '1', true, '', 'mail_password').'<label for="mail_password">'.gTxt('mail_it').'</label> '.
				fInput('submit', 'change_pass', gTxt('submit'), 'smallerbox').
				eInput('admin').
				sInput('change_pass')
			,' style="text-align: center;"')
		).'</div>';
	}

// -------------------------------------------------------------

	function reset_author_pass_form()
	{
		global $txp_user;

		$names = array();

		$them = safe_rows_start('*', 'txp_users', "name != '".doSlash($txp_user)."'");

		while ($a = nextRow($them))
		{
			extract($a);

			$names[$name] = $RealName.' ('.$name.')';
		}

		if ($names)
		{
			return '<div style="margin: 3em auto auto auto; text-align: center;">'.
			form(
				tag(gTxt('reset_author_password'), 'h3').
				graf(gTxt('a_new_password_will_be_mailed')).
					graf(selectInput('name', $names, '', 1).
					fInput('submit', 'author_change_pass', gTxt('submit'), 'smallerbox').
					eInput('admin').
					sInput('author_change_pass')
				,' style="text-align: center;"')
			).'</div>';
		}
	}

// -------------------------------------------------------------

	function author_change_pass()
	{
		require_privs('admin.edit');

		$name = ps('name');

		$email = safe_field('email', 'txp_users', "name = '".doSlash($name)."'");
		$new_pass = doSlash(generate_password(6));

		$rs = safe_update('txp_users', "pass = password(lower('$new_pass'))", "name = '".doSlash($name)."'");

		if ($rs)
		{
			if (send_new_password($new_pass, $email, $name))
			{
				admin(gTxt('password_sent_to').' '.$email);
			}

			else
			{
				admin(gTxt('could_not_mail').' '.$email);
			}
		}

		else
		{
			admin(gTxt('could_not_update_author').' '.$name);
		}
	}

// -------------------------------------------------------------

	function change_email_form($email)
	{
		return '<div style="margin: 3em auto auto auto; text-align: center;">'.
		form(
			tag(gTxt('change_email_address'), 'h3').
			graf('<label for="new_email">'.gTxt('new_email').'</label> '.
				fInput('text', 'new_email', $email, 'edit', '', '', '20', '2', 'new_email').
				fInput('submit', 'change_email', gTxt('submit'), 'smallerbox').
				eInput('admin').
				sInput('change_email')
			,' style="text-align: center;"')
		).'</div>';
	}

// -------------------------------------------------------------

	function author_list()
	{
		global $txp_user;

		echo n.n.hed(gTxt('authors'), 3,' style="text-align: center;"').

			n.n.startTable('list').

			n.tr(
				n.hCell(gTxt('real_name')).
				n.hCell(gTxt('login_name')).
				n.hCell(gTxt('email')).
				n.hCell(gTxt('privileges')).
				n.hCell().
				n.hCell()
			);

		$rs = safe_rows_start('*', 'txp_users', '1 = 1 order by name asc');

		if ($rs)
		{
			if (has_privs('admin.edit'))
			{
				while ($a = nextRow($rs))
				{
					extract($a);

					echo n.n.'<tr>'.

						n.'<form method="post" action="index.php">'.

						n.td(
							fInput('text', 'RealName', $RealName, 'edit')
						).

						td(htmlspecialchars($name)).
						td(
							fInput('text', 'email', $email, 'edit')
						);

					if ($name != $txp_user)
					{
						echo td(
							privs($privs).sp.popHelp('about_privileges')
						);
					}

					else
					{
						echo td(
							get_priv_level($privs).sp.popHelp('about_privileges').
							hInput('privs', $privs)
						);
					}

					echo td(
						fInput('submit', 'save', gTxt('save'), 'smallerbox')
					).

					n.hInput('user_id', $user_id).
					n.eInput('admin').
					n.sInput('author_save').
					n.'</form>';

					if ($name != $txp_user)
					{
						echo td(
							dLink('admin', 'author_delete', 'user_id', $user_id)
						);
					}

					else
					{
						echo td();
					}

					echo n.'</tr>';
				}
			}

			else
			{
				while ($a = nextRow($rs))
				{
					extract(doSpecial($a));

					echo tr(
						td($RealName).
						td($name).
						td('<a href="mailto:'.$email.'">'.$email.'</a>').
						td(
							get_priv_level($privs).sp.popHelp('about_privileges').
							hInput('privs', $privs)
						).
						td().
						td()
					);
				}
			}

			echo n.endTable();
		}
	}

// -------------------------------------------------------------

	function author_delete()
	{
		require_privs('admin.edit');

		$user_id = assert_int(ps('user_id'));

		$name = fetch('Realname', 'txp_users', 'user_id', $user_id);

		if ($name)
		{
			$rs = safe_delete('txp_users', "user_id = $user_id");

			if ($rs)
			{
				admin(
					gTxt('author_deleted', array('{name}' => $name))
				);
			}
		}
	}

// -------------------------------------------------------------

	function new_author_form()
	{
		return form(
			hed(gTxt('add_new_author'), 3,' style="margin-top: 2em; text-align: center;"').
			graf(gTxt('a_message_will_be_sent_with_login'), ' style="text-align: center;"').

			startTable('edit').
			tr(
				fLabelCell('real_name').
				fInputCell('RealName')
			).

			tr(
				fLabelCell('login_name').
				fInputCell('name')
			).

			tr(
				fLabelCell('email').
				fInputCell('email')
			).

			tr(
				fLabelCell('privileges').
				td(
					privs().sp.popHelp('about_privileges')
				)
			).

			tr(
				td().
				td(
					fInput('submit', '', gTxt('save'), 'publish').sp.popHelp('add_new_author')
				)
			).

			endTable().

			eInput('admin').
			sInput('author_save_new')
		);
	}

?>

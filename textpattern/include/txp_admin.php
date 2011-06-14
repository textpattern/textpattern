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

	$levels = get_groups();

	if ($event == 'admin')
	{
		require_privs('admin');

		include_once txpath.'/lib/txplib_admin.php';

		$available_steps = array(
			'admin_multi_edit' 	=> true,
			'admin_change_pageby' => true,
			'author_edit' 		=> false,
			'author_save' 		=> true,
			'author_save_new' 	=> true,
			'change_email' 		=> true,
			'change_pass' 		=> true
		);

		if (!$step or !bouncer($step, $available_steps)) {
			$step = 'author_edit';
		}
		$step();
	}

// -------------------------------------------------------------

	function author_edit($message = '')
	{
		global $txp_user;

		pagetop(gTxt('site_administration'), $message);

		if (is_disabled('mail'))
		{
			echo tag(gTxt('warn_mail_unavailable'), 'p',' id="warning" ');
		}

		$email = fetch('email', 'txp_users', 'name', $txp_user);

		echo n.'<div id="users_container" class="txp-container">';

		if (has_privs('admin.edit'))
		{
			echo n.'<div class="txp-edit">'.author_form().'</div>';
		}

		if (has_privs('admin.list'))
		{
			echo author_list();
		}

		echo new_pass_form();

		if (!has_privs('admin.edit'))
		{
			echo change_email_form($email);
		}
		echo n.'</div>';
	}

// -------------------------------------------------------------

	function change_email()
	{
		global $txp_user;

		$new_email = gps('new_email');

		if (!is_valid_email($new_email))
		{
			author_edit(array(gTxt('email_required'), E_ERROR));
			return;
		}

		$rs = safe_update('txp_users', "email = '".doSlash($new_email)."'", "name = '".doSlash($txp_user)."'");

		if ($rs)
		{
			author_edit(
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
			author_edit(array(gTxt('email_required'), E_ERROR));
			return;
		}

		$rs = safe_update('txp_users', "
			privs    = $privs,
			RealName = '$RealName',
			email    = '$email'",
			"user_id = $user_id"
		);

		if ($rs)
		{
			author_edit(
				gTxt('author_updated', array('{name}' => $RealName))
			);
		}
	}

// -------------------------------------------------------------

	function change_pass()
	{
		global $txp_user;

		extract(psa(array('new_pass', 'mail_password')));

		if (empty($new_pass))
		{
			author_edit(array(gTxt('password_required'), E_ERROR));
			return;
		}

		$hash = doSlash(txp_hash_password($new_pass));
		$rs = safe_update('txp_users', "pass = '$hash'", "name = '".doSlash($txp_user)."'");

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

			author_edit($message);
		}
	}

// -------------------------------------------------------------

	function author_save_new()
	{
		require_privs('admin.edit');

		extract(doSlash(psa(array('privs', 'name', 'email', 'RealName'))));

		$privs  = assert_int($privs);
		$length = function_exists('mb_strlen') ? mb_strlen($name, '8bit') : strlen($name);

		if ($name and $length <= 64 and is_valid_email($email))
		{
			$exists = safe_field('name', 'txp_users', "name = '" .$name. "'");

			if ($exists)
			{
				author_edit(array(gTxt('author_already_exists', array('{name}' => $name)), E_ERROR));
				return;
			}

			$password = generate_password(6);
			$hash	= doSlash(txp_hash_password($password));
			$nonce	= doSlash(md5(uniqid(mt_rand(), TRUE)));

			$rs = safe_insert('txp_users', "
				privs    = $privs,
				name     = '$name',
				email    = '$email',
				RealName = '$RealName',
				nonce    = '$nonce',
				pass     = '$hash'
			");

			if ($rs)
			{
				send_password($RealName, $name, $email, $password);

				author_edit(
					gTxt('password_sent_to').sp.$email
				);

				return;
			}
		}

		author_edit(array(gTxt('error_adding_new_author'), E_ERROR));
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
		, '', '', 'post', '', '', 'change_password').'</div>';
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
		, '', '', 'post', '','', 'change_email').'</div>';
	}

// -------------------------------------------------------------

	function author_list()
	{
		global $txp_user, $author_list_pageby;

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('admin_sort_column', 'name');
		if ($dir === '') $dir = get_pref('admin_sort_dir', 'asc');
		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		if (!in_array($sort, array('name', 'RealName', 'email', 'privs', 'last_login'))) $sort = 'name';

		$sort_sql   = $sort.' '.$dir;

		set_pref('admin_sort_column', $sort, 'admin', 2, '', 0, PREF_PRIVATE);
		set_pref('admin_sort_dir', $dir, 'admin', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$total = getCount('txp_users', '1=1');
		$limit = max($author_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		$rs = safe_rows_start('*, unix_timestamp(last_access) as last_login', 'txp_users', '1 = 1 order by '.$sort_sql.' limit '.$offset.', '.$limit);

		if ($rs)
		{
			echo n.'<div class="txp-list">';
			echo '<form action="index.php" id="users_form" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

			startTable('list', '', 'list').
			n.'<thead>'.
			n.tr(
				column_head('login_name', 'name', 'admin', true, $switch_dir, '', '', (('name' == $sort) ? "$dir " : '').'name login-name').
				column_head('real_name', 'RealName', 'admin', true, $switch_dir, '', '', (('RealName' == $sort) ? "$dir " : '').'name real-name').
				column_head('email', 'email', 'admin', true, $switch_dir, '', '', (('email' == $sort) ? "$dir " : '').'email').
				column_head('privileges', 'privs', 'admin', true, $switch_dir, '', '', (('privs' == $sort) ? "$dir " : '').'privs').
				column_head('last_login', 'last_login', 'admin', true, $switch_dir, '', '', (('last_login' == $sort) ? "$dir " : '').'date last-login modified').
				hCell('', '', ' class="actions"').
				hCell('', '', ' class="multi-edit"')
			).
			n.'</thead>';

			$tfoot = n.'<tfoot>'.tr(
				tda(
					((has_privs('admin.edit'))
						? select_buttons().
						author_multiedit_form($page, $sort, $dir, $crit, $search_method)
						: '')
				, ' class="multi-edit" colspan="7" style="text-align: right; border: none;"')
			).n.'</tfoot>';

			echo $tfoot;
			echo '<tbody>';

			$ctr = 1;

			while ($a = nextRow($rs))
			{
				extract(doSpecial($a));

				echo tr(
					td($name, '', 'name login-name').
					td($RealName, '', 'name real-name').
					td('<a href="mailto:'.$email.'">'.$email.'</a>', '', 'email').
					td(get_priv_level($privs), '', 'privs').
					td(($last_login ? safe_strftime('%b&#160;%Y', $last_login) : ''), '', 'date last-login modified').
					td(((has_privs('admin.edit')) ? eLink('admin', 'author_edit', 'user_id', $user_id, gTxt('edit')) : ''), '', 'actions').
					td(((has_privs('admin.edit') and $txp_user != $a['name']) ? fInput('checkbox', 'selected[]', $a['name'], 'checkbox') : ''), '', 'multi-edit')
				, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').'"'
				);

				$ctr++;
			}

			echo '</tbody>'.
			n.endTable().
			n.tInput().
			n.'</form>'.

			n.'<div id="users_navigation" class="txp-navigation">'.
			nav_form('admin', $page, $numPages, $sort, $dir, $crit, $search_method).

			pageby_form('admin', $author_list_pageby).
			n.'</div>'.n.'</div>';
		}
	}

// -------------------------------------------------------------

	function author_form()
	{
		global $step, $txp_user;

		$vars = array('user_id', 'name', 'RealName', 'email', 'privs');
		$rs = array();

		extract(gpsa($vars));
		if ($user_id && $step == 'author_edit')
		{
			$user_id = assert_int($user_id);
			$rs = safe_row('*', 'txp_users', "user_id = $user_id");
			extract($rs);
		}

		if ($step == 'author_save' or $step == 'author_save_new')
		{
			foreach ($vars as $var)
			{
				$$var = '';
			}
		}

		$caption = gTxt(($user_id && $step == 'author_edit') ? 'edit_author' : 'add_new_author');

		return form(

			hed($caption, 3,' style="text-align: center;"').

			startTable('edit', '', 'edit-pane').

			tr(
				fLabelCell('login_name', '', 'name').
				($user_id && $step == 'author_edit' ? td(strong($name)) : fInputCell('name', $name))
			, ' class="name login-name"').

			tr(
				fLabelCell('real_name', '', 'RealName').
				fInputCell('RealName', $RealName)
			, ' class="name real-name"').

			tr(
				fLabelCell('email', '', 'email').
				fInputCell('email', $email)
			, ' class="email"').

			tr(
				fLabelCell('privileges', '', 'privs').
				td(
					($txp_user != $name
						? privs($privs)
						: hInput('privs', $privs).strong(get_priv_level($privs))
					)
					.sp.popHelp('about_privileges')
				)
			, ' class="privs"').

			pluggable_ui('author_ui', 'extend_detail_form', '', $rs).

			tr(
				td().
				td(
					fInput('submit', '', gTxt('save'), 'publish').($user_id ? '' : sp.popHelp('add_new_author'))
				)
			).

			endTable().

			eInput('admin').
			($user_id ? hInput('user_id', $user_id).sInput('author_save') : sInput('author_save_new'))
		, '', '', 'post', 'edit-form', '', 'user_edit');
	}

// -------------------------------------------------------------

	function admin_change_pageby()
	{
		event_change_pageby('author');
		author_edit();
	}

// -------------------------------------------------------------

	function author_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changeprivilege' => gTxt('changeprivilege'),
			'resetpassword' => gTxt('resetpassword'),
			'delete' => gTxt('delete')
		);

		if (safe_count('txp_users', '1=1') <= 1) unset($methods['delete']); // Sorry guy, you're last.

		return event_multiedit_form('admin', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function admin_multi_edit()
	{
		global $txp_user;

		require_privs('admin.edit');

		$selected = ps('selected');
		$method   = ps('edit_method');
		$changed  = array();

		if (!$selected or !is_array($selected))
		{
			return author_edit();
		}

		$names = safe_column('name', 'txp_users', "name IN ('".join("','", doSlash($selected))."') AND name != '".doSlash($txp_user)."'");

		if (!$names) return author_edit();

		switch ($method)
		{
			case 'delete':

				$assign_assets = ps('assign_assets');
				if ($assign_assets === '')
				{
					$msg = array('must_reassign_assets', E_ERROR);
				}
				elseif (in_array($assign_assets, $names))
				{
					$msg = array('cannot_assign_assets_to_deletee', E_ERROR);
				}

				elseif (safe_delete('txp_users', "name IN ('".join("','", doSlash($names))."')"))
				{
					$changed = $names;
					$assign_assets = doSlash($assign_assets);
					$names = join("','", doSlash($names));

					// delete private prefs
					safe_delete('txp_prefs', "user_name IN ('$names')");

					// assign dangling assets to their new owner
					$reassign = array(
						'textpattern' => 'AuthorID',
						'txp_file' 	=> 'author',
						'txp_image' => 'author',
						'txp_link' 	=> 'author',
					);
					foreach ($reassign as $table => $col)
					{
						safe_update($table, "$col='$assign_assets'", "$col IN ('$names')");
					}
					$msg = 'author_deleted';
				}

				break;

			case 'changeprivilege':

				global $levels;

				$privilege = ps('privs');

				if (!isset($levels[$privilege])) return author_edit();

				if (safe_update('txp_users', 'privs = '.intval($privilege), "name IN ('".join("','", doSlash($names))."')"))
				{
					$changed = $names;
					$msg = 'author_updated';
				}

				break;

			case 'resetpassword':

				$failed  = array();

				foreach ($names as $name)
				{
					$passwd = generate_password(6);
					$hash 	= doSlash(txp_hash_password($passwd));

					if (safe_update('txp_users', "pass = '$hash'", "name = '".doSlash($name)."'"))
					{
						$email = safe_field('email', 'txp_users', "name = '".doSlash($name)."'");

						if (send_new_password($passwd, $email, $name))
						{
							$changed[] = $name;
							$msg = 'author_updated';
						}
						else
						{
							return author_edit(array(gTxt('could_not_mail').' '.htmlspecialchars($name), E_ERROR));
						}
					}
				}

				break;
		}

		if ($changed)
		{
			return author_edit(gTxt($msg, array('{name}' => htmlspecialchars(join(', ', $changed)))));
		}

		author_edit($msg);
	}
// -------------------------------------------------------------
//	@deprecated
	function admin($message = '')
	{
		author_edit($message);
	}

?>

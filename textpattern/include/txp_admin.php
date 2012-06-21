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
			'admin_multi_edit'    => true,
			'admin_change_pageby' => true,
			'author_list'         => false,
			'author_edit'         => true,
			'author_save'         => true,
			'author_save_new'     => true,
			'change_email'        => true,
			'change_email_form'   => true,
			'change_pass'         => true,
			'new_pass_form'       => true,
		);

		if (!$step or !bouncer($step, $available_steps)) {
			$step = 'author_list';
		}
		$step();
	}

// -------------------------------------------------------------

	function change_email()
	{
		global $txp_user;

		$new_email = gps('new_email');

		if (!is_valid_email($new_email))
		{
			author_list(array(gTxt('email_required'), E_ERROR));
			return;
		}

		$rs = safe_update('txp_users', "email = '".doSlash($new_email)."'", "name = '".doSlash($txp_user)."'");

		if ($rs)
		{
			author_list(
				gTxt('email_changed', array('{email}' => $new_email))
			);
		}
	}

// -------------------------------------------------------------

	function author_save()
	{
		require_privs('admin.edit');

		extract(doSlash(psa(array('login_privileges', 'user_id', 'real_name', 'login_email'))));
		$privs   = assert_int($login_privileges);
		$user_id = assert_int($user_id);

		if (!is_valid_email($login_email))
		{
			author_list(array(gTxt('email_required'), E_ERROR));
			return;
		}

		$rs = safe_update('txp_users', "
			privs    = $privs,
			RealName = '$real_name',
			email    = '$login_email'",
			"user_id = $user_id"
		);

		if ($rs)
		{
			author_list(
				gTxt('author_updated', array('{name}' => $real_name))
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
			author_list(array(gTxt('password_required'), E_ERROR));
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

			author_list($message);
		}
	}

// -------------------------------------------------------------

	function author_save_new()
	{
		require_privs('admin.edit');

		extract(doSlash(psa(array('login_privileges', 'login_name', 'login_email', 'real_name'))));

		$privs  = assert_int($login_privileges);
		$length = function_exists('mb_strlen') ? mb_strlen($login_name, '8bit') : strlen($login_name);

		if ($login_name and $length <= 64 and is_valid_email($login_email))
		{
			$exists = safe_field('name', 'txp_users', "name = '" .$login_name. "'");

			if ($exists)
			{
				author_list(array(gTxt('author_already_exists', array('{name}' => $login_name)), E_ERROR));
				return;
			}

			$password = generate_password(PASSWORD_LENGTH);
			$hash	= doSlash(txp_hash_password($password));
			$nonce	= doSlash(md5(uniqid(mt_rand(), TRUE)));

			$rs = safe_insert('txp_users', "
				privs    = $privs,
				name     = '$login_name',
				email    = '$login_email',
				RealName = '$real_name',
				nonce    = '$nonce',
				pass     = '$hash'
			");

			if ($rs)
			{
				send_password($real_name, $login_name, $login_email, $password);

				author_list(
					gTxt('password_sent_to').sp.$login_email
				);

				return;
			}
		}

		author_list(array(gTxt('error_adding_new_author'), E_ERROR));
	}

// -------------------------------------------------------------

	function privs($priv = '')
	{
		global $levels;
		return selectInput('login_privileges', $levels, $priv, '', '', 'login_privileges');
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
		global $step, $txp_user;

		pagetop(gTxt('tab_site_admin'), '');

		echo form(
			'<div class="txp-edit">'.
			hed(gTxt('change_password'), 2).
			inputLabel('new_pass', fInput('password', 'new_pass', '', '', '', '', INPUT_REGULAR, '', 'new_pass'), 'new_password').n.
			graf(checkbox('mail_password', '1', true, '', 'mail_password') . '<label for="mail_password">'.gTxt('mail_it').'</label>', ' class="edit-mail-password"').n.
			graf(fInput('submit', 'change_pass', gTxt('submit'), 'publish')).
			eInput('admin').
			sInput('change_pass').
			'</div>'
		, '', '', 'post', '', '', 'change_password');
	}

// -------------------------------------------------------------

	function change_email_form()
	{
		global $step, $txp_user;

		pagetop(gTxt('tab_site_admin'), '');

		$email = fetch('email', 'txp_users', 'name', $txp_user);

		echo form(
			'<div class="txp-edit">'.
			hed(gTxt('change_email_address'), 2).
			inputLabel('new_email', fInput('text', 'new_email', $email, '', '', '', INPUT_REGULAR, '', 'new_email'), 'new_email').n.
			graf(fInput('submit', 'change_email', gTxt('submit'), 'publish')).
			eInput('admin').
			sInput('change_email').
			'</div>'
		, '', '', 'post', '','', 'change_email');
	}

// -------------------------------------------------------------

	function author_list($message = '')
	{
		global $txp_user, $author_list_pageby;

		pagetop(gTxt('tab_site_admin'), $message);

		if (is_disabled('mail'))
		{
			echo tag(gTxt('warn_mail_unavailable'), 'p',' id="warning" ');
		}

		echo '<h1 class="txp-heading">'.gTxt('tab_site_admin').'</h1>';
		echo '<div id="users_control" class="txp-control-panel">';

		// Change password button
		echo '<div class="txp-buttons">';
		echo n.form(
			graf(
				fInput('submit', '', gTxt('change_password')).
				eInput('admin').
				sInput('new_pass_form')
			)
			, '', '', 'post', '', '', 'change_password');

		// Change email address button
		if (!has_privs('admin.edit'))
		{
			echo n.form(
				graf(
					fInput('submit', '', gTxt('change_email_address')).
					eInput('admin').
					sInput('change_email_form')
				)
				, '', '', 'post', '', '', 'change_email_address');
		}

		// User list
		if (has_privs('admin.list'))
		{
			extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
			if ($sort === '') $sort = get_pref('admin_sort_column', 'name');
			if ($dir === '') $dir = get_pref('admin_sort_dir', 'asc');
			$dir = ($dir == 'desc') ? 'desc' : 'asc';

			if (!in_array($sort, array('name', 'RealName', 'email', 'privs', 'last_login'))) $sort = 'name';

			$sort_sql = $sort.' '.$dir;

			set_pref('admin_sort_column', $sort, 'admin', 2, '', 0, PREF_PRIVATE);
			set_pref('admin_sort_dir', $dir, 'admin', 2, '', 0, PREF_PRIVATE);

			$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

			$criteria = 1;

			if ($search_method and $crit != '')
			{
				$crit_escaped = doSlash(str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));

				$critsql = array(
					'id'        => "user_id in ('" .join("','", do_list($crit_escaped)). "')",
					'login'     => "name like '%$crit_escaped%'",
					'real_name' => "RealName like '%$crit_escaped%'",
					'email'     => "email like '%$crit_escaped%'",
					'privs'     => "privs in ('" .join("','", do_list($crit_escaped)). "')",
				);

				if (array_key_exists($search_method, $critsql))
				{
					$criteria = $critsql[$search_method];
				}

				else
				{
					$search_method = '';
					$crit = '';
				}
			}

			else
			{
				$search_method = '';
				$crit = '';
			}

			$total = getCount('txp_users', $criteria);

			if (has_privs('admin.edit'))
			{
				echo n.form(
					graf(
						fInput('submit', '', gTxt('add_new_author')).
						eInput('admin').
						sInput('author_edit')
					)
					, '', '', 'post', '', '', 'author_create');
			}
			echo '</div>'; // end buttons

			if ($total < 1)
			{
				if ($criteria != 1)
				{
					echo n.author_search_form($crit, $search_method).
						n.graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
				}

				return;
			}

			$limit = max($author_list_pageby, 15);

			list($page, $offset, $numPages) = pager($total, $limit, $page);

			echo author_search_form($crit, $search_method).'</div>';

			$rs = safe_rows_start('*, unix_timestamp(last_access) as last_login', 'txp_users', "$criteria order by $sort_sql limit $offset, $limit");

			if ($rs)
			{
				echo n.'<div id="users_container" class="txp-container">';
				echo '<form action="index.php" id="users_form" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

				n.'<div class="txp-listtables">'.
				n.startTable('', '', 'txp-list').
				n.'<thead>'.
				n.tr(
					column_head('login_name', 'name', 'admin', true, $switch_dir, '', '', (('name' == $sort) ? "$dir " : '').'name login-name').
					column_head('real_name', 'RealName', 'admin', true, $switch_dir, '', '', (('RealName' == $sort) ? "$dir " : '').'name real-name').
					column_head('email', 'email', 'admin', true, $switch_dir, '', '', (('email' == $sort) ? "$dir " : '').'email').
					column_head('privileges', 'privs', 'admin', true, $switch_dir, '', '', (('privs' == $sort) ? "$dir " : '').'privs').
					column_head('last_login', 'last_login', 'admin', true, $switch_dir, '', '', (('last_login' == $sort) ? "$dir " : '').'date last-login modified').
					hCell('', '', ' class="multi-edit"')
				).
				n.'</thead>';

				$tfoot = n.'<tfoot>'.tr(
					tda(
						((has_privs('admin.edit'))
							? select_buttons().n.
							author_multiedit_form($page, $sort, $dir, $crit, $search_method)
							: '')
					, ' class="multi-edit" colspan="6"')
				).n.'</tfoot>';

				echo $tfoot;
				echo '<tbody>';

				$ctr = 1;

				while ($a = nextRow($rs))
				{
					extract(doSpecial($a));

					echo tr(
						td(((has_privs('admin.edit')) ? eLink('admin', 'author_edit', 'user_id', $user_id, $name) : $name), '', 'name login-name').
						td($RealName, '', 'name real-name').
						td('<a href="mailto:'.$email.'">'.$email.'</a>', '', 'email').
						td(get_priv_level($privs), '', 'privs').
						td(($last_login ? safe_strftime('%b&#160;%Y', $last_login) : ''), '', 'date last-login modified').
						td(((has_privs('admin.edit') and $txp_user != $a['name']) ? fInput('checkbox', 'selected[]', $a['name'], 'checkbox') : ''), '', 'multi-edit')
					, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').'"'
					);

					$ctr++;
				}

				echo '</tbody>'.
				n.endTable().
				n.'</div>'.
				n.tInput().
				n.'</form>'.

				n.'<div id="users_navigation" class="txp-navigation">'.
				nav_form('admin', $page, $numPages, $sort, $dir, $crit, $search_method).

				pageby_form('admin', $author_list_pageby).
				n.'</div>'.n.'</div>';
			}
		}

		else
		{
			echo '</div>';
		}
	}

// -------------------------------------------------------------

	function author_search_form($crit, $method)
	{
		$methods =	array(
			'id'        => gTxt('ID'),
			'login'     => gTxt('login_name'),
			'real_name' => gTxt('real_name'),
			'email'     => gTxt('email'),
			'privs'     => gTxt('privileges'),
		);

		return search_form('admin', 'author_list', $crit, $methods, $method, 'login');
	}

// -------------------------------------------------------------

	function author_edit()
	{
		global $step, $txp_user;

		pagetop(gTxt('tab_site_admin'), '');

		$vars = array('user_id', 'name', 'RealName', 'email', 'privs');
		$rs = array();

		extract(gpsa($vars));
		if ($user_id && $step == 'author_edit')
		{
			$user_id = assert_int($user_id);
			$rs = safe_row('*', 'txp_users', "user_id = $user_id");
			extract($rs);
		}

		$caption = gTxt(($user_id && $step == 'author_edit') ? 'edit_author' : 'add_new_author');

		echo form(
			'<div class="txp-edit">'.n.
			hed($caption, 2).n.
			inputLabel('login_name', ($user_id && $step == 'author_edit' ? strong($name) : fInput('text', 'login_name', $name, '', '', '', INPUT_REGULAR, '', 'login_name')), 'login_name', 'add_new_author').n.
			inputLabel('real_name', fInput('text', 'real_name', $RealName, '', '', '', INPUT_REGULAR, '', 'real_name'), 'real_name').n.
			inputLabel('login_email', fInput('text', 'login_email', $email, '', '', '', INPUT_REGULAR, '', 'login_email'), 'email').n.
			inputLabel('login_privileges', (($txp_user != $name) ? privs($privs) : hInput('login_privileges', $privs).strong(get_priv_level($privs))), 'privileges', 'about_privileges').n.
			pluggable_ui('author_ui', 'extend_detail_form', '', $rs).n.
			graf(fInput('submit', '', gTxt('save'), 'publish')).
			eInput('admin').
			($user_id ? hInput('user_id', $user_id).sInput('author_save') : sInput('author_save_new')).
			'</div>'
		, '', '', 'post', 'edit-form', '', 'user_edit');
	}

// -------------------------------------------------------------

	function admin_change_pageby()
	{
		event_change_pageby('author');
		author_list();
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
			return author_list();
		}

		$names = safe_column('name', 'txp_users', "name IN ('".join("','", doSlash($selected))."') AND name != '".doSlash($txp_user)."'");

		if (!$names) return author_list();

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

				$privilege = ps('login_privileges');

				if (!isset($levels[$privilege])) return author_list();

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
					$passwd = generate_password(PASSWORD_LENGTH);
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
							return author_list(array(gTxt('could_not_mail').' '.txpspecialchars($name), E_ERROR));
						}
					}
				}

				break;
		}

		if ($changed)
		{
			return author_list(gTxt($msg, array('{name}' => txpspecialchars(join(', ', $changed)))));
		}

		author_list($msg);
	}
// -------------------------------------------------------------
//	@deprecated
	function admin($message = '')
	{
		author_list($message);
	}

?>

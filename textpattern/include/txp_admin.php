<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2012 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Users panel.
 *
 * @package Admin\Admin
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
			'author_edit'         => false,
			'author_save'         => true,
			'author_save_new'     => true,
			'change_email'        => true,
			'change_email_form'   => false,
			'change_pass'         => true,
			'new_pass_form'       => false,
		);

		if ($step && bouncer($step, $available_steps))
		{
			$step();
		}
		else
		{
			author_list();
		}
	}

/**
 * Changes an email address.
 */

	function change_email()
	{
		global $txp_user;

		$new_email = ps('new_email');

		if (!is_valid_email($new_email))
		{
			author_list(array(gTxt('email_required'), E_ERROR));
			return;
		}

		$rs = update_user($txp_user, $new_email);

		if ($rs)
		{
			author_list(gTxt('email_changed', array('{email}' => $new_email)));
			return;
		}

		author_list(array(gTxt('author_save_failed'), E_ERROR));
	}

/**
 * Updates a user.
 */

	function author_save()
	{
		global $txp_user;

		require_privs('admin.edit');

		extract(psa(array(
			'privs',
			'name',
			'RealName',
			'email',
		)));

		$privs = assert_int($privs);

		if (!is_valid_email($email))
		{
			author_list(array(gTxt('email_required'), E_ERROR));
			return;
		}

		$rs = update_user($name, $email, $RealName);

		if ($rs && ($txp_user === $name || change_user_group($name, $privs)))
		{
			author_list(gTxt('author_updated', array('{name}' => $RealName)));
			return;
		}

		author_list(array(gTxt('author_save_failed'), E_ERROR));
	}

/**
 * Changes current user's password.
 */

	function change_pass()
	{
		global $txp_user;

		extract(psa(array('new_pass', 'mail_password')));

		if (empty($new_pass))
		{
			author_list(array(gTxt('password_required'), E_ERROR));
			return;
		}

		$rs = change_user_password($txp_user, $new_pass);

		if ($rs)
		{
			$message = gTxt('password_changed');

			if ($mail_password)
			{
				$email = fetch('email', 'txp_users', 'name', $txp_user);

				send_new_password($new_pass, $email, $txp_user);

				$message .= sp.gTxt('and_mailed_to').sp.$email;
			}

			$message .= '.';

			author_list($message);
		}
	}

/**
 * Creates a new user.
 */

	function author_save_new()
	{
		require_privs('admin.edit');

		extract(psa(array(
			'privs',
			'name',
			'email',
			'RealName',
		)));

		$privs = assert_int($privs);

		if (is_valid_username($name) && is_valid_email($email))
		{
			if (user_exists($name))
			{
				author_list(array(gTxt('author_already_exists', array('{name}' => $name)), E_ERROR));
				return;
			}

			$password = generate_password(PASSWORD_LENGTH);

			$rs = create_user($name, $email, $password, $RealName, $privs);

			if ($rs)
			{
				send_password($RealName, $name, $email, $password);

				author_list(
					gTxt('password_sent_to').sp.$email
				);

				return;
			}
		}

		author_list(array(gTxt('error_adding_new_author'), E_ERROR));
	}

/**
 * Lists user groups as a &lt;select&gt; input.
 *
 * @param  int $priv Selected option
 * @return string HTML
 */

	function privs($priv = '')
	{
		global $levels;
		return selectInput('privs', $levels, $priv, '', '', 'privileges');
	}

/**
 * Translates a numeric ID to a human-readable user group.
 *
 * @param  int $priv The group
 * @return string
 */

	function get_priv_level($priv)
	{
		global $levels;
		return $levels[$priv];
	}

/**
 * Password changing form.
 */

	function new_pass_form()
	{
		pagetop(gTxt('tab_site_admin'), '');

		echo form(
			n.tag(
				hed(gTxt('change_password'), 2).
				inputLabel('new_pass', fInput('password', 'new_pass', '', '', '', '', INPUT_REGULAR, '', 'new_pass'), 'new_password').
				graf(
					checkbox('mail_password', '1', true, '', 'mail_password').
					n.tag(gTxt('mail_it'), 'label', array('for' => 'mail_password'))
				, array('class' => 'edit-mail-password')).
				graf(fInput('submit', 'change_pass', gTxt('submit'), 'publish')).
				eInput('admin').
				sInput('change_pass').n
			, 'section', array('class' => 'txp-edit'))
		, '', '', 'post', '', '', 'change_password');
	}

/**
 * Email changing form.
 */

	function change_email_form()
	{
		global $txp_user;

		pagetop(gTxt('tab_site_admin'), '');

		$email = fetch('email', 'txp_users', 'name', $txp_user);

		echo form(
			n.tag(
				hed(gTxt('change_email_address'), 2).
				inputLabel('new_email', fInput('text', 'new_email', $email, '', '', '', INPUT_REGULAR, '', 'new_email'), 'new_email').
				graf(fInput('submit', 'change_email', gTxt('submit'), 'publish')).
				eInput('admin').
				sInput('change_email').
				n, 'section', array('class' => 'txp-edit')
			)
		, '', '', 'post', '', '', 'change_email');
	}

/**
 * The main author list.
 *
 * @param string|array $message The activity message
 */

	function author_list($message = '')
	{
		global $txp_user, $author_list_pageby, $levels;

		pagetop(gTxt('tab_site_admin'), $message);

		if (is_disabled('mail'))
		{
			echo graf(gTxt('warn_mail_unavailable'), array('class' => 'alert-block warning'));
		}

		echo hed(gTxt('tab_site_admin'), 1, array('class' => 'txp-heading'));
		echo n.'<div id="users_control" class="txp-control-panel">';

		$buttons = array();

		// Change password button.
		$buttons[] = sLink('admin', 'new_pass_form', gTxt('change_password'));

		if (!has_privs('admin.edit'))
		{
			// Change email address button.
			$buttons[] = sLink('admin', 'change_email_form', gTxt('change_email_address'));
		}
		else
		{
			// New author button.
			$buttons[] = sLink('admin', 'author_edit', gTxt('add_new_author'));
		}

		echo graf(join(n, $buttons), array('class' => 'txp-buttons'));

		// User list.
		if (has_privs('admin.list'))
		{
			extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

			if ($sort === '')
			{
				$sort = get_pref('admin_sort_column', 'name');
			}

			if ($dir === '')
			{
				$dir = get_pref('admin_sort_dir', 'asc');
			}

			$dir = ($dir == 'desc') ? 'desc' : 'asc';

			if (!in_array($sort, array('name', 'RealName', 'email', 'privs', 'last_login')))
			{
				$sort = 'name';
			}

			$sort_sql = $sort.' '.$dir;

			set_pref('admin_sort_column', $sort, 'admin', 2, '', 0, PREF_PRIVATE);
			set_pref('admin_sort_dir', $dir, 'admin', 2, '', 0, PREF_PRIVATE);

			$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

			$criteria = 1;

			if ($search_method and $crit != '')
			{
				$verbatim = preg_match('/^"(.*)"$/', $crit, $m);

				$priv_search = array();

				foreach ($levels as $level => $level_name)
				{
					$priv_search[$level] = array($level, "$level", $level_name);
				}

				$crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
				$critsql = $verbatim ?
					array(
						'id'        => "user_id in ('" .join("','", do_list($crit_escaped)). "')",
						'login'     => "name = '$crit_escaped'",
						'real_name' => "RealName = '$crit_escaped'",
						'email'     => "email = '$crit_escaped'",
						'privs'     => "convert(privs, char) in ('" . multiCompare($priv_search, do_list($crit_escaped), 0x0, "','"). "')",
					) : array(
						'id'        => "user_id in ('" .join("','", do_list($crit_escaped)). "')",
						'login'     => "name like '%$crit_escaped%'",
						'real_name' => "RealName like '%$crit_escaped%'",
						'email'     => "email like '%$crit_escaped%'",
						'privs'     => "convert(privs, char) in ('" . multiCompare($priv_search, do_list($crit_escaped), COMPARE_SUBSTRING, "','"). "')",
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

			$criteria .= callback_event('admin_criteria', 'author_list', 0, $criteria);

			$total = getCount('txp_users', $criteria);

			if ($total < 1)
			{
				if ($criteria != 1)
				{
					echo n.author_search_form($crit, $search_method).
						graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
				}

				return;
			}

			$limit = max($author_list_pageby, 15);

			list($page, $offset, $numPages) = pager($total, $limit, $page);

			$use_multi_edit = ( has_privs('admin.edit') && (safe_count('txp_users', '1=1') > 1) );

			echo author_search_form($crit, $search_method).'</div>';

			$rs = safe_rows_start(
				'*, unix_timestamp(last_access) as last_login',
				'txp_users',
				"$criteria order by $sort_sql limit $offset, $limit"
			);

			if ($rs)
			{
				echo n.'<div id="users_container" class="txp-container">';
				echo n.'<form action="index.php" id="users_form" class="multi_edit_form" method="post" name="longform">'.

				n.'<div class="txp-listtables">'.
				startTable('', '', 'txp-list').
				n.'<thead>'.
				tr(
					(($use_multi_edit)
						? hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' scope="col" title="'.gTxt('toggle_all_selected').'" class="multi-edit"')
						: hCell('', '', ' scope="col" class="multi-edit"')
					).
					column_head('login_name', 'name', 'admin', true, $switch_dir, '', '', (('name' == $sort) ? "$dir " : '').'name login-name').
					column_head('real_name', 'RealName', 'admin', true, $switch_dir, '', '', (('RealName' == $sort) ? "$dir " : '').'name real-name').
					column_head('email', 'email', 'admin', true, $switch_dir, '', '', (('email' == $sort) ? "$dir " : '').'email').
					column_head('privileges', 'privs', 'admin', true, $switch_dir, '', '', (('privs' == $sort) ? "$dir " : '').'privs').
					column_head('last_login', 'last_login', 'admin', true, $switch_dir, '', '', (('last_login' == $sort) ? "$dir " : '').'date last-login modified')
				).
				n.'</thead>';

				echo n.'<tbody>';

				while ($a = nextRow($rs))
				{
					extract(doSpecial($a));

					echo tr(
						td(((has_privs('admin.edit') and $txp_user != $a['name']) ? fInput('checkbox', 'selected[]', $a['name'], 'checkbox') : ''), '', 'multi-edit').
						hCell(((has_privs('admin.edit')) ? eLink('admin', 'author_edit', 'user_id', $user_id, $name) : $name), '', ' scope="row" class="name login-name"').
						td($RealName, '', 'name real-name').
						td(href($email, 'mailto:'.$email), '', 'email').
						td(get_priv_level($privs), '', 'privs').
						td(($last_login ? safe_strftime('%b&#160;%Y', $last_login) : ''), '', 'date last-login modified')
					);
				}

				echo n.'</tbody>'.
					endTable().
					n.'</div>'.
					(($use_multi_edit) ? author_multiedit_form($page, $sort, $dir, $crit, $search_method) : '').
					tInput().
					n.'</form>'.
					tag(
						pageby_form('admin', $author_list_pageby).
						nav_form('admin', $page, $numPages, $sort, $dir, $crit, $search_method).n
					, 'div', array(
						'id'    => 'users_navigation',
						'class' => 'txp-navigation'
					)).
					n.'</div>';
			}
		}
		else
		{
			echo n.'</div>';
		}
	}

/**
 * Renders a user search form.
 *
 * @param string $crit   Current search criteria
 * @param string $method Selected search method
 */

	function author_search_form($crit, $method)
	{
		$methods = array(
			'id'        => gTxt('ID'),
			'login'     => gTxt('login_name'),
			'real_name' => gTxt('real_name'),
			'email'     => gTxt('email'),
			'privs'     => gTxt('privileges'),
		);

		return search_form('admin', 'author_list', $crit, $methods, $method, 'login');
	}

/**
 * User editor panel.
 *
 * Accessing requires 'admin.edit' privileges.
 */

	function author_edit()
	{
		global $step, $txp_user;

		require_privs('admin.edit');

		pagetop(gTxt('tab_site_admin'), '');

		$vars = array('user_id', 'name', 'RealName', 'email', 'privs');
		$rs = array();
		$out = array();

		extract(gpsa($vars));

		$is_edit = ($user_id && $step == 'author_edit');

		if ($is_edit)
		{
			$user_id = assert_int($user_id);
			$rs = safe_row('*', 'txp_users', "user_id = $user_id");
			extract($rs);
		}

		if ($is_edit)
		{
			$out[] = hed(gTxt('edit_author'), 2);
		}
		else
		{
			$out[] = hed(gTxt('add_new_author'), 2);
		}

		if ($is_edit)
		{
			$out[] = inputLabel('login_name', strong(txpspecialchars($name)));
		}
		else
		{
			$out[] =  inputLabel('login_name', fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'login_name'), 'login_name', 'add_new_author');
		}

		$out[] = inputLabel('real_name', fInput('text', 'RealName', $RealName, '', '', '', INPUT_REGULAR, '', 'real_name'), 'real_name').
			inputLabel('login_email', fInput('email', 'email', $email, '', '', '', INPUT_REGULAR, '', 'login_email'), 'email');

		if ($txp_user != $name)
		{
			$out[] = inputLabel('privileges', privs($privs), 'privileges', 'about_privileges');
		}
		else
		{
			$out[] = inputLabel('privileges', strong(get_priv_level($privs))).
				hInput('privs', $privs);
		}

		$out[] = pluggable_ui('author_ui', 'extend_detail_form', '', $rs).
			graf(fInput('submit', '', gTxt('save'), 'publish')).
			eInput('admin');

		if ($user_id)
		{
			$out[] = hInput('user_id', $user_id).
				hInput('name', $name).
				sInput('author_save');
		}
		else
		{
			$out[] = sInput('author_save_new');
		}

		echo form(
			n.tag(join('', $out).n, 'section', array('class' => 'txp-edit'))
		, '', '', 'post', 'edit-form', '', 'user_edit');
	}

/**
 * Updates pageby value.
 */

	function admin_change_pageby()
	{
		event_change_pageby('author');
		author_list();
	}

/**
 * Renders multi-edit form.
 *
 * @param  int    $page          The page
 * @param  string $sort          The sorting value
 * @param  string $dir           The sorting direction
 * @param  string $crit          The search string
 * @param  string $search_method The search method
 * @return string HTML
 */

	function author_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$privileges = privs();
		$users = safe_column('name', 'txp_users', '1=1');

		$methods = array(
			'changeprivilege' => array('label' => gTxt('changeprivilege'), 'html' => $privileges),
			'resetpassword'   => gTxt('resetpassword'),
		);

		if (count($users) > 1)
		{
			$methods['delete'] = array(
				'label' => gTxt('delete'),
				'html'  => tag(gTxt('assign_assets_to'), 'label', array('for' => 'assign_assets')).
					selectInput('assign_assets', $users, '', true, '', 'assign_assets'),
			);
		}

		return multi_edit($methods, 'admin', 'admin_multi_edit', $page, $sort, $dir, $crit, $search_method);
	}

/**
 * Processes multi-edit actions.
 *
 * Accessing requires 'admin.edit' privileges.
 */

	function admin_multi_edit()
	{
		global $txp_user;

		require_privs('admin.edit');

		$selected = ps('selected');
		$method = ps('edit_method');
		$changed = array();
		$msg = '';

		if (!$selected or !is_array($selected))
		{
			return author_list();
		}

		$names = safe_column(
			'name',
			'txp_users',
			"name IN (".join(',', quote_list($selected)).") AND name != '".doSlash($txp_user)."'"
		);

		if (!$names)
		{
			return author_list();
		}

		switch ($method)
		{
			case 'delete' :

				$assign_assets = ps('assign_assets');

				if (!$assign_assets)
				{
					$msg = array('must_reassign_assets', E_ERROR);
				}
				else if (in_array($assign_assets, $names))
				{
					$msg = array('cannot_assign_assets_to_deletee', E_ERROR);
				}
				else if (remove_user($names, $assign_assets))
				{
					$changed = $names;
					callback_event('authors_deleted', '', 0, $changed);
					$msg = 'author_deleted';
				}

				break;

			case 'changeprivilege' :

				if (change_user_group($names, ps('privs')))
				{
					$changed = $names;
					$msg = 'author_updated';
				}

				break;

			case 'resetpassword' :

				foreach ($names as $name)
				{
					$passwd = generate_password(PASSWORD_LENGTH);

					if (change_user_password($name, $passwd))
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

/**
 * Legacy panel.
 *
 * @param      string|array $message
 * @deprecated in 4.2.0
 */

	function admin($message = '')
	{
		author_list($message);
	}

<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2016 The Textpattern Development Team
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

use Textpattern\Search\Filter;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

$levels = get_groups();

if ($event == 'admin') {
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

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
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

    if (!is_valid_email($new_email)) {
        change_email_form(array(gTxt('email_required'), E_ERROR));

        return;
    }

    $rs = update_user($txp_user, $new_email);

    if ($rs) {
        author_list(gTxt('email_changed', array('{email}' => $new_email)));

        return;
    }

    change_email_form(array(gTxt('author_save_failed'), E_ERROR));
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

    if (!is_valid_email($email)) {
        author_edit(array(gTxt('email_required'), E_ERROR));

        return;
    }

    $rs = update_user($name, $email, $RealName);

    if ($rs && ($txp_user === $name || change_user_group($name, $privs))) {
        author_list(gTxt('author_updated', array('{name}' => $RealName)));

        return;
    }

    author_edit(array(gTxt('author_save_failed'), E_ERROR));
}

/**
 * Changes current user's password.
 */

function change_pass()
{
    global $txp_user;

    extract(psa(array('current_pass', 'new_pass')));

    if (empty($new_pass)) {
        new_pass_form(array(gTxt('password_required'), E_ERROR));

        return;
    }

    if (txp_validate($txp_user, $current_pass)) {
        $rs = change_user_password($txp_user, $new_pass);

        if ($rs) {
            $message = gTxt('password_changed');
            author_list($message);
        }
    } else {
        new_pass_form(array(gTxt('password_invalid'), E_ERROR));
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

    if (is_valid_username($name) && is_valid_email($email)) {
        if (user_exists($name)) {
            author_edit(array(gTxt('author_already_exists', array('{name}' => $name)), E_ERROR));

            return;
        }

        $password = Txp::get('\Textpattern\Password\Random')->generate(PASSWORD_LENGTH);

        $rs = create_user($name, $email, $password, $RealName, $privs);

        if ($rs) {
            $message = send_account_activation($name);

            author_list($message);

            return;
        }
    }

    author_edit(array(gTxt('error_adding_new_author'), E_ERROR));
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
 *
 * @param string|array $message The activity message
 */

function new_pass_form($message = '')
{
    pagetop(gTxt('tab_site_admin'), $message);

    echo form(
        hed(gTxt('change_password'), 2).
        inputLabel(
            'current_pass',
            fInput('password', 'current_pass', '', '', '', '', INPUT_REGULAR, '', 'current_pass'),
            'current_password', '', array('class' => 'txp-form-field edit-admin-current-password')
        ).
        inputLabel(
            'new_pass',
            fInput('password', 'new_pass', '', 'txp-maskable txp-strength-hint', '', '', INPUT_REGULAR, '', 'new_pass').
            n.tag(null, 'div', array('class' => 'strength-meter')).
            n.tag(
                checkbox('unmask', 1, false, 0, 'show_password').
                n.tag(gTxt('show_password'), 'label', array('for' => 'show_password')),
                'div', array('class' => 'edit-admin-show-password')),
            'new_password', '', array('class' => 'txp-form-field edit-admin-new-password')
        ).
        graf(
            sLink('admin', '', gTxt('cancel'), 'txp-button').
            fInput('submit', 'change_pass', gTxt('submit'), 'publish'),
            array('class' => 'txp-edit-actions')
        ).
        eInput('admin').
        sInput('change_pass'),
    '', '', 'post', 'txp-edit', '', 'change_password');
}

/**
 * Email changing form.
 *
 * @param string|array $message The activity message
 */

function change_email_form($message = '')
{
    global $txp_user;

    pagetop(gTxt('tab_site_admin'), $message);

    $email = fetch('email', 'txp_users', 'name', $txp_user);

    echo form(
        hed(gTxt('change_email_address'), 2).
        inputLabel(
            'new_email',
            fInput('text', 'new_email', $email, '', '', '', INPUT_REGULAR, '', 'new_email'),
            'new_email', '', array('class' => 'txp-form-field edit-admin-new-email')
        ).
        graf(
            sLink('admin', '', gTxt('cancel'), 'txp-button').
            fInput('submit', 'change_email', gTxt('submit'), 'publish'),
            array('class' => 'txp-edit-actions')
        ).
        eInput('admin').
        sInput('change_email'),
    '', '', 'post', 'txp-edit', '', 'change_email');
}

/**
 * The main panel listing all authors.
 *
 * @param string|array $message The activity message
 */

function author_list($message = '')
{
    global $event, $txp_user, $author_list_pageby, $levels;

    pagetop(gTxt('tab_site_admin'), $message);

    if (is_disabled('mail')) {
        echo graf(
            span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
            gTxt('warn_mail_unavailable'),
            array('class' => 'alert-block warning')
        );
    }

    $buttons = array();

    // Change password button.
    $buttons[] = sLink('admin', 'new_pass_form', gTxt('change_password'), 'txp-button');

    if (!has_privs('admin.edit')) {
        // Change email address button.
        $buttons[] = sLink('admin', 'change_email_form', gTxt('change_email_address'), 'txp-button');
    } else {
        // New author button.
        $buttons[] = sLink('admin', 'author_edit', gTxt('add_new_author'), 'txp-button');
    }

    // User list.
    if (has_privs('admin.list')) {
        extract(gpsa(array(
            'page',
            'sort',
            'dir',
            'crit',
            'search_method',
        )));

        if ($sort === '') {
            $sort = get_pref('admin_sort_column', 'name');
        } else {
            if (!in_array($sort, array('name', 'RealName', 'email', 'privs', 'last_login'))) {
                $sort = 'name';
            }

            set_pref('admin_sort_column', $sort, 'admin', 2, '', 0, PREF_PRIVATE);
        }

        if ($dir === '') {
            $dir = get_pref('admin_sort_dir', 'asc');
        } else {
            $dir = ($dir == 'desc') ? "desc" : "asc";
            set_pref('admin_sort_dir', $dir, 'admin', 2, '', 0, PREF_PRIVATE);
        }

        $sort_sql = $sort.' '.$dir;

        $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

        $search = new Filter($event,
            array(
                'login' => array(
                    'column' => 'txp_users.name',
                    'label'  => gTxt('login_name'),
                ),
                'RealName' => array(
                    'column' => 'txp_users.RealName',
                    'label'  => gTxt('real_name'),
                ),
                'email' => array(
                    'column' => 'txp_users.email',
                    'label'  => gTxt('email'),
                ),
                'privs' => array(
                    'column' => array('txp_users.privs'),
                    'label'  => gTxt('privileges'),
                    'type'   => 'boolean',
                ),
            )
        );

        $search->setAliases('privs', $levels);

        list($criteria, $crit, $search_method) = $search->getFilter();

        $search_render_options = array(
            'placeholder' => 'search_users',
        );

        $total = getCount('txp_users', $criteria);

        echo n.'<div class="txp-layout">'.
            n.tag(
                hed(gTxt('tab_site_admin'), 1, array('class' => 'txp-heading')),
                'div', array('class' => 'txp-layout-4col-alt')
            );

        $searchBlock =
            n.tag(
                $search->renderForm('author_list', $search_render_options),
                'div', array(
                    'class' => 'txp-layout-4col-3span',
                    'id'    => 'users_control',
                )
            );

        $createBlock = array();

        $createBlock[] = n.tag(implode(n, $buttons), 'div', array('class' => 'txp-control-panel'));

        $contentBlockStart = n.tag_start('div', array(
                'class' => 'txp-layout-1col',
                'id'    => 'users_container',
            ));

        $createBlock = implode(n, $createBlock);

        if ($total < 1) {
            if ($criteria != 1) {
                echo $searchBlock.
                    $contentBlockStart.
                    $createBlock.
                    graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_results_found'),
                        array('class' => 'alert-block information')
                    ).
                    n.tag_end('div'). // End of .txp-layout-1col.
                    n.'</div>'; // End of .txp-layout.
            }

            return;
        }

        $limit = max($author_list_pageby, 15);

        list($page, $offset, $numPages) = pager($total, $limit, $page);

        $use_multi_edit = (has_privs('admin.edit') && ($total > 1 or safe_count('txp_users', "1 = 1") > 1));

        echo $searchBlock.$contentBlockStart.$createBlock;

        $rs = safe_rows_start(
            "*, UNIX_TIMESTAMP(last_access) AS last_login",
            'txp_users',
            "$criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        if ($rs) {
            echo
                n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'users_form',
                    'name'   => 'longform',
                    'method' => 'post',
                    'action' => 'index.php',
                )).
                n.tag_start('div', array('class' => 'txp-listtables')).
                n.tag_start('table', array('class' => 'txp-list')).
                n.tag_start('thead').
                tr(
                    (
                        ($use_multi_edit)
                        ? hCell(
                            fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                                '', ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                        )
                        : hCell('', '', ' class="txp-list-col-multi-edit" scope="col"')
                    ).
                    column_head(
                        'login_name', 'name', 'admin', true, $switch_dir, '', '',
                            (('name' == $sort) ? "$dir " : '').'txp-list-col-login-name name'
                    ).
                    column_head(
                        'real_name', 'RealName', 'admin', true, $switch_dir, '', '',
                            (('RealName' == $sort) ? "$dir " : '').'txp-list-col-real-name name'
                    ).
                    column_head(
                        'email', 'email', 'admin', true, $switch_dir, '', '',
                            (('email' == $sort) ? "$dir " : '').'txp-list-col-email'
                    ).
                    column_head(
                        'privileges', 'privs', 'admin', true, $switch_dir, '', '',
                            (('privs' == $sort) ? "$dir " : '').'txp-list-col-privs'
                    ).
                    column_head(
                        'last_login', 'last_login', 'admin', true, $switch_dir, '', '',
                            (('last_login' == $sort) ? "$dir " : '').'txp-list-col-last-login date'
                    )
                ).
                n.tag_end('thead').
                n.tag_start('tbody');

            while ($a = nextRow($rs)) {
                extract(doSpecial($a));

                echo tr(
                    td(
                        ((has_privs('admin.edit') and $txp_user != $a['name']) ? fInput('checkbox', 'selected[]', $a['name'], 'checkbox') : ''), '', 'txp-list-col-multi-edit'
                    ).
                    hCell(
                        ((has_privs('admin.edit')) ? eLink('admin', 'author_edit', 'user_id', $user_id, $name) : $name), '', ' class="txp-list-col-login-name name" scope="row"'
                    ).
                    td(
                        $RealName, '', 'txp-list-col-real-name name'
                    ).
                    td(
                        href($email, 'mailto:'.$email), '', 'txp-list-col-email'
                    ).
                    td(
                        get_priv_level($privs), '', 'txp-list-col-privs'
                    ).
                    td(
                        ($last_login ? safe_strftime('%b&#160;%Y', $last_login) : ''), '', 'txp-list-col-last-login date'
                    )
                );
            }

            echo
                n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div'). // End of .txp-listtables.
                (
                    ($use_multi_edit)
                    ? author_multiedit_form($page, $sort, $dir, $crit, $search_method)
                    : ''
                ).
                tInput().
                n.tag_end('form').
                n.tag_start('div', array(
                    'class' => 'txp-navigation',
                    'id'    => 'users_navigation',
                )).
                pageby_form('admin', $author_list_pageby).
                nav_form('admin', $page, $numPages, $sort, $dir, $crit, $search_method).
                n.tag_end('div');
        }

        echo n.tag_end('div'); // End of .txp-layout-1col.
    } else {
        echo
            n.tag_start('div', array(
                'class' => 'txp-layout-1col',
                'id'    => 'users_container',
            )).
            n.tag(implode(n, $buttons), 'div', array('class' => 'txp-control-panel')).
            n.tag_end('div'); // End of .txp-layout-1col.
    }

    echo n.'</div>'; // End of .txp-layout.
}

/**
 * Renders and outputs the user editor panel.
 *
 * Accessing requires 'admin.edit' privileges.
 *
 * @param string|array $message The activity message
 */

function author_edit($message = '')
{
    global $step, $txp_user;

    require_privs('admin.edit');

    pagetop(gTxt('tab_site_admin'), $message);

    $vars = array('user_id', 'name', 'RealName', 'email', 'privs');
    $rs = array();
    $out = array();

    extract(gpsa($vars));

    $is_edit = ($user_id && $step == 'author_edit');

    if ($is_edit) {
        $user_id = assert_int($user_id);
        $rs = safe_row("*", 'txp_users', "user_id = $user_id");
        extract($rs);
    }

    if ($is_edit) {
        $out[] = hed(gTxt('edit_author'), 2);
    } else {
        $out[] = hed(gTxt('add_new_author'), 2);
    }

    if ($is_edit) {
        $out[] = inputLabel(
            'login_name',
            strong(txpspecialchars($name)),
            '', '', array('class' => 'txp-form-field edit-admin-login-name')
        );
    } else {
        $out[] = inputLabel(
            'login_name',
            fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'login_name'),
            'login_name', 'add_new_author', array('class' => 'txp-form-field edit-admin-login-name')
        );
    }

    $out[] = inputLabel(
            'real_name',
            fInput('text', 'RealName', $RealName, '', '', '', INPUT_REGULAR, '', 'real_name'),
            'real_name', '', array('class' => 'txp-form-field edit-admin-name')
        ).
        inputLabel(
            'login_email',
            fInput('email', 'email', $email, '', '', '', INPUT_REGULAR, '', 'login_email'),
            'email', '', array('class' => 'txp-form-field edit-admin-email')
        );

    if ($txp_user != $name) {
        $out[] = inputLabel(
            'privileges',
            privs($privs),
            'privileges', 'about_privileges', array('class' => 'txp-form-field edit-admin-privileges')
        );
    } else {
        $out[] = inputLabel(
            'privileges',
            strong(get_priv_level($privs)),
            '', '', array('class' => 'txp-form-field edit-admin-privileges')
        ).
        hInput('privs', $privs);
    }

    $out[] = pluggable_ui('author_ui', 'extend_detail_form', '', $rs).
        graf(
            sLink('admin', '', gTxt('cancel'), 'txp-button').
            fInput('submit', '', gTxt('save'), 'publish'),
            array('class' => 'txp-edit-actions')
        ).
        eInput('admin');

    if ($user_id) {
        $out[] = hInput('user_id', $user_id).
            hInput('name', $name).
            sInput('author_save');
    } else {
        $out[] = sInput('author_save_new');
    }

    echo form(join('', $out), '', '', 'post', 'txp-edit', '', 'user_edit');
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
    $users = safe_column("name", 'txp_users', "1 = 1");

    $methods = array(
        'changeprivilege'  => array('label' => gTxt('changeprivilege'), 'html' => $privileges),
        'resetpassword'    => gTxt('resetpassword'),
        'resendactivation' => gTxt('resend_activation'),
    );

    if (count($users) > 1) {
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

    if (!$selected or !is_array($selected)) {
        return author_list();
    }

    $clause = '';

    if ($method === 'resetpassword') {
        $clause = " AND last_access IS NOT NULL";
    } elseif ($method === 'resendactivation') {
        $clause = " AND last_access IS NULL";
    }

    $names = safe_column(
        "name",
        'txp_users',
        "name IN (".join(',', quote_list($selected)).") AND name != '".doSlash($txp_user)."'".$clause
    );

    if (!$names) {
        return author_list();
    }

    switch ($method) {
        case 'delete':

            $assign_assets = ps('assign_assets');

            if (!$assign_assets) {
                $msg = array('must_reassign_assets', E_ERROR);
            } elseif (in_array($assign_assets, $names)) {
                $msg = array('cannot_assign_assets_to_deletee', E_ERROR);
            } elseif (remove_user($names, $assign_assets)) {
                $changed = $names;
                callback_event('authors_deleted', '', 0, $changed);
                $msg = 'author_deleted';
            }

            break;

        case 'changeprivilege':

            if (change_user_group($names, ps('privs'))) {
                $changed = $names;
                $msg = 'author_updated';
            }

            break;

        case 'resetpassword':

            foreach ($names as $name) {
                send_reset_confirmation_request($name);
                $changed[] = $name;
            }

            $msg = 'password_reset_confirmation_request_sent';
            break;

        case 'resendactivation':

            foreach ($names as $name) {
                send_account_activation($name);
                $changed[] = $name;
            }

            $msg = 'resend_activation_request_sent';
            break;
    }

    if ($changed) {
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

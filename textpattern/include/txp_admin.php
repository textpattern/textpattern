<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
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

    $available_steps = array(
        'admin_multi_edit'    => true,
        'admin_change_pageby' => true,
        'author_list'         => false,
        'author_edit'         => false,
        'author_save'         => true,
        'author_save_new'     => true,
        'change_pass'         => true,
        'new_pass_form'       => false,
    );

    $plugin_steps = array();
    callback_event_ref('user', 'steps', 0, $plugin_steps);

    // Available steps overwrite custom ones to prevent plugins trampling
    // core routines.
    if ($step && bouncer($step, array_merge($plugin_steps, $available_steps))) {
        if (array_key_exists($step, $available_steps)) {
            $step();
        } else {
            callback_event($event, $step, 0);
        }
    } else {
        author_list();
    }
}

/**
 * Updates a user.
 */

function author_save()
{
    global $txp_user;

    require_privs('admin.edit.own');

    extract(psa(array(
        'privs',
        'name',
        'RealName',
        'email',
        'language',
    )));

    $privs = assert_int($privs);

    if (!is_valid_email($email)) {
        $fullEdit = has_privs('admin.list') ? false : true;
        author_edit(array(gTxt('email_required'), E_ERROR), $fullEdit);

        return;
    }

    $rs = update_user($name, $email, $RealName);

    if ($rs && $language) {
        safe_upsert(
            'txp_prefs',
            "val = '".doSlash($language)."',
            event = 'admin',
            html = 'text_input',
            type = ".PREF_HIDDEN.",
            position = 0",
            array(
                'name'      => 'language_ui',
                'user_name' => doSlash((string) $name)
            )
        );
    }

    if (has_privs('admin.edit') && $rs && ($txp_user === $name || change_user_group($name, $privs))) {
        author_list(gTxt('author_updated', array('{name}' => $RealName)));

        return;
    } elseif ($rs && has_privs('admin.edit.own')) {
        $msg = gTxt('author_updated', array('{name}' => $RealName));
    } else {
        $msg = array(gTxt('author_save_failed'), E_ERROR);
    }

    if (has_privs('admin.edit')) {
        author_edit($msg);
    } elseif (has_privs('admin.edit.own')) {
        author_list($msg);
    }
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
        'language',
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
            if ($language) {
                safe_upsert(
                    'txp_prefs',
                    "val = '".doSlash($language)."',
                    event = 'admin',
                    html = 'text_input',
                    type = ".PREF_HIDDEN.",
                    position = 0",
                    array(
                        'name'      => 'language_ui',
                        'user_name' => doSlash((string) $name)
                    )
                );
            }

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
            fInput('password',
                array(
                    'name'         => 'current_pass',
                    'autocomplete' => 'current-password',
                ), '', 'txp-maskable', '', '', INPUT_REGULAR, '', 'current_pass', false, true),
            'current_password', '', array('class' => 'txp-form-field edit-admin-current-password')
        ).
        inputLabel(
            'new_pass',
            fInput('password',
                array(
                    'name'         => 'new_pass',
                    'autocomplete' => 'new-password',
                ), '', 'txp-maskable', '', '', INPUT_REGULAR, '', 'new_pass', false, true).
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
 * The main panel listing all authors.
 *
 * @param string|array $message The activity message
 */

function author_list($message = '')
{
    global $event, $txp_user, $levels;

    $buttons = author_edit_buttons();

    $fields = array(
        'user_id' => array(
            'sortable' => false,
            'visible'  => false,
        ),
        'name' => array(
            'label' => 'login_name',
            'class' => 'name',
        ),
        'RealName' => array(
            'label' => 'real_name',
            'class' => 'name',
        ),
        'email' => array(
        ),
        'privs' => array(
            'label' => 'privileges',
        ),
        'last_login' => array(
            'column' => 'UNIX_TIMESTAMP(last_access)',
            'class'  => 'date',
        ),
    );

    $sql_from = safe_pfx_j('txp_users');

    callback_event_ref('user', 'fields', 'list', $fields);
    callback_event_ref('user', 'from', 'list', $sql_from);

    $fieldlist = array();

    // Build field list: shame that array_filter() can't get keys and
    // values 'til PHP 5.6. @todo One day.
    foreach ($fields as $fld => $def) {
        $fieldlist[] = isset($def['column']) ? $def['column'].' AS '. $fld : $fld;
    }

    // User list.
    if (has_privs('admin.list')) {
        pagetop(gTxt('tab_site_admin'), $message);

        if (is_disabled('mail')) {
            echo graf(
                span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                gTxt('warn_mail_unavailable'),
                array('class' => 'alert-block warning')
            );
        }
        extract(gpsa(array(
            'page',
            'sort',
            'dir',
            'crit',
            'search_method',
        )));

        if ($sort === '') {
            $sort = get_pref('admin_sort_column', 'name');
        }

        if (!in_array($sort, array_keys(array_filter($fields, function($value) {
                return !isset($value['sortable']) || !empty($value['sortable']);
            })))) {
            $sort = 'name';
        }

        set_pref('admin_sort_column', $sort, 'admin', PREF_HIDDEN, '', 0, PREF_PRIVATE);

        if ($dir === '') {
            $dir = get_pref('admin_sort_dir', 'asc');
        } else {
            $dir = ($dir == 'desc') ? "desc" : "asc";
            set_pref('admin_sort_dir', $dir, 'admin', PREF_HIDDEN, '', 0, PREF_PRIVATE);
        }

        $sort_sql = $sort.' '.$dir.($sort == 'name' ? '' : ", name $dir");

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

        list($criteria, $crit, $search_method) = $search->getFilter(array('login' => array('can_list' => true)));

        $search_render_options = array('placeholder' => 'search_users');

        $total = (int)getThing("SELECT COUNT(*) FROM $sql_from WHERE $criteria");

        $searchBlock =
            n.tag(
                $search->renderForm('author_list', $search_render_options),
                'div', array(
                    'class' => 'txp-layout-4col-3span',
                    'id'    => 'users_control',
                )
            );

        $createBlock = n.tag(implode(n, $buttons), 'div', array('class' => 'txp-control-panel'));

        $contentBlock = '';

        $paginator = new \Textpattern\Admin\Paginator($event, 'author');
        $limit = $paginator->getLimit();

        list($page, $offset, $numPages) = pager($total, $limit, $page);

        if ($total < 1) {
            if ($crit !== '') {
                $contentBlock .=
                    graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_results_found'),
                        array('class' => 'alert-block information')
                    );
            }
        } else {
            $use_multi_edit = (has_privs('admin.edit') && ($total > 1 or safe_count('txp_users', "1 = 1") > 1));

            $rs = safe_query("SELECT ".implode(', ', $fieldlist).
                " FROM $sql_from".
                " WHERE $criteria ORDER BY $sort_sql LIMIT $offset, $limit"
            );

            if ($rs) {
                $contentBlock .=
                    n.tag_start('form', array(
                        'class'  => 'multi_edit_form',
                        'id'     => 'users_form',
                        'name'   => 'longform',
                        'method' => 'post',
                        'action' => 'index.php',
                    )).
                    n.tag_start('div', array(
                        'class'      => 'txp-listtables',
                        'tabindex'   => 0,
                        'aria-label' => gTxt('list'),
                    )).
                    n.tag_start('table', array('class' => 'txp-list')).
                    n.tag_start('thead');

                    $headings = array();
                    $headings[] = ($use_multi_edit)
                        ? hCell(
                            fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                                '', ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                        )
                        : hCell('', '', ' class="txp-list-col-multi-edit" scope="col"');

                    foreach ($fields as $col => $opts) {
                        if (isset($opts['visible']) && empty($opts['visible'])) {
                            continue;
                        }

                        $lbl = empty($opts['label']) ? $col : $opts['label'];
                        $cls = empty($opts['class']) ? $col : $opts['class'];
                        $headings[] = column_head(
                            $lbl,
                            $col,
                            'admin',
                            true,
                            $switch_dir,
                            '',
                            '',
                            (($col == $sort) ? "$dir " : '').
                                'txp-list-col-'.strtolower(str_replace('_', '-', $lbl)).' '.$cls
                        );

                    }

                    $contentBlock .= tr(
                        implode(n, $headings)
                    ).
                    n.tag_end('thead').
                    n.tag_start('tbody');

                foreach ($rs as $a) {
                    extract(doSpecial($a));

                    $contentBlock .= tr(
                        td(
                            ((has_privs('admin.edit') && $txp_user != $a['name']) ? fInput('checkbox', 'selected[]', $a['name'], 'checkbox') : ''), '', 'txp-list-col-multi-edit'
                        ).
                        hCell(
                            ((has_privs('admin.edit') || (has_privs('admin.edit.own') && $txp_user === $a['name'])) ? eLink('admin', 'author_edit', 'user_id', $user_id, $name, '', '', gTxt('edit')) : $name), '', ' class="txp-list-col-login-name name" scope="row"'
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
                        ).
                        pluggable_ui('user_ui', 'list.row', '', $a)
                    );
                }

                $contentBlock .=
                    n.tag_end('tbody').
                    n.tag_end('table').
                    n.tag_end('div'). // End of .txp-listtables.
                    (
                        ($use_multi_edit)
                        ? author_multiedit_form($page, $sort, $dir, $crit, $search_method)
                        : ''
                    ).
                    tInput().
                    n.tag_end('form');
            }
        }

        $pageBlock = $paginator->render().
        nav_form('admin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

        $table = new \Textpattern\Admin\Table('users');
        echo $table->render(compact('total', 'crit') + array('heading' => 'tab_site_admin'), $searchBlock, $createBlock, $contentBlock, $pageBlock);

    } elseif (has_privs('admin.edit.own')) {
        echo author_edit($message, true);
    } else {
        require_privs('admin.edit');
    }
}

/**
 * Create additional UI buttons.
 */
function author_edit_buttons()
{
    $buttons = array();

    // New author button.
    if (has_privs('admin.edit')) {
        $buttons[] = sLink('admin', 'author_edit', gTxt('create_author'), 'txp-button');
    }

    // Change password button.
    $buttons[] = sLink('admin', 'new_pass_form', gTxt('change_password'), 'txp-button');

    callback_event_ref('user', 'controls', 'panel', $buttons);

    return $buttons;
}

/**
 * Renders the user edit panel.
 *
 * @param string|array $message  The activity message
 * @param bool         $fullEdit Whether the user has full edit permissions or not
 */

function author_edit($message = '', $fullEdit = false)
{
    global $step, $txp_user;

    require_privs('admin.edit.own');

    pagetop(gTxt('tab_site_admin'), $message);

    $vars = array('user_id', 'name', 'RealName', 'email', 'privs');
    $rs = array();
    $out = array();

    extract(gpsa($vars));

    if (has_privs('admin.edit')) {
        if ($user_id) {
            $user_id = assert_int($user_id);
            $rs = safe_row("*", 'txp_users', "user_id = '$user_id'");

            extract($rs);
            $is_edit = true;
        } else {
            $is_edit = false;
        }
    } else {
        $rs = safe_row("*", 'txp_users', "name = '".doSlash($txp_user)."'");
        extract($rs);
        $is_edit = true;
    }

    if (!$is_edit) {
        $out[] = hed(gTxt('create_author'), 2);
    } else {
        $out[] = hed(gTxt('edit_author'), 2);
    }

    if ($is_edit) {
        $out[] = inputLabel(
            'login_name',
            strong(txpspecialchars($name)),
            '', '', array('class' => 'txp-form-field edit-admin-login-name')
        );
    } elseif (has_privs('admin.edit')) {
        $out[] = inputLabel(
            'login_name',
            fInput('text', 'name', $name, '', '', '', INPUT_REGULAR, '', 'login_name', false, true),
            'login_name', 'create_author', array('class' => 'txp-form-field edit-admin-login-name')
        );
    }

    // Get author's current admin language, if defined,
    $txpLang = Txp::get('\Textpattern\L10n\Lang');
    $langList = $txpLang->languageList();
    $authorLang = safe_field('val', 'txp_prefs', "name='language_ui' AND user_name = '".doSlash($name)."'");
    $authorLang = in_array($authorLang, $txpLang->installed()) ? $authorLang : ($is_edit? null : TEXTPATTERN_DEFAULT_LANG);

    if (count($langList) > 1) {
        $langField = inputLabel(
            'language',
            selectInput('language', $langList, $authorLang, true, false, 'language'),
            'active_language_ui', '', array('class' => 'txp-form-field edit-admin-language')
        );
    } else {
        $langField = hInput('language', $authorLang);
    }

    $out[] = inputLabel(
            'real_name',
            fInput('text', 'RealName', $RealName, '', '', '', INPUT_REGULAR, '', 'real_name'),
            'real_name', '', array('class' => 'txp-form-field edit-admin-name')
        ).
        inputLabel(
            'login_email',
            fInput('email', 'email', $email, '', '', '', INPUT_REGULAR, '', 'login_email', false, true),
            'email', '', array('class' => 'txp-form-field edit-admin-email')
        );

    if (has_privs('admin.edit') && $txp_user != $name) {
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

    $out[] = $langField;
    $out[] = pluggable_ui('author_ui', 'extend_detail_form', '', $rs).
        graf(
            ($fullEdit ? '' : sLink('admin', '', gTxt('cancel'), 'txp-button')).
            fInput('submit', '', gTxt('save'), 'publish'),
            array('class' => 'txp-edit-actions')
        ).
        eInput('admin');

    if ($is_edit) {
        $out[] = hInput('user_id', $user_id).
            hInput('name', $name).
            sInput('author_save');
    } else {
        $out[] = sInput('author_save_new');
    }

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_site_account'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        ).
        n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => 'users_container',
        )).
        ($fullEdit
            ? n.tag(implode(n, author_edit_buttons()), 'div', array('class' => 'txp-control-panel'))
            : ''
        );

    if (!$is_edit) {
        echo form(join('', $out), '', '', 'post', 'txp-edit', '', 'user_edit', '', false);
    } else {
        echo form(join('', $out), '', '', 'post', 'txp-edit', '', 'user_edit');
    }

    echo n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.
}

/**
 * Updates pageby value.
 */

function admin_change_pageby()
{
    global $event;

    Txp::get('\Textpattern\Admin\Paginator', $event, 'author')->change();
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
        'changeprivilege'  => array(
            'label' => gTxt('changeprivilege'),
            'html'  => $privileges,
        ),
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

    if (!$selected || !is_array($selected)) {
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

    if (is_array($msg)) {
        list($msg, $err) = $msg;
    } else {
        $err = 0;
    }

    if ($changed) {
        return author_list(array(gTxt($msg, array('{name}' => txpspecialchars(join(', ', $changed)))), $err));
    }

    author_list(array(gTxt($msg), $err));
}

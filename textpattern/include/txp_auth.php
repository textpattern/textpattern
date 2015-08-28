<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2015 The Textpattern Development Team
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
 * Login panel.
 *
 * @package Admin\Auth
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

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

    if (!$txp_user) {
        doLoginForm($message);
    }

    ob_start();
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

    if (gps('logout')) {
        $step = 'logout';
    } elseif (gps('reset')) {
        $step = 'reset';
    }

    pagetop(gTxt('login'), $message);

    $stay  = (cs('txp_login') and !gps('logout') ? 1 : 0);
    $reset = gps('reset');

    $name = join(',', array_slice(explode(',', cs('txp_login')), 0, -1));
    $out = array();

    if ($reset) {
        $out[] = hed(gTxt('password_reset'), 2, array('id' => 'txp-login-heading')).

            graf(
                n.span(tag(gTxt('name'), 'label', array('for' => 'login_name')), array('class' => 'txp-label')).
                n.span(fInput('text', 'p_userid', $name, '', '', '', INPUT_REGULAR, '', 'login_name'), array('class' => 'txp-value')), ' class="login-name"').

            graf(
                fInput('submit', '', gTxt('password_reset_button'), 'publish').n
            ).

            graf(
                href(gTxt('back_to_login'), 'index.php'), array('class' => 'login-return')).

            hInput('p_reset', 1);
    } else {
        $out[] = hed(gTxt('login_to_textpattern'), 2, array('id' => 'txp-login-heading')).

            graf(
                n.span(tag(gTxt('name'), 'label', array('for' => 'login_name')), array('class' => 'txp-label')).
                n.span(fInput('text', 'p_userid', $name, '', '', '', INPUT_REGULAR, '', 'login_name'), array('class' => 'txp-value')), array('class' => 'login-name')).

            graf(
                n.span(tag(gTxt('password'), 'label', array('for' => 'login_password')), array('class' => 'txp-label')).
                n.span(fInput('password', 'p_password', '', '', '', '', INPUT_REGULAR, '', 'login_password'), array('class' => 'txp-value')), array('class' => 'login-password')).

            graf(
                checkbox('stay', 1, $stay, '', 'login_stay').n.
                tag(gTxt('stay_logged_in'), 'label', array('for' => 'login_stay')).
                popHelp('remember_login').n, array('class' => 'login-stay')).

            graf(
                fInput('submit', '', gTxt('log_in_button'), 'publish').n
            ).

            graf(
                href(gTxt('password_forgotten'), '?reset=1'), array('class' => 'login-forgot'));

        if (gps('event')) {
            $out[] = eInput(gps('event'));
        }
    }

    echo form(
        tag(join('', $out), 'section', array(
            'role'            => 'region',
            'class'           => 'txp-login',
            'aria-labelledby' => 'txp-login-heading',
        )), '', '', 'post', '', '', 'login_form').

    script_js('textpattern.textarray = '.json_encode($textarray_script)).
    n.'</main><!-- /txp-body -->'.n.'</body>'.n.'</html>';

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
    $pub_path   = preg_replace('|//$|', '/', rhu.'/');

    if (cs('txp_login') and strpos(cs('txp_login'), ',')) {
        $txp_login = explode(',', cs('txp_login'));
        $c_hash = end($txp_login);
        $c_userid = join(',', array_slice($txp_login, 0, -1));
    } else {
        $c_hash   = '';
        $c_userid = '';
    }

    if ($logout) {
        setcookie('txp_login', '', time() - 3600);
        setcookie('txp_login_public', '', time() - 3600, $pub_path);
    }

    if ($c_userid and strlen($c_hash) == 32) { // Cookie exists.
        $r = safe_row(
            'name, nonce',
            'txp_users',
            "name='".doSlash($c_userid)."' AND last_access > DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        if ($r && $r['nonce'] && $r['nonce'] === md5($c_userid.pack('H*', $c_hash))) {
            // Cookie is good.
            if ($logout) {
                // Destroy nonce.
                safe_update(
                    'txp_users',
                    "nonce = '".doSlash(md5(uniqid(mt_rand(), true)))."'",
                    "name = '".doSlash($c_userid)."'"
                );
            } else {
                // Create $txp_user.
                $txp_user = $r['name'];
            }

            return $message;
        } else {
            txp_status_header('401 Your session has expired');
            setcookie('txp_login', $c_userid, time() + 3600 * 24 * 365);
            setcookie('txp_login_public', '', time() - 3600, $pub_path);
            $message = array(gTxt('bad_cookie'), E_ERROR);
        }
    } elseif ($p_userid and $p_password) { // Incoming login vars.
        $name = txp_validate($p_userid, $p_password);

        if ($name !== false) {
            $c_hash = md5(uniqid(mt_rand(), true));
            $nonce  = md5($name.pack('H*', $c_hash));

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
        } else {
            sleep(3);
            txp_status_header('401 Could not log in with that username/password');
            $message = array(gTxt('could_not_log_in'), E_ERROR);
        }
    } elseif ($p_reset) { // Reset request.
        sleep(3);

        include_once txpath.'/lib/txplib_admin.php';

        $message = ($p_userid) ? send_reset_confirmation_request($p_userid) : '';
    } elseif (gps('reset')) {
        $message = '';
    } elseif (gps('confirm')) {
        sleep(3);

        $confirm = pack('H*', gps('confirm'));
        $name    = substr($confirm, 5);
        $nonce   = safe_field('nonce', 'txp_users', "name = '".doSlash($name)."'");

        if ($nonce and $confirm === pack('H*', substr(md5($nonce), 0, 10)).$name) {
            include_once txpath.'/lib/txplib_admin.php';

            $message = reset_author_pass($name);
        }
    }

    $txp_user = '';

    return $message;
}

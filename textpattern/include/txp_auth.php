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
        if (trim(ps('app_mode')) == 'async') {
            echo script_js(
                'alert("'.escape_js(gTxt('login_to_textpattern')).'"); 
                window.location.assign("index.php")'
            );
            exit();
        } else {
            doLoginForm($message);
        }
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

    $stay = (cs('txp_login') && !gps('logout') ? 1 : 0);
    $reset = gps('reset');
    $confirm = gps('confirm');
    $activate = gps('activate');

    if (gps('logout')) {
        $step = 'logout';
    } elseif ($reset) {
        $step = 'reset';
    } elseif ($activate) {
        $step = 'activate';
    } elseif ($confirm) {
        $step = 'confirm';
    }

    $name = join(',', array_slice(explode(',', cs('txp_login')), 0, -1));
    $out = array();

    if ($reset) {
        $pageTitle = gTxt('password_reset');
        $out[] = hed(gTxt('password_reset'), 1, array('id' => 'txp-login-heading')).
            inputLabel(
                'login_name',
                fInput('text', 'p_userid', $name, '', '', '', INPUT_REGULAR, '', 'login_name'),
                'name', '', array('class' => 'txp-form-field login-name')
            ).
            graf(
                fInput('submit', '', gTxt('password_reset_button'), 'publish')
            ).
            graf(
                href(gTxt('back_to_login'), 'index.php'), array('class' => 'login-return')
            ).
            hInput('p_reset', 1);
    } elseif ($confirm || $activate) {
        $pageTitle = ($confirm) ? gTxt('change_password') : gTxt('set_password');
        $label = ($confirm) ? 'change_password' : 'set_password';
        $class = ($confirm) ? 'change-password' : 'set-password';
        $out[] = hed($pageTitle, 1, array('id' => 'txp-'.$class.'-heading')).
            inputLabel(
                $label,
                fInput('password', 'p_password', '', 'txp-maskable txp-strength-hint', '', '', INPUT_REGULAR, '', $label, false, true).
                n.tag(null, 'div', array('class' => 'strength-meter')).
                n.tag(
                    checkbox('unmask', 1, false, 0, 'show_password').
                    n.tag(gTxt('show_password'), 'label', array('for' => 'show_password')),
                    'div', array('class' => 'show-password')),
                'new_password', '', array('class' => 'txp-form-field '.$class)
            ).
            graf(
                fInput('submit', '', gTxt('password_confirm_button'), 'publish')
            ).
            graf(
                href(gTxt('back_to_login'), 'index.php'), array('class' => 'login-return')
            ).
            hInput('hash', gps('confirm').gps('activate')).
            hInput(($confirm ? 'p_alter' : 'p_set'), 1);
    } else {
        $pageTitle = gTxt('login');
        $out[] = hed(gTxt('login_to_textpattern'), 1, array('id' => 'txp-login-heading')).
            inputLabel(
                'login_name',
                fInput('text', 'p_userid', $name, '', '', '', INPUT_REGULAR, '', 'login_name'),
                'name', '', array('class' => 'txp-form-field login-name')
            ).
            inputLabel(
                'login_password',
                fInput('password', 'p_password', '', '', '', '', INPUT_REGULAR, '', 'login_password'),
                'password', '', array('class' => 'txp-form-field login-password')
            ).
            graf(
                checkbox('stay', 1, $stay, '', 'login_stay').n.
                tag(gTxt('stay_logged_in'), 'label', array('for' => 'login_stay')).
                popHelp('remember_login'), array('class' => 'login-stay')
            ).
            graf(
                fInput('submit', '', gTxt('log_in_button'), 'publish')
            ).
            graf(
                href(gTxt('password_forgotten'), '?reset=1'), array('class' => 'login-forgot')
            );

        if (gps('event')) {
            $out[] = eInput(gps('event'));
        }
    }

    pagetop($pageTitle, $message);

    gTxtScript(array(
        'password_strength_0',
        'password_strength_1',
        'password_strength_2',
        'password_strength_3',
        'password_strength_4',
        )
    );

    echo form(
        join('', $out), '', '', 'post', 'txp-login', '', 'login_form').

    script_js('vendors/dropbox/zxcvbn/zxcvbn.js', TEXTPATTERN_SCRIPT_URL).
    script_js('textpattern.textarray = '.json_encode($textarray_script)).
    n.'</main><!-- /txp-body -->'.n.'</body>'.n.'</html>';

    exit(0);
}

/**
 * Validates the sent login form and creates a session.
 *
 * During the reset request procedure, it is conceivable to verify the
 * token as soon as it's presented in the URL, but that would:
 *  a) require refactoring code similarities in both p_confirm and p_alter branches
 *  b) require some way (e.g. an Exception) to signal back to doLoginForm() that
 *     the token is bogus so the 'change your password' form is not displayed.
 *  c) leak information about the validity of a token, thus allowing rapid brute-force
 *     attempts.
 *
 * The inconvenience of a real user following an expired token and being told so
 * after they've set a password is a small price to pay for the improved security
 * and reduction of attack surface that validating after submission affords.
 *
 * @todo  Could the checks be done via a (reusable) Validator()?
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
    $p_alter    = ps('p_alter');
    $p_set      = ps('p_set');
    $stay       = ps('stay');
    $p_confirm  = gps('confirm');
    $logout     = gps('logout');
    $message    = '';
    $pub_path   = preg_replace('|//$|', '/', rhu.'/');

    if (cs('txp_login') && strpos(cs('txp_login'), ',')) {
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

    if ($c_userid && strlen($c_hash) === 32) {
        // Cookie exists.
        // @todo Improve security by using a better nonce/salt mechanism. md5 and uniqid are bad.
        $r = safe_row(
            "name, nonce",
            'txp_users',
            "name = '".doSlash($c_userid)."' AND last_access > DATE_SUB(NOW(), INTERVAL 30 DAY)"
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
    } elseif ($p_userid && $p_password) {
        // Incoming login vars.
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
                ($stay ? time() + 3600 * 24 * 365 : 0),
                null,
                null,
                null,
                LOGIN_COOKIE_HTTP_ONLY
            );

            setcookie(
                'txp_login_public',
                substr(md5($nonce), -10).$name,
                ($stay ? time() + 3600 * 24 * 30 : 0),
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
    } elseif ($p_reset) {
        // Reset request.
        sleep(3);

        include_once txpath.'/lib/txplib_admin.php';

        $message = ($p_userid) ? send_reset_confirmation_request($p_userid) : '';
    } elseif ($p_alter || $p_set) {
        // Password change/set confirmation.
        sleep(3);
        global $sitename;

        $pass = ps('p_password');
        $type = ($p_alter) ? 'password_reset' : 'account_activation';

        if (trim($pass) === '') {
            $message = array(gTxt('password_required'), E_ERROR);
        } else {
            $hash = gps('hash');
            $selector = substr($hash, SALT_LENGTH);

            $tokenInfo = safe_row("reference_id, token, expires", 'txp_token', "selector = '".doSlash($selector)."' AND type='$type'");

            if ($tokenInfo) {
                if (strtotime($tokenInfo['expires']) <= time()) {
                    $message = array(gTxt('token_expired'), E_ERROR);
                } else {
                    $uid = assert_int($tokenInfo['reference_id']);
                    $row = safe_row("name, email, nonce, pass AS old_pass", 'txp_users', "user_id = $uid");

                    if ($row && $row['nonce'] && ($hash === bin2hex(pack('H*', substr(hash(HASHING_ALGORITHM, $row['nonce'].$selector.$row['old_pass']), 0, SALT_LENGTH))).$selector)) {
                        if (change_user_password($row['name'], $pass)) {
                            $body = gTxt('salutation', array('{name}' => $row['name'])).
                                n.n.($p_alter ? gTxt('password_change_confirmation') : gTxt('password_set_confirmation').n.n.gTxt('log_in_at').': '.hu.'textpattern/index.php');
                            $message = ($p_alter) ? gTxt('password_changed') : gTxt('password_set');
                            txpMail($row['email'], "[$sitename] ".$message, $body);

                            // Invalidate all tokens in the wild for this user.
                            safe_delete("txp_token", "reference_id = $uid AND type IN ('password_reset', 'account_activation')");
                        }
                    } else {
                        $message = array(gTxt('invalid_token'), E_ERROR);
                    }
                }
            } else {
                $message = array(gTxt('invalid_token'), E_ERROR);
            }
        }
    }

    $txp_user = '';

    return $message;
}

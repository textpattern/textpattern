<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
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
 * Collection of password handling functions.
 *
 * @package User
 */

/**
 * Emails a new user with account details and requests they set a password.
 *
 * @param  string $name     The login name
 * @return bool FALSE on error.
 */

function send_account_activation($name)
{
    global $sitename;

    require_privs('admin.edit');

    $rs = safe_row("user_id, email, nonce, RealName, pass", 'txp_users', "name = '".doSlash($name)."'");

    if ($rs) {
        extract($rs);

        $expiryTimestamp = time() + (60 * 60 * ACTIVATION_EXPIRY_HOURS);

        $activation_code = generate_user_token($user_id, 'account_activation', $expiryTimestamp, $pass, $nonce);

        $expiryYear = safe_strftime('%Y', $expiryTimestamp);
        $expiryMonth = safe_strftime('%B', $expiryTimestamp);
        $expiryDay = safe_strftime('%Oe', $expiryTimestamp);
        $expiryTime = safe_strftime('%H:%M %Z', $expiryTimestamp);

        $message = gTxt('salutation', array('{name}' => $RealName)).
            n.n.gTxt('you_have_been_registered').' '.$sitename.

            n.n.gTxt('your_login_is').': '.$name.
            n.n.gTxt('account_activation_confirmation').
            n.hu.'textpattern/index.php?activate='.$activation_code.
            n.n.gTxt('link_expires', array(
                '{year}'  => $expiryYear,
                '{month}' => $expiryMonth,
                '{day}'   => $expiryDay,
                '{time}'  => $expiryTime,
            ));

        if (txpMail($email, "[$sitename] ".gTxt('account_activation'), $message)) {
            return gTxt('login_sent_to', array('{email}' => $email));
        } else {
            return array(gTxt('could_not_mail'), E_ERROR);
        }
    }
}

/**
 * Sends a password reset link to a user's email address.
 *
 * This function will return a success message even when the specified user
 * doesn't exist. Though an error message could be thrown when a user isn't
 * found, security best practice prevents leaking existing account names.
 *
 * @param  string $name The login name
 * @return string A localized message string
 * @see    send_new_password()
 * @see    reset_author_pass()
 * @example
 * echo send_reset_confirmation_request('username');
 */

function send_reset_confirmation_request($name)
{
    global $sitename;

    $expiryTimestamp = time() + (60 * RESET_EXPIRY_MINUTES);

    $rs = safe_query(
        "SELECT
            txp_users.user_id, txp_users.email,
            txp_users.nonce, txp_users.pass,
            txp_token.expires
        FROM ".safe_pfx('txp_users')." txp_users
        LEFT JOIN ".safe_pfx('txp_token')." txp_token
        ON txp_users.user_id = txp_token.reference_id
        AND txp_token.type = 'password_reset'
        WHERE txp_users.name = '".doSlash($name)."'");

    $row = nextRow($rs);

    if ($row) {
        extract($row);

        // Rate limit the reset requests.
        if ($expires) {
            $originalExpiry = strtotime($expires);

            if (($expiryTimestamp - $originalExpiry) < (60 * RESET_RATE_LIMIT_MINUTES)) {
                return gTxt('password_reset_confirmation_request_sent');
            }
        }

        $confirm = generate_user_token($user_id, 'password_reset', $expiryTimestamp, $pass, $nonce);

        $expiryYear = safe_strftime('%Y', $expiryTimestamp);
        $expiryMonth = safe_strftime('%B', $expiryTimestamp);
        $expiryDay = safe_strftime('%Oe', $expiryTimestamp);
        $expiryTime = safe_strftime('%H:%M %Z', $expiryTimestamp);

        $message = gTxt('salutation', array('{name}' => $name)).
            n.n.gTxt('password_reset_confirmation').
            n.hu.'textpattern/index.php?confirm='.$confirm.
            n.n.gTxt('link_expires', array(
                '{year}'  => $expiryYear,
                '{month}' => $expiryMonth,
                '{day}'   => $expiryDay,
                '{time}'  => $expiryTime,
            ));
        if (txpMail($email, "[$sitename] ".gTxt('password_reset_confirmation_request'), $message)) {
            return gTxt('password_reset_confirmation_request_sent');
        } else {
            return array(gTxt('could_not_mail'), E_ERROR);
        }
    } else {
        // Though 'unknown_author' could be thrown, send generic 'request_sent'
        // message instead so that (non-)existence of account names are not leaked.
        // Since this is a short circuit, there's a possibility of a timing attack
        // revealing the existence of an account, which we could defend against
        // to some degree.
        return gTxt('password_reset_confirmation_request_sent');
    }
}

/**
 * Emails a new user with login details.
 *
 * This function can be only executed when the currently authenticated user
 * trying to send the email was granted 'admin.edit' privileges.
 *
 * Should NEVER be used as sending plaintext passwords is wrong.
 * Will be removed in future, in lieu of sending reset request tokens.
 *
 * @param      string $RealName The real name
 * @param      string $name     The login name
 * @param      string $email    The email address
 * @param      string $password The password
 * @return     bool FALSE on error.
 * @deprecated in 4.6.0
 * @see        send_new_password(), send_reset_confirmation_request
 * @example
 * if (send_password('John Doe', 'login', 'example@example.tld', 'password'))
 * {
 *     echo "Login details sent.";
 * }
 */

function send_password($RealName, $name, $email, $password)
{
    global $sitename;

    require_privs('admin.edit');

    $message = gTxt('salutation', array('{name}' => $RealName)).

        n.n.gTxt('you_have_been_registered').' '.$sitename.

        n.n.gTxt('your_login_is').': '.$name.
        n.gTxt('your_password_is').': '.$password.

        n.n.gTxt('log_in_at').': '.hu.'textpattern/index.php';

    return txpMail($email, "[$sitename] ".gTxt('your_login_info'), $message);
}

/**
 * Sends a new password to an existing user.
 *
 * If the $name is FALSE, the password is sent to the currently
 * authenticated user.
 *
 * Should NEVER be used as sending plaintext passwords is wrong.
 * Will be removed in future, in lieu of sending reset request tokens.
 *
 * @param      string $password The new password
 * @param      string $email    The email address
 * @param      string $name     The login name
 * @return     bool FALSE on error.
 * @deprecated in 4.6.0
 * @see        send_reset_confirmation_request
 * @see        reset_author_pass()
 * @example
 * $pass = generate_password();
 * if (send_new_password($pass, 'example@example.tld', 'user'))
 * {
 *     echo "Password was sent to 'user'.";
 * }
 */

function send_new_password($password, $email, $name)
{
    global $txp_user, $sitename;

    if (empty($name)) {
        $name = $txp_user;
    }

    $message = gTxt('salutation', array('{name}' => $name)).

        n.n.gTxt('your_password_is').': '.$password.

        n.n.gTxt('log_in_at').': '.hu.'textpattern/index.php';

    return txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message);
}

/**
 * Generates a password.
 *
 * Generates a random password of given length using the symbols set in
 * PASSWORD_SYMBOLS constant.
 *
 * Should NEVER be used as it is not cryptographically secure.
 * Will be removed in future, in lieu of sending reset request tokens.
 *
 * @param      int $length The length of the password
 * @return     string Random plain-text password
 * @deprecated in 4.6.0
 * @see        \Textpattern\Password\Generate
 * @see        \Textpattern\Password\Random
 * @example
 * echo generate_password(128);
 */

function generate_password($length = 10)
{
    static $chars;

    if (!$chars) {
        $chars = str_split(PASSWORD_SYMBOLS);
    }

    $pool = false;
    $pass = '';

    for ($i = 0; $i < $length; $i++) {
        if (!$pool) {
            $pool = $chars;
        }

        $index = mt_rand(0, count($pool) - 1);
        $pass .= $pool[$index];
        unset($pool[$index]);
        $pool = array_values($pool);
    }

    return $pass;
}

/**
 * Resets the given user's password and emails it.
 *
 * The old password is replaced with a new random-generated one.
 *
 * Should NEVER be used as sending plaintext passwords is wrong.
 * Will be removed in future, in lieu of sending reset request tokens.
 *
 * @param  string $name The login name
 * @return string A localized message string
 * @deprecated in 4.6.0
 * @see    PASSWORD_LENGTH
 * @see    generate_password()
 * @example
 * echo reset_author_pass('username');
 */

function reset_author_pass($name)
{
    $email = safe_field("email", 'txp_users', "name = '".doSlash($name)."'");

    $new_pass = Txp::get('\Textpattern\Password\Random')->generate(PASSWORD_LENGTH);

    $rs = change_user_password($name, $new_pass);

    if ($rs) {
        if (send_new_password($new_pass, $email, $name)) {
            return gTxt('password_sent_to').' '.$email;
        } else {
            return gTxt('could_not_mail').' '.$email;
        }
    } else {
        return gTxt('could_not_update_author').' '.txpspecialchars($name);
    }
}

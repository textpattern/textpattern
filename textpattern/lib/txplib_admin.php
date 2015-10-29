<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
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
 * Collection of password handling functions.
 *
 * @package User
 */

/**
 * Emails a new user with login details.
 *
 * This function can be only executed when the currently authenticated user
 * trying to send the email was granted 'admin.edit' privileges.
 *
 * @param  string $RealName The real name
 * @param  string $name     The login name
 * @param  string $email    The email address
 * @param  string $password The password
 * @return bool FALSE on error.
 * @see    send_new_password()
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

    $message = gTxt('greeting').' '.$RealName.','.

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
 * @param  string $password The new password
 * @param  string $email    The email address
 * @param  string $name     The login name
 * @return bool FALSE on error.
 * @see    send_password()
 * @see    reset_author_pass()
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

    $message = gTxt('greeting').' '.$name.','.

        n.n.gTxt('your_password_is').': '.$password.

        n.n.gTxt('log_in_at').': '.hu.'textpattern/index.php';

    return txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message);
}

/**
 * Sends a password reset link to a user's email address.
 *
 * This function will return a success message even when the specified user
 * doesn't exist. Though an error message could be thrown when user isn't found,
 * this is done due to security. This prevents the function from leaking
 * existing account names.
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

    $rs = safe_row("email, nonce", 'txp_users', "name = '".doSlash($name)."'");

    if ($rs) {
        extract($rs);

        $confirm = bin2hex(pack('H*', substr(md5($nonce), 0, 10)).$name);

        $message = gTxt('greeting').' '.$name.','.

            n.n.gTxt('password_reset_confirmation').': '.
            n.hu.'textpattern/index.php?confirm='.$confirm;

        if (txpMail($email, "[$sitename] ".gTxt('password_reset_confirmation_request'), $message)) {
            return gTxt('password_reset_confirmation_request_sent');
        } else {
            return array(gTxt('could_not_mail'), E_ERROR);
        }
    } else {
        // Though 'unknown_author' could be thrown, send generic 'request_sent'
        // message instead so that (non-)existence of account names are not leaked.
        return gTxt('password_reset_confirmation_request_sent');
    }
}

/**
 * Generates a password.
 *
 * Generates a random password of given length using the symbols set in
 * PASSWORD_SYMBOLS constant.
 *
 * @param  int $length The length of the password
 * @return string Random plain-text password
 * @see    PASSWORD_SYMBOLS
 * @see    PASSWORD_LENGTH
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
 * The old password replaced with a new random-generated one.
 *
 * @param  string $name The login name
 * @return string A localized message string
 * @see    PASSWORD_LENGTH
 * @see    generate_password()
 * @example
 * echo reset_author_pass('username');
 */

function reset_author_pass($name)
{
    $email = safe_field("email", 'txp_users', "name = '".doSlash($name)."'");

    $new_pass = generate_password(PASSWORD_LENGTH);
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

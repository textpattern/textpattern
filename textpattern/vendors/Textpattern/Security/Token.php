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
 * Manages security tokens.
 *
 * @since   4.9.0
 * @package Security
 */

namespace Textpattern\Security;

class Token implements \Textpattern\Container\ReusableInterface
{
    /**
     * Generate a ciphered token.
     *
     * The token is reproducible, unique among sites and users, expires later.
     *
     * @see  form_token()
     * @return string CSRF token
     */

    public function csrf()
    {
        static $token = null;
        global $txp_user;

        // Generate a ciphered token from the current user's nonce (thus valid for
        // login time plus 30 days) and a pinch of salt from the blog UID.
        if ($token === null && $txp_user) {
            $nonce = safe_field("nonce", 'txp_users', "name = '".doSlash($txp_user)."'");
            $token = md5($nonce.get_pref('blog_uid'));
        }

        return $token;
    }

    /**
     * Validates admin steps and protects against CSRF attempts using tokens.
     *
     * Takes an admin step and validates it against an array of valid steps.
     * The valid steps array indicates the step's token based session riding
     * protection needs.
     *
     * If the step requires CSRF token protection, and the request doesn't come with
     * a valid token, the request is terminated, defeating any CSRF attempts.
     *
     * If the $step isn't in valid $steps, it returns FALSE, but the request
     * isn't terminated. If the $step is valid and passes CSRF validation,
     * returns TRUE.
     *
     * @param   string $step  Requested admin step
     * @param   array  $steps An array of valid steps with flag indicating CSRF needs,
     *                        e.g. array('savething' => true, 'listthings' => false)
     * @return  bool          If the $step is valid, proceeds and returns TRUE. Dies on CSRF attempt.
     * @see     $this->csrf()
     * @example
     * global $step;
     * if (Txp::get('\Textpattern\Security\Token')->bouncer($step, array(
     *     'browse'     => false,
     *     'edit'       => false,
     *     'save'       => true,
     *     'multi_edit' => true,
     * )))
     * {
     *     echo "The '{$step}' is valid.";
     * }
     */

    public function bouncer($step, $steps)
    {
        global $event;

        if (empty($step)) {
            return true;
        }

        // Validate step.
        if (!array_key_exists($step, $steps)) {
            return false;
        }

        // Does this step require a token?
        if (!$steps[$step]) {
            return true;
        }

        if (is_array($steps[$step]) && gpsa(array_keys($steps[$step])) != $steps[$step]) {
            return true;
        }

        // Validate token.
        if (gps('_txp_token') === $this->csrf()) {
            return true;
        }

        die(gTxt('get_off_my_lawn', array(
            '{event}' => $event,
            '{step}'  => $step,
        )));
    }

    /**
     * Create a secure token hash in the database from the passed information.
     *
     * @param  int    $ref             Reference to the user's account (user_id) or some other id
     * @param  string $type            Flavour of token to create
     * @param  int    $expiryTimestamp UNIX timestamp of when the token will expire
     * @param  string $pass            Password, used as part of the token generation
     * @param  string $nonce           Random nonce associated with the token
     * @return string                  Secure token suitable for emailing as part of a link
     */

    public function generate($ref, $type, $expiryTimestamp, $pass, $nonce)
    {
        $ref = assert_int($ref);
        $expiry = safe_strftime('%Y-%m-%d %H:%M:%S', $expiryTimestamp);

        // The selector becomes an indirect reference to the user row id,
        // and thus does not leak information when publicly displayed.
        $selector = \Txp::get('\Textpattern\Password\Random')->generate(12);

        // Use a hash of the nonce, selector and password.
        // This ensures that requests expire automatically when:
        //  a) The person logs in, or
        //  b) They successfully set/change their password
        // Using the selector in the hash just injects randomness, otherwise two requests
        // back-to-back would generate the same code.
        // Old requests for the same user id are purged when password is set.
        $token = $this->constructHash($selector, $pass, $nonce);
        $user_token = $token.$selector;

        // Remove any previous activation tokens and insert the new one.
        $safe_type = doSlash($type);
        safe_delete("txp_token", "reference_id = '$ref' AND type = '$safe_type'");
        safe_insert("txp_token",
                "reference_id = '$ref',
                type = '$safe_type',
                selector = '".doSlash($selector)."',
                token = '".doSlash($token)."',
                expires = '".doSlash($expiry)."'
            ");

        return $user_token;
    }

    /**
     * Construct a hash value from the cryptographic combination of the passed params.
     *
     * @param  string $selector The stretch
     * @param  string $pass     The secret
     * @param  string $nonce    The salt
     * @return string           Token
     */

    public function constructHash($selector, $pass, $nonce)
    {
        return bin2hex(pack('H*', substr(hash(HASHING_ALGORITHM, $nonce.$selector.$pass), 0, SALT_LENGTH)));
    }

    /**
     * Return the given token by its type and selector.
     *
     * @param  string $type     The type of token
     * @param  string $selector The selector to locate the token row
     * @return array            The relevant fields from the found row, or empty array if not found
     */

    public function fetch($type, $selector)
    {
        return safe_row(
            "reference_id, token, expires",
            'txp_token',
            "selector = '".doSlash($selector)."' AND type='".doSlash($type)."'"
        );
    }

    /**
     * Remove used/unnecessary/expired tokens. Chainable.
     *
     * @param  string $type     Plugin type
     * @param  string $ref      Reference to a particular row
     * @param  string $interval Remove other rows that are outside this time range
     * @example
     * Txp::get('\Textpattern\Security\Token')->remove('password_reset', 42, '4 HOUR');
     */

    public function remove($type, $ref = null, $interval = null)
    {
        $where = array();

        if ($ref) {
            $where[] = 'reference_id = '.doSlash($ref);
        }

        if ($interval) {
            $where[] = 'expires < DATE_SUB(NOW(), INTERVAL '.doSlash($interval).')';
        }

        $whereStr = implode(' OR ', $where);

        safe_delete("txp_token", "type = '".doSlash($type)."' AND (".$whereStr.")");

        return $this;
    }
}

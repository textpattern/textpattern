<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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
 * Privacy-related tags.
 *
 * @since  4.9.0
 */

namespace Textpattern\Tag\Syntax;

class Privacy
{
    public static function if_logged_in($atts, $thing = null)
    {
        global $txp_groups;
        static $cache = array();
    
        extract(lAtts(array(
            'group' => '',
            'name'  => '',
        ), $atts));
    
        $user = isset($cache[$name]) ? $cache[$name] : ($cache[$name] = is_logged_in($name));
        $x = false;
    
        if ($user && $group !== '') {
            $privs = do_list($group);
            $groups = array_flip($txp_groups);
    
            foreach ($privs as &$priv) {
                if (!is_numeric($priv) && isset($groups[$priv])) {
                    $priv = $groups[$priv];
                } else {
                    $priv = intval($priv);
                }
            }
    
            $privs = array_unique($privs);
    
            if (in_array($user['privs'], $privs)) {
                $x = true;
            }
        } else {
            $x = (bool) $user;
        }
    
        return isset($thing) ? parse($thing, $x) : $x;
    }
    
    // -------------------------------------------------------------

    public static function password_protect($atts, $thing = null)
    {
        ob_start();

        extract(lAtts(array(
            'login' => null,
            'pass'  => null,
            'privs' => null,
        ), $atts));

        if ($pass === null) {
            $access = ($user = is_logged_in($login)) !== false && ($privs === null || in_list($user['privs'], $privs));
        } else {
            $au = serverSet('PHP_AUTH_USER');
            $ap = serverSet('PHP_AUTH_PW');

            // For PHP as (f)cgi, two rules in htaccess often allow this workaround.
            $ru = serverSet('REDIRECT_REMOTE_USER');

            if (!$au && !$ap && strpos($ru, 'Basic') === 0) {
                list($au, $ap) = explode(':', base64_decode(substr($ru, 6)));
            }

            $access = $au === $login && $ap === $pass;
        }

        if ($access === false && $pass !== null) {
            header('WWW-Authenticate: Basic realm="Private"');
        }

        if ($thing === null) {
            if ($access === false) {
                txp_die(gTxt('auth_required'), '401');
            }

            return '';
        }

        return parse($thing, $access);
    }
}

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
 * Basic security filter options.
 *
 * <code>
 * Txp::get('\Textpattern\Security\Filter')->registerGlobals()->setMaxRequestUriLength(255);
 * </code>
 *
 * @since   4.6.0
 * @package Security.
 */

namespace Textpattern\Security;

use \Txp;

class Filter
{
    /**
     * An array of protected superglobals.
     *
     * @var array
     */

    private $protectedGlobals = array(
        '_SESSION',
        '_ENV',
        '_GET',
        '_POST',
        '_COOKIE',
        '_FILES',
        '_SERVER',
        '_REQUEST',
        'GLOBALS',
    );

    /**
     * Protection from those who'd bomb the site by GET.
     *
     * @throws \Textpattern\Security\Exception
     * @return \Textpattern\Security\Filter
     */

    public function setMaxRequestUriLength($length)
    {
        $uri = Txp::get('\Textpattern\Server\Config')->getVariable('REQUEST_URI');

        if (strlen($uri) > $length) {
            throw new Exception('Requested URL length exceeds application limit.');
        }

        return $this;
    }

    /**
     * Wipes automatically registered superglobals.
     *
     * Protects the server from global registering and overwriting attempts.
     *
     * @throws \Textpattern\Security\Exception
     * @return \Textpattern\Security\Filter
     */

    public function registerGlobals()
    {
        if (Txp::get('\Textpattern\Server\Config')->getRegisterGlobals()) {
            if (array_key_exists('GLOBALS', $_REQUEST) || array_key_exists('GLOBALS', $_FILES)) {
                throw new Exception('GLOBALS overwrite attempt detected. Please consider turning register_globals off.');
            }

            $variables = array_merge(
                isset($_SESSION) ? (array)$_SESSION : array(),
                (array)$_ENV,
                (array)$_GET,
                (array)$_POST,
                (array)$_COOKIE,
                (array)$_FILES,
                (array)$_SERVER
            );

            foreach ($variables as $variable => $value) {
                if (!in_array($variable, $this->protectedGlobals, true)) {
                    unset($GLOBALS[$variable]);
                }
            }
        }

        return $this;
    }
}

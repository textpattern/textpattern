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
 * Access server configuration variables.
 *
 * <code>
 * Txp::get('Textpattern\Server\Config')->getVariable('REQUEST_URI');
 * </code>
 *
 * @since   4.6.0
 * @package Server
 */

namespace Textpattern\Server;

class Config
{
    /**
     * Magic quotes GPC status.
     *
     * @var bool
     */

    private $magicQuotesGpc = false;

    /**
     * Magic quotes runtime status.
     *
     * @var bool
     */

    private $magicQuotesRuntime = false;

    /**
     * Register globals status.
     *
     * @var bool
     */

    private $registerGlobals = false;

    /**
     * Constructor.
     */

    public function __construct()
    {
        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->magicQuotesGpc = @get_magic_quotes_gpc();
            $this->magicQuotesRuntime = @get_magic_quotes_runtime();
            $this->registerGlobals = @ini_get('register_globals');
        }
    }

    /**
     * Gets a server configuration variable.
     *
     * @param  string $name The variable
     * @return mixed The variable
     */

    public function getVariable($name)
    {
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        return false;
    }

    /**
     * Magic quotes.
     *
     * @return bool
     */

    public function getMagicQuotesGpc()
    {
        return (bool)$this->magicQuotesGpc;
    }

    /**
     * Gets register globals status.
     *
     * @return bool
     */

    public function getRegisterGlobals()
    {
        return (bool)$this->registerGlobals;
    }

    /**
     * Turn runtime magic quotes off.
     *
     * <code>
     * Txp::get('\Textpattern\Server\Config')->setMagicQuotesOff();
     * </code>
     *
     * @return \Textpattern\Server\Config
     */

    public function setMagicQuotesOff()
    {
        if ($this->magicQuotesRuntime) {
            @set_magic_quotes_runtime(0);
        }

        return $this;
    }
}

<?php 

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2013 The Textpattern Development Team
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
 * Txp::get('ServerVar')->SERVER_NAME;
 * </code>
 *
 * @since   4.6.0
 * @package Server
 */

class Textpattern_Server_Var
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
	 * Constructor.
	 */

	public function __construct()
	{
		if (version_compare(PHP_VERSION, '5.4.0') < 0)
		{
			$this->magicQuotesGpc = @get_magic_quotes_gpc();
			$this->magicQuotesRuntime = @get_magic_quotes_runtime();
		}
	}

	/**
	 * Gets a server configuration variable.
	 *
	 * @param  string $name The variable
	 * @return mixed  The variable
	 */

	public function __get($name)
	{
		if (isset($_SERVER[$name]))
		{
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
		return (bool) $this->magicQuotesGpc;
	}

	/**
	 * Turn runtime magic quotes off.
	 *
	 * <code>
	 * Txp::get('ServerVar')->setMagicQuotesOff();
	 * </code>
	 *
	 * @return Textpattern_Server_Var
	 */

	public function setMagicQuotesOff()
	{
		if ($this->magicQuotesRuntime)
		{
			@set_magic_quotes_runtime(0);
		}

		return $this;
	}
}

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
 * Basic security filter options.
 *
 * <code>
 * Txp::get('SecurityFilter')->setMaxRequestUriLength(255)->registerGlobals();
 * </code>
 *
 * @since   4.6.0
 * @package Security.
 */

class Textpattern_Security_Filter
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
	 * @throws Textpattern_Security_Exception
	 * @return Textpattern_Security_Filter
	 */

	public function setMaxRequestUriLength($length)
	{
		$uri = Txp::get('ServerVar')->REQUEST_URI;

		if (strlen($uri) > $length)
		{
			throw new Textpattern_Security_Exception(gTxt('requested_uri_too_long'));
		}

		return $this;
	}

	/**
	 * Wipes automatically registered superglobals.
	 *
	 * Protects the server from global registering
	 * and overwriting attempts.
	 *
	 * @throws Textpattern_Security_Exception
	 * @return Textpattern_Security_Filter
	 */

	public function registerGlobals()
	{
		if (@ini_get('register_globals'))
		{
			if (array_key_exists('GLOBALS', $_REQUEST) || array_key_exists('GLOBALS', $_FILES))
			{
				throw new Textpattern_Security_Exception(gTxt('globals_overwrite_detected'));
			}

			$variables = array_merge(
				isset($_SESSION) ? (array) $_SESSION : array(),
				(array) $_ENV,
				(array) $_GET,
				(array) $_POST,
				(array) $_COOKIE,
				(array) $_FILES,
				(array) $_SERVER
			);

			foreach ($variables as $variable => $value)
			{
				if (!in_array($variable, $this->protectedGlobals, true))
				{
					unset($GLOBALS[$variable]);
				}
			}
		}

		return $this;
	}
}

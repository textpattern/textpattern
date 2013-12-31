<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2014 The Textpattern Development Team
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
 * Factory.
 *
 * @since   4.6.0
 * @package Container
 * @example
 * Txp::get('PasswordHash')->hash('abc');
 * Txp::get('TypeString', 'Hello word!')->replace('!', '.')->getLength();
 */

class Txp implements Textpattern_Container_FactoryInterface
{
	/**
	 * Stores the container instance.
	 *
	 * @var Textpattern_Container_Container
	 */

	static private $container;

	/**
	 * {@inheritdoc}
	 */

	static public function get($name)
	{
		$args = func_get_args();
		return self::getContainer()->getInstance(array_shift($args), $args);
	}

	/**
	 * {@inheritdoc}
	 */

	static public function getContainer()
	{
		if (!self::$container)
		{
			self::$container = new Textpattern_Container_Container();
		}

		return self::$container;
	}
}

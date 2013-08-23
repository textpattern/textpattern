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
 * Factory template.
 *
 * @since   4.6.0
 * @package Container
 */

interface Textpattern_Container_FactoryInterface
{
	/**
	 * Gets an instance.
	 *
	 * @param  string $name The class
	 * @return object
	 */

	static public function get($name);

	/**
	 * Gets the container.
	 *
	 * @return Textpattern_Container_ContainerInterface
	 */

	static public function getContainer();
}

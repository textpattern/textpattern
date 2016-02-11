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
 * Container.
 *
 * @since   4.6.0
 * @package Container
 */

namespace Textpattern\Container;

interface ContainerInterface
{
    /**
     * Gets an instance for the given alias.
     *
     * @param  string $alias   The class alias
     * @param  array  $options Options
     * @return object Instance of the resolved class
     */

    public function getInstance($alias, array $options);

    /**
     * Removes a registered class.
     *
     * @param  string $alias The alias
     * @return \Textpattern\Container\ContainerInterface
     */

    public function remove($alias);

    /**
     * Registers a class.
     *
     * Throws an exception if the alias is taken. To replace an alias, first
     * call remove.
     *
     * @param  string $alias The alias
     * @param  string $class The class
     * @return \Textpattern\Container\ContainerInterface
     * @throws InvalidArgumentException
     */

    public function register($alias, $class);
}

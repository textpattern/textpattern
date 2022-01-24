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
 * Factory template.
 *
 * @since   4.6.0
 * @package Container
 */

namespace Textpattern\Container;

interface FactoryInterface
{
    /**
     * Gets an instance.
     *
     * @param  string $name The class
     * @return object
     */

    public static function get($name);

    /**
     * Gets the container.
     *
     * @return \Textpattern\Container\ContainerInterface
     */

    public static function getContainer();
}

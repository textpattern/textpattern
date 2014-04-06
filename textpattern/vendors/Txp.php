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
 * <code>
 * Txp::get('Textpattern_Password_Hash')->hash('abc');
 * Txp::get('Textpattern_Type_String', 'Hello word!')->replace('!', '.')->getLength();
 * </code>
 *
 * @since   4.6.0
 * @package Container
 */

class Txp implements Textpattern_Container_FactoryInterface
{
    /**
     * Stores the container instance.
     *
     * @var Textpattern_Container_Container
     */

    private static $container;

    /**
     * {@inheritdoc}
     */

    public static function get($name)
    {
        $args = func_get_args();
        return self::getContainer()->getInstance(array_shift($args), $args);
    }

    /**
     * {@inheritdoc}
     */

    public static function getContainer()
    {
        if (!self::$container) {
            self::$container = new Textpattern_Container_Container();
        }

        return self::$container;
    }
}

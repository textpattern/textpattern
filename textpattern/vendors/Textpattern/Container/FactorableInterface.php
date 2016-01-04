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
 * Provides the factory its own initialisation method.
 *
 * The following will call 'getInstance()' method when creating new instance:
 *
 * <code>
 * class Abc_Class implements \Textpattern\Container\FactorableInterface
 * {
 *     public function getInstance()
 *     {
 *         echo 'Created instance';
 *         return $this;
 *     }
 * }
 * Txp::get('Abc_Class');
 * </code>
 *
 * The above echoes 'Created instance' as the method is invoked. Keep in mind
 * that implementing this interface doesn't prevent constructors from running,
 * or let you to initialise private classes. It merely adds an additional method
 * to the factory line.
 *
 * @since   4.6.0
 * @package Container
 */

namespace Textpattern\Container;

interface FactorableInterface
{
    /**
     * Gets an instance of the class for the factory.
     *
     * @return \Textpattern\Container\FactorableInterface
     */

    public function getInstance();
}

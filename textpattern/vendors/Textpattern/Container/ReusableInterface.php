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
 * Signals to the factory that the instance can be reused.
 *
 * Instances of this interface are treated as static. Once you initialise the
 * instance, it's kept and used again each time you reference the class using
 * the factory.
 *
 * For instance, the following will remember the initial value:
 *
 * <code>
 * class Abc_Class implements \Textpattern\Container\ReusableInterface
 * {
 *     public $random;
 *     public function __construct()
 *     {
 *         $this->random = rand();
 *     }
 * }
 * echo Txp::get('Abc_Class')->random;
 * echo Txp::get('Abc_Class')->random;
 * echo Txp::get('Abc_Class')->random;
 * </code>
 *
 * All three calls return the same Abc_Class::$random as the instance is kept
 * between calls.
 *
 * @since   4.6.0
 * @package Container
 */

namespace Textpattern\Container;

interface ReusableInterface
{
}

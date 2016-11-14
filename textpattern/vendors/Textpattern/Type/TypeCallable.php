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
 * Callable object.
 *
 * Inspects and converts callables.
 *
 * <code>
 * echo Txp::get('\Textpattern\Type\TypeCallable', array('class', 'method'))->toString();
 * </code>
 *
 * @since   4.6.0
 * @package Type
 */

namespace Textpattern\Type;

class TypeCallable implements TypeInterface
{
    /**
     * The callable.
     *
     * @var callable
     */

    protected $callable;

    /**
     * Constructor.
     *
     * @param string $callable The callable
     */

    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    /**
     * Gets the callable string presentation.
     *
     * @return string
     */

    public function __toString()
    {
        return (string)$this->toString();
    }

    /**
     * Converts a callable to a string presentation.
     *
     * If the callable is an object, returns the class name. For a callable
     * array of object and method, a 'class::staticMethod' or a 'class->method',
     * and for functions the name.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\TypeCallable', function () {return 'Hello world!';});
     * </code>
     *
     * Returns 'Closure'.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\TypeCallable', array('DateTimeZone', 'listAbbreviations'));
     * </code>
     *
     * Returns 'DateTimeZone::listAbbreviations'.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\TypeCallable', array(new DateTime(), 'setTime'));
     * </code>
     *
     * Returns 'DateTime->setTime'.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\TypeCallable', 'date');
     * </code>
     *
     * Returns 'date'.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\TypeCallable', 1);
     * </code>
     *
     * Returns ''.
     *
     * @return string The callable as a human-readable string
     */

    public function toString()
    {
        $callable = $this->callable;

        if (is_object($callable)) {
            return get_class($callable);
        }

        if (is_array($callable)) {
            $class = array_shift($callable);
            $separator = '::';

            if (is_object($class)) {
                $class = get_class($class);
                $separator = '->';
            }

            array_unshift($callable, $class);

            return implode($separator, array_filter($callable, 'is_scalar'));
        }

        if (!is_string($callable)) {
            return '';
        }

        return $callable;
    }
}

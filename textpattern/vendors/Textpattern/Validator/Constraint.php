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

namespace Textpattern\Validator;

/**
 * Constraint.
 *
 * Defines a single validation rule.
 *
 * @since   4.6.0
 * @package Validator
 */

class Constraint
{
    /**
     * The value to be validated.
     *
     * @var mixed
     */

    protected $value;

    /**
     * An array of options.
     *
     * @var array
     */

    protected $options;

    /**
     * Constructs a constraint.
     *
     * @param mixed $value The validee
     * @param array $options Key/value pairs of class-specific options
     */

    public function __construct($value, $options = array())
    {
        if (empty($options['message'])) {
            $options['message'] = 'undefined_constraint_violation';
        }

        $this->value = $value;
        $this->options = $options;
    }

    /**
     * Sets validee's value.
     *
     * @param $value mixed Validee
     */

    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Sets options.
     *
     * @param $options Scalar or array of options
     * @param null $key Key for scalar option
     */

    public function setOptions($options, $key = null)
    {
        if ($key === null) {
            $this->options = $options;
        } else {
            $this->options[$key] = $options;
        }
    }

    /**
     * Validate a given value against this constraint.
     *
     * @return bool If TRUE, the value obeys constraint
     */

    public function validate()
    {
        return true;
    }

    /**
     * Gets a message.
     *
     * @return string
     */

    public function getMessage()
    {
        return $this->options['message'];
    }
}

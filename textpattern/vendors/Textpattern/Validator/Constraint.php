<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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
     * An array of constraint values -> HTML attributes.
     *
     * @var array
     */

    protected $attributeMap = array();

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
     * Sets validee's value. Chainable.
     *
     * @param $value mixed Validee
     */

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets options. Chainable.
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

        return $this;
    }

    /**
     * Sets attribute map, singly or en masse. Chainable.
     *
     * @param array|string $map Scalar or array of options
     * @param string|null  $key Key for scalar option
     */

    public function setAttsMap($map, $key = null)
    {
        if ($key === null) {
            foreach ($map as $idx => $att) {
                // Permit shortcutting where attributes match parameters.
                $idx = is_numeric($idx) ? $att : $idx;

                if (array_key_exists($idx, $this->options)) {
                    $this->attributeMap[$idx] = $att;
                }
            }
        } else {
            if (array_key_exists($key, $this->options)) {
                $this->attributeMap[$key] = $map;
            }
        }

        return $this;
    }

    /**
     * Gets attribute map.
     *
     * @param  $key option to return just one of the values. If omitted, all are returned
     * @return array of HTML attribute->value pairs suitable for passing to a UI control
     */

    public function getAttsMap($key = null)
    {
        $out = array();

        foreach ($this->attributeMap as $idx => $att) {
            // Permit shortcuts where attributes match parameters.
            $idx = is_numeric($idx) ? $att : $idx;

            if ($this->options[$idx] !== null) {
                $out[$att] = $this->options[$idx];
            }
        }

        if ($key !== null) {
            return isset($out[$key]) ? array($key => $out[$key]) : array();
        }

        return $out;
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

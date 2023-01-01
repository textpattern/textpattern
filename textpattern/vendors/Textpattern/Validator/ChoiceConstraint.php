<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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
 * Tests one or more values against a list of valid options.
 *
 * @since   4.6.0
 * @package Validator
 */

namespace Textpattern\Validator;

class ChoiceConstraint extends Constraint
{
    /**
     * Constructor.
     *
     * @param mixed $value
     * @param array $options
     */

    public function __construct($value, $options = array())
    {
        $options = lAtts(array(
            'choices'     => array(),
            'allow_blank' => false,
            'message'     => 'unknown_choice',
        ), $options, false);
        parent::__construct($value, $options);
    }

    /**
     * Validates.
     *
     * @return bool
     */

    public function validate()
    {
        $values = !is_array($this->value) ? (array) $this->value : $this->value;

        $out = true;

        foreach ($values as $val) {
            $out = $out && (($this->options['allow_blank'] && ($val === '')) ||
                in_array($val, $this->options['choices']));
        }

        return $out;
    }
}

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

/**
 * Tests the number of options selected.
 *
 * @since   4.9.0
 * @package Validator
 */

namespace Textpattern\Validator;

class CountConstraint extends Constraint
{
    /**
     * Constructor.
     *
     * @param mixed $value
     * @param array $options Contains any/all of: min/max/message
     */

    public function __construct($value, $options = array())
    {
        $options = lAtts(array(
            'message' => 'out_of_range',
            'min'     => null,
            'max'     => null,
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

        if ($this->options['min'] !== null) {
            $out = $out && (count($values) >= $this->options['min']);
        }

        if ($this->options['max'] !== null) {
            $out = $out && (count($values) <= $this->options['max']);
        }

        return $out;
    }
}

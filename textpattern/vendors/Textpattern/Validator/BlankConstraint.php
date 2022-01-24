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

namespace Textpattern\Validator;

/**
 * Validates that a value is blank, defined as equal to a blank string or equal
 * to null.
 *
 * @since   4.6.0
 * @package Validator
 */

class BlankConstraint extends Constraint
{
    /**
     * Constructor.
     *
     * @param mixed $value
     * @param array $options
     */

    public function __construct($value, $options = array())
    {
        $options = lAtts(array('message' => 'should_be_blank'), $options, false);
        parent::__construct($value, $options);
    }

    /**
     * Validates.
     *
     * @return bool
     */

    public function validate()
    {
        return $this->value === '' || $this->value === null;
    }
}

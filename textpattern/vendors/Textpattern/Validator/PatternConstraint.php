<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2019 The Textpattern Development Team
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
 * Constraint for field patterns (regexes).
 *
 * @since   4.9.0
 * @package Validator
 */

namespace Textpattern\Validator;

class PatternConstraint extends Constraint
{
    /**
     * Function parameter => HTML attribute map.
     *
     * @var array
     */

    protected $attributeMap = array('pattern');

    /**
     * Constructor.
     *
     * @param mixed $value
     * @param array $options Contains any/all of: pattern/message/global
     */

    public function __construct($value, $options = array())
    {
        $options = lAtts(array(
            'message' => 'invalid_pattern',
            'pattern' => null,
            'global' => false,
        ), $options, false);
        parent::__construct($value, $options);
    }

    /**
     * Validates filter values.
     *
     * @return bool
     */

    public function validate()
    {
        if ($this->options['global']) {
            $out = (preg_match_all($this->options['pattern'], $this->value) >= 1);
        } else {
            $out = (preg_match($this->options['pattern'], $this->value) >= 1);
        }

        return $out;
    }
}

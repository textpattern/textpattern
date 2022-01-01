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
 * Main Validator class.
 *
 * @since   4.6.0
 * @package Validator
 */

class Validator
{
    /**
     * An array of constraint objects.
     *
     * @var \Textpattern\Validator\Constraint[]
     */

    protected $constraints;

    /**
     * An array of messages.
     *
     * @var array
     */

    protected $messages;

    /**
     * Constructs a validator.
     *
     * @param \Textpattern\Validator\Constraint[] $constraints Array of constraint objects to validate over
     */

    public function __construct($constraints = array())
    {
        $this->setConstraints($constraints);
    }

    /**
     * Validate all constraints and collect messages on violations.
     *
     * @return bool If TRUE, the value obeys constraints
     */

    public function validate()
    {
        foreach ($this->constraints as $c) {
            if (!$c->validate()) {
                $this->messages[] = $c->getMessage();
            }
        }

        return empty($this->messages);
    }

    /**
     * Gets an array of messages.
     *
     * This method returns an array of message strings with constraint-violation
     * details collected from Validator::validate().
     *
     * @return array An array of messages
     */

    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Sets new constraints.
     *
     * This method takes an array of Textpattern\Validator\Constraint instances, and adds it to end of
     * the current stack.
     *
     * @param \Textpattern\Validator\Constraint|\Textpattern\Validator\Constraint[] $constraints Single or array-of Textpattern\Validator\Constraint object(s)
     */

    public function setConstraints($constraints)
    {
        if (is_array($constraints)) {
            $in = $constraints;
        } else {
            $in[] = $constraints;
        }

        $this->constraints = $in;
        $this->messages = array();
    }
}

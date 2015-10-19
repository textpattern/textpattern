<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2015 The Textpattern Development Team
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
 * Imports Validator.
 */

namespace Textpattern\Textfilter;

require_once txpath.'/lib/txplib_validator.php';

/**
 * Constraint for Textfilters.
 *
 * @since   4.6.0
 * @package Textfilter
 */
class Constraint extends \Constraint
{
    /**
     * Validates filter selection.
     *
     * @return bool
     */

    public function validate()
    {
        return array_key_exists($this->value, \Txp::get('\Textpattern\Textfilter\Registry')->getMap());
    }
}

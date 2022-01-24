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

/*
 * Deprecation warning: This file serves merely as a compatibility layer for \Textpattern\Validator\*.
 * Use the respective base classes for new and updated code.
 * TODO: Remove in v4.next.0
 */

/**
 * Main Validator class.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class Validator extends \Textpattern\Validator\Validator
{
}

/**
 * Constraint.
 *
 * Defines a single validation rule.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class Constraint extends \Textpattern\Validator\Constraint
{
}

/**
 * Tests against a list of values.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class ChoiceConstraint extends \Textpattern\Validator\Constraint
{
}

/**
 * Tests against existing section names.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class SectionConstraint extends \Textpattern\Validator\ChoiceConstraint
{
}

/**
 * Tests against existing or blank category names.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class CategoryConstraint extends \Textpattern\Validator\ChoiceConstraint
{
}

/**
 * Tests against existing form names.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class FormConstraint extends \Textpattern\Validator\ChoiceConstraint
{
}

/**
 * Validates that a value is blank, defined as equal to a blank string or equal
 * to null.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class BlankConstraint extends \Textpattern\Validator\Constraint
{
}

/**
 * Validates that a value is true.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class TrueConstraint extends \Textpattern\Validator\Constraint
{
}

/**
 * Validates that a value is false.
 *
 * @since   4.5.0
 * @deprecated in 4.6.0
 * @package Validator
 */

class FalseConstraint extends \Textpattern\Validator\Constraint
{
}

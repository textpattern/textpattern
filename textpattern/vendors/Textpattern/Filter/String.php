<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2013 The Textpattern Development Team
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
 * String filter.
 *
 * @since   4.6.0
 * @package Filter
 * @example
 * try
 * {
 * 	$string = new Textpattern_Filter_String('Hello World!');
 * 	echo (string) $string->length(1, 64)->match('/^[a-z]$/i')->html();
 * }
 * catch (Textpatter_Filter_Exception $e)
 * {
 * 	echo $e->getMessage();
 * }
 */

class Textpattern_Filter_String extends Textpattern_Type_String
{
	/**
	 * {@inheritdoc}
	 */

	public function __construct($string)
	{
		if (!is_string($string))
		{
			throw new Textpattern_Filter_Exception('assert_string');
		}

		parent::__construct($string);
	}

	/**
	 * Matches the string against a regular expression.
	 *
	 * @param  string $pattern The pattern
	 * @param  array  $matches Matches 
	 * @param  int    $flags   Flags
	 * @param  int    $offset  Offset
	 * @return Textpattern_Filter_String
	 * @throws Textpattern_Filter_Exception
	 * @example
	 * $string = new Textpattern_Filter_String('Hello World!');
	 * echo (string) $string->match('/^[^0-9]$/');
	 */

	public function match($pattern, &$matches = null, $flags = 0, $offset = 0)
	{
		if (!preg_match($pattern, $this->string, $matches, $flags, $offset))
		{
			throw new Textpattern_Filter_Exception('filter_not_matching_pattern');
		}

		return $this;
	}

	/**
	 * Limits the length.
	 *
	 * @param  int $min The minimum length
	 * @param  int $max The maximum length
	 * @return Textpattern_Filter_String
	 * @throws Textpattern_Filter_Exception
	 * @example
	 * $string = new Textpattern_Filter_String('Hello World!');
	 * echo (string) $string->length(64);
	 */

	public function length($min, $max = null)
	{
		if ($this->getLength() < $min || ($max !== null && $this->getLength() > $max))
		{
			throw new Textpattern_Filter_Exception('filter_length_not_within_limits');
		}

		return $this;
	}
}
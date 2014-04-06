<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2014 The Textpattern Development Team
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
 * <code>
 * try {
 *     echo (string) Txp::get('Textpattern_Filter_String', 'Hello World!')->length(1, 64)->match('/^[a-z]$/i')->html();
 * } catch (Textpattern_Filter_Exception $e) {
 *     echo $e->getMessage();
 * }
 * </code>
 *
 * @since   4.6.0
 * @package Filter
 */

class Textpattern_Filter_String extends Textpattern_Type_String
{
    /**
     * {@inheritdoc}
     */

    public function __construct($string)
    {
        if (!is_string($string)) {
            throw new Textpattern_Filter_Exception(gTxt('assert_string'));
        }

        parent::__construct($string);
    }

    /**
     * Matches the string against a regular expression.
     *
     * <code>
     * try
     * {
     *     echo (string) Txp::get('Textpattern_Filter_String', 'Hello World!')->match('/^[^0-9]$/');
     * } catch (Textpattern_Filter_Exception $e) {
     *     echo $e->getMessage();
     * }
     * </code>
     *
     * @param  string $pattern The pattern
     * @param  array  $matches Matches
     * @param  int    $flags   Flags
     * @param  int    $offset  Offset
     * @return Textpattern_Filter_String
     * @throws Textpattern_Filter_Exception
     */

    public function match($pattern, &$matches = null, $flags = 0, $offset = 0)
    {
        if (!preg_match($pattern, $this->string, $matches, $flags, $offset)) {
            throw new Textpattern_Filter_Exception(gTxt('assert_pattern'));
        }

        return $this;
    }

    /**
     * Limits the length.
     *
     * <code>
     * try {
     *     echo (string) Txp::get('Textpattern_Filter_String', 'Hello World!')->length(64);
     * } catch (Textpattern_Filter_Exception $e) {
     *     echo $e->getMessage();
     * }
     * </code>
     *
     * @param  int $min The minimum length
     * @param  int $max The maximum length
     * @return Textpattern_Filter_String
     * @throws Textpattern_Filter_Exception
     */

    public function length($min, $max = null)
    {
        if ($this->getLength() < $min || ($max !== null && $this->getLength() > $max)) {
            throw new Textpattern_Filter_Exception(gTxt('assert_length'));
        }

        return $this;
    }
}

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

/**
 * String filter.
 *
 * <code>
 * try {
 *     echo (string) Txp::get('\Textpattern\Filter\StringFilter', 'Hello World!')->length(1, 64)->match('/^[a-z]$/i')->html();
 * } catch (\Textpattern\Filter\Exception $e) {
 *     echo $e->getMessage();
 * }
 * </code>
 *
 * @since   4.6.0
 * @package Filter
 */

namespace Textpattern\Filter;

class StringFilter extends \Textpattern\Type\StringType
{
    /**
     * {@inheritdoc}
     */

    public function __construct($string)
    {
        if (!is_string($string)) {
            throw new Exception(gTxt('assert_string'));
        }

        parent::__construct($string);
    }

    /**
     * Matches the string against a regular expression.
     *
     * <code>
     * try
     * {
     *     echo (string) Txp::get('\Textpattern\Filter\StringFilter', 'Hello World!')->match('/^[^0-9]$/');
     * } catch (\Textpattern\Filter\Exception $e) {
     *     echo $e->getMessage();
     * }
     * </code>
     *
     * @param  string $pattern The pattern
     * @param  array  $matches Matches
     * @param  int    $flags   Flags
     * @param  int    $offset  Offset
     * @return StringFilter
     * @throws \Textpattern\Filter\Exception
     */

    public function match($pattern, &$matches = null, $flags = 0, $offset = 0)
    {
        if (!preg_match($pattern, $this->string, $matches, $flags, $offset)) {
            throw new \Textpattern\Filter\Exception(gTxt('assert_pattern'));
        }

        return $this;
    }

    /**
     * Limits the length.
     *
     * <code>
     * try {
     *     echo (string) Txp::get('\Textpattern\Filter\StringFilter', 'Hello World!')->length(64);
     * } catch (\Textpattern\Filter\Exception $e) {
     *     echo $e->getMessage();
     * }
     * </code>
     *
     * @param  int $min The minimum length
     * @param  int $max The maximum length
     * @return StringFilter
     * @throws \Textpattern\Filter\Exception
     */

    public function length($min, $max = null)
    {
        if ($this->getLength() < $min || ($max !== null && $this->getLength() > $max)) {
            throw new \Textpattern\Filter\Exception(gTxt('assert_length'));
        }

        return $this;
    }
}

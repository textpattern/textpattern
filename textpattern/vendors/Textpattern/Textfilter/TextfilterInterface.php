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
 * Textfilter interface.
 *
 * This is an interface for creating Textfilters.
 *
 * @since   4.6.0
 * @package Textfilter
 */

namespace Textpattern\Textfilter;

interface TextfilterInterface
{
    /**
     * Filters the given raw input value.
     *
     * @param  string $thing   The raw input string
     * @param  array  $options Options
     * @return string Filtered output text
     */

    public function filter($thing, $options);

    /**
     * Gets filter-specific help.
     *
     * A help URL containing usage information that will be
     * presented to the user.
     *
     * @return string Filter-specific help
     */

    public function getHelp();

    /**
     * Gets a filter's globally unique identifier.
     *
     * @return string
     */

    public function getKey();
}

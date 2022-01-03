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
 * Plain-text filter.
 *
 * @since   4.6.0
 * @package Textfilter
 */

namespace Textpattern\Textfilter;

class Plain extends Base implements TextfilterInterface
{
    /**
     * Constructor.
     */

    public function __construct()
    {
        parent::__construct(LEAVE_TEXT_UNTOUCHED, gTxt('leave_text_untouched'));
    }

    /**
     * Filter.
     *
     * @param  string $thing
     * @param  array  $options
     * @return string
     */

    public function filter($thing, $options)
    {
        parent::filter($thing, $options);

        return trim($thing);
    }
}

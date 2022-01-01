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
 * File tags.
 *
 * @since  4.6.0
 */

namespace Textpattern\Tag\Syntax;

class File
{
    /**
     * Checks if the file is the first in the list.
     *
     * @param  array  $atts
     * @param  string $thing
     * @return string
     */

    public static function renderIfFirstFile($atts, $thing)
    {
        global $thisfile;

        assert_file();

        return parse($thing, !empty($thisfile['is_first']));
    }

    /**
     * Checks if the file is the last in the list.
     *
     * @param  array  $atts
     * @param  string $thing
     * @return string
     */

    public static function renderIfLastFile($atts, $thing)
    {
        global $thisfile;

        assert_file();

        return parse($thing, !empty($thisfile['is_last']));
    }
}

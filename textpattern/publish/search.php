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
 * Tools for searching site contents.
 *
 * @package Search
 */

/**
 * Limits search to searchable sections.
 *
 * This function gets a list of searchable sections as an SQL where clause.
 * The returned results can be then be used in or as an SQL query.
 *
 * @return string|bool SQL statement, or FALSE when all sections are included the search
 * @example
 * if ($r = safe_count('textpattern', "Title LIKE '%a%' " . filterSearch()))
 * {
 *     echo 'Found {$r} articles with "a" in the title.';
 * }
 */

function filterSearch()
{
    $rs = safe_column("name", 'txp_section', "searchable != '1'");
    if ($rs) {
        foreach ($rs as $name) {
            $filters[] = "AND Section != '".doSlash($name)."'";
        }

        return join(' ', $filters);
    }

    return false;
}

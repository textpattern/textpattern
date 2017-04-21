<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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
 * Template partials tags.
 *
 * @since  4.6.0
 */

namespace Textpattern\Tag\Syntax;

class Partial
{
    /**
     * Returns the inner content of the enclosing &lt;txp:output_form /&gt; tag.
     *
     * @return string
     */

    public static function renderYield($atts, $thing = null)
    {
        global $yield;

        extract(lAtts(array(
            'name' => '',
            'value' => $thing ? parse($thing) : $thing
        ), $atts));

        $inner = !empty($yield[$name]) ? end($yield[$name]) : $value;

        return isset($inner) ? $inner : '';
    }

    /**
     * Conditional for yield.
     *
     * @param  array  $atts
     * @param  string $thing
     * @return string
     */

    public static function renderIfYield($atts, $thing = null)
    {
        global $yield;

        extract(lAtts(array(
            'name'  => '',
            'value' => null
        ), $atts));

        $inner = isset($yield[$name]) ? end($yield[$name]) : null;

        return parse($thing, $inner !== null && ($value === null || (string)$inner === (string)$value));
    }
}

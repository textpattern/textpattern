<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2018 The Textpattern Development Team
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
     * @param  array  $atts
     * @param  string $thing
     * @return string
     */

    public static function renderYield($atts, $thing = null)
    {
        global $yield;

        extract(lAtts(array(
            'name'    => '',
            'default' => null,
        ), $atts));

        if (isset($yield[$name])) {
            $inner = end($yield[$name]);
        }

        if (!isset($inner)) {
            $inner = isset($default) ? $default : ($thing ? parse($thing) : $thing);
        }

        return $inner;
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
            'value' => null,
        ), $atts));

        $inner = isset($yield[$name]) ? end($yield[$name]) : null;

        return parse($thing, $inner !== null && ($value === null || (string)$inner === (string)$value));
    }
}

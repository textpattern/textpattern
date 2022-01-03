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
        global $yield, $txp_yield, $txp_atts, $txp_item;

        extract(lAtts(array(
            'name'    => '',
            'else'    => false,
            'default' => false,
            'item'    => null
        ), $atts));

        if (isset($item)) {
            $inner = isset($txp_item[$item]) ? $txp_item[$item] : null;
        } elseif ($name === '') {
            $end = empty($yield) ? null : end($yield);

            if (isset($end)) {
                $inner = parse($end, empty($else));
            }
        } elseif (!empty($txp_yield[$name])) {
            list($inner) = end($txp_yield[$name]);
            $txp_yield[$name][key($txp_yield[$name])][1] = true;
        }

        if (!isset($inner)) {
            $escape = isset($txp_atts['escape']) ? $txp_atts['escape'] : null;
            $inner = $default !== false ?
                ($default === true ? page_url(array('type' => $name, 'escape' => $escape)) : $default) :
                ($thing ? parse($thing) : $thing);
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
        global $yield, $txp_yield, $txp_item;

        extract(lAtts(array(
            'name'  => '',
            'else'  => false,
            'value' => null,
            'item'  => null
        ), $atts));

        if (isset($item)) {
            $inner = isset($txp_item[$item]) ? $txp_item[$item] : null;
        } elseif ($name === '') {
            $end = empty($yield) ? null : end($yield);

            if (isset($end)) {
                $inner = $value === null ? ($else ? getIfElse($end, false) : true) : parse($end, empty($else));
            }
        } elseif (empty($txp_yield[$name])) {
            $inner = null;
        } else {
            list($inner) = end($txp_yield[$name]);
        }

        return parse($thing, isset($inner) && ($value === null || (string)$inner === (string)$value || $inner && $value === true));
    }
}

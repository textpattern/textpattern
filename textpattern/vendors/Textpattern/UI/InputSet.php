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
 * An &lt;input /&gt; tag set.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class InputSet extends TagCollection implements UICollectionInterface
{
    /**
     * Construct a set of text input fields from an array.
     *
     * Primarily of use for creating a series of hidden inputs.
     *
     * @param array  $nameVals Key => Value pairs
     * @param string $type     The HTML type attribute
     * @param string $value    The glue to use if any of the values are themselves arrays
     */

    public function __construct($nameVals, $type = null, $join = ',')
    {
        if ($type === null) {
            $type = 'text';
        }

        foreach ((array) $nameVals as $key => $value) {
            if (is_array($value)) {
                $value = implode($join, $value);
            }

            $input = new \Textpattern\UI\Input((string)$key, (string)$type, (string)$value);

            // Retrieve and set the id. Although it's possible to just use $name
            // directly due to the key's simplicity, if the Input implementation of
            // the key changes in the Input class, this is safer.
            $id = $input->getKey();
            $this->add($input, $id);
        }
    }
}

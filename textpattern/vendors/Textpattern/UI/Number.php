<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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
 * A numeric &lt;input /&gt; tag with constraints.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Number extends Input implements UIInterface
{
    /**
     * Construct a single numeric input field.
     *
     * @param string $name        The input key (HTML name attribute)
     * @param string $value       The initial value
     * @param array  $constraints Array indices for min/max/step if desired.
     */

    public function __construct($name, $val, $constraints)
    {
        parent::__construct($name, 'number', $val);

        $min = isset($constraints['min']) ? $constraints['min'] : 0;
        $max = isset($constraints['max']) ? $constraints['max'] : '';
        $step = isset($constraints['step']) ? $constraints['step'] : 1;

        $this->setAtts(array(
                'id'    => $this->key,
                'name'  => $name,
                'min'   => $min,
                'max'   => $max,
                'step'  => $step,
            ), array('strip' => TEXTPATTERN_STRIP_NONE)
        );
    }
}

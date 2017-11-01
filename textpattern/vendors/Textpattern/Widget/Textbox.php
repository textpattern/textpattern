<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
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
 * &lt;input /&gt; tag.
 *
 * @since   4.7.0
 * @package Widget
 */

namespace Textpattern\Widget;

class Textbox extends Tag implements WidgetInterface
{
    public function __construct($name = null, $value = null)
    {
        parent::__construct('input');
        $this->setAtts(array(
                'type' => 'text',
                'name' => $name,
            ), array(
                'mandatory' => true,
            ))
            ->setAtts(array(
                'value' => $value,
            ), array(
                'flag' => TEXTPATTERN_STRIP_NONE,
            ));
    }
}

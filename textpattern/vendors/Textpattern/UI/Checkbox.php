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
 * A single &lt;input type="checkbox" /&gt; tag.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Checkbox extends Tag implements UIInterface
{
    /**
     * The key (id) used in the tag.
     *
     * @var string
     */

    protected $key = null;

    /**
     * Construct a single checkbox button.
     *
     * @param string $name    The Checkbox key (HTML name attribute)
     * @param string $value   The Checkbox value
     * @param bool   $checked Whether the checkbox is selected
     */

    public function __construct($name, $value = null, $checked = true)
    {
        parent::__construct('input');
        $type = $class = 'checkbox';
        $this->key = ($value !== null) ? $name.'-'.$value : $name;

        if ((bool)$checked === true) {
            $this->setBool('checked');
            $class .= ' active';
        }

        $this->setAtts(array(
                'class' => $class,
                'id'    => $this->key,
                'name'  => $name,
                'type'  => $type,
            ))
            ->setAtt('value', $value, array('flag' => TEXTPATTERN_STRIP_NONE));
    }

    /**
     * Fetch the key (id) in use by this radio button.
     *
     * @return string
     */

    public function getKey()
    {
        return $this->key;
    }
}

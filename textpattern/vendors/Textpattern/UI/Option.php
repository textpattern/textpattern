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
 * An &lt;option /&gt; tag.
 *
 * Only used for creating select list components.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Option extends Tag implements UIInterface
{
    /**
     * Construct a single option element.
     *
     * @param string  $value   The option key (HTML value attribute)
     * @param string  $label   The label
     * @param boolean $checked True to select the option by default
     */

    public function __construct($value, $label = '', $checked = false)
    {
        $this->setKey($value);

        parent::__construct('option');
        $this->setAtt('value', $value, array('strip' => TEXTPATTERN_STRIP_NONE))
            ->setContent($label);

        if ($checked) {
            $this->setSelected();
        }
    }

    /**
     * Set the option as selected. Chainable.
     */

    public function setSelected()
    {
        $this->setBool('selected');

        return $this;
    }
}

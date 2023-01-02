<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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
 * UI interface.
 *
 * An interface for creating UI components such as input fields,
 * checkboxes and controls.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

interface UIInterface
{
    /**
     * Sets the tag to use.
     *
     * @param  string $tag Tag name
     * @return this
     */

    public function setTag($tag);

    /**
     * Sets the given attributes.
     *
     * @param  array $atts Name-value attributes
     * @return this
     */

    public function setAtts($atts, $props = array());

    /**
     * Render the complete component.
     *
     * @param  array $option To affect the flavour of tag returned
     * @return string
     */

    public function render($option = null);
}

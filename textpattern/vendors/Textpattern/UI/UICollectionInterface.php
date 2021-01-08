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
 * UI Collection interface.
 *
 * An interface for grouping a set of user interface components.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

interface UICollectionInterface
{
    /**
     * Add a component to the collection. Chainable.
     *
     * @param  object $item The UI component to add
     * @param  string $key  Optional reference to the object in the collection
     * @return this
     */

    public function add($item, $key = null);

    /**
     * Remove a component from the collection by its key. Chainable.
     *
     * @param  string $key The reference to the object in the collection
     * @return this
     */

    public function remove($key);

    /**
     * Fetch a component from the collection by its key.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function get($key);

    /**
     * Render the content as a bunch of XML elements.
     *
     * @return string HTML
     */

    public function render();
}

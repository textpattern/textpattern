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
 * Widget Collection interface.
 *
 * An interface for grouping a set of user interface Widgets.
 *
 * @since   4.8.0
 * @package Widget
 */

namespace Textpattern\Widget;

interface WidgetCollectionInterface
{
    /**
     * Add a widget to the collection. Chainable.
     *
     * @param  object $widget The widget
     * @param  string $key    Optional reference to the object in the collection
     * @return this
     */

    public function addWidget($widget, $key = null);

    /**
     * Remove a widget from the collection. Chainable.
     *
     * @param  string $key The reference to the object in the collection
     * @return this
     */

    public function removeWidget($key);

    /**
     * Fetch a widget from the collection.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function getWidget($key);

    /**
     * Render the content as a bunch of XML elements.
     *
     * @return string HTML
     */

    public function render();
}

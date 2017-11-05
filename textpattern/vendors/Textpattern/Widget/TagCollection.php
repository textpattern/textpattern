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
 * A collection of widgets.
 *
 * @since   4.7.0
 * @package Widget
 */

namespace Textpattern\Widget;

class TagCollection implements \IteratorAggregate, \Textpattern\Widget\WidgetCollectionInterface
{
    /**
     * The object store for each widget.
     *
     * @var array
     */

    protected $items = array();

    /**
     * General constructor for the collection.
     */

    public function __construct($widget, $key = null)
    {
        $this->addWidget($widget, $key);
    }

    /**
     * Add a widget to the collection. Chainable.
     *
     * @param  object $widget The widget
     * @param  string $key    Optional reference to the object in the collection
     * @return this
     */

    public function addWidget($widget, $key = null)
    {
        if ($key === null) {
            $this->items[] = $widget;
        } else {
            $this->items[$key] = $widget;
        }

        return $this;
    }

    /**
     * Remove a widget from the collection. Chainable.
     *
     * @param  string $key The reference to the object in the collection
     * @return this
     */

    public function removeWidget($key)
    {
        if ($this->keyExists($key)) {
            unset($this->items[$key]);
        }

        return $this;
    }

    /**
     * Fetch a widget from the collection.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function getWidget($key)
    {
        if ($this->keyExists($key)) {
            return $this->items[$key];
        }
    }

    /**
     * Fetch the list of keys in use.
     */

    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * Fetch the number of items in the collection.
     */
    public function length()
    {
        return count($this->items);
    }

    /**
     * Check if the given key exists in the collection.
     *
     * @param  string $key The reference to the object in the collection
     */
    public function keyExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Render the content as a bunch of XML elements.
     *
     * @return string HTML
     */

    public function render()
    {
        $out = array();

        foreach ($this->items as $widget) {
            $out[] = $widget->render();
        }

        return join(n, $out);
    }

    /**
     * Magic method that prints the tag with default options.
     * 
     * @return string HTML
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * IteratorAggregate interface.
     *
     * @return ArrayIterator
     * @see    IteratorAggregate
     */

    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }
}

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
 * A collection of HTML tags.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class TagCollection implements \IteratorAggregate, UICollectionInterface
{
    /**
     * The object store for each field.
     *
     * @var array
     */

    protected $items = array();

    /**
     * The collection properties, keyed via their index.
     *
     * @var array
     */

    protected $properties = array();

    /**
     * General constructor for the collection.
     */

    public function __construct($item = null, $key = null)
    {
        if (!empty($item)) {
            $this->add($item, $key);
        }
    }

    /**
     * Add a tag to the collection. Chainable.
     *
     * @param  object $tag The tag
     * @param  string $key Optional reference to the object in the collection
     * @return this
     */

    public function add($tag, $key = null)
    {
        if ($key === null) {
            $this->items[] = $tag;
        } else {
            $this->items[$key] = $tag;
        }

        return $this;
    }

    /**
     * Remove a tag from the collection. Chainable.
     *
     * @param  string $key The reference to the object in the collection
     * @return this
     */

    public function remove($key)
    {
        if ($this->keyExists($key)) {
            unset($this->items[$key]);
        }

        return $this;
    }

    /**
     * Fetch/find a tag from the collection.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function get($key)
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
     * Define one or more local properties for the collection. Chainable.
     *
     * @param string|array $prop  The name of the property to set or an array of property=>value pairs
     * @param string|null  $value The value of the property
     */

    public function setProperty($prop, $value)
    {
        if (!is_array($prop)) {
            $prop = array($prop => $value);
        }

        foreach ($prop as $key => $val) {
            $this->properties[$key] = $val;
        }

        return $this;
    }

    /**
     * Set the break string to use after the tag has been output. Chainable.
     *
     * @param string $break The break tag to use
     */

    public function setBreak($break = br)
    {
        $this->setProperty('break', $break);

        return $this;
    }

    /**
     * Render the content as a bunch of XML elements.
     *
     * @return string HTML
     */

    public function render()
    {
        $out = array();

        $break = (array_key_exists('break', $this->properties)) ? $this->properties['break'] : '';

        foreach ($this->items as $item) {
            if (is_object($item)) {
                $out[] = $item->render();
            } else {
                $out[] = $item;
            }
        }

        return join(n, $out).$break;
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

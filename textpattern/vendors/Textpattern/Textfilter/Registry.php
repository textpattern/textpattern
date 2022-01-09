<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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

namespace Textpattern\Textfilter;

/**
 * A registry of Textfilters interfaces those to the core.
 *
 * @since   4.6.0
 * @package Textfilter
 */

class Registry implements \ArrayAccess, \IteratorAggregate, \Textpattern\Container\ReusableInterface
{
    /**
     * An array of filters.
     *
     * @var array
     */

    protected $filters;

    /**
     * Stores an array of filter titles.
     *
     * @var array
     */

    protected $titles;

    /**
     * Constructor.
     *
     * Creates core Textfilters according to a preference and registers all
     * available filters with the core.
     *
     * This method triggers 'textfilter.register' callback
     * event.
     */

    public function __construct()
    {
        if ($filters = get_pref('admin_textfilter_classes')) {
            foreach (do_list_unique($filters) as $filter) {
                $filter[0] == '\\' or $filter = __NAMESPACE__.'\\'.$filter;

                if (class_exists($filter)) {
                    new $filter;
                }
            }
        } else {
            new Plain();
            new Nl2Br();
            new Textile();
        }

        $this->filters = array();
        callback_event('textfilter', 'register', 0, $this);
    }

    /**
     * Gets an array map of filter keys vs. titles.
     *
     * @return array Map of 'key' => 'title' for all Textfilters
     */

    public function getMap()
    {
        if ($this->titles === null) {
            $this->titles = array();

            foreach ($this as $filter) {
                $this->titles[$filter->getKey()] = $filter->title;
            }
        }

        return $this->titles;
    }

    /**
     * Filter raw input text by calling one of our known Textfilters by its key.
     *
     * Invokes the 'textfilter.filter' pre- and post-callbacks.
     *
     * @param  string $key     The Textfilter's key
     * @param  string $thing   Raw input text
     * @param  array  $context Filter context ('options' => array, 'field' => string, 'data' => mixed)
     * @return string Filtered output text
     * @throws Exception
     */

    public function filter($key, $thing, $context)
    {
        // Preprocessing, anyone?
        callback_event_ref('textfilter', 'filter', 0, $thing, $context);

        if (isset($this[$key])) {
            $thing = $this[$key]->filter($thing, $context['options']);
        } else {
            throw new \Exception(gTxt('invalid_argument', array('{name}' => 'key')));
        }

        // Postprocessing, anyone?
        callback_event_ref('textfilter', 'filter', 1, $thing, $context);

        return $thing;
    }

    /**
     * Get help URL for a certain Textfilter.
     *
     * @param  string $key The Textfilter's key
     * @return string URL endpoint for human-readable help
     */

    public function getHelp($key)
    {
        if (isset($this[$key])) {
            return $this[$key]->getHelp();
        }

        return '';
    }

    /**
     * ArrayAccess interface to our set of filters.
     *
     * @param string $key
     * @param string $filter
     * @see   ArrayAccess
     */

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $filter)
    {
        if ($key === null) {
            $key = $filter->getKey();
        }

        $this->filters[$key] = $filter;
    }

    /**
     * Returns the value at specified offset.
     *
     * @param  string $key
     * @return string The value
     * @see    ArrayAccess
     */

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if ($this->offsetExists($key)) {
            return $this->filters[$key];
        }

        return null;
    }

    /**
     * Whether an offset exists.
     *
     * @param  string $key
     * @return bool
     * @see    ArrayAccess
     */

    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return isset($this->filters[$key]);
    }

    /**
     * Offset to unset.
     *
     * @param string $key
     * @see   ArrayAccess
     */

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->filters[$key]);
    }

    /**
     * IteratorAggregate interface.
     *
     * @return ArrayIterator
     * @see    IteratorAggregate
     */

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->filters);
    }
}

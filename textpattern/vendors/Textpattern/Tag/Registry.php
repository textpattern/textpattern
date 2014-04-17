<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2014 The Textpattern Development Team
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Handles template tag registry.
 *
 * @since   4.6.0
 * @package Tag
 */

class Textpattern_Tag_Registry implements Textpattern_Container_ReusableInterface
{
    /**
     * Stores registered tags.
     *
     * @var array
     */

    private $tags = array();

    /**
     * Registers a tag.
     *
     * @param  callback    $callback The tag callback
     * @param  string|null $tag      The tag name
     * @return Textpattern_Tag_Registry
     * @example
     * Txp::get('Textpattern_Tag_Registry')->register(array('class', 'method'), 'tag');
     */

    public function register($callback, $tag = null)
    {
        if (is_callable($callback, true)) {
            if ($tag === null && is_string($callback)) {
                $tag = $callback;
            }

            if ($tag) {
                $this->tags[$tag] = $callback;
            }
        }

        return $this;
    }

    /**
     * Processes a tag by name.
     *
     * @param  string      $tag   The tag
     * @param  array       $atts  An array of Attributes
     * @param  string|null $thing The contained statement
     * @return string|null The tag's results
     */

    public function process($tag, $atts, $thing)
    {
        if ($this->isRegistered($tag)) {
            return call_user_func($this->tags[$tag], $atts, $thing);
        }
    }

    /**
     * Checks if a tag is registered.
     *
     * @param  string $tag The tag
     * @return bool   TRUE if a tag exists
     */

    public function isRegistered($tag)
    {
        return array_key_exists($tag, $this->tags) && is_callable($this->tags[$tag]);
    }

    /**
     * Lists registered tags.
     *
     * @return array
     */

    public function getRegistered()
    {
        return $this->tags;
    }
}

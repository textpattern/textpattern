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

/**
 * Handles template tag registry.
 *
 * @since   4.6.0
 * @package Tag
 */

namespace Textpattern\Tag;

class Registry implements \Textpattern\Container\ReusableInterface
{
    /**
     * Stores registered tags and attributes.
     *
     * @var array
     */

    private $tags = array();
    private $atts = array();
    private $params = array();
    private $attr = array();

    /**
     * Registers a tag.
     *
     * <code>
     * Txp::get('\Textpattern\Tag\Registry')->register(array('class', 'method'), 'tag');
     * </code>
     *
     * @param  callback    $callback The tag callback
     * @param  string|null $tag      The tag name
     * @return \Textpattern\Tag\Registry
     */

    public function register($callback, $tag = null)
    {
        // is_callable only checks syntax here to avoid autoloading
        if (is_callable($callback, true)) {
            if ($tag === null && is_string($callback)) {
                $tag = $callback;
            } elseif (is_array($tag)) {
                list($tag, $atts) = $tag + array(null, null);
            }

            if ($tag) {
                $this->tags[$tag] = $callback;
                $params = array_slice(func_get_args(), 2);

                if (!empty($params)) {
                    $this->params[$tag] = $params;
                }

                if (isset($atts)) {
                    $this->atts[$tag] = (array)$atts;
                }
            }
        }

        return $this;
    }

    /**
     * Registers an attribute.
     *
     * <code>
     * Txp::get('\Textpattern\Tag\Registry')->registerAtt(array('class', 'method'), 'tag');
     * </code>
     *
     * @param  callback    $callback The attribute callback
     * @param  string|null $tag      The attribute name
     * @return \Textpattern\Tag\Registry
     */

    public function registerAttr($callback, $tag = null)
    {
        // is_callable only checks syntax here to avoid autoloading
        if (is_bool($callback)) {
            foreach (do_list_unique($tag) as $tag) {
                $this->attr[$tag] = $callback;
            }
        } elseif ($callback && is_callable($callback, true)) {
            if ($tag === null && is_string($callback)) {
                $this->attr[$callback] = $callback;
            } else {
                foreach (do_list_unique($tag) as $tag) {
                    $this->attr[$tag] = $callback;
                }
            }
        }

        return $this;
    }

    /**
     * Processes a tag by name.
     *
     * @param  string      $tag   The tag
     * @param  array|null  $atts  An array of Attributes
     * @param  string|null $thing The contained statement
     * @return string|bool The tag's results (string) or FALSE on unknown tags
     */

    public function process($tag, array $atts = null, $thing = null)
    {
        if ($this->isRegistered($tag)) {
            $atts = (array)$atts;

            if (isset($this->atts[$tag])) {
                $atts += $this->atts[$tag];
            }

            try {
                //TODO: switch to args unpacking for php 5.6+
                return isset($this->params[$tag]) ?
    //                (string) call_user_func($this->tags[$tag], (array)$atts, $thing, ...$this->params[$tag]) :
                    (string) call_user_func_array($this->tags[$tag], array_merge(array($atts, $thing), $this->params[$tag])) :
                    (string) call_user_func($this->tags[$tag], $atts, $thing);
            } catch (\Exception $e) {
                trigger_error($e->getMessage());
            }
        } else {
            return false;
        }
    }

    /**
     * Processes an attribute by name.
     *
     * @param  string      $tag   The attribute
     * @param  string|null $atts  The value of attribute
     * @param  string|null $thing The processed statement
     * @return string|bool The tag's results (string) or FALSE on unknown tags
     */

    public function processAttr($tag, $atts = null, $thing = null)
    {
        if ($this->isRegisteredAttr($tag)) {
            return (string) call_user_func($this->attr[$tag], $atts, $thing);
        } else {
            return false;
        }
    }

    /**
     * Checks if a tag is registered.
     *
     * @param  string $tag The tag
     * @return bool TRUE if the tag exists
     */

    public function isRegistered($tag)
    {
        return isset($this->tags[$tag]) && is_callable($this->tags[$tag]);
    }

    /**
     * Checks if an attribute is registered.
     *
     * @param  string $tag The tag
     * @return bool TRUE if the tag exists
     */

    public function isRegisteredAttr($tag)
    {
        return isset($this->attr[$tag]) && is_callable($this->attr[$tag]);
    }

    /**
     * Lists registered tags.
     *
     * @param  bool $is_attr tag or attr?
     * @return array
     */

    public function getRegistered($is_attr = false)
    {
        return $is_attr ? $this->attr : $this->tags;
    }

    /**
     * Tags getter.
     *
     * @param  string $tag
     * @return callable
     */

    public function getTag($tag)
    {
        return $this->isRegistered($tag) ? $this->tags[$tag] : false;
    }
}

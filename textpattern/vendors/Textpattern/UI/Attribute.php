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
 * One or more tag attributes.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Attribute implements \IteratorAggregate
{
    /**
     * The attribute value(s), keyed via their index.
     *
     * @var array
     */

    protected $values = array();

    /**
     * The attribute properties, keyed via their index.
     *
     * @var array
     */

    protected $properties = array();

    /**
     * Default attribute properties.
     *
     * Array options:
     *   format:   The data type of the attribute to cast (string, int, ...)
     *   flag:     How to treat the value if empty, undefined, etc
     *
     * @var array
     */

    protected $defaultProperties = array(
        'format'   => 'string',
        'strip'    => TEXTPATTERN_STRIP_EMPTY,
    );

    /**
     * General constructor for the attribute.
     *
     * @param  string $key   The attribute identifier
     * @param  string $value The attribute value
     * @param  string $props The attribute properties, such as format and flag
     */

    public function __construct($key = null, $value = null, $props = array())
    {
        $this->setAttribute($key, $value, $props);
    }

    /**
     * Set the attribute parameters. Chainable.
     *
     * @param  string $key   The attribute identifier
     * @param  string $value The attribute value
     * @param  string $props The attribute properties, such as format and flag
     * @return this
     */

    public function setAttribute($key, $value = null, $props = array())
    {
        if ($key) {
            if ($value === true) {
                $props['format'] = 'bool';
            }

            $this->setValue($key, $value);
            $this->setProperty($key, $props);
        }

        return $this;
    }

    /**
     * Set an attribute value. Chainable.
     *
     * @param  string $key   The attribute identifier
     * @param  string $value The attribute value
     * @return this
     */

    public function setValue($key, $value = null)
    {
        $this->values[(string)$key] = $value;

        return $this;
    }

    /**
     * Set the given property(ies), merging them with what's stored already,
     * or the defaults. Chainable.
     *
     * @param  string $key   The attribute identifier
     * @param  string|array  $prop  Property key, or name-value properties
     * @param  string        $value Property value
     * @return this
     */

    public function setProperty($key, $prop, $value = null)
    {
        $key = (string)$key;

        if (array_key_exists($key, $this->values)) {
            // Use default properties if none supplied.
            if (isset($this->properties[$key])) {
                $base = $this->properties[$key];
            } else {
                $base = $this->defaultProperties;
            }

            // Possibly overwrite values by supplying existing array 2nd.
            if ($value === null) {
                if (is_array($prop)) {
                    $base = $prop + $base;
                }
            } else {
                $base = array($key => $value) + $base;
            }

            $this->properties[$key] = $base;
        }

        return $this;
    }

    /**
     * Fetch an attribute value by its key. Chainable.
     *
     * @param  string $key The attribute identifier
     * @return mixed
     */

    public function getValue($key)
    {
        $key = (string)$key;
        $val = null;

        if (array_key_exists($key, $this->values)) {
            $val = $this->values[$key];
        }

        return $val;
    }

    /**
     * Permit multiple values to be sent by the tag. Chainable.
     *
     * @param string $flavour The type of mulitple to assign: 'all', 'name', or 'attribute'
     */

    public function setMultiple($flavour = 'all')
    {
        $addAttr = (in_array($flavour, array('all', 'attribute')));
        $addName = (in_array($flavour, array('all', 'name')));

        // Add the 'multiple' boolean attribute.
        if ($addAttr) {
            $this->setAttribute('multiple', true, array(
                'format' => 'bool',
                'strip'  => TEXTPATTERN_STRIP_TXP,
            ));
        }

        if ($addName) {
            $val = $this->getValue('name');

            // Append square brackets to the 'name' field if not already done.
            if ($val !== null) {
                if (strpos($val, '[]') === false) {
                    $val .= '[]';
                    $this->setValue('name', $val);
                }
            }
        }

        return $this;
    }

    /**
     * Return the given attribute(s) as name="value" pairs according to their defined properties.
     *
     * @return string HTML represdentation
     */

    public function render()
    {
        $out = array();

        foreach ($this->values as $key => $value) {
            $props = $this->properties[$key];
            $type = (empty($props['format'])) ? $this->defaultProperties['format'] : $props['format'];
            $flag = (is_numeric($props['strip'])) ? $props['strip'] : $this->defaultProperties['strip'];

            switch ($type) {
                case 'bool':
                    $out[$flag][$key] = true;
                    break;
                case 'number':
                    $out[$flag][$key] = (int)$value;
                    break;
                case 'string':
                default:
                    $out[$flag][$key] = (string)$value;
                    break;
            }
        }

        $final = array();

        foreach ($out as $flag => $atts) {
            $final[] = join_atts($atts, $flag);
        }

        return implode('', $final);
    }

    /**
     * Magic method that prints the attribute set.
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

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->values);
    }
}

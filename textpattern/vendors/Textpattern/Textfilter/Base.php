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
 * Textfilter base class.
 *
 * @since   4.6.0
 * @package Textfilter
 */

namespace Textpattern\Textfilter;

class Base implements TextfilterInterface
{
    /**
     * The filter's title.
     *
     * @var string
     */

    public $title;

    /**
     * The filter's version.
     *
     * @var string
     */

    public $version;

    /**
     * The filter's identifier.
     *
     * @var string
     */

    protected $key;

    /**
     * The filter's options.
     *
     * @var array
     */

    protected $options;

    /**
     * General constructor for Textfilters.
     *
     * @param string $key   A globally unique, persistable identifier for this particular Textfilter class
     * @param string $title The human-readable title of this filter class
     */

    public function __construct($key, $title)
    {
        global $txpversion;

        $this->key = $key;
        $this->title = $title;
        $this->version = $txpversion;
        $this->options = array(
            'lite'       => false,
            'restricted' => false,
            'rel'        => '',
            'noimage'    => false,
        );

        register_callback(array($this, 'register'), 'textfilter', 'register');
    }

    /**
     * Sets filter's options.
     *
     * @param array $options Array of options: 'lite' => boolean, 'rel' => string, 'noimage' => boolean, 'restricted' => boolean
     */

    private function setOptions($options)
    {
        $this->options = lAtts(array(
            'lite'       => false,
            'restricted' => false,
            'rel'        => '',
            'noimage'    => false,
        ), $options);
    }

    /**
     * Event handler, registers Textfilter class with the core.
     *
     * @param string                           $step     Not used
     * @param string                           $event    Not used
     * @param \Textpattern\Textfilter\Registry $registry Maintains the set of known Textfilters
     */

    public function register($step, $event, $registry)
    {
        $registry[] = $this;
    }

    /**
     * Filters the given raw input value.
     *
     * @param  string $thing   The raw input string
     * @param  array  $options Options
     * @return string Filtered output text
     */

    public function filter($thing, $options)
    {
        $this->setOptions($options);

        return $thing;
    }

    /**
     * Gets this filter's help URL.
     *
     * @return string
     */

    public function getHelp()
    {
        return '';
    }

    /**
     * Gets this filter's identifier.
     *
     * @return string
     */

    public function getKey()
    {
        return $this->key;
    }
}

<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2018 The Textpattern Development Team
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
 * A collection of data type mappings for custom fields.
 *
 * Each entry is a set of properties that define the way
 * the data type is represented in Textpattern.
 * 
 * @since   4.8.0
 * @package CustomField
 */

namespace Textpattern\Meta;

class DataType implements \IteratorAggregate
{
    /**
     * Default data field map. May be altered by plugins.
     *
     * Each entry comprises:
     *  type       : (string) database field type
     *  size       : (int) database field size (width). null = system defined
     *  textfilter : (bool) whether the field can accept Textfiltered content
     *  options    : (bool) whether the field is made of a set of options (e.g. select list)
     *
     * @var array
     * @todo Could (should?) be an array of DataType objects.
     */
    protected $dataTypeMap = array(
        'yesNoRadio' => array(
            'type'       => 'tinyint',
            'size'       => 2,
            'textfilter' => false,
            'options'    => false,
        ),
        'onOffRadio' => array(
            'type'       => 'tinyint',
            'size'       => 2,
            'textfilter' => false,
            'options'    => false,
        ),
        'radioSet' => array(
            'type'       => 'tinyint',
            'size'       => 2,
            'textfilter' => false,
            'options'    => true,
        ),
        'checkbox' => array(
            'type'       => 'tinyint',
            'size'       => 2,
            'textfilter' => false,
            'options'    => false,
        ),
        'checkboxSet' => array(
            'type'       => 'varchar',
            'size'       => 255,
            'textfilter' => false,
            'options'    => true,
        ),
        'number' => array(
            'type'       => 'int',
            'size'       => 12,
            'textfilter' => false,
            'options'    => false,
        ),
        'text_input' => array(
            'type'       => 'varchar',
            'size'       => 255,
            'textfilter' => true,
            'options'    => false,
        ),
        'text_area' => array(
            'type'       => 'text',
            'size'       => null,
            'textfilter' => true,
            'options'    => false,
        ),
        'selectInput' => array(
            'type'       => 'varchar',
            'size'       => 255,
            'textfilter' => true,
            'options'    => true,
        ),
        'multi-select' => array(
            'type'       => 'varchar',
            'size'       => 255,
            'textfilter' => true,
            'options'    => true,
        ),
        'dateTime' => array(
            'type'       => 'datetime',
            'size'       => null,
            'textfilter' => false,
            'options'    => false,
        ),
    );

    /**
     * General constructor for the map.
     *
     * The map can be extended with a 'txp.meta > data.types' callback event.
     * Callback functions get passed three arguments: '$event', '$step' and
     * '$meta_list'. The third parameter contains a reference to an array of
     * 'html_renderer => data_type' attributes.
     *
     * @param array List of meta data keys to exclude
     */

    public function __construct($exclude = array())
    {
        $map = $this->get($exclude);

        callback_event_ref('txp.meta', 'data.types', 0, $map);
    }

    /**
     * Return meta data field types and their associated properties.
     *
     * @param  string|array List of meta data keys to exclude
     * @return array        A meta data types map array
     */

    public function get($exclude = array())
    {
        $map = $this->dataTypeMap;

        if (!is_array($exclude)) {
            $exclude = do_list($exclude);
        }

        foreach ($exclude as $remove) {
            unset($map[(string)$remove]);
        }

        return $map;
    }

    /**
     * IteratorAggregate interface.
     *
     * @return ArrayIterator
     * @see    IteratorAggregate
     */

    public function getIterator()
    {
        return new \ArrayIterator($this->dataTypeMap);
    }
}

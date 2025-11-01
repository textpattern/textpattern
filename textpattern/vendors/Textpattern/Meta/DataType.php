<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * A collection of data type mappings for custom fields.
 *
 * Each entry is a set of properties that define the way
 * the data type is represented in Textpattern.
 *
 * @since   5.0.0
 * @package CustomField
 */

namespace Textpattern\Meta;

class DataType implements \IteratorAggregate
{
    /**
     * Default data field map. May be altered by plugins.
     *
     * Each entry comprises:
     *  type        : (string) database field type
     *  size        : (int) database field size (width). null = system defined
     *  textfilter  : (bool) whether the field can accept Textfiltered content
     *  options     : (bool) whether the field is made of a set of options (e.g. select list)
     *  delimited   : (bool) whether the field content requires a delimiter between stored values
     *  constraints : (array) The constraint object names that apply to this data type
     *
     * @var array
     * @todo Could (should?) be an array of DataType objects.
     */
    protected $dataTypeMap = array(
        'checkbox' => array(
            'type'        => 'tinyint',
            'size'        => 2,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => array('MinSelected'),
        ),
        'checkboxSet' => array(
            'type'        => 'varchar',
            'size'        => 255,
            'textfilter'  => false,
            'options'     => true,
            'delimited'   => true,
            'constraints' => array('MinSelected', 'MaxSelected'),
        ),
        'date' => array(
            'type'        => 'date',
            'size'        => null,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => array('Min', 'Max'),
        ),
        'dateTime' => array(
            'type'        => 'datetime',
            'size'        => null,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => array('Min', 'Max'),
        ),
        'multiSelect' => array(
            'type'        => 'varchar',
            'size'        => 255,
            'textfilter'  => true,
            'options'     => true,
            'delimited'   => true,
            'constraints' => array('MinSelected', 'MaxSelected'),
        ),
        'number' => array(
            'type'        => 'int',
            'size'        => 12,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => array('Min', 'Max', 'Step'),
        ),
        'onOffRadio' => array(
            'type'        => 'tinyint',
            'size'        => 2,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => false,
        ),
        'radioSet' => array(
            'type'        => 'tinyint',
            'size'        => 2,
            'textfilter'  => false,
            'options'     => true,
            'delimited'   => false,
            'constraints' => false,
        ),
        'range' => array(
            'type'        => 'int',
            'size'        => 12,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => array('Min', 'Max', 'Step'),
        ),
        'selectInput' => array(
            'type'        => 'varchar',
            'size'        => 255,
            'textfilter'  => true,
            'options'     => true,
            'delimited'   => false,
            'constraints' => array('MinSelected'),
        ),
        'textArea' => array(
            'type'        => 'text',
            'size'        => null,
            'textfilter'  => true,
            'options'     => false,
            'delimited'   => false,
            'constraints' => array('MinLength', 'MaxLength'),
        ),
        'textInput' => array(
            'type'        => 'varchar',
            'size'        => 7500,
            'textfilter'  => true,
            'options'     => false,
            'delimited'   => false,
            'constraints' => array('MinLength', 'MaxLength'),
        ),
        'time' => array(
            'type'        => 'time',
            'size'        => null,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => array('Min', 'Max'),
        ),
        'yesNoRadio' => array(
            'type'        => 'tinyint',
            'size'        => 2,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => false,
        ),
        'virtual' => array(
            'type'        => 'varchar',
            'size'        => 7500,
            'textfilter'  => false,
            'options'     => false,
            'delimited'   => false,
            'constraints' => false,
        ),
    );

    /**
     * Data types grouped by other columns.
     *
     * @var array
     */

    protected $typesBy = array(
        'delimited' => array(),
        'options' => array(),
        'textfilter' => array(),
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
     * @param  string|array $exclude List of meta data keys to exclude
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
     * Return meta data field names that match the given column attribute.
     *
     * @param  string       $column  Data type attribute to fetch
     * @param  string|array $exclude List of meta data types to exclude
     * @return array        Data types with the given attribute set true, less exclusions
     */

    public function getBy($column, $exclude = array())
    {
        $out = array();

        if (array_key_exists($column, $this->typesBy)) {
            if (empty($this->typesBy[$column])) {
                $map = $this->dataTypeMap;

                foreach ($map as $key => $data_type) {
                    if ($data_type[$column]) {
                        $this->typesBy[$column][] = $key;
                    }
                }
            }

            $out = $this->typesBy[$column];
            $exclude = do_list($exclude);

            foreach ($exclude as $remove) {
                unset($out[(string)$remove]);
            }
        }

        return $out;
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
        return new \ArrayIterator($this->dataTypeMap);
    }
}

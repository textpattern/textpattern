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
 * A collection of custom fields.
 *
 * Whereas a field's name can be reused between content types, each field
 * has a custom_N id, which is unique across ALL types.
 *
 * @since   5.0.0
 * @package CustomField
 */

namespace Textpattern\Meta;

class FieldSet implements \IteratorAggregate
{
    /**
     * Collection of Meta_Field entities by content type.
     *
     * @var array
     */

    protected $collection = array();

    /**
     * Filtered collection of Meta_Field entities by content type.
     *
     * @var array
     */

    protected $filterCollection = array();

    /**
     * Constructor for the field set.
     *
     * @param string $type Content type to load
     */

    public function __construct($type = 'article')
    {
        $type = (string)$type;

        // Haz cache?
        if (!isset($this->collection[$type])) {
            $cfs = safe_rows(
                "id,
                name,
                content_type,
                data_type,
                render,
                family,
                textfilter,
                delimiter,
                ordinal,
                created,
                modified,
                expires",
                'txp_meta',
                "content_type = '".doSlash($type)."'
                ORDER BY family, ordinal"
            );

            if (!isset($this->collection[$type])) {
                $this->collection[$type] = array();
            }

            foreach ($cfs as $def) {
                $this->collection[$type][$def['id']] = new Field($def);
            }

            $this->filterCollection = $this->collection[$type];
        }
    }

    /**
     * Set the given collection indexed by the given property. Chainable.
     *
     * @param  string $type Content type
     * @param  string $by   The key by which to index the collection (id, name, field)
     * @return array
     */

    public function filterCollection($type, $by = null)
    {
        if (isset($this->collection[$type])) {
            $this->filterCollection = array();

            if ($by === null) {
                $this->filterCollection = $this->collection[$type];
            } else {
                switch ($by) {
                    case 'id':
                        foreach ($this->collection[$type] as $idx => $def) {
                            $this->filterCollection[$def->get('id')] = $def;
                        }

                        break;
                    case 'name':
                        foreach ($this->collection[$type] as $idx => $def) {
                            $this->filterCollection[$def->get('name')] = $def;
                        }

                        break;
                    case 'field':
                        foreach ($this->collection[$type] as $idx => $def) {
                            $this->filterCollection['custom_' . $def->get('id')] = $def;
                        }

                        break;
                }

            }
        }

        return $this;
    }

    /**
     * Filter the given collection items that are still "in date" at a specified timestamp. Chainable.
     *
     * @param  string $type Content type
     * @param  int    $when UNIX timestamp cutoff point at which the field becomes invalid
     * @return array
     */

    public function filterCollectionAt($type, $when = null)
    {
        global $txpnow;

        if ($when === null) {
            $when = $txpnow;
        }

        assert_int($when);

        $this->filterCollection = array();

        foreach ($this->collection[$type] as $idx => $def) {
            $createStamp = safe_strtotime($def->get('created'));
            $expires = $def->get('expires');
            $expireStamp = empty($expires) ? 0 : safe_strtotime($expires);

            if ($when >= $createStamp && (empty($expireStamp) || $when <= $expireStamp)) {
                $this->filterCollection[$def->get('id')] = $def;
            }
        }

        return $this;
    }


    /**
     * Stash the value of each field in the collection. Chainable.
     */

    public function store($varray, $contentType, $contentId, $all = false)
    {
        assert_int($contentId);

        $cfq = array();

        if (isset($this->collection[$contentType])) {
            foreach ($this->collection[$contentType] as $id => $def) {
                $cf_type = $def->get('data_type');
                $custom_x = "custom_{$id}";
                $raw = array();

                if (isset($varray[$custom_x])) {
                    if (is_array($varray[$custom_x])) {
                        foreach($varray[$custom_x] as $cf_value) {
                            $raw[] = $cf_value;
                        }
                    } else {
                        $raw[] = $varray[$custom_x];
                    }
                } else {
                    $raw[] = '';
                }

                $cooked = null;

                $filter = $def->get('textfilter');

                foreach ($raw as $rawVal) {
                    if ($filter === null) {
                        $cfq[$cf_type][$id][] = "value = " . ($rawVal === '' ? 'NULL' : "'" . doSlash($rawVal) . "'");
                    } else {
                        $cooked = \Txp::get('Textpattern\Textfilter\Registry')->filter(
                            $filter,
                            $rawVal,
                            array('field' => $custom_x, 'options' => array('lite' => false), 'data' => array())
                        );

                        $cfq[$cf_type][$id][] = "value_raw = '" . doSlash($rawVal) . "', value = '" . doSlash($cooked) . "'";
                    }
                }
            }
        }

        // Store the values in the appropriate custom field table based on its data type.
        foreach ($cfq as $tableType => $data) {
            foreach ($data as $metaId => $content) {
                $tableName = 'txp_meta_value_'.$tableType;
                safe_delete($tableName, "meta_id = '" . doSlash($metaId) . "' AND content_id = $contentId");

                foreach ($content as $valueId => $set) {
                    if ($all || isset($this->filterCollection[$metaId])) {
                        $set .= ", meta_id = '" . doSlash($metaId) . "', content_id = $contentId, value_id = '" . doSlash($valueId) . "'";
                        safe_insert($tableName, $set);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Reorder the collection by the given attribute. Chainable.
     *
     * @todo
     */

    public function orderBy($property)
    {
        return $this;
    }

    /**
     * Reset any filters back to the full collection. Chainable.
     */

    public function reset($type)
    {
        $this->filterCollection = $this->collection[$type];

        return $this;
    }

    /**
     * Fetch the given item from the collection.
     *
     * @param  string $item The key of the item to retrieve
     */

    public function getItem($item)
    {
        if (isset($this->filterCollection[$item])) {
            return $this->filterCollection[$item];
        }

        return array();
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
        return new \ArrayIterator($this->filterCollection);
    }
}

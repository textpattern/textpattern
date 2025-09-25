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

    protected static $collection = array();

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
        if (!isset(self::$collection[$type])) {
            $stype = doSlash($type);
            $typeids = \Txp::get('Textpattern\Meta\ContentType')->getId(); //safe_field('id', 'txp_meta_entity', "name = '$stype'");
            $typeid = isset($typeids[$type]) ? $typeids[$type] : 0;
            self::$collection[$type] = array();

            if ($typeid and $cfs = getRows("SELECT
                m.id,
                m.name,
                '$stype' AS content_type,
                m.data_type,
                m.render,
                m.family,
                m.textfilter,
                m.delimiter,
                m.created,
                m.modified,
                m.expires
                FROM ".PFX."txp_meta m JOIN ".PFX."txp_meta_fieldsets fs ON fs.meta_id = m.id
                WHERE fs.type_id = $typeid
                ORDER BY m.family, fs.ordinal")
            ) {
                foreach ($cfs as $def) {
                    self::$collection[$type][$def['id']] = new Field($def, $typeid);
                }
            }

            $this->filterCollection = self::$collection[$type];
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
        if (isset(self::$collection[$type])) {
            $this->filterCollection = array();

            if ($by === null) {
                $this->filterCollection = self::$collection[$type];
            } else {
                switch ($by) {
                    case 'id':
                        foreach (self::$collection[$type] as $idx => $def) {
                            $this->filterCollection[$def->get('id')] = $def;
                        }

                        break;
                    case 'name':
                        foreach (self::$collection[$type] as $idx => $def) {
                            $this->filterCollection[$def->get('name')] = $def;
                        }

                        break;
                    case 'field':
                        foreach (self::$collection[$type] as $idx => $def) {
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

        foreach (self::$collection[$type] as $idx => $def) {
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

        $ids = \Txp::get('Textpattern\Meta\ContentType')->getId();
        $typeId = isset($ids[$contentType]) ? $ids[$contentType] : 0;
        $cfq = array();

        if ($typeId && isset(self::$collection[$contentType])) {
            foreach (self::$collection[$contentType] as $id => $def) {
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
                $safeMetaId = doSlash($metaId);
                safe_delete($tableName, "type_id = $typeId AND meta_id = '$safeMetaId' AND content_id = $contentId");

                if ($all || isset($this->filterCollection[$metaId])) {
                    foreach ($content as $valueId => $set) {
                        $set .= ", type_id = $typeId, meta_id = '$safeMetaId', content_id = $contentId, value_id = '" . doSlash($valueId) . "'";
                        safe_insert($tableName, $set);
                    }
                }
            }
        }

        safe_upsert('txp_meta_registry', array('content_id' => $contentId, 'type_id' => $typeId), array('content_id' => $contentId, 'type_id' => $typeId));

        return $this;
    }

    /**
     * Delete the value of each field in the collection. Chainable.
     */

    public function delete($contentType, $contentId)
    {
        assert_int($contentId);

        $ids = \Txp::get('Textpattern\Meta\ContentType')->getId();
        $typeId = isset($ids[$contentType]) ? $ids[$contentType] : 0;
        $cfq = array();

        if ($typeId && isset(self::$collection[$contentType])) {
            foreach (self::$collection[$contentType] as $id => $def) {
                $cfq[$def->get('data_type')] = $id;
            }

            // Delete the values of the appropriate custom field table based on its data type.
            foreach ($cfq as $tableType => $metaId) {
                $tableName = 'txp_meta_value_'.$tableType;
                safe_delete($tableName, "type_id = $typeId AND content_id = $contentId");
            }

            safe_delete('txp_meta_registry', "content_id = $contentId AND type_id = $typeId");
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
        $this->filterCollection = self::$collection[$type];

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

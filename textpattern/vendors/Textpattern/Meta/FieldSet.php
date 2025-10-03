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
     * @param null|string|int|array $type Content type to load
     */

    public function __construct($type = null, $content_id = null)
    {
        if ($type === null) {
            $types = safe_column('id', 'txp_meta');
        } elseif (is_string($type)) {
            $types = implode(',', \Txp::get('\Textpattern\Meta\ContentType')->getEntities($type));
            $types = $types ? safe_column('meta_id', 'txp_meta_fieldsets', "type_id IN ($types)") : array();
        } elseif (is_int($type)) {
            $type = \Txp::get('\Textpattern\Meta\ContentType')->getEntity($type);
            $types = $type ? safe_column('meta_id', 'txp_meta_fieldsets', "type_id = $type") : array();
        } elseif (is_array($type)) {
            $types = array_combine($type, $type);
        } else {
            $types = array();
        }
        
        $types = array_filter((array)$types);
        $to_fetch = array_diff_key($types, self::$collection);
        is_int($type) or $type = null;

        if ($to_fetch) {
            $to_fetch = implode(',', $to_fetch);

            if ($cfs = getRows("SELECT * FROM ".PFX."txp_meta WHERE id IN ($to_fetch) ORDER BY family")
            ) {
                foreach ($cfs as $def) {
                    self::$collection[$def['id']] = new Field($def);
                }
            }
        }

        $this->filterCollection = array_intersect_key(self::$collection, $types);

        if ($type && isset($content_id) && $content_id = intval($content_id)) {
            foreach (safe_column('meta_id', 'txp_meta_delta', "content_id = $content_id AND type_id = $type") as $meta_id) {
                if ($meta_id < 0) {
                    unset($this->filterCollection[-$meta_id]);
                } elseif ($meta_id > 0 and $cfs = getRows("SELECT * FROM ".PFX."txp_meta WHERE id = $meta_id ORDER BY family")) {
                    foreach ($cfs as $def) if (!isset($this->filterCollection[$def['id']])) {
                        $this->filterCollection[$def['id']] = new Field($def);
                    }
                }
            }
        }
    }

    /**
     * Set the given collection indexed by the given property. Chainable.
     *
     * @param  string $type Content type
     * @param  string $by   The key by which to index the collection (id, name, field)
     * @return array
     */

    public function filterCollection($by = null, $type = null)
    {
        if ($type === null) {
            $collection = $by === null ? null : $this->filterCollection;
        } else {
            $type = \Txp::get('\Textpattern\Meta\ContentType')->getEntity($type);
            $collection = isset(self::$collection[$type]) ? self::$collection[$type] : null;
        }

        if (isset($collection)) {
            if ($by === null) {
                $this->filterCollection = $collection;
            } else {
                $this->filterCollection = array();

                switch ($by) {
                    case 'id':
                        foreach ($collection as $idx => $def) {
                            $this->filterCollection[$def->get('id')] = $def;
                        }

                        break;
                    case 'name':
                        foreach ($collection as $idx => $def) {
                            $this->filterCollection[$def->get('name')] = $def;
                        }

                        break;
                    case 'field':
                        foreach ($collection as $idx => $def) {
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

    public function filterCollectionAt($when = null, $type = null)
    {
        global $txpnow;

        if ($type !== null) {
            $type = \Txp::get('\Textpattern\Meta\ContentType')->getEntity($type);
            $this->filterCollection = (isset(self::$collection[$type]) ? self::$collection[$type] : array());
        }

        if ($when === null) {
            $when = $txpnow;
        }

        assert_int($when);

        if (isset($this->filterCollection)) {
            foreach ($this->filterCollection as $idx => $def) {
                $createStamp = safe_strtotime($def->get('created'));
                $expires = $def->get('expires');
                $expireStamp = empty($expires) ? 0 : safe_strtotime($expires);

                if ($when < $createStamp || (!empty($expireStamp) && $when > $expireStamp)) {
                    unset($this->filterCollection[$idx]);
                }
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
        $contentType = \Txp::get('\Textpattern\Meta\ContentType')->getEntity($contentType);
        $cfq = array();
        $out = array();

        if ($contentType) {
            foreach ($this->filterCollection as $id => $def) {
                $cf_type = $def->get('data_type');
                $cf_name = $def->get('name');
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
                        $out[$cf_name] = $rawVal;
                    } else {
                        $cooked = \Txp::get('Textpattern\Textfilter\Registry')->filter(
                            $filter,
                            $rawVal,
                            array('field' => $custom_x, 'options' => array('lite' => false), 'data' => array())
                        );

                        $cfq[$cf_type][$id][] = "value_raw = '" . doSlash($rawVal) . "', value = '" . doSlash($cooked) . "'";
                        $out[$cf_name] = $cooked;
                    }
                }
            }
        }

        if (empty($contentId)) {
            return $out;
        } elseif ($contentType) {
            safe_upsert('txp_meta_registry', array('content_id' => $contentId, 'type_id' => $contentType), array('content_id' => $contentId, 'type_id' => $contentType));
        }

        // Store the values in the appropriate custom field table based on its data type.
        foreach ($cfq as $tableType => $data) {
            foreach ($data as $metaId => $content) {
                $tableName = 'txp_meta_value_'.$tableType;
                safe_delete($tableName, "type_id = $contentType AND meta_id = $metaId AND content_id = $contentId");

                if ($all || isset($this->filterCollection[$metaId])) {
                    foreach ($content as $valueId => $set) {
                        $set .= ", type_id = $contentType, meta_id = $metaId, content_id = $contentId, value_id = '" . doSlash($valueId) . "'";
                        safe_insert($tableName, $set);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Insert fields in the collection. Chainable.
     */

    public function insert($contentType, $contentId = null, $metaId = null)
    {
        return $this;
    }

    /**
     * Delete fields from the collection. Chainable.
     */

    public function delete($contentType, $contentId = null, $metaId = null)
    {
        assert_int($contentType);
        $contentId === null or $contentId = implode(',', array_filter(is_int($contentId) ? array($contentId) : array_map('intval', do_list_unique($contentId))));
        $metaId === null or $metaId = implode(',', array_filter(is_int($metaId) ? array($metaId) : array_map('intval', do_list_unique($metaId))));
        $contentQuery = $contentId ? " AND content_id IN ($contentId)" : '';
        $metaQuery = $metaId ? " AND meta_id IN ($metaId)" : '';
        $cfq = array();

        if ($contentType) {
            foreach ($this->filterCollection as $id => $def) {
                $cfq[$def->get('data_type')] = $id;
            }

            // Delete the values of the appropriate custom field table based on its data type.
            foreach ($cfq as $tableType => $id) {
                $tableName = 'txp_meta_value_'.$tableType;
                safe_delete($tableName, "type_id = {$contentType}{$contentQuery}{$metaQuery}");
            }

            if (empty($metaQuery)) {
                safe_delete('txp_meta_registry', "type_id = {$contentType}{$contentQuery}");
            } else {
                if ($contentId === null) {
                    safe_delete('txp_meta_fieldsets', "type_id = {$contentType}{$metaQuery}");
                    $metaQuery = $metaId ? " AND -meta_id IN ($metaId)" : '';
                } else {
                    $metaQuery = $metaId ? " AND ABS(meta_id) IN ($metaId)" : '';
                }
            }

            safe_delete('txp_meta_delta', "type_id = {$contentType}{$contentQuery}{$metaQuery}");
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

    public function getItem($item = null)
    {
        if (!isset($item)) {
            return $this->filterCollection;
        } elseif (isset($this->filterCollection[$item])) {
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

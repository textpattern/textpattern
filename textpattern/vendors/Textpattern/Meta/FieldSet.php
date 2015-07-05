<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2015 The Textpattern Development Team
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
 * @since   4.6.0
 * @package CustomField
 */

class Textpattern_Meta_FieldSet implements IteratorAggregate
{
    /**
     * Collection of Meta_Field entities by content type and timestamp.
     *
     * @var array
     */
    protected static $collection = array();

    /**
     * Constructor for the field set.
     *
     * @param string $type  Content type to load
     * @param string $when  DateTime from which the fields are to be loaded (null = now)
     */

    public function __construct($type = 'article', $when = null)
    {
        $clause = '';

        if ($when === null) {
            assert_int($when);
            $clause = " AND (expires IS NULL OR expires = '" . NULLDATETIME . "' OR expires > '" . doSlash(strftime('%F %T', $when)) ."')";
        }

        // Haz cache?
        if (!isset(self::$collection[$type][$when])) {
            $cfs = safe_rows('
                `id`,
                `name`,
                `content_type`,
                `data_type`,
                `render`,
                `family`,
                `textfilter`,
                `ordinal`,
                `created`,
                `modified`,
                `expires`',
                'txp_meta',
                "content_type = '" . doSlash($type) . "'" . $clause . "
                ORDER BY family, ordinal"
            );

            if (!isset(self::$collection[$type])) {
                self::$collection[$type] = array();
            }

            // @Todo: Fields don't appear on the Write panel in ordinal order as they're
            // indexed by ID here.
            foreach ($cfs as $def) {
                self::$collection[$type][$def['id']] = new Textpattern_Meta_Field($def);
            }
        }
    }

    /**
     * Get the given collection.
     *
     * @param  string $type Content type
     * @param  string $by   The method by which to extract the collection
     *                      (id, name, field, type, callback, textfilter)
     * @return array
     */

    public function getCollection($type, $by = null)
    {
        $out = array();

        if (isset(self::$collection[$type])) {
            if ($by === null) {
                $out = self::$collection[$type];
            } else {
                switch ($by) {
                    case 'id':
                        foreach (self::$collection[$type] as $def) {
                            $out[$type][$def->get('id')] = $def->get('name');
                        }

                        break;
                    case 'name':
                        foreach (self::$collection[$type] as $def) {
                            $out[$type][$def->get('name')] = $def->get('id');
                        }

                        break;
                    case 'field':
                        foreach (self::$collection[$type] as $def) {
                            $out[$type]['custom_' . $def->get('id')] = $def->get('name');
                        }

                        break;
                    case 'type':
                        foreach (self::$collection[$type] as $def) {
                            $out[$type][$def->get('id')] = $def->get('data_type');
                        }

                        break;
                    case 'callback':
                        foreach (self::$collection[$type] as $def) {
                            $out[$type][$def->get('id')] = $def->get('render');
                        }

                        break;
                    case 'textfilter':
                        foreach (self::$collection[$type] as $def) {
                            $out[$type][$def->get('id')] = $def->get('textfilter');
                        }

                        break;
                }

            }
        }

        return $out;
    }

    /**
     * Get the given collection items that are still "in date".
     *
     * @param  string $type Content type
     * @param  int    $when UNIX timestamp cutoff point at which the field becomes invalid
     * @return array
     */

    public function getCollectionByExpiry($type, $when = null)
    {
        global $txpnow;

        if ($when === null) {
            $when = $txpnow;
        }

        assert_int($when);

        $out = array();

        foreach (self::$collection[$type] as $def) {
            if ($def['expires'] === NULLDATETIME || $def['expires'] <= strftime('%F %T', $when)) {
                $out[$type][$def->get('id')] = $def->get('expires');
            }
        }

        return $out;
    }


    /**
     * Stash the value of each field in the collection.
     */

    public function store($varray, $contentType, $contentId)
    {
        assert_int($contentId);

        $cfq = array();
        $ret = '';

        if (isset(self::$collection[$contentType])) {
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
                        $cfq[$cf_type][$id][] = "value = '" . doSlash($rawVal) . "'";
                    } else {
                        $cooked = Txp::get('Textpattern_Textfilter_Registry')->filter(
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
                    $set .= ", meta_id = '" . doSlash($metaId) . "', content_id = $contentId, value_id = '" . doSlash($valueId) . "'";
                    $ret = safe_insert($tableName, $set);
                }
            }
        }

        return $ret;
    }

    /**
     * IteratorAggregate interface.
     *
     * @return ArrayIterator
     * @see    IteratorAggregate
     */

    public function getIterator()
    {
        return new ArrayIterator(self::$collection);
    }

}

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
 * A collection of content type mappings for custom fields.
 *
 * Each entry in the array is a place in Textpattern where
 * custom field content may be stored.
 *
 * @since   5.0.0
 * @package CustomField
 */

namespace Textpattern\Meta;

class ContentType implements \IteratorAggregate, \Textpattern\Container\ReusableInterface
{
    /**
     * Default table map. May be altered by plugins.
     *
     * @var array
     */
    protected $tableColumnMap = array();

    /**
     * Default content type map. May be altered by plugins.
     *
     * @var array
     */
    protected $contentTypeMap = array();

    /**
     * General constructor for the map.
     *
     * The map can be extended or altered with a 'txp.meta > content.types' callback.
     * Callback functions get passed three arguments: '$event', '$step' and '$map'
     * that contains a reference to a nested array of:
     *    content_identifier:
     *        key    => content+_identifier (for array_column() support)
     *        label  => internationalised label
     *        column => the table.column that contains its ID
     *
     * @todo Section is going to prove tricky as it doesn't have a numerical ID,
     * yet the meta store assumes integer identifiers to provide matches.
     */

    public function __construct()
    {
        // TODO
        $c = 0;
        $this->tableColumnMap = array(
            ++$c => array(
                'id'     => $c,
                'key'    => 'article',
                'label'  => gTxt('article'),
                'table'  => 'textpattern',
                'column' => PFX.'textpattern.ID',
            ),
            ++$c => array(
                'id'     => $c,
                'key'    => 'image',
                'label'  => gTxt('image'),
                'table'  => 'txp_image',
                'column' => PFX.'txp_image.id',
            ),
            ++$c => array(
                'id'     => $c,
                'key'    => 'file',
                'label'  => gTxt('file'),
                'table'  => 'txp_file',
                'column' => PFX.'txp_file.id',
            ),
            ++$c => array(
                'id'     => $c,
                'key'    => 'link',
                'label'  => gTxt('link'),
                'table'  => 'txp_link',
                'column' => PFX.'txp_link.id',
            ),
            ++$c => array(
                'id'     => $c,
                'key'    => 'user',
                'label'  => gTxt('author'),
                'table'  => 'txp_users',
                'column' => PFX.'txp_users.user_id',
            ),
            ++$c => array(
                'id'     => $c,
                'key'    => 'category',
                'label'  => gTxt('category'),
                'table'  => 'txp_category',
                'column' => PFX.'txp_category.id',
            ),
            ++$c => array(
                'id'     => $c,
                'key'    => 'section',
                'label'  => gTxt('section'),
                'table'  => 'txp_section',
                'column' => PFX.'txp_section.name',
            ),
        );

        foreach (safe_column(array('name', 'id,label,table_id'), 'txp_meta_entity') as $name => $row) {
            if (!isset($this->tableColumnMap[$row['table_id']])) {
                continue;
            }

            $this->contentTypeMap[$name] = array(
                'tableId'     => $row['table_id'],
                'id'     => $row['id'],
                'key'    => $name,
                'label'  => gTxt($row['label']),
//                'table'  => $this->tableColumnMap[$row['table_id']]['table'],
                'column' => $this->tableColumnMap[$row['table_id']]['column'],
            );
        }


        callback_event_ref('txp.meta', 'content.types', 0, $this->contentTypeMap);
    }

    public function getTableColumnMap($id = null)
    {
        return $id === null ? $this->tableColumnMap :
            (isset($this->tableColumnMap[$id]) ? $this->tableColumnMap[$id] : null);
    }

    /**
     * Return a list of entities.
     *
     * @param   null|int|string Table id|name (null = everything)
     * @return  array  An entities array
     */

    public function getEntities($id = null)
    {
        if ($id === null) {
            return $this->getItem('id', array(), 'id');
        } elseif (is_int($id)) {
            return $this->getItem('id', function($v) use ($id) { return $v['tableId'] == $id; }, 'id');
        } else {
            $entities = $this->getItem('id', array(), 'key', $this->tableColumnMap);
            $id = isset($entities[$id]) ? $entities[$id] : 0;

            return $id ? $this->getItem('id', function($v) use($id) { return $v['tableId'] == $id; }, 'id') : array();
        }
    }
    
    public function getEntity($type)
    {
        if (!is_int($type)) {
            $type = (string)$type;
            $typeids = $this->getId();
        } else {
            $typeids = $this->getItem('id', array(), 'id');
        }

        return isset($typeids[$type]) ? (int)$typeids[$type] : 0;
    }
    
    public function getEntityTable($type)
    {
        if ($type = $this->getEntity($type)) {
            $tableIds = $this->getItem('tableId', array(), 'id');
            $tableId = isset($tableIds[$type]) ? (int)$tableIds[$type] : 0;
        }
    
        return isset($tableId) ? $tableId : 0;
    }

    public function getItemEntity($content_id, $id = 1, $raw = true)
    {
        $content_id = (int)$content_id;
        $ids = implode(',', $this->getEntities($id));
        $type = $ids ? (int)safe_field('type_id', 'txp_meta_registry', "content_id = $content_id AND type_id IN ($ids) LIMIT 1") : 0;

        if ($raw) {
            return $type;
        }

        $types = $type ? $this->getItem('key', array(), 'id') : null;

        return (isset($types[$type]) ? $types[$type] : false);
    }

    /**
     * Return a list of content types.
     *
     * @param   string Item to retrive from the array (null = everything)
     * @param   array  List of content keys to exclude
     * @return  array  A content types array
     */

    public function getItem($item = null, $exclude = array(), $key = 'key', $map = null)
    {
        isset($map) or $map = $this->contentTypeMap;

        if (is_callable($exclude)) {
            $map = array_filter($map, $exclude);
        } else {
            foreach ((array)$exclude as $remove) {
                unset($map[$remove]);
            }
        }

        return array_column($map, $item, $key);
    }

    /**
     * Return a list of content types and their associated names.
     *
     * @param   array List of content keys to exclude
     * @return  array A content types array
     */

    public function getLabel($exclude = array())
    {
        return $this->getItem('label', $exclude);
    }

    /**
     * Return a list of content types and their associated ids.
     *
     * @todo
     */

    public function getId($exclude = array())
    {
        return $this->getItem('id', $exclude);
    }

    /**
     * Return a list of content types and their associated column identifiers.
     *
     * @param   array List of content keys to exclude
     * @return  array A content types array
     */

    public function getColumn($exclude = array())
    {
        return $this->getItem('column', $exclude);
    }

    /**
     * Return the entire list of content types and their associated data.
     *
     * @param   array List of content keys to exclude
     * @return  array A content types array
     */

    public function get($exclude = array())
    {
        return $this->getItem(null, $exclude);
    }

    /**
     * Save the meta information defining this field.
     *
     * @param array $data Name-value tuples for the data to store against each field
     * @return  string Outcome message
     */

    public function save($data = array())
    {
        extract($data);
        unset($data['id'], $data['meta']);
        $id = isset($id) ? (int)$id : 0;
        $meta = isset($meta) ? array_filter(array_map('intval', (array)$meta)) : array();
        // TODO: validate data

        if (empty($id)) {
            $ok = safe_insert('txp_meta_entity', $data);
        } else {
            unset($data['table_id']); // don't allow table changes
            $ok = safe_update('txp_meta_entity', $data, "id = $id");
        }

        if ($ok) {
            $old_meta = $id ? safe_column_num('meta_id', 'txp_meta_fieldsets', "type_id = $id") : array();
            $id or $id = $ok;

            if ($meta_in = array_diff($meta, $old_meta)) {
                \Txp::get('\Textpattern\Meta\FieldSet', $id)->insert(null, $meta_in);
            }

            if ($meta_out = array_diff($old_meta, $meta)) {
                \Txp::get('\Textpattern\Meta\FieldSet', $id)->delete(null, $meta_out);
            }
        }

        return $ok;
    }


    /**
     * Delete the meta information for this item.
     *
     * @param array|int $id The ids of the items to delete
     * @return  null
     */

    public function delete($ids = array())
    {
        foreach ($ids = array_filter(array_map('intval', (array)$ids)) as $id) {
            \Txp::get('\Textpattern\Meta\FieldSet', $id)->delete();
            safe_delete('txp_meta_fieldsets', "type_id = $id");
            safe_delete('txp_meta_entity', "id = $id");
        }

        return;
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
        return new \ArrayIterator($this->contentTypeMap);
    }
}

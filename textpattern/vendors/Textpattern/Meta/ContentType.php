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
    {/*
        foreach (safe_column(array('name', 'id,label,txp_table,txp_column'), 'txp_meta_entity') as $name => $row) {
            $this->contentTypeMap[$name] = array(
                'id'     => $row['id'],
                'key'    => $name,
                'label'  => gTxt($row['label']),
                'table'  => $row['txp_table'],
                'column' => $row['txp_table'].'.'.$row['txp_column'],
            );
        }*/
        $c = 1;
        $this->contentTypeMap = array(
            'article' => array(
                'id'     => $c++,
                'key'    => 'article',
                'label'  => gTxt('article'),
                'table'  => 'textpattern',
                'column' => PFX.'textpattern.ID',
            ),
            'image' => array(
                'id'     => $c++,
                'key'    => 'image',
                'label'  => gTxt('image'),
                'table'  => 'txp_image',
                'column' => PFX.'txp_image.id',
            ),
            'file' => array(
                'id'     => $c++,
                'key'    => 'file',
                'label'  => gTxt('file'),
                'table'  => 'txp_file',
                'column' => PFX.'txp_file.id',
            ),
            'link' => array(
                'id'     => $c++,
                'key'    => 'link',
                'label'  => gTxt('link'),
                'table'  => 'txp_link',
                'column' => PFX.'txp_link.id',
            ),
            'user' => array(
                'id'     => $c++,
                'key'    => 'user',
                'label'  => gTxt('author'),
                'table'  => 'txp_users',
                'column' => PFX.'txp_users.user_id',
            ),
            'category' => array(
                'id'     => $c++,
                'key'    => 'category',
                'label'  => gTxt('category'),
                'table'  => 'txp_category',
                'column' => PFX.'txp_category.id',
            ),
            'section' => array(
                'id'     => $c++,
                'key'    => 'section',
                'label'  => gTxt('section'),
                'table'  => 'txp_section',
                'column' => PFX.'txp_section.name',
            ),
        );

        callback_event_ref('txp.meta', 'content.types', 0, $this->contentTypeMap);
    }

    /**
     * Return a list of content types.
     *
     * @param   string Item to retrive from the array (null = everything)
     * @param   array  List of content keys to exclude
     * @return  array  A content types array
     */

    public function getItem($item = null, $exclude = array(), $key = 'key')
    {
        $map = $this->contentTypeMap;

        foreach ((array)$exclude as $remove) {
            unset($map[$remove]);
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
     * Return the table of the given content type.
     *
     * @todo
     */

    public function getTable($exclude = array())
    {
        return $this->getItem('table', $exclude);
    }

    /**
     * Return the table of the given content type.
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

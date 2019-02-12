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
 * @since   4.8.0
 * @package CustomField
 */

namespace Textpattern\Meta;

class ContentType implements \IteratorAggregate
{
    /**
     * Default content type map. May be altered by plugins.
     *
     * @var array
     */
    protected $contentTypeMap = null;

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
        $this->contentTypeMap = array(
            'article' => array(
                'key'    => 'article',
                'label'  => gTxt('article'),
                'column' => PFX.'textpattern.ID',
            ),
            'image' => array(
                'key'    => 'image',
                'label'  => gTxt('image'),
                'column' => PFX.'txp_image.id',
            ),
            'file' => array(
                'key'    => 'file',
                'label'  => gTxt('file'),
                'column' => PFX.'txp_file.id',
            ),
            'link' => array(
                'key'    => 'link',
                'label'  => gTxt('link'),
                'column' => PFX.'txp_link.id',
            ),
            'user' => array(
                'key'    => 'user',
                'label'  => gTxt('author'),
                'column' => PFX.'txp_users.user_id',
            ),
            'category' => array(
                'key'    => 'category',
                'label'  => gTxt('category'),
                'column' => PFX.'txp_category.id',
            ),
            'section' => array(
                'key'    => 'section',
                'label'  => gTxt('section'),
                'column' => PFX.'section.name',
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

    protected function getItem($item = null, $exclude = array())
    {
        $map = $this->contentTypeMap;

        if (!is_array($exclude)) {
            $exclude = array();
        }

        foreach ($exclude as $remove) {
            unset($map[$remove]);
        }

        return array_column($map, $item, 'key');
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

    public function getIterator()
    {
        return new \ArrayIterator($this->contentTypeMap);
    }
}

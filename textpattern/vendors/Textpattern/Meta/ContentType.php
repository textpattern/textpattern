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
     * The map can be extended with a 'content.type > types' callback event.
     * Callback functions get passed three arguments: '$event', '$step' and
     * '$content_list', which contains a reference to an array of
     * 'content_name => label' pairs.
     *
     * @param array List of content type keys to exclude
     */

    public function __construct($exclude = array())
    {
        $this->contentTypeMap = array(
            'article'  => gTxt('article'),
            'image'    => gTxt('image'),
            'file'     => gTxt('file'),
            'link'     => gTxt('link'),
            'user'     => gTxt('author'),
            'category' => gTxt('category'),
            'section'  => gTxt('section'),
        );

        $map = $this->get($exclude);

        callback_event_ref('content.type', 'types', 0, $map);
    }

    /**
     * Return a list of content types and their associated names.
     *
     * @param   array List of content keys to exclude
     * @return  array A content types array
     * @since   4.6.0
     */

    public function get($exclude = array())
    {
        $map = $this->contentTypeMap;

        if (!is_array($exclude)) {
            $exclude = array();
        }

        foreach ($exclude as $remove) {
            unset($map[$remove]);
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
        return new \ArrayIterator($this->contentTypeMap);
    }
}

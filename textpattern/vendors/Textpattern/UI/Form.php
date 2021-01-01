<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * A &lt;form /&gt; tag.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Form extends Tag implements UICollectionInterface
{
    /**
     * The key (id) used in the tag.
     *
     * @var string
     */

    protected $key = null;

    /**
     * Collection of tags to be used as content.
     *
     * @var array
     */

    protected $tags = null;

    /**
     * Construct a single form container tag.
     *
     * @param string $method Which mechanism to submit the data - post or get
     * @param mixed  $item   The UI element or collection to add as content
     */

    public function __construct($method = 'post', $item = null)
    {
        parent::__construct('form');

        $this->setAtts(array(
                'class' => 'txp-form',
                'method' => $method,
            ));

        $this->setAction('index.php');

        $this->tags = new \Textpattern\UI\TagCollection();

        if ($item !== null) {
            $this->add($item);
        }
    }

    /**
     * Add one or more elements to the form. Chainable.
     *
     * @param mixed   $item The pre-built UI element or collection
     * @param boolean $key  The optional unique key to associate with the tag
     */

    public function add($item, $key = null)
    {
        if ($item instanceof \Textpattern\UI\TagCollection) {
            foreach ($item as $key => $element) {
                $this->tags->add($element, $key);
            }

            // Original object is not needed any more as it's been merged in this object.
            $item = null;
        } else {
            $this->tags->add($item, $key);
        }

        return $this;
    }

    /**
     * Remove an element from the form. Chainable.
     *
     * @param  string $key The reference to the object in the collection
     * @return this
     */

    public function remove($key)
    {
        $this->tags->remove($key);

        return $this;
    }

    /**
     * Fetch an element from the form.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function get($key)
    {
        $this->tags->get($key);
    }

    /**
     * Fetch the list of keys in use in the form's tags.
     */

    public function keys()
    {
        return $this->tags->keys();
    }

    /**
     * Fetch the number of items in the form.
     */

    public function length()
    {
        return $this->tags->length();
    }

    /**
     * Check if the given key exists in the form.
     *
     * @param  string $key The reference to the object in the collection
     */

    public function keyExists($key)
    {
        return $this->tags->keyExists($key);
    }

    /**
     * Define the form's destination URL. Chainable.
     *
     * @param string $action   The main part of the endpoint URL
     * @param string $fragment The part after the hash of the URL
     */

    public function setAction($action, $fragment = null)
    {
        if ($fragment) {
            $action .= '#'.$fragment;
        }

        $this->setAtt('action', $action);

        return $this;
    }

    /**
     * Add the elements as content and draw them.
     *
     * @param  string $flavour To affect the flavour of tag returned - complete, self-closing, open, close, content
     * @return string HTML
     */

    public function render($flavour = 'complete')
    {
        $break = $this->getBreak();
        $out = $this->tags->render($break);

        $this->setContent(n.$out.n);

        return parent::render($flavour);
    }
}

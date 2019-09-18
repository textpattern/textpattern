<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * A &lt;form /&gt; tag.
 *
 * @since   4.8.0
 * @package Widget
 */

namespace Textpattern\Widget;

class Form extends Tag implements \Textpattern\Widget\WidgetCollectionInterface
{
    /**
     * The key (id) used in the tag.
     *
     * @var null
     */

    protected $key = null;

    /**
     * Collection of tags to be used as content.
     *
     * @var array
     */

    protected $tags = null;

    /**
     * Construct a single form container widget.
     *
     * @param string $method Which mechanism to submit the data - post or get
     * @param mixed  $widget The widget or widget collection to add as content
     */

    public function __construct($method = 'post', $widget = null)
    {
        parent::__construct('form');

        $this->setAtts(array(
                'class' => 'txp-form',
                'method' => $method,
            ));

        $this->setAction('index.php');

        $this->tags = new \Textpattern\Widget\TagCollection();

        if ($widget !== null) {
            $this->addWidget($widget);
        }
    }

    /**
     * Add one or more widgets to the form. Chainable.
     *
     * @param mixed   $widget The pre-built widget or collection of widgets
     * @param boolean $label  The optional unique key to associate with the tag
     */

    public function addWidget($widget, $key = null)
    {
        if ($widget instanceof \Textpattern\Widget\TagCollection) {
            foreach ($widget as $key => $item) {
                $this->tags->addWidget($item, $key);
            }

            // Original object is not needed any nore as it's been merged in this object.
            $widget = null;
        } else {
            $this->tags->addWidget($widget, $key);
        }

        return $this;
    }

    /**
     * Remove a widget from the form. Chainable.
     *
     * @param  string $key The reference to the object in the collection
     * @return this
     */

    public function removeWidget($key)
    {
        $this->tags->removeWidget($key);

        return $this;
    }

    /**
     * Fetch a widget from the form.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function getWidget($key)
    {
        $this->tags->getWidget($key);
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
     * Add the widgets as content and draw them.
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

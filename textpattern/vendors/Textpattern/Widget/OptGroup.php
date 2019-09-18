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
 * An &lt;optgroup /&gt; tag and collection of options.
 *
 * Only used for creating select list components.
 *
 * @since   4.8.0
 * @package Widget
 */

namespace Textpattern\Widget;

class OptGroup extends Tag implements \Textpattern\Widget\WidgetCollectionInterface
{
    /**
     * The key (id) used in the tag.
     *
     * @var null
     */

    protected $key = null;

    /**
     * TagCollection of options in the group.
     *
     * @var array
     */

    protected $options = null;

    /**
     * Construct a single optgroup widget.
     *
     * @param string                            $label   The optgroup label
     * @param \Textpattern\Widget\TagCollection $options The options to add to the group
     */

    public function __construct($label, $options = null)
    {
        $this->key = $label;

        parent::__construct('optgroup');
        $this->setAtt('label', $this->key);

        if ($options instanceof \Textpattern\Widget\TagCollection) {
            $this->options = $options;
        } else {
            $this->options = new \Textpattern\Widget\TagCollection();
        }
    }

    /**
     * Add an option to the select group.
     *
     * The value and label will be internally escaped and assume dir="auto". Chainable.
     *
     * @param string  $value   The option key (HTML value attribute)
     * @param string  $label   The option text
     * @param boolean $checked True if the option is to be selected
     */

    public function addOption($value, $label, $checked = false)
    {
        $option = new \Textpattern\Widget\Option(
            txpspecialchars($value),
            txpspecialchars($label),
            $checked
        );

        $option->setAtt('dir', 'auto');

        $this->options->addWidget($option, $value);

        return $this;
    }

    /**
     * Add a widget to the collection. Chainable.
     *
     * @param  object $widget The widget
     * @param  string $key    Optional reference to the object in the collection
     * @return this
     */

    public function addWidget($widget, $key = null)
    {
        $this->options->addWidget($widget, $key);

        return $this;
    }

    /**
     * Remove a widget from the collection. Chainable.
     *
     * @param  string $key The reference to the object in the collection
     * @return this
     */

    public function removeWidget($key)
    {
        $this->options->removeWidget($key);

        return $this;
    }

    /**
     * Fetch a widget from the collection.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function getWidget($key)
    {
        return $this->options->getWidget($key);
    }

    /**
     * Fetch the list of keys in use.
     */

    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * Fetch the key (id) in use by this optgroup.
     *
     * @return string
     */

    public function getKey()
    {
        return $this->key;
    }

    /**
     * Add the options as content and draw them.
     *
     * @return string HTML
     */

    public function render($flavour = null)
    {
        $out = array();

        foreach ($this->options as $option) {
            $out[] = $option->render();
        }

        $this->setContent(n.join(n, $out).n);

        return parent::render($flavour);
    }
}

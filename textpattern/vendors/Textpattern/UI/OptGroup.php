<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2020 The Textpattern Development Team
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
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class OptGroup extends Tag implements UICollectionInterface
{
    /**
     * The key (id) used in the tag.
     *
     * @var string
     */

    protected $key = null;

    /**
     * TagCollection of options in the group.
     *
     * @var array
     */

    protected $options = null;

    /**
     * Construct a single optgroup element.
     *
     * @param string                        $label   The optgroup label
     * @param \Textpattern\UI\TagCollection $options The options to add to the group
     */

    public function __construct($label, $options = null)
    {
        $this->key = $label;

        parent::__construct('optgroup');
        $this->setAtt('label', $this->key);

        if ($options instanceof \Textpattern\UI\TagCollection) {
            $this->options = $options;
        } else {
            $this->options = new \Textpattern\UI\TagCollection();
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
        $option = new \Textpattern\UI\Option(
            txpspecialchars($value),
            txpspecialchars($label),
            $checked
        );

        $option->setAtt('dir', 'auto');

        $this->options->add($option, $value);

        return $this;
    }

    /**
     * Add an item to the collection. Chainable.
     *
     * @param  object $item The interface component
     * @param  string $key  Optional reference to the object in the collection
     * @return this
     */

    public function add($item, $key = null)
    {
        $this->options->add($item, $key);

        return $this;
    }

    /**
     * Remove an element from the collection. Chainable.
     *
     * @param  string $key The reference to the object in the collection
     * @return this
     */

    public function remove($key)
    {
        $this->options->remove($key);

        return $this;
    }

    /**
     * Fetch an element from the collection.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function get($key)
    {
        return $this->options->get($key);
    }

    /**
     * Fetch the list of keys in use.
     */

    public function keys()
    {
        return array_keys($this->options);
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

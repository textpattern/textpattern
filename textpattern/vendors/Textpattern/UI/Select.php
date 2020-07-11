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
 * A &lt;select /&gt; list tag.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Select extends Tag implements UIInterface
{
    /**
     * The key (id) used in the tag.
     *
     * @var string
     */

    protected $key = null;

    /**
     * List of option key => labels in the select.
     *
     * @var array
     */

    protected $options = array();

    /**
     * List of option groups with corresponding option keys.
     *
     * @var array
     */

    protected $optGroups = array();

    /**
     * The current optGroup that is 'open'.
     *
     * Any options added while this is set will automatically be added to the group.
     *
     * @var string
     */

    protected $currentGroup = null;

    /**
     * Construct a select input with a bunch of options.
     *
     * @param string       $name    The Select key (HTML name attribute)
     * @param array        $options Key => Label pairs
     * @param array|string $default Which option(s) are selected by default
     */

    public function __construct($name, $options = array(), $default = null)
    {
        parent::__construct('select');
        $this->key = $name;

        $this->setAtt('name', $name);

        if ($default === null) {
            $default = array();
        } elseif (!is_array($default)) {
            $default = do_list($default);
        }

        if (count($default) > 1) {
            $this->setMultiple();
        }

        foreach ($options as $avalue => $alabel) {
            $this->addOption($avalue, $alabel, in_array($avalue, $default));
        }
    }

    /**
     * Add an option to the select.
     *
     * The value and label will be internally escaped and assume dir="auto". Chainable.
     *
     * @param string  $value   The option key (HTML value attribute)
     * @param string  $label   The option text
     * @param boolean $checked True if the option is to be selected
     * @param string  $group   The group label to add this option to
     */

    public function addOption($value, $label, $checked = false, $group = null)
    {
        $option = new \Textpattern\UI\Option(
            txpspecialchars($value),
            txpspecialchars($label),
            $checked
        );

        $option->setAtt('dir', 'auto');

        if ($group === null && $this->currentGroup !== null) {
            $group = $this->currentGroup;
        }

        if ($group !== null) {
            if (!isset($this->optGroups[$group])) {
                $this->addOptGroup($group);
            }

            $this->optGroups[$group]->add($option, $value);
        } else {
            $this->options[$value] = $option;
        }

        return $this;
    }

    /**
     * Add an option group to the select. Chainable.
     *
     * @param string $label The optgroup label
     */

    public function addOptGroup($label)
    {
        $optGroup = new \Textpattern\UI\OptGroup(
            txpspecialchars($label)
        );

        $optGroup->setAtt('dir', 'auto');

        $this->optGroups[$label] = $optGroup;
        $this->currentGroup = $label;

        return $this;
    }

    /**
     * Add an empty element to the beginning of the options. Chainable.
     */

    public function blankFirst()
    {
        $emptyOption = new \Textpattern\UI\Option('', '&#160;');
        array_unshift($this->options, $emptyOption);

        return $this;
    }

    /**
     * Fetch the key (id) in use by this select list.
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
     * @param  string $flavour To affect the flavour of tag returned - complete, self-closing, open, close, content
     * @return string HTML
     */

    public function render($flavour = null)
    {
        $out = array();

        if (!$this->hasContent()) {
            if ($this->optGroups) {
                foreach ($this->optGroups as $optGroup) {
                    $out[] = $optGroup->render();
                }
            } else {
                foreach ($this->options as $option) {
                    $out[] = $option->render();
                }
            }

            $this->setContent(n.join(n, $out).n);
        }

        return parent::render($flavour);
    }
}

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
 * An input field and its associated &lt;label /&gt; tag.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class InputLabel extends Tag implements UICollectionInterface
{
    /**
     * The key (name) used in the tag.
     *
     * @var string
     */

    protected $key = null;

    /**
     * The label to display.
     *
     * @var string
     */

    protected $label = null;

    /**
     * The help topic associated with this field.
     *
     * @var string
     */

    protected $help = null;

    /**
     * The inline help topic associated with this field.
     *
     * @var string
     */

    protected $inlineHelp = null;

    /**
     * Collection of tags to be used as field content.
     *
     * @var array
     */

    protected $tags = null;

    /**
     * Collection of tags to be appended to the label content.
     *
     * @var array
     */

    protected $labelTags = null;

    /**
     * Tags in which to wrap the content and label, respectively.
     *
     * @var array
     */

    protected $wrapTags = array('div', 'div');

    /**
     * Construct a combined input + label.
     *
     * @param string $name  The text input key (HTML name attribute)
     * @param string $item  The pre-built UI element or collection
     * @param string $label The label to assign to the input control
     */

    public function __construct($name, $item = null, $label = '')
    {
        $this->key = $name;
        $this->label = ($label) ? $label : $name;
        $this->tags = new \Textpattern\UI\TagCollection();
        $this->labelTags = new \Textpattern\UI\TagCollection();

        parent::__construct('div');

        if ($item !== null) {
            $this->add($item);
        }
    }

    /**
     * Add one or more elements as content. Chainable.
     *
     * @param mixed   $item The pre-built UI element or collection
     * @param string  $key  The optional unique key to associate with the tag
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
     * Set any tools that appear alongside the element label
     *
     * @param string  $item The HTML to render the tool
     * @param string  $key  The optional unique key to associate with the tag
     */

    public function addTool($item, $key = null)
    {
        if ($item instanceof \Textpattern\UI\TagCollection) {
            foreach ($item as $key => $element) {
                $this->labelTags->add($element, $key);
            }

            // Original object is not needed any more as it's been merged in this object.
            $item = null;
        } else {
            $this->labelTags->add($item, $key);
        }

        return $this;
    }

    /**
     * Set the associated help topic for this input field. Chainable.
     *
     * @param string|array $topic The main help topic or help + inline topics as an array
     */

    public function setHelp($topic)
    {
        if (!is_array($topic)) {
            $topic = array($topic);
        }

        if (empty($topic)) {
            $topic = array(
                0 => '',
                1 => '',
            );
        }

        $this->inlineHelp = (empty($topic[1])) ? '' : $topic[1];
        $this->help = $topic[0];

        return $this;
    }

    /**
     * Set the label for the input control. Chainable.
     *
     * @param string $label The label to use.
     */

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the associated wraptags for this field/label. Chainable.
     *
     * @param string|array $wraptags The wrapper tag(s) to use.
     */

    public function setWrap($wraptags)
    {
        if (!is_array($wraptags)) {
            $wraptags = array($wraptags, $wraptags);
        }

        $this->wrapTags = $wraptags;

        return $this;
    }

    /**
     * Fetch the key (id) in use by this inputLabel.
     *
     * @return string
     */

    public function getKey()
    {
        return $this->key;
    }

    /**
     * Remove an element from the tag content set. Chainable.
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
     * Fetch an element from the tag content set.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function get($key)
    {
        $this->tags->get($key);
    }

    /**
     * Fetch the list of keys in use in the inputLabel's tag content set.
     */

    public function keys()
    {
        return $this->tags->keys();
    }

    /**
     * Add the elements as content and draw them.
     *
     * @param  string $flavour To affect the flavour of tag returned - complete, self-closing, open, close, content
     * @todo   pluggable_ui()
     * @return string HTML
     */

    public function render($flavour = 'complete')
    {
        global $event;

        $arguments = array(
            'name'        => $this->key,
            'input'       => $this->tags,
            'label'       => $this->label,
            'help'        => array($this->help, $this->inlineHelp),
            'atts'        => $this->atts,
            'wraptag_val' => $this->wrapTags,
        );

        $class = $this->getAtt('class', 'txp-form-field edit-'.str_replace('_', '-', $this->key));
        $help = ($this->help) ? popHelp($this->help) : '';
        $inlineHelp = ($this->inlineHelp) ? fieldHelp($this->inlineHelp) : '';

        $this->setAtts(array(
            'class' => $class,
        ));

        if (empty($this->label)) {
            $labelContent = gTxt($this->key).$help;
        } else {
            $labelContent = new \Textpattern\UI\Tag('label');
            $labelContent
                ->setAtts(array('for' => $this->key))
                ->setContent(gTxt($this->label).$help)
                ->render();
        }

        $labelContent .= (empty($this->labelTags)) ? '' : $this->labelTags->render();

        // Content wraptag.
        if (empty($this->wrapTags[0])) {
            $input = $this->tags->render();
        } else {
            $input = new \Textpattern\UI\Tag($this->wrapTags[0]);
            $input
                ->setAtt('class', 'txp-form-field-value')
                ->setContent(n.$this->tags->render())
                ->render();
        }

        // Label wraptag.
        if (empty($this->wrapTags[1])) {
            $label = $labelContent;
        } else {
            $label = new \Textpattern\UI\Tag($this->wrapTags[1]);
            $label
                ->setAtt('class', 'txp-form-field-label')
                ->setContent(n.$labelContent)
                ->render();
        }

        $this->setContent(n.$label.$inlineHelp.$input.n);

        return pluggable_ui($event.'_ui', 'inputlabel.'.$this->key, parent::render($flavour), $arguments);
    }
}

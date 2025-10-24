<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2025 The Textpattern Development Team
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
     * The label to display.
     *
     * @var string
     */

    protected $label = null;

    /**
     * The label target element's ID.
     *
     * @var string
     */

    protected $for = '';

    /**
     * Whether the class has been given a label (true) or was auto-assigned (false).
     *
     * @var bool
     */

    protected $hasLabel = false;

    /**
     * The label replacement pairs.
     *
     * @var array
     */

    protected $labelReps = array();

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
     * Tags and attributes with which to wrap the input and label.
     *
     * @var array
     */

    protected $wrapTags = array(
        'field' => array(
            'tag' => 'div',
            'atts' => array(
                'class' => 'txp-form-field-value',
            ),
        ),
        'label' => array(
            'tag' => 'div',
            'atts' => array(
                'class' => 'txp-form-field-label',
            ),
        )
    );

    /**
     * Construct a combined input + label.
     *
     * @param string       $name  The text input key (HTML name attribute)
     * @param string       $item  The pre-built UI element or collection
     * @param string|array $label The label to assign to the input control, with optional target ID
     */

    public function __construct($name, $item = null, $label = '')
    {
        $this->setKey($name);

        if ($label) {
            $this->setLabel($label);
        } else {
            $this->label = $name;
        }

        $this->tags = new \Textpattern\UI\TagCollection($item);
        $this->labelTags = new \Textpattern\UI\TagCollection();

        parent::__construct('div');
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
     * If no ID is specified, assumes it is the same as the key name.
     *
     * @param string|array $label The label (and optional target ID) to use.
     * @param array        $reps  Any replacement key=>values pairs for the label.
     */

    public function setLabel($label, $reps = array())
    {
        if (!is_array($label)) {
            $label = do_list($label);
        }

        $this->label = $label[0];
        $this->hasLabel = true;
        $this->for = empty($label[1]) ? $this->getKey() : $label[1];

        if ($reps) {
            $this->labelReps = (array)$reps;
        }

        return $this;
    }

    /**
     * Set the associated wraptags for this field/label. Chainable.
     *
     * @param string $type    The flavour of wraptag to set ('field' or 'label')
     * @param string $wraptag The wrapper tag to use. Use '' to not wrap, or null to leave it as-is (i.e. just to set $atts)
     * @param array  $atts    The attributes to apply to the wraptag. Will replace any same-named default atts
     */

    public function setWrap($type, $wraptag, $atts = array())
    {
        if (array_key_exists($type, $this->wrapTags)) {
            if ($wraptag !== null) {
                $this->wrapTags[$type]['tag'] = $wraptag;
            }

            if (!empty($atts)) {
                $this->wrapTags[$type]['atts'] = array_merge($this->wrapTags[$type]['atts'], $atts);
            }
        }

        return $this;
    }

    /**
     * Remove an element from the tag input set. Chainable.
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
     * Fetch the ID of the element that the label targets.
     *
     * @return string
     */

    public function getFor()
    {
        return $this->for;
    }

    /**
     * Fetch an element from the tag input set.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function get($key)
    {
        return $this->tags->get($key);
    }

    /**
     * Fetch the list of keys in use in the inputLabel's tag input set.
     *
     * @param string $type The tag keys to fetch (either 'field' or 'label')
     * @return TagCollection
     */

    public function keys($type = 'field')
    {
        return ($type === 'field') ? $this->tags->keys() : $this->labelTags->keys();
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

        $key = $this->getKey();
        $for = $this->getFor();

        $arguments = array(
            'name'        => $key,
            'content'     => $this->tags,
            'label'       => $this->label,
            'help'        => array($this->help, $this->inlineHelp),
            'atts'        => $this->atts,
            'wraptag_val' => $this->wrapTags,
        );

        $class = $this->getAtt('class', 'txp-form-field edit-'.str_replace('_', '-', $key));
        $help = ($this->help) ? popHelp($this->help) : '';
        $inlineHelp = ($this->inlineHelp) ? fieldHelp($this->inlineHelp) : '';

        $this->setAtts(array(
            'class' => $class,
        ));

        if ($this->hasLabel === false) {
            $labelContent = gTxt($this->label, $this->labelReps).$help;
        } else {
            $labelContent = new \Textpattern\UI\Tag('label');
            $labelContent->setAtts(array('for' => $for))
                ->setContent(gTxt($this->label, $this->labelReps).$help)
                ->render();
        }

        $labelContent .= (empty($this->labelTags)) ? '' : $this->labelTags->render();

        // Content wraptag.
        if (empty($this->wrapTags['field']['tag'])) {
            $input = $this->tags->render();
        } else {
            $input = new \Textpattern\UI\Tag($this->wrapTags['field']['tag']);
            $input
                ->setAtts($this->wrapTags['field']['atts'])
                ->setContent(n.$this->tags->render())
                ->render();
        }

        // Label wraptag.
        if (empty($this->wrapTags['label']['tag'])) {
            $label = $labelContent;
        } else {
            $label = new \Textpattern\UI\Tag($this->wrapTags['label']['tag']);
            $label->setAtts($this->wrapTags['label']['atts'])
                ->setContent(n.$labelContent)
                ->render();
        }

        $this->setContent(n.$label.$inlineHelp.$input.n);

        return pluggable_ui($event.'_ui', 'inputlabel.'.$key, parent::render($flavour), $arguments);
    }
}

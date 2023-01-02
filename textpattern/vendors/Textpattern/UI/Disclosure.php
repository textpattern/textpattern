<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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
 * A disclosure (details/summary) block.
 *
 * Replaces wrapGroup() and wrapRegion().
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Disclosure extends Tag implements UICollectionInterface
{
    /**
     * The pane (id) to store open/closed state for the disclosure.
     *
     * @var string
     */

    protected $pane_id = null;

    /**
     * The lever (id) used for the disclosure.
     *
     * @var string
     */

    protected $lever_id = null;

    /**
     * The label text used for the disclosure.
     *
     * @var string
     */

    protected $label = null;

    /**
     * The label (id) used for the aria state.
     *
     * @var string
     */

    protected $label_id = null;

    /**
     * The help topic used for the disclosure.
     *
     * @var string
     */

    protected $help = null;

    /**
     * Collection of tags to be used as content.
     *
     * @var array
     */

    protected $tags = null;

    /**
     * Construct a set of elements that make up a clickable disclosure.
     *
     * @param string $key  The id of the region wrapper
     * @param string $pane The id of the pane to store the state of the twisty.
     */

    public function __construct($key, $pane = null)
    {
        parent::__construct('section');
        $class = 'txp-details';
        $this->setKey($key);
        $this->label_id = $key.'-label';

        if ($pane !== null) {
            $this->pane_id = $this->lever_id = $pane;
        } else {
            $this->lever_id = $key.'-anchor';
        }

        $this->tags = new \Textpattern\UI\TagCollection();
        $this->setAtts(array(
                'class' => $class,
                'id'    => $key,
            ));
    }

    /**
     * Add one or more elements to the disclosure content. Chainable.
     *
     * @param mixed   $item The pre-built UI element or collection
     * @param string  $key  The optional unique key to associate with the tag
     */

    public function add($item, $key = null)
    {
        if ($item instanceof \Textpattern\UI\TagCollection) {
            foreach ($item as $ref => $element) {
                $this->tags->add($element, $ref);
            }

            // Original object is not needed any more as it's been merged in this object.
            $item = null;
        } else {
            $this->tags->add($item, $key);
        }

        return $this;
    }

    /**
     * Define the label for the disclosure. Chainable.
     *
     * @param string $label The label to use
     */

    public function setLabel($label)
    {
//        $heading = new \Textpattern\UI\Tag('summary');
//        $heading->setContent(txpspecialchars($label));

        $this->label = $label;

        return $this;
    }

    /**
     * Define the help topic for the disclosure. Chainable.
     *
     * @param string $topic The help topic reference
     */

    public function setHelp($topic)
    {
        $this->help = $topic;

        return $this;
    }

    /**
     * Define the disclosure open/closed state. Chainable.
     *
     * Calling this without any argument toggles the state.
     *
     * @param bool $state Whether the control is open (true) or closed (false)
     */

    public function setVisible($state = null)
    {
        if ($state === null) {
            $this->setProperty('state', !$this->getProperty('state'));
        } else {
            $this->setProperty('state', (bool) $state);
        }

        return $this;
    }

    /**
     * Remove an element from the disclosure. Chainable.
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
     * Fetch an element from the disclosure.
     *
     * @param  string $key The reference to the object in the collection
     * @return object
     */

    public function get($key)
    {
        $this->tags->get($key);
    }

    /**
     * Fetch the list of keys in use in the disclosure's tags.
     */

    public function keys()
    {
        return $this->tags->keys();
    }

    /**
     * Fetch the number of items in the disclosure.
     */

    public function length()
    {
        return $this->tags->length();
    }

    /**
     * Check if the given key exists in the disclosure.
     *
     * @param  string $key The reference to the object in the collection
     */

    public function keyExists($key)
    {
        return $this->tags->keyExists($key);
    }

    /**
     * Add the elements as content and draw them.
     *
     * @param  string $flavour To affect the flavour of tag returned - complete, self-closing, open, close, content
     * @return string HTML
     */

    public function render($flavour = 'complete')
    {
        global $event;

        $pane_token = null;
        $anchor = null;
        $state = $this->getProperty('state');
        $heading_class = '';
        $display_state = array('role' => 'group');
        $anchorText = ($this->label ? gTxt($this->label) : null);

        if ($this->pane_id !== null) {
            $pane_token = md5($this->pane_id.$event.form_token().get_pref('blog_uid'));
            $heading_class = 'txp-summary'.($state ? ' expanded' : '');
            $display_state = array(
                'class' => $state ? 'toggle' : 'toggle hidden',
                'id'    => $this->lever_id,
                'role'  => 'group',
            );

            $anchor = new \Textpattern\UI\Tag('a');
            $anchor->setContent($anchorText)
                ->setAtts(array(
                    'href'           => '#'.$this->lever_id,
                    'role'           => 'button',
                    'data-txp-token' => $pane_token,
                    'data-txp-pane'  => $this->pane_id,
                ))
                ->render();
        }

        $break = $this->getBreak();
        $out = $this->tags->render($break);

        $heading = new \Textpattern\UI\Tag('h3');
        $heading->setAtts(array(
            'class' => $heading_class,
            'id'    => $this->label_id,
        ))
            ->setContent(($anchor ? $anchor : $anchorText).popHelp($this->help))
            ->render();

        $block = new \Textpattern\UI\Tag('div');
        $block->setContent(n.$out.n)
            ->setAtts($display_state)
            ->render();

        $this->atts->setAttribute('aria-labelledby', ($out ? $this->label_id : ''));
        $this->setContent(n.$heading.n.$block.n);

        return parent::render($flavour);
    }
}

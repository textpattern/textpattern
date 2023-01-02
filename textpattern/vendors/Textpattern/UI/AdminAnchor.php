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
 * An anchor tag for creating admin-side event/step URL links.
 *
 * Replaces aLink(), eLink(), sLink(), wLink().
 *
 * @since   4.9.0
 * @see     dLink()
 * @package UI
 */

namespace Textpattern\UI;

class AdminAnchor extends Tag implements UIInterface
{
    /**
     * The link endpoint's event (panel).
     *
     * @var string
     */

    protected $event = null;

    /**
     * The link endpoint's step (action).
     *
     * @var string
     */

    protected $step = null;

    /**
     * The link text.
     *
     * @var string
     */

    protected $linktext = null;

    /**
     * The link type.
     *
     * @var string
     */

    protected $type = null;

    /**
     * The tag's link attributes as an Attribute object.
     *
     * @var \Textpattern\UI\Attribute
     */

    protected $linkParams = null;

    /**
     * Construct content and anchor with links to admin-side event and step.
     *
     * @param string  $event    Textpattern panel (event)
     * @param string  $step     Textpattern action (step)
     * @param string  $linktext Link content
     * @param string  $type     Whether the link is an anchor (get) or a form (post)
     */

    public function __construct($event, $step, $linktext, $type = 'get')
    {
        $this->event = $event;
        $this->step = $step;
        $this->type = $type;
        $this->linktext = $linktext;
        $this->linkParams = new \Textpattern\UI\Attribute();

        if ($type === 'get') {
            parent::__construct('a');

            $this->setParams(array(
                'event' => $event,
                'step'  => $step,
            ));
        } else {
            parent::__construct('form');
        }
    }

    /**
     * Set the given link attribute. Chainable.
     *
     * @param  string $key   Attribute key
     * @param  string $value Attribute value
     * @param  array  $props Name-value attribute options
     * @return this
     */

    public function setParam($key, $value = null, $props = array())
    {
        $this->linkParams->setAttribute($key, $value, $props);

        return $this;
    }

    /**
     * Set the given attributes. Chainable.
     *
     * @param  array $atts  Name-value attributes
     * @param  array $props Name-value attribute options
     * @return this
     */

    public function setParams($atts, $props = array())
    {
        foreach ($atts as $key => $value) {
            $this->linkParams->setAttribute($key, $value, $props);
        }

        return $this;
    }

    /**
     * Render the anchor.
     *
     * @param  string $option To affect the flavour of tag returned - complete, self-closing, open, close, content
     * @return string HTML
     */

    public function render($flavour = 'complete')
    {
        $linkParams = array();

        $verify = (string)$this->getProperty('verify', null);
        $token = (string)$this->getProperty('token', false);

        if ($verify !== null) {
            $this->setAtt('data-verify', gTxt($verify));
        }

        if ($token) {
            $this->setParam('_txp_token', form_token());
        }

        // Collect all link attributes into a simple array.
        foreach ($this->linkParams as $att => $val) {
            $linkParams[$att] = $val;
        }

        switch ($this->type) {
            case 'post':
                $this->setAtts(array(
                    'method' => 'post',
                    'action' => 'index.php',
                ));

                $this->setContent(
                    $this->linktext.
                    \Txp::get('\Textpattern\UI\AdminAction', $this->event, $this->step, false)->render().
                    \Txp::get('\Textpattern\UI\InputSet', $linkParams, 'hidden')->render()
                );

                break;
            case 'get':
            default:
                $this->setContent(escape_title($this->linktext));

                $anchor = join_qs($linkParams);
                $this->setAtt('href', $anchor);

                break;
        }

        return parent::render();
    }
}

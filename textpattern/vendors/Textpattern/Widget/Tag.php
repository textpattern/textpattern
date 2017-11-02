<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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
 * Base widget - a tag.
 *
 * @since   4.7.0
 * @package Widget
 */

namespace Textpattern\Widget;

class Tag implements \Textpattern\Widget\WidgetInterface
{
    /**
     * The tag name.
     *
     * @var string
     */

    protected $tag = null;

    /**
     * The tag's contained contents.
     *
     * @var string
     */

    protected $content = null;

    /**
     * The tag's attributes as an Attribute object.
     *
     * @var \Textpattern\Widget\Attribute
     */

    protected $atts = null;

    /**
     * The tag properties, keyed via their index.
     *
     * @var array
     */

    protected $properties = array();

    /**
     * General constructor for the tag.
     */

    public function __construct($tag)
    {
        $this->setTag($tag);
        $this->atts = new \Textpattern\Widget\Attribute();
    }

    /**
     * Set the tag to use. Chainable.
     *
     * @param  string $tag The tag name
     * @return this
     */

    public function setTag($tag)
    {
        $this->tag = (string)$tag;

        return $this;
    }

    /**
     * Set the tag's contained content. Chainable.
     *
     * @param  array $content Thew content to put between the opening/closing tags
     * @return this
     */

    public function setContent($content)
    {
        $this->content = (string)$content;

        return $this;
    }

    /**
     * Set the given attributes. Chainable.
     *
     * @param  array $atts  Name-value attributes
     * @param  array $props Name-value attribute options
     * @return this
     */

    public function setAtts($atts, $props = array())
    {
        foreach ($atts as $key => $value) {
            $this->atts->setAttribute($key, $value, $props);
        }

        return $this;
    }

    /**
     * Set the given attributes. Chainable.
     *
     * @param  string|array $keys (List of) keys to set as boolean attributes
     * @return this
     */

    public function setBool($keys)
    {
        if (!is_array($keys)) {
            $keys = (array)$keys;
        }

        foreach ($keys as $key) {
            $this->atts->setAttribute($key, true, array(
                'format' => 'bool',
                'flag'   => TEXTPATTERN_STRIP_TXP,
            ));
        }

        return $this;
    }

    /**
     * Permit multiple values to be sent by the tag. Chainable.
     */

    public function setMultiple()
    {
        $this->atts->setMultiple();

        return $this;
    }

    /**
     * Render the given content as an XML element.
     *
     * @param  array $option To affect the flavour of tag returned - complete, self-closing, open, close, content
     * @return string HTML
     */

    public function render($option = null)
    {
        // @todo Check for required atts?
        if ($option === null) {
            if (empty($this->tag) || $this->content === '') {
                $option = 'content';
            } elseif ($this->content) {
                $option = 'complete';
            } else {
                $option = 'self-closing';
            }
        }

        switch ($option) {
            case 'complete':
                $out = '<'.$this->tag.$this->atts->render().'>'.$this->content.'</'.$this->tag.'>';
                break;
            case 'self-closing':
                $out = '<'.$this->tag.$this->atts->render().' />';
                break;
            case 'open':
                $out = '<'.$this->tag.$this->atts->render().'>';
                break;
            case 'close':
                $out = '</'.$this->tag.'>';
                break;
            case 'content':
            default:
                $out = $this->content;
                break;
        }

        return $out;
    }

    /**
     * Magic method that prints the tag with default options.
     * 
     * @return string HTML
     */
    public function __toString()
    {
        return $this->render();
    }
}

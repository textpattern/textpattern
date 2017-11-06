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
     * Global control over tag output.
     *
     * @var array
     */

    static $flags = null;

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
     * The available schemes that can be used for tag output.
     *
     * @var array
     */

    protected $schemes = array(
        'xhtml',
        'html5',
    );

    /**
     * General constructor for the tag.
     *
     * @param  string $tag The tag name
     */

    public function __construct($tag)
    {
        if (self::$flags === null) {
            self::$flags['boolean'] = 'html5';
            self::$flags['self-closing'] = 'html5';
        }

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

        $props = array('format' => 'bool');

        if (self::$flags['boolean'] === 'html5') {
            $props['flag'] = TEXTPATTERN_STRIP_TXP;
        }

        foreach ($keys as $key) {
            $this->atts->setAttribute($key, true, $props);
        }

        return $this;
    }

    /**
     * Permit multiple values to be sent by the tag. Chainable.
     *
     * @param string $flavour The type of mulitple to assign: 'all', 'name', or 'attribute'
     */

    public function setMultiple($flavour = 'all')
    {
        $this->atts->setMultiple($flavour);

        return $this;
    }

    /**
     * Define the global tag options from this point forward. Chainable.
     *
     * @param  string $flag   The name of the flag to set. Either 'self-closing' or 'boolean'
     * @param  string $scheme The scheme to set the flag to. Either 'html5' or 'xhtml'
     */

    public function setFlag($flag, $scheme)
    {
        if (in_array($scheme, $this->schemes)) {
            self::$flags[$flag] = $scheme;
        }

        return $this;
    }

    /**
     * Set the tag scheme to xhtml or html5 for all flags at once. Chainable.
     *
     * Just a shortcut for:
     *   setFlag('self-closing', $scheme);
     *   setFlag('boolean', $scheme);
     *
     * @param string $scheme The scheme to set all the output control flags to. Either 'html5' or 'xhtml'
     */

    public function setScheme($scheme)
    {
        if (in_array($scheme, $this->schemes)) {
            $this->setFlag('self-closing', $scheme);
            $this->setFlag('boolean', $scheme);
        }

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
                $out = '<'.$this->tag.$this->atts->render().(self::$flags['self-closing'] === 'html5' ? '>' : ' />');
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

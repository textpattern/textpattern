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
 * Base HTML component - a tag.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Tag implements UIInterface
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
     * @var \Textpattern\UI\Attribute
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
            self::$flags['break'] = '';
            self::$flags['break-on'] = '';
        }

        $this->setTag($tag);
        $this->atts = new \Textpattern\UI\Attribute();
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
     * Set the given attribute. Chainable.
     *
     * @param  string $key   Attribute key
     * @param  string $value Attribute value
     * @param  array  $props Name-value attribute options
     * @return this
     */

    public function setAtt($key, $value = null, $props = array())
    {
        $this->atts->setAttribute($key, $value, $props);

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
     * Append a value to an existing attribute, if a value already exists. Chainable.
     *
     * @param  string $key   Attribute key
     * @param  string $value Attribute value
     * @param  string $glue  The string to use to join the values together
     * @return this
     */

    public function appendAtt($key, $value, $glue = ' ')
    {
        $currentVal = $this->atts->getValue($key);
        $parts = array();

        if ($currentVal !== null) {
            $parts[] = $currentVal;
        }

        $parts[] = $value;

        $this->setAtt($key, implode($glue, $parts));

        return $this;
    }

    /**
     * Append a value to an existing attribute, if a value already exists. Chainable.
     *
     * @param  array  $atts  Name-value attributes
     * @param  string $glue  The string to use to join each value to its existing content
     * @return this
     */

    public function appendAtts($atts, $glue = ' ')
    {
        foreach ($atts as $key => $value) {
            $this->appendAtt($key, $value, $glue);
        }

        return $this;
    }

    /**
     * Retrieve the given attribute or assign default if not set.
     *
     * @param  string $key     Attribute key
     * @param  string $default Attribute default value if unset
     * @param  array  $props Name-value attribute options
     * @return this
     */

    public function getAtt($key, $default = null)
    {
        $val = $this->atts->getValue($key);

        return ($val === null) ? $default : $val;
    }

    /**
     * Set the given attributes. Chainable.
     *
     * @param  string|array $keys One or more keys to set as boolean attributes
     * @return this
     */

    public function setBool($keys)
    {
        if (!is_array($keys)) {
            $keys = do_list($keys);
        }

        $props = array('format' => 'bool');

        if (self::$flags['boolean'] === 'html5') {
            $props['strip'] = TEXTPATTERN_STRIP_TXP;
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
     * Define one more more global tag options from this point forward. Chainable.
     *
     * @param string|array $flag  The name of the flag to set or an array of flag=>value pairs
     * @param string|null  $value The value of the flag
     */

    public function setFlag($flag, $value = null)
    {
        if (!is_array($flag)) {
            $flag = array($flag => $value);
        }

        foreach ($flag as $key => $val) {
            self::$flags[$key] = $val;
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
            $this->setFlag(array(
                'self-closing' => $scheme,
                'boolean'      => $scheme,
            ));
        }

        return $this;
    }

    /**
     * Define one or more local tag properties. Chainable.
     *
     * @param string|array $prop  The name of the property to set or an array of property=>value pairs
     * @param string|null  $value The value of the property
     */

    public function setProperty($prop, $value)
    {
        if (!is_array($prop)) {
            $prop = array($prop => $value);
        }

        foreach ($prop as $key => $val) {
            $this->properties[$key] = $val;
        }

        return $this;
    }

    /**
     * Retrieve a local tag property.
     *
     * @param string      $key     The name of the property to fetch
     * @param string|null $default The default value of the property if not defined
     */

    public function getProperty($key, $default = null)
    {
        $out = $default;

        if (isset($this->properties[$key])) {
            $out = $this->properties[$key];
        }

        return $out;
    }

    /**
     * Set the break string to use after the tag has been output. Chainable.
     *
     * @param string $break The break tag to use
     */

    public function setBreak($break = br)
    {
        $this->setProperty('break', $break);

        return $this;
    }

    /**
     * Get the break string to use after the tag has been output.
     *
     * The global break flag is set first, then the break-on list is checked.
     * Finally, the local break may override it.
     */

    public function getBreak()
    {
        $breaklist = (empty(self::$flags['break-on'])) ? array() : do_list(self::$flags['break-on']);
        $break = (empty($breaklist) || in_array($this->tag, $breaklist)) ? self::$flags['break'] : '';
        $break = (array_key_exists('break', $this->properties)) ? $this->properties['break'] : $break;

        return $break;
    }

    /**
     * Determine if the tag has content assigned.
     *
     * @return boolean
     */

    public function hasContent()
    {
        return !empty($this->content);
    }

    /**
     * Render the given content as an XML-style element.
     *
     * @param  string $option To affect the flavour of tag returned - complete, self-closing, open, close, content
     * @return string HTML
     */

    public function render($option = null)
    {
        if ($option === null) {
            if (empty($this->tag)) {
                $option = 'content';
            } elseif ($this->content !== null) {
                $option = 'complete';
            } else {
                $option = 'self-closing';
            }
        }

        $break = $this->getBreak();

        switch ($option) {
            case 'complete':
                $out = '<'.$this->tag.$this->atts->render().'>'.$this->content.'</'.$this->tag.'>'.$break;
                break;
            case 'self-closing':
                $out = '<'.$this->tag.$this->atts->render().(self::$flags['self-closing'] === 'html5' ? '>' : ' />').$break;
                break;
            case 'open':
                $out = '<'.$this->tag.$this->atts->render().'>';
                break;
            case 'close':
                $out = '</'.$this->tag.'>'.$break;
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

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
 * A &lt;select /&gt; list tag built from a tree-based record set.
 *
 * @see  pre-order binary tree (category) algorithms
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class SelectTree extends Select implements UIInterface
{
    /**
     * Minimum length of the truncate flag.
     */

    const MIN_LABEL_LENGTH  = 4;

    /**
     * Tag properties that control the output.
     *
     * separator: Separator character(s) to indicate the option's depth.
     * label_max: Maximum number of characters in label before truncation.
     * more:      Characters to append to the label to indicate truncation.
     * skip:      Element keys to ignore in the final output.
     *
     * @var array
     */

    protected $properties = array(
        'separator' => sp.sp,
        'label_max' => null,
        'more'      => '&#8230;',
        'skip'      => 'root',
    );

    /**
     * Construct a tree select input with a bunch of options.
     *
     * @param string       $name    The Select key (HTML name attribute)
     * @param array        $options Record set with this structure:
     *                              row => array(
     *                                  'name'  => Option key
     *                                  'title' => Option label
     *                                  'level' => Option depth
     *                              ), ...
     * @param array|string $default Which option(s) are selected by default
     * @param array        $properties Ways to control the output. Options:
     *                                'separator' => Separator character (default: 2x sp chars)
     *                                'label_max' => Max length of label (min: 4)
     *                                'more'      => String to indicate truncation (default: &#8230; ellipses)
     *                                'skip'      => names (keys) to omit from the final list (default: root)
     */

    public function __construct($name, $options = array(), $default = null, $properties = null)
    {
        parent::__construct('select');

        $this->setKey($name)
            ->setAtt('name', $name);

        if ($default === null) {
            $default = array();
        } elseif (!is_array($default)) {
            $default = do_list($default);
        }

        if (count($default) > 1) {
            $this->setMultiple();
        }

        if (is_array($properties)) {
            foreach ($properties as $prop => $setting) {
                $this->setProperty($prop, $setting);
            }
        }

        foreach ($options as $key => $info) {
            $name = $info['name'];
            $title = $info['title'];
            $this->addOption($info['name'], $title, in_array($name, $default), $info['level']);
        }

        $this->blankFirst();
    }

    /**
     * Add an option to the select.
     *
     * The value and label will be internally escaped and assume dir="auto". Chainable.
     *
     * @param string  $value   The option key (HTML value attribute)
     * @param string  $label   The option text
     * @param boolean $checked True if the option is to be selected
     * @param string  $level   The depth that this option is repesented in the tree
     */

    public function addOption($value, $label, $checked = false, $level = null)
    {
        $skip = empty($this->properties['skip']) ? array() : do_list($this->properties['skip']);

        if (!in_array($value, $skip)) {
            $maxlen = empty($this->properties['label_max']) ? null : $this->properties['label_max'];
            $sep = empty($this->properties['separator']) ? '' : str_repeat($this->properties['separator'], $level);
            $html_title = $suffix = '';

            if (($maxlen > self::MIN_LABEL_LENGTH) && (Txp::get('\Textpattern\Type\StringType', $label)->getLength() > $maxlen)) {
                $html_title = txpspecialchars($label);
                $suffix = empty($this->properties['more']) ? '' : $this->properties['more'];
                $label = preg_replace('/^(.{0,'.($maxlen).'}).*$/su', '$1', $label);
            }

            $option = new \Textpattern\UI\Option(
                txpspecialchars($value),
                $sep.txpspecialchars($label).$suffix,
                $checked
            );

            $option->setAtts(array(
                'dir'        => 'auto',
                'data-level' => $level,
            ));

            if ($html_title) {
                $option->setAtt('title', $html_title);
            }

            $this->options[$value] = $option;
        }

        return $this;
    }
}

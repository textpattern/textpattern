<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2019 The Textpattern Development Team
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
 * Constraint for field sizes (min/max length).
 *
 * @since   4.9.0
 * @package Validator
 */

namespace Textpattern\Validator;

class SizeConstraint extends Constraint
{
    /**
     * Function parameter => HTML attribute map.
     *
     * @var array
     */

    protected $attributeMap = array(
        'cols' => 'cols',
        'min'  => 'minlength',
        'max'  => 'maxlength',
        'rows' => 'rows',
        'size' => 'size',
    );

    /**
     * Common sizes in use across the site.
     *
     * @var array
     */

    protected $sizeMap = array(
        INPUT_XLARGE  => 'xlarge',
        INPUT_LARGE   => 'large',
        INPUT_REGULAR => 'regular',
        INPUT_MEDIUM  => 'medium',
        INPUT_SMALL   => 'small',
        INPUT_XSMALL  => 'xsmall',
        INPUT_TINY    => 'tiny',
    );

    /**
     * Constructor.
     *
     * @param mixed $value
     * @param array $options Contains any/all of: min/max/message
     */

    public function __construct($value, $options = array())
    {
        $options = lAtts(array(
            'message' => 'out_of_range',
            'cols'    => null,
            'min'     => null,
            'max'     => null,
            'rows'    => null,
            'size'    => null,
        ), $options, false);
        parent::__construct($value, $options);
    }

    /**
     * Gets attribute map.
     *
     * @param  $key option to return just one of the values. If omitted, all are returned
     * @return array of HTML attribute->value pairs suitable for passing to a UI control
     */

    public function getAttsMap($key = null)
    {
        $out = parent::getAttsMap($key);

        if ($this->options['size'] !== null) {
            if (is_numeric($this->options['size']) && array_key_exists($this->options['size'], $this->sizeMap)) {
                $out['size'] = $this->options['size'];
                $out['class'] = $this->sizeMap[$this->options['size']];
            } elseif (($size = array_search($this->options['size'], $this->sizeMap)) !== false) {
                $out['size'] = $size;
                $out['class'] = $this->options['size'];
            }
        }

        return $out;
    }

    /**
     * Validates filter values.
     *
     * @return bool
     */

    public function validate()
    {
        $length = Txp::get('\Textpattern\Type\StringType', $this->value)->getLength();

        $out = true;

        if ($this->options['min'] !== null) {
            $out = $out && ($length >= $this->options['min']);
        }

        if ($this->options['max'] !== null) {
            $out = $out && ($length <= $this->options['max']);
        }

        return $out;
    }
}

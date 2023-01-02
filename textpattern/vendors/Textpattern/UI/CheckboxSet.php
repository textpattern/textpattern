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
 * An &lt;input type="checkbox" /&gt; tag set.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class CheckboxSet extends TagCollection implements UICollectionInterface
{
    /**
     * Construct a set of checkbox buttons.
     *
     * @param string       $name    The CheckboxSet key (HTML name attribute)
     * @param array        $options Key => Label pairs
     * @param array|string $default The key(s) from the $options array to select by default
     */

    public function __construct($name, $options, $default = null)
    {
        if ($default === null) {
            $default = array();
        } elseif (!is_array($default)) {
            $default = do_list($default);
        }

        foreach ((array) $options as $key => $label) {
            $key = (string)$key;
            $checked = (in_array($key, $default));

            $box = new \Textpattern\UI\Checkbox($name, $key, $checked);
            $box->setMultiple('name');
            $id = $box->getKey();
            $label = new \Textpattern\UI\Label($label, $id);

            $this->add($box, 'checkbox-'.$id)
                ->add($label, 'label-'.$id);
        }
    }
}

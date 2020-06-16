<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2020 The Textpattern Development Team
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
 * A &lt;textarea /&gt; tag.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Textarea extends Tag implements UIInterface
{
    /**
     * The key (id) used in the tag.
     *
     * @var string
     */

    protected $key = null;

    /**
     * Construct a single textarea field.
     *
     * @param string $name    The textarea key (HTML name attribute)
     * @param string $content The default content to assign
     */

    public function __construct($name, $content = '')
    {
        $this->key = $name;

        parent::__construct('textarea');
        $this->setAtt('name', $this->key);
        $this->setContent(txpspecialchars($content));
    }

    /**
     * Fetch the key (id) in use by this text input.
     *
     * @return string
     */

    public function getKey()
    {
        return $this->key;
    }
}

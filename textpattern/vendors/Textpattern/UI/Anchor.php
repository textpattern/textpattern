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
 * An anchor tag for creating URL links.
 *
 * Replaces href().
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Anchor extends Tag implements UIInterface
{
    /**
     * Construct content and anchor.
     *
     * @param string        $content The link content
     * @param string|array  $anchor  The link itself or a set of parts
     */

    public function __construct($content, $anchor = '#')
    {
        parent::__construct('a');

        if (is_array($anchor)) {
            $anchor = join_qs($anchor);
        }

        $this->setContent($content);
        $this->setAtt('href', $anchor);
    }
}

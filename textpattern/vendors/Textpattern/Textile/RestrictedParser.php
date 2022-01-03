<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * Textpattern configured Textile wrapper.
 *
 * @since   4.7.2
 * @package Textile
 */

namespace Textpattern\Textile;

/**
 * Textile restricted parser.
 *
 * @since   4.7.2
 * @package Textile
 */
 
class RestrictedParser extends Parser
{
    public function __construct($doctype = null)
    {
        parent::__construct($doctype);

        $this
            ->setRestricted(true)
            ->setLite(!get_pref('comments_use_fat_textile', 1))
            ->setImages((bool) get_pref('comments_disallow_images', 1));

        if (get_pref('comment_nofollow', 1)) {
            $this->setLinkRelationShip('nofollow');
        }
    }
}

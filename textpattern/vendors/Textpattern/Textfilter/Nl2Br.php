<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2016 The Textpattern Development Team
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Nl2Br filter.
 *
 * This filter converts line breaks to HTML &lt;br /&gt; tags.
 *
 * @since   4.6.0
 * @package Textfilter
 */

namespace Textpattern\Textfilter;

class Nl2Br extends Base implements TextfilterInterface
{
    /**
     * Constructor.
     */

    public function __construct()
    {
        parent::__construct(CONVERT_LINEBREAKS, gTxt('convert_linebreaks'));
    }

    /**
     * Filter.
     *
     * @param string $thing
     * @param array  $options
     */

    public function filter($thing, $options)
    {
        parent::filter($thing, $options);

        return nl2br(trim($thing));
    }
}

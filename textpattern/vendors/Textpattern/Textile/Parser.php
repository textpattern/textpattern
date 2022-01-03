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
 * @since   4.6.0
 * @package Textile
 */

namespace Textpattern\Textile;

/**
 * Textile parser.
 *
 * @since   4.6.0
 * @package Textile
 */
class Parser extends \Netcarver\Textile\Parser
{
    /**
     * Constructor.
     *
     * @param string|null $doctype The output doctype
     */

    public function __construct($doctype = null)
    {
        if ($doctype === null) {
            $doctype = get_pref('doctype', 'html5');
        }

        parent::__construct($doctype);
        $this->setImagePrefix(ihu)->setLinkPrefix(hu)->setRawBlocks(true);
        $this->setSymbol('quote_single_open', gTxt('txt_quote_single_open'));
        $this->setSymbol('quote_single_close', gTxt('txt_quote_single_close'));
        $this->setSymbol('quote_double_open', gTxt('txt_quote_double_open'));
        $this->setSymbol('quote_double_close', gTxt('txt_quote_double_close'));
    }

    /**
     * Parses content in a restricted mode.
     *
     * @param  string|null $text    The input document in textile format
     * @param  bool|null   $lite    Optional flag to switch the parser into lite mode
     * @param  bool|null   $noimage Optional flag controlling the conversion of images into HTML img tags
     * @param  string|null $rel     Relationship to apply to all generated links
     * @return string The text from the input document
     */

    public function textileRestricted($text, $lite = null, $noimage = null, $rel = null)
    {
        if ($lite === null) {
            $lite = !get_pref('comments_use_fat_textile', 1);
        }

        if ($noimage === null) {
            $noimage = get_pref('comments_disallow_images', 1);
        }

        if ($rel === null && get_pref('comment_nofollow', 1)) {
            $rel = 'nofollow';
        }

        return parent::textileRestricted($text, $lite, $noimage, $rel);
    }
}

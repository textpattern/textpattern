<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2018 The Textpattern Development Team
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
 * Pages
 *
 * Manages skins related pages.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Pages extends AssetBase
    {
        protected static $asset = 'page';
        protected static $dir = 'pages';
        protected static $table = 'txp_page';
        protected static $tableCols;
        protected static $contentsCol = 'user_html';
        protected static $essential = array(array('default', 'error_default'));
    }
}

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
 * Css
 *
 * Manages stylesheets.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Css extends AssetBase implements CssInterface
    {
        protected static $event = 'css';
        protected static $extension = 'css';
        protected static $table = 'txp_css';
        protected static $dir = 'styles';
        protected static $fileContentsField = 'css';
        protected static $essential = array(
            array(
                'name' => 'default',
                'css' => '/* Here goes the default rendering contents of the css tag. '
                        .'See https://docs.textpattern.io/tags/css */'
            ),
        );

        /**
         * $infos+$name properties setter.
         *
         * @param  string $name CSS name;
         * @param  string $css  CSS contents;
         * @return object $this The current class object (chainable).
         */

        public function setInfos(
            $name,
            $css = null
        ) {
            $name = $this->setName($name)->getName();

            $this->infos = compact('name', 'css');

            return $this;
        }
    }
}

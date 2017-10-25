<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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
 * Skin Interface
 *
 * Implemented by SkinBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    interface SkinInterface
    {
        /**
         * Constructor
         *
         * @param  string $skin  Skin name;
         * @param  array  $infos Skin infos;
         */

        public function __construct($skin = null);

        /**
         * Tells whether the skin row exists or not.
         *
         * @return bool
         */

        public function skinIsInstalled($copy = false);

        /**
         * Pseudo locks the skin directory by adding a 'lock' directory
         * and setting the $locked property.
         *
         * @return bool
         */

        public function lockSkin();

        /**
         * Creates a directory.
         *
         * @throws \Exception
         */

        public function mkDir($path = null);

        /**
         * Pseudo unlocks the skin directory by emoving the 'lock' directory
         * and resetting the $locked property.
         *
         * @return bool
         */

        public function unlockSkin();

        /**
         * Removes a directory.
         *
         * @throws \Exception
         */

        public function rmDir($path = null);

        /**
         * Gets the skin or asset related directory path.
         *
         * @param  string $basename String to add to the skin path ($dir property by default);
         * @return string           The path
         */

        public function getPath($basename = null);

        /**
         * Creates the skin and/or its asset related templates.
         *
         * @throws \Exception
         */
    }
}

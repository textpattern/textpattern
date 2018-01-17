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
 * MainInterface
 *
 * Implemented by SkinBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    interface MainInterface
    {
        /**
         * $defaultAssets property getter
         *
         * @return array
         */

        public function getSkinsAssets();

        /**
         * $defaultAssets property getter
         *
         * @return array
         */

        public static function getDefaultAssets();

        /**
         * $file property getter
         *
         * @return string The main skins JSON filename.
         */

        public static function getfile();

        /**
         * $SkinsAssets property setter.
         *
         * @param  array  $skins  See __construct();
         * @param  array  $assets See __construct();
         * @return object         $this.
         */

        public function setSkinsAssets($skins, $assets = null);

        /**
         * Whether a skin is used by a section or not.
         *
         * @param  string $skin A skin name.
         * @return bool         false on error.
         */

        public function isInUse($skin);

        /**
         * Get the most recently used skin from the prefs.
         *
         * @return string The skin name
         */

        public static function getCurrent();

        /**
         * Set the skin to the given theme.
         *
         * If $skin is empty, it looks up the theme as used by the default Section.
         * It also forces a refresh of the on-page prefs array.
         *
         * @param  string $skin A skin name.
         * @return bool   false on error.
         */

        public static function setCurrent($skin = null);

        /**
         * Get a skin row from the DB.
         *
         * @return array Associative array of skins and their templates rows
         *               as usual associative arrays.
         */

        public function getRows($skins = null);

        /**
         * Create skins and their defined related assets.
         *
         * @param  array $rows Associative array of the following txp_skin table related fields:
         *                    'title', 'version', 'description', 'author', 'author_uri';
         * @param  mixed $from A skin name or an array of names parallel to the $skins array
         *                     passed to the constructor or to the setSkinsAssets() method.
         * @return array       Created skins.
         */

        public function create($rows, $from = null);

        /**
         * Edit skins and their defined related assets.
         *
         * @param  array $row Associative array of the txp_skin table related fields:
         *                    'name', 'title', 'version', 'description', 'author', 'author_uri';
         * @return array      Updated skins.
         */

        public function edit($row);

        /**
         * Duplicate skins and their defined related assets.
         *
         * @return array Duplicated skins.
         */

        public function duplicate();

        /**
         * Import skins and their defined related assets.
         *
         * @param  bool  $clean    Whether to remove extra templates or not;
         * @param  bool  $override Whether to update/override a duplicated skin or not;
         * @return array           Imported skins.
         */

        public function import($clean = true, $override = false);

        /**
         * Export skins and their defined related assets.
         *
         * @param  bool  $clean Whether to remove extra templates or not;
         * @return array        Exported skins.
         */

        public function export($clean = true);

        /**
         * Delete skins and their defined related assets.
         *
         * @return array Deleted skins.
         */

        public function delete();
    }
}

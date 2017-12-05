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
 * AssetInterface
 *
 * Implemented by AssetBase
 *
 * @since   4.7.0
 * @package Skin
 * @see     SkinsInterface
 */

namespace Textpattern\Skin {

    interface AssetInterface
    {
        /**
         * Constructor
         *
         * @param array $skins     Set the related parent property.
         * @param array $templates Skins related template names (all by default).
         */

        public function __construct($skins = null, $templates = array());

        /**
         * Set the $templates property according to the $skins.
         *
         * @param  array  $templates Template names as stored in the $defaultTemplates property of the class.
         * @return object $this.
         */

        public function setSkinsTemplates($skins, $templates = null);

        /**
         * get the template names stored in the $templates property.
         *
         * @return array Template names.
         */

        public function getTemplateNames($skin);

        /**
         * Creates skin templates.
         *
         * @return array Created skins.
         */

        public function create();

        /**
         * Changes templates related skin
         *
         * @param  string $from The skin name from which templates are adopted.
         * @return array        Adopted skins.
         */

        public function adopt($from);

        /**
         * Import the skin related templates.
         *
         * @param  string $clean Whether to remove extra templates or not.
         * @return array         Imported skins.
         */

        public function import($clean = true);

        /**
         * Gets a new asset iterator instance.
         *
         * @param  array  $templates Template names;
         * @param  array  $subdir    A skin subdirectory.
         * @return object            RecursiveIteratorIterator
         */

        public static function getRecDirIterator($path, $templates = null);

        /**
         * Drops obsolete template rows.
         *
         * @param  array $not An array of template names to NOT drop;
         * @return bool
         */

        public static function dropRemovedFiles($not);

        /**
         * Duplicates the skin related templates.
         *
         * @return bool
         */

        public function duplicate($to);

        /**
         * Gets skins asset related templates rows.
         *
         * @return array Associative array of skins and their templates.
         */

        public function getRows($skins = null);

        /**
         * Exports the skin related templates.
         *
         * @param  string $clean Whether to remove extra templates or not.
         * @return array         Exported skins.
         */

        public function export($clean = true);

        /**
         * Unlinks obsolete template files.
         *
         * @param  array $not An array of template names to NOT unlink;
         * @return array      NOT removed templates;
         *                    thus an empty array means everything worked as expected.
         */

        public function unlinkRemovedRows($skin, $not);

        /**
         * Deletes the skin related templates.
         *
         * @return array Deleted skins.
         */

        public function delete();
    }
}

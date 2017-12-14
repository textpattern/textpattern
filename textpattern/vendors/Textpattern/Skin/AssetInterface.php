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
         * @param array $templates $skins parallel array of templates grouped by types.
         */

        public function __construct($skins = null, $templates = null)

        /**
         * $skinsTemplates property setter.
         *
         * @param  array  $skins     Set the related parent property.
         * @param  array  $templates $skins parallel array of templates grouped by types.
         * @return object $this
         */

        public function setSkinsTemplates($skins, $templates = null);

        /**
         * $skinsTemplates property getter.
         *
         * @return array Associative array of skins and their related templates grouped by types.
         */

        public function getSkinsTemplates()

        /**
         * Gets the template names defined for a defined skin.
         *
         * @return array Template names.
         */

        public function getTemplateNames($skin);

        /**
         * Gets the asset related essential template names from the $essential property.
         *
         * @return array Template names.
         */

        public function getEssentialNames();

        /**
         * Gets the asset related essential template types from the $essential property.
         *
         * @param  array $name A template name.
         * @return mixed the $name related type if the arg is set or an array of all types.
         */

        public function getEssentialTypes($name);

        /**
         * $dir property getter.
         *
         * @return string the asset related directory name.
         */

        public static function getDir()

        /**
         * $subdirCol property getter.
         *
         * @return string The DB column associated to subdirectories when applied.
         */

        public static function getSubdirCol()

        /**
         * $contentsCol property getter.
         *
         * @return string The DB column string the asset related main contents.
         */

        public static function getContentsCol()
        {
            return static::$contentsCol;
        }

        /**
         * $asset property getter.
         *
         * @return string The textpack related string used for the current asset.
         */

        public static function getAsset()
        {
            return static::$asset;
        }

        /**
         * $Extension property getter.
         *
         * @return string The current asset files related extension.
         */

        public static function getExtension()
        {
            return static::$extension;
        }

        /**
         * Creates skins templates.
         *
         * @return array Created skins.
         */

        public function create();

        /**
         * Changes the templates related skin.
         * Fires on after a skin update to keep templates associated with the right skin.
         *
         * @param  array $from The skin (old)names from which templates are adopted.
         *                     The array must be parallel to the $skins array
         *                     passed to the constructor or the setSkinsAssets() method.
         * @return array       Adopted skins.
         */

        public function adopt($from);

        /**
         * Imports skins templates.
         *
         * @param  string $clean Whether to remove extra templates or not.
         * @return array         Imported skins.
         */

        public function import($clean = true);

        /**
         * Duplicates skins templates.
         *
         * @param  array $to The skin (new)names to which templates are duplicated.
         *                   The array must be parallel to the $skins array
         *                   passed to the constructor or the setSkinsAssets() method.
         * @return array     Duplicated skins.
         */

        public function duplicate($to);

        /**
         * Exports skins templates.
         *
         * @param  string $clean Whether to remove extra template files or not.
         * @return array         Exported skins.
         */

        public function export($clean = true);

        /**
         * Deletes skins templates.
         *
         * @return array Deleted skins.
         */

        public function delete();
    }
}

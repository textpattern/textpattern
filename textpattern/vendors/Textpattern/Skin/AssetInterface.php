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
         * @param array $skins     A skin name or an array of names.
         * @param array $templates A templates array or a $skins parallel
         *                         array of templates grouped by types/subfolders.
         *                         If no type apply, just nest the templates array
         *                         into another one which simulates a abstract type.
         */

        public function __construct($skins = null, $templates = null);

        /**
         * $skinsTemplates property setter.
         *
         * @param  array  $skins     See __construct().
         * @param  array  $templates See __construct().
         * @return object $this
         * @see           __construct()
         */

        public function setSkinsTemplates($skins, $templates = null);

        /**
         * $skinsTemplates property getter.
         *
         * @return array Associative array of skins and their templates
         *               grouped by types/subfolders.
         * @see          setSkinsTemplates()
         */

        public function getSkinsTemplates();

        /**
         * Gets the template names defined for the provided
         * skin in the $skinsTemplates property.
         *
         * @param string A skin name.
         * @return array Template names.
         */

        public function getTemplateNames($skin);

        /**
         * Gets the asset essential template names from the $essential property.
         *
         * @return array Template names.
         */

        public static function getEssentialNames();

        /**
         * Gets the asset related essential template type(s)
         * from the $essential property.
         *
         * @param  array $name A template name.
         * @return mixed The $name related type if the arg is set
         *               or an array of all types.
         */

        public static function getEssentialTypes($name);

        /**
         * $dir property getter.
         *
         * @return string the asset related directory name.
         */

        public static function getDir();

        /**
         * $subdirCol property getter.
         *
         * @return string The DB column associated to subdirectories when applied.
         */

        public static function getSubdirCol();

        /**
         * $contentsCol property getter.
         *
         * @return string The DB column name used to store the main contents.
         */

        public static function getContentsCol();

        /**
         * $asset property getter.
         *
         * @return string The textpack related string used for the current asset.
         */

        public static function getAsset();

        /**
         * $Extension property getter.
         *
         * @return string The current asset files related extension.
         */

        public static function getExtension();

        /**
         * Creates skins templates.
         *
         * @return array Created skins.
         */

        public function create();

        /**
         * Imports skins templates.
         *
         * @param  string $clean Whether to remove extra template rows or not.
         * @return array         Imported skins.
         */

        public function import($clean = true);

        /**
         * Duplicates skins templates.
         *
         * @param  array $to The skin (new)names to which templates need to be duplicated.
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

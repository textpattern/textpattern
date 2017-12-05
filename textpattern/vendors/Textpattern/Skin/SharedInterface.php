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
 * SharedInterface
 *
 * Implemented by SkinBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    interface SharedInterface
    {
        /**
         * Constructor.
         *
         * @param string $skins Skin names.
         */

        public function __construct($skins = null);

        /**
         * Getter for the $table property.
         *
         * @return string Table name.
         */

        public static function getTable();

        /**
         * Gets the class related table column names.
         *
         * @param  array $exclude Column names.
         * @return array Column names
         */

        public static function getTableCols($exclude = array('lastmod'));

        /**
         * Setter for the $installed property.
         *
         * @param array Associative array of skin names and their related titles.
         */

        public static function setInstalled($skins);

        /**
         * Getter for the $installed property.
         *
         * @return array Associative array of installed skin names
         *                           and their related titles.
         */

        public static function getInstalled();

        /**
         * Whether a skin is installed or not.
         *
         * @param array A skin name.
         * @return bool
         */

        public static function isInstalled($skin);

        /**
         * Getter for the $validNamePattern property.
         *
         * @return string Regular Expression.
         */

        public static function getValidNamePattern();

        /**
         * Whether a string match the $validNamePattern property or not.
         *
         * @param  string A skin/template name.
         * @return bool
         */

        public static function isValidName($name);

        /**
         * Gets the skin names from the $skinsAssets/$skinsTemplates property.
         *
         * @return array Skin names.
         */

        public function getSkins();

        /**
         * Gets the position of the current skin
         * in the $skinsAssets/$skinsTemplates property.
         *
         * @return int The skin position.
         */

        public function getSkinIndex($skin);

        /**
         * Cleans skin names.
         *
         * @param  string $string The string to clean.
         * @return string         Cleaned string.
         */

        public static function sanitize($string);

        /**
         * Inserts or updates all asset related templates at once.
         *
         * @param  array $fields The template related database fields;
         * @param  array $values SQL VALUES as an array of group of values;
         * @param  bool  $update Whether to update rows on duplicate keys or not;
         * @return bool          false on error.
         */

        public function insert($fields, $values, $update = false);

        /**
         * Checks if a directory or a file exists.
         *
         * @return bool
         */

        public static function isType($path);

        /**
         * Checks if a skin directory exists and is readable.
         *
         * @return bool
         */

        public static function isReadable($path = null);

        /**
         * Checks if the Skin directory exists and is writable;
         * if not, creates it.
         *
         * @param  string $path See getPath().
         * @return bool
         */

        public static function isWritable($path = null);

        /**
         * Pseudo locks the skin directory by adding a 'lock' directory
         * and setting the $locked property.
         * Locking is used to avoid conflicts on import/export.
         *
         * @return bool
         */

        public function lock($skin);

        /**
         * Creates a directory.
         *
         * @return bool
         */

        public static function mkDir($path = null);

        /**
         * Pseudo unlocks the skin directory by removing the 'lock' directory
         * and resetting the $locked property.
         * Locking is used to avoid conflicts on import/export.
         *
         * @return bool
         */

        public function unlock($skin);

        /**
         * Removes a directory.
         *
         * @return bool
         */

        public static function rmDir($path = null);

        /**
         * {@inheritdoc}
         */

        public static function getBasePath();

        /**
         * Gets the skin or asset related directory path.
         *
         * @param  string $basename String to add to the skin path ($dir property by default);
         * @return string           The path
         */

        public static function getPath($path);

        /**
         * Return results and resets the related property.
         *
         * @param  array  $status Type of results to output (all by default);
         * @param  string $output Output rendering;
         * @return mixed          UI message or associative array if set to 'raw'.
         */

        public function getResults($status = array('success', 'warning', 'error'), $output = null);
    }
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
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
 * Asset Interface
 *
 * Implemented by AssetBase
 *
 * @since   4.7.0
 * @package Skin
 * @see     MainInterface
 */

namespace Textpattern\Skin {

    interface AssetInterface extends MainInterface
    {
        /**
         * Constructor
         *
         * @param string       $skin      The asset related skin name (set the related parent property)
         * @param string       $stamp     The asset related skin infos (set the related parent property)
         * @param string|array $templates Restricts import to provided template name(s)
         */

        public function __construct($skin = null, $infos = null, $templates = null);

        /**
         * Get the essential/default templates.
         *
         * @return array Template names (or names => type for forms).
         */

        public static function getEssential();

        /**
         * Gets a new asset iterator instance.
         *
         * @return RecursiveIteratorIterator
         */

        public function getRecDirIterator();

        /**
         * Inserts or updates all asset related templates at once.
         *
         * @param  array $fields The template related database fields;
         * @param  array $values SQL VALUES as an array of group of values;
         * @param  bool  $update Whether to update rows on duplicate keys or not;
         * @return bool  False on error.
         */

        public function insertTemplates($fields, $values, $update = false);

        /**
         * Drops obsolete template rows.
         *
         * @param  array $not An array of template names to NOT drop;
         * @return bool  False on error.
         */

        public function dropRemovedFiles($not);

        /**
         * Get skin asset related templates rows.
         *
         * @throws \Exception
         */

        public function getTemplateRows();

        /**
         * Exports a skin asset related template row.
         *
         * @param  array      $row A template row as an associative array
         * @throws \Exception
         */

        public function exportTemplate($row);

        /**
         * Unlinks obsolete template files.
         *
         * @param  array $not An array of template names to NOT unlink;
         * @return bool  False on error.
         */

        public function unlinkRemovedRows($not);

        /**
         * Deletes skin asset related template rows.
         *
         * @throws \Exception
         */

        public function deleteTemplates();
    }
}

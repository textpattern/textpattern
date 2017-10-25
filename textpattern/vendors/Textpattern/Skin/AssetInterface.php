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
 * Asset Interface
 *
 * Implemented by AssetBase
 *
 * @since   4.7.0
 * @package Skin
 * @see     MainInterface
 */

namespace Textpattern\Skin {

    interface AssetInterface
    {
        /**
         * Constructor
         *
         * @param string $skin The asset related skin name (set the related parent property)
         */

        public function __construct($skin = null);

        /**
         * Gets the essential/default templates.
         *
         * @return array Template names (or names => type for forms).
         */

        public static function getEssential();

        /**
         * Creates skin templates
         *
         * @param array $templates Template names.
         * @throws \Exception
         */

        public function create($templates = null);

        /**
         * Changes templates related skin
         *
         * @param string $name      The new skin name.
         * @param array  $templates Template names.
         * @throws \Exception
         */

        public function edit($name, $templates = null);

        /**
         * Import the skin related templates.
         *
         * @param string $clean     Whether to remove extra templates or not.
         * @param array  $templates Template names.
         * @throws \Exception
         */

        public function import($clean = true, $templates = null);

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
         * Re-imports the skin related templates.
         *
         * @param string $clean     Whether to remove extra templates or not.
         * @param array  $templates Template names.
         * @throws \Exception
         */

        public function update($clean = true, $templates = null);

        /**
         * Duplicates the skin related templates.
         *
         * @param array $templates Template names.
         * @throws \Exception
         */

        public function duplicate($templates = null);

        /**
         * Get skin asset related templates rows.
         *
         * @throws \Exception
         */

        public function getTemplateRows($templates);

        /**
         * {@inheritdoc}
         */

        public function getWhereClause($templates = null);

        /**
         * Exports the skin related templates.
         *
         * @param string $clean     Whether to remove extra templates or not.
         * @param array  $templates Template names.
         * @throws \Exception
         */

        public function export($clean = true, $templates = null);

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
         * Deletes the skin related templates.
         *
         * @param array $templates Template names.
         * @throws \Exception
         */

        public function delete($templates = null);

        /**
         * Deletes skin asset related template rows.
         *
         * @throws \Exception
         */

        public function deleteTemplates();
    }
}

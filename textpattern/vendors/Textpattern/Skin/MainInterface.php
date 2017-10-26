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
 * Main Interface
 *
 * Implemented by Main and Skin.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    interface MainInterface
    {
        /**
         * Creates a skin and its assets.
         *
         * @param string $title       The skin title;
         * @param string $version     The skin version;
         * @param string $description The skin description;
         * @param string $author      The skin author;
         * @param string $author_uri  The skin author URL;
         * @param mixed  $assets      The skin assets to create.
         * @throws \Exception
         */

        public function create(
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null,
            $assets = null
        );

        /**
         * Edits a skin and its assets.
         *
         * @param string $name        The skin new name;
         * @param string $title       The skin new title;
         * @param string $version     The skin new version;
         * @param string $description The skin new description;
         * @param string $author      The skin new author;
         * @param string $author_uri  The skin new author URL;
         * @throws \Exception
         */

        public function edit(
            $name = null,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null
        );

        /**
         * Duplicates a skin and its assets.
         *
         * @param mixed $assets The skin assets to duplicate.
         * @throws \Exception
         */

        public function duplicate($assets = null);

        /**
         * Duplicates a skin and its assets from new skin data.
         *
         * @param string $name        The skin copy name;
         * @param string $title       The skin copy title;
         * @param string $version     The skin copy version;
         * @param string $description The skin copy description;
         * @param string $author      The skin copy author;
         * @param string $author_uri  The skin copy author URL;
         * @param mixed  $assets      The skin assets to duplicate.
         * @throws \Exception
         */

        public function duplicate_as(
            $name = null,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null,
            $assets = null
        );

        /**
         * Edits a skin and its assets.
         *
         * @param bool  $clean  Whether to remove extra templates or not;
         * @param mixed $assets The skin assets to import;
         * @throws \Exception
         */

        public function import($clean = true, $assets = null);

        /**
         * Updates a skin from its related directory.
         *
         * @param bool  $clean  Whether to remove extra templates or not;
         * @param mixed $assets The skin assets to import;
         * @throws \Exception
         */

        public function update($clean = true, $assets = null);

        /**
         * Exports a skin and its assets.
         *
         * @param bool  $clean  Whether to remove extra templates or not;
         * @param mixed $assets The skin assets to import;
         * @throws \Exception
         */

        public function export($clean = true, $assets = null);

        /**
         * Deletes a skin and its assets.
         *
         * @throws \Exception
         */

        public function delete();
    }
}

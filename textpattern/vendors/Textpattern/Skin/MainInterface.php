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
         * Creates skins and their defined related assets.
         *
         * @param  array $row Associative array of the following txp_skin table related fields:
         *                    'title', 'version', 'description', 'author', 'author_uri';
         * @return array      Created skins.
         */

        public function create($row);

        /**
         * Edits skins and their defined related assets.
         *
         * @param  array $row Associative array of the txp_skin table related fields:
         *                    'name', 'title', 'version', 'description', 'author', 'author_uri';
         * @return array      Updated skins.
         */

        public function edit($row);

        /**
         * Duplicates skins and their defined related assets.
         *
         * @return array Duplicated skins.
         */

        public function duplicate();

        /**
         * Imports skins and their defined related assets.
         *
         * @param  bool  $clean    Whether to remove extra templates or not;
         * @param  bool  $override Whether to update/override a duplicated skin or not;
         * @return array           Imported skins.
         */

        public function import($clean = true, $override = false);

        /**
         * Exports skins and their defined related assets.
         *
         * @param  bool  $clean Whether to remove extra templates or not;
         * @return array        Exported skins.
         */

        public function export($clean = true);

        /**
         * Deletes skins and their defined related assets.
         *
         * @return array Deleted skins.
         */

        public function delete();
    }
}

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
 * Skins Interface
 *
 * Implemented by Skins and Skin.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    interface SkinsInterface
    {
        /**
         * Duplicates a skin and its assets.
         *
         * @param  mixed $assets The skin assets to duplicate (see create(), all if not set).
         * @return false on error.
         */

        public function duplicate($assets = null);

        /**
         * Updates a skin from its related directory.
         *
         * @param  bool  $clean  Whether to remove extra templates or not;
         * @param  mixed $assets The skin assets to update (see create(), all if not set).
         * @return false on error.
         */

        public function update($clean = true, $assets = null);

        /**
         * Exports a skin and its assets.
         *
         * @param  bool  $clean  Whether to remove extra templates or not;
         * @param  mixed $assets The skin assets to export (see create(), all if not set).
         * @return false on error.
         */

        public function export($clean = true, $assets = null);

        /**
         * Deletes a skin and its assets.
         *
         * @return false on error.
         */

        public function delete();
    }
}

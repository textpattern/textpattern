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
 * Implemented by Main and SkinInterface.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    interface MainInterface
    {
        /**
         * Creates the skin(s) and/or its asset related templates.
         *
         * @throws \Exception
         */

        public function create();

        /**
         * Edits the skin(s) and/or its asset related templates.
         *
         * @throws \Exception
         */

        public function edit();

        /**
         * Creates a time stamped copy of the skin(s) and/or its asset related template rows.
         *
         * @throws \Exception
         */

        public function duplicate();

        /**
         * Imports the skin(s) and/or its asset related templates from the related directory(ies).
         *
         * @param  bool $clean whether to remove obsolete files or not.
         * @throws \Exception
         */

        public function import($clean = true);

        /**
         * Updates/overrides the skin(s) and/or its asset related templates from the related directory(ies).
         *
         * @param  bool $clean whether to remove obsolete files or not.
         * @throws \Exception
         */

        public function update($clean = true);

        /**
         * Exports the skin(s) and/or its asset related templates from the database.
         *
         * @param  bool $clean whether to remove obsolete files or not.
         * @param  bool $copy whether to time stamped the exported directory or not.
         * @throws \Exception
         */

        public function export($clean = true, $copy = false);

        /**
         * Deletes The skin and/or its asset related templates.
         *
         * @throws \Exception
         */

        public function delete();
    }
}

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
 * Main Base
 *
 * Extended by Main and skinBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    abstract class MainBase implements MainInterface
    {
        /**
         * Caches the installed skins.
         *
         * @var array Associative array of skin names and their related title.
         * @see Main::getInstalled()
         */

        protected static $installed = null;

        /**
         * Caches the uploaded skin directories.
         *
         * @var array Associative array of skin names and their related title.
         * @see Main::getDirectories()
         */

        protected static $directories = null;

        /**
         * {@inheritdoc}
         */

        abstract public function create();

        /**
         * {@inheritdoc}
         */

        abstract public function edit();

        /**
         * {@inheritdoc}
         */

        abstract public function duplicate($as);

        /**
         * {@inheritdoc}
         */

        abstract public function import($clean = true);

        /**
         * {@inheritdoc}
         */

        abstract public function update($clean = true);

        /**
         * {@inheritdoc}
         */

        abstract public function export($clean = true, $as = null);

        /**
         * {@inheritdoc}
         */

        abstract public function delete();
    }
}

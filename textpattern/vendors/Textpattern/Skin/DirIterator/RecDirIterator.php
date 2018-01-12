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
 * RecDirIterator
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin\DirIterator {

    class RecDirIterator extends \RecursiveDirectoryIterator
    {
        /**
         * {@inheritdoc}
         */

        public function __construct($path, $flags = null)
        {
            if ($flags === null) {
                $flags = \FilesystemIterator::FOLLOW_SYMLINKS |
                         \FilesystemIterator::CURRENT_AS_SELF |
                         \FilesystemIterator::SKIP_DOTS;
            }

            parent::__construct($path, $flags);
        }

        /**
         * Gets the template contents.
         *
         * @throws \Exception
         */

        public function getContents()
        {
            $contents = file_get_contents($this->getPathname());

            if ($contents !== false) {
                return preg_replace('/[\r|\n]+$/', '', $contents);
            }

            throw new \Exception('Unable to read: '.$this->getName());
        }

        /**
         * Gets JSON file contents as an object.
         *
         * @return array
         * @throws Exception
         */

        public function getJSONContents()
        {
            return @json_decode($this->getContents(), true);
        }

        /**
         * Gets the template name.
         *
         * @return string
         */

        public function getName()
        {
            return pathinfo($this->getFilename(), PATHINFO_FILENAME);
        }

        /**
         * Gets the form Type from its path.
         *
         * @return string
         */

        public function getDir()
        {
            return basename($this->getPath());
        }
    }
}

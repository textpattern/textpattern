<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * Recursive directory iterator
 *
 * <code>
 * $files = new \Textpattern\Iterator\RecDirIterator($dirPath);
 *
 * foreach ($files as $file) {
 *     echo $file->getPathname();
 * }
 * </code>
 *
 * @since   4.7.0
 * @package Iterator
 */

namespace Textpattern\Iterator {

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
            $pathname = $this->getPathname();
            $contents = file_get_contents($pathname);

            if ($contents !== false) {
                return preg_replace('/[\r|\n]+$/', '', $contents);
            }

            throw new \Exception('Unable to read: '.$pathname);
        }

        /**
         * Gets JSON file contents as an object.
         *
         * @return array
         */

        public function getJSONContents()
        {
            return @json_decode($this->getContents(), true);
        }
    }
}

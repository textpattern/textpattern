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
 * Recursive filter iterator
 *
 * <code>
 * $files = \Txp::get('Textpattern\Iterator\RecDirIterator', $root);
 * $filter = \Txp::get('Textpattern\Iterator\RecFilterIterator', $files)->setNameIn($nameIn);
 * $filteredFiles = \Txp::get('Textpattern\Iterator\RecIteratorIterator', $filter);
 * $filteredFiles->setMaxDepth($maxDepth);
 *
 * foreach ($filteredFiles as $file) {
 *     echo $file->getPathname();
 * }
 * </code>
 *
 * @since   4.7.0
 * @package Iterator
 */

namespace Textpattern\Iterator {

    class RecFilterIterator extends \RecursiveFilterIterator
    {
        protected $names;

        /**
         * {@inheritdoc}
         */

         public function __construct(RecDirIterator $iterator)
         {
             parent::__construct($iterator);
         }

        /**
         * {@inheritdoc}
         */

        public function accept()
        {
            if ($this->isDir()) {
                return true;
            } else {
                $isValid = false;
                $names = $this->getNames();
                $filename = $this->getFilename();

                if ('.' !== substr($filename, 0, 1) &&
                    $this->isReadable() &&
                    ($this->isFile() || $this->isLink()) &&
                    (($names && in_array($filename, $names)) || !$names)
                ) {
                    return true;
                }
            }

            return false;
        }

        /**
         * $names property setter
         */

        public function setNames($names)
        {
            $this->names = $names;

            return $this;
        }

        /**
         * $names property getter
         */

        public function getNames()
        {
            return $this->names;
        }
    }
}

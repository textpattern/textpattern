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
        protected $filter;

        /**
         * Constructor
         *
         * @param object RecDirIterator $iterator Instance of RecDirIterator.
         * @param mixed                 $filter   Array of filenames or regEx as a string.
         */

        public function __construct(RecDirIterator $iterator, $filter)
        {
            parent::__construct($iterator);

            $this->setFilter($filter);
        }

        /**
         * {@inheritdoc}
         *
         * Get Children and pass the filter to them.
         */

        #[\ReturnTypeWillChange]
        public function getChildren()
        {
            return new self($this->getInnerIterator()->getChildren(), $this->getFilter());
        }

        /**
         * {@inheritdoc}
         */

        #[\ReturnTypeWillChange]
        public function accept()
        {
            return $this->isDir() ||
                   $this->isReadable() && ($this->isFile() || $this->isLink()) && $this->isValid();
        }

        /**
         * Whether the filename is valid according to the provided $filter property value.
         *
         * @return bool FALSE on error
         */

        public function isValid()
        {
            $filter = $this->getFilter();
            $filename = $this->getFilename();

            return is_array($filter) && in_array($filename, $filter) ||
                   !is_array($filter) && preg_match($filter, $filename);
        }

        /**
         * $filter property setter
         *
         * @param  mixed $filter Array of filenames or regEx as a string.
         * @return object $this.
         */

        public function setFilter($info)
        {
            $this->filter = $info;

            return $this;
        }

        /**
         * $names property getter
         *
         * @return mixed $filter Array of filenames or regEx as a string.
         */

        public function getFilter()
        {
            return $this->filter;
        }
    }
}

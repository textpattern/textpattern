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
 * RecRegexIterator
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin\DirIterator {

    class RecFilterIterator extends \RecursiveFilterIterator
    {
        protected $nameIn;

        /**
         * {@inheritdoc}
         */

         public function __construct(RecDirIterator $iterator, $nameIn = null)
         {
             $this->setNameIn($nameIn);

             parent::__construct($iterator);
         }

        /**
         * {@inheritdoc}
         */

        public function accept()
        {
            return $this->isDir() || $this->isValidTemplate();
        }

        /**
         * {@inheritdoc}
         */

        protected function setNameIn($names)
        {
            $this->nameIn = $names;

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        protected function getNameIn()
        {
            return $this->nameIn;
        }

        /**
         * Validates a template file name.
         *
         * @return bool
         */

        public function isValidTemplate()
        {
            $isValid = false;

            if (!$this->isDot() && $this->isReadable() && ($this->isFile() || $this->isLink())) {
                if ($this->getNameIn()) {
                    $isValid = (bool) in_array($this->getFilename(), $this->getNameIn());
                } else {
                    $isValid = true;
                }
            }

            return $isValid;
        }
    }
}

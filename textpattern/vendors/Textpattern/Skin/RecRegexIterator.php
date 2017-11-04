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
 * AssetFilterIterator
 *
 * Filters asset iterator results.
 *
 * This class iterates over template files.
 *
 * <code>
 * $filteredTemplates = new AssetFilterIterator(
 *    AssetIterator('/path/to/dir')
 * );
 * </code>
 *
 * @since   4.7.0
 * @package Skin
 * @see     \RecursiveAssetFilterIterator
 */

namespace Textpattern\Skin {

    class RecRegexIterator extends \RecursiveRegexIterator
    {
        /**
         * {@inheritdoc}
         */

        public function accept()
        {
            return $this->isDir() || $this->isValidTemplate();
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
                $isValid = (bool) preg_match(
                    self::getRegex(),
                    $this->getFilename()
                );
            }

            return $isValid;
        }
    }
}

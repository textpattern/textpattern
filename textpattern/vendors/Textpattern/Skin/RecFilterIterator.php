<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
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

    class RecFilterIterator extends \RecursiveFilterIterator
    {

        /**
         * Default regular expression pattern used
         * to validate template filenames.
         *
         * @var string
         */

        protected static $filePattern = '/^[a-z][a-z0-9_\-\.]{0,63}\.(txp|html)$/i';

        /**
         * Array of template names used
         * to validate template filenames.
         *
         * @var array
         */

        protected $templates = array();

        /**
         * Constructor
         *
         * @param RecDirIterator $iterator
         * @param string        $extension
         * @param string|array  $templates
         */

        public function __construct(
            RecDirIterator $iterator,
            $extension = null,
            $templates = null
        ) {
            parent::__construct($iterator);

            if (!empty($templates)) {
                $this->templates = $templates;
            }

            if ($extension !== null && $extension !== 'txp') {
                static::$filePattern = '/^[a-z][a-z0-9_\-\.]{0,63}\.('.$extension.')$/i';
            }
        }

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
                    static::$filePattern,
                    $this->getFilename()
                );

                if ($isValid && $this->templates) {
                    $isValid = in_array($this->getTemplateName(), $this->templates);
                }
            }

            return $isValid;
        }
    }
}

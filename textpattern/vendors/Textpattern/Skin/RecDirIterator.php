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

namespace Textpattern\Skin {

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

        public function getTemplateContents()
        {
            $contents = file_get_contents($this->getPathname());

            if ($contents !== false) {
                return preg_replace('/[\r|\n]+$/', '', $contents);
            }

            throw new \Exception('Unable to read: '.$this->getTemplateName());
        }

        /**
         * Gets JSON file contents as an object.
         *
         * @return array
         * @throws Exception
         */

        public function getTemplateJSONContents()
        {
            return @json_decode($this->getTemplateContents(), true);
        }

        /**
         * Gets the template name.
         *
         * @return string
         */

        public function getTemplateName()
        {
            return pathinfo($this->getFilename(), PATHINFO_FILENAME);
        }

        /**
         * Gets the form Type from its path.
         *
         * @return string
         */

        public function getTemplateDir()
        {
            $types = array_keys(get_form_types());
            $type = basename($this->getPath());

            if (in_array($type, $types)) {
                return $type;
            }

            return 'misc';
        }

        public function getTemplateInfo($type)
        {
            switch ($type) {
                case 'name':
                    return $this->getTemplateName();
                    break;
                case 'content':
                    return $this->getTemplateContents();
                    break;
                case 'dir':
                    return $this->getTemplateDir();
                    break;
                default:
                    return $this->getTemplateName();
                    break;
            }
        }
    }
}

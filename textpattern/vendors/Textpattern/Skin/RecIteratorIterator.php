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
 * AssetIterator
 *
 * This class iterates over template files.
 *
 * <code>
 * $templates = new RecursiveIteratorIterator(
 *     new AssetFilterIterator(
 *         new TemplateIterator('/path/to/dir')
 *     )
 * );
 * foreach ($templates as $template) {
 *     $template->getTemplateName();
 *     $template->getTemplateContents();
 * }
 * </code>
 *
 * @since   4.7.0
 * @package Skin
 * @see \RecursiveDirectoryIterator
 */

namespace Textpattern\Skin {

    class RecIteratorIterator extends \RecursiveIteratorIterator
    {
        /**
         * {@inheritdoc}
         *
         * @param int $depth Sets the MaxDepth property.
         */

        public function __construct(RecFilterIterator $iterator, $depth)
        {
            parent::__construct($iterator);
            parent::setMaxDepth($depth);
        }
    }
}

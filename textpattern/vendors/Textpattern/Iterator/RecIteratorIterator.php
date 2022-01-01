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
 * Recursive iterator iterator
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

    class RecIteratorIterator extends \RecursiveIteratorIterator
    {
    }
}

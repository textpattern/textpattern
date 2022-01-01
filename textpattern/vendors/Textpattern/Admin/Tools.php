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
 * Remove files and directories.
 *
 * <code>
 * Txp::get('\Textpattern\Admin\Tools')->removeFiles(txpath, 'setup');
 * </code>
 *
 * @since   4.6.0
 * @package Admin\Tools
 */

namespace Textpattern\Admin;

class Tools
{
    /**
     * Warnings.
     *
     * @var array
     */

    private $warnings;

    /**
     * Constructor.
     */

    public function __construct()
    {
        $this->warnings = array();
    }

    /**
     * Removes files and directories.
     *
     * @param  string $root  The parent directory
     * @param  array  $files Files to remove
     * @return bool   The result
     */

    public static function removeFiles($root, $files = null)
    {
        if (!is_dir($root) || !is_writable($root)) {
            return false;
        } elseif (!isset($files)) {
            $files = array_diff(scandir($root), array('.', '..'));
        } elseif (!is_array($files)) {
            $files = do_list_unique($files);
        }

        $result = true;

        foreach ($files as $file) {
            $file = $root.DS.$file;

            if (is_file($file)) {
                $result &= unlink($file);
            } elseif ($result &= self::removeFiles($file)) {
                $result &= rmdir($file);
            }
        }

        return $result;
    }

    /**
     * Outputs warnings.
     *
     * @return array The warnings
     */

    public function getWarnings()
    {
        return $this->warnings;
    }
}

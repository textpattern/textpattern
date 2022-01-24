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
 * Treat file uploads.
 *
 * <code>
 * Txp::get('\Textpattern\Server\Files')->refactor($_FILES['thefile']);
 * </code>
 *
 * @since   4.7.0
 * @package Server
 */

namespace Textpattern\Server;

class Files
{
    /**
     * Temporary chunked upload directory.
     *
     * @var string
     */

    private $tempdir = false;

    /**
     * Constructor.
     */

    public function __construct()
    {
        global $tempdir;

        $this->tempdir = (!empty($tempdir) ? $tempdir : ini_get('upload_tmp_dir')) or $this->tempdir = sys_get_temp_dir();
    }

    /**
     * Transforms a multiple $_FILES entry into int-indexed array.
     *
     * @param  array $file The file
     * @return array of files
     */

    public function refactor(&$file)
    {
        $is_array = is_array($file['name']);

        if (empty($file['name']) || $is_array && empty($file['name'][0])) {
            return array();
        }

        $file_array = array();
        $file_count = $is_array ? count($file['name']) : 1;
        $file_keys = array_keys($file);

        for ($i = 0; $i < $file_count; $i++) {
            $file_array[$i] = array();

            foreach ($file_keys as $key) {
                $file_array[$i][$key] = $is_array ? $file[$key][$i] : $file[$key];
            }
        }

        return $file_array;
    }

    /**
     * Treats chunked file uploads.
     *
     * @param  array $file The file
     * @return bool
     */

    public function dechunk(&$file)
    {
        global $txp_user;
        // Chuncked upload, anyone?
        if (!empty($_SERVER['HTTP_CONTENT_RANGE'])
            && isset($_SERVER['CONTENT_LENGTH'])
            && preg_match('/\b(\d+)\-(\d+)\/(\d+)\b/', $_SERVER['HTTP_CONTENT_RANGE'], $match)) {
            extract($file);
            $tmpfile = build_file_path($this->tempdir, md5($txp_user.':'.$name).'.part');

            // Get the range of the file uploaded from the client
            list($range, $begin, $end, $filesize) = $match;

            if (is_file($tmpfile) && filesize($tmpfile) == $begin) {
                file_put_contents($tmpfile, fopen($tmp_name, 'r'), FILE_APPEND);
                unlink($tmp_name);

                // Stop here if the file is not completely loaded
                if ($end + 1 < $filesize) {
                    exit;
                } else {
                    $file['tmp_name'] = $tmpfile;
                    $file['size'] = filesize($tmpfile);
                }
            } elseif ($begin == 0) {
                shift_uploaded_file($tmp_name, $tmpfile);
                exit;
            } else { // Chunk error, clean up
                unlink($tmpfile);
                $file['size'] = 0;
            }

            return true;
        }

        return false;
    }
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2025 The Textpattern Development Team
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

define('txpinterface', 'cli');

if (php_sapi_name() !== 'cli') {
    die('command line only');
}

if (empty($argv[1])) {
    die("usage: {$argv[0]} <txpath> [all|core|admin-themes]\n");
}

$locs = empty($argv[2]) ? 'all' : $argv[2];

define('txpath', rtrim(realpath($argv[1]), '/'));

$event = '';
$prefs['enable_xmlrpc_server'] = true;
require_once(txpath.'/lib/constants.php');
require_once(txpath.'/lib/txplib_misc.php');
require_once(txpath.'/lib/txplib_admin.php');

$files = array();
$fpattern = '/.*\.(?:php|js)$/';

if ($locs === 'all' || $locs === 'core') {
    $destination = txpath;

    $files = files_to_checksum($destination, $fpattern);

    // Append root and rpc files.
    $files = array_merge($files, glob(txpath.DS.'..'.DS.'*.php'));
    $files = array_merge($files, glob(txpath.DS.'..'.DS.'rpc'.DS.'*.php'));

    // Remove setup and config-dist.php.
    $files = array_filter($files, function($e) { return (strpos($e, '/setup') === false); });
    $files = array_filter($files, function($e) { return (strpos($e, '/config-dist') === false); });

    // Remove admin-themes because they're checksummed independently.
    $files = array_filter($files, function($e) { return (strpos($e, '/admin-themes') === false); });

    // Output list.
    if ($files) {
        $files = prep_checksums($files);
        write_checksums($destination, $files);
        $files = calculate_checksums($files);
        write_checksums($destination, $files);
        echo "Checksums updated in ".$destination."\n";
    }
}

if ($locs === 'all' || $locs === 'admin-themes') {
    $destination = txpath.DS.'admin-themes';

    $iterator = new DirectoryIterator($destination);
    $themesInstalled = array();

    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isDir() && !$fileinfo->isDot()) {
            $themesInstalled[] = $fileinfo->getFilename();
        }
    }

    foreach ($themesInstalled as $themeName) {
        $endpoint = $destination.DS.$themeName;
        $files = files_to_checksum($endpoint, $fpattern);

        // Output list.
        if ($files) {
            $files = prep_checksums($files);
            write_checksums($endpoint, $files);
            $files = calculate_checksums($files);
            write_checksums($endpoint, $files);
            echo "Checksums updated in ".$endpoint."\n";
        }
    }
}

/**
 * Create a file list, including new matching files added to the repo
 *
 * @param  [type] $dir   [description]
 * @param  [type] $files [description]
 * @return [type]        [description]
 */
function prep_checksums($files)
{
    return array_map(function ($str) { return str_replace(txpath, '', $str.": ".str_repeat('a', CHECKSUM_BYTES)); }, $files);
}

/**
 * Commit the passed list of checksummed $files (prepped or computed) to the $dir
 *
 * @param  string $dir   Destination directory for the checksums.txt file
 * @param  string $files Checksummed files
 */
function write_checksums($dir, $files)
{
    file_put_contents($dir.DS.'checksums.txt', implode(n, $files));
}

/**
 * Recursively fetch files from the given root.
 *
 * Dot files and directories are skipped.
 *
 * @param  string $folder  Path to the start point (root directory to traverse)
 * @param  string $pattern Full regex filter to apply
 * @return array           List of files
 */
function files_to_checksum($folder, $pattern)
{
    $dir = new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS);
    $iter = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($iter, $pattern, RegexIterator::GET_MATCH);
    $fileList = array();

    foreach ($files as $file) {
        $fileList = array_merge($fileList, $file);
    }

    return $fileList;
}

/**
 * Recalculate checksums of all files in the destination folder.
 *
 * @return array List of files and their checksums
 */
function calculate_checksums($filter = array())
{
    $fileList = array();

    foreach (check_file_integrity(INTEGRITY_HASH, true) as $file => $hash) {
        if (empty($filter) || preg_grep('/^' . preg_quote($file, '/') . ':/', $filter)) {
            $fileList[] = "$file: $hash";
        }
    }

    return $fileList;
}

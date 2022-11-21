<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
    die("usage: {$argv[0]} <txpath> [update|rebuild]\n");
}

$action = empty($argv[2]) ? 'update' : $argv[2];

define('txpath', rtrim(realpath($argv[1]), '/'));
define('n', "\n");

$event = '';
$prefs['enable_xmlrpc_server'] = true;
require_once(txpath.'/lib/constants.php');
require_once(txpath.'/lib/txplib_misc.php');
require_once(txpath.'/lib/txplib_admin.php');
$files = array();
$destination = txpath.DS.'checksums.txt';

switch ($action) {
    case 'update':
        $files = calculate_checksums();
        break;
    case 'rebuild':
        $files = files_to_checksum(txpath, '/.*\.(?:php|js)$/');

        // Append root and rpc files.
        $files = array_merge($files, glob(txpath.DS.'..'.DS.'*.php'));
        $files = array_merge($files, glob(txpath.DS.'..'.DS.'rpc'.DS.'*.php'));

        // Remove setup and config-dist.php.
        $files = array_filter($files, function($e) { return (strpos($e, '/setup') === false); });
        $files = array_filter($files, function($e) { return (strpos($e, '/config-dist') === false); });

        // Output list.
        if ($files) {
            $files = array_map(function ($str) { return str_replace(txpath, '', $str.": ".str_repeat('a', 32)); }, $files);
            file_put_contents($destination, implode(n, $files));
            $files = calculate_checksums();
        }
        break;
}

if ($files) {
    file_put_contents($destination, implode(n, $files));
    echo "Checksums updated in ".$destination.".\n";
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
 * Recalculate checksums of all files in the destination file.
 *
 * @return array List of files and their checksums
 */
function calculate_checksums()
{
    $fileList = array();

    foreach (check_file_integrity(INTEGRITY_MD5) as $file => $md5) {
        $fileList[] = "$file: $md5";
    }

    return $fileList;
}

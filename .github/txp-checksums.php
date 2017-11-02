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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

define('txpinterface', 'cli');

if (php_sapi_name() !== 'cli') {
    die('command line only');
}

define('txpath', 'textpattern');
define('n', "\n");

// Find .php and .js files in `textpattern` dir
$files = glob_recursive('textpattern/*\.{php,js}', GLOB_BRACE);
$files = preg_replace('%^textpattern%', '', $files);
$files = array_flip($files);



$event = '';
$prefs['enable_xmlrpc_server'] = true;
require_once(txpath.'/lib/constants.php');
require_once(txpath.'/lib/txplib_misc.php');

$out = '';
foreach (check_file_integrity(INTEGRITY_MD5) as $file => $md5) {
    $out .= "$file: $md5".n;
    unset($files[$file]);
}
file_put_contents(txpath.'/checksums.txt', $out);
echo "Checksums updated.\n\n";

// New files
$out = '';
foreach ($files as $file => $val) {
    if (! preg_match('%^/(config-dist\.php|setup)%', $file)) {
        $out .= "$file: ".md5_file(txpath.'/'.$file).n;
    }
}
if ($out) {
    echo "New files without checksums:".n.$out.n;
    if (count($argv) > 1) {
        echo "Add new files to checksums.txt before release.".n.n;
        exit(127);
    }
}

exit;


function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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

if (php_sapi_name() !== 'cli') {
    die('command line only');
}

define('txpath', 'textpattern');
define('n', "\n");

// Find `.php` and `.js` files in `textpattern` directory.
$files = glob_recursive('textpattern/*\.{php,js}', GLOB_BRACE);
$files = preg_replace('%^textpattern%', '', $files);
$files = array_flip($files);

$cs = @file_get_contents(txpath.'/checksums.txt');
if (preg_match_all('%^(\S+):\s+([\da-f]+)%im', $cs, $mm)) {
    $out = '';

    foreach ($mm[1] as $key => $file) {
        $md5 = md5_file(txpath.$file);
        $out .= "$file: $md5".n;
        unset($files[$file]);
    }

    file_put_contents(txpath.'/checksums.txt', $out);
    echo "Checksums updated.\n\n";
}

// New files.
$out = '';

foreach ($files as $file => $val) {
    if (! preg_match('%^/(config-dist\.php|setup)%', $file)) {
        $out .= "$file: ".md5_file(txpath.'/'.$file).n;
    }
}

if ($out) {
    echo "New files without checksums:".n.$out.n;
    echo "Add new files to 'checksums.txt' before release.".n.n;
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

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

// Use buffering to ensure bogus whitespace is ignored.
ob_start(null, 2048);
@include '../../private/config.php';
ob_end_clean();

$multisite_admin_path = dirname(__FILE__, 2);

// Does 'vendors' symlink resolve to correct location?
if (!is_dir(realpath($multisite_admin_path.'/vendors'))) {

    // System is Windows if TRUE.
    define('IS_WIN', strpos(strtoupper(PHP_OS), 'WIN') === 0);

    // Directory separator character.
    define('DS', defined('DIRECTORY_SEPARATOR') ? DIRECTORY_SEPARATOR : (IS_WIN ? '\\' : '/'));

    // NO: 'vendor' symlink does not exist or does not resolve correctly.
    if (!isset($_POST['txp-root-path'])) {

        // No Textpattern root path specified: request path from user.
        echo '<h3>Textpattern root directory not found.</h3>'.
            '<p>Your symlinks may be missing, or your <code>sites</code> folder is in a non-standard location.</p>'.
            '<p>Your <code>sites</code> directory is: <code>'.dirname($multisite_admin_path, 2).'</code></p>'.
            '<p>Please enter the full path to the root directory of your textpattern installation.</p>'.
            '<form method="post" action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'">'.
            '<label for="txp-root-path">Path to your base textpattern directory: </label><br>'.
            '<input type="text" id="txp-root-path" name="txp-root-path" size="50"'.
            'placeholder="'.dirname($multisite_admin_path, 3).DS.'textpattern">'.
            '<button>Submit</button></form>';
        exit;
    } else {

        // User has specified Textpattern root path.
        $multisite_txp_root_path = rtrim(htmlspecialchars($_POST['txp-root-path']), '/');

        if (!is_dir(realpath($multisite_txp_root_path.DS.'textpattern'))) {

            // Root path incorrect, please retry -> back to beginning.
            echo '<h3>Textpattern root directory details incorrect</h3>'.
                '<p>The location <code>'.$multisite_txp_root_path.'</code> you specified does not appear to be the correct textpattern root path.</p>'.
                '<p>Please check your path and try again.</p>'.
                '<form><button>Go back</button></form>';
            exit;
        } else {

            // Root path is correct. Proceed to create symlinks.
            echo '<h3>Textpattern root directory found. Thank you!</h3>'.
                '<p>Path to sites directory: <code>'.dirname($multisite_admin_path, 2).'</code></p>'.
                '<p>Path to Textpattern directory: <code>'.$multisite_txp_root_path.'</code></p>'.
                '<h3>Creating symlinks</h3>';

            // Calculate relative path.
            $relative_path = find_relative_path($multisite_admin_path, $multisite_txp_root_path);

            // Required symlinks in /sites subdirectories.
            $symlinks = array(
                'admin-themes' => array(
                    'path'   => 'admin',
                    'is_dir' => true
                    ),
                'textpattern.js' => array(
                    'path'   => 'admin',
                    'is_dir' => false
                    ),
                'vendors' => array(
                    'path'   => 'admin',
                    'is_dir' => true
                    ),
                'themes' => array(
                    'path'   => 'public',
                    'is_dir' => true
                    )
            );
            $lastkey = array_pop(array_keys($symlinks));

            // Relative path from current /admin/setup directory to multisite base directory
            $symlink_relpath = '..'.DS.'..'.DS;

            // Create symlinks.
            foreach ($symlinks as $symlink => $atts) {
                $symlink_local = $atts['path'].DS.$symlink;
                $symlink_target = $relative_path.DS. ($atts["path"] === "admin" ? 'textpattern'.DS : '') .$symlink;

                unlink($symlink_relpath.$symlink_local);
                symlink($symlink_target, $symlink_relpath.$symlink_local);

                // symlink resolves successfully?
                if (realpath($symlink_relpath.$symlink_local)) {
                    echo '<p>Symlink created: <code>'.$symlink_local.'  »»»  '.readlink($symlink_relpath.$symlink_local).'</code></p>';
                } else {
                    // If unsuccessful, provide copy-and-paste symlink code to manually create symlinks.
                    if (!isset($title_shown)) {
                        echo "<p><strong style=\"color:red;\">Symlink(s) could not be created.</strong> Please create symlink(s) manually:</p>".
                            "<textarea cols=\"80\" rows=\"8\" style=\"font-family: monospace;\">cd ".dirname($multisite_admin_path)."/\n";
                        $title_shown = true;
                    }
                    if (IS_WIN) {
                        // "mklink [/D] link target" on windows with /D flag for directory symlink
                        echo "mklink ".($atts['is_dir'] === true ? "/D " : "").$atts['path'].DS.$symlink."  ".$symlink_target."\n";
                    } else {
                        // "ln -sf target link" on linux
                        echo "ln -sf ".$symlink_target."  ".$atts['path'].DS.$symlink."\n";
                    }
                    if ($symlink === $lastkey) {
                      echo "</textarea><p> </p>";
                    }
                }
            }

            // Proceed to regular multisite installation.
            echo '<form><button>Proceed</button></form>';
            exit;
        }
    }
} else {

    // YES: vendor symlink resolves correctly. Proceed with regular multisite installation.
    if (!defined('txpath')) {
        define("txpath", dirname(realpath(dirname(__FILE__).'/../vendors')));
    }

    define("is_multisite", true);
    define("multisite_root_path", dirname(__FILE__, 3));

    include txpath.'/setup/index.php';
}

/**
 * Finds relative file system path between two file system paths.
 * Based on: https://gist.github.com/ohaal/2936041
 *
 * @param  string  $frompath  Path to start from
 * @param  string  $topath    Path we want to end up in
 * @return string             Relative path from $frompath to $topath
 */

function find_relative_path($frompath, $topath)
{
    $from = explode(DS, $frompath); // Folders/File
    $to = explode(DS, $topath); // Folders/File
    $relpath = '';

    $i = 0;
    // Find how far the path is the same
    while (isset($from[$i]) && isset($to[$i])) {
        if ($from[$i] != $to[$i]) {
            break;
        }
        $i++;
    }
    $j = count($from) - 1;
    // Add '..' until the path is the same
    while ($i <= $j) {
        if (!empty($from[$j])) {
            $relpath .= '..'.DS;
        }
        $j--;
    }
    // Go to folder from where it starts differing
    while (isset($to[$i])) {
        if (!empty($to[$i])) {
            $relpath .= $to[$i].DS;
        }
        $i++;
    }

    // Strip last separator
    return substr($relpath, 0, -1);
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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

    $self_url = htmlspecialchars($_SERVER["PHP_SELF"]);

    $out[] = <<<eod
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow">
<title>Setup &#124; Textpattern CMS</title>
<link rel="stylesheet" href="setup-multisite.css">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
</head>
<body class="setup" id="page-setup">
<main class="txp-body">
<div class="txp-setup">
eod;

    // NO: 'vendor' symlink does not exist or does not resolve correctly.
    if (!isset($_POST['txp-root-path'])) {

        // No Textpattern root path specified: request path from user.
        $sites_dir = dirname($multisite_admin_path, 2);
        $txp_root_suggestion = dirname($multisite_admin_path, 3).DS.'textpattern';

        $out[] = <<<eod
<form class="prefs-form" method="post" action="{$self_url}">
<p class="alert-block error"><span class="ui-icon ui-icon-alert"></span> Textpattern root directory not found!</p>
<p>Your symbolic links (symlinks) may be missing, or your <code>sites</code> folder is in a non-standard location.</p>
<p>Your <code>sites</code> directory is: <code>{$sites_dir}</code></p>
<p>Please enter the full path to the root directory of your Textpattern installation.</p>
<p><label for="txp-root-path">Path to Textpattern root directory</label><br>
<input class="input-large" id="txp-root-path" name="txp-root-path" type="text" size="48" placeholder="{$txp_root_suggestion}" required="required"></p>
<p><input class="publish" name="Submit" type="submit" value="Submit"></p>
</form>
eod;
        echo join("\n", $out);
        exit("</div></main></body></html>");
    } else {

        // User has specified Textpattern root path.
        $multisite_txp_root_path = rtrim(htmlspecialchars($_POST['txp-root-path']), '/');

        if (!is_dir(realpath($multisite_txp_root_path.DS.'textpattern'))) {

            // Root path incorrect, please retry -> back to beginning.
            $out[] = <<<eod
<p class="alert-block error"><span class="ui-icon ui-icon-alert"></span> Textpattern root directory details incorrect!</p>
<p>The location <code>{$multisite_txp_root_path}</code> you specified does not appear to be the correct Textpattern root path.</p>
<p>Please go back, check your path and try again.</p>
<p><a class="navlink publish" href="{$self_url}">Go back</a></p>
eod;
            echo join("\n", $out);
            exit("</div></main></body></html>");
        } else {
            $sites_dir = dirname($multisite_admin_path, 2);

            // Root path is correct. Proceed to create symlinks.
            $out[] = <<<eod
<form class="prefs-form" method="post" action="{$self_url}">
<p class="alert-block success"><span class="ui-icon ui-icon-check"></span> Textpattern root directory found. Thank you!</p>
<p>Path to sites directory: <code>{$sites_dir}</code></p>
<p>Path to Textpattern directory: <code>{$multisite_txp_root_path}</code></p>
<h3>Creating symlinks</h3>
eod;
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
                /*
                'themes' => array(
                    'path'   => 'public',
                    'is_dir' => true
                    )
                */
            );
            $lastkey = array_pop(array_keys($symlinks));

            // Relative path from current /admin/setup directory to multisite base directory
            $symlink_relpath = '..'.DS.'..'.DS;

            // Create symlinks.
            foreach ($symlinks as $symlink => $atts) {
                $symlink_local = $atts['path'].DS.$symlink;
                $symlink_target = $relative_path.DS.($atts["path"] === "admin" ? 'textpattern'.DS : '').$symlink;

                unlink($symlink_relpath.$symlink_local);
                symlink($symlink_target, $symlink_relpath.$symlink_local);

                // symlink resolves successfully?
                if (realpath($symlink_relpath.$symlink_local)) {
                    $out[] = '<p>Symlink created: <code>'.$symlink_local.' <span class="success">&#8594;</span> '.readlink($symlink_relpath.$symlink_local).'</code></p>';
                } else {
                    // If unsuccessful, provide copy-and-paste symlink code to manually create symlinks.
                    if (!isset($title_shown)) {
                        $site_root = dirname($multisite_admin_path);
                        $out[] = <<<eod
                        <p class="alert-block error"><span class="ui-icon ui-icon-alert"></span> Symlinks could not be created.</p>
                        <p>Please create symlinks manually:</p>
                        <pre dir="ltr"><code>cd {$site_root}
eod;
                        $title_shown = true;
                    }

                    if (IS_WIN) {
                        // "mklink [/D] link target" on windows with /D flag for directory symlink
                        $out[] = "mklink ".($atts['is_dir'] === true ? "/D " : "").$atts['path'].DS.$symlink." ".$symlink_target;
                    } else {
                        // "ln -sf target link" on linux
                        $out[] = "ln -sf ".$symlink_target." ".$atts['path'].DS.$symlink;
                    }

                    if ($symlink === $lastkey) {
                        $out[] = "</code></pre>";
                    }
                }
            }

            // Proceed to regular multisite installation.
            $out[] = '<p><input class="publish" name="Submit" type="submit" value="Proceed"></p></form>';
            echo join("\n", $out);
            exit('</div></main></body></html>');
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
 * @param  string $frompath Path to start from
 * @param  string $topath   Path we want to end up in
 * @return string           Relative path from $frompath to $topath
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

<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2016 The Textpattern Development Team
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

/**
 * Collection of update tools.
 *
 * @package Update
 */

/**
 * Installs language strings from a file.
 *
 * This function imports language strings to the database
 * from a file placed in the ../lang directory.
 *
 * Running this function will delete any missing strings of any
 * language specific event that were included in the file. Empty
 * strings are also stripped from the database.
 *
 * @param   string $lang The language code
 * @return  bool TRUE on success
 * @package L10n
 */

function install_language_from_file($lang)
{
    global $DB;
    $lang_files = glob(txpath.'/lang/'.$lang.'.{txt,textpack}', GLOB_BRACE);

    if (!empty($lang_files)) {
        $lang_file = $lang_files[0];

        if (!is_file($lang_file) || !is_readable($lang_file)) {
            return false;
        }

        $file = @fopen($lang_file, "r");

        if ($file) {
            $lastmod = @filemtime($lang_file);
            $lastmod = date('YmdHis', $lastmod);
            $data = $core_events = array();
            $event = '';

            // TODO: General overhaul: Try install_textpack() to replace this parser; use safe_* db functions.
            while (!feof($file)) {
                $line = fgets($file, 4096);

                // Ignore empty lines and simple comments (any line starting
                // with #, not followed by @).
                if (trim($line) === '' || ($line[0] == '#' && $line[1] != '@' && $line[1] != '#')) {
                    continue;
                }

                // If available use the lastmod time from the file.
                if (strpos($line, '#@version') === 0) {
                    // Looks like: "#@version id;unixtimestamp".
                    @list($fversion, $ftime) = explode(';', trim(substr($line, strpos($line, ' ', 1))));
                    $lastmod = date("YmdHis", min($ftime, time()));
                }

                // Each language section should be prefixed by #@.
                if ($line[0] == '#' && $line[1] == '@') {
                    if (!empty($data)) {
                        foreach ($data as $name => $value) {
                            $value = addslashes($value);
                            $exists = mysqli_query($DB->link, "SELECT name, lastmod FROM `".PFX."txp_lang` WHERE lang = '".$lang."' AND name = '$name' AND event = '$event'");

                            if ($exists) {
                                $exists = mysqli_fetch_row($exists);
                            }

                            if ($exists[1]) {
                                mysqli_query($DB->link, "UPDATE `".PFX."txp_lang` SET lastmod = '$lastmod', data = '$value' WHERE owner = '".doSlash(TEXTPATTERN_LANG_OWNER_SYSTEM)."' AND lang = '".$lang."' AND name = '$name' AND event = '$event'");
                                echo mysqli_error($DB->link);
                            } else {
                                mysqli_query($DB->link, "INSERT DELAYED INTO `".PFX."txp_lang` SET lang = '".$lang."', name = '$name', lastmod = '$lastmod', event = '$event', data = '$value'");
                                echo mysqli_error($DB->link);
                            }
                        }
                    }

                    // Reset.
                    $data = array();
                    $event = substr($line, 2, (strlen($line) - 2));
                    $event = rtrim($event);

                    if (strpos($event, 'version') === false) {
                        $core_events[] = $event;
                    }

                    continue;
                }

                // Guard against setup strings being loaded.
                // TODO: Setup strings will be removed from the .txt files at some point; this check can then be removed.
                if ($event !== 'setup') {
                    @list($name, $val) = explode(' => ', trim($line));
                    $data[$name] = $val;
                }
            }

            // Remember to add the last one.
            if (!empty($data)) {
                foreach ($data as $name => $value) {
                    mysqli_query($DB->link, "INSERT DELAYED INTO `".PFX."txp_lang` SET lang = '".$lang."', name = '$name', lastmod = '$lastmod', event = '$event', data = '$value'");
                }
            }

            mysqli_query($DB->link, "DELETE FROM `".PFX."txp_lang` WHERE owner = '' AND lang = '".$lang."' AND event IN ('".join("','", array_unique($core_events))."') AND  lastmod > $lastmod");
            @fclose($file);

            // Delete empty fields if any.
            mysqli_query($DB->link, "DELETE FROM `".PFX."txp_lang` WHERE data = ''");
            mysqli_query($DB->link, "FLUSH TABLE `".PFX."txp_lang`");

            return true;
        }
    }

    return false;
}

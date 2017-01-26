<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
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
    $lang_files = glob(txpath.'/lang/'.$lang.'.{txt,textpack}', GLOB_BRACE);

    if ($textpack = @file_get_contents($lang_files[0])) {
        $parser = new \Textpattern\Textpack\Parser();
        $parser->setOwner('');
        $parser->setLanguage($lang);
        $textpack = $parser->parse($textpack);

        if (empty($textpack)) {

            return false;
        }

        foreach ($textpack as $translation) {
            extract($translation);

            if ($event == 'setup') {
                continue;
            }

            $where = "lang = '".doSlash($lang)."' AND name = '".doSlash($name)."'";

            if (safe_count('txp_lang', $where)) {
                $r = safe_update(
                    'txp_lang',
                    "lastmod = '".date('YmdHis', $lastmod)."',
                    data = '".doSlash($data)."',
                    event = '".doSlash($event)."',
                    owner = '".doSlash($owner)."'",
                    $where
                );
            } else {
                $r = safe_insert(
                    'txp_lang',
                    "lastmod = '".date('YmdHis', $lastmod)."',
                    data = '".doSlash($data)."',
                    event = '".doSlash($event)."',
                    owner = '".doSlash($owner)."',
                    lang = '".doSlash($lang)."',
                    name = '".doSlash($name)."'"
                );
            }
        }

        return true;
    }

    return false;
}

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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Textpack parser.
 *
 * @since   4.6.0
 * @package Textpack
 */

namespace Textpattern\Textpack;

class Parser
{
    /**
     * Stores the default language.
     *
     * @var string
     */

    protected $language;

    /**
     * Stores the default owner.
     *
     * @var string
     */

    protected $owner;

    /**
     * Constructor.
     */

    public function __construct()
    {
        $this->language = get_pref('language', TEXTPATTERN_DEFAULT_LANG);
        $this->owner = TEXTPATTERN_LANG_OWNER_SITE;
    }

    /**
     * Sets the default language.
     *
     * @param string $language The language code
     */

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Sets the default owner.
     *
     * @param string $owner The default owner
     */

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * Converts a Textpack to an array.
     *
     * <code>
     * $textpack = \Textpattern\Textpack\Parser();
     * print_r(
     *     $textpack->parse("string => translation")
     * );
     * </code>
     *
     * @param  string       $textpack The Textpack
     * @param  string|array $group    Only return strings with the given event(s)
     * @return array An array of translations
     */

    public function parse($textpack, $group = null)
    {
        if ($group && !is_array($group)) {
            $group = do_list($group);
        } else {
            $group = (array)$group;
        }

        $out = array();
        $version = false;
        $lastmod = false;
        $event = false;
        $language = $this->language;
        $owner = $this->owner;

        if (strpos($textpack, '=>') === false 
            && $sections = parse_ini_string($textpack, true))
        {
            foreach ($sections as $event => $strings) {
                $event = $event == 'meta' ? 'common' : trim($event, ' _');

                if (!empty($group) && !in_array($event, $group)) {
                    continue;
                } else {
                    foreach ($strings as $name => $data) {
                        $out[] = array(
                            'name'    => $name,
                            'lang'    => $language,
                            'data'    => $data,
                            'event'   => $event,
                            'owner'   => $owner,
                            'version' => $version,
                            'lastmod' => $lastmod,
                        );
                    }
                }
            }

            return $out;
        }

        $lines = explode(n, (string)$textpack);

        foreach ($lines as $line) {
            $line = trim($line);

            // A blank/comment line.
            if ($line === '' || preg_match('/^#[^@]/', $line, $m)) {
                continue;
            }

            // Sets version. The lastmod timestamp after the ';' in the regex
            // remains for reading legacy files, but is no longer used.
            if (preg_match('/^#@version\s+([^;\n]+);?([0-9]*)$/', $line, $m)) {
                $version = $m[1];
//                $lastmod = $m[2] !== false ? $m[2] : $lastmod;
                continue;
            }

            // Sets language.
            if (preg_match('/^#@language\s+(.+)$/', $line, $m)) {
                $language = \Txp::get('\Textpattern\L10n\Locale')->validLocale($m[1]);
                continue;
            }

            // Sets owner.
            if (preg_match('/^#@owner\s+(.+)$/', $line, $m)) {
                $owner = $m[1];
                continue;
            }

            // Sets event.
            if (preg_match('/^#@([a-zA-Z0-9_-]+)$/', $line, $m)) {
                $event = $m[1];
                continue;
            }

            // Translation. Note that the array is numerically indexed.
            // Indexing by name seems attractive because it means merging default
            // strings is simpler. But doing so means that installing combined
            // textpacks (such as those from plugins with multiple languages
            // in one file) results in only the last pack being available.
            if (preg_match('/^([\w\-]+)\s*=>\s*(.+)$/', $line, $m)) {
                if (!empty($m[1]) && !empty($m[2]) && (empty($group) || in_array($event, $group))) {
                    $out[] = array(
                        'name'    => $m[1],
                        'lang'    => $language,
                        'data'    => $m[2],
                        'event'   => $event,
                        'owner'   => $owner,
                        'version' => $version,
                        'lastmod' => $lastmod,
                    );
                }
            }
        }

        return $out;
    }
}

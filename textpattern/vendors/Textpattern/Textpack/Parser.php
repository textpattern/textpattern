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
 * Textpack parser.
 *
 * @since   4.6.0
 * @package Textpack
 */

namespace Textpattern\Textpack;

class Parser
{
    /**
     * The default language.
     *
     * @var string
     */

    protected $language;

    /**
     * The default owner.
     *
     * @var string
     */

    protected $owner;

    /**
     * The list of strings in the pack, separated by language.
     *
     * @var array
     */

    protected $packs = array();

    /**
     * Constructor.
     */

    public function __construct()
    {
        $this->language = get_pref('language', TEXTPATTERN_DEFAULT_LANG);
        $this->owner = TEXTPATTERN_LANG_OWNER_SITE;
    }

    /**
     * Set the default language.
     *
     * @param string $language The language code
     */

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Set the default owner.
     *
     * @param string $owner The default owner
     */

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * Convert a Textpack to an array.
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

    public function parse($textpack, $group = '')
    {
        static $replacements = array(
            "\nnull" => "\n@null",
            "\nyes" => "\n@yes",
            "\nno" => "\n@no",
            "\ntrue" => "\n@true",
            "\nfalse" => "\n@false",
            "\non" => "\n@on",
            "\noff" => "\n@off",
            "\nnone" => "\n@none"
        );

        if ($group && !is_array($group)) {
            $group = do_list($group);
        } elseif (!$group) {
            $group = array();
        }

        $out = array();
        $version = false;
        $lastmod = false;
        $event = false;
        $language = $this->language;
        $owner = $this->owner;

        // Are we dealing with the .ini file format?
        if (strpos($textpack, '=>') === false
            && $sections = parse_ini_string('[common]'.n.strtr($textpack, $replacements), true)) {
            if (!empty($sections['@common'])
                && !empty($sections['@common']['lang_code'])
                && $sections['@common']['lang_code'] !== TEXTPATTERN_DEFAULT_LANG
            ) {
                $language = \Txp::get('\Textpattern\L10n\Locale')->validLocale($sections['@common']['lang_code']);
            }

            foreach ($sections as $event => $strings) {
                $event = trim($event, ' @');

                if (!empty($group) && !in_array($event, $group)) {
                    continue;
                } else {
                    foreach (array_filter($strings) as $name => $data) {
                        $out[] = array(
                            'name'    => ltrim($name, ' @'),
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

            $this->packs[$language] = $out;
            return;
        }

        // Not .ini, must be dealing with a regular .txt/.textpack file format.
        $lines = explode(n, (string)$textpack);

        foreach ($lines as $line) {
            $line = trim($line);

            // A blank/comment line.
            if ($line === '' || preg_match('/^#[^@]/', $line, $m)) {
                continue;
            }

            // Set version. The lastmod timestamp after the ';' in the regex
            // remains for reading legacy files, but is no longer used.
            if (preg_match('/^#@version\s+([^;\n]+);?([0-9]*)$/', $line, $m)) {
                $version = $m[1];
                continue;
            }

            // Set language.
            if (preg_match('/^#@language\s+(.+)$/', $line, $m)) {
                $language = \Txp::get('\Textpattern\L10n\Locale')->validLocale($m[1]);
                continue;
            }

            // Set owner.
            if (preg_match('/^#@owner\s+(.+)$/', $line, $m)) {
                $owner = $m[1];
                continue;
            }

            // Set event.
            if (preg_match('/^#@([a-zA-Z0-9_-]+)$/', $line, $m)) {
                $event = $m[1];
                continue;
            }

            // Translation.
            if (preg_match('/^([\w\-]+)\s*=>\s*(.+)$/', $line, $m)) {
                if (!empty($m[1]) && !empty($m[2]) && (empty($group) || in_array($event, $group))) {
                    $langGiven = $language ? $language : $this->language;
                    $langList = do_list_unique($langGiven);

                    foreach ($langList as $langToStore) {
                        $this->packs[$langToStore][] = array(
                            'name'    => $m[1],
                            'lang'    => $langToStore,
                            'data'    => $m[2],
                            'event'   => $event,
                            'owner'   => $owner,
                            'version' => $version,
                            'lastmod' => $lastmod,
                        );
                    }
                }
            }
        }

        return;
    }

    /**
     * Fetch the language strings extracted by the last-parsed Textpack.
     *
     * @return array
     */

    public function getStrings($lang_code)
    {
        $out = array();

        if (isset($this->packs[$lang_code])) {
            $out = $this->packs[$lang_code];
        }

        return $out;
    }

    /**
     * Fetch the list of languages used in the last-parsed Textpack.
     *
     * @return array
     */

    public function getLanguages()
    {
        return array_keys($this->packs);
    }
}

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
        $this->language = get_pref('language', 'en-gb');
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
     * @param  string $textpack The Textpack
     * @return array An array of translations
     */

    public function parse($textpack)
    {
        $lines = explode(n, (string)$textpack);
        $out = array();
        $version = false;
        $lastmod = false;
        $event = false;
        $language = $this->language;
        $owner = $this->owner;

        foreach ($lines as $line) {
            $line = trim($line);

            // A comment line.
            if (preg_match('/^#[^@]/', $line, $m)) {
                continue;
            }

            // Sets version and lastmod timestamp.
            if (preg_match('/^#@version\s+([^;\n]+);?([0-9]*)$/', $line, $m)) {
                $version = $m[1];
                $lastmod = $m[2] !== false ? $m[2] : $lastmod;
                continue;
            }

            // Sets language.
            if (preg_match('/^#@language\s+(.+)$/', $line, $m)) {
                $language = $m[1];
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

            // Translation.
            if (preg_match('/^([\w\-]+)\s*=>\s*(.+)$/', $line, $m)) {
                if (!empty($m[1]) && !empty($m[2])) {
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

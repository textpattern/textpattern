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
 * Language manipulation.
 *
 * @since   4.7.0
 * @package L10n
 */

namespace Textpattern\L10n;

class Lang implements \Textpattern\Container\ReusableInterface
{
    /**
     * Language base directory that houses all the language files/textpacks.
     *
     * @var string
     */

    protected $langDirectory = null;

    /**
     * List of files in the $langDirectory.
     *
     * @var array
     */

    protected $files = array();

    /**
     * The currently active language designator.
     *
     * @var string
     */

    protected $activeLang = null;

    /**
     * Metadata for languages installed in the database.
     *
     * @var array
     */

    protected $dbLangs = array();

    /**
     * Metadata for all available languages in the filesystem.
     *
     * @var array
     */

    protected $allLangs = array();

    /**
     * List of strings that have been loaded.
     *
     * @var array
     */

    protected $strings = array();

    /**
     * Date format to use for the lastmod column.
     *
     * @var string
     */

    protected $lastmodFormat = 'YmdHis';

    /**
     * Constructor.
     *
     * @param string $langDirectory Language directory to use
     */

    public function __construct($langDirectory = null)
    {
        if ($langDirectory === null) {
            $langDirectory = txpath.DS.'lang'.DS;
        }

        $this->langDirectory = $langDirectory;

        if (!$this->files) {
            $this->files = $this->files();
        }
    }

    /**
     * Return all installed languages in the database.
     *
     * @return array Available language codes
     */

    public function installed()
    {
        static $installed_langs = null;

        if (!$installed_langs) {
            $installed_langs = safe_column("lang", 'txp_lang', "owner = '' GROUP BY lang");
        }

        return $installed_langs;
    }

    /**
     * Return all language files in the lang directory.
     *
     * @return array Available language filenames
     */

    public function files()
    {
        if (!is_dir($this->langDirectory) || !is_readable($this->langDirectory)) {
            trigger_error('Lang directory is not accessible: '.$this->langDirectory, E_USER_WARNING);

            return array();
        }

        return glob($this->langDirectory.'*.{txt,textpack,ini}', GLOB_BRACE);
    }

    /**
     * Locate a file in the lang directory based on a language code.
     *
     * @param  string $lang_code The language code to look up
     * @return string|null       The matching filename
     */

    public function findFilename($lang_code)
    {
        $out = null;

        foreach ($this->files as $file) {
            $pathinfo = pathinfo($file);

            if ($pathinfo['filename'] === $lang_code) {
                $out = $file;
                break;
            }
        }

        return $out;
    }

    /**
     * Read the meta info from the top of the given language file.
     *
     * @param  string $file The filename to read
     * @return array        Meta info such as language name, language code, language direction and last modified time
     */

    public function fetchMeta($file)
    {
        $meta = array();

        if (is_file($file) && is_readable($file)) {
            $numMetaRows = 4;
            $separator = '=>';
            extract(pathinfo($file));
            $filename = preg_replace('/\.(txt|textpack|ini)$/i', '', $basename);
            $ini = strtolower($extension) == 'ini';

            $meta['filename'] = $filename;

            if ($fp = @fopen($file, 'r')) {
                for ($idx = 0; $idx < $numMetaRows; $idx++) {
                    $rows[] = fgets($fp, 1024);
                }

                fclose($fp);
                $meta['time'] = filemtime($file);

                if ($ini) {
                    $langInfo = parse_ini_string(join($rows));
                    $meta['name'] = (!empty($langInfo['lang_name'])) ? $langInfo['lang_name'] : $filename;
                    $meta['code'] = (!empty($langInfo['lang_code'])) ? strtolower($langInfo['lang_code']) : $filename;
                    $meta['direction'] = (!empty($langInfo['lang_dir'])) ? strtolower($langInfo['lang_dir']) : 'ltr';
                } else {
                    $langName = do_list($rows[1], $separator);
                    $langCode = do_list($rows[2], $separator);
                    $langDirection = do_list($rows[3], $separator);

                    $meta['name'] = (isset($langName[1])) ? $langName[1] : $filename;
                    $meta['code'] = (isset($langCode[1])) ? strtolower($langCode[1]) : $filename;
                    $meta['direction'] = (isset($langDirection[1])) ? strtolower($langDirection[1]) : 'ltr';
                }
            }
        }

        return $meta;
    }

    /**
     * Fetch available languages.
     *
     * Depending on the flags, the returned array can contain active,
     * installed or available language metadata.
     *
     * @param  int   $flags Determine which type of information to return
     * @param  int   $force Force update the given information, even if it's already populated
     * @return array
     */

    public function available($flags = TEXTPATTERN_LANG_AVAILABLE, $force = 0)
    {
        if ($force & TEXTPATTERN_LANG_ACTIVE || $this->activeLang === null) {
            $this->activeLang = get_pref('language', TEXTPATTERN_DEFAULT_LANG, true);
            $this->activeLang = \Txp::get('\Textpattern\L10n\Locale')->validLocale($this->activeLang);
        }

        if ($force & TEXTPATTERN_LANG_INSTALLED || !$this->dbLangs) {
            // We need a value here for the language itself, not for each one of the rows.
            $this->dbLangs = safe_rows(
                "lang, UNIX_TIMESTAMP(MAX(lastmod)) AS lastmod",
                'txp_lang',
                "owner = '' GROUP BY lang ORDER BY lastmod DESC"
            );
        }

        if ($force & TEXTPATTERN_LANG_AVAILABLE || !$this->allLangs) {
            $currently_lang = array();
            $installed_lang = array();
            $available_lang = array();

            foreach ($this->dbLangs as $language) {
                if ($language['lang'] === $this->activeLang) {
                    $currently_lang[$language['lang']] = array(
                        'db_lastmod' => $language['lastmod'],
                        'type'       => 'active',
                    );
                } else {
                    $installed_lang[$language['lang']] = array(
                        'db_lastmod' => $language['lastmod'],
                        'type'       => 'installed',
                    );
                }
            }

            // Get items from filesystem.
            if (!empty($this->files)) {
                foreach ($this->files as $file) {
                    $meta = $this->fetchMeta($file);
                    $name = $meta['filename'];

                    if (array_key_exists($name, $currently_lang)) {
                        $currently_lang[$name]['name'] = $meta['name'];
                        $currently_lang[$name]['direction'] = $meta['direction'];
                        $currently_lang[$name]['file_lastmod'] = $meta['time'];
                    } elseif (array_key_exists($name, $installed_lang)) {
                        $installed_lang[$name]['name'] = $meta['name'];
                        $installed_lang[$name]['direction'] = $meta['direction'];
                        $installed_lang[$name]['file_lastmod'] = $meta['time'];
                    }

                    $available_lang[$name]['file_lastmod'] = $meta['time'];
                    $available_lang[$name]['name'] = $meta['name'];
                    $available_lang[$name]['direction'] = $meta['direction'];
                    $available_lang[$name]['type'] = 'available';
                }
            }

            $this->allLangs = array(
                'active'    => $currently_lang,
                'installed' => $installed_lang,
                'available' => $available_lang,
            );
        }

        $out = array();

        if ($flags & TEXTPATTERN_LANG_ACTIVE) {
            $out = array_merge($out, $this->allLangs['active']);
        }

        if ($flags & TEXTPATTERN_LANG_INSTALLED) {
            $out = array_merge($out, $this->allLangs['installed']);
        }

        if ($flags & TEXTPATTERN_LANG_AVAILABLE) {
            $out = array_merge($out, $this->allLangs['available']);
        }

        return $out;
    }

    /**
     * Set/overwrite the language strings. Chainable.
     *
     * @param array $strings Set of strings to use
     */

    public function setPack(array $strings)
    {
        $this->strings = (array)$strings;

        return $this;
    }

    /**
     * Fetch Textpack strings from the file matching the given $lang_code.
     *
     * A subset of the strings may be fetched by supplying a list of
     * $group names to grab.
     *
     * @param  string|array $lang_code The language code to fetch, or array(lang_code, override_lang_code)
     * @param  string|array $group     Comma-separated list or array of headings from which to extract strings
     * @return array
     */

    public function getPack($lang_code, $group = null)
    {
        if (is_array($lang_code)) {
            $lang_over = $lang_code[1];
            $lang_code = $lang_code[0];
        } else {
            $lang_over = $lang_code;
        }

        $lang_file = $this->findFilename($lang_code);

        if ($textpack = @file_get_contents($lang_file)) {
            $parser = new \Textpattern\Textpack\Parser();
            $parser->setOwner('');
            $parser->setLanguage($lang_over);
            $textpack = $parser->parse($textpack, $group);
        }

        // Reindex the pack so it can be merged.
        $langpack = array();

        foreach ($textpack as $translation) {
            $langpack[$translation['name']] = $translation;
        }

        return $langpack;
    }

    /**
     * Install a language pack from a file.
     *
     * @param  string $lang_code The lang identifier to load
     */

    public function installFile($lang_code)
    {
        $langpack = $this->getPack($lang_code);

        if (empty($langpack)) {
            return false;
        }

        if ($lang_code !== TEXTPATTERN_DEFAULT_LANG) {
            // Load the fallback strings so we're not left with untranslated strings.
            // Note that the language is overridden to match the to-be-installed lang.
            $fallpack = $this->getPack(array(TEXTPATTERN_DEFAULT_LANG, $lang_code));
            $langpack = array_merge($fallpack, $langpack);
        }

        return $this->upsertPack($langpack, $lang_code);
    }

    /**
     * Installs localisation strings from a Textpack.
     *
     * @param   string $textpack      The Textpack to install
     * @param   bool   $addNewLangs If TRUE, installs strings for any included language
     * @return  int Number of installed strings
     * @package L10n
     */

    public function installTextpack($textpack, $addNewLangs = false)
    {
        $parser = new \Textpattern\Textpack\Parser();
        $parser->setLanguage(get_pref('language', TEXTPATTERN_DEFAULT_LANG));
        $textpack = $parser->parse($textpack);

        if (!$textpack) {
            return 0;
        }

        $installed_langs = $this->installed();
        $done = 0;
        $now = date($this->lastmodFormat);

        foreach ($textpack as $translation) {
            extract($translation);

            if (!$addNewLangs && !in_array($lang, $installed_langs)) {
                continue;
            }

            $where = array('lang' => $lang, 'name' => $name);

            $r = safe_upsert(
                'txp_lang',
                "lastmod = '".doSlash($now)."',
                data = '".doSlash($data)."',
                event = '".doSlash($event)."',
                owner = '".doSlash($owner)."'",
                $where
            );

            if ($r) {
                $done++;
            }
        }

        return $done;
    }

    /**
     * Insert or update a language pack.
     *
     * @param  array $langpack The language pack to store
     * @return result set
     */

    public function upsertPack($langpack, $lang_code)
    {
        if ($langpack) {
            $now = date($this->lastmodFormat);
            $lang_code = doSlash($lang_code);

            $exists = safe_column('name', 'txp_lang', "lang='".$lang_code."'");
            $sql = array();
            $inserts = array();

            foreach ($langpack as $translation) {
                extract(doSlash($translation));

                $lastmod = empty($lastmod) ? $now : date($this->lastmodFormat, $lastmod);

                if (!empty($exists[$name])) {
                    $where = "lang = '{$lang}' AND name = '{$name}'";
                    $fields = "event = '{$event}', owner = '{$owner}', data = '{$data}', lastmod = '{$lastmod}'";
                    $sql[] = "UPDATE ".PFX."txp_lang SET $fields WHERE $where";
                } else {
                    $fields = array("{$lang}", "{$name}", "{$event}", "{$owner}", "{$data}", "{$lastmod}");
                    $inserts[] = '('.join(', ', quote_list($fields)).')';
                }
            }

            if ($inserts) {
                $sql[] = "INSERT INTO ".PFX."txp_lang (lang, name, event, owner, data, lastmod) VALUES".join(', ', $inserts);
            }

            return safe_query($sql);
        }

        return false;
    }

    /**
     * Fetches the given language's strings from the database as an array.
     *
     * If no $events is specified, only appropriate strings for the current context
     * are returned. If 'txpinterface' constant equals 'admin' all strings are
     * returned. Otherwise, only strings from events 'common' and 'public'.
     *
     * If $events is FALSE, returns all strings.
     *
     * Note the returned array inlcudes the language if the fallback has been used.
     * This ensures (as far as possible) a full complement of strings, regardless of
     * the degree of translation that's taken place in the desired $lang code.
     * Any holes can be mopped up by the default language.
     *
     * @param   string            $lang_code The language code
     * @param   array|string|bool $events    An array of loaded events
     * @return  array
     */

    public function load($lang_code, $events = null)
    {
        if ($events === null && txpinterface !== 'admin') {
            $events = array('public', 'common');
        }

        $where = " AND name != ''";

        if ($events) {
            $where .= " AND event IN (".join(',', quote_list((array) $events)).")";
        }

        $out = array();

        $rs = safe_rows_start("name, data", 'txp_lang', "lang = '".doSlash($lang_code)."'".$where);

        if (!empty($rs)) {
            while ($a = nextRow($rs)) {
                $out[$a['name']] = $a['data'];
            }
        }

        $this->strings = $out;

        return $this->strings;
    }

    /**
     * Returns a localisation string.
     *
     * @param   string $var    String name
     * @param   array  $atts   Replacement pairs
     * @param   string $escape Convert special characters to HTML entities. Either "html" or ""
     * @return  string A localisation string
     * @package L10n
     */

    public function txt($var, $atts = array(), $escape = 'html')
    {
        if (!is_array($atts)) {
            $atts = array();
        }

        if ($escape == 'html') {
            foreach ($atts as $key => $value) {
                $atts[$key] = txpspecialchars($value);
            }
        }

        $v = strtolower($var);

        if (isset($this->strings[$v])) {
            $out = $this->strings[$v];

            if ($out !== '') {
                return strtr($out, $atts);
            }
        }

        if ($atts) {
            return $var.': '.join(', ', $atts);
        }

        return $var;
    }

    /**
     * Generate a &lt;select&gt; element of languages.
     *
     * @param  string $name  The HTML name and ID to assign to the select control
     * @param  string $val   The currently active language identifier (en-gb, fr, de, ...)
     * @param  int    $flags Logical OR list of flags indiacting the type of list to return:
     *                       TEXTPATTERN_LANG_ACTIVE: the active language
     *                       TEXTPATTERN_LANG_INSTALLED: all installed languages
     *                       TEXTPATTERN_LANG_AVAILABLE: all available languages in the file system
     * @return string HTML
     */

    public function languageSelect($name, $val, $flags = null)
    {
        if ($flags === null) {
            $flags = TEXTPATTERN_LANG_ACTIVE | TEXTPATTERN_LANG_INSTALLED;
        }

        $installed_langs = $this->available((int)$flags);
        $vals = array();

        foreach ($installed_langs as $lang => $langdata) {
            $vals[$lang] = $langdata['name'];

            if (trim($vals[$lang]) == '') {
                $vals[$lang] = $lang;
            }
        }

        ksort($vals);
        reset($vals);

        return selectInput($name, $vals, $val, false, true, $name);
    }
}

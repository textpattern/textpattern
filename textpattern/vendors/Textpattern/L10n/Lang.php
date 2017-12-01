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

    protected $lang_dir = null;

    /**
     * List of files in the $lang_dir.
     *
     * @var array
     */

    protected $files = array();

    /**
     * List of strings that have been loaded.
     *
     * @var array
     */

    protected $strings = array();

    /**
     * Constructor.
     *
     * @param string $lang_dir Language directory to use
     */

    public function __construct($lang_dir = null)
    {
        if ($lang_dir === null) {
            $lang_dir = txpath.DS.'lang'.DS;
        }

        $this->lang_dir = $lang_dir;

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
        if (!is_dir($this->lang_dir) || !is_readable($this->lang_dir)) {
            trigger_error('Lang directory is not accessible: '.$this->lang_dir, E_USER_WARNING);

            return array();
        }

        return glob($this->lang_dir.'*.{txt,textpack,ini}', GLOB_BRACE);
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
     * @return array
     */

    public function available($flags = TEXTPATTERN_LANG_AVAILABLE)
    {
        static $active_lang = null;
        static $in_db = array();
        static $allLangs = array();

        if ($active_lang === null) {
            $active_lang = get_pref('language', TEXTPATTERN_DEFAULT_LANG, true);
        }

        if (!$in_db) {
            // We need a value here for the language itself, not for each one of the rows.
            $in_db = safe_rows(
                "lang, UNIX_TIMESTAMP(MAX(lastmod)) AS lastmod",
                'txp_lang',
                "owner = '' GROUP BY lang ORDER BY lastmod DESC"
            );
        }

        if (!$allLangs) {
            $currently_lang = array();
            $installed_lang = array();
            $available_lang = array();

            foreach ($in_db as $language) {
                if ($language['lang'] === $active_lang) {
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

            $allLangs = array(
                'active'    => $currently_lang,
                'installed' => $installed_lang,
                'available' => $available_lang,
            );
        }

        $out = array();

        if ($flags & TEXTPATTERN_LANG_ACTIVE) {
            $out = array_merge($out, $allLangs['active']);
        }

        if ($flags & TEXTPATTERN_LANG_INSTALLED) {
            $out = array_merge($out, $allLangs['installed']);
        }

        if ($flags & TEXTPATTERN_LANG_AVAILABLE) {
            $out = array_merge($out, $allLangs['available']);
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

        $now = date('YmdHis');

        if ($langpack) {
            $exists = safe_column('name', 'txp_lang', "lang='".doSlash($lang_code)."'");

            foreach ($langpack as $translation) {
                extract(doSlash($translation));

                $where = "lang = '{$lang}' AND name = '{$name}'";
                $lastmod = empty($lastmod) ? $now : date('YmdHis', $lastmod);
                $fields = "lastmod = '{$lastmod}', data = '{$data}', event = '{$event}', owner = '{$owner}'";

                if (!empty($exists[$name])) {
                    $r = safe_update(
                        'txp_lang',
                        $fields,
                        $where
                    );
                } else {
                    $r = safe_insert(
                        'txp_lang',
                        $fields .", lang = '{$lang}', name = '{$name}'"
                    );
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Installs localisation strings from a Textpack.
     *
     * @param   string $textpack      The Textpack to install
     * @param   bool   $add_new_langs If TRUE, installs strings for any included language
     * @return  int Number of installed strings
     * @package L10n
     */

    public function installTextpack($textpack, $add_new_langs = false)
    {
        $parser = new \Textpattern\Textpack\Parser();
        $parser->setLanguage(get_pref('language', TEXTPATTERN_DEFAULT_LANG));
        $textpack = $parser->parse($textpack);

        if (!$textpack) {
            return 0;
        }

        $installed_langs = $this->installed();
        $done = 0;
        $now = date('YmdHis');

        foreach ($textpack as $translation) {
            extract($translation);

            if (!$add_new_langs && !in_array($lang, $installed_langs)) {
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
     * Install/Update a plugin Textpack.
     *
     * @param   string $name Plugin name
     * @return  int          Number of installed strings
     */

    public function installTextpackPlugin($name)
    {
        if (has_handler('plugin_textpack.fetch')) {
            $textpack = callback_event('plugin_textpack.fetch', '', false, compact('name'));
        } else {
            $textpack = safe_field('textpack', 'txp_plugin', "name = '".doSlash($name)."'");
        }

        if (!empty($textpack)) {
            $textpack = "#@owner {$name}".n."#@language ".TEXTPATTERN_DEFAULT_LANG.n.$textpack;

            return $this->installTextpack($textpack, false);
        }

        return 0;
    }

    /**
     * Install/update ALL plugin Textpacks. Used when a new language is added.
     */

    public function installTextpackPlugins()
    {
        if ($plugins = safe_column_num('name', 'txp_plugin', "textpack != '' ORDER BY load_order")) {
            foreach ($plugins as $name) {
                $this->installTextpackPlugin($name);
            }
        }
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

        if (txpinterface === 'admin') {
            $admin_events = array('admin-side', 'common');

            if ($events) {
                $admin_events = array_merge($admin_events, (array) $events);
            }

            $events = $admin_events;
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

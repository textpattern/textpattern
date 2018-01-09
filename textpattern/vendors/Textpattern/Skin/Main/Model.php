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

/**
 * SharedBase
 *
 * Extended by Main and AssetBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin\Main {

    class Model extends \Textpattern\Skin\Model
    {
        /**
         * {@inheritdoc}
         */

        protected static $table = 'txp_skin';

        /**
         * The skin related main file.
         *
         * @var string Filename.
         * @see        fileExists(), fileIsReadable(), getFilePath(), getFileContents().
         */

        protected static $file = 'manifest.json';

        /**
         * Sections used by the skin.
         *
         * @var array Section names.
         * @see       setSections(), getSections().
         */

        protected $sections;

        /**
         * Whether the skin is locked or not.
         *
         * @var bool
         * @see      lock(), unlock().
         */

        protected $locked;

        /**
         * Importable skins.
         *
         * @var array Associative array of skin names and their titles
         * @see       setDirectories(), getDirectories().
         */

        protected static $directories;

        /**
         * Installed skins.
         *
         * @var array Associative array of skin names and their titles
         * @see       setDirectories(), getDirectories().
         */

        protected static $installed;

        protected $infos;
        protected $base;

        /**
         * Constructor.
         *
         * @param string $names Skin name.
         */

        public function __construct()
        {
        }

        /**
         * $names property setter.
         *
         * @param  array  $names Skin names.
         * @return object $this.
         */

        public function setNames($names = null)
        {
            $this->names = $names === null ? $names = array() : $names;
            $this->setName($names ? $names[0] : null);

            return $this;
        }

        /**
         * $names property getter.
         *
         * @return array Skin names.
         */

        public function getNames()
        {
            return $this->names;
        }

        /**
         * $names property getter.
         *
         * @return array Skin names.
         */

        public function getBase()
        {
            return $this->base;
        }

        /**
         * $names property getter.
         *
         * @return array Skin names.
         */

        public function setBase($name)
        {
            $this->base = $name;

            return $this;
        }

        /**
         * $name property setter.
         *
         * @param object $this.
         */

        public function setName($name = null)
        {
            $this->name = $name === null ? static::getEditing() : $name;

            return $this;
        }

        public function setInfos(
            $name,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null
        ) {
            $this->infos = compact('name', 'title', 'version', 'description', 'author', 'author_uri');

            return $this;
        }

        public function getInfos()
        {
            $infoQuery = array();

            foreach ($this->infos as $col => $value) {
                $infoQuery[] = $col." = '".doSlash($value)."'";
            }

            return implode(', ', $infoQuery);
        }

        /**
         * $name property getter.
         *
         * @param string Skin name.
         */

        public function getName()
        {
            return $this->infos['name'];
        }

        /**
         * Whether the skin is installed or not.
         *
         * @param bool
         */

        public function isInstalled($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            if ($this->installed === null) {
                $isInstalled = (bool) safe_field(
                    'name',
                    static::getTable(),
                    "name = '".doSlash($name)."'"
                );
            } else {
                $isInstalled = in_array($name, array_values(static::getInstalled()));
            }

            return $isInstalled;
        }

        /**
         * Gets the skin_base_path pref related value.
         *
         * @param string path.
         */

        public static function getBasePath()
        {
            return get_pref('skin_base_path');
        }

        /**
         * Gets the skin directory path.
         *
         * @param string path.
         */

        public function getDirPath($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            return static::getBasePath().'/'.$name;
        }

        /**
         * Makes the skin related directory.
         *
         * @param bool false on error.
         */

        public function createDir()
        {
            return @mkdir($this->getDirPath());
        }

        /**
         * Whether the skin related directory exists or not.
         *
         * @param bool false on error.
         */

        public function dirExists($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            return file_exists($this->getDirPath($name));
        }

        /**
         * Whether the skin related main file exists or not.
         *
         * @param bool false on error.
         */

        public function fileExists()
        {
            return file_exists($this->getFilePath());
        }

        /**
         * Gets the skin related main file contents.
         *
         * @param array Associative array of JSON fields and their related values.
         */

        public function getFileContents()
        {
            return json_decode(file_get_contents($this->getFilePath()), true);
        }

        /**
         * Gets the main skin related file path.
         *
         * @param string path.
         */

        public function getFilePath()
        {
            return $this->getDirPath().'/'.static::$file;
        }

        /**
         * Whether the main skin related file is readable or not.
         *
         * @param bool false on error.
         */

        public function isFileReadable()
        {
            return is_readable($this->getFilePath());
        }

        /**
         * Whether the skin directory is writable or not.
         *
         * @param bool false on error.
         */

        public function isDirWritable()
        {
            return is_writable($this->getDirPath());
        }

        /**
         * Renames the skin related directory.
         *
         * @param bool false on error.
         */

        public function renameDir($from = null, $to = null)
        {
            $from === null ? $from = $this->getBase() : '';
            $to === null ? $to = $this->getName() : '';

            return @rename($this->getDirPath($from), $this->getDirPath($to));
        }

        /**
         * $sections property setter.
         *
         * @param array Section names.
         */

        protected function setSections()
        {
            return $this->sections = safe_column(
                'name',
                'txp_section',
                "skin ='".doSlash($this->getName())."'"
            );
        }

        /**
         * $sections property getter.
         *
         * @param array Section names.
         */

        public function getSections($skin = null)
        {
            $skin === null ? $skin = $this->getBase() : '';

            $this->sections === null ? $this->setSections() : '';

            return $this->sections;
        }

        /**
         * Updates a skin name in use.
         *
         * @param string $to   A skin newname.
         */

        public function updateSections()
        {
            return safe_update(
                'txp_section',
                "skin = '".doSlash($this->getName())."'",
                "skin = '".doSlash($this->getBase())."'"
            );
        }

        /**
         * skin_editing pref getter.
         *
         * @return string Skin name.
         */

        public static function getEditing()
        {
            return get_pref('skin_editing', 'default', true);
        }

        /**
         * Sets the skin_editing pref to the current skin or the one provided.
         *
         * @return bool false on error.
         */

        public function setEditing($name = null)
        {
            global $prefs;

            $name === null ? $name = $this->getName() : '';
            $prefs['skin_editing'] = $name;

            return set_pref('skin_editing', $name, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
        }

        /**
         * Sets the skin_editing pref to the skin used by the default section.
         *
         * @return bool false on error.
         */

        public static function resetEditing()
        {
            global $prefs;

            $name = safe_field('skin', 'txp_section', 'name = "default"');
            $prefs['skin_editing'] = $name;

            return set_pref('skin_editing', $name, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
        }

        /**
         * Pseudo lock the skin by adding a 'lock' folder into the skin directory.
         * Sets the locked property.
         *
         * @return bool false on error.
         */

        public function islocked()
        {
            return $this->locked;
        }

        /**
         * Pseudo lock the skin by adding a 'lock' folder into the skin directory.
         * Sets the locked property.
         *
         * @return bool false on error.
         */

        public function lock($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            $timeStart = microtime(true);
            $locked = false;
            $time = 0;

            while (!$locked && $time < 2) {
                $locked = @mkdir($this->getDirPath($name).'/lock');
                sleep(0.25);
                $time = microtime(true) - $timeStart;
            }

            return $this->locked = $locked;
        }

        /**
         * Pseudo unlock the skin by removing the 'lock' directory added by lock().
         * Sets the locked property.
         *
         * @return bool false on error.
         */

        public function unlock($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            if (@rmdir($this->getDirPath($name).'/lock')) {
                $this->locked = false;
            }

            return !$this->locked;
        }

        /**
         * Whether the skin directory has a valid name or not.
         *
         * @return bool false on error.
         */

        public static function isValidDirName($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            return preg_match('#^'.static::getnamePattern().'$#', $name);
        }

        /**
         * Inserts a skin row.
         *
         * @param  string $title       Skin title;
         * @param  string $version     Skin version;
         * @param  string $description Skin description;
         * @param  string $author      Skin author;
         * @param  string $author_uri  Skin author URL;
         * @return bool                false on error.
         */

        public function createRow()
        {
            return safe_insert(
                static::getTable(),
                $this->getInfos()
            );
        }

        /**
         * Updates a skin row.
         *
         * @param  string $name        Skin new name;
         * @param  string $title       Skin new title;
         * @param  string $version     Skin new version;
         * @param  string $description Skin new description;
         * @param  string $author      Skin new author;
         * @param  string $author_uri  Skin new author URL;
         * @return bool                false on error.
         */

        public function updateRow() {
            return safe_update(
                static::getTable(),
                $this->getInfos(),
                "name = '".doSlash($this->getBase())."'"
            );
        }

        /**
         * get the skin row.
         *
         * @return array Associative array of the skin row fieldsand their related values.
         */

        public function getRow()
        {
            return safe_row(
                'name, title, version, description, author, author_uri',
                static::getTable(),
                "name = '".doSlash($this->getName())."'"
            );
        }

        /**
         * Creates/overrides the main Skin related file.
         *
         * @param  string $title       Skin title;
         * @param  string $version     Skin version;
         * @param  string $description Skin description;
         * @param  string $author      Skin author;
         * @param  string $author_uri  Skin author URL;
         * @return bool                false on error.
         */

        public function createFile(
            $title,
            $version,
            $description,
            $author,
            $author_uri
        ) {
            $contents = array(
                'title'       => $title,
                'version'     => $version,
                'description' => $description,
                'author'      => $author,
                'author_uri'  => $author_uri,
                'txp-type'    => 'textpattern-theme',
            );

            return (bool) file_put_contents(
                $this->getFilePath(),
                JSONPrettyPrint(json_encode($contents, TEXTPATTERN_JSON))
            );
        }

        /**
         * $directories property setter.
         *
         * @return object $this.
         */

        public static function setDirectories()
        {
            $files = static::getFiles();
            static::$directories = array();

            foreach ($files as $file) {
                $name = basename($file->getPath());

                if (1 === 1 || static::isValidDirName($name)) {
                    $infos = $file->getJSONContents();
                    $infos ? static::$directories[$name] = $infos['title'] : '';
                }
            }
        }

        /**
         * Gets Skins related files.
         *
         * @return object
         */

        public static function getFiles()
        {
            return new \Textpattern\Skin\RecIteratorIterator(
                new \Textpattern\Skin\RecRegexIterator(
                    new \Textpattern\Skin\RecDirIterator(static::getBasePath()),
                    '/^manifest\.json/i'
                ),
                1
            );
        }

        /**
         * $directories property getter.
         *
         * @return array Associative array of importable skin names and titles.
         */

        public function getDirectories()
        {
            static::$directories === null ? static::setDirectories() : '';

            return static::$directories;
        }

        /**
         * $installed property setter.
         *
         * @param object $this.
         */

        public static function setInstalled($name = null)
        {
            if ($name) {
                // TODO
            } else {
                $rows = safe_field('name, title', static::getTable(), '1 = 1');

                static::$installed = array();

                foreach ($rows as $row) {
                    static::$installed[$row['name']] = $row['title'];
                }
            }
        }

        /**
         * $installed property getter.
         *
         * @return array Associative array of installed skin names and titles.
         */

        public static function getInstalled()
        {
            static::$installed === null ? static::setInstalled() : '';

            return static::$installed;
        }

        /**
         * $installed property unsetter.
         *
         * @return object $this.
         */

        public static function unsetInstalled($skins)
        {
            static::$installed = array_diff_key(
                static::getInstalled(),
                array_fill_keys($skins, '')
            );
        }

        /**
         * Gets Skins related rows.
         *
         * @param  array $names Skin names.
         * @return array        Associative array of skin names and their related infos.
         */

        public function getRows($names = null)
        {
            $names === null ? $names = $this->getNames() : '';

            $rows = safe_rows_start(
                implode(', ', static::getTableCols()),
                static::getTable(),
                "name IN ('".implode("', '", array_map('doSlash', $names))."')"
            );

            if ($rows) {
                $skinRows = array();

                while ($row = nextRow($rows)) {
                    $name = $row['name'];
                    unset($row['name']);
                    $skinRows[$name] = $row;
                }
            }

            return $skinRows;
        }

        /**
         * Deletes skin rows.
         *
         * @param  array $names Skin names.
         * @return bool         false on error.
         */

        public function deleteRows($names)
        {
            return safe_delete(
                static::getTable(),
                "name IN ('".implode("', '", array_map('doSlash', $names))."')"
            );
        }

        /**
         * Gets an array of the new — not imported yet — skin directories.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function getNewDirectories()
        {
            return array_diff_key(
                static::getDirectories(),
                static::getInstalled()
            );
        }

        public static function getSearchCount($criteria)
        {
            return safe_count('txp_skin', $criteria);
        }

        public static function getAllData($criteria, $sortSQL, $offset, $limit)
        {
            $assets = array('section', 'page', 'form', 'css');
            $things = array('*');

            foreach ($assets as $asset) {
                $things[] = '(SELECT COUNT(*) '
                              .'FROM '.safe_pfx_j('txp_'.$asset).' '
                              .'WHERE txp_'.$asset.'.skin = txp_skin.name) '
                              .$asset.'_count';
            }

            return safe_rows_start(
                implode(', ', $things),
                'txp_skin',
                $criteria.' order by '.$sortSQL.' limit '.$offset.', '.$limit
            );
        }
    }
}

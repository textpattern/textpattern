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

namespace Textpattern\Skin\Asset {

    abstract class Model extends \Textpattern\Skin\Model
    {
        protected $skinModel;

        protected static $contentsCol;

        /**
         * Whether the skin is installed or not.
         *
         * @see isInstalled().
         */

        protected $installed;

        /**
         * Whether the skin is locked or not.
         *
         * @see lock(), unlock().
         */

        protected $locked;

        protected static $dir;

        protected static $asset;

        protected static $essential = array();

        /**
         * The skin related main file.
         *
         * @see getFilePath().
         */

        protected static $extension = 'txp';

        /**
         * Constructor.
         */

        public function __construct(\Textpattern\Skin\Main\Model $skinModel, $names = null)
        {
            $this->setSkinModel($skinModel);
            $names ? $this->setNames($names) : '';
        }

        /**
         * $skin property setter.
         */

        protected function setSkinModel(\Textpattern\Skin\Main\Model $model)
        {
            $this->skin = $model;

            return $this;
        }

        /**
         * $skin property getter.
         */

        public function getSkin()
        {
            return $this->skin;
        }

        /**
         * $contentsCol property getter.
         */

        public static function getContentsCol()
        {
            return static::$contentsCol;
        }

        /**
         * $essential property getter.
         */

        public static function getEssential()
        {
            return static::$essential;
        }

        /**
         * Gets essential template names.
         *
         * @return array templates names.
         */

        public static function getEssentialNames()
        {
            return static::getEssential();
        }

        /**
         * $extension property getter.
         */

        public static function getExtension()
        {
            return static::$extension;
        }

        /**
         * $dir property getter.
         */

        public static function getDir()
        {
            return static::$dir;
        }

        /**
         * Gets the skin directory path.
         *
         * @return string path.
         */

        public function getDirPath()
        {
            return $this->getSkin()->getDirPath().'/'.static::getDir();
        }

        /**
         * Whether the skin related directory exists or not.
         *
         * @param bool false on error.
         */

        public function dirExists()
        {
            return file_exists($this->getDirPath());
        }

        /**
         * Creates the skin related directory.
         *
         * @param bool false on error.
         */

        public function createDir()
        {
            return @mkdir($this->getDirPath());
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
         * $name property setter.
         *
         * @param  string $name Skin name.
         * @return object $this.
         */

        public function setName($name = null)
        {
            $this->name = $name === null ? static::getLastSaved() : $name;

            return $this;
        }

        /**
         * $names property getter.
         */

        public function getNames()
        {
            return $this->names;
        }

        /**
         * $name property getter.
         */

        public function getName()
        {
            return $this->name;
        }

        /**
         * $asset property getter.
         */

        public static function getAsset()
        {
            return static::$asset;
        }

        /**
         * $installed property setter.
         *
         * @param bool
         */

        protected function setInstalled()
        {
            return $this->installed = (bool) safe_field(
                'name',
                static::getTable(),
                "skin ='".doSlash($this->getSkin()->getName())."' name ='".doSlash($this->getName())."'"
            );
        }

        /**
         * $installed property getter.
         */

        public function isInstalled()
        {
            $this->installed === null ? $this->setInstalled() : '';

            return $this->installed;
        }

        /**
         * Gets the template related file path.
         *
         * @param string path.
         */

        public function getFilePath()
        {
            return $this->getDirPath().'/'.$this->getName().'.'.static::getExtension();
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
         * Whether the skin directory is writable or not.
         *
         * @param bool false on error.
         */

        public function isDirReadable()
        {
            return is_readable($this->getDirPath());
        }

        /**
         * skin_editing pref getter.
         *
         * @return string Skin name.
         */

        public static function getLastSaved()
        {
            return get_pref('last_'.static::getAsset().'_saved', 'default', true);
        }

        /**
         * Sets the skin_editing pref to the current skin or the one provided.
         *
         * @return bool false on error.
         */

        public static function setLastSaved()
        {
            global $prefs;

            $name = $this->getName();
            $prefs['last_'.static::getAsset().'_saved'] = $name;

            return set_pref(
                'last_'.static::getAsset().'_saved',
                $name,
                'skin',
                PREF_HIDDEN,
                'text_input',
                0,
                PREF_PRIVATE
            );
        }

        /**
         * Sets the skin_editing pref to the skin used by the default section.
         *
         * @return bool false on error.
         */

        public static function resetLastSaved()
        {
            global $prefs;

            $name = safe_field('page', 'txp_section', 'name = "default"');
            $prefs['last_'.static::getAsset().'_saved'] = $name;

            return set_pref(
                'last_'.static::getAsset().'_saved',
                $name,
                'skin',
                PREF_HIDDEN,
                'text_input',
                0,
                PREF_PRIVATE
            );
        }

        /**
         * Whether the skin directory has a valid name or not.
         *
         * @return bool false on error.
         */

        public function isValidDirName()
        {
            return preg_match('#^'.static::getnamePattern().'$#', $this->getName());
        }

        /**
         * Updates a skin row.
         *
         * @param  string $name      Page name;
         * @param  string $user_html Page contents;
         * @return bool              false on error.
         */

        public function updateRow($name = null, $contents = null)
        {
            return safe_update(
                static::getTable(),
                "name = '".doSlash($name)."', ".static::getContentsCol()." = '".doSlash($contents)."', ",
                "name = '".doSlash($this->getName())."'"
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
                'name, '.static::getContentsCol(),
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

        public function createFile($contents = null)
        {
            return file_put_contents($this->getFilePath(), $contents);
        }

        /**
         * {@inheritdoc}
         */

        public function createRows($contents = null)
        {
            $thisNames = $this->getNames();
            $skin = $this->getSkin()->getName();
            var_dump($skin);
            $names = $thisNames ? $thisNames : static::getEssential();
            $values = array();

            foreach ($names as $i => $name) {
                $values[] = "('".doSlash($this->getSkin()->getName())."', "
                            ."'".doSlash($name)."', "
                            ."'".doSlash($contents ? $contents[$i] : '')."')";
            }

            if ($update) {
                 $updates = array();
                 foreach ($fields as $field) {
                     $updates[] = $field.'=VALUES('.$field.')';
                 }
                 $update = 'ON DUPLICATE KEY UPDATE '.implode(', ', $updates);
            }

            $contentsCol = static::getContentsCol();

            return safe_query(
                "INSERT INTO ".static::getTable()." (skin, name, ".$contentsCol.") "
                ."VALUES ".implode(', ', $values)
                ."ON DUPLICATE KEY UPDATE skin=VALUES(skin), name=VALUES(name), ".$contentsCol."=VALUES(".$contentsCol.")"
            );
        }

        /**
         * Updates a skin name in use.
         *
         * @param string $name A skin newname.
         */

        public function updateSkin()
        {
            $skin = $this->getSkin();

            return safe_update(
                static::getTable(),
                "skin = '".doSlash($skin->getName())."'",
                "skin = '".doSlash($skin->getBase())."'"
            );
        }

        /**
         * Gets files from a defined directory.
         *
         * @param  array  $templates Template names to filter results;
         * @return object            RecursiveIteratorIterator
         */

        public function getFiles()
        {
            $thisNames = $this->getNames();

            if ($thisNames) {
                $templates = '('.implode('|', $thisNames).')';
            } else {
                $templates = static::getNamePattern();
            }

            $extension = static::getExtension();
            $extension === 'txp' ? $extension = '(txp|html)' : '';

            $Files = new \Textpattern\Skin\RecIteratorIterator(
                new \Textpattern\Skin\RecRegexIterator(
                    new \Textpattern\Skin\RecDirIterator($this->getDirPath()),
                    '#^'.$templates.'\.'.$extension.'$#i'
                ),
                0
            );

            return $this->parseFiles($Files);
        }

        protected function parseFiles($Files) {
            $out = array();

            foreach ($Files as $File) {
                $out[$File->getName()] = $File->getContents();
            }

            return $out;
        }

        /**
         * Deletes skin rows from the DB.
         *
         * @param  array $passed Skin names to delete.
         * @return bool          false on error.
         */

        public function deleteRows()
        {
            $thisNames = $this->getNames();
            $nameIn = '';

            if ($thisNames) {
                $nameIn = " AND name IN ('".implode("', '", array_map('doSlash', $thisNames))."')";
            }

            return safe_delete(
                static::getTable(),
                "skin = '".doSlash($this->getSkin()->getName())."'".$nameIn
            );
        }

        /**
         * {@inheritdoc}
         */

        public function getRows()
        {
            $thisNames = $this->getNames();
            $nameIn = '';

            if ($thisNames) {
                $nameIn = " AND name IN ('".implode("', '", array_map('doSlash', $thisNames))."')";
            }

            $rows = safe_rows_start(
                implode(', ', static::getTableCols()),
                static::getTable(),
                "skin = '".doSlash($this->getSkin()->getName())."'".$nameIn
            );

            if ($rows) {
                return $this->parseRows($rows);
            }

            return false;
        }

        protected function parseRows($rows) {
            $skinRows = array();

            while ($row = nextRow($rows)) {
                $skinRows[$row['name']] = $row[static::getContentsCol()];
            }

            return $skinRows;
        }

        /**
         * Drops obsolete template rows.
         *
         * @param  array $not An array of template names to NOT drop;
         * @return bool  false on error.
         */

        public function cleanExtraRows($nameNotIn)
        {
            return safe_delete(
                static::getTable(),
                "skin = '".doSlash($this->getSkin()->getName())."' AND"
                ."name NOT IN ('".implode("', '", array_map('doSlash', $nameNotIn))."')"
            );
        }

        /**
         * Unlinks obsolete template files.
         *
         * @param  array $not An array of template names to NOT unlink;
         * @return array      !Templates for which the unlink process FAILED!;
         */

        public function cleanExtraFiles($nameNotIn)
        {
            $files = $this->getFiles();
            $notRemoved = array();

            foreach ($files as $name => $contents) {
                if (!$nameNotIn || ($nameNotIn && !in_array($name, $nameNotIn))) {
                    unlink($this->getFilePath()) ?: $notRemoved[] = $name;
                }
            }

            return $notRemoved;
        }
    }
}

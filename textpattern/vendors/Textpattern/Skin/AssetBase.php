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

namespace Textpattern\Skin {

    abstract class AssetBase extends CommonBase
    {
        /**
         * Current skin model.
         *
         * @var object Skin
         * @see        __construct().
         */

        protected $skinModel;

        /**
         * Asset related textpack string.
         *
         * @var string 'page', 'form', 'css', etc.
         * @see        getString().
         */

        protected static $string;

        /**
         * Asset related default directory.
         *
         * @var string Directory name.
         * @see        getDefaultDir().
         */

        protected static $defaultDir;

        /**
         * Asset related directory.
         *
         * @var string Directory name.
         * @see        getDirPath().
         */

        protected static $dir;

        /**
         * File related default asset subdirectory.
         *
         * @var string Asset subdirectory name.
         * @see        getDefaultSubdir().
         */

        protected static $defaultSubdir;

        /**
         * File related asset subdirectory.
         *
         * @var string Asset subdirectory name.
         * @see        getSubdirField().
         */

        protected static $subdirField;

        /**
         * File contents related table field(s).
         *
         * @var string Field name (could accept an array in the future for JSON contents)
         * @see        getFileContentsFields().
         */

        protected static $fileContentsFields;

        /**
         * The skin related main file.
         *
         * @see getFilePath().
         */

        protected static $extension = 'txp';

        /**
         * Asset related essential rows as an associative array of the following
         * fields and their value: 'name', ($subdirField, ) $fileContentsFields.
         *
         * @var array Associative array of the following fields and their value:
         *            'name', ($subdirField, ) $fileContentsFields.
         * @see       getEssential().
         */

        protected static $essential = array();

        /**
         * Whether the skin is installed or not.
         *
         * @var array Installed skins.
         * @see       getInstalled(), isInstalled().
         */

        protected $installed;

        /**
         * Constructor.
         */

        public function __construct(Skin $skin)
        {
            $this->setSkin($skin);
            $this->setDir();
        }

        /**
         * $skin property setter.
         */

        protected function setSkin(Skin $skin)
        {
            $this->skin = $skin;

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
         * $fileContentsFields property getter.
         */

        public static function getFileContentsFields()
        {
            return static::$fileContentsFields;
        }

        /**
         * $essential property getter.
         */

        public static function getEssential($field = null, $whereField = null, $whereValue = null)
        {
            if ($field === null) {
                return static::$essential;
            } else {
                $field === null ? $field = 'name' : '';
                $fieldValues = array();

                foreach (static::$essential as $row) {
                    if ($whereField) {
                        if ($row[$whereField] === $whereValue) {
                            $fieldValues[] = $row[$field];
                        }
                    } else {
                        $fieldValues[] = $row[$field];
                    }
                }

                return $fieldValues;
            }
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

        public static function getDefaultDir()
        {
            return static::$defaultDir;
        }

        /**
         * $dir property getter.
         */

        public function getDir()
        {
            return $this->dir;
        }

        /**
         * $skin property setter.
         */

        protected function setDir($name = null)
        {
            $name === null ? $name = static::getDefaultDir() : '';

            $this->dir = $name;

            return $this;
        }

        /**
         * Gets the skin directory path.
         *
         * @return string path.
         */

        public function getDirPath()
        {
            return $this->getSkin()->getDirPath().DS.$this->getDir();
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
         * $subdirField property getter.
         */

        public static function getSubdirField()
        {
            return static::$subdirField;
        }

        /**
         * $defaultSubdir property getter.
         */

        public static function getDefaultSubdir()
        {
            return static::$defaultSubdir;
        }

        /**
         * Gets an asset related subdirectory path.
         *
         * @param  string $name Subdirectory name.
         * @return array        Path.
         */

        public function getSubdirPath($name = null)
        {
            $name ?: $name = $this->getInfos()[static::getSubdirField()];

            return $this->getDirPath().DS.$name;
        }

        /**
         * Creates an asset related subdirectory.
         *
         * @param  string $name Subdirectory name.
         * @return bool         false on error.
         */

        public function createSubdir($name = null)
        {
            return @mkdir($this->getSubdirPath($name));
        }

        /**
         * Whether an asset related subdirectory exists or not.
         *
         * @param  string $name Subdirectory name.
         * @return bool
         */

        public function subdirExists($name = null)
        {
            return file_exists($this->getSubdirPath($name));
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
         * $string property getter.
         */

        public static function getString()
        {
            return static::$string;
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
            $dirPath = static::getSubdirField() ? $this->getSubdirPath() : $this->getDirPath();

            return $dirPath.DS.$this->getName().'.'.static::getExtension();
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
            return get_pref('last_'.static::getString().'_saved', 'default', true);
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
            $prefs['last_'.static::getString().'_saved'] = $name;

            return set_pref(
                'last_'.static::getString().'_saved',
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
            $prefs['last_'.static::getString().'_saved'] = $name;

            return set_pref(
                'last_'.static::getString().'_saved',
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
                "name = '".doSlash($name)."', ".static::getFileContentsFields()." = '".doSlash($contents)."', ",
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
                'name, '.static::getFileContentsFields(),
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

        public function createFile()
        {
            $infos = $this->getInfos();
            $contents = $infos[static::getFileContentsFields()];
            $subdirField = $this->getSubdirField();

            if ($subdirField) {
                $subdir = $infos[$subdirField];

                return file_put_contents($this->getFilePath($subdir), $contents);
            } else {
                return file_put_contents($this->getFilePath(), $contents);
            }
        }

        /**
         * {@inheritdoc}
         */

        public function createRows($rows = null)
        {
            var_dump($rows);

            $rows === null ? $rows = static::getEssential() : '';

            $skin = $this->getSkin()->getName();
            $fields = array('skin', 'name');
            $fileContentsFields = static::getFileContentsFields();
            $subdirField = static::getSubdirField();
            $values = array();
            $update = "skin=VALUES(skin), name=VALUES(name), ";

            if ($subdirField) {
                $fields[] = $subdirField;

                foreach ($rows as $row) {
                    $values[] = "('".doSlash($skin)."', "
                                ."'".doSlash($row['name'])."', "
                                ."'".doSlash($row[$subdirField])."', "
                                ."'".doSlash($row[$fileContentsFields])."')";
                }

                $update .= $subdirField."=VALUES(".$subdirField."), ";
            } else {
                foreach ($rows as $row) {
                    $values[] = "('".doSlash($skin)."', "
                                ."'".doSlash($row['name'])."', "
                                ."'".doSlash($row[$fileContentsFields])."')";
                }
            }

            $fields[] = $fileContentsFields;
            $update .= $fileContentsFields."=VALUES(".$fileContentsFields.")";

            return safe_query(
                "INSERT INTO ".static::getTable()." (".implode(', ', $fields).") "
                ."VALUES ".implode(', ', $values)
                ." ON DUPLICATE KEY UPDATE ".$update, true
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

            $Files = new DirIterator\RecIteratorIterator(
                new DirIterator\RecRegexIterator(
                    new DirIterator\RecDirIterator($this->getDirPath()),
                    '#^'.$templates.'\.'.$extension.'$#i'
                ),
                static::getSubdirField() ? 1 : 0
            );

            return $this->parseFiles($Files);
        }

        protected function parseFiles($Files) {
            $rows = array();
            $row = array();
            $subdirField = static::getSubdirField();

            foreach ($Files as $File) {
                $row['name'] = $File->getName();
                $subdirField ? $row[$subdirField] = $File->getDir() : '';
                $row[static::getFileContentsFields()] = $File->getContents();

                $rows[] = $row;
            }

            return $rows;
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

        public function getRows($names = null)
        {
            $names === null ? $names = $this->getNames() : '';
            $nameIn = '';

            if ($names) {
                $nameIn = " AND name IN ('".implode("', '", array_map('doSlash', $names))."')";
            }

            $fields = array('name');
            $subdirField = static::getSubdirField();
            $subdirField ? $fields[] = $subdirField : '';
            $fields[] = static::getFileContentsFields();

            $rows = safe_rows_start(
                implode(', ', $fields),
                static::getTable(),
                "skin = '".doSlash($this->getSkin()->getName())."'".$nameIn, true
            );

            $skinRows = array();

            while ($row = nextRow($rows)) {
                $skinRows[] = $row;
            }

            return $skinRows;
        }

        public function duplicateRowsTo($rows)
        {var_dump($rows);
            if (!$this->createRows($rows)) {
                return false;
            }

            return true;
        }

        /**
         * Drops obsolete template rows.
         *
         * @param  array $not An array of template names to NOT drop;
         * @return bool  false on error.
         */

        protected function cleanExtraRows($nameNotIn)
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

        protected function cleanExtraFiles($nameNotIn)
        {
            $files = $this->getFiles();
            $notRemoved = array();

            foreach ($files as $file) {
                $name = $file['name'];
                $this->setName($name);

                if (!$nameNotIn || ($nameNotIn && !in_array($name, $nameNotIn))) {
                    unlink($this->getFilePath()) ?: $notRemoved[] = $name;
                }
            }

            return $notRemoved;
        }

        /**
         * Imports templates.
         *
         * @param  bool   $clean    Whether to removes extra skin template rows or not;
         * @param  bool   $override Whether to insert or update the skins.
         * @return object $this.
         */

        public function import($clean = true, $override = false)
        {
            $skinModel = $this->getSkin();
            $skin = $skinModel->getName();
            $string = static::getString();
            $skinWasLocked = $skinModel->isLocked();

            // Works the skin if not already done.
            if (!$skinWasLocked) {
                if (!$skinModel->isInstalled()) {
                    $this->setResults('skin_unknown', $skin);
                } elseif (!$skinModel->isDirWritable()) {
                    $this->setResults('path_not_writable', $skinModel->getDirPath());
                } elseif ($skinModel->lock()) {
                    $this->setResults('skin_locking_failed', $skinModel->getDirPath());
                }
            }

            // Works with asset related templates once the skin locked.
            if ($skinModel->isLocked()) {
                if (!$this->isDirReadable()) {
                    $this->setResults('path_not_readable', array($skin => $this->getDirPath()));
                } else {
                    $files = $this->getFiles();
                    $imported = array();

                    if (!$files) {
                        $this->setResults('no_'.$string.'_found', array($skin => $this->getDirPath()));
                    } else {
                        if (!$this->createRows($files)) {
                            $this->setResults($string.'_import_failed', array($skin => $notImported));
                        } else {
                            $imported = $this->names;
                        }
                    }

                    $missing = array_diff(static::getEssential('name'), $imported);

                    if ($missing && !$this->setNames($missing)->createRows()) {

                    }
                }

                // Drops extra rows…
                if ($clean) {
                    $notCleaned = $this->cleanExtraRows($imported);

                    if ($notCleaned) {
                        $this->setResults($string.'_cleaning_failed', array($skin => $notCleaned));
                    }
                }

                // Unlocks the skin if needed.
                if ($skinWasLocked && !$skinModel->unlock()) {
                    $this->setResults('skin_unlocking_failed', array($skin => $skinModel->getDirPath()));
                }
            }

            return $this;
        }

        /**
         * Exports skins.
         *
         * @param  bool   $clean Whether to removes extra skin template files or not.
         * @return object $this.
         */

        public function export($clean = true)
        {
            $skinModel = $this->getSkin();
            $skin = $skinModel->getName();
            $string = static::getString();
            $skinWasLocked = $skinModel->isLocked();

            // Works the skin if not already done.
            if (!$skinWasLocked) {
                if (!$skinModel->isInstalled()) {
                    $this->setResults('skin_unknown', $skin);
                } elseif (!$skinModel->isDirWritable() && !$skinModel->createDir()) {
                    $this->setResults('path_not_Writable', $skinModel->getDirPath());
                } elseif ($skinModel->lock()) {
                    $this->setResults('skin_locking_failed', $skinModel->getDirPath());
                }
            }

            // Works with asset related templates once the skin locked.
            if ($skinModel->isLocked()) {
                if (!$this->isDirWritable() && !$this->createDir()) {
                    $this->setResults('path_not_writable', array($skin => $this->getDirPath()));
                } else {
                    $rows = $this->getRows();

                    if (!$rows) {
                        $failed[$skin] = $empty[$skin] = $this->getDirPath();
                    } else {
                        foreach ($rows as $row) {
                            extract($row);

                            $ready = true;

                            $contentsField = static::getFileContentsFields();

                            if (static::getSubdirField()) {
                                $subdirField = static::getSubdirField();
                                $this->setInfos($name, $$subdirField, $$contentsField);

                                if (!$this->subdirExists() && !$this->createSubdir()) {
                                    $this->setResults($string.'_not_writable', array($skin => $name));
                                    $ready = false;
                                }
                            } else {
                                $this->setInfos($name, $$contentsField);
                            }

                            if ($ready) {
                                if ($this->createFile() === false) {
                                    $this->setResults($string.'_export_failed', array($skin => $name));
                                } else {
                                    $exported[] = $name;
                                }
                            }
                        }
                    }
                }

                // Drops extra files…
                if ($clean && isset($exported)) {
                    $notUnlinked = $this->cleanExtraFiles($exported);
                    $this->setResults($string.'_cleaning_failed', array($skin => $notUnlinked));
                }

                // Unlocks the skin if needed.
                if ($skinWasLocked && !$skinModel->unlock()) {
                    $this->setResults('skin_unlocking_failed', array($skin => $skinModel->getDirPath()));
                }
            }

            return $this;
        }
    }
}

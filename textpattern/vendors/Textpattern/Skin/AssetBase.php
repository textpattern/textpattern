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

    abstract class AssetBase extends Base
    {
        /**
         * Asset related directory.
         *
         * @var string Directory name.
         * @see        setDir(), getDir().
         */

        protected static $dir;

        /**
         * Asset related default subdirectory to store exported files.
         *
         * @var string Asset subdirectory name.
         * @see        getDefaultSubdir().
         */

        protected static $defaultSubdir;

        /**
         * Asset related table field used as subdirectories.
         *
         * @var string
         * @see        getSubdirField().
         */

        protected static $subdirField;

        /**
         * Asset related table field(s) used as asset file contents.
         *
         * @var string Field name (could accept an array in the future for JSON contents)
         * @see        getFileContentsField().
         */

        protected static $fileContentsField;

        /**
         * The skin related main file.
         *
         * @see getFilePath().
         */

        protected static $extension = 'txp';

        /**
         * Asset related essential rows as an associative array of the following
         * fields and their value: 'name', ($subdirField, ) $fileContentsField.
         *
         * @var array Associative array of the following fields and their value:
         *            'name', ($subdirField, ) $fileContentsField.
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

         private $files;

         /**
          * Parent skin object.
          *
          * @var object skin
          * @see        __construct().
          */

         protected $skin;

        /**
         * Constructor.
         */

        public function __construct(Skin $skin)
        {
            $this->setSkin($skin);
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

        protected function getSkin()
        {
            return $this->skin;
        }

        /**
         * {@inheritdoc}
         */

        protected static function sanitizeName($name) {
            return sanitizeForPage($name);
        }

        /**
         * $fileContentsField property getter.
         */

        protected static function getFileContentsField()
        {
            return static::$fileContentsField;
        }

        /**
         * $essential property getter.
         */

        protected static function getEssential(
            $key = null,
            $whereKey = null,
            $valueIn = null
        ) {
            if ($key === null) {
                return static::$essential;
            } elseif ($key === '*' && $whereKey) {
                $keyValues = array();

                foreach (static::$essential as $row) {
                    if (in_array($row[$whereKey], $valueIn)) {
                        $keyValues[] = $row;
                    }
                }
            } else {
                $key === null ? $key = 'name' : '';
                $keyValues = array();

                foreach (static::$essential as $row) {
                    if ($whereKey) {
                        if (in_array($row[$whereKey], $valueIn)) {
                            $keyValues[] = $row[$key];
                        }
                    } else {
                        $keyValues[] = $row[$key];
                    }
                }
            }

            return $keyValues;
        }

        /**
         * $extension property getter.
         */

        protected static function getExtension()
        {
            return static::$extension;
        }

        /**
         * $skin property setter.
         */

        protected static function setDir($name)
        {
            static::$dir = $name;

            return $this;
        }

        /**
         * $dir property getter.
         */

        protected static function getDir()
        {
            return static::$dir;
        }

        /**
         * Gets the skin directory path.
         *
         * @return string path.
         */

        protected function getDirPath()
        {
            return $this->getSkin()->getSubdirPath().DS.static::getDir();
        }

        /**
         * $subdirField property getter.
         */

        protected static function getSubdirField()
        {
            return static::$subdirField;
        }

        /**
         * $defaultSubdir property getter.
         */

        protected static function getDefaultSubdir()
        {
            return static::$defaultSubdir;
        }

        /**
         * {@inheritdoc}
         */

        protected function getSubdirPath($name = null)
        {
            $name ?: $name = $this->getInfos()[self::getSubdirField()];

            return $this->getDirPath().DS.$name;
        }

        /**
         * Gets the template related file path.
         *
         * @param string path.
         */

        protected function getFilePath($name = null)
        {
            $dirPath = self::getSubdirField() ? $this->getSubdirPath($name) : $this->getDirPath();

            return $dirPath.DS.$this->getName().'.'.self::getExtension();
        }

        /**
         * {@inheritdoc}
         */

        protected function setInstalled()
        {
            // TODO
        }

        /**
         * {@inheritdoc}
         */

        protected function isInstalled()
        {
            // TODO
        }

        /**
         * {@inheritdoc}
         */

        public static function getEditing()
        {
            return get_pref('last_'.self::getString().'_saved', 'default', true);
        }

        /**
         * {@inheritdoc}
         */

        public function setEditing()
        {
            global $prefs;

            $name = $this->getName();
            $prefs['last_'.self::getString().'_saved'] = $name;

            return set_pref(
                'last_'.self::getString().'_saved',
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

        protected static function resetEditing()
        {
            global $prefs;

            $name = safe_field('page', 'txp_section', 'name = "default"');
            $prefs['last_'.self::getString().'_saved'] = $name;

            return set_pref(
                'last_'.self::getString().'_saved',
                $name,
                'skin',
                PREF_HIDDEN,
                'text_input',
                0,
                PREF_PRIVATE
            );
        }

        /**
         * {@inheritdoc}
         */

        protected function createFile()
        {
            $infos = $this->getInfos();
            $contents = $infos[self::getFileContentsField()];
            $subdirField = $this->getSubdirField();

            if ($subdirField) {
                $subdir = $infos[$subdirField];
                $path = $this->getFilePath($subdir);
            } else {
                $path = $this->getFilePath();
            }

            return file_put_contents($path, $contents);
        }

        /**
         * {@inheritdoc}
         */

        protected function createRow()
        {
            // TODO
        }

        /**
         * {@inheritdoc}
         */

        protected function updateRow()
        {
            // TODO
        }

        /**
         * {@inheritdoc}
         */

        protected function getRow()
        {
            // TODO
        }

        /**
         * {@inheritdoc}
         */

        public function createRows($rows = null)
        {
            $rows === null ? $rows = self::getEssential() : '';

            $skin = $this->getSkin()->getName();
            $fields = array('skin', 'name');
            $fileContentsField = self::getFileContentsField();
            $subdirField = self::getSubdirField();
            $values = array();
            $update = "skin=VALUES(skin), name=VALUES(name), ";

            if ($subdirField) {
                $fields[] = $subdirField;

                foreach ($rows as $row) {
                    $values[] = "('".doSlash($skin)."', "
                                ."'".doSlash($row['name'])."', "
                                ."'".doSlash($row[$subdirField])."', "
                                ."'".doSlash($row[$fileContentsField])."')";
                }

                $update .= $subdirField."=VALUES(".$subdirField."), ";
            } else {
                foreach ($rows as $row) {
                    $values[] = "('".doSlash($skin)."', "
                                ."'".doSlash($row['name'])."', "
                                ."'".doSlash($row[$fileContentsField])."')";
                }
            }

            $fields[] = $fileContentsField;
            $update .= $fileContentsField."=VALUES(".$fileContentsField.")";

            return safe_query(
                "INSERT INTO ".self::getTable()." (".implode(', ', $fields).") "
                ."VALUES ".implode(', ', $values)
                ." ON DUPLICATE KEY UPDATE ".$update
            );
        }

        /**
         * {@inheritdoc}
         */

        public function getRows()
        {
            $names = $this->getNames();
            $nameIn = '';

            if ($names) {
                $nameIn = " AND name IN ('".implode("', '", array_map('doSlash', $names))."')";
            }

            $fields = array('name');
            $subdirField = self::getSubdirField();
            $subdirField ? $fields[] = $subdirField : '';
            $fields[] = self::getFileContentsField();

            $rows = safe_rows_start(
                implode(', ', $fields),
                self::getTable(),
                "skin = '".doSlash($this->getSkin()->getName())."'".$nameIn
            );

            $skinRows = array();

            while ($row = nextRow($rows)) {
                $skinRows[] = $row;
            }

            return $skinRows;
        }

        /**
         * {@inheritdoc}
         */

        public function deleteRows()
        {
            $names = $this->getNames();
            $nameIn = '';

            if ($names) {
                $nameIn = " AND name IN ('".implode("', '", array_map('doSlash', $names))."')";
            }

            return safe_delete(
                self::getTable(),
                "skin = '".doSlash($this->getSkin()->getName())."'".$nameIn
            );
        }

        /**
         * Drops obsolete template rows.
         *
         * @param  array $not An array of template names to NOT drop;
         * @return bool  false on error.
         */

        protected function deleteExtraRows()
        {
            return safe_delete(
                self::getTable(),
                "skin = '".doSlash($this->getSkin()->getName())."' AND "
                ."name NOT IN ('".implode("', '", array_map('doSlash', $this->getNames()))."')"
            );
        }

        /**
         * Updates a skin name in use.
         *
         * @param string $name A skin newname.
         */

        public function updateSkin()
        {
            $thisSkin = $this->getSkin();

            return safe_update(
                self::getTable(),
                "skin = '".doSlash($thisSkin->getName())."'",
                "skin = '".doSlash($thisSkin->getBase())."'"
            );
        }

        /**
         * Gets files from a defined directory.
         *
         * @param  array  $templates Template names to filter results;
         * @return object            RecursiveIteratorIterator
         */

        protected function setFiles()
        {
            $templates = array();
            $extension = self::getExtension();

            foreach ($this->getNames() as $name) {
                $templates = $name.$extension;
            }

            $this->files = new DirIterator\RecIteratorIterator(
                new DirIterator\RecFilterIterator(
                    new DirIterator\RecDirIterator($this->getDirPath()),
                    $templates
                ),
                self::getSubdirField() ? 1 : 0
            );

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        protected function getFiles($asRows = false)
        {
            $this->files === null ? $this->setFiles() : '';

            return $this->files;
        }

        /**
         * {@inheritdoc}
         */

        protected function parseFiles() {
            $rows = array();
            $row = array();
            $subdirField = self::getSubdirField();

            $parsed = $names = array();

            foreach ($this->getFiles() as $File) {
                $name = $File->getName();
                $essentialSubdir = implode('', $this->getEssential($subdirField, 'name', array($name)));

                if (in_array($name, $parsed)) {
                    $this->mergeResult('duplicated', $name);
                } elseif ($subdirField && $essentialSubdir && $essentialSubdir !== $File->getDir()) {
                    $this->mergeResult('wrong_type', $name);
                } else {
                    $names[] = $name;
                    $parsed[] = $row['name'] = $name;
                    $subdirField ? $row[$subdirField] = $File->getDir() : '';
                    $row[self::getFileContentsField()] = $File->getContents();

                    $rows[] = $row;
                }
            }

            $missingNames = array_diff(self::getEssential('name'), $parsed);

            $this->setNames(array_merge($names, $missingNames));

            $missingRows = self::getEssential('*', 'name', $missingNames);

            return array_merge($rows, $missingRows);
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
                $name = $file->getName();
                $this->setName($name);

                if (!$nameNotIn || ($nameNotIn && !in_array($name, $nameNotIn))) {
                    unlink($this->getFilePath($file->getDir())) ?: $notRemoved[] = $name;
                }
            }

            return $notRemoved;
        }

        /**
         * {@inheritdoc}
         */

        public function create()
        {
            // TODO
        }

        /**
         * {@inheritdoc}
         */

        public function update()
        {
            // TODO
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate()
        {
            // TODO
        }

        /**
         * Import the $names property value related templates.
         *
         * @param  bool   $clean    Whether to removes extra asset related template rows or not;
         * @param  bool   $override Whether to insert or update the skins.
         * @return object $this     The current object (chainable).
         */

        public function import($clean = false, $override = false)
        {
            $thisSkin = $this->getSkin();
            $skin = $thisSkin->getName();
            $skinWasLocked = $thisSkin->isLocked();

            /**
             * TODO Allow import from skin assets related admin panels?
             *      This would help… #1
             *
             * if (!$skinWasLocked) {
             *     if (!$thisSkin->isInstalled()) {
             *         $this->mergeResult('skin_unknown', $skin);
             *     } elseif (!$thisSkin->isWritableDir()) {
             *         $this->mergeResult('path_not_writable', $thisSkin->getDirPath());
             *     } elseif ($thisSkin->lock()) {
             *         $this->mergeResult('skin_locking_failed', $thisSkin->getDirPath());
             *     }
             * }
             */

            if ($thisSkin->isLocked()) {
                $string = self::getString();
                $dirPath = $this->getDirPath();

                if (!is_readable($dirPath)) {
                    $this->mergeResult('path_not_readable', array($skin => $dirPath));
                } else {
                    if (!$this->getFiles()) {
                        $this->mergeResult('no_'.$string.'_found', array($skin => $dirPath));
                    }

                    if (!$this->createRows($this->parseFiles())) {
                        $this->mergeResult($string.'_import_failed', array($skin => $notImported));
                    } elseif (!$skinWasLocked) {
                        $this->mergeResult($string.'_imported', array($skin => $notImported), 'success');
                    }

                    // Drops extra rows…
                    if ($clean) {
                        if (!$this->deleteExtraRows()) {
                            $this->mergeResult($string.'_cleaning_failed', array($skin => $notCleaned));
                        }
                    }
                }

                /**
                 * TODO Allow import from skin assets related admin panels?
                 *      This would help… #2
                 *
                 * if ($skinWasLocked && !$thisSkin->unlock()) {
                 *     $this->mergeResult('skin_unlocking_failed', array($skin => $thisSkin->getDirPath()));
                 * }
                 */
            }

            return $this;
        }

        /**
         * Export the $names property value related templates.
         *
         * @param  bool   $clean Whether to removes extra asset related files or not.
         * @return object $this The current object (chainable).
         */

        public function export($clean = false, $override = false)
        {
            $thisSkin = $this->getSkin();
            $skin = $thisSkin->getName();
            $skinWasLocked = $thisSkin->isLocked();

            /**
             * TODO Allow export from skin assets related admin panels?
             *      This would help… #1
             *
             * if (!$skinWasLocked) {
             *     if (!$thisSkin->isInstalled()) {
             *         $this->mergeResult('skin_unknown', $skin);
             *     } elseif (!$thisSkin->isWritableDir() && !$thisSkin->createDir()) {
             *         $this->mergeResult('path_not_Writable', $thisSkin->getDirPath());
             *     } elseif ($thisSkin->lock()) {
             *         $this->mergeResult('skin_locking_failed', $thisSkin->getDirPath());
             *     }
             * }
             */

            if ($thisSkin->isLocked()) {
                $string = self::getString();
                $dirPath = $this->getDirPath();

                if (!is_writable($dirPath) && !@mkdir($dirPath)) {
                    $this->mergeResult('path_not_writable', array($skin => $dirPath));
                } else {
                    $rows = $this->getRows();

                    if (!$rows) {
                        $failed[$skin] = $empty[$skin] = $dirPath;
                    } else {
                        foreach ($rows as $row) {
                            extract($row);

                            $ready = true;

                            $subdirField = self::getSubdirField();
                            $contentsField = self::getFileContentsField();

                            if ($subdirField) {
                                $subdirPath = $this->setInfos($name, $$subdirField, $$contentsField)->getSubdirPath();

                                if (!is_dir($subdirPath) && !@mkdir($subdirPath)) {
                                    $this->mergeResult($string.'_not_writable', array($skin => $name));
                                    $ready = false;
                                }
                            } else {
                                $this->setInfos($name, $$contentsField);
                            }

                            if ($ready) {
                                if ($this->createFile() === false) {
                                    $this->mergeResult($string.'_export_failed', array($skin => $name));
                                } else {
                                    if (!$skinWasLocked) {
                                        $this->mergeResult($string.'_exported', array($skin => $name), 'success');
                                    }

                                    $exported[] = $name;
                                }
                            }
                        }
                    }

                    // Drops extra files…
                    if ($clean && isset($exported)) {
                        $notUnlinked = $this->cleanExtraFiles($exported);

                        if ($notUnlinked) {
                            $this->mergeResult($string.'_cleaning_failed', array($skin => $notUnlinked));
                        }
                    }
                }

                /**
                 * TODO Allow export from skin assets related admin panels?
                 *      This would help… #2
                 *
                 * if ($skinWasLocked && !$thisSkin->unlock()) {
                 *     $this->mergeResult('skin_unlocking_failed', array($skin => $thisSkin->getDirPath()));
                 * }
                 */
            }

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function delete($clean = false)
        {
            // TODO
        }

        /**
         * Render the Skin switch form.
         *
         * @return HTML
         */

        public function renderSelectEdit()
        {
            $thisSkin = $this->getSkin();
            $skins = $thisSkin::getInstalled();

            if (count($installed) > 1) {
                $select = selectInput('skin', $skins, $thisSkin::getEditing(), false, 1, 'skin');

                return form(
                    inputLabel('skin', $select, 'skin')
                    .eInput(self::getString())
                    .sInput(self::getString().'_skin_change'),
                    '',
                    '',
                    'post'
                );
            }

            return;
        }
    }
}

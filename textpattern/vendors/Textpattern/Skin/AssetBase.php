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
         * Current skin model.
         *
         * @var object skin
         * @see        __construct().
         */

        protected $skin;

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

         private $files;

         protected $step;

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

        protected function setStep($step)
        {
            $this->step = $step;

            return $this;
        }

        /**
         * $skin property getter.
         */

        protected function getStep()
        {
            return $this->step;
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
         * $fileContentsFields property getter.
         */

        protected static function getFileContentsFields()
        {
            return static::$fileContentsFields;
        }

        /**
         * $essential property getter.
         */

        protected static function getEssential($field = null, $whereField = null, $valueIn = null)
        {
            if ($field === null) {
                return static::$essential;
            } elseif ($field === '*' && $whereField) {
                $fieldValues = array();

                foreach (static::$essential as $row) {
                    if (in_array($row[$whereField], $valueIn)) {
                        $fieldValues[] = $row;
                    }
                }
            } else {
                $field === null ? $field = 'name' : '';
                $fieldValues = array();

                foreach (static::$essential as $row) {
                    if ($whereField) {
                        if (in_array($row[$whereField], $valueIn)) {
                            $fieldValues[] = $row[$field];
                        }
                    } else {
                        $fieldValues[] = $row[$field];
                    }
                }
            }

            return $fieldValues;
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

        protected function setDir($name = null)
        {
            $name === null ? $name = self::getDefaultDir() : '';

            $this->dir = $name;

            return $this;
        }

        /**
         * $dir property getter.
         */

        protected static function getDefaultDir()
        {
            return static::$defaultDir;
        }


        /**
         * $dir property getter.
         */

        protected function getDir()
        {
            $this->dir === null ? $this->setDir() : '';

            return $this->dir;
        }

        /**
         * Gets the skin directory path.
         *
         * @return string path.
         */

        protected function getDirPath()
        {
            return $this->getSkin()->getSubdirPath().DS.$this->getDir();
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
         * Gets an asset related subdirectory path.
         *
         * @param  string $name Subdirectory name.
         * @return array        Path.
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
            $contents = $infos[self::getFileContentsFields()];
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

        public function createRows()
        {
            if ($this->getStep() === 'import') {
                $rows = $this->parseFiles();
            } else {
                $rows = self::getEssential();
            }

            $skin = $this->getSkin()->getName();
            $fields = array('skin', 'name');
            $fileContentsFields = self::getFileContentsFields();
            $subdirField = self::getSubdirField();
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
            $fields[] = self::getFileContentsFields();

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
            $thisNames = $this->getNames();
            $nameIn = '';

            if ($thisNames) {
                $nameIn = " AND name IN ('".implode("', '", array_map('doSlash', $thisNames))."')";
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
            $thisNames = $this->getNames();

            if ($thisNames) {
                $templates = '('.implode('|', $thisNames).')';
            } else {
                $templates = self::getNamePattern();
            }

            $extension = self::getExtension();
            $extension === 'txp' ? $extension = '(txp|html)' : '';

            $this->files = new DirIterator\RecIteratorIterator(
                new DirIterator\RecRegexIterator(
                    new DirIterator\RecDirIterator($this->getDirPath()),
                    '#^'.$templates.'\.'.$extension.'$#i'
                ),
                self::getSubdirField() ? 1 : 0
            );

            return $this;
        }

        protected function getFiles($asRows = false)
        {
            $this->files === null ? $this->setFiles() : '';

            return $this->files;
        }

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
                    $row[self::getFileContentsFields()] = $File->getContents();

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

        public function import($clean = true, $override = false)
        {
            $this->setStep('import'); // See createRows().

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

                    if (!$this->createRows()) {
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

        public function export($clean = true)
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
                            $contentsField = self::getFileContentsFields();

                            if ($subdirField) {
                                $subdirPath = $this->setInfos($name, $$subdirField, $$contentsField)->getSubdirPath();

                                if (!file_exists($subdirPath) && !@mkdir($subdirPath)) {
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
                                    $skinWasLocked ?: $this->mergeResult($string.'_exported', array($skin => $name), 'success');
                                    $exported[] = $name;
                                }
                            }
                        }
                    }

                    // Drops extra files…
                    if ($clean && isset($exported)) {
                        $notUnlinked = $this->cleanExtraFiles($exported);
                        $this->mergeResult($string.'_cleaning_failed', array($skin => $notUnlinked));
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

        public function delete()
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
            $installed = Skin::getInstalled();

            if (count($installed) > 1) {
                return form(
                    inputLabel(
                        'skin',
                        selectInput('skin', $installed, $this->getSkin()::getEditing(), false, 1, 'skin'),
                        'skin'
                    )
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

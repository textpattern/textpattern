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

        public function __construct(Skin $skin = null)
        {
            $this->setSkin($skin);
        }

        /**
         * $skin property setter.
         */

        protected function setSkin(Skin $skin = null)
        {
            $this->skin = $skin === null ? \Txp::get('Textpattern\Skin\Skin')->setName() : $skin;

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
         * {@inheritdoc}
         */

        protected static function sanitize($name) {
            return sanitizeForPage($name);
        }

        /**
         * $infos property getter/parser.
         *
         * @param  bool  $safe Whether to get the property value
         *                     as an SQL query related string or not.
         * @return mixed TODO
         */

        protected function getInfos($safe = false)
        {
            if ($safe) {
                $infoQuery = array();

                foreach ($this->infos as $col => $value) {
                    if ($col === self::getFileContentsField()) {
                        $infoQuery[] = $col." = '".$value."'";
                    } else {
                        $infoQuery[] = $col." = '".doSlash($value)."'";
                    }
                }

                return implode(', ', $infoQuery);
            }

            return $this->infos;
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

        protected function isInstalled()
        {
            if ($this->installed === null) {
                $isInstalled = (bool) safe_field(
                    'name',
                    self::getTable(),
                    "name = '".doSlash($this->getName())."' AND skin = '".doSlash($this->getSkin()->getName())."'"
                );
            } else {
                $isInstalled = in_array($this->getName(), array_values(self::getInstalled()));
            }

            return $isInstalled;
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

        public function setEditing($name = null)
        {
            global $prefs;

            $name !== null ?: $name = $this->getName();
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

        public function removeEditing()
        {
            global $prefs;

            $string = $this->getString();

            unset($prefs['last_'.$string.'_saved']);
            return remove_pref('last_'.$string.'_saved', $string);
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

        protected function createFile($path = null, $contents = null)
        {
            if ($path === null || $contents === null) {
                $infos = $this->getInfos();
            }

            if ($path === null) {
                $subdirField = $this->getSubdirField();
                $file = $this->getName().'.'.self::getExtension();

                if ($subdirField) {
                    $path = $infos[$subdirField].DS.$file;
                } else {
                    $path = $file;
                }
            }

            if ($contents === null) {
                $infos = $this->getInfos();
                $contents = $infos[self::getFileContentsField()];
            }

            return file_put_contents($this->getDirPath().DS.$path, $contents);
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

        public function getRows($things = null, $where = null)
        {
            if ($things === null) {
                $things = 'name, ';
                $subdirField = self::getSubdirField();
                !$subdirField ?: $things .= $subdirField.', ';
                $things .= self::getFileContentsField();
            }

            if ($where === null) {
                $names = $this->getNames();
                $nameIn = '';

                if ($names) {
                    $nameIn = " AND name IN ('".implode("', '", array_map('doSlash', $names))."')";
                }

                $where = "skin = '".doSlash($this->getSkin()->getName())."'".$nameIn;
            }

            $rs = safe_rows_start($things, self::getTable(), $where);
            $rows = array();

            while ($row = nextRow($rs)) {
                $rows[] = $row;
            }

            return $rows;
        }

        /**
         * {@inheritdoc}
         */

        public function deleteRows($where = null)
        {
            if ($where === null) {
                $names = $this->getNames();
                $nameIn = '';

                if ($names) {
                    $nameIn = " AND name IN ('".implode("', '", array_map('doSlash', $names))."')";
                }

                $where = "skin = '".doSlash($this->getSkin()->getName())."'".$nameIn;
            }

            return safe_delete(self::getTable(), $where);
        }

        /**
         * Drops obsolete template rows.
         *
         * @param  array $not An array of template names to NOT drop;
         * @return bool  false on error.
         */

        protected function deleteExtraRows()
        {
            return $this->deleteRows(
                "skin = '".doSlash($this->getSkin()->getName())."' AND "
                ."name NOT IN ('".implode("', '", array_map('doSlash', $this->getNames()))."')"
            );
        }

        /**
         * Updates a skin name in use.
         *
         * @param string $name A skin newname.
         */

         public function updateRow($set = null, $where = null)
         {
             $set !== null ?: $set = $this->getInfos(true);
             $where !== null ?: $where = "skin = '".doSlash($this->getSkin()->getName())."' name = '".doSlash($this->getBase())."'";

             return safe_update(self::getTable(), $set, $where);
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
                $templates[] = $name.'.'.$extension;
            }

            $files = \Txp::get('Textpattern\Iterator\RecDirIterator', $this->getDirPath());
            $filter = \Txp::get('Textpattern\Iterator\RecFilterIterator', $files)->setNames($templates);
            $filteredFiles = \Txp::get('Textpattern\Iterator\RecIteratorIterator', $filter);
            $filteredFiles->setMaxDepth(self::getSubdirField() ? 1 : 0);

            $this->files = $filteredFiles;

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        protected function getFiles()
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
            $string = self::getString();

            $files = $this->getFiles();
            $parsed = $parsedFiles = $names = array();

            if ($files) {
                foreach ($files as $File) {
                    $name = $File->getName();
                    $filename = $File->getFilename();

                    if ($subdirField) {
                        $essentialSubdir = implode('', $this->getEssential($subdirField, 'name', array($name)));
                    }

                    if (in_array($filename, $parsedFiles)) {
                        $this->mergeResult($string.'_duplicate', $filename);
                    } elseif ($subdirField && $essentialSubdir && $essentialSubdir !== $File->getDir()) {
                        $this->mergeResult($string.'_wrong_type', $name);
                    } else {
                        $names[] = $name;
                        $parsed[] = $row['name'] = $name;
                        $parsedFiles[] = $filename;
                        $subdirField ? $row[$subdirField] = $File->getDir() : '';
                        $row[self::getFileContentsField()] = $File->getContents();

                        $rows[] = $row;
                    }
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

        protected function deleteExtraFiles($nameNotIn)
        {
            $files = $this->getFiles();
            $notRemoved = array();

            if ($files) {
                foreach ($files as $file) {
                    $name = $file->getName();
                    $this->setName($name);

                    if (!$nameNotIn || ($nameNotIn && !in_array($name, $nameNotIn))) {
                        unlink($this->getFilePath($file->getDir())) ?: $notRemoved[] = $name;
                    }
                }
            }

            return $notRemoved;
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
            $names = $this->getNames();
            $string = self::getString();

            callback_event($string.'.import', '', 1, array(
                'skin'  => $skin,
                'names' => $names,
            ));

            $done = array();
            $dirPath = $this->getDirPath();

            if (!is_readable($dirPath)) {
                $this->mergeResult('path_not_readable', array($skin => array($dirPath)));
            } else {
                if (!$this->getFiles()) {var_dump($this->files);
                    $this->mergeResult('no_'.$string.'_found', array($skin => array($dirPath)));
                }

                if (!$this->createRows($this->parseFiles())) {
                    $this->mergeResult($string.'_import_failed', array($skin => $names));
                } else {
                    $done[] = $names;

                    $this->mergeResult($string.'_imported', array($skin => $names), 'success');
                }

                // Drops extra rows…
                if ($clean) {
                    if (!$this->deleteExtraRows()) {
                        $this->mergeResult($string.'_cleaning_failed', array($skin => $notCleaned));
                    }
                }
            }

            callback_event($string.'.import', '', 0, array(
                'skin'  => $skin,
                'names' => $names,
                'done'  => $done,
            ));

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
            $names = $this->getNames();

            $string = self::getString();

            callback_event($string.'.export', '', 1, array(
                'skin'  => $skin,
                'names' => $names,
            ));

            $done = array();
            $string = self::getString();
            $dirPath = $this->getDirPath();

            if (!is_writable($dirPath) && !@mkdir($dirPath)) {
                $this->mergeResult('path_not_writable', array($skin => array($dirPath)));
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
                                $this->mergeResult($string.'_not_writable', array($skin => array($name)));
                                $ready = false;
                            }
                        } else {
                            $this->setInfos($name, $$contentsField);
                        }

                        if ($ready) {
                            if ($this->createFile() === false) {
                                $this->mergeResult($string.'_export_failed', array($skin => array($name)));
                            } else {
                                $this->mergeResult($string.'_exported', array($skin => array($name)), 'success');

                                $done[] = $name;
                            }
                        }
                    }
                }

                // Drops extra files…
                if ($clean && isset($done)) {
                    $notUnlinked = $this->deleteExtraFiles($done);

                    if ($notUnlinked) {
                        $this->mergeResult($string.'_cleaning_failed', array($skin => $notUnlinked));
                    }
                }
            }

            callback_event($string.'.export', '', 1, array(
                'skin'  => $skin,
                'names' => $names,
                'done'  => $done,
            ));

            return $this;
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

            if (count($skins) > 1) {
                return form(
                    inputLabel(
                        'skin',
                        selectInput('skin', $skins, $thisSkin::getEditing(), false, 1, 'skin'),
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

        /**
         * Changes the skin in which styles are being edited.
         *
         * Keeps track of which skin is being edited from panel to panel.
         *
         * @param  string $skin Optional skin name. Read from GET/POST otherwise
         */

        public function selectEdit($skin = null)
        {
            if ($skin === null) {
                $skin = gps('skin');
            }

            if ($skin) {
                $skin = $this->getSkin()->setEditing($skin);
            }

            return $this;
        }
    }
}

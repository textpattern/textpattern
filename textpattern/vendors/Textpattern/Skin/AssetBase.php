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
                $key !== null ?: $key = 'name';
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
         * $defaultSubdir property getter.
         */

        protected static function getSubdirValues()
        {
            return static::$subdirValues;
        }

        /**
         * $dir property value related subdirectory parser.
         *
         * @param  string $name Subdirectory name.
         * @return string       The subdirectory name if valid or the default subdirectory.
         */

        protected static function parseSubdir($name)
        {
            if (in_array($name, self::getSubdirValues())) {
                return $name;
            } else {
                return self::getDefaultSubdir();
            }
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

        public static function getEditing()
        {
            return get_pref('last_'.self::getEvent().'_saved', 'default', true);
        }

        /**
         * {@inheritdoc}
         */

        public function setEditing($name = null)
        {
            global $prefs;

            $name !== null ?: $name = $this->getName();
            $prefs['last_'.self::getEvent().'_saved'] = $name;

            return set_pref(
                'last_'.self::getEvent().'_saved',
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

            $event = self::getEvent();

            unset($prefs['last_'.$event.'_saved']);
            return remove_pref('last_'.$event.'_saved', $event);
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
            $prefs['last_'.self::getEvent().'_saved'] = $name;

            return set_pref(
                'last_'.self::getEvent().'_saved',
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
            $rows !== null ?: $rows = self::getEssential();

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
         * {@inheritdoc}
         */

        protected function parseFiles($files) {
            $rows = $row = array();
            $subdirField = self::getSubdirField();
            $event = self::getEvent();

            $parsed = $parsedFiles = $names = array();

            if ($files) {
                $thisSkin = $this->getSkin();
                $skin = $thisSkin->getName();

                foreach ($files as $file) {
                    $filename = $file->getFilename();
                    $name = pathinfo($filename, PATHINFO_FILENAME);;

                    if ($subdirField) {
                        $essentialSubdir = implode('', $this->getEssential($subdirField, 'name', array($name)));
                    }

                    if (in_array($filename, $parsedFiles)) {
                        $this->mergeResult($event.'_duplicate', array($skin => array($filename)));
                    } elseif ($subdirField && $essentialSubdir && $essentialSubdir !== basename($file->getPath())) {
                        $this->mergeResult($event.'_wrong_subdir', array($skin => array($name.' → '.basename($file->getPath()))));
                    } else {
                        $names[] = $name;
                        $parsed[] = $row['name'] = $name;
                        $parsedFiles[] = $filename;
                        if ($subdirField) {
                            $subdir = basename($file->getPath());
                            $subdirValid = self::parseSubdir($subdir);

                            if ($subdir !== $subdirValid) {
                                $this->mergeResult($event.'_subdir_change', array($skin => array($name)));
                            }

                            $row[$subdirField] = $subdirValid;
                        }

                        $row[self::getFileContentsField()] = $file->getContents();

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
            $filenames = array();
            $extension = self::getExtension();

            foreach ($this->getNames() as $name) {
                $filenames[] = $name.'.'.$extension;
            }

            $files = $this->getFiles($filenames, self::getSubdirField() ? 1 : 0);
            $notRemoved = array();

            if ($files) {
                foreach ($files as $file) {
                    $name = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    $this->setName($name);

                    if (!$nameNotIn || ($nameNotIn && !in_array($name, $nameNotIn))) {
                        unlink($this->getFilePath(basename($file->getPath()))) ?: $notRemoved[] = $name;
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
            $skin = $thisSkin !== null ? $thisSkin->getName() : Skin::getEditing();
            $names = $this->getNames();
            $event = self::getEvent();

            callback_event($event.'.import', '', 1, array(
                'skin'  => $skin,
                'names' => $names,
            ));

            $done = array();
            $dirPath = $this->getDirPath();

            if (!is_readable($dirPath)) {
                $this->mergeResult('path_not_readable', array($skin => array($dirPath)));
            } else {
                $filenames = array();
                $extension = self::getExtension();

                foreach ($this->getNames() as $name) {
                    $filenames[] = $name.'.'.$extension;
                }

                $files = $this->getFiles($filenames, self::getSubdirField() ? 1 : 0);

                if (!$files) {
                    $this->mergeResult('no_'.$event.'_found', array($skin => array($dirPath)));
                }

                if (!$this->createRows($this->parseFiles($files))) {
                    $this->mergeResult($event.'_import_failed', array($skin => $names));
                } else {
                    $done[] = $names;

                    $this->mergeResult($event.'_imported', array($skin => $names), 'success');
                }

                // Drops extra rows…
                if ($clean) {
                    if (!$this->deleteExtraRows()) {
                        $this->mergeResult($event.'_cleaning_failed', array($skin => $notCleaned));
                    }
                }
            }

            callback_event($event.'.import', '', 0, array(
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
            $skin = $thisSkin !== null ? $thisSkin->getName() : Skin::getEditing();
            $names = $this->getNames();

            $event = self::getEvent();

            callback_event($event.'.export', '', 1, array(
                'skin'  => $skin,
                'names' => $names,
            ));

            $done = array();
            $event = self::getEvent();
            $dirPath = $this->getDirPath();

            if (!is_writable($dirPath) && !@mkdir($dirPath)) {
                $this->mergeResult('path_not_writable', array($skin => array($dirPath)));
            } else {
                $ready = array();

                foreach ($names as $name) {
                    if (!$this->isInstalled()) {
                        $this->mergeResult($event.'_unknown', array($skin => array($name)));
                    } else {
                        $ready[] = $name;
                    }
                }

                $rows = $this->setNames($ready)->getRows();

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
                                $this->mergeResult($event.'_not_writable', array($skin => array($name)));
                                $ready = false;
                            }
                        } else {
                            $this->setInfos($name, $$contentsField);
                        }

                        if ($ready) {
                            if ($this->createFile() === false) {
                                $this->mergeResult($event.'_export_failed', array($skin => array($name)));
                            } else {
                                $this->mergeResult($event.'_exported', array($skin => array($name)), 'success');

                                $done[] = $name;
                            }
                        }
                    }
                }

                // Drops extra files…
                if ($clean && isset($done)) {
                    $notUnlinked = $this->deleteExtraFiles($done);

                    if ($notUnlinked) {
                        $this->mergeResult($event.'_cleaning_failed', array($skin => $notUnlinked));
                    }
                }
            }

            callback_event($event.'.export', '', 1, array(
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
            $skins = $thisSkin->getInstalled();

            if (count($skins) > 1) {
                return form(
                    inputLabel(
                        'skin',
                        selectInput('skin', $skins, $thisSkin::getEditing(), false, 1, 'skin'),
                        'skin'
                    )
                    .eInput(self::getEvent())
                    .sInput(self::getEvent().'_skin_change'),
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

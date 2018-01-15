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

    class Skin extends CommonBase
    {
        /**
         * Assets related objects.
         *
         * @var array
         * @see       setAssets().
         */

        private $assets;

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
         * Sections used by the skin defined.
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
         * Class related asset names to work with.
         *
         * @var array Names.
         * @see       setNames(), getNames().
         */

        protected static $basePath;

        /**
         * Constructor.
         *
         * @param string $names Skin name.
         */

        public function __construct()
        {
            static::setBasePath();
            $this->setAssets();
        }

        /**
         * Gets the skin directory path.
         *
         * @return string Path.
         */

        public static function setBasePath($path = null)
        {
            $path === null ? $path = get_pref('path_to_site').DS.get_pref('skin_dir') : '';

            self::$basePath = rtrim($path, DS);

            return static::getBasePath();
        }

        /**
         * Gets the skin directory path.
         *
         * @return string Path.
         */

        public static function getBasePath()
        {
            if (static::$basePath === null) {
                 static::setBasePath();
            }

            return static::$basePath;
        }

        /**
         * $names property setter.
         *
         * @param  array  $names Skin names.
         * @return object $this.
         */

        public function setNames($names = null)
        {
            if ($names === null) {
                $this->names = array();
            } else {
                $parsed = array();

                foreach ($names as $name) {
                    $parsed[] = sanitizeForTheme($name);
                }

                $this->names = $parsed;
            }

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
         * $name property setter.
         *
         * @param object $this.
         */

        public function setName($name = null)
        {
            $this->name = $name === null ? static::getEditing() : $name;

            return $this;
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

        public function setInfos(
            $name,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null
        ) {
            $name = sanitizeForTheme($name);
            // TODO check $author_uri against a URL related REGEX?

            $this->infos = compact('name', 'title', 'version', 'description', 'author', 'author_uri');

            return $this;
        }

        /**
         * $names property getter.
         *
         * @return array Skin names.
         */

        public function setBase($name)
        {
            $this->base = sanitizeForTheme($name);

            return $this;
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
         * Gets the skin directory path.
         *
         * @param string path.
         */

        public function getDirPath($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            return static::getBasePath().DS.$name;
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
         * Makes the skin related directory.
         *
         * @param bool false on error.
         */

        public function removeDir()
        {
            return @rmdir($this->getDirPath());
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
            return $this->getDirPath().DS.static::$file;
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

            set_pref('skin_editing', $name, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);

            return static::getEditing();
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
                $this->getInfos(true), true
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
                $this->getInfos(true),
                "name = '".doSlash($this->getBase())."'", true
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

        public function createFile() {
            $contents = array_merge($this->getInfos(), array('txp-type' => 'textpattern-theme'));

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
            return new DirIterator\RecIteratorIterator(
                new DirIterator\RecRegexIterator(
                    new DirIterator\RecDirIterator(static::getBasePath()),
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
                $rows = safe_rows('name, title', static::getTable(), '1 = 1');

                static::$installed = array();

                foreach ($rows as $row) {
                    static::$installed[$row['name']] = $row['title'];
                }
            }

            return static::getInstalled();
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

        public static function unsetInstalled($names)
        {
            static::$installed = array_diff_key(
                static::getInstalled(),
                array_fill_keys($names, '')
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
                'name, title, version, description, author, author_uri',
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

        public function deleteRows($names = null)
        {
            $names === null ? $names = $this->getNames() : '';

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

        /**
         * $assets property setter.
         *
         * @param array $pages  Page names to work with;
         * @param array $forms  Page names to work with;
         * @param array $styles Page names to work with.
         */

        public function setAssets($pages = null, $forms = null, $styles = null)
        {
            $assets = array(
                'Page' => $pages,
                'Form' => $forms,
                'CSS'  => $styles,
            );

            foreach ($assets as $class => $assets) {
                $this->assets[] = \Txp::get('Textpattern\Skin\\'.$class, $this)->setNames($assets);
            }

            return $this;
        }

        /**
         * $assets property getter.
         */

        public function getAssets()
        {
            return $this->assets;
        }

        /**
         * Creates a skin and its essential asset templates.
         *
         * @param  string $assetsFrom
         * @return object $this.
         */

        public function create() {
            $name = $this->getName();

            callback_event('skin.create', '', 1, array('name' => $name));

            if (empty($name)) {
                $this->setResults('skin_name_invalid', $name);
            } elseif ($this->isInstalled()) {
                $this->setResults('skin_already_exists', $name);
            } elseif ($this->DirExists()) {
                $this->setResults('skin_already_exists', $this->getDirPath());
            } elseif (!$this->CreateDir()) {
                $this->setResults('path_not_writable', $this->getDirPath());
            } elseif (!$this->lock()) {
                $this->setResults('skin_locking_failed', $this->getDirPath());
            } elseif (!$this->createRow()) {
                $this->setResults('skin_creation_failed', $name);
            } else {
                $failed = false;

                foreach ($this->getAssets() as $assetModel) {
                    if ($from && !$assetModel->duplicateRows($from) || !$from && !$assetModel->createRows()) {
                        $failed = true;

                        $this->setResults($assetModel->getString().'_creation_failed', $name);
                    }
                }

                if (!$this->unlock()) {
                    $this->setResults('skin_unlocking_failed', $name);
                } elseif (!$failed) {
                    $this->setResults('skin_created', $name, 'success');
                }
            }

            callback_event('skin.create', '', 0, array('name' => $name));

            return $this;
        }

        /**
         * Updates a skin.
         *
         * @param  string $base
         * @return object $this.
         */

        public function update() {
            $name = $this->getName();
            $base = $this->getBase();

            callback_event('skin.update', '', 1, array('name' => $base));

            $updated = false;

            if (!$this->isInstalled($base)) {
                $this->setResults('skin_unknown', $base);
            } elseif ($base !== $name && $this->isInstalled()) {
                $this->setResults('skin_already_exists', $name);
            } elseif ($base !== $name && $this->dirExists()) {
                $this->setResults('skin_already_exists', $this->getDirPath());
            } elseif ($this->dirExists($base) && !$this->lock($base)) {
                $this->setResults('skin_dir_locking_failed', $base);
            } elseif (!$this->updateRow()) {
                $this->setResults('skin_update_failed', $base);
                $toUnlock = $base;
            } else {
                $updated = true;

                if ($this->dirExists($base) && !$this->renameDir($base)) {
                    $this->setResults('path_renaming_failed', $base, 'warning');
                } else {
                    $toUnlock = $name;
                }
            }

            if (isset($toUnlock) && !$this->unlock($toUnlock)) {
                $this->setResults('skin_unlocking_failed', $toUnlock);
            }

            if ($updated) {
                $this->getSections() ? $this->updateSections() : '';

                if ($this->getEditing() === $name) {
                    $this->setEditing();
                }

                foreach ($this->getAssets() as $assetModel) {
                    if (!$assetModel->updateSkin()) {
                        $this->setResults($assetModel->getString().'_update_failed', $base);
                    }
                }

                $this->setResults('skin_updated', $name, 'success');

                update_lastmod('skin.edit', $suceeded);
                callback_event('skin.edit', 'success', 0, $suceeded);
            }

            callback_event('skin.update', '', 0, array('name' => $base));

            return $this;
        }

        /**
         * Duplicates skins.
         *
         * @return object $this.
         */

        public function duplicate()
        {
            $names = $this->getNames();

            callback_event('skin.duplicate', '', 1, array('names' => $names));

            $passed = array();

            foreach ($names as $name) {
                $this->setInfos($name);
                $copy = $name.'_copy';

                if (!$this->isInstalled()) {
                    $this->setResults('skin_unknown', $name);
                } elseif ($this->isInstalled($copy)) {
                    $this->setResults('skin_already_exists', $copy);
                } elseif (!$this->isDirWritable() && !$this->createDir()) {
                    $this->setResults('path_not_writable', $this->getDirPath());
                } elseif (!$this->lock()) {
                    $this->setResults('skin_dir_locking_failed', $name);
                } else {
                    $passed[] = $name;
                }

                $this->setNames($passed);
                $rows = $this->getRows();

                if (!$rows) {
                    $this->setResults('skin_unknown', $passed);
                } else {
                    foreach ($rows as $name => $infos) {
                        extract($infos);

                        $copy = $name.'_copy';
                        $copyTitle = $title.'_copy';

                        $this->setInfos($copy, $copyTitle, $version, $description, $author, $author_uri);

                        if (!$this->createRow()) {
                            $this->setResults('skin_duplication_failed', $name);
                        } else {
                            static::setInstalled(array($copy => $copyTitle));

                            foreach ($this->getAssets() as $assetModel) {
                                $this->setInfos($name);
                                $assetString = $assetModel::getString();
                                $assetRows = $assetModel->getRows();

                                if (!$assetRows) {
                                    $this->setResults($assetString.'_not_found', array($skin => $this->getDirPath()));
                                } else {
                                    if ($this->setInfos($copy) && !$assetModel->createRows($assetRows)) {
                                        $this->setResults($assetString.'_duplication_failed', array($skin => $notImported));
                                    }
                                }
                            }

                            $this->setInfos($name);
                        }
                    }

                    if ($this->islocked() && !$this->unlock()) {
                        $this->setResults('skin_unlocking_failed', $this->getDirPath());
                    } else {
                        $this->setResults('skin_duplicated', $name, 'success');
                    }
                }
            }

            callback_event('skin.duplicate', '', 0, array('names' => $names));

            return $this;
        }

        /**
         * Imports skins.
         *
         * @param  bool   $clean    Whether to removes extra skin template rows or not;
         * @param  bool   $override Whether to insert or update the skins.
         * @return object $this.
         */

        public function import($clean = true, $override = false)
        {
            $names = $this->getNames();

            callback_event('skin.import', '', 1, array('names' => $names));

            foreach ($names as $name) {
                $this->setInfos($name);

                if (!$override && $this->isInstalled()) {
                    $this->setResults('skin_unknown', $name);
                } elseif ($override && !$this->isInstalled()) {
                    $this->setResults('skin_already_exists', $name);
                } elseif (!$this->isDirWritable()) {
                    $this->setResults('path_not_writable', $this->getDirPath());
                } elseif (!$this->isFileReadable()) {
                    $this->setResults('path_not_readable', $this->getFilePath());
                } elseif (!$this->lock()) {
                    $this->setResults('skin_dir_locking_failed', $name);
                } else {
                    $skinInfos = $this->getFileContents();

                    if (!$skinInfos) {
                        $this->setResults('invalid_json', $this->getFilePath);
                    } else {
                        extract($skinInfos);

                        $this->setInfos($name, $title, $version, $description, $author, $author_uri);

                        if (!$override && !$this->createRow()) {
                            $this->setResults('skin_import_failed', $name);
                        } elseif ($override && !$this->setBase($name)->updateRow()) {
                            $this->setResults('skin_import_failed', $name);
                        } else {
                            static::setInstalled(array($name => $title));

                            foreach ($this->getAssets() as $asset) {
                                $asset->import($clean);
                            }
                        }
                    }
                }

                if ($this->islocked() && !$this->unlock()) {
                    $this->setResults('skin_unlocking_failed', $this->getDirPath());
                }
            }

            callback_event('skin.import', '', 0, array('names' => $names));

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
            $names = $this->getNames();

            callback_event('skin.export', '', 1, array('names' => $names));

            foreach ($names as $name) {
                $this->setInfos($name);

                if (!static::isValidDirName($name)) {
                    $this->setResults('skin_unsafe_name', $name);
                } elseif (!$this->isDirWritable() && !$this->createDir()) {
                    $this->setResults('path_not_writable', $this->getDirPath());
                } elseif (!$this->lock()) {
                    $this->setResults('skin_locking_failed', $name);
                } else {
                    $passed[] = $name;
                }
            }

            $rows = $this->setNames($passed)->getRows();

            if (!$rows) {
                $this->setResults('skin_unknown', $names);
            } else {
                foreach ($passed as $name) {
                    $this->setName($name);

                    extract($rows[$name]);

                    if (!$rows[$name]) {
                        $this->setResults('skin_unknown', $name);
                    } elseif (!$this->setInfos($name, $title, $version, $description, $author, $author_uri)->createFile()) {
                        $this->setResults('skin_export_failed', $name);
                    } else {
                        foreach ($this->getAssets() as $asset) {
                            $asset->export($clean);
                        }
                    }

                    if ($this->islocked() && !$this->unlock()) {
                        $this->setResults('skin_unlocking_failed', $name);
                    }
                }
            }

            callback_event('skin.export', '', 0, array('names' => $names));

            return $this;
        }

        /**
         * Deletes skins.
         *
         * @return object $this.
         */

        public function delete()
        {
            $names = $this->getNames();

            callback_event('skin.delete', '', 1, array('names' => $names));

            $passed = $failed = array();

            foreach ($names as $name) {
                $this->setInfos($name);

                if (!$this->isInstalled()) {
                    $failed[] = $name;
                    $this->setResults('skin_unknown', $name);
                } elseif ($this->getSections()) {
                    $failed[] = $name;
                    $this->setResults('skin_in_use', $name);
                } elseif ($this->dirExists() && !$this->lock()){
                    $this->setResults('skin_locking_failed', $name);
                } else {
                    $assetFailure = false;

                    foreach ($this->getAssets() as $assetModel) {
                        if (!$assetModel->deleteRows()) {
                            $failed[] = $name;
                            $this->setResults($assetModel->getString().'_deletion_failed', $name);
                        }
                    }

                    $assetFailure ? $failed[] = $name : $passed[] = $name;
                }
            }

            if ($passed) {
                if ($this->setNames($passed) && $this->deleteRows()) {
                    static::unsetInstalled($passed);

                    if (in_array(static::getEditing(), $passed)) {
                        static::resetEditing();
                    }

                    $this->setResults('skin_deleted', $passed, 'success');

                    update_lastmod('skin.delete', $passed);
                } else {
                    $this->setResults('skin_deletion_failed', $passed);
                }
            }

            foreach ($names as $name) {
                if ($this->setInfos($name)->islocked() && !$this->unlock()) {
                    $this->setResults('skin_unlocking_failed', $name);
                } else {
                    $this->removeDir();
                }
            }

            callback_event('skin.delete', '', 0, array('names' => $names));

            return $this;
        }

        /**
         * The main panel listing all skins.
         *
         * @param mixed $message The activity message
         */

        public function render()
        {
            return $this->renderList($this->getMessage());
        }

        /**
         * Skins list.
         *
         * @param  mixed $message The activity message
         * @return html
         */

        function renderList($message = '')
        {
            global $event;

            pagetop(gTxt('tab_skins'), $message);

            extract(gpsa(array(
                'page',
                'sort',
                'dir',
                'crit',
                'search_method',
            )));

            if ($sort === '') {
                $sort = get_pref('skin_sort_column', 'name');
            } else {
                $sortOpts = array(
                    'title',
                    'version',
                    'author',
                    'section_count',
                    'page_count',
                    'form_count',
                    'css_count',
                    'name',
                );

                in_array($sort, $sortOpts) or $sort = 'name';

                set_pref('skin_sort_column', $sort, 'skin', 2, '', 0, PREF_PRIVATE);
            }

            if ($dir === '') {
                $dir = get_pref('skin_sort_dir', 'desc');
            } else {
                $dir = ($dir == 'asc') ? 'asc' : 'desc';

                set_pref('skin_sort_dir', $dir, 'skin', 2, '', 0, PREF_PRIVATE);
            }

            $sortSQL = $sort.' '.$dir;
            $switchDir = ($dir == 'desc') ? 'asc' : 'desc';

            $search = new \Textpattern\Search\Filter(
                $event,
                array(
                    'name' => array(
                        'column' => 'txp_skin.name',
                        'label'  => gTxt('name'),
                    ),
                    'title' => array(
                        'column' => 'txp_skin.title',
                        'label'  => gTxt('title'),
                    ),
                    'description' => array(
                        'column' => 'txp_skin.description',
                        'label'  => gTxt('description'),
                    ),
                    'author' => array(
                        'column' => 'txp_skin.author',
                        'label'  => gTxt('author'),
                    ),
                )
            );

            list($criteria, $crit, $search_method) = $search->getFilter();

            $searchRenderOpts = array('placeholder' => 'search_skins');
            $total = Skin::getSearchCount($criteria);

            echo n.'<div class="txp-layout">'
                .n.tag(
                    hed(gTxt('tab_skins'), 1, array('class' => 'txp-heading')),
                    'div',
                    array('class' => 'txp-layout-4col-alt')
                );

            $searchBlock = n.tag(
                $search->renderForm('skin', $searchRenderOpts),
                'div',
                array(
                    'class' => 'txp-layout-4col-3span',
                    'id'    => $event.'_control',
                )
            );

            $createBlock = has_privs('skin.edit') ? static::renderCreateBlock() : '';

            $contentBlockStart = n.tag_start(
                'div',
                array(
                    'class' => 'txp-layout-1col',
                    'id'    => $event.'_container',
                )
            );

            echo $searchBlock
                .$contentBlockStart
                .$createBlock;

            if ($total < 1) {
                if ($criteria != 1) {
                    echo graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_results_found'),
                        array('class' => 'alert-block information')
                    );
                } else {
                    echo graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_skin_recorded'),
                        array('class' => 'alert-block error')
                    );
                }

                echo n.tag_end('div') // End of .txp-layout-1col.
                    .n.'</div>';      // End of .txp-layout.

                return;
            }

            $paginator = new \Textpattern\Admin\Paginator();
            $limit = $paginator->getLimit();

            list($page, $offset, $numPages) = pager($total, $limit, $page);

            $rs = Skin::getAllData($criteria, $sortSQL, $offset, $limit);

            if ($rs) {
                echo n.tag_start('form', array(
                        'class'  => 'multi_edit_form',
                        'id'     => 'skin_form',
                        'name'   => 'longform',
                        'method' => 'post',
                        'action' => 'index.php',
                    ))
                    .n.tag_start('div', array('class' => 'txp-listtables'))
                    .n.tag_start('table', array('class' => 'txp-list'))
                    .n.tag_start('thead');

                $ths = hCell(
                    fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                    '',
                    ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                );

                $thIds = array(
                    'name'          => 'name',
                    'title'         => 'title',
                    'version'       => 'version',
                    'author'        => 'author',
                    'section_count' => 'tab_sections',
                    'page_count'    => 'tab_pages',
                    'form_count'    => 'tab_forms',
                    'css_count'     => 'tab_style',
                );

                foreach ($thIds as $thId => $thVal) {
                    $thClass = 'txp-list-col-'.$thId
                              .($thId == $sort ? ' '.$dir : '')
                              .($thVal !== $thId ? ' skin_detail' : '');

                    $ths .= column_head($thVal, $thId, 'skin', true, $switchDir, $crit, $search_method, $thClass);
                }

                echo tr($ths)
                    .n.tag_end('thead')
                    .n.tag_start('tbody');

                while ($a = nextRow($rs)) {
                    extract($a, EXTR_PREFIX_ALL, 'skin');

                    $editUrl = array(
                        'event'         => 'skin',
                        'step'          => 'edit',
                        'name'          => $skin_name,
                        'sort'          => $sort,
                        'dir'           => $dir,
                        'page'          => $page,
                        'search_method' => $search_method,
                        'crit'          => $crit,
                    );

                    $tdAuthor = txpspecialchars($skin_author);

                    empty($skin_author_uri) or $tdAuthor = href($tdAuthor, $skin_author_uri);

                    $tds = td(fInput('checkbox', 'selected[]', $skin_name), '', 'txp-list-col-multi-edit')
                        .hCell(
                            href(txpspecialchars($skin_name), $editUrl, array('title' => gTxt('edit'))),
                            '',
                            array(
                                'scope' => 'row',
                                'class' => 'txp-list-col-name',
                            )
                        )
                        .td(txpspecialchars($skin_title), '', 'txp-list-col-title')
                        .td(txpspecialchars($skin_version), '', 'txp-list-col-version')
                        .td($tdAuthor, '', 'txp-list-col-author');

                    $countNames = array('section', 'page', 'form', 'css');

                    foreach ($countNames as $name) {
                        if (${'skin_'.$name.'_count'} > 0) {
                            if ($name === 'section') {
                                $linkParams = array(
                                    'event'         => 'section',
                                    'search_method' => 'skin',
                                    'crit'          => '"'.$skin_name.'"',
                                );
                            } else {
                                $linkParams = array(
                                    'event' => $name,
                                    'skin'  => $skin_name,
                                );
                            }

                            $tdVal = href(
                                ${'skin_'.$name.'_count'},
                                $linkParams,
                                array(
                                    'title' => gTxt(
                                        'skin_count_'.$name,
                                        array('{num}' => ${'skin_'.$name.'_count'})
                                    )
                                )
                            );
                        } else {
                            $tdVal = 0;
                        }

                        $tds .= td($tdVal, '', 'txp-list-col-'.$name.'_count');
                    }

                    echo tr($tds, array('id' => 'txp_skin_'.$skin_name));
                }

                echo n.tag_end('tbody')
                    .n.tag_end('table')
                    .n.tag_end('div') // End of .txp-listtables.
                    .n.static::renderMultiEditForm($page, $sort, $dir, $crit, $search_method)
                    .n.tInput()
                    .n.tag_end('form')
                    .n.tag_start(
                        'div',
                        array(
                            'class' => 'txp-navigation',
                            'id'    => $event.'_navigation',
                        )
                    )
                    .$paginator->render()
                    .nav_form('skin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit)
                    .n.tag_end('div');
            }

            echo n.tag_end('div') // End of .txp-layout-1col.
                .n.'</div>'; // End of .txp-layout.
        }

        /**
         * Renders the skin import form.
         *
         * @return html The form or a message if no new skin directory is found.
         */

        public static function renderImportForm()
        {
            $new = Skin::getNewDirectories();

            if ($new) {
                return n
                    .tag_start('form', array(
                        'id'     => 'skin_import_form',
                        'name'   => 'skin_import_form',
                        'method' => 'post',
                        'action' => 'index.php',
                    ))
                    .tag(gTxt('import_skin'), 'label', array('for' => 'skin_import'))
                    .popHelp('skin_import')
                    .selectInput('skins', $new, '', true, false, 'skins')
                    .eInput('skin')
                    .sInput('import')
                    .fInput('submit', '', gTxt('upload'))
                    .n
                    .tag_end('form');
            }
        }

        /**
         * Renders button to create a new skin.
         *
         * @return html Link.
         */

        public static function renderCreateButton()
        {
            return sLink('skin', 'edit', gTxt('create_skin'), 'txp-button');
        }

        /**
         * Renders the .txp-control-panel div.
         *
         * @return html div containing the 'Create' button and the import form..
         */

        public static function renderCreateBlock()
        {
            return tag(
                static::renderCreateButton()
                .static::renderImportForm(),
                'div',
                array('class' => 'txp-control-panel')
            );
        }

        /**
         * Renders the edit form.
         *
         * @return html Form.
         */

        public function renderEditForm($message = '')
        {
            global $step;

            require_privs('skin.edit');

            $message ? pagetop(gTxt('tab_skins'), $message) : '';

            extract(gpsa(array(
                'page',
                'sort',
                'dir',
                'crit',
                'search_method',
                'name',
            )));

            $fields = array('name', 'title', 'version', 'description', 'author', 'author_uri');

            if ($name) {
                $rs = $this->setInfos($name)->getRow();

                if (!$rs) {
                    return $this->main();
                }

                $caption = gTxt('edit_skin');
                $extraAction = href(
                    '<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'),
                    '#',
                    array(
                        'class'     => 'txp-clone',
                        'data-form' => 'skin_form',
                    )
                );
            } else {
                $rs = array_fill_keys($fields, '');
                $caption = gTxt('create_skin');
                $extraAction = '';
            }

            extract($rs, EXTR_PREFIX_ALL, 'skin');
            pagetop(gTxt('tab_skins'));

            $content = hed($caption, 2);

            foreach ($fields as $field) {
                $current = ${'skin_'.$field};

                if ($field === 'description') {
                    $input = text_area($field, 0, 0, $current, 'skin_'.$field);
                } elseif ($field === 'name') {
                    $input = '<input type="text" value="'.$current.'" id="skin_'.$field.'" name="'.$field.'" size="'.INPUT_REGULAR.'" maxlength="63" required />';
                } else {
                    $type = ($field === 'author_uri') ? 'url' : 'text';
                    $input = fInput($type, $field, $current, '', '', '', INPUT_REGULAR, '', 'skin_'.$field);
                }

                $content .= inputLabel('skin_'.$field, $input, 'skin_'.$field);
            }

            $content .= pluggable_ui('skin_ui', 'extend_detail_form', '', $rs)
                .graf(
                    $extraAction.
                    sLink('skin', '', gTxt('cancel'), 'txp-button')
                    .fInput('submit', '', gTxt('save'), 'publish'),
                    array('class' => 'txp-edit-actions')
                )
                .eInput('skin')
                .sInput('save')
                .hInput('old_name', $skin_name)
                .hInput('old_title', $skin_title)
                .hInput('search_method', $search_method)
                .hInput('crit', $crit)
                .hInput('page', $page)
                .hInput('sort', $sort)
                .hInput('dir', $dir);

            echo form($content, '', '', 'post', 'txp-edit', '', 'skin_form');
        }

        /**
         * Renders a multi-edit form widget.
         *
         * @param  int    $page         The page number
         * @param  string $sort         The current sorting value
         * @param  string $dir          The current sorting direction
         * @param  string $crit         The current search criteria
         * @param  string $search_method The current search method
         * @return string HTML
         */

        public function renderMultiEditForm($page, $sort, $dir, $crit, $search_method)
        {
            $clean = checkbox2('clean', get_pref('remove_extra_templates', true), 0, 'clean')
                    .n.tag(gtxt('remove_extra_templates'), 'label', array('for' => 'clean'))
                    .popHelp('remove_extra_templates');

            $methods = array(
                'import'    => array('label' => gTxt('import'), 'html' => $clean),
                'duplicate' => gTxt('duplicate'),
                'export'    => array('label' => gTxt('export'), 'html' => $clean),
                'delete'    => gTxt('delete'),
            );

            return multi_edit($methods, 'skin', 'multi_edit', $page, $sort, $dir, $crit, $search_method);
        }
    }
}

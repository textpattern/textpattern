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

    class Skin extends Base
    {
        /**
         * Skin(s) assets related objects.
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
         * {@inheritdoc}
         */

        protected static $string = 'skin';

        /**
         * The skin related main JSON file.
         *
         * @var string Filename.
         * @see        getFile().
         */

        protected static $file = 'manifest.json';

        /**
         * Whether the $name property value related skin is locked or not.
         *
         * @var bool
         * @see      lock(), unlock().
         */

        protected $locked;

        /**
         * Importable skins.
         *
         * @var array Associative array of skin names and their titles
         * @see       setUploaded(), getUploaded().
         */

        protected static $uploaded;

        /**
         * Installed skins.
         *
         * @var array Associative array of skin names and their titles
         * @see       setUploaded(), getUploaded().
         */

        protected static $installed;

        /**
         * Installed skins.
         *
         * @var array Associative array of skin names and their titles
         * @see       setUploaded(), getUploaded().
         */

        protected $infos;

        /**
         * Class related asset names to work with.
         *
         * @var array Names.
         * @see       setNames(), getNames().
         */

        protected static $dirPath;

        /**
         * {@inheritdoc}
         */

        public function __construct()
        {
        }

        protected function mergeResults($asset) {
            $thisResults = $this->getResults();
            $this->results = array_merge($thisResults, $asset->getResults());

            return array_diff($this->getResults(), $thisResults);
        }

        /**
         * $dirPath property setter.
         *
         * Set the skin related directory path from the parameter value
         * or from the path_to_site and the skin_dir pref values.
         *
         * @param  string $path             Path.
         * @return string self::$dirPath Path.
         */

        public static function setDirPath($path = null)
        {
            $path === null ? $path = get_pref('path_to_site').DS.get_pref('skin_dir') : '';

            self::$dirPath = rtrim($path, DS);

            return self::getDirPath();
        }

        /**
         * $dirPath property getter
         *
         * @return string self::$dirPath Path.
         * @see                             setDirPath().
         */

        protected static function getDirPath()
        {
            self::$dirPath === null ? self::setDirPath() : '';

            return self::$dirPath;
        }

        /**
         * $assets property setter.
         *
         * @param array $pages  Page names to work with;
         * @param array $forms  Form names to work with;
         * @param array $styles CSS names to work with.
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
         *
         * @return $this->$assets
         * @see                   setAssets().
         */

        protected function getAssets()
        {
            $this->assets === null ? $this->setAssets() : '';

            return $this->assets;
        }

        /**
         * {@inheritdoc}
         */

        protected static function sanitizeName($name) {
            return sanitizeForTheme($name);
        }

        /**
         * $infos property setter.
         *
         * @param  string $name        Skin name;
         * @param  string $title       Skin title;
         * @param  string $version     Skin version;
         * @param  string $description Skin description;
         * @param  string $author      Skin author;
         * @param  string $author_uri  Skin author URL;
         * @return object $this->name  The current object (chainable).
         * @see                        \sanitizeForTheme().
         */

        public function setInfos(
            $name,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null
        ) {
            $name = sanitizeForTheme($name);
            // TODO check $author_uri against a URL related RegEx?

            $this->name = $name;
            $this->infos = compact('name', 'title', 'version', 'description', 'author', 'author_uri');

            return $this;
        }

        /**
         * Whether a skin is installed or not.
         *
         * @param string $name Skin name (uses the $name property value if null)
         * @see          getName(), getInstalled().
         */

        public function isInstalled($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            if ($this->installed === null) {
                $isInstalled = (bool) safe_field(
                    'name',
                    self::getTable(),
                    "name = '".doSlash($name)."'"
                );
            } else {
                $isInstalled = in_array($name, array_values(self::getInstalled()));
            }

            return $isInstalled;
        }

        /**
         * Get a skin directory path.
         *
         * @param string $name Skin name (uses the $name property value if null)
         * @see          getName(), getDirPath().
         */

        public function getSubdirPath($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            return self::getDirPath().DS.$name;
        }

        /**
         * Get a skin directory path.
         *
         * @param string $name Skin name (uses the $name property value if null)
         * @see          getSubdirPath().
         */

        protected static function getFile()
        {
            return self::$file;
        }

        /**
         * Get a skin directory path.
         *
         * @param string $name Skin name (uses the $name property value if null)
         * @see          getSubdirPath().
         */

        protected function getFilePath()
        {
            return $this->getSubdirPath().DS.self::getFile();
        }

        /**
         * Get and complete the skin related JSON file contents.
         *
         * @param array Associative array of JSON fields and their related values / fallback values.
         * @see         getName(), getFilePath().
         */

        protected function getFileContents()
        {
            $contents = json_decode(file_get_contents($this->getFilePath()), true);

            if ($contents !== null) {
                empty($contents['title']) ? $contents['title'] = ucfirst($this->getName()) : '';
                empty($contents['version']) ? $contents['version'] = gTxt('unknown') : '';
                empty($contents['description']) ? $contents['description'] = '' : '';
                empty($contents['author']) ? $contents['author'] = gTxt('unknown') : '';
                empty($contents['author_uri']) ? $contents['author_uri'] = '' : '';
            }

            return $contents;
        }

        /**
         * $sections property getter.
         *
         * @param array Section names.
         */

        protected function getSections($whereSkin = null)
        {
            $whereSkin === null ? $whereSkin = $this->getName() : '';

            return array_values(
                safe_column(
                    'title',
                    'txp_section',
                    "skin ='".doSlash($whereSkin)."'"
                )
            );
        }

        /**
         * Update the $base property value related skin sections
         * to the $name property value.
         *
         * @return bool false on error.
         */

        protected function updateSections()
        {
            return safe_update(
                'txp_section',
                "skin = '".doSlash($this->getName())."'",
                "skin = '".doSlash($this->getBase())."'"
            );
        }

        /**
         * {@inheritdoc}
         */

        public static function getEditing()
        {
            return get_pref('skin_editing', 'default', true);
        }

        /**
         * {@inheritdoc}
         */

        public function setEditing()
        {
            global $prefs;

            $name = $this->getName();
            $prefs['skin_editing'] = $name;

            set_pref('skin_editing', $name, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);

            return self::getEditing();
        }

        /**
         * Set the skin_editing pref value to the skin name used by the default section.
         *
         * @return bool false on error.
         */

        public static function getDefault()
        {
            return safe_field('skin', 'txp_section', 'name = "default"');
        }

        /**
         * $locked property getter.
         *
         * @return bool $this->locked.
         */

        public function isLocked()
        {
            return $this->locked;
        }

        /**
         * Pseudo lock a skin directory by adding a 'lock' folder into it.
         *
         * $locked property auto setter.
         *
         * @param  string $name Skin name (uses the $name property value if null)
         * @return bool   false on error.
         * @see           getName(), getSubdirPath().
         */

        public function lock($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            $path = $this->getSubdirPath($name);
            $pathReady = is_dir($path);
            $timeStart = microtime(true);
            $this->locked = false;

            if ($pathReady || !$pathReady && @mkdir($path)) {
                $time = 0;

                while (!$this->islocked() && $time < 2) {
                    $this->locked = @mkdir($path.'/lock');
                    sleep(0.25);
                    $time = microtime(true) - $timeStart;
                }
            }

            return $this->islocked();
        }

        /**
         * Pseudo unlock a skin directory by removing a 'lock' folder into it.
         *
         * $locked property auto setter.
         *
         * @param  string $name Skin name (uses the $name property value if null).
         * @return bool   false on error.
         * @see           getName(), getSubdirPath().
         */

        public function unlock($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            $path = $this->getSubdirPath($name);
            $pathReady = is_dir($path);

            if (!$pathReady || @rmdir($path.'/lock')) {
                $this->locked = false;
            }

            $pathReady && !$this->islocked() ? @rmdir($path) : '';

            return !$this->islocked();
        }

        /**
         * {@inheritdoc}
         */

        protected function createRow()
        {
            return safe_insert(self::getTable(), $this->getInfos(true));
        }

        /**
         * {@inheritdoc}
         */

        protected function updateRow()
        {
            return safe_update(
                self::getTable(),
                $this->getInfos(true),
                "name = '".doSlash($this->getBase())."'"
            );
        }

        /**
         * {@inheritdoc}
         */

        protected function getRow()
        {
            return safe_row(
                'name, title, version, description, author, author_uri',
                self::getTable(),
                "name = '".doSlash($this->getName())."'"
            );
        }

        /**
         * {@inheritdoc}
         */

        protected function getRows()
        {
            $rows = safe_rows_start(
                'name, title, version, description, author, author_uri',
                self::getTable(),
                "name IN ('".implode("', '", array_map('doSlash', $this->getNames()))."')"
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
         * {@inheritdoc}
         */

        protected function deleteRows()
        {
            return safe_delete(
                self::getTable(),
                "name IN ('".implode("', '", array_map('doSlash', $this->getNames()))."')"
            );
        }

        /**
         * {@inheritdoc}
         */

        protected function createFile() {
            $contents = array_merge(
                $this->getInfos(),
                array('txp-type' => 'textpattern-theme')
            );

            return (bool) file_put_contents(
                $this->getFilePath(),
                JSONPrettyPrint(json_encode($contents, TEXTPATTERN_JSON))
            );
        }

        /**
         * Gets Skins related files.
         *
         * @return object
         */

        protected static function getFiles()
        {
            return new DirIterator\RecIteratorIterator(
                new DirIterator\RecFilterIterator(
                    new DirIterator\RecDirIterator(self::getDirPath()),
                    array(self::getFile())
                ),
                1
            );
        }

        /**
         * $uploaded property setter.
         *
         * @return object $this The current object (chainable).
         * @see                 getFiles(), getJSONContents().
         */

        protected static function setUploaded()
        {
            self::$uploaded = array();

            foreach (self::getFiles() as $file) {
                $name = basename($file->getPath());

                if (self::isValidDirName($name)) {
                    $infos = $file->getJSONContents();
                    $infos ? self::$uploaded[$name] = $infos['title'] : '';
                }
            }

            return self::getUploaded();
        }

        /**
         * $uploaded property getter.
         *
         * @return array self::directories Associative array of importable skin names and titles.
         * @see                              setUploaded().
         */

        protected static function getUploaded()
        {
            return self::$uploaded === null ? self::setUploaded() : self::$uploaded;
        }

        /**
         * $installed property setter.
         *
         * @param object $this The current object (chainable).
         */

        protected static function setInstalled($name = null)
        {
            if ($name) {
                // TODO
            } else {
                $rows = safe_rows('name, title', self::getTable(), '1 = 1');

                self::$installed = array();

                foreach ($rows as $row) {
                    self::$installed[$row['name']] = $row['title'];
                }
            }

            return self::getInstalled();
        }

        /**
         * $installed property getter.
         *
         * @return array Associative array of installed skin names and titles.
         * @see          setInstalled().
         */

        public static function getInstalled()
        {
            return self::$installed === null ? self::setInstalled() : self::$installed;
        }

        /**
         * $installed property unsetter.
         *
         * @return object $this.
         */

        protected static function unsetInstalled($names)
        {
            self::$installed = array_diff_key(
                self::getInstalled(),
                array_fill_keys($names, '')
            );
        }

        /**
         * {@inheritdoc}
         */

        protected static function getSearchCount($criteria)
        {
            return safe_count('txp_skin', $criteria);
        }

        /**
         * {@inheritdoc}
         */

        protected static function getTableData($criteria, $sortSQL, $offset, $limit)
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
         * {@inheritdoc}
         */

        public function create() {
            $infos = $this->getInfos();
            $name = $infos['name'];
            $base = $this->getBase();

            callback_event('skin.create', '', 1, array(
                'infos' => $infos,
                'base'  => $base,
            ));

            $status = 'error';

            if (empty($name)) {
                $this->mergeResult('skin_name_invalid', $name);
            } elseif ($base && !$this->isInstalled($base)) {
                $this->mergeResult('skin_unknown', $base);
            } elseif ($this->isInstalled()) {
                $this->mergeResult('skin_already_exists', $name);
            } elseif (is_dir($subdirPath = $this->getSubdirPath())) {
                $this->mergeResult('skin_already_exists', $subdirPath);
            } elseif (!@mkdir($subdirPath)) {
                $this->mergeResult('path_not_writable', $subdirPath);
            } elseif (!$this->lock()) {
                $this->mergeResult('skin_locking_failed', $subdirPath);
            } elseif (!$this->createRow()) {
                $this->mergeResult('skin_creation_failed', $name);
            } else {
                foreach ($this->getAssets() as $assetModel) {
                    if ($base) {
                        $this->setName($base);
                        $rows = $assetModel->getRows();
                        $this->setName($name);
                    } else {
                        $rows = null;
                    }

                    if (!$assetModel->createRows($rows)) {
                        $failure = true;

                        $this->mergeResult($assetModel->getString().'_creation_failed', $name);
                    }
                }

                if (!isset($failure)) {
                    $status = 'success';

                    $this->mergeResult('skin_created', $name, 'success');
                }
            }

            if ($this->isLocked() && !$this->unlock()) {
                $status === 'error' ?: $status =  'warning';

                $this->mergeResult('skin_unlocking_failed', $name);
            }

            callback_event('skin.create', '', 0, array(
                'infos'  => $infos,
                'base'   => $base,
                'status' => $status
            ));

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function update() {
            $infos = $this->getInfos();
            $name = $infos['name'];
            $base = $this->getBase();
            $done = false;

            callback_event('skin.update', '', 1, array(
                'infos' => $infos,
                'base'  => $base,
            ));

            if (empty($name)) {
                $this->mergeResult('skin_name_invalid', $name);
            } elseif (!$this->isInstalled($base)) {
                $this->mergeResult('skin_unknown', $base);
            } elseif ($base !== $name && $this->isInstalled()) {
                $this->mergeResult('skin_already_exists', $name);
            } elseif (is_dir($subdirPath = $this->getSubdirPath()) && $base !== $name) {
                $this->mergeResult('skin_already_exists', $subdirPath);
            } elseif (is_dir($this->getSubdirPath($base)) && !$this->lock($base)) {
                $this->mergeResult('skin_dir_locking_failed', $this->getSubdirPath($base));
            } elseif (!$this->updateRow()) {
                $this->mergeResult('skin_update_failed', $base);
                $toUnlock = $base;
            } else {
                $updated = true;

                if (is_dir($this->getSubdirPath($base)) && !@rename($this->getSubdirPath($base), $subdirPath)) {
                    $this->mergeResult('path_renaming_failed', $base, 'warning');
                } else {
                    $toUnlock = $name;
                }
            }

            if (isset($updated)) {
                $sections = $this->getSections($base);

                if ($sections && !$this->updateSections()) {
                    $this->mergeResult('skin_related_sections_update_failed', array($base => $sections));
                }

                self::getEditing() === $base ? $this->setEditing() : '';

                foreach ($this->getAssets() as $assetModel) {
                    if (!$assetModel->updateSkin()) {
                        $assetFailure = true;
                        $this->mergeResult($assetModel->getString().'_update_failed', $base);
                    }
                }

                if (!isset($assetFailure)) {
                    $done = true;

                    $this->mergeResult('skin_updated', $name, 'success');
                }
            }

            if (isset($toUnlock) && !$this->unlock($toUnlock)) {
                $this->mergeResult('skin_unlocking_failed', $this->getSubdirPath($toUnlock));
            }

            callback_event('skin.update', '', 0, array(
                'infos' => $infos,
                'base'  => $base,
                'done'  => $done,
            ));

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate()
        {
            $names = $this->getNames();
            $done = $ready = array();

            callback_event('skin.duplicate', '', 1, array('names' => $names));

            foreach ($names as $name) {
                $subdirPath = $this->setName($name)->getSubdirPath();
                $copy = $name.'_copy';

                if (!$this->isInstalled()) {
                    $this->mergeResult('skin_unknown', $name);
                } elseif ($this->isInstalled($copy)) {
                    $this->mergeResult('skin_already_exists', $copy);
                } elseif (!is_writable($subdirPath) && !@mkdir($subdirPath)) {
                    $this->mergeResult('path_not_writable', $subdirPath);
                } elseif (!$this->lock()) {
                    $this->mergeResult('skin_dir_locking_failed', $subdirPath);
                } else {
                    $ready[] = $name;
                }
            }

            $rows = $this->setNames($ready)->getRows();

            if (!$rows) {
                $this->mergeResult('skin_unknown', $ready);
            } else {
                foreach ($rows as $name => $infos) {
                    extract($infos);

                    $copy = $name.'_copy';
                    $copyTitle = $title.'_copy';

                    if (!$this->setInfos($copy, $copyTitle, $version, $description, $author, $author_uri)->createRow()) {
                        $this->mergeResult('skin_duplication_failed', $name);
                    } else {
                        self::setInstalled(array($copy => $copyTitle));

                        foreach ($this->getAssets() as $assetModel) {
                            $this->setName($name);
                            $assetString = $assetModel::getString();
                            $assetRows = $assetModel->getRows();

                            if (!$assetRows) {
                                $assetFailure = true;

                                $this->mergeResult($assetString.'_not_found', array($skin => $subdirPath));
                            } elseif ($this->setName($copy) && !$assetModel->createRows($assetRows)) {
                                $assetFailure = true;

                                $this->mergeResult($assetString.'_duplication_failed', array($skin => $notImported));
                            }
                        }

                        $this->setName($name);

                        if (!isset($assetFailure)) {
                            $done[] = $name;

                            $this->mergeResult('skin_duplicated', $name, 'success');
                        }
                    }
                }
            }

            foreach($ready as $name) {
                if ($this->isLocked() && !$this->unlock()) {
                    $this->mergeResult('skin_unlocking_failed', $subdirPath);
                }
            }

            callback_event('skin.duplicate', '', 0, array(
                'names' => $names,
                'done'  => $done,
            ));

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = false, $override = false)
        {
            $clean == $this->getCleaningPref() ?: $this->switchCleaningPref();

            $names = $this->getNames();
            $done = array();

            callback_event('skin.import', '', 1, array('names' => $names));

            foreach ($names as $name) {
                $this->setName($name);

                $isInstalled = $this->isInstalled();

                if (!$isInstalled) {
                    $clean = false;
                    $override = false;
                }

                if (!$override && $isInstalled) {
                    $this->mergeResult('skin_already_exists', $name);
                } elseif (!is_writable($subdirPath = $this->getSubdirPath())) {
                    $this->mergeResult('path_not_writable', $subdirPath);
                } elseif (!is_readable($filePath = $this->getFilePath())) {
                    $this->mergeResult('path_not_readable', $filePath);
                } elseif (!$this->lock()) {
                    $this->mergeResult('skin_dir_locking_failed', $name);
                } else {
                    $skinInfos = $this->getFileContents();

                    if (!$skinInfos) {
                        $this->mergeResult('invalid_json', $filePath);
                    } else {
                        extract($skinInfos);

                        $this->setInfos($name, $title, $version, $description, $author, $author_uri);

                        if (!$override && !$this->createRow()) {
                            $this->mergeResult('skin_import_failed', $name);
                        } elseif ($override && !$this->setBase($name)->updateRow()) {
                            $this->mergeResult('skin_import_failed', $name);
                        } else {
                            self::setInstalled(array($name => $title));

                            foreach ($this->getAssets() as $asset) {
                                $asset->import($clean, $override);
                                $this->mergeResults($asset);
                                is_array($asset->getMessage()) ? $assetFailure = true : '';
                            }
                        }

                        if (!isset($assetFailure)) {
                            $done[] = $name;

                            $this->mergeResult('skin_imported', $name, 'success');
                        }
                    }

                    if (!$this->unlock()) {
                        $this->mergeResult('skin_unlocking_failed', $subdirPath);
                    }
                }
            }

            callback_event('skin.import', '', 0, array(
                'names' => $names,
                'done'  => $done,
            ));

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = false, $override = false)
        {
            $clean == $this->getCleaningPref() ?: $this->switchCleaningPref();

            $names = $this->getNames();
            $done = array();

            callback_event('skin.export', '', 1, array('names' => $names));

            foreach ($names as $name) {
                $this->setName($name);

                $subdirPath = $this->getSubdirPath();
                $subdirExists = is_dir($subdirPath);
                $isWritableSubdir = is_writable($subdirPath);

                if (!$isWritableSubdir) {
                    $clean = false;
                    $override = false;
                }

                if (!self::isValidDirName($name)) {
                    $this->mergeResult('skin_unsafe_name', $name);
                } elseif (!$override && $subdirExists) {
                    $this->mergeResult('skin_already_exists', $name);
                } elseif ($override && !$isWritableSubdir && !@mkdir($subdirPath)) {
                    $this->mergeResult('path_not_writable', $subdirPath);
                } elseif (!$this->lock()) {
                    $this->mergeResult('skin_locking_failed', $name);
                } else {
                    $ready[] = $name;
                }
            }

            if ($ready) {
                $rows = $this->setNames($ready)->getRows();

                if (!$rows) {
                    $this->mergeResult('skin_unknown', $names);
                } else {
                    foreach ($ready as $name) {
                        $this->setName($name);

                        extract($rows[$name]);

                        if (!$rows[$name]) {
                            $this->mergeResult('skin_unknown', $name);
                        } elseif (!$this->setInfos($name, $title, $version, $description, $author, $author_uri)->createFile()) {
                            $this->mergeResult('skin_export_failed', $name);
                        } else {
                            foreach ($this->getAssets() as $asset) {
                                $asset->export($clean, $override);
                                $assetFailure = $this->mergeResults($asset);
                            }

                            if (!$assetFailure) {
                                $done[] = $name;

                                $this->mergeResult('skin_exported', $name, 'success');
                            }
                        }

                        if ($this->isLocked() && !$this->unlock()) {
                            $this->mergeResult('skin_unlocking_failed', $name);
                        }
                    }
                }
            }

            callback_event('skin.export', '', 0, array(
                'names' => $names,
                'done'  => $done,
            ));

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function delete($clean = false)
        {
            $names = $this->getNames();
            $done = $ready = array();

            callback_event('skin.delete', '', 1, array('names' => $names));

            foreach ($names as $name) {
                $this->setName($name);

                if (!$this->isInstalled()) {
                    $this->mergeResult('skin_unknown', $name);
                } elseif ($sections = $this->getSections()) {
                    $this->mergeResult('skin_in_use', array($name => $sections));
                } elseif (is_dir($this->getSubdirPath()) && !$this->lock()){
                    $this->mergeResult('skin_locking_failed', $name);
                } else {
                    $assetFailure = false;

                    foreach ($this->getAssets() as $assetModel) {
                        if (!$assetModel->deleteRows()) {
                            $this->mergeResult($assetModel->getString().'_deletion_failed', $name);
                        }
                    }

                    $assetFailure ?: $ready[] = $name;
                }
            }

            if ($ready) {
                if ($this->setNames($ready) && $this->deleteRows()) {
                    $done = $ready;

                    self::unsetInstalled($ready);

                    if (in_array(self::getEditing(), $ready)) {
                        $default = self::getDefault();

                        $default ? self::setEditing($default) : '';
                    }

                    $this->mergeResult('skin_deleted', $ready, 'success');

                    update_lastmod('skin.delete', $ready);
                } else {
                    $this->mergeResult('skin_deletion_failed', $ready);
                }
            }

            foreach ($names as $name) {
                $this->setName($name);

                if (!$clean && $this->isLocked() && !$this->unlock()) {
                    $this->mergeResult('skin_unlocking_failed', $name);
                } elseif ($clean &&
                          is_dir($this->getSubdirPath()) &&
                          !\Txp::get('Textpattern\Admin\Tools')::removeFiles($this->getDirPath(), $name)
                ) {
                    $this->mergeResult('skin_files_deletion_failed', $name);
                }
            }

            callback_event('skin.delete', '', 0, array(
                'names' => $names,
                'done'  => $done,
            ));

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function render()
        {
            return $this->renderList($this->getMessage());
        }

        /**
         * Render the main panel view.
         *
         * @param  mixed $message The activity message
         * @return HTML
         * @see          getTableData(), renderCreateBlock(), renderMultiEditForm();
         */

        public function renderList($message = '')
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

            $createBlock = has_privs('skin.edit') ? self::renderCreateBlock() : '';

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

            $rs = Skin::getTableData($criteria, $sortSQL, $offset, $limit);

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
                    .n.self::renderMultiEditForm($page, $sort, $dir, $crit, $search_method)
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
         * Render the .txp-control-panel div.
         *
         * @return HTML div containing the 'Create' button and the import form.
         * @see        renderImportForm(), renderCreateButton().
         */

        protected static function renderCreateBlock()
        {
            return tag(
                self::renderCreateButton().self::renderImportForm(),
                'div',
                array('class' => 'txp-control-panel')
            );
        }

        /**
         * Render the button to create a new skin.
         *
         * @return HTML Link.
         */

        protected static function renderCreateButton()
        {
            return sLink('skin', 'edit', gTxt('create_skin'), 'txp-button');
        }

        /**
         * Render the skin import form.
         *
         * @return HTML The form or a message if no new skin directory is found.
         */

        protected static function renderImportForm()
        {
            $new = array_diff_key(self::getUploaded(), self::getInstalled());

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
         * Render a multi-edit form widget.
         *
         * @param  int    $page          The current page number
         * @param  string $sort          The current sorting value
         * @param  string $dir           The current sorting direction
         * @param  string $crit          The current search criteria
         * @param  string $search_method The current search method
         * @return HTML
         */

        public function renderMultiEditForm($page, $sort, $dir, $crit, $search_method)
        {
            $removeExtra = checkbox2('clean', get_pref('remove_extra_templates', true), 0, 'clean')
                           .n.tag(gtxt('remove_extra_templates'), 'label', array('for' => 'clean'))
                           .popHelp('remove_extra_templates');

            $removeAll = checkbox2('clean', get_pref('remove_extra_templates', true), 0, 'clean')
                         .n.tag(gtxt('remove_skin_files'), 'label', array('for' => 'clean'))
                         .popHelp('remove_skin_files');

            $methods = array(
                'import'    => array('label' => gTxt('import'), 'html' => $removeExtra),
                'duplicate' => gTxt('duplicate'),
                'export'    => array('label' => gTxt('export'), 'html' => $removeExtra),
                'delete'    => array('label' => gTxt('delete'), 'html' => $removeAll),
            );

            return multi_edit($methods, 'skin', 'multi_edit', $page, $sort, $dir, $crit, $search_method);
        }

        /**
         * Render the edit form.
         *
         * @param  mixed $message
         * @return HTML
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
                $rs = $this->setName($name)->getRow();

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
    }
}

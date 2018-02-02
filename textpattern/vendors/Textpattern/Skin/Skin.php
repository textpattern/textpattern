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
         * Skin assets related objects.
         *
         * @var array Page, Form and CSS class objects.
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

        protected static $event = 'skin';

        /**
         * Class related main file.
         *
         * @var string Filename.
         * @see        getFile().
         */

        protected static $filename = 'manifest.json';

        /**
         * Importable skins.
         *
         * @var array Associative array of skin names and their titles
         * @see       setUploaded(), getUploaded().
         */

        protected static $uploaded;

        /**
         * Class related directory path.
         *
         * @var string Path.
         * @see        setDirPath(), getDirPath().
         */

        protected $dirPath;

        /**
         * {@inheritdoc}
         */

        public function __construct()
        {
            $this->setName();
        }

        protected function mergeResults($asset, $status) {
            $thisResults = $this->getResults();
            $this->results = array_merge($thisResults, $asset->getResults($status));

            return $this;
        }

        /**
         * $dirPath property setter.
         *
         * @param  string $path          Path (default: get_pref('path_to_site').DS.get_pref('skin_dir')).
         * @return string $this->dirPath
         */

        public function setDirPath($path = null)
        {
            $path !== null ?: $path = get_pref('path_to_site').DS.get_pref('skin_dir');

            $this->dirPath = rtrim($path, DS);

            return $this->getDirPath();
        }

        /**
         * $dirPath property getter
         *
         * @return string $this->dirPath
         */

        protected function getDirPath()
        {
            $this->dirPath !== null ?: $this->setDirPath();

            return $this->dirPath;
        }

        /**
         * $assets property setter.
         *
         * @param array   $pages  Page names to work with;
         * @param array   $forms  Form names to work with;
         * @param array   $styles CSS names to work with.
         * @return object $this
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
         * @return array $this->$assets
         */

        protected function getAssets()
        {
            $this->assets !== null ?: $this->setAssets();

            return $this->assets;
        }

        /**
         * {@inheritdoc}
         */

        protected static function sanitize($name)
        {
            return sanitizeForTheme($name);
        }

        /**
         * $infos and $name properties setter.
         *
         * @param  string $name        Skin name;
         * @param  string $title       Skin title;
         * @param  string $version     Skin version;
         * @param  string $description Skin description;
         * @param  string $author      Skin author;
         * @param  string $author_uri  Skin author URL;
         * @return object $this
         */

        public function setInfos(
            $name,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null
        ) {
            $this->setName($name);

            $name = $this->getName();

            $title ?: $title = ucfirst($name);

            $this->infos = compact('name', 'title', 'version', 'description', 'author', 'author_uri');

            return $this;
        }

        /**
         * Whether a skin is installed or not.
         *
         * @param  string $name Skin name (default: $this->getName()).
         * @return bool
         */

        public function isInstalled($name = null)
        {
            $name !== null ?: $name = $this->getName();

            if (self::$installed === null) {
                $isInstalled = (bool) $this->getField('name', "name = '".$name."'");
            } else {
                $isInstalled = in_array($name, array_keys($this->getInstalled()));
            }

            return $isInstalled;
        }

        /**
         * Get a $dir property value related subdirectory path.
         *
         * @param string  $name Directory(/skin) name (default: $this->getName()).
         * @return string       The Path
         */

        public function getSubdirPath($name = null)
        {
            $name !== null ?: $name = $this->getName();

            return $this->getDirPath().DS.$name;
        }

        /**
         * $file property getter.
         *
         * @return string self::$filename.
         */

        protected static function getFilename()
        {
            return self::$filename;
        }

        /**
         * Get the $file property value related path.
         *
         * @return string Path.
         */

        protected function getFilePath()
        {
            return $this->getSubdirPath().DS.self::getFilename();
        }

        /**
         * Get and complete the skin related file contents.
         *
         * @return array Associative array of JSON fields and their related values / fallback values.
         */

        protected function getFileContents()
        {
            $contents = json_decode(file_get_contents($this->getFilePath()), true);

            if ($contents !== null) {
                extract($contents);

                !empty($title) ?: $title = ucfirst($this->getName());
                !empty($version) ?: $version = gTxt('unknown');
                !empty($description) ?: $description = '';
                !empty($author) ?: $author = gTxt('unknown');
                !empty($author_uri) ?: $author_uri = '';

                $contents = compact('title', 'version', 'description', 'author', 'author_uri');
            }

            return $contents;
        }

        /**
         * $sections property getter.
         *
         * @param array Section names.
         */

        protected function getSections()
        {
            return array_values(
                safe_column(
                    'name',
                    'txp_section',
                    "skin ='".doSlash($this->getName())."'"
                )
            );
        }

        /**
         * Update the txp_section table.
         *
         * @param  string $set   The SET clause (default: "skin = '".doSlash($this->getName())."'")
         * @param  string $where The WHERE clause (default: "skin = '".doSlash($this->getBase())."'")
         * @return bool          FALSE on error.
         */

        protected function updateSections($set = null, $where = null)
        {
            $set !== null ?: $set = "skin = '".doSlash($this->getName())."'";
            $where !== null ?: $where = "skin = '".doSlash($this->getBase())."'";

            return safe_update('txp_section', $set, $where);
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

        public function setEditing($name = null)
        {
            global $prefs;

            $name !== null ?: $name = $this->getName();
            $prefs['skin_editing'] = $name;

            set_pref('skin_editing', $name, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);

            return self::getEditing();
        }

        /**
         * Get the skin name used by the default section.
         *
         * @return mixed Skin name or FALSE on error.
         */

        public static function getDefault()
        {
            return safe_field('skin', 'txp_section', 'name = "default"');
        }

        /**
         * Create a file in the $dir property value related directory.
         *
         * @param  string $pathname The file related path (default: $this->getName().DS.self::getFilename()).
         * @param  mixed  $contents The file related contents as as a string or
         *                          as an associative array for a .json file
         *                          (uses the $infos property related array).
         * @return bool             Written octets number or FALSE on error.
         */

        protected function createFile($pathname = null, $contents = null) {
            $pathname !== null ?: $pathname = $this->getName().DS.self::getFilename();

            if ($contents === null) {
                $contents = array_merge(
                    $this->getInfos(),
                    array('txp-type' => 'textpattern-theme')
                );

                unset($contents['name']);
            }

            if (pathinfo($pathname, PATHINFO_EXTENSION) === 'json') {
                $contents = JSONPrettyPrint(json_encode($contents, TEXTPATTERN_JSON));
            }

            return file_put_contents($this->getDirPath().DS.$pathname, $contents);
        }

        /**
         * $uploaded property setter.
         *
         * @return object $this.
         */

        protected function setUploaded()
        {
            self::$uploaded = array();
            $files = $this->getFiles(array(self::getFilename()), 1);

            if ($files) {
                foreach ($files as $file) {
                    $filePath = $file->getPath();
                    $name = basename($file->getPath());

                    if ($name === self::sanitize($name)) {
                        $infos = $file->getJSONContents();
                        !$infos ?: self::$uploaded[$name] = $infos['title'];
                    }
                }
            }

            return $this->getUploaded();
        }

        /**
         * $uploaded property getter.
         *
         * @return array self::$uploaded.
         */

        protected function getUploaded()
        {
            return self::$uploaded === null ? $this->setUploaded() : self::$uploaded;
        }

        /**
         * $installed property merger.
         *
         * @param array self::$installed.
         */

        protected function mergeInstalled($skins)
        {
            self::$installed = array_merge($this->getInstalled(), $skins);

            return $this->getInstalled();
        }

        /**
         * $installed property remover.
         *
         * @return array self::$installed.
         */

        protected function removeInstalled($names)
        {
            self::$installed = array_diff_key(
                $this->getInstalled(),
                array_fill_keys($names, '')
            );

            return $this->getInstalled();
        }

        /**
         * {@inheritdoc}
         */

        protected function getTableData($criteria, $sortSQL, $offset, $limit)
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
         * Create/CreateFrom a single skin (and its related assets)
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        public function create() {
            $infos = $this->getInfos();
            $name = $infos['name'];
            $base = $this->getBase();

            callback_event('skin.create', '', 1, array(
                'infos' => $infos,
                'base'  => $base,
            ));

            $done = false;

            if (empty($name)) {
                $this->mergeResult('skin_name_invalid', $name);
            } elseif ($base && !$this->isInstalled($base)) {
                $this->mergeResult('skin_unknown', $base);
            } elseif ($this->isInstalled()) {
                $this->mergeResult('skin_already_exists', $name);
            } elseif (is_dir($subdirPath = $this->getSubdirPath())) {
                // Create a skin which would already have a related directory could cause conflicts.
                $this->mergeResult('skin_already_exists', $subdirPath);
            } elseif (!$this->createRow()) {
                $this->mergeResult('skin_creation_failed', $name);
            } else {
                $this->mergeResult('skin_created', $name, 'success');

                // Start working with the skin related assets.
                foreach ($this->getAssets() as $assetModel) {
                    if ($base) {
                        $this->setName($base);
                        $rows = $assetModel->getRows();
                        $this->setName($name);
                    } else {
                        $rows = null;
                    }

                    if (!$assetModel->createRows($rows)) {
                        $assetsfailed = true;

                        $this->mergeResult($assetModel->getEvent().'_creation_failed', $name);
                    }
                }

                // If the assets related process did not failed; that is a success…
                isset($assetsfailed) ?: $done = $name;
            }

            callback_event('skin.create', '', 0, array(
                'infos' => $infos,
                'base'  => $base,
                'done'  => $done, // the created skin name, or null on error.
            ));

            return $this; // Chainable.
        }

        /**
         * Update a single skin (and its related dependencies)
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        public function update() {
            $infos = $this->getInfos();
            $name = $infos['name'];
            $base = $this->getBase();

            callback_event('skin.update', '', 1, array(
                'infos' => $infos,
                'base'  => $base,
            ));

            $done = null; // See the final callback event.
            $ready = false;

            if (empty($name)) {
                $this->mergeResult('skin_name_invalid', $name);
            } elseif (!$this->isInstalled($base)) {
                $this->mergeResult('skin_unknown', $base);
            } elseif ($base !== $name && $this->isInstalled()) {
                $this->mergeResult('skin_already_exists', $name);
            } elseif (is_dir($subdirPath = $this->getSubdirPath()) && $base !== $name) {
                // Rename the skin with a name which would already have a related directory could cause conflicts.
                $this->mergeResult('skin_already_exists', $subdirPath);
            } elseif (!$this->updateRow()) {
                $this->mergeResult('skin_update_failed', $base);
                $locked = $base;
            } else {
                $this->mergeResult('skin_updated', $name, 'success');
                $ready = true;
                $locked = $base;

                // Rename the skin related directory to allow new updates from files.
                if (is_dir($this->getSubdirPath($base)) && !@rename($this->getSubdirPath($base), $subdirPath)) {
                    $this->mergeResult('path_renaming_failed', $base, 'warning');
                } else {
                    $locked = $name;
                }
            }

            if ($ready) {
                // Update skin related sections.
                $sections = $this->getSections();

                if ($sections && !$this->updateSections()) {
                    $this->mergeResult('skin_related_sections_update_failed', array($base => $sections));
                }

                // update the skin_editing pref if needed.
                self::getEditing() !== $base ?: $this->setEditing();

                // Start working with the skin related assets.
                foreach ($this->getAssets() as $assetModel) {
                    if (!$assetModel->updateRow("skin = '".doSlash($this->getName())."'", "skin = '".doSlash($this->getBase())."'")) {
                        $assetsFailed = true;
                        $this->mergeResult($assetModel->getEvent().'_update_failed', $base);
                    }
                }

                // If the assets related process did not failed; that is a success…
                isset($assetsFailed) ?: $done = $name;
            }

            callback_event('skin.update', '', 0, array(
                'infos' => $infos,
                'base'  => $base,
                'done'  => $done, // the updated skin name, or null on error.
            ));

            return $this; // Chainable
        }

        /**
         * Duplicate multiple skins (and their related $assets)
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        public function duplicate()
        {
            $names = $this->getNames();

            callback_event('skin.duplicate', '', 1, array('names' => $names));

            $ready = $done = array(); // See the final callback event.

            foreach ($names as $name) {
                $subdirPath = $this->setName($name)->getSubdirPath();
                $copy = $name.'_copy';

                if (!$this->isInstalled()) {
                    $this->mergeResult('skin_unknown', $name);
                } elseif ($this->isInstalled($copy)) {
                    $this->mergeResult('skin_already_exists', $copy);
                } elseif (is_dir($copyPath = $this->getSubdirPath($copy))) {
                    $this->mergeResult('skin_already_exists', $copyPath);
                } else {
                    $ready[] = $name;
                }
            }

            if ($ready) {
                $rows = $this->getRows(null, "name IN ('".implode("', '", array_map('doSlash', $ready))."')"); // Get all skin rows at once.

                if (!$rows) {
                    $this->mergeResult('skin_duplication_failed', $name);
                } else {
                    foreach ($rows as $row) {
                        extract($row);

                        $copy = $name.'_copy';
                        $copyTitle = $title.' (copy)';

                        if (!$this->setInfos($copy, $copyTitle, $version, $description, $author, $author_uri)->createRow()) {
                            $this->mergeResult('skin_duplication_failed', $name);
                        } else {
                            $this->mergeResult('skin_duplicated', $name, 'success');
                            $this->mergeInstalled(array($copy => $copyTitle));

                            // Start working with the skin related assets.
                            foreach ($this->getAssets() as $assetModel) {
                                $this->setName($name);
                                $assetString = $assetModel::getEvent();
                                $assetRows = $assetModel->getRows();

                                if (!$assetRows) {
                                    $deleteExtraFiles = true;

                                    $this->mergeResult($assetString.'_not_found', array($skin => $subdirPath));
                                } elseif ($this->setName($copy) && !$assetModel->createRows($assetRows)) {
                                    $deleteExtraFiles = true;

                                    $this->mergeResult($assetString.'_duplication_failed', array($skin => $notImported));
                                }
                            }

                            $this->setName($name); // Be sure to restore the right $name.

                            // If the assets related process did not failed; that is a success…
                            isset($deleteExtraFiles) ?: $done[] = $name;
                        }
                    }
                }
            }

            callback_event('skin.duplicate', '', 0, array(
                'names' => $names,
                'done'  => $done, // Array of the duplicated skin names.
            ));

            return $this; // Chainable
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = false, $override = false)
        {
            $clean == $this->getCleaningPref() ?: $this->switchCleaningPref();
            $names = $this->getNames();

            callback_event('skin.import', '', 1, array('names' => $names));

            $done = array(); // See the final callback event.

            foreach ($names as $name) {
                $this->setName($name);

                $isInstalled = $this->isInstalled();
                $isInstalled ?: $clean = $override = false; // Avoid useless work.

                if (!$override && $isInstalled) {
                    $this->mergeResult('skin_already_exists', $name);
                } elseif (!is_readable($filePath = $this->getFilePath())) {
                    $this->mergeResult('path_not_readable', $filePath);
                } else {
                    $skinInfos = array_merge(array('name' => $name), $this->getFileContents());

                    if (!$skinInfos) {
                        $this->mergeResult('invalid_json', $filePath);
                    } else {
                        extract($skinInfos);

                        $this->setInfos($name, $title, $version, $description, $author, $author_uri);

                        if (!$override && !$this->createRow()) {
                            $this->mergeResult('skin_import_failed', $name);
                        } elseif ($override && !$this->updateRow(null, "name = '".doSlash($this->getBase())."'")) {
                            $this->mergeResult('skin_import_failed', $name);
                        } else {
                            $this->mergeResult('skin_imported', $name, 'success');
                            $this->mergeInstalled(array($name => $title));

                            // Start working with the skin related assets.
                            foreach ($this->getAssets() as $asset) {
                                $asset->import($clean, $override);

                                if (is_array($asset->getMessage())) {
                                    $assetFailed = true;

                                    $this->mergeResults($asset, array('warning', 'error'));
                                }
                            }
                        }

                        // If the assets related process did not failed; that is a success…
                        isset($assetFailed) ?: $done[] = $name;
                    }
                }
            }

            callback_event('skin.import', '', 0, array(
                'names' => $names,
                'done'  => $done, // Array of the imported skin names.
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

            callback_event('skin.export', '', 1, array('names' => $names));

            $ready = $done = array();

            foreach ($names as $name) {
                $this->setName($name);

                $subdirPath = $this->getSubdirPath();

                if (!is_writable($subdirPath)) {
                    $clean = false;
                    $override = false;
                }

                if (!self::isValidDirName($name)) {
                    $this->mergeResult('skin_unsafe_name', $name);
                } elseif (!$override && is_dir($subdirPath)) {
                    $this->mergeResult('skin_already_exists', $subdirPath);
                } elseif (!is_dir($subdirPath) && !@mkdir($subdirPath)) {
                    $this->mergeResult('path_not_writable', $subdirPath);
                } else {
                    $ready[] = $name;
                }
            }

            if ($ready) {
                $rows = $this->getRows(null, "name IN ('".implode("', '", array_map('doSlash', $ready))."')");

                if (!$rows) {
                    $this->mergeResult('skin_unknown', $names);
                } else {
                    foreach ($rows as $row) {
                        extract($row);

                        $this->setName($name);

                        if ($this->setInfos($name, $title, $version, $description, $author, $author_uri)->createFile() === false) {
                            $this->mergeResult('skin_export_failed', $name);
                        } else {
                            $this->mergeResult('skin_exported', $name, 'success');

                            foreach ($this->getAssets() as $asset) {
                                $asset->export($clean, $override);

                                if (is_array($asset->getMessage())) {
                                    $assetFailed = true;

                                    $this->mergeResults($asset, array('warning', 'error'));
                                }
                            }

                            isset($assetFailed) ?: $done[] = $name;
                        }
                    }
                }
            }

            callback_event('skin.export', '', 0, array(
                'names' => $names,
                'done'  => $done, // Array of the exported skin names.
            ));

            return $this;
        }

        /**
         * Delete multiple skins (and their related $assets + directories if empty)
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        public function delete($clean = false)
        {
            $names = $this->getNames();

            callback_event('skin.delete', '', 1, array('names' => $names));

            $ready = $done = array();

            foreach ($names as $name) {
                if (!$this->setName($name)->isInstalled()) {
                    $this->mergeResult('skin_unknown', $name);
                } elseif ($sections = $this->getSections()) {
                    $this->mergeResult('skin_in_use', array($name => $sections));
                } else {
                    /**
                     * Start working with the skin related assets.
                     * Done first as assets won't be accessible
                     * once their parent skin will be deleted.
                     */
                    $assetFailed = false;

                    foreach ($this->getAssets() as $assetModel) {
                        if (!$assetModel->deleteRows()) {
                            $this->mergeResult($assetModel->getEvent().'_deletion_failed', $name);
                        }
                    }

                    $assetFailed ?: $ready[] = $name;
                }
            }

            if ($ready) {
                if ($this->deleteRows("name IN ('".implode("', '", array_map('doSlash', $ready))."')")) {
                    $done = $ready;

                    $this->removeInstalled($ready);

                    if (in_array(self::getEditing(), $ready)) {
                        $default = self::getDefault();

                        !$default ?: $this->setEditing($default);
                    }

                    $this->mergeResult('skin_deleted', $ready, 'success');

                    // Remove all skins files and directories if needed.
                    if ($clean) {
                        foreach ($ready as $name) {
                            if (is_dir($this->getSubdirPath($name)) && !$this->deleteFiles(array($name))) {
                                $this->mergeResult('skin_files_deletion_failed', $name);
                            }
                        }
                    }

                    update_lastmod('skin.delete', $ready);
                } else {
                    $this->mergeResult('skin_deletion_failed', $ready);
                }
            }

            callback_event('skin.delete', '', 0, array(
                'names' => $names,
                'done'  => $done, // Array of the deleted skins name.
            ));

            return $this;
        }

        /**
         * Delete Files from the $dir property value related dirstory.
         *
         * @param  string $names directory/file names.
         * @return bool   0 on error.
         */

        protected function deleteFiles($names = null)
        {
            return \Txp::get('Textpattern\Admin\Tools')::removeFiles($this->getDirPath(), $names);
        }

        /**
         * Control the admin tab.
         */

        public function admin()
        {
            if (!defined('txpinterface')) {
                die('txpinterface is undefined.');
            }

            global $event, $step;

            if ($event === 'skin') {
                require_privs($event);

                bouncer($step, array(
                    'skin_change_pageby' => true, // Prefixed to make it work with the paginator…
                    'list'          => false,
                    'edit'          => false,
                    'save'          => true,
                    'import'        => false,
                    'multi_edit'    => true,
                ));

                switch ($step) {
                    case 'save':
                        $infos = array_map('assert_string', psa(array(
                            'name',
                            'title',
                            'old_name',
                            'old_title',
                            'version',
                            'description',
                            'author',
                            'author_uri',
                            'copy',
                        )));

                        extract($infos);

                        if ($old_name) {
                            if ($copy) {
                                $name === $old_name ? $name .= '_copy' : '';
                                $title === $old_title ? $title .= ' (copy)' : '';

                                $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                                     ->setBase($old_name)
                                     ->create();

                            } else {
                                $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                                     ->setBase($old_name)
                                     ->update();
                            }
                        } else {
                            $title === '' ? $title = ucfirst($name) : '';
                            $author === '' ? $author = substr(cs('txp_login_public'), 10) : '';
                            $version === '' ? $version = '0.0.1' : '';

                            $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                                 ->create();
                        }
                        break;
                    case 'multi_edit':
                        extract(psa(array(
                            'edit_method',
                            'selected',
                            'clean',
                        )));

                        if (!$selected || !is_array($selected)) {
                            return;
                        }

                        $this->setNames(ps('selected'));

                        switch ($edit_method) {
                            case 'export':
                                $this->export($clean, true);
                                break;
                            case 'duplicate':
                                $this->duplicate();
                                break;
                            case 'import':
                                $this->import($clean, true);
                                break;
                            case 'delete':
                                $this->delete($clean);
                                break;
                        }
                        break;
                    case 'edit':
                        break;
                    case 'import':
                        $this->setNames(array(ps('skins')))->import();
                        break;
                    case 'skin_change_pageby':
                        \Txp::get('\Textpattern\Admin\Paginator', $event, '')->change();
                        break;
                }

                return $this->render($step);
            }
        }

        /**
         * Render (echo) the $step related admin tab.
         *
         * @param string $step
         */

        public function render($step)
        {
            if ($step === 'edit') {
                echo $this->getEditForm();
            } else {
                echo $this->getList();
            }
        }

        /**
         * {@inheritdoc}
         */

        public function getList($message = '')
        {
            $message .= $this->getMessage();
            $event = $this->getEvent();

            pagetop(gTxt('tab_'.$event), $message);

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

            $search = \Txp::get('Textpattern\Search\Filter', $event, array(
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

            $total = $this->countRows($criteria);;

            $table = \Txp::get('Textpattern\Admin\Table');

            return $table->render(
                compact('total', 'criteria'),
                $this->getSearchBlock($search),
                $this->getCreateBlock(),
                $this->getContentBlock($total, $criteria, $sort, $dir),
                $this->getFootBlock()
            );
        }

        /**
         * Get the admin related search form wrapped in its div.
         *
         * @param  object $search Textpattern\Search\Filter class object.
         * @return HTML
         */

        public function getSearchBlock($search)
        {
            return n.tag(
                $search->renderForm('skin', array('placeholder' => 'search_skins')),
                'div',
                array(
                    'class' => 'txp-layout-4col-3span',
                    'id'    => $event.'_control',
                )
            );
        }

        /**
         * Render the .txp-control-panel div.
         *
         * @return HTML div containing the 'Create' button and the import form.
         * @see        getImportForm(), getCreateButton().
         */

        protected function getCreateBlock()
        {
            if (has_privs(self::getEvent().'.edit')) {
                return tag(
                    self::getCreateButton().$this->getImportForm(),
                    'div',
                    array('class' => 'txp-control-panel')
                );
            }
        }

        /**
         * Render the skin import form.
         *
         * @return HTML The form or a message if no new skin directory is found.
         */

        protected function getImportForm()
        {
            $dirPath = $this->getDirPath();

            if (is_dir($dirPath) && is_writable($dirPath)) {
                $new = array_diff_key($this->getUploaded(), $this->getInstalled());

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
            } else {
                return n
                    .graf(
                        span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                        gTxt('img_dir_not_writeable', array('{imgdir}' => $dirPath)),
                        array('class' => 'alert-block warning')
                    );
            }
        }

        /**
         * Render the button to create a new skin.
         *
         * @return HTML Link.
         */

        protected static function getCreateButton()
        {
            return sLink('skin', 'edit', gTxt('create_skin'), 'txp-button');
        }

        public function getContentBlock($total, $criteria, $sort, $dir)
        {
            $sortSQL = $sort.' '.$dir;
            $switchDir = ($dir == 'desc') ? 'asc' : 'desc';

            if ($total < 1) {
                if ($criteria != 1) {
                    $out = graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_results_found'),
                        array('class' => 'alert-block information')
                    );
                } else {
                    $out = graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_skin_recorded'),
                        array('class' => 'alert-block error')
                    );
                }

                return $out
                       .n.tag_end('div') // End of .txp-layout-1col.
                       .n.'</div>';      // End of .txp-layout.
            }

            $paginator = \Txp::get('\Textpattern\Admin\Paginator', $event, '');
            $limit = $paginator->getLimit();

            list($page, $offset, $numPages) = pager($total, $limit, $page);

            $rs = $this->getTableData($criteria, $sortSQL, $offset, $limit);

            if ($rs) {
                $out = n.tag_start('form', array(
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

                $out .= tr($ths)
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

                    $out .= tr($tds, array('id' => 'txp_skin_'.$skin_name));
                }

                return $out
                        .n.tag_end('tbody')
                       .n.tag_end('table')
                       .n.tag_end('div');
            }
        }

        public function getFootBlock()
        {
            return self::getMultiEditForm($page, $sort, $dir, $crit, $search_method).
            \Txp::get('\Textpattern\Admin\Paginator', $event, '')->render()
                .nav_form('skin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);
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

        public function getMultiEditForm($page, $sort, $dir, $crit, $search_method)
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

        public function getEditForm($message = '')
        {
            global $step;

            require_privs('skin.edit');

            !$message ?: pagetop(gTxt('tab_skins'), $message);

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

            return form($content, '', '', 'post', 'txp-edit', '', 'skin_form');
        }
    }
}

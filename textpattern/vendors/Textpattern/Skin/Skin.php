<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * Skin
 *
 * Manage Skins and their dependencies.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin;

class Skin extends CommonBase implements SkinInterface
{
    /**
     * Skin assets related objects.
     *
     * @var array Page, Form and CSS class objects.
     * @see       setAssets().
     */

    private $assets;

    /**
     * Class related main file.
     *
     * @var string Filename.
     * @see        getFile().
     */

    protected static $filename = 'manifest.json';

    /**
     * {@inheritdoc}
     */

    protected static $extension = 'json';

    /**
     * Importable skins.
     *
     * @var array Associative array of skin names and their infos from JSON files
     * @see       setUploaded(), getUploaded().
     */

    protected $uploaded;

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
        parent::__construct();
    }

    public function __toString()
    {
        return $this->getName();
    }

    protected function mergeResults($asset, $status)
    {
        $this->results = array_merge_recursive($this->getResults(), $asset->getResults($status));

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
        $path !== null or $path = get_pref('path_to_site').DS.get_pref($this->getEvent().'_dir');

        $this->dirPath = rtrim($path, DS);
        $this->uploaded = null;

        return $this->getDirPath();
    }

    /**
     * $dirPath property getter
     *
     * @return string $this->dirPath
     */

    protected function getDirPath()
    {
        $this->dirPath !== null or $this->setDirPath();

        return $this->dirPath;
    }

    /**
     * $assets property setter.
     *
     * @param array   $pages  Page names to work with;
     * @param array   $forms  Form names to work with;
     * @param array   $styles CSS names to work with.
     * @return object $this   The current class object (chainable)
     */

    public function setAssets($pages = null, $forms = null, $styles = null)
    {
        $assets = array(
            'Page' => $pages,
            'Form' => $forms,
            'Css'  => $styles,
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
        $this->assets !== null or $this->setAssets();

        return $this->assets;
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
     * @return object $this        The current class object (chainable).
     */

    public function setInfos(
        $name,
        $title = null,
        $version = null,
        $description = null,
        $author = null,
        $author_uri = null
    ) {
        $name = $this->setName($name)->getName();

        $title or $title = ucfirst($name);

        $this->infos = compact('name', 'title', 'version', 'description', 'author', 'author_uri');

        return $this;
    }

    /**
     * Get a $dir property value related subdirectory path.
     *
     * @param string  $name Directory(/skin) name (default: $this->getName()).
     * @return string       The Path
     */

    public function getSubdirPath($name = null)
    {
        $name !== null or $name = $this->getName();

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

        $contents === null or $contents = $this->parseInfos($contents);

        return $contents;
    }

    /**
     * Parse a skin related infos.
     *
     * @return array $infos Associative array of fields and their related values / fallback values.
     */

    protected function parseInfos($infos)
    {
        extract($infos);

        !empty($title) or $title = ucfirst($this->getName());
        !empty($version) or $version = gTxt('unknown');
        !empty($description) or $description = '';
        !empty($author) or $author = gTxt('unknown');
        !empty($author_uri) or $author_uri = '';

        return compact('title', 'version', 'description', 'author', 'author_uri');
    }

    /**
     * $sections property getter.
     *
     * @param array Section names.
     */

    protected function getSections($skin = null)
    {
        $skin = doSlash(is_string($skin) ? $skin : $this->getName());
        $event = $this->getEvent();

        return array_values(
            safe_column(
                'name',
                'txp_section',
                "$event ='$skin' OR dev_{$event} ='$skin'"
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

    public function updateSections($set = null, $where = null, $dev = false)
    {
        $event = ($dev ? "{$dev}_" : '').$this->getEvent();

        $set !== null or $set = $event." = '".doSlash($this->getName())."'";

        if ($where === null) {
            $base = $this->getBase();

            $where = $base ? $event." = '".doSlash($base)."'" : '1 = 1';
        }

        return safe_update('txp_section', $set, $where);
    }

    /**
     * {@inheritdoc}
     */

    public function getEditing()
    {
        $editing = get_pref($this->getEvent().'_editing', '', true);

        if (!$editing) {
            $installed = $this->getInstalled();

            reset($installed);

            $editing = $this->setEditing(key($installed));
        }

        return $editing;
    }

    /**
     * {@inheritdoc}
     */

    public function setEditing($name = null)
    {
        global $prefs;

        $event = $this->getEvent();

        $name !== null or $name = $this->getName();
        $prefs[$event.'_editing'] = $name;

        set_pref($event.'_editing', $name, $event, PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);

        return $this->getEditing();
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

    protected function createFile($pathname = null, $contents = null)
    {
        $pathname !== null or $pathname = $this->getName().DS.self::getFilename();

        if ($contents === null) {
            $contents = array_merge(
                $this->getInfos(),
                array('txp-type' => 'textpattern-theme')
            );

            unset($contents['name']);
        }

        if (pathinfo($pathname, PATHINFO_EXTENSION) === 'json') {
            $contents = json_encode($contents, TEXTPATTERN_JSON | JSON_PRETTY_PRINT);
        }

        return file_put_contents($this->getDirPath().DS.$pathname, $contents);
    }

    /**
     * $uploaded property setter.
     *
     * @return object $this The current class object (chainable).
     */

    protected function setUploaded()
    {
        $this->uploaded = array();
        $files = $this->getFiles(array(self::getFilename()), 1);

        if ($files) {
            foreach ($files as $file) {
                $name = basename($file->getPath());

                if ($name === self::sanitize($name)) {
                    $infos = $file->getJSONContents();

                    if ($infos && $infos['txp-type'] === 'textpattern-theme') {
                        $this->uploaded[$name] = $this->setName($name)->parseInfos($infos);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function getUploaded($expanded = true)
    {
        $this->uploaded !== null or $this->setUploaded();

        if (!$expanded) {
            $contracted = array();

            foreach ($this->uploaded as $name => $infos) {
                $contracted[$name] = $infos['title'] . ' ('.$infos['version'].')';
            }

            return $contracted;
        }

        return $this->uploaded;
    }

    /**
     * $installed property merger.
     *
     * @param array $this->installed.
     */

    protected function mergeInstalled($skins)
    {
        $this->installed = array_merge($this->getInstalled(), $skins);

        return $this->getInstalled();
    }

    /**
     * $installed property remover.
     *
     * @return array $this->installed.
     */

    protected function removeInstalled($names)
    {
        $this->installed = array_diff_key(
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
        $table = $this->getTable();

        foreach ($assets as $asset) {
            $things[] = '(SELECT COUNT(*) '
                        .'FROM '.safe_pfx_j('txp_'.$asset).' '
                        .'WHERE txp_'.$asset.'.'.$this->getEvent().' = '.$table.'.name) '
                        .$asset.'_count';
        }

        $things[] = '(SELECT COUNT(*) '
            .'FROM '.safe_pfx_j('txp_section').' '
            .'WHERE dev_'.$this->getEvent().' = '.$table.'.name) '
            .'dev_section_count';

        return safe_rows_start(
            implode(', ', $things),
            $table,
            $criteria.' order by '.$sortSQL.' limit '.$offset.', '.$limit
        );
    }

    /**
     * Create/CreateFrom a single skin (and its related assets)
     * Merges results in the related property.
     *
     * @return object $this The current object (chainable).
     */

    public function create()
    {
        $event = $this->getEvent();
        $infos = $this->getInfos();
        $name = $infos['name'];
        $base = $this->getBase();
        $callbackExtra = compact('infos', 'base');
        $done = false;

        callback_event('txp.'.$event, 'create', 1, $callbackExtra);

        if (empty($name)) {
            $this->mergeResult($event.'_name_invalid', $name);
        } elseif ($base && !$this->isInstalled($base)) {
            $this->mergeResult($event.'_unknown', $base);
        } elseif ($this->isInstalled()) {
            $this->mergeResult($event.'_already_exists', $name);
        } elseif (is_dir($nameDirPath = $this->getSubdirPath())) {
            // Create a skin which would already have a related directory could cause conflicts.
            $this->mergeResult($event.'_already_exists', $nameDirPath);
        } elseif (!$this->createRow()) {
            $this->mergeResult($event.'_creation_failed', $name);
        } else {
            $this->mergeResult($event.'_created', $name, 'success');

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
            isset($assetsfailed) or $done = $name;
        }

        callback_event('txp.'.$event, 'create', 0, $callbackExtra + compact('done'));

        return $this; // Chainable.
    }

    /**
     * Update a single skin (and its related dependencies)
     * Merges results in the related property.
     *
     * @return object $this The current object (chainable).
     */

    public function update()
    {
        $event = $this->getEvent();
        $infos = $this->getInfos();
        $name = $infos['name'];
        $base = $this->getBase();
        $callbackExtra = compact('infos', 'base');
        $done = null;
        $ready = false;

        callback_event('txp.'.$event, 'update', 1, $callbackExtra);

        if (empty($name)) {
            $this->mergeResult($event.'_name_invalid', $name);
        } elseif (!$this->isInstalled($base)) {
            $this->mergeResult($event.'_unknown', $base);
        } elseif ($base !== $name && $this->isInstalled()) {
            $this->mergeResult($event.'_already_exists', $name);
        } elseif (is_dir($nameDirPath = $this->getSubdirPath()) && $base !== $name) {
            // Rename the skin with a name which would already have a related directory could cause conflicts.
            $this->mergeResult($event.'_already_exists', $nameDirPath);
        } elseif (!$this->updateRow()) {
            $this->mergeResult($event.'_update_failed', $base);
            $locked = $base;
        } else {
            $this->mergeResult($event.'_updated', $name, 'success');
            $ready = true;
            $locked = $base;
            $baseDirPath = $this->getSubdirPath($base);

            // Rename the skin related directory to allow new updates from files.
            if (is_dir($baseDirPath) && !@rename($baseDirPath, $nameDirPath)) {
                $this->mergeResult('path_renaming_failed', $base, 'warning');
            } else {
                $locked = $name;
            }
        }

        if ($ready) {
            // Update skin related sections.
            if ($sections = $this->getSections($base)) {
                $updated = $this->updateSections();
                $updated = $this->updateSections(null, null, 'dev') && $updated;
                $updated or $this->mergeResult($event.'_related_sections_update_failed', array($base => $sections));
            }

            // update the skin_editing pref if needed.
            $this->getEditing() !== $base or $this->setEditing();

            // Start working with the skin related assets.
            $assetUpdateSet = $event." = '".doSlash($this->getName())."'";
            $assetUpdateWhere = $event." = '".doSlash($this->getBase())."'";

            foreach ($this->getAssets() as $assetModel) {
                if (!$assetModel->updateRow($assetUpdateSet, $assetUpdateWhere)) {
                    $assetsFailed = true;
                    $this->mergeResult($assetModel->getEvent().'_update_failed', $base);
                }
            }

            // If the assets related process did not failed; that is a success…
            isset($assetsFailed) or $done = $name;
        }

        callback_event('txp.'.$event, 'update', 0, $callbackExtra + compact('done'));

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
        $event = $this->getEvent();
        $names = $this->getNames();
        $callbackExtra = compact('names');
        $ready = $done = array();

        callback_event('txp.'.$event, 'duplicate', 1, $callbackExtra);

        foreach ($names as $name) {
            $nameDirPath = $this->setName($name)->getSubdirPath();
            $copy = $name.'_copy';

            if (!$this->isInstalled()) {
                $this->mergeResult($event.'_unknown', $name);
            } elseif ($this->isInstalled($copy)) {
                $this->mergeResult($event.'_already_exists', $copy);
            } elseif (is_dir($copyPath = $this->getSubdirPath($copy))) {
                $this->mergeResult($event.'_already_exists', $copyPath);
            } else {
                $ready[] = $name;
            }
        }

        if ($ready) {
            $rows = $this->getRows(
                "*",
                "name IN ('".implode("', '", array_map('doSlash', $ready))."')"
            );

            if (!$rows) {
                $this->mergeResult($event.'_not_found', $ready);
            } else {
                foreach ($rows as $row) {
                    extract($row);

                    $copy = $name.'_copy';
                    $copyTitle = $title.' (copy)';

                    $this->setInfos($copy, $copyTitle, $version, $description, $author, $author_uri);

                    if (!$this->createRow()) {
                        $this->mergeResult($event.'_creation_failed', $copy);
                    } else {
                        $this->mergeResult($event.'_created', $copy, 'success');
                        $this->mergeInstalled(array($copy => $copyTitle));

                        // Start working with the skin related assets.
                        foreach ($this->getAssets() as $assetModel) {
                            $this->setName($name);
                            $assetString = $assetModel->getEvent();
                            $assetRows = $assetModel->getRows();

                            if (!$assetRows) {
                                $deleteExtraFiles = true;

                                $this->mergeResult($assetString.'_not_found', array($skin => $nameDirPath));
                            } elseif ($this->setName($copy) && !$assetModel->createRows($assetRows)) {
                                $deleteExtraFiles = true;

                                $this->mergeResult($assetString.'_creation_failed', $copy);
                            }
                        }

                        $this->setName($name); // Be sure to restore the right $name.

                        // If the assets related process did not failed; that is a success…
                        isset($deleteExtraFiles) or $done[] = $name;
                    }
                }
            }
        }

        callback_event('txp.'.$event, 'duplicate', 0, $callbackExtra + compact('done'));

        return $this; // Chainable
    }

    /**
     * {@inheritdoc}
     */

    public function import($sync = false, $override = false)
    {
        $event = $this->getEvent();
        $syncPref = 'skin_delete_from_database';
        $sync == $this->getSyncPref($syncPref) or $this->switchSyncPref($syncPref);
        $names = $this->getNames();
        $callbackExtra = compact('names', 'sync');
        $done = array();

        callback_event('txp.'.$event, 'import', 1, $callbackExtra);

        foreach ($names as $name) {
            $this->setName($name);
            $this->setBase($name);

            $isInstalled = $this->isInstalled();
            $isInstalled or $sync = $override = false; // Avoid useless work.

            if (!$override && $isInstalled) {
                $this->mergeResult($event.'_already_exists', $name);
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
                        $this->mergeResult($event.'_import_failed', $name);
                    } elseif ($override && !$this->updateRow()) {
                        $this->mergeResult($event.'_import_failed', $name);
                    } else {
                        $this->mergeResult($event.'_imported', $name, 'success');
                        $this->mergeInstalled(array($name => $title));

                        // Start working with the skin related assets.
                        foreach ($this->getAssets() as $asset) {
                            $asset->import($sync, $override);

                            if (is_array($asset->getMessage())) {
                                $assetFailed = true;

                                $this->mergeResults($asset, array('warning', 'error'));
                            }
                        }
                    }

                    // If the assets related process did not failed; that is a success…
                    isset($assetFailed) or $done[] = $name;
                }
            }
        }

        callback_event('txp.'.$event, 'import', 0, $callbackExtra + compact('done'));

        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function export($sync = false, $override = false)
    {
        $syncPref = 'skin_delete_from_disk';
        $sync == $this->getSyncPref($syncPref) or $this->switchSyncPref($syncPref);

        $event = $this->getEvent();
        $names = $this->getNames();
        $callbackExtra = compact('names', 'sync');
        $ready = $done = array();

        callback_event('txp.'.$event, 'export', 1, $callbackExtra);

        foreach ($names as $name) {
            $this->setName($name);

            $nameDirPath = $this->getSubdirPath();

            if (!is_writable($nameDirPath)) {
                $sync = false;
                $override = false;
            }

            if (!self::isExportable($name)) {
                $this->mergeResult($event.'_unsafe_name', $name);
            } elseif (!$override && is_dir($nameDirPath)) {
                $this->mergeResult($event.'_already_exists', $nameDirPath);
            } elseif (!is_dir($nameDirPath) && !@mkdir($nameDirPath)) {
                $this->mergeResult('path_not_writable', $nameDirPath);
            } else {
                $ready[] = $name;
            }
        }

        if ($ready) {
            $rows = $this->getRows(
                "*",
                "name IN ('".implode("', '", array_map('doSlash', $ready))."')"
            );

            if (!$rows) {
                $this->mergeResult($event.'_unknown', $names);
            } else {
                foreach ($rows as $row) {
                    extract($row);

                    $this->setInfos($name, $title, $version, $description, $author, $author_uri);

                    if ($this->createFile() === false) {
                        $this->mergeResult($event.'_export_failed', $name);
                    } else {
                        $this->mergeResult($event.'_exported', $name, 'success');

                        foreach ($this->getAssets() as $asset) {
                            $asset->export($sync, $override);

                            if (is_array($asset->getMessage())) {
                                $assetFailed = true;

                                $this->mergeResults($asset, array('warning', 'error'));
                            }
                        }

                        isset($assetFailed) or $done[] = $name;
                    }
                }
            }
        }

        callback_event('txp.'.$event, 'export', 0, $callbackExtra + compact('done'));

        return $this;
    }

    /**
     * Delete multiple skins (and their related $assets + directories if empty)
     * Merges results in the related property.
     *
     * @return object $this The current object (chainable).
     */

    public function delete($sync = false)
    {
        $syncPref = 'skin_delete_entirely';
        $sync == $this->getSyncPref($syncPref) or $this->switchSyncPref($syncPref);

        $event = $this->getEvent();
        $names = $this->getNames();
        $callbackExtra = compact('names', 'sync');
        $ready = $done = array();

        callback_event('txp.'.$event, 'delete', 1, $callbackExtra);

        foreach ($names as $name) {
            $isDir = is_dir($this->getSubdirPath($name));

            if (!$this->setName($name)->isInstalled()) {
                $this->mergeResult($event.'_unknown', $name);
            } elseif ($sections = $this->getSections($name)) {
                $this->mergeResult($event.'_in_use', array($name => $sections));
            } else {
                /**
                 * Start working with the skin related assets.
                 * Done first as assets won't be accessible
                 * once their parent skin will be deleted.
                 */
                $assetFailed = false;

                foreach ($this->getAssets() as $assetModel) {
                    if (!$assetModel->deleteRows()) {
                        $assetFailed = true;
                        $this->mergeResult($assetModel->getEvent().'_deletion_failed', $name);
                    } elseif ($sync && $isDir) {
                        $notDeleted = $assetModel->deleteExtraFiles();

                        if ($notDeleted) {
                            $this->mergeResult($assetModel->getEvent().'_files_deletion_failed', array($name => $notDeleted));
                        }
                    }
                }

                $assetFailed or $ready[] = $name;
            }
        }

        if ($ready) {
            if ($this->deleteRows("name IN ('".implode("', '", array_map('doSlash', $ready))."')")) {
                $done = $ready;

                $this->removeInstalled($ready);

                if (in_array($this->getEditing(), $ready)) {
                    $default = $this->getDefault();

                    !$default or $this->setEditing($default);
                }

                $this->mergeResult($event.'_deleted', $ready, 'success');

                // Remove all skins files and directories if needed.
                if ($sync) {
                    $notDeleted = $this->deleteFiles($ready);

                    !$notDeleted or $this->mergeResult($event.'_files_deletion_failed', $notDeleted);
                }

                update_lastmod($event.'.delete', $ready);
            } else {
                $this->mergeResult($event.'_deletion_failed', $ready);
            }
        }

        callback_event('txp.'.$event, 'delete', 0, $callbackExtra + compact('done'));

        return $this;
    }

    /**
     * Delete Files from the $dir property value related directory.
     *
     * @param  string $names directory/file names.
     * @return bool   0 on error.
     */

    protected function deleteFiles($names = null)
    {
        $notRemoved = array();

        foreach ($names as $name) {
            if (is_dir($this->getSubdirPath($name))) {
                $filePath = $this->getFilePath();

                if (file_exists($filePath) && !unlink($filePath)) {
                    $notRemoved[$name][] = $filePath;
                }

                $subdirPath = $this->getSubdirPath();
                $isDirEmpty = self::isDirEmpty($subdirPath);

                if (!isset($notRemoved[$name]) && ($isDirEmpty && !@rmdir($subdirPath) || !$isDirEmpty)) {
                    $notRemoved[$name][] = $subdirPath;
                }
            }
        }

        return $notRemoved;
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

        if ($event === $this->getEvent()) {
            require_privs($event);

            bouncer($step, array(
                $event.'_change_pageby' => true, // Prefixed to make it work with the paginator…
                'list'                  => false,
                'edit'                  => false,
                'save'                  => true,
                'import'                => false,
                'multi_edit'            => true,
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
                            $name !== $old_name or $name .= '_copy';
                            $title !== $old_title or $title .= ' (copy)';

                            $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                                 ->setBase($old_name)
                                 ->create();
                        } else {
                            $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                                 ->setBase($old_name)
                                 ->update();
                        }
                    } else {
                        $title !== '' or $title = ucfirst($name);
                        $author !== '' or $author = substr(cs('txp_login_public'), 10);
                        $version !== '' or $version = '0.0.1';

                        $this->setInfos($name, $title, $version, $description, $author, $author_uri)
                             ->create();
                    }
                    break;
                case 'multi_edit':
                    extract(psa(array(
                        'edit_method',
                        'selected',
                        'sync',
                    )));

                    if (!$selected || !is_array($selected)) {
                        return $this->render($step);
                    }

                    $this->setNames(ps('selected'));

                    switch ($edit_method) {
                        case 'export':
                            $this->export($sync, true);
                            break;
                        case 'duplicate':
                            $this->duplicate();
                            break;
                        case 'import':
                            $this->import($sync, true);
                            break;
                        case 'delete':
                            $this->delete($sync);
                            break;
                    }
                    break;
                case 'edit':
                    break;
                case 'import':
                    $this->setNames(array(ps('skins')))->import();
                    break;
                case $event.'_change_pageby':
                    $this->getPaginator()->change();
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
        $message = $this->getMessage();

        if ($step === 'edit') {
            echo $this->getEditForm($message);
        } else {
            echo $this->getList($message);
        }
    }

    /**
     * {@inheritdoc}
     */

    protected function getList($message = '')
    {
        $event = $this->getEvent();
        $table = $this->getTable();

        pagetop(gTxt('tab_'.$event), $message);

        extract(gpsa(array(
            'page',
            'sort',
            'dir',
            'crit',
            'search_method',
        )));

        if ($sort === '') {
            $sort = get_pref($event.'_sort_column', 'name');
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

            set_pref($event.'_sort_column', $sort, $event, PREF_HIDDEN, '', 0, PREF_PRIVATE);
        }

        if ($dir === '') {
            $dir = get_pref($event.'_sort_dir', 'desc');
        } else {
            $dir = ($dir == 'asc') ? 'asc' : 'desc';

            set_pref($event.'_sort_dir', $dir, $event, PREF_HIDDEN, '', 0, PREF_PRIVATE);
        }

        $searchOpts = array();

        foreach (array('name', 'title', 'description', 'author') as $option) {
            $searchOpts[$option] = array(
                'column' => $table.'.'.$option,
                'label'  => gTxt($option),
            );
        }

        $search = $this->getSearchFilter($searchOpts);

        list($criteria, $crit, $search_method) = $search->getFilter();

        $total = $this->countRows($criteria);
        $limit = $this->getPaginator()->getLimit();

        list($page, $offset, $numPages) = pager($total, $limit, $page);

        $table = \Txp::get('Textpattern\Admin\Table');

        return $table->render(
            compact('total', 'crit') + array('help' => 'skin_overview'),
            $this->getSearchBlock($search),
            $this->getCreateBlock(),
            $this->getContentBlock(compact('offset', 'limit', 'total', 'criteria', 'crit', 'search_method', 'page', 'sort', 'dir')),
            $this->getFootBlock(compact('limit', 'numPages', 'total', 'crit', 'search_method', 'page', 'sort', 'dir'))
        );
    }

    /**
     * Get the admin related search form wrapped in its div.
     *
     * @param  object $search Textpattern\Search\Filter class object.
     * @return HTML
     */

    protected function getSearchBlock($search)
    {
        $event = $this->getEvent();

        return n.tag(
            $search->renderForm($event, array('placeholder' => 'search_skins')),
            'div',
            array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );
    }

    /**
     * Get the .txp-control-panel div.
     *
     * @return HTML div containing the 'Create' button and the import form.
     * @see        getImportForm(), getCreateButton().
     */

    protected function getCreateBlock()
    {
        if (has_privs($this->getEvent().'.edit')) {
            return tag(
                $this->getCreateButton().$this->getImportForm(),
                'div',
                array('class' => 'txp-control-panel txp-async-update', 'id' => 'skin_control_panel')
            );
        }
    }

    /**
     * Get the skin import form.
     *
     * @return HTML The form or a message if no new skin directory is found.
     */

    protected function getImportForm()
    {
        $event = $this->getEvent();
        $dirPath = $this->getDirPath();

        if (is_dir($dirPath) && is_writable($dirPath)) {
            $new = array_diff_key($this->getUploaded(false), $this->getInstalled());

            if ($new) {
                asort($new);

                return n
                    .tag_start('form', array(
                        'id'     => $event.'_import_form',
                        'name'   => $event.'_import_form',
                        'method' => 'post',
                        'action' => 'index.php',
                    ))
                    .tag(gTxt('import_from_disk'), 'label', array('for' => $event.'_import'))
                    .popHelp($event.'_import')
                    .selectInput('skins', $new, '', false, false, 'skins')
                    .eInput($this->getEvent())
                    .sInput('import')
                    .fInput('submit', '', gTxt('import'))
                    .n
                    .tag_end('form');
            }
        } else {
            return n
                .graf(
                    span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                    gTxt('path_not_writable', array('{list}' => $this->getDirPath())),
                    array('class' => 'alert-block warning')
                );
        }
    }

    /**
     * Get the class related Admin\Paginator instance.
     *
     * @return object Admin\Paginator instance.
     */

    protected function getPaginator()
    {
        return \Txp::get('\Textpattern\Admin\Paginator', $this->getEvent(), '');
    }

    /**
     * Get the class related Search\Filter instance.
     *
     * @param  array  $methods Available search methods.
     * @return object          Search\Filter instance.
     */

    protected function getSearchFilter($methods)
    {
        return \Txp::get('Textpattern\Search\Filter', $this->getEvent(), $methods);
    }

    /**
     * Get the button to create a new skin.
     *
     * @return HTML Link.
     */

    protected function getCreateButton()
    {
        $event = $this->getEvent();

        return sLink($event, 'edit', gTxt('create_skin'), 'txp-button');
    }

    /**
     * Get the Admin\Table $content block.
     *
     * @param  array $data compact('offset', 'limit', 'total', 'criteria', 'crit', 'search_method', 'page', 'sort', 'dir')
     * @return HTML        Skin list.
     */

    protected function getContentBlock($data)
    {
        extract($data);

        $event = $this->getEvent();
        $sortSQL = $sort.' '.$dir;
        $switchDir = ($dir == 'desc') ? 'asc' : 'desc';

        if ($total < 1) {
            if ($crit !== '') {
                $out = graf(
                    span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                    gTxt('no_results_found'),
                    array('class' => 'alert-block information')
                );
            } else {
                $out = graf(
                    span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                    gTxt('no_'.$event.'_recorded'),
                    array('class' => 'alert-block information')
                );
            }

            return $out
                   .n.tag_end('div') // End of .txp-layout-1col.
                   .n.'</div>';      // End of .txp-layout.
        }

        $rs = $this->getTableData($criteria, $sortSQL, $offset, $limit);
        $numThemes = mysqli_num_rows($rs);

        if ($rs) {
            $dev_preview = has_privs('skin.edit');
            $out = n.tag_start('form', array(
                        'class'  => 'multi_edit_form',
                        'id'     => $event.'_form',
                        'name'   => 'longform',
                        'method' => 'post',
                        'action' => 'index.php',
                    )).
                    n.tag_start('div', array(
                        'class'      => 'txp-listtables',
                        'tabindex'   => 0,
                        'aria-label' => gTxt('list'),
                    )).
                    n.tag_start('table', array('class' => 'txp-list')).
                    n.tag_start('thead');

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
                          .($thVal !== $thId ? ' '.$event.'_detail' : '');

                $ths .= column_head($thVal, $thId, $event, true, $switchDir, $crit, $search_method, $thClass);
            }

            $out .= tr($ths)
                .n.tag_end('thead')
                .n.tag_start('tbody');

            while ($a = nextRow($rs)) {
                extract($a, EXTR_PREFIX_ALL, $event);

                $editUrl = array(
                    'event'         => $event,
                    'step'          => 'edit',
                    'name'          => $skin_name,
                    'sort'          => $sort,
                    'dir'           => $dir,
                    'page'          => $page,
                    'search_method' => $search_method,
                    'crit'          => $crit,
                );

                $tdAuthor = txpspecialchars($skin_author);
                empty($skin_author_uri) or $tdAuthor = href($tdAuthor.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), $skin_author_uri, array(
                    'rel'    => 'external noopener',
                    'target' => '_blank',
                ));

                $tds = td(fInput('checkbox', 'selected[]', $skin_name), '', 'txp-list-col-multi-edit')
                    .hCell(
                        href(txpspecialchars($skin_name), $editUrl, array(
                            'title'      => gTxt('edit'),
                            'aria-label' => gTxt('edit'),
                        )).
                        ($numThemes > 1 ? ' | '.href(gTxt('assign_sections'), 'index.php?event=section&step=section_select_skin&skin='.urlencode($skin_name)).
                        (${$event.'_section_count'} > 0 ? sp.tag(gTxt('status_in_use'), 'small', array('class' => 'alert-block alert-pill success')) :
                            (${$event.'_dev_section_count'} > 0 ? sp.tag(gTxt('status_in_use'), 'small', array('class' => 'alert-block alert-pill warning')) : '')
                        ) : ''),
                        '', array(
                            'scope' => 'row',
                            'class' => 'txp-list-col-name',
                        )
                    )
                    .td(txpspecialchars($skin_title), '', 'txp-list-col-title')
                    .td(txpspecialchars($skin_version), '', 'txp-list-col-version')
                    .td($tdAuthor, '', 'txp-list-col-author');

                $countNames = array('section', 'page', 'form', 'css');

                foreach ($countNames as $name) {
                    if (${$event.'_'.$name.'_count'} > 0) {
                        if ($name === 'section') {
                            $linkParams = array(
                                'event'         => 'section',
                                'search_method' => $event,
                                'crit'          => '"'.$skin_name.'"',
                            );
                        } else {
                            $linkParams = array(
                                'event' => $name,
                                $event  => $skin_name,
                            );
                        }

                        $tdVal = href(
                            ${$event.'_'.$name.'_count'},
                            $linkParams,
                            array(
                                'title'      => gTxt($event.'_count_'.$name, array('{num}' => ${$event.'_'.$name.'_count'})),
                                'aria-label' => gTxt($event.'_count_'.$name, array('{num}' => ${$event.'_'.$name.'_count'})),
                            )
                        );
                    } else {
                        $tdVal = 0;
                    }

                    $tds .= td($tdVal, '', 'txp-list-col-'.$name.'_count');
                }

                $out .= tr($tds, array('id' => $this->getTable().'_'.$skin_name));
            }

            return $out
                   .n.tag_end('tbody')
                   .n.tag_end('table')
                   .n.tag_end('div')
                   .n.self::getMultiEditForm($page, $sort, $dir, $crit, $search_method)
                   .tInput()
                   .n.tag_end('form');
        }
    }

    /**
     * Get the Admin\Table $foot block.
     *
     * @param  array $data compact('limit', 'numPages', 'total', 'crit', 'search_method', 'page', 'sort', 'dir')
     * @return HTML        Multi-edit form, pagination and navigation form.
     */

    protected function getFootBlock($data)
    {
        extract($data);

        return $this->getPaginator()->render()
               .nav_form($this->getEvent(), $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);
    }

    /**
     * Get a multi-edit checkbox.
     *
     * @param  string $label The textpack related string to use.
     * @return HTML
     */

    protected function getMultiEditCheckbox($label)
    {
        return checkbox2('sync', get_pref($label, true), 0, 'sync')
               .n.tag(gTxt($label), 'label', array('for' => 'sync'))
               .popHelp($label);
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

    protected function getMultiEditForm($page, $sort, $dir, $crit, $search_method)
    {
        $methods = array(
            'import'    => array(
                'label' => gTxt('update_from_disk'),
                'html'  => $this->getMultiEditCheckbox('skin_delete_from_database'),
            ),
            'export'    => array(
                'label' => gTxt('export_to_disk'),
                'html'  => $this->getMultiEditCheckbox('skin_delete_from_disk'),
            ),
            'duplicate' => gTxt('duplicate'),
            'delete'    => array(
                'label' => gTxt('delete'),
                'html'  => $this->getMultiEditCheckbox('skin_delete_entirely'),
            ),
        );

        return multi_edit($methods, $this->getEvent(), 'multi_edit', $page, $sort, $dir, $crit, $search_method);
    }

    /**
     * Get the edit form.
     *
     * @param  mixed $message
     * @return HTML
     */

    protected function getEditForm($message = '')
    {
        global $step;

        $event = $this->getEvent();

        require_privs($event.'.edit');

        !$message or pagetop(gTxt('tab_'.$event), $message);

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

            $caption = gTxt('edit_'.$event);
            $extraAction = href(
                '<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'),
                '#',
                array(
                    'class'     => 'txp-clone',
                    'data-form' => $event.'_form',
                )
            );
        } else {
            $rs = array_fill_keys($fields, '');
            $caption = gTxt('create_'.$event);
            $extraAction = '';
        }

        extract($rs, EXTR_PREFIX_ALL, $event);
        pagetop(gTxt('tab_'.$event));

        $content = hed($caption, 2);

        foreach ($fields as $field) {
            $current = ${$event.'_'.$field};

            if ($field === 'description') {
                $input = text_area($field, 0, 0, $current, $event.'_'.$field);
            } elseif ($field === 'name') {
                $input = fInput(
                    'text',
                    array(
                        'name'      => $field,
                        'maxlength' => '63',
                    ), $current, '', '', '', INPUT_REGULAR, '', $event.'_'.$field, '', true
                );
            } elseif ($field === 'author_uri') {
                $input = fInput('url', $field, $current, '', '', '', INPUT_REGULAR, '', $event.'_'.$field, '', '', 'http(s)://');
            } else {
                $input = fInput('text', $field, $current, '', '', '', INPUT_REGULAR, '', $event.'_'.$field);
            }

            $content .= inputLabel($event.'_'.$field, $input, $event.'_'.$field, $event.'_'.$field);
        }

        $content .= pluggable_ui($event.'_ui', 'extend_detail_form', '', $rs)
            .graf(
                $extraAction.
                sLink($event, '', gTxt('cancel'), 'txp-button')
                .fInput('submit', '', gTxt('save'), 'publish'),
                array('class' => 'txp-edit-actions')
            )
            .eInput($event)
            .sInput('save')
            .hInput('old_name', $skin_name)
            .hInput('old_title', $skin_title)
            .hInput('search_method', $search_method)
            .hInput('crit', $crit)
            .hInput('page', $page)
            .hInput('sort', $sort)
            .hInput('dir', $dir);

        return form($content, '', '', 'post', 'txp-edit', '', $event.'_form');
    }
}

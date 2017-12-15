<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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
 * Main
 *
 * Manages skins and their assets.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Main extends SharedBase implements MainInterface
    {

        /**
         * {@inheritdoc}
         */

        protected static $table = 'txp_skin';

        /**
         * {@inheritdoc}
         */

        protected static $tableCols;

        /**
         * Caches whether the skin is used by any section.
         *
         * @var bool
         * @see      skinIsInUse()
         */

        protected $inUse = array();

        /**
         * Skin main file.
         *
         * @var array
         */

        protected static $file = 'manifest.json';

        /**
         * Parsed skins related assets.
         *
         * @var array
         * @see       setSkinsAssets(), setSkins().
         */

        protected $skinsAssets;

        /**
         * Skins default assets.
         *
         * @var array
         * @see       getDefaultAssets().
         */

        protected static $defaultAssets = array(
            'pages'  => array(array()),
            'forms'  => array(array()),
            'styles' => array(array()),
        );

        /**
         * Constructor.
         *
         * @param mixed $skins  Skin names;
         * @param mixed $assets $skins parallel array of skins related assets and
         *                      their related templates grouped by types.
         *                      If no defined type apply, just nest the templates array
         *                      into another one which simulates a abstract group.
         *                      All assets templates by default.
         * @see                 getSkinsAssets(), getDefaultAssets().
         */

        public function __construct($skins = null, $assets = null)
        {
            $skins ? $this->setSkinsAssets($skins, $assets) : '';
        }

        /**
         * {@inheritdoc}
         */

        public function setSkinsAssets($skins, $assets = null)
        {
            is_string($skins) ? $skins = array($skins) : '';

            $skins = array_map('self::sanitize', $skins);

            $this->inUse = array();

            if ($assets === null) {
                $globalAssets = self::getDefaultAssets();
            } elseif ($assets) {
                if (is_string($assets)) {
                    // $assets = 'pages';
                    $globalAssets = array($assets => array(array()));
                } elseif (isset($assets[0]) && is_string($assets[0])) {
                    // $assets = array('pages', 'forms');
                    $globalAssets = array_fill_keys($assets, array(array()));
                } elseif (!isset($assets[0])) {
                    // $assets = array('pages' => array('default'));
                    $globalAssets = $assets;
                }
            } else {
                $globalAssets = array(array());
            }

            if (isset($globalAssets)) {
                $this->skinsAssets = array();

                foreach ($skins as $skin) {
                    $this->skinsAssets[$skin] = $globalAssets;
                }
            } else {
                $this->skinsAssets = array_combine($skins, $assets);
            }

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function getSkinsAssets()
        {
            return $this->skinsAssets;
        }

        /**
         * {@inheritdoc}
         */

        public static function getDefaultAssets()
        {
            return static::$defaultAssets;
        }

        /**
         * {@inheritdoc}
         */

        public static function getfile()
        {
            return static::$file;
        }

        /**
         * {@inheritdoc}
         */

        protected function parseRows($rows)
        {
            return array_key_exists('title', $rows) ? array($rows) : $rows;
        }

        /**
         * {@inheritdoc}
         */

        public function create($rows, $from = false)
        {
            callback_event('skin.create', '', 1, self::getSkinsAssets());

            $rows = $this->parseRows($rows);
            $tableCols = self::getTableCols();

              $failed
            = $alreadyExists
            = $passed
            = $passedFrom
            = $set
            = $install
            = array();

            $hasAssets = false;

            foreach (self::getSkinsAssets() as $skin => $assets) {
                if (self::isInstalled($skin)) {
                    $failed[$skin] = $alreadyExists[$skin] = '';
                } elseif (in_array($skin, array_keys(self::getDirectories()))) {
                    $failed[$skin] = $alreadyExists[self::getPath($skin)] = '';
                } else {
                    $passed[$skin] = array_merge_recursive($assets, self::getDefaultAssets());

                    (!$hasAssets && $passed[$skin]) ? $hasAssets = true : false;
                    $from ? $passedFrom[] = $from[$this->getSkinIndex($skin)] : '';
                    $row = array_merge(array('name' => $skin), $rows[$this->getSkinIndex($skin)]);
                    $install[$skin] = $row['title'];
                    $sqlVal = array();

                    foreach ($tableCols as $col) {
                        $sqlVal[] = "'".doSlash($row[$col])."'";
                    }

                    $set[] = '('.implode(', ', $sqlVal).')';
                }
            }

            if ($passed) {
                if ($this->insert($tableCols, $set)) {
                    self::setInstalled($install);

                    if ($hasAssets) {
                        if ($from) {
                            $succeeded = $this->doAssets(
                                $passedFrom,
                                array_values($passed),
                                'duplicate',
                                array(array_keys($passed))
                            );
                        } else {
                            $succeeded = $this->doAssets(
                                array_keys($passed),
                                array_values($passed),
                                'create'
                            );
                        }
                    }

                    if ($succeeded) {
                        $this->setResults('skin_created', $succeeded, 'success');

                        update_lastmod('skin.create', $succeeded);
                        callback_event('skin.create', 'success', 0, $succeeded);
                    }
                } else {
                    $notCreated = $passed;
                    $failed = array_merge($failed, $passed);
                    $passed = array();
                }
            }

            if ($failed) {
                if ($alreadyExists) {
                    $this->setResults('skin_already_exists', $alreadyExists);
                }

                if ($notCreated) {
                    $this->setResults('skin_creation_failed', $notCreated);
                }

                callback_event('skin.create', 'failure', 0, $failed);
            }

            callback_event('skin.create', '', 0, self::getSkinsAssets());

            return $passed;
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate()
        {
            $from = $to = $rows = array();

            $rs = $this->getRows();

            foreach (self::getSkinsAssets() as $skin => $assets) {
                $row = $rs[$skin];
                $from[] = $row['name'];
                $to[] = $row['name'].'_copy';

                unset($row['name']);

                $row['title'] .= ' (copy)';
                $rows[] = $row;
            }

            $this->setSkinsAssets($to)
                 ->create($rows, $from);
        }

        /**
         * {@inheritdoc}
         */

        public function edit($rows)
        {
            callback_event('skin.edit', '', 1, self::getSkinsAssets());

            $rows = $this->parseRows($rows);
            $tableCols = self::getTableCols();

            $passed = $failed = $unknown = $installed = array();

            foreach (self::getSkinsAssets() as $skin => $assets) {
                $row = $rows[$this->getSkinIndex($skin)];

                if (!self::isInstalled($skin)) {
                    $failed[$skin] = $unknown[$skin] = '';
                } elseif ($skin !== $row['name'] && self::isInstalled($row['name'])) {
                    $failed[$row['name']] = $installed[$row['name']] = '';
                } else {
                    $sqlVal = array();

                    foreach ($tableCols as $col) {
                        $sqlVal[] = $col." = '".doSlash($row[$col])."'";
                    }

                    if ($this->updateRow($skin, implode(', ', $sqlVal))) {
                        $path = self::getPath($skin);

                        if (file_exists($path) && !@rename($path, self::getPath($row['name']))) {
                            $this->setResults('path_renaming_failed', $skin, 'warning');
                        }

                        $passed[$skin] = $assets;
                        $to[] = $row['name'];
                    } else {
                        $failed[$skin] = $notUpdated[$skin] = '';
                    }
                }
            }

            if ($passed) {
                $this->setSkinsAssets($to);

                foreach (self::getSkinsAssets() as $skin => $assets) {
                    $this->isInUse($skin) ? $this->updateSkinInUse($skin, $passed[array_search($skin, $passed)]) : '';
                }

                $suceeded = $this->doAssets(
                    $to,
                    array_values($passed),
                    'adopt',
                    array(array_keys($passed))
                );

                if ($suceeded) {
                    $this->setResults('skin_updated', $suceeded, 'success');

                    update_lastmod('skin.edit', $suceeded);
                    callback_event('skin.edit', 'success', 0, $suceeded);
                }
            }

            if ($failed) {
                if ($unknown) {
                    $this->setResults('skin_unknown', $unknown);
                }

                if ($installed) {
                    $this->setResults('skin_already_exists', $installed);
                }

                if ($notUpdated) {
                    $this->setResults('skin_update_failed', $notUpdated);
                }

                callback_event('skin.edit', 'failure', 0, $failed);
            }

            callback_event('skin.edit', '', 0, self::getSkinsAssets());

            return $passed;
        }

        /**
         * Updates a skin row.
         *
         * @param  string $skin A skin name;
         * @param  string $set  SQL set clause;
         * @return bool         false on error.
         */

        protected function updateRow($skin, $set)
        {
            return safe_update(self::$table, $set, "name = '".doSlash($skin)."'");
        }

        /**
         * Updates a skin name in use.
         *
         * @param string $to   A skin newname.
         * @param string $from A skin oldname.
         */

        protected function updateSkinInUse($to, $from = null)
        {
            $updated = safe_update(
                'txp_section',
                "skin = '".doSlash($to)."'",
                ($from === null) ? '1=1' : "skin = '".doSlash($from)."'"
            );

            if (!$updated) {
                $this->setResults(
                    'skin_related_sections_update_failed',
                    array('{name}' => $to),
                    'warning'
                );
            }

            return $updated;
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = true, $override = false)
        {
            callback_event('skin.import', '', 1, self::getSkinsAssets());

            $tableCols = self::getTableCols();

              $failed
            = $unknown
            = $alreadyExists
            = $notWritable
            = $unreadable
            = $unlockable
            = $passed
            = $passedRows
            = $set
            = $install
            = array();

            foreach (self::getSkinsAssets() as $skin => $assets) {
                if (!$override && self::isInstalled($skin)) {
                    $failed[$skin] = $unknown[$skin] = '';
                } elseif ($override && !self::isInstalled($skin)) {
                    $failed[$skin] = $alreadyExists[$skin] = '';
                } elseif (!self::isWritable($skin)) {
                    $failed[$skin] = $notWritable[self::getPath($skin)] = '';
                } elseif (!self::isReadable($skin.'/'.self::getfile())) {
                    $failed[$skin] = $unreadable[self::getPath($skin.'/'.self::getFile())] = '';
                } elseif (!$this->lock($skin)) {
                    $failed[$skin] = $unlockable[$skin] = '';
                } else {
                    $row = $this->getJSONInfos($skin);

                    if (!$row) {
                        $failed[$skin] = $invalid[$skin] = '';

                        if (!$this->unlock($skin)) {
                            $failed[$skin] = $stillLocked[$skin] = $assets;
                        }
                    } else {
                        $passed[$skin] = array_merge_recursive($assets, self::getDefaultAssets());

                        $row['name'] = $skin;
                        isset($row['title']) ?: $row['title'] = $skin;
                        $sqlVal = array();

                        $install[$skin] = $row['title'];

                        foreach ($tableCols as $col) {
                            $sqlVal[] = "'".doSlash(isset($row[$col]) ? $row[$col] : '')."'";
                        }

                        $set[] = '('.implode(', ', $sqlVal).')';
                    }
                }
            }

            if ($passed) {
                if ($this->insert($tableCols, $set, $override)) {
                    self::setInstalled($install);

                    $succeeded = $this->doAssets(
                        array_keys($passed),
                        array_values($passed),
                        'import',
                        array(($override ? $clean : false), $override)
                    );

                    if ($succeeded) {
                        $this->setResults('skin_imported', $succeeded, 'success');

                        update_lastmod('skin.import', $succeeded);
                        callback_event('skin.import', 'success', 0, $succeeded);
                    }

                    $stillLocked = array();

                    foreach ($passed as $skin => $assets) {
                        if (!$this->unlock($skin)) {
                            $failed[$skin] = $stillLocked[$skin] = $assets;
                        }
                    }
                } else {
                    $failed = array_merge($failed, $passed);
                    $notImported = $passed;
                    $passed = array();
                }
            }

            if ($failed) {
                if ($unknown) {
                    $this->setResults('skin_unknown', $unknown);
                } elseif ($alreadyExists) {
                    $this->setResults('skin_already_exists', $alreadyExists);
                }

                if ($notWritable) {
                    $this->setResults('path_not_writable', $notWritable);
                }

                if ($unreadable) {
                    $this->setResults('path_not_readable', $unreadable);
                }

                if ($unlockable) {
                    $this->setResults('skin_dir_locking_failed', $unlockable);
                }

                if ($invalid) {
                    $this->setResults('invalid_file', $invalid);
                }

                if ($notImported) {
                    $this->setResults('skin_import_failed', $notImported);
                }

                if ($stillLocked) {
                    $this->setResults('skin_unlocking_failed', $stillLocked);
                }

                callback_event('skin.import', 'failure', 0, $failed);
            }

            callback_event('skin.import', '', 0, self::getSkinsAssets());

            return $out;
        }

        /**
         * Gets and decodes a skin 'manifest.json' file contents.
         *
         * @return array Associative array of skin infos.
         */

        protected function getJSONInfos($skin)
        {
            return @json_decode(
                file_get_contents(self::getPath($skin.'/'.self::getfile())),
                true
            );
        }

        /**
         * Gets a skin row from the DB.
         *
         * @return array Associative array of skins and their templates rows
         *               as usual associative arrays.
         */

        protected function getRows($skins = null)
        {
            $skins === null ? $skins = $this->getSkins() : '';

            $rows = safe_rows_start(
                implode(', ', self::getTableCols()),
                self::$table,
                "name IN ('".implode("', '", array_map('doSlash', $skins))."')"
            );

            if ($rows) {
                $skinRows = array();

                while ($row = nextRow($rows)) {
                    $skinRows[$row['name']] = $row;
                }
            }

            return $skinRows;
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = true)
        {
            callback_event('skin.export', '', 1, $this->skins);

              $failed
            = $unwritable
            = $unlockable
            = $notExported
            = $passed
            = array();

            foreach (self::getSkinsAssets() as $skin => $assets) {
                if (!self::isValidName($skin)) {
                    $failed[$kin] = $invalid[$skin] = '';
                } elseif (!self::isWritable($skin) && !self::mkDir($skin)) {
                    $failed[$skin] = $unwritable[$skin] = '';
                } elseif (!$this->lock($skin)) {
                    $failed[$skin] = $unlockable[$skin] = '';
                } else {
                    $passed[$skin] = $assets;
                }
            }

            $rows = $this->getRows(array_keys($passed));

            if (!$rows) {
                $failed = array_merge($failed, $passed);
                $unknown = $passed;
            } else {
                $skinAssets = $passed;
                $passed = array();
                $hasAssets = false;

                foreach ($skinAssets as $skin => $assets) {
                    if (!$rows[$skin]) {
                        $failed[$skin] = $unknown[$skin] = '';
                    } elseif (!$this->exportSkin($rows[$skin])) {
                        $failed[$skin] = $notExported[$skin] = '';
                    } else {
                        $passed[$skin] = $assets;
                        $assets ? $hasAssets = true : '';
                    }
                }

                if ($passed) {
                    if ($hasAssets) {
                        $succeeded = $this->doAssets(
                            array_keys($passed),
                            array_values($passed),
                            'export',
                            array($clean)
                        );
                    } else {
                        $succeeded = $passed;
                    }

                    if ($succeeded) {
                        $this->setResults('skin_exported', $succeeded, 'success');

                        update_lastmod('skin.export', $succeeded);
                        callback_event('skin.export', 'success', 0, $succeeded);
                    }

                    foreach ($passed as $skin => $assets) {
                        if (!$this->unlock($skin)) {
                            $failed[$skin] = $stillLocked[$skin] = $assets;
                        }
                    }
                }
            }

            if ($failed) {
                if ($invalid) {
                    $this->setResults('skin_unsafe_name', $invalid);
                }

                if ($unknown) {
                    $this->setResults('skin_unknown', $unknown);
                }

                if ($unwritable) {
                    $this->setResults('dir_creation_or_writing_failed', $unwritable);
                }

                if ($unlockable) {
                    $this->setResults('skin_locking_failed', $unlockable);
                }

                if ($notExported) {
                    $this->setResults('skin_export_failed', $notExported);
                }

                callback_event('skin.export', 'failure', 0, $failed);
            }

            callback_event('skin.export', '', 0, self::getSkinsAssets());

            return $out;
        }

        /**
         * Exports the skin row by creating or editing the 'manifest.json' file contents.
         *
         * @param  array $row A skin row as an associative array.
         * @return bool       false on error.
         */

        protected function exportSkin($row)
        {
            $path = $row['name'].'/'.self::getfile();
            $contents = self::isWritable($path) ? $this->getJSONInfos($row['skin']) : array();

            if (array_key_exists('name', $row)) {
                unset($row['name']);
            }

            $contents['title'] = $row['title'] ? $row['title'] : $row['name'];
            $contents['txp-type'] = 'textpattern-theme';

            foreach ($row as $field => $value) {
                $value ? $contents[$field] = $value : '';
            }

            return $this->filePutJsonContents($path, $contents);
        }

        /**
         * Creates/overrides the Manifest file.
         *
         * @param  array $path     A skin directory name;
         * @param  array $contents The 'manifest.json' file contents as an associative array.
         * @return bool            false on error.
         */

        protected function filePutJsonContents($path, $contents)
        {
            return (bool) file_put_contents(
                self::getPath($path),
                JSONPrettyPrint(json_encode($contents, TEXTPATTERN_JSON))
            );
        }

        /**
         * {@inheritdoc}
         */

        public function delete()
        {
            callback_event('skin.delete', '', 1, self::getSkinsAssets());

              $failed
            = $unknown
            = $inUse
            = $passed
            = array();

            foreach (self::getSkinsAssets() as $skin => $assets) {
                if (!self::isInstalled($skin)) {
                    $failed[$skin] = $isUnknown[$skin] = '';
                } elseif ($this->isInUse($skin)) {
                    $failed[$skin] = $inUse[$skin] = '';
                } else {
                    $passed[$skin] = $assets;
                }
            }

            if ($passed) {
                $succeeded = $this->doAssets(
                    array_keys($passed),
                    array_values($passed),
                    'delete'
                );

                if ($succeeded) {
                    $succeededSkins = array_keys($succeeded);

                    if ($this->deleteSkins($succeededSkins)) {
                        self::unsetInstalled($succeededSkins);
                        self::setCurrent();

                        $this->setResults('skin_deleted', $succeeded, 'success');

                        update_lastmod('skin.delete', $succeeded);
                        callback_event('skin.delete', 'success', 0, $succeeded);
                    } else {
                        $failed = array_merge($failed, $passed);
                        $notDeleted = $passed;
                        $passed = array();
                    }
                }
            }

            if ($failed) {
                if ($unknown) {
                    $this->setResults('skin_unknown', $unknown);
                }

                if ($inUse) {
                    $this->setResults('skin_in_use', $inUse);
                }

                if ($notDeleted) {
                    $this->setResults('skin_deletion_failed', $notDeleted);
                }

                callback_event('skin.delete', 'failure', 0, $failed);
            }

            callback_event('skin.delete', '', 0, self::getSkinsAssets());

            return $passed;
        }

        /**
         * Deletes skin rows from the DB.
         *
         * @param  array $passed Skin names to delete.
         * @return bool          false on error.
         */

        protected function deleteSkins($passed)
        {
            return safe_delete(
                self::$table,
                "name IN ('".implode("', '", array_map('doSlash', $passed))."')"
            );
        }

        /**
         * {@inheritdoc}
         */

        public function isInUse($skin)
        {
            if (!in_array($skin, $this->inUse)) {
                if (safe_column("name", 'txp_section', "skin ='".doSlash($skin)."'")) {
                    $this->inUse[] = $skin;
                }
            }

            return in_array($skin, $this->inUse);
        }

        /**
         * {@inheritdoc}
         */

        public static function getCurrent()
        {
            return get_pref('skin_editing', 'default', true);
        }

        /**
         * {@inheritdoc}
         */

        public static function setCurrent($skin = null)
        {
            $skin ?: $skin = safe_field('skin', 'txp_section', 'name = "default"');

            return set_pref('skin_editing', $skin, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
        }

        /**
         * Calls an asset class instance method.
         *
         * @param  array  $skins  Skins to work with;
         * @param  array  $assets Skins related assets;
         * @param  string $method An asset class related method;
         * @param  array  $args   An array of arguments to pass to the method;
         * @return bool
         */

        protected function doAssets($skins, $assets, $method, $extra = null)
        {
            $out = true;

            $assetTemplates = array();

            foreach ($skins as $i => $skin) {
                foreach ($assets[$i] as $asset => $templates) {
                    $assetTemplates[$asset][$skin] = array_unique($templates);
                }
            }

            $passed = $skins;

            foreach ($assetTemplates as $asset => $skinTemplates) {
                $instance = \Txp::get('Textpattern\Skin\\'.ucfirst($asset));

                $skins = array_keys($skinTemplates);

                $instance->setSkinsTemplates($skins, array_values($skinTemplates));
                $instance->locked = $this->locked;

                if ($extra !== null) {
                    $failed = array_diff(
                        $skins,
                        call_user_func_array(array($instance, $method), $extra)
                    );
                } else {
                    $failed = array_diff($skins, $instance->$method());
                }

                $passed = array_diff($passed, $failed);

                $errors = $instance->getResults(array('warning', 'error'), 'raw');

                if ($errors) {
                    is_array($this->results) ?: $this->results = array();
                    $this->results = array_merge_recursive($this->results, $errors);
                }
            }

            return array_fill_keys($passed, '');
        }

        public static function renderCreateButton()
        {
            return sLink('skin', 'edit', gTxt('create_skin'), 'txp-button');
        }

        /**
         * Renders the skin import form.
         *
         * @return html The form or a message if no new skin directory is found.
         */

        public static function renderImportForm()
        {
            $new = self::getNewDirectories();

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
         * Renders the .txp-control-panel div.
         *
         * @return html div containing the 'Create' button and the import form..
         */

        public static function renderCreateBlock()
        {
            return tag(
                self::renderCreateButton()
                .self::renderImportForm(),
                'div',
                array('class' => 'txp-control-panel')
            );
        }

        /**
         * Gets an array of the available skin directories.
         *
         * Skin directories name must be in lower cases
         * and sanitized for URL to appears in the select list.
         * Theses directories also need to contain a manifest.json file
         * to get the skin title from the 'name' JSON field.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function getDirectories()
        {
            if (static::$directories === null) {
                $skins = self::getRecDirIterator();
                static::$directories = array();

                foreach ($skins as $skin) {
                    $name = basename($skin->getPath());

                    if (self::isValidName($name)) {
                        $infos = $skin->getTemplateJSONContents();
                        $infos ? static::$directories[$name] = $infos['title'] : '';
                    }
                }
            }

            return static::$directories;
        }

        /**
         * {@inheritdoc}
         */

        public static function getRecDirIterator()
        {
            return new RecIteratorIterator(
                new RecRegexIterator(
                    new RecDirIterator(get_pref('skin_base_path')),
                    '/^manifest\.json/i'
                ),
                1
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
                self::getDirectories(),
                self::getInstalled()
            );
        }

        /**
         * Gets an array of the installed skins.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function unsetInstalled($skins)
        {
            static::$installed = array_diff_key(
                self::getInstalled(),
                array_fill_keys($skins, '')
            );
        }

        /**
         * Gets an array of the installed skins.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function renderSwitchForm($event, $step, $current)
        {
            $installed = self::getInstalled();

            if ($installed) {
                return form(
                    inputLabel(
                        'skin',
                        selectInput('skin', self::getInstalled(), $current, false, 1, 'skin'),
                        'skin'
                    )
                    .eInput($event)
                    .sInput($step),
                    '',
                    '',
                    'post'
                );
            }

            return;
        }
    }
}

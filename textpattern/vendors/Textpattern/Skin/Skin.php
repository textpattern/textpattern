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
 * Skin
 *
 * Manages a skin and its assets — pages, forms and styles by default.
 *
 * <code>
 * Txp::get('Textpattern\Skin\Skin', abc_skin)->import();
 * </code>
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Skin extends SkinBase implements SkinsInterface, SkinInterface
    {

        /**
         * Skin table name.
         *
         * @var array
         */

        protected static $table = 'txp_skin';

        /**
         * Skin asset related table names.
         *
         * @var array
         */

        protected static $assetTables = array('txp_page', 'txp_form', 'txp_css');

        /**
         * Skin main file.
         *
         * @var array
         */

        protected static $file = 'manifest.json';

        /**
         * Default skin assets.
         *
         * @var array
         */

        protected static $defaultAssets = array(
            'pages'  => array(),
            'forms'  => array(),
            'styles' => array(),
        );

        /**
         * Constructor.
         *
         * @param string $skin The skin name (set the related parent property);
         */

        public function __construct($skin = null)
        {
            parent::__construct($skin);
        }

        /**
         * Parses the $assets constructor argument.
         *
         * @param string|array $assets See __construct()
         * @return array Associative array of assets and their relative templates.
         */

        private function parseAssets($assets = null)
        {
            if ($assets === null) {
                $assets = static::$defaultAssets;
            } elseif ($assets) {
                if (!is_array($assets)) {
                    $assets = array($assets);
                }

                if (isset($assets[0])) {
                    $assets = array_fill_keys($assets, array());
                }
            }

            return $assets;
        }

        /**
         * {@inheritdoc}
         */

        public function create($row, $assets = null)
        {
            $assets = $this->parseAssets($assets);

            $callback_extra = array(
                'row'    => $row,
                'assets' => $assets,
            );

            callback_event('skin', 'creation', 0, $callback_extra);

            $this->setSkin($row['name']);

            if ($this->skinIsInstalled()) {
                $this->setResults(
                    gtxt('skin_already_exists', array('{name}' => $this->skin))
                );

                callback_event('skin', 'creation_failed', 0, $callback_extra);

                return false;
            }

            $row['title'] ?: $row['title'] = $row['name'];

            if (!$this->doSkin('insert', $row)) {
                $this->setResults(
                    gtxt('skin_step_failure', array(
                        '{skin}' => $this->skin,
                        '{step}' => 'import',
                    ))
                );

                callback_event('skin', 'creation_failed', 0, $callback_extra);

                return false;
            }

            $this->isInstalled = true;

            $assetsCreated = $assets ? $this->callAssetsMethod($assets, 'create') : true;

            if ($assetsCreated === false) {
                callback_event('skin', 'creation_failed', 0, $callback_extra);

                return false;
            }

            $this->setResults(gtxt('skin_created'), false);

            callback_event('skin', 'created', 0, $callback_extra);
        }

        /**
         * {@inheritdoc}
         */

        public function edit($row)
        {
            $callback_extra = array(
                'skin'   => $this->skin,
                'row'    => $row,
            );

            callback_event('skin', 'edit', 0, $callback_extra);

            if (!$this->skinIsInstalled()) {
                $this->setResults(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );

                callback_event('skin', 'edit_failed', 0, $callback_extra);

                return false;
            }

            if ($this->skin !== $row['name'] && self::isInstalled($row['name'])) {
                $this->setResults(
                    gtxt('skin_already_exists', array('{name}' => $this->skin))
                );

                callback_event('skin', 'edit_failed', 0, $callback_extra);

                return false;
            }

            if (!$this->doSkin('update', $row)) {
                $this->setResults(
                    gtxt('skin_step_failure', array(
                        '{skin}' => $this->skin,
                        '{step}' => 'edit',
                    ))
                );

                callback_event('skin', 'edit_failed', 0, $callback_extra);

                return false;
            }

            if ($row['name'] !== $this->skin) {
                $path = $this->getPath();

                if (file_exists($path)) {
                    rename($path, self::getBasePath().'/'.$row['name']);
                }

                $old_name = $this->skin;

                $this->setSkin($row['name']);

                $this->isInUse() ? $this->updateSkinInUse($old_name) : '';

                $assetsAdopted = $this->callAssetsMethod($this->parseAssets(), 'adopt', $old_name);

                if ($assetsAdopted === false) {
                    callback_event('skin', 'edit_failed', 0, $callback_extra);

                    return false;
                }
            }

            callback_event('skin', 'edited', 0, $callback_extra);

            $this->setResults(gtxt('skin_edited'), false);
        }

        /**
         * Updates a skin name in use.
         *
         * @param string $from the skin name in use.
         */

        public function updateSkinInUse($from = null)
        {
            $where = ($from === null) ? '1=1' : "skin = '".doSlash($from)."'";

            return safe_update(
                'txp_section',
                "skin = '".doSlash($this->skin)."'",
                $where
            );
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = true, $assets = null)
        {
            $assets = $this->parseAssets($assets);

            $callback_extra = array(
                'skin'   => $this->skin,
                'clean'  => $clean,
                'assets' => $assets,
            );

            callback_event('skin', 'import', 0, $callback_extra);

            if ($this->skinIsInstalled()) {
                $this->setResults(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );

                callback_event('skin', 'import_failed', 0, $callback_extra);

                return false;
            }

            if (!$this->isWritable()) {
                $this->setResults(
                    gtxt('skin_dir_must_be_writable', array('{name}' => $this->skin))
                );

                callback_event('skin', 'import_failed', 0, $callback_extra);

                return false;
            }

            if (!$this->isReadable(static::$file)) {
                $this->setResults(
                    gtxt('unredable_skin_file', array('{name}' => static::$file))
                );

                callback_event('skin', 'import_failed', 0, $callback_extra);

                return false;
            }

            $this->lockSkin();

            $row = $this->getJSONInfos();
            $row['name'] = $this->skin;

            if (!$this->doSkin('insert', $row)) {
                $this->unlockSkin();
                $this->setResults(
                    gtxt('skin_step_failure', array(
                        '{skin}' => $this->skin,
                        '{step}' => 'import',
                    ))
                );

                callback_event('skin', 'import_failed', 0, $callback_extra);

                return false;
            }

            $this->isInstalled = true;

            $assetsImported = $assets ? $this->callAssetsMethod($assets, 'import', $clean) : true;

            $this->unlockSkin();

            if ($assetsImported === false) {
                callback_event('skin', 'import_failed', 0, $callback_extra);

                return false;
            }

            $this->setResults(gtxt('skin_imported'), false);

            callback_event('skin', 'imported', 0, $callback_extra);
        }

        /**
         * Gets and decodes the Manifest file contents.
         *
         * @return array
         * @throws \Exception
         */

        public function getJSONInfos()
        {
            $default = array(
                'title'       => $this->skin,
                'version'     => '',
                'description' => '',
                'author'      => '',
                'author_uri'  => '',
            );

            $infos = @json_decode(
                file_get_contents($this->getPath(static::$file)),
                true
            );

            if ($infos) {
                return array_merge($default, $infos);
            }

            throw new \Exception(
                gtxt('invalid_json_file', array('{name}' => self::$file))
            );
        }

        /**
         * Inserts, updates or deletes a skin row.
         *
         * @param  string $method 'insert'|'update'|'delete';
         * @param  string $row    The skin related DB row as an associative array;
         * @return bool
         */

        public function doSkin($method, $row = null)
        {
            if ($row) {
                extract($row);

                $set = "name = '".doSlash($name)."',
                        title = '".doSlash($title)."',
                        version = '".doSlash($version)."',
                        description = '".doSlash($description)."',
                        author = '".doSlash($author)."',
                        author_uri = '".doSlash($author_uri)."'
                       ";
            }

            switch ($method) {
                case 'insert':
                    $out = safe_insert(self::$table, $set);
                    break;
                case 'update':
                    $out = safe_update(self::$table, $set, "name = '".doSlash($this->skin)."'");
                    break;
                case 'delete':
                    $out = safe_delete(self::$table, "name = '".doSlash($this->skin)."'");
                    break;
                default:
                    throw new \Exception('unknown_method');
                    break;
            }

            return (bool) $out;
        }

        /**
         * {@inheritdoc}
         */

        public function update($clean = true, $assets = null)
        {
            $assets = $this->parseAssets($assets);

            $callback_extra = array(
                'skin'   => $this->skin,
                'clean'  => $clean,
                'assets' => $assets,
            );

            callback_event('skin', 'update', 0, $callback_extra);

            if (!$this->skinIsInstalled()) {
                $this->setResults(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );

                callback_event('skin', 'update_failed', 0, $callback_extra);

                return false;
            }

            if (!$this->isWritable()) {
                $this->setResults(
                    gtxt('skin_dir_must_be_writable', array('{name}' => $this->skin))
                );

                callback_event('skin', 'update_failed', 0, $callback_extra);

                return false;
            }

            if (!$this->isReadable(static::$file)) {
                $this->setResults(
                    gtxt('unredable_skin_file', array('{name}' => static::$file))
                );

                callback_event('skin', 'update_failed', 0, $callback_extra);

                return false;
            }

            $this->lockSkin();

            $row = $this->getJSONInfos();
            $row['name'] = $this->skin;

            if (!$this->doSkin('update', $row)) {
                $this->unlockSkin();
                $this->setResults(
                    gtxt('skin_step_failure', array(
                        '{skin}' => $this->skin,
                        '{step}' => 'update',
                    ))
                );

                callback_event('skin', 'update_failed', 0, $callback_extra);

                return false;
            }

            $assetsUpdated = $assets ? $this->callAssetsMethod($assets, 'update', $clean) : true;

            $this->unlockSkin();

            if ($assetsUpdated === false) {
                callback_event('skin', 'update_failed', 0, $callback_extra);

                return false;
            }

            $this->setResults(gtxt('skin_updated'), false);

            callback_event('skin', 'updated', 0, $callback_extra);
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate($assets = null)
        {
            $row = $this->getRow();

            $row['name'] .= '_copy';
            $row['title'] .= ' (copy)';

            if (strlen($row['name']) <= 63) {
                $this->duplicateAs($row);
            } else {
                $this->setResults(
                    gtxt('skin_name_would_be_too_long', array('{name}' => $name))
                );

                callback_event('skin', 'duplication_failed', 0, $callback_extra);

                return false;
            }
        }

        /**
         * {@inheritdoc}
         */

        public function duplicateAs($row, $assets = null)
        {
            $assets = $this->parseAssets($assets);

            $callback_extra = array(
                'skin'   => $this->skin,
                'row'    => $row,
                'assets' => $assets,
            );

            callback_event('skin', 'duplication', 0, $callback_extra);

            if (self::isInstalled($row['name'])) {
                $this->setResults(
                    gtxt('skin_already_exists', array('{name}' => $row['name']))
                );

                callback_event('skin', 'duplication_failed', 0, $callback_extra);

                return false;
            }

            $row['title'] ?: $row['title'] = $row['name'];

            if (!$this->skinIsInstalled()) {
                $this->setResults(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );

                callback_event('skin', 'duplication_failed', 0, $callback_extra);

                return false;
            }

            if ($this->doSkin('insert', $row)) {
                static::$installed[$row['name']] = $row['name'];

                $assets ? $this->callAssetsMethod($assets, 'duplicate', $row['name']) : '';

                callback_event('skin', 'duplicated', 0, $callback_extra);

                $this->setResults(gtxt('skin_duplicated'), false);
            } else {
                callback_event('skin', 'duplication_failed', 0, $callback_extra);

                $this->setResults(
                    gtxt('skin_step_failure', array(
                        '{skin}' => $this->skin,
                        '{step}' => 'duplication',
                    ))
                );

                return false;
            }
        }

        /**
         * Gets the skin row from the database.
         *
         * @throws \Exception
         */

        public function getRow()
        {
            $row = safe_row('*', self::$table, 'name = "'.doSlash($this->skin).'"');

            if ($row) {
                return $row;
            }

            throw new \Exception(
                gtxt('skin_data_not_found', array('{name}' => $this->skin))
            );
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = true, $assets = null)
        {
            $assets = $this->parseAssets($assets);

            $callback_extra = array(
                'skin'   => $this->skin,
                'clean'  => $clean,
                'assets' => $assets,
            );

            callback_event('skin', 'export', 0, $callback_extra);

            $row = $this->getRow();

            if (!$row) {
                $this->setResults(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );

                callback_event('skin', 'export_failed', 0, $callback_extra);

                return false;
            }

            if (!$this->isWritable() && !$this->mkDir()) {
                $this->setResults(
                    gtxt(
                        'unable_to_write_or_create_skin_directory',
                        array('{name}' => $this->skin)
                    )
                );

                callback_event('skin', 'export_failed', 0, $callback_extra);

                return false;
            }

            $this->lockSkin();

            $rowExported = $this->exportSkin($row);

            if (!$rowExported) {
                $this->unlockSkin();
                $this->setResults(
                    gtxt('skin_step_failure', array(
                        '{skin}' => $this->skin,
                        '{step}' => 'export',
                    ))
                );

                callback_event('skin', 'export_failed', 0, $callback_extra);

                return false;
            }

            $assetsExported = $assets ? $this->callAssetsMethod($assets, 'export', $clean) : true;

            $this->unlockSkin();

            if ($assetsExported === false) {
                callback_event('skin', 'export_failed', 0, $callback_extra);

                return false;
            }

            $this->setResults(gtxt('skin_exported'), false);

            callback_event('skin', 'exported', 0, $callback_extra);
        }

        /**
         * Exports the skin row by creating or editing the Manifest file contents.
         *
         * @param  array $row Skin row as an associative array
         * @return bool  False on error
         */

        public function exportSkin($row)
        {
            extract($row);

            $contents = $this->isWritable(static::$file) ? $this->getJSONInfos() : array();

            $contents['title'] = $title ? $title : $name;
            $contents['txp-type'] = 'textpattern-theme';
            $version ? $contents['version'] = $version : '';
            $description ? $contents['description'] = $description : '';
            $author ? $contents['author'] = $author : '';
            $author_uri ? $contents['author_uri'] = $author_uri : '';

            return (bool) $this->filePutJsonContents($contents);
        }

        /**
         * Creates/overrides the Manifest file.
         *
         * @param  array $contents The manifest file contents;
         * @return bool  False on error.
         */

        public function filePutJsonContents($contents)
        {
            return (bool) file_put_contents(
                $this->getPath(static::$file),
                JSONPrettyPrint(json_encode($contents, TEXTPATTERN_JSON))
            );
        }

        /**
         * {@inheritdoc}
         */

        public function delete()
        {
            $callback_extra = $this->skin;

            callback_event('skin', 'deletion', 0, $callback_extra);

            if (!$this->skinIsInstalled()) {
                $this->setResults(
                    gtxt('unknown_skin', array('{name}' => $this->skin))
                );

                callback_event('skin', 'deletion_failed', 0, $callback_extra);

                return false;
            }

            if ($this->isInUse()) {
                $this->setResults(
                    gtxt('skin_delete_failure', array(
                        '{name}' => $this->skin,
                        '{clue}' => 'skin in use',
                    ))
                );

                callback_event('skin', 'deletion_failed', 0, $callback_extra);

                return false;
            }

            if (count(Skins::getInstalled()) < 1) {
                $this->setResults(
                    gtxt('skin_delete_failure', array(
                        '{name}' => $this->skin,
                        '{clue}' => 'last skin',
                    ))
                );

                callback_event('skin', 'deletion_failed', 0, $callback_extra);

                return false;
            }

            $this->callAssetsMethod($this->parseAssets(), 'delete');

            if ($this->hasAssets()) {
                $this->setResults(
                    gtxt('skin_delete_failure', array(
                        '{name}' => $this->skin,
                        '{clue}' => 'still contains assets.',
                    ))
                );

                callback_event('skin', 'deletion_failed', 0, $callback_extra);

                return false;
            }

            if (!$this->doSkin('delete')) {
                $this->setResults(
                    gtxt('skin_step_failure', array(
                        '{skin}' => $this->skin,
                        '{step}' => 'deletion',
                    ))
                );

                callback_event('skin', 'deletion_failed', 0, $callback_extra);

                return false;
            }

            static::$installed = array_diff_key(
                static::$installed,
                array($this->skin => '')
            );

            self::setCurrent();

            callback_event('skin', 'deleted', 0, $callback_extra);

            $this->setResults(gtxt('skin_deleted'), false);
        }

        /**
         * {@inheritdoc}
         */

        public function isInUse()
        {
            if ($this->isInUse === null) {
                $this->isInUse = safe_column(
                    "name",
                    'txp_section',
                    "skin ='".doSlash($this->skin)."'"
                );
            }

            return $this->isInUse;
        }

        /**
         * Checks if any template belongs to the skin.
         *
         * @return bool
         * @throws \Exception
         */

        public function hasAssets()
        {
            $select = 'SELECT '.implode('.name, ', static::$assetTables).'.name';
            $from = ' FROM '.implode(', ', array_map('safe_pfx', static::$assetTables));
            $where = ' WHERE '
                     .implode(
                         '.skin = "'.doSlash($this->skin).'" AND ',
                         static::$assetTables
                     ).'.skin = "'.doSlash($this->skin).'"';

            $assets = safe_query($select.$from.$where);

            return @mysqli_num_rows($assets) > 0 ? true : false;
        }

        /**
         * Gets the skin set as the one selected in the admin tabs.
         *
         * @return string The skin name
         */

        public static function getCurrent()
        {
            return get_pref('skin_editing', 'default', true);
        }

        /**
         * Sets the skin as the one selected in the admin tabs.
         *
         * @param  string $skin A skin name.
         * @throws \Exception
         */

        public static function setCurrent($skin = null)
        {
            $skin ?: $skin = safe_field('skin', 'txp_section', 'name = "default"');

            if (set_pref('skin_editing', $skin, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE) === false) {
                throw new \Exception('unable_to_set_current_skin');
            }
        }

        /**
         * Calls an asset class instance method.
         *
         * @param  string $method An asset class related method;
         * @param  array  $args   An array to pass to the method;
         * @return false  on error
         */

        private function callAssetsMethod($assets, $method, $extra = null)
        {
            $failure = false;

            foreach ($assets as $asset => $templates) {
                $instance = \Txp::get('Textpattern\Skin\\'.ucfirst($asset), $this->skin);
                $instance->locked = $this->locked;

                if ($extra) {
                    $proceed = call_user_func_array(
                        array($instance, $method),
                        array($extra, $templates)
                    );
                } else {
                    $proceed = call_user_func(array($instance, $method), $templates);
                }

                if ($proceed === false) {
                    $this->results = array_merge($this->results, $instance->results);
                    $failure = true;
                }
            }

            if ($failure) {
                return false;
            }
        }
    }
}

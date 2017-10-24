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

    class Skin extends SkinBase implements SkinInterface
    {

        /**
         * Skin infos as an associative array.
         *
         * @var array
         */

        protected static $table = 'txp_skin';

        /**
         * Skin infos file.
         *
         * @var array
         */

        protected static $file = 'manifest.json';

        /**
         * Caches an associative array of assets and their related class instance.
         *
         * @var array
         */

        private $assets;

        /**
         * Constructor.
         *
         * @param string       $skin   The skin name (set the related parent property);
         * @param array        $infos  Skin infos (set the related parent property).
         * @param string|array $assets Asset, array of assets or associative array of asset(s)
         *                             and its/their related template(s) to work with
         *                             (Set the related property by caching the related instances).
         */

        public function __construct(
            $skin = null,
            $infos = null,
            $assets = array('pages', 'forms', 'styles')
        ) {
            parent::__construct($skin, $infos);

            if ($assets) {
                $assets = $this->parseAssets($assets);

                foreach ($assets as $asset => $templates) {
                    $this->assets[$asset] = \Txp::get(
                        'Textpattern\Skin\\'.ucfirst($asset),
                        $skin,
                        $infos,
                        $templates
                    );
                }
            }
        }

        /**
         * Parses the $assets constructor argument.
         *
         * @param string|array $assets See __construct()
         * @return array Associative array of assets and their relative templates.
         */

        private function parseAssets($assets)
        {
            if (!is_array($assets)) {
                $assets = array($assets);
            } elseif (isset($assets[0])) {
                $assets = array_fill_keys($assets, array());
            }

            return $assets;
        }

        /**
         * {@inheritdoc}
         */

        public function create()
        {
            if (!$this->skinIsInstalled()) {
                $callback_extra = array(
                    'skin'   => $this->skin,
                    'assets' => $this->assets,
                );

                callback_event('skin', 'creation', 0, $callback_extra);

                if ($this->createSkin()) {
                    $this->isInstalled = true;

                    $this->assets ? $this->callAssetsMethod(__FUNCTION__) : '';

                    callback_event('skin', 'created', 0, $callback_extra);
                } else {
                    callback_event('skin', 'creation_failed', 0, $callback_extra);

                    throw new \Exception(
                        gtxt(
                            'skin_step_failed',
                            array(
                                '{skin}' => $this->skin,
                                '{step}' => 'import',
                            )
                        )
                    );
                }
            } else {
                throw new \Exception('duplicated_skin');
            }
        }

        /**
         * Creates the skin row from $this->infos and some default values.
         *
         * @return bool False on error
         */

        public function createSkin()
        {
            extract($this->infos);

            return (bool) safe_insert(
                self::$table,
                "title = '".doSlash($title ? $title : $this->skin)."',
                 version = '".doSlash($version ? $version : '0.0.1')."',
                 description = '".doSlash($description)."',
                 author = '".doSlash($author ? $author : substr(cs('txp_login_public'), 10))."',
                 author_uri = '".doSlash($author_uri)."',
                 name = '".doSlash(strtolower(sanitizeForUrl($this->skin)))."'
                "
            );
        }

        /**
         * {@inheritdoc}
         */

        public function edit()
        {
            if ($this->skinIsInstalled()) {
                if ($this->skin === $this->infos['new_name'] || !self::isInstalled($this->infos['new_name'])) {
                    $sections = $this->isInUse();
                    $callback_extra = array(
                        'skin'   => $this->skin,
                        'assets' => $this->assets,
                    );

                    callback_event('skin', 'edit', 0, $callback_extra);

                    if ($this->editSkin()) {
                        $new = strtolower(sanitizeForUrl($this->infos['new_name']));

                        if (file_exists($path = $this->getPath())) {
                            rename($path, self::getBasePath().'/'.$new);
                        }

                        if ($sections) {
                            safe_update(
                                'txp_section',
                                "skin = '".doSlash($new)."'",
                                "skin = '".doSlash($this->skin)."'"
                            );
                        }

                        $this->assets ? $this->callAssetsMethod(__FUNCTION__) : '';
                        $this->skin = $this->infos['new_name'];

                        callback_event('skin', 'edited', 0, $callback_extra);
                    } else {
                        callback_event('skin', 'edit_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt(
                                'skin_step_failed',
                                array(
                                    '{skin}' => $this->skin,
                                    '{step}' => 'import',
                                )
                            )
                        );
                    }
                } else {
                    throw new \Exception('duplicated_skin');
                }
            } else {
                throw new \Exception('unknown_skin');
            }
        }

        /**
         * Updates the skin row from $this->infos.
         *
         * @return bool False on error
         */

        public function editSkin()
        {
            extract($this->infos);

            $update = (bool) safe_update(
                self::$table,
                "name = '".doSlash(strtolower(sanitizeForUrl($new_name)))."',
                 title = '".doSlash($title ? $title : $new_name)."',
                 version = '".doSlash($version)."',
                 description = '".doSlash($description)."',
                 author = '".doSlash($author)."',
                 author_uri = '".doSlash($author_uri)."'
                ",
                "name = '".doSlash($this->skin)."'"
            );

            return $update;
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = true)
        {
            if (!$this->skinIsInstalled()) {
                if ($this->isReadable(static::$file) && $this->lockSkin()) {
                    $callback_extra = array(
                        'skin'   => $this->skin,
                        'assets' => $this->assets,
                    );

                    callback_event('skin', 'import', 0, $callback_extra);

                    if ($this->importSkin()) {
                        $this->isInstalled = true;

                        $this->assets ? $this->callAssetsMethod(__FUNCTION__, func_get_args()) : '';

                        $this->unlockSkin();

                        callback_event('skin', 'imported', 0, $callback_extra);
                    } else {
                        $this->unlockSkin();

                        callback_event('skin', 'import_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt(
                                'skin_step_failed',
                                array(
                                    '{skin}' => $this->skin,
                                    '{step}' => 'import',
                                )
                            )
                        );
                    }
                }
            } else {
                throw new \Exception('Unknown_skin');
            }
        }

        /**
         * Get and decodes the Manifest file contents.
         *
         * @return array
         * @throws \Exception
         */

        public function getJSONInfos()
        {
            $infos = @json_decode(
                file_get_contents($this->getPath(static::$file)),
                true
            );

            if ($infos) {
                return $infos;
            }

            throw new \Exception(
                gtxt('invalid_skin_json_file', array('{file}' => self::$file))
            );
        }

        /**
         * Imports the skin into the database from the Manifest file contents.
         *
         * @return bool False on error
         */

        public function importSkin()
        {
            extract($this->getJSONInfos());

            return (bool) safe_upsert(
                self::$table,
                "title = '".doSlash(isset($title) ? $title : $this->skin)."',
                 version = '".doSlash(isset($version) ? $version : '')."',
                 description = '".doSlash(isset($description) ? $description : '')."',
                 author = '".doSlash(isset($author) ? $author : '')."',
                 author_uri = '".doSlash(isset($author_uri) ? $author_uri : '')."'
                ",
                "name = '".doSlash($this->skin)."'"
            );
        }

        /**
         * {@inheritdoc}
         */

        public function update($clean = true)
        {
            if ($this->skinIsInstalled()) {
                if ($this->isReadable(static::$file) && $this->lockSkin()) {
                    $callback_extra = array(
                        'skin'   => $this->skin,
                        'assets' => $this->assets,
                    );

                    callback_event('skin', 'update', 0, $callback_extra);

                    if ($this->importSkin()) {
                        $this->assets ? $this->callAssetsMethod(__FUNCTION__, func_get_args()) : '';

                        $this->unlockSkin();

                        callback_event('skin', 'updated', 0, $callback_extra);
                    } else {
                        $this->unlockSkin();

                        callback_event('skin', 'update_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt(
                                'skin_step_failed',
                                array(
                                    '{skin}' => $this->skin,
                                    '{step}' => 'update',
                                )
                            )
                        );
                    }
                } else {
                    throw new \Exception('Unknown_directory');
                }
            } else {
                throw new \Exception('Unknown_skin');
            }
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate($as)
        {
            $this->copy = $as;

            if (!$this->skinIsInstalled(true) && $this->copyIndexIsSafe()) {
                if ($row = $this->getRow()) {
                    $callback_extra = array(
                        'skin'   => $this->skin,
                        'copy'   => $this->copy,
                        'assets' => $this->assets,
                    );

                    callback_event('skin', 'duplication', 0, $callback_extra);

                    if ($this->duplicateSkin($row)) {
                        static::$installed[strtolower(sanitizeForUrl($this->copy))] = $this->copy;

                        $this->assets ? $this->callAssetsMethod(__FUNCTION__, func_get_args()) : '';

                        callback_event('skin', 'duplicated', 0, $callback_extra);
                    } else {
                        callback_event('skin', 'duplication_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt(
                                'skin_step_failed',
                                array(
                                    '{skin}' => $this->skin,
                                    '{step}' => 'duplication',
                                )
                            )
                        );
                    }
                } else {
                    throw new \Exception('Unknown_skin');
                }
            } else {
                throw new \Exception('Duplicated_skin');
            }
        }

        /**
         * Whether a skin copy name is safe to use in the name_skin index.
         *
         * @throws \Exception
         * @return bool true
         */

        private function copyIndexIsSafe()
        {
            $index = strtolower(sanitizeForUrl(substr($this->copy, 0, 50)));

            foreach (Main::getInstalled() as $name => $title) {
                if (substr($name, 0, 50) === $index) {
                    throw new \Exception('unsafe_skin_index');
                }
            }

            return true;
        }

        /**
         * Gets the skin row from the database.
         *
         * @throws \Exception
         */

        public function getRow()
        {
            if ($row = safe_row('*', self::$table, 'name = "'.doSlash($this->skin).'"')) {
                return $row;
            }

            throw new \Exception(
                gtxt('skin_not_found', array('{skin}' => $this->skin))
            );
        }

        /**
         * Duplicates the skin row.
         *
         * @param  array $row Skin row as an associative array
         * @return bool  False on error
         */

        public function duplicateSkin($row)
        {
            $sql = array();

            foreach ($row as $col => $value) {
                if ($col === 'name') {
                    $value = strtolower(sanitizeForUrl($this->copy));
                } elseif ($col === 'title') {
                    $value = $this->copy;
                }

                $sql[] = $col." = '".doSlash($value)."'";
            }

            return (bool) safe_insert(self::$table, implode(', ', $sql));
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = true, $as = null)
        {
            if ($row = $this->getRow()) {
                $as ? $this->copy = $as : '';

                $callback_extra = array(
                    'skin'   => $this->skin,
                    'copy'   => $this->copy,
                    'assets' => $this->assets,
                );

                callback_event('skin', 'export', 0, $callback_extra);

                if (($this->isWritable() || $this->mkDir()) && $this->lockSkin()) {
                    if ($this->exportSkin($row)) {
                        $this->assets ? $this->callAssetsMethod(__FUNCTION__, func_get_args()) : '';

                        $this->unlockSkin();

                        callback_event('skin', 'exported', 0, $callback_extra);
                    } else {
                        $this->unlockSkin();

                        callback_event('skin', 'export_failed', 0, $callback_extra);

                        throw new \Exception(
                            gtxt(
                                'skin_step_failed',
                                array(
                                    '{skin}' => $this->skin,
                                    '{step}' => 'export',
                                )
                            )
                        );
                    }
                }
            } else {
                throw new \Exception('Unknown_skin');
            }
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

            $contents['title'] = $this->copy ? $this->copy : ($title ? $title : $name);
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
                $this->JSONPrettyPrint(json_encode($contents, TEXTPATTERN_JSON)) // PHP 5.4+ => json_encode($contents, JSON_PRETTY_PRINT)
            );
        }

        /**
         * Replaces the JSON_PRETTY_PRINT flag in json_encode for PHP versions under 5.4.
         *
         * From https://stackoverflow.com/a/9776726
         *
         * @param  string $json The JSON contents to prettify;
         * @return string Prettified JSON contents.
         */

        public function JSONPrettyPrint($json)
        {
            $result = '';
            $level = 0;
            $in_quotes = false;
            $in_escape = false;
            $ends_line_level = null;
            $json_length = strlen($json);

            for ($i = 0; $i < $json_length; $i++) {
                $char = $json[$i];
                $new_line_level = null;
                $post = "";

                if ($ends_line_level !== null) {
                    $new_line_level = $ends_line_level;
                    $ends_line_level = null;
                }

                if ($in_escape) {
                    $in_escape = false;
                } elseif ($char === '"') {
                    $in_quotes = !$in_quotes;
                } elseif (! $in_quotes) {
                    switch ($char) {
                        case '}':
                        case ']':
                            $level--;
                            $ends_line_level = null;
                            $new_line_level = $level;
                            break;
                        case '{':
                        case '[':
                            $level++;
                        case ',':
                            $ends_line_level = $level;
                            break;
                        case ':':
                            $post = " ";
                            break;
                        case " ":
                        case "    ":
                        case "\n":
                        case "\r":
                            $char = "";
                            $ends_line_level = $new_line_level;
                            $new_line_level = null;
                            break;
                    }
                } elseif ($char === '\\') {
                    $in_escape = true;
                }

                if ($new_line_level !== null) {
                    $result .= "\n".str_repeat("    ", $new_line_level);
                }

                $result .= $char.$post;
            }

            return $result;
        }

        /**
         * {@inheritdoc}
         */

        public function delete()
        {
            if (!$this->skinIsInstalled()) {
                throw new \Exception('Unknown_skin');
            } elseif ($this->isInUse()) {
                throw new \Exception(gtxt('unable_to_delete_skin_in_use', array('{skin}' => $this->skin)));
            } elseif (count(Main::getInstalled()) < 1) {
                throw new \Exception('unable_to_delete_the_only_skin');
            } else {
                $callback_extra = array(
                    'skin'   => $this->skin,
                    'assets' => $this->assets,
                );

                callback_event('skin', 'deletion', 0, $callback_extra);

                $this->assets ? $this->callAssetsMethod(__FUNCTION__) : '';

                if ($this->hasAssets()) {
                    throw new \Exception('unable_to_delete_non_empty_skin');
                } elseif ($this->deleteSkin()) {
                    static::$installed = array_diff_key(
                        static::$installed,
                        array($this->skin => '')
                    );

                    self::getCurrent() === $this->skin ? self::setCurrent($this->skin) : '';

                    callback_event('skin', 'deleted', 0, $callback_extra);
                } else {
                    callback_event('skin', 'deletion_failed', 0, $callback_extra);

                    throw new \Exception(
                        gtxt(
                            'skin_step_failed',
                            array(
                                '{skin}' => $this->skin,
                                '{step}' => 'deletion',
                            )
                        )
                    );
                }
            }
        }

        /**
         * Deletes the skin row.
         *
         * @return bool  False on error.
         */

        public function deleteSkin()
        {
            return (bool) safe_delete(
                doSlash(self::$table),
                "name = '".doSlash($this->skin)."'"
            );
        }

        /**
         * {@inheritdoc}
         */

        public function isInUse()
        {
            if ($this->skin) {
                if ($this->isInUse === null) {
                    $this->isInUse = safe_column(
                        "name",
                        'txp_section',
                        "skin ='".doSlash($this->skin)."'"
                    );
                }

                return $this->isInUse;
            }

            throw new \Exception('undefined_skin');
        }

        /**
         * Checks if any template belongs to the skin.
         *
         * @return bool
         * @throws \Exception
         */

        public function hasAssets()
        {
            if ($this->skin) {
                $assets = safe_query(
                    'SELECT p.name, f.name, c.name
                     FROM '.safe_pfx('txp_page').' p,
                          '.safe_pfx('txp_form').' f,
                          '.safe_pfx('txp_css').' c
                     WHERE p.skin = "'.doSlash($this->skin).'" AND
                           f.skin = "'.doSlash($this->skin).'" AND
                           c.skin = "'.doSlash($this->skin).'"
                    '
                );

                return @mysqli_num_rows($assets) > 0 ? true : false;
            }

            throw new \Exception('undefined_skin');
        }

        /**
         * Gets the skin set as the one selected in the admin tabs.
         *
         * @return string The skin name
         */

        public static function getCurrent()
        {
            return get_pref('skin_editing', 'default');
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
         * @param string $method An asset class related method;
         * @param array  $args   An array to pass to the method;
         * @throws \Exception
         */

        private function callAssetsMethod($method, $args = array())
        {
            $exceptions = array();

            foreach ($this->assets as $asset => $instance) {
                try {
                    $instance->copy = $this->copy;
                    $instance->locked = $this->locked;
                    call_user_func_array(array($instance, $method), $args);
                } catch (\Exception $e) {
                    $exceptions[] = $e->getMessage();
                }
            }

            if ($exceptions) {
                $this->locked ? $this->unlockSkin() : '';

                foreach ($this->assets as $asset => $instance) {
                    $instance->copy = $this->copy;
                    $instance->locked = $this->locked;
                }

                throw new \Exception(implode(n, $exceptions));
            }
        }
    }
}

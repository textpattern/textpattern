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
 * Main
 *
 * Manages skins and their assets.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin\Main {

    class Multiple extends \Textpattern\Skin\Controller
    {
        /**
         * Assets related controllers.
         *
         * @see setAssets()
         */

        private $assets;

        /**
         * Constructor.
         *
         * @param array $model  Model instance;
         * @param array $pages  Page names to work with;
         * @param array $forms  Page names to work with;
         * @param array $styles Page names to work with.
         */

        public function __construct(Model $model, $names = null) {
            parent::__construct($model);

            $this->setAssets();
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
                $this->assets[] = \Txp::get(
                    'Textpattern\Skin\Asset\\'.$class.'\Controller',
                    \Txp::get('Textpattern\Skin\Asset\\'.$class.'\Model', $this->model, $assets)
                );
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
         * $assets property getter.
         */

        public function setNames($names)
        {
            $parsed = array();

            foreach ($names as $name) {
                $parsed[] = sanitizeForTheme($name);
            }

            $this->model->setNames($parsed);

            return $this;
        }

        /**
         * Processes multi-edit actions.
         */

        function multiEdit()
        {
            extract(psa(array(
                'edit_method',
                'selected',
                'clean',
            )));

            if (!$selected || !is_array($selected)) {
                return skin_list();
            }

            $this->setNames(ps('selected'));

            switch ($edit_method) {
                case 'export':
                    $this->export($clean);
                    break;
                case 'duplicate':
                    $this->duplicate();
                    break;
                case 'import':
                    $this->import($clean, true);
                    break;
                default: // delete.
                    $this->$edit_method();
                    break;
            }

            return $this;
        }

        /**
         * Duplicates skins.
         *
         * @return object $this.
         */

        public function duplicate()
        {
            $names = $this->model->getNames();

            callback_event('skin.duplicate', '', 1, array('names' => $names));

            $passed = array();

            foreach ($names as $name) {
                $this->model->setInfos($name);
                $copy = $name.'_copy';

                if (!$this->model->isInstalled()) {
                    $this->model->setResults('skin_unknown', $name);
                } elseif ($this->model->isInstalled($copy)) {
                    $this->model->setResults('skin_already_exists', $copy);
                } elseif (!$this->model->isDirWritable() && !$this->model->createDir()) {
                    $this->model->setResults('path_not_writable', $this->model->getDirPath());
                } elseif (!$this->model->lock()) {
                    $this->model->setResults('skin_dir_locking_failed', $name);
                } else {
                    $passed[] = $name;
                }

                $this->setNames($passed);
                $rows = $this->model->getRows();

                if (!$rows) {
                    $this->model->setResults('skin_unknown', $passed);
                } else {
                    foreach ($rows as $name => $infos) {
                        extract($infos);
                        $this->model->setInfos($copy, $title.' copy', $version, $description, $author, $author_uri);

                        if (!$this->model->createRow()) {
                            $this->model->setResults('skin_duplication_failed', $name);
                        } else {
                            $this->model::setInstalled(array($copy => $title.' copy'));
                            $this->model->setInfos($name);

                            foreach ($this->getAssets() as $asset) {
                                $assetModel = $asset->getModel();
                                $assetRows = $assetModel->getRows();

                                if (!$assetRows) {
                                    $this->model->setResults('no_found', array($skin => $this->model->getDirPath()));
                                } else {
                                    if ($assetModel->duplicateRowsTo($copy, $assetRows)) {
                                        $this->model->setResults($asset.'_duplication_failed', array($skin => $notImported));
                                    }
                                }
                            }
                        }
                    }

                    if ($this->model->islocked() && !$this->model->unlock()) {
                        $this->model->setResults('skin_unlocking_failed', $this->model->getDirPath());
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
            ps('skins') ? $this->model->setNames(array(ps('skins'))) : ''; // TODO

            $names = $this->model->getNames();

            callback_event('skin.import', '', 1, array('names' => $names));

            foreach ($names as $name) {
                $this->model->setInfos($name);

                if (!$override && $this->model->isInstalled()) {
                    $this->model->setResults('skin_unknown', $name);
                } elseif ($override && !$this->model->isInstalled()) {
                    $this->model->setResults('skin_already_exists', $name);
                } elseif (!$this->model->isDirWritable()) {
                    $this->model->setResults('path_not_writable', $this->model->getDirPath());
                } elseif (!$this->model->isFileReadable()) {
                    $this->model->setResults('path_not_readable', $this->model->getFilePath());
                } elseif (!$this->model->lock()) {
                    $this->model->setResults('skin_dir_locking_failed', $name);
                } else {
                    $skinInfos = $this->model->getFileContents();

                    if (!$skinInfos) {
                        $this->model->setResults('invalid_json', $this->model->getFilePath);
                    } else {
                        extract($skinInfos);

                        if (!$override && !$this->model->createRow($title, $version, $description, $author, $author_uri)) {
                            $this->model->setResults('skin_import_failed', $name);
                        } elseif ($override && !$this->model->updateRow($name, $title, $version, $description, $author, $author_uri)) {
                            $this->model->setResults('skin_import_failed', $name);
                        } else {
                            $this->model::setInstalled(array($name => $title));

                            foreach ($this->getAssets() as $asset) {
                                $asset->import($clean);
                            }
                        }
                    }
                }

                if ($this->model->islocked() && !$this->model->unlock()) {
                    $this->model->setResults('skin_unlocking_failed', $this->model->getDirPath());
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
            $names = $this->model->getNames();

            callback_event('skin.export', '', 1, array('names' => $names));

            foreach ($names as $name) {
                $this->model->setInfos($name);

                if (!$this->model::isValidDirName($name)) {
                    $this->model->setResults('skin_unsafe_name', $name);
                } elseif (!$this->model->isDirWritable() && !$this->model->createDir()) {
                    $this->model->setResults('path_not_writable', $this->model->getDirPath());
                } elseif (!$this->model->lock()) {
                    $this->model->setResults('skin_locking_failed', $name);
                } else {
                    $passed[] = $name;
                }
            }

            $rows = $this->model->setNames($passed)->getRows();

            if (!$rows) {
                $this->model->setResults('skin_unknown', $names);
            } else {
                foreach ($passed as $name) {
                    $this->model->setName($name);

                    extract($rows[$name]);

                    if (!$rows[$name]) {
                        $this->model->setResults('skin_unknown', $name);
                    } elseif (!$this->model->createFile($title, $version, $description, $author, $author_uri)) {
                        $this->model->setResults('skin_export_failed', $name);
                    } else {
                        foreach ($this->getAssets() as $asset) {
                            $asset->export($clean);
                        }
                    }

                    if ($this->model->islocked() && !$this->model->unlock()) {
                        $this->model->setResults('skin_unlocking_failed', $name);
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
            $names = $this->model->getNames();
var_dump($names);
            callback_event('skin.delete', '', 1, array('names' => $names));

            $passed = $failed = array();

            foreach ($names as $name) {
                $this->model->setInfos($name);

                if (!$this->model->isInstalled()) {
                    $failed[] = $name;
                    $this->model->setResults('skin_unknown', $name);
                } elseif ($this->model->getSections()) {
                    $failed[] = $name;
                    $this->model->setResults('skin_in_use', $name);
                } else {
                    $assetFailure = false;

                    foreach ($this->getAssets() as $asset) {
                        $assetModel = $asset->getModel();

                        if (!$assetModel->deleteRows()) {
                            $failed[] = $name;
                            $this->model->setResults($assetModel->getAsset().'_deletion_failed', $name);
                        }
                    }

                    $assetFailure ? $failed[] = $name : $passed[] = $name;
                }
            }

            if ($passed) {
                if ($this->setNames($passed) && $this->model->deleteRows()) {
                    $this->model::unsetInstalled($passed);

                    if (in_array(Model::getEditing(), $passed)) {
                        $this->model::resetEditing();
                    }

                    $this->model->setResults('skin_deleted', $passed, 'success');

                    update_lastmod('skin.delete', $passed);
                } else {
                    $this->model->setResults('skin_deletion_failed', $passed);
                }
            }

            callback_event('skin.delete', '', 0, array('names' => $names));

            return $this;
        }
    }
}

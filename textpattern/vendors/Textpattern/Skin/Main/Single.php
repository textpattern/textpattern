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

    class Single extends \Textpattern\Skin\Controller
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

        public function __construct(
            Model $model,
            $name = null,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null,
            $base = null
        ) {
            parent::__construct($model);

            if ($name) {
                $this->setInfos(
                    $name,
                    $title,
                    $version,
                    $description,
                    $author,
                    $author_uri
                );
            }

            $this->setAssets();
        }

        public function setInfos(
            $name,
            $title = null,
            $version = null,
            $description = null,
            $author = null,
            $author_uri = null
        ) {
            $this->model->setInfos(
                sanitizeForTheme($name),
                $title,
                $version,
                $description,
                $author,
                $author_uri
            );

            return $this;
        }

        public function setBase($name)
        {
            $this->model->setBase(sanitizeForTheme($name));

            return $this;
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
                    'Textpattern\Skin\Asset\\'.$class.'\Model',
                    $this->model,
                    $assets
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
         * Saves a skin.
         */

        public function save()
        {
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

            if (empty($name)) {
                $this->model->setResults('skin_name_invalid', $name);
            } elseif ($old_name) {
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

            return $this;
        }

        /**
         * Creates a skin and its essential asset templates.
         *
         * @param  string $assetsFrom
         * @return object $this.
         */

        public function create($assetsFrom = null) {
            $name = $this->model->getName();

            callback_event('skin.create', '', 1, array('name' => $name));

            if (empty($name)) {
                $this->model->setResults('skin_name_invalid', $name);
            } elseif ($this->model->isInstalled()) {
                $this->model->setResults('skin_already_exists', $name);
            } elseif ($this->model->DirExists()) {
                $this->model->setResults('skin_already_exists', $this->model->getDirPath());
            } elseif (!$this->model->CreateDir()) {
                $this->model->setResults('path_not_writable', $this->model->getDirPath());
            } elseif (!$this->model->lock()) {
                $this->model->setResults('skin_locking_failed', $this->model->getDirPath());
            } elseif (!$this->model->createRow()) {
                $this->model->setResults('skin_creation_failed', $name);
            } else {
                $failed = false;

                foreach ($this->getAssets() as $assetModel) {
                    if ($from && !$assetModel->duplicateRows($from) || !$from && !$assetModel->createRows()) {
                        $failed = true;

                        $this->model->setResults($assetModel->getAsset().'_creation_failed', $name);
                    }
                }

                if (!$this->model->unlock()) {
                    $this->model->setResults('skin_unlocking_failed', $name);
                } elseif (!$failed) {
                    $this->model->setResults('skin_created', $name, 'success');
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
            $name = $this->model->getName();
            $base = $this->model->getBase();

            callback_event('skin.update', '', 1, array('name' => $base));

            $updated = false;

            if (!$this->model->isInstalled($base)) {
                $this->model->setResults('skin_unknown', $base);
            } elseif ($base !== $name && $this->model->isInstalled()) {
                $this->model->setResults('skin_already_exists', $name);
            } elseif ($base !== $name && $this->model->dirExists()) {
                $this->model->setResults('skin_already_exists', $this->model->getDirPath());
            } elseif ($this->model->dirExists($base) && !$this->model->lock($base)) {
                $this->model->setResults('skin_dir_locking_failed', $base);
            } elseif (!$this->model->updateRow()) {
                $this->model->setResults('skin_update_failed', $base);
                $toUnlock = $base;
            } else {
                $updated = true;

                if ($this->model->dirExists($base) && !$this->model->renameDir($base)) {
                    $this->model->setResults('path_renaming_failed', $base, 'warning');
                } else {
                    $toUnlock = $name;
                }
            }

            if (isset($toUnlock) && !$this->model->unlock($toUnlock)) {
                $this->model->setResults('skin_unlocking_failed', $toUnlock);
            }

            if ($updated) {
                $this->model->getSections() ? $this->model->updateSections() : '';

                if ($this->model->getEditing() === $name) {
                    $this->model->setEditing();
                }

                foreach ($this->getAssets() as $assetModel) {
                    if (!$assetModel->updateSkin()) {
                        $this->model->setResults($assetModel->getAsset().'_update_failed', $base);
                    }
                }

                $this->model->setResults('skin_updated', $name, 'success');

                update_lastmod('skin.edit', $suceeded);
                callback_event('skin.edit', 'success', 0, $suceeded);
            }

            callback_event('skin.update', '', 0, array('name' => $base));

            return $this;
        }
    }
}

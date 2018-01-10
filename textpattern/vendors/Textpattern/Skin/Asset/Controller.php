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
 * Pages
 *
 * Manages skins related pages.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin\Asset {

    abstract class Controller extends \Textpattern\Skin\Controller
    {
        /**
         * {@inheritdoc}
         */

        public function __construct(Model $model)
        {
            parent::__construct($model);
        }

        /**
         * Imports templates.
         *
         * @param  bool   $clean    Whether to removes extra skin template rows or not;
         * @param  bool   $override Whether to insert or update the skins.
         * @return object $this.
         */

        public function import($clean = true, $override = false)
        {
            $skinModel = $this->model->getSkin();
            $skin = $skinModel->getName();
            $asset = $this->model::getAsset();
            $skinWasLocked = $skinModel->isLocked();

            // Works the skin if not already done.
            if (!$skinWasLocked) {
                if (!$skinModel->isInstalled()) {
                    $this->model->setResults('skin_unknown', $skin);
                } elseif (!$skinModel->isDirWritable()) {
                    $this->model->setResults('path_not_writable', $skinModel->getDirPath());
                } elseif ($skinModel->lock()) {
                    $this->model->setResults('skin_locking_failed', $skinModel->getDirPath());
                }
            }

            // Works with asset related templates once the skin locked.
            if ($skinModel->isLocked()) {
                if (!$this->model->isDirReadable()) {
                    $failed[$skin] = $notReadable[$skin] = $this->model->getDirPath();
                } else {
                    $files = $this->model->getFiles();
                    $files =
                    $imported = array();

                    if (!$files) {
                        $this->model->setResults('no_'.$asset.'_found', array($skin => $this->model->getDirPath()));
                    } else {
                        $imported = $this->importFiles($files);
                    }

                    $missing = array_diff($this->model::getEssential(), $imported);

                    if ($missing && !$this->model->setNames($missing)->createRows()) {

                    }
                }

                // Drops extra rows…
                if ($clean) {
                    $notCleaned = $this->model->cleanExtraRows($imported);
                    $this->model->setResults($asset.'_cleaning_failed', array($skin => $notCleaned));
                }

                // Unlocks the skin if needed.
                if ($skinWasLocked && !$skinModel->unlock()) {
                    $this->model->setResults('skin_unlocking_failed', array($skin => $skinModel->getDirPath()));
                }
            }

            return $this;
        }

        protected function importFiles($files)
        {
            $skinModel = $this->model->getSkin();
            $skin = $skinModel->getName();

            foreach ($files as $name => $contents) {
                if (!$override && !$this->model->setNames($names)->createRows($contents)) {
                    $this->model->setResults($asset.'_import_failed', array($skin => $notImported));
                } elseif ($override && !$this->model->setNames($names)->updateRows($contents)) {
                    $this->model->setResults($asset.'_import_failed', array($skin => $notImported));
                } else {
                    $imported = $name;
                }
            }

            return $imported;
        }

        /**
         * Exports skins.
         *
         * @param  bool   $clean Whether to removes extra skin template files or not.
         * @return object $this.
         */

        public function export($clean = true)
        {
            $skinModel = $this->model->getSkin();
            $skin = $skinModel->getName();
            $asset = $this->model::getAsset();
            $skinWasLocked = $skinModel->isLocked();

            // Works the skin if not already done.
            if (!$skinWasLocked) {
                if (!$skinModel->isInstalled()) {
                    $this->model->setResults('skin_unknown', $skin);
                } elseif (!$skinModel->isDirWritable() && !$skinModel->createDir()) {
                    $this->model->setResults('path_not_Writable', $skinModel->getDirPath());
                } elseif ($skinModel->lock()) {
                    $this->model->setResults('skin_locking_failed', $skinModel->getDirPath());
                }
            }

            // Works with asset related templates once the skin locked.
            if ($skinModel->isLocked()) {
                if (!$this->model->isDirWritable() && !$this->model->createDir()) {
                    $this->model->setResults('path_not_writable', array($skin => $this->model->getDirPath()));
                } else {
                    $rows = $this->model->getRows();

                    if (!$rows) {
                        $failed[$skin] = $empty[$skin] = $this->model->getDirPath();
                    } else {
                        $exported = $this->exportRows($rows);
                    }
                }

                // Drops extra files…
                if ($clean && isset($exported)) {
                    $notUnlinked = $this->model->cleanExtraFiles($exported);
                    $this->model->setResults($asset.'_cleaning_failed', array($skin => $notUnlinked));
                }

                // Unlocks the skin if needed.
                if ($skinWasLocked && !$skinModel->unlock()) {
                    $this->model->setResults('skin_unlocking_failed', array($skin => $skinModel->getDirPath()));
                }
            }

            return $this;
        }

        protected function exportRows($rows) {
            $skinModel = $this->model->getSkin();
            $skin = $skinModel->getName();
            $exported = array();

            foreach ($rows as $name => $contents) {
                if ($this->model->setName($name)->createFile($contents) === false) {
                    $this->model->setResults($asset.'_export_failed', array($skin => $name));
                } else {
                    $exported[] = $name;
                }
            }

            return $exported;
        }
    }
}

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

namespace Textpattern\Skin\Asset\Form {

    class Controller extends \Textpattern\Skin\Asset\Controller
    {
        protected function importFiles($files)
        {
            foreach ($files as $type => $templates) {
                foreach ($templates as $name => $contents) {
                    if (!$override && !$this->model->setNames($names)->createRows($contents)) {
                        $this->model->setResults($asset.'_import_failed', array($skin => $notImported));
                    } elseif ($override && !$this->model->setNames($names)->updateRows($contents)) {
                        $this->model->setResults($asset.'_import_failed', array($skin => $notImported));
                    } else {
                        $imported = $name;
                    }
                }
            }

            return $imported;
        }

        protected function exportRows($rows) {
            $exported = array();

            foreach ($rows as $type => $templates) {
                foreach ($templates as $name => $contents) {
                    if ($this->model->setName($name)->createFile($contents, $type) === false) {
                        $this->model->setResults($asset.'_export_failed', array($skin => $name));
                    } else {
                        $exported[] = $name;
                    }
                }
            }

            return $exported;
        }
    }
}

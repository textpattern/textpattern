<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Core.
 *
 * @since   4.7.0
 * @package DB
 */

namespace Textpattern\DB;

class Core
{
    /**
     * Textpattern table structure directory.
     *
     * @var string
     */

    protected $tables_dir;
    protected $tables_structure = array();

    protected $data_dir;

    /**
     * Constructor.
     *
     */

    public function __construct()
    {
        $this->tables_dir = dirname(__FILE__).DS.'Tables';
        $this->data_dir = dirname(__FILE__).DS.'Data';
    }

    private function getStructure()
    {
        if (empty($this->tables_structure)) {
            $this->tables_structure = get_files_content($this->tables_dir, 'table');
        }
    }

    public function createAllTables()
    {
        $this->getStructure();
        foreach ($this->tables_structure as $key=>$data) {
            safe_create($key, $data);
        }
    }

    public function importData()
    {
        foreach (get_files_content($this->data_dir, 'xml') as $key=>$data) {
            import_txp_xml($data, $key);
        }
    }


    public function getTablesName()
    {
        $this->getStructure();

        return array_keys($this->tables_structure);
    }

}

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

    abstract class Model
    {
        /**
         * Class related table.
         *
         * @var string Table name.
         * @see        getTable().
         */

        protected static $table;

        /**
         * Class related table columns.
         *
         * @var array Column names.
         * @see       getTableCols().
         */

        protected static $tableCols;

        /**
         * Class related asset names to work with.
         *
         * @var array Names.
         * @see       setNames(), getNames().
         */

        protected static $names;

        /**
         * Class related asset name to work with.
         *
         * @var string Name.
         * @see        setName(), getName().
         */

        protected static $name;

        /**
         * Stores action related results.
         *
         * @var array Associative array of 'success', 'warning' and 'error'
         *            textpack related items and their related '{list}' parameters.
         * @see       setResults(), getResults(), getMessage().
         */

        protected $results = array(
            'success' => array(),
            'warning' => array(),
            'error'   => array(),
        );

        /**
         * The valid skin directory name pattern.
         *
         * @var string Regex without delimiters.
         * @see        getNamePattern().
         */

        protected static $namePattern = '[a-zA-Z0-9_\-\.]{0,63}';

        /**
         * $table property getter.
         */

        public static function getTable()
        {
            return static::$table;
        }

        /**
         * $namePattern property getter
         */

        public static function getNamePattern()
        {
            return static::$namePattern;
        }

        /**
         * $tableCols property getter
         */

        public static function getTableCols()
        {
            static::$tableCols === null ? static::setTableCols() : '';

            return static::$tableCols;
        }

        /**
         * $tableCols property unsetter
         *
         * @param array $exclude Column names to exculde.
         */

        public static function setTableCols($exclude = array('lastmod'))
        {
            $query = safe_query('SHOW COLUMNS FROM '.safe_pfx(static::getTable()));

            static::$tableCols = array();

            while ($row = $query->fetch_assoc()) {
                if (!in_array($row['Field'], $exclude)) {
                    static::$tableCols[] = $row['Field'];
                }
            }

            return static::getTableCols();
        }

        /**
         * Gets the skin_base_path pref related value.
         *
         * @return string Path.
         */

        public static function getBasePath()
        {
            return get_pref('skin_base_path');
        }

        /**
         * Merges an action related result into the $results property.
         *
         * @param string $txtItem A textpack related item.
         * @param mixed  $list    A name or an array of names associated with the result.
         * @param string $status  'success'|'warning'|'error'.
         */

        public function setResults($txtItem, $list, $status = null)
        {
            is_string($list) ? $list = array($list) : '';
            $status = in_array($status, array('success', 'warning', 'error')) ? $status : 'error';

            $this->results = array_merge_recursive(
                $this->getResults(),
                array($status => array($txtItem => $list))
            );

            return $this;
        }

        /**
         * $results property getter.
         */

        public function getResults()
        {
            return $this->results;
        }

        /**
         * Gets the $results property related message to display in the UI.
         *
         * @return
         */

        public function getMessage()
        {
            $message = array();

            $thisResults = $this->getResults();

            foreach ($this->getResults() as $status => $results) {
                foreach ($results as $txtItem => $listGroup) {
                    $list = array();

                    if (count($listGroup) > 1) {
                        if (isset($listGroup[0])) {
                            $list = $listGroup;
                        } else {
                            foreach ($listGroup as $group => $names) {
                                $list[] = '('.$group.') '.implode(', ', $names).'; ';
                            }
                        }
                    } else {
                        $list = $listGroup;
                    }

                    $message[] = gTxt($txtItem, array('{list}' => implode(', ', $list)));
                }
            }

            $message = implode('<br>', $message);

            if ($thisResults['success'] && ($thisResults['warning'] || $thisResults['error'])) {
                $severity = 'E_WARNING';
            } elseif ($thisResults['warning']) {
                $severity = 'E_WARNING';
            } elseif ($thisResults['error']) {
                $severity = 'E_ERROR';
            } else {
                $severity = '';
            }

            return $severity ? array($message, constant($severity)) : $message;
        }
    }
}

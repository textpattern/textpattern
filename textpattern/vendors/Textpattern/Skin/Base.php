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

    abstract class Base
    {
        /**
         * Class related database table.
         *
         * @var string Table name.
         * @see        getTable().
         */

        protected static $table;

        /**
         * Class related textpack string.
         *
         * @var string 'skin', 'page', 'form', 'css', etc.
         * @see        getString().
         */

        protected static $string;

        /**
         * Skin/templates directory/files name(s) pattern.
         *
         * @var string Regex without delimiters.
         * @see        getNamePattern().
         */

        protected static $namePattern = '[a-zA-Z0-9_\-\.]{0,63}';

        /**
         * Class related skin/template names to work with.
         *
         * @var array Names.
         * @see       setNames(), getNames().
         */

        protected $names;

        /**
         * Skin/template name to work with.
         *
         * @var string Name.
         * @see        setName(), getName().
         */

        protected $name;

        /**
         * Skin/template name used as the base for update or duplication.
         *
         * @var string Name.
         * @see        setBase(), getBase().
         */

        protected $base;

        /**
         * Storage for admin related method results.
         *
         * @var array Associative array of 'success', 'warning' and 'error'
         *            textpack related items and their related '{list}' parameters.
         * @see       mergeResult(), getResults(), getMessage().
         */

        protected $results = array(
            'success' => array(),
            'warning' => array(),
            'error'   => array(),
        );

        /**
         * $table property getter.
         *
         * @return string static::$table Class related database table.
         */

        protected static function getTable()
        {
            return static::$table;
        }

        /**
         * $string property getter.
         */

        public static function getString()
        {
            return static::$string;
        }

        /**
         * $namePattern property getter
         *
         * @return string self::$namePattern Skin/templates directory/files name(s) pattern.
         */

        protected static function getNamePattern()
        {
            return self::$namePattern;
        }

        /**
         * Whether a skin directory name is valid or not.
         *
         * @param  string $name Skin name (uses the $name property value if null).
         * @return bool         false on error.
         * @see                 getName(), getNamePattern().
         */

        protected static function isValidDirName($name = null)
        {
            $name === null ? $name = $this->getName() : '';

            return preg_match('#^'.self::getNamePattern().'$#', $name);
        }

        abstract protected static function sanitizeName($name);

        /**
         * $names property setter.
         *
         * @param  array  $names Multiple skins or template names to work with related methods.
         * @return object $this  The current object (chainable).
         */

         public function setNames($names = null)
         {
             if ($names === null) {
                 $this->names = array();
             } else {
                 $parsed = array();

                 foreach ($names as $name) {
                     $parsed[] = static::sanitizeName($name);
                 }

                 $this->names = $parsed;
             }

             return $this;
         }

        /**
         * $names property getter.
         */

        protected function getNames()
        {
            return $this->names;
        }

        /**
         * $name property setter.
         *
         * @param  array  $name Single skin or template name to work with related methods.
         *                      Takes the 'last saved' or 'editing' related preference value if null.
         * @return object $this The current object (chainable).
         */

        public function setName($name = null)
        {
            $this->name = $name === null ? self::getEditing() : static::sanitizeName($name);

            return $this;
        }

        /**
         * $name property getter.
         */

        protected function getName()
        {
            return $this->name;
        }

        /**
         * $base property setter.
         *
         * @param object $this The current object (chainable).
         */

         public function setBase($name)
         {
             $this->base = static::sanitizeName($name);

             return $this;
         }

        /**
         * $base property getter.
         *
         * @return string Sanitized base name.
         */

        public function getBase()
        {
            return $this->base;
        }

        /**
         * Get the.
         *
         * @param string $name Skin name (uses the $name property value if null)
         * @see          getName(), getDirPath().
         */

        abstract protected function getSubdirPath($name = null);

        /**
         * Get the current  pref value.
         *
         * @return string Skin name.
         */

        abstract public static function getEditing();

        /**
         * Set the skin_editing pref value to $name property value.
         *
         * @return bool false on error.
         */

        abstract public function setEditing();

        /**
         * Get the 'remove_extra_templates' preference value.
         *
         * @return bool The current pref value.
         */

        protected function getCleaningPref() {
            return get_pref('remove_extra_templates', true);
        }

        /**
         * Switch the 'remove_extra_templates' preference value
         * and its related global variable.
         *
         * @return bool false on error.
         */

        protected function switchCleaningPref()
        {
            global $prefs;

            return set_pref(
                'remove_extra_templates',
                $prefs['remove_extra_templates'] = !$prefs['remove_extra_templates'],
                'skin',
                PREF_HIDDEN,
                'text_input',
                0,
                PREF_PRIVATE
            );
        }

        /**
         * Merge a result into the $results property array.
         *
         * @param string $txtItem A textpack item related to the what happened.
         * @param mixed  $list    A name or an array of names associated with the result
         *                        to build the txtItem related '{list}'.
         * @param string $status  'success'|'warning'|'error'.
         */

        protected function mergeResult($txtItem, $list, $status = null)
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
         *
         * @return $this->results Associative array of 'success', 'warning' and 'error'
         *                        textpack related items and their related '{list}' parameters.
         */

        protected function getResults()
        {
            return $this->results;
        }

        /**
         * Gets the $results property value as a message to pass to admin view.
         *
         * @return mixed Message or array containing the message and the related user notice constant.
         */

        protected function getMessage()
        {
            $message = array();

            $thisResults = $this->getResults();

            foreach ($this->getResults() as $status => $results) {
                foreach ($results as $txtItem => $listGroup) {
                    $list = array();

                    if (isset($listGroup[0])) {
                        $list = $listGroup;
                    } else {
                        foreach ($listGroup as $group => $names) {
                            if (count($listGroup) > 1) {
                                $list[] = '('.$group.') '.implode(', ', $names);
                            } else {
                                $list[] = implode(', ', $names);
                            }
                        }
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

        /**
         * $infos property getter/parser.
         *
         * @param  bool  $safe Whether to get the property value
         *                     as an SQL query related string or not.
         * @return mixed TODO
         */

        protected function getInfos($safe = false)
        {
            if ($safe) {
                $infoQuery = array();

                foreach ($this->infos as $col => $value) {
                    $infoQuery[] = $col." = '".doSlash($value)."'";
                }

                return implode(', ', $infoQuery);
            }

            return $this->infos;
        }

        /**
         * Create/CreateFrom a single skin (and its related assets)
         * or a single template from the $name (+ $skin) and $base property value(s).
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        abstract public function create();

        /**
         * Update a single skin (and its related dependencies)
         * or a single template from the $name (+ $skin) and $base property value(s).
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        abstract public function update();

        /**
         * Duplicate multiple skins (and their related $assets)
         * or multiple templates from the $names (+ $skin) property value(s).
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        abstract public function duplicate();

        /**
         * Import/Override (and clean) multiple skins (and their related $assets)
         * or multiple templates from the $names (+ $skin) property value(s).
         * Merges results in the related property.
         *
         * @param  bool   $clean    Whether to removes extra skin template rows or not;
         * @param  bool   $override Whether to insert or update the skins.
         * @return object $this     The current object (chainable).
         */

        abstract public function import($clean = false, $override = false);

        /**
         * Export (and clean) multiple skins (and their related $assets)
         * or multiple templates from the $names (+ $skin) property value(s).
         * Merges results in the related property.
         *
         * @param  bool   $clean Whether to removes extra skin template files or not;
         * @return object $this  The current object (chainable).
         */

        abstract public function export($clean = false, $override = false);

        /**
         * Delete multiple skins (and their related $assets + directories if empty)
         * or multiple templates from the $names (+ $skin) property value(s).
         * Merges results in the related property.
         *
         * @return object $this The current object (chainable).
         */

        abstract public function delete($clean = false);

        /**
         * Create/override a skin/asset file from the $infos property values.
         *
         * @return bool false on error.
         */

         abstract protected function createFile();

         /**
          * Insert a new row from the $infos (+ $skin) property values.
          *
          * @return bool false on error.
          */

         abstract protected function createRow();

         /**
          * Update the $base (+ $skin) property value(s) related row
          * with the $infos property related values.
          *
          * @return bool false on error.
          */

         abstract protected function updateRow();

         /**
          * Get a $name (+ $skin) property related row.
          *
          * @return array Associative array of the skin row fields and their values.
          * @see          getName().
          */

         abstract protected function getRow();

        /**
         * Get the $names (+ $skin) property value(s) related rows.
         *
         * @return array Associative array of skin names and their related infos.
         */

        abstract protected function getRows();

        /**
         * Delete the $names (+ $skin) property value(s) related rows.
         *
         * @return bool false on error.
         */

        abstract protected function deleteRows();
    }
}

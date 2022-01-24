<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * Common Interface
 *
 * Implemented by CommonBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin;

interface CommonInterface
{
    /**
     * $event property getter.
     *
     * @return string $this->event Class related textpack string (usually the event name).
     */

    public function getEvent();

    /**
     * $mimeTypes property getter.
     *
     * @return $this->mimeTypes The asset related mimeTypes array.
     */

    public function getMimeTypes();

    /**
     * $names property setter/sanitizer.
     *
     * @param  array $names Multiple skin or template names to work with related methods.
     * @return object $this  The current object (chainable).
     */

    public function setNames($names = null);

    /**
     * $name property setter.
     *
     * @param  array $name Single skin or template name to work with related methods.
     *                     Takes the '_last_saved' or '_editing' related preference
     *                     value if null.
     * @return object $this The current object (chainable).
     */

    public function setName($name = null);

    /**
     * $base property setter.
     *
     * @param object $this The current object (chainable).
     */

    public function setBase($name);

    /**
     * Get the current 'skin_editing' or '{asset}_last_saved' pref value.
     *
     * @return mixed Skin/template name | false on error.
     */

    public function getEditing();

    /**
     * Set the 'skin_editing' or '{asset}_last_saved' pref value
     * to the $name property value.
     *
     * @return bool false on error.
     */

    public function setEditing();

    /**
     * Get the $results property value as a message to display in the admin tabs.
     *
     * @return mixed Message or array containing the message
     *               and its related user notice constant.
     */

    public function getMessage();

    /**
     * Import/Override (and clean) multiple skins (and their related $assets)
     * or multiple templates from the $names (+ $skin) property value(s).
     * Merges results in the related property.
     *
     * @param  bool $sync     Whether to removes extra skin template rows or not;
     * @param  bool $override Whether to insert or update the skins.
     * @return object $this     The current object (chainable).
     */

    public function import($sync = false, $override = false);

    /**
     * Export (and clean) multiple skins (and their related $assets)
     * or multiple templates from the $names (+ $skin) property value(s).
     * Merges results in the related property.
     *
     * @param  bool $sync Whether to removes extra skin template files or not;
     * @return object $this The current object (chainable).
     */

    public function export($sync = false, $override = false);

    /**
     * Insert a row into the $table property value related table.
     *
     * @param  string $set   Optional SET clause.
     *                       Builds the clause from the $infos (+ $skin) property value(s) if null.
     * @param  bool   $debug Dump query
     * @return bool          FALSE on error.
     */

    public function createRow($set = null, $debug = false);

    /**
     * Update the $table property value related table.
     *
     * @param  string $set   Optional SET clause.
     *                       Builds the clause from the $infos property value if null.
     * @param  string $where Optional WHERE clause.
     *                       Builds the clause from the $base (+ $skin) property value(s) if null.
     * @param  bool   $debug Dump query
     * @return bool          FALSE on error.
     */

    public function updateRow($set = null, $where = null, $debug = false);

    /**
     * Get a row field from the $table property value related table.
     *
     * @param  string $thing Optional SELECT clause.
     *                       Uses 'name' if null.
     * @param  string $where Optional WHERE clause.
     *                       Builds the clause from the $name (+ $skin) property value(s) if null.
     * @param  bool   $debug Dump query
     * @return mixed         The Field or FALSE on error.
     */

    public function getField($thing = null, $where = null, $debug = false);

    /**
     * Delete rows from the $table property value related table.
     *
     * @param  string $where Optional WHERE clause.
     *                       Builds the clause from the $names (+ $skin) property value(s) if null.
     * @param  bool   $debug Dump query
     * @return bool          false on error.
     */

    public function deleteRows($where = null, $debug = false);

    /**
     * Count rows in the $table property value related table.
     *
     * @param  string $where The where clause.
     * @param  bool   $debug Dump query
     * @return mixed         Number of rows or FALSE on error
     */

    public function countRows($where = null, $debug = false);

    /**
     * Get a row from the $table property value related table as an associative array.
     *
     * @param  string $things Optional SELECT clause.
     *                        Uses '*' (all) if null.
     * @param  string $where  Optional WHERE clause.
     *                        Builds the clause from the $name (+ $skin) property value(s) if null.
     * @param  bool   $debug  Dump query
     * @return bool           Array.
     */

    public function getRow($things = null, $where = null, $debug = false);

    /**
     * Get rows from the $table property value related table as an associative array.
     *
     * @param  string $thing Optional SELECT clause.
     *                       Uses '*' (all) if null.
     * @param  string $where Optional WHERE clause (default: "name = '".doSlash($this->getName())."'")
     *                       Builds the clause from the $names (+ $skin) property value(s) if null.
     * @param  bool   $debug Dump query
     * @return array         (Empty on error)
     */

    public function getRows($things = null, $where = null, $debug = false);

    /**
     * $installed property getter.
     *
     * @return array $this->installed.
     */

    public function getInstalled();
}

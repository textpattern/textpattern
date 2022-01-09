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
 * Common Base
 *
 * Extended by Skin and AssetBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin;

abstract class CommonBase implements CommonInterface
{
    /**
     * Class related textpack string (usually the event name).
     *
     * @var string 'skin', 'page', 'form', 'css', etc.
     * @see getEvent().
     */

    protected $event;

    /**
     * Skin/templates directory/files name(s) pattern.
     *
     * @var string Regex without delimiters.
     * @see getNamePattern().
     */

    protected static $namePattern = '[a-zA-Z0-9_\-\.]{0,63}';

    /**
     * Installed.
     *
     * @var array Associative array of skin names and their titles.
     * @see setUploaded(), getUploaded().
     */

    protected $installed;

    /**
     * Class related skin/template names to work with.
     *
     * @var array Names.
     * @see setNames(), getNames().
     */

    protected $names;

    /**
     * Class related file extension.
     *
     * @see getExtension().
     */

    protected static $extension = 'txp';

    /**
     * Asset mimetypes.
     *
     * @var array Associative array of 'extension' => 'mediatype'
     */

    protected static $mimeTypes = array();

    /**
     * Skin/template name to work with.
     *
     * @var string Name.
     * @see setName(), getName().
     */

    protected $name;

    /**
     * Skin/template related infos.
     *
     * @var array Associative array of class related table main fields and their values.
     * @see setInfos(), getInfos().
     */

    protected $infos;

    /**
     * Skin/template name used as the base for update or duplication.
     *
     * @var string Name.
     * @see setBase(), getBase().
     */

    protected $base;

    /**
     * Storage for admin related method results.
     *
     * @var array Associative array of 'success', 'warning' and 'error'
     *            textpack related items and their related '{list}' parameters.
     * @see mergeResult(), getResults(), getMessage().
     */

    protected $results = array(
        'success' => array(),
        'warning' => array(),
        'error'   => array(),
    );

    /**
     * Constructor
     */

    public function __construct()
    {
        $this->setEvent();
    }

    /**
     * Get the class related Database table name
     *
     * @return string Table name.
     */

    protected function getTable()
    {
        return 'txp_'.$this->getEvent();
    }

    /**
     * $event property setter.
     *
     * @return $this The current object (chainable).
     */

    protected function setEvent()
    {
        $this->event = strtolower((new \ReflectionClass($this))->getShortName());

        return $this;
    }

    /**
     * $event property getter.
     *
     * @return string $this->event Class related textpack string (usually the event name).
     */

    public function getEvent()
    {
        return $this->event;
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
     * $mimeTypes property getter.
     *
     * @return $this->mimeTypes The asset related mimeTypes array.
     */

    public function getMimeTypes()
    {
        return static::$mimeTypes;
    }

    /**
     * Whether a $name property related value is a valid directory name or not.
     *
     * @return bool FALSE on error.
     */

    protected function isExportable($name = null)
    {
        return preg_match('#^'.self::getNamePattern().'$#', $this->getName());
    }

    /**
     * Sanitizes a string for use in a theme template's name.
     *
     * Just runs sanitizeForPage() followed by sanitizeForFile(),
     * then limits the number of characters to 63.
     *
     * @param  string $text The string
     * @return string
     */

    public static function sanitize($text)
    {
        $out = sanitizeForFile(sanitizeForPage($text));

        return \Txp::get('\Textpattern\Type\StringType', $out)->substring(0, 63)->getString();
    }

    /**
     * $names property setter/sanitizer.
     *
     * @param  array $names Multiple skin or template names to work with related methods.
     * @return object $this  The current class object (chainable).
     */

    public function setNames($names = null)
    {
        if ($names === null) {
            $this->names = array();
        } else {
            $parsed = array();

            foreach ($names as $name) {
                $parsed[] = static::sanitize($name);
            }

            $this->names = $parsed;
        }

        return $this;
    }

    /**
     * $names property getter.
     *
     * @return array Skin or template sanitized names.
     */

    protected function getNames()
    {
        return $this->names;
    }

    /**
     * $name property setter.
     *
     * @param  array $name Single skin or template name to work with related methods.
     *                     Takes the '_last_saved' or '_editing' related preference
     *                     value if null.
     * @return object $this The current class object (chainable).
     */

    public function setName($name = null)
    {
        $this->name = $name === null ? $this->getEditing() : static::sanitize($name);

        return $this;
    }

    /**
     * $name property getter.
     *
     * @return string Sanitized skin or template name.
     */

    protected function getName()
    {
        return $this->name;
    }

    /**
     * $infos property getter/parser.
     *
     * @param  bool $safe Whether to get the property value
     *                    as a safe SET query clause.
     * @return mixed       The $infos property value or the related SET clause.
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
     * $base property setter.
     *
     * @param object $this The current object (chainable).
     */

    public function setBase($name)
    {
        $this->base = static::sanitize($name);

        return $this;
    }

    /**
     * $base property getter.
     *
     * @return string Sanitized skin or template base name.
     */

    protected function getBase()
    {
        return $this->base;
    }

    /**
     * Get the 'synchronize' preference value.
     *
     * @param  string $name Pref name.
     * @return bool
     */

    protected function getSyncPref($name)
    {
        global $prefs;

        $value = get_pref($name, true);

        if (!isset($prefs[$name])) {
            $prefs[$name] = $value;
        }

        return $value;
    }

    /**
     * Switch the 'synchronize' preference value
     * and its related global variable.
     *
     * @param  string $name Pref name.
     * @return bool         FALSE on error.
     */

    protected function switchSyncPref($name)
    {
        global $prefs;

        return set_pref(
            $name,
            $prefs[$name] = !$prefs[$name],
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
     * @param  string $txtItem Textpack related item.
     * @param  mixed  $list    A name or an array of names associated with the result
     *                         to build the txtItem related '{list}'.
     *                         List values can be grouped like so: array($skin => $templates)
     * @param  string $status  'success'|'warning'|'error'.
     * @return object $this    The current class object (chainable).
     */

    protected function mergeResult($txtItem, $list, $status = null)
    {
        !is_string($list) or $list = array($list);
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
     * @param  array $status Array of results related status ('success', 'warning', 'error') to filter the output.
     * @return array         Associative array of status textpack related items
     *                       and their related '{list}' parameters.
     */

    protected function getResults($status = null)
    {
        if ($status === null) {
            return $this->results;
        } else {
            $results = array();

            foreach ($status as $severity) {
                $results[$severity] = $this->results[$severity];
            }

            return $results;
        }
    }

    /**
     * Get the $results property value as a message to display in the admin tabs.
     *
     * @return mixed Message or array containing the message
     *               and its related user notice constant.
     */

    public function getMessage()
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
     * $extension property getter.
     *
     * @return string static::$extension.
     */

    protected static function getExtension()
    {
        return static::$extension;
    }

    /**
     * Get files from the $dir property value related directory.
     *
     * @param  array $names    Optional filenames to filter the result.
     * @param  int   $maxDepth Optional RecursiveIteratorIterator related property value (default = -1 infinite).
     * @return object           Collection of file objects.
     */

    protected function getFiles($names = null, $maxDepth = null)
    {
        $extensions = implode('|', array_keys(static::$mimeTypes + array(self::getExtension() => null)));
        $filter = $names ? $names : '#^'.self::getNamePattern().'\.'."(?:$extensions)".'$#';
        $files = \Txp::get('Textpattern\Iterator\RecDirIterator', $this->getDirPath());
        $filter = \Txp::get('Textpattern\Iterator\RecFilterIterator', $files, $filter);
        $filteredFiles = \Txp::get('Textpattern\Iterator\RecIteratorIterator', $filter);
        $maxDepth !== null or $filteredFiles->setMaxDepth($maxDepth);

        return $filteredFiles;
    }

    /**
     * Insert a row into the $table property value related table.
     *
     * @param  string $set   Optional SET clause.
     *                       Builds the clause from the $infos (+ $skin) property value(s) if null.
     * @param  bool   $debug Dump query
     * @return bool          FALSE on error.
     */

    public function createRow($set = null, $debug = false)
    {
        if ($set === null) {
            $set = $this->getInfos(true);

            if (property_exists($this, 'skin')) {
                $set .= " skin = '".doSlash($this->getSkin()->getName())."'";
            }
        }

        return safe_insert($this->getTable(), $set, $debug);
    }

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

    public function updateRow($set = null, $where = null, $debug = false)
    {
        $set !== null or $set = $this->getInfos(true);

        if ($where === null) {
            $where = '';
            $base = $this->getBase();

            if ($base) {
                $where = "name = '".doSlash($base)."'";
            }

            if (property_exists($this, 'skin')) {
                $skin = $this->getSkin();
                $skinName = $skin ? $skin->getName() : '';

                if ($skinName) {
                    !$where or $where .= ' AND ';
                    $where .= "skin = '".doSlash($skinName)."'";
                }
            }

            $where or $where = '1 = 1';
        }

        return safe_update($this->getTable(), $set, $where, $debug);
    }

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

    public function getField($thing = null, $where = null, $debug = false)
    {
        $thing !== null or $thing = 'name';

        if ($where === null) {
            $where = '';
            $name = $this->getName();

            if ($name) {
                $where .= "name = '".doSlash($name)."'";
            }

            if (property_exists($this, 'skin')) {
                $skin = $this->getSkin();
                $skinName = $skin ? $skin->getName() : '';

                if ($skinName) {
                    !$where or $where .= ' AND ';
                    $where .= "skin = '".doSlash($skinName)."'";
                }
            }

            $where or $where = '1 = 1';
        }

        return safe_field($thing, $this->getTable(), $where, $debug);
    }

    /**
     * Delete rows from the $table property value related table.
     *
     * @param  string $where Optional WHERE clause.
     *                       Builds the clause from the $names (+ $skin) property value(s) if null.
     * @param  bool   $debug Dump query
     * @return bool          FALSE on error.
     */

    public function deleteRows($where = null, $debug = false)
    {
        if ($where === null) {
            $where = '';
            $names = $this->getNames();

            if ($names) {
                $where .= "name IN ('".implode("', '", array_map('doSlash', $names))."')";
            }

            if (property_exists($this, 'skin')) {
                $skin = $this->getSkin();
                $skinName = $skin ? $skin->getName() : '';

                if ($skinName) {
                    !$where or $where .= ' AND ';
                    $where .= "skin = '".doSlash($skinName)."'";
                }
            }

            $where or $where = '1 = 1';
        }

        return safe_delete($this->getTable(), $where, $debug);
    }

    /**
     * Count rows in the $table property value related table.
     *
     * @param  string $where The where clause.
     * @param  bool   $debug Dump query
     * @return mixed         Number of rows or FALSE on error
     */

    public function countRows($where = null, $debug = false)
    {
        return safe_count($this->getTable(), ($where === null ? '1 = 1' : $where), $debug);
    }

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

    public function getRow($things = null, $where = null, $debug = false)
    {
        $things !== null or $things = '*';

        if ($where === null) {
            $where = '';
            $name = $this->getName();

            if ($name) {
                $where .= "name = '".doSlash($name)."'";
            }

            if (property_exists($this, 'skin')) {
                $skin = $this->getSkin();
                $skinName = $skin ? $skin->getName() : '';

                if ($skinName) {
                    !$where or $where .= ' AND ';
                    $where .= "skin = '".doSlash($skinName)."'";
                }
            }

            $where or $where = '1=1';
        }

        return safe_row($things, $this->getTable(), $where, $debug);
    }

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

    public function getRows($things = null, $where = null, $debug = false)
    {
        $things !== null or $things = '*';

        if ($where === null) {
            $where = '';
            $names = $this->getNames();

            if ($names) {
                $where .= "name IN ('".implode("', '", array_map('doSlash', $names))."')";
            }

            if (property_exists($this, 'skin')) {
                $skin = $this->getSkin();
                $skinName = $skin ? $skin->getName() : '';

                if ($skinName) {
                    !$where or $where .= ' AND ';
                    $where .= "skin = '".doSlash($skinName)."'";
                }
            }

            $where or $where = '1=1';
        }

        $rs = safe_rows_start($things, $this->getTable(), $where, $debug);

        if ($rs) {
            $rows = array();

            while ($row = nextRow($rs)) {
                $rows[] = $row;
            }

            return $rows;
        }

        return array();
    }

    /**
     * Get the skin name used by the default section.
     *
     * @return mixed Skin name or FALSE on error.
     */

    protected function getDefault()
    {
        return safe_field($this->getEvent(), 'txp_section', 'name = "default"');
    }

    /**
     * $installed property setter.
     *
     * @param array $this->installed.
     */

    protected function setInstalled()
    {
        $things = 'name';
        $isAsset = property_exists($this, 'skin');
        $thing = $isAsset ? 'skin' : 'title, version';
        $things .= ', '.$thing;

        $rows = $this->getRows($things, '1 ORDER BY name');

        $this->installed = array();

        foreach ($rows as $row) {
            if ($isAsset) {
                $this->installed[$row[$thing]][] = $row['name'];
            } else {
                $this->installed[$row['name']] = $row['title'] .($row['version'] ? ' ('.$row['version'].')' : '');
            }
        }

        return $this->getInstalled();
    }

    /**
     * $installed property getter.
     *
     * @return array $this->installed.
     */

    public function getInstalled()
    {
        return $this->installed === null ? $this->setInstalled() : $this->installed;
    }

    /**
     * Whether a skin/template is installed or not.
     *
     * @param  string $name Skin name (default: $this->getName()).
     * @return bool
     */

    protected function isInstalled($name = null)
    {
        $isAsset = property_exists($this, 'skin');
        $name !== null or $name = $this->getName();

        if ($this->installed === null) {
            $isInstalled = (bool) $this->getField('name', "name = '".$name."'");
        } else {
            if ($isAsset) {
                $isInstalled = false;
                $installed = $this->getInstalled();

                foreach ($installed as $skin) {
                    if (in_array($name, array_keys($installed))) {
                        $isInstalled = $skin;
                    }
                }
            } else {
                $isInstalled = in_array($name, array_keys($this->getInstalled()));
            }
        }

        return $isInstalled;
    }

    /**
     * Whether a directory is empty or not.
     *
     * @param  string $path The directory path
     * @return mixed        NULL if the directory is not readable (or does not exist),
     *                      TRUE if empty, otherwise, FALSE.
     */
    protected static function isDirEmpty($path)
    {
        if (!is_readable($path)) {
            return null;
        }
        $handle = opendir($path);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return false;
            }
        }
        return true;
    }
}

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
 * SharedBase
 *
 * Extended by Main and AssetBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    abstract class SharedBase implements SharedInterface
    {
        /**
         * Class related DB table.
         *
         * @var array
         */

        protected static $table;

        /**
         * Class related DB table columns.
         *
         * @var array Column names.
         * @see       getTableCols().
         */

        protected static $tableCols;

        /**
         * Whether the skin is locked or not.
         * Locking is used to avoid conflicts during import/export.
         *
         * @var array Locked skin names
         * @see       lock(), unlock().
         */

        protected $locked = array();

        /**
         * Caches installed skins.
         *
         * @var array Associative array of skin names and their related title.
         * @see       setInstalled(), getInstalled().
         */

        protected static $installed = array();

        /**
         * Validates names on import/export.
         *
         * @var string Regular expression
         * @see        getValidNamePattern(), isValidName().
         */

        protected static $validNamePattern = '[a-zA-Z0-9_\-\.]{1,63}';

        /**
         * Caches uploaded skin directories.
         *
         * @var array Associative array of skin names and their related title.
         * @see       getDirectories().
         */

        protected static $directories = null;

        /**
         * Collected results.
         *
         * @var array Associative array of 'success', 'warning' and 'error' related messages;
         * @see       setResults(), getResults().
         */

        protected $results = array(
            'success' => array(),
            'warning' => array(),
            'error'   => array(),
        );

        /**
         * {@inheritdoc}
         */

        public static function getTable()
        {
            return static::$table;
        }

        /**
         * {@inheritdoc}
         */

        public static function getTableCols($exclude = array('lastmod'))
        {
            if (static::$tableCols) {
                return static::$tableCols;
            } else {
                $query = safe_query('SHOW COLUMNS FROM '.safe_pfx(static::$table));
                static::$tableCols = array();

                while ($row = $query->fetch_assoc()) {
                    if (!in_array($row['Field'], $exclude)) {
                        static::$tableCols[] = $row['Field'];
                    }
                }

                return static::$tableCols;
            }
        }

        /**
         * {@inheritdoc}
         */

        public static function setInstalled($skins)
        {
            static::$installed = array_merge(self::getInstalled(), $skins);
        }

        /**
         * {@inheritdoc}
         */

        public static function getInstalled()
        {
            if (!static::$installed) {
                $skins = safe_rows('name, title', 'txp_skin', "1=1");

                if ($skins) {
                    static::$installed = array();

                    foreach ($skins as $skin) {
                        static::$installed[$skin['name']] = $skin['title'];
                    }
                }
            }

            return static::$installed;
        }

        /**
         * {@inheritdoc}
         */

        public static function getValidNamePattern()
        {
            return static::$validNamePattern;
        }

        /**
         * {@inheritdoc}
         */

        public static function isValidName($name)
        {
            return preg_match('#^'.self::getValidNamePattern().'$#', $name);
        }

        /**
         * {@inheritdoc}
         */

        public function getSkins()
        {
            if (property_exists($this, 'skinsAssets')) {
                $skinsTemplates = $this->skinsAssets;
            } else {
                $skinsTemplates = $this->skinsTemplates;
            }

            return array_keys($skinsTemplates);
        }

        /**
         * {@inheritdoc}
         */

        public function getSkinIndex($skin)
        {
            return array_search($skin, $this->getSkins());
        }

        /**
         * {@inheritdoc}
         */

        public static function sanitize($string)
        {
            $string = (string) $string;

            return strtolower(sanitizeForTheme($string));
        }

        /**
         * Inserts or updates all asset related templates at once.
         *
         * @param  array $fields The template related database fields;
         * @param  array $values SQL VALUES as an array of group of values;
         * @param  bool  $update Whether to update rows on duplicate keys or not;
         * @return bool          false on error.
         */

        protected function insert($fields, $values, $update = false)
        {
            if ($update) {
                $updates = array();

                foreach ($fields as $field) {
                    $updates[] = $field.'=VALUES('.$field.')';
                }

                $update = 'ON DUPLICATE KEY UPDATE '.implode(', ', $updates);
            }

            return (bool) safe_query(
                sprintf(
                    'INSERT INTO '.safe_pfx(self::getTable()).' (%s) VALUES %s %s',
                    implode(', ', $fields),
                    implode(', ', $values),
                    $update ? $update : ''
                )
            );
        }

        /**
         * {@inheritdoc}
         */

        public static function isInstalled($skin)
        {
            $isInstalled = array_key_exists($skin, self::getInstalled());

            if (!$isInstalled) {
                $isInstalled = (bool) safe_field('name', 'txp_skin', "name ='".doSlash($skin)."'");
            }

            return $isInstalled;
        }

        /**
         * {@inheritdoc}
         */

        public static function isType($path)
        {
            $isFile = pathinfo($path, PATHINFO_EXTENSION);
            $isType = $isFile ? is_file($path) : is_dir($path);

            return $isType;
        }

        /**
         * {@inheritdoc}
         */

        public static function isReadable($path = null)
        {
            $path = self::getPath($path);

            return self::isType($path) && is_readable($path);
        }

        /**
         * {@inheritdoc}
         */

        public static function isWritable($path = null)
        {
            $path = self::getPath($path);

            return self::isType($path) && is_writable($path);
        }

        /**
         * {@inheritdoc}
         */

        public static function mkDir($path = null)
        {
            return @mkdir(self::getPath($path));
        }

        /**
         * {@inheritdoc}
         */

        public static function rmDir($path = null)
        {
            return @rmdir(self::getPath($path));
        }

        /**
         * {@inheritdoc}
         */

        public function lock($skin)
        {
            $timeStart = microtime(true);
            $locked = false;
            $time = 0;

            while (!$locked && $time < 3) {
                $locked = @mkdir(self::getPath($skin.'/lock'));
                sleep(0.5);
                $time = microtime(true) - $timeStart;
            }

            $locked ? $this->locked[] = $skin : '';

            return $locked;
        }

        /**
         * {@inheritdoc}
         */

        public function unlock($skin)
        {
            if (@rmdir(self::getPath($skin.'/lock'))) {
                unset($this->locked[array_search($skin, $this->locked)]);

                return true;
            }

            return false;
        }

        /**
         * {@inheritdoc}
         */

        public static function getBasePath()
        {
            return get_pref('skin_base_path');
        }

        /**
         * {@inheritdoc}
         */

        public static function getPath($path = null)
        {
            return self::getBasePath().'/'.$path;
        }

        /**
         * Sets/Merges results in the dedicated property.
         *
         * @param string $string  The textpack related string;
         * @param array  $data    The gTxt() related data;
         * @param string $status  Whether it is a 'success', 'warning' or 'error' related result.
         */

        protected function setResults($string = null, $data = null, $status = null)
        {
            if ($string === null) {
                $this->results = array(
                    'success' => array(),
                    'warning' => array(),
                    'error'   => array(),
                );
            } else {
                in_array($status, array('success', 'warning', 'error')) ?: $status = 'error';

                $this->results[$status][] = gTxt($string, self::getResultData($data));
            }
        }

        protected static function getResultData($passed)
        {
            $names = array();

            foreach ($passed as $skin => $templates) {
                if ($templates) {
                    $names[] = '('.$skin.')  '.implode(', ', $templates);
                    $glue = '; ';
                } else {
                    $names[] = $skin;
                    $glue = ', ';
                }
            }

            return array('{list}' => implode($glue, $names));
        }

        /**
         * {@inheritdoc}
         */

        public function getResults($status = array('success', 'warning', 'error'), $output = null)
        {
            $messages = array();
            $error = null;
            $results = $this->results;

            $unwanteds = array_diff(
                array('success', 'warning', 'error'),
                (is_array($status) ? $status : array($status))
            );

            foreach ($unwanteds as $unwanted) {
                unset($results[$unwanted]);
            }

            if ($output === 'raw') {
                $out = $results;
            } else {
                if ($results['success']) {
                    ($results['warning'] || $results['error']) ? $error = 'E_WARNING' : '';
                } elseif ($results['warning']) {
                    $error = ($results['error']) ? 'E_ERROR' : 'E_WARNING';
                } else {
                    $error = 'E_ERROR';
                }

                $messageList = array();

                foreach ($results as $severity => $messages) {
                    $messages ? $messageList[] = implode('<br>', $messages) : '';
                }

                $finalMessage = implode('<br>', $messageList);

                $out = $error ? array($finalMessage, constant($error)) : $finalMessage;
            }

            $this->setResults(); // Reset results.

            return $out;
        }
    }
}

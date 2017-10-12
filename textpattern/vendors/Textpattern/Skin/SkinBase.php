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
 * Skin Base
 *
 * Extended by Skin and AssetBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    abstract class SkinBase extends MainBase implements SkinInterface
    {
        /**
         * The skin to work with.
         *
         * @var string
         */

        protected $skin;

        /**
         * Caches whether the skin related row exists.
         *
         * @var bool
         * @see isInstalled()
         */

        protected $isInstalled = null;

        /**
         * Caches whether the skin is used by any section.
         *
         * @var bool
         * @see skinIsInUse()
         */

        protected $isInUse = null;

        /**
         * Whether the skin is locked via a 'lock' directory or not.
         *
         * @var string
         * @see lockSkin(), unlockSkin()
         */

        protected $locked = false;

        /**
         * The skin copy title.
         *
         * @var string
         */

        protected $copy;

        /**
         * The skin infos to work with as an associative array.
         *
         * @var array
         */

        protected $infos;

        /**
         * Constructor.
         *
         * @param string $skin  The skin name (set the related property);
         * @param array  $infos Skin infos (set the related property).
         */

        public function __construct($skin = null, $infos = null)
        {
            $skin ? $this->skin = $skin : '';
            $infos ? $this->infos = $infos : '';
        }

        /**
         * {@inheritdoc}
         */

        final public function skinIsInstalled($copy = false)
        {
            if ($this->skin) {
                if ($this->isInstalled === null) {
                    $name = strtolower(sanitizeForUrl($this->copy ? $this->copy : $this->skin));
                    $this->isInstalled = self::isInstalled($name);
                }

                return $this->isInstalled;
            }

            throw new \Exception('undefined_skin');
        }

        /**
         * Whether a skin row exists or not.
         *
         * @return bool
         */

        public static function isInstalled($skin)
        {
            $skin = strtolower(sanitizeForUrl($skin));

            if (static::$installed === null) {
                return (bool) safe_field('name', 'txp_skin', "name ='".doSlash($skin)."'");
            } else {
                return array_key_exists($skin, static::$installed);
            }
        }

        /**
         * Checks if a skin directory exists and is readable.
         *
         * @return string|bool path or false
         */

        public function isReadable($path = null)
        {
            $path = $this->getPath($path);

            return self::isType($path) && is_readable($path);
        }

        /**
         * Checks if the Skin directory exists and is writable;
         * if not, creates it.
         *
         * @param  string      $path See getPath().
         * @return string|bool path or false
         */

        public function isWritable($path = null)
        {
            $path = $this->getPath($path);

            return self::isType($path) && is_writable($path);
        }

        /**
         * Checks if a directory or file exists.
         *
         * @return string|bool path or false
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

        final public function lockSkin()
        {
            $time_start = microtime(true);

            if ($this->locked) {
                return true;
            } else {
                while (!($locked = $this->mkDir('lock')) && $time < 3) {
                    sleep(0.5);
                    $time = microtime(true) - $time_start;
                }

                if ($locked) {
                    $this->locked = true;
                    return $locked;
                }

                throw new \Exception("unable_to_create_the_skin_lock_directory");
            }
        }

        /**
         * {@inheritdoc}
         */

        final public function mkDir($path = null)
        {
            if (($path = $this->getPath($path)) && $created = @mkdir($path)) {
                return $created;
            }

            throw new \Exception(
                gtxt(
                    'unable_to_create_skin_directory',
                    array('{directory}' => basename($path))
                )
            );
        }

        /**
         * {@inheritdoc}
         */

        final public function unlockSkin()
        {
            if ($unlocked = $this->rmDir('lock')) {
                $this->locked = false;
                return $unlocked;
            }

            throw new \Exception("unable_to_unlock_the_skin_directory");
        }

        /**
         * {@inheritdoc}
         */

        final public function rmDir($path = null)
        {
            if (($path = $this->getPath($path)) && $removed = @rmdir($path)) {
                return $removed;
            }

            throw new \Exception(
                gtxt(
                    'unable_to_remove_skin_directory',
                    array('{directory}' => basename($path))
                )
            );
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

        public function getPath($path = null)
        {
            return self::getBasePath().'/'.
                strtolower(sanitizeForUrl($this->copy ? $this->copy : $this->skin)).
                ($path ? '/'.$path : '');
        }
    }
}

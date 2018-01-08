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

namespace Textpattern\Skin\Asset\Form {

    class Model extends \Textpattern\Skin\Asset\Model
    {
        protected static $table = 'txp_form';
        protected static $tableCols;
        protected static $contentsCol = 'Form';
        protected static $subdirCol = 'type';
        protected static $defaultSubdir = 'misc';
        protected static $dir = 'forms';
        protected static $asset = 'form';
        protected static $essential = array(
            'comment' => array('comments_display', 'comment_form'),
            'article' => array('default'),
            'link'    => array('plainlinks'),
            'file'    => array('files'),
        );

        /**
         * $subdirCol property getter.
         */

        public static function getSubdirCol()
        {
            return static::$subdirCol;
        }

        /**
         * $defaultSubdir property getter.
         */

        public static function getDefaultSubdir()
        {
            return static::$defaultSubdir;
        }

        /**
         * Gets an essential template related subdirectory.
         *
         * @param  string $name Template name.
         * @return array        The template related subdirectory, or the default one.
         */

        public static function getEssentialSubdir($name = null)
        {
            $essentialType = static::getDefaultSubdir();

            foreach (static::getEssential() as $type => $names) {
                in_array($name, $names) ? $essentialType = $type : '';
            }

            return $essentialType;
        }

        /**
         * Gets essential all essential names or ones related to a provided type.
         *
         * @param  string $type A type.
         * @return array        Essential names.
         */

        public static function getEssentialNames($type = null)
        {
            $essential = static::getEssential();

            if ($type) {
                $essentialNames = $essential[$type];
            } else {
                $essentialNames = array();

                foreach ($essential as $type => $names) {
                    $essentialNames = array_merge($essentialNames, $names);
                }
            }

            return $essentialNames;
        }

        /**
         * Gets an asset related subdirectory path.
         *
         * @param  string $name Subdirectory name.
         * @return array        Path.
         */

        public function getSubdirPath($name = null)
        {
            $name ?: $name = static::getDefaultSubdir();

            return $this->getDirPath().'/'.$name;
        }

        /**
         * Creates an asset related subdirectory.
         *
         * @param  string $name Subdirectory name.
         * @return bool         false on error.
         */

        public function createSubdir($name = null)
        {
            return @mkdir($this->getSubdirPath($name));
        }

        /**
         * Whether an asset related subdirectory exists or not.
         *
         * @param  string $name Subdirectory name.
         * @return bool
         */

        public function subdirExists($name = null)
        {
            return file_exists($this->getSubdirPath($name));
        }

        /**
         * Gets the template related file path.
         *
         * @param  string $subdir The subdirectory name.
         * @return string         Path.
         */

        public function getFilePath($subdir = null)
        {
            return $this->getSubdirPath($subdir).'/'.$this->getName().'.'.static::getExtension();
        }

        /**
         * Creates the template related file.
         *
         * @param  string $contents The File contents;
         * @param  string $subdir   The subdirectory name.
         * @return bool             false on error.
         */

        public function createFile($contents = null, $subdir = null)
        {
            if ($this->subdirExists($subdir) || $this->createSubdir($subdir)) {
                return file_put_contents($this->getFilePath($subdir), $contents);
            }

            return false;
        }

        /**
         * Creates template rows.
         *
         * @param  string $contents The File contents;
         * @return bool             false on error.
         */

        public function createRows($contents = null)
        {
            $thisNames = $this->getNames();
            $names = $thisNames ? $thisNames : static::getEssentialNames();
            $values = array();

            foreach ($names as $i => $name) {
                $values[] = "('".doSlash($this->getSkin()->getName())."', "
                            ."'".doSlash($name)."', "
                            ."'".doSlash(static::getEssentialSubdir($name))."', "
                            ."'".doSlash($contents ? $contents[$i] : '')."')";
            }

            return safe_query(
                "INSERT INTO ".static::getTable()." (skin, name, ".static::getSubdirCol().", ".static::getContentsCol().") "
                ."VALUES ".implode(', ', $values)
            );
        }

        protected function parseFiles($Files) {
            $out = array();

            foreach ($Files as $File) {
                $out[$File->getDir()][$File->getName()] = $File->getContents();
            }

            return $out;
        }

        protected function parseRows($rows) {
            $skinRows = array();

            while ($row = nextRow($rows)) {
                $skinRows[$row[static::getSubdirCol()]][$row['name']] = $row[static::getContentsCol()];
            }

            return $skinRows;
        }
    }
}

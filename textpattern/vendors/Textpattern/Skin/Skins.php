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
 * Skins
 *
 * Manages the Skin admin tab features.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Skins extends SkinsBase implements SkinsInterface
    {
        /**
         * {@inheritdoc}
         */

        protected $skins;

        /**
         * Constructor.
         *
         * @param mixed $skins  Skin(s) names.
         */

        public function __construct($skins = null)
        {
            if ($skins) {
                $this->skins = is_array($skins) ? $skins : array($skins);
            }
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate($assets = null)
        {
            return $this->callSkinsMethod(__FUNCTION__, func_get_args());
        }

        /**
         * {@inheritdoc}
         */

        public function update($clean = true, $assets = null)
        {
            return $this->callSkinsMethod(__FUNCTION__, func_get_args());
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = true, $assets = null)
        {
            return $this->callSkinsMethod(__FUNCTION__, func_get_args());
        }

        /**
         * {@inheritdoc}
         */

        public function delete()
        {
            return $this->callSkinsMethod(__FUNCTION__);
        }

        /**
         * Iterates skins and calls the defined Skin class method.
         *
         * @param  string $method A Skin class method name.
         * @param  array  $args   Array of arguments to pass to the defined method.
         * @return string The UI message to display.
         */

        private function callSkinsMethod($method, $args = array())
        {
            $instance = \Txp::get('Textpattern\Skin\Skin');

            foreach ($this->skins as $skin) {
                $instance->setSkin($skin);
                call_user_func_array(array($instance, $method), $args);
            }

            $this->results = $instance->results;
        }

        /**
         * Gets the skin import form.
         *
         * @return html The form or a message if no new skin directory is found.
         */

        public static function renderImportForm()
        {
            $new = self::getNewDirectories();

            if ($new) {
                return n.
                    tag_start('form', array(
                        'id'     => 'skin_import_form',
                        'name'   => 'skin_import_form',
                        'method' => 'post',
                        'action' => 'index.php',
                    )).
                    tag(gTxt('import_skin'), 'label', array('for' => 'skin_import')).
                    popHelp('skin_import').
                    selectInput('skins', $new, '', true, false, 'skins').
                    eInput('skin').
                    sInput('import').
                    fInput('submit', '', gtxt('upload')).
                    n.
                    tag_end('form');
            }
        }

        /**
         * Gets an array of the available skin directories.
         *
         * Skin directories name must be in lower cases
         * and sanitized for URL to appears in the select list.
         * Theses directories also need to contain a manifest.json file
         * to get the skin title from the 'name' JSON field.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function getDirectories()
        {
            if (static::$directories === null) {
                $skins = self::getRecDirIterator();
                static::$directories = array();

                foreach ($skins as $skin) {
                    $name = basename($skin->getPath());

                    if (preg_match('#^[a-z][a-z0-9_\-\.]{0,63}$#', $name)) {
                        $infos = $skin->getTemplateJSONContents();
                        static::$directories[$name] = $infos['title'];
                    }
                }
            }

            return static::$directories;
        }

        /**
         * {@inheritdoc}
         */

        public static function getRecDirIterator()
        {
            return new RecIteratorIterator(
                new RecRegexIterator(
                    new RecDirIterator(get_pref('skin_base_path')),
                    '/^manifest\.json/i'
                ),
                1
            );
        }

        /**
         * Gets an array of the new — not imported yet — skin directories.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function getNewDirectories()
        {
            return array_diff_key(
                self::getDirectories(),
                self::getInstalled()
            );
        }

        /**
         * Gets an array of the installed skins.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function getInstalled()
        {
            if (static::$installed === null) {
                $skins = safe_rows('name, title', 'txp_skin', "1=1");

                if (!empty($skins)) {
                    static::$installed = array();

                    foreach ($skins as $skin) {
                        static::$installed[$skin['name']] = $skin['title'];
                    }
                } else {
                    throw new \Exception('empty_skin_table');
                }
            }

            return static::$installed;
        }

        /**
         * Gets an array of the installed skins.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function renderSwitchForm($event, $step, $current)
        {
            return form(
                inputLabel('skin', selectInput('skin', self::getInstalled(), $current, false, 1, 'skin'), 'skin').
                eInput($event).
                sInput($step),
                '',
                '',
                'post'
            );
        }
    }
}

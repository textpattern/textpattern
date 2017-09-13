<?php

/*
 * Textpattern Content Management System
 * https://textpattern.io/
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
 * Main
 *
 * Manages the Skin admin tab features.
 *
 * @since   4.7.0
 * @package Skin
 */
 
namespace Textpattern\Skin {

    class Main extends MainBase implements MainInterface
    {
        /**
         * Stores skin(s) instances.
         *
         * @var array
         */

        protected $skins = array();

        /**
         * Constructor.
         *
         * @param array        $skins  Associative array of the skin(s) and their related edit infos.
         * @param string|array $assets Asset, array of assets or associative array of asset(s)
         *                             and its/their related template(s) to work with.
         */

        public function __construct($skins, $assets = array('pages', 'forms', 'styles'))
        {
            foreach ($skins as $skin => $infos) {
                $this->skins[$skin] = \Txp::get('Textpattern\Skin\Skin', $skin, $infos, $assets);
            }
        }

        /**
         * {@inheritdoc}
         */

        public function create()
        {
            return $this->callSkinsMethod(__FUNCTION__);
        }

        /**
         * {@inheritdoc}
         */

        public function edit()
        {
            return $this->callSkinsMethod(__FUNCTION__);
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate()
        {
            return $this->callSkinsMethod(__FUNCTION__);
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = true)
        {
            return $this->callSkinsMethod(__FUNCTION__, func_get_args());
        }

        /**
         * {@inheritdoc}
         */

        public function update($clean = true)
        {
            return $this->callSkinsMethod(__FUNCTION__, func_get_args());
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = true, $copy = false)
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
            $done = substr($method, -1) === 'e' ? 'd' : 'ed';
            $results = array();

            foreach ($this->skins as $skin => $instance) {
                try {
                    call_user_func_array(array($instance, $method), $args);

                    $results[$skin]['success'][] = gtxt(
                        'skin_step_succeeded',
                        array('{step}' => $method.$done)
                    );
                } catch (\Exception $e) {
                    $results[$skin]['error'][] = $e->getMessage();
                }
            }

            return self::getUIMessage($results);
        }

        /**
         * Builds the UI message to display.
         *
         * @param  array  $results Associative array of the skin(s)
         *                         and their success/failure messages.
         * @return string The UI message to display.
         * @see callSkinsMethod().
         */

        public static function getUIMessage($results)
        {
            $out = array();
            $status = null;

            foreach ($results as $skin => $result) {
                if (array_key_exists('error', $result) || $status === 'E_ERROR') {
                    if ($status && array_key_exists('success', $result)) {
                        $status = 'E_WARNING';
                    } else {
                        $status = 'E_ERROR';
                    }
                }

                foreach ($result as $severity => $messages) {
                    foreach ($messages as $message) {
                        if (array_key_exists($message, $out) && $severity === 'success') {
                            $out[$message] .= ', '.$skin;
                        } else {
                            $out[$message] = $message.($severity === 'success' ? ' '.$skin : '');
                        }
                    }
                }
            }

            $out = implode('<br>', $out);

            return $status ? array($out, constant($status)) : $out;
        }

        /**
         * Gets the skin import form.
         *
         * @return html The form or a message if no new skin directory is found.
         */

        public static function renderImportForm()
        {
            if ($new = self::getNewDirectories()) {
                return n.
                    tag_start('form', array(
                        'id'     => 'skin_import_form',
                        'name'   => 'skin_import_form',
                        'method' => 'post',
                        'action' => 'index.php',
                    )).
                    tag(gTxt('import_skin'), 'label', array('for' => 'skin_import')).
                    popHelp('skin_import').
                    selectInput('skins', $new, '', true, true, 'skins').
                    eInput('skin').
                    sInput('import').
                    n.
                    tag_end('form');
            } else {
                return '<span>'.gtxt('no_new_skin_to_import').'</span>';
            }
        }

        /**
         * Gets an array of the available skin directories.
         *
         * Skin directories name must be in lower cases
         * and sanitized for URL to appears in the select list.
         * Theses directories also need to contain a composer.json file
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

                    if ($name === strtolower(sanitizeForUrl($name))) {
                        $infos = $skin->getTemplateJSONContents();
                        static::$directories[$name] = $infos['name'];
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
                new RecFilterIterator(
                    new RecDirIterator(get_pref('skin_base_path')),
                    'json',
                    'composer'
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
                if (!empty($skins = safe_rows('name, title', 'txp_skin', "1=1"))) {
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
    }
}

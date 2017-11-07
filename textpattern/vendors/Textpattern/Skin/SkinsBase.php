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
 * Skins Base
 *
 * Extended by Skins and skinBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    abstract class SkinsBase
    {
        /**
         * Caches the installed skins.
         *
         * @var array Associative array of skin names and their related title.
         * @see Skins::getInstalled()
         */

        protected static $installed = null;

        /**
         * Caches the uploaded skin directories.
         *
         * @var array Associative array of skin names and their related title.
         * @see Skins::getDirectories()
         */

        protected static $directories = null;

        /**
         * Collected results.
         *
         * @var array
         */

        protected $results = array();

        /**
         * Sets results.
         *
         * @param  string $via The result message;
         * @param  bool   $failure Whether it is a failure or a success message;
         */

        protected function setResults($message, $failure = true)
        {
            $status = $failure ? 'failure' : 'success';
            $this->results[$this->skin][$status][] = $message;
        }

        /**
         * Renders results through a defined function.
         *
         * @param  string $via The function to call;
         * @return mixed  Result of the function called.
         */

        public function renderResults($via = 'skin_list')
        {
            return call_user_func($via, $this->getResults());
        }

        /**
         * Builds the UI message to display from the results property.
         *
         * @return string The UI message to display.
         * @see renderResults().
         */

        public function getResults()
        {
            $out = array();

            $success = false;
            $failure = false;

            foreach ($this->results as $skin => $result) {
                $success ?: $success = array_key_exists('success', $result);
                $failure ?: $failure = array_key_exists('failure', $result);

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

            if ($success) {
                $failure ? $status = 'E_WARNING' : '';
            } else {
                $status = 'E_ERROR';
            }

            $out = implode('<br>', $out);

            return isset($status) ? array($out, constant($status)) : $out;
        }
    }
}

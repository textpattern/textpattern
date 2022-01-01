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
 * Autoloader.
 *
 * <code>
 * Txp::get('\Textpattern\Loader', '/path/to/directory')->register();
 * </code>
 *
 * @since   4.6.0
 * @package Autoloader
 */

namespace Textpattern;

class Loader
{
    /**
     * Registered directory.
     *
     * @var string
     */

    protected $directory;

    /**
     * Registered namespace.
     *
     * @var string
     */

    protected $namespace;

    /**
     * Namespace separator.
     *
     * @var string
     */

    protected $separator;

    /**
     * File extension.
     *
     * @var string
     */

    protected $extension;

    /**
     * Registers the loader.
     *
     * @return bool FALSE on error
     */

    public function register()
    {
        global $trace;

        if ($this->directory) {
            $trace->log("[Textpattern autoload dir: '".str_replace(txpath.'/', '', $this->directory)."']");

            return spl_autoload_register(array($this, 'load'));
        }

        return false;
    }

    /**
     * Unregisters a loader.
     *
     * @return bool FALSE on error
     */

    public function unregister()
    {
        return spl_autoload_unregister(array($this, 'load'));
    }

    /**
     * Constructor.
     *
     * @param string $directory Registered vendors directory
     * @param string $namespace Limits the loader to a specific namespace
     * @param string $separator Namespace separator
     * @param string $extension File extension
     */

    public function __construct($directory, $namespace = null, $separator = '\\', $extension = '.php')
    {
        if (file_exists($directory) && is_dir($directory)) {
            $this->directory = $directory;
            $this->namespace = $namespace;
            $this->separator = $separator;
            $this->extension = $extension;
        }
    }

    /**
     * Loads a class.
     *
     * @param  string $class The class
     * @return bool
     */

    public function load($class)
    {
        global $trace;

        $request = $class;

        if ($this->namespace !== null && strpos($class, $this->namespace.$this->separator) !== 0 ||
            !preg_match('/^[\\a-zA-Z_\x7f-\xff][a-zA-Z0-9_\\\x7f-\xff]*$/', $class)
        ) {
            return false;
        }

        $file = $this->directory.'/';
        $divide = strripos($class, $this->separator);

        if ($divide !== false) {
            $namespace = substr($class, 0, $divide);
            $class = substr($class, $divide + 1);
            $file .= str_replace($this->separator, '/', $namespace).'/';
        }

        $file .= $class.$this->extension;

        if (is_readable($file)) {
            $trace->start("[Load: '".str_replace(txpath.'/', '', $file)."']");
            require_once $file;

            if (class_exists($request, false)) {
                $trace->log("[Class loaded: '$class']");
                $trace->stop();

                return true;
            }

            $trace->stop();
        }

        return false;
    }
}

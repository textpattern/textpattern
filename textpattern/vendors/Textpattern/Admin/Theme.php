<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2016 The Textpattern Development Team
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Base for admin-side themes.
 *
 * @package Admin\Theme
 */

namespace Textpattern\Admin;

if (!defined('THEME')) {
    /**
     * Relative path to themes directory.
     */

    define('THEME', 'admin-themes/');
}

/**
 * Admin-side theme.
 *
 * @package Admin\Theme
 */

abstract class Theme
{
    /**
     * The theme name.
     *
     * @var string
     */

    public $name;

    /**
     * Stores a menu.
     *
     * @var array
     */

    public $menu;

    /**
     * Theme location.
     *
     * @var string
     */

    public $url;

    /**
     * Just a popup window.
     *
     * @var bool
     */

    public $is_popup;

    /**
     * Stores an activity message.
     *
     * @var bool
     * @see \Textpattern\Admin\Theme::announce()
     * @see \Textpattern\Admin\Theme::announce_async()
     */

    public $message;

    /**
     * Constructor.
     *
     * @param string $name Theme name
     */

    public function __construct($name)
    {
        $this->name = $name;
        $this->menu = array();
        $this->url = THEME.rawurlencode($name).'/';
        $this->is_popup = false;
        $this->message = '';
    }

    /**
     * Gets a theme's source path.
     *
     * @param  string $name Theme name
     * @return string Source file path for named theme
     */

    public static function path($name)
    {
        return txpath.DS.THEME.$name.DS.$name.'.php';
    }

    /**
     * Theme factory.
     *
     * @param  string $name Theme name
     * @return \Textpattern\Admin\Theme|bool An initialised theme object or FALSE on failure
     */

    public static function factory($name)
    {
        $path = Theme::path($name);

        if (is_readable($path)) {
            require_once($path);
        } else {
            return false;
        }

        $t = "{$name}_theme";

        if (class_exists($t)) {
            return new $t($name);
        } else {
            return false;
        }
    }

    /**
     * Initialise the theme singleton.
     *
     * @param  string $name Theme name
     * @return \Textpattern\Admin\Theme A valid theme object
     */

    public static function init($name = '')
    {
        static $instance;

        if ($name === '') {
            $name = pluggable_ui('admin_side', 'theme_name', get_pref('theme_name', 'hive'));
        }

        if ($instance && is_object($instance) && ($name == $instance->name)) {
            return $instance;
        } else {
            $instance = null;
        }

        $instance = Theme::factory($name);

        if (!$instance) {
            set_pref('theme_name', 'hive');
            die(gTxt('cannot_instantiate_theme', array('{name}' => $name, '{class}' => "{$name}_theme", '{path}' => Theme::path($name))));
        }

        return $instance;
    }

    /**
     * Get a list of all theme names.
     *
     * @return array Alphabetically sorted array of all available theme names
     */

    public static function names()
    {
        $dirs = glob(txpath.DS.THEME.'*');

        if (is_array($dirs)) {
            foreach ($dirs as $d) {
                // Extract trailing directory name.
                preg_match('#(.*)[\\/]+(.*)$#', $d, $m);
                $name = $m[2];

                // Accept directories containing an equally named .php file.
                if (is_dir($d) && ($d != '.') && ($d != '..') && isset($name) && is_file($d.DS.$name.'.php')) {
                    $out[] = $name;
                }
            }

            sort($out, SORT_STRING);

            return $out;
        }

        return array();
    }

    /**
     * Inherit from an ancestor theme.
     *
     * @param  string $name Name of ancestor theme
     * @return bool TRUE on success, FALSE on unavailable/invalid ancestor theme
     */

    public static function based_on($name)
    {
        global $production_status;
        $theme = Theme::factory($name);

        if (!$theme) {
            set_pref('theme_name', 'hive');

            if ($production_status === 'debug') {
                echo gTxt('cannot_instantiate_theme', array('{name}' => $name, '{class}' => "{$name}_theme", '{path}' => Theme::path($name)));
            }

            return false;
        }

        return true;
    }

    /**
     * Sets Textpatterns menu structure, message contents and other application
     * states.
     *
     * @param  string $area Currently active top level menu
     * @param  string $event Currently active second level menu
     * @param  bool $is_popup Just a popup window for tag builder et cetera
     * @param  array $message The contents of the notification message pane
     * @return \Textpattern\Admin\Theme This theme object
     */

    public function set_state($area, $event, $is_popup, $message)
    {
        $this->is_popup = $is_popup;
        $this->message = $message;

        if ($is_popup) {
            return $this;
        }

        // Use legacy areas() for b/c.
        $areas = areas();
        $defaults = array(
            'content'      => 'article',
            'presentation' => 'page',
            'admin'        => 'admin',
        );

        if (empty($areas['start'])) {
            unset($areas['start']);
        }

        if (empty($areas['extensions'])) {
            unset($areas['extensions']);
        }

        $dflt_tab = get_pref('default_event', '');

        foreach ($areas as $ar => $items) {
            $l_ = gTxt('tab_'.$ar);
            $e_ = (array_key_exists($ar, $defaults)) ? $defaults[$ar] : reset($areas[$ar]);
            $i_ = array();

            if (has_privs('tab.'.$ar)) {
                if (!has_privs($e_)) {
                    $e_ = '';
                }

                foreach ($items as $a => $b) {
                    if (has_privs($b)) {
                        if ($e_ === '') {
                            $e_ = $b;
                        }

                        if ($b == $dflt_tab) {
                            $this->menu[$ar]['event'] = $dflt_tab;
                        }

                        $i_[] = array('label' => $a, 'event' => $b, 'active' => ($b == $event));
                    }
                }

                if ($e_) {
                    $this->menu[$ar] = array(
                        'label'  => $l_,
                        'event'  => $e_,
                        'active' => ($ar == $area),
                        'items'  => $i_,
                    );
                }
            }
        }

        return $this;
    }

    /**
     * HTML &lt;head&gt; section.
     *
     * Returned value is rendered into the head element of
     * all admin pages.
     *
     * @return string
     */

    abstract public function html_head();

    /**
     * Draw the theme's header.
     *
     * @return string
     */

    abstract public function header();

    /**
     * Draw the theme's footer.
     *
     * @return string
     */

    abstract public function footer();

    /**
     * Output notification message for synchronous HTML views.
     *
     * @param  array $thing Message text and status flag
     * @param  bool $modal If TRUE, immediate user interaction suggested
     * @return string HTML
     * @example
     * global $theme;
     * echo $theme->announce(array('my_message', E_ERROR));
     */

    public function announce($thing = array('', 0), $modal = false)
    {
        trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
    }

    /**
     * Output notification message for asynchronous JavaScript views.
     *
     * @param  array $thing Message text and status flag
     * @param  bool $modal If TRUE, immediate user interaction suggested
     * @return string JavaScript
     * @since 4.5.0
     * @example
     * global $theme;
     * echo script_js(
     *     $theme->announce_async(array('my_message', E_ERROR))
     * );
     */

    public function announce_async($thing = array('', 0), $modal = false)
    {
        trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
    }

    /**
     * Define bureaucratic details of this theme.
     *
     * All returned items are optional.
     *
     * @return array
     */

    public function manifest()
    {
        return array(
            'title'       => '', // Human-readable title of this theme. No HTML, keep it short.
            'author'      => '', // Name(s) of this theme's creator(s).
            'author_uri'  => '', // URI of the theme's site. Decent vanity is accepted.
            'version'     => '', // Version numbering. Mind version_compare().
            'description' => '', // Human readable short description. No HTML.
            'help'        => '', // URI of the theme's help and docs. Strictly optional.
        );
    }
}

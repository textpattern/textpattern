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
 * Base for admin-side themes.
 *
 * @package Admin\Theme
 */

namespace Textpattern\Admin;

if (!defined('THEME')) {
    /**
     * Relative path to themes directory.
     */

    define('THEME', 'admin-themes'.DS);
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

    public $cssPath;
    public $jsPath;

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
        $this->cssPath = 'assets'.DS.'css';
        $this->jsPath = 'assets'.DS.'js';
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
            die(gTxt('cannot_instantiate_theme', array(
                '{name}'  => $name,
                '{class}' => "{$name}_theme",
                '{path}'  => Theme::path($name),
            )));
        }

        return $instance;
    }

    /**
     * Get a list of all theme names.
     *
     * @param int $format
     *   0 - names
     *   1 - name => title              Used for selectInput
     *   2 - name => manifest(array)    Now not used, reserved
     *
     * @return array of all available theme names
     */

    public static function names($format = 0)
    {
        $out = array();

        if ($files = glob(txpath.DS.THEME.'*'.DS.'manifest.json')) {
            $DS = preg_quote(DS);

            foreach ($files as $file) {
                $file = realpath($file);
                if (preg_match('%^(.*'.$DS.'(\w+))'.$DS.'manifest\.json$%', $file, $mm) && $manifest = json_decode(txp_get_contents($file), true)) {
                    if (@$manifest['txp-type'] == 'textpattern-admin-theme' && is_file($mm[1].DS.$mm[2].'.php')) {
                        $manifest['title'] = empty($manifest['title']) ? ucwords($mm[2]) : $manifest['title'];
                        if ($format == 1) {
                            $out[$mm[2]] = $manifest['title'];
                        } elseif ($format == 2) {
                            $out[$mm[2]] = $manifest;
                        } else {
                            $out[] = $mm[2];
                        }
                    }
                }
            }
        }

        return $out;
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
                echo gTxt('cannot_instantiate_theme', array(
                    '{name}'  => $name,
                    '{class}' => "{$name}_theme",
                    '{path}'  => Theme::path($name),
                ));
            }

            return false;
        }

        return true;
    }

    /**
     * Sets Textpattern's menu structure, message contents and other application
     * states.
     *
     * @param  string $area     Currently active top level menu
     * @param  string $event    Currently active second level menu
     * @param  bool   $is_popup Just a popup window for tag builder et cetera
     * @param  array  $message  The contents of the notification message pane
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

                        $i_[] = array(
                            'label'  => $a,
                            'event'  => $b,
                            'active' => ($b == $event),
                        );
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
     * HTML &lt;head&gt; custom section.
     */

    public function html_head_custom()
    {
        $out = '';
        $prefs = $this->manifest('prefs');

        if (!empty($prefs['textpattern'])) {
            $content = json_encode($prefs['textpattern'], TEXTPATTERN_JSON);
            $out .= script_js("textpattern.prefs = jQuery.extend(textpattern.prefs, {$content})").n;
        }

        if (!empty($prefs['style'])) {
            $content = $prefs['style'];
            $out .= "<style>\n{$content}\n</style>".n;
        }

        // Custom CSS (see theme README for usage instructions).
        if (defined('admin_custom_css')) {
            $custom_css = admin_custom_css;
        } else {
            $custom_css = 'custom.css';
        }

        $custom = empty($this->cssPath) ? $custom_css : $this->cssPath.DS.$custom_css;
        if (is_readable(txpath.DS.THEME.$this->name.DS.$custom)) {
            $out .= '<link rel="stylesheet" href="'.$this->url.$custom.'">'.n;
        }

        // Custom JavaScript (see theme README for usage instructions).
        if (defined('admin_custom_js')) {
            $custom_js = admin_custom_js;
        } else {
            $custom_js = 'custom.js';
        }

        $custom = empty($this->jsPath) ? $custom_js : $this->jsPath.DS.$custom_js;
        if (is_readable(txpath.DS.THEME.$this->name.DS.$custom)) {
            $out .= '<script src="'.$this->url.$custom.'"></script>'.n;
        }

        return $out;
    }

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
     * @param   array $thing Message text and status flag
     * @param   bool  $modal If TRUE, immediate user interaction suggested
     * @return  string HTML
     * @example
     * global $theme;
     * echo $theme->announce(array('my_message', E_ERROR));
     */

    public function announce($thing = array('', 0), $modal = false)
    {
        return $this->_announce($thing, false, $modal);
    }

    /**
     * Output notification message for asynchronous JavaScript views.
     *
     * @param   array $thing Message text and status flag
     * @param   bool  $modal If TRUE, immediate user interaction suggested
     * @return  string JavaScript
     * @since   4.5.0
     * @example
     * global $theme;
     * echo script_js(
     *     $theme->announce_async(array('my_message', E_ERROR))
     * );
     */

    public function announce_async($thing = array('', 0), $modal = false)
    {
        return $this->_announce($thing, true, $modal);
    }

    /**
     * Output notification message for synchronous HTML and asynchronous JavaScript views.
     */

    private function _announce($thing, $async, $modal)
    {
        // $thing[0]: message text.
        // $thing[1]: message type, defaults to "success" unless empty or a different flag is set.

        if ($thing === '') {
            return '';
        }

        if (!is_array($thing) || !isset($thing[1])) {
            $thing = array($thing, 0);
        }

        if ($modal) {
            $js = 'window.alert("'.escape_js(strip_tags($thing[0])).'")';
        } else {
            // Try to inject $html into the message pane no matter when _announce()'s output is printed.
            $thing = json_encode($thing, TEXTPATTERN_JSON);
            $js = "textpattern.Console.addMessage({$thing})";
        }

        return $async ? $js : script_js(str_replace('</', '<\/', $js));
    }

    /**
     * Return details of this theme.
     *
     * All returned items are optional.
     *
     * @return array
     */

    public function manifest($type = 'manifest')
    {
        return json_decode(txp_get_contents($this->url.$type.'.json'), true);
    }
}

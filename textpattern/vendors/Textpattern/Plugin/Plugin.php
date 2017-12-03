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
 * Plugin
 *
 * @since   4.7.0
 * @package Plugin
 */

namespace Textpattern\Plugin;

class Plugin
{
    /**
     * Constructor.
     */

    public function __construct()
    {

    }

    /**
     * Install plugin to the database.
     *
     * If the plugin has been programmed to respond to lifecycle events,
     * the following callback is raised upon installation:
     *   plugin_lifecycle.{plugin_name} > installed
     *
     * @param  string $plugin Plugin_base64
     * @param  int    $status Plugin status
     *
     * @return string|array
     */

    public function install($plugin, $status = null)
    {
        if ($plugin = $this->extract($plugin)) {
            extract($plugin);

            $exists = fetch('name', 'txp_plugin', 'name', $name);

            if (isset($help_raw) && empty($plugin['allow_html_help'])) {
                // Default: help is in Textile format.
                $textile = new \Textpattern\Textile\Parser();
                $help = $textile->textileRestricted($help_raw, 0, 0);
            }

            $fields = "
                    type         = $type,
                    author       = '".doSlash($author)."',
                    author_uri   = '".doSlash($author_uri)."',
                    version      = '".doSlash($version)."',
                    description  = '".doSlash($description)."',
                    help         = '".doSlash($help)."',
                    code         = '".doSlash($code)."',
                    code_restore = '".doSlash($code)."',
                    code_md5     = '".doSlash($md5)."',
                    textpack     = '".@doSlash($textpack)."',
                    data         = '".@doSlash($data)."',
                    flags        = $flags
            ";

            if ($exists) {
                if ($status !== null) {
                    $fields .= ", status = ".(empty($status) ? 0 : 1);
                }
                $rs = safe_update(
                   'txp_plugin',
                    $fields,
                    "name        = '".doSlash($name)."'"
                );
            } else {
                $rs = safe_insert(
                   'txp_plugin',
                   "name         = '".doSlash($name)."',
                    status       = ".(empty($status) ? 0 : 1).",
                    load_order   = '".$order."',".
                    $fields
                );
            }

            if ($rs and $code) {
                $this->installTextpack($name, true);

                if ($flags & PLUGIN_LIFECYCLE_NOTIFY) {
                    load_plugin($name, true);
                    set_error_handler("pluginErrorHandler");
                    $message = callback_event("plugin_lifecycle.$name", 'installed');
                    restore_error_handler();
                }

                if (empty($message)) {
                    $message = gTxt('plugin_installed', array('{name}' => $name));
                }
            } else {
                $message = array(gTxt('plugin_install_failed', array('{name}' => $name)), E_ERROR);
            }
        }

        
        if (empty($message)) {
            $message = array(gTxt('bad_plugin_code'), E_ERROR);
        }

        return $message;
    }

    /**
     * Unpack a plugin from its base64-encoded/gzipped state.
     *
     * @param  string  $plugin    Plugin_base64
     * @param  boolean $normalize Check/normalize some fields
     * @return array
     */

    public function extract($plugin, $normalize = true)
    {
        if (strpos($plugin, '$plugin=\'') !== false) {
            @ini_set('pcre.backtrack_limit', '1000000');
            $plugin = preg_replace('@.*\$plugin=\'([\w=+/]+)\'.*@s', '$1', $plugin);
        }

        $plugin = preg_replace('/^#.*$/m', '', $plugin);
        $plugin = base64_decode($plugin);

        if (strncmp($plugin, "\x1F\x8B", 2) === 0) {
            $plugin = @gzinflate(substr($plugin, 10));
        }

        $plugin = @unserialize($plugin);

        if (empty($plugin['name'])) {

            return false;
        }

        if ($normalize) {
            $plugin['type']  = empty($plugin['type'])  ? 0 : min(max(intval($plugin['type']), 0), 5);
            $plugin['order'] = empty($plugin['order']) ? 5 : min(max(intval($plugin['order']), 1), 9);
            $plugin['flags'] = empty($plugin['flags']) ? 0 : intval($plugin['flags']);
        }

        return $plugin;
    }

    /**
     * Delete plugin from the database.
     *
     * If the plugin has been programmed to respond to lifecycle events,
     * the following callbacks are raised, in this order:
     *   plugin_lifecycle.{plugin_name} > disabled
     *   plugin_lifecycle.{plugin_name} > deleted
     *
     * @param  string $name Plugin name
     */

    public function delete($name)
    {
        if (! empty($name)) {
            if (safe_field("flags", 'txp_plugin', "name = '".doSlash($name)."'") & PLUGIN_LIFECYCLE_NOTIFY) {
                load_plugin($name, true);
                set_error_handler("pluginErrorHandler");
                callback_event("plugin_lifecycle.$name", 'disabled');
                callback_event("plugin_lifecycle.$name", 'deleted');
                restore_error_handler();
            }

            safe_delete('txp_plugin', "name = '".doSlash($name)."'");
            safe_delete('txp_lang', "owner = '".doSlash($name)."'");
        }
    }

    /**
     * Change plugin status: enabled or disabled.
     *
     * If the plugin has been programmed to respond to lifecycle events,
     * the following callbacks are raised depending on the given status:
     *   plugin_lifecycle.{plugin_name} > disabled
     *   plugin_lifecycle.{plugin_name} > enabled
     *
     * @param  string $name      Plugin name
     * @param  int    $setStatus Plugin status. Toggle status, if null
     */

    public function changeStatus($name, $setStatus = null)
    {
        if ($row = safe_row("flags, status", 'txp_plugin', "name = '".doSlash($name)."'")) {
            if ($row['flags'] & PLUGIN_LIFECYCLE_NOTIFY) {
                load_plugin($name, true);
                set_error_handler("pluginErrorHandler");

                if ($setStatus === null) {
                    callback_event("plugin_lifecycle.$name", $row['status'] ? 'disabled' : 'enabled');
                } else {
                    callback_event("plugin_lifecycle.$name", $setStatus ? 'enabled' : 'disabled');
                }

                restore_error_handler();
            }

            if ($setStatus === null) {
                $setStatus = "status = (1 - status)";
            } else {
                $setStatus = "status = ". ($setStatus ? 1 : 0);
            }

            safe_update('txp_plugin', $setStatus, "name = '".doSlash($name)."'");
        }
    }

    /**
     * Change plugin load priority.
     *
     * Plugins with a lower nunber are loaded first.
     *
     * @param  string $name  Plugin name
     * @param  int    $order Plugin load priority
     */

    public function changeOrder($name, $order)
    {
        $order = min(max(intval($order), 1), 9);
        safe_update('txp_plugin', "load_order = $order", "name = '".doSlash($name)."'");
    }

    /**
     * Install/update a plugin Textpack.
     *
     * The process may be intercepted (for example, to fetch data from the
     * filesystem) via the "api.plugin > textpack.fetch" callback.
     *
     * @param  string  $name   Plugin name
     * @param  boolean $reset  Delete old strings
     */

    public function installTextpack($name, $reset = false)
    {
        $owner = doSlash($name);

        if ($reset) {
            safe_delete('txp_lang', "owner = '{$owner}'");
        }

        if (has_handler('api.plugin', 'textpack.fetch')) {
            $textpack = callback_event('api.plugin', 'textpack.fetch', false, compact('name'));
        } else {
            $textpack = safe_field('textpack', 'txp_plugin', "name = '{$owner}'");
        }

        $textpack = $this->parseTextpack($textpack);

        if (empty($textpack)) {
            return;
        }

        if (! empty($textpack[TEXTPATTERN_DEFAULT_LANG])) {
            $fallback = TEXTPATTERN_DEFAULT_LANG;
        } else {
            // Get first language.
            reset($textpack);
            $fallback = key($textpack);
        }

        $installed_langs = \Txp::get('\Textpattern\L10n\Lang')->installed();

        foreach ($installed_langs as $lang) {
            if (empty($textpack[$lang])) {
                $langpack = $textpack[$fallback];
            } else {
                $langpack = array_merge($textpack[$fallback], $textpack[$lang]);
            }

            $lang = doSlash($lang);
            $exists = safe_column('name', 'txp_lang', "lang='{$lang}'");

            foreach ($langpack as $name => $item) {
                $name = doSlash($name);
                $event = doSlash($item['event']);
                $data = doSlash($item['data']);
                $fields = "lastmod = NOW(), data = '{$data}', event = '{$event}', owner = '{$owner}'";

                if (! empty($exists[$name])) {
                    safe_update(
                        'txp_lang',
                        $fields,
                        "lang = '{$lang}' AND name = '{$name}'"
                    );
                } else {
                    safe_insert(
                        'txp_lang',
                        $fields . ", lang = '{$lang}', name = '{$name}'"
                    );
                }
            }
        }
    }

    /**
     * Install/update ALL plugin Textpacks.
     *
     * Used when a new language is added.
     */

    public function installTextpacks()
    {
        if ($plugins = safe_column_num('name', 'txp_plugin', "textpack != '' ORDER BY load_order")) {
            foreach ($plugins as $name) {
                $this->installTextpack($name);
            }
        }
    }

    /**
     * Convert a Textpack to an associative array.
     *
     * @param  string       $textpack The Textpack
     * @return array An array of translations
     */

    public function parseTextpack($textpack)
    {
        $out = array();
        $language = TEXTPATTERN_DEFAULT_LANG;
        $event = '';

        $lines = explode(n, (string)$textpack);

        foreach ($lines as $line) {
            $line = trim($line);

            // A blank/comment line.
            if ($line === '' || preg_match('/^#[^@]/', $line, $m)) {
                continue;
            }

            // Set language.
            if (preg_match('/^#@language\s+(.+)$/', $line, $m)) {
                $language = \Txp::get('\Textpattern\L10n\Locale')->validLocale($m[1]);
                continue;
            }

            // Set event.
            if (preg_match('/^#@([a-zA-Z0-9_-]+)$/', $line, $m)) {
                $event = $m[1];
                continue;
            }

            if (preg_match('/^([\w\-]+)\s*=>\s*(.+)$/', $line, $m)) {
                if (!empty($m[1]) && !empty($m[2])) {
                    $out[$language][$m[1]] = array(
                        'event'   => $event,
                        'data'    => $m[2],
                    );
                }
            }
        }

        return $out;
    }

    /**
     * Fetch the plugin's 'data' field.
     *
     * The call can be intercepted (for example, to fetch data from the
     * filesystem) via the "api.plugin > data.fetch" callback.
     *
     * @param  string $name The plugin
     * @return string
     */

    public function fetchData($name)
    {
        if (has_handler('api.plugin', 'data.fetch')) {
            $data = callback_event('api.plugin', 'data.fetch', false, compact('name'));
        } else {
            $data = safe_field('data', 'txp_plugin', "name = '".doSlash($name)."'");
        }

        return $data;
    }
}

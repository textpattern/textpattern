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
 * Plugin
 *
 * @since   4.7.0
 * @package Plugin
 */

namespace Textpattern\Plugin;

class Plugin
{
    private static $metaData = array(
        'type'          => 0,
        'author'        => '',
        'author_uri'    => '',
        'version'       => '1',
        'description'   => '',
        'order'         => 5,
        'flags'         => 0
    );

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
     * @param string $plugin Plugin_base64
     * @param int    $status Plugin status
     *
     * @return string|array
     */

    public function install($plugin, $status = null, $write = true)
    {
        if ($encoded = !is_array($plugin)) {
            $plugin = $this->extract($plugin);
        }

        if (!empty($plugin['name'])) {
            extract($plugin + self::$metaData + array(
                'help'          => '',
                'code'          => '',
                'textpack'      => '',
                'data'          => ''
            ));

            $name = sanitizeForFile($name);
            $exists = safe_row('name, version', 'txp_plugin', "name='".doSlash($name)."'");
            isset($md5) or $md5 = md5($code);

            if (isset($help_raw) && empty($plugin['allow_html_help'])) {
                // Default: help is in Textile format.
                $textile = new \Textpattern\Textile\RestrictedParser();
                $help = $textile->setLite(false)->setImages(true)->parse($help_raw);
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
                    textpack     = '".doSlash($textpack)."',
                    data         = '".doSlash($data)."',
                    flags        = $flags
            ";

            if ($exists) {
                if (isset($status)) {
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

            if ($rs && ($code || !$encoded)) {
                $this->installTextpack($name, true);

                if ($write) {
                    $this->updateFile($name, $plugin);
                }

                if ($flags & PLUGIN_LIFECYCLE_NOTIFY) {
                    load_plugin($name, true);
                    set_error_handler("pluginErrorHandler");

                    if ($exists) {
                        $previous = $exists['version'];

                        if (version_compare($previous, $version, "<")) {
                            callback_event("plugin_lifecycle.$name", 'upgraded', '0', compact('previous', 'version'));
                        } elseif (version_compare($previous, $version, ">")) {
                            callback_event("plugin_lifecycle.$name", 'downgraded', '0', compact('previous', 'version'));
                        }
                    }

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

        set_error_handler(function () {}, E_WARNING|E_NOTICE);
        $plugin = unserialize($plugin, array());
        restore_error_handler();

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
     * Extract a section from plugin template.
     *
     * @param  string       $pack    Plugin template
     * @param  array|string $section Section
     * @return array
     */

    public function extractSection($pack, $section = 'CODE')
    {
        $result = array(false);

        foreach ((array)$section as $s) {
            $code = '';
            $pack = preg_split('/^\#\s*\-{3,}\s*BEGIN PLUGIN '.$s.'\s*\-{3,}\s*$(.*)^\#\s*\-{3,}\s*END PLUGIN '.$s.'\s*\-{3,}\s*$/Ums', $pack, -1, PREG_SPLIT_DELIM_CAPTURE);

            foreach ($pack as $i => $chunk) {
                if ($i % 2) {
                    $code .= $chunk;
                    $pack[$i] = '';
                }
            }

            $result[] = $code;
            $pack = implode('', $pack);
        }

        return array($pack) + $result;
    }

    /**
     * Read a plugin from file.
     *
     * @param  string|array $name|$path Plugin name
     * @param  boolean      $normalize  Check/normalize some fields
     * @return array
     */

    public function read($name, $normalize = true)
    {
        global $txp_user;

        $plugin = array();

        if (is_array($name)) {
            list($name, $target_path) = $name + array(null, null);
        } else {
            $name = sanitizeForFile($name);
            $dir = PLUGINPATH.DS.$name;

            if (!is_dir($dir)) {
                return false;
            }

            $dir .= DS;
            $target_path = $dir.$name.'.php';
        }

        if (!($pack = txp_get_contents($target_path))) {
            return false;
        }

        list($pack, $code, $help_raw) = $this->extractSection($pack, array('CODE', 'HELP'));
        $plugin = array_filter(compact('code', 'help_raw'));

        if (!empty($code)) {
            file_put_contents($target_path, $pack);
            include $target_path;
        } else {
            $code = preg_replace('/^\s*<\?(?:php)?\s*|\s*\?>\s*$/i', '', $pack);
            $plugin['code'] = $code;
        }

        if (!empty($dir)) {
            if ($info = txp_get_contents($dir.'manifest.json')) {
                $plugin += json_decode($info, true);
            }

            if ($textpack = txp_get_contents($dir.'textpack.txp')) {
                $plugin['textpack'] = $textpack;
            }

            if ($data = txp_get_contents($dir.'data.txp')) {
                $plugin['data'] = $data;
            }

            if ($help = txp_get_contents($dir.'help.html')) {
                $plugin['help'] = $help;
            } elseif ($help = txp_get_contents($dir.'help.textile')) {
                $plugin['help_raw'] = $help;
            }
        }

        $plugin += array('name' => $name, 'author' => get_author_name($txp_user));

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
     * @param string $name Plugin name
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
            gps('sync') and $this->updateFile($name, null);
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
     * @param string $name      Plugin name
     * @param int    $setStatus Plugin status. Toggle status, if null
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
     * Plugins with a lower number are loaded first.
     *
     * @param string $name  Plugin name
     * @param int    $order Plugin load priority
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
     * filesystem) via the "txp.plugin > textpack.fetch" callback.
     *
     * @param string  $name  Plugin name
     * @param boolean $reset Delete old strings
     */

    public function installTextpack($name, $reset = false)
    {
        $owner = doSlash($name);

        if ($reset) {
            safe_delete('txp_lang', "owner = '{$owner}'");
        }

        if (has_handler('txp.plugin', 'textpack.fetch')) {
            $textpack = callback_event('txp.plugin', 'textpack.fetch', false, compact('name'));
        } else {
            $textpack = safe_field('textpack', 'txp_plugin', "name = '{$owner}'");
        }

        $packParser = \Txp::get('\Textpattern\Textpack\Parser');
        $packParser->parse($textpack);
        $packLanguages = $packParser->getLanguages();

        if (empty($packLanguages)) {
            return;
        }

        $allpacks = array();

        foreach ($packLanguages as $lang_code) {
            $allpacks[$lang_code] = $packParser->getStrings($lang_code);
        }

        if (in_array(TEXTPATTERN_DEFAULT_LANG, $packLanguages)) {
            $fallback = TEXTPATTERN_DEFAULT_LANG;
        } else {
            // Use first language as default if possible.
            $fallback = !empty($packLanguages[0]) ? $packLanguages[0] : TEXTPATTERN_DEFAULT_LANG;
        }

        $installed_langs = \Txp::get('\Textpattern\L10n\Lang')->installed();

        foreach ($installed_langs as $lang) {
            if (!isset($allpacks[$lang])) {
                $langpack = $allpacks[$fallback];
            } else {
                $langpack = array();
                $done = array();

                // Manual merge since array_merge/array_merge_recursive don't work as expected
                // on these multi-dimensional structures.
                // There must be a more efficient way to do this...
                foreach ($allpacks[$fallback] as $idx => $packEntry) {
                    if (isset($allpacks[$lang][$idx]['name']) && $allpacks[$lang][$idx]['name'] === $packEntry['name']) {
                        // Great! keys in the same order.
                        $done[] = $idx;
                        $langpack[] = $allpacks[$lang][$idx];
                    } else {
                        // Drat, gotta search for it.
                        $found = false;

                        foreach ($allpacks[$lang] as $offset => $packSet) {
                            if (in_array($offset, $done)) {
                                continue;
                            }

                            if ($packSet['name'] === $packEntry['name']) {
                                $langpack[] = $packSet;
                                $found = true;
                                $done[] = $offset;
                                break;
                            }
                        }

                        if (!$found) {
                            $langpack[] = $packEntry;
                        }
                    }
                }
            }

            // Ensure the language code in the pack, which may contain fallback strings,
            // reflects the desired (to be installed) language code.
            foreach ($langpack as $idx => $packBlock) {
                $langpack[$idx]['lang'] = $lang;
            }

            \Txp::get('\Textpattern\L10n\Lang')->upsertPack($langpack, $name);
            $langDir = PLUGINPATH.DS.$name.DS.'lang'.DS;

            if (is_dir($langDir) && is_readable($langDir)) {
                $plugLang = new \Textpattern\L10n\Lang($langDir);
                $plugLang->installFile($lang, $name);
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
     * Create/update/delete plugin file.
     *
     * @param  string $name The plugin
     * @param  string $code The code
     */

    public function updateFile($name, $code = null)
    {
        if (!is_writable(PLUGINPATH)) {
            return;
        }

        $filename = sanitizeForFile($name);

        if (!isset($code)) {
            return \Txp::get('\Textpattern\Admin\Tools')->removeFiles(PLUGINPATH, $filename);
        }

        if (!is_dir($dir = PLUGINPATH.DS.$filename)) {
            mkdir($dir);
        }

        if (is_array($code)) {
            if ($manifest = array_intersect_key($code, self::$metaData)) {
                file_put_contents($dir.DS.'manifest.json', json_encode($manifest), LOCK_EX);
            }

            foreach (array(
                'help' => 'help.html',
                'help_raw' => 'help.textile',
                'textpack' => 'textpack.txp',
                'data' => 'data.txp'
                ) as $key => $file
            ) {
                if (isset($code[$key])) {
                    file_put_contents($dir.DS.$file, $code[$key], LOCK_EX);
                }
            }

            $code = isset($code['code']) ? $code['code'] : '';
        }

        return file_put_contents($dir.DS.$filename.'.php', '<?php'.n.$code, LOCK_EX);
    }

    /**
     * Fetch the plugin's 'data' field.
     *
     * The call can be intercepted (for example, to fetch data from the
     * filesystem) via the "txp.plugin > data.fetch" callback.
     *
     * @param  string $name The plugin
     * @return string
     */

    public function fetchData($name)
    {
        if (has_handler('txp.plugin', 'data.fetch')) {
            $data = callback_event('txp.plugin', 'data.fetch', false, compact('name'));
        } else {
            $data = safe_field('data', 'txp_plugin', "name = '".doSlash($name)."'");
        }

        return $data;
    }
}

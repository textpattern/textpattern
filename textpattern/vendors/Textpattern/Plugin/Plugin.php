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
     *
     */

    public function __construct()
    {

    }

    /**
     * Install plugin
     *
     * @param  string       $plugin   Plugin_base64
     * @param  int          $status   Plugin status
     *
     * @return string|array
     */

    public function install($plugin, $status = null)
    {
        $plugin = assert_string($plugin);

        if (strpos($plugin, '$plugin=\'') !== false) {
            @ini_set('pcre.backtrack_limit', '1000000');
            $plugin = preg_replace('@.*\$plugin=\'([\w=+/]+)\'.*@s', '$1', $plugin);
        }

        $plugin = preg_replace('/^#.*$/m', '', $plugin);

        if (trim($plugin)) {
            $plugin = base64_decode($plugin);

            if (strncmp($plugin, "\x1F\x8B", 2) === 0) {
                $plugin = gzinflate(substr($plugin, 10));
            }

            if ($plugin = unserialize($plugin)) {
                if (is_array($plugin)) {
                    extract($plugin);

                    $type = empty($type) ? 0 : min(max(intval($type), 0), 5);
                    $order = empty($order) ? 5 : min(max(intval($order), 1), 9);
                    $flags = empty($flags) ? 0 : intval($flags);
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
                        \Txp::get('\Textpattern\L10n\Lang')->install_textpack_plugin($name);

                        if ($flags & PLUGIN_LIFECYCLE_NOTIFY) {
                            load_plugin($name, true);
                            $message = callback_event("plugin_lifecycle.$name", 'installed');
                        }

                        if (empty($message)) {
                            $message = gTxt('plugin_installed', array('{name}' => $name));
                        }
                    } else {
                        $message = array(gTxt('plugin_install_failed', array('{name}' => $name)), E_ERROR);
                    }
                }
            }
        }
        
        if (empty($message)) {
            $message = array(gTxt('bad_plugin_code'), E_ERROR);
        }

        return $message;
    }

    /**
     * Delete plugin
     *
     * @param  string       $name       Plugin name
     */

    public function delete($name)
    {
        if (! empty($name)) {
            if (safe_field("flags", 'txp_plugin', "name = '".doSlash($name)."'") & PLUGIN_LIFECYCLE_NOTIFY) {
                load_plugin($name, true);
                callback_event("plugin_lifecycle.$name", 'disabled');
                callback_event("plugin_lifecycle.$name", 'deleted');
            }
            safe_delete('txp_plugin', "name = '".doSlash($name)."'");
            safe_delete('txp_lang', "owner = '".doSlash($name)."'");
        }
    }

    /**
     * Change plugin status
     *
     * @param  string       $name       Plugin name
     * @param  int          $setStatus  Plugin status. Toggle status, if null
     */

    public function changestatus($name, $setStatus = null)
    {
        if ($row = safe_row("flags, status", 'txp_plugin', "name = '".doSlash($name)."'")) {
            if ($row['flags'] & PLUGIN_LIFECYCLE_NOTIFY) {
                load_plugin($name, true);
                // Note: won't show returned messages anywhere due to
                // potentially overwhelming verbiage.
                if ($setStatus === null) {
                    callback_event("plugin_lifecycle.$name", $row['status'] ? 'disabled' : 'enabled');
                } else {
                    callback_event("plugin_lifecycle.$name", $setStatus ? 'enabled' : 'disabled');
                }
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
     * Change plugin load priority
     *
     * @param  string       $name       Plugin name
     * @param  int          $order      Plugin load priority
     */

    public function changeorder($name, $order)
    {
        $order = min(max(intval(ps('order')), 1), 9);
        safe_update('txp_plugin', "load_order = $order", "name = '".doSlash($name)."'");
    }



}

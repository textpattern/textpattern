<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
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
 * Plugins panel.
 *
 * @package Admin\Plugin
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'plugin') {
    require_privs('plugin');

    $available_steps = array(
        'plugin_edit'       => true,
        'plugin_help'       => false,
        'plugin_list'       => false,
        'plugin_install'    => true,
        'plugin_save'       => true,
        'plugin_verify'     => true,
        'switch_status'     => true,
        'plugin_multi_edit' => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        plugin_list();
    }
}

/**
 * The main panel listing all installed plugins.
 *
 * @param string|array $message The activity message
 */

function plugin_list($message = '')
{
    global $event;

    pagetop(gTxt('tab_plugins'), $message);

    extract(gpsa(array(
        'sort',
        'dir',
    )));

    if ($sort === '') {
        $sort = get_pref('plugin_sort_column', 'name');
    } else {
        if (!in_array($sort, array('name', 'status', 'author', 'version', 'modified', 'load_order'))) {
            $sort = 'name';
        }

        set_pref('plugin_sort_column', $sort, 'plugin', 2, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('plugin_sort_dir', 'asc');
    } else {
        $dir = ($dir == 'desc') ? "desc" : "asc";
        set_pref('plugin_sort_dir', $dir, 'plugin', 2, '', 0, PREF_PRIVATE);
    }

    $sort_sql = "$sort $dir";

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_plugins'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        ).
        n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => $event.'_container',
        )).
        n.tag(plugin_form(), 'div', array('class' => 'txp-control-panel'));

    $rs = safe_rows_start(
        "name, status, author, author_uri, version, description, length(help) AS help, ABS(STRCMP(MD5(code), code_md5)) AS modified, load_order, flags",
        'txp_plugin',
        "1 = 1 ORDER BY $sort_sql"
    );

    if ($rs and numRows($rs) > 0) {
        echo
            n.tag_start('form', array(
                'class'  => 'multi_edit_form',
                'id'     => 'plugin_form',
                'name'   => 'longform',
                'method' => 'post',
                'action' => 'index.php',
            )).
            n.tag_start('div', array('class' => 'txp-listtables')).
            n.tag_start('table', array('class' => 'txp-list')).
            n.tag_start('thead').
            tr(
                hCell(
                    fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                        '', ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                ).
                column_head(
                    'plugin', 'name', 'plugin', true, $switch_dir, '', '',
                        (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                ).
                column_head(
                    'author', 'author', 'plugin', true, $switch_dir, '', '',
                        (('author' == $sort) ? "$dir " : '').'txp-list-col-author'
                ).
                column_head(
                    'version', 'version', 'plugin', true, $switch_dir, '', '',
                        (('version' == $sort) ? "$dir " : '').'txp-list-col-version'
                ).
                column_head(
                    'plugin_modified', 'modified', 'plugin', true, $switch_dir, '', '',
                        (('modified' == $sort) ? "$dir " : '').'txp-list-col-modified'
                ).
                hCell(gTxt(
                    'description'), '', ' class="txp-list-col-description" scope="col"'
                ).
                column_head(
                    'active', 'status', 'plugin', true, $switch_dir, '', '',
                        (('status' == $sort) ? "$dir " : '').'txp-list-col-status'
                ).
                column_head(
                    'order', 'load_order', 'plugin', true, $switch_dir, '', '',
                        (('load_order' == $sort) ? "$dir " : '').'txp-list-col-load-order'
                ).
                hCell(
                    gTxt('manage'), '',  ' class="txp-list-col-manage" scope="col"'
                )
            ).
            n.tag_end('thead').
            n.tag_start('tbody');

        while ($a = nextRow($rs)) {
            foreach ($a as $key => $value) {
                $$key = txpspecialchars($value);
            }

            // Fix up the description for clean cases.
            $description = preg_replace(
                array(
                    '#&lt;br /&gt;#',
                    '#&lt;(/?(a|b|i|em|strong))&gt;#',
                    '#&lt;a href=&quot;(https?|\.|\/|ftp)([A-Za-z0-9:/?.=_]+?)&quot;&gt;#',
                ),
                array(
                    '<br />',
                    '<$1>',
                    '<a href="$1$2">',
                ),
                $description
            );

            if (!empty($help)) {
                $help = href(gTxt('help'), array(
                    'event' => 'plugin',
                    'step'  => 'plugin_help',
                    'name'  => $name,
                ), array('class' => 'plugin-help'));
            }

            if ($flags & PLUGIN_HAS_PREFS) {
                $plugin_prefs = span(
                    sp.span('&#124;', array('role' => 'separator')).
                    sp.href(gTxt('plugin_prefs'), array('event' => 'plugin_prefs.'.$name)),
                    array('class' => 'plugin-prefs')
                );
            } else {
                $plugin_prefs = '';
            }

            $manage = array();

            if ($help) {
                $manage[] = $help;
            }

            if ($plugin_prefs) {
                $manage[] = $plugin_prefs;
            }

            $manage_items = ($manage) ? join($manage) : '-';
            $edit_url = eLink('plugin', 'plugin_edit', 'name', $name, $name);

            echo tr(
                td(
                    fInput('checkbox', 'selected[]', $name), '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    $edit_url, '', ' class="txp-list-col-name" scope="row"'
                ).
                td(
                    href($author, $a['author_uri'], array('rel' => 'external')), '', 'txp-list-col-author'
                ).
                td(
                    $version, '', 'txp-list-col-version'
                ).
                td(
                    ($modified ? span(gTxt('yes'), array('class' => 'warning')) : ''), '', 'txp-list-col-modified'
                ).
                td(
                    $description, '', 'txp-list-col-description'
                ).
                td(
                    status_link($status, $name, yes_no($status)), '', 'txp-list-col-status'
                ).
                td(
                    $load_order, '', 'txp-list-col-load-order'
                ).
                td(
                    $manage_items, '', 'txp-list-col-manage'
                ),
                $status ? ' class="active"' : ''
            );

            unset($name, $page, $deletelink);
        }

        echo
            n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div'). // End of .txp-listtables.
            plugin_multiedit_form('', $sort, $dir, '', '').
            tInput().
            n.tag_end('form');
    }

    echo n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.
}

/**
 * Toggles a plugin's status.
 */

function switch_status()
{
    extract(array_map('assert_string', gpsa(array('thing', 'value'))));
    $change = ($value == gTxt('yes')) ? 0 : 1;

    safe_update('txp_plugin', "status = $change", "name = '".doSlash($thing)."'");

    if (safe_field('flags', 'txp_plugin', "name = '".doSlash($thing)."'") & PLUGIN_LIFECYCLE_NOTIFY) {
        load_plugin($thing, true);
        $message = callback_event("plugin_lifecycle.$thing", $change ? 'enabled' : 'disabled');
    }

    echo gTxt($change ? 'yes' : 'no');
}

/**
 * Renders and outputs the plugin editor panel.
 */

function plugin_edit()
{
    global $event;

    $name = gps('name');
    pagetop(gTxt('edit_plugins'));

    echo plugin_edit_form($name);
}

/**
 * Plugin help viewer panel.
 */

function plugin_help()
{
    global $event;

    $name = gps('name');
    pagetop(gTxt('plugin_help'));
    $help = ($name) ? safe_field('help', 'txp_plugin', "name = '".doSlash($name)."'") : '';
    echo n.tag($help, 'div', array('class' => 'txp-layout-textbox'));
}

/**
 * Renders an editor form for plugins.
 *
 * @param  string $name The plugin
 * @return string HTML
 */

function plugin_edit_form($name = '')
{
    assert_string($name);
    $code = ($name) ? fetch('code', 'txp_plugin', 'name', $name) : '';
    $thing = ($code) ? $code : '';

    return
        form(
            hed(gTxt('edit_plugin', array('{name}' => $name)), 2).
            graf('<textarea class="code" id="plugin_code" name="code" cols="'.INPUT_XLARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($thing).'</textarea>', ' class="edit-plugin-code"').
            graf(
                sLink('plugin', '', gTxt('cancel'), 'txp-button').
                fInput('submit', '', gTxt('save'), 'publish'),
                array('class' => 'txp-edit-actions')
            ).
            eInput('plugin').
            sInput('plugin_save').
            hInput('name', $name), '', '', 'post', '', '', 'plugin_details');
}

/**
 * Saves edited plugin code.
 */

function plugin_save()
{
    extract(doSlash(array_map('assert_string', gpsa(array('name', 'code')))));

    safe_update('txp_plugin', "code = '$code'", "name = '$name'");

    $message = gTxt('plugin_saved', array('{name}' => $name));

    plugin_list($message);
}

/**
 * Renders a status link.
 *
 * @param  string $status   The new status
 * @param  string $name     The plugin
 * @param  string $linktext The label
 * @return string HTML
 * @access private
 * @see    asyncHref()
 */

function status_link($status, $name, $linktext)
{
    return asyncHref(
        $linktext,
        array('step' => 'switch_status', 'thing' => $name)
    );
}

/**
 * Plugin installation's preview step.
 *
 * Outputs a panel displaying the plugin's source code
 * and the included help file.
 */

function plugin_verify()
{
    global $event;

    if (ps('txt_plugin')) {
        $plugin = join("\n", file($_FILES['theplugin']['tmp_name']));
    } else {
        $plugin = assert_string(ps('plugin'));
    }

    // Check for pre-4.0 style plugin.
    if (strpos($plugin, '$plugin=\'') !== false) {
        // Try to increase PCRE's backtrack limit in PHP 5.2+ to accommodate to
        // x-large plugins. See https://bugs.php.net/bug.php?id=40846.
        @ini_set('pcre.backtrack_limit', '1000000');
        $plugin = preg_replace('@.*\$plugin=\'([\w=+/]+)\'.*@s', '$1', $plugin);
        // Have we hit yet another PCRE restriction?
        if ($plugin === null) {
            plugin_list(array(gTxt('plugin_pcre_error', array('{errno}' => preg_last_error())), E_ERROR));

            return;
        }
    }

    // Strip out #comment lines.
    $plugin = preg_replace('/^#.*$/m', '', $plugin);

    if ($plugin === null) {
        plugin_list(array(gTxt('plugin_pcre_error', array('{errno}' => preg_last_error())), E_ERROR));

        return;
    }

    if (isset($plugin)) {
        $plugin_encoded = $plugin;
        $plugin = base64_decode($plugin);

        if (strncmp($plugin, "\x1F\x8B", 2) === 0) {
            if (function_exists('gzinflate')) {
                $plugin = gzinflate(substr($plugin, 10));
            } else {
                plugin_list(array(gTxt('plugin_compression_unsupported'), E_ERROR));

                return;
            }
        }

        if ($plugin = @unserialize($plugin)) {
            if (is_array($plugin)) {
                $source = '';

                if (isset($plugin['help_raw']) && empty($plugin['allow_html_help'])) {
                    $textile = new \Textpattern\Textile\Parser();
                    $help_source = $textile->textileRestricted($plugin['help_raw'], 0, 0);
                } else {
                    $help_source = highlight_string($plugin['help'], true);
                }

                $source .= highlight_string('<?php'.$plugin['code'].'?>', true);
                $sub = graf(
                    sLink('plugin', '', gTxt('cancel'), 'txp-button').
                    fInput('submit', '', gTxt('install'), 'publish'),
                    array('class' => 'txp-edit-actions')
                );

                pagetop(gTxt('verify_plugin'));
                echo form(
                    hed(gTxt('previewing_plugin'), 2).
                    tag($source, 'div', ' class="code" id="preview-plugin" dir="ltr"').
                    hed(gTxt('plugin_help').':', 2).
                    tag($help_source, 'div', ' class="code" id="preview-help" dir="ltr"').
                    $sub.
                    sInput('plugin_install').
                    eInput('plugin').
                    hInput('plugin64', $plugin_encoded), '', '', 'post', 'plugin-info', '', 'plugin_preview'
                );

                return;
            }
        }
    }

    plugin_list(array(gTxt('bad_plugin_code'), E_ERROR));
}

/**
 * Installs a plugin.
 */

function plugin_install()
{
    $plugin = assert_string(ps('plugin64'));

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

                if ($exists) {
                    $rs = safe_update(
                       'txp_plugin',
                        "type        = $type,
                        author       = '".doSlash($author)."',
                        author_uri   = '".doSlash($author_uri)."',
                        version      = '".doSlash($version)."',
                        description  = '".doSlash($description)."',
                        help         = '".doSlash($help)."',
                        code         = '".doSlash($code)."',
                        code_restore = '".doSlash($code)."',
                        code_md5     = '".doSlash($md5)."',
                        flags        = $flags",
                        "name        = '".doSlash($name)."'"
                    );
                } else {
                    $rs = safe_insert(
                       'txp_plugin',
                       "name         = '".doSlash($name)."',
                        status       = 0,
                        type         = $type,
                        author       = '".doSlash($author)."',
                        author_uri   = '".doSlash($author_uri)."',
                        version      = '".doSlash($version)."',
                        description  = '".doSlash($description)."',
                        help         = '".doSlash($help)."',
                        code         = '".doSlash($code)."',
                        code_restore = '".doSlash($code)."',
                        code_md5     = '".doSlash($md5)."',
                        load_order   = '".$order."',
                        flags        = $flags"
                    );
                }

                if ($rs and $code) {
                    if (!empty($textpack)) {
                        // Plugins tag their Textpack by plugin name.
                        // The ownership may be overridden in the Textpack itself.
                        $textpack = "#@owner {$name}".n.$textpack;
                        install_textpack($textpack, false);
                    }

                    if ($flags & PLUGIN_LIFECYCLE_NOTIFY) {
                        load_plugin($name, true);
                        $message = callback_event("plugin_lifecycle.$name", 'installed');
                    }

                    if (empty($message)) {
                        $message = gTxt('plugin_installed', array('{name}' => $name));
                    }

                    plugin_list($message);

                    return;
                } else {
                    $message = array(gTxt('plugin_install_failed', array('{name}' => $name)), E_ERROR);
                    plugin_list($message);

                    return;
                }
            }
        }
    }

    plugin_list(array(gTxt('bad_plugin_code'), E_ERROR));
}

/**
 * Renders a plugin installation form.
 *
 * @return string HTML
 * @access private
 * @see    form()
 */

function plugin_form()
{
    return form(
        tag(gTxt('install_plugin'), 'label', ' for="plugin-install"').popHelp('install_plugin').
        '<textarea class="code" id="plugin-install" name="plugin" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'" dir="ltr"></textarea>'.
        fInput('submit', 'install_new', gTxt('upload')).
        eInput('plugin').
        sInput('plugin_verify'), '', '', 'post', 'plugin-data', '', 'plugin_install_form');
}

/**
 * Renders a multi-edit form widget for plugins.
 *
 * @param  int    $page          The current page
 * @param  string $sort          The sort criteria
 * @param  string $dir           The sort direction
 * @param  string $crit          The search term
 * @param  string $search_method The search method
 * @return string HTML
 */

function plugin_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    $orders = selectInput('order', array(
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
    ), 5, false);

    $methods = array(
        'changestatus' => gTxt('changestatus'),
        'changeorder'  => array('label' => gTxt('changeorder'), 'html' => $orders),
        'delete'       => gTxt('delete'),
    );

    return multi_edit($methods, 'plugin', 'plugin_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

/**
 * Processes multi-edit actions.
 */

function plugin_multi_edit()
{
    $selected = ps('selected');
    $method = assert_string(ps('edit_method'));

    if (!$selected or !is_array($selected)) {
        return plugin_list();
    }

    $where = "name IN ('".join("','", doSlash($selected))."')";

    switch ($method) {
        case 'delete':
            foreach ($selected as $name) {
                if (safe_field("flags", 'txp_plugin', "name = '".doSlash($name)."'") & PLUGIN_LIFECYCLE_NOTIFY) {
                    load_plugin($name, true);
                    callback_event("plugin_lifecycle.$name", 'disabled');
                    callback_event("plugin_lifecycle.$name", 'deleted');
                }
            }
            // Remove plugins.
            safe_delete('txp_plugin', $where);
            // Remove plugin's l10n strings.
            safe_delete('txp_lang', "owner IN ('".join("','", doSlash($selected))."')");
            break;
        case 'changestatus':
            foreach ($selected as $name) {
                if (safe_field("flags", 'txp_plugin', "name = '".doSlash($name)."'") & PLUGIN_LIFECYCLE_NOTIFY) {
                    $status = safe_field("status", 'txp_plugin', "name = '".doSlash($name)."'");
                    load_plugin($name, true);
                    // Note: won't show returned messages anywhere due to
                    // potentially overwhelming verbiage.
                    callback_event("plugin_lifecycle.$name", $status ? 'disabled' : 'enabled');
                }
            }
            safe_update('txp_plugin', "status = (1 - status)", $where);
            break;
        case 'changeorder':
            $order = min(max(intval(ps('order')), 1), 9);
            safe_update('txp_plugin', "load_order = $order", $where);
            break;
    }

    $message = gTxt('plugin_'.($method == 'delete' ? 'deleted' : 'updated'), array('{name}' => join(', ', $selected)));

    plugin_list($message);
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2018 The Textpattern Development Team
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
                    'modified', 'modified', 'plugin', true, $switch_dir, '', '',
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
                    gTxt('manage'), '', ' class="txp-list-col-manage" scope="col"'
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
                    sp.href(gTxt('options'), array('event' => 'plugin_prefs.'.$name)),
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

    Txp::get('\Textpattern\Plugin\Plugin')->changestatus($thing, $change);

    echo gTxt($change ? 'yes' : 'no');
}

/**
 * Renders and outputs the plugin editor panel.
 */

function plugin_edit()
{
    $name = gps('name');
    pagetop(gTxt('edit_plugins'));

    echo plugin_edit_form($name);
}

/**
 * Plugin help viewer panel.
 */

function plugin_help()
{
    $name = gps('name');

    // Note that TEXTPATTERN_DEFAULT_LANG is not used here.
    // The assumption is that plugin help is in English, unless otherwise stated.
    $default_lang = $lang_plugin = 'en';

    pagetop(gTxt('plugin_help'));
    $help = ($name) ? safe_field('help', 'txp_plugin', "name = '".doSlash($name)."'") : '';
    $helpArray = do_list($help, n);

    if (preg_match('/^#@language\s+(.+)$/', $helpArray[0], $m)) {
        $lang_plugin = $m[1];
        $help = implode(n, array_slice($helpArray, 1));
    }

    if ($lang_plugin !== $default_lang) {
        $direction = safe_field('data', 'txp_lang', "lang = '".doSlash($lang_plugin)."' AND name='lang_dir'");
    }

    if (empty($direction) || !in_array($direction, array('ltr', 'rtl'))) {
        $direction = 'ltr';
    }

    echo n.tag($help, 'div', array(
        'class' => 'txp-layout-textbox',
        'lang'  => $lang_plugin,
        'dir'   => $direction,
    ));
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
        array(
            'step'  => 'switch_status',
            'thing' => $name,
        )
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
    if (ps('txt_plugin')) {
        $plugin64 = join("\n", file($_FILES['theplugin']['tmp_name']));
    } else {
        $plugin64 = assert_string(ps('plugin'));
    }

    if ($plugin = Txp::get('\Textpattern\Plugin\Plugin')->extract($plugin64)) {
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
            hInput('plugin64', $plugin64), '', '', 'post', 'plugin-info', '', 'plugin_preview'
        );

        return;
    }

    plugin_list(array(gTxt('bad_plugin_code'), E_ERROR));
}

/**
 * Installs a plugin.
 */

function plugin_install()
{
    $plugin64 = assert_string(ps('plugin64'));
    $message = Txp::get('\Textpattern\Plugin\Plugin')->install($plugin64);

    plugin_list($message);
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
        '<textarea class="code" id="plugin-install" name="plugin" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'" dir="ltr" required="required"></textarea>'.
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
        'changestatus' => array(
            'label' => gTxt('changestatus'),
            'html'  => onoffRadio('setStatus', 1),
        ),
        'changeorder'  => array(
            'label' => gTxt('changeorder'),
            'html'  => $orders,
        ),
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

    $plugin = new \Textpattern\Plugin\Plugin();

    switch ($method) {
        case 'delete':
            foreach ($selected as $name) {
                $plugin->delete($name);
            }
            break;
        case 'changestatus':
            foreach ($selected as $name) {
                $plugin->changeStatus($name, ps('setStatus'));
            }
            break;
        case 'changeorder':
            foreach ($selected as $name) {
                $plugin->changeOrder($name, ps('order'));
            }
            break;
    }

    $message = gTxt('plugin_'.($method == 'delete' ? 'deleted' : 'updated'), array('{name}' => join(', ', $selected)));

    plugin_list($message);
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2024 The Textpattern Development Team
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

use Textpattern\Search\Filter;

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
        'plugin_upload'     => true,
        'plugin_import'     => true,
        'plugin_compile'    => true,
        'plugin_export'     => true,
        'plugin_verify'     => true,
        'switch_status'     => true,
        'plugin_multi_edit' => true,
        'plugin_change_pageby' => true,
    );

    if ($step && bouncer($step, $available_steps) && is_callable($step)) {
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
            'page',
            'sort',
            'dir',
            'crit',
            'search_method',
    )));

    $now = time();
    $lastCheck = json_decode(get_pref('last_plugin_update_check', ''), true);

    if (empty($lastCheck) || $now > ($lastCheck['when'] + (60 * 60))) {
        $lastCheck = checkPluginUpdates();
    }

    if ($sort === '') {
        $sort = get_pref('plugin_sort_column', 'name');
    } else {
        if (!in_array($sort, array('name', 'status', 'author', 'version', 'load_order'))) {
            $sort = 'name';
        }

        set_pref('plugin_sort_column', $sort, 'plugin', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('plugin_sort_dir', 'asc');
    } else {
        $dir = ($dir == 'desc') ? "desc" : "asc";
        set_pref('plugin_sort_dir', $dir, 'plugin', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    $sort_sql = "$sort $dir".($sort == 'name' ? '' : ", name");
    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'name' => array(
                'column' => 'txp_plugin.name',
                'label'  => gTxt('plugin'),
            ),
            'author' => array(
                'column' => 'txp_plugin.author',
                'label'  => gTxt('author'),
            ),
            'author_uri' => array(
                'column' => 'txp_plugin.author_uri',
                'label'  => gTxt('website'),
            ),
            'description' => array(
                'column' => 'txp_plugin.description',
                'label'  => gTxt('description'),
            ),
            'version' => array(
                'column' => 'txp_plugin.version',
                'label'  => gTxt('version'),
                'type'   => 'numeric',
            ),
            'code' => array(
                'column' => 'txp_plugin.code',
                'label'  => gTxt('code'),
            ),
            'help' => array(
                'column' => 'txp_plugin.help',
                'label'  => gTxt('help'),
            ),
            'textpack' => array(
                'column' => 'txp_plugin.textpack',
                'label'  => 'Textpack',
            ),
            'status' => array(
                'column' => 'txp_plugin.status',
                'label'  => gTxt('active'),
                'type'   => 'boolean',
            ),
            'type' => array(
                'column' => 'txp_plugin.type',
                'label'  => gTxt('type'),
                'type'   => 'numeric',
            ),
            'load_order' => array(
                'column' => 'txp_plugin.load_order',
                'label'  => gTxt('order'),
                'type'   => 'numeric',
            ),
        )
    );

    $alias_yes = '1, Yes';
    $alias_no = '0, No';
    $search->setAliases('status', array($alias_no, $alias_yes));

    list($criteria, $crit, $search_method) = $search->getFilter();

    $search_render_options = array('placeholder' => 'search_plugins');
    $total = safe_count('txp_plugin', $criteria);

    $searchBlock =
        n.tag(
            $search->renderForm('plugin', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $contentBlock = '';
    $existing_files = get_filenames(PLUGINPATH.DS, GLOB_ONLYDIR) or $existing_files = array();

    foreach ($installed = safe_column_num('name', 'txp_plugin', 1) as $name) {
        unset($existing_files[$name]);
    }

    $paginator = new \Textpattern\Admin\Paginator($event, 'plugin');
    $limit = $paginator->getLimit();

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    if ($total < 1) {
        if ($crit !== '') {
            $contentBlock .= graf(
                span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                gTxt('no_results_found'),
                array('class' => 'alert-block information')
            );
        }
    } else {
        $rs = safe_rows_start(
            "name, status, author, author_uri, version, description, length(help) AS help, ABS(STRCMP(MD5(code), code_md5)) AS modified, load_order, flags, type",
            'txp_plugin',
            "$criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        $publicOn = get_pref('use_plugins');
        $adminOn = get_pref('admin_side_plugins');

        $contentBlock .=
            n.tag_start('form', array(
                'class'  => 'multi_edit_form',
                'id'     => 'plugin_form',
                'name'   => 'longform',
                'method' => 'post',
                'action' => 'index.php',
            )).
            n.tag_start('div', array(
                'class'      => 'txp-listtables',
                'tabindex'   => 0,
                'aria-label' => gTxt('list'),
            )).
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
                    'version', 'version', 'plugin', true, $switch_dir, '', '',
                        (('version' == $sort) ? "$dir " : '').'txp-list-col-version'
                ).
                column_head(
                    'active', 'status', 'plugin', true, $switch_dir, '', '',
                        (('status' == $sort) ? "$dir " : '').'txp-list-col-status'
                ).
                column_head(
                    'author', 'author', 'plugin', true, $switch_dir, '', '',
                        (('author' == $sort) ? "$dir " : '').'txp-list-col-author'
                ).
                hCell(gTxt(
                    'description'), '', ' class="txp-list-col-description" scope="col"'
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
                    href(gTxt('options'), array('event' => 'plugin_prefs.'.$name)),
                    array('class' => 'plugin-prefs')
                );
            } else {
                $plugin_prefs = '';
            }

            if (class_exists('\ZipArchive')) {
                $download = span(
                    href(gTxt('export'), array(
                        'event'      => 'plugin',
                        'step'       => 'plugin_export',
                        'name'       => $name,
                        '_txp_token' => form_token(),
                    )),
                    array('class' => 'plugin-download')
                );
            } else {
                $download = '';
            }

            $manage = array();

            if ($help) {
                $manage[] = $help;
            }

            if ($plugin_prefs) {
                $manage[] = $plugin_prefs;
            }

            $manage[] = href(gTxt('compile'), array(
                    'event'      => 'plugin',
                    'step'       => 'plugin_compile',
                    'name'       => $name,
                    'compress'   => 1,
                    '_txp_token' => form_token(),
                ), array('class' => 'plugin-download'));

            if ($download) {
                $manage[] = $download;
            }

            if (!empty($lastCheck['plugins'][$name])) {
                foreach ($lastCheck['plugins'][$name] as $pluginType => $pluginMeta) {
                    $manage[] = href(gTxt('plugin_upgrade', array('{version}' => $pluginMeta['version'], '{type}' => $pluginType)), $pluginMeta['endpoint']);
                }
            }

            $manage_items = ($manage) ? implode(sp.span('&#124;', array('role' => 'separator')).sp, $manage) : '-';
            $edit_url = array(
                'event'         => 'plugin',
                'step'          => 'plugin_edit',
                'name'          => $name,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
                '_txp_token'    => form_token(),
            );

            $statusLink = status_link($status, $name, yes_no($status), array('title' => gTxt('toggle_yes_no')));
            $statusDisplay = (!$publicOn && $type == 0) || (!$adminOn && in_array($type, array(3, 4))) || (!$publicOn && !$adminOn && in_array($type, array(0, 1, 3, 4, 5)))
                ? tag($statusLink, 's')
                : $statusLink;
            $showModified = ($modified ? sp.span(gTxt('modified'), array('class' => 'warning')) : '');

            $contentBlock .= tr(
                td(
                    fInput('checkbox', 'selected[]', $name), '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    href($name, $edit_url, array('title' => gTxt('edit'))), '', ' class="txp-list-col-name" scope="row"'
                ).
                td(
                    (!empty($lastCheck['plugins'][$name])
                        ? href($version.$showModified.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), PLUGIN_REPO_URL.'/plugins/'.$name, array(
                        'rel'    => 'external',
                        'target' => '_blank',))
                        : $version.$showModified), '', 'txp-list-col-version'
                ).
                td(
                    $statusDisplay, '', 'txp-list-col-status'
                ).
                td(
                    ($author_uri ? href($author.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), $a['author_uri'], array(
                        'rel'    => 'external',
                        'target' => '_blank',
                    )) : $author), '', 'txp-list-col-author'
                ).
                td(
                    $description, '', 'txp-list-col-description'
                ).
                td(
                    $load_order, '', 'txp-list-col-load-order'
                ).
                td(
                    $manage_items, '', 'txp-list-col-manage'
                ),
                $status ? ' class="active"' : ''
            );

            unset($name);
        }

        $contentBlock .=
            n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div'). // End of .txp-listtables.
            plugin_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form');
    }

    if (!is_dir(PLUGINPATH) || !is_writeable(PLUGINPATH)) {
        $createBlock =
            graf(
                span(null, array('class' => 'ui-icon ui-icon-alert')).' '.
                gTxt('plugin_dir_not_writeable', array('{plugindir}' => PLUGINPATH)),
                array('class' => 'alert-block warning')
            ).n;
    } else {
        $createBlock = tag(
            tag(href(gTxt('create'), array(
                'event'      => 'plugin',
                'step'       => 'plugin_edit',
                '_txp_token' => form_token(),
            ), 'class="txp-button"'), 'p').
            wrapRegion('txp-plugins-group', plugin_form($existing_files), 'txp-plugins-group-content', 'install_plugin', $installed ? 'plugin_install' : ''),
            'div',
            array('class' => 'txp-control-panel')
        );
    }

    $pageBlock = $paginator->render().
        nav_form('plugin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table();
    echo $table->render(compact('total', 'crit') + array('heading' => 'tab_plugins'), $searchBlock, $createBlock, $contentBlock, $pageBlock);
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

    $vars = array(
        'version',
        'type',
        'order',
        'author',
        'author_uri',
        'description',
        'flags',
        'code',
        'help_raw',
        'textpack',
    );

    $pluginObj = Txp::get('\Textpattern\Plugin\Plugin');

    if ($name) {
        $plugin = $pluginObj->read($name);
    } else {
        $userInfo = is_logged_in();
        $plugin = array('name' => '', 'order' => 5, 'version' => '0.1', 'author' => $userInfo ? $userInfo['RealName'] : '', 'author_uri' => hu);
    }

    if (empty($plugin)) {
        return graf(gTxt('plugin_not_editable'), array('class' => 'alert-block warning'));
    }

    foreach ($vars as $key) {
        if (empty($plugin[$key])) {
            $plugin[$key] = '';
        }
    }

    $flagset = array();

    if ((int)$plugin['flags'] & PLUGIN_HAS_PREFS) {
        $flagset[] = PLUGIN_HAS_PREFS;
    }

    if ((int)$plugin['flags'] & PLUGIN_LIFECYCLE_NOTIFY) {
        $flagset[] = PLUGIN_LIFECYCLE_NOTIFY;
    }

    $buttons = graf(
        sLink('plugin', '', gTxt('cancel'), 'txp-button').n.
        fInput('submit', '', gTxt('save'), 'publish'),
        array('class' => 'txp-edit-actions')
    );

    $fieldSizes = Txp::get('\Textpattern\DB\Core')->columnSizes('txp_plugin', 'name, author, author_uri, version');

    return form(
        tag(
            hed(gTxt('edit_plugin', array('{name}' => $name)), 2, array('class' => 'txp-heading')).
            Txp::get('\Textpattern\UI\InputLabel', 'code', Txp::get('\Textpattern\UI\Textarea', 'code', $plugin['code'])->setAtts(array(
                'class' => 'code',
                'id'    => 'plugin_code',
                'cols'  => INPUT_XLARGE,
                'rows'  => TEXTAREA_HEIGHT_LARGE,
                'dir'   => 'ltr',
            )), array('code', 'plugin_code')).
            Txp::get('\Textpattern\UI\InputLabel', 'help_raw', Txp::get('\Textpattern\UI\Textarea', 'help_raw', $plugin['help_raw'])->setAtts(array(
                'class' => 'help code',
                'id'    => 'plugin_help',
                'cols'  => INPUT_XLARGE,
                'rows'  => TEXTAREA_HEIGHT_LARGE,
                'dir'   => 'ltr',
            )), array('help', 'plugin_help')).
            Txp::get('\Textpattern\UI\InputLabel', 'textpack', Txp::get('\Textpattern\UI\Textarea', 'textpack', $plugin['textpack'])->setAtts(array(
                'class' => 'textpack code',
                'id'    => 'plugin_textpack',
                'cols'  => INPUT_XLARGE,
                'rows'  => TEXTAREA_HEIGHT_LARGE,
                'dir'   => 'ltr',
            )), array('textpack', 'plugin_textpack')),
        'div', array(
            'class' => 'txp-layout-4col-3span',
            'id'    => 'main_content',
            'role'  => 'region',
        )).
        tag(
            n.tag(
                $buttons,
                'div', array('class' => 'txp-save-zone')
            ).
            tag(
                hed(gTxt('plugin_details'), 3, array('id' => 'plugin-details-label')).
                tag(
                    Txp::get('\Textpattern\UI\InputLabel', 'newname',
                        Txp::get('\Textpattern\UI\Input', 'newname', 'text', $plugin['name'])
                            ->setAtts(array(
                                'id'        => 'newname',
                                'maxlength' => $fieldSizes['name'],
                            ))
                            ->setBool('required'),
                        'name'
                    ).
                    Txp::get('\Textpattern\UI\InputLabel', 'version',
                        Txp::get('\Textpattern\UI\Input', 'version', 'text', $plugin['version'])
                            ->setAtts(array(
                                'id'        => 'version',
                                'maxlength' => $fieldSizes['version'],
                            )),
                        'version'
                    ).
                    Txp::get('\Textpattern\UI\InputLabel', 'type',
                        Txp::get('\Textpattern\UI\Select', 'type', $pluginObj->getTypes(), $plugin['type'])
                            ->setAtt('id', 'plugin_type'),
                        array('type', 'plugin_type')
                    ).
                    Txp::get('\Textpattern\UI\InputLabel', 'order',
                        Txp::get('\Textpattern\UI\Select', 'order', array_combine(range(1,9), range(1,9)), $plugin['order'])
                            ->setAtt('id', 'order'),
                        'order'
                    ).
                    Txp::get('\Textpattern\UI\InputLabel', 'author',
                        Txp::get('\Textpattern\UI\Input', 'author', 'text', $plugin['author'])
                            ->setAtts(array(
                                'id'        => 'author',
                                'maxlength' => $fieldSizes['author'],
                        )),
                        'author'
                    ).
                    Txp::get('\Textpattern\UI\InputLabel', 'author_uri',
                        Txp::get('\Textpattern\UI\Input', 'author_uri', 'text', $plugin['author_uri'])
                            ->setAtts(array(
                                'id'        => 'author_uri',
                                'size'      => INPUT_LARGE,
                                'maxlength' => $fieldSizes['author_uri'],
                            )),
                        'author_uri'
                    ).
                    Txp::get('\Textpattern\UI\InputLabel', 'description',
                        Txp::get('\Textpattern\UI\Input', 'description', 'text', $plugin['description'])
                            ->setAtts(array(
                                'id'   => 'description',
                                'size' => INPUT_XLARGE,
                        )),
                        'description'
                    ).
                    Txp::get('\Textpattern\UI\InputLabel', 'flags',
                        Txp::get('\Textpattern\UI\CheckboxSet', 'flags', array(
                            1 => gTxt('plugin_has_prefs'),
                            2 => gTxt('plugin_lifecycle_notify'),
                        ), $flagset)
                    ).
                    eInput('plugin').
                    sInput('plugin_save').
                    hInput('help_hash', md5($plugin['help_raw'])).
                    hInput('sort', gps('sort')).
                    hInput('dir', gps('dir')).
                    hInput('page', gps('page')).
                    hInput('search_method', gps('search_method')).
                    hInput('crit', gps('crit')).
                    hInput('name', $name),
                'div', array(
                    'role' => 'group',
                ))
            , 'section', array(
                'class'           => 'txp-details',
                'id'              => 'plugin-details',
                'aria-labelledby' => 'plugin-details-label',
            )),
        'div', array(
            'class' => 'txp-layout-4col-alt',
            'role'  => 'region',
        ))
        , '', '', 'post', 'edit-plugin-code txp-layout', '', 'plugin_details');
}

/**
 * Saves edited plugin information.
 */

function plugin_save()
{
    $vars = array(
        'version'     => 'version',
        'type'        => 'type',
        'load_order'  => 'order',
        'author'      => 'author',
        'author_uri'  => 'author_uri',
        'description' => 'description',
        'code'        => 'code',
        'textpack'    => 'textpack',
    );

    $plugObj = Txp::get('\Textpattern\Plugin\Plugin');
    $plugin = array_map('assert_string', gpsa(array_merge($vars, array('name', 'newname', 'help_raw', 'help_hash'))));

    extract($plugin);
    $flags = (array)gps('flags', 0);

    if (empty($name)) {
        $plugin['name'] = $plugin['newname'];
    }

    if ($name !== $newname) {
        $ret = $name ? $plugObj->rename($name, $newname) : $plugObj->install($plugin);

        if ($ret === false) {
            // @todo issue a warning and stay on page?
            pagetop(gTxt('edit_plugins'));
            echo plugin_edit_form($name);

            return;
        }
    }

    if ($help_hash !== md5($help_raw)) {
        // Help has changed, so recompile it.
        $help = Txp::get('\Netcarver\Textile\Parser', 'html5')->parse($help_raw);
    } else {
        $help = $help_raw;
    }

    $vars['help'] = 'help';
    $vars['flags'] = 'flags';
    $clause = array();
    $flags = array_sum($flags);

    foreach ($vars as $key => $var) {
        $clause[] = "$key = '".doSlash($$var)."'";
    }

    $vars['help_raw'] = 'help_raw';

    safe_update('txp_plugin', implode(',', $clause), "name = '".doSlash($newname)."'");
    $plugObj->updateFile($newname, compact($vars));
    $message = gTxt('plugin_saved', array('{name}' => $newname));

    plugin_list($message);
}

/**
 * Renders a status link.
 *
 * @param  string $status     The new status
 * @param  string $name       The plugin
 * @param  string $linktext   The label
 * @param  string|array $atts The element's HTML attributes
 * @return string HTML
 * @access private
 * @see    asyncHref()
 */

function status_link($status, $name, $linktext, $atts = '')
{
    return asyncHref(
        $linktext,
        array(
            'step'  => 'switch_status',
            'thing' => $name,
        ),
        $atts
    );
}

/**
 * Plugin installation's preview step.
 *
 * Outputs a panel displaying the plugin's source code,
 * the included help file, Textpack strings, additional
 * data and any bundled files (if a zipped archive).
 *
 * @param array                      $payload   Information passed from the upload step, if applicable
 * @param \Textpattern\Plugin\Plugin $txpPlugin Plugin object from upload step
 */

function plugin_verify($payload = array(), $txpPlugin = null)
{
    $extras = '';

    if (!empty($payload['plugin-filename'])) {
        $extras .= hInput('plugin-filename', assert_string($payload['plugin-filename'])).
            hInput('plugin-token', $txpPlugin->generateToken());

        if (!empty($payload['files'])) {
            $extras .= hed(gTxt('upload'), 2).
                tag(implode(br, (array)$payload['files']), 'pre', array('id' => 'preview-data'));
        }

        $plugin = $payload['plugin'];
    } else {
        $plugin64 = assert_string(empty($payload['plugin64']) ? ps('plugin') : $payload['plugin64']);

        if (preg_match("#^https?://.+#", $plugin64) && @fopen($plugin64, 'r')) {
            // Dealing with a URL so forge a call to 'upload' it, which will redirect
            // back to this function when done to handle it properly.
            plugin_upload($plugin64);
            return;
        }

        $txpPlugin = Txp::get('\Textpattern\Plugin\Plugin');
        $plugin = $txpPlugin->extract($plugin64);
        $extras .= hInput('plugin64', $plugin64).
            hInput('plugin-token', $txpPlugin->generateToken());
    }

    if ($plugin) {
        $textpack = '';
        $data = '';

        if (isset($plugin['help_raw']) && empty($plugin['allow_html_help'])) {
            $textile = new \Textpattern\Textile\RestrictedParser();
            $help_source = $textile->setLite(false)->setImages(true)->parse($plugin['help_raw']);
        } else {
            $help_source = isset($plugin['help']) ? str_replace(array(t), array(sp.sp.sp.sp), txpspecialchars($plugin['help'])) : '';
        }

        if (isset($plugin['textpack'])) {
            $textpack = $plugin['textpack'];
        }

        if (isset($plugin['data'])) {
            $data = txpspecialchars($plugin['data']);
        }

        $source = isset($plugin['code']) ? txpspecialchars($plugin['code']) : '';
        $sub = graf(
            fInput('submit', 'plugin-cancel', gTxt('cancel'), 'txp-button').
            fInput('submit', 'plugin-go', gTxt('install'), 'publish'),
            array('class' => 'txp-edit-actions')
        );

        pagetop(gTxt('verify_plugin'));
        echo form(
            hed(gTxt('previewing_plugin'), 2).
            tag(
                tag($source, 'code', array(
                    'class' => 'language-php',
                    'dir'   => 'ltr',
                )),
                'pre', array('id' => 'preview-plugin')
            ).
            ($help_source
                ? hed(gTxt('plugin_help'), 2).
                    tag(
                        tag($help_source, 'code', array(
                            'class' => 'language-markup',
                            'dir'   => 'ltr',
                        )),
                        'pre', array('id' => 'preview-help')
                    )
                : ''
            ).
            ($textpack
                ? hed(tag('Textpack', 'bdi', array('dir' => 'ltr')), 2).
                    tag(
                        tag($textpack, 'code', array('dir' => 'ltr')), 'pre', array('id' => 'preview-textpack')
                    )
                : ''
            ).
            ($data
                ? hed(gTxt('meta'), 2).
                    tag(
                        tag($data, 'code', array('dir' => 'ltr')),
                        'pre', array('id' => 'preview-plugin')
                    )
                : ''
            ).
            $extras.
            $sub.
            sInput('plugin_install').
            eInput('plugin').
            hInput('plugin-name', $plugin['name'])
            , '', '', 'post', 'plugin-info', '', 'plugin_preview'
        );

        return;
    }

    plugin_list(array(gTxt('bad_plugin_code'), E_ERROR));
}

/**
 * Installs a plugin.
 *
 * Also handles cancellation of plugin installation by tidying up temp files.
 */

function plugin_install()
{
    $message = array(gTxt('bad_plugin_code'), E_ERROR);

    $srcFile = ps('plugin-filename');
    $source = $srcFile ? rtrim(get_pref('tempdir', sys_get_temp_dir()), DS).DS.sanitizeForFile($srcFile) : '';
    $name = sanitizeForFile(ps('plugin-name'));
    $txpPlugin = Txp::get('\Textpattern\Plugin\Plugin');

    if (ps('plugin-cancel')) {
        if ($source) {
            unlink($source);
        }

        $message = array(gTxt('plugin_install_cancelled'), E_WARNING);
    } elseif (ps('plugin-go')) {
        $hash = assert_string(ps('plugin-token'));

        if ($source && is_readable($source)) {
            if ($hash) {
                $message = $txpPlugin->verifyToken($hash, $source);
            }

            if ($message === true) {
                $target_dir = rtrim(PLUGINPATH, DS).DS.$name;
                $target_path = $target_dir.DS.basename($source);

                if (!file_exists($target_dir)) {
                    mkdir($target_dir);
                }

                if (rename($source, $target_path)) {
                    extract(pathinfo($target_path));
                    $extension = strtolower($extension);

                    if ($extension === 'txt') {
                        $write = true;
                        $plugin = $txpPlugin->extract(file_get_contents($target_path));
                        unlink($target_path);
                    } elseif ($extension === 'php') {
                        $write = true;
                        $plugin = $txpPlugin->read($target_path);
                    } elseif ($extension === 'zip' && class_exists('ZipArchive')) {
                        $zip = new \ZipArchive();
                        $zh = $zip->open($target_path);

                        if ($zh === true) {
                            $makedir = PLUGINPATH;
                            $badSlash = false;

                            for ($i = 0; $i < $zip->numFiles; $i++) {
                                $entryName = $zip->getNameIndex($i);

                                if (strpos($entryName, '\\') !== false) {
                                    $badSlash = true;
                                }

                                if (strpos(str_replace('\\', '/', $entryName), $filename.'/') !== 0) {
                                    $makedir = PLUGINPATH.DS.$filename;
                                }
                            }

                            if ($badSlash && DS !== '\\') {// Windows zip on Linux
                                $umask = umask();

                                for ($i = 0; $i < $zip->numFiles; $i++) {
                                    $entryName = $zip->getNameIndex($i);
                                    extract(pathinfo(str_replace('\\', '/', $entryName)));
                                    $dirname = $makedir . '/' . $dirname;

                                    if (!is_dir($dirname)) {
                                        mkdir($dirname, $umask, true);
                                    }

                                    $tmpname = md5($entryName);
                                    $zip->renameIndex($i, $tmpname);
                                    $zip->extractTo($dirname, $tmpname);
                                    rename($dirname.'/'.$tmpname, $dirname.'/'.$basename);
                                }
                            } else {
                                $zip->extractTo($makedir);
                            }

                            $zip->close();

                            list($plugin, $files) = $txpPlugin->read($target_path);
                            unlink($target_path);
                        }
                    }

                    $message = $txpPlugin->install($plugin, null, !empty($write));
                }
            } else {
                unlink($source);
            }
        } else {
            $plugin64 = assert_string(ps('plugin64'));

            if ($hash) {
                $message = $txpPlugin->verifyToken($hash, $plugin64);

                if ($message === true) {
                    $message = $txpPlugin->install($plugin64);
                }
            }
        }
    }

    Txp::get('\Textpattern\Security\Token')->remove('plugin_verify', $txpPlugin->computeRef($name), '2 HOUR');
    checkPluginUpdates();

    plugin_list($message);
}

/**
 * Uploads a plugin.
 *
 * @param string $url Fetch the plugin from this remote location instead
 */

function plugin_upload($url = null)
{
    $payload = array();
    $txpPlugin = Txp::get('\Textpattern\Plugin\Plugin');
    $dest = rtrim(get_pref('tempdir', sys_get_temp_dir()), DS);
    $ready = false;

    if ($url || $_FILES["theplugin"]["name"]) {
        if ($url) {
            $urlParts = parse_url($url);
            $pathParts = pathinfo($urlParts['path']);
            $filename = $pathParts['basename'];
            $target = $dest.DS.sanitizeForFile($filename);
            $content = file_get_contents($url);

            // Don't need to test for 'false', since '0' (number of returned bytes) is
            // still a 'failure' to write anything meaningful.
            if (file_put_contents($target, $content)) {
                $ready = true;
            }
        } else {
            $fileParts = pathinfo($_FILES["theplugin"]["name"]);
            $source = $_FILES["theplugin"]["tmp_name"];
            $target = $dest.DS.$fileParts['basename'];

            if (move_uploaded_file($source, $target)) {
                $ready = true;
            }
        }

        if ($ready) {
            extract(pathinfo($target));

            $extension = strtolower($extension);
            $payload['plugin-filename'] = basename($target);

            if ($extension === 'txt') {
                $payload['plugin64'] = file_get_contents($target);
                $payload['plugin-filename'] = '';
                unlink($target);
            } elseif ($extension === 'php') {
                $payload['plugin'] = $txpPlugin->read($target);
            } elseif ($extension === 'zip' && class_exists('ZipArchive')) {
                list($plugin, $files) = $txpPlugin->read($target);
                $payload['plugin'] = $plugin;
                $payload['files'] = $files;
            }
        }
    }

    plugin_verify($payload, $txpPlugin);
}

/**
 * Imports a plugin that is already in the filesystem but is not yet in the DB.
 */

function plugin_import()
{
    $plugin = array();

    if ($filename = gps('filename')) {
        $plugin = Txp::get('\Textpattern\Plugin\Plugin')->read($filename);
    }

    $message = Txp::get('\Textpattern\Plugin\Plugin')->install($plugin);
    plugin_list($message);
}

/**
 * Exports a plugin as a zip file.
 */

function plugin_export()
{
    if ($name = gps('name')) {
        echo Txp::get('\Textpattern\Plugin\Plugin')->createZip($name, true);
    }

    exit;
}

/**
 * Exports a plugin as a compiled .txt file.
 */

function plugin_compile()
{
    $compress = empty(gps('compress')) ? false : true;

    if ($name = gps('name')) {
        echo Txp::get('\Textpattern\Plugin\Plugin')->compile($name, $compress, true);
    }

    exit;
}

/**
 * Renders a plugin installation form.
 *
 * @param  array  $existing_files
 * @return string HTML
 * @access private
 * @see    form()
 */

function plugin_form($existing_files = array())
{
    return tag(
        tag(gTxt('upload_plugin'), 'label', ' for="plugin-upload"').popHelp('upload_plugin').
        n.tag_void('input', array(
            'type'     => 'file',
            'name'     => 'theplugin',
            'id'       => 'plugin-upload',
            'accept'   => (class_exists('ZipArchive') ? "application/x-zip-compressed, application/zip, " : '').".php, .txt",
            'required' => 'required',
        )).
        fInput('submit', 'install_new', gTxt('upload')).
        eInput('plugin').
        sInput('plugin_upload').
        tInput().n, 'form', array(
            'class'        => 'plugin-file',
            'id'           => 'plugin_upload_form',
            'method'       => 'post',
            'action'       => 'index.php',
            'enctype'      => 'multipart/form-data'
        )
    ).
    ($existing_files ? form(
        eInput('plugin').
        sInput('plugin_import').
        tag(gTxt('import_from_disk'), 'label', array('for' => 'file-existing')).
        selectInput('filename', $existing_files, null, false, '', 'file-existing').
        fInput('submit', '', gTxt('import')),
        '', '', 'post', 'assign-existing-form txp-async-update', '', 'assign_file'
    ) : '').
    form(
        tag(gTxt('install_plugin'), 'label', ' for="plugin-install"').popHelp('install_plugin').
        n.'<textarea class="code" id="plugin-install" name="plugin" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'" dir="ltr" required="required"></textarea>'.
        fInput('submit', 'install_new', gTxt('upload')).
        eInput('plugin').
        sInput('plugin_verify'), '', '', 'post', 'plugin-data', '', 'plugin_install_form'
    );
}

/**
 * Updates pageby value.
 */

function plugin_change_pageby()
{
    global $event;

    Txp::get('\Textpattern\Admin\Paginator', $event, 'plugin')->change();
    plugin_list();
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
        'update'       => gTxt('update_from_disk'),
        'coderevert'   => gTxt('revert_to_last_installed'),
        'delete'       => array(
            'label' => gTxt('delete'),
            'html' => checkbox2('sync', gps('sync'), 0, 'sync').n.
                tag(gTxt('plugin_delete_entirely'), 'label', array('for' => 'sync'))
        )
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
        case 'update':
            foreach ($selected as $name) {
                $plugin->install($plugin->read($name));
            }
            break;
        case 'coderevert':
            foreach ($selected as $name) {
                $plugin->revert($name);
            }
            break;
    }

    $message = gTxt('plugin_'.($method == 'delete' ? 'deleted' : 'updated'), array('{name}' => join(', ', $selected)));

    plugin_list($message);
}

/**
 * Checks for Textpattern plugin updates.
 *
 * @return  array|null When updates are found, an array of metadata about each installed plugin
 */

function checkPluginUpdates()
{
    static $plugins;

    $endpoint = PLUGIN_REPO_URL.'/all';

    // Can't use the globals, since plugins aren't loaded on the Plugins panel.
    if (empty($plugins)) {
        $rs = safe_rows('name, version', 'txp_plugin', '1');

        foreach ($rs as $a) {
            $n = array_shift($a);
            $plugins[$n] = $a['version'];
        }
    }

    $lastCheck = array(
        'when'     => time(),
        'msg'      => '',
        'plugins'  => array(),
        'response' => true,
    );

    if (OPENSSL_VERSION_NUMBER < REQUIRED_OPENSSL_VERSION) {
        $lastCheck['msg'] = 'problem_connecting_plugin_server';
        $lastCheck['response'] = false;
    } else {
        if (function_exists('curl_version')) {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $contents = curl_exec($ch);
        } else {
            $contents = file_get_contents($endpoint);
        }

        $allPlugins = json_decode($contents, true);

        if (is_array($allPlugins)) {
            foreach ($allPlugins as $pluginSet) {
                foreach ($pluginSet as $plugin) {
                    if ($plugins && array_key_exists($plugin['name'], $plugins)) {
                        // Check version dependencies.
                        if ($ret = pluginDependency($plugins, $plugin, 'stable')) {
                            $lastCheck['plugins'][$plugin['name']]['stable'] = $ret;
                        }
                        if ($ret = pluginDependency($plugins, $plugin, 'beta')) {
                            $lastCheck['plugins'][$plugin['name']]['beta'] = $ret;
                        }
                        // @todo: grab supersededBy so it can be flagged in the UI.
                    }
                }
            }
        }
    }

    set_pref('last_plugin_update_check', json_encode($lastCheck, TEXTPATTERN_JSON), 'publish', PREF_HIDDEN, 'text_input');

    return $lastCheck;
}

function pluginDependency($plugins, $plugin, $type = 'stable')
{
    $out = array();

    if (!empty($plugin[$type])) {
        $txpVersion = get_pref('version');
        $thisPluginVersion = !empty($plugin[$type]['version']) ? $plugin[$type]['version'] : 0;
        $minTxpVersion = !empty($plugin[$type]['verifiedMinTxpCompatibility']) ? $plugin[$type]['verifiedMinTxpCompatibility'] : 0;
        $maxTxpVersion = !empty($plugin[$type]['verifiedMaxTxpCompatibility']) ? $plugin[$type]['verifiedMaxTxpCompatibility'] : 0;

        if ((version_compare($plugins[$plugin['name']], $thisPluginVersion) < 0)
                && (check_compatibility($minTxpVersion, $maxTxpVersion))) {
            $out['endpoint'] = $plugin[$type]['endpointUrl'];
            $out['version'] = $plugin[$type]['version'];
        }
    }

    return $out;
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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

    if ($sort === '') {
        $sort = get_pref('plugin_sort_column', 'name');
    } else {
        if (!in_array($sort, array('name', 'status', 'author', 'version', 'modified', 'load_order'))) {
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

    foreach (safe_column_num('name', 'txp_plugin', 1) as $name) {
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

            $contentBlock .= tr(
                td(
                    fInput('checkbox', 'selected[]', $name), '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    href($name, $edit_url, array('title' => gTxt('edit'))), '', ' class="txp-list-col-name" scope="row"'
                ).
                td(
                    ($author_uri ? href($author.sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')), $a['author_uri'], array(
                        'rel'    => 'external',
                        'target' => '_blank',
                    )) : $author), '', 'txp-list-col-author'
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
                    $statusDisplay, '', 'txp-list-col-status'
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
        $createBlock = tag(plugin_form($existing_files), 'div', array('class' => 'txp-control-panel'));
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
    $code = ($name) ? fetch('code', 'txp_plugin', 'name', $name) : '';
    $thing = ($code) ? $code : '';

    return
        form(
            hed(gTxt('edit_plugin', array('{name}' => $name)), 2).
            '<textarea class="code" id="plugin_code" name="code" cols="'.INPUT_XLARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($thing).'</textarea>'.
            graf(
                sLink('plugin', '', gTxt('cancel'), 'txp-button').
                fInput('submit', '', gTxt('save'), 'publish'),
                array('class' => 'txp-edit-actions')
            ).
            eInput('plugin').
            sInput('plugin_save').
            hInput('name', $name).
            hInput('sort', gps('sort')).
            hInput('dir', gps('dir')).
            hInput('page', gps('page')).
            hInput('search_method', gps('search_method')).
            hInput('crit', gps('crit')).
            hInput('name', $name), '', '', 'post', 'edit-plugin-code', '', 'plugin_details');
}

/**
 * Saves edited plugin code.
 */

function plugin_save()
{
    extract(array_map('assert_string', gpsa(array('name', 'code'))));

    safe_update('txp_plugin', "code = '".doSlash($code)."'", "name = '".doSlash($name)."'");
    Txp::get('\Textpattern\Plugin\Plugin')->updateFile($name, $code);
    $message = gTxt('plugin_saved', array('{name}' => $name));

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
        $source = '';
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

        $source .= txpspecialchars($plugin['code']);
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
 *
 * @todo During upload, stash an sha1 digest of the code to the token table with
 *       an hour's expiry. Then verify this on installation to check the file/code
 *       has not been tampered with.
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
                            for ($i = 0; $i < $zip->numFiles; $i++) {
                                if (strpos($zip->getNameIndex($i), $filename.'/') !== 0) {
                                    $makedir = true;

                                    break;
                                }
                            }

                            $zip->extractTo(PLUGINPATH.(empty($makedir) ? '' : DS.$filename));
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
            $filename = $_FILES["theplugin"]["name"];
            $source = $_FILES["theplugin"]["tmp_name"];
            $target = $dest.DS.$filename;

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
    ).br.
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
    }

    $message = gTxt('plugin_'.($method == 'delete' ? 'deleted' : 'updated'), array('{name}' => join(', ', $selected)));

    plugin_list($message);
}

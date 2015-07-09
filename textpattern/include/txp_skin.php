<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2015 The Textpattern Development Team
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
 * Themes (skins) panel.
 *
 * @package Admin\Skin
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'skin') {
    require_privs('skin');

    global $all_skins;
    $all_skins = safe_column('name', 'txp_skin', "1=1");

    $available_steps = array(
        'skin_change_pageby' => true,
        'skin_list'          => false,
        'skin_delete'        => true,
        'skin_save'          => true,
        'skin_edit'          => false,
        'skin_multi_edit'    => true,
        'skin_toggle_option' => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        skin_list();
    }
}

/**
 * The main panel listing all skins.
 *
 * @param string|array $message The activity message
 */

function skin_list($message = '')
{
    global $event, $skin_list_pageby;

    pagetop(gTxt('tab_skins'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('skin_sort_column', 'time');
    }

    if ($dir === '') {
        $dir = get_pref('skin_sort_dir', 'desc');
    }
    $dir = ($dir == 'asc') ? 'asc' : 'desc';

    switch ($sort) {
        case 'title':
            $sort_sql = 'title '.$dir;
            break;
        case 'author':
            $sort_sql = 'author '.$dir;
            break;
        case 'name':
        default:
            $sort_sql = 'name '.$dir;
            break;
    }

    set_pref('skin_sort_column', $sort, 'skin', 2, '', 0, PREF_PRIVATE);
    set_pref('skin_sort_dir', $dir, 'skin', 2, '', 0, PREF_PRIVATE);

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $criteria = 1;

    if ($search_method and $crit != '') {
        $verbatim = preg_match('/^"(.*)"$/', $crit, $m);
        $crit_escaped = $verbatim ? doSlash($m[1]) : doLike($crit);
        $critsql = $verbatim ?
            array(
                'name'   => "name = '$crit_escaped'",
                'title'  => "title = '$crit_escaped'",
                'author' => "author = '$crit_escaped'",
            ) : array(
                'name'   => "name like '%$crit_escaped%'",
                'title'  => "title like '%$crit_escaped%'",
                'author' => "author like '%$crit_escaped%'",
            );

        $search_sql = array();

        foreach ((array) $search_method as $method) {
            if (isset($critsql[$method])) {
                $search_sql[] = $critsql[$method];
            }
        }

        if ($search_sql) {
            $criteria = join(' or ', $search_sql);
            $limit = 500;
        } else {
            $search_method = '';
            $crit = '';
        }
    } else {
        $search_method = '';
        $crit = '';
    }

    $criteria .= callback_event('admin_criteria', 'skin_list', 0, $criteria);

    $total = safe_count('txp_skin', $criteria);

    echo
        hed(gTxt('tab_skins').popHelp('skin_category'), 1, array('class' => 'txp-heading')).
        n.tag_start('div', array('id' => $event.'_control', 'class' => 'txp-control-panel')).

        graf(
            sLink('skin', 'skin_edit', gTxt('create_skin')),
            array('class' => 'txp-buttons')
        );

    if ($total < 1) {
        if ($criteria != 1) {
            echo skin_search_form($crit, $search_method).
                graf(gTxt('no_results_found'), ' class="indicator"').'</div>';
        }

        return;
    }

    $limit = max($skin_list_pageby, 15);

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    echo skin_search_form($crit, $search_method).'</div>';

    $rs = safe_rows_start(
        '*',
        'txp_skin',
        "{$criteria} order by {$sort_sql} limit {$offset}, {$limit}"
    );

    if ($rs) {
        echo
            n.tag_start('div', array(
                'id'    => $event.'_container',
                'class' => 'txp-container',
            )).
            n.tag_start('form', array(
                'action' => 'index.php',
                'id'     => 'skin_form',
                'class'  => 'multi_edit_form',
                'method' => 'post',
                'name'   => 'longform',
            )).
            n.tag_start('div', array('class' => 'txp-listtables')).
            n.tag_start('table', array('class' => 'txp-list')).
            n.tag_start('thead').
            tr(
                hCell(
                    fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                        '', ' scope="col" title="'.gTxt('toggle_all_selected').'" class="txp-list-col-multi-edit"'
                ).
                column_head(
                    'name', 'name', 'skin', true, $switch_dir, $crit, $search_method,
                        (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                ).
                column_head(
                    'title', 'title', 'skin', true, $switch_dir, $crit, $search_method,
                        (('title' == $sort) ? "$dir " : '').'txp-list-col-title'
                ).
                column_head(
                    'version', 'version', 'skin', true, $switch_dir, $crit, $search_method,
                        (('version' == $sort) ? "$dir " : '').'txp-list-col-version'
                ).
                column_head(
                    'author', 'author', 'skin', true, $switch_dir, $crit, $search_method,
                        (('author' == $sort) ? "$dir " : '').'txp-list-col-author'
                )
            ).
            n.tag_end('thead').
            n.tag_start('tbody');

        while ($a = nextRow($rs)) {
            extract($a, EXTR_PREFIX_ALL, 'skin');

            $edit_url = array(
                'event'         => 'skin',
                'step'          => 'skin_edit',
                'name'          => $skin_name,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
            );

            $author = ($skin_website) ? href(txpspecialchars($skin_author), $skin_website) : txpspecialchars($skin_author);

            echo tr(
                td(
                    fInput('checkbox', 'selected[]', $skin_name), '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    href(
                        txpspecialchars($skin_name), $edit_url, array('title' => gTxt('edit'))
                    ), '', array(
                        'scope' => 'row',
                        'class' => 'txp-list-col-name',
                    )
                ).
                td(
                    txpspecialchars($skin_title), '', 'txp-list-col-title'
                ).
                td(
                    txpspecialchars($skin_version), '', 'txp-list-col-version'
                ).
                td(
                    $author, '', 'txp-list-col-author'
                ),
                array('id' => 'txp_skin_'.$skin_name)
            );
        }

        echo
            n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div').
            skin_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            n.tag_start('div', array(
                'id'    => $event.'_navigation',
                'class' => 'txp-navigation',
            )).
            pageby_form('skin', $skin_list_pageby).
            nav_form('skin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div').
            n.tag_end('div');
    }
}

/**
 * The editor for skins.
 */

function skin_edit()
{
    global $event, $step, $all_skins;

    require_privs('skin.edit');

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
        'name',
    )));

    $is_edit = ($name && $step == 'skin_edit');
    $caption = gTxt('create_skin');

    if ($is_edit) {
        $rs = safe_row(
            '*',
            'txp_skin',
            "name = '".doSlash($name)."'"
        );

        $caption = gTxt('edit_skin');
    } else {
        $rs['name'] = $rs['title'] = $rs['version'] = $rs['author'] = $rs['website'] = '';
    }

    if (!$rs) {
        skin_list(array(gTxt('unknown_skin'), E_ERROR));

        return;
    }

    extract($rs, EXTR_PREFIX_ALL, 'skin');
    pagetop(gTxt('tab_skins'));

    $out = array();

    $out[] =
        n.tag_start('skin', array('class' => 'txp-edit')).
        hed($caption, 2);

    $out[] =
        inputLabel('skin_name', fInput('text', 'name', $skin_name, '', '', '', INPUT_REGULAR, '', 'skin_name'), 'skin_name').
        inputLabel('skin_title', fInput('text', 'title', $skin_title, '', '', '', INPUT_REGULAR, '', 'skin_title'), 'skin_title').
        inputLabel('skin_version', fInput('text', 'version', $skin_version, '', '', '', INPUT_REGULAR, '', 'skin_version'), 'skin_version').
        inputLabel('skin_author', fInput('text', 'author', $skin_author, '', '', '', INPUT_REGULAR, '', 'skin_author'), 'skin_author').
        inputLabel('skin_website', fInput('text', 'website', $skin_website, '', '', '', INPUT_REGULAR, '', 'skin_website'), 'skin_website');

    $out[] =
        pluggable_ui('skin_ui', 'extend_detail_form', '', $rs).
        graf(fInput('submit', '', gTxt('save'), 'publish')).
        eInput('skin').
        sInput('skin_save').
        hInput('old_name', $skin_name).
        hInput('search_method', $search_method).
        hInput('crit', $crit).
        hInput('page', $page).
        hInput('sort', $sort).
        hInput('dir', $dir).
        n.tag_end('skin');

    echo
        n.tag_start('div', array('id' => $event.'_container', 'class' => 'txp-container')).
        form(join('', $out), '', '', 'post', 'edit-form', '', 'skin_details').
        n.tag_end('div');
}

/**
 * Saves a skin.
 */

function skin_save()
{
    $in = array_map('assert_string', psa(array(
        'name',
        'title',
        'old_name',
        'version',
        'author',
        'website',
    )));

    if (empty($in['title'])) {
        $in['title'] = $in['name'];
    }

    // Prevent non-URL characters on skin names.
    $in['name']  = strtolower(sanitizeForUrl($in['name']));

    extract($in);

    $in = doSlash($in);
    extract($in, EXTR_PREFIX_ALL, 'safe');

    if ($name != strtolower($old_name)) {
        if (safe_field('name', 'txp_skin', "name='$safe_name'")) {
            $message = array(gTxt('skin_name_already_exists', array('{name}' => $name)), E_ERROR);
            skin_list($message);

            return;
        }
    }

    $ok = false;

    if ($name) {
        if ($safe_old_name) {
            $ok = safe_update('txp_skin', "
                name    = '$safe_name',
                title   = '$safe_title',
                version = '$safe_version',
                author  = '$safe_author',
                website = '$safe_website'
                ", "name = '$safe_old_name'");

            // Manually maintain referential integrity.
            if ($ok) {
                safe_update('txp_page', "skin = '$safe_name'", "skin = '$safe_old_name'");
                safe_update('txp_form', "skin = '$safe_name'", "skin = '$safe_old_name'");
                safe_update('txp_css', "skin = '$safe_name'", "skin = '$safe_old_name'");
            }
        } else {
            $ok = safe_insert('txp_skin', "
                name    = '$safe_name',
                title   = '$safe_title',
                version = '$safe_version',
                author  = '$safe_author',
                website = '$safe_website'");

            if ($ok) {
                // Set up blank assets for the skin.
                // Todo: insert both Pages in one call.
                safe_insert('txp_page',
                    "name = 'default', skin = '$safe_name'");

                safe_insert('txp_page',
                    "name = 'error_default', skin = '$safe_name'");

                safe_insert('txp_css',
                    "name = 'default', skin = '$safe_name'");

                $forms = get_essential_forms();

                foreach ($forms as $form => $group) {
                    $name = doSlash($form);
                    $type = doSlash($group);

                    safe_insert('txp_form',
                        "name = '$name', type = '$type', skin = '$safe_name'"
                    );
                }
            }
        }
    }

    if ($ok) {
        update_lastmod();
    }

    if ($ok) {
        skin_list(gTxt(($safe_old_name ? 'skin_updated' : 'skin_created'), array('{name}' => $name)));
    } else {
        skin_list(array(gTxt('skin_save_failed'), E_ERROR));
    }
}

/**
 * Changes and saves the pageby value.
 */

function skin_change_pageby()
{
    event_change_pageby('skin');
    skin_list();
}

/**
 * Processes delete actions sent using the multi-edit form.
 *
 * Can only delete skins that are not in use.
 */

function skin_delete()
{
    $selectedList = ps('selected');
    $message = '';
    $skins = array();

    // Cumbersome to check sections for in-use assets and also return
    // the skins that match, so iterate instead.
    foreach ($selectedList as $asset) {
        $inUse = safe_column(
            'name',
            'txp_section',
            "(page IN (SELECT name FROM ".PFX."txp_page WHERE skin = '{$asset}'))
                OR (css IN (SELECT name FROM ".PFX."txp_css WHERE skin = '{$asset}'))"
        );

        if (!$inUse) {
            $skins[] = $asset;
        }
    }

    $skinsNotDeleted = array_diff($selectedList, $skins);

    if ($skins && safe_delete('txp_skin', 'name in ('.join(',', quote_list($skins)).')')) {
        callback_event('skins_deleted', '', 0, $skins);
        $message = gTxt('skin_deleted', array('{name}' => join(', ', $skins)));
    }

    if ($skinsNotDeleted) {
        $severity = ($message) ? E_WARNING : E_ERROR;
        $message = array(($message ? $message . n : '') . gTxt('skin_delete_failure', array('{name}' => join(', ', $skinsNotDeleted))), $severity);
    }

    skin_list($message);
}

/**
 * Renders a search form for skins.
 *
 * @param  string $crit   The current search criteria
 * @param  string $method The selected search method
 * @return HTML
 */

function skin_search_form($crit, $method)
{
    $methods = array(
        'name'   => gTxt('name'),
        'title'  => gTxt('title'),
        'author' => gTxt('author'),
    );

    return search_form('skin', 'skin_list', $crit, $methods, $method, 'name');
}

/**
 * Renders a multi-edit form widget.
 *
 * @param  int    $page          The page number
 * @param  string $sort          The current sorting value
 * @param  string $dir           The current sorting direction
 * @param  string $crit          The current search criteria
 * @param  string $search_method The current search method
 * @return string HTML
 */

function skin_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $all_skins;

    $methods = array(
        'delete' => gTxt('delete'),
    );

    return multi_edit($methods, 'skin', 'skin_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

/**
 * Processes multi-edit actions.
 */

function skin_multi_edit()
{
    global $txp_user, $all_skins;

    extract(psa(array(
        'edit_method',
        'selected',
    )));

    if (!$selected || !is_array($selected)) {
        return skin_list();
    }

    switch ($edit_method) {
        case 'delete':
            return skin_delete();
            break;
    }

    skin_list();
}

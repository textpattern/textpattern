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

use Textpattern\Search\Filter;

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
    global $event;

    pagetop(gTxt('tab_skins'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('skin_sort_column', 'name');
    } else {
        if (!in_array($sort, array('name', 'title', 'version', 'author', 'section_count', 'page_count', 'form_count', 'css_count'))) {
            $sort = 'name';
        }

        set_pref('skin_sort_column', $sort, 'skin', 2, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('skin_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('skin_sort_dir', $dir, 'skin', 2, '', 0, PREF_PRIVATE);
    }

    switch ($sort) {
        case 'title':
            $sort_sql = 'title '.$dir;
            break;
        case 'version':
            $sort_sql = 'version '.$dir;
            break;
        case 'author':
            $sort_sql = 'author '.$dir;
            break;
        case 'section_count':
            $sort_sql = 'section_count '.$dir;
            break;
        case 'page_count':
            $sort_sql = 'page_count '.$dir;
            break;
        case 'form_count':
            $sort_sql = 'form_count '.$dir;
            break;
        case 'css_count':
            $sort_sql = 'css_count '.$dir;
            break;
        case 'name':
        default:
            $sort_sql = 'name '.$dir;
            break;
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'name' => array(
                'column' => 'txp_skin.name',
                'label'  => gTxt('name'),
            ),
            'title' => array(
                'column' => 'txp_skin.title',
                'label'  => gTxt('title'),
            ),
            'author' => array(
                'column' => 'txp_skin.author',
                'label'  => gTxt('author'),
            ),
        )
    );

    list($criteria, $crit, $search_method) = $search->getFilter();

    $search_render_options = array(
        'placeholder' => 'search_skins',
    );

    $total = safe_count('txp_skin', $criteria);

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_skins').popHelp('skin_category'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-4col-alt')
        );

    $searchBlock =
        n.tag(
            $search->renderForm('skin', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $createBlock = array();

    if (has_privs('skin.edit')) {
        $createBlock[] =
            n.tag(
                sLink('skin', 'skin_edit', gTxt('create_skin'), 'txp-button')
                , 'div', array('class' => 'txp-control-panel')
            );
    }

    $contentBlockStart = n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => $event.'_container',
        ));

    $createBlock = implode(n, $createBlock);

    if ($total < 1) {
        if ($criteria != 1) {
            echo $searchBlock.
                $contentBlockStart.
                $createBlock.
                graf(
                    span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                    gTxt('no_results_found'),
                    array('class' => 'alert-block information')
                ).
                n.tag_end('div'). // End of .txp-layout-1col.
                n.'</div>'; // End of .txp-layout.
        }

        return;
    }

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $paginator->getLimit();

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    echo $searchBlock.$contentBlockStart.$createBlock;

    $rs = safe_rows_start(
        '*,
            (select count(*) from '.safe_pfx_j('txp_section').' where txp_section.skin = txp_skin.name) as section_count,
            (select count(*) from '.safe_pfx_j('txp_page').' where txp_page.skin = txp_skin.name) as page_count,
            (select count(*) from '.safe_pfx_j('txp_form').' where txp_form.skin = txp_skin.name) as form_count,
            (select count(*) from '.safe_pfx_j('txp_css').' where txp_css.skin = txp_skin.name) as css_count',
        'txp_skin',
        "{$criteria} order by {$sort_sql} limit {$offset}, {$limit}"
    );

    if ($rs) {
        echo n.tag(
            toggle_box('skin_detail'), 'div', array('class' => 'txp-list-options')).
            n.tag_start('form', array(
                'class'  => 'multi_edit_form',
                'id'     => 'skin_form',
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
                ).
                column_head(
                    'tab_sections', 'section_count', 'skin', true, $switch_dir, $crit, $search_method,
                        (('section_count' == $sort) ? "$dir " : '').'txp-list-col-section_count skin_detail'
                ).
                column_head(
                    'tab_pages', 'page_count', 'skin', true, $switch_dir, $crit, $search_method,
                        (('page_count' == $sort) ? "$dir " : '').'txp-list-col-page_count skin_detail'
                ).
                column_head(
                    'tab_forms', 'form_count', 'skin', true, $switch_dir, $crit, $search_method,
                        (('form_count' == $sort) ? "$dir " : '').'txp-list-col-form_count skin_detail'
                ).
                column_head(
                    'tab_style', 'css_count', 'skin', true, $switch_dir, $crit, $search_method,
                        (('css_count' == $sort) ? "$dir " : '').'txp-list-col-css_count skin_detail'
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

            if ($skin_section_count > 0) {
                $sectionLink = href($skin_section_count, array(
                    'event'         => 'section',
                    'search_method' => 'skin',
                    'crit'          => '"'.$skin_name.'"',
                ), array(
                    'title' => gTxt('section_count', array('{num}' => $skin_section_count)),
                ));
            } else {
                $sectionLink = 0;
            }

            if ($skin_page_count > 0) {
                $pageLink = href($skin_page_count, array(
                    'event' => 'page',
                    'skin'  => $skin_name,
                ), array(
                    'title' => gTxt('page_count', array('{num}' => $skin_page_count)),
                ));
            } else {
                $pageLink = 0;
            }

            if ($skin_css_count > 0) {
                $cssLink = href($skin_css_count, array(
                    'event' => 'css',
                    'skin'  => $skin_name,
                ), array(
                    'title' => gTxt('css_count', array('{num}' => $skin_css_count)),
                ));
            } else {
                $cssLink = 0;
            }

            if ($skin_form_count > 0) {
                $formLink = href($skin_form_count, array(
                    'event' => 'form',
                    'skin'  => $skin_name,
                ), array(
                    'title' => gTxt('form_count', array('{num}' => $skin_form_count)),
                ));
            } else {
                $formLink = 0;
            }

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
                ).
                td(
                    $sectionLink, '', 'txp-list-col-section_count skin_detail'
                ).
                td(
                    $pageLink, '', 'txp-list-col-page_count skin_detail'
                ).
                td(
                    $formLink, '', 'txp-list-col-form_count skin_detail'
                ).
                td(
                    $cssLink, '', 'txp-list-col-css_count skin_detail'
                ),
                array('id' => 'txp_skin_'.$skin_name)
            );
        }

        echo n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div'). // End of .txp-listtables.
            skin_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            n.tag_start('div', array(
                'class' => 'txp-navigation',
                'id'    => $event.'_navigation',
            )).
            $paginator->render().
            nav_form('skin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div');
    }

    echo n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.
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

    $out[] = hed($caption, 2);
    $fields = array('name', 'title', 'version', 'author', 'website');

    foreach ($fields as $field) {
        $current = ${"skin_".$field};

        $out[] = inputLabel(
            "skin_$field",
            fInput('text', $field, $current, '', '', '', INPUT_REGULAR, '', "skin_$field"),
            "skin_$field"
        );
    }

    $out[] = pluggable_ui('skin_ui', 'extend_detail_form', '', $rs).
        graf(
            sLink('skin', '', gTxt('cancel'), 'txp-button').
            fInput('submit', '', gTxt('save'), 'publish'),
            array('class' => 'txp-edit-actions')
        ).
        eInput('skin').
        sInput('skin_save').
        hInput('old_name', $skin_name).
        hInput('search_method', $search_method).
        hInput('crit', $crit).
        hInput('page', $page).
        hInput('sort', $sort).
        hInput('dir', $dir);

    echo form(join('', $out), '', '', 'post', 'txp-edit', '', 'skin_details');
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
                // Set up blank assets for the skin using the default names.
                // Todo: Insert both Pages in one call.
                $defaults = safe_row("css, page", 'txp_section', "name = 'default'");

                safe_insert('txp_page',
                    "name = '" .$defaults['page']. "', skin = '$safe_name', user_html=''");

                safe_insert('txp_page',
                    "name = 'error_default', skin = '$safe_name', user_html=''");

                safe_insert('txp_css',
                    "name = '" .$defaults['css']. "', skin = '$safe_name', css=''");

                $forms = get_essential_forms();

                foreach ($forms as $form => $group) {
                    $formName = doSlash($form);
                    $formType = doSlash($group);

                    safe_insert('txp_form',
                        "name = '$formName', type = '$formType', skin = '$safe_name', Form=''"
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
    Txp::get('\Textpattern\Admin\Paginator')->change();
    skin_list();
}

/**
 * Processes delete actions sent using the multi-edit form.
 *
 * Can only delete skins that are not in use.
 */

function skin_delete()
{
    $selectedList = doSlash(ps('selected'));
    $message = '';
    $skins = array();
    $currentSkin = get_pref('skin_editing', 'default');
    $changeCurrentSkin = false;

    // Cumbersome to check sections for in-use assets and also return
    // the skins that match, so iterate instead.
    foreach ($selectedList as $asset) {
        $inUse = safe_column(
            'name',
            'txp_section',
            "(page IN (SELECT name FROM ".PFX."txp_page WHERE skin = '{$asset}') AND skin = '{$asset}')
                OR (css IN (SELECT name FROM ".PFX."txp_css WHERE skin = '{$asset}') AND skin = '{$asset}')"
        );

        if ($currentSkin === $asset) {
            $changeCurrentSkin = true;
        }

        if (!$inUse) {
            $skins[] = $asset;
        }
    }

    $skinsNotDeleted = array_diff($selectedList, $skins);
    $skinList = join(',', quote_list($skins));

    if ($skins && safe_delete('txp_skin', "name in ($skinList)")
            && safe_delete('txp_page', "skin in ($skinList)")
            && safe_delete('txp_css', "skin in ($skinList)")
            && safe_delete('txp_form', "skin in ($skinList)")) {
        if ($changeCurrentSkin) {
            skin_set_skin(safe_field('name', 'txp_skin', '1=1 ORDER BY name ASC LIMIT 1'));
        }

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

/**
 * Set the current skin so it persists across panels.
 *
 * @param  string $skin The skin name to store
 * @todo   Generalise this elsewhere?
 * @return string HTML
 */

function skin_set_skin($skin)
{
    set_pref('skin_editing', $skin, 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
}

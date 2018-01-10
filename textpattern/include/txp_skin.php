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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Themes (skins) panel.
 *
 * @package Admin\Skin
 */

use Textpattern\Search\Filter;
use Textpattern\Skin\Main as Skins;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event === 'skin') {
    require_privs($event);

    $availableSteps = array(
        'skin_change_pageby' => true, // Prefixed to make it work with the paginator…
        'list'          => false,
        'edit'          => false,
        'save'          => true,
        'import'        => false,
        'multi_edit'    => true,
    );

    if ($step && bouncer($step, $availableSteps)) {
        call_user_func($event.'_'.$step);
    } else {
        skin_list();
    }
}

/**
 * The main panel listing all skins.
 *
 * @param mixed $message The activity message
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
        $sortOpts = array(
            'title',
            'version',
            'author',
            'section_count',
            'page_count',
            'form_count',
            'css_count',
            'name',
        );

        in_array($sort, $sortOpts) or $sort = 'name';

        set_pref('skin_sort_column', $sort, 'skin', 2, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('skin_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? 'asc' : 'desc';

        set_pref('skin_sort_dir', $dir, 'skin', 2, '', 0, PREF_PRIVATE);
    }

    $sortSQL = $sort.' '.$dir;
    $switchDir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter(
        $event,
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

    $searchRenderOpts = array('placeholder' => 'search_skins');
    $total = safe_count('txp_skin', $criteria);

    echo n.'<div class="txp-layout">'
        .n.tag(
            hed(gTxt('tab_skins'), 1, array('class' => 'txp-heading')),
            'div',
            array('class' => 'txp-layout-4col-alt')
        );

    $searchBlock = n.tag(
        $search->renderForm('skin', $searchRenderOpts),
        'div',
        array(
            'class' => 'txp-layout-4col-3span',
            'id'    => $event.'_control',
        )
    );

    $createBlock = has_privs('skin.edit') ? Skins::renderCreateBlock() : '';

    $contentBlockStart = n.tag_start(
        'div',
        array(
            'class' => 'txp-layout-1col',
            'id'    => $event.'_container',
        )
    );

    echo $searchBlock
        .$contentBlockStart
        .$createBlock;

    if ($total < 1) {
        if ($criteria != 1) {
            echo graf(
                span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                gTxt('no_results_found'),
                array('class' => 'alert-block information')
            );
        } else {
            echo graf(
                span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                gTxt('no_skin_recorded'),
                array('class' => 'alert-block error')
            );
        }

        echo n.tag_end('div') // End of .txp-layout-1col.
            .n.'</div>';      // End of .txp-layout.

        return;
    }

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $paginator->getLimit();

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    $countNames = array('section', 'page', 'form', 'css');
    $rsThings = array('*');

    foreach ($countNames as $countName) {
        $rsThings[] = '(SELECT COUNT(*) '
                      .'FROM '.safe_pfx_j('txp_'.$countName).' '
                      .'WHERE txp_'.$countName.'.skin = txp_skin.name) '
                      .$countName.'_count';
    }

    $rs = safe_rows_start(
        implode(', ', $rsThings),
        'txp_skin',
        $criteria.' order by '.$sortSQL.' limit '.$offset.', '.$limit
    );

    if ($rs) {
        echo n.tag_start('form', array(
                'class'  => 'multi_edit_form',
                'id'     => 'skin_form',
                'name'   => 'longform',
                'method' => 'post',
                'action' => 'index.php',
            ))
            .n.tag_start('div', array('class' => 'txp-listtables'))
            .n.tag_start('table', array('class' => 'txp-list'))
            .n.tag_start('thead');

        $ths = hCell(
            fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
            '',
            ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
        );

        $thIds = array(
            'name'          => 'name',
            'title'         => 'title',
            'version'       => 'version',
            'author'        => 'author',
            'section_count' => 'tab_sections',
            'page_count'    => 'tab_pages',
            'form_count'    => 'tab_forms',
            'css_count'     => 'tab_style',
        );

        foreach ($thIds as $thId => $thVal) {
            $thClass = 'txp-list-col-'.$thId
                      .($thId == $sort ? ' '.$dir : '')
                      .($thVal !== $thId ? ' skin_detail' : '');

            $ths .= column_head($thVal, $thId, 'skin', true, $switchDir, $crit, $search_method, $thClass);
        }

        echo tr($ths)
            .n.tag_end('thead')
            .n.tag_start('tbody');

        while ($a = nextRow($rs)) {
            extract($a, EXTR_PREFIX_ALL, 'skin');

            $editUrl = array(
                'event'         => 'skin',
                'step'          => 'edit',
                'name'          => $skin_name,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
            );

            $tdAuthor = txpspecialchars($skin_author);

            empty($skin_author_uri) or $tdAuthor = href($tdAuthor, $skin_author_uri);

            $tds = td(fInput('checkbox', 'selected[]', $skin_name), '', 'txp-list-col-multi-edit')
                .hCell(
                    href(txpspecialchars($skin_name), $editUrl, array('title' => gTxt('edit'))),
                    '',
                    array(
                        'scope' => 'row',
                        'class' => 'txp-list-col-name',
                    )
                )
                .td(txpspecialchars($skin_title), '', 'txp-list-col-title')
                .td(txpspecialchars($skin_version), '', 'txp-list-col-version')
                .td($tdAuthor, '', 'txp-list-col-author');

            foreach ($countNames as $name) {
                if (${'skin_'.$name.'_count'} > 0) {
                    if ($name === 'section') {
                        $linkParams = array(
                            'event'         => 'section',
                            'search_method' => 'skin',
                            'crit'          => '"'.$skin_name.'"',
                        );
                    } else {
                        $linkParams = array(
                            'event' => $name,
                            'skin'  => $skin_name,
                        );
                    }

                    $tdVal = href(
                        ${'skin_'.$name.'_count'},
                        $linkParams,
                        array(
                            'title' => gTxt(
                                'skin_count_'.$name,
                                array('{num}' => ${'skin_'.$name.'_count'})
                            )
                        )
                    );
                } else {
                    $tdVal = 0;
                }

                $tds .= td($tdVal, '', 'txp-list-col-'.$name.'_count');
            }

            echo tr($tds, array('id' => 'txp_skin_'.$skin_name));
        }

        echo n.tag_end('tbody')
            .n.tag_end('table')
            .n.tag_end('div') // End of .txp-listtables.
            .n.skin_multiedit_form($page, $sort, $dir, $crit, $search_method)
            .n.tInput()
            .n.tag_end('form')
            .n.tag_start(
                'div',
                array(
                    'class' => 'txp-navigation',
                    'id'    => $event.'_navigation',
                )
            )
            .$paginator->render()
            .nav_form('skin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit)
            .n.tag_end('div');
    }

    echo n.tag_end('div') // End of .txp-layout-1col.
        .n.'</div>'; // End of .txp-layout.
}

/**
 * The editor for skins.
 */

function skin_edit($message = '')
{
    global $step;

    require_privs('skin.edit');

    $message ? pagetop(gTxt('tab_skins'), $message) : '';

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
        'name',
    )));

    $fields = Skins::getTableCols();

    if ($name) {
        $rs = Txp::get('\Textpattern\Skin\Main', $name)->getRows()[$name];

        if (!$rs) {
            return skin_list();
        }

        $caption = gTxt('edit_skin');
        $extraAction = href(
            '<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'),
            '#',
            array(
                'class'     => 'txp-clone',
                'data-form' => 'skin_form',
            )
        );
    } else {
        $rs = array_fill_keys($fields, '');
        $caption = gTxt('create_skin');
        $extraAction = '';
    }

    extract($rs, EXTR_PREFIX_ALL, 'skin');
    pagetop(gTxt('tab_skins'));

    $content = hed($caption, 2);

    foreach ($fields as $field) {
        $current = ${'skin_'.$field};

        if ($field === 'description') {
            $input = text_area($field, 0, 0, $current, 'skin_'.$field);
        } elseif ($field === 'name') {
            $input = '<input type="text" value="'.$current.'" id="skin_'.$field.'" name="'.$field.'" size="'.INPUT_REGULAR.'" maxlength="63" required />';
        } else {
            $type = ($field === 'author_uri') ? 'url' : 'text';
            $input = fInput($type, $field, $current, '', '', '', INPUT_REGULAR, '', 'skin_'.$field);
        }

        $content .= inputLabel('skin_'.$field, $input, 'skin_'.$field);
    }

    $content .= pluggable_ui('skin_ui', 'extend_detail_form', '', $rs)
        .graf(
            $extraAction.
            sLink('skin', '', gTxt('cancel'), 'txp-button')
            .fInput('submit', '', gTxt('save'), 'publish'),
            array('class' => 'txp-edit-actions')
        )
        .eInput('skin')
        .sInput('save')
        .hInput('old_name', $skin_name)
        .hInput('old_title', $skin_title)
        .hInput('search_method', $search_method)
        .hInput('crit', $crit)
        .hInput('page', $page)
        .hInput('sort', $sort)
        .hInput('dir', $dir);

    echo form($content, '', '', 'post', 'txp-edit', '', 'skin_form');
}

/**
 * Saves a skin.
 */

function skin_save()
{
    $infos = array_map('assert_string', psa(array(
        'name',
        'title',
        'old_name',
        'old_title',
        'version',
        'description',
        'author',
        'author_uri',
        'copy',
    )));

    extract($infos);

    if (empty($name)) {
        skin_list(array(gTxt('skin_name_invalid'), E_ERROR));
        return;
    }

    $Skin = Txp::get('\Textpattern\Skin\Main');

    if ($old_name) {
        if ($copy) {
            $name === $old_name ? $name .= '_copy' : '';
            $title === $old_title ? $title .= ' (copy)' : '';

            $Skin->setSkinsAssets($name)
                ->create(compact('title', 'version', 'description', 'author', 'author_uri'), $old_name);
        } else {
            $Skin->setSkinsAssets($old_name)
                 ->edit(compact('name', 'title', 'version', 'description', 'author', 'author_uri'));
        }
    } else {
        $title !== '' ?: $title = $name;
        $author !== '' ?: $author = substr(cs('txp_login_public'), 10);
        $version !== '' ?: $version = '0.0.1';
        $row = compact('title', 'version', 'description', 'author', 'author_uri');

        $Skin->setSkinsAssets($name)
             ->create($row);
    }

    skin_list($Skin->getResults());
}

/**
 * Changes and saves the 'pageby' value.
 */

function skin_skin_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    skin_list();
}

/**
 * Renders a multi-edit form widget.
 *
 * @param  int    $page         The page number
 * @param  string $sort         The current sorting value
 * @param  string $dir          The current sorting direction
 * @param  string $crit         The current search criteria
 * @param  string $search_method The current search method
 * @return string HTML
 */

function skin_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    $clean = checkbox2('clean', get_pref('remove_extra_templates', true), 0, 'clean')
            .tag(gtxt('remove_extra_templates'), 'label', array('for' => 'clean'))
            .popHelp('remove_extra_templates');

    $methods = array(
        'import'    => array('label' => gTxt('import'), 'html' => $clean),
        'duplicate' => gTxt('duplicate'),
        'export'    => array('label' => gTxt('export'), 'html' => $clean),
        'delete'    => gTxt('delete'),
    );

    return multi_edit($methods, 'skin', 'multi_edit', $page, $sort, $dir, $crit, $search_method);
}

/**
 * Processes multi-edit actions.
 */

function skin_multi_edit()
{
    global $prefs;

    extract(psa(array(
        'edit_method',
        'selected',
        'clean',
    )));

    if ($clean != get_pref('remove_extra_templates', true)) {
        set_pref('remove_extra_templates', $prefs['remove_extra_templates'] = !empty($clean), 'skin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
    }

    if (!$selected || !is_array($selected)) {
        return skin_list();
    }

    $Skins = Txp::get('\Textpattern\Skin\Main', ps('selected'));

    switch ($edit_method) {
        case 'export':
            $Skins->export($clean);
            break;
        case 'duplicate':
            $Skins->duplicate();
            break;
        case 'import':
            $Skins->import($clean, true);
            break;
        default: // delete.
            $Skins->$edit_method();
            break;
    }

    skin_list($Skins->getResults());
}

/**
 * Imports an uploaded skin into the database.
 */

function skin_import()
{
    $Skin = Txp::get('\Textpattern\Skin\Main', ps('skins'));

    $Skin->import();

    skin_list($Skin->getResults());
}

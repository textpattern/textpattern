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
 * Pages panel.
 *
 * @package Admin\Page
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'page') {
    require_privs('page');

    bouncer($step, array(
        'page_edit'   => false,
        'page_save'   => true,
        'page_delete' => true,
        'tagbuild'    => false,
    ));

    switch (strtolower($step)) {
        case '':
            page_edit();
            break;
        case 'page_edit':
            page_edit();
            break;
        case 'page_save':
            page_save();
            break;
        case 'page_delete':
            page_delete();
            break;
        case 'page_new':
            page_new();
            break;
        case 'tagbuild':
            echo page_tagbuild();
            break;
    }
}

/**
 * The main Page editor panel.
 *
 * @param string|array $message The activity message
 */

function page_edit($message = '')
{
    global $event, $step;

    pagetop(gTxt('edit_pages'), $message);

    extract(array_map('assert_string', gpsa(array(
        'copy',
        'save_error',
        'savenew',
    ))));

    $name = sanitizeForPage(assert_string(gps('name')));
    $newname = sanitizeForPage(assert_string(gps('newname')));

    if ($step == 'page_delete' || empty($name) && $step != 'page_new' && !$savenew) {
        $name = safe_field("page", 'txp_section', "name = 'default'");
    } elseif (((($copy || $savenew) && $newname) || ($newname && ($newname != $name))) && !$save_error) {
        $name = $newname;
    }

    $titleblock = inputLabel(
        'new_page',
        fInput('text', 'newname', $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_page', false, true),
        'page_name',
        array('', 'instructions_page_name'),
        array('class' => 'txp-form-field name')
    );

    if ($name === '') {
        $titleblock .= hInput('savenew', 'savenew');
    } else {
        $titleblock .= hInput('name', $name);
    }

    $titleblock .= eInput('page').sInput('page_save');

    $html = (!$save_error) ? fetch('user_html', 'txp_page', 'name', $name) : gps('html');

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_pages'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        );

    // Pages create/switcher column.

    $actionsExtras = '';

    if ($name) {
        $actionsExtras .= href('<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'), '#', array(
            'class'     => 'txp-clone',
            'data-form' => 'page_form',
        ));
    }

    $actions = graf(
        sLink('page', 'page_new', '<span class="ui-icon ui-extra-icon-new-document"></span> '.gTxt('create_new_page'), 'txp-new').
        $actionsExtras,
        array('class' => 'txp-actions txp-actions-inline')
    );

    $buttons = graf(
        tag_void('input', array(
            'class'  => 'publish',
            'type'   => 'submit',
            'method' => 'post',
            'value'  =>  gTxt('save'),
        )), ' class="txp-save"'
    );

    echo n.tag(
        page_list($name).n,
        'div', array(
            'class' => 'txp-layout-4col-alt',
            'id'    => 'content_switcher',
            'role'  => 'region',
        )
    );

    // Pages code columm.

    echo n.tag(
        form(
            $actions.
            $titleblock.
            inputLabel(
                'html',
                '<textarea class="code" id="html" name="html" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($html).'</textarea>',
                array(
                    'page_code',
                    n.href('<span class="ui-icon ui-extra-icon-code"></span> '.gTxt('tagbuilder'), '#', array('class' => 'txp-tagbuilder-dialog')),
                ),
                array('', 'instructions_page_code'),
                array('class' => 'txp-form-field'),
                array('div', 'div')
            ).
            $buttons
            , '', '', 'post', '', '', 'page_form'),
        'div', array(
            'class' => 'txp-layout-4col-3span',
            'id'    => 'main_content',
            'role'  => 'region',
        )
    );

    // Tag builder dialog.
    echo n.tag(
        page_tagbuild(),
        'div', array(
            'class'      => 'txp-tagbuilder-content',
            'id'         => 'tagbuild_links',
            'aria-label' => gTxt('tagbuilder'),
            'title'      => gTxt('tagbuilder'),
    ));

    echo n.'</div>'; // End of .txp-layout.
}

/**
 * Renders a list of page templates.
 *
 * @param  string $current The selected template
 * @return string HTML
 */

function page_list($current)
{
    $out = array();
    $protected = safe_column("DISTINCT page", 'txp_section', "1 = 1") + array('error_default');

    $criteria = 1;
    $criteria .= callback_event('admin_criteria', 'page_list', 0, $criteria);

    $rs = safe_rows_start("name", 'txp_page', "$criteria ORDER BY name ASC");

    if ($rs) {
        while ($a = nextRow($rs)) {
            extract($a);
            $active = ($current === $name);

            $edit = eLink('page', '', 'name', $name, $name);

            if (!in_array($name, $protected)) {
                $edit .= dLink('page', 'page_delete', 'name', $name);
            }

            $out[] = tag($edit, 'li', array(
                'class' => $active ? 'active' : '',
            ));
        }

        $out = tag(join(n, $out), 'ul', array(
            'class' => 'switcher-list',
        ));

        return wrapGroup('all_pages', $out, 'all_pages');
    }
}

/**
 * Deletes a page template.
 */

function page_delete()
{
    $name = ps('name');
    $count = safe_count('txp_section', "page = '".doSlash($name)."'");
    $message = '';

    if ($name == 'error_default') {
        return page_edit();
    }

    if ($count) {
        $message = array(gTxt('page_used_by_section', array('{name}' => $name, '{count}' => $count)), E_WARNING);
    } else {
        if (safe_delete('txp_page', "name = '".doSlash($name)."'")) {
            callback_event('page_deleted', '', 0, $name);
            $message = gTxt('page_deleted', array('{name}' => $name));
        }
    }

    page_edit($message);
}

/**
 * Saves or clones a page template.
 */

function page_save()
{
    extract(doSlash(array_map('assert_string', psa(array(
        'savenew',
        'html',
        'copy',
    )))));

    $name = sanitizeForPage(assert_string(ps('name')));
    $newname = sanitizeForPage(assert_string(ps('newname')));

    $save_error = false;
    $message = '';

    if (!$newname) {
        $message = array(gTxt('page_name_invalid'), E_ERROR);
        $save_error = true;
    } else {
        if ($copy && ($name === $newname)) {
            $newname .= '_copy';
            $_POST['newname'] = $newname;
        }

        $exists = safe_field("name", 'txp_page', "name = '".doSlash($newname)."'");

        if ($newname !== $name && $exists !== false) {
            $message = array(gTxt('page_already_exists', array('{name}' => $newname)), E_ERROR);
            if ($savenew) {
                $_POST['newname'] = '';
            }

            $save_error = true;
        } else {
            if ($savenew or $copy) {
                if ($newname) {
                    if (safe_insert('txp_page', "name = '".doSlash($newname)."', user_html = '$html'")) {
                        update_lastmod('page_created', compact('newname', 'name', 'html'));
                        $message = gTxt('page_created', array('{name}' => $newname));
                    } else {
                        $message = array(gTxt('page_save_failed'), E_ERROR);
                        $save_error = true;
                    }
                } else {
                    $message = array(gTxt('page_name_invalid'), E_ERROR);
                    $save_error = true;
                }
            } else {
                if (safe_update('txp_page', "user_html = '$html', name = '".doSlash($newname)."'", "name = '".doSlash($name)."'")) {
                    safe_update('txp_section', "page = '".doSlash($newname)."'", "page = '".doSlash($name)."'");
                    update_lastmod('page_saved', compact('newname', 'name', 'html'));
                    $message = gTxt('page_updated', array('{name}' => $name));
                } else {
                    $message = array(gTxt('page_save_failed'), E_ERROR);
                    $save_error = true;
                }
            }
        }
    }

    if ($save_error === true) {
        $_POST['save_error'] = '1';
    } else {
        callback_event('page_saved', '', 0, $name, $newname);
    }

    page_edit($message);
}

/**
 * Directs requests to page_edit() armed with a 'page_new' step.
 *
 * @see page_edit()
 */

function page_new()
{
    page_edit();
}

/**
 * Return a list of tag builder tags.
 *
 * @return HTML
 */

function page_tagbuild()
{
    $listActions = graf(
        href('<span class="ui-icon ui-icon-arrowthickstop-1-s"></span> '.gTxt('expand_all'), '#', array(
            'class'         => 'txp-expand-all',
            'aria-controls' => 'tagbuild_links',
        )).
        href('<span class="ui-icon ui-icon-arrowthickstop-1-n"></span> '.gTxt('collapse_all'), '#', array(
            'class'         => 'txp-collapse-all',
            'aria-controls' => 'tagbuild_links',
        )), array('class' => 'txp-actions')
    );

    // Format of each entry is popTagLink -> array ( gTxt() string, class/ID).
    $tagbuild_items = array(
        'page_article'     => array('page_article_hed','article-tags'),
        'page_article_nav' => array('page_article_nav_hed', 'article-nav-tags'),
        'page_nav'         => array('page_nav_hed', 'nav-tags'),
        'page_xml'         => array('page_xml_hed', 'xml-tags'),
        'page_misc'        => array('page_misc_hed', 'misc-tags'),
        'page_file'        => array('page_file_hed', 'file-tags'),
    );

    $tagbuild_links = '';

    foreach ($tagbuild_items as $tb => $item) {
        $tagbuild_links .= wrapRegion($item[1].'_group', taglinks($tb), $item[1], $item[0], 'page_'.$item[1]);
    }

    return $listActions.$tagbuild_links;
}

/**
 * Renders a list of tag builder options.
 *
 * @param  string $type
 * @return HTML
 * @access private
 * @see    popTagLinks()
 */

function taglinks($type)
{
    return popTagLinks($type);
}

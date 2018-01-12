<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2005 Dean Allen
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

if ($event === 'skin') {
    require_privs($event);

    $skin = Txp::get('Textpattern\Skin\Skin');
    $view = Txp::get('Textpattern\Skin\Admin', $skin);

    $availableSteps = array(
        'skin_change_pageby' => true, // Prefixed to make it work with the paginator…
        'list'          => false,
        'edit'          => false,
        'save'          => true,
        'import'        => false,
        'multi_edit'    => true,
    );

    if ($step && bouncer($step, $availableSteps)) {
        if (function_exists('skin_'.$step)) {
            call_user_func('skin_'.$step);
            $view->render();
        } elseif (is_callable([$view, $step])) {
            $view->$step();
        } else {
            $view->render();
        }
    } else {
        $view->render();
    }
}

/**
 * Imports skins.
 *
 * @param  bool   $clean    Whether to removes extra skin template rows or not;
 * @param  bool   $override Whether to insert or update the skins.
 * @return object $this.
 */

function skin_import()
{
    global $skin;

    $skin->setNames(array(ps('skins')))->import(false);

    return $skin;
}

/**
 * Saves a skin.
 */

function skin_save()
{
    global $skin;

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
        $skin->setResults('skin_name_invalid', $name);
    } elseif ($old_name) {
        if ($copy) {
            $name === $old_name ? $name .= '_copy' : '';
            $title === $old_title ? $title .= ' (copy)' : '';

            $skin->setInfos($name, $title, $version, $description, $author, $author_uri)
                 ->setBase($old_name)
                 ->create();

        } else {
            $skin->setInfos($name, $title, $version, $description, $author, $author_uri)
                 ->setBase($old_name)
                 ->update();
        }
    } else {
        $title === '' ? $title = ucfirst($name) : '';
        $author === '' ? $author = substr(cs('txp_login_public'), 10) : '';
        $version === '' ? $version = '0.0.1' : '';

        $skin->setInfos($name, $title, $version, $description, $author, $author_uri)
             ->create();
    }

    return $skin;
}

/**
 * Processes multi-edit actions.
 */

function skin_multi_edit()
{
    global $skin;

    extract(psa(array(
        'edit_method',
        'selected',
        'clean',
    )));

    if (!$selected || !is_array($selected)) {
        return skin_list();
    }

    $skin->setNames(ps('selected'));

    switch ($edit_method) {
        case 'export':
            $skin->export($clean);
            break;
        case 'duplicate':
            $skin->duplicate();
            break;
        case 'import':
            $skin->import($clean, true);
            break;
        default: // delete.
            $skin->$edit_method();
            break;
    }

    return $skin;
}

/**
 * Changes and saves the 'pageby' value.
 */

function skin_skin_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    skin_list();
}

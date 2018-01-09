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

    $model = Txp::get('Textpattern\Skin\Main\Model');
    $controller = $step === 'save' ? Txp::get('Textpattern\Skin\Main\Single', $model) : Txp::get('Textpattern\Skin\Main\Multiple', $model);
    $view = Txp::get('Textpattern\Skin\Main\View', $model);

    $availableSteps = array(
        'skin_change_pageby' => true, // Prefixed to make it work with the paginator…
        'list'          => false,
        'edit'          => false,
        'save'          => true,
        'import'        => false,
        'multiEdit'    => true,
    );

    if ($step && bouncer($step, $availableSteps)) {
        if (is_callable([$controller, $step])) {
            $controller->$step();
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
 * Changes and saves the 'pageby' value.
 */

function skin_skin_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
    skin_list();
}

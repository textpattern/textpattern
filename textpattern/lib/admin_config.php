<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * Collection of user configuration options.
 *
 * @package User
 */

/**
 * Sets permissions.
 *
 * @global array $txp_permissions
 */

$txp_permissions = array(
    'admin'                      => '1,2,3,4,5,6',
    'admin.edit'                 => '1',
    'admin.edit.own'             => '1,2,3,4,5,6',
    'admin.list'                 => '1,2,3',
    'article.delete.own'         => '1,2,3,4,5',
    'article.delete'             => '1,2',
    'article.edit'               => '1,2,3',
    'article.edit.published'     => '1,2,3',
    'article.edit.own'           => '1,2,3,4,5,6',
    'article.edit.own.published' => '1,2,3,4',
    'article.preview'            => '1,2,3,4',
    'article.publish'            => '1,2,3,4',
    'article.php'                => '1,2,3',
    'article.set_markup'         => '1,2,3,    6',
    'article'                    => '1,2,3,4,5,6',
    'list'                       => '1,2,3,4,5,6', // Likely the same as for article.
    'category'                   => '1,2,3',
    'css'                        => '1,2,      6',
    'debug.verbose'              => '1,2',
    'debug.backtrace'            => '1',
    'diag'                       => '1,2',
    'discuss'                    => '1,2,3',
    'file'                       => '1,2,3,4',
    'file.edit'                  => '1,2',
    'file.edit.own'              => '1,2,3,4',
    'file.delete'                => '1,2',
    'file.delete.own'            => '1,2,3,4',
    'file.publish'               => '1,2,3,4',
    'form'                       => '1,2,3,    6',
    'image'                      => '1,2,3,4,5,6',
    'image.create.trusted'       => '', // Deprecated in 4.7.0
    'image.edit'                 => '1,2,3,    6',
    'image.edit.own'             => '1,2,3,4,5,6',
    'image.delete'               => '1,2',
    'image.delete.own'           => '1,2,3,4,5,6',
    'lang'                       => '1,2,3,4,5,6',
    'lang.edit'                  => '1,2',
    'link'                       => '1,2,3',
    'link.edit'                  => '1,2,3',
    'link.edit.own'              => '1,2,3',
    'link.delete'                => '1,2',
    'link.delete.own'            => '1,2,3',
    'log'                        => '1,2,3', // More?
    'page'                       => '1,2,3,    6',
    'pane'                       => '1,2,3,4,5,6',
    'plugin'                     => '1,2',
    'prefs'                      => '1,2,3,4,5,6',
    'prefs.edit'                 => '1,2',
    'prefs.site'                 => '1,2',
    'prefs.admin'                => '1,2',
    'prefs.publish'              => '1,2',
    'prefs.feeds'                => '1,2',
    'prefs.custom'               => '1,2',
//    'prefs.comments'             => '1,2',
//    'prefs.advanced_options'     => '1,2',
    'section'                    => '1,2,      6',
    'section.edit'               => '1,2,      6',
    'skin'                       => '1,2,      6',
    'skin.edit'                  => '1,2,      6',
    'tab.admin'                  => '1,2,3,4,5,6',
    'tab.content'                => '1,2,3,4,5,6',
    'tab.extensions'             => '1,2',
    'tab.presentation'           => '1,2,3,    6',
    'tag'                        => '1,2,3,4,5,6',
    'help'                       => '1,2,3,4,5,6',
);

/**
 * List of user groups.
 *
 * @global array $txp_groups
 */

$txp_groups = array(
    1 => 'publisher',
    2 => 'managing_editor',
    3 => 'copy_editor',
    4 => 'staff_writer',
    5 => 'freelancer',
    6 => 'designer',
    0 => 'privs_none',
);

/**
 * List of pluggable options.
 *
 * @global array $txp_options
 */

$txp_options = array(
    'advanced_options' => '1,2',
    'use_comments' => array(
        'prefs.comments' => '1,2'
    ),
    'enable_dev_preview' => array(
        0 => '1,2,      6',
        'skin.preview' => true
    )
);

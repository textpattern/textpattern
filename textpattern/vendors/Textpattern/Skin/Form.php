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
 * Form
 *
 * Manages Form.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin;

class Form extends AssetBase implements FormInterface, \Textpattern\Container\FactorableInterface
{
    /**
     * {@inheritdoc}
     */

    protected static $dir = TXP_THEME_TREE['forms'];

    /**
     * {@inheritdoc}
     */

    protected static $subdirField = 'type';

    /**
     * The expected subdirs for this asset type.
     *
     * Note the order of the values is the order the blocks appear in the
     * Presentation->Forms panel.
     *
     * @var array
     */

    protected static $subdirValues = array('article', 'misc', 'category', 'comment', 'file', 'link', 'section');

    /**
     * {@inheritdoc}
     */

    protected static $defaultSubdir = 'misc';

    /**
     * {@inheritdoc}
     */

    protected static $fileContentsField = 'Form';

    /**
     * {@inheritdoc}
     */

    protected static $essential = array(
        array(
            'name' => 'comments',
            'type' => 'comment',
            'Form' => '<!-- Contents of the \'comments\' comment form goes here. Refer to https://docs.textpattern.com/ for further information. -->',
        ),
        array(
            'name' => 'comments_display',
            'type' => 'comment',
            'Form' => '<!-- Contents of the \'comments_display\' comment form goes here. Refer to https://docs.textpattern.com/ for further information. -->',
        ),
        array(
            'name' => 'comment_form',
            'type' => 'comment',
            'Form' => '<!-- Contents of the \'comments_form\' comment form goes here. Refer to https://docs.textpattern.com/ for further information. -->',
        ),
        array(
            'name' => 'default',
            'type' => 'article',
            'Form' => '<!-- Contents of the \'default\' article form goes here. Refer to https://docs.textpattern.com/ for further information. -->',
        ),
        array(
            'name' => 'plainlinks',
            'type' => 'link',
            'Form' => '<!-- Contents of the \'plainlinks\' link form goes here. Refer to https://docs.textpattern.com/ for further information. -->',
        ),
        array(
            'name' => 'files',
            'type' => 'file',
            'Form' => '<!-- Contents of the \'files\' file form goes here. Refer to https://docs.textpattern.com/ for further information. -->',
        ),
    );

    /**
     * Constructor
     */

    public function getInstance()
    {
        $textarray = array();

        if ($custom_types = parse_ini_string(get_pref('custom_form_types'), true)) {
            static::$mimeTypes = get_mediatypes($textarray);
        } else {
            $custom_types = array();
        }

        \Txp::get('\Textpattern\L10n\Lang')->setPack($textarray, true);

        static::$subdirValues = array_unique(array_merge(
            static::$subdirValues,
            array_keys($custom_types),
            array_keys(static::$mimeTypes)
        ));

        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function setInfos(
        $name,
        $type = null,
        $Form = null
    ) {
        $name = $this->setName($name)->getName();

        $this->infos = compact('name', 'type', 'Form');

        return $this;
    }

    /**
     * $defaultSubdir property getter.
     */

    public static function getTypes()
    {
        return static::$subdirValues;
    }
}

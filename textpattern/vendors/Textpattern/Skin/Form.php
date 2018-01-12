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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * SharedBase
 *
 * Extended by Main and AssetBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Form extends AssetBase
    {
        protected static $table = 'txp_form';
        protected static $fileContentsFields = 'Form';
        protected static $subdirField = 'type';
        protected static $defaultSubdir = 'misc';
        protected static $defaultDir = 'forms';
        protected static $string = 'form';
        protected static $essential = array(
            array(
                'name' => 'comments',
                'type' => 'comment',
                'Form' => '',
            ),
            array(
                'name' => 'comments_display',
                'type' => 'comment',
                'Form' => '',
            ),
            array(
                'name' => 'comment_form',
                'type' => 'comment',
                'Form' => '',
            ),
            array(
                'name' => 'default',
                'type' => 'article',
                'Form' => '',
            ),
            array(
                'name' => 'plainlinks',
                'type' => 'link',
                'Form' => '',
            ),
            array(
                'name' => 'files',
                'type' => 'file',
                'Form' => '',
            ),
        );

        public function setInfos(
            $name,
            $type = null,
            $Form = null
        ) {
            $name = sanitizeForTheme($name);

            $this->infos = compact('name', 'type', 'Form');
            $this->setName($name);

            return $this;
        }
    }
}

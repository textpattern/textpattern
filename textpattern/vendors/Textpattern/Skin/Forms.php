<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Forms
 *
 * Manages skins related forms.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin {

    class Forms extends AssetBase
    {
        protected static $asset = 'form';
        protected static $dir = 'forms';
        protected static $table = 'txp_form';
        protected static $tableCols;
        protected static $subdirCol = 'type';
        protected static $contentsCol = 'Form';
        protected static $essential = array(
            'article' => array('default'),
            'comment' => array('comments', 'comments_display', 'comment_form'),
            'link'    => array('plainlinks'),
            'file'    => array('files'),
        );

        /**
         * {@inheritdoc}
         */

        public static function getEssentialNames($types = null)
        {
            $types ?: $types = array_keys(static::$essential);

            $essential = array();

            foreach ($types as $type) {
                if (array_key_exists($type, static::$essential)) {
                    $essential = array_merge($essential, static::$essential[$type]);
                }
            }

            return $essential;
        }

        /**
         * {@inheritdoc}
         */

        public function cleanExtraRows($skin, $not)
        {
            foreach ($not as $type => $names_not) {
                $files = self::getRecDirIterator($skin.'/'.self::getDir().'/'.$type);

                foreach ($files as $file) {
                    $name = $file->getTemplateName();

                    if (!$names_not || ($names_not && !in_array($name, $names_not))) {
                        unlink($file->getPathname());
                    }
                }
            }
        }
    }
}

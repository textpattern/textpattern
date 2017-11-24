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
 * Pages
 *
 * Manages skin pages directly or via the Skin class.
 *
 * @since   4.7.0
 * @package Skin
 * @see     AssetInterface
 */

namespace Textpattern\Skin {

    class Pages extends AssetBase
    {
        protected static $dir = 'pages';
        protected static $table = 'txp_page';
        protected static $essential = array('default', 'error_default');

        /**
         * {@inheritdoc}
         */

        protected function getCreationSQLValues($templates)
        {
            $sql = array();

            foreach ($templates as $name) {
                $sql[] = "('".doSlash($name)."', '', '".doSlash($this->skin)."')";
            }

            return $sql;
        }

        /**
         * {@inheritdoc}
         */

        protected function getImportSQLValue(RecDirIterator $file)
        {
            return "('".doSlash($file->getTemplateName())."', "
                   ."'".doSlash($file->getTemplateContents())."', "
                   ."'".doSlash($this->skin)."')";
        }

        /**
         * {@inheritdoc}
         */

        public function exportTemplate($row)
        {
            extract($row);

            return (bool) file_put_contents(
                $this->getPath(static::$dir.'/'.$name.'.'.static::$extension),
                $user_html ? $user_html : '// Empty page.'
            );
        }
    }
}

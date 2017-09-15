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
 * Manages skin forms directly or via the Skin class.
 *
 * @since   4.7.0
 * @package Skin
 * @see     AssetInterface
 */

namespace Textpattern\Skin {

    class Forms extends Pages
    {
        protected static $dir = 'forms';
        protected static $depth = 1; // Forms stored by type in subfolders.
        protected static $table = 'txp_form';
        protected static $columns = array('skin', 'name', 'type', 'Form');
        protected static $essential = array(
            'comments'         => 'comment',
            'comments_display' => 'comment',
            'comment_form'     => 'comment',
            'default'          => 'article',
            'plainlinks'       => 'link',
            'files'            => 'file',
        );

        /**
         * {@inheritdoc}
         */

        protected function getCreationSQLValues($templates)
        {
            $sql = array();

            foreach ($templates as $name => $type) {
                $sql[] = "("
                  ."'".doSlash(strtolower(sanitizeForUrl($this->skin)))."', "
                  ."'".doSlash($name)."', "
                  ."'".doSlash($type)."', "
                  ."''"
                  .")";
            }

            return $sql;
        }

        /**
         * {@inheritdoc}
         */

        protected function getImportSQLValue(RecDirIterator $file)
        {
            return "("
                ."'".doSlash($this->skin)."', "
                ."'".doSlash($file->getTemplateName())."', "
                ."'".doSlash($file->getTemplateType())."', "
                ."'".doSlash($file->getTemplateContents())."'"
                .")";
        }

        /**
         * {@inheritdoc}
         */

        public function exportTemplate($row)
        {
            extract($row);

            $path = static::$dir.'/'.$type;

            if ($this->isWritable($path) || $this->mkDir($path)) {
                return (bool) file_put_contents(
                    $this->getPath($path.'/'.$name.'.'.static::$extension),
                    $Form
                );
            }
        }
    }
}

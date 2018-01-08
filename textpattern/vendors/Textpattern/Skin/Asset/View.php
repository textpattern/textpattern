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
 * Pages
 *
 * Manages skins related pages.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin\Asset {

    abstract class View
    {
        /**
         * {@inheritdoc}
         */

        public function __construct()
        {
        }

        /**
         * Gets an array of the installed skins.
         *
         * @return array Associative array of skin names and their related title.
         */

        public static function skinSwitchForm($event, $step, $current)
        {
            $installed = Textpattern\Skin\Asset\Page\Model::getInstalled();

            if ($installed) {
                return form(
                    inputLabel(
                        'skin',
                        selectInput('skin', $installed, $current, false, 1, 'skin'),
                        'skin'
                    )
                    .eInput($event)
                    .sInput($step),
                    '',
                    '',
                    'post'
                );
            }

            return;
        }
    }
}

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
 * Generates a password.
 *
 * <code>
 * echo Txp::get('\Textpattern\Password\Generator')->generate(16);
 * </code>
 *
 * @since   4.6.0
 * @package Password
 */

namespace Textpattern\Password;

class Generator
{
    /**
     * Stores the character table.
     *
     * @var array
     */

    protected $chars;

    /**
     * Gets the character table.
     *
     * @return array
     */

    public function getCharacterTable()
    {
        if (!$this->chars) {
            $this->chars = str_split(PASSWORD_SYMBOLS);
        }

        return $this->chars;
    }

    /**
     * Generates a random password.
     *
     * <code>
     * echo Txp::get('\Textpattern\Password\Generator')->generate(16);
     * </code>
     *
     * @param  int $length The length of the generated password
     * @return string The password
     */

    public function generate($length)
    {
        $pool = false;
        $pass = '';

        for ($i = 0; $i < $length; $i++) {
            if (!$pool) {
                $pool = $this->getCharacterTable();
            }

            $index = mt_rand(0, count($pool) - 1);
            $pass .= $pool[$index];
            unset($pool[$index]);
            $pool = array_values($pool);
        }

        return $pass;
    }
}

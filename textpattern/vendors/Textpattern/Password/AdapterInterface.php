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
 * Password hashing implementation template.
 *
 * @since   4.6.0
 * @package Password
 */

namespace Textpattern\Password;

interface AdapterInterface extends \Textpattern\Adaptable\AdapterInterface
{
    /**
     * Verifies the password.
     *
     * @param  string $password The password
     * @param  string $hash     The hash
     * @return bool   TRUE if the password matches the hash
     */

    public function verify($password, $hash);

    /**
     * Hashes the password.
     *
     * @param  string $password
     * @return string The hash
     */

    public function hash($password);
}

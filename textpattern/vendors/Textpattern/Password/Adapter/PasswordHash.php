<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2016 The Textpattern Development Team
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
 * Adapter for PHPass.
 *
 * @since   4.6.0
 * @package Password
 */

namespace Textpattern\Password\Adapter;

class PasswordHash implements \Textpattern\Password\AdapterInterface
{
    /**
     * Stores an instance of PHPass.
     *
     * @var \PasswordHash
     */

    private $phpass;

    /**
     * Constructor.
     */

    public function __construct()
    {
        $this->phpass = new \PasswordHash(PASSWORD_COMPLEXITY, PASSWORD_PORTABILITY);
    }

    /**
     * {@inheritdoc}
     */

    public function verify($password, $hash)
    {
        return $this->phpass->CheckPassword($password, $hash);
    }

    /**
     * {@inheritdoc}
     */

    public function hash($password)
    {
        return $this->phpass->HashPassword($password);
    }
}

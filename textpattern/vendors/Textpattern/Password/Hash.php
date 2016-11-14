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
 * Hashes and verifies a password.
 *
 * <code>
 * echo Txp::get('\Textpattern\Password\Hash')->hash('password');
 * </code>
 *
 * @since   4.6.0
 * @package Password
 */

namespace Textpattern\Password;

class Hash extends \Textpattern\Adaptable\Providable
{
    /**
     * {@inheritdoc}
     */

    public function getDefaultAdapter()
    {
        return new \Textpattern\Password\Adapter\PasswordHash();
    }
}

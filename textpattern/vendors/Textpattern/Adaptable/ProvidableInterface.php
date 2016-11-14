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
 * Adaptable interface using overridable provider.
 *
 * @since   4.6.0
 * @package Adaptable
 */

namespace Textpattern\Adaptable;

interface ProvidableInterface
{
    /**
     * Sets the current adapter.
     *
     * @param  \Textpattern\Adaptable\Adapter $adapter The adapter
     * @return ProvidableInterface
     */

    public function setAdapter(\Textpattern\Adaptable\Adapter $adapter);

    /**
     * Gets the current adapter.
     *
     * @return \Textpattern\Adaptable\Adapter
     */

    public function getAdapter();

    /**
     * Gets the original default adapter.
     *
     * @return \Textpattern\Adaptable\Adapter
     */

    public function getDefaultAdapter();
}

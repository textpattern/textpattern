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

namespace Textpattern\Textpack;

/**
 * Textpack string template.
 *
 * @since   4.6.0
 * @package Textpack
 */

interface StringInterface
{
    /**
     * Gets the name of the string.
     *
     * @return string
     */

    public function getName();

    /**
     * Gets the language.
     *
     * @return string
     */

    public function getLanguage();

    /**
     * Gets the translation string contents.
     *
     * @return string
     */

    public function getString();

    /**
     * Gets the event.
     *
     * @return string
     */

    public function getEvent();

    /**
     * Gets the owner.
     *
     * @return string
     */

    public function getOwner();

    /**
     * Gets the version.
     *
     * @return string
     */

    public function getVersion();

    /**
     * Gets the last modification timestamp.
     *
     * @return int
     */

    public function getLastmod();
}

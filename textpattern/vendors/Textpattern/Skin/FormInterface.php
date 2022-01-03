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
 * Form Interface
 *
 * Implemented by Form.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin;

interface FormInterface
{
    /**
     * $infos+$name properties setter.
     *
     * @param  string $name Form name;
     * @param  string $type Form type;
     * @param  string $Form Form contents;
     * @return object $this The current class object (chainable).
     */

    public function setInfos(
        $name,
        $type = null,
        $Form = null
    );

    /**
     *  $subdirValues getter.
     */

    public static function getTypes();
}

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
 * Asset Interface
 *
 * Implemented by AssetBase.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin;

interface AssetInterface
{
    /**
     * $skin property setter.
     */

    public function setSkin(Skin $skin = null);

    /**
     * $essential property getter.
     *
     * @param  string $key      $essential property array key for which you want to get values.
     * @param  string $whereKey Array key used to filter the output with $valueIn.
     * @param  array  $valueIn  Values to check against the $whereKey values to filter the output.
     * @return array           Filtered values.
     */

    public static function getEssential(
        $key = null,
        $whereKey = null,
        $valueIn = null
    );

    /**
     * Build the Skin switch form.
     *
     * @return HTML Auto submitted select list of installed skins.
     */

    public function getSelectEdit();
}

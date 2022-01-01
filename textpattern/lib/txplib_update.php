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
 * Collection of update tools.
 *
 * @package Update
 */

/**
 * Installs language strings from a file.
 *
 * This function imports language strings to the database
 * from a file placed in the ../lang directory.
 *
 * Running this function will delete any missing strings of any
 * language specific event that were included in the file. Empty
 * strings are also stripped from the database.
 *
 * @param      string $lang The language code
 * @return     bool TRUE on success
 * @package    L10n
 * @deprecated in 4.7.0
 */

function install_language_from_file($lang)
{
    return Txp::get('\Textpattern\L10n\Lang')->installFile($lang);
}

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

/*
 * Deprecation warning: This file serves merely as a compatibility layer for \Textpattern\Admin\Theme.
 * Use the base class for new and updated code.
 * TODO: Remove in v4.next.0
 */

/**
 * Base for admin-side themes.
 *
 * @package Admin\Theme
 */

/**
 * Admin-side theme.
 *
 * @package Admin\Theme
 * @deprecated in 4.6.0
 * @see \Textpattern\Admin\Theme
 */

abstract class theme extends \Textpattern\Admin\Theme
{
}

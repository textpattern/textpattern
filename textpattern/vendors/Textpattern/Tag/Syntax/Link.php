<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2014 The Textpattern Development Team
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
 * Link tags.
 *
 * @since  4.6.0
 */

class Textpattern_Tag_Syntax_Link
{
	/**
	 * Checks if the link is the first in the list.
	 *
	 * @param  array  $atts
	 * @param  string $thing
	 * @return string
	 */

	static public function if_first_link($atts, $thing)
	{
		global $thislink;

		assert_link();

		return parse(EvalElse($thing, !empty($thislink['is_first'])));
	}

	/**
	 * Checks if the link is the last in the list.
	 *
	 * @param  array  $atts
	 * @param  string $thing
	 * @return string
	 */

	static public function if_last_link($atts, $thing)
	{
		global $thislink;

		assert_link();

		return parse(EvalElse($thing, !empty($thislink['is_last'])));
	}
}
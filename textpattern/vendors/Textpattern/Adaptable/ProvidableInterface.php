<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2013 The Textpattern Development Team
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

interface Textpattern_Adaptable_ProvidableInterface
{
	/**
	 * Sets the default adapter.
	 *
	 * This method sets or overrides the default adapter
	 * with an static object.
	 *
	 * @param Textpattern_Adaptable_Provider $provider The overriding adapter
	 */

	static public function setDefaultAdaptableProvider(Textpattern_Adaptable_Provider $provider);

	/**
	 * Sets the current adapter.
	 *
	 * @param  Textpattern_Adaptable_Provider $provider The adapter
	 * @return Textpattern_Adaptable_ProvidableInterface
	 */

	public function setAdaptableProvider(Textpattern_Adaptable_Provider $provider);

	/**
	 * Gets the current adapter.
	 *
	 * @return Textpattern_Adaptable_Provider
	 */

	public function getAdaptableProvider();

	/**
	 * Gets the original default adapter.
	 *
	 * @return Textpattern_Adaptable_Provider
	 */

	public function getDefaultAdaptableProvider();
}
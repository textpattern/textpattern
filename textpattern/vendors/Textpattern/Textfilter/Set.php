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
 * Textpattern_Textfilter_Set: A set of Textfilters interfaces those to the core.
 *
 * @since   4.6.0
 * @package Textfilter
 */

class Textpattern_Textfilter_Set implements ArrayAccess, IteratorAggregate
{
	/**
	 * Stores an instance.
	 *
	 * @var Textpattern_Textfilter_Set
	 */

	private static $instance;

	/**
	 * An array of filters.
	 *
	 * @var array
	 */

	private $filters;

	/**
	 * Preference name for a comma-separated list of available Textfilters.
	 */

	const filterprefs = 'admin_textfilter_classes';

	/**
	 * Default Textfilter preference value.
	 */

	const corefilters = 'Textpattern_Textfilter_Plain, Textpattern_Textfilter_Nl2Br, Textpattern_Textfilter_Textile';

	/**
	 * Private constructor.
	 *
	 * This is not a publicly instantiable class.
	 *
	 * Creates core Textfilters according to a preference and
	 * registers all available filters with the core.
	 */

	private function __construct()
	{
		// Construct core Textfilters from preferences.
		foreach (do_list(get_pref(self::filterprefs, self::corefilters)) as $f)
		{
			if (class_exists($f))
			{
				new $f;
			}
		}

		$this->filters = array();

		// Broadcast a request for registration to both core Textfilters and Textfilter plugins.
		callback_event('textfilter', 'register', 0, $this);
	}

	/**
	 * Private singleton instance access.
	 *
	 * @return Textpattern_Textfilter_Set
	 */

	private static function getInstance()
	{
		if (!(self::$instance instanceof self))
		{
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Create an array map of filter keys vs. titles.
	 *
	 * @return array Map of 'key' => 'title' for all Textfilters
	 */

	static public function map()
	{
		static $out = array();
		if (empty($out))
		{
			foreach (self::getInstance() as $f)
			{
				$out[$f->getKey()] = $f->title;
			}
		}
		return $out;
	}

	/**
	 * Filter raw input text by calling one of our known Textfilters by its key.
	 *
	 * Invokes the 'textfilter'.'filter' pre- and post-callbacks.
	 *
	 * @param  string $key     The Textfilter's key
	 * @param  string $thing   Raw input text
	 * @param  array  $context Filter context ('options' => array, 'field' => string, 'data' => mixed)
	 * @return string Filtered output text
	 */

	static public function filter($key, $thing, $context)
	{
		// Preprocessing, anyone?
		callback_event_ref('textfilter', 'filter', 0, $thing, $context);

		$me = self::getInstance();
		if (isset($me[$key]))
		{
			$thing = $me[$key]->filter($thing, $context['options']);
		}
		else
		{
			// TODO: unknown filter - shall we throw an admin error?
		}

		// Postprocessing, anyone?
		callback_event_ref('textfilter', 'filter', 1, $thing, $context);

		return $thing;
	}

	/**
	 * Get help text for a certain Textfilter.
	 *
	 * @param  string $key The Textfilter's key
	 * @return string HTML for human-readable help
	 */

	static public function help($key)
	{
		$me = self::getInstance();
		if (isset($me[$key]))
		{
			return $me[$key]->help();
		}
		return '';
	}

	/**
	 * ArrayAccess interface to our set of filters.
	 *
	 * @param string $key
	 * @param string $filter
	 * @see   ArrayAccess
	 */

	public function offsetSet($key, $filter)
	{
		if (null === $key)
		{
			$key = $filter->getKey();
		}
		$this->filters[$key] = $filter;
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @param  string $key
	 * @return string The value
	 * @see    ArrayAccess
	 */

	public function offsetGet($key)
	{
		if ($this->offsetExists($key))
		{
			return $this->filters[$key];
		}
		return null;
	}

	/**
	 * Whether an offset exists.
	 *
	 * @param  string $key
	 * @return bool
	 * @see    ArrayAccess
	 */

	public function offsetExists($key)
	{
		return isset($this->filters[$key]);
	}

	/**
	 * Offset to unset.
	 *
	 * @param string $key
	 * @see   ArrayAccess
	 */

	public function offsetUnset($key)
	{
		unset($this->filters[$key]);
	}

	/**
	 * IteratorAggregate interface.
	 *
	 * @return ArrayIterator
	 * @see    IteratorAggregate
	 */

	public function getIterator()
	{
		return new ArrayIterator($this->filters);
	}
}

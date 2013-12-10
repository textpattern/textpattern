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
 * Container.
 *
 * @since   4.6.0
 * @package Container
 */

class Textpattern_Container_Container implements Textpattern_Container_ContainerInterface
{
	/**
	 * Stores registered classes.
	 *
	 * @var array
	 */

	protected $registered = array();

	/**
	 * Stores shared instances.
	 *
	 * @var array
	 */

	protected $instances = array();	

	/**
	 * {@inheritdoc}
	 */

	public function register($alias, $class)
	{
		if (isset($this->registered[$alias]))
		{
			throw new Exception('alias_is_taken');
		}

		$this->registered[$alias] = $class;
		return $this;
	}

	/**
	 * {@inheritdoc}
	 */

	public function remove($alias)
	{
		unset($this->registered[$alias], $this->instances[$alias]);
		return $this;
	}

	/**
	 * Resolves an alias to the actual classname.
	 *
	 * @param  string $alias The alias
	 * @return string The classname
	 */

	protected function resolveAlias($alias)
	{
		if (isset($this->registered[$alias]))
		{
			return $this->registered[$alias];
		}

		$colon = strpos($alias, ':');

		if ($colon === false && strpos($alias, '_') === false && strpos($alias, '\\') === false)
		{
			return 'Textpattern' . preg_replace('/([A-Z])/', '_$1', $alias);
		}

		if ($colon === 0)
		{
			$alias = substr($alias, 1);
		}

		return $alias;
	}

	/**
	 * {@inheritdoc}
	 */

	public function getInstance($alias, array $options)
	{
		if (isset($this->instances[$alias]))
		{
			$instance = $this->instances[$alias];
		}
		else
		{
			$class = $this->resolveAlias($alias);

			if ($options && method_exists($class, '__construct'))
			{
				$reflection = new ReflectionClass($class);
				$instance = $reflection->newInstanceArgs($options);
			}
			else
			{
				$instance = new $class;
			}

			if ($instance instanceof Textpattern_Container_ReusableInterface)
			{
				$this->instances[$alias] = $instance;
			}
		}

		if ($instance instanceof Textpattern_Container_FactorableInterface)
		{
			$instance = call_user_func_array(array($instance, 'getInstance'), $options);
		}

		return $instance;
	}
}

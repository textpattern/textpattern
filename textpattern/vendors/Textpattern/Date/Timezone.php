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
 * Handles timezones.
 *
 * This method extracts information from PHP's Timezone DB,
 * and allows configuring server's timezone information.
 *
 * @package Date
 * @since   4.6.0
 */

class Textpattern_Date_Timezone
{
	/**
	 * Stores a list of details about each timezone.
	 *
	 * @var array 
	 */

	protected $details;

	/**
	 * Stores a list of timezone offsets.
	 *
	 * @var array 
	 */

	protected $offsets;

	/**
	 * An array of accepted continents.
	 *
	 * @var array
	 */

	protected $continents = array(
		'Africa',
		'America',
		'Antarctica',
		'Arctic',
		'Asia',
		'Atlantic',
		'Australia',
		'Europe',
		'Indian',
		'Pacific',
	);

	/**
	 * Gets an array of safe timezones supported on this server.
	 *
	 * The following:
	 *
	 * <code>
	 * print_r(Txp::get('DateTimezone')->getTimeZones());
	 * </code>
	 *
	 * Returns:
	 *
	 * <code>
	 * Array
	 * (
	 * 	[America/New_York] => Array
	 * 	(
	 * 		[continent] => America
	 * 		[city] => New_York
	 * 		[subcity] => 
	 * 		[offset] => -18000
	 * 		[dst] => 1
	 * 	)
	 * 	[Europe/London] => Array
	 * 	(
	 * 		[continent] => Europe
	 * 		[city] => London
	 * 		[subcity] => 
	 * 		[offset] => 0
	 * 		[dst] => 1
	 * 	)
	 * )
	 * </code>
	 *
	 * Offset is the timezone offset from UTC excluding
	 * daylight saving time, DST is whether it's currently DST
	 * in the timezone. Identifiers are sorted alphabetically.
	 *
	 * @return array|bool An array of timezones, or FALSE on failure
	 */

	public function getTimeZones()
	{
		if ($this->details === null)
		{
			$this->details = array();

			if (($timezones = DateTimeZone::listIdentifiers()) === false)
			{
				return false;
			}

			foreach ($timezones as $timezone)
			{
				$parts = explode('/', $timezone);

				if (in_array($parts[0], $this->continents, true) && $data = $this->getIdentifier($timezone))
				{
					$this->details[$timezone] = $data;
					$this->offsets[$data['offset']] = $timezone;
				}
			}

			ksort($this->details);
		}

		return $this->details;
	}

	/**
	 * Find a timezone identifier for the given timezone offset.
	 *
	 * More than one key might fit any given offset,
	 * thus the returned value is ambiguous and merely useful for
	 * presentation purposes.
	 *
	 * @param  int         $offset
	 * @return string|bool Timezone identifier, or FALSE
	 */

	public function getOffsetIdentifier($offset)
	{
		if ($this->getTimeZones() && isset($this->offsets[$offset]))
		{
			return $this->offsets[$offset];
		}

		return false;
	}

	/**
	 * Whether DST is in effect.
	 *
	 * The given timestamp can either be a date format
	 * supported by DateTime, UNIX timestamp or NULL
	 * to check current status.
	 *
	 * If timezone is NULL, checks the server default
	 * timezone.
	 *
	 * <code>
	 * echo Txp::get('DateTimezone')->isDst('2013/06/20', 'Europe/London');
	 * </code>
	 *
	 * Returns TRUE, while this returns FALSE as the timezone does not
	 * use daylight saving time:
	 *
	 * <code>
	 * echo Txp::get('DateTimezone')->isDst('2013/06/20', 'Africa/Accra');
	 * </code>
	 *
	 * If it's winter this returns FALSE:
	 *
	 * <code>
	 * echo Txp::get('DateTimezone')->isDst(null, 'Europe/London');
	 * </code>
	 *
	 * @param  string|int|null $timestamp Time to check
	 * @param  string|null     $timezone  Timezone identifier
	 * @return bool            TRUE if timezone is using DST
	 */

	public function isDst($timestamp = null, $timezone = null)
	{
		if (!$timezone)
		{
			$timezone = $this->getTimeZone();
		}

		try
		{
			$timezone = new DateTimeZone($timezone);

			if ($timestamp !== null)
			{
				if ((string) intval($timestamp) !== (string) $timestamp)
				{
					$timestamp = strtotime($timestamp);
				}

				$timestamp = date('Y-m-d H:m:s', $timestamp);
			}

			$dateTime = new DateTime($timestamp, $timezone);
			return (bool) $dateTime->format('I');
		}
		catch (Exception $e)
		{
		}

		return false;
	}

	/**
	 * Gets the next day light saving transition period for the given timezone.
	 *
	 * Returns FALSE if the timezone does not use DST, or will in the future
	 * drop DST.
	 *
	 * <code>
	 * print_r(Txp::get('DateTimezone')->getDstPeriod('Europe/Helsinki'));
	 * </code>
	 *
	 * Returns:
	 *
	 * <code>
	 * Array
	 * (
	 * 	[0] => Array
	 * 	(
	 * 		[ts] => 1396141200
	 * 		[time] => 2014-03-30T01:00:00+0000
	 *		[offset] => 10800
	 * 		[isdst] => 1
	 * 		[abbr] => EEST
	 * 	)
	 * 	[1] => Array
	 * 	(
	 * 		[ts] => 1414285200
	 * 		[time] => 2014-10-26T01:00:00+0000
	 * 		[offset] => 7200
	 * 		[isdst] => 
	 * 		[abbr] => EET
	 * 	)
	 * )
	 * </code>
	 *
	 * @param  string|null $timezone The timezone identifier
	 * @return array|bool  An array of next two transitions, or FALSE 
	 * @throws Exception
	 */

	public function getDstPeriod($timezone = null)
	{
		if (!$timezone)
		{
			$timezone = $this->getTimeZone();
		}

		$timezone = new DateTimeZone($timezone);
		$time = time();
		$transitions = $timezone->getTransitions();
		$start = null;
		$end = null;

		foreach ($transitions as $transition)
		{
			if ($start !== null)
			{
				$end = $transition;
				break;
			}

			if ($transition['ts'] >= $time && $transition['isdst'])
			{
				$start = $transition;
			}
		}

		if ($start)
		{
			return array($start, $end);
		}

		return false;
	}

	/**
	 * Gets a timezone identifier.
	 *
	 * Extracts information about the given timezone. If the $timezone
	 * is NULL, uses the server's default timezone.
	 *
	 * <code>
	 * print_r(Txp::get('DateTimezone')->getIdentifier('Europe/London'));
	 * </code>
	 *
	 * Returns:
	 *
	 * <code>
	 * Array
	 * (
	 * 	[continent] => Europe
	 * 	[city] => London
	 * 	[subcity] => 
	 * 	[offset] => 0
	 * 	[dst] => 1
	 * )
	 * </code>
	 *
	 * @return array|bool
	 */

	public function getIdentifier($timezone = null)
	{
		if ($timezone === null)
		{
			$timezone = $this->getTimeZone();
		}

		if (isset($this->details[$timezone]))
		{
			return $this->details[$timezone];
		}

		try
		{
			$dateTime = new DateTime('now', new DateTimeZone($timezone));

			$data = array(
				'continent' => '',
				'city'      => '',
				'subcity'   => '',
				'offset'    => $dateTime->getOffset(),
				'dst'       => false,
			);

			if (strpos($timezone, '/') !== false)
			{
				$parts = array_pad(explode('/', $timezone), 3, '');
				$data['continent'] = $parts[0];
				$data['city'] = $parts[1];
				$data['subcity'] = $parts[2];
			}

			if ($dateTime->format('I'))
			{
				$data['offset'] -= 3600;
				$data['dst'] = true;
			}

			return $data;
		}
		catch (Exception $e)
		{
		}
	}

	/**
	 * Sets the server default timezone.
	 *
	 * @param  string $identifier The timezone identifier
	 * @return Textpattern_Date_Timezone
	 */

	public function setTimeZone($identifier)
	{
		@date_default_timezone_set($identifier);
		return $this;
	}

	/**
	 * Gets the server default timezone.
	 *
	 * @return string|bool Timezone identifier
	 */

	public function getTimeZone()
	{
		return @date_default_timezone_get();
	}
}

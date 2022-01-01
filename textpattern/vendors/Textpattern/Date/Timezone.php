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
 * Handles timezones.
 *
 * This method extracts information from PHP's Timezone DB, and allows
 * configuring server's timezone information.
 *
 * @package Date
 * @since   4.6.0
 */

namespace Textpattern\Date;

class Timezone
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
     * print_r(Txp::get('\Textpattern\Date\Timezone')->getTimeZones());
     * </code>
     *
     * Returns:
     *
     * <code>
     * Array
     * (
     *     [America/New_York] => Array
     *     (
     *         [continent] => America
     *         [city] => New_York
     *         [subcity] =>
     *         [offset] => -18000
     *         [dst] => 1
     *     )
     *     [Europe/London] => Array
     *     (
     *         [continent] => Europe
     *         [city] => London
     *         [subcity] =>
     *         [offset] => 0
     *         [dst] => 1
     *     )
     * )
     * </code>
     *
     * Offset is the timezone offset from UTC excluding daylight saving time,
     * DST is whether it's currently DST in the timezone. Identifiers are
     * sorted alphabetically.
     *
     * @return array|bool An array of timezones, or FALSE on failure
     */

    public function getTimeZones()
    {
        if ($this->details === null) {
            $this->details = array();

            if (($timezones = \DateTimeZone::listIdentifiers()) === false) {
                return false;
            }

            foreach ($timezones as $timezone) {
                $parts = explode('/', $timezone);

                if ((count($parts) == 1 || in_array($parts[0], $this->continents, true)) && $data = $this->getIdentifier($timezone)) {
                    $this->details[$timezone] = $data;

                    if (!isset($this->offsets[$data['offset']])) {
                        $this->offsets[$data['offset']] = array();
                    }

                    $this->offsets[$data['offset']][] = $timezone;
                }
            }

            ksort($this->details);
        }

        return $this->details;
    }

    /**
     * Gets timezone identifiers for the given timezone offset.
     *
     * More than one timezone might fit any given offset, thus the returned
     * value is ambiguous and merely useful for presentation purposes.
     *
     * <code>
     * print_r(Txp::get('\Textpattern\Date\Timezone')->getOffsetIdentifiers(3600));
     * </code>
     *
     * Returns:
     *
     * <code>
     * Array
     * (
     *     [0] => Africa/Malabo
     *     [1] => Europe/Amsterdam
     *     [2] => Europe/Berlin
     *     [3] => Europe/Zurich
     * )
     * </code>
     *
     * @param  int $offset Offset in seconds
     * @return array|bool An array of timezone identifiers, or FALSE
     */

    public function getOffsetIdentifiers($offset)
    {
        if ($this->getTimeZones() && isset($this->offsets[$offset])) {
            return $this->offsets[$offset];
        }

        return false;
    }

    /**
     * Whether DST is in effect.
     *
     * The given timestamp can either be a date format supported by DateTime,
     * UNIX timestamp or NULL to check current status.
     *
     * If timezone is NULL, checks the server default timezone.
     *
     * <code>
     * echo Txp::get('\Textpattern\Date\Timezone')->isDst('2013/06/20', 'Europe/London');
     * </code>
     *
     * Returns TRUE, while this returns FALSE as the timezone does not use
     * daylight saving time:
     *
     * <code>
     * echo Txp::get('\Textpattern\Date\Timezone')->isDst('2013/06/20', 'Africa/Accra');
     * </code>
     *
     * If it's winter this returns FALSE:
     *
     * <code>
     * echo Txp::get('\Textpattern\Date\Timezone')->isDst(null, 'Europe/London');
     * </code>
     *
     * @param  string|int|null $timestamp Time to check
     * @param  string|null     $timezone  Timezone identifier
     * @return bool TRUE if timezone is using DST
     */

    public function isDst($timestamp = null, $timezone = null)
    {
        static $DTZones = array();

        if (!$timezone) {
            $timezone = $this->getTimeZone();
        }

        if ($timestamp === null) {
            $timestamp = time();
        } else {
            if ((string)intval($timestamp) !== (string)$timestamp) {
                $timestamp = strtotime($timestamp);
            }
        }

        try {
            if (!isset($DTZones[$timezone])) {
                $DTZones[$timezone] = new \DateTimeZone($timezone);
            }
            $transition = $DTZones[$timezone]->getTransitions($timestamp, $timestamp);
            $isdst = $transition[0]['isdst'];
        } catch (\Exception $e) {
            $isdst = false;
        }

        return (bool)$isdst;
    }

    /**
     * Gets the next daylight saving transition period for the given timezone.
     *
     * Returns FALSE if the timezone does not use DST, or will in the future
     * drop DST.
     *
     * <code>
     * print_r(Txp::get('\Textpattern\Date\Timezone')->getDstPeriod('Europe/Helsinki'));
     * </code>
     *
     * Returns:
     *
     * <code>
     * Array
     * (
     *     [0] => Array
     *     (
     *         [ts] => 1396141200
     *         [time] => 2014-03-30T01:00:00+0000
     *         [offset] => 10800
     *         [isdst] => 1
     *         [abbr] => EEST
     *     )
     *     [1] => Array
     *     (
     *         [ts] => 1414285200
     *         [time] => 2014-10-26T01:00:00+0000
     *         [offset] => 7200
     *         [isdst] =>
     *         [abbr] => EET
     *     )
     * )
     * </code>
     *
     * @param  string|null $timezone The timezone identifier
     * @param  int         $from     Next transitions starting from when
     * @return array|bool An array of next two transitions, or FALSE
     * @throws \Exception
     */

    public function getDstPeriod($timezone = null, $from = null)
    {
        if (!$timezone) {
            $timezone = $this->getTimeZone();
        }

        $timezone = new \DateTimeZone($timezone);

        if ($from === null) {
            $from = time();
        }

        $transitions = $timezone->getTransitions();
        $start = null;
        $end = null;

        foreach ($transitions as $transition) {
            if ($start !== null) {
                $end = $transition;
                break;
            }

            if ($transition['ts'] >= $from && $transition['isdst']) {
                $start = $transition;
            }
        }

        if ($start) {
            return array($start, $end);
        }

        return false;
    }

    /**
     * Gets timezone abbreviation.
     *
     * If the $timezone is NULL, uses the server default. Returns FALSE if
     * there is no abbreviation to give.
     *
     * <code>
     * echo Txp::get('\Textpattern\Date\Timezone')->getTimeZoneAbbreviation('Europe/London');
     * </code>
     *
     * Returns 'GMT', while the following returns 'FALSE':
     *
     * <code>
     * echo Txp::get('\Textpattern\Date\Timezone')->getTimeZoneAbbreviation('Africa/Accra', true);
     * </code>
     *
     * As according to the timezone database, the timezone does not currently
     * use DST.
     *
     * @param  string $timezone Timezone identifier
     * @param  bool   $dst      TRUE to get the abbreviation during DST
     * @return string|bool The abbreviation, or FALSE on failure
     */

    public function getTimeZoneAbbreviation($timezone = null, $dst = false)
    {
        try {
            if ($timezone === null) {
                $timezone = $this->getTimeZone();
            }

            $timezone = new \DateTimeZone($timezone);
            $time = time();

            if ($transitions = $timezone->getTransitions()) {
                $latest = end($transitions);

                if ($latest['ts'] <= $time) {
                    $latest['ts'] = $time;
                    $transitions = array($latest);
                }

                foreach ($transitions as $transition) {
                    if ($time <= $transition['ts']) {
                        if ($dst === true && $transition['isdst']) {
                            return $transition['abbr'];
                        }

                        if ($dst === false && !$transition['isdst']) {
                            return $transition['abbr'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * Gets a timezone identifier.
     *
     * Extracts information about the given timezone. If the $timezone is NULL,
     * uses the server's default timezone.
     *
     * <code>
     * print_r(Txp::get('\Textpattern\Date\Timezone')->getIdentifier('Europe/London'));
     * </code>
     *
     * Returns:
     *
     * <code>
     * Array
     * (
     *     [continent] => Europe
     *     [city] => London
     *     [subcity] =>
     *     [offset] => 0
     *     [dst] => 1
     * )
     * </code>
     *
     * @param  string|null $timezone Timezone identifier
     * @return array|bool An array, or FALSE on failure
     */

    public function getIdentifier($timezone = null)
    {
        if ($timezone === null) {
            $timezone = $this->getTimeZone();
        }

        if (isset($this->details[$timezone])) {
            return $this->details[$timezone];
        }

        try {
            $dateTime = new \DateTime('now', new \DateTimeZone($timezone));

            $data = array(
                'continent' => '',
                'city'      => '',
                'subcity'   => '',
                'offset'    => $dateTime->getOffset(),
                'dst'       => false,
            );

            if (strpos($timezone, '/') !== false) {
                $parts = array_pad(explode('/', $timezone), 3, '');
                $data['continent'] = $parts[0];
                $data['city'] = $parts[1];
                $data['subcity'] = $parts[2];
            }

            if ($dateTime->format('I')) {
                $data['offset'] -= 3600;
                $data['dst'] = true;
            }

            return $data;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sets the server default timezone.
     *
     * If an array of identifiers is given, the first one supported is used.
     *
     * <code>
     * echo Txp::get('\Textpattern\Date\Timezone')->setTimeZone('UTC');
     * </code>
     *
     * Throws an exception if the identifier isn't valid.
     *
     * @param  array|string $identifiers The timezone identifier
     * @return Timezone
     * @throws \Exception
     */

    public function setTimeZone($identifiers)
    {
        foreach ((array)$identifiers as $identifier) {
            if (@date_default_timezone_set($identifier)) {
                return $this;
            }
        }

        throw new \Exception(gTxt('invalid_argument', array('{name}' => 'identifiers')));
    }

    /**
     * Gets the server default timezone.
     *
     * <code>
     * echo Txp::get('\Textpattern\Date\Timezone')->setTimeZone('Europe/London')->getTimeZone();
     * </code>
     *
     * The above returns 'Europe/London'.
     *
     * @return string|bool Timezone identifier
     */

    public function getTimeZone()
    {
        return @date_default_timezone_get();
    }
}

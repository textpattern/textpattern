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
 * Debugs memory usage.
 *
 * <code>
 * Txp::get('Textpattern_Debug_Memory')->logPeakUsage();
 * </code>
 *
 * @since   4.6.0
 * @package Debug
 */

class Textpattern_Debug_Memory implements Textpattern_Container_ReusableInterface
{
    /**
     * Top memory usage.
     *
     * @var int
     */

    private $memoryTop = 0;

    /**
     * Last logged message.
     *
     * @var string
     */

    private $memoryMessage = '';

    /**
     * Logs the current memory usage.
     *
     * This method updates the logged peak memory usage if
     * the current usage is greater than the last logged value.
     *
     * <code>
     * Txp::get('Textpattern_Debug_Memory')->logPeakUsage();
     * </code>
     *
     * @param  string $message The message to log with memory usage
     * @return Textpattern_Debug_Memory
     * @throws Exception
     */

    public function logPeakUsage($message = null)
    {
        if (!is_callable('memory_get_usage')) {
            throw new Exception('disabled_function', array('{name}' => 'memory_get_usage'));
        }

        $memory = memory_get_usage();

        if ($memory > $this->memoryTop) {
            $this->memoryTop = $memory;
            $this->memoryMessage = (string) $message;
        }

        return $this;
    }

    /**
     * Gets the logged message.
     *
     * <code>
     * echo Txp::get('Textpattern_Debug_Memory')->getLoggedMessage();
     * </code>
     *
     * @return string
     */

    public function getLoggedMessage()
    {
        return $this->memoryMessage;
    }

    /**
     * Gets the logged peak memory usage.
     *
     * <code>
     * echo Txp::get('Textpattern_Debug_Memory')->getLoggedUsage();
     * </code>
     *
     * @return int
     */

    public function getLoggedUsage()
    {
        return $this->memoryTop;
    }
}

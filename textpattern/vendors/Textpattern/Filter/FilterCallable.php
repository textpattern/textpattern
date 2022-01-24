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

namespace Textpattern\Filter;

/**
 * Callable filter.
 *
 * <code>
 * try {
 *     Txp::get('\Textpattern\Filter\FilterCallable', 'fn');
 * } catch (Textpattern\Filter\Exception $e) {
 *     echo $e->getMessage();
 * }
 * </code>
 *
 * @since   4.6.0
 * @package Filter
 */

class FilterCallable extends \Textpattern\Type\TypeCallable
{
    /**
     * {@inheritdoc}
     */

    public function __construct($callable)
    {
        if (!is_callable($callable)) {
            throw new \Textpattern\Filter\Exception(gTxt('assert_callable'));
        }

        parent::__construct($callable);
    }
}

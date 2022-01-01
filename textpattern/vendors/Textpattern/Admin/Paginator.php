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
 * List pagination.
 *
 * @since   4.7.0
 * @package Admin\Paginator
 */

namespace Textpattern\Admin;

class Paginator
{
    /**
     * Textpattern event (panel) to which this paginator applies.
     *
     * @var string
     */

    protected $event = null;

    /**
     * Identifier for the pref that holds the current value.
     *
     * @var string
     */

    protected $prefKey = null;

    /**
     * Allowable set of items per page.
     *
     * @var array
     */

    protected $sizes = array(12, 24, 48, 96);

    /**
     * Default pagination value.
     *
     * Usually the first value in the $sizes array.
     *
     * @var int
     */

    protected $defaultVal = null;

    /**
     * Constructor.
     *
     * The available sizes can be changed via a '{$this->event}_ui > pageby_values'
     * callback event.
     *
     * @param string $evt    Textpattern event (panel)
     * @param string $prefix Prefix for the pref that holds the current paging value
     */

    public function __construct($evt = null, $prefix = null)
    {
        global $event;

        if ($evt === null) {
            $evt = $event;
        }

        if ($prefix === null) {
            $prefix = $evt;
        }

        $this->event = $evt;
        $this->prefKey = $prefix.'_list_pageby';
        $this->defaultVal = $this->sizes[0];

        callback_event_ref($evt.'_ui', 'pageby_values', 0, $this->sizes);
    }

    /**
     * Renders a widget to select various amounts to page lists by.
     *
     * @param  int $val Current pagination setting
     * @return string      HTML
     */

    public function render($val = null)
    {
        $step = $this->event.'_change_pageby';

        if (empty($this->sizes)) {
            return;
        }

        $val = $this->closest($val);

        $out = array();

        foreach ($this->sizes as $qty) {
            if ($qty == $val) {
                $class = 'navlink-active';
                $aria_pressed = 'true';
            } else {
                $class = 'navlink';
                $aria_pressed = 'false';
            }

            $out[] = href($qty, array(
                'event'      => $this->event,
                'step'       => $step,
                'qty'        => $qty,
                '_txp_token' => form_token(),
            ), array(
                'class'        => $class,
                'title'        => gTxt('view_per_page', array('{page}' => $qty)),
                'aria-label'   => gTxt('view_per_page', array('{page}' => $qty)),
                'aria-pressed' => $aria_pressed,
                'role'         => 'button',
            ));
        }

        return n.tag(join('', $out), 'div', array('class' => 'nav-tertiary pageby'));
    }


    /**
     * Fetch the current paging limit, or lowest default.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->closest();
    }

    /**
     * Updates a list's per page number.
     *
     * Gets the per page number from a "qty" HTTP POST/GET parameter and
     * creates a user-specific preference value suffixed "_list_pageby".
     *
     * The assignment to $GLOBALS is for legacy plugins and can be
     * removed in future.
     */

    public function change()
    {
        global $prefs;

        $qty = intval(gps('qty'));
        $GLOBALS[$this->prefKey] = $prefs[$this->prefKey] = $qty;

        set_pref($this->prefKey, $qty, $this->event, PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
    }

    /**
     * Find closest value to $val from $sizes.
     *
     * @param  int $val Value to compare
     * @return int      Closest supported value
     */
    protected function closest($val = null)
    {
        if (empty($val)) {
            $val = get_pref($this->prefKey, $this->defaultVal);
        }

        if (!in_array($val, $this->sizes)) {
            $closest = null;

            foreach ($this->sizes as $item) {
                if ($closest === null || abs($val - $closest) > abs($item - $val)) {
                    $closest = $item;
                }
            }

            $val = $closest;
        }

        return $val;
    }
}

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
 * An anchor tag for creating admin-side deletion URL links.
 *
 * Replaces dLink().
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class AdminDelete extends AdminAnchor implements UIInterface
{
    /**
     * Construct content and anchor for deleting admin-side content.
     *
     * @param string  $event    Textpattern panel (event)
     * @param string  $step     Textpattern action (step)
     * @param string  $type     Whether the link is an anchor (get) or a form (post)
     */

    public function __construct($event, $step, $type = 'get')
    {
        $atts = new \Textpattern\UI\Attribute();

        switch ($type) {
            case 'post':
                $linktext = new \Textpattern\UI\Span(gTxt('delete'));
                $linktext->setAtt('class', 'ui-icon ui-icon-close');

                $out = new \Textpattern\UI\Tag('button');
                $out
                    ->setContent($linktext)
                    ->setAtts(array(
                        'class'      => 'destroy',
                        'type'       => 'submit',
                        'title'      => gTxt('delete'),
                        'aria-label' => gTxt('delete'),
                    ));
                break;
            case 'get':
                $out = gTxt('delete');
                $atts
                    ->setAttribute('class', 'destroy ui-icon ui-icon-close')
                    ->setAttribute('title', gTxt('delete'));
            default:
                break;
        }

        parent::__construct($event, $step, $out, $type);

        $this->setProperty('verify', 'confirm_delete_popup')
            ->setProperty('token', true);
    }
}

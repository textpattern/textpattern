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
 * Textile filter.
 *
 * @since   4.6.0
 * @package Textfilter
 */

class Textpattern_Textfilter_Textile extends Textpattern_Textfilter_Base implements Textpattern_Textfilter_Interface
{
    /**
     * Instance of Textile.
     *
     * @var Textile
     */

    protected $textile;

    /**
     * Constructor.
     */

    public function __construct()
    {
        parent::__construct(USE_TEXTILE, gTxt('use_textile'));
        $this->textile = new Textpattern_Textile_Parser();
        $this->version = $this->textile->getVersion();
    }

    /**
     * Filter.
     *
     * @param string $thing
     * @param array  $options
     */

    public function filter($thing, $options)
    {
        parent::filter($thing, $options);

        if (($this->options['restricted'])) {
            return $this->textile->TextileRestricted(
                $thing,
                $this->options['lite'],
                $this->options['noimage'],
                $this->options['rel']
            );
        } else {
            return $this->textile->TextileThis(
                $thing,
                $this->options['lite'],
                '',
                $this->options['noimage'],
                '',
                $this->options['rel']
            );
        }
    }

    /**
     * Help for Textile syntax.
     *
     * Gives some basic Textile syntax examples,
     * wrapped in an &lt;ul&gt;.
     *
     * @return string HTML
     */

    public function getHelp()
    {
        return
            n.'<ul class="textile plain-list">'.
            n.'<li>'.gTxt('header').': <strong>h<em>n</em>.</strong>'.
            popHelpSubtle('header', 400, 400).'</li>'.
            n.'<li>'.gTxt('blockquote').': <strong>bq.</strong>'.
            popHelpSubtle('blockquote', 400, 400).'</li>'.
            n.'<li>'.gTxt('numeric_list').': <strong>#</strong>'.
            popHelpSubtle('numeric', 400, 400).'</li>'.
            n.'<li>'.gTxt('bulleted_list').': <strong>*</strong>'.
            popHelpSubtle('bulleted', 400, 400).'</li>'.
            n.'<li>'.gTxt('definition_list').': <strong>; :</strong>'.
            popHelpSubtle('definition', 400, 400).'</li>'.
            n.'</ul>'.

            n.'<ul class="textile plain-list">'.
            n.'<li>'.'_<em>'.gTxt('emphasis').'</em>_'.
            popHelpSubtle('italic', 400, 400).'</li>'.
            n.'<li>'.'*<strong>'.gTxt('strong').'</strong>*'.
            popHelpSubtle('bold', 400, 400).'</li>'.
            n.'<li>'.'??<cite>'.gTxt('citation').'</cite>??'.
            popHelpSubtle('cite', 500, 300).'</li>'.
            n.'<li>'.'-'.gTxt('deleted_text').'-'.
            popHelpSubtle('delete', 400, 300).'</li>'.
            n.'<li>'.'+'.gTxt('inserted_text').'+'.
            popHelpSubtle('insert', 400, 300).'</li>'.
            n.'<li>'.'^'.gTxt('superscript').'^'.
            popHelpSubtle('super', 400, 300).'</li>'.
            n.'<li>'.'~'.gTxt('subscript').'~'.
            popHelpSubtle('subscript', 400, 400).'</li>'.
            n.'</ul>'.

            graf(
                '"'.gTxt('linktext').'":url'.popHelpSubtle('link', 400, 500),
                ' class="textile"'
            ).

            graf(
                '!'.gTxt('imageurl').'!'.popHelpSubtle('image', 500, 500),
                ' class="textile"'
            ).

            graf(
                href(gTxt('More'), 'http://textpattern.com/textile-sandbox', ' id="textile-docs-link" rel="external" target="_blank"')
            );
    }
}

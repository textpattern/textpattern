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
 * Iterates over the given Textpack strings.
 *
 * <code>
 * foreach (Txp::get('\Textpattern\Textpack\String\Iterator', 'en-gb.textpack') as $name => $string) {
 *     echo "{$name} is translated to: {$string} in " . $string->getLanguage();
 * }
 * </code>
 *
 * @since   4.6.0
 * @package Textpack
 */

namespace Textpattern\Textpack\String;

class Iterator extends \Textpattern\Iterator\FileIterator implements \Textpattern\Textpack\StringInterface
{
    /**
     * Stores Textpack parser instance.
     *
     * @var \Textpattern\Textpack\Parser
     */

    protected $parser;

    /**
     * {@inheritdoc}
     */

    public function __construct($filename)
    {
        $this->parser = \Txp::get('\Textpattern\Textpack\Parser');
        parent::__construct($filename);
    }

    /**
     * Returns the translation string.
     *
     * @return string
     */

    public function __toString()
    {
        return (string)$this->getString();
    }

    /**
     * Returns the current element.
     *
     * @return Iterator
     */

    public function current()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function getName()
    {
        return $this->current['name'];
    }

    /**
     * {@inheritdoc}
     */

    public function getLanguage()
    {
        return $this->current['lang'];
    }

    /**
     * {@inheritdoc}
     */

    public function getString()
    {
        return $this->current['data'];
    }

    /**
     * {@inheritdoc}
     */

    public function getEvent()
    {
        return $this->current['event'];
    }

    /**
     * {@inheritdoc}
     */

    public function getOwner()
    {
        return $this->current['owner'];
    }

    /**
     * {@inheritdoc}
     */

    public function getVersion()
    {
        return $this->current['version'];
    }

    /**
     * {@inheritdoc}
     */

    public function getLastmod()
    {
        return $this->current['lastmod'];
    }

    /**
     * {@inheritdoc}
     */

    public function next()
    {
        $buffer = '';

        while (1) {
            parent::next();

            if ($this->valid() === false) {
                return;
            }

            $buffer .= $this->current."\n";

            if ($string = $this->parser->parse($buffer)) {
                $this->current = $string[0];
                $this->key = $this->current['name'];

                return;
            }
        }
    }
}

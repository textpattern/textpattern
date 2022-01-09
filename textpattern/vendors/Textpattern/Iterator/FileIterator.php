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
 * File iterator.
 *
 * <code>
 * $file = new \Textpattern\Iterator\FileIterator('file.txt');
 * foreach ($file as $key => $line) {
 *     echo $line;
 * }
 * </code>
 *
 * @since   4.6.0
 * @package Iterator
 */

namespace Textpattern\Iterator;

class FileIterator implements \Iterator
{
    /**
     * Filename.
     *
     * @var string
     */

    protected $filename;

    /**
     * Line length.
     *
     * @var int
     */

    protected $lineLength = 4096;

    /**
     * Filepointer.
     *
     * @var resource
     */

    protected $filepointer;

    /**
     * The current element.
     */

    protected $current;

    /**
     * The current index.
     *
     * @var int
     */

    protected $key = -1;

    /**
     * Whether it's valid or not.
     *
     * @var bool
     */

    protected $valid;

    /**
     * Constructor.
     *
     * @param string $filename The filename
     */

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->rewind();
    }

    /**
     * Destructor.
     */

    public function __destruct()
    {
        if (is_resource($this->filepointer)) {
            fclose($this->filepointer);
        }
    }

    /**
     * Returns the current element.
     *
     * @return \Textpattern\Type\StringType
     */

    public function current()
    {
        return new \Textpattern\Type\StringType($this->current);
    }

    /**
     * {@inheritdoc}
     */

    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */

    public function next()
    {
        if (!feof($this->filepointer)) {
            $this->current = fgets($this->filepointer, $this->lineLength);
            $this->key++;
            $this->valid = true;
        } else {
            $this->valid = false;
            fclose($this->filepointer);
        }
    }

    /**
     * {@inheritdoc}
     */

    public function rewind()
    {
        if (is_resource($this->filepointer) === false) {
            if (($this->filepointer = fopen($this->filename, 'r')) === false) {
                throw new \Exception(gTxt('invalid_argument', array('{name}' => 'filename')));
            }
        }

        rewind($this->filepointer);
        $this->key = -1;
        $this->next();
    }

    /**
     * {@inheritdoc}
     */

    public function valid()
    {
        return $this->valid;
    }
}

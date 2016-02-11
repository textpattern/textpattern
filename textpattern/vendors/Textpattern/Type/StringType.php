<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2016 The Textpattern Development Team
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
 * StringType object.
 *
 * Wraps around Multibyte string extension, offering multi-byte safe
 * string functions.
 *
 * <code>
 * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!')->trim()->replace('!', '.')->lower();
 * </code>
 *
 * @since   4.6.0
 * @package Type
 */

namespace Textpattern\Type;

class StringType implements TypeInterface
{
    /**
     * The string.
     *
     * @var string
     */

    protected $string;

    /**
     * Whether multibyte string extension is available.
     *
     * @var bool
     */

    protected static $mbString = null;

    /**
     * Whether encoding functions are available.
     *
     * @var bool
     */

    protected static $encode = null;

    /**
     * Expected encoding.
     *
     * @var string
     */

    protected $encoding = 'UTF-8';

    /**
     * Constructor.
     *
     * @param string $string The string
     */

    public function __construct($string)
    {
        $this->string = (string)$string;

        if (self::$mbString === null) {
            self::$mbString = function_exists('mb_strlen');
        }

        if (self::$encode === null) {
            self::$encode = function_exists('utf8_decode');
        }
    }

    /**
     * Gets the string.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!');
     * </code>
     *
     * @return string
     * @see    \Textpattern\Type\String::getString()
     */

    public function __toString()
    {
        return (string)$this->string;
    }

    /**
     * Gets the string.
     *
     * <code>
     * echo Txp::get('\Textpattern\Type\StringType', 'Hello World!')->getString();
     * </code>
     *
     * @return string
     * @see    \Textpattern\Type\String::_toString()
     */

    public function getString()
    {
        return (string)$this->string;
    }

    /**
     * Gets string length.
     *
     * <code>
     * echo Txp::get('\Textpattern\Type\StringType', 'Hello World!')->getLength();
     * </code>
     *
     * @return int
     */

    public function getLength()
    {
        if (self::$mbString) {
            return mb_strlen($this->string, $this->encoding);
        }

        if (self::$encode) {
            return strlen(utf8_decode($this->string));
        }

        return strlen($this->string);
    }

    /**
     * Finds the first occurrence of a string in the string.
     *
     * <code>
     * echo Txp::get('\Textpattern\Type\StringType', '#@language')->position('@');
     * </code>
     *
     * @param  string $needle The string to find
     * @param  int    $offset The search offset
     * @return int|bool FALSE if the string does not contain results
     */

    public function position($needle, $offset = 0)
    {
        if (self::$mbString) {
            return mb_strpos($this->string, $needle, $offset, $this->encoding);
        }

        return strpos($this->string, $needle, $offset);
    }

    /**
     * Gets substring count.
     *
     * <code>
     * echo Txp::get('\Textpattern\Type\StringType', 'Hello World!')->count('ello');
     * </code>
     *
     * @param  string $needle The string to find
     * @return int
     */

    public function count($needle)
    {
        if (self::$mbString) {
            return mb_substr_count($this->string, $needle, $this->encoding);
        }

        return substr_count($this->string, $needle);
    }

    /**
     * Converts the string to a callback.
     *
     * <code>
     * Txp::get('\Textpattern\Type\StringType', '\Textpattern\Password\Hash->hash')->toCallback();
     * </code>
     *
     * @return mixed Callable
     */

    public function toCallback()
    {
        $callback = $this->string;

        if (strpos($this->string, '->')) {
            $callback = explode('->', $this->string);

            if (class_exists($callback[0])) {
                $callback[0] = new $callback[0];
            }
        } elseif (strpos($this->string, '::')) {
            $callback = explode('::', $this->string);
        }

        return $callback;
    }

    /**
     * Add slashes.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Some "content" to slash.')->addSlashes();
     * </code>
     *
     * @return StringType
     */

    public function addSlashes()
    {
        $this->string = addslashes($this->string);

        return $this;
    }

    /**
     * HTML encodes the string.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', '<strong>Hello World!</strong>')->html();
     * </code>
     *
     * @param  int  $flags         A bitmask of one or more flags. The default is ENT_QUOTES
     * @param  bool $double_encode When double_encode is turned off PHP will not encode existing HTML entities, the default is to convert everything
     * @return StringType
     */

    public function html($flags = ENT_QUOTES, $double_encode = true)
    {
        $this->string = htmlspecialchars($this->string, $flags, $this->encoding, $double_encode);

        return $this;
    }

    /**
     * Splits part of the string.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!')->substring(2, 5);
     * </code>
     *
     * @param  int $start  The start
     * @param  int $length The length
     * @return StringType
     */

    public function substring($start, $length = null)
    {
        if (self::$mbString) {
            $this->string = mb_substr($this->string, $start, $length, $this->encoding);
        } else {
            $this->string = substr($this->string, $start, $length);
        }

        return $this;
    }

    /**
     * Replaces all occurrences with replacements.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!')->replace('!', '.');
     * </code>
     *
     * @param  mixed $from The needle to find
     * @param  mixed $to   The replacement
     * @return StringType
     */

    public function replace($from, $to)
    {
        $this->string = str_replace($from, $to, $this->string);

        return $this;
    }

    /**
     * Translates substrings.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Article <strong>{title}</strong> deleted.')
     *     ->tr('{title}', 'Hello {title} variable.');
     * </code>
     *
     * @param  string $from StringType to find
     * @param  string $to   The replacement
     * @return StringType
     */

    public function tr($from, $to = null)
    {
        $this->string = strtr($this->string, $from, $to);

        return $this;
    }

    /**
     * Trims surrounding whitespace or other characters.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', ' Hello World! ')->trim();
     * </code>
     *
     * @param  string $characters Character list
     * @return StringType
     */

    public function trim($characters = "\t\n\r\0\x0B")
    {
        $this->string = trim($this->string, $characters);

        return $this;
    }

    /**
     * Trims whitespace or other characters from the beginning.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', ' Hello World! ')->ltrim();
     * </code>
     *
     * @param  string $characters Character list
     * @return StringType
     */

    public function ltrim($characters = "\t\n\r\0\x0B")
    {
        $this->string = ltrim($this->string, $characters);

        return $this;
    }

    /**
     * Trims whitespace or other characters from the end.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', ' Hello World! ')->rtrim();
     * </code>
     *
     * @param  string $characters Character list
     * @return StringType
     */

    public function rtrim($characters = "\t\n\r\0\x0B")
    {
        $this->string = rtrim($this->string, $characters);

        return $this;
    }

    /**
     * Splits string to chunks.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!')->chunk(1);
     * </code>
     *
     * @param  int    $length    The chunk length
     * @param  string $delimiter The delimiter
     * @return StringType
     */

    public function chunk($length = 76, $delimiter = n)
    {
        $this->string = chunk_split($this->string, $length, $delimiter);

        return $this;
    }

    /**
     * Word wraps the string.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!')->wordWrap();
     * </code>
     *
     * @param  int    $length    The line length
     * @param  string $delimiter The line delimiter
     * @param  bool   $cut       Cut off words
     * @return StringType
     */

    public function wordWrap($length = 75, $delimiter = n, $cut = false)
    {
        $this->string = wordwrap($this->string, $length, $delimiter, $cut);

        return $this;
    }

    /**
     * Converts the string to lowercase.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!')->lower();
     * </code>
     *
     * @return StringType
     */

    public function lower()
    {
        if (self::$mbString) {
            $this->string = mb_strtolower($this->string, $this->encoding);
        } else {
            $this->string = strtolower($this->string);
        }

        return $this;
    }

    /**
     * Converts the string to uppercase.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!')->upper();
     * </code>
     *
     * @return StringType
     */

    public function upper()
    {
        if (self::$mbString) {
            $this->string = mb_strtoupper($this->string, $this->encoding);
        } else {
            $this->string = strtoupper($this->string);
        }

        return $this;
    }

    /**
     * Converts the string to titlecase.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'hello world!')->title();
     * </code>
     *
     * @return StringType
     */

    public function title()
    {
        if (self::$mbString) {
            $this->string = mb_convert_case($this->string, MB_CASE_TITLE, $this->encoding);
        } else {
            $this->string = ucwords($this->string);
        }

        return $this;
    }

    /**
     * Uppercase the first letter.
     *
     * <code>
     * echo (string) Txp::get('\Textpattern\Type\StringType', 'Hello World!')->ucfirst();
     * </code>
     *
     * @return StringType
     */

    public function ucfirst()
    {
        if (self::$mbString) {
            $this->string =
                mb_strtoupper(mb_substr($this->string, 0, 1, $this->encoding), $this->encoding).
                mb_substr($this->string, 1, null, $this->encoding);
        } else {
            $this->string = ucfirst($this->string);
        }

        return $this;
    }
}

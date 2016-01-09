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
 * Collection of email related encoders.
 *
 * @since   4.6.0
 * @package Mail
 */

namespace Textpattern\Mail;

class Encode
{
    /**
     * Wished character encoding.
     *
     * @var string
     */

    protected $charset = 'UTF-8';

    /**
     * Constructor.
     */

    public function __construct()
    {
        if (get_pref('override_emailcharset') && is_callable('utf8_decode')) {
            $this->charset = 'ISO-8859-1';
        }
    }

    /**
     * Encodes an address list to a valid email header value.
     *
     * @param  array $value The address list
     * @return string
     */

    public function addressList($value)
    {
        if (!$value) {
            return '';
        }

        $out = array();

        foreach ($value as $email => $name) {
            if ($this->charset != 'UTF-8') {
                $name = utf8_decode($name);
            }

            $out[] = trim($this->header($this->escapeHeader($name), 'phrase').' <'.$this->escapeHeader($email).'>');
        }

        return join(', ', $out);
    }

    /**
     * Encodes a string for use in an email header.
     *
     * @param  string $string The string
     * @param  string $type   The type of header, either "text" or "phrase"
     * @return string
     * @throws \Textpattern\Mail\Exception
     */

    public function header($string, $type)
    {
        if (strpos($string, '=?') === false && !preg_match('/[\x00-\x1F\x7F-\xFF]/', $string)) {
            if ($type == 'phrase') {
                if (preg_match('/[][()<>@,;:".\x5C]/', $string)) {
                    $string = '"'.strtr($string, array("\\" => "\\\\", '"' => '\"')).'"';
                }
            } elseif ($type != 'text') {
                throw new \Textpattern\Mail\Exception(gTxt('invalid_argument', array('{name}' => 'type')));
            }

            return $string;
        }

        if ($this->charset == 'ISO-8859-1') {
            $start = '=?ISO-8859-1?B?';
            $pcre = '/.{1,42}/s';
        } else {
            $start = '=?UTF-8?B?';
            $pcre = '/.{1,45}(?=[\x00-\x7F\xC0-\xFF]|$)/s';
        }

        $end = '?=';
        $sep = IS_WIN ? "\r\n" : "\n";
        preg_match_all($pcre, $string, $matches);

        return $start.join($end.$sep.' '.$start, array_map('base64_encode', $matches[0])).$end;
    }

    /**
     * Converts an email address into unicode entities.
     *
     * <code>
     * echo Txp::get('\Textpattern\Mail\Encode')->entityObfuscateAddress('john.doe@example.com');
     * </code>
     *
     * @param  string $address The email address
     * @return string Encoded email address
     */

    public function entityObfuscateAddress($address)
    {
        $ent = array();

        for ($i = 0; $i < strlen($address); $i++) {
            $ent[] = "&#".ord(substr($address, $i, 1)).";";
        }

        return join('', $ent);
    }

    /**
     * Removes new lines and NULL bytes from header lines, preventing
     * header injections.
     *
     * @param  string $string The string
     * @return string Escaped header value
     */

    public function escapeHeader($string)
    {
        return str_replace(array("\r\n", "\r", "\n", "\0"), array(' ', ' ', ' ', ''), (string)$string);
    }
}

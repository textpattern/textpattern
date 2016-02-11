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
 * Mail message fields.
 *
 * @since   4.6.0
 * @package Mail
 */

namespace Textpattern\Mail;

class Message
{
    /**
     * An array of senders.
     *
     * @var array
     */

    public $from = array();

    /**
     * An array of recipients.
     *
     * @var array
     */

    public $to = array();

    /**
     * The subject.
     *
     * @var string
     */

    public $subject = '';

    /**
     * The message body.
     *
     * @var string
     */

    public $body = '';

    /**
     * An array of reply to addresses.
     *
     * @var array
     */

    public $replyTo = array();

    /**
     * An array of carbon copy addresses.
     *
     * @var array
     */

    public $cc = array();

    /**
     * An array of blind carbon copy addresses.
     *
     * @var array
     */

    public $bcc = array();

    /**
     * An array of additional headers.
     *
     * @var array
     */

    public $headers = array(
        'X-Mailer'                  => 'Textpattern',
        'Content-Transfer-Encoding' => '8bit',
        'Content-Type'              => 'text/plain; charset="UTF-8"',
    );
}

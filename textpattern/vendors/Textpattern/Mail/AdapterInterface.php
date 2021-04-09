<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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
 * Mail implementation template.
 *
 * @since   4.6.0
 * @package Mail
 */

namespace Textpattern\Mail;

interface AdapterInterface extends \Textpattern\Adaptable\AdapterInterface
{
    /**
     * Valid body text types.
     */

    const TYPES = array('plain', 'html');

    /**
     * Set the subject.
     *
     * <code>
     * Txp::get('\Textpattern\Mail\Compose')->subject('My subject');
     * </code>
     *
     * @param  string $subject The subject
     * @return AdapterInterface
     * @throws \Textpattern\Mail\Exception
     */

    public function subject($subject);

    /**
     * Set the message content.
     *
     * <code>
     * Txp::get('\Textpattern\Mail\Compose')->body('Plain-text based message.');
     * </code>
     *
     * @param  string|array $body The message text or array of plain/html parts
     * @param  string       $type The message content type (plain, or html) if $body is string
     * @return AdapterInterface
     * @throws \Textpattern\Mail\Exception
     */

    public function body($body, $type = 'plain');

    /**
     * Set an additional header.
     *
     * <code>
     * Txp::get('\Textpattern\Mail\Compose')->header('X-Mailer', 'abc_plugin');
     * </code>
     *
     * @param  string $name  The header name
     * @param  string $value The value
     * @return AdapterInterface
     * @throws \Textpattern\Mail\Exception
     */

    public function header($name, $value);

    /**
     * Send an email.
     *
     * <code>
     * Txp::get('\Textpattern\Mail\Compose')
     *     ->to('to@example.com')
     *     ->from('from@example.com')
     *     ->subject('Subject')
     *     ->body('Hello world!')
     *     ->send();
     * </code>
     *
     * @return AdapterInterface
     * @throws \Textpattern\Mail\Exception
     */

    public function send();
}

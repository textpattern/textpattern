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
 * Mail implementation template.
 *
 * @since   4.6.0
 * @package Mail
 */

interface Textpattern_Mail_AdapterInterface extends Textpattern_Adaptable_AdapterInterface
{
	/**
	 * Sets the subject.
	 *
	 * <code>
	 * Txp::get('MailCompose')->subject('My subject');
	 * </code>
	 *
	 * @param  string $subject The subject
	 * @return Textpattern_Mail_AdapterInterface
	 * @throws Textpattern_Mail_Exception
	 */

	public function subject($subject);

	/**
	 * Sets the message.
	 *
	 * <code>
	 * Txp::get('MailCompose')->body('Plain-text based message.');
	 * </code>
	 *
	 * @param  string $body The message
	 * @return Textpattern_Mail_AdapterInterface
	 * @throws Textpattern_Mail_Exception
	 */

	public function body($body);

	/**
	 * Sets an additional header.
	 *
	 * <code>
	 * Txp::get('MailCompose')->header('X-Mailer', 'abc_plugin');
	 * </code>
	 *
	 * @param  string $name  The header name
	 * @param  string $value The value
	 * @return Textpattern_Mail_AdapterInterface
	 * @throws Textpattern_Mail_Exception
	 */

	public function header($name, $value);

	/**
	 * Sends an email.
	 *
	 * <code>
	 * Txp::get('MailCompose')
	 * 	->to('to@example.com')
	 * 	->from('from@example.com')
	 * 	->subject('Subject')
	 * 	->body('Hello world!')
	 * 	->send();
	 * </code>
	 *
	 * @return Textpattern_Mail_AdapterInterface
	 * @throws Textpattern_Mail_Exception
	 */

	public function send();
}

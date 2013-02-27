<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2013 The Textpattern Development Team
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
 * Sends an email.
 *
 * @since   4.6.0
 * @package Email
 */

class Textpattern_Mail_Compose
{
	/**
	 * The email fields.
	 *
	 * @var Textpattern_Mail_Message
	 */

	protected $mail;

	/**
	 * An array of encoded headers.
	 *
	 * @var array
	 */

	protected $headers = array();

	/**
	 * Line separator.
	 *
	 * @var string
	 */

	protected $separator = "\n";

	/**
	 * The message encoding.
	 *
	 * @var string
	 */

	protected $charset = 'UTF-8';

	/**
	 * SMTP envelope sender address.
	 *
	 * @var string|bool
	 */

	protected $smtp_from = false;

	/**
	 * Constructor.
	 */

	public function __construct()
	{
		$this->mail = new Textpattern_Mail_Message();

		if (IS_WIN)
		{
			$this->separator = "\r\n";
		}

		if (get_pref('override_emailcharset') && is_callable('utf8_decode'))
		{
			$this->charset = 'ISO-8859-1';
		}

		if (is_valid_email(get_pref('smtp_from')))
		{
			if (IS_WIN)
			{
				ini_set('sendmail_from', get_pref('smtp_from'));
			}
			else if(!ini_get('safe_mode'))
			{
				$this->smtp_from = get_pref('smtp_from');
			}
		}

		$this->mail->headers = $this->headers = array(
			'X-Mailer'                  => 'Textpattern',
			'Content-Transfer-Encoding' => '8bit',
			'Content-Type'              => 'text/plain; charset="'.$this->charset.'"',
		);
	}

	/**
	 * Sets a recipiend.
	 *
	 * @param  string $address The email address
	 * @param  string $name    The name
	 * @return bool   TRUE on success
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * echo $email->to('john.doe@example.com', 'John Doe');
	 */

	public function to($address, $name = '')
	{
		return $this->add_address($this->mail->send_to, $address, $name);
	}

	/**
	 * Sets a sender.
	 *
	 * @param  string $address The email address
	 * @param  string $name    The name
	 * @return bool   TRUE on success
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * echo $email->from('john.doe@example.com', 'John Doe');
	 */

	public function from($address, $name = '')
	{
		return $this->add_address($this->mail->from, $address, $name);
	}

	/**
	 * Sets a reply to address.
	 *
	 * @param  string $address The email address
	 * @param  string $name    The name
	 * @return bool   TRUE on success
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * echo $email->reply_to('john.doe@example.com', 'John Doe');
	 */

	public function reply_to($address, $name = '')
	{
		return $this->add_address($this->mail->reply_to, $address, $name);
	}

	/**
	 * Sets carbon copy.
	 *
	 * @param  string $address The email address
	 * @param  string $name    The name
	 * @return bool   TRUE on success
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * echo $email->cc('john.doe@example.com', 'John Doe');
	 */

	public function cc($address, $name = '')
	{
		return $this->add_address($this->mail->cc, $address, $name);
	}

	/**
	 * Sets blind carbon copy.
	 *
	 * @param  string $address The email address
	 * @param  string $name    The name
	 * @return bool   TRUE on success
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * echo $email->bcc('john.doe@example.com', 'John Doe');
	 */

	public function bcc($address, $name = '')
	{
		return $this->add_address($this->mail->bcc, $address, $name);
	}

	/**
	 * Sets the subject.
	 *
	 * @param  string $subject The subject
	 * @return bool   TRUE on success
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * echo $email->subject('My subject');
	 */

	public function subject($subject)
	{
		if (!is_scalar($subject) || (string) $subject === '')
		{
			return false;
		}

		$this->mail->subject = $subject;
		return true;
	}

	/**
	 * Sets the message.
	 *
	 * @param  string $body The message
	 * @return bool TRUE on success
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * echo $email->body('Plain-text based message.');
	 */

	public function body($body)
	{
		$this->mail->body = $body;
		return true;
	}

	/**
	 * Sets an addition header.
	 *
	 * @param  string $name  The header name
	 * @param  string $value The value
	 * @return bool
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * echo $email->header('X-Mailer', 'abc_plugin');
	 */

	public function header($name, $value)
	{
		if ((string) $value !== '' && preg_match('/^[\041-\071\073-\176]+$/', $name))
		{
			$this->mail->header[$name] = $value;
			$this->headers[$name] = encode_mailheader(strip_rn($value), 'phrase');
			return true;
		}

		return false;
	}

	/**
	 * Sends an email.
	 *
	 * If the given arguments validate, the function fires
	 * a 'mail.handler' callback event. This event can be used
	 * replace the default mail handler.
	 *
	 * @return  bool Returns FALSE if sending fails
	 * @example
	 * $email = new Textpattern_Mail_Compose();
	 * $email->to('to@example.com');
	 * $email->from('from.@example.com');
	 * $email->subject('Subject');
	 * $email->body('Hello world!');
	 * echo $email->send();
	 */

	public function send()
	{
		if (is_disabled('mail') && !has_handler('mail.handler'))
		{
			return false;
		}

		if (!$this->mail->from || !$this->mail->send_to)
		{
			return false;
		}

		$subject = $this->mail->subject;
		$body = $this->mail->body;

		if ($this->charset != 'UTF-8')
		{
			$subject = utf8_decode($subject);
			$body = utf8_decode($body);
		}

		$subject = encode_mailheader(strip_rn($subject), 'text');

		foreach (array('from', 'send_to', 'reply_to', 'cc', 'bcc') as $field)
		{
			$$field = $this->encode_address_list($this->mail->$field);
		}

		$body = str_replace("\r\n", "\n", $body);
		$body = str_replace("\r", "\n", $body);
		$body = str_replace("\n", $this->separator, $body);
		$body = deNull($body);

		$headers = array();
		$headers['From'] = $from;

		if ($cc)
		{
			$headers['Cc'] = $cc;
		}

		if ($bcc)
		{
			$headers['Bcc'] = $bbc;
		}

		if ($reply_to)
		{
			$headers['Reply-to'] = $reply_to;
		}

		$headers += $this->headers;

		foreach ($headers as $name => &$value)
		{
			$value = $name.': '.$value;
		}

		$headers = join($this->separator, $headers).$this->separator;

		if (has_handler('mail.handler'))
		{
			return callback_event('mail.handler', '', 0, $this->mail, $send_to, $subject, $body, $headers) !== false;
		}

		if ($this->smtp_from)
		{
			return mail($send_to, $subject, $body, $headers, '-f'.$this->smtp_from);
		}

		return mail($send_to, $subject, $body, $headers);
	}

	/**
	 * Encodes an address list to a valid email header value.
	 *
	 * @param  array  $value The address list
	 * @return string
	 */

	protected function encode_address_list($value)
	{
		if (!$value)
		{
			return '';
		}

		$out = array();

		foreach ($value as $email => $name)
		{
			if ($this->charset != 'UTF-8')
			{
				$name = utf8_decode($name);
			}

			$out[] = trim(encode_mailheader(strip_rn($name), 'phrase').' <'.$email.'>');
		}

		return join(', ', $out);
	}

	/**
	 * Adds an address to the specified field.
	 *
	 * @param  string $field   The field
	 * @param  string $address The email address
	 * @param  string $name    The name
	 * @return bool   FALSE if the addresses doesn't validate as an email
	 */

	protected function add_address(&$field, $address, $name = '')
	{
		if (is_valid_email($address))
		{
			$field[$address] = $name;
			return true;
		}

		return false;
	}
}
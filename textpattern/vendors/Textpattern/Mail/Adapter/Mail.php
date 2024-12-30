<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2025 The Textpattern Development Team
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
 * Adapter for PHP's mail function.
 *
 * @since   4.6.0
 * @package Mail
 */

namespace Textpattern\Mail\Adapter;

use Textpattern\Mail\Encode;
use Textpattern\Mail\Exception;
use Textpattern\Mail\Message;

class Mail implements \Textpattern\Mail\AdapterInterface
{
    /**
     * The email fields.
     *
     * @var \Textpattern\Mail\Message
     */

    protected $mail;

    /**
     * Encoded email fields.
     *
     * @var \Textpattern\Mail\Message
     */

    protected $encoded;

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
     * Multipart boundary delimiters.
     *
     * Array key is the boundary type, and its value is the boundary string.
     *
     * @var array
     */

    protected $boundary = array();

    /**
     * SMTP envelope sender address.
     *
     * @var string|bool
     */

    protected $smtpFrom = false;

    /**
     * The encoder.
     *
     * @var Encode
     */

    protected $encoder;

    /**
     * Constructor.
     */

    public function __construct()
    {
        $this->mail = new Message();
        $this->encoded = new Message();
        $this->encoder = new Encode();
        $this->boundary['alternative'] = "Multipart_Boundary_alternative".md5(time());

        if (IS_WIN) {
            $this->separator = "\r\n";
        } elseif (ini_get('cgi.rfc2616_headers') != 0) {
            // Guard against non-Windows setups that use different character sets
            // or control characters. See http://www.faqs.org/rfcs/rfc2616.html
            $this->separator = "\r\n";
        }

        if (get_pref('override_emailcharset') && is_callable('utf8_decode')) {
            $this->charset = 'ISO-8859-1';
            $this->mail->headers['Content-Type'] = 'text/plain; charset="ISO-8859-1"';
            $this->encoded->headers['Content-Type'] = 'text/plain; charset="ISO-8859-1"';
        }

        $smtp_from = $this->encoder->fromRfcEmail(get_pref('smtp_from'));

        if (filter_var($smtp_from['email'], FILTER_VALIDATE_EMAIL)) {
            if (IS_WIN) {
                ini_set('sendmail_from', $smtp_from['email']);
            } else {
                $this->smtpFrom = $smtp_from['email'];
            }
        }
    }

    /**
     * Set or get a message field.
     *
     * @param  string $field The field
     * @param  array  $args  Arguments
     * @return \Textpattern\Mail\AdapterInterface
     * @throws \Textpattern\Mail\Exception
     */

    public function __call($field, array $args = array())
    {
        if (!$args) {
            if (property_exists($this->mail, $field) === false) {
                throw new Exception(gTxt('invalid_argument', array('{name}' => 'field')));
            }

            return $this->mail->$field;
        }

        $addresses = do_list_unique($args[0]);

        if (isset($args[1])) {
            // Not using _unique here. Multiple John Smiths are fine.
            $names = do_list($args[1]);

            foreach ($addresses as $idx => $address) {
                $this->addAddress($field, $address, empty($names[$idx]) ? '' : $names[$idx]);
            }

            return $this;
        }

        foreach ($addresses as $address) {
            $this->addAddress($field, $address);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function subject($subject)
    {
        if (!is_scalar($subject) || (string)$subject === '') {
            throw new Exception(gTxt('invalid_argument', array('{name}' => 'subject')));
        }

        $this->mail->subject = $subject;

        if ($this->charset != 'UTF-8') {
            $subject = safe_encode($subject, $this->charset, 'UTF-8');
        }

        $this->encoded->subject = $this->encoder->header($this->encoder->escapeHeader($subject), 'text');

        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function body($body, $type = 'plain')
    {
        $type = in_array($type, SELF::TYPES) ? $type : 'plain';
        $in = is_array($body) ? $body : array($type => $body);

        foreach ($in as $key => $block) {
            $this->mail->body[$key] = $block;

            if ($this->charset !== 'UTF-8') {
                $block = safe_encode($block, $this->charset, 'UTF-8');
            }

            $block = str_replace("\r\n", "\n", $block);
            $block = str_replace("\r", "\n", $block);
            $block = str_replace("\n", $this->separator, $block);
            $this->encoded->body[$key] = deNull($block);
        }

        return $this;
    }

    /**
     * Create mime boundaries ready for mailing and set appropriate header.
     *
     * @param array $body Body content as plain and html indexes.
     * @return string Body content appropriate to the type of desired output
     */

    public function formatBody($body)
    {
        $out = '';

        if (empty($body['html'])) {
            $out = $body['plain'];
        } else {
            $this->mail->headers['Content-Type'] = 'multipart/alternative; boundary="'.$this->boundary['alternative'].'"';
            $this->encoded->headers['Content-Type'] = 'multipart/alternative; boundary="'.$this->boundary['alternative'].'"';

            if (!empty($body['plain'])) {
                $out .= <<<EOMIME
--{$this->boundary['alternative']}
Content-Type: text/plain; charset="{$this->charset}"

{$body['plain']}

EOMIME;
            }

            $out .= <<<EOMIME
--{$this->boundary['alternative']}
Content-Type: text/html; charset="{$this->charset}"

{$body['html']}
--{$this->boundary['alternative']}--
EOMIME;
        }

        return $out;
    }

    /**
     * {@inheritdoc}
     */

    public function header($name, $value)
    {
        if ((string)$value === '' || !preg_match('/^[\041-\071\073-\176]+$/', $name)) {
            throw new Exception(gTxt('invalid_header'));
        }

        $this->mail->headers[$name] = $value;
        $this->encoded->headers[$name] = $this->encoder->header($this->encoder->escapeHeader($value), 'phrase');

        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function attach($fileInfo)
    {
        $this->mail->attachment[] = $fileInfo;

        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function send()
    {
        if (is_disabled('mail')) {
            throw new Exception(gTxt('disabled_function', array('{name}' => 'mail')));
        }

        if (!$this->mail->from || !$this->mail->to) {
            throw new Exception(gTxt('from_or_to_address_missing'));
        }

        $bodyField = $this->formatBody($this->encoded->body);

        $headers = array();
        $headers['From'] = $this->encoded->from;

        if ($this->encoded->cc) {
            $headers['Cc'] = $this->encoded->cc;
        }

        if ($this->encoded->bcc) {
            $headers['Bcc'] = $this->encoded->bcc;
        }

        if ($this->encoded->replyTo) {
            $headers['Reply-to'] = $this->encoded->replyTo;
        }

        // Handle attachments and boundaries.
        if ($this->mail->attachment) {
            $this->boundary['mixed'] = "Multipart_Boundary_mixed".md5(time());
            $this->mail->headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->boundary['mixed'] . '"';
            $this->encoded->headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->boundary['mixed'] . '"';
            $bodyField = '--' . $this->boundary['mixed'] . $this->separator . $bodyField;

            // Related (inline) content would wrap the current $bodyField here, but we
            // don't support that yet. So, directly append Mixed (attachment) content.
            foreach ($this->mail->attachment as $attachment) {
                $content = file_get_contents($attachment['filepath']);
                $content = chunk_split(base64_encode($content));
                $attachName = basename($attachment['name']);
                $bodyField .= $this->separator . <<<EOMIME
--{$this->boundary['mixed']}
Content-Type: {$attachment['type']}; name="{$attachName}"
Content-Description: {$attachment['name']}
Content-Disposition: attachment; filename="{$attachment['name']}";
Content-Transfer-Encoding: base64

{$content}

EOMIME;
            }

            $bodyField .= '--' . $this->boundary['mixed'] . '--';
        }

        // Concatenation preserves existing array entries so primary headers aren't
        // overwritten by custom ones.
        $headers += $this->encoded->headers;

        foreach ($headers as $name => &$value) {
            $value = $name.': '.$value;
        }

        $headers = join($this->separator, $headers).$this->separator;
        $additional_headers = ($this->smtpFrom ? '-f'.$this->smtpFrom : '');

        if (mail($this->encoded->to, $this->encoded->subject, $bodyField, $headers, $additional_headers) === false) {
            throw new Exception(gTxt('sending_failed'));
        }

        return $this;
    }

    /**
     * Add an address to the specified field.
     *
     * @param  string $field   The field
     * @param  string $address The email address
     * @param  string $name    The name
     * @return \Textpattern\Mail\AdapterInterface
     */

    protected function addAddress($field, $address, $name = '')
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->mail->$field = array_merge($this->mail->$field, array($address => $name));
            $this->encoded->$field = $this->encoder->addressList($this->mail->$field);

            return $this;
        }

        throw new Exception(gTxt('invalid_argument', array('{name}' => 'address')));
    }
}

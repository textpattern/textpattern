<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2020 The Textpattern Development Team
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
 * Adapter for external SMTP mail functionality.
 *
 * @since   4.9.0
 * @package Mail
 */

namespace Textpattern\Mail\Adapter;

use Textpattern\Mail\Encode;
use Textpattern\Mail\Exception;
use Textpattern\Mail\Message;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class SMTPMail implements \Textpattern\Mail\AdapterInterface
{
    /**
     * The email fields.
     *
     * @var \Textpattern\Mail\Message
     */

    protected $mail;

    /**
     * The mailer instance.
     *
     * @var \PHPMailer\PHPMailer\PHPMailer
     */

    protected $mailer;

    /**
     * Constructor.
     */

    public function __construct()
    {
        // Although the Message is populated, its values are not used.
        // Its primary purpose is to validate the allowed mail properties.
        $this->mail = new Message();
        $this->mailer = new PHPMailer(true);

        // Bypass the fact that PHPMailer clashes with <txp:php>.
        $this->mailer::$validator = 'phpinternal';

        // Use admin-side language if logged in, site language otherwise.
        if (is_logged_in()) {
            $lang = get_pref('language_ui');
        } else {
            $lang = get_pref('language');
        }

        $langpath = txpath.DS.'vendors'.DS.'phpmailer'.DS.'phpmailer'.DS.'language'.DS;

        foreach (array($lang, TEXTPATTERN_DEFAULT_LANG, 'en') as $langcode) {
            if (is_readable($langpath.'phpmailer.lang-'.$langcode.'.php')) {
                $this->mailer->SetLanguage($langcode);
                break;
            }
        }

        $sectype = defined('SMTP_SECTYPE') ? constant('SMTP_SECTYPE') : get_pref('smtp_sectype');

        $this->mailer->isSMTP(true);
        $this->mailer->isHTML(strpos(get_pref('html_email'), 'html') !== false);
        $this->mailer->Host = (string) defined('SMTP_HOST') ? constant('SMTP_HOST') : get_pref('smtp_host');
        $this->mailer->Port = (int) defined('SMTP_PORT') ? constant('SMTP_PORT') : get_pref('smtp_port');
        $this->mailer->Username = (string) defined('SMTP_USER') ? constant('SMTP_USER') : get_pref('smtp_user');
        $this->mailer->Password = (string) defined('SMTP_PASS') ? constant('SMTP_PASS') : get_pref('smtp_pass');
        $this->mailer->SMTPSecure = ($sectype === 'none') ? '' : $sectype;

        // Not a good idea, but allow it.
        if ($sectype === 'none') {
            $this->mailer->SMTPAutoTLS = false;
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                )
            );
        }

        $this->mailer->SMTPAuth = !empty($this->mailer->Username);
        $this->mailer->Debugoutput = function($str, $level) {
            file_put_contents(get_pref('tempdir').'/txp_smtp.log', gmdate('Y-m-d H:i:s'). "\t$level\t$str\n", FILE_APPEND | LOCK_EX);
        };

        $prod_status = get_pref('production_status');

        if ($prod_status === 'debug') {
            $this->mailer->SMTPDebug = 3;
        } elseif ($prod_status === 'testing') {
            $this->mailer->SMTPDebug = 2;
        }

        if (IS_WIN) {
            if ($this->mailer->Host) {
                ini_set('SMTP', $this->mailer->Host);
            }
        }

        if (get_pref('override_emailcharset') && is_callable('utf8_decode')) {
            $this->mailer->CharSet = 'ISO-8859-1';
        } else {
            $this->mailer->CharSet = 'UTF-8';
        }

        $smtp_from = get_pref('smtp_from');

        if (filter_var($smtp_from, FILTER_VALIDATE_EMAIL)) {
            if (IS_WIN) {
                ini_set('sendmail_from', $smtp_from);
            } else {
                $this->mail->from = $smtp_from;
            }
        }
    }

    /**
     * Sets or gets a message field.
     *
     * @param  string $name The field
     * @param  array  $args Arguments
     * @return \Textpattern\Mail\AdapterInterface
     * @throws \Textpattern\Mail\Exception
     */

    public function __call($name, array $args = null)
    {
        if (!$args) {
            if (property_exists($this->mail, $name) === false) {
                throw new Exception(gTxt('invalid_argument', array('{name}' => 'name')));
            }

            return $this->mail->$name;
        }

        if (isset($args[1])) {
            return $this->addAddress($name, $args[0], $args[1]);
        }

        return $this->addAddress($name, $args[0]);
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

        if ($this->mailer->CharSet !== 'UTF-8') {
            $subject = utf8_decode($subject);
        }

        $this->mailer->Subject = $subject;

        return $this;
    }

    /**
     * {@inheritdoc}
     */

    public function body($body)
    {
        $this->mail->body = $body;

        if ($this->mailer->CharSet !== 'UTF-8') {
            $body = utf8_decode($body);
        }

        // @todo which body? HTML? AltBody for plaintext?
        $this->mailer->Body = $body;

        return $this;
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

    public function send()
    {
        if (!$this->mail->from || !$this->mail->to) {
            throw new Exception(gTxt('from_or_to_address_missing'));
        }

        $from = $this->mail->from;
        $reply = $this->mail->replyTo;

        if (!$reply) {
            $this->mailer->addReplyTo($from);
        }

        $reps = array(
            '{SMTP_FROM}'     => (is_array($from) ? key($from) : $from),
            '{SMTP_REPLY_TO}' => (is_array($reply) ? key($reply) : $reply),
        );

        // Optional DKIM settings read from config.php
        foreach (array('domain', 'private', 'selector', 'passphrase', 'identity', 'copyHeaderFields') as $dkey) {
            $dkparam = 'DKIM_'.strtoupper($dkey);

            if (defined($dkparam)) {
                $dkeyname = 'DKIM_'.$dkey;
                $this->mailer->$dkeyname = strtr(constant($dkparam), $reps);
            }
        }

        try {
            $this->mailer->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
           throw $e;
        } catch (\Exception $e) {
           throw $e;
        }

        return $this;
    }

    /**
     * Adds an address to the specified field.
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

            switch ($field) {
                case 'cc':
                    $this->mailer->addCC($address, $name);
                    break;
                case 'bcc':
                    $this->mailer->addBCC($address, $name);
                    break;
                case 'replyTo':
                    $this->mailer->addReplyTo($address, $name);
                    break;
                case 'from':
                    $this->mailer->setFrom($address, $name);

                    if (empty($this->mail->replyTo)) {
                        $this->mail->replyTo = array_merge($this->mail->replyTo, array($address => $name));
                        $this->mailer->addReplyTo($address, $name);
                    }

                    break;
                case 'to':
                default:
                    $this->mailer->addAddress($address, $name);
                    break;
            }

            return $this;
        }

        throw new Exception(gTxt('invalid_argument', array('{name}' => 'address')));
    }
}

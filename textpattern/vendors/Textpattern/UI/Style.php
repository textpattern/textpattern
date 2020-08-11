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
 * A CSP-aware &lt;style&gt; or &lt;link&gt; tag.
 *
 * @example echo Txp::get('\Textpattern\UI\Style, 'body { font-size: 1.5em }');
 * @example echo Txp::get('\Textpattern\UI\Style)->setSource('/path/to/stylesheet.css');
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Style extends Tag implements UIInterface
{
    /**
     * Route this style tag only on these event(s).
     *
     * @var null|array
     */

    protected $targetEvent = null;

    /**
     * Route this style tag only on these step(s).
     *
     * @var null|array
     */

    protected $targetStep = null;

    /**
     * Content store. Can be added to and flushed at will.
     *
     * @var string
     */

    protected static $store = '';

    /**
     * Location for the href attribute.
     *
     * Overrides any content added to the tag and renders a &lt;link&gt; instead.
     *
     * @var string
     */

    protected $href = null;

    /**
     * Whether to add cache-busting content (version/date) to the href link
     *
     * @var array
     */

    protected $append = array(
        'version' => false,
        'date'    => false, // @todo. Unsupported at present
    );

    /**
     * Construct content for the style/link tag.
     *
     * Note that a style tag is created but here, but it may be overridden in
     * the render() stage if a source is supplied instead.
     *
     * @param string  $content The style content, without surrounding style tags
     */

    public function __construct($content = null)
    {
        parent::__construct('style');

        if ($content !== null) {
            $this->setContent($content);
        }
    }

    /**
     * Set/append content between the tags. Chainable.
     *
     * Call this multiple times to append.
     *
     * @param  string $content Content to set
     */

    public function setContent($content, $flush = null)
    {
        if ($flush === null) {
            $this->content = $content;
        } elseif ($flush === false) {
            self::$store .= n.$content.n;
        } elseif ($flush === true) {
            $this->content = self::$store.n.$content;
            self::$store = '';
        }

        return $this;
    }

    /**
     * Set the events/steps to which this style tag will be attached. Chainable.
     *
     * @param array|string $evt Array or comma-separated list of events for this tag
     * @param array|string $stp Array or comma-separated list of steps for this tag
     */

    public function setRoute($evt = null, $stp = null)
    {
        $this->targetEvent = empty($evt) ? null : (is_array($evt) ? $evt : do_list_unique($evt));
        $this->targetStep = empty($stp) ? null : (is_array($stp) ? $stp : do_list_unique($stp));

        return $this;
    }

    /**
     * Set the source URL. Chainable.
     *
     * Overrides any content added to the tag.
     *
     * Note that appending info to the source only works for stable releases, not dev.
     *
     * @param string $href   URL of the stylesheet
     * @param string $append Cache-busting content to add ('version' or 'date')
     */

    public function setSource($href, $append = null)
    {
        $this->href = (string)$href;

        if ($append && array_key_exists($append, $this->append) && strpos(txp_version, '-dev') === false) {
            $this->append[$append] = true;
        }

        return $this;
    }

    /**
     * Render the tag.
     *
     * @return string HTML
     */

    public function render($flavour = 'complete')
    {
        global $event, $step, $csp_nonce;

        if (
            ($this->targetEvent === null || in_array($event, $this->targetEvent)) &&
            ($this->targetStep === null || in_array($step, $this->targetStep))
        ) {
            if ($this->href !== null) {
                if ($this->append['version']) {
                    $ext = pathinfo($this->href, PATHINFO_EXTENSION);

                    if ($ext) {
                        $this->href = substr($this->href, 0, (strlen($ext) + 1) * -1);
                        $ext = '.'.$ext;
                    }

                    $this->href .= '.v'.txp_version.$ext;
                }

                $this->setTag('link')
                    ->setAtts(array(
                        'rel'  => 'stylesheet',
                        'href' => $this->href,
                    )
                );

                $flavour = 'self-closing';
            }

            // Include the nonce if a style-src element uses it in
            // the Content Security Policy.
            if ($csp_nonce && preg_match_all("/style-src(-elem|-attr)?\s+('[a-zA-Z0-9\-]+'\s+)*'nonce-.*?(?=;)/", CONTENT_SECURITY_POLICY) > 0) {
                $this->setAtt('nonce', $csp_nonce);
            }

            return n.parent::render($flavour);
        }

        return '';
    }
}

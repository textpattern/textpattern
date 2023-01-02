<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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
 * A CSP-aware &lt;script&gt; tag.
 *
 * Replaces script_js().
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class Script extends Tag implements UIInterface
{
    /**
     * Route this script tag only on these event(s).
     *
     * @var null|array
     */

    protected $targetEvent = null;

    /**
     * Route this script tag only on these step(s).
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
     * Content for the &lt;noscript&gt; tag.
     *
     * @var string
     */

    protected $noscript = null;

    /**
     * Location for the src attribute.
     *
     * Overrides any content added to the tag.
     *
     * @var string
     */

    protected $src = null;

    /**
     * Whether to add cache-busting content (version/date) to the src link
     *
     * @var array
     */

    protected $append = array(
        'version' => false,
        'date'    => false, // @todo. Unsupported at present
    );

    /**
     * Construct content for the script tag.
     *
     * If &lt;script&gt; tags are passed in as content, they are removed.
     *
     * @param string  $content The script content, without surrounding script tags
     */

    public function __construct($content = null)
    {
        parent::__construct('script');

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
        $content = preg_replace('#<(/?)(script)#i', '\\x3c$1$2', $content);

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
     * Set content for the noscript tag. Chainable.
     *
     * @param  string $content Content to set when scripting is unavailable
     */

    public function setNoscript($content)
    {
        $this->noscript = n.$content.n;

        return $this;
    }

    /**
     * Set the events/steps to which this script tag will be attached. Chainable.
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
     * Set the source script. Chainable.
     *
     * Overrides any content added to the tag.
     *
     * Note that appending info to the source only works for stable releases, not dev.
     *
     * @param string $src    Source of the script
     * @param string $append Cache-busting content to add ('version' or 'date')
     */

    public function setSource($src, $append = null)
    {
        $this->src = (string)$src;

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
            // Include the nonce if a script-src element uses it in
            // the Content Security Policy.
            if ($csp_nonce && preg_match_all("/script-src(-elem|-attr)?\s+('[a-zA-Z0-9\-]+'\s+)*'nonce-.*?(?=;)/", CONTENT_SECURITY_POLICY) > 0) {
                $this->setAtt('nonce', $csp_nonce);
            }

            if ($this->src) {
                if ($this->append['version']) {
                    $ext = pathinfo($this->src, PATHINFO_EXTENSION);

                    if ($ext) {
                        $this->src = substr($this->src, 0, (strlen($ext) + 1) * -1);
                        $ext = '.'.$ext;
                    }

                    $this->src .= '.v'.txp_version.$ext;
                }

                $this->setAtt('src', $this->src);

                return n.parent::render('complete');
            }

            $out = n.parent::render('complete');

            if ($this->noscript) {
                $noscript = new \Textpattern\UI\Tag('noscript');
                $noscript->setContent($this->noscript);
                $out .= n.$noscript->render('complete');
            }

            return $out;
        }

        return '';
    }
}

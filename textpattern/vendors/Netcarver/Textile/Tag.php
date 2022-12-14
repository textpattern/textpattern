<?php

/**
 * Textile - A Humane Web Text Generator.
 *
 * @link https://github.com/textile/php-textile
 */

/*
 * Copyright (c) 2016-2017, Netcarver https://github.com/netcarver
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * * Neither the name Textile nor the names of its contributors may be used to
 * endorse or promote products derived from this software without specific
 * prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Netcarver\Textile;

/**
 * Renders HTML elements.
 *
 * This class can be used to HTML elements. It
 * does not sanitise attribute values, but can be
 * used to construct tags with nice object oriented
 * syntax.
 *
 * bc. use Netcarver\Textile\Tag;
 * $img = new Tag('img');
 * echo (string) $img->class('big blue')->src('images/elephant.jpg');
 *
 * @method Tag alt(string $text, bool $allowEmpty = false)
 * @method Tag align(string $alignment)
 * @method Tag height(string|int $height)
 * @method Tag href(string $url, bool $allowEmpty = false)
 * @method Tag rel(string $relationship)
 * @method Tag src(string $url, bool $allowEmpty = false)
 * @method Tag title(string $title)
 * @method Tag width(string|int $width)
 * @internal
 */
class Tag extends DataBag
{
    /**
     * The name of the tag.
     *
     * @var string|null
     */
    protected $tag;

    /**
     * Whether the tag is self-closing.
     *
     * @var bool
     */
    protected $selfclose;

    /**
     * Constructor.
     *
     * @param string|null $name The tag name
     * @param array<string, int|string> $attributes  An array of attributes
     * @param bool  $selfclosing Whether the tag is self-closing
     */
    public function __construct($name, array $attributes = null, $selfclosing = true)
    {
        parent::__construct($attributes);
        $this->tag = $name;
        $this->selfclose = $selfclosing;
    }

    /**
     * Returns the tag as HTML.
     *
     * bc. $img = new Tag('img');
     * $img->src('images/example.jpg')->alt('Example image');
     * echo (string) $img;
     *
     * @return string A HTML element
     */
    public function __toString()
    {
        $attributes = '';

        if ($this->data) {
            ksort($this->data);
            foreach ($this->data as $name => $value) {
                $attributes .= " $name=\"$value\"";
            }
        }

        if ($this->tag) {
            return '<' . $this->tag . $attributes . (($this->selfclose) ? ' />' : '>');
        }

        return $attributes;
    }
}

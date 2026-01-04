<?php
/**
 * SLIR Image Library
 *
 * This file is part of SLIR (Smart Lencioni Image Resizer).
 *
 * Copyright (c) 2014 Joe Lencioni <joe.lencioni@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @copyright Copyright © 2014, Joe Lencioni
 * @copyright Copyright © 2026, The Textpattern Development Team
 * @license MIT
 * @since 4.9.0
 * @package SLIR
 */
namespace lencioni\SLIR\libs;

use \lencioni\SLIR\SLIRConfig;

abstract class SLIRImage
{
    /**
     * @var string Path to file
     */
    protected $path;

    /**
     * @var string Path to original file
     */
    protected $originalPath;

    /**
     * @var integer quality to render image at
     */
    protected $quality;

    /**
     * @var string background color in hex
     */
    protected $background;

    /**
     * @var float amount to sharpen
     */
    protected $sharpeningFactor;

    /**
     * @var boolean
     */
    protected $progressive;

    /**
     * @var string specified cropper to use
     */
    protected $cropper;

    /**
     * @var array information about the image
     */
    protected $info;

    /**
     * Mime types
     * @var array
     * @since 4.9.0
     */
    private $mimeTypes  = array(
        'JPEG'  => array(
            'image/jpeg'  => 1,
        ),
        'WEBP'  => array(
            'image/webp'  => 1,
        ),
        'AVIF'  => array(
            'image/avif'  => 1,
        ),
        'GIF' => array(
            'image/gif'   => 1,
        ),
        'PNG' => array(
            'image/png'   => 1,
            'image/x-png' => 1,
        ),
        'BMP' => array(
            'image/bmp'       => 1,
            'image/x-ms-bmp'  => 1,
        ),
        'SVG' => array(
            'image/svg+xml'   => 1,
        ),
    );

    /**
     * @param string $path
     * @return void
     */
    public function __construct($path = null)
    {
        if ($path !== null) {
            $this->setPath($path);
            $this->setOriginalPath($path);
            $this->quality = TEXTPATTERN_THUMB_QUALITY;
        }
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        unset(
                $this->path,
                $this->originalPath,
                $this->info
        );
    }

    /**
     * Sets the path of the file
     * @param string $path
     * @return SLIRImageLibrary
     * @since 4.9.0
     */
    final public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Gets the path of the file
     * @return string
     * @since 4.9.0
     */
    final public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     * @since 4.9.0
     */
    final public function getFullPath()
    {
        return SLIRConfig::$documentRoot . $this->getPath();
    }

    /**
     * Sets the path of the original file
     * @param string $path
     * @return SLIRImageLibrary
     * @since 4.9.0
     */
    final public function setOriginalPath($path)
    {
        $this->originalPath = $path;
        return $this;
    }

    /**
     * Gets the path of the original file
     * @return string
     * @since 4.9.0
     */
    final public function getOriginalPath()
    {
        return $this->originalPath;
    }

    /**
     * @return integer
     * @since 4.9.0
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @param integer $quality
     * @return SLIRImageLibrary
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * @return string
     * @since 4.9.0
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * @param string $color in hex
     * @return SLIRImageLibrary
     */
    public function setBackground($color)
    {
        $this->background = $color;
        return $this;
    }

        /**
     * @return boolean
     * @since 4.9.0
     */
    public function getProgressive()
    {
        return $this->progressive;
    }

    /**
     * @param boolean $progressive
     * @return SLIRImageLibrary
     */
    public function setProgressive($progressive)
    {
        $this->progressive = $progressive;
        return $this;
    }

    /**
     * Sets the sharpening factor of the image
     * @param float $sharpeningFactor
     * @return SLIRImageLibrary
     * @since 4.9.0
     */
    final public function setSharpeningFactor($sharpeningFactor)
    {
        $this->sharpeningFactor = $sharpeningFactor;
        return $this;
    }

    /**
     * Gets the sharpening factor of the image
     * @return float
     * @since 4.9.0
     */
    final public function getSharpeningFactor()
    {
        return $this->sharpeningFactor;
    }

    /**
     * Checks the mime type to see if it is an image
     *
     * @since 4.9.0
     * @return boolean
     */
    final public function isImage()
    {
        if (substr($this->getMimeType(), 0, 6) == 'image/') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 4.9.0
     * @param string $type Can be 'JPEG', 'GIF', 'PNG', or 'BMP'
     * @return boolean
     */
    final public function isOfType($type = 'JPEG')
    {
        if (isset($this->mimeTypes[$type][$this->getMimeType()])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final public function isJPEG()
    {
        return $this->isOfType('JPEG');
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final public function isWEBP()
    {
        return $this->isOfType('WEBP');
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final public function isAVIF()
    {
        return $this->isOfType('AVIF');
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final public function isSVG()
    {
        return $this->isOfType('SVG');
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final public function isGIF()
    {
        return $this->isOfType('GIF');
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final public function isBMP()
    {
        return $this->isOfType('BMP');
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final public function isPNG()
    {
        return $this->isOfType('PNG');
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final public function isAbleToHaveTransparency()
    {
        if ($this->isPNG() || $this->isGIF() || $this->isWEBP() || $this->isAVIF()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    final protected function isSharpeningDesired()
    {
        if ($this->isJPEG() || $this->isWEBP()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 4.9.0
     * @return integer
     */
    final public function getArea()
    {
        return $this->getWidth() * $this->getHeight();
    }

    /**
     * @since 4.9.0
     * @return float
     */
    final public function getRatio()
    {
        if ($this->getHeight() === 0 || $this->getHeight() === null) {
            return null;
        } else {
            return $this->getWidth() / $this->getHeight();
        }
    }

    /**
     * @since 4.9.0
     * @return float
     */
    final public function getCropRatio()
    {
        if ($this->getCropHeight() === 0 || $this->getCropHeight() === null) {
            return null;
        } else {
            return $this->getCropWidth() / $this->getCropHeight();
        }
    }

    /**
     * @since 4.9.0
     * @return integer
     */
    final public function getCropWidth()
    {
        return (int) $this->getInfo('cropWidth');
    }

    /**
     * @since 4.9.0
     * @return integer
     */
    final public function getCropHeight()
    {
        return (int) $this->getInfo('cropHeight');
    }

    /**
     * @since 4.9.0
     * @param integer $width
     * @return SLIRImage
     */
    final public function setCropWidth($width)
    {
        $this->info['cropWidth'] = $width;
        return $this;
    }

    /**
     * @since 4.9.0
     * @param integer $height
     * @return SLIRImage
     */
    final public function setCropHeight($height)
    {
        $this->info['cropHeight'] = $height;
        return $this;
    }

    /**
     * Gets the width of the image
     * @return integer
     * @since 4.9.0
     */
    public function getWidth()
    {
        return (int) $this->getInfo('width');
    }

    /**
     * Gets the height of the image
     * @return integer
     * @since 4.9.0
     */
    public function getHeight()
    {
        return (int) $this->getInfo('height');
    }

    /**
     * @since 4.9.0
     * @param integer $width
     * @return SLIRImage
     */
    final public function setWidth($width)
    {
        $this->info['width'] = $width;
        return $this;
    }

    /**
     * @since 4.9.0
     * @param integer $height
     * @return SLIRImage
     */
    final public function setHeight($height)
    {
        $this->info['height'] = $height;
        return $this;
    }

    /**
     * Gets the MIME type of the image
     * @return string
     * @since 4.9.0
     */
    public function getMimeType()
    {
        return (string) $this->getInfo('mime');
    }

    /**
     * Sets the MIME type of the image
     * @param string $mime
     * @return SLIRImageLibrary
     * @since 4.9.0
     */
    public function setMimeType($mime)
    {
        $this->info['mime'] = $mime;
        return $this;
    }

    /**
     * @return string
     * @since 4.9.0
     */
    public function getCropper()
    {
        if ($this->cropper !== null) {
            return $this->cropper;
        } else {
            return SLIRConfig::$defaultCropper;
        }
    }

    /**
     * @param string $cropper
     * @return SLIRImage
     * @since 4.9.0
     */
    public function setCropper($cropper)
    {
        $this->cropper = $cropper;
        return $this;
    }

    /**
     * @return integer size of image data
     */
    public function getDatasize()
    {
        return strlen($this->getData());
    }

    /**
     * Turns on transparency for image if no background fill color is
     * specified, otherwise, fills background with specified color
     *
     * @since 4.9.0
     * @return SLIRImageLibrary
     */
    final public function background()
    {
        if ($this->isAbleToHaveTransparency()) {
            if ($this->getBackground() === false || $this->getBackground() === null) {
                // If this is a GIF or a PNG, we need to set up transparency
                $this->enableTransparency();
            } else {
                // Fill the background with the specified color for matting purposes
                $this->fill($this->getBackground());
            }
        }

        return $this;
    }

    /**
     * @since 4.9.0
     * @return boolean
     */
    protected function croppingIsNeeded()
    {
        if ($this->getCropWidth() === 0 || $this->getCropHeight() === 0) {
            return false;
        } else if ($this->getCropWidth() < $this->getWidth() || $this->getCropHeight() < $this->getHeight()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 4.9.0
     */
    public function applyTransformations()
    {
        $this->crop()
            ->sharpen()
            ->interlace()
            ->optimize();
    }
}

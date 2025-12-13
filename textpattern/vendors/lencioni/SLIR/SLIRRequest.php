<?php
/**
 * Class definition file for SLIRRequest
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
 * @license MIT
 * @since 2.0
 * @package SLIR
 */

/**
 * SLIR request class
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 */
namespace lencioni\SLIR;

class SLIRRequest
{

    const CROP_RATIO_DELIMITERS = ':.x';

    /**
     * Path to image
     *
     * @since 2.0
     * @var string
     */
    private $path;

    /**
     * Maximum width for resized image, in pixels
     *
     * @since 2.0
     * @var integer
     */
    private $width;

    /**
     * Maximum height for resized image, in pixels
     *
     * @since 2.0
     * @var integer
     */
    private $height;

    /**
     * Ratio of width:height to crop image to.
     *
     * For example, if a square shape is desired, the crop ratio should be "1:1"
     * or if a long rectangle is desired, the crop ratio could be "4:1". Stored
     * as an associative array with keys being 'width' and 'height'.
     *
     * @since 2.0
     * @var array
     */
    private $cropRatio;

    /**
     * Name of the cropper to use, e.g. 'centered' or 'smart'
     *
     * @since 2.0
     * @var string
     */
    private $cropper;

    /**
     * Quality of rendered image
     *
     * @since 2.0
     * @var integer
     */
    private $quality;

    /**
     * Whether or not progressive JPEG output is turned on
     * @var boolean
     * @since 2.0
     */
    private $progressive;

    /**
     * Color to fill background of transparent PNGs and GIFs
     * @var string
     * @since 2.0
     */
    private $background;

    /**
     * @since 2.0
     * @var boolean
     */
    private $isUsingDefaultImagePath  = false;

    /**
     * Supplied request - overrides the URL
     *
     * @since 2.0
     * @var string
     */
    private $request = null;

    /**
     * The full paramstring extracted from the URL
     *
     * @since 2.0
     * @var string
     */
    private $paramString = null;


    /**
     * @since 2.0
     */
    final public function __construct($request = null)
    {
        if ($request !== null) {
            $this->request = $request;
        }
    }

    /**
     * @since 2.0
     */
    final public function initialize()
    {
        $params = $this->getParameters();

        // Set image path first
        if (isset($params['i']) && $params['i'] != '' && $params['i'] != '/') {
            $this->__set('i', $params['i']);
            unset($params['i']);
        } else if (SLIRConfig::$defaultImagePath !== null) {
            $this->__set('i', SLIRConfig::$defaultImagePath);
        } else {
            throw new \RuntimeException('Source image was not specified.');
        } // if

        // Set the rest of the parameters
        foreach ($params as $name => $value) {
            $this->__set($name, $value);
        } // foreach
    }

    /**
     * Destructor method. Try to clean up memory.
     *
     * @return void
     * @since 2.0
     */
    final public function __destruct()
    {
        unset($this->path);
        unset($this->width);
        unset($this->height);
        unset($this->cropRatio);
        unset($this->cropper);
        unset($this->quality);
        unset($this->progressive);
        unset($this->background);
        unset($this->isUsingDefaultImagePath);
    }

    /**
     * @since 2.0
     * @return void
     */
    final public function __set($name, $value)
    {
        switch ($name) {
            case 'i':
            case 'image':
            case 'imagePath':
            case 'path':
                $this->setPath($value);
                    break;

            case 'w':
            case 'width':
                $this->setWidth($value);
                    break;

            case 'h':
            case 'height':
                $this->setHeight($value);
                    break;

            case 'q':
            case 'quality':
                $this->setQuality($value);
                    break;

            case 'p':
            case 'progressive':
                $this->setProgressive($value);
                    break;

            case 'b':
            case 'background':
            case 'backgroundFillColor':
                $this->setBackgroundFillColor($value);
                    break;

            case 'c':
            case 'cropRatio':
                $this->setCropRatio($value);
                    break;
        } // switch
    }

    /**
     * @since 2.0
     * @return mixed
     */
    final public function __get($name)
    {
        return $this->$name;
    }

    /**
     * @since 2.0
     * @return void
     */
    private function setWidth($value)
    {
        $this->width  = (int) $value;
        if ($this->width < 1) {
            throw new \RuntimeException('Width must be greater than 0: ' . $this->width);
        }
    }

    /**
     * @since 2.0
     * @return void
     */
    private function setHeight($value)
    {
        $this->height = (int) $value;
        if ($this->height < 1) {
            throw new \RuntimeException('Height must be greater than 0: ' . $this->height);
        }
    }

    /**
     * @since 2.0
     * @return void
     */
    private function setQuality($value)
    {
        $this->quality  = (int) $value;
        if ($this->quality < 0 || $this->quality > 100) {
            throw new \RuntimeException('Quality must be between 0 and 100: ' . $this->quality);
        }
    }

    /**
     * @param string $value
     * @return void
     */
    private function setProgressive($value)
    {
        $this->progressive  = (bool) $value;
    }

    /**
     * @param string $value
     * @return void
     */
    private function setBackgroundFillColor($value)
    {
        $this->background = preg_replace('/[^0-9a-fA-F]/', '', $value);

        if (strlen($this->background) == 3) {
            $this->background = $this->background[0]
                .$this->background[0]
                .$this->background[1]
                .$this->background[1]
                .$this->background[2]
                .$this->background[2];
        } else if (strlen($this->background) != 6) {
            throw new \RuntimeException('Background fill color must be in hexadecimal format, longhand or shorthand: ' . $this->background);
        } // if
    }

    /**
     * @param string $value
     * @return void
     */
    private function setCropRatio($value)
    {
        $delimiters = preg_quote(self::CROP_RATIO_DELIMITERS);
        $ratio      = preg_split("/[$delimiters]/", (string) urldecode($value));
        if (count($ratio) >= 2) {
            if ((float) $ratio[0] == 0 || (float) $ratio[1] == 0) {
                throw new \RuntimeException('Crop ratio must not contain a zero: ' . (string) $value);
            }

            $this->cropRatio  = array(
                'width'   => (float) $ratio[0],
                'height'  => (float) $ratio[1],
                'ratio'   => (float) $ratio[0] / (float) $ratio[1]
            );

            // If there was a third part, that is the cropper being specified
            if (count($ratio) >= 3) {
                $this->cropper  = (string) $ratio[2];
            }
        } else {
            throw new \RuntimeException('Crop ratio must be in [width]x[height] format (e.g. 2x1): ' . (string) $value);
        } // if
    }

    /**
     * Determines the parameters to use for resizing
     *
     * @since 2.0
     * @return array
     */
    private function getParameters()
    {
        if (!$this->isUsingQueryString()) {
            // Using the mod_rewrite version
            return $this->getParametersFromURL();
        } else {
            // Using the query string version
            $this->paramString = $this->reconstructParameters($_GET);
            return $_GET;
        }
    }

    /**
     * [reconstructParameters description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    private function reconstructParameters($params)
    {
        unset($params['token'], $params['i']);

        return implode('-', array_map(function($k, $v){
            return "$k$v";
        }, array_keys($params), array_values($params)));
    }

    /**
     * Gets parameters from the URL
     *
     * This is used for requests that are using the mod_rewrite syntax
     *
     * @since 2.0
     * @return array
     */
    private function getParametersFromURL()
    {
        $params = array();

        // The parameters should be the first set of characters after the SLIR path
        $request = preg_replace(array('`.*?/' . preg_quote(basename(SLIRConfig::$pathToSLIR)) . '/`', '`cache\/rendered\/`'), '', (string) ($this->request === null ? $_SERVER['REQUEST_URI'] : $this->request), 1);
        $paramString  = strtok($request, '/');

        if ($paramString === false || $paramString === $request) {
            throw new \RuntimeException('Not enough parameters were given.

Available parameters:
 w = Maximum width
 h = Maximum height
 c = Crop ratio (width.height(.cropper?))
 q = Quality (0-100)
 b = Background fill color (RRGGBB or RGB)
 p = Progressive (0 or 1)

Example usage:
/slir/w300-h300-c1.1/path/to/image.jpg');

        }

        // The image path should start right after the parameters
        $this->paramString = $paramString;
        $params['i']  = substr($request, strlen($paramString) + 1); // +1 for the slash

        // The parameters are separated by hyphens
        $rawParam   = strtok($paramString, '-');

        while ($rawParam !== false) {
            if (strlen($rawParam) > 1) {
                // The name of each parameter should be the first character of the parameter string and the value of each parameter should be the remaining characters of the parameter string
                $params[$rawParam[0]] = substr($rawParam, 1);
            }

            $rawParam = strtok('-');
        }

        return $params;
    }

    /**
     * Determines if the request is using the mod_rewrite version or the query
     * string version
     *
     * @since 2.0
     * @return boolean
     */
    private function isUsingQueryString()
    {
        if (SLIRConfig::$forceQueryString === true) {
            return true;
        } else if (!empty($_SERVER['QUERY_STRING']) && count(array_intersect(array('i', 'w', 'h', 'q', 'c', 'b', 'p'), array_keys($_GET)))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the default image path set in the config is being used for this request
     *
     * @since 2.0
     * @return boolean
     */
    public function isUsingDefaultImagePath()
    {
        return $this->isUsingDefaultImagePath;
    }

    /**
     * @since 2.0
     * @param string $path
     */
    private function setPath($path)
    {
        $this->path = $this->localizePath((string) urldecode($path));

        if (!$this->isPathSecure()) {
            // Make sure the image path is secure
            throw new \RuntimeException('Image path may not contain ":", "..", "<", or ">"');
        } else if (!$this->pathExists()) {
            // Make sure the image file exists
            if (SLIRConfig::$defaultImagePath !== null && !$this->isUsingDefaultImagePath()) {
                $this->isUsingDefaultImagePath  = true;
                return $this->setPath(SLIRConfig::$defaultImagePath.$this->path);
            } else {
                throw new \RuntimeException('Image does not exist: ' . $this->fullPath());
            }
        }
    }

    /**
     * Strips the domain and query string from the path if either is there
     * @since 2.0
     * @return string
     */
    private function localizePath($path)
    {
        return '/' . trim($this->stripQueryString($this->stripProtocolAndDomain($path)), '/');
    }

    /**
     * Strips the protocol and domain from the path if it is there
     * @since 2.0
     * @return string
     */
    private function stripProtocolAndDomain($path)
    {
        return preg_replace('/^[^:]+:\/\/[^\/]+/i', '', $path);
    }

    /**
     * Strips the query string from the path if it is there
     * @since 2.0
     * @return string
     */
    private function stripQueryString($path)
    {
        return preg_replace('/\?.*+/', '', $path);
    }

    /**
     * Checks to see if the path is secure
     *
     * For security, directories may not contain ':' and images may not contain
     * '..', '<', or '>'.
     *
     * @since 2.0
     * @return boolean
     */
    private function isPathSecure()
    {
        if (strpos(dirname($this->path), ':') || preg_match('/(?:\.\.|<|>)/', $this->path)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Determines if the path exists
     *
     * @since 2.0
     * @return boolean
     */
    private function pathExists()
    {
        return is_file($this->fullPath());
    }

    /**
     * @return string
     * @since 2.0
     */
    final public function fullPath()
    {
        return SLIRConfig::$documentRoot . $this->path;
    }

    /**
     * @since 2.0
     * @return boolean
     */
    final public function isBackground()
    {
        if ($this->background !== null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 2.0
     * @return boolean
     */
    final public function isQuality()
    {
        if ($this->quality !== null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 2.0
     * @return boolean
     */
    final public function isCropping()
    {
        if (!empty($this->cropRatio['width']) && !empty($this->cropRatio['height'])) {
            return true;
        } else {
            return false;
        }
    }

}
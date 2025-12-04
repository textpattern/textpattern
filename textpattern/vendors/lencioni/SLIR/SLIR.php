<?php
/**
 * Class definition file for SLIR (Smart Lencioni Image Resizer)
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
 * SLIR (Smart Lencioni Image Resizer)
 * Resizes images, intelligently sharpens, crops based on width:height ratios,
 * color fills transparent GIFs and PNGs, and caches variations for optimal
 * performance.
 *
 * I love to hear when my work is being used, so if you decide to use this,
 * feel encouraged to send me an email. I would appreciate it if you would
 * include a link on your site back to Shifting Pixel (either the SLIR page or
 * shiftingpixel.com), but don?t worry about including a big link on each page
 * if you don?t want to?one will do just nicely. Feel free to contact me to
 * discuss any specifics (joe@shiftingpixel.com).
 *
 * REQUIREMENTS:
 *     - PHP 5.1.2+
 *     - GD
 *
 * RECOMMENDED:
 *     - mod_rewrite
 *
 * USAGE:
 * To use, place an img tag with the src pointing to the path of SLIR (typically
 * "/slir/") followed by the parameters, followed by the path to the source
 * image to resize. All parameters follow the pattern of a one-letter code and
 * then the parameter value:
 *     - Maximum width = w
 *     - Maximum height = h
 *     - Crop ratio = c
 *     - Quality = q
 *     - Background fill color = b
 *     - Progressive = p
 *
 * Note: filenames that include special characters must be URL-encoded (e.g.
 * plus sign, +, should be encoded as %2B) in order for SLIR to recognize them
 * properly. This can be accomplished by passing your filenames through PHP's
 * rawurlencode() or urlencode() function.
 *
 * EXAMPLES:
 *
 * Resizing a JPEG to a max width of 100 pixels and a max height of 100 pixels:
 * <code><img src="/slir/w100-h100/path/to/image.jpg" alt="Don't forget your alt
 * text" /></code>
 *
 * Resizing and cropping a JPEG into a square:
 * <code><img src="/slir/w100-h100-c1:1/path/to/image.jpg" alt="Don't forget
 * your alt text" /></code>
 *
 * Resizing a JPEG without interlacing (for use in Flash):
 * <code><img src="/slir/w100-p0/path/to/image.jpg" alt="Don't forget your alt
 * text" /></code>
 *
 * Matting a PNG with #990000:
 * <code><img src="/slir/b900/path/to/image.png" alt="Don't forget your alt
 * text" /></code>
 *
 * Without mod_rewrite (not recommended)
 * <code><img src="/slir/?w=100&amp;h=100&amp;c=1:1&amp;i=/path/to/image.jpg"
 * alt="Don't forget your alt text" /></code>
 *
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 *
 * @uses PEL
 *
 * @todo lock files when writing?
 * @todo Prevent SLIR from calling itself
 * @todo Percentage resizing?
 * @todo Animated GIF resizing?
 * @todo Seam carving?
 * @todo Crop zoom?
 * @todo Crop offsets?
 * @todo Remote image fetching?
 * @todo Alternative support for ImageMagick?
 * @todo Prevent files in cache from being read directly?
 * @todo split directory initialization into a separate
 * install/upgrade script with friendly error messages, an opportunity to give a
 * tip, and a button that tells me they are using it on their site if they like
 * @todo document new code
 * @todo clean up new code
 */

namespace lencioni\SLIR;

use \lencioni\SLIR\SLIRRequest;
use \lencioni\SLIR\SLIRExceptionHandler;
use \lencioni\SLIR\libs\gd\SLIRGDImage;
use \lencioni\SLIR\SLIRGarbageCollector;
use \lencioni\SLIR\icc\JPEG_ICC;

class SLIR
{
    /**
     * @since 2.0
     * @var string
     */
    const VERSION = '2.0b4';

    /**
     * @since 2.0
     * @var string
     */
    const CROP_CLASS_CENTERED = 'centered';

    /**
     * @since 2.0
     * @var string
     */
    const CROP_CLASS_TOP_CENTERED = 'topcentered';

    /**
     * @since 2.0
     * @var string
     */
    const CROP_CLASS_SMART = 'smart';

    /**
     * @var string
     * @since 2.0
     */
    const CONFIG_FILENAME = 'slirconfig.class.php';

    /**
     * Request object
     *
     * @since 2.0
     * @uses SLIRRequest
     * @var object
     */
    private $request;

    /**
     * Source image object
     *
     * @since 2.0
     * @uses SLIRImage
     * @var object
     */
    private $source;

    /**
     * Rendered image object
     *
     * @since 2.0
     * @uses SLIRImage
     * @var object
     */
    private $rendered;

    /**
     * Whether or not SLIR has alerady been initialized
     *
     * @since 2.0
     * @var boolean
     */
    private $isSLIRInitialized = false;

    /**
     * Whether or not the cache has already been initialized
     *
     * @since 2.0
     * @var boolean
     */
    private $isCacheInitialized = false;

    /**
     * Headers that have been sent.
     *
     * This is primarily used for testing.
     *
     * @since 2.0
     * @var array
     */
    private $headers = array();

    /**
     * The magic starts here
     *
     * @since 2.0
     */
    final public function __construct($path = null)
    {
        if ($path !== null) {
            if (empty($this->source)) {
                $this->source = new SLIRGDImage($path);
            }
        }
    }

    /**
     * Destructor method. Try to clean up memory a little.
     *
     * @return void
     * @since 2.0
     */
    final public function __destruct()
    {
        unset($this->request);
        unset($this->source);
        unset($this->rendered);
    }

    /**
     * Sets up SLIR to be able to process image resizing requests
     *
     * @since 2.0
     * @return void
     */
    public function initialize()
    {
        if (!$this->isSLIRInitialized) {
            // This helps prevent unnecessary warnings (which messes up images)
            // on servers that are set to display E_STRICT errors.
            $this->disableStrictErrorReporting();

            // Prevents ob_start('ob_gzhandler') in auto_prepend files from messing
            // up SLIR's output. However, if SLIR is being run from a command line
            // interface, we need to buffer the output so the command line does not
            // get messed up with garbage output of image data.
            if (!$this->isCLI()) {
                $this->escapeOutputBuffering();
            }

            $this->getConfig();

            // Set up our exception and error handler after the request cache to
            // help keep everything humming along nicely
            $this->initializeGarbageCollection();

            $this->isSLIRInitialized = true;
        }
    }

    /**
     * Processes the SLIR request from the parameters passed through the URL
     *
     * @since 2.0
     */
    public function processRequestFromURL()
    {
        $this->initialize();

        // Check the cache based on the request URI
        if ($this->shouldUseRequestCache() && $this->isRequestCached()) {
            return $this->serveRequestCachedImage();
        }

        // See if there is anything we actually need to do
        if ($this->isSourceImageDesired()) {
            return $this->serveSourceImage();
        }

        // Check the cache based on the properties of the rendered image
        if ($this->isRenderedCached()) {
            return $this->serveRenderedCachedImage();
        }

        // Image is not cached in any way, so we need to render the image,
        // cache it, and serve it up to the client
        $this->render();
        $this->serveRenderedImage();
    }

    /**
     * @since 2.0
     * @return SLIRRequest
     */
    private function getRequest()
    {
        if (empty($this->request)) {
            $this->request  = new SLIRRequest();
            $this->request->initialize();
        }

        return $this->request;
    }

    /**
     * @since 2.0
     * @return SLIRImage
     */
    private function getSource()
    {
        if (empty($this->source)) {
            $this->source = new SLIRGDImage($this->getRequest()->path);

            // If either a max width or max height are not specified or larger than
            // the source image we default to the dimension of the source image so
            // they do not become constraints on our resized image.
            if (!$this->getRequest()->width || $this->getRequest()->width > $this->source->getWidth()) {
                $this->getRequest()->width = $this->source->getWidth();
            }

            if (!$this->getRequest()->height ||  $this->getRequest()->height > $this->source->getHeight()) {
                $this->getRequest()->height = $this->source->getHeight();
            }
        }

        return $this->source;
    }

    /**
     * @since 2.0
     * @return SLIRImage
     */
    private function getRendered()
    {
        if (empty($this->rendered)) {
            $this->rendered = new SLIRGDImage();
            $this->rendered->setOriginalPath($this->getSource()->getPath());

            // Cropping
            /*
            To determine the width and height of the rendered image, the following
            should occur.

            If cropping an image is required, we need to:
             1. Compute the dimensions of the source image after cropping before
                resizing.
             2. Compute the dimensions of the resized image before cropping. One of
                these dimensions may be greater than maxWidth or maxHeight because
                they are based on the dimensions of the final rendered image, which
                will be cropped to fit within the specified maximum dimensions.
             3. Compute the dimensions of the resized image after cropping. These
                must both be less than or equal to maxWidth and maxHeight.
             4. Then when rendering, the image needs to be resized, crop offsets
                need to be computed based on the desired method (smart or centered),
                and the image needs to be cropped to the specified dimensions.

            If cropping an image is not required, we need to compute the dimensions
            of the image without cropping. These must both be less than or equal to
            maxWidth and maxHeight.
            */
            if ($this->isCroppingNeeded()) {
                // Determine the dimensions of the source image after cropping and
                // before resizing

                if ($this->getRequest()->cropRatio['ratio'] > $this->getSource()->getRatio()) {
                    // Image is too tall so we will crop the top and bottom
                    $this->getSource()->setCropHeight($this->getSource()->getWidth() / $this->getRequest()->cropRatio['ratio']);
                    $this->getSource()->setCropWidth($this->getSource()->getWidth());
                } else {
                    // Image is too wide so we will crop off the left and right sides
                    $this->getSource()->setCropWidth($this->getSource()->getHeight() * $this->getRequest()->cropRatio['ratio']);
                    $this->getSource()->setCropHeight($this->getSource()->getHeight());
                }
            }

            if ($this->shouldResizeBasedOnWidth()) {
                $resizeFactor = $this->resizeWidthFactor();
                $this->rendered->setHeight(round($resizeFactor * $this->getSource()->getHeight()));
                $this->rendered->setWidth(round($resizeFactor * $this->getSource()->getWidth()));

                // Determine dimensions after cropping
                if ($this->isCroppingNeeded()) {
                    $this->rendered->setCropHeight(round($resizeFactor * $this->getSource()->getCropHeight()));
                    $this->rendered->setCropWidth(round($resizeFactor * $this->getSource()->getCropWidth()));
                } // if
            } else if ($this->shouldResizeBasedOnHeight()) {
                $resizeFactor = $this->resizeHeightFactor();
                $this->rendered->setWidth(round($resizeFactor * $this->getSource()->getWidth()));
                $this->rendered->setHeight(round($resizeFactor * $this->getSource()->getHeight()));

                // Determine dimensions after cropping
                if ($this->isCroppingNeeded()) {
                    $this->rendered->setCropHeight(round($resizeFactor * $this->getSource()->getCropHeight()));
                    $this->rendered->setCropWidth(round($resizeFactor * $this->getSource()->getCropWidth()));
                } // if
            } else if ($this->isCroppingNeeded()) {
                // No resizing is needed but we still need to crop
                $ratio  = ($this->resizeUncroppedWidthFactor() > $this->resizeUncroppedHeightFactor())
                    ? $this->resizeUncroppedWidthFactor()
                    : $this->resizeUncroppedHeightFactor();

                $this->rendered->setWidth(round($ratio * $this->getSource()->getWidth()));
                $this->rendered->setHeight(round($ratio * $this->getSource()->getHeight()));

                $this->rendered->setCropWidth(round($ratio * $this->getSource()->getCropWidth()));
                $this->rendered->setCropHeight(round($ratio * $this->getSource()->getCropHeight()));
            } // if

            $this->rendered->setSharpeningFactor($this->calculateSharpnessFactor())
                ->setBackground($this->getBackground())
                ->setQuality($this->getQuality())
                ->setProgressive($this->getProgressive())
                ->setMimeType($this->getMimeType())
                ->setCropper($this->getRequest()->cropper);

            // Set up the appropriate image handling parameters based on the original
            // image's mime type
            // @todo some of this code should be moved to the SLIRImage class
            /*
            $this->renderedMime       = $this->getSource()->getMimeType();
            if ($this->getSource()->isJPEG()) {
                $this->rendered->progressive  = ($this->getRequest()->progressive !== null)
                    ? $this->getRequest()->progressive : SLIRConfig::$defaultProgressiveJPEG;
                $this->rendered->background   = null;
            } else if ($this->getSource()->isPNG()) {
                $this->rendered->progressive  = false;
            } else if ($this->getSource()->isGIF() || $this->getSource()->isBMP()) {
                // We convert GIFs and BMPs to PNGs
                $this->rendered->mime     = 'image/png';
                $this->rendered->progressive  = false;
            } else {
                throw new \RuntimeException("Unable to determine type of source image ({$this->getSource()->mime})");
            } // if

            if ($this->isBackgroundFillOn()) {
                $this->rendered->background = $this->getRequest()->background;
            }
            */
        }

        return $this->rendered;
    }

    /**
     * Checks to see if the request cache should be used
     *
     * @since 2.0
     * @return boolean
     */
    private function shouldUseRequestCache()
    {
        // The request cache can't be used if the request is falling back to the
        // default image path because it will prevent the actual image from being
        // shown if it eventually ends up on the server
        if (SLIRConfig::$enableRequestCache === true && !$this->getRequest()->isUsingDefaultImagePath()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Disables E_STRICT and E_NOTICE error reporting
     *
     * @since 2.0
     * @return integer
     */
    private function disableStrictErrorReporting()
    {
        return error_reporting(error_reporting() & ~E_STRICT & ~E_NOTICE);
    }

    /**
     * Escapes from output buffering.
     *
     * @since 2.0
     * @return void
     */
    final public function escapeOutputBuffering()
    {
        while ($level = ob_get_level()) {
            ob_end_clean();

            if ($level == ob_get_level()) {
                // On some setups, ob_get_level() will return a 1 instead of a 0 when there are no more buffers
                return;
            }
        }
    }

    /**
     * Determines if the garbage collector should run for this request.
     *
     * @since 2.0
     * @return boolean
     */
    private function garbageCollectionShouldRun()
    {
        if (rand(1, SLIRConfig::$garbageCollectDivisor) <= SLIRConfig::$garbageCollectProbability) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks to see if the garbage collector should be initialized, and if it should, initializes it.
     *
     * @since 2.0
     * @return void
     */
    private function initializeGarbageCollection()
    {
        if ($this->garbageCollectionShouldRun()) {
            // Register this as a shutdown function so the additional processing time
            // will not affect the speed of the request
            register_shutdown_function(array($this, 'collectGarbage'));
        }
    }

    /**
     * @return void
     * @since 2.0
     */
    public function collectGarbage()
    {
        // Shut down the connection so the user can go about his or her business
        $this->header('Connection: close');
        ignore_user_abort(true);
        flush();

        $garbageCollector = new SLIRGarbageCollector(array(
            $this->getRequestCacheDir() => false,
            $this->getRenderedCacheDir() => true,
        ));
    }

    /**
     * Includes the configuration file.
     *
     * If the configuration file cannot be included, this will throw an error that will hopefully explain what needs to be done.
     *
     * @since 2.0
     * @return void
     */
    final public function getConfig()
    {
        new SLIRConfig;
    }

    /**
     * @param string $path
     * @return string
     * @since 2.0
     */
    final public function resolveRelativePath($path)
    {
        $path = __DIR__ . '/' . $path;

        while (strstr($path, '../')) {
            $path = preg_replace('/\w+\/\.\.\//', '', $path);
        }

        return $path;
    }

    /**
     * Renders requested changes to the image
     *
     * @since 2.0
     * @return void
     */
    private function render()
    {
        ini_set('memory_limit', SLIRConfig::$maxMemoryToAllocate . 'M');
        $this->copySourceToRendered();
        $this->getSource()->destroy();
        $this->getRendered()->applyTransformations();
    }

    /**
     * Copies the source image to the rendered image, resizing (resampling) it if resizing is requested
     *
     * @since 2.0
     * @return void
     */
    private function copySourceToRendered()
    {
        // Set up the background. If there is a color fill, it needs to happen
        // before copying the image over.
        $this->getRendered()->background();

        // Resample the original image into the resized canvas we set up earlier
        if ($this->getSource()->getWidth() !== $this->getRendered()->getWidth() || 
                $this->getSource()->getHeight() != $this->getRendered()->getHeight()) {

            $this->getSource()->resample($this->getRendered());
        } else {
            // No resizing is needed, so make a clean copy
            $this->getSource()->copy($this->getRendered());
        } // if
    }

    /**
     * Calculates how much to sharpen the image based on the difference in dimensions of the source image and the rendered image
     *
     * @since 2.0
     * @return integer Sharpness factor
     */
    private function calculateSharpnessFactor()
    {
        return $this->calculateASharpnessFactor($this->getSource()->getArea(), $this->getRendered()->getArea());
    }

    /**
     * Calculates sharpness factor to be used to sharpen an image based on the
     * area of the source image and the area of the destination image
     *
     * @since 2.0
     * @author Ryan Rud
     * @link http://adryrun.com
     *
     * @param integer $sourceArea Area of source image
     * @param integer $destinationArea Area of destination image
     * @return integer Sharpness factor
     */
    private function calculateASharpnessFactor($sourceArea, $destinationArea)
    {
        $final  = sqrt($destinationArea) * (750.0 / sqrt($sourceArea));
        $a      = 52;
        $b      = -0.27810650887573124;
        $c      = .00047337278106508946;

        $result = $a + $b * $final + $c * $final * $final;

        return max(round($result), 0);
    }

    /**
     * Copies IPTC data from the source image to the cached file
     *
     * @since 2.0
     * @param string $cacheFilePath
     * @return boolean
     */
    private function copyIPTC($cacheFilePath)
    {
        $data = '';

        $iptc = $this->getSource()->iptc;

        // Originating program
        $iptc['2#065']  = array('Smart Lencioni Image Resizer');

        // Program version
        $iptc['2#070']  = array(SLIR::VERSION);

        foreach ($iptc as $tag => $iptcData) {
            $tag  = substr($tag, 2);
            $data .= $this->makeIPTCTag(2, $tag, $iptcData[0]);
        }

        // Embed the IPTC data
        return iptcembed($data, $cacheFilePath);
    }

    /**
     * @since 2.0
     * @author Thies C. Arntzen
     */
    private function makeIPTCTag($rec, $data, $value)
    {
        $length = strlen($value);
        $retval = chr(0x1C) . chr($rec) . chr($data);

        if ($length < 0x8000) {
            $retval .= chr($length >> 8) .  chr($length & 0xFF);
        } else {
            $retval .= chr(0x80) .
             chr(0x04) .
             chr(($length >> 24) & 0xFF) .
             chr(($length >> 16) & 0xFF) .
             chr(($length >> 8) & 0xFF) .
             chr($length & 0xFF);
        }

        return $retval . $value;
    }

    /**
     * Checks parameters against the image's attributes and determines whether
     * anything needs to be changed or if we simply need to serve up the source
     * image
     *
     * @since 2.0
     * @return boolean
     * @todo Add check for JPEGs and progressiveness
     */
    private function isSourceImageDesired()
    {
        if (strpos($this->getMimeType(), 'svg') !== false) {
            return true;
        } else {
            if ($this->isWidthDifferent() || 
                    $this->isHeightDifferent() || 
                    $this->isBackgroundFillOn() || 
                    $this->isQualityOn() || 
                    $this->isCroppingNeeded()) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Determines if the requested width is different than the width of the source image
     *
     * @since 2.0
     * @return boolean
     */
    private function isWidthDifferent()
    {
        if ($this->getRequest()->width !== null && $this->getRequest()->width < $this->getSource()->getWidth()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if the requested height is different than the height of the source image
     *
     * @since 2.0
     * @return boolean
     */
    private function isHeightDifferent()
    {
        if ($this->getRequest()->height !== null && $this->getRequest()->height < $this->getSource()->getHeight()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if a background fill has been requested and if the image is able to have transparency (not for JPEG files)
     *
     * @since 2.0
     * @return boolean
     */
    private function isBackgroundFillOn()
    {
        if ($this->getRequest()->isBackground() && $this->getSource()->isAbleToHaveTransparency()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if the user included image quality in the request
     *
     * @since 2.0
     * @return boolean
     */
    private function isQualityOn()
    {
        if ($this->getQuality() < 100) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if the image should be cropped based on the requested crop ratio and the width:height ratio of the source image
     *
     * @since 2.0
     * @return boolean
     */
    private function isCroppingNeeded()
    {
        if ($this->getRequest()->isCropping() && $this->getRequest()->cropRatio['ratio'] != $this->getSource()->getRatio()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine the quality to use when rendering the image
     * @return integer
     * @since 2.0
     */
    private function getQuality()
    {
        if ($this->getRequest()->quality !== null) {
            return $this->getRequest()->quality;
        } else {
            return SLIRConfig::$defaultQuality;
        }
    }

    /**
     * Determine whether the rendered image should be progressive or not
     * @return boolean
     * @since 2.0
     */
    private function getProgressive()
    {
        if ($this->getSource()->isJPEG()) {
            return ($this->getRequest()->progressive !== null)
                ? $this->getRequest()->progressive
                : SLIRConfig::$defaultProgressiveJPEG;
        } else {
            return false;
        }
    }

    /**
     * Get the mime type that we want to render as
     * @return string
     * @since 2.0
     */
    private function getMimeType()
    {
        if ($this->getSource()->isBMP()) {
            // We convert BMPs to PNGs for the time being
            return 'image/png';
        } else {
            return $this->getSource()->getMimeType();
        }
    }

    /**
     * @return string
     * @since 2.0
     */
    private function getBackground()
    {
        if ($this->isBackgroundFillOn()) {
            return $this->getRequest()->background;
        } else {
            return false;
        }
    }

    /**
     * Detemrines if the image should be resized based on its width (i.e. the width is the constraining dimension for this request)
     *
     * @since 2.0
     * @return boolean
     */
    private function shouldResizeBasedOnWidth()
    {
        if (floor($this->resizeWidthFactor() * $this->getSource()->getHeight()) <= $this->getRequest()->height) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Detemrines if the image should be resized based on its height (i.e. the height is the constraining dimension for this request)
     * @since 2.0
     * @return boolean
     */
    private function shouldResizeBasedOnHeight()
    {
        if (floor($this->resizeHeightFactor() * $this->getSource()->getWidth()) <= $this->getRequest()->width) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 2.0
     * @return float
     */
    private function resizeWidthFactor()
    {
        if ($this->getSource()->getCropWidth() !== 0) {
            return $this->resizeCroppedWidthFactor();
        } else {
            return $this->resizeUncroppedWidthFactor();
        }
    }

    /**
     * @since 2.0
     * @return float
     */
    private function resizeUncroppedWidthFactor()
    {
        return $this->getRequest()->width / $this->getSource()->getWidth();
    }

    /**
     * @since 2.0
     * @return float
     */
    private function resizeCroppedWidthFactor()
    {
        if ($this->getSource()->getCropWidth() === 0) {
            return false;
        } else {
            return $this->getRequest()->width / $this->getSource()->getCropWidth();
        }
    }

    /**
     * @since 2.0
     * @return float
     */
    private function resizeHeightFactor()
    {
        if ($this->getSource()->getCropHeight() !== 0) {
            return $this->resizeCroppedHeightFactor();
        } else {
            return $this->resizeUncroppedHeightFactor();
        }
    }

    /**
     * @since 2.0
     * @return float
     */
    private function resizeUncroppedHeightFactor()
    {
        return $this->getRequest()->height / $this->getSource()->getHeight();
    }

    /**
     * @since 2.0
     * @return float
     */
    private function resizeCroppedHeightFactor()
    {
        if ($this->getSource()->getCropHeight() === 0) {
            return false;
        } else {
            return $this->getRequest()->height / $this->getSource()->getCropHeight();
        }
    }

    /**
     * Determines if the rendered file is in the rendered cache
     *
     * @since 2.0
     * @return boolean
     */
    public function isRenderedCached()
    {
        return $this->isCached($this->renderedCacheFilePath());
    }

    /**
     * Determines if the request is symlinked to the rendered file
     *
     * @since 2.0
     * @return boolean
     */
    public function isRequestCached()
    {
        return $this->isCached($this->requestCacheFilePath());
    }

    /**
     * Determines if a given file exists in the cache
     *
     * @since 2.0
     * @param string $cacheFilePath
     * @return boolean
     */
    private function isCached($cacheFilePath)
    {
        if (!file_exists($cacheFilePath)) {
            return false;
        }

        $cacheModified  = filemtime($cacheFilePath);

        if (!$cacheModified) {
            return false;
        }

        $imageModified  = filectime($this->getRequest()->fullPath());

        if ($imageModified >= $cacheModified) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @since 2.0
     * @return string
     */
    private function getRenderedCacheDir()
    {
        return SLIRConfig::$pathToCacheDir . '/rendered';
    }

    /**
     * @since 2.0
     * @return string
     */
    private function renderedCacheFilePath()
    {
        return $this->getRenderedCacheDir() . $this->renderedCacheFilename();
    }

    /**
     * @since 2.0
     * @return string
     */
    private function renderedCacheFilename()
    {
        return '/' . $this->getRendered()->getHash();
    }

    /**
     * @since 2.0
     * @return string
     */
    private function getHTTPHost()
    {
        if ($this->isCLI()) {
            return 'CLI';
        } else if (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        } else {
            return '';
        }
    }

    /**
     * @since 2.0
     * @return string
     */
    private function requestCacheFilename()
    {
        return '/' . hash('md4', $this->getHTTPHost() . '/' . $this->requestURI(array('imgtoken')) . '/' . SLIRConfig::$defaultCropper);
    }

    /**
     * @since 2.0
     * @param string|array $remove Query string param(s) to remove (e.g. security token)
     * @return string
     */
    private function requestURI($remove = array())
    {
        if (SLIRConfig::$forceQueryString === true) {
            $url = $_SERVER['SCRIPT_NAME'] . '?' . http_build_query($_GET);
        } else {
            $url = $_SERVER['REQUEST_URI'];
        }

        $parsedUrl = parse_url($url);
        $query = array();
        $remove = is_array($remove) ? $remove : (array)$remove;

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);

            foreach ($remove as $varname) {
                unset($query[$varname]);
            }
        }

        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query = !empty($query) ? '?'. http_build_query($query) : '';

        return (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '') .
            (isset($parsedUrl['host']) ? $parsedUrl['host'] : ''). $path. $query;
    }

    /**
     * @since 2.0
     * @return string
     */
    private function getRequestCacheDir()
    {
        return SLIRConfig::$pathToCacheDir . '/request';
    }

    /**
     * @since 2.0
     * @return string
     */
    private function requestCacheFilePath()
    {
        return $this->getRequestCacheDir() . $this->requestCacheFilename();
    }

    /**
     * Write an image to the cache
     *
     * @since 2.0
     * @return boolean
     */
    private function cache()
    {
        $this->cacheRendered();

        if ($this->shouldUseRequestCache()) {
            return $this->cacheRequest($this->getRendered()->getData(), true);
        } else {
            return true;
        }
    }

    /**
     * Write an image to the cache based on the properties of the rendered image
     *
     * @since 2.0
     * @return boolean
     */
    private function cacheRendered()
    {
        $this->cacheFile(
                $this->renderedCacheFilePath(),
                $this->getRendered()->getData(),
                true
        );

        return true;
    }

    /**
     * Write an image to the cache based on the request URI
     *
     * @since 2.0
     * @param string $imageData
     * @param boolean $copyEXIF
     * @return string
     */
    private function cacheRequest($imageData, $copyEXIF = true)
    {
        return $this->cacheFile(
                $this->requestCacheFilePath(),
                $imageData,
                $copyEXIF,
                $this->renderedCacheFilePath()
        );
    }

    /**
     * Write an image to the cache based on the properties of the rendered image
     *
     * @since 2.0
     * @param string $cacheFilePath
     * @param string $imageData
     * @param boolean $copyEXIF
     * @param string $symlinkToPath
     * @return string|boolean
     */
    private function cacheFile($cacheFilePath, $imageData, $copyEXIF = true, $symlinkToPath = null)
    {
        $this->initializeCache();

        // Try to create just a symlink to minimize disk space
        if ($symlinkToPath && function_exists('symlink') && (file_exists($cacheFilePath) || symlink($symlinkToPath, $cacheFilePath))) {
            return true;
        }

        // Create the file
        if (!file_put_contents($cacheFilePath, $imageData)) {
            return false;
        }

        if (SLIRConfig::$copyEXIF == true && $copyEXIF && $this->getSource()->isJPEG()) {
            // Copy IPTC data
            if (isset($this->getSource()->iptc) && !$this->copyIPTC($cacheFilePath)) {
                return false;
            }

            // Copy EXIF data
            $imageData = $this->copyEXIF($cacheFilePath);
        }

        if ($this->getSource()->isJPEG()) {
            // Copy ICC Profile (color profile)
            $imageData = $this->copyICCProfile($cacheFilePath);
        }

        return $imageData;
    }

    /**
     * @since 2.0
     * @return SLIR
     */
    public function uncacheRendered()
    {
        if (file_exists($this->renderedCacheFilePath())) {
            unlink($this->renderedCacheFilePath());
        }
        return $this;
    }

    /**
     * @since 2.0
     * @return SLIR
     */
    public function uncacheRequest()
    {
        if (file_exists($this->requestCacheFilePath())) {
            unlink($this->requestCacheFilePath());
        }
        return $this;
    }

    /**
     * Removes an image from the cache
     *
     * @since 2.0
     * @return SLIR
     */
    public function uncache()
    {
        return $this->uncacheRequest()->uncacheRendered();
    }

    /**
     * Copy the source image's EXIF information to the new file in the cache
     *
     * @since 2.0
     * @uses PEL
     * @param string $cacheFilePath
     * @return mixed string contents of image on success, false on failure
     */
    private function copyEXIF($cacheFilePath)
    {
        $pelJpegLib = dirname(__FILE__) . '/../pel/src/PelJpeg.php';

        // Linking to pel library will break MIT license
        // Make the EXIF copy optional
        if (file_exists($pelJpegLib)) {
            // Make sure to suppress strict warning thrown by PEL
            require_once($pelJpegLib);

            $jpeg   = new PelJpeg($this->getSource()->getFullPath());
            $exif   = $jpeg->getExif();

            if ($exif !== null) {
                $jpeg   = new PelJpeg($cacheFilePath);
                $jpeg->setExif($exif);
                $imageData  = $jpeg->getBytes();

                if (!file_put_contents($cacheFilePath, $imageData)) {
                    return false;
                }

                return $imageData;
            }
        }

        return file_get_contents($cacheFilePath);
    }

    /**
     * Copy the source images' ICC Profile (color profile) to the new file in the cache
     *
     * @since 2.0
     * @uses PHP JPEG ICC profile manipulator
     * @param string $cacheFilePath
     * @return string contents of image
     *
     * @link http://jpeg-icc.sourceforge.net/
     * @link http://sourceforge.net/projects/jpeg-icc/
     */
    private function copyICCProfile($cacheFilePath)
    {
        try {
            $o = new JPEG_ICC();
            $o->LoadFromJPEG($this->getSource()->getFullPath());
            $o->SaveToJPEG($cacheFilePath);
        } catch (\Exception $e) {
        }

        return file_get_contents($cacheFilePath);
    }

    /**
     * Makes sure the cache directory exists, is readable, and is writable
     *
     * @since 2.0
     * @return boolean
     */
    private function initializeCache()
    {
        if ($this->isCacheInitialized) {
            return true;
        }

        $this->initializeDirectory(SLIRConfig::$pathToCacheDir);
        $this->initializeDirectory(SLIRConfig::$pathToCacheDir . '/rendered', false);
        $this->initializeDirectory(SLIRConfig::$pathToCacheDir . '/request', false);

        $this->isCacheInitialized = true;
        return true;
    }

    /**
     * Determines if SLIR is being run from a command line interface.
     *
     * @since 2.0
     * @return boolean
     */
    private function isCLI()
    {
        return (PHP_SAPI === 'cli');
    }

    /**
     * @since 2.0
     * @param string $header
     * @return SLIR
     */
    private function header($header)
    {
        $this->headers[] = $header;

        if (!$this->isCLI() && !headers_sent()) {
            header($header);
        }

        return $this;
    }

    /**
     * @since 2.0
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @since 2.0
     * @param string $path Directory to initialize
     * @param boolean $verifyReadWriteability
     * @return boolean
     */
    private function initializeDirectory($path, $verifyReadWriteability = true, $test = false)
    {
        if (!file_exists($path)) {
            if (!@mkdir($path, 0755, true)) {
                $this->header('HTTP/1.1 500 Internal Server Error');
                throw new \RuntimeException("Directory ($path) does not exist and was unable to be created. Please create the directory.");
            }
        }

        if (!$verifyReadWriteability) {
            return true;
        }

        // Make sure we can read and write the cache directory
        if (!is_readable($path)) {
            $this->header('HTTP/1.1 500 Internal Server Error');
            throw new \RuntimeException("Directory ($path) is not readable");
        } else if (!is_writable($path)) {
            $this->header('HTTP/1.1 500 Internal Server Error');
            throw new \RuntimeException("Directory ($path) is not writable");
        }

        return true;
    }

    /**
     * Serves the unmodified source image
     *
     * @since 2.0
     * @return void
     */
    private function serveSourceImage()
    {
        return $this->serveFile(
                $this->getSource()->getFullPath(),
                null,
                null,
                null,
                $this->getSource()->getMimeType(),
                'source'
        );
    }

    /**
     * Serves the image from the cache based on the properties of the rendered
     * image
     *
     * @since 2.0
     * @return void
     */
    private function serveRenderedCachedImage()
    {
        return $this->serveCachedImage($this->renderedCacheFilePath(), 'rendered');
    }

    /**
     * Serves the image from the cache based on the request URI
     *
     * @since 2.0
     * @return void
     */
    private function serveRequestCachedImage()
    {
        return $this->serveCachedImage($this->requestCacheFilePath(), 'request');
    }

    /**
     * Serves the image from the cache
     *
     * @since 2.0
     * @param string $cacheFilePath
     * @param string $cacheType Can be 'request' or 'image'
     * @return void
     */
    private function serveCachedImage($cacheFilePath, $cacheType)
    {
        // Serve the image
        $this->serveFile(
                $cacheFilePath,
                null,
                null,
                null,
                null,
                "$cacheType cache"
        );

        // If we are serving from the rendered cache, create a symlink in the
        // request cache to the rendered file
        if ($cacheType != 'request') {
            $this->cacheRequest(file_get_contents($cacheFilePath), false);
        }
    }

    /**
     * Determines the mime type of an image
     *
     * @since 2.0
     * @param string $path
     * @return string
     */
    private function mimeType($path)
    {
        $info = txpgetimagesize($path);
        return $info['mime'];
    }

    /**
     * Serves the rendered image
     *
     * @since 2.0
     * @return void
     */
    private function serveRenderedImage()
    {
        // Cache the image
        $this->cache();

        // Serve the file
        $this->serveFile(
                null,
                $this->getRendered()->getData(),
                gmdate('U'),
                $this->getRendered()->getDatasize(),
                $this->getRendered()->getMimeType(),
                'rendered'
        );

        // Clean up memory
        $this->getRendered()->destroy();
    }

    /**
     * Serves a file
     *
     * @since 2.0
     * @param string $imagePath Path to file to serve
     * @param string $data Data of file to serve
     * @param integer $lastModified Timestamp of when the file was last modified
     * @param string $mimeType
     * @param string $slirHeader
     * @return void
     */
    private function serveFile($imagePath, $data, $lastModified, $length, $mimeType, $slirHeader)
    {
        if ($imagePath !== null) {
            if ($lastModified === null) {
                $lastModified = filemtime($imagePath);
            }
            if ($length === null) {
                $length     = filesize($imagePath);
            }
            if ($mimeType === null) {
                $mimeType   = $this->mimeType($imagePath);
            }
        } else if ($length === null) {
            $length   = strlen($data);
        } // if

        // Serve the headers
        $continue = $this->serveHeaders(
                $this->lastModified($lastModified),
                $mimeType,
                $length,
                $slirHeader
        );

        if (!$continue) {
            return;
        }

        if ($data === null) {
            readfile($imagePath);
        } else {
            echo $data;
        }
    }

    /**
     * Serves headers for file for optimal browser caching
     *
     * @since 2.0
     * @param string $lastModified Time when file was last modified in 'D, d M Y H:i:s' format
     * @param string $mimeType
     * @param integer $fileSize
     * @param string $slirHeader
     * @return boolean true to continue, false to stop
     */
    private function serveHeaders($lastModified, $mimeType, $fileSize, $slirHeader)
    {
        $this->header("Last-Modified: $lastModified");
        $this->header("Content-Type: $mimeType");
        $this->header("Content-Length: $fileSize");

        // Lets us easily know whether the image was rendered from scratch,
        // from the cache, or served directly from the source image
        $this->header("X-Content-SLIR: $slirHeader");

        // Keep in browser cache how long?
        $this->header(sprintf('Expires: %s GMT', gmdate('D, d M Y H:i:s', time() + SLIRConfig::$browserCacheTTL)));

        // Public in the Cache-Control lets proxies know that it is okay to
        // cache this content. If this is being served over HTTPS, there may be
        // sensitive content and therefore should probably not be cached by
        // proxy servers.
        $this->header(sprintf('Cache-Control: max-age=%d, public', SLIRConfig::$browserCacheTTL));

        return $this->doConditionalGet($lastModified);

        // The "Connection: close" header allows us to serve the file and let
        // the browser finish processing the script so we can do extra work
        // without making the user wait. This header must come last or the file
        // size will not properly work for images in the browser's cache
        //$this->header('Connection: close');
    }

    /**
     * Converts a UNIX timestamp into the format needed for the Last-Modified
     * header
     *
     * @since 2.0
     * @param integer $timestamp
     * @return string
     */
    private function lastModified($timestamp)
    {
        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }

    /**
     * Checks the to see if the file is different than the browser's cache
     *
     * @since 2.0
     * @param string $lastModified
     * @return boolean true to continue, false to stop
     */
    private function doConditionalGet($lastModified)
    {
        $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ?
            stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) :
            false;

        if (!$ifModifiedSince || $ifModifiedSince <= $lastModified) {
            return true;
        }

        // Nothing has changed since their last request - serve a 304 and exit
        $this->header('HTTP/1.1 304 Not Modified');

        return false;
    }

} // class SLIR

// old pond
// a frog jumps
// the sound of water

// —Matsuo Basho
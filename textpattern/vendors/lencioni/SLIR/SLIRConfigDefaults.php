<?php
/**
 * Configuration file for SLIR (Smart Lencioni Image Resizer)
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
 * SLIR Config Class
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 */
namespace lencioni\SLIR;

class SLIRConfigDefaults
{
  /**
   * Path to default the source image to if the requested image cannot be found.
   *
   * This should match the style of path you would normally pass to SLIR in the URL (not the full path on the filesystem).
   *
   * For example, if your website was http://mysite.com and your document root was /var/www/, and your default image was at http://mysite.com/images/default.png, you would set $defaultImagePath = '/images/default.png';
   *
   * If null, SLIR will throw an exception if the requested image cannot be found.
   *
   * @since 2.0
   * @var string
   */
  public static $defaultImagePath = null;

  /**
   * Default quality setting to use if quality is not specified in the request. Ranges from 0 (worst quality, smaller file) to 100 (best quality, largest filesize).
   *
   * @since 2.0
   * @var integer
   */
  public static $defaultQuality = TEXTPATTERN_THUMB_QUALITY;

  /**
   * Default setting for whether JPEGs should be progressive JPEGs (interlaced) or not.
   *
   * @since 2.0
   * @var boolean
   */
  public static $defaultProgressiveJPEG = true;

  /**
   * How long (in seconds) the web browser should use its cached copy of the image
   * before checking with the server for a new version
   *
   * @since 2.0
   * @var integer
   */
  public static $browserCacheTTL  = 604800; // 7 days = 7 * 24 * 60 * 60

  /**
   * If true, enables the faster, symlink-based request cache as a first-line cache. If false, the request cache is disabled.
   *
   * The request cache seems to have issues on some Windows servers.
   *
   * @since 2.0
   * @var boolean
   */
  public static $enableRequestCache = true;

  /**
   * How much memory (in megabytes) SLIR is allowed to allocate for memory-intensive processes such as rendering and cropping.
   *
   * @since 2.0
   * @var integer
   */
  public static $maxMemoryToAllocate  = 100;

  /**
   * Default crop mode setting to use if crop mode is not specified in the request.
   *
   * Possible values are:
   * SLIR::CROP_CLASS_CENTERED
   * SLIR::CROP_CLASS_TOP_CENTERED
   * SLIR::CROP_CLASS_SMART
   *
   * @since 2.0
   * @var string
   */
  public static $defaultCropper = SLIR::CROP_CLASS_CENTERED;

  /**
   * If true, SLIR will generate and output images from error messages. If false, error messages will be plaintext.
   *
   * @since 2.0
   * @var boolean
   */
  public static $enableErrorImages  = true;

  /**
   * Absolute path to the web root (location of files when visiting http://example.com/) (no trailing slash).
   *
   * For example, if the files for your website are located in /var/www/ on your server, this should be '/var/www'.
   *
   * By default, this is dyanmically determined, so it is set in the init() function and hopefully will not need to be overwritten. However, if SLIR isn't working correctly, it might not be determining your document root correctly and you might need to set this manually in your configuration file.
   *
   * @since 2.0
   * @var string
   */
  public static $documentRoot = null;

  /**
   * Absolute path to SLIR (no trailing slash) from the root directory on your server's filesystem.
   *
   * For example, if the files on your website are in /var/www/ and slir is accessible at http://example.com/slir/, then the value of this setting should be '/var/www/slir'.
   *
   * By default, this is dyanmically determined, so it is set in the init() function and hopefully will not need to be overwritten. However, if SLIR isn't working correctly, it might not be determining the path to SLIR correctly and you might need to set this manually in your configuration file.
   *
   * @since 2.0
   * @var string
   */
  public static $pathToSLIR = null;

  /**
   * Absolute path to cache directory (no trailing slash). This directory must be world-readable, writable by the web server. Ideally, this directory should be located outside of the web tree for security reasons.
   *
   * By default, this is dynamically determined in the init() function and it defaults to /path/to/slir/cache (or $pathToSlir . '/cache') which is the cache directory inside the directory SLIR is located.
   *
   * @var string
   */
  public static $pathToCacheDir = null;

  /**
   * Path to the error log file. Needs to be writable by the web server. Ideally, this should be located outside of the web tree.
   *
   * If not specified, defaults to 'slir-error-log' in the directory SLIR is located.
   *
   * @since 2.0
   * @var string
   */
  public static $pathToErrorLog = null;

  /**
   * If true, forces SLIR to always use the query string for parameters instead of mod_rewrite.
   *
   * @since 2.0
   * @var boolean
   */
  public static $forceQueryString = false;

  /**
   * In conjunction with $garbageCollectDivisor is used to manage probability that the garbage collection routine is started.
   *
   * @since 2.0
   * @var integer
   */
  public static $garbageCollectProbability  = 1;

  /**
   * Coupled with $garbageCollectProbability defines the probability that the garbage collection process is started on every request.
   *
   * The probability is calculated by using $garbageCollectProbability/$garbageCollectDivisor, e.g. 1/100 means there is a 1% chance that the garbage collection process starts on each request.
   *
   * @since 2.0
   * @var integer
   */
  public static $garbageCollectDivisor  = 200;

  /**
   * Specifies the number of seconds after which data will be seen as 'garbage' and potentially cleaned up (deleted from the cache).
   *
   * @since 2.0
   * @var integer
   */
  public static $garbageCollectFileCacheMaxLifetime = 604800; // 7 days = 7 * 24 * 60 * 60

  /**
   * If true, SLIR will copy EXIF information should from the source image to the rendered image.
   *
   * This can be particularly useful (necessary?) if you use an embedded color profile.
   *
   * @since 2.0
   * @var boolean
   */
  public static $copyEXIF = false;

  /**
   * Initialize variables that require some dynamic processing.
   *
   * @since 2.0
   * @return void
   */
  public static function init()
  {
    global $img_dir;

    if (!defined('__DIR__')) {
      define('__DIR__', dirname(__FILE__));
    }

    if (self::$documentRoot === null) {
      self::$documentRoot = $_SERVER['DOCUMENT_ROOT'];
    }

    if (self::$pathToSLIR === null) {
      self::$pathToSLIR = ihu.$img_dir.'/'.TEXTPATTERN_THUMB_DIR;
    }

    if (self::$pathToCacheDir === null) {
      self::$pathToCacheDir = IMPATH . TEXTPATTERN_THUMB_DIR . '/cache';
    }

    if (self::$pathToErrorLog === null) {
      self::$pathToErrorLog = get_pref('tempdir') . '/slir-error-log';
    }
  }

}

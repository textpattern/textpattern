<?php
/**
 * Class definition file for SLIRExceptionHandler
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
 * Exception and error handler
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 */
namespace lencioni\SLIR;

class SLIRExceptionHandler
{
    /**
     * Max number of characters to wrap error message at
     *
     * @since 2.0
     * @var integer
     */
    const WRAP_AT   = 65;

    /**
     * Text size to use in imagestring(). Possible values are 1, 2, 3, 4, or 5
     *
     * @since 2.0
     * @var integer
     */
    const TEXT_SIZE   = 4;

    /**
     * Height of one line of text, in pixels
     *
     * @since 2.0
     * @var integer
     */
    const LINE_HEIGHT = 16;

    /**
     * Width of one character of text, in pixels
     *
     * @since 2.0
     * @var integer
     */
    const CHAR_WIDTH  = 8;

    /**
     * Logs the error to a file
     *
     * @since 2.0
     * @param Exception $e
     * @return boolean
     */
    private static function log(Exception $e)
    {
        $userAgent  = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $referrer   = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
        $request    = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] : '';

        $message = vsprintf("\n[%s] [%s %s] %s\n\nREFERRER: %s\n\nREQUEST: %s\n\n%s", array(
                @gmdate('D M d H:i:s Y'),
                $_SERVER['REMOTE_ADDR'],
                $userAgent,
                $e->getMessage(),
                $referrer,
                $request,
                $e->getTraceAsString(),
        ));

        return @error_log($message, 3, SLIRConfig::$pathToErrorLog);
    }

    /**
     * Create and output an image with an error message
     *
     * @since 2.0
     * @param Exception $e
     */
    private static function errorImage(Exception $e)
    {
        $text = wordwrap($e->getMessage(), self::WRAP_AT);
        $text = explode("\n", $text);

        // determine width
        $characters = 0;
        foreach ($text as $line) {
            if (($temp = strlen($line)) > $characters) {
                $characters = $temp;
            }
        } // foreach

        // set up the image
        $image  = imagecreatetruecolor(
                $characters * self::CHAR_WIDTH,
                count($text) * self::LINE_HEIGHT
        );
        $white  = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        // set text color
        $textColor  = imagecolorallocate($image, 200, 0, 0); // red

        // write the text to the image
        $i  = 0;
        foreach ($text as $line) {
            imagestring(
                    $image,
                    self::TEXT_SIZE,
                    0,
                    $i * self::LINE_HEIGHT,
                    $line,
                    $textColor
            );
            ++$i;
        }

        // output the image
        header('Content-type: image/png');
        imagepng($image);

        // clean up for memory
        if (version_compare(PHP_VERSION, '8.0.0') < 0) {
            imagedestroy($image);
        }
    }

    /**
     * Outputs the error as plain text
     *
     * @since 2.0
     * @param Exception $e
     * @return void
     */
    private static function errorText(Exception $e)
    {
        echo nl2br($e->getMessage() . ' in ' . $e->getFile() . ' on ' . $e->getLine()) . "\n";
    }

    /**
     * Exception handler
     *
     * @since 2.0
     * @param Exception $e
     * @return void
     */
    public static function handleException(Exception $e)
    {
        if (SLIRConfig::$enableErrorImages === true) {
            self::errorImage($e);
        } else {
            self::errorText($e);
        }

        self::log($e);
    }

    /**
     * Error handler
     *
     * Converts all errors into exceptions so they can be handled with the SLIR exception handler
     *
     * @since 2.0
     * @param integer $severity Level of the error raised
     * @param string $message Error message
     * @param string $filename Filename that the error was raised in
     * @param integer $lineno Line number the error was raised at,
     * @param array $context Points to the active symbol table at the point the error occurred
     */
    public static function handleError($severity, $message, $filename = null, $lineno = null, $context = array())
    {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return;
        }

        throw new ErrorException($message, 0, $severity, $filename, $lineno);
    }
}

set_error_handler(array('SLIRExceptionHandler', 'handleError'));
set_exception_handler(array('SLIRExceptionHandler', 'handleException'));
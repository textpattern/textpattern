<?php
/**
 * Class definition file for the centered SLIR cropper
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
 * @subpackage Croppers
 */

namespace lencioni\SLIR\libs\gd\croppers;

use \lencioni\SLIR\libs\gd\croppers\SLIRCropper;

/**
 * Centered SLIR cropper
 *
 * Calculates the crop offset anchored in the center of the image
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 * @subpackage Croppers
 */
class SLIRCropperCentered implements SLIRCropper
{
  /**
   * Determines if the top and bottom need to be cropped
   *
   * @since 2.0
   * @param SLIRImage $image
   * @return boolean
   */
  private function shouldCropTopAndBottom($image)
  {
    if ($image->getCropRatio() > $image->getRatio()) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * @since 2.0
   * @param SLIRImage $image
   * @return integer
   */
  public function getCropY($image)
  {
    return round(($image->getHeight() - $image->getCropHeight()) / 2);
  }

  /**
   * @since 2.0
   * @param SLIRImage $image
   * @return integer
   */
  public function getCropX($image)
  {
    return round(($image->getWidth() - $image->getCropWidth()) / 2);
  }

  /**
   * Calculates the crop offset anchored in the center of the image
   *
   * @since 2.0
   * @param SLIRImage $image
   * @return array Associative array with the keys of x and y that specify the top left corner of the box that should be cropped
   */
  public function getCrop($image)
  {
    // Determine crop offset
    $crop = array(
      'x' => 0,
      'y' => 0,
    );

    if ($this->shouldCropTopAndBottom($image)) {
      // Image is too tall so we will crop the top and bottom
      $crop['y']  = $this->getCropY($image);
    } else {
      // Image is too wide so we will crop off the left and right sides
      $crop['x']  = $this->getCropX($image);
    }

    return $crop;
  }
}

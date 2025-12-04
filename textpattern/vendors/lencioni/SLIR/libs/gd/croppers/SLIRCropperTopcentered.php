<?php
/**
 * Class definition file for the top/centered SLIR cropper
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

/**
 * Top/centered SLIR cropper
 *
 * Calculates the crop offset anchored in the top of the image if the top and bottom are being cropped, or the center of the image if the left and right are being cropped
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 * @subpackage Croppers
 */
class SLIRCropperTopcentered extends SLIRCropperCentered
{
  /**
   * @since 2.0
   * @param SLIRImage $image
   * @return integer
   */
  public function getCropY(SLIRImage $image)
  {
    return 0;
  }
}

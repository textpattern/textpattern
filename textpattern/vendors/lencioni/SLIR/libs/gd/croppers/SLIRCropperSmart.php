<?php
/**
 * Class definition file for the smart SLIR cropper
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
use \lencioni\SLIR\libs\gd\croppers\SLIRImage;
use \lencioni\SLIR\libs\gd\croppers\SLIRGDImage;

/**
 * Smart SLIR cropper
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 * @subpackage Croppers
 */
class SLIRCropperSmart implements SLIRCropper
{
  const OFFSET_NEAR = 0;
  const OFFSET_FAR  = 1;

  const PIXEL_LAB             = 0;
  const PIXEL_DELTA_E         = 1;
  const PIXEL_INTERESTINGNESS = 2;

  const RGB_RED   = 0;
  const RGB_GREEN = 1;
  const RGB_BLUE  = 2;

  const XYZ_X = 0;
  const XYZ_Y = 1;
  const XYZ_Z = 2;

  const LAB_L = 0;
  const LAB_A = 1;
  const LAB_B = 2;

  /**
   * @var array
   */
  private $colors;

  /**
   * Destruct method. Try to clean up memory a little.
   *
   * @return void
   * @since 2.0
   */
  public function __destruct()
  {
    unset($this->colors);
  }

  /**
   * Determines if the top and bottom need to be cropped
   *
   * @since 2.0
   * @param SLIRImage $image
   * @return boolean
   */
  private function shouldCropTopAndBottom(SLIRImage $image)
  {
    if ($image->getCropRatio() > $image->getRatio()) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determines the optimal number of rows in from the top or left to crop
   * the source image
   *
   * @since 2.0
   * @param SLIRImage $image
   * @return integer|boolean
   */
  private function cropSmartOffsetRows(SLIRImage $image)
  {
    // @todo Change this method to resize image, determine offset, and then extrapolate the actual offset based on the image size difference. Then we can cache the offset in APC (all just like we are doing for face detection)

    if ($this->shouldCropTopAndBottom($image)) {
      $length           = $image->getCropHeight();
      $lengthB          = $image->getCropWidth();
      $originalLength   = $image->getHeight();
    } else {
      $length           = $image->getCropWidth();
      $lengthB          = $image->getCropHeight();
      $originalLength   = $image->getWidth();
    }

    // To smart crop an image, we need to calculate the difference between
    // each pixel in each row and its adjacent pixels. Add these up to
    // determine how interesting each row is. Based on how interesting each
    // row is, we can determine whether or not to discard it. We start with
    // the closest row and the farthest row and then move on from there.

    // All colors in the image will be stored in the colors array.
    // This array will also include information about each pixel's
    // interestingness.
    //
    // For example (rough representation):
    //
    // $this->colors = array(
    //   x1 => array(
    //    x1y1  => array(
    //      self::PIXEL_LAB => array(l, a, b),
    //      self::PIXEL_DELTA_E => array(TL, TC, TR, LC, LR, BL, BC, BR),
    //      self::PIXEL_INTERESTINGNESS   => computedInterestingness
    //    ),
    //    x1y2  => array( ... ),
    //    ...
    //   ),
    //   x2 => array( ... ),
    //   ...
    // );
    $this->colors = array();

    // Offset will remember how far in from each side we are in the
    // cropping game
    $offset = array(
      self::OFFSET_NEAR => 0,
      self::OFFSET_FAR  => 0,
    );

    $rowsToCrop = $originalLength - $length;

    // $pixelStep will sacrifice accuracy for memory and speed. Essentially
    // it acts as a spot-checker and scales with the size of the cropped area
    $pixelStep  = round(sqrt($rowsToCrop * $lengthB) / 10);

    // We won't save much speed if the pixelStep is between 4 and 1 because
    // we still need to sample adjacent pixels
    if ($pixelStep < 4) {
      $pixelStep = 1;
    }

    $tolerance  = 0.5;
    $upperTol   = 1 + $tolerance;
    $lowerTol   = 1 / $upperTol;

    // Fight the near and far rows. The stronger will remain standing.
    $returningChampion  = null;
    $ratio              = 1;

    for ($rowsCropped = 0; $rowsCropped < $rowsToCrop; ++$rowsCropped) {
      $a  = $this->rowInterestingness($image, $offset[self::OFFSET_NEAR], $pixelStep, $originalLength);
      $b  = $this->rowInterestingness($image, $originalLength - $offset[self::OFFSET_FAR] - 1, $pixelStep, $originalLength);

      if ($a == 0 && $b == 0) {
        $ratio = 1;
      } else if ($b == 0) {
        $ratio = 1 + $a;
      } else {
        $ratio  = $a / $b;
      }

      if ($ratio > $upperTol) {
        ++$offset[self::OFFSET_FAR];

        // Fightback. Winning side gets to go backwards through fallen rows
        // to see if they are stronger
        if ($returningChampion == self::OFFSET_NEAR) {
          $offset[self::OFFSET_NEAR]  -= ($offset[self::OFFSET_NEAR] > 0) ? 1 : 0;
        } else {
          $returningChampion  = self::OFFSET_NEAR;
        }
      } else if ($ratio < $lowerTol) {
        ++$offset[self::OFFSET_NEAR];

        if ($returningChampion == self::OFFSET_FAR) {
          $offset[self::OFFSET_FAR] -= ($offset[self::OFFSET_FAR] > 0) ? 1 : 0;
        } else {
          $returningChampion  = self::OFFSET_FAR;
        }
      } else {
        // There is no strong winner, so discard rows from the side that
        // has lost the fewest so far. Essentially this is a draw.
        if ($offset[self::OFFSET_NEAR] > $offset[self::OFFSET_FAR]) {
          ++$offset[self::OFFSET_FAR];
        } else {
          // Discard near
          ++$offset[self::OFFSET_NEAR];
        }

        // No fightback for draws
        $returningChampion  = null;
      } // if

    } // for

    // Bounceback for potentially important details on the edge.
    // This may possibly be better if the winning side fights a hard final
    // push multiple-rows-at-stake battle where it stands the chance to gain
    // ground.
    if ($ratio > (1 + ($tolerance * 1.25))) {
      $offset[self::OFFSET_NEAR] -= round($length * .03);
    } else if ($ratio < (1 / (1 + ($tolerance * 1.25)))) {
      $offset[self::OFFSET_NEAR]  += round($length * .03);
    }

    return min($rowsToCrop, max(0, $offset[self::OFFSET_NEAR]));
  }

  /**
   * Calculate the interestingness value of a row of pixels
   *
   * @since 2.0
   * @param SLIRImage $image
   * @param integer $row
   * @param integer $pixelStep Number of pixels to jump after each step when comparing interestingness
   * @param integer $originalLength Number of rows in the original image
   * @return float
   */
  private function rowInterestingness(SLIRImage $image, $row, $pixelStep, $originalLength)
  {
    $interestingness  = 0;
    $max              = 0;

    if ($this->shouldCropTopAndBottom($image)) {
      for ($totalPixels = 0; $totalPixels < $image->getWidth(); $totalPixels += $pixelStep) {
        $i  = $this->pixelInterestingness($image, $totalPixels, $row);

        // Content at the very edge of an image tends to be less interesting than
        // content toward the center, so we give it a little extra push away from the edge
        //$i          += min($row, $originalLength - $row, $originalLength * .04);

        $max              = max($i, $max);
        $interestingness  += $i;
      }
    } else {
      for ($totalPixels = 0; $totalPixels < $image->getHeight(); $totalPixels += $pixelStep) {
        $i  = $this->pixelInterestingness($image, $row, $totalPixels);

        // Content at the very edge of an image tends to be less interesting than
        // content toward the center, so we give it a little extra push away from the edge
        //$i          += min($row, $originalLength - $row, $originalLength * .04);

        $max              = max($i, $max);
        $interestingness  += $i;
      }
    }

    return $interestingness + (($max - ($interestingness / ($totalPixels / $pixelStep))) * ($totalPixels / $pixelStep));
  }

  /**
   * Get the interestingness value of a pixel
   *
   * @since 2.0
   * @param SLIRImage $image
   * @param integer $x x-axis position of pixel to calculate
   * @param integer $y y-axis position of pixel to calculate
   * @return float
   */
  private function pixelInterestingness(SLIRImage $image, $x, $y)
  {
    if (!isset($this->colors[$x][$y][self::PIXEL_INTERESTINGNESS])) {
      // Ensure this pixel's color information has already been loaded
      $this->loadPixelInfo($image, $x, $y);

      // Calculate each neighboring pixel's Delta E in relation to this
      // pixel
      $this->calculateDeltas($image, $x, $y);

      // Calculate the interestingness of this pixel based on neighboring
      // pixels' Delta E in relation to this pixel
      $this->calculateInterestingness($x, $y);
    } // if

    return $this->colors[$x][$y][self::PIXEL_INTERESTINGNESS];
  }

  /**
   * Load the color information of the requested pixel into the $colors array
   *
   * @since 2.0
   * @param SLIRImage $image
   * @param integer $x x-axis position of pixel to calculate
   * @param integer $y y-axis position of pixel to calculate
   * @return boolean
   */
  private function loadPixelInfo(SLIRImage $image, $x, $y)
  {
    if ($x < 0 || $x >= $image->getWidth() || $y < 0 || $y >= $image->getHeight()) {
      return false;
    }

    if (!isset($this->colors[$x])) {
      $this->colors[$x] = array();
    }

    if (!isset($this->colors[$x][$y])) {
      $this->colors[$x][$y] = array();
    }

    if (!isset($this->colors[$x][$y][self::PIXEL_INTERESTINGNESS]) && !isset($this->colors[$x][$y][self::PIXEL_LAB])) {
      $this->colors[$x][$y][self::PIXEL_LAB]  = $this->evaluateColor(imagecolorat($image->getImage(), $x, $y));
    }

    return true;
  }

  /**
   * Calculates each adjacent pixel's Delta E in relation to the pixel requested
   *
   * @since 2.0
   * @param SLIRImage $image
   * @param integer $x x-axis position of pixel to calculate
   * @param integer $y y-axis position of pixel to calculate
   * @return boolean
   */
  private function calculateDeltas(SLIRImage $image, $x, $y)
  {
    // Calculate each adjacent pixel's Delta E in relation to the current
    // pixel (top left, top center, top right, center left, center right,
    // bottom left, bottom center, and bottom right)

    if (!isset($this->colors[$x][$y][self::PIXEL_DELTA_E]['d-1-1'])) {
      $this->calculateDelta($image, $x, $y, -1, -1);
    }
    if (!isset($this->colors[$x][$y][self::PIXEL_DELTA_E]['d0-1'])) {
      $this->calculateDelta($image, $x, $y, 0, -1);
    }
    if (!isset($this->colors[$x][$y][self::PIXEL_DELTA_E]['d1-1'])) {
      $this->calculateDelta($image, $x, $y, 1, -1);
    }
    if (!isset($this->colors[$x][$y][self::PIXEL_DELTA_E]['d-10'])) {
      $this->calculateDelta($image, $x, $y, -1, 0);
    }
    if (!isset($this->colors[$x][$y][self::PIXEL_DELTA_E]['d10'])) {
      $this->calculateDelta($image, $x, $y, 1, 0);
    }
    if (!isset($this->colors[$x][$y][self::PIXEL_DELTA_E]['d-11'])) {
      $this->calculateDelta($image, $x, $y, -1, 1);
    }
    if (!isset($this->colors[$x][$y][self::PIXEL_DELTA_E]['d01'])) {
      $this->calculateDelta($image, $x, $y, 0, 1);
    }
    if (!isset($this->colors[$x][$y][self::PIXEL_DELTA_E]['d11'])) {
      $this->calculateDelta($image, $x, $y, 1, 1);
    }

    return true;
  }

  /**
   * Calculates and stores requested pixel's Delta E in relation to comparison pixel
   *
   * @since 2.0
   * @param SLIRImage $image
   * @param integer $xA x-axis position of pixel to calculate
   * @param integer $yA y-axis position of pixel to calculate
   * @param integer $xMove number of pixels to move on the x-axis to find comparison pixel
   * @param integer $yMove number of pixels to move on the y-axis to find comparison pixel
   * @return boolean
   */
  private function calculateDelta(SLIRImage $image, $xA, $yA, $xMove, $yMove)
  {
    $xB = $xA + $xMove;
    $yB = $yA + $yMove;

    // Pixel is outside of the image, so we cant't calculate the Delta E
    if ($xB < 0 || $xB >= $image->getWidth() || $yB < 0 || $yB >= $image->getHeight()) {
      return null;
    }

    if (!isset($this->colors[$xA][$yA][self::PIXEL_LAB])) {
      $this->loadPixelInfo($image, $xA, $yA);
    }

    if (!isset($this->colors[$xB][$yB][self::PIXEL_LAB])) {
      $this->loadPixelInfo($image, $xB, $yB);
    }

    $delta  = $this->deltaE($this->colors[$xA][$yA][self::PIXEL_LAB], $this->colors[$xB][$yB][self::PIXEL_LAB]);

    $this->colors[$xA][$yA][self::PIXEL_DELTA_E]["d$xMove$yMove"] = $delta;

    $xBMove = $xMove * -1;
    $yBMove = $yMove * -1;
    $this->colors[$xB][$yB][self::PIXEL_DELTA_E]["d$xBMove$yBMove"] =& $this->colors[$xA][$yA][self::PIXEL_DELTA_E]["d$xMove$yMove"];

    return true;
  }

  /**
   * Calculates and stores a pixel's overall interestingness value
   *
   * @since 2.0
   * @param integer $x x-axis position of pixel to calculate
   * @param integer $y y-axis position of pixel to calculate
   * @return boolean
   */
  private function calculateInterestingness($x, $y)
  {
    // The interestingness is the average of the pixel's Delta E values
    $this->colors[$x][$y][self::PIXEL_INTERESTINGNESS]  = array_sum($this->colors[$x][$y][self::PIXEL_DELTA_E])
      / count(array_filter($this->colors[$x][$y][self::PIXEL_DELTA_E], 'is_numeric'));

    return true;
  }

  /**
   * @since 2.0
   * @param integer $int
   * @return array
   */
  private function evaluateColor($int)
  {
    $rgb  = $this->colorIndexToRGB($int);
    $xyz  = $this->RGBtoXYZ($rgb);
    $lab  = $this->XYZtoHunterLab($xyz);

    return $lab;
  }

  /**
   * @since 2.0
   * @param integer $int
   * @return array
   */
  private function colorIndexToRGB($int)
  {
    $a  = (255 - (($int >> 24) & 0xFF)) / 255;
    $r  = (($int >> 16) & 0xFF) * $a;
    $g  = (($int >> 8) & 0xFF) * $a;
    $b  = ($int & 0xFF) * $a;

    return array(
      self::RGB_RED   => $r,
      self::RGB_GREEN => $g,
      self::RGB_BLUE  => $b,
    );
  }

  /**
   * @since 2.0
   * @param array $rgb
   * @return array XYZ
   * @link http://easyrgb.com/index.php?X=MATH&H=02#text2
   */
  private function RGBtoXYZ($rgb)
  {
    $r  = $rgb[self::RGB_RED] / 255;
    $g  = $rgb[self::RGB_GREEN] / 255;
    $b  = $rgb[self::RGB_BLUE] / 255;

    if ($r > 0.04045) {
      $r  = pow((($r + 0.055) / 1.055), 2.4);
    } else {
      $r  = $r / 12.92;
    }

    if ($g > 0.04045) {
      $g  = pow((($g + 0.055) / 1.055), 2.4);
    } else {
      $g  = $g / 12.92;
    }

    if ($b > 0.04045) {
      $b  = pow((($b + 0.055) / 1.055), 2.4);
    } else {
      $b  = $b / 12.92;
    }

    $r  *= 100;
    $g  *= 100;
    $b  *= 100;

    //Observer. = 2°, Illuminant = D65
    return array(
      self::XYZ_X => $r * 0.4124 + $g * 0.3576 + $b * 0.1805,
      self::XYZ_Y => $r * 0.2126 + $g * 0.7152 + $b * 0.0722,
      self::XYZ_Z => $r * 0.0193 + $g * 0.1192 + $b * 0.9505,
    );
  }

  /**
   * @link http://www.easyrgb.com/index.php?X=MATH&H=05#text5
   */
  private function XYZtoHunterLab($xyz)
  {
    if ($xyz[self::XYZ_Y] == 0) {
      return array(
        self::LAB_L => 0,
        self::LAB_A => 0,
        self::LAB_B => 0,
      );
    }

    return array(
      self::LAB_L => 10 * sqrt($xyz[self::XYZ_Y]),
      self::LAB_A => 17.5 * (((1.02 * $xyz[self::XYZ_X]) - $xyz[self::XYZ_Y]) / sqrt($xyz[self::XYZ_Y])),
      self::LAB_B => 7 * (($xyz[self::XYZ_Y] - (0.847 * $xyz[self::XYZ_Z])) / sqrt($xyz[self::XYZ_Y])),
    );
  }

  /**
   * Converts a color from RGB colorspace to CIE-L*ab colorspace
   * @since 2.0
   * @param array $xyz
   * @return array LAB
   * @link http://www.easyrgb.com/index.php?X=MATH&H=05#text5
   */
  private function XYZtoCIELAB($xyz)
  {
    $refX = 100;
    $refY = 100;
    $refZ = 100;

    $x = $xyz[self::XYZ_X] / $refX;
    $y = $xyz[self::XYZ_Y] / $refY;
    $z = $xyz[self::XYZ_Z] / $refZ;

    if ($x > 0.008856) {
      $x = pow($x, 1/3);
    } else {
      $x = (7.787 * $x) + (16 / 116);
    }

    if ($y > 0.008856) {
      $y = pow($y, 1/3);
    } else {
      $y = (7.787 * $y) + (16 / 116);
    }

    if ($z > 0.008856) {
      $z = pow($z, 1/3);
    } else {
      $z = (7.787 * $z) + (16 / 116);
    }

    return array(
      self::LAB_L => (116 * $y) - 16,
      self::LAB_A => 500 * ($x - $y),
      self::LAB_B => 200 * ($y - $z),
    );
  }

  /**
   * @since 2.0
   * @param array $labA LAB color array
   * @param array $labB LAB color array
   * @return float
   */
  private function deltaE($labA, $labB)
  {
    return sqrt(
        (pow($labA[self::LAB_L] - $labB[self::LAB_L], 2))
        + (pow($labA[self::LAB_A] - $labB[self::LAB_A], 2))
        + (pow($labA[self::LAB_B] - $labB[self::LAB_B], 2))
    );
  }

  /**
   * Compute the Delta E 2000 value of two colors in the LAB colorspace
   *
   * @link http://en.wikipedia.org/wiki/Color_difference#CIEDE2000
   * @link http://easyrgb.com/index.php?X=DELT&H=05#text5
   * @since 2.0
   * @param array $labA LAB color array
   * @param array $labB LAB color array
   * @return float
   */
  private function deltaE2000($labA, $labB)
  {
    $weightL  = 1; // Lightness
    $weightC  = 1; // Chroma
    $weightH  = 1; // Hue

    $xCA = sqrt($labA[self::LAB_A] * $labA[self::LAB_A] + $labA[self::LAB_B] * $labA[self::LAB_B]);
    $xCB = sqrt($labB[self::LAB_A] * $labB[self::LAB_A] + $labB[self::LAB_B] * $labB[self::LAB_B]);
    $xCX = ($xCA + $xCB) / 2;
    $xGX = 0.5 * (1 - sqrt((pow($xCX, 7)) / ((pow($xCX, 7)) + (pow(25, 7)))));
    $xNN = (1 + $xGX) * $labA[self::LAB_A];
    $xCA = sqrt($xNN * $xNN + $labA[self::LAB_B] * $labA[self::LAB_B]);
    $xHA = $this->LABtoHue($xNN, $labA[self::LAB_B]);
    $xNN = (1 + $xGX) * $labB[self::LAB_A];
    $xCB = sqrt($xNN * $xNN + $labB[self::LAB_B] * $labB[self::LAB_B]);
    $xHB = $this->LABtoHue($xNN, $labB[self::LAB_B]);
    $xDL = $labB[self::LAB_L] - $labA[self::LAB_L];
    $xDC = $xCB - $xCA;

    if (($xCA * $xCB) == 0) {
       $xDH = 0;
    } else {
      $xNN = round($xHB - $xHA, 12);
      if (abs($xNN) <= 180) {
        $xDH = $xHB - $xHA;
      } else {
        if ($xNN > 180) {
          $xDH = $xHB - $xHA - 360;
        } else {
          $xDH = $xHB - $xHA + 360;
        }
      } // if
    } // if

    $xDH = 2 * sqrt($xCA * $xCB) * sin(rad2deg($xDH / 2));
    $xLX = ($labA[self::LAB_L] + $labB[self::LAB_L]) / 2;
    $xCY = ($xCA + $xCB) / 2;

    if (($xCA *  $xCB) == 0) {
      $xHX = $xHA + $xHB;
    } else {
      $xNN = abs(round($xHA - $xHB, 12));
      if ($xNN >  180) {
        if (($xHB + $xHA) <  360) {
          $xHX = $xHA + $xHB + 360;
        } else {
          $xHX = $xHA + $xHB - 360;
        }
      } else {
        $xHX = $xHA + $xHB;
      } // if
      $xHX /= 2;
    } // if

    $xTX = 1 - 0.17 * cos(rad2deg($xHX - 30))
      + 0.24 * cos(rad2deg(2 * $xHX))
      + 0.32 * cos(rad2deg(3 * $xHX + 6))
      - 0.20 * cos(rad2deg(4 * $xHX - 63));

    $xPH = 30 * exp(- (($xHX  - 275) / 25) * (($xHX  - 275) / 25));
    $xRC = 2 * sqrt((pow($xCY, 7)) / ((pow($xCY, 7)) + (pow(25, 7))));
    $xSL = 1 + ((0.015 * (($xLX - 50) * ($xLX - 50)))
      / sqrt(20 + (($xLX - 50) * ($xLX - 50))));
    $xSC = 1 + 0.045 * $xCY;
    $xSH = 1 + 0.015 * $xCY * $xTX;
    $xRT = - sin(rad2deg(2 * $xPH)) * $xRC;
    $xDL = $xDL / $weightL * $xSL;
    $xDC = $xDC / $weightC * $xSC;
    $xDH = $xDH / $weightH * $xSH;

    $delta  = sqrt(pow($xDL, 2) + pow($xDC, 2) + pow($xDH, 2) + $xRT * $xDC * $xDH);
    return (is_nan($delta)) ? 1 : $delta / 100;
  }

  /**
   * Compute the Delta CMC value of two colors in the LAB colorspace
   *
   * @since 2.0
   * @param array $labA LAB color array
   * @param array $labB LAB color array
   * @return float
   * @link http://easyrgb.com/index.php?X=DELT&H=06#text6
   */
  private function deltaCMC($labA, $labB)
  {
    // if $weightL is 2 and $weightC is 1, it means that the lightness
    // will contribute half as much importance to the delta as the chroma
    $weightL  = 2; // Lightness
    $weightC  = 1; // Chroma

    $xCA  = sqrt((pow($labA[self::LAB_A], 2)) + (pow($labA[self::LAB_B], 2)));
    $xCB  = sqrt((pow($labB[self::LAB_A], 2)) + (pow($labB[self::LAB_B], 2)));
    $xff  = sqrt((pow($xCA, 4)) / ((pow($xCA, 4)) + 1900));
    $xHA  = $this->LABtoHue($labA[self::LAB_A], $labA[self::LAB_B]);

    if ($xHA < 164 || $xHA > 345) {
      $xTT  = 0.36 + abs(0.4 * cos(deg2rad(35 + $xHA)));
    } else {
      $xTT  = 0.56 + abs(0.2 * cos(deg2rad(168 + $xHA)));
    }

    if ($labA[self::LAB_L] < 16) {
      $xSL  = 0.511;
    } else {
      $xSL  = (0.040975 * $labA[self::LAB_L]) / (1 + (0.01765 * $labA[self::LAB_L]));
    }

    $xSC = ((0.0638 * $xCA) / (1 + (0.0131 * $xCA))) + 0.638;
    $xSH = (($xff * $xTT) + 1 - $xff) * $xSC;
    $xDH = sqrt(pow($labB[self::LAB_A] - $labA[self::LAB_A], 2) + pow($labB[self::LAB_B] - $labA[self::LAB_B], 2) - pow($xCB - $xCA, 2));
    $xSL = ($labB[self::LAB_L] - $labA[self::LAB_L]) / $weightL * $xSL;
    $xSC = ($xCB - $xCA) / $weightC * $xSC;
    $xSH = $xDH / $xSH;

    $delta = sqrt(pow($xSL, 2) + pow($xSC, 2) + pow($xSH, 2));
    return (is_nan($delta)) ? 1 : $delta;
  }

  /**
   * @since 2.0
   * @param integer $a
   * @param integer $b
   * @return CIE-H° value
   */
  private function LABtoHue($a, $b)
  {
    $bias = 0;

    if ($a >= 0 && $b == 0) {
      return 0;
    }
    if ($a <  0 && $b == 0) {
      return 180;
    }
    if ($a == 0 && $b >  0) {
      return 90;
    }
    if ($a == 0 && $b <  0) {
      return 270;
    }
    if ($a >  0 && $b >  0) {
      $bias = 0;
    }
    if ($a <  0) {
      $bias = 180;
    }
    if ($a >  0 && $b <  0) {
      $bias = 360;
    }

    return (rad2deg(atan($b / $a)) + $bias);
  }

  /**
   * Calculates the crop offset using an algorithm that tries to determine
   * the most interesting portion of the image to keep.
   *
   * @since 2.0
   * @param SLIRImage $image
   * @return array Associative array with the keys of x and y that specify the top left corner of the box that should be cropped
   */
  public function getCrop(SLIRGDImage $image)
  {
    // Try contrast detection
    $o  = $this->cropSmartOffsetRows($image);

    $crop = array(
      'x' => 0,
      'y' => 0,
    );

    if ($o === false) {
      return true;
    } else if ($this->shouldCropTopAndBottom($image)) {
      $crop['y']  = $o;
    } else {
      $crop['x']  = $o;
    }

    return $crop;
  }

}
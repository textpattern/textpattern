<?php
/**
 * SLIR Coordinate mapping
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

class SLIRCoordinates 
{
  /**
   * @var integer
   */
  private $xa;

  /**
   * @var integer
   */
  private $ya;

  /**
   * @var integer
   */
  private $xb;

  /**
   * @var integer
   */
  private $yb;

  /**
   * @param integer $xa
   * @param integer $ya
   * @param integer $xb
   * @param integer $yb
   */
  public function __construct($xa, $ya, $xb, $yb)
  {
    $this->$xa = $xa;
    $this->$ya = $ya;
    $this->$xb = $xb;
    $this->$yb = $yb;
  }

  /**
   * @return integer
   */
  public function getWidth()
  {
    return abs($this->xb - $this->xa);
  }

  /**
   * @return integer
   */
  public function getHeight()
  {
    return abs($this->yb - $this->ya);
  }
}
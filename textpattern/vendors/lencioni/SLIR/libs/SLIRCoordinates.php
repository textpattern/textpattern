<?php
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
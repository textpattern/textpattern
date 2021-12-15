<?php

/**
 * Tools for creating thumbnails.
 *
 * @package Image
 * @author  C. Erdmann
 * @link    http://www.cerdmann.de/thumb
 * @author  Robert Wetzlmayr
 *
 * Refactored from function.thumb.php by C. Erdmann, which contained
 * the following credit and licensing terms:
 *
 * Smarty plugin "Thumb"
 * Purpose: creates cached thumbnails
 * Home: http://www.cerdmann.com/thumb/
 * Copyright (C) 2005 Christoph Erdmann
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation; either version 2.1 of
 * the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA
 *
 * @author Christoph Erdmann (CE) <smarty@cerdmann.com>
 * @link   http://www.cerdmann.com
 *
 * @author Benjamin Fleckenstein (BF)
 * @link http://www.benjaminfleckenstein.de
 *
 * @author Marcus Gueldenmeister (MG)
 * @link http://www.gueldenmeister.de/marcus/
 *
 * @author Andreas Bösch (AB)
 */

/**
 * Output debug log.
 *
 * @global bool $verbose
 */

$verbose = false;

/**
 * Creates thumbnails for larger images.
 *
 * @package Image
 */

class wet_thumb
{
    /**
     * The width of your thumbnail. The height (if not set) will be
     * automatically calculated.
     *
     * @var int
     */

    public $width;

    /**
     * The height of your thumbnail. The width (if not set) will be
     * automatically calculated.
     *
     * @var int
     */

    public $height;

    /**
     * Set the longest side of the image if width, height and shortside is
     * not set.
     */

    public $longside;

    /**
     * Set the shortest side of the image if width, height and longside is
     * not set.
     */

    public $shortside;

    /**
     * Set to 'false' if your source image is smaller than the calculated
     * thumb and you do not want the image to get extrapolated.
     */

    public $extrapolate;

    /**
     * Crops the image.
     *
     * If set to TRUE, image will be cropped in the center to destination width
     * and height params, while keeping aspect ratio. Otherwise the image will
     * get resized.
     *
     * @var bool
     */

    public $crop;

    /**
     * Applies unsharpen mask.
     *
     * Set to FALSE if you don't want to use the Unsharp-Mask.
     * Thumbnail creation will be faster, but quality is reduced.
     *
     * @var bool
     */

    public $sharpen;

    /**
     * If set to FALSE the image will not have a lens icon.
     *
     * @var bool
     */

    public $hint;

    /**
     * Set to FALSE to get no lightgrey bottom bar.
     *
     * @var bool
     */

    public $addgreytohint;

    /**
     * JPEG image quality (0...100, defaults to 80).
     *
     * @var int
     */

    public $quality;

    /**
     * Set to your target URL (a href="linkurl").
     *
     * @var string
     */

    public $linkurl;

    /**
     * Will be inserted in the image-tag.
     *
     * @var string
     */

    public $html;

    /**
     * An array of accepted image formats.
     *
     * @var array
     */

    public $types = array('', '.gif', '.jpg', '.png');

    /**
     * Source.
     *
     * @var array
     */

    public $_SRC;

    /**
     * Destination.
     *
     * @var array
     */

    public $_DST;

    /**
     * Constructor.
     */

    public function __construct()
    {
        $this->extrapolate = false;
        $this->crop = true;
        $this->sharpen = true;
        $this->hint = true;
        $this->addgreytohint = true;
        $this->quality = 80;
        $this->html = ' alt="" title="" ';
        $this->link = true;
    }

    /**
     * Writes a thumbnail file.
     *
     * @param  string $infile  Image file name.
     * @param  array  $outfile Array of thumb file names (1...n)
     * @return bool TRUE on success
     */

    public function write($infile, $outfile)
    {
        global $verbose;

        if ($verbose) {
            echo "writing thumb nail...";
        }

        // Get source image info.
        if (!($temp = txpimagesize($infile, true)) || empty($temp['image'])) {
            return false;
        }

        $this->_SRC['file']       = $infile;
        $this->_SRC['width']      = $temp[0];
        $this->_SRC['height']     = $temp[1];
        $this->_SRC['type']       = $temp[2]; // 1=GIF, 2=JPEG, 3=PNG, 18=WebP, 19=AVIF.
        $this->_SRC['string']     = $temp[3];
        $this->_SRC['image']      = $temp['image'];
        $this->_SRC['filename']   = basename($infile);
        //$this->_SRC['modified'] = filemtime($infile);
/*
        // Make sure we have enough memory if the image is large.
        if (max($this->_SRC['width'], $this->_SRC['height']) > 1024) {
            $shorthand = array('K', 'M', 'G');
            $tens = array('000', '000000', '000000000'); // A good enough decimal approximation of K, M, and G.

            // Do not *decrease* memory_limit.
            list($ml, $extra) = str_ireplace($shorthand, $tens, array(ini_get('memory_limit'), EXTRA_MEMORY));

            if ($ml < $extra) {
                ini_set('memory_limit', EXTRA_MEMORY);
            }
        }

        // Read source image.
        if ($this->_SRC['type'] == 1) {
            $this->_SRC['image'] = imagecreatefromgif($this->_SRC['file']);
        } elseif ($this->_SRC['type'] == 2) {
            $this->_SRC['image'] = imagecreatefromjpeg($this->_SRC['file']);
        } elseif ($this->_SRC['type'] == 3) {
            $this->_SRC['image'] = imagecreatefrompng($this->_SRC['file']);
        } elseif ($this->_SRC['type'] == 18) {
            $this->_SRC['image'] = imagecreatefromwebp($this->_SRC['file']);
        } elseif ($this->_SRC['type'] == 19) {
            $this->_SRC['image'] = imagecreatefromavif($this->_SRC['file']);
        }
*/
        // Ensure non-zero height/width.
        if (!$this->_SRC['height']) {
            $this->_SRC['height'] = 100;
        }

        if (!$this->_SRC['width']) {
            $this->_SRC['width'] = 100;
        }

        // Check image orientation.
        if ($this->_SRC['width'] >= $this->_SRC['height']) {
            $this->_SRC['format'] = 'landscape';
        } else {
            $this->_SRC['format'] = 'portrait';
        }

        // Get destination image info.
        if (is_numeric($this->width) and empty($this->height)) {
            $this->_DST['width']  = $this->width;
            $this->_DST['height'] = round($this->width/($this->_SRC['width']/$this->_SRC['height']));
        } elseif (is_numeric($this->height) and empty($this->width)) {
            $this->_DST['height'] = $this->height;
            $this->_DST['width']  = round($this->height/($this->_SRC['height']/$this->_SRC['width']));
        } elseif (is_numeric($this->width) and is_numeric($this->height)) {
            $this->_DST['width']  = $this->width;
            $this->_DST['height'] = $this->height;
        } elseif (is_numeric($this->longside) and empty($this->shortside)) {
            // Preserve aspect ratio based on provided height.
            if ($this->_SRC['format'] == 'portrait') {
                $this->_DST['height'] = $this->longside;
                $this->_DST['width']  = round($this->longside/($this->_SRC['height']/$this->_SRC['width']));
            } else {
                $this->_DST['width']  = $this->longside;
                $this->_DST['height'] = round($this->longside/($this->_SRC['width']/$this->_SRC['height']));
            }
        } elseif (is_numeric($this->shortside)) {
            // Preserve aspect ratio based on provided width.
            if ($this->_SRC['format'] == 'portrait') {
                $this->_DST['width']  = $this->shortside;
                $this->_DST['height'] = round($this->shortside/($this->_SRC['width']/$this->_SRC['height']));
            } else {
                $this->_DST['height'] = $this->shortside;
                $this->_DST['width']  = round($this->shortside/($this->_SRC['height']/$this->_SRC['width']));
            }
        } else {
            // Default dimensions.
            $this->width          = 100;
            $this->_DST['width']  = $this->width;
            $this->_DST['height'] = round($this->width/($this->_SRC['width']/$this->_SRC['height']));
        }

        // Don't make the new image larger than the original image.
        if (
            $this->extrapolate === false &&
            $this->_DST['height'] > $this->_SRC['height'] &&
            $this->_DST['width'] > $this->_SRC['width']
        ) {
            $this->_DST['width'] = $this->_SRC['width'];
            $this->_DST['height'] = $this->_SRC['height'];
        }

        $this->_DST['type'] = $this->_SRC['type'];
        $this->_DST['file'] = $outfile;

        // Crop image.
        $off_w = 0;
        $off_h = 0;

        if ($this->crop != false) {
            if ($this->_SRC['height'] < $this->_SRC['width']) {
                $ratio = (double) ($this->_SRC['height'] / $this->_DST['height']);
                $cpyWidth = round($this->_DST['width'] * $ratio);

                if ($cpyWidth > $this->_SRC['width']) {
                    $ratio = (double) ($this->_SRC['width'] / $this->_DST['width']);
                    $cpyWidth = $this->_SRC['width'];
                    $cpyHeight = round($this->_DST['height'] * $ratio);
                    $off_w = 0;
                    $off_h = round(($this->_SRC['height'] - $cpyHeight) / 2);
                    $this->_SRC['height'] = $cpyHeight;
                } else {
                    $cpyHeight = $this->_SRC['height'];
                    $off_w = round(($this->_SRC['width'] - $cpyWidth) / 2);
                    $off_h = 0;
                    $this->_SRC['width'] = $cpyWidth;
                }
            } else {
                $ratio = (double) ($this->_SRC['width'] / $this->_DST['width']);
                $cpyHeight = round($this->_DST['height'] * $ratio);

                if ($cpyHeight > $this->_SRC['height']) {
                    $ratio = (double) ($this->_SRC['height'] / $this->_DST['height']);
                    $cpyHeight = $this->_SRC['height'];
                    $cpyWidth = round($this->_DST['width'] * $ratio);
                    $off_w = round(($this->_SRC['width'] - $cpyWidth) / 2);
                    $off_h = 0;
                    $this->_SRC['width'] = $cpyWidth;
                } else {
                    $cpyWidth = $this->_SRC['width'];
                    $off_w = 0;
                    $off_h = round(($this->_SRC['height'] - $cpyHeight) / 2);
                    $this->_SRC['height'] = $cpyHeight;
                }
            }
        }

        // Create DST.
        $this->_DST['image'] = imagecreatetruecolor($this->_DST['width'], $this->_DST['height']);

        // GIF or PNG destination, set the transparency up.
        if ($this->_DST['type'] == 1 || $this->_DST['type'] == 3 || $this->_DST['type'] == 18) {
            $trans_idx = imagecolortransparent($this->_SRC['image']);
            $pallet_size = imagecolorstotal($this->_SRC['image']);

            // Is there a specific transparent colour?
            if ($trans_idx >= 0 && ($trans_idx < $pallet_size)) {
                $trans_color = imagecolorsforindex($this->_SRC['image'], $trans_idx);
                $trans_idx = imagecolorallocate(
                    $this->_DST['image'],
                    $trans_color['red'],
                    $trans_color['green'],
                    $trans_color['blue']
                );
                imagefill($this->_DST['image'], 0, 0, $trans_idx);
                imagecolortransparent($this->_DST['image'], $trans_idx);
            } elseif ($this->_DST['type'] == 3 || $this->_DST['type'] == 18 || $this->_DST['type'] == 19) {
                imagealphablending($this->_DST['image'], false);
                $transparent = imagecolorallocatealpha($this->_DST['image'], 0, 0, 0, 127);
                imagefill($this->_DST['image'], 0, 0, $transparent);
                imagesavealpha($this->_DST['image'], true);
            }
        }

        imagecopyresampled(
            $this->_DST['image'],
            $this->_SRC['image'],
            0,
            0,
            $off_w,
            $off_h,
            $this->_DST['width'],
            $this->_DST['height'],
            $this->_SRC['width'],
            $this->_SRC['height']
        );

        // avif weirdness
        if ($this->_DST['type'] == 19) {
            imageflip($this->_DST['image'], IMG_FLIP_HORIZONTAL);
        }

        if ($this->sharpen === true) {
            $this->_DST['image'] = UnsharpMask($this->_DST['image'], 80, .5, 3);
        }

        // Finally, the real dimensions.
        $this->height = $this->_DST['height'];
        $this->width = $this->_DST['width'];

        // Add magnifying glass.
        if ($this->hint === true) {
            // Should we really add white bars?
            if ($this->addgreytohint === true) {
                $trans = imagecolorallocatealpha($this->_DST['image'], 255, 255, 255, 25);
                imagefilledrectangle(
                    $this->_DST['image'],
                    0,
                    $this->_DST['height'] - 9,
                    $this->_DST['width'],
                    $this->_DST['height'],
                    $trans
                );
            }

            $magnifier = imagecreatefromstring(gzuncompress(base64_decode("eJzrDPBz5+WS4mJgYOD19HAJAtLcIMzBBiRXrilXA1IsxU6eIRxAUMOR0gHkcxZ4RBYD1QiBMOOlu3V/gIISJa4RJc5FqYklmfl5CiGZuakMBoZ6hkZ6RgYGJs77ex2BalRBaoLz00rKE4tSGXwTk4vyc1NTMhMV3DKLUsvzi7KLFXwjFEAa2svWnGdgYPTydHEMqZhTOsE++1CAyNHzm2NZjgau+dAmXlAwoatQmOld3t/NPxlLMvY7sovPzXHf7re05BPzjpQTMkZTPjm1HlHkv6clYWK43Zt16rcDjdZ/3j2cd7qD4/HHH3GaprFrw0QZDHicORXl2JsPsveVTDz//L3N+WpxJ5Hff+10Tjdd2/Vi17vea79Om5w9zzyne9GLnWGrN8atby/ayXPOsu2w4quvVtxNCVVz5nAf3nDpZckBCedpqSc28WTOWnT7rZNXZSlPvFybie9EFc6y3bIMCn3JAoJ+kyyfn9qWq+LZ9Las26Jv482cDRE6Ci0B6gVbo2oj9KabzD8vyMK4ZMqMs2kSvW4chz88SXNzmeGjtj1QZK9M3HHL8L7HITX3t19//VVY8CYDg9Kvy2vDXu+6mGGxNOiltMPsjn/t9eJr0ja/FOdi5TyQ9Lz3fOqstOr99/dnro2vZ1jy76D/vYivPsBoYPB09XNZ55TQBAAJjs5s</body>")));

            imagealphablending($this->_DST['image'], true);
            imagecopy($this->_DST['image'], $magnifier, $this->_DST['width'] - 15, $this->_DST['height'] - 14, 0, 0, 11, 11);
            imagedestroy($magnifier);
        }

        if ($verbose) {
            echo "... saving image ...";
        }

        $result = false;

        if ($this->_DST['type'] == 1) {
            imagetruecolortopalette($this->_DST['image'], false, 256);
            $imagefn = 'imagegif';
        } elseif ($this->_DST['type'] == 2) {
            $imagefn = 'imagejpeg';
        } elseif ($this->_DST['type'] == 3) {
            $imagefn = 'imagepng';
        } elseif ($this->_DST['type'] == 18) {
            $imagefn = 'imagewebp';
        } elseif ($this->_DST['type'] == 19) {
            $imagefn = 'imageavif';
        }

        if (isset($imagefn) && function_exists($imagefn)) {
            $result = $imagefn == 'imagejpeg' ?
                imagejpeg($this->_DST['image'], $this->_DST['file'], $this->quality) :
                $imagefn($this->_DST['image'], $this->_DST['file']);
        }

        imagedestroy($this->_DST['image']);
        imagedestroy($this->_SRC['image']);

        if ($verbose) {
            echo $result ? "... image successfully saved ..." : "... failed to save image ...";
        }

        return $result;
    }

    /**
     * Return a reference to the the thumbnail image as a HTML a or img tag.
     *
     * @param  bool $aslink  Return an anchor tag to the source image
     * @param  bool $aspopup Open the link in new window
     * @return string HTML markup
     */

    public function asTag($aslink = true, $aspopup = false)
    {
        $imgtag = '<img src="'.$this->_DST['file'].'" '.$this->html.' width="'.$this->width.'" height="'.$this->height.'" />';

        if ($aslink === true) {
            return '<a href="'.((empty($this->linkurl)) ? $this->_SRC['file'] : $this->linkurl).'" '.
                (($aspopup === true) ? ' rel="noopener" target="_blank"' : '').'>'.$imgtag.'</a>';
        }

        return $imgtag;
    }
}

/**
 * Wrapper for wet_thumb interfacing Textpattern.
 *
 * @package Image
 */

class txp_thumb extends wet_thumb
{
    /**
     * File extension.
     *
     * @var string
     */

    public $m_ext;

    /**
     * Image ID.
     *
     * @var int
     */

    public $m_id;

    /**
     * Constructor.
     *
     * @param int $id The Image id.
     */

    public function __construct($id)
    {
        $id = assert_int($id);
        $rs = safe_row("*", 'txp_image', "id = $id LIMIT 1");
        if ($rs) {
            extract($rs);
            $this->m_ext = $ext;
            $this->m_id = $id;
        }
        parent::__construct();
    }

    /**
     * Creates a thumbnail image from a source image.
     *
     * @param  string $dummy1 Isn't used.
     * @param  string $dummy2 Isn't used.
     * @return bool TRUE on success
     */

    public function write($dummy1 = '', $dummy2 = '')
    {
        if (!isset($this->m_ext)) {
            return false;
        }

        if (parent::write(
            IMPATH.$this->m_id.$this->m_ext,
            IMPATH.$this->m_id.'t'.$this->m_ext
        )) {
            safe_update(
                'txp_image',
                "thumbnail = 1,
                thumb_w = $this->width,
                thumb_h = $this->height,
                date = NOW()",
                "id = ".$this->m_id
            );

            chmod(IMPATH.$this->m_id.'t'.$this->m_ext, 0644);

            return true;
        }

        return false;
    }

    /**
     * Removes a thumbnail.
     *
     * @return bool TRUE on success
     */

    public function delete()
    {
        if (!isset($this->m_ext)) {
            return false;
        }

        if (unlink(IMPATH.$this->m_id.'t'.$this->m_ext)) {
            safe_update('txp_image', "thumbnail = 0", "id = ".$this->m_id);

            return true;
        }

        return false;
    }
}

/**
 * Unsharp mask.
 *
 * Unsharp mask algorithm by Torstein Hønsi 2003 (thoensi_at_netcom_dot_no)
 * Christoph Erdmann: changed it a little, because I could not reproduce the
 * darker blurred image, now it is up to 15% faster with same results
 *
 * @author Torstein Hønsi
 * @author Christoph Erdmann
 * @param  resource $img       Image as a resource
 * @param  int      $amount    Filter parameter
 * @param  int      $radius    Filter parameter
 * @param  int      $threshold Filter parameter
 * @return resource Sharpened image as a resource.
 *
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 */

    function UnsharpMask($img, $amount, $radius, $threshold)
    {
        // Attempt to calibrate the parameters to Photoshop:
        if ($amount > 500) {
            $amount = 500;
        }

        $amount = (int)($amount * 0.016);

        if ($radius > 50) {
            $radius = 50;
        }

        $radius = $radius * 2;

        if ($threshold > 255) {
            $threshold = 255;
        }

        $radius = abs(round($radius)); // Only integers make sense.

        if ($radius == 0) {
            return $img;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $imgCanvas = $img;
        $imgCanvas2 = $img;
        $imgBlur = imagecreatetruecolor($w, $h);

        // Gaussian blur matrix:
        // 1 2 1
        // 2 4 2
        // 1 2 1
        // Move copies of the image around one pixel at the time and merge them
        // with weight according to the matrix. The same matrix is simply
        // repeated for higher radii.

        for ($i = 0; $i < $radius; $i++) {
            imagecopy($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
            imagecopymerge($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
            imagecopymerge($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33); // down left
            imagecopymerge($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
            imagecopymerge($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33); // left
            imagecopymerge($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
            imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20); // up
            imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 17); // down
            imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
        }

        $imgCanvas = $imgBlur;

        // Calculate the difference between the blurred pixels and the original
        // and set the pixels.

        for ($x = 0; $x < $w; $x++) {
            // Each row.
            for ($y = 0; $y < $h; $y++) {
                // Each pixel.
                $rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
                $rOrig = (($rgbOrig >> 16) & 0xFF);
                $gOrig = (($rgbOrig >> 8) & 0xFF);
                $bOrig = ($rgbOrig & 0xFF);
                $rgbBlur = ImageColorAt($imgCanvas, $x, $y);
                $rBlur = (($rgbBlur >> 16) & 0xFF);
                $gBlur = (($rgbBlur >> 8) & 0xFF);
                $bBlur = ($rgbBlur & 0xFF);

                // When the masked pixels differ less from the original than the
                // threshold specifies, they are set to their original value.

                $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
                $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
                $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

                if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
                    $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
                    ImageSetPixel($img, $x, $y, $pixCol);
                }
            }
        }

        return $img;
    }

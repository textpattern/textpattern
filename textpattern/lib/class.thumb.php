<?php
/**
 * class wet_thumb
 * @author	C. Erdmann
 * @see		<a href="http://www.cerdmann.de/thumb">http://www.cerdmann.de/thumb</a>
 * @author	Robert Wetzlmayr
 *
 * refactored from function.thumb.php by C. Erdmann, which contained the following credit & licensing terms:
 * ===
 * Smarty plugin "Thumb"
 * Purpose: creates cached thumbnails
 * Home: http://www.cerdmann.com/thumb/
 * Copyright (C) 2005 Christoph Erdmann
 *
 * This library is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License as published by the Free Software Foundation; either version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this library; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA
 * -------------------------------------------------------------
 * Author:   Christoph Erdmann (CE) <smarty@cerdmann.com>
 * Internet: http://www.cerdmann.com
 *
 * Author: Benjamin Fleckenstein (BF)
 * Internet: http://www.benjaminfleckenstein.de
 *
 * Author: Marcus Gueldenmeister (MG)
 * Internet: http://www.gueldenmeister.de/marcus/
 *
 * Author: Andreas Bösch (AB)
 *
 */

/*
$HeadURL$
$LastChangedRevision$
*/

$verbose = false;


class wet_thumb {
    var $width;      // The width of your thumbnail. The height (if not set) will be automatically calculated.
    var $height;	// The height of your thumbnail. The width (if not set) will be automatically calculated.
    var $longside;	// Set the longest side of the image if width, height and shortside is not set.
    var $shortside;	// Set the shortest side of the image if width, height and longside is not set.
    var $extrapolate;  // Set to 'false' if your source image is smaller than the calculated thumb and you do not want the image to get extrapolated.
    var $crop;	// If set to 'true', image will be cropped in the center to destination width and height params, while keeping aspect ratio. Otherwise the image will get resized.
    var $sharpen;	// Set to 'false' if you don't want to use the Unsharp-Mask. Thumbnail creation will be faster, but quality is reduced.
    var $hint; 	// If set to 'false' the image will not have a lens-icon.
    var $addgreytohint; // Set to 'false' to get no lightgrey bottombar.
    var $quality;	// JPEG image quality (0...100, defaults to 80).
    // link related params
    var $linkurl;    // Set to your target URL (a href="linkurl")
    var $html;       // Will be inserted in the image-tag

    var $types = array('','.gif','.jpg','.png');
    var $_SRC;
    var $_DST;

    /**
     * constructor
     */
    function wet_thumb(  ) {
	$this->extrapolate = false;
	$this->crop = true;
	$this->sharpen = true;
	$this->hint = true;
	$this->addgreytohint = true;
	$this->quality = 80;
	$this->html = " alt=\"\" title=\"\" ";
	$this->link = true;
    }

    /**
     * write thumbnail file
     * @param	infile	image file name
     * @param	outfile	array of thumb file names (1...n)
     * @return	boolean, true indicates success
     */
    function write($infile = null, $outfile = null) {
        global $verbose;

        if( $verbose )echo "writing thumb nail...";

	### fetch source (SRC) info
	$temp = getimagesize($infile);

	$this->_SRC['file']		= $infile;
	$this->_SRC['width']		= $temp[0];
	$this->_SRC['height']		= $temp[1];
	$this->_SRC['type']		= $temp[2]; // 1=GIF, 2=JPG, 3=PNG, SWF=4
	$this->_SRC['string']		= $temp[3];
	$this->_SRC['filename'] 	= basename($infile);
	//$this->_SRC['modified'] 	= filemtime($infile);

	//check image orientation
	if ($this->_SRC['width'] >= $this->_SRC['height']) {
	    $this->_SRC['format'] = 'landscape';
	} else {
	    $this->_SRC['format'] = 'portrait';
	}

	### fetch destination (DST) info
	if (is_numeric($this->width) AND empty($this->height)) {
		$this->_DST['width']	= $this->width;
		$this->_DST['height']	= round($this->width/($this->_SRC['width']/$this->_SRC['height']));
	}
	elseif (is_numeric($this->height) AND empty($this->width)) {
		$this->_DST['height']	= $this->height;
		$this->_DST['width']	= round($this->height/($this->_SRC['height']/$this->_SRC['width']));
	}
	elseif (is_numeric($this->width) AND is_numeric($this->height)) {
		$this->_DST['width']	= $this->width;
		$this->_DST['height']	= $this->height;
	}
	elseif (is_numeric($this->longside) AND empty($this->shortside)) {
	    // preserve aspect ratio based on provided height
	    if ($this->_SRC['format'] == 'portrait') {
		$this->_DST['height']	= $this->longside;
		$this->_DST['width']	= round($this->longside/($this->_SRC['height']/$this->_SRC['width']));
	    }
	    else {
		$this->_DST['width']	= $this->longside;
		$this->_DST['height']	= round($this->longside/($this->_SRC['width']/$this->_SRC['height']));
	    }
        }
	elseif (is_numeric($this->shortside)) {
	    // preserve aspect ratio based on provided width
	    if ($this->_SRC['format'] == 'portrait') {
		$this->_DST['width']	= $this->shortside;
		$this->_DST['height']	= round($this->shortside/($this->_SRC['width']/$this->_SRC['height']));
	    }
	    else {
		$this->_DST['height']	= $this->shortside;
		$this->_DST['width']	= round($this->shortside/($this->_SRC['height']/$this->_SRC['width']));
	    }
        }
        else { // default dimensions
            $this->width = 100;
            $this->_DST['width'] = $this->width;
            $this->_DST['height'] = round($this->width/($this->_SRC['width']/$this->_SRC['height']));
        }


	// don't make the new image larger than the original image
	if ($this->extrapolate === false && $this->_DST['height'] > $this->_SRC['height'] &&
					    $this->_DST['width'] > $this->_SRC['width']) {
	    $this->_DST['width'] = $this->_SRC['width'];
	    $this->_DST['height'] = $this->_SRC['height'];
	}

	$this->_DST['type'] = $this->_SRC['type'];
	$this->_DST['file'] = $outfile;

	// make sure we have enough memory if the image is large
	if (max($this->_SRC['width'], $this->_SRC['height']) > 1024) {
		$shorthand = array('/K/i','/M/i','/G/i');
		$tens = array('000','000000', '000000000'); // A good enough decimal approximation of K, M, and G

		// Do not *decrease* memory_limit
		// TODO: Try str_ireplace instead of preg_replace once we are on PHP5
		list($ml, $extra) = preg_replace($shorthand, $tens, array(ini_get('memory_limit'), EXTRA_MEMORY));
		if ($ml < $extra) {
			// this won't work on all servers but it's worth a try
			ini_set('memory_limit', EXTRA_MEMORY);
		}
	}

	// read SRC
	if ($this->_SRC['type'] == 1)	$this->_SRC['image'] = imagecreatefromgif($this->_SRC['file']);
	elseif ($this->_SRC['type'] == 2)	$this->_SRC['image'] = imagecreatefromjpeg($this->_SRC['file']);
	elseif ($this->_SRC['type'] == 3)	$this->_SRC['image'] = imagecreatefrompng($this->_SRC['file']);

	// crop image?
	$off_w = 0;
	$off_h = 0;
	if($this->crop != false) {
	    if($this->_SRC['height'] < $this->_SRC['width']) {
		$ratio = (double)($this->_SRC['height'] / $this->_DST['height']);
		$cpyWidth = round($this->_DST['width'] * $ratio);
		if ($cpyWidth > $this->_SRC['width']) {
		    $ratio = (double)($this->_SRC['width'] / $this->_DST['width']);
		    $cpyWidth = $this->_SRC['width'];
		    $cpyHeight = round($this->_DST['height'] * $ratio);
		    $off_w = 0;
		    $off_h = round(($this->_SRC['height'] - $cpyHeight) / 2);
		    $this->_SRC['height'] = $cpyHeight;
		}
		else {
		    $cpyHeight = $this->_SRC['height'];
		    $off_w = round(($this->_SRC['width'] - $cpyWidth) / 2);
		    $off_h = 0;
		    $this->_SRC['width']= $cpyWidth;
		}
	    }
	    else {
		$ratio = (double)($this->_SRC['width'] / $this->_DST['width']);
		$cpyHeight = round($this->_DST['height'] * $ratio);
		if ($cpyHeight > $this->_SRC['height']) {
		    $ratio = (double)($this->_SRC['height'] / $this->_DST['height']);
		    $cpyHeight = $this->_SRC['height'];
		    $cpyWidth = round($this->_DST['width'] * $ratio);
		    $off_w = round(($this->_SRC['width'] - $cpyWidth) / 2);
		    $off_h = 0;
		    $this->_SRC['width']= $cpyWidth;
		}
		else {
		    $cpyWidth = $this->_SRC['width'];
		    $off_w = 0;
		    $off_h = round(($this->_SRC['height'] - $cpyHeight) / 2);
		    $this->_SRC['height'] = $cpyHeight;
		}
	    }
	}

	// ensure non-zero height/width
	if (!$this->_DST['height']) $this->_DST['height'] = 1;
	if (!$this->_DST['width'])  $this->_DST['width']  = 1;

	// create DST
	$this->_DST['image'] = imagecreatetruecolor($this->_DST['width'], $this->_DST['height']);

	// GIF or PNG destination, set the transparency up.
	if ($this->_DST['type'] == 1 || $this->_DST['type'] == 3) {
		$trans_idx = imagecolortransparent($this->_SRC['image']);

		// Is there a specific transparent colour?
		if ($trans_idx >= 0) {
			$trans_color = imagecolorsforindex($this->_SRC['image'], $trans_idx);
			$trans_idx = imagecolorallocate($this->_DST['image'], $trans_color['red'], $trans_color['green'], $trans_color['blue']);
			imagefill($this->_DST['image'], 0, 0, $trans_idx);
			imagecolortransparent($this->_DST['image'], $trans_idx);
		} else if ($this->_DST['type'] == 3) {
			imagealphablending($this->_DST['image'], false);
			$transparent = imagecolorallocatealpha($this->_DST['image'], 0, 0, 0, 127);
			imagefill($this->_DST['image'], 0, 0, $transparent);
			imagesavealpha($this->_DST['image'], true);
		}
	}
	imagecopyresampled($this->_DST['image'], $this->_SRC['image'], 0, 0, $off_w, $off_h, $this->_DST['width'], $this->_DST['height'], $this->_SRC['width'], $this->_SRC['height']);
	if ($this->sharpen === true) {
	    $this->_DST['image'] = UnsharpMask($this->_DST['image'],80,.5,3);
	}

        // finally: the real dimensions
        $this->height =  $this->_DST['height'];
        $this->width =  $this->_DST['width'];

	// add magnifying glass?
	if ( $this->hint === true) {
	    // should we really add white bars?
	    if ( $this->addgreytohint === true ) {
		$trans = imagecolorallocatealpha($this->_DST['image'], 255, 255, 255, 25);
		imagefilledrectangle($this->_DST['image'], 0, $this->_DST['height']-9, $this->_DST['width'], $this->_DST['height'], $trans);
	    }

	    $magnifier = imagecreatefromstring(gzuncompress(base64_decode("eJzrDPBz5+WS4mJgYOD19HAJAtLcIMzBBiRXrilXA1IsxU6eIRxAUMOR0gHkcxZ4RBYD1QiBMOOlu3V/gIISJa4RJc5FqYklmfl5CiGZuakMBoZ6hkZ6RgYGJs77ex2BalRBaoLz00rKE4tSGXwTk4vyc1NTMhMV3DKLUsvzi7KLFXwjFEAa2svWnGdgYPTydHEMqZhTOsE++1CAyNHzm2NZjgau+dAmXlAwoatQmOld3t/NPxlLMvY7sovPzXHf7re05BPzjpQTMkZTPjm1HlHkv6clYWK43Zt16rcDjdZ/3j2cd7qD4/HHH3GaprFrw0QZDHicORXl2JsPsveVTDz//L3N+WpxJ5Hff+10Tjdd2/Vi17vea79Om5w9zzyne9GLnWGrN8atby/ayXPOsu2w4quvVtxNCVVz5nAf3nDpZckBCedpqSc28WTOWnT7rZNXZSlPvFybie9EFc6y3bIMCn3JAoJ+kyyfn9qWq+LZ9Las26Jv482cDRE6Ci0B6gVbo2oj9KabzD8vyMK4ZMqMs2kSvW4chz88SXNzmeGjtj1QZK9M3HHL8L7HITX3t19//VVY8CYDg9Kvy2vDXu+6mGGxNOiltMPsjn/t9eJr0ja/FOdi5TyQ9Lz3fOqstOr99/dnro2vZ1jy76D/vYivPsBoYPB09XNZ55TQBAAJjs5s</body>")));
	    imagealphablending($this->_DST['image'], true);
	    imagecopy($this->_DST['image'], $magnifier, $this->_DST['width']-15, $this->_DST['height']-14, 0, 0, 11, 11);
	    imagedestroy($magnifier);
	}

        if ($verbose ) echo "... saving image ...";

        if ($this->_DST['type'] == 1)	{
	    imagetruecolortopalette($this->_DST['image'], false, 256);
		if ( function_exists ('imagegif') ) {
			imagegif($this->_DST['image'], $this->_DST['file']);
		} else {
			imagedestroy($this->_DST['image']);
			imagedestroy($this->_SRC['image']);
			return false;
		}
	}
	elseif ($this->_DST['type'] == 2) {
	    imagejpeg($this->_DST['image'], $this->_DST['file'], $this->quality);
	}
	elseif ($this->_DST['type'] == 3) {
	    imagepng($this->_DST['image'], $this->_DST['file']);
	}

        if ($verbose ) echo "... image successfully saved ...";

	imagedestroy($this->_DST['image']);
	imagedestroy($this->_SRC['image']);
	return true;
    }

    /**
     * return a reference to the the thumbnailimage as a HTML <a> or <img> tag
     * @param	aslink	return an anchor tag to the source image
     * @param	aspopup	open link in new window
     * @return	string with suitable HTML markup
     */
    function asTag( $aslink = true, $aspopup = false  )
    {
        $imgtag = "<img src=\"" . $this->_DST['file']. "\" " .
                    $this->html . " " .
                    "width=\"".$this->width."\" " .
                    "height=\"".$this->height."\" " .
                    "/>";

        if ( $aslink === true ) {
            return "<a href=\"" . ((empty($this->linkurl)) ? $this->_SRC['file'] : $this->linkurl) . "\" " .
                    (($aspopup === true) ? "target=\"_blank\"" : "") . ">" .
                    $imgtag .
                    "</a>";
        }
        else {
            return $imgtag;
        }
    }
}
/**
 * class txp_thumb: wrapper for wet_thumb interfacing the TxP repository
 */
class txp_thumb extends wet_thumb {

    var $m_ext;
    var $m_id;

    /***
     * constructor
     * @param	$id	image id
     */
    function txp_thumb ($id) {
        $id = assert_int($id);
        $rs = safe_row('*', 'txp_image', 'id = '.$id.' limit 1');
        if ($rs) {
            extract($rs);
            $this->m_ext = $ext;
            $this->m_id = $id;
        }
        $this->wet_thumb(); // construct base class instance
    }

    /**
     * create thumbnail image from source image
     * @return	boolean, true indicates success
     */
    function write($infile = null, $outfile = null) {
        if ( !isset($this->m_ext) ) return false;

        if ( parent::write ( IMPATH.$this->m_id.$this->m_ext, IMPATH.$this->m_id.'t'.$this->m_ext ) ) {
		    safe_update('txp_image', "thumbnail = 1, thumb_w = $this->width, thumb_h = $this->height, date = now()", 'id = '.$this->m_id);
		    chmod(IMPATH.$this->m_id.'t'.$this->m_ext, 0644);
		    return true;
		}
		return false;
    }

     /**
     * delete thumbnail
     * @return	boolean, true indicates success
     */
    function delete( ) {
        if (!isset($this->m_ext)) return false;

		if (unlink(IMPATH.$this->m_id.'t'.$this->m_ext)) {
			safe_update('txp_image', 'thumbnail = 0', 'id = '.$this->m_id);
	    	return true;
		}
	return false;
    }

}

/**
 * Unsharp mask algorithm by Torstein Hønsi 2003 (thoensi_at_netcom_dot_no)
 * Christoph Erdmann: changed it a little, cause i could not reproduce the
 * darker blurred image, now it is up to 15% faster with same results
 * @param   img     image as a ressource
 * @param   amount  filter parameter
 * @param   radius  filter parameter
 * @param   treshold    filter parameter
 * @return  sharpened image as a ressource
 *
 *
 * This library is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License as published by the Free Software Foundation; either version 2.1 of the License, or (at your option) any later version.
*/

function UnsharpMask($img, $amount, $radius, $threshold)    {
    // Attempt to calibrate the parameters to Photoshop:
    if ($amount > 500) $amount = 500;
    $amount = $amount * 0.016;
    if ($radius > 50) $radius = 50;
    $radius = $radius * 2;
    if ($threshold > 255) $threshold = 255;

    $radius = abs(round($radius)); 	// Only integers make sense.
    if ($radius == 0) {	return $img; imagedestroy($img); break;	}
    $w = imagesx($img); $h = imagesy($img);
    $imgCanvas = $img;
    $imgCanvas2 = $img;
    $imgBlur = imagecreatetruecolor($w, $h);

    // Gaussian blur matrix:
    //	1	2	1
    //	2	4	2
    //	1	2	1

    // Move copies of the image around one pixel at the time and merge them with weight
    // according to the matrix. The same matrix is simply repeated for higher radii.
    for ($i = 0; $i < $radius; $i++)
            {
            imagecopy      ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
            imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
            imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
            imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
            imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 ); // up
            imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
            imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
            }
    $imgCanvas = $imgBlur;

    // Calculate the difference between the blurred pixels and the original
    // and set the pixels
    for ($x = 0; $x < $w; $x++) { // each row
        for ($y = 0; $y < $h; $y++) { // each pixel
            $rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
            $rOrig = (($rgbOrig >> 16) & 0xFF);
            $gOrig = (($rgbOrig >> 8) & 0xFF);
            $bOrig = ($rgbOrig & 0xFF);
            $rgbBlur = ImageColorAt($imgCanvas, $x, $y);
            $rBlur = (($rgbBlur >> 16) & 0xFF);
            $gBlur = (($rgbBlur >> 8) & 0xFF);
            $bBlur = ($rgbBlur & 0xFF);

            // When the masked pixels differ less from the original
            // than the threshold specifies, they are set to their original value.
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

<?php

namespace lencioni\SLIR\icc;
/**
 * PHP JPEG ICC profile manipulator class
 *
 * @author Richard Toth aka risko (risko@risko.org)
 * @version 0.1
 */
class JPEG_ICC
{
	/**
     * ICC header size in APP2 segment
	 *
	 * 'ICC_PROFILE' 0x00 chunk_no chunk_cnt
     */
    const ICC_HEADER_LEN = 14;

    /**
     * maximum data len of a JPEG marker
     */
    const MAX_BYTES_IN_MARKER = 65533;

	/**
	 * ICC header marker
	 */
	const ICC_MARKER = "ICC_PROFILE\x00";

	/**
	 * Rendering intent field (Bytes 64 to 67 in ICC profile data)
	 */
	const ICC_RI_PERCEPTUAL = 0x00000000;
	const ICC_RI_RELATIVE_COLORIMETRIC = 0x00000001;
	const ICC_RI_SATURATION = 0x00000002;
	const ICC_RI_ABSOLUTE_COLORIMETRIC = 0x00000003;

	/**
	 * ICC profile data
	 * @var		string
	 */
    private $icc_profile = '';

	/**
	 * ICC profile data size
	 * @var		int
	 */
    private $icc_size = 0;
	/**
	 * ICC profile data chunks count
	 * @var		int
	 */
    private $icc_chunks = 0;


	/**
	 * Class contructor
	 */
    public function  __construct()
    {
    }

	/**
	 * Load ICC profile from JPEG file.
	 *
	 * Returns true if profile successfully loaded, false otherwise.
	 *
	 * @param		string		file name
	 * @return		bool
	 */
    public function LoadFromJPEG($fname)
    {
		$f = file_get_contents($fname);
		$len = strlen($f);
		$pos = 0;
		$counter = 0;
		$profile_chunks = array(); // tu su ulozene jednotlive casti profilu

		while ($pos < $len && $counter < 1000)
		{
			$pos = strpos($f, "\xff", $pos);
			if ($pos === false) break; // dalsie 0xFF sa uz nenaslo - koniec vyhladavania

			$type = $this->getJPEGSegmentType($f, $pos);
			switch ($type)
			{
				case 0xe2: // APP2
					//echo "APP2 ";
					$size = $this->getJPEGSegmentSize($f, $pos);
					//echo "Size: $size\n";

					if ($this->getJPEGSegmentContainsICC($f, $pos, $size))
					{
						//echo "+ ICC Profile: YES\n";
						list($chunk_no, $chunk_cnt) = $this->getJPEGSegmentICCChunkInfo($f, $pos);
						//echo "+ ICC Profile chunk number: $chunk_no\n";
						//echo "+ ICC Profile chunks count: $chunk_cnt\n";

						if ($chunk_no <= $chunk_cnt)
						{
							$profile_chunks[$chunk_no] = $this->getJPEGSegmentICCChunk($f, $pos);

							if ($chunk_no == $chunk_cnt) // posledny kusok
							{
								ksort($profile_chunks);
								$this->SetProfile(implode('', $profile_chunks));
								return true;
							}
						}
					}
					$pos += $size + 2; // size of segment data + 2B size of segment marker
					break;

				case 0xe0: // APP0
				case 0xe1: // APP1
				case 0xe3: // APP3
				case 0xe4: // APP4
				case 0xe5: // APP5
				case 0xe6: // APP6
				case 0xe7: // APP7
				case 0xe8: // APP8
				case 0xe9: // APP9
				case 0xea: // APP10
				case 0xeb: // APP11
				case 0xec: // APP12
				case 0xed: // APP13
				case 0xee: // APP14
				case 0xef: // APP15
				case 0xc0: // SOF0
				case 0xc2: // SOF2
				case 0xc4: // DHT
				case 0xdb: // DQT
				case 0xda: // SOS
				case 0xfe: // COM
					$size = $this->getJPEGSegmentSize($f, $pos);
					$pos += $size + 2; // size of segment data + 2B size of segment marker
					break;

				case 0xd8: // SOI
				case 0xdd: // DRI
				case 0xd9: // EOI
				case 0xd0: // RST0
				case 0xd1: // RST1
				case 0xd2: // RST2
				case 0xd3: // RST3
				case 0xd4: // RST4
				case 0xd5: // RST5
				case 0xd6: // RST6
				case 0xd7: // RST7
				default:
					$pos += 2;
					break;
			}
			$counter++;
		}

		return false;
    }

    public function SaveToJPEG($fname)
    {
		if ($this->icc_profile == '') throw new \Exception("No profile loaded.\n");

		if (!file_exists($fname)) throw new \Exception("File $fname doesn't exist.\n");
		if (!is_readable($fname)) throw new \Exception("File $fname isn't readable.\n");
		$dir = realpath($fname);
		if (!is_writable($dir)) throw new \Exception("Directory $fname isn't writeable.\n");

		$f = file_get_contents($fname);
		if ($this->insertProfile($f))
		{
			$fsize = strlen($f);
			$ret = file_put_contents($fname, $f);
			if ($ret === false || $ret < $fsize) throw new \Exception ("Write failed.\n");
		}
    }

	/**
	 * Load profile from ICC file.
	 *
	 * @param		string		file name
	 */
    public function LoadFromICC($fname)
    {
		if (!file_exists($fname)) throw new \Exception("File $fname doesn't exist.\n");
		if (!is_readable($fname)) throw new \Exception("File $fname isn't readable.\n");

		$this->SetProfile(file_get_contents($fname));
    }

	/**
	 * Save profile to ICC file.
	 *
	 * @param		string		file name
	 * @param		bool		[force overwrite]
	 */
    public function SaveToICC($fname, $force_overwrite = false)
    {
		if ($this->icc_profile == '') throw new \Exception("No profile loaded.\n");
		$dir = realpath($fname);
		if (!is_writable($dir)) throw new \Exception("Directory $fname isn't writeable.\n");
		if (!$force_overwrite && file_exists($fname)) throw new \Exception("File $fname exists.\n");

		$ret = file_put_contents($fname, $this->icc_profile);
		if ($ret === false || $ret < $this->icc_size) throw new \Exception ("Write failed.\n");
	}

	/**
	 * Remove profile from JPEG file and save it as a new file.
	 * Overwriting destination file can be forced
	 *
	 * @param		string		source file
	 * @param		string		destination file
	 * @param		bool		[force overwrite]
	 * @return		bool
	 */
    public function RemoveFromJPEG($input, $output, $force_overwrite = false)
    {
		if (!file_exists($input)) throw new \Exception("File $input doesn't exist.\n");
		if (!is_readable($input)) throw new \Exception("File $input isn't readable.\n");
		$dir = realpath($output);
		if (!is_writable($dir)) throw new \Exception("Directory $output isn't writeable.\n");
		if (!$force_overwrite && file_exists($output)) throw new \Exception("File $output exists.\n");

		$f = file_get_contents($input);
		$this->removeProfile($f);
		$fsize = strlen($f);
		$ret = file_put_contents($output, $f);
		if ($ret === false || $ret < $fsize) throw new \Exception ("Write failed.\n");

		return true; // any other error throws \exception
    }

	/**
	 * Set profile directly
	 *
	 * @param		string		profile data
	 */
    public function SetProfile($data)
    {
		$this->icc_profile = $data;
		$this->icc_size = strlen($data);
		$this->countChunks();
    }

	/**
	 * Get profile directly
	 *
	 * @return		string
	 */
    public function GetProfile()
    {
		return $this->icc_profile;
    }

	/**
	 * Count in how many chunks we need to divide the profile to store it in JPEG APP2 segments
	 */
    private function countChunks()
    {
		$this->icc_chunks = ceil($this->icc_size / ((float) (self::MAX_BYTES_IN_MARKER - self::ICC_HEADER_LEN)));
    }

	/**
	 * Set Rendering Intent of the profile.
	 *
	 * Possilbe values are ICC_RI_PERCEPTUAL, ICC_RI_RELATIVE_COLORIMETRIC, ICC_RI_SATURATION or ICC_RI_ABSOLUTE_COLORIMETRIC.
	 *
	 * @param		int		rendering intent
	 */
	private function setRenderingIntent($newRI)
	{
		if ($this->icc_size >= 68)
		{
			substr_replace($this->icc_profile, pack('N', $newRI), 64, 4);
		}
	}

	/**
	 * Get value of Rendering Intent field in ICC profile
	 *
	 * @return		int
	 */
	private function getRenderingIntent()
	{
		if ($this->icc_size >= 68)
		{
			$arr = unpack('Nint', substr($this->icc_profile, 64, 4));
			return $arr['int'];
		}

		return null;
	}

	/**
	 * Size of JPEG segment
	 *
	 * @param		string		file data
	 * @param		int			start of segment
	 * @return		int
	 */
	private function getJPEGSegmentSize(&$f, $pos)
	{
		$arr = unpack('nint', substr($f, $pos + 2, 2)); // segment size has offset 2 and length 2B
		return $arr['int'];
	}

	/**
	 * Type of JPEG segment
	 *
	 * @param		string		file data
	 * @param		int			start of segment
	 * @return		int
	 */
	private function getJPEGSegmentType(&$f, $pos)
	{
		$arr = unpack('Cchar', substr($f, $pos + 1, 1)); // segment type has offset 1 and length 1B
		return $arr['char'];
	}

	/**
	 * Check if segment contains ICC profile marker
	 *
	 * @param		string		file data
	 * @param		int			position of segment data
	 * @param		int			size of segment data (without 2 bytes of size field)
	 * @return		bool
	 */
	private function getJPEGSegmentContainsICC(&$f, $pos, $size)
	{
		if ($size < self::ICC_HEADER_LEN) return false; // ICC_PROFILE 0x00 Marker_no Marker_cnt

		return (bool) (substr($f, $pos + 4, self::ICC_HEADER_LEN - 2) == self::ICC_MARKER); // 4B offset in segment data = 2B segment marker + 2B segment size data
	}

	/**
	 * Get ICC segment chunk info
	 *
	 * @param		string		file data
	 * @param		int			position of segment data
	 * @return		array		{chunk_no, chunk_cnt}
	 */
	private function getJPEGSegmentICCChunkInfo(&$f, $pos)
	{
		$a = unpack('Cchunk_no/Cchunk_count', substr($f, $pos + 16, 2)); // 16B offset to data = 2B segment marker + 2B segment size + 'ICC_PROFILE' + 0x00, 1. byte chunk number, 2. byte chunks count
		return array_values($a);
	}

	/**
	 * Returns chunk of ICC profile data from segment.
	 *
	 * @param		string		&data
	 * @param		int			current position
	 * @return		string
	 */
	private function getJPEGSegmentICCChunk(&$f, $pos)
	{
		$data_offset = $pos + 4 + self::ICC_HEADER_LEN; // 4B JPEG APP offset + 14B ICC header offset
		$size = $this->getJPEGSegmentSize($f, $pos);
		$data_size = $size - self::ICC_HEADER_LEN - 2; // 14B ICC header - 2B of size data
		return substr($f, $data_offset, $data_size);
	}

	/**
	 * Get data of given chunk
	 *
	 * @param		int			chunk number
	 * @return		string
	 */
	private function getChunk($chunk_no)
	{
		if ($chunk_no > $this->icc_chunks) return '';

		$max_chunk_size = self::MAX_BYTES_IN_MARKER - self::ICC_HEADER_LEN;
		$from = ($chunk_no - 1) * $max_chunk_size;
		$bytes = ($chunk_no < $this->icc_chunks) ? $max_chunk_size : $this->icc_size % $max_chunk_size;

		return substr($this->icc_profile, $from, $bytes);
	}

	private function prepareJPEGProfileData()
	{
		$data = '';

		for ($i = 1; $i <= $this->icc_chunks; $i++)
		{
			$chunk = $this->getChunk($i);
			$chunk_size = strlen($chunk);
			$data .= "\xff\xe2" . pack('n', $chunk_size + 2 + self::ICC_HEADER_LEN); // APP2 segment marker + size field
			$data .= self::ICC_MARKER . pack('CC', $i, $this->icc_chunks); // profile marker inside segment
			$data .= $chunk;
		}

		return $data;
	}

	/**
	 * Removes profile from JPEG data
	 *
	 * @param		string		&data
	 * @return		bool
	 */
	private function removeProfile(&$jpeg_data)
	{
		$len = strlen($jpeg_data);
		$pos = 0;
		$counter = 0; // ehm...
		$chunks_to_go = -1;

		while ($pos < $len && $counter < 100)
		{
			$pos = strpos($jpeg_data, "\xff", $pos);
			if ($pos === false) break; // no more 0xFF - we can end up with search

			// analyze next segment
			$type = $this->getJPEGSegmentType($jpeg_data, $pos);

			switch ($type)
			{
				case 0xe2: // APP2
					$size = $this->getJPEGSegmentSize($jpeg_data, $pos);

					if ($this->getJPEGSegmentContainsICC($jpeg_data, $pos, $size))
					{
						list($chunk_no, $chunk_cnt) = $this->getJPEGSegmentICCChunkInfo($jpeg_data, $pos);
						if ($chunks_to_go == -1) $chunks_to_go = $chunk_cnt; // first time save chunks count

						$jpeg_data = substr_replace($jpeg_data, '', $pos, $size + 2); // remove this APP segment from dataset (segment size + 2B app marker)
						$len -= $size + 2; // shorten the size

						if (--$chunks_to_go == 0) return true; // no more icc profile chunks, store file

						break; // go out without changing the position
					}
					$pos += $size + 2; // size of segment data + 2B size of segment marker
					break;

				case 0xe0: // APP0
				case 0xe1: // APP1
				case 0xe3: // APP3
				case 0xe4: // APP4
				case 0xe5: // APP5
				case 0xe6: // APP6
				case 0xe7: // APP7
				case 0xe8: // APP8
				case 0xe9: // APP9
				case 0xea: // APP10
				case 0xeb: // APP11
				case 0xec: // APP12
				case 0xed: // APP13
				case 0xee: // APP14
				case 0xef: // APP15
				case 0xc0: // SOF0
				case 0xc2: // SOF2
				case 0xc4: // DHT
				case 0xdb: // DQT
				case 0xda: // SOS
				case 0xfe: // COM
					$size = $this->getJPEGSegmentSize($jpeg_data, $pos);
					$pos += $size + 2; // size of segment data + 2B size of segment marker
					break;

				case 0xd8: // SOI
				case 0xdd: // DRI
				case 0xd9: // EOI
				case 0xd0: // RST0
				case 0xd1: // RST1
				case 0xd2: // RST2
				case 0xd3: // RST3
				case 0xd4: // RST4
				case 0xd5: // RST5
				case 0xd6: // RST6
				case 0xd7: // RST7
				default:
					$pos += 2;
					break;
			}
			$counter++;
		}

		return false;
	}

	/**
	 * Inserts profile to JPEG data.
	 *
	 * Inserts profile immediately after SOI section
	 *
	 * @param		string		&data
	 * @return		bool
	 */
	private function insertProfile(&$jpeg_data)
	{
		$len = strlen($jpeg_data);
		$pos = 0;
		$counter = 0; // ehm...
		$chunks_to_go = -1;

		while ($pos < $len && $counter < 100)
		{
			$pos = strpos($jpeg_data, "\xff", $pos);
			if ($pos === false) break; // no more 0xFF - we can end up with search

			// analyze next segment
			$type = $this->getJPEGSegmentType($jpeg_data, $pos);

			switch ($type)
			{
				case 0xd8: // SOI
					$pos += 2;

					$p_data = $this->prepareJPEGProfileData();
					if ($p_data != '')
					{
						$before = substr($jpeg_data, 0, $pos);
						$after = substr($jpeg_data, $pos);
						$jpeg_data = $before . $p_data . $after;
						return true;
					}
					return false;
					//break;

				case 0xe0: // APP0
				case 0xe1: // APP1
				case 0xe2: // APP2
				case 0xe3: // APP3
				case 0xe4: // APP4
				case 0xe5: // APP5
				case 0xe6: // APP6
				case 0xe7: // APP7
				case 0xe8: // APP8
				case 0xe9: // APP9
				case 0xea: // APP10
				case 0xeb: // APP11
				case 0xec: // APP12
				case 0xed: // APP13
				case 0xee: // APP14
				case 0xef: // APP15
				case 0xc0: // SOF0
				case 0xc2: // SOF2
				case 0xc4: // DHT
				case 0xdb: // DQT
				case 0xda: // SOS
				case 0xfe: // COM
					$size = $this->getJPEGSegmentSize($jpeg_data, $pos);
					$pos += $size + 2; // size of segment data + 2B size of segment marker
					break;

				case 0xdd: // DRI
				case 0xd9: // EOI
				case 0xd0: // RST0
				case 0xd1: // RST1
				case 0xd2: // RST2
				case 0xd3: // RST3
				case 0xd4: // RST4
				case 0xd5: // RST5
				case 0xd6: // RST6
				case 0xd7: // RST7
				default:
					$pos += 2;
					break;
			}
			$counter++;
		}

		return false;
	}
}
?>

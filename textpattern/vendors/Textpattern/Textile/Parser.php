<?php

/**
 * Textpattern configured Textile wrapper.
 *
 * @since   4.6.0
 * @package Textile
 */

/**
 * Imports Textile.
 */

require_once txpath.'/lib/classTextile.php';

/**
 * Textile parser.
 *
 * @since   4.6.0
 * @package Textile
 */

class Textpattern_Textile_Parser extends Textile
{
	/**
	 * Constructor.
	 *
	 * @param string|null $doctype The output doctype
	 */

	public function __construct($doctype = null)
	{
		if ($doctype === null)
		{
			$doctype = get_pref('doctype', 'html5');
		}

		parent::__construct($doctype);
		$this->setRelativeImagePrefix(hu);
	}

	/**
	 * Parses content in a restricted mode.
	 *
	 * @param  string|null $text      The input document in textile format
	 * @param  bool|null   $lite      Optional flag to switch the parser into lite mode
	 * @param  bool|null   $noimage   Optional flag controlling the conversion of images into HTML img tags
	 * @param  string|null $rel       Relationship to apply to all generated links
	 * @return string      The text from the input document
	 */

	public function textileRestricted($text, $lite = null, $noimage = null, $rel = null)
	{
		if ($lite === null)
		{
			$lite = get_pref('comments_use_fat_textile', 1);
		}

		if ($noimage === null)
		{
			$noimage = get_pref('comments_disallow_images', 1);
		}

		if ($rel === null)
		{
			$rel = get_pref('comment_nofollow', 'nofollow');
		}

		return parent::textileRestricted($text, $lite, $noimage, $rel);
	}
}

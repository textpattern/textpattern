<?php

/**
 * Outputs CSS files.
 *
 * This file is here for backwards compatibility.
 * See css.php file in the parent directory instead.
 *
 * @deprecated in 4.2.0
 * @see        ../css.php
 */

if (!defined("txpath"))
{
	/**
	 * @ignore
	 */
	define("txpath", dirname(__FILE__));
}

require_once txpath.'/../css.php';
?>

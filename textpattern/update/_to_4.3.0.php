<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	// Raw CSS is now the only option
	safe_delete('txp_prefs', "event='css' and name='edit_raw_css_by_default'");

	$rs = getRows('select name,css from `'.PFX.'txp_css`');
	foreach ($rs as $row) {
		if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $row['css'])) {
			// Data is still base64 encoded
			safe_update('txp_css', "css = '" . doSlash(base64_decode($row['css'])) . "'", "name = '". doSlash($row['name']) ."'");
		}
	}

    // add column for file title
 	$cols = getThings('describe `'.PFX.'txp_file`');
 	if (!in_array('title', $cols))
 	{
		safe_alter('txp_file', "ADD `title` VARCHAR( 255 ) NULL AFTER `filename`");
 	}

 ?>

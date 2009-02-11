<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	$cols = getThings('describe `'.PFX.'txp_prefs`');
 	if (!in_array('user_name', $cols)) {
		safe_alter('txp_prefs',
		"ADD `user_name` varchar(64) NOT NULL, DROP INDEX `prefs_idx`, ADD UNIQUE `prefs_idx` (`prefs_id`, `name`, `user_name`), ADD INDEX `user_name` (`user_name`)");
 	}

?>
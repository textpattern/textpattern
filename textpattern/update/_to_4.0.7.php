<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	$txpplugin = getThings('describe `'.PFX.'txp_plugin`');
 	if (!in_array('load_order', $txpplugin)) {
		safe_alter('txp_plugin',
			"ADD load_order TINYINT UNSIGNED NOT NULL DEFAULT 5");
	}
?>


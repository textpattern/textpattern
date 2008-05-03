<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	$txpplugin = getThings('describe '.PFX.'txp_plugin');
 	if (!in_array('order', $txpplugin)) {
		safe_alter('txp_plugin',
			"ADD `order` TINYINT UNSIGNED DEFAULT 5");
	}
?>


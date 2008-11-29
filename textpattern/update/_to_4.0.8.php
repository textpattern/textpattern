<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/development/4.0/textpattern/update/_to_4.0.7.php $
$LastChangedRevision: 3014 $
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	if (!getRows('show index from `'.PFX.'txp_plugin` where `Key_name`=\'status_type_idx\'')) {
		safe_query('alter ignore table `'.PFX.'txp_plugin` add index status_type_idx(`status`, `type`)');
	}

?>


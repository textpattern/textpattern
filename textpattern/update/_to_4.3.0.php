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

 ?>

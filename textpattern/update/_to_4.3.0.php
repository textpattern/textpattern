<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/development/4.x/textpattern/update/_to_4.2.0.php $
$LastChangedRevision: 3233 $
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	// Raw CSS is now the only option
	safe_delete('txp_prefs', "event='css' and name='edit_raw_css_by_default'");

 ?>

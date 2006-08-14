<?php

/*
$HeadURL: http://svn.textpattern.com/current/textpattern/_update.php $
$LastChangedRevision: 711 $
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	if (!safe_field('name', 'txp_prefs', "name = 'allow_raw_php_scripting'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'allow_raw_php_scripting', val = '1', type = '1'");
	}

	if (!safe_field('name', 'txp_prefs', "name = 'log_list_pageby'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'log_list_pageby', val = '25', type = 2, event = 'publish'");
	}

	// turn on lastmod handling, and reset the lastmod date
	safe_update('txp_prefs', "val='1'", "name='send_lastmod' and prefs_id='1'");
	update_lastmod();
?>

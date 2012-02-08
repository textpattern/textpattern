<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	// doctype prefs
	if (!safe_field('name', 'txp_prefs', "name = 'doctype'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'doctype', val = 'xhtml', type = '0', event = 'publish', html = 'doctypes', position = '190'");
	}
?>
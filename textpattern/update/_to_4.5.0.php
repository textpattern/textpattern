<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/development/4.x/textpattern/update/_to_4.4.1.php $
$LastChangedRevision: 3563 $
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
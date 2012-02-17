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

	$has_idx = 0;
	$rs = getRows('show index from `'.PFX.'textpattern`');
	foreach ($rs as $row) {
		if ($row['Key_name'] == 'url_title_idx')
			$has_idx = 1;
	}

	if (!$has_idx) {
		safe_query('alter ignore table `'.PFX.'textpattern` add index url_title_idx(`url_title`)');
	}
?>
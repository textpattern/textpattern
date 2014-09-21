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

	// publisher's email address
	if (!safe_field('name', 'txp_prefs', "name = 'publisher_email'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'publisher_email', val = '', type = 1, event = 'admin', position = 115");
	}
	// goodbye raw ?php support
	if (safe_field('name', 'txp_prefs', "name = 'allow_raw_php_scripting'"))
	{
		safe_delete('txp_prefs', "name = 'allow_raw_php_scripting'");
	}

	safe_alter('txp_users', "MODIFY RealName VARCHAR(255) NOT NULL default '', MODIFY email VARCHAR(254) NOT NULL default ''");

	// Remove any setup strings from lang table
	safe_delete('txp_lang', "event='setup'");

	$has_idx = 0;
	$rs = getRows('show index from `'.PFX.'textpattern`');
	foreach ($rs as $row) {
		if ($row['Key_name'] == 'url_title_idx')
			$has_idx = 1;
	}

	if (!$has_idx) {
		safe_query('alter ignore table `'.PFX.'textpattern` add index url_title_idx(`url_title`)');
	}

	// Remove is_default from txp_section table and make it a preference
	if (!safe_field('name', 'txp_prefs', "name = 'default_section'"))
	{
		$current_default_section = safe_field('name', 'txp_section', 'is_default=1');
		safe_insert('txp_prefs', "prefs_id = 1, name = 'default_section', val = '".doSlash($current_default_section)."', type = '2', event = 'section', html = 'text_input', position = '0'");
	}
	$cols = getThings('describe `'.PFX.'txp_section`');
	if (in_array('is_default', $cols))
	{
		safe_alter('txp_section', "DROP `is_default`");
	}

	safe_alter('txp_css', 'MODIFY css MEDIUMTEXT NOT NULL');

?>

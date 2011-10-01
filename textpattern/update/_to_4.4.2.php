<?php

/*
$LastChangedRevision: 0 $
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	$cols = getThings('describe `'.PFX.'txp_category`');
 	if (!in_array('descr', $cols))
 	{
		safe_alter('txp_category', "ADD `descr` VARCHAR( 255 ) NULL default '' AFTER `title`");
		safe_alter('txp_category', "ADD `metakey` VARCHAR( 255 ) NULL default '' AFTER `descr`");
		safe_alter('txp_category', "ADD `metadesc` VARCHAR( 500 ) NULL default '' AFTER `metakey`");
 	}
	
	$cols = getThings('describe `'.PFX.'txp_section`');
 	if (!in_array('descr', $cols))
 	{
		safe_alter('txp_section', "ADD `descr` VARCHAR( 255 ) NULL default '' AFTER `title`");
		safe_alter('txp_section', "ADD `metakey` VARCHAR( 255 ) NULL default '' AFTER `descr`");
		safe_alter('txp_section', "ADD `metadesc` VARCHAR( 500 ) NULL  default '' AFTER `metakey`");
		safe_insert('txp_section', " name = 'home', page = 'default', css = 'default', is_default = '0', in_rss = '0', on_frontpage = '0', searchable = '0', title = 'Home', descr = '', metakey = '', metadesc = ''");
 	}
	
	$cols = getThings('describe `'.PFX.'textpattern`');
 	if (!in_array('Metadesc', $cols))
 	{
		safe_alter('textpattern', "ADD `Metadesc` text NOT NULL default '' AFTER `Keywords`");
 	}

 ?>
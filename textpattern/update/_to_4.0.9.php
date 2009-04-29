<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	// support for per-user private prefs
	$cols = getThings('describe `'.PFX.'txp_prefs`');
 	if (!in_array('user_name', $cols))
 	{
		safe_alter('txp_prefs',
		"ADD `user_name` varchar(64) NOT NULL default '', DROP INDEX `prefs_idx`, ADD UNIQUE `prefs_idx` (`prefs_id`, `name`, `user_name`), ADD INDEX `user_name` (`user_name`)");
 	}

 	// remove a few global prefs in favour of future private ones
	safe_delete('txp_prefs',
		"user_name = '' AND name in ('article_list_pageby', 'author_list_pageby', 'comment_list_pageby', 'file_list_pageby', 'image_list_pageby', 'link_list_pageby', 'log_list_pageby')");

 	// use dedicated prefs function for setting custom fields
	safe_update('txp_prefs', "html='custom_set'",
		"name IN ('custom_1_set', 'custom_2_set', 'custom_3_set', 'custom_4_set', 'custom_5_set', 'custom_6_set', 'custom_7_set', 'custom_8_set', 'custom_9_set', 'custom_10_set') AND html='text_input'");

 	// send comments prefs
	safe_update('txp_prefs', "html='commentsendmail'", "name='comments_sendmail' AND html='yesnoradio'");

 	// DST prefs
	safe_update('txp_prefs', "html='is_dst'", "name='is_dst' AND html='yesnoradio'");

	// default event admin pref
	if (!safe_field('name', 'txp_prefs', "name = 'default_event'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'default_event', val = 'article', type = '1', event = 'admin', html = 'default_event', position = '150'");
	}

    // add columns for thumbnail dimensions
 	$cols = getThings('describe `'.PFX.'txp_image`');
 	if (!in_array('thumb_w', $cols))
 	{
		safe_alter('txp_image',	"ADD `thumb_w` int(8) NOT NULL default 0, ADD `thumb_h` int(8) NOT NULL default 0");
 	}

	// plugin flags
	$cols = getThings('describe `'.PFX.'txp_plugin`');
 	if (!in_array('flags', $cols))
 	{
		safe_alter('txp_plugin', "ADD flags SMALLINT UNSIGNED NOT NULL DEFAULT 0");
	}

	// default theme
	if (!safe_field('name', 'txp_prefs', "name = 'theme_name'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'theme_name', val = 'classic', type = '1', event = 'admin', html = 'themename', position = '160'");
	}

	safe_alter('txp_plugin', 'CHANGE code code MEDIUMTEXT NOT NULL, CHANGE code_restore code_restore MEDIUMTEXT NOT NULL');
	safe_alter('txp_prefs', 'CHANGE val val TEXT NOT NULL');

	// add author column to files and links,
	// boldy assuming that the publisher in charge of updating this site is the author of any existing content items.
	foreach (array('txp_file', 'txp_link') as $table)
	{
		$cols = getThings('describe `'.PFX.$table.'`');
	 	if (!in_array('author', $cols))
	 	{
			safe_alter($table, "ADD author varchar(255) NOT NULL default '', ADD INDEX author_idx (author)");
			safe_update($table, "author='$txp_user'",'1=1');
	 	}
	}

	// add indices on author columns
	foreach (array('textpattern' => 'AuthorID', 'txp_image' => 'author') as $table => $col)
	{
		$has_idx = 0;
		$rs = getRows('show index from `'.PFX.$table.'`');
		foreach ($rs as $row)
		{
			if ($row['Key_name'] == 'author_idx')
				$has_idx = 1;
		}

		if (!$has_idx)
		{
			safe_query('ALTER IGNORE TABLE `'.PFX.$table.'` ADD INDEX author_idx('.$col.')');
		}
	}
 ?>

<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	$txpplugin = getThings('describe `'.PFX.'txp_plugin`');
 	if (!in_array('load_order', $txpplugin)) {
		safe_alter('txp_plugin',
			"ADD load_order TINYINT UNSIGNED NOT NULL DEFAULT 5");
	}

	#  Enable XML-RPC server?
	if (!safe_field('name', 'txp_prefs', "name = 'enable_xmlrpc_server'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'enable_xmlrpc_server', val = 0, type = 1, event = 'admin', html = 'yesnoradio', position = 130");
	}

	if (!safe_field('name', 'txp_prefs', "name = 'smtp_from'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'smtp_from', val = '', type = 1, event = 'admin', position = 110");
	}

	if (!safe_field('val', 'txp_prefs', "name='author_list_pageby'"))
	{
		safe_insert('txp_prefs', "prefs_id = 1, name = 'author_list_pageby', val = 25, type = 2");
	}

	# Expiry datetime for articles
	$txp = getThings('describe `'.PFX.'textpattern`');
	if (!in_array('Expires',$txp)) {
		safe_alter("textpattern", "add `Expires` datetime NOT NULL default '0000-00-00 00:00:00' after `Posted`");
	}

	$has_expires_idx = 0;
	$rs = getRows('show index from `'.PFX.'textpattern`');
	foreach ($rs as $row) {
		if ($row['Key_name'] == 'Expires_idx')
			$has_expires_idx = 1;
	}

	if (!$has_expires_idx) {
		safe_query('alter ignore table `'.PFX.'textpattern` add index Expires_idx(Expires)');
	}

	#  Publish expired articles, or return 410?
	if (!safe_field('name', 'txp_prefs', "name = 'publish_expired_articles'"))
		safe_insert('txp_prefs', "prefs_id = 1, name = 'publish_expired_articles', val = '0', type = '1', event='publish', html='yesnoradio', position='130'");

	#  Searchable article fields hidden preference
	if (!safe_field('name', 'txp_prefs', "name = 'searchable_article_fields'"))
		safe_insert('txp_prefs', "prefs_id = 1, name = 'searchable_article_fields', val = 'Title, Body', type = '2', event='publish', html='text_input', position='0'");
	
?>


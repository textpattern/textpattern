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
		safe_insert('txp_prefs', "prefs_id = 1, name = 'enable_xmlrpc_server', val = '0', type = '1', event='admin', html='yesnoradio', position='130'");
?>


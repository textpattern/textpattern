<?php

/*
$HeadURL: http://svn.textpattern.com/current/textpattern/_update.php $
$LastChangedRevision: 711 $
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	if (!safe_field('name', 'txp_prefs', "name = 'lastmod_keepalive'"))
		safe_insert('txp_prefs', "prefs_id = 1, name = 'lastmod_keepalive', val = '0', type = '1', html='yesnoradio'");

?>

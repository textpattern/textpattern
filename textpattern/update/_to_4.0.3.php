<?php
/*
$HeadURL: http://svn.textpattern.com/current/textpattern/_update.php $
$LastChangedRevision: 711 $
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");

	safe_update('txp_form',"Form = CONCAT('<txp:comments_error />',Form)", "name LIKE 'comment_form'");
?>

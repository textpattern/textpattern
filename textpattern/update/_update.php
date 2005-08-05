<?php
/*
$HeadURL$
$LastChangedRevision$
*/
	@ignore_user_abort(1);
	@set_time_limit(0);

	if (!defined('TXP_UPDATE'))
		exit;

	global $thisversion;
	if (( $thisversion == '' ) || 
		( strpos($thisversion, 'g1'   )!==false) ||
		( strpos($thisversion, '1.0rc')!==false) )
	{
		include txpath.DS.'update'.DS.'_to_1.0.0.php';
	}

?>

<?php
/*
$HeadURL$
$LastChangedRevision$
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");

	@ignore_user_abort(1);
	@set_time_limit(0);
	global $txpcfg, $thisversion;

	$dbversion = mysql_get_server_info();
	//Use "ENGINE" if version of MySQL > (4.0.18 or 4.1.2)
	$tabletype = ( intval($dbversion[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$dbversion)) 
					? " ENGINE=MyISAM "
					: " TYPE=MyISAM ";
	// On 4.1 or greater use utf8-tables, if that is configures in config.php
	if ( isset($txpcfg['dbcharset']) && (intval($dbversion[0]) >= 5 || preg_match('#^4\.[1-9]#',$dbversion))) 
	{
		$tabletype .= " CHARACTER SET = ". $txpcfg['dbcharset'] ." ";
	}

	include txpath.DS.'update'.DS.'_to_1.0.0.php';
?>

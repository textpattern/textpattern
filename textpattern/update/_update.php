<?php
/*
$HeadURL$
$LastChangedRevision$
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
	global $txpcfg, $thisversion, $dbversion, $txp_using_svn, $dbupdatetime;

	function newest_file() {
		$newest = 0;
		$dp = opendir(txpath.'/update/');
		while (false !== ($file = readdir($dp))) 
		{
			if (strpos($file,"_") === 0)
				$newest = max($newest, filemtime(txpath."/update/$file"));
		}
		closedir($dp);
		return $newest;
	}

	if ( $txp_using_svn && (newest_file() < $dbupdatetime) ) 
		return;

	@ignore_user_abort(1);
	@set_time_limit(0);

	//Use "ENGINE" if version of MySQL > (4.0.18 or 4.1.2)
	// On 4.1 or greater use utf8-tables, if that is configures in config.php
	$mysqlversion = mysql_get_server_info();
	$tabletype = ( intval($mysqlversion[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$mysqlversion)) 
					? " ENGINE=MyISAM "
					: " TYPE=MyISAM ";
	if ( isset($txpcfg['dbcharset']) && (intval($mysqlversion[0]) >= 5 || preg_match('#^4\.[1-9]#',$mysqlversion))) 
	{
		$tabletype .= " CHARACTER SET = ". $txpcfg['dbcharset'] ." ";
	}

	// Update to 4.0
	if (( $dbversion == '' ) ||  
		( strpos($dbversion, 'g1'   ) === 0) ||  
		( strpos($dbversion, '1.0rc') === 0) )  
	{  
		if ((include txpath.DS.'update'.DS.'_to_1.0.0.php') !== false)
			$dbversion = '4.0';
	}  

	if ( $dbversion !== '4.0.2' )
	{  
		if ((include txpath.DS.'update'.DS.'_to_4.0.2.php') !== false)
			$dbversion = '4.0.2';
	}

	if ( $dbversion !== '4.0.3' )
	{  
		if ((include txpath.DS.'update'.DS.'_to_4.0.3.php') !== false)
			$dbversion = '4.0.3';
	}

	// keep track of updates for svn users
	safe_delete('txp_prefs',"name = 'dbupdatetime'");
	safe_insert('txp_prefs', "prefs_id=1, name='dbupdatetime',val='".max(filemtime(__FILE__),time())."', type='2'");
	// update version
	safe_delete('txp_prefs',"name = 'version'");
	safe_insert('txp_prefs', "prefs_id=1, name='version',val='$dbversion', type='2'");
	// updated, baby. So let's get the fresh prefs and send them to languages
	$event = 'prefs';
	$step = 'list_languages';
	$prefs = extract(get_prefs());
?>
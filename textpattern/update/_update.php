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

	if ( $txp_using_svn && (newest_file() <= $dbupdatetime) )
		return;

	assert_system_requirements();

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

	// Wipe out the last update check setting so the next visit to Diagnostics forces an update check,
	// which resets the message. Without this, people who upgrade in future may still see a "new
	// version available" message for some time after upgrading
	safe_delete('txp_prefs', 'name="last_update_check"');

	// Update to 4.0
	if (( $dbversion == '' ) ||
		( strpos($dbversion, 'g1'   ) === 0) ||
		( strpos($dbversion, '1.0rc') === 0) )
	{
		if ((include txpath.DS.'update'.DS.'_to_1.0.0.php') !== false)
			$dbversion = '4.0';
	}

	if (version_compare($dbversion, '4.0.2', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.0.2.php') !== false)
			$dbversion = '4.0.2';
	}

	if (version_compare($dbversion, '4.0.3', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.0.3.php') !== false)
			$dbversion = '4.0.3';
	}

	if (version_compare($dbversion, '4.0.4', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.0.4.php') !== false)
			$dbversion = '4.0.4';
	}

	if (version_compare($dbversion, '4.0.5', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.0.5.php') !== false)
			$dbversion = '4.0.5';
	}

	if (version_compare($dbversion, '4.0.6', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.0.6.php') !== false)
			$dbversion = '4.0.6';
	}

	if (version_compare($dbversion, '4.0.7', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.0.7.php') !== false)
			$dbversion = '4.0.7';
	}

	if (version_compare($dbversion, '4.0.8', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.0.8.php') !== false)
			$dbversion = '4.0.8';
	}

	if (version_compare($dbversion, '4.2.0', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.2.0.php') !== false)
			$dbversion = '4.2.0';
	}

	if (version_compare($dbversion, '4.3.0', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.3.0.php') !== false)
			$dbversion = '4.3.0';
	}

	if (version_compare($dbversion, '4.4.0', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.4.0.php') !== false)
			$dbversion = '4.4.0';
	}

	if (version_compare($dbversion, '4.4.1', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.4.1.php') !== false)
			$dbversion = '4.4.1';
	}

	if (version_compare($dbversion, '4.5.5', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.5.0.php') !== false)
			$dbversion = '4.5.5';
	}

	// Invite optional third parties to the update experience
	// Convention: Put custom code into file(s) at textpattern/update/custom/post-update-abc-foo.php
	// where 'abc' is the third party's reserved prefix (@see http://textpattern.net/wiki/index.php?title=Reserved_Plugin_Prefixes)
	// and 'foo' is whatever. The execution order among all files is undefined.
	$files = glob(txpath.'/update/custom/post-update*.php');
	if (is_array($files))
	{
		foreach ($files as $f)
		{
			include $f;
		}
	}

	// keep track of updates for svn users
	safe_delete('txp_prefs',"name = 'dbupdatetime'");
	safe_insert('txp_prefs', "prefs_id=1, name='dbupdatetime',val='".max(newest_file(),time())."', type='2'");
	// update version
	safe_delete('txp_prefs',"name = 'version'");
	safe_insert('txp_prefs', "prefs_id=1, name='version',val='$dbversion', type='2'");
	// updated, baby. So let's get the fresh prefs and send them to languages
	define('TXP_UPDATE_DONE', 1);
	$event = 'prefs';
	$step = 'list_languages';

	$prefs = get_prefs();

	extract($prefs);

?>

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
		
	// new Status field for file downloads
	$txpfile = getThings('describe '.PFX.'txp_file');
	if (!in_array('status',$txpfile)) {
		safe_alter('txp_file',
			"add status smallint NOT NULL DEFAULT '4'");
	}
	$update_files = 0;
	if (!in_array('modified',$txpfile)) {
		safe_alter('txp_file',
			"add modified timestamp NOT NULL");
		$update_files = 1;
	}
	if (!in_array('created',$txpfile)) {
		safe_alter('txp_file',
			"add created timestamp NOT NULL");
		$update_files = 1;
	}
	if (!in_array('size',$txpfile)) {
		safe_alter('txp_file',
			"add size bigint");
		$update_files = 1;
	}

	// copy existing file timestamps into the new database columns
	if ($update_files) {
		$prefs = get_prefs();
		$rs = safe_rows('*', 'txp_file', '1=1');
		foreach ($rs as $row) {
			$path = build_file_path(@$prefs['file_base_path'], @$row['filename']);
			if ($path and ($stat = stat($path))) {
				safe_update('txp_file', "created='".strftime('%Y-%m-%d %H:%M:%S', $stat['ctime'])."', modified='".strftime('%Y-%m-%d %H:%M:%S', $stat['mtime'])."', size='".doSlash(sprintf('%u', $stat['size']))."'", "id='".doSlash($row['id'])."'");
			}
		}

	}


?>

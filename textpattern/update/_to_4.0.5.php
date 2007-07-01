<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	safe_alter('txp_lang', 'DELAY_KEY_WRITE = 0');

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
			"add modified datetime NOT NULL default '0000-00-00 00:00:00'");
		$update_files = 1;
	}
	if (!in_array('created',$txpfile)) {
		safe_alter('txp_file',
			"add created datetime NOT NULL default '0000-00-00 00:00:00'");
		$update_files = 1;
	}
	if (!in_array('size',$txpfile)) {
		safe_alter('txp_file',
			"add size bigint");
		$update_files = 1;
	}
	if (!in_array('downloads',$txpfile)) {
		safe_alter('txp_file', "ADD downloads INT DEFAULT '0' NOT NULL");
	}
	if (array_intersect(array('modified', 'created'), $txpfile)) {
		safe_alter('txp_file', "MODIFY modified datetime NOT NULL default '0000-00-00 00:00:00', MODIFY created datetime NOT NULL default '0000-00-00 00:00:00'");
	}

	// copy existing file timestamps into the new database columns
	if ($update_files) {
		$prefs = get_prefs();
		$rs = safe_rows('*', 'txp_file', '1=1');
		foreach ($rs as $row) {
			$path = build_file_path(@$prefs['file_base_path'], @$row['filename']);
			if ($path and ($stat = @stat($path))) {
				safe_update('txp_file', "created='".strftime('%Y-%m-%d %H:%M:%S', $stat['ctime'])."', modified='".strftime('%Y-%m-%d %H:%M:%S', $stat['mtime'])."', size='".doSlash(sprintf('%u', $stat['size']))."'", "id='".doSlash($row['id'])."'");
			}
		}

	}

	safe_update('textpattern', "Keywords=TRIM(BOTH ',' FROM REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(Keywords,'\n',','),'\r',','),'\t',','),'    ',' '),'  ',' '),'  ',' '),' ,',','),', ',','),',,,,',','),',,',','),',,',','))", "Keywords != ''");

	// shift preferences to more intuitive spots
	// give positions, leave enough room for later additions

	safe_update('txp_prefs', "position = 20", "name in(
		'sitename',
		'comments_on_default',
		'img_dir',
		'comments_require_name',
		'syndicate_body_or_excerpt',
		'title_no_widow'
	)");

	safe_update('txp_prefs', "position = 40", "name in(
		'siteurl',
		'comments_default_invite',
		'file_base_path',
		'comments_require_email',
		'rss_how_many',
		'articles_use_excerpts'
	)");

	safe_update('txp_prefs', "position = 60", "name in('
		site_slogan',
		'comments_moderate',
		'never_display_email',
		'file_max_upload_size',
		'show_comment_count_in_feed',
		'allow_form_override'
	)");

	safe_update('txp_prefs', "position = 80", "name in(
		'production_status',
		'comments_disabled_after',
		'tempdir',
		'comment_nofollow',
		'include_email_atom',
		'attach_titles_to_permalinks'
	)");

	safe_update('txp_prefs', "position = 100", "name in(
		'gmtoffset',
		'comments_auto_append',
		'plugin_cache_dir',
		'permalink_title_format',
		'use_mail_on_feeds_id'
	)");

	safe_update('txp_prefs', "position = 120", "name in(
		'is_dst',
		'comments_mode',
		'override_emailcharset'
	)");

	safe_update('txp_prefs', "position = 120, event = 'publish'", "name = 'send_lastmod'");

	safe_update('txp_prefs', "position = 140", "name in(
		'dateformat',
		'comments_dateformat',
		'spam_blacklists',
		'lastmod_keepalive'
	)");
	
	safe_update('txp_prefs', "position = 160", "name in(
		'archive_dateformat',
		'comments_are_ol',
		'comment_means_site_updated',
		'ping_weblogsdotcom'
	)");

	safe_update('txp_prefs', "position = 180", "name in('permlink_mode','comments_sendmail','ping_textpattern_com')");
	safe_update('txp_prefs', "position = 200", "name in('use_textile','expire_logs_after')");
	safe_update('txp_prefs', "position = 220", "name in('logging','use_dns')");
	safe_update('txp_prefs', "position = 240", "name in('use_comments','max_url_len')");

	safe_update('txp_prefs', "position = 260", "name = 'use_plugins'");
	safe_update('txp_prefs', "position = 280", "name = 'admin_side_plugins'");
	safe_update('txp_prefs', "position = 300", "name = 'allow_page_php_scripting'");
	safe_update('txp_prefs', "position = 320", "name = 'allow_article_php_scripting'");
	safe_update('txp_prefs', "position = 340", "name = 'allow_raw_php_scripting'");

	safe_update('txp_prefs', "position = 120, type = 1", "name = 'comments_disallow_images'");

	safe_update('txp_prefs', "event = 'comments'", "name in(
		'never_display_email',
		'comment_nofollow',
		'spam_blacklists',
		'comment_means_site_updated'
	)");

	safe_update('txp_prefs', "event = 'feeds'", "name in(
		'syndicate_body_or_excerpt',
		'rss_how_many',
		'show_comment_count_in_feed',
		'include_email_atom',
		'use_mail_on_feeds_id'
	)");
	
	# 'Textile links' feature removed due to unclear specs.
	safe_delete('txp_prefs', "event='link' and name='textile_links'");

	#  Use TextileRestricted lite/fat in comments?
	if (!safe_field('name', 'txp_prefs', "name = 'comments_use_fat_textile'"))
		safe_insert('txp_prefs', "prefs_id = 1, name = 'comments_use_fat_textile', val = '0', type = '1', event='comments', html='yesnoradio', position='130'");


?>

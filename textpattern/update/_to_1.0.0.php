<?php
/*
$HeadURL: http://svn.textpattern.com/current/textpattern/_update.php $
$LastChangedRevision: 711 $
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");

	safe_delete("txp_category","name=''");
	safe_delete("txp_category","name=' '");

	$txpcat = getThings('describe `'.PFX.'txp_category`');

	if (!in_array('id',$txpcat)) {
		safe_alter('txp_category', 
			'add `id` int(6) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
	}

	if (!in_array('parent',$txpcat)) {
		safe_alter("txp_category", "add `parent` varchar(64) not null default ''");
	}

	if (!in_array('lft',$txpcat)) {
		safe_alter("txp_category", "add `lft` int(6) not null default '0'");
	}

	if (!in_array('rgt',$txpcat)) {
		safe_alter("txp_category", "add `rgt` int(6) not null default '0'");
	}

	if (in_array('level',$txpcat)) {
		safe_alter("txp_category", "drop `level`");
	}


	$txp = getThings('describe `'.PFX.'textpattern`');

	if (!in_array('Keywords',$txp)) {
		safe_alter("textpattern", "add `Keywords` varchar(255) not null");
	}
	
	if (in_array('Listing1',$txp) && !in_array('textile_body',$txp)) {
		safe_alter("textpattern",
						"change Listing1 textile_body INT(2) DEFAULT '1' NOT NULL");
	}

	if (in_array('Listing2',$txp) && !in_array('textile_excerpt',$txp)) {
		safe_alter("textpattern",
						"change Listing2 textile_excerpt INT(2) DEFAULT '1' NOT NULL");
	}

	if (!in_array('url_title',$txp)) {
		safe_alter("textpattern", "add `url_title` varchar(255) not null");
	}

	if (!in_array('Excerpt',$txp)) {
		safe_alter("textpattern", "add `Excerpt` mediumtext not null after `Body_html`");
	}

		// Excerpt_html added in 1.0
	if (!in_array('Excerpt_html',$txp)) {
		safe_alter("textpattern", "add `Excerpt_html` mediumtext not null after `Excerpt` ");
	}

		// comments count cache field
	if (!in_array('comments_count',$txp)) {
		safe_alter("textpattern", "add `comments_count` int(8) not null after `AnnotateInvite` ");
	}

	// custom fields added for g1.19

	if (!in_array('custom_1',$txp)) {
		safe_alter("textpattern", "add `custom_1` varchar(255) not null");
	}

	if (!in_array('custom_2',$txp)) {
		safe_alter("textpattern", "add `custom_2` varchar(255) not null");
	}

	if (!in_array('custom_3',$txp)) {
		safe_alter("textpattern", "add `custom_3` varchar(255) not null");
	}

	if (!in_array('custom_4',$txp)) {
		safe_alter("textpattern", "add `custom_4` varchar(255) not null");
	}

	if (!in_array('custom_5',$txp)) {
		safe_alter("textpattern", "add `custom_5` varchar(255) not null");
	}

	if (!in_array('custom_6',$txp)) {
		safe_alter("textpattern", "add `custom_6` varchar(255) not null");
	}

	if (!in_array('custom_7',$txp)) {
		safe_alter("textpattern", "add `custom_7` varchar(255) not null");
	}

	if (!in_array('custom_8',$txp)) {
		safe_alter("textpattern", "add `custom_8` varchar(255) not null");
	}

	if (!in_array('custom_9',$txp)) {
		safe_alter("textpattern", "add `custom_9` varchar(255) not null");
	}

	if (!in_array('custom_10',$txp)) {
		safe_alter("textpattern", "add `custom_10` varchar(255) not null");
	}



	$txpsect = getThings('describe `'.PFX.'txp_section`');

	if (!in_array('searchable',$txpsect)) {
		safe_alter("txp_section", "add `searchable` int(2) not null default 1");
	}

	$txpuser = getThings('describe `'.PFX.'txp_users`');

	if (!in_array('nonce',$txpuser)) {
		safe_alter("txp_users", "add `nonce` varchar(64) not null");		
	};


	// 1.0rc: checking nonce in txp_users table

	$txpusers = safe_rows_start('name, nonce','txp_users','1');
	if ($txpusers) {
		while ($a = nextRow($txpusers)) {
			extract($a);
			if (!$nonce){
				$nonce = md5( uniqid( rand(), true ) );
				safe_update('txp_users',"nonce='$nonce'", "name = '$name'");
			}
		}
	}

	// 1.0rc: expanding password field in txp_users
	
	safe_alter('txp_users',"CHANGE `pass` `pass` VARCHAR( 128 ) NOT NULL");

	safe_alter('textpattern',"CHANGE `Body` `Body` MEDIUMTEXT NOT NULL");

	safe_alter('textpattern',"CHANGE `Body_html` `Body_html` MEDIUMTEXT NOT NULL");

	safe_alter('textpattern',"CHANGE `Excerpt` `Excerpt` TEXT NOT NULL");

	$popcom = fetch("*",'txp_form','name',"popup_comments");
	
	if (!$popcom) {
	
		$popform = <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="Stylesheet" href="<txp:css />" type="text/css" />
	<title><txp:page_title /></title>
</head>
<body>
<div style="text-align: left; padding: 1em; width:300px">

	<txp:popup_comments />

	</div>
</body>
</html>
eod;
		$popform = addslashes($popform);
		safe_insert("txp_form","name='popup_comments',type='comment',Form='$popform'");
	}
	
	safe_update("txp_category", "lft=0,rgt=0","name!='root'");

	safe_delete("txp_category", "name='root'");

	safe_update("txp_category", "parent='root'","parent = ''");

	safe_insert("txp_category", "name='root',parent='',type='article',lft=1,rgt=0");
	rebuild_tree('root',1,'article');

	safe_insert("txp_category", "name='root',parent='',type='link',lft=1,rgt=0");
	rebuild_tree('root',1,'link');

	safe_insert("txp_category", "name='root',parent='',type='image',lft=1,rgt=0");
	rebuild_tree('root',1,'image');

	if (!safe_field('val','txp_prefs',"name='article_list_pageby'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='article_list_pageby',val=25");
	}

	if (!safe_field('val','txp_prefs',"name='link_list_pageby'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='link_list_pageby',val=25");
	}

	if (!safe_field('val','txp_prefs',"name='image_list_pageby'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='image_list_pageby',val=25");
	}

	if (!safe_field('val','txp_prefs',"name='log_list_pageby'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='log_list_pageby',val=25");
	}

	if (!safe_field('val','txp_prefs',"name='comment_list_pageby'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='comment_list_pageby',val=25");
	}

	if (!safe_field('val','txp_prefs',"name='permlink_mode'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='permlink_mode',val='section_id_title'");
	}

	if (!safe_field('val','txp_prefs',"name='comments_are_ol'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='comments_are_ol',val='1'");
	}

	if (!safe_field('name','txp_prefs',"name='path_to_site'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='path_to_site',val=''");
	}

	// 1.0: need to get non-manually set url-only titles into the textpattern table,
	// so we can start using title as an url search option

	$rs = mysql_query("select ID, Title from `".PFX."textpattern` where url_title like ''");
	
		while ($a = mysql_fetch_array($rs)){
			extract($a);
			$url_title = addslashes(stripSpace($Title));
			safe_update("textpattern","url_title = '$url_title'","ID=$ID");
		}

		
	// 1.0: properly i18n
	//Change current language names by language codes
		$lang = fetch('val','txp_prefs','name','language');
		switch($lang){
	 	case 'czech':
	  		$rs = safe_update("txp_prefs", "val= 'cs-cs'", "name='language' AND val= 'czech'");
	 	break;
		case 'danish':
			$rs = safe_update("txp_prefs", "val= 'da-da'", "name='language' AND val= 'danish'");
		break;
		case 'dutch':
			$rs = safe_update("txp_prefs", "val= 'nl-nl'", "name='language' AND val= 'dutch'");
		break;
		case 'finish':
			$rs = safe_update("txp_prefs", "val= 'fi-fi'", "name='language' AND val= 'finish'");
		break;
		case 'french':
			$rs = safe_update("txp_prefs", "val= 'fr-fr'", "name='language' AND val= 'french'");
		break;
		case 'german':
			$rs = safe_update("txp_prefs", "val= 'de-de'", "name='language' AND val= 'german'");
		break;
		case 'italian':
			$rs = safe_update("txp_prefs", "val= 'it-it'", "name='language' AND val= 'italian'");
		break;
		case 'polish':
			$rs = safe_update("txp_prefs", "val= 'pl-pl'", "name='language' AND val= 'polish'");
		break;
		case 'portuguese':
			$rs = safe_update("txp_prefs", "val= 'pt-pt'", "name='language' AND val= 'portuguese'");
		break;
		case 'russian':
			$rs = safe_update("txp_prefs", "val= 'ru-ru'", "name='language' AND val= 'russian'");
		break;
		case 'scotts':
			//I'm not sure of this one
			$rs = safe_update("txp_prefs", "val= 'gl-gl'", "name='language' AND 'val' = 'scotts'");
		break;
		case 'spanish':
			$rs = safe_update("txp_prefs", "val= 'es-es'", "name='language' AND val= 'spanish'");
		break;
		case 'swedish':
			$rs = safe_update("txp_prefs", "val= 'sv-sv'", "name='language' AND val= 'swedish'");
		break;
		case 'tagalog':
			$rs = safe_update("txp_prefs", "val= 'tl-tl'", 	"name='language' AND val= 'tagalog'");
		break;
	        case 'english':
	        default:
			$rs = safe_update("txp_prefs", "val= 'en-gb'", "name='language' AND val= 'english'");
		break;
	}

	// 1.0: new time zone offset
	//If we check for a val, and the val is 0, this add another empty one
	if (!safe_field('name','txp_prefs',"name='is_dst'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='is_dst',val=0");
	}

	// FIXME: this presupposes 'gmtoffset' won't be set at clean install (RC4+ probably will)

	if (!safe_field('val','txp_prefs',"name='gmtoffset'")) {
		$old_offset = safe_field('val', 'txp_prefs', "name='timeoffset'");
		$serveroffset = gmmktime(0,0,0) - mktime(0,0,0);
		$gmtoffset = sprintf("%+d", $serveroffset + $old_offset);
		safe_insert('txp_prefs',"prefs_id=1,name='gmtoffset',val='$gmtoffset'");
	}

	// 1.0: locale support
	if (!safe_field('val','txp_prefs',"name='locale'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='locale',val='en_GB'");
	}

	// 1.0: temp dir
	if (!safe_field('val','txp_prefs',"name='tempdir'")) {
		$tempdir = addslashes(find_temp_dir());
		safe_insert('txp_prefs',"prefs_id=1,name='tempdir',val='$tempdir'");
	}
	
	//non image file upload tab:
	if (!safe_field('val', 'txp_prefs',"name='file_list_pageby'")){
		safe_insert('txp_prefs',"val=25,name='file_list_pageby',prefs_id=1");
	}
	
	// 1.0: max file upload size
	if (!safe_field('val', 'txp_prefs',"name='file_max_upload_size'")){	
		safe_insert('txp_prefs',"prefs_id=1,name='file_max_upload_size',val=2000000");
	}

	// 1.0: txp_file root cat
	if (!safe_field('name', 'txp_category',"type='file' AND name='root'")){
		safe_insert('txp_category',"name='root',type='file',lft=1,rgt=0");
	}
	rebuild_tree('root',1,'file');
	
	// 1.0: txp_file folder
	if (!safe_field('val', 'txp_prefs',"name='file_base_path'")){
		safe_insert('txp_prefs',"val='$tempdir',name='file_base_path',prefs_id=1");
	}
	
	// 1.0: txp_file table
	if (!safe_query("SELECT 1 FROM `".PFX."txp_file` LIMIT 0")) {
		// do install
		safe_query("CREATE TABLE `".PFX."txp_file` ( 
				`id` int(11) NOT NULL auto_increment,
				`filename` varchar( 255 ) NOT NULL default '',
				`category` varchar( 255 ) NOT NULL default '',
				`permissions` varchar( 32 ) NOT NULL DEFAULT '0',
				`description` text NOT NULL default '',
				`downloads` int(4) unsigned NOT NULL default '0',
				PRIMARY KEY ( `id` ) ,
				UNIQUE KEY `filename` ( `filename` ) 
			) $tabletype PACK_KEYS=0 AUTO_INCREMENT=1 ");
	}
	
	if (!safe_field('name', 'txp_form', "type='file'")){
		safe_insert('txp_form',"
			name='files',
			type='file',
			Form='<txp:text item=\"file\" />: \n<txp:file_download_link>\n<txp:file_download_name /> [<txp:file_download_size format=\"auto\" decimals=\"2\" />]\n</txp:file_download_link>\n<br />\n<txp:text item=\"category\" />: <txp:file_download_category /><br />\n<txp:text item=\"download\" />: <txp:file_download_downloads />'");
	}
	//eof: non image file upload tab

	// 1.0: improved comment spam nonce
	$txpnonce = getThings('describe `'.PFX.'txp_discuss_nonce`');
	if (!in_array('secret', $txpnonce))
		safe_alter('txp_discuss_nonce', "ADD `secret` varchar(255) NOT NULL default ''");

	// 1.0: flag for admin-side plugins
	$txpplugin = getThings('describe `'.PFX.'txp_plugin`');
	if (!in_array('type', $txpplugin))
		safe_alter('txp_plugin', "ADD `type` int(2) NOT NULL default '0'");

	// 1.0: log status & method
	$txplog = getThings('describe `'.PFX.'txp_log`');
	if (!in_array('status', $txplog))
		safe_alter('txp_log', "ADD `status` int NOT NULL default '200'");
	if (!in_array('method', $txplog))
		safe_alter('txp_log', "ADD `method` varchar(16) NOT NULL default 'GET'");
	if (!in_array('ip', $txplog))
		safe_alter('txp_log', "ADD `ip` varchar(16) NOT NULL default ''");

	// 1.0: need to get Excerpt_html values into the textpattern table,
	// so, catch empty ones and populate them
	$rs = mysql_query("select ID, Excerpt, textile_excerpt from `".PFX."textpattern` where Excerpt_html like ''");
	require_once txpath.'/lib/classTextile.php';
	$textile = new Textile();
	while ($a = @mysql_fetch_assoc($rs)){		
		extract($a);		
		$lite = ($textile_excerpt)? '' : 1;
		$Excerpt_html = $textile->TextileThis($Excerpt,$lite);
		safe_update("textpattern","Excerpt_html = '$Excerpt_html'","ID=$ID");
	}
	
	//1.0 feed unique ids
	//blog unique id
	if (!safe_field('val','txp_prefs',"name='blog_uid'"))
	{
		$prefs['blog_uid'] = md5(uniqid(rand(),true));
		safe_insert('txp_prefs',"name='blog_uid', val='".$prefs['blog_uid']."', prefs_id='1'");
	}
	if (!safe_field('val','txp_prefs',"name='blog_mail_uid'"))
	{
		$mail = safe_field('email', 'txp_users', "privs='1' LIMIT 1");
		safe_insert('txp_prefs',"name='blog_mail_uid', val='$mail', prefs_id='1'");
	}	
	if (!safe_field('val','txp_prefs',"name='blog_time_uid'"))
	{
		safe_insert('txp_prefs',"name='blog_time_uid', val='".date("Y")."', prefs_id='1'");
	}
	//Articles unique id
	if (!in_array('uid',$txp))
	{
		safe_alter('textpattern',"add `uid` varchar(32) not null");
		safe_alter('textpattern',"add `feed_time` DATE not null DEFAULT '0000-00-00'");
		
		$rs = safe_rows_start('ID,Posted','textpattern','1');
		if ($rs)
		{
			while ($a = nextRow($rs))
			{
				$feed_time = substr($a['Posted'],0,10);
				safe_update('textpattern',"uid='".md5(uniqid(rand(),true))."', feed_time='$feed_time'","ID={$a['ID']}");
			}
		}
	}

	// 1.0: populate comments_count field

		$rs = safe_rows_start('parentid, count(*) as thecount','txp_discuss','visible=1 group by parentid');
		if ($rs) {
			while ($a = nextRow($rs)) {
				safe_update('textpattern',"comments_count=".$a['thecount'],"ID=".$a['parentid']);
			}
		}


	
	// 1.0: Human-friendly title for sections and categories, to solve i18n problems
	if (!in_array('title',$txpsect)) {
		safe_alter("txp_section", "add `title` varchar(255) not null default ''");
	}
	if (!in_array('title',$txpcat)) {
		safe_alter("txp_category", "add `title` varchar(255) not null default ''");
	}	
	if (safe_count('txp_section', "title=''") > 0)
		safe_update('txp_section', 'title=name', "title=''");
	if (safe_count('txp_category', "title=''") > 0)
		safe_update('txp_category', 'title=name', "title=''");

	// 1.0: Unique key and 'type' field for the txp_prefs table
	$has_prefs_idx = 0;
	$rs = getRows('show index from `'.PFX.'txp_prefs`');
	foreach ($rs as $row)
		if ($row['Key_name'] == 'prefs_idx')
			$has_prefs_idx = 1;
	if (!$has_prefs_idx)
		safe_query('alter ignore table `'.PFX.'txp_prefs` add unique prefs_idx(prefs_id,name)');
		
	$txpprefs = getThings('describe `'.PFX.'txp_prefs`');
	if (!in_array('type', $txpprefs))
		safe_alter('txp_prefs', "add `type` smallint unsigned not null default '2'");
	# update the updated with default hidden type for old plugins prefs	
	safe_alter('txp_prefs',"change `type` `type` smallint unsigned not null default '2'");
	if (!in_array('event', $txpprefs))
		safe_alter('txp_prefs', "add `event` varchar(12) not null default 'publish'");
	if (!in_array('html', $txpprefs))
		safe_alter('txp_prefs', "add `html` varchar(64) not null default ''");
	if (!in_array('position', $txpprefs))
	{
		safe_alter('txp_prefs', "add `position` smallint unsigned not null default '0'");
		
		# add new column values to prefs
			$prefs_new_cols = array(
				'attach_titles_to_permalinks' => array('html' => 'yesnoradio', 'event'=> 'publish', 'type' => '1', 'position' => '1'),
				'sitename' => array('html' => 'text_input', 'event'=> 'publish', 'type' => '0', 'position' => '1'),
				'siteurl' => array('html' => 'text_input', 'event'=> 'publish', 'type' => '0', 'position' => '2'),
				'site_slogan'  => array('html' =>'text_input', 'event' => 'publish', 'type' => '0', 'position' => '3'),
				'language' => array('html' => 'languages','event'=> 'publish', 'type' => '0', 'position' => '4'),
				'gmtoffset' => array('html' => 'gmtoffset_select','event'=> 'publish', 'type' =>  '0', 'position' => '5'),
				'is_dst' => array('html' => 'yesnoradio','event'=> 'publish', 'type' => '0', 'position' => '6'),
				'dateformat' => array('html' => 'dateformats','event'=> 'publish', 'type' => '0', 'position' => '7'),
				'archive_dateformat' => array('html' => 'dateformats','event'=> 'publish', 'type' => '0', 'position' => '8'),
				'permlink_mode' => array('html' => 'permlinkmodes','event'=> 'publish', 'type' => '0', 'position' => '9'),
				'send_lastmod' => array('html' => 'yesnoradio','event'=> 'admin', 'type' => '1', 'position' => '0'),
				'ping_weblogsdotcom' => array('html' => 'yesnoradio','event'=> 'publish', 'type' => '1', 'position' => '0'),
				'use_comments' => array('html' => 'yesnoradio','event'=> 'publish', 'type' => '0', 'position' => '12'),
				'logging' => array('html' => 'logging','event'=> 'publish', 'type' => '0', 'position' => '10'),
				'use_textile' => array('html' => 'pref_text','event'=> 'publish', 'type' => '0', 'position' => '11'),
				'tempdir' => array('html' => 'text_input','event'=> 'admin', 'type' => '1', 'position' => '0'),
				'file_base_path' => array('html' => 'text_input','event'=> 'admin', 'type' => '1', 'position' => '0'),
				'file_max_upload_size' => array('html' => 'text_input','event'=> 'admin', 'type' => '1', 'position' => '0'),		
				'comments_moderate' => array('html' => 'yesnoradio','event'=> 'comments', 'type' => '0', 'position' => '13'),
				'comments_on_default' => array('html' => 'yesnoradio','event'=> 'comments', 'type' => '0', 'position' => '14'),
				'comments_are_ol' => array('html' => 'yesnoradio','event'=> 'comments', 'type' => '0', 'position' => '15'),
				'comments_sendmail' => array('html' => 'yesnoradio','event'=> 'comments', 'type' => '0', 'position' => '16'),
				'comments_disallow_images' => array('html' => 'yesnoradio','event'=> 'comments', 'type' => '0', 'position' => '17'),
				'comments_default_invite' => array('html' => 'text_input','event'=> 'comments', 'type' => '0', 'position' => '18'),
				'comments_dateformat' => array('html' => 'dateformats','event'=> 'comments', 'type' => '0', 'position' => '19'),
				'comments_mode' => array('html' => 'commentmode','event'=> 'comments', 'type' => '0', 'position' => '20'),
				'comments_disabled_after' => array('html' => 'weeks','event'=> 'comments', 'type' => '0', 'position' => '21'),
				'img_dir' => array('html' => 'text_input','event'=> 'admin', 'type' => '1', 'position' => '0'),
				'rss_how_many' => array('html' => 'text_input','event'=> 'admin', 'type' => '1', 'position' => '0'),
			);
			
			foreach ($prefs_new_cols as $pref_key => $pref_val)
			{
				safe_update('txp_prefs', "html='$pref_val[html]',event='$pref_val[event]',type='$pref_val[type]', position='$pref_val[position]'", "name='$pref_key' AND prefs_id='1'");
			}
			
			$prefs_hidden_rows = array('prefs_id','use_categories','use_sections','path_from_root','url_mode','record_mentions',
			'locale','file_base_path','lastmod','version','path_to_site','dbupdatetime','timeoffset','article_list_pageby',
			'blog_mail_uid','blog_time_uid','blog_uid','comment_list_pageby','file_list_pageby','image_list_pageby','link_list_pageby',
			'log_list_pageby',);
			
			foreach ($prefs_hidden_rows as $hidden_pref)
			{
				safe_update('txp_prefs', "type='2'", "name='$hidden_pref' AND prefs_id='1'");
			}
			
			global $txpac;
			#advanced prefs
			foreach ($txpac as $key => $val)
			{
				if (!in_array($key, array_keys($prefs)))
				{
					switch ($key)
					{
						case'custom_1_set':
						case'custom_2_set':
						case'custom_3_set':
						case'custom_4_set':
						case'custom_5_set':
						case'custom_6_set':
						case'custom_7_set':
						case'custom_8_set':
						case'custom_9_set':
						case'custom_10_set':
							$evt = 'custom';
							$html = 'text_input';
						break;
						
						case 'edit_raw_css_by_default':
							$evt = 'css';
							$html = 'yesnoradio';
						break;
						case 'spam_blacklists':
						case 'expire_logs_after':
						case 'max_url_len':
							$html = 'text_input';
							$evt = 'publish';
						break;
						case 'textile_links':
							$html = 'yesnoradio';	
							$evt = 'link';
						break;
						case 'show_article_category_count':
							$html = 'yesnoradio';	
							$evt = 'category';				
						break;
						case 'comments_require_name':
						case 'comments_require_email':
							$html = 'yesnoradio';	
							$evt = 'comments';				
						break;
						default:
							$html = 'yesnoradio';
							$evt = 'publish';
						break;				
					}
					safe_insert('txp_prefs',"val = '$val', name = '$key' , prefs_id ='1', type='1', html='$html', event='$evt'");
				}
			}		
	}
	
	safe_alter('txp_prefs',"CHANGE `html` `html` VARCHAR( 64 ) DEFAULT 'text_input' NOT NULL");
	safe_update('txp_prefs',"html='text_input'","html=''");
		
	if (!fetch('form','txp_form','name','search_results')) {
		$form = <<<EOF
<h3><txp:permlink><txp:title /></txp:permlink></h3>
<p><txp:search_result_excerpt /><br/>
<small><txp:permlink><txp:permlink /></txp:permlink> &middot;
<txp:posted /></small></p>
EOF;
		safe_insert('txp_form', "name='search_results', type='article', Form='$form'");
	}
	
	
	if (!safe_query("SELECT 1 FROM `".PFX."txp_lang` LIMIT 0")) {
		// do install
		safe_query("CREATE TABLE `".PFX."txp_lang` (
			`id` INT( 9 ) NOT NULL AUTO_INCREMENT ,
			`lang` VARCHAR(16),
			`name` VARCHAR(64),
			`event` VARCHAR( 64 ) ,
			`data` TINYTEXT,
			`lastmod` timestamp,
			PRIMARY KEY ( `id` ),
			UNIQUE INDEX (`lang`,`name`),
			INDEX (`lang`, `event`)
			) $tabletype;");

			require_once txpath.'/lib/IXRClass.php';		
	
			$client = new IXR_Client('http://rpc.textpattern.com');	

			if (!$client->query('tups.getLanguage',$prefs['blog_uid'],LANG))
			{				
				echo '<p style="color:red">Error trying to install language. Please, try it again again.<br /> 
				If problem connecting to the RPC server persists, you can go to <a href="http://rpc.textpattern.com/lang/">http://rpc.textpattern.com/lang/</a>, download the
				desired language file and place it in the /lang/ directory of your textpattern install. You can then install the language from file.</p>';
			}else {
				$response = $client->getResponse();
				$lang_struct = unserialize($response);
				function install_lang_key($value, $key)
				{			
					$q = "name='$value[name]', event='$value[event]', data='$value[data]', lastmod='".strftime('%Y%m%d%H%M%S',$value['uLastmod'])."'";					
					safe_insert('txp_lang',$q.", lang='".LANG."'");
				}			
				array_walk($lang_struct,'install_lang_key');
			}		
	}

	$maxpos = safe_field('max(position)', 'txp_prefs', '1');
	// 1.0: production_status setting to control error reporting
	if (!safe_field('val','txp_prefs',"name='production_status'"))
	{		
		safe_insert('txp_prefs',"name='production_status', val='testing', prefs_id='1', type='0', position='".doSlash($maxpos)."', html='prod_levels'");
	}
	
	# multiply position on prefs to allow easy reordering
	if(intval($maxpos) < 100)
	{
		safe_update('txp_prefs','position = position*10','1');
	}
	# remove, remove
	if (safe_field('name','txp_prefs',"name='logs_expire'"))
	{
		safe_delete('txp_prefs',"name='logs_expire'");
	}	

	// Let's make this visible in advanced prefs
	safe_update('txp_prefs',"type = '1'","name = 'file_base_path'");

	// 1.0: add option to override charset for emails (ISO-8559-1 instead of UTF-8)
	if (!safe_field('val','txp_prefs',"name='override_emailcharset'"))
	{		
		safe_insert('txp_prefs',"name='override_emailcharset', val='0', prefs_id='1', type='1', event='admin', position='".doSlash($maxpos)."', html='yesnoradio'");
	}


	if (safe_field('val', 'txp_prefs', "name='comments_auto_append'") === false) {
		safe_insert('txp_prefs',"val = '1', name = 'comments_auto_append' , prefs_id ='1', type='0', html='yesnoradio', event='comments', position='211'");

		$form = <<<EOF
<txp:comments />
<txp:if_comments_allowed>
<txp:comments_form />
</txp:if_comments_allowed>
EOF;
		safe_insert('txp_form', "name='comments_display', type='article', Form='$form'");
	}

	// /tmp is bad for permanent storage of files, 
	// if no files are uploaded yet, switch to the files directory in the top-txp dir.
	if (!safe_count('txp_file',"1")){ 
		$tempdir = find_temp_dir();
		if ($tempdir == safe_field('val','txp_prefs',"name='file_base_path'"))
			safe_update('txp_prefs',"val='".doSlash(dirname(txpath).DS.'files')."',prefs_id=1","name='file_base_path'");
	}

	// After this point the changes after RC4

	// let's get the advanced fields in the right order
	for ($i = 1; $i <= 10; $i++) {
		safe_update("txp_prefs","position=$i","name='custom_${i}_set'");
	}


	// RC4: adding privs table

	if (!safe_query("DESCRIBE `".PFX."txp_priv`")) {
		safe_query("CREATE TABLE `".PFX."txp_priv` (
			`id` INT( 8 ) NOT NULL AUTO_INCREMENT ,
			`priv` VARCHAR( 128 ) NOT NULL ,
			`1` TINYINT( 1 ) NOT NULL ,
			`2` TINYINT( 1 ) NOT NULL ,
			`3` TINYINT( 1 ) NOT NULL ,
			`4` TINYINT( 1 ) NOT NULL ,
			`5` TINYINT( 1 ) NOT NULL ,
			`6` TINYINT( 1 ) NOT NULL ,
			PRIMARY KEY ( `id` )
		) $tabletype");

		include txpath.'/lib/admin_config.php';
		
		foreach($txp_permissions as $a => $b) {
			$privs = explode(',',$b);
			foreach ($privs as $c) $sets[] = "`$c` = 1";
			safe_insert("txp_priv","priv='$a', ".join(', ',$sets));
			unset($sets);
		}
	}

	// index ip column in txp_log

		$ipidxset = false;
		$i = safe_show('index','txp_log');
		foreach ($i as $a => $b) {
			if ($b['Column_name'] == 'ip') $ipidxset = true;
		}
		if (!$ipidxset) safe_query("alter table `".PFX."txp_log` ADD INDEX `ip` (`ip`)");

	// Language selection moves to Manage languages, Hide it from prefs.
	safe_update("txp_prefs", "type=2", "name='language'");

	// Show gmt-selection in prefs
	safe_update('txp_prefs',"type=0, html='gmtoffset_select', position=50","name='gmtoffset'");

	if (!safe_field('name', 'txp_prefs', "prefs_id=1, name='plugin_cache_dir'")) {
		$maxpos = safe_field('max(position)', 'txp_prefs', '1');
		safe_insert('txp_prefs',"name='plugin_cache_dir', val='', prefs_id='1', type='1', event='admin', position='".doSlash($maxpos)."', html='text_input'");
	}

	// update version
	safe_delete('txp_prefs',"name = 'version'");
	safe_insert('txp_prefs', "prefs_id=1, name='version',val='4.0', type='2'");
?>

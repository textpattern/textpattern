 <?php

	safe_delete("txp_category","name=''");
	safe_delete("txp_category","name=' '");

	$txpcat = getThings('describe '.PFX.'txp_category');

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


	$txp = getThings('describe '.PFX.'textpattern');

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



	$txpsect = getThings('describe '.PFX.'txp_section');

	if (!in_array('searchable',$txpsect)) {
		safe_alter("txp_section", "add `searchable` int(2) not null default 1");
	}

	$txpuser = getThings('describe '.PFX.'txp_users');

	if (!in_array('nonce',$txpuser)) {
		safe_alter("txp_users", "add `nonce` varchar(64) not null");		
	};


	// 1.0rc: checking nonce in txp_users table

	$txpusers = safe_rows('name, nonce','txp_users','1');
	if ($txpusers) {
		foreach($txpusers as $a) {
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

	safe_delete('txp_prefs',"name = 'version'");
	safe_insert('txp_prefs', "prefs_id=1, name='version',val='$thisversion'");
	
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
		safe_insert('txp_prefs',"prefs_id=1,name='permlink_mode',val='section_id'");
	}

	if (!safe_field('val','txp_prefs',"name='comments_are_ol'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='comments_are_ol',val='1'");
	}

	if (!safe_field('val','txp_prefs',"name='path_to_site'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='path_to_site',val=''");
	}
/*
	foreach($txpac as $k => $v) {
		if (safe_count('txp_prefs',"`name`='$k'") == 0) {
			safe_insert("txp_prefs","`prefs_id`=1,`name`='$k',`val`='$v'");
		}
	}
*/
	// 1.0: need to get non-manually set url-only titles into the textpattern table,
	// so we can start using title as an url search option

	$rs = mysql_query("select ID, Title from ".PFX."textpattern where url_title like ''");
	
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
			$rs = safe_update("txp_prefs", "val= 'de-de'", "name='language' AND val= 'italian'");
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
	if (!safe_field('val','txp_prefs',"name='is_dst'")) {
		safe_insert('txp_prefs',"prefs_id=1,name='is_dst',val=0");
	}

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

// updated, baby.

?>

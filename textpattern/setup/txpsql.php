<?php

/*
$HeadURL$
$LastChangedRevision$
*/


if (!defined('TXP_INSTALL'))
	exit;

@ignore_user_abort(1);
@set_time_limit(0);

mysql_connect($dhost,$duser,$dpass);
mysql_select_db($ddb);

$result = mysql_query("describe `".PFX."textpattern`");
if ($result) die("Textpattern database table already exist. Can't run setup.");


$version = mysql_get_server_info();
//Use "ENGINE" if version of MySQL > (4.0.18 or 4.1.2)
$tabletype = ( intval($version[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$version)) 
				? " ENGINE=MyISAM " 
				: " TYPE=MyISAM ";

// On 4.1 or greater use utf8-tables
if ( isset($dbcharset) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version))) 
{
	$tabletype .= " CHARACTER SET = $dbcharset ";
	if (isset($dbcollate)) 
		$tabletype .= " COLLATE $dbcollate ";
	mysql_query("SET NAMES ".$dbcharset);
}

// Default to messy URLs if we know clean ones won't work
$permlink_mode = 'section_id_title';
if (is_callable('apache_get_modules')) {
	$modules = apache_get_modules();
	if (!in_array('mod_rewrite', $modules))
		$permlink_mode = 'messy';
}
else {
	$server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST'])
		? ( (@$_SERVER['SERVER_SOFTWARE']) ?  @$_SERVER['SERVER_SOFTWARE'] :  $_SERVER['HTTP_HOST'] )
		: '';
   if (!stristr($server_software, 'Apache'))
		$permlink_mode = 'messy';
}


$create_sql = array();

$create_sql[] = "CREATE TABLE `".PFX."textpattern` (
  `ID` int(11) NOT NULL auto_increment,
  `Posted` datetime NOT NULL default '0000-00-00 00:00:00',
  `AuthorID` varchar(64) NOT NULL default '',
  `LastMod` datetime NOT NULL default '0000-00-00 00:00:00',
  `LastModID` varchar(64) NOT NULL default '',
  `Title` varchar(255) NOT NULL default '',
  `Title_html` varchar(255) NOT NULL default '',
  `Body` mediumtext NOT NULL,
  `Body_html` mediumtext NOT NULL,
  `Excerpt` text NOT NULL,
  `Excerpt_html` mediumtext NOT NULL,
  `Image` varchar(255) NOT NULL default '',
  `Category1` varchar(128) NOT NULL default '',
  `Category2` varchar(128) NOT NULL default '',
  `Annotate` int(2) NOT NULL default '0',
  `AnnotateInvite` varchar(255) NOT NULL default '',
  `comments_count` int(8) NOT NULL default '0',
  `Status` int(2) NOT NULL default '4',
  `textile_body` int(2) NOT NULL default '1',
  `textile_excerpt` int(2) NOT NULL default '1',
  `Section` varchar(64) NOT NULL default '',
  `override_form` varchar(255) NOT NULL default '',
  `Keywords` varchar(255) NOT NULL default '',
  `url_title` varchar(255) NOT NULL default '',
  `custom_1` varchar(255) NOT NULL default '',
  `custom_2` varchar(255) NOT NULL default '',
  `custom_3` varchar(255) NOT NULL default '',
  `custom_4` varchar(255) NOT NULL default '',
  `custom_5` varchar(255) NOT NULL default '',
  `custom_6` varchar(255) NOT NULL default '',
  `custom_7` varchar(255) NOT NULL default '',
  `custom_8` varchar(255) NOT NULL default '',
  `custom_9` varchar(255) NOT NULL default '',
  `custom_10` varchar(255) NOT NULL default '',
  `uid` varchar(32) NOT NULL default '',
  `feed_time` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`ID`),
  KEY `categories_idx` (`Category1`(10),`Category2`(10)),
  KEY `Posted` (`Posted`),
  FULLTEXT KEY `searching` (`Title`,`Body`)
) $tabletype PACK_KEYS=1 AUTO_INCREMENT=2 ";

$setup_comment_invite = addslashes( ( gTxt('setup_comment_invite')=='setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite') );
$create_sql[] = "INSERT INTO `".PFX."textpattern` VALUES (1, now(), '$name', now(), '', 'First Post', '', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec rutrum est eu mauris. In volutpat blandit felis. Suspendisse eget pede. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Quisque sed arcu. Aenean purus nulla, condimentum ac, pretium at, commodo sit amet, turpis. Aenean lacus. Ut in justo. Ut viverra dui vel ante. Duis imperdiet porttitor mi. Maecenas at lectus eu justo porta tempus. Cras fermentum ligula non purus. Duis id orci non magna rutrum bibendum. Mauris tincidunt, massa in rhoncus consectetuer, lectus dui ornare enim, ut egestas ipsum purus id urna. Vestibulum volutpat porttitor metus. Donec congue vehicula ante.', '	<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec rutrum est eu mauris. In volutpat blandit felis. Suspendisse eget pede. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Quisque sed arcu. Aenean purus nulla, condimentum ac, pretium at, commodo sit amet, turpis. Aenean lacus. Ut in justo. Ut viverra dui vel ante. Duis imperdiet porttitor mi. Maecenas at lectus eu justo porta tempus. Cras fermentum ligula non purus. Duis id orci non magna rutrum bibendum. Mauris tincidunt, massa in rhoncus consectetuer, lectus dui ornare enim, ut egestas ipsum purus id urna. Vestibulum volutpat porttitor metus. Donec congue vehicula ante.</p>\n\n\n ', '', '\n\n\n ', '', '', '', 1, '".$setup_comment_invite."', 1, 4, 1, 1, 'article', '', '', 'first-post', '', '', '', '', '', '', '', '', '', '', 'becfea8fd42801204463b23701199f28', 0x323030352d30372d3138)";

$create_sql[] = "CREATE TABLE `".PFX."txp_category` (
  `id` int(6) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `type` varchar(64) NOT NULL default '',
  `parent` varchar(64) NOT NULL default '',
  `lft` int(6) NOT NULL default '0',
  `rgt` int(6) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) $tabletype PACK_KEYS=1 AUTO_INCREMENT=64 ";

$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (63, 'root', 'file', '', 1, 2, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (62, 'root', 'image', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (61, 'root', 'link', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (12, 'textpattern', 'link', 'root', 2, 3, 'textpattern')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (15, 'reciprocal-affection', 'article', 'root', 6, 7, 'reciprocal affection')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (14, 'hope-for-the-future', 'article', 'root', 2, 3, 'hope for the future')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (16, 'meaningful-labor', 'article', 'root', 4, 5, 'meaningful labor')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (51, 'site-design', 'image', 'root', 2, 3, 'site design')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (60, 'root', 'article', '', 1, 8, 'root')";

$create_sql[] = "CREATE TABLE `".PFX."txp_css` (
  `name` varchar(255) default NULL,
  `css` text,
  UNIQUE KEY `name` (`name`)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_css` VALUES ('default', 'Ym9keQ0Kew0KCWJhY2tncm91bmQtY29sb3I6ICNmZmY7DQp9DQoNCnAsIGJsb2NrcXVvdGUsIGxpLCBoMw0Kew0KCWZvbnQtZmFtaWx5OiBWZXJkYW5hLCAiTHVjaWRhIEdyYW5kZSIsIFRhaG9tYSwgSGVsdmV0aWNhOw0KCWZvbnQtc2l6ZTogMC45ZW07DQoJbGluZS1oZWlnaHQ6IDEuNmVtOw0KCXRleHQtYWxpZ246IGxlZnQ7DQoJcGFkZGluZy1sZWZ0OiAxMHB4Ow0KCXBhZGRpbmctcmlnaHQ6IDEwcHg7DQp9DQoNCmJsb2NrcXVvdGUNCnsNCgltYXJnaW4tbGVmdDogMjBweDsNCgltYXJnaW4tcmlnaHQ6IDBweDsNCn0NCg0KI3NpZGViYXItMiBwLCAjc2lkZWJhci0xIHANCnsNCglsaW5lLWhlaWdodDogMTVweDsNCglmb250LXNpemU6IDEwcHg7DQp9DQoNCiNzaWRlYmFyLTEgcA0Kew0KCXRleHQtYWxpZ246IHJpZ2h0Ow0KfQ0KDQojaGVhZA0Kew0KCXRleHQtYWxpZ246IGNlbnRlcjsNCgloZWlnaHQ6IDEwMHB4Ow0KfQ0KDQojY29udGFpbmVyDQp7DQoJd2lkdGg6IDc2MHB4Ow0KCVx3aWR0aDogNzcwcHg7DQoJd1xpZHRoOiA3NjBweDsNCgltYXJnaW46IDEwcHg7DQoJbWFyZ2luLWxlZnQ6IGF1dG87DQoJbWFyZ2luLXJpZ2h0OiBhdXRvOw0KCXBhZGRpbmc6IDEwcHg7DQp9DQoNCiNzaWRlYmFyLTENCnsNCglmbG9hdDogbGVmdDsNCgl3aWR0aDogMTUwcHg7DQoJXHdpZHRoOiAxNTBweDsNCgl3XGlkdGg6IDE1MHB4Ow0KCW1hcmdpbi1yaWdodDogNXB4Ow0KCXBhZGRpbmctdG9wOiAxMDBweDsNCn0NCg0KI2NvbnRlbnQNCnsNCgltYXJnaW4tbGVmdDogMTU1cHg7DQoJbWFyZ2luLXJpZ2h0OiAxNTVweDsNCglwYWRkaW5nLXRvcDogMzBweDsNCn0NCg0KI3NpZGViYXItMg0Kew0KCWZsb2F0OiByaWdodDsNCgl3aWR0aDogMTUwcHg7DQoJXHdpZHRoOiAxNTBweDsNCgl3XGlkdGg6IDE1MHB4Ow0KCW1hcmdpbi1sZWZ0OiA1cHg7DQoJcGFkZGluZy10b3A6IDEwMHB4Ow0KfQ0KDQojZm9vdA0Kew0KCWNsZWFyOiBib3RoOw0KCW1hcmdpbi10b3A6IDVweDsNCgl0ZXh0LWFsaWduOiBjZW50ZXI7DQp9DQojYWNjZXNzaWJpbGl0eQ0Kew0KCXBvc2l0aW9uOiBhYnNvbHV0ZTsNCgl0b3A6IC0xMDAwMHB4Ow0KfQ0KYQ0Kew0KCWNvbG9yOiBibGFjazsNCgl0ZXh0LWRlY29yYXRpb246IG5vbmU7DQoJYm9yZGVyLWJvdHRvbTogMXB4IGJsYWNrIHNvbGlkOw0KfQ0KDQojc2lkZWJhci0yIGEsICNzaWRlYmFyLTEgYQ0Kew0KCWJvcmRlcjogMHB4Ow0KCWNvbG9yOiAjQzAwOw0KfQ0KDQpoMQ0Kew0KCWZvbnQtd2VpZ2h0OiBub3JtYWw7DQoJdGV4dC1kZWNvcmF0aW9uOm5vbmU7DQoJZm9udC1mYW1pbHk6IEdlb3JnaWEsIFRpbWVzLCBTZXJpZjsNCglmb250LXNpemU6IDNlbTsNCn0NCg0KaDINCnsNCglmb250LXdlaWdodDogbm9ybWFsOw0KCWZvbnQtZmFtaWx5OiBHZW9yZ2lhLCBUaW1lcywgU2VyaWY7DQoJZm9udC1zaXplOiAxZW07DQoJZm9udC1zdHlsZTogaXRhbGljOw0KfQ0KDQpoMw0Kew0KCWZvbnQtd2VpZ2h0OiBub3JtYWw7DQp9DQoNCmgzIGENCnsNCglib3JkZXI6IDBweDsNCglmb250LXdlaWdodDogbm9ybWFsOw0KCWZvbnQtZmFtaWx5OiBHZW9yZ2lhLCBUaW1lcywgU2VyaWY7DQoJZm9udC1zaXplOiAxLjVlbTsNCn0NCg0KaDEgYSwgaDIgYQ0Kew0KCWJvcmRlcjogMHB4Ow0KfQ0KLmNhcHMNCnsNCglsZXR0ZXItc3BhY2luZzogMC4xZW07DQoJZm9udC1zaXplOiAxMHB4Ow0KfQ0KDQojY29udGVudCBocg0Kew0KCWNvbG9yOiAjY2NjOw0KCXdpZHRoOiA2NiU7DQoJbWFyZ2luLXRvcDogMmVtOw0KCW1hcmdpbi1ib3R0b206IDJlbTsNCn0NCg0KaW1nLmRpdmlkZXINCnsNCglhbGlnbjogY2VudGVyOw0KCW1hcmdpbi10b3A6IDJlbTsNCgltYXJnaW4tYm90dG9tOiAyZW07DQp9CgouY29tbWVudHNfZXJyb3IKeyAKCWJhY2tncm91bmQtY29sb3I6ICNmZmEgCn0KCmRpdi5jb21tZW50c19lcnJvcgp7Cglib3JkZXI6IDFweCBzb2xpZCAjY2M4OwoJcGFkZGluZyA6IDAuM2VtOwp9')";

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss` (
  `discussid` int(6) unsigned zerofill NOT NULL auto_increment,
  `parentid` int(8) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `email` varchar(50) NOT NULL default '',
  `web` varchar(255) NOT NULL default '',
  `ip` varchar(100) NOT NULL default '',
  `posted` datetime NOT NULL default '0000-00-00 00:00:00',
  `message` text NOT NULL,
  `visible` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`discussid`),
  KEY `parentid` (`parentid`)
) $tabletype PACK_KEYS=1 AUTO_INCREMENT=2 ";

$create_sql[] = "INSERT INTO `".PFX."txp_discuss` VALUES (000001, 1, 'Donald Swain', 'me@here.com', 'example.com', '127.0.0.1', '2005-07-22 14:11:32', 'I enjoy your site very much.', 1)";

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss_ipban` (
  `ip` varchar(255) NOT NULL default '',
  `name_used` varchar(255) NOT NULL default '',
  `date_banned` datetime NOT NULL default '0000-00-00 00:00:00',
  `banned_on_message` int(8) NOT NULL default '0'
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss_nonce` (
  `issue_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `nonce` varchar(255) NOT NULL default '',
  `used` tinyint(4) NOT NULL default '0',
  `secret` varchar(255) NOT NULL default ''
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_file` (
  `id` int(11) NOT NULL auto_increment,
  `filename` varchar(255) NOT NULL default '',
  `category` varchar(255) NOT NULL default '',
  `permissions` varchar(32) NOT NULL default '0',
  `description` text NOT NULL,
  `downloads` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `filename` (`filename`)
) $tabletype PACK_KEYS=0 AUTO_INCREMENT=1 ";

$create_sql[] = "CREATE TABLE `".PFX."txp_form` (
  `name` varchar(64) NOT NULL default '',
  `type` varchar(28) NOT NULL default '',
  `Form` text NOT NULL,
  PRIMARY KEY  (`name`),
  KEY `name` (`name`)
) $tabletype PACK_KEYS=1";





$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('Links', 'link', '<p><txp:link /><br />\r\n<txp:link_description /></p>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('lofi', 'article', '<h3><txp:title /></h3>\r\n<p><small><txp:permlink>#</txp:permlink> <txp:posted /></small></p>\r\n<txp:body />\r\n<hr size=\"1\" noshade=\"noshade\" />')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('Single', 'article', '<h3><txp:title /> <span class=\"permlink\"><txp:permlink>::</txp:permlink></span> <span class=\"date\"><txp:posted /></span></h3>\r\n<txp:body />')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('plainlinks', 'link', '<txp:linkdesctitle /><br />')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('comments', 'comment', '<txp:message /><br />\r\n<small>&#8212; <txp:comment_name /> &#160;&#160; <txp:comment_time /> &#160;&#160; <txp:comment_permlink>#</txp:comment_permlink></small>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('default', 'article', '<h3><txp:permlink><txp:title /></txp:permlink> &#183; <txp:posted /> by <txp:author /></h3>\r\n<txp:body />\r\n<txp:comments_invite wraptag=\"p\" />\r\n<div align=\"center\"><img src=\"<txp:site_url />images/1.gif\" style=\"height:1px;width:400px\" class=\"divider\" alt=\"\" /></div>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('comment_form', 'comment', '  <table cellpadding=\"4\" cellspacing=\"0\" border=\"0\">\r\n	<tr>\r\n	  <td align=\"right\" valign=\"top\">\r\n	   	<label for=\"name\"><txp:text item=\"name\" /></label>\r\n	  </td>\r\n   <td valign=\"top\">\r\n	  	<txp:comment_name_input />\r\n	  </td>\r\n	  <td valign=\"top\" align=\"left\">\r\n	  	<txp:comment_remember />\r\n	  </td> \r\n	</tr>\r\n	<tr>\r\n	  <td align=\"right\" valign=\"top\">\r\n	  	<label for=\"email\"><txp:text item=\"email\" /></label>\r\n	  </td>\r\n	  <td valign=\"top\" colspan=\"2\">\r\n	  	<txp:comment_email_input />\r\n	  </td>\r\n    </tr>\r\n	<tr> \r\n	  <td align=\"right\" valign=\"top\">\r\n	  	<label for=\"web\">http://</label>\r\n	  </td>\r\n	  <td valign=\"top\" colspan=\"2\">\r\n	  	<txp:comment_web_input />\r\n	  </td>\r\n	</tr>\r\n	<tr>\r\n	  <td valign=\"top\" align=\"right\">\r\n	  	<label for=\"message\"><txp:text item=\"message\" /></label>\r\n	  </td>\r\n	  <td valign=\"top\" colspan=\"2\">\r\n	  	<txp:comment_message_input />\r\n	  </td>\r\n	</tr>\r\n	<tr>\r\n	  <td align=\"right\" valign=\"top\">&nbsp;</td>\r\n	  <td valign=\"top\" align=\"left\">\r\n		<txp:comments_help />\r\n	  </td>\r\n	  <td align=\"right\" valign=\"top\">\r\n		<txp:comment_preview />\r\n		<txp:comment_submit />\r\n	  </td>\r\n	</tr>\r\n  </table>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('Noted', 'link', '<p> <txp:link />. <txp:link_description /></p>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('popup_comments', 'comment', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head>\n	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n	<link rel=\"Stylesheet\" href=\"<txp:css />\" type=\"text/css\" />\n	<title><txp:page_title /></title>\n</head>\n<body>\n<div style=\"text-align: left; padding: 1em; width:300px\">\n\n	<txp:popup_comments />\n\n	</div>\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('files', 'file', '<txp:text item=\"file\" />: \n<txp:file_download_link>\n<txp:file_download_name /> [<txp:file_download_size format=\"auto\" decimals=\"2\" />]\n</txp:file_download_link>\n<br />\n<txp:text item=\"category\" />: <txp:file_download_category /><br />\n<txp:text item=\"download\" />: <txp:file_download_downloads />')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('search_results', 'article', '<h3><txp:permlink><txp:title /></txp:permlink></h3>\n<p><txp:search_result_excerpt /><br/>\n<small><txp:permlink><txp:permlink /></txp:permlink> &middot;\n<txp:posted /></small></p>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('comments_display', 'article', '<txp:comments />\n<txp:if_comments_allowed>\n<txp:comments_form />\n<txp:else />\n<p><txp:text item=\"comments_closed\" /></p>\n</txp:if_comments_allowed>')";

$create_sql[] = "CREATE TABLE `".PFX."txp_image` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `category` varchar(255) NOT NULL default '',
  `ext` varchar(20) NOT NULL default '',
  `w` int(8) NOT NULL default '0',
  `h` int(8) NOT NULL default '0',
  `alt` varchar(255) NOT NULL default '',
  `caption` text NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `author` varchar(255) NOT NULL default '',
  `thumbnail` int(2) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) $tabletype PACK_KEYS=0 AUTO_INCREMENT=2 ";

$create_sql[] = "INSERT INTO `".PFX."txp_image` VALUES (1, 'divider.gif', 'site-design', '.gif', 400, 1, '', '', '2005-07-22 16:37:11', '$name', 0)";

$create_sql[] = "CREATE TABLE `".PFX."txp_lang` (
  `id` int(9) NOT NULL auto_increment,
  `lang` varchar(16) default NULL,
  `name` varchar(64) default NULL,
  `event` varchar(64) default NULL,
  `data` tinytext,
  `lastmod` timestamp,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `lang` (`lang`,`name`),
  KEY `lang_2` (`lang`,`event`)
) $tabletype DELAY_KEY_WRITE=1 AUTO_INCREMENT=1 ";

$create_sql[] = "CREATE TABLE `".PFX."txp_link` (
  `id` int(6) NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `category` varchar(64) NOT NULL default '',
  `url` text NOT NULL,
  `linkname` varchar(255) NOT NULL default '',
  `linksort` varchar(128) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) $tabletype PACK_KEYS=1 AUTO_INCREMENT=4 ";

$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (1, '2005-07-20 12:54:26', 'textpattern', 'http://textpattern.com', 'Textpattern', 'Textpattern', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (2, '2005-07-20 12:54:41', 'textpattern', 'http://textpattern.net', 'TextBook', 'TextBook', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (3, '2005-07-20 12:55:04', 'textpattern', 'http://textpattern.org', 'Txp Resources', 'Txp Recources', '')";

$create_sql[] = "CREATE TABLE `".PFX."txp_log` (
  `id` int(12) NOT NULL auto_increment,
  `time` datetime NOT NULL default '0000-00-00 00:00:00',
  `host` varchar(255) NOT NULL default '',
  `page` varchar(255) NOT NULL default '',
  `refer` mediumtext NOT NULL,
  `status` int(11) NOT NULL default '200',
  `method` varchar(16) NOT NULL default 'GET',
  `ip` varchar(16) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `time` (`time`)
) $tabletype AUTO_INCREMENT=77 ";

$create_sql[] = "CREATE TABLE `".PFX."txp_page` (
  `name` varchar(128) NOT NULL default '',
  `user_html` text NOT NULL,
  PRIMARY KEY  (`name`),
  UNIQUE KEY `name` (`name`)
) $tabletype PACK_KEYS=1";

$create_sql[] = "INSERT INTO `".PFX."txp_page` VALUES ('default', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\r\n        \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\r\n	<link rel=\"stylesheet\" href=\"<txp:css />\" type=\"text/css\" media=\"screen\" />\r\n	<title><txp:page_title /></title>\r\n</head>\r\n<body>\r\n<div id=\"accessibility\">\r\n  <a href=\"#content\" title=\"Go to content\">Go to content</a> \r\n  <a href=\"#sidebar-1\" title=\"Go to navigation\">Go to navigation</a> \r\n  <a href=\"#sidebar-2\" title=\"Go to search\">Go to search</a> \r\n</div> <!-- /accessibility -->\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n<div id=\"head\">\r\n<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n<h2><txp:site_slogan /></h2>\r\n\r\n</div>\r\n\r\n<!-- left -->\r\n<div id=\"sidebar-1\">\r\n\r\n	<txp:linklist wraptag=\"p\" />\r\n\r\n</div>\r\n\r\n<!-- right -->\r\n<div id=\"sidebar-2\">\r\n\r\n		<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n		<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n		<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link label=\"Atom\" flavor=\"atom\" /></p>\r\n\r\n		<p><img src=\"textpattern/txp_img/txp_slug105x45.gif\" alt=\"textpattern\" /></p>\r\n\r\n\r\n</div>\r\n\r\n<!-- center -->\r\n<div id=\"content\">\r\n\r\n	<txp:article />\r\n<txp:if_individual_article>\r\n<p>\r\n<txp:link_to_prev><txp:prev_title /></txp:link_to_prev>\r\n<txp:link_to_next><txp:next_title /></txp:link_to_next>\r\n</p>\r\n</txp:if_individual_article>\r\n<txp:if_article_list>\r\n<p>\r\n<txp:older>Previous</txp:older>\r\n<txp:newer>Next</txp:newer>\r\n</p>\r\n</txp:if_article_list>\r\n</div>\r\n\r\n<!-- footer -->\r\n<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_page` VALUES ('archive', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\r\n        \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\r\n	<link rel=\"stylesheet\" href=\"<txp:css />\" type=\"text/css\" media=\"screen\" />\r\n	<title><txp:page_title /></title>\r\n</head>\r\n<body>\r\n<div id=\"accessibility\">\r\n  <a href=\"#content\" title=\"Go to content\">Go to content</a> \r\n  <a href=\"#sidebar-1\" title=\"Go to navigation\">Go to navigation</a> \r\n  <a href=\"#sidebar-2\" title=\"Go to search\">Go to search</a> \r\n</div> <!-- /accessibility -->\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n<div id=\"head\">\r\n<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n\r\n</div>\r\n\r\n<!-- left -->\r\n<div id=\"sidebar-1\">\r\n\r\n	<txp:linklist wraptag=\"p\" />\r\n\r\n</div>\r\n\r\n<!-- right -->\r\n<div id=\"sidebar-2\">\r\n\r\n		<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n		<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n		<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link label=\"Atom\" flavor=\"atom\" /></p>\r\n\r\n		<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" alt=\"\" /></p>\r\n\r\n\r\n</div>\r\n\r\n<!-- center -->\r\n<div id=\"content\">\r\n\r\n	<txp:article />\r\n<txp:if_individual_article>\r\n<p>\r\n<txp:link_to_prev><txp:prev_title /></txp:link_to_prev>\r\n<txp:link_to_next><txp:next_title /></txp:link_to_next>\r\n</p>\r\n</txp:if_individual_article>\r\n<txp:if_article_list>\r\n<p>\r\n<txp:older>Previous</txp:older>\r\n<txp:newer>Next</txp:newer>\r\n</p>\r\n</txp:if_article_list>\r\n</div>\r\n\r\n<!-- footer -->\r\n<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n\r\n')";

$create_sql[] = "CREATE TABLE `".PFX."txp_plugin` (
  `name` varchar(64) NOT NULL default '',
  `status` int(2) NOT NULL default '1',
  `author` varchar(128) NOT NULL default '',
  `author_uri` varchar(128) NOT NULL default '',
  `version` varchar(10) NOT NULL default '1.0',
  `description` text NOT NULL,
  `help` text NOT NULL,
  `code` text NOT NULL,
  `code_restore` text NOT NULL,
  `code_md5` varchar(32) NOT NULL default '',
  `type` int(2) NOT NULL default '0',
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_prefs` (
  `prefs_id` int(11) default NULL,
  `name` varchar(255) default NULL,
  `val` varchar(255) default NULL,
  `type` smallint(5) unsigned NOT NULL default '2',
  `event` varchar(12) NOT NULL default 'publish',
  `html` varchar(64) NOT NULL default 'text_input',
  `position` smallint(5) unsigned NOT NULL default '0',
  UNIQUE KEY `prefs_idx` (`prefs_id`,`name`),
  KEY `name` (`name`)
) $tabletype ";

$prefs['blog_uid'] = md5(uniqid(rand(),true));
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'prefs_id', '1', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'sitename', '".addslashes(gTxt('my_site'))."', 0, 'publish', 'text_input', 10)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'siteurl', 'comment.local', 0, 'publish', 'text_input', 20)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'site_slogan', '".addslashes(gTxt('my_slogan'))."', 0, 'publish', 'text_input', 30)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'language', 'en-gb', 2, 'publish', 'languages', 40)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'url_mode', '1', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'timeoffset', '0', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_on_default', '0', 0, 'comments', 'yesnoradio', 140)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_default_invite', '".$setup_comment_invite."', 0, 'comments', 'text_input', 180)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_mode', '0', 0, 'comments', 'commentmode', 200)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_disabled_after', '42', 0, 'comments', 'weeks', 210)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_textile', '2', 0, 'publish', 'pref_text', 110)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'ping_weblogsdotcom', '0', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'rss_how_many', '5', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'logging', 'all', 0, 'publish', 'logging', 100)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_comments', '1', 0, 'publish', 'yesnoradio', 120)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_categories', '1', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_sections', '1', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'send_lastmod', '0', 1, 'admin', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'path_from_root', '/', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'lastmod', '2005-07-23 16:24:10', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_dateformat', '%b %d, %I:%M %p', 0, 'comments', 'dateformats', 190)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'dateformat', 'since', 0, 'publish', 'dateformats', 70)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'archive_dateformat', '%b %d, %I:%M %p', 0, 'publish', 'dateformats', 80)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_moderate', '1', 0, 'comments', 'yesnoradio', 130)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'img_dir', 'images', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_disallow_images', '0', 0, 'comments', 'yesnoradio', 170)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_sendmail', '0', 0, 'comments', 'yesnoradio', 160)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_max_upload_size', '2000000', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'path_to_site', '', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'article_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'link_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'image_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'log_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'permlink_mode', '".addslashes($permlink_mode)."', 0, 'publish', 'permlinkmodes', 90)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_are_ol', '1', 0, 'comments', 'yesnoradio', 150)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'is_dst', '0', 0, 'publish', 'yesnoradio', 60)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'locale', 'en_GB.UTF-8', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'tempdir', '".addslashes(find_temp_dir())."', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_base_path', '".addslashes(dirname(txpath).DS.'files')."', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_uid', '". $prefs['blog_uid'] ."', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_mail_uid', '".addslashes($_POST['email'])."', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_time_uid', '2005', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'edit_raw_css_by_default', '1', 1, 'css', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_page_php_scripting', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_article_php_scripting', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'textile_links', '0', 1, 'link', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'show_article_category_count', '1', 2, 'category', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'show_comment_count_in_feed', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'syndicate_body_or_excerpt', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'include_email_atom', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_means_site_updated', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'never_display_email', '0', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_require_name', '1', 1, 'comments', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_require_email', '1', 1, 'comments', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'articles_use_excerpts', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_form_override', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'attach_titles_to_permalinks', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'permalink_title_format', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'expire_logs_after', '7', 1, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_plugins', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_1_set', 'custom1', 1, 'custom', 'text_input', 1)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_2_set', 'custom2', 1, 'custom', 'text_input', 2)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_3_set', '', 1, 'custom', 'text_input', 3)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_4_set', '', 1, 'custom', 'text_input', 4)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_5_set', '', 1, 'custom', 'text_input', 5)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_6_set', '', 1, 'custom', 'text_input', 6)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_7_set', '', 1, 'custom', 'text_input', 7)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_8_set', '', 1, 'custom', 'text_input', 8)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_9_set', '', 1, 'custom', 'text_input', 9)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_10_set', '', 1, 'custom', 'text_input', 10)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'ping_textpattern_com', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_dns', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'admin_side_plugins', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_nofollow', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_mail_on_feeds_id', '0', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'max_url_len', '200', 1, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'spam_blacklists', 'sbl.spamhaus.org', 1, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'override_emailcharset', '0', 1, 'admin', 'yesnoradio', 21)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'production_status', 'testing', 0, 'publish', 'prod_levels', 210)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_auto_append', '1', 0, 'comments', 'yesnoradio', 211)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'dbupdatetime', '1122194504', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'version', '1.0rc4', 2, 'publish', 'text_input', 0)";

$create_sql[] = "CREATE TABLE `".PFX."txp_section` (
  `name` varchar(128) NOT NULL default '',
  `page` varchar(128) NOT NULL default '',
  `css` varchar(128) NOT NULL default '',
  `is_default` int(2) NOT NULL default '0',
  `in_rss` int(2) NOT NULL default '1',
  `on_frontpage` int(2) NOT NULL default '1',
  `searchable` int(2) NOT NULL default '1',
  `title` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`name`),
  UNIQUE KEY `name` (`name`)
) $tabletype PACK_KEYS=1";

$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('article', 'archive', 'default', 1, 1, 1, 1, 'article')";
$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('default', 'default', 'default', 0, 1, 1, 1, 'default')";
$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('about', 'default', 'default', 0, 0, 0, 1, 'about')";

$create_sql[] = "CREATE TABLE `".PFX."txp_users` (
  `user_id` int(4) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `pass` varchar(128) NOT NULL default '',
  `RealName` varchar(64) NOT NULL default '',
  `email` varchar(100) NOT NULL default '',
  `privs` tinyint(2) NOT NULL default '1',
  `last_access` datetime NOT NULL default '0000-00-00 00:00:00',
  `nonce` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `name` (`name`)
) $tabletype PACK_KEYS=1 AUTO_INCREMENT=2 ";


$GLOBALS['txp_install_successful'] = true;
$GLOBALS['txp_err_count'] = 0;
foreach ($create_sql as $query)
{
	$result = mysql_query($query);
	if (!$result) 
	{
		$GLOBALS['txp_err_count']++;
		echo "<b>".$GLOBALS['txp_err_count'].".</b> ".mysql_error()."<br />\r\n";
		echo "<!--\r\n $query \r\n-->\r\n";
		$GLOBALS['txp_install_successful'] = false;
	}
}

# Skip the RPC language fetch when testing
if (defined('TXP_TEST'))
	return;

require_once txpath.'/lib/IXRClass.php';
$client = new IXR_Client('http://rpc.textpattern.com');
if (!$client->query('tups.getLanguage',$prefs['blog_uid'],$lang))
{
	# If cannot install from lang file, setup the english lang
	if (!install_language_from_file($lang))
	{
		$lang = 'en-gb';
		include_once txpath.'/setup/en-gb.php';
		if (!@$lastmod) $lastmod = '0000-00-00 00:00:00';
		foreach ($en_gb_lang as $evt_name => $evt_strings)
		{
			foreach ($evt_strings as $lang_key => $lang_val)
			{
				$lang_val = addslashes($lang_val);
				if (@$lang_val)
					mysql_query("INSERT DELAYED INTO `".PFX."txp_lang`  SET `lang`='en-gb',`name`='$lang_key',`event`='$evt_name',`data`='$lang_val',`lastmod`='$lastmod'");
			}
		}
	}
}else {
	$response = $client->getResponse();
	$lang_struct = unserialize($response);
	foreach ($lang_struct as $item)
	{
		foreach ($item as $name => $value) 
			$item[$name] = addslashes($value);
		mysql_query("INSERT DELAYED INTO `".PFX."txp_lang`  SET `lang`='$lang',`name`='$item[name]',`event`='$item[event]',`data`='$item[data]',`lastmod`='".strftime('%Y%m%d%H%M%S',$item['uLastmod'])."'");
	}		
}
mysql_query("FLUSH TABLE `".PFX."txp_lang`");

?>

<?php

if (!defined('TXP_INSTALL'))
	exit;

mysql_connect($dhost,$duser,$dpass);
mysql_select_db($ddb);






mysql_query("CREATE TABLE `".PFX."textpattern` (
  `ID` int(11) NOT NULL auto_increment,
  `Posted` datetime NOT NULL default '0000-00-00 00:00:00',
  `AuthorID` varchar(64) NOT NULL default '',
  `LastMod` datetime NOT NULL default '0000-00-00 00:00:00',
  `LastModID` varchar(64) NOT NULL default '',
  `Title` varchar(255) NOT NULL default '',
  `Title_html` varchar(255) NOT NULL default '',
  `Body` text NOT NULL,
  `Body_html` text NOT NULL,
  `Excerpt` mediumtext NOT NULL,
  `Image` varchar(255) NOT NULL default '',
  `Category1` varchar(128) NOT NULL default '',
  `Category2` varchar(128) NOT NULL default '',
  `Annotate` int(2) NOT NULL default '0',
  `AnnotateInvite` varchar(255) NOT NULL default '',
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
  PRIMARY KEY  (`ID`),
  KEY `categories_idx` (`Category1`(10),`Category2`(10)),
  KEY `Posted` (`Posted`),
  FULLTEXT KEY `searching` (`Title`,`Body`)
) TYPE=MyISAM PACK_KEYS=1 AUTO_INCREMENT=2 ");





mysql_query("INSERT INTO `".PFX."textpattern` VALUES (1, now(), 'textpattern', now(), '', 'First Post', '', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec rutrum est eu mauris. In volutpat blandit felis. Suspendisse eget pede. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Quisque sed arcu. Aenean purus nulla, condimentum ac, pretium at, commodo sit amet, turpis. Aenean lacus. Ut in justo. Ut viverra dui vel ante. Duis imperdiet porttitor mi. Maecenas at lectus eu justo porta tempus. Cras fermentum ligula non purus. Duis id orci non magna rutrum bibendum. Mauris tincidunt, massa in rhoncus consectetuer, lectus dui ornare enim, ut egestas ipsum purus id urna. Vestibulum volutpat porttitor metus. Donec congue vehicula ante.', '	<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec rutrum est eu mauris. In volutpat blandit felis. Suspendisse eget pede. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Quisque sed arcu. Aenean purus nulla, condimentum ac, pretium at, commodo sit amet, turpis. Aenean lacus. Ut in justo. Ut viverra dui vel ante. Duis imperdiet porttitor mi. Maecenas at lectus eu justo porta tempus. Cras fermentum ligula non purus. Duis id orci non magna rutrum bibendum. Mauris tincidunt, massa in rhoncus consectetuer, lectus dui ornare enim, ut egestas ipsum purus id urna. Vestibulum volutpat porttitor metus. Donec congue vehicula ante.</p>\r\n\r\n\r\n ', '', '', '', '', 0, 'Comment', 4, 1, 1, 'article', '', '', '', '', '', '', '', '', '', '', '', '', '')");







mysql_query("CREATE TABLE `".PFX."txp_category` (
  `id` int(6) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `type` varchar(64) NOT NULL default '',
  `parent` varchar(64) NOT NULL default '',
  `lft` int(6) NOT NULL default '0',
  `rgt` int(6) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM PACK_KEYS=1 AUTO_INCREMENT=4 ");





mysql_query("INSERT INTO `".PFX."txp_category` VALUES (1, 'root', 'link', '', 1, 2)");
mysql_query("INSERT INTO `".PFX."txp_category` VALUES (2, 'root', 'article', '', 1, 2)");
mysql_query("INSERT INTO `".PFX."txp_category` VALUES (3, 'root', 'image', '', 1, 2)");
mysql_query("INSERT INTO `".PFX."txp_category` VALUES (3, 'root', 'file', '', 1, 2)");






mysql_query("CREATE TABLE `".PFX."txp_css` (
  `name` varchar(255) default NULL,
  `css` text,
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM");





mysql_query("INSERT INTO `".PFX."txp_css` VALUES ('default', 'Ym9keQp7CgliYWNrZ3JvdW5kLWNvbG9yOiAjZmZmOwp9CgpwLCBibG9ja3F1b3RlLCBsaSwgaDMKewoJZm9udC1mYW1pbHk6IFZlcmRhbmEsICJMdWNpZGEgR3JhbmRlIiwgVGFob21hLCBIZWx2ZXRpY2E7Cglmb250LXNpemU6IDExcHg7CglsaW5lLWhlaWdodDogMThweDsKCXRleHQtYWxpZ246IGxlZnQ7CglwYWRkaW5nLWxlZnQ6IDEwcHg7CglwYWRkaW5nLXJpZ2h0OiAxMHB4Owp9CgpibG9ja3F1b3RlCnsKCW1hcmdpbi1sZWZ0OiAyMHB4OwoJbWFyZ2luLXJpZ2h0OiAwcHg7Cn0KCiNyaWdodCBwLCAjbGVmdCBwCnsKCWxpbmUtaGVpZ2h0OiAxNXB4OwoJZm9udC1zaXplOiAxMHB4Owp9CgojbGVmdCBwCnsKCXRleHQtYWxpZ246IHJpZ2h0Owp9CgojaGVhZAp7Cgl0ZXh0LWFsaWduOiBjZW50ZXI7CgloZWlnaHQ6IDEwMHB4Owp9CgojY29udGFpbmVyCnsKCXdpZHRoOiA3NjBweDsKCVx3aWR0aDogNzcwcHg7Cgl3XGlkdGg6IDc2MHB4OwoJbWFyZ2luOiAxMHB4OwoJbWFyZ2luLWxlZnQ6IGF1dG87CgltYXJnaW4tcmlnaHQ6IGF1dG87CglwYWRkaW5nOiAxMHB4Owp9CgojbGVmdAp7CglmbG9hdDogbGVmdDsKCXdpZHRoOiAxNTBweDsKCVx3aWR0aDogMTUwcHg7Cgl3XGlkdGg6IDE1MHB4OwoJbWFyZ2luLXJpZ2h0OiA1cHg7CglwYWRkaW5nLXRvcDogMTAwcHg7Cn0KCiNjZW50ZXIKewoJbWFyZ2luLWxlZnQ6IDE1NXB4OwoJbWFyZ2luLXJpZ2h0OiAxNTVweDsKCXBhZGRpbmctdG9wOiAzMHB4OwoJYm9yZGVyLWxlZnQ6IDFweCBzb2xpZCBncmV5OwoJYm9yZGVyLXJpZ2h0OiAxcHggc29saWQgZ3JleTsKfQoKI3JpZ2h0CnsKCWZsb2F0OiByaWdodDsKCXdpZHRoOiAxNTBweDsKCVx3aWR0aDogMTUwcHg7Cgl3XGlkdGg6IDE1MHB4OwoJbWFyZ2luLWxlZnQ6IDVweDsKCXBhZGRpbmctdG9wOiAxMDBweDsKfQoKI2Zvb3QKewoJY2xlYXI6IGJvdGg7CgltYXJnaW4tdG9wOiA1cHg7Cgl0ZXh0LWFsaWduOiBjZW50ZXI7Cn0KCmEKewoJY29sb3I6IGJsYWNrOwoJdGV4dC1kZWNvcmF0aW9uOiBub25lOwoJYm9yZGVyLWJvdHRvbTogMXB4IGJsYWNrIHNvbGlkOwp9CgojcmlnaHQgYSwgI2xlZnQgYQp7Cglib3JkZXI6IDBweDsKCWNvbG9yOiAjQzAwOwp9CgpoMwp7Cglmb250LXdlaWdodDogbm9ybWFsOwp9CgpoMyBhCnsKCWJvcmRlcjogMHB4OwoJZm9udC13ZWlnaHQ6IG5vcm1hbDsKCWZvbnQtZmFtaWx5OiBHZW9yZ2lhLCBUaW1lcywgU2VyaWY7Cglmb250LXNpemU6IDE0cHg7Cn0KCi5jYXBzCnsKCWxldHRlci1zcGFjaW5nOiAwLjFlbTsKCWZvbnQtc2l6ZTogMTBweDsKfQ==')");







mysql_query("CREATE TABLE `".PFX."txp_discuss` (
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
) TYPE=MyISAM PACK_KEYS=1 AUTO_INCREMENT=1 ");












mysql_query("CREATE TABLE `".PFX."txp_discuss_ipban` (
  `ip` varchar(255) NOT NULL default '',
  `name_used` varchar(255) NOT NULL default '',
  `date_banned` datetime NOT NULL default '0000-00-00 00:00:00',
  `banned_on_message` int(8) NOT NULL default '0'
) TYPE=MyISAM");












mysql_query("CREATE TABLE `".PFX."txp_discuss_nonce` (
  `issue_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `nonce` varchar(255) NOT NULL default '',
  `used` tinyint(4) NOT NULL default '0'
) TYPE=MyISAM");












mysql_query("CREATE TABLE `".PFX."txp_form` (
  `name` varchar(64) NOT NULL default '',
  `type` varchar(28) NOT NULL default '',
  `Form` text NOT NULL,
  PRIMARY KEY  (`name`),
  KEY `name` (`name`)
) TYPE=MyISAM PACK_KEYS=1");





mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('Links', 'link', '<p><txp:link /><br />\r\n<txp:link_description /></p>')");
mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('lofi', 'article', '<h3><txp:title /></h3>\r\n<p><small><txp:permlink>#</txp:permlink> <txp:posted /></small></p>\r\n<txp:body />\r\n<hr size=\"1\" noshade=\"noshade\" />')");
mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('Single', 'article', '<h3><txp:title /> <span class=\"permlink\"><txp:permlink>::</txp:permlink></span> <span class=\"date\"><txp:posted /></span></h3>\r\n<txp:body />')");
mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('plainlinks', 'link', '<txp:linkdesctitle /><br />')");
mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('comments', 'comment', '<txp:message /><br />\r\n<small>&#8212; <txp:comment_name /> &#160;&#160; <txp:comment_time /> &#160;&#160; <txp:comment_permlink>#</txp:comment_permlink></small>')");
mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('default', 'article', '<h3><txp:permlink><txp:title /></txp:permlink> &#183; <txp:posted /></h3>\r\n<txp:body />\r\n<p><txp:comments_invite /> </p> \r\n<p>* * *</p>')");
mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('comment_form', 'comment', '  <table cellpadding=\"4\" cellspacing=\"0\" border=\"0\">\r\n	<tr>\r\n	  <td align=\"right\" valign=\"top\">\r\n	   	<txp:text item=\"name\" />\r\n	  </td>\r\n   <td valign=\"top\">\r\n	  	<txp:comment_name_input />\r\n	  </td>\r\n	  <td valign=\"top\" align=\"left\">\r\n	  	<txp:comment_remember />\r\n	  </td> \r\n	</tr>\r\n	<tr>\r\n	  <td align=\"right\" valign=\"top\">\r\n	  	<txp:text item=\"email\" />\r\n	  </td>\r\n	  <td valign=\"top\" colspan=\"2\">\r\n	  	<txp:comment_email_input />\r\n	  </td>\r\n    </tr>\r\n	<tr> \r\n	  <td align=\"right\" valign=\"top\">\r\n	  	http://\r\n	  </td>\r\n	  <td valign=\"top\" colspan=\"2\">\r\n	  	<txp:comment_web_input />\r\n	  </td>\r\n	</tr>\r\n	<tr>\r\n	  <td valign=\"top\" align=\"right\">\r\n	  	<txp:text item=\"message\" />\r\n	  </td>\r\n	  <td valign=\"top\" colspan=\"2\">\r\n	  	<txp:comment_message_input />\r\n	  </td>\r\n	</tr>\r\n	<tr>\r\n	  <td align=\"right\" valign=\"top\">&nbsp;</td>\r\n	  <td valign=\"top\" align=\"left\">\r\n		<txp:comments_help />\r\n	  </td>\r\n	  <td align=\"right\" valign=\"top\">\r\n		<txp:comment_preview />\r\n		<txp:comment_submit />\r\n	  </td>\r\n	</tr>\r\n  </table>')");
mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('Noted', 'link', '<p> <txp:link />. <txp:link_description /></p>')");
mysql_query("INSERT INTO `".PFX."txp_form` VALUES ('files', 'file', '<txp:text item=\"file\" />: \n<txp:file_download_link>\n<txp:file_download_name /> [<txp:file_download_size format=\"auto\" decimals=\"2\" />]\n</txp:file_download_link>\n<br />\n<txp:text item=\"category\" />: <txp:file_download_category /><br />\n<txp:text item=\"download\" />: <txp:file_download_downloads />'')");







mysql_query("CREATE TABLE `".PFX."txp_image` (
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
) TYPE=MyISAM PACK_KEYS=0 AUTO_INCREMENT=1 ");












mysql_query("CREATE TABLE `".PFX."txp_link` (
  `id` int(6) NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `category` varchar(64) NOT NULL default '',
  `url` text NOT NULL,
  `linkname` varchar(255) NOT NULL default '',
  `linksort` varchar(128) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM PACK_KEYS=1 AUTO_INCREMENT=1 ");












mysql_query("CREATE TABLE `".PFX."txp_log` (
  `id` int(12) NOT NULL auto_increment,
  `time` datetime NOT NULL default '0000-00-00 00:00:00',
  `host` varchar(255) NOT NULL default '',
  `page` varchar(255) NOT NULL default '',
  `refer` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `time` (`time`)
) TYPE=MyISAM AUTO_INCREMENT=29 ");





mysql_query("INSERT INTO `".PFX."txp_log` VALUES (23, '2004-03-05 17:02:44', 'localhost', '/', '')");
mysql_query("INSERT INTO `".PFX."txp_log` VALUES (24, '2004-03-05 17:02:54', 'localhost', '/index.php?q=fermentum', 'textism.local/')");
mysql_query("INSERT INTO `".PFX."txp_log` VALUES (25, '2004-03-11 16:47:03', 'localhost', '/textpattern/_g116update.php', '')");
mysql_query("INSERT INTO `".PFX."txp_log` VALUES (26, '2004-03-13 16:42:34', 'localhost', '/', '')");
mysql_query("INSERT INTO `".PFX."txp_log` VALUES (27, '2004-03-13 18:12:51', 'localhost', '/', '')");
mysql_query("INSERT INTO `".PFX."txp_log` VALUES (28, '2004-04-11 16:39:01', 'localhost', '/textpattern/_update.php', '')");







mysql_query("CREATE TABLE `".PFX."txp_log_mention` (
  `article_id` int(11) NOT NULL default '0',
  `refpage` varchar(255) NOT NULL default '',
  `reftitle` varchar(255) NOT NULL default '',
  `count` int(11) NOT NULL default '0',
  `excerpt` mediumtext NOT NULL,
  KEY `refpage` (`refpage`)
) TYPE=MyISAM");












mysql_query("CREATE TABLE `".PFX."txp_page` (
  `name` varchar(128) NOT NULL default '',
  `user_html` text NOT NULL,
  PRIMARY KEY  (`name`),
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM PACK_KEYS=1");





mysql_query("INSERT INTO `".PFX."txp_page` VALUES ('default', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\r\n        \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\r\n	<link rel=\"stylesheet\" href=\"<txp:css />\" type=\"text/css\" />\r\n	<title><txp:page_title /></title>\r\n</head>\r\n<body>\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n<div id=\"head\">\r\n<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n\r\n</div>\r\n\r\n<!-- left -->\r\n<div id=\"left\">\r\n\r\n	<txp:linklist wraptag=\"p\" />\r\n\r\n</div>\r\n\r\n<!-- right -->\r\n<div id=\"right\">\r\n\r\n		<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n		<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n		<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link label=\"Atom\" flavor=\"atom\" /></p>\r\n\r\n</div>\r\n\r\n<!-- center -->\r\n<div id=\"center\">\r\n\r\n	<txp:article />\r\n<txp:if_individual_article>\r\n<p>\r\n<txp:link_to_prev><txp:prev_title /></txp:link_to_prev>\r\n<txp:link_to_next><txp:next_title /></txp:link_to_next>\r\n</p>\r\n</txp:if_individual_article>\r\n<txp:if_article_list>\r\n<p>\r\n<txp:older>Previous</txp:older>\r\n<txp:newer>Next</txp:newer>\r\n</p>\r\n</txp:if_article_list>\r\n</div>\r\n\r\n<!-- footer -->\r\n<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n')");
mysql_query("INSERT INTO `".PFX."txp_page` VALUES ('archive', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\r\n        \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\r\n	<link rel=\"stylesheet\" href=\"<txp:css />\" type=\"text/css\" />\r\n	<title><txp:page_title /></title>\r\n</head>\r\n<body>\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n<div id=\"head\">\r\n<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n\r\n</div>\r\n\r\n<!-- left -->\r\n<div id=\"left\">\r\n\r\n	<txp:linklist />\r\n\r\n</div>\r\n\r\n<!-- right -->\r\n<div id=\"right\">\r\n		<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n		<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n		<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link label=\"Atom\" flavor=\"atom\" /></p>\r\n</div>\r\n\r\n<!-- center -->\r\n<div id=\"center\">\r\n	<txp:article limit=\"5\" />\r\n\r\n<txp:if_individual_article>\r\n<p>\r\n<txp:link_to_prev><txp:prev_title /></txp:link_to_prev>\r\n<txp:link_to_next><txp:next_title /></txp:link_to_next>\r\n</p>\r\n</txp:if_individual_article>\r\n<txp:if_article_list>\r\n<p>\r\n<txp:older>Previous</txp:older>\r\n<txp:newer>Next</txp:newer>\r\n</p>\r\n</txp:if_article_list>\r\n\r\n</div>\r\n\r\n<!-- footer -->\r\n<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>\r\n')");







mysql_query("CREATE TABLE `".PFX."txp_plugin` (
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
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`)
) TYPE=MyISAM");












mysql_query("CREATE TABLE `".PFX."txp_prefs` (
  `prefs_id` int(11) default NULL,
  `name` varchar(255) default NULL,
  `val` varchar(255) default NULL,
  KEY `name` (`name`)
) TYPE=MyISAM");





mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'prefs_id', '1')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'sitename', 'My Site')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'siteurl', 'thissite.com')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'site_slogan', 'My pithy slogan')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'language', 'english')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'url_mode', '1')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'timeoffset', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_on_default', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_default_invite', 'Comment')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_mode', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_disabled_after', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_textile', '2')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'ping_weblogsdotcom', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'rss_how_many', '5')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'logging', 'all')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_comments', '1')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_categories', '1')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_sections', '1')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'send_lastmod', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'path_from_root', '/')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'lastmod', '2004-02-19 20:04:48')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_dateformat', 'M j, g:ia')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'dateformat', 'since')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'archive_dateformat', 'j F y')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'logs_expire', '7')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_moderate', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'img_dir', 'images')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'record_mentions', '1')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_disallow_images', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_sendmail', '0')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_max_upload_size', '2000000')");
mysql_query("INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_list_pageby', '25')");






mysql_query("CREATE TABLE `".PFX."txp_section` (
  `name` varchar(128) NOT NULL default '',
  `page` varchar(128) NOT NULL default '',
  `css` varchar(128) NOT NULL default '',
  `is_default` int(2) NOT NULL default '0',
  `in_rss` int(2) NOT NULL default '1',
  `on_frontpage` int(2) NOT NULL default '1',
  `searchable` int(2) NOT NULL default '1',
  PRIMARY KEY  (`name`),
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM PACK_KEYS=1");





mysql_query("INSERT INTO `".PFX."txp_section` VALUES ('article', 'archive', 'default', 1, 1, 1, 1)");
mysql_query("INSERT INTO `".PFX."txp_section` VALUES ('default', 'default', 'default', 0, 1, 1, 1)");
mysql_query("INSERT INTO `".PFX."txp_section` VALUES ('about', 'default', 'default', 0, 0, 0, 1)");







mysql_query("CREATE TABLE `".PFX."txp_users` (
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
) TYPE=MyISAM PACK_KEYS=1 AUTO_INCREMENT=2 ");



mysql_query("CREATE TABLE `".PFX."txp_file` ( 
		`id` int(11) NOT NULL auto_increment,
		`filename` varchar( 255 ) NOT NULL default '',
		`category` varchar( 255 ) NOT NULL default '',
		`permissions` varchar( 32 ) NOT NULL DEFAULT '0',
		`description` text NOT NULL default '',
		`downloads` int(4) unsigned NOT NULL default '0',
		PRIMARY KEY ( `id` ) ,
		UNIQUE KEY `filename` ( `filename` ) 
	)  TYPE=MyISAM PACK_KEYS=0 AUTO_INCREMENT=1 ");


mysql_query("CREATE TABLE `".PFX."txp_lang` (
			`id` INT( 9 ) NOT NULL AUTO_INCREMENT ,
			`lang` VARCHAR(16),
			`name` VARCHAR(64),
			`event` VARCHAR( 64 ) ,
			`data` TINYTEXT,
			`lastmod` timestamp,
			PRIMARY KEY ( `id` ),
			UNIQUE INDEX (`lang`,`name`),
			INDEX (`lang`, `event`)
			)TYPE=MyISAM;");

include_once 'en-gb.php';
if (!@$lastmod) $lastmod = '0000-00-00 00:00:00';
foreach ($en_gb_lang as $evt_name => $evt_strings)
{
	foreach ($evt_strings as $lang_key => $lang_val)
	{
		if (@$lang_val)
			mysql_query("INSERT DELAYED INTO `".PFX."txp_lang`  SET `lang`='en-gb',`name`='$lang_key',`event`='$evt_name',`data`='$lang_val',`lastmod`='$lastmod'");
	}
}

?>

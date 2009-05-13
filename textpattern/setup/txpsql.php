<?php

/*
$HeadURL$
$LastChangedRevision$
*/


if (!defined('TXP_INSTALL'))
	exit;

@ignore_user_abort(1);
@set_time_limit(0);

mysql_connect($dhost,$duser,$dpass,false,$dclient_flags);
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
	if ($dbcharset == 'utf8')
		$tabletype .= " COLLATE utf8_general_ci ";
	mysql_query("SET NAMES ".$dbcharset);
}

// Default to messy URLs if we know clean ones won't work
$permlink_mode = 'section_id_title';
if (is_callable('apache_get_modules'))
{
	$modules = apache_get_modules();
	if (!in_array('mod_rewrite', $modules))
		$permlink_mode = 'messy';
}
else
{
	$server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST'])
		? ( (@$_SERVER['SERVER_SOFTWARE']) ?  @$_SERVER['SERVER_SOFTWARE'] :  $_SERVER['HTTP_HOST'] )
		: '';
	if (!stristr($server_software, 'Apache'))
		$permlink_mode = 'messy';
}

$name = ps('name') ? ps('name') : 'anon';

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

$setup_comment_invite = doSlash( ( gTxt('setup_comment_invite')=='setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite') );
$create_sql[] = "INSERT INTO `".PFX."textpattern` VALUES (1, now(), '".doSlash($name)."', now(), '', 'Welcome to Your Site!', '', 'h3. What do you want to do next?\n\n* Modify or even delete this article? The \"article list\":siteurl/textpattern/index.php?event=list is the place to start.\n* Change this site\'s name, or modify the style of the URLs? It\'s all up to your \"preferences\":siteurl/textpattern/index.php?event=prefs.\n* Get yourself acquainted with Textile, the humane web text generator which comes with Textpattern? The basics are \"simple\":http://textpattern.com/textile-sandbox. If you want to learn more about Textile, you can dig into an \"extensive manual\":http://textpattern.com/textile-reference-manual later.\n* Be guided through your \"Textpattern first steps\":http://textpattern.com/textpattern-first-steps by completing some basic tasks?\n* Study the \"Textpattern Semantic Model?\":http://textpattern.com/textpattern-semantic-model\n* Add \"another user\":siteurl/textpattern/index.php?event=admin, or extend the capabilities with \"third party plugins\":siteurl/textpattern/index.php?event=plugin you discovered from the central plugin directory at \"Textpattern Resources\":http://textpattern.org/?\n* Dive in and learn by trial and error? Then please note:\n** When you \"write\":siteurl/textpattern/index.php?event=article an article you assign it to a section of your site.\n** Sections use a \"page template\":siteurl/textpattern/index.php?event=page and a \"style\":siteurl/textpattern/index.php?event=css as an output scaffold.\n** Page templates use XHTML and Textpattern tags (like this: @<txp:article />@) to build the markup.\n** Some Textpattern tags use \"forms\":siteurl/textpattern/index.php?event=form, which are building blocks for reusable snippets of code and markup you may build and use at your discretion.\n\nThere are a host of \"Frequently Asked Questions\":http://textpattern.com/faq/ to help you get started.\n\n\"Textpattern tags\":http://textpattern.com/textpattern-tag-reference, their attributes and values are as well explained as sampled at the \"Textbook Wiki\":http://textbook.textpattern.net/, where you will also find valuable tips and tutorials.\n\nIf all else fails, there\'s a whole crowd of friendly, helpful people over at the \"Textpattern support forum\":http://forum.textpattern.com/. Come and pay a visit!\n', '\t<h3>What do you want to do next?</h3>\n\n\t<ul>\n\t\t<li>Modify or even delete this article? The <a href=\"siteurl/textpattern/index.php?event=list\">article list</a> is the place to start.</li>\n\t\t<li>Change this site&#8217;s name, or modify the style of the <span class=\"caps\">URL</span>s? It&#8217;s all up to your <a href=\"siteurl/textpattern/index.php?event=prefs\">preferences</a>.</li>\n\t\t<li>Get yourself acquainted with Textile, the humane web text generator which comes with Textpattern? The basics are <a href=\"http://textpattern.com/textile-sandbox\">simple</a>. If you want to learn more about Textile, you can dig into an <a href=\"http://textpattern.com/textile-reference-manual\">extensive manual</a> later.</li>\n\t\t<li>Be guided through your <a href=\"http://textpattern.com/textpattern-first-steps\">Textpattern first steps</a> by completing some basic tasks?</li>\n\t\t<li>Study the <a href=\"http://textpattern.com/textpattern-semantic-model\">Textpattern Semantic Model?</a></li>\n\t\t<li>Add <a href=\"siteurl/textpattern/index.php?event=admin\">another user</a>, or extend the capabilities with <a href=\"siteurl/textpattern/index.php?event=plugin\">third party plugins</a> you discovered from the central plugin directory at <a href=\"http://textpattern.org/\">Textpattern Resources</a>?</li>\n\t\t<li>Dive in and learn by trial and error? Then please note:\n\t<ul>\n\t\t<li>When you <a href=\"siteurl/textpattern/index.php?event=article\">write</a> an article you assign it to a section of your site.</li>\n\t\t<li>Sections use a <a href=\"siteurl/textpattern/index.php?event=page\">page template</a> and a <a href=\"siteurl/textpattern/index.php?event=css\">style</a> as an output scaffold.</li>\n\t\t<li>Page templates use <span class=\"caps\">XHTML</span> and Textpattern tags (like this: <code>&lt;txp:article /&gt;</code>) to build the markup.</li>\n\t\t<li>Some Textpattern tags use <a href=\"siteurl/textpattern/index.php?event=form\">forms</a>, which are building blocks for reusable snippets of code and markup you may build and use at your discretion.</li>\n\t</ul></li>\n\t</ul>\n\n\t<p>There are a host of <a href=\"http://textpattern.com/faq/\">Frequently Asked Questions</a> to help you get started.</p>\n\n\t<p><a href=\"http://textpattern.com/textpattern-tag-reference\">Textpattern tags</a>, their attributes and values are as well explained as sampled at the <a href=\"http://textbook.textpattern.net/\">Textbook Wiki</a>, where you will also find valuable tips and tutorials.</p>\n\n\t<p>If all else fails, there&#8217;s a whole crowd of friendly, helpful people over at the <a href=\"http://forum.textpattern.com/\">Textpattern support forum</a>. Come and pay a visit!</p>', '', '', '', 'hope-for-the-future', 'meaningful-labor', 1, '".$setup_comment_invite."', 1, 4, 1, 1, 'articles', '', '', 'welcome-to-your-site', '', '', '', '', '', '', '', '', '', '', '".md5(uniqid(rand(), true))."', now())";


$create_sql[] = "CREATE TABLE `".PFX."txp_category` (
  `id` int(6) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `type` varchar(64) NOT NULL default '',
  `parent` varchar(64) NOT NULL default '',
  `lft` int(6) NOT NULL default '0',
  `rgt` int(6) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) $tabletype PACK_KEYS=1 AUTO_INCREMENT=10 ";

$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (1, 'root', 'article', '', 1, 8, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (2, 'root', 'link', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (3, 'root', 'image', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (4, 'root', 'file', '', 1, 2, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (5, 'hope-for-the-future', 'article', 'root', 2, 3, 'Hope for the Future')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (6, 'meaningful-labor', 'article', 'root', 4, 5, 'Meaningful Labor')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (7, 'reciprocal-affection', 'article', 'root', 6, 7, 'Reciprocal Affection')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (8, 'textpattern', 'link', 'root', 2, 3, 'Textpattern')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (9, 'site-design', 'image', 'root', 2, 3, 'Site Design')";


$create_sql[] = "CREATE TABLE `".PFX."txp_css` (
  `name` varchar(255) NOT NULL,
  `css` text NOT NULL,
  UNIQUE KEY `name` (`name`)
) $tabletype ";

$create_sql[] = "INSERT INTO `".PFX."txp_css` VALUES ('default', 'LyogYmFzZQ0KLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8NCg0KYm9keSB7DQoJbWFyZ2luOiAwOw0KCXBhZGRpbmc6IDA7DQoJZm9udC1mYW1pbHk6IFZlcmRhbmEsICJMdWNpZGEgR3JhbmRlIiwgVGFob21hLCBIZWx2ZXRpY2EsIHNhbnMtc2VyaWY7DQoJY29sb3I6ICMwMDA7DQoJYmFja2dyb3VuZC1jb2xvcjogI2ZmZjsNCn0NCg0KYmxvY2txdW90ZSwgaDMsIHAsIGxpIHsNCglwYWRkaW5nLXJpZ2h0OiAxMHB4Ow0KCXBhZGRpbmctbGVmdDogMTBweDsNCglmb250LXNpemU6IDAuOWVtOw0KCWxpbmUtaGVpZ2h0OiAxLjZlbTsNCn0NCg0KYmxvY2txdW90ZSB7DQoJbWFyZ2luLXJpZ2h0OiAwOw0KCW1hcmdpbi1sZWZ0OiAyMHB4Ow0KfQ0KDQpoMSwgaDIsIGgzIHsNCgltYXJnaW46IDAgMCAxNXB4IDA7DQoJcGFkZGluZzogMCAxMHB4Ow0KCWZvbnQtd2VpZ2h0OiBub3JtYWw7DQp9DQoNCmgxLCBoMiB7DQoJZm9udC1mYW1pbHk6IEdlb3JnaWEsIFRpbWVzLCBzZXJpZjsNCn0NCg0KaDEgew0KCWZvbnQtc2l6ZTogMS40ZW07DQp9DQoNCmgyIHsNCglmb250LXNpemU6IDFlbTsNCglmb250LXN0eWxlOiBpdGFsaWM7DQp9DQoNCmhyIHsNCgltYXJnaW46IDJlbSBhdXRvOw0KCXdpZHRoOiAzNzBweDsNCgloZWlnaHQ6IDFweDsNCgljb2xvcjogIzdhN2U3ZDsNCgliYWNrZ3JvdW5kLWNvbG9yOiAjN2E3ZTdkOw0KCWJvcmRlcjogbm9uZTsNCn0NCg0Kc21hbGwsIC5zbWFsbCB7DQoJZm9udC1zaXplOiAwLjllbTsNCn0NCg0KLyogbGlua3MNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCmEgew0KCXRleHQtZGVjb3JhdGlvbjogbm9uZTsNCgljb2xvcjogIzAwMDsNCglib3JkZXItYm90dG9tOiAxcHggIzAwMCBzb2xpZDsNCn0NCg0KYSBpbWcgew0KCWJvcmRlcjogbm9uZTsNCn0NCg0KaDEgYSwgaDIgYSwgaDMgYSB7DQoJYm9yZGVyOiBub25lOw0KfQ0KDQpoMyBhIHsNCglmb250OiAxLjVlbSBHZW9yZ2lhLCBUaW1lcywgc2VyaWY7DQp9DQoNCiNzaXRlLW5hbWUgYSB7DQoJYm9yZGVyOiBub25lOw0KfQ0KDQojc2lkZWJhci0yIGEsICNzaWRlYmFyLTEgYSB7DQoJY29sb3I6ICNjMDA7DQoJYm9yZGVyOiBub25lOw0KfQ0KDQovKiBsYXlvdXQNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCiNhY2Nlc3NpYmlsaXR5IHsNCglwb3NpdGlvbjogYWJzb2x1dGU7DQoJdG9wOiAtMTAwMDBweDsNCn0NCg0KI2NvbnRhaW5lciB7DQoJbWFyZ2luOiAxMHB4IGF1dG87DQoJcGFkZGluZzogMTBweDsNCgl3aWR0aDogNzYwcHg7DQp9DQoNCiNoZWFkIHsNCgl0ZXh0LWFsaWduOiBjZW50ZXI7DQp9DQoNCiNzaXRlLW5hbWUgew0KCW1hcmdpbjogMTVweCAwOw0KCWZvbnQ6IDNlbSBHZW9yZ2lhLCBUaW1lcywgc2VyaWY7DQp9DQoNCiNzaXRlLXNsb2dhbiB7DQoJZm9udDogaXRhbGljIDFlbSBHZW9yZ2lhLCBUaW1lcywgc2VyaWY7DQp9DQoNCiNzaWRlYmFyLTEsICNzaWRlYmFyLTIgew0KCXBhZGRpbmctdG9wOiA1MHB4Ow0KCXdpZHRoOiAxNTBweDsNCn0NCg0KI3NpZGViYXItMSB7DQoJbWFyZ2luLXJpZ2h0OiA1cHg7DQoJZmxvYXQ6IGxlZnQ7DQoJdGV4dC1hbGlnbjogcmlnaHQ7DQp9DQoNCiNzaWRlYmFyLTIgew0KCW1hcmdpbi1sZWZ0OiA1cHg7DQoJZmxvYXQ6IHJpZ2h0Ow0KfQ0KDQouc2VjdGlvbl9saXN0IHsNCgltYXJnaW46IDAgMCAxMHB4IDA7DQoJcGFkZGluZzogMDsNCglsaXN0LXN0eWxlLXR5cGU6IG5vbmU7DQp9DQoNCi5zZWN0aW9uX2xpc3QgdWwgew0KCWxpc3Qtc3R5bGUtdHlwZTogbm9uZTsNCn0NCg0KLnNlY3Rpb25fbGlzdCBsaSB7DQoJbWFyZ2luOiAwIDEwcHggMnB4IDA7DQoJcGFkZGluZzogMDsNCn0NCg0KI2NvbnRlbnQgew0KCW1hcmdpbjogMCAxNTVweDsNCglwYWRkaW5nLXRvcDogMzBweDsNCn0NCg0KI2Zvb3Qgew0KCW1hcmdpbi10b3A6IDVweDsNCgljbGVhcjogYm90aDsNCgl0ZXh0LWFsaWduOiBjZW50ZXI7DQp9DQoNCi8qIGJveCBtb2RlbCBoYWNrcw0KaHR0cDovL2FyY2hpdmlzdC5pbmN1dGlvLmNvbS92aWV3bGlzdC9jc3MtZGlzY3Vzcy80ODM4Ng0KLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8NCg0KI2NvbnRhaW5lciB7DQpcd2lkdGg6IDc3MHB4Ow0Kd1xpZHRoOiA3NjBweDsNCn0NCg0KI3NpZGViYXItMSwgI3NpZGViYXItMiB7DQpcd2lkdGg6IDE1MHB4Ow0Kd1xpZHRoOiAxNTBweDsNCn0NCg0KLyogb3ZlcnJpZGVzDQotLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLSAqLw0KDQojc2lkZWJhci0yIHAsICNzaWRlYmFyLTEgcCB7DQoJZm9udC1zaXplOiAwLjhlbTsNCglsaW5lLWhlaWdodDogMS41ZW07DQp9DQoNCi5jYXBzIHsNCglmb250LXNpemU6IDAuOWVtOw0KCWxldHRlci1zcGFjaW5nOiAwLjFlbTsNCn0NCg0KZGl2LmRpdmlkZXIgew0KCW1hcmdpbjogMmVtIDA7DQoJdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQovKiBhcnRpY2xlcw0KLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8NCg0KLmRpcmVjdG9yeSB7DQoJbGlzdC1zdHlsZS10eXBlOiBjaXJjbGU7DQp9DQoNCi5hdXRob3Igew0KCWZvbnQtc3R5bGU6IG5vcm1hbDsNCglmb250LXNpemU6IDAuOGVtOw0KfQ0KDQoucHVibGlzaGVkIHsNCglmb250LXNpemU6IDAuOGVtOw0KfQ0KDQovKiBjb21tZW50cw0KLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8NCg0KLmNvbW1lbnRzX2Vycm9yIHsNCgljb2xvcjogIzAwMDsNCgliYWNrZ3JvdW5kLWNvbG9yOiAjZmZmNGY0Ow0KfQ0KDQp1bC5jb21tZW50c19lcnJvciB7DQoJcGFkZGluZyA6IDAuM2VtOw0KCWxpc3Qtc3R5bGUtdHlwZTogY2lyY2xlOw0KCWxpc3Qtc3R5bGUtcG9zaXRpb246IGluc2lkZTsNCglib3JkZXI6IDJweCBzb2xpZCAjZmRkOw0KfQ0KDQpkaXYjY3ByZXZpZXcgew0KCWNvbG9yOiAjMDAwOw0KCWJhY2tncm91bmQtY29sb3I6ICNmMWYxZjE7DQoJYm9yZGVyOiAycHggc29saWQgI2RkZDsNCn0NCg0KZm9ybSN0eHBDb21tZW50SW5wdXRGb3JtIHRkIHsNCgl2ZXJ0aWNhbC1hbGlnbjogdG9wOw0KfQ0KDQojY29tbWVudHMtaGVscCB7DQoJbWFyZ2luOiAycHggMCAxNXB4IDA7DQoJZm9udC1zaXplOiAwLjdlbTsNCn0NCg0KLyogZXJyb3IgcGFnZQ0KLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8NCg0KLmVycm9yLXN0YXR1cyB7DQoJZm9udDogMS4zZW0gR2VvcmdpYSwgVGltZXMsIHNlcmlmOw0KfQ==')";

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

$create_sql[] = "INSERT INTO `".PFX."txp_discuss` VALUES (000001, 1, 'Donald Swain', 'me@here.com', 'example.com', '127.0.0.1', '2005-07-22 14:11:32', '<p>I enjoy your site very much.</p>', 1)";

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss_ipban` (
  `ip` varchar(255) NOT NULL default '',
  `name_used` varchar(255) NOT NULL default '',
  `date_banned` datetime NOT NULL default '0000-00-00 00:00:00',
  `banned_on_message` int(8) NOT NULL default '0',
  PRIMARY KEY (`ip`)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_discuss_nonce` (
  `issue_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `nonce` varchar(255) NOT NULL default '',
  `used` tinyint(4) NOT NULL default '0',
  `secret` varchar(255) NOT NULL default '',
  PRIMARY KEY (`nonce`)
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
  `name` varchar(64) NOT NULL,
  `type` varchar(28) NOT NULL default '',
  `Form` text NOT NULL,
  PRIMARY KEY (`name`)
) $tabletype PACK_KEYS=1";

$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('Links', 'link', '<p><txp:link /><br />\n<txp:link_description /></p>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('lofi', 'article', '<h3 class=\"entry-title\"><txp:title /> <txp:permlink>#</txp:permlink></h3>\n\n<p class=\"published\"><txp:posted /></p>\n\n<div class=\"entry-content\">\n<txp:body />\n</div>\n\n<hr />\n\n')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('single', 'article', '<h3 class=\"entry-title\"><txp:permlink><txp:title /></txp:permlink></h3>\n\t<p class=\"published\"><txp:posted /></p>\n\n<div class=\"entry-content\">\n<txp:body />\n</div>\n\n')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('plainlinks', 'link', '<txp:linkdesctitle /><br />')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('comments', 'comment', '<txp:comment_message />\n\n<p class=\"small\">&#8212; <txp:comment_name /> &#183; <txp:comment_time /> &#183; <txp:comment_permlink>#</txp:comment_permlink></p>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('default', 'article', '<txp:if_individual_article>\n<h1 class=\"entry-title\"><txp:permlink><txp:title /></txp:permlink></h1>\n<txp:else />\n<h3 class=\"entry-title\"><txp:permlink><txp:title /></txp:permlink></h3>\n</txp:if_individual_article>\n\n<p class=\"published\"><txp:posted /></p>\n\n<div class=\"entry-content\">\n<txp:body />\n</div>\n\n<address class=\"vcard author\">&#8212; <span class=\"fn\"><txp:author /></span></address>\n\n<p class=\"tags\"><txp:category1 title=\"1\" link=\"1\" />, <txp:category2 title=\"1\" link=\"1\" /></p>\n\n<txp:comments_invite wraptag=\"p\" />\n\n<div class=\"divider\"><txp:image id=\"1\" /></div>\n\n')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('comment_form', 'comment', '<table cellpadding=\"4\" cellspacing=\"0\" border=\"0\">\n<tr>\n\t<td align=\"right\">\n\t\t<label for=\"name\"><txp:text item=\"comment_name\" /></label>\n\t</td>\n\n\t<td>\n\t\t<txp:comment_name_input />\n\t\t<txp:comment_remember />\n\t</td>\n</tr>\n\n<tr>\n\t<td align=\"right\">\n\t\t<label for=\"email\"><txp:text item=\"comment_email\" /></label>\n\t</td>\n\n\t<td>\n\t\t<txp:comment_email_input />\n\t</td>\n</tr>\n\n<tr>\n\t<td align=\"right\">\n\t\t<label for=\"web\"><txp:text item=\"comment_web\" /></label>\n\t</td>\n\n\t<td>\n\t\t<txp:comment_web_input />\n\t</td>\n</tr>\n\n<tr>\n\t<td align=\"right\">\n\t\t<label for=\"message\"><txp:text item=\"comment_message\" /></label>\n\t</td>\n\n\t<td>\n\t\t<txp:comment_message_input />\n\t\t<div id=\"comments-help\"><txp:comments_help /></div>\n\t</td>\n\n</tr>\n\n<tr>\n\t<td>&nbsp;</td>\n\n\t<td>\n\t\t<txp:comment_preview />\n\t\t<txp:comment_submit />\n\t</td>\n</tr>\n\n</table>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('noted', 'link', '<p><txp:link />. <txp:link_description /></p>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('popup_comments', 'comment', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head>\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n\t<title><txp:page_title /></title>\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"<txp:css />\" />\n</head>\n<body>\n\n<div style=\"padding: 1em; width:300px;\">\n<txp:popup_comments />\n</div>\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('files', 'file', '<txp:text item=\"file\" />: \n<txp:file_download_link>\n<txp:file_download_name /> [<txp:file_download_size format=\"auto\" decimals=\"2\" />]\n</txp:file_download_link>\n<br />\n<txp:text item=\"category\" />: <txp:file_download_category /><br />\n<txp:text item=\"download\" />: <txp:file_download_downloads />')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('search_results', 'article', '<h3 class=\"entry-title\"><txp:permlink><txp:title /></txp:permlink></h3>\n\n<p class=\"published\"><txp:posted /></p>\n\n<p class=\"entry-summary\"><txp:search_result_excerpt /></p>\n\n<p class=\"small\"><txp:permlink><txp:permlink /></txp:permlink></p>\n\n<div class=\"divider\"><txp:image id=\"1\" /></div>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('comments_display', 'article', '<h2 id=\"<txp:text item=\"comment\" />\"><txp:comments_invite textonly=\"1\" showalways=\"1\" showcount=\"0\" /></h2>\n\n<txp:comments />\n\n<txp:if_comments_preview>\n<div id=\"cpreview\">\n<txp:comments_preview />\n</div>\n</txp:if_comments_preview>\n\n<txp:if_comments_allowed>\n<txp:comments_form isize=\"25\" msgcols=\"45\" msgrows=\"15\" />\n<txp:else />\n<p><txp:text item=\"comments_closed\" /></p>\n</txp:if_comments_allowed>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form` VALUES ('article_listing', 'article', '<txp:if_first_article><ul class=\"directory\"></txp:if_first_article>\n\n<li><span class=\"entry-title\"><txp:permlink><txp:title /></txp:permlink></span> &#183; <span class=\"published\"><txp:posted format=\"%Y-%m-%d\" /></span></li>\n\n<txp:if_last_article></ul></txp:if_last_article>')";

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

$create_sql[] = "INSERT INTO `".PFX."txp_image` VALUES (1, 'divider.gif', 'site-design', '.gif', 400, 1, '---', '', '2005-07-22 16:37:11', '".doSlash($name)."', 0)";
$create_sql[] = "INSERT INTO `".PFX."txp_image` VALUES (2, 'txp_slug105x45.gif', 'site-design', '.gif', 105, 45, 'Textpattern CMS', '', '1977-12-24 13:20:42', '".doSlash($name)."', 0)";

$create_sql[] = "CREATE TABLE `".PFX."txp_lang` (
  `id` int(9) NOT NULL auto_increment,
  `lang` varchar(16) NOT NULL,
  `name` varchar(64) NOT NULL,
  `event` varchar(64) NOT NULL,
  `data` tinytext,
  `lastmod` timestamp,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `lang` (`lang`,`name`),
  KEY `lang_2` (`lang`,`event`)
) $tabletype AUTO_INCREMENT=1 ";

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

$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (1, '2005-07-20 12:54:26', 'textpattern', 'http://textpattern.com/', 'Textpattern', 'Textpattern', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (2, '2005-07-20 12:54:41', 'textpattern', 'http://textpattern.net/', 'TextBook', 'TextBook', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (3, '2005-07-20 12:55:04', 'textpattern', 'http://textpattern.org/', 'Txp Resources', 'Txp Resources', '')";

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
  `name` varchar(128) NOT NULL,
  `user_html` text NOT NULL,
  PRIMARY KEY (`name`)
) $tabletype PACK_KEYS=1";

$create_sql[] = "INSERT INTO `".PFX."txp_page` VALUES ('default', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head>\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n\n\t<title><txp:page_title /></title>\n\n\t<link rel=\"home\" href=\"<txp:site_url />\" />\n\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n\n\t<txp:css format=\"link\" />\n\n\t<txp:rsd />\n</head>\n<body id=\"<txp:if_section name=\"\">front<txp:else /><txp:section /></txp:if_section>\">\n\n<!-- accessibility -->\n<div id=\"accessibility\">\n\t<ul>\n\t\t<li><a href=\"#content\"><txp:text item=\"go_content\" /></a></li>\n\t\t<li><a href=\"#sidebar-1\"><txp:text item=\"go_nav\" /></a></li>\n\t\t<li><a href=\"#sidebar-2\"><txp:text item=\"go_search\" /></a></li>\n\t</ul>\n</div>\n\n<div id=\"container\">\n\n<!-- head -->\n\t<div id=\"head\">\n\t\t<h1 id=\"site-name\"><txp:link_to_home><txp:site_name /></txp:link_to_home></h1>\n\t\t<p id=\"site-slogan\"><txp:site_slogan /></p>\n\t</div>\n\n<!-- left -->\n\t<div id=\"sidebar-1\">\n\t\t<txp:section_list default_title=\'<txp:text item=\"home\" />\' include_default=\"1\" wraptag=\"ul\" break=\"li\">\n\t\t\t<txp:if_section name=\'<txp:section />\'>&raquo;</txp:if_section>\n\t\t\t<txp:section link=\"1\" title=\"1\" />\n\t\t\t<txp:if_section name=\'<txp:section />\'>\n\t\t\t\t<txp:article_custom  section=\'<txp:section />\' wraptag=\"ul\" break=\"li\">\n\t\t\t\t\t<txp:if_article_id>&rsaquo;</txp:if_article_id>\n\t\t\t\t\t<txp:permlink><txp:title /></txp:permlink>\n\t\t\t\t</txp:article_custom>\n\t\t\t</txp:if_section>\n\t\t</txp:section_list>\n\t\t<txp:search_input wraptag=\"p\" />\n\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\n\t</div>\n\n<!-- right -->\n\t<div id=\"sidebar-2\">\n\t\t<txp:linklist wraptag=\"p\" />\n\n\t\t<p><a href=\"http://textpattern.com/\"><txp:image id=\"2\" /></a></p>\n\t</div>\n\n<!-- center -->\n\t<div id=\"content\">\n<txp:if_category>\n\t\t<h2><txp:category title=\"1\" /></h2>\n\n\t\t<div class=\"hfeed\">\n\t\t<txp:article form=\"article_listing\" limit=\"5\" />\n\t\t</div>\n<txp:else />\n\t<txp:if_search>\n\t\t<h2><txp:text item=\"search_results\" />: <txp:page_url type=\"q\" /></h2>\n\n\t\t<div class=\"divider\"><txp:image id=\"1\" /></div>\n\t</txp:if_search>\n\n\t\t<div class=\"hfeed\">\n\t\t<txp:article limit=\"5\" />\n\t\t</div>\n</txp:if_category>\n\n\t\n<txp:if_individual_article>\n\t\t<div class=\"divider\"><txp:image id=\"1\" /></div>\n\n\t\t<p><txp:link_to_prev>&#171; <txp:prev_title /></txp:link_to_prev> \n\t\t\t<txp:link_to_next><txp:next_title /> &#187;</txp:link_to_next></p>\n<txp:else />\n\t\t<p><txp:older>&#171; <txp:text item=\"older\" /></txp:older> \n\t\t\t<txp:newer><txp:text item=\"newer\" /> &#187;</txp:newer></p>\n</txp:if_individual_article>\n\t</div>\n\n<!-- footer -->\n\t<div id=\"foot\">&nbsp;</div>\n\n</div>\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_page` VALUES ('archive', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head>\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n\n\t<title><txp:page_title /></title>\n\n\t<link rel=\"home\" href=\"<txp:site_url />\" />\n\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n\n\t<txp:css format=\"link\" />\n\n\t<txp:rsd />\n</head>\n<body id=\"<txp:section />\">\n\n<!-- accessibility -->\n<div id=\"accessibility\">\n\t<ul>\n\t\t<li><a href=\"#content\"><txp:text item=\"go_content\" /></a></li>\n\t\t<li><a href=\"#sidebar-1\"><txp:text item=\"go_nav\" /></a></li>\n\t\t<li><a href=\"#sidebar-2\"><txp:text item=\"go_search\" /></a></li>\n\t</ul>\n</div>\n\n<div id=\"container\">\n\n<!-- head -->\n\t<div id=\"head\">\n\t\t<p id=\"site-name\"><txp:link_to_home><txp:site_name /></txp:link_to_home></p>\n\t\t<p id=\"site-slogan\"><txp:site_slogan /></p>\n\t</div>\n\n<!-- left -->\n\t<div id=\"sidebar-1\">\n\t\t<txp:section_list default_title=\'<txp:text item=\"home\" />\' include_default=\"1\" wraptag=\"ul\" break=\"li\">\n\t\t\t<txp:if_section name=\'<txp:section />\'>&raquo;</txp:if_section>\n\t\t\t<txp:section link=\"1\" title=\"1\" />\n\t\t\t<txp:if_section name=\'<txp:section />\'>\n\t\t\t\t<txp:article_custom  section=\'<txp:section />\' wraptag=\"ul\" break=\"li\">\n\t\t\t\t\t<txp:if_article_id>&rsaquo;</txp:if_article_id>\n\t\t\t\t\t<txp:permlink><txp:title /></txp:permlink>\n\t\t\t\t</txp:article_custom>\n\t\t\t</txp:if_section>\n\t\t</txp:section_list>\n\n\t\t<txp:search_input wraptag=\"p\" />\n\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\n\t</div>\n\n<!-- right -->\n\t<div id=\"sidebar-2\">\n\t\t<txp:linklist wraptag=\"p\" />\n\n\t\t<p><a href=\"http://textpattern.com/\"><txp:image id=\"2\" /></a></p>\n\t</div>\n\n<!-- center -->\n\t<div id=\"content\">\n\t\t<txp:if_article_list><h1><txp:section title=\"1\" /></h1></txp:if_article_list>\n\n\t\t<div class=\"hfeed\">\n\t\t<txp:article listform=\"article_listing\" limit=\"5\" />\n\t\t</div>\n\t\n<txp:if_individual_article>\n\t\t<div class=\"divider\"><txp:image id=\"1\" /></div>\n\n\t\t<p><txp:link_to_prev>&#171; <txp:prev_title /></txp:link_to_prev> \n\t\t\t<txp:link_to_next><txp:next_title /> &#187;</txp:link_to_next></p>\n<txp:else />\n\t\t<p><txp:older>&#171; <txp:text item=\"older\" /></txp:older> \n\t\t\t<txp:newer><txp:text item=\"newer\" /> &#187;</txp:newer></p>\n</txp:if_individual_article>\n\t</div>\n\n<!-- footer -->\n\t<div id=\"foot\">&nbsp;</div>\n\n</div>\n\n</body>\n</html>')";

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
  UNIQUE KEY `name` (`name`)
) $tabletype ";

$create_sql[] = "CREATE TABLE `".PFX."txp_prefs` (
  `prefs_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `val` varchar(255) NOT NULL default '',
  `type` smallint(5) unsigned NOT NULL default '2',
  `event` varchar(12) NOT NULL default 'publish',
  `html` varchar(64) NOT NULL default 'text_input',
  `position` smallint(5) unsigned NOT NULL default '0',
  `user_name` varchar(64) NOT NULL default '',
  UNIQUE KEY `prefs_idx` (`prefs_id`,`name`, `user_name`),
  KEY `name` (`name`),
  KEY `user_name` (`user_name`)
) $tabletype ";

$prefs['blog_uid'] = md5(uniqid(rand(),true));
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'prefs_id', '1', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'sitename', '".doSlash(gTxt('my_site'))."', 0, 'publish', 'text_input', 10, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'siteurl', 'comment.local', 0, 'publish', 'text_input', 20, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'site_slogan', '".doSlash(gTxt('my_slogan'))."', 0, 'publish', 'text_input', 30, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'language', 'en-gb', 2, 'publish', 'languages', 40, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'url_mode', '1', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'timeoffset', '0', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_on_default', '0', 0, 'comments', 'yesnoradio', 140, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_default_invite', '".$setup_comment_invite."', 0, 'comments', 'text_input', 180, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_mode', '0', 0, 'comments', 'commentmode', 200, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_disabled_after', '42', 0, 'comments', 'weeks', 210, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_textile', '2', 0, 'publish', 'pref_text', 110, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'ping_weblogsdotcom', '0', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'rss_how_many', '5', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'logging', 'all', 0, 'publish', 'logging', 100, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_comments', '1', 0, 'publish', 'yesnoradio', 120, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_categories', '1', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_sections', '1', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'send_lastmod', '0', 1, 'admin', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'path_from_root', '/', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'lastmod', '2005-07-23 16:24:10', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_dateformat', '%b %d, %I:%M %p', 0, 'comments', 'dateformats', 190, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'dateformat', 'since', 0, 'publish', 'dateformats', 70, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'archive_dateformat', '%b %d, %I:%M %p', 0, 'publish', 'dateformats', 80, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_moderate', '1', 0, 'comments', 'yesnoradio', 130, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'img_dir', 'images', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_disallow_images', '0', 0, 'comments', 'yesnoradio', 170, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_sendmail', '0', 0, 'comments', 'yesnoradio', 160, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_max_upload_size', '2000000', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'path_to_site', '', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'article_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'link_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'image_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'log_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_list_pageby', '25', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'permlink_mode', '".doSlash($permlink_mode)."', 0, 'publish', 'permlinkmodes', 90, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_are_ol', '1', 0, 'comments', 'yesnoradio', 150, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'is_dst', '0', 0, 'publish', 'yesnoradio', 60, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'locale', 'en_GB.UTF-8', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'tempdir', '".doSlash(find_temp_dir())."', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'file_base_path', '".doSlash(dirname(txpath).DS.'files')."', 1, 'admin', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_uid', '". $prefs['blog_uid'] ."', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_mail_uid', '".doSlash(ps('email'))."', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'blog_time_uid', '2005', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'edit_raw_css_by_default', '1', 1, 'css', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_page_php_scripting', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_article_php_scripting', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_raw_php_scripting', '0', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'textile_links', '0', 1, 'link', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'show_article_category_count', '1', 2, 'category', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'show_comment_count_in_feed', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'syndicate_body_or_excerpt', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'include_email_atom', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_means_site_updated', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'never_display_email', '0', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_require_name', '1', 1, 'comments', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_require_email', '1', 1, 'comments', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'articles_use_excerpts', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'allow_form_override', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'attach_titles_to_permalinks', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'permalink_title_format', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'expire_logs_after', '7', 1, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_plugins', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_1_set', 'custom1', 1, 'custom', 'text_input', 1, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_2_set', 'custom2', 1, 'custom', 'text_input', 2, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_3_set', '', 1, 'custom', 'text_input', 3, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_4_set', '', 1, 'custom', 'text_input', 4, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_5_set', '', 1, 'custom', 'text_input', 5, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_6_set', '', 1, 'custom', 'text_input', 6, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_7_set', '', 1, 'custom', 'text_input', 7, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_8_set', '', 1, 'custom', 'text_input', 8, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_9_set', '', 1, 'custom', 'text_input', 9, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'custom_10_set', '', 1, 'custom', 'text_input', 10, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'ping_textpattern_com', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_dns', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'admin_side_plugins', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comment_nofollow', '1', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'use_mail_on_feeds_id', '0', 1, 'publish', 'yesnoradio', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'max_url_len', '200', 1, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'spam_blacklists', 'sbl.spamhaus.org', 1, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'override_emailcharset', '0', 1, 'admin', 'yesnoradio', 21, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'production_status', 'testing', 0, 'publish', 'prod_levels', 210, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_auto_append', '1', 0, 'comments', 'yesnoradio', 211, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'dbupdatetime', '1122194504', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'version', '1.0rc4', 2, 'publish', 'text_input', 0, '')";

$create_sql[] = "CREATE TABLE `".PFX."txp_section` (
  `name` varchar(128) NOT NULL,
  `page` varchar(128) NOT NULL default '',
  `css` varchar(128) NOT NULL default '',
  `is_default` int(2) NOT NULL default '0',
  `in_rss` int(2) NOT NULL default '1',
  `on_frontpage` int(2) NOT NULL default '1',
  `searchable` int(2) NOT NULL default '1',
  `title` varchar(255) NOT NULL default '',
  PRIMARY KEY (`name`)
) $tabletype PACK_KEYS=1";

$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('articles', 'archive', 'default', 1, 1, 1, 1, 'Articles')";
$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('default', 'default', 'default', 0, 1, 1, 1, 'default')";
$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('about', 'default', 'default', 0, 0, 0, 1, 'About')";

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
		echo "<b>".$GLOBALS['txp_err_count'].".</b> ".mysql_error()."<br />\n";
		echo "<!--\n $query \n-->\n";
		$GLOBALS['txp_install_successful'] = false;
	}
}

# Skip the RPC language fetch when testing
if (defined('TXP_TEST'))
	return;

require_once txpath.'/lib/IXRClass.php';
$client = new IXR_Client('http://rpc.textpattern.com');

if (!$client->query('tups.getLanguage',$prefs['blog_uid'],LANG))
{
	# If cannot install from lang file, setup the english lang
	if (!install_language_from_file(LANG))
	{
		$lang = 'en-gb';
		include_once txpath.'/setup/en-gb.php';
		if (!@$lastmod) $lastmod = '0000-00-00 00:00:00';
		foreach ($en_gb_lang as $evt_name => $evt_strings)
		{
			foreach ($evt_strings as $lang_key => $lang_val)
			{
				$lang_val = doSlash($lang_val);
				if (@$lang_val)
					mysql_query("INSERT DELAYED INTO `".PFX."txp_lang` SET lang='en-gb', name='".$lang_key."', event='".$evt_name."', data='".$lang_val."', lastmod='".$lastmod."'");
			}
		}
	}
}
else
{
	$response = $client->getResponse();
	$lang_struct = unserialize($response);
	foreach ($lang_struct as $item)
	{
		foreach ($item as $name => $value)
			$item[$name] = doSlash($value);
		mysql_query("INSERT DELAYED INTO `".PFX."txp_lang` SET lang='".LANG."', name='".$item['name']."', event='".$item['event']."', data='".$item['data']."', lastmod='".strftime('%Y%m%d%H%M%S',$item['uLastmod'])."'");
	}
}

mysql_query("FLUSH TABLE `".PFX."txp_lang`");

?>

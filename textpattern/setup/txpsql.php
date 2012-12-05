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
if ($result) die("Textpattern database table already exists. Can't run setup.");


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
	$modules = @apache_get_modules();
	if (!is_array($modules) || !in_array('mod_rewrite', $modules))
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
$create_sql[] = "INSERT INTO `".PFX."textpattern` VALUES (1, now(), '".doSlash($name)."', now(), '', 'Welcome to Your Site!', '', 'h3. What do you want to do next?\n\n* Modify or even delete this article? The \"article list\":siteurl/textpattern/index.php?event=list is the place to start.\n* Change this site\'s name, or modify the style of the URLs? It\'s all up to your \"preferences\":siteurl/textpattern/index.php?event=prefs.\n* Get yourself acquainted with Textile, the humane web text generator which comes with Textpattern? The basics are \"simple\":http://textpattern.com/textile-sandbox. If you want to learn more about Textile, you can dig into an \"extensive manual\":http://textpattern.com/textile-reference-manual later.\n* Be guided through your \"Textpattern first steps\":http://textpattern.com/textpattern-first-steps by completing some basic tasks?\n* Study the \"Textpattern Semantic Model?\":http://textpattern.com/textpattern-semantic-model\n* Add \"another user\":siteurl/textpattern/index.php?event=admin, or extend the capabilities with \"third party plugins\":siteurl/textpattern/index.php?event=plugin you discovered from the central plugin directory at \"Textpattern Resources\":http://textpattern.org/?\n* Dive in and learn by trial and error? Then please note:\n** When you \"write\":siteurl/textpattern/index.php?event=article an article you assign it to a section of your site.\n** Sections use a \"page template\":siteurl/textpattern/index.php?event=page and a \"style\":siteurl/textpattern/index.php?event=css as an output scaffold.\n** Page templates use HTML and Textpattern tags (like this: @<txp:article />@) to build the markup.\n** Some Textpattern tags use \"forms\":siteurl/textpattern/index.php?event=form, which are building blocks for reusable snippets of code and markup you may build and use at your discretion.\n\nThere are a host of \"Frequently Asked Questions\":http://textpattern.com/faq/ to help you get started.\n\n\"Textpattern tags\":http://textpattern.com/textpattern-tag-reference, their attributes and values are as well explained as sampled at the \"User Documentation\":http://textpattern.net/, where you will also find valuable tips and tutorials.\n\nIf all else fails, there\'s a whole crowd of friendly, helpful people over at the \"Textpattern support forum\":http://forum.textpattern.com/. Come and pay a visit!\n', '\t<h3>What do you want to do next?</h3>\n\n\t<ul>\n\t\t<li>Modify or even delete this article? The <a href=\"siteurl/textpattern/index.php?event=list\">article list</a> is the place to start.</li>\n\t\t<li>Change this site&#8217;s name, or modify the style of the <span class=\"caps\">URL</span>s? It&#8217;s all up to your <a href=\"siteurl/textpattern/index.php?event=prefs\">preferences</a>.</li>\n\t\t<li>Get yourself acquainted with Textile, the humane web text generator which comes with Textpattern? The basics are <a href=\"http://textpattern.com/textile-sandbox\">simple</a>. If you want to learn more about Textile, you can dig into an <a href=\"http://textpattern.com/textile-reference-manual\">extensive manual</a> later.</li>\n\t\t<li>Be guided through your <a href=\"http://textpattern.com/textpattern-first-steps\">Textpattern first steps</a> by completing some basic tasks?</li>\n\t\t<li>Study the <a href=\"http://textpattern.com/textpattern-semantic-model\">Textpattern Semantic Model?</a></li>\n\t\t<li>Add <a href=\"siteurl/textpattern/index.php?event=admin\">another user</a>, or extend the capabilities with <a href=\"siteurl/textpattern/index.php?event=plugin\">third party plugins</a> you discovered from the central plugin directory at <a href=\"http://textpattern.org/\">Textpattern Resources</a>?</li>\n\t\t<li>Dive in and learn by trial and error? Then please note:\n\t<ul>\n\t\t<li>When you <a href=\"siteurl/textpattern/index.php?event=article\">write</a> an article you assign it to a section of your site.</li>\n\t\t<li>Sections use a <a href=\"siteurl/textpattern/index.php?event=page\">page template</a> and a <a href=\"siteurl/textpattern/index.php?event=css\">style</a> as an output scaffold.</li>\n\t\t<li>Page templates use <span class=\"caps\">HTML</span> and Textpattern tags (like this: <code>&lt;txp:article /&gt;</code>) to build the markup.</li>\n\t\t<li>Some Textpattern tags use <a href=\"siteurl/textpattern/index.php?event=form\">forms</a>, which are building blocks for reusable snippets of code and markup you may build and use at your discretion.</li>\n\t</ul></li>\n\t</ul>\n\n\t<p>There are a host of <a href=\"http://textpattern.com/faq/\">Frequently Asked Questions</a> to help you get started.</p>\n\n\t<p><a href=\"http://textpattern.com/textpattern-tag-reference\">Textpattern tags</a>, their attributes and values are as well explained as sampled at the <a href=\"http://textpattern.net/\">User Documentation</a>, where you will also find valuable tips and tutorials.</p>\n\n\t<p>If all else fails, there&#8217;s a whole crowd of friendly, helpful people over at the <a href=\"http://forum.textpattern.com/\">Textpattern support forum</a>. Come and pay a visit!</p>', '', '', '', 'hope-for-the-future', 'meaningful-labor', 1, '".$setup_comment_invite."', 1, 4, 1, 1, 'articles', '', '', 'welcome-to-your-site', '', '', '', '', '', '', '', '', '', '', '".md5(uniqid(rand(), true))."', now())";


$create_sql[] = "CREATE TABLE `".PFX."txp_category` (
  `id` int(6) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `type` varchar(64) NOT NULL default '',
  `parent` varchar(64) NOT NULL default '',
  `lft` int(6) NOT NULL default '0',
  `rgt` int(6) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) $tabletype PACK_KEYS=1";

$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (1, 'root', 'article', '', 1, 8, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (2, 'root', 'link', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (3, 'root', 'image', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (4, 'root', 'file', '', 1, 2, 'root')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (5, 'hope-for-the-future', 'article', 'root', 2, 3, 'Hope for the Future')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (6, 'meaningful-labor', 'article', 'root', 4, 5, 'Meaningful Labor')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (7, 'reciprocal-affection', 'article', 'root', 6, 7, 'Reciprocal Affection')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (8, 'textpattern', 'link', 'root', 2, 3, 'Textpattern')";


$create_sql[] = "CREATE TABLE `".PFX."txp_css` (
  `name` varchar(255) NOT NULL,
  `css` text NOT NULL,
  UNIQUE KEY `name` (`name`)
) $tabletype ";

// sql:txp_css
$create_sql[] = "INSERT INTO `".PFX."txp_css`(`name`,`css`) VALUES('default', '/* ==========================================================================\n   Styling and layout for all media\n   ========================================================================== */\n\n\n/* Abridged version of Normalize.css\n   ========================================================================== */\n/* original authors: Nicolas Gallagher and Jonathan Neal - http://necolas.github.com/normalize.css/ */\n\n/* corrects block display not defined in IE7-9, Firefox3 */\narticle,\naside,\ndetails,\nfigcaption,\nfigure,\nfooter,\nheader,\nhgroup,\nnav,\nsection,\nsummary {\n  display: block;\n}\n\n/* corrects inline-block display not defined in IE7-9, Firefox3 */\naudio,\ncanvas,\nvideo {\n  display: inline-block;\n  /* display and zoom IE7 fix for display:inline-block */\n  *display: inline;\n  *zoom: 1;\n}\n\n/* prevents modern browsers from displaying \'audio\' without controls, remove excess height in iOS5 devices */\naudio:not([controls]) {\n  display: none;\n  height: 0;\n}\n\n/* addresses styling for \'hidden\' attribute not present in IE7-9, Firefox3, Safari4 */\n[hidden] {\n  display: none;\n}\n\nhtml {\n  /* corrects text resizing oddly in IE7 when body font-size is set using em units -\n     http://clagnut.com/blog/348/#c790 */\n  font-size: 100%;\n  /* always force scrollbar padding so we don\'t get \'jumping\' */\n  overflow-y: scroll;\n  /* prevents iOS text size adjust after orientation change, without disabling user zoom -\n     http://www.456bereastreet.com/archive/201012/controlling_text_size_in_safari_for_ios_without_disabling_user_zoom/ */\n  -webkit-text-size-adjust: 100%;\n  /* as above, for Windows Phone */\n  -ms-text-size-adjust: 100%;\n}\n\n/* Addresses margins set differently in IE7 */\np,\npre {\n  margin: 1em 0;\n}\n\n/* addresses style set to \'bolder\' in Firefox3-4, Safari4-5, Chrome */\nb,\nstrong {\n  font-weight: bold;\n}\n\n/* addresses CSS quotes not supported in IE7, addresses quote property not supported in Safari4 */\nq {\n  quotes: none;\n}\n\nq:before,\nq:after {\n  content: \"\";\n  content: none;\n}\n\n/* prevents sub and sup affecting line-height in all browsers */\nsub,\nsup {\n  /* 12px */\n  font-size: .85714285714286em;\n  line-height: 0;\n  position: relative;\n  vertical-align: baseline;\n}\n\nsup {\n  top: -0.5em;\n}\n\nsub {\n  bottom: -0.25em;\n}\n\n/* removes border when inside \'a\' element in IE7-9, Firefox3, improves image quality when scaled in IE7 -\n   code.flickr.com/blog/2008/11/12/on-ui-quality-the-little-things-client-side-image-resizing/ */\nimg {\n  border: 0;\n  -ms-interpolation-mode: bicubic;\n}\n\n/* consistent tables */\ntable {\n  margin-bottom: 1em;\n  border-collapse: collapse;\n  border-spacing: 0;\n  width: 100%;\n}\n\n/* make table cells align top and left by default */\nth,\ntd {\n  vertical-align: top;\n  text-align: left;\n}\n\n/* addresses margins set differently in IE7 */\ndl,\nmenu,\nol,\nul {\n  margin: 1em 0;\n}\ndd {\n  margin: 0 0 0 2em;\n}\n\n/* remove margins from nested lists */\ndl dl,\nul ul,\nol ol {\n  margin: 0;\n}\n\n/* addresses paddings set differently in IE7 */\nmenu,\nol,\nul {\n  padding: 0 0 0 2em;\n}\n\n\n/* Clearfix\n   ========================================================================== */\n/* updated to prevent margin-collapsing on child elements in most situations -\n   http://nicolasgallagher.com/micro-clearfix-hack/ */\n\n.clearfix,\nheader,\nnav ul,\n.container,\nfooter,\n#paginator,\n#monthly-list {\n  /* zoom IE7 fix */\n  *zoom: 1;\n}\n\n.clearfix:before,\n.clearfix:after,\nheader:before,\nheader:after,\nnav ul:before,\nnav ul:after,\n.container:before,\n.container:after,\nfooter:before,\nfooter:after,\n#paginator:before,\n#paginator:after,\n#monthly-list:before,\n#monthly-list:after {\n  content: \"\";\n  display: table;\n}\n\n.clearfix:after,\nheader:after,\nnav ul:after,\n.container:after,\nfooter:after,\n#paginator:after,\n#monthly-list:after {\n  clear: both;\n}\n\n\n\n/* ==========================================================================\n   Styling and layout for screen media (mobile first)\n   ========================================================================== */\n\n\n@media screen {\n\n\n/* Layout\n   ========================================================================== */\n\nbody {\n  margin: 0;\n  background: #f7f7f7;\n}\n\n.wrapper {\n  border-bottom: solid 1px #ccc;\n  padding-top: 2em;\n  background: #fff;\n}\n\nheader,\n.container,\nfooter {\n  margin: 0 auto;\n  /* 960px / 1024px */\n  width: 93.75%;\n  max-width: 86em;\n}\n\nheader {\n  padding: 1em 0;\n}\n\nheader h1 {\n  margin: 0;\n}\n\nheader h3 {\n  /* 14px margin top */\n  margin: .66666666666667em 0 0;\n}\n\nnav {\n  border-top: solid 1px #e1a61a;\n  border-bottom: solid 1px #e1a61a;\n  background-color: #ffda44;\n  /* Konqueror */\n  background-image: -khtml-gradient(linear, left top, left bottom, from(#ffda44), to(#fabc2b));\n  /* Safari4+, Chrome */\n  background-image: -webkit-gradient(linear, left top, left bottom, from(#ffda44), to(#fabc2b));\n  /* Safari5.1+, Chrome10+ */\n  background-image: -webkit-linear-gradient(#ffda44, #fabc2b);\n  /* Firefox3.6 */\n  background-image: -moz-linear-gradient(#ffda44, #fabc2b);\n  /* Opera11.10+ */\n  background-image: -o-linear-gradient(#ffda44, #fabc2b);\n  /* CSS3 Compliant */\n  background-image: linear-gradient(#ffda44, #fabc2b);\n}\n\nnav h1 {\n  display: none;\n}\n\nnav ul {\n  margin: 0 auto;\n  padding: 0;\n  max-width: 86em;\n  list-style: none;\n  list-style-image: none;\n}\n\nnav li {\n  margin: 0;\n  border-bottom: solid 1px #e1a61a;\n}\n\nnav li:last-child {\n  border-bottom: 0;\n}\n\nnav li:hover,\nnav li.active {\n  background-color: #ffe477;\n  /* Konqueror */\n  background-image: -khtml-gradient(linear, left top, left bottom, from(#ffe477), to(#fbcc5d));\n  /* Safari4+, Chrome */\n  background-image: -webkit-gradient(linear, left top, left bottom, from(#ffe477), to(#fbcc5d));\n  /* Safari5.1+, Chrome10+ */\n  background-image: -webkit-linear-gradient(#ffe477, #fbcc5d);\n  /* Firefox3.6 */\n  background-image: -moz-linear-gradient(#ffe477, #fbcc5d);\n  /* Opera11.10+ */\n  background-image: -o-linear-gradient(#ffe477, #fbcc5d);\n  /* CSS3 Compliant */\n  background-image: linear-gradient(#ffe477, #fbcc5d);\n}\n\nnav li:active {\n  background-color: #fabc2b;\n  /* Konqueror */\n  background-image: -khtml-gradient(linear, left top, left bottom, from(#fabc2b), to(#ffda44));\n  /* Safari4+, Chrome */\n  background-image: -webkit-gradient(linear, left top, left bottom, from(#fabc2b), to(#ffda44));\n  /* Safari5.1+, Chrome10+ */\n  background-image: -webkit-linear-gradient(#fabc2b, #ffda44);\n  /* Firefox3.6 */\n  background-image: -moz-linear-gradient(#fabc2b, #ffda44);\n  /* Opera11.10+ */\n  background-image: -o-linear-gradient(#fabc2b, #ffda44);\n  /* CSS3 Compliant */\n  background-image: linear-gradient(#fabc2b, #ffda44);\n}\n\nnav a {\n  text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);\n  display: block;\n  padding: .5em 3.125%;\n  color: #333;\n}\n\n[role=\"article\"] {\n  margin-bottom: 2em;\n}\n\n[role=\"complementary\"] {\n  margin-bottom: 2em;\n  padding-top: 2em;\n  border-top: dashed 2px #ccc;\n}\n\n[role=\"search\"] p {\n  margin-top: 0;\n}\n\nfooter {\n  padding: .5em 0;\n}\n\n\n/* Links\n   ========================================================================== */\n\na {\n  position: relative;\n  text-decoration: none;\n  color: #114eb1;\n  -webkit-tap-highlight-color: rgba(0, 102, 255, 0.5);\n}\n\na:hover, a:active {\n  /* improves readability when focused and also mouse hovered in all browsers */\n  outline: 0;\n}\n\na:active {\n  top: 1px;\n}\n\na:focus {\n  outline: thin dotted #06f;\n}\n\nheader a {\n  color: #333;\n  -moz-border-radius: .11904761904762em;\n  border-radius: .11904761904762em;\n}\n\nheader a:hover, header a:active {\n  background: #e8e8e8;\n}\n\n[role=\"main\"] a:hover,\n[role=\"main\"] a:active,\n[role=\"complementary\"] a:hover,\n[role=\"complementary\"] a:active,\nfooter a:hover,\nfooter a:active {\n  text-decoration: underline;\n  color: #06f;\n}\n\n[role=\"main\"] a:visited,\n[role=\"complementary\"] a:visited,\nfooter a:visited {\n  color: #183082;\n}\n\n[role=\"main\"] h1 a {\n  color: #333;\n  -moz-border-radius: .11904761904762em;\n  border-radius: .11904761904762em;\n}\n\n[role=\"main\"] h1 a:visited {\n  color: #333;\n}\n\n[role=\"main\"] h1 a:hover,\n[role=\"main\"] h1 a:active {\n  text-decoration: none;\n  color: #333;\n  background: #efefef;\n}\n\n\n/* Typography\n   ========================================================================== */\n\nbody {\n  font-family: \"PT Serif\", Georgia, serif;\n  /* 14px / 16px */\n  font-size: 0.875em;\n  line-height: 1.5;\n  color: #333;\n}\n\nnav {\n  font-family: Arial, Helvetica, sans-serif;\n  font-weight: bold;\n}\n\nh1 {\n  font-family: Arial, Helvetica, sans-serif;\n  /* 28px */\n  font-size: 2em;\n  /* 34px / 28px */\n  line-height: 1.21428571428571;\n  letter-spacing: -1px;\n  /* 28px margin top/bottom */\n  margin: .66666666666667em 0;\n}\n\nh1:first-child {\n  margin-top: 0;\n}\n\nh2 {\n  font-family: Arial, Helvetica, sans-serif;\n  /* 21px */\n  font-size: 1.5em;\n  /* 28px / 21px */\n  line-height: 1.33333333333333;\n  /* 21px margin top/bottom */\n  margin: .75em 0;\n}\n\nh3 {\n  /* 18px */\n  font-size: 1.28571428571429em;\n  /* 26px / 18px */\n  line-height: 1.44444444444444;\n  font-weight: normal;\n  font-style: italic;\n  /* 16px margin top/bottom */\n  margin: .76190476190476em 0;\n}\n\nh4 {\n  font-family: Arial, Helvetica, sans-serif;\n  /* 16px */\n  font-size: 1.14285714285714em;\n  margin: 0;\n}\n\nblockquote {\n  /* 16px */\n  font-size: 1.14285714285714em;\n  font-style: italic;\n  margin: .875em 0 .875em 0;\n  padding: 1px .875em;\n  -moz-border-radius: .35714285714286em;\n  border-radius: .35714285714286em;\n  background: #fff6d3;\n}\n\naddress {\n  margin: 1em 0;\n}\n\n/* addresses styling not present in IE7-9, Safari5, Chrome */\nabbr[title],\ndfn[title] {\n  border-bottom: dotted 1px;\n  cursor: help;\n}\n\ndfn,\nmark,\nq,\nvar {\n  padding: 0 .21428571428571em;\n  -moz-border-radius: .21428571428571em;\n  border-radius: .21428571428571em;\n  color: #333;\n  background: #fff6d3;\n}\n\ndfn,\nq {\n  font-style: italic;\n}\n\nq q {\n  padding: 0;\n}\n\nvar {\n  font-weight: bold;\n}\n\npre,\ncode,\nkbd,\nsamp {\n  font-family: Cousine, Consolas, \"Lucida Console\", Monaco, monospace;\n}\n\ncode,\nkbd,\nsamp {\n  /* 13px */\n  font-size: .92857142857143em;\n  border: 1px solid #e3e3e3;\n  padding: 0 .23076923076923em;\n  -moz-border-radius: .23076923076923em;\n  border-radius: .23076923076923em;\n  background: #f7f7f7;\n}\n\npre {\n  /* 13px */\n  font-size: .92857142857143em;\n  overflow-x: auto;\n  border: 1px solid #e3e3e3;\n  padding: 1em;\n  -moz-border-radius: .35714285714286em;\n  border-radius: .35714285714286em;\n  background: #f7f7f7;\n  tab-size: 4;\n}\n\npre code {\n  /* 13px */\n  font-size: 1em;\n  border: 0;\n  background: none;\n}\n\nsmall,\nfigcaption,\ntfoot,\n.footnote {\n  /* 12px */\n  font-size: .85714285714286em;\n}\n\nfigcaption,\ntfoot,\n.footnote {\n  color: #888;\n}\n\nfigcaption {\n  margin-top: .33333333333333em;\n  font-style: italic;\n}\n\n\n/* Support for non-latin languages (can be removed if not required)\n   ========================================================================== */\n\nhtml[lang=\"ja-jp\"] {\n  font-family: \"Hiragino Kaku Gothic Pro\", Meiryo, sans-serif;\n}\n\nhtml[lang=\"ko-kr\"] {\n  font-family: GulimChe, Gulim, sans-serif;\n}\n\nhtml[lang=\"zh-cn\"] {\n  font-family: SimHei, sans-serif;\n}\n\nhtml[lang=\"zh-tw\"] {\n  font-family: PMingLiU, sans-serif;\n}\n\n\n/* Embedded content\n   ========================================================================== */\n\nimg,\nvideo {\n  max-width: 100%;\n  height: auto;\n}\n\nfigure {\n  margin: 0;\n}\n\n/* image alignemnts */\nimg.align-left {\n  float: left;\n  margin: 1em 1em 1em 0;\n}\nimg.align-right {\n  float: right;\n  margin: 1em 0 1em 1em;\n}\nimg.align-center {\n  display: block;\n  margin: 1em auto;\n}\n\n/* corrects overflow displayed oddly in IE9 */\n  svg:not(:root) {\n  overflow: hidden;\n}\n\n\n/* Tables\n   ========================================================================== */\n\ncaption {\n  font-style: italic;\n  text-align: left;\n  margin-bottom: .5em;\n}\n\nth,\ntd {\n  border-bottom: solid 1px #ccc;\n  padding: .28571428571429em .5em .28571428571429em 0;\n}\n\nth:last-child,\ntd:last-child {\n  padding-right: 0;\n}\n\nthead th,\nthead td {\n  border-bottom: solid 2px #ccc;\n}\n\ntfoot th,\ntfoot td {\n  border-bottom: 0;\n  padding: .33333333333333em .58333333333333em .33333333333333em 0;\n}\n\ntfoot:last-child {\n  padding-right: 0;\n}\n\n\n/* Lists\n   ========================================================================== */\n\ndt {\n  font-style: italic;\n}\n\n[role=\"main\"] #article-list {\n  list-style: none;\n  margin: 0 0 2em 0;\n  padding: 0;\n  border-top: solid 1px #ccc;\n}\n\n#article-list li {\n  border-bottom: solid 1px #ccc;\n  padding-top: 1em;\n  margin-bottom: 0;\n}\n\n\n/* Forms\n   ========================================================================== */\n\n/* corrects margin displayed oddly in IE6-7 */\nform {\n  margin: 0;\n}\n\n/* remove default fieldset styling across browsers */\nfieldset {\n  margin: 0;\n  border: 0;\n  padding: 0;\n}\n\n/* corrects text not wrapping in FF3, corrects alignment displayed oddly in IE7 */\nlegend {\n  border: 0;\n  padding: 0;\n  white-space: normal;\n  *margin-left: -7px;\n}\n\n/* improves appearance and consistency in all browsers */\nbutton,\ninput,\nselect,\ntextarea {\n  font-size: 100%;\n  margin: 0;\n  vertical-align: baseline;\n  /* improves appearance and consistency in IE7 */\n  *vertical-align: middle;\n}\n\n/* colour placeholder text (Webkit and Mozilla only, so far) */\ninput::-webkit-input-placeholder,\ntextarea::-webkit-input-placeholder {\n  color: #888;\n}\ninput:-moz-placeholder,\ntextarea:-moz-placeholder {\n  color: #888;\n}\n\n/* suppress red glow that Firefox adds to form fields by default, even when user is still typing\n   add focus glow on form elements and kill phantom spacing and dotted border that appears in Firefox */\nbutton:invalid,\na.button:invalid,\ninput:invalid,\ninput[type=\"button\"]:invalid,\ninput[type=\"reset\"]:invalid,\ninput[type=\"submit\"]:invalid,\ntextarea:invalid {\n  -webkit-box-shadow: none;\n  -moz-box-shadow: none;\n  box-shadow: none;\n}\nbutton::-moz-focus-inner,\na.button::-moz-focus-inner,\ninput::-moz-focus-inner,\ninput[type=\"button\"]::-moz-focus-inner,\ninput[type=\"reset\"]::-moz-focus-inner,\ninput[type=\"submit\"]::-moz-focus-inner,\ntextarea::-moz-focus-inner {\n  border: 0;\n  padding: 0;\n}\nbutton:focus,\na.button:focus,\ninput:focus,\ninput[type=\"button\"]:focus,\ninput[type=\"reset\"]:focus,\ninput[type=\"submit\"]:focus,\nselect:focus,\ntextarea:focus {\n  -webkit-box-shadow: 0 0 7px #0066ff;\n  -moz-box-shadow: 0 0 7px #0066ff;\n  box-shadow: 0 0 7px #0066ff;\n  /* Opera */\n  z-index: 1;\n}\n\n  /* remove inner padding and search cancel button in webkit on OS X */\ninput[type=\"search\"]::-webkit-search-decoration,\ninput[type=\"search\"]::-webkit-search-cancel-button {\n  -webkit-appearance: none;\n}\n\ninput[type=\"file\"]:focus,\ninput[type=\"file\"]:active,\ninput[type=\"radio\"]:focus,\ninput[type=\"radio\"]:active,\ninput[type=\"checkbox\"]:focus,\ninput[type=\"checkbox\"]:active {\n  -webkit-box-shadow: none;\n  -moz-box-shadow: none;\n  box-shadow: none;\n}\n\ntextarea,\ninput[type=\"color\"],\ninput[type=\"date\"],\ninput[type=\"datetime\"],\ninput[type=\"datetime-local\"],\ninput[type=\"email\"],\ninput[type=\"month\"],\ninput[type=\"number\"],\ninput[type=\"password\"],\ninput[type=\"search\"],\ninput[type=\"tel\"],\ninput[type=\"text\"],\ninput[type=\"time\"],\ninput[type=\"url\"],\ninput[type=\"week\"] {\n  /* remove iOS Safari default styling */\n  -webkit-appearance: none;\n  font-family: Arial, Helvetica, sans-serif;\n  /* 12px */\n  font-size: .85714285714286em;\n  text-align: left;\n  border: solid 1px #ccc;\n  padding: .5em;\n  background: #fff;\n  outline: 0;\n  -webkit-box-sizing: border-box;\n  -moz-box-sizing: border-box;\n  box-sizing: border-box;\n  -moz-border-radius: 0;\n  border-radius: 0;\n}\n\ninput[type=\"color\"] {\n  padding: 0;\n  height: 2.33333333333333em;\n}\n\n[role=\"complementary\"] input[type=\"search\"] {\n  margin-right: 2px;\n  width: 66.666666666667%;\n  display: inline-block;\n  /* display and zoom IE7 fix for display:inline-block */\n  *display: inline;\n  *zoom: 1;\n}\n\ntextarea {\n  min-height: 3em;\n  /* removes default vertical scrollbar in IE7-9 */\n  overflow: auto;\n  /* improves readability and alignment in all browsers */\n  vertical-align: top;\n  resize: vertical;\n  width: 100%;\n}\n\nselect {\n  font-family: Arial, Helvetica, sans-serif;\n  /* 12px */\n  font-size: .85714285714286em;\n  text-align: left;\n  border: solid 1px #ccc;\n  padding: .5em;\n  background: #fff;\n  -webkit-box-sizing: border-box;\n  -moz-box-sizing: border-box;\n  box-sizing: border-box;\n}\n\ntextarea,\nselect[size],\nselect[multiple] {\n  height: auto;\n}\n\noptgroup {\n  font-family: Arial, Helvetica, sans-serif;\n  font-style: normal;\n  font-weight: normal;\n  color: #333;\n}\n\n/* kill phantom spacing and dotted border that appears in Firefox */\noptgroup::-moz-focus-inner {\n  border: 0;\n  padding: 0;\n}\n\n/* addresses box sizing set to content-box and excess padding in IE7-9 */\ninput[type=\"checkbox\"],\ninput[type=\"radio\"] {\n  -webkit-box-sizing: border-box;\n  -moz-box-sizing: border-box;\n  box-sizing: border-box;\n  padding: 0;\n  *height: 1em;\n  *width: 1em;\n}\n\n/* make sure disable elements really are disabled */\nbutton[disabled],\ninput[disabled],\ninput[type=\"button\"][disabled],\ninput[type=\"reset\"][disabled],\ninput[type=\"submit\"][disabled],\nselect[disabled],\nselect[disabled] option,\nselect[disabled] optgroup,\ntextarea[disabled],\nspan.disabled {\n  opacity: 1;\n  -webkit-user-select: none;\n  -khtml-user-select: none;\n  -moz-user-select: -moz-none;\n  user-select: none;\n  border: solid 1px #d2d2d2 !important;\n  text-shadow: none !important;\n  color: #888888 !important;\n  background: #eee !important;\n  cursor: default !important;\n  top: 0 !important;\n}\n\n.large input {\n  width: 50%;\n  min-width: 302px;\n}\n\n.large textarea {\n  height: 156px;\n}\n\n.small input {\n  width: 25%;\n  min-width: 151px;\n}\n\n.small textarea {\n  height: 5.5em;\n}\n\n\n/* Buttons\n   ========================================================================== */\n\nbutton,\n[role] a.button,\nspan.disabled,\ninput[type=\"button\"],\ninput[type=\"reset\"],\ninput[type=\"submit\"] {\n  /* remove iOS Safari default styling */\n  -webkit-appearance: none;\n  -webkit-background-clip: padding;\n  -khtml-background-clip: padding-box;\n  -moz-background-clip: padding;\n  background-clip: padding-box;\n  width: auto;\n  overflow: visible;\n  font-family: Arial, Helvetica, sans-serif;\n  /* 12px */\n  font-size: .85714285714286em;\n  font-weight: normal;\n  line-height: normal;\n  text-align: center;\n  text-decoration: none;\n  text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);\n  cursor: pointer;\n  border: solid 1px #e1a61a;\n  -moz-border-radius: .35714285714286em;\n  border-radius: .35714285714286em;\n  padding: .5em 1em;\n  display: inline-block;\n  /* display and zoom IE7 fix for display:inline-block */\n  *display: inline;\n  *zoom: 1;\n  color: #333;\n  outline: 0;\n  background-color: #ffda44;\n  /* Konqueror */\n  background-image: -khtml-gradient(linear, left top, left bottom, from(#ffda44), to(#fabc2b));\n  /* Safari4+, Chrome */\n  background-image: -webkit-gradient(linear, left top, left bottom, from(#ffda44), to(#fabc2b));\n  /* Safari5.1+, Chrome10+ */\n  background-image: -webkit-linear-gradient(#ffda44, #fabc2b);\n  /* Firefox3.6 */\n  background-image: -moz-linear-gradient(#ffda44, #fabc2b);\n  /* Opera11.10+ */\n  background-image: -o-linear-gradient(#ffda44, #fabc2b);\n  /* CSS3 Compliant */\n  background-image: linear-gradient(#ffda44, #fabc2b);\n}\n\nbutton:hover,\n[role] a.button:hover,\ninput[type=\"button\"]:hover,\ninput[type=\"reset\"]:hover,\ninput[type=\"submit\"]:hover {\n  background-color: #ffe477;\n  /* Konqueror */\n  background-image: -khtml-gradient(linear, left top, left bottom, from(#ffe477), to(#fbcc5d));\n  /* Safari4+, Chrome */\n  background-image: -webkit-gradient(linear, left top, left bottom, from(#ffe477), to(#fbcc5d));\n  /* Safari5.1+, Chrome10+ */\n  background-image: -webkit-linear-gradient(#ffe477, #fbcc5d);\n  /* Firefox3.6 */\n  background-image: -moz-linear-gradient(#ffe477, #fbcc5d);\n  /* Opera11.10+ */\n  background-image: -o-linear-gradient(#ffe477, #fbcc5d);\n  /* CSS3 Compliant */\n  background-image: linear-gradient(#ffe477, #fbcc5d);\n}\n\nbutton:active,\n[role] a.button:active,\ninput[type=\"button\"]:active,\ninput[type=\"reset\"]:active,\ninput[type=\"submit\"]:active {\n  position: relative;\n  top: 1px;\n  color: #1a1a1a;\n  background-color: #fabc2b;\n  /* Konqueror */\n  background-image: -khtml-gradient(linear, left top, left bottom, from(#fabc2b), to(#ffda44));\n  /* Safari4+, Chrome */\n  background-image: -webkit-gradient(linear, left top, left bottom, from(#fabc2b), to(#ffda44));\n  /* Safari5.1+, Chrome10+ */\n  background-image: -webkit-linear-gradient(#fabc2b, #ffda44);\n  /* Firefox3.6 */\n  background-image: -moz-linear-gradient(#fabc2b, #ffda44);\n  /* Opera11.10+ */\n  background-image: -o-linear-gradient(#fabc2b, #ffda44);\n  /* CSS3 Compliant */\n  background-image: linear-gradient(#fabc2b, #ffda44);\n}\n\n#paginator {\n  margin-bottom: 2em;\n}\n\n#paginator .button {\n  padding: .25em 1em;\n}\n\n#paginator a.button {\n  text-decoration: none;\n  color: #333;\n}\n\n#paginator-l {\n  float: left;\n}\n\n#paginator-r {\n  float: right;\n}\n\n\n/* Comments\n   ========================================================================== */\n\n.comments {\n  margin-bottom: 1em;\n  -moz-border-radius: .35714285714286em;\n  border-radius: .35714285714286em;\n  padding: 1em 1em 1px;\n  background: #f7f7f7;\n  word-wrap: break-word;\n}\n\n.comments h4 .is-author {\n  font-weight: normal;\n}\n\n.comments h4 .comment-anchor {\n  float: right;\n  font-weight: normal;\n }\n\n.comments-author {\n  background: #efefef;\n}\n\n#cpreview {\n  margin-bottom: 2px;\n  -moz-border-radius: .35714285714286em;\n  border-radius: .35714285714286em;\n  padding: 1em;\n  background: #fff3d6;\n}\n\n.comments_error {\n  background: #fff4f4 !important;\n}\n\n.required,\n.error_message li {\n  color: #c00;\n}\n\n.required {\n	cursor: help;\n}\n\n\n/* Popup comments (can be removed if you don\'t use popups)\n   ========================================================================== */\n\n#popup-page .wrapper {\n  padding-top: 0;\n}\n\n#popup-page .container {\n  max-width: 52em;\n}\n\n}\n\n\n/* addresses select alignment in Safari/Chrome */\n@media screen and (-webkit-min-device-pixel-ratio: 0) {\n\nselect,\nselect[size=\"0\"],\nselect[size=\"1\"] {\n  height: 2.2em;\n}\n\nselect:not([size]),\nselect:not([multiple]) {\n  position: relative;\n  top: -1px;\n}\n\n}\n\n\n\n/* ==========================================================================\n   Additional layout for screen media 490px and up\n   ========================================================================== */\n\n\n@media only screen and (min-width: 35em) {\n\nnav ul {\n  width: 93.75%;\n}\n\nnav li {\n  float: left;\n  border-right: solid 1px #e1a61a;\n  border-bottom: 0;\n}\n\nnav li:first-child {\n  border-left: solid 1px #e1a61a;\n}\n\nnav a {\n  padding: .5em 1em;\n}\n\n}\n\n\n\n/* ==========================================================================\n   Additional layout for screen media 672px and up\n   ========================================================================== */\n\n\n@media only screen and (min-width: 48em) {\n\n[role=\"main\"] {\n  float: left;\n  /* 592px / 960px */\n  width: 61.666666666667%;\n}\n\n[role=\"complementary\"] {\n  float: right;\n  border: 1px solid #e3e3e3;\n  -moz-border-radius: .35714285714286em;\n  border-radius: .35714285714286em;\n  padding: 1em 1em 0;\n  /* 290px / 960px */\n  width: 30.208333333333%;\n  background: #f7f7f7;\n}\n\nh1 {\n  /* 42px */\n  font-size: 3em;\n}\n\nh2 {\n  /* 28px */\n  font-size: 2em;\n}\n\nh3 {\n  /* 21px */\n  font-size: 1.5em;\n}\n\nblockquote {\n  float: right;\n  margin: 0 0 .875em .875em;\n  /* 254px / 592px */\n  width: 42.905405405405%;\n}\n\n}\n\n\n\n/* ==========================================================================\n   Additional layout for screen media 1280px and up\n   ========================================================================== */\n\n\n@media only screen and (min-width: 80em) {\n\nbody {\n  /* 16px */\n  font-size: 100%;\n}\n\nheader,\nnav ul,\n.container,\nfooter {\n  /* 1152px / 1280px */\n  width: 90%;\n}\n\n}\n\n\n\n/* ==========================================================================\n   Additional layout for screen media 1800px and up\n   ========================================================================== */\n\n\n@media only screen and (min-width: 112.5em) {\n\nbody {\n  /* 18px */\n  font-size: 112.5%;\n}\n\n}\n\n\n\n/* ==========================================================================\n   Fix for reponsive embedded content in IE8\n   ========================================================================== */\n\n\n@media \\\\0screen {\n\nimg,\nvideo {\n  width: auto;\n}\n\n}\n\n\n\n/* ==========================================================================\n   Styling and layout for print media\n   ========================================================================== */\n\n\n@media print {\n\n* {\n  /* black prints faster - http://sanbeiji.com/archives/953 */\n  color: black !important;\n  text-shadow: none !important;\n  background: transparent !important;\n  -webkit-box-shadow: none !important;\n  -moz-box-shadow: none !important;\n  box-shadow: none !important;\n}\n\nbody {\n  font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;\n  font-size: 8pt;\n  line-height: 1.5;\n  margin: .5cm;\n  padding: 2em 5em;\n}\n\nheader {\n  border-bottom: solid 1pt black;\n}\n\nfooter {\n  margin-top: 12pt;\n  border-top: solid 1pt black;\n}\n\n/* hide unnecessary content from print */\nnav,\naudio,\nvideo,\nform,\n[role=\"complementary\"],\n#paginator,\n#comments-form,\n.comments h4 a:last-child {\n  display: none;\n}\n\na {\n  text-decoration: none;\n}\n\n/* show URLs for certain links in print */\na[href]:after {\n  content: \" (\" attr(href) \")\";\n}\n\nh1 a[href]:after,\nh2 a[href]:after,\nh3 a[href]:after,\nsup a[href]:after,\na[itemprop=\"discussionUrl\"]:after,\na[rel=\"tag\"]:after {\n  content: \"\";\n}\n\n/* show long-form for abbreviations in print */\nabbr[title]:after {\n  content: \" (\" attr(title) \")\";\n}\n\nh1 {\n  font-size: 32pt;\n  line-height: 36pt;\n  font-weight: normal;\n  margin: .5em 0;\n}\n\nh2 {\n  font-size: 18pt;\n  line-height: 23pt;\n  page-break-after: avoid;\n  orphans: 3;\n  widows: 3;\n  margin: .66666666666667em 0;\n}\n\nh3 {\n  font-size: 12pt;\n  line-height: 17pt;\n  page-break-after: avoid;\n  orphans: 3;\n  widows: 3;\n  margin: .66666666666667em 0;\n}\n\np {\n  orphans: 3;\n  widows: 3;\n}\n\nfooter,\nfigcaption,\ntfoot,\nsmall,\n.footnote {\n  font-size: 6pt;\n}\n\nblockquote {\n  border: solid 1pt black;\n  padding: 0 8pt;\n  page-break-inside: avoid;\n}\n\npre {\n  margin-bottom: 8pt;\n  border: solid 1pt black;\n  padding: 8pt;\n}\n\n.comments {\n  page-break-inside: avoid;\n}\n\npre,\ncode,\nkbd,\nsamp,\nvar {\n  font-family: \"Courier New\", Courier, monospace;\n}\n\ndfn,\nq,\ndt {\n  font-style: italic;\n}\n\nimg {\n  max-width: 100% !important;\n  page-break-inside: avoid;\n}\n\n/* image alignemnts */\nimg.align-left {\n  float: left;\n  margin: 1em 1em 1em 0;\n}\nimg.align-right {\n  float: right;\n  margin: 1em 0 1em 1em;\n}\nimg.align-center {\n  display: block;\n  margin: 1em auto;\n}\n\naudio {\n  display: none;\n}\n\nfigure {\n  margin-bottom: 8pt;\n}\n\nfigcaption {\n  margin-top: 4pt;\n}\n\nul {\n  list-style: square;\n  margin: 0 0 8pt 1.8em;\n}\n\nol {\n  list-style: decimal;\n  margin: 0 0 8pt 1.8em;\n}\n\ndl {\n  margin: 0 0 8pt 1.8em;\n}\n\ntable {\n  margin-bottom: 8pt;\n  width: 100%;\n}\n\ncaption {\n  font-weight: bold;\n  text-align: left;\n  margin-bottom: 4pt;\n}\n\n/* display table head across multi-page tables - http://css-discuss.incutio.com/wiki/Printing_Tables */\nthead {\n  display: table-header-group;\n}\n\nthead th {\n  border-top: solid 1pt black;\n}\n\ntr {\n  page-break-inside: avoid;\n}\n\nth,\ntd {\n  border-bottom: solid 1pt black;\n  padding: 4pt 8pt;\n}\n\n}\n')";
// /sql:txp_css

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

$create_sql[] = "INSERT INTO `".PFX."txp_discuss` VALUES (000001, 1, 'Donald Swain', 'donald.swain@example.com', 'example.com', '127.0.0.1', '2005-07-22 14:11:32', '<p>I enjoy your site very much.</p>', 1)";

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

// sql:txp_form
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('article_listing', 'article', '<txp:if_first_article><ul id=\"article-list\"></txp:if_first_article>\n  <li role=\"article\" itemscope itemtype=\"http://schema.org/Article\">\n    <h4 itemprop=\"name\"><a href=\"<txp:permlink />\" itemprop=\"url\"><txp:title /></a></h4>\n\n<!-- if the article has an excerpt, display that -->\n    <txp:if_excerpt>\n      <div itemprop=\"description\">\n        <txp:excerpt />\n      </div>\n    </txp:if_excerpt>\n\n    <p class=\"footnote\"><txp:text item=\"posted\" /> <time datetime=\"<txp:posted format=\'iso8601\' />\" itemprop=\"datePublished\"><txp:posted /></time>, <txp:text item=\"author\" /> <span itemprop=\"author\"><txp:author link=\"1\" this_section=\"1\" /></span></p>\n  </li>\n<txp:if_last_article></ul></txp:if_last_article>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('comments', 'comment', '<!-- load the comment email into a variable. you will be using below this along with author email variable loaded in form: default.article.txp\n  then check the comment email variable against article author email variable, and if it matches add \'comments-author\' class -->\n<txp:variable name=\"this_comment\" value=\'<txp:comment_email />\' />\n<txp:if_variable name=\"this_comment\" value=\'<txp:author_email />\'>\n  <article class=\"comments comments-author\" itemprop=\"comment\">\n<txp:else />\n  <article class=\"comments\" itemprop=\"comment\">\n</txp:if_variable>\n\n  <h4>\n\n  <span itemprop=\"creator\"><txp:comment_name /></span>\n\n  <!-- ...now check the comment email variable against article author email variable, and if it matches add \'(author)\' text -->\n  <txp:if_variable name=\"this_comment\" value=\'<txp:author_email />\'>\n    <span class=\"is-author\">(<txp:text item=\"author\" />)</span>\n  </txp:if_variable>\n\n  <!-- add a permlink so people can link direct to this comment -->\n    <span class=\"comment-anchor\" itemprop=\"url\"><txp:comment_permlink>#</txp:comment_permlink></span>\n\n  </h4>\n\n  <!-- also add a \'since\' to show comment freshness -->\n  <p class=\"footnote\"><time datetime=\"<txp:comment_time format=\'iso8601\' />\" itemprop=\"commentTime\"><txp:comment_time /> (<txp:comment_time format=\"since\" />)</time></p>\n\n  <div itemprop=\"commentText\">\n    <txp:comment_message />\n  </div>\n\n</article>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('comments_display', 'article', '<!-- added an id attribute to the section so we can link directly to here e.g. http://mysite.com/section/article#comments-head -->\n\n<section id=\"comments-head\">\n\n<h3><txp:text item=\"comments\" /></h3>\n\n<!-- if there are comments, display them (note: example code below overrides the global preference setting for comments wrapping by stating\n  attributes of wraptag=\"\" and break=\"\", you are instead using ol and li tags below)... -->\n<txp:if_comments>\n  <ol class=\"comments-list\" itemscope itemtype=\"http://schema.org/UserComments\">\n\n    <txp:comments wraptag=\"\" break=\"li\" /> <!-- links by default to form: \'comments.comment.txp\' unless you specify a different form -->\n\n  <!-- if this is a comment preview, display it (but only if there is no error) -->\n    <txp:if_comments_preview>\n      <li>\n        <p id=\"cpreview\"><txp:text item=\"press_preview_then_submit\" /></p>\n        <txp:comments_preview wraptag=\"\" /> <!-- links by default to form: \'comments.comment.txp\' unless you specify a different form -->\n      </li>\n    </txp:if_comments_preview>\n\n  </ol>\n\n<txp:else />\n\n<!-- else if there are no comments and if user is currently previewing comment,display it (but only if there is no error) -->\n  <txp:if_comments_preview>\n    <ol class=\"comments-list\" itemscope itemtype=\"http://schema.org/UserComments\">\n      <li>\n        <p id=\"cpreview\"><txp:text item=\"press_preview_then_submit\" /></p>\n        <txp:comments_preview wraptag=\"\" /> <!-- links by default to form: \'comments.comment.txp\' unless you specify a different form -->\n      </li>\n    </ol>\n\n  <txp:else />\n\n<!-- else just display that there are simply no comments whatsoever :( ...but only if comments are allowed -->\n    <txp:if_comments_allowed>\n      <p><txp:text item=\"no_comments\" /></p>\n    </txp:if_comments_allowed>\n\n  </txp:if_comments_preview>\n\n</txp:if_comments>\n\n<!-- if new comments are allowed for this article then display comment form, if not then display \'closed\' messages -->\n<txp:if_comments_allowed>\n  <section id=\"comments-form\">\n\n    <!-- comment invite text is taken for the article\'s comment invitation field on the \'write\' screen -->\n    <h3><txp:comments_invite showcount=\"0\" textonly=\"1\" showalways=\"1\" /></h3>\n\n    <txp:comments_form isize=\"32\" msgcols=\"64\" msgrows=\"4\" /> <!-- links by default to form: \'comment_form.comment.txp\' unless you specify a different form -->\n  </section>\n\n<txp:else />\n  \n  <!-- display either a comments expired message or a comments disabled message -->\n  <txp:if_comments>\n    <p><strong><txp:text item=\"comments_expired\" /></strong></p>\n  <txp:else />\n    <p><strong><txp:text item=\"comments_closed\" /></strong></p>\n  </txp:if_comments>\n</txp:if_comments_allowed>\n\n</section>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('comment_form', 'comment', '<p><txp:text item=\"enter_comment_here\" /></p>\n\n<!-- if there is an error, then inform user -->\n<txp:if_comments_error>\n  <txp:comments_error wraptag=\"ol\" break=\"li\" class=\"error_message\" />\n</txp:if_comments_error>\n\n<fieldset>\n\n  <p class=\"large\"><label for=\"name\"><txp:text item=\"comment_name\" /> <b class=\"required\" title=\"<txp:text item=\'required\' />\">*</b></label><br>\n  <txp:comment_name_input /></p>\n\n  <p class=\"large\"><label for=\"email\"><txp:text item=\"comment_email\" /> <b class=\"required\" title=\"<txp:text item=\'required\' />\">*</b></label><br>\n  <txp:comment_email_input /></p>\n\n  <p class=\"large\"><label for=\"web\"><txp:text item=\"comment_web\" /></label><br>\n  <txp:comment_web_input /></p>\n\n  <p><txp:comment_remember /></p>\n\n  <p class=\"small\"><label for=\"message\"><txp:text item=\"comment_message\" /> <b class=\"required\" title=\"<txp:text item=\'required\' />\">*</b></label><br>\n  <txp:comment_message_input /></p>\n\n</fieldset>\n\n<!-- preview and submit buttons (note: submit button will have a class of \'disabled\' applied until you have previewed the message at least once) -->\n<p><txp:comment_preview /> <txp:comment_submit /></p>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('default', 'article', '<article role=\"article\" itemscope itemtype=\"http://schema.org/Article\">\n\n<!-- if not an individual article then make the title h1 a link -->\n<txp:if_individual_article>\n  <h1 itemprop=\"name\"><txp:title /></h1>\n<txp:else />\n  <h1 itemprop=\"name\"><a href=\"<txp:permlink />\" itemprop=\"url\"><txp:title /></a></h1>\n</txp:if_individual_article>\n\n  <p><strong><txp:text item=\"posted\" /></strong> <time datetime=\"<txp:posted format=\'iso8601\' />\" itemprop=\"datePublished\"><txp:posted /></time><br>\n    <strong><txp:text item=\"comments\" /></strong> <a href=\"<txp:permlink />#comments-head\" title=\"<txp:text item=\'view\' />&#8230;\" itemprop=\"discussionUrl\" itemscope itemtype=\"http://schema.org/UserComments\">\n\n<!-- if comments then display the number, if no comments then print \'none\' -->\n<txp:if_comments>\n  <span itemprop=\"interactionCount\"><txp:comments_count /></span>\n<txp:else />\n  <span itemprop=\"interactionCount\"><txp:text item=\"none\" /></span>\n</txp:if_comments>\n\n  </a></p>\n\n  <div itemprop=\"articleBody\">\n    <txp:body />\n  </div>\n\n  <p><strong><txp:text item=\"author\" /></strong> <span itemprop=\"author\"><txp:author link=\"1\" this_section=\"1\" /></span>\n\n<!-- only display categories if they are actually set for an article, otherwise omit -->\n  <txp:if_article_category>\n    <br><strong><txp:text item=\"categories\" /></strong> <span itemprop=\"keywords\"><txp:category1 title=\"1\" link=\"1\" /><txp:if_article_category number=\"1\"><txp:if_article_category number=\"2\">, </txp:if_article_category></txp:if_article_category><txp:category2 title=\"1\" link=\"1\" /></span>\n  </txp:if_article_category>\n\n  </p>\n\n<!-- if this is an individual article then add the comments section via form: comments_display.article.txp -->\n<txp:if_individual_article>\n  <txp:article form=\"comments_display\" />\n</txp:if_individual_article>\n\n</article>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('files', 'file', '<!-- set up a variables to check whether a file also has a title, description, category associated with it... -->\n<txp:variable name=\"file_download_title\" value=\'<txp:file_download_name title=\"1\" />\' />\n<txp:variable name=\"file_download_description\" value=\'<txp:file_download_description />\' />\n<txp:variable name=\"file_download_category\" value=\'<txp:file_download_category />\' />\n\n<div itemscope itemtype=\"http://schema.org/userDownloads\">\n\n  <!-- ...if exists, use the file title, otherwise use file name -->\n  <txp:if_variable name=\"file_download_title\" value=\"\">\n    <strong itemprop=\"name\"><a href=\"<txp:file_download_link />\" title=\"<txp:file_download_name />\" itemprop=\"url\"><txp:file_download_name /></a></strong><br>\n  <txp:else />\n    <strong itemprop=\"name\"><a href=\"<txp:file_download_link />\" title=\"<txp:file_download_name title=\'1\' />\" itemprop=\"url\"><txp:file_download_name title=\"1\" /></a></strong><br>\n  </txp:if_variable>\n\n  <!-- ...if exists, use the file description, otherwise omit that line -->\n  <txp:if_variable name=\"file_download_description\" value=\"\">\n  <txp:else />\n  <span itemprop=\"description\"><txp:file_download_description /></span><br>\n  </txp:if_variable>\n\n  <span class=\"footnote\">\n\n  <!-- ...if exists, use the file category, otherwise omit that line -->\n  <txp:if_variable name=\"file_download_category\" value=\"\">\n  <txp:else />\n    <strong><txp:text item=\"category\" /></strong> <txp:file_download_category /> &#124; \n  </txp:if_variable>\n\n    <strong><txp:text item=\"author\" /></strong> <txp:file_download_author link=\"1\" /> &#124; \n    <strong><txp:text item=\"file_size\" /></strong> <txp:file_download_size /> &#124; \n    <strong><txp:text item=\"last_modified\" /></strong> <span itemprop=\"dateModified\"><txp:file_download_created /></span> &#124; \n    <strong><txp:text item=\"download_count\" /></strong> <span itemprop=\"interactionCount\"><txp:file_download_downloads /></span>\n\n  </span>\n\n</div>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('images', 'misc', '<!-- set up a variable to check whether a image also has a caption associated with it... -->\n<txp:variable name=\"caption\" value=\'<txp:image_info type=\"caption\" />\' />\n\n<!-- ...now use that image caption and wrap img inside a figure with figcaption tags, otherwise just use a plain img tag -->\n<txp:if_variable name=\"caption\" value=\"\">\n\n<!-- image - overriding the width and height to let the image scale to fit parent container -->\n  <p><txp:image width=\"0\" height=\"0\" /></p>\n\n<txp:else />\n\n  <figure itemscope itemtype=\"http://schema.org/ImageObject\">\n\n<!-- image - overriding the width and height to let the image scale to fit parent container -->\n    <txp:image width=\"0\" height=\"0\" />\n\n<!-- you do not need to specify the attribute type=\"caption\" as that is the default setting for <txp:image_info /> tag -->\n    <figcaption itemprop=\"caption\"><txp:image_info type=\"caption\" /></figcaption>\n\n  </figure>\n\n</txp:if_variable>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('plainlinks', 'link', '<!-- This is being used as an external links form, therefore rel is set to \'external\' -->\n<txp:linkdesctitle rel=\"external\" />')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('popup_comments', 'comment', '<!doctype html>\n<html lang=\"<txp:lang />\">\n\n<head>\n  <meta charset=\"utf-8\">\n\n  <title><txp:page_title /></title>\n  <meta name=\"generator\" content=\"Textpattern CMS\">\n  <meta name=\"robots\" content=\"noindex, follow, noodp, noydir\">\n\n<!-- mobile viewport optimised, more info: http://h5bp.com/viewport -->\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\n<!-- css -->\n  <!-- Google font API (remove this if you intend to use the theme in a project without internet access) -->\n  <link rel=\"stylesheet\" href=\"http://fonts.googleapis.com/css?family=PT+Serif:n4,i4,n7,i7|Cousine\">\n\n  <txp:css format=\"link\" media=\"\" />\n  <!-- or you can use (faster) external CSS files eg. <link rel=\"stylesheet\" href=\"<txp:site_url />css/default.css\"> -->\n\n<!-- HTML5/Media Queries support for IE < 9 (you can remove this section and the corresponding \'js\' directory files if you don\'t intend to support IE < 9) -->\n  <!--[if lt IE 9]>\n    <script src=\"<txp:site_url />js/html5shiv.js\"></script>\n    <script src=\"<txp:site_url />js/css3-mediaqueries.js\"></script>\n  <![endif]-->\n\n</head>\n\n<body id=\"popup-page\">\n\n  <div class=\"wrapper\">\n    <div class=\"container\">\n\n      <!-- this form is only used if you set \'Comments mode\' to \'popup\' format in preferences -->\n      <txp:popup_comments />\n\n    </div> <!-- /.container -->\n  </div> <!-- /.wrapper -->\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('search_input', 'misc', '<form role=\"search\" method=\"get\" action=\"<txp:site_url />\">\n  <h4><label for=\"search-textbox\"><txp:text item=\"search\" /></label></h4>\n  <p><input id=\"search-textbox\" type=\"search\" name=\"q\"<txp:if_search> value=\"<txp:search_term />\"</txp:if_search>><input type=\"submit\" value=\"<txp:text item=\'go\' />\"></p>\n</form>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('search_results', 'article', '<txp:if_search>\n\n<!-- count how many results return -->\n  <txp:article limit=\"10\" pgonly=\"1\" />\n\n  <txp:if_search_results>\n\n<!-- if search result count greater than 200 then display excessive results message, otherwise show search result count -->\n    <txp:if_search_results max=\"200\">\n      <h3><txp:search_result_count /> <txp:text item=\"matching_search_request\" /> &#8216;<txp:search_term />&#8217;&#8230;</h3>\n    <txp:else />\n      <h3><txp:text item=\"too_common_search_term\" /> &#8216;<txp:search_term />&#8217;</h3>\n    </txp:if_search_results>\n\n<!-- if no search results, then display no search results message -->\n  <txp:else />\n    <h3><txp:text item=\"no_search_matches\" /></h3>\n\n  </txp:if_search_results>\n\n<!-- display resulting articles (10 per page) -->\n  <txp:article limit=\"10\">\n\n    <txp:if_first_article><ul id=\"article-list\"></txp:if_first_article>\n      <li role=\"article\" itemscope itemtype=\"http://schema.org/Article\">\n        <h4 itemprop=\"name\"><a href=\"<txp:permlink />\" itemprop=\"url\"><txp:title /></a></h4>\n\n<!-- if the article has an excerpt, display that, otherwise show highlighted keywords in context of article -->\n        <txp:if_excerpt>\n          <div itemprop=\"description\">\n            <txp:excerpt />\n          </div>\n        <txp:else />\n          <p><txp:search_result_excerpt /></p>\n        </txp:if_excerpt>\n\n        <p class=\"footnote\"><txp:text item=\"posted\" /> <time datetime=\"<txp:posted format=\'iso8601\' />\" itemprop=\"datePublished\"><txp:posted /></time>, <txp:text item=\"author\" /> <span itemprop=\"author\"><txp:author link=\"1\" this_section=\"1\" /></span></p>\n      </li>\n    <txp:if_last_article></ul></txp:if_last_article>\n\n  </txp:article>\n\n<!-- check if there are further results and provide pagination links or disabled buttons depending on the result,\n  this method is more flexibile than using simple txp:older/txp:newer tags -->\n  <txp:if_search_results min=\"11\">\n\n    <p id=\"paginator\">\n\n    <txp:variable name=\"prev\" value=\'<txp:older />\' />\n    <txp:variable name=\"next\" value=\'<txp:newer />\' />\n\n    <txp:if_variable name=\"next\" value=\"\">\n      <span id=\"paginator-l\" class=\"button disabled\">&#8592; <txp:text item=\"prev\" /></span>\n    <txp:else />\n      <a id=\"paginator-l\" href=\"<txp:newer />\" title=\"&#8592; <txp:text item=\"prev\" />\" class=\"button\">&#8592; <txp:text item=\"prev\" /></a>\n    </txp:if_variable>\n    <txp:if_variable name=\"prev\" value=\"\">\n      <span id=\"paginator-r\" class=\"button disabled\"><txp:text item=\"next\" /> &#8594;</span>\n    <txp:else />\n      <a id=\"paginator-r\" href=\"<txp:older />\" title=\"<txp:text item=\"next\" /> &#8594;\" class=\"button\"><txp:text item=\"next\" /> &#8594;</a>\n    </txp:if_variable>\n\n    </p>\n\n  </txp:if_search_results>\n\n</txp:if_search>')";
// /sql:txp_form

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
) $tabletype PACK_KEYS=0";

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

$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (1, '2005-07-20 12:54:26', 'textpattern', 'http://textpattern.com/', 'Textpattern', '10', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (2, '2005-07-20 12:54:41', 'textpattern', 'http://textpattern.net/', 'User Documentation', '20', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (3, '2005-07-20 12:55:04', 'textpattern', 'http://textpattern.org/', 'Txp Resources', '30', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (4, '2012-06-01 08:15:42', 'textpattern', 'http://textpattern.com/@textpattern', '@textpattern', '40', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (5, '2012-06-01 08:15:42', 'textpattern', 'http://textpattern.com/+', '+Textpattern CMS', '50', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (6, '2012-06-01 08:15:42', 'textpattern', 'http://textpattern.com/facebook', 'Textpattern Facebook Group ', '60', '')";

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

// sql:txp_page
$create_sql[] = "INSERT INTO `".PFX."txp_page`(`name`,`user_html`) VALUES('archive', '<!doctype html>\n<html lang=\"<txp:lang />\">\n\n<head>\n  <meta charset=\"utf-8\">\n\n<!-- page title and metas, no meta keywords as they are now obsolete -->\n  <title><txp:page_title /></title>\n  <meta name=\"description\" content=\"\">\n  <meta name=\"generator\" content=\"Textpattern CMS\">\n  <meta name=\"robots\" content=\"noindex, follow, noodp, noydir\">\n\n<!-- mobile viewport optimised, more info: http://h5bp.com/viewport -->\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\n  <txp:if_individual_article>\n<!-- add meta author for individual articles -->\n    <txp:meta_author title=\"1\" />\n  </txp:if_individual_article>\n\n<!-- content feeds -->\n  <txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n  <txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n  <txp:rsd />\n\n<!-- specify canonical, more info: http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html -->\n  <txp:if_individual_article>\n    <link rel=\"canonical\" href=\"<txp:permlink />\">\n  <txp:else />\n    <link rel=\"canonical\" href=\"<txp:section url=\'1\' />\">\n  </txp:if_individual_article>\n\n<!-- css -->\n  <!-- Google font API (remove this if you intend to use the theme in a project without internet access) -->\n  <link rel=\"stylesheet\" href=\"http://fonts.googleapis.com/css?family=PT+Serif:n4,i4,n7,i7|Cousine\">\n\n  <txp:css format=\"link\" media=\"\" />\n  <!-- or you can use (faster) external CSS files eg. <link rel=\"stylesheet\" href=\"<txp:site_url />css/default.css\"> -->\n\n<!-- HTML5/Media Queries support for IE < 9 (you can remove this section and the corresponding \'js\' directory files if you don\'t intend to support IE < 9) -->\n  <!--[if lt IE 9]>\n    <script src=\"<txp:site_url />js/html5shiv.js\"></script>\n    <script src=\"<txp:site_url />js/css3-mediaqueries.js\"></script>\n  <![endif]-->\n\n</head>\n\n<body id=\"<txp:section />-page\">\n\n<!-- header -->\n  <header role=\"banner\">\n    <hgroup>\n      <h1><txp:link_to_home><txp:site_name /></txp:link_to_home></h1>\n      <h3><txp:site_slogan /></h3>\n    </hgroup>\n  </header>\n\n<!-- navigation -->\n  <nav role=\"navigation\">\n    <h1><txp:text item=\"navigation\" /></h1>\n    <txp:section_list default_title=\'<txp:text item=\"home\" />\' include_default=\"1\" wraptag=\"ul\" break=\"\">\n      <li<txp:if_section name=\'<txp:section />\'> class=\"active\"</txp:if_section>>\n        <txp:section title=\"1\" link=\"1\" />\n      </li>\n    </txp:section_list>\n  </nav>\n\n  <div class=\"wrapper\">\n    <div class=\"container\">\n\n<!-- left (main) column -->\n      <div role=\"main\">\n\n        <txp:if_article_list><h1><txp:section title=\"1\" /></h1></txp:if_article_list>\n\n        <txp:article listform=\"article_listing\" limit=\"5\" />\n        <!-- or if you want to list all articles from all sections instead, then replace txp:article with txp:article_custom -->\n\n        <!-- add pagination links to foot of article/article listings if there are more articles available,\n          this method is more flexibile than using simple txp:link_to_prev/txp:link_to_next or txp:older/txp:newer tags -->\n        <p id=\"paginator\">\n\n        <txp:if_individual_article>\n\n          <txp:variable name=\"prev\" value=\'<txp:link_to_prev />\' />\n          <txp:variable name=\"next\" value=\'<txp:link_to_next />\' />\n\n          <txp:if_variable name=\"prev\" value=\"\">\n            <span id=\"paginator-l\" class=\"button disabled\">&#8592; <txp:text item=\"older\" /></span>\n          <txp:else />\n            <a id=\"paginator-l\" href=\"<txp:link_to_prev />\" title=\"<txp:prev_title />\" class=\"button\">&#8592; <txp:text item=\"older\" /></a>\n          </txp:if_variable>\n          <txp:if_variable name=\"next\" value=\"\">\n            <span id=\"paginator-r\" class=\"button disabled\"><txp:text item=\"newer\" /> &#8594;</span>\n          <txp:else />\n            <a id=\"paginator-r\" href=\"<txp:link_to_next />\" title=\"<txp:next_title />\" class=\"button\"><txp:text item=\"newer\" /> &#8594;</a>\n          </txp:if_variable>\n\n        <txp:else />\n\n          <txp:variable name=\"prev\" value=\'<txp:older />\' />\n          <txp:variable name=\"next\" value=\'<txp:newer />\' />\n          <txp:if_variable name=\"prev\" value=\"\">\n            <span id=\"paginator-l\" class=\"button disabled\">&#8592; <txp:text item=\"older\" /></span>\n          <txp:else />\n            <a id=\"paginator-l\" href=\"<txp:older />\" title=\"<txp:text item=\'older\' />\" class=\"button\">&#8592; <txp:text item=\"older\" /></a>\n          </txp:if_variable>\n          <txp:if_variable name=\"next\" value=\"\">\n            <span id=\"paginator-r\" class=\"button disabled\"><txp:text item=\"newer\" /> &#8594;</span>\n          <txp:else />\n            <a id=\"paginator-r\" href=\"<txp:newer />\" title=\"<txp:text item=\'newer\' />\" class=\"button\"><txp:text item=\"newer\" /> &#8594;</a>\n          </txp:if_variable>\n\n        </txp:if_individual_article>\n\n        </p>\n\n      </div> <!-- /main -->\n\n  <!-- right (complementary) column -->\n      <div role=\"complementary\">\n        <txp:search_input /> <!-- links by default to form: \'search_input.misc.txp\' unless you specify a different form -->\n  \n        <!-- Feed links, default flavor is rss, so we don\'t need to specify a flavor on the first feed_link -->\n        <p><txp:feed_link label=\"RSS\" class=\"feed-rss\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" class=\"feed-atom\" /></p>\n\n        <h4><txp:text item=\"external_links\" /></h4>\n        <txp:linklist wraptag=\"ul\" break=\"li\" limit=\"10\" /> <!-- links by default to form: \'plainlinks.link.txp\' unless you specify a different form -->\n      </div> <!-- /complementary -->\n\n    </div> <!-- /.container -->\n  </div> <!-- /.wrapper -->\n\n<!-- footer -->\n  <footer role=\"contentinfo\">\n    <p><small><txp:text item=\"published_with\" /> <a href=\"http://textpattern.com\" rel=\"external\" title=\"<txp:text item=\'go_txp_com\' />\">Textpattern CMS</a>.</small></p>\n  </footer>\n\n<!-- JavaScript at the bottom for fast page loading, more info: http://developer.yahoo.com/performance/rules.html#js_bottom -->\n\n  <!-- add your own JavaScript here -->\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_page`(`name`,`user_html`) VALUES('default', '<!doctype html>\n<html lang=\"<txp:lang />\">\n\n<head>\n  <meta charset=\"utf-8\">\n\n<!-- page title and metas, no meta keywords as they are now obsolete -->\n  <title><txp:page_title /></title>\n  <meta name=\"description\" content=\"\">\n  <meta name=\"generator\" content=\"Textpattern CMS\">\n  <txp:if_search>\n    <meta name=\"robots\" content=\"none\">\n  <txp:else />\n  <txp:if_category>\n    <meta name=\"robots\" content=\"noindex, follow, noodp, noydir\">\n  <txp:else />\n  <txp:if_author>\n    <meta name=\"robots\" content=\"noindex, follow, noodp, noydir\">\n  <txp:else />\n    <meta name=\"robots\" content=\"index, follow, noodp, noydir\">\n  </txp:if_author>\n  </txp:if_category>\n  </txp:if_search>\n\n<!-- mobile viewport optimised, more info: http://h5bp.com/viewport -->\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\n<!-- content feeds -->\n  <txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n  <txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n  <txp:rsd />\n\n<!-- specify canonical, more info: http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html -->\n  <txp:if_section name=\"\">\n    <link rel=\"canonical\" href=\"<txp:site_url />\">\n  <txp:else />\n    <txp:if_individual_article>\n      <link rel=\"canonical\" href=\"<txp:permlink />\">\n    <txp:else />\n      <link rel=\"canonical\" href=\"<txp:section url=\'1\' />\">\n    </txp:if_individual_article>\n  </txp:if_section>\n\n<!-- css -->\n  <!-- Google font API (remove this if you intend to use the theme in a project without internet access) -->\n  <link rel=\"stylesheet\" href=\"http://fonts.googleapis.com/css?family=PT+Serif:n4,i4,n7,i7|Cousine\">\n\n  <txp:css format=\"link\" media=\"\" />\n  <!-- or you can use (faster) external CSS files eg. <link rel=\"stylesheet\" href=\"<txp:site_url />css/default.css\"> -->\n\n<!-- HTML5/Media Queries support for IE < 9 (you can remove this section and the corresponding \'js\' directory files if you don\'t intend to support IE < 9) -->\n  <!--[if lt IE 9]>\n    <script src=\"<txp:site_url />js/html5shiv.js\"></script>\n    <script src=\"<txp:site_url />js/css3-mediaqueries.js\"></script>\n  <![endif]-->\n\n</head>\n\n<body id=\"<txp:if_section name=\"\"><txp:if_search>search<txp:else />front</txp:if_search><txp:else /><txp:section /></txp:if_section>-page\">\n\n<!-- header -->\n  <header role=\"banner\">\n    <hgroup>\n      <h1><txp:link_to_home><txp:site_name /></txp:link_to_home></h1>\n      <h3><txp:site_slogan /></h3>\n    </hgroup>\n  </header>\n\n<!-- navigation -->\n  <nav role=\"navigation\">\n    <h1><txp:text item=\"navigation\" /></h1>\n    <txp:section_list default_title=\'<txp:text item=\"home\" />\' include_default=\"1\" wraptag=\"ul\" break=\"\">\n      <li<txp:if_section name=\'<txp:section />\'><txp:if_search><txp:else /><txp:if_category><txp:else /><txp:if_author><txp:else /> class=\"active\"</txp:if_author></txp:if_category></txp:if_search></txp:if_section>>\n        <txp:section title=\"1\" link=\"1\" />\n      </li>\n    </txp:section_list>\n  </nav>\n\n  <div class=\"wrapper\">\n    <div class=\"container\">\n\n<!-- left (main) column -->\n      <div role=\"main\">\n\n      <!-- is this result result page? also omits the pagination links below (uses pagination format within search_results.article.txp instead) -->\n      <txp:if_search>\n\n        <h1><txp:text item=\"search_results\" /></h1>\n        <txp:output_form form=\"search_results\"/>\n\n      <txp:else />\n\n        <!-- else is this an article category list? -->\n        <txp:if_category>\n\n          <h1><txp:text item=\"category\" /> <txp:category title=\"1\" /></h1>\n          <txp:article form=\"article_listing\" limit=\"5\" />\n\n        <txp:else />\n\n          <!-- else is this an article author list? -->\n          <txp:if_author>\n\n          <h1><txp:text item=\"author\" /> <txp:author /></h1>\n          <txp:article form=\"article_listing\" limit=\"5\" />\n\n          <txp:else />\n\n            <!-- else display articles normally -->\n            <txp:article limit=\"5\" /> <!-- links by default to form: \'default.article.txp\' unless you specify a different form -->\n\n          </txp:if_author>\n        </txp:if_category>\n\n        <!-- add pagination links to foot of article/article listings/category listings if there are more articles available,\n          this method is more flexibile than using simple txp:link_to_prev/txp:link_to_next or txp:older/txp:newer tags -->\n        <p id=\"paginator\">\n\n        <txp:variable name=\"prev\" value=\'<txp:older />\' />\n        <txp:variable name=\"next\" value=\'<txp:newer />\' />\n\n        <txp:if_variable name=\"prev\" value=\"\">\n          <span id=\"paginator-l\" class=\"button disabled\">&#8592; <txp:text item=\"older\" /></span>\n        <txp:else />\n          <a id=\"paginator-l\" href=\"<txp:older />\" title=\"<txp:text item=\'older\' />\" class=\"button\">&#8592; <txp:text item=\"older\" /></a>\n        </txp:if_variable>\n        <txp:if_variable name=\"next\" value=\"\">\n          <span id=\"paginator-r\" class=\"button disabled\"><txp:text item=\"newer\" /> &#8594;</span>\n        <txp:else />\n            <a id=\"paginator-r\" href=\"<txp:newer />\" title=\"<txp:text item=\'newer\' />\" class=\"button\"><txp:text item=\"newer\" /> &#8594;</a>\n        </txp:if_variable>\n\n        </p>\n\n      </txp:if_search>\n\n      </div> <!-- /main -->\n\n<!-- right (complementary) column -->\n      <div role=\"complementary\">\n        <txp:search_input /> <!-- links by default to form: \'search_input.misc.txp\' unless you specify a different form -->\n\n        <!-- Feed links, default flavor is rss, so we don\'t need to specify a flavor on the first feed_link -->\n        <p><txp:feed_link label=\"RSS\" class=\"feed-rss\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" class=\"feed-atom\" /></p>\n\n        <h4><txp:text item=\"external_links\" /></h4>\n        <txp:linklist wraptag=\"ul\" break=\"li\" limit=\"10\" /> <!-- links by default to form: \'plainlinks.link.txp\' unless you specify a different form -->\n      </div> <!-- /complementary -->\n\n    </div> <!-- /.container -->\n  </div> <!-- /.wrapper -->\n\n<!-- footer -->\n  <footer role=\"contentinfo\">\n    <p><small><txp:text item=\"published_with\" /> <a href=\"http://textpattern.com\" rel=\"external\" title=\"<txp:text item=\'go_txp_com\' />\">Textpattern CMS</a>.</small></p>\n  </footer>\n\n<!-- JavaScript at the bottom for fast page loading, more info: http://developer.yahoo.com/performance/rules.html#js_bottom -->\n\n  <!-- add your own JavaScript here -->\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_page`(`name`,`user_html`) VALUES('error_default', '<!doctype html>\n<html lang=\"<txp:lang />\">\n\n<head>\n  <meta charset=\"utf-8\">\n\n<!-- page title and metas, no meta keywords as they are now obsolete -->\n  <title><txp:error_status /></title>\n  <meta name=\"description\" content=\"<txp:error_message />\">\n  <meta name=\"generator\" content=\"Textpattern CMS\">\n  <meta name=\"robots\" content=\"none\">\n\n<!-- mobile viewport optimised, more info: http://h5bp.com/viewport -->\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\n<!-- content feeds -->\n  <txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n  <txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n  <txp:rsd />\n\n<!-- css -->\n  <!-- Google font API (remove this if you intend to use the theme in a project without internet access) -->\n  <link rel=\"stylesheet\" href=\"http://fonts.googleapis.com/css?family=PT+Serif:n4,i4,n7,i7|Cousine\">\n\n  <txp:css format=\"link\" media=\"\" />\n  <!-- or you can use (faster) external CSS files eg. <link rel=\"stylesheet\" href=\"<txp:site_url />css/default.css\"> -->\n\n<!-- HTML5/Media Queries support for IE < 9 (you can remove this section and the corresponding \'js\' directory files if you don\'t intend to support IE < 9) -->\n  <!--[if lt IE 9]>\n    <script src=\"<txp:site_url />js/html5shiv.js\"></script>\n    <script src=\"<txp:site_url />js/css3-mediaqueries.js\"></script>\n  <![endif]-->\n\n</head>\n\n<body id=\"error-page\">\n\n<!-- header -->\n  <header role=\"banner\">\n    <hgroup>\n      <h1><txp:link_to_home><txp:site_name /></txp:link_to_home></h1>\n      <h3><txp:site_slogan /></h3>\n    </hgroup>\n  </header>\n\n<!-- navigation -->\n  <nav role=\"navigation\">\n    <h1><txp:text item=\"navigation\" /></h1>\n    <txp:section_list default_title=\'<txp:text item=\"home\" />\' include_default=\"1\" wraptag=\"ul\" break=\"li\">\n      <txp:section title=\"1\" link=\"1\" />\n    </txp:section_list>\n  </nav>\n\n  <div class=\"wrapper\">\n    <div class=\"container\">\n\n<!-- left (main) column -->\n      <div role=\"main\">\n        <h1 class=\"error-status\"><txp:error_status /></h1>\n        <p class=\"error-msg\"><txp:error_message /></p>\n      </div> <!-- /main -->\n\n<!-- right (complementary) column -->\n      <div role=\"complementary\">\n        <txp:search_input /> <!-- links by default to form: \'search_input.misc.txp\' unless you specify a different form -->\n\n        <!-- Feed links, default flavor is rss, so we don\'t need to specify a flavor on the first feed_link -->\n        <p><txp:feed_link label=\"RSS\" class=\"feed-rss\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" class=\"feed-atom\" /></p>\n\n        <h4><txp:text item=\"external_links\" /></h4>\n        <txp:linklist wraptag=\"ul\" break=\"li\" limit=\"10\" /> <!-- links by default to form: \'plainlinks.link.txp\' unless you specify a different form -->\n      </div> <!-- /complementary -->\n\n    </div> <!-- /.container -->\n  </div> <!-- /.wrapper -->\n\n<!-- footer -->\n  <footer role=\"contentinfo\">\n    <p><small><txp:text item=\"published_with\" /> <a href=\"http://textpattern.com\" rel=\"external\" title=\"<txp:text item=\'go_txp_com\' />\">Textpattern CMS</a>.</small></p>\n  </footer>\n\n<!-- JavaScript at the bottom for fast page loading, more info: http://developer.yahoo.com/performance/rules.html#js_bottom -->\n\n  <!-- add your own JavaScript here -->\n\n</body>\n</html>')";
// /sql:txp_page

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
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'comments_auto_append', '0', 0, 'comments', 'yesnoradio', 211, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'dbupdatetime', '1122194504', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'version', '1.0rc4', 2, 'publish', 'text_input', 0, '')";
$create_sql[] = "INSERT INTO `".PFX."txp_prefs` VALUES (1, 'doctype', 'html5', 0, 'publish', 'doctypes', 190, '')";

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

/**
 * Stub replacement for txplib_db.php/safe_escape()
 */
function safe_escape($in='')
{
	return mysql_real_escape_string($in);
}
?>

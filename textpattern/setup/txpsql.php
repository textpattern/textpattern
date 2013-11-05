<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2013 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('TXP_INSTALL'))
{
	exit;
}

@ignore_user_abort(1);
@set_time_limit(0);

mysql_connect($dhost, $duser, $dpass, false, $dclient_flags);
mysql_select_db($ddb);

$result = mysql_query("describe `".PFX."textpattern`");
if ($result)
{
	die("Textpattern database table already exists. Can't run setup.");
}

$version = mysql_get_server_info();

// Use "ENGINE" if version of MySQL > (4.0.18 or 4.1.2).
$tabletype = (intval($version[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#', $version))
	? " ENGINE=MyISAM "
	: " TYPE=MyISAM ";

// On 4.1 or greater use UTF-8 tables.
if (isset($dbcharset) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#', $version)))
{
	$tabletype .= " CHARACTER SET = $dbcharset ";
	if ($dbcharset == 'utf8')
	{
		$tabletype .= " COLLATE utf8_general_ci ";
	}
	mysql_query("SET NAMES ".$dbcharset);
}

// Default to messy URLs if we know clean ones won't work.
$permlink_mode = 'section_id_title';
if (is_callable('apache_get_modules'))
{
	$modules = @apache_get_modules();
	if (!is_array($modules) || !in_array('mod_rewrite', $modules))
	{
		$permlink_mode = 'messy';
	}
}
else
{
	$server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST'])
		? ((@$_SERVER['SERVER_SOFTWARE']) ? @$_SERVER['SERVER_SOFTWARE'] : $_SERVER['HTTP_HOST'])
		: '';
	if (!stristr($server_software, 'Apache'))
	{
		$permlink_mode = 'messy';
	}
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

$setup_comment_invite = doSlash((gTxt('setup_comment_invite')=='setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite'));
$create_sql[] = "INSERT INTO `".PFX."textpattern` VALUES (1, now(), '".doSlash($name)."', now(), '', 'Welcome to your site', '', 'h3. What do you want to do next?\n\n* Modify or even delete this article? The \"article list\":siteurl/textpattern/index.php?event=list is the place to start.\n* Change this site\'s name, or modify the style of the URLs? It\'s all up to your \"preferences\":siteurl/textpattern/index.php?event=prefs.\n* Get yourself acquainted with Textile, the humane web text generator which comes with Textpattern? The basics are \"simple\":http://textpattern.com/textile-sandbox. If you want to learn more about Textile, you can dig into an \"extensive manual\":http://textpattern.com/textile-reference-manual later.\n* Be guided through your \"Textpattern first steps\":http://textpattern.com/textpattern-first-steps by completing some basic tasks?\n* Study the \"Textpattern Semantic Model?\":http://textpattern.com/textpattern-semantic-model\n* Add \"another user\":siteurl/textpattern/index.php?event=admin, or extend the capabilities with \"third party plugins\":siteurl/textpattern/index.php?event=plugin you discovered from the central plugin directory at \"Textpattern Resources\":http://textpattern.org/?\n* Dive in and learn by trial and error? Then please note:\n** When you \"write\":siteurl/textpattern/index.php?event=article an article you assign it to a section of your site.\n** Sections use a \"page template\":siteurl/textpattern/index.php?event=page and a \"style\":siteurl/textpattern/index.php?event=css as an output scaffold.\n** Page templates use HTML and Textpattern tags (like this: @<txp:article />@) to build the markup.\n** Some Textpattern tags use \"forms\":siteurl/textpattern/index.php?event=form, which are building blocks for reusable snippets of code and markup you may build and use at your discretion.\n\nThere are a host of \"Frequently Asked Questions\":http://textpattern.com/faq/ to help you get started.\n\n\"Textpattern tags\":http://textpattern.com/textpattern-tag-reference, their attributes and values are explained and sampled within the \"Textpattern User Documentation\":http://textpattern.net/, where you will also find valuable tips and tutorials.\n\nIf all else fails, there\'s a whole crowd of friendly, helpful people over at the \"Textpattern support forum\":http://forum.textpattern.com/. Come and pay a visit!\n', '\t<h3>What do you want to do next?</h3>\n\n\t<ul>\n\t\t<li>Modify or even delete this article? The <a href=\"siteurl/textpattern/index.php?event=list\">article list</a> is the place to start.</li>\n\t\t<li>Change this site&#8217;s name, or modify the style of the <span class=\"caps\">URL</span>s? It&#8217;s all up to your <a href=\"siteurl/textpattern/index.php?event=prefs\">preferences</a>.</li>\n\t\t<li>Get yourself acquainted with Textile, the humane web text generator which comes with Textpattern? The basics are <a href=\"http://textpattern.com/textile-sandbox\">simple</a>. If you want to learn more about Textile, you can dig into an <a href=\"http://textpattern.com/textile-reference-manual\">extensive manual</a> later.</li>\n\t\t<li>Be guided through your <a href=\"http://textpattern.com/textpattern-first-steps\">Textpattern first steps</a> by completing some basic tasks?</li>\n\t\t<li>Study the <a href=\"http://textpattern.com/textpattern-semantic-model\">Textpattern Semantic Model?</a></li>\n\t\t<li>Add <a href=\"siteurl/textpattern/index.php?event=admin\">another user</a>, or extend the capabilities with <a href=\"siteurl/textpattern/index.php?event=plugin\">third party plugins</a> you discovered from the central plugin directory at <a href=\"http://textpattern.org/\">Textpattern Resources</a>?</li>\n\t\t<li>Dive in and learn by trial and error? Then please note:\n\t<ul>\n\t\t<li>When you <a href=\"siteurl/textpattern/index.php?event=article\">write</a> an article you assign it to a section of your site.</li>\n\t\t<li>Sections use a <a href=\"siteurl/textpattern/index.php?event=page\">page template</a> and a <a href=\"siteurl/textpattern/index.php?event=css\">style</a> as an output scaffold.</li>\n\t\t<li>Page templates use <span class=\"caps\">HTML</span> and Textpattern tags (like this: <code>&lt;txp:article /&gt;</code>) to build the markup.</li>\n\t\t<li>Some Textpattern tags use <a href=\"siteurl/textpattern/index.php?event=form\">forms</a>, which are building blocks for reusable snippets of code and markup you may build and use at your discretion.</li>\n\t</ul></li>\n\t</ul>\n\n\t<p>There are a host of <a href=\"http://textpattern.com/faq/\">Frequently Asked Questions</a> to help you get started.</p>\n\n\t<p><a href=\"http://textpattern.com/textpattern-tag-reference\">Textpattern tags</a>, their attributes and values are explained and sampled within the <a href=\"http://textpattern.net/\">Textpattern User Documentation</a>, where you will also find valuable tips and tutorials.</p>\n\n\t<p>If all else fails, there&#8217;s a whole crowd of friendly, helpful people over at the <a href=\"http://forum.textpattern.com/\">Textpattern support forum</a>. Come and pay a visit!</p>', '', '', '', 'hope-for-the-future', 'meaningful-labor', 1, '".$setup_comment_invite."', 1, 4, 1, 1, 'articles', '', '', 'welcome-to-your-site', '', '', '', '', '', '', '', '', '', '', '".md5(uniqid(rand(), true))."', now())";


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
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (5, 'hope-for-the-future', 'article', 'root', 2, 3, 'Hope for the future')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (6, 'meaningful-labor', 'article', 'root', 4, 5, 'Meaningful labor')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (7, 'reciprocal-affection', 'article', 'root', 6, 7, 'Reciprocal affection')";
$create_sql[] = "INSERT INTO `".PFX."txp_category` VALUES (8, 'textpattern', 'link', 'root', 2, 3, 'Textpattern')";


$create_sql[] = "CREATE TABLE `".PFX."txp_css` (
  `name` varchar(255) NOT NULL,
  `css` text NOT NULL,
  UNIQUE KEY `name` (`name`)
) $tabletype ";

// sql:txp_css
$create_sql[] = "INSERT INTO `".PFX."txp_css`(`name`,`css`) VALUES('default', '/* ==========================================================================\n   Styling and layout for all media\n   ========================================================================== */\n/* correct block display not defined in IE8-9 */\narticle,\naside,\ndetails,\nfigcaption,\nfigure,\nfooter,\nheader,\nmain,\nnav,\nsection,\nsummary {\n  display: block;\n}\n\n/* correct `<summary>` not showing as clickable */\nsummary {\n  cursor: pointer;\n}\n\n/* address `[hidden]` styling not present in IE8-9.\n   hide the `template` element in IE, Safari, and Firefox < 22 */\n[hidden],\ntemplate {\n  display: none;\n}\n\n/* Clearfix\n   ========================================================================== */\n/* http://nicolasgallagher.com/micro-clearfix-hack/ */\nheader:after,\nfooter:after,\nnav ul:after,\nnav ol:after,\n.clearfix:after,\n.container:after,\n.paginator:after {\n  content: \"\";\n  display: table;\n  clear: both;\n}\n\n/* ==========================================================================\n   Styling and layout for screen media (mobile first)\n   ========================================================================== */\n@media screen {\n  /* Layout\n     ========================================================================== */\n  html {\n    -webkit-tap-highlight-color: rgba(0, 102, 255, 0.5);\n    /* always force scrollbar padding so we don\'t get \'jumping\' */\n    overflow-y: scroll;\n    /* prevent iOS text size adjust after orientation change, without disabling user zoom */\n    -webkit-text-size-adjust: 100%;\n    /* as above, for Windows Phone */\n    -ms-text-size-adjust: 100%;\n  }\n\n  body {\n    margin: 0;\n    background: #f7f7f7;\n  }\n\n  .wrapper {\n    border-top: 1px solid #cccccc;\n    border-bottom: 1px solid #cccccc;\n    padding: 1px 0 1em;\n    background: white;\n  }\n\n  header,\n  footer,\n  .container {\n    margin: 0 auto;\n    /* 960px / 1024px */\n    width: 93.75%;\n    max-width: 86em;\n  }\n\n  header {\n    padding: 1em 0;\n  }\n  header h1 {\n    margin: 0;\n  }\n  header h3 {\n    /* 16px margin top */\n    margin: 0.66667em 0 0;\n  }\n\n  .site-navigation {\n    background-color: #dddddd;\n  }\n  .site-navigation ul {\n    margin: 0 auto;\n    padding: 0;\n    max-width: 86em;\n    list-style: none;\n  }\n  .site-navigation li {\n    -webkit-transition: box-shadow 0.2s linear;\n    -moz-transition: box-shadow 0.2s linear;\n    -o-transition: box-shadow 0.2s linear;\n    transition: box-shadow 0.2s linear;\n    border-top: 1px solid #cccccc;\n    background-color: #eeeeee;\n  }\n  .site-navigation li:hover {\n    background-color: #f8f8f8;\n  }\n  .site-navigation li:active {\n    -webkit-box-shadow: inset 0 0.2em 0.25em rgba(0, 0, 0, 0.15);\n    -moz-box-shadow: inset 0 0.2em 0.25em rgba(0, 0, 0, 0.15);\n    box-shadow: inset 0 0.2em 0.25em rgba(0, 0, 0, 0.15);\n    background-color: #e4e4e4;\n    background-image: -webkit-linear-gradient(#d3d3d3, #e4e4e4);\n    background-image: -moz-linear-gradient(#d3d3d3, #e4e4e4);\n    background-image: -o-linear-gradient(#d3d3d3, #e4e4e4);\n    background-image: linear-gradient(#d3d3d3, #e4e4e4);\n  }\n  .site-navigation li.active {\n    background-color: white;\n  }\n  .site-navigation a {\n    display: block;\n    padding: 0.5em 3.125%;\n    color: #333333;\n  }\n  .site-navigation a:hover, .site-navigation a:visited {\n    color: #333333;\n    text-decoration: none;\n  }\n  .site-navigation a:active {\n    color: #1a1a1a;\n    text-decoration: none;\n  }\n\n  [role=\"article\"] {\n    margin-bottom: 2em;\n  }\n\n  [role=\"complementary\"] {\n    margin: 2em 0;\n    border-top: 2px dashed #cccccc;\n    padding-top: 1em;\n  }\n\n  [role=\"search\"] p {\n    margin-top: 0;\n  }\n\n  footer {\n    padding: 0.5em 0;\n  }\n\n  /* address differences between Firefox and other browsers */\n  hr {\n    -webkit-box-sizing: content-box;\n    -moz-box-sizing: content-box;\n    box-sizing: content-box;\n    border: 0;\n    border-bottom: 1px solid #cccccc;\n    height: 0;\n  }\n\n  /* Links\n     ========================================================================== */\n  a {\n    text-decoration: none;\n    color: #114eb1;\n    background: transparent;\n  }\n  a:hover, a:active {\n    /* improve readability when focused and also mouse hovered in all browsers */\n    outline: 0;\n    text-decoration: underline;\n    color: #0066ff;\n  }\n  a:focus {\n    outline: thin dotted #0066ff;\n  }\n  a:visited {\n    color: #183082;\n  }\n  header a {\n    -webkit-border-radius: 0.125em;\n    -moz-border-radius: 0.125em;\n    border-radius: 0.125em;\n    color: #333333;\n  }\n  header a:hover, header a:active {\n    background: #e8e8e8;\n  }\n\n  h1 a {\n    -webkit-border-radius: 0.125em;\n    -moz-border-radius: 0.125em;\n    border-radius: 0.125em;\n    color: #333333;\n  }\n  h1 a:visited {\n    color: #333333;\n  }\n  h1 a:hover, h1 a:active {\n    text-decoration: none;\n    color: #333333;\n    background: #ededed;\n  }\n\n  .paginator {\n    margin-bottom: 2em;\n  }\n  .paginator [rel=\"prev\"] {\n    float: left;\n  }\n  .paginator [rel=\"next\"] {\n    float: right;\n  }\n\n  /* Typography\n     ========================================================================== */\n  html {\n    font-size: 100%;\n    line-height: 1.5em;\n  }\n\n  body {\n    font-family: \"PT Serif\", Georgia, serif;\n    color: #333333;\n  }\n\n  .site-navigation {\n    font-family: Arial, Helvetica, sans-serif;\n    font-weight: bold;\n  }\n\n  h1,\n  h2,\n  h3,\n  h4,\n  h5,\n  h6 {\n    clear: both;\n    font-family: Arial, Helvetica, sans-serif;\n  }\n\n  h1 {\n    font-size: 2.5em;\n    line-height: 1.2em;\n    margin: 0.6em 0;\n  }\n\n  h2 {\n    font-size: 2em;\n    line-height: 1.25em;\n    margin: 0.75em 0;\n  }\n\n  h3 {\n    font-size: 1.5em;\n    line-height: 1.3333333em;\n    margin: 1em 0;\n  }\n\n  h4 {\n    font-size: 1.25em;\n    line-height: 1.4em;\n    margin: 1.2em 0;\n  }\n\n  /* address style set to `bolder` in Firefox4+, Safari5, and Chrome */\n  b,\n  strong {\n    font-weight: bold;\n  }\n\n  address {\n    margin: 1em 0;\n  }\n\n  blockquote {\n    font-size: 1.125em;\n    line-height: 1.44em;\n    font-style: italic;\n    /* 16px / 18px */\n    margin: 0.88889em 0;\n    border-left: 3px solid #cccccc;\n    padding: 0 0 0 0.88889em;\n  }\n\n  /* sets consistent quote types */\n  q {\n    quotes: \"\\\\201C\" \"\\\\201D\" \"\\\\2018\" \"\\\\2019\";\n  }\n\n  /* address styling not present in Safari5 and Chrome */\n  dfn {\n    font-style: italic;\n  }\n\n  /* address styling not present in IE8-9, Safari5, Chrome */\n  abbr[title],\n  dfn[title] {\n    border-bottom: 1px dotted;\n    cursor: help;\n  }\n\n  mark,\n  var {\n    -webkit-border-radius: 0.25em;\n    -moz-border-radius: 0.25em;\n    border-radius: 0.25em;\n    padding: 0 0.25em;\n    color: #333333;\n    background: #e8f0fd;\n  }\n\n  pre,\n  code,\n  kbd,\n  samp {\n    font-family: Cousine, Consolas, \"Lucida Console\", Monaco, monospace;\n  }\n\n  code,\n  kbd,\n  samp {\n    font-size: 0.875em;\n    line-height: 1.5em;\n    -webkit-border-radius: 0.2857143em;\n    -moz-border-radius: 0.2857143em;\n    border-radius: 0.2857143em;\n    border: 1px solid #e3e3e3;\n    /* 2px / 14px + 3px / 14px */\n    padding: 0.07143em 0.21429em;\n    background: #f7f7f7;\n  }\n\n  pre {\n    font-size: 0.875em;\n    line-height: 1.5em;\n    -webkit-border-radius: 0.5714286em;\n    -moz-border-radius: 0.5714286em;\n    border-radius: 0.5714286em;\n    overflow-x: auto;\n    border: 1px solid #e3e3e3;\n    padding: 1em;\n    background: #f7f7f7;\n    /* set tab size to 4 spaces */\n    tab-size: 4;\n  }\n  pre code {\n    font-size: 1em;\n    border: 0;\n    padding: 0;\n    background: none;\n  }\n\n  /* prevent `<sub>` and `<sup>` affecting line height in all browsers */\n  sub,\n  sup {\n    font-size: 0.75em;\n    line-height: 0em;\n    position: relative;\n    vertical-align: baseline;\n  }\n\n  sup {\n    top: -0.5em;\n  }\n\n  sub {\n    bottom: -0.25em;\n  }\n\n  small,\n  figcaption,\n  tfoot,\n  .footnote {\n    font-size: 0.75em;\n    line-height: 1.5em;\n  }\n\n  figcaption,\n  tfoot,\n  .footnote {\n    color: #888888;\n  }\n\n  figcaption {\n    margin-top: 0.5em;\n    font-style: italic;\n  }\n\n  /* Support for non-latin languages (can be removed if not required)\n     ========================================================================== */\n  html[lang=\"ja-jp\"] {\n    font-family: \"Hiragino Kaku Gothic Pro\", Meiryo, sans-serif;\n  }\n\n  html[lang=\"ko-kr\"] {\n    font-family: GulimChe, Gulim, sans-serif;\n  }\n\n  html[lang=\"zh-cn\"] {\n    font-family: SimHei, sans-serif;\n  }\n\n  html[lang=\"zh-tw\"] {\n    font-family: PMingLiU, sans-serif;\n  }\n\n  /* Embedded content\n     ========================================================================== */\n  /* remove the gap between images, videos, audio and canvas and the bottom of their containers */\n  audio,\n  canvas,\n  img,\n  video {\n    vertical-align: middle;\n  }\n\n  /* correct `inline-block` display not defined in IE8-9 */\n  audio,\n  canvas,\n  video {\n    display: inline-block;\n  }\n\n  /* make embedded elements responsive */\n  img,\n  embed,\n  object,\n  video {\n    max-width: 100%;\n    height: auto;\n  }\n\n  img {\n    /* remove border when inside `<a>` element in IE8-9 */\n    border: 0;\n  }\n  img.align-left {\n    float: left;\n    margin: 1em 1em 1em 0;\n  }\n  img.align-right {\n    float: right;\n    margin: 1em 0 1em 1em;\n  }\n  img.align-center {\n    display: block;\n    margin: 1em auto;\n  }\n\n  /* address margin not present in IE8-9 and Safari5 */\n  figure {\n    margin: 0;\n  }\n\n  /* prevent modern browsers from displaying `<audio>` without controls, remove excess height in iOS5 devices */\n  audio:not([controls]) {\n    display: none;\n    height: 0;\n  }\n\n  /* correct overflow displayed oddly in IE9 */\n  svg:not(:root) {\n    overflow: hidden;\n  }\n\n  /* Tables\n     ========================================================================== */\n  /* consistent tables */\n  table {\n    margin-bottom: 1em;\n    border-collapse: collapse;\n    border-spacing: 0;\n    width: 100%;\n  }\n\n  caption {\n    font-style: italic;\n    text-align: left;\n    margin-bottom: 0.5em;\n  }\n\n  th,\n  td {\n    /* make table cells align top and left by default */\n    vertical-align: top;\n    text-align: left;\n    border-bottom: 1px solid #cccccc;\n    padding: 0.25em 0em 0.25em 0.5em;\n  }\n  th:first-child,\n  td:first-child {\n    padding-left: 0;\n  }\n\n  thead tr:last-child th,\n  thead tr:last-child td {\n    border-bottom: 2px solid #cccccc;\n  }\n\n  tfoot th,\n  tfoot td {\n    padding: 0.325em 0em 0.325em 0.61538em;\n  }\n  tfoot:first-child {\n    padding-left: 0;\n  }\n  tfoot tr:last-child th,\n  tfoot tr:last-child td {\n    border-bottom: 0;\n  }\n\n  /* vertical cell alignment */\n  [rowspan] {\n    vertical-align: middle;\n  }\n\n  /* Lists\n     ========================================================================== */\n  /* address paddings set differently */\n  menu,\n  ol,\n  ul {\n    padding: 0 0 0 2em;\n  }\n\n  /* remove margins from nested lists */\n  li > ul,\n  li > ol {\n    margin: 0;\n  }\n\n  dd {\n    margin: 0 0 0 2em;\n  }\n\n  dt {\n    font-style: italic;\n  }\n\n  .article-list {\n    list-style: none;\n    margin: 0 0 2em 0;\n    border-top: 1px solid #cccccc;\n    padding: 0;\n  }\n  .article-list li {\n    margin-bottom: 0;\n    border-bottom: 1px solid #cccccc;\n  }\n\n  /* Forms\n     ========================================================================== */\n  /* zero out fieldsets */\n  fieldset {\n    margin: 0;\n    border: 0;\n    padding: 0;\n  }\n\n  /* zero out legends */\n  legend {\n    border: 0;\n    padding: 0;\n  }\n\n  button,\n  input,\n  select,\n  textarea {\n    /* correct font size not being inherited in all browsers */\n    font-size: 100%;\n    font-size: 0.875em;\n    line-height: 1.5em;\n    /* address margins set differently in Firefox4+, Safari5+, and Chrome */\n    margin: 0;\n    font-family: Arial, Helvetica, sans-serif;\n    vertical-align: baseline;\n  }\n\n  /* colour placeholder text (WebKit and Mozilla only, so far) */\n  input::-webkit-input-placeholder,\n  textarea::-webkit-input-placeholder {\n    color: #888888;\n  }\n\n  input:-moz-placeholder,\n  textarea:-moz-placeholder {\n    color: #888888;\n  }\n\n  /* remove inner padding and border in Firefox 4+ */\n  button::-moz-focus-inner,\n  input::-moz-focus-inner {\n    border: 0;\n    padding: 0;\n  }\n\n  /* remove inner padding and search cancel button in Safari5+ and Chrome on OS X */\n  input[type=\"search\"]::-webkit-search-cancel-button,\n  input[type=\"search\"]::-webkit-search-decoration {\n    -webkit-appearance: none;\n  }\n\n  input[type=\"color\"],\n  input[type=\"date\"],\n  input[type=\"datetime\"],\n  input[type=\"datetime-local\"],\n  input[type=\"email\"],\n  input[type=\"month\"],\n  input[type=\"number\"],\n  input[type=\"password\"],\n  input[type=\"search\"],\n  input[type=\"tel\"],\n  input[type=\"text\"],\n  input[type=\"time\"],\n  input[type=\"url\"],\n  input[type=\"week\"],\n  select,\n  textarea {\n    -webkit-border-radius: 0;\n    -moz-border-radius: 0;\n    border-radius: 0;\n    -webkit-box-sizing: border-box;\n    -moz-box-sizing: border-box;\n    box-sizing: border-box;\n    -webkit-transition: box-shadow 0.2s linear;\n    -moz-transition: box-shadow 0.2s linear;\n    -o-transition: box-shadow 0.2s linear;\n    transition: box-shadow 0.2s linear;\n    /* remove iOS Safari default styling */\n    -webkit-appearance: none;\n    border: 1px solid #cccccc;\n    padding: 0.3571428em;\n    height: 2.3571428em;\n    background: #ffffff;\n    text-align: left;\n  }\n  input[type=\"color\"]:focus,\n  input[type=\"date\"]:focus,\n  input[type=\"datetime\"]:focus,\n  input[type=\"datetime-local\"]:focus,\n  input[type=\"email\"]:focus,\n  input[type=\"month\"]:focus,\n  input[type=\"number\"]:focus,\n  input[type=\"password\"]:focus,\n  input[type=\"search\"]:focus,\n  input[type=\"tel\"]:focus,\n  input[type=\"text\"]:focus,\n  input[type=\"time\"]:focus,\n  input[type=\"url\"]:focus,\n  input[type=\"week\"]:focus,\n  select:focus,\n  textarea:focus {\n    /* better form focus glows */\n    -webkit-box-shadow: 0 0 7px #0066ff;\n    -moz-box-shadow: 0 0 7px #0066ff;\n    box-shadow: 0 0 7px #0066ff;\n    outline: 0;\n  }\n\n  input[type=\"color\"] {\n    padding: 0;\n  }\n\n  /* address box sizing set to `content-box` and excess padding in IE8-9 */\n  input[type=\"checkbox\"],\n  input[type=\"radio\"] {\n    padding: 0;\n    box-sizing: border-box;\n  }\n\n  [role=\"complementary\"] input[type=\"search\"] {\n    margin-right: 2px;\n    width: 66%;\n    display: inline-block;\n  }\n\n  /* address inconsistent `text-transform` inheritance for `select` in Firefox 4+ */\n  select {\n    text-transform: none;\n  }\n\n  select[size],\n  select[multiple] {\n    height: auto;\n  }\n\n  select[size=\"0\"],\n  select[size=\"1\"] {\n    height: 2.3571428em;\n  }\n\n  /* address `<select>` alignment in Safari/Chrome */\n  optgroup {\n    font-family: Arial, Helvetica, sans-serif;\n    font-style: normal;\n    font-weight: normal;\n    color: #333333;\n  }\n  optgroup::-moz-focus-inner {\n    border: 0;\n    padding: 0;\n  }\n\n  textarea {\n    height: auto;\n    min-height: 3em;\n    /* remove default vertical scrollbar in IE8-9 */\n    overflow: auto;\n    resize: vertical;\n    width: 100%;\n  }\n\n  /* make sure disable elements really are disabled */\n  button[disabled],\n  html input[disabled],\n  input[type=\"button\"][disabled],\n  input[type=\"reset\"][disabled],\n  input[type=\"submit\"][disabled],\n  select[disabled],\n  select[disabled] option,\n  select[disabled] optgroup,\n  textarea[disabled],\n  span.disabled {\n    -webkit-box-shadow: none !important;\n    -moz-box-shadow: none !important;\n    box-shadow: none !important;\n    opacity: 1;\n    border-color: #e3e3e3 !important;\n    color: #aaaaaa !important;\n    background: #eeeeee !important;\n    text-shadow: none !important;\n    cursor: default !important;\n  }\n\n  .large input {\n    width: 50%;\n    min-width: 302px;\n  }\n  .large textarea {\n    height: 156px;\n  }\n\n  .small input {\n    width: 25%;\n    min-width: 151px;\n  }\n  .small textarea {\n    height: 5.5em;\n  }\n\n  /* Buttons\n     ========================================================================== */\n  /* address inconsistent `text-transform` inheritance for `button` in Chrome, Safari5+, and IE6+ */\n  button {\n    text-transform: none;\n  }\n\n  button,\n  input[type=\"button\"],\n  input[type=\"reset\"],\n  input[type=\"submit\"] {\n    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);\n    -webkit-border-radius: 0.5em;\n    -moz-border-radius: 0.5em;\n    border-radius: 0.5em;\n    -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);\n    -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);\n    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);\n    -webkit-background-clip: padding;\n    -moz-background-clip: padding;\n    background-clip: padding-box;\n    -webkit-box-sizing: border-box;\n    -moz-box-sizing: border-box;\n    box-sizing: border-box;\n    background-color: #dddddd;\n    background-image: -webkit-linear-gradient(#eeeeee, #dddddd);\n    background-image: -moz-linear-gradient(#eeeeee, #dddddd);\n    background-image: -o-linear-gradient(#eeeeee, #dddddd);\n    background-image: linear-gradient(#eeeeee, #dddddd);\n    -webkit-transition: box-shadow 0.2s linear;\n    -moz-transition: box-shadow 0.2s linear;\n    -o-transition: box-shadow 0.2s linear;\n    transition: box-shadow 0.2s linear;\n    /* remove iOS Safari default styling */\n    -webkit-appearance: none;\n    display: inline-block;\n    border: 1px solid #cccccc;\n    padding: .3571428em .7142857em;\n    width: auto;\n    height: 2.3571428em;\n    overflow: visible;\n    font-weight: normal;\n    text-align: center;\n    color: #333333;\n    cursor: pointer;\n  }\n  button:hover,\n  input[type=\"button\"]:hover,\n  input[type=\"reset\"]:hover,\n  input[type=\"submit\"]:hover {\n    background-color: #e8e8e8;\n    background-image: -webkit-linear-gradient(#f8f8f8, #e8e8e8);\n    background-image: -moz-linear-gradient(#f8f8f8, #e8e8e8);\n    background-image: -o-linear-gradient(#f8f8f8, #e8e8e8);\n    background-image: linear-gradient(#f8f8f8, #e8e8e8);\n    border-color: #aaaaaa;\n    text-decoration: none;\n  }\n  button:active,\n  input[type=\"button\"]:active,\n  input[type=\"reset\"]:active,\n  input[type=\"submit\"]:active {\n    -webkit-box-shadow: inset 0 0.2em 0.25em rgba(0, 0, 0, 0.15);\n    -moz-box-shadow: inset 0 0.2em 0.25em rgba(0, 0, 0, 0.15);\n    box-shadow: inset 0 0.2em 0.25em rgba(0, 0, 0, 0.15);\n    background-color: #e4e4e4;\n    background-image: -webkit-linear-gradient(#d3d3d3, #e4e4e4);\n    background-image: -moz-linear-gradient(#d3d3d3, #e4e4e4);\n    background-image: -o-linear-gradient(#d3d3d3, #e4e4e4);\n    background-image: linear-gradient(#d3d3d3, #e4e4e4);\n    border-color: #aaaaaa;\n    color: #1a1a1a;\n  }\n  button:focus,\n  input[type=\"button\"]:focus,\n  input[type=\"reset\"]:focus,\n  input[type=\"submit\"]:focus {\n    /* better button focus glows */\n    -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 0 7px #0066ff;\n    -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 0 7px #0066ff;\n    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 0 7px #0066ff;\n    outline: 0;\n  }\n\n  /* Comments\n     ========================================================================== */\n  .comments {\n    -webkit-border-radius: 0.5em;\n    -moz-border-radius: 0.5em;\n    border-radius: 0.5em;\n    margin-bottom: 1em;\n    padding: 1px 1em;\n    background: #f7f7f7;\n    word-wrap: break-word;\n  }\n  .comments h4 .is-author {\n    font-weight: normal;\n  }\n  .comments h4 .comment-anchor {\n    float: right;\n    font-weight: normal;\n  }\n\n  .comments-author {\n    background: #efefef;\n  }\n\n  #cpreview {\n    -webkit-border-radius: 0.5em;\n    -moz-border-radius: 0.5em;\n    border-radius: 0.5em;\n    margin-bottom: 2px;\n    padding: 1em;\n    background: #fff3d6;\n  }\n\n  .comments_error {\n    background: #f2dede !important;\n  }\n\n  .required,\n  .error_message li {\n    color: #9d261d;\n  }\n\n  .required {\n    cursor: help;\n  }\n\n  /* Popup comments (can be removed if you don\'t use popups)\n     ========================================================================== */\n  #popup-page .container {\n    max-width: 52em;\n  }\n}\n@media screen and (-webkit-min-device-pixel-ratio: 0) {\n  select[size],\n  select[multiple],\n  select[multiple][size] {\n    background-image: none;\n    padding-right: .5em;\n  }\n\n  select,\n  select[size=\"0\"],\n  select[size=\"1\"] {\n    background: white url(\"data:image/svg+xml,<svg version=\'1.1\' baseProfile=\'full\' xmlns=\'http://www.w3.org/2000/svg\' xmlns:xlink=\'http://www.w3.org/1999/xlink\' x=\'0px\' y=\'0px\' width=\'21px\' height=\'7px\' viewBox=\'0 0 21 7\' enable-background=\'new 0 0 21 7\' xml:space=\'preserve\'><polygon fill=\'#333333\' points=\'2,0 7,7 12,0\'/></svg>\") no-repeat right center;\n    padding-right: 2.25em;\n    -webkit-background-size: 1.5em .5em;\n    background-size: 1.5em .5em;\n  }\n}\n/* ==========================================================================\n   Additional layout for screen media 576px and up\n   ========================================================================== */\n@media only screen and (min-width: 36em) {\n  .site-navigation {\n    border-top: 1px solid #cccccc;\n  }\n  .site-navigation ul {\n    width: 93.75%;\n  }\n  .site-navigation li {\n    float: left;\n    border-top: 0;\n    border-right: 1px solid #cccccc;\n  }\n  .site-navigation li:first-child {\n    border-left: 1px solid #cccccc;\n  }\n  .site-navigation a {\n    padding: 0.5em 1em;\n  }\n}\n/* ==========================================================================\n   Additional layout for screen media 864px and up\n   ========================================================================== */\n@media only screen and (min-width: 54em) {\n  main {\n    float: left;\n    width: 62.5%;\n  }\n\n  [role=\"complementary\"] {\n    -webkit-border-radius: 0.5em;\n    -moz-border-radius: 0.5em;\n    border-radius: 0.5em;\n    float: right;\n    border: 1px solid #e3e3e3;\n    padding: 0 1em;\n    width: 30%;\n    background: #f7f7f7;\n  }\n}\n/* ==========================================================================\n   Additional layout for screen media 1280px and up\n   ========================================================================== */\n@media only screen and (min-width: 82em) {\n  header,\n  footer,\n  .container {\n    width: 93.75%;\n  }\n}\n/* ==========================================================================\n   Styling and layout for print media\n   ========================================================================== */\n@media print {\n  * {\n    -webkit-box-shadow: none !important;\n    -moz-box-shadow: none !important;\n    box-shadow: none !important;\n    /* black prints faster */\n    color: black !important;\n    text-shadow: none !important;\n    background: transparent !important;\n  }\n\n  body {\n    font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;\n    font-size: 8pt;\n    line-height: 1.5;\n    margin: 0.5cm;\n    padding: 2em 5em;\n  }\n\n  header {\n    border-bottom: 1pt solid black;\n  }\n\n  footer {\n    margin-top: 12pt;\n    border-top: 1pt solid black;\n  }\n\n  /* hide unnecessary content from print */\n  nav,\n  audio,\n  video,\n  form,\n  [role=\"complementary\"],\n  #comments-form,\n  .comments h4 a:last-child,\n  .paginator {\n    display: none;\n  }\n\n  a {\n    text-decoration: none;\n  }\n\n  /* show URLs for certain links in print */\n  a[href]:after {\n    content: \" (\" attr(href) \")\";\n  }\n\n  h1 a[href]:after,\n  h2 a[href]:after,\n  h3 a[href]:after,\n  sup a[href]:after,\n  a[itemprop=\"discussionUrl\"]:after,\n  a[rel=\"tag\"]:after {\n    content: \"\";\n  }\n\n  /* show long-form for abbreviations in print */\n  abbr[title]:after {\n    content: \" (\" attr(title) \")\";\n  }\n\n  h1 {\n    font-size: 32pt;\n    line-height: 36pt;\n    font-weight: normal;\n    margin: 0.5em 0;\n  }\n\n  h2 {\n    font-size: 18pt;\n    line-height: 23pt;\n    page-break-after: avoid;\n    orphans: 3;\n    widows: 3;\n    margin: 0.66667em 0;\n  }\n\n  h3 {\n    font-size: 12pt;\n    line-height: 17pt;\n    page-break-after: avoid;\n    orphans: 3;\n    widows: 3;\n    margin: 0.75em 0;\n  }\n\n  h4 {\n    font-size: 10pt;\n    line-height: 14pt;\n    page-break-after: avoid;\n    orphans: 3;\n    widows: 3;\n    margin: 0.875em 0;\n  }\n\n  p {\n    orphans: 3;\n    widows: 3;\n  }\n\n  footer,\n  figcaption,\n  tfoot,\n  small,\n  .footnote {\n    font-size: 6pt;\n  }\n\n  blockquote {\n    font-size: 16pt;\n    border-left: 3pt solid black;\n    padding: 0 0 0 8pt;\n    page-break-inside: avoid;\n  }\n\n  pre {\n    margin-bottom: 8pt;\n    border: 1pt solid black;\n    padding: 8pt;\n  }\n\n  .comments {\n    page-break-inside: avoid;\n  }\n\n  pre,\n  code,\n  kbd,\n  samp,\n  var {\n    font-family: \"Courier New\", Courier, monospace;\n  }\n\n  dfn,\n  q,\n  dt {\n    font-style: italic;\n  }\n\n  img {\n    max-width: 100% !important;\n    page-break-inside: avoid;\n  }\n\n  img.align-left {\n    float: left;\n    margin: 1em 1em 1em 0;\n  }\n  img.align-right {\n    float: right;\n    margin: 1em 0 1em 1em;\n  }\n  img.align-center {\n    display: block;\n    margin: 1em auto;\n  }\n\n  figure {\n    margin-bottom: 8pt;\n  }\n\n  figcaption {\n    margin-top: 4pt;\n  }\n\n  ul {\n    list-style: square;\n    margin: 0 0 8pt 1.8em;\n  }\n\n  ol {\n    list-style: decimal;\n    margin: 0 0 8pt 1.8em;\n  }\n\n  dl {\n    margin: 0 0 8pt 1.8em;\n  }\n\n  table {\n    margin-bottom: 8pt;\n    width: 100%;\n  }\n\n  caption {\n    font-weight: bold;\n    text-align: left;\n    margin-bottom: 4pt;\n  }\n\n  /* display table head across multi-page tables */\n  thead {\n    display: table-header-group;\n  }\n  thead th {\n    border-top: 1pt solid black;\n  }\n\n  tr {\n    page-break-inside: avoid;\n  }\n\n  th,\n  td {\n    border-bottom: 1pt solid black;\n    padding: 4pt 8pt;\n  }\n}\n')";
$create_sql[] = "INSERT INTO `".PFX."txp_css`(`name`,`css`) VALUES('ie8', '/* ==========================================================================\n   Desktop version layout for IE8 due to lack of media queries support\n   ========================================================================== */\nheader,\nfooter,\n.container,\n.site-navigation ul {\n  width: 93.75%;\n}\n\n.site-navigation {\n  border-top: 1px solid #cccccc;\n}\n.site-navigation li {\n  float: left;\n  border-top: 0;\n  border-right: 1px solid #cccccc;\n}\n.site-navigation li:first-child {\n  border-left: 1px solid #cccccc;\n}\n.site-navigation a {\n  padding: 0.5em 1em;\n}\n\nmain {\n  float: left;\n  width: 62.5%;\n}\n\n[role=\"complementary\"] {\n  float: right;\n  border: 1px solid #e3e3e3;\n  padding: 0 1em;\n  width: 30%;\n  background: #f7f7f7;\n}\n\n/* Fix for reponsive embedded content in IE8\n   ========================================================================== */\nimg,\nvideo {\n  width: auto;\n}\n')";
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

$create_sql[] = "INSERT INTO `".PFX."txp_discuss` VALUES (000001, 1, 'Donald Swain', 'donald.swain@example.com', 'example.com', '127.0.0.1', now() + interval 1 hour, '<p>I enjoy your site very much.</p>', 1)";

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
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('article_listing', 'article', '<li role=\"article\" itemscope itemtype=\"http://schema.org/Article\">\n    <h4 itemprop=\"name\"><a href=\"<txp:permlink />\" itemprop=\"url\"><txp:title /></a></h4>\n\n<!-- if the article has an excerpt, display that -->\n    <txp:if_excerpt>\n        <div itemprop=\"description\">\n            <txp:excerpt />\n        </div>\n    </txp:if_excerpt>\n\n    <p class=\"footnote\"><txp:text item=\"posted\" /> <time datetime=\"<txp:posted format=\"iso8601\" />\" itemprop=\"datePublished\"><txp:posted /></time>, <txp:text item=\"author\" /> <span itemprop=\"author\"><txp:author link=\"1\" this_section=\"1\" /></span></p>\n</li>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('comments', 'comment', '<!-- load the comment email into a variable. you will be using below this along with author email variable loaded in form: default.article.txp\n    then check the comment email variable against article author email variable, and if it matches add \'comments-author\' class -->\n<txp:variable name=\"this_comment\" value=\'<txp:comment_email />\' />\n<txp:if_variable name=\"this_comment\" value=\'<txp:author_email />\'>\n    <article class=\"comments comments-author\" itemprop=\"comment\">\n<txp:else />\n    <article class=\"comments\" itemprop=\"comment\">\n</txp:if_variable>\n\n    <h4>\n\n    <span itemprop=\"creator\"><txp:comment_name /></span>\n\n    <!-- ...now check the comment email variable against article author email variable, and if it matches add \'(author)\' text -->\n    <txp:if_variable name=\"this_comment\" value=\'<txp:author_email />\'>\n        <span class=\"is-author\">(<txp:text item=\"author\" />)</span>\n    </txp:if_variable>\n\n    <!-- add a permlink so people can link direct to this comment -->\n        <span class=\"comment-anchor\" itemprop=\"url\"><txp:comment_permlink>#</txp:comment_permlink></span>\n\n    </h4>\n\n    <!-- also add a \'since\' to show comment freshness -->\n    <p class=\"footnote\"><time datetime=\"<txp:comment_time format=\"iso8601\" />\" itemprop=\"commentTime\"><txp:comment_time /> (<txp:comment_time format=\"since\" />)</time></p>\n\n    <div itemprop=\"commentText\">\n        <txp:comment_message />\n    </div>\n\n</article>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('comments_display', 'comment', '<!-- added an id attribute to the section so we can link directly to here e.g. http://mysite.com/section/article#comments-head -->\n\n<section id=\"comments-head\">\n\n<h3><txp:text item=\"comments\" /></h3>\n\n<!-- if there are comments, display them (note: example code below overrides the global preference setting for comments wrapping by stating\n    attributes of wraptag=\"\" and break=\"\", you are instead using ol and li tags below)... -->\n<txp:if_comments>\n    <ol class=\"comments-list\" itemscope itemtype=\"http://schema.org/UserComments\">\n\n        <txp:comments wraptag=\"\" break=\"li\" /> <!-- links by default to form: \'comments.comment.txp\' unless you specify a different form -->\n\n    <!-- if this is a comment preview, display it (but only if there is no error) -->\n        <txp:if_comments_preview>\n            <li>\n                <p id=\"cpreview\"><txp:text item=\"press_preview_then_submit\" /></p>\n                <txp:comments_preview wraptag=\"\" /> <!-- links by default to form: \'comments.comment.txp\' unless you specify a different form -->\n            </li>\n        </txp:if_comments_preview>\n\n    </ol>\n\n<txp:else />\n\n<!-- else if there are no comments and if user is currently previewing comment,display it (but only if there is no error) -->\n    <txp:if_comments_preview>\n        <ol class=\"comments-list\" itemscope itemtype=\"http://schema.org/UserComments\">\n            <li>\n                <p id=\"cpreview\"><txp:text item=\"press_preview_then_submit\" /></p>\n                <txp:comments_preview wraptag=\"\" /> <!-- links by default to form: \'comments.comment.txp\' unless you specify a different form -->\n            </li>\n        </ol>\n\n    <txp:else />\n\n<!-- else just display that there are simply no comments whatsoever :( ...but only if comments are allowed -->\n        <txp:if_comments_allowed>\n            <p><txp:text item=\"no_comments\" /></p>\n        </txp:if_comments_allowed>\n\n    </txp:if_comments_preview>\n\n</txp:if_comments>\n\n<!-- if new comments are allowed for this article then display comment form, if not then display \'closed\' messages -->\n<txp:if_comments_allowed>\n    <section id=\"comments-form\">\n\n        <!-- comment invite text is taken for the article\'s comment invitation field on the \'write\' screen -->\n        <h3><txp:comments_invite showcount=\"0\" textonly=\"1\" showalways=\"1\" /></h3>\n\n        <txp:comments_form isize=\"32\" msgcols=\"64\" msgrows=\"4\" /> <!-- links by default to form: \'comment_form.comment.txp\' unless you specify a different form -->\n    </section>\n\n<txp:else />\n    \n    <!-- display either a comments expired message or a comments disabled message -->\n    <txp:if_comments>\n        <p><strong><txp:text item=\"comments_expired\" /></strong></p>\n    <txp:else />\n        <p><strong><txp:text item=\"comments_closed\" /></strong></p>\n    </txp:if_comments>\n</txp:if_comments_allowed>\n\n</section>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('comment_form', 'comment', '<p><txp:text item=\"enter_comment_here\" /></p>\n\n<!-- if there is an error, then inform user -->\n<txp:if_comments_error>\n    <txp:comments_error wraptag=\"ol\" break=\"li\" class=\"error_message\" />\n</txp:if_comments_error>\n\n<fieldset>\n\n    <p class=\"large\"><label for=\"name\"><txp:text item=\"comment_name\" /> <b class=\"required\" title=\"<txp:text item=\"required\" />\">*</b></label><br>\n    <txp:comment_name_input /></p>\n\n    <p class=\"large\"><label for=\"email\"><txp:text item=\"comment_email\" /> <b class=\"required\" title=\"<txp:text item=\"required\" />\">*</b></label><br>\n    <txp:comment_email_input /></p>\n\n    <p class=\"large\"><label for=\"web\"><txp:text item=\"comment_web\" /></label><br>\n    <txp:comment_web_input /></p>\n\n    <p><txp:comment_remember /></p>\n\n    <p class=\"small\"><label for=\"message\"><txp:text item=\"comment_message\" /> <b class=\"required\" title=\"<txp:text item=\"required\" />\">*</b></label><br>\n    <txp:comment_message_input /></p>\n\n</fieldset>\n\n<!-- preview and submit buttons (note: submit button will have a class of \'disabled\' applied until you have previewed the message at least once) -->\n<p><txp:comment_preview /> <txp:comment_submit /></p>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('default', 'article', '<article role=\"article\" itemscope itemtype=\"http://schema.org/Article\">\n\n<!-- if not an individual article then make the title h1 a link -->\n<txp:if_individual_article>\n    <h1 itemprop=\"name\"><txp:title /></h1>\n<txp:else />\n    <h1 itemprop=\"name\"><a href=\"<txp:permlink />\" itemprop=\"url\"><txp:title /></a></h1>\n</txp:if_individual_article>\n\n    <p><strong><txp:text item=\"posted\" /></strong> <time datetime=\"<txp:posted format=\"iso8601\" />\" itemprop=\"datePublished\"><txp:posted /></time><br>\n        <strong><txp:text item=\"comments\" /></strong> <a href=\"<txp:permlink />#comments-head\" title=\"<txp:text item=\"view\" />&#8230;\" itemprop=\"discussionUrl\" itemscope itemtype=\"http://schema.org/UserComments\">\n\n<!-- if comments then display the number, if no comments then print \'none\' -->\n<txp:if_comments>\n    <span itemprop=\"interactionCount\"><txp:comments_count /></span>\n<txp:else />\n    <span itemprop=\"interactionCount\"><txp:text item=\"none\" /></span>\n</txp:if_comments>\n\n    </a></p>\n\n    <txp:if_article_image>\n        <txp:images form=\"images\" />\n    </txp:if_article_image>\n\n    <div itemprop=\"articleBody\">\n        <txp:body />\n    </div>\n\n    <p><strong><txp:text item=\"author\" /></strong> <span itemprop=\"author\"><txp:author link=\"1\" this_section=\"1\" /></span>\n\n<!-- only display categories if they are actually set for an article, otherwise omit -->\n    <txp:if_article_category>\n        <br><strong><txp:text item=\"categories\" /></strong> <span itemprop=\"keywords\"><txp:category1 title=\"1\" link=\"1\" /><txp:if_article_category number=\"1\"><txp:if_article_category number=\"2\">, </txp:if_article_category></txp:if_article_category><txp:category2 title=\"1\" link=\"1\" /></span>\n    </txp:if_article_category>\n\n    </p>\n\n<!-- if this is an individual article then add the comments section via form: comments_display.article.txp -->\n<txp:if_individual_article>\n    <txp:output_form form=\"comments_display\" />\n</txp:if_individual_article>\n\n</article>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('files', 'file', '<!-- set up a variables to check whether a file also has a title, description, category associated with it... -->\n<txp:variable name=\"file_download_title\" value=\'<txp:file_download_name title=\"1\" />\' />\n<txp:variable name=\"file_download_description\" value=\'<txp:file_download_description />\' />\n<txp:variable name=\"file_download_category\" value=\'<txp:file_download_category />\' />\n\n<div itemscope itemtype=\"http://schema.org/userDownloads\">\n\n    <!-- ...if exists, use the file title, otherwise use file name -->\n    <txp:if_variable name=\"file_download_title\" value=\"\">\n        <strong itemprop=\"name\"><a href=\"<txp:file_download_link />\" title=\"<txp:file_download_name />\" itemprop=\"url\"><txp:file_download_name /></a></strong><br>\n    <txp:else />\n        <strong itemprop=\"name\"><a href=\"<txp:file_download_link />\" title=\"<txp:file_download_name title=\'1\' />\" itemprop=\"url\"><txp:file_download_name title=\"1\" /></a></strong><br>\n    </txp:if_variable>\n\n    <!-- ...if exists, use the file description, otherwise omit that line -->\n    <txp:if_variable name=\"file_download_description\" value=\"\">\n    <txp:else />\n    <span itemprop=\"description\"><txp:file_download_description /></span><br>\n    </txp:if_variable>\n\n    <span class=\"footnote\">\n\n    <!-- ...if exists, use the file category, otherwise omit that line -->\n    <txp:if_variable name=\"file_download_category\" value=\"\">\n    <txp:else />\n        <strong><txp:text item=\"category\" /></strong> <txp:file_download_category /> &#124; \n    </txp:if_variable>\n\n        <strong><txp:text item=\"author\" /></strong> <txp:file_download_author link=\"1\" /> &#124; \n        <strong><txp:text item=\"file_size\" /></strong> <txp:file_download_size /> &#124; \n        <strong><txp:text item=\"last_modified\" /></strong> <span itemprop=\"dateModified\"><txp:file_download_created /></span> &#124; \n        <strong><txp:text item=\"download_count\" /></strong> <span itemprop=\"interactionCount\"><txp:file_download_downloads /></span>\n\n    </span>\n\n</div>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('images', 'misc', '<!-- set up a variable to check whether a image also has a caption associated with it... -->\n<txp:variable name=\"caption\" value=\'<txp:image_info type=\"caption\" />\' />\n\n<!-- ...now use that image caption and wrap img inside a figure with figcaption tags, otherwise just use a plain img tag -->\n<txp:if_variable name=\"caption\" value=\"\">\n\n<!-- image - overriding the width and height to let the image scale to fit parent container -->\n    <p itemprop=\"image\"><txp:image width=\"0\" height=\"0\" /></p>\n\n<txp:else />\n\n    <figure itemscope itemtype=\"http://schema.org/ImageObject\">\n\n<!-- image - overriding the width and height to let the image scale to fit parent container -->\n        <span itemprop=\"image\"><txp:image width=\"0\" height=\"0\" /></span>\n\n<!-- you do not need to specify the attribute type=\"caption\" as that is the default setting for <txp:image_info /> tag -->\n        <figcaption itemprop=\"caption\"><txp:image_info type=\"caption\" /></figcaption>\n\n    </figure>\n\n</txp:if_variable>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('plainlinks', 'link', '<!-- This is being used as an external links form, therefore rel is set to \'external\' -->\n<txp:linkdesctitle rel=\"external\" />')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('popup_comments', 'comment', '<!DOCTYPE html>\n<html lang=\"<txp:lang />\" dir=\"<txp:text item=\"lang_dir\" />\">\n\n<head>\n    <meta charset=\"utf-8\">\n\n    <title><txp:page_title /></title>\n    <meta name=\"generator\" content=\"Textpattern CMS\">\n    <meta name=\"robots\" content=\"noindex, follow, noodp, noydir\">\n\n<!-- mobile viewport optimised, more info: http://h5bp.com/viewport -->\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\n<!-- css -->\n    <!-- Google font API (remove this if you intend to use the theme in a project without internet access) -->\n    <link rel=\"stylesheet\" href=\"http://fonts.googleapis.com/css?family=PT+Serif:400,400italic,700,700italic|Cousine\">\n\n    <txp:css format=\"link\" media=\"\" />\n    <!-- or you can use (faster) external CSS files eg. <link rel=\"stylesheet\" href=\"<txp:site_url />css/default.css\"> -->\n\n<!-- HTML5/Media Queries support for IE < 9 (you can remove this section and the corresponding \'js\' directory files if you don\'t intend to support IE < 9) -->\n    <!--[if lt IE 9]>\n        <script src=\"<txp:site_url />js/html5shiv.js\"></script>\n        <script src=\"<txp:site_url />js/css3-mediaqueries.js\"></script>\n    <![endif]-->\n\n</head>\n\n<body class=\"popup-page\">\n\n    <div class=\"wrapper\">\n        <div class=\"container\">\n\n            <!-- this form is only used if you set \'Comments mode\' to \'popup\' format in preferences -->\n            <txp:popup_comments />\n\n        </div> <!-- /.container -->\n    </div> <!-- /.wrapper -->\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('search_input', 'misc', '<form role=\"search\" method=\"get\" action=\"<txp:site_url />\">\n    <h4><label for=\"search-textbox\"><txp:text item=\"search\" /></label></h4>\n    <p><input id=\"search-textbox\" type=\"search\" name=\"q\"<txp:if_search> value=\"<txp:search_term />\"</txp:if_search>><input type=\"submit\" value=\"<txp:text item=\"go\" />\"></p>\n</form>')";
$create_sql[] = "INSERT INTO `".PFX."txp_form`(`name`,`type`,`Form`) VALUES('search_results', 'article', '<!-- count how many results return -->\n<txp:article limit=\"10\" pgonly=\"1\" />\n\n<txp:if_search_results>\n\n<!-- if search result count greater than 200 then display excessive results message, otherwise show search result count -->\n    <txp:if_search_results max=\"200\">\n        <h3><txp:search_result_count /> <txp:text item=\"matching_search_request\" /> &#8216;<txp:search_term />&#8217;&#8230;</h3>\n    <txp:else />\n        <h3><txp:text item=\"too_common_search_term\" /> &#8216;<txp:search_term />&#8217;</h3>\n    </txp:if_search_results>\n\n<!-- if no search results, then display no search results message -->\n<txp:else />\n    <h3><txp:text item=\"no_search_matches\" /></h3>\n\n</txp:if_search_results>\n\n<!-- display resulting articles (10 per page) -->\n<txp:article limit=\"10\" wraptag=\"ul\" class=\"article-list\">\n\n    <li role=\"article\" itemscope itemtype=\"http://schema.org/Article\">\n        <h4 itemprop=\"name\"><a href=\"<txp:permlink />\" itemprop=\"url\"><txp:title /></a></h4>\n\n<!-- if the article has an excerpt, display that, otherwise show highlighted keywords in context of article -->\n        <txp:if_excerpt>\n            <div itemprop=\"description\">\n                <txp:excerpt />\n            </div>\n        <txp:else />\n            <p><txp:search_result_excerpt /></p>\n        </txp:if_excerpt>\n\n        <p class=\"footnote\"><txp:text item=\"posted\" /> <time datetime=\"<txp:posted format=\"iso8601\" />\" itemprop=\"datePublished\"><txp:posted /></time>, <txp:text item=\"author\" /> <span itemprop=\"author\"><txp:author link=\"1\" this_section=\"1\" /></span></p>\n    </li>\n\n</txp:article>\n\n<!-- check if there are further results and provide pagination links or disabled buttons depending on the result,\n    this method is more flexibile than using simple txp:older/txp:newer tags -->\n<txp:if_search_results min=\"11\">\n\n    <txp:variable name=\"prev\" value=\'<txp:older />\' />\n    <txp:variable name=\"next\" value=\'<txp:newer />\' />\n\n    <p class=\"paginator\">\n        <txp:if_variable name=\"next\" value=\"\">\n        <txp:else />\n            <a rel=\"prev\" href=\"<txp:newer />\" title=\"&#8592; <txp:text item=\"prev\" />\">&#8592; <txp:text item=\"prev\" /></a>\n        </txp:if_variable>\n\n        <txp:if_variable name=\"prev\" value=\"\">\n        <txp:else />\n            <a rel=\"next\" href=\"<txp:older />\" title=\"<txp:text item=\"next\" /> &#8594;\"><txp:text item=\"next\" /> &#8594;</a>\n        </txp:if_variable>\n    </p>\n\n</txp:if_search_results>')";
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
  `data` text,
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

$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (1, now(), 'textpattern', 'http://textpattern.com/', 'Textpattern Website', '10', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (2, now(), 'textpattern', 'http://textpattern.net/', 'Textpattern User Documentation', '20', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (3, now(), 'textpattern', 'http://textpattern.org/', 'Textpattern Resources', '30', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (4, now(), 'textpattern', 'http://textpattern.com/@textpattern', '@textpattern', '40', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (5, now(), 'textpattern', 'http://textpattern.com/+', '+Textpattern CMS', '50', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (6, now(), 'textpattern', 'http://textpattern.com/facebook', 'Textpattern Facebook Group', '60', '')";
$create_sql[] = "INSERT INTO `".PFX."txp_link` VALUES (7, now(), 'textpattern', 'http://textgarden.org/', 'Textgarden', '70', '')";

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
  KEY `time` (`time`),
  KEY `page` (`page`),
  KEY `ip` (`ip`),
  KEY `host` (`host`)
) $tabletype AUTO_INCREMENT=77 ";

$create_sql[] = "CREATE TABLE `".PFX."txp_page` (
  `name` varchar(128) NOT NULL,
  `user_html` text NOT NULL,
  PRIMARY KEY (`name`)
) $tabletype PACK_KEYS=1";

// sql:txp_page
$create_sql[] = "INSERT INTO `".PFX."txp_page`(`name`,`user_html`) VALUES('archive', '<!DOCTYPE html>\n<html lang=\"<txp:lang />\" dir=\"<txp:text item=\"lang_dir\" />\">\n\n<head>\n    <meta charset=\"utf-8\">\n\n    <title><txp:page_title /></title>\n    <meta name=\"generator\" content=\"Textpattern CMS\">\n    <meta name=\"robots\" content=\"index, follow, noodp, noydir\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\n    <txp:if_individual_article>\n<!-- add meta author for individual articles -->\n        <txp:meta_author title=\"1\" />\n    </txp:if_individual_article>\n\n<!-- content feeds -->\n    <txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n    <txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n    <txp:rsd />\n\n<!-- specify canonical, more info: http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html -->\n    <txp:if_individual_article>\n        <link rel=\"canonical\" href=\"<txp:permlink />\">\n    <txp:else />\n        <link rel=\"canonical\" href=\"<txp:section url=\"1\" />\">\n    </txp:if_individual_article>\n\n<!-- css -->\n    <!-- Google font API (remove this if you intend to use the theme in a project without internet access) -->\n    <link rel=\"stylesheet\" href=\"http://fonts.googleapis.com/css?family=PT+Serif:400,400italic,700,700italic|Cousine\">\n\n    <txp:css format=\"link\" media=\"\" />\n    <!-- or you can use (faster) external CSS files eg. <link rel=\"stylesheet\" href=\"<txp:site_url />css/default.css\"> -->\n\n<!-- HTML5/Media Queries support for IE8 (you can remove this section and the corresponding \'js\' directory file if you don\'t intend to support IE8) -->\n    <!--[if lt IE 9]>\n        <script src=\"<txp:site_url />js/html5shiv.js\"></script>\n        <txp:css format=\"link\" media=\"\" name=\"ie8\" />\n    <![endif]-->\n\n</head>\n\n<body class=\"<txp:section />-page\">\n\n<!-- header -->\n    <header role=\"banner\">\n        <h1><txp:link_to_home><txp:site_name /></txp:link_to_home></h1>\n        <h3><txp:site_slogan /></h3>\n    </header>\n\n<!-- navigation -->\n    <nav role=\"navigation\" class=\"site-navigation\" aria-label=\"<txp:text item=\"navigation\" />\">\n        <txp:section_list default_title=\'<txp:text item=\"home\" />\' include_default=\"1\" wraptag=\"ul\" break=\"\">\n            <li<txp:if_section name=\'<txp:section />\'> class=\"active\"</txp:if_section>>\n                <txp:section title=\"1\" link=\"1\" />\n            </li>\n        </txp:section_list>\n    </nav>\n\n    <div class=\"wrapper\">\n        <div class=\"container\">\n\n<!-- left (main) column -->\n            <main role=\"main\" aria-label=\"<txp:text item=\"main_content\" />\">\n\n                <txp:if_article_list>\n\n                    <section role=\"region\" itemscope itemtype=\"http://schema.org/CollectionPage\">\n                        <h1 itemprop=\"name\"><txp:section title=\"1\" /></h1>\n\n                        <div itemprop=\"mainContentOfPage\">\n                            <txp:if_article_list>\n                                <txp:article form=\"article_listing\" limit=\"5\" wraptag=\"ul\" class=\"article-list\" />\n                                <!-- or if you want to list all articles from all sections instead, then replace txp:article with txp:article_custom -->\n                            <txp:else />\n                                <txp:article />\n                            </txp:if_article_list>\n                        </div>\n                    </section>\n\n                <txp:else />\n\n                    <txp:article />\n\n                </txp:if_article_list>\n\n                <!-- add pagination links to foot of article/article listings if there are more articles available,\n                    this method is more flexibile than using simple txp:link_to_prev/txp:link_to_next or txp:older/txp:newer tags -->\n                <txp:if_individual_article>\n\n                    <txp:variable name=\"more\" value=\'<txp:link_to_prev /><txp:link_to_next />\' />\n                    <txp:variable name=\"prev\" value=\'<txp:link_to_prev />\' />\n                    <txp:variable name=\"next\" value=\'<txp:link_to_next />\' />\n\n                    <txp:if_variable name=\"more\" value=\"\">\n                    <txp:else />\n                        <p class=\"paginator\">\n                            <txp:if_variable name=\"prev\" value=\"\">\n                            <txp:else />\n                                <a rel=\"prev\" href=\"<txp:link_to_prev />\" title=\"<txp:prev_title />\">&#8592; <txp:text item=\"older\" /></a>\n                            </txp:if_variable>\n\n                            <txp:if_variable name=\"next\" value=\"\">\n                            <txp:else />\n                                <a rel=\"next\" href=\"<txp:link_to_next />\" title=\"<txp:next_title />\"><txp:text item=\"newer\" /> &#8594;</a>\n                            </txp:if_variable>\n                        </p>\n                    </txp:if_variable>\n\n                <txp:else />\n\n                    <txp:variable name=\"more\" value=\'<txp:older /><txp:newer />\' />\n                    <txp:variable name=\"prev\" value=\'<txp:older />\' />\n                    <txp:variable name=\"next\" value=\'<txp:newer />\' />\n\n                    <txp:if_variable name=\"more\" value=\"\">\n                    <txp:else />\n                        <p class=\"paginator\">\n                            <txp:if_variable name=\"prev\" value=\"\">\n                            <txp:else />\n                                <a rel=\"prev\" href=\"<txp:older />\" title=\"<txp:text item=\"older\" />\">&#8592; <txp:text item=\"older\" /></a>\n                            </txp:if_variable>\n\n                            <txp:if_variable name=\"next\" value=\"\">\n                            <txp:else />\n                                <a rel=\"next\" href=\"<txp:newer />\" title=\"<txp:text item=\"newer\" />\"><txp:text item=\"newer\" /> &#8594;</a>\n                            </txp:if_variable>\n                        </p>\n                    </txp:if_variable>\n\n                </txp:if_individual_article>\n\n            </main>\n\n    <!-- right (complementary) column -->\n            <div role=\"complementary\">\n                <txp:search_input /> <!-- links by default to form: \'search_input.misc.txp\' unless you specify a different form -->\n    \n                <!-- Feed links, default flavor is rss, so we don\'t need to specify a flavor on the first feed_link -->\n                <p><txp:feed_link label=\"RSS\" class=\"feed-rss\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" class=\"feed-atom\" /></p>\n\n                <h4><txp:text item=\"external_links\" /></h4>\n                <txp:linklist wraptag=\"ul\" break=\"li\" limit=\"10\" /> <!-- links by default to form: \'plainlinks.link.txp\' unless you specify a different form -->\n            </div> <!-- /complementary -->\n\n        </div> <!-- /.container -->\n    </div> <!-- /.wrapper -->\n\n<!-- footer -->\n    <footer role=\"contentinfo\">\n        <p><small><txp:text item=\"published_with\" /> <a rel=\"external\" href=\"http://textpattern.com\" title=\"<txp:text item=\"go_txp_com\" />\">Textpattern CMS</a>.</small></p>\n    </footer>\n\n    <!-- add your own JavaScript here -->\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_page`(`name`,`user_html`) VALUES('default', '<!DOCTYPE html>\n<html lang=\"<txp:lang />\" dir=\"<txp:text item=\"lang_dir\" />\">\n\n<head>\n    <meta charset=\"utf-8\">\n\n    <title><txp:page_title /></title>\n    <meta name=\"generator\" content=\"Textpattern CMS\">\n    <txp:if_search>\n        <meta name=\"robots\" content=\"none\">\n    <txp:else />\n        <txp:if_category>\n            <meta name=\"robots\" content=\"noindex, follow, noodp, noydir\">\n        <txp:else />\n            <txp:if_author>\n                <meta name=\"robots\" content=\"noindex, follow, noodp, noydir\">\n            <txp:else />\n                <meta name=\"robots\" content=\"index, follow, noodp, noydir\">\n            </txp:if_author>\n        </txp:if_category>\n    </txp:if_search>\n\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\n<!-- content feeds -->\n    <txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n    <txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n    <txp:rsd />\n\n<!-- specify canonical, more info: http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html -->\n    <txp:if_section name=\"\">\n        <link rel=\"canonical\" href=\"<txp:site_url />\">\n    <txp:else />\n        <txp:if_individual_article>\n            <link rel=\"canonical\" href=\"<txp:permlink />\">\n        <txp:else />\n            <link rel=\"canonical\" href=\"<txp:section url=\"1\" />\">\n        </txp:if_individual_article>\n    </txp:if_section>\n\n<!-- css -->\n    <!-- Google font API (remove this if you intend to use the theme in a project without internet access) -->\n    <link rel=\"stylesheet\" href=\"http://fonts.googleapis.com/css?family=PT+Serif:400,400italic,700,700italic|Cousine\">\n\n    <txp:css format=\"link\" media=\"\" />\n    <!-- or you can use (faster) external CSS files eg. <link rel=\"stylesheet\" href=\"<txp:site_url />css/default.css\"> -->\n\n<!-- HTML5/Media Queries support for IE8 (you can remove this section and the corresponding \'js\' directory file if you don\'t intend to support IE8) -->\n    <!--[if lt IE 9]>\n        <script src=\"<txp:site_url />js/html5shiv.js\"></script>\n        <txp:css format=\"link\" media=\"\" name=\"ie8\" />\n    <![endif]-->\n\n</head>\n\n<body class=\"<txp:if_section name=\"\"><txp:if_search>search<txp:else />front</txp:if_search><txp:else /><txp:section /></txp:if_section>-page\">\n\n<!-- header -->\n    <header role=\"banner\">\n        <h1><txp:link_to_home><txp:site_name /></txp:link_to_home></h1>\n        <h3><txp:site_slogan /></h3>\n    </header>\n\n<!-- navigation -->\n    <nav role=\"navigation\" class=\"site-navigation\" aria-label=\"<txp:text item=\"navigation\" />\">\n        <txp:section_list default_title=\'<txp:text item=\"home\" />\' include_default=\"1\" wraptag=\"ul\" break=\"\">\n            <li<txp:if_section name=\'<txp:section />\'><txp:if_search><txp:else /><txp:if_category><txp:else /><txp:if_author><txp:else /> class=\"active\"</txp:if_author></txp:if_category></txp:if_search></txp:if_section>>\n                <txp:section title=\"1\" link=\"1\" />\n            </li>\n        </txp:section_list>\n    </nav>\n\n    <div class=\"wrapper\">\n        <div class=\"container\">\n\n<!-- left (main) column -->\n            <main role=\"main\" aria-label=\"<txp:text item=\"main_content\" />\">\n\n            <!-- is this the search result page? also omits the pagination links below (uses pagination format within search_results.article.txp instead) -->\n                <txp:if_search>\n\n                    <section role=\"region\" itemscope itemtype=\"http://schema.org/SearchResultsPage\">\n                        <h1 itemprop=\"name\"><txp:text item=\"search_results\" /></h1>\n                        <div itemprop=\"mainContentOfPage\">\n                            <txp:output_form form=\"search_results\" />\n                        </div>\n                    </section>\n\n                <txp:else />\n\n                <!-- else is this an article category list? -->\n                    <txp:if_category>\n\n                        <section role=\"region\" itemscope itemtype=\"http://schema.org/CollectionPage\">\n                            <h1 itemprop=\"name\"><txp:text item=\"category\" /> <txp:category title=\"1\" /></h1>\n                            <div itemprop=\"mainContentOfPage\">\n                                <txp:article form=\"article_listing\" limit=\"5\" wraptag=\"ul\" class=\"article-list\" />\n                            </div>\n                        </section>\n\n                    <txp:else />\n\n                        <!-- else is this an article author list? -->\n                        <txp:if_author>\n\n                            <section role=\"region\" itemscope itemtype=\"http://schema.org/CollectionPage\">\n                                <h1 itemprop=\"name\"><txp:text item=\"author\" /> <txp:author /></h1>\n                                <div itemprop=\"mainContentOfPage\">\n                                    <txp:article form=\"article_listing\" limit=\"5\" wraptag=\"ul\" class=\"article-list\" />\n                                </div>\n                            </section>\n\n                        <txp:else />\n\n                            <!-- else display articles normally -->\n                            <txp:article limit=\"5\" /> <!-- links by default to form: \'default.article.txp\' unless you specify a different form -->\n\n                        </txp:if_author>\n                    </txp:if_category>\n\n                    <!-- add pagination links to foot of article/article listings/category listings if there are more articles available,\n                    this method is more flexibile than using simple txp:link_to_prev/txp:link_to_next or txp:older/txp:newer tags -->\n                    <txp:variable name=\"more\" value=\'<txp:older /><txp:newer />\' />\n                    <txp:variable name=\"prev\" value=\'<txp:older />\' />\n                    <txp:variable name=\"next\" value=\'<txp:newer />\' />\n\n                    <txp:if_variable name=\"more\" value=\"\">\n                    <txp:else />\n                        <p class=\"paginator\">\n                            <txp:if_variable name=\"prev\" value=\"\">\n                            <txp:else />\n                                <a rel=\"prev\" href=\"<txp:older />\" title=\"<txp:text item=\"older\" />\">&#8592; <txp:text item=\"older\" /></a>\n                            </txp:if_variable>\n\n                            <txp:if_variable name=\"next\" value=\"\">\n                            <txp:else />\n                                <a rel=\"next\" href=\"<txp:newer />\" title=\"<txp:text item=\"newer\" />\"><txp:text item=\"newer\" /> &#8594;</a>\n                            </txp:if_variable>\n                        </p>\n                    </txp:if_variable>\n\n                </txp:if_search>\n\n            </main>\n\n<!-- right (complementary) column -->\n            <div role=\"complementary\">\n                <txp:search_input /> <!-- links by default to form: \'search_input.misc.txp\' unless you specify a different form -->\n\n                <!-- Feed links, default flavor is rss, so we don\'t need to specify a flavor on the first feed_link -->\n                <p><txp:feed_link label=\"RSS\" class=\"feed-rss\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" class=\"feed-atom\" /></p>\n\n                <h4><txp:text item=\"external_links\" /></h4>\n                <txp:linklist wraptag=\"ul\" break=\"li\" limit=\"10\" /> <!-- links by default to form: \'plainlinks.link.txp\' unless you specify a different form -->\n            </div> <!-- /complementary -->\n\n        </div> <!-- /.container -->\n    </div> <!-- /.wrapper -->\n\n<!-- footer -->\n    <footer role=\"contentinfo\">\n        <p><small><txp:text item=\"published_with\" /> <a rel=\"external\" href=\"http://textpattern.com\" title=\"<txp:text item=\"go_txp_com\" />\">Textpattern CMS</a>.</small></p>\n    </footer>\n\n    <!-- add your own JavaScript here -->\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO `".PFX."txp_page`(`name`,`user_html`) VALUES('error_default', '<!DOCTYPE html>\n<html lang=\"<txp:lang />\" dir=\"<txp:text item=\"lang_dir\" />\">\n\n<head>\n    <meta charset=\"utf-8\">\n\n    <title><txp:error_status /></title>\n    <meta name=\"generator\" content=\"Textpattern CMS\">\n    <meta name=\"robots\" content=\"none\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n\n<!-- content feeds -->\n    <txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n    <txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n    <txp:rsd />\n\n<!-- css -->\n    <!-- Google font API (remove this if you intend to use the theme in a project without internet access) -->\n    <link rel=\"stylesheet\" href=\"http://fonts.googleapis.com/css?family=PT+Serif:400,400italic,700,700italic|Cousine\">\n\n    <txp:css format=\"link\" media=\"\" />\n    <!-- or you can use (faster) external CSS files eg. <link rel=\"stylesheet\" href=\"<txp:site_url />css/default.css\"> -->\n\n<!-- HTML5/Media Queries support for IE8 (you can remove this section and the corresponding \'js\' directory file if you don\'t intend to support IE8) -->\n    <!--[if lt IE 9]>\n        <script src=\"<txp:site_url />js/html5shiv.js\"></script>\n        <txp:css format=\"link\" media=\"\" name=\"ie8\" />\n    <![endif]-->\n\n</head>\n\n<body class=\"error-page\">\n\n<!-- header -->\n    <header role=\"banner\">\n        <h1><txp:link_to_home><txp:site_name /></txp:link_to_home></h1>\n        <h3><txp:site_slogan /></h3>\n    </header>\n\n<!-- navigation -->\n    <nav role=\"navigation\" class=\"site-navigation\" aria-label=\"<txp:text item=\"navigation\" />\">\n        <txp:section_list default_title=\'<txp:text item=\"home\" />\' include_default=\"1\" wraptag=\"ul\" break=\"li\">\n            <txp:section title=\"1\" link=\"1\" />\n        </txp:section_list>\n    </nav>\n\n    <div class=\"wrapper\">\n        <div class=\"container\">\n\n<!-- left (main) column -->\n            <main role=\"main\" aria-label=\"<txp:text item=\"main_content\" />\">\n                <h1 class=\"error-status\"><txp:error_status /></h1>\n                <p class=\"error-msg\"><txp:error_message /></p>\n            </main>\n\n<!-- right (complementary) column -->\n            <div role=\"complementary\">\n                <txp:search_input /> <!-- links by default to form: \'search_input.misc.txp\' unless you specify a different form -->\n\n                <!-- Feed links, default flavor is rss, so we don\'t need to specify a flavor on the first feed_link -->\n                <p><txp:feed_link label=\"RSS\" class=\"feed-rss\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" class=\"feed-atom\" /></p>\n\n                <h4><txp:text item=\"external_links\" /></h4>\n                <txp:linklist wraptag=\"ul\" break=\"li\" limit=\"10\" /> <!-- links by default to form: \'plainlinks.link.txp\' unless you specify a different form -->\n            </div> <!-- /complementary -->\n\n        </div> <!-- /.container -->\n    </div> <!-- /.wrapper -->\n\n<!-- footer -->\n    <footer role=\"contentinfo\">\n        <p><small><txp:text item=\"published_with\" /> <a rel=\"external\" href=\"http://textpattern.com\" title=\"<txp:text item=\"go_txp_com\" />\">Textpattern CMS</a>.</small></p>\n    </footer>\n\n    <!-- add your own JavaScript here -->\n\n</body>\n</html>')";
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

$prefs['blog_uid'] = md5(uniqid(rand(), true));
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
$create_sql[] = "INSERT INTO `".PFX."txp_section` VALUES ('default', 'default', 'default', 0, 1, 1, 1, 'Default')";
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

// Skip the RPC language fetch when testing.
if (defined('TXP_TEST'))
{
	return;
}

require_once txpath.'/lib/IXRClass.php';
$client = new IXR_Client('http://rpc.textpattern.com');

if (!$client->query('tups.getLanguage', $prefs['blog_uid'],LANG))
{
	// If cannot install from lang file, setup the English lang.
	if (!install_language_from_file(LANG))
	{
		$lang = 'en-gb';
		include_once txpath.'/setup/en-gb.php';
		if (!@$lastmod)
		{
			$lastmod = '0000-00-00 00:00:00';
		}

		foreach ($en_gb_lang as $evt_name => $evt_strings)
		{
			foreach ($evt_strings as $lang_key => $lang_val)
			{
				$lang_val = doSlash($lang_val);
				if (@$lang_val)
				{
					mysql_query("INSERT DELAYED INTO `".PFX."txp_lang` SET lang='en-gb', name='".$lang_key."', event='".$evt_name."', data='".$lang_val."', lastmod='".$lastmod."'");
				}
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
		{
			$item[$name] = doSlash($value);
		}
		mysql_query("INSERT DELAYED INTO `".PFX."txp_lang` SET lang='".LANG."', name='".$item['name']."', event='".$item['event']."', data='".$item['data']."', lastmod='".strftime('%Y%m%d%H%M%S', $item['uLastmod'])."'");
	}
}

mysql_query("FLUSH TABLE `".PFX."txp_lang`");


/**
 * Stub replacement for txplib_db.php/safe_escape()
 *
 * @ignore
 */

function safe_escape($in = '')
{
	return mysql_real_escape_string($in);
}

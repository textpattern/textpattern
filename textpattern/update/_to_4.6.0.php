<?php

if (!defined('TXP_UPDATE'))
{
	exit("Nothing here. You can't access this file directly.");
}

safe_alter('textpattern', "CHANGE COLUMN `textile_body` `textile_body` VARCHAR(32) NOT NULL DEFAULT '1', CHANGE COLUMN `textile_excerpt` `textile_excerpt` VARCHAR(32) NOT NULL DEFAULT '1';");
safe_update('txp_prefs', "name = 'pane_article_textfilter_help_visible'", "name = 'pane_article_textile_help_visible'");

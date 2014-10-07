<?php

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	// Updates comment email length.
	safe_alter('txp_discuss', "MODIFY email VARCHAR(254) NOT NULL default ''");

	// Store IPv6 properly in logs.
	safe_alter('txp_log', "MODIFY ip VARCHAR(45) NOT NULL default ''");

	// Save sections correctly in articles.
	safe_alter('textpattern', "MODIFY Section VARCHAR(128) NOT NULL default ''");

	// Ensure all memory-mappable columns have defaults
	safe_alter('txp_form', "MODIFY `name` VARCHAR(64) NOT NULL default ''");
	safe_alter('txp_page', "MODIFY `name` VARCHAR(128) NOT NULL default ''");
	safe_alter('txp_prefs', "MODIFY `prefs_id` INT(11) NOT NULL default '1'");
	safe_alter('txp_prefs', "MODIFY `name` VARCHAR(255) NOT NULL default ''");
	safe_alter('txp_section', "MODIFY `name` VARCHAR(128) NOT NULL default ''");

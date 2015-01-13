<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2015 The Textpattern Development Team
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

if (!defined('TXP_UPDATE')) {
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

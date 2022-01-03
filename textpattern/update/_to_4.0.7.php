<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('TXP_UPDATE')) {
    exit("Nothing here. You can't access this file directly.");
}

$txpplugin = getThings('DESCRIBE `'.PFX.'txp_plugin`');

if (!in_array('load_order', $txpplugin)) {
    safe_alter('txp_plugin', "ADD load_order TINYINT UNSIGNED NOT NULL DEFAULT 5");
}

// Expiry datetime for articles.
$txp = getThings("DESCRIBE `".PFX."textpattern`");

if (!in_array('Expires', $txp)) {
    safe_alter('textpattern', "ADD Expires DATETIME AFTER Posted");
}

safe_create_index('textpattern', 'Expires', 'Expires_idx');

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

safe_create_index('txp_plugin', 'status, type', 'status_type_idx');

// Preserve old tag behaviour during upgrades.
safe_update('txp_page', "user_html = REPLACE(user_html, '<txp:if_section>', '<txp:if_section name=\"\">')", "1 = 1");
safe_update('txp_page', "user_html = REPLACE(user_html, '<txp:if_category name=\"\">', '<txp:if_category>')", "1 = 1");
safe_update('txp_form', "Form = REPLACE(Form, '<txp:if_section>', '<txp:if_section name=\"\">')", "1 = 1");
safe_update('txp_form', "Form = REPLACE(Form, '<txp:if_category name=\"\">', '<txp:if_category>')", "1 = 1");

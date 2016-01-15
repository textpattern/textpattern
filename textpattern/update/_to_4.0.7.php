<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2016 The Textpattern Development Team
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

$txpplugin = getThings('DESCRIBE `'.PFX.'txp_plugin`');

if (!in_array('load_order', $txpplugin)) {
    safe_alter('txp_plugin', "ADD load_order TINYINT UNSIGNED NOT NULL DEFAULT 5");
}

// Enable XML-RPC server?
if (!safe_field("name", 'txp_prefs', "name = 'enable_xmlrpc_server'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'enable_xmlrpc_server', val = 0, type = 1, event = 'admin', html = 'yesnoradio', position = 130");
}

if (!safe_field("name", 'txp_prefs', "name = 'smtp_from'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'smtp_from', val = '', type = 1, event = 'admin', position = 110");
}

if (!safe_field("val", 'txp_prefs', "name = 'author_list_pageby'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'author_list_pageby', val = 25, type = 2");
}

// Expiry datetime for articles.
$txp = getThings("DESCRIBE `".PFX."textpattern`");

if (!in_array('Expires', $txp)) {
    safe_alter('textpattern', "ADD Expires DATETIME AFTER Posted");
}

safe_create_index('textpattern', 'Expires', 'Expires_idx');

// Publish expired articles, or return 410?
if (!safe_field("name", 'txp_prefs', "name = 'publish_expired_articles'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'publish_expired_articles', val = '0', type = '1', event = 'publish', html = 'yesnoradio', position = '130'");
}

// Searchable article fields hidden preference.
if (!safe_field("name", 'txp_prefs', "name = 'searchable_article_fields'")) {
    safe_insert('txp_prefs', "prefs_id = 1, name = 'searchable_article_fields', val = 'Title, Body', type = '2', event = 'publish', html = 'text_input', position = '0'");
}

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

// Replace deprecated tags with functionally equivalent, valid tags.
$tags = array(
    'sitename'    => 'site_name',
    'request_uri' => 'page_url',
    'id'          => 'page_url type="id"',
    's'           => 'page_url type="s"',
    'c'           => 'page_url type="c"',
    'q'           => 'page_url type="q"',
    'pg'          => 'page_url type="pg"',
);

foreach ($tags as $search => $replace) {
    foreach (array(' ', '/') as $end) {
        safe_update('txp_page', "user_html = REPLACE(user_html, '<txp:".$search.$end."', '<txp:".$replace.' '.trim($end)."')", "1 = 1");
        safe_update('txp_form', "Form = REPLACE(Form, '<txp:".$search.$end."', '<txp:".$replace.' '.trim($end)."')", "1 = 1");
    }
}

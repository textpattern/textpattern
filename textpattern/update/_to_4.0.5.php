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

safe_alter('txp_lang', 'DELAY_KEY_WRITE = 0');

// New status field for file downloads.
$txpfile = getThings("DESCRIBE `".PFX."txp_file`");

if (!in_array('status', $txpfile)) {
    safe_alter('txp_file', "ADD status SMALLINT NOT NULL DEFAULT '4'");
}

if (!in_array('modified', $txpfile)) {
    safe_alter('txp_file', "ADD modified DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'");
}

safe_alter('txp_file', "MODIFY modified DATETIME NOT NULL");

if (!in_array('created', $txpfile)) {
    safe_alter('txp_file', "ADD created DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'");
}

safe_alter('txp_file', "MODIFY created DATETIME NOT NULL");

if (!in_array('size', $txpfile)) {
    safe_alter('txp_file', "ADD size BIGINT");
}

if (!in_array('downloads', $txpfile)) {
    safe_alter('txp_file', "ADD downloads INT DEFAULT '0' NOT NULL");
}

$txpfile = getThings("DESCRIBE `".PFX."txp_file`");

// Copy existing file timestamps into the new database columns.
if (array_intersect(array('modified', 'created', 'size', ), $txpfile)) {
    $rs  = safe_rows("*", 'txp_file', "1 = 1");
    $dir = get_pref('file_base_path', dirname(txpath).DS.'files');

    foreach ($rs as $row) {
        if (empty($row['filename'])) {
            continue;
        }

        $path = build_file_path($dir, $row['filename']);

        if (file_exists($path) and ($stat = @stat($path))) {
            safe_update('txp_file', "created = '".date('Y-m-d H:i:s', $stat['ctime'])."', modified = '".date('Y-m-d H:i:s', $stat['mtime'])."', size = '".doSlash(sprintf('%u', $stat['size']))."'", "id = '".doSlash($row['id'])."'");
        }
    }
}

safe_update('textpattern', "Keywords = TRIM(BOTH ',' FROM
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(Keywords, '\n', ','),
                                            '\r', ','),
                                        '\t', ','),
                                    '    ', ' '),
                                '  ', ' '),
                            '  ', ' '),
                        ' ,', ','),
                    ', ', ','),
                ',,,,', ','),
            ',,', ','),
        ',,', ',')
    )",
    "Keywords != ''"
);

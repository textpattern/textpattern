<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2021 The Textpattern Development Team
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

// ... Sections permlink_mode...
$cols = getThings('describe `'.PFX.'txp_section`');

if (!in_array('permlink_mode', $cols)) {
    safe_alter('txp_section', "ADD permlink_mode VARCHAR(63) NOT NULL AFTER css");
}

// Here come unlimited custom fields a.k.a. the Textpattern Meta Store.
safe_create(
    "txp_meta",
    "`id` int(12) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(63) NULL DEFAULT NULL,
    `content_type` varchar(31) NULL DEFAULT NULL,
    `data_type` varchar(31) NULL DEFAULT '',
    `render` varchar(255) NULL DEFAULT 'textInput',
    `family` varchar(255) NULL DEFAULT NULL,
    `textfilter` tinyint(4) NULL DEFAULT NULL,
    `delimiter` varchar(31) NULL DEFAULT NULL,
    `ordinal` smallint(5) unsigned NULL DEFAULT NULL,
    `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` timestamp NULL DEFAULT NULL,
    `expires` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_content` (`name`,`content_type`)"
);

// Allow multi-select options and constraints to be defined.
safe_create(
    "txp_meta_options",
    "`meta_id` int(12) NULL DEFAULT NULL,
    `type`  varchar(31) NULL DEFAULT 'option',
    `name`  varchar(192) NULL DEFAULT NULL,
    `ordinal` int(11) NULL DEFAULT 0,
    KEY `meta_id` (`meta_id`,`name`)"
);

// Only varchar fields are catered for on update, since they were
// Txp's only official custom field type prior to this version.
//
// @todo Investigate whether this is necessary here. There may be
// a way to sneakily support glz_cf by NOT creating this here, but
// using Textpattern\Meta\Field() to instantiate one "new field"
// per existing cutom_N, then calling ->save() on it with the
// data populated from each existing custom_N column. The save()
// rountine is responsible for creating the value tables for
// data types it doesn't already have so, providing the glz_cf
// datatypes can be mapped to internal types via a tiny plugin
// on the dataTypes callback, all CF data may possibly be migrated.
safe_create(
    "txp_meta_value_varchar",
    "`meta_id` int(12) NULL DEFAULT NULL,
    `content_id` int(12) NULL DEFAULT NULL,
    `value_id` tinyint(4) NULL DEFAULT '0',
    `value_raw` varchar(255) NULL DEFAULT NULL,
    `value` varchar(255) NULL DEFAULT NULL,
    UNIQUE KEY `meta_content` (`meta_id`,`content_id`,`value_id`)"
);

// Migrate existing custom field data.
// Parts of this are from the old getCustomFields() function.
$rows = safe_rows('*', 'textpattern', '1=1');
$cfs = preg_grep('/^custom_\d+_set/', array_keys($prefs));
$numFields = count($rows) * count($cfs);
$fieldList = array();
$fieldTally = 0;
//dmp('TOTAL FIELDS', $numFields);

foreach ($cfs as $name) {
    preg_match('/(\d+)/', $name, $match);

    if (!empty($prefs[$name])) {
        $fieldList[$match[1]] = $prefs[$name];
    }
}

// Pull all data from the CFs that were in use for each article.
// @Todo Make atomic?
try {
    safe_query('START TRANSACTION');

    if ($cfs) {
        foreach ($rows as $idx => $row) {
            $insert = array();
            $safeArticleId = doSlash($row['ID']);

            foreach ($fieldList as $fieldNum => $fieldName) {
                $safeNum = doSlash($fieldNum);
                $safeName = doSlash(sanitizeForUrl($fieldName));
                $safeLabel = doSlash($fieldName);
                $safeContent = isset($row['custom_' . $fieldNum]) ? doSlash($row['custom_' . $fieldNum]) : '';

                // First article: create the meta fields.
                if ($idx === 0) {
                    $exists = safe_field('id', 'txp_meta', "id='$safeNum' AND name='$safeName'");

                    if (!$exists) {
                        safe_insert(
                            "txp_meta",
                            "id = '$safeNum',
                            name = '$safeName',
                            content_type = 'article',
                            data_type = 'varchar',
                            textfilter = 1,
                            ordinal = '$safeNum'
                            "
                        );
                        safe_insert(
                            "txp_lang",
                            "lang = '" . LANG . "',
                            name = 'txpcf_article_$safeName',
                            event = 'article',
                            owner = 'custom_field',
                            data = '$fieldName'
                            "
                        );
                    }
                }

                if ($safeContent === '') {
                    $fieldTally++;
                } else {
                    $ok = safe_insert(
                        "txp_meta_value_varchar",
                        "meta_id = '$safeNum',
                        content_id = '$safeArticleId',
                        value_raw = '$safeContent',
                        value = '$safeContent'
                        "
                    );

                    if ($ok) {
                        $fieldTally++;
                    }
                }
            }
        }

        // Delete existing CF columns ONLY when we're sure all data is migrated.
        // Suppress warnings because there's no guarantee the column exists, and MySQL
        // has no DROP COLUMN IF EXISTS syntax.
        // @Todo Defensive code around here in the event there are no CFs in the
        //       textpattern table but the names still exist in prefs. And vice versa?
        if ($fieldTally === $numFields) {
            foreach ($fieldList as $fieldNum => $fieldName) {
                @safe_alter('textpattern', "drop column `custom_" . $fieldNum . "`");
            }

            safe_delete('txp_prefs', "name like 'custom\_%\_set'");
            safe_query('COMMIT');
        }
    }
} catch (DatabaseException $e) {
    safe_query('ROLLBACK');
}

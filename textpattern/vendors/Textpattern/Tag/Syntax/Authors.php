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

/**
 * Generates a list of authors.
 *
 * @since  4.6.0
 */

namespace Textpattern\Tag\Syntax;

class Authors
{
    /**
     * Generates a list of authors.
     *
     * @param  array  $atts
     * @param  string $thing
     * @return string
     */

    public static function renderAuthors($atts, $thing = null)
    {
        global $thisauthor, $txp_groups;

        extract(lAtts(array(
            'break'    => '',
            'class'    => '',
            'form'     => '',
            'group'    => '',
            'label'    => '',
            'labeltag' => '',
            'limit'    => '',
            'name'     => '',
            'offset'   => '',
            'sort'     => 'name ASC',
            'wraptag'  => '',
        ), $atts));

        $sql = array("1 = 1");
        $sql_limit = '';
        $sql_sort = " ORDER BY ".doSlash($sort);

        if ($name) {
            $sql[] = "name IN (".join(', ', quote_list(do_list($name))).")";
        }

        if ($group !== '') {
            $privs = do_list($group);
            $groups = array_flip($txp_groups);

            foreach ($privs as &$priv) {
                if (isset($groups[$priv])) {
                    $priv = $groups[$priv];
                }
            }

            $sql[] = 'convert(privs, char) in ('.join(', ', quote_list($privs)).')';
        }

        if ($limit !== '' || $offset) {
            $sql_limit = " LIMIT ".intval($offset).", ".($limit === '' ? PHP_INT_MAX : intval($limit));
        }

        $rs = safe_rows_start(
            "user_id as id, name, RealName as realname, email, privs, last_access",
            'txp_users',
            join(" AND ", $sql)." $sql_sort $sql_limit"
        );

        if ($rs && numRows($rs)) {
            $out = array();

            if ($thing === null && $form !== '') {
                $thing = fetch_form($form);
            }

            while ($a = nextRow($rs)) {
                $oldauthor = $thisauthor;
                $thisauthor = $a;
                $out[] = parse($thing);
                $thisauthor = $oldauthor;
            }

            unset($thisauthor);

            return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
        }

        return '';
    }
}

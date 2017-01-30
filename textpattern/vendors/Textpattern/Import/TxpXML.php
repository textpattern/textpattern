<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2017 The Textpattern Development Team
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

/**
 * Import Textpattern XML
 *
 * @since   4.7.0
 * @package Import
 */

namespace Textpattern\Import;

class TxpXML
{
    /**
     * Default allow import all data types
     * ToDo (maybe): css, form, page, users
     *
     * @var array
     */

    protected $importAllow = array('articles', 'category', 'section', 'link');


    /**
     * Constructor.
     *
     * @param string
     */

    public function __construct($importAllow = '')
    {
        $this->setImportAllow($importAllow);
    }

    /**
     *
     *
     * @param string
     */

    private function setImportAllow($importAllow)
    {
        if (!empty($importAllow)) {
            $this->importAllow = do_list($importAllow);
        }
    }

    /**
     * importXml
     *
     */

    public function importXml($data, $importAllow='')
    {
        $importAllow = empty($importAllow) ? $this->importAllow : do_list($importAllow);

        if ($xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA)) {
            foreach ((array)$xml->children() as $key => $children) {
                if (! in_array($key, $importAllow)) {
                    continue;
                }

                if ($key == 'articles') {
                    $this->importXmlArticles($children);
                    continue;
                }

                foreach ($children->item as $item) {
                    safe_insert('txp_'.$key, make_sql_set($item));
                }
            }
        } else {
            // error XML
        }
    }

    /**
     * importXmlArticles
     *
     */

    public function importXmlArticles($data)
    {
        // ToDo
    }



}

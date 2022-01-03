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
 * Import Textpattern XML
 *
 * @since   4.7.0
 * @package Import
 */

namespace Textpattern\Import;

class TxpXML
{
    /**
     * Default allow import all data types.
     *
     * @var array
     */

    protected $importAllow = array('articles', 'category', 'section', 'link', 'skin', 'css', 'form', 'page');

    /**
     * articleOptionalFields
     *
     * @var array
     */

    protected $articleOptionalFields = array('status', 'keywords', 'description', 'annotate', 'annotateinvite',
        'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5', 'custom_6', 'custom_7', 'custom_8', 'custom_9', 'custom_10');

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
     * setImportAllow
     *
     * @param string
     */

    public function setImportAllow($importAllow)
    {
        if (!empty($importAllow)) {
            $this->importAllow = do_list($importAllow);
        }
    }

    /**
     * setArticleOptionalFields
     *
     * @param string
     */

    public function setArticleOptionalFields($articleOptionalFields)
    {
        if (!empty($articleOptionalFields)) {
            $this->articleOptionalFields = do_list($articleOptionalFields);
        }
    }

    /**
     * importXml
     *
     * Allowed a mix of different data types in one xml file.
     * Import articles after the creation of all categories and sections.
     */

    public function importXml($data, $importAllow = '')
    {
        $importAllow = empty($importAllow) ? $this->importAllow : do_list($importAllow);

        if (\PHP_VERSION_ID < 80000) {
            $oldLoader = libxml_disable_entity_loader(true);
        }

        if ($xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA)) {
            $articles = array();
            foreach ((array)$xml->children() as $key => $children) {
                if (! in_array($key, $importAllow)) {
                    continue;
                }

                if ($key == 'articles') {
                    $articles[] = $children;
                    continue;
                }

                // Permit data to be imported into plugin tables.
                $keyPrefix = preg_match('/^[a-z]{3,3}\_/', $key) ? '' : 'txp_';

                foreach ($children->item as $item) {
                    safe_insert($keyPrefix.$key, $this->makeSqlSet($item));
                }
            }
            foreach ($articles as $a) {
                $this->importXmlArticles($a);
            }
        } else {
            // error XML
        }

        if (\PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader($oldLoader);
        }
    }

    /**
     * importXmlArticles
     */

    private function importXmlArticles($xml)
    {
        global $prefs, $txp_user;

        $textile = new \Netcarver\Textile\Parser('html5');

        foreach ($xml->article as $a) {
            $article = array();
            $article['status'] = STATUS_LIVE;
            $article['annotate'] = 1;
            $article['annotateinvite'] = $prefs['comments_default_invite'];

            foreach ($this->articleOptionalFields as $field) {
                if (!empty($a->$field)) {
                    $article[$field] = $a->$field;
                }
            }

            $article['Title']     = trim($a->title);
            $article['url_title'] = stripSpace($article['Title'], 1);
            $article['section']   = (isset($a->section) ? (string)$a->section : '');
            $article['Category1'] = (isset($a->category[0]) ? (string)$a->category[0] : '');
            $article['Category2'] = (isset($a->category[1]) ? (string)$a->category[1] : '');

            $article['Body'] = trim($this->replaceUrls(isset($a->body) ? $a->body : ''));
            $format = $a->body->attributes()->format;
            if ($format == 'textile') {
                $article['Body_html']       = $textile->parse($article['Body']);
                $article['textile_body']    = 1;
            } else {
                $article['Body_html']       = $article['Body'];
                $article['textile_body']    = 0;
            }

            $article['Excerpt'] = trim($this->replaceUrls(isset($a->excerpt) ? $a->excerpt : ''));
            $format = $a->excerpt->attributes()->format;
            if ($format == 'textile') {
                $article['Excerpt_html']    = $textile->parse($article['Excerpt']);
                $article['textile_excerpt'] = 1;
            } else {
                $article['Excerpt_html']    = $article['Excerpt'];
                $article['textile_excerpt'] = 0;
            }

            $article['AuthorID'] = $txp_user;
            $article['Posted'] = $article['LastMod'] = $article['feed_time'] = 'NOW()';
            $article['uid'] = md5(uniqid(rand(), true));

            $id = safe_insert('textpattern', $this->makeSqlSet($article));

            if ($id && !empty($a->comment)) {
                foreach ($a->comment as $c) {
                    $name = empty($c->name) ? $txp_user : $c->name;
                    $email = empty($c->email) ? stripSpace($name, 1).'@example.com' : $c->email;
                    safe_insert('txp_discuss', "
                        parentid        = '$id',
                        name            = '".doSlash($name)."',
                        email           = '".doSlash($email)."',
                        web             = '".doSlash($c->web)."',
                        message         = '".doSlash($c->message)."',
                        posted          = NOW(),
                        visible         = 1"
                    );
                }
                update_comments_count($id);
            }
        }
    }

    /**
     * replaceUrls
     * Used in importXmlArticles()
     */

    private function replaceUrls($txt)
    {
        global $adminurl, $siteurl;

        $adminpath = preg_replace('#^[^/]+#', '', $adminurl);
        $sitepath = preg_replace('#^[^/]+#', '', $siteurl);

        $txt = str_replace('adminurl', $adminpath, $txt);
        $txt = str_replace('siteurl', $sitepath, $txt);

        return $txt;
    }

    /**
     * Make sql set string from array
     */

    public function makeSqlSet($array)
    {
        $out = array();
        foreach (doSlash((array)$array) as $field=>$data) {
            if (in_array(trim($data), array('NOW()', 'NULL'), true)) {
                $out[]="`{$field}`=".trim($data);
            } else {
                $out[]="`{$field}`='$data'";
            }
        }

        return implode(', ', $out);
    }
}

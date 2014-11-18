<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2014 The Textpattern Development Team
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
 * Imports from Blogger.
 *
 * @package Admin\Import
 */

/**
 * Imports from a Blogger dump file.
 *
 * This function parses a file in the 'MovableType Import Format'.
 * The data isn't interpreted at all, just parsed into a
 * structure.
 *
 * This function supports importing comments and articles from
 * Blogger. Things such as statuses, sections, categories or keywords
 * do not carry over. Just body content and titles, basically.
 *
 * Returns results as a HTML formatted list.
 *
 * @param  string $file    Path to the dump file
 * @param  string $section The article section
 * @param  string $status  The article status
 * @param  string $invite  The comments invite
 * @return string HTML
 * @see    http://www.movabletype.org/docs/mtimport.html
 */

function doImportBLOGGER($file, $section, $status, $invite)
{
    $fp = fopen($file, 'r');

    if (!$fp) {
        return false;
    }

    // Keep some response on some part.
    $results = array();
    $multiline_type = '';
    $multiline_data = '';
    $state = 'metadata';
    $item = array();

    while (!feof($fp)) {
        $line = rtrim(fgets($fp, 8192));

        // The states suggested by the spec are inconsisent, but we'll do our
        // best to fake it.
        if ($line == '--------') {
            if ($state == 'multiline' and !empty($multiline_type)) {
                $item[$multiline_type][] = $multiline_data;
            }

            // End of an item, so we can process it.
            $results[] = import_blogger_item($item, $section, $status, $invite);
            $item = array();
            $state = 'metadata';
        } elseif ($line == '-----' and $state == 'metadata') {
            $state = 'multiline';
            $multiline_type = '';
        } elseif ($line == '-----' and $state == 'multiline') {
            if (!empty($multiline_type)) {
                $item[$multiline_type][] = $multiline_data;
            }

            $state = 'multiline';
            $multiline_type = '';
        } elseif ($state == 'metadata') {
            if (preg_match('/^([A-Z ]+):\s*(.*)$/', $line, $match)) {
                $item[$match[1]] = $match[2];
            }
        } elseif ($state == 'multiline' and empty($multiline_type)) {
            if (preg_match('/^([A-Z ]+):\s*$/', $line, $match)) {
                $multiline_type = $match[1];
                $multiline_data = array();
            }
        } elseif ($state == 'multiline') {
            // Here's where things get hinky. Rather than put the multiline
            // metadata before the field name, it goes after, with no clear
            // separation between metadata and data. And either the metadata
            // or data might be missing.
            if (empty($multiline_data['content']) and preg_match('/^([A-Z ]+):\s*(.*)$/', $line, $match)) {
                // Metadata within the multiline field.
                $multiline_data[$match[1]] = $match[2];
            } elseif (empty($multiline_data['content'])) {
                $multiline_data['content'] = ($line."\n");
            } else {
                $multiline_data['content'] .= ($line."\n");
            }
        }
    }

    // Catch the last item in the file, if it doesn't end with a separator.
    if (!empty($item)) {
        $results[] = import_blogger_item($item, $section, $status, $invite);
    }

    fclose($fp);

    return join('<br />', $results);
}

/**
 * Inserts a parsed item to the database.
 *
 * This import code is untested.
 *
 * @param  array  $item
 * @param  string $section
 * @param  int    $status
 * @param  string $invite
 * @return string A feedback message
 * @access private
 */

function import_blogger_item($item, $section, $status, $invite)
{
    if (empty($item)) {
        return;
    }

    $textile = new Textpattern_Textile_Parser();

    $title = $textile->TextileThis($item['TITLE'], 1);
    $url_title = stripSpace($title, 1);

    $body = $item['BODY'][0]['content'];
    $body_html = $textile->textileThis($body, 1);

    $date = strtotime($item['DATE']);
    $date = date('Y-m-d H:i:s', $date);

    if (isset($item['STATUS'])) {
        $post_status = ($item['STATUS'] == 'Draft' ? 1 : 4);
    } else {
        $post_status = $status;
    }

    // Blogger can use special characters on author names. Strip them and check
    // for realname.
    $authorid = safe_field('user_id', 'txp_users', "RealName = '".doSlash($item['AUTHOR'])."'");
    if (!$authorid) {
//        $authorid = safe_field('user_id', 'txp_users', 'order by user_id asc limit 1');

        // Add new authors.
        safe_insert('txp_users', "name='".doSlash(stripSpace($textile->TextileThis($item['AUTHOR'], 1)))."', RealName='".doSlash($item['AUTHOR'])."'");
    }

    if (!safe_field("ID", "textpattern", "Title = '".doSlash($title)."' AND Posted = '".doSlash($date)."'")) {
        $ok = safe_insert('textpattern',
            "Posted='".doSlash($date)."',".
            "LastMod='".doSlash($date)."',".
            "AuthorID='".doSlash($item['AUTHOR'])."',".
            "LastModID='".doSlash($item['AUTHOR'])."',".
            "Title='".doSlash($title)."',".
            "Body='".doSlash($body)."',".
            "Body_html='".doSlash($body_html)."',".
            "AnnotateInvite='".doSlash($invite)."',".
            "Status='".doSlash($post_status)."',".
            "Section='".doSlash($section)."',".
            "uid='".md5(uniqid(rand(), true))."',".
            "feed_time='".substr($date, 0, 10)."',".
            "url_title='".doSlash($url_title)."'");

        if ($ok) {
            $parentid = $ok;

            if (!empty($item['COMMENT'])) {
                foreach ($item['COMMENT'] as $comment) {
                    $comment_date = date('Y-m-d H:i:s', strtotime(@$comment['DATE']));
                    $comment_content = $textile->TextileThis(nl2br(@$comment['content']), 1);

                    // Check for Comments authors.
                    if (preg_match('/<a href="(.*)">(.*)<\/a>/', @$comment['AUTHOR'], $match)) {
                        @$comment['URL'] = $match[1];
                        @$comment['AUTHOR'] = $match[2];
                    }

                    if (!safe_field("discussid", "txp_discuss", "posted = '".doSlash($comment_date)."' AND message = '".doSlash($comment_content)."'")) {
                        safe_insert('txp_discuss',
                            "parentid='".doSlash($parentid)."',".
                            // Blogger places the link to user profile page as
                            // comment author.
                            "name='".doSlash(strip_tags(@$comment['AUTHOR']))."',".
//                            "email='".doSlash(@$item['EMAIL'])."',".
                            "web='".doSlash(@$comment['URL'])."',".
//                            "ip='".doSlash(@$item['IP'])."',".
                            "posted='".doSlash($comment_date)."',".
                            "message='".doSlash($comment_content)."',".
                            "visible='1'");
                    }
                }
            }
        }

        return $title;
    }

    return $title.' already imported';
}

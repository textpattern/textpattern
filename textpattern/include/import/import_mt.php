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
 * Imports from Movable Type dump file.
 *
 * @package Admin\Import
 */

/**
 * Imports articles and comments from a Movable Type dump file.
 *
 * This function parses a file in the 'MovableType Import Format'.
 * The data isn't interpreted at all, just parsed into a
 * structure.
 *
 * This function supports importing comments and articles
 * from MovableType.
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

function doImportMT($file, $section, $status, $invite)
{
    ini_set('auto_detect_line_endings', 1);

    $fp = fopen($file, 'r');

    if (!$fp) {
        return false;
    }

    // Keep some response on some part.
    $results = array();

    $state = 'metadata';
    $item = array();

    while (!feof($fp)) {
        $line = rtrim(fgets($fp, 8192));

        // The states suggested by the spec are inconsisent, but we'll do our
        // best to fake it.
        if ($line == '--------') {
            # End of an item, so we can process it
            $results[] = import_mt_item($item, $section, $status, $invite);
            $item = array();
            $state = 'metadata';
        } elseif ($line == '-----' and $state == 'metadata') {
            $state = 'multiline';
            $multiline_type = '';
        } elseif ($line == '-----' and $state == 'multiline') {
            if (!empty($multiline_type)) {
                $item[$multiline_type][] = import_mt_utf8($multiline_data);
            }

            $state = 'multiline';
            $multiline_type = '';
        } elseif ($state == 'metadata') {
            if (preg_match('/^([A-Z ]+):\s*(.*)$/', $line, $match)) {
                $item[$match[1]] = import_mt_utf8($match[2]);
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
                $multiline_data[$match[1]] = import_mt_utf8($match[2]);
            } elseif (empty($multiline_data['content'])) {
                $multiline_data['content'] = import_mt_utf8(($line."\n"));
            } else {
                $multiline_data['content'] .= import_mt_utf8(($line."\n"));
            }
        }
    }

    // Catch the last item in the file, if it doesn't end with a separator.
    if (!empty($item)) {
        $results[] = import_mt_item($item, $section, $status, $invite, $blogid);
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

function import_mt_item($item, $section, $status, $invite)
{
    global $prefs;

    if (empty($item)) {
        return;
    }

    include_once txpath.'/lib/classTextile.php';
    $textile = new Textile();

    $title = $textile->TextileThis($item['TITLE'], 1);

    // Nice non-English permlinks.
    $url_title = stripSpace($title, 1);

    $body = isset($item['BODY'][0]['content']) ? $item['BODY'][0]['content'] : '';

    if (isset($item['EXTENDED BODY'][0]['content'])) {
        $body .= "\n <!-- more -->\n\n".$item['EXTENDED BODY'][0]['content'];
    }

    $body_html = $textile->textileThis($body);

    $excerpt = isset($item['EXCERPT'][0]['content']) ? $item['EXCERPT'][0]['content'] : '';
    $excerpt_html = $textile->textileThis($excerpt);

    $date = safe_strtotime($item['DATE']);
    $date = strftime('%Y-%m-%d %H:%M:%S', $date);

    if (isset($item['STATUS'])) {
        $post_status = ($item['STATUS'] == 'Draft' ? 1 : 4);
    } else {
        $post_status = $status;
    }

    $category1 = @$item['PRIMARY CATEGORY'];

    if ($category1 and !safe_field("name", "txp_category", "name = '$category1'")) {
        safe_insert('txp_category', "name='".doSlash($category1)."', type='article', parent='root'");
    }

    $category2 = @$item['CATEGORY'];

    if ($category2 == $category1) {
        $category2 = '';
    }

    if ($category2 and !safe_field("name", "txp_category", "name = '$category2'")) {
        safe_insert('txp_category', "name='".doSlash($category2)."', type='article', parent='root'");
    }

    $keywords = isset($item['KEYWORDS'][0]['content']) ? $item['KEYWORDS'][0]['content'] : '';

    $annotate = !empty($item['ALLOW COMMENTS']);

    if (isset($item['ALLOW COMMENTS'])) {
        $annotate = intval($item['ALLOW COMMENTS']);
    } else {
        $annotate = (!empty($item['COMMENT']) or $prefs['comments_on_default']);
    }

    $authorid = safe_field('user_id', 'txp_users', "name = '".doSlash($item['AUTHOR'])."'");

    if (!$authorid) {
//        $authorid = safe_field('user_id', 'txp_users', 'order by user_id asc limit 1');

        // Add new authors.
        safe_insert('txp_users', "name='".doSlash($item['AUTHOR'])."'");
    }

    if (!safe_field("ID", "textpattern", "Title = '".doSlash($title)."' AND Posted = '".doSlash($date)."'")) {
        $parentid = safe_insert('textpattern',
            "Posted='".doSlash($date)."',".
            "LastMod='".doSlash($date)."',".
            "AuthorID='".doSlash($item['AUTHOR'])."',".
            "LastModID='".doSlash($item['AUTHOR'])."',".
            "Title='".doSlash($title)."',".
            "Body='".doSlash($body)."',".
            "Body_html='".doSlash($body_html)."',".
            "Excerpt='".doSlash($excerpt)."',".
            "Excerpt_html='".doSlash($excerpt_html)."',".
            "Category1='".doSlash($category1)."',".
            "Category2='".doSlash($category2)."',".
            "Annotate='".doSlash($annotate)."',".
            "AnnotateInvite='".doSlash($invite)."',".
            "Status='".doSlash($post_status)."',".
            "Section='".doSlash($section)."',".
            "Keywords='".doSlash($keywords)."',".
            "uid='".md5(uniqid(rand(), true))."',".
            "feed_time='".substr($date, 0, 10)."',".
            "url_title='".doSlash($url_title)."'");

        if (!empty($item['COMMENT']) and is_array($item['COMMENT'])) {
            foreach ($item['COMMENT'] as $comment) {
                $comment_date = strftime('%Y-%m-%d %H:%M:%S', safe_strtotime(@$comment['DATE']));
                $comment_content = $textile->TextileThis(nl2br(@$comment['content']), 1);

                if (!safe_field("discussid", "txp_discuss", "posted = '".doSlash($comment_date)."' AND message = '".doSlash($comment_content)."'")) {
                    safe_insert('txp_discuss',
                        "parentid='".doSlash($parentid)."',".
                        "name='".doSlash(@$comment['AUTHOR'])."',".
                        "email='".doSlash(@$comment['EMAIL'])."',".
                        "web='".doSlash(@$comment['URL'])."',".
                        "ip='".doSlash(@$comment['IP'])."',".
                        "posted='".doSlash($comment_date)."',".
                        "message='".doSlash($comment_content)."',".
                        "visible='1'");
                }
            }

            update_comments_count($parentid);
        }

        return $title;
    }

    return $title.' already imported';
}

/**
 * Tries to convert a string to UTF-8.
 *
 * @param  string $str The string
 * @return string
 * @access private
 */

function import_mt_utf8($str)
{
    if (is_callable('mb_detect_encoding')) {
        $enc = mb_detect_encoding($str, 'UTF-8,ASCII,ISO-8859-1');

        if ($enc and $enc != 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $enc);
        }
    }

    return $str;
}

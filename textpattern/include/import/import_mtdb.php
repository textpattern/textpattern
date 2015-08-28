<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2015 The Textpattern Development Team
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
 * Imports from MovableType database
 *
 * @package Admin\Import
 */

/**
 * Imports a MovableType database.
 *
 * This function imports users, categories and articles
 * from a MovableType installation.
 *
 * Returns results as a HTML formatted list.
 *
 * @param  string $mt_dblogin             The user
 * @param  string $mt_db                  The database
 * @param  string $mt_dbpass              The password
 * @param  string $mt_dbhost              The hostname
 * @param  string $blog_id                The MovableType blog ID
 * @param  string $insert_into_section    Article section
 * @param  int    $insert_with_status     Article status
 * @param  string $default_comment_invite Article comments invite
 * @return string HTML
 */

function doImportMTDB($mt_dblogin, $mt_db, $mt_dbpass, $mt_dbhost, $blog_id, $insert_into_section, $insert_with_status, $default_comment_invite)
{
    // Keep some response on some part.
    $results = array();

    // Avoid left joins.
    $authors_map = array();
    $categories_map = array();

    $mtlink = mysqli_connect($mt_dbhost, $mt_dblogin, $mt_dbpass, $mt_db);

    if (!$mtlink) {
        return 'mt database values don&#8217;t work. Please replace them and try again';
    }

    $results[] = 'connected to mt database. Importing Data';

    sleep(2);

    $a = mysqli_query($mtlink, "
        select
        author_id as user_id,
        author_nickname as name,
        author_name as RealName,
        author_email as email,
        author_password as pass
        from mt_author
    ");

    while ($b = mysqli_fetch_assoc($a)) {
        $authors[] = $b;
    }

    $a = mysqli_query($mtlink, "
        select
        mt_entry.entry_id as ID,
        mt_entry.entry_text as Body,
        mt_entry.entry_text_more as Body2,
        mt_entry.entry_title as Title,
        mt_entry.entry_excerpt as Excerpt,
        mt_entry.entry_keywords as Keywords,
        mt_entry.entry_created_on as Posted,
        mt_entry.entry_modified_on as LastMod,
        mt_entry.entry_author_id as AuthorID
        from mt_entry
        where entry_blog_id = '$blog_id'
    ");

    $results[] = mysqli_error($mtlink);

    while ($b = mysqli_fetch_assoc($a)) {
        $cat = mysqli_query($mtlink, "select placement_category_id as category_id from mt_placement where placement_entry_id='{$b['ID']}'");
        while ($cat_id = mysqli_fetch_row($cat)) {
            $categories[] = $cat_id[0];
        }

        if (!empty($categories[0])) {
            $b['Category1'] = $categories[0];
        }

        if (!empty($categories[1])) {
            $b['Category2'] = $categories[1];
        }

        unset($categories);

        // Trap comments for each article.
        $comments = array();

        $q = "
            select
            mt_comment.comment_id as discussid,
            mt_comment.comment_ip as ip,
            mt_comment.comment_author as name,
            mt_comment.comment_email as email,
            mt_comment.comment_url as web,
            mt_comment.comment_text as message,
            mt_comment.comment_created_on as posted
            from mt_comment where comment_blog_id = '$blog_id' AND comment_entry_id='{$b['ID']}'
        ";

        $c = mysqli_query($mtlink, $q);

        while ($d = mysqli_fetch_assoc($c)) {
            $comments[] = $d;
        }

        // Attach comments to article.
        $b['comments'] = $comments;
        unset($comments);

        // Article finished.
        $articles[] = $b;
    }

    $a = mysqli_query($mtlink, "
        select category_id,category_label from mt_category where category_blog_id='{$blog_id}'
    ");

    while ($b = mysqli_fetch_assoc($a)) {
        $categories_map[$b['category_id']] = $b['category_label'];
    }

    mysqli_close($mtlink);

    // Yes, we have to make a new connection, otherwise doArray complains.
    $DB = new DB;

    include txpath.'/lib/classTextile.php';

    $textile = new Textile;

    if (!empty($authors)) {
        foreach ($authors as $author) {
            extract($author);
            $name = (empty($name)) ? $RealName : $name;
            $authors_map[$user_id] = $name;

            $authorid = safe_field('user_id', 'txp_users', "name = '".doSlash($name)."'");

            if (!$authorid) {
                // Add new authors.
                $q = safe_insert("txp_users", "
                    name     = '".doSlash($RealName)."',
                    email    = '".doSlash($email)."',
                    pass     = '".doSlash(txp_hash_password($pass))."',
                    RealName = '".doSlash($RealName)."',
                    privs='1'"
                );

                if ($q) {
                    $results[] = 'inserted '.$RealName.' into txp_users';
                } else {
                    $results[] = mysqli_error($DB->link);
                }
            }
        }
    }

    if (!empty($categories_map)) {
        foreach ($categories_map as $category) {
            $category = doSlash($category);
            $rs = safe_row('id', 'txp_category', "name='$category' and type='article'");

            if (!$rs) {
                $q = safe_insert("txp_category", "name='$category',type='article',parent='root'");

                if ($q) {
                    $results[] = 'inserted '.stripslashes($category).' into txp_category';
                } else {
                    $results[] = mysqli_error($DB->link);
                }
            }
        }
    }

    if (!empty($articles)) {
        foreach ($articles as $article) {
            extract($article);
            $Body .= (trim($Body2)) ? "\n\n".$Body2 : '';

            $Body_html = $textile->textileThis($Body);
            $Excerpt_html = $textile->textileThis($Excerpt);
            $Title = $textile->textileThis($Title, 1);
            $Category1 = (!empty($Category1)) ? doSlash($Category1) : '';
            $AuthorID = (!empty($authors_map[$AuthorID])) ? doSlash($authors_map[$AuthorID]) : '';

            $insertID = safe_insert("textpattern", "
                ID             = '$ID',
                Posted         = '$Posted',
                LastMod        = '$LastMod',
                Title          = '".doSlash($Title)."',
                Body           = '".doSlash($Body)."',
                Excerpt        = '".doSlash($Excerpt)."',
                Excerpt_html   = '".doSlash($Excerpt_html)."',
                Keywords       = '".doSlash($Keywords)."',
                Body_html      = '".doSlash($Body_html)."',
                AuthorID       = '$AuthorID',
                Category1      = '$Category1',
                AnnotateInvite = '".doSlash($default_comment_invite)."',
                Section        = '".doSlash($insert_into_section)."',
                uid            = '".md5(uniqid(rand(), true))."',
                feed_time      = '".substr($Posted, 0, 10)."',
                Status         = '$insert_with_status'
            ");

            if ($insertID) {
                $results[] = 'inserted MT entry '.strong($Title).
                    ' into Textpattern as article '.strong($insertID).'';

                // Do coment for article.
                if (!empty($comments) && is_array($comments)) {
                    foreach ($comments as $comment) {
                        extract($comment);
                        $message = nl2br($message);

                        $commentID = safe_insert("txp_discuss", "
                            discussid = $discussid,
                            parentid  = $insertID,
                            name      = '".doSlash($name)."',
                            email     = '".doSlash($email)."',
                            web       = '".doSlash($web)."',
                            message   = '".doSlash($message)."',
                            ip        = '$ip',
                            posted    = '$posted',
                            visible   = 1"
                        );

                        if ($commentID) {
                            $results[] = 'inserted MT comment '.$commentID.
                                ' for article '.$insertID.' into txp_discuss';
                        } else {
                            $results[] = mysqli_error($DB->link);
                        }
                    }
                }
            } else {
                $results[] = mysqli_error($DB->link);
            }
        }
    }

    return join('<br />', $results);
}

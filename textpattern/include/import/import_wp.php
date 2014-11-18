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
 * Import from WordPress.
 *
 * @package Admin\Import
 */

/**
 * Imports a WordPress database.
 *
 * This function imports users, categories, articles and
 * links from a WordPress installation.
 *
 * Returns results as a &lt;ul&gt; list.
 *
 * @param  string $b2dblogin              The user
 * @param  string $b2db                   The database
 * @param  string $b2dbpass               The password
 * @param  string $b2dbhost               The hostname
 * @param  string $wpdbprefix             The WordPress table prefix
 * @param  string $insert_into_section    Article section
 * @param  int    $insert_with_status     Article status
 * @param  string $default_comment_invite Article comments invite
 * @param  string $wpdbcharset            WordPress database charset
 * @return string HTML
 */

function doImportWP($b2dblogin, $b2db, $b2dbpass, $b2dbhost, $wpdbprefix, $insert_into_section, $insert_with_status, $default_comment_invite, $wpdbcharset)
{
    global $txpcfg;

    $b2link = mysql_connect($b2dbhost, $b2dblogin, $b2dbpass, true);

    if (!$b2link) {
        return 'WordPress database values don&#8217;t work. Go back, replace them and try again.';
    }

    mysql_select_db($b2db, $b2link);

    if (!mysql_query('SET NAMES '.doslash($wpdbcharset), $b2link)) {
        return 'WordPress database does not support the requested character set. Aborting.';
    }

    // Keep some response on some part.
    $results = array();
    $errors = array();

    $results[] = hed('Connected to WordPress database. Importing Data&#8230;', 1);

    /*
    export users
    */

    $users = array();

    $user_query = mysql_query("
        select
            ID as user_id,
            user_login as name,
            user_email as email,
            display_name as RealName
        from ".$wpdbprefix."users
    ", $b2link) or $errors[] = mysql_error();

    while ($user = mysql_fetch_array($user_query)) {
        $user_privs_query = mysql_query("
            select
                meta_value
            from ".$wpdbprefix."usermeta
            where user_id = ".$user['user_id']." and meta_key = '".$wpdbprefix."capabilities'
        ", $b2link) or $errors[] = mysql_error();

        $privs = unserialize(mysql_result($user_privs_query, 0));

        foreach ($privs as $key => $val) {
            // Convert the built-in WordPress roles to their Textpattern equivalent.
            switch ($key) {
                // Publisher.
                case 'administrator':
                    $user['privs'] = 1;
                    break;

                // Managing editor.
                case 'editor':
                    $user['privs'] = 2;
                    break;

                // Staff writer.
                case 'author':
                    $user['privs'] = 4;
                    break;

                // Freelancer.
                case 'contributor':
                    $user['privs'] = 5;
                    break;

                // None.
                case 'subscriber':
                default:
                    $user['privs'] = 0;
                    break;
            }
        }

        $users[] = $user;
    }

    /*
    export article and link categories
    */

    $categories = array();

    $category_query = mysql_query("
        select
            t.slug as name,
            t.name as title,
            tt.taxonomy as type,
            tt.parent as parent
        from ".$wpdbprefix."terms as t inner join ".$wpdbprefix."term_taxonomy as tt
            on(t.term_id = tt.term_id)
        order by field(tt.taxonomy, 'category', 'post_tag', 'link_category'), tt.parent asc, t.name asc
    ", $b2link) or $errors[] = mysql_error();

    while ($category = mysql_fetch_array($category_query)) {
        if ($category['parent'] != 0) {
            $category_parent_query = mysql_query("
                select
                    slug as name
                from ".$wpdbprefix."terms
                where term_id = '".doSlash($category['parent'])."'
            ", $b2link) or $errors[] = mysql_error();

            while ($parent = mysql_fetch_array($category_parent_query)) {
                $category['parent'] = $parent['name'];
            }
        } else {
            $category['parent'] = 'root';
        }

        switch ($category['type']) {
            case 'post_tag':
            case 'category':
                $category['type'] = 'article';
                break;
            case 'link_category':
                $category['type'] = 'link';
                break;
        }

        $categories[] = $category;
    }

    /*
    export articles - do not export post revisions from WordPress 2.6+
    */

    $article_query = mysql_query("
        select
            p.ID as ID,
            p.post_status as Status,
            p.post_date as Posted,
            p.post_modified as LastMod,
            p.post_title as Title,
            p.post_content as Body,
            p.comment_status as Annotate,
            p.comment_count as comments_count,
            p.post_name as url_title,
            u.user_login as AuthorID
        from ".$wpdbprefix."posts as p left join ".$wpdbprefix."users as u
            on u.ID = p.post_author
        where p.post_type = 'post'
        order by p.ID asc
    ", $b2link) or $errors[] = mysql_error();

    while ($article = mysql_fetch_array($article_query)) {
        // Convert WordPress article status to Textpattern equivalent.
        switch ($article['Status']) {
            case 'draft':
                $article['Status'] = 1;
                break;

            // Hidden.
            case 'private':
                $article['Status'] = 2;
                break;

            case 'pending':
                $article['Status'] = 3;
                break;

            // Live.
            case 'publish':
                $article['Status'] = 4;
                break;

            default:
                $article['Status'] = $insert_with_status;
                break;
        }

        // Convert WordPress comment status to Textpattern equivalent.
        switch ($article['Annotate']) {
            // On.
            case 'open':
                $article['Annotate'] = 1;
                break;

            // Off.
            case 'closed':
            case 'registered_only':
                $article['Annotate'] = 0;
                break;
        }

        // Article commments.
        $comments = array();

        $comment_query = mysql_query("
            select
                comment_author_IP as ip,
                comment_author as name,
                comment_author_email as email,
                comment_author_url as web,
                comment_content as message,
                comment_date as posted
            from ".$wpdbprefix."comments
            where comment_post_ID = '".$article['ID']."'
            order by comment_ID asc
        ", $b2link) or $errors[] = mysql_error();

        while ($comment = mysql_fetch_assoc($comment_query)) {
            $comments[] = $comment;
        }

        $article['comments'] = $comments;

        // Article categories.
        $article_categories = array();

        $article_category_query = mysql_query("
            select
                t.name as title,
                t.slug as name
            from ".$wpdbprefix."terms as t inner join ".$wpdbprefix."term_taxonomy as tt
                on(t.term_id = tt.term_id)
            inner join ".$wpdbprefix."term_relationships as tr
                on(tt.term_taxonomy_id = tr.term_taxonomy_id)
            where tr.object_id = '".$article['ID']."' and tt.taxonomy in('post_tag', 'category')
            order by tr.object_id asc, t.name asc
            limit 2;
        ", $b2link) or $errors[] = mysql_error();

        while ($category = mysql_fetch_array($article_category_query)) {
            $article_categories[] = $category;
        }

        $article['Category1'] = !empty($article_categories[0]) ? $article_categories[0]['name'] : '';
        $article['Category2'] = !empty($article_categories[1]) ? $article_categories[1]['name'] : '';

        // Article images.
        $article_images = array();

        $article_image_query = mysql_query("
        select
            guid
        from ".$wpdbprefix."posts
        where post_type = 'attachment' and post_mime_type like 'image/%' and post_parent=".$article['ID'], $b2link) or $errors[] = mysql_error();

        while ($image = mysql_fetch_array($article_image_query)) {
            $article_images[] = $image['guid'];
        }

        // Comma-separated image URLs preserve multiple attachments.
        // Note: If more than one image is attached, <txp:article_image /> will
        // not work out of the box.
        $article['Image'] = join(',', $article_images);

        $articles[] = $article;
    }

    /*
    export links
    */

    $links = array();

    $link_query = mysql_query("
        select
            link_id as id,
            link_name as linkname,
            link_description as description,
            link_updated as date,
            link_url as url
        from ".$wpdbprefix."links
        order by link_id asc
    ", $b2link) or $errors[] = mysql_error();

    while ($link = mysql_fetch_array($link_query)) {
        // Link categories.
        $link_categories = array();

        $link_category_query = mysql_query("
            select
                t.name as title,
                t.slug as name
            from ".$wpdbprefix."terms as t inner join ".$wpdbprefix."term_taxonomy as tt
                on(t.term_id = tt.term_id)
            inner join ".$wpdbprefix."term_relationships as tr
                on(tt.term_taxonomy_id = tr.term_taxonomy_id)
            where tr.object_id = '".$link['id']."' and tt.taxonomy = 'link_category'
            order by tr.object_id asc, t.name asc
        ", $b2link) or $errors[] = mysql_error();

        while ($category = mysql_fetch_array($link_category_query)) {
            $link['category'] = $category['name'];
        }

        $links[] = $link;
    }

    mysql_close($b2link);

    /*
    begin import
    */

    // Keep a handy copy of txpdb values.
    $txpdb      = $txpcfg['db'];
    $txpdblogin = $txpcfg['user'];
    $txpdbpass  = $txpcfg['pass'];
    $txpdbhost  = $txpcfg['host'];

    // Yes, we have to make a new connection, otherwise doArray complains.
    $DB = new DB;
    $txplink = &$DB->link;

    mysql_select_db($txpdb, $txplink);

    /*
    import users
    */

    if ($users) {
        include_once txpath.'/lib/txplib_admin.php';

        $results[] = hed('Imported Users:', 2).
            graf('Because WordPress uses a different password mechanism than Textpattern, you will need to reset each user&#8217;s password from <a href="index.php?event=admin">the Users tab</a>.').
            n.'<ul>';

        foreach ($users as $user) {
            extract($user);

            if (!safe_row('user_id', 'txp_users', "name = '".doSlash($name)."'")) {
                $pass = doSlash(generate_password(6));
                $nonce = doSlash(md5(uniqid(mt_rand(), true)));

                $rs = mysql_query("
                    insert into ".safe_pfx('txp_users')." set
                        name     = '".doSlash($name)."',
                        pass     = 'import_wp_unknown',
                        email    = '".doSlash($email)."',
                        RealName = '".doSlash($RealName)."',
                        privs    = ".$privs.",
                        nonce    = '".doSlash($nonce)."'
                ", $txplink) or $errors[] = mysql_error();

                if (mysql_insert_id()) {
                    $results[] = '<li>'.$name.' ('.$RealName.')</li>';
                }
            }
        }

        $results[] = '</ul>';
    }

    /*
    import categories
    */

    if ($categories) {
        $results[] = hed('Imported Categories:', 2).n.'<ul>';

        foreach ($categories as $category) {
            extract($category);

            if (!safe_row('id', 'txp_category', "name = '".doSlash($name)."' and type = '".doSlash($type)."' and parent = '".doSlash($parent)."'")) {
                $rs = mysql_query("
                    insert into ".safe_pfx('txp_category')." set
                        name   = '".doSlash($name)."',
                        title  = '".doSlash($title)."',
                        type   = '".doSlash($type)."',
                        parent = '".doSlash($parent)."'
                ", $txplink) or $errors[] = mysql_error();

                if (mysql_insert_id()) {
                    $results[] = '<li>'.$title.' ('.$type.')</li>';
                }
            }
        }

        rebuild_tree_full('article');
        rebuild_tree_full('link');

        $results[] = '</ul>';
    }

    /*
    import articles
    */

    if ($articles) {
        $results[] = hed('Imported Articles and Comments:', 2).n.'<ul>';

        $textile = new Textpattern_Textile_Parser;

        foreach ($articles as $article) {
            extract($article);

            // Ugly, really ugly way to workaround the slashes WordPress gotcha.
            $Body = str_replace('<!--more-->', '', $Body);
            $Body_html = $textile->textileThis($Body);

            // Can not use array slash due to way on which comments are selected.
            $rs = mysql_query("
                insert into ".safe_pfx('textpattern')." set
                    Posted         = '".doSlash($Posted)."',
                    LastMod        = '".doSlash($LastMod)."',
                    Title          = '".doSlash($textile->TextileThis($Title, 1))."',
                    url_title      = '".doSlash($url_title)."',
                    Body           = '".doSlash($Body)."',
                    Body_html      = '".doSlash($Body_html)."',
                    Image          = '".doSlash($Image)."',
                    AuthorID       = '".doSlash($AuthorID)."',
                    Category1      = '".doSlash($Category1)."',
                    Category2      = '".doSlash($Category2)."',
                    Section        = '$insert_into_section',
                    uid            = '".md5(uniqid(rand(), true))."',
                    feed_time      = '".substr($Posted, 0, 10)."',
                    Annotate       = '".doSlash($Annotate)."',
                    AnnotateInvite = '$default_comment_invite',
                    Status         = '".doSlash($Status)."'
            ", $txplink) or $errors[] = mysql_error();

            if ((int) $insert_id = mysql_insert_id($txplink)) {
                $results[] = '<li>'.$Title.'</li>';

                if (!empty($comments)) {
                    $inserted_comments = 0;

                    foreach ($comments as $comment) {
                        extract(array_slash($comment));

                        // The ugly workaround again.
                        $message = nl2br($message);

                        $rs = mysql_query("
                            insert into ".safe_pfx('txp_discuss')." set
                                parentid = '$insert_id',
                                name     = '".doSlash($name)."',
                                email    = '".doSlash($email)."',
                                web      = '".doSlash($web)."',
                                ip       = '".doSlash($ip)."',
                                posted   = '".doSlash($posted)."',
                                message  = '".doSlash($message)."',
                                visible  = 1
                        ", $txplink) or $results[] = mysql_error();

                        if (mysql_insert_id()) {
                            $inserted_comments++;
                        }
                    }

                    $results[] = '<li>- '.$inserted_comments.' of '.$comments_count.' comment(s)</li>';
                }
            }
        }

        $results[] = '</ul>';
    }

    /*
    import links
    */

    if ($links) {
        $results[] = hed('Imported Links:', 2).n.'<ul>';

        foreach ($links as $link) {
            extract($link);

            $rs = mysql_query("
                insert into ".safe_pfx('txp_link')." set
                    linkname    = '".doSlash($linkname)."',
                    linksort    = '".doSlash($linkname)."',
                    description = '".doSlash($description)."',
                    category    = '".doSlash($category)."',
                    date        = '".doSlash($date)."',
                    url         = '".doSlash($url)."'
            ", $txplink) or $errors[] = mysql_error();

            if (mysql_insert_id()) {
                $results[] = '<li>'.$linkname.'</li>';
            }
        }

        $results[] = '</ul>';
    }

    /*
    show any errors we encountered
    */

    if ($errors) {
        $results[] = hed('Errors Encountered:', 2).n.'<ul>';

        foreach ($errors as $error) {
            $results[] = '<li>'.$error.'</li>';
        }

        $results[] = '</ul>';
    }

    return join(n, $results);
}

/**
 * Unquotes a string or an array of values.
 *
 * @param  string|array $in The input value
 * @return mixed
 * @access private
 * @see    stripslashes()
 * @see    doArray()
 */

function undoSlash($in)
{
    return doArray($in, 'stripslashes');
}

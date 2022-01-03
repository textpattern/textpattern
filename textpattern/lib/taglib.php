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
 * Used for tag builder.
 *
 * Populates the tag selector.
 *
 * @ignore
 */

$article_tags = array(
    'permlink',
    'posted',
    'title',
    'body',
    'excerpt',
    'section',
    'category1',
    'category2',
    'article_image',
    'comments_invite',
    'author',
);

$link_tags = array(
    'link',
    'linkdesctitle',
    'link_name',
    'link_description',
    'link_category',
    'link_date',
);

$comment_tags = array(
    'comments',
    'comments_form',
    'comments_preview',
);

$comment_details_tags = array(
    'comment_permlink',
    'comment_name',
    'comment_email',
    'comment_web',
    'comment_time',
    'comment_message',
);

$comment_form_tags = array(
    'comment_name_input',
    'comment_email_input',
    'comment_web_input',
    'comment_message_input',
    'comment_remember',
    'comment_preview',
    'comment_submit',
);

$search_result_tags = array(
    'search_result_title',
    'search_result_excerpt',
    'search_result_date',
    'search_result_url',
);

$file_download_tags = array(
    'file_download_link',
    'file_download_name',
    'file_download_description',
    'file_download_category',
    'file_download_created',
    'file_download_modified',
    'file_download_size',
    'file_download_downloads',
);

$category_tags = array(
    'category',
    'if_category',
);

$section_tags = array(
    'section',
    'if_section',
);

$page_article_tags = array(
    'article',
    'article_custom',
);

$page_article_nav_tags = array(
    'prev_title',
    'next_title',
    'link_to_prev',
    'link_to_next',
    'older',
    'newer',
);

$page_nav_tags = array(
    'link_to_home',
    'section_list',
    'category_list',
    'popup',
    'recent_articles',
    'recent_comments',
    'related_articles',
    'search_input',
);

$page_xml_tags = array(
    'feed_link',
    'link_feed_link',
);

$page_misc_tags = array(
    'page_title',
    'css',
    'site_name',
    'site_slogan',
    'breadcrumb',
    'search_input',
    'email',
    'linklist',
    'password_protect',
    'output_form',
    'lang',
);

$page_file_tags = array(
    'file_download_list',
    'file_download',
    'file_download_link',
);

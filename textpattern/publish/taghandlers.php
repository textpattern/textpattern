<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2005 Dean Allen
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Collection of tag functions.
 *
 * @package Tag
 */

Txp::get('\Textpattern\Tag\Registry')
    ->register('page_title')
    ->register('css')
    ->register('image')
    ->register('thumbnail')
    ->register('output_form')
    ->register(array('\Textpattern\Tag\Syntax\Partial', 'renderYield'), 'yield')
    ->register(array('\Textpattern\Tag\Syntax\Partial', 'renderIfYield'), 'if_yield')
    ->register('feed_link')
    ->register('link_feed_link')
    ->register('linklist')
    ->register('tpt_link', 'link')
    ->register('linkdesctitle')
    ->register('link_name')
    ->register('link_url')
    ->register('link_author')
    ->register('link_description')
    ->register('link_date')
    ->register('link_category')
    ->register('link_id')
    ->register(array('\Textpattern\Tag\Syntax\Link', 'renderIfFirstLink'), 'if_first_link')
    ->register(array('\Textpattern\Tag\Syntax\Link', 'renderIfLastLink'), 'if_last_link')
    ->register('email')
    ->register('password_protect')
    ->register('recent_articles')
    ->register('recent_comments')
    ->register('related_articles')
    ->register('popup')
    ->register('category_list')
    ->register('section_list')
    ->register('search_input')
    ->register('search_term')
    ->register('link_to_next')
    ->register('link_to_prev')
    ->register('next_title')
    ->register('prev_title')
    ->register('site_name')
    ->register('site_slogan')
    ->register('link_to_home')
    ->register('newer')
    ->register('older')
    ->register('text')
    ->register('article_id')
    ->register('article_url_title')
    ->register('if_article_id')
    ->register('posted')
    ->register('expires')
    ->register('if_expires')
    ->register('if_expired')
    ->register('modified')
    ->register('comments_count')
    ->register('comments_invite')
    ->register('comments_form')
    ->register('comments_error')
    ->register('if_comments_error')
    ->register('comments')
    ->register('comments_preview')
    ->register('if_comments_preview')
    ->register('comment_permlink')
    ->register('comment_id')
    ->register('comment_name')
    ->register('comment_email')
    ->register('comment_web')
    ->register('comment_time')
    ->register('comment_message')
    ->register('comment_anchor')
    ->register(array('\Textpattern\Tag\Syntax\Authors', 'renderAuthors'), 'authors')
    ->register('author')
    ->register('author_email')
    ->register('if_author')
    ->register('if_article_author')
    ->register('body')
    ->register('title')
    ->register('excerpt')
    ->register('category1')
    ->register('category2')
    ->register('category')
    ->register('section')
    ->register('keywords')
    ->register('if_keywords')
    ->register('if_description')
    ->register('if_article_image')
    ->register('article_image')
    ->register('search_result_title')
    ->register('search_result_excerpt')
    ->register('search_result_url')
    ->register('search_result_date')
    ->register('search_result_count')
    ->register('image_index')
    ->register('image_display')
    ->register('images')
    ->register('image_info')
    ->register('image_url')
    ->register('image_author')
    ->register('image_date')
    ->register(array('\Textpattern\Tag\Syntax\Image', 'renderIfFirstImage'), 'if_first_image')
    ->register(array('\Textpattern\Tag\Syntax\Image', 'renderIfLastImage'), 'if_last_image')
    ->register('if_thumbnail')
    ->register('if_comments')
    ->register('if_comments_allowed')
    ->register('if_comments_disallowed')
    ->register('if_individual_article')
    ->register('if_article_list')
    ->register('meta_keywords')
    ->register('meta_description')
    ->register('meta_author')
    ->register('permlink')
    ->register('lang')
    ->register('breadcrumb')
    ->register('if_excerpt')
    ->register('if_search')
    ->register('if_search_results')
    ->register('if_category')
    ->register('if_article_category')
    ->register('if_first_category')
    ->register('if_last_category')
    ->register('if_section')
    ->register('if_article_section')
    ->register('if_first_section')
    ->register('if_last_section')
    ->register('php')
    ->register('txp_header', 'header')
    ->register('custom_field')
    ->register('if_custom_field')
    ->register('site_url')
    ->register('error_message')
    ->register('error_status')
    ->register('if_status')
    ->register('page_url')
    ->register('if_different')
    ->register('if_first_article')
    ->register('if_last_article')
    ->register('if_plugin')
    ->register('file_download_list')
    ->register('file_download')
    ->register('file_download_link')
    ->register('file_download_size')
    ->register('file_download_created')
    ->register('file_download_modified')
    ->register('file_download_id')
    ->register('file_download_name')
    ->register('file_download_category')
    ->register('file_download_author')
    ->register('file_download_downloads')
    ->register('file_download_description')
    ->register(array('\Textpattern\Tag\Syntax\File', 'renderIfFirstFile'), 'if_first_file')
    ->register(array('\Textpattern\Tag\Syntax\File', 'renderIfLastFile'), 'if_last_file')
    ->register('hide')
    ->register('rsd')
    ->register('variable')
    ->register('if_variable')
    ->register('article')
    ->register('article_custom')
    ->register('txp_die')
    ->register('txp_eval', 'evaluate')
    ->register('comments_help')
    ->register('comment_name_input')
    ->register('comment_email_input')
    ->register('comment_web_input')
    ->register('comment_message_input')
    ->register('comment_remember')
    ->register('comment_preview')
    ->register('comment_submit')
// Global attributes (false just removes unknown attribute warning)
    ->registerAttr(false, 'class, html_id, labeltag')
    ->registerAttr(true, 'not, txp-process, breakby, breakclass')
    ->registerAttr('txp_escape', 'escape')
    ->registerAttr('txp_wraptag', 'wraptag, label');

// -------------------------------------------------------------

function page_title($atts)
{
    global $parentid, $thisarticle, $q, $c, $author, $context, $s, $pg, $sitename;

    extract(lAtts(array(
        'separator' => ' | ',
    ), $atts));

    $appending = txpspecialchars($separator.$sitename);
    $parent_id = (int) $parentid;
    $pageStr = ($pg ? $separator.gTxt('page').' '.$pg : '');

    if ($parent_id) {
        $out = gTxt('comments_on').' '.escape_title(safe_field("Title", 'textpattern', "ID = $parent_id")).$appending;
    } elseif ($thisarticle['title']) {
        $out = escape_title($thisarticle['title']).$appending;
    } elseif ($q) {
        $out = gTxt('search_results').' '.gTxt('txt_quote_double_open').txpspecialchars($q).gTxt('txt_quote_double_close').$pageStr.$appending;
    } elseif ($c) {
        $out = txpspecialchars(fetch_category_title($c, $context)).$pageStr.$appending;
    } elseif ($s && $s != 'default') {
        $out = txpspecialchars(fetch_section_title($s)).$pageStr.$appending;
    } elseif ($author) {
        $out = txpspecialchars(get_author_name($author)).$pageStr.$appending;
    } elseif ($pg) {
        $out = gTxt('page').' '.$pg.$appending;
    } else {
        $out = txpspecialchars($sitename);
    }

    return $out;
}

// -------------------------------------------------------------

function css($atts)
{
    global $css, $doctype, $pretext;

    extract(lAtts(array(
        'format' => 'url',
        'media'  => 'screen',
        'name'   => $css,
        'rel'    => 'stylesheet',
        'theme'  => $pretext['skin'],
        'title'  => '',
    ), $atts));

    if (empty($name)) {
        $name = 'default';
    }

    if (has_handler('css.url')) {
        $url = callback_event('css.url', '', false, compact('name', 'theme'));
    } else {
        $url = hu.'css.php?n='.urlencode($name).'&t='.urlencode($theme);
    }

    if ($format == 'link') {
        return tag_void('link', array(
            'rel'   => $rel,
            'type'  => $doctype != 'html5' ? 'text/css' : '',
            'media' => $media,
            'title' => $title,
            'href'  => $url,
        ));
    }

    return txpspecialchars($url);
}

// -------------------------------------------------------------

function image($atts)
{
    extract(lAtts(array(
        'class'   => '',
        'escape'  => 'html',
        'html_id' => '',
        'id'      => '',
        'name'    => '',
        'width'   => '',
        'height'  => '',
        'style'   => '',
        'wraptag' => '',
    ), $atts));

    if ($imageData = imageFetchInfo($id, $name)) {
        extract($imageData);

        if ($escape == 'html') {
            $alt = txpspecialchars($alt);
            $caption = txpspecialchars($caption);
        }

        if ($width == '' && $w) {
            $width = $w;
        }

        if ($height == '' && $h) {
            $height = $h;
        }

        $out = '<img src="'.imagesrcurl($id, $ext).'" alt="'.$alt.'"';

        if ($html_id && !$wraptag) {
            $out .= ' id="'.txpspecialchars($html_id).'"';
        }

        if ($class && !$wraptag) {
            $out .= ' class="'.txpspecialchars($class).'"';
        }

        if ($style) {
            $out .= ' style="'.txpspecialchars($style).'"';
        }

        if ($width) {
            $out .= ' width="'.(int) $width.'"';
        }

        if ($height) {
            $out .= ' height="'.(int) $height.'"';
        }

        $out .= ' />';

        if ($wraptag) {
            return doTag($out, $wraptag, $class, '', $html_id);
        }

        return $out;
    }
}

// -------------------------------------------------------------

function thumbnail($atts)
{
    extract(lAtts(array(
        'class'    => '',
        'escape'   => 'html',
        'html_id'  => '',
        'height'   => '',
        'id'       => '',
        'link'     => 0,
        'link_rel' => '',
        'name'     => '',
        'poplink'  => 0, // Deprecated, 4.7
        'style'    => '',
        'wraptag'  => '',
        'width'    => '',
    ), $atts));

    if (isset($atts['poplink'])) {
        trigger_error(gTxt('deprecated_attribute', array('{name}' => 'poplink')), E_USER_NOTICE);
    }

    if ($imageData = imageFetchInfo($id, $name)) {
        extract($imageData);

        if ($thumbnail) {
            if ($escape == 'html') {
                $alt = txpspecialchars($alt);
                $caption = txpspecialchars($caption);
            }

            if ($width == '' && $thumb_w) {
                $width = $thumb_w;
            }

            if ($height == '' && $thumb_h) {
                $height = $thumb_h;
            }

            $out = '<img src="'.imagesrcurl($id, $ext, true).'" alt="'.$alt.'"';

            if ($html_id && !$wraptag) {
                $out .= ' id="'.txpspecialchars($html_id).'"';
            }

            if ($class && !$wraptag) {
                $out .= ' class="'.txpspecialchars($class).'"';
            }

            if ($style) {
                $out .= ' style="'.txpspecialchars($style).'"';
            }

            if ($width) {
                $out .= ' width="'.(int) $width.'"';
            }

            if ($height) {
                $out .= ' height="'.(int) $height.'"';
            }

            $out .= ' />';

            if ($link) {
                $attribs = '';

                if (!empty($link_rel)) {
                    $attribs .= " rel='".txpspecialchars($link_rel)."'";
                }

                $out = href($out, imagesrcurl($id, $ext), $attribs);
            } elseif ($poplink) {
                $out = '<a href="'.imagesrcurl($id, $ext).'"'.
                    ' onclick="window.open(this.href, \'popupwindow\', '.
                    '\'width='.$w.', height='.$h.', scrollbars, resizable\'); return false;">'.$out.'</a>';
            }

            if ($wraptag) {
                return doTag($out, $wraptag, $class, '', $html_id);
            }

            return $out;
        }
    }
}

// -------------------------------------------------------------

function output_form($atts, $thing = null)
{
    global $txp_atts, $yield;

    if (empty($atts['form'])) {
        trigger_error(gTxt('form_not_specified'));

        return '';
    }

    $form = $atts['form'];

    if (!empty($atts['txp-yield'])) {
        $txp_atts = null;
    } else {
        lAtts(array(
            'form' => '',
            'txp-yield' => ''
        ), $atts);
        $atts = array();
    }

    unset($atts['form'], $atts['txp-yield']);
    $atts += array('' => $thing ? parse($thing) : $thing);

    foreach ($atts as $name => $value) {
        if (!isset($yield[$name])) {
            $yield[$name] = array();
        }

        $yield[$name][] = $value;
    }

    $out = parse_form($form);

    foreach ($atts as $name => $value) {
        array_pop($yield[$name]);
    }

    return $out;
}

// -------------------------------------------------------------

function feed_link($atts, $thing = null)
{
    global $s, $c;

    extract(lAtts(array(
        'category' => $c,
        'flavor'   => 'rss',
        'format'   => 'a',
        'label'    => '',
        'limit'    => '',
        'section'  => ($s == 'default' ? '' : $s),
        'title'    => gTxt('rss_feed_title')
    ), $atts));

    $url = pagelinkurl(array(
        $flavor    => '1',
        'section'  => $section,
        'category' => $category,
        'limit'    => $limit,
    ));

    if ($flavor == 'atom') {
        $title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
    }

    $title = txpspecialchars($title);

    $type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

    if ($format == 'link') {
        return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
    }

    $txt = ($thing === null ? $label : parse($thing));

    $out = href($txt, $url, array(
        'type'  => $type,
        'title' => $title,
    ));

    return $out;
}

// -------------------------------------------------------------

function link_feed_link($atts)
{
    global $c;

    extract(lAtts(array(
        'category' => $c,
        'flavor'   => 'rss',
        'format'   => 'a',
        'label'    => '',
        'title'    => gTxt('rss_feed_title'),
        'wraptag'  => '',
        'class'    => __FUNCTION__
    ), $atts));

    $url = pagelinkurl(array(
        $flavor    => '1',
        'area'     => 'link',
        'category' => $category,
    ));

    if ($flavor == 'atom') {
        $title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
    }

    $title = txpspecialchars($title);

    $type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

    if ($format == 'link') {
        return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
    }

    $out = href($label, $url, array(
        'type'  => $type,
        'title' => $title,
    ));

    return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
}

// -------------------------------------------------------------

function linklist($atts, $thing = null)
{
    global $s, $c, $context, $thislink, $thispage, $pretext;

    extract(lAtts(array(
        'break'       => '',
        'category'    => '',
        'author'      => '',
        'realname'    => '',
        'auto_detect' => 'category, author',
        'class'       => __FUNCTION__,
        'form'        => 'plainlinks',
        'id'          => '',
        'pageby'      => '',
        'limit'       => 0,
        'offset'      => 0,
        'sort'        => 'linksort asc',
        'wraptag'     => '',
    ), $atts));

    $where = array();
    $filters = isset($atts['category']) || isset($atts['author']) || isset($atts['realname']);
    $context_list = (empty($auto_detect) || $filters) ? array() : do_list_unique($auto_detect);
    $pageby = ($pageby == 'limit') ? $limit : $pageby;

    if ($category) {
        $where[] = "category IN ('".join("','", doSlash(do_list_unique($category)))."')";
    }

    if ($id) {
        $where[] = "id IN ('".join("','", doSlash(do_list_unique($id)))."')";
    }

    if ($author) {
        $where[] = "author IN ('".join("','", doSlash(do_list_unique($author)))."')";
    }

    if ($realname) {
        $authorlist = safe_column("name", 'txp_users', "RealName IN ('".join("','", doArray(doSlash(do_list_unique($realname)), 'urldecode'))."')");
        if ($authorlist) {
            $where[] = "author IN ('".join("','", doSlash($authorlist))."')";
        }
    }

    // If no links are selected, try...
    if (!$where && !$filters) {
        foreach ($context_list as $ctxt) {
            switch ($ctxt) {
                case 'category':
                    // ...the global category in the URL.
                    if ($context == 'link' && !empty($c)) {
                        $where[] = "category = '".doSlash($c)."'";
                    }
                    break;
                case 'author':
                    // ...the global author in the URL.
                    if ($context == 'link' && !empty($pretext['author'])) {
                        $where[] = "author = '".doSlash($pretext['author'])."'";
                    }
                    break;
            }

            // Only one context can be processed.
            if ($where) {
                break;
            }
        }
    }

    if (!$where && $filters) {
        // If nothing matches, output nothing.
        return '';
    }

    if (!$where) {
        // If nothing matches, start with all links.
        $where[] = "1 = 1";
    }

    $where = join(" AND ", $where);

    // Set up paging if required.
    if ($limit && $pageby) {
        $grand_total = safe_count('txp_link', $where);
        $total = $grand_total - $offset;
        $numPages = ($pageby > 0) ? ceil($total/$pageby) : 1;
        $pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
        $pgoffset = $offset + (($pg - 1) * $pageby);

        // Send paging info to txp:newer and txp:older.
        $pageout['pg']          = $pg;
        $pageout['numPages']    = $numPages;
        $pageout['s']           = $s;
        $pageout['c']           = $c;
        $pageout['context']     = 'link';
        $pageout['grand_total'] = $grand_total;
        $pageout['total']       = $total;

        if (empty($thispage)) {
            $thispage = $pageout;
        }
    } else {
        $pgoffset = $offset;
    }

    $qparts = array(
        $where,
        'ORDER BY '.doSlash($sort),
        ($limit) ? 'LIMIT '.intval($pgoffset).', '.intval($limit) : '',
    );

    $rs = safe_rows_start("*, UNIX_TIMESTAMP(date) AS uDate", 'txp_link', join(' ', $qparts));

    if ($rs) {
        $count = 0;
        $last = numRows($rs);
        $out = array();

        while ($a = nextRow($rs)) {
            ++$count;
            $thislink = $a;
            $thislink['date'] = $thislink['uDate'];
            $thislink['is_first'] = ($count == 1);
            $thislink['is_last'] = ($count == $last);
            unset($thislink['uDate']);

            $out[] = ($thing) ? parse($thing) : parse_form($form);

            $thislink = '';
        }

        if ($out) {
            return doWrap($out, $wraptag, $break, $class);
        }
    }

    return '';
}

// -------------------------------------------------------------

// NOTE: tpt_ prefix used because link() is a PHP function. See publish.php.
function tpt_link($atts)
{
    global $thislink;

    extract(lAtts(array(
        'rel'  => '',
        'id'   => '',
        'name' => '',
    ), $atts));

    $rs = $thislink;
    $sql = array();

    if ($id) {
        $sql[] = "id = ".intval($id);
    } elseif ($name) {
        $sql[] = "linkname = '".doSlash($name)."'";
    }

    if ($sql) {
        $rs = safe_row("linkname, url", 'txp_link', implode(" AND ", $sql)." LIMIT 1");
    }

    if (!$rs) {
        trigger_error(gTxt('unknown_link'));

        return '';
    }

    return tag(
        txpspecialchars($rs['linkname']), 'a',
        ($rel ? ' rel="'.txpspecialchars($rel).'"' : '').
        ' href="'.txpspecialchars($rs['url']).'"'
    );
}

// -------------------------------------------------------------

function linkdesctitle($atts)
{
    global $thislink;

    assert_link();

    extract(lAtts(array(
        'rel' => '',
    ), $atts));

    $description = ($thislink['description'])
        ? ' title="'.txpspecialchars($thislink['description']).'"'
        : '';

    return tag(
        txpspecialchars($thislink['linkname']), 'a',
        ($rel ? ' rel="'.txpspecialchars($rel).'"' : '').
        ' href="'.doSpecial($thislink['url']).'"'.$description
    );
}

// -------------------------------------------------------------

function link_name($atts)
{
    global $thislink;

    assert_link();

    extract(lAtts(array(
        'escape' => null,
    ), $atts));

    return ($escape === null)
        ? txpspecialchars($thislink['linkname'])
        : $thislink['linkname'];
}

// -------------------------------------------------------------

function link_url()
{
    global $thislink;

    assert_link();

    return doSpecial($thislink['url']);
}

// -------------------------------------------------------------

function link_author($atts)
{
    global $thislink, $s;

    assert_link();

    extract(lAtts(array(
        'link'         => 0,
        'title'        => 1,
        'section'      => '',
        'this_section' => ''
    ), $atts));

    if ($thislink['author']) {
        $author_name = get_author_name($thislink['author']);
        $display_name = txpspecialchars(($title) ? $author_name : $thislink['author']);

        $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;

        $author = ($link)
            ? href($display_name, pagelinkurl(array('s' => $section, 'author' => $author_name, 'context' => 'link')))
            : $display_name;

        return $author;
    }
}

// -------------------------------------------------------------

function link_description($atts)
{
    global $thislink;

    assert_link();

    extract(lAtts(array(
        'escape'   => null
    ), $atts));

    if ($thislink['description']) {
        return ($escape === null) ?
            txpspecialchars($thislink['description']) :
            $thislink['description'];
    }
}

// -------------------------------------------------------------

function link_date($atts)
{
    global $thislink, $dateformat;

    assert_link();

    extract(lAtts(array(
        'format' => $dateformat,
        'gmt'    => '',
        'lang'   => '',
    ), $atts));

    return safe_strftime($format, $thislink['date'], $gmt, $lang);
}

// -------------------------------------------------------------

function link_category($atts)
{
    global $thislink;

    assert_link();

    extract(lAtts(array(
        'title'    => 0
    ), $atts));

    if ($thislink['category']) {
        $category = ($title)
            ? fetch_category_title($thislink['category'], 'link')
            : $thislink['category'];

        return $category;
    }
}

// -------------------------------------------------------------

function link_id()
{
    global $thislink;

    assert_link();

    return $thislink['id'];
}

// -------------------------------------------------------------

function email($atts, $thing = null)
{
    extract(lAtts(array(
        'email'    => '',
        'linktext' => gTxt('contact'),
        'title'    => '',
    ), $atts));

    if ($email) {
        if ($thing !== null) {
            $linktext = parse($thing);
        }

        // Obfuscate link text?
        if (is_valid_email($linktext)) {
            $linktext = eE($linktext);
        }

        return href(
            $linktext,
            eE('mailto:'.$email),
            ($title ? ' title="'.txpspecialchars($title).'"' : '')
        );
    }

    return '';
}

// -------------------------------------------------------------

function password_protect($atts, $thing = null)
{
    ob_start();

    extract(lAtts(array(
        'login' => null,
        'pass'  => null,
        'privs' => null,
    ), $atts));

    if ($pass === null) {
        $access = ($user = is_logged_in($login)) !== false && ($privs === null || in_list($user['privs'], $privs));
    } else {
        $au = serverSet('PHP_AUTH_USER');
        $ap = serverSet('PHP_AUTH_PW');

        // For PHP as (f)cgi, two rules in htaccess often allow this workaround.
        $ru = serverSet('REDIRECT_REMOTE_USER');

        if (!$au && !$ap && strpos($ru, 'Basic') === 0) {
            list($au, $ap) = explode(':', base64_decode(substr($ru, 6)));
        }

        $access = $au === $login && $ap === $pass;
    }

    if ($access === false && $pass !== null) {
        header('WWW-Authenticate: Basic realm="Private"');
    }

    if ($thing === null) {
        if ($access === false) {
            txp_die(gTxt('auth_required'), '401');
        }

        return '';
    }

    return parse($thing, $access);
}

// -------------------------------------------------------------

function recent_articles($atts)
{
    global $prefs;

    $atts = lAtts(array(
        'break'    => 'br',
        'category' => '',
        'class'    => __FUNCTION__,
        'label'    => gTxt('recent_articles'),
        'labeltag' => '',
        'limit'    => 10,
        'offset'   => 0,
        'section'  => '',
        'sort'     => 'Posted DESC',
        'wraptag'  => '',
        'no_widow' => @$prefs['title_no_widow'],
    ), $atts);

    $thing = '<txp:permlink><txp:title no_widow="'.($atts['no_widow'] ? '1' : '').'" /></txp:permlink>';
    unset($atts['no_widow']);

    return article_custom($atts, $thing);
}

// -------------------------------------------------------------

function recent_comments($atts, $thing = null)
{
    global $prefs;
    global $thisarticle, $thiscomment;

    extract(lAtts(array(
        'break'    => br,
        'class'    => __FUNCTION__,
        'form'     => '',
        'limit'    => 10,
        'offset'   => 0,
        'sort'     => 'posted DESC',
        'wraptag'  => '',
    ), $atts));

    $sort = preg_replace('/\bposted\b/', 'd.posted', $sort);
    $expired = ($prefs['publish_expired_articles']) ? '' : " AND (".now('expires')." <= t.Expires OR t.Expires IS NULL) ";

    $rs = startRows("SELECT d.name, d.email, d.web, d.message, d.discussid, UNIX_TIMESTAMP(d.Posted) AS time,
            t.ID AS thisid, UNIX_TIMESTAMP(t.Posted) AS posted, t.Title AS title, t.Section AS section, t.url_title
        FROM ".safe_pfx('txp_discuss')." AS d INNER JOIN ".safe_pfx('textpattern')." AS t ON d.parentid = t.ID
        WHERE t.Status >= ".STATUS_LIVE.$expired." AND d.visible = ".VISIBLE."
        ORDER BY ".doSlash($sort)."
        LIMIT ".intval($offset).", ".intval($limit));

    if ($rs) {
        $out = array();
        $old_article = $thisarticle;

        while ($c = nextRow($rs)) {
            if ($form === '' && $thing === null) {
                $out[] = href(
                    txpspecialchars($c['name']).' ('.escape_title($c['title']).')',
                    permlinkurl($c).'#c'.$c['discussid']
                );
            } else {
                $thiscomment['name'] = $c['name'];
                $thiscomment['email'] = $c['email'];
                $thiscomment['web'] = $c['web'];
                $thiscomment['message'] = $c['message'];
                $thiscomment['discussid'] = $c['discussid'];
                $thiscomment['time'] = $c['time'];

                // Allow permlink guesstimation in permlinkurl(), elsewhere.
                $thisarticle['thisid'] = $c['thisid'];
                $thisarticle['posted'] = $c['posted'];
                $thisarticle['title'] = $c['title'];
                $thisarticle['section'] = $c['section'];
                $thisarticle['url_title'] = $c['url_title'];

                if ($thing === null && $form !== '') {
                    $out[] = parse_form($form);
                } else {
                    $out[] = parse($thing);
                }
            }
        }

        if ($out) {
            unset($GLOBALS['thiscomment']);
            $thisarticle = $old_article;

            return doWrap($out, $wraptag, $break, $class);
        }
    }

    return '';
}

// -------------------------------------------------------------

function related_articles($atts, $thing = null)
{
    global $thisarticle, $prefs;

    assert_article();

    $atts = lAtts(array(
        'break'    => br,
        'class'    => __FUNCTION__,
        'form'     => '',
        'limit'    => 10,
        'offset'   => 0,
        'match'    => 'Category1,Category2',
        'no_widow' => @$prefs['title_no_widow'],
        'section'  => '',
        'sort'     => 'Posted DESC',
        'wraptag'  => '',
    ), $atts);

    $match = array_intersect(do_list_unique(strtolower($atts['match'])), array_merge(array('category1', 'category2', 'author', 'keywords'), getCustomFields()));
    $categories = $cats = array();

    foreach ($match as $cf) {
        switch ($cf) {
            case 'category1':
            case 'category2':
                if (!empty($thisarticle[$cf])) {
                    $cats[] = $thisarticle[$cf];
                }

                $categories[] = ucwords($cf);
                break;
            case 'author':
                $atts['author'] = $thisarticle['authorid'];
                break;
            default:
                if (empty($thisarticle[$cf])) {
                    return '';
                }

                $atts[$cf] = $thisarticle[$cf];
                break;
        }
    }

    if (!empty($cats)) {
        $atts['category'] = implode(',', $cats);
    } elseif ($categories) {
        return '';
    }

    $atts['match'] = implode(',', $categories);
    $atts['exclude'] = $thisarticle['thisid'];

    if ($atts['form'] === '' && $thing === null) {
        $thing = '<txp:permlink><txp:title no_widow="'.($atts['no_widow'] ? '1' : '').'" /></txp:permlink>';
    }

    unset($atts['no_widow']);

    return article_custom($atts, $thing);
}

// -------------------------------------------------------------

function popup($atts)
{
    global $s, $c, $permlink_mode;

    extract(lAtts(array(
        'label'        => gTxt('browse'),
        'wraptag'      => '',
        'class'        => '',
        'section'      => '',
        'this_section' => 0,
        'type'         => 'category',
    ), $atts));

    $type = substr($type, 0, 1);

    if ($type == 's') {
        $rs = safe_rows_start("name, title", 'txp_section', "name != 'default' ORDER BY name");
    } else {
        $rs = safe_rows_start("name, title", 'txp_category', "type = 'article' AND name != 'root' ORDER BY name");
    }

    if ($rs) {
        $out = array();

        $current = ($type == 's') ? $s : $c;

        $sel = '';
        $selected = false;

        while ($a = nextRow($rs)) {
            extract($a);

            if ($name == $current) {
                $sel = ' selected="selected"';
                $selected = true;
            }

            $out[] = '<option value="'.$name.'"'.$sel.'>'.txpspecialchars($title).'</option>';

            $sel = '';
        }

        if ($out) {
            $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;

            $out = n.'<select name="'.txpspecialchars($type).'" onchange="submit(this.form);">'.
                n.t.'<option value=""'.($selected ? '' : ' selected="selected"').'>&#160;</option>'.
                n.t.join(n.t, $out).
                n.'</select>';

            if ($label) {
                $out = $label.br.$out;
            }

            if ($wraptag) {
                $out = doTag($out, $wraptag, $class);
            }

            if (($type == 's' || $permlink_mode == 'messy')) {
                $action = hu;
                $his = ($section !== '') ? n.hInput('s', $section) : '';
            } else {
                // Clean URLs for category popup.
                $action = pagelinkurl(array('s' => $section));
                $his = '';
            }

            return '<form method="get" action="'.$action.'">'.
                '<div>'.
                $his.
                n.$out.
                n.'<noscript><div><input type="submit" value="'.gTxt('go').'" /></div></noscript>'.
                n.'</div>'.
                n.'</form>';
        }
    }
}

// -------------------------------------------------------------

// Output href list of site categories.
function category_list($atts, $thing = null)
{
    global $s, $c, $thiscategory;
    static $cache = array(), $level = 0;

    extract(lAtts(array(
        'active_class' => '',
        'break'        => br,
        'categories'   => '',
        'class'        => __FUNCTION__,
        'exclude'      => '',
        'form'         => '',
        'html_id'      => '',
        'label'        => '',
        'labeltag'     => '',
        'parent'       => '',
        'section'      => '',
        'children'     => 1,
        'sort'         => '',
        'this_section' => 0,
        'type'         => 'article',
        'wraptag'      => '',
        'limit'        => '',
        'offset'       => '',
    ), $atts));

    $categories = $categories === true ? array(isset($thiscategory['name']) ? $thiscategory['name'] : ($c ? $c : 'root')) : do_list_unique($categories);
    $roots = ($parent === true ? array(isset($thiscategory['name']) ? $thiscategory['name'] : ($c ? $c : 'root')) : do_list_unique($parent)) or $roots = $categories or $roots = array('root');
    $level++;
    $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;
    $multiple = count($roots) > 1;
    $root = implode(',', $roots);
    $children = $children === true ? PHP_INT_MAX : intval(is_numeric($children) ? $children : !empty($children));
    $sql_query = "type = '".doSlash($type)."'".($sort ? ' order by '.doSlash($sort) : ($categories ? " order by FIELD(name, ".implode(',', quote_list($categories)).")": ''));
    $sql_limit = $limit !== '' || $offset ? "LIMIT ".intval($offset).", ".($limit === '' || $limit === true ? PHP_INT_MAX : intval($limit)) : '';
    $exclude = $exclude ? ($exclude === true ? $roots : do_list_unique($exclude)) : array();
    $sql_exclude = $exclude && $sql_limit ? " and name not in(".implode(',', quote_list($exclude)).")" : '';
    $nocache = !$children || $sql_limit || $children == $level;
    $hash = md5($nocache ? uniqid() : $sql_query);

    if (!isset($cache[$hash])) {
        $cache[$hash] = array();
    }

    if (!isset($cache[$hash][$root]) || !$multiple && $root != 'root' && empty($cache[$hash][$root][$root])) {
        $cache[$hash][$root] = array();

        if (!$children || !in_array('root', $roots)) {
            $cats = safe_rows('name, parent, title, description, lft, rgt', 'txp_category', "name IN (".implode(',', quote_list($roots)).") and $sql_query") or $cats = array();
            $retrieve = false;
            $between = array();

            foreach ($cats as $cat) {
                extract($cat);
                $name = doSlash($name);
                $between[] = $children ? "lft>=$lft and rgt<=$rgt" : "name='$name' or parent='$name'";

                if ($rgt - $lft > 1) {
                    $retrieve = true;
                }
            }

            $cats = $retrieve ? safe_rows('name, parent, title, description', 'txp_category', "name!='root' $sql_exclude and (".implode(' or ', $between).") and $sql_query $sql_limit") : $cats;
        } else {
            $cats = safe_rows('name, parent, title, description', 'txp_category', "name !='root' $sql_exclude and $sql_query $sql_limit");
        }

        foreach ($cats as $cat) {
            extract($cat);
            $node = $children == $level ? $root : $name;

            if (!isset($cache[$hash][$node])) {
                $cache[$hash][$node] = array();
            }

            $cache[$hash][$node][$name] = $cat;

            if ($children != $level) {
                if ($multiple && in_array($name, $roots)) {
                    $cache[$hash][$root][$name] = $cat;
                }

                if (!isset($cache[$hash][$parent])) {
                    $cache[$hash][$parent] = array();
                }

                $cache[$hash][$parent][$name] = $cat;

                if ($multiple && in_array($parent, $roots)) {
                    $cache[$hash][$root][$name] = $cat;
                }
            }
        }
    }

    $oldcategory = isset($thiscategory) ? $thiscategory : null;
    $out = array();
    $count = 0;
    $last = count($cache[$hash][$root]);

    foreach ($cache[$hash][$root] as $name => $thiscategory) {
        if (!in_array($name, $exclude) && (!$categories || in_array($name, $categories))) {
            $count++;

            if (!isset($thing) && !$form) {
                extract($thiscategory);
                $out[] = tag(txpspecialchars($title), 'a',
                    (($active_class && (0 == strcasecmp($c, $name))) ? ' class="'.txpspecialchars($active_class).'"' : '').
                    ' href="'.pagelinkurl(array('s' => $section, 'c' => $name, 'context' => $type)).'"'
                ).(
                    isset($cache[$hash][$name]) && $children > $level && count($cache[$hash][$name]) > 1
                    ? category_list(array('parent' => $name, 'exclude' => implode(',', array_merge($exclude, array($name))), 'label' => '', 'html_id' => '') + $atts)
                    : ''
                );
            } else {
                $thiscategory['type'] = $type;
                $thiscategory['is_first'] = ($count == 1);
                $thiscategory['is_last'] = ($count == $last);

                if (isset($atts['section'])) {
                    $thiscategory['section'] = $section;
                }

                $out[] = $form ? parse_form($form) : parse($thing);
            }
        } else {
            $last--;
        }
    }

    $thiscategory = $oldcategory;
    $level--;

    if ($nocache || $level <= 0) {
        unset($cache[$hash]);
    }

    return $out ? ($label ? doLabel($label, $labeltag) : '').doWrap($out, $wraptag, compact('break', 'class', 'html_id')) : '';
}

// -------------------------------------------------------------

// Output href list of site sections.
function section_list($atts, $thing = null)
{
    global $sitename, $s, $thissection;

    extract(lAtts(array(
        'active_class'    => '',
        'break'           => br,
        'class'           => __FUNCTION__,
        'default_title'   => $sitename,
        'exclude'         => '',
        'form'            => '',
        'html_id'         => '',
        'include_default' => '',
        'sections'        => '',
        'sort'            => '',
        'wraptag'         => '',
        'offset'          => '',
        'limit'           => '',
    ), $atts));

    $sql_limit = '';
    $sql_sort = doSlash($sort);
    $sql = array();
    $sql[] = 1;

    if ($limit !== '' || $offset) {
        $sql_limit = " LIMIT ".intval($offset).", ".($limit === '' ? PHP_INT_MAX : intval($limit));
    }

    if ($sections) {
        if ($include_default) {
            $sections .= ', default';
        }

        $sections = join(',', quote_list(do_list_unique($sections)));
        $sql[] = "name IN ($sections)";

        if (!$sql_sort) {
            $sql_sort = "FIELD(name, $sections)";
        }
    } else {
        if ($exclude) {
            $exclude = join(',', quote_list(do_list_unique($exclude)));
            $sql[] = "name NOT IN ($exclude)";
        }

        if (!$include_default) {
            $sql[] = "name != 'default'";
        }

        if (!$sql_sort) {
            $sql_sort = "name ASC";
        }
    }

    if ($include_default) {
        $sql_sort = "name != 'default', ".$sql_sort;
    }

    $rs = safe_rows_start(
        "name, title, description",
        'txp_section',
        join(" AND ", $sql)." ORDER BY ".$sql_sort.$sql_limit
    );

    if ($rs && $last = numRows($rs)) {
        $out = array();
        $count = 0;

        if (isset($thissection)) {
            $old_section = $thissection;
        }

        while ($a = nextRow($rs)) {
            ++$count;
            extract($a);

            if ($name == 'default') {
                $title = $default_title;
            }

            if ($form === '' && $thing === null) {
                $url = pagelinkurl(array('s' => $name));

                $out[] = tag(txpspecialchars($title), 'a',
                    (($active_class && (0 == strcasecmp($s, $name))) ? ' class="'.txpspecialchars($active_class).'"' : '').
                    ' href="'.$url.'"'
                );
            } else {
                $thissection = array(
                    'name'        => $name,
                    'title'       => $title,
                    'description' => $description,
                    'is_first'    => ($count == 1),
                    'is_last'     => ($count == $last),
                );

                if ($thing === null && $form !== '') {
                    $out[] = parse_form($form);
                } else {
                    $out[] = parse($thing);
                }
            }
        }

        $thissection = isset($old_section) ? $old_section : null;

        if ($out) {
            return doWrap($out, $wraptag, compact('break', 'class', 'html_id'));
        }
    }

    return '';
}

// -------------------------------------------------------------

// Input form for search queries.
function search_input($atts)
{
    global $q, $permlink_mode, $doctype;

    extract(lAtts(array(
        'form'    => 'search_input',
        'wraptag' => 'p',
        'class'   => __FUNCTION__,
        'size'    => '15',
        'html_id' => '',
        'label'   => gTxt('search'),
        'button'  => '',
        'section' => '',
        'match'   => 'exact',
    ), $atts));

    if ($form && !array_diff_key($atts, array('form' => true))) {
        $rs = fetch_form($form);

        if ($rs) {
            return parse($rs);
        }
    }

    $h5 = ($doctype == 'html5');
    $sub = (!empty($button)) ? '<input type="submit" value="'.txpspecialchars($button).'" />' : '';
    $id =  (!empty($html_id)) ? ' id="'.txpspecialchars($html_id).'"' : '';
    $out = fInput($h5 ? 'search' : 'text', 'q', $q, '', '', '', $size, '', '', false, $h5);
    $out = (!empty($label)) ? txpspecialchars($label).br.$out.$sub : $out.$sub;
    $out = ($match === 'exact') ? $out : hInput('m', txpspecialchars($match)).$out;
    $out = ($wraptag) ? doTag($out, $wraptag, $class) : $out;

    if (!$section) {
        return '<form method="get" action="'.hu.'"'.$id.'>'.
            n.$out.
            n.'</form>';
    }

    if ($permlink_mode != 'messy') {
        return '<form method="get" action="'.pagelinkurl(array('s' => $section)).'"'.$id.'>'.
            n.$out.
            n.'</form>';
    }

    return '<form method="get" action="'.hu.'"'.$id.'>'.
        n.hInput('s', $section).
        n.$out.
        n.'</form>';
}

// -------------------------------------------------------------

function search_term($atts)
{
    global $q;

    if (empty($q)) {
        return '';
    }

    extract(lAtts(array(
        'escape' => 'html', // Deprecated in 4.5.0.
    ), $atts));

    if (isset($atts['escape'])) {
        trigger_error(gTxt('deprecated_attribute', array('{name}' => 'escape')), E_USER_NOTICE);
    }

    // TODO: Remove deprecated attribute 'escape'.
    return ($escape == 'html' ? txpspecialchars($q) : $q);
}

// -------------------------------------------------------------

// Link to next/prev article, if it exists.
function link_to($atts, $thing = null, $target = null)
{
    global $thisarticle;

    if (!in_array($target, array('next', 'prev'))) {
        return '';
    }

    if (!assert_article()) {
        return '';
    }

    extract(lAtts(array(
        'form' => '',
        'link' => 1,
        'showalways' => 0,
    ), $atts));

    if (is_array($thisarticle)) {
        if (!isset($thisarticle[$target])) {
            $thisarticle = $thisarticle + getNextPrev();
        }

        if ($thisarticle[$target] !== false) {
            $url = permlinkurl($thisarticle[$target]);

            if ($form || $thing !== null) {
                $oldarticle = $thisarticle;
                populateArticleData($thisarticle[$target]);
                $thisarticle['is_first'] = $thisarticle['is_last'] = true;
                $thing = $form ? parse_form($form) : parse($thing);
                $target_title = escape_title($thisarticle[$target]['Title']);
                $thisarticle = $oldarticle;

                return $link ? href(
                    $thing,
                    $url,
                    ($target_title != $thing ? ' title="'.$target_title.'"' : '').
                    ' rel="'.$target.'"'
                ) : $thing;
            }

            return $url;
        }
    }

    return ($showalways) ? parse($thing) : '';
}

// -------------------------------------------------------------

// Link to next article, if it exists.
function link_to_next($atts, $thing = null)
{
    return link_to($atts, $thing, 'next');
}

// -------------------------------------------------------------

// Link to previous article, if it exists.
function link_to_prev($atts, $thing = null)
{
    return link_to($atts, $thing, 'prev');
}

// -------------------------------------------------------------

function next_title()
{
    global $thisarticle, $is_article_list;

    if (!assert_article()) {
        return $is_article_list ? '' : null;
    }

    if (!isset($thisarticle['next'])) {
        $thisarticle = $thisarticle + getNextPrev();
    }

    if ($thisarticle['next'] !== false) {
        return escape_title($thisarticle['next']['Title']);
    } else {
        return '';
    }
}

// -------------------------------------------------------------

function prev_title()
{
    global $thisarticle, $is_article_list;

    if (!assert_article()) {
        return $is_article_list ? '' : null;
    }

    if (!isset($thisarticle['prev'])) {
        $thisarticle = $thisarticle + getNextPrev();
    }

    if ($thisarticle['prev'] !== false) {
        return escape_title($thisarticle['prev']['Title']);
    } else {
        return '';
    }
}

// -------------------------------------------------------------

function site_name()
{
    global $sitename;

    return txpspecialchars($sitename);
}

// -------------------------------------------------------------

function site_slogan()
{
    global $site_slogan;

    return txpspecialchars($site_slogan);
}

// -------------------------------------------------------------

function link_to_home($atts, $thing = null)
{
    extract(lAtts(array(
        'class' => false,
    ), $atts));

    if ($thing) {
        $class = ($class) ? ' class="'.txpspecialchars($class).'"' : '';

        return href(
            parse($thing),
            hu,
            $class.
            ' rel="home"'
        );
    }

    return hu;
}

// -------------------------------------------------------------

function newer($atts, $thing = null)
{
    global $thispage, $pretext, $m, $is_article_list;

    if (empty($thispage)) {
        return $is_article_list ? postpone_process() : '';
    }

    extract(lAtts(array(
        'showalways' => 0,
        'title'      => '',
        'escape'     => 'html',
    ), $atts));

    $numPages = $thispage['numPages'];
    $pg = $thispage['pg'];

    if ($numPages > 1 && $pg > 1 && $pg <= $numPages) {
        $nextpg = ($pg - 1 == 1) ? '' : ($pg - 1);

        // Author URLs should use RealName, rather than username.
        if (!empty($pretext['author'])) {
            $author = get_author_name($pretext['author']);
        } else {
            $author = '';
        }

        $url = pagelinkurl(array(
            'month'   => @$pretext['month'],
            'pg'      => $nextpg,
            's'       => @$pretext['s'],
            'c'       => @$pretext['c'],
            'context' => @$pretext['context'],
            'q'       => @$pretext['q'],
            'm'       => @$m,
            'author'  => $author,
        ));

        if ($thing) {
            if ($escape == 'html') {
                $title = escape_title($title);
            }

            return href(
                parse($thing),
                $url,
                (empty($title) ? '' : ' title="'.$title.'"')
            );
        }

        return $url;
    }

    return ($showalways) ? parse($thing) : '';
}

// -------------------------------------------------------------

function older($atts, $thing = null)
{
    global $thispage, $pretext, $m, $is_article_list;

    if (empty($thispage)) {
        return $is_article_list ? postpone_process() : '';
    }

    extract(lAtts(array(
        'showalways' => 0,
        'title'      => '',
        'escape'     => 'html',
    ), $atts));

    $numPages = $thispage['numPages'];
    $pg = $thispage['pg'];

    if ($numPages > 1 && $pg > 0 && $pg < $numPages) {
        $nextpg = $pg + 1;

        // Author URLs should use RealName, rather than username.
        if (!empty($pretext['author'])) {
            $author = get_author_name($pretext['author']);
        } else {
            $author = '';
        }

        $url = pagelinkurl(array(
            'month'   => @$pretext['month'],
            'pg'      => $nextpg,
            's'       => @$pretext['s'],
            'c'       => @$pretext['c'],
            'context' => @$pretext['context'],
            'q'       => @$pretext['q'],
            'm'       => @$m,
            'author'  => $author,
        ));

        if ($thing) {
            if ($escape == 'html') {
                $title = escape_title($title);
            }

            return href(
                parse($thing),
                $url,
                (empty($title) ? '' : ' title="'.$title.'"')
            );
        }

        return $url;
    }

    return ($showalways) ? parse($thing) : '';
}

// -------------------------------------------------------------

function text($atts)
{
    extract(lAtts(array(
        'item'   => '',
        'escape' => 'html',
    ), $atts, false));

    if (!$item) {
        return '';
    }

    unset(
        $atts['item'],
        $atts['escape']
    );

    $tags = array();

    foreach ($atts as $name => $value) {
        $tags['{'.$name.'}'] = $value;
    }

    return gTxt($item, $tags, $escape);
}

// -------------------------------------------------------------

function article_id()
{
    global $thisarticle;

    assert_article();

    return $thisarticle['thisid'];
}

// -------------------------------------------------------------

function article_url_title()
{
    global $thisarticle;

    assert_article();

    return $thisarticle['url_title'];
}

// -------------------------------------------------------------

function if_article_id($atts, $thing = null)
{
    global $thisarticle, $pretext;

    assert_article();

    extract(lAtts(array(
        'id' => $pretext['id'],
    ), $atts));

    $x = $id && in_list($thisarticle['thisid'], $id);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function posted($atts)
{
    global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

    assert_article();

    extract(lAtts(array(
        'format'  => '',
        'gmt'     => '',
        'lang'    => '',
    ), $atts));

    if ($format) {
        $out = safe_strftime($format, $thisarticle['posted'], $gmt, $lang);
    } else {
        if ($id || $c || $pg) {
            $out = safe_strftime($archive_dateformat, $thisarticle['posted'], $gmt, $lang);
        } else {
            $out = safe_strftime($dateformat, $thisarticle['posted'], $gmt, $lang);
        }
    }

    return $out;
}

// -------------------------------------------------------------

function modified($atts)
{
    global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

    assert_article();

    extract(lAtts(array(
        'format'  => '',
        'gmt'     => '',
        'lang'    => ''
    ), $atts));

    if ($format) {
        $out = safe_strftime($format, $thisarticle['modified'], $gmt, $lang);
    } else {
        if ($id || $c || $pg) {
            $out = safe_strftime($archive_dateformat, $thisarticle['modified'], $gmt, $lang);
        } else {
            $out = safe_strftime($dateformat, $thisarticle['modified'], $gmt, $lang);
        }
    }

    return $out;
}

// -------------------------------------------------------------

function expires($atts)
{
    global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

    assert_article();

    if ($thisarticle['expires'] == 0) {
        return '';
    }

    extract(lAtts(array(
        'format'  => '',
        'gmt'     => '',
        'lang'    => ''
    ), $atts));

    if ($format) {
        $out = safe_strftime($format, $thisarticle['expires'], $gmt, $lang);
    } else {
        if ($id || $c || $pg) {
            $out = safe_strftime($archive_dateformat, $thisarticle['expires'], $gmt, $lang);
        } else {
            $out = safe_strftime($dateformat, $thisarticle['expires'], $gmt, $lang);
        }
    }

    return $out;
}

// -------------------------------------------------------------

function if_expires($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = !empty($thisarticle['expires']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_expired($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = !empty($thisarticle['expires']) && ($thisarticle['expires'] <= time());
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function comments_count()
{
    global $thisarticle;

    assert_article();

    return $thisarticle['comments_count'];
}

// -------------------------------------------------------------

function comments_invite($atts)
{
    global $thisarticle, $is_article_list;

    assert_article();

    extract($thisarticle);
    global $comments_mode;

    if (!$comments_invite) {
        $comments_invite = get_pref('comments_default_invite');
    }

    extract(lAtts(array(
        'class'      => __FUNCTION__,
        'showcount'  => true,
        'textonly'   => false,
        'showalways' => false,  // FIXME in crockery. This is only for BC.
        'wraptag'    => '',
    ), $atts));

    $invite_return = '';

    if (($annotate || $comments_count) && ($showalways || $is_article_list)) {
        $comments_invite = txpspecialchars($comments_invite);
        $ccount = ($comments_count && $showcount) ?  ' ['.$comments_count.']' : '';

        if ($textonly) {
            $invite_return = $comments_invite.$ccount;
        } else {
            if (!$comments_mode) {
                $invite_return = doTag($comments_invite, 'a', $class, ' href="'.permlinkurl($thisarticle).'#'.gTxt('comment').'" ').$ccount;
            } else {
                $invite_return = "<a href=\"".hu."?parentid=$thisid\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\"".(($class) ? ' class="'.txpspecialchars($class).'"' : '').'>'.$comments_invite.'</a> '.$ccount;
            }
        }

        if ($wraptag) {
            $invite_return = doTag($invite_return, $wraptag, $class);
        }
    }

    return $invite_return;
}

// -------------------------------------------------------------

function popup_comments($atts, $thing = null)
{
    extract(lAtts(array(
        'form' => 'comments_display'
    ), $atts));

    $rs = safe_row(
        "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(LastMod) AS uLastMod, UNIX_TIMESTAMP(Expires) AS uExpires",
        'textpattern',
        "ID=".intval(gps('parentid'))." AND Status >= 4"
    );

    if ($rs) {
        populateArticleData($rs);

        return ($thing === null ? parse_form($form) : parse($thing));
    }

    return '';
}

// -------------------------------------------------------------

function comments_form($atts, $thing = null)
{
    global $thisarticle, $has_comments_preview;
    global $thiscommentsform; // TODO: Remove any uses of $thiscommentsform when removing deprecated attributes from below.

    // deprecated attributes since TXP 4.6. Most of these (except msgstyle)
    // were moved to the tags that occur within a comments_form, although
    // some of the names changed.
    $deprecated = array('isize', 'msgrows', 'msgcols', 'msgstyle',
        'previewlabel', 'submitlabel', 'rememberlabel', 'forgetlabel');

    foreach ($deprecated as $att) {
        if (isset($atts[$att])) {
            trigger_error(gTxt('deprecated_attribute', array('{name}' => $att)), E_USER_NOTICE);
        }
    }

    $atts = lAtts(array(
        'class'         => __FUNCTION__,
        'form'          => 'comment_form',
        'isize'         => '25',
        'msgcols'       => '25',
        'msgrows'       => '5',
        'msgstyle'      => '',
        'show_preview'  => empty($has_comments_preview),
        'wraptag'       => '',
        'previewlabel'  => gTxt('preview'),
        'submitlabel'   => gTxt('submit'),
        'rememberlabel' => gTxt('remember'),
        'forgetlabel'   => gTxt('forget'),
    ), $atts);

    extract($atts);

    $thiscommentsform = array_intersect_key($atts, array_flip($deprecated));

    assert_article();

    extract($thisarticle);

    $out = '';
    $ip = serverset('REMOTE_ADDR');
    $blacklisted = is_blacklisted($ip);

    if (!checkCommentsAllowed($thisid)) {
        $out = graf(gTxt("comments_closed"), ' id="comments_closed"');
    } elseif ($blacklisted) {
        $out = graf(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), ' id="comments_blacklisted"');
    } elseif (gps('commented') !== '') {
        $out = gTxt("comment_posted");

        if (gps('commented') === '0') {
            $out .= " ".gTxt("comment_moderated");
        }

        $out = graf($out, ' id="txpCommentInputForm"');
    } else {
        // Display a comment preview if required.
        if (ps('preview') && $show_preview) {
            $out = comments_preview(array());
        }

        extract(doDeEnt(psa(array(
            'parentid',
            'backpage',
        ))));

        // If the form fields are filled (anything other than blank), pages really
        // should not be saved by a public cache (rfc2616/14.9.1).
        if (pcs('name') || pcs('email') || pcs('web')) {
            header('Cache-Control: private');
        }

        $url = $GLOBALS['pretext']['request_uri'];

        // Experimental clean URLs with only 404-error-document on Apache possibly
        // requires messy URLs for POST requests.
        if (defined('PARTLY_MESSY') && (PARTLY_MESSY)) {
            $url = hu.'?id='.intval($parentid);
        }

        $out .= '<form id="txpCommentInputForm" method="post" action="'.txpspecialchars($url).'#cpreview">'.
            n.'<div class="comments-wrapper">'.n. // Prevent XHTML Strict validation gotchas.
            ($thing === null ? parse_form($form) : parse($thing)).
            n.hInput('parentid', ($parentid ? $parentid : $thisid)).
            n.hInput('backpage', (ps('preview') ? $backpage : $url)).
            n.'</div>'.
            n.'</form>';
    }

    return (!$wraptag ? $out : doTag($out, $wraptag, $class));
}

// -------------------------------------------------------------

function comment_name_input($atts)
{
    global $prefs, $thiscommentsform;

    extract(lAtts(array(
        'size' => $thiscommentsform['isize']
    ), $atts));

    $namewarn = false;
    $name = pcs('name');
    $h5 = ($prefs['doctype'] == 'html5');

    if (ps('preview')) {
        $comment = getComment();
        $name = $comment['name'];
        $namewarn = ($prefs['comments_require_name'] && !$name);
    }

    return fInput('text', 'name', $name, 'comment_name_input'.($namewarn ? ' comments_error' : ''), '', '', $size, '', 'name', false, $h5 && $prefs['comments_require_name']);
}

// -------------------------------------------------------------

function comment_email_input($atts)
{
    global $prefs, $thiscommentsform;

    extract(lAtts(array(
        'size' => $thiscommentsform['isize']
    ), $atts));

    $emailwarn = false;
    $email = clean_url(pcs('email'));
    $h5 = ($prefs['doctype'] == 'html5');

    if (ps('preview')) {
        $comment = getComment();
        $email = $comment['email'];
        $emailwarn = ($prefs['comments_require_email'] && !$email);
    }

    return fInput($h5 ? 'email' : 'email', 'email', $email, 'comment_email_input'.($emailwarn ? ' comments_error' : ''), '', '', $size, '', 'email', false, $h5 && $prefs['comments_require_email']);
}

// -------------------------------------------------------------

function comment_web_input($atts)
{
    global $prefs, $thiscommentsform;

    extract(lAtts(array(
        'size' => $thiscommentsform['isize']
    ), $atts));

    $web = clean_url(pcs('web'));
    $h5 = ($prefs['doctype'] == 'html5');

    if (ps('preview')) {
        $comment = getComment();
        $web = $comment['web'];
    }

    return fInput($h5 ? 'text' : 'text', 'web', $web, 'comment_web_input', '', '', $size, '', 'web', false, false); /* TODO: maybe use type = 'url' once browsers are less strict */
}

// -------------------------------------------------------------

function comment_message_input($atts)
{
    global $prefs, $thiscommentsform;

    extract(lAtts(array(
        'rows'  => $thiscommentsform['msgrows'],
        'cols'  => $thiscommentsform['msgcols']
    ), $atts));

    $style = $thiscommentsform['msgstyle'];
    $commentwarn = false;
    $n_message = 'message';
    $formnonce = '';
    $message = '';

    if (ps('preview')) {
        $comment = getComment();
        $message = $comment['message'];
        $split = rand(1, 31);
        $nonce = getNextNonce();
        $secret = getNextSecret();
        safe_insert('txp_discuss_nonce', "issue_time = NOW(), nonce = '".doSlash($nonce)."', secret = '".doSlash($secret)."'");
        $n_message = md5('message'.$secret);
        $formnonce = n.hInput(substr($nonce, 0, $split), substr($nonce, $split));
        $commentwarn = (!trim($message));
    }

    $required = ($prefs['doctype'] == 'html5') ? ' required' : '';
    $cols = ($cols && is_numeric($cols)) ? ' cols="'.intval($cols).'"' : '';
    $rows = ($rows && is_numeric($rows)) ? ' rows="'.intval($rows).'"' : '';
    $style = ($style ? ' style="'.$style.'"' : '');

    return '<textarea class="txpCommentInputMessage'.(($commentwarn) ? ' comments_error"' : '"').
        ' id="message" name="'.$n_message.'"'.$cols.$rows.$style.$required.
        '>'.txpspecialchars(substr(trim($message), 0, 65535)).'</textarea>'.
        callback_event('comment.form').
        $formnonce;
}

// -------------------------------------------------------------

function comment_remember($atts)
{
    global $thiscommentsform;

    extract(lAtts(array(
        'rememberlabel' => $thiscommentsform['rememberlabel'],
        'forgetlabel'   => $thiscommentsform['forgetlabel']
    ), $atts));

    extract(doDeEnt(psa(array(
        'checkbox_type',
        'remember',
        'forget'
    ))));

    if (!ps('preview')) {
        $rememberCookie = cs('txp_remember');

        if ($rememberCookie === '') {
            $checkbox_type = 'remember';
            $remember = 1;
        } elseif ($rememberCookie == 1) {
            $checkbox_type = 'forget';
        } else {
            $checkbox_type = 'remember';
        }
    }

    if ($checkbox_type == 'forget') {
        // Inhibit default remember.
        if ($forget == 1) {
            destroyCookies();
        }

        $checkbox = checkbox('forget', 1, $forget, '', 'forget').' '.tag(txpspecialchars($forgetlabel), 'label', ' for="forget"');
    } else {
        // Inhibit default remember.
        if ($remember != 1) {
            destroyCookies();
        }

        $checkbox = checkbox('remember', 1, $remember, '', 'remember').' '.tag(txpspecialchars($rememberlabel), 'label', ' for="remember"');
    }

    $checkbox .= ' '.hInput('checkbox_type', $checkbox_type);

    return $checkbox;
}

// -------------------------------------------------------------

function comment_preview($atts)
{
    global $thiscommentsform;

    extract(lAtts(array(
        'label'  => $thiscommentsform['previewlabel']
    ), $atts));

    return fInput('submit', 'preview', $label, 'button', '', '', '', '', 'txpCommentPreview', false);
}

// -------------------------------------------------------------

function comment_submit($atts)
{
    global $thiscommentsform;

    extract(lAtts(array(
        'label'  => $thiscommentsform['submitlabel']
    ), $atts));

    // If all fields check out, the submit button is active/clickable.
    if (ps('preview')) {
        return fInput('submit', 'submit', $label, 'button', '', '', '', '', 'txpCommentSubmit', false);
    } else {
        return fInput('submit', 'submit', $label, 'button disabled', '', '', '', '', 'txpCommentSubmit', true);
    }
}

// -------------------------------------------------------------

function comments_error($atts)
{
    extract(lAtts(array(
        'break'   => 'br',
        'class'   => __FUNCTION__,
        'wraptag' => 'div',
    ), $atts));

    $evaluator = & get_comment_evaluator();

    $errors = $evaluator->get_result_message();

    if ($errors) {
        return doWrap($errors, $wraptag, $break, $class);
    }
}

// -------------------------------------------------------------

function if_comments_error($atts, $thing = null)
{
    $evaluator = & get_comment_evaluator();

    $x = (count($evaluator->get_result_message()) > 0);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function comments($atts, $thing = null)
{
    global $thisarticle, $prefs;
    extract($prefs);

    extract(lAtts(array(
        'form'       => 'comments',
        'wraptag'    => ($comments_are_ol ? 'ol' : ''),
        'break'      => ($comments_are_ol ? 'li' : 'div'),
        'class'      => __FUNCTION__,
        'breakclass' => '', // Deprecated in 4.6.0
        'limit'      => 0,
        'offset'     => 0,
        'sort'       => 'posted ASC',
    ), $atts));

    assert_article();

    if (isset($atts['breakclass'])) {
        trigger_error(gTxt('deprecated_attribute', array('{name}' => 'breakclass')), E_USER_NOTICE);
    }

    extract($thisarticle);

    if (!$comments_count) {
        return '';
    }

    $qparts = array(
        "parentid = ".intval($thisid)." AND visible = ".VISIBLE,
        "ORDER BY ".doSlash($sort),
        ($limit) ? "LIMIT ".intval($offset).", ".intval($limit) : '',
    );

    $rs = safe_rows_start("*, UNIX_TIMESTAMP(posted) AS time", 'txp_discuss', join(' ', $qparts));

    $out = '';

    if ($rs) {
        $comments = array();

        while ($vars = nextRow($rs)) {
            $GLOBALS['thiscomment'] = $vars;
            $comments[] = ($thing === null ? parse_form($form) : parse($thing)).n;
            unset($GLOBALS['thiscomment']);
        }

        $out .= doWrap($comments, $wraptag, $break, $class, $breakclass);
    }

    return $out;
}

// -------------------------------------------------------------

function comments_preview($atts, $thing = null)
{
    global $has_comments_preview;

    if (!ps('preview')) {
        return '';
    }

    extract(lAtts(array(
        'form'    => 'comments',
        'wraptag' => '',
        'class'   => __FUNCTION__,
    ), $atts));

    assert_article();

    $preview = psa(array('name', 'email', 'web', 'message', 'parentid', 'remember'));
    $preview['time'] = time();
    $preview['discussid'] = 0;
    $preview['name'] = strip_tags($preview['name']);
    $preview['email'] = clean_url($preview['email']);

    if ($preview['message'] == '') {
        $in = getComment();
        $preview['message'] = $in['message'];
    }

    // It is called 'message', not 'novel'!
    $preview['message'] = markup_comment(substr(trim($preview['message']), 0, 65535));

    $preview['web'] = clean_url($preview['web']);

    $GLOBALS['thiscomment'] = $preview;
    $comments = ($thing === null ? parse_form($form) : parse($thing)).n;
    unset($GLOBALS['thiscomment']);
    $out = doTag($comments, $wraptag, $class);

    // Set a flag to tell the comments_form tag that it doesn't have to show
    // a preview.
    $has_comments_preview = true;

    return $out;
}

// -------------------------------------------------------------

function if_comments_preview($atts, $thing = null)
{
    $x = ps('preview') && checkCommentsAllowed(gps('parentid'));
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function comment_permlink($atts, $thing)
{
    global $thisarticle, $thiscomment;

    assert_article();
    assert_comment();

    extract($thiscomment);
    extract(lAtts(array(
        'anchor' => empty($thiscomment['has_anchor_tag']),
    ), $atts));

    $dlink = permlinkurl($thisarticle).'#c'.$discussid;

    $thing = parse($thing);

    $name = ($anchor ? ' id="c'.$discussid.'"' : '');

    return tag($thing, 'a', ' href="'.$dlink.'"'.$name);
}

// -------------------------------------------------------------

function comment_id()
{
    global $thiscomment;

    assert_comment();

    return $thiscomment['discussid'];
}

// -------------------------------------------------------------

function comment_name($atts)
{
    global $thiscomment, $prefs;

    assert_comment();

    extract($prefs);
    extract($thiscomment);

    extract(lAtts(array(
        'link' => 1,
    ), $atts));

    $name = txpspecialchars($name);

    if ($link) {
        $web = comment_web();
        $nofollow = (@$comment_nofollow ? ' rel="nofollow"' : '');

        if (!empty($web)) {
            return href($name, $web, $nofollow);
        }

        if ($email && !$never_display_email) {
            return href($name, eE('mailto:'.$email), $nofollow);
        }
    }

    return $name;
}

// -------------------------------------------------------------

function comment_email()
{
    global $thiscomment;

    assert_comment();

    return txpspecialchars($thiscomment['email']);
}

// -------------------------------------------------------------

function comment_web()
{
    global $thiscomment;

    assert_comment();

    if (preg_match('/^\S/', $thiscomment['web'])) {
        // Prepend default protocol 'http' for all non-local URLs.
        if (!preg_match('!^https?://|^#|^/[^/]!', $thiscomment['web'])) {
            $thiscomment['web'] = 'http://'.$thiscomment['web'];
        }

        return txpspecialchars($thiscomment['web']);
    }

    return '';
}

// -------------------------------------------------------------

function comment_time($atts)
{
    global $thiscomment, $comments_dateformat;

    assert_comment();

    extract(lAtts(array(
        'format' => $comments_dateformat,
        'gmt'    => '',
        'lang'   => '',
    ), $atts));

    return safe_strftime($format, $thiscomment['time'], $gmt, $lang);
}

// -------------------------------------------------------------

function comment_message()
{
    global $thiscomment;

    assert_comment();

    return $thiscomment['message'];
}

// -------------------------------------------------------------

function comment_anchor()
{
    global $thiscomment;

    assert_comment();

    $thiscomment['has_anchor_tag'] = 1;

    return '<a id="c'.$thiscomment['discussid'].'"></a>';
}

// -------------------------------------------------------------

function author($atts)
{
    global $thisarticle, $thisauthor, $s, $author;

    extract(lAtts(array(
        'escape'       => 'html',
        'link'         => 0,
        'title'        => 1,
        'section'      => '',
        'this_section' => 0,
        'format'       => '', // empty, link, or url
    ), $atts));

    // Synonym.
    if ($format === 'link') {
        $link = 1;
    }

    if ($thisauthor) {
        $realname = $thisauthor['realname'];
        $name = $thisauthor['name'];
    } elseif ($author) {
        $realname = get_author_name($author);
        $name = $author;
    } else {
        assert_article();
        $realname = get_author_name($thisarticle['authorid']);
        $name = $thisarticle['authorid'];
    }

    if ($title) {
        $display_name = $realname;
    } else {
        $display_name = $name;
    }

    $display_name = ($escape === 'html') ? txpspecialchars($display_name) : $display_name;

    if ($this_section && $s != 'default') {
        $section = $s;
    }

    $href = pagelinkurl(array(
            's'      => $section,
            'author' => $realname,
        ));

    if ($format === 'url') {
        return $href;
    }

    if ($link) {
        return href($display_name, $href, ' rel="author"');
    }

    return $display_name;
}

// -------------------------------------------------------------

function author_email($atts)
{
    global $thisarticle, $thisauthor;

    extract(lAtts(array(
        'escape' => 'html',
        'link'   => '',
    ), $atts));

    if ($thisauthor) {
        $email = get_author_email($thisauthor['name']);
    } else {
        assert_article();
        $email = get_author_email($thisarticle['authorid']);
    }

    if ($escape == 'html') {
        $display_email = txpspecialchars($email);
    } else {
        $display_email = $email;
    }

    if ($link) {
        return email(array(
            'email'    => $email,
            'linktext' => $display_email,
        ));
    }

    return $display_email;
}

// -------------------------------------------------------------

function if_author($atts, $thing = null)
{
    global $author, $context, $thisauthor;

    extract(lAtts(array(
        'type' => 'article',
        'name' => '',
    ), $atts));

    $theType = ($type) ? $type == $context : true;

    if ($thisauthor) {
        $x = $name === '' || in_list($thisauthor['name'], $name);
    } elseif ($name) {
        $x = ($theType && in_list($author, $name));
    } else {
        $x = ($theType && (string) $author !== '');
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_article_author($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    extract(lAtts(array(
        'name' => '',
    ), $atts));

    $author = $thisarticle['authorid'];

    $x = $name ? in_list($author, $name) : (string) $author !== '';
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function body()
{
    global $thisarticle, $is_article_body;

    assert_article();

    $was_article_body = $is_article_body;
    $is_article_body = 1;
    $out = parse($thisarticle['body']);
    $is_article_body = $was_article_body;

    return $out;
}

// -------------------------------------------------------------

function title($atts)
{
    global $thisarticle, $prefs;

    assert_article();

    extract(lAtts(array(
        'escape' => null,
        'no_widow' => @$prefs['title_no_widow']
    ), $atts));

    $t = $escape === null ? escape_title($thisarticle['title']) : $thisarticle['title'];

    if ($no_widow && $escape === null) {
        $t = noWidow($t);
    }

    return $t;
}

// -------------------------------------------------------------

function excerpt()
{
    global $thisarticle, $is_article_body;

    assert_article();

    $was_article_body = $is_article_body;
    $is_article_body = 1;
    $out = parse($thisarticle['excerpt']);
    $is_article_body = $was_article_body;

    return $out;
}

// -------------------------------------------------------------

function category1($atts, $thing = null)
{
    return article_category(array('number' => 1) + $atts, $thing);
}

// -------------------------------------------------------------

function category2($atts, $thing = null)
{
    return article_category(array('number' => 2) + $atts, $thing);
}

// -------------------------------------------------------------

function article_category($atts, $thing = null)
{
    global $thisarticle, $s, $permlink_mode;

    assert_article();

    extract(lAtts(array(
        'number'       => 1,
        'class'        => '',
        'link'         => 0,
        'title'        => 0,
        'section'      => '',
        'this_section' => 0,
        'wraptag'      => '',
    ), $atts));

    $cat = 'category'.intval($number);

    if ($thisarticle[$cat]) {
        $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;
        $category = $thisarticle[$cat];

        $label = txpspecialchars(($title) ? fetch_category_title($category) : $category);

        if ($thing) {
            $out = href(
                parse($thing),
                pagelinkurl(array('s' => $section, 'c' => $category)),
                (($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '').
                ($title ? ' title="'.$label.'"' : '').
                ($permlink_mode != 'messy' ? ' rel="category tag"' : '')
            );
        } elseif ($link) {
            $out = href(
                $label,
                pagelinkurl(array('s' => $section, 'c' => $category)),
                ($permlink_mode != 'messy' ? ' rel="category tag"' : '')
            );
        } else {
            $out = $label;
        }

        return doTag($out, $wraptag, $class);
    }
}

// -------------------------------------------------------------

function category($atts, $thing = null)
{
    global $s, $c, $thiscategory, $context;

    extract(lAtts(array(
        'class'        => '',
        'link'         => 0,
        'name'         => '',
        'section'      => $s,
        'this_section' => 0,
        'title'        => 0,
        'type'         => 'article',
        'url'          => 0,
        'wraptag'      => '',
    ), $atts));

    if ($name) {
        $category = $name;
    } elseif (!empty($thiscategory['name'])) {
        $category = $thiscategory['name'];
        $type = $thiscategory['type'];
    } else {
        $category = $c;

        if (!isset($atts['type'])) {
            $type = $context;
        }
    }

    if ($category) {
        if ($this_section) {
            $section = ($s == 'default' ? '' : $s);
        } elseif (isset($thiscategory['section'])) {
            $section = $thiscategory['section'];
        }

        $label = txpspecialchars(($title) ? fetch_category_title($category, $type) : $category);

        $href = pagelinkurl(array('s' => $section, 'c' => $category, 'context' => $type));

        if ($thing) {
            $out = href(
                parse($thing),
                $href,
                (($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '').
                ($title ? ' title="'.$label.'"' : '')
            );
        } elseif ($link) {
            $out = href(
                $label,
                $href,
                ($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : ''
            );
        } elseif ($url) {
            $out = $href;
        } else {
            $out = $label;
        }

        return doTag($out, $wraptag, $class);
    }
}

// -------------------------------------------------------------

function section($atts, $thing = null)
{
    global $thisarticle, $s, $thissection;

    extract(lAtts(array(
        'class'   => '',
        'link'    => 0,
        'name'    => '',
        'title'   => 0,
        'url'     => 0,
        'wraptag' => '',
    ), $atts));

    if ($name) {
        $sec = $name;
    } elseif (!empty($thissection['name'])) {
        $sec = $thissection['name'];
    } elseif (!empty($thisarticle['section'])) {
        $sec = $thisarticle['section'];
    } else {
        $sec = $s;
    }

    if ($sec) {
        $label = txpspecialchars(($title) ? fetch_section_title($sec) : $sec);

        $href = pagelinkurl(array('s' => $sec));

        if ($thing) {
            $out = href(
                parse($thing),
                $href,
                (($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '').
                ($title ? ' title="'.$label.'"' : '')
            );
        } elseif ($link) {
            $out = href(
                $label,
                $href,
                ($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : ''
            );
        } elseif ($url) {
            $out = $href;
        } else {
            $out = $label;
        }

        return doTag($out, $wraptag, $class);
    }
}

// -------------------------------------------------------------

function keywords($atts)
{
    global $thisarticle;

    assert_article();

    extract(lAtts(array(
        'class'   => '',
        'break'     => ',',
        'wraptag' => ''
    ), $atts));

    $out = do_list_unique(txpspecialchars($thisarticle['keywords']));

//    trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

    return doWrap($out, $wraptag, $break, $class);
}

// -------------------------------------------------------------

function if_keywords($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    extract(lAtts(array(
        'keywords' => '',
    ), $atts));

    $condition = empty($keywords)
        ? $thisarticle['keywords']
        : array_intersect(do_list($keywords), do_list($thisarticle['keywords']));

    $x = !empty($condition);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_article_image($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = !empty($thisarticle['article_image']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function article_image($atts)
{
    global $thisarticle;

    assert_article();

    extract(lAtts(array(
        'class'     => '',
        'escape'    => 'html',
        'html_id'   => '',
        'style'     => '',
        'width'     => '',
        'height'    => '',
        'thumbnail' => 0,
        'wraptag'   => '',
    ), $atts));

    if ($thisarticle['article_image']) {
        $image = $thisarticle['article_image'];
    } else {
        return '';
    }

    if (intval($image)) {
        $rs = safe_row("*", 'txp_image', "id = ".intval($image));

        if ($rs) {
            $width = ($width == '') ? (($thumbnail) ? $rs['thumb_w'] : $rs['w']) : $width;
            $height = ($height == '') ? (($thumbnail) ? $rs['thumb_h'] : $rs['h']) : $height;

            if ($thumbnail) {
                if ($rs['thumbnail']) {
                    extract($rs);

                    if ($escape == 'html') {
                        $alt = txpspecialchars($alt);
                        $caption = txpspecialchars($caption);
                    }

                    $out = '<img src="'.imagesrcurl($id, $ext, true).'" alt="'.$alt.'"'.
                        (($html_id && !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '').
                        (($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '').
                        ($style ? ' style="'.txpspecialchars($style).'"' : '').
                        ($width ? ' width="'.(int) $width.'"' : '').
                        ($height ? ' height="'.(int) $height.'"' : '').
                        ' />';
                } else {
                    return '';
                }
            } else {
                extract($rs);

                if ($escape == 'html') {
                    $alt = txpspecialchars($alt);
                    $caption = txpspecialchars($caption);
                }

                $out = '<img src="'.imagesrcurl($id, $ext).'" alt="'.$alt.'"'.
                    (($html_id && !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '').
                    (($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '').
                    ($style ? ' style="'.txpspecialchars($style).'"' : '').
                    ($width ? ' width="'.(int) $width.'"' : '').
                    ($height ? ' height="'.(int) $height.'"' : '').
                    ' />';
            }
        } else {
            trigger_error(gTxt('unknown_image'));

            return '';
        }
    } else {
        $out = '<img src="'.txpspecialchars($image).'" alt=""'.
            (($html_id && !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '').
            (($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '').
            ($style ? ' style="'.txpspecialchars($style).'"' : '').
            ($width ? ' width="'.(int) $width.'"' : '').
            ($height ? ' height="'.(int) $height.'"' : '').
            ' />';
    }

    return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
}

// -------------------------------------------------------------

function search_result_title($atts)
{
    return permlink($atts, '<txp:title />');
}

// -------------------------------------------------------------

function search_result_excerpt($atts)
{
    global $thisarticle, $pretext;

    assert_article();

    extract(lAtts(array(
        'break'   => ' &#8230;',
        'hilight' => 'strong',
        'limit'   => 5,
    ), $atts));

    $m = $pretext['m'];
    $q = $pretext['q'];

    $quoted = ($q[0] === '"') && ($q[strlen($q) - 1] === '"');
    $q = $quoted ? trim(trim($q, '"')) : trim($q);

    $result = preg_replace('/\s+/', ' ', strip_tags(str_replace('><', '> <', $thisarticle['body'])));

    if ($quoted || empty($m) || $m === 'exact') {
        $regex_search = '/(?:\G|\s).{0,50}'.preg_quote($q, '/').'.{0,50}(?:\s|$)/iu';
        $regex_hilite = '/('.preg_quote($q, '/').')/i';
    } else {
        $regex_search = '/(?:\G|\s).{0,50}('.preg_replace('/\s+/', '|', preg_quote($q, '/')).').{0,50}(?:\s|$)/iu';
        $regex_hilite = '/('.preg_replace('/\s+/', '|', preg_quote($q, '/')).')/i';
    }

    preg_match_all($regex_search, $result, $concat);
    $concat = $concat[0];

    for ($i = 0, $r = array(); $i < min($limit, count($concat)); $i++) {
        $r[] = trim($concat[$i]);
    }

    $concat = join($break.n, $r);
    $concat = preg_replace('/^[^>]+>/U', '', $concat);
// TODO:

    $concat = preg_replace($regex_hilite, "<$hilight>$1</$hilight>", $concat);

    return ($concat) ? trim($break.$concat.$break) : '';
}

// -------------------------------------------------------------

function search_result_url($atts)
{
    global $thisarticle;

    assert_article();

    $l = permlinkurl($thisarticle);

    return permlink($atts, $l);
}

// -------------------------------------------------------------

function search_result_date($atts)
{
    assert_article();

    return posted($atts);
}

// -------------------------------------------------------------

function search_result_count($atts)
{
    global $thispage;

    if (empty($thispage)) {
        return postpone_process();
    }

    extract(lAtts(array(
        'text' => null,
        'pageby' => 1
    ), $atts));

    $by = (int)$pageby or $by = 1;
    $t = ceil(@$thispage[$pageby === true ? 'numPages' : 'grand_total']/$by);

    if (!isset($text)) {
        $text = $pageby === true || $by > 1 ? gTxt($t == 1 ? 'page' : 'pages') : gTxt($t == 1 ? 'article_found' : 'articles_found');
    }

    return $t.($text ? ' '.$text : '');
}

// -------------------------------------------------------------

function image_index($atts)
{
    trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

    global $c;

    lAtts(array(
        'break'    => br,
        'wraptag'  => '',
        'class'    => __FUNCTION__,
        'category' => $c,
        'limit'    => 0,
        'offset'   => 0,
        'sort'     => 'name ASC',
    ), $atts);

    if (!isset($atts['category'])) {
        $atts['category'] = $c;
    }

    if (!isset($atts['class'])) {
        $atts['class'] = __FUNCTION__;
    }

    if ($atts['category']) {
        return images($atts);
    }

    return '';
}

// -------------------------------------------------------------

function image_display($atts)
{
    trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

    global $p;

    if ($p) {
        return image(array('id' => $p));
    }
}

// -------------------------------------------------------------

function images($atts, $thing = null)
{
    global $s, $c, $context, $thisimage, $thisarticle, $thispage, $pretext;

    extract(lAtts(array(
        'name'        => '',
        'id'          => '',
        'category'    => '',
        'author'      => '',
        'realname'    => '',
        'extension'   => '',
        'thumbnail'   => '',
        'auto_detect' => 'article, category, author',
        'break'       => br,
        'wraptag'     => '',
        'class'       => __FUNCTION__,
        'html_id'     => '',
        'form'        => '',
        'pageby'      => '',
        'limit'       => 0,
        'offset'      => 0,
        'sort'        => 'name ASC',
    ), $atts));

    $safe_sort = doSlash($sort);
    $where = array();
    $has_content = $thing || $form;
    $filters = isset($atts['id']) || isset($atts['name']) || isset($atts['category']) || isset($atts['author']) || isset($atts['realname']) || isset($atts['extension']) || $thumbnail === '1' || $thumbnail === '0';
    $context_list = (empty($auto_detect) || $filters) ? array() : do_list_unique($auto_detect);
    $pageby = ($pageby == 'limit') ? $limit : $pageby;

    if ($name) {
        $where[] = "name IN ('".join("','", doSlash(do_list_unique($name)))."')";
    }

    if ($category) {
        $where[] = "category IN ('".join("','", doSlash(do_list_unique($category)))."')";
    }

    if ($id) {
        $where[] = "id IN ('".join("','", doSlash(do_list_unique($id)))."')";
    }

    if ($author) {
        $where[] = "author IN ('".join("','", doSlash(do_list_unique($author)))."')";
    }

    if ($realname) {
        $authorlist = safe_column("name", 'txp_users', "RealName IN ('".join("','", doArray(doSlash(do_list_unique($realname)), 'urldecode'))."')");
        if ($authorlist) {
            $where[] = "author IN ('".join("','", doSlash($authorlist))."')";
        }
    }

    if ($extension) {
        $where[] = "ext IN ('".join("','", doSlash(do_list_unique($extension)))."')";
    }

    if ($thumbnail === '0' || $thumbnail === '1') {
        $where[] = "thumbnail = $thumbnail";
    }

    // If no images are selected, try...
    if (!$where && !$filters) {
        foreach ($context_list as $ctxt) {
            switch ($ctxt) {
                case 'article':
                    // ...the article image field.
                    if ($thisarticle && !empty($thisarticle['article_image'])) {
                        $items = do_list_unique($thisarticle['article_image']);
                        foreach ($items as &$item) {
                            if (is_numeric($item)) {
                                $item = intval($item);
                            } else {
                                return article_image(compact('class', 'html_id', 'wraptag'));
                            }
                        }
                        $items = join(",", $items);

                        // Note: This clause will squash duplicate ids.
                        $where[] = "id IN ($items)";

                        // Order of ids in article image field overrides default 'sort' attribute.
                        if (empty($atts['sort'])) {
                            $safe_sort = "FIELD(id, $items)";
                        }
                    }
                    break;
                case 'category':
                    // ...the global category in the URL.
                    if ($context == 'image' && !empty($c)) {
                        $where[] = "category = '".doSlash($c)."'";
                    }
                    break;
                case 'author':
                    // ...the global author in the URL.
                    if ($context == 'image' && !empty($pretext['author'])) {
                        $where[] = "author = '".doSlash($pretext['author'])."'";
                    }
                    break;
            }
            // Only one context can be processed.
            if ($where) {
                break;
            }
        }
    }

    // Order of ids in 'id' attribute overrides default 'sort' attribute.
    if (empty($atts['sort']) && $id !== '') {
        $safe_sort = "FIELD(id, ".join(',', doSlash(do_list_unique($id))).")";
    }

    // If nothing matches, output nothing.
    if (!$where && $filters) {
        return '';
    }

    // If nothing matches, start with all images.
    if (!$where) {
        $where[] = "1 = 1";
    }

    $where = join(" AND ", $where);

    // Set up paging if required.
    if ($limit && $pageby) {
        $grand_total = safe_count('txp_image', $where);
        $total = $grand_total - $offset;
        $numPages = ($pageby > 0) ? ceil($total / $pageby) : 1;
        $pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
        $pgoffset = $offset + (($pg - 1) * $pageby);

        // Send paging info to txp:newer and txp:older.
        $pageout['pg']          = $pg;
        $pageout['numPages']    = $numPages;
        $pageout['s']           = $s;
        $pageout['c']           = $c;
        $pageout['context']     = 'image';
        $pageout['grand_total'] = $grand_total;
        $pageout['total']       = $total;

        if (empty($thispage)) {
            $thispage = $pageout;
        }
    } else {
        $pgoffset = $offset;
    }

    $qparts = array(
        $where,
        "ORDER BY ".$safe_sort,
        ($limit) ? "LIMIT ".intval($pgoffset).", ".intval($limit) : '',
    );

    $rs = safe_rows_start("*", 'txp_image', join(' ', $qparts));

    if ($rs) {
        $out = array();
        $count = 0;
        $last = numRows($rs);

        if (isset($thisimage)) {
            $old_image = $thisimage;
        }

        while ($a = nextRow($rs)) {
            ++$count;
            $thisimage = image_format_info($a);
            $thisimage['is_first'] = ($count == 1);
            $thisimage['is_last'] = ($count == $last);

            if (!$has_content) {
                $url = pagelinkurl(array(
                    'c'       => $thisimage['category'],
                    'context' => 'image',
                    's'       => $s,
                    'p'       => $thisimage['id'],
                ));
                $src = image_url(array('thumbnail' => '1'));
                $thing = href(
                    '<img src="'.$src.'" alt="'.txpspecialchars($thisimage['alt']).'" />',
                    $url
                );
            }

            $out[] = ($thing) ? parse($thing) : parse_form($form);
        }

        $thisimage = (isset($old_image) ? $old_image : null);

        if ($out) {
            return doWrap($out, $wraptag, compact('break', 'class', 'html_id'));
        }
    }

    return '';
}

// -------------------------------------------------------------

function image_info($atts)
{
    extract(lAtts(array(
        'name'       => '',
        'id'         => '',
        'type'       => 'caption',
        'escape'     => 'html',
        'wraptag'    => '',
        'class'      => '',
        'break'      => '',
        'breakclass' => '', // Deprecated in 4.6.0.
    ), $atts));

    if (isset($atts['breakclass'])) {
        trigger_error(gTxt('deprecated_attribute', array('{name}' => 'breakclass')), E_USER_NOTICE);
    }

    $validItems = array('id', 'name', 'category', 'category_title', 'alt', 'caption', 'ext', 'author', 'w', 'h', 'thumb_w', 'thumb_h', 'date');
    $type = do_list($type);

    $out = array();
    if ($imageData = imageFetchInfo($id, $name)) {
        $imageData['category_title'] = fetch_category_title($imageData['category'], 'image');

        foreach ($type as $item) {
            if (in_array($item, $validItems)) {
                if (isset($imageData[$item])) {
                    $out[] = ($escape == 'html') ?
                        txpspecialchars($imageData[$item]) : $imageData[$item];
                }
            } else {
                trigger_error(gTxt('invalid_attribute_value', array('{name}' => $item)), E_USER_NOTICE);
            }
        }
    }

    return doWrap($out, $wraptag, $break, $class, $breakclass);
}

// -------------------------------------------------------------

function image_url($atts, $thing = null)
{
    extract(lAtts(array(
        'name'      => '',
        'id'        => '',
        'thumbnail' => 0,
        'link'      => 'auto',
    ), $atts));

    if (($name || $id) && $thing) {
        global $thisimage;
        $stash = $thisimage;
    }

    if ($thisimage = imageFetchInfo($id, $name)) {
        $url = imagesrcurl($thisimage['id'], $thisimage['ext'], $thumbnail);
        $link = ($link == 'auto') ? (($thing) ? 1 : 0) : $link;
        $out = ($thing) ? parse($thing) : $url;
        $out = ($link) ? href($out, $url) : $out;
    }

    if (isset($stash)) {
        $thisimage = $stash;
    }

    return isset($out) ? $out : '';
}

// -------------------------------------------------------------

function image_author($atts)
{
    global $s;

    extract(lAtts(array(
        'name'         => '',
        'id'           => '',
        'link'         => 0,
        'title'        => 1,
        'section'      => '',
        'this_section' => ''
    ), $atts));

    if ($imageData = imageFetchInfo($id, $name)) {
        $author_name = get_author_name($imageData['author']);
        $display_name = txpspecialchars(($title) ? $author_name : $imageData['author']);

        $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;

        $author = ($link)
            ? href($display_name, pagelinkurl(array('s' => $section, 'author' => $author_name, 'context' => 'image')))
            : $display_name;

        return $author;
    }
}

// -------------------------------------------------------------

function image_date($atts)
{
    extract(lAtts(array(
        'name'   => '',
        'id'     => '',
        'format' => '',
    ), $atts));

    if ($imageData = imageFetchInfo($id, $name)) {
        // Not a typo: use fileDownloadFormatTime() since it's fit for purpose.
        $out = fileDownloadFormatTime(array(
            'ftime'  => $imageData['date'],
            'format' => $format,
        ));

        return $out;
    }
}

// -------------------------------------------------------------

function if_thumbnail($atts, $thing = null)
{
    global $thisimage;

    assert_image();

    $x = ($thisimage['thumbnail'] == 1);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_comments($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = ($thisarticle['comments_count'] > 0);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_comments_allowed($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = checkCommentsAllowed($thisarticle['thisid']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_comments_disallowed($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = !checkCommentsAllowed($thisarticle['thisid']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_individual_article($atts, $thing = null)
{
    global $is_article_list;

    $x = ($is_article_list == false);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_article_list($atts, $thing = null)
{
    global $is_article_list;

    $x = ($is_article_list == true);
    return isset($thing) ? parse($thing, $x) : $x;
}

/**
 * Returns article keywords.
 *
 * @param  array  $atts Tag attributes
 * @return string
 */

function meta_keywords($atts)
{
    global $id_keywords;

    extract(lAtts(array(
        'escape'    => null,
        'format'    => 'meta', // or empty for raw value
        'separator' => '',
    ), $atts));

    $out = '';

    if ($id_keywords) {
        $content = ($escape === null) ? txpspecialchars($id_keywords) : $id_keywords;

        if ($separator !== '') {
            $content = implode($separator, do_list($content));
        }

        if ($format === 'meta') {
            // Can't use tag_void() since it escapes its content.
            $out = '<meta name="keywords" content="'.$content.'" />';
        } else {
            $out = $content;
        }
    }

    return $out;
}

/**
 * Returns article, section or category meta description info.
 *
 * @param  array  $atts Tag attributes
 * @return string
 */

function meta_description($atts)
{
    extract(lAtts(array(
        'escape' => null,
        'format' => 'meta', // or empty for raw value
        'type'   => null,
    ), $atts));

    $out = '';
    $content = getMetaDescription($type);

    if ($content) {
        $content = ($escape === null ? txpspecialchars($content) : $content);

        if ($format === 'meta') {
            $out = '<meta name="description" content="'.$content.'" />';
        } else {
            $out = $content;
        }
    }

    return $out;
}

/**
 * Determines if there is meta description content in the given context.
 *
 * @param  array  $atts  Tag attributes
 * @param  string $thing Tag container content
 * @return string
 */

function if_description($atts, $thing = null)
{
    extract(lAtts(array(
        'type' => null,
    ), $atts));

    $content = getMetaDescription($type);
    $x = !empty($content);

    return isset($thing) ? parse($thing, $x) : $x;
}


// -------------------------------------------------------------

function meta_author($atts)
{
    global $id_author;

    extract(lAtts(array(
        'escape' => null,
        'format' => 'meta', // or empty for raw value
        'title'  => 0,
    ), $atts));

    $out = '';

    if ($id_author) {
        $display_name = ($title) ? get_author_name($id_author) : $id_author;
        $display_name = ($escape === null) ? txpspecialchars($display_name) : $display_name;

        if ($format === 'meta') {
            // Can't use tag_void() since it escapes its content.
            $out = '<meta name="author" content="'.$display_name.'" />';
        } else {
            $out = $display_name;
        }
    }

    return $out;
}

// -------------------------------------------------------------

function permlink($atts, $thing = null)
{
    global $thisarticle;

    extract(lAtts(array(
        'class' => '',
        'id'    => '',
        'style' => '',
        'title' => '',
    ), $atts));

    if (!$id) {
        assert_article();
    }

    $url = ($id) ? permlinkurl_id($id) : permlinkurl($thisarticle);

    if ($url) {
        if ($thing === null) {
            return $url;
        }

        return tag(parse($thing), 'a', array(
            'rel' => 'bookmark',
            'href' => $url,
            'title' => $title,
            'style' => $style,
            'class' => $class
            )
        );
    }
}

// -------------------------------------------------------------

function lang()
{
    return txpspecialchars(LANG);
}

// -------------------------------------------------------------

function breadcrumb($atts, $thing = null)
{
    global $c, $s, $sitename, $thiscategory;
    static $cache = array();

    extract(lAtts(array(
        'category'  => $c,
        'section'   => $s,
        'wraptag'   => 'p',
        'separator' => '&#160;&#187;&#160;',
        'limit'     => null,
        'offset'    => 0,
        'link'      => 1,
        'label'     => $sitename,
        'title'     => '',
        'class'     => '',
        'linkclass' => '',
    ), $atts));

    // For BC, get rid of in crockery.
    if ($link == 'n') {
        $linked = false;
    } else {
        $linked = $link;
    }

    $content = array();
    $label = txpspecialchars($label);

    if ($linked && $label) {
        $label = doTag($label, 'a', $linkclass, ' href="'.hu.'"');
    }

    if (!empty($section) && $section != 'default') {
        $section_title = ($title) ? fetch_section_title($section) : $section;
        $section_title_html = escape_title($section_title);
        $content[] = ($linked)
            ? (doTag($section_title_html, 'a', $linkclass, ' href="'.pagelinkurl(array('s' => $s)).'"'))
            : $section_title_html;
    }

    if (!$category) {
        $catpath = array();
    } elseif (isset($cache[$category])) {
        $catpath = $cache[$category];
    } else {
        $catpath = getTreePath($category, 'article');
        array_shift($catpath);
        $cache[$category] = $catpath;
    }

    if ($limit || $offset) {
        $offset = (int)$offset < 0 ? (int)$offset - 1 : (int)$offset;
        $catpath = array_slice($catpath, $offset, isset($limit) ? (int)$limit : null);
    }

    $oldcategory = isset($thiscategory) ? $thiscategory : null;

    foreach ($catpath as $thiscategory) {
        $category_title_html = isset($thing) ? parse($thing) : ($title ? escape_title($thiscategory['title']) : $thiscategory['name']);
        $content[] = ($linked)
            ? doTag($category_title_html, 'a', $linkclass, ' href="'.pagelinkurl(array('c' => $thiscategory['name'])).'"')
            : $category_title_html;
    }

    $thiscategory = isset($oldcategory) ? $oldcategory : null;

    // Add the label at the end, to prevent breadcrumb for homepage.
    if ($content) {
        return doWrap($label ? array_merge(array($label), $content) : $content, $wraptag, $separator, $class);
    }
}

//------------------------------------------------------------------------

function if_excerpt($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = trim($thisarticle['excerpt']) !== '';
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------
// Searches use default page. This tag allows you to use different templates if searching
// -------------------------------------------------------------

function if_search($atts, $thing = null)
{
    global $pretext;

    $x = !empty($pretext['q']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_search_results($atts, $thing = null)
{
    global $pretext, $thispage, $is_article_list;

    if (empty($pretext['q']) || empty($thispage)) {
        return $is_article_list ? postpone_process() : '';
    }

    extract(lAtts(array(
        'min' => 1,
        'max' => 0,
    ), $atts));

    $results = (int) $thispage['grand_total'];

    $x = $results >= $min && (!$max || $results <= $max);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_category($atts, $thing = null)
{
    global $c, $context, $thiscategory;
    static $cache = array();

    extract(lAtts(array(
        'category' => false,
        'type'     => false,
        'name'     => false,
        'parent'   => 0
    ), $atts));

    if ($category === false) {
        $category = $c;
        $theType = $context;
    } elseif ($category === true) {
        $category = empty($thiscategory['name']) ? $c : $thiscategory['name'];
        $theType = empty($thiscategory['type']) ? $context : $thiscategory['type'];
    } else {
        $theType = $type && $type !== true ? validContext($type) : $context;
        ($parent || $type === false) or $parent = true;
    }

    if ($type && $theType !== $type) {
        $x = false;
    } elseif (!$parent || !$category) {
        $x = $name === false ? !empty($category) : in_list($category, $name);
    } else {
        if (!isset($cache[$theType.$category])) {
            $cache[$theType.$category] = array_reverse(array_slice(array_column(getTreePath($category, $theType), 'name'), 1));
        }

        $path = $cache[$theType.$category];
        $names = do_list_unique($name);

        if ($parent === true) {
            $x = $path && ($name === false || array_intersect($path, $names));
        } else {
            ($parent = (int)$parent) >= 0 or $parent = count($path) + $parent - 1;
            $x = isset($path[$parent]) && ($name === false || in_array($path[$parent], $names));
        }
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_article_category($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    extract(lAtts(array(
        'name'   => '',
        'number' => '',
    ), $atts));

    $cats = array();

    if ($number) {
        if (!empty($thisarticle['category'.$number])) {
            $cats = array($thisarticle['category'.$number]);
        }
    } else {
        if (!empty($thisarticle['category1'])) {
            $cats[] = $thisarticle['category1'];
        }

        if (!empty($thisarticle['category2'])) {
            $cats[] = $thisarticle['category2'];
        }

        $cats = array_unique($cats);
    }

    if ($name) {
        $cats = array_intersect(do_list($name), $cats);
    }

    $x = !empty($cats);

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_first_category($atts, $thing = null)
{
    global $thiscategory;

    assert_category();

    $x = !empty($thiscategory['is_first']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_last_category($atts, $thing = null)
{
    global $thiscategory;

    assert_category();

    $x = !empty($thiscategory['is_last']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_section($atts, $thing = null)
{
    global $s;

    extract(lAtts(array(
        'name' => false,
    ), $atts));

    $section = ($s == 'default' ? '' : $s);

    if ($section) {
        $x = $name === false || in_list($section, $name);
    } else {
        $x = $name !== false && (in_list('', $name) || in_list('default', $name));
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_article_section($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    extract(lAtts(array(
        'name' => '',
    ), $atts));

    $section = $thisarticle['section'];

    $x = in_list($section, $name);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_first_section($atts, $thing = null)
{
    global $thissection;

    assert_section();

    $x = !empty($thissection['is_first']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_last_section($atts, $thing = null)
{
    global $thissection;

    assert_section();

    $x = !empty($thissection['is_last']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function php($atts = null, $thing = null)
{
    global $is_article_body, $thisarticle, $prefs;

    $error = null;

    if (empty($is_article_body)) {
        if (empty($prefs['allow_page_php_scripting'])) {
            $error = 'php_code_disabled_page';
        }
    } else {
        if (!empty($prefs['allow_article_php_scripting'])) {
            if (!has_privs('article.php', $thisarticle['authorid'])) {
                $error = 'php_code_forbidden_user';
            }
        } else {
            $error = 'php_code_disabled_article';
        }
    }

    if ($thing !== null) {
        ob_start();

        if ($error) {
            trigger_error(gTxt($error));
        } else {
            eval($thing);
        }

        return ob_get_clean();
    }

    return empty($error);
}

// -------------------------------------------------------------

function txp_header($atts)
{
    if (!php()) {
        return;
    }

    extract(lAtts(array(
        'name'    => 'Content-Type',
        'replace' => true,
        'value'   => 'text/html; charset=utf-8'
    ), $atts));

    if ($name) {
        set_headers(array(strtolower($name) => $value), !empty($replace));
    }
}

// -------------------------------------------------------------

function custom_field($atts)
{
    global $is_article_body, $thisarticle;

    assert_article();

    extract(lAtts(array(
        'name'    => get_pref('custom_1_set'),
        'escape'  => null,
        'default' => '',
    ), $atts));

    $name = strtolower($name);

    if (!isset($thisarticle[$name])) {
        trigger_error(gTxt('field_not_found', array('{name}' => $name)), E_USER_NOTICE);

        return '';
    }

    if ($thisarticle[$name] !== '') {
        $out = $thisarticle[$name];
    } else {
        $out = $default;
    }

    $was_article_body = $is_article_body;
    $is_article_body = 1;
    $out = ($escape === null ? txpspecialchars($out) : parse($out));
    $is_article_body = $was_article_body;

    return $out;
}

// -------------------------------------------------------------

function if_custom_field($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    extract(lAtts(array(
        'name'      => get_pref('custom_1_set'),
        'value'     => null,
        'match'     => 'exact',
        'separator' => '',
    ), $atts));

    $name = strtolower($name);

    if (!isset($thisarticle[$name])) {
        trigger_error(gTxt('field_not_found', array('{name}' => $name)), E_USER_NOTICE);

        return '';
    }

    if ($value !== null) {
        switch ($match) {
            case '':
            case 'exact':
                $cond = ($thisarticle[$name] == $value);
                break;
            case 'any':
                $values = do_list($value);
                $cond = false;
                $cf_contents = ($separator) ? do_list($thisarticle[$name], $separator) : $thisarticle[$name];
                foreach ($values as $term) {
                    if ($term == '') {
                        continue;
                    }

                    $cond = is_array($cf_contents) ? in_array($term, $cf_contents) : ((strpos($cf_contents, $term) !== false) ? true : false);

                    // Short circuit if a match is found.
                    if ($cond) {
                        break;
                    }
                }
                break;
            case 'all':
                $values = do_list($value);
                $num_values = count($values);
                $term_count = 0;
                $cf_contents = ($separator) ? do_list($thisarticle[$name], $separator) : $thisarticle[$name];
                foreach ($values as $term) {
                    if ($term == '') {
                        continue;
                    }

                    $term_count += is_array($cf_contents) ? in_array($term, $cf_contents) : ((strpos($cf_contents, $term) !== false) ? true : false);
                }
                $cond = ($term_count == $num_values) ? true : false;
                break;
            case 'pattern':
                // Cannot guarantee that a fixed delimiter won't break preg_match
                // (and preg_quote doesn't help) so dynamically assign the delimiter
                // based on the first entry in $dlmPool that is NOT in the value
                // attribute. This minimises (does not eliminate) the possibility
                // of a TXP-initiated preg_match error, while still preserving
                // errors outside TXP's control (e.g. mangled user-submitted
                // PCRE pattern).
                $dlmPool = array('/', '@', '#', '~', '`', '|', '!', '%');
                $dlm = array_merge(array_diff($dlmPool, preg_split('//', $value, -1)));
                $dlm = (count($dlm) > 0) ? $dlm[0].$value.$dlm[0] : $value;
                $cond = preg_match($dlm, $thisarticle[$name]);
                break;
            default:
                trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'value')), E_USER_NOTICE);
                $cond = false;
        }
    } else {
        $cond = ($thisarticle[$name] !== '');
    }

    return isset($thing) ? parse($thing, !empty($cond)) : !empty($cond);
}

// -------------------------------------------------------------

function site_url()
{
    return hu;
}

// -------------------------------------------------------------

function error_message()
{
    global $txp_error_message;

    return $txp_error_message;
}

// -------------------------------------------------------------

function error_status()
{
    global $txp_error_status;

    return $txp_error_status;
}

// -------------------------------------------------------------

function if_status($atts, $thing = null)
{
    global $pretext, $txp_error_code;

    extract(lAtts(array(
        'status' => '200',
    ), $atts));

    $page_status = $txp_error_code
        ? $txp_error_code
        : $pretext['status'];

    $x = $status == $page_status;
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function page_url($atts)
{
    global $pretext;

    extract(lAtts(array(
        'type'    => 'request_uri',
        'default' => '',
        'escape'  => null
    ), $atts));

    if ($type == 'pg' && $pretext['pg'] == '') {
        return '1';
    }

    if (isset($pretext[$type])) {
        $out = $pretext[$type];
    } else {
        $out = gps($type, $default);
        $out = is_array($out) ? implode(',', $out) : $out;
    }

    return $escape === null ? txpspecialchars($out) : $out;
}

// -------------------------------------------------------------

function if_different($atts, $thing)
{
    static $last;

    $key = md5($thing);
    $out = parse($thing, 1);

    if (empty($last[$key]) || $out != $last[$key]) {
        return $last[$key] = $out;
    } else {
        return parse($thing, 0);
    }
}

// -------------------------------------------------------------

function if_first_article($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = !empty($thisarticle['is_first']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_last_article($atts, $thing = null)
{
    global $thisarticle;

    assert_article();

    $x = !empty($thisarticle['is_last']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_plugin($atts, $thing = null)
{
    global $plugins, $plugins_ver;

    extract(lAtts(array(
        'name'    => '',
        'version' => '',
    ), $atts));

    $x = @in_array($name, $plugins) && (!$version || version_compare($plugins_ver[$name], $version) >= 0);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function file_download_list($atts, $thing = null)
{
    global $s, $c, $context, $thisfile, $thispage, $pretext;

    extract(lAtts(array(
        'break'       => br,
        'category'    => '',
        'author'      => '',
        'realname'    => '',
        'auto_detect' => 'category, author',
        'class'       => __FUNCTION__,
        'form'        => 'files',
        'id'          => '',
        'pageby'      => '',
        'limit'       => 10,
        'offset'      => 0,
        'sort'        => 'filename asc',
        'wraptag'     => '',
        'status'      => STATUS_LIVE,
    ), $atts));

    if (!is_numeric($status)) {
        $status = getStatusNum($status);
    }

    // Note: status treated slightly differently.
    $where = $statwhere = array();
    $filters = isset($atts['id']) || isset($atts['category']) || isset($atts['author']) || isset($atts['realname']) || isset($atts['status']);
    $context_list = (empty($auto_detect) || $filters) ? array() : do_list_unique($auto_detect);
    $pageby = ($pageby == 'limit') ? $limit : $pageby;

    if ($category) {
        $where[] = "category IN ('".join("','", doSlash(do_list_unique($category)))."')";
    }

    $ids = array_map('intval', do_list_unique($id));

    if ($id) {
        $where[] = "id IN ('".join("','", $ids)."')";
    }

    if ($status) {
        $statwhere[] = "status = '".doSlash($status)."'";
    }

    if ($author) {
        $where[] = "author IN ('".join("','", doSlash(do_list_unique($author)))."')";
    }

    if ($realname) {
        $authorlist = safe_column("name", 'txp_users', "RealName IN ('".join("','", doArray(doSlash(do_list_unique($realname)), 'urldecode'))."')");
        if ($authorlist) {
            $where[] = "author IN ('".join("','", doSlash($authorlist))."')";
        }
    }

    // If no files are selected, try...
    if (!$where && !$filters) {
        foreach ($context_list as $ctxt) {
            switch ($ctxt) {
                case 'category':
                    // ...the global category in the URL.
                    if ($context == 'file' && !empty($c)) {
                        $where[] = "category = '".doSlash($c)."'";
                    }
                    break;
                case 'author':
                    // ...the global author in the URL.
                    if ($context == 'file' && !empty($pretext['author'])) {
                        $where[] = "author = '".doSlash($pretext['author'])."'";
                    }
                    break;
            }

            // Only one context can be processed.
            if ($where) {
                break;
            }
        }
    }

    if (!$where && !$statwhere && $filters) {
        // If nothing matches, output nothing.
        return '';
    }

    $where[] = "created <= ".now('created');

    $where = join(" AND ", array_merge($where, $statwhere));

    // Set up paging if required.
    if ($limit && $pageby) {
        $grand_total = safe_count('txp_file', $where);
        $total = $grand_total - $offset;
        $numPages = ($pageby > 0) ? ceil($total/$pageby) : 1;
        $pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
        $pgoffset = $offset + (($pg - 1) * $pageby);

        // Send paging info to txp:newer and txp:older.
        $pageout['pg']          = $pg;
        $pageout['numPages']    = $numPages;
        $pageout['s']           = $s;
        $pageout['c']           = $c;
        $pageout['context']     = 'file';
        $pageout['grand_total'] = $grand_total;
        $pageout['total']       = $total;

        if (empty($thispage)) {
            $thispage = $pageout;
        }
    } else {
        $pgoffset = $offset;
    }

    // Preserve order of custom file ids unless 'sort' attribute is set.
    if (!empty($atts['id']) && empty($atts['sort'])) {
        $safe_sort = "FIELD(id, ".join(',', $ids).")";
    } else {
        $safe_sort = doSlash($sort);
    }

    $qparts = array(
        "ORDER BY ".$safe_sort,
        ($limit) ? "LIMIT ".intval($pgoffset).", ".intval($limit) : '',
    );

    $rs = safe_rows_start("*", 'txp_file', $where.' '.join(' ', $qparts));

    if ($rs) {
        $count = 0;
        $last = numRows($rs);
        $out = array();

        while ($a = nextRow($rs)) {
            ++$count;
            $thisfile = file_download_format_info($a);
            $thisfile['is_first'] = ($count == 1);
            $thisfile['is_last'] = ($count == $last);

            $out[] = ($thing) ? parse($thing) : parse_form($form);

            $thisfile = '';
        }

        if ($out) {
            return doWrap($out, $wraptag, $break, $class);
        }
    }

    return '';
}

// -------------------------------------------------------------

function file_download($atts, $thing = null)
{
    global $thisfile;

    extract(lAtts(array(
        'filename' => '',
        'form'     => 'files',
        'id'       => '',
    ), $atts));

    $from_form = false;

    if ($id) {
        $thisfile = fileDownloadFetchInfo('id = '.intval($id).' and created <= '.now('created'));
    } elseif ($filename) {
        $thisfile = fileDownloadFetchInfo("filename = '".doSlash($filename)."' and created <= ".now('created'));
    } else {
        assert_file();

        $from_form = true;
    }

    if ($thisfile) {
        $out = ($thing) ? parse($thing) : parse_form($form);

        // Cleanup: this wasn't called from a form, so we don't want this
        // value remaining.
        if (!$from_form) {
            $thisfile = '';
        }

        return $out;
    }
}

// -------------------------------------------------------------

function file_download_link($atts, $thing = null)
{
    global $thisfile;

    extract(lAtts(array(
        'filename' => '',
        'id'       => '',
    ), $atts));

    $from_form = false;

    if ($id) {
        $thisfile = fileDownloadFetchInfo('id = '.intval($id).' and created <= '.now('created'));
    } elseif ($filename) {
        $thisfile = fileDownloadFetchInfo("filename = '".doSlash($filename)."' and created <= ".now('created'));
    } else {
        assert_file();

        $from_form = true;
    }

    if ($thisfile) {
        $url = filedownloadurl($thisfile['id'], $thisfile['filename']);

        $out = ($thing) ? href(parse($thing), $url) : $url;

        // Cleanup: this wasn't called from a form, so we don't want this
        // value remaining
        if (!$from_form) {
            $thisfile = '';
        }

        return $out;
    }
}

// -------------------------------------------------------------

function file_download_size($atts)
{
    global $thisfile;

    assert_file();

    extract(lAtts(array(
        'decimals' => 2,
        'format'   => '',
    ), $atts));

    if (is_numeric($decimals) && $decimals >= 0) {
        $decimals = intval($decimals);
    } else {
        $decimals = 2;
    }

    if (isset($thisfile['size'])) {
        $format_unit = strtolower(substr($format, 0, 1));

        return format_filesize($thisfile['size'], $decimals, $format_unit);
    } else {
        return '';
    }
}

// -------------------------------------------------------------

function file_download_created($atts)
{
    global $thisfile;

    assert_file();

    extract(lAtts(array(
        'format' => '',
    ), $atts));

    if ($thisfile['created']) {
        return fileDownloadFormatTime(array(
            'ftime'  => $thisfile['created'],
            'format' => $format,
        ));
    }
}

// -------------------------------------------------------------

function file_download_modified($atts)
{
    global $thisfile;

    assert_file();

    extract(lAtts(array(
        'format' => '',
    ), $atts));

    if ($thisfile['modified']) {
        return fileDownloadFormatTime(array(
            'ftime'  => $thisfile['modified'],
            'format' => $format,
        ));
    }
}

// -------------------------------------------------------------

function file_download_id()
{
    global $thisfile;

    assert_file();

    return $thisfile['id'];
}

// -------------------------------------------------------------

function file_download_name($atts)
{
    global $thisfile;

    assert_file();

    extract(lAtts(array(
        'title' => 0,
    ), $atts));

    return ($title) ? $thisfile['title'] : $thisfile['filename'];
}

// -------------------------------------------------------------

function file_download_category($atts)
{
    global $thisfile;

    assert_file();

    extract(lAtts(array(
        'title'   => 0
    ), $atts));

    if ($thisfile['category']) {
        $category = ($title)
            ? fetch_category_title($thisfile['category'], 'file')
            : $thisfile['category'];

        return $category;
    }
}

// -------------------------------------------------------------

function file_download_author($atts)
{
    global $thisfile, $s;

    assert_file();

    extract(lAtts(array(
        'link'         => 0,
        'title'        => 1,
        'section'      => '',
        'this_section' => ''
    ), $atts));

    if ($thisfile['author']) {
        $author_name = get_author_name($thisfile['author']);
        $display_name = txpspecialchars(($title) ? $author_name : $thisfile['author']);

        $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;

        $author = ($link)
            ? href($display_name, pagelinkurl(array('s' => $section, 'author' => $author_name, 'context' => 'file')))
            : $display_name;

        return $author;
    }
}

// -------------------------------------------------------------

function file_download_downloads()
{
    global $thisfile;

    assert_file();

    return $thisfile['downloads'];
}

// -------------------------------------------------------------

function file_download_description($atts)
{
    global $thisfile;

    assert_file();

    extract(lAtts(array(
        'escape'  => null
    ), $atts));

    if ($thisfile['description']) {
        return ($escape === null)
            ? txpspecialchars($thisfile['description'])
            : $thisfile['description'];
    }
}

// -------------------------------------------------------------

function hide($atts = array(), $thing = null)
{
    if (empty($atts)) {
        return '';
    }

    global $pretext;

    extract(lAtts(array('process' => null), $atts));

    if (is_numeric($process)) {
        if (intval($process) > $pretext['secondpass'] + 1) {
            return postpone_process($process);
        } else {
            return $process ? parse($thing) : '';
        }
    } elseif ($process) {
        parse($thing);
    }

    return '';
}

// -------------------------------------------------------------

function rsd()
{
    global $prefs;

    trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

    return ($prefs['enable_xmlrpc_server']) ? '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.hu.'rpc/" />' : '';
}

// -------------------------------------------------------------

function variable($atts, $thing = null)
{
    global $variable, $trace;

    $set = isset($atts['value']) || isset($thing) ? '' : null;

    extract(lAtts(array(
        'escape' => $set,
        'name'   => '',
        'value'  => $thing ? parse($thing) : $thing,
    ), $atts));

    if (empty($name)) {
        trigger_error(gTxt('variable_name_empty'));
    } elseif ($set === null) {
        if (isset($variable[$name])) {
            return $variable[$name];
        } else {
            $trace->log("[<txp:variable>: Unknown variable '$name']");
        }
    } else {
        $variable[$name] = $escape
            ? txp_escape(array('escape' => $escape), $value)
            : $value;
    }

    return '';
}

// -------------------------------------------------------------

function if_variable($atts, $thing = null)
{
    global $variable;

    extract(lAtts(array(
        'name'  => '',
        'value' => '',
    ), $atts));

    if (empty($name)) {
        trigger_error(gTxt('variable_name_empty'));

        return '';
    }

    if (isset($variable[$name])) {
        if (!isset($atts['value'])) {
            $x = true;
        } else {
            $x = $variable[$name] == $value;
        }
    } else {
        $x = false;
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function txp_eval($atts, $thing = null)
{
    global $txp_parsed, $txp_else, $txp_tag;
    static $xpath = null, $functions = null;

    extract(lAtts(array(
        'query' => null,
        'test'  => !isset($atts['query'])
    ), $atts));

    if (!isset($query)) {
        $x = true;
    } elseif (!($query = trim($query))) {
        $x = $query;
    } elseif (class_exists('DOMDocument')) {
        if (!isset($xpath)) {
            $xpath = new DOMXpath(new DOMDocument);
            $functions = do_list_unique(get_pref('txp_functions'));

            if ($functions) {
                $xpath->registerNamespace('php', 'http://php.net/xpath');
                $xpath->registerPHPFunctions($functions);
            }

            $functions = implode('|', $functions);
        }

        if ($functions) {
            $query = preg_replace('/\b('.$functions.')\s*\(/', "php:function('$1',", $query);
        }

        $x = $xpath->evaluate($query);

        if ($x instanceof DOMNodeList) {
            $x = $x->length;
        }
    } else {
        trigger_error('PHP DOM extension '.gTxt('gd_unavailable'));
        return '';
    }

    if (!isset($thing)) {
        return $x;
    } elseif (empty($x)) {
        return parse($thing, false);
    }

    $hash = sha1($thing);

    if (empty($txp_parsed[$hash]) || empty($txp_else[$hash])) {
        return $thing;
    }

    $test = trim($test);
    $isempty = !empty($test);
    $test = !$isempty || is_numeric($test) ? false : do_list_unique($test);
    $tag = $txp_parsed[$hash];
    $nr = $txp_else[$hash][0] - 2;
    $out = array($tag[0]);

    for ($tags = array(), $n = 1; $n <= $nr; $n++) {
        $txp_tag = $tag[$n];

        if ($test && !in_array($txp_tag[1], $test)) {
            $out[] = $txp_tag;
            $tags[] = $n;
        } else {
            $nextag = processTags($txp_tag[1], $txp_tag[2], $txp_tag[3]);
            $out[] = $nextag;
            $isempty &= trim($nextag) === '';
        }

        $out[] = $tag[++$n];
    }

    if ($isempty) {
        return parse($thing, false);
    }

    foreach ($tags as $n) {
        $txp_tag = $out[$n];
        $out[$n] = processTags($txp_tag[1], $txp_tag[2], $txp_tag[3]);
    }

    return implode('', $out);
}

// -------------------------------------------------------------

function txp_escape($atts, $thing = '')
{
    static $textile = null;

    extract(lAtts(array(
        'escape'    => ''
    ), $atts));

    $escape = $escape === true ? array('html') : do_list($escape);

    foreach ($escape as $attr) {
        switch ($attr = strtolower(trim($attr))) {
            case 'html':
                $thing = txpspecialchars($thing);
                break;
            case 'json':
                $thing = substr(json_encode($thing, TEXTPATTERN_JSON), 1, -1);
                break;
            case 'number':
                $thing = floatval($thing);
                break;
            case 'strip':
                $thing = strip_tags($thing);
                break;
            case 'upper': case 'lower':
                $function = function_exists('mb_strto'.$attr) ? 'mb_strto'.$attr : 'strto'.$attr;
                $thing = $function($thing);
                break;
            case 'trim': case 'ltrim': case 'rtrim': case 'intval':
                $thing = $attr($thing);
                break;
            case 'textile':
                if ($textile === null) {
                    $textile = Txp::get('\Textpattern\Textile\Parser');
                }

                $thing = $textile->TextileThis($thing);
                break;
        }
    }

    return $thing;
}

// -------------------------------------------------------------

function txp_wraptag($atts, $thing = '')
{
    extract(lAtts(array(
        'label'    => '',
        'labeltag' => '',
        'wraptag' => '',
        'class'   => '',
        'html_id' => ''
    ), $atts));

    $thing = $wraptag && trim($thing) !== '' ? doTag($thing, $wraptag, $class, '', '', $html_id) : $thing;

    return $label && trim($thing) !== '' ? doLabel($label, $labeltag).n.$thing : $thing;
}

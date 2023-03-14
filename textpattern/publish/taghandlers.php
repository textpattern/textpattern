<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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
    ->register('page_url')
    ->register('css')
    ->register('output_form')
    ->register('txp_yield', 'yield')
    ->register('txp_if_yield', 'if_yield')
    ->register('feed_link')
    ->register('link_feed_link')
    ->register(array('\Textpattern\Tag\Syntax\Link', 'linklist'))
    ->register(array('\Textpattern\Tag\Syntax\Link', 'link'))
    ->register(array('\Textpattern\Tag\Syntax\Link', 'linkdesctitle'))
    ->register(array('\Textpattern\Tag\Syntax\Link', 'link_name'))
    ->register(array('\Textpattern\Tag\Syntax\Link', 'link_url'))
    ->register(array('\Textpattern\Tag\Syntax\Link', 'link_author'))
    ->register(array('\Textpattern\Tag\Syntax\Link', 'link_description'))
    ->register(array('\Textpattern\Tag\Syntax\Link', 'link_category'))
    ->register(array('\Textpattern\Tag\Syntax\Link', 'link_id'))
    ->register('posted', array('link_date', array('type' => 'link', 'time' => 'date')))
    ->register('if_first', 'if_first_link', 'link')
    ->register('if_last', 'if_last_link', 'link')
    ->register('email')
    ->register('recent_articles')
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'recent_comments'))
    ->register('related_articles')
    ->register('popup')
    ->register('category_list')
    ->register('section_list')
    ->register('search_input')
    ->register('search_term')
    ->register('link_to', 'link_to_next')
    ->register('link_to', 'link_to_prev', 'prev')
    ->register('next_title')
    ->register('prev_title')
    ->register('site_name')
    ->register('site_slogan')
    ->register('link_to_home')
    ->register('txp_pager', 'newer', true)
    ->register('txp_pager', 'older', false)
    ->register('txp_pager', 'pages')
    ->register('text')
    ->register('article_id')
    ->register('article_url_title')
    ->register('if_article_id')
    ->register('posted')
    ->register('posted', array('modified', array('time' => 'modified')))
    ->register('posted', array('expires', array('time' => 'expires')))
    ->register('if_expires')
    ->register('if_expired')
    ->register('comments_invite')
    ->register('comments_count')
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comments_help'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_input'), 'comment_name_input')
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_input'), 'comment_email_input', 'email', 'clean_url')
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_input'), array('comment_web_input', array('placeholder' => 'http(s)://')), 'web', 'clean_url')
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_message_input'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_remember'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_preview'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_submit'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comments_form'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comments_error'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comments'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comments_preview'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_permlink'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_id'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_name'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_email'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_web'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_message'))
    ->register(array('\Textpattern\Tag\Syntax\Comment', 'comment_anchor'))
    ->register('posted', array('comment_time', array('type' => 'comment', 'time' => 'time')))
    ->register(array('\Textpattern\Tag\Syntax\Authors', 'renderAuthors'), 'authors')
    ->register('author')
    ->register('author_email')
    ->register('if_author')
    ->register('if_article_author')
    ->register('body')
    ->register('title')
    ->register('excerpt')
    ->register('article_category', 'category1')
    ->register('article_category', array('category2', array('number' => 2)))
    ->register('category')
    ->register('section')
    ->register('keywords')
    ->register('if_keywords')
    ->register('if_description')
    ->register('if_article_image')
    ->register('article_image')
    ->register(array('\Textpattern\Tag\Syntax\Search', 'search_result_title'))
    ->register(array('\Textpattern\Tag\Syntax\Search', 'search_result_excerpt'))
    ->register(array('\Textpattern\Tag\Syntax\Search', 'search_result_url'))
    ->register(array('\Textpattern\Tag\Syntax\Search', 'search_result_date'))
    ->register(array('\Textpattern\Tag\Syntax\Search', 'search_result_count'))
    ->register('items_count')
    ->register(array('\Textpattern\Tag\Syntax\Image', 'image'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'image_index'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'image_display'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'images'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'image_info'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'image_url'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'image_author'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'image_date'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'if_thumbnail'))
    ->register(array('\Textpattern\Tag\Syntax\Image', 'image'), array('thumbnail', array('thumbnail' => null)))
    ->register('if_first', 'if_first_image', 'image')
    ->register('if_last', 'if_last_image', 'image')
    ->register('if_comments')
    ->register('if_comments_preview')
    ->register('if_comments_error')
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
    ->register('if_first', 'if_first_category', 'category')
    ->register('if_last', 'if_last_category', 'category')
    ->register('if_section')
    ->register('if_article_section')
    ->register('if_first', 'if_first_section', 'section')
    ->register('if_last', 'if_last_section', 'section')
    ->register(array('\Textpattern\Tag\Syntax\Privacy', 'if_logged_in'))
    ->register(array('\Textpattern\Tag\Syntax\Privacy', 'password_protect'))
    ->register('if_request')
    ->register('hide')
    ->register('php')
    ->register('txp_header', 'header')
    ->register('custom_field')
    ->register('if_custom_field')
    ->register('site_url')
    ->register('error_message')
    ->register('error_status')
    ->register('if_status')
    ->register('if_different')
    ->register('if_first', 'if_first_article')
    ->register('if_last', 'if_last_article')
    ->register('if_plugin')
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_list'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_link'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_size'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_id'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_name'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_category'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_author'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_downloads'))
    ->register(array('\Textpattern\Tag\Syntax\File', 'file_download_description'))
    ->register('posted', array('file_download_created', array('type' => 'file', 'time' => 'created')))
    ->register('posted', array('file_download_modified', array('type' => 'file', 'time' => 'modified')))
    ->register('if_first', 'if_first_file', 'file')
    ->register('if_last', 'if_last_file', 'file')
    ->register('rsd')
    ->register('variable')
    ->register('if_variable')
    ->register('article')
    ->register('article_custom')
    ->register('txp_die')
    ->register('txp_eval', 'evaluate')
// Global attributes (false just removes unknown attribute warning)
    ->registerAttr(false, 'labeltag')
    ->registerAttr(true, 'class, html_id, not, breakclass, breakform, wrapform, evaluate')
    ->registerAttr('txp_escape', 'escape')
    ->registerAttr('txp_wraptag', 'wraptag, break, breakby, label, trim, replace, default, limit, offset, sort');

// -------------------------------------------------------------

function page_title($atts)
{
    global $parentid, $thisarticle, $q, $c, $author, $context, $s, $pg, $sitename;

    extract(lAtts(array('separator' => ' | '), $atts));

    $appending = $separator === '' ? txpspecialchars($separator.$sitename) : '';
    $parent_id = (int) $parentid;
    $pageStr = ($pg ? $separator.gTxt('page').' '.$pg : '');

    if ($parent_id) {
        $out = gTxt('comments_on').' '.escape_title(safe_field("Title", 'textpattern', "ID = $parent_id")).$appending;
    } elseif (isset($thisarticle['title'])) {
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

    $out = '';
    $format = strtolower(preg_replace('/\s+/', '', $format));
    list($mode, $format) = explode('.', $format.'.'.$format);

    if (has_handler('css.url')) {
        $url = callback_event('css.url', '', false, compact('name', 'theme'));
    } elseif ($mode === 'flat') {
        $url = array();
        $skin_dir = urlencode(get_pref('skin_dir'));

        foreach (do_list_unique($name) as $n) {
            $url[] = hu.$skin_dir.'/'.urlencode($theme).'/'.TXP_THEME_TREE['styles'].'/'.urlencode($n).'.css';
        }
    } else {
        $url = hu.'css.php?n='.urlencode($name).'&t='.urlencode($theme);
    }

    switch ($format) {
        case 'link':
            foreach ((array)$url as $href) {
                $out .= tag_void('link', array(
                    'rel'   => $rel,
                    'type'  => $doctype != 'html5' ? 'text/css' : '',
                    'media' => $media,
                    'title' => $title,
                    'href'  => $href,
                )).n;
            }
            break;
        default:
            $out .= txpspecialchars(is_array($url) ? implode(',', $url) : $url);
            break;
    }

    return $out;
}

// -------------------------------------------------------------

function component($atts)
{
    global $doctype, $pretext;
    static $mimetypes = null, $dir = null,
        $internals = array('id', 's', 'c', 'context', 'q', 'm', 'pg', 'p', 'month', 'author'),
        $defaults = array(
        'format'  => 'url',
        'form'    => '',
        'context' => null,
        'rel'     => '',
        'title'   => '',
    );

    extract(lAtts($defaults, $atts, false));

    if (empty($form)) {
        return;
    }

    $format = strtolower(preg_replace('/\s+/', '', $format));
    list($mode, $format) = explode('.', $format.'.'.$format);
    $out = '';
    $qs = get_context($context, $internals) + array_diff_key($atts, $defaults);

    if ($mode === 'flat') {
        if (!isset($mimetypes)) {
            $null = null;
            $mimetypes = get_mediatypes($null);
        }

        $url = array();
        $skin_dir = urlencode(get_pref('skin_dir'));

        foreach (do_list_unique($form) as $n) {
            $type = pathinfo($n, PATHINFO_EXTENSION);
            if (isset($mimetypes[$type])) {
                $url[] = hu.$skin_dir.'/'.$pretext['skin'].'/'.TXP_THEME_TREE['forms'].'/'.urlencode($type).'/'.urlencode($n).($qs ? join_qs($qs) : '');
            } else {
                $url[] = pagelinkurl(array('f' => $n) + $qs);
            }
        }
    } else {
        $url = pagelinkurl(array('f' => $form) + $qs);
    }

    switch ($format) {
        case 'url':
        case 'flat':
            $out .= is_array($url) ? implode(',', $url) : $url;
            break;
        case 'link':
            foreach ((array)$url as $href) {
                $out .= tag_void('link', array(
                    'rel'   => $rel,
                    'title' => $title,
                    'href'  => $href,
                )).n;
            }
            break;
        case 'script':
            foreach ((array)$url as $href) {
                $out .= tag(null, 'script', array(
                    'title' => $title,
                    'type'  => $doctype != 'html5' ? 'application/javascript' : '',
                    'src'  => $href,
                )).n;
            }
            break;
        case 'image':
            foreach ((array)$url as $href) {
                $out .= tag_void('img', array(
                    'title' => $title,
                    'src'  => $href,
                )).n;
            }
            break;
        default:
            foreach ((array)$url as $href) {
                $out .= href($title ? $title : $href, $href, array(
                    'rel'   => $rel,
                )).n;
            }
    }

    return $out;
}

// -------------------------------------------------------------

function output_form($atts, $thing = null)
{
    global $txp_atts, $yield, $txp_yield, $is_form;

    if (empty($atts['form'])) {
        trigger_error(gTxt('form_not_specified'));

        return '';
    } elseif (strpos($atts['form'], '|') === false) {
        $form = $atts['form'];
    } else {
        $form = do_list($atts['form'], '|');
        $form = $form[rand(0, count($form) - 1)];
    }

    $to_yield = isset($atts['yield']) ? $atts['yield'] : false;
    unset($atts['form'], $atts['yield'], $txp_atts['form'], $txp_atts['yield']);

    if ($form === true && empty($atts)) {
        return fetch_form(do_list_unique($to_yield));
    }

    if (!empty($to_yield)) {
        $to_yield = $to_yield === true ? $atts : array_fill_keys(do_list_unique($to_yield), null);
        empty($txp_atts) or $txp_atts = array_diff_key($txp_atts, $to_yield);
    }

    if (isset($atts['format'])) {// component
        empty($txp_atts) or $atts = array_diff_key($atts, $txp_atts);

        return component($atts + array('form' => $form));
    } elseif (is_array($to_yield)) {
        $atts = lAtts($to_yield, $atts) or $atts = array();
    }

    foreach ($atts as $name => $value) {
        if (!isset($txp_yield[$name])) {
            $txp_yield[$name] = array();
        }

        $txp_yield[$name][] = array($value, false);
    }

    $is_form++;
    $yield[] = $thing;
    $out = parse_form($form);
    array_pop($yield);
    $is_form--;

    foreach ($atts as $name => $value) {
        $result = array_pop($txp_yield[$name]);

        if (!empty($result[1])) {
            unset($txp_atts[$name]);
        }
    }

    return $out;
}

// -------------------------------------------------------------

function txp_yield($atts, $thing = null)
{
    global $yield, $txp_yield, $txp_atts, $txp_item, $is_form;

    extract(lAtts(array(
        'name'    => '',
        'else'    => false,
        'default' => false,
        'item'    => null
    ), $atts));

    if (isset($item)) {
        $inner = isset($txp_item[$item]) ? $txp_item[$item] : null;
    } elseif ($name === '') {
        $end = empty($yield) ? null : end($yield);

        if (isset($end)) {
            $was_form = $is_form;
            $is_form--;
            $inner = parse($end, empty($else));
            $is_form = $was_form;
        }
    } elseif (!empty($txp_yield[$name])) {
        list($inner) = end($txp_yield[$name]);
        $txp_yield[$name][key($txp_yield[$name])][1] = true;
    }

    if (!isset($inner)) {
        $escape = isset($txp_atts['escape']) ? $txp_atts['escape'] : null;
        $inner = $default !== false ?
            ($default === true ? page_url(array('type' => $name, 'escape' => $escape)) : $default) :
            ($thing ? parse($thing) : $thing);
    }

    return $inner;
}

function txp_if_yield($atts, $thing = null)
{
    global $yield, $txp_yield, $txp_item;

    extract(lAtts(array(
        'name'  => '',
        'else'  => false,
        'value' => null,
        'item'  => null
    ), $atts));

    if (isset($item)) {
        $inner = isset($txp_item[$item]) ? $txp_item[$item] : null;
    } elseif ($name === '') {
        $end = empty($yield) ? null : end($yield);

        if (isset($end)) {
            $inner = $value === null ? ($else ? getIfElse($end, false) : true) : parse($end, empty($else));
        }
    } elseif (empty($txp_yield[$name])) {
        $inner = null;
    } else {
        list($inner) = end($txp_yield[$name]);
    }

    return parse($thing, isset($inner) && ($value === null || (string)$inner === (string)$value || $inner && $value === true));
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
        'title'    => gTxt('rss_feed_title'),
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
        return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'"'.(get_pref('doctype') === 'html5' ? '>' : ' />');
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
        'class'    => __FUNCTION__,
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
        return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'"'.(get_pref('doctype') === 'html5' ? '>' : ' />');
    }

    $out = href($label, $url, array(
        'type'  => $type,
        'title' => $title,
    ));

    return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
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
            $linktext = Txp::get('\Textpattern\Mail\Encode')->entityObfuscateAddress($linktext);
        }

        return href(
            $linktext,
            Txp::get('\Textpattern\Mail\Encode')->entityObfuscateAddress('mailto:'.$email),
            ($title ? ' title="'.txpspecialchars($title).'"' : '')
        );
    }

    return '';
}

// -------------------------------------------------------------

function recent_articles($atts, $thing = null)
{
    global $prefs;

    $atts += array(
        'break'    => 'br',
        'class'    => __FUNCTION__,
        'form'     => '',
        'label'    => gTxt('recent_articles'),
        'labeltag' => '',
        'no_widow' => '',
    );

    if (!isset($thing) && !$atts['form']) {
        $thing = '<txp:permlink><txp:title no_widow="'.($atts['no_widow'] ? '1' : '').'" /></txp:permlink>';
    }

    unset($atts['no_widow']);

    return article_custom($atts, $thing);
}

// -------------------------------------------------------------

function related_articles($atts, $thing = null)
{
    global $thisarticle, $prefs, $txp_atts;

    assert_article();

    $globals = array(
        'break'    => br,
        'class'    => __FUNCTION__,
        'label'    => gTxt('related_articles'),
        'labeltag' => '',
    );

    $atts += $globals + array(
        'form'     => '',
        'match'    => 'Category',
        'no_widow' => '',
    );

    $txp_atts = (isset($txp_atts) ? $txp_atts : array()) + $globals;

    $match = array_intersect(do_list_unique(strtolower($atts['match'])), array_merge(array('category', 'category1', 'category2', 'author', 'keywords', 'section'), getCustomFields()));
    $categories = $cats = array();

    foreach ($match as $cf) {
        switch ($cf) {
            case 'category':
            case 'category1':
            case 'category2':
                foreach(($cf == 'category' ? array('category1', 'category2') : array($cf)) as $cat) {
                    if (!empty($thisarticle[$cat])) {
                        $cats[] = $thisarticle[$cat];
                    }
                }

                $categories[] = ucwords($cf);
                break;
            case 'author':
                $atts['author'] = $thisarticle['authorid'];
                break;
            case 'section':
                !empty($atts['section']) or $atts['section'] = $thisarticle['section'];
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
                n.'<noscript><div><input type="submit" value="'.gTxt('go').'"'.(get_pref('doctype') === 'html5' ? '>' : ' />').'</div></noscript>'.
                n.'</div>'.
                n.'</form>';
        }
    }
}

// -------------------------------------------------------------

// Output href list of site categories.
function category_list($atts, $thing = null, $cats = null)
{
    global $s, $c, $thiscategory;

    extract(lAtts(array(
        'active_class' => '',
        'break'        => br,
        'categories'   => null,
        'children'     => !isset($atts['categories']) ? 1 : (!empty($atts['parent']) ? true : 0),
        'class'        => __FUNCTION__,
        'exclude'      => '',
        'form'         => '',
        'html_id'      => '',
        'label'        => '',
        'labeltag'     => '',
        'limit'        => '',
        'link'         => '1',
        'offset'       => '',
        'parent'       => '',
        'section'      => '',
        'sort'         => isset($atts['categories']) ? '' : (!empty($atts['parent']) ? 'lft' : 'name'),
        'this_section' => 0,
        'type'         => 'article',
        'wraptag'      => '',
    ), $atts));

    $categories !== true or $categories = isset($thiscategory['name']) ? $thiscategory['name'] : ($c ? $c : 'root');
    $parent !== true or $parent = isset($thiscategory['name']) ? $thiscategory['name'] : ($c ? $c : 'root');
    isset($cats) or $cats = get_tree(compact('categories', 'parent', 'children', 'sort') + array('flatten' => false) + $atts);
    $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;
    $oldcategory = isset($thiscategory) ? $thiscategory : null;
    $out = array();
    $count = 0;
    $last = count($cats);
    $active_class = txpspecialchars($active_class);

    foreach ($cats as $name => $thiscategory) {
        $count++;
        $nodes = empty($thiscategory['children']) ? '' :
            category_list(array(
                'label'   => '',
                'html_id' => '',
            ) + $atts, $thing, $thiscategory['children']);

        unset($thiscategory['level'], $thiscategory['children']);

        if (!isset($thing) && !$form) {
            $cat = $link ? tag(txpspecialchars($thiscategory['title']), 'a',
                (($active_class && (0 == strcasecmp($c, $name))) ? ' class="'.$active_class.'"' : '').
                ' href="'.pagelinkurl(array(
                    's'       => $section,
                    'c'       => $name,
                    'context' => $type,
                )).'"'
            ) : $name;
        } else {
            $thiscategory['type'] = $type;
            $thiscategory['is_first'] = ($count == 1);
            $thiscategory['is_last'] = ($count == $last);

            if (isset($atts['section'])) {
                $thiscategory['section'] = $section;
            }

            $cat = $form ? parse_form($form) : parse($thing);
        }

        $out[] = $cat.$nodes;
    }

    $thiscategory = $oldcategory;

    return $out ?
        ($label ? doLabel($label, $labeltag) : '').doWrap($out, $wraptag, compact('break', 'class', 'html_id')) :
        ($thing ? parse($thing, false) : '');
}

// -------------------------------------------------------------

// Output href list of site sections.
function section_list($atts, $thing = null)
{
    global $s, $thissection;

    extract(lAtts(array(
        'active_class'    => '',
        'break'           => br,
        'class'           => __FUNCTION__,
        'default_title'   => get_pref('sitename'),
        'exclude'         => '',
        'filter'          => false,
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
    $sql_sort = sanitizeForSort($sort);
    $sql = array();

    if ($limit !== '' || $offset) {
        $sql_limit = " LIMIT ".intval($offset).", ".($limit === '' ? PHP_INT_MAX : intval($limit));
    }

    if ($sections === true) {
        $sql['page'] = '';
    } elseif ($sections) {
        if ($include_default) {
            $sections .= ', default';
        }

        $sections = quote_list(do_list_unique($sections), ',');
        $sql[] = " AND name IN ($sections)";

        if (!$sql_sort) {
            $sql_sort = "FIELD(name, $sections)";
        }
    } else {
        $sql['page'] = filterFrontPage('', 'page');
    }

    if ($filter) {
        foreach(do_list($filter) as $f) {
            $sql[$f] = filterFrontPage('', $f);
        }
    }

    if ($exclude === true) {
        $sql['searchable'] = " AND searchable";
    } elseif ($exclude) {
        $exclude = quote_list(do_list_unique($exclude), ',');
        $sql[] = " AND name NOT IN ($exclude)";
    }

    if (!$include_default) {
        $sql[] = " AND name != 'default'";
    }

    if (!$sql_sort) {
        $sql_sort = "name ASC";
    }

    if ($include_default) {
        $sql_sort = "name != 'default', ".$sql_sort;
    }

    $rs = safe_rows_start(
        "name, title, description",
        'txp_section',
        '1'.join('', $sql)." ORDER BY ".$sql_sort.$sql_limit
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
function search_input($atts, $thing = null)
{
    global $q, $permlink_mode, $doctype;
    static $outside = null;

    $inside = is_array($outside);

    extract(lAtts(array(
        'form'        => null,
        'wraptag'     => 'p',
        'class'       => __FUNCTION__,
        'size'        => '15',
        'html_id'     => '',
        'label'       => gTxt('search'),
        'aria_label'  => '',
        'placeholder' => '',
        'button'      => '',
        'section'     => '',
        'match'       => 'exact',
    ), $inside ? $atts + $outside : $atts));

    unset($atts['form']);

    if (!$inside && !isset($form) && !isset($thing) && empty($atts)) {
        $form = 'search_input';
    }

    if ($form && $form = fetch_form($form)) {
        $thing = $form;
    }

    if (isset($thing)) {
        $oldatts = $outside;
        $outside = $atts;
        $out = parse($thing);
        $outside = $oldatts;
    } else {
        $h5 = ($doctype === 'html5');
        $out = fInput(
            $h5 ? 'search' : 'text',
            array(
                'name'        => 'q',
                'aria-label'  => $aria_label,
                'placeholder' => $placeholder,
                'required'    => $h5,
                'size'        => $size,
                'class'       => $wraptag || empty($atts['class']) ? false : $class
            ),
            $q
        );
    }

    if ($form || $inside) {
        empty($atts['wraptag']) or $out = doTag($out, $wraptag, $class);

        return $out;
    }

    $sub = (!empty($button)) ? '<input type="submit" value="'.txpspecialchars($button).'"'.(get_pref('doctype') === 'html5' ? '>' : ' />') : '';
    $id =  (!empty($html_id)) ? ' id="'.txpspecialchars($html_id).'"' : '';

    $out = (!empty($label)) ? txpspecialchars($label).br.$out.$sub : $out.$sub;
    $out = ($match === 'exact') ? $out : hInput('m', txpspecialchars($match)).$out;
    $out = ($wraptag) ? doTag($out, $wraptag, $class) : $out;

    if (!$section) {
        return '<form role="search" method="get" action="'.hu.'"'.$id.'>'.
            n.$out.
            n.'</form>';
    }

    if ($permlink_mode != 'messy') {
        return '<form role="search" method="get" action="'.pagelinkurl(array('s' => $section)).'"'.$id.'>'.
            n.$out.
            n.'</form>';
    }

    return '<form role="search" method="get" action="'.hu.'"'.$id.'>'.
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

    return txpspecialchars($q);
}

// -------------------------------------------------------------

// Link to next/prev article, if it exists.
function link_to($atts, $thing = null, $target = 'next')
{
    global $thisarticle, $txp_context;
    static $lAtts = array(
        'form'       => '',
        'link'       => 1,
        'showalways' => 0
    );

    $atts += array('context' => empty($txp_context) ? true : null);
    extract($atts + $lAtts, EXTR_SKIP);

    if (!in_array($target, array('next', 'prev'))) {
        return '';
    }

    assert_article();
    $dir = $target == 'next' ? '>' : '<';

    if (!isset($thisarticle[$dir])) {
        $thisarticle += getNextPrev();
    }

    if ($thisarticle[$dir] !== false) {
        $oldarticle = $thisarticle;
        $thisarticle = $thisarticle[$dir];
        $url = permlink(array_diff_key($atts, $lAtts));

        if ($form || $thing !== null) {
            populateArticleData($thisarticle);
            $thisarticle['is_first'] = $thisarticle['is_last'] = true;
            $thing = $form ? parse_form($form) : parse($thing);
            $target_title = escape_title($thisarticle['Title']);

            $url = $link ? href(
                $thing,
                $url,
                ($target_title != $thing ? ' title="'.$target_title.'"' : '').
                ' rel="'.$target.'"'
            ) : $thing;
        }

        $thisarticle = $oldarticle;
    }

    unset($thisarticle[$dir]);

    return isset($url) ? $url : ($showalways ? parse($thing) : '');
}

// -------------------------------------------------------------

function next_title()
{
    global $thisarticle, $is_article_list;

    if (empty($thisarticle)) {
        return $is_article_list ? '' : null;
    }

    if (!isset($thisarticle['>'])) {
        $thisarticle += getNextPrev();
    }

    if ($thisarticle['>'] !== false) {
        return escape_title($thisarticle['>']['Title']);
    } else {
        return '';
    }
}

// -------------------------------------------------------------

function prev_title()
{
    global $thisarticle, $is_article_list;

    if (empty($thisarticle)) {
        return $is_article_list ? '' : null;
    }

    if (!isset($thisarticle['<'])) {
        $thisarticle += getNextPrev();
    }

    if ($thisarticle['<'] !== false) {
        return escape_title($thisarticle['<']['Title']);
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
    extract(lAtts(array('class' => false), $atts));

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

function txp_pager($atts, $thing = null, $newer = null)
{
    global $thispage, $is_article_list, $txp_context, $txp_item;
    static $pg = true, $numPages = null, $linkall = false, $top = 1, $shown = array();
    static $items = array('page' => null, 'total' => null, 'url' => null);

    $get = isset($atts['total']) && $atts['total'] === true;
    $set = $newer === null && (isset($atts['pg']) || isset($atts['total']) && !$get);
    $oldPages = $numPages;
    $oldpg = $pg;

    extract(lAtts($set ? array(
        'pg'         => $pg,
        'total'      => $numPages,
        'shift'      => 1,
        'showalways' => true,
        'link'       => false,
        ) : array(
        'showalways' => false,
        'title'      => '',
        'link'       => $linkall,
        'escape'     => 'html',
        'rel'        => '',
        'shift'      => false,
        'limit'      => 0,
        'wraptag'    => '',
        'break'      => '',
        'class'      => '',
        'html_id'    => '') +
        ($get ? array(
        'total'      => true,
        ) : array()), $atts));

    if ($set) {
        if (isset($total) && $total !== true) {
            $numPages = (int)$total;
        } elseif ($pg === true) {
            $numPages = isset($thispage['numPages']) ? (int)$thispage['numPages'] : null;
        }
    }

    if (!isset($numPages)) {
        if (isset($thispage['numPages'])) {
            $numPages = (int)$thispage['numPages'];
        } else {
            return $is_article_list ? postpone_process() : '';
        }
    }

    if ($set) {
        $oldtop = $top;
        $top = $shift === true ? 0 : ((int)$shift < 0 ? $numPages + $shift + 1 : $shift);
        $oldshown = $shown;
        $oldlink = $linkall;
        $linkall = $link;
        $shown = array();

        if ($thing !== null) {
            $thing = $numPages >= ($showalways ? (int)$showalways : 2) ? parse($thing) : '';
            $numPages = $oldPages;
            $pg = $oldpg;
            $top = $oldtop;
            $linkall = $oldlink;
            $shown = $oldshown;
        }

        return $thing;
    }

    $pgc = $pg === true ? 'pg' : $pg;
    $thispg = $pg === true && isset($thispage['pg']) ? $thispage['pg'] : intval(gps($pgc, $top));
    $thepg = max(1, min($thispg, $numPages));

    if ($get) {
        if ($thing === null && $shift === false) {
            return $newer === null ? $numPages : ($newer ? $thepg - 1 : $numPages - $thepg);
        } elseif ($shift === true || $shift === false) {
            if ($newer !== null) {
                $range = $newer ? $thepg - 1 : $numPages - $thepg;
            }
        } else {
            $range = (int)$shift;
        }
    }

    if (isset($range)) {
        if (!$range) {
            $pages = array();
        } elseif ($range > 0) {
            $pages = $newer === null ? range(-max($range, 2*$range + $thepg - $numPages), max($range, 2*$range - $thepg + 1)) :
                range($newer ? max($range, 2*$range + $thepg - $numPages) : 1, $newer ? 1 : max($range, 2*$range - $thepg + 1));
        } else {
            $pages = $newer !== null ? ($newer ? range(-1, -max(-$range, -2*$range + $thepg - $numPages)) : range(-max(-$range, -2*$range - $thepg + 1), -1)) :
                range(min(max(1 - $range - $thepg, 1 - 2*$range - $numPages), 0), max(0, min($numPages + $range - $thepg, $numPages + 2*$range - 1)));
        }
    } elseif (is_bool($shift)) {
        $pages = $newer === null ? ($shift ? range(1 - $thepg, $numPages - $thepg) : array(0)) : array($shift ? true : 1);
        $range = !$shift;
    } else {
        $pages = array_map('intval', do_list($shift, array(',', '-')));
        $range = false;
    }

    foreach ($items as $item => $val) {
        $items[$item] = isset($txp_item[$item]) ? $txp_item[$item] : null;
    }

    $txp_item['total'] = $numPages;
    $limit = $limit ? (int)$limit : -1;
    $old_context = $txp_context;
    $txp_context += get_context();
    $out = array();
    $rel_att = $rel === '' ? '' : ' rel="'.txpspecialchars($rel).'"';
    $class_att = $wraptag === '' && $class !== '' ? ' class="'.txpspecialchars($class).'"' : '';
    $id_att = $wraptag === '' && $html_id !== '' ? ' id="'.txpspecialchars($html_id).'"' : '';

    if ($title !== '') {
        $title_att = ' title="'.($escape == 'html' ? escape_title($title) :
            ($escape ? txp_escape($escape, $title) : $title)
        ).'"';
    } else {
        $title_att = '';
    }

    foreach ($pages as $page) {
        if ($newer === null) {
            $nextpg = $thepg + $page;
        } elseif ($newer) {
            $nextpg = $page === true ? 1 : ((int)$page < 0 ? -$page : $thepg - $page);
        } else {
            $nextpg = $page === true ? $numPages : ((int)$page < 0 ? $numPages + $page + 1 : $thepg + $page);
        }

        if (
            $nextpg >= ($newer === false && $range !== false ? $thepg + 1 : 1) &&
            $nextpg <= ($newer === true && $range !== false ? $thepg - 1 : $numPages)
        ) {
            if (empty($shown[$nextpg]) || $showalways) {
                $txp_context[$pgc] = $nextpg == $top ? null : $nextpg;
                $url = pagelinkurl($txp_context);
                $txp_item['page'] = $nextpg;
                $txp_item['url'] = $url;

                if ($shift !== false || $newer === null || !is_bool($range)) {
                    $shown[$nextpg] = true;
                    $limit--;
                }

                if (isset($thing)) {

                    $url = $link || $link === false && $nextpg != $thispg ? href(
                        parse($thing),
                        $url,
                        $id_att.$title_att.$rel_att.$class_att
                    ) : parse($thing);
                }
            } else {
                $url = false;
            }
        } else {
            $url = isset($thing) ? parse($thing, false) : false;
        }

        empty($url) or $out[] = $url;

        if (!$limit) {
            break;
        }
    }

    foreach ($items as $item => $val) {
        $txp_item[$item] = $val;
    }

    $txp_context = $old_context;

    return $wraptag !== '' ? doWrap($out, $wraptag, compact('break', 'class', 'html_id')) : doWrap($out, '', $break);
}

// -------------------------------------------------------------

function text($atts)
{
    extract(lAtts(array(
        'item'   => '',
        'escape' => null,
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

    return gTxt($item, $tags, isset($escape) ? '' : 'html');
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

    extract(lAtts(array('id' => $pretext['id']), $atts));

    assert_article();

    $x = $id && in_list($thisarticle['thisid'], $id);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function posted($atts, $thing = null)
{
    global $id, $c, $pg, $dateformat, $archive_dateformat, $comments_dateformat;

    extract(lAtts(array(
        'calendar' => '',
        'format'   => '',
        'gmt'      => '',
        'lang'     => '',
        'time'     => 'posted',
        'type' => 'article',
    ), $atts));

    assert_context($type);
    global ${'this'.$type};

    if ($time === true) {
        $time = time();
    } elseif (isset(${'this'.$type}[$time])) {
        $time = ${'this'.$type}[$time];
    }

    if (!is_numeric($time) && ($time = strtotime($time)) === false) {
        return '';
    }

    if ($calendar) {
        $lang = ($lang ? $lang : LANG)."@calendar=$calendar";
    }

    if (empty($format)) {
        switch ($type) {
            case 'article':
                $format = $id || $c || $pg ? $archive_dateformat : $dateformat;
                break;
            case 'file':
                $format = $archive_dateformat;
                break;
            case 'comment':
                $format = $comments_dateformat;
                break;
            default: $format = $dateformat;
        }
    }

    return safe_strftime($format, $time, $gmt, $lang);
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

    extract(lAtts(array(
        'date'  => 'expires',
        'time'  => null,
    ), $atts));

    switch ($date) {
        case 'expires':
        case 'posted':
        case 'modified':
            assert_article();
            $x = !empty($thisarticle[$date]) && ($thisarticle[$date] <= (isset($time) ? strtotime($time) : time()));
            break;
        default:
            $x = strtotime($date) <= (isset($time) ? strtotime($time) : time());
    }

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

    extract(lAtts(array(
        'class'      => __FUNCTION__,
        'showcount'  => true,
        'textonly'   => false,
        'showalways' => false,  // FIXME in crockery. This is only for BC.
        'wraptag'    => '',
    ), $atts));

    assert_article();

    $comments_invite = empty($thisarticle['comments_invite']) ? get_pref('comments_default_invite') : $thisarticle['comments_invite'];
    $comments_count = intval($thisarticle['comments_count']);
    $invite_return = '';

    if (($thisarticle['annotate'] || $comments_count) && ($showalways || $is_article_list)) {
        $comments_invite = txpspecialchars($comments_invite);
        $ccount = ($comments_count && $showcount) ?  ' ['.$comments_count.']' : '';

        if ($textonly) {
            $invite_return = $comments_invite.$ccount;
        } else {
            global $comments_mode;
            if (!$comments_mode) {
                $invite_return = doTag($comments_invite, 'a', $class, ' href="'.permlinkurl($thisarticle).'#'.gTxt('comment').'" ').$ccount;
            } else {
                $thisid = $thisarticle['thisid'];
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

    $fetchRealName = $link || $title || $format === 'url';

    if ($thisauthor) {
        $realname = $thisauthor['realname'];
        $name = $thisauthor['name'];
    } elseif ($author) {
        $name = $author;
    } else {
        assert_article();
        $name = $thisarticle['authorid'];
    }

    isset($realname) or $realname = $fetchRealName ? get_author_name($name) : $name;

    if ($title) {
        $display_name = $realname;
    } else {
        $display_name = $name;
    }

    if ($escape === 'html') {
        $display_name =  txpspecialchars($display_name);
    } elseif ($escape) {
        $display_name = txp_escape($escape, $display_name);
    }

    if (!$link && $format !== 'url') {
        return $display_name;
    }

    if ($this_section && $s != 'default') {
        $section = $s;
    }

    $href = pagelinkurl(array(
            's'      => $section,
            'author' => $realname,
        ));

    return $format === 'url' ? $href : href($display_name, $href, ' rel="author"');
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
        $display_email = $escape ? txp_escape($escape, $email) : $email;
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

    extract(lAtts(array('name' => ''), $atts));

    assert_article();

    $author = $thisarticle['authorid'];

    $x = $name ? in_list($author, $name) : (string) $author !== '';
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function txp_sandbox($atts = array(), $thing = null)
{
    static $articles = array(), $uniqid = null, $stack = array(), $depth = null;
    global $thisarticle, $is_article_body, $is_form, $txp_atts;

    isset($depth) or $depth = get_pref('form_circular_depth', 15);

    $id = isset($atts['id']) ? $atts['id'] : null;
    $field = isset($atts['field']) ? $atts['field'] : null;

    if (empty($id)) {
        assert_article();
        $id = $thisarticle['thisid'];
    } elseif (!isset($articles[$id])) {
        return;
    }

    if ($field) {
        if (!isset($stack[$id])) {
            $stack[$id] = 1;
        } elseif ($stack[$id] >= $depth) {
            trigger_error(gTxt('form_circular_reference', array(
                '{name}' => '<txp:article id="'.$id.'"/>'
            )));

            return '';
        } else {
            $stack[$id]++;
        }
    }

    $oldarticle = $thisarticle;
    isset($articles[$id]) and $thisarticle = $articles[$id];
    $was_article_body = $is_article_body;
    $is_article_body = $thisarticle['authorid'];
    $was_form = $is_form;
    $is_form = 0;

    $thing = parse(isset($thing) ? $thing : $thisarticle[$field]);

    $is_article_body = $was_article_body;
    $is_form = $was_form;
    $thisarticle = $oldarticle;

    $field and $stack[$id]--;

    if (!preg_match('@<(?:'.TXP_PATTERN.'):@', $thing)) {
        return $thing;
    }

    if (!isset($uniqid)) {
        $uniqid = 'sandbox_'.strtr(uniqid('', true), '.', '_');
        Txp::get('\Textpattern\Tag\Registry')->register('txp_sandbox', $uniqid);
    }

    $txp_atts = null;
    $atts['id'] = $id;
    unset($atts['field']);
    isset($articles[$id]) or $articles[$id] = $thisarticle;

    return "<txp:$uniqid".($atts ? join_atts($atts) : '').">{$thing}</txp:$uniqid>";
}

// -------------------------------------------------------------

function body($atts = array())
{
    return txp_sandbox(array('id' => null, 'field' => 'body') + $atts);
}

// -------------------------------------------------------------

function title($atts)
{
    global $thisarticle, $prefs;

    extract(lAtts(array(
        'escape'   => null,
        'no_widow' => '',
    ), $atts));

    assert_article();

    $t = $escape === null ? escape_title($thisarticle['title']) : $thisarticle['title'];

    if ($no_widow && $escape === null) {
        $t = noWidow($t);
    }

    return $t;
}

// -------------------------------------------------------------

function excerpt($atts = array())
{
    return txp_sandbox(array('id' => null, 'field' => 'excerpt') + $atts);
}

// -------------------------------------------------------------

function article_category($atts, $thing = null)
{
    global $thisarticle, $s, $permlink_mode;

    extract(lAtts(array(
        'number'       => 1,
        'class'        => '',
        'link'         => 0,
        'title'        => 0,
        'escape'       => true,
        'section'      => '',
        'this_section' => 0,
        'wraptag'      => '',
    ), $atts));

    assert_article();

    $cat = 'category'.intval($number);

    if (!empty($thisarticle[$cat])) {
        $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;
        $category = $thisarticle[$cat];

        $label = $title ? fetch_category_title($category) : $category;

        if ($thing) {
            $out = href(
                parse($thing),
                pagelinkurl(array(
                    's' => $section,
                    'c' => $category,
                )),
                (($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '').
                ($title ? ' title="'.txpspecialchars($label).'"' : '').
                ($permlink_mode != 'messy' ? ' rel="tag"' : '')
            );
        } else {
            if ($escape) {
                $label = txp_escape($escape, $label);
            }

            if ($link) {
                $out = href(
                    $label,
                    pagelinkurl(array(
                        's' => $section,
                        'c' => $category,
                    )),
                    ($permlink_mode != 'messy' ? ' rel="tag"' : '')
                );
            } else {
                $out = $label;
            }
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
        $type = validContext($type);
    } elseif (!empty($thiscategory['name'])) {
        $category = $thiscategory['name'];
        $type = $thiscategory['type'];
    } else {
        $category = $c;
        $type = $context;
    }

    if ($category) {
        if ($this_section) {
            $section = ($s == 'default' ? '' : $s);
        } elseif (isset($thiscategory['section'])) {
            $section = $thiscategory['section'];
        }

        $label = txpspecialchars(($title) ? fetch_category_title($category, $type) : $category);

        $href = pagelinkurl(array(
            's'       => $section,
            'c'       => $category,
            'context' => $type,
        ));

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

    return txpspecialchars($thisarticle['keywords']);
}

// -------------------------------------------------------------

function if_keywords($atts, $thing = null)
{
    global $thisarticle;

    extract(lAtts(array('keywords' => ''), $atts));

    assert_article();

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
    global $doctype, $thisarticle, $txp_atts;
    static $tagAtts = array(
        'range'     => '1',
        'title'     => '',
        'class'     => '',
        'html_id'   => '',
        'width'     => '',
        'height'    => '',
        'thumbnail' => 0,
        'wraptag'   => '',
        'break'     => '',
        'loading'   => null,
    );

    $extAtts = join_atts(array_diff_key($atts, $tagAtts + ($txp_atts ? $txp_atts : array())), TEXTPATTERN_STRIP_EMPTY_STRING|TEXTPATTERN_STRIP_TXP);
    $atts = array_intersect_key($atts, $tagAtts);

    extract(lAtts($tagAtts, $atts));

    assert_article();

    if ($thisarticle['article_image']) {
        $images = do_list_unique($thisarticle['article_image'], array(',', '-'));
    }

    if (empty($images)) {
        return '';
    }

    $out = array();
    
    if ($range === true) {
        $items = array_keys($images);
    } else {
        $n = count($images);
        $items = array();

        foreach (do_list($range) as $item) {
            if (is_numeric($item)) {
                $items[] = $item > 0 ? $item - 1 : $n + $item;
            } elseif (preg_match('/^([-+]?\d+)\s*(?:\-|\.{2})\s*([-+]?\d+)$/', $item, $match)) {
                list($item, $start, $stop) = $match;
                $start = $start > 0 ? $start - 1 : $n + $start;
                $stop = $stop > 0 ? $stop - 1 : $n + $stop;
                $items = array_merge($items, range($start, $stop));
            }
        }

        $images = array_intersect_key($images, array_flip($items));
    }

    $dbimages = array_map('intval', array_filter($images, 'is_numeric'));
    $dbimages = empty($dbimages) ? array() :
        array_column(safe_rows('*', 'txp_image', 'id IN('.implode(',', $dbimages).')'), null, 'id');

    foreach ($items as $item) if (isset($images[$item])) {
        $image = $images[$item];

        if (is_numeric($image)) {
            if (!isset($dbimages[$image])) {
                trigger_error(gTxt('unknown_image'));

                continue;
            }

            $rs = $dbimages[$image];

            if ($thumbnail && empty($rs['thumbnail'])) {
                continue;
            }

            $w = $width !== '' ? $width : $rs[$thumbnail ? 'thumb_w' :'w'];
            $h = $height !== '' ? $height : $rs[$thumbnail ? 'thumb_h' :'h'];

            extract($rs, EXTR_SKIP);

            if ($title === true) {
                $title = $caption;
            }

            $img = '<img src="'.imagesrcurl($id, $ext, !empty($atts['thumbnail'])).
                '" alt="'.txpspecialchars($alt, ENT_QUOTES, 'UTF-8', false).'"'.
                ($title ? ' title="'.txpspecialchars($title, ENT_QUOTES, 'UTF-8', false).'"' : '');
        } else {
            $w = $width !== '' ? $width : 0;
            $h = $height !== '' ? $height : 0;
            $img = '<img src="'.txpspecialchars($image).'" alt=""'.
                ($title && $title !== true ? ' title="'.txpspecialchars($title).'"' : '');
        }

        if ($loading && $doctype === 'html5' && in_array($loading, array('auto', 'eager', 'lazy'))) {
            $img .= ' loading="'.$loading.'"';
        }

        $img .=
            (($html_id && !$wraptag) ? ' id="'.txpspecialchars($html_id).'"' : '').
            (($class && !$wraptag) ? ' class="'.txpspecialchars($class).'"' : '').
            ($w ? ' width="'.(int) $w.'"' : '').
            ($h ? ' height="'.(int) $h.'"' : '').
            $extAtts.
            (get_pref('doctype') === 'html5' ? '>' : ' />');

            $out[] = $img;
    }

    return $wraptag ? doWrap($out, $wraptag, compact('break', 'class', 'html_id')) : implode($break, $out);
}

// -------------------------------------------------------------

function items_count($atts)
{
    global $thispage;

    if (empty($thispage)) {
        return postpone_process();
    }

    extract(lAtts(array(
        'text'   => null,
        'pageby' => 1,
    ), $atts));

    $by = (int)$pageby or $by = 1;
    $t = ceil($thispage[$pageby === true ? 'numPages' : 'grand_total']/$by);

    if (!isset($text)) {
        $text = $pageby === true || $by > 1 ? gTxt($t == 1 ? 'page' : 'pages') : gTxt($t == 1 ? 'article_found' : 'articles_found');
    }

    return $t.($text ? ' '.$text : '');
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

function if_comments_preview($atts, $thing = null)
{
    $x = ps('preview') && checkCommentsAllowed(gps('parentid'));
    return isset($thing) ? parse($thing, $x) : $x;
}
    
// -------------------------------------------------------------

function if_comments_error($atts, $thing = null)
{
    $evaluator = & get_comment_evaluator();

    $x = (count($evaluator->get_result_message()) > 0);
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
    global $is_article_list, $pretext;
    static $defaults = array('s', 'c', 'q', 'month', 'author');

    $x = ($is_article_list == true);

    if ($x && !empty($atts)) {
        extract(lAtts(array('type' => ''), $atts));

        foreach ($type === true ? $defaults : do_list_unique($type) as $q) {
            switch ($q) {
                case 's':
                    $x = !empty($pretext['s']) && $pretext['s'] != 'default';
                    break;
                default:
                    $x = !empty($pretext[$q]) || !isset($pretext[$q]) && gps($q);
            }

            if ($x) {
                break;
            }
        }
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

/**
 * Determines if there is meta description content in the given context.
 *
 * @param  array  $atts  Tag attributes
 * @param  string $thing Tag container content
 * @return string
 */

function if_description($atts, $thing = null)
{
    extract(lAtts(array('type' => null), $atts));

    $content = getMetaDescription($type);
    $x = !empty($content);

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

/**
 * Returns article, section or category meta description info.
 *
 * @param  array  $atts Tag attributes
 * @return string
 */

function meta_description($atts)
{
    extract(lAtts(array(
        'escape' => true,
        'format' => 'meta', // or empty for raw value
        'type'   => null,
    ), $atts));

    $out = '';
    $content = getMetaDescription($type);

    if ($content) {
        $content = ($escape === true) ? txpspecialchars($content) : txp_escape($escape, $content);

        if ($format === 'meta') {
            $out = '<meta name="description" content="'.$content.'"'.(get_pref('doctype') === 'html5' ? '>' : ' />');
        } else {
            $out = $content;
        }
    }

    return $out;
}

// -------------------------------------------------------------

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
        'escape'    => true,
        'format'    => 'meta', // or empty for raw value
        'separator' => null,
    ), $atts));

    $out = '';

    if ($id_keywords) {
        $content = ($escape === true) ? txpspecialchars($id_keywords) : txp_escape($escape, $id_keywords);

        if ($separator !== null) {
            $content = implode($separator, do_list($content));
        }

        if ($format === 'meta') {
            // Can't use tag_void() since it escapes its content.
            $out = '<meta name="keywords" content="'.$content.'"'.(get_pref('doctype') === 'html5' ? '>' : ' />');
        } else {
            $out = $content;
        }
    }

    return $out;
}

// -------------------------------------------------------------

function meta_author($atts)
{
    global $id_author;

    extract(lAtts(array(
        'escape' => true,
        'format' => 'meta', // or empty for raw value
        'title'  => 0,
    ), $atts));

    $out = '';

    if ($id_author) {
        $display_name = ($title) ? get_author_name($id_author) : $id_author;
        $display_name = ($escape === true) ? txpspecialchars($display_name) : txp_escape($escape, $display_name);

        if ($format === 'meta') {
            // Can't use tag_void() since it escapes its content.
            $out = '<meta name="author" content="'.$display_name.'"'.(get_pref('doctype') === 'html5' ? '>' : ' />');
        } else {
            $out = $display_name;
        }
    }

    return $out;
}

// -------------------------------------------------------------

function permlink($atts, $thing = null)
{
    global $thisarticle, $txp_context;
    static $lAtts = array(
        'class'   => '',
        'id'      => '',
        'style'   => '',
        'title'   => '',
        'context' => null,
    );

    $old_context = $txp_context;

    if (!isset($atts['context'])) {
        if (empty($txp_context)) {
            $atts = lAtts($lAtts, $atts);
        } else {
            $atts = lAtts($lAtts + $txp_context, $atts);
            $txp_context = array_intersect_key($atts, $txp_context);
        }
    } elseif ($atts['context'] === true) {
        $atts = lAtts($lAtts, $atts);
    } else {
        $extralAtts = array_fill_keys(do_list_unique($atts['context']), null);
        $atts = lAtts($lAtts + $extralAtts, $atts);
        $extralAtts = array_intersect_key($atts, $extralAtts);
    }

    $id = $atts['id'];

    if (!$id && empty($thisarticle)) {
        return;
    }

    $txp_context = get_context(isset($extralAtts) ? $extralAtts : $atts['context']);
    $url = $id ? permlinkurl_id($id) : permlinkurl($thisarticle);
    $txp_context = $old_context;

    if ($url) {
        if ($thing === null) {
            return $url;
        }

        return tag((string)parse($thing), 'a', array(
            'rel'   => 'bookmark',
            'href'  => $url,
            'title' => $atts['title'],
            'style' => $atts['style'],
            'class' => $atts['class'],
        ));
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
    global $c, $s, $sitename, $thiscategory, $context;

    extract(lAtts(array(
        'type'      => $context,
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

    $content = array();
    $label = txpspecialchars($label);
    $type = $type === true ? $context : validContext($type);
    $section != 'default' or $section = '';

    if ($link && $label) {
        $label = doTag($label, 'a', $linkclass, ' href="'.hu.'"');
    }

    if (!empty($section)) {
        $section_title = ($title) ? fetch_section_title($section) : $section;
        $section_title_html = escape_title($section_title);
        $content[] = ($link)
            ? (doTag($section_title_html, 'a', $linkclass, ' href="'.pagelinkurl(array('s' => $s)).'"'))
            : $section_title_html;
    }

    if (!$category) {
        $catpath = array();
    } else {
        $catpath = array_reverse(getRootPath($category, $type));
    }

    if ($limit || $offset) {
        $offset = (int)$offset < 0 ? (int)$offset - 1 : (int)$offset;
        $catpath = array_slice($catpath, $offset, isset($limit) ? (int)$limit : null);
    }

    $oldcategory = isset($thiscategory) ? $thiscategory : null;

    foreach ($catpath as $thiscategory) {
        $category_title_html = isset($thing) ? parse($thing) : ($title ? escape_title($thiscategory['title']) : $thiscategory['name']);
        $content[] = ($link)
            ? doTag($category_title_html, 'a', $linkclass, ' href="'.pagelinkurl(array(
                'c'       => $thiscategory['name'],
                'context' => $type,
                's'       => $section
            )).'"')
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

    $x = trim((string)$thisarticle['excerpt']) !== '';
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

    extract(lAtts(array(
        'category' => false,
        'type'     => false,
        'name'     => false,
        'parent'   => 0,
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
        $category = trim($category);
    }

    if ($type && $type !== true && $theType !== $type) {
        $x = false;
    } else {
        $parentname = $parent && is_numeric((string)$parent);
        $x = $name === false ? !empty($category) : $parentname || in_list($category, $name);
    }

    if ($x && $parent && $category) {
        $path = array_column(getRootPath($category, $theType), 'name');

        if (!$parentname) {
            $name = $parent;
            $parent = true;
        }

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

    extract(lAtts(array(
        'name'   => '',
        'number' => '',
    ), $atts));

    assert_article();

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

function if_section($atts, $thing = null)
{
    global $s, $thissection, $txp_sections;

    extract(lAtts(array('filter' => false, 'name' => false, 'section' => false), $atts));

    if ($section === true) {
        $section = isset($thissection) ? $thissection['name'] : $s;
    } elseif ($section === false) {
        $section = $s;
    }

    $section !== 'default' or $section = '';
    is_bool($name) or $name = do_list($name);

    if ($section) {
        $x = $name === true ? !empty($txp_sections[$section]['page']) : ($name === false || in_array($section, $name));
    } else {
        $x = $filter || is_array($name) && (in_array('', $name) || in_array('default', $name));
    }

    if ($x && $filter) {
        foreach(do_list($filter) as $f) {
            if (empty($section ? $txp_sections[$section][$f] : array_filter(array_column($txp_sections, $f)))) {
                $x = false;
                break;
            }
        }
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_article_section($atts, $thing = null)
{
    global $thisarticle, $txp_sections;

    extract(lAtts(array('filter' => false, 'name' => ''), $atts));

    assert_article();

    $section = $thisarticle['section'];

    $x = $name === true ? !empty($txp_sections[$section]['page']) : in_list($section, $name);

    if ($x && $filter) {
        foreach(do_list($filter) as $f) {
            if (empty($txp_sections[$section][$f])) {
                $x = false;
                break;
            }
        }
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function hide($atts = array(), $thing = null)
{
    if (!isset($atts['process'])) {
        return '';
    }

    global $pretext;

    extract(lAtts(array('process' => null), $atts));

    if (!$process) {
        return trim((string)$process) === '' && $pretext['secondpass'] < (int)get_pref('secondpass', 1) ? postpone_process() : $thing;
    } elseif (is_numeric($process)) {
        return abs($process) > $pretext['secondpass'] + 1 ?
            postpone_process($process) :
            ($process > 0 ? parse($thing) : '<txp:hide>'.parse($thing).'</txp:hide>');
    } elseif ($process) {
        parse($thing);
    }

    return '';
}

// -------------------------------------------------------------

function php($atts = null, $thing = null, $priv = null)
{
    global $is_article_body, $is_form;

    $error = null;

    if ($priv) {
        $error = $is_article_body && !$is_form && !has_privs($priv, $is_article_body);
    } elseif (!$is_article_body || $is_form) {
        if (!get_pref('allow_page_php_scripting')) {
            $error = 'php_code_disabled_page';
        }
    } elseif (!get_pref('allow_article_php_scripting')) {
        $error = 'php_code_disabled_article';
    } elseif (!has_privs('article.php', $is_article_body)) {
        $error = 'php_code_forbidden_user';
    }

    if ($thing !== null) {
        ob_start();

        if ($error) {
            trigger_error(gTxt($error));
        } else {
            empty($atts) or extract($atts, EXTR_SKIP);
            eval($thing);
        }

        return ob_get_clean();
    }

    return empty($error);
}

// -------------------------------------------------------------

function txp_header($atts)
{
    extract(lAtts(array(
        'name'    => isset($atts['value']) ? '' : 'Content-Type',
        'replace' => 1,
        'value'   => isset($atts['name']) ? true : 'text/html; charset=utf-8'
    ), $atts));

    $out = set_headers(array($name => $value), $replace);

    return $out ? doWrap($out) : null;
}

// -------------------------------------------------------------

function custom_field($atts = array())
{
    global $thisarticle;

    extract(lAtts(array(
        'name'    => get_pref('custom_1_set'),
        'escape'  => null,
        'default' => '',
    ), $atts));

    assert_article();

    $name = strtolower($name);

    if (!isset($thisarticle[$name])) {
        trigger_error(gTxt('field_not_found', array('{name}' => $name)), E_USER_NOTICE);

        return '';
    }

    $thing = $thisarticle[$name] !== '' ? $thisarticle[$name] : $default;

    return $escape === null ? txpspecialchars($thing) : txp_sandbox(array('id' => null, 'field' => $name) + $atts, $thing);
}

// -------------------------------------------------------------

function if_custom_field($atts, $thing = null)
{
    global $thisarticle;

    extract($atts = lAtts(array(
        'name'      => get_pref('custom_1_set'),
        'value'     => null,
        'match'     => 'exact',
        'separator' => '',
    ), $atts));

    assert_article();

    $name = strtolower($name);

    if (!isset($thisarticle[$name])) {
        trigger_error(gTxt('field_not_found', array('{name}' => $name)), E_USER_NOTICE);

        return '';
    }

    if ($value !== null) {
        $cond = txp_match($atts, $thisarticle[$name]);
    } else {
        $cond = ($thisarticle[$name] !== '');
    }

    return isset($thing) ? parse($thing, !empty($cond)) : !empty($cond);
}

// -------------------------------------------------------------

function site_url($atts)
{
    extract(lAtts(array(
        'type' => '',
    ), $atts));

    return $type === 'admin' ? ahu : hu;
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

    extract(lAtts(array('status' => '200'), $atts));

    $page_status = $txp_error_code
        ? $txp_error_code
        : $pretext['status'];

    $x = $status == $page_status;
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function page_url($atts, $thing = null)
{
    global $prefs, $pretext, $txp_context;
    static $specials = null,
        $internals = array('id', 's', 'c', 'context', 'q', 'm', 'p', 'month', 'author', 'f'),
        $lAtts = array(
            'type'    => null,
            'default' => false,
            'escape'  => null,
            'context' => null,
            'root'    => hu
        );

    isset($specials) or $specials = array(
        'admin_root'  => ahu,
        'images_root' => ihu.get_pref('img_dir'),
        'themes_root' => hu.get_pref('skin_dir'),
        'theme_path'  => hu.get_pref('skin_dir').'/'.$pretext['skin'],
        'theme'       => $pretext['skin'],
    );

    $old_context = $txp_context;
    $old_base = isset($prefs['url_base']) ? $prefs['url_base'] : null;

    if (!isset($atts['context'])) {
        if (empty($txp_context)) {
            $atts = lAtts($lAtts, $atts);
        } else {
            $atts = lAtts($lAtts + $txp_context, $atts);
            $txp_context = array_intersect_key($atts, $txp_context);
        }
    } elseif ($atts['context'] === true) {
        $atts = lAtts($lAtts, $atts);
    } else {
        $extralAtts = array_fill_keys(do_list_unique($atts['context']), null);
        $atts = lAtts($lAtts + $extralAtts, $atts);
        $extralAtts = array_intersect_key($atts, $extralAtts);
    }

    extract($atts, EXTR_SKIP);

    $prefs['url_base'] = $root === true ? rhu : $root;
    $txp_context = get_context(isset($extralAtts) ? $extralAtts : $context, $internals);

    if ($default !== false) {
        if ($default === true) {
            if (isset($type)) {
                unset($txp_context[$type]);
            } else {
                $txp_context = array();
            }
        } elseif (!isset($txp_context[$type])) {
            $txp_context[$type] = $default;
        }
    }

    if (!isset($type)) {
        $type = 'request_uri';
    }

    if (isset($thing)) {
        $out = parse($thing);
    } elseif (isset($context)) {
        $out = pagelinkurl($txp_context);
        $escape === null or $out = str_replace('&amp;', '&', $out);
    } elseif (isset($specials[$type])) {
        $out = $specials[$type];
    } elseif ($type == 'pg' && $pretext['pg'] == '') {
        $out = '1';
    } elseif (isset($pretext[$type]) && is_bool($default)) {
        $out = $type == 's' && $pretext['s'] == 'default' ? '' : $pretext[$type];
        $escape === null or $out = txpspecialchars($out);
    } else {
        $out = gps($type, $default);
        !is_array($out) or $out = implode(',', $out);
        $escape !== null or $out = txpspecialchars($out);
    }

    $txp_context = $old_context;
    $prefs['url_base'] = $old_base;

    return $out;
}

// -------------------------------------------------------------

function if_different($atts, $thing = null)
{
    static $last, $tested;

    extract(lAtts(array(
        'test'    => null,
        'not'     => '',
        'id'      => null
    ), $atts));

    $key = isset($id) ? $id : txp_hash($thing);
    $out = isset($test) ? $test : parse($thing);

    if (isset($test)) {
        if ($different = !isset($tested[$key]) || $out != $tested[$key]) {
            $tested[$key] = $out;
        }
    } else {
        if ($different = !isset($last[$key]) || $out != $last[$key]) {
            $last[$key] = $out;
        }
    }

    $condition = $not ? !$different : $different;

    return isset($test) ?
        parse($thing, $condition) :
        ($condition ? $out : parse($thing, false));
}

// -------------------------------------------------------------

function if_first($atts, $thing = null, $type = 'article')
{
    global ${"this$type"};

    $x = !empty(${"this$type"}['is_first']);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_last($atts, $thing = null, $type = 'article')
{
    global ${"this$type"};

    $x = !empty(${"this$type"}['is_last']);
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

    $x = empty($name) ? version_compare(get_pref('version'), $version) >= 0 :
        $plugins && in_array($name, $plugins) && (!$version || version_compare($plugins_ver[$name], $version) >= 0);
    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function rsd()
{
    global $prefs;

    trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

    return ($prefs['enable_xmlrpc_server']) ? '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.hu.'rpc/"'.(get_pref('doctype') === 'html5' ? '>' : ' />') : '';
}

// -------------------------------------------------------------

function variable($atts, $thing = null)
{
    global $variable, $trace;

    $set = isset($thing) || isset($atts['value']) || isset($atts['add']) || isset($atts['reset']) ? false : null;

    extract(lAtts(array(
        'name'      => '',
        'value'     => null,
        'default'   => false,
        'add'       => null,
        'reset'     => null,
        'separator' => null,
        'output'    => null,
    ), $atts));

    $var = isset($variable[$name]) ? $variable[$name] : null;

    if (empty($name)) {
        trigger_error(gTxt('variable_name_empty'));
    } elseif ($set === null && !isset($var) && !isset($output)) {
        $trace->log("[<txp:variable>: Unknown variable '$name']");
    } else {
        if ($add === true) {
            empty($thing) or $thing = parse($thing);

            if (!isset($value)) {
                $add = isset($thing) ? $thing : 1;
            } elseif ($value === true) {
                $add = $var;
                !isset($thing) or $var = $thing;
            } else {
                $add = isset($thing) ? $thing : $var;
                $var = $value;
            }
        } elseif ($value === true) {
            isset($var) or $var = (isset($thing) ? parse($thing) : null);
        } else {
            $var = isset($value) ?
                $value :
                (isset($thing) ? parse($thing) : $var);
        }
    }

    if ($default !== false && trim((string)$var) === '') {
        $var = $default;
    }

    if (isset($add) && $add !== '') {
        if (!isset($separator) && is_numeric($add) && (empty($var) || is_numeric($var))) {
            $var += $add;
        } else {
            $var .= ($var ? $separator : '').$add;
        }
    }

    if ($set !== null) {
        global $txp_atts;

        if ($txp_atts) {
            $var = txp_wraptag($txp_atts, $var);
        }

        if (isset($reset)) {
            $variable[$name] = $reset === true ? null : $reset;
            isset($output) or $output = 1;
        } else {
            $variable[$name] = $var;
        }
    } else {
        isset($output) or $output = 1;
    }

    return !$output ? '' : ((int)$output ? $var : txp_escape(array('escape' => $output), $var));
}

// -------------------------------------------------------------

function if_variable($atts, $thing = null)
{
    global $variable;

    extract($atts = lAtts(array(
        'name'      => '',
        'value'     => false,
        'match'     => 'exact',
        'separator' => '',
    ), $atts));

    if (empty($name)) {
        trigger_error(gTxt('variable_name_empty'));

        return '';
    }

    if (isset($variable[$name])) {
        $x = $value === false ? true : txp_match($atts, $variable[$name]);
    } else {
        $x = false;
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function if_request($atts, $thing = null)
{
    extract(lAtts(array(
        'name'      => '',
        'type'      => 'request',
        'value'     => null,
        'match'     => 'exact',
        'separator' => '',
    ), $atts));

    $atts = compact('value', 'match', 'separator');

    switch ($type = strtoupper($type)) {
        case 'REQUEST':
        case 'GET':
        case 'POST':
        case 'COOKIE':
        case 'SERVER':
            global ${'_'.$type};
            $what = isset(${'_'.$type}[$name]) ? ${'_'.$type}[$name] : null;
            $x = $name === '' ? !empty(${'_'.$type}) : txp_match($atts, $what);
            break;
        case 'NAME':
            $x = txp_match($atts, $name);
            break;
        default:
            trigger_error(gTxt('invalid_attribute_value', array('{name}' => 'type')), E_USER_NOTICE);
    }

    return isset($thing) ? parse($thing, $x) : $x;
}

// -------------------------------------------------------------

function txp_eval($atts, $thing = null)
{
    global $prefs, $txp_tag, $txp_atts;
    static $xpath = null, $functions = null, $_functions = null;

    unset($txp_atts['evaluate']);
    $staged = null;

    extract(lAtts(array(
        'query' => null,
        'test'  => !isset($atts['query']),
    ), $atts));

    if (!isset($thing) && isset($atts['test']) && $atts['test'] !== true) {
        $thing = $atts['test'];
        $test = null;
    }

    if (!isset($query) || $query === true) {
        $x = true;
    } elseif (!($query = trim($query))) {
        $x = $query;
    } elseif (class_exists('DOMDocument')) {
        if (!isset($xpath)) {
            $xpath = new DOMXpath(new DOMDocument);
            $functions = do_list_unique(get_pref('txp_evaluate_functions'));
            $_functions = array();

            foreach ($functions as $function) {
                list($key, $val) = do_list($function, '=') + array(null, $function);

                if (function_exists($val)) {
                    $_functions[$key] = $val;
                }
            }

            if ($_functions) {
                $functions = implode('|', array_keys($_functions));
                $xpath->registerNamespace('php', 'http://php.net/xpath');
                $xpath->registerPHPFunctions($_functions);
            } else {
                $functions = false;
            }
        }

        if ($functions) {
            $query = preg_replace_callback('/\b('.$functions.')\s*\(\s*(\)?)/',
                function ($match) use ($_functions) {
                    $function = empty($_functions[$match[1]]) ? $match[1] : $_functions[$match[1]];

                    return "php:function('$function'".($match[2] ? ')' : ',');
                },
                $query
            );
        }

        if (strpos($query, '<+>') !== false) {
            $staged = $x = true;
        } else {
            $x = $xpath->evaluate($query);

            if ($x instanceof DOMNodeList) {
                $x = $x->length;
            }
        }
    } else {
        trigger_error(gTxt('missing_dom_extension'));
        return '';
    }

    if (!isset($thing)) {
        return $test === true ? !empty($x) : $x;
    } elseif (empty($x)) {
        return isset($test) ? parse($thing, false) : false;
    }

    $txp_tag = null;

    if (isset($test) && $query !== true) {
        $txp_atts['evaluate'] = $test;
        $x = parse($thing);
        unset($txp_atts['evaluate']);
    } else {
        $x = $thing;
    }

    if ($txp_tag !== false) {
        if ($staged) {
            $quoted = txp_escape('quote', $x);
            $query = str_replace('<+>', $quoted, $query);
            $query = $xpath->evaluate($query);
            $query = $query instanceof DOMNodeList ? $query->length : $query;

            if ($query === false) {
                return isset($test) ? parse($thing, false) : false;
            } else {
                return $query === true ? $x : $query;
            }
        } elseif ($query === true) {
            $x = parse($thing, !isset($test) || !empty($test));
        }
    } else {
        $txp_atts = null;
        $x = isset($test) ? parse($thing, false) : false;
    }

    return $test === null && $query !== true ? !empty($x) : $x;
}

// -------------------------------------------------------------

function txp_escape($escape, $thing = '')
{
    global $prefs;
    static $textile = null, $decimal = null, $spellout = null, $ordinal = null,
        $mb = null, $LocaleInfo = null, $tr = array("'" => "',\"'\",'");

    if (is_array($escape)) {
        extract(lAtts(array('escape' => true), $escape, false));
    }

    if (empty($escape)) {
        return $thing;
    }

    $escape = $escape === true ? array('html') : do_list(strtolower($escape));
    $filter = $tidy = $quoted = false;
    isset($thing) or $thing = '';

    isset($mb) or $mb = extension_loaded('mbstring') ? 'mb_' : '';

    foreach ($escape as $attr) {
        switch ($attr) {
            case 'html':
                $thing = !$tidy ? txpspecialchars($thing) :
                    ($mb ? mb_encode_numericentity($thing, array(0x0080, 0x10FFFF, 0x0, 0xFFFFFF), 'UTF-8') : htmlentities($thing));
                break;
            case 'db':
                $thing = safe_escape($thing);
                $quoted = true;
                break;
            case 'url':
                $thing = $tidy ? rawurlencode($thing) : urlencode($thing);
                break;
            case 'url_title':
                $thing = stripSpace($thing, 1);
                break;
            case 'js':
                $thing = escape_js($thing);
                break;
            case 'json':
                $thing = substr(json_encode($thing, JSON_UNESCAPED_UNICODE), 1, -1);
                break;
            case 'integer':
                !$filter or $thing = do_list($thing);
                // no break
            case 'number': case 'float': case 'spell': case 'ordinal':
                isset($LocaleInfo) or $LocaleInfo = localeconv();
                $dec_point = $LocaleInfo['decimal_point'];
                $thousands_sep = $LocaleInfo['thousands_sep'];
                !$thousands_sep or $thing = str_replace($thousands_sep, '', $thing);
                $dec_point == '.' or $thing = str_replace($dec_point, '.', $thing);

                if (is_array($thing)) {// integer mode
                    $value = $tidy ?
                        array_map(function ($str) {
                            return filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        }, $thing) :
                        $thing;
                } else {
                    $value = floatval($tidy ?
                        filter_var($thing, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) :
                        $thing
                    );
                }

                switch ($attr) {
                    case 'integer':
                        $thing = $filter ? implode(',', array_filter(array_map('intval', $value))) : intval($value);
                        break;
                    case 'number':
                        isset($decimal)
                            or !($decimal = class_exists('NumberFormatter'))
                            or $decimal = new NumberFormatter(LANG, NumberFormatter::DECIMAL);

                        if ($decimal) {
                            $thing = $decimal->format($value);
                        } else {
                            $thing = number_format($value, 3, $dec_point, $thousands_sep);
                            $thing = rtrim(rtrim($thing, '0'), $dec_point);
                        }
                        break;
                    case 'spell':
                        isset($spellout)
                            or !($spellout = class_exists('NumberFormatter'))
                            or $spellout = new NumberFormatter(LANG, NumberFormatter::SPELLOUT);

                        if ($spellout && ($tidy || is_numeric($thing))) {
                            $thing = $spellout->format($value);
                        }
                        break;
                    case 'ordinal':
                        isset($ordinal)
                            or !($ordinal = class_exists('NumberFormatter'))
                            or $ordinal = new NumberFormatter(LANG, NumberFormatter::ORDINAL);

                        if ($ordinal && ($tidy || is_numeric($thing))) {
                            $thing = $ordinal->format($value);
                        }
                        break;
                    default:
                        $thing = $dec_point != '.' ? str_replace($dec_point, '.', $value) : $value;
                }
                break;
            case 'tags':
                $thing = strip_tags($thing);
                break;
            case 'upper': case 'lower':
                $function = ($mb && mb_detect_encoding($thing) != 'ASCII' ? 'mb_strto' : 'strto').$attr;
                $thing = $function($thing);
                break;
            case 'title':
                $thing = $mb && mb_detect_encoding($thing) != 'ASCII' ?
                    mb_convert_case($thing, MB_CASE_TITLE) : ucwords($thing);
                break;
            case 'trim': case 'ltrim': case 'rtrim':
                $filter = true;
                $thing = is_int($thing) ? ($thing ? $thing : '') : $attr($thing);
                break;
            case 'tidy':
                $tidy = true;
                $thing = preg_replace('/\s+/', ' ', trim($thing));
                break;
            case 'untidy':
                $tidy = false;
                break;
            case 'textile':
                if ($textile === null) {
                    $textile = Txp::get('\Textpattern\Textile\Parser');
                }

                $thing = $textile->parse($tidy ? ' '.$thing : $thing);
                !$tidy or $thing = ltrim($thing);
                break;
            case 'quote':
                $thing = $quoted || strpos($thing, "'") === false ? "'$thing'" : "concat('".strtr($thing, $tr)."')";
                break;
            default:
                $thing = preg_replace('@</?'.($tidy ? preg_quote($attr) : $attr).'\b[^<>]*>@Usi', '', $thing);
        }
    }

    return $thing;
}

// -------------------------------------------------------------

function txp_wraptag($atts, $thing = '')
{
    static $regex = '/([^\\\w\s]).+\1[UsiAmuS]*$/As';

    extract(lAtts(array(
        'escape'   => '',
        'label'    => '',
        'labeltag' => '',
        'wraptag'  => '',
        'class'    => '',
        'html_id'  => '',
        'break'    => null,
        'breakby'  => null,
        'trim'     => null,
        'replace'  => null,
        'limit'    => null,
        'offset'   => null,
        'sort'     => null,
        'default'  => null,
    ), $atts, false));

    $dobreak = array('break' => $break === true ? txp_break($wraptag) : $break);

    if (isset($breakby) || (isset($break) || isset($limit) || isset($offset) || isset($sort) || $replace === true) && ($breakby = true)) {
        if ($breakby === '') {// cheat, php 7.4 mb_str_split would be better
            $thing = preg_split('/(.)/u', $thing, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        } elseif (is_numeric(str_replace(array(' ', ',', '-'), '', $breakby)) && ($dobreak['breakby'] = $breakby)) {
            $thing = do_list($thing);
        } elseif (strlen($breakby) > 2 && preg_match($regex, $breakby)) {
            $thing = preg_split($breakby, $thing, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        } else {
            $thing = $breakby === true ? do_list($thing) : explode($breakby, $thing);
        }

        isset($trim) or !empty($escape) or $trim = true;
    }

    if (isset($trim) || isset($replace) || is_array($thing)) {
        $thing = doWrap($thing, null, compact('escape', 'trim', 'replace', 'limit', 'offset', 'sort') + $dobreak);
    } elseif ($escape) {
        $thing = txp_escape($escape, $thing);
    }

    !isset($default) or trim($thing) !== '' or $thing = $default;

    if (trim($thing) !== '') {
        $thing = $wraptag ? doTag($thing, $wraptag, $class, '', $html_id) : $thing;
        $thing = $label ? doLabel($label, $labeltag).n.$thing : $thing;
    }

    return $thing;
}

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
 * Image tags.
 *
 * @since  4.9.0
 */

namespace Textpattern\Tag\Syntax;

class Image
{
    public static function thumbnail($atts)
    {
        return self::image($atts + array('thumbnail' => null));
    }
    
    // -------------------------------------------------------------
    
    public static function image($atts)
    {
        global $doctype, $txp_atts;
        static $tagAtts = array(
            'escape'    => true,
            'alt'       => null,
            'title'     => '',
            'class'     => '',
            'html_id'   => '',
            'height'    => '',
            'id'        => '',
            'link'      => 0,
            'link_rel'  => '',
            'loading'   => null,
            'name'      => '',
            'poplink'   => 0, // Deprecated, 4.7
            'wraptag'   => '',
            'width'     => '',
            'thumbnail' => false,
        );
    
        $extAtts = join_atts(array_diff_key($atts, $tagAtts + ($txp_atts ? $txp_atts : array())), TEXTPATTERN_STRIP_EMPTY_STRING|TEXTPATTERN_STRIP_TXP);
        $atts = array_intersect_key($atts, $tagAtts);
    
        extract(lAtts($tagAtts, $atts));
    
        if (isset($atts['poplink'])) {
            trigger_error(gTxt('deprecated_attribute', array('{name}' => 'poplink')), E_USER_NOTICE);
        }
    
        if ($imageData = imageFetchInfo($id, $name)) {
            $thumb_ = $thumbnail || !isset($thumbnail) ? 'thumb_' : '';
    
            if ($thumb_ && empty($imageData['thumbnail'])) {
                $thumb_ = '';
    
                if (!isset($thumbnail)) {
                    return;
                }
            }
    
            if ($alt === true) {
                $imageData['alt'] !== '' or $imageData['alt'] = $imageData['name'];
            } elseif (isset($alt)) {
                $imageData['alt'] = $alt;
            }
    
            extract($imageData);
    
            if ($escape) {
                $alt = txp_escape($escape, $alt);
            }
    
            if ($title === true) {
                $title = $caption;
            }
    
            if ($width == '' && ($thumb_ && $thumb_w || !$thumb_ && $w)) {
                $width = ${$thumb_.'w'};
            }
    
            if ($height == '' && ($thumb_ && $thumb_h || !$thumb_ && $h)) {
                $height = ${$thumb_.'h'};
            }
    
            $out = '<img src="'.imagesrcurl($id, $ext, !empty($thumb_)).
                '" alt="'.txpspecialchars($alt, ENT_QUOTES, 'UTF-8', false).'"';
    
            if ($title) {
                $out .= ' title="'.txpspecialchars($title, ENT_QUOTES, 'UTF-8', false).'"';
            }
    
            if ($html_id && !$wraptag) {
                $out .= ' id="'.txpspecialchars($html_id).'"';
            }
    
            if ($class && !$wraptag) {
                $out .= ' class="'.txpspecialchars($class).'"';
            }
    
            if ($width) {
                $out .= ' width="'.(int) $width.'"';
            }
    
            if ($height) {
                $out .= ' height="'.(int) $height.'"';
            }
    
            if ($loading && $doctype === 'html5' && in_array($loading, array('auto', 'eager', 'lazy'))) {
                $out .= ' loading="'.$loading.'"';
            }
    
            $out .= $extAtts.(get_pref('doctype') === 'html5' ? '>' : ' />');
    
            if ($link && $thumb_) {
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
    
            return $wraptag ? doTag($out, $wraptag, $class, '', $html_id) : $out;
        }
    }

    // -------------------------------------------------------------
    
    public static function image_index($atts)
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
            return self::images($atts);
        }
    
        return '';
    }
    
    // -------------------------------------------------------------
    
    public static function image_display($atts)
    {
        trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);
    
        global $p;
    
        if ($p) {
            return self::image(array('id' => $p, 'thumbnail' => false));
        }
    }
    
    // -------------------------------------------------------------
    
    public static function images($atts, $thing = null)
    {
        global $s, $c, $context, $thisimage, $thisarticle, $thispage, $prefs, $pretext;
    
        extract(lAtts(array(
            'name'        => '',
            'id'          => '',
            'category'    => '',
            'author'      => '',
            'realname'    => '',
            'extension'   => '',
            'thumbnail'   => true,
            'size'        => '',
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
    
        $safe_sort = sanitizeForSort($sort);
        $where = array();
        $has_content = isset($thing) || $form;
        ($has_content || $thumbnail) or $thumbnail = null;
        $filters = isset($atts['id']) || isset($atts['name']) || isset($atts['category']) || isset($atts['author']) || isset($atts['realname']) || isset($atts['extension']) || isset($atts['size']) || $thumbnail === '1' || $thumbnail === '0';
        $context_list = (empty($auto_detect) || $filters) ? array() : do_list_unique($auto_detect);
        $pageby = ($pageby == 'limit') ? $limit : $pageby;
    
        if ($name) {
            $where[] = "name IN ('".join("','", doSlash(do_list_unique($name)))."')";
        }
    
        if ($category) {
            $where[] = "category IN ('".join("','", doSlash(do_list_unique($category)))."')";
        }
    
        if ($id) {
            $id = join(',', array_map('intval', do_list_unique($id, array(',', '-'))));
            $where[] = "id IN ($id)";
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
    
        // Handle aspect ratio filtering.
        if ($size === 'portrait') {
            $where[] = "h > w";
        } elseif ($size === 'landscape') {
            $where[] = "w > h";
        } elseif ($size === 'square') {
            $where[] = "w = h";
        } elseif (is_numeric($size)) {
            $where[] = "ROUND(w/h, 2) = $size";
        } elseif (strpos($size, ':') !== false) {
            $ratio = explode(':', $size);
            $ratiow = $ratio[0];
            $ratioh = !empty($ratio[1]) ? $ratio[1] : '';
    
            if (is_numeric($ratiow) && is_numeric($ratioh)) {
                $where[] = "ROUND(w/h, 2) = ".round($ratiow/$ratioh, 2);
            } elseif (is_numeric($ratiow)) {
                $where[] = "w = $ratiow";
            } elseif (is_numeric($ratioh)) {
                $where[] = "h = $ratioh";
            }
        }
    
        // If no images are selected, try...
        if (!$where && !$filters) {
            foreach ($context_list as $ctxt) {
                switch ($ctxt) {
                    case 'article':
                        // ...the article image field.
                        if ($thisarticle && !empty($thisarticle['article_image'])) {
                            if (!is_numeric(str_replace(array(',', '-', ' '), '', $thisarticle['article_image']))) {
                                return article_image(
                                    compact('class', 'html_id', 'wraptag', 'break', 'thumbnail')+ array('range' => ($offset + 1).'-'.($offset + $limit))
                                );
                            }
    
                            $id = join(",", array_map('intval', do_list_unique($thisarticle['article_image'], array(',', '-'))));
    
                            // Note: This clause will squash duplicate ids.
                            $where[] = "id IN ($id)";
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
        if (empty($atts['sort']) && $id) {
            $safe_sort = "FIELD(id, $id)";
        }
    
        // If nothing matches from the filterable attributes, output nothing.
        if (!$where && $filters) {
            return isset($thing) ? parse($thing, false) : '';
        }
    
        // If no images are filtered, start with all images.
        if (!$where) {
            $where[] = "1 = 1";
        }
    
        $where = join(" AND ", $where);
    
        // Set up paging if required.
        if ($limit && $pageby) {
            $pg = (!$pretext['pg']) ? 1 : $pretext['pg'];
            $pgoffset = $offset + (($pg - 1) * $pageby);
    
            if (empty($thispage)) {
                $grand_total = safe_count('txp_image', $where);
                $total = $grand_total - $offset;
                $numPages = ($pageby > 0) ? ceil($total / $pageby) : 1;
    
                // Send paging info to txp:newer and txp:older.
                $pageout['pg']          = $pg;
                $pageout['numPages']    = $numPages;
                $pageout['s']           = $s;
                $pageout['c']           = $c;
                $pageout['context']     = 'image';
                $pageout['grand_total'] = $grand_total;
                $pageout['total']       = $total;
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
    
        if (!$has_content) {
            $url = "<txp:page_url context='s, c, p' c='<txp:image_info type=\"category\" />' p='<txp:image_info type=\"id\" escape=\"\" />' />&amp;context=image";
            $thumb = !isset($thumbnail) ? 0 : ($thumbnail !== true ? 1 : '<txp:image_info type="thumbnail" escape="" />');
            $thing = '<a href="'.$url.'"><txp:image thumbnail=\''.$thumb.'\' /></a>';
        }
    
        $out = parseList($rs, $thisimage, 'image_format_info', compact('form', 'thing'));
    
        return empty($out) ?
            (isset($thing) ? parse($thing, false) : '') :
            doWrap($out, $wraptag, compact('break', 'class', 'html_id'));
    }
    
    // -------------------------------------------------------------
    
    public static function image_info($atts)
    {
        extract(lAtts(array(
            'name'       => '',
            'id'         => '',
            'type'       => 'caption',
            'escape'     => true,
            'wraptag'    => '',
            'class'      => '',
            'break'      => '',
        ), $atts));
    
        $validItems = array('id', 'name', 'category', 'category_title', 'alt', 'caption', 'ext', 'mime', 'author', 'w', 'h', 'thumbnail', 'thumb_w', 'thumb_h', 'date');
        $type = do_list($type);
    
        $out = array();
    
        if ($imageData = imageFetchInfo($id, $name)) {
            foreach ($type as $item) {
                if (in_array($item, $validItems)) {
                    if ($item === 'category_title') {
                        $imageData['category_title'] = fetch_category_title($imageData['category'], 'image');
                    }
    
                    if (isset($imageData[$item])) {
                        $out[] = $escape ? txp_escape($escape, $imageData[$item]) : $imageData[$item];
                    }
                } else {
                    trigger_error(gTxt('invalid_attribute_value', array('{name}' => $item)), E_USER_NOTICE);
                }
            }
        }
    
        return doWrap($out, $wraptag, $break, $class);
    }
    
    // -------------------------------------------------------------
    
    public static function image_url($atts, $thing = null)
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
    
    public static function image_author($atts)
    {
        global $s;
    
        extract(lAtts(array(
            'name'         => '',
            'id'           => '',
            'link'         => 0,
            'title'        => 1,
            'section'      => '',
            'this_section' => '',
        ), $atts));
    
        if ($imageData = imageFetchInfo($id, $name)) {
            $author_name = get_author_name($imageData['author']);
            $display_name = txpspecialchars(($title) ? $author_name : $imageData['author']);
    
            $section = ($this_section) ? ($s == 'default' ? '' : $s) : $section;
    
            $author = ($link)
                ? href($display_name, pagelinkurl(array(
                    's'       => $section,
                    'author'  => $author_name,
                    'context' => 'image',
                )))
                : $display_name;
    
            return $author;
        }
    }
    
    // -------------------------------------------------------------
    
    public static function image_date($atts)
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
    
    public static function if_thumbnail($atts, $thing = null)
    {
        global $thisimage;
    
        assert_image();
    
        $x = ($thisimage['thumbnail'] == 1);
        return isset($thing) ? parse($thing, $x) : $x;
    }
}

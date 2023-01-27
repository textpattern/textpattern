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
 * Generates meta tags.
 *
 * @since  4.9.0
 */

namespace Textpattern\Tag\Syntax;

class Meta
{
    /**
     * Returns article renderKeywords.
     *
     * @param  array  $atts Tag attributes
     * @return string
     */
    
     public static function renderKeywords($atts)
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
    
    /**
     * Returns article, section or category meta description info.
     *
     * @param  array  $atts Tag attributes
     * @return string
     */
    
     public static function renderDescription($atts)
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
    
    public static function renderAuthor($atts)
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
}

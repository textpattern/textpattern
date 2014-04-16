<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
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
 * Tag builder.
 *
 * @package Admin\Tag
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

// -------------------------------------------------------------

header('X-Frame-Options: '.X_FRAME_OPTIONS);
header('X-UA-Compatible: '.X_UA_COMPATIBLE);

?><!DOCTYPE html>
<html lang="<?php echo LANG; ?>" dir="<?php echo txpspecialchars(gTxt('lang_dir')); ?>">
<head>
<meta charset="utf-8">
<title><?php echo gTxt('build'); ?> &#124; Textpattern CMS</title>
<script src="jquery.js"></script>
<?php echo script_js(
    'var textpattern = '.json_encode(array(
        'event' => $event,
        'step' => $step,
        '_txp_token' => form_token(),
    )).';'
); ?>
<?php echo $theme->html_head(); ?>
</head>
<body id="tag-event">
<?php echo TagBuilderTags::tag(gps('tag_name')); ?>
</body>
</html>
<?php

/*
begin generic functions
*/

// -------------------------------------------------------------

function tagRow($label, $thing)
{
    return tr(
        fLabelCell($label).
        td($thing)
    );
}

// -------------------------------------------------------------

function tb($tag, $atts_list = array(), $thing = '')
{
    $atts = array();

    foreach ($atts_list as $att => $val) {
        if ($val or $val === '0' or $val === '{att_empty}') {
            $val = str_replace('{att_empty}', '', $val);
            $atts[] = ' '.$att.'="'.$val.'"';
        }
    }

    $atts = ($atts) ? join('', $atts) : '';

    return !empty($thing) ?
        '<txp:'.$tag.$atts.'>'.$thing.'</txp:'.$tag.'>' :
        '<txp:'.$tag.$atts.' />';
}

// -------------------------------------------------------------

function tbd($tag, $thing)
{
    return '<txp:'.$tag.'>'.$thing.'</txp:'.$tag.'>';
}

// -------------------------------------------------------------

function tdb($thing)
{
    return graf(text_area('tag', '', '', $thing), ' id="tagbuilder-output"');
}

//--------------------------------------------------------------

function key_input($name, $var)
{
    return '<textarea name="'.$name.'">'.$var.'</textarea>';
}

//--------------------------------------------------------------

function input_id($id)
{
    return fInput('text', 'id', $id, '', '', '', 6);
}

//--------------------------------------------------------------

function time_pop($time)
{
    $vals = array(
        'past'   => gTxt('time_past'),
        'future' => gTxt('time_future'),
        'any'    => gTxt('time_any')
    );

    return ' '.selectInput('time', $vals, $time, true);
}

//--------------------------------------------------------------

function input_limit($limit)
{
    return fInput('text', 'limit', $limit, '', '', '', 2);
}

//--------------------------------------------------------------

function input_offset($offset)
{
    return fInput('text', 'offset', $offset, '', '', '', 2);
}

//--------------------------------------------------------------

function input_tag($name, $val)
{
    return fInput('text', $name, $val, '', '', '', 6);
}

//--------------------------------------------------------------

function yesno_pop($select_name, $val)
{
    $vals = array(
        1 => gTxt('yes'),
        0 => gTxt('no'),
    );

    if (is_numeric($val)) {
        $val = (int) $val;
    }

    return ' '.selectInput($select_name, $vals, $val, true, '', '', true);
}

//--------------------------------------------------------------

function status_pop($val)
{
    $vals = array(
        4 => gTxt('live'),
        5 => gTxt('sticky'),
        3 => gTxt('pending'),
        1 => gTxt('draft'),
        2 => gTxt('hidden'),
    );

    return ' '.selectInput('status', $vals, $val, true);
}

//--------------------------------------------------------------

function section_pop($select_name, $val)
{
    $vals = array();

    $rs = safe_rows_start('name, title', 'txp_section', "name != 'default' order by name");

    if ($rs and numRows($rs) > 0) {
        while ($a = nextRow($rs)) {
            extract($a);

            $vals[$name] = $title;
        }

        return ' '.selectInput($select_name, $vals, $val, true);
    }

    return gTxt('no_sections_available');
}

//--------------------------------------------------------------

function type_pop($val)
{
    $vals = array(
        'article' => gTxt('article'),
        'link'        => gTxt('link'),
        'image'        => gTxt('image'),
        'file'        => gTxt('file'),
    );

    return ' '.selectInput('type', $vals, $val, true);
}

//--------------------------------------------------------------

function feed_flavor_pop($val)
{
    $vals = array(
        'atom' => 'Atom 1.0',
        'rss'     => 'RSS 2.0'
    );

    return ' '.selectInput('flavor', $vals, $val, true);
}

//--------------------------------------------------------------

function feed_format_pop($val)
{
    $vals = array(
        'a'    => '<a href...',
        'link' => '<link rel...',
    );

    return ' '.selectInput('format', $vals, $val, true);
}

//--------------------------------------------------------------

function article_category_pop($val)
{
    $vals = getTree('root', 'article');

    if ($vals) {
        return ' '.treeSelectInput('category', $vals, $val);
    }

    return gTxt('no_categories_available');
}

//--------------------------------------------------------------

function link_category_pop($val)
{
    $vals = getTree('root', 'link');

    if ($vals) {
        return ' '.treeSelectInput('category', $vals, $val);
    }

    return gTxt('no_categories_available');
}

//--------------------------------------------------------------

function file_category_pop($val)
{
    $vals = getTree('root', 'file');

    if ($vals) {
        return ' '.treeSelectInput('category', $vals, $val);
    }

    return gTxt('no_categories_available');
}

//--------------------------------------------------------------

function match_pop($val)
{
    $vals = array(
        'Category1,Category2' => gTxt('category1').' '.gTxt('and').' '.gTxt('category2'),
        'Category1'           => gTxt('category1'),
        'Category2'           => gTxt('category2')
    );

    return ' '.selectInput('match', $vals, $val, true);
}

//--------------------------------------------------------------

function search_opts_pop($val)
{
    $vals = array(
        'exact' => gTxt('exact'),
        'any'   => gTxt('any'),
        'all'   => gTxt('all'),
    );

    return ' '.selectInput('match', $vals, $val, false);
}

//--------------------------------------------------------------

function author_pop($val)
{
    $vals = array();

    $rs = safe_rows_start('name', 'txp_users', '1 = 1 order by name');

    if ($rs) {
        while ($a = nextRow($rs)) {
            extract($a);

            $vals[$name] = $name;
        }

        return ' '.selectInput('author', $vals, $val, true);
    }
}

//--------------------------------------------------------------

function sort_pop($val)
{
    $asc = ' ('.gTxt('ascending').')';
    $desc = ' ('.gTxt('descending').')';

    $vals = array(
        'Title asc'      => gTxt('tag_title').$asc,
        'Title desc'     => gTxt('tag_title').$desc,
        'Posted asc'     => gTxt('tag_posted').$asc,
        'Posted desc'    => gTxt('tag_posted').$desc,
        'LastMod asc'    => gTxt('last_modification').$asc,
        'LastMod desc'   => gTxt('last_modification').$desc,
        'Section asc'    => gTxt('section').$asc,
        'Section desc'   => gTxt('section').$desc,
        'Category1 asc'  => gTxt('category1').$asc,
        'Category1 desc' => gTxt('category1').$desc,
        'Category2 asc'  => gTxt('category2').$asc,
        'Category2 desc' => gTxt('category2').$desc,
        'rand()'         => gTxt('random')
    );

    return ' '.selectInput('sort', $vals, $val, true);
}

//--------------------------------------------------------------

function discuss_sort_pop($val)
{
    $asc = ' ('.gTxt('ascending').')';
    $desc = ' ('.gTxt('descending').')';

    $vals = array(
        'posted asc'  => gTxt('posted').$asc,
        'posted desc' => gTxt('posted').$desc,
    );

    return ' '.selectInput('sort', $vals, $val, true);
}

//--------------------------------------------------------------

function list_sort_pop($val)
{
    $asc = ' ('.gTxt('ascending').')';
    $desc = ' ('.gTxt('descending').')';

    $vals = array(
        'title asc'  => gTxt('tag_title').$asc,
        'title desc' => gTxt('tag_title').$desc,
        'name asc'   => gTxt('name').$asc,
        'name desc'  => gTxt('name').$desc,
    );

    return ' '.selectInput('sort', $vals, $val, true);
}

//--------------------------------------------------------------

function pgonly_pop($val)
{
    $vals = array(
        '1' => gTxt('yes'),
        '0' => gTxt('no')
    );

    return ' '.selectInput('pgonly', $vals, $val, true);
}

//--------------------------------------------------------------

function form_pop($select_name, $type = '', $val)
{
    $vals = array();

    $type = ($type) ? "type = '".doSlash($type)."'" : '1 = 1';

    $rs = safe_rows_start('name', 'txp_form', "$type order by name");

    if ($rs and numRows($rs) > 0) {
        while ($a = nextRow($rs)) {
            extract($a);

            $vals[$name] = $name;
        }

        return ' '.selectInput($select_name, $vals, $val, true);
    }

    return gTxt('no_forms_available');
}

//--------------------------------------------------------------

function css_pop($val)
{
    $vals = array();

    $rs = safe_rows_start('name', 'txp_css', "1 = 1 order by name");

    if ($rs) {
        while ($a = nextRow($rs)) {
            extract($a);

            $vals[$name] = $name;
        }

        return ' '.selectInput('name', $vals, $val, true);
    }

    return false;
}

//--------------------------------------------------------------

function css_format_pop($val)
{
    $vals = array(
        'link' => '<link rel...',
        'url'  => 'css.php?...'
    );

    return ' '.selectInput('format', $vals, $val, true);
}

//--------------------------------------------------------------

function escape_pop($val)
{
    $vals = array(
        '{att_empty}' => '',
        'html'        => 'html',
    );

    return ' '.selectInput('escape', $vals, $val, false);
}

/**
 * Collection of tag builder functions.
 *
 * @package Admin\Tag
 */

class TagBuilderTags
{
    /**
     * Returns a tag handler.
     *
     * @param  string      $name The tag
     * @return string|bool FALSE on error
     */

    public static function tag($name)
    {
        global $tag_name, $endform;

        $tagbuilder = new TagBuilderTags();
        $tag_name = gps('tag_name');
        $method = 'tag_'.$tag_name;

        if (method_exists($tagbuilder, $method)) {
            $endform = tr(
                td().
                td(fInput('submit', '', gTxt('build')))
            ).
            endTable().
            eInput('tag').
            sInput('build').
            hInput('tag_name', $tag_name);
            return $tagbuilder->$method($tag_name);
        }

        return false;
    }

    function tag_article()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'allowoverride',
            'form',
            'limit',
            'listform',
            'offset',
            'pageby',
            'pgonly',
            'searchall',
            'searchsticky',
            'sort',
            'status',
            'time'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('status',
                status_pop($status)).

            tagRow('time',
                time_pop($time)).

            tagRow('searchall',
                yesno_pop('searchall', $searchall)).

            tagRow('searchsticky',
                yesno_pop('searchsticky', $searchsticky)).

            tagRow('limit',
                input_limit($limit)).

            tagRow('offset',
                input_offset($offset)).

            tagRow('pageby',
                fInput('text', 'pageby', $pageby, '', '', '', 2)).

            tagRow('sort',
                sort_pop($sort)).

            tagRow('pgonly',
                pgonly_pop($pgonly)).

            tagRow('allowoverride',
                yesno_pop('allowoverride', $allowoverride)).

            tagRow('form',
                form_pop('form', 'article', $form)).

            tagRow('listform',
                form_pop('listform', 'article', $listform)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_article_custom()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'allowoverride',
            'author',
            'break',
            'category',
            'class',
            'excerpted',
            'expired',
            'form',
            'id',
            'keywords',
            'label',
            'labeltag',
            'limit',
            'month',
            'offset',
            'pageby',
            'pgonly',
            'section',
            'sort',
            'status',
            'time',
            'wraptag',
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('id',
                input_id($id)).

            tagRow('status',
                status_pop($status)).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('category',
                article_category_pop($category)).

            tagRow('time',
                time_pop($time)).

            tagRow('month',
                fInput('text', 'month', $month, '', '', '', 7). ' ('.gTxt('yyyy-mm').')') .

            tagRow('keywords',
                key_input('keywords', $keywords)).

            tagRow('has_excerpt',
                yesno_pop('excerpted', $excerpted)).

            tagRow('expired',
                yesno_pop('expired', $expired)).

            tagRow('author',
                author_pop($author)).

            tagRow('sort',
                sort_pop($sort)).

            tagRow('limit',
                input_limit($limit)).

            tagRow('offset',
                input_offset($offset)).

            tagRow('pgonly',
                pgonly_pop($pgonly)).

            tagRow('allowoverride',
                yesno_pop('allowoverride', $allowoverride)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', 20)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', 14)).

            tagRow('break',
                input_tag('break', $break)).

            tagRow('form',
                form_pop('form', 'article', $form)).
            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_email()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'email',
            'linktext',
            'title'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('email_address',
                fInput('email', 'email', $email, '', '', '', 20)).

            tagRow('tooltip',
                fInput('text', 'title', $title, '', '', '', 20)).

            tagRow('link_text',
                fInput('text', 'linktext', $linktext, '', '', '', 20)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_page_title()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array('separator'));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('title_separator',
                fInput('text', 'separator', $separator, '', '', '', 4)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_linklist()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'break',
            'category',
            'form',
            'label',
            'labeltag',
            'limit',
            'sort',
            'wraptag',
        ));

        $asc = ' ('.gTxt('ascending').')';
        $desc = ' ('.gTxt('descending').')';

        $sorts = array(
            'linksort asc'  => gTxt('name').$asc,
            'linksort desc' => gTxt('name').$desc,
            'category asc'  => gTxt('category').$asc,
            'category desc' => gTxt('category').$desc,
            'date asc'      => gTxt('date').$asc,
            'date desc'     => gTxt('date').$desc,
            'rand()'        => gTxt('random')
        );

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('category',
                link_category_pop($category)).

            tagRow('limit',
                input_limit($limit)).

            tagRow('sort',
                ' '.selectInput('sort', $sorts, $sort)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', 20)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('form',
                form_pop('form', 'link', $form)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('break',
                input_tag('break', $break)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        echo $out;
    }

    // -------------------------------------------------------------

    function tag_section_list()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'active_class',
            'break',
            'class',
            'default_title',
            'exclude',
            'include_default',
            'label',
            'labeltag',
            'sections',
            'sort',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('include_default',
                yesno_pop('include_default', $include_default)).

            tagRow('sort',
                list_sort_pop($sort)).

            tagRow('default_title',
                fInput('text', 'default_title', $default_title, '', '', '', 20)).

            tagRow('sections',
                fInput('text', 'sections', $sections, '', '', '', 20)).

            tagRow('exclude',
                fInput('text', 'exclude', $exclude, '', '', '', 20)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', 20)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', 14)).

            tagRow('active_class',
                fInput('text', 'active_class', $active_class, '', '', '', 14)).

            tagRow('break',
                input_tag('break', $break)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        echo $out;

    }

    // -------------------------------------------------------------

    function tag_category_list()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'active_class',
            'break',
            'categories',
            'class',
            'exclude',
            'label',
            'labeltag',
            'parent',
            'section',
            'sort',
            'this_section',
            'type',
            'wraptag',
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('type',
                type_pop($type)).

            tagRow('parent',
                fInput('text', 'parent', $parent, '', '', '', 20)).

            tagRow('categories',
                fInput('text', 'categories', $categories, '', '', '', 20)).

            tagRow('exclude',
                fInput('text', 'exclude', $exclude, '', '', '', 20)).

            tagRow('this_section',
                yesno_pop('this_section', $this_section)).

            tagRow('category_list_section',
                section_pop('section', $section)).

            tagRow('sort',
                list_sort_pop($sort)).

            tagRow('label',
                fInput('text', 'label', ($label ? $label : gTxt('categories')), '', '', '', 20)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', 14)).

            tagRow('active_class',
                fInput('text', 'active_class', $active_class, '', '', '', 14)).

            tagRow('break',
                input_tag('break', $break)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        echo $out;
    }

    // -------------------------------------------------------------

    function tag_recent_articles()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'break',
            'category',
            'label',
            'labeltag',
            'limit',
            'section',
            'sort',
            'wraptag',
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('category',
                article_category_pop($category)).

            tagRow('sort',
                sort_pop($sort)).

            tagRow('limit',
                fInput('text', 'limit', $limit, '', '', '', 2)).

            tagRow('label',
                fInput('text', 'label', ($label ? $label : gTxt('recent_articles')), '', '', '', 20)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('break',
                input_tag('break', $break)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_related_articles()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'break',
            'class',
            'label',
            'labeltag',
            'limit',
            'match',
            'section',
            'sort',
            'wraptag',
        ));

        extract($atts);

        $label = (!$label) ? 'Related Articles' : $label;

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('match',
                match_pop($match)).

            tagRow('sort',
                sort_pop($sort)).

            tagRow('limit',
                fInput('text', 'limit', $limit, '', '', '', 2)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', 20)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', 20)).

            tagRow('break',
                input_tag('break', $break)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_recent_comments()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'break',
            'class',
            'label',
            'labeltag',
            'limit',
            'sort',
            'wraptag',
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('sort',
                discuss_sort_pop($sort)).

            tagRow('limit',
                fInput('text', 'limit', $limit, '', '', '', 2)).

            tagRow('label',
                fInput('text', 'label', ($label ? $label : gTxt('recent_comments')), '', '', '', 20)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', 5)).

            tagRow('break',
                input_tag('break', $break)).

        $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_output_form()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'form'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('form',
                form_pop('form', 'misc', $form)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_popup()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'label',
            'section',
            'this_section',
            'type',
            'wraptag'
        ));

        extract($atts);

        $types = array(
            'c' => gTxt('Category'),
            's' => gTxt('Section')
        );

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('type',
                ' '.selectInput('type', $types, $type, true)).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('this_section',
                yesno_pop('this_section', $this_section)).

            tagRow('label',
                fInput('text', 'label', ($label ? $label : gTxt('browse')), '', '', '', INPUT_REGULAR)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_password_protect()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'login',
            'pass'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('login',
                fInput('text', 'login', $login, '', '', '', INPUT_REGULAR)).

            tagRow('password',
                fInput('text', 'pass', $pass, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_search_input()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'button',
            'class',
            'form',
            'label',
            'match',
            'section',
            'size',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('match_type',
                search_opts_pop($match)).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('button_text',
                fInput('text', 'button', $button, '', '', '', INPUT_REGULAR)).

            tagRow('input_size',
                fInput('text', 'size', $size, '', '', '', INPUT_TINY)).

            tagRow('label',
                fInput('text', 'label', ($label ? $label : gTxt('search')), '', '', '', INPUT_REGULAR)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            tagRow('form',
                form_pop('form', 'misc', $form)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_category1()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'link',
            'title',
            'section',
            'this_section',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').
            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('title',
                yesno_pop('title', $title)).

            tagRow('link_to_this_category',
                yesno_pop('link', $link)).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('this_section',
                yesno_pop('this_section', $this_section)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_category2()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'link',
            'title',
            'section',
            'this_section',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').
            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('title',
                yesno_pop('title', $title)).

            tagRow('link_to_this_category',
                yesno_pop('link', $link)).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('this_section',
                yesno_pop('this_section', $this_section)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_category()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'link',
            'name',
            'this_section',
            'title',
            'type',
            'url',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('name',
                fInput('text', 'name', $name, '', '', '', INPUT_REGULAR)).

            tagRow('link_to_this_category',
                yesno_pop('link', $link)).

            tagRow('title',
                yesno_pop('title', $title)).

            tagRow('type',
                type_pop('type', $type)).

            tagRow('url_only',
                yesno_pop('url', $url)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_if_category()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'name'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('name',
                fInput('text', 'name', $name, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts, gTxt('...')));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_section()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'link',
            'name',
            'title',
            'url',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('name',
                section_pop('name', $tag_name)).

            tagRow('link_to_this_section',
                yesno_pop('link', $link)).

            tagRow('url_only',
                yesno_pop('url', $url)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_if_section()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'name'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('name',
                section_pop('name', $tag_name)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts, gTxt('...')));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_author()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'link',
            'section',
            'this_section'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('link_to_this_author',
                yesno_pop('link', $link)).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('this_section',
                yesno_pop('this_section', $this_section)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_link_to_home()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
        ));

        extract($atts);

        $thing = gps('thing');

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('link_text',
                fInput('text', 'thing', ($thing ? $thing : gTxt('tag_home')), '', '', '', INPUT_REGULAR)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts, $thing));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_link_to_prev()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'showalways',
        ));

        extract($atts);

        $thing = gps('thing');

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('link_text',
                fInput('text', 'thing', ($thing ? $thing : '<txp:prev_title />'), '', '', '', INPUT_REGULAR)).

            tagRow('showalways',
                yesno_pop('showalways', $showalways)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts, $thing));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_link_to_next()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'showalways',
        ));

        extract($atts);

        $thing = gps('thing');

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('link_text',
                fInput('text', 'thing', ($thing ? $thing : '<txp:next_title />'), '', '', '', INPUT_REGULAR)).

            tagRow('showalways',
                yesno_pop('showalways', $showalways)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts, $thing));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_feed_link()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'category',
            'class',
            'flavor',
            'format',
            'label',
            'limit',
            'section',
            'title',
            'wraptag',
        ));

        extract($atts);

        $label = $label ? $label : 'XML';

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('flavor',
                feed_flavor_pop($flavor)).

            tagRow('format',
                feed_format_pop($format)).

            tagRow('section',
                section_pop('section', $section)).

            tagRow('category',
                article_category_pop($section)).

            tagRow('limit',
                input_limit($limit)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', INPUT_REGULAR)).

            tagRow('title',
                fInput('text', 'title', $title, '', '', '', INPUT_REGULAR)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_link_feed_link()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'category',
            'class',
            'flavor',
            'format',
            'label',
            'limit',
            'title',
            'wraptag'
        ));

        extract($atts);

        $label = (!$label) ? 'XML' : $label;

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('flavor',
                feed_flavor_pop($flavor)).

            tagRow('format',
                feed_format_pop($format)).

            tagRow('category',
                link_category_pop($category)).

            tagRow('limit',
                fInput('text', 'limit', $limit, '', '', '', INPUT_TINY)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', INPUT_REGULAR)).

            tagRow('title',
                fInput('text', 'title', $title, '', '', '', INPUT_REGULAR)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_permlink()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'id',
            'style',
            'title'
        ));

        extract($atts);

        $thing = gps('thing');

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('id',
                input_id($id)).

            tagRow('link_text',
                fInput('text', 'thing', ($thing ? $thing : '<txp:title />'), '', '', '', INPUT_REGULAR)).

            tagRow('title',
                fInput('text', 'title', $title, '', '', '', INPUT_REGULAR)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            tagRow('inline_style',
                fInput('text', 'style', $style, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts, $thing));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_newer()
    {
        global $step, $endform, $tag_name;

        $thing = gps('thing');

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('link_text',
                fInput('text', 'thing', ($thing ? $thing : '<txp:text item="newer" />'), '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, array(), $thing));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_older()
    {
        global $step, $endform, $tag_name;

        $thing = gps('thing');

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('link_text',
                fInput('text', 'thing', ($thing ? $thing : '<txp:text item="older" />'), '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, array(), $thing));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_next_title()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_site_name()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_site_slogan()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_prev_title()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_article_image()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'escape',
            'height',
            'html_id',
            'style',
            'thumbnail',
            'width',
            'wraptag',
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('use_thumbnail',
                yesno_pop('thumbnail', $thumbnail)).

            tagRow('escape',
                escape_pop($escape)).

            tagRow('html_id',
                fInput('text', 'html_id', $html_id, '', '', '', INPUT_REGULAR)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            tagRow('inline_style',
                fInput('text', 'style', $style, '', '', '', INPUT_REGULAR)).

            tagRow('wraptag',
                fInput('text', 'wraptag', $wraptag, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_css()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'format',
            'media',
            'name',
            'rel',
            'title'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('name',
                css_pop($name)).

            tagRow('format',
                css_format_pop($format)).

            tagRow('media',
                fInput('text', 'media', $media, '', '', '', INPUT_REGULAR)).

            tagRow('rel',
                fInput('text', 'rel', $rel, '', '', '', INPUT_REGULAR)).

            tagRow('title',
                fInput('text', 'title', $title, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_body()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_excerpt()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_title()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_link()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'id',
            'name',
            'rel'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('id',
                input_id($id)).
            tagRow('name',
                fInput('text', 'name', $name, '', '', '', INPUT_REGULAR)).
            tagRow('rel',
                fInput('text', 'rel', $rel, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_linkdesctitle()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'rel'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('rel',
                fInput('text', 'rel', $rel, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_link_description()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'escape',
            'label',
            'labeltag',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('escape',
                escape_pop($escape)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', INPUT_REGULAR)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_link_name()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'escape',
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('escape',
                escape_pop($escape)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_link_category()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'label',
            'labeltag',
            'title',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('title',
                yesno_pop('title', $title)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', INPUT_REGULAR)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_link_date()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'format',
            'gmt',
            'lang'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('time_format',
                fInput('text', 'format', $format, '', '', '', INPUT_REGULAR)).

            tagRow('gmt',
                yesno_pop('gmt', $gmt)).

            tagRow('locale',
                fInput('text', 'lang', $lang, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_posted()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'format',
            'gmt',
            'lang'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('time_format',
                fInput('text', 'format', $format, '', '', '', INPUT_REGULAR)).

            tagRow('gmt',
                yesno_pop('gmt', $gmt)).

            tagRow('locale',
                fInput('text', 'lang', $lang, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_comments_invite()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'showcount',
            'textonly',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('textonly',
                yesno_pop('textonly', $textonly)).

            tagRow('showcount',
                yesno_pop('showcount', $showcount)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_comment_permlink()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_time()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'format',
            'gmt',
            'lang'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('time_format',
                fInput('text', 'format', $format, '', '', '', INPUT_REGULAR)).

            tagRow('gmt',
                yesno_pop('gmt', $gmt)).

            tagRow('locale',
                fInput('text', 'lang', $lang, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_comment_name()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'link'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('comment_name_link',
                yesno_pop('link', $link)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_comment_email()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_web()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_message()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_email_input()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_message_input()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_name_input()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_preview()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_remember()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_submit()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comment_web_input()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_comments()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'break',
            'class',
            'form',
            'limit',
            'offset',
            'sort',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('form',
                form_pop('form', 'comment', $form)).

            tagRow('sort',
                discuss_sort_pop($sort)).

            tagRow('limit',
                input_limit($limit)).

            tagRow('offset',
                input_offset($offset)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            tagRow('break',
                input_tag('break', $break)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_comments_form()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'id',
            'isize',
            'form',
            'msgcols',
            'msgrows',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('id',
                input_id($id)).

            tagRow('isize',
                fInput('text', 'isize', $isize, '', '', '', 2)).

            tagRow('msgcols',
                fInput('text', 'msgcols', $msgcols, '', '', '', 2)).

            tagRow('msgrows',
                fInput('text', 'msgrows', $msgrows, '', '', '', 2)).

            tagRow('form',
                form_pop('form', 'comment', $form)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_comments_preview()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'id',
            'form',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('id',
                input_id($id)).

            tagRow('form',
                form_pop('form', 'comment', $form)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_search_result_title()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_search_result_excerpt()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'hilight',
            'limit'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('hilight',
                input_tag('hilight', $hilight)).

            tagRow('hilight_limit',
                input_limit($limit)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_search_result_url()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_search_result_date()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'format',
            'gmt',
            'lang'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('time_format',
                fInput('text', 'format', $format, '', '', '', INPUT_REGULAR)).

            tagRow('gmt',
                yesno_pop('gmt', $gmt)).

            tagRow('locale',
                fInput('text', 'lang', $lang, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_lang()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_breadcrumb()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'label',
            'link',
            'linkclass',
            'separator',
            'title',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('breadcrumb_separator',
                fInput('text', 'separator', $separator, '', '', '', INPUT_XSMALL)).

            tagRow('breadcrumb_linked',
                yesno_pop('link', $link)).

            tagRow('linkclass',
                fInput('text', 'linkclass', $linkclass, '', '', '', INPUT_REGULAR)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', INPUT_REGULAR)).

            tagRow('title',
                fInput('text', 'title', $title, '', '', '', INPUT_REGULAR)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_image()
    {
        global $step, $endform, $tag_name, $img_dir;

        $atts = gpsa(array(
            'class',
            'html_id',
            'style',
            'wraptag',

            'alt',
            'caption',
            'h',
            'id',
            'w',
        ));

        extract($atts);

        $ext = gps('ext');
        $type = gps('type');

        $types = array(
            'textile'     => 'Textile',
            'textpattern' => 'Textpattern',
            'html'        => 'HTML'
        );

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('type',
                ''.selectInput('type', $types, ($type ? $type : 'textpattern'), true)).

            tagRow('html_id',
                fInput('text', 'html_id', $html_id, '', '', '', INPUT_REGULAR)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            tagRow('inline_style',
                fInput('text', 'style', $style, '', '', '', INPUT_REGULAR)).

            tagRow('wraptag',
                fInput('text', 'wraptag', $wraptag, '', '', '', INPUT_REGULAR)).

            hInput('id', $id).
            hInput('ext', $ext).
            hInput('w', $w).
            hInput('h', $h).
            hInput('alt', $alt).
            hInput('caption', $caption).

            $endform
        );

        if ($step == 'build') {
            $url = imagesrcurl($id, $ext);

            switch ($type) {
                case 'textile' :
                    $alt = ($alt) ? ' ('.$alt.')' : '';
                    $modifiers = '';

                    if ($class) {
                        $modifiers .= '('.$class;

                        if ($html_id) {
                            $modifiers .= '#'.$html_id;
                        }

                        $modifiers .= ')';
                    } elseif ($html_id) {
                        $modifiers .= "(#$html_id)";
                    }

                    if ($style) {
                        $modifiers .= '{'.$style.'}';
                    }

                    $wraptag = ($wraptag) ? $wraptag.$modifiers . '. ' : '';

                    $out .= tdb(
                        ( ($wraptag) ? $wraptag : '') . '!'. ( ($wraptag) ? '' : $modifiers ) .$url.$alt.'!'
                    );
                    break;
                case 'html' :
                    $alt     = ' alt="'.txpspecialchars($alt).'"';
                    $caption = ($caption) ? ' title="'.txpspecialchars($caption).'"' : '';
                    $class   = ($class)   ? ' class="'.$class.'"' : '';
                    $html_id = ($html_id) ? ' id="'.$html_id.'"' : '';
                    $style   = ($style)   ? ' style="'.$style.'"' : '';

                    $out .= tdb(
                        ($wraptag ? "<$wraptag>" : '').
                        '<img src="'.$url.'" width="'.$w.'" height="'.$h.'"'.$alt.$caption.$html_id.$class.$style.' />'.
                        ($wraptag ? "</$wraptag>" : '')
                    );
                    break;
                case 'textpattern' :
                default:
                    $atts = array(
                        'class'   => $class,
                        'html_id' => $html_id,
                        'id'      => $id,
                        'style'   => $style,
                        'wraptag' => $wraptag
                    );
                    $out .= tdb(tb($tag_name, $atts));
                    break;
            }
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_file_download()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'form',
            'id'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('id',
                input_id($id)).

            tagRow('form',
                form_pop('form', 'file', $form)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_file_download_list()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'break',
            'category',
            'form',
            'label',
            'labeltag',
            'limit',
            'sort',
            'wraptag',
        ));

        $asc = ' ('.gTxt('ascending').')';
        $desc = ' ('.gTxt('descending').')';

        $sorts = array(
            'filename asc'   => gTxt('file_name').$asc,
            'filename desc'  => gTxt('file_name').$desc,
            'downloads asc'  => gTxt('downloads').$asc,
            'downloads desc' => gTxt('downloads').$desc,
            'rand()'         => 'Random'
        );

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(tdcs(hed(gTxt('tag_'.$tag_name),3),2) ).

            tagRow('category',
                file_category_pop($category)).

            tagRow('sort',
                ' '.selectInput('sort', $sorts, $sort, true)).

            tagRow('limit',
                input_limit($limit)).

            tagRow('label',
                fInput('text', 'label', $label, '', '', '', INPUT_REGULAR)).

            tagRow('labeltag',
                input_tag('labeltag', $labeltag)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('break',
                input_tag('break', $break)).

            tagRow('form',
                form_pop('form', 'file', $form)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        echo $out;
    }

    // -------------------------------------------------------------

    function tag_file_download_created()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'format'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('time_format',
                fInput('text', 'format', $format, '', '', '', 15)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_file_download_modified()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'format'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('time_format',
                fInput('text', 'format', $format, '', '', '', 15)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_file_download_size()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'decimals',
            'format'
        ));

        $formats = array(
            'b' => 'Bytes',
            'k' => 'Kilobytes',
            'm' => 'Megabytes',
            'g' => 'Gigabytes',
            't' => 'Terabytes',
            'p' => 'Petabytes',
            'e' => 'Exabytes',
            'z' => 'Zettabytes',
            'y' => 'Yottabytes',
        );

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(hed(gTxt('tag_'.$tag_name), 3)
            , 2)
            ).

            tagRow('size_format',
                ' '.selectInput('format', $formats, $format, true)).

            tagRow('decimals',
                fInput('text', 'decimals', $decimals, '', '', '', 4)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_file_download_link()
    {
        global $step, $endform, $tag_name, $permlink_mode;

        $atts = gpsa(array(
            'filename',
            'id'
        ));

        extract($atts);

        $thing = gps('thing');

        $type = gps('type');
        $description = gps('description');

        $types = array(
            'textile'     => 'Textile',
            'textpattern' => 'Textpattern',
            'html'        => 'HTML'
        );

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('type',
                ''.selectInput('type', $types, ($type ? $type : 'textpattern'), true)).

            tagRow('id',
                input_id($id)).

            tagRow('filename',
                fInput('text', 'filename', $filename, '', '', '', INPUT_REGULAR)).

            tagRow('link_text',
                fInput('text', 'thing', ($thing ? $thing : $filename), '', '', '', INPUT_REGULAR)).

            tagRow('description',
                '<textarea name="description" cols="'.INPUT_REGULAR.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.$description.'</textarea>').

            $endform
        );

        if ($step == 'build') {
            $description = str_replace('&', '&#38;', txpspecialchars($description));
            $urlinfo = parse_url(hu);
            $url = ($permlink_mode == 'messy') ?
                $urlinfo['path'].'index.php?s=file_download'.($type == 'textile' ? '&' : a).'id='.$id:
                $urlinfo['path'].gTxt('file_download').'/'.$id.($filename ? '/'.urlencode($filename) : '');

            switch ($type) {
                case 'textile' :
                    $thing = ($thing) ? $thing : $filename;
                    $description = ($description) ? ' ('.$description.')' : '';
                    $out .= tdb('"'.$thing.$description.'":'.$url);
                    break;
                case 'html' :
                    $thing = ($thing) ? $thing : $filename;
                    $description = ($description) ? ' title="'.$description.'"' : '';
                    $out .= tdb(href($thing, $url, $description));
                    break;
                case 'textpattern' :
                default :
                    $atts = array('id' => $id);
                    $thing = ($thing) ? $thing : '<txp:file_download_name />';
                    $out .= tdb(tb($tag_name, $atts, $thing));
                    break;
            }
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_file_download_name()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_file_download_downloads()
    {
        global $step, $endform, $tag_name;

        return form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            endTable()
        ).

        tdb(tb($tag_name));
    }

    // -------------------------------------------------------------

    function tag_file_download_category()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'escape',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('escape',
                escape_pop($escape)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }

    // -------------------------------------------------------------

    function tag_file_download_description()
    {
        global $step, $endform, $tag_name;

        $atts = gpsa(array(
            'class',
            'escape',
            'wraptag'
        ));

        extract($atts);

        $out = form(
            startTable('tagbuilder').

            tr(
                tdcs(
                    hed(gTxt('tag_'.$tag_name), 3)
                , 2)
            ).

            tagRow('escape',
                escape_pop($escape)).

            tagRow('wraptag',
                input_tag('wraptag', $wraptag)).

            tagRow('class',
                fInput('text', 'class', $class, '', '', '', INPUT_REGULAR)).

            $endform
        );

        if ($step == 'build') {
            $out .= tdb(tb($tag_name, $atts));
        }

        return $out;
    }
}

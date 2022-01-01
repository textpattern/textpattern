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
 * Tag builder.
 *
 * @package Admin\Tag
 */

namespace Textpattern\Tag;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

$tagName = gps('tag_name');
$panel = gps('panel');

if ($tagName) {
    echo \Txp::get('\Textpattern\Tag\BuilderTags')->renderTagHelp($tagName, $panel);
} elseif ($panel) {
    echo \Txp::get('\Textpattern\Tag\BuilderTags')->tagbuildDialog($panel);
}

/**
 * Collection of tag builder functions.
 *
 * @package Admin\Tag
 */

class BuilderTags
{
    /**
     * HTML block for the header portion of the form tag.
     *
     * @var string
     */

    private $startblock;

    /**
     * HTML block for the end of the form tag.
     *
     * Includes submit button and hidden form elements.
     *
     * @var string
     */

    private $endform;

    /**
     * The name of the tagbuilder tag that is currently being displayed.
     *
     * @var string
     */

    private $tagname;

    /**
     * Return a list of tag builder tags for the given event.
     *
     * @param  $ev The event (type of dialog) to build: page, form
     * @return HTML
     */

    public function tagbuildDialog($ev)
    {
        $listActions = graf(
            href('<span class="ui-icon ui-icon-arrowthickstop-1-s"></span> '.gTxt('expand_all'), '#', array(
                'class'         => 'txp-expand-all',
                'aria-controls' => 'tagbuild_links',
            )).
            href('<span class="ui-icon ui-icon-arrowthickstop-1-n"></span> '.gTxt('collapse_all'), '#', array(
                'class'         => 'txp-collapse-all',
                'aria-controls' => 'tagbuild_links',
            )), array('class' => 'txp-actions')
        );

        $tagbuild_items = array();

        // Generate the tagbuilder links.
        // Format of each entry is popTagLink -> array ( gTxt string, class/ID ).
        switch ($ev) {
            case "form":
                $tagbuild_items = array(
                    'article'         => array('articles', 'article-tags'),
                    'link'            => array('links', 'link-tags'),
                    'comment'         => array('comments', 'comment-tags'),
                    'comment_details' => array('comment_details', 'comment-detail-tags'),
                    'comment_form'    => array('comment_form', 'comment-form-tags'),
                    'search_result'   => array('search_results_form', 'search-result-tags'),
                    'file_download'   => array('file_download_tags', 'file-tags'),
                    'category'        => array('category_tags', 'category-tags'),
                    'section'         => array('section_tags', 'section-tags'),
                );
                break;
            case "page":
                $tagbuild_items = array(
                    'page_article'     => array('page_article_hed', 'article-tags'),
                    'page_article_nav' => array('page_article_nav_hed', 'article-nav-tags'),
                    'page_nav'         => array('page_nav_hed', 'nav-tags'),
                    'page_xml'         => array('page_xml_hed', 'xml-tags'),
                    'page_misc'        => array('page_misc_hed', 'misc-tags'),
                    'page_file'        => array('page_file_hed', 'file-tags'),
                );
                break;
        }

        $tagbuild_links = '';

        foreach ($tagbuild_items as $tb => $item) {
            $tagbuild_links .= wrapRegion($item[1].'_group', $this->popTagLinks($tb, $ev), $item[1], $item[0], $item[1]);
        }

        // Tag builder dialog.
        if ($tagbuild_links) {
            return $listActions.$tagbuild_links;
        }

        return false;
    }

    /**
     * List of tags in their corresponding groups.
     *
     * @see popTagLinks()
     * @return array
     */

    protected function tagbuildGroups()
    {
        return array(
            'article_tags' => array(
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
            ),
            'link_tags' => array(
                'link',
                'linkdesctitle',
                'link_name',
                'link_description',
                'link_category',
                'link_date',
            ),
            'comment_tags' => array(
                'comments',
                'comments_form',
                'comments_preview',
            ),
            'comment_details_tags' => array(
                'comment_permlink',
                'comment_name',
                'comment_email',
                'comment_web',
                'comment_time',
                'comment_message',
            ),
            'comment_form_tags' => array(
                'comment_name_input',
                'comment_email_input',
                'comment_web_input',
                'comment_message_input',
                'comment_remember',
                'comment_preview',
                'comment_submit',
            ),
            'search_result_tags' => array(
                'search_result_title',
                'search_result_excerpt',
                'search_result_date',
                'search_result_url',
            ),
            'file_download_tags' => array(
                'file_download_link',
                'file_download_name',
                'file_download_description',
                'file_download_category',
                'file_download_created',
                'file_download_modified',
                'file_download_size',
                'file_download_downloads',
            ),
            'category_tags' => array(
                'category',
                'if_category',
            ),
            'section_tags' => array(
                'section',
                'if_section',
            ),
            'page_article_tags' => array(
                'article',
                'article_custom',
            ),
            'page_article_nav_tags' => array(
                'prev_title',
                'next_title',
                'link_to_prev',
                'link_to_next',
                'older',
                'newer',
            ),
            'page_nav_tags' => array(
                'link_to_home',
                'section_list',
                'category_list',
                'popup',
                'recent_articles',
                'recent_comments',
                'related_articles',
                'search_input',
            ),
            'page_xml_tags' => array(
                'feed_link',
                'link_feed_link',
            ),
            'page_misc_tags' => array(
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
            ),
            'page_file_tags' => array(
                'file_download_list',
                'file_download',
                'file_download_link',
            ),
        );
    }

    /**
     * Return a list of tag builder links.
     *
     * @param  string $type  Tag type
     * @param  string $panel The panel (event) on which the builder is displayed
     * @return string HTML
     */

    protected function popTagLinks($type, $panel)
    {
        $tagGroups = $this->tagbuildGroups();
        $arname = $type.'_tags';

        $out = array();

        if (isset($tagGroups[$arname])) {
            foreach ($tagGroups[$arname] as $a) {
                $out[] = tag(popTag($a, gTxt('tag_'.$a), array('panel' => $panel)), 'li');
            }
        }

        return n.tag(n.join(n, $out).n, 'ul', array('class' => 'plain-list'));
    }

    /**
     * Return a single tag handler instance.
     *
     * @param  string $name  The tag
     * @param  string $panel The panel from which the tag was invoked
     * @return string|bool HTML or FALSE on error
     */

    public function renderTagHelp($name, $panel)
    {
        $this->tagname = (string)$name;
        $method = 'tag_'.$this->tagname;

        if (method_exists($this, $method)) {
            $backLink = '';

            if ($panel) {
                $backLink = graf(
                    href(
                        gTxt('go_back'),
                        array('event' => 'tag', 'panel' => $panel),
                        array('class' => 'txp-tagbuilder-link')
                    ),
                    array('class' => 'txp-actions')
                );
            }

            $this->startblock = $backLink.
                hed(gTxt('tag_'.$this->tagname), 2).
                href(
                    gTxt('documentation').sp.span(gTxt('opens_external_link'), array('class' => 'ui-icon ui-icon-extlink')),
                    'https://docs.textpattern.com/tags/'.$this->tagname,
                    array(
                        'class'  => 'txp-tagbuilder-docs-link',
                        'rel'    => 'external noopener',
                        'target' => '_blank',
                    )
                );

            $this->endform = graf(
                fInput('submit', '', gTxt('build'))
            ).
            eInput('tag').
            sInput('build').
            hInput('tag_name', $this->tagname).
            hInput('panel', $panel);

            return $this->$method($this->tagname);
        }

        return false;
    }

    /**
     * Render a form tag with the given content.
     *
     * @param  string $content The HTML form contents
     * @return string HTML
     */

    private function tagbuildForm($content)
    {
        return form($content, '', '', 'post', 'asynchtml txp-tagbuilder', 'txp-tagbuilder-output');
    }

    /**
     * Render an input widget.
     *
     * @param  string $label The label reference to use (will be subject to l10n)
     * @param  string $thing Content
     * @return string HTML
     */

    private function widget($label, $thing)
    {
        // TODO: Link to attribute help?
        return inputLabel(
            $label,
            $thing,
            $label
        );
    }

    /**
     * Render a set of input widgets.
     *
     * @param  array $widgets List of label => content pairs
     * @return string HTML
     */

    private function widgets($widgets = array())
    {
        $out = '';

        // Common global attributes
        /*
        $widgets += array(
            'escape'   => $this->tbInput('escape', gps('escape'), INPUT_REGULAR),
            'wraptag'  => $this->tbInput('wraptag', gps('wraptag')),
            'class'    => $this->tbInput('class', gps('class'), INPUT_REGULAR),
            'html_id'  => $this->tbInput('html_id', gps('html_id'), INPUT_REGULAR),
            'label'    => $this->tbInput('label', gps('label')),
            'labeltag' => $this->tbInput('labeltag', gps('labeltag'))
        );
        */

        // TODO: Link to attribute help?
        foreach ($widgets as $label => $thing) {
            $out .= $this->widget($label, $thing);
        }

        return $out;
    }

    /**
     * Generate a parameter-less Textpattern tag.
     *
     * @return string &lt;txp:tag /&gt;
     */

    private function tbNoAtts()
    {
        return $this->tagbuildForm($this->startblock.$this->widgets().$this->endform).$this->tdb($this->tb($this->tagname));
    }

    /**
     * Generate a Textpattern tag from the given attributes and content.
     *
     * @param  string $tag Tag name
     * @param  string $atts_list List of attribute => value pairs
     * @param  string $thing Tag container content
     * @return string &lt;txp:tag ...&gt;
     */

    private function tb($tag, $atts_list = array(), $thing = '')
    {
        $atts = array();

        // Common global attributes
        /*
        $atts_list += gpsa(array('escape', 'wraptag', 'class', 'html_id', 'label', 'labeltag'));
        */

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

    /**
     * Render a textarea to hold the built content.
     *
     * @param  string $thing Content
     * @return string HTML
     */

    private function tdb($thing)
    {
        return graf('<label for="txp-tagbuilder-output">'.gTxt('code').'</label>'.n.text_area(
            'txp-tagbuilder-output',
            '',
            '',
            $thing,
            'txp-tagbuilder-output',
            TEXTAREA_HEIGHT_SMALL,
            INPUT_LARGE
        ));
    }

    /**
     * Assemble the tag output container.
     *
     * @param  array $atts Attribute key => value pairs
     * @param  string $thing Tag container content
     * @return string HTML
     */

    private function build($atts, $thing = '')
    {
        global $step;

        $out = '';

        if ($step === 'build') {
            $out = $this->tdb($this->tb($this->tagname, $atts, $thing));
        }

        return $out;
    }

    /**
     * Render a HTML &lt;select&gt; list of time ranges.
     *
     * @param  string $time Currently selected value
     * @return string HTML
     */

    private function tbTimePop($time)
    {
        $vals = array(
            'past'   => gTxt('time_past'),
            'future' => gTxt('time_future'),
            'any'    => gTxt('time_any'),
        );

        return ' '.selectInput('time', $vals, $time, true, '', 'time');
    }

    /**
     * Render HTML boolean &lt;select&gt; options.
     *
     * @param  string $name Input name/ID
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbYesNoPop($select_name, $value)
    {
        $vals = array(
            1 => gTxt('yes'),
            0 => gTxt('no'),
        );

        if (is_numeric($value)) {
            $value = (int)$value;
        }

        return ' '.selectInput($select_name, $vals, $value, true, '', $select_name, true);
    }

    /**
     * Render a HTML &lt;select&gt; list of article status options.
     *
     * @param  string $value Currently selected value
     * @param  string $type  The flavor of status to return. article=full set, file=limited set
     * @return string HTML
     */

    private function tbStatusPop($value, $type = 'article')
    {
        $vals = array(
            STATUS_LIVE    => gTxt('live'),
            STATUS_STICKY  => gTxt('sticky'),
            STATUS_PENDING => gTxt('pending'),
            STATUS_DRAFT   => gTxt('draft'),
            STATUS_HIDDEN  => gTxt('hidden'),
        );

        if ($type !== 'article') {
            unset($vals[STATUS_DRAFT], $vals[STATUS_STICKY]);
        }

        return ' '.selectInput('status', $vals, $value, true, '', 'status');
    }

    /**
     * Render a HTML &lt;select&gt; list of sort options.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbSortPop($value)
    {
        $asc = ' ('.gTxt('ascending').')';
        $desc = ' ('.gTxt('descending').')';

        $vals = array(
            'Title asc'      => gTxt('title').$asc,
            'Title desc'     => gTxt('title').$desc,
            'Posted asc'     => gTxt('posted').$asc,
            'Posted desc'    => gTxt('posted').$desc,
            'LastMod asc'    => gTxt('last_modification').$asc,
            'LastMod desc'   => gTxt('last_modification').$desc,
            'Section asc'    => gTxt('section').$asc,
            'Section desc'   => gTxt('section').$desc,
            'Category1 asc'  => gTxt('category1').$asc,
            'Category1 desc' => gTxt('category1').$desc,
            'Category2 asc'  => gTxt('category2').$asc,
            'Category2 desc' => gTxt('category2').$desc,
            'rand()'         => gTxt('random'),
        );

        return ' '.selectInput('sort', $vals, $value, true, '', 'sort');
    }

    /**
     * Render a HTML &lt;select&gt; list of comment sort options.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbDiscussSortPop($value)
    {
        $asc = ' ('.gTxt('ascending').')';
        $desc = ' ('.gTxt('descending').')';

        $vals = array(
            'posted asc'  => gTxt('posted').$asc,
            'posted desc' => gTxt('posted').$desc,
        );

        return ' '.selectInput('sort', $vals, $value, true, '', 'sort');
    }

    /**
     * Render a HTML &lt;select&gt; list of article sort options.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbListSortPop($value)
    {
        $asc = ' ('.gTxt('ascending').')';
        $desc = ' ('.gTxt('descending').')';

        $vals = array(
            'title asc'  => gTxt('title').$asc,
            'title desc' => gTxt('title').$desc,
            'name asc'   => gTxt('name').$asc,
            'name desc'  => gTxt('name').$desc,
        );

        return ' '.selectInput('sort', $vals, $value, true, '', 'sort');
    }

    /**
     * Render a HTML &lt;select&gt; list of authors/users.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbAuthorPop($value)
    {
        $vals = array();

        $rs = safe_rows_start("name", 'txp_users', "1 = 1 ORDER BY name");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);

                $vals[$name] = $name;
            }

            return ' '.selectInput('author', $vals, $value, true, '', 'author');
        }
    }

    /**
     * Render a HTML &lt;select&gt; list of Sections.
     *
     * @param  string $select_name Input name/ID
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbSectionPop($select_name, $value)
    {
        $vals = array();

        $rs = safe_rows_start("name, title", 'txp_section', "name != 'default' ORDER BY name");

        if ($rs && numRows($rs) > 0) {
            while ($a = nextRow($rs)) {
                extract($a);

                $vals[$name] = $title;
            }

            return ' '.selectInput($select_name, $vals, $value, true, '', $select_name);
        }

        return gTxt('no_sections_available');
    }

    /**
     * Render a HTML &lt;select&gt; list of Categories.
     *
     * @param  string $value Currently selected value
     * @param  string $type Context to which the category applies
     * @return string HTML
     */

    private function tbCategoryPop($value, $type = 'article')
    {
        $vals = getTree('root', $type);

        if ($vals) {
            return ' '.treeSelectInput('category', $vals, $value, 'category');
        }

        return gTxt('no_categories_available');
    }

    /**
     * Render a HTML &lt;select&gt; list of category match options.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbMatchCatPop($value)
    {
        $vals = array(
            'Category'            => gTxt('category1').' '.gTxt('or').' '.gTxt('category2'),
            'Category1,Category2' => gTxt('category1').' '.gTxt('and').' '.gTxt('category2'),
            'Category1'           => gTxt('category1'),
            'Category2'           => gTxt('category2'),
        );

        return ' '.selectInput('match', $vals, $value, true, '', 'match');
    }

    /**
     * Render a HTML &lt;select&gt; list of pattern match types.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbPatternPop($value)
    {
        $vals = array(
            'exact' => gTxt('exact'),
            'any'   => gTxt('any'),
            'all'   => gTxt('all'),
        );

        return ' '.selectInput('match', $vals, $value, false, '', 'match');
    }

    /**
     * Render a HTML &lt;select&gt; list of context types.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbTypePop($value)
    {
        $vals = array(
            'article' => gTxt('article'),
            'image'   => gTxt('image'),
            'file'    => gTxt('file'),
            'link'    => gTxt('link'),
        );

        return ' '.selectInput('type', $vals, $value, true, '', 'type');
    }

    /**
     * Render a HTML &lt;select&gt; list of Themes.
     *
     * @param  string $value Currently selected value
     * @return string | bool HTML | false on error
     */

    private function tbThemePop($value)
    {
        $vals = array();

        $rs = safe_rows_start("name, title", 'txp_skin', "1 = 1 ORDER BY name, title");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);

                $vals[$name] = $title;
            }

            return ' '.selectInput('theme', $vals, $value, true, '', 'theme');
        }

        return false;
    }

    /**
     * Render a HTML &lt;select&gt; list of forms.
     *
     * @param  string $select_name Input name/ID
     * @param  string $type Form type
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbFormPop($select_name, $type = '', $value = '')
    {
        $vals = array();

        $type = ($type) ? "type = '".doSlash($type)."'" : "1 = 1";

        $rs = safe_rows_start("name", 'txp_form', "$type ORDER BY name");

        if ($rs and numRows($rs) > 0) {
            while ($a = nextRow($rs)) {
                extract($a);

                $vals[$name] = $name;
            }

            return ' '.selectInput($select_name, $vals, $value, true, '', $select_name);
        }

        return gTxt('no_forms_available');
    }

    /**
     * Render a HTML &lt;select&gt; list of Stylesheets.
     *
     * @param  string $value Currently selected value
     * @return string | bool HTML | false on error
     */

    private function tbCssPop($value)
    {
        $vals = array();

        $rs = safe_rows_start("name", 'txp_css', "1 = 1 ORDER BY name");

        if ($rs) {
            while ($a = nextRow($rs)) {
                extract($a);

                $vals[$name] = $name;
            }

            return ' '.selectInput('name', $vals, $value, true, '', 'name');
        }

        return false;
    }

    /**
     * Render a HTML &lt;select&gt; list of CSS formats.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbCssFormatPop($value)
    {
        $vals = array(
            'link' => '<link rel...',
            'url'  => 'css.php?...',
        );

        return ' '.selectInput('format', $vals, $value, true, '', 'format');
    }

    /**
     * Render a HTML &lt;select&gt; list of escape options.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbEscapePop($value)
    {
        $vals = array(
            '{att_empty}' => '',
            'html'        => 'HTML',
        );

        return ' '.selectInput('escape', $vals, $value, false, '', 'escape');
    }

    /**
     * Render a HTML &lt;select&gt; list of feed flavours.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbFeedFlavorPop($value)
    {
        $vals = array(
            'atom' => 'Atom 1.0',
            'rss'  => 'RSS 2.0',
        );

        return ' '.selectInput('flavor', $vals, $value, true, '', 'flavor');
    }

    /**
     * Render a HTML &lt;select&gt; list of feed formats.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbFeedFormatPop($value)
    {
        $vals = array(
            'a'    => '<a href...',
            'link' => '<link rel...',
        );

        return ' '.selectInput('format', $vals, $value, true, '', 'format');
    }

    /**
     * Render a HTML &lt;select&gt; list of author formats.
     *
     * @param  string $value Currently selected value
     * @return string HTML
     */

    private function tbAuthorFormatPop($value)
    {
        $vals = array(
            'link' => '<a href...',
            'url'  => gTxt('url'),
        );

        return ' '.selectInput('format', $vals, $value, true, '', 'format');
    }

    /**
     * Render a HTML &lt;input&gt; tag.
     *
     * @param  string $name Input name
     * @param  string $value Input value
     * @param  string $size Input size in characters
     * @param  string $id Input HTML ID. Uses $name if omitted
     * @return string HTML
     */

    private function tbInput($name, $value, $size = INPUT_SMALL, $id = null)
    {
        return fInput('text', $name, $value, '', '', '', $size, '', (($id === null) ? $name : $id));
    }

    /**
     * Tag builder &lt;txp:article&gt; tag.
     */

    function tag_article()
    {
        $atts = gpsa(array(
            'allowoverride',
            'break',
            'class',
            'form',
            'frontpage',
            'keywords',
            'label',
            'labeltag',
            'limit',
            'listform',
            'offset',
            'pageby',
            'pgonly',
            'searchall',
            'searchform',
            'searchsticky',
            'sort',
            'status',
            'time',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'status'        => $this->tbStatusPop($status),
                'time'          => $this->tbTimePop($time),
                'searchall'     => $this->tbYesNoPop('searchall', $searchall),
                'searchsticky'  => $this->tbYesNoPop('searchsticky', $searchsticky),
                'on_front_page' => $this->tbYesNoPop('frontpage', $frontpage),
                'keywords'      => '<textarea name="keywords" id="keywords">'.txpspecialchars($keywords).'</textarea>',
                'limit'         => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'        => $this->tbInput('offset', $offset, INPUT_TINY),
                'pageby'        => $this->tbInput('pageby', $pageby, INPUT_TINY),
                'sort'          => $this->tbSortPop($sort),
                'pgonly'        => $this->tbYesNoPop('pgonly', $pgonly),
                'allowoverride' => $this->tbYesNoPop('allowoverride', $allowoverride),
                'label'         => $this->tbInput('label', $label),
                'labeltag'      => $this->tbInput('labeltag', $labeltag),
                'wraptag'       => $this->tbInput('wraptag', $wraptag),
                'class'         => $this->tbInput('class', $class, INPUT_REGULAR),
                'break'         => $this->tbInput('break', $break),
                'form'          => $this->tbFormPop('form', 'article', $form),
                'listform'      => $this->tbFormPop('listform', 'article', $listform),
                'searchform'    => $this->tbFormPop('searchform', 'article', $searchform),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:article_custom&gt; tag.
     */

    function tag_article_custom()
    {
        $atts = gpsa(array(
            'allowoverride',
            'author',
            'break',
            'category',
            'class',
            'excerpted',
            'exclude',
            'expired',
            'form',
            'frontpage',
            'id',
            'keywords',
            'label',
            'labeltag',
            'limit',
            'match',
            'month',
            'offset',
            'section',
            'sort',
            'status',
            'time',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'id'            => $this->tbInput('id', $id),
                'status'        => $this->tbStatusPop($status),
                'section'       => $this->tbSectionPop('section', $section),
                'category'      => $this->tbCategoryPop($category),
                'match_type'    => $this->tbMatchCatPop($match),
                'exclude'       => $this->tbInput('exclude', $exclude, INPUT_REGULAR),
                'time'          => $this->tbTimePop($time),
                'month'         => fInput(
                    'text',
                    'month',
                    $month,
                    '',
                    '',
                    '',
                    7,
                    '',
                    'month'
                ).' ('.gTxt('yyyy-mm').')',
                'keywords'      => '<textarea name="keywords" id="keywords">'.txpspecialchars($keywords).'</textarea>',
                'has_excerpt'   => $this->tbYesNoPop('excerpted', $excerpted),
                'frontpage'     => $this->tbYesNoPop('frontpage', $frontpage),
                'expired'       => $this->tbYesNoPop('expired', $expired),
                'author'        => $this->tbAuthorPop($author),
                'sort'          => $this->tbSortPop($sort),
                'limit'         => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'        => $this->tbInput('offset', $offset, INPUT_TINY),
                'allowoverride' => $this->tbYesNoPop('allowoverride', $allowoverride),
                'label'         => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag'      => $this->tbInput('labeltag', $labeltag),
                'wraptag'       => $this->tbInput('wraptag', $wraptag),
                'class'         => $this->tbInput('class', $class, INPUT_REGULAR),
                'break'         => $this->tbInput('break', $break),
                'form'          => $this->tbFormPop('form', 'article', $form),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:article_image&gt; tag.
     */

    function tag_article_image()
    {
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

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'use_thumbnail' => $this->tbYesNoPop('thumbnail', $thumbnail),
                'escape'        => $this->tbEscapePop($escape),
                'html_id'       => $this->tbInput('html_id', $html_id, INPUT_REGULAR),
                'class'         => $this->tbInput('class', $class, INPUT_REGULAR),
                'inline_style'  => $this->tbInput('style', $style, INPUT_REGULAR, 'inline_style'),
                'wraptag'       => $this->tbInput('wraptag', $wraptag),
                'width'         => $this->tbInput('width', $width, INPUT_SMALL),
                'height'        => $this->tbInput('height', $height, INPUT_SMALL),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:author&gt; tag.
     */

    function tag_author()
    {
        $atts = gpsa(array(
            'escape',
            'format',
            'link',
            'section',
            'this_section',
            'title',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'escape'              => $this->tbEscapePop($escape),
                'format'              => $this->tbAuthorFormatPop($format),
                'link_to_this_author' => $this->tbYesNoPop('link', $link),
                'section'             => $this->tbSectionPop('section', $section),
                'this_section'        => $this->tbYesNoPop('this_section', $this_section),
                'title'               => $this->tbYesNoPop('title', $title),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:body&gt; tag.
     */

    function tag_body()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:breadcrumb&gt; tag.
     */

    function tag_breadcrumb()
    {
        $atts = gpsa(array(
            'category',
            'class',
            'label',
            'limit',
            'link',
            'linkclass',
            'offset',
            'section',
            'separator',
            'title',
            'type',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'breadcrumb_separator' => $this->tbInput('separator', $separator, INPUT_XSMALL, 'breadcrumb_separator'),
                'breadcrumb_linked'    => $this->tbYesNoPop('link', $link),
                'linkclass'            => $this->tbInput('linkclass', $linkclass, INPUT_REGULAR),
                'label'                => $this->tbInput('label', $label, INPUT_REGULAR),
                'title'                => $this->tbInput('title', $title, INPUT_REGULAR),
                'type'                 => $this->tbTypePop($type),
                'category'             => $this->tbInput('category', $category),
                'section'              => $this->tbSectionPop('section', $section),
                'limit'                => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'               => $this->tbInput('offset', $offset, INPUT_TINY),
                'wraptag'              => $this->tbInput('wraptag', $wraptag),
                'class'                => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:category&gt; tag.
     */

    function tag_category()
    {
        $atts = gpsa(array(
            'class',
            'link',
            'name',
            'section',
            'this_section',
            'title',
            'type',
            'url',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'name'                  => $this->tbInput('name', $name, INPUT_REGULAR),
                'link_to_this_category' => $this->tbYesNoPop('link', $link),
                'section'               => $this->tbSectionPop('section', $section),
                'this_section'          => $this->tbYesNoPop('this_section', $this_section),
                'title'                 => $this->tbYesNoPop('title', $title),
                'type'                  => $this->tbTypePop($type),
                'url'                   => $this->tbYesNoPop('url', $url),
                'wraptag'               => $this->tbInput('wraptag', $wraptag),
                'class'                 => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:category_list&gt; tag.
     */

    function tag_category_list()
    {
        $atts = gpsa(array(
            'active_class',
            'break',
            'categories',
            'children',
            'class',
            'exclude',
            'form',
            'html_id',
            'label',
            'labeltag',
            'limit',
            'offset',
            'parent',
            'section',
            'sort',
            'this_section',
            'type',
            'wraptag',
        ));

        if (!isset($_POST['label'])) {
            $atts['label'] = gTxt('categories');
        }

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'type'                  => $this->tbTypePop($type),
                'parent'                => $this->tbInput('parent', $parent, INPUT_REGULAR),
                'categories'            => $this->tbInput('categories', $categories, INPUT_REGULAR),
                'exclude'               => $this->tbInput('exclude', $exclude, INPUT_REGULAR),
                'this_section'          => $this->tbYesNoPop('this_section', $this_section),
                'category_list_section' => $this->tbSectionPop('section', $section),
                'depth'                 => $this->tbInput('children', $children, INPUT_TINY),
                'form'                  => $this->tbFormPop('form', '', $form),
                'sort'                  => $this->tbListSortPop($sort),
                'limit'                 => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'                => $this->tbInput('offset', $offset, INPUT_TINY),
                'label'                 => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag'              => $this->tbInput('labeltag', $labeltag),
                'wraptag'               => $this->tbInput('wraptag', $wraptag),
                'class'                 => $this->tbInput('class', $class, INPUT_REGULAR),
                'active_class'          => $this->tbInput('active_class', $active_class, INPUT_REGULAR),
                'html_id'               => $this->tbInput('html_id', $html_id, INPUT_REGULAR),
                'break'                 => $this->tbInput('break', $break),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:category1&gt; tag.
     */

    function tag_category1()
    {
        $atts = gpsa(array(
            'class',
            'link',
            'title',
            'section',
            'this_section',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'title'                 => $this->tbYesNoPop('title', $title),
                'link_to_this_category' => $this->tbYesNoPop('link', $link),
                'section'               => $this->tbSectionPop('section', $section),
                'this_section'          => $this->tbYesNoPop('this_section', $this_section),
                'wraptag'               => $this->tbInput('wraptag', $wraptag),
                'class'                 => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:category2&gt; tag.
     */

    function tag_category2()
    {
        $atts = gpsa(array(
            'class',
            'link',
            'title',
            'section',
            'this_section',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'title'                 => $this->tbYesNoPop('title', $title),
                'link_to_this_category' => $this->tbYesNoPop('link', $link),
                'section'               => $this->tbSectionPop('section', $section),
                'this_section'          => $this->tbYesNoPop('this_section', $this_section),
                'wraptag'               => $this->tbInput('wraptag', $wraptag),
                'class'                 => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comment_email&gt; tag.
     */

    function tag_comment_email()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:comment_email_input&gt; tag.
     */

    function tag_comment_email_input()
    {
        $atts = gpsa(array(
            'aria_label',
            'class',
            'placeholder',
            'size',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'input_size'  => $this->tbInput('size', $size, INPUT_TINY),
                'class'       => $this->tbInput('class', $class),
                'placeholder' => $this->tbInput('placeholder', $placeholder),
                'aria_label'  => $this->tbInput('aria_label', $aria_label),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comment_message&gt; tag.
     */

    function tag_comment_message()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:comment_message_input&gt; tag.
     */

    function tag_comment_message_input()
    {
        $atts = gpsa(array(
            'cols',
            'rows',
            'aria_label',
            'class',
            'placeholder',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'msgcols'     => $this->tbInput('cols', $cols, INPUT_TINY),
                'msgrows'     => $this->tbInput('rows', $rows, INPUT_TINY),
                'class'       => $this->tbInput('class', $class),
                'placeholder' => $this->tbInput('placeholder', $placeholder),
                'aria_label'  => $this->tbInput('aria_label', $aria_label),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comment_name&gt; tag.
     */

    function tag_comment_name()
    {
        $atts = gpsa(array(
            'link',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget('comment_name_link', $this->tbYesNoPop('link', $link)).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comment_name_input&gt; tag.
     */

    function tag_comment_name_input()
    {
        $atts = gpsa(array(
            'aria_label',
            'class',
            'placeholder',
            'size',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'input_size'  => $this->tbInput('size', $size, INPUT_TINY),
                'class'       => $this->tbInput('class', $class),
                'placeholder' => $this->tbInput('placeholder', $placeholder),
                'aria_label'  => $this->tbInput('aria_label', $aria_label),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comment_permlink&gt; tag.
     */

    function tag_comment_permlink()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:comment_preview&gt; tag.
     */

    function tag_comment_preview()
    {
        $atts = gpsa(array(
            'label',
            'class',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'class' => $this->tbInput('class', $class),
                'label' => $this->tbInput('label', $label),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comment_remember&gt; tag.
     */

    function tag_comment_remember()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:comment_submit&gt; tag.
     */

    function tag_comment_submit()
    {
        $atts = gpsa(array(
            'label',
            'class',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'class' => $this->tbInput('class', $class),
                'label' => $this->tbInput('label', $label),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comment_time&gt; tag.
     */

    function tag_comment_time()
    {
        $atts = gpsa(array(
            'format',
            'gmt',
            'lang',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'time_format' => $this->tbInput('format', $format, INPUT_MEDIUM, 'time_format'),
                'gmt'         => $this->tbYesNoPop('gmt', $gmt),
                'locale'      => $this->tbInput('lang', $lang, INPUT_MEDIUM, 'locale'),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comment_web&gt; tag.
     */

    function tag_comment_web()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:comment_web_input&gt; tag.
     */

    function tag_comment_web_input()
    {
        $atts = gpsa(array(
            'aria_label',
            'class',
            'placeholder',
            'size',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'input_size'  => $this->tbInput('size', $size, INPUT_TINY),
                'class'       => $this->tbInput('class', $class),
                'placeholder' => $this->tbInput('placeholder', $placeholder),
                'aria_label'  => $this->tbInput('aria_label', $aria_label),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comments&gt; tag.
     */

    function tag_comments()
    {
        $atts = gpsa(array(
            'break',
            'class',
            'form',
            'limit',
            'offset',
            'sort',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'form'    => $this->tbFormPop('form', 'comment', $form),
                'sort'    => $this->tbDiscussSortPop($sort),
                'limit'   => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'  => $this->tbInput('offset', $offset, INPUT_TINY),
                'wraptag' => $this->tbInput('wraptag', $wraptag),
                'class'   => $this->tbInput('class', $class, INPUT_REGULAR),
                'break'   => $this->tbInput('break', $break),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comments_form&gt; tag.
     */

    function tag_comments_form()
    {
        $atts = gpsa(array(
            'class',
            'form',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'class'   => $this->tbInput('class', $class),
                'form'    => $this->tbFormPop('form', 'comment', $form),
                'wraptag' => $this->tbInput('wraptag', $wraptag),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comments_invite&gt; tag.
     */

    function tag_comments_invite()
    {
        $atts = gpsa(array(
            'class',
            'showcount',
            'textonly',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'textonly'  => $this->tbYesNoPop('textonly', $textonly),
                'showcount' => $this->tbYesNoPop('showcount', $showcount),
                'wraptag'   => $this->tbInput('wraptag', $wraptag),
                'class'     => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:comments_preview&gt; tag.
     */

    function tag_comments_preview()
    {
        $atts = gpsa(array(
            'class',
            'form',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'form'    => $this->tbFormPop('form', 'comment', $form),
                'wraptag' => $this->tbInput('wraptag', $wraptag),
                'class'   => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:css&gt; tag.
     */

    function tag_css()
    {
        $atts = gpsa(array(
            'format',
            'media',
            'name',
            'rel',
            'theme',
            'title',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'name'   => $this->tbCssPop($name),
                'skin'   => $this->tbThemePop($theme),
                'format' => $this->tbCssFormatPop($format),
                'media'  => $this->tbInput('media', $media, INPUT_REGULAR),
                'rel'    => $this->tbInput('rel', $rel, INPUT_REGULAR),
                'title'  => $this->tbInput('title', $title, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:email&gt; tag.
     */

    function tag_email()
    {
        $atts = gpsa(array(
            'email',
            'linktext',
            'title',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'email_address' => $this->tbInput('email', $email, INPUT_REGULAR, 'email_address'),
                'tooltip'       => $this->tbInput('title', $title, INPUT_REGULAR, 'tooltip'),
                'link_text'     => $this->tbInput('linktext', $linktext, INPUT_REGULAR, 'link_text'),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:excerpt&gt; tag.
     */

    function tag_excerpt()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:feed_link&gt; tag.
     */

    function tag_feed_link()
    {
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

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'flavor'   => $this->tbFeedFlavorPop($flavor),
                'format'   => $this->tbFeedFormatPop($format),
                'section'  => $this->tbSectionPop('section', $section),
                'category' => $this->tbCategoryPop($category),
                'limit'    => $this->tbInput('limit', $limit, INPUT_TINY),
                'label'    => $this->tbInput('label', $label, INPUT_REGULAR),
                'title'    => $this->tbInput('title', $title, INPUT_REGULAR),
                'wraptag'  => $this->tbInput('wraptag', $wraptag),
                'class'    => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:file_download&gt; tag.
     */

    function tag_file_download()
    {
        $atts = gpsa(array(
            'filename',
            'form',
            'id',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'id'       => $this->tbInput('id', $id),
                'filename' => $this->tbInput('filename', $filename, INPUT_REGULAR),
                'form'     => $this->tbFormPop('form', 'file', $form),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:file_download_category&gt; tag.
     */

    function tag_file_download_category()
    {
        $atts = gpsa(array(
            'class',
            'escape',
            'title',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'title'   => $this->tbYesNoPop('title', $title),
                'escape'  => $this->tbEscapePop($escape),
                'wraptag' => $this->tbInput('wraptag', $wraptag),
                'class'   => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:file_download_created&gt; tag.
     */

    function tag_file_download_created()
    {
        $atts = gpsa(array(
            'format',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget('time_format', $this->tbInput('format', $format, INPUT_MEDIUM, 'time_format')).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:file_download_description&gt; tag.
     */

    function tag_file_download_description()
    {
        $atts = gpsa(array(
            'class',
            'escape',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'escape'  => $this->tbEscapePop($escape),
                'wraptag' => $this->tbInput('wraptag', $wraptag),
                'class'   => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:file_download_downloads&gt; tag.
     */

    function tag_file_download_downloads()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:file_download_link&gt; tag.
     */

    function tag_file_download_link()
    {
        global $step, $permlink_mode;

        $atts = gpsa(array(
            'filename',
            'id',
            'linktext',
            'type',
            'description',
        ));

        if (!isset($_POST['type'])) {
            $atts['type'] = 'textpattern';
        }

        extract($atts);

        $types = array(
            'textile'     => 'Textile',
            'textpattern' => 'Textpattern',
            'html'        => 'HTML',
        );

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'type'        => ''.selectInput('type', $types, $type, false, '', 'type'),
                'id'          => $this->tbInput('id', $id),
                'filename'    => $this->tbInput('filename', $filename, INPUT_REGULAR),
                'link_text'   => $this->tbInput('linktext', ($linktext ? $linktext : ''), INPUT_REGULAR, 'link_text'),
                'description' => '<textarea id="description" name="description" cols="'.INPUT_REGULAR.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.txpspecialchars($description).'</textarea>',
            )).
            $this->endform
        );

        if ($step === 'build') {
            $desc = str_replace('&', '&#38;', txpspecialchars($description));
            $urlinfo = parse_url(hu);
            $url = ($permlink_mode === 'messy')
                ? $urlinfo['path'].'index.php?s=file_download'.($type === 'textile' ? '&' : a).'id='.$id
                : $urlinfo['path'].'file_download'.'/'.$id.($filename ? '/'.urlencode($filename) : '');

            switch ($type) {
                case 'textile':
                    $link = ($linktext) ? $linktext : $filename;
                    $desc = ($desc) ? ' ('.$desc.')' : '';
                    $out .= $this->tdb('"'.$link.$desc.'":'.$url);
                    break;
                case 'html':
                    $link = ($linktext) ? $linktext : $filename;
                    $desc = ($desc) ? ' title="'.$desc.'"' : '';
                    $out .= $this->tdb(href($link, $url, $desc));
                    break;
                case 'textpattern':
                default:
                    $atts = array('id' => $id);
                    $link = ($linktext) ? $linktext : '<txp:file_download_name />';
                    $out .= $this->build($atts, $link);
                    break;
            }
        }

        return $out;
    }

    /**
     * Tag builder &lt;txp:file_download_list&gt; tag.
     *
     * Not adding realname attribute as it's pretty much the same as author.
     */

    function tag_file_download_list()
    {
        $atts = gpsa(array(
            'author',
            'auto_detect',
            'break',
            'category',
            'id',
            'form',
            'label',
            'labeltag',
            'limit',
            'offset',
            'pageby',
            'sort',
            'status',
            'wraptag',
        ));

        $asc = ' ('.gTxt('ascending').')';
        $desc = ' ('.gTxt('descending').')';

        $sorts = array(
            'filename asc'   => gTxt('file_name').$asc,
            'filename desc'  => gTxt('file_name').$desc,
            'downloads asc'  => gTxt('downloads').$asc,
            'downloads desc' => gTxt('downloads').$desc,
            'rand()'         => 'Random',
        );

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'author'     => $this->tbAuthorPop($author),
                'category'   => $this->tbCategoryPop($category, 'file'),
                'id'         => $this->tbInput('id', $id),
                'status'     => $this->tbStatusPop($status, 'file'),
                'sort'       => ' '.selectInput('sort', $sorts, $sort, true),
                'limit'      => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'     => $this->tbInput('offset', $offset, INPUT_TINY),
                'pageby'     => $this->tbInput('pageby', $pageby, INPUT_TINY),
                'label'      => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag'   => $this->tbInput('labeltag', $labeltag),
                'wraptag'    => $this->tbInput('wraptag', $wraptag),
                'break'      => $this->tbInput('break', $break),
                'form'       => $this->tbFormPop('form', 'file', $form),
                'match_type' => $this->tbInput('auto_detect', $auto_detect, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:file_download_modified&gt; tag.
     */

    function tag_file_download_modified()
    {
        $atts = gpsa(array(
            'format',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget(
                'time_format',
                $this->tbInput('format', $format, INPUT_MEDIUM, 'time_format')
            ).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:file_download_name&gt; tag.
     */

    function tag_file_download_name()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:file_download_size&gt; tag.
     */

    function tag_file_download_size()
    {
        $atts = gpsa(array(
            'decimals',
            'format',
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

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'size_format' => ' '.selectInput('format', $formats, $format, true, '', 'size_format'),
                'decimals'    => $this->tbInput('decimals', $decimals, INPUT_XSMALL),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:if_category&gt; tag.
     */

    function tag_if_category()
    {
        $atts = gpsa(array(
            'category',
            'name',
            'parent',
            'type',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'category' => $this->tbInput('category', $category, INPUT_REGULAR),
                'parent'   => $this->tbInput('parent', $parent, INPUT_REGULAR),
                'name'     => $this->tbInput('name', $name, INPUT_REGULAR),
                'type'     => $this->tbTypePop($type),
            )).
            $this->endform
        ).
        $this->build($atts, gTxt('...'));

        return $out;
    }

    /**
     * Tag builder &lt;txp:if_section&gt; tag.
     */

    function tag_if_section()
    {
        $atts = gpsa(array(
            'name',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget('name', $this->tbSectionPop('name', $name)).
            $this->endform
        ).
        $this->build($atts, gTxt('...'));

        return $out;
    }

    /**
     * Tag builder &lt;txp:image&gt; tag.
     */

    function tag_image()
    {
        global $step;

        $atts = gpsa(array(
            'alt',
            'caption',
            'class',
            'escape',
            'ext',
            'h',
            'html_id',
            'id',
            'name',
            'style',
            'type',
            'w',
            'wraptag',
        ));

        if (!isset($_POST['type'])) {
            $atts['type'] = 'textpattern';
        }

        extract($atts);

        $types = array(
            'textile'     => 'Textile',
            'textpattern' => 'Textpattern',
            'html'        => 'HTML',
        );

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'type'         => ''.selectInput(
                    'type',
                    $types,
                    $type,
                    false,
                    '',
                    'type'
                ),
                'escape'       => $this->tbEscapePop($escape),
                'html_id'      => $this->tbInput('html_id', $html_id, INPUT_REGULAR),
                'class'        => $this->tbInput('class', $class, INPUT_REGULAR),
                'inline_style' => $this->tbInput('style', $style, INPUT_REGULAR, 'inline_style'),
                'wraptag'      => $this->tbInput('wraptag', $wraptag),
            )).
            hInput('id', $id).
            hInput('ext', $ext).
            hInput('w', $w).
            hInput('h', $h).
            hInput('alt', $alt).
            hInput('caption', $caption).
            $this->endform
        );

        $alt = urldecode($alt);
        $caption = urldecode($caption);

        if ($step === 'build') {
            $url = imagesrcurl($id, $ext);

            switch ($type) {
                case 'textile':
                    $alternate = ($alt) ? ' ('.$alt.')' : '';
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

                    $wrap = ($wraptag) ? $wraptag.$modifiers.'. ' : '';

                    $out .= $this->tdb(
                        (($wrap) ? $wrap : '').'!'.(($wrap) ? '' : $modifiers).$url.$alternate.'!'
                    );
                    break;
                case 'html':
                    $alternate = ' alt="'.txpspecialchars($alt).'"';
                    $cap = ($caption) ? ' title="'.txpspecialchars($caption).'"' : '';
                    $cls = ($class) ? ' class="'.$class.'"' : '';
                    $htmlid = ($html_id) ? ' id="'.$html_id.'"' : '';
                    $inlinestyle = ($style) ? ' style="'.$style.'"' : '';

                    $out .= $this->tdb(
                        ($wraptag ? "<$wraptag>" : '').
                        '<img src="'.$url.'" width="'.$w.'" height="'.$h.'"'.$alternate.$cap.$htmlid.$cls.$inlinestyle.' />'.
                        ($wraptag ? "</$wraptag>" : '')
                    );
                    break;
                case 'textpattern':
                default:
                    $atts = array(
                        'class'   => $class,
                        'escape'  => $escape,
                        'html_id' => $html_id,
                        'id'      => $id,
                        'style'   => $style,
                        'wraptag' => $wraptag,
                    );
                    $out .= $this->build($atts);
                    break;
            }
        }

        return $out;
    }

    /**
     * Tag builder &lt;txp:lang&gt; tag.
     */

    function tag_lang()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:link&gt; tag.
     */

    function tag_link()
    {
        $atts = gpsa(array(
            'id',
            'name',
            'rel',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'id'   => $this->tbInput('id', $id),
                'name' => $this->tbInput('name', $name, INPUT_REGULAR),
                'rel'  => $this->tbInput('rel', $rel, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:link_category&gt; tag.
     */

    function tag_link_category()
    {
        $atts = gpsa(array(
            'class',
            'label',
            'labeltag',
            'title',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'title'    => $this->tbYesNoPop('title', $title),
                'label'    => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag' => $this->tbInput('labeltag', $labeltag),
                'wraptag'  => $this->tbInput('wraptag', $wraptag),
                'class'    => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:link_date&gt; tag.
     */

    function tag_link_date()
    {
        $atts = gpsa(array(
            'format',
            'gmt',
            'lang',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'time_format' => $this->tbInput('format', $format, INPUT_MEDIUM, 'time_format'),
                'gmt'         => $this->tbYesNoPop('gmt', $gmt),
                'locale'      => $this->tbInput('lang', $lang, INPUT_MEDIUM, 'locale'),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:link_description&gt; tag.
     */

    function tag_link_description()
    {
        $atts = gpsa(array(
            'class',
            'escape',
            'label',
            'labeltag',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'escape'   => $this->tbEscapePop($escape),
                'label'    => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag' => $this->tbInput('labeltag', $labeltag),
                'wraptag'  => $this->tbInput('wraptag', $wraptag),
                'class'    => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:link_feed_link&gt; tag.
     */

    function tag_link_feed_link()
    {
        $atts = gpsa(array(
            'category',
            'class',
            'flavor',
            'format',
            'label',
            'title',
            'wraptag',
        ));

        extract($atts);

        $label = (!$label) ? 'XML' : $label;

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'flavor'   => $this->tbFeedFlavorPop($flavor),
                'format'   => $this->tbFeedFormatPop($format),
                'category' => $this->tbCategoryPop($category, 'link'),
                'label'    => $this->tbInput('label', $label, INPUT_REGULAR),
                'title'    => $this->tbInput('title', $title, INPUT_REGULAR),
                'wraptag'  => $this->tbInput('wraptag', $wraptag),
                'class'    => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:link_name&gt; tag.
     */

    function tag_link_name()
    {
        $atts = gpsa(array(
            'escape',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget('escape', $this->tbEscapePop($escape)).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:link_to_home&gt; tag.
     */

    function tag_link_to_home()
    {
        $atts = gpsa(array(
            'class',
        ));

        extract($atts);

        $linktext = isset($_POST['linktext']) ? ps('linktext') : gTxt('tag_home');

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'link_text' => $this->tbInput(
                    'linktext',
                    $linktext,
                    INPUT_REGULAR,
                    'link_text'
                ),
                'class'     => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts, $linktext);

        return $out;
    }

    /**
     * Tag builder &lt;txp:link_to_next&gt; tag.
     */

    function tag_link_to_next()
    {
        $atts = gpsa(array(
            'showalways',
        ));

        extract($atts);

        $linktext = isset($_POST['linktext']) ? ps('linktext') : '<txp:next_title />';

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'link_text'  => $this->tbInput(
                    'linktext',
                    $linktext,
                    INPUT_REGULAR,
                    'link_text'
                ),
                'showalways' => $this->tbYesNoPop('showalways', $showalways),
            )).
            $this->endform
        ).
        $this->build($atts, $linktext);

        return $out;
    }

    /**
     * Tag builder &lt;txp:link_to_prev&gt; tag.
     */

    function tag_link_to_prev()
    {
        $atts = gpsa(array(
            'showalways',
        ));

        extract($atts);

        $linktext = isset($_POST['linktext']) ? ps('linktext') : '<txp:prev_title />';

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'link_text'  => $this->tbInput(
                    'linktext',
                    $linktext,
                    INPUT_REGULAR,
                    'link_text'
                ),
                'showalways' => $this->tbYesNoPop('showalways', $showalways),
            )).
            $this->endform
        ).
        $this->build($atts, $linktext);

        return $out;
    }

    /**
     * Tag builder &lt;txp:linkdesctitle&gt; tag.
     */

    function tag_linkdesctitle()
    {
        $atts = gpsa(array(
            'rel',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
                $this->widget('rel', $this->tbInput('rel', $rel, INPUT_REGULAR)).
                $this->endform
        ).
            $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:linklist&gt; tag.
     *
     * Not adding realname attribute as it's pretty much the same as author.
     */

    function tag_linklist()
    {
        $atts = gpsa(array(
            'author',
            'auto_detect',
            'break',
            'category',
            'class',
            'form',
            'id',
            'label',
            'labeltag',
            'limit',
            'offset',
            'pageby',
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
            'rand()'        => gTxt('random'),
        );

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'author'     => $this->tbAuthorPop($author),
                'category'   => $this->tbCategoryPop($category, 'link'),
                'id'         => $this->tbInput('id', $id),
                'limit'      => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'     => $this->tbInput('offset', $offset, INPUT_TINY),
                'pageby'     => $this->tbInput('pageby', $pageby, INPUT_TINY),
                'sort'       => ' '.selectInput('sort', $sorts, $sort, false, '', 'sort'),
                'label'      => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag'   => $this->tbInput('labeltag', $labeltag),
                'wraptag'    => $this->tbInput('wraptag', $wraptag),
                'class'      => $this->tbInput('class', $class),
                'break'      => $this->tbInput('break', $break),
                'form'       => $this->tbFormPop('form', 'link', $form),
                'match_type' => $this->tbInput('auto_detect', $auto_detect, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:newer&gt; tag.
     */

    function tag_newer()
    {
        $linktext = isset($_POST['linktext']) ? ps('linktext') : '<txp:text item="newer" />';

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget(
                'link_text',
                $this->tbInput(
                    'linktext',
                    $linktext,
                    INPUT_REGULAR,
                    'link_text'
                )
            ).
            $this->endform
        ).
        $this->build(array(), $linktext);

        return $out;
    }

    /**
     * Tag builder &lt;txp:next_title&gt; tag.
     */

    function tag_next_title()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:older&gt; tag.
     */

    function tag_older()
    {
        $linktext = isset($_POST['linktext']) ? ps('linktext') : '<txp:text item="older" />';

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget(
                'link_text',
                $this->tbInput(
                    'linktext',
                    $linktext,
                    INPUT_REGULAR,
                    'link_text'
                )
            ).
            $this->endform
        ).
            $this->build(array(), $linktext);

        return $out;
    }

    /**
     * Tag builder &lt;txp:output_form&gt; tag.
     */

    function tag_output_form()
    {
        $atts = gpsa(array(
            'form',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget(
                'form',
                $this->tbFormPop('form', 'misc', $form)
            ).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:page_title&gt; tag.
     */

    function tag_page_title()
    {
        $atts = gpsa(array(
            'separator',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widget(
                'title_separator',
                $this->tbInput('separator', $separator, INPUT_XSMALL, 'title_separator')
            ).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:password_protect&gt; tag.
     */

    function tag_password_protect()
    {
        $atts = gpsa(array(
            'login',
            'pass',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'login'    => $this->tbInput('login', $login, INPUT_REGULAR),
                'password' => $this->tbInput('pass', $pass, INPUT_REGULAR, 'password'),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:permlink&gt; tag.
     */

    function tag_permlink()
    {
        $atts = gpsa(array(
            'class',
            'id',
            'style',
            'title',
        ));

        extract($atts);

        $linktext = isset($_POST['linktext']) ? ps('linktext') : '<txp:title />';

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'id'           => $this->tbInput('id', $id),
                'link_text'    => $this->tbInput(
                    'linktext',
                    $linktext,
                    INPUT_REGULAR,
                    'link_text'
                ),
                'title'        => $this->tbInput('title', $title, INPUT_REGULAR),
                'class'        => $this->tbInput('class', $class, INPUT_REGULAR),
                'inline_style' => $this->tbInput('style', $style, INPUT_REGULAR, 'inline_style'),
            )).
            $this->endform
        ).
            $this->build($atts, $linktext);

        return $out;
    }

    /**
     * Tag builder &lt;txp:popup&gt; tag.
     */

    function tag_popup()
    {
        $atts = gpsa(array(
            'class',
            'label',
            'section',
            'this_section',
            'type',
            'wraptag',
        ));

        if (!isset($_POST['label'])) {
            $atts['label'] = gTxt('browse');
        }

        extract($atts);

        $types = array(
            'c' => gTxt('category'),
            's' => gTxt('section'),
        );

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'type'         => ' '.selectInput('type', $types, $type, true, '', 'type'),
                'section'      => $this->tbSectionPop('section', $section),
                'this_section' => $this->tbYesNoPop('this_section', $this_section),
                'label'        => $this->tbInput('label', $label, INPUT_REGULAR),
                'wraptag'      => $this->tbInput('wraptag', $wraptag),
                'class'        => $this->tbInput('class', $class, INPUT_REGULAR),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:posted&gt; tag.
     */

    function tag_posted()
    {
        $atts = gpsa(array(
            'format',
            'gmt',
            'lang',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'time_format' => $this->tbInput('format', $format, INPUT_MEDIUM, 'time_format'),
                'gmt'         => $this->tbYesNoPop('gmt', $gmt),
                'locale'      => $this->tbInput('lang', $lang, INPUT_MEDIUM, 'locale'),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:prev_title&gt; tag.
     */

    function tag_prev_title()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:recent_articles&gt; tag.
     */

    function tag_recent_articles()
    {
        $atts = gpsa(array(
            'break',
            'category',
            'class',
            'label',
            'labeltag',
            'limit',
            'offset',
            'section',
            'sort',
            'wraptag',
        ));

        if (!isset($_POST['label'])) {
            $atts['label'] = gTxt('recent_articles');
        }

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'section'  => $this->tbSectionPop('section', $section),
                'category' => $this->tbCategoryPop($category),
                'sort'     => $this->tbSortPop($sort),
                'limit'    => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'   => $this->tbInput('offset', $offset, INPUT_TINY),
                'label'    => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag' => $this->tbInput('labeltag', $labeltag),
                'wraptag'  => $this->tbInput('wraptag', $wraptag),
                'class'    => $this->tbInput('class', $class),
                'break'    => $this->tbInput('break', $break),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:recent_comments&gt; tag.
     */

    function tag_recent_comments()
    {
        $atts = gpsa(array(
            'break',
            'class',
            'form',
            'label',
            'labeltag',
            'limit',
            'offset',
            'sort',
            'wraptag',
        ));

        if (!isset($_POST['label'])) {
            $atts['label'] = gTxt('recent_comments');
        }

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'sort'     => $this->tbDiscussSortPop($sort),
                'limit'    => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'   => $this->tbInput('offset', $offset, INPUT_TINY),
                'label'    => $this->tbInput('label', ($label ? $label : gTxt('recent_comments')), INPUT_REGULAR),
                'labeltag' => $this->tbInput('labeltag', $labeltag),
                'wraptag'  => $this->tbInput('wraptag', $wraptag),
                'class'    => $this->tbInput('class', $class, INPUT_REGULAR),
                'break'    => $this->tbInput('break', $break),
                'form'     => $this->tbFormPop('form', 'comment', $form),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:related_articles&gt; tag.
     */

    function tag_related_articles()
    {
        $atts = gpsa(array(
            'break',
            'class',
            'form',
            'label',
            'labeltag',
            'limit',
            'match',
            'offset',
            'section',
            'sort',
            'wraptag',
        ));

        if (!isset($_POST['label'])) {
            $atts['label'] = gTxt('related_articles');
        }

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'section'    => $this->tbSectionPop('section', $section),
                'match_type' => $this->tbMatchCatPop($match),
                'sort'       => $this->tbSortPop($sort),
                'limit'      => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'     => $this->tbInput('offset', $offset, INPUT_TINY),
                'label'      => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag'   => $this->tbInput('labeltag', $labeltag),
                'wraptag'    => $this->tbInput('wraptag', $wraptag),
                'class'      => $this->tbInput('class', $class, INPUT_REGULAR),
                'break'      => $this->tbInput('break', $break),
                'form'       => $this->tbFormPop('form', 'article', $form),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:search_input&gt; tag.
     */

    function tag_search_input()
    {
        $atts = gpsa(array(
            'button',
            'class',
            'form',
            'html_id',
            'label',
            'match',
            'section',
            'size',
            'wraptag',
        ));

        if (!isset($_POST['label'])) {
            $atts['label'] = gTxt('search');
        }

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'match_type'  => $this->tbPatternPop($match),
                'section'     => $this->tbSectionPop('section', $section),
                'button_text' => $this->tbInput('button', $button, INPUT_REGULAR, 'button_text'),
                'input_size'  => $this->tbInput('size', $size, INPUT_TINY, 'input_size'),
                'html_id'     => $this->tbInput('html_id', $html_id, INPUT_REGULAR),
                'label'       => $this->tbInput('label', $label, INPUT_REGULAR),
                'wraptag'     => $this->tbInput('wraptag', $wraptag),
                'class'       => $this->tbInput('class', $class, INPUT_REGULAR),
                'form'        => $this->tbFormPop('form', 'misc', $form),
            )).
             $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:search_result_date&gt; tag.
     */

    function tag_search_result_date()
    {
        $atts = gpsa(array(
            'format',
            'gmt',
            'lang',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'time_format' => $this->tbInput('format', $format, INPUT_MEDIUM, 'time_format'),
                'gmt'         => $this->tbYesNoPop('gmt', $gmt),
                'locale'      => $this->tbInput('lang', $lang, INPUT_MEDIUM, 'locale'),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:search_result_excerpt&gt; tag.
     */

    function tag_search_result_excerpt()
    {
        $atts = gpsa(array(
            'separator',
            'hilight',
            'limit',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'hilight'       => $this->tbInput('hilight', $hilight),
                'hilight_limit' => $this->tbInput('limit', $limit, INPUT_TINY, 'hilight_limit'),
                'separator'     => $this->tbInput('separator', $separator),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:search_result_title&gt; tag.
     */

    function tag_search_result_title()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:search_result_url&gt; tag.
     */

    function tag_search_result_url()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:section&gt; tag.
     */

    function tag_section()
    {
        $atts = gpsa(array(
            'class',
            'link',
            'name',
            'title',
            'url',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'name'                 => $this->tbSectionPop('name', $name),
                'link_to_this_section' => $this->tbYesNoPop('link', $link),
                'url'                  => $this->tbYesNoPop('url', $url),
                'wraptag'              => $this->tbInput('wraptag', $wraptag),
                'class'                => $this->tbInput('class', $class, INPUT_REGULAR),
                'title'                => $this->tbYesNoPop('title', $title),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:section_list&gt; tag.
     */

    function tag_section_list()
    {
        $atts = gpsa(array(
            'active_class',
            'break',
            'class',
            'default_title',
            'exclude',
            'form',
            'html_id',
            'include_default',
            'label',
            'labeltag',
            'limit',
            'offset',
            'sections',
            'sort',
            'wraptag',
        ));

        extract($atts);

        $out = $this->tagbuildForm(
            $this->startblock.
            $this->widgets(array(
                'include_default' => $this->tbYesNoPop('include_default', $include_default),
                'sort'            => $this->tbListSortPop($sort),
                'default_title'   => $this->tbInput('default_title', $default_title, INPUT_REGULAR),
                'sections'        => $this->tbInput('sections', $sections, INPUT_REGULAR),
                'exclude'         => $this->tbInput('exclude', $exclude, INPUT_REGULAR),
                'html_id'         => $this->tbInput('html_id', $html_id, INPUT_REGULAR),
                'limit'           => $this->tbInput('limit', $limit, INPUT_TINY),
                'offset'          => $this->tbInput('offset', $offset, INPUT_TINY),
                'label'           => $this->tbInput('label', $label, INPUT_REGULAR),
                'labeltag'        => $this->tbInput('labeltag', $labeltag),
                'wraptag'         => $this->tbInput('wraptag', $wraptag),
                'class'           => $this->tbInput('class', $class, INPUT_REGULAR),
                'active_class'    => $this->tbInput('active_class', $active_class, INPUT_REGULAR),
                'break'           => $this->tbInput('break', $break),
                'form'            => $this->tbFormPop('form', 'misc', $form),
            )).
            $this->endform
        ).
        $this->build($atts);

        return $out;
    }

    /**
     * Tag builder &lt;txp:site_name&gt; tag.
     */

    function tag_site_name()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:site_slogan&gt; tag.
     */

    function tag_site_slogan()
    {
        return $this->tbNoAtts();
    }

    /**
     * Tag builder &lt;txp:title&gt; tag.
     */

    function tag_title()
    {
        return $this->tbNoAtts();
    }
}

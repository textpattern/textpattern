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
 * Collection of HTML widgets.
 *
 * @package HTML
 */

/**
 * Renders the admin-side footer.
 *
 * The footer's default markup is provided by a theme. It can be further
 * customised via the "admin_side > footer" pluggable UI callback event.
 *
 * In addition to the pluggable UI, this function also calls callback events
 * "admin_side > main_content_end" and "admin_side > body_end".
 */

function end_page()
{
    global $event, $app_mode, $theme, $textarray_script;

    if ($app_mode != 'async' && $event != 'tag') {
        callback_event('admin_side', 'main_content_end');
        echo n.'</main><!-- /txp-body -->'.n.'<footer class="txp-footer">';
        echo pluggable_ui('admin_side', 'footer', $theme->footer());
        callback_event('admin_side', 'body_end');
        echo script_js('vendors/PrismJS/prism/prism.js', TEXTPATTERN_SCRIPT_URL).
            script_js('textpattern.textarray = '.json_encode($textarray_script, TEXTPATTERN_JSON), true).
            n.'</footer><!-- /txp-footer -->'.n.'</body>'.n.'</html>';
    }
}

/**
 * Renders the user interface for one head cell of columnar data.
 *
 * @param  string $value   Element text
 * @param  string $sort    Sort criterion
 * @param  string $event   Event name
 * @param  bool   $is_link Include link to admin action in user interface according to the other params
 * @param  string $dir     Sort direction, either "asc" or "desc"
 * @param  string $crit    Search criterion
 * @param  string $method  Search method
 * @param  string $class   HTML "class" attribute applied to the resulting element
 * @param  string $step    Step name
 * @return string HTML
 */

function column_head($value, $sort = '', $event = '', $is_link = '', $dir = '', $crit = '', $method = '', $class = '', $step = 'list')
{
    if (is_array($value)) {
        extract($value);
    }

    $options = (isset($options) ? (array) $options : array()) + array(
        'class'    => $class,
        'data-col' => $sort,
    );

    $head_items = array(
        'value'   => $value,
        'sort'    => $sort,
        'event'   => $event,
        'step'    => $step,
        'is_link' => $is_link,
        'dir'     => $dir,
        'crit'    => $crit,
        'method'  => $method,
    );

    return column_multi_head(array($head_items), $options);
}

/**
 * Renders the user interface for multiple head cells of columnar data.
 *
 * @param  array  $head_items An array of hashed elements. Valid keys: 'value', 'sort', 'event', 'is_link', 'dir', 'crit', 'method'
 * @param  string $class      HTML "class" attribute applied to the resulting element
 * @return string HTML
 */

function column_multi_head($head_items, $class = '')
{
    $o = '';
    $first_item = true;

    foreach ($head_items as $item) {
        if (empty($item)) {
            continue;
        }

        extract(lAtts(array(
            'value'   => '',
            'sort'    => '',
            'event'   => '',
            'step'    => 'list',
            'is_link' => '',
            'dir'     => '',
            'crit'    => '',
            'method'  => '',
        ), $item));

        $o .= ($first_item) ? '' : ', ';
        $first_item = false;

        if ($is_link) {
            $o .= href(gTxt($value), array(
                'event'         => $event,
                'step'          => $step,
                'sort'          => $sort,
                'dir'           => $dir,
                'crit'          => $crit,
                'search_method' => $method,
            ), array());
        } else {
            $o .= gTxt($value);
        }
    }

    $extra_atts = is_array($class) ? $class : array('class' => $class);

    return hCell($o, '', $extra_atts + array('scope' => 'col'));
}

/**
 * Renders a &lt;th&gt; element.
 *
 * @param  string       $text    Cell text
 * @param  string       $caption Is not used
 * @param  string|array $atts    HTML attributes
 * @return string HTML
 */

function hCell($text = '', $caption = '', $atts = '')
{
    $text = ('' === $text) ? sp : $text;

    return n.tag($text, 'th', $atts);
}

/**
 * Renders a link invoking an admin-side action.
 *
 * @param  string $event    Event
 * @param  string $step     Step
 * @param  string $linktext Link text
 * @param  string $class    HTML class attribute for link
 * @return string HTML
 */

function sLink($event, $step, $linktext, $class = '')
{
    if ($linktext === '') {
        $linktext = null;
    }

    return href($linktext, array(
        'event' => $event,
        'step'  => $step,
    ), array('class' => $class));
}

/**
 * Renders a link with two additional URL parameters.
 *
 * Renders a link invoking an admin-side action while taking up to two
 * additional URL parameters.
 *
 * @param  string $event    Event
 * @param  string $step     Step
 * @param  string $thing    URL parameter key #1
 * @param  string $value    URL parameter value #1
 * @param  string $linktext Link text
 * @param  string $thing2   URL parameter key #2
 * @param  string $val2     URL parameter value #2
 * @param  string $title    Anchor title
 * @param  string $class    HTML class attribute
 * @return string HTML
 */

function eLink($event, $step, $thing, $value, $linktext, $thing2 = '', $val2 = '', $title = '', $class = '')
{
    if ($title) {
        $title = gTxt($title);
    }

    if ($linktext === '') {
        $linktext = null;
    } else {
        $linktext = escape_title($linktext);
    }

    return href($linktext, array(
        'event'      => $event,
        'step'       => $step,
        $thing       => $value,
        $thing2      => $val2,
        '_txp_token' => form_token(),
    ), array(
        'class'      => $class,
        'title'      => $title,
        'aria-label' => $title,
    ));
}

/**
 * Renders a link with one additional URL parameter.
 *
 * Renders an link invoking an admin-side action while taking up to one
 * additional URL parameter.
 *
 * @param  string $event Event
 * @param  string $step  Step
 * @param  string $thing URL parameter key
 * @param  string $value URL parameter value
 * @param  string $class HTML class attribute
 * @return string HTML
 */

function wLink($event, $step = '', $thing = '', $value = '', $class = '')
{
    return href(sp.'!'.sp, array(
        'event'      => $event,
        'step'       => $step,
        $thing       => $value,
        '_txp_token' => form_token(),
    ), array('class' => $class));
}

/**
 * Renders a delete link.
 *
 * Renders a link invoking an admin-side "delete" action while taking up to two
 * additional URL parameters.
 *
 * @param  string $event     Event
 * @param  string $step      Step
 * @param  string $thing     URL parameter key #1
 * @param  string $value     URL parameter value #1
 * @param  string $verify    Show an "Are you sure?" dialogue with this text
 * @param  string $thing2    URL parameter key #2
 * @param  string $thing2val URL parameter value #2
 * @param  bool   $get       If TRUE, uses GET request
 * @param  array  $remember  Convey URL parameters for page state. Member sequence is $page, $sort, $dir, $crit, $search_method
 * @return string HTML
 */

function dLink($event, $step, $thing, $value, $verify = '', $thing2 = '', $thing2val = '', $get = '', $remember = null)
{
    if ($remember) {
        list($page, $sort, $dir, $crit, $search_method) = $remember;
    }

    if ($get) {
        if ($verify) {
            $verify = gTxt($verify);
        } else {
            $verify = gTxt('confirm_delete_popup');
        }

        if ($remember) {
            return href(gTxt('delete'), array(
                'event'         => $event,
                'step'          => $step,
                $thing          => $value,
                $thing2         => $thing2val,
                '_txp_token'    => form_token(),
                'page'          => $page,
                'sort'          => $sort,
                'dir'           => $dir,
                'crit'          => $crit,
                'search_method' => $search_method,
            ), array(
                'class'       => 'destroy ui-icon ui-icon-close',
                'title'       => gTxt('delete'),
                'data-verify' => $verify,
            ));
        }

        return href(gTxt('delete'), array(
            'event'      => $event,
            'step'       => $step,
            $thing       => $value,
            $thing2      => $thing2val,
            '_txp_token' => form_token(),
        ), array(
            'class'       => 'destroy ui-icon ui-icon-close',
            'title'       => gTxt('delete'),
            'data-verify' => $verify,
        ));
    }

    return join('', array(
        n.'<form method="post" action="index.php" data-verify="'.gTxt('confirm_delete_popup').'">',
        tag(
            span(gTxt('delete'), array('class' => 'ui-icon ui-icon-close')),
            'button',
            array(
                'class'      => 'destroy',
                'type'       => 'submit',
                'title'      => gTxt('delete'),
                'aria-label' => gTxt('delete'),
            )
        ),
        eInput($event).
        sInput($step),
        hInput($thing, $value),
        ($thing2) ? hInput($thing2, $thing2val) : '',
        ($remember) ? hInput(compact('page', 'sort', 'dir', 'crit', 'search_method')) : '',
        tInput(),
        n.'</form>',
    ));
}

/**
 * Renders an add link.
 *
 * This function can be used for invoking an admin-side "add" action while
 * taking up to two additional URL parameters.
 *
 * @param  string $event  Event
 * @param  string $step   Step
 * @param  string $thing  URL parameter key #1
 * @param  string $value  URL parameter value #1
 * @param  string $thing2 URL parameter key #2
 * @param  string $value2 URL parameter value #2
 * @return string HTML
 */

function aLink($event, $step, $thing = '', $value = '', $thing2 = '', $value2 = '')
{
    return href('+', array(
        'event'      => $event,
        'step'       => $step,
        $thing       => $value,
        $thing2      => $value2,
        '_txp_token' => form_token(),
    ), array('class' => 'alink'));
}

/**
 * Renders a link invoking an admin-side "previous/next article" action.
 *
 * @param  string $name  Link text
 * @param  string $event Event
 * @param  string $step  Step
 * @param  int    $id    ID of target Textpattern object (article,...)
 * @param  string $title HTML title attribute
 * @param  string $rel   HTML rel attribute
 * @return string HTML
 */

function prevnext_link($name, $event, $step, $id, $title = '', $rel = '')
{
    return href($name, array(
        'event' => $event,
        'step'  => $step,
        'ID'    => $id,
    ), array(
        'class' => 'navlink',
        'title' => $title,
        'rel'   => $rel,
    ));
}

/**
 * Renders a link invoking an admin-side "previous/next page" action.
 *
 * @param  string $event         Event
 * @param  int    $page          Target page number
 * @param  string $label         Link text
 * @param  string $type          Direction, either "prev" or "next"
 * @param  string $sort          Sort field
 * @param  string $dir           Sort direction, either "asc" or "desc"
 * @param  string $crit          Search criterion
 * @param  string $search_method Search method
 * @param  string $step          Step
 * @return string HTML
 */

function PrevNextLink($event, $page, $label, $type, $sort = '', $dir = '', $crit = '', $search_method = '', $step = 'list')
{
    $theClass = ($type === 'next') ? 'ui-icon-arrowthick-1-e' : 'ui-icon-arrowthick-1-w';

    return href(
        span(
            $label,
            array('class' => 'ui-icon '.$theClass)
        ),
        array(
            'event'         => $event,
            'step'          => $step,
            'page'          => (int) $page,
            'dir'           => $dir,
            'crit'          => $crit,
            'search_method' => $search_method,
        ),
        array(
            'rel'        => $type,
            'title'      => $label,
            'aria-label' => $label,
        )
    );
}

/**
 * Renders a page navigation form.
 *
 * @param  string $event         Event
 * @param  int    $page          Current page number
 * @param  int    $numPages         Total pages
 * @param  string $sort          Sort criterion
 * @param  string $dir           Sort direction, either "asc" or "desc"
 * @param  string $crit          Search criterion
 * @param  string $search_method Search method
 * @param  int    $total         Total search term hit count [0]
 * @param  int    $limit         First visible search term hit number [0]
 * @param  string $step             Step
 * @param  int    $list          Number of displayed page links discounting jump links, previous and next
 * @return string HTML
 */

function nav_form($event, $page, $numPages, $sort = '', $dir = '', $crit = '', $search_method = '', $total = 0, $limit = 0, $step = 'list', $list = 5)
{
    $out = array();

    if ($numPages > 1 && $crit !== '') {
        $out[] = announce(
            gTxt('showing_search_results', array(
                '{from}'  => (($page - 1) * $limit) + 1,
                '{to}'    => min($total, $page * $limit),
                '{total}' => $total,
            )),
            TEXTPATTERN_ANNOUNCE_REGULAR
        );
    }

    $nav = array();
    $list--;
    $page = max(min($page, $numPages), 1);

    $parameters = array(
        'event'         => $event,
        'step'          => $step,
        'dir'           => $dir,
        'crit'          => $crit,
        'search_method' => $search_method,
    );

    // Previous page.
    if ($page > 1) {
        $nav[] = n.PrevNextLink($event, $page - 1, gTxt('prev'), 'prev', $sort, $dir, $crit, $search_method, $step);
    } else {
        $nav[] = n.span(
            span(gTxt('prev'), array('class' => 'ui-icon ui-icon-arrowthick-1-w')),
            array(
                'class'         => 'disabled',
                'aria-disabled' => 'true',
                'aria-label'    => gTxt('prev'),
            )
        );
    }


    $nav[] = form(
        n.tag(gTxt('page'), 'label', array('for' => 'current-page')).
        n.tag_void('input', array(
            'class'     => 'current-page',
            'id'        => 'current-page',
            'name'      => 'page',
            'type'      => 'text',
            'size'      => INPUT_XSMALL,
            'inputmode' => 'numeric',
            'pattern'   => '[0-9]+',
            'value'     => $page,
        )).
        n.gTxt('of').
        n.span($numPages, array('class' => 'total-pages')).
        eInput($event).
        hInput(compact('sort', 'dir', 'crit', 'search_method')),
        '',
        '',
        'get'
    );

    // Next page.
    if ($page < $numPages) {
        $nav[] = n.PrevNextLink($event, $page + 1, gTxt('next'), 'next', $sort, $dir, $crit, $search_method, $step);
    } else {
        $nav[] = n.span(
            span(gTxt('next'), array('class' => 'ui-icon ui-icon-arrowthick-1-e')),
            array(
                'class'         => 'disabled',
                'aria-disabled' => 'true',
                'aria-label'    => gTxt('next'),
            )
        );
    }

    $out[] = n.tag(join($nav).n, 'nav', array(
        'class'      => 'prev-next',
        'aria-label' => gTxt('page_nav'),
        'style'      => ($numPages > 1 ? false : 'display:none'),
    ));

    return join('', $out);
}

/**
 * Wraps a collapsible region and group structure around content.
 *
 * @param  string $id        HTML id attribute for the region wrapper and ARIA label
 * @param  string $content   Content to wrap. If empty, only the outer wrapper will be rendered
 * @param  string $anchor_id HTML id attribute for the collapsible wrapper
 * @param  string $label     L10n label name
 * @param  string $pane      Pane reference for maintaining toggle state in prefs. Prefixed with 'pane_', suffixed with '_visible'
 * @param  string $class     CSS class name to apply to wrapper
 * @param  string $help      Help text item
 * @return string HTML
 * @since  4.6.0
 */

function wrapRegion($id, $content = '', $anchor_id = '', $label = '', $pane = '', $class = '', $help = '', $visible = null)
{
    global $event;
    $label = $label ? gTxt($label) : null;

    if ($anchor_id && $pane) {
        $heading_class = 'txp-summary'.($visible ? ' expanded' : '');
        $display_state = array(
            'class' => 'toggle',
            'id'    => $anchor_id,
            'role'  => 'group',
            'style' => $visible ? '' : 'display:none',
        );

        $label = href($label, '#'.$anchor_id, array(
            'role'           => 'button',
            'data-txp-token' => md5($pane.$event.form_token().get_pref('blog_uid')),
            'data-txp-pane'  => $pane,
        ));

        $help = '';
    } else {
        $heading_class = '';
        $display_state = array('role' => 'group');
    }

    if ($content) {
        $content =
            hed($label.popHelp($help), 3, array(
                'class' => $heading_class,
                'id'    => $id.'-label',
            )).
            n.tag($content.n, 'div', $display_state).n;
    }

    return n.tag($content, 'section', array(
        'class'           => trim('txp-details '.$class),
        'id'              => $id,
        'aria-labelledby' => $content ? $id.'-label' : '',
    ));
}

/**
 * Wraps a region and group structure around content.
 *
 * @param  string $name    HTML id attribute for the group wrapper and ARIA label
 * @param  string $content Content to wrap
 * @param  string $label   L10n label name
 * @param  string $class   CSS class name to apply to wrapper
 * @param  string $help    Help text item
 * @return string HTML
 * @see    wrapRegion()
 * @since  4.6.0
 */

function wrapGroup($id, $content, $label, $class = '', $help = '')
{
    return wrapRegion($id, $content, '', $label, '', $class, $help);
}

/**
 * Renders start of a layout &lt;table&gt; element.
 *
 * @param  string $id    HTML id attribute
 * @param  string $align HTML align attribute
 * @param  string $class HTML class attribute
 * @param  int    $p     HTML cellpadding attribute
 * @param  int    $w     HTML width attribute
 * @return string HTML
 * @example
 * startTable().
 * tr(td('column') . td('column')).
 * tr(td('column') . td('column')).
 * endTable();
 */

function startTable($id = '', $align = '', $class = '', $p = 0, $w = 0)
{
    $atts = join_atts(array(
        'class'       => $class,
        'id'          => $id,
        'cellpadding' => (int) $p,
        'width'       => (int) $w,
        'align'       => $align,
    ), TEXTPATTERN_STRIP_EMPTY);

    return n.'<table'.$atts.'>';
}

/**
 * Renders closing &lt;/table&gt; tag.
 *
 * @return string HTML
 */

function endTable()
{
    return n.'</table>';
}

/**
 * Renders &lt;tr&gt; elements from input parameters.
 *
 * Takes a list of arguments containing each making a row.
 *
 * @return string HTML
 * @example
 * stackRows(
 *     td('cell') . td('cell'),
 *  td('cell') . td('cell')
 * );
 */

function stackRows()
{
    foreach (func_get_args() as $a) {
        $o[] = tr($a);
    }

    return join('', $o);
}

/**
 * Renders a &lt;td&gt; element.
 *
 * @param  string $content Cell content
 * @param  int    $width   HTML width attribute
 * @param  string $class   HTML class attribute
 * @param  string $id      HTML id attribute
 * @return string HTML
 */

function td($content = '', $width = null, $class = '', $id = '')
{
    $opts = array(
        'class' => $class,
        'id'    => $id,
    );

    if (is_numeric($width)) {
        $opts['width'] = (int) $width;
    } elseif (is_array($width)) {
        $opts = array_merge($opts, $width);
    }

    return tda($content, $opts);
}

/**
 * Renders a &lt;td&gt; element with attributes.
 *
 * @param  string       $content Cell content
 * @param  string|array $atts    Cell attributes
 * @return string HTML
 */

function tda($content, $atts = '')
{
    $content = ($content === '') ? sp : $content;

    return n.tag($content, 'td', $atts);
}

/**
 * Renders a &lt;td&gt; element with attributes.
 *
 * This function is identical to tda().
 *
 * @param  string       $content Cell content
 * @param  string|array $atts    Cell attributes
 * @return string HTML
 * @access private
 * @see    tda()
 */

function tdtl($content, $atts = '')
{
    return tda($content, $atts);
}

/**
 * Renders a &lt;tr&gt; element with attributes.
 *
 * @param  string       $content Row content
 * @param  string|array $atts    Row attributes
 * @return string HTML
 */

function tr($content, $atts = '')
{
    return n.tag($content, 'tr', $atts);
}

/**
 * Renders a &lt;td&gt; element with top/left text orientation, colspan and
 * other attributes.
 *
 * @param  string $content Cell content
 * @param  int    $span    Cell colspan attribute
 * @param  int    $width   Cell width attribute
 * @param  string $class   Cell class attribute
 * @return string HTML
 */

function tdcs($content, $span, $width = null, $class = '')
{
    $opts = array(
        'class'   => $class,
        'colspan' => (int) $span,
    );

    if (is_numeric($width)) {
        $opts['width'] = (int) $width;
    }

    return tda($content, $opts);
}

/**
 * Renders a &lt;td&gt; element with a rowspan attribute.
 *
 * @param  string $content Cell content
 * @param  int    $span    Cell rowspan attribute
 * @param  int    $width   Cell width attribute
 * @param  string $class   Cell class attribute
 * @return string HTML
 */

function tdrs($content, $span, $width = null, $class = '')
{
    $opts = array(
        'class'   => $class,
        'rowspan' => (int) $span,
    );

    if (is_numeric($width)) {
        $opts['width'] = (int) $width;
    }

    return tda($content, $opts);
}

/**
 * Renders a form label inside a table cell.
 *
 * @param  string $text     Label text
 * @param  string $help     Help text
 * @param  string $label_id HTML "for" attribute, i.e. id of corresponding form element
 * @return string HTML
 */

function fLabelCell($text, $help = '', $label_id = '')
{
    $cell = gTxt($text).' '.popHelp($help);

    if ($label_id) {
        $cell = tag($cell, 'label', array('for' => $label_id));
    }

    return tda($cell, array('class' => 'cell-label'));
}

/**
 * Renders a form input inside a table cell.
 *
 * @param  string $name     HTML name attribute
 * @param  string $var      Input value
 * @param  int    $tabindex HTML tabindex attribute
 * @param  int    $size     HTML size attribute
 * @param  bool   $help     TRUE to display help link
 * @param  string $id       HTML id attribute
 * @return string HTML
 */

function fInputCell($name, $var = '', $tabindex = 0, $size = 0, $help = false, $id = '')
{
    $pop = ($help) ? popHelp($name) : '';

    return tda(fInput('text', $name, $var, '', '', '', $size, $tabindex, $id).$pop);
}

/**
 * Renders a name-value input control with label.
 *
 * The rendered input can be customised via the
 * '{$event}_ui > inputlabel.{$name}' pluggable UI callback event.
 *
 * @param  string       $name        Input name
 * @param  string       $input       Complete input control widget
 * @param  string|array $label       Label text | array (label text, HTML block to append to label)
 * @param  string|array $help        Help text item | array(help text item, inline help text)
 * @param  string|array $atts        Class name | attribute pairs to assign to container div
 * @param  string|array $wraptag_val Tag to wrap the value / label in, or empty to omit
 * @return string HTML
 * @example
 * echo inputLabel('active', yesnoRadio('active'), 'Keep active?');
 */

function inputLabel($name, $input, $label = '', $help = array(), $atts = array(), $wraptag_val = array('div', 'div'))
{
    global $event;

    $arguments = compact('name', 'input', 'label', 'help', 'atts', 'wraptag_val');

    $fallback_class = 'txp-form-field edit-'.str_replace('_', '-', $name);
    $tools = '';

    if ($atts && is_string($atts)) {
        $atts = array('class' => $atts);
    } elseif (!$atts) {
        $atts = array('class' => $fallback_class);
    } elseif (is_array($atts) && !array_key_exists('class', $atts)) {
        $atts['class'] = $fallback_class;
    }

    if (!is_array($help)) {
        $help = array($help);
    }

    if (is_array($label)) {
        if (isset($label[1])) {
            $tools = (string) $label[1];
        }

        $label = (string) $label[0];
    }

    if (empty($help)) {
        $help = array(
            0 => '',
            1 => '',
        );
    }

    $inlineHelp = (isset($help[1])) ? $help[1] : '';

    if ($label !== '') {
        $labelContent = tag(gTxt($label).popHelp($help[0]), 'label', array('for' => $name)).$tools;
    } else {
        $labelContent = gTxt($name).popHelp($help[0]).$tools;
    }

    if (!is_array($wraptag_val)) {
        $wraptag_val = array($wraptag_val, $wraptag_val);
    }

    if ($wraptag_val[0]) {
        $input = n.tag($input, $wraptag_val[0], array('class' => 'txp-form-field-value'));
    }

    if (isset($wraptag_val[1]) && $wraptag_val[1]) {
        $labeltag = n.tag($labelContent, $wraptag_val[1], array('class' => 'txp-form-field-label'));
    } else {
        $labeltag = $labelContent;
    }

    $out = n.tag(
        $labeltag.
        fieldHelp($inlineHelp).
        $input.n,
        'div',
        $atts
    );

    return pluggable_ui($event.'_ui', 'inputlabel.'.$name, $out, $arguments);
}

/**
 * Renders anything as an XML element.
 *
 * @param  string       $content Enclosed content
 * @param  string       $tag     The tag without brackets
 * @param  string|array $atts    The element's HTML attributes
 * @return string HTML
 * @example
 * echo tag('Link text', 'a', array('href' => '#', 'class' => 'warning'));
 */

function tag($content, $tag, $atts = '')
{
    static $tags = array();

    if (empty($tag) || $content === '') {
        return $content;
    }

    if (!isset($tags[$tag])) {
        $tags[$tag] = preg_match('/^\w[\w\-\.\:]*$/', $tag) ? 1 :
            (strpos($tag, '<+>') === false ? 2 : 3);
    }

    switch ($tags[$tag]) {
        case 1:
            $atts = $atts ? join_atts($atts) : '';
            return '<'.$tag.$atts.'>'.$content.'</'.$tag.'>';
        case 2:
            return $tag.$content.$tag;
        default:
            return str_replace('<+>', $content, $tag);
    }
}

/**
 * Renders anything as a HTML void element.
 *
 * @param  string $tag  The tag without brackets
 * @param  string|array $atts HTML attributes
 * @return string HTML
 * @since  4.6.0
 * @example
 * echo tag_void('input', array('name' => 'name', 'type' => 'text'));
 */

function tag_void($tag, $atts = '')
{
    return '<'.$tag.join_atts($atts).' />';
}

/**
 * Renders anything as a HTML start tag.
 *
 * @param  string $tag The tag without brackets
 * @param  string|array $atts HTML attributes
 * @return string A HTML start tag
 * @since  4.6.0
 * @example
 * echo tag_start('section', array('class' => 'myClass'));
 */

function tag_start($tag, $atts = '')
{
    return '<'.$tag.join_atts($atts).'>';
}

/**
 * Renders anything as a HTML end tag.
 *
 * @param  string $tag The tag without brackets
 * @return string A HTML end tag
 * @since  4.6.0
 * @example
 * echo tag_end('section');
 */

function tag_end($tag)
{
    return '</'.$tag.'>';
}

/**
 * Renders a &lt;p&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string HTML
 * @example
 * echo graf('This a paragraph.');
 */

function graf($item, $atts = '')
{
    return n.tag($item, 'p', $atts);
}

/**
 * Renders a &lt;hx&gt; element.
 *
 * @param  string       $item  The Enclosed content
 * @param  int          $level Heading level 1...6
 * @param  string|array $atts  HTML attributes
 * @return string HTML
 * @example
 * echo hed('Heading', 2);
 */

function hed($item, $level, $atts = '')
{
    return n.tag($item, 'h'.$level, $atts).n;
}

/**
 * Renders an &lt;a&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $href The link target
 * @param  string|array $atts HTML attributes
 * @return string HTML
 */

function href($item, $href, $atts = '')
{
    if (is_array($atts)) {
        $atts['href'] = $href;
    } else {
        if (is_array($href)) {
            $href = join_qs($href);
        }

        $atts .= ' href="'.$href.'"';
    }

    return tag($item, 'a', $atts);
}

/**
 * Renders a &lt;strong&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string HTML
 */

function strong($item, $atts = '')
{
    return tag($item, 'strong', $atts);
}

/**
 * Renders a &lt;span&gt; element.
 *
 * @param  string       $item Enclosed content
 * @param  string|array $atts HTML attributes
 * @return string HTML
 */

function span($item, $atts = '')
{
    return tag($item, 'span', $atts);
}

/**
 * Renders a &lt;pre&gt; element.
 *
 * @param  string       $item The input string
 * @param  string|array $atts HTML attributes
 * @return string HTML
 * @example
 * echo htmlPre('&lt;?php echo "Hello World"; ?&gt;');
 */

function htmlPre($item, $atts = '')
{
    if (($item = tag($item, 'code')) === '') {
        $item = null;
    }

    return tag($item, 'pre', $atts);
}

/**
 * Renders a HTML comment (&lt;!-- --&gt;) element.
 *
 * @param  string $item The input string
 * @return string HTML
 * @example
 * echo comment('Some HTML comment.');
 */

function comment($item)
{
    return '<!-- '.str_replace('--', '- - ', $item).' -->';
}

/**
 * Renders a &lt;small&gt element.
 *
 * @param  string       $item The input string
 * @param  string|array $atts HTML attributes
 * @return string HTML
 */

function small($item, $atts = '')
{
    return tag($item, 'small', $atts);
}

/**
 * Renders a table data row from an array of content => width pairs.
 *
 * @param  array        $array Array of content => width pairs
 * @param  string|array $atts  Table row attributes
 * @return string A HTML table row
 */

function assRow($array, $atts = '')
{
    $out = array();

    foreach ($array as $value => $width) {
        $out[] = tda($value, array('width' => (int) $width));
    }

    return tr(join('', $out), $atts);
}

/**
 * Renders a table head row from an array of strings.
 *
 * Takes an argument list of head text strings. i18n is applied to the strings.
 *
 * @return string HTML
 */

function assHead()
{
    $array = func_get_args();
    $o = array();

    foreach ($array as $a) {
        $o[] = hCell(gTxt($a), '', ' scope="col"');
    }

    return tr(join('', $o));
}

/**
 * Renders the ubiquitous popup help button.
 *
 * The rendered link can be customised via a 'admin_help > {$help_var}'
 * pluggable UI callback event.
 *
 * @param  string|array $help_var Help topic or topic and lang in an array
 * @param  int          $width    Popup window width
 * @param  int          $height   Popup window height
 * @param  string       $class    HTML class
 * @param  string       $inline   Inline pophelp
 * @return string       HTML
 */

function popHelp($help_var, $width = 0, $height = 0, $class = 'pophelp', $inline = '')
{
    global $txp_user, $prefs;

    $lang = null;

    if (empty($help_var) || empty($prefs['module_pophelp'])) {
        return '';
    }

    if (is_array($help_var)) {
        $lang = empty($help_var[1]) ? null : $help_var[1];
        $help_var = $help_var[0];
    }

    $url = filter_var($help_var, FILTER_VALIDATE_URL);

    $atts = array(
        'rel'    => 'help',
        'title'  => gTxt('help'),
        'role'   => 'button',
    );

    if ($url === false) {
        $atts['class'] = $class;
        $url = '#';
        if (! empty($inline)) {
            $atts['data-item'] = $inline;
        } elseif (empty($txp_user)) {
            // Use inline pophelp, if unauthorized user or setup stage
            if (class_exists('\Textpattern\Module\Help\HelpAdmin')) {
                $atts['data-item'] = \Txp::get('\Textpattern\Module\Help\HelpAdmin')->pophelp($help_var, $lang);
            }
        } else {
            $url = '?event=help&step=pophelp&item='.urlencode($help_var);
        }
        $ui = sp.href(span(gTxt('help'), array('class' => 'ui-icon ui-icon-help')), $url, $atts);
    } else {
        $atts['target'] = '_blank';
        $ui = sp.href(span(gTxt('help'), array('class' => 'ui-icon ui-icon-extlink')), $url, $atts);
    }

    return pluggable_ui('admin_help', $help_var, $ui, compact('help_var', 'width', 'height', 'class', 'inline'));
}

/**
 * Renders inline help text.
 *
 * The help topic is the name of a string that can be found in txp_lang.
 *
 * The rendered link can be customised via a 'admin_help_field > {$help_var}'
 * pluggable UI callback event.
 *
 * @param  string $help_var   Help topic
 * @return string HTML
 */

function fieldHelp($help_var)
{
    if (!$help_var) {
        return '';
    }

    $help_text = gTxt($help_var);

    // If rendered string is the same as the input string, either the l10n
    // doesn't exist or the string is missing from txp_lang.
    // Either way, no instruction text, no render.
    if ($help_var === $help_text) {
        return '';
    }

    $ui = n.tag($help_text, 'div', array('class' => 'txp-form-field-instructions'));

    return pluggable_ui('admin_help_field', $help_var, $ui, compact('help_var'));
}

/**
 * Renders the ubiquitous popup help button with a little less visual noise.
 *
 * The rendered link can be customised via a 'admin_help > {$help_var}'
 * pluggable UI callback event.
 *
 * @param  string $help_var Help topic
 * @param  int    $width    Popup window width
 * @param  int    $height   Popup window height
 * @return string HTML
 */

function popHelpSubtle($help_var, $width = 0, $height = 0)
{
    return popHelp($help_var, $width, $height, 'pophelpsubtle');
}

/**
 * Renders a link that opens a popup tag area.
 *
 * @param  string $var   Tag name
 * @param  string $text  Link text
 * @param  array  $atts  Attributes to add to the link
 * @return string HTML
 */

function popTag($var, $text, $atts = array())
{
    $opts = array(
        'event'    => 'tag',
        'tag_name' => $var,
    ) + $atts;

    return href($text, $opts, array(
        'class'      => 'txp-tagbuilder-link',
        'title'      => gTxt('tagbuilder'),
        'aria-label' => gTxt('tagbuilder'),
    ));
}

/**
 * Renders an admin-side message text.
 *
 * @param  string $thing    Subject
 * @param  string $thething Predicate (strong)
 * @param  string $action   Object
 * @return string HTML
 * @deprecated in 4.7.0
 */

function messenger($thing, $thething = '', $action = '')
{
    return gTxt($thing).' '.strong($thething).' '.gTxt($action);
}

/**
 * Renders a multi-edit form listing editing methods.
 *
 * @param  array   $options       array('value' => array( 'label' => '', 'html' => '' ),...)
 * @param  string  $event         Event
 * @param  string  $step          Step
 * @param  int     $page          Page number
 * @param  string  $sort          Column sorted by
 * @param  string  $dir           Sorting direction
 * @param  string  $crit          Search criterion
 * @param  string  $search_method Search method
 * @return string  HTML
 * @example
 * echo form(
 *     multi_edit(array(
 *         'feature' => array('label' => 'Feature', 'html' => yesnoRadio('is_featured', 1)),
 *         'delete'  => array('label' => 'Delete'),
 *     ))
 * );
 */

function multi_edit($options, $event = null, $step = null, $page = '', $sort = '', $dir = '', $crit = '', $search_method = '')
{
    $html = $methods = array();
    $methods[''] = gTxt('with_selected_option', array('{count}' => '0'));

    if ($event === null) {
        global $event;
    }

    if ($step === null) {
        $step = $event.'_multi_edit';
    }

    callback_event_ref($event.'_ui', 'multi_edit_options', 0, $options);

    foreach ($options as $value => $option) {
        if (is_array($option)) {
            $methods[$value] = $option['label'];

            if (isset($option['html'])) {
                $html[$value] = n.tag($option['html'], 'div', array(
                    'class'             => 'multi-option',
                    'data-multi-option' => $value,
                ));
            }
        } else {
            $methods[$value] = $option;
        }
    }

    return n.tag(
        selectInput('edit_method', $methods, '').
        eInput($event).
        sInput($step).
        hInput('page', $page).
        ($sort ? hInput('sort', $sort).hInput('dir', $dir) : '').
        ($crit !== '' ? hInput('crit', $crit).hInput('search_method', $search_method) : '').
        join('', $html).
        fInput('submit', '', gTxt('go')),
        'div',
        array('class' => 'multi-edit')
    );
}

/**
 * Renders a widget to select various amounts to page lists by.
 *
 * The rendered options can be changed via a '{$event}_ui > pageby_values'
 * callback event.
 *
 * @param  string      $event Event
 * @param  int         $val   Current setting
 * @param  string|null $step  Step
 * @return string HTML
 * @deprecated in 4.7.0
 */

function pageby_form($event, $val, $step = null)
{
    return Txp::get('\Textpattern\Admin\Paginator', $event, $step)->render($val);
}

/**
 * Renders an upload form.
 *
 * The rendered form can be customised via the '{$event}_ui > upload_form'
 * pluggable UI callback event.
 *
 * @param  string       $label         File name label. May be empty
 * @param  string       $pophelp       Help item
 * @param  string       $step          Step
 * @param  string       $event         Event
 * @param  string       $id            File id
 * @param  int          $max_file_size Maximum allowed file size
 * @param  string       $label_id      HTML id attribute for the filename input element
 * @param  string       $class         HTML class attribute for the form element
 * @param  string|array $wraptag_val   Tag to wrap the value / label in, or empty to omit
 * @param  array        $extra         array('postinput' => $categories ...)
 * @param  string|array $accept        Comma separated list of allowed file types, or empty to omit
 * @return string HTML
 */

function upload_form($label, $pophelp, $step, $event, $id = '', $max_file_size = 1000000, $label_id = '', $class = '', $wraptag_val = array('div', 'div'), $extra = null, $accept = '')
{
    if (!$label_id) {
        $label_id = $event.'-upload';
    }

    if ($wraptag_val) {
        $wraptag_class = 'txp-form-field file-uploader';
    } else {
        $wraptag_class = 'inline-file-uploader';
    }

    if ($multiple = (bool) preg_match('/^.+\[\]$/', $step)) {
        $step = substr($step, 0, -2);
    }

    $name = 'thefile'.($multiple ? '[]' : '');
    $argv = func_get_args();

    return pluggable_ui(
        $event.'_ui',
        'upload_form',
        n.tag(
            (!empty($max_file_size) ? hInput('MAX_FILE_SIZE', $max_file_size) : '').
            eInput($event).
            sInput($step).
            hInput('id', $id).
            tInput().
            inputLabel(
                $label_id,
                n.tag_void('input', array(
                    'name'     => $name,
                    'type'     => 'file',
                    'required' => true,
                    'id'       => $label_id,
                    'multiple' => $multiple,
                    'accept'   => $accept,
                )).
                (isset($extra['postinput']) ? $extra['postinput'] : '').
                n.tag(
                    fInput('reset', '', gTxt('reset')).
                    fInput('submit', '', gTxt('upload')).n,
                    'span',
                    array('class' => 'inline-file-uploader-actions')
                ),
                $label,
                array($pophelp, 'instructions_'.$pophelp),
                $wraptag_class,
                $wraptag_val
            ).
            tag(null, 'progress', array(
                'class' => 'txp-upload-progress',
                'style' =>  'display:none',
            )),
            'form',
            array(
                'class'   => 'upload-form'.($class ? ' '.trim($class) : ''),
                'method'  => 'post',
                'enctype' => 'multipart/form-data',
                'action'  => "index.php?event=$event&step=$step",
            )
        ),
        $argv
    );
}

/**
 * Renders an admin-side search form.
 *
 * @param  string $event          Event
 * @param  string $step           Step
 * @param  string $crit           Search criterion
 * @param  array  $methods        Valid search methods
 * @param  string $method         Actual search method
 * @param  string $default_method Default search method
 * @return string HTML
 */

function search_form($event, $step, $crit, $methods, $method, $default_method)
{
    $method = ($method) ? $method : $default_method;

    return form(
        graf(
            tag(gTxt('search'), 'label', array('for' => $event.'-search')).
            selectInput('search_method', $methods, $method, '', '', $event.'-search').
            fInput('text', 'crit', $crit, 'input-medium', '', '', INPUT_MEDIUM).
            eInput($event).
            sInput($step).
            fInput('submit', 'search', gTxt('go'))
        ), '', '', 'get', 'search-form'
    );
}

/**
 * Renders a dropdown for selecting Textfilter method preferences.
 *
 * @param  string $name Element name
 * @param  string $val  Current value
 * @param  string $id   HTML id attribute for the select input element
 * @return string HTML
 */

function pref_text($name, $val, $id = '')
{
    $id = ($id) ? $id : $name;
    $vals = Txp::get('\Textpattern\Textfilter\Registry')->getMap();

    return selectInput($name, $vals, $val, '', '', $id);
}

/**
 * Attaches a HTML fragment to a DOM node.
 *
 * @param  string $id        Target DOM node's id
 * @param  string $content   HTML fragment
 * @param  string $noscript  Noscript alternative
 * @param  string $wraptag   Wrapping HTML element
 * @param  string $wraptagid Wrapping element's HTML id
 * @return string HTML/JS
 */

function dom_attach($id, $content, $noscript = '', $wraptag = 'div', $wraptagid = '')
{
    $id = escape_js($id);
    $content = escape_js($content);
    $wraptag = escape_js($wraptag);
    $wraptagid = escape_js($wraptagid);

    $js = <<<EOF
        $(function ()
        {
            $('#{$id}').append($('<{$wraptag} />').attr('id', '{$wraptagid}').html('{$content}'));
        });
EOF;

    return script_js($js, (string) $noscript);
}

/**
 * Renders a &lt:script&gt; element.
 *
 * The $route parameter allows script_js() to be included in fixed page
 * locations (e.g. prior to the &lt;/body&gt; tag) but to only render
 * its content if the event / step match.
 *
 * @param  string     $js    JavaScript code
 * @param  int|string $flags Flags TEXTPATTERN_SCRIPT_URL | TEXTPATTERN_SCRIPT_ATTACH_VERSION, or boolean or noscript alternative if a string
 * @param  array      $route Optional events/steps upon which to add the script
 * @return string HTML with embedded script element
 * @example
 * echo script_js('/js/script.js', TEXTPATTERN_SCRIPT_URL);
 */

function script_js($js, $flags = '', $route = array())
{
    static $store = '';
    global $event, $step;

    $targetEvent = empty($route[0]) ? null : (is_array($route[0]) ? $route[0] : do_list_unique($route[0]));
    $targetStep = empty($route[1]) ? null : (is_array($route[1]) ? $route[1] : do_list_unique($route[1]));

    if (($targetEvent === null || in_array($event, $targetEvent)) && ($targetStep === null || in_array($step, $targetStep))) {
        if (is_int($flags)) {
            if ($flags & TEXTPATTERN_SCRIPT_URL) {
                if ($flags & TEXTPATTERN_SCRIPT_ATTACH_VERSION && strpos(txp_version, '-dev') === false) {
                    $ext = pathinfo($js, PATHINFO_EXTENSION);

                    if ($ext) {
                        $js = substr($js, 0, (strlen($ext) + 1) * -1);
                        $ext = '.'.$ext;
                    }

                    $js .= '.v'.txp_version.$ext;
                }

                return n.tag(null, 'script', array('src' => $js));
            }
        }

        $js = preg_replace('#<(/?)(script)#i', '\\x3c$1$2', $js);

        if (is_bool($flags)) {
            if (!$flags) {
                $store .= n.$js;

                return;
            } else {
                $js = $store.n.$js;
                $store = '';
            }
        }

        $js = trim($js);
        $out = $js ? n.tag(n.$js.n, 'script') : '';

        if ($flags && $flags !== true) {
            $out .= n.tag(n.trim($flags).n, 'noscript');
        }

        return $out;
    }

    return '';
}

/**
 * Renders a checkbox to set/unset a browser cookie.
 *
 * @param  string $classname Label text. The cookie's name will be derived from this value
 * @param  bool   $form      Create as a stand-along &lt;form&gt; element
 * @return string HTML
 */

function cookie_box($classname, $form = true)
{
    $name = 'cb_'.$classname;
    $id = escape_js($name);
    $class = escape_js($classname);

    if (cs('toggle_'.$classname)) {
        $value = 1;
    } else {
        $value = 0;
    }

    $newvalue = 1 - $value;

    $out = checkbox($name, 1, (bool) $value, 0, $name).
        n.tag(gTxt($classname), 'label', array('for' => $name));

    $js = <<<EOF
        $(function ()
        {
            $('input')
                .filter(function () {
                    if ($(this).attr('id') === '{$id}') {
                        return true;
                    }
                })
                .change(function () {
                    setClassRemember('{$class}', $newvalue);
                    $(this).parents('form').submit();
                });
        });
EOF;

    $out .= script_js($js);

    if ($form) {
        if (serverSet('QUERY_STRING')) {
            $action = 'index.php?'.serverSet('QUERY_STRING');
        } else {
            $action = 'index.php';
        }

        $out .= eInput(gps('event')).tInput();

        return tag($out, 'form', array(
            'class'  => $name,
            'method' => 'post',
            'action' => $action,
        ));
    }

    return $out;
}

/**
 * Renders a &lt;fieldset&gt; element.
 *
 * @param  string $content Enclosed content
 * @param  string $legend  Legend text
 * @param  string $id      HTML id attribute
 * @return string HTML
 */

function fieldset($content, $legend = '', $id = '')
{
    return tag(trim(tag($legend, 'legend').n.$content), 'fieldset', array('id' => $id));
}

/**
 * Renders a link element to hook up txpAsyncHref() with request parameters.
 *
 * See this function's JavaScript companion, txpAsyncHref(), in textpattern.js.
 *
 * @param  string       $item  Link text
 * @param  array        $parms Request parameters; array keys are 'event', 'step', 'thing', 'property'
 * @param  string|array $atts  HTML attributes
 * @return string HTML
 * @since  4.5.0
 * @example
 * echo asyncHref('Disable', array(
 *     'event'    => 'myEvent',
 *     'step'     => 'myStep',
 *     'thing'    => 'status',
 *     'property' => 'disable',
 * ));
 */

function asyncHref($item, $parms, $atts = '')
{
    global $event, $step;

    $parms = lAtts(array(
        'event'    => $event,
        'step'     => $step,
        'thing'    => '',
        'property' => '',
    ), $parms);

    $class = $parms['step'].' async';

    if (is_array($atts)) {
        $atts['class'] = $class;
    } else {
        $atts .= ' class="'.txpspecialchars($class).'"';
    }

    return href($item, join_qs($parms), $atts);
}

/**
 * Renders an array of items as a HTML list.
 *
 * This function is used for tag handler functions. Creates a HTML list markup
 * from an array of items.
 *
 * @param   array  $list
 * @param   string $wraptag    The HTML element
 * @param   string $break      The HTML break element
 * @param   string $class      Class applied to the wraptag
 * @param   string $breakclass Class applied to break tag
 * @param   string $atts       HTML attributes applied to the wraptag
 * @param   string $breakatts  HTML attributes applied to the break tag
 * @param   string $id         HTML id applied to the wraptag
 * @return  string HTML
 * @package HTML
 * @example
 * echo doWrap(array('item1', 'item2'), 'div', 'p');
 */

function doWrap($list, $wraptag = null, $break = null, $class = null, $breakclass = null, $atts = null, $breakatts = null, $html_id = null)
{
    global $txp_atts, $txp_item;
    static $regex = '/([^\\\w\s]).+\1[UsiAmuS]*$/As',
        $import = array('break', 'breakby', 'breakclass', 'breakform', 'class', 'escape', 'html_id', 'wrapform', 'trim', 'replace', 'limit', 'offset', 'sort');

    $list = array_filter(is_array($list) ? $list : array($list), function ($v) {
        return $v !== false;
    });

    if (is_array($break)) {
        extract($break + array('break' => ''));
    }

    foreach ($import as $global) {
        isset($$global) or $$global = isset($txp_atts[$global]) ? $txp_atts[$global] : null;
        unset($txp_atts[$global]);
    }

    if (isset($trim) || isset($replace)) {
        $replacement = $replace === true ? null : $replace;

        if ($trim === true) {
            $list = array_map('trim', $list);
            !isset($replacement) or $list = preg_replace('/\s+/', $replacement, $list);
            $list = array_filter($list, function ($v) {return $v !== '';});
        } elseif (isset($trim)) {
            $list = strlen($trim) > 2 && preg_match($regex, $trim) ?
                preg_replace($trim, (string)$replacement, $list) :
                (isset($replacement) ?
                    str_replace($trim, $replacement, $list) :
                    array_map(function ($v) use ($trim) {return trim($v, $trim);}, $list)
                );
            $list = array_filter($list, function ($v) {return $v !== '';});
        } elseif (isset($replacement)) {
            $list = strlen($replacement) > 2 && preg_match($regex, $replacement) ?
                array_filter($list, function ($v) use ($replacement) {return preg_match($replacement, $v);}) :
                array_filter($list, function ($v) use ($replacement) {return strpos($v, $replacement) !== false;});
        }

        if ($replace === true) {
            $list = array_unique($list);
        }
    }

    if (!$list) {
        return '';
    }

    if (($sort = isset($sort) ? strtolower($sort) : '') || $offset == '?' && $limit > 0) {
        $rand = strpos($sort, 'rand') !== false;

        if ($rand || $offset == '?') {
            if ($limit == 1) {
                $list = array($list[array_rand($list)]);
            } elseif ($limit && $limit < count($list)) {
                $newlist = array();

                foreach (array_rand($list, (int)$limit) as $key) {
                    $newlist[] = $list[$key];
                }

                $list = $newlist;
            }

            $limit = $offset = null;
        }

        $flag = (strpos($sort, 'nat') === false ? SORT_STRING : SORT_NATURAL) | (strpos($sort, 'case') === false ? SORT_FLAG_CASE : 0);
        $rand ? shuffle($list) : (strpos($sort, 'desc') !== false ? rsort($list, $flag) : ($sort ? sort($list, $flag) : null));
    }

    if($limit || $offset) {
        if (!$offset || is_numeric($offset)) {
            $list = array_slice($list, (int)$offset, isset($limit) ? (int)$limit : null);
        } else {
            $count = count($list);
            $newlist = array();

            foreach (do_list($offset, array(',', '-')) as $ind) {
                $ind = $ind === '?' ? array_rand($list) : ($ind >= 0 ? (int)$ind - 1 : $count + (int)$ind);
                !isset($list[$ind]) or $newlist[] = $list[$ind];
            }

            $list = $limit ? array_slice($newlist, 0, (int)$limit) : $newlist;
        }
    }

    if (($break || $breakform) && !empty($breakby)) { // array_merge to reindex
        $breakby = array_merge(array_filter(array_map('intval', do_list($breakby))));
        $newlist = array();

        switch ($count = count($breakby)) {
            case 0:
                break;
            case 1:
                if ($breakby[0] > 0) {
                    $breakby[0] == 1 or $newlist = array_chunk($list, $breakby[0]);
                    break;
                }
                // no break
            default:
                for ($i = 0; count($list); $i = ($i + 1)%$count) {
                    $newlist[] = $breakby[$i] > 0 ? array_splice($list, 0, $breakby[$i]) :  array_splice($list, $breakby[$i]);
                }

        }

        empty($newlist) or $list = array_map('implode', $newlist);
    }

    $old_item = $txp_item;

    if ($escape) {
        foreach ($list as &$item) {
            $item = txp_escape($escape, $item);
        }
    }

    if ($breakform) {
        array_walk($list, function (&$item, $key) use ($breakform) {
            global $txp_item;
            $txp_item['count'] = $key + 1;
            $txp_item[true] = $item;
            $item = str_replace('<+>', $item, parse_form($breakform));
            unset($txp_item);
        });
    }

    if ($html_id) {
        $atts .= ' id="'.txpspecialchars($html_id).'"';
    }

    if ($class) {
        $atts .= ' class="'.txpspecialchars($class).'"';
    }

    if ($breakclass) {
        $breakatts .= ' class="'.txpspecialchars($breakclass).'"';
    }

    if ($break === true) {
        $break = txp_break($wraptag);
    }

    if ((string)$break === '') {
        $content = join('', $list);
    } elseif (strpos($break, '<+>') !== false) {
        $content = array_reduce($list, function ($carry, $item) use ($break) {
            return $carry.str_replace('<+>', $item, $break);
        });
    }
    // Non-enclosing breaks.
    elseif ($break === 'br' || $break === 'hr' || !preg_match('/^\w+$/', $break)) {
        if ($break === 'br' || $break === 'hr') {
            $break = "<$break $breakatts/>".n;
        }

        $content = join($break, $list);
    } else {
        $content = "<{$break}{$breakatts}>".join("</$break>".n."<{$break}{$breakatts}>", $list)."</{$break}>";
    }

    if (!empty($wrapform)) {
        $content = str_replace('<+>', $content, parse_form($wrapform));
    }

    $txp_item = $old_item;

    return empty($wraptag) ? $content : tag($content, $wraptag, $atts);
}

/**
 * Renders anything as a HTML tag.
 *
 * Used for tag handler functions.
 *
 * If $content is empty, renders a self-closing tag.
 *
 * @param   string $content The wrapped item
 * @param   string $tag     The HTML tag
 * @param   string $class   HTML class
 * @param   string $atts    HTML attributes
 * @param   string $id      HTML id
 * @return  string HTML
 * @package HTML
 * @example
 * echo doTag('', 'meta', '', 'name="description" content="Some content"');
 */

function doTag($content, $tag, $class = '', $atts = '', $id = '')
{
    if (!$tag) {
        return $content;
    }

    if ($id) {
        $atts .= ' id="'.txpspecialchars($id).'"';
    }

    if ($class) {
        $atts .= ' class="'.txpspecialchars($class).'"';
    }

    return (string)$content !== '' ? tag($content, $tag, $atts) : "<$tag $atts />";
}

/**
 * Renders a label.
 *
 * This function is mostly used for rendering headings in tag handler functions.
 *
 * If no $labeltag is given, label is separated from the content with
 * a &lt;br&gt;.
 *
 * @param   string $label    The label
 * @param   string $labeltag The HTML element
 * @return  string HTML
 * @package HTML
 * @example
 * echo doLabel('My label', 'h3');
 */

function doLabel($label = '', $labeltag = '')
{
    if ($label) {
        return (empty($labeltag) ? $label.'<br />' : tag($label, $labeltag));
    }

    return '';
}

<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2016 The Textpattern Development Team
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
 * Sections panel.
 *
 * @package Admin\Section
 */

use Textpattern\Search\Filter;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'section') {
    require_privs('section');

    global $all_pages, $all_styles;
    $all_pages = safe_column("name", 'txp_page', "1 = 1 ORDER BY name");
    $all_styles = safe_column("name", 'txp_css', "1 = 1 ORDER BY name");

    $available_steps = array(
        'section_change_pageby' => true,
        'sec_section_list'      => false,
        'section_delete'        => true,
        'section_save'          => true,
        'section_edit'          => false,
        'section_multi_edit'    => true,
        'section_set_default'   => true,
        'section_toggle_option' => true,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        sec_section_list();
    }
}

/**
 * The main panel listing all sections.
 *
 * So-named to avoid clashing with the &lt;txp:section_list /&gt; tag.
 *
 * @param string|array $message The activity message
 */

function sec_section_list($message = '')
{
    global $event, $section_list_pageby;

    pagetop(gTxt('tab_sections'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    if ($sort === '') {
        $sort = get_pref('section_sort_column', 'name');
    } else {
        if (!in_array($sort, array('title', 'page', 'css', 'in_rss', 'on_frontpage', 'searchable', 'article_count'))) {
            $sort = 'name';
        }

        set_pref('section_sort_column', $sort, 'section', 2, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('section_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('section_sort_dir', $dir, 'section', 2, '', 0, PREF_PRIVATE);
    }

    switch ($sort) {
        case 'title':
            $sort_sql = "title $dir";
            break;
        case 'page':
            $sort_sql = "page $dir";
            break;
        case 'css':
            $sort_sql = "css $dir";
            break;
        case 'in_rss':
            $sort_sql = "in_rss $dir";
            break;
        case 'on_frontpage':
            $sort_sql = "on_frontpage $dir";
            break;
        case 'searchable':
            $sort_sql = "searchable $dir";
            break;
        case 'article_count':
            $sort_sql = "article_count $dir";
            break;
        default:
            $sort_sql = "name $dir";
            break;
    }

    $switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

    $search = new Filter($event,
        array(
            'name' => array(
                'column' => 'txp_section.name',
                'label'  => gTxt('name'),
            ),
            'title' => array(
                'column' => 'txp_section.title',
                'label'  => gTxt('title'),
            ),
            'page' => array(
                'column' => 'txp_section.page',
                'label'  => gTxt('page'),
            ),
            'css' => array(
                'column' => 'txp_section.css',
                'label'  => gTxt('css'),
            ),
            'on_frontpage' => array(
                'column' => 'txp_section.on_frontpage',
                'label'  => gTxt('on_front_page'),
                'type'   => 'boolean',
            ),
            'in_rss' => array(
                'column' => 'txp_section.in_rss',
                'label'  => gTxt('syndicate'),
                'type'   => 'boolean',
            ),
            'searchable' => array(
                'column' => 'txp_section.searchable',
                'label'  => gTxt('include_in_search'),
                'type'   => 'boolean',
            ),
        )
    );

    $alias_yes = '1, Yes';
    $alias_no = '0, No';
    $search->setAliases('on_frontpage', array($alias_no, $alias_yes));
    $search->setAliases('in_rss', array($alias_no, $alias_yes));
    $search->setAliases('searchable', array($alias_no, $alias_yes));

    list($criteria, $crit, $search_method) = $search->getFilter();

    $search_render_options = array(
        'placeholder' => 'search_sections',
    );

    $total = safe_count('txp_section', $criteria);

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_sections'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-4col-alt')
        );

    $searchBlock =
        n.tag(
            $search->renderForm('sec_section', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );

    $createBlock = array();

    if (has_privs('section.edit')) {
        $createBlock[] =
            n.tag(
                sLink('section', 'section_edit', gTxt('create_section'), 'txp-button').
                n.tag_start('form', array(
                    'class'  => 'async',
                    'id'     => 'default_section_form',
                    'name'   => 'default_section_form',
                    'method' => 'post',
                    'action' => 'index.php',
                )).
                tag(gTxt('default_write_section'), 'label', array('for' => 'default_section')).
                popHelp('section_default').
                section_select_list().
                eInput('section').
                sInput('section_set_default').
                n.tag_end('form'),
                'div', array('class' => 'txp-control-panel')
            );
    }

    $contentBlockStart = n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => $event.'_container',
        ));

    $createBlock = implode(n, $createBlock);

    if ($total < 1) {
        if ($criteria != 1) {
            echo $searchBlock.
                $contentBlockStart.
                $createBlock.
                graf(
                    span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                    gTxt('no_results_found'),
                    array('class' => 'alert-block information')
                ).
                n.tag_end('div'). // End of .txp-layout-1col.
                n.'</div>'; // End of .txp-layout.
        }

        return;
    }

    $limit = max($section_list_pageby, 15);

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    echo $searchBlock.$contentBlockStart.$createBlock;

    $rs = safe_rows_start(
        "*, (SELECT COUNT(*) FROM ".safe_pfx_j('textpattern')." WHERE textpattern.Section = txp_section.name) AS article_count",
        'txp_section',
        "$criteria ORDER BY $sort_sql LIMIT $offset, $limit"
    );

    if ($rs) {
        echo n.tag(
                toggle_box('section_detail'), 'div', array('class' => 'txp-list-options')).
            n.tag_start('form', array(
                'class'  => 'multi_edit_form',
                'id'     => 'section_form',
                'name'   => 'longform',
                'method' => 'post',
                'action' => 'index.php',
            )).
            n.tag_start('div', array('class' => 'txp-listtables')).
            n.tag_start('table', array('class' => 'txp-list')).
            n.tag_start('thead').
            tr(
                hCell(
                    fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                        '', ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                ).
                column_head(
                    'name', 'name', 'section', true, $switch_dir, $crit, $search_method,
                        (('name' == $sort) ? "$dir " : '').'txp-list-col-name'
                ).
                column_head(
                    'title', 'title', 'section', true, $switch_dir, $crit, $search_method,
                        (('title' == $sort) ? "$dir " : '').'txp-list-col-title'
                ).
                column_head(
                    'page', 'page', 'section', true, $switch_dir, $crit, $search_method,
                        (('page' == $sort) ? "$dir " : '').'txp-list-col-page'
                ).
                column_head(
                    'css', 'css', 'section', true, $switch_dir, $crit, $search_method,
                        (('css' == $sort) ? "$dir " : '').'txp-list-col-style'
                ).
                column_head(
                    'on_front_page', 'on_frontpage', 'section', true, $switch_dir, $crit, $search_method,
                        (('on_frontpage' == $sort) ? "$dir " : '').'txp-list-col-frontpage section_detail'
                ).
                column_head(
                    'syndicate', 'in_rss', 'section', true, $switch_dir, $crit, $search_method,
                        (('in_rss' == $sort) ? "$dir " : '').'txp-list-col-syndicate section_detail'
                ).
                column_head(
                    'include_in_search', 'searchable', 'section', true, $switch_dir, $crit, $search_method,
                        (('searchable' == $sort) ? "$dir " : '').'txp-list-col-searchable section_detail'
                ).
                column_head(
                    'articles', 'article_count', 'section', true, $switch_dir, $crit, $search_method,
                        (('article_count' == $sort) ? "$dir " : '').'txp-list-col-article_count section_detail'
                )
            ).
            n.tag_end('thead').
            n.tag_start('tbody');

        while ($a = nextRow($rs)) {
            extract($a, EXTR_PREFIX_ALL, 'sec');

            $edit_url = array(
                'event'         => 'section',
                'step'          => 'section_edit',
                'name'          => $sec_name,
                'sort'          => $sort,
                'dir'           => $dir,
                'page'          => $page,
                'search_method' => $search_method,
                'crit'          => $crit,
            );

            if ($sec_name == 'default') {
                $articles = $sec_searchable = $sec_in_rss = $sec_on_frontpage = '-';
            } else {
                $sec_on_frontpage = asyncHref(yes_no($sec_on_frontpage), array(
                    'step'     => 'section_toggle_option',
                    'thing'    => $sec_name,
                    'property' => 'on_frontpage',
                ));

                $sec_in_rss = asyncHref(yes_no($sec_in_rss), array(
                    'step'     => 'section_toggle_option',
                    'thing'    => $sec_name,
                    'property' => 'in_rss',
                ));

                $sec_searchable = asyncHref(yes_no($sec_searchable), array(
                    'step'     => 'section_toggle_option',
                    'thing'    => $sec_name,
                    'property' => 'searchable',
                ));

                if ($sec_article_count > 0) {
                    $articles = href($sec_article_count, array(
                        'event'         => 'list',
                        'search_method' => 'section',
                        'crit'          => '"'.$sec_name.'"',
                    ), array(
                        'title' => gTxt('article_count', array('{num}' => $sec_article_count)),
                    ));
                } else {
                    $articles = 0;
                }
            }

            $sec_page = href(txpspecialchars($sec_page), array(
                'event' => 'page',
                'name'  => $sec_page,
            ), array('title' => gTxt('edit')));

            $sec_css = href(txpspecialchars($sec_css), array(
                'event' => 'css',
                'name'  => $sec_css,
            ), array('title' => gTxt('edit')));

            echo tr(
                td(
                    fInput('checkbox', 'selected[]', $sec_name), '', 'txp-list-col-multi-edit'
                ).
                hCell(
                    href(
                        txpspecialchars($sec_name), $edit_url, array('title' => gTxt('edit'))
                    ).
                    span(
                        sp.span('&#124;', array('role' => 'separator')).
                        sp.href(gTxt('view'), pagelinkurl(array('s' => $sec_name))),
                        array('class' => 'txp-option-link section_detail')
                    ), '', array(
                        'class' => 'txp-list-col-name',
                        'scope' => 'row',
                    )
                ).
                td(
                    txpspecialchars($sec_title), '', 'txp-list-col-title'
                ).
                td(
                    $sec_page, '', 'txp-list-col-page'
                ).
                td(
                    $sec_css, '', 'txp-list-col-style'
                ).
                td(
                    $sec_on_frontpage, '', 'txp-list-col-frontpage section_detail'
                ).
                td(
                    $sec_in_rss, '', 'txp-list-col-syndicate section_detail'
                ).
                td(
                    $sec_searchable, '', 'txp-list-col-searchable section_detail'
                ).
                td(
                    $articles, '', 'txp-list-col-article_count section_detail'
                ),
                array('id' => 'txp_section_'.$sec_name)
            );
        }

        echo n.tag_end('tbody').
            n.tag_end('table').
            n.tag_end('div'). // End of .txp-listtables.
            section_multiedit_form($page, $sort, $dir, $crit, $search_method).
            tInput().
            n.tag_end('form').
            n.tag_start('div', array(
                'class' => 'txp-navigation',
                'id'    => $event.'_navigation',
            )).
            pageby_form('section', $section_list_pageby).
            nav_form('section', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).
            n.tag_end('div');
    }

    echo n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.
}

/**
 * Renders and outputs the section editor panel.
 */

function section_edit()
{
    global $event, $step, $all_pages, $all_styles;

    require_privs('section.edit');

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
        'name',
    )));

    $is_edit = ($name && $step == 'section_edit');
    $caption = gTxt('create_section');
    $is_default_section = false;

    if ($is_edit) {
        $rs = safe_row(
            "*",
            'txp_section',
            "name = '".doSlash($name)."'"
        );

        if ($name == 'default') {
            $caption = gTxt('edit_default_section');
            $is_default_section = true;
        } else {
            $caption = gTxt('edit_section');
        }
    } else {
        // Pulls defaults for the new section from the 'default'.
        $rs = safe_row(
            "page, css, on_frontpage, in_rss, searchable",
            'txp_section',
            "name = 'default'"
        );

        if ($rs) {
            $rs['name'] = $rs['title'] = $rs['description'] = '';
        }
    }

    if (!$rs) {
        sec_section_list(array(gTxt('unknown_section'), E_ERROR));

        return;
    }

    extract($rs, EXTR_PREFIX_ALL, 'sec');
    pagetop(gTxt('tab_sections'));

    $out = array();

    $out[] = hed($caption, 2);

    if ($is_default_section) {
        $out[] = hInput('name', 'default');
    } else {
        $out[] = inputLabel(
                'section_name',
                fInput('text', 'name', $sec_name, '', '', '', INPUT_REGULAR, '', 'section_name'),
                'section_name', '', array('class' => 'txp-form-field edit-section-name')
            ).
            inputLabel(
                'section_title',
                fInput('text', 'title', $sec_title, '', '', '', INPUT_REGULAR, '', 'section_title'),
                'section_longtitle', '', array('class' => 'txp-form-field edit-section-longtitle')
            );
    }

    $out[] = inputLabel(
            'section_page',
            selectInput('section_page', $all_pages, $sec_page, '', '', 'section_page'),
            'uses_page', 'section_uses_page', array('class' => 'txp-form-field edit-section-uses-page')
        ).
        inputLabel(
            'section_css',
            selectInput('css', $all_styles, $sec_css, '', '', 'section_css'),
            'uses_style', 'section_uses_css', array('class' => 'txp-form-field edit-section-uses-css')
        );

    if (!$is_default_section) {
        $out[] = inputLabel(
                'on_front_page',
                yesnoradio('on_frontpage', $sec_on_frontpage, '', $sec_name),
                '', 'section_on_frontpage', array('class' => 'txp-form-field edit-section-on-frontpage')
            ).
            inputLabel(
                'syndicate',
                yesnoradio('in_rss', $sec_in_rss, '', $sec_name),
                '', 'section_syndicate', array('class' => 'txp-form-field edit-section-syndicate')
            ).
            inputLabel(
                'include_in_search',
                yesnoradio('searchable', $sec_searchable, '', $sec_name),
                '', 'section_searchable', array('class' => 'txp-form-field edit-section-searchable')
            );
    }

    $out[] = inputLabel(
            'section_description',
            '<textarea id="section_description" name="description" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.$sec_description.'</textarea>',
            'description', 'section_description', array('class' => 'txp-form-field txp-form-field-textarea edit-section-description')
        );

    $out[] = pluggable_ui('section_ui', 'extend_detail_form', '', $rs).
        graf(
            sLink('section', '', gTxt('cancel'), 'txp-button').
            fInput('submit', '', gTxt('save'), 'publish'),
            array('class' => 'txp-edit-actions')
        ).
        eInput('section').
        sInput('section_save').
        hInput('old_name', $sec_name).
        hInput('search_method', $search_method).
        hInput('crit', $crit).
        hInput('page', $page).
        hInput('sort', $sort).
        hInput('dir', $dir);

    echo form(join('', $out), '', '', 'post', 'txp-edit', '', 'section_details');
}

/**
 * Saves a section.
 */

function section_save()
{
    $in = array_map('assert_string', psa(array(
        'name',
        'title',
        'description',
        'old_name',
        'section_page',
        'css',
    )));

    if (empty($in['title'])) {
        $in['title'] = $in['name'];
    }

    // Prevent non-URL characters on section names.
    $in['name'] = strtolower(sanitizeForUrl($in['name']));

    extract($in);

    $in = doSlash($in);
    extract($in, EXTR_PREFIX_ALL, 'safe');

    if ($name != strtolower($old_name)) {
        if (safe_field("name", 'txp_section', "name = '$safe_name'")) {
            // Invalid input. Halt all further processing (e.g. plugin event
            // handlers).
            $message = array(gTxt('section_name_already_exists', array('{name}' => $name)), E_ERROR);
//            modal_halt($message);
            sec_section_list($message);

            return;
        }
    }

    $ok = false;
    if ($name == 'default') {
        $ok = safe_update('txp_section', "page = '$safe_section_page', css = '$safe_css', description = '$safe_description'", "name = 'default'");
    } elseif ($name) {
        extract(array_map('assert_int', psa(array('on_frontpage', 'in_rss', 'searchable'))));

        if ($safe_old_name) {
            $ok = safe_update('txp_section', "
                name         = '$safe_name',
                title        = '$safe_title',
                page         = '$safe_section_page',
                css          = '$safe_css',
                description  = '$safe_description',
                on_frontpage = $on_frontpage,
                in_rss       = $in_rss,
                searchable   = $searchable
                ", "name = '$safe_old_name'");

            // Manually maintain referential integrity.
            if ($ok) {
                $ok = safe_update('textpattern', "Section = '$safe_name'", "Section = '$safe_old_name'");
            }
        } else {
            $ok = safe_insert('txp_section', "
                name         = '$safe_name',
                title        = '$safe_title',
                page         = '$safe_section_page',
                css          = '$safe_css',
                description  = '$safe_description',
                on_frontpage = $on_frontpage,
                in_rss       = $in_rss,
                searchable   = $searchable");
        }
    }

    if ($ok) {
        update_lastmod('section_saved', compact('name', 'title', 'page', 'css', 'description', 'on_frontpage', 'in_rss', 'searchable'));
    }

    if ($ok) {
        sec_section_list(gTxt(($safe_old_name ? 'section_updated' : 'section_created'), array('{name}' => $name)));
    } else {
        sec_section_list(array(gTxt('section_save_failed'), E_ERROR));
    }
}

/**
 * Changes and saves the pageby value.
 */

function section_change_pageby()
{
    event_change_pageby('section');
    sec_section_list();
}

/**
 * Toggles section yes/no parameters.
 *
 * This function requires three HTTP POST parameters: 'column', 'value' and
 * 'name'. The 'value' is the new value, localised 'Yes' or 'No',
 * 'name' is the section and the 'column' is the altered setting,
 * either 'on_frontpage', 'in_rss' or 'searchable'.
 *
 * Outputs a text/plain response comprising the new displayable
 * value for the toggled parameter.
 */

function section_toggle_option()
{
    extract(psa(array(
        'property',
        'value',
        'thing',
    )));

    $value = (int) ($value === gTxt('no'));

    if (in_array($property, array('on_frontpage', 'in_rss', 'searchable'))) {
        if (safe_update('txp_section', "$property = $value", "name = '".doSlash($thing)."'")) {
            echo yes_no($value);

            return;
        }
    }

    trigger_error(gTxt('section_save_failed'), E_USER_ERROR);
}

/**
 * Sets a section as the default.
 */

function section_set_default()
{
    extract(psa(array(
        'default_section',
    )));

    $exists = safe_row("name", 'txp_section', "name = '".doSlash($default_section)."'");

    if ($exists && set_pref('default_section', $default_section, 'section', PREF_HIDDEN)) {
        send_script_response(announce(gTxt('default_section_updated')));

        return;
    }

    send_script_response(announce(gTxt('section_save_failed'), E_ERROR));
}

/**
 * Renders a 'default_section' &lt;select&gt; input listing all sections.
 *
 * Used for changing the default section.
 *
 * @return string HTML
 */

function section_select_list()
{
    $val = get_pref('default_section');
    $sections = safe_rows("name, title", 'txp_section', "name != 'default' ORDER BY title, name");
    $vals = array();
    foreach ($sections as $row) {
        $vals[$row['name']] = $row['title'];
    }

    return selectInput('default_section', $vals, $val, false, true, 'default_section');
}

/**
 * Processes delete actions sent using the multi-edit form.
 */

function section_delete()
{
    $selectedList = ps('selected');
    $selected = join(',', quote_list($selectedList));
    $message = '';

    $sections = safe_column(
        "name",
        'txp_section',
        "name != 'default' AND name IN ($selected) AND name NOT IN (SELECT Section FROM ".safe_pfx('textpattern').")"
    );

    $sectionsNotDeleted = array_diff($selectedList, $sections);

    if ($sections && safe_delete('txp_section', "name IN (".join(',', quote_list($sections)).")")) {
        callback_event('sections_deleted', '', 0, $sections);
        $message = gTxt('section_deleted', array('{name}' => join(', ', $sections)));
    }

    if ($sectionsNotDeleted) {
        $severity = ($message) ? E_WARNING : E_ERROR;
        $message = array(($message ? $message.n : '') . gTxt('section_delete_failure', array('{name}' => join(', ', $sectionsNotDeleted))), $severity);
    }

    sec_section_list($message);
}

/**
 * Renders a multi-edit form widget.
 *
 * @param  int    $page          The page number
 * @param  string $sort          The current sorting value
 * @param  string $dir           The current sorting direction
 * @param  string $crit          The current search criteria
 * @param  string $search_method The current search method
 * @return string HTML
 */

function section_multiedit_form($page, $sort, $dir, $crit, $search_method)
{
    global $all_pages, $all_styles;

    $methods = array(
        'changepage' => array(
            'label' => gTxt('uses_page'),
            'html'  => selectInput('uses_page', $all_pages, '', false),
        ),
        'changecss' => array(
            'label' => gTxt('uses_style'),
            'html'  => selectInput('css', $all_styles, '', false),
        ),
        'changeonfrontpage' => array(
            'label' => gTxt('on_front_page'),
            'html'  => yesnoRadio('on_frontpage', 1),
        ),
        'changesyndicate' => array(
            'label' => gTxt('syndicate'),
            'html'  => yesnoRadio('in_rss', 1),
        ),
        'changesearchable' => array(
            'label' => gTxt('include_in_search'),
            'html'  => yesnoRadio('searchable', 1),
        ),
        'delete' => gTxt('delete'),
    );

    return multi_edit($methods, 'section', 'section_multi_edit', $page, $sort, $dir, $crit, $search_method);
}

/**
 * Processes multi-edit actions.
 */

function section_multi_edit()
{
    global $txp_user, $all_pages, $all_styles;

    extract(psa(array(
        'edit_method',
        'selected',
    )));

    if (!$selected || !is_array($selected)) {
        return sec_section_list();
    }

    $key = $val = '';

    switch ($edit_method) {
        case 'delete':
            return section_delete();
            break;
        case 'changepage':
            $val = ps('uses_page');
            if (in_array($val, $all_pages, true)) {
                $key = 'page';
            }
            break;
        case 'changecss':
            $val = ps('css');
            if (in_array($val, $all_styles, true)) {
                $key = 'css';
            }
            break;
        case 'changeonfrontpage':
            $key = 'on_frontpage';
            $val = (int) ps('on_frontpage');
            break;
        case 'changesyndicate':
            $key = 'in_rss';
            $val = (int) ps('in_rss');
            break;
        case 'changesearchable':
            $key = 'searchable';
            $val = (int) ps('searchable');
            break;
    }

    $sections = safe_column(
        "name",
        'txp_section',
        "name IN (".join(',', quote_list($selected)).")"
    );

    if ($key && $sections) {
        if (
            safe_update(
                'txp_section',
                "$key = '".doSlash($val)."'",
                "name IN (".join(',', quote_list($sections)).")"
            )
        ) {
            sec_section_list(gTxt('section_updated', array('{name}' => join(', ', $sections))));

            return;
        }
    }

    sec_section_list();
}

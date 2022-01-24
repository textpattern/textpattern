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

    global $all_skins, $all_pages, $all_styles;

    $all_skins = \Txp::get('Textpattern\Skin\Skin')->getInstalled();
    $all_pages = \Txp::get('Textpattern\Skin\Page')->getInstalled();
    $all_styles = \Txp::get('Textpattern\Skin\Css')->getInstalled();

    $available_steps = array(
        'section_change_pageby' => true,
        'sec_section_list'      => false,
        'section_delete'        => true,
        'section_save'          => true,
        'section_edit'          => false,
        'section_multi_edit'    => true,
        'section_set_default'   => true,
        'section_set_theme'     => true,
        'section_select_skin'   => false,
        'section_toggle_option' => true,
    );

    if ($step && is_callable($step) && bouncer($step, $available_steps)) {
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

function sec_section_list($message = '', $update = false)
{
    global $event, $step, $all_pages, $all_styles, $txp_sections;

    if ($update) {
        $txp_sections = safe_column(array('name'), 'txp_section', '1 ORDER BY title, name');
    }

    pagetop(gTxt('tab_sections'), $message);

    extract(gpsa(array(
        'page',
        'sort',
        'dir',
        'crit',
        'search_method',
    )));

    $columns = array('name', 'title', 'skin', 'page', 'css', 'permlink_mode', 'on_frontpage', 'in_rss', 'searchable', 'article_count');
    $columns = array_merge(
        array_combine($columns, $columns),
        array('on_frontpage' => 'on_front_page', 'in_rss' => 'syndicate', 'searchable' => 'include_in_search', 'article_count' => 'articles')
    );

    if ($sort === '') {
        $sort = get_pref('section_sort_column', 'name');
    } else {
        if (!isset($columns[$sort])) {
            $sort = 'name';
        }

        set_pref('section_sort_column', $sort, 'section', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if ($dir === '') {
        $dir = get_pref('section_sort_dir', 'desc');
    } else {
        $dir = ($dir == 'asc') ? "asc" : "desc";
        set_pref('section_sort_dir', $dir, 'section', PREF_HIDDEN, '', 0, PREF_PRIVATE);
    }

    if (isset($columns[$sort])) {
        $sort_sql = "$sort $dir".($sort == 'name' ? '' : ", name");
    } else {
        $sort_sql = "name $dir";
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
            'skin' => array(
                'column' => array('txp_section.skin', 'txp_section.dev_skin'),
                'label'  => gTxt('skin'),
            ),
            'page' => array(
                'column' => array('txp_section.page', 'txp_section.dev_page'),
                'label'  => gTxt('page'),
            ),
            'css' => array(
                'column' => array('txp_section.css', 'txp_section.dev_css'),
                'label'  => gTxt('css'),
            ),
            'description' => array(
                'column' => 'txp_section.description',
                'label'  => gTxt('description'),
            ),
            'permlink_mode' => array(
                'column' => 'txp_section.permlink_mode',
                'label'  => gTxt('permlink_mode'),
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

    $search_render_options = array('placeholder' => 'search_sections');
    $total = safe_count('txp_section', $criteria);

    $searchBlock =
        n.tag(
            $search->renderForm('sec_section', $search_render_options),
            'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $event.'_control',
            )
        );


    getDefaultSection();
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

    $paginator = new \Textpattern\Admin\Paginator();
    $limit = $step == 'section_select_skin' ? PHP_INT_MAX : $paginator->getLimit();
    $skin = $step == 'section_select_skin' ? gps('skin') : false;

    list($page, $offset, $numPages) = pager($total, $limit, $page);

    $createBlock = implode(n, $createBlock);
    $contentBlock = '';

    if ($total < 1) {
        if ($crit !== '') {
            $contentBlock .= graf(
                span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                gTxt('no_results_found'),
                array('class' => 'alert-block information')
            );
        }
    } else {
        $rs = safe_rows_start(
            "*, (SELECT COUNT(*) FROM ".safe_pfx_j('textpattern')." WHERE textpattern.Section = txp_section.name) AS article_count",
            'txp_section',
            "$criteria ORDER BY $sort_sql LIMIT $offset, $limit"
        );

        if ($rs) {
            $dev_set = false;
            $dev_preview = get_pref('enable_dev_preview') && has_privs('skin.edit');
            $contentBlock .= n.tag_start('form', array(
                    'class'  => 'multi_edit_form',
                    'id'     => 'section_form',
                    'name'   => 'longform',
                    'method' => 'post',
                    'action' => 'index.php',
                )).
                n.tag_start('div', array(
                    'class'      => 'txp-listtables',
                    'tabindex'   => 0,
                    'aria-label' => gTxt('list'),
                )).
                n.tag_start('table', array('class' => 'txp-list')).
                n.tag_start('thead');
                $thead = hCell(
                    fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                        '', ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                );

            foreach ($columns as $column => $label) {
                $thead .= column_head(
                    $label, $column, 'section', true, $switch_dir, $crit, $search_method,
                        (($column == $sort) ? "$dir " : '').'txp-list-col-'.$column
                );
            }

            $contentBlock .= tr($thead).
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
                    ), array(
                        'title'      => gTxt('toggle_yes_no'),
                        'aria-label' => gTxt('toggle_yes_no'),
                    ));

                    $sec_in_rss = asyncHref(yes_no($sec_in_rss), array(
                        'step'     => 'section_toggle_option',
                        'thing'    => $sec_name,
                        'property' => 'in_rss',
                    ), array(
                        'title'      => gTxt('toggle_yes_no'),
                        'aria-label' => gTxt('toggle_yes_no'),
                    ));

                    $sec_searchable = asyncHref(yes_no($sec_searchable), array(
                        'step'     => 'section_toggle_option',
                        'thing'    => $sec_name,
                        'property' => 'searchable',
                    ), array(
                        'title'      => gTxt('toggle_yes_no'),
                        'aria-label' => gTxt('toggle_yes_no'),
                    ));

                    if ($sec_article_count > 0) {
                        $articles = href($sec_article_count, array(
                            'event'         => 'list',
                            'search_method' => 'section',
                            'crit'          => '"'.$sec_name.'"',
                        ), array(
                            'title'      => gTxt('article_count', array('{num}' => $sec_article_count)),
                            'aria-label' => gTxt('article_count', array('{num}' => $sec_article_count)),
                        ));
                    } else {
                        $articles = 0;
                    }
                }

                $has_dev_skin = !empty($sec_dev_skin) && $sec_dev_skin !== $sec_skin;
                !empty($sec_dev_skin) or $sec_dev_skin = $sec_skin;
                !empty($sec_dev_page) or $sec_dev_page = $sec_page;
                !empty($sec_dev_css) or $sec_dev_css = $sec_css;

                $in_dev = false;

                foreach (array('page', 'css') as $item) {
                    $all_items = $item === 'page' ? $all_pages : $all_styles;
                    $sec_item = ${"sec_$item"};
                    $sec_dev_item = ${"sec_dev_$item"};

                    $missing = $sec_dev_item && isset($all_items[$sec_dev_skin]) && !in_array($sec_dev_item, $all_items[$sec_dev_skin]);
                    $replaced = $dev_preview && ($has_dev_skin && $sec_dev_item || $sec_item != $sec_dev_item || $sec_dev_item && $missing) ? 'disabled' : false;
                    $dev_set = $dev_set || $replaced;
                    $in_dev = $in_dev || $replaced;

                    ${"sec_$item"} = ($sec_item ? tag(href(txpspecialchars($sec_item), array(
                        'event' => $item,
                        'name'  => $sec_item,
                        'skin'  => $sec_skin,
                    ), array(
                        'title'      => gTxt('edit'),
                        'aria-label' => gTxt('edit'),
                    )
                    ), $replaced ? 'span' : null, $replaced ? array('class' => 'secondary-text') : '') : tag(gTxt('none'), 'span', array('class' => 'disabled'))).
                    ($replaced ?
                        n.'<hr class="secondary" />'.n.
                        href(txpspecialchars($sec_dev_item), array(
                            'event' => $item,
                            'name'  => $sec_dev_item,
                            'skin'  => $sec_dev_skin,
                        ), array(
                            'title'      => gTxt('edit'),
                            'aria-label' => gTxt('edit'),
                        )).
                        ($missing ? sp.tag(gTxt('status_missing'), 'small', array('class' => 'alert-block alert-pill error')) : '')
                    : '');
                }

                $replaced = $dev_preview && ($sec_skin != $sec_dev_skin) ? 'disabled' : false;
                $dev_set = $dev_set || $replaced;
                $in_dev = $in_dev || $replaced;

                $contentBlock .= tr(
                    td(
                        fInput('checkbox', array('name' => 'selected[]', 'checked' => $sec_skin == $skin || $sec_dev_skin == $skin), $sec_name), '', 'txp-list-col-multi-edit'
                    ).
                    hCell(
                        href(
                            txpspecialchars($sec_name), $edit_url, array(
                                'title'      => gTxt('edit'),
                                'aria-label' => gTxt('edit'),
                            )
                        ).
                        span(
                            sp.span('&#124;', array('role' => 'separator')).
                            sp.href(gTxt('view'), pagelinkurl(array('s' => $sec_name), null, $sec_permlink_mode)),
                            array('class' => 'txp-option-link')
                        ).
                        ($in_dev ? n.'<hr class="secondary" />'.n.tag(gTxt('dev_theme'), 'small', array('class' => 'alert-block alert-pill warning')) : ''), '', array(
                            'class' => 'txp-list-col-name',
                            'scope' => 'row',
                        )
                    ).
                    td(
                        txpspecialchars($sec_title), '', 'txp-list-col-title'
                    ).
                    td(
                        tag($sec_skin, $replaced ? 'span' : null, $replaced ? array('class' => 'secondary-text') : '').($replaced ? n.'<hr class="secondary" />'.n.$sec_dev_skin : ''),
                        '', 'txp-list-col-skin'
                    ).
                    td(
                        $sec_page, '', 'txp-list-col-page'
                    ).
                    td(
                        $sec_css, '', 'txp-list-col-style'
                    ).
                    td(
                        $sec_permlink_mode ? gTxt($sec_permlink_mode) : '<span class="secondary-text">'.gTxt(get_pref('permlink_mode')).'</span>', '', 'txp-list-col-permlink_mode'
                    ).
                    td(
                        $sec_on_frontpage, '', 'txp-list-col-on_frontpage'
                    ).
                    td(
                        $sec_in_rss, '', 'txp-list-col-in_rss'
                    ).
                    td(
                        $sec_searchable, '', 'txp-list-col-searchable'
                    ).
                    td(
                        $articles, '', 'txp-list-col-article_count'
                    ),
                    array('id' => 'txp_section_'.$sec_name)
                );
            }

            $disabled = $dev_set ? array() : array('switchdevlive');

            $contentBlock .= n.tag_end('tbody').
                n.tag_end('table').
                n.tag_end('div'). // End of .txp-listtables.
                section_multiedit_form($page, $sort, $dir, $crit, $search_method, $disabled).
                tInput().
                n.tag_end('form');
        }
    }

    $pageBlock = $paginator->render().
        nav_form('section', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit);

    $table = new \Textpattern\Admin\Table($event);
    echo $table->render(compact('total', 'crit') + array('heading' => 'tab_sections'), $searchBlock, $createBlock, $contentBlock, $pageBlock);
}

/**
 * Renders and outputs the section editor panel.
 */

function section_edit()
{
    global $event, $step, $all_skins, $all_pages, $all_styles;

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
            "skin, page, css, on_frontpage, in_rss, searchable",
            'txp_section',
            "name = 'default'"
        );

        if ($rs) {
            $rs['name'] = $rs['title'] = $rs['description'] = $rs['permlink_mode'] = '';
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
                fInput('text', 'name', $sec_name, '', '', '', INPUT_REGULAR, '', 'section_name', false, true),
                'section_name', '', array('class' => 'txp-form-field edit-section-name')
            ).
            inputLabel(
                'section_title',
                fInput('text', 'title', $sec_title, '', '', '', INPUT_REGULAR, '', 'section_title'),
                'section_longtitle', '', array('class' => 'txp-form-field edit-section-longtitle')
            );
    }

    $pageSelect = selectInput(array('name' => 'section_page', 'required' => false), array(), '', '', '', 'section_page');
    $styleSelect = selectInput(array('name' => 'css', 'required' => false), array(), '', '', '', 'section_css');
    $json_page = json_encode($all_pages, TEXTPATTERN_JSON);
    $json_style = json_encode($all_styles, TEXTPATTERN_JSON);

    $out[] =
        inputLabel(
            'section_skin',
            selectInput('skin', $all_skins, $sec_skin, '', '', 'section_skin'),
            'uses_skin',
            'section_uses_skin',
            array('class' => 'txp-form-field edit-section-uses-skin')
        ).
        inputLabel(
            'section_page',
            $pageSelect,
            'uses_page',
            'section_uses_page',
            array('class' => 'txp-form-field edit-section-uses-page')
        ).
        inputLabel(
            'section_css',
            $styleSelect,
            'uses_style',
            'section_uses_css',
            array('class' => 'txp-form-field edit-section-uses-css')
        ).
        inputLabel(
            'permlink_mode',
            permlinkmodes('permlink_mode', $is_default_section ? get_pref('permlink_mode') : $sec_permlink_mode, $is_default_section ? false : array('' => gTxt('default'))),
            'permlink_mode',
            'permlink_mode',
            array('class' => 'txp-form-field edit-section-permlink-mode')
        ).
        script_js(<<<EOJS
var skin_page = {$json_page};
var skin_style = {$json_style};
var page_sel = '{$sec_page}';
var style_sel = '{$sec_css}';
EOJS
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
        'skin',
        'description',
        'old_name',
        'section_page',
        'css',
        'permlink_mode',
    )));

    if (empty($in['title'])) {
        $in['title'] = $in['name'];
    }

    // Prevent non-URL characters on section names.
    $mbstrings = extension_loaded('mbstrings');
    $in['name'] = $mbstrings ?
        mb_strtolower(sanitizeForUrl($in['name']), 'UTF-8') :
        strtolower(sanitizeForUrl($in['name']));

    extract($in);

    $in = doSlash($in);
    extract($in, EXTR_PREFIX_ALL, 'safe');
    $lower_name = $mbstrings ?
        mb_strtolower($old_name, 'UTF-8') :
        strtolower($old_name);

    if ($name != $lower_name) {
        if (safe_field("name", 'txp_section', "name = '$safe_name'")) {
            // Invalid input. Halt all further processing (e.g. plugin event
            // handlers).
            $message = array(gTxt('section_name_already_exists', array('{name}' => $name)), E_ERROR);
            sec_section_list($message);

            return;
        }
    }

    $ok = false;

    if ($name == 'default') {
        $on_frontpage = $in_rss = $searchable = 0;

        $ok = safe_update('txp_section', "skin = '$safe_skin', page = '$safe_section_page', css = '$safe_css', description = '$safe_description'", "name = 'default'");
        set_pref('permlink_mode', $permlink_mode);
    } elseif ($name) {
        extract(array_map('assert_int', psa(array('on_frontpage', 'in_rss', 'searchable'))));

        if ($safe_old_name) {
            $ok = safe_update('txp_section', "
                name           = '$safe_name',
                title          = '$safe_title',
                skin           = '$safe_skin',
                page           = '$safe_section_page',
                css            = '$safe_css',
                description    = '$safe_description',
                permlink_mode  = '$safe_permlink_mode',
                on_frontpage   = '$on_frontpage',
                in_rss         = '$in_rss',
                searchable     = '$searchable'
                ", "name = '$safe_old_name'");

            // Manually maintain referential integrity.
            if ($ok) {
                $ok = safe_update('textpattern', "Section = '$safe_name'", "Section = '$safe_old_name'");
            }
        } else {
            $ok = safe_insert('txp_section', "
                name         = '$safe_name',
                title        = '$safe_title',
                skin         = '$safe_skin',
                page         = '$safe_section_page',
                css          = '$safe_css',
                description  = '$safe_description',
                permlink_mode  = '$safe_permlink_mode',
                on_frontpage = '$on_frontpage',
                in_rss       = '$in_rss',
                searchable   = '$searchable'");
        }
    }

    if ($ok) {
        if ($name != $lower_name && $lower_name == get_pref('default_section')) {
            set_pref('default_section', $name, 'section', PREF_HIDDEN);
        }
        update_lastmod('section_saved', compact('name', 'title', 'section_page', 'css', 'description', 'on_frontpage', 'in_rss', 'searchable', 'permlink_mode'));
        Txp::get('Textpattern\Skin\Skin')->setEditing($safe_skin);
    }

    if ($ok) {
        sec_section_list(gTxt(($safe_old_name ? 'section_updated' : 'section_created'), array('{name}' => $name)), true);
    } else {
        sec_section_list(array(gTxt('section_save_failed'), E_ERROR));
    }
}

/**
 * Changes and saves the pageby value.
 */

function section_change_pageby()
{
    Txp::get('\Textpattern\Admin\Paginator')->change();
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
    extract(psa(array('default_section')));

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
    global $txp_sections;

    $val = get_pref('default_section');
    $vals = array();

    foreach ($txp_sections as $name => $row) {
        $name == 'default' or $vals[$name] = $row['title'];
    }

    return selectInput(array(
        'name' => 'default_section', 'class' => 'txp-async-update'
    ), $vals, $val, false, true, 'default_section');
}

/**
 * Processes delete actions sent using the multi-edit form.
 */

function section_delete()
{
    global $txp_sections;

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
        foreach ($sections as $section) {
            unset($txp_sections[$section]);
        }

        callback_event('sections_deleted', '', 0, $sections);
        $message = gTxt('section_deleted', array('{name}' => join(', ', $sections)));
    }

    if ($sectionsNotDeleted) {
        $severity = ($message) ? E_WARNING : E_ERROR;
        $message = array(($message ? $message.n : '').gTxt('section_delete_failure', array('{name}' => join(', ', $sectionsNotDeleted))), $severity);
    }

    sec_section_list($message);
}

/**
 * Processes theme preview actions.
 */

function section_set_theme($type = 'dev_skin')
{
    global $all_skins, $all_pages, $all_styles;

    $skin = gps('skin');
    $message = '';

    if (isset($all_skins[$skin]) && has_privs('skin.edit')) {
        safe_update(
            'txp_section',
            "$type = '".doSlash($skin)."'",
            $type == 'dev_skin' ? '1' : 'page IN ('.join(',', quote_list($all_pages[$skin])).') AND css IN ('.join(',', quote_list($all_styles[$skin])).')'
        );
        $message = gTxt($type == 'dev_skin' ? 'dev_theme' : 'live_theme').': '.txpspecialchars($all_skins[$skin]);

        if ($type == 'dev_skin') {
            Txp::get('Textpattern\Skin\Skin')->setName($skin)->setEditing();
        }
    }

    script_js(<<<EOS
if (typeof window.history.replaceState == 'function') {history.replaceState({}, '', '?event=section')}
EOS
    , false);
    sec_section_list($message, true);
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

function section_multiedit_form($page, $sort, $dir, $crit, $search_method, $disabled = array())
{
    global $all_skins, $all_pages, $all_styles, $step;

    $json_page = json_encode($all_pages, TEXTPATTERN_JSON);
    $json_style = json_encode($all_styles, TEXTPATTERN_JSON);

    $themeSelect = inputLabel(
        'multiedit_skin',
        selectInput('skin', $all_skins, gps('skin'), false, '', 'multiedit_skin'),
        'skin', '', array('class' => 'multi-option multi-step'), ''
    );

    $pageSelect = inputLabel(
        'multiedit_page',
        selectInput('section_page', array(), '', '', '', 'multiedit_page'),
        'page', '', array('class' => 'multi-option multi-step'), ''
    );

    $styleSelect = inputLabel(
        'multiedit_css',
        selectInput('css', array(), '', '', '', 'multiedit_css'),
        'css', '', array('class' => 'multi-option multi-step'), ''
    );

    $devThemeSelect = inputLabel(
        'multiedit_skin',
        selectInput('dev_skin', $all_skins, '', false, '', 'multiedit_dev_skin'),
        'skin', '', array('class' => 'multi-option multi-step'), ''
    );

    $devPageSelect = inputLabel(
        'multiedit_page',
        selectInput('dev_page', array(), '', '', '', 'multiedit_dev_page'),
        'page', '', array('class' => 'multi-option multi-step'), ''
    );

    $devStyleSelect = inputLabel(
        'multiedit_css',
        selectInput('dev_css', array(), '', '', '', 'multiedit_dev_css'),
        'css', '', array('class' => 'multi-option multi-step'), ''
    );

    $dev_preview = get_pref('enable_dev_preview') && has_privs('skin.edit');

    $methods = array(
        'changepagestyle' => array(
            'label' => gTxt('change_page_style'),
            'html'  => (!$dev_preview ?
                hInput('live_theme', 1) :
                inputLabel('dev_theme',
                    checkbox2('dev_theme', 1, 0, 'dev_theme'),
                    'dev_theme', '', array('class' => 'multi-option multi-step'), ''
                ) . inputLabel('live_theme',
                    checkbox2('live_theme', 0, 0, 'live_theme'),
                    'live_theme', '', array('class' => 'multi-option multi-step'), ''
                )
            ) . $themeSelect . $pageSelect . $styleSelect
        ),
        'switchdevlive' => array(
            'label' => gTxt('switch_dev_live'),
            'html'  => radioSet(array(
                0 => gTxt('live_to_dev'),
                1 => gTxt('dev_to_live'),
                ), 'switch_dev_live', 0),
        ),
        'permlinkmode' => array(
            'label' => gTxt('permlink_mode'),
            'html'  => permlinkmodes('permlink_mode', '', array('' => gTxt('default'))),
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

    foreach ($disabled as $method) {
        unset($methods[$method]);
    }

    $script = <<<EOJS
var skin_page = {$json_page};
var skin_style = {$json_style};
var page_sel = null;
var style_sel = null;
EOJS;

    if ($step == 'section_select_skin') {
        $script .= <<<EOJS
$(function() {
//    $('#select_all').click();
    $('[name="edit_method"]').val('changepagestyle').change();
    var skin = $('#multiedit_skin');
    var selected = skin.find('option[selected]').val();
    skin.val(selected || '').change();
});
EOJS;
    }
    return multi_edit($methods, 'section', 'section_multi_edit', $page, $sort, $dir, $crit, $search_method).
    script_js($script, false);
}

/**
 * Processes multi-edit actions.
 */

function section_multi_edit()
{
    global $txp_user, $all_skins, $all_pages, $all_styles;

    extract(psa(array(
        'edit_method',
        'selected',
    )));

    if (!$selected || !is_array($selected)) {
        return sec_section_list();
    }

    $nameVal = array();

    switch ($edit_method) {
        case 'delete':
            return section_delete();
            break;
        case 'changepagestyle':
            if (ps('live_theme')) {
                $nameVal += array(
                    'skin' => ps('skin'),
                    'page' => ps('section_page'),
                    'css'  => ps('css'),
                );
            }

            if (ps('dev_theme')) {
                $nameVal += array(
                    'dev_skin' => ps('skin'),
                    'dev_page' => ps('section_page'),
                    'dev_css'  => ps('css'),
                );
            }

            break;
        case 'switchdevlive':
            $nameVal['switch_dev_live'] = (int) ps('switch_dev_live');
            break;
        case 'permlinkmode':
            $nameVal['permlink_mode'] = (string) ps('permlink_mode');
            break;
        case 'changeonfrontpage':
            $nameVal['on_frontpage'] = (int) ps('on_frontpage');
            break;
        case 'changesyndicate':
            $nameVal['in_rss'] = (int) ps('in_rss');
            break;
        case 'changesearchable':
            $nameVal['searchable'] = (int) ps('searchable');
            break;
    }

    $setskin = "IF(dev_skin > '', dev_skin, skin)";
    $setpage = "IF(dev_page > '', dev_page, page)";
    $setcss = "IF(dev_css > '', dev_css, css)";

    $filter = array("name IN (".join(',', quote_list($selected)).")");
    $message = '';

    if ($edit_method === 'changepagestyle' && !empty($nameVal['skin'])) {
        $skin = $nameVal['skin'];

        if (empty($nameVal['page'])) {
            $filter[] = empty($all_pages[$skin]) ?
                '0' :
                "page IN (".join(',', quote_list($all_pages[$skin])).")";
        }

        if (empty($nameVal['css'])) {
            $filter[] = empty($all_styles[$skin]) ?
                '0' :
                "css IN (".join(',', quote_list($all_styles[$skin])).")";
        }
    } elseif ($edit_method === 'switchdevlive' && empty($nameVal['switch_dev_live'])) {
        $skinset = array();

        foreach ($all_skins as $skin => $title) {
            $skinset[] = "$setskin = '".doSlash($skin)."' AND ($setpage = '' OR ".
            (empty($all_pages[$skin]) ?
                '0' :
                "$setpage IN (".join(',', quote_list($all_pages[$skin]))."))"
            )." AND ($setcss = '' OR ".
            (empty($all_styles[$skin]) ?
                '0' :
                "$setcss IN (".join(',', quote_list($all_styles[$skin]))."))"
            );
        }

        $filter[] = '('.implode(' OR ', $skinset).')';
    }

    $sections = safe_column(
        "name",
        'txp_section',
        implode(' AND ', $filter)
    );

    if ($nameVal && $sections) {
        if ($edit_method == 'switchdevlive') {
            $set = ($nameVal['switch_dev_live'] ? '' :
                "skin = $setskin,
                page = $setpage,
                css = $setcss, "
            )."dev_skin = '', dev_page = '', dev_css = ''";
        } elseif ($edit_method == 'permlinkmode') {
            $set = "permlink_mode = IF(name='default', '', '".doSlash($nameVal['permlink_mode'])."')";

            if ($nameVal['permlink_mode'] && in_array('default', $sections)) {
                set_pref('permlink_mode', $nameVal['permlink_mode']);
            }
        } else {
            $in = array();

            foreach ($nameVal as $key => $val) {
                if ((string)$val != '*') {
                    $in[] = "{$key} = '".doSlash($val)."'";
                }
            }

            $set = implode(',', $in);
        }

        if ($set &&
            safe_update(
                'txp_section',
                $set,
                "name IN (".join(',', quote_list($sections)).")"
            )
        ) {
            $message = gTxt('section_updated', array('{name}' => join(', ', $sections)));

            if ($edit_method === 'changepagestyle') {
                Txp::get('Textpattern\Skin\Skin')->setEditing(doSlash($nameVal['skin']));
            }
        }
    } else {
        $message = array(gTxt('section_save_failed'), E_ERROR);
    }

    sec_section_list($message, $nameVal && $sections);
}

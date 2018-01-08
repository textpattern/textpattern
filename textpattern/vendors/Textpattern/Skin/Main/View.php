<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2018 The Textpattern Development Team
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
 * Main
 *
 * Manages skins and their assets.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin\Main {

    class View
    {

        /**
         * {@inheritdoc}
         */

        private $model;

        /**
         * Constructor.
         *
         * @param object $model Model instance.
         */

        public function __construct(\Textpattern\Skin\Main\Model $model)
        {
            $this->model = $model;
        }

        /**
         * The main panel listing all skins.
         *
         * @param mixed $message The activity message
         */

        public function output()
        {
            $var = $this->model->getMessage();

            return $this->main($this->model->getMessage());
        }

        /**
         * Skins list.
         *
         * @param  mixed $message The activity message
         * @return html
         */

        function main($message = '')
        {
            global $event;

            pagetop(gTxt('tab_skins'), $message);

            extract(gpsa(array(
                'page',
                'sort',
                'dir',
                'crit',
                'search_method',
            )));

            if ($sort === '') {
                $sort = get_pref('skin_sort_column', 'name');
            } else {
                $sortOpts = array(
                    'title',
                    'version',
                    'author',
                    'section_count',
                    'page_count',
                    'form_count',
                    'css_count',
                    'name',
                );

                in_array($sort, $sortOpts) or $sort = 'name';

                set_pref('skin_sort_column', $sort, 'skin', 2, '', 0, PREF_PRIVATE);
            }

            if ($dir === '') {
                $dir = get_pref('skin_sort_dir', 'desc');
            } else {
                $dir = ($dir == 'asc') ? 'asc' : 'desc';

                set_pref('skin_sort_dir', $dir, 'skin', 2, '', 0, PREF_PRIVATE);
            }

            $sortSQL = $sort.' '.$dir;
            $switchDir = ($dir == 'desc') ? 'asc' : 'desc';

            $search = new \Textpattern\Search\Filter(
                $event,
                array(
                    'name' => array(
                        'column' => 'txp_skin.name',
                        'label'  => gTxt('name'),
                    ),
                    'title' => array(
                        'column' => 'txp_skin.title',
                        'label'  => gTxt('title'),
                    ),
                    'author' => array(
                        'column' => 'txp_skin.author',
                        'label'  => gTxt('author'),
                    ),
                )
            );

            list($criteria, $crit, $search_method) = $search->getFilter();

            $searchRenderOpts = array('placeholder' => 'search_skins');
            $total = Model::getSearchCount($criteria);

            echo n.'<div class="txp-layout">'
                .n.tag(
                    hed(gTxt('tab_skins'), 1, array('class' => 'txp-heading')),
                    'div',
                    array('class' => 'txp-layout-4col-alt')
                );

            $searchBlock = n.tag(
                $search->renderForm('skin', $searchRenderOpts),
                'div',
                array(
                    'class' => 'txp-layout-4col-3span',
                    'id'    => $event.'_control',
                )
            );

            $createBlock = has_privs('skin.edit') ? static::createBlock() : '';

            $contentBlockStart = n.tag_start(
                'div',
                array(
                    'class' => 'txp-layout-1col',
                    'id'    => $event.'_container',
                )
            );

            echo $searchBlock
                .$contentBlockStart
                .$createBlock;

            if ($total < 1) {
                if ($criteria != 1) {
                    echo graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_results_found'),
                        array('class' => 'alert-block information')
                    );
                } else {
                    echo graf(
                        span(null, array('class' => 'ui-icon ui-icon-info')).' '.
                        gTxt('no_skin_recorded'),
                        array('class' => 'alert-block error')
                    );
                }

                echo n.tag_end('div') // End of .txp-layout-1col.
                    .n.'</div>';      // End of .txp-layout.

                return;
            }

            $paginator = new \Textpattern\Admin\Paginator();
            $limit = $paginator->getLimit();

            list($page, $offset, $numPages) = pager($total, $limit, $page);

            $rs = Model::getAllData($criteria, $sortSQL, $offset, $limit);

            if ($rs) {
                echo n.tag_start('form', array(
                        'class'  => 'multi_edit_form',
                        'id'     => 'skin_form',
                        'name'   => 'longform',
                        'method' => 'post',
                        'action' => 'index.php',
                    ))
                    .n.tag_start('div', array('class' => 'txp-listtables'))
                    .n.tag_start('table', array('class' => 'txp-list'))
                    .n.tag_start('thead');

                $ths = hCell(
                    fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'),
                    '',
                    ' class="txp-list-col-multi-edit" scope="col" title="'.gTxt('toggle_all_selected').'"'
                );

                $thIds = array(
                    'name'          => 'name',
                    'title'         => 'title',
                    'version'       => 'version',
                    'author'        => 'author',
                    'section_count' => 'tab_sections',
                    'page_count'    => 'tab_pages',
                    'form_count'    => 'tab_forms',
                    'css_count'     => 'tab_style',
                );

                foreach ($thIds as $thId => $thVal) {
                    $thClass = 'txp-list-col-'.$thId
                              .($thId == $sort ? ' '.$dir : '')
                              .($thVal !== $thId ? ' skin_detail' : '');

                    $ths .= column_head($thVal, $thId, 'skin', true, $switchDir, $crit, $search_method, $thClass);
                }

                echo tr($ths)
                    .n.tag_end('thead')
                    .n.tag_start('tbody');

                while ($a = nextRow($rs)) {
                    extract($a, EXTR_PREFIX_ALL, 'skin');

                    $editUrl = array(
                        'event'         => 'skin',
                        'step'          => 'edit',
                        'name'          => $skin_name,
                        'sort'          => $sort,
                        'dir'           => $dir,
                        'page'          => $page,
                        'search_method' => $search_method,
                        'crit'          => $crit,
                    );

                    $tdAuthor = txpspecialchars($skin_author);

                    $skin_author_uri ? $tdAuthor = href($tdAuthor, $skin_author_uri) : '';

                    $tds = td(fInput('checkbox', 'selected[]', $skin_name), '', 'txp-list-col-multi-edit')
                        .hCell(
                            href(txpspecialchars($skin_name), $editUrl, array('title' => gTxt('edit'))),
                            '',
                            array(
                                'scope' => 'row',
                                'class' => 'txp-list-col-name',
                            )
                        )
                        .td(txpspecialchars($skin_title), '', 'txp-list-col-title')
                        .td(txpspecialchars($skin_version), '', 'txp-list-col-version')
                        .td($tdAuthor, '', 'txp-list-col-author');

                    $countNames = array('section', 'page', 'form', 'css');

                    foreach ($countNames as $name) {
                        if (${'skin_'.$name.'_count'} > 0) {
                            if ($name === 'section') {
                                $linkParams = array(
                                    'event'         => 'section',
                                    'search_method' => 'skin',
                                    'crit'          => '"'.$skin_name.'"',
                                );
                            } else {
                                $linkParams = array(
                                    'event' => $name,
                                    'skin'  => $skin_name,
                                );
                            }

                            $tdVal = href(
                                ${'skin_'.$name.'_count'},
                                $linkParams,
                                array(
                                    'title' => gTxt(
                                        'skin_count_'.$name,
                                        array('{num}' => ${'skin_'.$name.'_count'})
                                    )
                                )
                            );
                        } else {
                            $tdVal = 0;
                        }

                        $tds .= td($tdVal, '', 'txp-list-col-'.$name.'_count');
                    }

                    echo tr($tds, array('id' => 'txp_skin_'.$skin_name));
                }

                echo n.tag_end('tbody')
                    .n.tag_end('table')
                    .n.tag_end('div') // End of .txp-listtables.
                    .n.static::multiEditForm($page, $sort, $dir, $crit, $search_method)
                    .n.tInput()
                    .n.tag_end('form')
                    .n.tag_start(
                        'div',
                        array(
                            'class' => 'txp-navigation',
                            'id'    => $event.'_navigation',
                        )
                    )
                    .$paginator->render()
                    .nav_form('skin', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit)
                    .n.tag_end('div');
            }

            echo n.tag_end('div') // End of .txp-layout-1col.
                .n.'</div>'; // End of .txp-layout.
        }

        /**
         * Renders the skin import form.
         *
         * @return html The form or a message if no new skin directory is found.
         */

        public static function importForm()
        {
            $new = Model::getNewDirectories();

            if ($new) {
                return n
                    .tag_start('form', array(
                        'id'     => 'skin_import_form',
                        'name'   => 'skin_import_form',
                        'method' => 'post',
                        'action' => 'index.php',
                    ))
                    .tag(gTxt('import_skin'), 'label', array('for' => 'skin_import'))
                    .popHelp('skin_import')
                    .selectInput('skins', $new, '', true, false, 'skins')
                    .eInput('skin')
                    .sInput('import')
                    .fInput('submit', '', gTxt('upload'))
                    .n
                    .tag_end('form');
            }
        }

        /**
         * Renders button to create a new skin.
         *
         * @return html Link.
         */

        public static function CreateButton()
        {
            return sLink('skin', 'edit', gTxt('create_skin'), 'txp-button');
        }

        /**
         * Renders the .txp-control-panel div.
         *
         * @return html div containing the 'Create' button and the import form..
         */

        public static function createBlock()
        {
            return tag(
                static::createButton()
                .static::importForm(),
                'div',
                array('class' => 'txp-control-panel')
            );
        }

        /**
         * Renders the edit form.
         *
         * @return html Form.
         */

        public function edit($message = '')
        {
            global $step;

            require_privs('skin.edit');

            $message ? pagetop(gTxt('tab_skins'), $message) : '';

            extract(gpsa(array(
                'page',
                'sort',
                'dir',
                'crit',
                'search_method',
                'name',
            )));

            $fields = array('name', 'title', 'version', 'description', 'author', 'author_uri');

            if ($name) {
                $rs = $this->model->setName($name)->getRow();

                if (!$rs) {
                    return $this->main();
                }

                $caption = gTxt('edit_skin');
                $extraAction = href(
                    '<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'),
                    '#',
                    array(
                        'class'     => 'txp-clone',
                        'data-form' => 'skin_form',
                    )
                );
            } else {
                $rs = array_fill_keys($fields, '');
                $caption = gTxt('create_skin');
                $extraAction = '';
            }

            extract($rs, EXTR_PREFIX_ALL, 'skin');
            pagetop(gTxt('tab_skins'));

            $content = hed($caption, 2);

            foreach ($fields as $field) {
                $current = ${'skin_'.$field};

                if ($field === 'description') {
                    $input = text_area($field, 0, 0, $current, 'skin_'.$field);
                } elseif ($field === 'name') {
                    $input = '<input type="text" value="'.$current.'" id="skin_'.$field.'" name="'.$field.'" size="'.INPUT_REGULAR.'" maxlength="63" required />';
                } else {
                    $type = ($field === 'author_uri') ? 'url' : 'text';
                    $input = fInput($type, $field, $current, '', '', '', INPUT_REGULAR, '', 'skin_'.$field);
                }

                $content .= inputLabel('skin_'.$field, $input, 'skin_'.$field);
            }

            $content .= pluggable_ui('skin_ui', 'extend_detail_form', '', $rs)
                .graf(
                    $extraAction.
                    sLink('skin', '', gTxt('cancel'), 'txp-button')
                    .fInput('submit', '', gTxt('save'), 'publish'),
                    array('class' => 'txp-edit-actions')
                )
                .eInput('skin')
                .sInput('save')
                .hInput('old_name', $skin_name)
                .hInput('old_title', $skin_title)
                .hInput('search_method', $search_method)
                .hInput('crit', $crit)
                .hInput('page', $page)
                .hInput('sort', $sort)
                .hInput('dir', $dir);

            echo form($content, '', '', 'post', 'txp-edit', '', 'skin_form');
        }

        /**
         * Renders a multi-edit form widget.
         *
         * @param  int    $page         The page number
         * @param  string $sort         The current sorting value
         * @param  string $dir          The current sorting direction
         * @param  string $crit         The current search criteria
         * @param  string $search_method The current search method
         * @return string HTML
         */

        public function multiEditForm($page, $sort, $dir, $crit, $search_method)
        {
            $clean = checkbox('clean', 1, true, 0, 'clean')
                    .tag(gtxt('remove_extra_templates'), 'label', array('for' => 'clean'))
                    .popHelp('remove_extra_templates');

            $methods = array(
                'import'    => array('label' => gTxt('import'), 'html' => $clean),
                'duplicate' => gTxt('duplicate'),
                'export'    => array('label' => gTxt('export'), 'html' => $clean),
                'delete'    => gTxt('delete'),
            );

            return multi_edit($methods, 'skin', 'multiEdit', $page, $sort, $dir, $crit, $search_method);
        }
    }
}

<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2025 The Textpattern Development Team
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
 * Category panel.
 *
 * @package Admin\Category
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'category') {
    require_privs('category');

    $available_steps = array(
        'cat_category_list'      => false,
        'cat_category_multiedit' => true,
        'cat_article_create'     => true,
        'cat_image_create'       => true,
        'cat_file_create'        => true,
        'cat_link_create'        => true,
        'cat_article_save'       => true,
        'cat_image_save'         => true,
        'cat_file_save'          => true,
        'cat_link_save'          => true,
        'cat_article_edit'       => false,
        'cat_image_edit'         => false,
        'cat_file_edit'          => false,
        'cat_link_edit'          => false,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        cat_category_list();
    }
}

/**
 * Outputs the main panel listing all categories.
 *
 * @param string|array $message The activity message
 */

function cat_category_list($message = "")
{
    pagetop(gTxt('categories'), $message);
    $out = array(n . '<div class="txp-layout">' .
        n . tag(
            hed(gTxt('tab_organise'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        ),
        n . tag(cat_article_list(), 'section', array(
                'class' => 'txp-layout-4col',
                'id'    => 'categories_article',
            )),
        n . tag(cat_image_list(), 'section', array(
                'class' => 'txp-layout-4col',
                'id'    => 'categories_image',
            )),
        n . tag(cat_file_list(), 'section', array(
                'class' => 'txp-layout-4col',
                'id'    => 'categories_file',
            )),
        n . tag(cat_link_list(), 'section', array(
                'class' => 'txp-layout-4col',
                'id'    => 'categories_link',
            )),
        n . '</div>', // End of .txp-layout.
        script_js(<<<EOS
    $('.category-tree').txpMultiEditForm({
        'row' : 'p',
        'highlighted' : 'p'
    });
EOS
        , false, true),
    );
    echo join(n, $out);
}

/**
 * Renders a list of article categories.
 */

function cat_article_list()
{
    return cat_event_category_list('article');
}

/**
 * Processes a saved editor form and creates an article category.
 */

function cat_article_create()
{
    return cat_event_category_create('article');
}

/**
 * Renders an editor form for article categories.
 */

function cat_article_edit()
{
    return cat_event_category_edit('article');
}

/**
 * Saves an article category.
 */

function cat_article_save()
{
    return cat_event_category_save('article', 'textpattern');
}

/**
 * Renders a list of parent category options.
 *
 * @return string HTML &lt;select&gt; input
 */

function cat_parent_pop($name, $type, $id)
{
    if ($id) {
        $id = assert_int($id);
        list($lft, $rgt) = array_values(safe_row("lft, rgt", 'txp_category', "id = '$id'"));

        $rs = getTree('root', $type, "lft NOT BETWEEN $lft AND $rgt");
    } else {
        $rs = getTree('root', $type);
    }

    if ($rs) {
        return array(treeSelectInput('parent', $rs, $name, 'category_parent'), true);
    }

    return array(gTxt('no_other_categories_exist'), false);
}

/**
 * Renders a list of link categories.
 */

function cat_link_list()
{
    return cat_event_category_list('link');
}

/**
 * Processes a saved editor form and creates a link category.
 */

function cat_link_create()
{
    return cat_event_category_create('link');
}

/**
 * Renders an editor form for link categories.
 */

function cat_link_edit()
{
    return cat_event_category_edit('link');
}

/**
 * Saves a link category.
 */

function cat_link_save()
{
    return cat_event_category_save('link', 'txp_link');
}

/**
 * Renders a list of image categories.
 */

function cat_image_list()
{
    return cat_event_category_list('image');
}

/**
 * Processes a saved editor form and creates an image category.
 */

function cat_image_create()
{
    return cat_event_category_create('image');
}

/**
 * Renders an editor form for image categories.
 */

function cat_image_edit()
{
    return cat_event_category_edit('image');
}

/**
 * Saves an image category.
 */

function cat_image_save()
{
    return cat_event_category_save('image', 'txp_image');
}

/**
 * Renders a multi-edit form.
 *
 * @param  string $area  Type of category
 * @param  array  $array Additional HTML added to the form
 * @return string HTML
 */

function cat_article_multiedit_form($area, $array)
{
    $rs = getTree('root', $area);
    $categories = $rs ? treeSelectInput('new_parent', $rs, '') : '';

    $methods = array(
        'changeparent' => array(
            'label' => gTxt('changeparent'),
            'html'  => $categories,
        ),
        'deleteforce'  => gTxt('deleteforce'),
        'delete'       => gTxt('delete'),
    );

    if ($array) {
        return
            form(
                join('', $array) .
                hInput('type', $area) .
                multi_edit($methods, 'category', 'cat_category_multiedit', '', '', '', '', '', $area), '', '', 'post', 'category-tree', '', 'category_' . $area . '_form'
            );
    }

    return;
}

/**
 * Processes multi-edit actions.
 */

function cat_category_multiedit()
{
    $type = ps('type');
    $method = ps('edit_method');
    $things = ps('selected');

    if (is_array($things) and $things and in_array($type, array('article', 'image', 'link', 'file'))) {
        // Fetch selected items and remove bogus (false) entries to prevent SQL syntax errors.
        $things = array_map('assert_int', $things);
        $things = array_filter($things);
        $message = false;

        if ($method == 'delete' || $method == 'deleteforce') {
            if ($type === 'article') {
                $used = "name NOT IN (SELECT category1 FROM " . safe_pfx('textpattern') . ")
                    AND name NOT IN (SELECT category2 FROM " . safe_pfx('textpattern') . ")";
            } else {
                $used = "name NOT IN (SELECT category FROM " . safe_pfx('txp_' . $type) . ")";
            }

            $rs = safe_rows("id, name", 'txp_category', "id IN (" . join(',', $things) . ")" . (($method == 'deleteforce') ? '' : " AND rgt - lft = 1 AND " . $used));

            if ($rs) {
                foreach ($rs as $cat) {
                    $catid[] = $cat['id'];
                    $names[] = $cat['name'];
                }

                if (delete_nodes($catid, $type)) {
                    if ($method == 'deleteforce') {
                        // Clear the deleted category names from assets.
                        $affected = quote_list($names, ',');

                        if ($type === 'article') {
                            safe_update('textpattern', "category1 = ''", "category1 IN ($affected)");
                            safe_update('textpattern', "category2 = ''", "category2 IN ($affected)");
                        } else {
                            safe_update('txp_' . $type, "category = ''", "category IN ($affected)");
                        }
                    }

                    callback_event('categories_deleted', $type, 0, $catid);

                    $message = count($things) == count($catid) ?
                        gTxt($type . '_categories_deleted', array('{list}' => join(', ', $catid))) :
                        array(gTxt($type . '_categories_deleted', array('{list}' => join(', ', $catid))), E_WARNING);
                }
            } else {
                $message = array(gTxt($type . '_categories_deleted', array('{list}' => 0)), E_ERROR);
            }
        } elseif ($method == 'changeparent') {
            $new_parent = doSlash(ps('new_parent')) or $new_parent = 'root';
            $exists = safe_row("name, title, lft, rgt", 'txp_category', "name = '$new_parent' AND type = '$type'");

            if ($exists) {
                extract($exists);
                $to_change = $affected = array();

                // Cannot assign parent to a child.
                $rs = safe_rows(
                    "id, title", 'txp_category',
                    "id IN (" . join(',', $things) . ") AND type = '" . $type . "' AND parent != '$new_parent' AND (lft > $lft OR rgt < $rgt)"
                );

                foreach ($rs as $cat) {
                    $to_change[] = $cat['id'];
                    $affected[] = $cat['title'];
                }

                $ret = $to_change && safe_update('txp_category', "parent = '$new_parent'", "id IN (" . join(",", $to_change) . ")");

                if ($ret) {
                    insert_nodes($to_change, array('parent' => $name, 'at' => $rgt), $type);

                    $message = !empty($affected)
                        ? gTxt('categories_set_parent', array(
                            '{type}'   => gTxt($type),
                            '{parent}' => $title,
                            '{list}'   => join(', ', $affected),
                        ))
                        : array(gTxt('category_save_failed'), E_ERROR);
                }
            }
        }

        return cat_category_list($message ? $message : array(gTxt('category_save_failed'), E_ERROR));
    }

    return cat_category_list();
}

/**
 * Renders a list of categories.
 *
 * @param  string $event Type of category
 * @return string HTML
 */

function cat_event_category_list($event)
{
    $rs = getTree('root', $event);

    $parent = ps('parent_cat');

    $heading = 'tab_' . ($event == 'article' ? 'list' : $event);
    $for = $rs ? ' for="' . $event . '_category_parent"' : '';
    $fieldSizes = Txp::get('\Textpattern\DB\Core')->columnSizes('txp_category', 'title');

    $out = hed(gTxt($heading) . popHelp($event . '_category'), 2) .
        form(
            graf(
                tag(gTxt('create_category'), 'label', array('for' => $event . '_category_new')) . br .
                Txp::get('\Textpattern\UI\Input', 'title', 'text')->setAtts(array(
                    'id'        => $event . '_category_new',
                    'size'      => INPUT_REGULAR,
                    'maxlength' => $fieldSizes['title'],
                ))->setBool('required')
            ) .
            (($rs)
                ? graf('<label' . $for . '>' . gTxt('parent') . '</label>' . br .
                    treeSelectInput('parent_cat', $rs, $parent, $event . '_category_parent'), array('class' => 'parent'))
                : ''
            ) .
            graf(
                fInput('submit', '', gTxt('create')) .
                eInput('category') .
                sInput('cat_' . $event . '_create')
            ), '', '', 'post', $event
        );

    if ($rs) {
        $total_count = array();

        if ($event == 'article') {
            // Count distinct articles for both categories, avoid duplicates.
            $rs2 = getRows(
                "SELECT category, COUNT(*) AS num FROM (
                    SELECT ID, Category1 AS category FROM " . safe_pfx('textpattern') . "
                        UNION
                    SELECT ID, Category2 AS category FROM " . safe_pfx('textpattern') . "
                ) AS t WHERE category != '' GROUP BY category"
            );

            if ($rs2 !== false) {
                foreach ($rs2 as $a) {
                    $total_count[$a['category']] = $a['num'];
                }
            }
        } else {
            switch ($event) {
                case 'link':
                case 'image':
                case 'file':
                    $rs2 = safe_rows_start("category, COUNT(*) AS num", 'txp_' . $event, "1 = 1 GROUP BY category");
                    break;
            }

            while ($a = nextRow($rs2)) {
                $name = $a['category'];
                $num = $a['num'];

                $total_count[$name] = $num;
            }
        }

        $items = array();

        foreach ($rs as $a) {
            extract($a);

            // Format count.
            switch ($event) {
                case 'article':
                    $url = array(
                            'event'         => 'list',
                            'search_method' => 'categories',
                            'crit'          => '"' . $name . '"',
                        );
                    break;
                case 'link':
                case 'image':
                case 'file':
                    $url = array(
                            'event'         => $event,
                            'search_method' => 'category',
                            'crit'          => '"' . $name . '"',
                        );
                    break;
            }

            $count = isset($total_count[$name]) ? href('(' . $total_count[$name] . ')', $url, array(
                'title' => gTxt('category_count', array('{num}' => $total_count[$name])),
            )) : '(0)';

            if (empty($title)) {
                $edit_link = '<em>' . eLink('category', 'cat_' . $event . '_edit', compact('id'), $id, gTxt('untitled'), '', '', gTxt('edit')) . '</em>';
            } else {
                $edit_link = eLink('category', 'cat_' . $event . '_edit', compact('id'), $id, $title, '', '', gTxt('edit'));
            }

            $items[] = graf(
                checkbox('selected[]', $id, 0) . sp . str_repeat(sp . sp, $level * 2) . $edit_link . sp . $count, ' class="level-' . $level . '"'
            );
        }

        if ($items) {
            $out .= cat_article_multiedit_form($event, $items);
        }
    } else {
        $out .= graf(
            span(null, array('class' => 'ui-icon ui-icon-info')) . ' ' .
            gTxt('no_categories_exist'),
            array('class' => 'alert-block information')
        );
    }

    return $out;
}

/**
 * Creates a new category.
 *
 * @param string $event The type of category
 */

function cat_event_category_create($event)
{
    $title = ps('title');

    $name = strtolower(sanitizeForUrl($title));

    if (!$name) {
        $message = array(gTxt($event . '_category_invalid', array('{name}' => $title)), E_ERROR);

        return cat_category_list($message);
    }

    $exists = safe_field("name", 'txp_category', "name = '" . doSlash($name) . "' AND type = '" . doSlash($event) . "'");

    if ($exists !== false) {
        $message = array(gTxt($event . '_category_already_exists', array('{name}' => $name)), E_ERROR);

        return cat_category_list($message);
    }

    $parent = strtolower(sanitizeForUrl(ps('parent_cat')));

    if (insert_nodes(null, compact('name', 'title', 'parent'), $event)) {
        $message = gTxt($event . '_category_created', array('{name}' => $name));

        cat_category_list($message);
    } else {
        cat_category_list(array(gTxt('category_save_failed'), E_ERROR));
    }
}

/**
 * Renders and outputs a category editor panel.
 *
 * @param string $evname Type of category
 */

function cat_event_category_edit($evname, $message = '')
{
    $id     = assert_int(gps('id'));
    $parent = doSlash(gps('parent'));
    $fieldSizes = Txp::get('\Textpattern\DB\Core')->columnSizes('txp_category', 'name, title, description');

    $row = safe_row("*", 'txp_category', "id = '$id'");

    if ($row) {
        pagetop(gTxt('edit_category'), $message);
        extract($row);
        list($parent_widget, $has_parent) = cat_parent_pop($parent, $evname, $id);

        $out = hed(gTxt('edit_category'), 2) .
            inputLabel(
                'category_name',
                Txp::get('\Textpattern\UI\Input', 'name', 'text', $name)->setAtts(array(
                    'id'        => 'category_name',
                    'size'      => INPUT_REGULAR,
                    'maxlength' => $fieldSizes['name'],
                ))->setBool('required'),
                $evname . '_category_name', '', array('class' => 'txp-form-field edit-category-name')
            ) .
            inputLabel(
                'category_parent',
                $parent_widget,
                'parent', '', array('class' => 'txp-form-field edit-category-parent')
            ) .
            inputLabel(
                'category_title',
                Txp::get('\Textpattern\UI\Input', 'title', 'text', $title)->setAtts(array(
                    'id'        => 'category_title',
                    'size'      => INPUT_REGULAR,
                    'maxlength' => $fieldSizes['title'],
                )),
                $evname . '_category_title', '', array('class' => 'txp-form-field edit-category-title')
            ) .
            inputLabel(
                'category_description',
                '<textarea id="category_description" name="description" cols="' . INPUT_LARGE . '" rows="' . TEXTAREA_HEIGHT_SMALL . '" maxlength="' . $fieldSizes['description'] . '">' . $description . '</textarea>',
                $evname . '_category_description', 'category_description', array('class' => 'txp-form-field txp-form-field-textarea edit-category-description')
            ) .
            pluggable_ui('category_ui', 'extend_detail_form', '', $row) .
            hInput('id', $id) .
            graf(
                sLink('category', '', gTxt('cancel'), 'txp-button') .
                fInput('submit', '', gTxt('save'), 'publish'),
                array('class' => 'txp-edit-actions')
            ) .
            eInput('category') .
            sInput('cat_' . $evname . '_save') .
            hInput('old_name', $name);

        echo form($out, '', '', 'post', 'txp-edit');
    } else {
        cat_category_list(array(gTxt('category_not_found'), E_ERROR));
    }
}

/**
 * Saves a category from HTTP POST data.
 *
 * @param string $event Type of category
 * @param string $table Affected database table
 */

function cat_event_category_save($event, $table_name)
{
    $data = array_map('assert_string', psa(array('name', 'description', 'parent', 'title')));
    $rawname = $data['name'];
    $data['name'] = sanitizeForUrl($rawname);
    $id = assert_int(ps('id'));

    // Make sure the name is valid.
    if (!$data['name'] || !($cat = safe_row('name AS old_name, parent AS old_parent', 'txp_category', "id = $id"))) {
        $message = array(gTxt($event . '_category_invalid', array('{name}' => $rawname)), E_ERROR);

        return cat_event_category_edit($event, $message);
    }

    extract(doSlash($data + $cat));

    // Don't allow rename to clobber an existing category.
    if (safe_count('txp_category', "name = '$name' AND type = '$event' AND id != $id")) {
        $message = array(gTxt($event . '_category_already_exists', array('{name}' => $data['name'])), E_ERROR);

        return cat_event_category_edit($event, $message);
    }

    $parent or $parent = 'root';

    if ($parent == $old_parent || insert_nodes($id, $data, $event)) {
        $res = safe_update('txp_category', "name = '$name', parent = '$parent', title = '$title', description = '$description'", "id = '$id'");

        if ($old_name != $name && safe_update('txp_category', "parent = '$name'", "parent = '$old_name' AND type = '$event'")) {
            if ($event == 'article') {
                $res = safe_update('textpattern', "Category1 = '$name'", "Category1 = '$old_name'") &&
                    safe_update('textpattern', "Category2 = '$name'", "Category2 = '$old_name'");
            } else {
                $res = safe_update($table_name, "category = '$name'", "category = '$old_name'");
            }
        }

        $message = empty($res) ? array(gTxt('category_save_failed'), E_ERROR) : gTxt($event . '_category_updated', array('{name}' => $data['name']));
    }

    cat_category_list($message);
}

/**
 * Renders a list of file categories.
 */

function cat_file_list()
{
    return cat_event_category_list('file');
}

/**
 * Processes a saved editor form and creates a file category.
 */

function cat_file_create()
{
    return cat_event_category_create('file');
}

/**
 * Renders an editor form for file categories.
 */

function cat_file_edit()
{
    return cat_event_category_edit('file');
}

/**
 * Saves a file category.
 */

function cat_file_save()
{
    return cat_event_category_save('file', 'txp_file');
}

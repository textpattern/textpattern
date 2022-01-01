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
 * Pages panel.
 *
 * @package Admin\Page
 */

use Textpattern\Skin\Skin;
use Textpattern\Skin\Page;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'page') {
    require_privs('page');

    $instance = Txp::get('Textpattern\Skin\Page');

    bouncer($step, array(
        'page_edit'        => false,
        'page_save'        => true,
        'page_delete'      => true,
        'page_skin_change' => true,
        'tagbuild'         => false,
    ));

    switch (strtolower($step)) {
        case '':
            page_edit();
            break;
        case 'page_edit':
            page_edit();
            break;
        case 'page_save':
            page_save();
            break;
        case 'page_delete':
            page_delete();
            break;
        case 'page_new':
            page_new();
            break;
        case "page_skin_change":
            $instance->selectEdit();
            page_edit();
            break;
        case 'tagbuild':
            echo page_tagbuild();
            break;
    }
}

/**
 * The main Page editor panel.
 *
 * @param string|array $message          The activity message
 * @param bool         $refresh_partials Whether to refresh partial contents
 */

function page_edit($message = '', $refresh_partials = false)
{
    global $instance, $event, $step;

    /*
    $partials is an array of:
    $key => array (
        'mode' => {PARTIAL_STATIC | PARTIAL_VOLATILE | PARTIAL_VOLATILE_VALUE},
        'selector' => $DOM_selector or array($selector, $fragment) of $DOM_selectors,
         'cb' => $callback_function,
         'html' => $return_value_of_callback_function (need not be initialised here)
    )
    */
    $partials = array(
        // Stylesheet list.
        'list' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#all_pages',
            'cb'       => 'page_list',
        ),
        // Name field.
        'name' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'div.name',
            'cb'       => 'page_partial_name',
        ),
        // Name value.
        'name_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#new_page,#main_content input[name=name]',
            'cb'       => 'page_partial_name_value',
        ),
        // Textarea.
        'template' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.template',
            'cb'       => 'page_partial_template',
        ),
    );

    extract(array_map('assert_string', gpsa(array(
        'copy',
        'save_error',
        'savenew',
        'skin',
    ))));

    $default_name = safe_field("page", 'txp_section', "name = 'default'");

    $name = assert_string(gps('name'));
    $newname = Page::sanitize(assert_string(gps('newname')));
    $skin = ($skin !== '') ? $skin : null;
    $class = 'async';

    $thisSkin = Txp::get('Textpattern\Skin\Skin');
    $skin = $thisSkin->setName($skin)->setEditing();

    if ($step == 'page_delete' || $name === '' && $step != 'page_new' && !$savenew) {
        $name = get_pref('last_page_saved', $default_name);
    } elseif ((($copy || $savenew) && $newname) && !$save_error) {
        $name = $newname;
    } elseif ((($newname && $newname !== $name) || $step === 'page_new') && !$save_error) {
        $name = $newname;
        $class = '';
    } elseif ($savenew && $save_error) {
        $class = '';
    }

    if (!$save_error) {
        $html = safe_field('user_html', 'txp_page', "name = '".doSlash($name)."' AND skin = '" . doSlash($skin) . "'");
    } else {
        $html = gps('html', false);
    }

    $html !== false or $name = ''; 
    $actionsExtras = '';

    if ($name !== '') {
        $actionsExtras .= sLink('page', 'page_new', '<span class="ui-icon ui-extra-icon-new-document"></span> '.gTxt('create_page'), 'txp-new')
        .href('<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'), '#',
            array(
                'class'     => 'txp-clone',
                'data-form' => 'page_form',
            )
        );
    }

    $actions = graf(
        $actionsExtras,
        array('class' => 'txp-actions txp-actions-inline')
    );

    $skinBlock = n.$instance->setSkin($thisSkin)->getSelectEdit();

    $buttons = graf(
        (!is_writable($instance->getDirPath()) ? '' :
            span(
                checkbox2('export', gps('export'), 0, 'export').
                n.tag(gTxt('export_to_disk'), 'label', array('for' => 'export'))
            , array('class' => 'txp-save-export'))
        ).n.
        tag_void('input', array(
            'class'  => 'publish',
            'type'   => 'submit',
            'method' => 'post',
            'value'  =>  gTxt('save'),
        )), ' class="txp-save"'
    );

    $rs = array(
        'name'    => $name,
        'newname' => $newname,
        'default' => $default_name,
        'skin'    => $skin,
        'html'    => $html,
        );

    // Get content for volatile partials.
    $partials = updatePartials($partials, $rs, array(PARTIAL_VOLATILE, PARTIAL_VOLATILE_VALUE));

    if ($refresh_partials) {
        $response[] = announce($message);
        $response = array_merge($response, updateVolatilePartials($partials));
        send_script_response(join(";\n", $response));

        // Bail out.
        return;
    }

    // Get content for static partials.
    $partials = updatePartials($partials, $rs, PARTIAL_STATIC);

    pagetop(gTxt('tab_pages'), $message);

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_pages'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        );

    // Pages create/switcher column.
    echo n.tag(
        $skinBlock.$partials['list']['html'].n,
        'div', array(
            'class' => 'txp-layout-4col-alt',
            'id'    => 'content_switcher',
            'role'  => 'region',
        )
    );

    // Pages code column.
    echo n.tag(
        form(
            $actions.
            $partials['name']['html'].
            $partials['template']['html'].
            $buttons, '', '', 'post', $class, '', 'page_form'),
        'div', array(
            'class' => 'txp-layout-4col-3span',
            'id'    => 'main_content',
            'role'  => 'region',
        )
    );

    // Tag builder dialog placeholder.
    echo n.tag(
        '&nbsp;',
        'div', array(
            'class'      => 'txp-tagbuilder-content',
            'id'         => 'tagbuild_links',
            'title'      => gTxt('tagbuilder'),
            'aria-label' => gTxt('tagbuilder'),
        ));

    echo n.'</div>'; // End of .txp-layout.
}

/**
 * Renders a list of page templates.
 *
 * @param  string $current The selected template info
 * @return string HTML
 */

function page_list($current)
{
    $out = array();
    $safe_skin = doSlash($current['skin']);
    $protected = safe_column("DISTINCT page", 'txp_section', "skin = '$safe_skin' OR dev_skin = '$safe_skin'") + array('error_default');

    $criteria = "skin = '$safe_skin'";
    $criteria .= callback_event('admin_criteria', 'page_list', 0, $criteria);

    $rs = safe_rows_start("name", 'txp_page', "$criteria ORDER BY name ASC");

    if ($rs) {
        while ($a = nextRow($rs)) {
            extract($a);
            $active = ($current['name'] === $name);

            $edit = eLink('page', '', 'name', $name, $name);

            if (!in_array($name, $protected)) {
                $edit .= dLink('page', 'page_delete', 'name', $name);
            }

            $out[] = tag(n.$edit.n, 'li', array('class' => $active ? 'active' : ''));
        }

        $out = tag(join(n, $out), 'ul', array('class' => 'switcher-list'));

        return wrapGroup('all_pages', $out, 'all_pages');
    }
}

/**
 * Deletes a page template.
 */

function page_delete()
{
    global $prefs;

    $name = ps('name');
    $safe_name = doSlash($name);
    $skin = get_pref('skin_editing', 'default');
    $safe_skin = doSlash($skin);
    $count = safe_count('txp_section', "page = '$safe_name' AND (skin='$safe_skin' OR dev_skin='$safe_skin')");
    $message = '';

    if ($name == 'error_default') {
        return page_edit();
    }

    if ($count) {
        $message = array(gTxt('page_used_by_section', array(
            '{name}'  => $name,
            '{count}' => $count,
        )), E_WARNING);
    } else {
        if (safe_delete('txp_page', "name = '$safe_name' AND skin='$safe_skin'")) {
            callback_event('page_deleted', '', 0, compact('name', 'skin'));
            $message = gTxt('page_deleted', array('{list}' => $name));
            if ($name === get_pref('last_page_saved')) {
                unset($prefs['last_page_saved']);
                remove_pref('last_page_saved', 'page');
            }
        }
    }

    page_edit($message);
}

/**
 * Changes the skin in which styles are being edited.
 *
 * Keeps track of which skin is being edited from panel to panel.
 *
 * @param      string $skin Optional skin name. Read from GET/POST otherwise
 * @deprecated in 4.7.0
 */

function page_skin_change($skin = null)
{
    Txp::get('Textpattern\Skin\Page')->selectEdit($skin);

    return true;
}

/**
 * Saves or clones a page template.
 */

function page_save()
{
    global $instance, $app_mode;

    extract(doSlash(array_map('assert_string', psa(array(
        'savenew',
        'html',
        'copy',
        'skin',
    )))));

    $passedName = assert_string(ps('name'));
    $name = Page::sanitize($passedName);
    $newname = Page::sanitize(assert_string(ps('newname')));

    $skin = Txp::get('Textpattern\Skin\Skin')->setName($skin)->setEditing();

    $save_error = false;
    $message = '';

    if (empty($newname)) {
        $message = array(gTxt('page_name_invalid'), E_ERROR);
        $save_error = true;
    } else {
        if ($copy && ($name === $newname)) {
            $newname .= '_copy';
            $passedName = $name;
            $_POST['newname'] = $newname;
        }

        $safe_skin = doSlash($skin);
        $safe_name = doSlash($passedName);
        $safe_newname = doSlash($newname);

        $exists = safe_field('name', 'txp_page', "name = '$safe_newname' AND skin = '$safe_skin'");

        if ($newname !== $name && $exists !== false) {
            $message = array(gTxt('page_already_exists', array('{name}' => $newname)), E_ERROR);

            if ($savenew) {
                $_POST['newname'] = '';
            }

            $save_error = true;
        } else {
            if ($savenew or $copy) {
                if ($newname) {
                    if (safe_insert('txp_page', "name = '$safe_newname', user_html = '$html', skin = '$safe_skin'")) {
                        set_pref('last_page_saved', $newname, 'page', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
                        update_lastmod('page_created', compact('newname', 'name', 'html'));

                        $message = gTxt('page_created', array('{list}' => $newname));

                        // If page name has been auto-sanitized, throw a warning.
                        if ($passedName !== $name) {
                            $message = array($message, E_WARNING);
                        }

                        callback_event($copy ? 'page_duplicated' : 'page_created', '', 0, $name, $newname);
                    } else {
                        $message = array(gTxt('page_save_failed'), E_ERROR);
                        $save_error = true;
                    }
                } else {
                    $message = array(gTxt('page_name_invalid'), E_ERROR);
                    $save_error = true;
                }
            } else {
                if (safe_update('txp_page',
                        "user_html = '$html', name = '$safe_newname', skin = '$safe_skin'",
                        "name = '$safe_name' AND skin = '$safe_skin'")) {
                    safe_update('txp_section', "page = '$safe_newname'", "page='$safe_name' AND skin='$safe_skin'");
                    safe_update('txp_section', "dev_page = '$safe_newname'", "dev_page='$safe_name' AND dev_skin='$safe_skin'");
                    set_pref('last_page_saved', $newname, 'page', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
                    update_lastmod('page_saved', compact('newname', 'name', 'html'));

                    $message = gTxt('page_updated', array('{list}' => $newname));

                    // If page name has been auto-sanitized, throw a warning.
                    if ($passedName !== $name) {
                        $message = array($message, E_WARNING);
                    }

                    callback_event('page_updated', '', 0, $name, $newname);
                } else {
                    $message = array(gTxt('page_save_failed'), E_ERROR);
                    $save_error = true;
                }
            }
        }
    }

    if ($save_error === true) {
        $_POST['save_error'] = '1';
    } else {
        if (gps('export')) {
            $instance->setNames(array($newname))->export()->getMessage();
        }

        callback_event('page_saved', '', 0, $name, $newname);
    }

    page_edit($message, ($app_mode === 'async') ? true : false);
}

/**
 * Directs requests to page_edit() armed with a 'page_new' step.
 *
 * @see page_edit()
 */

function page_new()
{
    page_edit();
}

/**
 * Return a list of tag builder tags.
 *
 * @return HTML
 */

function page_tagbuild()
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

    // Format of each entry is popTagLink -> array ( gTxt() string, class/ID).
    $tagbuild_items = array(
        'page_article'     => array('page_article_hed', 'article-tags'),
        'page_article_nav' => array('page_article_nav_hed', 'article-nav-tags'),
        'page_nav'         => array('page_nav_hed', 'nav-tags'),
        'page_xml'         => array('page_xml_hed', 'xml-tags'),
        'page_misc'        => array('page_misc_hed', 'misc-tags'),
        'page_file'        => array('page_file_hed', 'file-tags'),
    );

    $tagbuild_links = '';

    foreach ($tagbuild_items as $tb => $item) {
        $tagbuild_links .= wrapRegion($item[1].'_group', taglinks($tb), $item[1], $item[0], 'page_'.$item[1]);
    }

    return $listActions.$tagbuild_links;
}

/**
 * Renders a list of tag builder options.
 *
 * @param  string $type
 * @return HTML
 * @access private
 * @see    popTagLinks()
 */

function taglinks($type)
{
    return popTagLinks($type);
}

/**
 * Renders page name field.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function page_partial_name($rs)
{
    $name = $rs['name'];
    $skin = $rs['skin'];
    $nameRegex = '^(?=[^0.\s]|0.)[^\x00-\x1f\x22\x26\x27\x2a\x2f\x3a\x3c\x3e\x3f\x5c\x7c\x7f]+';

    $titleblock = inputLabel(
        'new_page',
        fInput('text', array('name' => 'newname', 'pattern' => $nameRegex), $name, 'input-medium', '', '', INPUT_MEDIUM, '', 'new_page', false, true),
        'page_name',
        array('', 'instructions_page_name'),
        array('class' => 'txp-form-field name')
    );

    if ($name === '') {
        $titleblock .= hInput('savenew', 'savenew');
    } else {
        $titleblock .= hInput('name', $name);
    }

    $titleblock .= hInput('skin', $skin).
        eInput('page').sInput('page_save');

    return $titleblock;
}

/**
 * Renders page name value.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function page_partial_name_value($rs)
{
    return $rs['name'];
}

/**
 * Renders page textarea field.
 *
 * @param  array  $rs Record set
 * @return string HTML
 */

function page_partial_template($rs)
{
    global $event;

    $out = inputLabel(
        'html',
        '<textarea class="code" id="html" name="html" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'" dir="ltr">'.txpspecialchars($rs['html']).'</textarea>',
        array(
            'page_code',
            n.span(
                (has_privs('tag')
                    ? href(
                        span(null, array('class' => 'ui-icon ui-extra-icon-code')).' '.gTxt('tagbuilder'),
                        array('event' => 'tag', 'panel' => $event),
                        array('class' => 'txp-tagbuilder-dialog')
                    )
                    : ''
                ),
                array('class' => 'txp-textarea-options')
            )
        ),
        array('', 'instructions_page_code'),
        array('class' => 'txp-form-field template'),
        array('div', 'div')
    );

    return $out;
}

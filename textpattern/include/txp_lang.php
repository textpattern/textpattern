<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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
 * Languages panel.
 *
 * @package Admin\Lang
 * @since   4.6.0
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

if ($event == 'lang') {
    require_privs('lang');

    $available_steps = array(
        'get_language'     => true,
        'get_textpack'     => true,
        'remove_language'  => true,
        'save_language'    => true,
        'save_language_ui' => true,
        'list_languages'   => false,
    );

    if ($step && bouncer($step, $available_steps)) {
        $step();
    } else {
        list_languages();
    }
}

/**
 * Generate a &lt;select&gt; element of installed languages.
 *
 * @param  string $name The HTML name and ID to assign to the select control
 * @param  string $val  The currently active language identifier (en-gb, fr-fr, ...)
 * @return string HTML
 * @todo   Move this to a central location so it can also be used by install_textpack, etc
 */

function languages($name, $val)
{
    $installed_langs = Txp::get('\Textpattern\L10n\Lang')->available(TEXTPATTERN_LANG_ACTIVE | TEXTPATTERN_LANG_INSTALLED);
    $vals = array();

    foreach ($installed_langs as $lang => $langdata) {
        $vals[$lang] = $langdata['name'];

        if (trim($vals[$lang]) == '') {
            $vals[$lang] = $lang;
        }
    }

    ksort($vals);
    reset($vals);

    return selectInput($name, $vals, $val, false, true, $name);
}

/**
 * Generates a grid of every language that Textpattern supports.
 *
 * @param string|array $message The activity message
 */

function list_languages($message = '')
{
    $active_lang = get_pref('language', TEXTPATTERN_DEFAULT_LANG, true);
    $active_ui_lang = get_pref('language_ui', $active_lang, true);
    $cpanel = '';

    if (has_privs('lang.edit')) {
        $cpanel .= form(
            tag(gTxt('active_language'), 'label', array('for' => 'language')).
            languages('language', $active_lang).
            eInput('lang').
            sInput('save_language')
        );
    }

    $lang_form = tag(
        $cpanel.
        form(
            tag(gTxt('active_language_ui'), 'label', array('for' => 'language_ui')).
            languages('language_ui', $active_ui_lang).
            eInput('lang').
            sInput('save_language_ui')
        ), 'div', array(
            'class' => 'txp-control-panel',
        )
    );

    $available_lang = Txp::get('\Textpattern\L10n\Lang')->available();
    $installed_lang = Txp::get('\Textpattern\L10n\Lang')->available(TEXTPATTERN_LANG_INSTALLED);
    $active_lang = Txp::get('\Textpattern\L10n\Lang')->available(TEXTPATTERN_LANG_ACTIVE);
    $represented_lang = array_merge($active_lang, $installed_lang);

    $grid = '';
    $done = array();

    // Create the widget components.
    foreach ($represented_lang + $available_lang as $langname => $langdata) {
        if (in_array($langname, $done)) {
            continue;
        }

        $file_updated = (isset($langdata['db_lastmod']) && $langdata['file_lastmod'] > $langdata['db_lastmod']);

        if (array_key_exists($langname, $represented_lang)) {
            if ($file_updated) {
                $cellclass = 'warning';
                $icon = 'ui-icon-alert';
                $disabled = (has_privs('lang.edit') ? '' : 'disabled');
            } else {
                $cellclass = 'success';
                $icon = 'ui-icon-check';
                $disabled = 'disabled';
            }

            $btnText = escape_title(gTxt('update'));
            $removeLink = href(escape_title(gTxt('remove')), array(
                'event'      => 'lang',
                'step'       => 'remove_language',
                'lang_code'  => $langname,
                '_txp_token' => form_token(),
            ), array(
                'class' => 'txp-button'
            ));

            $btnRemove = (
                array_key_exists($langname, $active_lang)
                    ? ''
                    : (has_privs('lang.edit')
                        ? $removeLink
                        : '')
            );
        } else {
            $cellclass = $icon = '';
            $btnText = escape_title(gTxt('install'));
            $disabled = $btnRemove = '';
        }

        $installLink = ($disabled
            ? span($btnText, array(
                'class'    => 'txp-button',
                'disabled' => true,
            ))
            : href($btnText, array(
                'event'      => 'lang',
                'step'       => 'get_language',
                'lang_code'  => $langname,
                '_txp_token' => form_token(),
            ), array(
                'class' => 'txp-button',
            )));

        $grid .= tag(
            graf(
                ($icon ? '<span class="ui-icon '.$icon.'"></span>' : '').n.
                tag(gTxt($langdata['name']), 'strong')
            ).
            graf(
                (has_privs('lang.edit')
                    ? $installLink
                    : '')
                .n. $btnRemove
            ),
            'li',
            array('class' => 'txp-grid-cell'.($cellclass ? ' '.$cellclass : ''))
        ).n;

        $done[] = $langname;
    }

    // Output table and content.
    pagetop(gTxt('tab_languages'), $message);

    echo n.'<div class="txp-layout">'.
        n.tag(
            hed(gTxt('tab_languages'), 1, array('class' => 'txp-heading')),
            'div', array('class' => 'txp-layout-1col')
        ).
        n.tag_start('div', array(
            'class' => 'txp-layout-1col',
            'id'    => 'language_container',
        ));

    if (isset($msg) && $msg) {
        echo graf('<span class="ui-icon ui-icon-alert"></span> '.$msg, array('class' => 'alert-block error'));
    }

    echo $lang_form.
        '<ul class="txp-grid">'.
        $grid.
        '</ul>'.

        ((has_privs('lang.edit'))
            ? hed(gTxt('install_from_textpack'), 3).
                n.tag(
                    form(
                        '<label for="textpack-install">'.gTxt('install_textpack').'</label>'.popHelp('get_textpack').
                        n.'<textarea class="code" id="textpack-install" name="textpack" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'" dir="ltr" required="required"></textarea>'.
                        fInput('submit', 'install_new', gTxt('upload')).
                        eInput('lang').
                        sInput('get_textpack'), '', '', 'post', '', '', 'text_uploader'
                    ), 'div', array('class' => 'txp-control-panel'))
            : '');

    echo n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.;
}

/**
 * Saves the active language.
 */

function save_language()
{
    global $locale;

    require_privs('lang.edit');

    extract(psa(array(
        'language',
    )));

    if (safe_field("lang", 'txp_lang', "lang = '".doSlash($language)."' LIMIT 1")) {
        $locale = Txp::get('\Textpattern\L10n\Locale')->getLanguageLocale($language);
        $new_locale = $prefs['locale'] = Txp::get('\Textpattern\L10n\Locale')->setLocale(LC_ALL, array($language, 'C'))->getLocale();
        set_pref('locale', $new_locale);
        if ($new_locale == $locale) {
            $msg = gTxt('preferences_saved');
        } else {
            $msg = array(gTxt('locale_not_available_for_language', array('{name}' => $language)), E_WARNING);
        }
        set_pref('language', $language);
        list_languages($msg);

        return;
    }

    list_languages(array(gTxt('language_not_installed', array('{name}' => $language)), E_ERROR));
}

/**
 * Saves the active admin-side language.
 */

function save_language_ui()
{
    global $textarray, $locale;

    extract(psa(array(
        'language_ui',
    )));

    if (safe_field("lang", 'txp_lang', "lang = '".doSlash($language_ui)."' LIMIT 1")) {
        $locale = Txp::get('\Textpattern\L10n\Locale')->getLanguageLocale($language_ui);

        if ($locale) {
            $msg = gTxt('preferences_saved');
            set_pref('language_ui', $language_ui, 'admin', PREF_CORE, 'text_input', 0, PREF_PRIVATE);
            $textarray = load_lang($language_ui);
        } else {
            $msg = array(gTxt('locale_not_available_for_language', array('{name}' => $language_ui)), E_WARNING);
        }

        list_languages($msg);

        return;
    }

    list_languages(array(gTxt('language_not_installed', array('{name}' => $language_ui)), E_ERROR));
}

/**
 * Installs a language from a file.
 *
 * The HTTP POST parameter 'lang_code' is the installed language,
 * e.g. 'en-gb', 'fi-fi'.
 */

function get_language()
{
    $lang_code = gps('lang_code');

    if (Txp::get('\Textpattern\L10n\Lang')->install_file($lang_code)) {
        callback_event('lang_installed', 'file', false, $lang_code);

        // @todo Better i18n.
        return list_languages(gTxt($lang_code).sp.gTxt('updated'));
    }
}

/**
 * Writes a new language string to the database.
 *
 * The language is taken from a 'lang_code' HTTP POST or GET parameter.
 *
 * The '$value' argument takes a string as an array. This array consists of keys
 * 'name', 'event', 'data', 'uLastmod'.
 *
 * @param array $value  The string
 * @param int   $key    Not used
 */

function install_lang_key(&$value, $key)
{
    extract(gpsa(array(
        'lang_code',
        'updating',
    )));

    $exists = safe_field(
        "name",
        'txp_lang',
        "name = '".doSlash($value['name'])."' AND lang = '".doSlash($lang_code)."'"
    );

    $q =
        "name = '".doSlash($value['name'])."',
        event = '".doSlash($value['event'])."',
        data = '".doSlash($value['data'])."',
        lastmod = '".doSlash(strftime('%Y%m%d%H%M%S', $value['uLastmod']))."'";

    if ($exists !== false) {
        $value['ok'] = safe_update(
            'txp_lang',
            $q,
            "owner = '".doSlash(TEXTPATTERN_LANG_OWNER_SYSTEM)."' AND lang = '".doSlash($lang_code)."' AND name = '".doSlash($value['name'])."'"
        );
    } else {
        $value['ok'] = safe_insert(
            'txp_lang',
            "$q, lang = '".doSlash($lang_code)."'"
        );
    }
}

/**
 * Installs a Textpack.
 *
 * The Textpack to load is fed by a 'textpack' HTTP POST parameter.
 */

function get_textpack()
{
    require_privs('lang.edit');

    $textpack = ps('textpack');
    $n = Txp::get('\Textpattern\L10n\Lang')->install_textpack($textpack, true);
    list_languages(gTxt('textpack_strings_installed', array('{count}' => $n)));
}

/**
 * Remove all language strings for the given lang code.
 *
 * Removed language code is specified with 'lang_code' HTTP POST
 * parameter.
 */

function remove_language()
{
    require_privs('lang.edit');

    $lang_code = gps('lang_code');
    $ret = safe_delete('txp_lang', "lang = '".doSlash($lang_code)."'");

    if ($ret) {
        callback_event('lang_deleted', '', 0, $lang_code);
        $msg = gTxt($lang_code).sp.gTxt('deleted');
    } else {
        $msg = gTxt('cannot_delete', array('{thing}' => $lang_code));
    }

    list_languages($msg);
}

/**
 * Lists all language files in the 'lang' directory.
 *
 * @return     array Available language filenames
 * @deprecated in 4.7.0
 */

function get_lang_files()
{
    return Txp::get('\Textpattern\L10n\Lang')->files();
}

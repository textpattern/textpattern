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
 * Generates a grid of every language that Textpattern supports.
 *
 * @param string|array $message The activity message
 */

function list_languages($message = '')
{
    global $txp_user, $prefs;

    $allTypes = TEXTPATTERN_LANG_ACTIVE | TEXTPATTERN_LANG_INSTALLED | TEXTPATTERN_LANG_AVAILABLE;
    $available_lang = Txp::get('\Textpattern\L10n\Lang')->available($allTypes, $allTypes);
    $installed_lang = Txp::get('\Textpattern\L10n\Lang')->available(TEXTPATTERN_LANG_INSTALLED);
    $active_lang = Txp::get('\Textpattern\L10n\Lang')->available(TEXTPATTERN_LANG_ACTIVE);
    $represented_lang = array_merge($active_lang, $installed_lang);

    $site_lang = get_pref('language', TEXTPATTERN_DEFAULT_LANG, true);
    $ui_lang = get_pref('language_ui', $site_lang, true);
    $cpanel = '';

    if (has_privs('lang.edit')) {
        $langList = Txp::get('\Textpattern\L10n\Lang')->languageSelect('language', $site_lang);
        $cpanel .= form(
            tag(gTxt('active_language'), 'label', array('for' => 'language')).
            $langList.
            eInput('lang').
            sInput('save_language')
        );
    }

    $langList = Txp::get('\Textpattern\L10n\Lang')->languageSelect('language_ui', $ui_lang);
    $lang_form = tag(
        $cpanel.
        form(
            tag(gTxt('active_language_ui'), 'label', array('for' => 'language_ui')).
            $langList.
            eInput('lang').
            sInput('save_language_ui')
        ), 'div', array('class' => 'txp-control-panel')
    );

    $grid = '';
    $done = array();
    $in_use_by = safe_rows('val, user_name', 'txp_prefs', "name = 'language_ui' AND val in ('".join("','", doSlash(array_keys($represented_lang)))."') AND user_name != '".doSlash($txp_user)."'");

    $langUse = array();

    foreach ($in_use_by as $row) {
        $langUse[$row['val']][] = $row['user_name'];
    }

    foreach ($langUse as $key => $row) {
        $langUse[$key] = tag(eLink(
            'admin', 'author_list', 'search_method', 'login', '('.count($row).')', 'crit', join(',', doSlash($row)), gTxt('language_count_user', array('{num}' => count($row)))
        ), 'span', array('class' => 'txp-lang-user-count'));
    }

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
                $status = gTxt('installed').' <span role="separator">/</span> '.gTxt('update_available');
                $disabled = (has_privs('lang.edit') ? '' : 'disabled');
            } else {
                $cellclass = 'success';
                $icon = 'ui-icon-check';
                $status = gTxt('installed');
                $disabled = 'disabled';
            }

            if (isset($available_lang[$langname])) {
                $btnText = '<span class="ui-icon ui-icon-refresh"></span>'.sp.escape_title(gTxt('update'));
            } else {
                $btnText = '';
                $cellclass = 'warning';
            }

            $removeText = '<span class="ui-icon ui-icon-minus"></span>'.sp.escape_title(gTxt('remove'));

            $btnRemove = (
                array_key_exists($langname, $active_lang)
                    ? ''
                    : (has_privs('lang.edit')
                        ? tag($removeText, 'button', array(
                            'type' => 'submit',
                            'name' => 'remove_language',
                            ))
                        : '')
            );
        } else {
            $cellclass = $icon = '';
            $btnText = '<span class="ui-icon ui-icon-plus"></span>'.sp.escape_title(gTxt('install'));
            $disabled = $btnRemove = '';
        }

        $installLink = ($disabled
            ? ''
            : tag($btnText, 'button', array(
                'type'      => 'submit',
                'name'      => 'get_language',
            )));

        $langMeta = graf(
            ($icon ? '<span class="ui-icon '.$icon.'" role="status">'.$status.'</span>' : '').n.
            tag(gTxt($langdata['name']), 'strong', array('dir' => 'auto')).br.
            tag($langname, 'code', array('dir' => 'ltr')).
            ($btnRemove && array_key_exists($langname, $langUse) ? n.$langUse[$langname] : '')
        );

        $btnSet = trim((has_privs('lang.edit')
                ? $installLink
                : '')
            .n. $btnRemove);

        $grid .= tag(
            ($btnSet
                ? form(
                    $langMeta.
                    graf($btnSet).
                    hInput('lang_code', $langname).
                    eInput('lang').
                    sInput(null)
                , '', '', 'post')
                : $langMeta
            ),
            'li',
            array('class' => 'txp-grid-cell txp-grid-cell-2span'.($cellclass ? ' '.$cellclass : ''))
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

    if (!empty($prefs['module_pophelp'])) {
        echo graf(gTxt('language_preamble'), array('class' => 'txp-layout-textbox'));
    }

    if (isset($msg) && $msg) {
        echo graf('<span class="ui-icon ui-icon-alert"></span> '.$msg, array('class' => 'alert-block error'));
    }

    echo $lang_form.
        '<ul class="txp-grid txp-grid-lang">'.
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

    $txpLocale = Txp::get('\Textpattern\L10n\Locale');
    $langName = fetchLangName($language);

    if (safe_field("lang", 'txp_lang', "lang = '".doSlash($language)."' LIMIT 1")) {
        $txpLocale->setLocale(LC_TIME, LANG);
        $old_formats = txp_dateformats();
        $candidates = array_unique(array($language, $txpLocale->getLocaleLanguage($language)));
        $locale = $txpLocale->getLanguageLocale($language);
        $new_locale = $txpLocale->setLocale(LC_ALL, array_filter($candidates))->getLocale();
        $new_language = $txpLocale->getLocaleLanguage($new_locale);
        set_pref('locale', $new_locale);
        $new_formats = txp_dateformats();

        foreach (array('dateformat', 'archive_dateformat', 'comments_dateformat') as $dateformat) {
            $key = array_search(get_pref($dateformat), $old_formats);

            if ($key !== false && $new_formats[$key] != $old_formats[$key]) {
                set_pref($dateformat, $new_formats[$key]);
            }
        }

        if ($new_locale == $locale || $new_language == $language) {
            $msg = gTxt('preferences_saved');
        } else {
            $msg = array(gTxt('locale_not_available_for_language', array('{name}' => $langName)), E_WARNING);
        }

        set_pref('language', $language);
        list_languages($msg);

        return;
    }

    list_languages(array(gTxt('language_not_installed', array('{name}' => $langName)), E_ERROR));
}

/**
 * Saves the active admin-side language.
 */

function save_language_ui()
{
    global $locale;

    extract(psa(array(
        'language_ui',
    )));

    if (get_pref('language_ui') != $language_ui) {
        $langName = fetchLangName($language_ui);

        if (safe_field("lang", 'txp_lang', "lang = '".doSlash($language_ui)."' LIMIT 1")) {
            $locale = Txp::get('\Textpattern\L10n\Locale')->getLanguageLocale($language_ui);

            if ($locale) {
                set_pref('language_ui', $language_ui, 'admin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
                txp_die('', 307, '?event=lang');
            } else {
                $msg = array(gTxt('locale_not_available_for_language', array('{name}' => $langName)), E_WARNING);
            }
        } else {
            $msg = array(gTxt('language_not_installed', array('{name}' => $langName)), E_ERROR);
        }
    } else {
        $msg = gTxt('preferences_saved');
    }

    list_languages($msg);
}

/**
 * Installs a language from a file.
 *
 * The HTTP POST parameter 'lang_code' is the installed language,
 * e.g. 'en-gb', 'fi'.
 */

function get_language()
{
    $lang_code = ps('lang_code');
    $langName = fetchLangName($lang_code);
    $txpLang = Txp::get('\Textpattern\L10n\Lang');
    $installed = $txpLang->installed();
    $installString = in_array($lang_code, $installed) ? 'language_updated' : 'language_installed';

    if ($txpLang->installFile($lang_code)) {
        callback_event('lang_installed', 'file', false, $lang_code);

        $txpLang->available(TEXTPATTERN_LANG_AVAILABLE, TEXTPATTERN_LANG_INSTALLED | TEXTPATTERN_LANG_AVAILABLE);
        Txp::get('\Textpattern\Plugin\Plugin')->installTextpacks();

        return list_languages(gTxt($installString, array('{name}' => $langName)));
    }

    return list_languages(array(gTxt('language_not_installed', array('{name}' => $langName)), E_ERROR));
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
        lastmod = '".doSlash(date('YmdHis', $value['uLastmod']))."'";

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
    $n = Txp::get('\Textpattern\L10n\Lang')->installTextpack($textpack, true);
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
    global $event;

    require_privs('lang.edit');

    $lang_code = ps('lang_code');
    $langName = fetchLangName($lang_code);

    $ret = safe_delete('txp_lang', "lang = '".doSlash($lang_code)."'");

    if ($ret) {
        callback_event('lang_deleted', '', 0, $lang_code);
        $msg = gTxt('language_deleted', array('{name}' => $langName));
        $represented_lang = Txp::get('\Textpattern\L10n\Lang')->available(
            TEXTPATTERN_LANG_ACTIVE | TEXTPATTERN_LANG_INSTALLED,
            TEXTPATTERN_LANG_ACTIVE | TEXTPATTERN_LANG_INSTALLED | TEXTPATTERN_LANG_AVAILABLE
        );

        $site_lang = get_pref('language', TEXTPATTERN_DEFAULT_LANG, true);
        $ui_lang = get_pref('language_ui', $site_lang, true);
        $ui_lang = (array_key_exists($ui_lang, $represented_lang)) ? $ui_lang : $site_lang;
        set_pref('language_ui', $ui_lang, 'admin', PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
        load_lang($ui_lang, $event);
    } else {
        $msg = gTxt('cannot_delete', array('{thing}' => $langName));
    }

    list_languages($msg);
}

/**
 * Get the lang name from the given language file.
 *
 * @param  string $lang_code Language designator
 * @return string
 */

function fetchLangName($lang_code)
{
    $txpLang = Txp::get('\Textpattern\L10n\Lang');
    $langFile = $txpLang->findFilename($lang_code);
    $langInfo = $txpLang->fetchMeta($langFile);
    $langName = (isset($langInfo['name'])) ? $langInfo['name'] : $lang_code;

    return $langName;
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

<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
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
 * Languages panel.
 *
 * @package Admin\Lang
 * @since   4.6.0
 */

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

include_once txpath.'/lib/txplib_update.php';

if ($event == 'lang') {
    require_privs('lang');

    $available_steps = array(
        'get_language'    => true,
        'get_textpack'    => true,
        'remove_language' => true,
        'save_language'   => true,
        'list_languages'  => false,
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
 */

function languages($name, $val)
{
    $installed_langs = safe_column("lang", 'txp_lang', "1 = 1 GROUP BY lang");

    $vals = array();

    foreach ($installed_langs as $lang) {
        $vals[$lang] = safe_field("data", 'txp_lang', "name = '".doSlash($lang)."' AND lang = '".doSlash($lang)."'");

        if (trim($vals[$lang]) == '') {
            $vals[$lang] = $lang;
        }
    }

    asort($vals);
    reset($vals);

    return selectInput($name, $vals, $val, false, true, $name);
}

/**
 * Generates a &lt;table&gt; of every language that Textpattern supports.
 *
 * If requested with HTTP POST parameter 'force' set anything other than 'file',
 * outputs any errors in RPC server connection.
 *
 * @param string|array $message The activity message
 */

function list_languages($message = '')
{
    require_once txpath.'/lib/IXRClass.php';

    $active_lang = safe_field("val", 'txp_prefs', "name = 'language'");

    $lang_form = tag(
        form(
            tag(gTxt('active_language'), 'label', array('for' => 'language')).
            languages('language', $active_lang).
            eInput('lang').
            sInput('save_language')
        ), 'div', array(
            'class' => 'txp-control-panel',
        )
    );

    $client = new IXR_Client(RPC_SERVER);
//    $client->debug = true;

    $available_lang = array();
    $rpc_connect = false;
    $show_files = false;

    // Get items from RPC.
    @set_time_limit(90); // TODO: 90 seconds: seriously?
    if ($client->query('tups.listLanguages', get_pref('blog_uid'))) {
        $rpc_connect = true;
        $response = $client->getResponse();

        foreach ($response as $language) {
            $available_lang[$language['language']]['rpc_lastmod'] = gmmktime($language['lastmodified']->hour, $language['lastmodified']->minute, $language['lastmodified']->second, $language['lastmodified']->month, $language['lastmodified']->day, $language['lastmodified']->year);
        }
    } elseif (gps('force') != 'file') {
        $msg = gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->";
    }

    // Get items from Filesystem.
    $files = get_lang_files();

    if (is_array($files) && !empty($files)) {
        foreach ($files as $file) {
            if ($fp = @fopen(txpath.DS.'lang'.DS.$file, 'r')) {
                $name = preg_replace('/\.(txt|textpack)$/i', '', $file);
                $firstline = fgets($fp, 4069);
                fclose($fp);

                if (strpos($firstline, '#@version') !== false) {
                    @list($fversion, $ftime) = explode(';', trim(substr($firstline, strpos($firstline, ' ', 1))));
                } else {
                    $fversion = $ftime = null;
                }

                $available_lang[$name]['file_note'] = (isset($fversion)) ? $fversion : 0;
                $available_lang[$name]['file_lastmod'] = (isset($ftime)) ? $ftime : 0;
            }
        }
    }

    // Get installed items from the database.
    // We need a value here for the language itself, not for each one of the rows.
    $rows = safe_rows("lang, UNIX_TIMESTAMP(MAX(lastmod)) AS lastmod", 'txp_lang', "1 = 1 GROUP BY lang ORDER BY lastmod DESC");
    $installed_lang = array();

    foreach ($rows as $language) {
        $available_lang[$language['lang']]['db_lastmod'] = $language['lastmod'];

        if ($language['lang'] != $active_lang) {
            $installed_lang[] = $language['lang'];
        }
    }

    $list = '';

    // Create the language table components.
    foreach ($available_lang as $langname => $langdat) {
        $file_updated = (isset($langdat['db_lastmod']) && @$langdat['file_lastmod'] > $langdat['db_lastmod']);
        $rpc_updated = (@$langdat['rpc_lastmod'] > @$langdat['db_lastmod']);

        $rpc_install = tda(
            ($rpc_updated)
            ? strong(
                eLink(
                    'lang',
                    'get_language',
                    'lang_code',
                    $langname,
                    (isset($langdat['db_lastmod'])
                        ? gTxt('update')
                        : gTxt('install')
                    ),
                    'updating',
                    isset($langdat['db_lastmod']),
                    ''
                )
            ).
            n.span(safe_strftime('%d %b %Y %X', @$langdat['rpc_lastmod']), array('class' => 'date modified'))
            : (
                (isset($langdat['rpc_lastmod'])
                    ? gTxt('up_to_date')
                    : '-'
                ).
                (isset($langdat['db_lastmod'])
                    ? n.span(safe_strftime('%d %b %Y %X', $langdat['db_lastmod']), array('class' => 'date modified'))
                    : ''
                )
            ), (isset($langdat['db_lastmod']) && $rpc_updated)
                ? ' class="highlight lang-value"'
                : ' class="lang-value"'
        );

        $lang_file = tda(
            (isset($langdat['file_lastmod']))
            ? strong(
                eLink(
                    'lang',
                    'get_language',
                    'lang_code',
                    $langname,
                    (
                        ($file_updated)
                        ? gTxt('update')
                        : gTxt('install')
                    ),
                    'force',
                    'file',
                    ''
                )
            ).
            n.span(safe_strftime(get_pref('archive_dateformat'), $langdat['file_lastmod']), array(
                'class' => 'date '.($file_updated ? 'created' : 'modified'),
            ))
            : '-', ' class="lang-value languages_detail'.((isset($langdat['db_lastmod']) && $rpc_updated) ? ' highlight' : '').'"'
        );

        $list .= tr(
        // Lang-Name and Date.
            hCell(
                gTxt($langname), '', (isset($langdat['db_lastmod']) && $rpc_updated)
                        ? ' class="highlight lang-label" scope="row"'
                        : ' class="lang-label" scope="row"'
                ).
            n.$rpc_install.
            n.$lang_file.
            tda((in_array($langname, $installed_lang) ? dLink('lang', 'remove_language', 'lang_code', $langname, 1) : '-'), ' class="languages_detail'.((isset($langdat['db_lastmod']) && $rpc_updated) ? ' highlight' : '').'"')
        ).n;
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

    echo $lang_form,
        n.tag(
            toggle_box('languages_detail'), 'div', array('class' => 'txp-list-options')).
        n.tag_start('div', array('class' => 'txp-listtables')).
        n.tag_start('table', array('class' => 'txp-list')).
        n.tag_start('thead').
        tr(
            hCell(
                gTxt('language'), '', ' scope="col"'
            ).
            hCell(
                gTxt('from_server').popHelp('install_lang_from_server'), '', ' scope="col"'
            ).
            hCell(
                gTxt('from_file').popHelp('install_lang_from_file'), '', ' class="languages_detail" scope="col"'
            ).
            hCell(
                gTxt('remove_lang').popHelp('remove_lang'), '', ' class="languages_detail" scope="col"'
            )
        ).
        n.tag_end('thead').
        n.tag_start('tbody').
        $list.
        n.tag_end('tbody').
        n.tag_end('table').
        n.tag_end('div'). // End of .txp-listtables.

        hed(gTxt('install_from_textpack'), 2).
        n.tag(
            form(
                '<label for="textpack-install">'.gTxt('install_textpack').'</label>'.popHelp('get_textpack').
                n.'<textarea class="code" id="textpack-install" name="textpack" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'" dir="ltr"></textarea>'.
                fInput('submit', 'install_new', gTxt('upload')).
                eInput('lang').
                sInput('get_textpack'), '', '', 'post', '', '', 'text_uploader'
            ), 'div', array('class' => 'txp-control-panel'));

    echo n.tag_end('div'). // End of .txp-layout-1col.
        n.'</div>'; // End of .txp-layout.;
}

/**
 * Saves the active language.
 */

function save_language()
{
    global $textarray, $locale;

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
        $textarray = load_lang($language);
        list_languages($msg);

        return;
    }

    list_languages(array(gTxt('language_not_installed', array('{name}' => $language)), E_ERROR));
}

/**
 * Installs a language from the RPC server or from a file.
 *
 * This function fetches language strings for the given language code from
 * either the RPC server or a file.
 *
 * Action is taken based on three HTTP POST parameters: 'lang_code', 'force' and
 * 'updating'. The 'lang_code' is the installed langauge, e.g. 'en-gb', 'fi-fi'.
 * The 'force' when set to 'file' can be used force an installation from a local
 * file. The 'updating' specifies whether only to install (0) or to update (1).
 */

function get_language()
{
    global $prefs, $textarray;
    require_once txpath.'/lib/IXRClass.php';
    $lang_code = gps('lang_code');

    $client = new IXR_Client(RPC_SERVER);
//    $client->debug = true;

    @set_time_limit(90); // TODO: 90 seconds: seriously?
    if (gps('force') == 'file' || !$client->query('tups.getLanguage', $prefs['blog_uid'], $lang_code)) {
        if ((gps('force') == 'file' || gps('updating') !== '1') && install_language_from_file($lang_code)) {
            if (defined('LANG')) {
                $textarray = load_lang(LANG);
            }

            callback_event('lang_installed', 'file', false, $lang_code);

            return list_languages(gTxt($lang_code).sp.gTxt('updated'));
        } else {
            pagetop(gTxt('installing_language'));
            echo graf('<span class="ui-icon ui-icon-alert"></span> '.gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->", array('class' => 'alert-block error'));
        }
    } else {
        $response = $client->getResponse();
        $lang_struct = unserialize($response);

        if ($lang_struct === false) {
            $errors = $size = 1;
        } else {
            array_walk($lang_struct, 'install_lang_key');
            $size = count($lang_struct);
            $errors = 0;

            for ($i = 0; $i < $size; $i++) {
                $errors += (!$lang_struct[$i]['ok']);
            }

            if (defined('LANG')) {
                $textarray = load_lang(LANG);
            }
        }

        $msg = gTxt($lang_code).sp.gTxt('updated');

        callback_event('lang_installed', 'remote', false, $lang_code);

        if ($errors > 0) {
            $msg = array($msg.sprintf(" (%s errors, %s ok)", $errors, ($size-$errors)), E_ERROR);
        }

        list_languages($msg);
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
 * The Textpack is feeded by a 'textpack' HTTP POST parameter.
 *
 * @see install_textpack()
 */

function get_textpack()
{
    $textpack = ps('textpack');
    $n = install_textpack($textpack, true);
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
    $lang_code = ps('lang_code');
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
 * @return array Available language filenames
 */

function get_lang_files()
{
    $lang_dir = txpath.DS.'lang'.DS;

    if (!is_dir($lang_dir)) {
        trigger_error('Lang directory is not a directory: '.$lang_dir, E_USER_WARNING);

        return array();
    }

    if (chdir($lang_dir)) {
        if ($files = glob('*.{txt,textpack}', GLOB_BRACE)) {
            return $files;
        }
    }

    return array();
}

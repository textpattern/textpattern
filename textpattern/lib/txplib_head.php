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
 * Used for generating admin-side headers.
 *
 * @package HTML
 */

/**
 * Creates and outputs an admin-side header.
 *
 * The output contains HTML &lt;head&gt; section and the main navigation.
 * The results are echoed as opposed to returned.
 *
 * This function offers a way to invoke modal activity messages and set the
 * page title.
 *
 * Output will automatically become silent on asynchronous script responses
 * that do not want HTML headers.
 *
 * @param  string       $pagetitle The page title
 * @param  string|array $message   A message show to the user
 * @example
 * pagetop('Title', array('My error message', E_ERROR));
 * echo 'My page contents.';
 */

function pagetop($pagetitle = '', $message = '')
{
    global $siteurl, $sitename, $txp_user, $event, $step, $app_mode, $theme, $textarray_script, $file_max_upload_size;

    header('Content-Security-Policy: '.CONTENT_SECURITY_POLICY);
    header('X-Frame-Options: '.X_FRAME_OPTIONS);

    if ($app_mode == 'async') {
        return;
    }

    $area = gps('area');
    $event = (!$event) ? 'article' : $event;
    $bm = gps('bm');

    $areas = areas();
    $area = false;

    foreach ($areas as $k => $v) {
        if (in_array($event, $v)) {
            $area = $k;
            break;
        }
    }

    if (gps('logout')) {
        $body_id = 'page-logout';
        $area = 'login';
    } elseif (!$txp_user) {
        $body_id = 'page-login';
        $area = 'login';
    } else {
        $body_id = 'page-'.txpspecialchars($event);
    }

    gTxtScript(array(
        'are_you_sure',
        'close',
        'cookies_must_be_enabled',
        'documentation',
        'form_submission_error',
        'help',
        'list_options',
        'ok',
        'plugin_help',
        'publish',
        'save',
        'select',
        'toggle_all_selected',
        'upload_err_form_size',
        'with_selected_option',
    ));

    $lang_direction = gTxt('lang_dir');
    $lang_ui = get_pref('language_ui', LANG);

    if (!in_array($lang_direction, array('ltr', 'rtl'))) {
        // Apply biased default for missing translations.
        $lang_direction = 'ltr';
    } ?><!DOCTYPE html>
<html lang="<?php echo txpspecialchars($lang_ui); ?>" dir="<?php echo $lang_direction; ?>">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow">
<title><?php echo admin_title($pagetitle)?></title><?php echo
    script_js('vendors/jquery/jquery/jquery.js', TEXTPATTERN_SCRIPT_URL).
    script_js('vendors/jquery/jquery-ui/jquery-ui.js', TEXTPATTERN_SCRIPT_URL).
    script_js('vendors/blueimp/fileupload/jquery.fileupload.js', TEXTPATTERN_SCRIPT_URL, array("file, image")).
    script_js(
        'var textpattern = '.json_encode(
            array(
                '_txp_uid' => get_pref('blog_uid'),
                'event' => $event,
                'step' => $step,
                '_txp_token' => form_token(),
                'ajax_timeout' => (int) AJAX_TIMEOUT,
                'prefs' => array(
                    'max_file_size' => $file_max_upload_size,
                    'max_upload_size' => real_max_upload_size(0),
                    'production_status' => get_pref('production_status'),
                    'do_spellcheck' => get_pref(
                        'do_spellcheck',
                        '#page-article #body, #page-article #title,'.
                        '#page-image #image_alt_text, #page-image #caption,'.
                        '#page-file #description,'.
                        '#page-link #link-title, #page-link #link-description'
                    ),
                    'language_ui' => get_pref(
                        'language_ui',
                        TEXTPATTERN_DEFAULT_LANG
                    ),
                    'message' => '<span class="ui-icon ui-icon-{status}"></span> {message}',
                    'messagePane' => '<span class="messageflash {status}" role="alert" aria-live="assertive">
    {message}
    <a class="close" role="button" title="{close}" href="#close"><span class="ui-icon ui-icon-close">{close}</span></a>
</span>'
                ),
                'textarray' => (object) null,
            ),
            TEXTPATTERN_JSON
        ).';'
    ).
    script_js('textpattern.js', TEXTPATTERN_SCRIPT_URL).n;

echo script_js("
    $(function() {
        if (!textpattern.version || !'".txp_version."'.match(textpattern.version)) {
            alert('Please force-reload the page or clear your browser caches.')
        }
    })", false);
echo $theme->html_head();
echo $theme->html_head_custom();
    callback_event('admin_side', 'head_end'); ?>
</head>
<body class="not-ready <?php echo $area; ?>" id="<?php echo $body_id; ?>">
<noscript>Please enable JavaScript in your browser to use this application.</noscript>
<a class="txp-skip-link" href="#txp-main"><?php echo gTxt('go_content') ?></a>
<header class="txp-header">
<?php callback_event('admin_side', 'pagetop');
    $theme->set_state($area, $event, $bm, $message);
    echo pluggable_ui('admin_side', 'header', $theme->header());
    callback_event('admin_side', 'pagetop_end');
    echo n.'</header><!-- /txp-header -->'.
        n.'<main id="txp-main" class="txp-body">'.
        n.'<div class="messagepane" id="messagepane">'.$theme->announce($message).'</div>';
    callback_event('admin_side', 'main_content');
}

/**
 * Return the HTML &lt;title&gt; contents for an admin-side page.
 *
 * The rendered title can be customised via a 'admin_side > html_title'
 * pluggable UI callback event.
 *
 * @param  string $pagetitle Specific page title part
 * @return string
 * @since  4.6.0
 */

function admin_title($pagetitle)
{
    global $sitename;

    if ((string) $pagetitle === '') {
        $title = gTxt('untitled');
    } else {
        $title = $pagetitle;
    }

    $title = escape_title($title).' - '.txpspecialchars($sitename).' &#124; Textpattern CMS';

    return pluggable_ui('admin_side', 'html_title', $title, compact('pagetitle'));
}

/**
 * Creates an area tab.
 *
 * This can be used to create table based navigation bars.
 *
 * @param      string $label
 * @param      string $event
 * @param      string $tarea
 * @param      string $area
 * @return     string HTML table column
 * @deprecated in 4.6.0
 */

function areatab($label, $event, $tarea, $area)
{
    $tc = ($area == $event) ? 'tabup' : 'tabdown';
    $atts = ' class="'.$tc.'"';
    $hatts = ' href="?event='.$tarea.'"';

    return tda(tag($label, 'a', $hatts), $atts);
}

/**
 * Creates a secondary area tab.
 *
 * This can be used to create table based navigation bars.
 *
 * @param      string $label
 * @param      string $tabevent
 * @param      string $event
 * @return     string HTML table column
 * @deprecated in 4.6.0
 */

function tabber($label, $tabevent, $event)
{
    $tc = ($event == $tabevent) ? 'tabup' : 'tabdown2';
    $out = '<td class="'.$tc.'"><a href="?event='.$tabevent.'">'.$label.'</a></td>';

    return $out;
}

/**
 * Creates a table based navigation bar row.
 *
 * This can be used to create table based navigation bars.
 *
 * @param      string $area
 * @param      string $event
 * @return     string HTML table columns
 * @deprecated in 4.6.0
 */

function tabsort($area, $event)
{
    if ($area) {
        $areas = areas();

        $out = array();

        foreach ($areas[$area] as $a => $b) {
            if (has_privs($b)) {
                $out[] = tabber($a, $b, $event, 2);
            }
        }

        return ($out) ? join('', $out) : '';
    }

    return '';
}

/**
 * Gets the main menu structure as an array.
 *
 * @return array
 * @example
 * print_r(
 *     areas()
 * );
 */

function areas()
{
    global $plugin_areas;

    $adminString = has_privs('admin.list') ? gTxt('tab_site_admin') : gTxt('tab_site_account');

    $areas['start'] = array(
    );

    $areas['content'] = array(
        gTxt('tab_write')    => 'article',
        gTxt('tab_list')     => 'list',
        gTxt('tab_image')    => 'image',
        gTxt('tab_file')     => 'file',
        gTxt('tab_link')     => 'link',
        gTxt('tab_organise') => 'category',
    );

    $areas['presentation'] = array(
        gTxt('tab_skin')     => 'skin',
        gTxt('tab_sections') => 'section',
        gTxt('tab_pages')    => 'page',
        gTxt('tab_forms')    => 'form',
        gTxt('tab_style')    => 'css',
    );

    $areas['admin'] = array(
        gTxt('tab_diagnostics') => 'diag',
        gTxt('tab_preferences') => 'prefs',
        gTxt('tab_languages')   => 'lang',
        $adminString            => 'admin',
        gTxt('tab_plugins')     => 'plugin',
    );

    $areas['extensions'] = array(
    );

    if (get_pref('use_comments', 0)) {
        $areas['content'][gTxt('tab_comments')] = 'discuss';
    }

    if (get_pref('logging') !== 'none' && get_pref('expire_logs_after')) {
        $areas['admin'][gTxt('tab_logs')] = 'log';
    }

    if (is_array($plugin_areas)) {
        $areas = array_merge_recursive($areas, $plugin_areas);
    }

    return $areas;
}

/**
 * Creates an admin-side main menu as a &lt;select&gt; dropdown.
 *
 * @param  mixed  $inline Is not used.
 * @return string A HTML form
 * @example
 * echo navPop();
 */

function navPop($inline = '')
{
    $areas = areas();

    $out = array();

    foreach ($areas as $a => $b) {
        if (!has_privs('tab.'.$a)) {
            continue;
        }

        if (count($b) > 0) {
            $out[] = n.'<optgroup label="'.gTxt('tab_'.$a).'">';

            foreach ($b as $c => $d) {
                if (has_privs($d)) {
                    $out[] = n.'<option value="'.txpspecialchars($d).'">'.strip_tags($c).'</option>';
                }
            }

            $out[] = n.'</optgroup>';
        }
    }

    if ($out) {
        return n.'<form class="navpop" method="get" action="index.php">'.
            n.'<select name="event" data-submit-on="change">'.
            n.'<option>'.gTxt('go').'&#8230;</option>'.
            join('', $out).
            n.'</select>'.
            n.'</form>';
    }
}

/**
 * Generates a button link.
 *
 * @param      string $label
 * @param      string $link
 * @deprecated in 4.6.0
 */

function button($label, $link)
{
    return '<span style="margin-right:2em"><a href="?event='.$link.'">'.$label.'</a></span>';
}

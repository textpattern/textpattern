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
 * Write panel.
 *
 * @package Admin\Article
 */

use Textpattern\Validator\BlankConstraint;
use Textpattern\Validator\CategoryConstraint;
use Textpattern\Validator\ChoiceConstraint;
use Textpattern\Validator\FalseConstraint;
use Textpattern\Validator\FormConstraint;
use Textpattern\Validator\SectionConstraint;
use Textpattern\Validator\Validator;

if (!defined('txpinterface')) {
    die('txpinterface is undefined.');
}

global $vars, $statuses;

$vars = array(
    'ID',
    'Title',
    'Body',
    'Excerpt',
    'textile_excerpt',
    'Image',
    'textile_body',
    'Keywords',
    'description',
    'Status',
    'Posted',
    'Expires',
    'Section',
    'Category1',
    'Category2',
    'Annotate',
    'AnnotateInvite',
    'publish_now',
    'reset_time',
    'AuthorID',
    'sPosted',
    'LastModID',
    'sLastMod',
    'override_form',
    'from_view',
    'year',
    'month',
    'day',
    'hour',
    'minute',
    'second',
    'url_title',
    'exp_year',
    'exp_month',
    'exp_day',
    'exp_hour',
    'exp_minute',
    'exp_second',
    'sExpires',
);

$cfs = getCustomFields();

foreach ($cfs as $i => $cf_name) {
    $vars[] = "custom_$i";
}

$statuses = status_list();

if (!empty($event) and $event == 'article') {
    require_privs('article');

    $save = gps('save');

    if ($save) {
        $step = 'save';
    }

    $publish = gps('publish');

    if ($publish) {
        $step = 'publish';
    }

    if (empty($step)) {
        $step = 'create';
    }

    bouncer($step, array(
        'create'  => false,
        'publish' => true,
        'edit'    => false,
        'save'    => true,
    ));

    switch ($step) {
        case 'create':
            article_edit();
            break;
        case 'publish':
            article_post();
            break;
        case 'edit':
            article_edit();
            break;
        case 'save':
            article_save();
            break;
    }
}

/**
 * Processes sent forms and saves new articles.
 */

function article_post()
{
    global $txp_user, $vars, $prefs;

    extract($prefs);

    $incoming = array_map('assert_string', psa($vars));

    if (!has_privs('article.set_markup')) {
        $incoming['textile_body'] = $incoming['textile_excerpt'] = $use_textile;
    }

    $incoming = doSlash(textile_main_fields($incoming));
    extract($incoming);

    $msg = '';
    if ($Title or $Body or $Excerpt) {
        $is_clone = (ps('copy'));

        $Status = assert_int(ps('Status'));

        // Comments may be on, off, or disabled.
        $Annotate = (int) $Annotate;

        // Set and validate article timestamp.
        if ($publish_now == 1 || $reset_time == 1) {
            $when = "NOW()";
            $when_ts = time();
        } else {
            if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) || !is_numeric($hour) || !is_numeric($minute) || !is_numeric($second)) {
                $ts = false;
            } else {
                $ts = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
            }

            // Tracking the PHP meanders on how to return an error.
            if ($ts === false || $ts < 0) {
                article_edit(array(gTxt('invalid_postdate'), E_ERROR));

                return;
            }

            $when_ts = $ts - tz_offset($ts);
            $when = "FROM_UNIXTIME($when_ts)";
        }

        // Set and validate expiry timestamp.
        if (empty($exp_year)) {
            $expires = 0;
        } else {
            if (empty($exp_month)) {
                $exp_month = 1;
            }

            if (empty($exp_day)) {
                $exp_day = 1;
            }

            if (empty($exp_hour)) {
                $exp_hour = 0;
            }

            if (empty($exp_minute)) {
                $exp_minute = 0;
            }

            if (empty($exp_second)) {
                $exp_second = 0;
            }

            $ts = strtotime($exp_year.'-'.$exp_month.'-'.$exp_day.' '.$exp_hour.':'.$exp_minute.':'.$exp_second);
            if ($ts === false || $ts < 0) {
                article_edit(array(gTxt('invalid_expirydate'), E_ERROR));

                return;
            } else {
                $expires = $ts - tz_offset($ts);
            }
        }

        if ($expires && ($expires <= $when_ts)) {
            article_edit(array(gTxt('article_expires_before_postdate'), E_ERROR));

            return;
        }

        if ($expires) {
            $whenexpires = "FROM_UNIXTIME($expires)";
        } else {
            $whenexpires = "NULL";
        }

        $user = doSlash($txp_user);
        $Keywords = doSlash(trim(preg_replace('/( ?[\r\n\t,])+ ?/s', ',', preg_replace('/ +/', ' ', ps('Keywords'))), ', '));
        $msg = '';

        if (!has_privs('article.publish') && $Status >= STATUS_LIVE) {
            $Status = STATUS_PENDING;
        }

        if ($is_clone && $Status >= STATUS_LIVE) {
            $Status = STATUS_DRAFT;
            $url_title = '';
        }

        if (empty($url_title)) {
            $url_title = stripSpace($Title_plain, 1);
        }

        $cfq = array();
        $cfs = getCustomFields();

        foreach ($cfs as $i => $cf_name) {
            $custom_x = "custom_{$i}";
            $cfq[] = "custom_$i = '".$$custom_x."'";
        }

        $cfq = join(', ', $cfq);

        $rs = compact($vars);
        if (article_validate($rs, $msg)) {
            $ok = safe_insert(
               'textpattern',
               "Title           = '$Title',
                Body            = '$Body',
                Body_html       = '$Body_html',
                Excerpt         = '$Excerpt',
                Excerpt_html    = '$Excerpt_html',
                Image           = '$Image',
                Keywords        = '$Keywords',
                description     = '$description',
                Status          =  $Status,
                Posted          =  $when,
                Expires         =  $whenexpires,
                AuthorID        = '$user',
                LastMod         = NOW(),
                LastModID       = '$user',
                Section         = '$Section',
                Category1       = '$Category1',
                Category2       = '$Category2',
                textile_body    = '$textile_body',
                textile_excerpt = '$textile_excerpt',
                Annotate        =  $Annotate,
                override_form   = '$override_form',
                url_title       = '$url_title',
                AnnotateInvite  = '$AnnotateInvite',"
                .(($cfs) ? $cfq.',' : '').
                "uid            = '".md5(uniqid(rand(), true))."',
                feed_time       = NOW()"
            );

            if ($ok) {
                $rs['ID'] = $GLOBALS['ID'] = $ok;

                if ($is_clone) {
                    safe_update(
                        'textpattern',
                        "Title = CONCAT(Title, ' (', $ok, ')'),
                        url_title = CONCAT(url_title, '-', $ok)",
                        "ID = $ok"
                    );
                }

                if ($Status >= STATUS_LIVE) {
                    do_pings();
                    update_lastmod('article_posted', $rs);
                    now('posted', true);
                    now('expires', true);
                }

                callback_event('article_posted', '', false, $rs);
                $s = check_url_title($url_title);
                $msg = array(get_status_message($Status).' '.$s, ($s ? E_WARNING : 0));
            } else {
                unset($GLOBALS['ID']);
                $msg = array(gTxt('article_save_failed'), E_ERROR);
            }
        }
    }
    article_edit($msg);
}

/**
 * Processes sent forms and updates existing articles.
 */

function article_save()
{
    global $txp_user, $vars, $prefs;

    extract($prefs);

    $incoming = array_map('assert_string', psa($vars));

    $oldArticle = safe_row("Status, url_title, Title, textile_body, textile_excerpt,
        UNIX_TIMESTAMP(LastMod) AS sLastMod, LastModID,
        UNIX_TIMESTAMP(Posted) AS sPosted,
        UNIX_TIMESTAMP(Expires) AS sExpires",
        'textpattern', "ID = ".(int) $incoming['ID']);

    if (!(($oldArticle['Status'] >= STATUS_LIVE and has_privs('article.edit.published'))
        or ($oldArticle['Status'] >= STATUS_LIVE and $incoming['AuthorID'] === $txp_user and has_privs('article.edit.own.published'))
        or ($oldArticle['Status'] < STATUS_LIVE and has_privs('article.edit'))
        or ($oldArticle['Status'] < STATUS_LIVE and $incoming['AuthorID'] === $txp_user and has_privs('article.edit.own')))) {
        // Not allowed, you silly rabbit, you shouldn't even be here.
        // Show default editing screen.
        article_edit();

        return;
    }

    if ($oldArticle['sLastMod'] != $incoming['sLastMod']) {
        article_edit(array(gTxt('concurrent_edit_by', array('{author}' => txpspecialchars($oldArticle['LastModID']))), E_ERROR), true, true);

        return;
    }

    if (!has_privs('article.set_markup')) {
        $incoming['textile_body'] = $oldArticle['textile_body'];
        $incoming['textile_excerpt'] = $oldArticle['textile_excerpt'];
    }

    $incoming = textile_main_fields($incoming);

    extract(doSlash($incoming));
    extract(array_map('assert_int', psa(array('ID', 'Status'))));

    // Comments may be on, off, or disabled.
    $Annotate = (int) $Annotate;

    if (!has_privs('article.publish') && $Status >= STATUS_LIVE) {
        $Status = STATUS_PENDING;
    }

    // Set and validate article timestamp.
    if ($reset_time) {
        $whenposted = "Posted = NOW()";
        $when_ts = time();
    } else {
        if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) || !is_numeric($hour) || !is_numeric($minute) || !is_numeric($second)) {
            $ts = false;
        } else {
            $ts = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
        }

        if ($ts === false || $ts < 0) {
            $when = $when_ts = $oldArticle['sPosted'];
            $msg = array(gTxt('invalid_postdate'), E_ERROR);
        } else {
            $when = $when_ts = $ts - tz_offset($ts);
        }

        $whenposted = "Posted = FROM_UNIXTIME($when)";
    }

    // Set and validate expiry timestamp.
    if (empty($exp_year)) {
        $expires = 0;
    } else {
        if (empty($exp_month)) {
            $exp_month = 1;
        }

        if (empty($exp_day)) {
            $exp_day = 1;
        }

        if (empty($exp_hour)) {
            $exp_hour = 0;
        }

        if (empty($exp_minute)) {
            $exp_minute = 0;
        }

        if (empty($exp_second)) {
            $exp_second = 0;
        }

        $ts = strtotime($exp_year.'-'.$exp_month.'-'.$exp_day.' '.$exp_hour.':'.$exp_minute.':'.$exp_second);

        if ($ts === false || $ts < 0) {
            $expires = $oldArticle['sExpires'];
            $msg = array(gTxt('invalid_expirydate'), E_ERROR);
        } else {
            $expires = $ts - tz_offset($ts);
        }
    }

    if ($expires && ($expires <= $when_ts)) {
        $expires = $oldArticle['sExpires'];
        $msg = array(gTxt('article_expires_before_postdate'), E_ERROR);
    }

    if ($expires) {
        $whenexpires = "Expires = FROM_UNIXTIME($expires)";
    } else {
        $whenexpires = "Expires = NULL";
    }

    // Auto-update custom-titles according to Title, as long as unpublished and
    // NOT customised.
    if (empty($url_title)
        || (($oldArticle['Status'] < STATUS_LIVE)
        && ($oldArticle['url_title'] === $url_title)
        && ($oldArticle['url_title'] === stripSpace($oldArticle['Title'], 1))
        && ($oldArticle['Title'] !== $Title)
    )) {
        $url_title = stripSpace($Title_plain, 1);
    }

    $Keywords = doSlash(trim(preg_replace('/( ?[\r\n\t,])+ ?/s', ',', preg_replace('/ +/', ' ', ps('Keywords'))), ', '));
    $user = doSlash($txp_user);

    $cfq = array();
    $cfs = getCustomFields();

    foreach ($cfs as $i => $cf_name) {
        $custom_x = "custom_{$i}";
        $cfq[] = "custom_$i = '".$$custom_x."'";
    }

    $cfq = join(', ', $cfq);

    $rs = compact($vars);
    if (article_validate($rs, $msg)) {
        if (safe_update('textpattern',
           "Title           = '$Title',
            Body            = '$Body',
            Body_html       = '$Body_html',
            Excerpt         = '$Excerpt',
            Excerpt_html    = '$Excerpt_html',
            Keywords        = '$Keywords',
            description     = '$description',
            Image           = '$Image',
            Status          =  $Status,
            LastMod         =  NOW(),
            LastModID       = '$user',
            Section         = '$Section',
            Category1       = '$Category1',
            Category2       = '$Category2',
            Annotate        =  $Annotate,
            textile_body    = '$textile_body',
            textile_excerpt = '$textile_excerpt',
            override_form   = '$override_form',
            url_title       = '$url_title',
            AnnotateInvite  = '$AnnotateInvite',"
            .(($cfs) ? $cfq.',' : '').
            "$whenposted,
            $whenexpires",
            "ID = $ID"
        )) {
            if ($Status >= STATUS_LIVE && $oldArticle['Status'] < STATUS_LIVE) {
                do_pings();
            }

            if ($Status >= STATUS_LIVE || $oldArticle['Status'] >= STATUS_LIVE) {
                update_lastmod('article_saved', $rs);
            }

            now('posted', true);
            now('expires', true);
            callback_event('article_saved', '', false, $rs);

            if (empty($msg)) {
                $s = check_url_title($url_title);
                $msg = array(get_status_message($Status).' '.$s, $s ? E_WARNING : 0);
            }
        } else {
            $msg = array(gTxt('article_save_failed'), E_ERROR);
        }
    }
    article_edit($msg, false, true);
}

/**
 * Renders article editor form.
 *
 * @param string|array $message          The activity message
 * @param bool         $concurrent       Treat as a concurrent save
 * @param bool         $refresh_partials Whether refresh partial contents
 */

function article_edit($message = '', $concurrent = false, $refresh_partials = false)
{
    global $vars, $txp_user, $prefs, $event, $view;

    extract($prefs);

    /*
    $partials is an array of:
    $key => array (
        'mode' => {PARTIAL_STATIC | PARTIAL_VOLATILE | PARTIAL_VOLATILE_VALUE},
        'selector' => $DOM_selector or array($selector, $fragment) of $DOM_selectors,
         'cb' => $callback_function,
         'html' => $return_value_of_callback_function (need not be intialised here)
    )
    */
    $partials = array(
        // HTML 'Title' field (in <head>).
        'html_title'   => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'title',
            'cb'       => 'article_partial_html_title',
        ),
        // 'Text/HTML/Preview' links region.
        'view_modes' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#view_modes',
            'cb'       => 'article_partial_view_modes',
        ),
        // 'Title' region.
        'title' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.title',
            'cb'       => 'article_partial_title',
        ),
        // 'Title' field.
        'title_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#title',
            'cb'       => 'article_partial_title_value',
        ),
        // 'Body' region.
        'body' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.body',
            'cb'       => 'article_partial_body',
        ),
        // 'Excerpt' region.
        'excerpt' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.excerpt',
            'cb'       => 'article_partial_excerpt',
        ),
        // 'Author' region.
        'author' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'p.author',
            'cb'       => 'article_partial_author',
        ),
        // 'Posted' value.
        'sPosted' => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '[name=sPosted]',
            'cb'       => 'article_partial_value',
        ),
        // 'Last modified' value.
        'sLastMod' => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '[name=sLastMod]',
            'cb'       => 'article_partial_value',
        ),
        // 'Duplicate' link.
        'article_clone' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#article_partial_article_clone',
            'cb'       => 'article_partial_article_clone',
        ),
        // 'View' link.
        'article_view' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#article_partial_article_view',
            'cb'       => 'article_partial_article_view',
        ),
        // 'Previous/Next' article links region.
        'article_nav' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'nav.nav-tertiary',
            'cb'       => 'article_partial_article_nav',
        ),
        // 'Status' region.
        'status' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#txp-container-status',
            'cb'       => 'article_partial_status',
        ),
        // 'Section' region.
        'section' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.section',
            'cb'       => 'article_partial_section',
        ),
        // Categories region.
        'categories' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => '#categories_group',
            'cb'       => 'article_partial_categories',
        ),
        // Publish date/time region.
        'posted' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#publish-datetime-group',
            'cb'       => 'article_partial_posted',
        ),
        // Expire date/time region.
        'expires' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#expires-datetime-group',
            'cb'       => 'article_partial_expires',
        ),
        // Meta 'URL-only title' region.
        'url_title' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.url-title',
            'cb'       => 'article_partial_url_title',
        ),
        // Meta 'URL-only title' field.
        'url_title_value' => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#url-title',
            'cb'       => 'article_partial_url_title_value',
        ),
        // Meta 'Description' region.
        'description' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.description',
            'cb'       => 'article_partial_description',
        ),
        // Meta 'Description' field.
        'description_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#description',
            'cb'       => 'article_partial_description_value',
        ),
        // Meta 'Keywords' region.
        'keywords' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'div.keywords',
            'cb'       => 'article_partial_keywords',
        ),
        // Meta 'Keywords' field.
        'keywords_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#keywords',
            'cb'       => 'article_partial_keywords_value',
        ),
        // 'Comment options' section.
        'comments' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#write-comments',
            'cb'       => 'article_partial_comments',
        ),
        // 'Article image' section.
        'image' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => array('#txp-image-group .txp-container', '.txp-container'),
            'cb'       => 'article_partial_image',
        ),
        // 'Custom fields' section.
        'custom_fields' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => array('#txp-custom-field-group-content .txp-container', '.txp-container'),
            'cb'       => 'article_partial_custom_fields',
        ),
        // 'Text formatting help' section.
        'sidehelp' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'ul.textile',
            'cb'       => 'article_partial_sidehelp',
        ),
        // 'Recent articles' values.
        'recent_articles' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => array('#txp-recent-group-content .txp-container', '.txp-container'),
            'cb'       => 'article_partial_recent_articles',
        ),
    );

    // Add partials for custom fields (and their values which is redundant by
    // design, for plugins).
    global $cfs;

    foreach ($cfs as $k => $v) {
        $partials["custom_field_{$k}"] = array(
            'mode'     => PARTIAL_STATIC,
            'selector' => "p.custom-field.custom-{$k}",
            'cb'       => 'article_partial_custom_field',
        );
        $partials["custom_{$k}"] = array(
            'mode'     => PARTIAL_STATIC,
            'selector' => "#custom-{$k}",
            'cb'       => 'article_partial_value',
        );
    }

    extract(gpsa(array(
        'view',
        'from_view',
        'step',
    )));

    // Newly-saved article.
    if (!empty($GLOBALS['ID'])) {
        $ID = $GLOBALS['ID'];
        $step = 'edit';
    } else {
        $ID = gps('ID');
    }

    // Switch to 'text' view upon page load and after article post.
    if (!$view || gps('save') || gps('publish')) {
        $view = 'text';
    }

    if (!$step) {
        $step = "create";
    }

    if ($step == "edit"
        && $view == "text"
        && !empty($ID)
        && $from_view != 'preview'
        && $from_view != 'html'
        && !$concurrent) {
        $pull = true; // It's an existing article - off we go to the database.
        $ID = assert_int($ID);

        $rs = safe_row(
            "*, UNIX_TIMESTAMP(Posted) AS sPosted,
            UNIX_TIMESTAMP(Expires) AS sExpires,
            UNIX_TIMESTAMP(LastMod) AS sLastMod",
            'textpattern',
            "ID = $ID"
        );

        if (empty($rs)) {
            return;
        }

        $rs['reset_time'] = $rs['publish_now'] = false;
    } else {
        $pull = false; // Assume they came from post.

        if ($from_view == 'preview' or $from_view == 'html') {
            $store_out = array();
            $store = json_decode(base64_decode(ps('store')), true);

            foreach ($vars as $var) {
                if (isset($store[$var])) {
                    $store_out[$var] = $store[$var];
                }
            }
        } else {
            $store_out = gpsa($vars);

            if ($concurrent) {
                $store_out['sLastMod'] = safe_field("UNIX_TIMESTAMP(LastMod) AS sLastMod", 'textpattern', "ID = $ID");
            }

            if (!has_privs('article.set_markup') && !empty($ID)) {
                $oldArticle = safe_row("textile_body, textile_excerpt", 'textpattern', "ID = $ID");
                if (!empty($oldArticle)) {
                    $store_out['textile_body'] = $oldArticle['textile_body'];
                    $store_out['textile_excerpt'] = $oldArticle['textile_excerpt'];
                }
            }
        }

        // Use preferred Textfilter as default and fallback.
        $hasfilter = new \Textpattern\Textfilter\Constraint(null);
        $validator = new Validator();

        foreach (array('textile_body', 'textile_excerpt') as $k) {
            $hasfilter->setValue($store_out[$k]);
            $validator->setConstraints($hasfilter);
            if (!$validator->validate()) {
                $store_out[$k] = $use_textile;
            }
        }

        $rs = textile_main_fields($store_out);

        if (!empty($rs['exp_year'])) {
            if (empty($rs['exp_month'])) {
                $rs['exp_month'] = 1;
            }

            if (empty($rs['exp_day'])) {
                $rs['exp_day'] = 1;
            }

            if (empty($rs['exp_hour'])) {
                $rs['exp_hour'] = 0;
            }

            if (empty($rs['exp_minute'])) {
                $rs['exp_minute'] = 0;
            }

            if (empty($rs['exp_second'])) {
                $rs['exp_second'] = 0;
            }

            $rs['sExpires'] = safe_strtotime($rs['exp_year'].'-'.$rs['exp_month'].'-'.$rs['exp_day'].' '.
                $rs['exp_hour'].':'.$rs['exp_minute'].':'.$rs['exp_second']);
        }

        if (!empty($rs['year'])) {
            $rs['sPosted'] = safe_strtotime($rs['year'].'-'.$rs['month'].'-'.$rs['day'].' '.
                $rs['hour'].':'.$rs['minute'].':'.$rs['second']);
        }
    }

    $validator = new Validator(new SectionConstraint($rs['Section']));
    if (!$validator->validate()) {
        $rs['Section'] = getDefaultSection();
    }

    extract($rs);

    $GLOBALS['step'] = $step;

    if ($step != 'create' && isset($sPosted)) {
        // Previous record?
        $rs['prev_id'] = checkIfNeighbour('prev', $sPosted, $ID);

        // Next record?
        $rs['next_id'] = checkIfNeighbour('next', $sPosted, $ID);
    } else {
        $rs['prev_id'] = $rs['next_id'] = 0;
    }

    // Let plugins chime in on partials meta data.
    callback_event_ref('article_ui', 'partials_meta', 0, $rs, $partials);
    $rs['partials_meta'] = &$partials;

    // Get content for volatile partials.
    foreach ($partials as $k => $p) {
        if ($p['mode'] == PARTIAL_VOLATILE || $p['mode'] == PARTIAL_VOLATILE_VALUE) {
            $cb = $p['cb'];
            $partials[$k]['html'] = (is_array($cb) ? call_user_func($cb, $rs, $k) : $cb($rs, $k));
        }
    }

    if ($refresh_partials) {
        $response[] = announce($message);
        $response[] = '$("#article_form [type=submit]").val(textpattern.gTxt("save"))';

        if ($Status < STATUS_LIVE) {
            $response[] = '$("#article_form").addClass("saved").removeClass("published")';
        } else {
            $response[] = '$("#article_form").addClass("published").removeClass("saved")';
        }

        // Update the volatile partials.
        foreach ($partials as $k => $p) {
            // Volatile partials need a target DOM selector.
            if (empty($p['selector']) && $p['mode'] != PARTIAL_STATIC) {
                trigger_error("Empty selector for partial '$k'", E_USER_ERROR);
            } else {
                // Build response script.
                list($selector, $fragment) = (array)$p['selector'] + array(null, null);
                if (!isset($fragment)) {
                    $fragment = $selector;
                }
                if ($p['mode'] == PARTIAL_VOLATILE) {
                    // Volatile partials replace *all* of the existing HTML
                    // fragment for their selector with the new one.
                    $response[] = '$("'.$selector.'").replaceWith($("<div>'.escape_js($p['html']).'</div>").find("'.$fragment.'"))';
                } elseif ($p['mode'] == PARTIAL_VOLATILE_VALUE) {
                    // Volatile partial values replace the *value* of elements
                    // matching their selector.
                    $response[] = '$("'.$selector.'").val("'.escape_js($p['html']).'")';
                }
            }
        }
        send_script_response(join(";\n", $response));

        // Bail out.
        return;
    }

    foreach ($partials as $k => $p) {
        if ($p['mode'] == PARTIAL_STATIC) {
            $cb = $p['cb'];
            $partials[$k]['html'] = (is_array($cb) ? call_user_func($cb, $rs, $k) : $cb($rs, $k));
        }
    }

    $page_title = $ID ? $Title : gTxt('write');

    pagetop($page_title, $message);

    $class = array();

    if ($Status >= STATUS_LIVE) {
        $class[] = 'published';
    } elseif ($ID) {
        $class[] = 'saved';
    }

    if ($step !== 'create') {
        $class[] = 'async';
    }

    echo n.tag_start('form', array(
            'class'  => $class,
            'id'     => 'article_form',
            'name'   => 'article_form',
            'method' => 'post',
            'action' => 'index.php',
        )).
        n.'<div class="txp-layout">';

    if (!empty($store_out)) {
        echo hInput('store', base64_encode(json_encode($store_out)));
    }

    echo hInput('ID', $ID).
        eInput('article').
        sInput($step).
        hInput('sPosted', $sPosted).
        hInput('sLastMod', $sLastMod).
        hInput('AuthorID', $AuthorID).
        hInput('LastModID', $LastModID).
        n.'<input type="hidden" name="view" />';

    echo n.'<div class="txp-layout-4col-3span">'.
        hed(gTxt('tab_write'), 1, array('class' => 'txp-heading'));

    echo n.'<div role="region" id="main_content">';

    // View mode tabs.
    echo $partials['view_modes']['html'];

    // Title input.
    if ($view == 'preview') {
        echo n.'<div class="preview">'.
            graf(gTxt('title'), array('class' => 'alert-block information')).
            hed(txpspecialchars($Title), 1, ' class="title"');
    } elseif ($view == 'html') {
        echo n.'<div class="html">'.
            graf(gTxt('title'), array('class' => 'alert-block information')).
            hed(txpspecialchars($Title), 1, ' class="title"');
    } elseif ($view == 'text') {
        echo n.'<div class="text">'.$partials['title']['html'];
    }

    // Body.
    if ($view == 'preview') {
        echo n.'<div class="body">'.
                n.graf(gTxt('body'), array('class' => 'alert-block information')).
                $Body_html.
                '</div>';
    } elseif ($view == 'html') {
        echo graf(gTxt('body'), array('class' => 'alert-block information')).
            n.tag(
                tag(str_replace(array(t), array(sp.sp.sp.sp), txpspecialchars($Body_html)), 'code', array(
                    'class' => 'language-markup',
                    'dir'   => 'ltr',
                )),
                'pre', array('class' => 'body line-numbers')
            );
    } else {
        echo $partials['body']['html'];
    }

    // Excerpt.
    if ($articles_use_excerpts) {
        if ($view == 'preview') {
            echo n.'<div class="excerpt">'.
                graf(gTxt('excerpt'), array('class' => 'alert-block information')).
                $Excerpt_html.
                '</div>';
        } elseif ($view == 'html') {
            echo graf(gTxt('excerpt'), array('class' => 'alert-block information')).
                n.tag(
                    tag(str_replace(array(t), array(sp.sp.sp.sp), txpspecialchars($Excerpt_html)), 'code', array(
                        'class' => 'language-markup',
                        'dir'   => 'ltr',
                    )),
                    'pre', array('class' => 'excerpt line-numbers')
                );
        } else {
            echo $partials['excerpt']['html'];
        }
    }

    echo hInput('from_view', $view),
        n.'</div>';

    // Author.
    if ($view == "text" && $step != "create") {
        echo $partials['author']['html'];
    }

    echo n.'</div>'.// End of #main_content.
        n.'</div>'; // End of .txp-layout-4col-3span.

    // Sidebar column (only shown if in text editing view).
    if ($view == 'text') {
        echo n.'<div class="txp-layout-4col-alt">';

        // 'Publish/Save' button.
        if ($step == 'create' and empty($GLOBALS['ID'])) {
            if (has_privs('article.publish')) {
                $push_button = fInput('submit', 'publish', gTxt('publish'), 'publish');
            } else {
                $push_button = fInput('submit', 'publish', gTxt('save'), 'publish');
            }

            echo graf($push_button, array('class' => 'txp-save'));
        } elseif (
            ($Status >= STATUS_LIVE && has_privs('article.edit.published')) ||
            ($Status >= STATUS_LIVE && $AuthorID === $txp_user && has_privs('article.edit.own.published')) ||
            ($Status < STATUS_LIVE && has_privs('article.edit')) ||
            ($Status < STATUS_LIVE && $AuthorID === $txp_user && has_privs('article.edit.own'))
        ) {
            echo graf(fInput('submit', 'save', gTxt('save'), 'publish'), array('class' => 'txp-save'));
        }

        // View/Duplicate/Create new article links.
        $an_cb = href('<span class="ui-icon ui-extra-icon-new-document"></span> '.gTxt('create_new'), 'index.php?event=article', array('class' => 'txp-new'));
        $ac_cb = $rs['partials_meta']['article_clone']['cb'];
        $av_cb = $rs['partials_meta']['article_view']['cb'];

        echo($step != 'create' ? graf($an_cb.$ac_cb($rs).$av_cb($rs), array('class' => 'txp-actions')) : '');

        // Prev/next article links.
        if ($step != 'create' and ($rs['prev_id'] or $rs['next_id'])) {
            echo $partials['article_nav']['html'];
        }

        echo n.'<div role="region" id="supporting_content">';

        // 'Sort and display' section.
        echo pluggable_ui(
            'article_ui',
            'sort_display',
            wrapRegion('txp-write-sort-group', $partials['status']['html'].$partials['section']['html'].$partials['categories']['html'], '', gTxt('sort_display')),
            $rs
        );

        echo graf(
            href('<span class="ui-icon ui-icon-arrowthickstop-1-s"></span> '.gTxt('expand_all'), '#', array(
                'class'         => 'txp-expand-all',
                'aria-controls' => 'supporting_content',
            )).
            href('<span class="ui-icon ui-icon-arrowthickstop-1-n"></span> '.gTxt('collapse_all'), '#', array(
                'class'         => 'txp-collapse-all',
                'aria-controls' => 'supporting_content',
            )), array('class' => 'txp-actions')
        );

        // 'Date and time' collapsible section.

        if ($step == "create" and empty($GLOBALS['ID'])) {
            // Timestamp.
            // Avoiding modified date to disappear.

            if (!empty($store_out['year'])) {
                $persist_timestamp = safe_strtotime(
                    $store_out['year'].'-'.$store_out['month'].'-'.$store_out['day'].' '.
                    $store_out['hour'].':'.$store_out['minute'].':'.$store_out['second']
                );
            } else {
                $persist_timestamp = time();
            }

            $posted_block = pluggable_ui(
                'article_ui',
                'timestamp',
                inputLabel(
                    'year',
                    tsi('year', '%Y', $persist_timestamp, '', 'year').
                    ' <span role="separator">/</span> '.
                    tsi('month', '%m', $persist_timestamp, '', 'month').
                    ' <span role="separator">/</span> '.
                    tsi('day', '%d', $persist_timestamp, '', 'day'),
                    'publish_date',
                    array('publish_date', 'instructions_publish_date'),
                    array('class' => 'txp-form-field date posted')
                ).
                inputLabel(
                    'hour',
                    tsi('hour', '%H', $persist_timestamp, '', 'hour').
                    ' <span role="separator">:</span> '.
                    tsi('minute', '%M', $persist_timestamp, '', 'minute').
                    ' <span role="separator">:</span> '.
                    tsi('second', '%S', $persist_timestamp, '', 'second'),
                    'publish_time',
                    array('', 'instructions_publish_time'),
                    array('class' => 'txp-form-field time posted')
                ).
                n.tag(
                    checkbox('publish_now', '1', true, '', 'publish_now').
                    n.tag(gTxt('set_to_now'), 'label', array('for' => 'publish_now')),
                    'div', array('class' => 'posted-now')
                ),
                array('sPosted' => $persist_timestamp) + $rs
            );

            // Expires.

            if (!empty($store_out['exp_year'])) {
                $persist_timestamp = safe_strtotime(
                    $store_out['exp_year'].'-'.$store_out['exp_month'].'-'.$store_out['exp_day'].' '.
                    $store_out['exp_hour'].':'.$store_out['exp_minute'].':'.$store_out['second']
                );
            } else {
                $persist_timestamp = 0;
            }

            $expires_block = pluggable_ui(
                'article_ui',
                'expires',
                inputLabel(
                    'exp_year',
                    tsi('exp_year', '%Y', $persist_timestamp, '', 'exp_year').
                    ' <span role="separator">/</span> '.
                    tsi('exp_month', '%m', $persist_timestamp, '', 'exp_month').
                    ' <span role="separator">/</span> '.
                    tsi('exp_day', '%d', $persist_timestamp, '', 'exp_day'),
                    'expire_date',
                    array('expire_date', 'instructions_expire_date'),
                    array('class' => 'txp-form-field date expires')
                ).
                inputLabel(
                    'exp_hour',
                    tsi('exp_hour', '%H', $persist_timestamp, '', 'exp_hour').
                    ' <span role="separator">:</span> '.
                    tsi('exp_minute', '%M', $persist_timestamp, '', 'exp_minute').
                    ' <span role="separator">:</span> '.
                    tsi('exp_second', '%S', $persist_timestamp, '', 'exp_second'),
                    'expire_time',
                    array('', 'instructions_expire_time'),
                    array('class' => 'txp-form-field time expires')
                ),
                $rs
            );
        } else {
            // Timestamp.
            $posted_block = $partials['posted']['html'];

            // Expires.
            $expires_block = $partials['expires']['html'];
        }

        echo wrapRegion('txp-dates-group', $posted_block.$expires_block, 'txp-dates-group-content', 'date_settings', 'article_dates');

        // 'Meta' collapsible section.

        // 'URL-only title' field.
        $html_url_title = $partials['url_title']['html'];

        // 'Description' field.
        $html_description = $partials['description']['html'];

        // 'Keywords' field.
        $html_keywords = $partials['keywords']['html'];

        echo wrapRegion('txp-meta-group', $html_url_title.$html_description.$html_keywords, 'txp-meta-group-content', 'meta', 'article_meta');

        // 'Comment options' collapsible section.
        echo wrapRegion('txp-comments-group', $partials['comments']['html'], 'txp-comments-group-content', 'comment_settings', 'article_comments');

        // 'Article image' collapsible section.
        echo wrapRegion('txp-image-group', $partials['image']['html'], 'txp-image-group-content', 'article_image', 'article_image');

        // 'Custom fields' collapsible section.
        echo wrapRegion('txp-custom-field-group', $partials['custom_fields']['html'], 'txp-custom-field-group-content', 'custom', 'article_custom_field');

        // 'Advanced options' collapsible section.

        // 'Article markup'/'Excerpt markup' selection.
        if (has_privs('article.set_markup')) {
            $html_markup =
                inputLabel(
                    'markup-body',
                    pref_text('textile_body', $textile_body, 'markup-body'),
                    'article_markup',
                    array('', 'instructions_textile_body'),
                    array('class' => 'txp-form-field markup markup-body')
                ).
                inputLabel(
                    'markup-excerpt',
                    pref_text('textile_excerpt', $textile_excerpt, 'markup-excerpt'),
                    'excerpt_markup',
                    array('', 'instructions_textile_excerpt'),
                    array('class' => 'txp-form-field markup markup-excerpt')
                );
        } else {
            $html_markup = '';
        }

        $html_markup = pluggable_ui('article_ui', 'markup', $html_markup, $rs);

        // 'Override form' selection.
        $form_pop = $allow_form_override ? form_pop($override_form, 'override-form') : '';
        $html_override = $form_pop
            ? pluggable_ui('article_ui', 'override',
                inputLabel(
                    'override-form',
                    $form_pop,
                    'override_default_form',
                    array('override_form', 'instructions_override_form'),
                    array('class' => 'txp-form-field override-form')
                ),
                $rs)
            : '';

        echo wrapRegion('txp-advanced-group', $html_markup.$html_override, 'txp-advanced-group-content', 'advanced_options', 'article_advanced');

        // Custom menu entries.
        echo pluggable_ui('article_ui', 'extend_col_1', '', $rs);

        // 'Text formatting help' collapsible section.
        echo wrapRegion('txp-textfilter-group', $partials['sidehelp']['html'], 'txp-textfilter-group-content', 'textfilter_help', 'article_textfilter_help');

        // 'Recent articles' collapsible section.
        echo wrapRegion('txp-recent-group', $partials['recent_articles']['html'], 'txp-recent-group-content', 'recent_articles', 'article_recent');

        echo n.'</div>'. // End of #supporting_content.
            n.'</div>'; // End of .txp-layout-4col-alt.
    }

    echo tInput().
        n.'</div>'. // End of .txp-layout.
        n.'</form>';
}

/**
 * Renders a custom field.
 *
 * @param  int    $num     The custom field number
 * @param  string $field   The label
 * @param  string $content The field contents
 * @return string HTML form field
 */

function custField($num, $field, $content)
{
    return inputLabel(
        'custom-'.$num,
        fInput('text', 'custom_'.$num, $content, '', '', '', INPUT_REGULAR, '', 'custom-'.$num),
        $field,
        array('', 'instructions_custom_'.$num),
        array('class' => 'txp-form-field custom-field custom-'.$num)
    );
}

/**
 * Gets the ID of the next or the previous article.
 *
 * @param  string $whichway Either '&lt;' or '&gt;'
 * @param  int    Unix timestamp
 * @param  int    pivot article ID
 * @return int
 */

function checkIfNeighbour($whichway, $sPosted, $ID = 0)
{
    // Eventual backward compatibility.
    if (empty($ID)) {
        $ID = !empty($GLOBALS['ID']) ? $GLOBALS['ID'] : gps('ID');
    }
    $sPosted = assert_int($sPosted);
    $ID = assert_int($ID);
    $dir = ($whichway == 'prev') ? '<' : '>';
    $ord = ($whichway == 'prev') ? "DESC" : "ASC";

    return safe_field("ID", 'textpattern',
        "Posted $dir FROM_UNIXTIME($sPosted) OR Posted = FROM_UNIXTIME($sPosted) AND ID $dir $ID ORDER BY Posted $ord, ID $ord LIMIT 1");
}

/**
 * Renders an article status field.
 *
 * @param  int $status Selected status
 * @return string HTML
 */

function status_display($status)
{
    global $statuses;

    if (!$status) {
        $status = get_pref('default_publish_status', STATUS_LIVE);
    }

    return inputLabel(
        'status',
        selectInput('Status', $statuses, $status, false, '', 'status'),
        'status',
        array('', 'instructions_status'),
        array('class' => 'txp-form-field status')
    );
}

/**
 * Renders a section field.
 *
 * @param  string $Section The selected section
 * @param  string $id      The HTML id
 * @return string HTML &lt;select&gt; input
 */

function section_popup($Section, $id)
{
    $rs = safe_rows("name, title", 'txp_section', "name != 'default' ORDER BY title ASC, name ASC");

    if ($rs) {
        $options = array();

        foreach ($rs as $a) {
            $options[$a['name']] = $a['title'];
        }

        return selectInput('Section', $options, $Section, false, '', $id);
    }

    return false;
}

/**
 * Renders a category field.
 *
 * @param  string $name The Name of the field
 * @param  string $val  The selected option
 * @param  string $id   The HTML id
 * @return string HTML &lt;select&gt; input
 */

function category_popup($name, $val, $id)
{
    $rs = getTree('root', 'article');

    if ($rs) {
        return treeSelectInput($name, $rs, $val, $id, 35);
    }

    return false;
}

/**
 * Renders a view tab.
 *
 * @param  string $tabevent Target view
 * @param  string $view     The current view
 * @return string HTML
 */

function tab($tabevent, $view)
{
    $state = ($view == $tabevent) ? 'active' : '';
    $pressed = ($view == $tabevent) ? 'true' : 'false';

    $link = href(gTxt('view_'.$tabevent.'_short'), '#', array(
        'data-view-mode' => $tabevent,
        'title'          => gTxt('view_'.$tabevent),
        'aria-pressed'   => $pressed,
        'role'           => 'button',
    ));

    return n.tag($link, 'li', array(
        'class' => $state,
        'id'    => 'tab-'.$tabevent,
    ));
}

/**
 * Gets the name of the default section.
 *
 * @return string The section
 */

function getDefaultSection()
{
    return get_pref('default_section');
}

/**
 * Renders 'override form' field.
 *
 * @param  string $form The selected form
 * @param  string $id   The HTML id
 * @return string HTML &lt;select&gt; input
 */

function form_pop($form, $id)
{
    $rs = safe_column("name", 'txp_form', "type = 'article' AND name != 'default' ORDER BY name");

    if ($rs) {
        return selectInput('override_form', $rs, $form, true, '', $id);
    }
}

/**
 * Checks URL title for duplicates.
 *
 * @param  string $url_title The URL title
 * @return string Localised feedback message, or an empty string
 */

function check_url_title($url_title)
{
    // Check for blank or previously used identical url-titles.
    if (strlen($url_title) === 0) {
        return gTxt('url_title_is_blank');
    } else {
        $url_title_count = safe_count('textpattern', "url_title = '$url_title'");

        if ($url_title_count > 1) {
            return gTxt('url_title_is_multiple', array('{count}' => $url_title_count));
        }
    }

    return '';
}

/**
 * Translates a status ID to a feedback message.
 *
 * This message is displayed when an article is saved.
 *
 * @param  int $Status The status
 * @return string The status message
 */

function get_status_message($Status)
{
    switch ($Status) {
        case STATUS_PENDING:
            return gTxt("article_saved_pending");
        case STATUS_HIDDEN:
            return gTxt("article_saved_hidden");
        case STATUS_DRAFT:
            return gTxt("article_saved_draft");
        default:
            return gTxt('article_posted');
    }
}

/**
 * Parses article fields using Textile.
 *
 * @param  array $incoming
 * @return array
 */

function textile_main_fields($incoming)
{
    $textile = new \Textpattern\Textile\Parser();

    $incoming['Title_plain'] = trim($incoming['Title']);
    $incoming['Title_html'] = ''; // not used
    $incoming['Title'] = $textile->textileEncode($incoming['Title_plain']);

    $incoming['Body_html'] = Txp::get('\Textpattern\Textfilter\Registry')->filter(
        $incoming['textile_body'],
        $incoming['Body'],
        array('field' => 'Body', 'options' => array('lite' => false), 'data' => $incoming)
    );

    $incoming['Excerpt_html'] = Txp::get('\Textpattern\Textfilter\Registry')->filter(
        $incoming['textile_excerpt'],
        $incoming['Excerpt'],
        array('field' => 'Excerpt', 'options' => array('lite' => false), 'data' => $incoming)
    );

    return $incoming;
}

/**
 * Pings Ping-O-Matic when an article is published.
 */

function do_pings()
{
    global $prefs, $production_status;

    // Only ping for Live sites.
    if ($production_status !== 'live') {
        return;
    }

    include_once txpath.'/lib/IXRClass.php';

    callback_event('ping');

    if ($prefs['ping_weblogsdotcom'] == 1) {
        $wl_client = new IXR_Client('http://rpc.pingomatic.com/');
        $wl_client->query('weblogUpdates.ping', $prefs['sitename'], hu);
    }
}

/**
 * Renders the &lt;title&gt; element for the 'Write' page.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_html_title($rs)
{
    return tag(admin_title($rs['Title']), 'title');
}

/**
 * Renders article formatting tips.
 *
 * The rendered widget can be customised via the 'article_ui > sidehelp'
 * pluggable UI callback event.
 *
 * @param array $rs Article data
 */

function article_partial_sidehelp($rs)
{
    // Show markup help for both body and excerpt if they are different.
    $help = Txp::get('\Textpattern\Textfilter\Registry')->getHelp($rs['textile_body']);

    if ($rs['textile_body'] != $rs['textile_excerpt']) {
        $help .= Txp::get('\Textpattern\Textfilter\Registry')->getHelp($rs['textile_excerpt']);
    }

    return pluggable_ui('article_ui', 'sidehelp', $help, $rs);
}

/**
 * Renders article title partial.
 *
 * The rendered widget can be customised via the 'article_ui > title'
 * pluggable UI callback event.
 *
 * @param array $rs Article data
 */

function article_partial_title($rs)
{
    global $step;

    $out = inputLabel(
        'title',
        fInput('text', 'Title', preg_replace("/&amp;(?![#a-z0-9]+;)/i", "&", $rs['Title']), '', '', '', INPUT_LARGE, '', 'title'),
        'title',
        array('title', 'instructions_title'),
        array('class' => 'txp-form-field title')
    );

    return pluggable_ui('article_ui', 'title', $out, $rs);
}

/**
 * Gets article's title from the given article data set.
 *
 * @param  array $rs Article data
 * @return string
 */

function article_partial_title_value($rs)
{
    return preg_replace("/&amp;(?![#a-z0-9]+;)/i", "&", $rs['Title']);
}

/**
 * Renders author partial.
 *
 * The rendered widget can be customised via the 'article_ui > author'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_author($rs)
{
    extract($rs);
    $out = n.'<p class="author"><small>'.gTxt('posted_by').': '.txpspecialchars($AuthorID).' &#183; '.safe_strftime('%d %b %Y &#183; %X', $sPosted);

    if ($sPosted != $sLastMod) {
        $out .= br.gTxt('modified_by').': '.txpspecialchars($LastModID).' &#183; '.safe_strftime('%d %b %Y &#183; %X', $sLastMod);
    }

    $out .= '</small></p>';

    return pluggable_ui('article_ui', 'author', $out, $rs);
}

/**
 * Renders custom field partial.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_custom_field($rs, $key)
{
    global $prefs;
    extract($prefs);

    preg_match('/custom_field_([0-9]+)/', $key, $m);
    $custom_x_set = "custom_{$m[1]}_set";
    $custom_x = "custom_{$m[1]}";

    return ($$custom_x_set !== '' ? custField($m[1], $$custom_x_set,  $rs[$custom_x]) : '');
}

/**
 * Renders URL title partial.
 *
 * The rendered widget can be customised via the 'article_ui > url_title'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_url_title($rs)
{
    $out = inputLabel(
        'url-title',
        fInput('text', 'url_title', article_partial_url_title_value($rs), '', '', '', INPUT_REGULAR, '', 'url-title'),
        'url_title',
        array('url_title', 'instructions_url_title'),
        array('class' => 'txp-form-field url-title')
    );

    return pluggable_ui('article_ui', 'url_title', $out, $rs);
}

/**
 * Gets URL title from the given article data set.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_url_title_value($rs)
{
    return $rs['url_title'];
}

/**
 * Renders description partial.
 *
 * The rendered widget can be customised via the 'article_ui > description'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_description($rs)
{
    $out = inputLabel(
        'description',
        '<textarea id="description" name="description" cols="'.INPUT_MEDIUM.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.txpspecialchars(article_partial_description_value($rs)).'</textarea>',
        'description',
        array('description', 'instructions_description'),
        array('class' => 'txp-form-field txp-form-field-textarea description')
    );

    return pluggable_ui('article_ui', 'description', $out, $rs);
}

/**
 * Gets description from the given article data set.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_description_value($rs)
{
    return $rs['description'];
}

/**
 * Renders keywords partial.
 *
 * The rendered widget can be customised via the 'article_ui > keywords'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_keywords($rs)
{
    $out = inputLabel(
        'keywords',
        '<textarea id="keywords" name="Keywords" cols="'.INPUT_MEDIUM.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.txpspecialchars(article_partial_keywords_value($rs)).'</textarea>',
        'keywords',
        array('keywords', 'instructions_keywords'),
        array('class' => 'txp-form-field txp-form-field-textarea keywords')
    );

    return pluggable_ui('article_ui', 'keywords', $out, $rs);
}

/**
 * Gets keywords from the given article data set.
 *
 * @param  array $rs Article data
 * @return string
 */

function article_partial_keywords_value($rs)
{
    // Separate keywords by a comma plus at least one space.
    return preg_replace('/,(\S)/', ', $1', $rs['Keywords']);
}

/**
 * Renders article image partial.
 *
 * The rendered widget can be customised via the 'article_ui > article_image'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_image($rs)
{
    $default = inputLabel(
        'article-image',
        fInput('text', 'Image', $rs['Image'], '', '', '', INPUT_REGULAR, '', 'article-image'),
        'article_image',
        array('article_image', 'instructions_article_image'),
        array('class' => 'txp-form-field article-image')
    );

    return tag(pluggable_ui('article_ui', 'article_image', $default, $rs), 'div', array('class' => 'txp-container'));
}

/**
 * Renders all custom fields in one partial.
 *
 * The rendered widget can be customised via the 'article_ui > custom_fields'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_custom_fields($rs)
{
    global $cfs;
    $cf = '';

    foreach ($cfs as $k => $v) {
        $cf .= article_partial_custom_field($rs, "custom_field_{$k}");
    }

    return tag(pluggable_ui('article_ui', 'custom_fields', $cf, $rs), 'div', array('class' => 'txp-container'));
}

/**
 * Renders &lt;ol&gt; list of recent articles.
 *
 * The rendered widget can be customised via the 'article_ui > recent_articles'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_recent_articles($rs)
{
    $recents = safe_rows_start("Title, ID", 'textpattern', "1 = 1 ORDER BY LastMod DESC LIMIT ".(int) WRITE_RECENT_ARTICLES_COUNT);
    $ra = '';

    if ($recents && numRows($recents)) {
        $ra = '<ol class="recent">';

        while ($recent = nextRow($recents)) {
            if ($recent['Title'] === '') {
                $recent['Title'] = gTxt('untitled').sp.$recent['ID'];
            }

            $ra .= n.'<li class="recent-article">'.
                href(escape_title($recent['Title']), '?event=article'.a.'step=edit'.a.'ID='.$recent['ID']).
                '</li>';
        }

        $ra .= '</ol>';
    }

    return tag(pluggable_ui('article_ui', 'recent_articles', $ra, $rs), 'div', array('class' => 'txp-container'));
}

/**
 * Renders article 'duplicate' link.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_article_clone($rs)
{
    extract($rs);

    return n.href('<span class="ui-icon ui-icon-copy"></span> '.gTxt('duplicate'), '#', array(
        'class' => 'txp-clone',
        'id'    => 'article_partial_article_clone',
    ));
}

/**
 * Renders article 'view' link.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_article_view($rs)
{
    extract($rs);

    if ($Status != STATUS_LIVE and $Status != STATUS_STICKY) {
        $url = '?txpreview='.intval($ID).'.'.time(); // Article ID plus cachebuster.
    } else {
        include_once txpath.'/publish/taghandlers.php';
        $url = permlinkurl_id($ID);
    }

    return n.href('<span class="ui-icon ui-icon-notice"></span> '.gTxt('view'), $url, array(
        'class' => 'txp-article-view',
        'id'    => 'article_partial_article_view',
    ));
}

/**
 * Renders article body field.
 *
 * The rendered widget can be customised via the 'article_ui > body'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_body($rs)
{
    $out = inputLabel(
        'body',
        '<textarea id="body" name="Body" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_REGULAR.'">'.txpspecialchars($rs['Body']).'</textarea>',
        'body',
        array('body', 'instructions_body'),
        array('class' => 'txp-form-field txp-form-field-textarea body')
    );

    return pluggable_ui('article_ui', 'body', $out, $rs);
}

/**
 * Renders article excerpt field.
 *
 * The rendered widget can be customised via the 'article_ui > excerpt'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_excerpt($rs)
{
    $out = inputLabel(
        'excerpt',
        '<textarea id="excerpt" name="Excerpt" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.txpspecialchars($rs['Excerpt']).'</textarea>',
        'excerpt',
        array('excerpt', 'instructions_excerpt'),
        array('class' => 'txp-form-field txp-form-field-textarea excerpt')
    );

    return pluggable_ui('article_ui', 'excerpt', $out, $rs);
}

/**
 * Renders list of view modes.
 *
 * The rendered widget can be customised via the 'article_ui > view'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_view_modes($rs)
{
    global $step, $view, $use_textile;

    if ($step == "create") {
        $hasfilter = ($use_textile !== LEAVE_TEXT_UNTOUCHED);
    } else {
        $hasfilter = ($rs['textile_body'] !== LEAVE_TEXT_UNTOUCHED || $rs['textile_excerpt'] !== LEAVE_TEXT_UNTOUCHED);
    }

    if ($hasfilter) {
        $out = n.tag((tab('text', $view).tab('html', $view).tab('preview', $view)), 'ul');
    } else {
        $out = '&#160;';
    }

    $out = pluggable_ui('article_ui', 'view', $out, $rs);

    return n.tag($out.n, 'div', array('id' => 'view_modes'));
}

/**
 * Renders next/prev links.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_article_nav($rs)
{
    $out = array();

    if ($rs['prev_id']) {
        $out[] = prevnext_link(gTxt('prev'), 'article', 'edit', $rs['prev_id'], '', 'prev');
    } else {
        $out[] = span(gTxt('prev'), array(
            'class'         => 'navlink-disabled',
            'aria-disabled' => 'true',
        ));
    }

    if ($rs['next_id']) {
        $out[] = prevnext_link(gTxt('next'), 'article', 'edit', $rs['next_id'], '', 'next');
    } else {
        $out[] = span(gTxt('next'), array(
            'class'         => 'navlink-disabled',
            'aria-disabled' => 'true',
        ));
    }

    return n.tag(join('', $out), 'nav', array('class' => 'nav-tertiary'));
}

/**
 * Renders article status partial.
 *
 * The rendered widget can be customised via the 'article_ui > status'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_status($rs)
{
    return n.tag(pluggable_ui('article_ui', 'status', status_display($rs['Status']), $rs), 'div', array('id' => 'txp-container-status'));
}

/**
 * Renders article section partial.
 *
 * The rendered widget can be customised via the 'article_ui > section'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_section($rs)
{
    $out = inputLabel(
        'section',
        section_popup($rs['Section'], 'section').
        n.eLink('section', 'list', '', '', gTxt('edit'), '', '', '', 'txp-option-link'),
        'section',
        array('', 'instructions_section'),
        array('class' => 'txp-form-field section')
    );

    return pluggable_ui('article_ui', 'section', $out, $rs);
}

/**
 * Renders article categories partial.
 *
 * The rendered widget can be customised via the 'article_ui > categories'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_categories($rs)
{
    $out = n.'<div id="categories_group">'.
        inputLabel(
            'category-1',
            category_popup('Category1', $rs['Category1'], 'category-1').
            n.eLink('category', 'list', '', '', gTxt('edit'), '', '', '', 'txp-option-link'),
            'category1',
            array('', 'instructions_category1'),
            array('class' => 'txp-form-field category category-1')
        ).
        inputLabel(
            'category-2',
            category_popup('Category2', $rs['Category2'], 'category-2'),
            'category2',
            array('', 'instructions_category2'),
            array('class' => 'txp-form-field category category-2')
        ).
        n.'</div>';

    return pluggable_ui('article_ui', 'categories', $out, $rs);
}

/**
 * Renders comment options partial.
 *
 * The rendered widget can be customised via the 'article_ui > annotate_invite'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string|null HTML
 */

function article_partial_comments($rs)
{
    global $step, $use_comments, $comments_disabled_after, $comments_default_invite, $comments_on_default;

    extract($rs);

    if ($step == "create") {
        // Avoid invite disappearing when previewing.

        if (!empty($store_out['AnnotateInvite'])) {
            $AnnotateInvite = $store_out['AnnotateInvite'];
        } else {
            $AnnotateInvite = $comments_default_invite;
        }

        $Annotate = $comments_on_default;
    }

    if ($use_comments == 1) {
        $comments_expired = false;

        if ($step != 'create' && $comments_disabled_after) {
            $lifespan = $comments_disabled_after * 86400;
            $time_since = time() - $sPosted;

            if ($time_since > $lifespan) {
                $comments_expired = true;
            }
        }

        if ($comments_expired) {
            $invite = graf(gTxt('expired'), array(
                'class' => 'comment-annotate',
                'id'    => 'write-comments',
            ));
        } else {
            $invite = n.tag(
                    onoffRadio('Annotate', $Annotate),
                    'div', array('class' => 'txp-form-field comment-annotate')
                ).
                inputLabel(
                    'comment-invite',
                    fInput('text', 'AnnotateInvite', $AnnotateInvite, '', '', '', INPUT_REGULAR, '', 'comment-invite'),
                    'comment_invitation',
                    array('', 'instructions_comment_invitation'),
                    array('class' => 'txp-form-field comment-invite')
                );
        }

        return n.tag_start('div', array('id' => 'write-comments')).
            pluggable_ui('article_ui', 'annotate_invite', $invite, $rs).
            n.tag_end('div');
    }
}

/**
 * Renders timestamp partial.
 *
 * The rendered widget can be customised via the 'article_ui > timestamp'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_posted($rs)
{
    extract($rs);

    $out =
        inputLabel(
            'year',
            tsi('year', '%Y', $sPosted, '', 'year').
            ' <span role="separator">/</span> '.
            tsi('month', '%m', $sPosted, '', 'month').
            ' <span role="separator">/</span> '.
            tsi('day', '%d', $sPosted, '', 'day'),
            'publish_date',
            array('publish_date', 'instructions_publish_date'),
            array('class' => 'txp-form-field date posted')
        ).
        inputLabel(
            'hour',
            tsi('hour', '%H', $sPosted, '', 'hour').
            ' <span role="separator">:</span> '.
            tsi('minute', '%M', $sPosted, '', 'minute').
            ' <span role="separator">:</span> '.
            tsi('second', '%S', $sPosted, '', 'second'),
            'publish_time',
            array('', 'instructions_publish_time'),
            array('class' => 'txp-form-field time posted')
        ).
        n.tag(
            checkbox('reset_time', '1', $reset_time, '', 'reset_time').
            n.tag(gTxt('reset_time'), 'label', array('for' => 'reset_time')),
            'div', array('class' => 'reset-time')
        );

    return n.tag_start('div', array('id' => 'publish-datetime-group')).
        pluggable_ui('article_ui', 'timestamp', $out, $rs).
        n.tag_end('div');
}

/**
 * Renders expiration date partial.
 *
 * The rendered widget can be customised via the 'article_ui > expires'
 * pluggable UI callback event.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_expires($rs)
{
    extract($rs);

    $out =
        inputLabel(
            'exp_year',
            tsi('exp_year', '%Y', $sExpires, '', 'exp_year').
            ' <span role="separator">/</span> '.
            tsi('exp_month', '%m', $sExpires, '', 'exp_month').
            ' <span role="separator">/</span> '.
            tsi('exp_day', '%d', $sExpires, '', 'exp_day'),
            'expire_date',
            array('expire_date', 'instructions_expire_date'),
            array('class' => 'txp-form-field date expires')
        ).
        inputLabel(
            'exp_hour',
            tsi('exp_hour', '%H', $sExpires, '', 'exp_hour').
            ' <span role="separator">:</span> '.
            tsi('exp_minute', '%M', $sExpires, '', 'exp_minute').
            ' <span role="separator">:</span> '.
            tsi('exp_second', '%S', $sExpires, '', 'exp_second'),
            'expire_time',
            array('', 'instructions_expire_time'),
            array('class' => 'txp-form-field time expires')
        ).
        hInput('sExpires', $sExpires);

    return n.tag_start('div', array('id' => 'expires-datetime-group')).
        pluggable_ui('article_ui', 'expires', $out, $rs).
        n.tag_end('div');
}

/**
 * Gets a partial value from the given article data set.
 *
 * @param  array  $rs  Article data
 * @param  string $key The value to get
 * @return string HTML
 */

function article_partial_value($rs, $key)
{
    return($rs[$key]);
}

/**
 * Validates article data.
 *
 * @param  array        $rs  Article data
 * @param  string|array $msg Initial message
 * @return string HTML
 */

function article_validate($rs, &$msg)
{
    global $prefs, $step, $statuses;

    if (!empty($msg)) {
        return false;
    }

    $constraints = array(
        'Status' => new ChoiceConstraint(
            $rs['Status'],
            array('choices' => array_keys($statuses), 'message' => 'invalid_status')
        ),
        'Section' => new SectionConstraint($rs['Section']),
        'Category1' => new CategoryConstraint(
            $rs['Category1'],
            array('type' => 'article')
        ),
        'Category2' => new CategoryConstraint(
            $rs['Category2'],
            array('type' => 'article')
        ),
        'textile_body' => new \Textpattern\Textfilter\Constraint(
            $rs['textile_body'],
            array('message' => 'invalid_textfilter_body')
        ),
        'textile_excerpt' => new \Textpattern\Textfilter\Constraint(
            $rs['textile_excerpt'],
            array('message' => 'invalid_textfilter_excerpt')
        ),
    );

    if (!$prefs['articles_use_excerpts']) {
        $constraints['excerpt_blank'] = new BlankConstraint(
            $rs['Excerpt'],
            array('message' => 'excerpt_not_blank')
        );
    }

    if (!$prefs['use_comments']) {
        $constraints['annotate_invite_blank'] = new BlankConstraint(
            $rs['AnnotateInvite'],
            array('message' => 'invite_not_blank')
        );

        $constraints['annotate_false'] = new FalseConstraint(
            $rs['Annotate'],
            array('message' => 'comments_are_on')
        );
    }

    if ($prefs['allow_form_override']) {
        $constraints['override_form'] = new FormConstraint(
            $rs['override_form'],
            array('type' => 'article')
        );
    } else {
        $constraints['override_form'] = new BlankConstraint(
            $rs['override_form'],
            array('message' => 'override_form_not_blank')
        );
    }

    callback_event_ref('article_ui', "validate_$step", 0, $rs, $constraints);

    $validator = new Validator($constraints);
    if ($validator->validate()) {
        $msg = '';

        return true;
    } else {
        $msg = doArray($validator->getMessages(), 'gTxt');
        $msg = array(join(', ', $msg), E_ERROR);

        return false;
    }
}

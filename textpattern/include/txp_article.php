<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2015 The Textpattern Development Team
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
        case "create":
            article_edit();
            break;
        case "publish":
            article_post();
            break;
        case "edit":
            article_edit();
            break;
        case "save":
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

        // Comments my be on, off, or disabled.
        $Annotate = (int) $Annotate;

        // Set and validate article timestamp.
        if ($publish_now == 1) {
            $when = 'now()';
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
            $when = "from_unixtime($when_ts)";
        }

        // Force a reasonable 'last modified' date for future articles,
        // keep recent articles list in order.
        $lastmod = ($when_ts > time() ? 'now()' : $when);

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
            $whenexpires = "from_unixtime($expires)";
        } else {
            $whenexpires = NULLDATETIME;
        }

        $user = doSlash($txp_user);
        $description = doSlash($description);
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
               "textpattern",
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
                LastMod         =  $lastmod,
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
                feed_time       = now()"
            );

            if ($ok) {
                $rs['ID'] = $GLOBALS['ID'] = $ok;

                if ($is_clone) {
                    safe_update(
                        'textpattern',
                        "Title = concat(Title, ' (', {$ok}, ')'),
                        url_title = concat(url_title, '-', {$ok})",
                        "ID = {$ok}"
                    );
                }

                if ($Status >= STATUS_LIVE) {
                    do_pings();
                    update_lastmod('article_posted', $rs);
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

    $oldArticle = safe_row('Status, url_title, Title, '.
        'unix_timestamp(LastMod) as sLastMod, LastModID, '.
        'unix_timestamp(Posted) as sPosted, '.
        'unix_timestamp(Expires) as sExpires',
        'textpattern', 'ID = '.(int) $incoming['ID']);

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
        $incoming['textile_body'] = $incoming['textile_excerpt'] = $use_textile;
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
        $whenposted = "Posted=now()";
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

        $whenposted = "Posted=from_unixtime($when)";
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
        $whenexpires = "Expires=from_unixtime($expires)";
    } else {
        $whenexpires = "Expires=".NULLDATETIME;
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
    $description = doSlash($description);

    $cfq = array();
    $cfs = getCustomFields();

    foreach ($cfs as $i => $cf_name) {
        $custom_x = "custom_{$i}";
        $cfq[] = "custom_$i = '".$$custom_x."'";
    }

    $cfq = join(', ', $cfq);

    $rs = compact($vars);
    if (article_validate($rs, $msg)) {
        if (safe_update("textpattern",
           "Title           = '$Title',
            Body            = '$Body',
            Body_html       = '$Body_html',
            Excerpt         = '$Excerpt',
            Excerpt_html    = '$Excerpt_html',
            Keywords        = '$Keywords',
            description     = '$description',
            Image           = '$Image',
            Status          =  $Status,
            LastMod         =  now(),
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
        'selector' => $DOM_selector,
         'cb' => $callback_function,
         'html' => $return_value_of_callback_function (need not be intialised here)
    )
    */
    $partials = array(
        'html_title'   => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'title',
            'cb'       => 'article_partial_html_title',
        ),
        'sLastMod' => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '[name=sLastMod]',
            'cb'       => 'article_partial_value',
        ),
        'sPosted' => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '[name=sPosted]',
            'cb'       => 'article_partial_value',
        ),
        'sidehelp' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#textfilter_group',
            'cb'       => 'article_partial_sidehelp',
        ),
        'url_title' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'p.url-title',
            'cb'       => 'article_partial_url_title',
        ),
        'url_title_value' => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#url-title',
            'cb'       => 'article_partial_url_title_value',
        ),
        'description' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'p.description',
            'cb'       => 'article_partial_description',
        ),
        'description_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#description',
            'cb'       => 'article_partial_description_value',
        ),
        'keywords' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'p.keywords',
            'cb'       => 'article_partial_keywords',
        ),
        'keywords_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#keywords',
            'cb'       => 'article_partial_keywords_value',
        ),
        'image' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => '#image_group',
            'cb'       => 'article_partial_image',
        ),
        'custom_fields' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => '#custom_field_group',
            'cb'       => 'article_partial_custom_fields',
        ),
        'recent_articles' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#recent_group .recent',
            'cb'       => 'article_partial_recent_articles',
        ),
        'title' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'p.title',
            'cb'       => 'article_partial_title',
        ),
        'title_value'  => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => '#title',
            'cb'       => 'article_partial_title_value',
        ),
        'article_clone' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#article_partial_article_clone',
            'cb'       => 'article_partial_article_clone',
        ),
        'article_view' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#article_partial_article_view',
            'cb'       => 'article_partial_article_view',
        ),
        'body' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'p.body',
            'cb'       => 'article_partial_body',
        ),
        'excerpt' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'p.excerpt',
            'cb'       => 'article_partial_excerpt',
        ),
        'author' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'p.author',
            'cb'       => 'article_partial_author',
        ),
        'view_modes' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#view_modes',
            'cb'       => 'article_partial_view_modes',
        ),
        'article_nav' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'p.nav-tertiary',
            'cb'       => 'article_partial_article_nav',
        ),
        'status' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#write-status',
            'cb'       => 'article_partial_status',
        ),
        'categories' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => '#categories_group',
            'cb'       => 'article_partial_categories',
        ),
        'section' => array(
            'mode'     => PARTIAL_STATIC,
            'selector' => 'p.section',
            'cb'       => 'article_partial_section',
        ),
        'comments' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#write-comments',
            'cb'       => 'article_partial_comments',
        ),
        'posted' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#write-timestamp',
            'cb'       => 'article_partial_posted',
        ),
        'expires' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#write-expires',
            'cb'       => 'article_partial_expires',
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

    extract(gpsa(array('view', 'from_view', 'step')));

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
            "*, unix_timestamp(Posted) as sPosted,
            unix_timestamp(Expires) as sExpires,
            unix_timestamp(LastMod) as sLastMod",
            "textpattern",
            "ID=$ID"
        );

        if (empty($rs)) {
            return;
        }

        $rs['reset_time'] = $rs['publish_now'] = false;
    } else {
        $pull = false; // Assume they came from post.

        if ($from_view == 'preview' or $from_view == 'html') {
            $store_out = array();
            $store = unserialize(base64_decode(ps('store')));

            foreach ($vars as $var) {
                if (isset($store[$var])) {
                    $store_out[$var] = $store[$var];
                }
            }
        } else {
            $store_out = gpsa($vars);

            if ($concurrent) {
                $store_out['sLastMod'] = safe_field('unix_timestamp(LastMod) as sLastMod', 'textpattern', 'ID='.$ID);
            }
        }

        // Use preferred Textfilter as default and fallback.
        $hasfilter = new Textpattern_Textfilter_Constraint(null);
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
        $rs['prev_id'] = checkIfNeighbour('prev', $sPosted);

        // Next record?
        $rs['next_id'] = checkIfNeighbour('next', $sPosted);
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
                if ($p['mode'] == PARTIAL_VOLATILE) {
                    // Volatile partials replace *all* of the existing HTML
                    // fragment for their selector.
                    $response[] = '$("'.$p['selector'].'").replaceWith("'.escape_js($p['html']).'")';
                } elseif ($p['mode'] == PARTIAL_VOLATILE_VALUE) {
                    // Volatile partial values replace the *value* of elements
                    // matching their selector.
                    $response[] = '$("'.$p['selector'].'").val("'.escape_js($p['html']).'")';
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

    echo hed(gTxt('tab_write'), 1, array('class' => 'txp-heading txp-accessibility'));
    echo
        n.tag_start('form', array(
            'id'     => 'article_form',
            'name'   => 'article_form',
            'method' => 'post',
            'action' => 'index.php',
            'class'  => $class,
        )).
        n.'<div id="'.$event.'_container" class="txp-layout-grid">';

    if (!empty($store_out)) {
        echo hInput('store', base64_encode(serialize($store_out)));
    }

    echo hInput('ID', $ID).
        eInput('article').
        sInput($step).
        hInput('sPosted', $sPosted).
        hInput('sLastMod', $sLastMod).
        hInput('AuthorID', $AuthorID).
        hInput('LastModID', $LastModID).
        n.'<input type="hidden" name="view" />';

    echo n.'<div class="txp-layout-cell txp-layout-1-4">'.
        n.'<div id="configuration_content">';

    if ($view == 'text') {
        // Markup help.
        echo $partials['sidehelp']['html'];

        // Custom menu entries.
        echo pluggable_ui('article_ui', 'extend_col_1', '', $rs);

        // Advanced.

        // Markup selection.
        if (has_privs('article.set_markup')) {
            $html_markup =
                graf(
                    '<label for="markup-body">'.gTxt('article_markup').'</label>'.br.
                    pref_text('textile_body', $textile_body, 'markup-body'), ' class="markup markup-body"').
                graf(
                    '<label for="markup-excerpt">'.gTxt('excerpt_markup').'</label>'.br.
                    pref_text('textile_excerpt', $textile_excerpt, 'markup-excerpt'), ' class="markup markup-excerpt"');
        } else {
            $html_markup = '';
        }

        $html_markup = pluggable_ui('article_ui', 'markup', $html_markup, $rs);

        // Form override.
        $form_pop = $allow_form_override ? form_pop($override_form, 'override-form') : '';
        $html_override = $form_pop
            ? pluggable_ui('article_ui', 'override', graf('<label for="override-form">'.gTxt('override_default_form').'</label>'.popHelp('override_form').br.
                $form_pop, ' class="override-form"'), $rs)
            : '';

        echo wrapRegion('advanced_group', $html_markup.$html_override, 'advanced', 'advanced_options', 'article_advanced');

        // Meta info.

        // keywords.
        $html_keywords = $partials['keywords']['html'];

        // description.
        $html_description = $partials['description']['html'];

        // URL title.
        $html_url_title = $partials['url_title']['html'];

        echo wrapRegion('meta_group', $html_url_title.$html_description.$html_keywords, 'meta', 'meta', 'article_meta');

        // Article image.
        echo $partials['image']['html'];

        // Custom fields.
        echo $partials['custom_fields']['html'];

        // Recent articles.
        echo wrapRegion('recent_group', $partials['recent_articles']['html'], 'recent', 'recent_articles', 'article_recent');
    } else {
        echo sp;
    }

    echo n.'</div>'. // End of #configuration_content.
        n.'</div>'; // End of .txp-layout-cell.

    echo n.'<div class="txp-layout-cell txp-layout-2-4">'.
        n.'<div role="region" id="main_content">';

    // View mode tabs.
    echo $partials['view_modes']['html'];

    // Title input.
    if ($view == 'preview') {
        echo n.'<div class="preview">'.hed(gTxt('preview'), 2).hed($Title, 1, ' class="title"');
    } elseif ($view == 'html') {
        echo n.'<div class="html">'.hed('HTML', 2).hed($Title, 1, ' class="title"');
    } elseif ($view == 'text') {
        echo n.'<div class="text">'.$partials['title']['html'];
    }

    // Body.
    if ($view == 'preview') {
        echo n.'<div class="body">'.$Body_html.'</div>';
    } elseif ($view == 'html') {
        echo tag(str_replace(array(n, t), array(br, sp.sp.sp.sp), txpspecialchars($Body_html)), 'code', ' class="body"');
    } else {
        echo $partials['body']['html'];
    }

    // Excerpt.
    if ($articles_use_excerpts) {
        if ($view == 'preview') {
            echo n.'<hr />'.n.'<div class="excerpt">'.$Excerpt_html.'</div>';
        } elseif ($view == 'html') {
            echo n.'<hr />'.
                tag(str_replace(array(n, t), array(br, sp.sp.sp.sp), txpspecialchars($Excerpt_html)), 'code', array('class' => 'excerpt'));
        } else {
            echo $partials['excerpt']['html'];
        }
    }

    // Author.
    if ($view == "text" && $step != "create") {
        echo $partials['author']['html'];
    }

    echo hInput('from_view', $view),
        n.'</div>';

    echo n.'</div>'. // End of #main_content.
        n.'</div>'; // End of .txp-layout-cell.

    echo n.'<div class="txp-layout-cell txp-layout-1-4">'.
        n.'<div id="supporting_content">';

    if ($view == 'text') {
        // Publish and Save buttons.
        if ($step == 'create' and empty($GLOBALS['ID'])) {
            if (has_privs('article.publish')) {
                $push_button = fInput('submit', 'publish', gTxt('publish'), 'publish');
            } else {
                $push_button = fInput('submit', 'publish', gTxt('save'), 'publish');
            }

            echo graf($push_button, array('id' => 'write-publish'));
        } elseif (
            ($Status >= STATUS_LIVE && has_privs('article.edit.published')) ||
            ($Status >= STATUS_LIVE && $AuthorID === $txp_user && has_privs('article.edit.own.published')) ||
            ($Status < STATUS_LIVE && has_privs('article.edit')) ||
            ($Status < STATUS_LIVE && $AuthorID === $txp_user && has_privs('article.edit.own'))
        ) {
            echo graf(fInput('submit', 'save', gTxt('save'), 'publish'), array('id' => 'write-save'));
        }

        if ($step != 'create') {
            echo graf(href(gTxt('create_new'), 'index.php?event=article'), ' class="action-create"');
        }

        // Prev/next article links.
        if ($step != 'create' and ($rs['prev_id'] or $rs['next_id'])) {
            echo $partials['article_nav']['html'];
        }

        // Sort and display.
        echo pluggable_ui(
            'article_ui',
            'sort_display',
            wrapRegion('write-sort', $partials['status']['html'].$partials['section']['html'].$partials['categories']['html'], '', gTxt('sort_display')),
            $rs
        );

        // "Comments" section.
        echo wrapRegion('comments_group', $partials['comments']['html'], 'comments', 'comment_settings', 'article_comments', (($use_comments == 1)
            ? ''
            : 'empty'
        ));

        // "Dates" section.

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
                wrapRegion(
                    'write-timestamp',
                    graf(
                        checkbox('publish_now', '1', $publish_now, '', 'publish_now').
                        n.'<label for="publish_now">'.gTxt('set_to_now').'</label>', ' class="publish-now"'
                    ).

                    graf(gTxt('or_publish_at').popHelp('timestamp'), ' class="publish-at"').

                    graf(
                        span(gTxt('date'), array('class' => 'txp-label-fixed')).br.
                        tsi('year', '%Y', $persist_timestamp, '').' / '.
                        tsi('month', '%m', $persist_timestamp, '').' / '.
                        tsi('day', '%d', $persist_timestamp, ''), ' class="date posted created"'
                    ).

                    graf(
                        span(gTxt('time'), array('class' => 'txp-label-fixed')).br.
                        tsi('hour', '%H', $persist_timestamp, '').' : '.
                        tsi('minute', '%M', $persist_timestamp, '').' : '.
                        tsi('second', '%S', $persist_timestamp, ''), ' class="time posted created"'
                    ),
                    '',
                    gTxt('timestamp')
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
                wrapRegion(
                    'write-expires',
                    graf(
                        span(gTxt('date'), array('class' => 'txp-label-fixed')).br.
                        tsi('exp_year', '%Y', $persist_timestamp, '').' / '.
                        tsi('exp_month', '%m', $persist_timestamp, '').' / '.
                        tsi('exp_day', '%d', $persist_timestamp, ''), ' class="date expires"'
                    ).

                    graf(
                        span(gTxt('time'), array('class' => 'txp-label-fixed')).br.
                        tsi('exp_hour', '%H', $persist_timestamp, '').' : '.
                        tsi('exp_minute', '%M', $persist_timestamp, '').' : '.
                        tsi('exp_second', '%S', $persist_timestamp, ''), ' class="time expires"'
                    ),
                    '',
                    gTxt('expires')
                ),
                $rs
            );
        } else {
            // Timestamp.
            $posted_block = $partials['posted']['html'];

            // Expires.
            $expires_block = $partials['expires']['html'];
        }

        echo wrapRegion('dates_group', $posted_block.$expires_block, 'dates', 'date_settings', 'article_dates');
    }

    echo n.'</div>'.// End of #supporting_content.
        n.'</div>'.// End of .txp-layout-cell.
        n.'</div>'.// End of .txp-layout-grid.
        tInput().
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
    return graf('<label for="custom-'.$num.'">'.$field.'</label>'.br.
        fInput('text', 'custom_'.$num, $content, '', '', '', INPUT_REGULAR, '', 'custom-'.$num), ' class="custom-field custom-'.$num.'"');
}

/**
 * Gets the ID of the next or the previous article.
 *
 * @param  string $whichway Either '&lt;' or '&gt;'
 * @param  int    Unix timestamp
 * @return int
 */

function checkIfNeighbour($whichway, $sPosted)
{
    $sPosted = assert_int($sPosted);
    $dir = ($whichway == 'prev') ? '<' : '>';
    $ord = ($whichway == 'prev') ? 'desc' : 'asc';

    return safe_field("ID", "textpattern",
        "Posted $dir from_unixtime($sPosted) order by Posted $ord limit 1");
}

/**
 * Renders an article status field.
 *
 * @param  int    $status Selected status
 * @return string HTML
 */

function status_display($status)
{
    global $statuses;

    if (!$status) {
        $status = get_pref('default_publish_status', STATUS_LIVE);
    }

    return graf(
        '<label for="status">'.gTxt('status').'</label>'.br.
        selectInput('Status', $statuses, $status, false, '', 'status'), ' class="status"');
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
    $rs = safe_rows('name, title', 'txp_section', "name != 'default' order by title asc, name asc");

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
        'role'           => 'button',
        'aria-pressed'   => $pressed,
        'title'          => gTxt('view_'.$tabevent),
    ));

    return n.tag($link, 'li', array(
        'id'    => 'tab-'.$tabevent,
        'class' => $state,
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
    $rs = safe_column('name', 'txp_form', "type = 'article' and name != 'default' order by name");

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
 * @param  int    $Status The status
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
    $textile = new Textpattern_Textile_Parser();

    $incoming['Title_plain'] = trim($incoming['Title']);
    $incoming['Title_html'] = ''; // not used
    $incoming['Title'] = $textile->textileEncode($incoming['Title_plain']);

    $incoming['Body_html'] = Txp::get('Textpattern_Textfilter_Registry')->filter(
        $incoming['textile_body'],
        $incoming['Body'],
        array('field' => 'Body', 'options' => array('lite' => false), 'data' => $incoming)
    );

    $incoming['Excerpt_html'] = Txp::get('Textpattern_Textfilter_Registry')->filter(
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
 * @param  array  $rs Article data
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
    $help = Txp::get('Textpattern_Textfilter_Registry')->getHelp($rs['textile_body']);

    if ($rs['textile_body'] != $rs['textile_excerpt']) {
        $help .= Txp::get('Textpattern_Textfilter_Registry')->getHelp($rs['textile_excerpt']);
    }

    $out = wrapRegion('textfilter_group', $help, 'textfilter_help', 'textfilter_help', 'article_textfilter_help');

    return pluggable_ui('article_ui', 'sidehelp', $out, $rs);
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
    $av_cb = $rs['partials_meta']['article_view']['cb'];
    $ac_cb = $rs['partials_meta']['article_clone']['cb'];
    $out = graf('<label for="title">'.gTxt('title').'</label>'.popHelp('title').br.
        n.'<input type="text" id="title" name="Title" value="'.escape_title($rs['Title']).'" size="48" />'.
        ($step != 'create' ?  $ac_cb($rs).$av_cb($rs) : ''), ' class="title"');

    return pluggable_ui('article_ui', 'title', $out, $rs);
}

/**
 * Gets article's title from the given article data set.
 *
 * @param  array  $rs Article data
 * @return string
 */

function article_partial_title_value($rs)
{
    return html_entity_decode($rs['Title'], ENT_QUOTES, 'UTF-8');
}

/**
 * Renders author partial.
 *
 * The rendered widget can be customised via the 'article_ui > author'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
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
 * @param  array  $rs Article data
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
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_url_title($rs)
{
    $out = graf('<label for="url-title">'.gTxt('url_title').'</label>'.popHelp('url_title').br.
        fInput('text', 'url_title', article_partial_url_title_value($rs), '', '', '', INPUT_REGULAR, '', 'url-title'), ' class="url-title"');

    return pluggable_ui('article_ui', 'url_title', $out, $rs);
}

/**
 * Gets URL title from the given article data set.
 *
 * @param  array  $rs Article data
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
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_description($rs)
{
    $out = graf('<label for="description">'.gTxt('description').'</label>'.popHelp('description').br.
        text_area('description', 0, 0, article_partial_description_value($rs), 'description', TEXTAREA_HEIGHT_SMALL, INPUT_LARGE), ' class="description"');

    return pluggable_ui('article_ui', 'description', $out, $rs);
}

/**
 * Gets description from the given article data set.
 *
 * @param  array  $rs Article data
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
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_keywords($rs)
{
    $out = graf('<label for="keywords">'.gTxt('keywords').'</label>'.popHelp('keywords').br.
        n.'<textarea id="keywords" name="Keywords" cols="'.INPUT_MEDIUM.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.txpspecialchars(article_partial_keywords_value($rs)).'</textarea>', ' class="keywords"');

    return pluggable_ui('article_ui', 'keywords', $out, $rs);
}

/**
 * Gets keywords from the given article data set.
 *
 * @param  array  $rs Article data
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
    $default = graf(
        '<label for="article-image">'.gTxt('article_image').'</label>'.popHelp('article_image').br.
            fInput('text', 'Image', $rs['Image'], '', '', '', INPUT_REGULAR, '', 'article-image'), ' class="article-image"');

    return wrapRegion('image_group', pluggable_ui('article_ui', 'article_image', $default, $rs), 'image', 'article_image', 'article_image');
}

/**
 * Renders all custom fields in one partial.
 *
 * The rendered widget can be customised via the 'article_ui > custom_fields'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_custom_fields($rs)
{
    global $cfs;
    $cf = '';

    foreach ($cfs as $k => $v) {
        $cf .= article_partial_custom_field($rs, "custom_field_{$k}");
    }

    return wrapRegion('custom_field_group', pluggable_ui('article_ui', 'custom_fields', $cf, $rs), 'custom_field', 'custom', 'article_custom_field', (($cfs) ? '' : 'empty'));
}

/**
 * Renders &lt;ol&gt; list of recent articles.
 *
 * The rendered widget can be customised via the 'article_ui > recent_articles'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_recent_articles($rs)
{
    $recents = safe_rows_start('Title, ID', 'textpattern', '1=1 order by LastMod desc limit '.(int) WRITE_RECENT_ARTICLES_COUNT);
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

    return pluggable_ui('article_ui', 'recent_articles', $ra, $rs);
}

/**
 * Renders article duplicate link.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_article_clone($rs)
{
    extract($rs);

    return n.span(href(gTxt('duplicate'), '#', array('id' => 'txp_clone', 'class' => 'article-clone')), array(
            'id'    => 'article_partial_article_clone',
            'class' => 'txp-actions',
        ));
}

/**
 * Renders article view link.
 *
 * @param  array  $rs Article data
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

    return n.span(href(gTxt('view'), $url, array('class' => 'article-view')), array(
        'id'    => 'article_partial_article_view',
        'class' => 'txp-actions',
    ));
}

/**
 * Renders article body field.
 *
 * The rendered widget can be customised via the 'article_ui > body'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_body($rs)
{
    $out = graf('<label for="body">'.gTxt('body').'</label>'.popHelp('body').br.
        n.'<textarea id="body" name="Body" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_LARGE.'">'.txpspecialchars($rs['Body']).'</textarea>', ' class="body"'
    );

    return pluggable_ui('article_ui', 'body', $out, $rs);
}

/**
 * Renders article excerpt field.
 *
 * The rendered widget can be customised via the 'article_ui > excerpt'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_excerpt($rs)
{
    $out = graf('<label for="excerpt">'.gTxt('excerpt').'</label>'.popHelp('excerpt').br.
        n.'<textarea id="excerpt" name="Excerpt" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_MEDIUM.'">'.txpspecialchars($rs['Excerpt']).'</textarea>', ' class="excerpt"'
    );

    return pluggable_ui('article_ui', 'excerpt', $out, $rs);
}

/**
 * Renders list of view modes.
 *
 * The rendered widget can be customised via the 'article_ui > view'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
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
 * @param  array  $rs Article data
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

    return graf(join('', $out), ' role="navigation" class="nav-tertiary prev-next"');
}

/**
 * Renders article status partial.
 *
 * The rendered widget can be customised via the 'article_ui > status'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_status($rs)
{
    return pluggable_ui('article_ui', 'status', status_display($rs['Status']), $rs);
}

/**
 * Renders article section partial.
 *
 * The rendered widget can be customised via the 'article_ui > section'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_section($rs)
{
    $out = graf(
        '<label for="section">'.gTxt('section').'</label>'.

        sp.span(
            span('[', array('aria-hidden' => 'true')).
            eLink('section', '', '', '', gTxt('edit')).
            span(']', array('aria-hidden' => 'true')), array('class' => 'section-edit')).br.

        section_popup($rs['Section'], 'section'), ' class="section"');

    return pluggable_ui('article_ui', 'section', $out, $rs);
}

/**
 * Renders article categories partial.
 *
 * The rendered widget can be customised via the 'article_ui > categories'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_categories($rs)
{
    $out = n.'<div id="categories_group">'.

        graf(
            '<label for="category-1">'.gTxt('category1').'</label>'.

            sp.span(
                span('[', array('aria-hidden' => 'true')).
                eLink('category', '', '', '', gTxt('edit')).
                span(']', array('aria-hidden' => 'true')), array('class' => 'category-edit')).br.

            category_popup('Category1', $rs['Category1'], 'category-1'), ' class="category category-1"').

        graf(
            '<label for="category-2">'.gTxt('category2').'</label>'.br.
            category_popup('Category2', $rs['Category2'], 'category-2'), ' class="category category-2"').

        n.'</div>';

    return pluggable_ui('article_ui', 'categories', $out, $rs);
}

/**
 * Renders comment options partial.
 *
 * The rendered widget can be customised via the 'article_ui > annotate_invite'
 * pluggable UI callback event.
 *
 * @param  array       $rs Article data
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

        if ($comments_on_default == 1) {
            $Annotate = 1;
        }
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
            $invite = graf(gTxt('expired'), ' class="comment-annotate" id="write-comments"');
        } else {
            $invite = n.'<div id="write-comments">'.
                graf(
                onoffRadio('Annotate', $Annotate), ' class="comment-annotate"').

                graf(
                '<label for="comment-invite">'.gTxt('comment_invitation').'</label>'.br.
                    fInput('text', 'AnnotateInvite', $AnnotateInvite, '', '', '', INPUT_REGULAR, '', 'comment-invite'), ' class="comment-invite"').
                n.'</div>';
        }

        return pluggable_ui('article_ui', 'annotate_invite', $invite, $rs);
    }
}

/**
 * Renders timestamp partial.
 *
 * The rendered widget can be customised via the 'article_ui > timestamp'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_posted($rs)
{
    extract($rs);

    $out =
        wrapRegion(
            'write-timestamp',
            graf(
                checkbox('reset_time', '1', $reset_time, '', 'reset_time').
                tag(gTxt('reset_time'), 'label', array('for' => 'reset_time')), ' class="reset-time"'
            ).

            graf(gTxt('published_at').popHelp('timestamp'), ' class="publish-at"').

            graf(
                span(gTxt('date'), array('class' => 'txp-label-fixed')).br.
                tsi('year', '%Y', $sPosted).' / '.
                tsi('month', '%m', $sPosted).' / '.
                tsi('day', '%d', $sPosted), ' class="date posted created"'
            ).

            graf(
                span(gTxt('time'), array('class' => 'txp-label-fixed')).br.
                tsi('hour', '%H', $sPosted).' : '.
                tsi('minute', '%M', $sPosted).' : '.
                tsi('second', '%S', $sPosted), ' class="time posted created"'
            ),
            '',
            gTxt('timestamp')
        );

    return pluggable_ui('article_ui', 'timestamp', $out, $rs);
}

/**
 * Renders expiration date partial.
 *
 * The rendered widget can be customised via the 'article_ui > expires'
 * pluggable UI callback event.
 *
 * @param  array  $rs Article data
 * @return string HTML
 */

function article_partial_expires($rs)
{
    extract($rs);

    $out =
        wrapRegion(
            'write-expires',
            graf(
                span(gTxt('date'), array('class' => 'txp-label-fixed')).br.
                tsi('exp_year', '%Y', $sExpires).' / '.
                tsi('exp_month', '%m', $sExpires).' / '.
                tsi('exp_day', '%d', $sExpires), ' class="date expires"'
            ).

            graf(
                span(gTxt('time'), array('class' => 'txp-label-fixed')).br.
                tsi('exp_hour', '%H', $sExpires).' : '.
                tsi('exp_minute', '%M', $sExpires).' : '.
                tsi('exp_second', '%S', $sExpires), ' class="time expires"'
            ).
            hInput('sExpires', $sExpires),
            '',
            gTxt('expires')
        );

    return pluggable_ui('article_ui', 'expires', $out, $rs);
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
        'textile_body' => new Textpattern_Textfilter_Constraint(
            $rs['textile_body'],
            array('message' => 'invalid_textfilter_body')
        ),
        'textile_excerpt' => new Textpattern_Textfilter_Constraint(
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

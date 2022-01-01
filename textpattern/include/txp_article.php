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
    'expire_now',
    'AuthorID',
    'sPosted',
    'LastModID',
    'sLastMod',
    'override_form',
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

if (!empty($event) && $event == 'article') {
    require_privs('article');

    $save = gps('save');
    $publish = gps('publish');

    if ($save || $publish) {
        $step = 'save';
    } elseif (empty($step)) {
        $step = 'edit';
    }

    bouncer($step, array(
        'create'         => false,
        'publish'        => true,
        'edit'           => array('view' => 'preview'),
        'save'           => true,
    ));

    switch ($step) {
        case 'publish':
        case 'save':
            article_save();
            break;
        default:
            article_edit();
    }
}

/**
 * Processes sent forms and saves new articles. Deprecated in 4.7 by article_save().
 */

function article_post()
{
    article_save();
}

/**
 * Processes sent forms and updates existing articles.
 */

function article_save()
{
    global $txp_user, $vars, $prefs;

    extract($prefs);

    $incoming = array_map('assert_string', psa($vars));
    $is_clone = ps('copy');

    if ($is_clone) {
        $incoming['ID'] = $incoming['url_title'] = '';
        $incoming['Status'] = STATUS_DRAFT;
    }

    if ($incoming['ID']) {
        $oldArticle = safe_row("Status, AuthorID, url_title, Title, textile_body, textile_excerpt,
            UNIX_TIMESTAMP(LastMod) AS sLastMod, LastModID,
            UNIX_TIMESTAMP(Posted) AS sPosted,
            UNIX_TIMESTAMP(Expires) AS sExpires",
            'textpattern', "ID = ".(int) $incoming['ID']);

        if (!($oldArticle['Status'] >= STATUS_LIVE && has_privs('article.edit.published')
            || $oldArticle['Status'] >= STATUS_LIVE && $oldArticle['AuthorID'] === $txp_user && has_privs('article.edit.own.published')
            || $oldArticle['Status'] < STATUS_LIVE && has_privs('article.edit')
            || $oldArticle['Status'] < STATUS_LIVE && $oldArticle['AuthorID'] === $txp_user && has_privs('article.edit.own'))) {
            // Not allowed, you silly rabbit, you shouldn't even be here.
            // Show default editing screen.
            article_edit();

            return;
        }

        if ($oldArticle['sLastMod'] != $incoming['sLastMod']) {
            article_edit(array(gTxt('concurrent_edit_by', array('{author}' => txpspecialchars($oldArticle['LastModID']))), E_ERROR), true, true);

            return;
        }
    } else {
        $oldArticle = array('Status' => STATUS_PENDING,
            'url_title'       => '',
            'Title'           => '',
            'textile_body'    => $use_textile,
            'textile_excerpt' => $use_textile,
            'sLastMod'        => null,
            'LastModID'       => $txp_user,
            'sPosted'         => time(),
            'sExpires'        => null,
        );
    }

    if (!has_privs('article.set_markup')) {
        $incoming['textile_body'] = $oldArticle['textile_body'];
        $incoming['textile_excerpt'] = $oldArticle['textile_excerpt'];
    }

    $incoming = textile_main_fields($incoming);

    extract(doSlash($incoming));
    $ID = intval($ID);
    $Status = assert_int($Status);

    if (!has_privs('article.publish') && $Status >= STATUS_LIVE) {
        $Status = STATUS_PENDING;
    }

    // Comments may be on, off, or disabled.
    $Annotate = (int) $Annotate;

    // Set and validate article timestamp.
    if ($publish_now || $reset_time) {
        $whenposted = "NOW()";
        $when_ts = time();
    } else {
        if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) || !is_numeric($hour) || !is_numeric($minute) || !is_numeric($second)) {
            $ts = false;
        } else {
            $ts = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
        }

        if ($ts === false || $ts < 0) {
            $when_ts = $oldArticle['sPosted'];
            $msg = array(gTxt('invalid_postdate'), E_ERROR);
        } else {
            $when_ts = $ts - tz_offset($ts);
        }

        $whenposted = "FROM_UNIXTIME($when_ts)";
    }

    // Set and validate expiry timestamp.
    if ($expire_now) {
        $ts = time();
        $expires = $ts - tz_offset($ts);
    } elseif (empty($exp_year)) {
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
            $msg = array(gTxt('invalid_expiredate'), E_ERROR);
        } else {
            $expires = $ts - tz_offset($ts);
        }
    }

    if ($expires && ($expires <= $when_ts)) {
        $expires = $oldArticle['sExpires'];
        $msg = array(gTxt('article_expires_before_postdate'), E_ERROR);
    }

    if ($expires) {
        $whenexpires = "FROM_UNIXTIME($expires)";
    } else {
        $whenexpires = "NULL";
    }

    // Auto-update custom-titles according to Title, as long as unpublished and
    // NOT customised.
    if (empty($url_title)
        || (($oldArticle['Status'] < STATUS_LIVE)
        && ($oldArticle['url_title'] === $url_title)
        && ($oldArticle['Title'] !== $Title)
        && ($oldArticle['url_title'] === stripSpace($oldArticle['Title'], 1))
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
        $set =
           "Title           = '$Title',
            Body            = '$Body',
            Body_html       = '$Body_html',
            Excerpt         = '$Excerpt',
            Excerpt_html    = '$Excerpt_html',
            Keywords        = '$Keywords',
            description     = '$description',
            Image           = '$Image',
            Status          = '$Status',
            Posted          =  $whenposted,
            Expires         =  $whenexpires,
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
            AnnotateInvite  = '$AnnotateInvite'"
            .(($cfs) ? ', '.$cfq : '')
            .(!empty($ID) ? '' :
            ", AuthorID        = '$user',
            uid             = '".md5(uniqid(rand(), true))."',
            feed_time       = NOW()");

        if ($ID && safe_update('textpattern', $set, "ID = $ID")
            || !$ID && $rs['ID'] = $GLOBALS['ID'] = safe_insert('textpattern', $set)
        ) {
            if ($is_clone) {
                $url_title = stripSpace($Title_plain.' ('.$rs['ID'].')', 1);
                safe_update(
                    'textpattern',
                    "Title = CONCAT(Title, ' (', ID, ')'),
                    url_title = '$url_title'",
                    "ID = ".$rs['ID']
                );
            }

            if ($Status >= STATUS_LIVE) {
                if ($oldArticle['Status'] < STATUS_LIVE) {
                    do_pings();
                } else {
                    update_lastmod($ID ? 'article_saved' : 'article_posted', $rs);
                }
            }

            now('posted', true);
            now('expires', true);
            callback_event($ID ? 'article_saved' : 'article_posted', '', false, $rs);

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
 * Renders article preview.
 *
 * @param string $field
 */

function article_preview($field = false)
{
    global $prefs, $vars, $app_mode;

    // Assume they came from post.
    $view = ps('view', 'preview');
    $rs = textile_main_fields(psa($vars));

    // Preview pane
    $preview = '<div id="pane-view" class="'.txpspecialchars($view).'">';

    if ($view == 'preview') {
        if (!$field || $field == 'body') {
            $preview .= n.'<div class="body">'.
                n.'<h2>'.gTxt('body').'</h2>'.
                implode('', txp_tokenize($rs['Body_html'], false, function ($tag) {
                    return '<span class="disabled">'.txpspecialchars($tag).'</span>';
                })).
            '</div>';
        }

        if ($prefs['articles_use_excerpts'] && (!$field || $field == 'excerpt')) {
            $preview .= n.'<div class="excerpt">'.
                n.'<h2>'.gTxt('excerpt').'</h2>'.
                implode('', txp_tokenize($rs['Excerpt_html'], false, function ($tag) {
                    return '<span class="disabled">'.txpspecialchars($tag).'</span>';
                })).
                '</div>';
        }
    } elseif ($view == 'html') {
        if (!$field || $field == 'body') {
            $preview .= n.'<h2>'.gTxt('body').'</h2>'.
            n.tag(
                tag(str_replace(array(t), array(sp.sp.sp.sp), txpspecialchars($rs['Body_html'])), 'code', array(
                    'class' => 'language-markup',
                    'dir'   => 'ltr',
                )),
                'pre', array('class' => 'body')
            );
        }

        if ($prefs['articles_use_excerpts'] && (!$field || $field == 'excerpt')) {
            $preview .= n.'<h2>'.gTxt('excerpt').'</h2>'.
                n.tag(
                    tag(str_replace(array(t), array(sp.sp.sp.sp), txpspecialchars($rs['Excerpt_html'])), 'code', array(
                        'class' => 'language-markup',
                        'dir'   => 'ltr',
                    )),
                    'pre', array('class' => 'excerpt')
                );
        }
    }

    $preview .= '</div>';// End of #pane-view.

    return $preview;
}

/**
 * Renders article editor form.
 *
 * @param string|array $message          The activity message
 * @param bool         $concurrent       Treat as a concurrent save
 * @param bool         $refresh_partials Whether to refresh partial contents
 */

function article_edit($message = '', $concurrent = false, $refresh_partials = false)
{
    global $vars, $txp_user, $prefs, $step, $view, $app_mode;

    $view = gps('view', 'text');

    if ($view != 'text') {
        exit(article_preview(gps('preview')));
    }

    extract($prefs);

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
        // Hidden 'ID'.
        'ID'   => array(
            'mode'     => PARTIAL_VOLATILE_VALUE,
            'selector' => 'input[name=ID]',
            'cb'       => 'article_partial_value',
        ),
        // HTML 'Title' field (in <head>).
        'html_title'   => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'title',
            'cb'       => 'article_partial_html_title',
        ),
        // 'Text/HTML/Preview' links region.
        'view_modes' => array(
            'mode'     => PARTIAL_STATIC,
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
        // 'Author' region.
        'author' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => 'div.author',
            'cb'       => 'article_partial_author',
        ),
        // 'Actions' region.
        'actions' => array(
            'mode'     => PARTIAL_VOLATILE,
            'selector' => '#txp-article-actions',
            'cb'       => 'article_partial_actions',
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

    if ($step !== 'create') {
        $step = "edit";
    }

    // Newly-saved article.
    if (!empty($GLOBALS['ID'])) {
        $ID = $GLOBALS['ID'];
    } else {
        $ID = $step === 'create' ? 0 : intval(gps('ID'));
    }

    if (!empty($ID) && !$concurrent) {
        // It's an existing article - off we go to the database.
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

        $rs['reset_time'] = $rs['publish_now'] = $rs['expire_now'] = false;

        if (gps('copy') && !gps('publish')) {
            $rs['ID'] = $rs['url_title'] = '';
            $rs['Status'] = STATUS_DRAFT;
        }
    } else {
        // Assume they came from post.
        $store_out = array('ID' => $ID) + psa($vars);

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

    if ($ID && !empty($sPosted)) {
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
    $partials = updatePartials($partials, $rs, array(PARTIAL_VOLATILE, PARTIAL_VOLATILE_VALUE));

    if ($refresh_partials) {
        $response[] = announce($message);
        $response[] = '$("#article_form [type=submit]").val(textpattern.gTxt("save"))';

        if ($Status < STATUS_LIVE) {
            $response[] = '$("#article_form").addClass("saved").removeClass("published")';
        } else {
            $response[] = '$("#article_form").addClass("published").removeClass("saved")';
        }

        if (!empty($GLOBALS['ID'])) {
            $response[] = "if (typeof window.history.replaceState == 'function') {history.replaceState({}, '', '?event=article&ID=$ID')}";
        }

        $response = array_merge($response, updateVolatilePartials($partials));
        send_script_response(join(";\n", $response));

        // Bail out.
        return;
    }

    // Get content for static partials.
    $partials = updatePartials($partials, $rs, PARTIAL_STATIC);

    $page_title = $ID ? $Title : gTxt('write');
    pagetop($page_title, $message);

    $class = array('async');

    if ($Status >= STATUS_LIVE) {
        $class[] = 'published';
    } elseif ($ID) {
        $class[] = 'saved';
    }

    echo n.tag_start('form', array(
            'class'  => $class,
            'id'     => 'article_form',
            'name'   => 'article_form',
            'method' => 'post',
            'action' => 'index.php',
        )).
        n.'<div class="txp-layout">';

    echo hInput('ID', $ID).
        eInput('article').
        sInput($step);

        echo n.'<div class="txp-layout-4col-3span">'.
        hed(gTxt('tab_write'), 1, array('class' => 'txp-heading'));

    echo n.'<div role="region" id="main_content">';

    echo n.'<div class="text" id="pane-text">'.$partials['title']['html'],
        $partials['author']['html'],
        $partials['body']['html'];
    if ($articles_use_excerpts) {
        echo $partials['excerpt']['html'];
    }
    echo n.'</div>';

    echo n.'<div class="txp-dialog">';
    echo n.$partials['view_modes']['html'];
    echo article_preview();
    echo '</div>';// End of .txp-dialog.

    echo n.'</div>'.// End of #main_content.
        n.'</div>'; // End of .txp-layout-4col-3span.

    // Sidebar column (only shown if in text editing view).
    echo n.'<div class="txp-layout-4col-alt">';

    // 'Publish/Save' button.
    echo $partials['actions']['html'];

    echo n.'<div role="region" id="supporting_content">';

    // 'Override form' selection.
    $form_pop = $allow_form_override ? form_pop($override_form, 'override-form', $rs['Section']) : '';
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

    // 'Sort and display' section.
    echo pluggable_ui(
        'article_ui',
        'sort_display',
        wrapRegion('txp-write-sort-group', $partials['status']['html'].$partials['section']['html'].$html_override, '', gTxt('sort_display')),
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
    if (empty($ID)) {
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

        $posted_block = tag(pluggable_ui(
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
                'div', array('class' => 'txp-form-field posted-now')
            ),
            array('sPosted' => $persist_timestamp) + $rs
        ), 'div', array('id' => 'publish-datetime-group'));

        // Expires.
        if (!empty($store_out['exp_year'])) {
            $persist_timestamp = safe_strtotime(
                $store_out['exp_year'].'-'.$store_out['exp_month'].'-'.$store_out['exp_day'].' '.
                $store_out['exp_hour'].':'.$store_out['exp_minute'].':'.$store_out['second']
            );
        } else {
            $persist_timestamp = 0;
        }

        $expires_block = tag(pluggable_ui(
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
            ).
            n.tag(
                checkbox('expire_now', '1', false, '', 'expire_now').
                n.tag(gTxt('set_expire_now'), 'label', array('for' => 'expire_now')),
                'div', array('class' => 'txp-form-field expire-now')
            ),
            $rs
        ), 'div', array('id' => 'expires-datetime-group'));
    } else {
        // Timestamp.
        $posted_block = $partials['posted']['html'];

        // Expires.
        $expires_block = $partials['expires']['html'];
    }

    echo wrapRegion('txp-dates-group', $posted_block.$expires_block, 'txp-dates-group-content', 'date_settings', 'article_dates');

    // 'Categories' section.
    $html_categories = pluggable_ui('article_ui', 'categories', $partials['categories']['html'], $rs);
    echo wrapRegion('txp-categories-group', $html_categories, 'txp-categories-group-content', 'categories', 'categories');

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
    // Unused by core, but leaving the placeholder for legacy plugin support.
    $html_advanced = pluggable_ui('article_ui', 'markup', '', $rs);

    if ($html_advanced) {
        echo wrapRegion('txp-advanced-group', $html_advanced, 'txp-advanced-group-content', 'advanced_options', 'article_advanced');
    }

    // Custom menu entries.
    echo pluggable_ui('article_ui', 'extend_col_1', '', $rs);

    // 'Recent articles' collapsible section.
    echo wrapRegion('txp-recent-group', $partials['recent_articles']['html'], 'txp-recent-group-content', 'recent_articles', 'article_recent');

    echo n.'</div>'; // End of #supporting_content.

    // Prev/next article links.
    echo $partials['article_nav']['html'];

    echo n.'</div>'; // End of .txp-layout-4col-alt.

    echo //tInput().
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
        txpspecialchars($field),
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
    $crit = callback_event('txp.article', 'neighbour.criteria', 0, compact('ID', 'whichway', 'sPosted'));

    return safe_field("ID", 'textpattern',
        "(Posted $dir FROM_UNIXTIME($sPosted) OR Posted = FROM_UNIXTIME($sPosted) AND ID $dir $ID) $crit ORDER BY Posted $ord, ID $ord LIMIT 1");
}

/**
 * Renders an article status field.
 *
 * @param  int $status Selected status
 * @return string HTML
 */

function status_display($status = 0)
{
    global $statuses;

    if (!$status) {
        $status = get_pref('default_publish_status', STATUS_LIVE);
        has_privs('article.publish') or $status = min($status, STATUS_PENDING);
    }

    $disabled = has_privs('article.publish') ? false : array(STATUS_LIVE, STATUS_STICKY);

    return inputLabel(
        'status',
        selectInput('Status', $statuses, $status, false, '', 'status', false, $disabled),
        'status',
        array('status', 'instructions_status'),
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
    global $txp_sections;

    $rs = $txp_sections;
    unset($rs['default']);

    if ($rs) {
        $options = array();

        foreach ($rs as $a) {
            $options[$a['name']] = array('title' => $a['title'], 'data-skin' => $a['skin']);
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
    static $rs = null;

    isset($rs) or $rs = getTree('root', 'article');

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

function tab($tabevent, $view, $tag = 'li')
{
    $state = ($view == $tabevent) ? 'active' : '';
    $pressed = ($view == $tabevent) ? 'true' : 'false';
    
    if (is_array($tabevent)) {
        list($tabevent, $label) = $tabevent + array(null, gTxt('text'));
    } else {
        $label = gTxt('view_'.$tabevent.'_short');
    }

    $link = href($label, '#', array(
        'data-view-mode' => $tabevent ? $tabevent : false,
        'aria-pressed'   => $pressed,
        'role'           => 'button',
    ));

    return $tag ? n.tag($link, 'li', array(
        'class' => $state,
        'id'    => 'tab-'.$tabevent,
    )) : $link;
}

/**
 * Renders 'override form' field.
 *
 * @param  string $form    The selected form
 * @param  string $id      HTML id to apply to the input control
 * @param  string $section The section that is currently in use
 * @return string HTML &lt;select&gt; input
 */

function form_pop($form, $id, $section)
{
    global $txp_sections;

    $skinforms = array();
    $form_types = get_pref('override_form_types');

    $rs = safe_rows('skin, name, type', 'txp_form', "type IN (".implode(",", quote_list(do_list($form_types))).") AND name != 'default' ORDER BY type,name");

    foreach ($txp_sections as $name => $row) {
        $skin = $row['skin'];

        if (!isset($skinforms[$skin])) {
            $skinforms[$skin] = array_column(array_filter($rs, function($v) use ($skin) {
                return $v['skin'] == $skin;
            }), 'name');
        }
    }

    script_js('var allForms = '.json_encode($skinforms, TEXTPATTERN_JSON), false);

    $skin = isset($txp_sections[$section]['skin']) ? $txp_sections[$section]['skin'] : false;
    $rs = $skin && isset($skinforms[$skin]) ? array_combine($skinforms[$skin], $skinforms[$skin]) : false;

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
            return gTxt('article_saved_pending');
        case STATUS_HIDDEN:
            return gTxt('article_saved_hidden');
        case STATUS_DRAFT:
            return gTxt('article_saved_draft');
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
    // Use preferred Textfilter as default and fallback.
    $hasfilter = new \Textpattern\Textfilter\Constraint(null);
    $validator = new Validator();

    foreach (array('textile_body', 'textile_excerpt') as $k) {
        $hasfilter->setValue($incoming[$k]);
        $validator->setConstraints($hasfilter);
        if (!$validator->validate()) {
            $incoming[$k] = get_pref('use_textile');
        }
    }

    $textile = new \Textpattern\Textile\Parser();
    $options = array('lite' => false);

    $incoming['Title_plain'] = trim($incoming['Title']);
    $incoming['Title_html'] = ''; // not used
    $incoming['Title'] = $textile->textileEncode($incoming['Title_plain']);

    $incoming['Body_html'] = Txp::get('\Textpattern\Textfilter\Registry')->filter(
        $incoming['textile_body'],
        $incoming['Body'],
        array('field' => 'Body', 'options' => $options, 'data' => $incoming)
    );

    $incoming['Excerpt_html'] = Txp::get('\Textpattern\Textfilter\Registry')->filter(
        $incoming['textile_excerpt'],
        $incoming['Excerpt'],
        array('field' => 'Excerpt', 'options' => $options, 'data' => $incoming)
    );

    return $incoming;
}

/**
 * Raises a ping callback so plugins can take action when an article is published.
 */

function do_pings()
{
    global $production_status;

    // Only ping for Live sites.
    if ($production_status !== 'live') {
        return;
    }

    callback_event('ping');
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
 * Renders article title partial.
 *
 * The rendered widget can be customised via the 'article_ui > title'
 * pluggable UI callback event.
 *
 * @param array $rs Article data
 */

function article_partial_title($rs)
{
    $out = inputLabel(
        'title',
        fInput('text', 'Title', preg_replace("/&amp;(?![#a-z0-9]+;)/i", "&", $rs['Title']), '', '', '', INPUT_LARGE, '', 'title', false, true),
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

    $out = n.'<div class="author">';

    if (!empty($ID)) {
        $out .= '<small>';
        $out .= gTxt('id').' '.txpspecialchars($ID).sp.span('&#183;', array('role' => 'separator')).sp.gTxt('posted_by').' '.txpspecialchars($AuthorID).sp.span('&#183;', array('role' => 'separator')).sp.safe_strftime('%d %b %Y %X', $sPosted);

        if ($sPosted != $sLastMod) {
            $out .= sp.span('&#124;', array('role' => 'separator')).sp.gTxt('modified_by').' '.txpspecialchars($LastModID).sp.span('&#183;', array('role' => 'separator')).sp.safe_strftime('%d %b %Y %X', $sLastMod);
        }

        $out .= '</small>';
    }

    $out .= '</div>';

    return pluggable_ui('article_ui', 'author', $out, $rs);
}

/* View/Duplicate/Create new article links.
 *
 * @param  array $rs Article data
 * @return string HTML
 */

function article_partial_actions($rs)
{
    global $txp_user;

    // 'Publish/Save' button.
    $push_button = '';

    if (empty($rs['ID'])) {
        if (has_privs('article.publish') && get_pref('default_publish_status', STATUS_LIVE) >= STATUS_LIVE) {
            $push_button = fInput('submit', 'publish', gTxt('publish'), 'publish');
        } else {
            $push_button = fInput('submit', 'publish', gTxt('save'), 'publish');
        }

        $push_button = graf($push_button, array('class' => 'txp-save'));
    } elseif (
        ($rs['Status'] >= STATUS_LIVE && has_privs('article.edit.published')) ||
        ($rs['Status'] >= STATUS_LIVE && $rs['AuthorID'] === $txp_user && has_privs('article.edit.own.published')) ||
        ($rs['Status'] < STATUS_LIVE && has_privs('article.edit')) ||
        ($rs['Status'] < STATUS_LIVE && $rs['AuthorID'] === $txp_user && has_privs('article.edit.own'))
    ) {
        $push_button = graf(fInput('submit', 'save', gTxt('save'), 'publish'), array('class' => 'txp-save'));
    }

    return n.'<div id="txp-article-actions" class="txp-save-zone">'.n.
        hInput('sPosted', $rs['sPosted']).
        hInput('sLastMod', $rs['sLastMod']).
        hInput('AuthorID', $rs['AuthorID']).
        hInput('LastModID', $rs['LastModID']).n.
        $push_button.
        graf($rs['ID']
            ? href('<span class="ui-icon ui-extra-icon-new-document"></span> '.gTxt('create_article'), 'index.php?event=article', array('class' => 'txp-new'))
            .article_partial_article_clone($rs)
            .article_partial_article_view($rs)
            : null,
            array(
                'class' => 'txp-actions',
        )).n.'</div>';
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

    return ($$custom_x_set !== '' ? custField($m[1], $$custom_x_set, $rs[$custom_x]) : '');
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
        if (!has_privs('article.preview')) {
            return;
        }

        $url = '?txpreview='.intval($ID).'.'.time(); // Article ID plus cachebuster.
    } else {
        $url = permlinkurl_id($ID);
    }

    return n.href('<span class="ui-icon ui-icon-notice"></span> '.gTxt('view'), $url, array(
        'class'  => 'txp-article-view',
        'id'     => 'article_partial_article_view',
        'rel'    => 'noopener',
        'target' => '_blank',
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
    $textarea_options = n.href(gTxt('view_preview_short'), '#', array(
        'class'             => 'txp-textarea-preview',
        'data-preview-link' => 'body',
    ));

    // Article markup selection.
    if (has_privs('article.set_markup')) {
        // Markup help.
        $help = '';
        $textfilter_opts = Txp::get('\Textpattern\Textfilter\Registry')->getMap();
        isset($textfilter_opts[$rs['textile_body']]) or $rs['textile_body'] = LEAVE_TEXT_UNTOUCHED;

        $html_markup = array();

        foreach ($textfilter_opts as $filter_key => $filter_name) {
            $thisHelp = Txp::get('\Textpattern\Textfilter\Registry')->getHelp($filter_key);
            $renderHelp = ($thisHelp) ? popHelp($thisHelp) : '';
            $selected = (string)$filter_key === (string)$rs['textile_body'];

            $html_markup[] = tag(
                $filter_name, 'option', array(
                    'data-id'   => $filter_key,
                    'data-help' => $renderHelp,
                    'selected'  => $selected,
                )
            );

            if ($selected) {
                $help = $renderHelp;
            }
        }

        // Note: not using span() for the textfilter help, because it doesn't render empty content.
        $html_markup = tag(implode(n, $html_markup),
            'select',
            array('class' => 'jquery-ui-selectmenu'))
            .tag_void('input', array(
                'class' => 'textfilter-value',
                'name'  => 'textile_body',
                'type'  => 'hidden',
                'value' => $rs['textile_body'],
            ));
        $textarea_options = n.'<label>'.gTxt('textfilter').n.$html_markup.'</label>'.
            '<span class="textfilter-help">'.$help.'</span>'.$textarea_options;
    }

    $textarea_options = '<div class="txp-textarea-options txp-textfilter-options no-ui-button">'.$textarea_options.'</div>';
    $out = inputLabel(
        'body',
        '<textarea id="body" name="Body" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_REGULAR.'">'.txpspecialchars($rs['Body']).'</textarea>',
        array('body', $textarea_options),
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
    $textarea_options = n.href(gTxt('view_preview_short'), '#', array(
        'class'             => 'txp-textarea-preview',
        'data-preview-link' => 'excerpt',
    ));

    // Excerpt markup selection.
    if (has_privs('article.set_markup')) {
        // Markup help.
        $help = '';
        $textfilter_opts = Txp::get('\Textpattern\Textfilter\Registry')->getMap();
        isset($textfilter_opts[$rs['textile_excerpt']]) or $rs['textile_excerpt'] = LEAVE_TEXT_UNTOUCHED;

        $html_markup = array();

        foreach ($textfilter_opts as $filter_key => $filter_name) {
            $thisHelp = Txp::get('\Textpattern\Textfilter\Registry')->getHelp($filter_key);
            $renderHelp = ($thisHelp) ? popHelp($thisHelp) : '';
            $selected = (string)$filter_key === (string)$rs['textile_excerpt'];

            $html_markup[] = tag(
                $filter_name, 'option', array(
                    'data-id'   => $filter_key,
                    'data-help' => $renderHelp,
                    'selected'  => $selected,
                )
            );

            if ($selected) {
                $help = $renderHelp;
            }
        }

        // Note: not using span() for the textfilter help, because it doesn't render empty content.
        $html_markup = tag(implode(n, $html_markup),
            'select',
            array('class' => 'jquery-ui-selectmenu'))
            .tag_void('input', array(
                'class' => 'textfilter-value',
                'name'  => 'textile_excerpt',
                'type'  => 'hidden',
                'value' => $rs['textile_excerpt'],
            ));
            $textarea_options = n.'<label>'.gTxt('textfilter').n.$html_markup.'</label>'.
                '<span class="textfilter-help">'.$help.'</span>'.$textarea_options;
    }

    $textarea_options = '<div class="txp-textarea-options txp-textfilter-options no-ui-button">'.$textarea_options.'</div>';
    $out = inputLabel(
        'excerpt',
        '<textarea id="excerpt" name="Excerpt" cols="'.INPUT_LARGE.'" rows="'.TEXTAREA_HEIGHT_SMALL.'">'.txpspecialchars($rs['Excerpt']).'</textarea>',
        array('excerpt', $textarea_options),
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
    global $view;

    $out = n.'<div class="txp-textarea-options txp-live-preview">'.
        checkbox2('', false, 0, 'live-preview').
        sp.tag(gTxt('live_preview'), 'label', array('for' => 'live-preview')).
        n.'</div>'.
        n.tag(tab(array('preview'), $view).tab(array('html', '<bdi dir="ltr">HTML</bdi>'), $view), 'ul');
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
        (has_privs('section.edit') ? n.eLink('section', 'list', '', '', gTxt('edit'), '', '', '', 'txp-option-link') : ''),
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
    $out = inputLabel(
        'category-1',
        category_popup('Category1', $rs['Category1'], 'category-1').
        (has_privs('category') ? n.eLink('category', 'list', '', '', gTxt('edit'), '', '', '', 'txp-option-link') : ''),
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
    );

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

    if (empty($ID)) {
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

        if (!empty($ID) && $comments_disabled_after) {
            $lifespan = $comments_disabled_after * 86400;
            $time_since = time() - intval($sPosted);

            if ($time_since > $lifespan) {
                $comments_expired = true;
            }
        }

        if ($comments_expired) {
            $invite = graf(gTxt('expired'), array('class' => 'comment-annotate-expired'));
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
            'div', array('class' => 'txp-form-field reset-time')
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
        n.tag(
            checkbox('expire_now', '1', $expire_now, '', 'expire_now').
            n.tag(gTxt('set_expire_now'), 'label', array('for' => 'expire_now')),
            'div', array('class' => 'txp-form-field expire-now')
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
            array('type' => get_pref('override_form_types'))
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

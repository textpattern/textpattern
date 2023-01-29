<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2023 The Textpattern Development Team
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
 * Generates comment tags.
 *
 * @since  4.9.0
 */

namespace Textpattern\Tag\Syntax;

class Comment
{
    public static function recent_comments($atts, $thing = null)
    {
        global $prefs;
        global $thisarticle, $thiscomment;
    
        extract(lAtts(array(
            'break'    => br,
            'class'    => __FUNCTION__,
            'form'     => '',
            'limit'    => 10,
            'offset'   => 0,
            'sort'     => 'posted DESC',
            'wraptag'  => '',
        ), $atts));
    
        $sort = preg_replace('/\bposted\b/', 'd.posted', $sort);
        $expired = ($prefs['publish_expired_articles']) ? '' : " AND (".now('expires')." <= t.Expires OR t.Expires IS NULL) ";
    
        $rs = startRows("SELECT d.name, d.email, d.web, d.message, d.discussid, UNIX_TIMESTAMP(d.Posted) AS time, t.ID AS thisid,
                UNIX_TIMESTAMP(t.Posted) AS posted, t.Title AS title, t.Section AS section, t.Category1, t.Category2, t.url_title
            FROM ".safe_pfx('txp_discuss')." AS d INNER JOIN ".safe_pfx('textpattern')." AS t ON d.parentid = t.ID
            WHERE t.Status >= ".STATUS_LIVE.$expired." AND d.visible = ".VISIBLE."
            ORDER BY ".sanitizeForSort($sort)."
            LIMIT ".intval($offset).", ".($limit ? intval($limit) : PHP_INT_MAX));
    
        if ($rs) {
            $out = array();
            $old_article = $thisarticle;
    
            while ($c = nextRow($rs)) {
                if ($form === '' && $thing === null) {
                    $out[] = href(
                        txpspecialchars($c['name']).' ('.escape_title($c['title']).')',
                        permlinkurl($c).'#c'.$c['discussid']
                    );
                } else {
                    $thiscomment['name'] = $c['name'];
                    $thiscomment['email'] = $c['email'];
                    $thiscomment['web'] = $c['web'];
                    $thiscomment['message'] = $c['message'];
                    $thiscomment['discussid'] = $c['discussid'];
                    $thiscomment['time'] = $c['time'];
    
                    // Allow permlink guesstimation in permlinkurl(), elsewhere.
                    $thisarticle['thisid'] = $c['thisid'];
                    $thisarticle['posted'] = $c['posted'];
                    $thisarticle['title'] = $c['title'];
                    $thisarticle['section'] = $c['section'];
                    $thisarticle['url_title'] = $c['url_title'];
    
                    if ($thing === null && $form !== '') {
                        $out[] = parse_form($form);
                    } else {
                        $out[] = parse($thing);
                    }
                }
            }
    
            if ($out) {
                unset($GLOBALS['thiscomment']);
                $thisarticle = $old_article;
    
                return doWrap($out, $wraptag, $break, $class);
            }
        }
    
        return '';
    }
    
    // -------------------------------------------------------------
    
    public static function popup_comments($atts, $thing = null)
    {
        extract(lAtts(array('form' => 'comments_display'), $atts));
    
        $rs = safe_row(
            "*, UNIX_TIMESTAMP(Posted) AS uPosted, UNIX_TIMESTAMP(LastMod) AS uLastMod, UNIX_TIMESTAMP(Expires) AS uExpires",
            'textpattern',
            "ID=".intval(gps('parentid'))." AND Status >= 4"
        );
    
        if ($rs) {
            populateArticleData($rs);
    
            return ($thing === null ? parse_form($form) : parse($thing));
        }
    
        return '';
    }
    
    // -------------------------------------------------------------
    
    public static function comments_form($atts, $thing = null)
    {
        global $thisarticle, $has_comments_preview;
        global $thiscommentsform; // TODO: Remove any uses of $thiscommentsform when removing deprecated attributes from below.
    
        // deprecated attributes since TXP 4.6. Most of these (except msgstyle)
        // were moved to the tags that occur within a comments_form, although
        // some of the names changed.
        $deprecated = array('isize', 'msgrows', 'msgcols', 'msgstyle',
            'previewlabel', 'submitlabel', 'rememberlabel', 'forgetlabel');
    
        foreach ($deprecated as $att) {
            if (isset($atts[$att])) {
                trigger_error(gTxt('deprecated_attribute', array('{name}' => $att)), E_USER_NOTICE);
            }
        }
    
        $atts = lAtts(array(
            'class'         => __FUNCTION__,
            'form'          => 'comment_form',
            'isize'         => '25',
            'msgcols'       => '25',
            'msgrows'       => '5',
            'msgstyle'      => '',
            'show_preview'  => empty($has_comments_preview),
            'wraptag'       => '',
            'previewlabel'  => gTxt('preview'),
            'submitlabel'   => gTxt('submit'),
            'rememberlabel' => gTxt('remember'),
            'forgetlabel'   => gTxt('forget'),
        ), $atts);
    
        extract($atts);
    
        $thiscommentsform = array_intersect_key($atts, array_flip($deprecated));
    
        assert_article();
    
        $out = '';
        $ip = serverSet('REMOTE_ADDR');
        $blocklisted = is_blocklisted($ip);
    
        if (!checkCommentsAllowed($thisarticle['thisid'])) {
            $out = graf(gTxt('comments_closed'), ' id="comments_closed"');
        } elseif ($blocklisted) {
            $out = graf(gTxt('your_ip_is_blocklisted_by'.' '.$blocklisted), ' id="comments_blocklisted"');
        } elseif (gps('commented') !== '') {
            $out = gTxt('comment_posted');
    
            if (gps('commented') === '0') {
                $out .= " ".gTxt('comment_moderated');
            }
    
            $out = graf($out, ' id="txpCommentInputForm"');
        } else {
            // Display a comment preview if required.
            if (ps('preview') && $show_preview) {
                $out = comments_preview(array());
            }
    
            extract(doDeEnt(psa(array('parentid', 'backpage'))));
    
            // If the form fields are filled (anything other than blank), pages really
            // should not be saved by a public cache (rfc2616/14.9.1).
            if (pcs('name') || pcs('email') || pcs('web')) {
                header('Cache-Control: private');
            }
    
            $url = $GLOBALS['pretext']['request_uri'];
    
            // Experimental clean URLs with only 404-error-document on Apache possibly
            // requires messy URLs for POST requests.
            if (defined('PARTLY_MESSY') && (PARTLY_MESSY)) {
                $url = hu.'?id='.intval($parentid);
            }
    
            $out .= '<form id="txpCommentInputForm" method="post" action="'.txpspecialchars($url).'#cpreview">'.
                n.'<div class="comments-wrapper">'.n. // Prevent XHTML Strict validation gotchas.
                ($thing === null ? parse_form($form) : parse($thing)).
                n.hInput('parentid', ($parentid ? $parentid : $thisarticle['thisid'])).
                n.hInput('backpage', (ps('preview') ? $backpage : $url)).
                n.'</div>'.
                n.'</form>';
        }
    
        return (!$wraptag ? $out : doTag($out, $wraptag, $class));
    }
    
    // -------------------------------------------------------------
    
    public static function comment_input($atts, $thing = null, $field = 'name', $clean = false)
    {
        global $prefs, $thiscommentsform;
    
        extract(lAtts(array(
            'class'       => '',
            'size'        => $thiscommentsform['isize'],
            'aria_label'  => '',
            'placeholder' => '',
        ), $atts));
    
        $warn = false;
        $val = is_callable($clean) ? $clean(pcs($field)) : pcs($field);
        $h5 = ($prefs['doctype'] === 'html5');
        $required = get_pref('comments_require_'.$field);
    
        if (!empty($class)) {
            $class = ' '.txpspecialchars($class);
        }
    
        if (ps('preview')) {
            $comment = getComment();
            $val = $comment[$field];
            $warn = $required && !$val;
        }
    
        return fInput('text', array(
                'name'         => $field,
                'aria-label'   => $aria_label,
                'autocomplete' => $field == 'web' ? 'url' : $field,
                'placeholder'  => $placeholder,
                'required'     => $h5 && $required
            ), $val, 'comment_'.$field.'_input'.$class.($warn ? ' comments_error' : ''), '', '', $size, '', $field);
    }
    
    // -------------------------------------------------------------
    
    public static function comment_message_input($atts)
    {
        global $prefs, $thiscommentsform;
    
        extract(lAtts(array(
            'class'       => '',
            'rows'        => $thiscommentsform['msgrows'],
            'cols'        => $thiscommentsform['msgcols'],
            'aria_label'  => '',
            'placeholder' => ''
        ), $atts));
    
        $style = $thiscommentsform['msgstyle'];
        $commentwarn = false;
        $n_message = 'message';
        $formnonce = '';
        $message = '';
    
        if (!empty($class)) {
            $class = ' '.txpspecialchars($class);
        }
    
        if (ps('preview')) {
            $comment = getComment();
            $message = $comment['message'];
            $split = rand(1, 31);
            $nonce = getNextNonce();
            $secret = getNextSecret();
            safe_insert('txp_discuss_nonce', "issue_time = NOW(), nonce = '".doSlash($nonce)."', secret = '".doSlash($secret)."'");
            $n_message = md5('message'.$secret);
            $formnonce = n.hInput(substr($nonce, 0, $split), substr($nonce, $split));
            $commentwarn = (!trim($message));
        }
    
        $attr = join_atts(array(
            'cols'        => intval($cols),
            'rows'        => intval($rows),
            'required'    => $prefs['doctype'] === 'html5',
            'style'       => $style,
            'aria-label'  => $aria_label,
            'placeholder' => $placeholder
        ));
    
        return '<textarea class="txpCommentInputMessage'.$class.(($commentwarn) ? ' comments_error"' : '"').
            ' id="message" name="'.$n_message.'"'.$attr.
            '>'.txpspecialchars(substr(trim($message), 0, 65535)).'</textarea>'.
            callback_event('comment.form').
            $formnonce;
    }
    
    // -------------------------------------------------------------
    
    public static function comment_remember($atts)
    {
        global $thiscommentsform;
    
        extract(lAtts(array(
            'class'         => '',
            'rememberlabel' => $thiscommentsform['rememberlabel'],
            'forgetlabel'   => $thiscommentsform['forgetlabel']
        ), $atts));
    
        if (!empty($class)) {
            $class = ' class="'.txpspecialchars($class).'"';
        }
    
        extract(doDeEnt(psa(array('checkbox_type', 'remember', 'forget'))));
    
        if (!ps('preview')) {
            $rememberCookie = cs('txp_remember');
    
            if (!$rememberCookie) {
                $checkbox_type = 'remember';
            } else {
                $checkbox_type = 'forget';
            }
    
            // Inhibit default remember.
            if ($forget == 1 || (string) $rememberCookie === '0') {
                destroyCookies();
            }
        }
    
        if ($checkbox_type == 'forget') {
            $checkbox = checkbox('forget', 1, $forget, '', 'forget').' '.tag(txpspecialchars($forgetlabel), 'label', ' for="forget"'.$class);
        } else {
            $checkbox = checkbox('remember', 1, $remember, '', 'remember').' '.tag(txpspecialchars($rememberlabel), 'label', ' for="remember"'.$class);
        }
    
        $checkbox .= ' '.hInput('checkbox_type', $checkbox_type);
    
        return $checkbox;
    }
    
    // -------------------------------------------------------------
    
    public static function comment_preview($atts)
    {
        global $thiscommentsform;
    
        extract(lAtts(array(
            'class' => '',
            'label' => $thiscommentsform['previewlabel']
        ), $atts));
    
        if (!empty($class)) {
            $class = ' '.txpspecialchars($class);
        }
    
        return fInput('submit', 'preview', $label, 'button'.$class, '', '', '', '', 'txpCommentPreview', false);
    }
    
    // -------------------------------------------------------------
    
    public static function comment_submit($atts)
    {
        global $thiscommentsform;
    
        extract(lAtts(array(
            'class' => '',
            'label' => $thiscommentsform['submitlabel']
        ), $atts));
    
        if (!empty($class)) {
            $class = ' '.txpspecialchars($class);
        }
    
        // If all fields check out, the submit button is active/clickable.
        if (ps('preview')) {
            return fInput('submit', 'submit', $label, 'button'.$class, '', '', '', '', 'txpCommentSubmit', false);
        } else {
            return fInput('submit', 'submit', $label, 'button disabled'.$class, '', '', '', '', 'txpCommentSubmit', true);
        }
    }
    
    // -------------------------------------------------------------
    
    public static function comments_error($atts)
    {
        extract(lAtts(array(
            'break'   => 'br',
            'class'   => __FUNCTION__,
            'wraptag' => 'div',
        ), $atts));
    
        $evaluator = & get_comment_evaluator();
    
        $errors = $evaluator->get_result_message();
    
        if ($errors) {
            return doWrap($errors, $wraptag, $break, $class);
        }
    }
    
    // -------------------------------------------------------------
    
    public static function comments($atts, $thing = null)
    {
        global $thisarticle, $prefs;
        $comments_are_ol = !empty($prefs['comments_are_ol']);
    
        extract(lAtts(array(
            'form'    => 'comments',
            'wraptag' => ($comments_are_ol ? 'ol' : ''),
            'break'   => ($comments_are_ol ? 'li' : 'div'),
            'class'   => __FUNCTION__,
            'limit'   => 0,
            'offset'  => 0,
            'sort'    => 'posted ASC',
        ), $atts));
    
        assert_article();
    
        if (!$thisarticle['comments_count']) {
            return '';
        }
    
        $qparts = array(
            "parentid = ".intval($thisarticle['thisid'])." AND visible = ".VISIBLE,
            "ORDER BY ".sanitizeForSort($sort),
            ($limit) ? "LIMIT ".intval($offset).", ".intval($limit) : '',
        );
    
        $rs = safe_rows_start("*, UNIX_TIMESTAMP(posted) AS time", 'txp_discuss', join(' ', $qparts));
    
        $out = '';
    
        if ($rs) {
            $comments = array();
    
            while ($vars = nextRow($rs)) {
                $GLOBALS['thiscomment'] = $vars;
                $comments[] = ($thing === null ? parse_form($form) : parse($thing)).n;
                unset($GLOBALS['thiscomment']);
            }
    
            $out .= doWrap($comments, $wraptag, $break, $class);
        }
    
        return $out;
    }
    
    // -------------------------------------------------------------
    
    public static function comments_preview($atts, $thing = null)
    {
        global $has_comments_preview;
    
        if (!ps('preview')) {
            return '';
        }
    
        extract(lAtts(array(
            'form'    => 'comments',
            'wraptag' => '',
            'class'   => __FUNCTION__,
        ), $atts));
    
        assert_article();
    
        $preview = psa(array('name', 'email', 'web', 'message', 'parentid', 'remember'));
        $preview['time'] = time();
        $preview['discussid'] = 0;
        $preview['name'] = strip_tags($preview['name']);
        $preview['email'] = clean_url($preview['email']);
    
        if ($preview['message'] == '') {
            $in = getComment();
            $preview['message'] = $in['message'];
        }
    
        // It is called 'message', not 'novel'!
        $preview['message'] = markup_comment(substr(trim($preview['message']), 0, 65535));
    
        $preview['web'] = clean_url($preview['web']);
    
        $GLOBALS['thiscomment'] = $preview;
        $comments = ($thing === null ? parse_form($form) : parse($thing)).n;
        unset($GLOBALS['thiscomment']);
        $out = doTag($comments, $wraptag, $class);
    
        // Set a flag to tell the comments_form tag that it doesn't have to show
        // a preview.
        $has_comments_preview = true;
    
        return $out;
    }
    
    // -------------------------------------------------------------
    
    public static function comment_permlink($atts, $thing)
    {
        global $thisarticle, $thiscomment;
    
        extract(lAtts(array('anchor' => empty($thiscomment['has_anchor_tag'])), $atts));
    
        assert_article();
        assert_comment();
    
        extract($thiscomment);
    
        $dlink = permlinkurl($thisarticle).'#c'.$discussid;
    
        $thing = parse($thing);
    
        $name = ($anchor ? ' id="c'.$discussid.'"' : '');
    
        return tag((string)$thing, 'a', ' href="'.$dlink.'"'.$name);
    }
    
    // -------------------------------------------------------------
    
    public static function comment_id()
    {
        global $thiscomment;
    
        assert_comment();
    
        return $thiscomment['discussid'];
    }
    
    // -------------------------------------------------------------
    
    public static function comment_name($atts)
    {
        global $thiscomment, $prefs;
        static $encoder = null;
    
        extract(lAtts(array('link' => 1), $atts));
    
        assert_comment();
        isset($encoder) or $encoder = \Txp::get('\Textpattern\Mail\Encode');
    
        extract($thiscomment);
    
        $name = txpspecialchars($name);
    
        if ($link) {
            $web = self::comment_web();
            $nofollow = empty($prefs['comment_nofollow']) ? '' : ' rel="nofollow"';
    
            if (!empty($web)) {
                return href($name, $web, $nofollow);
            }
    
            if ($email && empty($prefs['never_display_email'])) {
                return href($name, $encoder->entityObfuscateAddress('mailto:'.$email), $nofollow);
            }
        }
    
        return $name;
    }
    
    // -------------------------------------------------------------
    
    public static function comment_email()
    {
        global $thiscomment;
    
        assert_comment();
    
        return txpspecialchars($thiscomment['email']);
    }
    
    // -------------------------------------------------------------
    
    public static function comment_web()
    {
        global $thiscomment;
    
        assert_comment();
    
        if (preg_match('/^\S/', $thiscomment['web'])) {
            // Prepend default protocol 'http' for all non-local URLs.
            if (!preg_match('!^https?://|^#|^/[^/]!', $thiscomment['web'])) {
                $thiscomment['web'] = 'http://'.$thiscomment['web'];
            }
    
            return txpspecialchars($thiscomment['web']);
        }
    
        return '';
    }
    
    // -------------------------------------------------------------
    
    public static function comment_message()
    {
        global $thiscomment;
    
        assert_comment();
    
        return $thiscomment['message'];
    }
    
    // -------------------------------------------------------------
    
    public static function comment_anchor()
    {
        global $thiscomment;
    
        assert_comment();
    
        $thiscomment['has_anchor_tag'] = 1;
    
        return '<a id="c'.$thiscomment['discussid'].'"></a>';
    }    
}

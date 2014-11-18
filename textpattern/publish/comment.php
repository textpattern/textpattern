<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2005 Dean Allen
 * Copyright (C) 2014 The Textpattern Development Team
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
 * Collection of comment tools.
 *
 * @package Comment
 */

/**
 * Gets comments as an array from the given article.
 *
 * @param  int        $id The article ID
 * @return array|null An array of comments, or NULL on error
 * @example
 * if ($comments = fetchComments(12))
 * {
 *     print_r($comments);
 * }
 */

function fetchComments($id)
{
    $rs = safe_rows(
        '*, unix_timestamp(posted) as time',
        'txp_discuss',
        'parentid='.intval($id).' and visible='.VISIBLE.' order by posted asc'
    );

    if ($rs) {
        return $rs;
    }
}

/**
 * Returns a formatted comment thread and form.
 *
 * This function returns parsed 'comments_display' form template.
 *
 * @param  int         $id The article
 * @return string|null HTML
 * @example
 * echo discuss(12);
 */

function discuss($id)
{
    $rs = safe_row(
        '*, unix_timestamp(Posted) as uPosted, unix_timestamp(LastMod) as uLastMod, unix_timestamp(Expires) as uExpires',
        'textpattern',
        'ID='.intval($id).' and Status >= 4'
    );

    if ($rs) {
        populateArticleData($rs);
        $result = parse_form('comments_display');

        return $result;
    }

    return '';
}

/**
 * Gets next nonce.
 *
 * @param  bool   $check_only
 * @return string A random MD5 hash
 */

function getNextNonce($check_only = false)
{
    static $nonce = '';

    if (!$nonce && !$check_only) {
        $nonce = md5(uniqid(rand(), true));
    }

    return $nonce;
}

/**
 * Gets next secret.
 *
 * @param  bool   $check_only
 * @return string A random MD5 hash
 */

function getNextSecret($check_only = false)
{
    static $secret = '';

    if (!$secret && !$check_only) {
        $secret = md5(uniqid(rand(), true));
    }

    return $secret;
}

/**
 * Renders a HTML comment form.
 *
 * @param  int    $id   The Article ID
 * @param  array  $atts An array of attributes
 * @return string HTML
 */

function commentForm($id, $atts = null)
{
    global $prefs;
    extract($prefs);

    $h5 = ($doctype == 'html5');

    extract(lAtts(array(
        'isize'         => '25',
        'msgrows'       => '5',
        'msgcols'       => '25',
        'msgstyle'      => '',
        'form'          => 'comment_form',
        'previewlabel'  => gTxt('preview'),
        'submitlabel'   => gTxt('submit'),
        'rememberlabel' => gTxt('remember'),
        'forgetlabel'   => gTxt('forget'),
    ), $atts, 0));

    $namewarn = false;
    $emailwarn = false;
    $commentwarn = false;
    $name = pcs('name');
    $email = clean_url(pcs('email'));
    $web = clean_url(pcs('web'));
    $n_message = 'message';

    extract(doDeEnt(psa(array(
        'checkbox_type',
        'remember',
        'forget',
        'parentid',
        'preview',
        'message',
        'submit',
        'backpage',
    ))));

    if ($message == '') { // Second or later preview will have randomised message-field name.
        $in = getComment();
        $message = doDeEnt($in['message']);
    }

    if ($preview) {
        $name  = ps('name');
        $email = clean_url(ps('email'));
        $web   = clean_url(ps('web'));
        $nonce = getNextNonce();
        $secret = getNextSecret();
        safe_insert("txp_discuss_nonce", "issue_time=now(), nonce='".doSlash($nonce)."', secret='".doSlash($secret)."'");
        $n_message = md5('message'.$secret);

        $namewarn = ($comments_require_name && !trim($name));
        $emailwarn = ($comments_require_email && !trim($email));
        $commentwarn = (!trim($message));

        $evaluator = & get_comment_evaluator();

        if ($namewarn) {
            $evaluator->add_estimate(RELOAD, 1, gTxt('comment_name_required'));
        }

        if ($emailwarn) {
            $evaluator->add_estimate(RELOAD, 1, gTxt('comment_email_required'));
        }

        if ($commentwarn) {
            $evaluator->add_estimate(RELOAD, 1, gTxt('comment_required'));
        }
    } else {
        $rememberCookie = cs('txp_remember');

        if ($rememberCookie === '') {
            $checkbox_type = 'remember';
            $remember = 1;
        } elseif ($rememberCookie == 1) {
            $checkbox_type = 'forget';
        } else {
            $checkbox_type = 'remember';
        }
    }

    // If the form fields are filled (anything other than blank), pages really
    // should not be saved by a public cache (rfc2616/14.9.1).
    if ($name || $email || $web) {
        header('Cache-Control: private');
    }

    $parentid = (!$parentid) ? $id : $parentid;

    $url = $GLOBALS['pretext']['request_uri'];

    // Experimental clean URLs with only 404-error-document on Apache possibly
    // requires messy URLs for POST requests.
    if (defined('PARTLY_MESSY') and (PARTLY_MESSY)) {
        $url = hu.'?id='.intval($parentid);
    }

    $out = '<form id="txpCommentInputForm" method="post" action="'.txpspecialchars($url).'#cpreview">'.

        // Prevent XHTML Strict validation gotchas.
        n.'<div class="comments-wrapper">'.n.n;

    $Form = fetch('Form', 'txp_form', 'name', $form);

    $required = ($h5) ? ' required' : '';

    $msgstyle = ($msgstyle ? ' style="'.$msgstyle.'"' : '');
    $msgrows = ($msgrows and is_numeric($msgrows)) ? ' rows="'.intval($msgrows).'"' : '';
    $msgcols = ($msgcols and is_numeric($msgcols)) ? ' cols="'.intval($msgcols).'"' : '';

    $textarea = '<textarea id="message" name="'.$n_message.'"'.$msgcols.$msgrows.$msgstyle.$required.
        ' class="txpCommentInputMessage'.(($commentwarn) ? ' comments_error"' : '"').
        '>'.txpspecialchars(substr(trim($message), 0, 65535)).'</textarea>';

    // By default, the submit button is visible but disabled.
    $comment_submit_button = fInput('submit', 'submit', $submitlabel, 'button disabled', '', '', '', '', 'txpCommentSubmit', true);

    // If all fields check out, the submit button is active/clickable.
    if ($preview) {
        $comment_submit_button = fInput('submit', 'submit', $submitlabel, 'button', '', '', '', '', 'txpCommentSubmit', false);
    }

    if ($checkbox_type == 'forget') {
        // Inhibit default remember.
        if ($forget == 1) {
            destroyCookies();
        }

        $checkbox = checkbox('forget', 1, $forget, '', 'forget').' '.tag(txpspecialchars($forgetlabel), 'label', ' for="forget"');
    } else {
        // Inhibit default remember.
        if ($remember != 1) {
            destroyCookies();
        }

        $checkbox = checkbox('remember', 1, $remember, '', 'remember').' '.tag(txpspecialchars($rememberlabel), 'label', ' for="remember"');
    }

    $checkbox .= ' '.hInput('checkbox_type', $checkbox_type);

    $vals = array(
        'comment_name_input'    => fInput('text', 'name', $name, 'comment_name_input'.($namewarn ? ' comments_error' : ''), '', '', $isize, '', 'name', false, $h5 && $comments_require_name),
        'comment_email_input'   => fInput($h5 ? 'email' : 'text', 'email', $email, 'comment_email_input'.($emailwarn ? ' comments_error' : ''), '', '', $isize, '', 'email', false, $h5 && $comments_require_email),
        'comment_web_input'     => fInput($h5 ? 'text' : 'text', 'web', $web, 'comment_web_input', '', '', $isize, '', 'web', false, false), /* TODO: maybe use type = 'url' once browsers are less strict */
        'comment_message_input' => $textarea.'<!-- plugin-place-holder -->',
        'comment_remember'      => $checkbox,
        'comment_preview'       => fInput('submit', 'preview', $previewlabel, 'button', '', '', '', '', 'txpCommentPreview', false),
        'comment_submit'        => $comment_submit_button,
    );

    foreach ($vals as $a => $b) {
        $Form = str_replace('<txp:'.$a.' />', $b, $Form);
    }

    $form = parse($Form);

    $out .= $form.
        n.hInput('parentid', $parentid);

    $split = rand(1, 31);

    $out .= ($preview) ? n.hInput(substr($nonce, 0, $split), substr($nonce, $split)) : '';

    $out .= (!$preview) ?
        n.hInput('backpage', $url) :
        n.hInput('backpage', $backpage);

    $out = str_replace('<!-- plugin-place-holder -->', callback_event('comment.form'), $out);

    $out .= n.n.'</div>'.n.'</form>';

    return $out;
}

/**
 * Parses a &lt;txp:popup_comments /&gt; tag.
 *
 * @param  int    $id The article's ID
 * @return string HTML
 */

function popComments($id)
{
    global $sitename, $s, $thisarticle;
    $preview = gps('preview');
    $h3 = ($preview) ? hed(gTxt('message_preview'), 3) : '';
    $discuss = discuss($id);
    ob_start('parse');
    $out = fetch_form('popup_comments');
    $out = str_replace("<txp:popup_comments />", $discuss, $out);

    return $out;
}

/**
 * Remembers comment form values.
 *
 * Creates a HTTP cookie for each value.
 *
 * @param string $name  The name
 * @param string $email The email address
 * @param string $web   The website
 */

function setCookies($name, $email, $web)
{
    $cookietime = time() + (365 * 24 * 3600);
    ob_start();
    setcookie("txp_name", $name, $cookietime, "/");
    setcookie("txp_email", $email, $cookietime, "/");
    setcookie("txp_web", $web, $cookietime, "/");
    setcookie("txp_last", date("H:i d/m/Y"), $cookietime, "/");
    setcookie("txp_remember", '1', $cookietime, "/");
}

/**
 * Deletes HTTP cookies created by the comment form.
 */

function destroyCookies()
{
    $cookietime = time() - 3600;
    ob_start();
    setcookie("txp_name", '', $cookietime, "/");
    setcookie("txp_email", '', $cookietime, "/");
    setcookie("txp_web", '', $cookietime, "/");
    setcookie("txp_last", '', $cookietime, "/");
    setcookie("txp_remember", '0', $cookietime + (365 * 25 * 3600), "/");
}

/**
 * Gets the received comment.
 *
 * Comment spam filter plugins should call this function to fetch
 * comment contents.
 *
 * @return  array
 * @example
 * print_r(
 *     getComment()
 * );
 */

function getComment()
{
    $c = psa(array(
        'parentid',
        'name',
        'email',
        'web',
        'message',
        'backpage',
        'remember',
    ));

    $n = array();

    foreach (stripPost() as $k => $v) {
        if (preg_match('#^[A-Fa-f0-9]{32}$#', $k.$v)) {
            $n[] = doSlash($k.$v);
        }
    }

    $c['nonce'] = '';
    $c['secret'] = '';

    if (!empty($n)) {
        $rs = safe_row('nonce, secret', 'txp_discuss_nonce', "nonce in ('".join("','", $n)."')");
        $c['nonce'] = $rs['nonce'];
        $c['secret'] = $rs['secret'];
    }
    $c['message'] = ps(md5('message'.$c['secret']));

    return $c;
}

/**
 * Saves a comment.
 */

function saveComment()
{
    global $siteurl, $comments_moderate, $comments_sendmail, $comments_disallow_images, $prefs;

    $ref = serverset('HTTP_REFERRER');
    $in = getComment();
    $evaluator = & get_comment_evaluator();

    extract($in);

    if (!checkCommentsAllowed($parentid)) {
        txp_die(gTxt('comments_closed'), '403');
    }

    $ip = serverset('REMOTE_ADDR');

    if (!checkBan($ip)) {
        txp_die(gTxt('you_have_been_banned'), '403');
    }

    $blacklisted = is_blacklisted($ip);

    if ($blacklisted) {
        txp_die(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), '403');
    }

    $web = clean_url($web);
    $email = clean_url($email);

    if ($remember == 1 || ps('checkbox_type') == 'forget' && ps('forget') != 1) {
        setCookies($name, $email, $web);
    } else {
        destroyCookies();
    }

    $name = doSlash(strip_tags(deEntBrackets($name)));
    $web = doSlash(strip_tags(deEntBrackets($web)));
    $email = doSlash(strip_tags(deEntBrackets($email)));
    $message = substr(trim($message), 0, 65535);
    $message2db = doSlash(markup_comment($message));

    $isdup = safe_row(
        "message,name",
        "txp_discuss",
        "name='$name' and message='$message2db' and ip='".doSlash($ip)."'"
    );

    if (
        ($prefs['comments_require_name'] && !trim($name)) ||
        ($prefs['comments_require_email'] && !trim($email)) ||
        (!trim($message))
    ) {
        $evaluator->add_estimate(RELOAD, 1); // The error-messages are added in the preview-code.
    }

    if ($isdup) {
        $evaluator->add_estimate(RELOAD, 1); // FIXME? Tell the user about dupe?
    }

    if (($evaluator->get_result() != RELOAD) && checkNonce($nonce)) {
        callback_event('comment.save');
        $visible = $evaluator->get_result();

        if ($visible != RELOAD) {
            $parentid = assert_int($parentid);
            $commentid = safe_insert(
                "txp_discuss",
                "parentid = $parentid,
                 name     = '$name',
                 email    = '$email',
                 web      = '$web',
                 ip       = '".doSlash($ip)."',
                 message  = '$message2db',
                 visible  = ".intval($visible).",
                 posted   = now()"
            );

            if ($commentid) {
                safe_update("txp_discuss_nonce", "used = 1", "nonce='".doSlash($nonce)."'");

                if ($prefs['comment_means_site_updated']) {
                    update_lastmod();
                }

                callback_event('comment.saved', '', false, compact(
                    'message',
                    'name',
                    'email',
                    'web',
                    'parentid',
                    'commentid',
                    'ip',
                    'visible'
                ));

                mail_comment($message, $name, $email, $web, $parentid, $commentid);

                $updated = update_comments_count($parentid);
                $backpage = substr($backpage, 0, $prefs['max_url_len']);
                $backpage = preg_replace("/[\x0a\x0d#].*$/s", '', $backpage);
                $backpage = preg_replace("#(https?://[^/]+)/.*$#", "$1", hu).$backpage;

                if (defined('PARTLY_MESSY') and (PARTLY_MESSY)) {
                    $backpage = permlinkurl_id($parentid);
                }

                $backpage .= ((strstr($backpage, '?')) ? '&' : '?').'commented='.(($visible == VISIBLE) ? '1' : '0');

                txp_status_header('302 Found');

                if ($comments_moderate) {
                    header('Location: '.$backpage.'#txpCommentInputForm');
                } else {
                    header('Location: '.$backpage.'#c'.sprintf("%06s", $commentid));
                }

                log_hit('302');
                $evaluator->write_trace();
                exit;
            }
        }
    }

    // Force another Preview.
    $_POST['preview'] = RELOAD;
    //$evaluator->write_trace();
}

/**
 * Comment evaluator.
 *
 * Validates and filters comments. Keeps out spam.
 *
 * @package Comment
 */

class comment_evaluation
{
    /**
     * Stores estimated statuses.
     *
     * @var array
     */

    public $status;

    /**
     * Stores estimated messages.
     *
     * @var array
     */

    public $message;

    /**
     * Debug log.
     *
     * @var array
     */

    public $txpspamtrace = array();

    /**
     * List of available statuses.
     *
     * @var array
     */

    public $status_text = array();

    /**
     * Constructor.
     */

    public function __construct()
    {
        global $prefs;
        extract(getComment());

        $this->status = array(
            SPAM => array(),
            MODERATE => array(),
            VISIBLE => array(),
            RELOAD => array(),
        );

        $this->status_text = array(
            SPAM => gTxt('spam'),
            MODERATE => gTxt('unmoderated'),
            VISIBLE  => gTxt('visible'),
            RELOAD => gTxt('reload'),
        );

        $this->message = $this->status;
        $this->txpspamtrace[] = "Comment on $parentid by $name (".safe_strftime($prefs['archive_dateformat'], time()).")";

        if ($prefs['comments_moderate']) {
            $this->status[MODERATE][] = 0.5;
        } else {
            $this->status[VISIBLE][] = 0.5;
        }
    }

    /**
     * Adds an estimate about the comment's status.
     *
     * @param int    $type        The status, either SPAM, MODERATE, VISIBLE or  RELOAD
     * @param float  $probability Estimates probability - throughout 0 to 1, e.g. 0.75
     * @param string $msg         The error or success message shown to the user
     * @example
     * $evaluator =& get_comment_evaluator();
     * $evaluator->add_estimate(RELOAD, 1, 'Message');
     */

    public function add_estimate($type = SPAM, $probability = 0.75, $msg = '')
    {
        global $production_status;

        if (!array_key_exists($type, $this->status)) {
            trigger_error(gTxt('unknown_spam_estimate'), E_USER_WARNING);
        }

        $this->txpspamtrace[] = "   $type; ".max(0, min(1, $probability))."; $msg";
        //FIXME trace is only viewable for RELOADS. Maybe add info to HTTP-Headers in debug-mode

        $this->status[$type][] = max(0, min(1, $probability));

        if (trim($msg)) {
            $this->message[$type][] = $msg;
        }
    }

    /**
     * Gets resulting estimated status.
     *
     * @param  string     $result_type If 'numeric' returns the ID of the status, a localised label otherwise
     * @return int|string
     * @example
     * $evaluator =& get_comment_evaluator();
     * print_r(
     *     $evaluator->get_result()
     * );
     */

    public function get_result($result_type = 'numeric')
    {
        $result = array();

        foreach ($this->status as $key => $value) {
            $result[$key] = array_sum($value)/max(1, count($value));
        }

        arsort($result, SORT_NUMERIC);
        reset($result);

        return (($result_type == 'numeric') ? key($result) : $this->status_text[key($result)]);
    }

    /**
     * Gets resulting success or error message.
     *
     * @return array
     * @example
     * $evaluator =& get_comment_evaluator();
     * echo $evaluator->get_result_message();
     */

    public function get_result_message()
    {
        return $this->message[$this->get_result()];
    }

    /**
     * Writes a debug log.
     */

    public function write_trace()
    {
        global $prefs;
        $file = $prefs['tempdir'].DS.'evaluator_trace.php';

        if (!file_exists($file)) {
            $fp = fopen($file, 'wb');

            if ($fp) {
                fwrite($fp, "<?php return; ?>\n".
                    "This trace-file tracks saved comments. (created ".safe_strftime($prefs['archive_dateformat'], time()).")\n".
                    "Format is: Type; Probability; Message (Type can be -1 => spam, 0 => moderate, 1 => visible)\n\n"
                );
            }
        } else {
            $fp = fopen($file, 'ab');
        }

        if ($fp) {
            fwrite($fp, implode("\n", $this->txpspamtrace));
            fwrite($fp, "\n  RESULT: ".$this->get_result()."\n\n");
            fclose($fp);
        }
    }
}

/**
 * Gets a comment evaluator instance.
 *
 * @return comment_evaluation
 */

function &get_comment_evaluator()
{
    static $instance;

    // If the instance is not there, create one
    if (!isset($instance)) {
        $instance = new comment_evaluation();
    }

    return $instance;
}

/**
 * Verifies a given nonce.
 *
 * This function will also do clean up and deletes expired nonces.
 *
 * @param  string $nonce The nonce
 * @return bool   TRUE if the nonce is valid
 * @see    getNextNonce()
 */

function checkNonce($nonce)
{
    if (!$nonce || !preg_match('#^[a-zA-Z0-9]*$#', $nonce)) {
        return false;
    }

    // Delete expired nonces.
    safe_delete("txp_discuss_nonce", "issue_time < date_sub(now(),interval 10 minute)");

    // Check for nonce.
    return (safe_row("*", "txp_discuss_nonce", "nonce='".doSlash($nonce)."' and used = 0")) ? true : false;
}

/**
 * Checks if an IP address is banned.
 *
 * @param  string $ip The IP address
 * @return bool   TRUE if the IP is not banned
 * @example
 * if (checkBan('127.0.0.1') === false)
 * {
 *     echo "IP address is banned.";
 * }
 */

    function checkBan($ip)
    {
        return (!fetch("ip", "txp_discuss_ipban", "ip", $ip)) ? true : false;
    }

/**
 * Checks if comments are open for the given article.
 *
 * @param  int  $id The article.
 * @return bool FALSE if comments are closed
 * @example
 * if (checkCommentsAllowed(12))
 * {
 *     echo "Article accepts comments";
 * }
 */

function checkCommentsAllowed($id)
{
    global $use_comments, $comments_disabled_after, $thisarticle;

    $id = intval($id);

    if (!$use_comments || !$id) {
        return false;
    }

    if (isset($thisarticle['thisid']) && ($thisarticle['thisid'] == $id) && isset($thisarticle['annotate'])) {
        $Annotate = $thisarticle['annotate'];
        $uPosted  = $thisarticle['posted'];
    } else {
        extract(
            safe_row(
                "Annotate,unix_timestamp(Posted) as uPosted",
                "textpattern",
                "ID = $id"
            )
        );
    }

    if ($Annotate != 1) {
        return false;
    }

    if ($comments_disabled_after) {
        $lifespan = ($comments_disabled_after * 86400);
        $timesince = (time() - $uPosted);

        return ($lifespan > $timesince);
    }

    return true;
}

/**
 * Renders a Textile help link.
 *
 * @return string HTML
 */

function comments_help()
{
    return '<a id="txpCommentHelpLink" href="'.HELP_URL.'?item=textile_comments&amp;language='.LANG.'" onclick="window.open(this.href, \'popupwindow\', \'width=300,height=400,scrollbars,resizable\'); return false;">'.gTxt('textile_help').'</a>';
}

/**
 * Emails a new comment to the article's author.
 *
 * This function can only be executed directly after a comment was sent,
 * otherwise it will not run properly.
 *
 * Will not send comments flagged as spam, and follows site's
 * comment preferences.
 *
 * @param string $message   The comment message
 * @param string $cname     The comment name
 * @param string $cemail    The comment email
 * @param string $cweb      The comment website
 * @param int    $parentid  The article ID
 * @param int    $discussid The comment ID
 */

function mail_comment($message, $cname, $cemail, $cweb, $parentid, $discussid)
{
    global $sitename, $comments_sendmail;

    if (!$comments_sendmail) {
        return;
    }

    $evaluator = & get_comment_evaluator();

    if ($comments_sendmail == 2 && $evaluator->get_result() == SPAM) {
        return;
    }

    $parentid = assert_int($parentid);
    $discussid = assert_int($discussid);
    $article = safe_row("Section, Posted, ID, url_title, AuthorID, Title", "textpattern", "ID = $parentid");
    extract($article);
    extract(safe_row("RealName, email", "txp_users", "name = '".doSlash($AuthorID)."'"));

    $out = gTxt('greeting')." $RealName,".n;
    $out .= str_replace('{title}', $Title, gTxt('comment_recorded')).n;
    $out .= permlinkurl_id($parentid).n;

    if (has_privs('discuss', $AuthorID)) {
        $out .= hu.'textpattern/index.php?event=discuss&step=discuss_edit&discussid='.$discussid.n;
    }

    $out .= gTxt('status').": ".$evaluator->get_result('text').'. '.implode(',', $evaluator->get_result_message()).n;
    $out .= n;
    $out .= gTxt('comment_name').": $cname".n;
    $out .= gTxt('comment_email').": $cemail".n;
    $out .= gTxt('comment_web').": $cweb".n;
    $out .= gTxt('comment_comment').": $message";

    $subject = strtr(gTxt('comment_received'), array('{site}' => $sitename, '{title}' => $Title));

    if (!is_valid_email($cemail)) {
        $cemail = null;
    }

    $success = txpMail($email, $subject, $out, $cemail);
}

/**
 * Renders a HTML input.
 *
 * Deprecated, use fInput() instead.
 *
 * @param      string $type
 * @param      string $name
 * @param      string $val
 * @param      int    $size
 * @param      string $class
 * @param      int    $tab
 * @param      bool   $chkd
 * @return     string
 * @deprecated in 4.0.4
 * @see        fInput()
 */

function input($type, $name, $val, $size = '', $class = '', $tab = '', $chkd = '')
{
    trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'fInput')), E_USER_NOTICE);
    $o = array(
        '<input type="'.$type.'" name="'.$name.'" id="'.$name.'" value="'.$val.'"',
        ($size)    ? ' size="'.$size.'"'      : '',
        ($class) ? ' class="'.$class.'"'    : '',
        ($tab)     ? ' tabindex="'.$tab.'"'    : '',
        ($chkd)    ? ' checked="checked"'    : '',
        ' />'.n,
    );

    return join('', $o);
}

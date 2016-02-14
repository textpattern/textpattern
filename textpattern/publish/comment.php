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
 * Collection of comment tools.
 *
 * @package Comment
 */

/**
 * Gets comments as an array from the given article.
 *
 * @param  int $id The article ID
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
        "*, UNIX_TIMESTAMP(posted) AS time",
        'txp_discuss',
        "parentid = ".intval($id)." AND visible = ".VISIBLE." ORDER BY posted ASC"
    );

    if ($rs) {
        return $rs;
    }
}

/**
 * Gets next nonce.
 *
 * @param  bool $check_only
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
 * @param  bool $check_only
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

function getComment($obfuscated = false)
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
        $rs = safe_row("nonce, secret", 'txp_discuss_nonce', "nonce IN ('".join("','", $n)."')");
        $c['nonce'] = $rs['nonce'];
        $c['secret'] = $rs['secret'];
    }

    if ($obfuscated || $c['message'] == '') {
        $c['message'] = ps(md5('message'.$c['secret']));
    }

    $c['name']    = trim(strip_tags(deEntBrackets($c['name'])));
    $c['web']     = trim(clean_url(strip_tags(deEntBrackets($c['web']))));
    $c['email']   = trim(clean_url(strip_tags(deEntBrackets($c['email']))));
    $c['message'] = trim(substr(trim(doDeEnt($c['message'])), 0, 65535));

    return $c;
}

/**
 * Saves a comment.
 */

function saveComment()
{
    global $siteurl, $comments_moderate, $comments_sendmail, $comments_disallow_images, $prefs;

    $ref = serverset('HTTP_REFERRER');
    $comment = getComment(true);
    $evaluator = & get_comment_evaluator();

    extract($comment);

    if (!checkCommentsAllowed($parentid)) {
        txp_die(gTxt('comments_closed'), '403');
    }

    $ip = serverset('REMOTE_ADDR');
    $blacklisted = is_blacklisted($ip);

    if ($blacklisted) {
        txp_die(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), '403');
    }

    if ($remember == 1 || ps('checkbox_type') == 'forget' && ps('forget') != 1) {
        setCookies($name, $email, $web);
    } else {
        destroyCookies();
    }

    $message2db = markup_comment($message);

    $isdup = safe_row(
        "message, name",
        'txp_discuss',
        "name = '".doSlash($name)."' AND message = '".doSlash($message2db)."' AND ip = '".doSlash($ip)."'"
    );

    checkCommentRequired($comment);

    if ($isdup) {
        $evaluator->add_estimate(RELOAD, 1, gTxt('comment_duplicate'));
    }

    if (($evaluator->get_result() != RELOAD) && checkNonce($nonce)) {
        callback_event('comment.save');
        $visible = $evaluator->get_result();

        if ($visible != RELOAD) {
            $parentid = assert_int($parentid);
            $commentid = safe_insert(
                'txp_discuss',
                "parentid = $parentid,
                 name     = '".doSlash($name)."',
                 email    = '".doSlash($email)."',
                 web      = '".doSlash($web)."',
                 ip       = '".doSlash($ip)."',
                 message  = '".doSlash($message2db)."',
                 visible  = ".intval($visible).",
                 posted   = NOW()"
            );

            if ($commentid) {
                safe_update('txp_discuss_nonce', "used = 1", "nonce = '".doSlash($nonce)."'");

                if ($prefs['comment_means_site_updated']) {
                    update_lastmod('comment_saved', compact('commentid', 'parentid', 'name', 'email', 'web', 'message', 'visible', 'ip'));
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
 * Checks if all required comment fields are filled out.
 *
 * To be used only by TXP itself
 *
 * @param array comment fields (from getComment())
 */

function checkCommentRequired($comment)
{
    global $prefs;

    $evaluator = & get_comment_evaluator();

    if ($prefs['comments_require_name'] && !$comment['name']) {
        $evaluator->add_estimate(RELOAD, 1, gTxt('comment_name_required'));
    }
    if ($prefs['comments_require_email'] && !$comment['email']) {
        $evaluator->add_estimate(RELOAD, 1, gTxt('comment_email_required'));
    }
    if (!$comment['message']) {
        $evaluator->add_estimate(RELOAD, 1, gTxt('comment_required'));
    }
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
     * @param  string $result_type If 'numeric' returns the ID of the status, a localised label otherwise
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
            $result[$key] = array_sum($value) / max(1, count($value));
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
 * @return bool TRUE if the nonce is valid
 * @see    getNextNonce()
 */

function checkNonce($nonce)
{
    if (!$nonce || !preg_match('#^[a-zA-Z0-9]*$#', $nonce)) {
        return false;
    }

    // Delete expired nonces.
    safe_delete('txp_discuss_nonce', "issue_time < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");

    // Check for nonce.
    return (safe_row("*", 'txp_discuss_nonce', "nonce = '".doSlash($nonce)."' AND used = 0")) ? true : false;
}

/**
 * Checks if comments are open for the given article.
 *
 * @param  int $id The article.
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
        $uPosted = $thisarticle['posted'];
    } else {
        extract(
            safe_row(
                "Annotate, UNIX_TIMESTAMP(Posted) AS uPosted",
                'textpattern',
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
    return '<a id="txpCommentHelpLink" href="'.HELP_URL.'?item=textile_comments&amp;language='.txpspecialchars(LANG).'" onclick="window.open(this.href, \'popupwindow\', \'width=300,height=400,scrollbars,resizable\'); return false;">'.gTxt('textile_help').'</a>';
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
    $article = safe_row("Section, Posted, ID, url_title, AuthorID, Title", 'textpattern', "ID = $parentid");
    extract($article);
    extract(safe_row("RealName, email", 'txp_users', "name = '".doSlash($AuthorID)."'"));

    $out = gTxt('salutation', array('{name}' => $RealName)).n;
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
        ($size)  ? ' size="'.$size.'"'    : '',
        ($class) ? ' class="'.$class.'"'  : '',
        ($tab)   ? ' tabindex="'.$tab.'"' : '',
        ($chkd)  ? ' checked="checked"'   : '',
        ' />'.n,
    );

    return join('', $o);
}

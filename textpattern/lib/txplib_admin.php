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
 * Collection of password handling functions.
 *
 * @package User
 */

/**
 * Emails a new user with account details and requests they set a password.
 *
 * @param  string $name     The login name
 * @return bool FALSE on error.
 */

function send_account_activation($name)
{
    global $sitename;

    require_privs('admin.edit');

    $rs = safe_row("user_id, email, nonce, RealName, pass", 'txp_users', "name = '".doSlash($name)."'");

    if ($rs) {
        extract($rs);

        $expiryTimestamp = time() + (60 * 60 * ACTIVATION_EXPIRY_HOURS);

        $activation_code = generate_user_token($user_id, 'account_activation', $expiryTimestamp, $pass, $nonce);

        $expiryYear = safe_strftime('%Y', $expiryTimestamp);
        $expiryMonth = safe_strftime('%B', $expiryTimestamp);
        $expiryDay = safe_strftime('%Oe', $expiryTimestamp);
        $expiryTime = safe_strftime('%H:%M %Z', $expiryTimestamp);

        $authorLang = safe_field('val', 'txp_prefs', "name='language_ui' AND user_name = '".doSlash($name)."'");
        $authorLang = ($authorLang) ? $authorLang : TEXTPATTERN_DEFAULT_LANG;

        $txpLang = Txp::get('\Textpattern\L10n\Lang');
        $txpLang->swapStrings($authorLang, 'admin, common');

        $message = gTxt('salutation', array('{name}' => $RealName)).
            n.n.gTxt('you_have_been_registered').' '.$sitename.

            n.n.gTxt('your_login_is').' '.$name.
            n.n.gTxt('account_activation_confirmation').
            n.ahu.'index.php?lang='.$authorLang.'&activate='.$activation_code.
            n.n.gTxt('link_expires', array(
                '{year}'  => $expiryYear,
                '{month}' => $expiryMonth,
                '{day}'   => $expiryDay,
                '{time}'  => $expiryTime,
            ));

        $subject = gTxt('account_activation');

        $txpLang->swapStrings(null);

        if (txpMail($email, "[$sitename] ".$subject, $message)) {
            return gTxt('login_sent_to', array('{email}' => $email));
        } else {
            return array(gTxt('could_not_mail'), E_ERROR);
        }
    }
}

/**
 * Sends a password reset link to a user's email address.
 *
 * This function will return a success message even when the specified user
 * doesn't exist. Though an error message could be thrown when a user isn't
 * found, security best practice prevents leaking existing account names.
 *
 * @param  string $name The login name
 * @return string A localized message string
 * @see    send_new_password()
 * @see    reset_author_pass()
 * @example
 * echo send_reset_confirmation_request('username');
 */

function send_reset_confirmation_request($name)
{
    global $sitename;

    $expiryTimestamp = time() + (60 * RESET_EXPIRY_MINUTES);
    $safeName = doSlash($name);

    $rs = safe_query(
        "SELECT
            txp_users.user_id, txp_users.email,
            txp_users.nonce, txp_users.pass,
            txp_token.expires
        FROM ".safe_pfx('txp_users')." txp_users
        LEFT JOIN ".safe_pfx('txp_token')." txp_token
        ON txp_users.user_id = txp_token.reference_id
        AND txp_token.type = 'password_reset'
        WHERE txp_users.name = '$safeName'"
    );

    $row = nextRow($rs);

    if ($row) {
        extract($row);

        // Rate limit the reset requests.
        if ($expires) {
            $originalExpiry = strtotime($expires);

            if (($expiryTimestamp - $originalExpiry) < (60 * RESET_RATE_LIMIT_MINUTES)) {
                return gTxt('password_reset_confirmation_request_sent');
            }
        }

        $confirm = generate_user_token($user_id, 'password_reset', $expiryTimestamp, $pass, $nonce);

        $expiryYear = safe_strftime('%Y', $expiryTimestamp);
        $expiryMonth = safe_strftime('%B', $expiryTimestamp);
        $expiryDay = safe_strftime('%Oe', $expiryTimestamp);
        $expiryTime = safe_strftime('%H:%M %Z', $expiryTimestamp);

        $authorLang = safe_field('val', 'txp_prefs', "name='language_ui' AND user_name = '$safeName'");
        $authorLang = ($authorLang) ? $authorLang : TEXTPATTERN_DEFAULT_LANG;

        $txpLang = Txp::get('\Textpattern\L10n\Lang');
        $txpLang->swapStrings($authorLang, 'admin, common');

        $message = gTxt('salutation', array('{name}' => $name)).
            n.n.gTxt('password_reset_confirmation').
            n.ahu.'index.php?lang='.$authorLang.'&confirm='.$confirm.
            n.n.gTxt('link_expires', array(
                '{year}'  => $expiryYear,
                '{month}' => $expiryMonth,
                '{day}'   => $expiryDay,
                '{time}'  => $expiryTime,
            ));

        $subject = gTxt('password_reset_confirmation_request');
        $txpLang->swapStrings(null);

        if (txpMail($email, "[$sitename] ".$subject, $message)) {
            return gTxt('password_reset_confirmation_request_sent');
        } else {
            return array(gTxt('could_not_mail'), E_ERROR);
        }
    } else {
        // Send generic 'request_sent' message so that (non-)existence of
        // account names are not leaked. Since this is a short circuit, there's
        // a possibility of a timing attack revealing the existence of an
        // account, which we could defend against to some degree.
        return gTxt('password_reset_confirmation_request_sent');
    }
}

/**
 * Emails a new user with login details.
 *
 * This function can be only executed when the currently authenticated user
 * trying to send the email was granted 'admin.edit' privileges.
 *
 * Should NEVER be used as sending plaintext passwords is wrong.
 * Will be removed in future, in lieu of sending reset request tokens.
 *
 * @param      string $RealName The real name
 * @param      string $name     The login name
 * @param      string $email    The email address
 * @param      string $password The password
 * @return     bool FALSE on error.
 * @deprecated in 4.6.0
 * @see        send_new_password(), send_reset_confirmation_request
 * @example
 * if (send_password('John Doe', 'login', 'example@example.tld', 'password'))
 * {
 *     echo "Login details sent.";
 * }
 */

function send_password($RealName, $name, $email, $password)
{
    global $sitename;

    require_privs('admin.edit');

    $message = gTxt('salutation', array('{name}' => $RealName)).

        n.n.gTxt('you_have_been_registered').' '.$sitename.

        n.n.gTxt('your_login_is').' '.$name.
        n.gTxt('your_password_is').' '.$password.

        n.n.gTxt('log_in_at').' '.ahu.'index.php';

    return txpMail($email, "[$sitename] ".gTxt('your_login_info'), $message);
}

/**
 * Sends a new password to an existing user.
 *
 * If the $name is FALSE, the password is sent to the currently
 * authenticated user.
 *
 * Should NEVER be used as sending plaintext passwords is wrong.
 * Will be removed in future, in lieu of sending reset request tokens.
 *
 * @param      string $password The new password
 * @param      string $email    The email address
 * @param      string $name     The login name
 * @return     bool FALSE on error.
 * @deprecated in 4.6.0
 * @see        send_reset_confirmation_request
 * @see        reset_author_pass()
 * @example
 * $pass = generate_password();
 * if (send_new_password($pass, 'example@example.tld', 'user'))
 * {
 *     echo "Password was sent to 'user'.";
 * }
 */

function send_new_password($password, $email, $name)
{
    global $txp_user, $sitename;

    if (empty($name)) {
        $name = $txp_user;
    }

    $message = gTxt('salutation', array('{name}' => $name)).

        n.n.gTxt('your_password_is').' '.$password.

        n.n.gTxt('log_in_at').' '.ahu.'index.php';

    return txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message);
}

/**
 * Generates a password.
 *
 * Generates a random password of given length using the symbols set in
 * PASSWORD_SYMBOLS constant.
 *
 * Should NEVER be used as it is not cryptographically secure.
 * Will be removed in future, in lieu of sending reset request tokens.
 *
 * @param      int $length The length of the password
 * @return     string Random plain-text password
 * @deprecated in 4.6.0
 * @see        \Textpattern\Password\Generate
 * @see        \Textpattern\Password\Random
 * @example
 * echo generate_password(128);
 */

function generate_password($length = 10)
{
    static $chars;

    if (!$chars) {
        $chars = str_split(PASSWORD_SYMBOLS);
    }

    $pool = false;
    $pass = '';

    for ($i = 0; $i < $length; $i++) {
        if (!$pool) {
            $pool = $chars;
        }

        $index = mt_rand(0, count($pool) - 1);
        $pass .= $pool[$index];
        unset($pool[$index]);
        $pool = array_values($pool);
    }

    return $pass;
}

/**
 * Resets the given user's password and emails it.
 *
 * The old password is replaced with a new random-generated one.
 *
 * Should NEVER be used as sending plaintext passwords is wrong.
 * Will be removed in future, in lieu of sending reset request tokens.
 *
 * @param  string $name The login name
 * @return string A localized message string
 * @deprecated in 4.6.0
 * @see    PASSWORD_LENGTH
 * @see    generate_password()
 * @example
 * echo reset_author_pass('username');
 */

function reset_author_pass($name)
{
    $email = safe_field("email", 'txp_users', "name = '".doSlash($name)."'");

    $new_pass = Txp::get('\Textpattern\Password\Random')->generate(PASSWORD_LENGTH);

    $rs = change_user_password($name, $new_pass);

    if ($rs) {
        if (send_new_password($new_pass, $email, $name)) {
            return gTxt('password_sent_to').' '.$email;
        } else {
            return gTxt('could_not_mail').' '.$email;
        }
    } else {
        return gTxt('could_not_update_author').' '.txpspecialchars($name);
    }
}

/**
 * Loads client-side localisation scripts.
 *
 * Passes localisation strings from the database to JavaScript.
 *
 * Only works on the admin-side pages.
 *
 * @param   string|array $var   Scalar or array of string keys
 * @param   array        $atts  Array or array of arrays of variable substitution pairs
 * @param   array        $route Optional events/steps upon which to add the strings
 * @since   4.5.0
 * @package L10n
 * @example
 * gTxtScript(array('string1', 'string2', 'string3'));
 */

function gTxtScript($var, $atts = array(), $route = array())
{
    global $textarray_script, $event, $step;

    $targetEvent = empty($route[0]) ? null : (array)$route[0];
    $targetStep = empty($route[1]) ? null : (array)$route[1];

    if (($targetEvent === null || in_array($event, $targetEvent)) && ($targetStep === null || in_array($step, $targetStep))) {
        if (!is_array($textarray_script)) {
            $textarray_script = array();
        }

        $data = is_array($var) ? array_map('gTxt', $var, $atts) : (array) gTxt($var, $atts);
        $textarray_script = $textarray_script + array_combine((array) $var, $data);
    }
}

/**
 * Handle refreshing the passed AJAX content to the UI.
 *
 * @param  array $partials Partials array
 * @param  array $rs       Record set of the edited content
 */

function updatePartials($partials, $rs, $types)
{
    if (!is_array($types)) {
        $types = array($types);
    }

    foreach ($partials as $k => $p) {
        if (in_array($p['mode'], $types)) {
            $cb = $p['cb'];
            $partials[$k]['html'] = (is_array($cb) ? call_user_func($cb, $rs, $k) : $cb($rs, $k));
        }
    }

    return $partials;
}

/**
 * Handle refreshing the passed AJAX content to the UI.
 *
 * @param  array $partials Partials array
 * @return array           Response to send back to the browser
 */

function updateVolatilePartials($partials)
{
    $response = array();

    // Update the volatile partials.
    foreach ($partials as $k => $p) {
        // Volatile partials need a target DOM selector.
        if (empty($p['selector']) && $p['mode'] != PARTIAL_STATIC) {
            trigger_error(gTxt('empty_partial_selector', array('{name}' => $k)), E_USER_ERROR);
        } else {
            // Build response script.
            list($selector, $fragment) = (array)$p['selector'] + array(null, null);

            if ($p['mode'] == PARTIAL_VOLATILE) {
                // Volatile partials replace *all* of the existing HTML
                // fragment for their selector with the new one.
                $selector = do_list($selector);
                $fragment = isset($fragment) ? do_list($fragment) + $selector : $selector;
                $response[] = 'var $html = $("<div>'.escape_js($p['html']).'</div>")';

                foreach ($selector as $i => $sel) {
                    $response[] = '$("'.$sel.'").replaceWith($html.find("'.$fragment[$i].'"))';
                }
            } elseif ($p['mode'] == PARTIAL_VOLATILE_VALUE) {
                // Volatile partial values replace the *value* of elements
                // matching their selector.
                $response[] = '$("'.$selector.'").val("'.escape_js($p['html']).'")';
            }
        }
    }

    return $response;
}

/**
 * Checks if GD supports the given image type.
 *
 * @param   string $image_type Either '.gif', '.jpg', '.png'
 * @return  bool TRUE if the type is supported
 * @package Image
 */

function check_gd($image_type)
{
    if (!function_exists('gd_info')) {
        return false;
    }

    $gd_info = gd_info();

    switch ($image_type) {
        case '.gif':
            return ($gd_info['GIF Create Support'] == true);
            break;
        case '.jpg':
        case '.jpeg':
            return ($gd_info['JPEG Support'] == true);
            break;
        case '.png':
            return ($gd_info['PNG Support'] == true);
            break;
        case '.webp':
            return (!empty($gd_info['WebP Support']));
            break;
        case '.avif':
            return (!empty($gd_info['AVIF Support']));
            break;
    }

    return false;
}

/**
 * Returns the given image file data.
 *
 * @param   array      $file     HTTP file upload variables
 * @return  array|bool An array of image data on success, false on error
 * @package Image
 */

function txpimagesize($file, $create = false)
{
    if ($data = getimagesize($file)) {
        list($w, $h, $ext) = $data;
        $exts = get_safe_image_types();
        $ext = !empty($exts[$ext]) ? $exts[$ext] : false;
    }

    if (empty($ext)) {
        return false;
    }

    $imgf = 'imagecreatefrom'.($ext == '.jpg' ? 'jpeg' : ltrim($ext, '.'));
    $data['ext'] = $ext;

    if (($create || empty($w) || empty($h)) && function_exists($imgf)) {
        // Make sure we have enough memory if the image is large.
        if (filesize($file) > 256*1024) {
            $shorthand = array('K', 'M', 'G');
            $tens = array('000', '000000', '000000000'); // A good enough decimal approximation of K, M, and G.

            // Do not *decrease* memory_limit.
            list($ml, $extra) = str_ireplace($shorthand, $tens, array(ini_get('memory_limit'), EXTRA_MEMORY));

            if ($ml < $extra) {
                ini_set('memory_limit', EXTRA_MEMORY);
            }
        }

        $errlevel = error_reporting(0);

        if ($data['image'] = $imgf($file)) {
            $data[0] or $data[0] = imagesx($data['image']);
            $data[1] or $data[1] = imagesy($data['image']);
            $data[3] = 'width="'.$data[0].'" height="'.$data[1].'"';
        }

        error_reporting($errlevel);
    }

    return $data;
}

/**
 * Uploads an image.
 *
 * Can be used to upload a new image or replace an existing one.
 * If $id is specified, the image will be replaced. If $uploaded is set FALSE,
 * $file can take a local file instead of HTTP file upload variable.
 *
 * All uploaded files will included on the Images panel.
 *
 * @param   array        $file     HTTP file upload variables
 * @param   array        $meta     Image meta data, allowed keys 'caption', 'alt', 'category'
 * @param   int          $id       Existing image's ID
 * @param   bool         $uploaded If FALSE, $file takes a filename instead of upload vars
 * @return  array|string An array of array(message, id) on success, localized error string on error
 * @package Image
 * @example
 * print_r(image_data(
 *     $_FILES['myfile'],
 *     array(
 *         'caption' => '',
 *         'alt' => '',
 *         'category' => '',
 *     )
 * ));
 */

function image_data($file, $meta = array(), $id = 0, $uploaded = true)
{
    global $txp_user, $event;

    $name = $file['name'];
    $error = $file['error'];
    $file = $file['tmp_name'];

    if ($uploaded) {
        if ($error !== UPLOAD_ERR_OK) {
            return upload_get_errormsg($error);
        }

        $file = get_uploaded_file($file);
    }

    if (empty($file)) {
        return upload_get_errormsg(UPLOAD_ERR_NO_FILE);
    }

    if (get_pref('file_max_upload_size') < filesize($file)) {
        unlink($file);

        return upload_get_errormsg(UPLOAD_ERR_FORM_SIZE);
    }

    if (!($data = txpimagesize($file))) {
        return gTxt('only_graphic_files_allowed', array('{formats}' => join(', ', get_safe_image_types())));
    }

    list($w, $h) = $data;
    $ext = $data['ext'];

    $name = substr($name, 0, strrpos($name, '.')).$ext;
    $safename = doSlash($name);
    $meta = lAtts(array(
        'category' => '',
        'caption'  => '',
        'alt'      => '',
    ), (array) $meta, false);

    extract(doSlash($meta));

    $q = "
        name = '$safename',
        ext = '$ext',
        w = $w,
        h = $h,
        alt = '$alt',
        caption = '$caption',
        category = '$category',
        date = NOW(),
        author = '".doSlash($txp_user)."'
    ";

    if (empty($id)) {
        $rs = safe_insert('txp_image', $q);

        if ($rs) {
            $id = $GLOBALS['ID'] = $rs;
        } else {
            return gTxt('image_save_error');
        }
    } else {
        $id = assert_int($id);
    }

    $newpath = IMPATH.$id.$ext;

    if (shift_uploaded_file($file, $newpath) == false) {
        if (!empty($rs)) {
            safe_delete('txp_image', "id = '$id'");
            unset($GLOBALS['ID']);
        }

        return gTxt('directory_permissions', array('{path}' => $newpath));
    } elseif (empty($rs)) {
        $rs = safe_update('txp_image', $q, "id = $id");

        if (!$rs) {
            return gTxt('image_save_error');
        }
    }

    @chmod($newpath, 0644);

    // GD is supported
    if (check_gd($ext)) {
        // Auto-generate a thumbnail using the last settings
        if (get_pref('thumb_w') > 0 || get_pref('thumb_h') > 0) {
            $t = new txp_thumb($id);
            $t->crop = (bool) get_pref('thumb_crop');
            $t->hint = '0';
            $t->width = (int) get_pref('thumb_w');
            $t->height = (int) get_pref('thumb_h');
            $t->write();
        }
    }

    $message = gTxt('image_uploaded', array('{name}' => $name));
    update_lastmod('image_uploaded', compact('id', 'name', 'ext', 'w', 'h', 'alt', 'caption', 'category', 'txp_user'));

    // call post-upload plugins with new image's $id
    callback_event('image_uploaded', $event, false, $id);

    return array($message, $id);
}

/**
 * Error handler for admin-side pages.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

function adminErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $production_status, $theme, $event, $step;

    $error = array();

    if ($production_status == 'testing') {
        $error = array(
            E_WARNING           => 'Warning',
            E_RECOVERABLE_ERROR => 'Catchable fatal error',
            E_USER_ERROR        => 'User_Error',
            E_USER_WARNING      => 'User_Warning',
        );
    } elseif ($production_status == 'debug') {
        $error = array(
            E_WARNING           => 'Warning',
            E_NOTICE            => 'Notice',
            E_RECOVERABLE_ERROR => 'Catchable fatal error',
            E_USER_ERROR        => 'User_Error',
            E_USER_WARNING      => 'User_Warning',
            E_USER_NOTICE       => 'User_Notice',
        );

        if (!isset($error[$errno])) {
            $error[$errno] = $errno;
        }
    }

    if (!isset($error[$errno]) || !error_reporting()) {
        return;
    }

    // When even a minimum environment is missing.
    if (!isset($production_status)) {
        echo '<pre dir="auto">'.gTxt('internal_error').' "'.$errstr.'"'.n."in $errfile at line $errline".'</pre>';

        return;
    }

    $backtrace = '';

    if (has_privs('debug.verbose')) {
        $msg = $error[$errno].' "'.$errstr.'"';
    } else {
        $msg = gTxt('internal_error');
    }

    if ($production_status == 'debug' && has_privs('debug.backtrace')) {
        $msg .= n."in $errfile at line $errline";
        $backtrace = join(n, get_caller(10, 1));
    }

    if ($errno == E_ERROR || $errno == E_USER_ERROR) {
        $httpstatus = 500;
    } else {
        $httpstatus = 200;
    }

    $out = "$msg.\n$backtrace";

    if (http_accept_format('html')) {
        if ($backtrace) {
            echo "<pre dir=\"auto\">$msg.</pre>".
                n.'<pre class="backtrace" dir="ltr"><code>'.
                txpspecialchars($backtrace).'</code></pre>';
        } elseif (is_object($theme)) {
            echo $theme->announce(array($out, E_ERROR), true);
        } else {
            echo "<pre dir=\"auto\">$out</pre>";
        }
    } elseif (http_accept_format('js')) {
        if (is_object($theme)) {
            send_script_response($theme->announce_async(array($out, E_ERROR), true));
        } else {
            send_script_response('/* '.$out.'*/');
        }
    } elseif (http_accept_format('xml')) {
        send_xml_response(array(
            'http-status'    => $httpstatus,
            'internal_error' => "$out",
        ));
    } else {
        txp_die($msg, 500);
    }
}

/**
 * Error handler for update scripts.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

function updateErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $production_status;

    $old = $production_status;
    $production_status = 'debug';

    adminErrorHandler($errno, $errstr, $errfile, $errline);

    $production_status = $old;

    throw new Exception('update failed');
}

/**
 * Registers an admin-side extension page.
 *
 * For now this just does the same as register_callback().
 *
 * @param   callback $func  The callback function
 * @param   string   $event The callback event
 * @param   string   $step  The callback step
 * @param   bool     $top   The top or the bottom of the page
 * @access  private
 * @see     register_callback()
 * @package Callback
 */

function register_page_extension($func, $event, $step = '', $top = 0)
{
    register_callback($func, $event, $step, $top);
}

/**
 * Registers a new admin-side panel and adds a navigation link to the menu.
 *
 * @param   string $area  The menu the panel appears in, e.g. "home", "content", "presentation", "admin", "extensions"
 * @param   string $panel The panel's event
 * @param   string $title The menu item's label
 * @package Callback
 * @example
 * add_privs('abc_admin_event', '1,2');
 * register_tab('extensions', 'abc_admin_event', 'My Panel');
 * register_callback('abc_admin_function', 'abc_admin_event');
 */

function register_tab($area, $panel, $title)
{
    global $plugin_areas, $event;

    if ($event !== 'plugin') {
        $plugin_areas[$area][$title] = $panel;
    }
}

/**
 * Call an event's pluggable UI function.
 *
 * @param   string $event   The event
 * @param   string $element The element selector
 * @param   string $default The default interface markup
 * @return  mixed  Returned value from a callback handler, or $default if no custom UI was provided
 * @package Callback
 */

function pluggable_ui($event, $element, $default = '')
{
    $argv = func_get_args();
    $argv = array_merge(array(
        $event,
        $element,
       (string) $default === '' ? 0 : array(0, 0)
    ), array_slice($argv, 2));
    // Custom user interface, anyone?
    // Signature for called functions:
    // string my_called_func(string $event, string $step, string $default_markup[, mixed $context_data...])
    $ui = call_user_func_array('callback_event', $argv);

    // Either plugins provided a user interface, or we render our own.
    return ($ui === '') ? $default : $ui;
}

/**
 * Gets a list of form types.
 *
 * The list of form types can be extended with a 'form.types > types'
 * callback event. Callback functions get passed three arguments: '$event',
 * '$step' and '$types'. The third parameter contains a reference to an
 * array of 'type => label' pairs.
 *
 * @return     array An array of form types
 * @since      4.6.0
 * @deprecated 4.8.6
 * @see        Textpattern\Skin\Form->getTypes()
 * @todo       Move callback to Textpattern\Skin\Form->getTypes()?
 * @package    Template
 */

function get_form_types()
{
    static $types = null;

    if ($types === null) {
        foreach (Txp::get('Textpattern\Skin\Form')->getTypes() as $type) {
            $types[$type] = gTxt($type);
        }

        callback_event_ref('form.types', 'types', 0, $types);
    }

    return $types;
}

/**
 * Gets a list of essential form templates.
 *
 * These forms can not be deleted or renamed. The array keys hold
 * the form names, the array values their group.
 *
 * The list forms can be extended with a 'form.essential > forms'
 * callback event. Callback functions get passed three arguments: '$event',
 * '$step' and '$essential'. The third parameter contains a reference to an
 * array of forms.
 *
 * @return  array An array of form names
 * @since   4.6.0
 * @package Template
 */

function get_essential_forms()
{
    static $essential = null;

    if ($essential === null) {
        $essential = array(
            'comments'         => 'comment',
            'comments_display' => 'comment',
            'comment_form'     => 'comment',
            'default'          => 'article',
            'plainlinks'       => 'link',
            'files'            => 'file',
        );

        callback_event_ref('form.essential', 'forms', 0, $essential);
    }

    return $essential;
}

/**
 * Renders a HTML &lt;select&gt; list of supported permanent link URL formats.
 *
 * @param  string $name HTML name and id of the list
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */

function permlinkmodes($name, $val, $blank = false)
{
    $vals = array(
        'messy'                     => gTxt('messy'),
        'id_title'                  => gTxt('id_title'),
        'section_id_title'          => gTxt('section_id_title'),
        'section_category_title'    => gTxt('section_category_title'),
        'year_month_day_title'      => gTxt('year_month_day_title'),
        'breadcrumb_title'          => gTxt('breadcrumb_title'),
        'section_title'             => gTxt('section_title'),
        'title_only'                => gTxt('title_only')
    );

    return selectInput($name, $vals, $val, $blank, '', $name);
}

/**
 * Gets the name of the default publishing section.
 *
 * @return string The section
 */

function getDefaultSection()
{
    global $txp_sections;

    $name = get_pref('default_section');

    if (!isset($txp_sections[$name])) {
        foreach ($txp_sections as $name => $section) {
            if ($name != 'default') {
                break;
            }
        }

        set_pref('default_section', $name, 'section', PREF_HIDDEN);
    }

    return $name;
}

/**
 * Updates a list's per page number.
 *
 * Gets the per page number from a "qty" HTTP POST/GET parameter and
 * creates a user-specific preference value "$name_list_pageby".
 *
 * @param string|null $name The name of the list
 * @deprecated in 4.7.0
 */

function event_change_pageby($name = null)
{
    global $event;

    Txp::get('\Textpattern\Admin\Paginator', $event, $name)->change();
}

/**
 * Generic multi-edit form's edit handler shared across panels.
 *
 * Receives an action from a multi-edit form and runs it in the given
 * database table.
 *
 * @param  string $table  The database table
 * @param  string $id_key The database column selected items match to. Column should be integer type
 * @return string Comma-separated list of affected items
 * @see    multi_edit()
 */

function event_multi_edit($table, $id_key)
{
    $method = ps('edit_method');
    $selected = ps('selected');

    if ($selected) {
        if ($method == 'delete') {
            foreach ($selected as $id) {
                $id = assert_int($id);

                if (safe_delete($table, "$id_key = '$id'")) {
                    $ids[] = $id;
                }
            }

            return join(', ', $ids);
        }
    }

    return '';
}

/**
 * Verifies temporary directory existence and that it's writeable.
 *
 * @return  bool|null NULL on error, TRUE on success
 * @package Debug
 */

function find_temp_dir()
{
    global $path_to_site, $img_dir;

    if (IS_WIN) {
        $guess = array(
            txpath.DS.'tmp',
            getenv('TMP'),
            getenv('TEMP'),
            getenv('SystemRoot').DS.'Temp',
            'C:'.DS.'Temp',
            $path_to_site.DS.$img_dir,
        );

        foreach ($guess as $k => $v) {
            if (empty($v)) {
                unset($guess[$k]);
            }
        }
    } else {
        $guess = array(
            txpath.DS.'tmp',
            sys_get_temp_dir(),
            DS.'tmp',
            $path_to_site.DS.$img_dir,
        );
    }

    foreach ($guess as $dir) {
        if (is_writable($dir)) {
            $tf = tempnam($dir, 'txp_');

            if ($tf) {
                $tf = realpath($tf);
            }

            if ($tf and file_exists($tf)) {
                unlink($tf);

                return dirname($tf);
            }
        }
    }

    return false;
}

/**
 * Moves an uploaded file and returns its new location.
 *
 * @param   string $f    The filename of the uploaded file
 * @param   string $dest The destination of the moved file. If omitted, the file is moved to the temp directory
 * @return  string|bool The new path or FALSE on error
 * @package File
 */

function get_uploaded_file($f, $dest = '')
{
    global $tempdir;

    if (!is_uploaded_file($f)) {
        return false;
    }

    if ($dest) {
        $newfile = $dest;
    } else {
        $newfile = tempnam($tempdir, 'txp_');
        if (!$newfile) {
            return false;
        }
    }

    // $newfile is created by tempnam(), but move_uploaded_file will overwrite it.
    if (move_uploaded_file($f, $newfile)) {
        return $newfile;
    }
}

/**
 * Gets an array of files in the Files directory that weren't uploaded
 * from Textpattern.
 *
 * Used for importing existing files on the server to Textpattern's files panel.
 *
 * @param   string $path    The directory to scan
 * @param   int    $options glob() options
 * @return  array An array of file paths
 * @package File
 */

function get_filenames($path = null, $options = GLOB_NOSORT)
{
    global $file_base_path;

    $files = array();
    $file_path = isset($path) ? $path : $file_base_path;
    $is_file = ($options & GLOB_ONLYDIR) ? 'is_dir' : 'is_file';

    if (!is_dir($file_path) || !is_readable($file_path)) {
        return array();
    }

    $cwd = getcwd();

    if (chdir($file_path)) {
        $directory = glob('*', $options);

        if ($directory) {
            foreach ($directory as $filename) {
                if ($is_file($filename) && is_readable($filename)) {
                    $files[$filename] = $filename;
                }
            }

            unset($directory);
        }

        if ($cwd) {
            chdir($cwd);
        }
    }

    if (!$files || isset($path)) {
        return $files;
    }

    $rs = safe_rows_start("filename", 'txp_file', "1 = 1");

    if ($rs && numRows($rs)) {
        while ($a = nextRow($rs)) {
            unset($files[$a['filename']]);
        }
    }

    return $files;
}

/**
 * Moves a file.
 *
 * @param   string $f    The file to move
 * @param   string $dest The destination
 * @return  bool TRUE on success, or FALSE on error
 * @package File
 */

function shift_uploaded_file($f, $dest)
{
    if (@rename($f, $dest)) {
        return true;
    }

    if (@copy($f, $dest)) {
        unlink($f);

        return true;
    }

    return false;
}

/**
 * Assigns assets to a different user.
 *
 * Changes the owner of user's assets. It will move articles, files, images
 * and links from one user to another.
 *
 * Should be run when a user's permissions are taken away, a username is
 * renamed or the user is removed from the site.
 *
 * Affected database tables can be extended with a 'user.assign_assets > columns'
 * callback event. Callback functions get passed three arguments: '$event',
 * '$step' and '$columns'. The third parameter contains a reference to an
 * array of 'table => column' pairs.
 *
 * On a successful run, will trigger a 'user.assign_assets > done' callback event.
 *
 * @param   string|array $owner     List of current owners
 * @param   string       $new_owner The new owner
 * @return  bool FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (assign_user_assets(array('user1', 'user2'), 'new_owner'))
 * {
 *     echo "Assigned assets by 'user1' and 'user2' to 'new_owner'.";
 * }
 */

function assign_user_assets($owner, $new_owner)
{
    static $columns = null;

    if (!$owner || !user_exists($new_owner)) {
        return false;
    }

    if ($columns === null) {
        $columns = array(
            'textpattern' => 'AuthorID',
            'txp_file'    => 'author',
            'txp_image'   => 'author',
            'txp_link'    => 'author',
        );

        callback_event_ref('user.assign_assets', 'columns', 0, $columns);
    }

    $names = join(',', quote_list((array) $owner));
    $assign = doSlash($new_owner);

    foreach ($columns as $table => $column) {
        if (safe_update($table, "$column = '$assign'", "$column IN ($names)") === false) {
            return false;
        }
    }

    callback_event('user.assign_assets', 'done', 0, compact('owner', 'new_owner', 'columns'));

    return true;
}

/**
 * Validates a string as a username.
 *
 * @param   string $name The username
 * @return  bool TRUE if the string valid
 * @since   4.6.0
 * @package User
 * @example
 * if (is_valid_username('john'))
 * {
 *     echo "'john' is a valid username.";
 * }
 */

function is_valid_username($name)
{
    if (function_exists('mb_strlen')) {
        $length = mb_strlen($name, '8bit');
    } else {
        $length = strlen($name);
    }

    return $name && !preg_match('/^\s|[,\'"<>]|\s$/u', $name) && $length <= 64;
}

/**
 * Creates a user account.
 *
 * On a successful run, will trigger a 'user.create > done' callback event.
 *
 * @param   string $name     The login name
 * @param   string $email    The email address
 * @param   string $password The password
 * @param   string $realname The real name
 * @param   int    $group    The user group
 * @return  bool FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (create_user('john', 'john.doe@example.com', 'DancingWalrus', 'John Doe', 1))
 * {
 *     echo "User 'john' created.";
 * }
 */

function create_user($name, $email, $password, $realname = '', $group = 0)
{
    $levels = get_groups();

    if (!$password || !is_valid_username($name) || !is_valid_email($email) || user_exists($name) || !isset($levels[$group])) {
        return false;
    }

    $nonce = md5(uniqid(mt_rand(), true));
    $hash = Txp::get('\Textpattern\Password\Hash')->hash($password);

    if (
        safe_insert(
            'txp_users',
            "name = '".doSlash($name)."',
            email = '".doSlash($email)."',
            pass = '".doSlash($hash)."',
            nonce = '".doSlash($nonce)."',
            privs = ".intval($group).",
            RealName = '".doSlash($realname)."'"
        ) === false
    ) {
        return false;
    }

    callback_event('user.create', 'done', 0, compact('name', 'email', 'password', 'realname', 'group', 'nonce', 'hash'));

    return true;
}

/**
 * Updates a user.
 *
 * Updates a user account's properties. The $user argument is used for
 * selecting the updated user, and rest of the arguments new values.
 * Use NULL to omit an argument.
 *
 * On a successful run, will trigger a 'user.update > done' callback event.
 *
 * @param   string      $user     The updated user
 * @param   string|null $email    The email address
 * @param   string|null $realname The real name
 * @param   array|null  $meta     Additional meta fields
 * @return  bool FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (update_user('login', null, 'John Doe'))
 * {
 *     echo "Updated user's real name.";
 * }
 */

function update_user($user, $email = null, $realname = null, $meta = array())
{
    if (($email !== null && !is_valid_email($email)) || !user_exists($user)) {
        return false;
    }

    $meta = (array) $meta;
    $meta['RealName'] = $realname;
    $meta['email'] = $email;
    $set = array();

    foreach ($meta as $name => $value) {
        if ($value !== null) {
            $set[] = $name." = '".doSlash($value)."'";
        }
    }

    if (
        safe_update(
            'txp_users',
            join(',', $set),
            "name = '".doSlash($user)."'"
        ) === false
    ) {
        return false;
    }

    callback_event('user.update', 'done', 0, compact('user', 'email', 'realname', 'meta'));

    return true;
}

/**
 * Changes a user's password.
 *
 * On a successful run, will trigger a 'user.password_change > done' callback event.
 *
 * @param   string $user     The updated user
 * @param   string $password The new password
 * @return  bool FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (change_user_password('login', 'WalrusWasDancing'))
 * {
 *     echo "Password changed.";
 * }
 */

function change_user_password($user, $password)
{
    if (!$user || !$password) {
        return false;
    }

    $hash = Txp::get('\Textpattern\Password\Hash')->hash($password);

    if (
        safe_update(
            'txp_users',
            "pass = '".doSlash($hash)."'",
            "name = '".doSlash($user)."'"
        ) === false
    ) {
        return false;
    }

    callback_event('user.password_change', 'done', 0, compact('user', 'password', 'hash'));

    return true;
}

/**
 * Removes a user.
 *
 * The user's assets are assigned to the given new owner.
 *
 * On a successful run, will trigger a 'user.remove > done' callback event.
 *
 * @param   string|array $user      List of removed users
 * @param   string       $new_owner Assign assets to
 * @return  bool FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (remove_user('user', 'new_owner'))
 * {
 *     echo "Removed 'user' and assigned assets to 'new_owner'.";
 * }
 */

function remove_user($user, $new_owner)
{
    if (!$user || !$new_owner) {
        return false;
    }

    $names = join(',', quote_list((array) $user));

    if (assign_user_assets($user, $new_owner) === false) {
        return false;
    }

    if (safe_delete('txp_prefs', "user_name IN ($names)") === false) {
        return false;
    }

    if (safe_delete('txp_users', "name IN ($names)") === false) {
        return false;
    }

    callback_event('user.remove', 'done', 0, compact('user', 'new_owner'));

    return true;
}

/**
 * Renames a user.
 *
 * On a successful run, will trigger a 'user.rename > done' callback event.
 *
 * @param   string $user    Updated user
 * @param   string $newname The new name
 * @return  bool FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (rename_user('login', 'newname'))
 * {
 *     echo "'login' renamed to 'newname'.";
 * }
 */

function rename_user($user, $newname)
{
    if (!is_scalar($user) || !is_valid_username($newname)) {
        return false;
    }

    if (assign_user_assets($user, $newname) === false) {
        return false;
    }

    if (
        safe_update(
            'txp_users',
            "name = '".doSlash($newname)."'",
            "name = '".doSlash($user)."'"
        ) === false
    ) {
        return false;
    }

    callback_event('user.rename', 'done', 0, compact('user', 'newname'));

    return true;
}

/**
 * Checks if a user exists.
 *
 * @param   string $user The user
 * @return  bool TRUE if the user exists
 * @since   4.6.0
 * @package User
 * @example
 * if (user_exists('john'))
 * {
 *     echo "'john' exists.";
 * }
 */

function user_exists($user)
{
    return (bool) safe_row("name", 'txp_users', "name = '".doSlash($user)."'");
}

/**
 * Changes a user's group.
 *
 * On a successful run, will trigger a 'user.change_group > done' callback event.
 *
 * @param   string|array $user  Updated users
 * @param   int          $group The new group
 * @return  bool FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (change_user_group('john', 1))
 * {
 *     echo "'john' is now publisher.";
 * }
 */

function change_user_group($user, $group)
{
    $levels = get_groups();

    if (!$user || !isset($levels[$group])) {
        return false;
    }

    $names = join(',', quote_list((array) $user));

    if (
        safe_update(
            'txp_users',
            "privs = ".intval($group),
            "name IN ($names)"
        ) === false
    ) {
        return false;
    }

    callback_event('user.change_group', 'done', 0, compact('user', 'group'));

    return true;
}

/**
 * Validates the given user credentials.
 *
 * Validates a given login and a password combination. If the combination is
 * correct, the user's login name is returned, FALSE otherwise.
 *
 * If $log is TRUE, also checks that the user has permissions to access the
 * admin side interface. On success, updates the user's last access timestamp.
 *
 * @param   string $user     The login
 * @param   string $password The password
 * @param   bool   $log      If TRUE, requires privilege level greater than 'none'
 * @return  string|bool The user's login name or FALSE on error
 * @package User
 */

function txp_validate($user, $password, $log = true)
{
    $safe_user = doSlash($user);
    $name = false;

    $r = safe_row("name, pass, privs", 'txp_users', "name = '$safe_user'");

    if (!$r) {
        return false;
    }

    // Check post-4.3-style passwords.
    if ($pass = Txp::get('\Textpattern\Password\Hash')->verify($password, $r['pass'])) {
        if (!$log || $r['privs'] > 0) {
            $name = $r['name'];
        }

        if ($pass === true) {
            safe_update('txp_users', "pass = '".doSlash(Txp::get('\Textpattern\Password\Hash')->hash($password))."'", "name = '$safe_user'");
        }
    } else {
        // No good password: check 4.3-style passwords.
        $pass = '*'.sha1(sha1($password, true));

        $name = safe_field("name", 'txp_users',
            "name = '$safe_user' AND privs > 0 AND (pass = UPPER('$pass') OR pass = LOWER('$pass'))");

        // Old password is good: migrate password to phpass.
        if ($name !== false) {
            safe_update('txp_users', "pass = '".doSlash(Txp::get('\Textpattern\Password\Hash')->hash($password))."'", "name = '$safe_user'");
        }
    }

    if ($name !== false && $log) {
        // Update the last access time.
        safe_update('txp_users', "last_access = NOW()", "name = '$safe_user'");
    }

    return $name;
}

/**
 * Calculates a password hash.
 *
 * @param   string $password The password
 * @return  string A hash
 * @see     PASSWORD_COMPLEXITY
 * @see     PASSWORD_PORTABILITY
 * @package User
 */

function txp_hash_password($password)
{
    static $phpass = null;

    if (!$phpass) {
        include_once txpath.'/lib/PasswordHash.php';
        $phpass = new PasswordHash(PASSWORD_COMPLEXITY, PASSWORD_PORTABILITY);
    }

    return $phpass->HashPassword($password);
}

/**
 * Create a secure token hash in the database from the passed information.
 *
 * @param  int    $ref             Reference to the user's account (user_id)
 * @param  string $type            Flavour of token to create
 * @param  int    $expiryTimestamp UNIX timestamp of when the token will expire
 * @param  string $pass            Password, used as part of the token generation
 * @param  string $nonce           Random nonce associated with the user's account
 * @return string                  Secure token suitable for emailing as part of a link
 * @since  4.6.1
 */

function generate_user_token($ref, $type, $expiryTimestamp, $pass, $nonce)
{
    $ref = assert_int($ref);
    $expiry = date('Y-m-d H:i:s', $expiryTimestamp);

    // The selector becomes an indirect reference to the user row id,
    // and thus does not leak information when publicly displayed.
    $selector = Txp::get('\Textpattern\Password\Random')->generate(12);

    // Use a hash of the nonce, selector and password.
    // This ensures that requests expire automatically when:
    //  a) The person logs in, or
    //  b) They successfully set/change their password
    // Using the selector in the hash just injects randomness, otherwise two requests
    // back-to-back would generate the same code.
    // Old requests for the same user id are purged when password is set.
    $token = bin2hex(pack('H*', substr(hash(HASHING_ALGORITHM, $nonce.$selector.$pass), 0, SALT_LENGTH)));
    $user_token = $token.$selector;

    // Remove any previous activation tokens and insert the new one.
    $safe_type = doSlash($type);
    safe_delete("txp_token", "reference_id = '$ref' AND type = '$safe_type'");
    safe_insert("txp_token",
            "reference_id = '$ref',
            type = '$safe_type',
            selector = '".doSlash($selector)."',
            token = '".doSlash($token)."',
            expires = '".doSlash($expiry)."'
        ");

    return $user_token;
}

/**
 * Display a modal client message in response to an AJAX request and
 * halt execution.
 *
 * @param   string|array $thing The $thing[0] is the message's text; $thing[1] is the message's type (one of E_ERROR or E_WARNING, anything else meaning "success"; not used)
 * @since   4.5.0
 * @package Ajax
 */

function modal_halt($thing)
{
    global $app_mode, $theme;

    if ($app_mode == 'async') {
        send_script_response($theme->announce_async($thing, true));
        die();
    }
}

/**
 * Sends an activity message to the client.
 *
 * @param   string|array $message The message
 * @param   int          $type    The type, either 0, E_ERROR, E_WARNING
 * @param   int          $flags   Flags, consisting of TEXTPATTERN_ANNOUNCE_ADAPTIVE | TEXTPATTERN_ANNOUNCE_ASYNC | TEXTPATTERN_ANNOUNCE_MODAL | TEXTPATTERN_ANNOUNCE_REGULAR
 * @package Announce
 * @since   4.6.0
 * @example
 * echo announce('My message', E_WARNING);
 */

function announce($message, $type = 0, $flags = TEXTPATTERN_ANNOUNCE_ADAPTIVE)
{
    global $app_mode, $theme;

    if (!is_array($message)) {
        $message = array($message, $type);
    }

    if ($flags & TEXTPATTERN_ANNOUNCE_ASYNC || ($flags & TEXTPATTERN_ANNOUNCE_ADAPTIVE && $app_mode === 'async')) {
        return $theme->announce_async($message);
    }

    if ($flags & TEXTPATTERN_ANNOUNCE_MODAL) {
        return $theme->announce_async($message, true);
    }

    return $theme->announce($message);
}

/**
 * Loads date definitions from a localisation file.
 *
 * @param      string $lang The language
 * @package    L10n
 * @deprecated in 4.6.0
 */

function load_lang_dates($lang)
{
    $filename = is_file(txpath.'/lang/'.$lang.'_dates.txt') ?
        txpath.'/lang/'.$lang.'_dates.txt' :
        txpath.'/lang/en-gb_dates.txt';
    $file = @file(txpath.'/lang/'.$lang.'_dates.txt', 'r');

    if (is_array($file)) {
        foreach ($file as $line) {
            if ($line[0] == '#' || strlen($line) < 2) {
                continue;
            }

            list($name, $val) = explode('=>', $line, 2);
            $out[trim($name)] = trim($val);
        }

        return $out;
    }

    return false;
}

/**
 * Gets language strings for the given event.
 *
 * If no $lang is specified, the strings are loaded from the currently
 * active language.
 *
 * @param   string $event The event to get, e.g. "common", "admin", "public"
 * @param   string $lang  The language code
 * @return  array|string Array of string on success, or an empty string when no strings were found
 * @package L10n
 * @see     load_lang()
 * @example
 * print_r(
 *     load_lang_event('common')
 * );
 */

function load_lang_event($event, $lang = LANG)
{
    $installed = (false !== safe_field("name", 'txp_lang', "lang = '".doSlash($lang)."' LIMIT 1"));

    $lang_code = ($installed) ? $lang : TEXTPATTERN_DEFAULT_LANG;

    $rs = safe_rows_start("name, data", 'txp_lang', "lang = '".doSlash($lang_code)."' AND event = '".doSlash($event)."'");

    $out = array();

    if ($rs && !empty($rs)) {
        while ($a = nextRow($rs)) {
            $out[$a['name']] = $a['data'];
        }
    }

    return ($out) ? $out : '';
}

/**
 * Installs localisation strings from a Textpack.
 *
 * @param      string $textpack      The Textpack to install
 * @param      bool   $add_new_langs If TRUE, installs strings for any included language
 * @return     int Number of installed strings
 * @package    L10n
 * @deprecated in 4.7.0
 */

function install_textpack($textpack, $add_new_langs = false)
{
    return Txp::get('\Textpattern\L10n\Lang')->installTextpack($textpack, $add_new_langs);
}

/**
 * Generate a ciphered token.
 *
 * The token is reproducible, unique among sites and users, expires later.
 *
 * @return  string The token
 * @see     bouncer()
 * @package CSRF
 */

function form_token()
{
    static $token = null;
    global $txp_user;

    // Generate a ciphered token from the current user's nonce (thus valid for
    // login time plus 30 days) and a pinch of salt from the blog UID.
    if ($token === null && $txp_user) {
        $nonce = safe_field("nonce", 'txp_users', "name = '".doSlash($txp_user)."'");
        $token = md5($nonce.get_pref('blog_uid'));
    }

    return $token;
}

/**
 * Validates admin steps and protects against CSRF attempts using tokens.
 *
 * Takes an admin step and validates it against an array of valid steps.
 * The valid steps array indicates the step's token based session riding
 * protection needs.
 *
 * If the step requires CSRF token protection, and the request doesn't come with
 * a valid token, the request is terminated, defeating any CSRF attempts.
 *
 * If the $step isn't in valid steps, it returns FALSE, but the request
 * isn't terminated. If the $step is valid and passes CSRF validation,
 * returns TRUE.
 *
 * @param   string $step  Requested admin step
 * @param   array  $steps An array of valid steps with flag indicating CSRF needs, e.g. array('savething' => true, 'listthings' => false)
 * @return  bool If the $step is valid, proceeds and returns TRUE. Dies on CSRF attempt.
 * @see     form_token()
 * @package CSRF
 * @example
 * global $step;
 * if (bouncer($step, array(
 *     'browse'     => false,
 *     'edit'       => false,
 *     'save'       => true,
 *     'multi_edit' => true,
 * )))
 * {
 *     echo "The '{$step}' is valid.";
 * }
 */

function bouncer($step, $steps)
{
    global $event;

    if (empty($step)) {
        return true;
    }

    // Validate step.
    if (!array_key_exists($step, $steps)) {
        return false;
    }

    // Does this step require a token?
    if (!$steps[$step]) {
        return true;
    }

    if (is_array($steps[$step]) && gpsa(array_keys($steps[$step])) != $steps[$step]) {
        return true;
    }

    // Validate token.
    if (gps('_txp_token') === form_token()) {
        return true;
    }

    die(gTxt('get_off_my_lawn', array(
        '{event}' => $event,
        '{step}'  => $step,
    )));
}

/**
 * Checks install's file integrity and returns results.
 *
 * Depending on the given $flags this function will either return an array of
 * file statuses, checksums or the digest of the install. It can also return the
 * parsed contents of the checksum file.
 *
 * @param   int $flags Options are INTEGRITY_MD5 | INTEGRITY_STATUS | INTEGRITY_REALPATH | INTEGRITY_DIGEST
 * @return  array|bool Array of files and status, or FALSE on error
 * @since   4.6.0
 * @package Debug
 * @example
 * print_r(
 *     check_file_integrity(INTEGRITY_MD5 | INTEGRITY_REALPATH)
 * );
 */

function check_file_integrity($flags = INTEGRITY_STATUS)
{
    static $files = null, $files_md5 = array(), $checksum_table = array();

    if ($files === null) {
        $checksums = txpath.'/checksums.txt';

        if (is_readable($checksums) && ($cs = file($checksums))) {
            $files = array();

            foreach ($cs as $c) {
                if (preg_match('@^(\S+):(?: r?(\S+) | )\(?(.{32})\)?$@', trim($c), $m)) {
                    list(, $relative, $r, $md5) = $m;
                    $file = realpath(txpath.$relative);
                    $checksum_table[$relative] = $md5;

                    if ($file === false) {
                        $files[$relative] = INTEGRITY_MISSING;
                        $files_md5[$relative] = false;
                        continue;
                    }

                    if (!is_readable($file)) {
                        $files[$relative] = INTEGRITY_NOT_READABLE;
                        $files_md5[$relative] = false;
                        continue;
                    }

                    if (!is_file($file)) {
                        $files[$relative] = INTEGRITY_NOT_FILE;
                        $files_md5[$relative] = false;
                        continue;
                    }

                    $files_md5[$relative] = md5_file($file);

                    if ($files_md5[$relative] !== $md5) {
                        $files[$relative] = INTEGRITY_MODIFIED;
                    } else {
                        $files[$relative] = INTEGRITY_GOOD;
                    }
                }
            }

            if (!get_pref('enable_xmlrpc_server', true)) {
                unset(
                    $files_md5['/../rpc/index.php'],
                    $files_md5['/../rpc/TXP_RPCServer.php'],
                    $files['/../rpc/index.php'],
                    $files['/../rpc/TXP_RPCServer.php']
                );
            }
        } else {
            $files_md5 = $files = false;
        }
    }

    if ($flags & INTEGRITY_DIGEST) {
        return $files_md5 ? md5(implode(n, $files_md5)) : false;
    }

    if ($flags & INTEGRITY_TABLE) {
        return $checksum_table ? $checksum_table : false;
    }

    $return = $files;

    if ($flags & INTEGRITY_MD5) {
        $return = $files_md5;
    }

    if ($return && $flags & INTEGRITY_REALPATH) {
        $relative = array();

        foreach ($return as $path => $status) {
            $realpath = realpath(txpath.$path);
            $relative[!$realpath ? $path : $realpath] = $status;
        }

        return $relative;
    }

    return $return;
}

/**
 * Assert system requirements.
 *
 * @access private
 */

function assert_system_requirements()
{
    if (version_compare(REQUIRED_PHP_VERSION, PHP_VERSION) > 0) {
        txp_die('This server runs PHP version '.PHP_VERSION.'. Textpattern needs PHP version '.REQUIRED_PHP_VERSION.' or better.');
    }

    if (!extension_loaded('simplexml')) {
        txp_die('This server does not have the required SimpleXML library installed (php-xml). Please install it.');
    }
}

/**
 * Get Theme prefs
 * Now Textpattern does not support themes. If the setup folder is deleted, it will return an empty array.
 */

function get_prefs_theme()
{
    $out = json_decode(txp_get_contents(txpath.'/setup/data/theme.prefs'), true);
    if (empty($out)) {
        return array();
    }

    return $out;
}


/**
 * Renders an array of available ways to display the date.
 * @return array
 */

function txp_dateformats()
{
    $old_reporting = error_reporting(0);

    $dayname = '%A';
    $dayshort = '%a';
    $daynum = is_numeric(@strftime('%e')) ? '%e' : '%d';
    $daynumlead = '%d';
    $daynumord = is_numeric(substr(trim(@strftime('%Oe')), 0, 1)) ? '%Oe' : $daynum;
    $monthname = '%B';
    $monthshort = '%b';
    $monthnum = '%m';
    $year = '%Y';
    $yearshort = '%y';
    $time24 = '%H:%M';
    $time12 = @strftime('%p') ? '%I:%M %p' : $time24;
    $date = @strftime('%x') ? '%x' : '%Y-%m-%d';

    error_reporting($old_reporting);

    return array(
        "since",
        "$monthshort $daynumord",
        "$monthshort $daynumord, $time12",
        "$daynum.$monthnum.$yearshort",
        "$daynumord $monthname, $time12",
        "$yearshort.$monthnum.$daynumlead, $time12",
        "$dayshort $monthshort $daynumord, $time12",
        "$dayname $monthname $daynumord, $year",
//        "$daynumord $monthname $yearshort",
//        "$daynumord $monthnum $year - $time24",
        "$daynumord $monthname $year",
        "$daynumord $monthname $year, $time24",
//        "$daynumord. $monthname $year",
//        "$daynumord. $monthname $year, $time24",
        "$year-$monthnum-$daynumlead",
        "$year-$monthnum-$daynumlead $time24",
//        "$year-$daynumlead-$monthnum",
        "$date $time12",
        "$date",
//        "$time24",
//        "$time12",
    );
}

<?php

/**
 * Collection of miscellaneous tools.
 *
 * @package Misc
 */

/**
 * Strips NULL bytes.
 *
 * @param  string|array $in The input value
 * @return mixed
 */

	function deNull($in)
	{
		return is_array($in) ? doArray($in, 'deNull') : strtr($in, array("\0" => ''));
	}

/**
 * Strips carriage returns and linefeeds.
 *
 * @param  string|array $in The input value
 * @return mixed
 */

	function deCRLF($in)
	{
		return is_array($in) ? doArray($in, 'deCRLF') : strtr($in, array("\n" => '', "\r" => ''));
	}

/**
 * Applies a callback to a given string or an array.
 *
 * @param  string|array $in       An array or a string to run through the callback function
 * @param  callback     $function The callback function
 * @return mixed
 * @example
 * echo doArray(array('value1', 'value2'), 'intval');
 */

	function doArray($in, $function)
	{
		if (is_array($in))
		{
			return array_map($function, $in);
		}

		if (is_array($function))
		{
			return call_user_func($function, $in);
		}

		return $function($in);
	}

/**
 * Un-quotes a quoted string or an array of values.
 *
 * @param  string|array $in The input value
 * @return mixed
 */

	function doStrip($in)
	{
		return is_array($in) ? doArray($in, 'doStrip') : doArray($in, 'stripslashes');
	}

/**
 * Strips HTML and PHP tags from a string or an array.
 *
 * @param  string|array $in The input value
 * @return mixed
 * @example
 * echo doStripTags('<p>Hello world!</p>');
 */

	function doStripTags($in)
	{
		return is_array($in) ? doArray($in, 'doStripTags') : doArray($in, 'strip_tags');
	}

/**
 * Converts entity escaped brackets back to characters.
 *
 * @param  string|array $in The input value
 * @return mixed
 */

	function doDeEnt($in)
	{
		return doArray($in, 'deEntBrackets');
	}

/**
 * Converts entity escaped brackets back to characters.
 *
 * @param  string $in The input value
 * @return string
 */

	function deEntBrackets($in)
	{
		$array = array(
			'&#60;'  => '<',
			'&lt;'   => '<',
			'&#x3C;' => '<',
			'&#62;'  => '>',
			'&gt;'   => '>',
			'&#x3E;' => '>',
		);

		foreach ($array as $k => $v)
		{
			$in = preg_replace("/".preg_quote($k)."/i", $v, $in);
		}

		return $in;
	}

/**
 * Escapes special characters for use in an SQL statement.
 *
 * Always use this function when dealing with user-defined values
 * in SQL statements. If this function is not used to escape
 * user-defined data in a statement, the query is vulnerable to
 * SQL injection attacks.
 *
 * @param   string|array $in The input value
 * @return  mixed        An array of escaped values or a string depending on $in
 * @package DB
 * @example
 * echo safe_field('column', 'table', "color='" . doSlash(gps('color')) . "'");
 */

	function doSlash($in)
	{
		return doArray($in, 'safe_escape');
	}

/**
 * Escape SQL LIKE pattern's wildcards for use in an SQL statement.
 *
 * @param   string|array $in The input value
 * @return  mixed        An array of escaped values or a string depending on $in
 * @since   4.6.0
 * @package DB
 * @example
 * echo safe_field('column', 'table', "color LIKE '" . doLike(gps('color')) . "'");
 */

	function doLike($in)
	{
		return doArray($in, 'safe_escape_like');
	}

/**
 * A shell for htmlspecialchars() with $flags defaulting to ENT_QUOTES.
 *
 * @param   string $string The string being converted
 * @param   int    $flags A bitmask of one or more flags. The default is ENT_QUOTES
 * @param   string $encoding Defines encoding used in conversion. The default is UTF-8
 * @param   bool   $double_encode When double_encode is turned off PHP will not encode existing HTML entities, the default is to convert everything
 * @return  string
 * @see     http://www.php.net/manual/function.htmlspecialchars.php
 * @since   4.5.0
 * @package Filter
 */

	function txpspecialchars($string, $flags = ENT_QUOTES, $encoding = 'UTF-8', $double_encode = true)
	{
//		Ignore ENT_HTML5 and ENT_XHTML for now.
//		ENT_HTML5 and ENT_XHTML are defined in PHP 5.4+ but we consistently encode single quotes as &#039; in any doctype.
//		global $prefs;
//		static $h5 = null;
//		if (defined(ENT_HTML5)) {
//			if ($h5 === null) {
//				$h5 = ($prefs['doctype'] == 'html5' && txpinterface == 'public');
//			}
//			if ($h5) {
//				$flags = ($flags | ENT_HTML5) & ~ENT_HTML401;
//			}
//		}
		return htmlspecialchars($string, $flags, $encoding, $double_encode);
	}

/**
 * Converts special characters to HTML entities.
 *
 * @param   array|string $in The input value
 * @return  mixed        The array or string with HTML syntax characters escaped
 * @package Filter
 */

	function doSpecial($in)
	{
		return doArray($in, 'txpspecialchars');
	}

/**
 * Converts the given value to NULL.
 *
 * @param   mixed $a The input value
 * @return  null
 * @package Filter
 * @access  private
 */

	function _null($a)
	{
		return null;
	}

/**
 * Converts an array of values to NULL.
 *
 * @param   array $in The array
 * @return  array
 * @package Filter
 */

	function array_null($in)
	{
		return array_map('_null', $in);
	}

/**
 * Escapes a page title. Converts &lt;, &gt;, ', " characters to HTML entities.
 *
 * @param   string $title The input string
 * @return  string The string escaped
 * @package Filter
 */

	function escape_title($title)
	{
		return strtr($title, array(
			'<' => '&#60;',
			'>' => '&#62;',
			"'" => '&#39;',
			'"' => '&#34;',
		));
	}

/**
 * Sanitises a string for use in a JavaScript string.
 *
 * This function escapes \, \n, \r, " and ' characters. When
 * you need to pass a string from PHP to JavaScript, use this
 * function to sanitise the value to avoid XSS attempts.
 *
 * @param   string $js JavaScript input
 * @return  string Escaped JavaScript
 * @since   4.4.0
 * @package Filter
 */

	function escape_js($js)
	{
		return addcslashes($js, "\\\'\"\n\r");
	}

/**
 * A shell for htmlspecialchars() with $flags defaulting to ENT_QUOTES.
 *
 * @param      string $str The input string
 * @return     string
 * @deprecated in 4.2.0
 * @see        txpspecialchars()
 * @package    Filter
 */

	function escape_output($str)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'txpspecialchars')), E_USER_NOTICE);
		return txpspecialchars($str);
	}

/**
 * Replaces &lt; and &gt; characters with entities.
 *
 * @param      string $str The input string
 * @return     string
 * @deprecated in 4.2.0
 * @see        txpspecialchars()
 * @package    Filter
 */

	function escape_tags($str)
	{
		trigger_error(gTxt('deprecated_function', array('{name}' => __FUNCTION__)), E_USER_NOTICE);
		return strtr($str, array(
			'<' => '&#60;',
			'>' => '&#62;',
		));
	}

/**
 * Escapes CDATA section for an XML document.
 *
 * @param   string $str The string
 * @return  string XML representation wrapped in CDATA tags
 * @package XML
 */

	function escape_cdata($str)
	{
		return '<![CDATA['.str_replace(']]>', ']]]><![CDATA[]>', $str).']]>';
	}

/**
 * Returns a localisation string.
 *
 * @param   string $var    String name
 * @param   array  $atts   Replacement pairs
 * @param   string $escape Convert special characters to HTML entities. Either "html" or ""
 * @return  string A localisation string
 * @package L10n
 */

	function gTxt($var, $atts = array(), $escape = 'html')
	{
		global $textarray;

		if (!is_array($atts))
		{
			$atts = array();
		}

		if ($escape == 'html')
		{
			foreach ($atts as $key => $value)
			{
				$atts[$key] = txpspecialchars($value);
			}
		}

		$v = strtolower($var);
		if (isset($textarray[$v]))
		{
			$out = $textarray[$v];
			if ($out !== '')
			{
				return strtr($out, $atts);
			}
		}

		if ($atts)
		{
			return $var.': '.join(', ', $atts);
		}

		return $var;
	}

/**
 * Loads client-side localisation scripts.
 *
 * This function passes localisation strings from the database
 * to JavaScript.
 *
 * Only works on the admin-side pages.
 *
 * @param   string|array $var  Scalar or array of string keys
 * @param   array        $atts Array or array of arrays of variable substitution pairs
 * @since   4.5.0
 * @package L10n
 * @example
 * gTxtScript(array('string1', 'string2', 'string3'));
 */

	function gTxtScript($var, $atts = array())
	{
		global $textarray_script;

		if (!is_array($textarray_script))
		{
			$textarray_script = array();
		}

		$data = is_array($var) ? array_map('gTxt', $var, $atts) : (array) gTxt($var, $atts);
		$textarray_script = $textarray_script + array_combine((array) $var, $data);
	}

/**
 * Returns given timestamp in a format of 01 Jan 2001 15:19:16.
 *
 * @param   int    $timestamp The UNIX timestamp
 * @return  string A formatted date
 * @access  private
 * @see     safe_stftime()
 * @package DateTime
 * @example
 * echo gTime();
 */

	function gTime($timestamp = 0)
	{
		return safe_strftime('%d&#160;%b&#160;%Y %X', $timestamp);
	}

/**
 * Cretes a dumpfile from a backtrace and outputs given parameters.
 *
 * @package Debug
 */

	function dmp()
	{
		static $f = false;

		if (defined('txpdmpfile'))
		{
			global $prefs;

			if (!$f)
			{
				$f = fopen($prefs['tempdir'].'/'.txpdmpfile, 'a');
			}

			$stack = get_caller();
			fwrite($f, "\n[".$stack[0].t.safe_strftime('iso8601')."]\n");
		}

		$a = func_get_args();

		if (!$f)
		{
			echo "<pre>".n;
		}

		foreach ($a as $thing)
		{
			$out = is_scalar($thing) ? strval($thing) : var_export($thing, true);

			if ($f)
			{
				fwrite($f, $out.n);
			}
			else
			{
				echo txpspecialchars($out).n;
			}
		}

		if (!$f)
		{
			echo "</pre>".n;
		}
	}

/**
 * Gets the given language's strings from the database.
 *
 * This function gets the given language from the database
 * and returns the strings as an array.
 * 
 * If no $events is specified, only appropriate strings for the 
 * current context are returned. If 'txpinterface' constant equals 'admin' all
 * strings are returned. Otherwise, only strings from events 'common' and 'public'.
 *
 * If $events is FALSE, returns all strings.
 *
 * @param   string            $lang   The language code
 * @param   array|string|bool $events An array of loaded events
 * @return  array
 * @package L10n
 * @see     load_lang_event()
 * @example
 * print_r(
 * 	load_lang('en-gb', false)
 * );
 */

	function load_lang($lang, $events = null)
	{
		if ($events === null && txpinterface != 'admin')
		{
			$events = array('public', 'common');
		}

		$where = '';

		if ($events)
		{
			$where .= ' and event in('.join(',', quote_list((array) $events)).')';
		}

		foreach(array($lang, 'en-gb') as $lang_code)
		{
			$rs = safe_rows('name, data', 'txp_lang', "lang='".doSlash($lang_code)."'".$where);

			if (!empty($rs))
			{
				break;
			}
		}

		$out = array();

		if (!empty($rs))
		{
			foreach ($rs as $a)
			{
				if (!empty($a['name']))
				{
					$out[$a['name']] = $a['data'];
				}
			}
		}
		else
		{
			// Backward compatibility stuff. Remove when necessary.
			$filename = is_file(txpath.'/lang/'.$lang.'.txt')
				? txpath.'/lang/'.$lang.'.txt'
				: txpath.'/lang/en-gb.txt';

			$file = @fopen($filename, "r");
			if ($file)
			{
				while (!feof($file))
				{
					$line = fgets($file, 4096);
					if ($line[0] == '#')
					{
						continue;
					}
					@list($name, $val) = explode(' => ', trim($line));
					$out[$name] = $val;
				}
				@fclose($filename);
			}
		}

		return $out;
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
		$filename = is_file(txpath.'/lang/'.$lang.'_dates.txt')?
			txpath.'/lang/'.$lang.'_dates.txt':
			txpath.'/lang/en-gb_dates.txt';
		$file = @file(txpath.'/lang/'.$lang.'_dates.txt', 'r');
		if (is_array($file))
		{
			foreach ($file as $line)
			{
				if ($line[0] == '#' || strlen($line) < 2)
				{
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
 * If no $lang is specified, the strings are loaded
 * from the currently active language.
 *
 * @param   string       $event The event to get, e.g. "common", "admin", "public"
 * @param   string       $lang  The language code
 * @return  array|string Array of string on success, or an empty string when no strings were found
 * @package L10n
 * @see     load_lang()
 * @example
 * print_r(
 * 	load_lang_event('common')
 * );
 */

	function load_lang_event($event, $lang = LANG)
	{
		$installed = (false !== safe_field('name', 'txp_lang',"lang='".doSlash($lang)."' limit 1"));

		$lang_code = ($installed) ? $lang : 'en-gb';

		$rs = safe_rows_start('name, data', 'txp_lang', "lang='".doSlash($lang_code)."' AND event='".doSlash($event)."'");

		$out = array();

		if ($rs && !empty($rs))
		{
			while ($a = nextRow($rs))
			{
				$out[$a['name']] = $a['data'];
			}
		}

		return ($out) ? $out : '';
	}

/**
 * Requires privileges from a user.
 *
 * @deprecated in 4.3.0
 * @see        require_privs()
 * @package    User
 */

	function check_privs()
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'require_privs')), E_USER_NOTICE);
		global $txp_user;
		$privs = safe_field("privs", "txp_users", "name='".doSlash($txp_user)."'");
		$args = func_get_args();
		if(!in_array($privs,$args)) {
			exit(pageTop('Restricted').'<p style="margin-top:3em;text-align:center">'.
				gTxt('restricted_area').'</p>');
		}
	}

/**
 * Grants privileges to user-groups.
 *
 * This function will not let you to override existing privs.
 *
 * @param   string $res  The resource
 * @param   string $perm List of user-groups, e.g. '1,2,3'
 * @package User
 * @example
 * add_privs('my_admin_side_panel_event', '1,2,3,4,5');
 */

	function add_privs($res, $perm = '1')
	{
		global $txp_permissions;

		if (!isset($txp_permissions[$res]))
		{
			$perm = join(',', do_list($perm));
			$txp_permissions[$res] = $perm;
		}
	}

/**
 * Checks if a users has priviliges to the given resource.
 *
 * @param   string $res  The resource
 * @param   string $user The user. If no user name is supplied, assume the current logged in user
 * @return  bool
 * @package User
 * @example
 * add_privs('my_privilege_resource', '1,2,3');
 * if (has_privs('my_privilege_resource', 'username'))
 * {
 * 	echo "'username' has privileges to 'my_privilege_resource'.";
 * }
 */

	function has_privs($res, $user = '')
	{
		global $txp_user, $txp_permissions;
		static $privs;

		if (empty($user))
		{
			$user = $txp_user;
		}

		if (!isset($privs[$user]))
		{
			$privs[$user] = safe_field("privs", "txp_users", "name='".doSlash($user)."'");
		}

		if (isset($txp_permissions[$res]) && $privs[$user] && $txp_permissions[$res])
		{
			return in_array($privs[$user], explode(',', $txp_permissions[$res]));
		}
		else
		{
			return false;
		}
	}

/**
 * Require privileges from a user to the given resource.
 *
 * Terminates the script if user doesn't have required privileges.
 *
 * @param   string $res  The resource
 * @param   string $user The user. If no user name is supplied, assume the current logged in user
 * @package User
 * @example
 * require_privs('article.edit');
 */

	function require_privs($res, $user = '')
	{
		if (!has_privs($res, $user))
		{
			pagetop('Restricted');
			echo graf(gTxt('restricted_area'), ' class="restricted-area"');
			end_page();
			exit;
		}
	}

/**
 * Gets a list of users having access to a resource.
 *
 * @param   string $res The resource, e.g. 'article.edit.published'
 * @return  array  A list of usernames
 * @since   4.5.0
 * @package User
 */

	function the_privileged($res)
	{
		global $txp_permissions;

		if (isset($txp_permissions[$res]))
		{
			return safe_column('name', 'txp_users', "FIND_IN_SET(privs, '{$txp_permissions[$res]}') order by name asc");
		}
		else
		{
			return array();
		}
	}

/**
 * Gets a list of user groups.
 *
 * @return  array
 * @package User
 * @example
 * print_r(
 * 	get_groups()
 * );
 */

	function get_groups()
	{
		global $txp_groups;
		return doArray($txp_groups, 'gTxt');
	}

/**
 * Gets the dimensions of an image for a HTML &lt;img&gt; tag.
 *
 * @param   string      $name The filename
 * @return  string|bool height="100" width="40", or FALSE on failure
 * @package Image
 * @example
 * if ($size = sizeImage('/path/to/image.png'))
 * {
 * 	echo "&lt;img src='image.png' {$size} /&gt;";
 * }
 */

	function sizeImage($name)
	{
		$size = @getimagesize($name);
		return is_array($size) ? $size[3] : false;
	}

/**
 * Lists image types that can be safely uploaded.
 *
 * This function returns different results
 * based on the logged in user's privileges.
 *
 * @param   int         $type If set, validates the given value
 * @return  mixed
 * @package Image
 * @since   4.6.0
 * @example
 * list($width, $height, $extension) = getimagesize('image');
 * if ($type = get_safe_image_types($extension))
 * {
 * 	echo "Valid image of {$type}.";
 * }
 */

	function get_safe_image_types($type = null)
	{
		global $txp_user;

		if (!$txp_user || !has_privs('image.create.trusted'))
		{
			$extensions = array(0, '.gif', '.jpg', '.png');
		}
		else
		{
			$extensions = array(0, '.gif', '.jpg', '.png', '.swf', 0, 0, 0, 0, 0, 0, 0, 0, '.swf');
		}

		if (func_num_args() > 0)
		{
			return !empty($extensions[$type]) ? $extensions[$type] : false;
		}

		return $extensions;
	}

/**
 * Checks if GD supports the given image type.
 *
 * @param   string $image_type Either '.gif', '.png', '.jpg'
 * @return  bool   TRUE if the type is supported
 * @package Image
 */

	function check_gd($image_type)
	{
		if (!function_exists('gd_info'))
		{
			return false;
		}

		$gd_info = gd_info();

		switch ($image_type)
		{
			case '.gif' :
				return ($gd_info['GIF Create Support'] == true);
				break;
			case '.png' :
				return ($gd_info['PNG Support'] == true);
				break;
			case '.jpg' :
				return (!empty($gd_info['JPEG Support']) || !empty($gd_info['JPG Support']));
				break;
		}

		return false;
	}

/**
 * Uploads an image.
 *
 * This function can be used to upload a new image or replace an existing one.
 * If $id is specified, the image will be replaced. If $uploaded is set
 * FALSE, $file can take a local file instead of HTTP file upload variable.
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
 * 	$_FILES['myfile'],
 * 	array(
 * 		'caption' => '',
 * 		'alt' => '',
 * 		'category' => '',
 * 	)
 * ));
 */

	function image_data($file, $meta = array(), $id = 0, $uploaded = true)
	{
		global $txp_user, $prefs, $file_max_upload_size, $event;

		$name = $file['name'];
		$error = $file['error'];
		$file = $file['tmp_name'];

		if ($uploaded)
		{
			$file = get_uploaded_file($file);

			if ($file_max_upload_size < filesize($file))
			{
				unlink($file);

				return upload_get_errormsg(UPLOAD_ERR_FORM_SIZE);
			}
		}

		if (empty($file))
		{
			return upload_get_errormsg(UPLOAD_ERR_NO_FILE);
		}

		list($w, $h, $extension) = getimagesize($file);
		$ext = get_safe_image_types($extension);

		if (!$ext)
		{
			return gTxt('only_graphic_files_allowed');
		}

		$name = substr($name, 0, strrpos($name, '.')).$ext;
		$safename = doSlash($name);
		$meta = lAtts(array(
			'category' => '',
			'caption' => '',
			'alt' => ''
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
			date = now(),
			author = '".doSlash($txp_user)."'
		";

		if (empty($id))
		{
			$rs = safe_insert('txp_image', $q);

			if ($rs)
			{
				$id = $GLOBALS['ID'] = $rs;
			}

			$update = false;
		}
		else
		{
			$id = assert_int($id);
			$rs = safe_update('txp_image', $q, "id = $id");
			$update = true;
		}

		if (!$rs)
		{
			return gTxt('image_save_error');
		}

		$newpath = IMPATH.$id.$ext;

		if (shift_uploaded_file($file, $newpath) == false)
		{
			if (!$update)
			{
				safe_delete('txp_image', "id = $id");
			}

			unset($GLOBALS['ID']);
			return $newpath.sp.gTxt('upload_dir_perms');
		}

		@chmod($newpath, 0644);

		// GD is supported
		if (check_gd($ext))
		{
			// Auto-generate a thumbnail using the last settings
			if (isset($prefs['thumb_w'], $prefs['thumb_h'], $prefs['thumb_crop']))
			{
				$width  = intval($prefs['thumb_w']);
				$height = intval($prefs['thumb_h']);

				if ($width > 0 or $height > 0)
				{
					$t = new txp_thumb( $id );

					$t->crop = ($prefs['thumb_crop'] == '1');
					$t->hint = '0';
					$t->width = $width;
					$t->height = $height;

					$t->write();
				}
			}
		}

		$message = gTxt('image_uploaded', array('{name}' => $name));
		update_lastmod();

		// call post-upload plugins with new image's $id
		callback_event('image_uploaded', $event, false, $id);

		return array($message, $id);
	}

/**
 * Gets an image as an array.
 *
 * @param   string     $where SQL where clause
 * @return  array|bool An image data, or FALSE on failure
 * @package Image
 * @example
 * if ($image = fileDownloadFetchInfo('id = 1'))
 * {
 * 	print_r($image);
 * }
 */

	function imageFetchInfo($where)
	{
		$rs = safe_row('*', 'txp_image', $where);

		if ($rs)
		{
			return image_format_info($rs);
		}

		return false;
	}

/**
 * Formats image info.
 *
 * This function takes an image data array generated
 * by imageFetchInfo() and formats the contents.
 *
 * @param   array $image The image
 * @return  array
 * @see     imageFetchInfo()
 * @access  private
 * @package Image
 */

	function image_format_info($image)
	{
		if (($unix_ts = @strtotime($image['date'])) > 0)
		{
			$image['date'] = $unix_ts;
		}

		return $image;
	}

/**
 * Formats link info.
 *
 * @param   array $link The link to format
 * @return  array Formatted link data
 * @access  private
 * @package Link
 */

	function link_format_info($link)
	{
		if (($unix_ts = @strtotime($link['date'])) > 0)
		{
			$link['date'] = $unix_ts;
		}

		return $link;
	}

/**
 * Gets a HTTP GET or POST parameter.
 *
 * This function internally handles and normalises MAGIC_QUOTES_GPC,
 * strips CRLF from GET parameters and removes NULL bytes.
 *
 * @param   string       $thing The parameter to get
 * @return  string|array The value of $thing, or an empty string
 * @package Network
 * @example
 * if (gps('sky') == 'blue' && gps('roses') == 'red')
 * {
 * 	echo 'Roses are red, sky is blue.';
 * }
 */

	function gps($thing)
	{
		$out = '';
		if (isset($_GET[$thing]))
		{
			if (MAGIC_QUOTES_GPC)
			{
				$out = doStrip($_GET[$thing]);
			}
			else
			{
				$out = $_GET[$thing];
			}

			$out = doArray($out, 'deCRLF');
		}
		elseif (isset($_POST[$thing]))
		{
			if (MAGIC_QUOTES_GPC)
			{
				$out = doStrip($_POST[$thing]);
			}
			else
			{
				$out = $_POST[$thing];
			}
		}

		$out = doArray($out, 'deNull');

		return $out;
	}

/**
 * Gets an array of HTTP GET or POST parameters.
 *
 * @param   array $array The parameters to extract
 * @return  array
 * @package Network
 * @example
 * extract(gpsa(array('sky', 'roses'));
 * if ($sky == 'blue' && $roses == 'red')
 * {
 * 	echo 'Roses are red, sky is blue.';
 * }
 */

	function gpsa($array)
	{
		if (is_array($array))
		{
			$out = array();
			foreach ($array as $a)
			{
				$out[$a] = gps($a);
			}
			return $out;
		}

		return false;
	}

/**
 * Gets a HTTP POST parameter.
 *
 * This function internally handles and normalises MAGIC_QUOTES_GPC,
 * and removes NULL bytes.
 *
 * @param   string       $thing	The parameter to get
 * @return  string|array The value of $thing, or an empty string
 * @package Network
 * @example
 * if (ps('sky') == 'blue' && ps('roses') == 'red')
 * {
 * 	echo 'Roses are red, sky is blue.';
 * }
 */

	function ps($thing)
	{
		$out = '';
		if (isset($_POST[$thing]))
		{
			if (MAGIC_QUOTES_GPC)
			{
				$out = doStrip($_POST[$thing]);
			}
			else
			{
				$out = $_POST[$thing];
			}
		}

		$out = doArray($out, 'deNull');

		return $out;
	}

/**
 * Gets an array of HTTP POST parameters.
 *
 * @param   array $array The parameters to extract
 * @return  array
 * @package Network
 * @example
 * extract(psa(array('sky', 'roses'));
 * if ($sky == 'blue' && $roses == 'red')
 * {
 * 	echo 'Roses are red, sky is blue.';
 * }
 */

	function psa($array)
	{
		foreach($array as $a)
		{
			$out[$a] = ps($a);
		}
		return $out;
	}

/**
 * Gets an array of HTTP POST parameters and strips HTML and PHP tags from values.
 *
 * @param   array $array The parameters to extract
 * @return  array
 * @package Network
 */

	function psas($array)
	{
		foreach($array as $a)
		{
			$out[$a] = doStripTags(ps($a));
		}
		return $out;
	}

/**
 * Gets all received HTTP POST parameters.
 *
 * @return  array
 * @package Network
 */

	function stripPost()
	{
		if (isset($_POST))
		{
			if (MAGIC_QUOTES_GPC)
			{
				return doStrip($_POST);
			}
			else
			{
				return $_POST;
			}
		}
		return '';
	}

/**
 * Gets a variable from $_SERVER global array.
 *
 * @param   mixed $thing The variable
 * @return  mixed The variable, or an empty string on error
 * @package System
 * @example
 * echo serverSet('HTTP_USER_AGENT');
 */

	function serverSet($thing)
	{
		return (isset($_SERVER[$thing])) ? $_SERVER[$thing] : '';
	}

/**
 * Gets the client's IP address.
 *
 * This function supports proxies and uses 'X_FORWARDED_FOR'
 * HTTP header if deemed necessary.
 *
 * @return  string
 * @package Network
 * @example
 * if ($ip = remote_addr())
 * {
 * 	echo "Your IP address is: {$ip}.";
 * }
 */

	function remote_addr()
	{
		$ip = serverSet('REMOTE_ADDR');
		if (($ip == '127.0.0.1' || $ip == '::1' || $ip == '::ffff:127.0.0.1' || $ip == serverSet('SERVER_ADDR')) && serverSet('HTTP_X_FORWARDED_FOR'))
		{
			$ips = explode(', ', serverSet('HTTP_X_FORWARDED_FOR'));
			$ip = $ips[0];
		}
		return $ip;
	}

/**
 * Gets a variable from HTTP POST or a prefixed cookie.
 *
 * This function gets either a HTTP cookie of the given
 * name prefixed with 'txp_', or a HTTP POST parameter
 * without a prefix.
 *
 * @param   string       $thing The variable
 * @return  array|string The variable or an empty string
 * @package Network
 * @example
 * if ($cs = psc('myVariable'))
 * {
 * 	echo "'txp_myVariable' cookie or 'myVariable' POST parameter contained: '{$cs}'.";
 * }
 */

 	function pcs($thing)
	{
		if (isset($_COOKIE["txp_".$thing]))
		{
			if (MAGIC_QUOTES_GPC)
			{
				return doStrip($_COOKIE["txp_".$thing]);
			}
			else
			{
				return $_COOKIE["txp_".$thing];
			}
		}
		elseif (isset($_POST[$thing]))
		{
			if (MAGIC_QUOTES_GPC)
			{
				return doStrip($_POST[$thing]);
			}
			else
			{
				return $_POST[$thing];
			}
		}
		return '';
	}

/**
 * Gets a HTTP cookie.
 *
 * This function internally normalises MAGIC_QUOTES_GPC.
 *
 * @param   string $thing The cookie
 * @return  string The cookie or an empty string
 * @package Network
 * @example
 * if ($cs = cs('myVariable'))
 * {
 * 	echo "'myVariable' cookie contained: '{$cs}'.";
 * }
 */

 	function cs($thing)
	{
		if (isset($_COOKIE[$thing]))
		{
			if (MAGIC_QUOTES_GPC)
			{
				return doStrip($_COOKIE[$thing]);
			}
			else
			{
				return $_COOKIE[$thing];
			}
		}
		return '';
	}

/**
 * Converts a boolean to a localised "Yes" or "No" string.
 *
 * @param   bool   $status The boolean. Ignores type and as such can also take a string or an integer
 * @return  string No if FALSE, Yes otherwise
 * @package L10n
 * @example
 * echo yes_no(3 * 3 === 2);
 */

	function yes_no($status)
	{
		return ($status) ? gTxt('yes') : gTxt('no');
	}

/**
 * Gets UNIX timestamp with microseconds.
 *
 * @return  float
 * @package DateTime
 * @example
 * echo getmicrotime();
 */

	function getmicrotime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float) $usec + (float) $sec);
	}

/**
 * Loads the given plugin or checks if it was loaded.
 *
 * @param  string $name  The plugin
 * @param  bool   $force If TRUE loads the plugin even if it's disabled
 * @return bool   TRUE if the plugin is loaded
 * @example
 * if (load_plugin('abc_plugin'))
 * {
 * 	echo "'abc_plugin' is active.";
 * }
 */

	function load_plugin($name, $force=false)
	{
		global $plugins, $plugins_ver, $prefs, $txp_current_plugin;

		if (is_array($plugins) and in_array($name, $plugins))
		{
			return true;
		}

		if (!empty($prefs['plugin_cache_dir']))
		{
			$dir = rtrim($prefs['plugin_cache_dir'], '/') . '/';
			// In case it's a relative path.
			if (!is_dir($dir))
			{
				$dir = rtrim(realpath(txpath.'/'.$dir), '/') . '/';
			}
			if (is_file($dir . $name . '.php'))
			{
				$plugins[] = $name;
				set_error_handler("pluginErrorHandler");
				if (isset($txp_current_plugin))
				{
					$txp_parent_plugin = $txp_current_plugin;
				}
				$txp_current_plugin = $name;
				include($dir . $name . '.php');
				$txp_current_plugin = isset($txp_parent_plugin) ? $txp_parent_plugin : null;
				$plugins_ver[$name] = @$plugin['version'];
				restore_error_handler();
				return true;
			}
		}

		$rs = safe_row("name, code, version", "txp_plugin", ($force ? '' : 'status = 1 AND '). "name='".doSlash($name)."'");
		if ($rs)
		{
			$plugins[] = $rs['name'];
			$plugins_ver[$rs['name']] = $rs['version'];

			set_error_handler("pluginErrorHandler");
			if (isset($txp_current_plugin))
			{
				$txp_parent_plugin = $txp_current_plugin;
			}
			$txp_current_plugin = $rs['name'];
			eval($rs['code']);
			$txp_current_plugin = isset($txp_parent_plugin) ? $txp_parent_plugin : null;
			restore_error_handler();

			return true;
		}

		return false;
	}

/**
 * Loads a plugin.
 *
 * Identical to load_plugin() except upon failure it issues an E_USER_ERROR.
 *
 * @param  string $name The plugin
 * @return bool
 * @see    load_plugin()
 */

	function require_plugin($name)
	{
		if (!load_plugin($name))
		{
			trigger_error("Unable to include required plugin \"{$name}\"",E_USER_ERROR);
			return false;
		}
		return true;
	}

/**
 * Loads a plugin.
 *
 * Identical to load_plugin() except upon failure it issues an E_USER_WARNING.
 *
 * @param  string $name The plugin
 * @return bool
 * @see    load_plugin()
 */

	function include_plugin($name)
	{
		if (!load_plugin($name))
		{
			trigger_error("Unable to include plugin \"{$name}\"",E_USER_WARNING);
			return false;
		}
		return true;
	}

/**
 * Error handler for plugins.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

	function pluginErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status, $txp_current_plugin;

		$error = array(
			E_WARNING => "Warning",
			E_NOTICE => "Notice",
			E_RECOVERABLE_ERROR => "Catchable fatal error",
			E_USER_ERROR => "User_Error",
			E_USER_WARNING => "User_Warning",
			E_USER_NOTICE => "User_Notice"
		);

		if (!($errno & error_reporting()))
		{
			return;
		}

		if ($production_status == 'live' || ($production_status != 'debug' && $errno == E_USER_NOTICE))
		{
			return;
		}

		printf("<pre>".gTxt('plugin_load_error').' <b>%s</b> -> <b>%s: %s on line %s</b></pre>',
				$txp_current_plugin, $error[$errno], $errstr, $errline);

		if ($production_status == 'debug')
		{
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";
		}
	}

/**
 * Error handler for page templates.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

	function tagErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status, $txp_current_tag, $txp_current_form, $pretext;

		$error = array(
			E_WARNING => "Warning",
			E_NOTICE => "Notice",
			E_RECOVERABLE_ERROR => "Textpattern Catchable fatal error",
			E_USER_ERROR => "Textpattern Error",
			E_USER_WARNING => "Textpattern Warning",
			E_USER_NOTICE => "Textpattern Notice"
		);

		if (!($errno & error_reporting()))
		{
			return;
		}

		if ($production_status == 'live')
		{
			return;
		}

		$page = empty($pretext['page']) ? gTxt('none') : $pretext['page'];

		if (!isset($txp_current_form))
		{
			$txp_current_form = gTxt('none');
		}

		$locus = gTxt('while_parsing_page_form', array(
			'{page}' => txpspecialchars($page),
			'{form}' => txpspecialchars($txp_current_form)
		));

		printf("<pre>".gTxt('tag_error').' <b>%s</b> -> <b> %s: %s %s</b></pre>',
				txpspecialchars($txp_current_tag), $error[$errno], $errstr, $locus);

		if ($production_status == 'debug')
		{
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";

			$trace_msg = gTxt('tag_error').' '.$txp_current_tag.' -> '.$error[$errno].': '.$errstr.' '.$locus;
			trace_add($trace_msg);
		}
	}

/**
 * Error handler for XML feeds.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

	function feedErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		if ($production_status != 'debug')
		{
			return;
		}

		return tagErrorHandler($errno, $errstr, $errfile, $errline);
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

		if (!error_reporting())
		{
			return;
		}

		// When even a minimum environment is missing.
		if (!isset($production_status))
		{
			echo '<pre>'.gTxt('internal_error').' "'.$errstr.'"'.n."in $errfile at line $errline".'</pre>';
			return;
		}

		if ($production_status == 'live' || ($production_status != 'debug' && $errno == E_USER_NOTICE))
		{
			$backtrace = $msg = '';
		}
		else
		{
			$backtrace = '';
			$msg = gTxt('internal_error');

			if (has_privs('debug.verbose'))
			{
				$msg .= ' "'.$errstr.'"';
			}

			if ($production_status == 'debug')
			{
				if (has_privs('debug.backtrace'))
				{
					$msg .= n."in $errfile at line $errline";
					$backtrace = join(n, get_caller(5, 1));
				}
			}
		}

		$httpstatus = in_array($errno, array(E_ERROR, E_USER_ERROR)) ? '500' : '200';
		$out = "$msg.\n$backtrace";

		if (http_accept_format('html'))
		{
			if (!empty($backtrace))
			{
				echo "<pre>$msg.</pre>".
					n.'<pre style="padding-left: 2em;" class="backtrace"><code>'.
					txpspecialchars($backtrace).'</code></pre>';
			}
			elseif (!empty($msg))
			{
				echo is_object($theme) ? $theme->announce(array($out, E_ERROR), true) : "<pre>$out</pre>";
			}
			$c = array('in' => '', 'out' => '');
		}
		elseif (http_accept_format('js'))
		{
			send_script_response(
				is_object($theme) && !empty($msg) ?
				$theme->announce_async(array($out, E_ERROR), true) :
				"/* $out */"
			);
			$c = array('in' => '/* ', 'out' => ' */');
		}
		elseif (http_accept_format('xml'))
		{
			send_xml_response(array('http-status' => $httpstatus, 'internal_error' => "$out"));
			$c = array('in' => '<!-- ', 'out' => ' -->');
		}
		else
		{
			txp_die($msg, 500);
		}

		if ($production_status != 'live' && in_array($errno, array(E_ERROR, E_USER_ERROR)))
		{
			die($c['in'].gTxt('get_off_my_lawn', array(
				'{event}' => $event,
				'{step}' => $step
			)).$c['out']);
		}
	}

/**
 * Error handler for public-side.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

	function publicErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		$error = array(
			E_WARNING => "Warning",
			E_NOTICE => "Notice",
			E_USER_ERROR => "Textpattern Error",
			E_USER_WARNING => "Textpattern Warning",
			E_USER_NOTICE => "Textpattern Notice"
		);

		if (!($errno & error_reporting()))
		{
			return;
		}

		if ($production_status == 'live' || ($production_status != 'debug' && $errno == E_USER_NOTICE))
		{
			return;
		}

		printf ("<pre>".gTxt('general_error').' <b>%s: %s on line %s</b></pre>',
			$error[$errno], $errstr, $errline);

		if ($production_status == 'debug')
		{
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";
		}
	}

/**
 * Loads plugins.
 *
 * @param bool $type If TRUE loads admin-side plugins, otherwise public
 */

	function load_plugins($type = false)
	{
		global $prefs, $plugins, $plugins_ver, $app_mode;

		if (!is_array($plugins))
		{
			$plugins = array();
		}

		if (!empty($prefs['plugin_cache_dir']))
		{
			$dir = rtrim($prefs['plugin_cache_dir'], '/') . '/';

			// In case it's a relative path.
			if (!is_dir($dir))
			{
				$dir = rtrim(realpath(txpath.'/'.$dir), '/') . '/';
			}

			$files = glob($dir.'*.php');

			if ($files)
			{
				natsort($files);

				foreach ($files as $f)
				{
					trace_add("[Loading plugin from cache dir '$f']");
					load_plugin(basename($f, '.php'));
				}
			}
		}

		$admin = ($app_mode == 'async' ? '4,5' : '1,3,4,5');
		$where = 'status = 1 AND type IN ('.($type ? $admin : '0,1,5').')';

		$rs = safe_rows("name, code, version", "txp_plugin", $where.' order by load_order');

		if ($rs)
		{
			$old_error_handler = set_error_handler("pluginErrorHandler");
			foreach ($rs as $a)
			{
				if (!in_array($a['name'], $plugins))
				{
					$plugins[] = $a['name'];
					$plugins_ver[$a['name']] = $a['version'];
					$GLOBALS['txp_current_plugin'] = $a['name'];
					trace_add("[Loading plugin '{$a['name']}' version '{$a['version']}']");
					$eval_ok = eval($a['code']);

					if ($eval_ok === false)
					{
						echo gTxt('plugin_load_error_above').strong($a['name']).n.br;
					}

					unset($GLOBALS['txp_current_plugin']);
				}
			}
			restore_error_handler();
		}
	}

/**
 * Attachs a handler to a callback event.
 *
 * @param   callback $func  The callback function
 * @param   string   $event The callback event
 * @param   string   $step  The callback step
 * @param   bool     $pre   Before or after. Works only with selected callback events
 * @package Callback
 * @example
 * register_callback('my_callback_function', 'article.updated');
 * function my_callback_function($event)
 * {
 * 	return "'$event' fired.";
 * }
 */

	function register_callback($func, $event, $step = '', $pre = 0)
	{
		global $plugin_callback;
		$plugin_callback[] = array(
			'function' => $func,
			'event'    => $event,
			'step'     => $step,
			'pre'      => $pre,
		);
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
 * Call an event's callback.
 *
 * This function executes all callback handlers attached to the
 * matched event and step.
 *
 * When this function is called, any event handlers attached with
 * register_callback() to the matching event, step and pre will be called.
 * The handlers, callback functions, will be executed in the same order they
 * were registered.
 *
 * Any extra arguments will be passed to the callback handlers in the same
 * argument position. This allows passing any type of data to the attached
 * handlers. Callback handlers will also receive the event and the step.
 *
 * This function returns a compined value of all values returned by the callback
 * handlers.
 *
 * @param   string $event The callback event
 * @param   string $step  Additional callback step
 * @param   bool   $pre   Allows two callbacks, a prepending and an appending, with same event and step
 * @return  string The value returned by the attached callback functions, or an empty string
 * @package Callback
 * @see     register_callback()
 * @example
 * register_callback('my_callback_function', 'my_custom_event');
 * function my_callback_function($event, $step, $extra)
 * {
 * 	return "Passed '$extra' on '$event'.";
 * }
 * echo callback_event('my_custom_event', '', 0, 'myExtraValue');
 */

	function callback_event($event, $step = '', $pre = 0)
	{
		global $plugin_callback, $production_status;

		if (!is_array($plugin_callback))
		{
			return '';
		}

		$return_value = '';

		// Any payload parameters?
		$argv = func_get_args();
		$argv = (count($argv) > 3) ? array_slice($argv, 3) : array();

		foreach ($plugin_callback as $c)
		{
			if ($c['event'] == $event and (empty($c['step']) or $c['step'] == $step) and $c['pre'] == $pre)
			{
				if (is_callable($c['function']))
				{
					$return_value .= call_user_func_array($c['function'], array('event' => $event, 'step' => $step) + $argv);
				}
				elseif ($production_status == 'debug')
				{
					trigger_error(gTxt('unknown_callback_function', array('{function}' => callback_tostring($c['function']))), E_USER_WARNING);
				}
			}
		}

		return $return_value;
	}

/**
 * Call an event's callback with two optional byref parameters.
 *
 * @param   string $event   The callback event
 * @param   string $step    Optional callback step
 * @param   bool   $pre     Allows two callbacks, a prepending and an appending, with same event and step
 * @param   mixed  $data    Optional arguments for event handlers
 * @param   mixed  $options Optional arguments for event handlers
 * @return  array  Collection of return values from event handlers
 * @since   4.5.0
 * @package Callback
 */

 	function callback_event_ref($event, $step = '', $pre = 0, &$data = null, &$options = null)
	{
		global $plugin_callback, $production_status;

		if (!is_array($plugin_callback))
		{
			return array();
		}

		$return_value = array();

		foreach ($plugin_callback as $c)
		{
			if ($c['event'] == $event and (empty($c['step']) or $c['step'] == $step) and $c['pre'] == $pre)
			{
				if (is_callable($c['function']))
				{
					// Cannot call event handler via call_user_func() as this would dereference all arguments.
					// Side effect: callback handler *must* be ordinary function, *must not* be class method in PHP <5.4
					// see https://bugs.php.net/bug.php?id=47160
					$return_value[] = $c['function']($event, $step, $data, $options);
				}
				elseif ($production_status == 'debug')
				{
					trigger_error(gTxt('unknown_callback_function', array('{function}' => callback_tostring($c['function']))), E_USER_WARNING);
				}
			}
		}
		return $return_value;
	}

/**
 * Converts a callable to a string presentation.
 *
 * @param   callback $callback The callback
 * @return  string   The $callback as a human-readable string
 * @since   4.5.0
 * @package Callback
 * @see     string_tocallback()
 * @example
 * echo callback_tostring(array('class', 'method'));
 */

	function callback_tostring($callback)
	{
		if (is_array($callback))
		{
			$class = array_shift($callback);

			if (is_object($class))
			{
				$class = get_class($class);
			}

			array_unshift($callback, $class);
			return join('::', array_filter($callback, 'is_scalar'));
		}
		elseif (!is_scalar($callback))
		{
			return '';
		}

		return $callback;
	}

/**
 * Convers a string presentation to a callable.
 *
 * This function returns FALSE if the given string isn't
 * a valid callback.
 *
 * @param   string $string The callback string
 * @return  mixed  Callable on success, FALSE on error
 * @since   4.6.0
 * @package Callback
 * @see     callback_tostring()
 * @example
 * echo call_user_func(string_to_callback('class->method'));
 */

	function string_tocallback($string)
	{
		$callback = $string;

		if (is_string($string))
		{
			if (strpos($string, '->'))
			{
				$callback = explode('->', $string);

				if (!class_exists($callback[0]))
				{
					return false;
				}

				$callback[0] = new $callback[0];
			}
			else if (strpos($string, '::'))
			{
				$callback = explode('::', $string);
			}
		}

		if (!is_callable($callback))
		{
			return false;
		}

		return $callback;
	}

/**
 * Checks if a callback event has active handlers.
 *
 * @param   string $event   The callback event
 * @param   string $step    The callback step
 * @param   bool   $pre     The position
 * @return  bool   TRUE if the event is active, FALSE otherwise
 * @since   4.6.0
 * @package Callback
 * @example
 * if (has_handlers('article_saved'))
 * {
 * 	echo "There are active handlers for 'article_saved' event.";
 * }
 */

	function has_handler($event, $step = '', $pre = 0)
	{
		return (bool) callback_handlers($event, $step, $pre, false);
	}

/**
 * Lists handlers attached to an event.
 *
 * @param   string     $event     The callback event
 * @param   string     $step      The callback step
 * @param   bool       $pre       The position
 * @param   bool       $as_string Return callables in string representation
 * @return  array|bool An array of handlers, or FALSE
 * @since   4.6.0
 * @package Callback
 * @example
 * if ($handlers = callback_handlers('article_saved'))
 * {
 * 	print_r($handlers);
 * }
 */

	function callback_handlers($event, $step = '', $pre = 0, $as_string = true)
	{
		global $plugin_callback;

		$out = array();

		foreach ((array) $plugin_callback as $c)
		{
			if ($c['event'] == $event && (!$c['step'] || $c['step'] == $step) && $c['pre'] == $pre)
			{
				if ($as_string)
				{
					$out[] = callback_tostring($c['function']);
				}
				else
				{
					$out[] = $c['function'];
				}
			}
		}

		if ($out)
		{
			return $out;
		}

		return false;
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

		if ($event !== 'plugin')
		{
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
		$argv = array_slice($argv, 2);
		// Custom user interface, anyone?
		// Signature for called functions:
		// string my_called_func(string $event, string $step, string $default_markup[, mixed $context_data...])
		$ui = call_user_func_array('callback_event', array('event' => $event, 'step' => $element, 'pre' => 0) + $argv);
		// Either plugins provided a user interface, or we render our own.
		return ($ui === '') ? $default : $ui;
	}

/**
 * Gets an attribute from the $theatts global.
 *
 * @param      string $name
 * @param      string $default
 * @return     string
 * @deprecated in 4.2.0
 * @see        lAtts()
 * @package    TagParser
 */

	function getAtt($name, $default=NULL)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'lAtts')), E_USER_NOTICE);
		global $theseatts;
		return isset($theseatts[$name]) ? $theseatts[$name] : $default;
	}

/**
 * Gets an attribute from the given array.
 *
 * @param      array  $atts
 * @param      string $name
 * @param      string $default
 * @return     string
 * @deprecated in 4.2.0
 * @see        lAtts()
 * @package    TagParser
 */

	function gAtt(&$atts, $name, $default=NULL)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'lAtts')), E_USER_NOTICE);
		return isset($atts[$name]) ? $atts[$name] : $default;
	}

/**
 * Merge the second array into the first array.
 *
 * @param   array $pairs The first array
 * @param   array $atts  The second array
 * @param   bool  $warn  If TRUE triggers errors if second array contains values that are not in the first
 * @return  array The two arrays merged
 * @package TagParser
 */

	function lAtts($pairs, $atts, $warn = true)
	{
		global $production_status;

		foreach ($atts as $name => $value)
		{
			if (array_key_exists($name, $pairs))
			{
				$pairs[$name] = $value;
			}
			elseif ($warn and $production_status != 'live')
			{
				trigger_error(gTxt('unknown_attribute', array('{att}' => $name)));
			}
		}

		return ($pairs) ? $pairs : false;
	}

/**
 * Generates All, None and Range selection buttons.
 *
 * @return     string HTML
 * @deprecated in 4.5.0
 * @see        multi_edit()
 * @package    Form
 */

	function select_buttons()
	{
		return
		gTxt('select').
		n.fInput('button','selall',gTxt('all'),'','select all','selectall();').
		n.fInput('button','selnone',gTxt('none'),'','select none','deselectall();').
		n.fInput('button','selrange',gTxt('range'),'','select range','selectrange();');
	}

/**
 * Sanitises a string for use in an article's URL title.
 *
 * @param   string $text  The title or an URL
 * @param   bool   $force Force sanitisation
 * @return  string|null
 * @package URL
 */

	function stripSpace($text, $force = false)
	{
		global $prefs;
		if ($force or !empty($prefs['attach_titles_to_permalinks']))
		{
			$text = trim(sanitizeForUrl($text), '-');
			if ($prefs['permalink_title_format'])
			{
				return (function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text));
			}
			else
			{
				return str_replace('-', '', $text);
			}
		}
	}

/**
 * Sanitises a string for use in a URL.
 *
 * @param  string $text The string
 * @return string
 * @package URL
 */

	function sanitizeForUrl($text)
	{
		$out = callback_event('sanitize_for_url', '', 0, $text);
		if ($out !== '')
		{
			return $out;
		}

		$in = $text;
		// Remove names entities and tags.
		$text = preg_replace("/(^|&\S+;)|(<[^>]*>)/U", "", dumbDown($text));
		// Dashify high-order chars leftover from dumbDown().
		$text = preg_replace("/[\x80-\xff]/", "-", $text);
		// Collapse spaces, minuses, (back-)slashes and non-words.
		$text = preg_replace('/[\s\-\/\\\\]+/', '-', trim(preg_replace('/[^\w\s\-\/\\\\]/', '', $text)));
		// Remove all non-whitelisted characters
		$text = preg_replace("/[^A-Za-z0-9\-_]/", "", $text);
		// Sanitising shouldn't leave us with plain nothing to show.
		// Fall back on percent-encoded URLs as a last resort for RFC 1738 conformance.
		if (empty($text) || $text == '-')
		{
			$text = rawurlencode($in);
		}
		return $text;
	}

/**
 * Sanitises a string for use in a filename.
 *
 * @param   string $text The string
 * @return  string
 * @package File
 */

	function sanitizeForFile($text)
	{
		$out = callback_event('sanitize_for_file', '', 0, $text);
		if ($out !== '')
		{
			return $out;
		}

		// Remove control characters and " * \ : < > ? / |
		$text = preg_replace('/[\x00-\x1f\x22\x2a\x2f\x3a\x3c\x3e\x3f\x5c\x7c\x7f]+/', '', $text);
		// Remove duplicate dots and any leading or trailing dots/spaces.
		$text = preg_replace('/[.]{2,}/', '.', trim($text, '. '));
		return $text;
	}

/**
 * Sanitises a string for use in a page template's name.
 *
 * @param   string $text The string
 * @return  string
 * @package Filter
 * @access  private
 */

	function sanitizeForPage($text)
	{
		$out = callback_event('sanitize_for_page', '', 0, $text);
		if ($out !== '')
		{
			return $out;
		}

		return trim(preg_replace('/[<>&"\']/', '', $text));
	}

/**
 * Transliterates a string to ASCII.
 *
 * This function is used to generate RFC 3986 compliant and pretty ASCII-only URLs.
 *
 * @param   string $str  The string to convert
 * @param   string $lang The language which translation table is used
 * @see     sanitizeForUrl()
 * @package L10n
 */

	function dumbDown($str, $lang = LANG)
	{
		static $array;
		if (empty($array[$lang]))
		{
			$array[$lang] = array( // Nasty, huh?
				'&#192;'=>'A','&Agrave;'=>'A','&#193;'=>'A','&Aacute;'=>'A','&#194;'=>'A','&Acirc;'=>'A',
				'&#195;'=>'A','&Atilde;'=>'A','&#196;'=>'Ae','&Auml;'=>'A','&#197;'=>'A','&Aring;'=>'A',
				'&#198;'=>'Ae','&AElig;'=>'AE',
				'&#256;'=>'A','&#260;'=>'A','&#258;'=>'A',
				'&#199;'=>'C','&Ccedil;'=>'C','&#262;'=>'C','&#268;'=>'C','&#264;'=>'C','&#266;'=>'C',
				'&#270;'=>'D','&#272;'=>'D','&#208;'=>'D','&ETH;'=>'D',
				'&#200;'=>'E','&Egrave;'=>'E','&#201;'=>'E','&Eacute;'=>'E','&#202;'=>'E','&Ecirc;'=>'E','&#203;'=>'E','&Euml;'=>'E',
				'&#274;'=>'E','&#280;'=>'E','&#282;'=>'E','&#276;'=>'E','&#278;'=>'E',
				'&#284;'=>'G','&#286;'=>'G','&#288;'=>'G','&#290;'=>'G',
				'&#292;'=>'H','&#294;'=>'H',
				'&#204;'=>'I','&Igrave;'=>'I','&#205;'=>'I','&Iacute;'=>'I','&#206;'=>'I','&Icirc;'=>'I','&#207;'=>'I','&Iuml;'=>'I',
				'&#298;'=>'I','&#296;'=>'I','&#300;'=>'I','&#302;'=>'I','&#304;'=>'I',
				'&#306;'=>'IJ',
				'&#308;'=>'J',
				'&#310;'=>'K',
				'&#321;'=>'K','&#317;'=>'K','&#313;'=>'K','&#315;'=>'K','&#319;'=>'K',
				'&#209;'=>'N','&Ntilde;'=>'N','&#323;'=>'N','&#327;'=>'N','&#325;'=>'N','&#330;'=>'N',
				'&#210;'=>'O','&Ograve;'=>'O','&#211;'=>'O','&Oacute;'=>'O','&#212;'=>'O','&Ocirc;'=>'O','&#213;'=>'O','&Otilde;'=>'O',
				'&#214;'=>'Oe','&Ouml;'=>'Oe',
				'&#216;'=>'O','&Oslash;'=>'O','&#332;'=>'O','&#336;'=>'O','&#334;'=>'O',
				'&#338;'=>'OE',
				'&#340;'=>'R','&#344;'=>'R','&#342;'=>'R',
				'&#346;'=>'S','&#352;'=>'S','&#350;'=>'S','&#348;'=>'S','&#536;'=>'S',
				'&#356;'=>'T','&#354;'=>'T','&#358;'=>'T','&#538;'=>'T',
				'&#217;'=>'U','&Ugrave;'=>'U','&#218;'=>'U','&Uacute;'=>'U','&#219;'=>'U','&Ucirc;'=>'U',
				'&#220;'=>'Ue','&#362;'=>'U','&Uuml;'=>'Ue',
				'&#366;'=>'U','&#368;'=>'U','&#364;'=>'U','&#360;'=>'U','&#370;'=>'U',
				'&#372;'=>'W',
				'&#221;'=>'Y','&Yacute;'=>'Y','&#374;'=>'Y','&#376;'=>'Y',
				'&#377;'=>'Z','&#381;'=>'Z','&#379;'=>'Z',
				'&#222;'=>'T','&THORN;'=>'T',
				'&#224;'=>'a','&#225;'=>'a','&#226;'=>'a','&#227;'=>'a','&#228;'=>'ae',
				'&auml;'=>'ae',
				'&#229;'=>'a','&#257;'=>'a','&#261;'=>'a','&#259;'=>'a','&aring;'=>'a',
				'&#230;'=>'ae',
				'&#231;'=>'c','&#263;'=>'c','&#269;'=>'c','&#265;'=>'c','&#267;'=>'c',
				'&#271;'=>'d','&#273;'=>'d','&#240;'=>'d',
				'&#232;'=>'e','&#233;'=>'e','&#234;'=>'e','&#235;'=>'e','&#275;'=>'e',
				'&#281;'=>'e','&#283;'=>'e','&#277;'=>'e','&#279;'=>'e',
				'&#402;'=>'f',
				'&#285;'=>'g','&#287;'=>'g','&#289;'=>'g','&#291;'=>'g',
				'&#293;'=>'h','&#295;'=>'h',
				'&#236;'=>'i','&#237;'=>'i','&#238;'=>'i','&#239;'=>'i','&#299;'=>'i',
				'&#297;'=>'i','&#301;'=>'i','&#303;'=>'i','&#305;'=>'i',
				'&#307;'=>'ij',
				'&#309;'=>'j',
				'&#311;'=>'k','&#312;'=>'k',
				'&#322;'=>'l','&#318;'=>'l','&#314;'=>'l','&#316;'=>'l','&#320;'=>'l',
				'&#241;'=>'n','&#324;'=>'n','&#328;'=>'n','&#326;'=>'n','&#329;'=>'n',
				'&#331;'=>'n',
				'&#242;'=>'o','&#243;'=>'o','&#244;'=>'o','&#245;'=>'o','&#246;'=>'oe',
				'&ouml;'=>'oe',
				'&#248;'=>'o','&#333;'=>'o','&#337;'=>'o','&#335;'=>'o',
				'&#339;'=>'oe',
				'&#341;'=>'r','&#345;'=>'r','&#343;'=>'r',
				'&#353;'=>'s',
				'&#249;'=>'u','&#250;'=>'u','&#251;'=>'u','&#252;'=>'ue','&#363;'=>'u',
				'&uuml;'=>'ue',
				'&#367;'=>'u','&#369;'=>'u','&#365;'=>'u','&#361;'=>'u','&#371;'=>'u',
				'&#373;'=>'w',
				'&#253;'=>'y','&#255;'=>'y','&#375;'=>'y',
				'&#382;'=>'z','&#380;'=>'z','&#378;'=>'z',
				'&#254;'=>'t',
				'&#223;'=>'ss',
				'&#383;'=>'ss',
				'&agrave;'=>'a','&aacute;'=>'a','&acirc;'=>'a','&atilde;'=>'a','&auml;'=>'ae',
				'&aring;'=>'a','&aelig;'=>'ae','&ccedil;'=>'c','&eth;'=>'d',
				'&egrave;'=>'e','&eacute;'=>'e','&ecirc;'=>'e','&euml;'=>'e',
				'&igrave;'=>'i','&iacute;'=>'i','&icirc;'=>'i','&iuml;'=>'i',
				'&ntilde;'=>'n',
				'&ograve;'=>'o','&oacute;'=>'o','&ocirc;'=>'o','&otilde;'=>'o','&ouml;'=>'oe',
				'&oslash;'=>'o',
				'&ugrave;'=>'u','&uacute;'=>'u','&ucirc;'=>'u','&uuml;'=>'ue',
				'&yacute;'=>'y','&yuml;'=>'y',
				'&thorn;'=>'t',
				'&szlig;'=>'ss',
			);


			if (is_file(txpath.'/lib/i18n-ascii.txt'))
			{
				$i18n = parse_ini_file(txpath.'/lib/i18n-ascii.txt', true);
				// Load the global map.
				if (isset($i18n['default']) && is_array($i18n['default']))
				{
					$array[$lang] = array_merge($array[$lang], $i18n['default']);
					// Base language overrides: 'de-AT' applies the 'de' section.
					if (preg_match('/([a-zA-Z]+)-.+/', $lang, $m))
					{
						if (isset($i18n[$m[1]]) && is_array($i18n[$m[1]]))
						{
							$array[$lang] = array_merge($array[$lang], $i18n[$m[1]]);
						}
					}
					// Regional language overrides: 'de-AT' applies the 'de-AT' section.
					if (isset($i18n[$lang]) && is_array($i18n[$lang]))
					{
						$array[$lang] = array_merge($array[$lang], $i18n[$lang]);
					}
				}
				// Load an old file (no sections) just in case.
				else
				{
					$array[$lang] = array_merge($array[$lang], $i18n);
				}
			}
		}

		return strtr($str, $array[$lang]);
	}

/**
 * Cleans a URL.
 *
 * @param   string $url The URL
 * @return  string
 * @access  private
 * @package URL
 */

	function clean_url($url)
	{
		return preg_replace("/\"|'|(?:\s.*$)/", '', $url);
	}

/**
 * Replace the last space with a &#160; non-breaking space.
 *
 * @param   string $str The string
 * @return  string
 */

	function noWidow($str)
	{
		if (REGEXP_UTF8 == 1)
		{
			return preg_replace('@[ ]+([[:punct:]]?[\p{L}\p{N}\p{Pc}]+[[:punct:]]?)$@u', '&#160;$1', rtrim($str));
		}
		return preg_replace('@[ ]+([[:punct:]]?\w+[[:punct:]]?)$@', '&#160;$1', rtrim($str));
	}

/**
 * Checks if an IP is on a spam blacklist.
 *
 * @param   string  $ip     The IP address
 * @param   string  $checks The checked lists. Defaults to $prefs['spam_blacklists']
 * @return  string|bool     The lists the IP is on or FALSE
 * @package Comment
 * @example
 * if (is_blacklisted('127.0.0.1'))
 * {
 * 	echo "'127.0.0.1' is blacklisted.";
 * }
 */

	function is_blacklisted($ip, $checks = '')
	{
		global $prefs;

		if (!$checks)
		{
			$checks = do_list($prefs['spam_blacklists']);
		}

		$rip = join('.', array_reverse(explode('.', $ip)));

		foreach ($checks as $a)
		{
			$parts = explode(':', $a, 2);
			$rbl   = $parts[0];

			if (isset($parts[1]))
			{
				foreach (explode(':', $parts[1]) as $code)
				{
					$codes[] = strpos($code, '.') ? $code : '127.0.0.'.$code;
				}
			}

			$hosts = $rbl ? @gethostbynamel($rip.'.'.trim($rbl, '. ').'.') : false;

			if ($hosts and (!isset($codes) or array_intersect($hosts, $codes)))
			{
				$listed[] = $rbl;
			}
		}

		return (!empty($listed)) ? join(', ', $listed) : false;
	}

/**
 * Checks if the user is authenticated on the public-side.
 *
 * @param   string     $user The checked username. If not provided, any user is accepted
 * @return  array|bool An array containing details about the user; name, RealName, email, privs. FALSE when the user hasn't authenticated.
 * @package User
 * @example
 * if ($user = is_logged_in())
 * {
 * 	echo "Logged in as {$user['RealName']}";
 * }
 */

	function is_logged_in($user = '')
	{
		$name = substr(cs('txp_login_public'), 10);

		if (!strlen($name) or strlen($user) and $user !== $name)
		{
			return false;
		}

		$rs = safe_row('nonce, name, RealName, email, privs', 'txp_users', "name = '".doSlash($name)."'");

		if ($rs and substr(md5($rs['nonce']), -10) === substr(cs('txp_login_public'), 0, 10))
		{
			unset($rs['nonce']);
			return $rs;
		}
		else
		{
			return false;
		}
	}

/**
 * Updates the path to the site.
 *
 * @param   string $here The path
 * @access  private
 * @package Pref
 */

	function updateSitePath($here)
	{
		set_pref('path_to_site', $here, 'publish', PREF_HIDDEN);
	}

/**
 * Converts Textpattern tag's attribute list to an array.
 *
 * @param   string  $text The attribute list, e.g. foobar="1" barfoo="0"
 * @return  array   Array of attributes
 * @access  private
 * @package TagParser
 */

	function splat($text)
	{
		$atts  = array();

		if (preg_match_all('@(\w+)\s*=\s*(?:"((?:[^"]|"")*)"|\'((?:[^\']|\'\')*)\'|([^\s\'"/>]+))@s', $text, $match, PREG_SET_ORDER))
		{
			foreach ($match as $m)
			{
				switch (count($m))
				{
					case 3 :
						$val = str_replace('""', '"', $m[2]);
						break;
					case 4 :
						$val = str_replace("''", "'", $m[3]);

						if (strpos($m[3], '<txp:') !== false)
						{
							trace_add("[attribute '".$m[1]."']");
							$val = parse($val);
							trace_add("[/attribute]");
						}

						break;
					case 5 :
						$val = $m[4];
						trigger_error(gTxt('attribute_values_must_be_quoted'), E_USER_WARNING);
						break;
				}

				$atts[strtolower($m[1])] = $val;
			}

		}

		return $atts;
	}

/**
 * Renders peak memory usage in a HTML comment.
 *
 * @param   string      $message  The message associated with the logged memory usage
 * @param   bool        $returnit Return the usage wrapped in a HTML comment
 * @return  null|string HTML
 * @package Debug
 */

	function maxMemUsage($message = 'none', $returnit = false)
	{
		$memory = get_txp_memory_usage($message);

		if ($returnit)
		{
			if ($memory)
			{
				return n.comment(sprintf('Memory: %sKb, %s',
					ceil($memory[0]/1024), $memory[1]));
			}
			else
			{
				return n.comment('Memory: no info available');
			}
		}
	}

/**
 * Gets Textpattern peak memory usage.
 *
 * Peak memory usage is checked, calculated and logged each
 * time this function is invoked.
 *
 * @param   string     $message The message associated with the logged memory usage
 * @return  array|bool An array consisting of peak usage and its message, or FALSE on error
 * @since   4.6.0
 * @package Debug
 * @example
 * if ($memory = get_txp_memory_usage())
 * {
 * 	list($usage, $message) = $memory;
 * 	echo "Memory: {$usage}, {$message}.";
 * }
 */

	function get_txp_memory_usage($message = 'none')
	{
		static $memory_top = 0;
		static $memory_message = '';

		if (!is_callable('memory_get_usage'))
		{
			return false;
		}

		$memory_now = memory_get_usage();

		if ($memory_now > $memory_top)
		{
			$memory_top = $memory_now;
			$memory_message = $message;
		}

		return array($memory_top, $memory_message);
	}

/**
 * Replaces CR and LF with spaces, and drops NULL bytes.
 *
 * This function is used for sanitising email headers.
 *
 * @param   string $str The string
 * @return  string
 * @package Email
 */

	function strip_rn($str)
	{
		return str_replace(array("\r\n", "\r", "\n"), ' ', deNull($str));
	}

/**
 * Validates a string as an email address.
 *
 * @param   string $address The email address
 * @return  bool
 * @package Email
 * @example
 * if (is_valid_email('john.doe@example.com'))
 * {
 * 	echo "'john.doe@example.com' validates.";
 * }
 */

	function is_valid_email($address)
	{
		return (bool) filter_var($address, FILTER_VALIDATE_EMAIL);
	}

/**
 * Sends an email message as the currently logged in user.
 *
 * @param   string $to_address The receiver
 * @param   string $subject    The subject
 * @param   string $body       The message
 * @param   string $reply_to   The reply to address
 * @return  bool   Returns FALSE when sending failed
 * @see     send_email()
 * @package Email
 * @example
 * if (txpMail('john.doe@example.com', 'Subject', 'Some message'))
 * {
 * 	echo "Email sent to 'john.doe@example.com'.";
 * }
 */

	function txpMail($to_address, $subject, $body, $reply_to = null)
	{
		global $txp_user, $prefs;

		// Send the email as the currently logged in user.
		if ($txp_user)
		{
			$sender = safe_row(
				'RealName, email',
				'txp_users',
				"name = '".doSlash($txp_user)."'"
			);

			if ($sender && is_valid_email($prefs['publisher_email']))
			{
				$sender['email'] = $prefs['publisher_email'];
			}
		}
		// If not logged in, the receiver is the sender.
		else
		{
			$sender = safe_row(
				'RealName, email',
				'txp_users',
				"email = '".doSlash($to_address)."'"
			);
		}

		if ($sender)
		{
			extract($sender);
			return send_email(array($email => $RealName), (string) $to_address, $subject, $body, (string) $reply_to);
		}

		return false;
	}

/**
 * Sends an email.
 *
 * If the given arguments validate, the function fires
 * a 'mail.handler' callback event. This event can be used
 * replace the default mail handler.
 *
 * @param   string|array $from     Sender
 * @param   string|array $send_to  The receiver
 * @param   string       $subject  The subject
 * @param   string       $body     The message
 * @param   string|array $reply_to The reply address
 * @param   string|array $cc       Carbon copy
 * @param   string|array $bcc      Blind carbon copy
 * @param   array        $headers  An array of additional email headers
 * @return  bool         Returns FALSE when sending failed
 * @since   4.6.0
 * @package Email
 * @example
 * if (send_email(array('john.doe@example.com' => 'John Doe'), 'receiver@example.com', 'Hello world!', 'Some message.'))
 * {
 * 	echo "Email sent to 'receiver@example.com'."; 
 * }
 */

	function send_email($from, $send_to, $subject, $body, $reply_to = array(), $cc = array(), $bcc = array(), $headers = array())
	{
		if (is_disabled('mail') && !has_handler('mail.handler'))
		{
			return false;
		}

		if (!is_array($headers) || !$from || !$send_to)
		{
			return false;
		}

		$arguments = compact(
			'from',
			'send_to',
			'subject',
			'body',
			'reply_to',
			'cc',
			'bcc',
			'headers'
		);

		if (get_pref('override_emailcharset') && is_callable('utf8_decode'))
		{
			$charset = 'ISO-8859-1';
			$subject = utf8_decode($subject);
			$body = utf8_decode($body);
		}
		else
		{
			$charset = 'UTF-8';
		}

		$subject = encode_mailheader(strip_rn($subject), 'text');

		foreach (compact('from', 'send_to', 'reply_to', 'cc', 'bcc') as $field => $value)
		{
			if (!$value)
			{
				$$field = null;
				continue;
			}

			$out = array();

			foreach ((array) $value as $email => $name)
			{
				if (is_int($email))
				{
					$email = $name;
					$name = '';
				}

				if (is_valid_email($email))
				{
					if ($charset == 'UTF-8')
					{
						$name = utf8_decode($name);
					}

					$out[] = trim(encode_mailheader(strip_rn($name), 'phrase').' <'.$email.'>');
				}
				else
				{
					return false;
				}
			}

			$$field = join(', ', $out);
		}

		$sep = IS_WIN ? "\r\n" : "\n";

		$body = str_replace("\r\n", "\n", $body);
		$body = str_replace("\r", "\n", $body);
		$body = str_replace("\n", $sep, $body);
		$body = deNull($body);

		$envelope = array();
		$envelope['From'] = $from;

		if ($cc)
		{
			$envelope['Cc'] = $cc;
		}

		if ($bcc)
		{
			$envelope['Bcc'] = $bbc;
		}

		if ($reply_to)
		{
			$envelope['Reply-to'] = $reply_to;
		}

		if (empty($headers['X-Mailer']))
		{
			$envelope['X-Mailer'] = 'Textpattern';
		}

		$envelope['Content-Transfer-Encoding'] = '8bit';
		$envelope['Content-Type'] = 'text/plain; charset="'.$charset.'"';

		foreach ($envelope as $n => &$v)
		{
			$v = $n.': '.$v;
		}

		foreach ($headers as $field => $value)
		{
			if (!isset($envelope[$field]) && preg_match('/[A-z0-9-_]/i', $value))
			{
				$envelope[] = $field.': '.encode_mailheader(strip_rn($value), 'phrase');
			}
		}

		$headers = join($sep, $envelope).$sep;

		if (is_valid_email(get_pref('smtp_from')) && IS_WIN)
		{
			ini_set('sendmail_from', get_pref('smtp_from'));
		}

		if (has_handler('mail.handler'))
		{
			return callback_event('mail.handler', '', 0, $arguments, $send_to, $subject, $body, $headers) !== '';
		}

		if (is_valid_email(get_pref('smtp_from')) && !IS_WIN && !ini_get('safe_mode'))
		{
			return mail($send_to, $subject, $body, $headers, '-f'.get_pref('smtp_from'));
		}

		return mail($send_to, $subject, $body, $headers);
	}

/**
 * Encodes a string for use in an email header.
 *
 * @param   string $string The string
 * @param   string $type   The type of header, either "text" or "phrase"
 * @return  string
 * @package Email
 */

	function encode_mailheader($string, $type)
	{
		global $prefs;
		if (strpos($string, '=?') === false and !preg_match('/[\x00-\x1F\x7F-\xFF]/', $string))
		{
			if ("phrase" == $type)
			{
				if (preg_match('/[][()<>@,;:".\x5C]/', $string))
				{
					$string = '"'. strtr($string, array("\\" => "\\\\", '"' => '\"')) . '"';
				}
			}
			elseif ("text" != $type)
			{
				trigger_error( 'Unknown encode_mailheader type', E_USER_WARNING);
			}
			return $string;
		}

		if ($prefs['override_emailcharset'] and is_callable('utf8_decode'))
		{
			$start = '=?ISO-8859-1?B?';
			$pcre  = '/.{1,42}/s';
		}
		else
		{
			$start = '=?UTF-8?B?';
			$pcre  = '/.{1,45}(?=[\x00-\x7F\xC0-\xFF]|$)/s';
		}

		$end = '?=';
		$sep = IS_WIN ? "\r\n" : "\n";
		preg_match_all($pcre, $string, $matches);
		return $start . join($end.$sep.' '.$start, array_map('base64_encode', $matches[0])) . $end;
	}

/**
 * Converts an email address into unicode entities.
 *
 * @param   string $txt The email address
 * @return  string Encoded email address
 * @package Email
 */

	function eE($txt)
	{
		$ent = array();

		for ($i = 0; $i < strlen($txt); $i++)
		{
			$ent[] = "&#".ord(substr($txt, $i, 1)).";";
		}

		return join('', $ent);
	}

/**
 * Strips PHP tags from a string.
 *
 * @param  string $in The input
 * @return string
 */

	function stripPHP($in)
	{
		return preg_replace("/".chr(60)."\?(?:php)?|\?".chr(62)."/i", '', $in);
	}

/**
 * Gets a HTML select field containing all categories, or sub-categories.
 *
 * @param   string $name Return specified parent category's sub-categories
 * @param   string $cat  The selected category option
 * @param   string $id   The HTML ID
 * @return  string|bool  HTML select field or FALSE on error
 * @package Form
 */

 	function event_category_popup($name, $cat = '', $id = '')
	{
		$arr = array('');
		$rs = getTree('root', $name);

		if ($rs)
		{
			return treeSelectInput('category', $rs, $cat, $id);
		}

		return false;
	}

/**
 * Updates a list's per page number.
 *
 * Gets the per page number from a "qty" HTTP POST/GET parameter and
 * creates a user-specific preference value "$name_list_pageby".
 *
 * @param string $name The name of the list
 */

 	function event_change_pageby($name)
	{
		global $event;
		$qty = gps('qty');
		assert_int($qty);
		$pageby = $name.'_list_pageby';
		$GLOBALS[$pageby] = $qty;

		set_pref($pageby, $qty, $event, PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);

		return;
	}

/**
 * Generates a multi-edit widget.
 *
 * @param      string $name
 * @param      array  $methods
 * @param      int    $page
 * @param      string $sort
 * @param      string $dir
 * @param      string $crit
 * @param      string $search_method
 * @deprecated in 4.5.0
 * @see        multi_edit()
 * @package    Form
 */

	function event_multiedit_form($name, $methods = null, $page, $sort, $dir, $crit, $search_method)
	{
		$method = ps('edit_method');

		if ($methods === NULL)
		{
			$methods = array(
				'delete' => gTxt('delete')
			);
		}

		return '<label for="withselected">'.gTxt('with_selected').'</label>'.
			n.selectInput('edit_method', $methods, $method, 1, ' id="withselected" onchange="poweredit(this); return false;"').
			n.eInput($name).
			n.sInput($name.'_multi_edit').
			n.hInput('page', $page).
			( $sort ? n.hInput('sort', $sort).n.hInput('dir', $dir) : '' ).
			( ($crit != '') ? n.hInput('crit', $crit).n.hInput('search_method', $search_method) : '' ).
			n.fInput('submit', '', gTxt('go'));
	}

/**
 * Generic multi-edit form's edit handler shared across panels.
 *
 * Receives an action from a multi-edit form and runs it
 * in the given database table.
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

		if ($selected)
		{
			if ($method == 'delete')
			{
				foreach ($selected as $id)
				{
					$id = assert_int($id);

					if (safe_delete($table, "$id_key = $id"))
					{
						$ids[] = $id;
					}
				}

				return join(', ', $ids);
			}
		}

		return '';
	}

/**
 * Gets a "since days ago" date format from a given UNIX timestamp.
 *
 * @param   int    $stamp UNIX timestamp
 * @return  string "n days ago"
 * @package DateTime
 */

	function since($stamp)
	{
		$diff = (time() - $stamp);

		if ($diff <= 3600)
		{
			$mins = round($diff / 60);
			$since = ($mins <= 1) ? ($mins==1) ? '1 '.gTxt('minute') : gTxt('a_few_seconds') : "$mins ".gTxt('minutes');
		}
		else if (($diff <= 86400) && ($diff > 3600))
		{
			$hours = round($diff / 3600);
			$since = ($hours <= 1) ? '1 '.gTxt('hour') : "$hours ".gTxt('hours');
		}
		else if ($diff >= 86400)
		{
			$days = round($diff / 86400);
			$since = ($days <= 1) ? "1 ".gTxt('day') : "$days ".gTxt('days');
		}

		return $since.' '.gTxt('ago'); // sorry, this needs to be hacked until a truly multilingual version is done
	}

/**
 * Calculates a timezone offset.
 *
 * This function calculates the offset between the server local time
 * and the user's selected timezone at a given point in time.
 *
 * @param   int $timestamp The timestamp. Defaults to time()
 * @return  int The offset in seconds
 * @package DateTime
 */

	function tz_offset($timestamp = null)
	{
		global $gmtoffset, $timezone_key;

		if (is_null($timestamp))
		{
			$timestamp = time();
		}

		extract(getdate($timestamp));
		$serveroffset = gmmktime($hours, $minutes, 0, $mon, $mday, $year) - mktime($hours, $minutes, 0, $mon, $mday, $year);

		$real_dst = timezone::is_dst($timestamp, $timezone_key);
		return $gmtoffset - $serveroffset + ($real_dst ? 3600 : 0);
	}

/**
 * Formats a time.
 *
 * This function respects the locale and local timezone,
 * and makes sure the output string is encoded in UTF-8.
 *
 * @param   string $format          The date format
 * @param   int    $time            UNIX timestamp. Defaults to time()
 * @param   bool   $gmt             Return GMT time
 * @param   string $override_locale Override the locale
 * @return  string Formatted date
 * @package DateTime
 * @example
 * echo safe_strftime('w3cdtf');
 */

	function safe_strftime($format, $time = '', $gmt = false, $override_locale = '')
	{
		global $locale;
		$old_locale = $locale;

		if (!$time)
		{
			$time = time();
		}

		// We could add some other formats here.
		if ($format == 'iso8601' or $format == 'w3cdtf')
		{
			$format = '%Y-%m-%dT%H:%M:%SZ';
			$gmt = true;
		}
		elseif ($format == 'rfc822')
		{
			$format = '%a, %d %b %Y %H:%M:%S GMT';
			$gmt = true;
			$override_locale = 'en-gb';
		}

		if ($override_locale)
		{
			getlocale($override_locale);
		}

		if ($format == 'since')
		{
			$str = since($time);
		}
		elseif ($gmt)
		{
			$str = gmstrftime($format, $time);
		}
		else
		{
			$str = strftime($format, $time + tz_offset($time));
		}

		@list($lang, $charset) = explode('.', $locale);

		if (empty($charset))
		{
			$charset = 'ISO-8859-1';
		}
		elseif (IS_WIN and is_numeric($charset))
		{
			$charset = 'Windows-'.$charset;
		}

		if ($charset != 'UTF-8' and $format != 'since')
		{
			$new = '';
			if (is_callable('iconv'))
			{
				$new = @iconv($charset, 'UTF-8', $str);
			}

			if ($new)
			{
				$str = $new;
			}
			elseif (is_callable('utf8_encode'))
			{
				$str = utf8_encode($str);
			}
		}

		// Revert to the old locale.
		if ($override_locale)
		{
			$locale = setlocale(LC_ALL, $old_locale);
		}

		return $str;
	}

/**
 * Converts a time string from the Textpattern timezone to GMT.
 *
 * @param   string $time_str The time string
 * @return  int    UNIX timestamp
 * @package DateTime
 */

	function safe_strtotime($time_str)
	{
		$ts = strtotime($time_str);
		return strtotime($time_str, time() + tz_offset($ts)) - tz_offset($ts);
	}

/**
 * Generic error handler.
 *
 * @param   int    $errno
 * @param   string $errstr
 * @param   string $errfile
 * @param   int    $errline
 * @access  private
 * @package Debug
 */

	function myErrorHandler($errno, $errstr, $errfile, $errline)
	{
		if (!error_reporting())
		{
			return;
		}

		echo '<pre>'.n.n."$errno: $errstr in $errfile at line $errline\n";

		if (is_callable('debug_backtrace'))
		{
			echo "Backtrace:\n";
			$trace = debug_backtrace();

			foreach ($trace as $ent)
			{
				if (isset($ent['file']))
				{
					echo $ent['file'].':';
				}

				if (isset($ent['function']))
				{
					echo $ent['function'].'(';
					if (isset($ent['args']))
					{
						$args='';
						foreach ($ent['args'] as $arg)
						{
							$args.=$arg.',';
						}
						echo rtrim($args,',');
					}
					echo ') ';
				}

				if (isset($ent['line']))
				{
					echo 'at line '.$ent['line'].' ';
				}

				if (isset($ent['file']))
				{
					echo 'in '.$ent['file'];
				}
				echo "\n";
			}
		}
		echo "</pre>";
	}

/**
 * Verifies temporary directory.
 *
 * This function verifies that the temporary directory is writeable.
 *
 * @param   string $dir The directory to check
 * @return  bool|null NULL on error, TRUE on success
 * @package Debug
 */

	function find_temp_dir()
	{
		global $path_to_site, $img_dir;

		if (IS_WIN)
		{
			$guess = array(
				txpath.DS.'tmp',
				getenv('TMP'),
				getenv('TEMP'),
				getenv('SystemRoot').DS.'Temp',
				'C:'.DS.'Temp',
				$path_to_site.DS.$img_dir
			);

			foreach ($guess as $k => $v)
			{
				if (empty($v))
				{
					unset($guess[$k]);
				}
			}
		}
		else
		{
			$guess = array(
				txpath.DS.'tmp',
				'',
				DS.'tmp',
				$path_to_site.DS.$img_dir
			);
		}

		foreach ($guess as $dir)
		{
			$tf = @tempnam($dir, 'txp_');
			if ($tf)
			{
				$tf = realpath($tf);
			}
			if ($tf and file_exists($tf))
			{
				unlink($tf);
				return dirname($tf);
			}
		}

		return false;
	}

/**
 * Moves an uploaded file and returns its new location.
 *
 * @param   string      $f    The filename of the uploaded file
 * @param   string      $dest The destination of the moved file. If omitted, the file is moved to the temp directory
 * @return  string|bool The new path or FALSE on error
 * @package File
 */

	function get_uploaded_file($f, $dest = '')
	{
		global $tempdir;

		if (!is_uploaded_file($f))
		{
			return false;
		}

		if ($dest)
		{
			$newfile = $dest;
		}
		else
		{
			$newfile = tempnam($tempdir, 'txp_');
			if (!$newfile)
			{
				return false;
			}
		}

		// $newfile is created by tempnam(), but move_uploaded_file will overwrite it.
		if (move_uploaded_file($f, $newfile))
		{
			return $newfile;
		}
	}

/**
 * Sets error reporting level.
 *
 * @param   string $level The level. Either "debug", "live" or "testing"
 * @package Debug
 */

	function set_error_level($level)
	{
		if ($level == 'debug')
		{
			error_reporting(E_ALL | E_STRICT);
		}
		elseif ($level == 'live')
		{
			// Don't show errors on screen.
			$suppress = E_NOTICE | E_USER_NOTICE | E_WARNING | E_STRICT | (defined('E_DEPRECATED') ? E_DEPRECATED : 0);
			error_reporting(E_ALL ^ $suppress);
			@ini_set("display_errors","1");
		}
		else
		{
			// Default is 'testing': display everything except notices.
			error_reporting((E_ALL | E_STRICT) ^ (E_NOTICE | E_USER_NOTICE));
		}
	}

/**
 * Moves a file.
 *
 * @param   string $f    The file to move
 * @param   string $dest The destination
 * @return  bool
 * @package File
 */

	function shift_uploaded_file($f, $dest)
	{
		if (@rename($f, $dest))
		{
			return true;
		}

		if (@copy($f, $dest))
		{
			unlink($f);
			return true;
		}
	}

/**
 * Translates upload error code to a localised error message.
 *
 * @param   int    $err_code The error code
 * @return  string The $err_code as a message
 * @package File
 */

	function upload_get_errormsg($err_code)
	{
		$msg = '';
		switch ($err_code)
		{
			// Value: 0; There is no error, the file uploaded with success.
			case UPLOAD_ERR_OK :
				$msg = '';
				break;
			// Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
			case UPLOAD_ERR_INI_SIZE :
				$msg = gTxt('upload_err_ini_size');
				break;
			// Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
			case UPLOAD_ERR_FORM_SIZE  :
				$msg = gTxt('upload_err_form_size');
				break;
			// Value: 3; The uploaded file was only partially uploaded.
			case UPLOAD_ERR_PARTIAL :
				$msg = gTxt('upload_err_partial');
				break;
			// Value: 4; No file was uploaded.
			case UPLOAD_ERR_NO_FILE :
				$msg = gTxt('upload_err_no_file');
				break;
			// Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
			case UPLOAD_ERR_NO_TMP_DIR :
				$msg = gTxt('upload_err_tmp_dir');
				break;
			// Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.
			case UPLOAD_ERR_CANT_WRITE :
				$msg = gTxt('upload_err_cant_write');
				break;
			// Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0.
			case UPLOAD_ERR_EXTENSION :
				$msg = gTxt('upload_err_extension');
				break;
		}
		return $msg;
	}

/**
 * Formats a file size.
 *
 * @param   int    $bytes    Size in bytes
 * @param   int    $decimals Number of decimals
 * @param   string $format   The format the size is represented
 * @return  string Formatted file size
 * @package File
 * @example
 * echo format_filesize(168642);
 */

	function format_filesize($bytes, $decimals = 2, $format = '')
	{
		$units = array('b', 'k', 'm', 'g', 't', 'p', 'e', 'z', 'y');

		if (in_array($format, $units))
		{
			$pow = array_search($format, $units);
		}
		else
		{
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);
		}

		$bytes /= pow(1024, $pow);

		$separators = localeconv();
		$sep_dec = isset($separators['decimal_point']) ? $separators['decimal_point'] : '.';
		$sep_thous = isset($separators['thousands_sep']) ? $separators['thousands_sep'] : ',';

		return number_format($bytes, $decimals, $sep_dec, $sep_thous) . gTxt('units_' . $units[$pow]);
	}

/**
 * Gets a file download as an array.
 *
 * @param   string     $where SQL where clause
 * @return  array|bool An array of files, or FALSE on failure
 * @package File
 * @example
 * if ($file = fileDownloadFetchInfo('id = 1'))
 * {
 * 	print_r($file);
 * }
 */

	function fileDownloadFetchInfo($where)
	{
		$rs = safe_row('*', 'txp_file', $where);

		if ($rs)
		{
			return file_download_format_info($rs);
		}

		return false;
	}

/**
 * Formats file download info.
 *
 * Takes a data array generated by fileDownloadFetchInfo()
 * and formats the contents.
 *
 * @param   array $file The file info to format
 * @return  array Formatted file info
 * @access  private
 * @package File
 */

	function file_download_format_info($file)
	{
		if (($unix_ts = @strtotime($file['created'])) > 0)
		{
			$file['created'] = $unix_ts;
		}

		if (($unix_ts = @strtotime($file['modified'])) > 0)
		{
			$file['modified'] = $unix_ts;
		}

		return $file;
	}

/**
 * Formats file download's modification and creation timestamps.
 *
 * This function is used by file_download tags.
 *
 * @param   array $params
 * @return  string
 * @access  private
 * @package File
 */

	function fileDownloadFormatTime($params)
	{
		global $prefs;

		extract(lAtts(array(
			'ftime'  => '',
			'format' => ''
		), $params));

		if (!empty($ftime))
		{
			if ($format)
			{
				return safe_strftime($format, $ftime);
			}

			return safe_strftime($prefs['archive_dateformat'], $ftime);
		}

		return '';
	}

/**
 * Checks if the system is Windows.
 *
 * Exists for backwards compatibility.
 *
 * @return     bool
 * @deprecated in 4.3.0
 * @see        IS_WIN
 * @package    System
 */

	function is_windows()
	{
		return IS_WIN;
	}

/**
 * Checks if PHP is run as CGI.
 *
 * Exists for backwards compatibility.
 *
 * @return     bool
 * @deprecated in 4.3.0
 * @see        IS_CGI
 * @package    System
 */

	function is_cgi()
	{
		return IS_CGI;
	}

/**
 * Checks if PHP is run as Apache module.
 *
 * Exists for backwards compatibility.
 *
 * @return     bool
 * @deprecated in 4.3.0
 * @see        IS_APACHE
 * @package    System
 */

	function is_mod_php()
	{
		return IS_APACHE;
	}

/**
 * Checks if a function is disabled.
 *
 * @param   string $function The function name
 * @return  bool   TRUE if the function is disabled
 * @package System
 * @example
 * if (is_disabled('mail'))
 * {
 * 	echo "'mail' function is disabled.";
 * }
 */

	function is_disabled($function)
	{
		static $disabled;

		if (!isset($disabled))
		{
			$disabled = do_list(ini_get('disable_functions'));
		}

		return in_array($function, $disabled);
	}

/**
 * Joins two strings to form a single filesystem path.
 *
 * @param   string $base The base directory
 * @param   string $path The second path, a relative filename
 * @return  string A path to a file
 * @package File
 */

	function build_file_path($base, $path)
	{
		$base = rtrim($base, '/\\');
		$path = ltrim($path, '/\\');

		return $base.DIRECTORY_SEPARATOR.$path;
	}

/**
 * Gets a user's real name.
 *
 * @param   string $name The username
 * @return  string A real name, or username if empty
 * @package User
 */

	function get_author_name($name)
	{
		static $authors = array();

		if (isset($authors[$name]))
		{
			return $authors[$name];
		}

		$realname = fetch('RealName', 'txp_users', 'name', $name);
		$authors[$name] = $realname;
		return ($realname) ? $realname : $name;
	}

/**
 * Gets a user's email address.
 *
 * @param   string $name The username
 * @return  string
 * @package User
 */

	function get_author_email($name)
	{
		static $authors = array();

		if (isset($authors[$name]))
		{
			return $authors[$name];
		}

		$email = fetch('email', 'txp_users', 'name', $name);
		$authors[$name] = $email;
		return $email;
	}

/**
 * Checks if a database table contains items just from one user.
 *
 * @param   string $table The database table
 * @param   string $col   The column
 * @return  bool
 * @package User
 * @example
 * if (has_single_author('textpattern', 'AuthorID'))
 * {
 * 	echo "'textpattern' table has only content from one author.";
 * }
 */

	function has_single_author($table, $col = 'author')
	{
		return (safe_field('COUNT(name)', 'txp_users', '1=1') <= 1) &&
			(safe_field('COUNT(DISTINCT('.doSlash($col).'))', doSlash($table), '1=1') <= 1);
	}

/**
 * Validates a string as a username.
 *
 * @param   string $name The username
 * @return  bool   TRUE if the string valid
 * @since   4.6.0
 * @package User
 * @example
 * if (is_valid_username('john'))
 * {
 * 	echo "'john' is a valid username.";
 * }
 */

	function is_valid_username($name)
	{
		if (function_exists('mb_strlen'))
		{
			$length = mb_strlen($name, '8bit');
		}
		else
		{
			$length = strlen($name);
		}

		return $name && !preg_match('/^\s|[,\'"<>]|\s$/u', $name) && $length <= 64;
	}

/**
 * Assigns assets to a different user.
 *
 * This function changes the owner of user's assets. It will
 * move articles, files, images and links from
 * one user to another.
 *
 * This function should be run when a user's permissions are taken
 * away, username is renamed or the user is removed from the site.
 *
 * Affected database tables can be extended with a 'user.assign_assets > columns'
 * callback event. Callback functions get passed three arguments: '$event',
 * '$step' and '$columns'. The third parameter contains a reference to an
 * array of 'table => column' pairs.
 *
 * On a successful run, this function will trigger a 'user.assign_assets > done'
 * callback event.
 *
 * @param   string|array $owner     List of current owners
 * @param   string       $new_owner The new owner
 * @return  bool         FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (assign_user_assets(array('user1', 'user2'), 'new_owner'))
 * {
 * 	echo "Assigned assets by 'user1' and 'user2' to 'new_owner'.";
 * }
 */

	function assign_user_assets($owner, $new_owner)
	{
		static $columns = null;

		if (!$owner || !user_exists($new_owner))
		{
			return false;
		}

		if ($columns === null)
		{
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

		foreach ($columns as $table => $column)
		{
			if (safe_update($table, "$column = '$assign'", "$column in ($names)") === false)
			{
				return false;
			}
		}

		callback_event('user.assign_assets', 'done', 0, compact('owner', 'new_owner', 'columns'));

		return true;
	}

/**
 * Creates a user account.
 *
 * On a successful run, this function will trigger
 * a 'user.create > done' callback event.
 *
 * @param   string $name     The login name
 * @param   string $email    The email address
 * @param   string $password The password
 * @param   string $realname The real name
 * @param   int    $group    The user group
 * @return  bool   FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (create_user('john', 'john.doe@example.com', 'DancingWalrus', 'John Doe', 1))
 * {
 * 	echo "User 'john' created.";
 * }
 */

	function create_user($name, $email, $password, $realname = '', $group = 0)
	{
		$levels = get_groups();

		if (!$password || !is_valid_username($name) || !is_valid_email($email) || user_exists($name) || !isset($levels[$group]))
		{
			return false;
		}

		$nonce = md5(uniqid(mt_rand(), true));
		$hash = txp_hash_password($password);

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
		)
		{
			return false;
		}

		callback_event('user.create', 'done', 0, compact('name', 'email', 'password', 'realname', 'group', 'nonce', 'hash'));
		return true;
	}

/**
 * Updates a user.
 *
 * This function updates a user account's properties.
 * The $user argument is used for selecting the updated
 * user, and rest of the arguments new values.
 * Use NULL to omit an argument.
 *
 * On a successful run, this function will trigger
 * a 'user.update > done' callback event.
 *
 * @param   string      $user     The updated user
 * @param   string|null $email    The email address
 * @param   string|null $realname The real name
 * @param   array|null  $meta     Additional meta fields
 * @return  bool   FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (update_user('login', null, 'John Doe'))
 * {
 * 	echo "Updated user's real name.";
 * }
 */

	function update_user($user, $email = null, $realname = null, $meta = array())
	{
		if (($email !== null && !is_valid_email($email)) || !user_exists($user))
		{
			return false;
		}

		$meta = (array) $meta;
		$meta['RealName'] = $realname;
		$meta['email'] = $email;
		$set = array();

		foreach ($meta as $name => $value)
		{
			if ($value !== null)
			{
				$set[] = $name."='".doSlash($value)."'";
			}
		}

		if (
			safe_update(
				'txp_users',
				join(',', $set),
				"name = '".doSlash($user)."'"
			) === false
		)
		{
			return false;
		}

		callback_event('user.update', 'done', 0, compact('user', 'email', 'realname', 'meta'));
		return true;
	}

/**
 * Changes a user's password.
 *
 * On a successful run, this function will trigger
 * a 'user.password_change > done' callback event.
 *
 * @param   string $user     The updated user
 * @param   string $password The new password
 * @return  bool   FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (change_user_password('login', 'WalrusWasDancing'))
 * {
 * 	echo "Password changed.";
 * }
 */

	function change_user_password($user, $password)
	{
		if (!$user || !$password)
		{
			return false;
		}

		if (
			safe_update(
				'txp_users',
				"pass = '".doSlash(txp_hash_password($password))."'",
				"name = '".doSlash($user)."'"
			) === false
		)
		{
			return false;
		}

		callback_event('user.password_change', 'done', 0, compact('user', 'password'));
		return true;
	}

/**
 * Removes a user.
 *
 * The user's assets are assigned to the given new owner.
 *
 * On a successful run, this function will trigger
 * a 'user.remove > done' callback event.
 *
 * @param   string|array $user      List of removed users
 * @param   string       $new_owner Assign assets to
 * @return  bool         FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (remove_user('user', 'new_owner'))
 * {
 * 	echo "Removed 'user' and assigned assets to 'new_owner'.";
 * }
 */

	function remove_user($user, $new_owner)
	{
		if (!$user || !$new_owner)
		{
			return false;
		}

		$names = join(',', quote_list((array) $user));

		if (assign_user_assets($user, $new_owner) === false)
		{
			return false;
		}

		if (safe_delete('txp_prefs', "user_name in ($names)") === false)
		{
			return false;
		}

		if (safe_delete('txp_users', "name in ($names)") === false)
		{
			return false;
		}

		callback_event('user.remove', 'done', 0, compact('user', 'new_owner'));

		return true;
	}

/**
 * Renames a user.
 *
 * On a successful run, this function will trigger
 * a 'user.rename > done' callback event.
 *
 * @param   string $user    Updated user
 * @param   string $newname The new name
 * @return  bool   FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (rename_user('login', 'newname'))
 * {
 * 	echo "'login' renamed to 'newname'.";
 * }
 */

	function rename_user($user, $newname)
	{
		if (!is_scalar($user) || !is_valid_username($newname))
		{
			return false;
		}

		if (assign_user_assets($user, $newname) === false)
		{
			return false;
		}

		if (
			safe_update(
				'txp_users',
				"name = '".doSlash($newname)."'",
				"name = '".doSlash($user)."'"
			) === false
		)
		{
			return false;
		}

		callback_event('user.rename', 'done', 0, compact('user', 'newname'));

		return true;
	}

/**
 * Checks if a user exists.
 *
 * @param   string $user The user
 * @return  bool   TRUE if the user exists
 * @since   4.6.0
 * @package User
 * @example
 * if (user_exists('john'))
 * {
 * 	echo "'john' exists.";
 * }
 */

	function user_exists($user)
	{
		return (bool) safe_row('name', 'txp_users', "name = '".doSlash($user)."'");
	}

/**
 * Changes a user's group.
 *
 * On a successful run, this function will trigger
 * a 'user.change_group > done' callback event.
 *
 * @param   string|array $user  Updated users
 * @param   int          $group The new group
 * @return  bool         FALSE on error
 * @since   4.6.0
 * @package User
 * @example
 * if (change_user_group('john', 1))
 * {
 * 	echo "'john' is now publisher.";
 * }
 */

	function change_user_group($user, $group)
	{
		$levels = get_groups();

		if (!$user || !isset($levels[$group]))
		{
			return false;
		}

		$names = join(',', quote_list((array) $user));

		if (
			safe_update(
				'txp_users',
				'privs = '.intval($group),
				"name in ($names)"
			) === false
		)
		{
			return false;
		}

		callback_event('user.change_group', 'done', 0, compact('user', 'group'));
		return true;
	}

/**
 * Validates the given user credentials.
 *
 * This function validates a given login and a password combination.
 * If the combination is correct, the user's login name is returned,
 * FALSE otherwise.
 *
 * If $log is TRUE, also checks that the user has permissions to access
 * the admin side interface. On success, updates the user's last access
 * timestamp.
 *
 * @param   string      $user     The login
 * @param   string      $password The password
 * @param   bool        $log      If TRUE, requires privilege level greater than 'none'
 * @return  string|bool The user's login name or FALSE on error
 * @package User
 */

	function txp_validate($user, $password, $log = true)
	{
		static $phpass = null;

		$safe_user = doSlash($user);
		$name = false;

		$r = safe_row('name, pass, privs', 'txp_users', "name = '$safe_user'");

		if (!$r)
		{
			return false;
		}

		if (!$phpass)
		{
			include_once txpath.'/lib/PasswordHash.php';
			$phpass = new PasswordHash(PASSWORD_COMPLEXITY, PASSWORD_PORTABILITY);
		}

		// Check post-4.3-style passwords.
		if ($phpass->CheckPassword($password, $r['pass']))
		{
			if (!$log || $r['privs'] > 0)
			{
				$name = $r['name'];
			}
		}
		else
		{
			// No good password: check 4.3-style passwords.
			$passwords = array();
			$passwords[] = "password(lower('".doSlash($password)."'))";
			$passwords[] = "password('".doSlash($password)."')";

			if (version_compare(mysql_get_server_info(), '4.1.0', '>='))
			{
				$passwords[] = "old_password(lower('".doSlash($password)."'))";
				$passwords[] = "old_password('".doSlash($password)."')";
			}

			$name = safe_field("name", "txp_users",
				"name = '$safe_user' and (pass = ".join(' or pass = ', $passwords).") and privs > 0");

			// Old password is good: migrate password to phpass.
			if ($name !== false)
			{
				safe_update("txp_users", "pass = '".doSlash($phpass->HashPassword($password))."'", "name = '$safe_user'");
			}
		}

		if ($name !== false && $log)
		{
			// Update the last access time.
			safe_update("txp_users", "last_access = now()", "name = '$safe_user'");
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
		if (!$phpass)
		{
			include_once txpath.'/lib/PasswordHash.php';
			$phpass = new PasswordHash(PASSWORD_COMPLEXITY, PASSWORD_PORTABILITY);
		}
		return $phpass->HashPassword($password);
	}

/**
 * Extracts a statement from a if/else condition.
 *
 * @param   string  $thing     Statement in Textpattern tag markup presentation
 * @param   bool    $condition TRUE to return if statement, FALSE to else
 * @return  string  Either if or else statement
 * @package TagParser
 * @example
 * echo parse(EvalElse('true &lt;txp:else /&gt; false', 1 === 1));
 */

	function EvalElse($thing, $condition)
	{
		global $txp_current_tag;
		static $gTxtTrue = null, $gTxtFalse;

		if (empty($gTxtTrue))
		{
			$gTxtTrue = gTxt('true');
			$gTxtFalse = gTxt('false');
		}

		trace_add("[$txp_current_tag: ".($condition ? $gTxtTrue : $gTxtFalse)."]");

		$els = strpos($thing, '<txp:else');

		if ($els === false)
		{
			if ($condition)
			{
				return $thing;
			}

			return '';
		}
		elseif ($els === strpos($thing, '<txp:'))
		{
			if ($condition)
			{
				return substr($thing, 0, $els);
			}

			return substr($thing, strpos($thing, '>', $els) + 1);
		}

		$tag    = false;
		$level  = 0;
		$str    = '';
		$regex  = '@(</?txp:\w+(?:\s+\w+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/>]+))*\s*/?'.chr(62).')@s';
		$parsed = preg_split($regex, $thing, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($parsed as $chunk)
		{
			if ($tag)
			{
				if ($level === 0 and strpos($chunk, 'else') === 5 and substr($chunk, -2, 1) === '/')
				{
					if ($condition)
					{
						return $str;
					}

					return substr($thing, strlen($str)+strlen($chunk));
				}
				elseif (substr($chunk, 1, 1) === '/')
				{
					$level--;
				}
				elseif (substr($chunk, -2, 1) !== '/')
				{
					$level++;
				}
			}

			$tag = !$tag;
			$str .= $chunk;
		}

		if ($condition)
		{
			return $thing;
		}

		return '';
	}

/**
 * Gets a form template's contents.
 *
 * @param   string $name The form
 * @return  string
 * @package TagParser
 */

	function fetch_form($name)
	{
		static $forms = array();

		if (isset($forms[$name]))
		{
			$f = $forms[$name];
		}
		else
		{
			$row = safe_row('Form', 'txp_form',"name='".doSlash($name)."'");
			if (!$row)
			{
				trigger_error(gTxt('form_not_found').': '.$name);
				return '';
			}
			$f = $row['Form'];
			$forms[$name] = $f;
		}

		trace_add('['.gTxt('form').': '.$name.']');
		return $f;
	}

/**
 * Parses a form template.
 *
 * @param   string $name The form
 * @return  string The parsed contents
 * @package TagParser
 */

	function parse_form($name)
	{
		global $txp_current_form;
		static $stack = array();

		$out = '';
		$f = fetch_form($name);
		if ($f)
		{
			if (in_array($name, $stack))
			{
				trigger_error(gTxt('form_circular_reference', array('{name}' => $name)));
				return '';
			}
			$old_form = $txp_current_form;
			$txp_current_form = $stack[] = $name;
			$out = parse($f);
			$txp_current_form = $old_form;
			array_pop($stack);
		}

		return $out;
	}

/**
 * Gets a category's title.
 *
 * @param  string      $name The category
 * @param  string      $type Category's type. Either "article", "file", "image" or "link"
 * @return string|bool The title or FALSE on error
 */

	function fetch_category_title($name, $type = 'article')
	{
		static $cattitles = array();
		global $thiscategory;

		if (isset($cattitles[$type][$name]))
		{
			return $cattitles[$type][$name];
		}

		if (!empty($thiscategory['title']) && $thiscategory['name'] == $name && $thiscategory['type'] == $type)
		{
			$cattitles[$type][$name] = $thiscategory['title'];
			return $thiscategory['title'];
		}

		$f = safe_field('title', 'txp_category', "name='".doSlash($name)."' and type='".doSlash($type)."'");
		$cattitles[$type][$name] = $f;
		return $f;
	}

/**
 * Gets a section's title.
 *
 * @param  string      $name The section
 * @return string|bool The title or FALSE on error
 */

	function fetch_section_title($name)
	{
		static $sectitles = array();
		global $thissection;

		// Try cache.
		if (isset($sectitles[$name]))
		{
			return $sectitles[$name];
		}

		// Try global set by section_list().
		if (!empty($thissection['title']) && $thissection['name'] == $name)
		{
			$sectitles[$name] = $thissection['title'];
			return $thissection['title'];
		}

		if ($name == 'default' or empty($name))
		{
			return '';
		}

		$f = safe_field('title', 'txp_section', "name='".doSlash($name)."'");
		$sectitles[$name] = $f;
		return $f;
	}

/**
 * Updates an article's comment count.
 *
 * @param   int $id The article
 * @return  bool
 * @package Comment
 */

	function update_comments_count($id)
	{
		$id = assert_int($id);
		$thecount = safe_field('count(*)', 'txp_discuss', 'parentid='.$id.' and visible='.VISIBLE);
		$thecount = assert_int($thecount);
		$updated = safe_update('textpattern', 'comments_count='.$thecount, 'ID='.$id);
		return ($updated) ? true : false;
	}

/**
 * Recalculates and updates comment counts.
 *
 * @param   array $parentids List of articles to update
 * @package Comment
 */

	function clean_comment_counts($parentids)
	{
		$parentids = array_map('assert_int', $parentids);
		$rs = safe_rows_start('parentid, count(*) as thecount', 'txp_discuss', 'parentid IN ('.implode(',', $parentids).') AND visible='.VISIBLE.' group by parentid');

		if (!$rs)
		{
			return;
		}

		$updated = array();
		while ($a = nextRow($rs))
		{
			safe_update('textpattern', "comments_count=".$a['thecount'], "ID=".$a['parentid']);
			$updated[] = $a['parentid'];
		}
		// We still need to update all those, that have zero comments left.
		$leftover = array_diff($parentids, $updated);
		if ($leftover)
		{
			safe_update('textpattern', "comments_count=0","ID IN (".implode(',', $leftover).")");
		}
	}

/**
 * Parses and formats comment message using Textile.
 *
 * @param   string $msg The comment message
 * @return  string HTML markup
 * @package Comment
 */

	function markup_comment($msg)
	{
		global $prefs;

		$disallow_images = !empty($prefs['comments_disallow_images']);
		$lite = empty($prefs['comments_use_fat_textile']);

		$rel = !empty($prefs['comment_nofollow']) ? 'nofollow' : '';

		include_once txpath.'/lib/classTextile.php';

		$textile = new Textile($prefs['doctype']);

		return $textile->TextileRestricted($msg, $lite, $disallow_images, $rel);
	}

/**
 * Updates site's last modification date.
 *
 * @package Pref
 * @example
 * update_lastmod();
 */

	function update_lastmod()
	{
		safe_upsert("txp_prefs", "val = now()", "name = 'lastmod'");
	}

/**
 * Gets the site's last modification date.
 *
 * @param   int $unix_ts UNIX timestamp
 * @return  int UNIX timestamp
 * @package Pref
 */

	function get_lastmod($unix_ts = null)
	{
		global $prefs;

		if ($unix_ts === null)
		{
			$unix_ts = @strtotime($prefs['lastmod']);
		}

		// Check for future articles that are now visible.
		if ($max_article = safe_field('unix_timestamp(Posted)', 'textpattern', "Posted <= now() and Status >= 4 order by Posted desc limit 1"))
		{
			$unix_ts = max($unix_ts, $max_article);
		}

		return $unix_ts;
	}

/**
 * Sends and handles a lastmod header.
 *
 * @param   int|null $unix_ts The last modification date as a UNIX timestamp
 * @param   bool     $exit    If TRUE, terminates the script
 * @return  array    Array of sent HTTP status and the lastmod header
 * @package Pref
 */

	function handle_lastmod($unix_ts = null, $exit = 1)
	{
		global $prefs;
		extract($prefs);

		if ($send_lastmod and $production_status == 'live')
		{
			$unix_ts = get_lastmod($unix_ts);

			// Make sure lastmod isn't in the future.
			$unix_ts = min($unix_ts, time());

			// Or too far in the past (7 days).
			$unix_ts = max($unix_ts, time() - 3600 * 24 * 7);

			$last = safe_strftime('rfc822', $unix_ts, 1);
			header("Last-Modified: $last");
			header('Cache-Control: no-cache');

			$hims = serverSet('HTTP_IF_MODIFIED_SINCE');
			if ($hims and @strtotime($hims) >= $unix_ts)
			{
				log_hit('304');
				if (!$exit)
				{
					return array('304', $last);
				}
				txp_status_header('304 Not Modified');

				// Some mod_deflate versions have a bug that breaks subsequent
				// requests when keepalive is used.  dropping the connection
				// is the only reliable way to fix this.
				if (empty($lastmod_keepalive))
				{
					header('Connection: close');
				}
				header('Content-Length: 0');

				// Discard all output.
				while (@ob_end_clean());
				exit;
			}

			if (!$exit)
			{
				return array('200', $last);
			}
		}
	}

/**
 * Creates or updates a preference.
 *
 * @param   string $name       The name
 * @param   string $val        The value
 * @param   string $event      The section the preference appears in
 * @param   int    $type       Either PREF_CORE, PREF_PLUGIN, PREF_HIDDEN
 * @param   string $html       The HTML control type the field uses. Can take a custom function name
 * @param   int    $position   Used to sort the field on the Preferences panel
 * @param   bool   $is_private If PREF_PRIVATE, is created as a user pref
 * @return  bool   FALSE on error
 * @package Pref
 * @example
 * if (set_pref('myPref', 'value'))
 * {
 * 	echo "'myPref' created or updated.";
 * }
 */

	function set_pref($name, $val, $event = 'publish', $type = PREF_CORE, $html = 'text_input', $position = 0, $is_private = PREF_GLOBAL)
	{
		$user_name = null;

		if ($is_private == PREF_PRIVATE)
		{
			$user_name = PREF_PRIVATE;
		}

		if (pref_exists($name, $user_name))
		{
			return update_pref($name, (string) $val, null, null, null, null, $user_name);
		}

		return create_pref($name, $val, $event, $type, $html, $position, $user_name);
	}

/**
 * Gets a preference string.
 *
 * This function prefers global system-wide preferences
 * over a user's private preferences.
 *
 * @param   string $thing   The named variable
 * @param   mixed  $default Used as a replacement if named pref isn't found
 * @param   bool   $from_db If TRUE checks database opposed $prefs variable in memory
 * @return  string Preference value or $default
 * @package Pref
 * @example
 * if (get_pref('enable_xmlrpc_server'))
 * {
 * 	echo "XML-RPC server is enabled.";
 * }
 */

	function get_pref($thing, $default = '', $from_db = false)
	{
		global $prefs, $txp_user;

		if ($from_db)
		{
			$name = doSlash($thing);
			$user_name = doSlash($txp_user);

			$field = safe_field(
				'val',
				'txp_prefs',
				"name='$name' and (user_name='' or user_name='$user_name') order by user_name limit 1"
			);

			if ($field !== false)
			{
				$prefs[$thing] = $field;
			}
		}

		if (isset($prefs[$thing]))
		{
			return $prefs[$thing];
		}

		return $default;
	}

/**
 * Removes a preference string.
 *
 * This function removes preference strings based on the given
 * arguments. Use NULL to omit an argument.
 *
 * @param   string|null      $name       The preference string name
 * @param   string|null      $event      The preference event
 * @param   string|null|bool $user_name  The owner. If PREF_PRIVATE, the current user
 * @return  bool             TRUE on success
 * @since   4.6.0
 * @package Pref
 * @example
 * if (remove_pref(null, 'myEvent'))
 * {
 * 	echo "Removed all preferences from 'myEvent'.";
 * }
 */

	function remove_pref($name = null, $event = null, $user_name = null)
	{
		global $txp_user;

		$sql = array();

		if ($user_name === PREF_PRIVATE)
		{
			if (!$txp_user)
			{
				return false;
			}

			$user_name = $txp_user;
		}

		if ($user_name !== null)
		{
			$sql[] = "user_name = '".doSlash((string) $user_name)."'";
		}

		if ($event !== null)
		{
			$sql[] = "event = '".doSlash($event)."'";
		}

		if ($name !== null)
		{
			$sql[] = "name = '".doSlash($name)."'";
		}

		if ($sql)
		{
			return safe_delete('txp_prefs', join(' and ', $sql));
		}

		return false;
	}

/**
 * Checks if a preference string exists.
 *
 * This function searches for matching preference strings based on
 * the given arguments.
 *
 * The $user_name argument can be used to limit the search to a specifc
 * user, or to global and private strings. If NULL, matches are searched
 * from both private and global strings.
 *
 * @param   string           $name      The preference string name
 * @param   string|null|bool $user_name Either the username, NULL, PREF_PRIVATE or PREF_GLOBAL
 * @return  bool             TRUE if the string exists, or FALSE on error
 * @since   4.6.0
 * @package Pref
 * @example
 * if (pref_exists('myPref'))
 * {
 * 	echo "'myPref' exists.";
 * }
 */

	function pref_exists($name, $user_name = null)
	{
		global $txp_user;

		$sql = array();
		$sql[] = "name = '".doSlash($name)."'";

		if ($user_name === PREF_PRIVATE)
		{
			if (!$txp_user)
			{
				return false;
			}

			$user_name = $txp_user;
		}

		if ($user_name !== null)
		{
			$sql[] = "user_name = '".doSlash((string) $user_name)."'";
		}

		if (safe_row('name', 'txp_prefs', join(' and ', $sql)))
		{
			return true;
		}

		return false;
	}

/**
 * Creates a preference string.
 *
 * @param   string      $name       The name
 * @param   string      $val        The value
 * @param   string      $event      The section the preference appears in
 * @param   int         $type       Either PREF_CORE, PREF_PLUGIN, PREF_HIDDEN
 * @param   string      $html       The HTML control type the field uses. Can take a custom function name
 * @param   int         $position   Used to sort the field on the Preferences panel
 * @param   string|bool $user_name  The user name, PREF_GLOBAL or PREF_PRIVATE
 * @return  bool        TRUE if the string exists, FALSE on error
 * @since   4.6.0
 * @package Pref
 * @example
 * if (set_pref('myPref', 'value', 'site', PREF_PLUGIN, 'text_input', 25))
 * {
 * 	echo "'myPref' created.";
 * }
 */

	function create_pref($name, $val, $event = 'publish', $type = PREF_CORE, $html = 'text_input', $position = 0, $user_name = PREF_GLOBAL)
	{
		global $txp_user;

		if ($user_name === PREF_PRIVATE)
		{
			if (!$txp_user)
			{
				return false;
			}

			$user_name = $txp_user;
		}

		if (pref_exists($name, $user_name))
		{
			return true;
		}

		return safe_insert(
			'txp_prefs',
			"prefs_id = 1,
			name = '".doSlash($name)."',
			val = '".doSlash($val)."',
			event = '".doSlash($event)."',
			html = '".doSlash($html)."',
			type = ".intval($type).",
			position = ".intval($position).",
			user_name = '".doSlash((string) $user_name)."'"
		) !== false;
	}

/**
 * Updates a preference string.
 *
 * This function updates a preference string's properties.
 * $name and $user_name arguments are used for selecting
 * the updated string, and rest of the arguments take
 * the new values. Use NULL to omit an argument.
 *
 * @param   string           $name       The update preference string's name
 * @param   string|null      $val        The value
 * @param   string|null      $event      The section the preference appears in
 * @param   int|null         $type       Either PREF_CORE, PREF_PLUGIN, PREF_HIDDEN
 * @param   string|null      $html       The HTML control type the field uses. Can take a custom function name
 * @param   int|null         $position   Used to sort the field on the Preferences panel
 * @param   string|bool|null $user_name  The updated string's owner, PREF_GLOBAL or PREF_PRIVATE
 * @return  bool             FALSE on error
 * @since   4.6.0
 * @package Pref
 * @example
 * if (update_pref('myPref', 'New value.'))
 * {
 * 	echo "Updated 'myPref' value.";
 * }
 */

	function update_pref($name, $val = null, $event = null, $type = null, $html = null, $position = null, $user_name = PREF_GLOBAL)
	{
		global $txp_user;

		$where = $set = array();
		$where[] = "name = '".doSlash($name)."'";

		if ($user_name === PREF_PRIVATE)
		{
			if (!$txp_user)
			{
				return false;
			}

			$user_name = $txp_user;
		}

		if ($user_name !== null)
		{
			$where[] = "user_name = '".doSlash((string) $user_name)."'";
		}

		foreach (array('val', 'event', 'type', 'html', 'position') as $field)
		{
			if ($$field !== null)
			{
				$set[] = $field." = '".doSlash($$field)."'";
			}
		}

		if ($set)
		{
			return safe_update('txp_prefs', join(', ', $set), join(' and ', $where));
		}

		return false;
	}

/**
 * Renames a preference string.
 *
 * @param   string $newname   The new name
 * @param   string $name      The current name
 * @param   string $user_name Either the username, PREF_GLOBAL or PREF_PRIVATE
 * @return  bool   FALSE on error
 * @since   4.6.0
 * @package Pref
 * @example
 * if (rename_pref('mynewPref', 'myPref'))
 * {
 * 	echo "Renamed 'myPref' to 'mynewPref'.";
 * }
 */

	function rename_pref($newname, $name, $user_name = null)
	{
		global $txp_user;

		$where = array();
		$where[] = "name = '".doSlash($name)."'";

		if ($user_name === PREF_PRIVATE)
		{
			if (!$txp_user)
			{
				return false;
			}

			$user_name = $txp_user;
		}

		if ($user_name !== null)
		{
			$where[] = "user_name = '".doSlash((string) $user_name)."'";
		}

		return safe_update('txp_prefs', "name = '".doSlash($newname)."'", join(' and ', $where));
	}

/**
 * Gets a list of custom fields.
 *
 * @return  array
 * @package CustomField
 */

	function getCustomFields()
	{
		global $prefs;
		static $out = null;

		// Have cache?
		if (!is_array($out))
		{
			$cfs = preg_grep('/^custom_\d+_set/', array_keys($prefs));

			$out = array();
			foreach ($cfs as $name)
			{
				preg_match('/(\d+)/', $name, $match);
				if (!empty($prefs[$name]))
				{
					$out[$match[1]] = strtolower($prefs[$name]);
				}
			}
		}

		return $out;
	}

/**
 * Build a query qualifier to filter non-matching custom fields from the result set.
 *
 * @param   array       $custom An array of 'custom_field_name' => field_number tupels
 * @param   array       $pairs  Filter criteria: An array of 'name' => value tupels
 * @return  bool|string An SQL qualifier for a query's 'WHERE' part
 * @package CustomField
 */

	function buildCustomSql($custom, $pairs)
	{
		if ($pairs)
		{
			$pairs = doSlash($pairs);
			foreach ($pairs as $k => $v)
			{
				if (in_array($k, $custom))
				{
					$no = array_keys($custom, $k);
					$out[] = "and custom_".$no[0]." like '$v'";
				}
			}
		}

		return !empty($out) ? ' '.join(' ', $out).' ' : false;
	}

/**
 * Sends a HTTP status header.
 *
 * @param   string $status The HTTP status code
 * @package Network
 * @example
 * txp_status_header('403 Forbidden');
 */

	function txp_status_header($status = '200 OK')
	{
		if (IS_FASTCGI)
		{
			header("Status: $status");
		}
		elseif (serverSet('SERVER_PROTOCOL') == 'HTTP/1.0')
		{
			header("HTTP/1.0 $status");
		}
		else
		{
			header("HTTP/1.1 $status");
		}
	}

/**
 * Terminates normal page rendition and outputs an error page.
 *
 * @param   string|array $msg    The error message
 * @param   string       $status HTTP status code
 * @param   string       $url    Redirects to the specified URL. Can be used with $status of 301, 302 and 307
 * @package Tag
 */

	function txp_die($msg, $status = '503', $url = '')
	{
		global $connected, $txp_error_message, $txp_error_status, $txp_error_code;

		// Make it possible to call this function as a tag, e.g. in an article <txp:txp_die status="410" />.
		if (is_array($msg))
		{
			extract(lAtts(array(
				'msg' => '',
				'status' => '503',
				'url' => ''
			), $msg));
		}

		// Intentionally incomplete - just the ones we're likely to use.
		$codes = array(
			'200' => 'OK',
			'301' => 'Moved Permanently',
			'302' => 'Found',
			'304' => 'Not Modified',
			'307' => 'Temporary Redirect',
			'401' => 'Unauthorized',
			'403' => 'Forbidden',
			'404' => 'Not Found',
			'410' => 'Gone',
			'414' => 'Request-URI Too Long',
			'500' => 'Internal Server Error',
			'501' => 'Not Implemented',
			'503' => 'Service Unavailable',
		);

		if ($status)
		{
			if (isset($codes[strval($status)]))
			{
				$status = strval($status) . ' ' . $codes[$status];
			}

			txp_status_header($status);
		}

		$code = '';
		if ($status and $parts = @explode(' ', $status, 2))
		{
			$code = @$parts[0];
		}

		callback_event('txp_die', $code, 0, $url);

		// Redirect with status.
		if ($url && in_array($code, array(301, 302, 307)))
		{
			ob_end_clean();
			header("Location: $url", true, $code);
			die('<html><head><meta http-equiv="refresh" content="0;URL='.txpspecialchars($url).'"></head><body></body></html>');
		}

		if ($connected && @txpinterface == 'public')
		{
			$out = safe_field('user_html', 'txp_page', "name='error_".doSlash($code)."'");
			if ($out === false)
			{
				$out = safe_field('user_html', 'txp_page', "name='error_default'");
			}
		}
		else
		{
			$out = <<<eod
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="utf-8">
   <title>Textpattern Error: <txp:error_status /></title>
</head>
<body>
	<p><txp:error_message /></p>
</body>
</html>
eod;
		}

		header("Content-type: text/html; charset=utf-8");

		if (is_callable('parse'))
		{
			$txp_error_message = $msg;
			$txp_error_status = $status;
			$txp_error_code = $code;
			set_error_handler("tagErrorHandler");
			die(parse($out));
		}
		else
		{
			$out = preg_replace(
				array('@<txp:error_status[^>]*/>@', '@<txp:error_message[^>]*/>@'),
				array($status, $msg),
				$out
			);
			die($out);
		}
	}

/**
 * Gets a URL-encoded and HTML entity-escaped query string for a URL.
 *
 * This function builds a HTTP query string from an associative array.
 *
 * @param   array  $q The parameters for the query
 * @return  string The query, including starting "?".
 * @package URL
 * @example
 * echo join_qs(array('param1' => 'value1', 'param2' => 'value2'));
 */

	function join_qs($q)
	{
		$qs = array();
		foreach ($q as $k => $v)
		{
			if (is_array($v))
			{
				$v = join(',', $v);
			}
			if ($k && (string) $v !== '')
			{
				$qs[$k] = urlencode($k) . '=' . urlencode($v);
			}
		}

		$str = join('&amp;', $qs);
		return ($str ? '?'.$str : '');
	}

/**
 * Builds a HTML attribute list from an array.
 *
 * This function takes an array of raw HTML attributes, and returns a
 * properly sanitised HTML attribute string for use in a HTML tag.
 *
 * This function internally handles HTML boolean attributes, array lists and 
 * query strings. If an attributes value is set as a boolean, the attribute is
 * considered as one too. If a value is NULL, it's omitted and the attribute is
 * added without a value. An array value is converted to a space-separated list,
 * or for 'href' and 'src' to URL encoded a query string.
 *
 * @param   array|string  $atts  HTML attributes
 * @param   int           $flags ATTS_STRIP_EMPTY
 * @return  string        HTML attribute list
 * @since   4.6.0
 * @package HTML
 * @example
 * echo join_atts(array('class' => 'myClass', 'disabled' => true));
 */

	function join_atts($atts, $flags = ATTS_STRIP_EMPTY)
	{
		if (!is_array($atts))
		{
			return $atts ? ' '.trim($atts) : '';
		}

		$list = array();

		foreach ($atts as $name => $value)
		{
			if (($flags & ATTS_STRIP_EMPTY && !$value) || $value === false)
			{
				continue;
			}

			elseif (is_array($value))
			{
				if ($name == 'href' || $name == 'src')
				{
					$list[] = $name.'="'.join_qs($value).'"';
					continue;
				}

				$value = join(' ', $value);
			}

			else if ($value === true)
			{
				$value = $name;
			}

			else if ($value === null)
			{
				$list[] = $name;
				continue;
			}

			$list[] = $name.'="'.txpspecialchars($value).'"';
		}

		return $list ? ' '.join(' ', $list) : '';
	}

/**
 * Builds a page URL from an array of parameters.
 *
 * The $inherit can be used to add parameters to an existing url, e.g:
 * pagelinkurl(array('pg'=>2), $pretext).
 *
 * This function can not be used to link to an article. See permlinkurl()
 * and permlinkurl_id() instead.
 *
 * @param   array $parts   The parts used to construct the URL
 * @param   array $inherit Can be used to add parameters to an existing url
 * @return  string
 * @see     permlinkurl()
 * @see     permlinkurl_id()
 * @package URL
 */

	function pagelinkurl($parts, $inherit = array())
	{
		global $permlink_mode, $prefs;

		$keys = array_merge($inherit, $parts);

		if (isset($prefs['custom_url_func'])
		    and is_callable($prefs['custom_url_func'])
		    and ($url = call_user_func($prefs['custom_url_func'], $keys, PAGELINKURL)) !== false)
		{
			return $url;
		}

		// Can't use this to link to an article.
		if (isset($keys['id']))
		{
			unset($keys['id']);
		}

		if (isset($keys['s']) && $keys['s'] == 'default')
		{
			unset($keys['s']);
		}

		// 'article' context is implicit, no need to add it to the page URL.
		if (isset($keys['context']) && $keys['context'] == 'article')
		{
			unset($keys['context']);
		}

		if ($permlink_mode == 'messy')
		{
			if (!empty($keys['context']))
			{
				$keys['context'] = gTxt($keys['context'].'_context');
			}
			return hu.'index.php'.join_qs($keys);
		}

		else
		{
			// All clean URL modes use the same schemes for list pages.
			$url = '';

			if (!empty($keys['rss']))
			{
				$url = hu.'rss/';
				unset($keys['rss']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['atom']))
			{
				$url = hu.'atom/';
				unset($keys['atom']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['s']))
			{
				if (!empty($keys['context']))
				{
					$keys['context'] = gTxt($keys['context'].'_context');
				}
				$url = hu.urlencode($keys['s']).'/';
				unset($keys['s']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['author']))
			{
				$ct = empty($keys['context']) ? '' : strtolower(urlencode(gTxt($keys['context'].'_context'))).'/';
				$url = hu.strtolower(urlencode(gTxt('author'))).'/'.$ct.urlencode($keys['author']).'/';
				unset($keys['author'], $keys['context']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['c']))
			{
				$ct = empty($keys['context']) ? '' : strtolower(urlencode(gTxt($keys['context'].'_context'))).'/';
				$url = hu.strtolower(urlencode(gTxt('category'))).'/'.$ct.urlencode($keys['c']).'/';
				unset($keys['c'], $keys['context']);
				return $url.join_qs($keys);
			}

			return hu.join_qs($keys);
		}
	}

/**
 * Gets a URL for the given article.
 *
 * If you need to generate a list of article URLs
 * from already fetched table rows, consider using
 * permlinkurl() over this due to performance benefits.
 *
 * @param   int    $id The article ID
 * @return  string The URL
 * @see     permlinkurl()
 * @package URL
 * @example
 * echo permlinkurl_id(12);
 */

	function permlinkurl_id($id)
	{
		global $permlinks;

		$id = (int) $id;

		if (isset($permlinks[$id]))
		{
			return $permlinks[$id];
		}

		$rs = safe_row(
			"ID as thisid, Section as section, Title as title, url_title, unix_timestamp(Posted) as posted",
			'textpattern',
			"ID = $id"
		);

		return permlinkurl($rs);
	}

/**
 * Generates an article URL from the given data array.
 *
 * @param   array  $article_array An array consisting of keys 'thisid', 'section', 'title', 'url_title', 'posted'
 * @return  string The URL
 * @package URL
 * @see     permlinkurl_id()
 * @example
 * echo permlinkurl_id(array(
 * 	'thisid'    => 12,
 * 	'section'   => 'blog',
 * 	'url_title' => 'my-title',
 * 	'posted'    => 1345414041
 * ));
 */

	function permlinkurl($article_array)
	{
		global $permlink_mode, $prefs, $permlinks;

		if (!$article_array || !is_array($article_array))
		{
			return;
		}

		if (isset($prefs['custom_url_func'])
		    and is_callable($prefs['custom_url_func'])
		    and ($url = call_user_func($prefs['custom_url_func'], $article_array, PERMLINKURL)) !== false)
		{
			return $url;
		}

		extract(lAtts(array(
			'thisid'    => null,
			'ID'        => null,
			'Title'     => null,
			'title'     => null,
			'url_title' => null,
			'section'   => null,
			'Section'   => null,
			'posted'    => null,
			'Posted'    => null,
		), $article_array, false));

		if (empty($thisid))
		{
			$thisid = $ID;
		}

		$thisid = (int) $thisid;

		if (isset($permlinks[$thisid]))
		{
			return $permlinks[$thisid];
		}

		if (!isset($title))
		{
			$title = $Title;
		}

		if (empty($url_title))
		{
			$url_title = stripSpace($title);
		}

		if (empty($section))
		{
			$section = $Section;
		}

		if (!isset($posted))
		{
			$posted = $Posted;
		}

		$section = urlencode($section);
		$url_title = urlencode($url_title);

		switch ($permlink_mode)
		{
			case 'section_id_title' :
				if ($prefs['attach_titles_to_permalinks'])
				{
					$out = hu."$section/$thisid/$url_title";
				}
				else
				{
					$out = hu."$section/$thisid/";
				}
				break;
			case 'year_month_day_title' :
				list($y, $m, $d) = explode("-", date("Y-m-d", $posted));
				$out =  hu."$y/$m/$d/$url_title";
				break;
			case 'id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					$out = hu."$thisid/$url_title";
				}
				else
				{
					$out = hu."$thisid/";
				}
				break;
			case 'section_title' :
				$out = hu."$section/$url_title";
				break;
			case 'title_only' :
				$out = hu."$url_title";
				break;
			case 'messy' :
				$out = hu."index.php?id=$thisid";
				break;
		}

		return $permlinks[$thisid] = $out;
	}

/**
 * Gets a file download URL.
 *
 * @param   int    $id       The ID
 * @param   string $filename The filename
 * @return  string
 * @package File
 */

	function filedownloadurl($id, $filename = '')
	{
		global $permlink_mode;

		$filename = urlencode($filename);
		// FIXME: work around yet another mod_deflate problem (double compression)
		// http://blogs.msdn.com/wndp/archive/2006/08/21/Content-Encoding-not-equal-Content-Type.aspx
		if (preg_match('/gz$/i', $filename))
		{
			$filename .= a;
		}
		return ($permlink_mode == 'messy') ?
			hu.'index.php?s=file_download'.a.'id='.$id :
			hu.gTxt('file_download').'/'.$id.($filename ? '/'.$filename : '');
	}

/**
 * Gets an image's absolute URL.
 *
 * @param   int    $id        The image
 * @param   string $ext       The file extension
 * @param   bool   $thumbnail If TRUE returns a URL to the thumbnail
 * @return  string
 * @package Image
 */

	function imagesrcurl($id, $ext, $thumbnail = false)
	{
		global $img_dir;
		$thumbnail = $thumbnail ? 't' : '';
		return ihu.$img_dir.'/'.$id.$thumbnail.$ext;
	}

/**
 * Checks if a value exists in a list.
 *
 * @param  string $val   The searched value
 * @param  string $list  The value list
 * @param  string $delim The list boundary
 * @return bool   Returns TRUE if $val is found, FALSE otherwise
 * @example
 * if (in_list('red', 'blue, green, red, yellow'))
 * {
 * 	echo "'red' found from the list.";
 * }
 */

	function in_list($val, $list, $delim = ',')
	{
		$args = do_list($list, $delim);

		return in_array($val, $args);
	}

/**
 * Split a string by string.
 *
 * This function trims created values from whitespace.
 *
 * @param  string $list  The string
 * @param  string $delim The boundary
 * @return array
 * @example
 * print_r(
 * 	do_list('value1, value2, value3')
 * );
 */

	function do_list($list, $delim = ',')
	{
		return array_map('trim', explode($delim, $list));
	}

/**
 * Wraps a string in single quotes.
 *
 * @param  string $val The input string
 * @return string
 */

	function doQuote($val)
	{
		return "'$val'";
	}

/**
 * Escapes special characters for use in an SQL statement and wraps the value in quote.
 *
 * Use this function if you want to use an array of values in an SQL statement.
 *
 * @param   string|array $in The input value
 * @return  mixed
 * @package DB
 * @example
 * if ($r = safe_row('name', 'myTable', 'type in(' . quote_list(array('value1', 'value2')) . ')')
 * {
 * 	echo "Found '{$r['name']}'.";
 * }
 */

	function quote_list($in)
	{
		$out = doSlash($in);
		return doArray($out, 'doQuote');
	}

/**
 * Adds a line to the tag trace.
 *
 * @param   string $msg The message
 * @package Debug
 */

	function trace_add($msg)
	{
		global $production_status, $txptrace, $txptracelevel;

		if ($production_status === 'debug')
		{
			$txptrace[] = str_repeat("\t", $txptracelevel).$msg;
		}
	}

/**
 * Push current article on the end of data stack.
 *
 * This function populates $stack_article global with the
 * current $thisarticle.
 */

	function article_push()
	{
		global $thisarticle, $stack_article;
		$stack_article[] = $thisarticle;
	}

/**
 * Advance to the next article in the current data stack.
 *
 * This function populates $thisarticle global with the
 * last article form the stack stored in $stack_article.
 */

	function article_pop()
	{
		global $thisarticle, $stack_article;
		$thisarticle = array_pop($stack_article);
	}

/**
 * Gets a path relative to the site's root directory.
 *
 * @param   string $path The filename to parse
 * @param   string $pfx  The root directory
 * @return  string The absolute $path converted to relative
 * @package File
 */

	function relative_path($path, $pfx = null)
	{
		if ($pfx === null)
		{
			$pfx = dirname(txpath);
		}

		return preg_replace('@^/'.preg_quote(ltrim($pfx, '/'), '@').'/?@', '', $path);
	}

/**
 * Gets a backtrace.
 *
 * @param   int $num   The limit
 * @param   int $start The offset
 * @return  array A backtrace
 * @package Debug
 */

	function get_caller($num = 1, $start = 2)
	{
		$out = array();

		if (!is_callable('debug_backtrace'))
		{
			return $out;
		}

		$bt = debug_backtrace();
		for ($i = $start; $i < $num+$start; $i++)
		{
			if (!empty($bt[$i]))
			{
				$t = '';
				if (!empty($bt[$i]['file']))
				{
					$t .= relative_path($bt[$i]['file']);
				}
				if (!empty($bt[$i]['line']))
				{
					$t .= ':'.$bt[$i]['line'];
				}
				if ($t)
				{
					$t .= ' ';
				}
				if (!empty($bt[$i]['class']))
				{
					$t .= $bt[$i]['class'];
				}
				if (!empty($bt[$i]['type']))
				{
					$t .= $bt[$i]['type'];
				}
				if (!empty($bt[$i]['function']))
				{
					$t .= $bt[$i]['function'];
					$t .= '()';
				}

				$out[] = $t;
			}
		}
		return $out;
	}

/**
 * Sets a locale.
 *
 * The function name is misleading but remains for legacy reasons.
 *
 * @param   string $lang
 * @return  string Current locale
 * @package L10n
 */

	function getlocale($lang)
	{
		global $locale;

		if (empty($locale))
		{
			$locale = @setlocale(LC_TIME, '0');
		}

		// Locale identifiers vary from system to system.  The
		// following code will attempt to discover which identifiers
		// are available.  We'll need to expand these lists to
		// improve support.
		// ISO identifiers: http://www.w3.org/WAI/ER/IG/ert/iso639.htm
		// Windows: http://msdn.microsoft.com/library/default.asp?url=/library/en-us/vclib/html/_crt_language_strings.asp
		$guesses = array(
			'ar-dz' => array('ar_DZ.UTF-8', 'ar_DZ', 'ara', 'ar', 'arabic', 'ar_DZ.ISO_8859-6'),
			'bg-bg' => array('bg_BG.UTF-8', 'bg_BG', 'bg', 'bul', 'bulgarian', 'bg_BG.ISO8859-5'),
			'ca-es' => array('ca_ES.UTF-8', 'ca_ES', 'cat', 'ca', 'catalan', 'ca_ES.ISO_8859-1'),
			'cs-cz' => array('cs_CZ.UTF-8', 'cs_CZ', 'ces', 'cze', 'cs', 'csy', 'czech', 'cs_CZ.cs_CZ.ISO_8859-2'),
			'da-dk' => array('da_DK.UTF-8', 'da_DK'),
			'de-de' => array('de_DE.UTF-8', 'de_DE', 'de', 'deu', 'german', 'de_DE.ISO_8859-1'),
			'en-gb' => array('en_GB.UTF-8', 'en_GB', 'en_UK', 'eng', 'en', 'english-uk', 'english', 'en_GB.ISO_8859-1','C'),
			'en-us' => array('en_US.UTF-8', 'en_US', 'english-us', 'en_US.ISO_8859-1'),
			'es-es' => array('es_ES.UTF-8', 'es_ES', 'esp', 'spanish', 'es_ES.ISO_8859-1'),
			'et-ee' => array('et_EE.UTF-8', 'et_EE'),
			'el-gr' => array('el_GR.UTF-8', 'el_GR', 'el', 'gre', 'greek', 'el_GR.ISO_8859-7'),
			'fi-fi' => array('fi_FI.UTF-8', 'fi_FI', 'fin', 'fi', 'finnish', 'fi_FI.ISO_8859-1'),
			'fr-fr' => array('fr_FR.UTF-8', 'fr_FR', 'fra', 'fre', 'fr', 'french', 'fr_FR.ISO_8859-1'),
			'gl-gz' => array('gl_GZ.UTF-8', 'gl_GZ', 'glg', 'gl', '', ''),
			'he_il' => array('he_IL.UTF-8', 'he_IL', 'heb', 'he', 'hebrew', 'he_IL.ISO_8859-8'),
			'hr-hr' => array('hr_HR.UTF-8', 'hr_HR', 'hr'),
			'hu-hu' => array('hu_HU.UTF-8', 'hu_HU', 'hun', 'hu', 'hungarian', 'hu_HU.ISO8859-2'),
			'id-id' => array('id_ID.UTF-8', 'id_ID', 'id', 'ind', 'indonesian','id_ID.ISO_8859-1'),
			'is-is' => array('is_IS.UTF-8', 'is_IS'),
			'it-it' => array('it_IT.UTF-8', 'it_IT', 'it', 'ita', 'italian', 'it_IT.ISO_8859-1'),
			'ja-jp' => array('ja_JP.UTF-8', 'ja_JP', 'ja', 'jpn', 'japanese', 'ja_JP.ISO_8859-1'),
			'ko-kr' => array('ko_KR.UTF-8', 'ko_KR', 'ko', 'kor', 'korean'),
			'lv-lv' => array('lv_LV.UTF-8', 'lv_LV', 'lv', 'lav'),
			'nl-nl' => array('nl_NL.UTF-8', 'nl_NL', 'dut', 'nla', 'nl', 'nld', 'dutch', 'nl_NL.ISO_8859-1'),
			'no-no' => array('no_NO.UTF-8', 'no_NO', 'no', 'nor', 'norwegian', 'no_NO.ISO_8859-1'),
			'pl-pl' => array('pl_PL.UTF-8', 'pl_PL', 'pl', 'pol', 'polish', ''),
			'pt-br' => array('pt_BR.UTF-8', 'pt_BR', 'pt', 'ptb', 'portuguese-brazil', ''),
			'pt-pt' => array('pt_PT.UTF-8', 'pt_PT', 'por', 'portuguese', 'pt_PT.ISO_8859-1'),
			'ro-ro' => array('ro_RO.UTF-8', 'ro_RO', 'ron', 'rum', 'ro', 'romanian', 'ro_RO.ISO8859-2'),
			'ru-ru' => array('ru_RU.UTF-8', 'ru_RU', 'ru', 'rus', 'russian', 'ru_RU.ISO8859-5'),
			'sk-sk' => array('sk_SK.UTF-8', 'sk_SK', 'sk', 'slo', 'slk', 'sky', 'slovak', 'sk_SK.ISO_8859-1'),
			'sv-se' => array('sv_SE.UTF-8', 'sv_SE', 'sv', 'swe', 'sve', 'swedish', 'sv_SE.ISO_8859-1'),
			'th-th' => array('th_TH.UTF-8', 'th_TH', 'th', 'tha', 'thai', 'th_TH.ISO_8859-11'),
			'uk-ua' => array('uk_UA.UTF-8', 'uk_UA', 'uk', 'ukr'),
			'vi-vn' => array('vi_VN.UTF-8', 'vi_VN', 'vi', 'vie'),
			'zh-cn' => array('zh_CN.UTF-8', 'zh_CN'),
			'zh-tw' => array('zh_TW.UTF-8', 'zh_TW'),
		);

		if (!empty($guesses[$lang]))
		{
			$l = @setlocale(LC_TIME, $guesses[$lang]);
			if ($l !== false)
			{
				$locale = $l;
			}
		}
		@setlocale(LC_TIME, $locale);

		return $locale;
	}

/**
 * Assert article context error.
 */

	function assert_article()
	{
		global $thisarticle;
		if (empty($thisarticle))
		{
			trigger_error(gTxt('error_article_context'));
		}
	}

/**
 * Assert comment context error.
 */

	function assert_comment()
	{
		global $thiscomment;
		if (empty($thiscomment))
		{
			trigger_error(gTxt('error_comment_context'));
		}
	}

/**
 * Assert file context error.
 */

	function assert_file()
	{
		global $thisfile;
		if (empty($thisfile))
		{
			trigger_error(gTxt('error_file_context'));
		}
	}

/**
 * Assert image context error.
 */

	function assert_image()
	{
		global $thisimage;
		if (empty($thisimage))
		{
			trigger_error(gTxt('error_image_context'));
		}
	}

/**
 * Assert link context error.
 */

	function assert_link()
	{
		global $thislink;
		if (empty($thislink))
		{
			trigger_error(gTxt('error_link_context'));
		}
	}

/**
 * Assert section context error.
 */

	function assert_section()
	{
		global $thissection;
		if (empty($thissection))
		{
			trigger_error(gTxt('error_section_context'));
		}
	}

/**
 * Assert category context error.
 */

	function assert_category()
	{
		global $thiscategory;
		if (empty($thiscategory))
		{
			trigger_error(gTxt('error_category_context'));
		}
	}

/**
 * Validate a variable as an integer.
 *
 * @param  mixed    $myvar The variable
 * @return int|bool The variable or FALSE on error
 */

	function assert_int($myvar)
	{
		if (is_numeric($myvar) and $myvar == intval($myvar))
		{
			return (int) $myvar;
		}
		trigger_error("'".txpspecialchars((string) $myvar)."' is not an integer", E_USER_ERROR);
		return false;
	}

/**
 * Validate a variable as a string.
 *
 * @param  mixed       $myvar The variable
 * @return string|bool The variable or FALSE on error
 */

	function assert_string($myvar)
	{
		if (is_string($myvar))
		{
			return $myvar;
		}
		trigger_error("'".txpspecialchars((string)$myvar)."' is not a string", E_USER_ERROR);
		return false;
	}

/**
 * Validate a variable as an array.
 *
 * @param  mixed      $myvar The variable
 * @return array|bool The variable or FALSE on error
 */

	function assert_array($myvar)
	{
		if (is_array($myvar))
		{
			return $myvar;
		}
		trigger_error("'".txpspecialchars((string) $myvar)."' is not an array", E_USER_ERROR);
		return false;
	}

/**
 * Converts relative links in HTML markup to absolute.
 *
 * @param   string $html      The HTML to check
 * @param   string $permalink Optional URL part appended to the links
 * @return  string HTML
 * @package URL
 */

	function replace_relative_urls($html, $permalink = '')
	{
		global $siteurl;

		// URLs like "/foo/bar" - relative to the domain.
		if (serverSet('HTTP_HOST'))
		{
			$html = preg_replace('@(<a[^>]+href=")/@', '$1'.PROTOCOL.serverSet('HTTP_HOST').'/', $html);
			$html = preg_replace('@(<img[^>]+src=")/@', '$1'.PROTOCOL.serverSet('HTTP_HOST').'/', $html);
		}
		// "foo/bar" - relative to the textpattern root,
		// leave "http:", "mailto:" et al. as absolute URLs.
		$html = preg_replace('@(<a[^>]+href=")(?!\w+:)@', '$1'.PROTOCOL.$siteurl.'/$2', $html);
		$html = preg_replace('@(<img[^>]+src=")(?!\w+:)@', '$1'.PROTOCOL.$siteurl.'/$2', $html);

		if ($permalink)
		{
			$html = preg_replace("/href=\\\"#(.*)\"/", "href=\"".$permalink."#\\1\"", $html);
		}

		return ($html);
	}

/**
 * Used for clean URL test.
 *
 * @param  array   $pretext
 * @access private
 */

	function show_clean_test($pretext)
	{
		echo md5(@$pretext['req']).n;
		if (serverSet('SERVER_ADDR') == serverSet('REMOTE_ADDR'))
		{
			var_export($pretext);
		}
	}

/**
 * Calculates paging.
 *
 * Takes a total number of items, a per page limit and the current
 * page number, and in return returns the page number, an offset
 * and a number of pages.
 *
 * @param  int   $total The number of items in total
 * @param  int   $limit The number of items per page
 * @param  int   $page  The page number
 * @return array Array of page, offset and number of pages.
 * @example
 * list($page, $offset, $num_pages) = pager(150, 10, 1);
 * echo "Page {$page} of {$num_pages}. Offset is {$offset}.";
 */

	function pager($total, $limit, $page)
	{
		$total = (int) $total;
		$limit = (int) $limit;
		$page = (int) $page;

		$num_pages = ceil($total / $limit);

		$page = min(max($page, 1), $num_pages);

		$offset = max(($page - 1) * $limit, 0);

		return array($page, $offset, $num_pages);
	}

/**
 * Word-wrap a string using a zero width space.
 *
 * @param  string $text  The input string
 * @param  int    $width Target line lenght
 * @param  string $break Is not used
 * @return string
 */

	function soft_wrap($text, $width, $break = '&#8203;')
	{
		$wbr = chr(226).chr(128).chr(139);
		$words = explode(' ', $text);
		foreach($words as $wordnr => $word)
		{
			$word = preg_replace('|([,./\\>?!:;@-]+)(?=.)|', '$1 ', $word);
			$parts = explode(' ', $word);
			foreach($parts as $partnr => $part)
			{
				$len = strlen(utf8_decode($part));
				if (!$len)
				{
					continue;
				}
				$parts[$partnr] = preg_replace('/(.{'.ceil($len/ceil($len/$width)).'})(?=.)/u', '$1'.$wbr, $part);
			}
			$words[$wordnr] = join($wbr, $parts);
		}
		return join(' ', $words);
	}

/**
 * Removes prefix from a string.
 *
 * @param  string $str The string
 * @param  string $pfx The prefix
 * @return string
 */

	function strip_prefix($str, $pfx)
	{
		return preg_replace('/^'.preg_quote($pfx, '/').'/', '', $str);
	}

/** 
 * Sends an XML envelope.
 * 
 * Wraps an array of name => value tupels into an XML envelope,
 * supports one level of nested arrays at most.
 *
 * @param   array  $response
 * @return  string XML envelope
 * @package XML
 */

	function send_xml_response($response = array())
	{
		static $headers_sent = false;

		if (!$headers_sent)
		{
			ob_clean();
			header('Content-Type: text/xml; charset=utf-8');
			$out[] = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
			$headers_sent = true;
		}

		$default_response = array(
			'http-status' => '200 OK',
		);

		// Backfill default response properties.
		$response = $response + $default_response;

		txp_status_header($response['http-status']);
		$out[] = '<textpattern>';
		foreach ($response as $element => $value)
		{
			if (is_array($value))
			{
				$out[] = t."<$element>".n;
				foreach ($value as $e => $v)
				{
					// Character escaping in values; @see http://www.w3.org/TR/2000/WD-xml-c14n-20000119.html#charescaping
					$v = str_replace(array("\t", "\n", "\r"), array("&#x9;", "&#xA;", "&#xD;"), htmlentities($v, ENT_QUOTES, 'UTF-8'));
					$out[] = t.t."<$e value='$v' />".n;
				}
				$out[] = t."</$element>".n;
			}
			else
			{
				$value = str_replace(array("\t", "\n", "\r"), array("&#x9;", "&#xA;", "&#xD;"), htmlentities($value, ENT_QUOTES, 'UTF-8'));
				$out[] = t."<$element value='$value' />".n;
			}
		}
		$out[] = '</textpattern>';
		echo join(n, $out);
	}

/**
 * Sends a text/javascript response.
 *
 * @param   string $out The JavaScript
 * @since   4.4.0
 * @package Ajax
 */

	function send_script_response($out = '')
	{
		static $headers_sent = false;
		if (!$headers_sent)
		{
			ob_clean();
			header('Content-Type: text/javascript; charset=utf-8');
			txp_status_header('200 OK');
			$headers_sent = true;
		}
		echo ";\n".$out.";\n";
	}

/**
 * Display a modal client message in response to an AJAX request and halt execution.
 *
 * @param   string|array $thing The $thing[0] is the message's text; $thing[1] is the message's type (one of E_ERROR or E_WARNING, anything else meaning "success"; not used)
 * @since   4.5.0
 * @package Ajax
 */

	function modal_halt($thing)
	{
		global $app_mode, $theme;
		if ($app_mode == 'async')
		{
			send_script_response($theme->announce_async($thing, true));
			die();
		}
	}

/**
 * Sends an activity message to the client.
 *
 * @param   string|array $message The message
 * @param   int          $type    The type, either 0, E_ERROR, E_WARNING
 * @param   int          $flags   Flags, consisting of ANNOUNCE_ADAPTIVE | ANNOUNCE_ASYNC | ANNOUNCE_MODAL | ANNOUNCE_REGULAR
 * @package Announce
 * @since   4.6.0
 * @example
 * echo announce('My message', E_WARNING);
 */

	function announce($message, $type = 0, $flags = ANNOUNCE_ADAPTIVE)
	{
		global $app_mode, $theme;

		if (!is_array($message))
		{
			$message = array($message, $type);
		}

		if ($flags & ANNOUNCE_ASYNC || ($flags & ANNOUNCE_ADAPTIVE && $app_mode === 'async'))
		{
			return $theme->announce_async($message);
		}

		if ($flags & ANNOUNCE_MODAL)
		{
			return $theme->announce_async($message, true);
		}

		return $theme->announce($message);
	}

/**
 * Performs regular housekeeping.
 *
 * @access private
 */

	function janitor()
	{
		global $prefs, $auto_dst, $timezone_key, $is_dst;

		// Update DST setting.
		if ($auto_dst && $timezone_key)
		{
			$is_dst = timezone::is_dst(time(), $timezone_key);
			if ($is_dst != $prefs['is_dst'])
			{
				$prefs['is_dst'] = $is_dst;
				set_pref('is_dst', $is_dst, 'publish', 2);
			}
		}
	}

/**
 * Dealing with timezones.
 *
 * @package DateTime
 */

	class timezone
	{
		/**
		 * Stores a list of details about each timezone.
		 *
		 * @var array 
		 */

		private $_details;

		/**
		 * Stores a list of timezone offsets
		 *
		 * @var array 
		 */

		private $_offsets;

		/**
		 * Constructor.
		 */

		function __construct()
		{
			if (!timezone::is_supported())
            {
            	// Standard timezones as compiled by H.M. Nautical Almanac Office, June 2004
	            // http://aa.usno.navy.mil/faq/docs/world_tzones.html
	            $timezones = array(
	                -12, -11, -10, -9.5, -9, -8.5, -8, -7, -6, -5, -4, -3.5, -3, -2, -1,
	                0,
	                +1, +2, +3, +3.5, +4, +4.5, +5, +5.5, +6, +6.5, +7, +8, +9, +9.5, +10, +10.5, +11, +11.5, +12, +13, +14,
	            );

	            foreach ($timezones as $tz)
	            {
	            	// Fake timezone id
	            	$timezone_id = 'GMT'.sprintf('%+05.1f', $tz);
	            	$sign = ($tz >= 0 ? '+' : '');
	                $label = sprintf("GMT %s%02d:%02d", $sign, $tz, abs($tz - (int)$tz) * 60);
	                $this->_details[$timezone_id]['continent'] = gTxt('timezone_gmt');
	                $this->_details[$timezone_id]['city'] = $label;
	                $this->_details[$timezone_id]['offset'] = $tz * 3600;
	                $this->_offsets[$tz * 3600] = $timezone_id;
	            }
            }
            else
            {
				$continents = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia',
					'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');

				$server_tz = @date_default_timezone_get();
				$tzlist = timezone_abbreviations_list();
				foreach ($tzlist as $abbr => $timezones)
				{
					foreach ($timezones as $tz)
					{
						$timezone_id = $tz['timezone_id'];
						// $timezone_ids are not unique among abbreviations.
						if ($timezone_id && !isset($this->_details[$timezone_id]))
						{
							$parts = explode('/', $timezone_id);
							if (in_array($parts[0], $continents))
							{
								if (!empty($server_tz))
								{
									if (date_default_timezone_set($timezone_id))
									{
										$is_dst = date('I', time());
									}
								}

								$this->_details[$timezone_id]['continent'] = $parts[0];
								$this->_details[$timezone_id]['city'] = (isset($parts[1])) ? $parts[1] : '';
								$this->_details[$timezone_id]['subcity'] = (isset($parts[2])) ? $parts[2] : '';
								$this->_details[$timezone_id]['offset'] = date_offset_get(date_create()) - ($is_dst ? 3600 : 0);
								$this->_details[$timezone_id]['dst'] = $tz['dst'];
								$this->_details[$timezone_id]['abbr'] = strtoupper($abbr);

								// Guesstimate a timezone key for a given GMT offset.
								$this->_offsets[$tz['offset']] = $timezone_id;
							}
						}
					}
				}
			}

			if (!empty($server_tz))
			{
				date_default_timezone_set($server_tz);
			}
		}

		/**
		 * Render HTML &lt;select&gt; element for choosing a timezone.
		 *
		 * @param  string         $name        Element name
		 * @param  string         $value       Selected timezone
		 * @param  boolean        $blank_first Add empty first option
		 * @param  boolean|string $onchange
		 * @param  string         $select_id   HTML id attribute
		 * @return string         HTML markup
		 */

		function selectInput($name = '', $value = '', $blank_first = '', $onchange = '', $select_id = '')
		{
			if (!empty($this->_details))
			{
				$thiscontinent = '';
				$selected = false;

				ksort($this->_details);
				foreach ($this->_details as $timezone_id => $tz)
				{
					extract($tz);
					if ($value == $timezone_id)
					{
						$selected = true;
					}
					if ($continent !== $thiscontinent)
					{
						if ($thiscontinent !== '')
						{
							$out[] = n.'</optgroup>';
						}
						$out[] = n.'<optgroup label="'.gTxt($continent).'">';
						$thiscontinent = $continent;
					}

					$where = gTxt(str_replace('_', ' ', $city))
								.(!empty($subcity) ? '/'.gTxt(str_replace('_', ' ', $subcity)) : '').t
								/*."($abbr)"*/;
					$out[] = n.'<option value="'.txpspecialchars($timezone_id).'"'.($value == $timezone_id ? ' selected="selected"' : '').'>'.$where.'</option>';
				}
				$out[] = n.'</optgroup>';
				return n.'<select'.( $select_id ? ' id="'.$select_id.'"' : '' ).' name="'.$name.'"'.
					($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange).
					'>'.
					($blank_first ? n.'<option value=""'.($selected == false ? ' selected="selected"' : '').'></option>' : '').
					join('', $out).
					n.'</select>';
			}
			return '';
		}

		/**
		 * Build a matrix of timezone details.
		 *
		 * @return array Array of timezone details indexed by timezone key
		 */

		function details()
		{
			return $this->_details;
		}

		/**
		 * Find a timezone key matching a given GMT offset.
		 *
		 * NB: More than one key might fit any given GMT offset,
		 * thus the returned value is ambiguous and merely useful for presentation purposes.
		 *
		 * @param  int    $gmtoffset
		 * @return string timezone key
		 */

		function key($gmtoffset)
		{
			return isset($this->_offsets[$gmtoffset]) ? $this->_offsets[$gmtoffset] : '';
		}

		/**
		 * Is DST in effect?
		 *
		 * @param  int     $timestamp When?
		 * @param  string  $timezone_key Where?
		 * @return bool
		 */

		static function is_dst($timestamp, $timezone_key)
		{
			global $is_dst, $auto_dst;

			$out = $is_dst;
			if ($auto_dst && $timezone_key && timezone::is_supported())
			{
				$server_tz = @date_default_timezone_get();
				if ($server_tz)
				{
					// Switch to client timezone.
					if (date_default_timezone_set($timezone_key))
					{
						$out = date('I', $timestamp);
						// Restore server timezone.
						date_default_timezone_set($server_tz);
					}
				}
			}
			return $out;
		}

		/**
		 * Check for run-time timezone support.
		 *
		 * @return bool Timezone feature is enabled
		 */

		static function is_supported()
		{
			return !defined('NO_TIMEZONE_SUPPORT');	// user-definable emergency brake
		}
	}

/**
 * Installs localisation strings from a Textpack.
 *
 * Created strings get a well-known static modifcation date set in the past.
 * This is done to avoid tampering with lastmod dates used for RPC server
 * interactions, caching and update checks.
 *
 * @param   string $textpack      The Textpack to install
 * @param   bool   $add_new_langs If TRUE, installs strings for any included language
 * @return  int    Number of installed strings
 * @package L10n
 */

	function install_textpack($textpack, $add_new_langs = false)
	{
		$textpack = parse_textpack($textpack, get_pref('language', 'en-gb'));

		if (!$textpack)
		{
			return 0;
		}

		$installed_langs = safe_column('lang', 'txp_lang', "1 = 1 group by lang");
		$done = 0;

		foreach ($textpack as $translation)
		{
			extract($translation);

			if (!$add_new_langs && !in_array($lang, $installed_langs))
			{
				continue;
			}

			$where = "lang='".doSlash($lang)."' and name='".doSlash($name)."'";

			if (safe_count('txp_lang', $where))
			{
				safe_update(
					'txp_lang',
					"lastmod='2005-08-14',
					data='".doSlash($data)."',
					event='".doSlash($event)."',
					owner='".doSlash($owner)."'",
					$where
				);
			}
			else
			{
				safe_insert(
					'txp_lang',
					"lastmod='2005-08-14',
					data='".doSlash($data)."',
					event='".doSlash($event)."',
					owner='".doSlash($owner)."',
					lang='".doSlash($lang)."',
					name='".doSlash($name)."'"
				);
			}

			$done++;
		}

		return $done;
	}

/**
 * Converts a Textpack to an array.
 *
 * @param   string $textpack The Textpack
 * @param   string $language The default language
 * @param   string $owner    The default owner
 * @return  array            An array of translations
 * @since   4.6.0
 * @package L10n
 * @example
 * print_r(
 * 	parse_textpack("string => translation")
 * );
 */

	function parse_textpack($textpack, $language = LANG, $owner = LANG_OWNER_SITE)
	{
		$lines = explode(n, (string) $textpack);
		$out = array();
		$version = false;
		$lastmod = false;
		$event = false;

		foreach ($lines as $line)
		{
			$line = trim($line);

			// A comment line.
			if (preg_match('/^#[^@]/', $line, $m))
			{
				continue;
			}

			// Sets version and lastmod timestamp.
			if (preg_match('/^#@version\s+([^;\n]+);?([0-9]*)$/', $line, $m))
			{
				$version = $m[1];
				$lastmod = $m[2] !== false ? $m[2] : $lastmod;
				continue;
			}

			// Sets language.
			if (preg_match('/^#@language\s+(.+)$/', $line, $m))
			{
				$language = $m[1];
				continue;
			}

			// Sets owner.
			if (preg_match('/^#@owner\s+(.+)$/', $line, $m))
			{
				$owner = $m[1];
				continue;
			}

			// Sets event.
			if (preg_match('/^#@([a-zA-Z0-9_-]+)$/', $line, $m))
			{
				$event = $m[1];
				continue;
			}

			// Translation.
			if (preg_match('/^(\w+)\s*=>\s*(.+)$/', $line, $m))
			{
				if (!empty($m[1]) && !empty($m[2]))
				{
					$out[] = array(
						'name'    => $m[1],
						'lang'    => $language,
						'data'    => $m[2],
						'event'   => $event,
						'owner'   => $owner,
						'version' => $version,
						'lastmod' => $lastmod,
					);
				}
			}
		}

		return $out;
	}

/**
 * Generate a ciphered token.
 *
 * The token is reproducable, unique among sites and users, expires later.
 *
 * @return  string The token
 * @see     bouncer()
 * @package CSRF
 */

	function form_token()
	{
		static $token;
		global $txp_user;

		// Generate a ciphered token from the current user's nonce (thus valid for login time plus 30 days)
		// and a pinch of salt from the blog UID.
		if (empty($token))
		{
			$nonce = safe_field('nonce', 'txp_users', "name='".doSlash($txp_user)."'");
			$token = md5($nonce . get_pref('blog_uid'));
		}
		return $token;
	}

/**
 * Assert system requirements.
 *
 * @access private
 */

	function assert_system_requirements()
	{
		if (version_compare(REQUIRED_PHP_VERSION, PHP_VERSION) > 0)
		{
			txp_die('This server runs PHP version '.PHP_VERSION.'. Textpattern needs PHP version '. REQUIRED_PHP_VERSION. ' or better.');
		}
	}

/**
 * Validates admin steps and protects against CSRF attempts using tokens.
 *
 * This function takes an admin step and validates it against an array of valid steps.
 * The valid steps array indicates the step's token based session riding protection
 * needs.
 *
 * If the step requires CSRF token protection, and the request doesn't come with a
 * valid token, the request is terminated, defeating any CSRF attempts.
 *
 * If the $step isn't in valid steps, this function returns FALSE, but the request
 * isn't terminated. If the $step is valid and passes CSRF validation, returns
 * TRUE.
 *
 * @param   string  $step  Requested admin step
 * @param   array   $steps An array of valid steps with flag indicating CSRF needs, e.g. array('savething' => true, 'listthings' => false)
 * @return  bool    If the $step is valid, proceeds and returns TRUE. Dies on CSRF attempt.
 * @see     form_token()
 * @package CSRF
 * @example
 * global $step;
 * if (bouncer($step, array(
 * 	'browse'     => false,
 * 	'edit'       => false,
 * 	'save'       => true,
 * 	'multi_edit' => true,
 * )))
 * {
 * 	echo "The '{$step}' is valid.";
 * }
 */

	function bouncer($step, $steps)
	{
		global $event;

		if (empty($step))
		{
			return true;
		}

		// Validate step.
		if (!array_key_exists($step, $steps))
		{
			return false;
		}

		// Does this step require a token?
		if (!$steps[$step])
		{
			return true;
		}

		// Validate token.
		if (gps('_txp_token') === form_token())
		{
			return true;
		}

		die(gTxt('get_off_my_lawn', array('{event}' => $event, '{step}' => $step)));
	}

/**
 * Test whether the client accepts a certain response format.
 *
 * Discards formats with a quality factor below 0.1
 *
 * @param   string  $format One of 'html', 'txt', 'js', 'css', 'json', 'xml', 'rdf', 'atom', 'rss'
 * @return  boolean $format TRUE if accepted
 * @since   4.5.0
 * @package Network
 */

	function http_accept_format($format)
	{
		static $formats = array(
			'html' => array('text/html', 'application/xhtml+xml', '*/*'),
			'txt'  => array('text/plain', '*/*'),
			'js'   => array('application/javascript', 'application/x-javascript', 'text/javascript', 'application/ecmascript', 'application/x-ecmascript', '*/*'),
			'css'  => array('text/css', '*/*'),
			'json' => array('application/json', 'application/x-json', '*/*'),
			'xml'  => array('text/xml', 'application/xml', 'application/x-xml', '*/*'),
			'rdf'  => array('application/rdf+xml', '*/*'),
			'atom' => array('application/atom+xml', '*/*'),
			'rss'  => array('application/rss+xml', '*/*'),
		);
		static $accepts = array();
		static $q = array();

		if (empty($accepts))
		{
			// Build cache of accepted formats.
			$accepts = preg_split('/\s*,\s*/', serverSet('HTTP_ACCEPT'), null, PREG_SPLIT_NO_EMPTY);
			foreach ($accepts as $i => &$a)
			{
				// Sniff out quality factors if present.
				if (preg_match('/(.*)\s*;\s*q=([.0-9]*)/', $a, $m))
				{
					$a = $m[1];
					$q[$a] = floatval($m[2]);
				}
				else
				{
					$q[$a] = 1.0;
				}
				// Discard formats with quality factors below an arbitrary threshold
				// as jQuery adds a wildcard '*/*; q=0.01' to the 'Accepts' header for XHR requests.
				if ($q[$a] < 0.1)
				{
					unset($q[$a]);
					unset($accepts[$i]);
				}
			}
		}
		return isset($formats[$format]) ? count(array_intersect($formats[$format], $accepts)) > 0 : false;
	}

/**
 * Translates article status names into numerical status codes.
 *
 * @param  string $name Named status 'draft', 'hidden', 'pending', 'live', 'sticky'
 * @return int    Numerical status [1..5]
 */

	function getStatusNum($name)
	{
		$labels = array(
			'draft'   => 1,
			'hidden'  => 2,
			'pending' => 3,
			'live'    => 4,
			'sticky'  => 5
		);
		$status = strtolower($name);
		$num = empty($labels[$status]) ? 4 : $labels[$status];
		return $num;
	}

/**
 * Checks install's file integrity and returns results.
 *
 * Depending on the given $flags this function will either return
 * an array of file statuses, checksums or the digest of the install.
 * It can also return the parsed contents of the checksum file.
 *
 * @param   int        $flags Options are INTEGRITY_MD5 | INTEGRITY_STATUS | INTEGRITY_REALPATH | INTEGRITY_DIGEST
 * @return  array|bool Array of files and status, or FALSE on error
 * @since   4.6.0
 * @package Debug
 * @example
 * print_r(
 * 	check_file_integrity(INTEGRITY_MD5 | INTEGRITY_REALPATH)
 * );
 */

	function check_file_integrity($flags = INTEGRITY_STATUS)
	{
		static $files = null, $files_md5 = array(), $checksum_table = array();

		if ($files === null)
		{
			if ($cs = @file(txpath . '/checksums.txt'))
			{
				$files = array();

				foreach ($cs as $c)
				{
					if (preg_match('@^(\S+):(?: r?(\S+) | )\(?(.{32})\)?$@', trim($c), $m))
					{
						list (, $relative, $r, $md5) = $m;
						$file = realpath(txpath . $relative);
						$checksum_table[$relative] = $md5;

						if ($file === false)
						{
							$files[$relative] = INTEGRITY_MISSING;
							$files_md5[$relative] = false;
							continue;
						}

						if (!is_readable($file))
						{
							$files[$relative] = INTEGRITY_NOT_READABLE;
							$files_md5[$relative] = false;
							continue;
						}

						if (!is_file($file))
						{
							$files[$relative] = INTEGRITY_NOT_FILE;
							$files_md5[$relative] = false;
							continue;
						}

						$files_md5[$relative] = md5_file($file);

						if ($files_md5[$relative] !== $md5)
						{
							$files[$relative] = INTEGRITY_MODIFIED;
						}
						else
						{
							$files[$relative] = INTEGRITY_GOOD;
						}
					}
				}

				if (!get_pref('enable_xmlrpc_server', true))
				{
					unset(
						$files_md5['/../rpc/index.php'],
						$files_md5['/../rpc/TXP_RPCServer.php'],
						$files['/../rpc/index.php'],
						$files['/../rpc/TXP_RPCServer.php']
					);
				}
			}
			else
			{
				$files_md5 = $files = false;
			}
		}

		if ($flags & INTEGRITY_DIGEST)
		{
			return $files_md5 ? md5(implode(n, $files_md5)) : false;
		}

		if ($flags & INTEGRITY_TABLE)
		{
			return $checksum_table ? $checksum_table : false;
		}

		$return = $files;

		if ($flags & INTEGRITY_MD5)
		{
			$return = $files_md5;
		}

		if ($return && $flags & INTEGRITY_REALPATH)
		{
			$relative = array();

			foreach ($return as $path => $status)
			{
				$realpath = realpath(txpath.$path);
				$relative[!$realpath ? $path : $realpath] = $status;
			}

			return $relative;
		}

		return $return;
	}

<?php

/*
$HeadURL$
$LastChangedRevision$
*/

// -------------------------------------------------------------
	function deNull($in)
	{
		return (is_array($in) ? doArray($in, 'deNull') : strtr($in, array("\0" => '')));
	}

// -------------------------------------------------------------
	function deCRLF($in)
	{
		return (is_array($in) ? doArray($in, 'deCRLF') : strtr($in, array("\n" => '', "\r" => '')));
	}

// -------------------------------------------------------------
	function doArray($in,$function)
	{
		if(is_array($in))
		{
			return array_map($function, $in);
		}
		
		if(is_array($function))
		{
			return call_user_func($function, $in);
		}
		
		return $function($in);
	}

// -------------------------------------------------------------
	function doStrip($in)
	{
		return is_array($in) ? doArray($in, 'doStrip') : doArray($in, 'stripslashes');
	}

// -------------------------------------------------------------
	function doStripTags($in)
	{
		return is_array($in) ? doArray($in, 'doStripTags') : doArray($in,'strip_tags');
	}

// -------------------------------------------------------------
	function doDeEnt($in)
	{
		return doArray($in,'deEntBrackets');
	}

// -------------------------------------------------------------
	function deEntBrackets($in)
	{
		$array = array(
			'&#60;'  => '<',
			'&lt;'   => '<',
			'&#x3C;' => '<',
			'&#62;'  => '>',
			'&gt;'   => '>',
			'&#x3E;' => '>'
		);

		foreach($array as $k=>$v){
			$in = preg_replace("/".preg_quote($k)."/i",$v, $in);
		}
		return $in;
	}

// -------------------------------------------------------------
	function doSlash($in)
	{
		return doArray($in,'safe_escape');
	}

	/**
	 * A shell for htmlspecialchars() with $flags defaulting to ENT_QUOTES
	 *
	 * @param string $string 	The string being converted.
	 * @param int $flags 		A bitmask of one or more flags. The default is ENT_QUOTES
	 * @param string $encoding 	Defines encoding used in conversion. The default is UTF-8.
	 * @param bool $double_encode When double_encode is turned off PHP will not encode existing html entities, the default is to convert everything.
	 * @return string
	 * @see http://www.php.net/manual/function.htmlspecialchars.php
	 * @since 4.5.0
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

// -------------------------------------------------------------
	function doSpecial($in)
	{
		return doArray($in,'txpspecialchars');
	}

// -------------------------------------------------------------
	function _null($a)
	{
		return NULL;
	}
// -------------------------------------------------------------
	function array_null($in)
	{
		return array_map('_null', $in);
	}

// -------------------------------------------------------------
	function escape_title($title)
	{
		return strtr($title,
			array(
				'<' => '&#60;',
				'>' => '&#62;',
				"'" => '&#39;',
				'"' => '&#34;',
			)
		);
	}

/**
 * Escape special string characters like \n or \\ for JavaScript.
 *
 * @param string $js JavaScript input
 * @return	string	Escaped JavaScript
 * @since 4.4
 */

function escape_js($js)
{
	return addcslashes($js, "\\\'\"\n\r");
}

// -------------------------------------------------------------
// deprecated in 4.2.0
	function escape_output($str)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'txpspecialchars')), E_USER_NOTICE);
		return txpspecialchars($str);
	}

// -------------------------------------------------------------
// deprecated in 4.2.0
	function escape_tags($str)
	{
		trigger_error(gTxt('deprecated_function', array('{name}' => __FUNCTION__)), E_USER_NOTICE);
		return strtr($str,
			array(
				'<' => '&#60;',
				'>' => '&#62;',
			)
		);
	}

// -------------------------------------------------------------
	function escape_cdata($str)
	{
		return '<![CDATA['.str_replace(']]>', ']]]><![CDATA[]>', $str).']]>';
	}

//-------------------------------------------------------------
	function gTxt($var, $atts=array(), $escape='html')
	{
		global $textarray;

		if (!is_array($atts)) {
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
		if (isset($textarray[$v])) {
			$out = $textarray[$v];
			if ($out !== '') return strtr($out, $atts);
		}

		if ($atts)
			return $var.': '.join(', ', $atts);
		return $var;
	}

//-------------------------------------------------------------
/**
 * Localize client scripts
 *
 * @param string|array $var scalar or array of string keys
 * @param array $atts array or array of arrays of variable substitution pairs
 * @since 4.5.0
 */
	function gTxtScript($var, $atts = array())
	{
		global $textarray_script;

		if (!is_array($textarray_script)) {
			$textarray_script = array();
		}

		$data = (is_array($var) ? array_map('gTxt', $var, $atts) : (array)gTxt($var, $atts));
		$textarray_script = $textarray_script + array_combine((array)$var, $data);
	}

//-------------------------------------------------------------
	function gTime($timestamp)
	{
		return safe_strftime('%d&#160;%b&#160;%Y %X', $timestamp);
	}

// -------------------------------------------------------------
	function dmp()
	{
		static $f = FALSE;

		if(defined('txpdmpfile'))
		{
			global $prefs;

			if(!$f) $f = fopen($prefs['tempdir'].'/'.txpdmpfile, 'a');

			$stack = get_caller();
			fwrite($f, "\n[".$stack[0].t.safe_strftime('iso8601')."]\n");
		}

		$a = func_get_args();

		if(!$f) echo "<pre>".n;

		foreach ($a as $thing)
		{
			$out = is_scalar($thing) ? strval($thing) : var_export($thing, true);

			if ($f)
			{
				fwrite($f, $out."\n");
			}
			else
			{
				echo txpspecialchars($out), n;
			}
		}

		if(!$f) echo "</pre>".n;
	}

// -------------------------------------------------------------
	function load_lang($lang)
	{
		foreach(array($lang, 'en-gb') as $lang_code)
		{
			$rs = (txpinterface == 'admin')
				? safe_rows('name, data','txp_lang',"lang='".doSlash($lang_code)."'")
				: safe_rows('name, data','txp_lang',"lang='".doSlash($lang_code)."' AND ( event='public' OR event='common')");

			if (!empty($rs)) break;
		}

		$out = array();

		if (!empty($rs))
		{
			foreach ($rs as $a)
			{
				if (!empty($a['name'])) {
					$out[$a['name']] = $a['data'];
				}
			}
		}else{
			#backward compatibility stuff. Remove when necessary.
			$filename = is_file(txpath.'/lang/'.$lang.'.txt')
			?	txpath.'/lang/'.$lang.'.txt'
			:	txpath.'/lang/en-gb.txt';

			$file = @fopen($filename, "r");
			if ($file) {
				while (!feof($file)) {
					$line = fgets($file, 4096);
				if($line[0]=='#') continue;
				@list($name,$val) = explode(' => ',trim($line));
				$out[$name] = $val;
			 }
				@fclose($filename);
			}
		}

		return $out;
	}

// -------------------------------------------------------------
	function load_lang_dates($lang)
	{
		$filename = is_file(txpath.'/lang/'.$lang.'_dates.txt')?
			txpath.'/lang/'.$lang.'_dates.txt':
			txpath.'/lang/en-gb_dates.txt';
		$file = @file(txpath.'/lang/'.$lang.'_dates.txt','r');
		if(is_array($file)) {
			foreach($file as $line) {
				if($line[0]=='#' || strlen($line) < 2) continue;
				list($name,$val) = explode('=>',$line,2);
				$out[trim($name)] = trim($val);
			}
			return $out;
		}
		return false;
	}
// -------------------------------------------------------------

	function load_lang_event($event)
	{
		$lang = LANG;

		$installed = (false !== safe_field('name', 'txp_lang',"lang='".doSlash($lang)."' limit 1"));

		$lang_code = ($installed)? $lang : 'en-gb';

		$rs = safe_rows_start('name, data','txp_lang',"lang='".doSlash($lang_code)."' AND event='".doSlash($event)."'");

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

// -------------------------------------------------------------
// deprecated in 4.3.0
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

// -------------------------------------------------------------
	function add_privs($res, $perm = '1') // perm = '1,2,3'
	{
		global $txp_permissions;
		// Don't let them override privs that exist
		if (!isset($txp_permissions[$res]))
			$txp_permissions[$res] = $perm;
	}

// -------------------------------------------------------------
	function has_privs($res, $user='')
	{
		global $txp_user, $txp_permissions;
		static $privs;

		// If no user name is supplied, assume the current login name
		if (empty($user))
			$user = $txp_user;

		if (!isset($privs[$user]))
		{
			$privs[$user] = safe_field("privs", "txp_users", "name='".doSlash($user)."'");
		}

		if (isset($txp_permissions[$res]))
		{
			return in_array($privs[$user], explode(',', $txp_permissions[$res]));
		}

		else
		{
			return false;
		}
	}

// -------------------------------------------------------------
	function require_privs($res, $user='')
	{
		if (!has_privs($res, $user))
			exit(pageTop('Restricted').'<p class="restricted-area">'.
				gTxt('restricted_area').'</p>');
	}

	/**
	 * Get list of users having access to a resource
	 *
	 * @param string $res	resource, e.g. 'article.edit.published'
	 * @return array
	 * @since 4.5.0
	 */
	function the_privileged($res)
	{
		global $txp_permissions;
		if (isset($txp_permissions[$res])) {
			return safe_column('name', 'txp_users', "FIND_IN_SET(privs, '{$txp_permissions[$res]}') order by name asc");
		} else {
			return array();
		}
	}

// -------------------------------------------------------------
	function get_groups()
	{
		global $txp_groups;
		return doArray($txp_groups, 'gTxt');
	}

// -------------------------------------------------------------
	function sizeImage($name)
	{
		$size = @getimagesize($name);
		return(is_array($size)) ? $size[3] : false;
	}

// -------------------------------------------------------------
	function gps($thing) // checks GET and POST for a named variable, or creates it blank
	{
		$out = '';
		if (isset($_GET[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				$out = doStrip($_GET[$thing]);
			} else {
				$out = $_GET[$thing];
			}

			$out = doArray( $out, 'deCRLF' ); # Remove CRLF from Get parameters
		} elseif (isset($_POST[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				$out = doStrip($_POST[$thing]);
			} else {
				$out = $_POST[$thing]; # CRLF ok in posted vars
			}
		}

		$out = doArray($out, 'deNull'); # Remove Nulls to avoid string truncations in C calls (ie. All the filesystem routines)

		return $out;
	}

// -------------------------------------------------------------
	function gpsa($array) // performs gps() on an array of variable names
	{
		if(is_array($array)) {
			$out = array();
			foreach($array as $a) {
				$out[$a] = gps($a);
			}
			return $out;
		}
		return false;
	}

// -------------------------------------------------------------
	function ps($thing) // checks POST for a named variable, or creates it blank
	{
		$out = '';
		if (isset($_POST[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				$out = doStrip($_POST[$thing]);
			} else {
				$out = $_POST[$thing];
			}
		}

		$out = doArray($out, 'deNull');

		return $out;
	}

// -------------------------------------------------------------
	function psa($array) // performs ps on an array of variable names
	{
		foreach($array as $a) {
			$out[$a] = ps($a);
		}
		return $out;
	}

// -------------------------------------------------------------
	function psas($array) // same as above, but does strip_tags on post values
	{
		foreach($array as $a) {
			$out[$a] = doStripTags(ps($a));
		}
		return $out;
	}

// -------------------------------------------------------------
	function stripPost()
	{
		if (isset($_POST)) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_POST);
			} else {
				return $_POST;
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function serverSet($thing) // Get a var from $_SERVER global array, or create it
	{
		return (isset($_SERVER[$thing])) ? $_SERVER[$thing] : '';
	}

// -------------------------------------------------------------
	function remote_addr()
	{
		$ip = serverSet('REMOTE_ADDR');
		if (($ip == '127.0.0.1' || $ip == '::1' || $ip == '::ffff:127.0.0.1' || $ip == serverSet('SERVER_ADDR')) && serverSet('HTTP_X_FORWARDED_FOR')) {
			$ips = explode(', ', serverSet('HTTP_X_FORWARDED_FOR'));
			$ip = $ips[0];
		}
		return $ip;
	}

// -------------------------------------------------------------
 	function pcs($thing) //	Get a var from POST or COOKIE; if not, create it
	{
		if (isset($_COOKIE["txp_".$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_COOKIE["txp_".$thing]);
			} else return $_COOKIE["txp_".$thing];
		} elseif (isset($_POST[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_POST[$thing]);
			} else return $_POST[$thing];
		}
		return '';
	}

// -------------------------------------------------------------
 	function cs($thing) //	Get a var from COOKIE; if not, create it
	{
		if (isset($_COOKIE[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_COOKIE[$thing]);
			} else return $_COOKIE[$thing];
		}
		return '';
	}

// -------------------------------------------------------------
	function yes_no($status)
	{
		return ($status==0) ? (gTxt('no')) : (gTxt('yes'));
	}

// -------------------------------------------------------------
	function getmicrotime()
	{
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

// -------------------------------------------------------------
	function load_plugin($name, $force=false)
	{
		global $plugins, $plugins_ver, $prefs, $txp_current_plugin;

		if (is_array($plugins) and in_array($name,$plugins)) {
			return true;
		}

		if (!empty($prefs['plugin_cache_dir'])) {
			$dir = rtrim($prefs['plugin_cache_dir'], '/') . '/';
			# in case it's a relative path
			if (!is_dir($dir))
				$dir = rtrim(realpath(txpath.'/'.$dir), '/') . '/';
			if (is_file($dir . $name . '.php')) {
				$plugins[] = $name;
				set_error_handler("pluginErrorHandler");
				if (isset($txp_current_plugin)) $txp_parent_plugin = $txp_current_plugin;
				$txp_current_plugin = $name;
				include($dir . $name . '.php');
				$txp_current_plugin = (isset($txp_parent_plugin) ? $txp_parent_plugin : NULL);
				$plugins_ver[$name] = @$plugin['version'];
				restore_error_handler();
				return true;
			}
		}

		$rs = safe_row("name,code,version","txp_plugin", ($force ? '' : 'status = 1 AND '). "name='".doSlash($name)."'");
		if ($rs) {
			$plugins[] = $rs['name'];
			$plugins_ver[$rs['name']] = $rs['version'];

			set_error_handler("pluginErrorHandler");
			if (isset($txp_current_plugin)) $txp_parent_plugin = $txp_current_plugin;
			$txp_current_plugin = $rs['name'];
			eval($rs['code']);
			$txp_current_plugin = (isset($txp_parent_plugin) ? $txp_parent_plugin : NULL);
			restore_error_handler();

			return true;
		}

		return false;
	}

// -------------------------------------------------------------
	function require_plugin($name)
	{
		if (!load_plugin($name)) {
			trigger_error("Unable to include required plugin \"{$name}\"",E_USER_ERROR);
			return false;
		}
		return true;
	}

// -------------------------------------------------------------
	function include_plugin($name)
	{
		if (!load_plugin($name)) {
			trigger_error("Unable to include plugin \"{$name}\"",E_USER_WARNING);
			return false;
		}
		return true;
	}

// -------------------------------------------------------------
	function pluginErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		$error = array( E_WARNING => "Warning", E_NOTICE => "Notice", E_RECOVERABLE_ERROR => "Catchable fatal error",
                        E_USER_ERROR => "User_Error", E_USER_WARNING => "User_Warning", E_USER_NOTICE => "User_Notice");

		if (!($errno & error_reporting())) return;
		if ($production_status == 'live' || ($production_status != 'debug' && $errno == E_USER_NOTICE)) return;

		global $txp_current_plugin, $production_status;
		printf ("<pre>".gTxt('plugin_load_error').' <b>%s</b> -> <b>%s: %s on line %s</b></pre>',
				$txp_current_plugin, $error[$errno], $errstr, $errline);
		if ($production_status == 'debug')
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";
	}

// -------------------------------------------------------------
	function tagErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		$error = array( E_WARNING => "Warning", E_NOTICE => "Notice", E_RECOVERABLE_ERROR => "Textpattern Catchable fatal error",
                        E_USER_ERROR => "Textpattern Error", E_USER_WARNING => "Textpattern Warning", E_USER_NOTICE => "Textpattern Notice");

		if (!($errno & error_reporting())) return;
		if ($production_status == 'live') return;

		global $txp_current_tag, $txp_current_form, $pretext;
		$page = (empty($pretext['page']) ? gTxt('none') : $pretext['page']);
		if (!isset($txp_current_form)) $txp_current_form = gTxt('none');
		$locus = gTxt('while_parsing_page_form', array('{page}' => txpspecialchars($page), '{form}' => txpspecialchars($txp_current_form)));

		printf ("<pre>".gTxt('tag_error').' <b>%s</b> -> <b> %s: %s %s</b></pre>',
				txpspecialchars($txp_current_tag), $error[$errno], $errstr, $locus );
		if ($production_status == 'debug')
			{
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";

			$trace_msg = gTxt('tag_error').' '.$txp_current_tag.' -> '.$error[$errno].': '.$errstr.' '.$locus;
			trace_add( $trace_msg );
			}
	}

// -------------------------------------------------------------
	function feedErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		if ($production_status != 'debug') return;

		return tagErrorHandler($errno, $errstr, $errfile, $errline);
	}

// -------------------------------------------------------------
	function adminErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status, $theme, $event, $step;

		if (!error_reporting()) return;

		// When even a minimum environment is missing...
		if (!isset($production_status)) {
			echo '<pre>'.gTxt('internal_error').' "'.$errstr.'"'.n."in $errfile at line $errline".'</pre>';
			return;
		}

		if ($production_status == 'live' || ($production_status != 'debug' && $errno == E_USER_NOTICE)) {
			$backtrace = $msg = '';
		} else {
			$backtrace = '';
			$msg = gTxt('internal_error');

			if (has_privs('debug.verbose')) {
				$msg .= ' "'.$errstr.'"';
			};

			if (($production_status == 'debug')) {
				if (has_privs('debug.backtrace')) {
					$msg .= n."in $errfile at line $errline";
					$backtrace = join(n, get_caller(5,1));
				}
			}
		}

		$httpstatus = in_array($errno, array(E_ERROR, E_USER_ERROR)) ? '500' : '200';
		$out = "$msg.\n$backtrace";

		if (http_accept_format('html')) {
			if (!empty($backtrace)) {
				echo "<pre>$msg.</pre>".
					n.'<pre style="padding-left: 2em;" class="backtrace"><code>'.
					txpspecialchars($backtrace).'</code></pre>';
			} elseif (!empty($msg)) {
				echo is_object($theme) ? $theme->announce(array($out, E_ERROR), true) : "<pre>$out</pre>";
			}
			$c = array('in' => '', 'out' => '');
		} elseif (http_accept_format('js')) {
			send_script_response(
				is_object($theme) && !empty($msg) ?
				$theme->announce_async(array($out, E_ERROR), true) :
				"/* $out */"
			);
			$c = array('in' => '/* ', 'out' => ' */');
		} elseif (http_accept_format('xml')) {
			send_xml_response(array('http-status' => $httpstatus, 'internal_error' => "$out"));
			$c = array('in' => '<!-- ', 'out' => ' -->');
		} else {
			txp_die($msg, 500);
		}

		if ($production_status != 'live' && in_array($errno, array(E_ERROR, E_USER_ERROR))) {
			die($c['in'].gTxt('get_off_my_lawn', array('{event}' => $event, '{step}' => $step)).$c['out']);
		}
	}

// -------------------------------------------------------------
	function publicErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		$error = array( E_WARNING => "Warning", E_NOTICE => "Notice", E_USER_ERROR => "Textpattern Error",
		                E_USER_WARNING => "Textpattern Warning", E_USER_NOTICE => "Textpattern Notice");

		if (!($errno & error_reporting())) return;
		if ($production_status == 'live' || ($production_status != 'debug' && $errno == E_USER_NOTICE)) return;

		global $production_status;
		printf ("<pre>".gTxt('general_error').' <b>%s: %s on line %s</b></pre>',
			$error[$errno], $errstr, $errline);
		if ($production_status == 'debug')
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".txpspecialchars(join("\n", get_caller(10)))."</code></pre>";
	}

// -------------------------------------------------------------
	function load_plugins($type=0)
	{
		global $prefs, $plugins, $plugins_ver, $app_mode;

		if (!is_array($plugins)) $plugins = array();

		if (!empty($prefs['plugin_cache_dir'])) {
			$dir = rtrim($prefs['plugin_cache_dir'], '/') . '/';
			// in case it's a relative path
			if (!is_dir($dir))
				$dir = rtrim(realpath(txpath.'/'.$dir), '/') . '/';
			$files = glob($dir.'*.php');
			if ($files) {
				natsort($files);
				foreach ($files as $f) {
					load_plugin(basename($f, '.php'));
				}
			}
		}

		$admin = ($app_mode == 'async' && !AJAXALLY_CHALLENGED ? '4,5' : '1,3,4,5');
		$where = 'status = 1 AND type IN ('.($type ? $admin : '0,1,5').')';

		$rs = safe_rows("name, code, version", "txp_plugin", $where.' order by load_order');
		if ($rs) {
			$old_error_handler = set_error_handler("pluginErrorHandler");
			foreach($rs as $a) {
				if (!in_array($a['name'],$plugins)) {
					$plugins[] = $a['name'];
					$plugins_ver[$a['name']] = $a['version'];
					$GLOBALS['txp_current_plugin'] = $a['name'];
					$eval_ok = eval($a['code']);
					if ($eval_ok === FALSE)
						echo gTxt('plugin_load_error_above').strong($a['name']).n.br;
					unset($GLOBALS['txp_current_plugin']);
				}
			}
			restore_error_handler();
		}
	}

// -------------------------------------------------------------
	function register_callback($func, $event, $step='', $pre=0)
	{
		global $plugin_callback;

		$plugin_callback[] = array('function'=>$func, 'event'=>$event, 'step'=>$step, 'pre'=>$pre);
	}

// -------------------------------------------------------------
	function register_page_extension($func, $event, $step='', $top=0)
	{
		# For now this just does the same as register_callback
		register_callback($func, $event, $step, $top);
	}

// -------------------------------------------------------------
	function callback_event($event, $step='', $pre=0)
	{
		global $plugin_callback, $production_status;

		if (!is_array($plugin_callback))
			return '';

		$return_value = '';

		// any payload parameters?
		$argv = func_get_args();
		$argv = (count($argv) > 3) ? array_slice($argv, 3) : array();

		foreach ($plugin_callback as $c) {
			if ($c['event'] == $event and (empty($c['step']) or $c['step'] == $step) and $c['pre'] == $pre) {
				if (is_callable($c['function'])) {
					$return_value .= call_user_func_array($c['function'], array('event' => $event, 'step' => $step) + $argv);
				} elseif ($production_status == 'debug') {
					trigger_error(gTxt('unknown_callback_function', array('{function}' => callback_tostring($c['function']))), E_USER_WARNING);
				}
			}
		}
		return $return_value;
	}


// -------------------------------------------------------------
/**
 * Call an event's callback with two optional byref parameters
 * @param string $event
 * @param string $step
 * @param boolean $pre 0|1
 * @param mixed $data optional arguments for event handlers
 * @param mixed $options optional arguments for event handlers
 * @return array collection of return values from event handlers
 * @since 4.5.0
 */
 	function callback_event_ref($event, $step='', $pre=0, &$data=null, &$options=null)
	{
		global $plugin_callback, $production_status;

		if (!is_array($plugin_callback))
			return array();

		$return_value = array();

		foreach ($plugin_callback as $c) {
			if ($c['event'] == $event and (empty($c['step']) or $c['step'] == $step) and $c['pre'] == $pre) {
				if (is_callable($c['function'])) {
					// cannot call event handler via call_user_func() as this would dereference all arguments.
					// side effect: callback handler *must* be ordinary function, *must not* be class method in PHP <5.4 (@see https://bugs.php.net/bug.php?id=47160)
					$return_value[] = $c['function']($event, $step, $data, $options);
				} elseif ($production_status == 'debug') {
					trigger_error(gTxt('unknown_callback_function', array('{function}' => callback_tostring($c['function']))), E_USER_WARNING);
				}
			}
		}
		return $return_value;
	}

/**
 * @param string|array $callback a PHP "callback"
 * @return string $callback as a human-readable string
 * @since 4.5.0
 */
	function callback_tostring($callback)
	{
		if (is_array($callback)) {
			return join('::', array_filter($callback, 'is_scalar'));
		}
		elseif (!is_scalar($callback)) {
			return '';
		}
		return $callback;
	}

// -------------------------------------------------------------
	function register_tab($area, $event, $title)
	{
		global $plugin_areas;

		if (!isset($GLOBALS['event']) || ($GLOBALS['event'] !== 'plugin'))
		{
			$plugin_areas[$area][$title] = $event;
		}
	}

// -------------------------------------------------------------
	function pluggable_ui($event, $element, $default='')
	{
		$argv = func_get_args();
		$argv = array_slice($argv, 2);
		// custom user interface, anyone?
		// signature for called functions:
		// string my_called_func(string $event, string $step, string $default_markup[, mixed $context_data...])
		$ui = call_user_func_array('callback_event', array('event' => $event, 'step' => $element, 'pre' => 0) + $argv);
		// either plugins provided a user interface, or we render our own
		return ($ui === '')? $default : $ui;
	}

// -------------------------------------------------------------
	// deprecated in 4.2.0
	function getAtt($name, $default=NULL)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'lAtts')), E_USER_NOTICE);
		global $theseatts;
		return isset($theseatts[$name]) ? $theseatts[$name] : $default;
	}

// -------------------------------------------------------------
	// deprecated in 4.2.0
	function gAtt(&$atts, $name, $default=NULL)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'lAtts')), E_USER_NOTICE);
		return isset($atts[$name]) ? $atts[$name] : $default;
	}

// -------------------------------------------------------------
	function lAtts($pairs, $atts, $warn=1)
	{
		global $production_status;

		foreach($atts as $name => $value)
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

// -------------------------------------------------------------
	// deprecated in 4.5.0
	function select_buttons()
	{
		return
		gTxt('select').
		n.fInput('button','selall',gTxt('all'),'','select all','selectall();').
		n.fInput('button','selnone',gTxt('none'),'','select none','deselectall();').
		n.fInput('button','selrange',gTxt('range'),'','select range','selectrange();');
	}

// -------------------------------------------------------------
	function stripSpace($text, $force=0)
	{
		global $prefs;
		if ($force or !empty($prefs['attach_titles_to_permalinks']))
		{
			$text = trim(sanitizeForUrl($text), '-');
			if ($prefs['permalink_title_format']) {
				return (function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text));
			} else {
				return str_replace('-','',$text);
			}
		}
	}

// -------------------------------------------------------------
	function sanitizeForUrl($text)
	{
		// any overrides?
		$out = callback_event('sanitize_for_url', '', 0, $text);
		if ($out !== '') return $out;

		$in = $text;
		// Remove names entities and tags
		$text = preg_replace("/(^|&\S+;)|(<[^>]*>)/U","",dumbDown($text));
		// Dashify high-order chars leftover from dumbDown()
		$text = preg_replace("/[\x80-\xff]/","-",$text);
		// Collapse spaces, minuses, (back-)slashes and non-words
		$text = preg_replace('/[\s\-\/\\\\]+/', '-', trim(preg_replace('/[^\w\s\-\/\\\\]/', '', $text)));
		// Remove all non-whitelisted characters
		$text = preg_replace("/[^A-Za-z0-9\-_]/","",$text);
		// Sanitizing shouldn't leave us with plain nothing to show.
		// Fall back on percent-encoded URLs as a last resort for RFC 1738 conformance.
		if (empty($text) || $text == '-')
		{
			$text = rawurlencode($in);
		}
		return $text;
	}

// -------------------------------------------------------------
	function sanitizeForFile($text)
	{
		// any overrides?
		$out = callback_event('sanitize_for_file', '', 0, $text);
		if ($out !== '') return $out;

		// Remove control characters and " * \ : < > ? / |
		$text = preg_replace('/[\x00-\x1f\x22\x2a\x2f\x3a\x3c\x3e\x3f\x5c\x7c\x7f]+/', '', $text);
		// Remove duplicate dots and any leading or trailing dots/spaces
		$text = preg_replace('/[.]{2,}/', '.', trim($text, '. '));
		return $text;
	}

// -------------------------------------------------------------
	function sanitizeForPage($text)
	{
		// any overrides?
		$out = callback_event('sanitize_for_page', '', 0, $text);
		if ($out !== '') return $out;

		return trim(preg_replace('/[<>&"\']/', '', $text));
	}

// -------------------------------------------------------------
	function dumbDown($str, $lang=LANG)
	{
		static $array;
		if (empty($array[$lang])) {
			$array[$lang] = array( // nasty, huh?.
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
				'&szlig;'=>'ss'
			);


			if (is_file(txpath.'/lib/i18n-ascii.txt')) {
				$i18n = parse_ini_file(txpath.'/lib/i18n-ascii.txt', true);
				# load the global map
				if (isset($i18n['default']) && is_array($i18n['default'])) {
					$array[$lang] = array_merge($array[$lang], $i18n['default']);
					# base language overrides: 'de-AT' applies the 'de' section
					if (preg_match('/([a-zA-Z]+)-.+/', $lang, $m)) {
						if (isset($i18n[$m[1]]) && is_array($i18n[$m[1]]))
							$array[$lang] = array_merge($array[$lang], $i18n[$m[1]]);
					};
					# regional language overrides: 'de-AT' applies the 'de-AT' section
					if (isset($i18n[$lang]) && is_array($i18n[$lang]))
						$array[$lang] = array_merge($array[$lang], $i18n[$lang]);
				}
				# load an old file (no sections) just in case
				else
					$array[$lang] = array_merge($array[$lang], $i18n);
			}
		}

		return strtr($str, $array[$lang]);
	}

// -------------------------------------------------------------
	function clean_url($url)
	{
		return preg_replace("/\"|'|(?:\s.*$)/",'',$url);
	}

// -------------------------------------------------------------
	function noWidow($str)
	{
		// replace the last space with a nbsp
		if (REGEXP_UTF8 == 1)
			return preg_replace('@[ ]+([[:punct:]]?[\p{L}\p{N}\p{Pc}]+[[:punct:]]?)$@u', '&#160;$1', rtrim($str));
		return preg_replace('@[ ]+([[:punct:]]?\w+[[:punct:]]?)$@', '&#160;$1', rtrim($str));
	}

// -------------------------------------------------------------
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

			$hosts = $rbl ? @gethostbynamel($rip.'.'.trim($rbl, '. ').'.') : FALSE;

			if ($hosts and (!isset($codes) or array_intersect($hosts, $codes)))
			{
				$listed[] = $rbl;
			}
		}

		return (!empty($listed)) ? join(', ', $listed) : false;
	}

// -------------------------------------------------------------
	function is_logged_in($user = '')
	{
		$name = substr(cs('txp_login_public'), 10);

		if (!strlen($name) or strlen($user) and $user !== $name)
		{
			return FALSE;
		}

		$rs = safe_row('nonce, name, RealName, email, privs', 'txp_users', "name = '".doSlash($name)."'");

		if ($rs and substr(md5($rs['nonce']), -10) === substr(cs('txp_login_public'), 0, 10))
		{
			unset($rs['nonce']);
			return $rs;
		}
		else
		{
			return FALSE;
		}
	}

// -------------------------------------------------------------
	function updateSitePath($here)
	{
		$here = doSlash($here);
		$rs = safe_field ("name",'txp_prefs',"name = 'path_to_site'");
		if (!$rs) {
			safe_insert("txp_prefs","prefs_id=1,name='path_to_site',val='$here'");
		} else {
			safe_update('txp_prefs',"val='$here'","name='path_to_site'");
		}
	}

// -------------------------------------------------------------
	function splat($text)
	{
		$atts  = array();

		if (preg_match_all('@(\w+)\s*=\s*(?:"((?:[^"]|"")*)"|\'((?:[^\']|\'\')*)\'|([^\s\'"/>]+))@s', $text, $match, PREG_SET_ORDER))
		{
			foreach ($match as $m)
			{
				switch (count($m))
				{
					case 3:
						$val = str_replace('""', '"', $m[2]);
						break;
					case 4:
						$val = str_replace("''", "'", $m[3]);

						if (strpos($m[3], '<txp:') !== FALSE)
						{
							trace_add("[attribute '".$m[1]."']");
							$val = parse($val);
							trace_add("[/attribute]");
						}

						break;
					case 5:
						$val = $m[4];
						trigger_error(gTxt('attribute_values_must_be_quoted'), E_USER_WARNING);
						break;
				}

				$atts[strtolower($m[1])] = $val;
			}

		}

		return $atts;
	}

// -------------------------------------------------------------
	function maxMemUsage($message = 'none', $returnit = 0)
	{
		static $memory_top = 0;
		static $memory_message;

		if (is_callable('memory_get_usage'))
		{
			$memory_now = memory_get_usage();
			if ($memory_now > $memory_top)
			{
				$memory_top = $memory_now;
				$memory_message = $message;
			}
		}

		if ($returnit != 0)
		{
			if (is_callable('memory_get_usage'))
				return n.comment(sprintf('Memory: %sKb, %s',
					ceil($memory_top/1024),$memory_message));
			else
				return n.comment('Memory: no info available');
		}
	}

// -------------------------------------------------------------
	function strip_rn($str)
	{
		return strtr($str, "\r\n", '  ');
	}

// -------------------------------------------------------------

	function is_valid_email($address)
	{
		return (bool) filter_var($address, FILTER_VALIDATE_EMAIL);
	}

// -------------------------------------------------------------

	function txpMail($to_address, $subject, $body, $reply_to = null)
	{
		global $txp_user, $prefs;

		// if mailing isn't possible, don't even try
		if (is_disabled('mail'))
		{
			return false;
		}

		// Likely sending passwords
		if (isset($txp_user))
		{
			if (is_valid_email($prefs['publisher_email'])) {
				// explicit publisher email address preferred
				$RealName = safe_field('RealName', 'txp_users', "name = '".doSlash($txp_user)."'");
				$email = $prefs['publisher_email'];
			} else {
				// default: current user invites new users using her personal email address
				extract(safe_row('RealName, email', 'txp_users', "name = '".doSlash($txp_user)."'"));
			}
		}

		// Likely sending comments -> "to" equals "from"
		else
		{
			extract(safe_row('RealName, email', 'txp_users', "email = '".doSlash($to_address)."'"));
		}

		if ($prefs['override_emailcharset'] and is_callable('utf8_decode'))
		{
			$charset = 'ISO-8859-1';

			$RealName = utf8_decode($RealName);
			$subject = utf8_decode($subject);
			$body = utf8_decode($body);
		}

		else
		{
			$charset = 'UTF-8';
		}

		$RealName = encode_mailheader(strip_rn($RealName), 'phrase');
		$subject = encode_mailheader(strip_rn($subject), 'text');
		$email = strip_rn($email);

		if (!is_null($reply_to))
		{
			$reply_to = strip_rn($reply_to);
		}

		$sep = !IS_WIN ? "\n" : "\r\n";

		$body = str_replace("\r\n", "\n", $body);
		$body = str_replace("\r", "\n", $body);
		$body = str_replace("\n", $sep, $body);

		$headers = "From: $RealName <$email>".
			$sep.'Reply-To: '.( isset($reply_to) ? $reply_to : "$RealName <$email>" ).
			$sep.'X-Mailer: Textpattern'.
			$sep.'Content-Transfer-Encoding: 8bit'.
			$sep.'Content-Type: text/plain; charset="'.$charset.'"'.
			$sep;

		if (is_valid_email($prefs['smtp_from']))
		{
			if (IS_WIN)
			{
				ini_set('sendmail_from', $prefs['smtp_from']);
			}
			elseif (!ini_get('safe_mode'))
			{
				return mail($to_address, $subject, $body, $headers, '-f'.$prefs['smtp_from']);
			}
		}

		return mail($to_address, $subject, $body, $headers);
	}

// -------------------------------------------------------------
	function encode_mailheader($string, $type)
	{
		global $prefs;
		if (!strstr($string,'=?') and !preg_match('/[\x00-\x1F\x7F-\xFF]/', $string)) {
			if ("phrase" == $type) {
				if (preg_match('/[][()<>@,;:".\x5C]/', $string)) {
					$string = '"'. strtr($string, array("\\" => "\\\\", '"' => '\"')) . '"';
				}
			}
			elseif ( "text" != $type) {
				trigger_error( 'Unknown encode_mailheader type', E_USER_WARNING);
			}
			return $string;
		}
		if ($prefs['override_emailcharset'] and is_callable('utf8_decode')) {
			$start = '=?ISO-8859-1?B?';
			$pcre  = '/.{1,42}/s';
		}
		else {
			$start = '=?UTF-8?B?';
			$pcre  = '/.{1,45}(?=[\x00-\x7F\xC0-\xFF]|$)/s';
		}
		$end = '?=';
		$sep = IS_WIN ? "\r\n" : "\n";
		preg_match_all($pcre, $string, $matches);
		return $start . join($end.$sep.' '.$start, array_map('base64_encode',$matches[0])) . $end;
	}

// -------------------------------------------------------------
	function stripPHP($in)
	{
		return preg_replace("/".chr(60)."\?(?:php)?|\?".chr(62)."/i",'',$in);
	}

// -------------------------------------------------------------

/**
 * PEDRO:
 * Helper functions for common textpattern event files actions.
 * Code refactoring from original files. Intended to do easy and less error
 * prone the future build of new textpattern extensions, and to add new
 * events to multiedit forms.
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

// -------------------------------------------------------------
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

// -------------------------------------------------------------
// DEPRECATED in v4.5.0: use multi_edit() instead
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

// -------------------------------------------------------------

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

// -------------------------------------------------------------
	function since($stamp)
	{
		$diff = (time() - $stamp);
		if ($diff <= 3600) {
			$mins = round($diff / 60);
			$since = ($mins <= 1)
			?	($mins==1)
				?	'1 '.gTxt('minute')
				:	gTxt('a_few_seconds')
			:	"$mins ".gTxt('minutes');
		} else if (($diff <= 86400) && ($diff > 3600)) {
			$hours = round($diff / 3600);
			$since = ($hours <= 1) ? '1 '.gTxt('hour') : "$hours ".gTxt('hours');
		} else if ($diff >= 86400) {
			$days = round($diff / 86400);
			$since = ($days <= 1) ? "1 ".gTxt('day') : "$days ".gTxt('days');
		}
		return $since.' '.gTxt('ago'); // sorry, this needs to be hacked until a truly multilingual version is done
	}

// -------------------------------------------------------------
// Calculate the offset between the server local time and the
// user's selected time zone at a given point in time
	function tz_offset($timestamp = NULL)
	{
		global $gmtoffset, $timezone_key;

		if (is_null($timestamp)) $timestamp = time();

		extract(getdate($timestamp));
		$serveroffset = gmmktime($hours,$minutes,0,$mon,$mday,$year) - mktime($hours,$minutes,0,$mon,$mday,$year);

		$real_dst = timezone::is_dst($timestamp, $timezone_key);
		return $gmtoffset - $serveroffset + ($real_dst ? 3600 : 0);
	}

// -------------------------------------------------------------
// Format a time, respecting the locale and local time zone,
// and make sure the output string is safe for UTF-8
	function safe_strftime($format, $time='', $gmt=0, $override_locale='')
	{
		global $locale;
		$old_locale = $locale;

		if (!$time)
			$time = time();

		# we could add some other formats here
		if ($format == 'iso8601' or $format == 'w3cdtf') {
			$format = '%Y-%m-%dT%H:%M:%SZ';
			$gmt = 1;
		}
		elseif ($format == 'rfc822') {
			$format = '%a, %d %b %Y %H:%M:%S GMT';
			$gmt = 1;
			$override_locale = 'en-gb';
		}

		if ($override_locale)
			getlocale($override_locale);

		if ($format == 'since')
			$str = since($time);
		elseif ($gmt)
			$str = gmstrftime($format, $time);
		else
			$str = strftime($format, $time + tz_offset($time));

		@list($lang, $charset) = explode('.', $locale);
		if (empty($charset))
			$charset = 'ISO-8859-1';
		elseif (IS_WIN and is_numeric($charset))
			// Score -1 for consistent naming conventions
			$charset = 'Windows-'.$charset;

		if ($charset != 'UTF-8' and $format != 'since') {
			$new = '';
			if (is_callable('iconv'))
				$new = @iconv($charset, 'UTF-8', $str);

			if ($new)
				$str = $new;
			elseif (is_callable('utf8_encode'))
				$str = utf8_encode($str);
		}

		# revert to the old locale
		if ($override_locale)
			$locale = setlocale(LC_ALL, $old_locale);

		return $str;
	}

// -------------------------------------------------------------
// Convert a time string from the Textpattern time zone to GMT
	function safe_strtotime($time_str)
	{
		$ts = strtotime($time_str);
		return strtotime($time_str, time() + tz_offset($ts)) - tz_offset($ts);
	}

// -------------------------------------------------------------
	function myErrorHandler($errno, $errstr, $errfile, $errline)
	{
		# error_reporting() returns 0 when the '@' suppression
		# operator is used
		if (!error_reporting())
			return;

		echo '<pre>'.n.n."$errno: $errstr in $errfile at line $errline\n";
		# Requires PHP 4.3
		if (is_callable('debug_backtrace')) {
			echo "Backtrace:\n";
			$trace = debug_backtrace();
			foreach($trace as $ent) {
				if(isset($ent['file'])) echo $ent['file'].':';
				if(isset($ent['function'])) {
					echo $ent['function'].'(';
					if(isset($ent['args'])) {
						$args='';
						foreach($ent['args'] as $arg) { $args.=$arg.','; }
						echo rtrim($args,',');
					}
					echo ') ';
				}
				if(isset($ent['line'])) echo 'at line '.$ent['line'].' ';
				if(isset($ent['file'])) echo 'in '.$ent['file'];
				echo "\n";
			}
		}
		echo "</pre>";
	}

// -------------------------------------------------------------
	function find_temp_dir()
	{
		global $path_to_site, $img_dir;

		if (IS_WIN) {
			$guess = array(txpath.DS.'tmp', getenv('TMP'), getenv('TEMP'), getenv('SystemRoot').DS.'Temp', 'C:'.DS.'Temp', $path_to_site.DS.$img_dir);
			foreach ($guess as $k=>$v)
				if (empty($v)) unset($guess[$k]);
		}
		else
			$guess = array(txpath.DS.'tmp', '', DS.'tmp', $path_to_site.DS.$img_dir);

		foreach ($guess as $dir) {
			$tf = @tempnam($dir, 'txp_');
			if ($tf) $tf = realpath($tf);
			if ($tf and file_exists($tf)) {
				unlink($tf);
				return dirname($tf);
			}
		}

		return false;
	}

// -------------------------------------------------------------
	function get_uploaded_file($f, $dest='')
	{
		global $tempdir;

		if (!is_uploaded_file($f))
			return false;

		if ($dest) {
			$newfile = $dest;
		}
		else {
			$newfile = tempnam($tempdir, 'txp_');
			if (!$newfile)
				return false;
		}

		# $newfile is created by tempnam(), but move_uploaded_file
		# will overwrite it
		if (move_uploaded_file($f, $newfile))
			return $newfile;
	}

// --------------------------------------------------------------
	function set_error_level($level)
	{

		if ($level == 'debug') {
			// We need to violate/disable E_STRICT for PHP 4.x compatibility
			// E_STRICT bitmask calculation stems from the variations for E_ALL in PHP 4.x, 5.{0,1,2,3}, and 5.4+
			// E_STRICT is defined since PHP 5.x and is set in E_ALL in PHP 5.4
			error_reporting(E_ALL & ~(defined('E_STRICT') ? E_STRICT : 0));
		}
		elseif ($level == 'live') {
			// don't show errors on screen
			$suppress = E_WARNING | E_NOTICE;
			if (defined('E_STRICT') && (E_ALL & E_STRICT)) $suppress |= E_STRICT;
			if (defined('E_DEPRECATED')) $suppress |= E_DEPRECATED;
			error_reporting(E_ALL ^ $suppress);
			@ini_set("display_errors","1");
		}
		else {
			// default is 'testing': display everything except notices and strict
			error_reporting((E_ALL ^ E_NOTICE) & ~(defined('E_STRICT') ? E_STRICT : 0));
		}
	}


// -------------------------------------------------------------
	function shift_uploaded_file($f, $dest)
	{
		// Rename might not work, but it's worth a try
		if (@rename($f, $dest))
			return true;

		if (@copy($f, $dest)) {
			unlink($f);
			return true;
		}
	}
// -------------------------------------------------------------
	function upload_get_errormsg($err_code)
	{
		$msg = '';
		switch ($err_code)
		{
				// Value: 0; There is no error, the file uploaded with success.
			case UPLOAD_ERR_OK         : $msg = '';break;
				// Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
			case UPLOAD_ERR_INI_SIZE   : $msg = gTxt('upload_err_ini_size');break;
				// Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
			case UPLOAD_ERR_FORM_SIZE  : $msg = gTxt('upload_err_form_size');break;
				// Value: 3; The uploaded file was only partially uploaded.
			case UPLOAD_ERR_PARTIAL    : $msg = gTxt('upload_err_partial');break;
				// Value: 4; No file was uploaded.
			case UPLOAD_ERR_NO_FILE    : $msg = gTxt('upload_err_no_file');break;
				// Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
			case UPLOAD_ERR_NO_TMP_DIR : $msg = gTxt('upload_err_tmp_dir');break;
				// Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.
			case UPLOAD_ERR_CANT_WRITE : $msg = gTxt('upload_err_cant_write');break;
				// Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0.
			case UPLOAD_ERR_EXTENSION  : $msg = gTxt('upload_err_extension');break;
		}
		return $msg;
	}

/**
 * Formats a file size
 *
 * @param	int		$bytes		Size in bytes
 * @param	int		$decimals	Number of decimals
 * @param	string	$format		The format the size is represented
 * @return	string				Formatted file size
 */

	function format_filesize($bytes, $decimals=2, $format='')
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

// -------------------------------------------------------------
	// for b/c only
	function is_windows()
	{
		return IS_WIN;
	}

// -------------------------------------------------------------
	function is_cgi()
	{
		return IS_CGI;
	}

// -------------------------------------------------------------
	function is_mod_php()
	{
		return IS_APACHE;
	}

// -------------------------------------------------------------

	function is_disabled($function)
	{
		static $disabled;

		if (!isset($disabled))
		{
			$disabled = do_list(ini_get('disable_functions'));
		}

		return in_array($function, $disabled);
	}

// --------------------------------------------------------------
	function build_file_path($base,$path)
	{
		$base = rtrim($base,'/\\');
		$path = ltrim($path,'/\\');

		return $base.DIRECTORY_SEPARATOR.$path;
	}

// --------------------------------------------------------------
	function get_author_name($name)
	{
		static $authors = array();

		if (isset($authors[$name]))
			return $authors[$name];

		$realname = fetch('RealName','txp_users','name',doSlash($name));
		$authors[$name] = $realname;
		return ($realname) ? $realname : $name;
	}

// --------------------------------------------------------------
	function get_author_email($name)
	{
		static $authors = array();

		if (isset($authors[$name]))
			return $authors[$name];

		$email = fetch('email','txp_users','name',doSlash($name));
		$authors[$name] = $email;
		return $email;
	}

// --------------------------------------------------------------
	function has_single_author($table, $col='author')
	{
		return (safe_field('COUNT(name)', 'txp_users', '1=1') <= 1) &&
			(safe_field('COUNT(DISTINCT('.doSlash($col).'))', doSlash($table), '1=1') <= 1);
	}

// --------------------------------------------------------------
	function EvalElse($thing, $condition)
	{
		global $txp_current_tag;
		static $gTxtTrue = NULL, $gTxtFalse;

		if (empty($gTxtTrue))
		{
			$gTxtTrue = gTxt('true');
			$gTxtFalse = gTxt('false');
		}

		trace_add("[$txp_current_tag: ".($condition ? $gTxtTrue : $gTxtFalse)."]");

		$els = strpos($thing, '<txp:else');

		if ($els === FALSE)
		{
			return $condition ? $thing : '';
		}
		elseif ($els === strpos($thing, '<txp:'))
		{
			return $condition
				? substr($thing, 0, $els)
				: substr($thing, strpos($thing, '>', $els) + 1);
		}

		$tag    = FALSE;
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
					return $condition
						? $str
						: substr($thing, strlen($str)+strlen($chunk));
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

		return $condition ? $thing : '';
	}

// --------------------------------------------------------------
	function fetch_form($name)
	{
		static $forms = array();

		if (isset($forms[$name]))
			$f = $forms[$name];
		else {
			$row = safe_row('Form', 'txp_form',"name='".doSlash($name)."'");
			if (!$row) {
				trigger_error(gTxt('form_not_found').': '.$name);
				return;
			}
			$f = $row['Form'];
			$forms[$name] = $f;
		}

		trace_add('['.gTxt('form').': '.$name.']');
		return $f;
	}

// --------------------------------------------------------------
	function parse_form($name)
	{
		global $txp_current_form;
		static $stack = array();

		$f = fetch_form($name);
		if ($f) {
			if (in_array($name, $stack)) {
				trigger_error(gTxt('form_circular_reference', array('{name}' => $name)));
				return;
			}
			$old_form = $txp_current_form;
			$txp_current_form = $stack[] = $name;
			$out = parse($f);
			$txp_current_form = $old_form;
			array_pop($stack);
			return $out;
		}
	}

// --------------------------------------------------------------
	function fetch_category_title($name, $type='article')
	{
		static $cattitles = array();
		global $thiscategory;

		if (isset($cattitles[$type][$name]))
			return $cattitles[$type][$name];

		if(!empty($thiscategory['title']) && $thiscategory['name'] == $name && $thiscategory['type'] == $type)
		{
			$cattitles[$type][$name] = $thiscategory['title'];
			return $thiscategory['title'];
		}

		$f = safe_field('title','txp_category',"name='".doSlash($name)."' and type='".doSlash($type)."'");
		$cattitles[$type][$name] = $f;
		return $f;
	}

// -------------------------------------------------------------
	function fetch_section_title($name)
	{
		static $sectitles = array();
		global $thissection;

		// try cache
		if (isset($sectitles[$name]))
			return $sectitles[$name];

		// try global set by section_list()
		if(!empty($thissection['title']) && $thissection['name'] == $name)
		{
			$sectitles[$name] = $thissection['title'];
			return $thissection['title'];
		}

		if($name == 'default' or empty($name))
			return '';

		$f = safe_field('title','txp_section',"name='".doSlash($name)."'");
		$sectitles[$name] = $f;
		return $f;
	}

// -------------------------------------------------------------
	function update_comments_count($id)
	{
		$id = assert_int($id);
		$thecount = safe_field('count(*)','txp_discuss','parentid='.$id.' and visible='.VISIBLE);
		$thecount = assert_int($thecount);
		$updated = safe_update('textpattern','comments_count='.$thecount,'ID='.$id);
		return ($updated) ? true : false;
	}

// -------------------------------------------------------------
	function clean_comment_counts($parentids)
	{
		$parentids = array_map('assert_int',$parentids);
		$rs = safe_rows_start('parentid, count(*) as thecount','txp_discuss','parentid IN ('.implode(',',$parentids).') AND visible='.VISIBLE.' group by parentid');
		if (!$rs) return;

		$updated = array();
		while($a = nextRow($rs)) {
			safe_update('textpattern',"comments_count=".$a['thecount'],"ID=".$a['parentid']);
			$updated[] = $a['parentid'];
		}
		// We still need to update all those, that have zero comments left.
		$leftover = array_diff($parentids, $updated);
		if ($leftover)
			safe_update('textpattern',"comments_count=0","ID IN (".implode(',',$leftover).")");
	}

// -------------------------------------------------------------

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

//-------------------------------------------------------------
	function update_lastmod() {
		safe_upsert("txp_prefs", "val = now()", "name = 'lastmod'");
	}

//-------------------------------------------------------------
	function get_lastmod($unix_ts=NULL) {
		global $prefs;

		if ($unix_ts === NULL)
			$unix_ts = @strtotime($prefs['lastmod']);

		# check for future articles that are now visible
		if ($max_article = safe_field('unix_timestamp(Posted)', 'textpattern', "Posted <= now() and Status >= 4 order by Posted desc limit 1")) {
			$unix_ts = max($unix_ts, $max_article);
		}

		return $unix_ts;
	}

//-------------------------------------------------------------
	function handle_lastmod($unix_ts=NULL, $exit=1) {
		global $prefs;
		extract($prefs);

		if($send_lastmod and $production_status == 'live') {
			$unix_ts = get_lastmod($unix_ts);

			# make sure lastmod isn't in the future
			$unix_ts = min($unix_ts, time());
			# or too far in the past (7 days)
			$unix_ts = max($unix_ts, time() - 3600*24*7);

			$last = safe_strftime('rfc822', $unix_ts, 1);
			header("Last-Modified: $last");
			header('Cache-Control: no-cache');

			$hims = serverset('HTTP_IF_MODIFIED_SINCE');
			if ($hims and @strtotime($hims) >= $unix_ts) {
				log_hit('304');
				if (!$exit)
					return array('304', $last);
				txp_status_header('304 Not Modified');
				# some mod_deflate versions have a bug that breaks subsequent
				# requests when keepalive is used.  dropping the connection
				# is the only reliable way to fix this.
				if (empty($lastmod_keepalive))
					header('Connection: close');
				header('Content-Length: 0');
				# discard all output
				while (@ob_end_clean());
				exit;
			}

			if (!$exit)
				return array('200', $last);
		}
	}

//-------------------------------------------------------------
	function set_pref($name, $val, $event='publish',  $type=0, $html='text_input', $position=0, $is_private=PREF_GLOBAL)
	{
		global $txp_user;

		$user_name = '';
		if ($is_private == PREF_PRIVATE) {
			if (empty($txp_user))
				return false;

			$user_name = 'user_name = \''.doSlash($txp_user).'\'';
		}

		$name = doSlash($name);
		$val = doSlash($val);
		$event = doSlash($event);
		$type = (int) $type;
		$html = doSlash($html);
		$position = (int) $position;

		if (!safe_row('name', 'txp_prefs', "name = '$name'" . ($user_name ? " AND $user_name" : ''))) {
			$user_name = ($user_name ? "$user_name," : '');
			return safe_insert('txp_prefs', "
				name  = '$name',
				val   = '$val',
				event = '$event',
				html  = '$html',
				type  = '$type',
				position = '$position',
				$user_name
				prefs_id = 1"
			);
    	} else {
        	return safe_update('txp_prefs', "val = '$val'","name = '$name'" . ($user_name ? " AND $user_name" : ''));
    	}
	}

//-------------------------------------------------------------
	function get_pref($thing, $default='', $from_db=0) // checks $prefs for a named variable, or creates a default
	{
		global $prefs, $txp_user;

		if ($from_db)
		{
			$name = doSlash($thing);
			$user_name = doSlash($txp_user);
			// prefer system prefs over user's prefs
			$field = safe_field('val', 'txp_prefs',
								"name='$name' AND (user_name='' OR user_name='$user_name') order by user_name limit 1");
			if ($field !== false)
			{
				$prefs[$thing] = $field;
			}
		}
		return (isset($prefs[$thing])) ? $prefs[$thing] : $default;
	}

// -------------------------------------------------------------
	function getCustomFields()
	{
		global $prefs;
		static $out = NULL;

		// have cache?
		if (!is_array($out))
		{
			$cfs = preg_grep('/^custom_\d+_set/', array_keys($prefs));

			$out = array();
			foreach ($cfs as $name) {
				preg_match('/(\d+)/', $name, $match);
				if (!empty($prefs[$name])) {
					$out[$match[1]] = strtolower($prefs[$name]);
				}
			}
		}
		return $out;
	}

/**
 * Build a query qualifier to filter non-matching custom fields from the result set
 *
 * @param array $custom 	An array of 'custom_field_name' => field_number tupels
 * @param array $pairs 		Filter criteria: An array of 'name' => value tupels
 * @return bool|string 		A SQL qualifier for a querys 'WHERE' part
 */
// -------------------------------------------------------------
	function buildCustomSql($custom,$pairs)
	{
		if ($pairs) {
			$pairs = doSlash($pairs);
			foreach($pairs as $k => $v) {
				if(in_array($k,$custom)) {
					$no = array_keys($custom,$k);
					# nb - use 'like' here to allow substring matches
					$out[] = "and custom_".$no[0]." like '$v'";
				}
			}
		}
		return (!empty($out)) ? ' '.join(' ',$out).' ' : false;
	}

// -------------------------------------------------------------
	function txp_status_header($status='200 OK')
	{
		if (IS_FASTCGI)
			header("Status: $status");
		elseif ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0')
			header("HTTP/1.0 $status");
		else
			header("HTTP/1.1 $status");
	}

// -------------------------------------------------------------
	function txp_die($msg, $status='503', $url='')
	{
		// 503 status might discourage search engines from indexing or caching the error message

		//Make it possible to call this function as a tag, e.g. in an article <txp:txp_die status="410" />
		if (is_array($msg))
			extract(lAtts(array('msg' => '', 'status' => '503', 'url' => ''),$msg));

		// Intentionally incomplete - just the ones we're likely to use
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

		if ($status) {
			if (isset($codes[strval($status)]))
				$status = strval($status) . ' ' . $codes[$status];

			txp_status_header($status);
		}

		$code = '';
		if ($status and $parts = @explode(' ', $status, 2)) {
			$code = @$parts[0];
		}

		callback_event('txp_die', $code);

		// redirect with status
		if ($url && in_array($code, array(301, 302, 307))) {
			ob_end_clean();
			header("Location: $url", true, $code);
			die('<html><head><meta http-equiv="refresh" content="0;URL='.txpspecialchars($url).'"></head><body></body></html>');
		}

		if (@$GLOBALS['connected'] && @txpinterface == 'public') {
			$out = safe_field('user_html','txp_page',"name='error_".doSlash($code)."'");
			if ($out === false)
				$out = safe_field('user_html','txp_page',"name='error_default'");
		}

		if (!isset($out))
			$out = <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
   <meta http-equiv="content-type" content="text/html; charset=utf-8" />
   <title>Textpattern Error: <txp:error_status /></title>
</head>
<body>
<p align="center" style="margin-top:4em"><txp:error_message /></p>
</body>
</html>
eod;

		header("Content-type: text/html; charset=utf-8");

		if (is_callable('parse')) {

			$GLOBALS['txp_error_message'] = $msg;
			$GLOBALS['txp_error_status'] = $status;
			$GLOBALS['txp_error_code'] = $code;

			set_error_handler("tagErrorHandler");
			die(parse($out));
		}
		else {
			$out = preg_replace(array('@<txp:error_status[^>]*/>@', '@<txp:error_message[^>]*/>@'),
				array($status, $msg),
				$out);
			die($out);
		}
	}

// -------------------------------------------------------------
	function join_qs($q)
	{
		$qs = array();
		foreach ($q as $k=>$v)
		{
			if (is_array($v))
			{
				$v = join(',', $v);
			}
			if ($v)
			{
				$qs[] = urlencode($k) . '=' . urlencode($v);
			}
		}

		$str = join('&amp;', $qs);
		return ($str ? '?'.$str : '');
	}

// -------------------------------------------------------------

	function pagelinkurl($parts, $inherit = array())
	{
		global $permlink_mode, $prefs;

		// $inherit can be used to add parameters to an existing url, e.g:
		// $url = pagelinkurl(array('pg'=>2), $pretext);
		$keys = array_merge($inherit, $parts);

		if (isset($prefs['custom_url_func'])
		    and is_callable($prefs['custom_url_func'])
		    and ($url = call_user_func($prefs['custom_url_func'], $keys, PAGELINKURL)) !== FALSE)
		{
			return $url;
		}

		// can't use this to link to an article
		if (isset($keys['id']))
		{
			unset($keys['id']);
		}

		if (isset($keys['s']) && $keys['s'] == 'default')
		{
			unset($keys['s']);
		}

		// 'article' context is implicit, no need to add it to the page URL
		if (isset($keys['context']) && $keys['context'] == 'article')
		{
			unset($keys['context']);
		}

		if ($permlink_mode == 'messy')
		{
			if (!empty($keys['context'])) {
				$keys['context'] = gTxt($keys['context'].'_context');
			}
			return hu.'index.php'.join_qs($keys);
		}

		else
		{
			// all clean URL modes use the same schemes for list pages
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
				if (!empty($keys['context'])) {
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

// -------------------------------------------------------------
	function filedownloadurl($id, $filename='')
	{
		global $permlink_mode;

		$filename = urlencode($filename);
		#FIXME: work around yet another mod_deflate problem (double compression)
		# http://blogs.msdn.com/wndp/archive/2006/08/21/Content-Encoding-not-equal-Content-Type.aspx
		if (preg_match('/gz$/i', $filename))
			$filename .= a;
		return ($permlink_mode == 'messy') ?
			hu.'index.php?s=file_download'.a.'id='.$id :
			hu.gTxt('file_download').'/'.$id.($filename ? '/'.$filename : '');
	}

// -------------------------------------------------------------
	function imagesrcurl($id, $ext, $thumbnail = false)
	{
		global $img_dir;
		$thumbnail = ($thumbnail ? 't' : '');
		return ihu.$img_dir.'/'.$id.$thumbnail.$ext;
	}

// -------------------------------------------------------------

	function in_list($val, $list, $delim = ',')
	{
		$args = do_list($list, $delim);

		return in_array($val, $args);
	}

// -------------------------------------------------------------

	function do_list($list, $delim = ',')
	{
		return array_map('trim', explode($delim, $list));
	}

// -------------------------------------------------------------
	function doQuote($val)
	{
		return "'$val'";
	}

// -------------------------------------------------------------
	function quote_list($in)
	{
		$out = doSlash($in);
		return doArray($out, 'doQuote');
	}

// -------------------------------------------------------------
	function trace_add($msg)
	{
		global $production_status;

		if ($production_status === 'debug')
		{
			global $txptrace,$txptracelevel;

			$txptrace[] = str_repeat("\t", $txptracelevel).$msg;
		}
	}

//-------------------------------------------------------------
	function article_push() {
		global $thisarticle, $stack_article;
		$stack_article[] = @$thisarticle;
	}

//-------------------------------------------------------------
	function article_pop() {
		global $thisarticle, $stack_article;
		$thisarticle = array_pop($stack_article);
	}
// -------------------------------------------------------------

	function relative_path($path, $pfx=NULL)
	{
		if ($pfx === NULL)
			$pfx = dirname(txpath);
		return preg_replace('@^/'.preg_quote(ltrim($pfx, '/'), '@').'/?@', '', $path);
	}

// -------------------------------------------------------------
	function get_caller($num=1,$start=2)
	{
		$out = array();
		if (!is_callable('debug_backtrace'))
			return $out;

		$bt = debug_backtrace();
		for ($i=$start; $i< $num+$start; $i++) {
			if (!empty($bt[$i])) {
				$t = '';
				if (!empty($bt[$i]['file']))
					$t .= relative_path($bt[$i]['file']);
				if (!empty($bt[$i]['line']))
					$t .= ':'.$bt[$i]['line'];
				if ($t)
					$t .= ' ';
				if (!empty($bt[$i]['class']))
					$t .= $bt[$i]['class'];
				if (!empty($bt[$i]['type']))
					$t .= $bt[$i]['type'];
				if (!empty($bt[$i]['function'])) {
					$t .= $bt[$i]['function'];

				$t .= '()';
				}


				$out[] = $t;
			}
		}
		return $out;
	}

//-------------------------------------------------------------
// function name is misleading but remains for legacy reasons
// this actually sets the locale
	function getlocale($lang) {
		global $locale;

		if (empty($locale))
			$locale = @setlocale(LC_TIME, '0');

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

		if (!empty($guesses[$lang])) {
			$l = @setlocale(LC_TIME, $guesses[$lang]);
			if ($l !== false)
				$locale = $l;
		}
		@setlocale(LC_TIME, $locale);

		return $locale;
	}

//-------------------------------------------------------------
	function assert_article() {
		global $thisarticle;
		if (empty($thisarticle))
			trigger_error(gTxt('error_article_context'));
	}

//-------------------------------------------------------------
	function assert_comment() {
		global $thiscomment;
		if (empty($thiscomment))
			trigger_error(gTxt('error_comment_context'));
	}

//-------------------------------------------------------------
	function assert_file() {
		global $thisfile;
		if (empty($thisfile))
			trigger_error(gTxt('error_file_context'));
	}

//-------------------------------------------------------------
	function assert_image() {
		global $thisimage;
		if (empty($thisimage))
			trigger_error(gTxt('error_image_context'));
	}

//-------------------------------------------------------------
	function assert_link() {
		global $thislink;
		if (empty($thislink))
			trigger_error(gTxt('error_link_context'));
	}

//-------------------------------------------------------------
	function assert_section() {
		global $thissection;
		if (empty($thissection))
			trigger_error(gTxt('error_section_context'));
	}

//-------------------------------------------------------------
	function assert_category() {
		global $thiscategory;
		if (empty($thiscategory))
			trigger_error(gTxt('error_category_context'));
	}

//-------------------------------------------------------------
	function assert_int($myvar) {
		if (is_numeric($myvar) and $myvar == intval($myvar)) {
			return (int) $myvar;
		}
		trigger_error("'".txpspecialchars((string)$myvar)."' is not an integer", E_USER_ERROR);
		return false;
	}

//-------------------------------------------------------------
	function assert_string($myvar) {
		if (is_string($myvar)) {
			return $myvar;
		}
		trigger_error("'".txpspecialchars((string)$myvar)."' is not a string", E_USER_ERROR);
		return false;
	}

//-------------------------------------------------------------
	function assert_array($myvar) {
		if (is_array($myvar)) {
			return $myvar;
		}
		trigger_error("'".txpspecialchars((string)$myvar)."' is not an array", E_USER_ERROR);
		return false;
	}

//-------------------------------------------------------------
	function replace_relative_urls($html, $permalink='') {

		global $siteurl;

		# urls like "/foo/bar" - relative to the domain
		if (serverSet('HTTP_HOST')) {
			$html = preg_replace('@(<a[^>]+href=")/@','$1'.PROTOCOL.serverSet('HTTP_HOST').'/',$html);
			$html = preg_replace('@(<img[^>]+src=")/@','$1'.PROTOCOL.serverSet('HTTP_HOST').'/',$html);
		}
		# "foo/bar" - relative to the textpattern root
		# leave "http:", "mailto:" et al. as absolute urls
		$html = preg_replace('@(<a[^>]+href=")(?!\w+:)@','$1'.PROTOCOL.$siteurl.'/$2',$html);
		$html = preg_replace('@(<img[^>]+src=")(?!\w+:)@','$1'.PROTOCOL.$siteurl.'/$2',$html);

		if ($permalink)
			$html = preg_replace("/href=\\\"#(.*)\"/","href=\"".$permalink."#\\1\"",$html);
		return ($html);
	}

//-------------------------------------------------------------
	function show_clean_test($pretext) {
		echo md5(@$pretext['req']).n;
		if (serverSet('SERVER_ADDR') == serverSet('REMOTE_ADDR'))
		{
			var_export($pretext);
		}
	}

//-------------------------------------------------------------

	function pager($total, $limit, $page) {
		$total = (int) $total;
		$limit = (int) $limit;
		$page = (int) $page;

		$num_pages = ceil($total / $limit);

		$page = min(max($page, 1), $num_pages);

		$offset = max(($page - 1) * $limit, 0);

		return array($page, $offset, $num_pages);
	}

//-------------------------------------------------------------
// word-wrap a string using a zero width space
	function soft_wrap($text, $width, $break='&#8203;')
	{
		$wbr = chr(226).chr(128).chr(139);
		$words = explode(' ', $text);
		foreach($words as $wordnr => $word) {
			$word = preg_replace('|([,./\\>?!:;@-]+)(?=.)|', '$1 ', $word);
			$parts = explode(' ', $word);
			foreach($parts as $partnr => $part) {
				$len = strlen(utf8_decode($part));
				if (!$len) continue;
				$parts[$partnr] = preg_replace('/(.{'.ceil($len/ceil($len/$width)).'})(?=.)/u', '$1'.$wbr, $part);
			}
			$words[$wordnr] = join($wbr, $parts);
		}
		return join(' ', $words);
	}

//-------------------------------------------------------------
	function strip_prefix($str, $pfx) {
		return preg_replace('/^'.preg_quote($pfx, '/').'/', '', $str);
	}

//-------------------------------------------------------------
// wrap an array of name => value tupels into an XML envelope,
// supports one level of nested arrays at most.
	function send_xml_response($response=array())
	{
		static $headers_sent = false;

		if (!$headers_sent) {
			ob_clean();
			header('Content-Type: text/xml; charset=utf-8');
			$out[] = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
			$headers_sent = true;
		}

		$default_response = array (
			'http-status' => '200 OK',
		);

		// backfill default response properties
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
 * Send a text/javascript response
 *
 * @param string $out
 * @since 4.4
 */
	function send_script_response($out = '')
	{
		static $headers_sent = false;
		if (!$headers_sent) {
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
 * @param array $message string|array: $message[0] is the message's text; $message[1] is the message's type (one of E_ERROR or E_WARNING, anything else meaning "success"; not used)
 * @since 4.5
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

// -------------------------------------------------------------
// Perform regular housekeeping.
// Might evolve into some kind of pseudo-cron later...
	function janitor()
	{
		global $prefs;

		// update DST setting
		global $auto_dst, $timezone_key, $is_dst;
		if ($auto_dst && $timezone_key)
		{
			$is_dst = timezone::is_dst(time(), $timezone_key);
			if ($is_dst != $prefs['is_dst'])
			{
				$prefs['is_dst'] = $is_dst;
				set_pref('is_dst', $is_dst, 'publish', 2);
			}
		}

        // deprecation nags
        if (AJAXALLY_CHALLENGED)
        {
            trigger_error(gTxt('deprecated_configuration', array('{name}' => 'AJAXALLY_CHALLENGED')), E_USER_NOTICE);
        }
	}

// -------------------------------------------------------------
// Dealing with timezones.
	class timezone
	{
		private $_details;
		private $_offsets;

		function __construct()
		{
			if (!timezone::is_supported())
            {
            	// Standard time zones as compiled by H.M. Nautical Almanac Office, June 2004
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
						// $timezone_ids are not unique among abbreviations
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

								// Guesstimate a timezone key for a given GMT offset
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
		 * Render HTML SELECT element for choosing a timezone
		 * @param	string	$name	Element name
		 * @param	string	$value	Selected timezone
		 * @param	boolean	$blank_first Add empty first option
		 * @param	boolean|string	$onchange n/a
		 * @param	string	$select_id	HTML id attribute
		 * @return	string	HTML markup
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
					if ($value == $timezone_id) $selected = true;
					if ($continent !== $thiscontinent)
					{
						if ($thiscontinent !== '') $out[] = n.t.'</optgroup>';
						$out[] = n.t.'<optgroup label="'.gTxt($continent).'">';
						$thiscontinent = $continent;
					}

					$where = gTxt(str_replace('_', ' ', $city))
								.(!empty($subcity) ? '/'.gTxt(str_replace('_', ' ', $subcity)) : '').t
								/*."($abbr)"*/;
					$out[] = n.t.t.'<option value="'.txpspecialchars($timezone_id).'"'.($value == $timezone_id ? ' selected="selected"' : '').'>'.$where.'</option>';
				}
				$out[] = n.t.'</optgroup>';
				return n.'<select'.( $select_id ? ' id="'.$select_id.'"' : '' ).' name="'.$name.'"'.
					($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange).
					'>'.
					($blank_first ? n.t.'<option value=""'.($selected == false ? ' selected="selected"' : '').'></option>' : '').
					join('', $out).
					n.'</select>';
			}
			return '';
		}

		/**
		 * Build a matrix of timezone details
		 * @return	array	Array of timezone details indexed by timezone key
		 */
		function details()
		{
			return $this->_details;
		}

		/**
		 * Find a timezone key matching a given GMT offset.
		 * NB: More than one key might fit any given GMT offset,
		 * thus the returned value is ambiguous and merely useful for presentation purposes.
		 * @param	integer $gmtoffset
		 * @return	string	timezone key
		 */
		function key($gmtoffset)
		{
			return isset($this->_offsets[$gmtoffset]) ? $this->_offsets[$gmtoffset] : '';
		}

		 /**
		 * Is DST in effect?
		 * @param	integer $timestamp When?
		 * @param	string 	$timezone_key Where?
		 * @return	boolean	Yes, they are saving time, actually.
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
					// switch to client time zone
					if (date_default_timezone_set($timezone_key))
					{
						$out = date('I', $timestamp);
						// restore server time zone
						date_default_timezone_set($server_tz);
					}
				}
			}
			return $out;
		}

		/**
		 * Check for run-time timezone support
		 * @return	boolean	Timezone feature is enabled
		 */
		static function is_supported()
		{
			return !defined('NO_TIMEZONE_SUPPORT');	// user-definable emergency brake
		}
	}

//-------------------------------------------------------------
	function install_textpack($textpack, $add_new_langs = false)
	{
		global $prefs;

		$textpack = explode(n, $textpack);
		if (empty($textpack)) return 0;

		// presume site language equals textpack language
		$language = get_pref('language', 'en-gb');

		$installed_langs = safe_column('lang', 'txp_lang', "1 = 1 group by lang");
		$doit = true;

		$done = 0;
		foreach ($textpack as $line)
		{
			$line = trim($line);
			// A line starting with #, not followed by @ is a simple comment
			if (preg_match('/^#[^@]/', $line, $m))
			{
				continue;
			}

			// A line matching "#@language xx-xx" establishes the designated language for all subsequent lines
			if (preg_match('/^#@language\s+(.+)$/', $line, $m))
			{
				$language = doSlash($m[1]);
				// May this Textpack introduce texts for this language?
				$doit = ($add_new_langs || in_array($language, $installed_langs));
				continue;
			}

			// A line matching "#@event_name" establishes the event value for all subsequent lines
			if (preg_match('/^#@([a-zA-Z0-9_-]+)$/', $line, $m))
			{
				$event = doSlash($m[1]);
				continue;
			}

			// Data lines match a "name => value" pattern. Some white space allowed.
			if ($doit && preg_match('/^(\w+)\s*=>\s*(.+)$/', $line, $m))
			{
				if (!empty($m[1]) && !empty($m[2]))
				{
					$name = doSlash($m[1]);
					$value = doSlash($m[2]);
					$where = "lang='$language' AND name='$name'";
					// Store text; do *not* tamper with last modification date from RPC but use a well-known date in the past
					if (safe_count('txp_lang', $where))
					{
						safe_update('txp_lang',	"lastmod='2005-08-14', data='$value', event='$event'", $where);
					}
					else
					{
						safe_insert('txp_lang',	"lastmod='2005-08-14', data='$value', event='$event', lang='$language', name='$name'");
					}
					++$done;
				}
			}
		}
		return $done;
	}

/**
 * Generate a ciphered token.
 *
 * The token is reproducable, unique among sites and users, expires later.
 *
 * @return	string	The token.
 */
//-------------------------------------------------------------
	function form_token()
	{
		static $token;
		global $txp_user;

		// Generate a ciphered token from the current user's nonce (thus valid for login time plus 30 days)
		// and a pinch of salt from the blog UID.
		if (empty($token)) {
			$nonce = safe_field('nonce', 'txp_users', "name='".doSlash($txp_user)."'");
			$token = md5($nonce . get_pref('blog_uid'));
		}
		return $token;
	}

/**
 * Assert system requirements
 */
//-------------------------------------------------------------
	function assert_system_requirements()
	{
		if (version_compare(REQUIRED_PHP_VERSION, PHP_VERSION) > 0) {
			txp_die('This server runs PHP version '.PHP_VERSION.'. Textpattern needs PHP version '. REQUIRED_PHP_VERSION. ' or better.');
		}
	}


/**
 * Validate admin steps. Protect against CSRF attempts.
 *
 * @param	string	$step	Requested admin step.
 * @param	array	$steps	An array of valid steps with flag indicating CSRF needs, e.g. array('savething' => true, 'listthings' => false)
 * @return	boolean	$step is valid, proceed. Dies on CSRF attempt.
 */
//-------------------------------------------------------------
	function bouncer($step, $steps)
	{
		global $event;

		if (empty($step)) return true;

		// Validate step
		if (!array_key_exists($step, $steps)) {
			return false;
		}

		// Does this step require a token?
		if (!$steps[$step]) {
			return true;
		}

		// Validate token
		if (gps('_txp_token') == form_token()) {
			return true;
		}

		// This place ain't no good for you, son.
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
 * Translate article status names into numerical status codes
 *
 * @param string $name 	Named status {'draft', 'hidden', 'pending', 'live', 'sticky'}
 * @return int 			Numerical status [1..5]
 */
function getStatusNum($name)
{
	$labels = array('draft' => 1, 'hidden' => 2, 'pending' => 3, 'live' => 4, 'sticky' => 5);
	$status = strtolower($name);
	$num = empty($labels[$status]) ? 4 : $labels[$status];
	return $num;
}

?>

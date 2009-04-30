<?php

/*
$HeadURL$
$LastChangedRevision$
*/

// -------------------------------------------------------------
	function doArray($in,$function)
	{
		return is_array($in) ? array_map($function,$in) : $function($in);
	}

// -------------------------------------------------------------
	function doStrip($in)
	{
		return doArray($in,'stripslashes');
	}

// -------------------------------------------------------------
	function doStripTags($in)
	{
		return doArray($in,'strip_tags');
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
		return doArray($in,'mysql_real_escape_string');
	}

// -------------------------------------------------------------
	function doSpecial($in)
	{
		return doArray($in,'htmlspecialchars');
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

// -------------------------------------------------------------
// deprecated, use htmlspecialchars instead. Remove in crockery
	function escape_output($str)
	{
		return htmlspecialchars($str);
	}

// -------------------------------------------------------------
// unused function => deprecate and remove in crockery?
	function escape_tags($str)
	{
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
	function gTxt($var, $atts=array())
	{
		global $textarray;
		if(isset($textarray[strtolower($var)])) {
			$out = $textarray[strtolower($var)];
			return strtr($out, $atts);
		}

		if ($atts)
			return $var.': '.join(', ', $atts);
		return $var;
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
				echo htmlspecialchars($out), n;
			}
		}

		if(!$f) echo "</pre>".n;
	}

// -------------------------------------------------------------
	function load_lang($lang)
	{
		global $txpcfg;

		foreach(array($lang, 'en-gb') as $lang_code)
		{
			$rs = (txpinterface == 'admin')
				? safe_rows_start('name, data','txp_lang',"lang='".doSlash($lang_code)."'")
				: safe_rows_start('name, data','txp_lang',"lang='".doSlash($lang_code)."' AND ( event='public' OR event='common')");

			if (mysql_num_rows($rs)) break;
		}

		$out = array();

		if ($rs && mysql_num_rows($rs) > 0)
		{
			while ($a = nextRow($rs))
			{
				$out[$a['name']] = $a['data'];
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
		global $txpcfg;
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
		global $txpcfg;
		$lang = LANG;

		$installed = safe_field('name', 'txp_lang',"lang='".doSlash($lang)."' limit 1");

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
	function check_privs()
	{
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
			exit(pageTop('Restricted').'<p style="margin-top:3em;text-align:center">'.
				gTxt('restricted_area').'</p>');
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
		if (isset($_GET[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_GET[$thing]);
			} else {
				return $_GET[$thing];
			}
		} elseif (isset($_POST[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_POST[$thing]);
			} else {
				return $_POST[$thing];
			}
		}
		return '';
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
		if (isset($_POST[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_POST[$thing]);
			} else {
				return $_POST[$thing];
			}
		}
		return '';
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
			$out[$a] = strip_tags(ps($a));
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
		$error = array( E_WARNING => "Warning", E_NOTICE => "Notice", E_USER_ERROR => "User_Error",
						E_USER_WARNING => "User_Warning", E_USER_NOTICE => "User_Notice");

		if (!($errno & error_reporting())) return;

		global $txp_current_plugin, $production_status;
		printf ("<pre>".gTxt('plugin_load_error').' <b>%s</b> -> <b>%s: %s on line %s</b></pre>',
				$txp_current_plugin, $error[$errno], $errstr, $errline);
		if ($production_status == 'debug')
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".htmlspecialchars(join("\n", get_caller(10)))."</code></pre>";
	}

// -------------------------------------------------------------
	function tagErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		$error = array( E_WARNING => "Warning", E_NOTICE => "Notice", E_USER_ERROR => "Textpattern Error",
						E_USER_WARNING => "Textpattern Warning", E_USER_NOTICE => "Textpattern Notice");

		if (!($errno & error_reporting())) return;
		if ($production_status == 'live') return;

		global $txp_current_tag;
		$errline = ($errstr === 'unknown_tag') ? '' : " on line $errline";
		printf ("<pre>".gTxt('tag_error').' <b>%s</b> -> <b> %s: %s %s</b></pre>',
				htmlspecialchars($txp_current_tag), $error[$errno], $errstr, $errline );
		if ($production_status == 'debug')
			{
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".htmlspecialchars(join("\n", get_caller(10)))."</code></pre>";

			$trace_msg = gTxt('tag_error').' '.$txp_current_tag.' -> '.$error[$errno].': '.$errstr.' '.$errline;
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
	function load_plugins($type=0)
	{
		global $prefs, $plugins, $plugins_ver;

		if (!is_array($plugins)) $plugins = array();

		if (!empty($prefs['plugin_cache_dir'])) {
			$dir = rtrim($prefs['plugin_cache_dir'], '/') . '/';
			// in case it's a relative path
			if (!is_dir($dir))
				$dir = rtrim(realpath(txpath.'/'.$dir), '/') . '/';
			$files = glob($dir.'*.php');
			if ($files) {
				foreach ($files as $f) {
					load_plugin(basename($f, '.php'));
				}
			}
		}

		$where = 'status = 1 AND type IN ('.($type ? '1,3' : '0,1').')';

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
					trigger_error(gTxt('unknown_callback_function', array('function' => $c['function'])), E_USER_WARNING);
				}
			}
		}
		return $return_value;
	}

// -------------------------------------------------------------
	function register_tab($area, $event, $title)
	{
		global $plugin_areas;

		if ($GLOBALS['event'] !== 'plugin')
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
// deprecated, use lAtts instead. Remove in crockery
	function getAtt($name, $default=NULL)
	{
		global $theseatts;
		return isset($theseatts[$name]) ? $theseatts[$name] : $default;
	}

// -------------------------------------------------------------
	// deprecated, use lAtts instead. Remove in crockery
	function gAtt(&$atts, $name, $default=NULL)
	{
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
	function select_buttons()
	{
		return
		gTxt('select').': '.
		fInput('button','selall',gTxt('all'),'smallerboxsp','select all','selectall();').
		fInput('button','selnone',gTxt('none'),'smallerboxsp','select none','deselectall();').
		fInput('button','selrange',gTxt('range'),'smallerboxsp','select range','selectrange();');
	}

// -------------------------------------------------------------
	function stripSpace($text, $force=0)
	{
		global $prefs;
		if ($force or !empty($prefs['attach_titles_to_permalinks']))
		{
			$text = sanitizeForUrl($text);
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

		// Remove names entities and tags
		$text = preg_replace("/(^|&\S+;)|(<[^>]*>)/U","",dumbDown($text));
		// Dashify high-order chars leftover from dumbDown()
		$text = preg_replace("/[\x80-\xff]/","-",$text);
		// Collapse spaces, minuses, (back-)slashes and non-words
		$text = preg_replace('/[\s\-\/\\\\]+/', '-', trim(preg_replace('/[^\w\s\-\/\\\\]/', '', $text)));
		// Remove all non-whitelisted characters
		$text = preg_replace("/[^A-Za-z0-9\-_]/","",$text);
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
			return preg_replace('@[ ]+([[:punct:]]?\pL+[[:punct:]]?)$@u', '&#160;$1', rtrim($str));
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
		return preg_match('/^[a-z0-9](\.?[a-z0-9_+%-])*@([a-z0-9](-*[a-z0-9])*\.)+[a-z]{2,6}$/i', $address);
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
			extract(safe_row('RealName, email', 'txp_users', "name = '".doSlash($txp_user)."'"));
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

		$sep = !is_windows() ? "\n" : "\r\n";

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
			if (is_windows())
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
		$sep = is_windows() ? "\r\n" : "\n";
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
		$pageby = $name.'_list_pageby';
		$GLOBALS[$pageby] = $qty;

		set_pref($pageby, $qty, $event, PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);

		return;
	}

// -------------------------------------------------------------

	function event_multiedit_form($name, $methods = null, $page, $sort, $dir, $crit, $search_method)
	{
		$method = ps('edit_method');

		if ($methods === NULL)
		{
			$methods = array(
				'delete' => gTxt('delete')
			);
		}

		return '<label for="withselected">'.gTxt('with_selected').'</label>'.sp.
			selectInput('edit_method', $methods, $method, 1, ' id="withselected" onchange="poweredit(this); return false;"').
			n.eInput($name).
			n.sInput($name.'_multi_edit').
			n.hInput('page', $page).
			( $sort ? n.hInput('sort', $sort).n.hInput('dir', $dir) : '' ).
			( $crit ? n.hInput('crit', $crit).n.hInput('search_method', $search_method) : '' ).
			n.fInput('submit', '', gTxt('go'), 'smallerbox');
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
// user's selected time zone
	function tz_offset()
	{
		global $gmtoffset, $is_dst;

		extract(getdate());
		$serveroffset = gmmktime(0,0,0,$mon,$mday,$year) - mktime(0,0,0,$mon,$mday,$year);
		$offset = $gmtoffset - $serveroffset;

		return $offset + ($is_dst ? 3600 : 0);
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
			$str = strftime($format, $time + tz_offset());

		@list($lang, $charset) = explode('.', $locale);
		if (empty($charset))
			$charset = 'ISO-8859-1';
		elseif (is_windows() and is_numeric($charset))
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
		return strtotime($time_str, time()+tz_offset()) - tz_offset();
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

		if (is_windows()) {
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
			error_reporting(E_ALL);
		}
		elseif ($level == 'live') {
			// don't show errors on screen
			error_reporting(E_ALL ^ (E_WARNING | E_NOTICE));
			@ini_set("display_errors","1");
		}
		else {
			// default is 'testing': display everything except notices
			error_reporting(E_ALL ^ (E_NOTICE));
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

// -------------------------------------------------------------
	function is_windows()
	{
		return (PHP_OS == 'WINNT' or PHP_OS == 'WIN32' or PHP_OS == 'Windows');
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
			$disabled = explode(',', ini_get('disable_functions'));
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
	function has_single_author($table, $col='author')
	{
		return (safe_field('COUNT(name)', 'txp_users', '1=1') <= 1) &&
			(safe_field('COUNT(DISTINCT('.doSlash($col).'))', doSlash($table), '1=1') <= 1);
	}

// --------------------------------------------------------------
	function EvalElse($thing, $condition)
	{
		global $txp_current_tag;

		trace_add("[$txp_current_tag: ".($condition ? gTxt('true') : gTxt('false'))."]");

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
		static $stack = array();

		$f = fetch_form($name);
		if ($f) {
			if (in_array($name, $stack)) {
				trigger_error(gTxt('form_circular_reference', array('{name}' => $name)));
				return;
			}
			array_push($stack, $name);
			$out = parse($f);
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

		$textile = new Textile();

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
		extract(doSlash(func_get_args()));

		$user_name = '';
		if ($is_private == PREF_PRIVATE) {
			if (empty($txp_user))
				return false;

			$user_name = 'user_name = \''.doSlash($txp_user).'\'';
		}

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
        	return safe_update('txp_prefs', "val = '$val'","name like '$name'");
    	}
	}

//-------------------------------------------------------------
	function get_pref($thing, $default='') // checks $prefs for a named variable, or creates a default
	{
		global $prefs;
		return (isset($prefs[$thing])) ? $prefs[$thing] : $default;
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
	function txp_die($msg, $status='503')
	{
		// 503 status might discourage search engines from indexing or caching the error message

		//Make it possible to call this function as a tag, e.g. in an article <txp:txp_die status="410" />
		if (is_array($msg))
			extract(lAtts(array('msg' => '', 'status' => '503'),$msg));

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

		if (@$GLOBALS['connected']) {
			$out = safe_field('user_html','txp_page',"name='error_".doSlash($code)."'");
			if (empty($out))
				$out = safe_field('user_html','txp_page',"name='error_default'");
		}

		if (empty($out))
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
			if ($v)
				$qs[] = urlencode($k) . '=' . urlencode($v);

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

		if (@$keys['s'] == 'default')
		{
			unset($keys['s']);
		}

		if ($permlink_mode == 'messy')
		{
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
				$url = hu.urlencode($keys['s']).'/';
				unset($keys['s']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['author']))
			{
				$url = hu.strtolower(urlencode(gTxt('author'))).'/'.urlencode($keys['author']).'/';
				unset($keys['author']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['c']))
			{
				$url = hu.strtolower(urlencode(gTxt('category'))).'/'.urlencode($keys['c']).'/';
				unset($keys['c']);
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
		global $production_status;

		if (is_numeric($myvar) and $myvar == intval($myvar)) {
			return (int) $myvar;
		}

		if (($production_status == 'debug') || (txpinterface == 'admin'))
		{
			trigger_error("<pre>Error: '".htmlspecialchars($myvar)."' is not an integer</pre>".
				n.'<pre style="padding-left: 2em;" class="backtrace"><code>'.
				htmlspecialchars(join(n, get_caller(5,1))).'</code></pre>', E_USER_ERROR);
		}
		else
		{
			trigger_error("'".htmlspecialchars($myvar)."' is not an integer.", E_USER_ERROR);
		}

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
		ob_clean();
		$default_response = array (
			'http-status' => '200 OK',
		);

		// backfill default response properties
		$response =  doSpecial($response) + $default_response;

		header('Content-Type: text/xml');
		txp_status_header($response['http-status']);
		$out[] = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
		$out[] = '<textpattern>';
		foreach ($response as $element => $value)
		{
			if (is_array($value))
			{
				$out[] = t."<$element>".n;
				foreach ($value as $e => $v)
				{
					$out[] = t.t."<$e value='$v' />".n;
				}
				$out[] = t."</$element>".n;
			}
			else
			{
				$out[] = t."<$element value='$value' />".n;
			}
		}
		$out[] = '</textpattern>';
		echo(join(n, $out));
		exit();
	}
?>

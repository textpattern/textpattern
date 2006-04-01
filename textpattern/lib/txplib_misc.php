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
		if(phpversion() >= "4.3.0") {
			return doArray($in,'mysql_real_escape_string');
		} else {
			return doArray($in,'mysql_escape_string');
		}
	}

// -------------------------------------------------------------
	function doSpecial($in)
	{ 
		return doArray($in,'htmlspecialchars'); 
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
	function escape_output($str)
	{
		# should be safe for xhtml and xml
		return strtr($str,
			array(
				'&' => '&#38;',
				'<' => '&#60;',
				'>' => '&#62;',
				"'" => '&#39;',
				'"' => '&#34;',
			)
		);
	}

//-------------------------------------------------------------
	function gTxt($var)
	{
		global $textarray;
		if(isset($textarray[strtolower($var)])) {
			return $textarray[strtolower($var)];
		}
		return $var;
	}

// -------------------------------------------------------------
    function dmp() 
    {
		$a = func_get_args();
		echo "<pre>".n;
		foreach ($a as $thing)
			echo htmlspecialchars(is_scalar($thing) ? strval($thing) : var_export($thing, true)), n;
		echo "</pre>".n;
    }

// -------------------------------------------------------------
	function load_lang($lang) 
	{
		global $txpcfg;
		
		$installed = safe_field('name', 'txp_lang',"lang='".doSlash($lang)."' limit 1");
		
		$lang_code = ($installed)? $lang : 'en-gb';
				
		$rs = (txpinterface == 'admin') 
				? safe_rows_start('name, data','txp_lang',"lang='".doSlash($lang_code)."'")
				: safe_rows_start('name, data','txp_lang',"lang='".doSlash($lang_code)."' AND ( event='public' OR event='common')");
		
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

		// If no user name is supplied, assume the current login name
		if (empty($user))
			$user = $txp_user;

		$privs = safe_field("privs", "txp_users", "`name`='".doSlash($user)."'");
		if (@$txp_permissions[$res])
			$req = explode(',', $txp_permissions[$res]);
		else
			$req = array('1'); // The Publisher gets prived for anything
		return in_array($privs, $req);
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
			if (get_magic_quotes_gpc()) {
				return doStrip($_GET[$thing]);
			} else {
				return $_GET[$thing];
			}
		} elseif (isset($_POST[$thing])) {
			if (get_magic_quotes_gpc()) {
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
			if (get_magic_quotes_gpc()==1) {
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
			if (get_magic_quotes_gpc()==1) {
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
			if (get_magic_quotes_gpc()) {
				return doStrip($_COOKIE["txp_".$thing]);
			} else return $_COOKIE["txp_".$thing];
		} elseif (isset($_POST[$thing])) {
			if (get_magic_quotes_gpc()) {
				return doStrip($_POST[$thing]);
			} else return $_POST[$thing];
		} 
		return '';
	}

// -------------------------------------------------------------
 	function cs($thing) //	Get a var from COOKIE; if not, create it 
	{
		if (isset($_COOKIE[$thing])) {
			if (get_magic_quotes_gpc()) {
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
	function getmicrotime() { 
    	list($usec, $sec) = explode(" ",microtime()); 
    	return ((float)$usec + (float)$sec); 
    }

// -------------------------------------------------------------
	function load_plugin($name)
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
				$txp_current_plugin = $name;
				include($dir . $name . '.php');
				$plugins_ver[$name] = @$plugin['version'];
				restore_error_handler();
				return true;
			}
		}
							
		$rs = safe_row("name,code,version","txp_plugin","status='1' AND name='".doSlash($name)."'");
		if ($rs) {
			$plugins[] = $rs['name'];
			$plugins_ver[$rs['name']] = $rs['version'];
			
			set_error_handler("pluginErrorHandler");
			$txp_current_plugin = $rs['name'];
			eval($rs['code']);
			restore_error_handler();
			
			return true;	
		}
		
		return false;
	}

// -------------------------------------------------------------
	function require_plugin($name)
	{
		if (!load_plugin($name))
			trigger_error("Unable to include required plugin \"{$name}\"",E_USER_ERROR);
	}
	
// -------------------------------------------------------------
	function include_plugin($name)
	{
		if (!load_plugin($name))
			trigger_error("Unable to include plugin \"{$name}\"",E_USER_WARNING);
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
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".join("\n", get_caller(10))."</code></pre>";
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
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".join("\n", get_caller(10))."</code></pre>";
	}

// -------------------------------------------------------------
   function load_plugins($type=NULL)
   {
		global $prefs,$plugins, $plugins_ver;
		
		if (!is_array($plugins)) $plugins = array();
		
		if (!empty($prefs['plugin_cache_dir'])) {
			$dir = rtrim($prefs['plugin_cache_dir'], '/') . '/';
			# allow a relative path
			if (!is_dir($dir))
				$dir = rtrim(realpath(txpath.'/'.$dir), '/') . '/';
			$dh = @opendir($dir);
			while ($dh and false !== ($f = @readdir($dh))) {
				if ($f{0} != '.')
					load_plugin(basename($f, '.php'));
			}
		}

		$where = "status='1'";
		if ($type !== NULL)
			$where .= (" and type='".doSlash($type)."'");

		$rs = safe_rows("name, code, version", "txp_plugin", $where);
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
		global $plugin_callback;

		if (!is_array($plugin_callback))
			return;
		$return_value = '';
		foreach ($plugin_callback as $c) {
			if ($c['event'] == $event and (empty($c['step']) or $c['step'] == $step) and $c['pre'] == $pre) {
				if (is_callable($c['function'])) {
					$return_value .= call_user_func($c['function'], $event, $step);
				}
			}
		}
		return $return_value;
	}

// -------------------------------------------------------------
	function register_tab($area, $event, $title) 
	{
		global $plugin_areas;
		
		$plugin_areas[$area][$title] = $event;
	}

// -------------------------------------------------------------
	function getAtt($name, $default=NULL) { // thanks zem!
		global $theseatts;
		return isset($theseatts[$name]) ? $theseatts[$name] : $default;
	}
	
// -------------------------------------------------------------
		function gAtt(&$atts, $name, $default=NULL) {
			return isset($atts[$name]) ? $atts[$name] : $default;
		}

// -------------------------------------------------------------
		function lAtts($pairs, $atts) {
			foreach($pairs as $name => $default) {
				$out[$name] = gAtt($atts,$name,$default);
			}
			return ($out) ? $out : false;
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
		if ($force or !empty($prefs['attach_titles_to_permalinks'])) {
		
			$text = dumbDown($text);
			$text = preg_replace("/(^|&\S+;)|(<[^>]*>)/U","",$text);		

			if ($prefs['permalink_title_format']) {
				$text =  
				strtolower(
					preg_replace('/[\s\-]+/', '-', trim(preg_replace('/[^\w\s\-]/', '', $text)))
				);
				return preg_replace("/[^A-Za-z0-9\-]/","",$text);
			} else {
				return preg_replace("/[^A-Za-z0-9]/","",$text);
			}
		}
	}

// -------------------------------------------------------------
	function dumbDown($str, $lang=NULL) 
	{
		$array = array( // nasty, huh?. 
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
			if (@is_array($i18n['default'])) {
				$array = array_merge($array,$i18n['default']);
				# language overrides
				$lang = empty($lang) ? LANG : $lang;
				if (@is_array($i18n[$lang]))
					$array = array_merge($array,$i18n[$lang]);
			}
			# load an old file (no sections) just in case
			else
				$array = array_merge($array,$i18n);
		}

		return strtr($str, $array);
	}

// -------------------------------------------------------------
	function clean_url($url)
	{
		return preg_replace("/\"|'|(?:\s.*$)/",'',$url);
	}

// -------------------------------------------------------------
	function is_blacklisted($ip) 
	{
		global $prefs;
		$checks = explode(',', $prefs['spam_blacklists']);
						
		$rip = join('.',array_reverse(explode(".",$ip)));
		foreach ($checks as $a) {
			if(@gethostbyname("$rip.".trim($a)) == '127.0.0.2') {
				$listed[] = $a;
			}
		}
		return (!empty($listed)) ? join(', ',$listed) : false;
	}

// -------------------------------------------------------------
	function updateSitePath($here) 
	{
		$here = doSlash($here);
		$rs = safe_field ("val",'txp_prefs',"name = 'path_to_site'");
		if ($rs === false) {
			safe_insert("txp_prefs","prefs_id=1,name='path_to_site',val='$here'");
		} else {
			safe_update('txp_prefs',"val='$here'","name='path_to_site'");
		}
	}

// -------------------------------------------------------------
	function splat($text)
	{
		$atts = array();
		if (preg_match_all('/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)/', $text, $match, PREG_SET_ORDER)) {
			foreach ($match as $m) {
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes($m[4]);
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes($m[6]);
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
      return preg_replace('/[\r\n]/', ' ', $str);
	}

// -------------------------------------------------------------
	function txpMail($to_address, $subject, $body, $reply_to = null) 
	{
		global $txp_user, $prefs;
		if (isset($txp_user))
		{	// Likely sending passwords
			extract(safe_row("RealName, email", "txp_users", "name = '".doSlash($txp_user)."'"));
		} 
		else
		{	// Likely sending comments -> "to" equals "from"
			extract(safe_row("RealName, email", "txp_users", "email = '".doSlash($to_address)."'"));
		}

		if ($prefs['override_emailcharset'])
		{
			$charset = 'ISO-8599-1';
			if (is_callable('utf8_decode'))
			{
				$RealName = utf8_decode($RealName);
				$subject  = utf8_decode($subject);
				$body     = utf8_decode($body);
				$to_address = utf8_decode($to_address);
				if (!is_null($reply_to)) $reply_to = utf8_decode($reply_to);
			}
		} else {
			$charset = 'UTF-8';
		}

		$RealName = strip_rn($RealName);
		$subject = strip_rn($subject);
		$email = strip_rn($email);
		if (!is_null($reply_to)) $reply_to = strip_rn($reply_to);

		if (!is_callable('mail')) 
		{
			if (txpinterface == 'admin' && $GLOBALS['production_status'] != 'live') 
				echo tag(gTxt('warn_mail_unavailable'),'p',' style="color:red;" ');
			return false;
		}
		else
		{
			$sep = (!is_windows()) ? "\n" : "\r\n";
	        $body = str_replace("\r\n", "\n", $body);
	        $body = str_replace("\r", "\n", $body);
	        $body = str_replace("\n", $sep, $body);
			return mail($to_address, $subject, $body,
			 "From: $RealName <$email>$sep"
			."Reply-To: ". ((isset($reply_to)) ? $reply_to : "$RealName <$email>") . $sep
			."X-Mailer: Textpattern$sep"
			."Content-Transfer-Encoding: 8bit$sep"
			."Content-Type: text/plain; charset=\"$charset\"$sep");		
		}
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

 	function event_category_popup($name, $cat="")
	{
		$arr = array('');
		$rs = getTree("root",$name);
		if ($rs) {
			return treeSelectInput("category", $rs, $cat);
		}
		return false;
	}
// ------------------------------------------------------------- 	
 	function event_change_pageby($name) 
	{
		$qty = gps('qty');
		safe_update('txp_prefs',"val='".doSlash($qty)."'","name='".doSlash($name).'_list_pageby'."'");
		return;
	}

// -------------------------------------------------------------
	function event_multiedit_form($name, $methods = NULL)
	{
		$method = ps('method');
		if ($methods === NULL)
			$methods = array('delete'=>gTxt('delete'));
		if ($name == 'list') {
			$methods['changesection'] = gTxt('changesection');
			$methods['changestatus'] = gTxt('changestatus');
		}
		return
			gTxt('with_selected').sp.selectInput('method',$methods,$method,1,(
				($name == 'list')? ' onchange="poweredit(this);return false;" id="withselected"':'')
			).
			eInput($name).sInput($name.'_multi_edit').fInput('submit','',gTxt('go'),'smallerbox');
	}

// -------------------------------------------------------------
	function event_multi_edit($tablename, $idkeyname)
	{
		$method = ps('method');
		$things = ps('selected');
		if ($things) {
			if ($method == 'delete') {
				foreach($things as $id) {
					$id = intval($id);
					if (safe_delete($tablename,"$idkeyname='$id'")) {
						$ids[] = $id;
					}
				}
				return join(', ',$ids);
			}elseif ($method == 'changesection'){
				$section = ps('Section');
				foreach($things as $id) {
					$id = intval($id);
					if (safe_update($tablename,"Section='$section'","$idkeyname='$id'")) {
						$ids[] = $id;
					}
				}
				return join(', ',$ids);
			}elseif ($method == 'changestatus'){
				$status = ps('Status');
				foreach($things as $id) {
					$id = intval($id);
					if (safe_update($tablename,"Status='$status'","$idkeyname='$id'")) {
						$ids[] = $id;
					}
				}
				return join(', ',$ids);
			}else return '';
		} else return '';
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

		if (!$time)
			$time = time();

		# we could add some other formats here
		if ($format == 'iso8601' or $format == 'w3cdtf') {
			$format = '%Y-%m-%dT%H:%M:%SZ';
			$gmt = 1;
		}
		elseif ($format == 'rfc822') {
			$format = '%a, %d %b %Y %T GMT';
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
			setlocale(LC_ALL, $locale);

		return $str;
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
			$tf = realpath(@tempnam($dir, 'txp_'));
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
			$newfile = @tempnam($tempdir, 'txp_');
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
		}
		return $msg;
	}

// -------------------------------------------------------------
	function is_windows()
	{
		return (PHP_OS == 'WINNT' or PHP_OS == 'WIN32' or PHP_OS == 'Windows');
	}

// --------------------------------------------------------------
	function build_file_path($base,$path)
	{
		$base = rtrim($base,'/\\');
		$path = ltrim($path,'/\\');		
		
		return $base.DIRECTORY_SEPARATOR.$path;
	}	
	
// --------------------------------------------------------------
	function get_author_name($id)
	{
		static $authors = array();

		if (isset($authors[$id]))
			return $authors[$id];

		$name = fetch('RealName','txp_users','name',doSlash($id));
		$authors[$id] = $name;
		return ($name) ? $name : $id;
	}


// --------------------------------------------------------------
	function EvalElse($thing, $condition) 
	{
		$f = '@(</?txp:\S+\b.*(?:(?<!br )/)?'.chr(62).')@sU';

		$parsed = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		$tagpat = '@^<(/?)txp:(\w+).*?(/?)>$@';

		$parts = array(0 => '', 1 => '');
		$in = 0;
		$level = 0;
		foreach ($parsed as $chunk) {
			if (preg_match($tagpat, $chunk, $m)) {
				if ($m[2] == 'else' and $m[3] == '/' and $level == 0) {
					$in = 1-$in;
				}
				elseif ($m[1] == '' and $m[3] == '') {
					++$level;
					$parts[$in] .= $chunk;
				}
				elseif ($m[1] == '/') {
					--$level;
					$parts[$in] .= $chunk;
				}
				else {
					$parts[$in] .= $chunk;
				}
			}
			else {
				$parts[$in] .= $chunk;
			}
		}

		global $txp_current_tag;
		trace_add("[$txp_current_tag: ".($condition ? gTxt('true') : gTxt('false'))."]");
		return ($condition ? $parts[0] : $parts[1]);
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
	function fetch_category_title($name, $type='article') 
	{
		static $cattitles = array();

		if (isset($cattitles[$type][$name]))
			return $cattitles[$type][$name];

		$f = safe_field('title','txp_category',"name='".doSlash($name)."' and type='".doSlash($type)."'");
		$cattitles[$type][$name] = $f;
		return $f;
	}

// -------------------------------------------------------------
	function fetch_section_title($name)
	{
		static $sectitles = array();

		if (isset($sectitles[$name]))
			return $sectitles[$name];

		$f = safe_field('title','txp_section',"name='".doSlash($name)."'");
		$sectitles[$name] = $f;
		return $f;
	}

// -------------------------------------------------------------
	function update_comments_count($id) 
	{
		$thecount = safe_field('count(*)','txp_discuss','parentid='.doSlash($id).' and visible='.VISIBLE);
		$updated = safe_update("textpattern","comments_count='".doSlash($thecount)."'","ID='".doSlash($id)."'");
		return ($updated) ? true : false;
	}

// -------------------------------------------------------------
	function clean_comment_counts($parentids) 
	{
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
		global $prefs, $txpcfg;

		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile();
		
		extract($prefs);

		$im = (!empty($comments_disallow_images)) ? 1 : '';
		$msg = trim(
			$textile->blockLite(
				$textile->TextileThis(
					strip_tags(deEntBrackets($msg)),1
				),'',$im,'',(@$comment_nofollow ? 'nofollow' : ''))
		);

		return $msg;
	}


// -------------------------------------------------------------
	function set_pref($name, $val, $event,  $type, $html='text_input') 
	{
		extract(doSlash(func_get_args()));

    	if (!safe_row("*", 'txp_prefs', "name = '$name'") ) {
        	return safe_insert('txp_prefs', "
				name  = '$name',
				val   = '$val',
				event = '$event',
				html  = '$html',
				type  = '$type',
				prefs_id = 1"
			);
    	} else {
        	return safe_update('txp_prefs', "val = '$val'","name like '$name'");    	
    	}
    	return false;
	}

// -------------------------------------------------------------
	function txp_status_header($status='200 OK')
	{
		if (substr(php_sapi_name(), 0, 3) == 'cgi' and empty($_SERVER['FCGI_ROLE']) and empty($_ENV['FCGI_ROLE']))
			header("Status: $status");
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
	function pagelinkurl($parts, $inherit=array())
	{
		global $permlink_mode;

		# $inherit can be used to add parameters to an existing url, e.g:
		# $url = pagelinkurl(array('pg'=>2), $pretext);
		$keys = array_merge($inherit, $parts);

		# can't use this to link to an article
		if (isset($keys['id']))
			unset($keys['id']);

		if (@$keys['s'] == 'default')
			unset($keys['s']);

		if ($permlink_mode == 'messy') {
			return hu.'index.php'.join_qs($keys);
		}
		else {
			# all clean URL modes use the same schemes for list pages
			$url = '';
			if (!empty($keys['rss'])) {
				$url = hu.'rss'.'/';
				unset($keys['rss']);
				return $url . join_qs($keys);
			}
			elseif (!empty($keys['atom'])) {
				$url = hu.'atom'.'/';
				unset($keys['atom']);
				return $url . join_qs($keys);
			}
			elseif (!empty($keys['s'])) {
				$url = hu.urlencode($keys['s']).'/';
				unset($keys['s']);
				return $url . join_qs($keys);
			}
			elseif (!empty($keys['author'])) {
				$url = hu.strtolower(urlencode(gTxt('author'))).'/'.urlencode($keys['author']).'/';
				unset($keys['author']);
				return $url . join_qs($keys);
			}
			elseif (!empty($keys['c'])) {
				$url = hu.strtolower(urlencode(gTxt('category'))).'/'.urlencode($keys['c']).'/';
				unset($keys['c']);
				return $url . join_qs($keys);
			}

			return hu . join_qs($keys);
		}
	}

// -------------------------------------------------------------
	function in_list($val, $list, $delim=',')
	{
		$args = explode($delim, $list);
		return in_array($val, $args);
	}

// -------------------------------------------------------------
	function trace_add($msg)
	{
		global $production_status,$txptrace,$txptracelevel;

		if (in_array($production_status, array('debug', 'test')))
			 @$txptrace[] = str_repeat("\t", @$txptracelevel).$msg;
	}


// -------------------------------------------------------------
	function relative_path($path, $pfx=NULL)
	{
		if ($pfx === NULL)
			$pfx = dirname(txpath);
		return preg_replace('@^/'.preg_quote(ltrim($pfx, '/'), '@').'/?@', '', $path);
	}

// -------------------------------------------------------------
	function get_caller($num=1)
	{
		$out = array();
		if (!is_callable('debug_backtrace'))
			return $out;

		$bt = debug_backtrace();
		for ($i=2; $i< $num+2; $i++) {
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

					if (!empty($bt[$i]['args']))
						$t .= '('.@join(', ', $bt[$i]['args']).')';
					else 
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
			'cs-cz' => array('cs_CZ.UTF-8', 'cs_CZ', 'ces', 'cze', 'cs', 'csy', 'czech', 'cs_CZ.cs_CZ.ISO_8859-2'),
			'de-de' => array('de_DE.UTF-8', 'de_DE', 'de', 'deu', 'german', 'de_DE.ISO_8859-1'),
			'en-gb' => array('en_GB.UTF-8', 'en_GB', 'en_UK', 'eng', 'en', 'english-uk', 'english', 'en_GB.ISO_8859-1'),
			'en-us' => array('en_US.UTF-8', 'en_US', 'english-us', 'en_US.ISO_8859-1'),
			'es-es' => array('es_ES.UTF-8', 'es_ES', 'esp', 'spanish', 'es_ES.ISO_8859-1'),
			'el-gr' => array('el_GR.UTF-8', 'el_GR', 'el', 'gre', 'greek', 'el_GR.ISO_8859-7'),
			'fr-fr' => array('fr_FR.UTF-8', 'fr_FR', 'fra', 'fre', 'fr', 'french', 'fr_FR.ISO_8859-1'),
			'fi-fi' => array('fi_FI.UTF-8', 'fi_FI', 'fin', 'fi', 'finnish', 'fi_FI.ISO_8859-1'),
			'it-it' => array('it_IT.UTF-8', 'it_IT', 'it', 'ita', 'italian', 'it_IT.ISO_8859-1'),
			'id-id' => array('id_ID.UTF-8', 'id_ID', 'id', 'ind', 'indonesian','id_ID.ISO_8859-1'),
			'ja-jp' => array('ja_JP.UTF-8', 'ja_JP', 'ja', 'jpn', 'japanese', 'ja_JP.ISO_8859-1'),
			'no-no' => array('no_NO.UTF-8', 'no_NO', 'no', 'nor', 'norwegian', 'no_NO.ISO_8859-1'),
			'nl-nl' => array('nl_NL.UTF-8', 'nl_NL', 'dut', 'nla', 'nl', 'nld', 'dutch', 'nl_NL.ISO_8859-1'),
			'pt-pt' => array('pt_PT.UTF-8', 'pt_PT', 'por', 'portuguese', 'pt_PT.ISO_8859-1'),
			'ru-ru' => array('ru_RU.UTF-8', 'ru_RU', 'ru', 'rus', 'russian', 'ru_RU.ISO8859-5'),
			'sk-sk' => array('sk_SK.UTF-8', 'sk_SK', 'sk', 'slo', 'slk', 'sky', 'slovak', 'sk_SK.ISO_8859-1'),
			'sv-se' => array('sv_SE.UTF-8', 'sv_SE', 'sv', 'swe', 'sve', 'swedish', 'sv_SE.ISO_8859-1'),
			'th-th' => array('th_TH.UTF-8', 'th_TH', 'th', 'tha', 'thai', 'th_TH.ISO_8859-11')
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
	function replace_relative_urls($html, $permalink='') {

		global $siteurl;

		# urls like "/foo/bar" - relative to the domain
		if (serverSet('HTTP_HOST')) {
			$html = preg_replace('@(<a[^>]+href=")/@','$1http://'.serverSet('HTTP_HOST').'/',$html);
			$html = preg_replace('@(<img[^>]+src=")/@','$1http://'.serverSet('HTTP_HOST').'/',$html);
		}
		# "foo/bar" - relative to the textpattern root
		$html = preg_replace('@(<a[^>]+href=")(?!http://)@','$1http://'.$siteurl.'/$2',$html);
		$html = preg_replace('@(<img[^>]+src=")(?!http://)@','$1http://'.$siteurl.'/$2',$html);

		if ($permalink)
			$html = preg_replace("/href=\\\"#(.*)\"/","href=\"".$permalink."#\\1\"",$html);
		return ($html);
	}

?>

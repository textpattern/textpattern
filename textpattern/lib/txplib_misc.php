<?php

if (defined('DIRECTORY_SEPARATOR'))
	define('DS', DIRECTORY_SEPARATOR);
else
	define ('DS', (is_windows() ? '\\' : '/'));

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
    function dmp($in) 
    {
		echo "<pre>", n, (is_array($in)) ? print_r($in) : $in, n, "</pre>";
    }

// -------------------------------------------------------------
	function load_lang($lang) 
	{
		global $txpcfg;
		$filename = is_file($txpcfg['txpath'].'/lang/'.$lang.'.txt')
		?	$txpcfg['txpath'].'/lang/'.$lang.'.txt'
		:	$txpcfg['txpath'].'/lang/en-gb.txt';
		 
		$file = @file($filename);
		if(is_array($file)) {
			foreach($file as $line) {
				if($line[0]=='#') continue; 
				@list($name,$val) = explode(' => ',trim($line));
				$out[$name] = $val;
			}
			return ($out) ? $out : '';
		} 
	}

// -------------------------------------------------------------
	function load_lang_dates($lang) 
	{
		global $txpcfg;
		$filename = is_file($txpcfg['txpath'].'/lang/'.$lang.'_dates.txt')?
			$txpcfg['txpath'].'/lang/'.$lang.'_dates.txt':
			$txpcfg['txpath'].'/lang/en-gb_dates.txt';
		$file = @file($txpcfg['txpath'].'/lang/'.$lang.'_dates.txt','r');
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
	function check_privs()
	{
		global $txp_user;
		$privs = safe_field("privs", "txp_users", "`name`='$txp_user'");
		$args = func_get_args();
		if(!in_array($privs,$args)) {
			exit(pageTop('Restricted').'<p style="margin-top:3em;text-align:center">'.
				gTxt('restricted_area').'</p>');
		}
	}

// -------------------------------------------------------------
	function has_privs($res)
	{
		global $txp_user;

		$permissions = array(
			'article.delete.self' => '1,2,3,4',
			'article.delete' => '1,2',
		);

		$privs = safe_field("privs", "txp_users", "`name`='$txp_user'");
		$req = explode(',', @$perms[$res]);
		return in_array($privs, $req);
	}

// -------------------------------------------------------------
	function require_privs($res)
	{
		if (!has_privs($res))
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
				return doStrip(urldecode($_GET[$thing]));
			} else {
				return urldecode($_GET[$thing]);
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
		return ($status==0) ? ucfirst(gTxt('no')) : ucfirst(gTxt('yes'));
	}
	
// -------------------------------------------------------------
	function getmicrotime() { 
    	list($usec, $sec) = explode(" ",microtime()); 
    	return ((float)$usec + (float)$sec); 
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
	function stripSpace($text) 
	{
		global $txpac;
		if ($txpac['attach_titles_to_permalinks']) {
		
			$text = dumbDown($text);
			$text = preg_replace("/(^|&\S+;)|(<[^>]*>)/U","",$text);		

			if ($txpac['permalink_title_format']) {
				return 
				strtolower(
					preg_replace("/[^[:alnum:]\-]/","",
						str_replace(" ","-",
							$text
						)
					)
				);			
			} else {
				return preg_replace("/[^[:alnum:]]/","",$text);
			}
		}
	}

// -------------------------------------------------------------
	function dumbDown($str) 
	{
		$array = array( // nasty, huh?
			'&#192;'=>'A','&#193;'=>'A','&#194;'=>'A','&#195;'=>'A','&#196;'=>'A',
			'&#197;'=>'A','&#256;'=>'A','&#260;'=>'A','&#258;'=>'A',
			'&#198;'=>'Ae',
			'&#199;'=>'C','&#262;'=>'C','&#268;'=>'C','&#264;'=>'C','&#266;'=>'C',
			'&#270;'=>'D','&#272;'=>'D','&#208;'=>'D',
			'&#200;'=>'E','&#201;'=>'E','&#202;'=>'E','&#203;'=>'E','&#274;'=>'E',
			'&#280;'=>'E','&#282;'=>'E','&#276;'=>'E','&#278;'=>'E',
			'&#284;'=>'G','&#286;'=>'G','&#288;'=>'G','&#290;'=>'G',
			'&#292;'=>'H','&#294;'=>'H',
			'&#204;'=>'I','&#205;'=>'I','&#206;'=>'I','&#207;'=>'I','&#298;'=>'I',
			'&#296;'=>'I','&#300;'=>'I','&#302;'=>'I','&#304;'=>'I',
			'&#306;'=>'IJ',
			'&#308;'=>'J',
			'&#310;'=>'K',
			'&#321;'=>'K','&#317;'=>'K','&#313;'=>'K','&#315;'=>'K','&#319;'=>'K',
			'&#209;'=>'N','&#323;'=>'N','&#327;'=>'N','&#325;'=>'N','&#330;'=>'N',
			'&#210;'=>'O','&#211;'=>'O','&#212;'=>'O','&#213;'=>'O','&#214;'=>'O',
			'&#216;'=>'O','&#332;'=>'O','&#336;'=>'O','&#334;'=>'O',
			'&#338;'=>'OE',
			'&#340;'=>'R','&#344;'=>'R','&#342;'=>'R',
			'&#346;'=>'S','&#352;'=>'S','&#350;'=>'S','&#348;'=>'S','&#536;'=>'S',
			'&#356;'=>'T','&#354;'=>'T','&#358;'=>'T','&#538;'=>'T',
			'&#217;'=>'U','&#218;'=>'U','&#219;'=>'U','&#220;'=>'U','&#362;'=>'U',
			'&#366;'=>'U','&#368;'=>'U','&#364;'=>'U','&#360;'=>'U','&#370;'=>'U',
			'&#372;'=>'W',
			'&#221;'=>'Y','&#374;'=>'Y','&#376;'=>'Y',
			'&#377;'=>'Z','&#381;'=>'Z','&#379;'=>'Z',
			'&#222;'=>'T','&#222;'=>'T',
			'&#224;'=>'a','&#225;'=>'a','&#226;'=>'a','&#227;'=>'a','&#228;'=>'a',
			'&#229;'=>'a','&#257;'=>'a','&#261;'=>'a','&#259;'=>'a',
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
			'&#242;'=>'o','&#243;'=>'o','&#244;'=>'o','&#245;'=>'o','&#246;'=>'o',
			'&#248;'=>'o','&#333;'=>'o','&#337;'=>'o','&#335;'=>'o',
			'&#339;'=>'oe',
			'&#341;'=>'r','&#345;'=>'r','&#343;'=>'r',
			'&#353;'=>'s',
			'&#249;'=>'u','&#250;'=>'u','&#251;'=>'u','&#252;'=>'u','&#363;'=>'u',
			'&#367;'=>'u','&#369;'=>'u','&#365;'=>'u','&#361;'=>'u','&#371;'=>'u',
			'&#373;'=>'w',
			'&#253;'=>'y','&#255;'=>'y','&#375;'=>'y',
			'&#382;'=>'z','&#380;'=>'z','&#378;'=>'z',
			'&#254;'=>'t',
			'&#223;'=>'ss',
			'&#383;'=>'ss'
		);

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
		$checks = array('bl.spamcop.net','list.dsbl.org','sbl.spamhaus.org');
						
		$rip = join('.',array_reverse(explode(".",$ip)));
		foreach ($checks as $a) {
			if(@gethostbyname("$rip.$a") == '127.0.0.2') {
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
		if ($rs == false) {
			safe_insert("txp_prefs","prefs_id=1,name='path_to_site',val='$here'");
		} else {
			safe_update('txp_prefs',"val='$here'","name='path_to_site'");
		}
	}

// -------------------------------------------------------------
	function splat($text)
	{
		$pairs = explode('" ', $text);
		foreach	($pairs as $pair) {
			$pair =	explode("=",trim(str_replace('"', "", $pair)));
			if (count($pair)==1)
				$pair[1] = 1;
				$attributes[strtolower($pair[0])] = $pair[1];
		}
		return $attributes;
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
		safe_update('txp_prefs',"val=$qty","name='".$name.'_list_pageby'."'");
		return;
	}

// -------------------------------------------------------------
	function event_multiedit_form($name)
	{
		$method = ps('method');
		$methods = array('delete'=>gTxt('delete'));
		return
			gTxt('with_selected').sp.selectInput('method',$methods,$method,1).
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
					if (safe_delete($tablename,"$idkeyname='$id'")) {
						$ids[] = $id;
					}
				}
				return join(', ',$ids);
			} else return '';
		} else return '';
	}

// -------------------------------------------------------------
// Calculate the offset between the server local time and the
// user's selected time zone
	function tz_offset()
	{	
		global $gmtoffset, $is_dst;

		$serveroffset = gmmktime(0,0,0) - mktime(0,0,0);
		$offset = $gmtoffset - $serveroffset;
		
		return $offset + ($is_dst ? 3600 : 0);
	}

// -------------------------------------------------------------
// Format a time, respecting the locale and local time zone,
// and make sure the output string is safe for UTF-8
	function safe_strftime($format, $time='')
	{
		global $locale;

		if (!$time)
			$time = time();

		$str = strftime($format, $time + tz_offset());
		@list($lang, $charset) = explode('.', $locale);
		if (!empty($charset) and $charset != 'UTF-8') {
			$new = '';
			if (is_callable('iconv')) 
				$new = @iconv($charset, 'UTF-8', $str);

			if ($new)
				$str = $new;
			elseif (is_callable('utf8_encode'))
				$str = utf8_encode($str);
		}

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

// -------------------------------------------------------------
	function shift_uploaded_file($f, $dest)
	{
		// Rename might not work, but it's worth a try
		if (@rename($f, $dest))
			return true;

		if (copy($f, $dest)) {
			unlink($f);
			return true;
		}
	}

// -------------------------------------------------------------
	function is_windows()
	{
		return (PHP_OS == 'WINNT' or PHP_OS == 'WIN32' or PHP_OS == 'Windows');
	}

?>

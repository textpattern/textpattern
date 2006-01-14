<?php

/*
$HeadURL$
$LastChangedRevision$
*/

//-------------------------------------------------------------
function install_language_from_file($lang)
{
	$lang_file = txpath.'/lang/'.$lang.'.txt';
	# first attempt with local file		
	if (is_file($lang_file) && is_readable($lang_file))
	{
		$lang_file = txpath.'/lang/'.$lang.'.txt';
		if (!is_file($lang_file) || !is_readable($lang_file)) return;
		$file = @fopen($lang_file, "r");
		if ($file) {
			$lastmod = @filemtime($lang_file);
			$lastmod = date('YmdHis',$lastmod);
			$data = array();
			$event = '';
			
			while (!feof($file)) {
				$line = fgets($file, 4096);
				# any line starting with #, not followed by @ is a simple comment
				if($line[0]=='#' && $line[1]!='@' && $line[1]!='#') continue;
				# if available use the lastmod time from the file
				if (strpos($line,'#@version') === 0) 
				{	# Looks like: "#@version id;unixtimestamp"
					@list($fversion,$ftime) = explode(';',trim(substr($line,strpos($line,' ',1))));
					$lastmod = date("YmdHis",min($ftime, time()));
				}
				# each language section should be prefixed by #@
				if($line[0]=='#' && $line[1]=='@')
				{
					if (!empty($data)){
						foreach ($data as $name => $value)
						{
							$value = addslashes($value);
							$exists = mysql_query('SELECT name, lastmod FROM `'.PFX."txp_lang` WHERE `lang`='".$lang."' AND `name`='$name' AND `event`='$event'");
							if ($exists) $exists = mysql_fetch_row($exists);
							if ($exists[1])
							{
								mysql_query("UPDATE `".PFX."txp_lang` SET `lastmod`='$lastmod', `data`='$value' WHERE `lang`='".$lang."' AND `name`='$name' AND `event`='$event'");
								echo mysql_error();
							} else
								mysql_query("INSERT DELAYED INTO `".PFX."txp_lang`  SET	`lang`='".$lang."', `name`='$name', `lastmod`='$lastmod', `event`='$event', `data`='$value'");
								echo mysql_error();
						}
					}
					# reset
					$data = array();
					$event = substr($line,2, (strlen($line)-2));
					$event = rtrim($event);
					continue;
				}
				 
				@list($name,$val) = explode(' => ',trim($line));
				$data[$name] = $val;
			}
			# remember to add the last one
			if (!empty($data)){
				foreach ($data as $name => $value)
				{
					 mysql_query("INSERT DELAYED INTO `".PFX."txp_lang`  SET `lang`='".$lang."', `name`='$name', `lastmod`='$lastmod', `event`='$event', `data`='$value'");
				}
			}
			mysql_query("DELETE FROM `".PFX."txp_lang`  WHERE `lang`='".$lang."' AND `lastmod`>$lastmod");
			@fclose($filename);
			#delete empty fields if any
			mysql_query("DELETE FROM `".PFX."txp_lang` WHERE `data`=''");
			mysql_query("FLUSH TABLE `".PFX."txp_lang`");

			return true;
		}
	}
	return false;
}

//-------------------------------------------------------------
# check for updates through xml-rpc
function checkUpdates()
{
	require_once txpath.'/lib/IXRClass.php';
	$client = new IXR_Client('http://rpc.textpattern.com');
	$uid = safe_field('val','txp_prefs',"name='blog_uid'");
	if (!$client->query('tups.getTXPVersion',$uid))
	{
		return gTxt('problem_connecting_rpc_server');
	}else{
		$msg = array();
		$response = $client->getResponse();
		if (is_array($response))
		{
			ksort($response);
			$version = safe_field('val','txp_prefs',"name='version'");
			$lversion = explode('.',$version);

			$branch = substr($version,0,3);
			foreach ($response as $key => $val)
			{
				$rversion = explode('.',$val);
			
				if ($key == 'txp_current_version_'.$branch)
				{					
					if (isset($lversion[2]) && isset($rversion[2]) && (intval($rversion[2])>intval($lversion[2])))
					{
						$msg[]= gTxt('updated_branch_version_available');
					}else{
						$msg[]= gTxt('your_branch_is_updated');
					}
				}else{
					if (intval($rversion[0])>intval($lversion[0]) || intval($rversion[1])>intval($lversion[1]))
					{
						$msg[]= gTxt('new_textpattern_version_available').': '.$val;
					}
				}
			}
			return $msg;
		}
	}
}

//-------------------------------------------------------------
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

?>

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


?>

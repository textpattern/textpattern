<?php

//-------------------------------------------------------------
function install_language_from_file($lang)
{
	$lang_file = txpath.'/lang/'.$lang.'.txt';

	if (is_file($lang_file) && is_readable($lang_file))
	{
		$lang_file = txpath.'/lang/'.$lang.'.txt';
		if (!is_file($lang_file) || !is_readable($lang_file)) return;
		$file = @fopen($lang_file, "r");
		if ($file) {
			$lastmod = @filemtime($lang_file);
			$lastmod = date('YmdHis',$lastmod);
			$data = $core_events = array();
			$event = '';

			while (!feof($file)) {
				$line = fgets($file, 4096);
				# ignore empty lines and simple comments (any line starting with #, not followed by @)
				if(trim($line) === '' || ($line[0] == '#' && $line[1] != '@' && $line[1] != '#')) continue;
				# if available use the lastmod time from the file
				if (strpos($line,'#@version') === 0)
				{	# Looks like: "#@version id;unixtimestamp"
					@list($fversion,$ftime) = explode(';',trim(substr($line,strpos($line,' ',1))));
					$lastmod = date("YmdHis",min($ftime, time()));
				}
				# each language section should be prefixed by #@
				if($line[0] == '#' && $line[1] == '@')
				{
					if (!empty($data))
					{
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
					if (strpos($event, 'version') === false) {
						$core_events[] = $event;
					}
					continue;
				}

				# Guard against setup strings being loaded.
				# TODO: Setup strings will be removed from the .txt files at some point; this check can then be removed
				if ($event !== 'setup')
				{
					@list($name,$val) = explode(' => ',trim($line));
					$data[$name] = $val;
				}
			}
			# remember to add the last one
			if (!empty($data))
			{
				foreach ($data as $name => $value)
				{
					 mysql_query("INSERT DELAYED INTO `".PFX."txp_lang`  SET `lang`='".$lang."', `name`='$name', `lastmod`='$lastmod', `event`='$event', `data`='$value'");
				}
			}
			mysql_query("DELETE FROM `".PFX."txp_lang`  WHERE `lang`='".$lang."' AND `event` IN ('".join("','", array_unique($core_events))."') AND `lastmod`>$lastmod");
			@fclose($filename);
			#delete empty fields if any
			mysql_query("DELETE FROM `".PFX."txp_lang` WHERE `data`=''");
			mysql_query("FLUSH TABLE `".PFX."txp_lang`");

			return true;
		}
	}
	return false;
}

?>

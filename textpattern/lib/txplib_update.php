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
				# each language section should be prefixed by #@
				if($line[0]=='#' && $line[1]=='@')
				{
					if (!empty($data)){
						foreach ($data as $name => $value)
						{
							mysql_query("INSERT DELAYED INTO `".PFX."txp_lang`  SET	`lang`='".$lang."', `name`='$name', `lastmod`='$lastmod', `event`='$event', `data`='$value'");
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
			@fclose($filename);
			#delete empty fields if any
			mysql_query("DELETE FROM `".PFX."txp_lang` WHERE `data`=''");
			return true;
		}
	}
	return false;
}
?>

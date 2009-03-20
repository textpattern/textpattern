<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/development/4.0/textpattern/lib/txplib_head.php $
$LastChangedRevision: 3125 $
*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

require_once(txpath.DS.THEME.'classic/classic.php');

class remora_theme extends classic_theme
{
	function html_head()
	{
		return '<link href="'.$this->url.'../classic/textpattern.css" rel="stylesheet" type="text/css" />'.n. // fugly
		'<link href="'.$this->url.'remora.css" rel="stylesheet" type="text/css" />'.n;
	}

	function header()
	{
		$out[] = '<ul id="nav">';
		foreach ($this->menu as $tab)
		{
			$class = ($tab['active']) ? 'tabup active' : 'tabdown inactive';
			$out[] = "<li class='{$class}'><a href='?event={$tab['event']}'>{$tab['label']}</a>";
			if (!empty($tab['items']))
			{
				$out[] = '<ul>';
				foreach($tab['items'] as $item)
				{
					$class = ($item['active']) ? 'tabup active' : 'tabdown2 inactive';
					$out[] = "<li class='{$class}'><a href='?event={$item['event']}'>{$item['label']}</a>";
				}
				$out[] = '</ul>';

			}
			$out[] = '</li>';
		}
		$out[] = '</ul>';
		return join(n, $out);
	}

	function manifest()
	{
		global $prefs;
		return array(
			'author' 		=> 'Team Textpattern',
			'author_uri' 	=> 'http://textpattern.com/',
			'version' 		=> $prefs['version'],
			'description' 	=> 'Textpattern Remora Theme',
			'help' 			=> 'http://textpattern.com/admin-theme-help',
		);
	}
}
?>

<?php

/*
$HeadURL$
$LastChangedRevision$
*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

theme::based_on('classic');

class remora_theme extends classic_theme
{
	function html_head()
	{
		$js = <<<SF
			$(document).ready( function() {
				$("#nav li").mouseenter( function() { $(this).addClass("sfhover"); } );
				$("#nav li").mouseleave( function() { $(this).removeClass("sfhover"); } );
			});
SF;
		return '<link href="'.$this->url.'../classic/textpattern.css" rel="stylesheet" type="text/css" />'.n. // fugly
		'<link href="'.$this->url.'remora.css" rel="stylesheet" type="text/css" />'.n.script_js($js).n;
	}

	function header()
	{
		$out[] = '<div id="topbar"><ul id="nav">';
		foreach ($this->menu as $tab)
		{
			$class = ($tab['active']) ? 'tabup active' : 'tabdown inactive';
			$out[] = "<li class='{$class}'><a href='?event={$tab['event']}'>{$tab['label']}</a>";
			if (!empty($tab['items']))
			{
				$out[] = '<ul>';
				foreach ($tab['items'] as $item)
				{
					$class = ($item['active']) ? 'tabup active' : 'tabdown2 inactive';
					$out[] = "<li class='{$class}'><a href='?event={$item['event']}'>{$item['label']}</a>";
				}
				$out[] = '</ul>';

			}
			$out[] = '</li>';
		}
		$out[] = '</ul></div>';
		$out[] = '<div id="messagepane">'.$this->messenger($this->message).'</div>';
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

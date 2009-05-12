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
				$("#nav li").hover( function() { $(this).addClass("sfhover"); }, function() { $(this).removeClass("sfhover"); } );
			});
SF;
		return parent::html_head().n.script_js($js).n;
	}

	function header()
	{
		global $txp_user;
		$out[] = '<div id="masthead"><ul id="nav">';
		foreach ($this->menu as $tab)
		{
			$class = ($tab['active']) ? 'tabup active' : 'tabdown inactive';
			$out[] = "<li class='primary {$class}'><a href='?event={$tab['event']}'>{$tab['label']}</a>";
			if (!empty($tab['items']))
			{
				$out[] = '<ul>';
				foreach ($tab['items'] as $item)
				{
					$class = ($item['active']) ? 'tabup active' : 'tabdown2 inactive';
					$out[] = "<li class='secondary {$class}'><a href='?event={$item['event']}'>{$item['label']}</a>";
				}
				$out[] = '</ul>';

			}
			$out[] = '</li>';
		}
		$out[] = '<li id="view-site" class="primary tabdown inactive"><a href="'.hu.'" target="_blank">'.gTxt('tab_view_site').'</a></li>';
		if ($txp_user) $out[] = '<li id="logout" class="primary tabdown inactive"><a href="index.php?logout=1" onclick="return verify(\''.gTxt('are_you_sure').'\')">'.gTxt('logout').'</a></li>';
		$out[] = '</ul></div>';
		$out[] = '<div id="messagepane">'.$this->announce($this->message).'</div>';
		return join(n, $out);
	}

	function footer()
	{
		return '<div id="end_page">'.n.
			'<a href="http://textpattern.com/" id="mothership"><img src="theme/classic/carver.gif" width="60" height="48" border="0" alt="" /></a>'.n.
			graf('Textpattern &#183; '.txp_version).n.'</div>';
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

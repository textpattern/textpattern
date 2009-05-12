<?php

/*
$HeadURL$
$LastChangedRevision$
*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

class classic_theme extends theme
{
	function html_head()
	{
		return '<link href="'.$this->url.'textpattern.css" rel="stylesheet" type="text/css" />'.n;
	}

	function header()
	{
		$out[] = '<table id="pagetop" cellpadding="0" cellspacing="0">'.n.
		  '<tr id="branding"><td><h1 id="textpattern">Textpattern</h1></td><td id="navpop">'.navPop(1).'</td></tr>'.n.
		  '<tr id="nav-primary"><td align="center" class="tabs" colspan="2">';

 		if (!$this->is_popup)
 		{
 			$out[] = '<table cellpadding="0" cellspacing="0" align="center">'.n.
			'<tr><td id="messagepane">&nbsp;'.$this->announce($this->message).'</td>';

 			$secondary = '';
 			foreach ($this->menu as $tab)
 			{
				$tc = ($tab['active']) ? 'tabup' : 'tabdown';
				$atts=' class="'.$tc.'"';
				$hatts=' href="?event='.$tab['event'].'" class="plain"';
      			$out[] = tda(tag($tab['label'], 'a', $hatts), $atts);

      			if ($tab['active'] && !empty($tab['items']))
				{
					$secondary = '</td></tr><tr id="nav-secondary"><td align="center" class="tabs" colspan="2">'.n.
					'<table cellpadding="0" cellspacing="0" align="center">'.n.
					'<tr>';
					foreach ($tab['items'] as $item)
					{
						$tc = ($item['active']) ? 'tabup' : 'tabdown2';
						$secondary .= '<td class="'.$tc.'"><a href="?event='.$item['event'].'" class="plain">'.$item['label'].'</a></td>';
					}
					$secondary .= '</tr></table>';
				}
			}
			$out[] = '<td id="view-site" class="tabdown"><a href="'.hu.'" class="plain" target="_blank">'.gTxt('tab_view_site').'</a></td>';
			$out[] = '</tr></table>';
	 		$out[] = $secondary;
 		}
		$out[] = '</td></tr></table>';
 		return join(n, $out);
	}

	function footer()
	{
		global $txp_user;

		$out[] = '<div id="end_page">'.n.
			'<a href="http://textpattern.com/" id="mothership"><img src="'.$this->url.'carver.gif" width="60" height="48" border="0" alt="" /></a>'.n.
			graf('Textpattern &#183; '.txp_version);

		if ($txp_user)
		{
			$out[] = graf(gTxt('logged_in_as').' '.span(htmlspecialchars($txp_user)).br.
				'<a href="index.php?logout=1">'.gTxt('logout').'</a>', ' id="moniker"');
		}

		$out[] = '</div>';

		return join(n, $out);;
	}

	function announce($thing)
	{
 		// $thing[0]: message text
 		// $thing[1]: message type, defaults to "success" unless empty or a different flag is set

		if ($thing === '') return '';

		if (!is_array($thing) || !isset($thing[1]))
 		{
 			$thing = array($thing, 0);
 		}

 		switch ($thing[1])
 		{
 			case E_ERROR:
 				$class = 'error';
 				break;
 			case E_WARNING:
 				$class = 'warning';
 				break;
 			default:
 				$class = 'success';
 				break;
 		}
 		$html = "<span id='message' class='$class'>".gTxt($thing[0]).'</span>';
 		// Try to inject $html into the message pane no matter when announce()'s output is printed
 		$js = addslashes($html);
 		$js = <<< EOS
 		$(document).ready( function(){
	 		$("#messagepane").html("{$js}");
			$('#messagepane #message.error').fadeOut(800).fadeIn(800);
			$('#messagepane #message.warning').fadeOut(800).fadeIn(800);
		} )
EOS;
 		return script_js(str_replace('</', '<\/', $js), $html);
	}

	function manifest()
	{
		global $prefs;
		return array(
			'author' 		=> 'Team Textpattern',
			'author_uri' 	=> 'http://textpattern.com/',
			'version' 		=> $prefs['version'],
			'description' 	=> 'Textpattern Classic Theme',
			'help' 			=> 'http://textpattern.com/admin-theme-help',
		);
	}
}
?>

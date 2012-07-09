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
			'<tr><td id="messagepane">&#160;'.$this->announce($this->message).'</td>';

			$secondary = '';
			foreach ($this->menu as $tab)
			{
				$tc = ($tab['active']) ? 'tabup' : 'tabdown';
				$atts=' class="'.$tc.'"';
				$hatts=' href="?event='.$tab['event'].'"';
				$out[] = tda(tag($tab['label'], 'a', $hatts), $atts);

				if ($tab['active'] && !empty($tab['items']))
				{
					$secondary = '</td></tr><tr id="nav-secondary"><td align="center" class="tabs" colspan="2">'.n.
					'<table cellpadding="0" cellspacing="0" align="center">'.n.
					'<tr>';
					foreach ($tab['items'] as $item)
					{
						$tc = ($item['active']) ? 'tabup' : 'tabdown2';
						$secondary .= '<td class="'.$tc.'"><a href="?event='.$item['event'].'">'.$item['label'].'</a></td>';
					}
					$secondary .= '</tr></table>';
				}
			}
			$out[] = '<td id="view-site" class="tabdown"><a href="'.hu.'" target="_blank">'.gTxt('tab_view_site').'</a></td>';
			$out[] = '</tr></table>';
			$out[] = $secondary;
		}
		$out[] = '</td></tr></table>';
		return join(n, $out);
	}

	function footer()
	{
		global $txp_user;

		$out[] = '<a id="mothership" href="http://textpattern.com/" title="'.gTxt('go_txp_com').'" rel="external"><img src="'.$this->url.'carver.png" width="40" height="40" alt="Textpattern" /></a>'.n.
			graf('Textpattern CMS &#183; '.txp_version);

		if ($txp_user)
		{
			$out[] = graf(gTxt('logged_in_as').' '.span(txpspecialchars($txp_user)).br.
				'<a href="index.php?logout=1">'.gTxt('logout').'</a>', ' id="moniker"');
		}

		return join(n, $out);;
	}

	function announce($thing=array('', 0), $modal = false)
	{
		return $this->_announce($thing, false, $modal);
	}

	function announce_async($thing=array('', 0), $modal = false)
	{
		return $this->_announce($thing, true, $modal);
	}

	private function _announce($thing, $async, $modal)
	{
		// $thing[0]: message text
		// $thing[1]: message type, defaults to "success" unless empty or a different flag is set

		if (!is_array($thing) || !isset($thing[1]))	{
			$thing = array($thing, 0);
		}

		// still nothing to say?
		if (trim($thing[0]) === '') return '';

		switch ($thing[1]) {
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

		if ($modal) {
			$html = ''; // TODO: Say what?
			$js = 'window.alert("'.escape_js(strip_tags($thing[0])).'")';
		} else {
			$html = "<span id='message' class='$class'>".gTxt($thing[0]).'</span>';
			// Try to inject $html into the message pane no matter when _announce()'s output is printed
			$js = escape_js($html);
			$js = <<< EOS
				$(document).ready( function(){
					$("#messagepane").html("{$js}");
					$('#messagepane #message.error').fadeOut(800).fadeIn(800);
					$('#messagepane #message.warning').fadeOut(800).fadeIn(800);
				});
EOS;
		}
		if ($async) {
			return $js;
		} else {
			return script_js(str_replace('</', '<\/', $js), $html);
		}

	}

	function manifest()
	{
		global $prefs;
		return array(
			'author'      => 'Team Textpattern',
			'author_uri'  => 'http://textpattern.com/',
			'version'     => $prefs['version'],
			'description' => 'Textpattern Classic Theme',
			'help'        => 'http://textpattern.com/admin-theme-help',
		);
	}
}
?>

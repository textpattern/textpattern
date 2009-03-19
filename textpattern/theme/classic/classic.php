<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/development/4.0/textpattern/lib/txplib_head.php $
$LastChangedRevision: 3125 $
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
		return comment('#TODO');
	}

	function footer()
	{
		global $txp_user;

		$out[] = '<div id="end_page">'.n.
			'<a href="http://textpattern.com"><img src="txp_img/carver.gif" width="60" height="48" border="0" alt="" /></a>'.n.
			graf('Textpattern &#183; '.txp_version);

		if ($txp_user)
		{
			$out[] = graf(gTxt('logged_in_as').' '.span(htmlspecialchars($txp_user)).br.
				'<a href="index.php?logout=1">'.gTxt('logout').'</a>', ' id="moniker"');
		}

		$out[] = '</div>';

		return join(n, $out);;
	}

	function manifest()
	{
		global $prefs;
		return array(
			'author' 		=> 'Team Textpattern',
			'author_uri' 	=> 'http://textpattern.com/',
			'version' 		=> $prefs['version'],
			'description' 	=> 'Textpattern Classic Theme',
			'help' 			=> '',
			'screenshot' 	=> ''

		);
	}
}
?>

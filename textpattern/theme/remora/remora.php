<?php

if (!defined('txpinterface'))
{
	die('txpinterface is undefined.');
}

theme::based_on('classic');

class remora_theme extends classic_theme
{
	function html_head()
	{
		$js = <<<SF
			$(document).ready(function ()
			{
				$("#nav li").hover(function ()
				{
					$(this).addClass("sfhover");
				},
				function ()
				{
					$(this).removeClass("sfhover");
				});
			});
SF;
		return parent::html_head().script_js($js);
	}

	function header()
	{
		global $txp_user;
		$out[] = hed(htmlspecialchars($GLOBALS["prefs"]["sitename"]), 1, ' class="txp-accessibility"');
		$out[] = '<nav role="navigation" id="masthead" aria-label="'.gTxt('navigation').'">';
		$out[] = '<ul id="nav">';

		foreach ($this->menu as $tab)
		{
			$class = ($tab['active']) ? ' active' : '';
			$out[] = '<li class="primary'.$class.'">'.href($tab["label"], '?event='.$tab["event"]);

			if (!empty($tab['items']))
			{
				$out[] = '<ul>';

				foreach ($tab['items'] as $item)
				{
					$class = ($item['active']) ? ' active' : '';
					$out[] = '<li class="secondary'.$class.'">'.
						href($item["label"], '?event='.$item["event"]).
						'</li>';
				}

				$out[] = '</ul>';
			}

			$out[] = '</li>';
		}

		$out[] = '<li id="view-site" class="primary tabdown inactive">'.
			href(gTxt('tab_view_site'), hu, ' target="_blank"').
			'</li>';

		if ($txp_user)
		{
			$out[] = '<li id="logout" class="primary tabdown inactive">'.
				href(gTxt('logout'), 'index.php?logout=1', ' onclick="return verify(\''.gTxt('are_you_sure').'\')"').
				'</li>';
		}

		$out[] = '</ul>';
		$out[] = '</nav>';
		$out[] = '<div id="messagepane">'.$this->announce($this->message).'</div>'.n;

		return join(n, $out);
	}

	function footer()
	{
		return graf(
			href('Textpattern CMS', 'http://textpattern.com/', ' rel="external" title="'.gTxt('go_txp_com').'" target="_blank"').
			n.span('&#183;', array('role' => 'separator')).
			n.txp_version
		);
	}

	function manifest()
	{
		global $prefs;

		return array(
			'author'      => 'Team Textpattern',
			'author_uri'  => 'http://textpattern.com/',
			'version'     => $prefs['version'],
			'description' => 'Textpattern Remora Theme',
			'help'        => 'http://textpattern.com/admin-theme-help',
		);
	}
}

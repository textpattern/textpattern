<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2013 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('txpinterface'))
{
	die('txpinterface is undefined.');
}

class classic_theme extends theme
{
	function html_head()
	{
		$out[] = '<link rel="stylesheet" href="'.$this->url.'textpattern.css">';
		$out[] = '<meta name="generator" content="Textpattern CMS">';
		$out[] = '<script src="vendors/modernizr/modernizr/modernizr.js"></script>';

		return join(n, $out);
	}

	function header()
	{
		$out[] = hed(htmlspecialchars($GLOBALS["prefs"]["sitename"]), 1, ' class="txp-accessibility"');
		$out[] = '<div id="masthead">';
		$out[] = '<div id="navpop">'.navPop(1).'</div>';
		$out[] = hed('Textpattern', 1, ' id="branding"');
		$out[] = '</div>';

		if (!$this->is_popup)
		{
			$out[] = '<nav role="navigation" aria-label="'.gTxt('navigation').'">';
			$out[] = '<div id="nav-primary" class="nav-tabs">';
			$out[] = '<ul>';

			$secondary = '';

			foreach ($this->menu as $tab)
			{
				$tc = ($tab['active']) ? 'tabup' : 'tabdown';
				$out[] = '<li>'.
					href($tab["label"], array('event' => $tab['event']), ' class="'.$tc.'"').
					'</li>';

				if ($tab['active'] && !empty($tab['items']))
				{
					$secondary = '<div id="nav-secondary" class="nav-tabs">'.
						n.'<ul>';

					foreach ($tab['items'] as $item)
					{
						$tc = ($item['active']) ? 'tabup' : 'tabdown';
						$secondary .= n.'<li>'.
							href($item['label'], array('event' => $item['event']), ' class="'.$tc.'"').
							'</li>';
					}

					$secondary .= n.'</ul>'.
						n.'</div>';
				}
			}

			$out[] = '<li id="view-site">'.
				href(gTxt('tab_view_site'), hu, ' class="tabdown" target="_blank"').
				'</li>';

			$out[] = '</ul>';
			$out[] = '</div>';
			$out[] = $secondary;
			$out[] = '</nav>';
		}
		$out[] = '<div id="messagepane">'.$this->announce($this->message).'</div>'.n;

		return join(n, $out);
	}

	function footer()
	{
		global $txp_user;

		$out[] = href('Textpattern CMS', 'http://textpattern.com', ' title="'.gTxt('go_txp_com').'" rel="external" target="_blank"').
			n.span('&#183;', array('role' => 'separator')).
			n.txp_version;

		if ($txp_user)
		{
			$out[] = graf(gTxt('logged_in_as').' '.span(txpspecialchars($txp_user)).br.
				href(gTxt('logout'), 'index.php?logout=1', ' onclick="return verify(\''.gTxt('are_you_sure').'\')"')
				, ' id="moniker"');
		}

		return join(n, $out);;
	}

	function announce($thing = array('', 0), $modal = false)
	{
		return $this->_announce($thing, false, $modal);
	}

	function announce_async($thing = array('', 0), $modal = false)
	{
		return $this->_announce($thing, true, $modal);
	}

	private function _announce($thing, $async, $modal)
	{
		// $thing[0]: message text.
		// $thing[1]: message type, defaults to "success" unless empty or a different flag is set.

		if (!is_array($thing) || !isset($thing[1]))
		{
			$thing = array($thing, 0);
		}

		// Still nothing to say?
		if (trim($thing[0]) === '')
		{
			return '';
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

		if ($modal)
		{
			$html = ''; // TODO: Say what?
			$js = 'window.alert("'.escape_js(strip_tags($thing[0])).'")';
		}
		else
		{
			$html = span(
				gTxt($thing[0]).
				sp.href('&#215;', '#close', ' role="button" class="close" title="'.gTxt('close').'" aria-label="'.gTxt('close').'"')
			, array(
				'role'  => 'alert',
				'id'    => 'message',
				'class' => $class,
			));

			// Try to inject $html into the message pane no matter when _announce()'s output is printed.
			$js = escape_js($html);
			$js = <<< EOS
				$(document).ready(function ()
				{
					$("#messagepane").html("{$js}");
					$('#message.success, #message.warning, #message.error').fadeOut('fast').fadeIn('fast');
				});
EOS;
		}

		if ($async)
		{
			return $js;
		}
		else
		{
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

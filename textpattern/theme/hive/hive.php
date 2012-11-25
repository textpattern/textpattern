<?php

if (!defined('txpinterface'))
{
	die('txpinterface is undefined.');
}

class hive_theme extends theme
{
	function html_head()
	{
		$out[] = '<link rel="stylesheet" href="'.$this->url.'css/textpattern.css">';

		// Start of custom CSS toggles (see README.textile for usage instructions)
		if (defined('hive_theme_hide_branding'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_branding.css">';
		}
		if (defined('hive_theme_hide_headings'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_headings.css">';
		}
		if (defined('hive_theme_hide_preview_tabs_group'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_preview_tabs.css">';
		}
		if (defined('hive_theme_hide_textfilter_group'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_textfilter_group.css">';
		}
		if (defined('hive_theme_hide_advanced_group'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_advanced_group.css">';
		}
		if (defined('hive_theme_hide_custom_field_group'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_custom_field_group.css">';
		}
		if (defined('hive_theme_hide_image_group'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_image_group.css">';
		}
		if (defined('hive_theme_hide_keywords_field'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_keywords_field.css">';
		}
		if (defined('hive_theme_hide_recent_articles_group'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_recent_articles_group.css">';
		}
		if (defined('hive_theme_hide_comments_group'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_comments_group.css">';
		}
		if (defined('hive_theme_hide_expires_field'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_expires_field.css">';
		}
		if (defined('hive_theme_hide_tag_builder_column'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_tag_builder_column.css">';
		}
		if (defined('hive_theme_hide_form_preview'))
		{
			$out[] = '<link rel="stylesheet" href="'.$this->url.'css/custom/hide_form_preview.css">';
		}
		// End of custom CSS toggles.

		$out[] = '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">';
		$out[] = '<meta name="apple-mobile-web-app-capable" content="yes">';
		$out[] = '<meta name="generator" content="Textpattern CMS">';
		$out[] = '<script src="modernizr.js"></script>';
		$out[] = '<!--[if lt IE 9]><script src="'.$this->url.'js/selectivizr.min.js"></script><![endif]-->';
		$out[] = '<script src="'.$this->url.'js/scripts.js"></script>'.n;

		return join(n, $out);
	}

	function header()
	{
		global $txp_user;
		$out[] = '<h1><a href="'.hu.'" title="'.gTxt('tab_view_site').'" rel="external">'.htmlspecialchars($GLOBALS["prefs"]["sitename"]).'</a></h1>';

		if ($txp_user)
		{
			$out[] = '<p class="txp-logout"><a href="index.php?logout=1" onclick="return verify(\''.gTxt('are_you_sure').'\')">'.gTxt('logout').'</a></p>';
			$out[] = '<nav role="navigation" aria-label="'.gTxt('navigation').'">';
			$out[] = '<div class="txp-nav">';
			$out[] = '<ul class="data-dropdown">';

			foreach ($this->menu as $tab)
			{
				$class = ($tab['active']) ? ' active' : '';
				$out[] = '<li class="dropdown'.$class.'"><a class="dropdown-toggle" href="?event='.$tab["event"].'">'.$tab["label"].'</a>';

				if (!empty($tab['items']))
				{
					$out[] = '<ul class="dropdown-menu">';

					foreach ($tab['items'] as $item)
					{
						$class = ($item['active']) ? ' class="active"' : '';
						$out[] = '<li'.$class.'><a href="?event='.$item["event"].'">'.$item["label"].'</a></li>';
					}

					$out[] = '</ul>';
				}

				$out[] = '</li>';
			}
			$out[] = '</ul>';
			$out[] = '</div>';
			$out[] = '<div class="txp-nav-select">';
			$out[] = '<select>';

			foreach ($this->menu as $tab)
			{
				$out[] = '<optgroup label="'.$tab['label'].'">';

				if (!empty($tab['items']))
				{
					foreach ($tab['items'] as $item)
					{
						$select = ($item['active']) ? ' selected="selected"' : '';
						$out[] = '<option value="?event='.$item["event"].'"'.$select.'>'.$item["label"].'</option>';
					}
				}

				$out[] = '</optgroup>';
			}

			$out[] = '</select>';
			$out[] = '</div>';
			$out[] = '</nav>';
		}

		$out[] = '<div id="messagepane">'.$this->announce($this->message).'</div>';

		return join(n, $out);
	}

	function footer()
	{
		$out[] = n.'<p class="mothership"><a href="http://textpattern.com" title="'.gTxt('go_txp_com').'" rel="external">Textpattern CMS</a> (v'.txp_version.')</p>';
		$out[] = '<p class="pagejump"><a href="#">'.gTxt('back_to_top').'</a></p>';
		return join(n, $out);
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
		// $thing[0]: message text.
		// $thing[1]: message type, defaults to "success" unless empty or a different flag is set.

		if ($thing === '') return '';

		if (!is_array($thing) || !isset($thing[1]))	{
			$thing = array($thing, 0);
		}

		switch ($thing[1])
		{
			case E_ERROR :
				$class = 'error';
				break;
			case E_WARNING :
				$class = 'warning';
				break;
			default :
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
				gTxt($thing[0]).' <a role="button" href="#close" class="close" title="'.gTxt('close').'" aria-label="'.gTxt('close').'">&times;</a>'
			, array(
				'role'  => 'alert',
				'class' => 'messageflash '.$class,
			));

			// Try to inject $html into the message pane no matter when _announce()'s output is printed.
			$js = escape_js($html);
			$js = <<< EOS
				$(document).ready(function ()
				{
					$("#messagepane").html("{$js}");
					$(window).resize(function ()
					{
						$("#messagepane").css({
							left: ($(window).width() - $("#messagepane").outerWidth()) / 2
						});
					});
					$(window).resize();
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
			'description' => 'Textpattern Hive Theme',
			'help'        => 'https://github.com/philwareham/txp-hive-admin-theme',
		);
	}
}

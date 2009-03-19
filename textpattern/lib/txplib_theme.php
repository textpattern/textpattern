<?php

/*
$HeadURL$
$LastChangedRevision$
*/

if (!defined ('THEME')) define('THEME', 'theme/');

class theme
{
	var $name, $menu, $url;

	function theme($name)
	{
		$this->name = $name;
		$this->url = hu.'textpattern/'.THEME.$name.'/';
		return $this;
	}

	/* static */
	function init()
	{
		$name = get_pref('theme_name_TODO', 'classic');
		$path = txpath.DS.THEME.DS.$name.DS;
		require_once($path.$name.'.php');
		$t = "{$name}_theme";
		$t = new $t($name);
		return $t;
	}

	function set_menu()
	{
		// use legacy areas() for b/c
		$areas = areas();

		$tabs = array(
			'content' => array(
				'label' => gTxt('tab_content'),
				'event' => 'article'
				),
			'presentation' => array(
				'label' => gTxt('tab_presentation'),
				'event' => 'page'
				),
			'admin' => array(
				'label' => gTxt('tab_admin'),
				'event' => 'admin'
				),
		);

		// FIXME!
		if(!empty($areas['extensions']))
		{
			$tabs = $tabs +
				array('extensions' => array(
					'label' => gTxt('tab_extensions'),
					'event' => array_shift($areas['extensions'])
				));
		}

		foreach ($areas as $area => $items)
		{
			if (has_privs('tab.'.$area))
			{
				$this->menu[$area]['label'] = $tabs[$area]['label'];
				$this->menu[$area]['event'] = $tabs[$area]['event'];
				foreach ($items as $a => $b)
				{
					if (has_privs($b))
					{
						$this->menu[$area]['items'][] = array('label' => $a, 'event' => $b);
					}
				}
			}
		}
		return $this;
	}

	function html_head()
	{
		trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
	}

	function header()
	{
		trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
	}

	function footer()
	{
		trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
	}

	function manifest()
	{
		return array(
			'author' 		=> '',
			'author_uri' 	=> '',
			'version' 		=> '',
			'description' 	=> '',
			'help' 			=> '',
			'screenshot' 	=> ''
		);
	}
}
?>

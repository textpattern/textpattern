<?php

/*
$HeadURL$
$LastChangedRevision$
*/

if (!defined ('THEME')) define('THEME', 'theme/');

class theme
{
	var $name, $menu, $url, $is_popup, $message;

	function theme($name)
	{
		$this->name = $name;
		$this->menu = array();
		$this->url = THEME.$name.'/';
		$this->is_popup = false;
		$this->message = '';
		return $this;
	}

	/* static */
	function init($name = '')
	{
		static $instance;

		if ($name === '')
		{
			$name = pluggable_ui('admin_side', 'theme_name', get_pref('theme_name', 'classic'));
		}

		if ($instance && is_object($instance) && ($name == $instance->name))
		{
			return $instance;
		}
		else
		{
			$instance = null;
		}

		$path = txpath.DS.THEME.$name.DS.$name.'.php';
		if (is_readable($path))
		{
			require_once($path);
		}
		else
		{
			set_pref('theme_name', 'classic');
			die(gTxt('cannot_read_theme_file', array('path' => $path)));
		}

		$t = "{$name}_theme";
		if (class_exists($t))
		{
			$instance = new $t($name);
		}
		else
		{
			set_pref('theme_name', 'classic');
			die(gTxt('missing_required_theme_class', array('name' => $name, 'class' => $t)));
		}

		return $instance;
	}

	/* static */
	function names()
	{
		$dirs = glob(txpath.DS.THEME.'*');
		if (is_array($dirs))
		{
			foreach ($dirs as $d)
			{
				// extract trailing directory name
				preg_match('#(.*)[\\/]+(.*)$#', $d, $m);
				$name = $m[2];

				// accept directories containing an equally named .php file
				if (is_dir($d) && ($d != '.') && ($d != '..') && isset($name) && is_file($d.DS.$name.'.php'))
				{
					$out[] = $name;
				}
			}
			sort($out, SORT_STRING);
			return $out;
		}
		else
			return array();
	}

	/* static */
	function based_on($name)
	{
		require_once(txpath.DS.THEME.$name.DS.$name.'.php');
	}

	function set_state($area, $event, $is_popup, $message)
	{
		$this->is_popup = $is_popup;
		$this->message = $message;

		if ($is_popup) return $this;

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

		if(empty($areas['extensions']))
		{
			unset($areas['extensions']);
		}
		else
		{
			$tabs = $tabs +
				array('extensions' => array(
					'label' => gTxt('tab_extensions'),
					'event' => reset($areas['extensions'])
				));
		}

		foreach ($areas as $ar => $items)
		{
			if (has_privs('tab.'.$ar))
			{
				$this->menu[$ar] = array(
					'label' => $tabs[$ar]['label'],
					'event' => $tabs[$ar]['event'],
					'active' => ($ar == $area)
				);

				foreach ($items as $a => $b)
				{
					if (has_privs($b))
					{
						$this->menu[$ar]['items'][] = array('label' => $a, 'event' => $b, 'active' => ($b == $event));
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

	function announce($thing=array('', 0))
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

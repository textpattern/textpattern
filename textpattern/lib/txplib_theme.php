<?php

/*
$HeadURL$
$LastChangedRevision$
*/

if (!defined ('THEME')) define('THEME', 'theme/');

class theme
{
	var $name, $menu, $url, $is_popup, $message;

//----------------------------------------
// Theme engine methods
//----------------------------------------

	/**
	 * Constructor
	 * @param	string	$name	Theme name
	 */
	function theme($name)
	{
		$this->name = $name;
		$this->menu = array();
		$this->url = THEME.rawurlencode($name).'/';
		$this->is_popup = false;
		$this->message = '';
	}

	/**
	 * Get a theme's source path
	 * @param	string	$name	Theme name
	 * @return	string	Source file path for named theme
	 */
	/* static */
	function path($name)
	{
		return txpath.DS.THEME.$name.DS.$name.'.php';
	}

	/**
	 * Theme factory
	 * @param	string	$name	Theme name
	 * @return	object|boolean	An initialised theme object, or false on failure
	 */
	/* static */
	function factory($name)
	{
		$path = theme::path($name);
		if (is_readable($path))
		{
			require_once($path);
		}
		else
		{
			return false;
		}

		$t = "{$name}_theme";
		if (class_exists($t))
		{
			return new $t($name);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Initialise the theme singleton
	 * @param	string 	$name 	Theme name
	 * @return	object	A valid theme object
	 */
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

		$instance = theme::factory($name);
		if (!$instance)
		{
			set_pref('theme_name', 'classic');
			die(gTxt('cannot_instantiate_theme', array('{name}' => $name, '{class}' => "{$name}_theme", '{path}' => theme::path($name))));
		}

		return $instance;
	}

	/**
	 * Get a list of all theme names
	 * @return array Alphabetically sorted array of all available theme names
	 */
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

	/**
	 * Inherit from an ancestor theme
	 * @param	string	$name	Name of ancestor theme
	 * @return	boolean	True on success, false on unavailable/invalid ancestor theme
	 */
	/* static */
	function based_on($name)
	{
		global $production_status;
		$theme = theme::factory($name);
		if (!$theme)
		{
			set_pref('theme_name', 'classic');
			if ($production_status === 'debug')
			{
				echo gTxt('cannot_instantiate_theme', array('{name}' => $name, '{class}' => "{$name}_theme", '{path}' => theme::path($name)));
			}
			return false;
		}
		return true;
	}

	/**
	 * Sets Textpatterns menu structure, message contents and other application states
	 * @param	string	$area	Currently active top level menu
	 * @param	string	$event	Currently active second level menu
	 * @param	boolean	$is_popup	Just a popup window for tag builder et cetera
	 * @param	array	$message	The contents of the notification message pane
	 * @return	object	This theme object
	 */
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

//----------------------------------------
// Overrideable methods for custom themes
//----------------------------------------

	/**
	 * Output HEAD element contents. Returned value is rendered into the HEAD element of all admin side pages by core.
	 * @return string
	 */
	function html_head()
	{
		trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
	}

	/**
	 * Draw the theme's header
	 * @return string
	 */
	function header()
	{
		trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
	}

	/**
	 * Draw the theme's footer
	 * @return string
	 */
	function footer()
	{
		trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
	}

	/**
	 * Output notification message
	 * @param	array	$thing	Message text and status flag
	 */
	function announce($thing=array('', 0))
	{
		trigger_error(__FUNCTION__.' is abstract.', E_USER_ERROR);
	}

	/**
	 * Define bureaucratic details of this theme. All returned items are optional.
	 * @return array
	 */
	function manifest()
	{
		return array(
			'title'			=> '',	// Human-readable title of this theme. No HTML, keep it short.
			'author' 		=> '',	// Name(s) of this theme's creator(s).
			'author_uri' 	=> '',	// URI of the theme's site. Decent vanity is accepted.
			'version' 		=> '',	// Version numbering. Mind version_compare().
			'description' 	=> '',	// Human readable short description. No HTML.
			'help' 			=> '',	// URI of the theme's help and docs. Strictly optional.
		);
	}
}
?>

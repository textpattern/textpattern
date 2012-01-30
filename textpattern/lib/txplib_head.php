<?php

/*
$HeadURL$
$LastChangedRevision$
*/

// -------------------------------------------------------------
	function pagetop($pagetitle,$message="")
	{
		global $siteurl, $sitename, $txp_user, $event, $step, $app_mode, $theme;

		if ($app_mode == 'async') return;

		$area = gps('area');
		$event = (!$event) ? 'article' : $event;
		$bm = gps('bm');

		$privs = safe_field("privs", "txp_users", "name = '".doSlash($txp_user)."'");

		$GLOBALS['privs'] = $privs;

		$areas = areas();
		$area = false;

		foreach ($areas as $k => $v)
		{
			if (in_array($event, $v))
			{
				$area = $k;
				break;
			}
		}

		if (gps('logout'))
		{
			$body_id = 'page-logout';
		}

		elseif (!$txp_user)
		{
			$body_id = 'page-login';
		}

		else
		{
			$body_id = 'page-'.htmlspecialchars($event);
		}

		header(pluggable_ui('admin_side', 'x_frame_options', 'X-Frame-Options: SAMEORIGIN'));

	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo LANG; ?>" lang="<?php echo LANG; ?>" dir="<?php echo gTxt('lang_dir'); ?>">
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
	<title>Txp &#8250; <?php echo htmlspecialchars($sitename) ?> &#8250; <?php echo escape_title($pagetitle) ?></title>
	<script src="jquery.js" type="text/javascript"></script>
	<?php echo script_js(
		'var textpattern = {
		event: "'.htmlspecialchars($event).'",
		step: "'.htmlspecialchars($step).'",
		_txp_token: "'.htmlspecialchars(form_token()).'",
		ajax_timeout: '.htmlspecialchars(get_pref('ajax_timeout', max(30000, 1000 * @ini_get('max_execution_time')))).',
		do_spellcheck: "'.htmlspecialchars(
							get_pref('do_spellcheck', '#page-article #body, #page-article #title,'.
													'#page-image #alt-text, #page-image #caption,'.
													'#page-file #description,'.
													'#page-link #link-title, #page-link #link-description')
							).'"};'
	); ?>
	<script type="text/javascript" src="textpattern.js"></script>
	<script type="text/javascript">
	<!--

		var cookieEnabled = checkCookies();

		if (!cookieEnabled)
		{
			confirm('<?php echo trim(gTxt('cookies_must_be_enabled')); ?>');
		}

<?php
	$edit = array();

	if ($event == 'list')
	{
		$rs = safe_column('name', 'txp_section', "name != 'default'");

		$edit['section'] = $rs ? selectInput('Section', $rs, '', true) : '';

		$rs = getTree('root', 'article');

		$edit['category1'] = $rs ? treeSelectInput('Category1', $rs, '') : '';
		$edit['category2'] = $rs ? treeSelectInput('Category2', $rs, '') : '';

		$edit['comments'] = onoffRadio('Annotate', safe_field('val', 'txp_prefs', "name = 'comments_on_default'"));

		$edit['status'] = selectInput('Status', array(
			1 => gTxt('draft'),
			2 => gTxt('hidden'),
			3 => gTxt('pending'),
			4 => gTxt('live'),
			5 => gTxt('sticky'),
		), '', true);

		$rs = safe_column('name', 'txp_users', "privs not in(0,6) order by name asc");

		$edit['author'] = $rs ? selectInput('AuthorID', $rs, '', true) : '';
	}

	if (in_array($event, array('image', 'file', 'link')))
	{
		$rs = getTree('root', $event);
		$edit['category'] = $rs ? treeSelectInput('category', $rs, '') : '';

		$rs = safe_column('name', 'txp_users', "privs not in(0,6) order by name asc");
		$edit['author'] = $rs ? selectInput('author', $rs, '', true) : '';
	}

	if ($event == 'plugin')
	{
		$edit['order'] = selectInput('order', array(1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9), 5, false);
	}

	if ($event == 'admin')
	{
		$edit['privilege'] = privs();
		$rs = safe_column('name', 'txp_users', '1=1');
		$edit_assign_assets = $rs ? selectInput('assign_assets', $rs, '', true) : '';
	}

	// output JavaScript
?>
		function poweredit(elm)
		{
			var something = elm.options[elm.selectedIndex].value;

			// Add another chunk of HTML
			var pjs = document.getElementById('js');

			if (pjs == null)
			{
				var br = document.createElement('br');
				elm.parentNode.appendChild(br);

				pjs = document.createElement('P');
				pjs.setAttribute('id','js');
				elm.parentNode.appendChild(pjs);
			}

			if (pjs.style.display == 'none' || pjs.style.display == '')
			{
				pjs.style.display = 'block';
			}

			if (something != '')
			{
				switch (something)
				{
<?php
		foreach($edit as $key => $val)
		{
			echo "case 'change".$key."':".n.
				t."pjs.innerHTML = '<span>".str_replace(array("\n", '-'), array('', '&#45;'), str_replace('</', '<\/', addslashes($val)))."<\/span>';".n.
				t.'break;'.n.n;
		}
		if (isset($edit_assign_assets))
		{
			echo "case 'delete':".n.
					t."pjs.innerHTML = '<label for=\"assign_assets\">".addslashes(gTxt('assign_assets_to'))."</label><span>".str_replace(array("\n", '-'), array('', '&#45;'), str_replace('</', '<\/', addslashes($edit_assign_assets)))."<\/span>';".n.
					t.'break;'.n.n;
		}
?>
					default:
						pjs.style.display = 'none';
						break;
				}
			}

			return false;
		}

		addEvent(window, 'load', cleanSelects);
	-->
	</script>
	<?php
	echo $theme->html_head();
	callback_event('admin_side', 'head_end');
	?>
	</head>
	<body id="<?php echo $body_id; ?>" class="<?php echo $area; ?>">
	<?php callback_event('admin_side', 'pagetop');
		$theme->set_state($area, $event, $bm, $message);
		echo pluggable_ui('admin_side', 'header', $theme->header());
		callback_event('admin_side', 'pagetop_end');
	}

// -------------------------------------------------------------
	function areatab($label,$event,$tarea,$area)
	{
		$tc = ($area == $event) ? 'tabup' : 'tabdown';
		$atts=' class="'.$tc.'"';
		$hatts=' href="?event='.$tarea.'" class="plain"';
      	return tda(tag($label,'a',$hatts),$atts);
	}

// -------------------------------------------------------------
	function tabber($label,$tabevent,$event)
	{
		$tc = ($event==$tabevent) ? 'tabup' : 'tabdown2';
		$out = '<td class="'.$tc.'"><a href="?event='.$tabevent.'" class="plain">'.$label.'</a></td>';
		return $out;
	}

// -------------------------------------------------------------

	function tabsort($area, $event)
	{
		if ($area)
		{
			$areas = areas();

			$out = array();

			foreach ($areas[$area] as $a => $b)
			{
				if (has_privs($b))
				{
					$out[] = tabber($a, $b, $event, 2);
				}
			}

			return ($out) ? join('', $out) : '';
		}

		return '';
	}

// -------------------------------------------------------------
	function areas()
	{
		global $privs, $plugin_areas;

		$areas['start'] = array(
		);

		$areas['content'] = array(
			gTxt('tab_organise') => 'category',
			gTxt('tab_write')    => 'article',
			gTxt('tab_list')    =>  'list',
			gTxt('tab_image')    => 'image',
			gTxt('tab_file')	 => 'file',
			gTxt('tab_link')     => 'link',
			gTxt('tab_comments') => 'discuss'
		);

		$areas['presentation'] = array(
			gTxt('tab_sections') => 'section',
			gTxt('tab_pages')    => 'page',
			gTxt('tab_forms')    => 'form',
			gTxt('tab_style')    => 'css'
		);

		$areas['admin'] = array(
			gTxt('tab_diagnostics') => 'diag',
			gTxt('tab_preferences') => 'prefs',
			gTxt('tab_site_admin')  => 'admin',
			gTxt('tab_logs')        => 'log',
			gTxt('tab_plugins')     => 'plugin',
			gTxt('tab_import')      => 'import'
		);

		$areas['extensions'] = array(
		);

		if (is_array($plugin_areas))
			$areas = array_merge_recursive($areas, $plugin_areas);

		return $areas;
	}

// -------------------------------------------------------------

	function navPop($inline = '')
	{
		$areas = areas();

		$out = array();

		foreach ($areas as $a => $b)
		{
			if (!has_privs( 'tab.'.$a))
			{
				continue;
			}

			if (count($b) > 0)
			{
				$out[] = n.t.'<optgroup label="'.gTxt('tab_'.$a).'">';

				foreach ($b as $c => $d)
				{
					if (has_privs($d))
					{
						$out[] = n.t.t.'<option value="'.$d.'">'.$c.'</option>';
					}
				}

				$out[] = n.t.'</optgroup>';
			}
		}

		if ($out)
		{
			$style = ($inline) ? ' style="display: inline;"': '';

			return '<form method="get" action="index.php" class="navpop"'.$style.'>'.
				n.'<select name="event" onchange="submit(this.form);">'.
				n.t.'<option>'.gTxt('go').'&#8230;</option>'.
				join('', $out).
				n.'</select>'.
				n.'</form>';
		}
	}

// -------------------------------------------------------------
	function button($label,$link)
	{
		return '<span style="margin-right:2em"><a href="?event='.$link.'" class="plain">'.$label.'</a></span>';
	}
?>

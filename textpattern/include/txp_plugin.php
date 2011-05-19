<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'plugin') {
		require_privs('plugin');

		$available_steps = array(
			'plugin_edit'	=> false,
			'plugin_help'	=> false,
			'plugin_list'	=> false,
			'plugin_install'	=> true,
			'plugin_save'	=> true,
			'plugin_verify'	=> true,
			'switch_status'	=> true,
			'plugin_multi_edit'	=> true
		);

		if (!$step or !bouncer($step, $available_steps)) {
			$step = 'plugin_list';
		}
		$step();
	}

// -------------------------------------------------------------

	function plugin_list($message = '')
	{
		global $event;

		pagetop(gTxt('edit_plugins'), $message);

		echo '<div id="'.$event.'_control" class="txp-control-panel">';
		echo n.n.startTable('edit', '', 'plugin-install').
			tr(
				tda(
					plugin_form()
				,' colspan="8" style="height: 30px; border: none;"')
			).
		endTable().
		'</div>';

		extract(gpsa(array('sort', 'dir')));
		if ($sort === '') $sort = get_pref('plugin_sort_column', 'name');
		if ($dir === '') $dir = get_pref('plugin_sort_dir', 'asc');
		$dir = ($dir == 'desc') ? 'desc' : 'asc';
		if (!in_array($sort, array('name', 'status', 'author', 'version', 'modified', 'load_order'))) $sort = 'name';

		$sort_sql = $sort.' '.$dir;

		set_pref('plugin_sort_column', $sort, 'plugin', 2, '', 0, PREF_PRIVATE);
		set_pref('plugin_sort_dir', $dir, 'plugin', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$rs = safe_rows_start('name, status, author, author_uri, version, description, length(help) as help, abs(strcmp(md5(code),code_md5)) as modified, load_order, flags',
			'txp_plugin', '1 order by '.$sort_sql);

		if ($rs and numRows($rs) > 0)
		{
			echo n.'<div id="'.$event.'_container" class="txp-container txp-list">';
			echo '<form action="index.php" id="plugin_form" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

			startTable('list', '', 'list').
			n.'<thead>'.
			tr(
				column_head('plugin', 'name', 'plugin', true, $switch_dir, '', '', (('name' == $sort) ? "$dir " : '').'name').
				column_head('author', 'author', 'plugin', true, $switch_dir, '', '', (('author' == $sort) ? "$dir " : '').'author').
				column_head('version', 'version', 'plugin', true, $switch_dir, '', '', (('version' == $sort) ? "$dir " : '').'version').
				column_head('plugin_modified', 'modified', 'plugin', true, $switch_dir, '', '', (('modified' == $sort) ? "$dir " : '').'modified').
				hCell(gTxt('description'), '', ' class="description"').
				column_head('active', 'status', 'plugin', true, $switch_dir, '', '', (('status' == $sort) ? "$dir " : '').'status').
				column_head('order', 'load_order', 'plugin', true, $switch_dir, '', '', (('load_order' == $sort) ? "$dir " : '').'load-order').
				hCell(gTxt('manage'), '',  ' class="manage actions"').
				hCell('', '', ' class="multi-edit"')
			).
			n.'</thead>';

			$tfoot = n.'<tfoot>'.tr(
				tda(
					select_buttons().
					plugin_multiedit_form('', $sort, $dir, '', '')
				, ' class="multi-edit" colspan="10" style="text-align: right; border: none;"')
			).n.'</tfoot>';

			echo $tfoot;
			echo '<tbody>';

			$ctr = 1;

			while ($a = nextRow($rs))
			{
				foreach ($a as $key => $value) {
					$$key = htmlspecialchars($value);
				}
				// Fix up the description for clean cases
				$description = preg_replace(array('#&lt;br /&gt;#',
												  '#&lt;(/?(a|b|i|em|strong))&gt;#',
												  '#&lt;a href=&quot;(https?|\.|\/|ftp)([A-Za-z0-9:/?.=_]+?)&quot;&gt;#'),
											array('<br />','<$1>','<a href="$1$2">'),
											$description);

				$help = !empty($help) ?
					n.t.'<li class="action-view"><a href="?event=plugin'.a.'step=plugin_help'.a.'name='.urlencode($name).'">'.gTxt('help').'</a></li>' : '';

				$plugin_prefs = ($flags & PLUGIN_HAS_PREFS) && $status ?
					n.t.'<li class="action-options"><a href="?event=plugin_prefs.'.urlencode($name).'">'.gTxt('plugin_prefs').'</a></li>' : '';

				echo tr(

					n.td($name, '', 'name').

					td(
						href($author, $author_uri)
					, '', 'author').

					td($version, 10, 'version').
					td(($modified ? gTxt('yes') : ''), '', 'modified').
					td($description, 260, 'description').

					td(
						status_link($status, $name, yes_no($status))
					,30, 'status').

					td($load_order, '', 'load-order').
					td(
						n.'<ul class="actions">'.
						$help.
						n.t.'<li class="action-edit">'.eLink('plugin', 'plugin_edit', 'name', $name, gTxt('edit')).'</li>'.
						$plugin_prefs.
						n.'</ul>'
					,'', 'manage').
					td(
						fInput('checkbox', 'selected[]', $name)
					,30, 'multi-edit')
				, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').'"'
				);

				$ctr++;
				unset($name, $page, $deletelink);
			}

			echo '</tbody>'.
			n.endTable().
			n.tInput().
			n.'</form>'.
			n.'</div>';
		}
	}

// -------------------------------------------------------------

	function switch_status()
	{
		extract(gpsa(array('name', 'status')));

		$change = ($status) ? 0 : 1;

		safe_update('txp_plugin', "status = $change", "name = '".doSlash($name)."'");

		if (safe_field('flags', 'txp_plugin', "name ='".doSlash($name)."'") & PLUGIN_LIFECYCLE_NOTIFY)
		{
			load_plugin($name, true);
			$message = callback_event("plugin_lifecycle.$name", $status ? 'disabled' : 'enabled');
		}
		if (empty($message)) $message = gTxt('plugin_updated', array('{name}' => $name));

		plugin_list($message);
	}

// -------------------------------------------------------------
  function plugin_edit()
  {
  		global $event;

		$name = gps('name');
		pagetop(gTxt('edit_plugins'));

		echo n.'<div id="'.$event.'_container" class="txp-container txp-edit">';
		echo plugin_edit_form($name);
		echo '</div>';
  }


// -------------------------------------------------------------
	function plugin_help()
	{
		global $event;

		$name = gps('name');
		pagetop(gTxt('plugin_help'));
		$help = ($name) ? safe_field('help','txp_plugin',"name = '".doSlash($name)."'") : '';
		echo
		'<div id="'.$event.'_container" class="txp-container txp-view">'.
		startTable('edit', '', 'plugin-help')
		.	tr(tda($help,' width="600"'))
		.	endTable()
		.  '</div>';
	}

// -------------------------------------------------------------
	function plugin_edit_form($name='')
	{
		$sub = fInput('submit','',gTxt('save'),'publish');
		$code = ($name) ? fetch('code','txp_plugin','name',$name) : '';
		$thing = ($code)
		?	$code
		:	'';
		$textarea = '<textarea id="plugin-code" class="code" name="code" rows="28" cols="90">'.htmlspecialchars($thing).'</textarea>';

		return
		form(startTable('edit', '', 'edit-pane')
		.	tr(td($textarea))
		.	tr(td($sub))
#		.	tr(td($help))
		.	endTable().sInput('plugin_save').eInput('plugin').hInput('name',$name), '', '', 'post', 'edit-form', '', 'plugin_details');
	}

// -------------------------------------------------------------

	function plugin_save()
	{
		extract(doSlash(gpsa(array('name', 'code'))));

		safe_update('txp_plugin', "code = '$code'", "name = '$name'");

		$message = gTxt('plugin_saved', array('{name}' => $name));

		plugin_list($message);
	}

// -------------------------------------------------------------

	function status_link($status,$name,$linktext)
	{
		$out = '<a href="index.php?';
		$out .= 'event=plugin&#38;step=switch_status&#38;status='.
			$status.'&#38;name='.urlencode($name).'&#38;_txp_token='.form_token().'"';
		$out .= '>'.$linktext.'</a>';
		return $out;
	}

// -------------------------------------------------------------
	function plugin_verify()
	{
		global $event;

		if (ps('txt_plugin')) {
			$plugin = join("\n", file($_FILES['theplugin']['tmp_name']));
		} else {
			$plugin = ps('plugin');
		}

		// pre-4.0 style plugin?
		if (strpos($plugin, '$plugin=\'') !== false) {
			// try to increase PCRE's backtrack limit in PHP 5.2+ to accommodate to x-large plugins
			// @see http://bugs.php.net/bug.php?id=40846 et al.
			@ini_set('pcre.backtrack_limit', '1000000');
			$plugin = preg_replace('@.*\$plugin=\'([\w=+/]+)\'.*@s', '$1', $plugin);
			// have we hit yet another PCRE restriction?
			if ($plugin === null)
			{
				plugin_list(array(
								gTxt('plugin_pcre_error', array('{errno}' => preg_last_error())),
								E_ERROR
							));
				return;
			}
		}

		// strip out #comment lines
		$plugin = preg_replace('/^#.*$/m', '', $plugin);
		if ($plugin === null)
		{
			plugin_list(array(
							gTxt('plugin_pcre_error', array('{errno}' => preg_last_error())),
							E_ERROR
						));
			return;
		}

		if (isset($plugin)) {
			$plugin_encoded = $plugin;
			$plugin = base64_decode($plugin);

			if (strncmp($plugin, "\x1F\x8B", 2) === 0)
			{
				if (function_exists('gzinflate'))
				{
					$plugin = gzinflate(substr($plugin, 10));
				}

				else
				{
					plugin_list(array(gTxt('plugin_compression_unsupported'), E_ERROR));
					return;
				}
			}

			if ($plugin = @unserialize($plugin))
			{
				if(is_array($plugin)){
					extract($plugin);
					$source = '';
					if (isset($help_raw) && empty($plugin['allow_html_help'])) {
						include_once txpath.'/lib/classTextile.php';
						$textile = new Textile();
						$help_source = $textile->TextileRestricted($help_raw, 0, 0);
					} else {
						$help_source= highlight_string($help, true);
					}
					$source.= highlight_string('<?php'.$plugin['code'].'?>', true);
					$sub = fInput('submit','',gTxt('install'),'publish');

					pagetop(gTxt('edit_plugins'));
					echo
					'<div id="'.$event.'_container" class="txp-container txp-view">'.
					form(
						hed(gTxt('previewing_plugin'), 3).
						tag($source, 'div', ' id="preview-plugin" class="code"').
						hed(gTxt('plugin_help').':', 3).
						tag($help_source, 'div', ' id="preview-help" class="code"').
						$sub.
						sInput('plugin_install').
						eInput('plugin').
						hInput('plugin64', $plugin_encoded)
					, 'margin: 0 auto; width: 65%;', '', 'post', 'plugin-info', '', 'plugin_preview').
					'</div>';
					return;
				}
			}
		}
		plugin_list(array(gTxt('bad_plugin_code'), E_ERROR));

	}

// -------------------------------------------------------------
	function plugin_install()
	{

		$plugin = ps('plugin64');
		if (strpos($plugin, '$plugin=\'') !== false) {
			@ini_set('pcre.backtrack_limit', '1000000');
			$plugin = preg_replace('@.*\$plugin=\'([\w=+/]+)\'.*@s', '$1', $plugin);
		}

		$plugin = preg_replace('/^#.*$/m', '', $plugin);

		if(trim($plugin)) {

			$plugin = base64_decode($plugin);
			if (strncmp($plugin,"\x1F\x8B",2)===0)
				$plugin = gzinflate(substr($plugin, 10));

			if ($plugin = unserialize($plugin)) {

				if(is_array($plugin)){

					extract($plugin);

					$type  = empty($type)  ? 0 : min(max(intval($type), 0), 3);
					$order = empty($order) ? 5 : min(max(intval($order), 1), 9);
					$flags = empty($flags) ? 0 : intval($flags);

					$exists = fetch('name','txp_plugin','name',$name);

					if (isset($help_raw) && empty($plugin['allow_html_help'])) {
							// default: help is in Textile format
							include_once txpath.'/lib/classTextile.php';
							$textile = new Textile();
							$help = $textile->TextileRestricted($help_raw, 0, 0);
					}

					if ($exists) {
						$rs = safe_update(
						   "txp_plugin",
							"status      = 0,
							type         = $type,
							author       = '".doSlash($author)."',
							author_uri   = '".doSlash($author_uri)."',
							version      = '".doSlash($version)."',
							description  = '".doSlash($description)."',
							help         = '".doSlash($help)."',
							code         = '".doSlash($code)."',
							code_restore = '".doSlash($code)."',
							code_md5     = '".doSlash($md5)."',
							flags     	 = $flags",
							"name        = '".doSlash($name)."'"
						);

					} else {

						$rs = safe_insert(
						   "txp_plugin",
						   "name         = '".doSlash($name)."',
							status       = 0,
							type         = $type,
							author       = '".doSlash($author)."',
							author_uri   = '".doSlash($author_uri)."',
							version      = '".doSlash($version)."',
							description  = '".doSlash($description)."',
							help         = '".doSlash($help)."',
							code         = '".doSlash($code)."',
							code_restore = '".doSlash($code)."',
							code_md5     = '".doSlash($md5)."',
							load_order   = '".$order."',
							flags   	 = $flags"
						);
					}

					if ($rs and $code)
					{
						if (!empty($textpack))
						{
							install_textpack($textpack, false);
							// TODO: How do we get rid of stale Textpacks once a plugin is uninstalled?
						}

						if ($flags & PLUGIN_LIFECYCLE_NOTIFY)
						{
							load_plugin($name, true);
							$message = callback_event("plugin_lifecycle.$name", 'installed');
						}

						if (empty($message)) $message = gTxt('plugin_installed', array('{name}' => $name));

						plugin_list($message);
						return;
					}

					else
					{
						$message = array(gTxt('plugin_install_failed', array('{name}' => $name)), E_ERROR);

						plugin_list($message);
						return;
					}
				}
			}
		}
		plugin_list(array(gTxt('bad_plugin_code'), E_ERROR));
	}

// -------------------------------------------------------------

	function plugin_form()
	{
		return n.n.form(
			graf(
			tag(gTxt('install_plugin'), 'span', ' style="vertical-align: top;"').sp.

			'<textarea id="plugin-install" class="code" name="plugin" cols="62" rows="1"></textarea>'.sp.

			tag(
				popHelp('install_plugin').sp.
				fInput('submit', 'install_new', gTxt('upload'), 'smallerbox')
		   , 'span', ' style="vertical-align: 6px;"').

				eInput('plugin').
				sInput('plugin_verify')
			)
		, 'text-align: center;', '', 'post', 'plugin-data', '', 'plugin_install_form');
	}

// -------------------------------------------------------------

	function plugin_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changestatus' => gTxt('changestatus'),
			'changeorder'  => gTxt('changeorder'),
			'delete'       => gTxt('delete')
		);

		return event_multiedit_form('plugin', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function plugin_multi_edit()
	{
		$selected = ps('selected');
		$method   = ps('edit_method');

		if (!$selected or !is_array($selected))
		{
			return plugin_list();
		}

		$where = "name IN ('".join("','", doSlash($selected))."')";

		switch ($method)
		{
			case 'delete':
				foreach ($selected as $name)
				{
					if (safe_field('flags', 'txp_plugin', "name ='".doSlash($name)."'") & PLUGIN_LIFECYCLE_NOTIFY)
					{
						load_plugin($name, true);
						callback_event("plugin_lifecycle.$name", 'disabled');
						callback_event("plugin_lifecycle.$name", 'deleted');
					}
				}
				safe_delete('txp_plugin', $where);
				break;

			case 'changestatus':
				foreach ($selected as $name)
				{
					if (safe_field('flags', 'txp_plugin', "name ='".doSlash($name)."'") & PLUGIN_LIFECYCLE_NOTIFY)
					{
						$status = safe_field('status', 'txp_plugin', "name ='".doSlash($name)."'");
						load_plugin($name, true);
						// NB: won't show returned messages anywhere due to potentially overwhelming verbiage.
						callback_event("plugin_lifecycle.$name", $status ? 'disabled' : 'enabled');
					}
				}
				safe_update('txp_plugin', 'status = (1-status)', $where);
				break;

			case 'changeorder':
				$order = min(max(intval(ps('order')), 1), 9);
				safe_update('txp_plugin', 'load_order = '.$order, $where);
				break;
		}

		$message = gTxt('plugin_'.($method == 'delete' ? 'deleted' : 'updated'), array('{name}' => join(', ', $selected)));

		plugin_list($message);
	}
?>

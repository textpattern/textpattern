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
			'plugin_edit'       => true,
			'plugin_help'       => false,
			'plugin_list'       => false,
			'plugin_install'    => true,
			'plugin_save'       => true,
			'plugin_verify'     => true,
			'switch_status'     => true,
			'plugin_multi_edit' => true
		);

		if ($step && bouncer($step, $available_steps)) {
			$step();
		} else {
			plugin_list();
		}
	}

// -------------------------------------------------------------

	function plugin_list($message = '')
	{
		global $event;

		pagetop(gTxt('tab_plugins'), $message);

		echo '<h1 class="txp-heading">'.gTxt('tab_plugins').'</h1>';
		echo '<div id="'.$event.'_control" class="txp-control-panel">';
		echo n.plugin_form().
			n.'</div>';

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
			echo n.'<div id="'.$event.'_container" class="txp-container">';
			echo '<form action="index.php" id="plugin_form" class="multi_edit_form" method="post" name="longform">'.

			n.'<div class="txp-listtables">'.
			n. startTable('', '', 'txp-list').
			n.'<thead>'.
			tr(
				n.hCell(fInput('checkbox', 'select_all', 0, '', '', '', '', '', 'select_all'), '', ' title="'.gTxt('toggle_all_selected').'" class="multi-edit"').
				n.column_head('plugin', 'name', 'plugin', true, $switch_dir, '', '', (('name' == $sort) ? "$dir " : '').'name').
				n.column_head('author', 'author', 'plugin', true, $switch_dir, '', '', (('author' == $sort) ? "$dir " : '').'author').
				n.column_head('version', 'version', 'plugin', true, $switch_dir, '', '', (('version' == $sort) ? "$dir " : '').'version').
				n.column_head('plugin_modified', 'modified', 'plugin', true, $switch_dir, '', '', (('modified' == $sort) ? "$dir " : '').'modified').
				n.hCell(gTxt('description'), '', ' class="description"').
				n.column_head('active', 'status', 'plugin', true, $switch_dir, '', '', (('status' == $sort) ? "$dir " : '').'status').
				n.column_head('order', 'load_order', 'plugin', true, $switch_dir, '', '', (('load_order' == $sort) ? "$dir " : '').'load-order').
				n.hCell(gTxt('manage'), '',  ' class="manage actions"')
			).
			n.'</thead>';

			echo '<tbody>';

			while ($a = nextRow($rs))
			{
				foreach ($a as $key => $value) {
					$$key = txpspecialchars($value);
				}
				// Fix up the description for clean cases
				$description = preg_replace(array('#&lt;br /&gt;#',
												  '#&lt;(/?(a|b|i|em|strong))&gt;#',
												  '#&lt;a href=&quot;(https?|\.|\/|ftp)([A-Za-z0-9:/?.=_]+?)&quot;&gt;#'),
											array('<br />','<$1>','<a href="$1$2">'),
											$description);

				$help = !empty($help) ?
					'<a class="plugin-help" href="?event=plugin'.a.'step=plugin_help'.a.'name='.urlencode($name).'">'.gTxt('help').'</a>' : '';

				$plugin_prefs = ($flags & PLUGIN_HAS_PREFS) ?
					'<a class="plugin-prefs" href="?event=plugin_prefs.'.urlencode($name).'">'.gTxt('plugin_prefs').'</a>' : '';

				$manage = array();

				if ($help)
				{
					$manage[] = $help;
				}
				if ($plugin_prefs)
				{
					$manage[] = $plugin_prefs;
				}

				$manage_items = ($manage) ? join(tag(sp.'&#124;'.sp, 'span'), $manage) : '-';
				$edit_url = eLink('plugin', 'plugin_edit', 'name', $name, $name);

				echo tr(
					n.td(
						fInput('checkbox', 'selected[]', $name)
					,'', 'multi-edit').

					td($edit_url, '', 'name').

					td(
						href($author, $author_uri, ' rel="external"')
					, '', 'author').

					td($version, '', 'version').
					td(($modified ? '<span class="warning">'.gTxt('yes').'</span>' : ''), '', 'modified').
					td($description, '', 'description').

					td(
						status_link($status, $name, yes_no($status))
					, '', 'status').

					td($load_order, '', 'load-order').
					td($manage_items, '', 'manage')
				, $status ? ' class="active"' : '');

				unset($name, $page, $deletelink);
			}

			echo '</tbody>',
				n, endTable(),
				n, '</div>',
				n, plugin_multiedit_form('', $sort, $dir, '', ''),
				n, tInput(),
				n, '</form>',
				n, '</div>';

			// Show/hide "Options" link by setting the appropriate class on the plugins TR
			echo script_js(<<<EOS
textpattern.Relay.register('txpAsyncHref.success', function(event, data) {
	$(data['this']).closest('tr').toggleClass('active');
});
EOS
			);
		}
	}

// -------------------------------------------------------------

	function switch_status()
	{
		extract(array_map('assert_string', gpsa(array('thing', 'value'))));
		$change = ($value == gTxt('yes')) ? 0 : 1;

		safe_update('txp_plugin', "status = $change", "name = '".doSlash($thing)."'");

		if (safe_field('flags', 'txp_plugin', "name ='".doSlash($thing)."'") & PLUGIN_LIFECYCLE_NOTIFY)
		{
			load_plugin($thing, true);
			$message = callback_event("plugin_lifecycle.$thing", $change ? 'enabled' : 'disabled');
		}

		// TODO: Remove non-AJAX alternative code path in future version
		if (!AJAXALLY_CHALLENGED) {
			echo gTxt($change ? 'yes' : 'no');
		} else {
			if (empty($message)) $message = gTxt('plugin_updated', array('{name}' => $thing));
			plugin_list($message);
		}
	}

// -------------------------------------------------------------
	function plugin_edit()
	{
		global $event;

		$name = gps('name');
		pagetop(gTxt('edit_plugins'));

		echo n.'<div id="'.$event.'_container" class="txp-container">';
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
		echo '<div id="'.$event.'_container" class="txp-container txp-view">'
			.'<div class="text-column">' . $help . '</div>'
			.'</div>';
	}

// -------------------------------------------------------------
	function plugin_edit_form($name='')
	{
		assert_string($name);
		$code = ($name) ? fetch('code','txp_plugin','name',$name) : '';
		$thing = ($code) ? $code : '';

		return
			form(
				hed(gTxt('edit_plugin', array('{name}' => $name)), 2).n.
				graf('<textarea id="plugin_code" name="code" class="code" cols="'.INPUT_XLARGE.'" rows="'.INPUT_REGULAR.'">'.txpspecialchars($thing).'</textarea>', ' class="edit-plugin-code"').n.
				graf(fInput('submit', '', gTxt('Save'), 'publish')).n.
				eInput('plugin').n.
				sInput('plugin_save').n.
				hInput('name',$name)
			, '', '', 'post', 'edit-form', '', 'plugin_details');
	}

// -------------------------------------------------------------

	function plugin_save()
	{
		extract(doSlash(array_map('assert_string', gpsa(array('name', 'code')))));

		safe_update('txp_plugin', "code = '$code'", "name = '$name'");

		$message = gTxt('plugin_saved', array('{name}' => $name));

		plugin_list($message);
	}

// -------------------------------------------------------------

	function status_link($status,$name,$linktext)
	{
		return asyncHref($linktext, array('step' => 'switch_status', 'thing' => $name),' title="'.($status==1 ? gTxt('disable') : gTxt('enable')).'"' );
	}

// -------------------------------------------------------------
	function plugin_verify()
	{
		global $event;

		if (ps('txt_plugin')) {
			$plugin = join("\n", file($_FILES['theplugin']['tmp_name']));
		} else {
			$plugin = assert_string(ps('plugin'));
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

					pagetop(gTxt('verify_plugin'));
					echo
					'<div id="'.$event.'_container" class="txp-container txp-view">'.
					form(
						hed(gTxt('previewing_plugin'), 2).
						tag($source, 'div', ' id="preview-plugin" class="code"').
						hed(gTxt('plugin_help').':', 2).
						tag($help_source, 'div', ' id="preview-help" class="code"').
						$sub.
						sInput('plugin_install').
						eInput('plugin').
						hInput('plugin64', $plugin_encoded)
					, '', '', 'post', 'plugin-info', '', 'plugin_preview').
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

		$plugin = assert_string(ps('plugin64'));
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

					$type  = empty($type)  ? 0 : min(max(intval($type), 0), 5);
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
							"type        = $type,
							author       = '".doSlash($author)."',
							author_uri   = '".doSlash($author_uri)."',
							version      = '".doSlash($version)."',
							description  = '".doSlash($description)."',
							help         = '".doSlash($help)."',
							code         = '".doSlash($code)."',
							code_restore = '".doSlash($code)."',
							code_md5     = '".doSlash($md5)."',
							flags        = $flags",
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
							flags        = $flags"
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
			'<p>'.
			tag(gTxt('install_plugin'), 'label', ' for="plugin-install"').sp.popHelp('install_plugin').n.
			'<textarea id="plugin-install" class="code" name="plugin" cols="'.INPUT_LARGE.'" rows="'.INPUT_TINY.'"></textarea>'.n.
			fInput('submit', 'install_new', gTxt('upload')).
			eInput('plugin').
			sInput('plugin_verify').
			'</p>'
		, '', '', 'post', 'plugin-data', '', 'plugin_install_form');
	}

// -------------------------------------------------------------

	function plugin_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$orders = selectInput('order', array(1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9), 5, false);

		$methods = array(
			'changestatus' => gTxt('changestatus'),
			'changeorder'  => array('label' => gTxt('changeorder'), 'html' => $orders),
			'delete'       => gTxt('delete')
		);

		return multi_edit($methods, 'plugin', 'plugin_multi_edit', $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function plugin_multi_edit()
	{
		$selected = ps('selected');
		$method   = assert_string(ps('edit_method'));

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
